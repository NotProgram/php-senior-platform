<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\ExamService;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class ExamController extends AbstractController
{
    #[Route('/examen', name: 'app_exam')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        ExamService $examService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $exam = $examService->buildExam($user, 12);

        return $this->render('exam/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $roadmapService->getSections(),
            'exam' => $exam,
            'current_tab_title' => 'CertificationExam.test',
            'breadcrumb_category' => 'evaluations',
            'breadcrumb_current' => 'Simulador de Examen',
        ]);
    }

    #[Route('/examen/submit', name: 'app_exam_submit', methods: ['POST'])]
    public function submit(
        Request $request,
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        ExamService $examService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $answers = $request->request->all('answers');

        if (!is_array($answers) || count($answers) === 0) {
            $this->addFlash('warning', 'Debes responder al menos una pregunta antes de enviar el examen.');

            return $this->redirectToRoute('app_exam');
        }

        $result = $examService->evaluate($user, $answers);

        if ($result['passed']) {
            $this->addFlash('success', sprintf(
                '¡Excelente trabajo! Has aprobado la evaluación con %d%% de aciertos. (+%d XP ganados)',
                $result['score'],
                $result['correct'] * 15
            ));
        } else {
            $this->addFlash('warning', sprintf(
                'Obtuviste %d%% en la evaluación (umbral de aprobación: 75%%). Revisa las explicaciones por módulo para reforzar los conceptos.',
                $result['score']
            ));
        }

        return $this->render('exam/result.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $roadmapService->getSections(),
            'result' => $result,
            'current_tab_title' => 'ExamReport.json',
            'breadcrumb_category' => 'evaluations',
            'breadcrumb_current' => 'Reporte de Certificación',
        ]);
    }
}
