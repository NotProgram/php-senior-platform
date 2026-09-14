<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\LearningProgressService;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\Routing\Attribute\Route;

class SettingsController extends AbstractController
{
    private const CSRF_TOKEN_ID = 'submit';

    #[Route('/settings', name: 'app_settings')]
    public function index(UserRepository $userRepo, RoadmapService $roadmapService): Response
    {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('settings/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'Settings.json',
            'breadcrumb_category' => 'preferences',
            'breadcrumb_current' => 'Configuración',
        ]);
    }

    #[Route('/settings/reset-progress', name: 'app_settings_reset_progress', methods: ['POST'])]
    public function resetProgress(
        Request $request,
        UserRepository $userRepo,
        LearningProgressService $learningProgress
    ): Response {
        if (!$this->isCsrfTokenValid(self::CSRF_TOKEN_ID, $request->getPayload()->getString('_csrf_token'))) {
            throw new AccessDeniedHttpException('Token CSRF inválido.');
        }

        $learningProgress->resetProgress($userRepo->findOrCreateDefaultUser());

        $this->addFlash('success', 'Progreso de aprendizaje reiniciado a cero exitosamente. Puedes comenzar desde el inicio.');

        return $this->redirectToRoute('app_roadmap');
    }
}
