<?php

declare(strict_types=1);

namespace App\Tests\Functional;

use App\Entity\ReviewCard;
use App\Enum\ProgressStatus;
use App\Repository\ReviewCardRepository;
use App\Service\LearningProgressService;
use App\Service\ProgressBackupService;
use Symfony\Component\HttpFoundation\File\UploadedFile;

final class ProgressBackupTest extends FunctionalTestCase
{
    public function testExportProgressReturnsValidJsonSnapshot(): void
    {
        $this->markLessonsCompleted('php-request-lifecycle');
        $user = $this->reloadDefaultUser();
        $user->setExperiencePoints(150);
        $this->entityManager()->flush();

        $this->client->request('GET', '/settings/export');

        self::assertResponseIsSuccessful();
        self::assertResponseHeaderSame('content-type', 'application/json');
        self::assertStringContainsString('attachment', (string) $this->client->getResponse()->headers->get('content-disposition'));

        $content = (string) $this->client->getResponse()->getContent();
        $data = json_decode($content, true, 512, JSON_THROW_ON_ERROR);

        self::assertSame(ProgressBackupService::CURRENT_SCHEMA_VERSION, $data['schema_version']);
        self::assertSame('Senior DevLab', $data['app']);
        self::assertSame(150, $data['user']['experience_points']);
        self::assertCount(1, $data['progress']);
        self::assertSame('php-request-lifecycle', $data['progress'][0]['lesson_slug']);
    }

    public function testImportProgressRestoresUserDataAndLessons(): void
    {
        $crawler = $this->client->request('GET', '/settings');
        self::assertResponseIsSuccessful();
        $token = $crawler->filter('input[name="_csrf_token"]')->first()->attr('value');

        $backupPayload = [
            'schema_version' => 1,
            'app' => 'Senior DevLab',
            'exported_at' => '2026-09-14T10:00:00+00:00',
            'user' => [
                'email' => 'dilan@devlab.local',
                'display_name' => 'Dilan Garrido',
                'current_level' => 'Senior',
                'experience_points' => 500,
                'streak_days' => 5,
                'last_active_at' => '2026-09-14T10:00:00+00:00',
            ],
            'progress' => [
                [
                    'module_slug' => 'php-fundamentals',
                    'lesson_slug' => 'php-request-lifecycle',
                    'status' => ProgressStatus::Completed->value,
                    'score' => 100,
                    'notes' => 'Imported test note',
                    'completed_at' => '2026-09-14T09:30:00+00:00',
                    'updated_at' => '2026-09-14T09:30:00+00:00',
                ],
                [
                    'module_slug' => 'php-fundamentals',
                    'lesson_slug' => 'php-types-memory',
                    'status' => ProgressStatus::Mastered->value,
                    'score' => 100,
                    'notes' => null,
                    'completed_at' => '2026-09-14T09:45:00+00:00',
                    'updated_at' => '2026-09-14T09:45:00+00:00',
                ],
            ],
            'quiz_attempts' => [
                [
                    'lesson_slug' => 'php-request-lifecycle',
                    'answers' => ['q1' => 'a'],
                    'score_percentage' => 100,
                    'is_passed' => true,
                    'attempted_at' => '2026-09-14T09:30:00+00:00',
                ],
            ],
            'exercise_attempts' => [
                [
                    'lesson_slug' => 'php-request-lifecycle',
                    'submitted_code' => '<?php echo "ok";',
                    'is_passed' => true,
                    'feedback' => 'Perfect',
                    'executed_at' => '2026-09-14T09:30:00+00:00',
                ],
            ],
            'review_cards' => [
                [
                    'card_id' => 'card-test-1',
                    'lesson_slug' => 'php-request-lifecycle',
                    'kind' => 'concept',
                    'ease_factor' => 2.6,
                    'interval_days' => 3,
                    'repetitions' => 2,
                    'lapses' => 0,
                    'due_at' => '2026-09-17T10:00:00+00:00',
                    'last_reviewed_at' => '2026-09-14T10:00:00+00:00',
                ],
            ],
        ];

        $tempFile = tempnam(sys_get_temp_dir(), 'backup_test_');
        file_put_contents($tempFile, json_encode($backupPayload));

        $uploadedFile = new UploadedFile(
            $tempFile,
            'progress.json',
            'application/json',
            null,
            true
        );

        $this->client->request(
            'POST',
            '/settings/import',
            ['_csrf_token' => $token],
            ['backup_file' => $uploadedFile]
        );

        self::assertResponseRedirects('/settings');
        $this->client->followRedirect();
        self::assertSelectorExists('.flash--success');

        $user = $this->reloadDefaultUser();
        self::assertSame(500, $user->getExperiencePoints());
        self::assertSame('Senior', $user->getCurrentLevel());
        self::assertSame(5, $user->getStreakDays());

        self::assertSame(ProgressStatus::Completed->value, $this->lessonStatus('php-request-lifecycle'));
        self::assertSame(ProgressStatus::Mastered->value, $this->lessonStatus('php-types-memory'));

        $card = static::getContainer()->get(ReviewCardRepository::class)->findForUser($user, 'card-test-1');
        self::assertNotNull($card);
        self::assertSame(3, $card->getIntervalDays());
        self::assertSame(2, $card->getRepetitions());

        if (file_exists($tempFile)) {
            @unlink($tempFile);
        }
    }

    public function testImportProgressRejectsInvalidCsrf(): void
    {
        $this->client->request('POST', '/settings/import', ['_csrf_token' => 'invalid-token']);
        self::assertResponseStatusCodeSame(403);
    }

    public function testCompleteLessonTriggersAutoBackup(): void
    {
        $backupService = static::getContainer()->get(ProgressBackupService::class);
        $learningService = static::getContainer()->get(LearningProgressService::class);
        $user = $this->reloadDefaultUser();

        $learningService->completeLesson($user, 'php-request-lifecycle');

        self::assertTrue($backupService->hasAutoBackup());
        $info = $backupService->getAutoBackupInfo();
        self::assertNotNull($info);
        self::assertGreaterThanOrEqual(1, $info['lessons_count']);
    }

    public function testImportAutoBackupRestoresProgress(): void
    {
        $backupService = static::getContainer()->get(ProgressBackupService::class);
        $user = $this->reloadDefaultUser();
        $user->setExperiencePoints(320);
        $this->markLessonsCompleted('php-request-lifecycle');
        $backupService->autoSave($user);

        // Reset user progress in DB
        $user->setExperiencePoints(0);
        $this->entityManager()->flush();
        $this->reloadDefaultUser();

        $crawler = $this->client->request('GET', '/settings');
        $token = $crawler->filter('input[name="_csrf_token"]')->first()->attr('value');

        $this->client->request('POST', '/settings/import-auto-backup', ['_csrf_token' => $token]);
        self::assertResponseRedirects('/settings');
        $this->client->followRedirect();
        self::assertSelectorExists('.flash--success');

        $reloaded = $this->reloadDefaultUser();
        self::assertSame(320, $reloaded->getExperiencePoints());
    }
}
