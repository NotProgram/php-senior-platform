<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\UserRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: UserRepository::class)]
#[ORM\Table(name: 'users')]
class User
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\Column(type: Types::STRING, length: 180, unique: true)]
    private string $email;

    #[ORM\Column(type: Types::STRING, length: 100)]
    private string $displayName;

    #[ORM\Column(type: Types::STRING, length: 50)]
    private string $currentLevel = 'Junior';

    #[ORM\Column(type: Types::INTEGER)]
    private int $experiencePoints = 0;

    #[ORM\Column(type: Types::INTEGER)]
    private int $streakDays = 1;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $createdAt;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $lastActiveAt;

    /**
     * @var Collection<int, UserProgress>
     */
    #[ORM\OneToMany(targetEntity: UserProgress::class, mappedBy: 'user', cascade: ['persist', 'remove'], orphanRemoval: true)]
    private Collection $progressRecords;

    public function __construct(string $email, string $displayName)
    {
        $this->email = $email;
        $this->displayName = $displayName;
        $this->createdAt = new DateTimeImmutable();
        $this->lastActiveAt = new DateTimeImmutable();
        $this->progressRecords = new ArrayCollection();
    }

    public function getId(): ?int
    {
        return $this->id;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function setEmail(string $email): self
    {
        $this->email = $email;
        return $this;
    }

    public function getDisplayName(): string
    {
        return $this->displayName;
    }

    public function setDisplayName(string $displayName): self
    {
        $this->displayName = $displayName;
        return $this;
    }

    public function getCurrentLevel(): string
    {
        return $this->currentLevel;
    }

    public function setCurrentLevel(string $currentLevel): self
    {
        $this->currentLevel = $currentLevel;
        return $this;
    }

    public function getExperiencePoints(): int
    {
        return $this->experiencePoints;
    }

    public function addExperiencePoints(int $points): self
    {
        $this->experiencePoints += $points;
        return $this;
    }

    public function getStreakDays(): int
    {
        return $this->streakDays;
    }

    public function setStreakDays(int $streakDays): self
    {
        $this->streakDays = $streakDays;
        return $this;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getLastActiveAt(): DateTimeImmutable
    {
        return $this->lastActiveAt;
    }

    public function touchLastActive(): self
    {
        $this->lastActiveAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return Collection<int, UserProgress>
     */
    public function getProgressRecords(): Collection
    {
        return $this->progressRecords;
    }
}
