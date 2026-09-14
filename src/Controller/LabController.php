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
    /**
     * @var array<string, array{template: string, tab: string, category: string, title: string}>
     */
    private const LABS = [
        'git' => ['template' => 'lab/git.html.twig', 'tab' => 'GitSimulator.sh', 'category' => 'git', 'title' => 'Git & Conflict Simulator'],
        'testing' => ['template' => 'lab/testing.html.twig', 'tab' => 'PhpUnitTestRunner.php', 'category' => 'testing', 'title' => 'PHPUnit Interactive Lab'],
        'symfony-architecture' => ['template' => 'lab/symfony_architecture.html.twig', 'tab' => 'HttpKernelLifecycle.flow', 'category' => 'symfony', 'title' => 'Symfony Architecture & Lifecycle Lab'],
        'dependency-injection' => ['template' => 'lab/dependency_injection.html.twig', 'tab' => 'ServiceContainerGraph.dot', 'category' => 'architecture', 'title' => 'Dependency Injection Lab'],
        'doctrine' => ['template' => 'lab/doctrine.html.twig', 'tab' => 'UnitOfWorkInspector.sql', 'category' => 'doctrine', 'title' => 'Doctrine Unit of Work Lab'],
        'code-review' => ['template' => 'lab/code_review.html.twig', 'tab' => 'PullRequestReview.diff', 'category' => 'quality', 'title' => 'Code Review Lab'],
        'adr' => ['template' => 'lab/adr.html.twig', 'tab' => 'ADRStudio.md', 'category' => 'architecture', 'title' => 'Architecture Decision Records Lab'],
    ];

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly RoadmapService $roadmapService,
    ) {}

    #[Route('/labs', name: 'app_labs')]
    public function index(): Response
    {
        return $this->renderLab('lab/index.html.twig', 'LabsHub.vue', 'laboratories', 'Laboratorios Interactivos');
    }

    #[Route('/lab/git', name: 'app_lab_git', defaults: ['lab' => 'git'])]
    #[Route('/lab/testing', name: 'app_lab_testing', defaults: ['lab' => 'testing'])]
    #[Route('/lab/symfony-architecture', name: 'app_lab_symfony_arch', defaults: ['lab' => 'symfony-architecture'])]
    #[Route('/lab/dependency-injection', name: 'app_lab_di', defaults: ['lab' => 'dependency-injection'])]
    #[Route('/lab/doctrine', name: 'app_lab_doctrine', defaults: ['lab' => 'doctrine'])]
    #[Route('/lab/code-review', name: 'app_lab_code_review', defaults: ['lab' => 'code-review'])]
    #[Route('/lab/adr', name: 'app_lab_adr', defaults: ['lab' => 'adr'])]
    public function show(string $lab): Response
    {
        $config = self::LABS[$lab];

        return $this->renderLab($config['template'], $config['tab'], $config['category'], $config['title']);
    }

    private function renderLab(string $template, string $tabTitle, string $category, string $title): Response
    {
        return $this->render($template, [
            'current_user' => $this->userRepository->findOrCreateDefaultUser(),
            'roadmap_sections' => $this->roadmapService->getSections(),
            'current_tab_title' => $tabTitle,
            'breadcrumb_category' => $category,
            'breadcrumb_current' => $title,
        ]);
    }
}
