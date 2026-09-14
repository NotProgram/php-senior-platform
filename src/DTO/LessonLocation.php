<?php

declare(strict_types=1);

namespace App\DTO;

/**
 * Where a lesson lives in the roadmap: the owning module (section key and data) plus the lesson metadata.
 */
final readonly class LessonLocation
{
    /**
     * @param array<string, mixed> $module
     * @param array<string, mixed> $lesson
     */
    public function __construct(
        public string $moduleSlug,
        public array $module,
        public array $lesson,
    ) {}
}
