<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Access control and progress resolution on top of the curriculum DAG.
 *
 * Status is always derived from the graph plus the stored progress records;
 * a single query loads every record for the user, so resolving the whole
 * curriculum costs one round trip instead of one per lesson.
 */
class PrerequisiteEngine
{
    /**
     * @var array<string, UserProgress>|null
     */
    private ?array $progressCache = null;

    private ?int $progressCacheUserId = null;

    public function __construct(
        private readonly UserProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly CurriculumGraph $graph,
    ) {}

    /**
     * @return array<string, UserProgress>
     */
    private function progressMap(User $user): array
    {
        if ($this->progressCache === null || $this->progressCacheUserId !== $user->getId()) {
            $this->progressCache = $this->progressRepository->getProgressMapForUser($user);
            $this->progressCacheUserId = $user->getId();
        }

        return $this->progressCache;
    }

    private function isDone(?UserProgress $progress): bool
    {
        return $progress !== null && in_array(
            $progress->getStatus(),
            [UserProgress::STATUS_COMPLETED, UserProgress::STATUS_MASTERED],
            true
        );
    }

    public function resolveLessonStatus(User $user, string $lessonSlug): ProgressStatus
    {
        return $this->statusFromMap($this->progressMap($user), $lessonSlug);
    }

    /**
     * @param array<string, UserProgress> $progressMap
     */
    private function statusFromMap(array $progressMap, string $lessonSlug): ProgressStatus
    {
        $existing = $progressMap[$lessonSlug] ?? null;
        if ($existing !== null) {
            return ProgressStatus::tryFrom($existing->getStatus()) ?? ProgressStatus::Available;
        }

        if ($this->graph->isEntryPoint($lessonSlug)) {
            return ProgressStatus::Available;
        }

        if (!$this->graph->isKnown($lessonSlug)) {
            return ProgressStatus::Locked;
        }

        foreach ($this->graph->prerequisitesFor($lessonSlug) as $prereqSlug) {
            if (!$this->isDone($progressMap[$prereqSlug] ?? null)) {
                return ProgressStatus::Locked;
            }
        }

        return ProgressStatus::Available;
    }

    /**
     * Prerequisites the learner still has to finish before this lesson opens.
     *
     * @return list<string>
     */
    public function missingPrerequisites(User $user, string $lessonSlug): array
    {
        $progressMap = $this->progressMap($user);
        $missing = [];

        foreach ($this->graph->prerequisitesFor($lessonSlug) as $prereqSlug) {
            if (!$this->isDone($progressMap[$prereqSlug] ?? null)) {
                $missing[] = $prereqSlug;
            }
        }

        return $missing;
    }

    /**
     * Lessons that this lesson unlocks, so the learner sees what the effort buys.
     *
     * @return list<string>
     */
    public function unlockedBy(string $lessonSlug): array
    {
        return $this->graph->dependentsOf($lessonSlug);
    }

    /**
     * @return array{allowed: bool, status: ProgressStatus, missingPrerequisite: ?string}
     */
    public function canAccessLesson(User $user, string $lessonSlug): array
    {
        $status = $this->resolveLessonStatus($user, $lessonSlug);

        if ($status === ProgressStatus::Locked) {
            $missing = $this->missingPrerequisites($user, $lessonSlug);

            return [
                'allowed' => false,
                'status' => ProgressStatus::Locked,
                'missingPrerequisite' => $missing[0] ?? null,
            ];
        }

        // Visiting an available lesson starts it.
        $record = $this->progressRepository->findProgress($user, $lessonSlug);
        if ($record === null) {
            $record = new UserProgress(
                $user,
                $this->graph->moduleOf($lessonSlug),
                $lessonSlug,
                ProgressStatus::InProgress->value
            );
            $this->entityManager->persist($record);
            $this->entityManager->flush();
            $this->progressCache = null;
        } elseif ($record->getStatus() === ProgressStatus::Available->value) {
            $record->setStatus(ProgressStatus::InProgress->value);
            $this->entityManager->flush();
            $this->progressCache = null;
        }

        return [
            'allowed' => true,
            'status' => ProgressStatus::tryFrom($record->getStatus()) ?? ProgressStatus::InProgress,
            'missingPrerequisite' => null,
        ];
    }

