<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\SystemDesignState;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<SystemDesignState>
 */
class SystemDesignStateRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, SystemDesignState::class);
    }

    public function findOrCreateForScenario(User $user, string $scenarioSlug): SystemDesignState
    {
        $state = $this->findOneBy(['user' => $user, 'scenarioSlug' => $scenarioSlug]);
        if ($state === null) {
            $state = new SystemDesignState($user, $scenarioSlug);
            $this->getEntityManager()->persist($state);
            $this->getEntityManager()->flush();
        }

        return $state;
    }
}
