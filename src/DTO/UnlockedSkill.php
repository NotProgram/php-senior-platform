<?php

declare(strict_types=1);

namespace App\DTO;

readonly class UnlockedSkill
{
    public function __construct(
        public string $name,
        public string $category,
        public string $icon,
        public bool $isUnlocked,
        public string $masteryLevel,
        public string $description
    ) {}
}
