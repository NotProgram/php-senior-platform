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

        foreach ($sections as $mKey => $module) {
            if (!isset($module['lessons'])) {
                continue;
            }

            $totalModules++;
            $moduleLessonsCount = count($module['lessons']);
            $moduleCompletedCount = 0;

            $isProjectModule = ($mKey === 'projects');

            foreach ($module['lessons'] as $lesson) {
                $totalLessons++;
                if ($isProjectModule) {
                    $totalProjects++;
                }

                $prog = $progressMap[$lesson['slug']] ?? null;

                if ($prog !== null) {
                    if ($prog->getStatus() === UserProgress::STATUS_COMPLETED || $prog->getStatus() === UserProgress::STATUS_MASTERED) {
                        $completedLessons++;
                        $moduleCompletedCount++;
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
            nextTarget: 'Dominar la arquitectura de memoria del Zend Engine y OpCache en PHP 8.4',
            skills: $skills
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
                'name' => 'Share-Nothing Architecture',
                'category' => 'Runtime',
                'icon' => 'php',
                'trigger_slug' => 'php-request-lifecycle',
                'description' => 'Comprensión profunda del aislamiento de peticiones en PHP-FPM y liberación de memoria.',
            ],
            [
                'name' => 'Zend Memory & Garbage Collection',
                'category' => 'Runtime',
                'icon' => 'cpu',
                'trigger_slug' => 'php-types-memory',
                'description' => 'Manejo de referencias cíclicas, asignación emalloc y buffers en memoria.',
            ],
            [
                'name' => 'HttpKernel Flow & Resolvers',
                'category' => 'Framework',
                'icon' => 'symfony',
                'trigger_slug' => 'symfony-http-kernel-lifecycle',
                'description' => 'Dominio del flujo Request -> Controller -> Response sin asunciones mágicas.',
            ],
            [
                'name' => 'Compiled DI & Compiler Passes',
                'category' => 'Framework',
                'icon' => 'layers',
                'trigger_slug' => 'symfony-service-container',
                'description' => 'Manipulación del AST del contenedor antes de la compilación estática a disco.',
            ],
            [
                'name' => 'Unit of Work & Identity Map',
                'category' => 'Persistencia',
                'icon' => 'database',
                'trigger_slug' => 'doctrine-unit-of-work',
                'description' => 'Diferenciación entre persist() en memoria y flush() transaccional a la base de datos.',
            ],
            [
                'name' => 'N+1 Detection & Query Tuning',
                'category' => 'Persistencia',
                'icon' => 'mysql',
                'trigger_slug' => 'doctrine-n-plus-one-optimization',
                'description' => 'Optimización de consultas relacionales y análisis de planes con EXPLAIN ANALYZE.',
            ],
            [
                'name' => 'Hexagonal & Ports and Adapters',
                'category' => 'Arquitectura',
                'icon' => 'sliders',
                'trigger_slug' => 'arch-hexagonal-clean',
                'description' => 'Aislamiento del dominio de negocio frente a frameworks y librerías externas.',
            ],
            [
                'name' => 'High-Throughput Caching & Queues',
                'category' => 'System Design',
                'icon' => 'zap',
                'trigger_slug' => 'system-design-high-throughput',
                'description' => 'Desacoplamiento asíncrono con Symfony Messenger y Redis para 10k req/sec.',
            ],
        ];

        $skills = [];
        foreach ($definitions as $def) {
            $prog = $progressMap[$def['trigger_slug']] ?? null;
            $isUnlocked = ($prog !== null && in_array($prog->getStatus(), [UserProgress::STATUS_COMPLETED, UserProgress::STATUS_MASTERED], true));
            $mastery = $isUnlocked ? 'Dominado' : 'Pendiente';

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
