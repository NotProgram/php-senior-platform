<?php

declare(strict_types=1);

return [
    'slug' => 'system-design-canvas',
    'title' => 'Laboratorio Interactivo de Arquitecturas Distribuidas',
    'module' => 'system-design',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En el diseno de sistemas de alto volumen (System Design), el patron de almacenamiento en cache no es una simple optimización opcional; es el escudo protector que previene el colapso de la base de datos relacional. Sin embargo, implementar cache de manera ingenua suele crear problemas peores que los que intenta resolver: inconsistencia de datos por invalidaciones fallidas y la temida Avalancha de Cache (Cache Stampede o Dog-piling), donde miles de peticiones simultaneas descubren que una clave expiro al mismo microsegundo e intentan recalcularla a la vez contra MySQL, saturando las conexiones de base de datos.

El patron Cache-Aside (Lazy Loading de Cache) es la estrategia enterprise por excelencia: 1. La aplicacion solicita la clave al almacen de cache (Redis / Memcached).
2. Si existe (Cache Hit), se retorna de inmediato en menos de 1 milisegundo.
3. Si no existe o expiro (Cache Miss), se ejecuta la funcion de contingencia (Fallback Closure) contra la base de datos, se calcula el valor fresco, se almacena en cache con su TTL correspondiente y se retorna al cliente.',
        'problem' => 'En el diseno de sistemas de alto volumen (System Design), el patron de almacenamiento en cache no es una simple optimización opcional; es el escudo protector que previene el colapso de la base de datos relacional. Sin embargo, implementar cache de manera ingenua suele crear problemas peores que los que intenta resolver: inconsistencia de datos por invalidaciones fallidas y la temida Avalancha de Cache (Cache Stampede o Dog-piling), donde miles de peticiones simultaneas descubren que una clave expiro al mismo microsegundo e intentan recalcularla a la vez contra MySQL, saturando las conexiones de base de datos.',
        'solution' => 'El patron Cache-Aside (Lazy Loading de Cache) es la estrategia enterprise por excelencia: 1. La aplicacion solicita la clave al almacen de cache (Redis / Memcached).
2. Si existe (Cache Hit), se retorna de inmediato en menos de 1 milisegundo.
3. Si no existe o expiro (Cache Miss), se ejecuta la funcion de contingencia (Fallback Closure) contra la base de datos, se calcula el valor fresco, se almacena en cache con su TTL correspondiente y se retorna al cliente.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Pizarra de la Mesa de Despacho vs el Archivo Central Subterraneo',
        'concept' => 'Imagina a un recepcionista de hotel atendiendo a cientos de turistas en temporada alta:
1. LA PIZARRA DE PARED (La Cache en Memoria): Detras del mostrador hay una pequena pizarra donde estan anotadas las 10 preguntas mas frecuentes: la contrasena del Wi-Fi, la hora del desayuno y el telefono del taxi oficial. Cuando un turista pregunta el Wi-Fi, el recepcionista gira la cabeza y responde en 0.5 segundos (Cache Hit).
2. EL ARCHIVO CENTRAL (La Base de Datos): En el sotano del hotel hay un archivador blindado con miles de carpetas con las reservas de los ultimos 5 anos. Bajar al sotano, abrir el candado y buscar una carpeta toma 15 minutos (Cache Miss).
3. LA INVALIDACION: Si la empresa de telecomunicaciones cambia la clave del Wi-Fi a mediodia, el conserje toma un borrador y borra inmediatamente la pizarra (Invalidacion). La siguiente persona provocara que el recepcionista baje al sotano a leer la nueva clave del contrato y la vuelva a anotar en la pizarra.

Si la pizarra no existiera y el recepcionista tuviera que bajar al sotano para responder cada pregunta de Wi-Fi, la fila de turistas saldria a la calle y el hotel colapsaria.',
        'ascii_diagram' => 'PATRON CACHE-ASIDE (Lectura y Recalculo con Fallback):

Cliente ---> [ CacheAsideManager::get($key, $ttl, $fallback) ]
                                |
                   1. ¿Clave en Cache?
                     /                \\
             SI (Hit)                  NO (Miss / Expirada)
               /                            \\
              v                              v
   Retorna dato inmediato           2. Ejecuta $fallback()
   (Tiempo: < 1ms)                  (Query SQL a Base de Datos)
                                             |
                                             v
                                    3. Guarda en Cache con TTL
                                    (microtime(true) + $ttl)
                                             |
                                             v
                                    4. Retorna dato fresco
',
        'analogy' => 'Imagina a un recepcionista de hotel atendiendo a cientos de turistas en temporada alta:
1. LA PIZARRA DE PARED (La Cache en Memoria): Detras del mostrador hay una pequena pizarra donde estan anotadas las 10 preguntas mas frecuentes: la contrasena del Wi-Fi, la hora del desayuno y el telefono del taxi oficial. Cuando un turista pregunta el Wi-Fi, el recepcionista gira la cabeza y responde en 0.5 segundos (Cache Hit).
2. EL ARCHIVO CENTRAL (La Base de Datos): En el sotano del hotel hay un archivador blindado con miles de carpetas con las reservas de los ultimos 5 anos. Bajar al sotano, abrir el candado y buscar una carpeta toma 15 minutos (Cache Miss).
3. LA INVALIDACION: Si la empresa de telecomunicaciones cambia la clave del Wi-Fi a mediodia, el conserje toma un borrador y borra inmediatamente la pizarra (Invalidacion). La siguiente persona provocara que el recepcionista baje al sotano a leer la nueva clave del contrato y la vuelva a anotar en la pizarra.

Si la pizarra no existiera y el recepcionista tuviera que bajar al sotano para responder cada pregunta de Wi-Fi, la fila de turistas saldria a la calle y el hotel colapsaria.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En PHP 8.4, el gestor de cache debe operar con marcas de tiempo de alta precision mediante microtime(true), evitando la imprecision de la funcion time() tradicional de enteros.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'La clave debe sanitizarse con trim() y validarse estrictamente: una clave vacia o un TTL menor o igual a 0 debe arrojar InvalidArgumentException.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'El dato almacenado se envuelve en una estructura asociativa que incluye el payload y la marca de tiempo exacta de expiracion (expires_at).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Para combatir el Cache Stampede en sistemas de alta concurrencia, los ingenieros Senior utilizan dos tecnicas: 1. Probabilistic Early Expiration (Algoritmo XFetch): recalcula el dato en segundo plano unos segundos antes de que expire, basandose en la probabilidad del tiempo de ejecucion del query. 2. Distributed Locks con Mutex (Redis SETNX): solo un worker obtiene el candado para ejecutar el fallback contra MySQL, mientras los demas esperan 50ms a que el dato fresco este disponible en cache.',
        ],
    ],
    'video' => [
        'title' => 'The Barebones of Distributed Systems',
        'speaker' => 'Hussein Nasser',
        'youtube_id' => 'uR4YjsrBj14',
        'duration' => '28 min',
        'description' => 'Hussein Nasser desglosa los fundamentos esenciales de los sistemas distribuidos: comunicacion por red, particionamiento, consistencia, latencia y el rol critico de las capas de cache en memoria.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Cache;

use Closure;
use InvalidArgumentException;

final class CacheAsideManager
{
    /**
     * @var array<string, array{data: mixed, expires_at: float}>
     */
    private array $storage = [];

    public function get(string $key, int $ttlSeconds, Closure $fallback): mixed
    {
        $cleanKey = trim($key);
        if ($cleanKey === \'\') {
            throw new InvalidArgumentException(\'La clave de cache no puede estar vacia.\');
        }

        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException(\'El tiempo de vida (TTL) debe ser mayor a 0 segundos.\');
        }

        $now = microtime(true);

        if (isset($this->storage[$cleanKey])) {
            $entry = $this->storage[$cleanKey];
            if ($entry[\'expires_at\'] > $now) {
                return $entry[\'data\'];
            }
            // Clave expirada
            unset($this->storage[$cleanKey]);
        }

        // Cache Miss: ejecutar fallback
        $freshData = $fallback();

        $this->storage[$cleanKey] = [
            \'data\' => $freshData,
            \'expires_at\' => $now + (float) $ttlSeconds,
        ];

        return $freshData;
    }

    public function invalidate(string $key): void
    {
        $cleanKey = trim($key);
        unset($this->storage[$cleanKey]);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior coloca cache en todas partes sin definir una estrategia de invalidacion ni controlar el TTL, provocando datos corruptos y errores inexplicables para los usuarios. El desarrollador Senior analiza la tasa de cambio de los datos (Read/Write Ratio): almacena en cache unicamente lecturas frecuentes con TTL predecible y disena invalidaciones explicitas tras mutaciones de negocio.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Carga la base de datos con millones de consultas identicas por segundo sin ningun mecanismo de cache.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Implementa Cache-Aside con TTL determinista y fallback encapsulado, reduciendo la carga en la base de datos en un 95%.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Las Dos Cosas Dificiles en Informatica',
            'source' => 'Two Hard Things / Martin Fowler Bliki',
            'quote' => 'Solo hay dos cosas dificiles en las Ciencias de la Computacion: la invalidacion de la cache y ponerle nombre a las cosas.',
            'author' => 'Phil Karlton',
            'explanation' => 'Garantizar que los datos en cache reflejen la realidad tras mutaciones complejas es uno de los mayores desafios de la ingenieria.',
        ],
        [
            'topic' => 'El Teorema CAP',
            'source' => 'Brewer\'s Conjecture and the Feasibility of Consistent, Available, Partition-Tolerant Web Services',
            'quote' => 'En un sistema distribuido asincrono, es imposible garantizar simultaneamente Consistencia (Consistency), Disponibilidad (Availability) y Tolerancia a Particiones (Partition Tolerance).',
            'author' => 'Eric Brewer & Seth Gilbert',
            'explanation' => 'Al cachear datos se sacrifica consistencia estricta en favor de disponibilidad inmediata y minima latencia.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Microsoft Architecture Guides: Cache-Aside Pattern',
            'url' => 'https://learn.microsoft.com/en-us/azure/architecture/patterns/cache-aside',
            'type' => 'GUIDE',
        ],
        [
            'title' => 'Redis Best Practices: Caching Strategies',
            'url' => 'https://redis.io/docs/manual/patterns/',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Gestor de Cache-Aside con Expiracion y Fallback (CacheAsideManager)',
        'objective' => 'Implementar la clase CacheAsideManager para gestionar lecturas con cache en memoria, validando claves y TTL, ejecutando el Closure de fallback ante cache miss o expiracion, utilizando microtime(true) y permitiendo la invalidacion explicita de claves.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\SystemDesign\\Cache.
2. Implementa la clase CacheAsideManager.
3. Implementa function get(string $key, int $ttlSeconds, \\Closure $fallback): mixed.
4. Sanitiza $key con trim(); si queda vacia o $ttlSeconds <= 0, lanza \\InvalidArgumentException.
5. Utiliza microtime(true) para comprobar si la clave existe y no ha expirado; si es valida, retorna el dato en cache.
6. Si la clave no existe o expiro, ejecuta $freshData = $fallback(), almacena el dato con su marca expires_at ($now + $ttlSeconds) y retornalo.
7. Implementa function invalidate(string $key): void que elimine la clave del almacenamiento con trim().',
        'filename' => 'src/SystemDesign/Cache/CacheAsideManager.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array asociativo privado $storage = [].',
                'Paso 2: En get(), valida que trim($key) !== \'\' y $ttlSeconds > 0, de lo contrario lanza InvalidArgumentException.',
                'Paso 3: Si la clave existe y $entry[\'expires_at\'] > microtime(true), retorna el dato almacenado.',
                'Paso 4: Ante un cache miss o dato vencido, ejecuta $fallback(), guarda el array con \'data\' y \'expires_at\', y retorna.',
                'Paso 5: En invalidate(), elimina la clave de $this->storage.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Cache-Aside solo invoca el fallback cuando el dato no esta en cache o ya supero su tiempo de expiracion.',
            ],
            [
                'text' => '[Pista 2: Estructura] En get(): if (isset($this->storage[$cleanKey]) && $this->storage[$cleanKey][\'expires_at\'] > $now) { return ...; }',
            ],
            [
                'text' => '[Pista 3: Snippet] $fresh = $fallback(); $this->storage[$cleanKey] = [\'data\' => $fresh, \'expires_at\' => microtime(true) + $ttlSeconds]; return $fresh;',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Cache;

use Closure;
use InvalidArgumentException;

class CacheAsideManager
{
    /**
     * @var array<string, array{data: mixed, expires_at: float}>
     */
    private array $storage = [];

    public function get(string $key, int $ttlSeconds, Closure $fallback): mixed
    {
        // TODO: Validar $key no vacia y $ttlSeconds > 0 (lanzar InvalidArgumentException)
        // TODO: Comprobar existencia y vigencia con microtime(true)
        // TODO: Si miss o expirada, ejecutar $fallback(), almacenar con expires_at y retornar
        return null;
    }

    public function invalidate(string $key): void
    {
        // TODO: Eliminar clave sanitizada con trim()
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Cache;

use Closure;
use InvalidArgumentException;

class CacheAsideManager
{
    /**
     * @var array<string, array{data: mixed, expires_at: float}>
     */
    private array $storage = [];

    public function get(string $key, int $ttlSeconds, Closure $fallback): mixed
    {
        $cleanKey = trim($key);
        if ($cleanKey === \'\') {
            throw new InvalidArgumentException(\'La clave de cache no puede estar vacia.\');
        }

        if ($ttlSeconds <= 0) {
            throw new InvalidArgumentException(\'El TTL debe ser mayor a 0 segundos.\');
        }

        $now = microtime(true);

        if (isset($this->storage[$cleanKey])) {
            $entry = $this->storage[$cleanKey];
            if ($entry[\'expires_at\'] > $now) {
                return $entry[\'data\'];
            }
            unset($this->storage[$cleanKey]);
        }

        $fresh = $fallback();

        $this->storage[$cleanKey] = [
            \'data\' => $fresh,
            \'expires_at\' => $now + (float) $ttlSeconds,
        ];

        return $fresh;
    }

    public function invalidate(string $key): void
    {
        $cleanKey = trim($key);
        unset($this->storage[$cleanKey]);
    }
}
',
        'explanation' => 'La clase CacheAsideManager implementa el patron estandar de recuperacion y almacenamiento en cache diferido. Utiliza microtime(true) para garantizar precision temporal submilisegundo, verifica invariantes estrictas y encapsula la llamada al callback de base de datos solo cuando es estrictamente necesario.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Laboratorio Interactivo de Arquitecturas Distribuidas',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿En que consiste el patron de cache \'Cache-Aside\'?',
                'options' => [
                    'a' => 'La aplicacion siempre escribe directamente en el disco duro y la base de datos lee de la cache.',
                    'b' => 'La aplicacion consulta primero la cache; ante un hit retorna el valor, y ante un miss consulta el almacenamiento primario, actualiza la cache y retorna.',
                    'c' => 'El motor MySQL se encarga automaticamente de clonar las tablas en la memoria del navegador.',
                    'd' => 'La cache se vacia por completo cada 60 segundos de forma obligatoria.',
                ],
                'correct' => 'b',
                'explanation' => 'Cache-Aside coloca la responsabilidad de la coordinacion entre la cache y la base de datos en la aplicacion, permitiendo cargar en memoria unicamente los datos realmente solicitados.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Que fenomeno describe la \'Avalancha de Cache\' (Cache Stampede / Dog-piling)?',
                'options' => [
                    'a' => 'Un virus informatico que borra las claves de Redis.',
                    'b' => 'Cuando una clave con alto volumen de trafico expira, cientos o miles de peticiones simultaneas intentan recalcular el dato a la vez contra la base de datos, saturando el servidor relacional.',
                    'c' => 'Cuando la memoria RAM del servidor supera el 100% y se reinicia el sistema operativo.',
                    'd' => 'Cuando un usuario envia mas de 100 caracteres en una clave de cache.',
                ],
                'correct' => 'b',
                'explanation' => 'El Cache Stampede ocurre cuando expira una clave popular. Al no estar en cache, todas las peticiones concurrentes golpean a la base de datos simultaneamente provocando picos letales de CPU y conexiones.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que se recomienda utilizar microtime(true) en lugar de time() para gestionar marcas de expiracion en cache?',
                'options' => [
                    'a' => 'Porque time() solo funciona en sistemas operativos Windows.',
                    'b' => 'Porque microtime(true) provee precision en microsegundos como numero flotante, permitiendo mediciones y expiraciones exactas en sistemas con alta tasa de peticiones por segundo.',
                    'c' => 'Porque time() esta obsoleto en PHP 8.4.',
                    'd' => 'Porque microtime(true) encripta los datos automaticamente con AES-256.',
                ],
                'correct' => 'b',
                'explanation' => 'time() solo otorga precision de segundos enteros. En sistemas de alto rendimiento que procesan miles de peticiones por segundo, la precision de microsegundos es esencial para calcular TTLs y ventanas de tiempo exactas.',
            ],
        ],
    ],
];
