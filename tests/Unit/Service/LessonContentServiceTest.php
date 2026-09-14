<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\LessonContentService;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class LessonContentServiceTest extends TestCase
{
    private LessonContentService $service;

    protected function setUp(): void
    {
        $this->service = new LessonContentService(dirname(__DIR__, 3) . '/content/lessons');
    }

    public function testLoadsALessonBySlug(): void
    {
        self::assertSame('php-request-lifecycle', $this->service->findLesson('php-request-lifecycle')['slug'] ?? null);
    }

    #[DataProvider('invalidSlugs')]
    public function testReturnsNullForUnknownOrMaliciousSlugs(string $slug): void
    {
        self::assertNull($this->service->findLesson($slug));
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function invalidSlugs(): iterable
    {
        yield 'unknown lesson' => ['does-not-exist'];
        yield 'path traversal' => ['../../config/bundles'];
        yield 'empty' => [''];
    }
}
