<?php

declare(strict_types=1);

return [
    'slug' => 'doctrine-unit-of-work',
    'title' => 'Unit of Work, Identity Map Internals & Procesamiento en Lotes',
    'module' => 'Doctrine ORM',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Doctrine ORM opera como un Data Mapper respaldado por los patrones Unit of Work e Identity Map. El Identity Map garantiza que una entidad con un ID específico solo exista una única vez en memoria RAM, mientras que el Unit of Work calcula las diferencias de estado (Dirty Checking) al invocar flush().',
        'problem' => 'En comandos batch de importación o procesamiento de miles de registros, invocar únicamente \'$em->flush()\' deja todas las entidades vivas dentro del Identity Map, provocando que el consumo de memoria crezca linealmente hasta colapsar el proceso con \'Allowed memory size exhausted\'.',
        'solution' => 'Implementar \'BatchFlushCoordinator\' con el método \'processInBatches(iterable $items, callable $worker, int $batchSize = 100): int\', invocando tanto \'$em->flush()\' para persistir cambios como \'$em->clear()\' para vaciar el Identity Map en los puntos de corte del lote.',
        'problem_label' => 'El Problema: Fugas de Memoria en Comandos Batch con Doctrine',
        'solution_label' => 'La Solución Senior: Unit of Work, Identity Map y Coordinación de flush() + clear()',
    ],
    'mental_model' => [
        'title' => 'El Notario con Libreta de Registro (Identity Map) y la Trituradora de Lotes',
        'analogy' => 'Imagina que Doctrine es un notario meticuloso. Cada vez que cargas un cliente de la base de datos (`$user = $em->find(User::class, 1)`), el notario anota al cliente en una libreta llamada **Identity Map**. Si en el mismo request pides al cliente 1 diez veces, el notario no va al archivo de la corte (base de datos); te muestra la misma página de su libreta en memoria. Cuando llamas a `$em->flush()`, el **Unit of Work** compara los valores actuales de cada objeto contra una copia original guardada en el nacimiento (Snapshot / Dirty Checking) y emite un solo `UPDATE` consolidado. ¿Cuál es el peligro mortal en comandos batch? Si procesas una lista de 100,000 registros y solo haces `$em->flush()`, **el notario sigue acumulando 100,000 entidades vivas en su libreta**. La memoria RAM se satura y el servidor colapsa. Para procesar millones de filas de forma segura, un Senior ejecuta **`$em->flush()` seguido inmediatamente de `$em->clear()`**: esto escribe en la base de datos y luego pasa la libreta por la trituradora de papel, vaciando el Identity Map y manteniendo el consumo de RAM plano en O(1).',
        'ascii_diagram' => 'ESTRUCTURA DEL UNIT OF WORK EN MEMORIA RAM:
┌─────────────────────────────────────────────────────────────┐
│                    EntityManager                            │
│  ┌───────────────────────────────────────────────────────┐  │
│  │                    Unit of Work                       │  │
│  │  Identity Map:  [User#1] [User#2] [Order#45]          │  │
│  │  Original Data: Snapshots de valores en hidratación   │  │
│  │  Entity States: MANAGED, DETACHED, NEW, REMOVED       │  │
│  └───────────────────────────────────────────────────────┘  │
└─────────────────────────────────────────────────────────────┘

PROCESAMIENTO EN LOTES (BatchFlushCoordinator):
for ($i = 0; $i < 10000; $i++) {
    $worker($items[$i]);
    if ($i % $batchSize === 0) {
        $em->flush(); // 1. Escribe cambios acumulados en SQL
        $em->clear(); // 2. Vacia el Identity Map -> ¡RAM vuelve a 0 MB!
    }
}',
        'key_concept' => 'flush() escribe las operaciones en la base de datos; clear() desvincula las entidades del Unit of Work y vacía el Identity Map para que el recolector de basura de PHP recupere la memoria.',
    ],
    'internals' => [
        'title' => 'Los 4 Estados de una Entidad en el Unit of Work',
        'steps' => [
            [
                'phase' => '1. NEW (Transitorio)',
                'description' => 'Una entidad recién instanciada con \'new User()\' que aún no ha sido persistida ni tiene clave primaria asignada por la base de datos.',
            ],
            [
                'phase' => '2. MANAGED (Gestionada)',
                'description' => 'La entidad está registrada en el Identity Map del Unit of Work. Doctrine rastrea cualquier cambio en sus propiedades mediante Dirty Checking.',
            ],
            [
                'phase' => '3. DETACHED (Desvinculada)',
                'description' => 'Una entidad que posee un ID en base de datos pero ya no está en el Identity Map (tras invocar $em->clear() o $em->detach()). Sus cambios no se persisten en flush().',
            ],
            [
                'phase' => '4. REMOVED (Marcada para Borrado)',
                'description' => 'Entidad programada para eliminación mediante $em->remove(). En el siguiente flush(), Doctrine ejecutará el comando DELETE correspondiente.',
            ],
            [
                'phase' => '5. El Proceso de Dirty Checking al hacer flush()',
                'description' => 'Unit of Work itera todas las entidades MANAGED, compara sus valores actuales contra el snapshot de hidratación, calcula el ChangeSet exacto y agrupa los UPDATEs en sentencias transaccionales.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Peligro de Entidades Desvinculadas (DETACHED) tras clear()',
            'icon' => 'alert-circle',
            'content' => 'Cuando ejecutas `$em->clear()`, el Identity Map se vacía por completo.

**Consecuencia Vital:**
Cualquier objeto que tuvieras en una variable local pasa al estado **DETACHED**:
```php
$user = $em->find(User::class, 1);
$em->clear();

$user->setName(\'Nuevo Nombre\');
$em->flush(); // ¡NO OCURRE NADA! $user ya no está gestionado por Doctrine.
```

Si necesitas volver a asociar la entidad desvinculada al Unit of Work, debes usar `$em->merge()` o volver a cargar la entidad fresca desde la base de datos con `$em->find()`.',
            'takeaways' => 'Ten en cuenta que tras $em->clear(), todas las entidades previas se vuelven detached y no registrarán cambios en flush() posteriores.',
        ],
        [
            'title' => 'flush() Fuera del Bucle: La Regla de Oro del Rendimiento',
            'icon' => 'zap',
            'content' => 'Uno de los antipatrones más destructivos es llamar a `$em->flush()` dentro de cada iteración de un bucle de 10,000 elementos:

• En cada `flush()`, Doctrine recalcula el árbol completo de cambios y ejecuta una transacción SQL con su respectivo `COMMIT` y round-trip de red.
• Hacer 10,000 flushes tarda **más de 60 segundos**.
• Agrupar en lotes de 100 con `flush()` y `clear()` tarda **menos de 1.5 segundos**, una aceleración de más del 4000%.',
            'takeaways' => 'Nunca invoques flush() en cada elemento: agrupa siempre en lotes de 50 o 100 para maximizar el throughput.',
        ],
    ],
    'video' => [
        'title' => 'PHP UK Conference 2016 - Marco Pivetta - Doctrine ORM Good Practices and Tricks',
        'speaker' => 'Marco Pivetta (Ocramius - Doctrine Core Team)',
        'youtube_id' => 'rzGeNYC3oz0',
        'duration' => '50 min',
        'description' => 'La conferencia definitiva sobre arquitectura interna de Doctrine ORM, gestión de memoria con Unit of Work, Identity Map y mejores prácticas empresariales.',
        'key_takeaways' => [
            'Cómo opera internamente el Identity Map para garantizar unicidad de objetos.',
            'El funcionamiento exacto del algoritmo de Dirty Checking en flush().',
            'Patrones para procesar millones de registros sin agotar la memoria con clear().',
            'Por qué las entidades deben proteger sus invariantes y evitar setters anémicos.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Doctrine/Batch/BatchFlushCoordinator.php',
        'title' => 'Coordinador de Procesamiento en Lotes para Doctrine ORM',
        'tag' => 'Doctrine 3 Batch Coordinator',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Batch;

use Doctrine\\ORM\\EntityManagerInterface;
use InvalidArgumentException;

/**
 * Coordina la persistencia en lotes de grandes volúmenes de datos vaciando
 * periódicamente el Identity Map para mantener el consumo de memoria en O(1).
 */
class BatchFlushCoordinator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    /**
     * Procesa una colección en bloques invocando flush() y clear() en cada punto de corte.
     *
     * @param iterable<mixed> $items Colección de elementos a procesar
     * @param callable(mixed): void $worker Función que procesa cada elemento
     * @param int $batchSize Tamaño del lote (predeterminado 100)
     * @return int Total de elementos procesados
     */
    public function processInBatches(iterable $items, callable $worker, int $batchSize = 100): int
    {
        if ($batchSize <= 0) {
            throw new InvalidArgumentException(\'El tamaño del lote debe ser estrictamente mayor a cero.\');
        }

        $count = 0;

        foreach ($items as $item) {
            $worker($item);
            $count++;

            if ($count % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        if ($count % $batchSize !== 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $count;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior llama a \'$em->flush()\' en cada vuelta de un bucle for o se pregunta por qué su comando de consola colapsa la RAM al procesar un CSV. Un ingeniero Senior comprende la anatomía del Unit of Work y el Identity Map, agrupa el trabajo en lotes de tamaño medido y vacía periódicamente la memoria con \'$em->flush()\' y \'$em->clear()\'.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: flush() en cada vuelta o fuga masiva de memoria sin clear()',
            'code' => '// Fuga masiva de memoria
foreach ($hugeList as $row) {
    $user = new User($row[\'email\']);
    $em->persist($user);
    $em->flush(); // Desastroso: 10,000 viajes de red individuales!
    // Olvida $em->clear(): ¡las 10,000 entidades se quedan en RAM!
}',
            'flaws' => [
                'Ejecuta una transacción individual para cada registro (lentitud extrema).',
                'No limpia el Identity Map con clear(), provocando Out of Memory fatal.',
                'No parametriza el tamaño del lote ni valida precondiciones.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Coordinador de lotes con flush() y clear() periódicos',
            'code' => 'declare(strict_types=1);

namespace App\\Doctrine\\Batch;

use Doctrine\\ORM\\EntityManagerInterface;
use InvalidArgumentException;

class BatchFlushCoordinator
{
    public function __construct(private readonly EntityManagerInterface $em) {}

    public function processInBatches(iterable $items, callable $worker, int $batch = 100): int
    {
        if ($batch <= 0) throw new InvalidArgumentException(\'Batch size must be > 0\');
        $i = 0;
        foreach ($items as $item) {
            $worker($item);
            $i++;
            if ($i % $batch === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }
        if ($i % $batch !== 0) {
            $this->em->flush();
            $this->em->clear();
        }
        return $i;
    }
}',
            'rationale' => [
                'Coordina lotes exactos optimizando el número de viajes SQL a la base de datos.',
                'Ejecuta flush() y clear() manteniendo la huella de memoria plana en O(1).',
                'Valida invariantes de entrada (batchSize > 0) con tipado estricto.',
            ],
            'trade_offs' => [
                'Tras invocar clear(), cualquier entidad previamente cargada se desvincula del EntityManager.',
                'Si un worker necesita entidades de referencia estáticas (ej. un rol de usuario), deben recargarse o fusionarse tras cada corte de lote.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Unit of Work Pattern',
            'source' => 'Patterns of Enterprise Application Architecture',
            'quote' => 'El Unit of Work mantiene una lista de objetos afectados por una transacción de negocio y coordina la escritura de cambios y la resolución de problemas de concurrencia.',
            'author' => 'Martin Fowler',
            'explanation' => 'El patrón canónico en el que se basa el EntityManager de Doctrine.',
        ],
        [
            'topic' => 'Batch Processing Memory Management',
            'source' => 'Doctrine ORM Documentation',
            'quote' => 'El procesamiento en lotes en Doctrine requiere invocar periódicamente clear() para desvincular objetos del Identity Map y permitir que PHP reclame la memoria.',
            'author' => 'Doctrine Project',
            'explanation' => 'La recomendación oficial imprescindible para comandos de importación masiva.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Doctrine Docs: Batch Processing',
            'url' => 'https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/batch-processing.html',
            'description' => 'Guía oficial de Doctrine sobre procesamiento por lotes, flush, clear e iterables.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Martin Fowler: Identity Map Pattern',
            'url' => 'https://martinfowler.com/eaaCatalog/identityMap.html',
            'description' => 'Definición del patrón Identity Map para garantizar que cada registro se cargue una sola vez.',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Ocramius: Doctrine ORM Performance Pitfalls',
            'url' => 'https://ocramius.github.io/',
            'description' => 'Artículos técnicos de Marco Pivetta sobre optimización y arquitectura en Doctrine.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Coordinador de Procesamiento en Lotes para Doctrine',
        'objective' => 'Implementar la clase BatchFlushCoordinator con el método processInBatches(iterable $items, callable $worker, int $batchSize = 100): int, validando $batchSize > 0 e invocando tanto flush() como clear() en los puntos de corte.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'BatchFlushCoordinator\' en el namespace \'App\\Doctrine\\Batch\'.
3. Inyecta \'Doctrine\\ORM\\EntityManagerInterface\' en el constructor.
4. Implementa el método \'processInBatches(iterable $items, callable $worker, int $batchSize = 100): int\'.
5. Lanza \'InvalidArgumentException\' si \'$batchSize <= 0\'.
6. Itera \'$items\' ejecutando \'$worker($item)\'. Cada \'$batchSize\' elementos, invoca \'$this->entityManager->flush()\' y \'$this->entityManager->clear()\'.
7. Al terminar el bucle, si quedaron elementos pendientes, realiza un último \'flush()\' y \'clear()\', retornando el total procesado.',
        'filename' => 'src/Doctrine/Batch/BatchFlushCoordinator.php',
        'guide' => [
            'explanation' => 'En processInBatches, valida if ($batchSize <= 0) throw new \\InvalidArgumentException(\'...\');. Lleva un contador. Cada vez que $count % $batchSize === 0, invoca $this->entityManager->flush() y $this->entityManager->clear(). Repite al final si $count % $batchSize !== 0.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase BatchFlushCoordinator con el método processInBatches(iterable $items, callable $worker, int $batchSize = 100): int.',
            ],
            [
                'text' => '[Pista 2: Estructura] El coordinador debe invocar tanto flush() como clear() en el EntityManager para vaciar el Identity Map en los puntos de corte de lote.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($batchSize <= 0) throw new \\InvalidArgumentException(\'Batch size must be > 0\'); if ($count % $batchSize === 0) { $this->entityManager->flush(); $this->entityManager->clear(); }.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Batch;

use Doctrine\\ORM\\EntityManagerInterface;
use InvalidArgumentException;

class BatchFlushCoordinator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function processInBatches(iterable $items, callable $worker, int $batchSize = 100): int
    {
        // TODO: Valida batchSize > 0 y procesa en lotes con flush() y clear()
        return 0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Batch;

use Doctrine\\ORM\\EntityManagerInterface;
use InvalidArgumentException;

class BatchFlushCoordinator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {}

    public function processInBatches(iterable $items, callable $worker, int $batchSize = 100): int
    {
        if ($batchSize <= 0) {
            throw new InvalidArgumentException(\'El tamaño del lote debe ser estrictamente mayor a cero.\');
        }

        $count = 0;

        foreach ($items as $item) {
            $worker($item);
            $count++;

            if ($count % $batchSize === 0) {
                $this->entityManager->flush();
                $this->entityManager->clear();
            }
        }

        if ($count % $batchSize !== 0) {
            $this->entityManager->flush();
            $this->entityManager->clear();
        }

        return $count;
    }
}
',
        'explanation' => 'BatchFlushCoordinator implementa la técnica canónica para procesamiento masivo en Doctrine: ejecuta flush() para enviar los cambios y clear() para vaciar el Identity Map, manteniendo el uso de memoria RAM constante y previsible.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Unit of Work, Identity Map Internals & Procesamiento en Lotes',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué función cumple el \'Identity Map\' dentro del EntityManager de Doctrine?',
                'options' => [
                    'a' => 'Cifra las claves foráneas en la base de datos.',
                    'b' => 'Garantiza que cada entidad con una clave primaria determinada solo tenga una única instancia cargada en memoria durante el ciclo de vida de la petición.',
                    'c' => 'Genera UUIDs v4 de forma automática.',
                    'd' => 'Mantiene un registro de las direcciones IP de los clientes web.',
                ],
                'correct' => 'b',
                'explanation' => 'El Identity Map actúa como caché de primer nivel en memoria: si consultas la misma entidad varias veces en el mismo request, Doctrine te devuelve siempre la misma instancia sin repetir consultas SQL.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué invocar \'$em->clear()\' es indispensable al procesar grandes volúmenes de datos en bucles?',
                'options' => [
                    'a' => 'Porque de lo contrario Doctrine borra las tablas de la base de datos.',
                    'b' => 'Porque vacía el Identity Map y desvincula las entidades gestionadas, permitiendo que el Garbage Collector de PHP libere la memoria RAM.',
                    'c' => 'Porque sin clear() el comando flush() no escribe en disco.',
                    'd' => 'Porque clear() reinicia el servidor web Apache.',
                ],
                'correct' => 'b',
                'explanation' => 'Sin clear(), cada entidad instanciada permanece referenciada indefinidamente dentro del Unit of Work, causando fugas de memoria hasta agotar el memory_limit.',
            ],
            [
                'id' => 'q3',
                'question' => '¿En qué estado queda una entidad gestionada después de invocar \'$em->clear()\'?',
                'options' => [
                    'a' => 'NEW',
                    'b' => 'MANAGED',
                    'c' => 'DETACHED',
                    'd' => 'REMOVED',
                ],
                'correct' => 'c',
                'explanation' => 'Pasa a estado DETACHED (desvinculada). La entidad sigue existiendo en tu variable local con su ID, pero Doctrine ya no monitorea sus mutaciones en llamadas flush() posteriores.',
            ],
        ],
    ],
];
