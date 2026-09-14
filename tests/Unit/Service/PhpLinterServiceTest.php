<?php

declare(strict_types=1);

namespace App\Tests\Unit\Service;

use App\Service\PhpLinterService;
use PHPUnit\Framework\TestCase;

final class PhpLinterServiceTest extends TestCase
{
    private PhpLinterService $linter;

    protected function setUp(): void
    {
        $this->linter = new PhpLinterService();
    }

    public function testBlankCodeIsValid(): void
    {
        self::assertSame(['valid' => true, 'errors' => []], $this->linter->lint("  \n "));
    }

    public function testAcceptsSyntaxOfTheRunningPhpVersion(): void
    {
        $code = <<<'PHP'
            <?php
            declare(strict_types=1);

            final class Money
            {
                public function __construct(public private(set) int $amount) {}
            }
            PHP;

        self::assertTrue($this->linter->lint($code)['valid']);
    }

    public function testReportsLineAndMessageOfASyntaxError(): void
    {
        $result = $this->linter->lint("<?php\n\$a = 1;\n\$b = ;\n");

        self::assertFalse($result['valid']);
        self::assertSame(3, $result['errors'][0]['line']);
        self::assertStringContainsString('syntax error', $result['errors'][0]['message']);
    }
}
