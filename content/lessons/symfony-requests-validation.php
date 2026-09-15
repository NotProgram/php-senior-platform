<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-requests-validation',
    'title' => 'Peticiones HTTP, DTOs & Validación con MapRequestPayload',
    'module' => 'Symfony Framework',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'El componente HttpFoundation de Symfony sustituye las variables superglobales de PHP ($_GET, $_POST, $_SERVER) por una abstracción orientada a objetos segura: Symfony\Component\HttpFoundation\Request. En Symfony moderno, el atributo #[MapRequestPayload] combina el Serializer y el Validator del framework para deserializar automáticamente el cuerpo JSON de la petición en un DTO inmutable y validar sus campos mediante atributos nativos de validación.',
        'problem' => 'Los desarrolladores junior leen peticiones manualmente con json_decode(file_get_contents(\'php://input\'), true) dentro del controlador y validan con largas cadenas de if/else manuales. Esto produce código duplicado, omite tipos escalares y genera respuestas de error inconsistentes ante entradas maliciosas o incompletas.',
        'solution' => 'Modelar las entradas de cada endpoint mediante Data Transfer Objects (DTOs) inmutables provistos de atributos de Symfony Validator (#[Assert\NotBlank], #[Assert\Email], #[Assert\Length], #[Assert\Positive]). Al usar #[MapRequestPayload] en la firma de la acción, Symfony valida la petición automáticamente y devuelve un HTTP 422 Unprocessable Entity si los datos no cumplen las reglas.',
        'problem_label' => 'El Problema: json_decode Manual, Validaciones Espagueti y Errores Inconsistentes',
        'solution_label' => 'La Solución Senior: DTOs Tipados, #[MapRequestPayload] y Respuestas 422 Semánticas',
    ],
    'mental_model' => [
        'title' => 'La Aduana Postal y el Escáner de Rayos X Automatizado',
        'analogy' => 'Imagina una oficina aduanera de carga internacional.

En el enfoque artesanal (antipatrón), el agente abre cada paquete a mano con un cuchillo, examina a ojo si el contenido es legal y anota en un papel si faltan datos. Si el paquete viene dañado o con sustancias extrañas, el agente se confunde o colapsa el servicio.

Con **DTOs y #[MapRequestPayload]**, la aduana cuenta con un túnel de escáner automatizado de alta tecnología:
1. El paquete que entra por la red (JSON Request) debe coincidir con la forma exacta del contenedor autorizado (**el DTO**).
2. Los sensores láser infrarrojos (**Atributos #[Assert]**) escanean de inmediato: ¿Tiene formato de email válido? ¿La contraseña mide al menos 8 caracteres? ¿La edad es un número positivo?
3. Si cualquier regla falla, el escáner expulsa automáticamente el paquete hacia el remitente con un sello oficial de rechazo (**HTTP 422 Unprocessable Entity**) detallando con precisión quirúrgica cada campo inválido.
4. El agente de aduana (tu controlador) solo recibe paquetes 100% esterilizados, válidos y estrictamente tipados.',
        'ascii_diagram' => '         [Cliente HTTP envía POST /api/register con JSON Payload]
                                    │
                                    ▼
                         [Symfony HttpKernel]
                                    │
                                    ▼
                       [#[MapRequestPayload]]
                                    │ (Deserializa JSON a objeto DTO)
                                    ▼
                          [Symfony Validator]
                    (Evalúa #[Assert\NotBlank], #[Assert\Email], etc.)
                                    │
                    ┌───────────────┴───────────────┐
                    ▼                               ▼
               ¿Hay errores?                   ¿Datos 100% válidos?
                    │                               │
                    │ SÍ                            │ NO
                    ▼                               ▼
        [HTTP 422 Unprocessable Entity]   [Controlador recibe DTO tipado]
        (Retorna JSON con violaciones)    [Ejecuta servicio de registro]',
        'key_concept' => 'Tu controlador nunca debe validar datos crudos ni decodificar JSON a mano. Un DTO con #[MapRequestPayload] garantiza que solo datos válidos alcancen tu lógica de negocio.',
    ],
    'internals' => [
        'title' => 'Tubería de Deserialización y Validación con #[MapRequestPayload]',
        'steps' => [
            [
                'phase' => '1. Captura del Payload HTTP',
                'description' => 'HttpKernel recibe el Request y extrae el cuerpo en bruto ($request->getContent()) con formato JSON o datos de formulario.',
            ],
            [
                'phase' => '2. Deserialización mediante Symfony Serializer',
                'description' => '#[MapRequestPayload] invoca al componente Serializer para instanciar el DTO tipado e hidratar sus propiedades.',
            ],
            [
                'phase' => '3. Ejecución de Reglas de Validación (Constraints)',
                'description' => 'El componente Validator evalúa los atributos #[Assert] (NotBlank, Email, Length, Positive) recopilando cualquier violación detectada.',
            ],
            [
                'phase' => '4. Respuesta Automática HTTP 422',
                'description' => 'Si existen violaciones, se interrumpe el flujo y se envía una respuesta JSON 422 Unprocessable Entity sin ejecutar el controlador.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Objeto Request y sus ParameterBags',
            'icon' => 'globe',
            'content' => 'Symfony reemplaza las variables superglobales de PHP por bolsas orientadas a objetos seguras:
• **$request->query**: Parámetros de la URL ($_GET).
• **$request->request**: Datos enviados por formulario ($_POST).
• **$request->headers**: Cabeceras HTTP normalizadas.
• **$request->attributes**: Parámetros extraídos por el enrutador de Symfony.',
            'takeaways' => 'Nunca accedas a $_GET o $_POST directamente; usa siempre el objeto Request.',
        ],
        [
            'title' => 'Catálogo de Constraints de Validación (Symfony Validator)',
            'icon' => 'check-circle',
            'content' => 'Las restricciones declarativas eliminan cientos de líneas de código de validación manual:
```php
use Symfony\Component\Validator\Constraints as Assert;

final readonly class OrderInput
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $customerEmail,

        #[Assert\Positive]
        public int $quantity,

        #[Assert\Choice(choices: [\'EUR\', \'USD\'])]
        public string $currency
    ) {}
}
```',
            'takeaways' => 'Validar en el DTO garantiza que la lógica de dominio solo reciba datos matemáticamente y sintácticamente válidos.',
        ],
    ],
    'video' => [
        'title' => 'Symfony 7 - MapRequestPayload & Input Validation Made Easy',
        'speaker' => 'SymfonyCasts',
        'youtube_id' => 'kX9nL6J1K0Q',
        'duration' => '20 min',
        'description' => 'Aprende a validar peticiones JSON entrantes en Symfony 7 usando el atributo #[MapRequestPayload] y Data Transfer Objects fuertemente tipados.',
        'key_takeaways' => [
            'Cómo #[MapRequestPayload] deserializa y valida automáticamente antes del controlador.',
            'Uso de atributos #[Assert] para blindar las propiedades de los DTOs.',
            'Manejo estandarizado de respuestas 422 sin código boilerplate en controladores.',
            'Diferencia entre validar en el controlador y encapsular en DTOs.',
        ],
    ],
    'architecture_code' => [
        'title' => 'Endpoint con #[MapRequestPayload] y DTO de Registro Validado',
        'description' => 'Ejemplo completo de recepción de carga útil tipada con DTO inmutable y atributos de validación en Symfony 7.',
        'language' => 'php',
        'code' => '<?php

declare(strict_types=1);

namespace App\DTO;

use Symfony\Component\Validator\Constraints as Assert;

final readonly class UserRegistrationDto
{
    public function __construct(
        #[Assert\NotBlank(message: \'El email es obligatorio.\')]
        #[Assert\Email(message: \'El formato del email no es válido.\')]
        public string $email,

        #[Assert\NotBlank(message: \'La contraseña es obligatoria.\')]
        #[Assert\Length(min: 8, minMessage: \'La contraseña debe contener al menos 8 caracteres.\')]
        public string $plainPassword,

        #[Assert\Positive(message: \'La edad debe ser un número positivo.\')]
        public int $age
    ) {}
}

namespace App\Controller;

use App\DTO\UserRegistrationDto;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Attribute\MapRequestPayload;
use Symfony\Component\Routing\Attribute\Route;

final class RegistrationController extends AbstractController
{
    #[Route(\'/api/register\', name: \'api_register\', methods: [\'POST\'])]
    public function __invoke(#[MapRequestPayload] UserRegistrationDto $dto): JsonResponse
    {
        // Llegar aquí garantiza al 100% que $dto contiene datos válidos
        return $this->json([
            \'status\' => \'created\',
            \'email\' => $dto->email,
        ], Response::HTTP_CREATED);
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior nunca permite que datos no validados penetren en la capa de servicios o dominio. Diseña un DTO inmutable con reglas de validación explícitas en cada campo. Al delegar la deserialización a #[MapRequestPayload], confía en el framework para filtrar y rechazar basura con HTTP 422 antes de que el controlador comience.',
        'critical_questions' => [
            '¿Qué ocurre si el cliente envía un JSON con campos adicionales no definidos en el DTO?',
            '¿Estoy devolviendo 422 Unprocessable Entity en errores semánticos y 400 en errores sintácticos de JSON?',
            '¿Todas las propiedades del DTO son readonly para garantizar inmutabilidad?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Procesar un registro de usuario recibiendo email, password y edad vía JSON.',
        'junior' => [
            'approach' => 'Llama a json_decode($request->getContent(), true) y escribe múltiples if (!isset($data[\'email\'])) manuales dentro del controlador.',
            'flaws' => [
                'Duplica lógica de validación, mezcla responsabilidades en el controlador y responde con formatos de error inconsistentes.',
            ],
        ],
        'senior' => [
            'approach' => 'Crea un DTO readonly con atributos de validación #[Assert] y usa #[MapRequestPayload] en la acción del controlador.',
            'rationale' => [
                'El framework se encarga de la deserialización, validación y respuesta 422, garantizando código limpio y seguro.',
            ],
            'trade_offs' => [
                'Requiere crear una clase DTO adicional, pero previene errores de tipado e inyecciones de datos corruptos.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Data Transfer Objects y Validación',
            'source' => 'Patterns of Enterprise Application Architecture',
            'quote' => 'Un DTO es un objeto que transporta datos entre procesos para reducir el número de llamadas y aislar el dominio de los formatos de transporte.',
            'author' => 'Martin Fowler',
            'explanation' => 'Usar DTOs para la entrada HTTP evita que los cambios en la base de datos o en la API rompan las demás capas.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Documentación Oficial de Symfony: Validation',
            'url' => 'https://symfony.com/doc/current/validation.html',
            'description' => 'Guía completa sobre el componente Validator y catálogo de constraints en Symfony.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'DTO de Registro con Atributos de Validación',
        'objective' => 'Implementar la clase UserRegistrationDto en App\\DTO con propiedades readonly y atributos de validación, junto a un procesador UserRegistrationProcessor.',
        'instructions' => 'Crea el archivo UserRegistrationDto.php en App\\DTO con declare(strict_types=1);. Define la clase UserRegistrationDto con propiedades públicas promocionadas: public string $email, public string $plainPassword, public int $age. Anótalas con las constraints de Symfony\\Component\\Validator\\Constraints: email con #[Assert\\NotBlank] y #[Assert\\Email]; plainPassword con #[Assert\\NotBlank] y #[Assert\\Length(min: 8)]; age con #[Assert\\Positive]. Luego crea en App\\Service la clase UserRegistrationProcessor con el método process(UserRegistrationDto $dto): array que valide que la edad sea mayor o igual a 18 (si es menor, lanza InvalidArgumentException(\'Debes ser mayor de edad para registrarte.\')) y retorne [\'email\' => $dto->email, \'age\' => $dto->age, \'status\' => \'active\'].',
        'filename' => 'UserRegistrationDto.php',
        'guide' => [
            'explanation' => 'Aprenderás a modelar contratos de entrada seguros mediante DTOs y validar reglas de negocio sobre datos tipados.',
            'steps' => [
                'Paso 1: Declara declare(strict_types=1); y namespace App\\DTO;.',
                'Paso 2: Importa Symfony\\Component\\Validator\\Constraints as Assert.',
                'Paso 3: Declara class UserRegistrationDto con constructor promocionado readonly.',
                'Paso 4: Añade atributos #[Assert\\NotBlank] y #[Assert\\Email] a $email.',
                'Paso 5: Añade #[Assert\\NotBlank] y #[Assert\\Length(min: 8)] a $plainPassword.',
                'Paso 6: Añade #[Assert\\Positive] a $age.',
                'Paso 7: Declara class UserRegistrationProcessor en App\\Service con process(UserRegistrationDto $dto): array comprobando $dto->age >= 18.',
            ],
            'useful_functions' => [
                [
                    'name' => '#[Assert\Email]',
                    'desc' => 'Constraint de Symfony Validator que valida que la cadena tenga sintaxis de email válida.',
                ],
                [
                    'name' => '#[Assert\Length(min: 8)]',
                    'desc' => 'Constraint que valida la longitud mínima de caracteres de una cadena.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Importación de Constraints',
                'text' => 'Usa use Symfony\\Component\\Validator\\Constraints as Assert; para escribir atributos concisos.',
                'snippet' => '#[Assert\NotBlank] #[Assert\Email] public string $email',
            ],
            [
                'label' => 'Regla de Mayoría de Edad',
                'text' => 'En el procesador, verifica if ($dto->age < 18) lanzando InvalidArgumentException.',
                'snippet' => 'if ($dto->age < 18) { throw new \\InvalidArgumentException(\'Debes ser mayor de edad para registrarte.\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object para Registro de Usuario.
 */
class UserRegistrationDto
{
    public function __construct(
        // PASO 1: Añade #[Assert\NotBlank] y #[Assert\Email]
        public string $email,

        // PASO 2: Añade #[Assert\NotBlank] y #[Assert\Length(min: 8)]
        public string $plainPassword,

        // PASO 3: Añade #[Assert\Positive]
        public int $age
    ) {}
}

namespace App\Service;

use App\DTO\UserRegistrationDto;
use InvalidArgumentException;

/**
 * Procesador de Registro de Usuario.
 */
class UserRegistrationProcessor
{
    /**
     * Procesa los datos del DTO validado.
     */
    public function process(UserRegistrationDto $dto): array
    {
        // PASO 4: Valida que $dto->age >= 18 (lanza InvalidArgumentException si es menor)
        // PASO 5: Retorna [\'email\' => $dto->email, \'age\' => $dto->age, \'status\' => \'active\']
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\DTO;

use InvalidArgumentException;
use Symfony\Component\Validator\Constraints as Assert;

/**
 * Data Transfer Object para Registro de Usuario.
 */
class UserRegistrationDto
{
    public function __construct(
        #[Assert\NotBlank]
        #[Assert\Email]
        public string $email,

        #[Assert\NotBlank]
        #[Assert\Length(min: 8)]
        public string $plainPassword,

        #[Assert\Positive]
        public int $age
    ) {}
}

namespace App\Service;

use App\DTO\UserRegistrationDto;
use InvalidArgumentException;

/**
 * Procesador de Registro de Usuario.
 */
class UserRegistrationProcessor
{
    /**
     * @return array{email: string, age: int, status: string}
     */
    public function process(UserRegistrationDto $dto): array
    {
        if ($dto->age < 18) {
            throw new InvalidArgumentException(\'Debes ser mayor de edad para registrarte.\');
        }

        return [
            \'email\' => $dto->email,
            \'age\' => $dto->age,
            \'status\' => \'active\',
        ];
    }
}
',
        'explanation' => 'UserRegistrationDto y UserRegistrationProcessor ejemplifican el flujo moderno de Symfony: DTOs inmutables con constraints declarativas en la frontera HTTP y servicios que operan sobre tipos seguros.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Peticiones HTTP y Validación con MapRequestPayload',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué ocurre automáticamente cuando una petición falla las validaciones de un DTO anotado con #[MapRequestPayload]?',
                'options' => [
                    'a' => 'Symfony ignora los campos erróneos y continúa la ejecución del controlador con valores null.',
                    'b' => 'Symfony interrumpe la petición y devuelve automáticamente un código HTTP 422 Unprocessable Entity con la lista de violaciones.',
                    'c' => 'La base de datos bloquea la conexión por seguridad.',
                    'd' => 'Se genera un error 500 Internal Server Error con un volcado de memoria.',
                ],
                'correct' => 'b',
                'explanation' => '#[MapRequestPayload] captura las violaciones del validador y emite una respuesta HTTP 422 estandarizada sin llegar a invocar el controlador.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la diferencia semántica en HTTP entre un error 400 Bad Request y un error 422 Unprocessable Entity?',
                'options' => [
                    'a' => '400 indica error de sintaxis en el transporte (ej. JSON malformado); 422 indica sintaxis correcta pero violación de reglas semánticas de validación.',
                    'b' => '400 es para llamadas GET y 422 es exclusivamente para llamadas DELETE.',
                    'c' => '422 solo se utiliza si el servidor corre en modo debug.',
                    'd' => 'No hay diferencia; son códigos sinónimos en el estándar RFC.',
                ],
                'correct' => 'a',
                'explanation' => 'RFC 9110 define 400 para fallos de formato o sintaxis y 422 para instrucciones que no pueden procesarse debido a errores semánticos de validación.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por qué es preferible validar la entrada mediante DTOs en lugar de validar campos sueltos en el controlador?',
                'options' => [
                    'a' => 'Porque los DTOs se guardan automáticamente en la base de datos sin necesidad de Doctrine.',
                    'b' => 'Porque los DTOs encapsulan el contrato de transporte, tipan estrictamente los datos y permiten reutilizar las reglas de validación en múltiples capas.',
                    'c' => 'Porque Symfony no soporta controladores con más de 2 argumentos.',
                    'd' => 'Porque el motor Zend VM no compila controladores sin DTOs.',
                ],
                'correct' => 'b',
                'explanation' => 'Los DTOs proporcionan seguridad de tipos en tiempo de análisis estático, documentación viva de la API y desacoplamiento de la infraestructura.',
            ],
        ],
    ],
];
