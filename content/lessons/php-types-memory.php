<?php

declare(strict_types=1);

return [
    'slug' => 'php-types-memory',
    'title' => 'Tipado Estricto, zvals, Refcount & Gestión de Memoria en Zend VM',
    'module' => 'PHP Moderno',
    'minutes' => 45,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'En el Zend Engine, cada variable está representada por una estructura en C de 16 bytes llamada zval (Zend Value). PHP gestiona la memoria mediante conteo de referencias (Refcounting) y optimiza las asignaciones a través de la semántica Copy-On-Write (COW).',
        'problem' => 'El manejo ingenuo de colecciones y arrays masivos provoca el temido \'Allowed memory size exhausted\'. Muchos desarrolladores creen erróneamente que pasar variables por referencia \'&$var\' ahorra memoria, cuando en realidad suele romper las optimizaciones nativas de Copy-On-Write.',
        'solution' => 'Comprender la anatomía interna de los zvals y la semántica Copy-On-Write, adoptar tipado estricto para habilitar optimizaciones de motor y utilizar Generadores (yield) para procesar millones de registros con un consumo constante de memoria O(1).',
        'problem_label' => 'El Problema: Explosión de Memoria y Mitos sobre Referencias',
        'solution_label' => 'La Solución Senior: Zvals, Copy-On-Write y Procesamiento en Streaming O(1)',
    ],
    'mental_model' => [
        'title' => 'Los Casilleros con Tarjeta Llave y la Fotocopiadora bajo Demanda',
        'analogy' => 'Imagina un almacén gigante con casilleros numerados. Cuando creas un array `$a = [1, 2, 3]`, no estás metiendo los datos en una caja llamada \'$a\'. Estás alquilando un casillero en la memoria RAM y recibiendo una tarjeta llave de plástico llamada **Zval** (una estructura de 16 bytes). En el casillero hay un contador de tarjetas emitidas: `refcount = 1`. Si luego haces `$b = $a`, PHP **no duplica el casillero ni copia el contenido**. Simplemente le entrega a `$b` una segunda tarjeta llave para el mismo casillero e incrementa el contador a `refcount = 2`. Ambos apuntan a los mismos datos exactos en memoria. Solo en el milisegundo en que uno de los dos intenta pintar las paredes del casillero (por ejemplo, `$b[] = 4`), PHP detiene la operación y activa la fotocopiadora: clona el casillero original, le entrega a `$b` el nuevo casillero y decrementa el refcount del anterior a 1. Esto es **Copy-On-Write (COW)**.',
        'ascii_diagram' => '1. ASIGNACIÓN INICIAL ($a = [1, 2, 3]):
   $a (Zval: IS_ARRAY) ──> [zend_array en RAM: refcount=1, data=[1, 2, 3]]

2. PASO POR VALOR ($b = $a): ¡Cero bytes duplicados!
   $a (Zval: IS_ARRAY) ──┐
                         ├──> [zend_array en RAM: refcount=2, data=[1, 2, 3]]
   $b (Zval: IS_ARRAY) ──┘

3. MUTACIÓN ($b[] = 4): Disparo de COPY-ON-WRITE (COW)
   $a (Zval: IS_ARRAY) ──> [zend_array en RAM: refcount=1, data=[1, 2, 3]]
   $b (Zval: IS_ARRAY) ──> [NUEVO zend_array en RAM: refcount=1, data=[1, 2, 3, 4]]

4. GENERADOR (yield): Memoria constante O(1)
   [Dataset de 1,000,000 transacciones]
         │
     yield chunk  ──> [Memoria RAM activa: Solo ~32 KB constantes]',
        'key_concept' => 'El paso de argumentos por valor en PHP es sumamente económico gracias a Copy-On-Write. Duplicar estructuras solo ocurre cuando una variable compartida es modificada.',
    ],
    'internals' => [
        'title' => 'Anatomía del Zval y el Recolector de Basura en Zend Engine',
        'steps' => [
            [
                'phase' => '1. Estructura del Zval en C (16 bytes)',
                'description' => 'En sistemas de 64 bits, una zval consta de una unión zend_value (8 bytes) que almacena enteros, flotantes o punteros a objetos/strings/arrays, y una cabecera zend_type_info (8 bytes) que guarda el tipo de dato y banderas de estado.',
            ],
            [
                'phase' => '2. Valores Inmediatos vs Valores con Refcount',
                'description' => 'Enteros, booleanos y flotantes se almacenan directamente dentro de la zval sin requerir memoria dinámica adicional. Arrays, objetos, recursos y strings no internadas reservan memoria en el heap y utilizan refcounting.',
            ],
            [
                'phase' => '3. Mecánica de Copy-On-Write (COW)',
                'description' => 'Cuando se modifica un valor compartido (refcount > 1), la máquina virtual Zend invoca SEPARATE_ARRAY(), clonando la estructura y separando los punteros para garantizar que la mutación sea aislada.',
            ],
            [
                'phase' => '4. El Recolector de Ciclos (Cycle Collector)',
                'description' => 'Si el objeto A apunta al objeto B y el objeto B apunta al objeto A, al destruir las variables locales su refcount queda en 1 pero son inalcanzables. El Garbage Collector de PHP analiza los buffers de colores (púrpura, gris, blanco, negro) para detectar y liberar ciclos huérfanos.',
            ],
            [
                'phase' => '5. Generadores y Máquinas de Estados Finitas',
                'description' => 'La interfaz Generator implementa una máquina de estados a nivel de C. Al encontrar \'yield\', guarda el puntero de instrucción del frame de ejecución y devuelve el control al llamador sin desapilar la función.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Peligro de las Referencias (&$var) y la Rotura de COW',
            'icon' => 'alert-triangle',
            'content' => 'Un error común de programadores que vienen de C o C++ es pensar que pasar variables por referencia `function process(&$data)` optimiza el rendimiento.

En PHP, esto suele provocar el efecto opuesto:
1. Al crear una referencia, PHP debe envolver el valor en una estructura especial `zend_reference`.
2. Si luego pasas esa variable a otra función que no usa referencia, el motor se ve obligado a duplicar la estructura de forma prematura.
3. Rompe la inmutabilidad y la capacidad del motor de optimizar lecturas concurrentes.

El paso por valor estándar de PHP es prácticamente gratuito para lectura gracias a Copy-On-Write. Usa referencias únicamente cuando la mutación del argumento original sea un requisito explícito de diseño.',
            'code' => '// ANTIPATRÓN: Crear referencias innecesarias
function readItems(&$items) {
    return count($items);
}

// PATRÓN SENIOR: Paso por valor limpio con COW automático
function readItems(array $items): int {
    return count($items); // Zero copy overhead!
}',
            'takeaways' => 'Confía en Copy-On-Write: el paso por valor en PHP no duplica datos a menos que ocurra una escritura explícita.',
        ],
        [
            'title' => 'Generadores vs Arrays: Procesamiento de Datasets Gigantes',
            'icon' => 'cpu',
            'content' => 'Supongamos que necesitas procesar una exportación bancaria de 1,000,000 de filas:

• Si haces `fetchAll()` y devuelves un array de arrays, PHP creará 1,000,000 de zvals más las estructuras de hash tables, consumiendo fácilmente **300 MB a 500 MB de RAM**, arriesgando un Out-of-Memory fatal.
• Si usas un Generador con `yield`, PHP solo mantiene en memoria el registro que se está procesando actualmente, consumiendo **menos de 2 MB de RAM** constantes durante todo el ciclo de vida del script.

En Symfony, métodos como `toIterable()` de Doctrine ORM o el componente Messenger aprovechan este principio para procesar colas de millones de mensajes sin fugas de memoria.',
            'takeaways' => 'Usa generadores con \'yield\' para pipelines de datos, exportaciones masivas y procesamiento batch con huella de memoria O(1).',
        ],
    ],
    'video' => [
        'title' => 'PHP Performance Demystified - Measure, Understand, Optimise',
        'speaker' => 'Volker Dusch',
        'youtube_id' => 'hiU4HwS1JKA',
        'duration' => '48 min',
        'description' => 'Una clase magistral sobre la gestión de memoria interna en PHP, el comportamiento de zvals, perfilado de rendimiento y optimización real.',
        'key_takeaways' => [
            'Cómo están representados los zvals en C y por qué el consumo de memoria es mayor al tamaño en bytes de los datos crudos.',
            'El funcionamiento exacto de Copy-On-Write y cómo evitar separaciones accidentales de memoria.',
            'Estrategias de profiling con herramientas como Blackfire y Tideways para detectar picos de asignación.',
            'El impacto del tipado estricto en el rendimiento del recolector de basura.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Infrastructure/Stream/TransactionStreamer.php',
        'title' => 'Lector en Streaming con Generadores en Memoria O(1)',
        'tag' => 'PHP 8.4 Memory O(1) Generators',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Stream;

use Generator;

/**
 * Procesa colecciones masivas de transacciones dividiéndolas en lotes
 * sin saturar la memoria RAM mediante el uso de Generadores nativos.
 */
class TransactionStreamer
{
    /**
     * Emite fragmentos del array original manteniendo un consumo de memoria constante O(1).
     *
     * @param list<int> $numbers Lista de IDs o valores numéricos a procesar
     * @param int $chunkSize Tamaño de cada bloque a emitir
     * @return Generator<int, list<int>>
     */
    public function chunkStream(array $numbers, int $chunkSize): Generator
    {
        if ($chunkSize <= 0) {
            throw new \\InvalidArgumentException(\'El tamaño del fragmento debe ser estrictamente mayor a 0.\');
        }

        $total = count($numbers);
        for ($i = 0; $i < $total; $i += $chunkSize) {
            yield array_slice($numbers, $i, $chunkSize);
        }
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un programador junior que ve un \'Allowed memory size exhausted\' acude de inmediato a php.ini a subir memory_limit a 2G, postergando la catástrofe para el siguiente pico de tráfico. Un ingeniero Senior entiende zvals, analiza dónde se rompe Copy-On-Write, y reemplaza la acumulación masiva de colecciones por generadores con yield y cursores de streaming en memoria O(1).',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Acumulación voraz en memoria y uso erróneo de referencias',
            'code' => '// Sin tipado estricto, acumula millones de filas en memoria
function getBigReport(&$hugeArray) { // Uso injustificado de referencia
    $chunks = [];
    // array_chunk duplica inmediatamente toda la estructura en RAM!
    $chunks = array_chunk($hugeArray, 500); 
    return $chunks;
}',
            'flaws' => [
                'Usa array_chunk acumulando todos los lotes a la vez en RAM, duplicando el consumo de memoria.',
                'Pasa el array por referencia creyendo erróneamente que ahorra memoria, cuando en realidad rompe COW.',
                'Carece de tipado estricto y lanza un Out of Memory con archivos de más de 100 MB.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Generador lazy con tipado estricto y consumo O(1)',
            'code' => 'declare(strict_types=1);

namespace App\\Infrastructure\\Stream;

use Generator;

class TransactionStreamer
{
    /**
     * @param list<int> $numbers
     * @return Generator<int, list<int>>
     */
    public function chunkStream(array $numbers, int $chunkSize): Generator
    {
        if ($chunkSize <= 0) {
            throw new \\InvalidArgumentException(\'Chunk size must be > 0\');
        }

        $total = count($numbers);
        for ($i = 0; $i < $total; $i += $chunkSize) {
            yield array_slice($numbers, $i, $chunkSize);
        }
    }
}',
            'rationale' => [
                'Usa yield para emitir cada bloque bajo demanda, permitiendo al llamador procesar e inmediatamente liberar memoria.',
                'Valida invariantes de entrada (chunkSize > 0) lanzando excepciones tempranas (Fail-Fast).',
                'Aprovecha declare(strict_types=1) y tipos precisos de PHPStan (list<int> y Generator).',
            ],
            'trade_offs' => [
                'Los generadores son de avance secuencial único; no pueden rebobinarse con rewind() sin reiniciar la función generadora.',
                'Para colecciones muy pequeñas (< 100 elementos), un array nativo puede tener un overhead menor que la inicialización del objeto Generator.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Zval Memory Architecture',
            'source' => 'PHP Internals: Memory Management',
            'quote' => 'Zval fue rediseñado en PHP 7 para medir exactamente 16 bytes en x86-64, permitiendo que quepa de forma óptima en las líneas de cache L1 de la CPU.',
            'author' => 'Nikita Popov',
            'explanation' => 'El alineamiento de 16 bytes de zval redujo el consumo de memoria de PHP en un 50% y duplicó la velocidad respecto a PHP 5.',
        ],
        [
            'topic' => 'Copy-On-Write Strategy',
            'source' => 'Zend Engine Internals Handbook',
            'quote' => 'Copy-On-Write garantiza que una estructura en memoria solo se duplique si y solo si uno de los consumidores intenta modificar su contenido.',
            'author' => 'Zeev Suraski',
            'explanation' => 'Permite pasar colecciones complejas entre múltiples servicios sin incurrir en penalizaciones de rendimiento por copia.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP Manual Oficial: Generadores y sintaxis Yield',
            'url' => 'https://www.php.net/manual/es/language.generators.syntax.php',
            'description' => 'Documentación oficial sobre funciones generadoras y control de flujo lazy.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PHP Internals Book: Zvals Architecture',
            'url' => 'https://www.phpinternalsbook.com/php7/internal_types/zvals.html',
            'description' => 'Explicación técnica profunda de la estructura C de zval, refcount y zend_value.',
            'type' => 'BOOK',
        ],
        [
            'title' => 'Understanding PHP 7 Memory Allocator',
            'url' => 'https://nikic.github.io/2015/05/05/Internal-value-representation-in-PHP-7-part-1.html',
            'description' => 'Artículo canónico de Nikita Popov sobre el rediseño de zvals y tipos de memoria.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Lector Eficiente con Generadores en Memoria O(1)',
        'objective' => 'Construir la clase TransactionStreamer que use Generadores (yield) para fragmentar datos en bloques sin acumular memoria.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'TransactionStreamer\' en el namespace \'App\\Infrastructure\\Stream\'.
3. Implementa el método \'chunkStream(array $numbers, int $chunkSize): \\Generator\'.
4. Utiliza \'yield\' para emitir los subfragmentos del array de manera perezosa (lazy).',
        'filename' => 'src/Infrastructure/Stream/TransactionStreamer.php',
        'guide' => [
            'explanation' => 'No utilices array_chunk, ya que crea todos los lotes de golpe en memoria. Utiliza un bucle for que avance de $chunkSize en $chunkSize y emita cada lote con yield array_slice(...).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Un generador en PHP no retorna un array de golpe; produce elementos uno a uno usando la palabra clave yield, manteniendo el puntero de ejecución pausado en cada iteración.',
            ],
            [
                'text' => '[Pista 2: Estructura] La firma del método debe ser public function chunkStream(array $numbers, int $chunkSize): \\Generator. Dentro, utiliza un bucle que divida los elementos y emita cada fragmento con yield $chunk.',
            ],
            [
                'text' => '[Pista 3: Snippet] Puedes usar array_slice($numbers, $i, $chunkSize) dentro de un bucle for ($i = 0; $i < count($numbers); $i += $chunkSize) y ejecutar yield array_slice($numbers, $i, $chunkSize); para que la memoria se mantenga constante.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Stream;

use Generator;

class TransactionStreamer
{
    /**
     * @param list<int> $numbers
     * @param int $chunkSize
     * @return Generator<int, list<int>>
     */
    public function chunkStream(array $numbers, int $chunkSize): Generator
    {
        // TODO: Implementa el generador lazy usando yield sin usar array_chunk
        yield [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Stream;

use Generator;

class TransactionStreamer
{
    /**
     * @param list<int> $numbers
     * @param int $chunkSize
     * @return Generator<int, list<int>>
     */
    public function chunkStream(array $numbers, int $chunkSize): Generator
    {
        if ($chunkSize <= 0) {
            throw new \\InvalidArgumentException(\'El tamaño del fragmento debe ser mayor a 0.\');
        }

        $total = count($numbers);
        for ($i = 0; $i < $total; $i += $chunkSize) {
            yield array_slice($numbers, $i, $chunkSize);
        }
    }
}
',
        'explanation' => 'Al usar yield junto con array_slice en un bucle for, solo existe un lote en memoria en cualquier momento dado. Esto garantiza una huella de memoria plana O(1) independientemente del tamaño total de los datos.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Tipado Estricto, zvals, Refcount & Gestión de Memoria en Zend VM',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuándo se produce físicamente la duplicación de memoria en una variable bajo la semántica Copy-On-Write (COW)?',
                'options' => [
                    'a' => 'Inmediatamente al asignar la variable con $b = $a.',
                    'b' => 'Únicamente cuando una de las dos variables que comparten el zval sufre una mutación o modificación.',
                    'c' => 'Cada vez que la variable se pasa como argumento a una función.',
                    'd' => 'Nunca; PHP siempre muta las estructuras en memoria de forma destructiva.',
                ],
                'correct' => 'b',
                'explanation' => 'Copy-On-Write pospone la clonación hasta el momento exacto en que se realiza una escritura. Si solo se realizan lecturas, ambas variables comparten el mismo casillero en memoria.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué pasar variables por referencia (&$var) puede perjudicar el rendimiento en PHP?',
                'options' => [
                    'a' => 'Porque las referencias están prohibidas a partir de PHP 8.0.',
                    'b' => 'Porque obliga a crear una estructura zend_reference que rompe la optimización de Copy-On-Write y fuerza clonaciones anticipadas.',
                    'c' => 'Porque las referencias solo pueden almacenar tipos enteros.',
                    'd' => 'Porque bloquea el acceso multihilo en el servidor web.',
                ],
                'correct' => 'b',
                'explanation' => 'Al forzar una zend_reference, el motor Zend pierde la certeza de inmutabilidad y se ve forzado a desvincular zvals de forma prematura si se comparten con otras partes del código.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es la complejidad espacial en memoria de un Generador que procesa 10 millones de filas usando yield?',
                'options' => [
                    'a' => 'O(N), donde N es el número total de filas.',
                    'b' => 'O(N^2), debido al overhead de la pila de llamadas.',
                    'c' => 'O(1), memoria constante ya que solo el elemento o fragmento actual permanece activo en RAM.',
                    'd' => 'O(log N), proporcional a la profundidad del árbol sintáctico.',
                ],
                'correct' => 'c',
                'explanation' => 'Los generadores actúan como máquinas de estado pausables. Solo el registro o bloque emitido por \'yield\' está presente en memoria en cada ciclo, logrando un consumo plano O(1).',
            ],
        ],
    ],
];
