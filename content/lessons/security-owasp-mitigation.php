<?php

declare(strict_types=1);

return [
    'slug' => 'security-owasp-mitigation',
    'title' => 'Mitigacion Activa de OWASP Top 10',
    'module' => 'security',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El OWASP Top 10 es el compendio estandar de la industria sobre los riesgos de seguridad mas criticos en aplicaciones web. A nivel Senior, la seguridad no se delega ciegamente en \'lo que haga el framework\', sino que se comprende la fisica del vector de ataque y se aplican defensas en profundidad.

Uno de los ataques mas destructivos en arquitecturas modernas de nube es la Falsificacion de Peticiones del Lado del Servidor (SSRF, Server-Side Request Forgery). Ocurre cuando una aplicacion web recibe una URL externa suministrada por el usuario (por ejemplo, para descargar una foto de perfil o consultar un webhook) y realiza una peticion HTTP saliente sin validacion adecuada. Un atacante puede suministrar http://169.254.169.254/latest/meta-data/ para extraer credenciales IAM de AWS, o http://127.0.0.1:6379 para interactuar directamente con un servidor Redis interno sin autenticacion.

En esta leccion implementamos un validador estricto de proteccion anti-SSRF que valida esquemas HTTP/HTTPS, resuelve la IP del host y bloquea rangos de red privados y reservados antes de que cURL emita un solo paquete.',
        'problem' => 'El OWASP Top 10 es el compendio estandar de la industria sobre los riesgos de seguridad mas criticos en aplicaciones web. A nivel Senior, la seguridad no se delega ciegamente en \'lo que haga el framework\', sino que se comprende la fisica del vector de ataque y se aplican defensas en profundidad.',
        'solution' => 'Uno de los ataques mas destructivos en arquitecturas modernas de nube es la Falsificacion de Peticiones del Lado del Servidor (SSRF, Server-Side Request Forgery). Ocurre cuando una aplicacion web recibe una URL externa suministrada por el usuario (por ejemplo, para descargar una foto de perfil o consultar un webhook) y realiza una peticion HTTP saliente sin validacion adecuada. Un atacante puede suministrar http://169.254.169.254/latest/meta-data/ para extraer credenciales IAM de AWS, o http://127.0.0.1:6379 para interactuar directamente con un servidor Redis interno sin autenticacion.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Recepcionista que Recibe una Carta Sospechosa',
        'concept' => 'Imagina a un recepcionista en la recepcion de una embajada que recibe una orden de un visitante:
1. LA VULNERABILIDAD SSRF: El visitante le dice: \'Por favor, entrega este sobre a la oficina en la direccion que escribi en la nota\'. El recepcionista ingenuo toma la nota sin leer y camina hacia el interior del edificio.
2. EL ATAQUE: En la nota, el visitante escribio: \'Boveda subterranea de codigos de seguridad, Piso -2, Puerta 127.0.0.1\'. El recepcionista, teniendo gafete oficial que le da acceso a todo el edificio, abre la boveda y entrega el paquete (la boveda queda expuesta).
3. LA DEFENSA ANTI-SSRF (El Protocolo de Seguridad): El recepcionista lee la direccion antes de dar un solo paso:
   - Verifica que la direccion empiece con correo postal externo legitimo (HTTP/HTTPS).
   - Consulta el plano catastral oficial para resolver la direccion fisica (gethostbyname).
   - Si la direccion apunta a una oficina interna de la embajada (127.0.0.1, 10.0.0.0/8, 192.168.0.0/16 o 169.254.0.0/16), activa la alarma de inmediato (RuntimeException) y destruye la carta.',
        'ascii_diagram' => 'VECTOR DE ATAQUE SSRF Y DEFENSA DE RESOLUCION PREVIA:

[ Atacante ] ---> POST /download-avatar?url=http://169.254.169.254/creds
                     |
                     v
+-------------------------------------------------------------+
| SsrfProtectionValidator::validateUrl($url)                  |
|                                                             |
| 1. filter_var($url, FILTER_VALIDATE_URL) -> Valido          |
| 2. parse_url($url, PHP_URL_SCHEME) -> Solo http / https     |
| 3. $host = parse_url($url, PHP_URL_HOST)                    |
| 4. $ip = gethostbyname($host)                               |
|                                                             |
| 5. Filtro de Bloqueo de Red:                                |
|    - ¿Es 127.0.0.1 o loopback? -> BLOQUEO INMEDIATO         |
|    - FILTER_FLAG_NO_PRIV_RANGE (10.0.0.0, 192.168.0.0, etc)|
|    - FILTER_FLAG_NO_RES_RANGE (169.254.0.0 cloud metadata)  |
|                                                             |
| -> Si la IP cae en rango privado: throw RuntimeException    |
+-------------------------------------------------------------+
                     |
               (Solo si es IP publica real de Internet)
                     v
   cURL / HttpClient descarga recurso de forma 100% segura
',
        'analogy' => 'Imagina a un recepcionista en la recepcion de una embajada que recibe una orden de un visitante:
1. LA VULNERABILIDAD SSRF: El visitante le dice: \'Por favor, entrega este sobre a la oficina en la direccion que escribi en la nota\'. El recepcionista ingenuo toma la nota sin leer y camina hacia el interior del edificio.
2. EL ATAQUE: En la nota, el visitante escribio: \'Boveda subterranea de codigos de seguridad, Piso -2, Puerta 127.0.0.1\'. El recepcionista, teniendo gafete oficial que le da acceso a todo el edificio, abre la boveda y entrega el paquete (la boveda queda expuesta).
3. LA DEFENSA ANTI-SSRF (El Protocolo de Seguridad): El recepcionista lee la direccion antes de dar un solo paso:
   - Verifica que la direccion empiece con correo postal externo legitimo (HTTP/HTTPS).
   - Consulta el plano catastral oficial para resolver la direccion fisica (gethostbyname).
   - Si la direccion apunta a una oficina interna de la embajada (127.0.0.1, 10.0.0.0/8, 192.168.0.0/16 o 169.254.0.0/16), activa la alarma de inmediato (RuntimeException) y destruye la carta.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En PHP 8.4, la resolucion de DNS con gethostbyname() es obligatoria para neutralizar tecnicas de DNS Rebinding y dominios enganosos como \'localhost.attacker.com\' o \'nip.io\'.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Al convertir el host a su representacion IPv4 antes de evaluar la peticion, se puede aplicar la constante nativa de PHP: filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE).',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Si la direccion es privada (RFC 1918) o reservada, la funcion retorna false, permitiendo arrojar una RuntimeException que detiene el ataque en seco.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Otros riesgos criticos del OWASP Top 10 que todo desarrollador Senior mitiga sistematicamente: 1. Inyeccion SQL: neutralizada al 100% mediante consultas parametrizadas con PDO o Doctrine DQL con setParameter(). 2. Cross-Site Scripting (XSS): mitigado mediante el auto-escaping contextual de Twig y cabeceras estrictas de Content-Security-Policy (CSP). 3. Cross-Site Request Forgery (CSRF): neutralizado con tokens criptograficos en formularios y la directiva SameSite=Lax en cookies de sesion.',
        ],
    ],
    'video' => [
        'title' => '7 Security risks you should never take as a developer',
        'speaker' => 'Fireship',
        'youtube_id' => '4YOpILi9Oxs',
        'duration' => '12 min',
        'description' => 'Fireship recorre de manera visual y directa los mayores vectores de ataque del OWASP Top 10: SSRF, Inyeccion SQL, robo de tokens de sesion y malas practicas de autenticacion en entornos de produccion.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Owasp;

use RuntimeException;
use InvalidArgumentException;

final class SsrfProtectionValidator
{
    /**
     * Valida y sanitiza una URL externa asegurando que no apunte a direcciones privadas o locales.
     */
    public function validateUrl(string $url): string
    {
        $cleanUrl = trim($url);

        if (!filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(\'La URL proporcionada no cumple con un formato valido.\');
        }

        $scheme = parse_url($cleanUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, [\'http\', \'https\'], true)) {
            throw new InvalidArgumentException(\'Solo se admiten esquemas HTTP y HTTPS.\');
        }

        $host = parse_url($cleanUrl, PHP_URL_HOST);
        if ($host === null || $host === \'\') {
            throw new InvalidArgumentException(\'La URL no contiene un host valido.\');
        }

        $ip = gethostbyname($host);

        // Bloqueo explicito de loopback y rangos privados / reservados
        if ($ip === \'127.0.0.1\' || str_starts_with($ip, \'127.\')) {
            throw new RuntimeException(\'Acceso bloqueado: la direccion de loopback local no esta permitida.\');
        }

        $isPublic = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isPublic === false) {
            throw new RuntimeException(sprintf(\'Acceso bloqueado: la direccion IP resolvida "%s" es privada o reservada.\', $ip));
        }

        return $cleanUrl;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior confia en que nadie suministrara una URL maliciosa o que la red privada de AWS es impenetrable. El desarrollador Senior aplica Zero Trust: valida todas las URLs salientes resolviendo la IP del host y bloqueando rangos locales y de metadata de nube, previniendo la exfiltracion catastrofica de credenciales.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Descarga archivos directamente con file_get_contents($_POST[\'url\']) sin ningun tipo de analisis previo.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Valida esquema, resuelve DNS y filtra rangos privados con filter_var() y flags estrictas antes de ejecutar peticiones HTTP.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Mitigacion de SSRF',
            'source' => 'OWASP Server-Side Request Forgery Prevention Cheat Sheet',
            'quote' => 'Nunca confies en nombres de dominio. Resuelve el host a una direccion IP y verifica que no pertenezca a rangos de loopback, RFC 1918 ni a las IPs de metadatos de proveedores de nube (169.254.169.254).',
            'author' => 'OWASP Security Team',
            'explanation' => 'La resolucion DNS previa es la unica defensa certera contra DNS Rebinding en SSRF.',
        ],
        [
            'topic' => 'Defensa en Profundidad',
            'source' => 'Security Engineering: A Guide to Building Dependable Distributed Systems',
            'quote' => 'La seguridad nunca debe depender de una sola barrera. Si el atacante supera el firewall de red, la aplicacion debe seguir defendiendo sus datos.',
            'author' => 'Ross Anderson',
            'explanation' => 'La validacion en el codigo fuente actua como escudo aun cuando la red permita el trafico.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'OWASP Top 10 Official Documentation',
            'url' => 'https://owasp.org/www-project-top-ten/',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PortSwigger Web Security Academy: SSRF Attacks',
            'url' => 'https://portswigger.net/web-security/ssrf',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Validador Defensivo Anti-SSRF (SsrfProtectionValidator)',
        'objective' => 'Implementar SsrfProtectionValidator con validateUrl(), verificando que cumpla FILTER_VALIDATE_URL, inspeccionando el esquema con parse_url / PHP_URL_SCHEME, resolviendo IP con gethostbyname, bloqueando \'127.0.0.1\' y filtrando con FILTER_FLAG_NO_PRIV_RANGE y FILTER_FLAG_NO_RES_RANGE lanzando RuntimeException ante intentos de intrusion.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Security\\Owasp.
2. Implementa la clase SsrfProtectionValidator.
3. Implementa function validateUrl(string $url): string.
4. Valida que $url cumpla FILTER_VALIDATE_URL (de lo contrario lanza \\InvalidArgumentException).
5. Inspecciona el esquema con parse_url($url, PHP_URL_SCHEME); si no es \'http\' ni \'https\', lanza \\InvalidArgumentException.
6. Extrae el host y resuelve la IP mediante gethostbyname($host).
7. Si la IP es \'127.0.0.1\', lanza \\RuntimeException.
8. Verifica con filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE); si retorna false, lanza \\RuntimeException.
9. Retorna la URL original sanitizada si todas las pruebas pasan.',
        'filename' => 'src/Security/Owasp/SsrfProtectionValidator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida el formato basico con filter_var($url, FILTER_VALIDATE_URL).',
                'Paso 2: Usa parse_url($url, PHP_URL_SCHEME) y comprueba in_array.',
                'Paso 3: Extrae el host con parse_url($url, PHP_URL_HOST).',
                'Paso 4: Resuelve con $ip = gethostbyname($host).',
                'Paso 5: Comprueba si $ip === \'127.0.0.1\' y lanza RuntimeException.',
                'Paso 6: Evalua filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) y lanza RuntimeException si es false.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] SSRF busca acceder a servicios internos o metadatos de nube resolviendo hosts locales o privados.',
            ],
            [
                'text' => '[Pista 2: Estructura] filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) retorna false para IPs como 10.x, 192.168.x y 169.254.x.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($ip === \'127.0.0.1\' || filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE) === false) { throw new \\RuntimeException(\'Blocked IP\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Owasp;

use InvalidArgumentException;
use RuntimeException;

class SsrfProtectionValidator
{
    public function validateUrl(string $url): string
    {
        // TODO: Validar con filter_var($url, FILTER_VALIDATE_URL)
        // TODO: Extraer esquema con parse_url y PHP_URL_SCHEME
        // TODO: Resolver IP con gethostbyname
        // TODO: Bloquear 127.0.0.1 y validar con FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE (lanzar RuntimeException)
        // TODO: Retornar URL validada
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Owasp;

use InvalidArgumentException;
use RuntimeException;

class SsrfProtectionValidator
{
    public function validateUrl(string $url): string
    {
        $cleanUrl = trim($url);

        if (!filter_var($cleanUrl, FILTER_VALIDATE_URL)) {
            throw new InvalidArgumentException(\'URL con formato invalido.\');
        }

        $scheme = parse_url($cleanUrl, PHP_URL_SCHEME);
        if (!in_array($scheme, [\'http\', \'https\'], true)) {
            throw new InvalidArgumentException(\'Esquema de URL no permitido.\');
        }

        $host = parse_url($cleanUrl, PHP_URL_HOST);
        if ($host === null || $host === \'\') {
            throw new InvalidArgumentException(\'Host no encontrado.\');
        }

        $ip = gethostbyname($host);

        if ($ip === \'127.0.0.1\') {
            throw new RuntimeException(\'Acceso local loopback bloqueado.\');
        }

        $isAllowed = filter_var(
            $ip,
            FILTER_VALIDATE_IP,
            FILTER_FLAG_NO_PRIV_RANGE | FILTER_FLAG_NO_RES_RANGE
        );

        if ($isAllowed === false) {
            throw new RuntimeException(sprintf(\'Direccion IP privada o reservada bloqueada: %s\', $ip));
        }

        return $cleanUrl;
    }
}
',
        'explanation' => 'La clase SsrfProtectionValidator mitiga ataques de Server-Side Request Forgery en la aplicacion. Al inspeccionar el esquema, resolver la IP y cotejar contra los rangos privados y reservados de Internet, previene que un atacante engañe al servidor para consultar la API de metadatos de AWS o redis local.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Mitigacion Activa de OWASP Top 10',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Que objetivo busca un atacante mediante una vulnerabilidad SSRF (Server-Side Request Forgery)?',
                'options' => [
                    'a' => 'Cambiar el tipo de letra CSS de la aplicacion en el navegador del cliente.',
                    'b' => 'Hacer que el servidor vulnerable realice peticiones HTTP en su nombre hacia servicios de red internos (como Redis o metadatos de AWS) inaccesibles desde Internet.',
                    'c' => 'Forzar la actualizacion de PHPUnit en el entorno de desarrollo local.',
                    'd' => 'Descargar la lista de ramas de Git del repositorio publico.',
                ],
                'correct' => 'b',
                'explanation' => 'El atacante utiliza al servidor web como un proxy para escanear y acceder a la red interna privada de la empresa o a la direccion 169.254.169.254 que expone secretos de nube.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por que validar unicamente que la URL comience con \'https://\' es insuficiente para evitar un ataque SSRF?',
                'options' => [
                    'a' => 'Porque HTTPS no admite cifrado TLS en conexiones de prueba.',
                    'b' => 'Porque un atacante puede registrar un dominio publico legitimo que resuelva a la IP privada 127.0.0.1 o 169.254.169.254, burlando la validacion superficial de strings.',
                    'c' => 'Porque PHP prohibe el uso de cURL con HTTPS.',
                    'd' => 'Porque el protocolo HTTP/2 no soporta cabeceras Host.',
                ],
                'correct' => 'b',
                'explanation' => 'El atacante puede apuntar un dominio como \'evil.com\' a 127.0.0.1 o a 10.0.0.5. Por ello es mandatorio resolver el DNS y verificar la direccion IP resultante.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cual constante de PHP combinada con FILTER_VALIDATE_IP permite rechazar direcciones IP de redes privadas (como 192.168.0.0/16)?',
                'options' => [
                    'a' => 'FILTER_FLAG_NO_PRIV_RANGE',
                    'b' => 'FILTER_FLAG_IPV6_ONLY',
                    'c' => 'FILTER_FLAG_BLOCK_ALL',
                    'd' => 'FILTER_SANITIZE_STRING',
                ],
                'correct' => 'a',
                'explanation' => 'FILTER_FLAG_NO_PRIV_RANGE hace que filter_var() retorne false para direcciones IPv4 en rangos privados RFC 1918 (10.0.0.0/8, 172.16.0.0/12, 192.168.0.0/16).',
            ],
        ],
    ],
];
