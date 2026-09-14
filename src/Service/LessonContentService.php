<?php

declare(strict_types=1);

namespace App\Service;

use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * Lesson content lives in content/lessons/<slug>.php, one file returning an array per lesson,
 * so each lesson can be edited and reviewed on its own (OPcache keeps the arrays in shared memory).
 */
class LessonContentService
{
    private const SLUG_PATTERN = '/^[a-z0-9]+(?:-[a-z0-9]+)*$/';

    public function __construct(
        #[Autowire('%kernel.project_dir%/content/lessons')]
        private readonly string $lessonsDirectory,
    ) {}

    /**
     * @return array<string, mixed>|null null when the slug has no content file
     */
    public function findLesson(string $slug): ?array
    {
        // Slugs come from the URL: only plain kebab-case names may reach the filesystem
        if (preg_match(self::SLUG_PATTERN, $slug) !== 1) {
            return null;
        }

        $file = $this->lessonsDirectory . '/' . $slug . '.php';

        return is_file($file) ? require $file : null;
    }
}
