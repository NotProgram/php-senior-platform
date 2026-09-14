<?php

declare(strict_types=1);

namespace App\Tests\Unit;

use App\Entity\ReviewCard;
use App\Repository\ReviewCardRepository;
use App\Service\FlashcardService;
use App\Service\SpacedRepetitionService;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class SpacedRepetitionServiceTest extends TestCase
{
    private SpacedRepetitionService $service;

    protected function setUp(): void
    {
        $flashcards = $this->createStub(FlashcardService::class);
        $repo = $this->createStub(ReviewCardRepository::class);
        $em = $this->createStub(EntityManagerInterface::class);

        $this->service = new SpacedRepetitionService($flashcards, $repo, $em);
    }

    public function testComputeScheduleAgainResetsRepetitionsAndInterval(): void
    {
        $result = $this->service->computeSchedule(
            ease: 2.5,
            intervalDays: 10,
            repetitions: 3,
            grade: SpacedRepetitionService::GRADE_AGAIN
        );

        self::assertSame(0, $result['repetitions']);
        self::assertSame(0, $result['interval_days']);
        self::assertEqualsWithDelta(2.3, $result['ease'], 0.001);
    }

    public function testComputeScheduleFirstRepetitionGood(): void
    {
        $result = $this->service->computeSchedule(
            ease: 2.5,
            intervalDays: 0,
            repetitions: 0,
            grade: SpacedRepetitionService::GRADE_GOOD
        );

        self::assertSame(1, $result['repetitions']);
        self::assertSame(1, $result['interval_days']);
        self::assertEqualsWithDelta(2.5, $result['ease'], 0.001);
    }

    public function testComputeScheduleFirstRepetitionEasy(): void
    {
        $result = $this->service->computeSchedule(
            ease: 2.5,
            intervalDays: 0,
            repetitions: 0,
            grade: SpacedRepetitionService::GRADE_EASY
        );

        self::assertSame(1, $result['repetitions']);
        self::assertSame(3, $result['interval_days']);
        self::assertEqualsWithDelta(2.65, $result['ease'], 0.001);
    }

    public function testComputeScheduleSecondRepetitionGood(): void
    {
        $result = $this->service->computeSchedule(
            ease: 2.5,
            intervalDays: 1,
            repetitions: 1,
            grade: SpacedRepetitionService::GRADE_GOOD
        );

        self::assertSame(2, $result['repetitions']);
        self::assertSame(6, $result['interval_days']);
    }

    public function testComputeScheduleRespectsEaseBounds(): void
    {
        // Lower bound test
        $result = $this->service->computeSchedule(
            ease: ReviewCard::EASE_MIN,
            intervalDays: 1,
            repetitions: 1,
            grade: SpacedRepetitionService::GRADE_HARD
        );

        self::assertGreaterThanOrEqual(ReviewCard::EASE_MIN, $result['ease']);

        // Upper bound test
        $result = $this->service->computeSchedule(
            ease: ReviewCard::EASE_MAX,
            intervalDays: 1,
            repetitions: 1,
            grade: SpacedRepetitionService::GRADE_EASY
        );

        self::assertLessThanOrEqual(ReviewCard::EASE_MAX, $result['ease']);
    }
}