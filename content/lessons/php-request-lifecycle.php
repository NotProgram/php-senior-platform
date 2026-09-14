<?php

declare(strict_types=1);

return [
    'slug' => 'php-request-lifecycle',
    'title' => 'Ciclo de Vida de la Petición, Web Servers & SAPI Internals',
    'module' => 'PHP Moderno',
    'minutes' => 35,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'En su arquitectura clásica, PHP opera bajo el principio \'Share-Nothing\': cada petición web inicializa su propio entorno de memoria aislado (RINIT) y destruye por completo su estado al finalizar (RSHUTDOWN). Nginx se comunica con PHP-FPM mediante el protocolo binario FastCGI a través de sockets Unix o TCP.',
        'problem' => 'Muchos desarrolladores tratan a PHP como una \'caja negra\' que simplemente responde peticiones, sin entender la frontera entre el servidor web (Nginx/Apache), el gestor de procesos (PHP-FPM) y el Zend Engine. Esto conduce a configuraciones desastrosas de workers que agotan la RAM del servidor o a pánicos por errores como \'headers already sent\'.',
        'solution' => 'Dominar el flujo físico de la petición desde la trama TCP hasta el socket FastCGI, comprender el propósito de RINIT y RSHUTDOWN, dimensionar matemáticamente los procesos de PHP-FPM y controlar el Output Buffering para streaming de datos de alto rendimiento.',
        'problem_label' => 'El Problema: La Caja Negra de PHP y el Colapso por Swapping',
        'solution_label' => 'La Solución Senior: Frontera FastCGI, RINIT/RSHUTDOWN y Dimensionamiento Preciso',
    ],
    'mental_model' => [
        'title' => 'El Restaurante Pop-Up Efímero vs El Buffet Permanente',
        'analogy' => 'Imagina dos modelos de restaurantes. En Node.js o Go, el restaurante es un buffet abierto 24/7 con los mismos camareros siempre despiertos; si un camarero derrama café o deja una mesa sucia (fuga de memoria o estado global), esa suciedad permanece para el siguiente comensal. En PHP clásico (Share-Nothing), cada cliente que llega por la puerta recibe un restaurante pop-up completamente nuevo: mesas esterilizadas, vajilla impecable y un chef contratado exclusivamente para preparar su pedido (fase RINIT - Request Initialization). Una vez que el cliente termina y recibe su cuenta (HTTP Response), el restaurante completo es demolido, limpiado y desinfectado hasta el último gramo de memoria (fase RSHUTDOWN - Request Shutdown). Esta garantía física hace que PHP sea inmune a fugas de memoria persistentes entre usuarios, pero impone el costo de inicialización en cada ciclo.',
        'ascii_diagram' => '[Cliente HTTP] ──> [Nginx :80/443] (Reverse Proxy)
                         │
         FastCGI Binary  │  Unix Domain Socket (/run/php/php8.4-fpm.sock)
         Protocol Frames │  FCGI_BEGIN_REQUEST + FCGI_PARAMS + FCGI_STDIN
                         ▼
             [PHP-FPM Master Process]
                         │ Asigna worker libre del pool
                         ▼
             [Worker Process (PID: 1420)]
             ┌────────────────────────────────────────────────────────┐
             │ 1. RINIT (Request Init): Asigna zend_mm pool           │
             │ 2. Carga superglobales: $_SERVER, $_GET, $_POST        │
             │ 3. OpCache: Lee Opcodes precompilados de SHM           │
             │ 4. Zend VM ejecuta public/index.php                    │
             │ 5. Output Buffer acumula el body HTTP (4KB)            │
             │ 6. Emite Headers HTTP + FCGI_STDOUT                    │
             │ 7. RSHUTDOWN: zend_mm_shutdown() destruye objetos      │
             └────────────────────────────────────────────────────────┘
                         │
                         ▼ FCGI_END_REQUEST
                   [Nginx] ──> [Cliente HTTP 200 OK]',
        'key_concept' => 'La arquitectura Share-Nothing garantiza aislamiento absoluto entre peticiones. Ningún objeto o variable de usuario sobrevive entre peticiones consecutivas dentro del worker estándar de PHP-FPM.',
    ],
    'internals' => [
        'title' => 'Las 5 Fases del Ciclo de Vida FastCGI en Zend Engine',
        'steps' => [
            [
                'phase' => '1. Negociación HTTP y Multiplexado en Nginx',
                'description' => 'Nginx recibe la conexión TCP del cliente, realiza el apretón de manos TLS (HTTPS) y determina mediante la directiva \'location ~ \\.php$\' que debe enviar la petición a PHP-FPM a través de un Unix Domain Socket.',
            ],
            [
                'phase' => '2. Empaquetado Binario FastCGI (FCGI_BEGIN_REQUEST)',
                'description' => 'Nginx traduce la petición HTTP a tramas binarias FastCGI: FCGI_PARAMS serializa las cabeceras HTTP, método y rutas en pares clave-valor (que se convertirán en $_SERVER); FCGI_STDIN transporta el cuerpo de la petición.',
            ],
            [
                'phase' => '3. RINIT (Request Initialization)',
                'description' => 'El worker seleccionado de PHP-FPM inicializa su asignador de memoria zend_mm, crea las tablas de símbolos locales, rellena las variables superglobales y activa el Output Buffering predeterminado (4096 bytes).',
            ],
            [
                'phase' => '4. Ejecución del AST y Bytecode en Zend VM',
                'description' => 'La máquina virtual ejecuta los Opcodes precompilados desde la memoria compartida (OpCache). El código de Symfony despacha el kernel HTTP, ejecuta controladores y genera el objeto Response.',
            ],
            [
                'phase' => '5. RSHUTDOWN y Limpieza de Memoria',
                'description' => 'Se emiten las tramas FCGI_STDOUT y FCGI_END_REQUEST hacia Nginx. PHP ejecuta los destructores de objetos, libera toda la memoria asignada durante la petición con zend_mm_shutdown() y el worker queda listo para la siguiente petición.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Unix Domain Sockets vs TCP 127.0.0.1: Latencia Real',
            'icon' => 'network',
            'content' => 'Cuando Nginx y PHP-FPM residen en el mismo servidor físico o contenedor, existen dos formas de comunicarlos:

• **TCP Socket (127.0.0.1:9000):** La petición atraviesa la pila de red del kernel, calcula checksums TCP, realiza handshakes de conexión y compite por puertos efímeros.
• **Unix Domain Socket (/run/php/php8.4-fpm.sock):** Es un archivo especial gestionado directamente por el kernel del sistema operativo. La comunicación ocurre a través de buffers de memoria compartida en el kernel sin sobrecarga de red.

En pruebas de alto tráfico, usar Unix Domain Sockets reduce la latencia entre un 15% y un 25% y elimina el riesgo de agotar puertos efímeros bajo ataques o picos de tráfico.',
            'code' => '# Nginx: Configuración recomendada con Unix Socket
location ~ ^/index\\.php(/|$) {
    fastcgi_pass unix:/run/php/php8.4-fpm.sock;
    fastcgi_split_path_info ^(.+\\.php)(/.*)$;
    include fastcgi_params;
    fastcgi_param SCRIPT_FILENAME $realpath_root$fastcgi_script_name;
    fastcgi_param DOCUMENT_ROOT $realpath_root;
    internal;
}',
            'takeaways' => 'Usa siempre Unix Domain Sockets para comunicar Nginx y PHP-FPM en la misma máquina para maximizar el throughput y reducir la latencia.',
        ],
        [
            'title' => 'Fórmula Matemática de Capacidad para Workers PHP-FPM',
            'icon' => 'sliders',
            'content' => 'Uno de los errores más graves en producción es dejar \'pm = dynamic\' con valores por defecto. Si configuras demasiados workers, el servidor agota la memoria RAM física y comienza a usar Swap en disco, multiplicando la latencia por 1000 hasta congelarse.

**Fórmula Senior de Dimensionamiento:**
`pm.max_children = (RAM Total Servidor - RAM Reservada SO/BD) / Consumo Promedio por Worker PHP`

**Ejemplo Práctico:**
• Servidor con 8 GB de RAM total.
• SO, base de datos y servicios auxiliares reservan 2 GB.
• Memoria dedicada a PHP: 6 GB (6,144 MB).
• Medición en producción (`ps -ylC php-fpm8.4 --sort:rss`): un worker de Symfony consume ~45 MB.
• `pm.max_children = 6144 / 45 ≈ 136 workers`.
• Establecemos `pm.max_children = 115` para dejar un margen de seguridad del 15% ante picos.',
            'code' => '; /etc/php/8.4/fpm/pool.d/www.conf
pm = static
pm.max_children = 115
pm.max_requests = 1000
catch_workers_output = yes',
            'takeaways' => 'En servidores dedicados a PHP, usa \'pm = static\' para evitar el costo de crear y destruir procesos de sistema operativo bajo demanda.',
        ],
    ],
    'video' => [
        'title' => 'Packets, Protocols and PHP: Networking Fundamentals for Developers',
        'speaker' => 'Jessica Smith',
        'youtube_id' => 'Z-sYEwWdBrM',
        'duration' => '42 min',
        'description' => 'Una inmersión profunda en los protocolos de red, sockets y la frontera entre servidores web y el runtime de PHP.',
        'key_takeaways' => [
            'Cómo viajan las tramas binarias de FastCGI entre el proxy inverso y el runtime.',
            'La diferencia física de latencia entre un socket Unix y un socket de red TCP loopback.',
            'Por qué los headers HTTP deben transmitirse antes del primer byte del body.',
            'Estrategias para medir con precisión de microsegundos la duración de cada fase.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Infrastructure/Http/RequestInspector.php',
        'title' => 'Inspector de Entorno FastCGI y Telemetría de Petición en PHP 8.4',
        'tag' => 'PHP 8.4 FastCGI & Server Telemetry',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Http;

/**
 * Inspecciona el entorno FastCGI y calcula la duración de la petición
 * con precisión de microsegundos según estándares de alta concurrencia.
 */
class RequestInspector
{
    /**
     * @param array<string, mixed> $serverParams Variables de entorno, habitualmente $_SERVER
     */
    public function __construct(
        private readonly array $serverParams = []
    ) {}

    /**
     * Determina si la ejecución actual está respaldada por el protocolo FastCGI o SAPI web.
     */
    public function isFastCgi(): bool
    {
        $gatewayInterface = (string) ($this->serverParams[\'GATEWAY_INTERFACE\'] ?? \'\');
        $sapiName = \\PHP_SAPI;

        return str_starts_with($gatewayInterface, \'CGI/\')
            || in_array($sapiName, [\'fpm-fcgi\', \'cgi-fcgi\', \'cli-server\'], true);
    }

    /**
     * Calcula la duración transcurrida desde el timestamp inicial en microsegundos.
     * 1 segundo equivale a 1,000,000 microsegundos (µs).
     */
    public function getExecutionTimeMicroseconds(float $requestStartTime): float
    {
        $currentTime = microtime(true);

        return ($currentTime - $requestStartTime) * 1_000_000.0;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior culpa a PHP cuando la web responde lento, sin saber si la latencia proviene de la negociación TLS en Nginx, de la saturación de workers de FPM haciendo Swap, o de una consulta de base de datos. Un ingeniero Senior instrumenta telemetría en microsegundos desde RINIT, monitorea los procesos activos en status.html de PHP-FPM y dimensiona los recursos con fórmulas matemáticas comprobables.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Suposiciones a ciegas y emisión accidental de headers',
            'code' => '// Código desordenado sin tipado estricto
function checkServer() {
    echo "Iniciando verificación..."; // Dispara el Output Buffer prematuramente!
    header("Content-Type: application/json"); // Error: headers already sent!
    
    $start = time(); // Precisión de solo 1 segundo (inútil para microservicios)
    sleep(1);
    $diff = time() - $start;
    return ["time" => $diff];
}',
            'flaws' => [
                'Emite salida con echo antes de definir cabeceras HTTP, causando errores \'headers already sent\'.',
                'Usa time() con precisión de segundos, incapaz de medir latencias web reales de 10 a 50 ms.',
                'No valida el entorno SAPI ni encapsula dependencias en clases testeables.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Telemetría de microsegundos e inspección SAPI limpia',
            'code' => 'declare(strict_types=1);

namespace App\\Infrastructure\\Http;

class RequestInspector
{
    public function __construct(private readonly array $serverParams = []) {}

    public function isFastCgi(): bool
    {
        $gateway = (string) ($this->serverParams[\'GATEWAY_INTERFACE\'] ?? \'\');
        return str_starts_with($gateway, \'CGI/\') 
            || in_array(\\PHP_SAPI, [\'fpm-fcgi\', \'cgi-fcgi\', \'cli-server\'], true);
    }

    public function getExecutionTimeMicroseconds(float $requestStartTime): float
    {
        return (microtime(true) - $requestStartTime) * 1_000_000.0;
    }
}',
            'rationale' => [
                'Usa declare(strict_types=1) y tipado estricto en parámetros y valores de retorno.',
                'Emplea microtime(true) con precisión flotante multiplicada por 1,000,000 para obtener microsegundos.',
                'Inyecta las variables de servidor en el constructor, permitiendo tests unitarios sin acoplarse a globales.',
            ],
            'trade_offs' => [
                'La arquitectura Share-Nothing tiene un costo de CPU en el arranque que se mitiga completamente con OpCache y Preloading.',
                'Los Unix Sockets son más rápidos pero requieren que Nginx y PHP-FPM residan en el mismo host o compartan volúmenes en contenedores.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Share-Nothing Architecture',
            'source' => 'PHP Internals History',
            'quote' => 'PHP fue concebido bajo el principio de no compartir estado entre peticiones, eliminando carreras de hilos y garantizando un aislamiento total.',
            'author' => 'Rasmus Lerdorf',
            'explanation' => 'El aislamiento total significa que un fallo fatal en la petición del usuario A nunca afectará la memoria ni tumbará la petición del usuario B.',
        ],
        [
            'topic' => 'FastCGI Specification',
            'source' => 'OpenMarket FastCGI Protocol Specification',
            'quote' => 'FastCGI separa la lógica del servidor web del proceso que genera el contenido dinámico mediante un canal bidireccional multiplexado.',
            'author' => 'Mark R. Brown',
            'explanation' => 'Permite que servidores web ultrarrápidos como Nginx deleguen la computación pesada a pools de workers especializados.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP Manual Oficial: PHP-FPM Configuration',
            'url' => 'https://www.php.net/manual/es/install.fpm.configuration.php',
            'description' => 'Documentación oficial de todas las directivas de configuración de pools de PHP-FPM.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'FastCGI Specification v1.0',
            'url' => 'https://fastcgi-archives.github.io/FastCGI_Specification.html',
            'description' => 'Especificación formal del protocolo de tramas binarias entre servidores web y runtimes.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'PHP Internals Book: SAPI Architecture',
            'url' => 'https://www.phpinternalsbook.com/php7/internal_types/sapis.html',
            'description' => 'Explicación exhaustiva en C de cómo el Zend Engine se acopla a las distintas interfaces SAPI.',
            'type' => 'BOOK',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Configuración Segura de FastCGI & Headers',
        'objective' => 'Implementar la clase RequestInspector para verificar el entorno FastCGI y calcular la duración de la petición en microsegundos con tipado estricto en PHP 8.4.',
        'instructions' => '1. Declara estricto \'declare(strict_types=1);\'.
2. Define la clase \'RequestInspector\' en el namespace \'App\\Infrastructure\\Http\'.
3. Implementa el método \'isFastCgi(): bool\' que evalúe GATEWAY_INTERFACE y PHP_SAPI.
4. Implementa el método \'getExecutionTimeMicroseconds(float $requestStartTime): float\' utilizando \'microtime(true)\'.',
        'filename' => 'src/Infrastructure/Http/RequestInspector.php',
        'guide' => [
            'explanation' => 'Recuerda que 1 segundo equivale a 1,000,000 microsegundos. Al usar microtime(true) obtienes una marca de tiempo flotante en segundos. La diferencia entre el tiempo actual y el tiempo inicial multiplicada por 1,000,000 entrega la duración exacta en microsegundos.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El protocolo FastCGI se detecta inspeccionando las variables del entorno del servidor como GATEWAY_INTERFACE (suele comenzar con CGI/) y la constante nativa PHP_SAPI (fpm-fcgi, cgi-fcgi, cli-server).',
            ],
            [
                'text' => '[Pista 2: Estructura] Declara los métodos public function isFastCgi(): bool y public function getExecutionTimeMicroseconds(float $requestStartTime): float. Para la duración, resta el tiempo inicial al tiempo actual obtenido con microtime(true).',
            ],
            [
                'text' => '[Pista 3: Snippet] En getExecutionTimeMicroseconds: $currentTime = microtime(true); return ($currentTime - $requestStartTime) * 1_000_000.0;. Asegúrate de usar microtime(true) y tipado estricto.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Http;

class RequestInspector
{
    /**
     * @param array<string, mixed> $serverParams
     */
    public function __construct(
        private readonly array $serverParams = []
    ) {}

    public function isFastCgi(): bool
    {
        // TODO: Evalúa GATEWAY_INTERFACE y PHP_SAPI
        return false;
    }

    public function getExecutionTimeMicroseconds(float $requestStartTime): float
    {
        // TODO: Calcula la diferencia usando microtime(true) en microsegundos
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Http;

class RequestInspector
{
    /**
     * @param array<string, mixed> $serverParams
     */
    public function __construct(
        private readonly array $serverParams = []
    ) {}

    public function isFastCgi(): bool
    {
        $gateway = (string) ($this->serverParams[\'GATEWAY_INTERFACE\'] ?? \'\');
        $sapi = \\PHP_SAPI;

        return str_starts_with($gateway, \'CGI/\')
            || in_array($sapi, [\'fpm-fcgi\', \'cgi-fcgi\', \'cli-server\'], true);
    }

    public function getExecutionTimeMicroseconds(float $requestStartTime): float
    {
        $currentTime = microtime(true);

        return ($currentTime - $requestStartTime) * 1_000_000.0;
    }
}
',
        'explanation' => 'La clase encapsula la detección del entorno FastCGI y proporciona medición de alta precisión en microsegundos usando microtime(true), permitiendo auditar la latencia de cada fase del ciclo de vida sin depender de globales no testeadas.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Ciclo de Vida de la Petición, Web Servers & SAPI Internals',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un Unix Domain Socket es preferible a una conexión TCP 127.0.0.1 entre Nginx y PHP-FPM en un mismo servidor?',
                'options' => [
                    'a' => 'Porque los sockets Unix cifran automáticamente todo el tráfico con TLS 1.3.',
                    'b' => 'Porque evita el overhead de la pila TCP/IP, cálculo de checksums y la saturación de puertos efímeros.',
                    'c' => 'Porque PHP-FPM no soporta conexiones TCP en sistemas Linux modernos.',
                    'd' => 'Porque los sockets TCP consumen 1 GB de memoria RAM por cada conexión activa.',
                ],
                'correct' => 'b',
                'explanation' => 'Los Unix Domain Sockets operan como canales de memoria gestionados por el kernel, evitando el empaquetado de red TCP, los handshakes y la contención de puertos efímeros bajo alto tráfico.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué consecuencia inmediata tiene el principio \'Share-Nothing\' en los workers de PHP-FPM?',
                'options' => [
                    'a' => 'Los workers no pueden compartir la misma base de datos relacional.',
                    'b' => 'Toda la memoria de objetos y variables asignadas durante la petición se destruye en RSHUTDOWN, garantizando aislamiento total.',
                    'c' => 'La memoria compartida de OpCache debe borrarse en cada petición HTTP.',
                    'd' => 'Es imposible usar sesiones de usuario en PHP.',
                ],
                'correct' => 'b',
                'explanation' => 'En RSHUTDOWN, zend_mm_shutdown() destruye el árbol de memoria de la petición actual, aislando totalmente a los usuarios entre sí y previniendo fugas de memoria persistentes.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué ocurre si la suma de memoria de todos los workers definidos en pm.max_children supera la memoria física disponible?',
                'options' => [
                    'a' => 'PHP-FPM reduce dinámicamente el tamaño de los zvals.',
                    'b' => 'El kernel del sistema operativo comienza a hacer Swapping a disco, degradando el rendimiento hasta el colapso.',
                    'c' => 'Nginx cancela las peticiones inmediatamente con un código HTTP 404.',
                    'd' => 'OpCache duplica su memoria para compensar la falta de RAM.',
                ],
                'correct' => 'b',
                'explanation' => 'El swapping a disco sustituye la velocidad de la RAM (nanosegundos) por el acceso a disco (milisegundos), provocando que los workers se encolen y el servidor se congele por inanición de recursos.',
            ],
        ],
    ],
];
