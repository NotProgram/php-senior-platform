<?php

declare(strict_types=1);

namespace App\Tests\Functional;

class ControllerSmokeTest extends FunctionalTestCase
{
    public function testHomePageIsSuccessful(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('body', 'Dilan');
    }

    public function testRoadmapPageIsSuccessful(): void
    {
        $this->client->request('GET', '/roadmap');

        self::assertResponseIsSuccessful();
    }

    public function testLearningPathsPageIsSuccessful(): void
    {
        $this->client->request('GET', '/rutas');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Rutas');
    }

    public function testLearningPathDetailIsSuccessful(): void
    {
        $this->client->request('GET', '/ruta/fundamentos-solidos');

        self::assertResponseIsSuccessful();
        self::assertSelectorTextContains('h1', 'Fundamentos');
    }

    public function testLabsHubPageIsSuccessful(): void
    {
        $this->client->request('GET', '/labs');

        self::assertResponseIsSuccessful();
    }

    public function testGitLabIsSuccessful(): void
    {
        $this->client->request('GET', '/lab/git');

        self::assertResponseIsSuccessful();
    }

    public function testTestingLabIsSuccessful(): void
    {
        $this->client->request('GET', '/lab/testing');

        self::assertResponseIsSuccessful();
    }

    public function testSystemDesignPageIsSuccessful(): void
    {
        $this->client->request('GET', '/system-design');

        self::assertResponseIsSuccessful();
    }

    public function testReviewPageIsSuccessful(): void
    {
        $this->client->request('GET', '/repaso');

        self::assertResponseIsSuccessful();
    }

    public function testExamPageIsSuccessful(): void
    {
        $this->client->request('GET', '/examen');

        self::assertResponseIsSuccessful();
    }

    public function testSettingsPageIsSuccessful(): void
    {
        $this->client->request('GET', '/settings');

        self::assertResponseIsSuccessful();
    }

    public function testUnlockedLessonPageIsSuccessful(): void
    {
        $this->client->request('GET', '/lesson/php-request-lifecycle');

        self::assertResponseIsSuccessful();
    }

    public function testLockedLessonRedirectsToRoadmap(): void
    {
        $this->client->request('GET', '/lesson/php-types-memory');

        self::assertResponseRedirects('/roadmap');
    }

    public function testResponsiveElementsAreRendered(): void
    {
        $this->client->request('GET', '/');

        self::assertResponseIsSuccessful();
        self::assertSelectorExists('link[href*="responsive.css"]');
        self::assertSelectorExists('#btn-mobile-menu');
        self::assertSelectorExists('#btn-mobile-search');
        self::assertSelectorExists('#mobile-nav-drawer');
        self::assertSelectorExists('#mobile-nav-backdrop');
        self::assertSelectorExists('#sidebar-backdrop');
    }
}
