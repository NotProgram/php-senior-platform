<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\QuizAttemptRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: QuizAttemptRepository::class)]
#[ORM\Table(name: 'quiz_attempts')]
#[ORM\Index(name: 'idx_quiz_user_lesson', columns: ['user_id', 'lesson_slug'])]
class QuizAttempt
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $lessonSlug;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $answers = [];

    #[ORM\Column(type: Types::INTEGER)]
    private int $scorePercentage;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $passed;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $attemptedAt;

    /**
     * @param array<string, mixed> $answers
     */
    public function __construct(User $user, string $lessonSlug, array $answers, int $scorePercentage, bool $passed)
    {
        $this->user = $user;
        $this->lessonSlug = $lessonSlug;
        $this->answers = $answers;
        $this->scorePercentage = $scorePercentage;
        $this->passed = $passed;
        $this->attemptedAt = new DateTimeImmutable();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getUser(): User
    {
        return $this->user;
    }

    public function getLessonSlug(): string
    {
        return $this->lessonSlug;
    }

    /**
     * @return array<string, mixed>
     */
    public function getAnswers(): array
    {
        return $this->answers;
    }

    public function getScorePercentage(): int
    {
        return $this->scorePercentage;
    }

    public function isPassed(): bool
    {
        return $this->passed;
    }

    public function getAttemptedAt(): DateTimeImmutable
    {
        return $this->attemptedAt;
    }

    public function setAttemptedAt(DateTimeImmutable $attemptedAt): self
    {
        $this->attemptedAt = $attemptedAt;
        return $this;
    }
}
