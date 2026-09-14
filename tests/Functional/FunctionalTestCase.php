<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use App\Repository\UserRepository;
use App\Service\RoadmapService;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Tools\SchemaTool;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

/**
 * Boots a browser against a freshly created SQLite schema (see .env.test) for every test.
 */
abstract class FunctionalTestCase extends WebTestCase
{
    protected KernelBrowser $client;

    protected function setUp(): void
    {
        $this->client = static::createClient();

        $entityManager = $this->entityManager();
        $metadata = $entityManager->getMetadataFactory()->getAllMetadata();
        $schemaTool = new SchemaTool($entityManager);
        $schemaTool->dropSchema($metadata);
        $schemaTool->createSchema($metadata);
    }

    protected function entityManager(): EntityManagerInterface
    {
        return static::getContainer()->get(EntityManagerInterface::class);
    }

    /**
     * Re-reads the default user from the database, bypassing the identity map filled by earlier requests.
     */
    protected function reloadDefaultUser(): User
    {
        $this->entityManager()->clear();

        return static::getContainer()->get(UserRepository::class)->findOrCreateDefaultUser();
    }

    protected function lessonStatus(string $lessonSlug): ?string
    {
        return static::getContainer()->get(UserProgressRepository::class)
            ->findProgress($this->reloadDefaultUser(), $lessonSlug)
            ?->getStatus();
    }

    protected function markLessonsCompleted(string ...$lessonSlugs): void
    {
        $user = $this->reloadDefaultUser();
        $roadmap = static::getContainer()->get(RoadmapService::class);

        foreach ($lessonSlugs as $lessonSlug) {
            $this->entityManager()->persist(new UserProgress(
                $user,
                $roadmap->getModuleSlugForLesson($lessonSlug),
                $lessonSlug,
                ProgressStatus::Completed->value
            ));
        }

        $this->entityManager()->flush();
    }
}
