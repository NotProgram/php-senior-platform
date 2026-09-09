<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\ExerciseAttempt;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<ExerciseAttempt>
 */
class ExerciseAttemptRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ExerciseAttempt::class);
    }
}
