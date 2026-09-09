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
        public array $skills
    ) {}
}
