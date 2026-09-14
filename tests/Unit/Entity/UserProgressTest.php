<?php

declare(strict_types=1);

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use PHPUnit\Framework\TestCase;

final class UserProgressTest extends TestCase
{
    public function testAdvancesForwardAndStampsTheCompletionDate(): void
    {
        $progress = $this->progressAt(ProgressStatus::InProgress);

        self::assertTrue($progress->advanceTo(ProgressStatus::Completed));
        self::assertSame(ProgressStatus::Completed->value, $progress->getStatus());
        self::assertNotNull($progress->getCompletedAt());
    }

    public function testNeverDowngradesAMasteredLesson(): void
    {
        $progress = $this->progressAt(ProgressStatus::Mastered);

        self::assertFalse($progress->advanceTo(ProgressStatus::Completed));
        self::assertSame(ProgressStatus::Mastered->value, $progress->getStatus());
    }

    public function testReachingTheSameStatusAgainIsNotAnAdvance(): void
    {
        self::assertFalse($this->progressAt(ProgressStatus::Completed)->advanceTo(ProgressStatus::Completed));
    }

    public function testResettingTheUserRestoresStartingValues(): void
    {
        $user = (new User('dev@example.test', 'Dev'))
            ->addExperiencePoints(300)
            ->setCurrentLevel('Senior')
            ->setStreakDays(12);

        $user->resetProgress();

        self::assertSame(0, $user->getExperiencePoints());
        self::assertSame('Junior', $user->getCurrentLevel());
        self::assertSame(1, $user->getStreakDays());
    }

    private function progressAt(ProgressStatus $status): UserProgress
    {
        return new UserProgress(new User('dev@example.test', 'Dev'), 'php-fundamentals', 'php-types-memory', $status->value);
    }
}
