<?php

declare(strict_types=1);

return [
    'slug' => 'perf-profiling-blackfire',
    'title' => 'Profiling de Memoria & CPU en Symfony',
    'module' => 'performance',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En la optimizacion de rendimiento de software, la intuicion humana es notoriamente enganosa. Los desarrolladores suelen perder dias intentando optimizar bucles for o concatenaciones de strings, cuando el verdadero cuello de botella es una consulta SQL no indexada, la instanciacion masiva de entidades en memoria o un servicio que carga 20 MB de datos para mostrar un simple contador.

Un desarrollador Senior nunca optimiza a ciegas; utiliza Profiling Cientifico con herramientas como Blackfire.io o Tideways. Un profiler instrumenta la ejecucion de la Zend VM y genera un grafo aciclico de llamadas (Call Graph), midiendo cuatro dimensiones fisicas criticas: Tiempo de CPU, Tiempo de Pared (Wall Time / I/O Wait), Consumo de Memoria RAM (Peak Memory) y Numero de Llamadas a Funciones.

En esta leccion implementamos un temporizador de ejecucion de alta precision (ExecutionTimer) utilizando la funcion monotonica hrtime(true) y las metricas reales de memoria del sistema operativo memory_get_usage(true) para establecer checkpoints cientificos de rendimiento.',
        'problem' => 'En la optimizacion de rendimiento de software, la intuicion humana es notoriamente enganosa. Los desarrolladores suelen perder dias intentando optimizar bucles for o concatenaciones de strings, cuando el verdadero cuello de botella es una consulta SQL no indexada, la instanciacion masiva de entidades en memoria o un servicio que carga 20 MB de datos para mostrar un simple contador.',
        'solution' => 'Un desarrollador Senior nunca optimiza a ciegas; utiliza Profiling Cientifico con herramientas como Blackfire.io o Tideways. Un profiler instrumenta la ejecucion de la Zend VM y genera un grafo aciclico de llamadas (Call Graph), midiendo cuatro dimensiones fisicas criticas: Tiempo de CPU, Tiempo de Pared (Wall Time / I/O Wait), Consumo de Memoria RAM (Peak Memory) y Numero de Llamadas a Funciones.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Telemetria en Tiempo Real de un Coche de Formula 1',
        'concept' => 'Piensa en un equipo de ingenieria de Formula 1 durante una sesion de clasificacion:
1. EL PILOTO INGENUO (El Desarrollador sin Profiler): Vuelve a los boxes y dice: \'Siento que el auto no dobla rapido\'. Los mecanicos cambian el aleron delantero a ciegas, pero el tiempo por vuelta no mejora.
2. LA TELEMETRIA CIENTIFICA (El Profiler): Los ingenieros no escuchan sensaciones; descargan los 500 sensores de telemetria del coche: presion de neumaticos curva por curva, temperatura de frenos, apertura del acelerador y flujo de combustible.
3. EL HALLAZGO INESPERADO: La telemetria demuestra que el auto pierde 0.4 segundos en la recta principal porque la caja de cambios tarda 80 milisegundos de mas en engranar la 7ma marcha, no en la curva.

El profiler es la telemetria de tu backend: te muestra con precision quirurgica en que linea exacta de codigo se gasta el 90% del tiempo de CPU o la memoria de la aplicacion.',
        'ascii_diagram' => 'DIMENSIONES FISICAS DEL PROFILING CIENTIFICO:

+-------------------------------------------------------------+
| WALL TIME (Tiempo de Pared / Total de Peticion)             |
|                                                             |
| +-------------------------+ +-----------------------------+ |
| | CPU TIME (Procesamiento)| | I/O WAIT (Espera de Red/DB) | |
| | - Compilacion PHP       | | - Socket MySQL esperando SQL| |
| | - Hash criptografico    | | - Socket Redis esperando    | |
| | - Algoritmos en memoria | | - Lectura de archivo en SSD | |
| +-------------------------+ +-----------------------------+ |
+-------------------------------------------------------------+

METRICAS DE MEMORIA:
- memory_get_usage(true): Memoria fisica real asignada por Linux (malloc)
- memory_get_peak_usage(true): Maximo historico de RAM consumido por la peticion
- hrtime(true): Tiempo monotonico del hardware en nanosegundos (inmune a saltos NTP)
',
        'analogy' => 'Piensa en un equipo de ingenieria de Formula 1 durante una sesion de clasificacion:
1. EL PILOTO INGENUO (El Desarrollador sin Profiler): Vuelve a los boxes y dice: \'Siento que el auto no dobla rapido\'. Los mecanicos cambian el aleron delantero a ciegas, pero el tiempo por vuelta no mejora.
2. LA TELEMETRIA CIENTIFICA (El Profiler): Los ingenieros no escuchan sensaciones; descargan los 500 sensores de telemetria del coche: presion de neumaticos curva por curva, temperatura de frenos, apertura del acelerador y flujo de combustible.
3. EL HALLAZGO INESPERADO: La telemetria demuestra que el auto pierde 0.4 segundos en la recta principal porque la caja de cambios tarda 80 milisegundos de mas en engranar la 7ma marcha, no en la curva.

El profiler es la telemetria de tu backend: te muestra con precision quirurgica en que linea exacta de codigo se gasta el 90% del tiempo de CPU o la memoria de la aplicacion.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En PHP, la medicion precisa del tiempo de ejecucion NO debe realizarse con microtime(true). microtime() lee el reloj del sistema de pared (wall-clock), el cual es vulnerable a corrupciones si el demonio NTP del servidor ajusta la hora durante la peticion (pudiendo arrojar duraciones negativas).',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'En su lugar, se utiliza hrtime(true), que lee el reloj monotonico de hardware del procesador con precision de nanosegundos.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Para medir memoria, pasar el argumento booleano true a memory_get_usage(true) mide la memoria real asignada por el kernel de Linux al proceso (system alloc), en lugar del heap interno de la Zend VM.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Reglas de oro de optimizacion descubiertas mediante profiling con Blackfire: 1. Reducir el numero de llamadas: 10,000 llamadas a una funcion muy pequena acumulan costo de cambio de stack en la Zend VM. 2. Hidratacion de Doctrine: hidratar 1,000 entidades con ciclo de vida completo consume 30 MB de RAM. Usar DQL getArrayResult() para lecturas masivas reduce el uso de memoria a 1.5 MB y el tiempo a un 20%. 3. Autoloading: utilizar Composer dump-autoload -o --classmap-authoritative para evitar lecturas de filesystem en cada require.',
        ],
    ],
    'video' => [
        'title' => 'PHP profiling using Blackfire with Fabien Potencier',
        'speaker' => 'Fabien Potencier (Creador de Symfony)',
        'youtube_id' => 'xbOiPnFOdqw',
        'duration' => '45 min',
        'description' => 'Fabien Potencier demuestra en vivo como utilizar Blackfire para detectar cuellos de botella reales en aplicaciones Symfony, analizando grafos de llamadas, consumo de memoria y optimizaciones de bajo nivel.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Profiling;

use InvalidArgumentException;
use RuntimeException;

final class ExecutionTimer
{
    /**
     * @var array<string, array{start_ns: int, start_mem: int}>
     */
    private array $checkpoints = [];

    public function start(string $checkpoint): void
    {
        $name = trim($checkpoint);
        if ($name === \'\') {
            throw new InvalidArgumentException(\'El nombre del checkpoint no puede estar vacio.\');
        }

        $this->checkpoints[$name] = [
            \'start_ns\' => hrtime(true),
            \'start_mem\' => memory_get_usage(true),
        ];
    }

    /**
     * @return array{checkpoint: string, duration_ms: float, memory_delta_bytes: int, peak_memory_bytes: int}
     */
    public function stop(string $checkpoint): array
    {
        $name = trim($checkpoint);
        if (!isset($this->checkpoints[$name])) {
            throw new RuntimeException(sprintf(\'El checkpoint "%s" no ha sido iniciado.\', $name));
        }

        $endNs = hrtime(true);
        $endMem = memory_get_usage(true);
        $peakMem = memory_get_peak_usage(true);

        $startData = $this->checkpoints[$name];
        unset($this->checkpoints[$name]);

        $durationMs = round(($endNs - $startData[\'start_ns\']) / 1_000_000.0, 3);
        $memDelta = $endMem - $startData[\'start_mem\'];

        return [
            \'checkpoint\' => $name,
            \'duration_ms\' => $durationMs,
            \'memory_delta_bytes\' => $memDelta,
            \'peak_memory_bytes\' => $peakMem,
        ];
    }

    public function reset(): void
    {
        $this->checkpoints = [];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior optimiza por adivinacion, haciendo el codigo mas ilegible para ahorrar 3 microsegundos. El desarrollador Senior aplica telemetria y profiling: mide con Blackfire, identifica el metodo responsable del 80% del tiempo de respuesta, optimiza con precision quirurgica y valida con una prueba automatizada de regresion de rendimiento.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Cambia comillas dobles por comillas simples creyendo que acelera el interprete PHP.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Usa hrtime y profilers para descubrir que el 85% del tiempo de la peticion es I/O wait esperando una query SQL no sargable.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Optimizacion Prematura',
            'source' => 'Structured Programming with go to Statements / ACM Computing Surveys',
            'quote' => 'La optimizacion prematura es la raiz de todos los males (o al menos de la mayoria de ellos) en programacion.',
            'author' => 'Donald Knuth',
            'explanation' => 'No optimices sin haber medido primero el perfil real de ejecucion.',
        ],
        [
            'topic' => 'El Valor del Profiling',
            'source' => 'Blackfire.io Documentation',
            'quote' => 'No puedes mejorar lo que no puedes medir. El profiling te permite reemplazar asunciones sobre el rendimiento con hechos cientificos reproducibles.',
            'author' => 'Fabien Potencier',
            'explanation' => 'Los grafos de llamadas exponen la relacion causa-efecto de cada milisegundo consumido.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Blackfire.io: PHP Performance Testing and Profiling',
            'url' => 'https://blackfire.io/docs/introduction',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PHP Manual: hrtime (High resolution monotonic timer)',
            'url' => 'https://www.php.net/manual/es/function.hrtime.php',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Temporizador de Precision y Diagnostico de Memoria (ExecutionTimer)',
        'objective' => 'Implementar la clase ExecutionTimer con start(), stop() y reset(), midiendo tiempo de alta resolucion con hrtime(true), capturando asignaciones de memoria con memory_get_usage(true) y memory_get_peak_usage(true), y calculando duration_ms y memory_delta_bytes.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Performance\\Profiling.
2. Implementa la clase ExecutionTimer.
3. Implementa function start(string $checkpoint): void; si el nombre esta vacio lanza \\InvalidArgumentException.
4. En start(), registra la marca de tiempo de hardware con hrtime(true) y la memoria con memory_get_usage(true).
5. Implementa function stop(string $checkpoint): array; si no fue iniciado lanza \\RuntimeException.
6. En stop(), calcula duration_ms dividiendo los nanosegundos entre 1_000_000.0 y redondeando a 3 decimales.
7. Calcula memory_delta_bytes restando la memoria final de la inicial, y captura peak_memory_bytes con memory_get_peak_usage(true).
8. Retorna el array con checkpoint, duration_ms, memory_delta_bytes y peak_memory_bytes.
9. Implementa function reset(): void vaciando los checkpoints.',
        'filename' => 'src/Performance/Profiling/ExecutionTimer.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array privado $checkpoints = [].',
                'Paso 2: En start(), sanitiza el nombre con trim() y lanza InvalidArgumentException si queda vacio.',
                'Paso 3: Almacena hrtime(true) y memory_get_usage(true) en el array de checkpoints.',
                'Paso 4: En stop(), comprueba isset(); si no existe, lanza RuntimeException.',
                'Paso 5: Calcula las diferencias, elimina el checkpoint y retorna el resultado estructurado.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] hrtime(true) retorna nanosegundos como entero de 64 bits. 1 milisegundo = 1,000,000 nanosegundos.',
            ],
            [
                'text' => '[Pista 2: Estructura] duration_ms = round(($endNs - $startNs) / 1000000.0, 3); memory_delta_bytes = $endMem - $startMem.',
            ],
            [
                'text' => '[Pista 3: Snippet] return [\'checkpoint\' => $name, \'duration_ms\' => $durationMs, \'memory_delta_bytes\' => $memDelta, \'peak_memory_bytes\' => memory_get_peak_usage(true)];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Profiling;

use InvalidArgumentException;
use RuntimeException;

class ExecutionTimer
{
    /**
     * @var array<string, array{start_ns: int, start_mem: int}>
     */
    private array $checkpoints = [];

    public function start(string $checkpoint): void
    {
        // TODO: Validar nombre no vacio (lanzar InvalidArgumentException)
        // TODO: Almacenar hrtime(true) y memory_get_usage(true)
    }

    public function stop(string $checkpoint): array
    {
        // TODO: Lanzar RuntimeException si no existe checkpoint
        // TODO: Calcular duration_ms y memory_delta_bytes
        // TODO: Obtener memory_get_peak_usage(true)
        // TODO: Retornar metricas estructuradas
        return [];
    }

    public function reset(): void
    {
        // TODO: Vaciar checkpoints
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Profiling;

use InvalidArgumentException;
use RuntimeException;

class ExecutionTimer
{
    /**
     * @var array<string, array{start_ns: int, start_mem: int}>
     */
    private array $checkpoints = [];

    public function start(string $checkpoint): void
    {
        $name = trim($checkpoint);
        if ($name === \'\') {
            throw new InvalidArgumentException(\'El nombre del checkpoint no puede estar vacio.\');
        }

        $this->checkpoints[$name] = [
            \'start_ns\' => hrtime(true),
            \'start_mem\' => memory_get_usage(true),
        ];
    }

    public function stop(string $checkpoint): array
    {
        $name = trim($checkpoint);
        if (!isset($this->checkpoints[$name])) {
            throw new RuntimeException(sprintf(\'Checkpoint "%s" no iniciado.\', $name));
        }

        $endNs = hrtime(true);
        $endMem = memory_get_usage(true);
        $peakMem = memory_get_peak_usage(true);

        $startData = $this->checkpoints[$name];
        unset($this->checkpoints[$name]);

        $durationMs = round(($endNs - $startData[\'start_ns\']) / 1_000_000.0, 3);
        $memDelta = $endMem - $startData[\'start_mem\'];

        return [
            \'checkpoint\' => $name,
            \'duration_ms\' => $durationMs,
            \'memory_delta_bytes\' => $memDelta,
            \'peak_memory_bytes\' => $peakMem,
        ];
    }

    public function reset(): void
    {
        $this->checkpoints = [];
    }
}
',
        'explanation' => 'La clase ExecutionTimer proporciona mediciones cientificas de rendimiento. Al usar hrtime(true), garantiza mediciones monotonicas independientes de ajustes horarios de red, y con memory_get_usage(true) reporta el impacto real sobre la memoria RAM del sistema operativo.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Profiling de Memoria & CPU en Symfony',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por que se debe utilizar la funcion hrtime() en lugar de microtime() para medir lapsos de tiempo de ejecucion en PHP?',
                'options' => [
                    'a' => 'Porque hrtime() utiliza un reloj monotonico de hardware que nunca salta hacia atras ni es afectado por sincronizaciones NTP del sistema operativo.',
                    'b' => 'Porque microtime() solo admite valores negativos en servidores Linux.',
                    'c' => 'Porque hrtime() se ejecuta en la GPU de la tarjeta grafica.',
                    'd' => 'Porque microtime() requiere instalar una extension de terceros desde PECL.',
                ],
                'correct' => 'a',
                'explanation' => 'microtime() lee el reloj de pared, que puede ser alterado por ajustes de red NTP. hrtime() lee el contador monotonico del procesador con precision de nanosegundos, garantizando mediciones siempre positivas y precisas.',
            ],
            [
                'id' => 'q2',
                'question' => 'Al invocar memory_get_usage(true), ¿que indica el parametro booleano true?',
                'options' => [
                    'a' => 'Que formatee el resultado en megabytes con un string de texto.',
                    'b' => 'Que capture la memoria real asignada por el sistema operativo al proceso PHP (system alloc / malloc), en lugar de la memoria consumida por el heap interno de la Zend VM.',
                    'c' => 'Que libere la memoria RAM inmediatamente mediante recoleccion de basura.',
                    'd' => 'Que cifre el reporte de memoria con una clave simetrica.',
                ],
                'correct' => 'b',
                'explanation' => 'memory_get_usage() sin argumentos solo reporta lo que la Zend VM ha reservado para variables. Con true, reporta la huella real de memoria que el kernel de Linux le ha otorgado al proceso.',
            ],
            [
                'id' => 'q3',
                'question' => 'En un reporte de Blackfire, ¿que diferencia existe entre \'Wall Time\' y \'CPU Time\'?',
                'options' => [
                    'a' => 'Son sinonimos exactos que indican los segundos totales de la solicitud.',
                    'b' => 'Wall Time es el tiempo total transcurrido (incluyendo esperas de I/O a la base de datos o red), mientras que CPU Time es unicamente el tiempo que el procesador estuvo calculando instrucciones activamente.',
                    'c' => 'CPU Time solo se calcula en servidores de arquitectura ARM.',
                    'd' => 'Wall Time mide el tiempo que la pantalla del usuario tarda en encenderse.',
                ],
                'correct' => 'b',
                'explanation' => 'Si una peticion tarda 200ms de Wall Time pero solo 10ms de CPU Time, significa que 190ms se gastaron en esperas de I/O (bloqueos en la base de datos o latencia de red).',
            ],
        ],
    ],
];
