<?php

declare(strict_types=1);

return [
    'slug' => 'eval-senior-code-review',
    'title' => 'Reto: Code Review & Detección de Smells',
    'module' => 'evaluations',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El Code Review a nivel Senior no consiste en buscar discrepancias de estilo visual o indentación (tarea que debe delegarse a herramientas automatizadas de CI como PHP-CS-Fixer o ECS), sino en evaluar la solidez arquitectónica, el respeto a las invariantes de dominio, el impacto en escalabilidad, la seguridad frente a vulnerabilidades y la deuda técnica acumulativa. Un desarrollador Senior actúa como guardián de la mantenibilidad sistémica de la plataforma.',
        'problem' => 'En muchos equipos de desarrollo, las revisiones de código caen en dos extremos nocivos: la "aprobación ciega" (LGTM sin análisis crítico) o el "nitpicking trivial" (debates interminables sobre comillas simples versus dobles o saltos de línea). Esto permite que fallas graves de diseño —como consultas N+1 encubiertas, flush dentro de ciclos, swallowing de excepciones y fugas de abstracción— lleguen silenciosamente a producción, degradando el rendimiento y la estabilidad.',
        'problem_label' => 'El Antipatrón del Review Cosmético',
        'solution' => 'Instituir un marco riguroso de Code Review enfocado en vectores de alto impacto: 1) Correctitud de dominio y tipado estricto; 2) Eficiencia de I/O y persistencia (evitar flush() en bucles y consultas no indexadas); 3) Resiliencia y manejo de errores sin excepciones ocultas; 4) Superficie de ataque y sanitización de entradas; y 5) Acoplamiento y cohesión de componentes.',
        'solution_label' => 'Auditoría Arquitectónica & Detección de Smells',
    ],
    'internals' => [
        'title' => 'Dimensiones Críticas del Code Review de Nivel Senior',
        'steps' => [
            [
                'phase' => '1. Verificación de Contratos y Tipado Estricto',
                'description' => 'Confirmar la presencia obligatoria de declare(strict_types=1);, uso de tipos de retorno específicos, clases final o readonly por defecto, y ausencia de tipos mixtos (mixed) injustificados.',
            ],
            [
                'phase' => '2. Análisis de I/O y Patrones de Persistencia',
                'description' => 'Identificar llamadas síncronas innecesarias dentro de bucles, especialmente $entityManager->flush() dentro de foreach, invocaciones HTTP en bucles, o deserializaciones JSON masivas sin límites de tamaño.',
            ],
            [
                'phase' => '3. Evaluación de Resiliencia y Manejo de Errores',
                'description' => 'Asegurar que los bloques try-catch no silencien excepciones (empty catch o re-lanzar tipos genéricos sin causa), y que los errores de infraestructura se traduzcan en excepciones de dominio semánticas.',
            ],
            [
                'phase' => '4. Seguridad Defensiva y Sanitización',
                'description' => 'Comprobar que ninguna entrada externa se concatene directamente en strings de consulta SQL/DQL o comandos del sistema, forzando parámetros preparados y validación en fronteras de entrada.',
            ],
        ],
    ],
    'architecture_code' => [
        'filename' => 'CodeReviewAnalyzer.php',
        'title' => 'Analizador Estático de Reglas de Calidad y Anti-patrones en PHP 8.4',
        'tag' => 'Automated Code Review Engine',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Evaluations\\Review;

use PhpParser\\Error;
use PhpParser\\Node;
use PhpParser\\NodeTraverser;
use PhpParser\\NodeVisitorAbstract;
use PhpParser\\ParserFactory;

/**
 * Representa un hallazgo crítico detectado durante el análisis estático.
 */
final readonly class ReviewFinding
{
    public function __construct(
        public string $ruleId,
        public string $severity,
        public string $message,
        public int $line,
        public string $recommendation
    ) {}
}

/**
 * Visitador AST para detectar olores de código y antipatrones de rendimiento.
 */
class PerformanceAndSafetyVisitor extends NodeVisitorAbstract
{
    /** @var list<ReviewFinding> */
    private array $findings = [];
    private int $loopDepth = 0;

    public function enterNode(Node $node): ?int
    {
        // Rastrear profundidad de ciclos (foreach, for, while)
        if ($node instanceof Node\\Stmt\\Foreach_ || $node instanceof Node\\Stmt\\For_ || $node instanceof Node\\Stmt\\While_) {
            $this->loopDepth++;
        }

        // Regla 1: Detección de flush() dentro de ciclos
        if ($this->loopDepth > 0 && $node instanceof Node\\Expr\\MethodCall) {
            if ($node->name instanceof Node\\Identifier && $node->name->toString() === "flush") {
                $this->findings[] = new ReviewFinding(
                    ruleId: "PERF-001",
                    severity: "CRITICAL",
                    message: "Llamada a flush() detectada dentro de un bucle de iteración.",
                    line: $node->getStartLine(),
                    recommendation: "Acumula las entidades y ejecuta flush() y clear() en lotes fuera del bucle para evitar I/O masivo."
                );
            }
        }

        // Regla 2: Detección de catch vacío (swallowing exceptions)
        if ($node instanceof Node\\Stmt\\Catch_) {
            if (count($node->stmts) === 0) {
                $this->findings[] = new ReviewFinding(
                    ruleId: "RESIL-002",
                    severity: "CRITICAL",
                    message: "Bloque catch vacío silenciando excepciones operacionales.",
                    line: $node->getStartLine(),
                    recommendation: "Registra la excepción en el LoggerInterface o relanza una excepción de dominio."
                );
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\\Stmt\\Foreach_ || $node instanceof Node\\Stmt\\For_ || $node instanceof Node\\Stmt\\While_) {
            $this->loopDepth = max(0, $this->loopDepth - 1);
        }

        return null;
    }

    /**
     * @return list<ReviewFinding>
     */
    public function getFindings(): array
    {
        return $this->findings;
    }
}

/**
 * Servicio de orquestación de revisión de código.
 */
final class CodeReviewAnalyzer
{
    /**
     * @return list<ReviewFinding>
     */
    public function analyze(string $sourceCode): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        
        try {
            $ast = $parser->parse($sourceCode);
        } catch (Error $e) {
            return [
                new ReviewFinding(
                    ruleId: "SYNTAX-ERR",
                    severity: "BLOCKER",
                    message: sprintf("Error de sintaxis PHP: %s", $e->getMessage()),
                    line: $e->getStartLine(),
                    recommendation: "Corrige los errores de sintaxis antes de someter a revisión."
                ),
            ];
        }

        if ($ast === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $visitor = new PerformanceAndSafetyVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getFindings();
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Al revisar un Pull Request, el ingeniero Senior lee el código de afuera hacia adentro: primero comprende el problema de negocio en la descripción del PR, luego inspecciona las pruebas unitarias y de integración para validar el contrato esperado, y finalmente revisa la implementación técnica buscando cuellos de botella de concurrencia, efectos secundarios ocultos y riesgos de escalabilidad futura.',
        'critical_questions' => [
            '¿Qué sucede si este endpoint recibe 2,000 peticiones concurrentes? ¿Existe contención de locks en base de datos o llamadas a APIs externas que puedan bloquear los workers de PHP-FPM?',
            '¿Cómo falla este código cuando el servicio dependiente no está disponible? ¿Hay degradación elegante (circuit breaker / fallback) o colapsará en cascada con un Error 500?',
            '¿Se respetan las fronteras de encapsulación del dominio o este cambio expone detalles de persistencia a los controladores y capas de presentación?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Aprobación y retroalimentación de un Pull Request que introduce persistencia masiva de registros importados desde un archivo CSV.',
        'junior' => [
            'approach' => 'Revisa superficialmente el PR, verifica que "funcione" en entorno local y aprueba un foreach que ejecuta $em->persist($row) y $em->flush() en cada fila del archivo CSV.',
            'flaws' => [
                'Degradación catastrófica de I/O: miles de viajes de red individuales y transacciones redundantes que saturan la base de datos.',
                'Consumo exponencial de memoria al retener todas las entidades en el Unit of Work sin llamar a clear().',
                'Swallowing de excepciones: atrapa errores con catch vacíos para evitar que el script se detenga.',
            ],
        ],
        'senior' => [
            'approach' => 'Identifica el cuello de botella de I/O y exige procesamiento por lotes con batching, aislamiento de transacciones y procesamiento en segundo plano con Symfony Messenger.',
            'rationale' => [
                'Rendimiento O(1) en memoria al vaciar y desasociar entidades periódicamente mediante $em->flush() y $em->clear().',
                'Resiliencia operativa: las fallas de registros individuales se aíslan y se envían a Dead Letter Queues sin abortar el lote.',
                'Mantenibilidad: el código se somete a linters de CI antes de llegar a la revisión humana de arquitectura.',
            ],
            'trade_offs' => [
                'Requiere diseñar DTOs intermedios y configurar colas asíncronas, pero garantiza escalabilidad para millones de registros.',
                'Hacer un review exhaustivo requiere invertir entre 20 y 40 minutos por PR significativo, pero ahorra días enteros de debugging de incidentes críticos en producción y previene la degradación acumulativa de la base de datos.',
            ],
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Implementación de CodeSmellDetector',
        'objective' => 'Implementar la clase CodeSmellDetector para analizar código fuente PHP y detectar cuatro antipatrones críticos de producción: ausencia de declare(strict_types=1), llamadas a flush() dentro de bucles, bloques catch vacíos, y concatenación insegura en sentencias SQL.',
        'instructions' => 'Completa la clase CodeSmellDetector en el namespace App\\Evaluations\\Review. Implementa detectSmells(string $phpCode): array. 1) Si no contiene "declare(strict_types=1);", registra smell con type "missing_strict_types", severity "critical", y message descriptivo. 2) Si detecta "flush()" dentro de un bucle "foreach" o "for" (e.g. mediante regex o análisis de bloques), registra smell con type "flush_in_loop", severity "high", y message descriptivo. 3) Si detecta un bloque catch vacío (catch (...) { }), registra smell con type "empty_catch", severity "critical", y message descriptivo. 4) Si detecta concatenación directa de variables en consultas SQL (e.g. "SELECT ... " . $var o dentro de comillas dobles con variables sin prepared statements), registra smell con type "sql_injection_risk", severity "critical", y message descriptivo. Retorna array con: ["valid" => count($smells) === 0, "smells_count" => count($smells), "smells" => $smells]. Implementa además hasCriticalSmells(string $phpCode): bool, que retorne true si existe al menos un smell con severity "critical".',
        'filename' => 'CodeSmellDetector.php',
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Evaluations\\Review;

class CodeSmellDetector
{
    /**
     * @return array{valid: bool, smells_count: int, smells: array<int, array{type: string, severity: string, message: string}>}
     */
    public function detectSmells(string $phpCode): array
    {
        // TODO: Detectar missing_strict_types
        // TODO: Detectar flush_in_loop
        // TODO: Detectar empty_catch
        // TODO: Detectar sql_injection_risk
        // TODO: Retornar estructura valid, smells_count y smells
        return [
            \'valid\' => false,
            \'smells_count\' => 0,
            \'smells\' => [],
        ];
    }

    public function hasCriticalSmells(string $phpCode): bool
    {
        // TODO: Retornar true si algún smell tiene severity \'critical\'
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Evaluations\\Review;

class CodeSmellDetector
{
    /**
     * @return array{valid: bool, smells_count: int, smells: array<int, array{type: string, severity: string, message: string}>}
     */
    public function detectSmells(string $phpCode): array
    {
        $smells = [];

        // 1. Validar declare(strict_types=1);
        if (!preg_match(\'/declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;/i\', $phpCode)) {
            $smells[] = [
                \'type\' => \'missing_strict_types\',
                \'severity\' => \'critical\',
                \'message\' => \'Falta la directiva declare(strict_types=1); en el archivo.\',
            ];
        }

        // 2. Validar flush() en loops (foreach / for / while)
        if (preg_match(\'/(foreach|for|while)\\s*\\([^)]*\\)\\s*\\{[^}]*->flush\\s*\\(/s\', $phpCode)) {
            $smells[] = [
                \'type\' => \'flush_in_loop\',
                \'severity\' => \'high\',
                \'message\' => \'Llamada a flush() detectada dentro de un bucle. Utilizar procesamiento por lotes.\',
            ];
        }

        // 3. Validar catch vacío
        if (preg_match(\'/catch\\s*\\([^)]*\\)\\s*\\{\\s*\\}/s\', $phpCode)) {
            $smells[] = [
                \'type\' => \'empty_catch\',
                \'severity\' => \'critical\',
                \'message\' => \'Bloque catch vacío detectado silenciando excepciones operacionales.\',
            ];
        }

        // 4. Validar riesgo de SQL Injection por concatenación
        if (
            preg_match(\'/(SELECT|INSERT|UPDATE|DELETE)\\s+[^;"]*[\\\'"]\\s*\\.\\s*\\$/i\', $phpCode) ||
            preg_match(\'/[\\\'"](SELECT|INSERT|UPDATE|DELETE)\\s+[^;"]*\\$[a-zA-Z_]/i\', $phpCode)
        ) {
            $smells[] = [
                \'type\' => \'sql_injection_risk\',
                \'severity\' => \'critical\',
                \'message\' => \'Posible inyección SQL detectada por concatenación directa de variables en la consulta.\',
            ];
        }

        return [
            \'valid\' => count($smells) === 0,
            \'smells_count\' => count($smells),
            \'smells\' => $smells,
        ];
    }

    public function hasCriticalSmells(string $phpCode): bool
    {
        $result = $this->detectSmells($phpCode);
        foreach ($result[\'smells\'] as $smell) {
            if ($smell[\'severity\'] === \'critical\') {
                return true;
            }
        }

        return false;
    }
}
',
        'explanation' => 'CodeSmellDetector demuestra cómo un análisis estático de reglas de negocio previene antipatrones severos de persistencia, seguridad y fiabilidad antes de que el código llegue a los entornos de producción.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Code Review de Nivel Senior & Métricas de Calidad',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál debe ser el foco primordial de un desarrollador Senior durante el proceso de revisión de código (Pull Request)?',
                'options' => [
                    'a' => 'Discutir si las variables deben usar camelCase o snake_case y corregir saltos de línea.',
                    'b' => 'Validar la solidez del diseño arquitectónico, límites de dominio, seguridad contra ataques, resiliencia y el impacto del I/O en la escalabilidad del sistema.',
                    'c' => 'Comprobar que no haya comentarios en el código.',
                    'd' => 'Aprobar inmediatamente si los tests pasan en verde sin leer la implementación.',
                ],
                'correct' => 'b',
                'explanation' => 'El formato y estilo de código deben delegarse a linters automáticos en el pipeline de CI. El valor insustituible del Code Review humano Senior radica en el análisis de arquitectura, concurrencia, seguridad y diseño de dominio.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué un bloque catch vacío (swallowing exception) es considerado un code smell crítico de nivel blocker?',
                'options' => [
                    'a' => 'Porque hace que el archivo PHP pese más kilobytes.',
                    'b' => 'Porque silencia fallos operacionales sin registro alguno, corrompiendo el estado del sistema y haciendo que los errores en producción sean prácticamente imposibles de diagnosticar.',
                    'c' => 'Porque la sintaxis de PHP 8.4 prohíbe los bloques catch.',
                    'd' => 'Porque obliga al servidor Apache a reiniciarse.',
                ],
                'correct' => 'b',
                'explanation' => 'Silenciar excepciones enmascara estados inconsistentes y bugs catastróficos. Una excepción siempre debe ser manejada adecuadamente, registrada con contexto estructurado o transformada en una excepción semántica de dominio.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el peligro de invocar $entityManager->flush() dentro de un bucle de importación de miles de registros?',
                'options' => [
                    'a' => 'Provoca un error de sintaxis en Doctrine.',
                    'b' => 'Desencadena un ciclo continuo de cálculo de ChangeSet y transacciones individuales de red hacia la base de datos por cada iteración, destruyendo el throughput y pudiendo agotar la memoria y el tiempo límite de ejecución.',
                    'c' => 'Borra la base de datos completa de forma irreversible.',
                    'd' => 'Fuerza a Doctrine a usar SQLite en lugar de MySQL.',
                ],
                'correct' => 'b',
                'explanation' => 'Cada llamada a flush() calcula el changeset de todas las entidades gestionadas y ejecuta transacciones SQL individuales. En bucles masivos debe aplicarse batch processing ($em->flush() y $em->clear() cada N registros).',
            ],
        ],
    ],
];
