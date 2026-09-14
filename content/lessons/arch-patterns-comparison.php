<?php

declare(strict_types=1);

return [
    'slug' => 'arch-patterns-comparison',
    'title' => 'Monolito Modular, Capas, Hexagonal & Clean Architecture',
    'module' => 'architecture',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En la ingenieria de software enterprise, la arquitectura no se define por la distribucion de directorios en el disco, sino por la direccion de las dependencias y la solidez de las fronteras entre modulos. Durante decadas, la arquitectura tradicional en capas (UI -> Negocio -> Datos) domino el desarrollo web; sin embargo, su debilidad intrinseca es que acopla las reglas de negocio al esquema de base de datos relacional. Si la tabla cambia, la entidad cambia y el servicio se rompe.

La Arquitectura Hexagonal (Ports & Adapters, concebida por Alistair Cockburn) y Clean Architecture (Uncle Bob) invierten este paradigma mediante el Principio de Inversion de Dependencias (DIP): el dominio central se coloca en el centro y no conoce nada sobre la base de datos, el framework web o la terminal CLI. La comunicacion con el exterior se realiza exclusivamente mediante Puertos (interfaces). Por su parte, el Monolito Modular permite mantener estas fronteras estrictas dentro de un unico proceso ejecutable, evitando los enormes costos de latencia y consistencia eventual de los microservicios.',
        'problem' => 'En la ingenieria de software enterprise, la arquitectura no se define por la distribucion de directorios en el disco, sino por la direccion de las dependencias y la solidez de las fronteras entre modulos. Durante decadas, la arquitectura tradicional en capas (UI -> Negocio -> Datos) domino el desarrollo web; sin embargo, su debilidad intrinseca es que acopla las reglas de negocio al esquema de base de datos relacional. Si la tabla cambia, la entidad cambia y el servicio se rompe.',
        'solution' => 'La Arquitectura Hexagonal (Ports & Adapters, concebida por Alistair Cockburn) y Clean Architecture (Uncle Bob) invierten este paradigma mediante el Principio de Inversion de Dependencias (DIP): el dominio central se coloca en el centro y no conoce nada sobre la base de datos, el framework web o la terminal CLI. La comunicacion con el exterior se realiza exclusivamente mediante Puertos (interfaces). Por su parte, el Monolito Modular permite mantener estas fronteras estrictas dentro de un unico proceso ejecutable, evitando los enormes costos de latencia y consistencia eventual de los microservicios.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'Los Mamparos Estancos del Submarino Moderno',
        'concept' => 'Considera como se construye el casco de un submarino nuclear de alta profundidad:
1. Si el submarino fuera un espacio unico abierto (un monolito espagueti) y una valvula sufriera una fuga en la sala de maquinas, el agua inundaria toda la nave en minutos y el submarino se hundiria sin remedio.
2. En su lugar, el submarino se divide mediante mamparos estancos de acero reforzado (Puertos y Limites de Modulo). Cada compartimento (Navegacion, Propulsion, Soporte Vital, Comunicaciones) opera con independencia.
3. Si un torpedo o una roca rompe el compartimento de torpedos, las compuertas estancas se sellan automaticamente. El compartimento inundado queda aislado, pero el reactor nuclear y el sistema de oxigeno siguen funcionando intactos.

Un Monolito Modular con arquitectura de puertos y adaptadores es ese submarino: si el modulo de pasarela de pagos sufre un error de red o requiere cambiar de Stripe a Adyen, ningun otro modulo del sistema se entera ni colapsa.',
        'ascii_diagram' => 'COMPARACION ARQUITECTONICA: CAPAS VS HEXAGONAL:

ARQUITECTURA TRADICIONAL EN CAPAS (Acoplada a DB):
+-------------------------------------------------------------+
| Capa de Presentacion (Controladores Symfony / Twig)         |
+-------------------------------------------------------------+
                              | depende de
                              v
+-------------------------------------------------------------+
| Capa de Logica de Negocio (Servicios de Dominio)            |
+-------------------------------------------------------------+
                              | depende de
                              v
+-------------------------------------------------------------+
| Capa de Acceso a Datos (Doctrine ORM / Tablas MySQL)        |
+-------------------------------------------------------------+

ARQUITECTURA HEXAGONAL (Inversion de Dependencias - Regla de Oro):
+-------------------------------------------------------------+
| INFRAESTRUCTURA (Adaptadores Primarios y Secundarios)        |
|  [ Web Controller ]    [ CLI Command ]    [ MySQL PDO ]      |
|           |                   |                  ^          |
|           v                   v                  |          |
|      +-----------------------------+             |          |
|      | APLICACION (Casos de Uso)   |             |          |
|      | [ RegisterStudentUseCase ]  |             |          |
|      +-----------------------------+             |          |
|                    |                             |          |
|                    v                             |          |
|      +-----------------------------+             |          |
|      | DOMINIO (Entidades, Reglas) |             |          |
|      | <<interface>>               |-------------+          |
|      | StudentRepositoryPort       | (Inversion de control) |
|      +-----------------------------+                        |
+-------------------------------------------------------------+
',
        'analogy' => 'Considera como se construye el casco de un submarino nuclear de alta profundidad:
1. Si el submarino fuera un espacio unico abierto (un monolito espagueti) y una valvula sufriera una fuga en la sala de maquinas, el agua inundaria toda la nave en minutos y el submarino se hundiria sin remedio.
2. En su lugar, el submarino se divide mediante mamparos estancos de acero reforzado (Puertos y Limites de Modulo). Cada compartimento (Navegacion, Propulsion, Soporte Vital, Comunicaciones) opera con independencia.
3. Si un torpedo o una roca rompe el compartimento de torpedos, las compuertas estancas se sellan automaticamente. El compartimento inundado queda aislado, pero el reactor nuclear y el sistema de oxigeno siguen funcionando intactos.

Un Monolito Modular con arquitectura de puertos y adaptadores es ese submarino: si el modulo de pasarela de pagos sufre un error de red o requiere cambiar de Stripe a Adyen, ningun otro modulo del sistema se entera ni colapsa.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En PHP 8.4, la implementacion de puertos y adaptadores se apoya en interfaces estrictas y autowiring.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'El caso de uso (RegisterStudentUseCase) recibe por su constructor una interfaz abstracta (StudentRepositoryPort), sin saber si en tiempo de ejecucion esta respaldada por DoctrineStudentRepository, MongoStudentRepository o InMemoryStudentRepository.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Symfony resuelve la implementacion en el contenedor mediante el atributo #[AsAlias] o #[Autowire(service: ...)].',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => 'Esto permite ejecutar la prueba del caso de uso en 0.5 milisegundos con un repositorio Fake en memoria sin tocar la base de datos.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'El error comun en la Arquitectura Limpia es sobre-abstraer sistemas simples creando 6 capas de DTOs, mappers y puertos para un simple CRUD administrativo. Un desarrollador Senior evalua la complejidad del dominio: si el modulo posee logica de negocio rica e invariantes criticas (como evaluacion de creditos o liquidacion de sueldos), la inversion de dependencias protege la empresa; si es un simple formulario de contacto, una arquitectura en capas o un controlador delgado con Form y Doctrine es la decision mas pragmatica y economica.',
        ],
    ],
    'video' => [
        'title' => 'Modular Monoliths',
        'speaker' => 'Simon Brown (Creador del Modelo C4)',
        'youtube_id' => '5OjqD-ow8GE',
        'duration' => '48 min',
        'description' => 'Simon Brown expone en GOTO Conference por que la mayoria de los equipos que migran a microservicios cometen un grave error de diseno y como disenar monolitos modulares limpios con fronteras de paquetes bien definidas.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Academy\\Application;

use App\\Academy\\Domain\\Student;
use App\\Academy\\Domain\\Port\\StudentRepositoryPort;
use InvalidArgumentException;
use RuntimeException;

final readonly class RegisterStudentUseCase
{
    public function __construct(
        private StudentRepositoryPort $studentRepository
    ) {}

    public function execute(string $email, string $fullName): Student
    {
        $cleanEmail = trim(strtolower($email));
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\'El formato del email proporcionado es invalido.\');
        }

        $cleanName = trim($fullName);
        if (strlen($cleanName) < 3) {
            throw new InvalidArgumentException(\'El nombre debe tener al menos 3 caracteres.\');
        }

        if ($this->studentRepository->existsByEmail($cleanEmail)) {
            throw new RuntimeException(sprintf(\'El estudiante con email "%s" ya se encuentra registrado.\', $cleanEmail));
        }

        $student = new Student(
            email: $cleanEmail,
            fullName: $cleanName,
            registeredAt: new \\DateTimeImmutable()
        );

        $this->studentRepository->save($student);

        return $student;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior cree que la arquitectura es un conjunto inmutable de dogmas que debe aplicarse por igual a todo el proyecto. El desarrollador Senior comprende que la arquitectura es un balance de compensaciones (trade-offs): adopta Monolito Modular para mantener alta velocidad de desarrollo, aplica Hexagonal en los modulos de mayor riesgo de negocio y mantiene controladores directos para operaciones transaccionales triviales.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Crea 10 clases, interfaces y mappers para consultar un listado de paises estaticos en base de datos.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Aplica puertos y adaptadores unicamente donde existe riesgo de volatilidad tecnologica o logica de negocio de alto valor.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'La Regla de Dependencia',
            'source' => 'Clean Architecture: A Craftsman\'s Guide to Software Structure and Design',
            'quote' => 'Las dependencias en el codigo fuente solo deben apuntar hacia adentro, en direccion a las politicas de nivel superior. Nada en un circulo interno puede saber absolutamente nada sobre algo en un circulo externo.',
            'author' => 'Robert C. Martin (Uncle Bob)',
            'explanation' => 'El nucleo de dominio nunca debe importar clases de frameworks, drivers o interfaces de usuario.',
        ],
        [
            'topic' => 'Monolito Modular',
            'source' => 'Software Architecture for Developers',
            'quote' => 'Si no puedes construir un monolito con componentes bien estructurados y fronteras limpias, ¿que te hace pensar que los microservicios seran la respuesta?',
            'author' => 'Simon Brown',
            'explanation' => 'La modularidad es una propiedad logica interna, no fisica de despliegue.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Alistair Cockburn: Hexagonal Architecture Explained',
            'url' => 'https://alistair.cockburn.us/hexagonal-architecture/',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'C4 Model: Visualising Software Architecture',
            'url' => 'https://c4model.com/',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Implementacion de Puerto y Caso de Uso (RegisterStudentUseCase)',
        'objective' => 'Definir el puerto StudentRepositoryPort e implementar el caso de uso RegisterStudentUseCase inyectando el puerto, verificando duplicados y persistiendo la entidad de dominio.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Academy.
2. Define la interfaz StudentRepositoryPort con existsByEmail(string $email): bool y save(object $student): void.
3. Implementa la clase RegisterStudentUseCase inyectando StudentRepositoryPort en el constructor.
4. En el metodo execute(string $email, string $fullName): object, valida que el email no este vacio y lanza InvalidArgumentException si es invalido.
5. Invoca existsByEmail($email) en el puerto; si ya existe, lanza RuntimeException.
6. Crea la instancia del estudiante e invoca save($student) en el puerto, retornando la entidad.',
        'filename' => 'src/Academy/RegisterStudentUseCase.php',
        'guide' => [
            'steps' => [
                'Paso 1: Declara la interfaz StudentRepositoryPort con metodos existsByEmail y save.',
                'Paso 2: En RegisterStudentUseCase, inyecta private readonly StudentRepositoryPort $repository.',
                'Paso 3: Valida el email con filter_var y lanza InvalidArgumentException ante formato incorrecto.',
                'Paso 4: Invoca $this->repository->existsByEmail($email).',
                'Paso 5: Si no existe, persiste con $this->repository->save($student) y retorna el objeto.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El caso de uso solo conoce la interfaz del puerto StudentRepositoryPort, no la base de datos.',
            ],
            [
                'text' => '[Pista 2: Estructura] En execute(): if ($this->studentRepository->existsByEmail($email)) { throw new \\RuntimeException(...); }',
            ],
            [
                'text' => '[Pista 3: Snippet] interface StudentRepositoryPort { public function existsByEmail(string $email): bool; public function save(object $student): void; }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Academy;

use InvalidArgumentException;
use RuntimeException;

interface StudentRepositoryPort
{
    public function existsByEmail(string $email): bool;
    public function save(object $student): void;
}

class RegisterStudentUseCase
{
    public function __construct(
        // TODO: Inyectar puerto secundario StudentRepositoryPort
    ) {}

    public function execute(string $email, string $fullName): object
    {
        // TODO: Validar formato de email con filter_var (lanzar InvalidArgumentException)
        // TODO: Verificar duplicados usando existsByEmail en el puerto (lanzar RuntimeException)
        // TODO: Persistir nuevo estudiante con save en el puerto y retornar el objeto
        return new \\stdClass();
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Academy;

use InvalidArgumentException;
use RuntimeException;

interface StudentRepositoryPort
{
    public function existsByEmail(string $email): bool;
    public function save(object $student): void;
}

class RegisterStudentUseCase
{
    public function __construct(
        private readonly StudentRepositoryPort $studentRepository
    ) {}

    public function execute(string $email, string $fullName): object
    {
        $cleanEmail = trim(strtolower($email));
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\'El formato del email es invalido.\');
        }

        if ($this->studentRepository->existsByEmail($cleanEmail)) {
            throw new RuntimeException(sprintf(\'El estudiante con email "%s" ya existe.\', $cleanEmail));
        }

        $student = (object) [
            \'email\' => $cleanEmail,
            \'fullName\' => trim($fullName),
            \'registeredAt\' => new \\DateTimeImmutable(),
        ];

        $this->studentRepository->save($student);

        return $student;
    }
}
',
        'explanation' => 'La solucion desacopla completamente el caso de uso del mecanismo de almacenamiento. El caso de uso interactua unicamente con la abstraccion StudentRepositoryPort, lo que permite probarlo en memoria con un doble de prueba en microsegundos y cambiar de MySQL a DynamoDB sin alterar la logica.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Monolito Modular, Capas, Hexagonal & Clean Architecture',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la diferencia central entre la arquitectura en capas tradicional y la arquitectura hexagonal?',
                'options' => [
                    'a' => 'La arquitectura hexagonal exige programar en seis lenguajes de programacion simultaneos.',
                    'b' => 'En capas tradicional, la logica de negocio depende de la capa de datos; en hexagonal, la logica de negocio se coloca en el centro y la infraestructura depende de los puertos definidos por el dominio.',
                    'c' => 'La arquitectura en capas solo admite bases de datos NoSQL y la hexagonal solo admite MySQL.',
                    'd' => 'No existe diferencia; son dos terminos comerciales para la misma especificacion de directorios.',
                ],
                'correct' => 'b',
                'explanation' => 'La inversion de dependencias es el elemento diferenciador clave. En hexagonal, el dominio define las interfaces (puertos) y los adaptadores de infraestructura (Doctrine, PDO, APIs) deben adaptarse a ellas.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Que ventaja practica ofrece un Monolito Modular frente a una arquitectura de Microservicios para equipos medianos?',
                'options' => [
                    'a' => 'Permite tener separacion estricta de dominios y bajo acoplamiento sin sufrir la latencia de red, la consistencia eventual y la sobrecarga operativa de multiples despliegues.',
                    'b' => 'Elimina la necesidad de escribir pruebas unitarias o de integracion.',
                    'c' => 'Obliga a que todos los desarrolladores compartan la misma maquina virtual.',
                    'd' => 'Hace que las consultas SQL se ejecuten en tiempo negativo.',
                ],
                'correct' => 'a',
                'explanation' => 'El Monolito Modular otorga la misma disciplina de diseno y separacion de Bounded Contexts que los microservicios, pero dentro de un solo proceso en memoria sin el dolor de la distribucion de red.',
            ],
            [
                'id' => 'q3',
                'question' => 'En la regla de dependencia de Clean Architecture, ¿que direccion deben seguir SIEMPRE las dependencias del codigo fuente?',
                'options' => [
                    'a' => 'Hacia afuera, desde las entidades hacia las vistas y los controladores HTTP.',
                    'b' => 'Hacia adentro, desde los mecanismos externos (UI, DB, Framework) hacia las politicas de alto nivel y reglas de negocio del dominio.',
                    'c' => 'En ambas direcciones de forma circular bidireccional.',
                    'd' => 'Hacia el proveedor de nube AWS de manera obligatoria.',
                ],
                'correct' => 'b',
                'explanation' => 'Las reglas de negocio son la parte mas valiosa y estable de la empresa. La infraestructura cambia con frecuencia; por ello, la infraestructura debe depender del dominio y nunca al reves.',
            ],
        ],
    ],
];
