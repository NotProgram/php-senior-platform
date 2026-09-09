<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\User;
use App\Entity\UserProgress;
use App\Enum\ProgressStatus;
use App\Repository\UserProgressRepository;
use Doctrine\ORM\EntityManagerInterface;

class PrerequisiteEngine
{
    /**
     * Dependency map: lessonSlug => array of prerequisite lessonSlugs
     * @var array<string, list<string>>
     */
    private const PREREQUISITE_MAP = [
        // PHP Moderno Track
        'php-request-lifecycle' => [],
        'php-types-memory' => ['php-request-lifecycle'],
        'php-opcache-jit' => ['php-types-memory'],
        'php-namespaces-autoloading' => ['php-opcache-jit'],
        'php-error-handling-exceptions' => ['php-namespaces-autoloading'],
        'php-modern-features-84' => ['php-error-handling-exceptions'],

        // POO Track
        'poo-encapsulation-invariants' => ['php-modern-features-84'],
        'poo-composition-over-inheritance' => ['poo-encapsulation-invariants'],
        'poo-value-objects-dtos' => ['poo-composition-over-inheritance'],
        'poo-enums-state-machines' => ['poo-value-objects-dtos'],

        // Symfony Track
        'symfony-http-kernel-lifecycle' => ['poo-enums-state-machines'],
        'symfony-service-container' => ['symfony-http-kernel-lifecycle'],
        'symfony-event-dispatcher' => ['symfony-service-container'],
        'symfony-routing-controllers' => ['symfony-event-dispatcher'],

        // Twig Track
        'twig-clean-separation' => ['symfony-routing-controllers'],
        'twig-inheritance-components' => ['twig-clean-separation'],
        'twig-escaping-security' => ['twig-inheritance-components'],

        // Databases & SQL
        'sql-indexing-explain' => ['twig-escaping-security'],
        'sql-transactions-isolation' => ['sql-indexing-explain'],

        // Doctrine ORM Track
        'doctrine-unit-of-work' => ['sql-transactions-isolation'],
        'doctrine-n-plus-one-optimization' => ['doctrine-unit-of-work'],

        // APIs RESTful
        'apis-rest-architecture' => ['doctrine-n-plus-one-optimization'],
        'apis-rate-limiting-auth' => ['apis-rest-architecture'],

        // Testing & TDD
        'testing-unit-vs-integration' => ['apis-rate-limiting-auth'],
        'testing-tdd-pragmatic' => ['testing-unit-vs-integration'],

        // Design Patterns
        'patterns-factory-strategy' => ['testing-tdd-pragmatic'],
        'patterns-decorator-proxy' => ['patterns-factory-strategy'],

        // Architecture & System Design
        'arch-hexagonal-clean' => ['patterns-decorator-proxy'],
        'arch-pragmatic-ddd' => ['arch-hexagonal-clean'],
        'system-design-canvas' => ['arch-pragmatic-ddd'],
        'system-design-high-throughput' => ['system-design-canvas'],

        // Defensive Security
        'security-voters-authorization' => ['system-design-high-throughput'],
        'security-owasp-mitigation' => ['security-voters-authorization'],

        // Performance & Caching
        'perf-profiling-blackfire' => ['security-owasp-mitigation'],
        'perf-redis-caching-queues' => ['perf-profiling-blackfire'],

        // DevOps & Infrastructure
        'devops-docker-fpm-nginx' => ['perf-redis-caching-queues'],
        'devops-ci-cd-github-actions' => ['devops-docker-fpm-nginx'],

        // Guided Projects
        'project-01-senior-crud' => ['devops-ci-cd-github-actions'],
        'project-07-capstone-distributed' => ['project-01-senior-crud'],

        // Evaluations
        'eval-senior-code-review' => ['project-07-capstone-distributed'],

        // Reference Resources
        'resources-php-rfcs' => ['php-request-lifecycle'],
    ];

