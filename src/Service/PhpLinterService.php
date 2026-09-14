<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Syntax-checks PHP snippets with the running engine's own parser (token_get_all + TOKEN_PARSE).
 * There is deliberately no `php -l` shell-out: the CLI binary may be a different PHP version than the
 * one serving the app, and exec() is unavailable on serverless hosts, which made every snippet fail there.
 */
class PhpLinterService
{
    /**
     * @return array{valid: bool, errors: list<array{line: int, column: int, message: string, severity: string}>}
     */
    public function lint(string $code): array
    {
        if (trim($code) === '') {
            return ['valid' => true, 'errors' => []];
        }

        try {
            token_get_all($code, \TOKEN_PARSE);
        } catch (\ParseError $e) {
            return [
                'valid' => false,
                'errors' => [
                    [
                        'line' => $e->getLine(),
                        'column' => 1,
                        'message' => $e->getMessage(),
                        'severity' => 'error',
                    ],
                ],
            ];
        }

        return ['valid' => true, 'errors' => []];
    }
}
