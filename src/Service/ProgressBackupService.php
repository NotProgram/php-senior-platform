<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExerciseAttempt;
use App\Entity\QuizAttempt;
use App\Entity\ReviewCard;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Repository\ExerciseAttemptRepository;
use App\Repository\QuizAttemptRepository;
use App\Repository\ReviewCardRepository;
use App\Repository\UserProgressRepository;
use DateTimeImmutable;
use Doctrine\ORM\EntityManagerInterface;
use InvalidArgumentException;
use RuntimeException;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

class ProgressBackupService
{
    public const CURRENT_SCHEMA_VERSION = 1;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly UserProgressRepository $progressRepository,
        private readonly QuizAttemptRepository $quizAttemptRepository,
        private readonly ExerciseAttemptRepository $exerciseAttemptRepository,
        private readonly ReviewCardRepository $reviewCardRepository,
        private readonly RoadmapService $roadmapService,
        #[Autowire('%kernel.project_dir%')]
        private readonly string $projectDir,
    ) {}

    /**
     * Exports all user learning records, stats, quiz attempts, exercise attempts,
     * and spaced repetition cards into a portable associative array.
     *
     * @return array<string, mixed>
     */
    public function export(User $user): array
    {
        $progressRecords = $this->progressRepository->findBy(['user' => $user], ['id' => 'ASC']);
        $quizAttempts = $this->quizAttemptRepository->findBy(['user' => $user], ['attemptedAt' => 'ASC']);
        $exerciseAttempts = $this->exerciseAttemptRepository->findBy(['user' => $user], ['executedAt' => 'ASC']);
        $reviewCards = $this->reviewCardRepository->findBy(['user' => $user], ['id' => 'ASC']);

        return [
            'schema_version' => self::CURRENT_SCHEMA_VERSION,
            'app' => 'Senior DevLab',
            'exported_at' => (new DateTimeImmutable())->format(DateTimeImmutable::ATOM),
            'user' => [
                'email' => $user->getEmail(),
                'display_name' => $user->getDisplayName(),
                'current_level' => $user->getCurrentLevel(),
                'experience_points' => $user->getExperiencePoints(),
                'streak_days' => $user->getStreakDays(),
                'last_active_at' => $user->getLastActiveAt()->format(DateTimeImmutable::ATOM),
            ],
            'progress' => array_map(static fn (UserProgress $p): array => [
                'module_slug' => $p->getModuleSlug(),
                'lesson_slug' => $p->getLessonSlug(),
                'status' => $p->getStatus(),
                'score' => $p->getScore(),
                'notes' => $p->getNotes(),
                'completed_at' => $p->getCompletedAt()?->format(DateTimeImmutable::ATOM),
                'updated_at' => $p->getUpdatedAt()->format(DateTimeImmutable::ATOM),
            ], $progressRecords),
            'quiz_attempts' => array_map(static fn (QuizAttempt $q): array => [
                'lesson_slug' => $q->getLessonSlug(),
                'answers' => $q->getAnswers(),
                'score_percentage' => $q->getScorePercentage(),
                'is_passed' => $q->isPassed(),
                'attempted_at' => $q->getAttemptedAt()->format(DateTimeImmutable::ATOM),
            ], $quizAttempts),
            'exercise_attempts' => array_map(static fn (ExerciseAttempt $e): array => [
                'lesson_slug' => $e->getLessonSlug(),
                'submitted_code' => $e->getSubmittedCode(),
                'is_passed' => $e->isPassed(),
                'feedback' => $e->getFeedback(),
                'executed_at' => $e->getExecutedAt()->format(DateTimeImmutable::ATOM),
            ], $exerciseAttempts),
            'review_cards' => array_map(static fn (ReviewCard $c): array => [
                'card_id' => $c->getCardId(),
                'lesson_slug' => $c->getLessonSlug(),
                'kind' => $c->getKind(),
                'ease_factor' => $c->getEaseFactor(),
                'interval_days' => $c->getIntervalDays(),
                'repetitions' => $c->getRepetitions(),
                'lapses' => $c->getLapses(),
                'due_at' => $c->getDueAt()->format(DateTimeImmutable::ATOM),
                'last_reviewed_at' => $c->getLastReviewedAt()?->format(DateTimeImmutable::ATOM),
            ], $reviewCards),
        ];
    }

    /**
     * Imports progress from an array into the database inside a single transaction.
     * Overwrites previous progress for the given user to ensure consistency.
     *
     * @param array<string, mixed> $data
     * @return array<string, int> summary statistics of restored items
     */
    public function import(User $user, array $data): array
    {
        $this->validateBackupData($data);

        return $this->entityManager->wrapInTransaction(function (EntityManagerInterface $em) use ($user, $data): array {
            // Remove previous user records
            foreach ([UserProgress::class, QuizAttempt::class, ExerciseAttempt::class, ReviewCard::class] as $entityClass) {
                $em->createQueryBuilder()
                    ->delete($entityClass, 'e')
                    ->where('e.user = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->execute();
            }

            // Restore user metadata and points
            if (!empty($data['user']['display_name'])) {
                $user->setDisplayName(trim((string) $data['user']['display_name']));
            }
            if (isset($data['user']['experience_points'])) {
                $user->setExperiencePoints((int) $data['user']['experience_points']);
            }
            if (isset($data['user']['current_level'])) {
                $user->setCurrentLevel((string) $data['user']['current_level']);
            }
            if (isset($data['user']['streak_days'])) {
                $user->setStreakDays((int) $data['user']['streak_days']);
            }
            if (!empty($data['user']['last_active_at'])) {
                try {
                    $user->setLastActiveAt(new DateTimeImmutable((string) $data['user']['last_active_at']));
                } catch (\Exception) {
                    $user->touchLastActive();
                }
            }

            // Restore UserProgress
            $restoredProgress = 0;
            foreach ($data['progress'] ?? [] as $item) {
                if (!isset($item['lesson_slug'])) {
                    continue;
                }
                $moduleSlug = $item['module_slug'] ?? $this->roadmapService->getModuleSlugForLesson($item['lesson_slug']);
                $progress = new UserProgress(
                    $user,
                    $moduleSlug,
                    $item['lesson_slug'],
                    $item['status'] ?? UserProgress::STATUS_AVAILABLE
                );
                if (isset($item['score'])) {
                    $progress->setScore((int) $item['score']);
                }
                if (isset($item['notes'])) {
                    $progress->setNotes($item['notes']);
                }
                if (!empty($item['completed_at'])) {
                    try {
                        $progress->setCompletedAt(new DateTimeImmutable((string) $item['completed_at']));
                    } catch (\Exception) {}
                }
                if (!empty($item['updated_at'])) {
                    try {
                        $progress->setUpdatedAt(new DateTimeImmutable((string) $item['updated_at']));
                    } catch (\Exception) {}
                }
                $em->persist($progress);
                $restoredProgress++;
            }

            // Restore QuizAttempts
            $restoredQuizzes = 0;
            foreach ($data['quiz_attempts'] ?? [] as $item) {
                if (!isset($item['lesson_slug'])) {
                    continue;
                }
                $quiz = new QuizAttempt(
                    $user,
                    $item['lesson_slug'],
                    is_array($item['answers'] ?? null) ? $item['answers'] : [],
                    (int) ($item['score_percentage'] ?? 0),
                    (bool) ($item['is_passed'] ?? false)
                );
                if (!empty($item['attempted_at'])) {
                    try {
                        $quiz->setAttemptedAt(new DateTimeImmutable((string) $item['attempted_at']));
                    } catch (\Exception) {}
                }
                $em->persist($quiz);
                $restoredQuizzes++;
            }

            // Restore ExerciseAttempts
            $restoredExercises = 0;
            foreach ($data['exercise_attempts'] ?? [] as $item) {
                if (!isset($item['lesson_slug'])) {
                    continue;
                }
                $exercise = new ExerciseAttempt(
                    $user,
                    $item['lesson_slug'],
                    (string) ($item['submitted_code'] ?? ''),
                    (bool) ($item['is_passed'] ?? false),
                    isset($item['feedback']) ? (string) $item['feedback'] : null
                );
                if (!empty($item['executed_at'])) {
                    try {
                        $exercise->setExecutedAt(new DateTimeImmutable((string) $item['executed_at']));
                    } catch (\Exception) {}
                }
                $em->persist($exercise);
                $restoredExercises++;
            }

            // Restore ReviewCards
            $restoredCards = 0;
            foreach ($data['review_cards'] ?? [] as $item) {
                if (!isset($item['card_id'], $item['lesson_slug'])) {
                    continue;
                }
                $card = new ReviewCard(
                    $user,
                    $item['card_id'],
                    $item['lesson_slug'],
                    $item['kind'] ?? 'concept'
                );
                $dueAt = new DateTimeImmutable();
                if (!empty($item['due_at'])) {
                    try {
                        $dueAt = new DateTimeImmutable((string) $item['due_at']);
                    } catch (\Exception) {}
                }
                $lastReviewedAt = null;
                if (!empty($item['last_reviewed_at'])) {
                    try {
                        $lastReviewedAt = new DateTimeImmutable((string) $item['last_reviewed_at']);
                    } catch (\Exception) {}
                }

                $card->restoreState(
                    (float) ($item['ease_factor'] ?? ReviewCard::EASE_DEFAULT),
                    (int) ($item['interval_days'] ?? 0),
                    (int) ($item['repetitions'] ?? 0),
                    (int) ($item['lapses'] ?? 0),
                    $dueAt,
                    $lastReviewedAt
                );
                $em->persist($card);
                $restoredCards++;
            }

            $em->flush();

            return [
                'lessons' => $restoredProgress,
                'quiz_attempts' => $restoredQuizzes,
                'exercise_attempts' => $restoredExercises,
                'review_cards' => $restoredCards,
                'xp' => $user->getExperiencePoints(),
            ];
        });
    }

    public function autoSave(User $user): ?string
    {
        $dir = $this->projectDir . '/var';
        if (!is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $path = $dir . '/progress_backup.json';
        $data = $this->export($user);
        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        file_put_contents($path, $json);

        return $path;
    }

    public function hasAutoBackup(): bool
    {
        return file_exists($this->getAutoBackupPath());
    }

    public function getAutoBackupPath(): string
    {
        return $this->projectDir . '/var/progress_backup.json';
    }

    /**
     * @return array{path: string, updated_at: string, lessons_count: int, xp: int, size_bytes: int}|null
     */
    public function getAutoBackupInfo(): ?array
    {
        $path = $this->getAutoBackupPath();
        if (!file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        if ($content === false) {
            return null;
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            return null;
        }

        return [
            'path' => $path,
            'updated_at' => $data['exported_at'] ?? date('c', (int) filemtime($path)),
            'lessons_count' => is_array($data['progress'] ?? null) ? count($data['progress']) : 0,
            'xp' => (int) ($data['user']['experience_points'] ?? 0),
            'size_bytes' => (int) filesize($path),
        ];
    }

    /**
     * @return array<string, int>
     */
    public function importFromAutoBackup(User $user): array
    {
        $path = $this->getAutoBackupPath();
        if (!file_exists($path)) {
            throw new RuntimeException('No se encontró archivo de respaldo local automático.');
        }

        $content = file_get_contents($path);
        if ($content === false) {
            throw new RuntimeException('No se pudo leer el archivo de respaldo.');
        }

        $data = json_decode($content, true);
        if (!is_array($data)) {
            throw new InvalidArgumentException('El archivo de respaldo no contiene JSON válido.');
        }

        return $this->import($user, $data);
    }

    /**
     * @param array<string, mixed> $data
     */
    private function validateBackupData(array $data): void
    {
        if (!isset($data['user']) || !is_array($data['user'])) {
            throw new InvalidArgumentException('El archivo JSON no contiene una sección de usuario válida.');
        }

        if (!isset($data['progress']) || !is_array($data['progress'])) {
            throw new InvalidArgumentException('El archivo JSON no contiene registros de progreso de lecciones.');
        }
    }
}
