<?php

declare(strict_types=1);

return [
    'slug' => 'arch-hexagonal-clean',
    'title' => 'Arquitectura Hexagonal: Cuando Si y Cuando No',
    'module' => 'architecture',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'La Arquitectura Hexagonal (tambien conocida como Ports & Adapters) es una de las tecnicas de diseno mas poderosas para aislar el nucleo de una aplicacion. Sin embargo, su adopcion ciega es una causa frecuente de bancarrota tecnica en startups y proyectos con requerimientos cambiantes. Un desarrollador Senior no solo sabe como implementar puertos y adaptadores; sabe con precision matematica CUANDO vale la pena pagar su costo cognitivo y cuando constituye sobre-ingenieria destructiva.

El hexagono tiene dos costados claros:
1. Costado Primario / Conductor (Driving): Actores externos que inician la accion (Controladores HTTP, Consolas CLI, Webhooks).
2. Costado Secundario / Conducido (Driven): Sistemas externos convocados por la aplicacion (Bases de datos relacionales, servicios de mensajeria, pasarelas de pago, almacenamiento en la nube S3).
En esta leccion diseccionamos un caso de uso real de registro de usuarios con validaciones de guardia, hash criptografico y puertos de repositorio, identificando el umbral exacto entre calidad arquitectonica y burocracia de codigo.',
        'problem' => 'La Arquitectura Hexagonal (tambien conocida como Ports & Adapters) es una de las tecnicas de diseno mas poderosas para aislar el nucleo de una aplicacion. Sin embargo, su adopcion ciega es una causa frecuente de bancarrota tecnica en startups y proyectos con requerimientos cambiantes. Un desarrollador Senior no solo sabe como implementar puertos y adaptadores; sabe con precision matematica CUANDO vale la pena pagar su costo cognitivo y cuando constituye sobre-ingenieria destructiva.',
        'solution' => 'El hexagono tiene dos costados claros:
1. Costado Primario / Conductor (Driving): Actores externos que inician la accion (Controladores HTTP, Consolas CLI, Webhooks).
2. Costado Secundario / Conducido (Driven): Sistemas externos convocados por la aplicacion (Bases de datos relacionales, servicios de mensajeria, pasarelas de pago, almacenamiento en la nube S3).
En esta leccion diseccionamos un caso de uso real de registro de usuarios con validaciones de guardia, hash criptografico y puertos de repositorio, identificando el umbral exacto entre calidad arquitectonica y burocracia de codigo.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Hub USB-C Universal para Laptops',
        'concept' => 'Piensa en una computadora portatil moderna que solo cuenta con puertos universales USB-C:
1. LA COMPUTADORA CENTRAL (El Dominio): Contiene el procesador, la memoria y el sistema operativo. Sabe procesar informacion, calcular matematicas y ejecutar logica.
2. LOS PUERTOS (Interfaces USB-C): Especifican un protocolo estricto de conexion electrica y transmision de datos. La laptop no sabe que dispositivo vas a conectar manana.
3. LOS ADAPTADORES (Perifericos): Si conectas un cable HDMI, un adaptador traduce las senales de video a USB-C. Si conectas un cable Ethernet de red, otro adaptador traduce los paquetes IP a USB-C. Si conectas un teclado mecanico, traduce pulsaciones de teclas a USB-C.

Si manana se inventa una nueva tecnologia de pantallas holograficas, no compras una laptop nueva; compras un adaptador USB-C a holograma. La computadora central nunca sufre modificaciones.',
        'ascii_diagram' => 'EL HEXAGONO DE PUERTOS Y ADAPTADORES:

       ADAPTADORES PRIMARIOS                       ADAPTADORES SECUNDARIOS
          (Driving / Entrada)                         (Driven / Salida)

      +----------------------+                     +----------------------+
      | WebController (HTTP) |                     | MySQL (Doctrine)     |
      +----------------------+                     +----------------------+
                 |                                            ^
                 v                                            |
        +------------------+                       +--------------------+
        | Driving Port     |                       | Driven Port        |
        | (RegisterUser)   |                       | (UserRepository)   |
        +------------------+                       +--------------------+
                 |                                            ^
                 v                                            |
        +---------------------------------------------------------------+
        |                     NUCLEO DE DOMINIO                         |
        |  - Entidad User (Invariantes, estado)                         |
        |  - Reglas de Guardia (Email valido, password >= 8 chars)      |
        |  - Excepciones de Dominio (UserAlreadyExistsException)        |
        +---------------------------------------------------------------+
                 ^                                            |
                 |                                            v
        +------------------+                       +--------------------+
        | Driving Port     |                       | Driven Port        |
        | (CLI Command)    |                       | (PasswordHasher)   |
        +------------------+                       +--------------------+
                 ^                                            |
                 |                                            v
      +----------------------+                     +----------------------+
      | UserRegisterCommand  |                     | SodiumHasher (Bcrypt)|
      +----------------------+                     +----------------------+
',
        'analogy' => 'Piensa en una computadora portatil moderna que solo cuenta con puertos universales USB-C:
1. LA COMPUTADORA CENTRAL (El Dominio): Contiene el procesador, la memoria y el sistema operativo. Sabe procesar informacion, calcular matematicas y ejecutar logica.
2. LOS PUERTOS (Interfaces USB-C): Especifican un protocolo estricto de conexion electrica y transmision de datos. La laptop no sabe que dispositivo vas a conectar manana.
3. LOS ADAPTADORES (Perifericos): Si conectas un cable HDMI, un adaptador traduce las senales de video a USB-C. Si conectas un cable Ethernet de red, otro adaptador traduce los paquetes IP a USB-C. Si conectas un teclado mecanico, traduce pulsaciones de teclas a USB-C.

Si manana se inventa una nueva tecnologia de pantallas holograficas, no compras una laptop nueva; compras un adaptador USB-C a holograma. La computadora central nunca sufre modificaciones.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'A nivel de ejecucion en PHP 8.4, la arquitectura hexagonal elimina cualquier dependencia directa con Symfony\\Component\\HttpFoundation\\Request en el nucleo del caso de uso.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'El controlador extrae los primitivos de la peticion HTTP y los transforma en un Command o DTO de entrada.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'El caso de uso valida los tipos de guardia con filter_var() y strlen(), verifica colisiones a traves del puerto secundario $userRepository->findByEmail(), calcula el hash con password_hash($password, PASSWORD_BCRYPT) y delega la persistencia en $userRepository->save($user).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => '¿Cuando NO utilizar Arquitectura Hexagonal? 1. Sistemas puramente CRUD de lectura intensiva donde no existen reglas de negocio complejas. 2. Proyectos piloto (MVP) con horizonte de vida menor a 6 meses donde la velocidad de descubrimiento de mercado es prioritaria. 3. Cuando el equipo es Junior y no comprende la inversion de dependencias, lo que produce una \'arquitectura hexagonal cosmetica\' donde las interfaces solo duplican las firmas de Doctrine sin aportar ningun desacoplamiento real.',
        ],
    ],
    'video' => [
        'title' => 'Hexagonal Architecture',
        'speaker' => 'Alistair Cockburn (Creador de la Arquitectura Hexagonal)',
        'youtube_id' => 'k0ykTxw7s0Y',
        'duration' => '55 min',
        'description' => 'Alistair Cockburn, firmante original del Manifiesto Agil, explica de primera mano la genesis de Ports & Adapters, el proposito de mantener la aplicacion operable por humanos y scripts de prueba, y como evitar la sobre-complejidad.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\User\\Application;

use InvalidArgumentException;
use RuntimeException;

class UserAlreadyExistsException extends RuntimeException {}

interface UserRepositoryPort
{
    public function findByEmail(string $email): ?object;
    public function save(object $user): void;
}

final readonly class RegisterUserUseCase
{
    public function __construct(
        private UserRepositoryPort $userRepository
    ) {}

    public function execute(string $email, string $plainPassword): object
    {
        $cleanEmail = trim($email);
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\'El formato de correo electronico es invalido.\');
        }

        if (strlen($plainPassword) < 8) {
            throw new InvalidArgumentException(\'La contrasena debe contener al menos 8 caracteres.\');
        }

        if ($this->userRepository->findByEmail($cleanEmail) !== null) {
            throw new UserAlreadyExistsException(sprintf(\'El usuario con email "%s" ya se encuentra registrado.\', $cleanEmail));
        }

        $passwordHash = password_hash($plainPassword, PASSWORD_BCRYPT, [\'cost\' => 12]);

        $user = (object) [
            \'email\' => $cleanEmail,
            \'passwordHash\' => $passwordHash,
            \'createdAt\' => new \\DateTimeImmutable(),
        ];

        $this->userRepository->save($user);

        return $user;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior adopta la Arquitectura Hexagonal como una moda estetica porque la vio en un blog, aplicandola a modelos anemicos donde solo hay getters y setters. El desarrollador Senior sabe que cada abstraccion es una hipoteca tecnica: solo se justifica si los intereses pagados (desacoplamiento, testeabilidad, proteccion de invariantes) superan el costo de su mantenimiento a largo plazo.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Crea una interfaz de repositorio para una entidad que solo se lee en un reporte y nunca cambiara de MySQL.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Mantiene queries directas con Doctrine o DBAL para reportes de solo lectura, y blinda con Hexagonal el motor de precios y autorizaciones criticas.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'El Proposito de Ports & Adapters',
            'source' => 'Hexagonal Architecture (Ports and Adapters)',
            'quote' => 'Permite que una aplicacion sea igualmente conducida por usuarios, programas, pruebas automatizadas o scripts batch, y que sea desarrollada y probada en aislamiento de sus dispositivos y bases de datos en tiempo de ejecucion.',
            'author' => 'Alistair Cockburn',
            'explanation' => 'El foco central es la independencia de ejecucion y la facilidad de prueba.',
        ],
        [
            'topic' => 'Sobre-arquitectura y Costo',
            'source' => 'Extreme Programming Explained: Embrace Change',
            'quote' => 'No agregues complejidad para un futuro hipotetico que quiza nunca llegue (YAGNI). Disena la solucion mas simple que pueda funcionar hoy.',
            'author' => 'Kent Beck',
            'explanation' => 'La arquitectura debe evolucionar al ritmo de la complejidad real del negocio.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Alistair Cockburn: Ports and Adapters Pattern',
            'url' => 'https://johanneslink.net/hexagonal-architecture/',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Netflix Technology Blog: Ready for Changes with Hexagonal Architecture',
            'url' => 'https://netflixtechblog.com/ready-for-changes-with-hexagonal-architecture-b61522d1a880',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Caso de Uso Hexagonal de Registro con Reglas de Guardia (RegisterUserUseCase)',
        'objective' => 'Implementar RegisterUserUseCase con validaciones estrictas de email con filter_var, longitud minima de contrasena (>= 8), verificacion de existencia con findByEmail(), lanzamiento de UserAlreadyExistsException, hashing con password_hash y persistencia con save().',
        'instructions' => '1. Declara strict_types=1 y namespace App\\User\\Application.
2. Declara la clase UserAlreadyExistsException extends \\RuntimeException.
3. Declara la interfaz UserRepositoryPort con findByEmail(string $email): ?object y save(object $user): void.
4. Implementa RegisterUserUseCase inyectando private readonly UserRepositoryPort $userRepository.
5. Valida que $email cumpla FILTER_VALIDATE_EMAIL; si no, lanza \\InvalidArgumentException.
6. Valida que strlen($plainPassword) >= 8; si no, lanza \\InvalidArgumentException.
7. Invoca $this->userRepository->findByEmail($email); si no es null, lanza new UserAlreadyExistsException.
8. Genera el hash de la contrasena con password_hash($plainPassword, PASSWORD_BCRYPT).
9. Persiste con $this->userRepository->save($user) y retorna el usuario creado.',
        'filename' => 'src/User/Application/RegisterUserUseCase.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida el email con filter_var($email, FILTER_VALIDATE_EMAIL).',
                'Paso 2: Comprueba que la longitud de la contrasena no sea menor a 8 caracteres (strlen < 8).',
                'Paso 3: Lanza InvalidArgumentException ante cualquiera de estas violaciones.',
                'Paso 4: Consulta el puerto $this->userRepository->findByEmail($email); si retorna un objeto, lanza UserAlreadyExistsException.',
                'Paso 5: Crea el hash criptografico con password_hash y persiste con save().',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Las reglas de guardia protegen las invariantes basicas antes de consultar la infraestructura.',
            ],
            [
                'text' => '[Pista 2: Estructura] if (strlen($plainPassword) < 8) { throw new \\InvalidArgumentException(\'Password too short\'); }',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($this->userRepository->findByEmail($email) !== null) { throw new UserAlreadyExistsException(\'User exists\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\User\\Application;

use InvalidArgumentException;
use RuntimeException;

class UserAlreadyExistsException extends RuntimeException {}

interface UserRepositoryPort
{
    public function findByEmail(string $email): ?object;
    public function save(object $user): void;
}

class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryPort $userRepository
    ) {}

    public function execute(string $email, string $plainPassword): object
    {
        // TODO: Validar email con filter_var($email, FILTER_VALIDATE_EMAIL) (lanzar InvalidArgumentException)
        // TODO: Validar que strlen($plainPassword) >= 8 (lanzar InvalidArgumentException)
        // TODO: Consultar $this->userRepository->findByEmail($email) (lanzar UserAlreadyExistsException si existe)
        // TODO: Generar hash con password_hash($plainPassword, PASSWORD_BCRYPT)
        // TODO: Persistir con $this->userRepository->save($user) y retornar usuario
        return new \\stdClass();
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\User\\Application;

use InvalidArgumentException;
use RuntimeException;

class UserAlreadyExistsException extends RuntimeException {}

interface UserRepositoryPort
{
    public function findByEmail(string $email): ?object;
    public function save(object $user): void;
}

class RegisterUserUseCase
{
    public function __construct(
        private readonly UserRepositoryPort $userRepository
    ) {}

    public function execute(string $email, string $plainPassword): object
    {
        $cleanEmail = trim($email);
        if (!filter_var($cleanEmail, FILTER_VALIDATE_EMAIL)) {
            throw new InvalidArgumentException(\'El email proporcionado no tiene un formato valido.\');
        }

        if (strlen($plainPassword) < 8) {
            throw new InvalidArgumentException(\'La contrasena debe contener al menos 8 caracteres.\');
        }

        $existing = $this->userRepository->findByEmail($cleanEmail);
        if ($existing !== null) {
            throw new UserAlreadyExistsException(sprintf(\'El usuario con email "%s" ya existe.\', $cleanEmail));
        }

        $hashed = password_hash($plainPassword, PASSWORD_BCRYPT);

        $user = (object) [
            \'email\' => $cleanEmail,
            \'password\' => $hashed,
            \'createdAt\' => new \\DateTimeImmutable(),
        ];

        $this->userRepository->save($user);

        return $user;
    }
}
',
        'explanation' => 'La implementacion de RegisterUserUseCase encapsula completamente el flujo de negocio del registro. Protege las invariantes con clausulas de guardia tempranas, evita colisiones arrojando una excepcion semantica UserAlreadyExistsException, cifra la credencial con password_hash y persiste a traves del puerto secundario.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Arquitectura Hexagonal: Cuando Si y Cuando No',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el rol exacto de un \'Adaptador Primario\' (Driving Adapter) en la Arquitectura Hexagonal?',
                'options' => [
                    'a' => 'Gestionar la conexion TCP a la base de datos MySQL.',
                    'b' => 'Recibir la peticion externa (HTTP, CLI, cola AMQP), transformarla en datos comprensibles por el dominio y convocar al caso de uso correspondiente.',
                    'c' => 'Generar las migraciones de base de datos con bin/console make:migration.',
                    'd' => 'Compilar el contenedor de inyeccion de dependencias en produccion.',
                ],
                'correct' => 'b',
                'explanation' => 'Los adaptadores primarios conducen la aplicacion. Toman una entrada del mundo exterior (como una peticion JSON o un comando de consola) y llaman a un puerto del caso de uso.',
            ],
            [
                'id' => 'q2',
                'question' => '¿En cual de los siguientes escenarios NO se recomienda aplicar Arquitectura Hexagonal completa?',
                'options' => [
                    'a' => 'En una aplicacion bancaria con reglas complejas de calculo de intereses y comisiones.',
                    'b' => 'En un microservicio de scoring de credito que integra tres buros de credito externos.',
                    'c' => 'En una aplicacion puramente CRUD de administración interna para listar y editar provincias de un catalogo estatico.',
                    'd' => 'En un sistema de facturacion electronica con estandares legales rigurosos.',
                ],
                'correct' => 'c',
                'explanation' => 'Para un CRUD simple sin reglas de negocio complejas, la arquitectura hexagonal solo agrega capas innecesarias de puertos, adaptadores y DTOs (sobre-ingenieria).',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que el caso de uso nunca debe recibir directamente el objeto Symfony\\Component\\HttpFoundation\\Request?',
                'options' => [
                    'a' => 'Porque la clase Request consume mas de 500 MB de memoria RAM.',
                    'b' => 'Porque acoplaria el caso de uso al componente HttpFoundation de Symfony, impidiendo ejecutar la misma logica desde un comando CLI o una prueba unitaria sin simular peticiones HTTP.',
                    'c' => 'Porque PHP 8.4 prohibe inyectar clases de frameworks en namespaces de aplicacion.',
                    'd' => 'Porque los controladores web no pueden acceder a los datos de la sesion.',
                ],
                'correct' => 'b',
                'explanation' => 'El caso de uso debe operar con tipos escalares o DTOs limpios de dominio. Si inyectas Request, el caso de uso ya no puede ser llamado desde un consumidor de colas de RabbitMQ o un comando de terminal.',
            ],
        ],
    ],
];
