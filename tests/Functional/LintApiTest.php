<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\DTO\LintRequest;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

final class LintApiTest extends WebTestCase
{
    public function testValidCodePasses(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/lint', ['code' => "<?php\necho 'ok';\n"]);

        self::assertResponseIsSuccessful();
        self::assertSame(['valid' => true, 'errors' => []], $this->decode((string) $client->getResponse()->getContent()));
    }

    public function testSyntaxErrorIsReportedWithItsLine(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/lint', ['code' => "<?php\n\$total = ;\n"]);

        $body = $this->decode((string) $client->getResponse()->getContent());
        self::assertFalse($body['valid']);
        self::assertSame(2, $body['errors'][0]['line']);
    }

    public function testOversizedPayloadIsRejected(): void
    {
        $client = static::createClient();

        $client->jsonRequest('POST', '/api/lint', ['code' => str_repeat('a', LintRequest::MAX_CODE_LENGTH + 1)]);

        self::assertResponseStatusCodeSame(422);
    }

    /**
     * @return array<string, mixed>
     */
    private function decode(string $json): array
    {
        return json_decode($json, true, flags: \JSON_THROW_ON_ERROR);
    }
}
