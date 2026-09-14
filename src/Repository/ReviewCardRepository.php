<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ReviewCard;
use App\Entity\User;
use DateTimeImmutable;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ReviewCard>
 */
class ReviewCardRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ReviewCard::class);
    }

    /**
     * @return array<string, ReviewCard> indexed by cardId
     */
    public function getStateMap(User $user): array
    {
        $map = [];
        foreach ($this->findBy(['user' => $user]) as $card) {
            $map[$card->getCardId()] = $card;
        }

        return $map;
    }

    public function findForUser(User $user, string $cardId): ?ReviewCard
    {
        return $this->findOneBy(['user' => $user, 'cardId' => $cardId]);
    }

    public function countDue(User $user, DateTimeImmutable $moment): int
    {
        return (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :user')
            ->andWhere('c.dueAt <= :moment')
            ->setParameter('user', $user)
            ->setParameter('moment', $moment)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array{tracked: int, due: int, mature: int, lapses: int}
     */
    public function statsFor(User $user, DateTimeImmutable $moment): array
    {
        $rows = $this->createQueryBuilder('c')
            ->select('COUNT(c.id) AS tracked', 'COALESCE(SUM(c.lapses), 0) AS lapses')
            ->andWhere('c.user = :user')
            ->setParameter('user', $user)
            ->getQuery()
            ->getSingleResult();

        $mature = (int) $this->createQueryBuilder('c')
            ->select('COUNT(c.id)')
            ->andWhere('c.user = :user')
            ->andWhere('c.intervalDays >= :threshold')
            ->setParameter('user', $user)
            ->setParameter('threshold', 21)
            ->getQuery()
            ->getSingleScalarResult();

        return [
            'tracked' => (int) $rows['tracked'],
            'lapses' => (int) $rows['lapses'],
            'mature' => $mature,
            'due' => $this->countDue($user, $moment),
        ];
    }

    /**
     * Upcoming review load per day, for the forecast chart.
     *
     * @return array<string, int> yyyy-mm-dd => cards due
     */
    public function forecast(User $user, DateTimeImmutable $from, int $days): array
    {
        $until = $from->modify(sprintf('+%d days', $days));

        $cards = $this->createQueryBuilder('c')
            ->andWhere('c.user = :user')
            ->andWhere('c.dueAt < :until')
            ->setParameter('user', $user)
            ->setParameter('until', $until)
            ->getQuery()
            ->getResult();

        $buckets = [];
        for ($offset = 0; $offset < $days; $offset++) {
            $buckets[$from->modify(sprintf('+%d days', $offset))->format('Y-m-d')] = 0;
        }

        foreach ($cards as $card) {
            $key = max($card->getDueAt(), $from)->format('Y-m-d');
            if (isset($buckets[$key])) {
                $buckets[$key]++;
            }
        }

        return $buckets;
    }
}
