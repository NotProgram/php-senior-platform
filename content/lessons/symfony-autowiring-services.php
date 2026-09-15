<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-autowiring-services',
    'title' => 'Inyección de Dependencias, Autowiring & Servicios de Dominio',
    'module' => 'Symfony Framework',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'El Contenedor de Inyección de Dependencias (Dependency Injection Container - DIC) es la columna vertebral de Symfony. Gracias a las directivas autowire y autoconfigure activas por defecto en config/services.yaml, Symfony inspecciona las declaraciones de tipos de tus constructores y resuelve automáticamente qué clase o interfaz instanciar y suministrar, eliminando la necesidad de registrar servicios manualmente en archivos XML o YAML.',
        'problem' => 'El antipatrón más destructivo en Symfony es el Service Locator: acceder al contenedor mediante llamadas opacas como $this->container->get(\'mi_servicio\') o instanciar colaboradores con new. Esto oculta las dependencias reales de la clase, viola el Principio de Responsabilidad Única, rompe el autocompletado del IDE y hace imposible escribir pruebas unitarias con mocks.',
        'solution' => 'Dominar la Inyección de Dependencias por Constructor con promoción de propiedades y readonly en PHP 8.4: inyectar contratos e interfaces del estándar PSR (como LoggerInterface), utilizar el atributo #[Autowire] para parámetros escalares y variables de entorno (%env()%), y mantener todas las dependencias explícitas en la firma del constructor.',
        'problem_label' => 'El Problema: Service Locator Antipatrón, Contenedores Opacos y Acoplamiento Fuerte',
        'solution_label' => 'La Solución Senior: Inyección por Constructor, Autowiring Tipado e Interfaces PSR',
    ],
    'mental_model' => [
        'title' => 'El Taller del Relojero vs La Caja Negra Sorpresa',
        'analogy' => 'Imagina que contratas a un maestro relojero para ensamblar un cronómetro suizo.

En el antipatrón **Service Locator**, el relojero entra a su mesa de trabajo arrastrando una caja gigante sellada que contiene toda la ferretería del pueblo ($container). Cada vez que necesita una lupa o una pinza, mete la mano a ciegas en la caja. Si trasladas al relojero a otra mesa sin esa caja gigante o si la caja no tiene exactamente la llave que esperaba, el relojero falla estrepitosamente. Es imposible saber de antemano qué herramientas necesita para trabajar.

Con **Inyección de Dependencias**, el contrato es transparente y honorable: antes de que el relojero empiece, la firma de su taller (el constructor) declara: "Para armar este reloj, necesito exactamente estas pinzas (PinzaInterface) y esta lupa (LupaInterface)". Tú le entregas las herramientas al contratarlo. En tus pruebas unitarias, puedes pasarle pinzas de plástico (Mocks) en un segundo sin necesidad de montar una fábrica entera.',
        'ascii_diagram' => ' [ANTIPATRÓN: SERVICE LOCATOR]               [SENIOR: INYECCIÓN DE DEPENDENCIAS]
 ┌───────────────────────────────────┐       ┌───────────────────────────────────┐
 │ class OrderProcessor              │       │ class OrderProcessor              │
 │ {                                 │       │ {                                 │
 │   public function process()       │       │   public function __construct(    │
 │   {                               │       │     private LoggerInterface $log,│
 │     $log = $this->container       │       │     private MailerInterface $mail│
 │       ->get(\'logger\');            │       │   ) {}                            │
 │   }                               │       │ }                                 │
 └───────────────────────────────────┘       └───────────────────────────────────┘
   Dependencias invisibles, acopladas          Dependencias explícitas en firma,
   al contenedor, no testeable a solas         100% testeable con PHPUnit Mocks',
        'key_concept' => 'Un servicio nunca debe pedirle cosas al contenedor. El contenedor debe entregarle al servicio todo lo que necesita al momento de nacer en su constructor.',
    ],
    'internals' => [
        'title' => 'El Ciclo del Contenedor: Autowiring, Compilación y Resolución de Servicios',
        'steps' => [
            [
                'phase' => '1. Inspección de Tipos (Autowiring)',
                'description' => 'Symfony inspecciona el type-hint de cada argumento del constructor. Si tipas LoggerInterface, busca qué servicio implementa esa interfaz y lo inyecta sin configuración manual.',
            ],
            [
                'phase' => '2. Autoconfiguración de Tags',
                'description' => 'Si una clase implementa contratos como EventSubscriberInterface o Command, Symfony le asigna automáticamente las etiquetas correspondientes del contenedor.',
            ],
            [
                'phase' => '3. Atributo #[Autowire] para Escalares',
                'description' => 'Para inyectar parámetros del framework (%app.name%) o variables de entorno (%env(API_KEY)%), #[Autowire] enlaza el valor directamente en la propiedad del constructor.',
            ],
            [
                'phase' => '4. Compilación del Contenedor a Caché',
                'description' => 'En producción, todo el grafo de dependencias se compila a código PHP plano ultra-optimizado en var/cache/prod/, eliminando sobrecostes de reflexión en tiempo de ejecución.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Inversión de Control (IoC) y Principio de Hollywood',
            'icon' => 'cpu',
            'content' => 'El Principio de Hollywood dicta: "No nos llames, nosotros te llamaremos". Tus clases de negocio nunca deben pedir colaboradores a un registro central. El Contenedor de Inyección de Dependencias se encarga de fabricar y suministrar las instancias necesarias al arrancar.',
            'takeaways' => 'Un servicio bien diseñado expone con honestidad el 100% de sus necesidades en los parámetros de su constructor.',
        ],
        [
            'title' => 'Tipar Contra Interfaces (PSR-3 LoggerInterface)',
            'icon' => 'shield',
            'content' => 'Al inyectar dependencias de infraestructura como logging, nunca dependas de la clase concreta `Monolog\Logger`. Escribe siempre `Psr\Log\LoggerInterface`:
```php
use Psr\Log\LoggerInterface;

public function __construct(
    private readonly LoggerInterface $logger
) {}
```
Esto permite intercambiar Monolog por cualquier otro logger o por un Mock en pruebas unitarias sin tocar una sola línea de lógica.',
            'takeaways' => 'Depender de contratos PSR asegura portabilidad y aislamiento total.',
        ],
    ],
    'video' => [
        'title' => 'Symfony 7 Dependency Injection & Autowiring Masterclass',
        'speaker' => 'SymfonyCasts',
        'youtube_id' => 'k3eYh1QJq7M',
        'duration' => '22 min',
        'description' => 'Aprende cómo funciona el autowiring del contenedor de Symfony, cómo inyectar servicios con PHP 8 y cómo utilizar #[Autowire] para parámetros de configuración.',
        'key_takeaways' => [
            'Diferencia entre instanciación manual y resolución automática por contenedor.',
            'Por qué el Service Locator rompe la testeabilidad de la aplicación.',
            'Cómo inyectar interfaces de logging y parámetros de configuración con atributos.',
            'Cómo Symfony compila el contenedor para evitar sobrecostes de reflexión.',
        ],
    ],
    'architecture_code' => [
        'title' => 'Servicio de Dominio con Inyección de Dependencias y Parámetros',
        'description' => 'Servicio desacoplado con tipado estricto, constructor promocionado y uso de contratos PSR.',
        'language' => 'php',
        'code' => '<?php

declare(strict_types=1);

namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

final class UserNotifier
{
    public function __construct(
        private readonly LoggerInterface $logger,
        #[Autowire(param: \'app.name\')]
        private readonly string $appName = \'SeniorAcademy\'
    ) {}

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function notify(string $email, string $subject, string $message): bool
    {
        if (trim($email) === \'\' || !str_contains($email, \'@\')) {
            throw new \InvalidArgumentException(\'El correo electrónico suministrado es inválido.\');
        }

        if (trim($subject) === \'\' || trim($message) === \'\') {
            throw new \InvalidArgumentException(\'El asunto y el mensaje no pueden estar vacíos.\');
        }

        $this->logger->info(sprintf(
            \'[%s] Notificación enviada exitosamente a %s con asunto "%s"\',
            $this->appName,
            $email,
            $subject
        ));

        return true;
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior nunca instanciará un colaborador con new dentro de un servicio si ese colaborador realiza I/O (bases de datos, logs, emails, llamadas HTTP). En su lugar, type-hintea la interfaz en el constructor. Esto desacopla el qué (la necesidad de registrar logs) del cómo (Monolog guardando en disco o enviando a CloudWatch).',
        'critical_questions' => [
            '¿Esta clase oculta alguna dependencia que no esté visible en la firma de su constructor?',
            '¿Puedo escribir un test unitario para esta clase pasando mocks sin instanciar la base de datos ni Symfony?',
            '¿Estoy tipando contra una interfaz abstracta (LoggerInterface) en vez de una clase concreta acoplada?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Implementar un servicio que envíe notificaciones y registre logs de auditoría.',
        'junior' => [
            'approach' => 'Instancia un Logger con new o inyecta el contenedor completo ($container->get(\'logger\')).',
            'flaws' => [
                'Acopla la clase a una implementación específica o a todo el framework Symfony.',
                'Hace que escribir un test unitario requiera configurar el contenedor completo con decenas de servicios.',
            ],
        ],
        'senior' => [
            'approach' => 'Inyecta LoggerInterface y parámetros de configuración en el constructor mediante promoción de propiedades readonly.',
            'rationale' => [
                'Garantiza inmutabilidad, contratos explícitos y permite pruebas unitarias instantáneas con cualquier mock PSR-3.',
            ],
            'trade_offs' => [
                'Requiere añadir los argumentos al constructor, pero proporciona 100% de transparencia arquitectónica.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Principio de Inversión de Dependencias',
            'source' => 'Agile Software Development, Principles, Patterns, and Practices',
            'quote' => 'Los módulos de alto nivel no deben depender de los módulos de bajo nivel. Ambos deben depender de abstracciones.',
            'author' => 'Robert C. Martin',
            'explanation' => 'El contenedor de Symfony hace realidad este principio inyectando implementaciones concretas en servicios que solo conocen interfaces.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Documentación Oficial de Symfony: Service Container & Autowiring',
            'url' => 'https://symfony.com/doc/current/service_container.html',
            'description' => 'Guía definitiva sobre autowiring, inyección por constructor y configuración de servicios.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Servicio de Notificación con Autowiring y Validación',
        'objective' => 'Implementar la clase UserNotifier en App\\Service con tipado estricto, constructor promocionado inyectando LoggerInterface y appName, y validación estricta de parámetros.',
        'instructions' => 'Crea la clase UserNotifier en el namespace App\\Service con declare(strict_types=1);. Su constructor debe recibir private readonly Psr\\Log\\LoggerInterface $logger y private readonly string $appName = \'SeniorAcademy\'. Implementa dos métodos: getAppName(): string (retorna $this->appName) y notify(string $email, string $subject, string $message): bool (valida que $email no esté vacío y contenga \'@\', que $subject y $message no estén vacíos lanzando InvalidArgumentException si fallan, llama a $this->logger->info(...) y retorna true).',
        'filename' => 'UserNotifier.php',
        'guide' => [
            'explanation' => 'Aprenderás a estructurar servicios de dominio limpios y testeables inyectando dependencias por constructor.',
            'steps' => [
                'Paso 1: Inicia con declare(strict_types=1); y namespace App\\Service;.',
                'Paso 2: Importa Psr\\Log\\LoggerInterface e InvalidArgumentException.',
                'Paso 3: Define el constructor con private readonly LoggerInterface $logger y private readonly string $appName = \'SeniorAcademy\'.',
                'Paso 4: Implementa getAppName(): string retornando $this->appName.',
                'Paso 5: En notify(), valida email con str_contains($email, \'@\'), y subject/message con trim() === \'\'. Lanza InvalidArgumentException si no cumplen.',
                'Paso 6: Registra el evento con $this->logger->info(\'...\') y retorna true.',
            ],
            'useful_functions' => [
                [
                    'name' => 'str_contains(string $haystack, string $needle)',
                    'desc' => 'Determina si una cadena contiene otra de forma eficiente.',
                ],
                [
                    'name' => 'sprintf(string $format, mixed ...$values)',
                    'desc' => 'Construye un string formateado para el mensaje de log.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Validación de Correo',
                'text' => 'Comprueba que no esté vacío y que contenga el símbolo arroba.',
                'snippet' => 'if (trim($email) === \'\' || !str_contains($email, \'@\')) { throw new \\InvalidArgumentException(\'Email inválido\'); }',
            ],
            [
                'label' => 'Llamada al Logger',
                'text' => 'Utiliza el método info() de LoggerInterface inyectado.',
                'snippet' => '$this->logger->info("Notificación a {$email}: {$subject}");',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Notificaciones de Usuario.
 */
class UserNotifier
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $appName = \'SeniorAcademy\'
    ) {}

    public function getAppName(): string
    {
        // PASO 1: Retorna el nombre de la app inyectado
        return \'\';
    }

    /**
     * Envía una notificación validando los datos y registrando el evento en el logger.
     */
    public function notify(string $email, string $subject, string $message): bool
    {
        // PASO 2: Valida que $email no esté vacío y contenga \'@\' (lanza InvalidArgumentException si falla)
        // PASO 3: Valida que $subject y $message no estén vacíos (lanza InvalidArgumentException si falla)
        // PASO 4: Llama a $this->logger->info()
        // PASO 5: Retorna true
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\Service;

use InvalidArgumentException;
use Psr\Log\LoggerInterface;

/**
 * Servicio de Notificaciones de Usuario.
 */
class UserNotifier
{
    public function __construct(
        private readonly LoggerInterface $logger,
        private readonly string $appName = \'SeniorAcademy\'
    ) {}

    public function getAppName(): string
    {
        return $this->appName;
    }

    public function notify(string $email, string $subject, string $message): bool
    {
        if (trim($email) === \'\' || !str_contains($email, \'@\')) {
            throw new InvalidArgumentException(\'El correo electrónico proporcionado es inválido.\');
        }

        if (trim($subject) === \'\' || trim($message) === \'\') {
            throw new InvalidArgumentException(\'El asunto y el mensaje no pueden estar vacíos.\');
        }

        $this->logger->info(sprintf(
            \'[%s] Notificación enviada a %s: %s\',
            $this->appName,
            $email,
            $subject
        ));

        return true;
    }
}
',
        'explanation' => 'UserNotifier muestra la inyección de dependencias estándar en Symfony: constructor con interfaces PSR, propiedades readonly y validación defensiva de argumentos de entrada.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Inyección de Dependencias y Autowiring',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la ventaja fundamental del Autowiring en Symfony frente a configurar servicios en YAML?',
                'options' => [
                    'a' => 'Hace que PHP sea un lenguaje interpretado más rápido.',
                    'b' => 'Permite que Symfony resuelva dependencias automáticamente inspeccionando los tipos del constructor, eliminando mantenimiento manual de configuración.',
                    'c' => 'Impide el uso de pruebas unitarias.',
                    'd' => 'Obliga a que todos los servicios sean singleton no modificables.',
                ],
                'correct' => 'b',
                'explanation' => 'Autowiring asocia dependencias mediante type-hinting en tiempo de compilación del contenedor, simplificando radicalmente el código.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué el Service Locator ($container->get(...)) es considerado un antipatrón en Symfony moderno?',
                'options' => [
                    'a' => 'Porque hace imposible saber qué dependencias requiere una clase con solo leer su firma, dificultando las pruebas y el mantenimiento.',
                    'b' => 'Porque el contenedor de Symfony no permite invocar get() en modo desarrollo.',
                    'c' => 'Porque consume el doble de memoria RAM en el servidor.',
                    'd' => 'Porque solo funciona con bases de datos MySQL.',
                ],
                'correct' => 'a',
                'explanation' => 'El Service Locator oculta las dependencias reales de la clase y acopla el código al contenedor del framework en lugar de a contratos limpios.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué atributo moderno de Symfony permite inyectar un parámetro escalar o variable de entorno directamente en el constructor?',
                'options' => [
                    'a' => '#[Route]',
                    'b' => '#[Autowire]',
                    'c' => '#[Entity]',
                    'd' => '#[AsEventListener]',
                ],
                'correct' => 'b',
                'explanation' => '#[Autowire] (ej. #[Autowire(param: \'app.name\')]) inyecta parámetros y variables de entorno directamente en el constructor.',
            ],
        ],
    ],
];
