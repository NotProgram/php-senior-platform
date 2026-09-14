<?php

declare(strict_types=1);

namespace App\Entity;

use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserProgressRepository::class)]
#[ORM\Table(name: 'user_progress')]
#[ORM\UniqueConstraint(name: 'uniq_user_lesson', columns: ['user_id', 'lesson_slug'])]
#[ORM\Index(name: 'idx_user_module', columns: ['user_id', 'module_slug'])]
class UserProgress
{
    public const STATUS_LOCKED = 'LOCKED';
    public const STATUS_AVAILABLE = 'AVAILABLE';
    public const STATUS_IN_PROGRESS = 'IN_PROGRESS';
    public const STATUS_COMPLETED = 'COMPLETED';
    public const STATUS_MASTERED = 'MASTERED';

    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class, inversedBy: 'progressRecords')]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $moduleSlug;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $lessonSlug;

    #[ORM\Column(type: Types::STRING, length: 30)]
    private string $status = self::STATUS_AVAILABLE;

    #[ORM\Column(type: Types::INTEGER)]
    private int $score = 0;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE, nullable: true)]
    private ?DateTimeImmutable $completedAt = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    public function __construct(User $user, string $moduleSlug, string $lessonSlug, string $status = self::STATUS_AVAILABLE)
    {
        $this->user = $user;
        $this->moduleSlug = $moduleSlug;
        $this->lessonSlug = $lessonSlug;
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getModuleSlug(): string
    {
        return $this->moduleSlug;
    }

    public function getLessonSlug(): string
    {
        return $this->lessonSlug;
    }

    public function getStatus(): string
    {
        return $this->status;
    }

    public function setStatus(string $status): self
    {
        $this->status = $status;
        $this->updatedAt = new DateTimeImmutable();
        if ($status === self::STATUS_COMPLETED || $status === self::STATUS_MASTERED) {
            if ($this->completedAt === null) {
                $this->completedAt = new DateTimeImmutable();
            }
        }
        return $this;
    }

    /**
     * Moves the lesson forward to $target and never backwards, so e.g. passing a quiz with 80%
     * cannot demote a lesson that was already MASTERED.
     *
     * @return bool whether the status actually advanced
     */
    public function advanceTo(ProgressStatus $target): bool
    {
        $current = ProgressStatus::tryFrom($this->status) ?? ProgressStatus::Available;
        if ($target->rank() <= $current->rank()) {
            return false;
        }

        $this->setStatus($target->value);

        return true;
    }

    public function getScore(): int
    {
        return $this->score;
    }

    public function setScore(int $score): self
    {
        $this->score = $score;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getNotes(): ?string
    {
        return $this->notes;
    }

    public function setNotes(?string $notes): self
    {
        $this->notes = $notes;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    public function getCompletedAt(): ?DateTimeImmutable
    {
        return $this->completedAt;
    }

    public function setCompletedAt(?DateTimeImmutable $completedAt): self
    {
        $this->completedAt = $completedAt;
        return $this;
    }

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }

    public function setUpdatedAt(DateTimeImmutable $updatedAt): self
    {
        $this->updatedAt = $updatedAt;
        return $this;
    }
}
