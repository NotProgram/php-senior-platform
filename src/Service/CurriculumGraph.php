<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Single source of truth for the curriculum dependency graph.
 *
 * The curriculum is a directed acyclic graph, not a single chain: Git, SQL,
 * Linux and the engineering fundamentals are independent entry points that
 * converge later on Symfony, architecture and delivery. Keeping the graph here
 * (instead of inside the access-control engine) lets the roadmap, the learning
 * paths and the recommendation engine read the exact same topology.
 */
final class CurriculumGraph
{
    /**
     * Lessons that need no prior work: the four roots of the graph plus the
     * reference material, which must always be reachable.
     *
     * @var list<string>
     */
    private const ENTRY_POINTS = [
        'se-sdlc-requirements',
        'git-fundamentals-plumbing',
        'php-syntax-types-variables',
        'sql-indexing-explain',
        'devops-linux-cli-internals',
        'resources-php-rfcs',
    ];

    /**
     * lessonSlug => prerequisite lessonSlugs (all must be completed).
     *
     * Edges are deliberately few: one edge per real dependency. Anything that
     * can be learnt in parallel is left unconstrained so the learner always has
     * several open fronts instead of one forced sequence.
     *
     * @var array<string, list<string>>
     */
    private const PREREQUISITES = [
        // --- Roots -----------------------------------------------------------
        'se-sdlc-requirements' => [],
        'git-fundamentals-plumbing' => [],
        'php-syntax-types-variables' => [],
        'sql-indexing-explain' => [],
        'devops-linux-cli-internals' => [],
        'resources-php-rfcs' => [],

        // --- Software engineering -------------------------------------------
        'se-clean-code-quality' => ['se-sdlc-requirements'],
        'se-solid-principles' => ['se-clean-code-quality'],
        'se-refactoring-code-smells' => ['se-solid-principles'],

        // --- Git -------------------------------------------------------------
        'git-branching-strategies' => ['git-fundamentals-plumbing'],
        'git-pr-code-review' => ['git-branching-strategies'],
        'git-github-collaboration' => ['git-pr-code-review'],

        // --- PHP foundations -------------------------------------------------
        'php-control-flow-functions' => ['php-syntax-types-variables'],
        'php-arrays-data' => ['php-control-flow-functions'],
        'php-oop-foundations' => ['php-arrays-data'],

        // --- PHP runtime -----------------------------------------------------
        'php-request-lifecycle' => ['php-oop-foundations'],
        'php-types-memory' => ['php-request-lifecycle'],
        'php-namespaces-autoloading' => ['php-types-memory'],
        'php-opcache-jit' => ['php-types-memory'],
        'php-error-handling-exceptions' => ['php-namespaces-autoloading'],
        'php-modern-features-84' => ['php-error-handling-exceptions'],

        // --- Object design ---------------------------------------------------
        'poo-encapsulation-invariants' => ['php-modern-features-84'],
        'poo-composition-over-inheritance' => ['poo-encapsulation-invariants', 'se-solid-principles'],
        'poo-value-objects-dtos' => ['poo-encapsulation-invariants'],
        'poo-enums-state-machines' => ['poo-value-objects-dtos'],

        // --- Symfony core ----------------------------------------------------
        'symfony-architecture-controllers' => ['php-request-lifecycle', 'poo-encapsulation-invariants'],
        'symfony-autowiring-services' => ['symfony-architecture-controllers'],
        'symfony-requests-validation' => ['symfony-autowiring-services'],
        'symfony-http-kernel-lifecycle' => ['symfony-requests-validation'],
        'symfony-service-container' => ['symfony-http-kernel-lifecycle'],
        'symfony-event-dispatcher' => ['symfony-service-container'],
        'symfony-routing-controllers' => ['symfony-service-container'],

        // --- Twig ------------------------------------------------------------
        'twig-clean-separation' => ['symfony-routing-controllers'],
        'twig-inheritance-components' => ['twig-clean-separation'],
        'twig-escaping-security' => ['twig-inheritance-components'],

        // --- Relational data -------------------------------------------------
        'sql-transactions-isolation' => ['sql-indexing-explain'],
        'doctrine-unit-of-work' => ['sql-transactions-isolation', 'symfony-service-container'],
        'doctrine-n-plus-one-optimization' => ['doctrine-unit-of-work', 'sql-indexing-explain'],

        // --- APIs ------------------------------------------------------------
        'apis-rest-architecture' => ['symfony-routing-controllers', 'poo-value-objects-dtos'],
        'apis-rate-limiting-auth' => ['apis-rest-architecture'],

        // --- Testing ---------------------------------------------------------
        'testing-fundamentals-pyramid' => ['php-modern-features-84'],
        'testing-phpunit-mastery' => ['testing-fundamentals-pyramid'],
        'testing-unit-vs-integration' => ['testing-phpunit-mastery'],
        'testing-symfony-functional' => ['testing-unit-vs-integration', 'symfony-routing-controllers'],
        'testing-tdd-pragmatic' => ['testing-unit-vs-integration', 'se-refactoring-code-smells'],

        // --- Design patterns -------------------------------------------------
        'patterns-factory-strategy' => ['se-solid-principles', 'poo-composition-over-inheritance'],
        'patterns-decorator-proxy' => ['patterns-factory-strategy', 'symfony-service-container'],

        // --- Architecture ----------------------------------------------------
        'arch-patterns-comparison' => ['patterns-factory-strategy'],
        'arch-hexagonal-clean' => ['arch-patterns-comparison', 'testing-unit-vs-integration'],
        'arch-microservices-tradeoffs' => ['arch-hexagonal-clean'],
        'arch-pragmatic-ddd' => ['arch-hexagonal-clean', 'poo-enums-state-machines'],

        // --- System design ---------------------------------------------------
        'system-design-canvas' => ['arch-patterns-comparison'],
        'system-design-high-throughput' => ['system-design-canvas', 'perf-redis-caching-queues'],

        // --- Security --------------------------------------------------------
        'security-voters-authorization' => ['symfony-routing-controllers'],
        'security-owasp-mitigation' => ['security-voters-authorization', 'twig-escaping-security'],

        // --- Performance -----------------------------------------------------
        'perf-profiling-blackfire' => ['doctrine-n-plus-one-optimization', 'php-opcache-jit'],
        'perf-redis-caching-queues' => ['perf-profiling-blackfire'],

        // --- Delivery --------------------------------------------------------
        'devops-docker-fpm-nginx' => ['devops-linux-cli-internals', 'php-request-lifecycle'],
        'devops-ci-cd-github-actions' => ['devops-docker-fpm-nginx', 'git-pr-code-review', 'testing-phpunit-mastery'],

        // --- Professional practice -------------------------------------------
        'prof-team-communication' => ['git-github-collaboration', 'se-sdlc-requirements'],
        'prof-adr-technical-decisions' => ['arch-patterns-comparison', 'prof-team-communication'],
        'prof-failure-engineering' => ['devops-ci-cd-github-actions'],
        'prof-incident-management-logs' => ['prof-failure-engineering'],

        // --- Applied projects & evaluation -----------------------------------
        'project-01-senior-crud' => ['apis-rest-architecture', 'testing-phpunit-mastery', 'doctrine-unit-of-work'],
        'project-07-capstone-distributed' => ['project-01-senior-crud', 'arch-pragmatic-ddd', 'devops-ci-cd-github-actions'],
        'eval-senior-code-review' => ['se-refactoring-code-smells', 'git-pr-code-review', 'arch-hexagonal-clean'],
    ];

