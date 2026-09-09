<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use App\Entity\UserProgress;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<UserProgress>
 */
class UserProgressRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, UserProgress::class);
    }

    public function findProgress(User $user, string $lessonSlug): ?UserProgress
    {
        return $this->findOneBy([
            'user' => $user,
            'lessonSlug' => $lessonSlug,
        ]);
    }

    /**
     * @return array<string, UserProgress> indexed by lessonSlug
     */
    public function getProgressMapForUser(User $user): array
    {
        $records = $this->findBy(['user' => $user]);
        $map = [];
        foreach ($records as $record) {
            $map[$record->getLessonSlug()] = $record;
        }

        return $map;
    }

    /**
     * @return array{total_completed: int, total_in_progress: int, total_score: int}
     */
    public function calculateUserStats(User $user): array
    {
        $qb = $this->createQueryBuilder('p')
            ->select('COUNT(p.id) as total')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);

        $completedQb = clone $qb;
        $completed = (int) $completedQb->andWhere('p.status IN (:completedStatus)')
            ->setParameter('completedStatus', [UserProgress::STATUS_COMPLETED, UserProgress::STATUS_MASTERED])
            ->getQuery()
            ->getSingleScalarResult();

        $inProgressQb = clone $qb;
        $inProgress = (int) $inProgressQb->andWhere('p.status = :inProgressStatus')
            ->setParameter('inProgressStatus', UserProgress::STATUS_IN_PROGRESS)
            ->getQuery()
            ->getSingleScalarResult();

        $scoreQb = $this->createQueryBuilder('p')
            ->select('COALESCE(SUM(p.score), 0) as totalScore')
            ->andWhere('p.user = :user')
            ->setParameter('user', $user);
        $totalScore = (int) $scoreQb->getQuery()->getSingleScalarResult();

        return [
            'total_completed' => $completed,
            'total_in_progress' => $inProgress,
            'total_score' => $totalScore,
        ];
    }
}
