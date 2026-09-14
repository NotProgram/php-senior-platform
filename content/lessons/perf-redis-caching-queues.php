<?php

declare(strict_types=1);

return [
    'slug' => 'perf-redis-caching-queues',
    'title' => 'Symfony Messenger & Redis Queues',
    'module' => 'performance',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En aplicaciones web concurrentes, obligar a que una peticion HTTP procese operaciones lentas (generar un PDF de 50 paginas, enviar emails transaccionales, sincronizar con un CRM externo) de manera sincronica destruye el rendimiento y eleva la tasa de cancelacion de los usuarios. Si la peticion tarda 4 segundos en responder, el servidor web retiene el socket abierto, consume un hilo de PHP-FPM y se vuelve vulnerable a fallas en cascada.

El componente Symfony Messenger transforma esta arquitectura mediante el desacoplamiento de Mensajes y Manejadores (Message Buses). La peticion HTTP unicamente despacha un comando a un transporte asincrono respaldado por Redis o RabbitMQ, y responde al usuario en 3 milisegundos con un codigo 202 Accepted. Posteriormente, trabajadores en segundo plano (Workers) consumen la cola de forma continua y controlada.

Un desarrollador Senior domina el principio mas importante de las colas distribuidas: la Idempotencia. Dado que las redes y los brokers garantizan entrega al menos una vez (At-Least-Once Delivery), un mismo mensaje puede recibirse dos veces tras un timeout. El manejador debe garantizar que procesar el mismo job dos veces no genere efectos secundarios duplicados (como cobrar dos veces a un cliente).',
        'problem' => 'En aplicaciones web concurrentes, obligar a que una peticion HTTP procese operaciones lentas (generar un PDF de 50 paginas, enviar emails transaccionales, sincronizar con un CRM externo) de manera sincronica destruye el rendimiento y eleva la tasa de cancelacion de los usuarios. Si la peticion tarda 4 segundos en responder, el servidor web retiene el socket abierto, consume un hilo de PHP-FPM y se vuelve vulnerable a fallas en cascada.',
        'solution' => 'El componente Symfony Messenger transforma esta arquitectura mediante el desacoplamiento de Mensajes y Manejadores (Message Buses). La peticion HTTP unicamente despacha un comando a un transporte asincrono respaldado por Redis o RabbitMQ, y responde al usuario en 3 milisegundos con un codigo 202 Accepted. Posteriormente, trabajadores en segundo plano (Workers) consumen la cola de forma continua y controlada.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Talonario de Encomiendas de una Oficina Postal',
        'concept' => 'Imagina como opera una sucursal de correos concurrida:
1. EL ERROR SINCRONICO: Un cliente llega con 10 cajas pesadas. El cartero le dice: \'Espere aqui en la ventanilla mientras cargo las cajas en mi furgoneta, conduzco 15 kilometros hasta el aeropuerto y regreso a sellarle el comprobante\'. La fila de clientes en la oficina saldria a la calle y la sucursal colapsaria.
2. EL ENFOQUE ASINCRONO CON MENSAJES (Messenger): El cartero toma los 10 paquetes, les pega una etiqueta con un codigo de barras unico (Job ID / Idempotency Key), le entrega un recibo al cliente en 5 segundos (\'Paquetes Aceptados\') y coloca las cajas en la cinta transportadora del deposito (La Cola de Redis).
3. EL CONSUMIDOR EN EL DEPOSITO (Worker): En el sotano, un operario que no atiende publico toma las cajas de la cinta una por una (FIFO con array_shift) y las carga en los camiones al ritmo optimo de la flota.
4. LA PROTECCION IDEMPOTENTE: Si una caja se cae y vuelve a entrar en la cinta con el mismo codigo de barras ya despachado, el operario escanea la etiqueta, ve que ya fue procesada (isProcessed) y la aparta sin volver a enviarla.',
        'ascii_diagram' => 'ARQUITECTURA ASINCRONA CON SYMFONY MESSENGER & REDIS:

[ Peticion Web HTTP (Tiempo: 3ms) ]:
Client ---> POST /checkout ---> [ OrderController ]
                                        |
                                        v despacha OrderPlacedEvent
                             [ Symfony MessageBus ]
                                        |
                                        v transport: redis
                             [ Cola FIFO en Redis ]
                                        |
                                        v
                                 HTTP 202 Accepted (Responde al usuario)

--------------------------------------------------------------------------------
[ Consumo Asincrono en Segundo Plano (Worker CLI) ]:
bin/console messenger:consume redis -vv
             |
             v array_shift() (Procesamiento secuencial y controlado)
   [ IdempotentJobQueue ] ---> ¿Ya procesado? (isProcessed)
                                    /           \\
                              SI (Duplicado)    NO (Nuevo)
                                  /                \\
                           Ignora silencioso      Ejecuta $processor()
                                                  (Envia email, cobra tarjeta)
                                                  Registra en processed_jobs
',
        'analogy' => 'Imagina como opera una sucursal de correos concurrida:
1. EL ERROR SINCRONICO: Un cliente llega con 10 cajas pesadas. El cartero le dice: \'Espere aqui en la ventanilla mientras cargo las cajas en mi furgoneta, conduzco 15 kilometros hasta el aeropuerto y regreso a sellarle el comprobante\'. La fila de clientes en la oficina saldria a la calle y la sucursal colapsaria.
2. EL ENFOQUE ASINCRONO CON MENSAJES (Messenger): El cartero toma los 10 paquetes, les pega una etiqueta con un codigo de barras unico (Job ID / Idempotency Key), le entrega un recibo al cliente en 5 segundos (\'Paquetes Aceptados\') y coloca las cajas en la cinta transportadora del deposito (La Cola de Redis).
3. EL CONSUMIDOR EN EL DEPOSITO (Worker): En el sotano, un operario que no atiende publico toma las cajas de la cinta una por una (FIFO con array_shift) y las carga en los camiones al ritmo optimo de la flota.
4. LA PROTECCION IDEMPOTENTE: Si una caja se cae y vuelve a entrar en la cinta con el mismo codigo de barras ya despachado, el operario escanea la etiqueta, ve que ya fue procesada (isProcessed) y la aparta sin volver a enviarla.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Redis, el transporte de Symfony Messenger utiliza Redis Streams (XADD, XREADGROUP, XACK) o listas con BRPOPLPUSH / BLPOP para consumo FIFO bloqueante sin consumo ocioso de CPU.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Cuando el worker finaliza la ejecucion del handler con exito, envia un XACK a Redis confirmando que el mensaje puede eliminarse del Pending Entries List (PEL).',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Si el worker se cae por un fallo de hardware antes del XACK, Redis reasigna el mensaje a otro worker tras el timeout de visibilidad (redelivery).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Para implementar colas idempotentes en PHP: 1. Todo trabajo debe portar un identificador universal unico (UUID v4 o hash de negocio). 2. Antes de ejecutar el procesamiento, se comprueba si el jobId ya figura en el almacen de trabajos procesados. 3. Se extrae el trabajo en orden estricto FIFO mediante array_shift(). 4. Se ejecuta el Closure de procesamiento pasando el payload. 5. Se retorna una estructura asociativa con job_id, result y processed_at.',
        ],
    ],
    'video' => [
        'title' => 'Samuel Roze - Symfony Messenger: Messages, Queues, Workers and more',
        'speaker' => 'Samuel Roze (Creador de Symfony Messenger)',
        'youtube_id' => 'SgtCBcVyoEU',
        'duration' => '42 min',
        'description' => 'Samuel Roze explica en phpday la arquitectura del componente Messenger de Symfony, la separacion entre mensajes y manejadores, configuracion de transportes con Redis y tecnicas avanzadas de reintentos y tolerancia a fallos.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Queue;

use Closure;
use InvalidArgumentException;

final class IdempotentJobQueue
{
    /**
     * @var list<array{jobId: string, payload: array<string, mixed>}>
     */
    private array $queue = [];

    /**
     * @var array<string, bool>
     */
    private array $processedJobs = [];

    public function enqueue(string $jobId, array $payload): bool
    {
        $id = trim($jobId);
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador del trabajo no puede estar vacio.\');
        }

        if ($this->isProcessed($id)) {
            return false;
        }

        $this->queue[] = [
            \'jobId\' => $id,
            \'payload\' => $payload,
        ];

        return true;
    }

    /**
     * @return array{job_id: string, result: mixed, processed_at: string}|null
     */
    public function processNext(Closure $processor): ?array
    {
        if (empty($this->queue)) {
            return null;
        }

        /** @var array{jobId: string, payload: array<string, mixed>} $job */
        $job = array_shift($this->queue);
        $id = $job[\'jobId\'];

        if ($this->isProcessed($id)) {
            return null;
        }

        $result = $processor($job[\'payload\']);
        $this->processedJobs[$id] = true;

        return [
            \'job_id\' => $id,
            \'result\' => $result,
            \'processed_at\' => (new \\DateTimeImmutable())->format(\\DateTimeInterface::ATOM),
        ];
    }

    public function isProcessed(string $jobId): bool
    {
        $id = trim($jobId);
        return isset($this->processedJobs[$id]);
    }

    public function clear(): void
    {
        $this->queue = [];
        $this->processedJobs = [];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior ejecuta tareas pesadas directamente en el hilo de la peticion HTTP del usuario, provocando timeouts de 30 segundos y quejas de clientes. El desarrollador Senior desacopla la arquitectura con Messenger: el usuario recibe respuesta en 5ms, el trabajo se encola en Redis con identificador idempotente y se procesa con reintentos automaticos exponenciales en segundo plano.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Envia correos masivos con un bucle foreach dentro del controlador del checkout.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Despacha eventos de dominio inmutables hacia colas de Redis, procesados por workers desacoplados con confirmaciones ACK.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Desacoplamiento Asincrono',
            'source' => 'Enterprise Integration Patterns: Designing, Building, and Deploying Messaging Solutions',
            'quote' => 'El paso de mensajes asincrono permite a las aplicaciones comunicarse a traves de colas sin requerir que ambos sistemas esten disponibles al mismo microsegundo.',
            'author' => 'Gregor Hohpe & Bobby Woolf',
            'explanation' => 'Aisla los picos de trafico web de la capacidad de procesamiento de los sistemas internos.',
        ],
        [
            'topic' => 'Idempotencia en Colas',
            'source' => 'Designing Data-Intensive Applications',
            'quote' => 'En sistemas distribuidos, la semantica de entrega es \'al menos una vez\'. Por tanto, todo manejador de mensajes debe ser estrictamente idempotente para tolerar reintentos de red sin dano.',
            'author' => 'Martin Kleppmann',
            'explanation' => 'Procesar dos veces el mismo mensaje debe producir exactamente el mismo resultado final.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: The Messenger Component',
            'url' => 'https://symfony.com/doc/current/messenger.html',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Redis Streams Tutorial for Message Queues',
            'url' => 'https://redis.io/docs/data-types/streams/',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Cola de Trabajos Idempotente con Despacho FIFO (IdempotentJobQueue)',
        'objective' => 'Implementar la clase IdempotentJobQueue con enqueue(), processNext(), isProcessed() y clear(), validando $jobId no vacio con InvalidArgumentException, extrayendo trabajos en orden FIFO con array_shift(), ejecutando el Closure $processor($job[\'payload\']) y retornando job_id, result y processed_at.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Performance\\Queue.
2. Implementa la clase IdempotentJobQueue.
3. Implementa function enqueue(string $jobId, array $payload): bool; si $jobId esta vacio lanza \\InvalidArgumentException.
4. En enqueue(), si isProcessed($jobId) retorna false; en caso contrario encola el trabajo y retorna true.
5. Implementa function isProcessed(string $jobId): bool verificando si el trabajo ya fue procesado.
6. Implementa function processNext(\\Closure $processor): ?array; si la cola esta vacia retorna null.
7. Extrae el primer trabajo usando array_shift($this->queue).
8. Ejecuta $processor($job[\'payload\']), marca el trabajo como procesado y retorna un array con claves job_id, result y processed_at.
9. Implementa function clear(): void reseteando la cola y el registro de procesados.',
        'filename' => 'src/Performance/Queue/IdempotentJobQueue.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array privado $queue = [] y un array asociativo $processedJobs = [].',
                'Paso 2: En enqueue(), valida trim($jobId) !== \'\' arrojando InvalidArgumentException si esta vacio.',
                'Paso 3: En processNext(), si empty($this->queue) retorna null.',
                'Paso 4: Extrae con $job = array_shift($this->queue);',
                'Paso 5: Ejecuta $result = $processor($job[\'payload\']); y registra $this->processedJobs[$id] = true;',
                'Paso 6: Retorna el array asociativo con job_id, result y processed_at.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] FIFO significa First-In, First-Out: el primer trabajo encolado debe ser el primero extraido.',
            ],
            [
                'text' => '[Pista 2: Estructura] array_shift extrae y elimina el primer elemento del array.',
            ],
            [
                'text' => '[Pista 3: Snippet] $job = array_shift($this->queue); $res = $processor($job[\'payload\']); return [\'job_id\' => $job[\'jobId\'], \'result\' => $res, \'processed_at\' => date(\'c\')];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Queue;

use Closure;
use InvalidArgumentException;

class IdempotentJobQueue
{
    /**
     * @var list<array{jobId: string, payload: array<string, mixed>}>
     */
    private array $queue = [];

    /**
     * @var array<string, bool>
     */
    private array $processedJobs = [];

    public function enqueue(string $jobId, array $payload): bool
    {
        // TODO: Validar $jobId no vacio (lanzar InvalidArgumentException)
        // TODO: Si isProcessed($jobId), retornar false
        // TODO: Encolar trabajo y retornar true
        return false;
    }

    public function processNext(Closure $processor): ?array
    {
        // TODO: Si cola vacia retornar null
        // TODO: Extraer primer trabajo con array_shift
        // TODO: Ejecutar $processor($job[\'payload\'])
        // TODO: Marcar como procesado y retornar [\'job_id\' => ..., \'result\' => ..., \'processed_at\' => ...]
        return null;
    }

    public function isProcessed(string $jobId): bool
    {
        // TODO: Retornar si $jobId ya fue procesado
        return false;
    }

    public function clear(): void
    {
        // TODO: Vaciar cola y registro de procesados
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Performance\\Queue;

use Closure;
use InvalidArgumentException;

class IdempotentJobQueue
{
    /**
     * @var list<array{jobId: string, payload: array<string, mixed>}>
     */
    private array $queue = [];

    /**
     * @var array<string, bool>
     */
    private array $processedJobs = [];

    public function enqueue(string $jobId, array $payload): bool
    {
        $id = trim($jobId);
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador del trabajo no puede estar vacio.\');
        }

        if ($this->isProcessed($id)) {
            return false;
        }

        $this->queue[] = [
            \'jobId\' => $id,
            \'payload\' => $payload,
        ];

        return true;
    }

    public function processNext(Closure $processor): ?array
    {
        if (empty($this->queue)) {
            return null;
        }

        /** @var array{jobId: string, payload: array<string, mixed>} $job */
        $job = array_shift($this->queue);
        $id = $job[\'jobId\'];

        if ($this->isProcessed($id)) {
            return null;
        }

        $result = $processor($job[\'payload\']);
        $this->processedJobs[$id] = true;

        return [
            \'job_id\' => $id,
            \'result\' => $result,
            \'processed_at\' => (new \\DateTimeImmutable())->format(\\DateTimeInterface::ATOM),
        ];
    }

    public function isProcessed(string $jobId): bool
    {
        $id = trim($jobId);
        return isset($this->processedJobs[$id]);
    }

    public function clear(): void
    {
        $this->queue = [];
        $this->processedJobs = [];
    }
}
',
        'explanation' => 'La clase IdempotentJobQueue implementa el patron basico de cola de mensajes asincrona con deduplicacion. Garantiza orden secuencial FIFO mediante array_shift(), previene ejecuciones duplicadas con isProcessed() y encapsula la transformacion y marca de tiempo atomica al completar cada tarea.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Symfony Messenger & Redis Queues',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el beneficio principal de responder al usuario con HTTP 202 Accepted y delegar la operacion a Symfony Messenger?',
                'options' => [
                    'a' => 'Hace que la base de datos MySQL guarde la informacion en memoria RAM de manera irreversible.',
                    'b' => 'Libera de inmediato el socket de conexion y el hilo del worker PHP-FPM en milisegundos, permitiendo que el servidor soporte una concurrencia masiva de usuarios.',
                    'c' => 'Evita que el desarrollador tenga que escribir pruebas unitarias.',
                    'd' => 'Fuerza a que todos los navegadores web recarguen la pagina automaticamente.',
                ],
                'correct' => 'b',
                'explanation' => 'Al desacoplar el procesamiento pesado de la respuesta HTTP, el tiempo de respuesta pasa de varios segundos a 3ms, evitando que los hilos de PHP-FPM se agoten bajo picos de trafico.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por que los Message Handlers en sistemas distribuidos con colas deben ser disenados para ser \'Idempotentes\'?',
                'options' => [
                    'a' => 'Porque las colas garantizan entrega \'al menos una vez\' (At-Least-Once), por lo que un fallo de red temporal puede reenviar el mismo mensaje dos veces.',
                    'b' => 'Porque el motor PHP solo puede ejecutar un archivo .php una sola vez al dia.',
                    'c' => 'Porque los brokers de mensajeria como Redis eliminan los mensajes de forma aleatoria.',
                    'd' => 'Porque es un requisito de la licencia comercial de Symfony.',
                ],
                'correct' => 'a',
                'explanation' => 'En caso de un timeout de red antes del ACK, el broker vuelve a entregar el mensaje a otro worker. Si el handler no es idempotente, podria cobrar dos veces al usuario o duplicar un envio.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que comando de consola de Symfony se utiliza para iniciar el bucle continuo de consumo de mensajes de una cola en segundo plano?',
                'options' => [
                    'a' => 'bin/console messenger:consume <transporte>',
                    'b' => 'bin/console cache:clear --force',
                    'c' => 'bin/console server:run --async',
                    'd' => 'bin/console doctrine:fixtures:load',
                ],
                'correct' => 'a',
                'explanation' => 'El comando bin/console messenger:consume arranca el worker que escucha los transportes configurados (Redis, AMQP, Doctrine) y despacha los mensajes a sus respectivos manejadores.',
            ],
        ],
    ],
];
