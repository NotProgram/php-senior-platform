<?php

declare(strict_types=1);

namespace App\Twig;

use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use App\Service\LearningPathService;
use App\Service\RoadmapService;
use App\Service\SpacedRepetitionService;
use Twig\Attribute\AsTwigFunction;

/**
 * Shell-wide data for the IDE chrome (status bar, review badge, path switcher).
 *
 * These values are needed on every page; exposing them as Twig functions keeps
 * every controller from having to pass the same three variables.
 */
final class DevLabExtension
{
    /**
     * @var array<string, mixed>|null
     */
    private ?array $statusCache = null;

    /**
     * @var list<array<string, mixed>>|null
     */
    private ?array $pathsCache = null;

    public function __construct(
        private readonly UserRepository $userRepository,
        private readonly UserProgressRepository $progressRepository,
        private readonly RoadmapService $roadmapService,
        private readonly SpacedRepetitionService $spacedRepetition,
        private readonly LearningPathService $learningPathService
    ) {}

    /**
     * @return array{name: string, level: string, xp: int, streak: int, completed: int, total: int, percent: int, due: int}
     */
    #[AsTwigFunction('devlab_status')]
    public function status(): array
    {
        if ($this->statusCache !== null) {
            return $this->statusCache;
        }

        $user = $this->userRepository->findOrCreateDefaultUser();
        $stats = $this->progressRepository->calculateUserStats($user);
        $totalLessons = $this->roadmapService->getTotalLessonsCount();
        $completed = $stats['total_completed'];

        return $this->statusCache = [
            'name' => $user->getDisplayName(),
            'level' => $user->getCurrentLevel(),
            'xp' => $user->getExperiencePoints(),
            'streak' => $user->getStreakDays(),
            'completed' => $completed,
            'total' => $totalLessons,
            'percent' => $totalLessons > 0 ? (int) round(($completed / $totalLessons) * 100) : 0,
            'due' => $this->spacedRepetition->countDue($user),
        ];
    }

    /**
     * Learning paths with progress, for the titlebar switcher and the sidebar.
     *
     * @return list<array<string, mixed>>
     */
    #[AsTwigFunction('devlab_paths')]
    public function paths(): array
    {
        if ($this->pathsCache !== null) {
            return $this->pathsCache;
        }

        $user = $this->userRepository->findOrCreateDefaultUser();

        return $this->pathsCache = $this->learningPathService->overview($user);
    }
}
