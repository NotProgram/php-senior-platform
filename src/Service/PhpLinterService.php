<?php

declare(strict_types=1);

namespace App\Service;

class PhpLinterService
{
    /**
     * @return array{valid: bool, errors: list<array{line: int, column: int, message: string, severity: string}>}
     */
    public function lint(string $code): array
    {
        $trimmed = trim($code);
        if ($trimmed === '') {
            return ['valid' => true, 'errors' => []];
        }

        // 1. Fast in-memory token parsing with TOKEN_PARSE
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

        // 2. Secondary validation via native CLI linting
        $tempFile = tempnam(sys_get_temp_dir(), 'php_lint_');
        if ($tempFile === false) {
            return ['valid' => true, 'errors' => []];
        }

        file_put_contents($tempFile, $code);

        $output = [];
        $returnCode = 0;
        exec('php -l -d display_errors=1 ' . escapeshellarg($tempFile) . ' 2>&1', $output, $returnCode);
        @unlink($tempFile);

        $outputText = implode("\n", $output);

        if ($returnCode === 0 && str_contains($outputText, 'No syntax errors detected')) {
            return [
                'valid' => true,
                'errors' => [],
            ];
        }

        $line = 1;
        $message = 'Error sintáctico detectado.';

        if (preg_match('/syntax error,?\s*(.+?)\s+in\s+.+?\s+on\s+line\s+(\d+)/i', $outputText, $matches)) {
            $message = 'Syntax error: ' . trim($matches[1]);
            $line = (int) $matches[2];
        } elseif (preg_match('/Parse error:\s*(.+?)\s+in\s+.+?\s+on\s+line\s+(\d+)/i', $outputText, $matches)) {
            $message = 'Parse error: ' . trim($matches[1]);
            $line = (int) $matches[2];
        }

        return [
            'valid' => false,
            'errors' => [
                [
                    'line' => $line,
                    'column' => 1,
                    'message' => $message,
                    'severity' => 'error',
                ],
            ],
        ];
    }
}
