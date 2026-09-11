<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\DashboardMetrics;
use App\DTO\UnlockedSkill;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Repository\UserProgressRepository;
use DateTimeImmutable;

class DashboardService
{
    public function __construct(
        private readonly RoadmapService $roadmapService,
        private readonly UserProgressRepository $progressRepository
    ) {}

    public function buildMetrics(User $user): DashboardMetrics
    {
        $sections = $this->roadmapService->getSections();
        $progressMap = $this->progressRepository->getProgressMapForUser($user);

        $totalLessons = 0;
        $completedLessons = 0;
        $inProgressLessons = 0;
        $totalModules = 0;
        $completedModules = 0;
        $totalProjects = 0;
        $completedProjects = 0;

        $currentLessonSlug = 'php-request-lifecycle';
        $currentLessonTitle = 'Request Lifecycle & Web Servers';
        $currentLessonModule = 'PHP Moderno';
        $foundCurrent = false;

        $mostRecentUpdate = null;
        $mostRecentLessonTitle = null;

        $catCounts = [
            'se' => ['total' => 0, 'completed' => 0],
            'git' => ['total' => 0, 'completed' => 0],
            'php' => ['total' => 0, 'completed' => 0],
            'symfony' => ['total' => 0, 'completed' => 0],
            'testing' => ['total' => 0, 'completed' => 0],
            'arch' => ['total' => 0, 'completed' => 0],
            'devops' => ['total' => 0, 'completed' => 0],
            'prof' => ['total' => 0, 'completed' => 0],
        ];

        foreach ($sections as $mKey => $module) {
            if (!isset($module['lessons'])) {
                continue;
            }

            $totalModules++;
            $moduleLessonsCount = count($module['lessons']);
            $moduleCompletedCount = 0;

            $isProjectModule = ($mKey === 'projects');
            $categoryBucket = match ($mKey) {
                'software-engineering' => 'se',
                'git' => 'git',
                'php-fundamentals', 'poo' => 'php',
                'symfony', 'twig', 'databases', 'doctrine', 'apis', 'design-patterns' => 'symfony',
                'testing' => 'testing',
                'architecture', 'system-design', 'security', 'performance' => 'arch',
                'devops' => 'devops',
                'professional-developer', 'projects', 'evaluations', 'resources' => 'prof',
                default => 'php',
            };

            foreach ($module['lessons'] as $lesson) {
                $totalLessons++;
                $catCounts[$categoryBucket]['total']++;

                if ($isProjectModule) {
                    $totalProjects++;
                }

                $prog = $progressMap[$lesson['slug']] ?? null;

                if ($prog !== null) {
                    if ($prog->getStatus() === UserProgress::STATUS_COMPLETED || $prog->getStatus() === UserProgress::STATUS_MASTERED) {
                        $completedLessons++;
                        $moduleCompletedCount++;
                        $catCounts[$categoryBucket]['completed']++;

                        if ($isProjectModule) {
                            $completedProjects++;
                        }
                    } elseif ($prog->getStatus() === UserProgress::STATUS_IN_PROGRESS) {
                        $inProgressLessons++;
                        if (!$foundCurrent) {
                            $currentLessonSlug = $lesson['slug'];
                            $currentLessonTitle = $lesson['title'];
                            $currentLessonModule = $module['title'];
                            $foundCurrent = true;
                        }
                    }

                    if ($mostRecentUpdate === null || $prog->getUpdatedAt() > $mostRecentUpdate) {
                        $mostRecentUpdate = $prog->getUpdatedAt();
                        $mostRecentLessonTitle = $lesson['title'];
                    }
                } elseif (!$foundCurrent) {
                    // First unstarted lesson becomes current
                    $currentLessonSlug = $lesson['slug'];
                    $currentLessonTitle = $lesson['title'];
                    $currentLessonModule = $module['title'];
                    $foundCurrent = true;
                }
            }

            if ($moduleCompletedCount === $moduleLessonsCount && $moduleLessonsCount > 0) {
                $completedModules++;
            }
        }

        $completionPercentage = $totalLessons > 0 ? (int) round(($completedLessons / $totalLessons) * 100) : 0;
        $hoursEstimated = (int) round($totalLessons * 0.75);

        $calcPct = static fn(array $c): int => $c['total'] > 0 ? (int) round(($c['completed'] / $c['total']) * 100) : 0;

        $seProgress = $calcPct($catCounts['se']);
        $gitProgress = $calcPct($catCounts['git']);
        $phpProgress = $calcPct($catCounts['php']);
        $symfonyProgress = $calcPct($catCounts['symfony']);
        $testingProgress = $calcPct($catCounts['testing']);
        $archProgress = $calcPct($catCounts['arch']);
        $devopsProgress = $calcPct($catCounts['devops']);
        $profProgress = $calcPct($catCounts['prof']);

        // Calculate relative time for last activity
        if ($mostRecentUpdate !== null) {
            $lastActivityText = 'Trabajó en: ' . $mostRecentLessonTitle;
            $lastActivityAgo = $this->formatRelativeTime($mostRecentUpdate);
        } else {
            $lastActivityText = 'Perfil inicializado para el Senior Track';
            $lastActivityAgo = $this->formatRelativeTime($user->getCreatedAt());
        }

        // Generate skills matrix based on completed lessons
        $skills = $this->resolveSkillsMatrix($progressMap);

        return new DashboardMetrics(
            user: $user,
            totalLessons: $totalLessons,
            completedLessons: $completedLessons,
            inProgressLessons: $inProgressLessons,
            completionPercentage: $completionPercentage,
            totalModules: $totalModules,
            completedModules: $completedModules,
            hoursEstimated: $hoursEstimated,
            streakDays: $user->getStreakDays(),
            currentLevel: $user->getCurrentLevel(),
            currentLessonSlug: $currentLessonSlug,
            currentLessonTitle: $currentLessonTitle,
            currentLessonModule: $currentLessonModule,
            lastActivityText: $lastActivityText,
            lastActivityAgo: $lastActivityAgo,
            completedProjects: $completedProjects,
            totalProjects: $totalProjects,
            nextTarget: 'Dominar la ingeniería de software profesional, Git y Clean Architecture',
            skills: $skills,
            seProgress: $seProgress,
            gitProgress: $gitProgress,
            phpProgress: $phpProgress,
            symfonyProgress: $symfonyProgress,
            testingProgress: $testingProgress,
            archProgress: $archProgress,
            devopsProgress: $devopsProgress,
            profProgress: $profProgress,
            currentProject: 'Proyecto 1: CRUD Enterprise con DTOs',
            currentChallenge: 'Reto: Invariantes y Value Objects',
            currentGitSkill: 'Trunk-based Workflow & Conflict Resolution',
            recommendedNextLessonSlug: $currentLessonSlug,
            recommendedNextLessonTitle: $currentLessonTitle
        );
    }

