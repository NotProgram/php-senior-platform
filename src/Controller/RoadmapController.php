<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use App\Service\PrerequisiteEngine;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class RoadmapController extends AbstractController
{
    #[Route('/roadmap', name: 'app_roadmap')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        PrerequisiteEngine $prerequisiteEngine
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();
        $statusMap = $prerequisiteEngine->resolveFullStatusMap($user, $sections);

        // Aggregate counts by state
        $counts = [
            'total' => 0,
            'locked' => 0,
            'available' => 0,
            'in_progress' => 0,
            'completed' => 0,
            'mastered' => 0,
        ];

        foreach ($statusMap as $status) {
            $counts['total']++;
            match ($status) {
                \App\Enum\ProgressStatus::Locked => $counts['locked']++,
                \App\Enum\ProgressStatus::Available => $counts['available']++,
                \App\Enum\ProgressStatus::InProgress => $counts['in_progress']++,
                \App\Enum\ProgressStatus::Completed => $counts['completed']++,
                \App\Enum\ProgressStatus::Mastered => $counts['mastered']++,
            };
        }

        return $this->render('roadmap/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'career_levels' => $roadmapService->getCareerLevels(),
            'status_map' => $statusMap,
            'counts' => $counts,
            'current_tab_title' => 'Roadmap.yaml',
            'breadcrumb_category' => 'curriculum',
            'breadcrumb_current' => 'Senior Roadmap',
        ]);
    }

    #[Route('/module/{moduleSlug}', name: 'app_module')]
    public function module(
        string $moduleSlug,
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        PrerequisiteEngine $prerequisiteEngine
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();
        $module = $sections[$moduleSlug] ?? null;

        if (!$module) {
            return $this->redirectToRoute('app_roadmap');
        }

        $statusMap = $prerequisiteEngine->resolveFullStatusMap($user, [$moduleSlug => $module]);

        $totalMinutes = 0;
        if (isset($module['lessons']) && is_array($module['lessons'])) {
            foreach ($module['lessons'] as $lesson) {
                $totalMinutes += (int) ($lesson['minutes'] ?? 45);
            }
        }
        $module['total_minutes'] = $totalMinutes > 0 ? $totalMinutes : 180;

        return $this->render('roadmap/module.html.twig', [
            'current_user' => $user,
            'module' => $module,
            'module_slug' => $moduleSlug,
            'status_map' => $statusMap,
            'roadmap_sections' => $sections,
            'current_tab_title' => $module['title'] . '.md',
            'breadcrumb_category' => 'modules',
            'breadcrumb_current' => $module['title'],
        ]);
    }
}
