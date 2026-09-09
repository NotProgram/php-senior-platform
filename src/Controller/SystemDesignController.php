<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\SystemDesignStateRepository;
use App\Repository\UserRepository;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SystemDesignController extends AbstractController
{
    #[Route('/system-design', name: 'app_system_design')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        SystemDesignStateRepository $designStateRepo
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();
        $labState = $designStateRepo->findOrCreateForScenario($user, 'ecommerce-high-throughput');

        return $this->render('system_design/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'lab_state' => $labState,
            'current_tab_title' => 'SystemDesignLab.canvas',
            'breadcrumb_category' => 'architecture',
            'breadcrumb_current' => 'System Design Lab',
        ]);
    }
}