    /**
     * @param array<string, UserProgress> $progressMap
     * @return array<UnlockedSkill>
     */
    private function resolveSkillsMatrix(array $progressMap): array
    {
        $definitions = [
            [
                'name' => 'Software Engineering',
                'category' => 'Ingeniería',
                'icon' => 'layers',
                'trigger_slug' => 'se-sdlc-requirements',
                'description' => 'SDLC, elicitación de requerimientos funcionales/no funcionales y especificación con User Stories.',
            ],
            [
                'name' => 'Git & GitHub',
                'category' => 'Control de Versiones',
                'icon' => 'git',
                'trigger_slug' => 'git-fundamentals-plumbing',
                'description' => 'Árbol de trabajo, staging area, grafos de commits, rebase y resolución de conflictos.',
            ],
            [
                'name' => 'Clean Code',
                'category' => 'Calidad',
                'icon' => 'check-square',
                'trigger_slug' => 'se-clean-code-quality',
                'description' => 'Alta cohesión, bajo acoplamiento, nomenclatura semántica y eliminación de deuda técnica.',
            ],
            [
                'name' => 'SOLID Principles',
                'category' => 'Diseño',
                'icon' => 'code',
                'trigger_slug' => 'se-solid-principles',
                'description' => 'Dominio de SRP, OCP, LSP, ISP y DIP aplicados a casos reales de producción en PHP y Symfony.',
            ],
            [
                'name' => 'Testing & PHPUnit',
                'category' => 'Calidad',
                'icon' => 'phpunit',
                'trigger_slug' => 'testing-phpunit-mastery',
                'description' => 'Pirámide de pruebas, test doubles, fixtures, pruebas funcionales con WebTestCase y TDD.',
            ],
            [
                'name' => 'Software Architecture',
                'category' => 'Arquitectura',
                'icon' => 'cpu',
                'trigger_slug' => 'arch-patterns-comparison',
                'description' => 'Monolito modular, capas, arquitectura hexagonal, regla de dependencia y trade-offs.',
            ],
            [
                'name' => 'Domain-Driven Design (DDD)',
                'category' => 'Arquitectura',
                'icon' => 'book-open',
                'trigger_slug' => 'arch-pragmatic-ddd',
                'description' => 'Bounded Contexts, agregados, entidades ricas, objetos de valor y eventos de dominio.',
            ],
            [
                'name' => 'System Design',
                'category' => 'Sistemas',
                'icon' => 'sliders',
                'trigger_slug' => 'system-design-high-throughput',
                'description' => 'Escalabilidad horizontal, caching multicapa, colas asíncronas y mitigación de cuellos de botella.',
            ],
            [
                'name' => 'DevOps & Docker',
                'category' => 'Infraestructura',
                'icon' => 'docker',
                'trigger_slug' => 'devops-docker-fpm-nginx',
                'description' => 'Contenedores multi-stage para PHP-FPM/Nginx y automatización de pipelines con GitHub Actions.',
            ],
            [
                'name' => 'Code Review & Smells',
                'category' => 'Mentoría',
                'icon' => 'award',
                'trigger_slug' => 'eval-senior-code-review',
                'description' => 'Detección proactiva de antipatrones, cuellos de botella, problemas de seguridad y refactorizaciones.',
            ],
            [
                'name' => 'Production Engineering',
                'category' => 'Operación',
                'icon' => 'network',
                'trigger_slug' => 'prof-failure-engineering',
                'description' => 'Circuit breaker, retries idempotentes, postmortems sin culpa y gestión de incidentes críticos.',
            ],
            [
                'name' => 'Share-Nothing Architecture',
                'category' => 'Runtime',
                'icon' => 'php',
                'trigger_slug' => 'php-request-lifecycle',
                'description' => 'Aislamiento de memoria en PHP-FPM, ciclo RINIT/RSHUTDOWN y Garbage Collection.',
            ],
        ];

        $skills = [];
        foreach ($definitions as $def) {
            $prog = $progressMap[$def['trigger_slug']] ?? null;
            $isUnlocked = ($prog !== null && in_array($prog->getStatus(), [UserProgress::STATUS_COMPLETED, UserProgress::STATUS_MASTERED], true));
            
            $mastery = 'Pendiente';
            if ($prog !== null) {
                if ($prog->getStatus() === UserProgress::STATUS_MASTERED) {
                    $mastery = 'Professional';
                } elseif ($prog->getStatus() === UserProgress::STATUS_COMPLETED) {
                    $mastery = $prog->getScore() >= 90 ? 'Advanced' : 'Intermediate';
                } elseif ($prog->getStatus() === UserProgress::STATUS_IN_PROGRESS) {
                    $mastery = 'Beginner';
                }
            }

            $skills[] = new UnlockedSkill(
                name: $def['name'],
                category: $def['category'],
                icon: $def['icon'],
                isUnlocked: $isUnlocked,
                masteryLevel: $mastery,
                description: $def['description']
            );
        }

        return $skills;
    }

    private function formatRelativeTime(DateTimeImmutable $dateTime): string
    {
        $now = new DateTimeImmutable();
        $diffSeconds = $now->getTimestamp() - $dateTime->getTimestamp();

        if ($diffSeconds < 60) {
            return 'hace unos segundos';
        }

        $diffMinutes = (int) floor($diffSeconds / 60);
        if ($diffMinutes < 60) {
            return sprintf('hace %d minuto%s', $diffMinutes, $diffMinutes > 1 ? 's' : '');
        }

        $diffHours = (int) floor($diffMinutes / 60);
        if ($diffHours < 24) {
            return sprintf('hace %d hora%s', $diffHours, $diffHours > 1 ? 's' : '');
        }

        $diffDays = (int) floor($diffHours / 24);
        return sprintf('hace %d día%s', $diffDays, $diffDays > 1 ? 's' : '');
    }
}
