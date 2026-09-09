<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\ExerciseAttemptRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: ExerciseAttemptRepository::class)]
#[ORM\Table(name: 'exercise_attempts')]
#[ORM\Index(name: 'idx_attempt_user_lesson', columns: ['user_id', 'lesson_slug'])]
class ExerciseAttempt
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

    #[ORM\Column(type: Types::TEXT)]
    private string $submittedCode;

    #[ORM\Column(type: Types::BOOLEAN)]
    private bool $isPassed;

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $feedback = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $executedAt;

    public function __construct(User $user, string $lessonSlug, string $submittedCode, bool $isPassed, ?string $feedback = null)
    {
        $this->user = $user;
        $this->lessonSlug = $lessonSlug;
        $this->submittedCode = $submittedCode;
        $this->isPassed = $isPassed;
        $this->feedback = $feedback;
        $this->executedAt = new DateTimeImmutable();
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

    public function getSubmittedCode(): string
    {
        return $this->submittedCode;
    }

    public function isPassed(): bool
    {
        return $this->isPassed;
    }

    public function getFeedback(): ?string
    {
        return $this->feedback;
    }

    public function getExecutedAt(): DateTimeImmutable
    {
        return $this->executedAt;
    }
}
