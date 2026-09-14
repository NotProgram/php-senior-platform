<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ControllerSmokeTest extends WebTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Dilan');
    }

    public function testRoadmapPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/roadmap');

        self::assertResponseIsSuccessful();
    }

    public function testLearningPathsPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/rutas');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Rutas');
    }

    public function testLearningPathDetailIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/ruta/fundamentos-solidos');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Fundamentos');
    }

    public function testLabsHubPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/labs');

        self::assertResponseIsSuccessful();
    }

    public function testGitLabIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/git');

        self::assertResponseIsSuccessful();
    }

    public function testTestingLabIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lab/testing');

        self::assertResponseIsSuccessful();
    }

    public function testSystemDesignPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/system-design');

        self::assertResponseIsSuccessful();
    }

    public function testReviewPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/repaso');

        self::assertResponseIsSuccessful();
    }

    public function testExamPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/examen');

        self::assertResponseIsSuccessful();
    }

    public function testSettingsPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/settings');

        self::assertResponseIsSuccessful();
    }

    public function testUnlockedLessonPageIsSuccessful(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lesson/php-request-lifecycle');

        self::assertResponseIsSuccessful();
    }

    public function testLockedLessonRedirectsToRoadmap(): void
    {
        $client = static::createClient();
        $client->request('GET', '/lesson/psr-12-per-coding-style');

        self::assertResponseRedirects('/roadmap');
    }
}