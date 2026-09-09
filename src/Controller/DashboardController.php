<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\DashboardService;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DashboardController extends AbstractController
{
    #[Route('/', name: 'app_dashboard')]
    public function index(
        UserRepository $userRepository,
        DashboardService $dashboardService,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepository->findOrCreateDefaultUser();
        $metrics = $dashboardService->buildMetrics($user);
        $sections = $roadmapService->getSections();

        return $this->render('dashboard/index.html.twig', [
            'current_user' => $user,
            'metrics' => $metrics,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'Dashboard.php',
            'breadcrumb_category' => 'overview',
            'breadcrumb_current' => 'Dashboard',
        ]);
    }
}
