<?php

declare(strict_types=1);

return [
    'slug' => 'sql-transactions-isolation',
    'title' => 'Transacciones ACID, Niveles de Aislamiento & Resolución de Deadlocks',
    'module' => 'Bases de Datos & SQL',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Las transacciones garantizan atomicidad, consistencia, aislamiento y durabilidad (ACID). Bajo concurrencia, los bloqueos mutuos (Deadlocks / Error MySQL 1213) son eventos esperados del motor relacional que deben gestionarse mediante reintentos automáticos idempotentes con backoff exponencial.',
        'problem' => 'Tratar un deadlock como un fallo catastrófico e interrumpir la petición del usuario con un error 500 arruina la experiencia de compra y corrompe procesos de pago cuando dos hilos bloquean recursos en orden inverso.',
        'solution' => 'Implementar la clase \'TransactionRetryCoordinator\' con el método \'executeWithRetry\', capturando PDOException, detectando errores de deadlock (texto \'deadlock\' o código 1213) y reintentando la transacción con pausas calculadas hasta un límite definido.',
        'problem_label' => 'El Problema: Bloqueos Mutuos (Deadlocks) y Fallos en Concurrencia',
        'solution_label' => 'La Solución Senior: Transacciones ACID y Coordinador de Reintentos Automáticos',
    ],
    'mental_model' => [
        'title' => 'La Bóveda con Candados Cruzados (Deadlock) y el Reintento con Jitter',
        'analogy' => 'Imagina dos cajeros bancarios intentando transferir dinero entre las cuentas A y B a la vez. El Cajero 1 bloquea la Cuenta A con candado y pide la Cuenta B. Al mismo tiempo, el Cajero 2 bloquea la Cuenta B y pide la Cuenta A. Ambos cajeros se quedan congelados mirándose fijamente, esperando que el otro suelte su llave (**Deadlock / Error MySQL 1213**). El motor de la base de datos detecta el ciclo mortal y aborta a uno de los dos cajeros lanzando una `PDOException`. Un sistema descuidado muestra un error 500 al cliente y cancela la compra. Un **Coordinador de Reintentos Senior** (`TransactionRetryCoordinator`) es un supervisor inteligente: detecta el error de deadlock, espera un intervalo breve y aleatorio (Jitter) para desincronizar a los dos cajeros, y reintenta la operación completa de forma silenciosa. Al segundo intento, la Cuenta A ya está libre y la transacción se completa con éxito sin que el usuario note el menor tropiezo.',
        'ascii_diagram' => 'HILO 1: Transfiere de A a B          HILO 2: Transfiere de B a A
1. Bloquea Cuenta A [LOCK]            1. Bloquea Cuenta B [LOCK]
2. Solicita Cuenta B...               2. Solicita Cuenta A...
      │                                     │
      ▼                                     ▼
   [ESPERA A QUE 2 SUELTE B]           [ESPERA A QUE 1 SUELTE A]
      └──────────────────┬──────────────────┘
                         │
                         ▼
             ¡DEADLOCK DETECTADO! (MySQL Error 1213)
                         │
      Motor relacional aborta Hilo 1 con PDOException
                         │
                         ▼
        TransactionRetryCoordinator::executeWithRetry()
        ├─ 1. Captura PDOException
        ├─ 2. Verifica si el mensaje contiene "deadlock" o código 1213
        ├─ 3. Pausa 50ms aleatorios (Jitter)
        └─ 4. Reintento 1 de 3: ¡TRANSACCIÓN EXITOSA!',
        'key_concept' => 'Los deadlocks no son bugs; son el mecanismo de defensa de la base de datos ante carreras de bloqueo. Las operaciones transaccionales críticas deben envolverse en reintentos automáticos.',
    ],
    'internals' => [
        'title' => 'Los 4 Niveles de Aislamiento y la Detección de Ciclos',
        'steps' => [
            [
                'phase' => '1. Read Uncommitted (Lecturas Sucias)',
                'description' => 'Una transacción puede leer datos no confirmados por otra. Permite anomalías de Dirty Read donde se lee información que luego es revertida (ROLLBACK).',
            ],
            [
                'phase' => '2. Read Committed (Lecturas No Repetibles)',
                'description' => 'Solo lee datos confirmados. Evita lecturas sucias, pero si ejecutas la misma consulta dos veces en la misma transacción, otra transacción confirmada puede haber alterado los valores (Non-Repeatable Read).',
            ],
            [
                'phase' => '3. Repeatable Read (Por defecto en MySQL InnoDB)',
                'description' => 'Utiliza MVCC (Multi-Version Concurrency Control) mediante lecturas consistentes de snapshot. Evita lecturas no repetibles, pero puede sufrir de Phantom Reads ante rangos abiertos.',
            ],
            [
                'phase' => '4. Serializable (Aislamiento Total)',
                'description' => 'Emula ejecución secuencial estricta bloqueando rangos con candados compartidos (Shared Locks). Elimina todas las anomalías pero destruye la concurrencia de la base de datos.',
            ],
            [
                'phase' => '5. Algoritmo de Detección de Grafos de Espera',
                'description' => 'InnoDB mantiene un grafo dirigido de transacciones en espera. Si se forma un ciclo cerrado (A espera a B y B espera a A), el motor elige a la transacción con menor número de escrituras y la revierte con el error 1213.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'La Regla de Oro: Bloquear Recursos en el Mismo Orden',
            'icon' => 'shield-check',
            'content' => '¿Cómo prevenir el 90% de los deadlocks en el código de aplicación?

La técnica más efectiva es **ordenar deterministamente los recursos antes de adquirirlos**:

```php
// Si transfieres entre cuenta 4 y cuenta 9:
$firstId = min($sourceId, $targetId);   // Siempre bloquea 4 primero
$secondId = max($sourceId, $targetId);  // Siempre bloquea 9 segundo

$em->find(Account::class, $firstId, LockMode::PESSIMISTIC_WRITE);
$em->find(Account::class, $secondId, LockMode::PESSIMISTIC_WRITE);
```

Al bloquear siempre en orden ascendente de clave primaria, los dos hilos competirán por el recurso 4 primero; uno ganará y el otro esperará en fila, eliminando la posibilidad de candados cruzados.',
            'takeaways' => 'Ordena siempre los IDs de las entidades antes de solicitar bloqueos pesimistas para prevenir ciclos de deadlock.',
        ],
        [
            'title' => 'Backoff Exponencial con Jitter en Reintentos',
            'icon' => 'clock',
            'content' => 'Si dos transacciones chocan y ambas reintentan exactamente al cabo de 100 milisegundos, volverán a chocar en el siguiente ciclo (Thundering Herd Problem).

Para evitarlo, un algoritmo senior añade **Jitter (aleatoriedad)**:
`$sleepMicroseconds = (2 ** $attempt * 50_000) + random_int(1_000, 25_000);`

Esto desincroniza a los hilos en el tiempo, permitiendo que uno termine limpiamente mientras el otro espera su turno.',
            'takeaways' => 'Añade pausas aleatorias (jitter) en tus reintentos de transacciones para desincronizar hilos en colisión.',
        ],
    ],
    'video' => [
        'title' => 'ACID: Isolation, Transactions and what can go wrong with your data',
        'speaker' => 'CockroachDB & Hussein Nasser',
        'youtube_id' => 'iD_Yk5AhNGc',
        'duration' => '48 min',
        'description' => 'Una clase magistral sobre los 4 principios ACID, anomalías de lectura sucia y no repetible, detección de deadlocks y algoritmos de reintento.',
        'key_takeaways' => [
            'Las diferencias físicas entre Read Committed, Repeatable Read y Serializable.',
            'Cómo el control de concurrencia multiversión (MVCC) resuelve lecturas sin bloqueos.',
            'La causa matemática de los deadlocks y por qué el motor debe abortar un hilo.',
            'Estrategias de retry transparentes para aplicaciones de alta concurrencia.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Database/Transaction/TransactionRetryCoordinator.php',
        'title' => 'Coordinador de Reintentos Automáticos ante Deadlocks',
        'tag' => 'SQL Transaction Retry Coordinator',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Transaction;

use PDOException;
use Throwable;

/**
 * Coordina la ejecución de operaciones transaccionales reintentando
 * automáticamente ante bloqueos mutuos (Deadlocks / MySQL Error 1213).
 */
class TransactionRetryCoordinator
{
    /**
     * Ejecuta una operación invocable con reintentos transparentes ante deadlocks.
     */
    public function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                $attempts++;
                return $operation();
            } catch (Throwable $e) {
                $isDeadlock = $this->isDeadlockException($e);

                if (!$isDeadlock || $attempts >= $maxRetries) {
                    throw $e;
                }

                // Pausa breve con microsegundos para desincronizar hilos
                usleep(50_000 * $attempts);
            }
        }
    }

    private function isDeadlockException(Throwable $e): bool
    {
        $message = strtolower($e->getMessage());
        $code = (int) $e->getCode();

        return str_contains($message, \'deadlock\')
            || $code === 1213
            || (str_contains($message, \'1213\'));
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior ve un error de deadlock en Sentry y asume que es un bug inexplicable de la base de datos. Un ingeniero Senior entiende que los bloqueos cruzados son inevitables bajo alta concurrencia: ordena las adquisiciones de bloqueos por ID para prevenirlos, y envuelve las transacciones críticas en un TransactionRetryCoordinator con backoff exponencial para absorberlos silenciosamente.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Transacción sin reintentos que colapsa ante deadlocks',
            'code' => '// Sin reintentos ante concurrencia
public function transfer($from, $to, $amount) {
    // Si otro hilo transfiere en sentido inverso al mismo tiempo:
    // FATAL: Deadlock found when trying to get lock; try restarting transaction
    $this->db->beginTransaction();
    $this->db->query("UPDATE accounts SET balance = balance - $amount WHERE id = $from");
    $this->db->query("UPDATE accounts SET balance = balance + $amount WHERE id = $to");
    $this->db->commit();
}',
            'flaws' => [
                'No captura PDOException ni contempla deadlocks.',
                'Provoca errores 500 aleatorios a los usuarios bajo picos de tráfico.',
                'No ordena los recursos, maximizando la probabilidad de bloqueos cruzados.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: TransactionRetryCoordinator con detección de error 1213',
            'code' => 'declare(strict_types=1);

namespace App\\Database\\Transaction;

class TransactionRetryCoordinator
{
    public function executeWithRetry(callable $op, int $max = 3): mixed
    {
        $tries = 0;
        while (true) {
            try {
                $tries++;
                return $op();
            } catch (\\Throwable $e) {
                $msg = strtolower($e->getMessage());
                $isDeadlock = str_contains($msg, \'deadlock\') || $e->getCode() === 1213;
                if (!$isDeadlock || $tries >= $max) throw $e;
                usleep(50000 * $tries);
            }
        }
    }
}',
            'rationale' => [
                'Detecta de forma confiable el texto \'deadlock\' y el código de error MySQL 1213.',
                'Reintenta de manera transparente con backoff progresivo sin molestar al usuario.',
                'Acepta cualquier callable preservando el tipo de retorno.',
            ],
            'trade_offs' => [
                'La operación dentro de executeWithRetry DEBE ser estrictamente idempotente o volver a iniciar la transacción desde cero.',
                'Si una transacción realiza llamadas a pasarelas de pago externas HTTP, estas no deben reintentarse ciegamente sin claves de idempotencia.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Deadlock Handling in Relational Databases',
            'source' => 'MySQL 8.0 InnoDB Locking Guide',
            'quote' => 'Los deadlocks no son peligrosos; simplemente significan que debes reintentar la transacción. Diseña tus aplicaciones para que cuando una transacción falle con un deadlock, se reinicie automáticamente.',
            'author' => 'MySQL Documentation Team',
            'explanation' => 'La documentación oficial de InnoDB prescribe el patrón de reintento como la forma estándar de gestionar bloqueos.',
        ],
        [
            'topic' => 'Transaction Isolation Anomalies',
            'source' => 'A Critique of ANSI SQL Isolation Levels',
            'quote' => 'El aislamiento perfecto tiene un costo inaceptable en concurrencia; por ello los sistemas modernos combinan MVCC con detección de bloqueos y reintentos.',
            'author' => 'Hal Berenson et al.',
            'explanation' => 'El paper clásico que reformuló los niveles de aislamiento en bases de datos relacionales.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'MySQL Docs: Deadlocks in InnoDB',
            'url' => 'https://dev.mysql.com/doc/refman/8.0/en/innodb-deadlocks.html',
            'description' => 'Guía oficial sobre cómo InnoDB detecta y resuelve automáticamente los deadlocks.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PostgreSQL Docs: Transaction Isolation',
            'url' => 'https://www.postgresql.org/docs/current/transaction-iso.html',
            'description' => 'Explicación de los niveles de aislamiento en PostgreSQL y control de concurrencia MVCC.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Martin Fowler: Optimistic & Pessimistic Offline Lock',
            'url' => 'https://martinfowler.com/eaaCatalog/pessimisticOfflineLock.html',
            'description' => 'Patrones de bloqueo pesimista y optimista en arquitectura de software.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Coordinador de Reintentos de Transacciones ante Deadlocks',
        'objective' => 'Implementar la clase TransactionRetryCoordinator con el método executeWithRetry(callable $operation, int $maxRetries = 3): mixed, detectando deadlocks (texto \'deadlock\' o código de error MySQL 1213) y capturando PDOException.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'TransactionRetryCoordinator\' en el namespace \'App\\Database\\Transaction\'.
3. Implementa el método \'executeWithRetry(callable $operation, int $maxRetries = 3): mixed\'.
4. Envuelve la invocación \'$operation()\' en un bucle con bloque try/catch capturando \'Throwable\' o \'PDOException\'.
5. Comprueba si la excepción es un deadlock analizando si el mensaje contiene \'deadlock\' o si el código es 1213.
6. Si es un deadlock y no se ha alcanzado \'$maxRetries\', realiza una pausa (usleep) y reintenta; de lo contrario relanza la excepción.',
        'filename' => 'src/Database/Transaction/TransactionRetryCoordinator.php',
        'guide' => [
            'explanation' => 'En executeWithRetry, usa un bucle while (true). Lleva la cuenta de intentos. Si ocurre un fallo, verifica if (str_contains(strtolower($e->getMessage()), \'deadlock\') || $e->getCode() === 1213 || str_contains($e->getMessage(), \'1213\')). Si excede maxRetries, throw $e;.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase TransactionRetryCoordinator con el método executeWithRetry(callable $operation, int $maxRetries = 3): mixed.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe capturarse y verificarse el error de deadlock (texto \'deadlock\' o código de error MySQL 1213) capturando PDOException o Exception.',
            ],
            [
                'text' => '[Pista 3: Snippet] if (str_contains(strtolower($e->getMessage()), \'deadlock\') || $e->getCode() === 1213) { if ($attempts < $maxRetries) { usleep(50000); continue; } } throw $e;.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Transaction;

use PDOException;
use Throwable;

class TransactionRetryCoordinator
{
    public function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
    {
        // TODO: Ejecuta $operation() con reintentos ante error de deadlock (1213)
        return $operation();
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Transaction;

use PDOException;
use Throwable;

class TransactionRetryCoordinator
{
    public function executeWithRetry(callable $operation, int $maxRetries = 3): mixed
    {
        $attempts = 0;

        while (true) {
            try {
                $attempts++;
                return $operation();
            } catch (Throwable $e) {
                $message = strtolower($e->getMessage());
                $isDeadlock = str_contains($message, \'deadlock\')
                    || (int) $e->getCode() === 1213
                    || str_contains($message, \'1213\');

                if (!$isDeadlock || $attempts >= $maxRetries) {
                    throw $e;
                }

                usleep(50_000 * $attempts);
            }
        }
    }
}
',
        'explanation' => 'TransactionRetryCoordinator captura excepciones de tipo PDOException/Throwable, inspecciona si corresponden a un error de deadlock de base de datos (código 1213 o texto \'deadlock\'), y reintenta la transacción de forma transparente hasta alcanzar el límite configurado.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Transacciones ACID, Niveles de Aislamiento & Resolución de Deadlocks',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué representa el código de error MySQL 1213 (ER_LOCK_DEADLOCK)?',
                'options' => [
                    'a' => 'Que la base de datos se quedó sin espacio en disco.',
                    'b' => 'Que se detectó un ciclo de bloqueo mutuo entre dos transacciones y una de ellas fue abortada para romper el punto muerto.',
                    'c' => 'Que la contraseña del usuario de base de datos es incorrecta.',
                    'd' => 'Que una tabla fue eliminada con DROP TABLE.',
                ],
                'correct' => 'b',
                'explanation' => 'El error 1213 indica que dos transacciones adquirieron candados cruzados y ninguna puede avanzar sin la otra. El motor revierte una de ellas para permitir que la otra continúe.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la mejor estrategia en el código de aplicación para prevenir que ocurran deadlocks?',
                'options' => [
                    'a' => 'Desactivar las claves primarias en todas las tablas.',
                    'b' => 'Adquirir siempre los bloqueos sobre las entidades en un orden determinista y consistente (por ejemplo, por orden ascendente de ID).',
                    'c' => 'Usar transacciones con nivel de aislamiento READ UNCOMMITTED en todas las rutas.',
                    'd' => 'Ejecutar todas las consultas SQL con sleep(1).',
                ],
                'correct' => 'b',
                'explanation' => 'Si todas las transacciones bloquean las cuentas en orden ascendente (primero la cuenta con menor ID y luego la mayor), nunca se formará un ciclo circular de bloqueo mutuo.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por qué es crucial añadir pausas aleatorias (jitter) al reintentar una transacción que sufrió un deadlock?',
                'options' => [
                    'a' => 'Para dar tiempo a que el recolector de basura de PHP libere memoria zval.',
                    'b' => 'Para evitar el problema de rebaño (Thundering Herd) y desincronizar los hilos en colisión, evitando que vuelvan a chocar en el siguiente milisegundo.',
                    'c' => 'Porque la conexión TCP de PDO se desconecta después de un deadlock.',
                    'd' => 'Para actualizar el archivo de caché de Symfony.',
                ],
                'correct' => 'b',
                'explanation' => 'Si ambos hilos reintentan al mismo tiempo con intervalos fijos, existe un alto riesgo de que vuelvan a solicitar los mismos bloqueos a la vez y colisionen repetidamente.',
            ],
        ],
    ],
];
