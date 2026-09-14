<?php

declare(strict_types=1);

return [
    'slug' => 'system-design-high-throughput',
    'title' => 'Caso: 10,000 Requests/sec en Symfony + Redis',
    'module' => 'system-design',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Soportar 10,000 peticiones por segundo (RPS) en una aplicacion Symfony representa un salto cuantitativo y cualitativo de ingenieria. En una arquitectura tradicional PHP-FPM, cada proceso worker de PHP atiende una sola peticion web de manera sincronica. Si una peticion tarda un promedio de 50 milisegundos en responder, un solo worker puede atender como maximo 20 RPS (1000ms / 50ms). Para alcanzar 10,000 RPS, la infraestructura necesitaria 500 workers de PHP-FPM concurrentes, requiriendo decenas de gigabytes de memoria RAM y provocando un colapso por cambio de contexto en el procesador de Linux.

La solucion Senior descansa en tres pilares de diseno de sistemas:
1. Edge Caching y Reverse Proxies (Varnish, Cloudflare): absorber el 90% del trafico estatico y GET publico sin tocar PHP.
2. Limitacion de Tasa Granular (Rate Limiting): proteger la infraestructura contra abusos mediante el algoritmo de Ventana Deslizante (Sliding Window Log / Counter) en Redis.
3. Desacoplamiento Asincrono (Symfony Messenger + Redis Queues): para escrituras masivas (POST), responder inmediatamente 202 Accepted en 2 milisegundos y delegar el procesamiento pesado a trabajadores en segundo plano.',
        'problem' => 'Soportar 10,000 peticiones por segundo (RPS) en una aplicacion Symfony representa un salto cuantitativo y cualitativo de ingenieria. En una arquitectura tradicional PHP-FPM, cada proceso worker de PHP atiende una sola peticion web de manera sincronica. Si una peticion tarda un promedio de 50 milisegundos en responder, un solo worker puede atender como maximo 20 RPS (1000ms / 50ms). Para alcanzar 10,000 RPS, la infraestructura necesitaria 500 workers de PHP-FPM concurrentes, requiriendo decenas de gigabytes de memoria RAM y provocando un colapso por cambio de contexto en el procesador de Linux.',
        'solution' => 'La solucion Senior descansa en tres pilares de diseno de sistemas:
1. Edge Caching y Reverse Proxies (Varnish, Cloudflare): absorber el 90% del trafico estatico y GET publico sin tocar PHP.
2. Limitacion de Tasa Granular (Rate Limiting): proteger la infraestructura contra abusos mediante el algoritmo de Ventana Deslizante (Sliding Window Log / Counter) en Redis.
3. Desacoplamiento Asincrono (Symfony Messenger + Redis Queues): para escrituras masivas (POST), responder inmediatamente 202 Accepted en 2 milisegundos y delegar el procesamiento pesado a trabajadores en segundo plano.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Molinete del Metro en Hora Punta (Sliding Window)',
        'concept' => 'Imagina la estacion central de una linea de metro subterraneo a las 8:00 AM:
1. EL PROBLEMA (El Embudo): Si 10,000 personas intentaran entrar a la vez por una puerta estrecha, se produciria una avalancha humana peligrosa en el anden del tren.
2. EL MOLINETE ELECTRONICO CON VENTANA DESLIZANTE: Cada vez que un usuario pasa su tarjeta magnetica, el molinete registra la marca de tiempo exacta de su entrada en una lista electronica en memoria (Redis ZSET).
3. LA VENTANA DESLIZANTE DE 60 SEGUNDOS: El sensor no cuenta de minuto en minuto fijo (de 8:00 a 8:01), porque si 100 personas entran a las 8:00:59 y otras 100 a las 8:01:01, habrian entrado 200 personas en 2 segundos (el fallo de la ventana fija). En su lugar, mira hacia atras exactamente 60 segundos desde el instante presente. Si en ese lapso de 60 segundos deslizantes ya entraron mas del limite permitido, el molinete se bloquea de inmediato (return false).

El flujo de pasajeros queda perfectamente regulado y el anden jamas se satura.',
        'ascii_diagram' => 'VENTANA DESLIZANTE (Sliding Window Rate Limiter):

Instante actual ($now = 100.0s), Ventana ($windowSeconds = 60s)
Umbral de tiempo valido: [$now - $windowSeconds] = [40.0s a 100.0s]

Historial de marcas de tiempo en Redis para el cliente:
[ 15.2s,  28.4s ]  <--- Fuera de la ventana (< 40.0s) -> Se eliminan (pruning)
[ 42.1s,  58.0s,  75.4s,  92.1s,  99.8s ]  <--- Dentro de la ventana (5 peticiones)

Evaluacion de Cuota:
- Conteos validos: 5 peticiones
- ¿5 < maxRequests (10)? -> SI -> Guarda marca $now y retorna TRUE (Peticion permitida)
- Si conteo >= maxRequests -> NO -> Retorna FALSE (HTTP 429 Too Many Requests)
',
        'analogy' => 'Imagina la estacion central de una linea de metro subterraneo a las 8:00 AM:
1. EL PROBLEMA (El Embudo): Si 10,000 personas intentaran entrar a la vez por una puerta estrecha, se produciria una avalancha humana peligrosa en el anden del tren.
2. EL MOLINETE ELECTRONICO CON VENTANA DESLIZANTE: Cada vez que un usuario pasa su tarjeta magnetica, el molinete registra la marca de tiempo exacta de su entrada en una lista electronica en memoria (Redis ZSET).
3. LA VENTANA DESLIZANTE DE 60 SEGUNDOS: El sensor no cuenta de minuto en minuto fijo (de 8:00 a 8:01), porque si 100 personas entran a las 8:00:59 y otras 100 a las 8:01:01, habrian entrado 200 personas en 2 segundos (el fallo de la ventana fija). En su lugar, mira hacia atras exactamente 60 segundos desde el instante presente. Si en ese lapso de 60 segundos deslizantes ya entraron mas del limite permitido, el molinete se bloquea de inmediato (return false).

El flujo de pasajeros queda perfectamente regulado y el anden jamas se satura.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Redis, la implementacion optima de Sliding Window utiliza Sorted Sets (ZSET).',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Se invoca ZREMRANGEBYSCORE key 0 (now - window) para purgar marcas obsoletas, ZADD key now now para registrar la marca actual y ZCARD key para contar las peticiones dentro de la ventana.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Todo esto se ejecuta atomicamente mediante un script de Lua o un bloque MULTI/EXEC en Redis, garantizando que ninguna condicion de carrera pueda exceder la cuota bajo alta concurrencia.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'En PHP puro (o en entornos en memoria para pruebas de alta fidelidad), la clase SlidingWindowRateLimiter mantiene un array asociativo de marcas de tiempo flotantes obtenidas mediante microtime(true). Al consultar isAllowed(), filtra las marcas anteriores al umbral ($now - $windowSeconds), compara el conteo resultante con $maxRequests, agrega la marca actual si califica y retorna true o false con precision quirurgica.',
        ],
    ],
    'video' => [
        'title' => 'Redis In-Memory Database Crash Course',
        'speaker' => 'Hussein Nasser',
        'youtube_id' => 'V7FPk4J10KI',
        'duration' => '1h 05m',
        'description' => 'Hussein Nasser profundiza en el modelo de hilos de Redis, estructuras de datos en memoria (Strings, Hashes, Sorted Sets), almacenamiento de latencia sub-milisegundo y tecnicas para escalar arquitecturas web hasta decenas de miles de operaciones por segundo.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Limiter;

use InvalidArgumentException;

final class SlidingWindowRateLimiter
{
    /**
     * @var array<string, list<float>>
     */
    private array $requestLogs = [];

    public function isAllowed(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $cleanKey = trim($key);
        if ($cleanKey === \'\') {
            throw new InvalidArgumentException(\'La clave de rate limiting no puede estar vacia.\');
        }

        if ($maxRequests <= 0) {
            throw new InvalidArgumentException(\'El numero maximo de peticiones debe ser mayor a 0.\');
        }

        if ($windowSeconds <= 0) {
            throw new InvalidArgumentException(\'La ventana de tiempo debe ser mayor a 0 segundos.\');
        }

        $now = microtime(true);
        $threshold = $now - (float) $windowSeconds;

        $timestamps = $this->requestLogs[$cleanKey] ?? [];

        // Filtrar marcas de tiempo que cayeron fuera de la ventana deslizante
        $validTimestamps = [];
        foreach ($timestamps as $ts) {
            if ($ts >= $threshold) {
                $validTimestamps[] = $ts;
            }
        }

        if (count($validTimestamps) >= $maxRequests) {
            $this->requestLogs[$cleanKey] = $validTimestamps;
            return false;
        }

        $validTimestamps[] = $now;
        $this->requestLogs[$cleanKey] = $validTimestamps;

        return true;
    }

    public function reset(string $key): void
    {
        $cleanKey = trim($key);
        unset($this->requestLogs[$cleanKey]);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior cree que para soportar 10k RPS la unica respuesta es comprar servidores mas grandes en AWS. El desarrollador Senior aplica diseno de sistemas: analiza los cuellos de botella del runtime, introduce edge caching, implementa rate limiters con algoritmos de ventana deslizante en Redis y desacopla la persistencia pesada con colas asincronas, logrando atender 10k RPS con una fraccion del costo de infraestructura.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Deja que cada peticion HTTP golpee directamente a PHP-FPM y MySQL, colapsando el servidor ante el primer pico de trafico.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Protege los endpoints con un Sliding Window Rate Limiter y cache en Redis, garantizando estabilidad y retorno inmediato de 429 Too Many Requests ante abusos.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Arquitecturas de Alto Rendimiento',
            'source' => 'Designing Data-Intensive Applications',
            'quote' => 'Un sistema escalable no es aquel que resuelve todos los problemas con mas hardware, sino aquel que degrada elegantemente bajo sobrecarga protegiendo sus recursos criticos mediante limitacion de tasa y colas asincronas.',
            'author' => 'Martin Kleppmann',
            'explanation' => 'El Rate Limiting previene que el exceso de peticiones sature la base de datos.',
        ],
        [
            'topic' => 'El Algoritmo de Ventana Deslizante',
            'source' => 'System Design Interview – An Insider\'s Guide',
            'quote' => 'A diferencia del contador de ventana fija, la ventana deslizante previene picos de trafico en los limites de los minutos, ofreciendo la regulacion mas estricta y justa para APIs de alta concurrencia.',
            'author' => 'Alex Xu',
            'explanation' => 'Garantiza que el limite de peticiones nunca se supere en ningun intervalo continuo de tiempo.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Cloudflare: How Rate Limiting Protects APIs',
            'url' => 'https://www.cloudflare.com/learning/bots/what-is-rate-limiting/',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Symfony RateLimiter: Sliding Window Implementation',
            'url' => 'https://symfony.com/doc/current/rate_limiter.html#sliding-window',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Limitador de Tasa por Ventana Deslizante (SlidingWindowRateLimiter)',
        'objective' => 'Implementar la clase SlidingWindowRateLimiter para proteger endpoints contra sobrecarga, validando parametros con InvalidArgumentException, midiendo marcas temporales con microtime(true), purgando marcas obsoletas anteriores a ($now - $windowSeconds) y retornando true o false.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\SystemDesign\\Limiter.
2. Implementa la clase SlidingWindowRateLimiter.
3. Implementa function isAllowed(string $key, int $maxRequests, int $windowSeconds): bool.
4. Si trim($key) esta vacia, $maxRequests <= 0 o $windowSeconds <= 0, lanza \\InvalidArgumentException.
5. Utiliza microtime(true) para obtener el instante actual ($now).
6. Filtra la lista de marcas previas para retener unicamente aquellas >= ($now - $windowSeconds).
7. Si count($validTimestamps) >= $maxRequests, actualiza el registro y retorna false.
8. En caso contrario, anade $now a la lista, actualiza el registro y retorna true.
9. Implementa function reset(string $key): void que elimine la clave sanitizada con trim().',
        'filename' => 'src/SystemDesign/Limiter/SlidingWindowRateLimiter.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array privado $requestLogs = [].',
                'Paso 2: En isAllowed(), valida los argumentos y lanza InvalidArgumentException ante valores no validos.',
                'Paso 3: Obtiene $now = microtime(true) y calcula $threshold = $now - (float) $windowSeconds.',
                'Paso 4: Filtra el array de marcas de la clave reteniendo solo las >= $threshold.',
                'Paso 5: Si el conteo supera o iguala a $maxRequests, retorna false.',
                'Paso 6: Si hay cupo, agrega $now y retorna true.',
                'Paso 7: En reset(), elimina la clave de $requestLogs.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] La ventana deslizante cuenta cuantas peticiones ocurrieron en los ultimos $windowSeconds a partir de $now.',
            ],
            [
                'text' => '[Pista 2: Estructura] if (count($validTimestamps) >= $maxRequests) { return false; } return true;',
            ],
            [
                'text' => '[Pista 3: Snippet] $threshold = microtime(true) - (float) $windowSeconds; $valid = array_filter($logs, fn($ts) => $ts >= $threshold);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Limiter;

use InvalidArgumentException;

class SlidingWindowRateLimiter
{
    /**
     * @var array<string, list<float>>
     */
    private array $requestLogs = [];

    public function isAllowed(string $key, int $maxRequests, int $windowSeconds): bool
    {
        // TODO: Validar que $key no sea vacia, $maxRequests > 0 y $windowSeconds > 0 (lanzar InvalidArgumentException)
        // TODO: Filtrar marcas mayores o iguales a (microtime(true) - $windowSeconds)
        // TODO: Si conteo >= $maxRequests retornar false; de lo contrario registrar $now y retornar true
        return false;
    }

    public function reset(string $key): void
    {
        // TODO: Eliminar clave sanitizada con trim()
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\SystemDesign\\Limiter;

use InvalidArgumentException;

class SlidingWindowRateLimiter
{
    /**
     * @var array<string, list<float>>
     */
    private array $requestLogs = [];

    public function isAllowed(string $key, int $maxRequests, int $windowSeconds): bool
    {
        $cleanKey = trim($key);
        if ($cleanKey === \'\') {
            throw new InvalidArgumentException(\'La clave de rate limiting no puede estar vacia.\');
        }

        if ($maxRequests <= 0) {
            throw new InvalidArgumentException(\'El numero maximo de peticiones debe ser mayor a 0.\');
        }

        if ($windowSeconds <= 0) {
            throw new InvalidArgumentException(\'La ventana de tiempo debe ser mayor a 0 segundos.\');
        }

        $now = microtime(true);
        $threshold = $now - (float) $windowSeconds;

        $timestamps = $this->requestLogs[$cleanKey] ?? [];

        $validTimestamps = [];
        foreach ($timestamps as $ts) {
            if ($ts >= $threshold) {
                $validTimestamps[] = $ts;
            }
        }

        if (count($validTimestamps) >= $maxRequests) {
            $this->requestLogs[$cleanKey] = $validTimestamps;
            return false;
        }

        $validTimestamps[] = $now;
        $this->requestLogs[$cleanKey] = $validTimestamps;

        return true;
    }

    public function reset(string $key): void
    {
        $cleanKey = trim($key);
        unset($this->requestLogs[$cleanKey]);
    }
}
',
        'explanation' => 'La clase SlidingWindowRateLimiter implementa el algoritmo de limitacion de tasa mas preciso de la industria. Al calcular la ventana continua retrospectiva con microtime(true), elimina el problema de picos en los bordes de las ventanas fijas y responde booleanamente con minima sobrecarga computacional.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Caso: 10,000 Requests/sec en Symfony + Redis',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el principal defecto del algoritmo de Rate Limiting de \'Ventana Fija\' (Fixed Window Counter) que el algoritmo de \'Ventana Deslizante\' resuelve?',
                'options' => [
                    'a' => 'Que la ventana fija requiere almacenar los datos en formato XML.',
                    'b' => 'Que un atacante puede enviar el doble de la cuota maxima en un lapso muy corto si concentra peticiones al final de una ventana y al inicio de la siguiente.',
                    'c' => 'Que la ventana fija no funciona con direcciones IPv4.',
                    'd' => 'Que la ventana fija consume toda la CPU del servidor en cada ciclo.',
                ],
                'correct' => 'b',
                'explanation' => 'En una ventana fija de 1 minuto con limite de 100 reqs, enviar 100 reqs a las 00:59 y otras 100 reqs a las 01:00 permite 200 peticiones en 2 segundos. La ventana deslizante mide continuamente 60 segundos hacia atras, bloqueando ese pico.',
            ],
            [
                'id' => 'q2',
                'question' => 'En una arquitectura web para 10,000 RPS, ¿por que es esencial desacoplar las operaciones de escritura (POST) con colas asincronas (Symfony Messenger + Redis)?',
                'options' => [
                    'a' => 'Porque las bases de datos relacionales no admiten peticiones POST.',
                    'b' => 'Porque permite responder de inmediato al cliente HTTP con status 202 Accepted en 2ms, protegiendo a la base de datos de picos concurrentes al procesar las escrituras de manera controlada con workers en segundo plano.',
                    'c' => 'Porque PHP no puede ejecutar consultas INSERT sin reiniciar el servidor.',
                    'd' => 'Porque el estandar PSR-12 exige que todo comando POST use Redis de manera obligatoria.',
                ],
                'correct' => 'b',
                'explanation' => 'Al poner el trabajo pesado en una cola, la peticion HTTP se completa en microsegundos y la base de datos procesa el lote a un ritmo constante y controlado sin saturar sus conexiones.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cual codigo de estado HTTP formal debe retornar una API cuando un cliente excede su cuota de Rate Limiting?',
                'options' => [
                    'a' => '400 Bad Request',
                    'b' => '403 Forbidden',
                    'c' => '429 Too Many Requests',
                    'd' => '503 Service Unavailable',
                ],
                'correct' => 'c',
                'explanation' => 'El RFC 6585 define formalmente el codigo HTTP 429 Too Many Requests para indicar que el usuario ha enviado demasiadas solicitudes en un periodo de tiempo determinado.',
            ],
        ],
    ],
];
