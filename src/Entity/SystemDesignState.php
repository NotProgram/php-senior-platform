<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\SystemDesignStateRepository;
use DateTimeImmutable;
use Doctrine\DBAL\Types\Types;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: SystemDesignStateRepository::class)]
#[ORM\Table(name: 'system_design_states')]
#[ORM\UniqueConstraint(name: 'uniq_user_scenario', columns: ['user_id', 'scenario_slug'])]
class SystemDesignState
{
    #[ORM\Id]
    #[ORM\GeneratedValue]
    #[ORM\Column(type: Types::INTEGER)]
    private ?int $id = null;

    #[ORM\ManyToOne(targetEntity: User::class)]
    #[ORM\JoinColumn(nullable: false, onDelete: 'CASCADE')]
    private User $user;

    #[ORM\Column(type: Types::STRING, length: 150)]
    private string $scenarioSlug;

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $nodesConfig = [];

    /**
     * @var array<string, mixed>
     */
    #[ORM\Column(type: Types::JSON)]
    private array $connectionsConfig = [];

    #[ORM\Column(type: Types::TEXT, nullable: true)]
    private ?string $notes = null;

    #[ORM\Column(type: Types::DATETIME_IMMUTABLE)]
    private DateTimeImmutable $updatedAt;

    /**
     * @param array<string, mixed> $nodesConfig
     * @param array<string, mixed> $connectionsConfig
     */
    public function __construct(User $user, string $scenarioSlug, array $nodesConfig = [], array $connectionsConfig = [])
    {
        $this->user = $user;
        $this->scenarioSlug = $scenarioSlug;
        $this->nodesConfig = $nodesConfig;
        $this->connectionsConfig = $connectionsConfig;
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

    public function getScenarioSlug(): string
    {
        return $this->scenarioSlug;
    }

    /**
     * @return array<string, mixed>
     */
    public function getNodesConfig(): array
    {
        return $this->nodesConfig;
    }

    /**
     * @param array<string, mixed> $nodesConfig
     */
    public function setNodesConfig(array $nodesConfig): self
    {
        $this->nodesConfig = $nodesConfig;
        $this->updatedAt = new DateTimeImmutable();
        return $this;
    }

    /**
     * @return array<string, mixed>
     */
    public function getConnectionsConfig(): array
    {
        return $this->connectionsConfig;
    }

    /**
     * @param array<string, mixed> $connectionsConfig
     */
    public function setConnectionsConfig(array $connectionsConfig): self
    {
        $this->connectionsConfig = $connectionsConfig;
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

    public function getUpdatedAt(): DateTimeImmutable
    {
        return $this->updatedAt;
    }
}
