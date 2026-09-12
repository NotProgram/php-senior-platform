<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\UserRepository;
use App\Service\RoadmapService;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class LabController extends AbstractController
{
    #[Route('/labs', name: 'app_labs')]
    public function index(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/index.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'LabsHub.vue',
            'breadcrumb_category' => 'laboratories',
            'breadcrumb_current' => 'Laboratorios Interactivos',
        ]);
    }

    #[Route('/lab/git', name: 'app_lab_git')]
    public function gitLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/git.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'GitSimulator.sh',
            'breadcrumb_category' => 'git',
            'breadcrumb_current' => 'Git & Conflict Simulator',
        ]);
    }

    #[Route('/lab/testing', name: 'app_lab_testing')]
    public function testingLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/testing.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'PhpUnitTestRunner.php',
            'breadcrumb_category' => 'testing',
            'breadcrumb_current' => 'PHPUnit Interactive Lab',
        ]);
    }

    #[Route('/lab/symfony-architecture', name: 'app_lab_symfony_arch')]
    public function symfonyArchitectureLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/symfony_architecture.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'HttpKernelLifecycle.flow',
            'breadcrumb_category' => 'symfony',
            'breadcrumb_current' => 'Symfony Architecture & Lifecycle Lab',
        ]);
    }

    #[Route('/lab/dependency-injection', name: 'app_lab_di')]
    public function dependencyInjectionLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/dependency_injection.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'ServiceContainerGraph.dot',
            'breadcrumb_category' => 'architecture',
            'breadcrumb_current' => 'Dependency Injection Lab',
        ]);
    }

    #[Route('/lab/doctrine', name: 'app_lab_doctrine')]
    public function doctrineLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/doctrine.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'UnitOfWorkInspector.sql',
            'breadcrumb_category' => 'doctrine',
            'breadcrumb_current' => 'Doctrine Unit of Work Lab',
        ]);
    }

    #[Route('/lab/code-review', name: 'app_lab_code_review')]
    public function codeReviewLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/code_review.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'PullRequestReview.diff',
            'breadcrumb_category' => 'quality',
            'breadcrumb_current' => 'Code Review Lab',
        ]);
    }

    #[Route('/lab/adr', name: 'app_lab_adr')]
    public function adrLab(
        UserRepository $userRepo,
        RoadmapService $roadmapService
    ): Response {
        $user = $userRepo->findOrCreateDefaultUser();
        $sections = $roadmapService->getSections();

        return $this->render('lab/adr.html.twig', [
            'current_user' => $user,
            'roadmap_sections' => $sections,
            'current_tab_title' => 'ADRStudio.md',
            'breadcrumb_category' => 'architecture',
            'breadcrumb_current' => 'Architecture Decision Records Lab',
        ]);
    }
}