    /**
     * @param array<string, array<string, mixed>> $sections
     * @return array<string, ProgressStatus> indexed by lessonSlug
     */
    public function resolveFullStatusMap(User $user, array $sections): array
    {
        $progressMap = $this->progressMap($user);
        $statusMap = [];

        foreach ($sections as $section) {
            if (!isset($section['lessons'])) {
                continue;
            }
            foreach ($section['lessons'] as $lesson) {
                $statusMap[$lesson['slug']] = $this->statusFromMap($progressMap, $lesson['slug']);
            }
        }

        return $statusMap;
    }

    /**
     * Ranked list of lessons the learner can start right now.
     *
     * Ordering favours (1) lessons already in progress, (2) lessons that unlock
     * the most downstream work, (3) shorter lessons, so momentum is rewarded.
     *
     * @param array<string, array<string, mixed>> $sections
     * @return list<array{slug: string, title: string, module: string, module_slug: string, minutes: int, difficulty: string, status: ProgressStatus, unlocks: int, reason: string}>
     */
    public function recommendNextLessons(User $user, array $sections, int $limit = 5): array
    {
        $progressMap = $this->progressMap($user);
        $candidates = [];

        foreach ($sections as $moduleSlug => $module) {
            if (!isset($module['lessons'])) {
                continue;
            }

            foreach ($module['lessons'] as $lesson) {
                $slug = $lesson['slug'];
                $status = $this->statusFromMap($progressMap, $slug);

                if (!in_array($status, [ProgressStatus::Available, ProgressStatus::InProgress], true)) {
                    continue;
                }

                $unlocks = count($this->graph->dependentsOf($slug));
                $minutes = (int) ($lesson['minutes'] ?? 45);

                $score = $unlocks * 10 - (int) ($minutes / 15);
                if ($status === ProgressStatus::InProgress) {
                    $score += 40;
                }
                if ($this->graph->isEntryPoint($slug)) {
                    $score += 6;
                }

                $candidates[] = [
                    'slug' => $slug,
                    'title' => $lesson['title'],
                    'module' => $module['title'] ?? $moduleSlug,
                    'module_slug' => $moduleSlug,
                    'minutes' => $minutes,
                    'difficulty' => $lesson['difficulty'] ?? 'Intermedio',
                    'status' => $status,
                    'unlocks' => $unlocks,
                    'score' => $score,
                    'reason' => $this->buildReason($status, $unlocks, $minutes),
                ];
            }
        }

        usort($candidates, static fn(array $a, array $b): int => $b['score'] <=> $a['score']);

        return array_map(
            static function (array $candidate): array {
                unset($candidate['score']);

                return $candidate;
            },
            array_slice($candidates, 0, $limit)
        );
    }

    private function buildReason(ProgressStatus $status, int $unlocks, int $minutes): string
    {
        if ($status === ProgressStatus::InProgress) {
            return sprintf(
                'Ya la empezaste: cerrarla libera %d lección(es) y evita el costo de recontextualizar.',
                $unlocks
            );
        }

        if ($unlocks >= 3) {
            return sprintf(
                'Es un cuello de botella del grafo: desbloquea %d lecciones en ~%d min de estudio.',
                $unlocks,
                $minutes
            );
        }

        if ($unlocks === 0) {
            return 'Cierra una rama del currículo; ideal como sesión corta de consolidación.';
        }

        return sprintf('Abre %d lección(es) nueva(s) y encaja en un bloque de %d min.', $unlocks, $minutes);
    }
}
