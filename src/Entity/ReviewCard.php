<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ReviewCardRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

/**
 * Scheduling state of one retrieval-practice card for one learner.
 *
 * Card content is derived from the lesson corpus, never stored: only the SM-2
 * scheduling state lives here, so regenerating or rewording a card never
 * invalidates the learner's review history.
 */
#[ORM\Entity(repositoryClass: ReviewCardRepository::class)]
#[ORM\Table(name: 'review_cards')]
#[ORM\UniqueConstraint(name: 'uniq_user_card', columns: ['user_id', 'card_id'])]
#[ORM\Index(name: 'idx_review_due', columns: ['user_id', 'due_at'])]
class ReviewCard
{
    public const EASE_MIN = 1.3;
    public const EASE_MAX = 2.8;
    public const EASE_DEFAULT = 2.5;

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(name: 'card_id', type: Types::STRING, length: 190)]
    private string $cardId;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $lessonSlug;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $kind;

    #[ORM\Column(type: Types::FLOAT)]
    private float $easeFactor = self::EASE_DEFAULT;

    #[ORM\Column(type: Types::INTEGER)]
    private int $intervalDays = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $repetitions = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $lapses = 0;

    #[ORM\Column(name: 'due_at', type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $dueAt;

    #[ORM\Column(name: 'last_reviewed_at', type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $lastReviewedAt = null;

    public function __construct(User $user, string $cardId, string $lessonSlug, string $kind)
    {
        $this->user = $user;
        $this->cardId = $cardId;
        $this->lessonSlug = $lessonSlug;
        $this->kind = $kind;
        $this->dueAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getCardId(): string
    {
        return $this->cardId;
    }

    public function getLessonSlug(): string
    {
        return $this->lessonSlug;
    }

    public function getKind(): string
    {
        return $this->kind;
    }

    public function getEaseFactor(): float
    {
        return $this->easeFactor;
    }

    public function getIntervalDays(): int
    {
        return $this->intervalDays;
    }

    public function getRepetitions(): int
    {
        return $this->repetitions;
    }

    public function getLapses(): int
    {
        return $this->lapses;
    }

    public function getDueAt(): DateTimeImmutable
    {
        return $this->dueAt;
    }

    public function getLastReviewedAt(): ?DateTimeImmutable
    {
        return $this->lastReviewedAt;
    }

    public function isDue(DateTimeImmutable $moment): bool
    {
        return $this->dueAt <= $moment;
    }

    /**
     * Applies a freshly computed schedule. The SM-2 arithmetic itself lives in
     * SpacedRepetitionService; the entity only guards its own invariants.
     */
    public function reschedule(float $easeFactor, int $intervalDays, int $repetitions, DateTimeImmutable $dueAt, bool $lapsed): self
    {
        $this->easeFactor = max(self::EASE_MIN, min(self::EASE_MAX, $easeFactor));
        $this->intervalDays = max(0, $intervalDays);
        $this->repetitions = max(0, $repetitions);
        $this->dueAt = $dueAt;
        $this->lastReviewedAt = new DateTimeImmutable();

        if ($lapsed) {
            $this->lapses++;
        }

        return $this;
    }
}
