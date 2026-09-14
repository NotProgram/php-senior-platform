<?php

declare(strict_types=1);

namespace App\Repository;

use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<User>
 */
class UserRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function findOrCreateDefaultUser(): User
    {
        $user = $this->findOneBy(['email' => 'developer@senior-platform.local']);

        if ($user === null) {
            $user = new User('developer@senior-platform.local', 'Dilan');
            $user->setCurrentLevel('Junior')
                ->setStreakDays(1)
                ->addExperiencePoints(50);

            $this->getEntityManager()->persist($user);
            $this->getEntityManager()->flush();
        }

        return $user;
    }
}