    public function __construct(
        private readonly UserProgressRepository $progressRepository,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Resolves the current status of a specific lesson for a user.
     */
    public function resolveLessonStatus(User $user, string $lessonSlug): ProgressStatus
    {
        $existing = $this->progressRepository->findProgress($user, $lessonSlug);
        if ($existing !== null) {
            return ProgressStatus::tryFrom($existing->getStatus()) ?? ProgressStatus::Available;
        }

        // Only the foundational entry point is available unconditionally
        if ($lessonSlug === 'php-request-lifecycle') {
            return ProgressStatus::Available;
        }

        // Unconfigured or non-existent lessons remain locked
        if (!isset(self::PREREQUISITE_MAP[$lessonSlug])) {
            return ProgressStatus::Locked;
        }

        $prereqs = self::PREREQUISITE_MAP[$lessonSlug];
        if (empty($prereqs)) {
            return ProgressStatus::Available;
        }

        foreach ($prereqs as $prereqSlug) {
            $prereqProgress = $this->progressRepository->findProgress($user, $prereqSlug);
            if ($prereqProgress === null) {
                return ProgressStatus::Locked;
            }

            $status = $prereqProgress->getStatus();
            if ($status !== UserProgress::STATUS_COMPLETED && $status !== UserProgress::STATUS_MASTERED) {
                return ProgressStatus::Locked;
            }
        }

        return ProgressStatus::Available;
    }

    /**
     * Checks if a user can access a lesson, returning validation result and missing prerequisite if locked.
     *
     * @return array{allowed: bool, status: ProgressStatus, missingPrerequisite: ?string}
     */
    public function canAccessLesson(User $user, string $lessonSlug): array
    {
        $status = $this->resolveLessonStatus($user, $lessonSlug);

        if ($status === ProgressStatus::Locked) {
            $prereqs = self::PREREQUISITE_MAP[$lessonSlug] ?? [];
            $missing = null;
            foreach ($prereqs as $prereqSlug) {
                $prereqProgress = $this->progressRepository->findProgress($user, $prereqSlug);
                if ($prereqProgress === null || !in_array($prereqProgress->getStatus(), [UserProgress::STATUS_COMPLETED, UserProgress::STATUS_MASTERED], true)) {
                    $missing = $prereqSlug;
                    break;
                }
            }

            return [
                'allowed' => false,
                'status' => ProgressStatus::Locked,
                'missingPrerequisite' => $missing,
            ];
        }

        // If the lesson is available and the user accesses it, transition to IN_PROGRESS
        $record = $this->progressRepository->findProgress($user, $lessonSlug);
        if ($record === null) {
            $record = new UserProgress($user, $this->resolveModuleSlug($lessonSlug), $lessonSlug, ProgressStatus::InProgress->value);
            $this->entityManager->persist($record);
            $this->entityManager->flush();
        } elseif ($record->getStatus() === ProgressStatus::Available->value) {
            $record->setStatus(ProgressStatus::InProgress->value);
            $this->entityManager->flush();
        }

        return [
            'allowed' => true,
            'status' => ProgressStatus::tryFrom($record->getStatus()) ?? ProgressStatus::InProgress,
            'missingPrerequisite' => null,
        ];
    }

    /**
     * Resolves the progress status for all lessons in the curriculum.
     *
     * @return array<string, ProgressStatus> indexed by lessonSlug
     */
    public function resolveFullStatusMap(User $user, array $sections): array
    {
        $statusMap = [];
        foreach ($sections as $section) {
            if (isset($section['lessons'])) {
                foreach ($section['lessons'] as $lesson) {
                    $statusMap[$lesson['slug']] = $this->resolveLessonStatus($user, $lesson['slug']);
                }
            }
        }

        return $statusMap;
    }

    private function resolveModuleSlug(string $lessonSlug): string
    {
        if (str_starts_with($lessonSlug, 'poo-')) {
            return 'poo';
        }
        if (str_starts_with($lessonSlug, 'symfony-')) {
            return 'symfony';
        }
        if (str_starts_with($lessonSlug, 'twig-')) {
            return 'twig';
        }
        if (str_starts_with($lessonSlug, 'sql-')) {
            return 'databases';
        }
        if (str_starts_with($lessonSlug, 'doctrine-')) {
            return 'doctrine';
        }
        if (str_starts_with($lessonSlug, 'arch-') || str_starts_with($lessonSlug, 'system-design-')) {
            return 'architecture';
        }

        return 'php-fundamentals';
    }
}
