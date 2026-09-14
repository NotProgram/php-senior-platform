<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Enum\ProgressStatus;

/**
 * Turns the static path catalog into per-learner progress: percentage, current
 * phase, remaining time and the single next action for each route.
 */
class LearningPathService
{
    /**
     * Lesson metadata indexed by slug, built once per request.
     *
     * @var array<string, array{slug: string, title: string, minutes: int, difficulty: string, module: string, module_slug: string}>|null
     */
    private ?array $lessonIndex = null;

    public function __construct(
        private readonly LearningPathCatalog $catalog,
        private readonly RoadmapService $roadmapService,
        private readonly PrerequisiteEngine $prerequisiteEngine
    ) {}

    /**
     * @return array<string, array{slug: string, title: string, minutes: int, difficulty: string, module: string, module_slug: string}>
     */
    private function lessonIndex(): array
    {
        if ($this->lessonIndex !== null) {
            return $this->lessonIndex;
        }

        $index = [];
        foreach ($this->roadmapService->getSections() as $moduleSlug => $module) {
            if (!isset($module['lessons'])) {
                continue;
            }
            foreach ($module['lessons'] as $lesson) {
                $index[$lesson['slug']] = [
                    'slug' => $lesson['slug'],
                    'title' => $lesson['title'],
                    'minutes' => (int) ($lesson['minutes'] ?? 45),
                    'difficulty' => (string) ($lesson['difficulty'] ?? 'Intermedio'),
                    'module' => (string) ($module['title'] ?? $moduleSlug),
                    'module_slug' => (string) $moduleSlug,
                ];
            }
        }

        return $this->lessonIndex = $index;
    }

    /**
     * @return list<string>
     */
    public function pathSlugs(): array
    {
        return array_keys($this->catalog->all());
    }

    /**
     * Compact progress summary for every path, ordered by momentum:
     * started-but-unfinished first, then untouched, then finished.
     *
     * @return list<array<string, mixed>>
     */
    public function overview(User $user): array
    {
        $summaries = [];

        foreach ($this->catalog->all() as $slug => $definition) {
            $summaries[] = $this->summarize($user, $slug, $definition);
        }

        usort($summaries, static function (array $a, array $b): int {
            $rank = static function (array $path): int {
                if ($path['percent'] >= 100) {
                    return 2;
                }

                return $path['completed'] > 0 ? 0 : 1;
            };

            return [$rank($a), -$a['percent']] <=> [$rank($b), -$b['percent']];
        });

        return $summaries;
    }

    /**
     * @param array<string, mixed> $definition
     * @return array<string, mixed>
     */
    private function summarize(User $user, string $slug, array $definition): array
    {
        $index = $this->lessonIndex();
        $total = 0;
        $completed = 0;
        $minutesTotal = 0;
        $minutesLeft = 0;
        $labs = 0;
        $next = null;
        $currentPhase = null;
        $phaseNumber = 0;

        foreach ($definition['phases'] as $position => $phase) {
            $labs += count($phase['labs'] ?? []);
            $phaseDone = true;

            foreach ($phase['lessons'] as $lessonSlug) {
                $meta = $index[$lessonSlug] ?? null;
                if ($meta === null) {
                    continue;
                }

                $total++;
                $minutesTotal += $meta['minutes'];
                $status = $this->prerequisiteEngine->resolveLessonStatus($user, $lessonSlug);

                if (in_array($status, [ProgressStatus::Completed, ProgressStatus::Mastered], true)) {
                    $completed++;
                    continue;
                }

                $phaseDone = false;
                $minutesLeft += $meta['minutes'];

                if ($next === null && $status !== ProgressStatus::Locked) {
                    $next = $meta + ['status' => $status];
                }
            }

            if (!$phaseDone && $currentPhase === null) {
                $currentPhase = $phase['title'];
                $phaseNumber = $position + 1;
            }
        }

        $percent = $total > 0 ? (int) round(($completed / $total) * 100) : 0;

        return [
            'slug' => $slug,
            'title' => $definition['title'],
            'subtitle' => $definition['subtitle'],
            'icon' => $definition['icon'],
            'level' => $definition['level'],
            'accent' => $definition['accent'],
            'glow' => $definition['glow'],
            'goal' => $definition['goal'],
            'audience' => $definition['audience'],
            'outcome' => $definition['outcome'],
            'phase_count' => count($definition['phases']),
            'lab_count' => $labs,
            'total' => $total,
            'completed' => $completed,
            'percent' => $percent,
            'hours_total' => (int) ceil($minutesTotal / 60),
            'hours_left' => (int) ceil($minutesLeft / 60),
            'current_phase' => $currentPhase,
            'current_phase_number' => $phaseNumber,
            'next' => $next,
        ];
    }

