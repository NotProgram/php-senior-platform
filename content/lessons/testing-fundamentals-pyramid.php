<?php

declare(strict_types=1);

return [
    'slug' => 'testing-fundamentals-pyramid',
    'title' => 'Piramide de Testing & Estrategia de Calidad',
    'module' => 'testing',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'En la ingenieria de software, una suite de pruebas no es un mero mecanismo de validacion; es una red de seguridad economica y arquitectonica. Si cada cambio en el codigo requiere horas de pruebas manuales o minutos interminables de pruebas de interfaz de usuario inestables, el costo marginal de desplegar a produccion crece exponencialmente hasta paralizar el negocio. La Piramide de Pruebas (formalizada por Mike Cohn y popularizada por Martin Fowler) establece una distribucion geometrica rigurosa: una base masiva de pruebas unitarias ultrarrapidas y deterministas, una capa media de pruebas de integracion que verifican la comunicacion entre subsistemas, y una cupula reducida de pruebas end-to-end (E2E) que simulan el flujo completo del usuario final.

Un desarrollador Senior comprende la razon fisica y matematica detras de esta distribucion: el costo de mantenimiento y el tiempo de ejecucion crecen un orden de magnitud en cada peldano ascendente. Cuando esta relacion se invierte (el antipatron del Cono de Helado o Ice Cream Cone), la suite se vuelve fragil, genera falsos positivos frecuentes y destruye la confianza del equipo de ingenieria.',
        'problem' => 'En la ingenieria de software, una suite de pruebas no es un mero mecanismo de validacion; es una red de seguridad economica y arquitectonica. Si cada cambio en el codigo requiere horas de pruebas manuales o minutos interminables de pruebas de interfaz de usuario inestables, el costo marginal de desplegar a produccion crece exponencialmente hasta paralizar el negocio. La Piramide de Pruebas (formalizada por Mike Cohn y popularizada por Martin Fowler) establece una distribucion geometrica rigurosa: una base masiva de pruebas unitarias ultrarrapidas y deterministas, una capa media de pruebas de integracion que verifican la comunicacion entre subsistemas, y una cupula reducida de pruebas end-to-end (E2E) que simulan el flujo completo del usuario final.',
        'solution' => 'Un desarrollador Senior comprende la razon fisica y matematica detras de esta distribucion: el costo de mantenimiento y el tiempo de ejecucion crecen un orden de magnitud en cada peldano ascendente. Cuando esta relacion se invierte (el antipatron del Cono de Helado o Ice Cream Cone), la suite se vuelve fragil, genera falsos positivos frecuentes y destruye la confianza del equipo de ingenieria.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Linea de Ensamblaje y el Sensor Electronico vs la Prueba de Choque',
        'concept' => 'Imagina la fabricacion de un automovil de alto rendimiento. Para garantizar que cada componente es seguro y funcional, no estrellas el vehiculo completo contra un muro cada vez que un operario ajusta un tornillo. En su lugar, utilizas sensores electronicos y calibres milimetricos en la mesa de trabajo para inspeccionar cada pieza aislada en 2 milisegundos (Pruebas Unitarias). Luego, montas el motor junto a la transmision en un banco de pruebas mecanico para validar que los engranajes acoplan sin friccion destructiva (Pruebas de Integracion). Finalmente, cuando el auto esta ensamblado, un piloto de pruebas lo conduce en la pista de pruebas bajo condiciones reales de lluvia y asfalto (Pruebas E2E / Funcionales).

Si intentaras probar todo exclusivamente en la pista de choque, tardarias dias en diagnosticar por que el coche fallo: ¿fue la bujia, el sensor de oxigeno, la bomba de combustible o el pedal? La prueba unitaria no solo es rapida; otorga aislamiento de falla con precision quirurgica.',
        'ascii_diagram' => 'PIRAMIDE DE TESTING (Estrategia Optima de ROI):

                / \\
               / E2E \\            <- 5-10% del total. Muy lentas (~3-10s), fragiles.
              /------- \\              Validan flujos de negocio criticos de punta a punta.
             /          \\
            / Integracion \\        <- 20-30% del total. Velocidad media (~50-200ms).
           /-------------- \\          Validan colaboracion con DB real, Cache y HTTP Kernel.
          /                  \\
         /     Unitarias      \\    <- 60-75% del total. Ultrarrapidas (<5ms), aisladas.
        /----------------------\\      Validan logica de dominio pura e invariantes de negocio.

-----------------------------------------------------------------------------------------
EL ANTIPATRON DEL CONO DE HELADO (Ice Cream Cone - Fallo Catastrofico):

        \\======================/   <- 60% Pruebas E2E / Browser (Lentas, flaky, no deterministicas)
         \\                    /
          \\   Integracion    /     <- 30% Pruebas de Integracion
           \\                /
            \\   Unitarias  /      <- 10% Pruebas Unitarias (Base insuficiente, nulo aislamiento)
             \\------------/
',
        'analogy' => 'Imagina la fabricacion de un automovil de alto rendimiento. Para garantizar que cada componente es seguro y funcional, no estrellas el vehiculo completo contra un muro cada vez que un operario ajusta un tornillo. En su lugar, utilizas sensores electronicos y calibres milimetricos en la mesa de trabajo para inspeccionar cada pieza aislada en 2 milisegundos (Pruebas Unitarias). Luego, montas el motor junto a la transmision en un banco de pruebas mecanico para validar que los engranajes acoplan sin friccion destructiva (Pruebas de Integracion). Finalmente, cuando el auto esta ensamblado, un piloto de pruebas lo conduce en la pista de pruebas bajo condiciones reales de lluvia y asfalto (Pruebas E2E / Funcionales).

Si intentaras probar todo exclusivamente en la pista de choque, tardarias dias en diagnosticar por que el coche fallo: ¿fue la bujia, el sensor de oxigeno, la bomba de combustible o el pedal? La prueba unitaria no solo es rapida; otorga aislamiento de falla con precision quirurgica.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'A nivel de ejecucion en el motor de PHP y PHPUnit, las pruebas unitarias ejecutan metodos puros en memoria sin inicializar sockets de red, descriptores de archivos ni conexiones TCP a motores de bases de datos.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Esto permite que un solo hilo de PHP ejecute 5,000 aserciones unitarias en menos de 200 milisegundos.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Por el contrario, cada prueba de integracion o funcional con base de datos requiere inicializar el Dependency Injection Container de Symfony, abrir conexiones PDO y gestionar transacciones ACID, lo que introduce una latencia minima de 15 a 50 milisegundos por caso de prueba.',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => 'En pruebas E2E con herramientas como Panther o Playwright, se arranca un proceso completo de navegador Chromium mediante WebDriver, consumiendo cientos de megabytes de memoria RAM y miles de ciclos de reloj de CPU.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'La decision de que tipo de prueba escribir se fundamenta en la matriz de mensajes propuesta por Sandi Metz: 1. Mensajes Entrantes de Consulta (Incoming Queries): se prueba el valor retornado mediante aserciones unitarias directas. 2. Mensajes Entrantes de Comando (Incoming Commands): se prueba el cambio de estado observable del objeto receptor. 3. Mensajes Privados / Autocomunicacion: nunca se prueban directamente; hacerlo genera acoplamiento a detalles de implementacion. 4. Mensajes Salientes de Comando: se prueba la emision del mensaje a traves de dobles de prueba (Mocks/Spies). Comprender estos principios evita escribir pruebas unitarias redundantes que ralentizan el refactor sin aportar cobertura real.',
        ],
    ],
    'video' => [
        'title' => 'It\'s Time We Go Beyond The Test Pyramid (& Do This Instead)',
        'speaker' => 'Dave Farley (Co-autor de Continuous Delivery)',
        'youtube_id' => 'IBvYFRSw4do',
        'duration' => '18 min',
        'description' => 'Dave Farley desglosa desde los primeros principios de la ingenieria de software como optimizar la velocidad de retroalimentacion (feedback loop) balanceando las pruebas automatizadas para lograr despliegues continuos sin friccion.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Testing\\Architecture;

use InvalidArgumentException;

/**
 * Calculador de Metricas de Salud y Distribucion de la Suite de Pruebas.
 * Aplica los principios cuantitativos de la Piramide de Testing.
 */
final readonly class TestSuiteHealthMetrics
{
    public float $unitRatio;
    public float $integrationRatio;
    public float $e2eRatio;
    public bool $isHealthy;
    public ?string $detectedAntiPattern;

    public function __construct(
        public int $unitTests,
        public int $integrationTests,
        public int $e2eTests
    ) {
        if ($unitTests < 0 || $integrationTests < 0 || $e2eTests < 0) {
            throw new InvalidArgumentException(\'Los conteos de pruebas no pueden ser negativos.\');
        }

        $total = $unitTests + $integrationTests + $e2eTests;
        if ($total === 0) {
            throw new InvalidArgumentException(\'La suite de pruebas no puede contener 0 pruebas.\');
        }

        $this->unitRatio = round(($unitTests / $total) * 100.0, 2);
        $this->integrationRatio = round(($integrationTests / $total) * 100.0, 2);
        $this->e2eRatio = round(($e2eTests / $total) * 100.0, 2);

        // Evaluacion de antipatrones cuantitativos
        if ($this->e2eRatio > 30.0 || $this->unitRatio < 40.0) {
            $this->detectedAntiPattern = \'ICE_CREAM_CONE\';
            $this->isHealthy = false;
        } elseif ($this->integrationRatio > 60.0) {
            $this->detectedAntiPattern = \'HOURGLASS\';
            $this->isHealthy = false;
        } else {
            $this->detectedAntiPattern = null;
            $this->isHealthy = ($this->unitRatio >= 60.0 && $this->e2eRatio <= 15.0);
        }
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador Junior mide el exito de una suite de pruebas unicamente por el porcentaje de cobertura de lineas (Code Coverage). Un desarrollador Senior entiende que una cobertura del 100% lograda con pruebas E2E lentas y fragiles paraliza al equipo, mientras que una cobertura del 85% concentrada en el nucleo de dominio mediante pruebas unitarias deterministicas que se ejecutan en menos de 5 segundos permite desplegar a produccion decenas de veces al dia con total tranquilidad.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Crea pruebas E2E pesadas con navegadores para probar validaciones basicas de campos obligatorios o formatos de string.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Prueba exhaustivamente las reglas de negocio e invariantes con pruebas unitarias puras y reserva las pruebas E2E para los 3 caminos criticos de monetizacion.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'La Piramide de Pruebas',
            'source' => 'Succeeding with Agile / Fowler Bliki',
            'quote' => 'La piramide de pruebas enfatiza tener una base amplia de tests unitarios rapidos y baratos, una capa intermedia de integracion y un numero reducido de tests end-to-end.',
            'author' => 'Mike Cohn & Martin Fowler',
            'explanation' => 'Invertir la piramide degrada el tiempo de retroalimentacion en integracion continua de segundos a horas.',
        ],
        [
            'topic' => 'Velocidad de Feedback',
            'source' => 'Continuous Delivery: Reliable Software Releases through Build, Test, and Deployment Automation',
            'quote' => 'Si una prueba tarda mas de unos pocos minutos en ejecutarse, los desarrolladores dejaran de ejecutarla antes de cada commit.',
            'author' => 'Jez Humble & David Farley',
            'explanation' => 'El tiempo de ejecucion de la suite de pruebas determina el ritmo de iteracion del equipo entero.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: The Practical Test Pyramid',
            'url' => 'https://martinfowler.com/articles/practical-test-pyramid.html',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'PHPUnit Official Documentation: Test Suite Optimization',
            'url' => 'https://docs.phpunit.de/en/11.0/',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Auditor Cuantitativo de Salud de la Piramide de Pruebas',
        'objective' => 'Implementar la clase TestPyramidAuditor para analizar la composicion de una suite de pruebas, detectar antipatrones estructurales (\'ICE_CREAM_CONE\', \'HOURGLASS\') y proyectar el tiempo estimado de ejecucion.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Testing.
2. Implementa la clase TestPyramidAuditor con el metodo auditDistribution(int $unitCount, int $integrationCount, int $e2eCount): array.
3. Valida que los conteos no sean negativos y que el total sea mayor a 0; de lo contrario lanza InvalidArgumentException.
4. Calcula el porcentaje de cada nivel y evalua si la distribucion es saludable ($unitPercent >= 60.0 y $e2ePercent <= 15.0).
5. Detecta el antipatron \'ICE_CREAM_CONE\' si $e2ePercent > 30.0 o $unitPercent < 40.0; o \'HOURGLASS\' si $integrationPercent > 60.0.
6. Implementa calculateEstimatedRuntime(int $unitCount, int $integrationCount, int $e2eCount, float $unitMs = 2.0, float $integrationMs = 50.0, float $e2eMs = 3000.0): float retornando el tiempo total en segundos redondeado a 2 decimales.',
        'filename' => 'src/Testing/TestPyramidAuditor.php',
        'guide' => [
            'steps' => [
                'Paso 1: Suma los tres niveles de prueba y verifica que ningun parametro sea negativo.',
                'Paso 2: Calcula el porcentaje flotante de cada categoria respecto al total.',
                'Paso 3: Evalua las reglas condicionales de antipatrones y salud estructural.',
                'Paso 4: Multiplica cada conteo por su costo en milisegundos, suma y divide entre 1000.0 para obtener segundos.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Una piramide sana requiere base ancha: unitarias >= 60% y E2E <= 15%. Si E2E > 30%, la forma es un cono invertido.',
            ],
            [
                'text' => '[Pista 2: Estructura] auditDistribution retorna un array asociativo con claves \'is_healthy\', \'anti_pattern\', \'unit_percent\', \'integration_percent\' y \'e2e_percent\'.',
            ],
            [
                'text' => '[Pista 3: Snippet] $runtimeSec = round((($unitCount * $unitMs) + ($integrationCount * $integrationMs) + ($e2eCount * $e2eMs)) / 1000.0, 2);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Testing;

use InvalidArgumentException;

class TestPyramidAuditor
{
    /**
     * @return array{is_healthy: bool, anti_pattern: ?string, unit_percent: float, integration_percent: float, e2e_percent: float}
     */
    public function auditDistribution(int $unitCount, int $integrationCount, int $e2eCount): array
    {
        // TODO: Validar que conteos >= 0 y total > 0 (lanzar InvalidArgumentException)
        // TODO: Calcular porcentajes relativos
        // TODO: Detectar antipatrones ICE_CREAM_CONE y HOURGLASS
        // TODO: Retornar resultado estructurado
        return [];
    }

    public function calculateEstimatedRuntime(
        int $unitCount,
        int $integrationCount,
        int $e2eCount,
        float $unitMs = 2.0,
        float $integrationMs = 50.0,
        float $e2eMs = 3000.0
    ): float {
        // TODO: Calcular suma ponderada de milisegundos y convertir a segundos con 2 decimales
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Testing;

use InvalidArgumentException;

class TestPyramidAuditor
{
    /**
     * @return array{is_healthy: bool, anti_pattern: ?string, unit_percent: float, integration_percent: float, e2e_percent: float}
     */
    public function auditDistribution(int $unitCount, int $integrationCount, int $e2eCount): array
    {
        if ($unitCount < 0 || $integrationCount < 0 || $e2eCount < 0) {
            throw new InvalidArgumentException(\'Los conteos de pruebas no pueden ser negativos.\');
        }

        $total = $unitCount + $integrationCount + $e2eCount;
        if ($total === 0) {
            throw new InvalidArgumentException(\'El total de pruebas debe ser mayor a cero.\');
        }

        $unitPercent = round(($unitCount / $total) * 100.0, 2);
        $integrationPercent = round(($integrationCount / $total) * 100.0, 2);
        $e2ePercent = round(($e2eCount / $total) * 100.0, 2);

        $antiPattern = null;
        $isHealthy = false;

        if ($e2ePercent > 30.0 || $unitPercent < 40.0) {
            $antiPattern = \'ICE_CREAM_CONE\';
        } elseif ($integrationPercent > 60.0) {
            $antiPattern = \'HOURGLASS\';
        } else {
            $isHealthy = ($unitPercent >= 60.0 && $e2ePercent <= 15.0);
        }

        return [
            \'is_healthy\' => $isHealthy,
            \'anti_pattern\' => $antiPattern,
            \'unit_percent\' => $unitPercent,
            \'integration_percent\' => $integrationPercent,
            \'e2e_percent\' => $e2ePercent,
        ];
    }

    public function calculateEstimatedRuntime(
        int $unitCount,
        int $integrationCount,
        int $e2eCount,
        float $unitMs = 2.0,
        float $integrationMs = 50.0,
        float $e2eMs = 3000.0
    ): float {
        if ($unitCount < 0 || $integrationCount < 0 || $e2eCount < 0) {
            throw new InvalidArgumentException(\'Los conteos de pruebas no pueden ser negativos.\');
        }

        $totalMs = ($unitCount * $unitMs) + ($integrationCount * $integrationMs) + ($e2eCount * $e2eMs);

        return round($totalMs / 1000.0, 2);
    }
}
',
        'explanation' => 'La clase TestPyramidAuditor encapsula la logica de gobierno de calidad del proyecto. Al calcular proporciones y detectar desviaciones como ICE_CREAM_CONE, permite a los lideres tecnicos auditar automaticamente en CI si los desarrolladores estan abusando de pruebas funcionales lentas en lugar de solidificar la logica de dominio en pruebas unitarias puras.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Piramide de Testing & Estrategia de Calidad',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la principal desventaja de tener una suite dominada casi exclusivamente por pruebas End-to-End (Cono de Helado)?',
                'options' => [
                    'a' => 'Requieren escribir demasiado codigo PHP puro y carecen de aserciones booleanas.',
                    'b' => 'Son lentas de ejecutar, altamente propensas a falsos positivos (flakiness) y proporcionan muy poco aislamiento sobre la causa exacta de una falla.',
                    'c' => 'No permiten interactuar con el motor de base de datos relacional MySQL.',
                    'd' => 'Impiden que Symfony pueda compilar su contenedor de inyeccion de dependencias en modo debug.',
                ],
                'correct' => 'b',
                'explanation' => 'Las pruebas E2E atraviesan toda la pila tecnologica (red, servidor web, navegador, base de datos). Una falla puede deberse a un timeout de red, un selector CSS modificado o un deadlock temporal, demandando costosas horas de depuracion.',
            ],
            [
                'id' => 'q2',
                'question' => 'En la taxonomia de Sandi Metz, ¿que tipo de mensajes NUNCA deben ser probados directamente mediante aserciones o mocks?',
                'options' => [
                    'a' => 'Los mensajes entrantes de consulta (Incoming Queries).',
                    'b' => 'Los mensajes privados que un objeto se envia a si mismo.',
                    'c' => 'Los mensajes salientes de comando con efectos secundarios.',
                    'd' => 'Los mensajes que retornan valores escalares booleanos o enteros.',
                ],
                'correct' => 'b',
                'explanation' => 'Probar metodos o mensajes privados acopla las pruebas a la implementacion interna del objeto, impidiendo refactorizar el codigo sin romper las pruebas aunque el comportamiento publico permanezca intacto.',
            ],
            [
                'id' => 'q3',
                'question' => 'Si una suite tiene 1,000 pruebas unitarias (2ms c/u), 200 de integracion (50ms c/u) y 10 E2E (3,000ms c/u), ¿cual es la distribucion porcentual aproximada y su tiempo total de ejecucion?',
                'options' => [
                    'a' => '82.6% Unit, 16.5% Integration, 0.8% E2E; tiempo total ~42 segundos.',
                    'b' => '50% Unit, 40% Integration, 10% E2E; tiempo total ~10 minutos.',
                    'c' => '10% Unit, 20% Integration, 70% E2E; tiempo total ~1 hora.',
                    'd' => '33% en cada nivel; tiempo total ~2 segundos.',
                ],
                'correct' => 'a',
                'explanation' => 'Total de pruebas: 1,210. 1000/1210 = 82.6% unitarias; 200/1210 = 16.5% integracion; 10/1210 = 0.8% E2E. Tiempo: (1000*2 + 200*50 + 10*3000) = 2,000 + 10,000 + 30,000 = 42,000 ms = 42 segundos. Es una distribucion piramidal ejemplar.',
            ],
        ],
    ],
];
