<?php

declare(strict_types=1);

namespace App\Service;

use App\Entity\ExerciseAttempt;
use App\Entity\User;
use App\Enum\ProgressStatus;
use Doctrine\ORM\EntityManagerInterface;

class ExerciseEvaluatorService
{
    public function __construct(
        private readonly LearningProgressService $learningProgress,
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * @return array{passed: bool, feedback: string, hints: list<string>, xp_awarded: int}
     */
    public function evaluateCode(User $user, string $lessonSlug, string $submittedCode): array
    {
        $code = trim($submittedCode);

        // Basic sanity checks
        if ($code === '') {
            return [
                'passed' => false,
                'feedback' => 'El código enviado está vacío.',
                'hints' => ['Escribe la solución en el editor antes de enviar.'],
                'xp_awarded' => 0,
            ];
        }

        $hints = [];
        $passed = true;

        // 1. Strict types check (Senior requirement)
        if (!str_contains($code, 'declare(strict_types=1);')) {
            $passed = false;
            $hints[] = 'Falta declarar tipado estricto al inicio: declare(strict_types=1);';
        }

        // 2. Dangerous practices check
        if (str_contains($code, 'eval(') || str_contains($code, '$$')) {
            $passed = false;
            $hints[] = 'Código rechazado: No utilices eval() ni variables dinámicas ($$). Un desarrollador Senior evita estas vulnerabilidades.';
        }

        // 3. Lesson-specific validation rules
        match ($lessonSlug) {
            // Fundamentos de PHP
            'php-syntax-types-variables' => $this->validatePhpSyntaxTypesVariables($code, $passed, $hints),
            'php-control-flow-functions' => $this->validatePhpControlFlowFunctions($code, $passed, $hints),
            'php-arrays-data' => $this->validatePhpArraysData($code, $passed, $hints),
            'php-oop-foundations' => $this->validatePhpOopFoundations($code, $passed, $hints),
            'php-request-lifecycle' => $this->validateRequestLifecycle($code, $passed, $hints),
            'php-types-memory' => $this->validateTypesMemory($code, $passed, $hints),
            'php-opcache-jit' => $this->validateOpCacheJit($code, $passed, $hints),
            'php-namespaces-autoloading' => $this->validateAutoloading($code, $passed, $hints),
            'php-error-handling-exceptions' => $this->validateErrorHandling($code, $passed, $hints),
            'php-modern-features-84' => $this->validateModernPhp84($code, $passed, $hints),
            // POO & Modelado Avanzado
            'poo-encapsulation-invariants' => $this->validateEncapsulationInvariants($code, $passed, $hints),
            'poo-composition-over-inheritance' => $this->validateCompositionOverInheritance($code, $passed, $hints),
            'poo-value-objects-dtos' => $this->validateValueObjectsDtos($code, $passed, $hints),
            'poo-enums-state-machines' => $this->validateEnumsStateMachines($code, $passed, $hints),
            // Symfony Framework Internals
            'symfony-http-kernel-lifecycle' => $this->validateSymfonyHttpKernel($code, $passed, $hints),
            'symfony-service-container' => $this->validateSymfonyServiceContainer($code, $passed, $hints),
            'symfony-event-dispatcher' => $this->validateSymfonyEventDispatcher($code, $passed, $hints),
            'symfony-routing-controllers' => $this->validateSymfonyRoutingControllers($code, $passed, $hints),
            // Twig Template Engine
            'twig-clean-separation' => $this->validateTwigCleanSeparation($code, $passed, $hints),
            'twig-inheritance-components' => $this->validateTwigInheritanceComponents($code, $passed, $hints),
            'twig-escaping-security' => $this->validateTwigEscapingSecurity($code, $passed, $hints),
            // Bases de Datos & SQL
            'sql-indexing-explain' => $this->validateSqlIndexingExplain($code, $passed, $hints),
            'sql-transactions-isolation' => $this->validateSqlTransactionsIsolation($code, $passed, $hints),
            // Doctrine ORM Track
            'doctrine-unit-of-work' => $this->validateDoctrineUnitOfWork($code, $passed, $hints),
            'doctrine-n-plus-one-optimization' => $this->validateDoctrineNPlusOneOptimization($code, $passed, $hints),
            // APIs RESTful
            'apis-rest-architecture' => $this->validateApisRestArchitecture($code, $passed, $hints),
            'apis-rate-limiting-auth' => $this->validateApisRateLimitingAuth($code, $passed, $hints),
            // Testing & TDD
            'testing-fundamentals-pyramid' => $this->validateTestingFundamentalsPyramid($code, $passed, $hints),
            'testing-phpunit-mastery' => $this->validateTestingPhpUnitMastery($code, $passed, $hints),
            'testing-unit-vs-integration' => $this->validateTestingUnitVsIntegration($code, $passed, $hints),
            'testing-symfony-functional' => $this->validateTestingSymfonyFunctional($code, $passed, $hints),
            'testing-tdd-pragmatic' => $this->validateTestingTddPragmatic($code, $passed, $hints),
            // Design Patterns
            'patterns-factory-strategy' => $this->validatePatternsFactoryStrategy($code, $passed, $hints),
            'patterns-decorator-proxy' => $this->validatePatternsDecoratorProxy($code, $passed, $hints),
            // Architecture & DDD
            'arch-patterns-comparison' => $this->validateArchPatternsComparison($code, $passed, $hints),
            'arch-hexagonal-clean' => $this->validateArchHexagonalClean($code, $passed, $hints),
            'arch-microservices-tradeoffs' => $this->validateArchMicroservicesTradeoffs($code, $passed, $hints),
            'arch-pragmatic-ddd' => $this->validateArchPragmaticDdd($code, $passed, $hints),
            // System Design Lab
            'system-design-canvas' => $this->validateSystemDesignCanvas($code, $passed, $hints),
            'system-design-high-throughput' => $this->validateSystemDesignHighThroughput($code, $passed, $hints),
            // Security Defensiva
            'security-voters-authorization' => $this->validateSecurityVotersAuthorization($code, $passed, $hints),
            'security-owasp-mitigation' => $this->validateSecurityOwaspMitigation($code, $passed, $hints),
            // Performance & Caching
            'perf-profiling-blackfire' => $this->validatePerfProfilingBlackfire($code, $passed, $hints),
            'perf-redis-caching-queues' => $this->validatePerfRedisCachingQueues($code, $passed, $hints),
            // DevOps & Contenedores
            'devops-linux-cli-internals' => $this->validateDevopsLinuxCliInternals($code, $passed, $hints),
            'devops-docker-fpm-nginx' => $this->validateDevopsDockerFpmNginx($code, $passed, $hints),
            'devops-ci-cd-github-actions' => $this->validateDevopsCiCdGithubActions($code, $passed, $hints),
            // Proyectos Guiados
            'project-01-senior-crud' => $this->validateProject01SeniorCrud($code, $passed, $hints),
            'project-07-capstone-distributed' => $this->validateProject07CapstoneDistributed($code, $passed, $hints),
            // Evaluaciones & Retos
            'eval-senior-code-review' => $this->validateEvalSeniorCodeReview($code, $passed, $hints),
            // Recursos & RFCs
            'resources-php-rfcs' => $this->validateResourcesPhpRfcs($code, $passed, $hints),
            // Nuevos Módulos de Ingeniería, Git, Testing, Arquitectura y Flujo Profesional
            'se-sdlc-requirements' => $this->validateSeSdlcRequirements($code, $passed, $hints),
            'se-clean-code-quality' => $this->validateSeCleanCodeQuality($code, $passed, $hints),
            'se-solid-principles' => $this->validateSeSolidPrinciples($code, $passed, $hints),
            'se-refactoring-code-smells' => $this->validateSeRefactoringCodeSmells($code, $passed, $hints),
            'git-fundamentals-plumbing' => $this->validateGitPlumbing($code, $passed, $hints),
            'git-branching-strategies' => $this->validateGitBranchingStrategies($code, $passed, $hints),
            'git-pr-code-review' => $this->validateGitPrCodeReview($code, $passed, $hints),
            'git-github-collaboration' => $this->validateGitCollaboration($code, $passed, $hints),
            'prof-team-communication' => $this->validateProfTeamCommunication($code, $passed, $hints),
            'prof-adr-technical-decisions' => $this->validateProfAdrTechnicalDecisions($code, $passed, $hints),
            'prof-failure-engineering' => $this->validateProfFailureEngineering($code, $passed, $hints),
            'prof-failure-engineering-postmortems' => $this->validateProfFailureEngineering($code, $passed, $hints),
            'prof-incident-management-logs' => $this->validateProfIncidentManagementLogs($code, $passed, $hints),
            default => null,
        };

        $feedback = $passed
            ? '¡Excelente trabajo! La solución respeta las restricciones arquitectónicas y los estándares de calidad Senior.'
            : 'Tu código requiere ajustes para cumplir con los estándares de nivel Senior.';

        // Persist attempt in database
        $attempt = new ExerciseAttempt($user, $lessonSlug, $code, $passed, $feedback);
        $this->entityManager->persist($attempt);

        // Passing once completes the lesson; passing again after completion masters it
        $xpAwarded = 0;
        if ($passed) {
            $progress = $this->learningProgress->findOrCreateProgress($user, $lessonSlug);
            $target = $progress->getStatus() === ProgressStatus::Completed->value
                ? ProgressStatus::Mastered
                : ProgressStatus::Completed;
            $xpAwarded = $this->learningProgress->advance($user, $progress, $target);
        }

        $this->entityManager->flush();

        return [
            'passed' => $passed,
            'feedback' => $feedback,
            'hints' => $hints,
            'xp_awarded' => $xpAwarded,
        ];
    }

    private function validateRequestLifecycle(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'isFastCgi') || !str_contains($code, 'getExecutionTimeMicroseconds')) {
            $passed = false;
            $hints[] = 'La solución debe implementar los métodos isFastCgi() y getExecutionTimeMicroseconds().';
        }
        if (!str_contains($code, 'microtime')) {
            $hints[] = 'Sugerencia Senior: Utiliza microtime(true) para obtener la marca de tiempo en coma flotante de alta precisión.';
        }
    }

    private function validateTypesMemory(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'yield')) {
            $passed = false;
            $hints[] = 'El generador debe utilizar la palabra clave "yield" para producir resultados en memoria O(1).';
        }
        if (!str_contains($code, 'chunkStream')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método chunkStream(array $numbers, int $chunkSize): Generator.';
        }
    }

    private function validateOpCacheJit(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'calculateHealth')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateHealth().';
        }
        if (!str_contains($code, 'hits') || !str_contains($code, 'misses')) {
            $passed = false;
            $hints[] = 'Debe calcular el ratio de aciertos a partir de las estadísticas hits y misses de OpCache.';
        }
    }

    private function validateAutoloading(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'resolveFilePath')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método resolveFilePath(string $className): ?string.';
        }
        if (!str_contains($code, '.php')) {
            $passed = false;
            $hints[] = 'La ruta del archivo debe terminar con la extensión .php conforme al estándar PSR-4.';
        }
    }

    private function validateErrorHandling(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'DomainException') && !str_contains($code, 'Exception')) {
            $passed = false;
            $hints[] = 'La excepción debe heredar de DomainException o una subclase de Exception.';
        }
        if (!str_contains($code, 'currentBalance') || !str_contains($code, 'requiredAmount')) {
            $passed = false;
            $hints[] = 'La excepción debe encapsular las propiedades currentBalance y requiredAmount para auditoría.';
        }
    }

    private function validateModernPhp84(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'BankAccount')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase BankAccount.';
        }
        if (!str_contains($code, 'balance') || !str_contains($code, 'deposit')) {
            $passed = false;
            $hints[] = 'La clase debe contener la propiedad balance y el método deposit().';
        }
    }

    private function validateEncapsulationInvariants(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class BankAccount')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase BankAccount.';
        }
        if (!str_contains($code, 'function open(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el constructor nombrado estático open() para encapsular la creación.';
        }
        if (!str_contains($code, 'function deposit(') || !str_contains($code, 'function withdraw(')) {
            $passed = false;
            $hints[] = 'La clase debe exponer métodos de negocio deposit() y withdraw() en lugar de mutaciones genéricas.';
        }
        if (str_contains($code, 'function setBalance(') || str_contains($code, 'setBalance')) {
            $passed = false;
            $hints[] = 'Violación de Encapsulación: No expongas un setter público setBalance(). Las mutaciones deben realizarse a través de métodos de intención semántica.';
        }
        if (!str_contains($code, 'DomainException') && !str_contains($code, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una DomainException cuando una operación viole los fondos disponibles considerando el sobregiro.';
        }
    }

    private function validateCompositionOverInheritance(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'interface DiscountStrategyInterface') && !str_contains($code, 'DiscountStrategyInterface')) {
            $passed = false;
            $hints[] = 'Debe definirse y utilizarse la interfaz DiscountStrategyInterface.';
        }
        if (!str_contains($code, 'class PricingEngine')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase PricingEngine.';
        }
        if (str_contains($code, 'class PricingEngine extends')) {
            $passed = false;
            $hints[] = 'Violación de Composición: PricingEngine no debe heredar de ninguna clase base mediante "extends". Inyecta la estrategia a través del constructor.';
        }
        if (!str_contains($code, 'calculateFinalPrice')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateFinalPrice(int $subtotalCents): int.';
        }
    }

    private function validateValueObjectsDtos(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'readonly class Money') && !str_contains($code, 'class Money')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase Money (preferiblemente "readonly class Money" para inmutabilidad estricta).';
        }
        if (!str_contains($code, 'cents') || !str_contains($code, 'currency')) {
            $passed = false;
            $hints[] = 'El Value Object Money debe encapsular centavos enteros ($cents) y divisa ISO ($currency).';
        }
        if (!str_contains($code, 'function add(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método add(self $other): self que retorne una nueva instancia inmutable.';
        }
        if (!str_contains($code, 'function equals(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método equals(self $other): bool para igualdad estructural.';
        }
        if (!str_contains($code, 'InvalidArgumentException') && !str_contains($code, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una excepción si se intenta sumar dinero con divisas diferentes.';
        }
    }

    private function validateEnumsStateMachines(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'enum OrderStatus: string')) {
            $passed = false;
            $hints[] = 'Debe declararse el Backed Enum: enum OrderStatus: string.';
        }
        if (!str_contains($code, 'Draft') || !str_contains($code, 'Delivered') || !str_contains($code, 'Cancelled')) {
            $passed = false;
            $hints[] = 'El enum debe incluir los casos de estado del pedido: Draft, Paid, Shipped, Delivered, Cancelled.';
        }
        if (!str_contains($code, 'canTransitionTo')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método canTransitionTo(self $target): bool.';
        }
        if (!str_contains($code, 'isTerminal')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método isTerminal(): bool.';
        }
        if (!str_contains($code, 'match')) {
            $hints[] = 'Sugerencia Senior: Utiliza la expresión match ($this) para garantizar verificación exhaustiva de estados en tiempo de compilación.';
        }
    }

    private function validateSymfonyHttpKernel(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'EventSubscriberInterface')) {
            $passed = false;
            $hints[] = 'Debe implementarse EventSubscriberInterface para autoconfigurar el suscriptor en el HttpKernel.';
        }
        if (!str_contains($clean, 'KernelEvents::RESPONSE') && !str_contains($clean, 'kernel.response')) {
            $passed = false;
            $hints[] = 'Debe suscribirse al evento KernelEvents::RESPONSE (o "kernel.response").';
        }
        if (!str_contains($clean, 'onKernelResponse')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método onKernelResponse(ResponseEvent $event): void.';
        }
        if (!str_contains($clean, 'X-Frame-Options') || !str_contains($clean, 'X-Content-Type-Options')) {
            $passed = false;
            $hints[] = 'Deben inyectarse las cabeceras de seguridad X-Frame-Options (DENY) y X-Content-Type-Options (nosniff) en la Response.';
        }
    }

    private function validateSymfonyServiceContainer(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'interface NotifierInterface') && !str_contains($clean, 'NotifierInterface')) {
            $passed = false;
            $hints[] = 'Debe definirse y utilizarse la interfaz NotifierInterface.';
        }
        if (!str_contains($clean, 'class NotificationManager')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase NotificationManager.';
        }
        if (!str_contains($clean, 'iterable $notifiers')) {
            $passed = false;
            $hints[] = 'NotificationManager debe recibir una colección de servicios tipada iterable $notifiers en el constructor.';
        }
        if (str_contains($clean, '$container->get(') || str_contains($clean, 'ContainerInterface')) {
            $passed = false;
            $hints[] = 'Violación arquitectónica: No utilices el antipatrón Service Locator ($container->get()). Inyecta los servicios etiquetados mediante iterable.';
        }
        if (!str_contains($clean, 'dispatch')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método dispatch(string $channel, string $recipient, string $message): bool.';
        }
    }

    private function validateSymfonyEventDispatcher(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'EventSubscriberInterface')) {
            $passed = false;
            $hints[] = 'Debe implementarse EventSubscriberInterface.';
        }
        if (!str_contains($clean, 'order.placed')) {
            $passed = false;
            $hints[] = 'El método getSubscribedEvents() debe suscribirse al evento "order.placed".';
        }
        if (!str_contains($clean, '100') || !str_contains($clean, '-50')) {
            $passed = false;
            $hints[] = 'Deben asignarse prioridades numéricas explícitas: 100 para la reserva de inventario y -50 para analíticas.';
        }
        if (!str_contains($clean, 'reserveInventory') || !str_contains($clean, 'trackAnalytics')) {
            $passed = false;
            $hints[] = 'Deben implementarse los métodos de recepción reserveInventory y trackAnalytics.';
        }
    }

    private function validateSymfonyRoutingControllers(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'ValueResolverInterface')) {
            $passed = false;
            $hints[] = 'El resolver debe implementar Symfony\Component\HttpKernel\Controller\ValueResolverInterface.';
        }
        if (!str_contains($clean, 'resolve')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método resolve(Request $request, ArgumentMetadata $argument): iterable.';
        }
        if (!str_contains($clean, 'apiKey') && !str_contains($clean, 'API_KEY')) {
            $passed = false;
            $hints[] = 'El resolver debe comprobar el nombre o tipo del argumento (apiKey).';
        }
        if (!str_contains($clean, 'X-Api-Key')) {
            $passed = false;
            $hints[] = 'Debe extraerse la cabecera HTTP "X-Api-Key" de la petición.';
        }
    }

    private function validateTwigCleanSeparation(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'AbstractExtension')) {
            $passed = false;
            $hints[] = 'La clase FinancialTwigExtension debe extender Twig\Extension\AbstractExtension.';
        }
        if (!str_contains($clean, 'price_cents')) {
            $passed = false;
            $hints[] = 'Debe registrarse el filtro "price_cents" en getFilters().';
        }
        if (!str_contains($clean, 'formatCents')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método formatCents(int $cents): string.';
        }
        if (!str_contains($clean, 'InvalidArgumentException') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el monto en centavos es negativo ($cents < 0).';
        }
        if (!str_contains($clean, '100')) {
            $passed = false;
            $hints[] = 'El cálculo monetario debe convertir los centavos a dólares dividiendo entre 100.';
        }
    }

    private function validateTwigInheritanceComponents(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'BadgeComponentHelper')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase BadgeComponentHelper.';
        }
        if (!str_contains($clean, 'ALLOWED_VARIANTS') && !str_contains($clean, 'primary') && !str_contains($clean, 'success')) {
            $passed = false;
            $hints[] = 'Deben validarse las variantes permitidas (primary, success, warning, danger).';
        }
        if (!str_contains($clean, 'renderBadge')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método renderBadge(string $label, string $variant = "primary"): string.';
        }
        if (!str_contains($clean, 'InvalidArgumentException') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si la variante no es válida.';
        }
        if (!str_contains($clean, 'htmlspecialchars')) {
            $passed = false;
            $hints[] = 'El texto del label debe escaparse con htmlspecialchars() para prevenir inyecciones XSS en el HTML.';
        }
    }

    private function validateTwigEscapingSecurity(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'SafeContentSanitizer')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase SafeContentSanitizer.';
        }
        if (!str_contains($clean, 'sanitize')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método sanitize(string $untrustedHtml): string.';
        }
        if (!str_contains($clean, '<script') && !str_contains($clean, 'script')) {
            $passed = false;
            $hints[] = 'Debe detectarse y rechazarse cualquier intento de inyección de etiquetas <script>.';
        }
        if (!str_contains($clean, 'javascript:')) {
            $passed = false;
            $hints[] = 'Debe detectarse y rechazarse el pseudo-protocolo "javascript:".';
        }
        if (!str_contains($clean, 'strip_tags') && !str_contains($clean, 'ALLOWED_TAGS')) {
            $passed = false;
            $hints[] = 'Debe sanitizarse el HTML permitiendo únicamente etiquetas seguras (strip_tags o allowlist).';
        }
    }

    private function validateSqlIndexingExplain(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class SargableQueryOptimizer')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase SargableQueryOptimizer.';
        }
        if (!str_contains($clean, 'optimizeYearPredicate')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método optimizeYearPredicate(string $column, int $year): string.';
        }
        if (!str_contains($clean, '1970')) {
            $passed = false;
            $hints[] = 'Debe validarse que el año sea mayor a 1970 lanzando InvalidArgumentException.';
        }
        if (!str_contains($clean, 'isOptimalAccessType')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método isOptimalAccessType(string $accessType): bool.';
        }
        if (!str_contains($clean, 'const') || !str_contains($clean, 'eq_ref') || !str_contains($clean, 'ref') || !str_contains($clean, 'range')) {
            $passed = false;
            $hints[] = 'Los tipos óptimos de EXPLAIN deben incluir "const", "eq_ref", "ref" y "range".';
        }
    }

    private function validateSqlTransactionsIsolation(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class TransactionRetryCoordinator')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase TransactionRetryCoordinator.';
        }
        if (!str_contains($clean, 'executeWithRetry')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método executeWithRetry(callable $operation, int $maxRetries = 3): mixed.';
        }
        if (!str_contains($clean, 'deadlock') && !str_contains($clean, '1213')) {
            $passed = false;
            $hints[] = 'Debe capturarse y verificarse el error de deadlock (texto "deadlock" o código de error MySQL 1213).';
        }
        if (!str_contains($clean, 'PDOException') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Deben capturarse excepciones PDOException o subclases de Exception.';
        }
    }

    private function validateDoctrineUnitOfWork(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class BatchFlushCoordinator')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase BatchFlushCoordinator.';
        }
        if (!str_contains($clean, 'processInBatches')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método processInBatches(iterable $items, callable $worker, int $batchSize = 100): int.';
        }
        if (!str_contains($clean, 'flush()') || !str_contains($clean, 'clear()')) {
            $passed = false;
            $hints[] = 'El coordinador debe invocar tanto flush() como clear() en el EntityManager para vaciar el Identity Map en los puntos de corte de lote.';
        }
        if (!str_contains($clean, 'InvalidArgumentException') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si $batchSize <= 0.';
        }
    }

    private function validateDoctrineNPlusOneOptimization(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class OptimizedDqlQueryBuilder')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase OptimizedDqlQueryBuilder.';
        }
        if (!str_contains($clean, 'buildJoinFetchDql')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string.';
        }
        if (!str_contains($clean, 'empty($joins)') && !str_contains($clean, 'count($joins)')) {
            $passed = false;
            $hints[] = 'Debe validarse si el array $joins está vacío para prevenir consultas incompletas.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el array $joins está vacío.';
        }
        if (!str_contains($clean, 'SELECT') && !str_contains($clean, 'select')) {
            $passed = false;
            $hints[] = 'La sentencia DQL construida debe contener la cláusula SELECT con los alias correspondientes.';
        }
        if (!str_contains($clean, 'INNER JOIN') && !str_contains($clean, 'JOIN')) {
            $passed = false;
            $hints[] = 'La sentencia DQL debe construir cláusulas JOIN para resolver colaboradores en una única consulta.';
        }
    }

    private function validateApisRestArchitecture(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class ProblemDetailsFactory')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase ProblemDetailsFactory.';
        }
        if (!str_contains($clean, 'createProblem')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método createProblem(int $statusCode, string $title, string $detail, ?string $type = null, array $invalidParams = []): array.';
        }
        if (!str_contains($clean, '400') || !str_contains($clean, '599')) {
            $passed = false;
            $hints[] = 'Debe validarse que el código de estado HTTP esté en el rango de errores 400 a 599.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el código de estado no corresponde a un error 4xx o 5xx.';
        }
        if (!str_contains($clean, 'about:blank')) {
            $passed = false;
            $hints[] = 'El tipo por defecto (RFC 7807) cuando $type sea null debe ser "about:blank".';
        }
        if (!str_contains($clean, 'invalid_params')) {
            $passed = false;
            $hints[] = 'Debe incorporarse la clave "invalid_params" en el array resultante cuando se proporcionen parámetros inválidos.';
        }
    }

    private function validateApisRateLimitingAuth(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class IdempotencyKeyManager')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase IdempotencyKeyManager.';
        }
        if (!str_contains($clean, 'validateKey')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validateKey(string $key): bool.';
        }
        if (!str_contains($clean, 'preg_match')) {
            $passed = false;
            $hints[] = 'Debe validarse el formato canónico UUID v4 mediante preg_match().';
        }
        if (!str_contains($clean, 'buildCachedResponse')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método buildCachedResponse(int $statusCode, array $headers, string $body): array.';
        }
        if (!str_contains($clean, 'cached_at')) {
            $passed = false;
            $hints[] = 'El array de respuesta cacheada debe incluir la marca de tiempo "cached_at".';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el código de estado HTTP es menor a 100 o mayor a 599.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateTestingUnitVsIntegration(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'calculateDiscount')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateDiscount(float $subtotal, float $discountPercent, ?string $couponCode = null): float.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe validarse que el subtotal no sea negativo y el porcentaje esté entre 0 y 100 lanzando InvalidArgumentException.';
        }
        if (!str_contains($clean, 'VIP2026')) {
            $passed = false;
            $hints[] = 'Debe evaluarse el cupón "VIP2026" para otorgar 15.0 de bonificación si el subtotal supera 100.0.';
        }
        if (!str_contains($clean, 'round') || !str_contains($clean, 'PHP_ROUND_HALF_UP')) {
            $passed = false;
            $hints[] = 'El descuento debe redondearse a 2 decimales utilizando round($val, 2, PHP_ROUND_HALF_UP).';
        }
        if (!str_contains($clean, '$subtotal') || (!str_contains($clean, '$discount > $subtotal') && !str_contains($clean, 'min('))) {
            $passed = false;
            $hints[] = 'Debe acotarse el descuento para que en ningún caso exceda el valor del subtotal.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateTestingTddPragmatic(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'calculateProratedAmount')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateProratedAmount(float $monthlyRate, int $activeDays, int $totalDaysInMonth, int $consumedUnits): float.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Deben validarse las invariantes de dominio (tarifa, días del mes entre 28 y 31, días activos y unidades no negativas) lanzando InvalidArgumentException.';
        }
        if (!str_contains($clean, '28') || !str_contains($clean, '31')) {
            $passed = false;
            $hints[] = 'Debe validarse que el total de días del mes esté comprendido entre 28 y 31.';
        }
        if (!str_contains($clean, '100') || !str_contains($clean, '500')) {
            $passed = false;
            $hints[] = 'Deben aplicarse los umbrales de consumo escalonado en 100 y 500 unidades.';
        }
        if (!str_contains($clean, '0.1') && !str_contains($clean, '0.10')) {
            $passed = false;
            $hints[] = 'Las unidades consumidas entre 101 y 500 deben tarifarse a 0.10 por unidad adicional.';
        }
        if (!str_contains($clean, '0.25')) {
            $passed = false;
            $hints[] = 'Las unidades consumidas por encima de 500 deben tarifarse a 0.25 por unidad adicional.';
        }
        if (!str_contains($clean, 'round') || !str_contains($clean, 'PHP_ROUND_HALF_UP')) {
            $passed = false;
            $hints[] = 'El total debe redondearse a 2 decimales utilizando round($total, 2, PHP_ROUND_HALF_UP).';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateTestingFundamentalsPyramid(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class TestPyramidAuditor')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase TestPyramidAuditor.';
        }
        if (!str_contains($clean, 'auditDistribution')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método auditDistribution(int $unitCount, int $integrationCount, int $e2eCount): array.';
        }
        if (!str_contains($clean, 'ICE_CREAM_CONE')) {
            $passed = false;
            $hints[] = 'Debe detectarse el antipatrón "ICE_CREAM_CONE" cuando las pruebas E2E superan el 30% o las unitarias son menores al 40%.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si los conteos son negativos o el total es cero.';
        }
        if (!str_contains($clean, 'calculateEstimatedRuntime')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateEstimatedRuntime(int $unitCount, int $integrationCount, int $e2eCount, float $unitMs = 2.0, float $integrationMs = 50.0, float $e2eMs = 3000.0): float.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateTestingSymfonyFunctional(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);
        if (!str_contains($clean, 'class ApiTestResponseAssertor')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase ApiTestResponseAssertor.';
        }
        if (!str_contains($clean, 'assertJsonResponse')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método assertJsonResponse(int $expectedStatus, int $actualStatus, string $rawBody, array $requiredKeys = []): array.';
        }
        if (!str_contains($clean, 'buildAuthenticatedHeaders')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método buildAuthenticatedHeaders(string $jwtToken): array.';
        }
        if (!str_contains($clean, 'HTTP_AUTHORIZATION') || !str_contains($clean, 'Bearer')) {
            $passed = false;
            $hints[] = 'El método buildAuthenticatedHeaders debe incluir la cabecera HTTP_AUTHORIZATION con el prefijo Bearer.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si los códigos HTTP no coinciden o son inválidos.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validatePatternsFactoryStrategy(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'registerStrategy')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método registerStrategy(DiscountStrategyInterface $strategy): void.';
        }
        if (!str_contains($clean, 'resolve')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método resolve(string $identifier): DiscountStrategyInterface.';
        }
        if (!str_contains($clean, 'execute')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método execute(string $identifier, float $amount, float $rate): float.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Deben validarse identificadores vacíos, duplicados, estrategias inexistentes y valores negativos lanzando InvalidArgumentException.';
        }
        if (!str_contains($clean, '$amount < 0') && !str_contains($clean, '$amount < 0.0')) {
            $passed = false;
            $hints[] = 'Debe validarse que el importe $amount no sea negativo.';
        }
        if (!str_contains($clean, '$rate < 0') && !str_contains($clean, '$rate < 0.0')) {
            $passed = false;
            $hints[] = 'Debe validarse que el valor de descuento $rate no sea negativo.';
        }
        if (!str_contains($clean, 'apply(') && !str_contains($clean, '->apply')) {
            $passed = false;
            $hints[] = 'Debe delegarse el cálculo invocando el método apply() de la estrategia resuelta.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validatePatternsDecoratorProxy(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, '$this->maxRetries < 1') && !str_contains($clean, '$maxRetries < 1')) {
            $passed = false;
            $hints[] = 'Debe validarse en el constructor que maxRetries sea al menos 1 lanzando InvalidArgumentException.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si maxRetries es menor a 1.';
        }
        if (!str_contains($clean, 'TransientGatewayException')) {
            $passed = false;
            $hints[] = 'Debe capturarse la excepción de infraestructura TransientGatewayException para reintentar.';
        }
        if (!str_contains($clean, 'try') || !str_contains($clean, 'catch')) {
            $passed = false;
            $hints[] = 'Debe implementarse un bloque try-catch para interceptar las excepciones transitorias.';
        }
        if (!str_contains($clean, '$this->inner->charge') && !str_contains($clean, '->charge(')) {
            $passed = false;
            $hints[] = 'Debe delegarse la ejecución en el objeto interno decorado ($this->inner->charge).';
        }
        if (!str_contains($clean, 'while') && !str_contains($clean, 'for') && !str_contains($clean, 'do')) {
            $passed = false;
            $hints[] = 'Debe utilizarse un bucle (while o for) para gestionar el número máximo de reintentos configurado.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateArchHexagonalClean(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'filter_var') || !str_contains($clean, 'FILTER_VALIDATE_EMAIL')) {
            $passed = false;
            $hints[] = 'Debe validarse el formato del email usando filter_var($email, FILTER_VALIDATE_EMAIL).';
        }
        if (!str_contains($clean, 'strlen') || (!str_contains($clean, '< 8') && !str_contains($clean, '>= 8'))) {
            $passed = false;
            $hints[] = 'Debe validarse que la contraseña en texto plano tenga una longitud mínima de 8 caracteres.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el email o la contraseña no superan las validaciones de guardia.';
        }
        if (!str_contains($clean, 'throw new UserAlreadyExistsException') && !str_contains($clean, 'throw new \UserAlreadyExistsException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse UserAlreadyExistsException si el usuario ya se encuentra registrado.';
        }
        if (!str_contains($clean, 'findByEmail')) {
            $passed = false;
            $hints[] = 'Debe consultarse el puerto secundario userRepository->findByEmail() para verificar existencia.';
        }
        if (!str_contains($clean, 'password_hash')) {
            $passed = false;
            $hints[] = 'Debe generarse un hash criptográfico seguro de la contraseña mediante password_hash().';
        }
        if (!str_contains($clean, 'save($user)') && !str_contains($clean, 'save(')) {
            $passed = false;
            $hints[] = 'Debe persistirse el nuevo usuario a través del puerto secundario userRepository->save().';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateArchMicroservicesTradeoffs(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class ArchitectureTradeoffMatrix')) {
            $passed = false;
            $hints[] = 'Debe implementarse la clase ArchitectureTradeoffMatrix.';
        }
        if (!str_contains($clean, 'evaluateReadiness')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método evaluateReadiness(int $engineeringTeamSize, int $boundedContextsCount, bool $requiresIndependentDeployments, float $monthlyDevOpsBudgetUsd): array.';
        }
        if (!str_contains($clean, 'MODULAR_MONOLITH') || !str_contains($clean, 'MICROSERVICES')) {
            $passed = false;
            $hints[] = 'El método evaluateReadiness debe retornar "MODULAR_MONOLITH" o "MICROSERVICES" según la matriz de costos organizacionales.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una InvalidArgumentException si el tamaño del equipo <= 0, contextos <= 0 o el presupuesto es negativo.';
        }
        if (!str_contains($clean, 'calculateNetworkOverheadMs')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateArchPragmaticDdd(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, '$quantity <= 0') && !str_contains($clean, '$quantity < 1')) {
            $passed = false;
            $hints[] = 'Debe validarse en addItem() que la cantidad agregada sea mayor a 0.';
        }
        if (!str_contains($clean, '$unitPrice < 0') && !str_contains($clean, '$unitPrice < 0.0')) {
            $passed = false;
            $hints[] = 'Debe validarse en addItem() que el precio unitario no sea negativo.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si la cantidad o el precio unitario no cumplen las invariantes de dominio.';
        }
        if (!str_contains($clean, 'throw new DomainException') && !str_contains($clean, 'throw new \DomainException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse DomainException al intentar ejecutar checkout() sobre un carrito vacío.';
        }
        if (!str_contains($clean, 'removeItem')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método removeItem(string $productId): void.';
        }
        if (!str_contains($clean, 'getTotal')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método getTotal(): float calculando la suma de subtotales de los items.';
        }
        if (!str_contains($clean, 'checkout')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método checkout(): float.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateSystemDesignCanvas(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class CacheAsideManager')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase CacheAsideManager.';
        }
        if (!str_contains($clean, 'function get(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método get(string $key, int $ttlSeconds, Closure $fallback): mixed.';
        }
        if (!str_contains($clean, 'function invalidate(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método invalidate(string $key): void.';
        }
        if (!str_contains($clean, 'trim(')) {
            $passed = false;
            $hints[] = 'Debe sanitizarse la clave usando trim() antes de consultar o almacenar en caché.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si la clave está vacía o el TTL es menor o igual a 0.';
        }
        if (!str_contains($clean, '$fallback()')) {
            $passed = false;
            $hints[] = 'Debe ejecutarse la función Closure $fallback() ante un cache miss o expiración del dato.';
        }
        if (!str_contains($clean, 'microtime(true)')) {
            $passed = false;
            $hints[] = 'Debe utilizarse microtime(true) para calcular y comparar la expiración con alta precisión.';
        }
        if (!str_contains($clean, '$ttlSeconds') || !str_contains($clean, 'expires_at')) {
            $passed = false;
            $hints[] = 'Debe almacenarse el dato con su marca de tiempo de expiración (expires_at) calculada con el TTL.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateSystemDesignHighThroughput(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class SlidingWindowRateLimiter')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase SlidingWindowRateLimiter.';
        }
        if (!str_contains($clean, 'function isAllowed(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método isAllowed(string $key, int $maxRequests, int $windowSeconds): bool.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(string $key): void.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si la clave está vacía, maxRequests <= 0 o windowSeconds <= 0.';
        }
        if (!str_contains($clean, 'microtime(true)')) {
            $passed = false;
            $hints[] = 'Debe emplearse microtime(true) para medir marcas de tiempo con precisión submilisegundo.';
        }
        if (!str_contains($clean, '$windowSeconds') || (!str_contains($clean, 'count(') && !str_contains($clean, 'count ($cleanKey)'))) {
            $passed = false;
            $hints[] = 'Debe filtrarse la lista de peticiones dentro de la ventana de tiempo deslizante y compararse contra $maxRequests.';
        }
        if (!str_contains($clean, 'return true') || !str_contains($clean, 'return false')) {
            $passed = false;
            $hints[] = 'Debe retornarse true si la petición está dentro de la cuota y false si la cuota fue excedida.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateSecurityVotersAuthorization(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class InvoiceAccessVoter')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase InvoiceAccessVoter.';
        }
        if (!str_contains($clean, 'function supports(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método supports(string $attribute, mixed $subject): bool.';
        }
        if (!str_contains($clean, 'function voteOnAttribute(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool.';
        }
        if (!str_contains($clean, 'instanceof Invoice')) {
            $passed = false;
            $hints[] = 'Debe verificarse que el sujeto de autorización sea una instancia de Invoice ($subject instanceof Invoice).';
        }
        if (!str_contains($clean, '$user === null') && !str_contains($clean, '!$user')) {
            $passed = false;
            $hints[] = 'Debe aplicarse denegación por defecto si el usuario es nulo ($user === null).';
        }
        if (!str_contains($clean, 'ROLE_SUPER_ADMIN')) {
            $passed = false;
            $hints[] = 'Debe concederse acceso prioritario si el usuario posee el rol ROLE_SUPER_ADMIN.';
        }
        if (!str_contains($clean, 'ROLE_AUDITOR')) {
            $passed = false;
            $hints[] = 'Debe otorgarse permiso de lectura (INVOICE_VIEW) a los usuarios con rol ROLE_AUDITOR.';
        }
        if (!str_contains($clean, 'getOwnerId()')) {
            $passed = false;
            $hints[] = 'Debe validarse la propiedad de la factura comparando el id del usuario con getOwnerId().';
        }
        if (!str_contains($clean, 'isEditable()')) {
            $passed = false;
            $hints[] = 'Debe comprobarse si la factura admite edición invocando isEditable().';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateSecurityOwaspMitigation(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class SsrfProtectionValidator')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase SsrfProtectionValidator.';
        }
        if (!str_contains($clean, 'function validateUrl(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validateUrl(string $url): string.';
        }
        if (!str_contains($clean, 'FILTER_VALIDATE_URL')) {
            $passed = false;
            $hints[] = 'Debe validarse el formato de la URL utilizando filter_var() con FILTER_VALIDATE_URL.';
        }
        if (!str_contains($clean, 'PHP_URL_SCHEME') && !str_contains($clean, 'parse_url(')) {
            $passed = false;
            $hints[] = 'Debe extraerse e inspeccionarse el esquema de la URL mediante parse_url.';
        }
        if (!str_contains($clean, 'gethostbyname(')) {
            $passed = false;
            $hints[] = 'Debe resolverse la dirección IP del host mediante gethostbyname() para mitigar ataques de redirección SSRF.';
        }
        if (!str_contains($clean, 'FILTER_FLAG_NO_PRIV_RANGE') || !str_contains($clean, 'FILTER_FLAG_NO_RES_RANGE')) {
            $passed = false;
            $hints[] = 'Debe verificarse que la IP no sea privada ni reservada con FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE.';
        }
        if (!str_contains($clean, '127.0.0.1')) {
            $passed = false;
            $hints[] = 'Debe bloquearse explícitamente el acceso a direcciones locales de loopback (127.0.0.1).';
        }
        if (!str_contains($clean, 'throw new RuntimeException') && !str_contains($clean, 'throw new \RuntimeException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse una RuntimeException si se detecta un intento de SSRF hacia una IP no autorizada.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validatePerfProfilingBlackfire(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class ExecutionTimer')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase ExecutionTimer.';
        }
        if (!str_contains($clean, 'function start(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método start(string $checkpoint): void.';
        }
        if (!str_contains($clean, 'function stop(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método stop(string $checkpoint): array.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(): void.';
        }
        if (!str_contains($clean, 'hrtime(true)')) {
            $passed = false;
            $hints[] = 'Debe utilizarse hrtime(true) para obtener marcas de tiempo monotónicas de alta resolución en nanosegundos.';
        }
        if (!str_contains($clean, 'memory_get_usage(true)')) {
            $passed = false;
            $hints[] = 'Debe capturarse la memoria real asignada por el sistema con memory_get_usage(true).';
        }
        if (!str_contains($clean, 'memory_get_peak_usage(true)')) {
            $passed = false;
            $hints[] = 'Debe medirse el pico de memoria alcanzado mediante memory_get_peak_usage(true).';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el nombre del checkpoint está vacío.';
        }
        if (!str_contains($clean, 'throw new RuntimeException') && !str_contains($clean, 'throw new \RuntimeException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse RuntimeException si se intenta detener un checkpoint inexistente o no iniciado.';
        }
        if (!str_contains($clean, 'duration_ms') || !str_contains($clean, 'memory_delta_bytes')) {
            $passed = false;
            $hints[] = 'Debe retornarse un array asociativo con las métricas calculadas: checkpoint, duration_ms, memory_delta_bytes y peak_memory_bytes.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validatePerfRedisCachingQueues(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class IdempotentJobQueue')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase IdempotentJobQueue.';
        }
        if (!str_contains($clean, 'function enqueue(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método enqueue(string $jobId, array $payload): bool.';
        }
        if (!str_contains($clean, 'function processNext(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método processNext(Closure $processor): ?array.';
        }
        if (!str_contains($clean, 'function isProcessed(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método isProcessed(string $jobId): bool.';
        }
        if (!str_contains($clean, 'function clear(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método clear(): void.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el identificador del trabajo está vacío.';
        }
        if (!str_contains($clean, 'isProcessed(')) {
            $passed = false;
            $hints[] = 'Debe comprobarse si el trabajo ya fue procesado mediante isProcessed() para garantizar idempotencia.';
        }
        if (!str_contains($clean, 'array_shift(')) {
            $passed = false;
            $hints[] = 'Debe extraerse el primer trabajo de la cola respetando orden FIFO mediante array_shift().';
        }
        if (!str_contains($clean, '$processor(')) {
            $passed = false;
            $hints[] = 'Debe ejecutarse el callback de procesamiento $processor($job[\'payload\']) al procesar cada trabajo.';
        }
        if (!str_contains($clean, 'job_id') || !str_contains($clean, 'result') || !str_contains($clean, 'processed_at')) {
            $passed = false;
            $hints[] = 'Debe retornarse el array estructurado con las claves job_id, result y processed_at.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateDevopsLinuxCliInternals(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class LinuxProcessSecurityAuditor')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase LinuxProcessSecurityAuditor.';
        }
        if (!str_contains($clean, 'auditFilePermissions')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método auditFilePermissions(string $path, int $octalMode): array.';
        }
        if (!str_contains($clean, 'validateEnvVarName')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validateEnvVarName(string $name): bool.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si la ruta está vacía o el modo octal es negativo.';
        }
        if (!str_contains($clean, 'preg_match')) {
            $passed = false;
            $hints[] = 'Debe validarse el nombre de variable de entorno POSIX mediante preg_match().';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateDevopsDockerFpmNginx(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class DockerManifestValidator')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase DockerManifestValidator.';
        }
        if (!str_contains($clean, 'function validateDockerfile(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validateDockerfile(string $dockerfileContent): array.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(): void.';
        }
        if (!str_contains($clean, 'preg_match_all(') || !str_contains($clean, 'FROM')) {
            $passed = false;
            $hints[] = 'Debe detectarse la presencia de múltiples etapas multi-stage mediante cláusulas FROM ... AS ....';
        }
        if (!str_contains($clean, 'USER')) {
            $passed = false;
            $hints[] = 'Debe validarse la directiva USER para garantizar la ejecución como usuario no-root.';
        }
        if (!str_contains($clean, '--no-dev')) {
            $passed = false;
            $hints[] = 'Debe comprobarse la inclusión de la bandera --no-dev en las dependencias de producción.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el contenido del Dockerfile está vacío.';
        }
        if (!str_contains($clean, 'throw new RuntimeException') && !str_contains($clean, 'throw new \RuntimeException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse RuntimeException si el manifiesto no cumple las políticas de seguridad requeridas.';
        }
        if (!str_contains($clean, 'valid') || !str_contains($clean, 'stages_count') || !str_contains($clean, 'non_root_user')) {
            $passed = false;
            $hints[] = 'Debe retornarse un array estructurado con las claves valid, stages_count y non_root_user.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateDevopsCiCdGithubActions(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class CiPipelineRunner')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase CiPipelineRunner.';
        }
        if (!str_contains($clean, 'function addStep(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método addStep(string $name, Closure $check): void.';
        }
        if (!str_contains($clean, 'function run(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método run(): array.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(): void.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el nombre del paso está vacío.';
        }
        if (!str_contains($clean, 'Throwable') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe capturarse cualquier excepción (Throwable) durante la ejecución de los pasos del pipeline.';
        }
        if (!str_contains($clean, 'passed') || !str_contains($clean, 'total_steps') || !str_contains($clean, 'executed_steps') || !str_contains($clean, 'failed_step')) {
            $passed = false;
            $hints[] = 'Debe retornarse el resumen estructurado con las claves passed, total_steps, executed_steps, failed_step y error.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateProject01SeniorCrud(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class EnterpriseDtoValidator')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase EnterpriseDtoValidator.';
        }
        if (!str_contains($clean, 'function validateDto(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validateDto(object $dto): array.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(): void.';
        }
        if (!str_contains($clean, 'ReflectionClass')) {
            $passed = false;
            $hints[] = 'Debe emplearse ReflectionClass para inspeccionar las propiedades públicas del DTO.';
        }
        if (!str_contains($clean, 'getProperties(')) {
            $passed = false;
            $hints[] = 'Debe iterarse sobre las propiedades mediante getProperties().';
        }
        if (!str_contains($clean, 'is_valid') || !str_contains($clean, 'errors')) {
            $passed = false;
            $hints[] = 'Debe retornarse un array estructurado con las claves is_valid y errors.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateProject07CapstoneDistributed(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class DistributedModularBus')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase DistributedModularBus.';
        }
        if (!str_contains($clean, 'function registerModule(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método registerModule(string $moduleName, object $handler): void.';
        }
        if (!str_contains($clean, 'function dispatchCommand(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método dispatchCommand(string $moduleName, string $action, array $payload): array.';
        }
        if (!str_contains($clean, 'function hasModule(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método hasModule(string $moduleName): bool.';
        }
        if (!str_contains($clean, 'function reset(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método reset(): void.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el nombre del módulo está vacío al registrar.';
        }
        if (!str_contains($clean, 'Throwable') && !str_contains($clean, 'Exception')) {
            $passed = false;
            $hints[] = 'Debe capturarse Throwable durante el despacho dinámico del comando para aislar fallos de los módulos.';
        }
        if (!str_contains($clean, 'status') || !str_contains($clean, 'module') || !str_contains($clean, 'action')) {
            $passed = false;
            $hints[] = 'Debe retornarse el array estructurado con status, module, action y result o error.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateEvalSeniorCodeReview(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class CodeSmellDetector')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase CodeSmellDetector.';
        }
        if (!str_contains($clean, 'function detectSmells(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método detectSmells(string $phpCode): array.';
        }
        if (!str_contains($clean, 'function hasCriticalSmells(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método hasCriticalSmells(string $phpCode): bool.';
        }
        if (!str_contains($clean, 'missing_strict_types')) {
            $passed = false;
            $hints[] = 'Debe detectarse la ausencia de declare(strict_types=1); con el tipo missing_strict_types.';
        }
        if (!str_contains($clean, 'flush_in_loop')) {
            $passed = false;
            $hints[] = 'Debe detectarse la llamada a flush() dentro de bucles con el tipo flush_in_loop.';
        }
        if (!str_contains($clean, 'empty_catch')) {
            $passed = false;
            $hints[] = 'Debe detectarse bloques catch vacíos con el tipo empty_catch.';
        }
        if (!str_contains($clean, 'sql_injection_risk')) {
            $passed = false;
            $hints[] = 'Debe detectarse riesgo de inyección SQL por concatenación directa con el tipo sql_injection_risk.';
        }
        if (!str_contains($clean, 'valid') || !str_contains($clean, 'smells_count') || !str_contains($clean, 'smells')) {
            $passed = false;
            $hints[] = 'Debe retornarse el array estructurado con las claves valid, smells_count y smells.';
        }
    }

    /**
     * @param string[] $hints
     */
    private function validateResourcesPhpRfcs(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class PsrStandardRegistry')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase PsrStandardRegistry.';
        }
        if (!str_contains($clean, 'function registerPsr(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método registerPsr(int $number, string $name, string $type, string $status): void.';
        }
        if (!str_contains($clean, 'function getPsr(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método getPsr(int $number): ?array.';
        }
        if (!str_contains($clean, 'function listByType(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método listByType(string $type): array.';
        }
        if (!str_contains($clean, 'function validatePhpFeatureRfc(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método validatePhpFeatureRfc(string $featureName, string $minPhpVersion): bool.';
        }
        if (!str_contains($clean, 'function count(')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método count(): int.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException ante parámetros no válidos al registrar un estándar.';
        }
        if (!str_contains($clean, 'version_compare')) {
            $passed = false;
            $hints[] = 'Debe utilizarse version_compare() para comparar la versión mínima de PHP con PHP_VERSION.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateSeSdlcRequirements(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (str_contains($clean, 'UserStorySpecification')) {
            if (!str_contains($clean, 'class UserStorySpecification')) {
                $passed = false;
                $hints[] = 'Debes declarar la clase UserStorySpecification.';
            }
            if (!str_contains($clean, 'isInvestCompliant')) {
                $passed = false;
                $hints[] = 'La clase debe implementar el método isInvestCompliant(): bool.';
            }
            if (!str_contains($clean, 'hasNonFunctionalSla')) {
                $passed = false;
                $hints[] = 'La clase debe implementar el método hasNonFunctionalSla(): bool.';
            }
            if (!str_contains($clean, 'acceptanceCriteria')) {
                $passed = false;
                $hints[] = 'Debes incluir la propiedad $acceptanceCriteria para registrar los criterios de aceptación.';
            }
            return;
        }

        // Backwards compatibility for legacy SlaViolationDetector submissions
        if (str_contains($clean, 'SlaViolationDetector')) {
            if (!str_contains($clean, 'class SlaViolationDetector')) {
                $passed = false;
                $hints[] = 'Debes declarar la clase SlaViolationDetector.';
            }
            if (!str_contains($clean, 'calculateP95') || !str_contains($clean, 'isSlaViolated')) {
                $passed = false;
                $hints[] = 'La clase debe implementar los métodos calculateP95(array $latencies): float e isSlaViolated(array $latencies): bool.';
            }
            return;
        }

        $passed = false;
        $hints[] = 'Debes implementar la clase UserStorySpecification con los métodos isInvestCompliant(): bool y hasNonFunctionalSla(): bool.';
    }

    /**
     * @param list<string> $hints
     */
    private function validateSeCleanCodeQuality(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class CleanOrderValidator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase CleanOrderValidator.';
        }
        if (!str_contains($code, 'function isValidOrder(')) {
            $passed = false;
            $hints[] = 'La clase debe implementar el método isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool.';
        }
        if (preg_match('/if\s*\([^)]+\)\s*\{[^{}]*if\s*\(/', $code)) {
            $passed = false;
            $hints[] = 'Clean Code: Evita anidar if dentro de if. Aplica Cláusulas de Guarda (Early Return) para mantener el código plano y legible.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateSeSolidPrinciples(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'interface TaxStrategyInterface')) {
            $passed = false;
            $hints[] = 'Debes declarar la interfaz TaxStrategyInterface (Open/Closed Principle).';
        }
        if (!str_contains($code, 'class TaxEngine')) {
            $passed = false;
            $hints[] = 'Debes implementar la clase TaxEngine con inyección de estrategias.';
        }
        if (!str_contains($code, 'supports(') || !str_contains($code, 'calculateTax(')) {
            $passed = false;
            $hints[] = 'TaxStrategyInterface debe definir supports(string $countryCode): bool y calculateTax(float $amount): float.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateSeRefactoringCodeSmells(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class EmailAddress')) {
            $passed = false;
            $hints[] = 'Debes declarar el Value Object EmailAddress.';
        }
        if (!str_contains($code, 'function getDomain(')) {
            $passed = false;
            $hints[] = 'EmailAddress debe implementar el método getDomain(): string.';
        }
        if (!str_contains($code, 'InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debes lanzar \InvalidArgumentException si el formato del correo es inválido.';
        }
        if (!str_contains($code, 'FILTER_VALIDATE_EMAIL') && !str_contains($code, 'filter_var')) {
            $passed = false;
            $hints[] = 'Pista: Utiliza filter_var($email, FILTER_VALIDATE_EMAIL) para validar el formato estándar.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateGitPlumbing(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class GitBlobHasher')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase GitBlobHasher.';
        }
        if (!str_contains($code, 'computeBlobSha(')) {
            $passed = false;
            $hints[] = 'Debes implementar el método computeBlobSha(string $content): string.';
        }
        if (!str_contains($code, 'blob ') || (!str_contains($code, '\0') && !str_contains($code, 'chr(0)'))) {
            $passed = false;
            $hints[] = 'Git empaqueta los blobs con el encabezado "blob <longitud>\0".';
        }
    }

    /**
    /**
     * @param list<string> $hints
     */
    private function validateGitBranchingStrategies(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class BranchMergeAnalyzer')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase BranchMergeAnalyzer.';
        }
        if (!str_contains($code, 'canFastForward(')) {
            $passed = false;
            $hints[] = 'Debes implementar el método canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool.';
        }
        if (!str_contains($code, 'parents') && !str_contains($code, 'commitGraph')) {
            $passed = false;
            $hints[] = 'Debes explorar los padres de cada commit en el grafo para verificar si targetCommit es alcanzable.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateGitPrCodeReview(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class ConventionalCommitValidator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase ConventionalCommitValidator.';
        }
        if (!str_contains($code, 'function isValid(')) {
            $passed = false;
            $hints[] = 'Debes implementar el método isValid(string $commitMessage): bool.';
        }
        if (!str_contains($code, 'feat') || !str_contains($code, 'fix')) {
            $passed = false;
            $hints[] = 'El validador debe contemplar tipos convencionales como feat, fix, etc.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateGitCollaboration(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class BranchProtectionPolicyValidator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase BranchProtectionPolicyValidator.';
        }
        if (!str_contains($code, 'function evaluate(')) {
            $passed = false;
            $hints[] = 'Debes implementar el método evaluate(array $policy, array $prState): array.';
        }
        if (!str_contains($code, 'require_ci') || !str_contains($code, 'approvals')) {
            $passed = false;
            $hints[] = 'Debes evaluar las reglas de CI y el número mínimo de aprobaciones requeridas.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateTestingPhpUnitMastery(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class TieredPricingCalculator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase TieredPricingCalculator.';
        }
        if (!str_contains($code, 'calculateTotal(')) {
            $passed = false;
            $hints[] = 'Debes implementar calculateTotal(int $units, float $unitPrice): float.';
        }
        if (!str_contains($code, 'InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debes lanzar \InvalidArgumentException ante unidades <= 0 o precio negativo.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateArchPatternsComparison(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'interface StudentRepositoryPort')) {
            $passed = false;
            $hints[] = 'Debes definir la interfaz de puerto StudentRepositoryPort.';
        }
        if (!str_contains($code, 'class RegisterStudentUseCase')) {
            $passed = false;
            $hints[] = 'Debes implementar el caso de uso RegisterStudentUseCase inyectando el puerto.';
        }
        if (!str_contains($code, 'existsByEmail') || !str_contains($code, 'save')) {
            $passed = false;
            $hints[] = 'El caso de uso debe invocar existsByEmail y save en el puerto.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateProfTeamCommunication(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class UserStoryInvestEvaluator')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase UserStoryInvestEvaluator.';
        }
        if (!str_contains($clean, 'evaluateStory')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método evaluateStory(array $storyData): array.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si faltan campos obligatorios o los puntos de historia son inválidos.';
        }
        if (!str_contains($clean, 'as_a') || !str_contains($clean, 'i_want') || !str_contains($clean, 'so_that')) {
            $passed = false;
            $hints[] = 'La historia debe evaluar los componentes canónicos de Connextra: as_a, i_want y so_that.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateProfAdrTechnicalDecisions(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class AdrQualityEvaluator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase AdrQualityEvaluator.';
        }
        if (!str_contains($code, 'isComplete(')) {
            $passed = false;
            $hints[] = 'Debes implementar el método isComplete(array $adrData): bool.';
        }
        if (!str_contains($code, 'context') || !str_contains($code, 'decision') || !str_contains($code, 'consequences')) {
            $passed = false;
            $hints[] = 'El evaluador debe comprobar campos indispensables de ADR: context, decision, consequences.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateProfFailureEngineering(string $code, bool &$passed, array &$hints): void
    {
        if (!str_contains($code, 'class CircuitBreaker')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase CircuitBreaker.';
        }
        if (!str_contains($code, 'recordFailure(') || !str_contains($code, 'recordSuccess(')) {
            $passed = false;
            $hints[] = 'Debes implementar recordFailure() y recordSuccess().';
        }
        if (!str_contains($code, 'OPEN') || !str_contains($code, 'CLOSED')) {
            $passed = false;
            $hints[] = 'El Circuit Breaker debe transicionar entre estados CLOSED y OPEN.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validateProfIncidentManagementLogs(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class BlamelessPostmortemAuditor')) {
            $passed = false;
            $hints[] = 'Debe definirse la clase BlamelessPostmortemAuditor.';
        }
        if (!str_contains($clean, 'auditPostmortem')) {
            $passed = false;
            $hints[] = 'Debe implementarse el método auditPostmortem(array $report): array.';
        }
        if (!str_contains($clean, 'throw new InvalidArgumentException') && !str_contains($clean, 'throw new \InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debe lanzarse InvalidArgumentException si el reporte no contiene incident_id o el tiempo de resolución es negativo.';
        }
        if (!str_contains($clean, 'culpable') && !str_contains($clean, 'blameless')) {
            $passed = false;
            $hints[] = 'Debe comprobarse la cultura sin culpas (blameless) rechazando atribuciones individuales de error.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validatePhpSyntaxTypesVariables(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class InvoiceCalculator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase InvoiceCalculator.';
        }
        if (!str_contains($clean, 'calculateTotal')) {
            $passed = false;
            $hints[] = 'Debes implementar el método calculateTotal(float $subtotal, float $taxRate): float.';
        }
        if (!str_contains($clean, 'formatCurrency')) {
            $passed = false;
            $hints[] = 'Debes implementar el método formatCurrency(float $amount, string $currency = \'USD\'): string.';
        }
        if (!str_contains($clean, 'InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debes lanzar InvalidArgumentException cuando el subtotal sea negativo o la tasa de impuesto sea inválida.';
        }
        if (!str_contains($clean, 'round(')) {
            $hints[] = 'Sugerencia: Utiliza round() para evitar imprecisiones de coma flotante en el total calculado.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validatePhpControlFlowFunctions(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class CustomerDiscountEvaluator')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase CustomerDiscountEvaluator.';
        }
        if (!str_contains($clean, 'getDiscountPercentage')) {
            $passed = false;
            $hints[] = 'Debes implementar el método getDiscountPercentage(string $customerTier): float.';
        }
        if (!str_contains($clean, 'applyDiscount')) {
            $passed = false;
            $hints[] = 'Debes implementar el método applyDiscount(float $amount, string $customerTier): float.';
        }
        if (!str_contains($clean, 'match')) {
            $passed = false;
            $hints[] = 'Debes utilizar la expresión match de PHP 8 para evaluar el tier del cliente.';
        }
        if (!str_contains($clean, 'InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debes validar que el importe ($amount) no sea negativo lanzando InvalidArgumentException.';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validatePhpArraysData(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class ProductCatalogProcessor')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase ProductCatalogProcessor.';
        }
        if (!str_contains($clean, 'filterActive')) {
            $passed = false;
            $hints[] = 'Debes implementar el método filterActive(array $products): array.';
        }
        if (!str_contains($clean, 'calculateInventoryValue')) {
            $passed = false;
            $hints[] = 'Debes implementar el método calculateInventoryValue(array $products): float.';
        }
        if (!str_contains($clean, 'array_filter') && !str_contains($clean, 'filterActive(')) {
            $passed = false;
            $hints[] = 'Debes filtrar la colección de productos evaluando la clave active.';
        }
        if (!str_contains($clean, 'array_values') && !str_contains($clean, 'array_column')) {
            $hints[] = 'Sugerencia: Reindexa el array filtrado con array_values() y extrae columnas numéricas con array_column().';
        }
    }

    /**
     * @param list<string> $hints
     */
    private function validatePhpOopFoundations(string $code, bool &$passed, array &$hints): void
    {
        $clean = $this->stripComments($code);

        if (!str_contains($clean, 'class StudentProfile')) {
            $passed = false;
            $hints[] = 'Debes declarar la clase StudentProfile.';
        }
        if (!str_contains($clean, '__construct')) {
            $passed = false;
            $hints[] = 'Debes definir el constructor para inicializar name, email y grades.';
        }
        if (!str_contains($clean, 'addGrade')) {
            $passed = false;
            $hints[] = 'Debes implementar el método addGrade(float $grade): self.';
        }
        if (!str_contains($clean, 'getAverage')) {
            $passed = false;
            $hints[] = 'Debes implementar el método getAverage(): float.';
        }
        if (!str_contains($clean, 'isApproved')) {
            $passed = false;
            $hints[] = 'Debes implementar el método isApproved(): bool.';
        }
        if (!str_contains($clean, 'InvalidArgumentException')) {
            $passed = false;
            $hints[] = 'Debes validar que la calificación esté entre 0.0 y 100.0 lanzando InvalidArgumentException.';
        }
    }

    private function stripComments(string $code): string
    {
        $clean = preg_replace('!/\*.*?\*/!s', '', $code) ?? $code;
        $clean = preg_replace('!//.*?$!m', '', $clean) ?? $clean;
        return preg_replace('!#.*?$!m', '', $clean) ?? $clean;
    }
}
