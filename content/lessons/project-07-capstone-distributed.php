<?php

declare(strict_types=1);

return [
    'slug' => 'project-07-capstone-distributed',
    'title' => 'Proyecto Final: Arquitectura Modular & Caching',
    'module' => 'Proyectos Guiados',
    'minutes' => 240,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El Proyecto Capstone representa la culminación integral del currículum de Senior PHP Developer. Sintetiza todos los conceptos dominados a lo largo de las 20 fases previas: Arquitectura Hexagonal y Domain-Driven Design pragmático, segregación de responsabilidades con CQRS ligero (Command-Query Responsibility Segregation), comunicación asíncrona respaldada por Symfony Messenger y Redis Streams, caching distribuido multinivel (L1 en memoria estática de proceso + L2 en Redis con invalidación probabilística), resiliencia con Circuit Breakers y observabilidad continua con métricas de alta precisión en nanosegundos (hrtime).',
        'problem' => 'El antipatrón del "Monolito Espagueti Ingobernable": sistemas donde los módulos de facturación, usuarios, catálogo e inventario están acoplados directamente mediante dependencias cruzadas entre servicios y consultas SQL gigantescas con 12 JOINs. Cualquier cambio en la tabla de usuarios rompe el cálculo de impuestos, las caídas de pasarelas de pago bloquean el renderizado del catálogo y la base de datos colapsa ante la primera campaña publicitaria.',
        'solution' => 'Arquitectura Modular Desacoplada con Event-Driven Caching: 1) Micro-Modularidad en Symfony mediante Bounded Contexts que se comunican exclusivamente por contratos y eventos; 2) CQRS Ligero para separar escrituras transaccionales ACID en MySQL de lecturas denormalizadas en Redis (< 2ms); 3) Caching Distribuido Quirúrgico con invalidación basada en eventos de dominio; 4) Resiliencia con Circuit Breakers y colas de contingencia con Dead Letter Queues.',
        'problem_label' => 'El Antipatrón del Monolito Espagueti Altamente Acoplado:',
        'solution_label' => 'La Solución: Monolito Modular Desacoplado con Event-Driven Caching y CQRS:',
    ],
    'internals' => [
        'title' => 'Arquitectura del Capstone: Micro-Modularidad, Eventos de Dominio y Cache Distribuido',
        'steps' => [
            [
                'phase' => '1. Bounded Contexts y Contratos Intermodulares',
                'description' => 'Cada módulo de dominio (Billing, Catalog, Orders) es autónomo. No importa directamente entidades de otros módulos; la interacción se realiza mediante Interfaces de Servicio y DTOs de integración, impidiendo dependencias cíclicas.',
            ],
            [
                'phase' => '2. CQRS Ligero: Separación de Flujos de Comando y Consulta',
                'description' => 'Las mutaciones de estado se procesan mediante Commands transaccionales en MySQL, mientras que las consultas de lectura pesadas se resuelven contra proyecciones optimizadas en Redis en tiempo sub-milisegundo.',
            ],
            [
                'phase' => '3. Invalidación de Caché Basada en Eventos de Dominio',
                'description' => 'Cuando se publica el evento ProductPriceChangedEvent, un listener especializado invalida exclusivamente las claves de caché afectadas en Redis, erradicando lecturas obsoletas sin recurrir a costosas purgas globales de caché.',
            ],
            [
                'phase' => '4. Resiliencia con Circuit Breakers y Supervisión de Fallos',
                'description' => 'Todas las integraciones con servicios externos (pasarelas de pago, APIs logísticas) están protegidas por Circuit Breakers que conmutan automáticamente a degradación elegante cuando la tasa de fallo supera el umbral configurado.',
            ],
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Projects/Capstone/DistributedSystemCoordinator.php',
        'title' => 'Coordinador Central de Arquitectura Modular con CQRS y Cache Distribuido',
        'tag' => 'Capstone Distributed Coordinator & CQRS',
        'code' => 'declare(strict_types=1);

namespace App\\Projects\\Capstone;

use RuntimeException;
use Closure;

class DistributedSystemCoordinator
{
    /**
     * @var array<string, object>
     */
    private array $modules = [];

    /**
     * @var array<string, array{value: mixed, expires_at: float}>
     */
    private array $distributedCache = [];

    public function registerModule(string $name, object $moduleService): void
    {
        $cleanName = trim($name);
        if ($cleanName === \'\') {
            throw new RuntimeException(\'El nombre del módulo no puede estar vacío.\');
        }

        $this->modules[$cleanName] = $moduleService;
    }

    /**
     * Ejecuta una consulta optimizada con lectura en caché distribuida L2 (CQRS Query)
     */
    public function executeQuery(string $cacheKey, int $ttlSeconds, Closure $dataFetcher): mixed
    {
        $now = microtime(true);

        if (isset($this->distributedCache[$cacheKey])) {
            $item = $this->distributedCache[$cacheKey];
            if ($now < $item[\'expires_at\']) {
                return $item[\'value\'];
            }
            unset($this->distributedCache[$cacheKey]);
        }

        $freshData = $dataFetcher();

        $this->distributedCache[$cacheKey] = [
            \'value\' => $freshData,
            \'expires_at\' => $now + (float) $ttlSeconds,
        ];

        return $freshData;
    }

    /**
     * Ejecuta un comando mutacional con invalidación quirúrgica de caché (CQRS Command)
     *
     * @param list<string> $cacheKeysToInvalidate
     */
    public function executeCommand(string $module, string $method, array $args, array $cacheKeysToInvalidate = []): mixed
    {
        if (!isset($this->modules[$module])) {
            throw new RuntimeException(sprintf(\'Módulo "%s" no registrado en el coordinador.\', $module));
        }

        $handler = $this->modules[$module];
        if (!method_exists($handler, $method)) {
            throw new RuntimeException(sprintf(\'El método "%s" no existe en el módulo "%s".\', $method, $module));
        }

        $result = $handler->$method(...$args);

        // Invalidación reactiva de claves de caché vinculadas al comando
        foreach ($cacheKeysToInvalidate as $key) {
            unset($this->distributedCache[$key]);
        }

        return $result;
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Para un Senior Staff / Principal Engineer, la arquitectura no se mide por cuántos patrones o librerías de moda incorpora, sino por su simplicidad, resiliencia y desacoplamiento. El verdadero logro de un Senior es diseñar un sistema donde cualquier módulo pueda fallar, entrar en mantenimiento o escalar de forma independiente sin poner en peligro la continuidad del negocio.',
        'critical_questions' => [
            '¿Están los Bounded Contexts completamente desacoplados sin consultas SQL que hagan JOINs entre tablas de diferentes dominios?',
            '¿Se utiliza CQRS para evitar que consultas complejas de lectura bloqueen las tablas transaccionales de escritura?',
            '¿Es la invalidación de caché quirúrgica y guiada por eventos en lugar de usar TTLs cortos arbitrarios?',
            '¿Cuenta el sistema con degradación elegante cuando un servicio dependiente externo no responde en tiempo y forma?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Diseñar una plataforma de alta escala para ventas internacionales de comercio electrónico.',
        'junior' => [
            'approach' => 'Divide todo inmediatamente en 15 microservicios con gRPC y Docker, bases de datos separadas para cada servicio y llamadas HTTP síncronas entre ellos.',
            'flaws' => [
                'Latencia acumulativa en cascada: cada petición pasa por 8 microservicios síncronos tardando 3 segundos.',
                'Pérdida de consistencia: transacciones distribuidas complejas que dejan órdenes a medio cobrar.',
                'Sobrecarga operativa monumental para un equipo pequeño.',
            ],
        ],
        'senior' => [
            'approach' => 'Construye un Monolito Modular con fronteras de dominio claras, eventos en memoria con Symfony EventDispatcher y Messenger, CQRS ligero con caché en Redis y Circuit Breakers para pagos externos.',
            'rationale' => [
                'Latencia sub-milisegundo para lecturas de catálogo desde Redis.',
                'Despliegue unificado sin la pesadilla operativa de orquestar 15 servicios independientes.',
                'Evolución fluida: si un módulo requiere escalamiento independiente en el futuro, ya está completamente desacoplado por sus interfaces.',
            ],
            'trade_offs' => [
                'Requiere disciplina para no violar las fronteras de los módulos dentro del mismo repositorio.',
            ],
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Implementación de DistributedModularBus',
        'objective' => 'Implementar la clase DistributedModularBus para orquestar la comunicación entre módulos desacoplados mediante registro dinámico de handlers, despacho de comandos con captura de excepciones, y verificación de existencia de módulos.',
        'instructions' => 'Completa DistributedModularBus: implementa registerModule(string $moduleName, object $handler): void. Valida que $moduleName (tras trim) no sea vacío (InvalidArgumentException) y guarda el handler. Implementa dispatchCommand(string $moduleName, string $action, array $payload): array: valida que el módulo exista (lanza RuntimeException si no); invoca la acción dinámicamente si el método existe en el handler o lanza RuntimeException si no existe; captura cualquier Throwable y retorna [status => "error", module => $moduleName, action => $action, error => $e->getMessage()]; si tiene éxito, retorna [status => "success", module => $moduleName, action => $action, result => $result, timestamp => microtime(true)]. Implementa hasModule(string $moduleName): bool y reset(): void.',
        'filename' => 'DistributedModularBus.php',
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Projects\\Capstone;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DistributedModularBus
{
    /**
     * @var array<string, object>
     */
    private array $modules = [];

    public function registerModule(string $moduleName, object $handler): void
    {
        // TODO: Validar que $moduleName no esté vacío (InvalidArgumentException)
        // TODO: Almacenar en $this->modules
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status: string, module: string, action: string, result?: mixed, error?: string, timestamp?: float}
     */
    public function dispatchCommand(string $moduleName, string $action, array $payload): array
    {
        // TODO: Validar que el módulo exista (RuntimeException si no)
        // TODO: Invocar $handler->$action($payload) de forma dinámica capturando Throwable
        // TODO: Retornar array estructurado con status, module, action y result o error
        return [
            \'status\' => \'error\',
            \'module\' => $moduleName,
            \'action\' => $action,
            \'error\' => \'No implementado\',
        ];
    }

    public function hasModule(string $moduleName): bool
    {
        return isset($this->modules[trim($moduleName)]);
    }

    public function reset(): void
    {
        $this->modules = [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Projects\\Capstone;

use InvalidArgumentException;
use RuntimeException;
use Throwable;

class DistributedModularBus
{
    /**
     * @var array<string, object>
     */
    private array $modules = [];

    public function registerModule(string $moduleName, object $handler): void
    {
        $clean = trim($moduleName);
        if ($clean === \'\') {
            throw new InvalidArgumentException(\'El nombre del módulo no puede estar vacío.\');
        }

        $this->modules[$clean] = $handler;
    }

    /**
     * @param array<string, mixed> $payload
     * @return array{status: string, module: string, action: string, result?: mixed, error?: string, timestamp?: float}
     */
    public function dispatchCommand(string $moduleName, string $action, array $payload): array
    {
        $cleanModule = trim($moduleName);
        $cleanAction = trim($action);

        if (!isset($this->modules[$cleanModule])) {
            return [
                \'status\' => \'error\',
                \'module\' => $cleanModule,
                \'action\' => $cleanAction,
                \'error\' => sprintf(\'Módulo "%s" no registrado.\', $cleanModule),
            ];
        }

        $handler = $this->modules[$cleanModule];

        if (!method_exists($handler, $cleanAction)) {
            return [
                \'status\' => \'error\',
                \'module\' => $cleanModule,
                \'action\' => $cleanAction,
                \'error\' => sprintf(\'La acción "%s" no existe en el módulo "%s".\', $cleanAction, $cleanModule),
            ];
        }

        try {
            $result = $handler->$cleanAction($payload);

            return [
                \'status\' => \'success\',
                \'module\' => $cleanModule,
                \'action\' => $cleanAction,
                \'result\' => $result,
                \'timestamp\' => microtime(true),
            ];
        } catch (Throwable $e) {
            return [
                \'status\' => \'error\',
                \'module\' => $cleanModule,
                \'action\' => $cleanAction,
                \'error\' => $e->getMessage(),
            ];
        }
    }

    public function hasModule(string $moduleName): bool
    {
        return isset($this->modules[trim($moduleName)]);
    }

    public function reset(): void
    {
        $this->modules = [];
    }
}
',
        'explanation' => 'DistributedModularBus permite desacoplar los dominios en un monolito modular, ejecutando comandos a través de contratos dinámicos con captura y aislamiento de errores en cada frontera de módulo.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Arquitectura Modular & Caching Distribuido',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un Monolito Modular con fronteras de dominio estrictas suele ser superior a una arquitectura de Microservicios prematura para la gran mayoría de proyectos?',
                'options' => [
                    'a' => 'Porque los microservicios no funcionan con Docker.',
                    'b' => 'Porque mantiene la cohesión de dominio y el desacoplamiento mediante interfaces sin pagar el enorme costo operativo, latencia de red en cascada y complejidad de transacciones distribuidas de los microservicios.',
                    'c' => 'Porque PHP 8.4 solo puede correr en un único servidor.',
                    'd' => 'Porque los microservicios requieren bases de datos en cinta magnética.',
                ],
                'correct' => 'b',
                'explanation' => 'Un Monolito Modular proporciona los mismos beneficios de organización de código y separación de responsabilidades que los microservicios, pero con llamadas en memoria (< 1µs), transacciones ACID locales y despliegues unificados y sencillos.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cómo ayuda el patrón CQRS (Command-Query Responsibility Segregation) a resolver problemas de contención en bases de datos de alta concurrencia?',
                'options' => [
                    'a' => 'Separa el modelo de escritura (Command) transaccional del modelo de lectura (Query), permitiendo que las consultas masivas se resuelvan contra proyecciones desnormalizadas en caché sin bloquear las tablas de escritura transaccional.',
                    'b' => 'Elimina las sentencias SELECT de la aplicación.',
                    'c' => 'Duplica el número de procesadores físicos del servidor.',
                    'd' => 'Desactiva los bloqueos de InnoDB permanentemente.',
                ],
                'correct' => 'a',
                'explanation' => 'Al separar las lecturas de las escrituras, las consultas intensivas leen de proyecciones precalculadas en memoria (Redis/Elasticsearch), liberando a la base de datos relacional para procesar exclusivamente mutaciones críticas.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el beneficio de la invalidación de caché guiada por eventos de dominio frente a depender exclusivamente de TTLs temporales pasivos?',
                'options' => [
                    'a' => 'Permite que la caché dure exactamente 100 años.',
                    'b' => 'Garantiza consistencia inmediata: la caché se purga exactamente cuando el estado del dato cambia en el sistema, evitando servir datos obsoletos y eliminando la necesidad de adivinar TTLs arbitrarios.',
                    'c' => 'Reduce el tamaño de la memoria de PHP a cero.',
                    'd' => 'Evita tener que usar Redis en producción.',
                ],
                'correct' => 'b',
                'explanation' => 'Con invalidación basada en eventos, los datos pueden tener TTLs muy largos porque el sistema sabe exactamente cuándo un cambio de estado los vuelve obsoletos, logrando una tasa de acierto (Cache Hit Ratio) cercana al 99%.',
            ],
        ],
    ],
];