    /**
     * Module a lesson belongs to, derived from its slug prefix.
     *
     * @var array<string, string>
     */
    private const MODULE_BY_PREFIX = [
        'se-' => 'software-engineering',
        'git-' => 'git',
        'php-' => 'php-fundamentals',
        'poo-' => 'poo',
        'symfony-' => 'symfony',
        'twig-' => 'twig',
        'sql-' => 'databases',
        'doctrine-' => 'doctrine',
        'apis-' => 'apis',
        'testing-' => 'testing',
        'patterns-' => 'design-patterns',
        'system-design-' => 'system-design',
        'arch-' => 'architecture',
        'security-' => 'security',
        'perf-' => 'performance',
        'devops-' => 'devops',
        'prof-' => 'professional-developer',
        'project-' => 'projects',
        'eval-' => 'evaluations',
        'resources-' => 'resources',
    ];

    /**
     * Cached reverse index: prerequisite => lessons it unlocks.
     *
     * @var array<string, list<string>>|null
     */
    private ?array $dependents = null;

    /**
     * @return array<string, list<string>>
     */
    public function prerequisites(): array
    {
        return self::PREREQUISITES;
    }

    /**
     * @return list<string>
     */
    public function prerequisitesFor(string $lessonSlug): array
    {
        return self::PREREQUISITES[$lessonSlug] ?? [];
    }

    public function isKnown(string $lessonSlug): bool
    {
        return isset(self::PREREQUISITES[$lessonSlug]);
    }

    public function isEntryPoint(string $lessonSlug): bool
    {
        return in_array($lessonSlug, self::ENTRY_POINTS, true);
    }

    /**
     * @return list<string>
     */
    public function entryPoints(): array
    {
        return self::ENTRY_POINTS;
    }

    /**
     * Lessons that become reachable once the given lesson is completed.
     *
     * @return list<string>
     */
    public function dependentsOf(string $lessonSlug): array
    {
        if ($this->dependents === null) {
            $index = [];
            foreach (self::PREREQUISITES as $slug => $prereqs) {
                foreach ($prereqs as $prereq) {
                    $index[$prereq][] = $slug;
                }
            }
            $this->dependents = $index;
        }

        return $this->dependents[$lessonSlug] ?? [];
    }

    public function moduleOf(string $lessonSlug): string
    {
        // Longest prefix wins so 'system-design-' is not swallowed by 'se-'.
        $best = 'php-fundamentals';
        $bestLength = 0;

        foreach (self::MODULE_BY_PREFIX as $prefix => $module) {
            if (str_starts_with($lessonSlug, $prefix) && strlen($prefix) > $bestLength) {
                $best = $module;
                $bestLength = strlen($prefix);
            }
        }

        return $best;
    }

    /**
     * Module-level dependency graph, aggregated from the lesson edges.
     * Used to draw the roadmap map without hand-maintaining a second graph.
     *
     * @return array<string, list<string>>
     */
    public function moduleDependencies(): array
    {
        $edges = [];

        foreach (self::PREREQUISITES as $slug => $prereqs) {
            $module = $this->moduleOf($slug);
            foreach ($prereqs as $prereq) {
                $parent = $this->moduleOf($prereq);
                if ($parent === $module) {
                    continue;
                }
                $edges[$module][$parent] = true;
            }
        }

        $result = [];
        foreach ($edges as $module => $parents) {
            $result[$module] = array_keys($parents);
        }

        return $result;
    }
}
