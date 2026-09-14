<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class LintRequest
{
    public const MAX_CODE_LENGTH = 100_000;

    public function __construct(
        #[Assert\Length(max: self::MAX_CODE_LENGTH)]
        public string $code = '',
    ) {}
}
