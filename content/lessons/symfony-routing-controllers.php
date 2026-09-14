<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-routing-controllers',
    'title' => 'Routing, Argument Resolvers & Value Resolvers',
    'module' => 'Symfony Framework',
    'minutes' => 45,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'En Symfony moderno, los controladores deben mantenerse extraordinariamente delgados y desacoplados del objeto \'Request\'. Mediante \'ValueResolverInterface\', el framework intercepta los argumentos de la acción y extrae, valida y convierte cabeceras o cargas útiles en variables tipadas antes de que el controlador comience a ejecutarse.',
        'problem' => 'El código novato accede directamente a \'$request->headers->get(\'X-Api-Key\')\' o \'$request->getContent()\' dentro de cada acción de controlador, duplicando lógica de extracción de cabeceras y haciendo imposible reutilizar o testear la lógica de resolución sin mocks de peticiones HTTP pesadas.',
        'solution' => 'Implementar un Value Resolver personalizado conforme a \'ValueResolverInterface\' que extraiga la cabecera \'X-Api-Key\' cuando el parámetro del controlador se llame \'apiKey\', entregando el valor resuelto mediante un generador \'yield\'.',
        'problem_label' => 'El Problema: Controladores Gordos y Extracción Manual de Cabeceras',
        'solution_label' => 'La Solución Senior: ValueResolverInterface y Controladores Delgados',
    ],
    'mental_model' => [
        'title' => 'El Conserje Diplomático y el Protocolo de Entrega en Bandeja',
        'analogy' => 'Imagina la sala de operaciones de un cirujano de alto nivel (el Controlador). En un hospital descuidado, el cirujano tiene que salir al pasillo con guantes esterilizados, abrir la mochila del paciente (`$request->headers->get(\'X-Api-Key\')`), revisar si trajo los papeles del seguro, desinfectar los apósitos y recién entonces ponerse a operar. En un hospital de élite, el cirujano nunca toca mochilas ni papeles. En la puerta hay un **Conserje Diplomático Especializado (Value Resolver)**. Cuando el cirujano declara en su pizarra que necesita instrumental específico (`public function index(string $apiKey)`), el Value Resolver (`ValueResolverInterface`) intercepta la petición, verifica si el argumento se llama `apiKey`, extrae la cabecera `X-Api-Key`, valida que esté presente y se la entrega al cirujano en una bandeja de plata (`yield $apiKey`). Si la cabecera no existe o es inválida, el conserje deniega el acceso antes de que el cirujano pierda un solo segundo.',
        'ascii_diagram' => '[HTTP Request con Header \'X-Api-Key: secret123\']
                      │
                      ▼
            [HttpKernel Controller Arguments Pipeline]
                      │
                      ▼
        [ApiKeyHeaderValueResolver]
        1. Comprueba ArgumentMetadata: ¿El argumento es \'apiKey\'?
        2. Extrae $request->headers->get(\'X-Api-Key\')
        3. yield $apiKey;
                      │
                      ▼
     Controlador recibe el argumento limpio y tipado:
     public function index(string $apiKey): Response',
        'key_concept' => 'ValueResolverInterface desacopla tus controladores de los detalles del objeto Request, transformando cabeceras y payloads en argumentos tipados antes de la invocación de la acción.',
    ],
    'internals' => [
        'title' => 'El Pipeline de Resolución de Argumentos en HttpKernel',
        'steps' => [
            [
                'phase' => '1. Inspección de Metadatos con ArgumentMetadata',
                'description' => 'HttpKernel analiza la reflexión del controlador y genera objetos ArgumentMetadata con el nombre, tipo estricto y atributos asociados a cada parámetro.',
            ],
            [
                'phase' => '2. Cadena de Responsabilidad de Value Resolvers',
                'description' => 'Symfony recorre secuencialmente los resolvers registrados (RequestValueResolver, EntityValueResolver, MapRequestPayload, y resolvers personalizados).',
            ],
            [
                'phase' => '3. Coincidencia y Extracción de Datos',
                'description' => 'El resolver evalúa si puede manejar el argumento (por nombre $argument->getName() === \'apiKey\' o por tipo). Si coincide, extrae la cabecera \'X-Api-Key\' del Request.',
            ],
            [
                'phase' => '4. Emisión mediante Generadores (yield)',
                'description' => 'El método resolve() devuelve un iterable. Emplear \'yield $value\' suministra el valor exacto a la lista de argumentos finales del controlador.',
            ],
            [
                'phase' => '5. Invocación del Controlador Desacoplado',
                'description' => 'HttpKernel ejecuta el controlador pasando los argumentos ya resueltos, logrando que el método sea 100% independiente de $request.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Value Resolvers vs Middleware en APIs RESTful',
            'icon' => 'sliders',
            'content' => 'Muchos desarrolladores que vienen de Express o Laravel intentan resolver todo con \'Middlewares\' globales.

En Symfony, los **Value Resolvers** ofrecen una ventaja arquitectónica superior:
• Un middleware intercepta la petición completa a ciegas sin saber qué necesita el controlador específico.
• Un **Value Resolver** solo se activa si el controlador declara explícitamente el parámetro (ej. `string $apiKey` o `UserSession $session`). Si la ruta no necesita la API Key, el resolver no hace nada.
• Permite reutilizar lógica de autenticación o enriquecimiento de datos de forma granular y auto-documentada en las firmas de los métodos.',
            'takeaways' => 'Usa Value Resolvers para convertir datos de la petición en parámetros tipados de tus controladores de forma declarativa.',
        ],
        [
            'title' => 'Atributos Nativos #[MapRequestPayload] y #[MapQueryString]',
            'icon' => 'code',
            'content' => 'En Symfony 6.3 y 7+, los Value Resolvers vienen integrados con el Serializer y el Validator mediante atributos:

```php
#[Route(\'/api/users\', methods: [\'POST\'])]
public function create(
    #[MapRequestPayload]
    CreateUserDto $dto
): JsonResponse {
    // $dto ya está des-serializado y 100% validado con Assert!*
    return $this->json($dto, 201);
}
```

Esto elimina por completo el código repetitivo de `$serializer->deserialize()` y `$validator->validate()` dentro de los controladores.',
            'takeaways' => 'Adopta #[MapRequestPayload] para DTOs de entrada en APIs RESTful, eliminando parsing manual de JSON.',
        ],
    ],
    'video' => [
        'title' => 'A Cautionary Tale About GET Requests',
        'speaker' => 'HTTP & Controller Design Guide',
        'youtube_id' => 'A4ujMeiG_v8',
        'duration' => '16 min',
        'description' => 'Una exploración indispensable sobre el diseño seguro de peticiones HTTP, semántica de métodos REST y manejo limpio de controladores.',
        'key_takeaways' => [
            'Por qué los métodos GET deben ser estrictamente seguros e idempotentes.',
            'El peligro de mutar estado en la base de datos a través de peticiones GET.',
            'Cómo desacoplar la lógica del controlador de las cabeceras HTTP crudas.',
            'Diseño de endpoints REST profesionales siguiendo los estándares RFC.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/ValueResolver/ApiKeyHeaderValueResolver.php',
        'title' => 'Value Resolver para Extracción Automática de API Key',
        'tag' => 'Symfony 7 ValueResolverInterface',
        'code' => '<?php

declare(strict_types=1);

namespace App\\ValueResolver;

use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;
use Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;

/**
 * Extrae automáticamente la cabecera \'X-Api-Key\' cuando un controlador
 * declara un argumento llamado \'apiKey\'.
 */
class ApiKeyHeaderValueResolver implements ValueResolverInterface
{
    /**
     * @return iterable<string|null>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getName() !== \'apiKey\') {
            return [];
        }

        $apiKey = $request->headers->get(\'X-Api-Key\');

        yield $apiKey;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior llena sus controladores de llamadas imperativas a \'$request->headers->get("X-Api-Key")\' con condicionales manuales. Un ingeniero Senior implementa un ValueResolverInterface que extrae y tipifica los datos antes del controlador, manteniendo las acciones del framework limpias, testeables y con responsabilidades únicas.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Extracción manual de cabeceras en cada controlador',
            'code' => '// Controlador con extracción manual
class ApiController extends AbstractController {
    public function index(Request $request): Response {
        $key = $request->headers->get(\'X-Api-Key\'); // Acoplado a Request en cada acción!
        if (!$key) throw new BadRequestHttpException(\'No key\');
        return new Response("OK");
    }
}',
            'flaws' => [
                'Obliga a inyectar el objeto Request completo solo para leer una cabecera.',
                'Duplica la lógica de extracción de X-Api-Key en decenas de endpoints.',
                'Dificulta probar el controlador con tests unitarios simples sin mockear Request.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: ValueResolverInterface y firma de acción limpia',
            'code' => 'declare(strict_types=1);

namespace App\\ValueResolver;

use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;
use Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;

class ApiKeyHeaderValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getName() !== \'apiKey\') return [];
        yield $request->headers->get(\'X-Api-Key\');
    }
}

// Controlador limpio:
// public function index(?string $apiKey): Response { ... }',
            'rationale' => [
                'Implementa ValueResolverInterface para desacoplar el controlador del objeto Request.',
                'Permite que el controlador declare directamente el argumento tipado string $apiKey.',
                'Reutilizable en cualquier ruta o controlador del proyecto automáticamente.',
            ],
            'trade_offs' => [
                'Crear resolvers personalizados requiere entender la tubería de ArgumentMetadata de Symfony.',
                'Si los nombres de parámetros no coinciden exactamente, el resolver no se activará a menos que se use un atributo dedicado.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Thin Controllers Principle',
            'source' => 'Symfony Best Practices',
            'quote' => 'Mantén los controladores delgados. Un controlador solo debe recibir la petición, coordinar los servicios del dominio y devolver una respuesta.',
            'author' => 'Symfony Documentation',
            'explanation' => 'La lógica de parsing de headers y payloads no pertenece al cuerpo del controlador.',
        ],
        [
            'topic' => 'Value Resolvers in HttpKernel',
            'source' => 'Symfony RFC: Controller Argument Resolvers',
            'quote' => 'Los Value Resolvers permiten que las acciones del controlador expresen sus dependencias de entrada como argumentos de método tipados.',
            'author' => 'Symfony Core Team',
            'explanation' => 'Modernizó el manejo de argumentos eliminando la necesidad de acceder a superglobales o headers crudos.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Docs: Extending Action Argument Resolving',
            'url' => 'https://symfony.com/doc/current/controller/value_resolver.html',
            'description' => 'Guía oficial para crear Value Resolvers personalizados con ValueResolverInterface.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Docs: Mapping Request Data to Objects',
            'url' => 'https://symfony.com/doc/current/controller.html#mapping-request-data-to-an-object',
            'description' => 'Tutorial oficial sobre los atributos #[MapRequestPayload] y #[MapQueryString].',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Clean Code in Symfony: Controllers as Coordinators',
            'url' => 'https://stovepipe.systems/post/clean-symfony-controllers',
            'description' => 'Artículo canónico sobre cómo estructurar controladores limpios desacoplados de Request.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Value Resolvers para Extracción de Cabeceras HTTP',
        'objective' => 'Implementar ApiKeyHeaderValueResolver implementando ValueResolverInterface para extraer la cabecera \'X-Api-Key\' del Request cuando el argumento se llame \'apiKey\'.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'ApiKeyHeaderValueResolver\' en el namespace \'App\\ValueResolver\'.
3. Implementa \'Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface\'.
4. Implementa el método \'resolve(Request $request, ArgumentMetadata $argument): iterable\'.
5. Si \'$argument->getName() !== \'apiKey\'\', retorna un array vacío \'[]\'.
6. Extrae la cabecera \'X-Api-Key\' del objeto \'$request\' y emítela usando \'yield\'.',
        'filename' => 'src/ValueResolver/ApiKeyHeaderValueResolver.php',
        'guide' => [
            'explanation' => 'En resolve(), verifica if ($argument->getName() !== \'apiKey\') return [];. Luego extrae $apiKey = $request->headers->get(\'X-Api-Key\'); y ejecuta yield $apiKey;.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El resolver debe implementar Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe implementarse el método resolve(Request $request, ArgumentMetadata $argument): iterable comprobando si el argumento se llama apiKey.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($argument->getName() !== \'apiKey\') return []; yield $request->headers->get(\'X-Api-Key\');.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\ValueResolver;

use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;
use Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;

class ApiKeyHeaderValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        // TODO: Comprueba si el argumento es \'apiKey\' y emite la cabecera \'X-Api-Key\'
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\ValueResolver;

use Symfony\\Component\\HttpFoundation\\Request;
use Symfony\\Component\\HttpKernel\\Controller\\ValueResolverInterface;
use Symfony\\Component\\HttpKernel\\ControllerMetadata\\ArgumentMetadata;

class ApiKeyHeaderValueResolver implements ValueResolverInterface
{
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        if ($argument->getName() !== \'apiKey\') {
            return [];
        }

        $apiKey = $request->headers->get(\'X-Api-Key\');

        yield $apiKey;
    }
}
',
        'explanation' => 'ApiKeyHeaderValueResolver implementa ValueResolverInterface para interceptar la resolución del argumento \'apiKey\', abstrayendo la cabecera \'X-Api-Key\' y permitiendo controladores limpios y desacoplados.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Routing, Argument Resolvers & Value Resolvers',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la función primordial de \'ValueResolverInterface\' en Symfony?',
                'options' => [
                    'a' => 'Conectar la aplicación a una base de datos Redis.',
                    'b' => 'Examinar la petición HTTP y resolver el valor exacto de un parámetro del controlador antes de invocarlo.',
                    'c' => 'Compilar las plantillas Twig a código HTML plano.',
                    'd' => 'Autenticar contraseñas de usuario con hash bcrypt.',
                ],
                'correct' => 'b',
                'explanation' => 'ValueResolverInterface permite examinar el objeto Request y los metadatos de los parámetros del controlador para transformar cabeceras, rutas o cuerpos JSON en variables tipadas.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué debe retornar el método \'resolve()\' de un Value Resolver si no sabe o no debe resolver el argumento actual?',
                'options' => [
                    'a' => 'Lanzar una excepción fatal RuntimeException.',
                    'b' => 'Retornar un iterable vacío \'[]\' para permitir que el siguiente resolver de la cadena lo intente.',
                    'c' => 'Retornar null.',
                    'd' => 'Cerrar la conexión HTTP inmediatamente con un código 500.',
                ],
                'correct' => 'b',
                'explanation' => 'Los Value Resolvers operan en una Cadena de Responsabilidad. Retornar un array vacío o no hacer yield cede el turno al siguiente resolver registrado.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué ventaja aporta el atributo \'#[MapRequestPayload]\' sobre el manejo manual de peticiones en un controlador?',
                'options' => [
                    'a' => 'Reduce el tamaño de las imágenes cargadas por el usuario.',
                    'b' => 'Des-serializa automáticamente el JSON entrante a un DTO fuertemente tipado y ejecuta las validaciones de Symfony Validator de forma declarativa.',
                    'c' => 'Permite ejecutar el controlador sin necesidad de servidor web.',
                    'd' => 'Inhabilita la protección CSRF en formularios.',
                ],
                'correct' => 'b',
                'explanation' => '#[MapRequestPayload] automatiza la deserialización y validación del cuerpo de la petición, evitando escribir código repetitivo de validación en los controladores.',
            ],
        ],
    ],
];