    /**
     * Full path view: phases decorated with lesson status and per-phase progress.
     *
     * @return array<string, mixed>|null
     */
    public function detail(User $user, string $slug): ?array
    {
        $definitions = $this->catalog->all();
        if (!isset($definitions[$slug])) {
            return null;
        }

        $definition = $definitions[$slug];
        $index = $this->lessonIndex();
        $summary = $this->summarize($user, $slug, $definition);
        $phases = [];
        $activeAssigned = false;

        foreach ($definition['phases'] as $position => $phase) {
            $lessons = [];
            $done = 0;

            foreach ($phase['lessons'] as $lessonSlug) {
                $meta = $index[$lessonSlug] ?? null;
                if ($meta === null) {
                    continue;
                }

                $status = $this->prerequisiteEngine->resolveLessonStatus($user, $lessonSlug);
                $isDone = in_array($status, [ProgressStatus::Completed, ProgressStatus::Mastered], true);
                if ($isDone) {
                    $done++;
                }

                $lessons[] = $meta + [
                    'status' => $status,
                    'is_done' => $isDone,
                    'missing' => $status === ProgressStatus::Locked
                        ? $this->prerequisiteEngine->missingPrerequisites($user, $lessonSlug)
                        : [],
                ];
            }

            $count = count($lessons);
            $isComplete = $count > 0 && $done === $count;
            $isActive = !$isComplete && !$activeAssigned;
            if ($isActive) {
                $activeAssigned = true;
            }

            $phases[] = [
                'number' => $position + 1,
                'title' => $phase['title'],
                'goal' => $phase['goal'],
                'checkpoint' => $phase['checkpoint'] ?? null,
                'labs' => $phase['labs'] ?? [],
                'lessons' => $lessons,
                'done' => $done,
                'count' => $count,
                'percent' => $count > 0 ? (int) round(($done / $count) * 100) : 0,
                'is_complete' => $isComplete,
                'is_active' => $isActive,
                'minutes' => array_sum(array_map(static fn(array $l): int => $l['minutes'], $lessons)),
            ];
        }

        return $summary + [
            'phases' => $phases,
            'exit_criteria' => $definition['exit_criteria'],
        ];
    }

    /**
     * The path the learner should focus on now: the most advanced unfinished one.
     *
     * @return array<string, mixed>|null
     */
    public function focusPath(User $user): ?array
    {
        foreach ($this->overview($user) as $path) {
            if ($path['percent'] < 100 && $path['next'] !== null) {
                return $path;
            }
        }

        return null;
    }

    /**
     * Paths that include a given lesson, so a lesson page can show its context.
     *
     * @return list<array{slug: string, title: string, phase: string, accent: string}>
     */
    public function pathsContaining(string $lessonSlug): array
    {
        $found = [];

        foreach ($this->catalog->all() as $slug => $definition) {
            foreach ($definition['phases'] as $phase) {
                if (in_array($lessonSlug, $phase['lessons'], true)) {
                    $found[] = [
                        'slug' => $slug,
                        'title' => $definition['title'],
                        'phase' => $phase['title'],
                        'accent' => $definition['accent'],
                    ];
                    break;
                }
            }
        }

        return $found;
    }
}
