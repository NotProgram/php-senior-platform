<?php

declare(strict_types=1);

return [
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
                'description' => 'El uso de atributos como #[Assert\\NotBlank], #[Assert\\Positive] o #[Assert\\Length] en las propiedades del DTO centraliza las reglas sintácticas y de negocio sin ensuciar la entidad de persistencia ni el controlador.',
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
        'code' => 'declare(strict_types=1);

namespace App\\Projects\\EnterpriseCrud;

use Symfony\\Component\\Validator\\Validator\\ValidatorInterface;
use Symfony\\Component\\Validator\\Constraints as Assert;
use InvalidArgumentException;

readonly class CreateProductDto
{
    public function __construct(
        #[Assert\\NotBlank(message: \'El SKU del producto es obligatorio.\')]
        public string $sku,

        #[Assert\\NotBlank(message: \'El nombre del producto es obligatorio.\')]
        #[Assert\\Length(min: 3, max: 120)]
        public string $name,

        #[Assert\\Positive(message: \'El precio debe ser un valor positivo.\')]
        public float $price,

        #[Assert\\PositiveOrZero(message: \'El inventario no puede ser negativo.\')]
        public int $stock = 0
    ) {}
}

readonly class ProductResponseDto
{
    public function __construct(
        public int $id,
        public string $sku,
        public string $name,
        public float $price,
        public int $stock,
        public string $createdAt
    ) {}
}

class ProductManagementService
{
    public function __construct(
        private readonly ValidatorInterface $validator
    ) {}

    /**
     * Valida el DTO y ejecuta la lógica de negocio sin acoplarse al transporte HTTP
     *
     * @return array{success: bool, product: ?ProductResponseDto, errors: list<string>}
     */
    public function createProduct(CreateProductDto $dto): array
    {
        $violations = $this->validator->validate($dto);
        if (count($violations) > 0) {
            $errors = [];
            foreach ($violations as $violation) {
                $errors[] = sprintf(\'%s: %s\', $violation->getPropertyPath(), $violation->getMessage());
            }

            return [\'success\' => false, \'product\' => null, \'errors\' => $errors];
        }

        // Simulación de persistencia de la entidad de dominio
        $response = new ProductResponseDto(
            id: mt_rand(100, 999),
            sku: strtoupper(trim($dto->sku)),
            name: trim($dto->name),
            price: $dto->price,
            stock: $dto->stock,
            createdAt: date(\'Y-m-d H:i:s\')
        );

        return [\'success\' => true, \'product\' => $response, \'errors\' => []];
    }
}',
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
            'trade_offs' => [
                'Requiere escribir clases DTO y mappers adicionales.',
            ],
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Implementación de EnterpriseDtoValidator',
        'objective' => 'Implementar la clase EnterpriseDtoValidator que inspeccione un objeto DTO mediante reflexión, valide que campos de texto no estén vacíos, que campos numéricos con nombres clave (price, amount, quantity) sean positivos (> 0), y que campos con "email" tengan formato de correo válido.',
        'instructions' => 'Completa EnterpriseDtoValidator: implementa validateDto(object $dto): array. Valida que el objeto no sea nulo. Inspecciona las propiedades públicas del DTO. Si una propiedad de tipo string está vacía tras trim(), añade error "$prop no puede estar vacío". Si una propiedad numérica cuyo nombre contenga "price", "amount" o "quantity" es <= 0, añade error "$prop debe ser positivo". Si una propiedad string contiene "email" en su nombre y no cumple filter_var($val, FILTER_VALIDATE_EMAIL), añade error "$prop tiene formato inválido". Si hay errores, retorna [is_valid => false, errors => $errors]; si no hay errores, retorna [is_valid => true, errors => []]. Implementa reset(): void.',
        'filename' => 'EnterpriseDtoValidator.php',
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Projects\\EnterpriseCrud;

use ReflectionClass;
use ReflectionProperty;

class EnterpriseDtoValidator
{
    /**
     * @return array{is_valid: bool, errors: list<string>}
     */
    public function validateDto(object $dto): array
    {
        // TODO: Inspeccionar propiedades públicas del DTO mediante ReflectionClass
        // TODO: Validar strings no vacíos
        // TODO: Validar valores numéricos positivos para propiedades de precio/monto/cantidad
        // TODO: Validar formato de email para propiedades con \'email\'
        // TODO: Retornar array estructurado con is_valid y errors
        return [
            \'is_valid\' => false,
            \'errors\' => [\'Validación no implementada.\'],
        ];
    }

    public function reset(): void
    {
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Projects\\EnterpriseCrud;

use ReflectionClass;
use ReflectionProperty;

class EnterpriseDtoValidator
{
    /**
     * @return array{is_valid: bool, errors: list<string>}
     */
    public function validateDto(object $dto): array
    {
        $reflection = new ReflectionClass($dto);
        $properties = $reflection->getProperties(ReflectionProperty::IS_PUBLIC);

        $errors = [];

        foreach ($properties as $property) {
            $name = $property->getName();
            $value = $property->getValue($dto);

            if (is_string($value)) {
                if (trim($value) === \'\') {
                    $errors[] = sprintf(\'El campo "%s" no puede estar vacío.\', $name);
                    continue;
                }

                if (str_contains(strtolower($name), \'email\') && !filter_var($value, FILTER_VALIDATE_EMAIL)) {
                    $errors[] = sprintf(\'El campo "%s" tiene un formato de correo electrónico inválido.\', $name);
                }
            } elseif (is_int($value) || is_float($value)) {
                $lowerName = strtolower($name);
                if ((str_contains($lowerName, \'price\') || str_contains($lowerName, \'amount\') || str_contains($lowerName, \'quantity\')) && $value <= 0) {
                    $errors[] = sprintf(\'El campo "%s" debe tener un valor numérico estrictamente positivo.\', $name);
                }
            }
        }

        return [
            \'is_valid\' => count($errors) === 0,
            \'errors\' => $errors,
        ];
    }

    public function reset(): void
    {
    }
}
',
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
];
