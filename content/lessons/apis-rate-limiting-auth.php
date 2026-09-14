<?php

declare(strict_types=1);

return [
    'slug' => 'apis-rate-limiting-auth',
    'title' => 'Rate Limiting, Claves de Idempotencia & Prevención de Doble Cobro',
    'module' => 'APIs RESTful',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En APIs distribuidas de alta criticidad (como pasarelas de pago y facturación), los fallos de red temporales provocan que los clientes reintenten peticiones POST. La idempotencia garantiza que ejecutar una operación múltiples veces produzca exactamente el mismo efecto secundario que ejecutarla una sola vez.',
        'problem' => 'La falta de soporte para claves de idempotencia (\'Idempotency-Key\') provoca que un cliente que reintenta un pago tras un timeout de red reciba un doble cargo bancario, generando pérdidas financieras y quejas de clientes.',
        'solution' => 'Implementar la clase \'IdempotencyKeyManager\' con \'validateKey(string $key): bool\' validando formato canónico UUID v4 mediante \'preg_match()\', y \'buildCachedResponse\' validando códigos de estado HTTP (100 a 599 lanzando InvalidArgumentException) e incorporando la marca temporal \'cached_at\'.',
        'problem_label' => 'El Problema: Reintentos de Red y el Peligro del Doble Cobro',
        'solution_label' => 'La Solución Senior: Claves de Idempotencia UUID v4 y Respuestas Cacheadas',
    ],
    'mental_model' => [
        'title' => 'El Ticket de Turno Numérico con Huella Dactilar vs Cobrar Dos Veces',
        'analogy' => 'Imagina un cliente en un restaurante que paga su cuenta con tarjeta de crédito. El datáfono se congela 10 segundos por una micro-caída de WiFi. El cliente, impaciente, vuelve a presionar el botón \'Pagar\'. Si la API de pagos no es **Idempotente**, el banco procesará dos cargos separados de $100 ($200 cobrados al cliente). Una **Clave de Idempotencia (Idempotency-Key Header)** es un ticket de turno con una huella dactilar criptográfica única generada por el cliente móvil (ej. UUID v4 `9b1deb4d-3b7d-4bad-9bdd-2b0d7b3dcb6d`). El gestor de idempotencia (`IdempotencyKeyManager`) valida el formato canónico UUID v4 con `preg_match()`. Si es la primera vez que ve la clave, procesa el cobro y guarda en memoria Redis la respuesta HTTP completa (`status`, `headers`, `body`, y `cached_at`). Si la petición se repite por un reintento de red, el gestor **no vuelve a cobrar**: devuelve de inmediato la respuesta guardada en la caché, garantizando que la operación produzca exactamente el mismo efecto sin importar cuántas veces se reintente.',
        'ascii_diagram' => 'PETICIÓN 1: POST /api/v1/payments (Idempotency-Key: e4b2f1... UUID v4)
     │
     ▼
IdempotencyKeyManager: ¿Existe la clave en Redis?
     ├── NO ──> Ejecuta el cargo bancario ($100)
     │          Almacena en Redis: [status: 201, body, cached_at: 1718000000]
     │          Retorna HTTP 201 Created al cliente
     │
CAÍDA DE RED: El cliente no recibe el ACK y reintenta la misma petición
     │
PETICIÓN 2 (Reintento con la misma Idempotency-Key):
     │
     ▼
IdempotencyKeyManager: ¿Existe la clave en Redis?
     └── SI ──> ¡Intercepta! No vuelve a tocar el banco.
                Devuelve la respuesta cacheada con cached_at.
                ¡Cero doble cobro!',
        'key_concept' => 'La idempotencia garantiza que ejecutar una operación múltiples veces produzca el mismo resultado que ejecutarla una sola vez. Una clave de idempotencia UUID v4 permite cachear y devolver la respuesta original sin repetir la mutación.',
    ],
    'internals' => [
        'title' => 'Mecánica de la Idempotencia en APIs Distribuidas',
        'steps' => [
            [
                'phase' => '1. Validación del Formato UUID v4 Canónico',
                'description' => 'Se valida la clave enviada en la cabecera \'Idempotency-Key\' mediante una expresión regular que asegure 36 caracteres hexadecimales con guiones en formato 8-4-4-4-12.',
            ],
            [
                'phase' => '2. Adquisición de Candado Distribuido (Lock)',
                'description' => 'En entornos concurrentes, se adquiere un lock en Redis con TTL breve sobre la clave. Si dos peticiones idénticas llegan exactamente en el mismo milisegundo, la segunda espera a que la primera termine.',
            ],
            [
                'phase' => '3. Verificación de Respuesta Cacheada',
                'description' => 'Si la clave ya existe en el almacén de caché, se devuelve directamente el payload almacenado junto con la cabecera \'X-Cache-Lookup: HIT\'.',
            ],
            [
                'phase' => '4. Ejecución del Proceso de Negocio y Construcción de Caché',
                'description' => 'Si la clave es nueva, se ejecuta la operación de negocio, se valida que el código HTTP esté en el rango 100-599, y se registra la respuesta serializada junto a la marca \'cached_at\'.',
            ],
            [
                'phase' => '5. TTL y Expiración de Claves',
                'description' => 'Las claves de idempotencia suelen tener un tiempo de vida (TTL) de 24 a 48 horas en Redis, tiempo suficiente para cubrir cualquier ventana de reintento de clientes.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Métodos HTTP Naturalmente Idempotentes vs POST',
            'icon' => 'globe',
            'content' => 'Según el estándar HTTP (RFC 9110):

• **Idempotentes por definición:** `GET`, `HEAD`, `PUT`, `DELETE`, `OPTIONS`. Si ejecutas `DELETE /users/5` diez veces, el usuario 5 seguirá eliminado.
• **NO Idempotente por definición:** `POST`. Si ejecutas `POST /payments` tres veces, crearás tres cobros separados.

Por esta razón, las pasarelas de pago profesionales (como Stripe o PayPal) exigen la cabecera `Idempotency-Key` en operaciones `POST` para transformar un método no idempotente en una operación determinista y segura.',
            'takeaways' => 'Exige claves de idempotencia en endpoints POST que muten saldos o recursos críticos para prevenir efectos duplicados ante fallos de red.',
        ],
        [
            'title' => 'Algoritmos de Rate Limiting: Token Bucket vs Leaky Bucket',
            'icon' => 'shield',
            'content' => 'Para proteger una API de abusos o ataques de denegación de servicio (DoS), se implementa **Rate Limiting**:

• **Token Bucket:** Cada usuario tiene un balde con tokens que se llena a una tasa constante (ej. 10 tokens por segundo). Cada petición consume 1 token. Permite picos de tráfico controlados hasta que el balde se vacía.
• **Sliding Window Counter:** Mantiene la cuenta exacta de peticiones en los últimos 60 segundos deslizando la ventana en Redis. Es el algoritmo más preciso contra ataques distribuidos.

Symfony provee el componente nativo `symfony/rate-limiter` que implementa Token Bucket con almacenamiento en Redis o APCu.',
            'takeaways' => 'Usa el componente RateLimiter de Symfony con Redis para proteger tus endpoints sensibles contra ataques de fuerza bruta.',
        ],
    ],
    'video' => [
        'title' => 'How Idempotency Keys Prevent Duplicate Payments',
        'speaker' => 'Piyush Mittal',
        'youtube_id' => 'yj2zwD5SBH8',
        'duration' => '15 min',
        'description' => 'Una explicación visual y práctica sobre cómo las claves de idempotencia previenen cobros dobles en pasarelas de pago ante fallos de red en arquitecturas distribuidas.',
        'key_takeaways' => [
            'Por qué los reintentos automáticos en redes móviles generan operaciones duplicadas.',
            'El rol de las claves de idempotencia UUID v4 en APIs financieras.',
            'Cómo cachear respuestas HTTP en Redis para entregar resultados idénticos.',
            'Manejo de estados de transacción en progreso vs completadas.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Api/Idempotency/IdempotencyKeyManager.php',
        'title' => 'Gestor de Claves de Idempotencia y Caché de Respuestas HTTP',
        'tag' => 'REST API Idempotency Manager',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Idempotency;

use InvalidArgumentException;

/**
 * Valida claves de idempotencia en formato canónico UUID v4 y estructura
 * las respuestas HTTP cacheadas para prevenir ejecuciones duplicadas.
 */
class IdempotencyKeyManager
{
    private const string UUID_V4_REGEX = \'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i\';

    /**
     * Valida si la clave cumple con el formato canónico UUID v4.
     */
    public function validateKey(string $key): bool
    {
        return preg_match(self::UUID_V4_REGEX, trim($key)) === 1;
    }

    /**
     * Construye el array de respuesta estructurada para almacenar en caché.
     *
     * @param int $statusCode Código de estado HTTP (debe estar entre 100 y 599)
     * @param array<string, string> $headers Cabeceras de respuesta HTTP
     * @param string $body Contenido del cuerpo de la respuesta
     * @return array{status_code: int, headers: array<string, string>, body: string, cached_at: int}
     */
    public function buildCachedResponse(int $statusCode, array $headers, string $body): array
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(
                \'Código de estado HTTP inválido: %d (rango permitido: 100 a 599).\',
                $statusCode
            ));
        }

        return [
            \'status_code\' => $statusCode,
            \'headers\' => $headers,
            \'body\' => $body,
            \'cached_at\' => time(),
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior confía en que \'el cliente no hará dos clics\' y procesa pagos a ciegas en cada petición POST. Un ingeniero Senior implementa claves de idempotencia en cabeceras HTTP, valida el formato UUID v4 con expresiones regulares estrictas, y almacena las respuestas cacheadas con marcas de tiempo para blindar el sistema contra duplicaciones financieras.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Endpoint POST sin protección contra doble cobro',
            'code' => '// Endpoint vulnerable a doble cobro
#[Route(\'/api/charge\', methods: [\'POST\'])]
public function charge(Request $request): JsonResponse {
    // Si la conexión se corta, el cliente reintenta y se cobra DOS VECES!
    $this->paymentService->charge($request->get(\'amount\'));
    return new JsonResponse([\'status\' => \'charged\']);
}',
            'flaws' => [
                'No exige ni valida claves de idempotencia en operaciones de mutación crítica.',
                'Provoca cobros duplicados catastróficos ante caídas de red o timeouts.',
                'No cachea las respuestas para reintentos transparentes.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: IdempotencyKeyManager con validación UUID v4 y cached_at',
            'code' => 'declare(strict_types=1);

namespace App\\Api\\Idempotency;

use InvalidArgumentException;

class IdempotencyKeyManager
{
    private const string REGEX = \'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i\';

    public function validateKey(string $key): bool
    {
        return preg_match(self::REGEX, trim($key)) === 1;
    }

    public function buildCachedResponse(int $code, array $headers, string $body): array
    {
        if ($code < 100 || $code > 599) throw new InvalidArgumentException(\'Invalid status code\');
        return [
            \'status_code\' => $code,
            \'headers\' => $headers,
            \'body\' => $body,
            \'cached_at\' => time(),
        ];
    }
}',
            'rationale' => [
                'Valida el formato canónico UUID v4 mediante preg_match() estricto.',
                'Valida el código HTTP de respuesta (rango 100-599) lanzando InvalidArgumentException.',
                'Incorpora la marca temporal cached_at para auditoría y TTLs en caché Redis.',
            ],
            'trade_offs' => [
                'Almacenar respuestas en caché requiere memoria RAM en Redis y un TTL adecuado (ej. 24 horas).',
                'El cliente debe ser responsable de generar UUIDs únicos consistentes para cada intención de pago.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Idempotency in Distributed Systems',
            'source' => 'IETF Draft: The Idempotency-Key HTTP Header Field',
            'quote' => 'La cabecera HTTP Idempotency-Key permite a los clientes reconocer de forma segura operaciones que pueden haberse ejecutado parcialmente o cuya respuesta se perdió en la red.',
            'author' => 'IETF HTTP Working Group',
            'explanation' => 'El borrador de estándar de la IETF que formaliza la cabecera Idempotency-Key en APIs REST.',
        ],
        [
            'topic' => 'Payment Idempotency Patterns',
            'source' => 'Stripe Engineering Blog',
            'quote' => 'Diseñamos nuestras APIs para ser idempotentes porque los fallos de red son una certeza matemática en internet. Sin claves de idempotencia, los dobles cobros son inevitables.',
            'author' => 'Stripe Engineering',
            'explanation' => 'La justificación arquitectónica de por qué las plataformas de pagos líderes exigen idempotencia.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'IETF Internet-Draft: The Idempotency-Key HTTP Header Field',
            'url' => 'https://datatracker.ietf.org/doc/draft-ietf-httpapi-idempotency-key-header/',
            'description' => 'Especificación del estándar de cabecera Idempotency-Key para APIs REST.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Symfony Docs: The RateLimiter Component',
            'url' => 'https://symfony.com/doc/current/rate_limiter.html',
            'description' => 'Guía oficial para limitar la tasa de peticiones en rutas y login en Symfony.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Stripe Docs: Idempotent Requests',
            'url' => 'https://docs.stripe.com/api/idempotent_requests',
            'description' => 'Documentación de Stripe sobre cómo utilizar claves de idempotencia para prevenir duplicados.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Gestor de Claves de Idempotencia y Respuestas Cacheadas',
        'objective' => 'Implementar la clase IdempotencyKeyManager con validateKey(string $key): bool validando UUID v4 con preg_match(), y buildCachedResponse(int $statusCode, array $headers, string $body): array validando $statusCode entre 100 y 599 e incluyendo \'cached_at\'.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'IdempotencyKeyManager\' en el namespace \'App\\Api\\Idempotency\'.
3. Implementa el método \'validateKey(string $key): bool\' validando formato UUID v4 mediante \'preg_match()\'.
4. Implementa el método \'buildCachedResponse(int $statusCode, array $headers, string $body): array\'.
5. Si \'$statusCode < 100 || $statusCode > 599\', lanza una \'InvalidArgumentException\'.
6. Retorna un array asociativo con \'status_code\', \'headers\', \'body\', y la marca de tiempo \'cached_at\' usando \'time()\'.',
        'filename' => 'src/Api/Idempotency/IdempotencyKeyManager.php',
        'guide' => [
            'explanation' => 'Usa una regex para UUID v4: \'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i\'. En buildCachedResponse, valida el código y retorna [\'status_code\' => $statusCode, \'headers\' => $headers, \'body\' => $body, \'cached_at\' => time()].',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase IdempotencyKeyManager y el método validateKey(string $key): bool con preg_match().',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe implementarse buildCachedResponse(int $statusCode, array $headers, string $body): array validando que el código esté entre 100 y 599 e incluyendo cached_at.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($statusCode < 100 || $statusCode > 599) throw new \\InvalidArgumentException(\'Código inválido\'); return [\'status_code\' => $statusCode, \'headers\' => $headers, \'body\' => $body, \'cached_at\' => time()];.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Idempotency;

use InvalidArgumentException;

class IdempotencyKeyManager
{
    public function validateKey(string $key): bool
    {
        // TODO: Valida formato canónico UUID v4 con preg_match
        return false;
    }

    public function buildCachedResponse(int $statusCode, array $headers, string $body): array
    {
        // TODO: Valida status 100-599 y construye array con cached_at
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Idempotency;

use InvalidArgumentException;

class IdempotencyKeyManager
{
    private const string UUID_V4_REGEX = \'/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i\';

    public function validateKey(string $key): bool
    {
        return preg_match(self::UUID_V4_REGEX, trim($key)) === 1;
    }

    public function buildCachedResponse(int $statusCode, array $headers, string $body): array
    {
        if ($statusCode < 100 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(\'Código de estado HTTP %d fuera de rango (100-599).\', $statusCode));
        }

        return [
            \'status_code\' => $statusCode,
            \'headers\' => $headers,
            \'body\' => $body,
            \'cached_at\' => time(),
        ];
    }
}
',
        'explanation' => 'IdempotencyKeyManager protege las operaciones contra ejecuciones duplicadas validando que las claves de idempotencia cumplan el formato canónico UUID v4 y estructurando respuestas cacheadas con la marca temporal cached_at para auditoría y reintentos transparentes.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Rate Limiting, Claves de Idempotencia & Prevención de Doble Cobro',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué las operaciones POST no son naturalmente idempotentes según la especificación HTTP?',
                'options' => [
                    'a' => 'Porque los servidores web las rechazan automáticamente después de 30 segundos.',
                    'b' => 'Porque cada petición POST representa una intención de crear o mutar un nuevo recurso subordinado, por lo que repetir la llamada crea múltiples recursos independientes a menos que se use una clave de idempotencia.',
                    'c' => 'Porque POST solo funciona con datos cifrados en base64.',
                    'd' => 'Porque el método POST no permite enviar cabeceras HTTP.',
                ],
                'correct' => 'b',
                'explanation' => 'A diferencia de PUT o DELETE (que son idempotentes por diseño), enviar un POST múltiple veces creará múltiples cobros o registros a menos que se coordine con una Idempotency-Key.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué estándar de identificador único se recomienda comúnmente para la cabecera \'Idempotency-Key\'?',
                'options' => [
                    'a' => 'Un número autoincremental de 1 a 100.',
                    'b' => 'UUID versión 4 (identificador universalmente único generado con aleatoriedad criptográfica).',
                    'c' => 'La dirección IP del cliente.',
                    'd' => 'El nombre de usuario de la cuenta.',
                ],
                'correct' => 'b',
                'explanation' => 'UUID v4 garantiza 122 bits de entropía aleatoria, haciendo que la probabilidad de colisión entre dos intenciones de pago diferentes sea prácticamente cero a nivel matemático.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué debe hacer un servidor API REST cuando recibe una petición con una Idempotency-Key que ya fue procesada exitosamente en las últimas 24 horas?',
                'options' => [
                    'a' => 'Lanzar un error 500 Internal Server Error.',
                    'b' => 'Devolver inmediatamente la respuesta previamente almacenada en caché sin volver a ejecutar la lógica de negocio ni realizar cobros.',
                    'c' => 'Borrar la cuenta del usuario por intento de fraude.',
                    'd' => 'Ignorar la cabecera y cobrar de nuevo.',
                ],
                'correct' => 'b',
                'explanation' => 'El propósito central de la idempotencia es entregar exactamente el mismo resultado que la primera ejecución exitosa sin repetir los efectos secundarios (como transferencias de dinero).',
            ],
        ],
    ],
];
