<?php

declare(strict_types=1);

namespace App\DTO;

use App\Entity\User;

readonly class DashboardMetrics
{
    /**
     * @param array<UnlockedSkill> $skills
     */
    public function __construct(
        public User $user,
        public int $totalLessons,
        public int $completedLessons,
        public int $inProgressLessons,
        public int $completionPercentage,
        public int $totalModules,
        public int $completedModules,
        public int $hoursEstimated,
        public int $streakDays,
        public string $currentLevel,
        public string $currentLessonSlug,
        public string $currentLessonTitle,
        public string $currentLessonModule,
        public string $lastActivityText,
        public string $lastActivityAgo,
        public int $completedProjects,
        public int $totalProjects,
        public string $nextTarget,
        public array $skills,
        public int $seProgress = 0,
        public int $gitProgress = 0,
        public int $phpProgress = 0,
        public int $symfonyProgress = 0,
        public int $testingProgress = 0,
        public int $archProgress = 0,
        public int $devopsProgress = 0,
        public int $profProgress = 0,
        public string $currentProject = 'Proyecto 1: CRUD Enterprise con DTOs',
        public string $currentChallenge = 'Reto: Invariantes y Value Objects',
        public string $currentGitSkill = 'Trunk-based Workflow & Conflict Resolution',
        public string $recommendedNextLessonSlug = 'php-request-lifecycle',
        public string $recommendedNextLessonTitle = 'Request Lifecycle & Web Servers'
    ) {}
}
