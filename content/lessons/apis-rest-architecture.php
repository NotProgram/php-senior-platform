<?php

declare(strict_types=1);

return [
    'slug' => 'apis-rest-architecture',
    'title' => 'Diseño de APIs REST Nivel Enterprise & Estándar RFC 7807 Problem Details',
    'module' => 'APIs RESTful',
    'minutes' => 50,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'El diseño de APIs REST profesionales se rige por contratos estrictos, hipermedia y respuestas de error predecibles. El estándar de la IETF RFC 7807 (Problem Details for HTTP APIs) establece una estructura universal para reportar errores de cliente y servidor bajo el MIME type \'application/problem+json\'.',
        'problem' => 'El uso de formatos de error propietarios y ad-hoc (\'{"success": false, "msg": "error"}\') obliga a los desarrolladores de frontend y aplicaciones móviles a escribir parsers incompatibles para cada endpoint, complicando el manejo de errores de validación.',
        'solution' => 'Implementar la clase \'ProblemDetailsFactory\' con el método \'createProblem\', validando que el código de estado HTTP pertenezca al rango de errores (400 a 599 lanzando InvalidArgumentException), asignando \'about:blank\' como tipo por defecto, y soportando el campo estructurado \'invalid_params\'.',
        'problem_label' => 'El Problema: Formatos de Error Propietarios y Status 200 Falsos',
        'solution_label' => 'La Solución Senior: Estándar RFC 7807 Problem Details y Fábrica Centralizada',
    ],
    'mental_model' => [
        'title' => 'El Pasaporte Estandarizado de Incidentes vs Las Notas Arbitrarias',
        'analogy' => 'En APIs descuidadas, cada endpoint inventa su propio formato de error: uno devuelve `{"error": "fallo"}`, otro `{"code": 102, "msg": "bad"}`, y peor aún, devuelven status HTTP 200 OK con `success: false` dentro del JSON. Los clientes móviles y aplicaciones frontend se vuelven locos intentando interpretar 20 formatos de error dispares. El estándar de la IETF **RFC 7807 (Problem Details for HTTP APIs)** es como un **Pasaporte Estandarizado de Incidentes**: establece un contrato universal JSON con cabecera `Content-Type: application/problem+json` que contiene exactamente 5 campos formales:

• `type`: URI que identifica el tipo formal de error (por defecto `\'about:blank\'`).
• `title`: Resumen corto y legible para humanos del incidente (ej. \'Validation Failed\').
• `status`: Código de estado HTTP exacto (rango de errores 400 a 599).
• `detail`: Explicación detallada contextual del fallo específico.
• `invalid_params`: Lista estructurada de parámetros que fallaron con su motivo.',
        'ascii_diagram' => 'ERROR PROPIETARIO CAÓTICO (Antipatrón):
HTTP/1.1 200 OK  <-- ¡Status 200 para un fallo!
{ "success": false, "err_msg": "Email inválido" }

ESTÁNDAR RFC 7807 PROBLEM DETAILS (Patrón Senior):
HTTP/1.1 422 Unprocessable Entity
Content-Type: application/problem+json
{
  "type": "about:blank",
  "title": "Validation Failed",
  "status": 422,
  "detail": "Los datos enviados en la petición contienen errores de validación.",
  "invalid_params": [
    { "name": "email", "reason": "Formato de correo electrónico no válido." }
  ]
}',
        'key_concept' => 'RFC 7807 unifica el tratamiento de errores en APIs REST. Todo error de cliente (4xx) o servidor (5xx) debe comunicarse con status HTTP semántico y una estructura Problem Details estandarizada.',
    ],
    'internals' => [
        'title' => 'Estructura Formal y Miembros de RFC 7807 / RFC 9457',
        'steps' => [
            [
                'phase' => '1. Cabecera Content-Type Específica',
                'description' => 'Las respuestas RFC 7807 deben emitirse con la cabecera \'Content-Type: application/problem+json\' (o problem+xml), indicando al cliente que el cuerpo contiene un problema estandarizado.',
            ],
            [
                'phase' => '2. Validación de Rango de Códigos de Estado (400 - 599)',
                'description' => 'Un Problem Detail solo tiene sentido para errores de cliente (400 a 499) o de servidor (500 a 599). Los códigos 2xx y 3xx deben ser rechazados de inmediato.',
            ],
            [
                'phase' => '3. El Miembro \'type\' y \'about:blank\'',
                'description' => 'La especificación dicta que cuando no exista una URI de documentación específica para el error, el campo \'type\' DEBE tener el valor canónico \'about:blank\'.',
            ],
            [
                'phase' => '4. Extensión \'invalid_params\' para Formularios y DTOs',
                'description' => 'Para errores de validación (HTTP 422), la especificación permite miembros de extensión como \'invalid_params\' conteniendo la lista de campos con error (\'name\' y \'reason\').',
            ],
            [
                'phase' => '5. Integración con Symfony Serializer & ExceptionListener',
                'description' => 'En Symfony, un ExceptionListener global intercepta DomainException o ValidationFailedException y utiliza ProblemDetailsFactory para retornar un JsonResponse estandarizado.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Antipatrón del Status 200 con Error en el Body',
            'icon' => 'alert-triangle',
            'content' => 'Uno de los pecados capitales en arquitectura REST es devolver `HTTP 200 OK` con un cuerpo que dice `{"error": "Usuario no autorizado"}`.

**Por qué es destructivo:**
1. **Cachés intermedias:** Servidores proxy (Varnish, Cloudflare) y navegadores asumirán que la respuesta fue exitosa y la almacenarán en caché para otros usuarios.
2. **Monitoreo ciego:** Herramientas de observabilidad (Datadog, New Relic) registrarán la petición como exitosa, ocultando que el 50% de tus usuarios están sufriendo fallos.
3. **Bibliotecas cliente:** Axios, Fetch y clientes HTTP están diseñados para disparar bloques catch automáticamente ante códigos 4xx y 5xx. Devolver 200 obliga al frontend a inspeccionar manualmente el body en cada llamada.',
            'takeaways' => 'Usa siempre códigos de estado HTTP semánticos (400, 401, 403, 404, 422, 500) y nunca devuelvas 200 para indicar un error.',
        ],
        [
            'title' => '400 Bad Request vs 422 Unprocessable Entity',
            'icon' => 'sliders',
            'content' => '¿Cuándo usar 400 y cuándo usar 422 en una API REST?

• **HTTP 400 Bad Request:** El cliente envió una sintaxis de petición mal formada que el servidor no puede ni parsear (ej. JSON con comas rotas, XML corrupto, o cabeceras ausentes).
• **HTTP 422 Unprocessable Entity:** La sintaxis es perfecta (el JSON es válido y se parsea sin error), pero los datos violan las reglas semánticas del negocio (ej. la contraseña tiene menos de 8 caracteres o el saldo es insuficiente).

Para validaciones de formularios y DTOs de Symfony Validator, **422 es el código semánticamente correcto**.',
            'takeaways' => 'Reserva 400 para fallos sintácticos de parsing de JSON y usa 422 para violaciones de reglas de negocio y validación de campos.',
        ],
    ],
    'video' => [
        'title' => 'Your REST API Errors Are Wrong. Problem Details Will Fix This',
        'speaker' => 'Milan Jovanović',
        'youtube_id' => 'eN4GX5WW87s',
        'duration' => '19 min',
        'description' => 'Una explicación profunda sobre por qué los formatos de error personalizados son un dolor de cabeza en APIs REST y cómo estandarizarlos usando RFC 7807 Problem Details.',
        'key_takeaways' => [
            'Los 5 miembros estándar de una respuesta RFC 7807 Problem Details.',
            'Por qué devolver errores en formatos propietarios dificulta la interoperabilidad.',
            'Cómo estructurar errores de validación mediante miembros de extensión como invalid_params.',
            'Implementación de middleware y controladores para automatizar respuestas Problem Details.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Api/Factory/ProblemDetailsFactory.php',
        'title' => 'Fábrica de Respuestas de Error Estandarizadas RFC 7807',
        'tag' => 'REST API RFC 7807 Factory',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Factory;

use InvalidArgumentException;

/**
 * Genera arrays estructurados conformes a la especificación IETF RFC 7807
 * (Problem Details for HTTP APIs) para respuestas de error de cliente y servidor.
 */
class ProblemDetailsFactory
{
    /**
     * @param int $statusCode Código de estado HTTP (debe estar en el rango 400 a 599)
     * @param string $title Resumen corto legible para humanos del error
     * @param string $detail Explicación contextual detallada del problema
     * @param string|null $type URI del tipo de problema (predeterminado \'about:blank\')
     * @param array<int, array{name: string, reason: string}> $invalidParams Lista de parámetros inválidos
     * @return array<string, mixed>
     */
    public function createProblem(
        int $statusCode,
        string $title,
        string $detail,
        ?string $type = null,
        array $invalidParams = []
    ): array {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(
                \'El código de estado HTTP %d no corresponde a un error (rango válido: 400 a 599).\',
                $statusCode
            ));
        }

        $problem = [
            \'type\' => $type ?? \'about:blank\',
            \'title\' => $title,
            \'status\' => $statusCode,
            \'detail\' => $detail,
        ];

        if (!empty($invalidParams)) {
            $problem[\'invalid_params\'] = $invalidParams;
        }

        return $problem;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior devuelve arrays asociativos inventados en el momento con strings aleatorios y códigos HTTP 200. Un ingeniero Senior adopta estándares internacionales de la IETF: centraliza la emisión de errores en un ProblemDetailsFactory conforme a RFC 7807, valida los rangos de estado HTTP, e incluye campos estructurados de validación para una integración limpia con clientes.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Formato de error ad-hoc con status HTTP arbitrario',
            'code' => '// Error desordenado sin estándar
return new JsonResponse([
    \'success\' => false,
    \'error_message\' => \'Email no válido\',
    \'timestamp\' => time()
], 200); // Peligro: Devuelve HTTP 200 para un fallo de validación!',
            'flaws' => [
                'Devuelve status 200 OK para un error, engañando a proxies y clientes HTTP.',
                'Formato propietario imposible de predecir por clientes genéricos.',
                'No incluye el tipo formal de error ni detalles contextuales estructurados.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: ProblemDetailsFactory formal bajo estándar RFC 7807',
            'code' => 'declare(strict_types=1);

namespace App\\Api\\Factory;

use InvalidArgumentException;

class ProblemDetailsFactory
{
    public function createProblem(
        int $status,
        string $title,
        string $detail,
        ?string $type = null,
        array $invalid = []
    ): array {
        if ($status < 400 || $status > 599) throw new InvalidArgumentException(\'Invalid status\');
        $res = [
            \'type\' => $type ?? \'about:blank\',
            \'title\' => $title,
            \'status\' => $status,
            \'detail\' => $detail,
        ];
        if (!empty($invalid)) $res[\'invalid_params\'] = $invalid;
        return $res;
    }
}',
            'rationale' => [
                'Cumple con el estándar de la IETF RFC 7807 (Problem Details).',
                'Valida que el código HTTP corresponda al rango estricto de errores (400-599).',
                'Soporta el miembro canónico \'about:blank\' e integra \'invalid_params\'.',
            ],
            'trade_offs' => [
                'Requiere configurar la cabecera Content-Type como \'application/problem+json\' en la respuesta JsonResponse.',
                'En errores internos de servidor (HTTP 500), se debe asegurar de no filtrar detalles técnicos sensibles como credenciales de BD en el campo \'detail\'.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'RFC 7807 Specification',
            'source' => 'IETF RFC 7807: Problem Details for HTTP APIs',
            'quote' => 'Este documento define un formato de detalle de problemas para transmitir detalles legibles por máquina de errores en respuestas HTTP para evitar la necesidad de definir nuevos formatos de error para las APIs.',
            'author' => 'Mark Nottingham et al. (IETF)',
            'explanation' => 'El estándar de oro de la industria para el reporte consistente de fallos en APIs web.',
        ],
        [
            'topic' => 'Semantics of HTTP Status Codes',
            'source' => 'RFC 9110: HTTP Semantics',
            'quote' => 'El código de estado de una respuesta es un número entero de tres dígitos que indica el resultado del intento del servidor de entender y satisfacer la solicitud del cliente.',
            'author' => 'Roy Fielding et al.',
            'explanation' => 'Los códigos de estado HTTP son la columna vertebral de la arquitectura REST y no deben distorsionarse.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'IETF RFC 7807: Problem Details for HTTP APIs',
            'url' => 'https://datatracker.ietf.org/doc/html/rfc7807',
            'description' => 'Especificación oficial de la IETF para el formato Problem Details.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'RFC 9457: Problem Details for HTTP APIs (Updated)',
            'url' => 'https://datatracker.ietf.org/doc/html/rfc9457',
            'description' => 'La evolución y refinamiento más reciente del estándar RFC 7807.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Martin Fowler: Richardson Maturity Model',
            'url' => 'https://martinfowler.com/articles/richardsonMaturityModel.html',
            'description' => 'Modelo de madurez para el diseño de APIs RESTful desde recursos hasta HATEOAS.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Fábrica de Errores RFC 7807 Problem Details',
        'objective' => 'Implementar la clase ProblemDetailsFactory con el método createProblem(int $statusCode, string $title, string $detail, ?string $type = null, array $invalidParams = []): array, validando que $statusCode esté entre 400 y 599, usando \'about:blank\' por defecto y soportando \'invalid_params\'.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'ProblemDetailsFactory\' en el namespace \'App\\Api\\Factory\'.
3. Implementa el método \'createProblem(int $statusCode, string $title, string $detail, ?string $type = null, array $invalidParams = []): array\'.
4. Valida si \'$statusCode < 400 || $statusCode > 599\'; si es así, lanza una \'InvalidArgumentException\'.
5. Si \'$type === null\', asígnale el valor canónico \'about:blank\'.
6. Si \'$invalidParams\' no está vacío, añade la clave \'invalid_params\' al array resultante y retórnalo.',
        'filename' => 'src/Api/Factory/ProblemDetailsFactory.php',
        'guide' => [
            'explanation' => 'En createProblem, valida if ($statusCode < 400 || $statusCode > 599) throw new \\InvalidArgumentException(\'...\');. Estructura el array con \'type\' => $type ?? \'about:blank\', \'title\' => $title, \'status\' => $statusCode, \'detail\' => $detail. Si !empty($invalidParams), agrega \'invalid_params\' => $invalidParams.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase ProblemDetailsFactory y el método createProblem.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe validarse que el código de estado HTTP esté en el rango de errores 400 a 599 lanzando InvalidArgumentException.',
            ],
            [
                'text' => '[Pista 3: Snippet] $problem = [\'type\' => $type ?? \'about:blank\', \'title\' => $title, \'status\' => $statusCode, \'detail\' => $detail]; if (!empty($invalidParams)) $problem[\'invalid_params\'] = $invalidParams; return $problem;.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Factory;

use InvalidArgumentException;

class ProblemDetailsFactory
{
    public function createProblem(
        int $statusCode,
        string $title,
        string $detail,
        ?string $type = null,
        array $invalidParams = []
    ): array {
        // TODO: Valida rango 400-599, usa \'about:blank\' por defecto y agrega \'invalid_params\'
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Api\\Factory;

use InvalidArgumentException;

class ProblemDetailsFactory
{
    /**
     * @param array<int, array{name: string, reason: string}> $invalidParams
     * @return array<string, mixed>
     */
    public function createProblem(
        int $statusCode,
        string $title,
        string $detail,
        ?string $type = null,
        array $invalidParams = []
    ): array {
        if ($statusCode < 400 || $statusCode > 599) {
            throw new InvalidArgumentException(sprintf(\'Código HTTP inválido: %d. Debe estar entre 400 y 599.\', $statusCode));
        }

        $problem = [
            \'type\' => $type ?? \'about:blank\',
            \'title\' => $title,
            \'status\' => $statusCode,
            \'detail\' => $detail,
        ];

        if (!empty($invalidParams)) {
            $problem[\'invalid_params\'] = $invalidParams;
        }

        return $problem;
    }
}
',
        'explanation' => 'ProblemDetailsFactory genera respuestas estandarizadas bajo la norma IETF RFC 7807, garantizando que los códigos de error HTTP pertenezcan al rango 4xx/5xx y formateando fallos de validación en la propiedad invalid_params.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Diseño de APIs REST Nivel Enterprise & Estándar RFC 7807 Problem Details',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la cabecera Content-Type estándar estipulada por la IETF para respuestas RFC 7807 Problem Details?',
                'options' => [
                    'a' => 'application/json',
                    'b' => 'application/problem+json',
                    'c' => 'text/plain',
                    'd' => 'application/x-www-form-urlencoded',
                ],
                'correct' => 'b',
                'explanation' => 'RFC 7807 define explícitamente el tipo de medio \'application/problem+json\' para permitir que los clientes HTTP reconozcan y procesen de forma automática un payload de error estandarizado.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué valor debe tener el miembro \'type\' según RFC 7807 si no existe una URI de documentación específica para el error?',
                'options' => [
                    'a' => 'null',
                    'b' => '"about:blank"',
                    'c' => '"unknown"',
                    'd' => '"https://localhost/error"',
                ],
                'correct' => 'b',
                'explanation' => 'La especificación estipula formalmente que cuando no se proporciona una URI que desglosé el error, el campo \'type\' debe tener el valor canónico \'about:blank\'.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por qué es una mala práctica arquitectónica devolver HTTP 200 OK con un cuerpo que indica un fallo de validación?',
                'options' => [
                    'a' => 'Porque corrompe los servidores de proxy/caché, oculta errores en herramientas de monitoreo (como Datadog) y rompe la gestión automática de errores en librerías cliente (como Axios).',
                    'b' => 'Porque las computadoras no pueden procesar números menores a 200.',
                    'c' => 'Porque el motor PHP se niega a serializar JSON en respuestas 200.',
                    'd' => 'Porque inhabilita el uso de certificados SSL.',
                ],
                'correct' => 'a',
                'explanation' => 'La semántica HTTP es la base de la web. Un código 200 indica éxito a nivel de transporte; devolver 200 para un fallo impide que la infraestructura de red y los clientes distingan peticiones exitosas de transacciones rechazadas.',
            ],
        ],
    ],
];
