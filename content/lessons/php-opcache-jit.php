<?php

declare(strict_types=1);

return [
    'slug' => 'php-opcache-jit',
    'title' => 'OpCache, Preloading, JIT Compiler & Optimización de Bytecode',
    'module' => 'PHP Moderno',
    'minutes' => 40,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'OpCache almacena los Opcodes precompilados de PHP en memoria compartida (SHM), eliminando el costo de leer archivos del disco, tokenizarlos y parsearlos en cada petición. El compilador JIT (Just-In-Time) de PHP 8 compila Opcodes frecuentes directamente a instrucciones de máquina x86-64/ARM.',
        'problem' => 'Muchos desarrolladores asumen que habilitar JIT acelerará automáticamente sus aplicaciones Symfony por un factor de 10x, sin comprender que las aplicaciones web están limitadas por operaciones de Entrada/Salida (I/O-bound: base de datos, red, disco) y no por saturación de CPU.',
        'solution' => 'Dominar el pipeline de 4 fases del Zend Engine, dimensionar la memoria compartida de OpCache para mantener una tasa de aciertos (Hit Rate) superior al 99.5%, implementar Preloading en producción y entender los casos de uso reales de JIT.',
        'problem_label' => 'El Problema: La Falacia de la Bala de Plata de JIT y Cachés Ineficientes',
        'solution_label' => 'La Solución Senior: Optimización de Bytecode, Preloading y Telemetría de OpCache',
    ],
    'mental_model' => [
        'title' => 'El Traductor en Vivo vs La Enciclopedia Plastificada y la Cortadora Láser',
        'analogy' => 'Imagina una biblioteca de textos en ruso. En **PHP sin OpCache**, cada vez que un cliente pide un libro, contratas a un traductor que lee el libro, analiza la gramática palabra por palabra y se lo dicta en español al cliente. Eso es Lexing, Parsing y Compilación de Opcodes en CADA petición HTTP. Con **OpCache**, el traductor traduce el libro la primera vez y deja las páginas plastificadas en una mesa pública compartida en memoria RAM (Shared Memory). Cada nuevo cliente solo lee las páginas plastificadas al instante sin traducir nada. ¿Y qué es **JIT (Just-In-Time Compiler)**? JIT observa qué párrafos específicos lee la gente 10,000 veces seguidas (Hot Traces) y, en lugar de leer las páginas impresas, utiliza una cortadora láser para grabar esas instrucciones directamente en un chip de silicio (código máquina nativo de la CPU), saltándose por completo la máquina virtual de PHP.',
        'ascii_diagram' => 'CÓDIGO FUENTE (Controller.php en disco)
       │
       ▼ [1. Lexer: Tokenización] ──> [2. Parser: Árbol de Sintaxis Abstracta (AST)]
       │
       ▼ [3. Compiler: Emisión de Opcodes Zend]
       │
┌──────┴──────────────────────────────────────────────────────┐
│                    MEMORIA COMPARTIDA (SHM)                 │
│  [OpCache Shared Memory Segment]                           │
│  ├─ Opcodes precompilados de todo el framework              │
│  ├─ Strings internadas inmutables                          │
│  └─ Preloading (Symfony config/preload.php al inicio)       │
└──────┬──────────────────────────────────────────────────────┘
       │
       ├──> [Modo Estándar]: Zend VM interpreta Opcodes en bucle C
       │
       └──> [Compilador JIT (Tracing JIT)]:
            DynASM compila \'Hot Code\' a instrucciones nativas CPU
            (movq, addq, jmp) -> Ejecución directa en hardware',
        'key_concept' => 'OpCache elimina el 70% al 80% de la latencia en aplicaciones web al evitar recompilar código fuente. JIT optimiza tareas intensivas en CPU (matemáticas, compresión, IA), pero tiene poco impacto en cuellos de botella de red y base de datos.',
    ],
    'internals' => [
        'title' => 'El Pipeline de Ejecución de Zend Engine y el Ciclo JIT',
        'steps' => [
            [
                'phase' => '1. Lexing & Parsing (AST)',
                'description' => 'El Lexer escanea el texto del archivo PHP y produce tokens. El Parser evalúa la gramática según las reglas sintácticas del lenguaje y construye el AST (Abstract Syntax Tree).',
            ],
            [
                'phase' => '2. Compilación a Opcodes',
                'description' => 'El compilador recorre el AST y emite instrucciones de bajo nivel llamadas Zend Opcodes (ej. ZEND_ADD, ZEND_FETCH_DIM_R, ZEND_INIT_METHOD_CALL).',
            ],
            [
                'phase' => '3. Almacenamiento en Memoria Compartida (SHM)',
                'description' => 'OpCache guarda los Opcodes y las clases resultantes en un segmento de memoria compartida (mmap). Todas las peticiones posteriores leen estos Opcodes sin tocar el disco ni compilar.',
            ],
            [
                'phase' => '4. Preloading de Clases (opcache.preload)',
                'description' => 'Al arrancar PHP-FPM, un script de inicio (como config/preload.php en Symfony) compila y ancla en memoria permanente las clases centrales del framework, resolviendo dependencias una sola vez para siempre.',
            ],
            [
                'phase' => '5. Compilación JIT en Tiempo de Ejecución',
                'description' => 'El motor JIT monitoriza qué secuencias de Opcodes se ejecutan repetidamente (\'hot spots\'). Cuando superan un umbral, las traduce a código máquina nativo para la arquitectura de la CPU actual.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Por qué JIT no acelera tu API Symfony un 1000%',
            'icon' => 'activity',
            'content' => 'Existe una ley fundamental en ingeniería de sistemas llamada **Ley de Amdahl**: la aceleración de un sistema está limitada por la proporción del tiempo que depende de la parte mejorada.

En una aplicación web típica (Symfony / Laravel / API Platform):
• **80% del tiempo:** Esperando consultas de base de datos relacionales (PostgreSQL/MySQL), caché Redis, llamadas HTTP externas y serialización JSON (I/O-Bound).
• **15% del tiempo:** Inicialización de clases e inyección de dependencias (resuelto por OpCache y Preloading).
• **5% del tiempo:** Operaciones matemáticas puras de CPU.

Aunque el compilador JIT reduzca el tiempo de CPU al instante (5% -> 0.5%), la ganancia global en la respuesta HTTP apenas será del 2% al 4%. JIT brilla en tareas CPU-bound: renderizado 3D, compresión, parsers de texto y machine learning.',
            'takeaways' => 'Para acelerar aplicaciones web, optimiza tus consultas SQL, usa índices y reduce serializaciones antes de esperar milagros de JIT.',
        ],
        [
            'title' => 'Telemetría de OpCache y el Fenómeno de \'Cache Churn\'',
            'icon' => 'database',
            'content' => 'Si asignas `opcache.memory_consumption = 64` (el valor por defecto) en una aplicación Symfony moderna con cientos de dependencias en `vendor/`, ocurrirá una catástrofe silenciosa:

1. OpCache llena rápidamente los 64 MB de memoria.
2. La métrica \'wasted memory\' sube al superar el umbral de `opcache.max_wasted_percentage`.
3. OpCache se reinicia de emergencia (Restart), expulsando todas las clases de la memoria.
4. Todos los workers colapsan el disco intentando recompilar todo el código simultáneamente (**Cache Churn**).

En producción, mantén `opcache.memory_consumption` en al menos 256 MB o 512 MB, y monitoriza que el **Hit Rate sea mayor a 99.5%** continuamente mediante telemetría.',
            'code' => '; Configuración recomendada para Symfony en producción
opcache.enable=1
opcache.memory_consumption=256
opcache.interned_strings_buffer=16
opcache.max_accelerated_files=20000
opcache.validate_timestamps=0 ; Cero I/O de disco para verificar fechas
opcache.preload=/var/www/html/config/preload.php
opcache.preload_user=www-data',
            'takeaways' => 'En producción, pon opcache.validate_timestamps=0 para que PHP nunca verifique el disco, y haz reload graceful de PHP-FPM en cada deploy.',
        ],
    ],
    'video' => [
        'title' => 'PHP Performance Demystified: Internals, Profiling, and the Road to PHP 9',
        'speaker' => 'Volker Dusch',
        'youtube_id' => 'PYhuj7E16Js',
        'duration' => '45 min',
        'description' => 'Una exploración profunda del compilador JIT, OpCache, internals de Zend VM y optimización de infraestructura.',
        'key_takeaways' => [
            'La diferencia técnica entre Function JIT y Tracing JIT en PHP 8.',
            'Cómo diagnosticar si OpCache está sufriendo reinicios cíclicos por falta de memoria.',
            'El impacto del Preloading en el tiempo de arranque de contenedores Symfony.',
            'Métricas clave de telemetría para monitorear en Datadog o Prometheus.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Infrastructure/Telemetry/OpCacheHealthChecker.php',
        'title' => 'Monitor de Telemetría y Salud de OpCache en PHP 8.4',
        'tag' => 'PHP 8.4 OpCache Telemetry & Health',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Telemetry;

/**
 * Evalúa la salud operativa de OpCache calculando la tasa de aciertos (Hit Rate)
 * y detectando degradación por agotamiento de memoria compartida.
 */
class OpCacheHealthChecker
{
    private const float MINIMUM_HEALTHY_HIT_RATE = 95.0;

    /**
     * Evalúa el estado de OpCache a partir del array devuelto por opcache_get_status().
     *
     * @param array<string, mixed> $status Datos de telemetría de OpCache
     * @return array{healthy: bool, hit_rate_percentage: float, reason: string}
     */
    public function calculateHealth(array $status = []): array
    {
        $opcacheEnabled = (bool) ($status[\'opcache_enabled\'] ?? false);
        if (!$opcacheEnabled) {
            return [
                \'healthy\' => false,
                \'hit_rate_percentage\' => 0.0,
                \'reason\' => \'OpCache no está habilitado en este entorno.\',
            ];
        }

        $stats = $status[\'opcache_statistics\'] ?? [];
        $hits = (int) ($stats[\'hits\'] ?? 0);
        $misses = (int) ($stats[\'misses\'] ?? 0);
        $totalRequests = $hits + $misses;

        if ($totalRequests === 0) {
            return [
                \'healthy\' => true,
                \'hit_rate_percentage\' => 100.0,
                \'reason\' => \'OpCache habilitado, sin peticiones registradas aún.\',
            ];
        }

        $hitRate = ($hits / $totalRequests) * 100.0;
        $isHealthy = $hitRate >= self::MINIMUM_HEALTHY_HIT_RATE;

        return [
            \'healthy\' => $isHealthy,
            \'hit_rate_percentage\' => round($hitRate, 2),
            \'reason\' => $isHealthy
                ? \'OpCache opera en rangos óptimos de rendimiento.\'
                : sprintf(\'Hit Rate crítico (%.2f%%) por debajo del umbral mínimo (%.1f%%).\', $hitRate, self::MINIMUM_HEALTHY_HIT_RATE),
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior confía ciegamente en que JIT solucionará la lentitud de su sistema y nunca mira los logs de OpCache. Un ingeniero Senior instrumenta métricas de hits y misses, calibra la memoria compartida por encima de los requisitos del framework, inhabilita la validación de timestamps en producción y diseña despliegues atómicos sin tiempo de inactividad.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Ignorancia de OpCache y lecturas constantes de disco',
            'code' => '// Configuración por defecto: validate_timestamps=1
// PHP verifica stat() en el disco para CADA archivo en CADA petición!
function checkStatus() {
    $info = opcache_get_status();
    if ($info[\'opcache_enabled\']) {
        return "OK"; // Asume que si está prendido, todo está perfecto
    }
    return "DISABLED";
}',
            'flaws' => [
                'Ignora si la memoria está saturada o si el Hit Rate está en 40%, dando falsos positivos de salud.',
                'Permite que validate_timestamps=1 haga miles de llamadas stat() a disco innecesarias.',
                'No contempla telemetría estructurada para alertar al equipo de SRE.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Telemetría matemática de Hit Rate y umbrales claros',
            'code' => 'declare(strict_types=1);

namespace App\\Infrastructure\\Telemetry;

class OpCacheHealthChecker
{
    public function calculateHealth(array $status = []): array
    {
        if (!($status[\'opcache_enabled\'] ?? false)) {
            return [\'healthy\' => false, \'hit_rate_percentage\' => 0.0, \'reason\' => \'OpCache disabled\'];
        }

        $stats = $status[\'opcache_statistics\'] ?? [];
        $hits = (int) ($stats[\'hits\'] ?? 0);
        $misses = (int) ($stats[\'misses\'] ?? 0);
        $total = $hits + $misses;

        $rate = $total > 0 ? ($hits / $total) * 100.0 : 100.0;
        return [
            \'healthy\' => $rate >= 95.0,
            \'hit_rate_percentage\' => round($rate, 2),
            \'reason\' => $rate >= 95.0 ? \'Optimal\' : \'Low hit rate\'
        ];
    }
}',
            'rationale' => [
                'Calcula matemáticamente el Hit Rate sobre el total de consultas (hits + misses).',
                'Establece umbrales formales de alerta (95%) para integración con Prometheus / Health Checks.',
                'Maneja edge cases como cero peticiones iniciales sin división por cero.',
            ],
            'trade_offs' => [
                'Con validate_timestamps=0, cualquier cambio en código fuente requiere recargar PHP-FPM obligatoriamente.',
                'El Preloading consume memoria compartida que no puede ser reclamada por workers individuales sin reiniciar FPM.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'JIT in PHP 8',
            'source' => 'PHP RFC: JIT',
            'quote' => 'JIT no es una panacea para aplicaciones web comunes limitadas por IO, sino una puerta hacia nuevas fronteras donde PHP puede competir en cargas de CPU pesadas.',
            'author' => 'Dmitry Stogov',
            'explanation' => 'El creador de JIT en PHP aclara que la computación numérica se beneficia enormemente, mientras que las apps web dependen de OpCache.',
        ],
        [
            'topic' => 'Bytecode Caching',
            'source' => 'Zend OpCache Documentation',
            'quote' => 'Al evitar la sobrecarga de recompilar código en cada solicitud, OpCache ofrece una mejora de velocidad sustancial sin cambiar una sola línea de código.',
            'author' => 'Zeev Suraski',
            'explanation' => 'OpCache es el componente individual más importante para el rendimiento en cualquier instalación de PHP en producción.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP Manual Oficial: Funciones y Configuración de OpCache',
            'url' => 'https://www.php.net/manual/es/book.opcache.php',
            'description' => 'Directivas oficiales de configuración, preloading y funciones de inspección.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Docs: OpCache & Preloading Best Practices',
            'url' => 'https://symfony.com/doc/current/performance.html#use-opcache-class-preloading',
            'description' => 'Guía oficial de Symfony para optimizar el rendimiento en producción mediante preloading.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'The PHP 8 JIT Explained',
            'url' => 'https://stitcher.io/blog/php-8-jit-setup',
            'description' => 'Análisis exhaustivo de Brent Roose sobre cómo configurar y medir el impacto real de JIT.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Verificador de Telemetría de OpCache',
        'objective' => 'Implementar la clase OpCacheHealthChecker para evaluar el estado de OpCache y alertar cuando la tasa de aciertos (Hit Rate) sea inaceptable.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'OpCacheHealthChecker\' en el namespace \'App\\Infrastructure\\Telemetry\'.
3. Implementa el método \'calculateHealth(array $status = []): array\'.
4. Calcula la tasa a partir de \'hits\' y \'misses\' presentes en \'$status[\'opcache_statistics\']\'.
5. Considera saludable un Hit Rate >= 95.0%.',
        'filename' => 'src/Infrastructure/Telemetry/OpCacheHealthChecker.php',
        'guide' => [
            'explanation' => 'Extrae los valores de hits y misses asegurándote de no dividir por cero si la suma es 0. Retorna un array asociativo con la clave \'healthy\' (booleano) y \'hit_rate_percentage\' (flotante redondeado).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] La tasa de aciertos (Hit Rate) de OpCache se calcula como hits / (hits + misses) * 100. Una tasa saludable en producción supera el 95%.',
            ],
            [
                'text' => '[Pista 2: Estructura] Implementa el método public function calculateHealth(array $status = []): array. Extrae las estadísticas de $status[\'opcache_statistics\'][\'hits\'] y misses.',
            ],
            [
                'text' => '[Pista 3: Snippet] $total = $hits + $misses; $rate = $total > 0 ? ($hits / $total) * 100.0 : 100.0; return [\'healthy\' => $rate >= 95.0, \'hit_rate_percentage\' => round($rate, 2)];.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Telemetry;

class OpCacheHealthChecker
{
    /**
     * @param array<string, mixed> $status
     * @return array{healthy: bool, hit_rate_percentage: float}
     */
    public function calculateHealth(array $status = []): array
    {
        // TODO: Implementa la lógica de cálculo con hits y misses
        return [
            \'healthy\' => false,
            \'hit_rate_percentage\' => 0.0,
        ];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Telemetry;

class OpCacheHealthChecker
{
    /**
     * @param array<string, mixed> $status
     * @return array{healthy: bool, hit_rate_percentage: float}
     */
    public function calculateHealth(array $status = []): array
    {
        $enabled = (bool) ($status[\'opcache_enabled\'] ?? false);
        if (!$enabled) {
            return [
                \'healthy\' => false,
                \'hit_rate_percentage\' => 0.0,
            ];
        }

        $stats = $status[\'opcache_statistics\'] ?? [];
        $hits = (int) ($stats[\'hits\'] ?? 0);
        $misses = (int) ($stats[\'misses\'] ?? 0);
        $total = $hits + $misses;

        if ($total === 0) {
            return [
                \'healthy\' => true,
                \'hit_rate_percentage\' => 100.0,
            ];
        }

        $hitRate = ($hits / $total) * 100.0;

        return [
            \'healthy\' => $hitRate >= 95.0,
            \'hit_rate_percentage\' => round($hitRate, 2),
        ];
    }
}
',
        'explanation' => 'El calculador extrae de forma segura hits y misses del reporte de OpCache, previene divisiones por cero y entrega un indicador de salud booleano listo para integrarse en sondas de liveness de Kubernetes o endpoints de monitoreo.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: OpCache, Preloading, JIT Compiler & Optimización de Bytecode',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué poner \'opcache.validate_timestamps=0\' en producción genera un aumento drástico en el rendimiento?',
                'options' => [
                    'a' => 'Porque elimina todas las llamadas al sistema operativo stat() para verificar si los archivos PHP cambiaron en el disco.',
                    'b' => 'Porque permite a JIT convertir todo el código a lenguaje ensamblador de forma desatendida.',
                    'c' => 'Porque inhabilita el recolector de basura de la memoria zval.',
                    'd' => 'Porque borra automáticamente la memoria caché cuando ocurre un error 500.',
                ],
                'correct' => 'a',
                'explanation' => 'Con validate_timestamps=0, PHP confía ciegamente en los Opcodes en memoria y nunca verifica el sistema de archivos, ahorrando millones de syscalls a disco bajo alto tráfico.',
            ],
            [
                'id' => 'q2',
                'question' => '¿En cuál de los siguientes escenarios un compilador JIT aportará el MAYOR beneficio medible en PHP?',
                'options' => [
                    'a' => 'En una API REST que consulta una base de datos MySQL y serializa JSON.',
                    'b' => 'En una aplicación que renderiza plantillas Twig simples y las envía a Nginx.',
                    'c' => 'En un worker de procesamiento que calcula fractales matemáticos o procesa matrices en memoria pura.',
                    'd' => 'En un endpoint de autenticación que valida tokens JWT llamando a un servicio externo.',
                ],
                'correct' => 'c',
                'explanation' => 'JIT compila bucles de cálculo intensivo de CPU a código máquina nativo. En tareas I/O-bound (bases de datos o APIs externas), el cuello de botella es la red y no la velocidad de ejecución de Opcodes.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué problema ocurre si opcache.memory_consumption es insuficiente para almacenar todos los scripts del proyecto?',
                'options' => [
                    'a' => 'PHP lanza una excepción fatal OutOfMemoryException inmediatamente.',
                    'b' => 'OpCache sufre \'Cache Churn\': se reinicia continuamente expulsando clases y obligando a recompilar desde disco una y otra vez.',
                    'c' => 'Composer desactiva automáticamente las clases que no caben en memoria.',
                    'd' => 'Nginx redirige el tráfico a un servidor de respaldo.',
                ],
                'correct' => 'b',
                'explanation' => 'El Cache Churn ocurre cuando la memoria de OpCache se agota, forzando constantes reinicios del segmento de memoria compartida y saturando el CPU y el disco del servidor.',
            ],
        ],
    ],
];
