<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Enum\ProgressStatus;

final class SettingsTest extends FunctionalTestCase
{
    public function testResetProgressWipesProgressAndExperience(): void
    {
        $this->markLessonsCompleted('php-request-lifecycle', 'php-types-memory');
        $crawler = $this->client->request('GET', '/settings');

        $this->client->submit($crawler->selectButton('Reiniciar mi progreso a cero')->form());

        self::assertResponseRedirects('/roadmap');
        $user = $this->reloadDefaultUser();
        self::assertSame(0, $user->getExperiencePoints());
        self::assertSame('Junior', $user->getCurrentLevel());
        self::assertSame(1, $user->getStreakDays());
        self::assertNull($this->lessonStatus('php-request-lifecycle'));
    }

    public function testResetProgressRejectsCrossSiteRequests(): void
    {
        $this->markLessonsCompleted('php-request-lifecycle');

        $this->client->request('POST', '/settings/reset-progress', ['_csrf_token' => 'forged'], [], ['HTTP_SEC_FETCH_SITE' => 'cross-site']);

        self::assertResponseStatusCodeSame(403);
        self::assertSame(ProgressStatus::Completed->value, $this->lessonStatus('php-request-lifecycle'));
    }

    public function testUpdateProfileName(): void
    {
        $crawler = $this->client->request('GET', '/settings');
        self::assertResponseIsSuccessful();

        $form = $crawler->selectButton('Guardar')->form([
            'display_name' => 'Alex Developer',
        ]);
        $this->client->submit($form);

        self::assertResponseRedirects('/settings');
        $this->client->followRedirect();

        $user = $this->reloadDefaultUser();
        self::assertSame('Alex Developer', $user->getDisplayName());
        self::assertSelectorTextContains('.user-badge-text', 'Alex Developer');
    }

    public function testUpdateProfileNameRejectsBlank(): void
    {
        $crawler = $this->client->request('GET', '/settings');
        $token = $crawler->filter('input[name="_csrf_token"]')->first()->attr('value');

        $this->client->request('POST', '/settings/profile', [
            '_csrf_token' => $token,
            'display_name' => '   ',
        ]);

        self::assertResponseRedirects('/settings');
        $this->client->followRedirect();
        self::assertSelectorExists('.flash--error');

        $user = $this->reloadDefaultUser();
        self::assertSame('Dilan', $user->getDisplayName());
    }
}
