<?php

declare(strict_types=1);

return [
    'slug' => 'resources-php-rfcs',
    'title' => 'Guía de RFCs & Estándares PSR',
    'module' => 'resources',
    'minutes' => 30,
    'difficulty' => 'Todos',
    'overview' => [
        'concept' => 'Los estándares PHP-FIG (PHP Standard Recommendations) y el proceso de RFCs (Requests for Comments) de PHP Internals constituyen los dos pilares de modernización, interoperabilidad y estabilidad del ecosistema PHP contemporáneo. Comprender la evolución de los PSRs y las propuestas de internals permite a los ingenieros diseñar arquitecturas agnósticas, desacopladas de frameworks propietarios y preparadas para aprovechar al máximo las optimizaciones nativas de rendimiento del motor Zend.',
        'problem' => 'Muchas bases de código sufren de acoplamiento severo a implementaciones propietarias de librerías o frameworks concretos (violando el principio de inversión de dependencias) y mantienen soluciones complejas obsoletas para problemas que PHP moderno ya resuelve de forma nativa mediante características aprobadas en RFCs oficiales (como Property Hooks, Enums, First-Class Callables o Asymmetric Visibility).',
        'problem_label' => 'El Antipatrón del Acoplamiento Propietario y Deuda Técnica',
        'solution' => 'Adoptar interfaces estándar PSR (PSR-3 para logging, PSR-7/PSR-15/PSR-17 para HTTP, PSR-11 para contenedores de inyección de dependencias, PSR-14 para eventos) en todas las capas de abstracción de dominio, y aplicar de manera proactiva las nuevas capacidades aprobadas en RFCs para reducir la complejidad accidental del código.',
        'solution_label' => 'Arquitectura Basada en Contratos PSR y Primitivas RFC',
    ],
    'internals' => [
        'title' => 'El Ciclo de Vida de los RFCs en PHP Internals y los Estándares PSR',
        'steps' => [
            [
                'phase' => '1. Proceso de RFC en PHP Internals',
                'description' => 'Una propuesta técnica (RFC) pasa por fases estrictas: Redacción inicial (Draft), discusión técnica pública en internals@lists.php.net, período formal de votación de 2 semanas (requiriendo mayoría de 2/3 para cambios de sintaxis o lenguaje), e implementación en C con pull request en php-src.',
            ],
            [
                'phase' => '2. Estándares PSR de Mensajería HTTP y Middlewares',
                'description' => 'PSR-7 define representaciones inmutables de Request y Response HTTP; PSR-17 proporciona factorías tipadas para construir dichos mensajes; y PSR-15 estandariza RequestHandlerInterface y MiddlewareInterface para crear tuberías de procesamiento HTTP universales.',
            ],
            [
                'phase' => '3. Estándares PSR de Infraestructura y Observabilidad',
                'description' => 'PSR-3 estandariza LoggerInterface con niveles de severidad RFC 5424 y mensajes interpolados con llaves {}; PSR-11 define ContainerInterface para resolución uniforme de dependencias; y PSR-14 desacopla la publicación y captura de eventos de dominio.',
            ],
            [
                'phase' => '4. La Filosofía de Inmutabilidad y Tipado Estricto en PHP Moderno',
                'description' => 'Los RFCs de PHP 8.1+ (Readonly Classes, Property Hooks en PHP 8.4, Asymmetric Visibility) y los PSRs contemporáneos convergen hacia objetos inmutables con invariantes blindadas y verificación exhaustiva de tipos en tiempo de compilación.',
            ],
        ],
    ],
    'architecture_code' => [
        'filename' => 'PsrInteroperabilityRegistry.php',
        'title' => 'Registro y Despachador Interoperable con Estándares PSR-3, PSR-11 y PSR-14 en PHP 8.4',
        'tag' => 'Universal PSR Interoperability Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Resources\\Standards;

use Psr\\Container\\ContainerInterface;
use Psr\\EventDispatcher\\EventDispatcherInterface;
use Psr\\Log\\LoggerInterface;
use Psr\\Log\\LogLevel;
use InvalidArgumentException;

/**
 * Evento inmutable de estándar PSR para despacho desacoplado.
 */
final readonly class StandardRegisteredEvent
{
    public function __construct(
        public int $psrNumber,
        public string $name,
        public string $category,
        public float $timestamp = 0.0
    ) {}
}

/**
 * Servicio de orquestación desacoplada basado enteramente en interfaces PSR.
 */
class PsrInteroperabilityRegistry
{
    /** @var array<int, array{number: int, name: string, category: string, status: string}> */
    private array $standards = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly ?EventDispatcherInterface $dispatcher = null,
        private readonly ?ContainerInterface $container = null
    ) {}

    public function registerStandard(int $number, string $name, string $category, string $status = "accepted"): void
    {
        if ($number <= 0) {
            throw new InvalidArgumentException("El número de PSR debe ser un entero positivo.");
        }

        $cleanName = trim($name);
        if ($cleanName === "") {
            throw new InvalidArgumentException("El nombre del estándar no puede estar vacío.");
        }

        $this->standards[$number] = [
            "number" => $number,
            "name" => $cleanName,
            "category" => trim($category),
            "status" => trim($status),
        ];

        // Logging estructurado compatible con PSR-3 (RFC 5424)
        $this->logger?->log(
            LogLevel::INFO,
            "Estándar PSR-{number} ({name}) registrado correctamente.",
            ["number" => $number, "name" => $cleanName, "category" => $category]
        );

        // Notificación de evento desacoplada mediante PSR-14
        $this->dispatcher?->dispatch(
            new StandardRegisteredEvent(
                psrNumber: $number,
                name: $cleanName,
                category: $category,
                timestamp: microtime(true)
            )
        );
    }

    /**
     * @return array{number: int, name: string, category: string, status: string}|null
     */
    public function getStandard(int $number): ?array
    {
        return $this->standards[$number] ?? null;
    }

    /**
     * Resuelve un servicio dependiente a través del contenedor PSR-11.
     */
    public function resolveDependency(string $serviceId): mixed
    {
        if ($this->container !== null && $this->container->has($serviceId)) {
            return $this->container->get($serviceId);
        }

        return null;
    }

    /**
     * @return array<int, array{number: int, name: string, category: string, status: string}>
     */
    public function getAllStandards(): array
    {
        return $this->standards;
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un ingeniero Senior no diseña librerías ni modelos de dominio acoplados a Symfony, Laravel o ningún framework específico. En su lugar, programa contra contratos PSR puros. Esto permite que el núcleo del negocio permanezca inmune a cambios de infraestructura, migraciones tecnológicas o decisiones corporativas durante más de una década.',
        'critical_questions' => [
            '¿Nuestras interfaces y servicios de dominio dependen de librerías de terceros o utilizan contratos estándar PSR universales?',
            '¿Estamos utilizando las capacidades nativas aprobadas en RFCs recientes (e.g. Property Hooks en PHP 8.4) para eliminar capas innecesarias de getters/setters redundantes?',
            '¿Cumplen nuestros registros de log con PSR-3 empleando contexto estructurado para integración transparente con sistemas de observabilidad (Elastic, Datadog, Prometheus)?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Diseño de un paquete compartido de autenticación y auditoría para ser consumido por 12 servicios distintos en la organización.',
        'junior' => [
            'approach' => 'Crea el paquete vinculándolo directamente con las clases concretas de Monolog y el contenedor de Symfony, escribiendo adaptadores propietarios ad-hoc para cada servicio.',
            'flaws' => [
                'Imposibilidad de utilizar el paquete en microservicios basados en Laravel o herramientas CLI independientes sin instalar todo Symfony.',
                'Dificultad severa para realizar pruebas unitarias en aislamiento debido a dependencias rígidas de infraestructura.',
                'Falta de interoperabilidad con estándares abiertos de telemetría y logging distribuido.',
            ],
        ],
        'senior' => [
            'approach' => 'Diseña el paquete dependiendo exclusivamente de PSR-3 (LoggerInterface), PSR-11 (ContainerInterface) y PSR-7/15 (HTTP Messages & Middlewares), delegando la inyección de la implementación concreta a la aplicación consumidora.',
            'rationale' => [
                'Interoperabilidad absoluta: el paquete funciona en Symfony, Laravel, Slim o scripts independientes sin fricción.',
                'Facilidad extrema de testeo: permite sustituir cualquier dependencia con implementaciones ligeras o nulas (NullLogger, InMemoryContainer).',
                'Longevidad arquitectónica: el paquete no sufrirá rupturas por actualizaciones mayores de frameworks externos.',
            ],
            'trade_offs' => [
                'Requiere mayor rigor en la definición de contratos e interfaces de inyección de dependencias.',
                'Invertir en estándares PSR requiere una curva de aprendizaje inicial para el equipo sobre especificaciones PHP-FIG, pero erradica el vendor lock-in y garantiza portabilidad total del código.',
            ],
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Implementación de PsrStandardRegistry',
        'objective' => 'Implementar la clase PsrStandardRegistry en el namespace App\\Resources\\Standards para registrar, consultar y validar estándares PSR y compatibilidad de RFCs de PHP moderno.',
        'instructions' => 'Completa la clase PsrStandardRegistry en el namespace App\\Resources\\Standards. Implementa los siguientes métodos: 1) registerPsr(int $number, string $name, string $type, string $status): void. Valida que $number > 0, que $name (tras trim) no esté vacío, que $type pertenezca a ["coding_standard", "interfaces", "http", "caching", "other"] y que $status pertenezca a ["accepted", "deprecated", "abandoned"]. Si alguna validación falla, lanza InvalidArgumentException. Almacena el PSR indexado por su número. 2) getPsr(int $number): ?array. Retorna el array del PSR registrado con keys ["number", "name", "type", "status"] o null si no existe. 3) listByType(string $type): array. Retorna la lista de PSRs registrados cuyo type coincida con el parámetro. 4) validatePhpFeatureRfc(string $featureName, string $minPhpVersion): bool. Valida que la versión de PHP en tiempo de ejecución (PHP_VERSION) sea mayor o igual que $minPhpVersion utilizando version_compare(PHP_VERSION, $minPhpVersion, ">="). Si la versión actual cumple con el requisito, retorna true; de lo contrario retorna false. 5) count(): int. Retorna la cantidad total de estándares PSR registrados.',
        'filename' => 'PsrStandardRegistry.php',
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Resources\\Standards;

use InvalidArgumentException;

class PsrStandardRegistry
{
    /**
     * @var array<int, array{number: int, name: string, type: string, status: string}>
     */
    private array $standards = [];

    public function registerPsr(int $number, string $name, string $type, string $status): void
    {
        // TODO: Validar $number > 0
        // TODO: Validar $name no vacío
        // TODO: Validar $type en [\'coding_standard\', \'interfaces\', \'http\', \'caching\', \'other\']
        // TODO: Validar $status en [\'accepted\', \'deprecated\', \'abandoned\']
        // TODO: Guardar en $this->standards[$number]
    }

    /**
     * @return array{number: int, name: string, type: string, status: string}|null
     */
    public function getPsr(int $number): ?array
    {
        // TODO: Retornar PSR o null
        return null;
    }

    /**
     * @return array<int, array{number: int, name: string, type: string, status: string}>
     */
    public function listByType(string $type): array
    {
        // TODO: Retornar lista filtrada por type
        return [];
    }

    public function validatePhpFeatureRfc(string $featureName, string $minPhpVersion): bool
    {
        // TODO: Comparar con PHP_VERSION usando version_compare
        return false;
    }

    public function count(): int
    {
        return count($this->standards);
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Resources\\Standards;

use InvalidArgumentException;

class PsrStandardRegistry
{
    private const array ALLOWED_TYPES = [\'coding_standard\', \'interfaces\', \'http\', \'caching\', \'other\'];
    private const array ALLOWED_STATUSES = [\'accepted\', \'deprecated\', \'abandoned\'];

    /**
     * @var array<int, array{number: int, name: string, type: string, status: string}>
     */
    private array $standards = [];

    public function registerPsr(int $number, string $name, string $type, string $status): void
    {
        if ($number <= 0) {
            throw new InvalidArgumentException(\'El número de PSR debe ser un entero positivo.\');
        }

        $cleanName = trim($name);
        if ($cleanName === \'\') {
            throw new InvalidArgumentException(\'El nombre del estándar PSR no puede estar vacío.\');
        }

        $cleanType = trim($type);
        if (!in_array($cleanType, self::ALLOWED_TYPES, true)) {
            throw new InvalidArgumentException(sprintf(\'Tipo no válido: "%s".\', $cleanType));
        }

        $cleanStatus = trim($status);
        if (!in_array($cleanStatus, self::ALLOWED_STATUSES, true)) {
            throw new InvalidArgumentException(sprintf(\'Estado no válido: "%s".\', $cleanStatus));
        }

        $this->standards[$number] = [
            \'number\' => $number,
            \'name\' => $cleanName,
            \'type\' => $cleanType,
            \'status\' => $cleanStatus,
        ];
    }

    /**
     * @return array{number: int, name: string, type: string, status: string}|null
     */
    public function getPsr(int $number): ?array
    {
        return $this->standards[$number] ?? null;
    }

    /**
     * @return array<int, array{number: int, name: string, type: string, status: string}>
     */
    public function listByType(string $type): array
    {
        $result = [];
        foreach ($this->standards as $standard) {
            if ($standard[\'type\'] === $type) {
                $result[] = $standard;
            }
        }

        return $result;
    }

    public function validatePhpFeatureRfc(string $featureName, string $minPhpVersion): bool
    {
        return version_compare(PHP_VERSION, $minPhpVersion, \'>=\');
    }

    public function count(): int
    {
        return count($this->standards);
    }
}
',
        'explanation' => 'PsrStandardRegistry proporciona un catálogo tipado de especificaciones estándar y validación de compatibilidad con versiones de PHP para garantizar que los módulos respeten los contratos del ecosistema.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Estándares PSR & Evolución del Ecosistema PHP',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué la especificación PSR-7 define que los objetos HTTP Message (Request y Response) deben ser inmutables?',
                'options' => [
                    'a' => 'Porque los objetos mutables no pueden convertirse a JSON.',
                    'b' => 'Para garantizar predictibilidad y evitar efectos secundarios: en una tubería de middlewares, cada componente recibe una copia inalterada del estado y cualquier transformación produce una nueva instancia.',
                    'c' => 'Porque PHP 8.4 elimina los métodos setters de todas las clases.',
                    'd' => 'Para que el código solo pueda ejecutarse dentro de Apache.',
                ],
                'correct' => 'b',
                'explanation' => 'La inmutabilidad en PSR-7 erradica los errores sutiles de estado compartido entre middlewares, asegurando que ninguna capa intermedia modifique accidentalmente la petición sin devolver explícitamente un nuevo mensaje con withHeader() o withBody().',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es el beneficio de codificar las dependencias de una librería contra PSR-11 (ContainerInterface) en lugar de contra el contenedor específico de Symfony o Laravel?',
                'options' => [
                    'a' => 'Duplica la velocidad del procesador.',
                    'b' => 'Permite que la librería sea agnóstica al framework: cualquier aplicación que implemente ContainerInterface (Symfony, Laravel, PHP-DI) puede suministrar sus dependencias sin acoplamiento a paquetes propietarios.',
                    'c' => 'Evita tener que usar namespaces en PHP.',
                    'd' => 'Obliga a que todos los servicios sean estáticos.',
                ],
                'correct' => 'b',
                'explanation' => 'PSR-11 estandariza una interfaz minimalista con has() y get(), permitiendo que librerías y componentes reutilizables interactúen con cualquier contenedor de inyección de dependencias de la industria.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué característica principal introdujo el RFC oficial de Property Hooks aprobado para PHP 8.4?',
                'options' => [
                    'a' => 'Eliminó las variables con signo de dólar ($).',
                    'b' => 'Permite adjuntar lógica de lectura (get) y escritura (set) directamente en las propiedades de las clases, eliminando métodos getters/setters redundantes mientras se preserva el encapsulamiento.',
                    'c' => 'Hizo que PHP compilara a código ensamblador nativo en tiempo real.',
                    'd' => 'Permite ejecutar PHP en el navegador cliente sin WebAssembly.',
                ],
                'correct' => 'b',
                'explanation' => 'Property Hooks en PHP 8.4 permite declarar hooks get y set directamente en la definición de la propiedad, reduciendo drásticamente el código boilerplate de getters y setters sin sacrificar la validación ni el encapsulamiento.',
            ],
        ],
    ],
];
