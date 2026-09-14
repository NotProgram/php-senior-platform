<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExerciseAttempt;
use App\Entity\QuizAttempt;
use App\Entity\ReviewCard;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

/**
 * Single place where lesson progress moves forward and XP is granted, so manual completion,
 * quizzes and code challenges all follow the same rules: never downgrade, XP only when progress advances.
 */
class LearningProgressService
{
    public const XP_PER_ADVANCE = 50;

    public function __construct(
        private readonly UserProgressRepository $progressRepository,
        private readonly RoadmapService $roadmapService,
        private readonly EntityManagerInterface $entityManager,
    ) {}

    public function findOrCreateProgress(User $user, string $lessonSlug): UserProgress
    {
        $progress = $this->progressRepository->findProgress($user, $lessonSlug);
        if ($progress === null) {
            $progress = new UserProgress($user, $this->roadmapService->getModuleSlugForLesson($lessonSlug), $lessonSlug);
            $this->entityManager->persist($progress);
        }

        return $progress;
    }

    /**
     * Advances the lesson and grants XP only if its status actually moved forward. Does not flush.
     *
     * @return int XP granted (0 when the lesson was already at or beyond $target)
     */
    public function advance(User $user, UserProgress $progress, ProgressStatus $target): int
    {
        if (!$progress->advanceTo($target)) {
            return 0;
        }

        $user->addExperiencePoints(self::XP_PER_ADVANCE)->touchLastActive();

        return self::XP_PER_ADVANCE;
    }

    /**
     * @return int XP granted
     */
    public function completeLesson(User $user, string $lessonSlug): int
    {
        $progress = $this->findOrCreateProgress($user, $lessonSlug);
        $xpAwarded = $this->advance($user, $progress, ProgressStatus::Completed);
        if ($xpAwarded > 0) {
            $progress->setScore(100);
        }

        $this->entityManager->flush();

        return $xpAwarded;
    }

    public function resetProgress(User $user): void
    {
        $this->entityManager->wrapInTransaction(static function (EntityManagerInterface $em) use ($user): void {
            foreach ([UserProgress::class, QuizAttempt::class, ExerciseAttempt::class, ReviewCard::class] as $entityClass) {
                $em->createQueryBuilder()
                    ->delete($entityClass, 'e')
                    ->where('e.user = :user')
                    ->setParameter('user', $user)
                    ->getQuery()
                    ->execute();
            }

            $user->resetProgress();
        });
    }
}
