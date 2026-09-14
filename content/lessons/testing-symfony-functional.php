<?php

declare(strict_types=1);

return [
    'slug' => 'testing-symfony-functional',
    'title' => 'Symfony Testing con KernelTestCase & WebTestCase',
    'module' => 'testing',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En el desarrollo de aplicaciones enterprise con Symfony, las pruebas funcionales HTTP representan la maxima garantia de que la aplicacion responde adecuadamente a las solicitudes de los clientes. Mediante WebTestCase y su cliente emulado (HttpKernelBrowser), Symfony permite simular peticiones HTTP completas a traves del HttpKernel sin necesidad de iniciar un servidor web Nginx ni abrir sockets TCP de red.

El cliente de WebTestCase inyecta un objeto Symfony\\Component\\HttpFoundation\\Request directamente en HttpKernel::handle(), activando toda la cadena de eventos del framework: Routing, Security Firewall, Argument Resolvers, Controladores, Doctrine y serializadores JSON. Un desarrollador Senior construye harnesses y asertores reutilizables para validar codigos de estado HTTP, cabeceras seguras y esquemas JSON sin ensuciar los metodos de prueba con codigo repetitivo.',
        'problem' => 'En el desarrollo de aplicaciones enterprise con Symfony, las pruebas funcionales HTTP representan la maxima garantia de que la aplicacion responde adecuadamente a las solicitudes de los clientes. Mediante WebTestCase y su cliente emulado (HttpKernelBrowser), Symfony permite simular peticiones HTTP completas a traves del HttpKernel sin necesidad de iniciar un servidor web Nginx ni abrir sockets TCP de red.',
        'solution' => 'El cliente de WebTestCase inyecta un objeto Symfony\\Component\\HttpFoundation\\Request directamente en HttpKernel::handle(), activando toda la cadena de eventos del framework: Routing, Security Firewall, Argument Resolvers, Controladores, Doctrine y serializadores JSON. Un desarrollador Senior construye harnesses y asertores reutilizables para validar codigos de estado HTTP, cabeceras seguras y esquemas JSON sin ensuciar los metodos de prueba con codigo repetitivo.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Simulador de Vuelo de Alta Precision',
        'concept' => 'Para certificar a un piloto de aviacion comercial antes de volar un Boeing 787 con 300 pasajeros, las aerolineas utilizan un simulador de vuelo hidraulico de alta fidelidad:
1. La cabina cuenta con los instrumentos, controles y computadoras de a bordo 100% reales.
2. La computadora del simulador inyecta turbulencia, fallos de motor o tormentas directamente en los sensores.
3. No es necesario despegar de una pista real ni gastar toneladas de combustible de aviacion para validar como reacciona el piloto y el software de navegacion.

WebTestCase es exactamente el simulador de vuelo de Symfony: los controladores, voters de seguridad, rutas y servicios son 100% reales. Lo unico que se reemplaza es el cable fisico de red (la tarjeta de red y el socket FastCGI), inyectando la peticion directamente en el cerebro del framework en memoria.',
        'ascii_diagram' => 'FLUJO DE EJECUCION DE WEBTESTCASE (HttpKernelBrowser):

[ Peticion de Produccion (Lenta, red real) ]:
Browser ---> [ Socket TCP ] ---> [ Nginx / FPM ] ---> [ HttpKernel::handle() ] ---> Response

[ Peticion de WebTestCase (Memoria pura, ultra-rapida) ]:
TestCode ---> [ HttpKernelBrowser ] ------------------> [ HttpKernel::handle() ] ---> Response
                   |                                            |
                   v                                            v
           Simula Cookies, JWT                     Ejecuta Routing, Security,
           y Payload JSON                          Controllers y Eventos
',
        'analogy' => 'Para certificar a un piloto de aviacion comercial antes de volar un Boeing 787 con 300 pasajeros, las aerolineas utilizan un simulador de vuelo hidraulico de alta fidelidad:
1. La cabina cuenta con los instrumentos, controles y computadoras de a bordo 100% reales.
2. La computadora del simulador inyecta turbulencia, fallos de motor o tormentas directamente en los sensores.
3. No es necesario despegar de una pista real ni gastar toneladas de combustible de aviacion para validar como reacciona el piloto y el software de navegacion.

WebTestCase es exactamente el simulador de vuelo de Symfony: los controladores, voters de seguridad, rutas y servicios son 100% reales. Lo unico que se reemplaza es el cable fisico de red (la tarjeta de red y el socket FastCGI), inyectando la peticion directamente en el cerebro del framework en memoria.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'Cuando se invoca static::createClient(), Symfony arranca un kernel de pruebas en el entorno \'test\'.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'A diferencia del entorno de produccion, en \'test\' el contenedor de inyeccion de dependencias expone servicios privados para inspeccion directa mediante static::getContainer().',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Ademas, HttpKernelBrowser gestiona automaticamente una instancia de CookieJar y un historial de navegacion, lo que permite autenticar usuarios programaticamente sin pasar por el formulario de login mediante $client->loginUser($testUser) y verificar cabeceras con $client->getResponse()->headers.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'En APIs RESTful desacopladas, las pruebas funcionales deben validar no solo el codigo de estado HTTP 200 o 201, sino tambien la estructura del cuerpo de respuesta y las cabeceras de seguridad. Para endpoints protegidos, el cliente debe incluir la cabecera \'HTTP_AUTHORIZATION\' con el esquema \'Bearer &lt;token&gt;\'. Un asertor reutilizable garantiza que las respuestas cumplan con las claves JSON requeridas, lanzando excepciones inmediatas si falta algun atributo critico del payload.',
        ],
    ],
    'video' => [
        'title' => 'Symfony 5 Test Driven Development (TDD) Tutorial',
        'speaker' => 'Gary Clarke',
        'youtube_id' => 'TOa7JGbRwvk',
        'duration' => '26 min',
        'description' => 'Tutorial exhaustivo sobre pruebas funcionales en Symfony utilizando WebTestCase, simulacion de clientes HTTP, aserciones sobre respuestas JSON y aislamiento de base de datos.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Testing\\Harness;

use InvalidArgumentException;
use RuntimeException;

/**
 * Asertor y Constructor de Cabeceras para Pruebas Funcionales HTTP en APIs REST.
 */
final class ApiTestResponseAssertor
{
    /**
     * Valida el codigo HTTP y comprueba la presencia de claves en la respuesta JSON.
     *
     * @param list<string> $requiredKeys
     * @return array<string, mixed>
     */
    public function assertJsonResponse(
        int $expectedStatus,
        int $actualStatus,
        string $rawBody,
        array $requiredKeys = []
    ): array {
        if ($expectedStatus < 100 || $expectedStatus > 599 || $actualStatus < 100 || $actualStatus > 599) {
            throw new InvalidArgumentException(\'Los codigos de estado HTTP deben estar entre 100 y 599.\');
        }

        if ($expectedStatus !== $actualStatus) {
            throw new InvalidArgumentException(
                sprintf(\'El codigo HTTP esperado era %d, pero se recibio %d.\', $expectedStatus, $actualStatus)
            );
        }

        $cleanBody = trim($rawBody);
        if ($cleanBody === \'\') {
            throw new InvalidArgumentException(\'El cuerpo de la respuesta JSON no puede estar vacio.\');
        }

        /** @var array<string, mixed> $data */
        $data = json_decode($cleanBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new InvalidArgumentException(\'La respuesta HTTP no contiene un JSON valido: \' . json_last_error_msg());
        }

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RuntimeException(sprintf(\'La clave requerida "%s" no esta presente en la respuesta JSON.\', $key));
            }
        }

        return $data;
    }

    /**
     * Construye las cabeceras HTTP necesarias para peticiones autenticadas con JWT.
     *
     * @return array<string, string>
     */
    public function buildAuthenticatedHeaders(string $jwtToken): array
    {
        $token = trim($jwtToken);
        if ($token === \'\') {
            throw new InvalidArgumentException(\'El token JWT no puede estar vacio.\');
        }

        return [
            \'HTTP_AUTHORIZATION\' => \'Bearer \' . $token,
            \'CONTENT_TYPE\' => \'application/json\',
            \'HTTP_ACCEPT\' => \'application/json\',
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior enciende un servidor local y prueba los endpoints manualmente en Postman. El desarrollador Senior automatiza las pruebas funcionales con WebTestCase, validando codigos HTTP, contratos JSON y seguridad en CI en cada pull request, garantizando que ningun refactor rompa la integracion con los clientes moviles o frontend.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Prueba solo que el endpoint devuelva 200 OK sin verificar el contenido del JSON ni los encabezados.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Valida el status code, Content-Type, claves requeridas del JSON y los escenarios negativos (401 Unauthorized, 403 Forbidden, 422 Unprocessable).',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Pruebas Funcionales en Symfony',
            'source' => 'Symfony Testing Best Practices',
            'quote' => 'WebTestCase simula peticiones HTTP directamente contra el HttpKernel sin la sobrecarga de un servidor web, convirtiendolo en el metodo mas rapido y confiable para probar controladores.',
            'author' => 'Fabien Potencier',
            'explanation' => 'Permite pruebas completas de pila a velocidades cercanas a pruebas de integracion.',
        ],
        [
            'topic' => 'Validacion de Contratos de API',
            'source' => 'Building Microservices: Designing Fine-Grained Systems',
            'quote' => 'Tus pruebas funcionales deben validar los contratos publicos de la API para garantizar que las dependencias de los consumidores nunca se rompan silenciosamente.',
            'author' => 'Sam Newman',
            'explanation' => 'El esquema de respuesta es una promesa inquebrantable entre el backend y los clientes.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: Testing Controllers with WebTestCase',
            'url' => 'https://symfony.com/doc/current/testing.html#functional-tests',
            'type' => 'DOCS',
        ],
        [
            'title' => 'HttpKernelBrowser API Reference',
            'url' => 'https://symfony.com/doc/current/testing/http_kernel_browser.html',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Asertor de Respuestas de API para WebTestCase (ApiTestResponseAssertor)',
        'objective' => 'Implementar la clase ApiTestResponseAssertor para validar respuestas de endpoints RESTful en WebTestCase, verificando codigos de estado, decodificacion de payloads JSON, claves obligatorias y cabeceras de autorizacion Bearer.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Testing\\Harness.
2. Implementa la clase ApiTestResponseAssertor.
3. Implementa assertJsonResponse(int $expectedStatus, int $actualStatus, string $rawBody, array $requiredKeys = []): array.
4. Valida que los codigos esten entre 100 y 599 y que $expectedStatus coincida con $actualStatus; de lo contrario lanza InvalidArgumentException.
5. Valida que $rawBody sea un JSON valido asociativo; de lo contrario lanza InvalidArgumentException.
6. Comprueba que cada clave en $requiredKeys exista en el array resultante; de faltar alguna lanza RuntimeException.
7. Implementa buildAuthenticatedHeaders(string $jwtToken): array que retorne \'HTTP_AUTHORIZATION\' => \'Bearer \' . $jwtToken, \'CONTENT_TYPE\' => \'application/json\' y \'HTTP_ACCEPT\' => \'application/json\'. Si el token esta vacio, lanza InvalidArgumentException.',
        'filename' => 'src/Testing/Harness/ApiTestResponseAssertor.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que $expectedStatus y $actualStatus sean codigos HTTP validos (100-599).',
                'Paso 2: Compara si ambos codigos coinciden. Si difieren, lanza InvalidArgumentException.',
                'Paso 3: Decodifica $rawBody con json_decode(..., true) y verifica json_last_error().',
                'Paso 4: Itera sobre $requiredKeys y verifica array_key_exists. Si falta alguna, lanza RuntimeException.',
                'Paso 5: En buildAuthenticatedHeaders, retorna el array con HTTP_AUTHORIZATION conteniendo \'Bearer \'.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] En Symfony WebTestCase, las cabeceras HTTP de servidor en el array de peticion van prefijadas por HTTP_, como HTTP_AUTHORIZATION.',
            ],
            [
                'text' => '[Pista 2: Estructura] Si $expectedStatus !== $actualStatus lanza InvalidArgumentException. Si falta una clave requerida, lanza RuntimeException.',
            ],
            [
                'text' => '[Pista 3: Snippet] return [\'HTTP_AUTHORIZATION\' => \'Bearer \' . trim($jwtToken), \'CONTENT_TYPE\' => \'application/json\', \'HTTP_ACCEPT\' => \'application/json\'];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Testing\\Harness;

use InvalidArgumentException;
use RuntimeException;

class ApiTestResponseAssertor
{
    /**
     * @param list<string> $requiredKeys
     * @return array<string, mixed>
     */
    public function assertJsonResponse(
        int $expectedStatus,
        int $actualStatus,
        string $rawBody,
        array $requiredKeys = []
    ): array {
        // TODO: Validar codigos HTTP entre 100 y 599 (lanzar InvalidArgumentException si son invalidos o difieren)
        // TODO: Decodificar $rawBody y validar JSON (lanzar InvalidArgumentException si es invalido)
        // TODO: Validar presencia de cada clave en $requiredKeys (lanzar RuntimeException si alguna falta)
        // TODO: Retornar payload decodificado
        return [];
    }

    /**
     * @return array<string, string>
     */
    public function buildAuthenticatedHeaders(string $jwtToken): array
    {
        // TODO: Validar que token no sea vacio (lanzar InvalidArgumentException)
        // TODO: Retornar cabeceras con HTTP_AUTHORIZATION => \'Bearer \' . $jwtToken
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Testing\\Harness;

use InvalidArgumentException;
use RuntimeException;

class ApiTestResponseAssertor
{
    /**
     * @param list<string> $requiredKeys
     * @return array<string, mixed>
     */
    public function assertJsonResponse(
        int $expectedStatus,
        int $actualStatus,
        string $rawBody,
        array $requiredKeys = []
    ): array {
        if ($expectedStatus < 100 || $expectedStatus > 599 || $actualStatus < 100 || $actualStatus > 599) {
            throw new InvalidArgumentException(\'Los codigos de estado HTTP deben estar entre 100 y 599.\');
        }

        if ($expectedStatus !== $actualStatus) {
            throw new InvalidArgumentException(
                sprintf(\'El codigo HTTP esperado era %d, pero se recibio %d.\', $expectedStatus, $actualStatus)
            );
        }

        $cleanBody = trim($rawBody);
        if ($cleanBody === \'\') {
            throw new InvalidArgumentException(\'El cuerpo de la respuesta JSON no puede estar vacio.\');
        }

        /** @var array<string, mixed>|null $data */
        $data = json_decode($cleanBody, true);
        if (json_last_error() !== JSON_ERROR_NONE || !is_array($data)) {
            throw new InvalidArgumentException(\'La respuesta HTTP no contiene un JSON valido: \' . json_last_error_msg());
        }

        foreach ($requiredKeys as $key) {
            if (!array_key_exists($key, $data)) {
                throw new RuntimeException(sprintf(\'La clave requerida "%s" no esta presente en la respuesta JSON.\', $key));
            }
        }

        return $data;
    }

    /**
     * @return array<string, string>
     */
    public function buildAuthenticatedHeaders(string $jwtToken): array
    {
        $token = trim($jwtToken);
        if ($token === \'\') {
            throw new InvalidArgumentException(\'El token JWT no puede estar vacio.\');
        }

        return [
            \'HTTP_AUTHORIZATION\' => \'Bearer \' . $token,
            \'CONTENT_TYPE\' => \'application/json\',
            \'HTTP_ACCEPT\' => \'application/json\',
        ];
    }
}
',
        'explanation' => 'La clase ApiTestResponseAssertor proporciona aserciones consistentes y reutilizables para pruebas funcionales. Al centralizar la validacion de codigos de estado, decodificacion de JSON y construccion de cabeceras de autorizacion Bearer, reduce la duplicacion en las pruebas de WebTestCase y asegura un contrato estricto de API.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Symfony Testing con KernelTestCase & WebTestCase',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Como ejecuta WebTestCase una peticion HTTP a un controlador de Symfony?',
                'options' => [
                    'a' => 'Abre un proceso cURL externo que envia peticiones HTTP reales a un servidor web en localhost:8000.',
                    'b' => 'Inyecta un objeto Request directamente en HttpKernel::handle() en memoria, ejecutando toda la pila de Symfony sin sockets de red.',
                    'c' => 'Ejecuta un script bash que compila los controladores en binarios nativos.',
                    'd' => 'Realiza una conexion SSH al servidor remoto de pruebas.',
                ],
                'correct' => 'b',
                'explanation' => 'HttpKernelBrowser es un cliente emulado que se comunica directamente con la interfaz HttpKernelInterface en la memoria del proceso PHP, haciendolo ordenes de magnitud mas rapido que un servidor web real.',
            ],
            [
                'id' => 'q2',
                'question' => 'En el entorno de pruebas de Symfony (\'test\'), ¿que mecanismo permite autenticar a un usuario sin simular el envio de credenciales por un formulario?',
                'options' => [
                    'a' => '$client->loginUser($user)',
                    'b' => '$client->forceAdminAccess()',
                    'c' => 'Modificar directamente el archivo /etc/passwd del sistema operativo.',
                    'd' => 'Desactivar el firewall en config/packages/security.yaml de manera manual.',
                ],
                'correct' => 'a',
                'explanation' => 'El metodo $client->loginUser($user) crea el token de seguridad correspondiente y lo asocia a la sesion del cliente emulado, permitiendo probar endpoints protegidos de forma directa y rapida.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que en HttpKernelBrowser las cabeceras HTTP personalizadas de autorizacion deben suministrarse con el formato \'HTTP_AUTHORIZATION\'?',
                'options' => [
                    'a' => 'Porque sigue la convencion estandar de la variable superglobal $_SERVER en la especificacion CGI/FastCGI de PHP.',
                    'b' => 'Porque Symfony no soporta minusculas en ningun array asociativo.',
                    'c' => 'Porque es un requisito obligatorio del protocolo IPv6.',
                    'd' => 'Porque de lo contrario el recolector de basura de PHP elimina la cabecera antes del routing.',
                ],
                'correct' => 'a',
                'explanation' => 'En PHP, las cabeceras HTTP recibidas del servidor web se mapean en $_SERVER prefijadas con \'HTTP_\' y en mayusculas. HttpKernelBrowser emula fielmente este entorno para compatibilidad total.',
            ],
        ],
    ],
];
