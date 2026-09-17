<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Service\RoadmapService;
use PHPUnit\Framework\Attributes\DataProvider;

final class PageSmokeTest extends FunctionalTestCase
{
    #[DataProvider('pages')]
    public function testPageRenders(string $url): void
    {
        $this->client->request('GET', $url);

        self::assertResponseIsSuccessful();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function pages(): iterable
    {
        yield 'dashboard' => ['/'];
        yield 'roadmap' => ['/roadmap'];
        yield 'module' => ['/module/php-fundamentals'];
        yield 'labs hub' => ['/labs'];
        foreach (['git', 'testing', 'symfony-architecture', 'dependency-injection', 'doctrine', 'code-review', 'adr'] as $lab) {
            yield "lab $lab" => ["/lab/$lab"];
        }
        yield 'system design' => ['/system-design'];
        yield 'settings' => ['/settings'];
        yield 'entry lesson' => ['/lesson/php-syntax-types-variables'];
    }

    public function testScriptTagsInLessonContentAreShownAsTextNotExecuted(): void
    {
        $this->markLessonsCompleted('twig-inheritance-components');

        $this->client->request('GET', '/lesson/twig-inheritance-components');
        $html = (string) $this->client->getResponse()->getContent();

        self::assertResponseIsSuccessful();
        self::assertStringNotContainsString('<script>alert(1)</script>', $html);
        self::assertStringContainsString('&lt;script&gt;alert(1)&lt;/script&gt;', $html);
    }

    public function testEveryLessonPageRendersWithItsQuizOnceUnlocked(): void
    {
        $slugs = [];
        foreach (static::getContainer()->get(RoadmapService::class)->getSections() as $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                $slugs[] = $lesson['slug'];
            }
        }
        $this->markLessonsCompleted(...$slugs);

        foreach ($slugs as $slug) {
            $crawler = $this->client->request('GET', '/lesson/' . $slug);

            self::assertResponseIsSuccessful("Lesson $slug should render");
            self::assertGreaterThan(
                0,
                $crawler->filter('#quiz-section input[type=radio]')->count(),
                "Lesson $slug should render its quiz questions"
            );
        }
    }

    public function testSystemDesignStudioRendersAppleGlassComponents(): void
    {
        $crawler = $this->client->request('GET', '/system-design');

        self::assertResponseIsSuccessful();
        self::assertStringContainsString('System Design Studio', (string) $this->client->getResponse()->getContent());
        self::assertStringContainsString('apple-glass.css', (string) $this->client->getResponse()->getContent());
        self::assertGreaterThan(0, $crawler->filter('.sd-header-glass')->count());
        self::assertGreaterThan(0, $crawler->filter('.apple-segmented-control')->count());
        self::assertGreaterThan(0, $crawler->filter('.sd-inspector-glass')->count());
    }
}

