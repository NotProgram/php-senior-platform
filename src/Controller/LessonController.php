<?php

declare(strict_types=1);

namespace App\Controller;

use App\DTO\LessonLocation;
use App\Entity\User;
use App\Enum\ProgressStatus;
use App\Repository\ExerciseAttemptRepository;
use App\Repository\QuizAttemptRepository;
use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use App\Service\ExerciseEvaluatorService;
use App\Service\LearningProgressService;
use App\Service\LessonContentService;
use App\Service\PrerequisiteEngine;
use App\Service\QuizEvaluatorService;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class LessonController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'submit';

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoadmapService $roadmapService,
        private readonly PrerequisiteEngine $prerequisiteEngine,
    ) {}

    #[Route('/lesson/{lessonSlug}', name: 'app_lesson', methods: ['GET'])]
    public function show(
        string $lessonSlug,
        UserProgressRepository $progressRepo,
        LessonContentService $contentService,
        QuizAttemptRepository $quizAttemptRepo,
        ExerciseAttemptRepository $exerciseAttemptRepo
    ): Response {
        $location = $this->findLessonOr404($lessonSlug);
        $details = $contentService->findLesson($lessonSlug)
            ?? throw $this->createNotFoundException(sprintf('La lección "%s" no tiene contenido.', $lessonSlug));
        $user = $this->userRepository->findOrCreateDefaultUser();

        // Enforce strict access control through Prerequisite Engine
        $access = $this->prerequisiteEngine->canAccessLesson($user, $lessonSlug);
        if (!$access['allowed']) {
            $this->addFlash('warning', sprintf(
                'Esta lección está bloqueada. Debes completar primero los fundamentos y requisitos previos (Completar: %s).',
                $access['missingPrerequisite'] ?? 'lección anterior'
            ));

            return $this->redirectToRoute('app_roadmap');
        }

        return $this->render('lesson/show.html.twig', [
            'current_user' => $user,
            'lesson' => $location->lesson,
            'module' => $location->module,
            'module_key' => $location->moduleSlug,
            'progress' => $progressRepo->findProgress($user, $lessonSlug),
            'current_status' => $access['status'],
            'roadmap_sections' => $this->roadmapService->getSections(),
            'current_lesson_slug' => $lessonSlug,
            'current_tab_title' => $lessonSlug . '.php',
            'breadcrumb_category' => $location->module['title'],
            'breadcrumb_current' => $location->lesson['title'],
            'details' => $details,
            'latest_quiz' => $quizAttemptRepo->findOneBy(['user' => $user, 'lessonSlug' => $lessonSlug], ['attemptedAt' => 'DESC']),
            'latest_exercise' => $exerciseAttemptRepo->findOneBy(['user' => $user, 'lessonSlug' => $lessonSlug], ['executedAt' => 'DESC']),
        ]);
    }

    #[Route('/lesson/{lessonSlug}/complete', name: 'app_lesson_complete', methods: ['POST'])]
    public function complete(string $lessonSlug, Request $request, LearningProgressService $learningProgress): Response
    {
        $user = $this->userRepository->findOrCreateDefaultUser();
        $denied = $this->denyUnlessLessonActionAllowed($request, $lessonSlug, $user);
        if ($denied !== null) {
            return $denied;
        }

        $xpAwarded = $learningProgress->completeLesson($user, $lessonSlug);
        if ($xpAwarded > 0) {
            $this->addFlash('success', sprintf('Lección completada exitosamente. +%d XP acumulados. Siguiente lección desbloqueada.', $xpAwarded));
        } else {
            $this->addFlash('warning', 'Esta lección ya estaba completada; no se suman XP adicionales.');
        }

        return $this->redirectToRoute('app_lesson', ['lessonSlug' => $lessonSlug]);
    }

    #[Route('/lesson/{lessonSlug}/quiz', name: 'app_lesson_quiz', methods: ['POST'])]
    public function submitQuiz(string $lessonSlug, Request $request, QuizEvaluatorService $quizEvaluator): Response
    {
        $user = $this->userRepository->findOrCreateDefaultUser();
        $denied = $this->denyUnlessLessonActionAllowed($request, $lessonSlug, $user);
        if ($denied !== null) {
            return $denied;
        }

        $result = $quizEvaluator->evaluate($user, $lessonSlug, $request->request->all('answers'));

        if (!$result['success']) {
            $this->addFlash('warning', $result['message']);
        } elseif ($result['passed']) {
            $this->addFlash('success', $result['xp_awarded'] > 0
                ? sprintf('¡Evaluación aprobada con %d%%! (+%d XP). Demostraste criterio técnico sólido.', $result['score_percentage'], $result['xp_awarded'])
                : sprintf('¡Evaluación aprobada con %d%%! Ya habías alcanzado este nivel en la lección, así que no se suman XP.', $result['score_percentage']));
        } else {
            $this->addFlash('warning', sprintf(
                'Obtuviste %d%% en la evaluación (se requiere mínimo %d%%). Revisa las explicaciones de la solución para afianzar tus conceptos.',
                $result['score_percentage'],
                QuizEvaluatorService::PASSING_PERCENTAGE
            ));
        }

        return $this->redirectToRoute('app_lesson', [
            'lessonSlug' => $lessonSlug,
            '_fragment' => 'quiz-section',
        ]);
    }

    #[Route('/lesson/{lessonSlug}/exercise', name: 'app_lesson_exercise', methods: ['POST'])]
    public function submitExercise(string $lessonSlug, Request $request, ExerciseEvaluatorService $exerciseEvaluator): Response
    {
        $user = $this->userRepository->findOrCreateDefaultUser();
        $denied = $this->denyUnlessLessonActionAllowed($request, $lessonSlug, $user);
        if ($denied !== null) {
            return $denied;
        }

        $result = $exerciseEvaluator->evaluateCode($user, $lessonSlug, $request->getPayload()->getString('submitted_code'));

        if ($result['passed']) {
            $this->addFlash('success', $result['xp_awarded'] > 0
                ? sprintf('¡Reto de código superado con éxito! (+%d XP). Se han aplicado los estándares Senior.', $result['xp_awarded'])
                : '¡Reto de código superado de nuevo! Ya habías alcanzado este nivel en la lección, así que no se suman XP.');
        } else {
            $this->addFlash('warning', 'El código enviado no cumple todas las restricciones. Revisa las sugerencias:');
            foreach ($result['hints'] as $hint) {
                $this->addFlash('warning', $hint);
            }
        }

        return $this->redirectToRoute('app_lesson', [
            'lessonSlug' => $lessonSlug,
            '_fragment' => 'exercise-section',
        ]);
    }

    private function findLessonOr404(string $lessonSlug): LessonLocation
    {
        return $this->roadmapService->findLessonLocation($lessonSlug)
            ?? throw $this->createNotFoundException(sprintf('La lección "%s" no existe.', $lessonSlug));
    }

    /**
     * Preconditions shared by every state-changing lesson action: valid CSRF token, known lesson, prerequisites met.
     */
    private function denyUnlessLessonActionAllowed(Request $request, string $lessonSlug, User $user): ?Response
    {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        $this->findLessonOr404($lessonSlug);

        if ($this->prerequisiteEngine->resolveLessonStatus($user, $lessonSlug) === ProgressStatus::Locked) {
            $this->addFlash('warning', 'Esta lección está bloqueada: completa primero sus requisitos previos.');

            return $this->redirectToRoute('app_roadmap');
        }

        return null;
    }
}
