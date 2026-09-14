<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\LearningPathService;
use App\Service\PrerequisiteEngine;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PathController extends AbstractController
{
    #[Route('/rutas', name: 'app_paths')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        LearningPathService $pathService,
        PrerequisiteEngine $prerequisiteEngine
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('path/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'paths' => $pathService->overview($user),
            'focus' => $pathService->focusPath($user),
            'recommendations' => $prerequisiteEngine->recommendNextLessons($user, $sections, 3),
            'current_tab_title' => 'LearningPaths.yaml',
            'breadcrumb_category' => 'curriculum',
            'breadcrumb_current' => 'Rutas de aprendizaje',
        ]);
    }

    #[Route('/ruta/{pathSlug}', name: 'app_path')]
    public function show(
        string $pathSlug,
        UserRepository $userRepo,
        RoadmapService $roadmapService,
        LearningPathService $pathService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $path = $pathService->detail($user, $pathSlug);

        if ($path === null) {
            $this->addFlash('warning', 'Esa ruta de aprendizaje no existe. Aquí están todas las disponibles.');

            return $this->redirectToRoute('app_paths');
        }

        return $this->render('path/show.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $roadmapService->getSections(),
            'path' => $path,
            'current_tab_title' => $pathSlug . '.path',
            'breadcrumb_category' => 'rutas',
            'breadcrumb_current' => $path['title'],
        ]);
    }
}
