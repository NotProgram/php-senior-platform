<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\ExerciseAttemptRepository;
use App\Repository\QuizAttemptRepository;
use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use App\Service\ExerciseEvaluatorService;
use App\Service\LessonContentService;
use App\Service\PrerequisiteEngine;
use App\Service\QuizEvaluatorService;
use App\Service\RoadmapService;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LessonController extends AbstractController
{
    #[Route('/lesson/{lessonSlug}', name: 'app_lesson')]
    public function show(
        string $lessonSlug,
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        UserProgressRepository $progressRepo,
        PrerequisiteEngine $prerequisiteEngine,
        LessonContentService $contentService,
        QuizAttemptRepository $quizAttemptRepo,
        ExerciseAttemptRepository $exerciseAttemptRepo
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        // 1. Enforce strict access control through Prerequisite Engine
        $access = $prerequisiteEngine->canAccessLesson($user, $lessonSlug);
        if (!$access['allowed']) {
            $this->addFlash('warning', sprintf(
                'Esta lección está bloqueada. Debes completar primero los fundamentos y requisitos previos (Completar: %s).',
                $access['missingPrerequisite'] ?? 'lección anterior'
            ));

            return $this->redirectToRoute('app_roadmap');
        }

        // 2. Find lesson metadata
        $currentLesson = null;
        $parentModule = null;
        $parentModuleKey = null;

        foreach ($sections as $mKey => $module) {
            if (isset($module['lessons'])) {
                foreach ($module['lessons'] as $lesson) {
                    if ($lesson['slug'] === $lessonSlug) {
                        $currentLesson = $lesson;
                        $parentModule = $module;
                        $parentModuleKey = $mKey;
                        break 2;
                    }
                }
            }
        }

        if (!$currentLesson) {
            return $this->redirectToRoute('app_roadmap');
        }

        $progress = $progressRepo->findProgress($user, $lessonSlug);
        $currentStatus = $progress ? ProgressStatus::tryFrom($progress->getStatus()) : ProgressStatus::InProgress;

        // Rich lesson content & historical submissions
        $details = $contentService->getLessonDetails($lessonSlug);
        $latestQuizAttempt = $quizAttemptRepo->findOneBy(['user' => $user, 'lessonSlug' => $lessonSlug], ['attemptedAt' => 'DESC']);
        $latestExerciseAttempt = $exerciseAttemptRepo->findOneBy(['user' => $user, 'lessonSlug' => $lessonSlug], ['executedAt' => 'DESC']);

        return $this->render('lesson/show.html.twig', [
            'current_user' => $user,
            'lesson' => $currentLesson,
            'module' => $parentModule,
            'module_key' => $parentModuleKey,
            'progress' => $progress,
            'current_status' => $currentStatus,
            'roadmap_sections' => $sections,
            'current_lesson_slug' => $lessonSlug,
            'current_tab_title' => $lessonSlug . '.php',
            'breadcrumb_category' => $parentModule['title'],
            'breadcrumb_current' => $currentLesson['title'],
            'details' => $details,
            'latest_quiz' => $latestQuizAttempt,
            'latest_exercise' => $latestExerciseAttempt,
        ]);
    }

    #[Route('/lesson/{lessonSlug}/complete', name: 'app_lesson_complete', methods: ['POST'])]
    public function complete(
        string $lessonSlug,
        Request $request,
        UserRepository $userRepo,
        UserProgressRepository $progressRepo,
        EntityManagerInterface $em
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $progress = $progressRepo->findProgress($user, $lessonSlug);

        if (!$progress) {
            $progress = new UserProgress($user, 'php-fundamentals', $lessonSlug);
            $em->persist($progress);
        }

        $progress->setStatus(ProgressStatus::Completed->value);
        $progress->setScore(100);
        $user->addExperiencePoints(50);
        $user->touchLastActive();

        $em->flush();

        $this->addFlash('success', 'Lección completada exitosamente. +50 XP acumulados. Siguiente lección desbloqueada.');

        return $this->redirectToRoute('app_lesson', ['lessonSlug' => $lessonSlug]);
    }

    #[Route('/lesson/{lessonSlug}/quiz', name: 'app_lesson_quiz', methods: ['POST'])]
    public function submitQuiz(
        string $lessonSlug,
        Request $request,
        UserRepository $userRepo,
        QuizEvaluatorService $quizEvaluator
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $answers = $request->request->all('answers');

        $result = $quizEvaluator->evaluate($user, $lessonSlug, $answers);

        if ($result['passed']) {
            $this->addFlash('success', sprintf(
                '¡Evaluación aprobada con %d%%! (+50 XP). Demostraste criterio técnico sólido.',
                $result['score_percentage']
            ));
        } else {
            $this->addFlash('warning', sprintf(
                'Obtuviste %d%% en la evaluación (se requiere mínimo 80%%). Revisa las explicaciones de la solución para afianzar tus conceptos.',
                $result['score_percentage']
            ));
        }

        return $this->redirectToRoute('app_lesson', [
            'lessonSlug' => $lessonSlug,
            '_fragment' => 'quiz-section',
        ]);
    }

    #[Route('/lesson/{lessonSlug}/exercise', name: 'app_lesson_exercise', methods: ['POST'])]
    public function submitExercise(
        string $lessonSlug,
        Request $request,
        UserRepository $userRepo,
        ExerciseEvaluatorService $exerciseEvaluator
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $submittedCode = (string) $request->request->get('submitted_code', '');

        $result = $exerciseEvaluator->evaluateCode($user, $lessonSlug, $submittedCode);

        if ($result['passed']) {
            $this->addFlash('success', '¡Reto de código superado con éxito! (+50 XP). Se han aplicado los estándares Senior.');
        } else {
            $this->addFlash('warning', 'El código enviado no cumple todas las restricciones. Revisa las sugerencias indicadas.');
        }

        return $this->redirectToRoute('app_lesson', [
            'lessonSlug' => $lessonSlug,
            '_fragment' => 'exercise-section',
        ]);
    }
}
