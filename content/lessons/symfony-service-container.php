<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-service-container',
    'title' => 'DI Container, Compiler Passes & Autowire Tags',
    'module' => 'Symfony Framework',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'A diferencia de otros frameworks que resuelven dependencias mediante reflexión dinámica en cada petición web, el Dependency Injection Container de Symfony se compila a código PHP plano durante el calentamiento de caché (cache:warmup), ofreciendo rendimiento O(1) en producción.',
        'problem' => 'El uso del antipatrón Service Locator (\'$container->get(\'mi_servicio\')\' o inyectar \'ContainerInterface\') acopla el código a la infraestructura del framework, oculta dependencias reales, rompe el análisis estático y dificulta las pruebas unitarias.',
        'solution' => 'Aprovechar la inyección de colecciones de servicios etiquetados mediante \'iterable $notifiers\', definir contratos de interfaz limpios con \'NotifierInterface\' y agrupar colaboradores desacoplados sin acceder jamás al contenedor directamente.',
        'problem_label' => 'El Problema: El Antipatrón Service Locator y Dependencias Ocultas',
        'solution_label' => 'La Solución Senior: Contenedor Compilado e Inyección de Colecciones con \'iterable\'',
    ],
    'mental_model' => [
        'title' => 'El Molde de Acero de la Fundición vs El Mayordomo Indeciso (Service Locator)',
        'analogy' => 'Imagina dos formas de construir herramientas. En sistemas con contenedores dinámicos, el contenedor actúa como un mayordomo despistado: cada vez que le pides un destornillador (`$container->get(\'notifier\')`), el mayordomo sale corriendo a buscar notas adhesivas, revisa manuales con reflexión en cada habitación y tarda milisegundos preciosos en armar la herramienta en vivo. Symfony opera como una **Fundición Industrial de Acero**: durante el despliegue (`cache:warmup`), el compilador analiza todas las definiciones de clases, valida los tipos de cada argumento, ejecuta los **Compiler Passes** (que recogen servicios con etiquetas especiales como `#[AutoconfigureTag(\'app.notifier\')]`), y vierte metal fundido en un molde rígido. El resultado es un único archivo PHP nativo (`App_KernelProdContainer.php`) con métodos directos `new NotificationManager([new EmailNotifier(), new SmsNotifier()])`. En producción, el contenedor es código plano en OpCache sin reflexión, y las clases reciben colecciones tipadas (`iterable $notifiers`) sin saber ni preocuparse de qué es un contenedor.',
        'ascii_diagram' => 'COMPILACIÓN DEL CONTENEDOR (Build Time / bin/console cache:warmup):
[Clases Notifier con interfaz] ──┐
[Atributos de Autowire / Tags] ──┼──> [Compiler Passes] ──> [Resolución del Grafo]
[Definición de Servicios]      ──┘                                │
                                                                 ▼
                                                    [App_KernelProdContainer.php]
                                                    (Código PHP plano en OpCache)

CONSUMO SENIOR (Runtime - Zero Service Locator):
class NotificationManager {
    /**
     * @param iterable<NotifierInterface> $notifiers
     */
    public function __construct(
        private readonly iterable $notifiers // Inyección limpia de colección etiquetada
    ) {}
}',
        'key_concept' => 'Un servicio Senior nunca inyecta el Contenedor ni llama a $container->get(). Recibe exactamente las dependencias que necesita en su constructor, usando iterable para colecciones polimórficas.',
    ],
    'internals' => [
        'title' => 'La Compilación del Grafo de Inyección de Dependencias',
        'steps' => [
            [
                'phase' => '1. Registro de Definiciones (Definition & Reference)',
                'description' => 'El contenedor lee clases PHP, atributos y configuración, creando objetos Definition (instrucciones de cómo instanciar) y Reference (punteros a otros servicios).',
            ],
            [
                'phase' => '2. Ejecución de Compiler Passes',
                'description' => 'Las clases que implementan CompilerPassInterface inspeccionan y transforman el contenedor antes de congelarlo. Por ejemplo, buscan todos los servicios etiquetados con \'app.notifier\' y los inyectan como array en NotificationManager.',
            ],
            [
                'phase' => '3. Resolución de Dependencias Circulares y Optimización',
                'description' => 'El compilador detecta ciclos infinitos de dependencias (A requiere B y B requiere A), elimina servicios no utilizados que sean privados y genera proxies perezosos (lazy proxies) si es necesario.',
            ],
            [
                'phase' => '4. Volcado a Código PHP Nativo (Dumping)',
                'description' => 'PhpDumper genera una clase gigante en PHP plano que contiene métodos getService() ultra-optimizados, garantizando arranque instantáneo en producción.',
            ],
            [
                'phase' => '5. Inmutabilidad en Tiempo de Ejecución',
                'description' => 'Una vez compilado y congelado (frozen), es imposible añadir o modificar definiciones en el contenedor. Esto garantiza determinismo y seguridad de hilos.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Por qué el Service Locator ($container->get) es un Antipatrón Tóxico',
            'icon' => 'alert-triangle',
            'content' => '¿Por qué inyectar `ContainerInterface $container` es tan destructivo?

1. **Dependencias Invisibles:** La firma de la clase miente. Al mirar `public function __construct(ContainerInterface $c)`, parece que la clase solo depende del contenedor, pero por dentro llama a `$c->get(\'logger\')`, `$c->get(\'mailer\')`, `$c->get(\'doctrine\')`.
2. **Falla en Runtime en lugar de Compilación:** Si renombras un servicio, el contenedor no podrá validar el tipo hasta que un usuario ejecute esa línea específica en producción.
3. **Pesadilla de Testing:** Para probar la clase en un test unitario, debes simular (mock) todo el contenedor de Symfony con llamadas encadenadas en lugar de simplemente pasar instancias en el constructor.

La inyección por constructor declara abiertamente qué necesita la clase para funcionar.',
            'takeaways' => 'Prohíbe terminantemente inyectar ContainerInterface en tus servicios; usa siempre inyección por constructor promotida.',
        ],
        [
            'title' => 'Inyección de Colecciones Etiquetadas con #[AutowireIterator]',
            'icon' => 'cpu',
            'content' => 'En Symfony 7, la forma más elegante de implementar el patrón Strategy o Plugins es mediante colecciones etiquetadas con atributos:

```php
#[AutoconfigureTag(\'app.payment_gateway\')]
interface PaymentGatewayInterface { ... }

class PaymentManager {
    public function __construct(
        #[AutowireIterator(\'app.payment_gateway\')]
        private iterable $gateways
    ) {}
}
```

Cualquier nueva clase que implemente `PaymentGatewayInterface` recibe la etiqueta automáticamente y se inyecta en `PaymentManager` sin tocar ni una línea de configuración YAML.',
            'takeaways' => 'Combina #[AutoconfigureTag] con #[AutowireIterator] para crear arquitecturas de plugins extensibles y desacopladas.',
        ],
    ],
    'video' => [
        'title' => 'Collect Similar Services for Your #[AutowireLocator] Using Tags',
        'speaker' => 'Ryan Weaver (SymfonyCasts)',
        'youtube_id' => 'r7uesvWQetA',
        'duration' => '14 min',
        'description' => 'Tutorial exhaustivo sobre cómo agrupar servicios con etiquetas, usar AutowireIterator y AutowireLocator para inyecciones polimórficas.',
        'key_takeaways' => [
            'Cómo registrar etiquetas de servicios automáticamente con interfaces.',
            'Diferencias entre inyectar un iterable plano vs un ServiceLocator perezoso.',
            'Eliminación de la configuración manual en services.yaml gracias a atributos modernos.',
            'Cómo validar que todas las implementaciones cumplan con el contrato de negocio.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Service/Notification/NotificationManager.php',
        'title' => 'Gestor de Notificaciones con Inyección de Colección Etiquetada',
        'tag' => 'Symfony 7 DI Tagged Collection',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Service\\Notification;

/**
 * Contrato formal para cualquier canal de notificación.
 */
interface NotifierInterface
{
    public function supports(string $channel): bool;
    public function send(string $recipient, string $message): bool;
}

/**
 * Gestor de notificaciones que ensambla sus canales mediante una colección
 * tipada iterable $notifiers, sin tocar jamás el contenedor de servicios.
 */
class NotificationManager
{
    /**
     * @param iterable<NotifierInterface> $notifiers Colección de notificadores etiquetados
     */
    public function __construct(
        private readonly iterable $notifiers
    ) {}

    /**
     * Despacha el mensaje al canal correspondiente.
     */
    public function dispatch(string $channel, string $recipient, string $message): bool
    {
        foreach ($this->notifiers as $notifier) {
            if ($notifier->supports($channel)) {
                return $notifier->send($recipient, $message);
            }
        }

        return false;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior inyecta ContainerInterface para \'tener acceso a todo\' y ahorrarse declarar dependencias en el constructor. Un ingeniero Senior entiende que el acoplamiento al contenedor destruye la mantenibilidad: define contratos con interfaces, inyecta colecciones de servicios etiquetados con \'iterable\', y deja que el compilador de Symfony verifique la integridad del sistema en tiempo de construcción.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Service Locator acoplado y dependencias invisibles',
            'code' => '// Antipatrón Service Locator
class NotificationManager {
    public function __construct(private $container) {}

    public function dispatch($channel, $recipient, $msg) {
        // Oculta dependencias, no valida tipos, imposible de testear limpiamente
        if ($channel === \'email\') {
            return $this->container->get(\'app.email_notifier\')->send($recipient, $msg);
        }
        return false;
    }
}',
            'flaws' => [
                'Inyecta el contenedor violando la encapsulación y ocultando dependencias.',
                'Hardcodea nombres de servicios mágicos propensos a errores en runtime.',
                'Imposible de testear con tests unitarios simples sin mockear todo el contenedor.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Inyección de colección iterable tipada y polimórfica',
            'code' => 'declare(strict_types=1);

namespace App\\Service\\Notification;

interface NotifierInterface
{
    public function supports(string $channel): bool;
    public function send(string $recipient, string $message): bool;
}

class NotificationManager
{
    /**
     * @param iterable<NotifierInterface> $notifiers
     */
    public function __construct(private readonly iterable $notifiers) {}

    public function dispatch(string $channel, string $recipient, string $message): bool
    {
        foreach ($this->notifiers as $notifier) {
            if ($notifier->supports($channel)) {
                return $notifier->send($recipient, $message);
            }
        }
        return false;
    }
}',
            'rationale' => [
                'Inyecta iterable<NotifierInterface> respetando el Principio de Inversión de Dependencias (DIP).',
                'Cero acoplamiento a ContainerInterface o al framework Symfony.',
                'Permite agregar nuevos canales de notificación sin modificar NotificationManager (Open/Closed Principle).',
            ],
            'trade_offs' => [
                'Si la colección tiene 100 servicios pesados, un iterable plano puede instanciarlos secuencialmente; para lazy loading bajo demanda se puede emplear un ServiceLocator específico tipado.',
                'Requiere registrar interfaces y etiquetas formales para la autoconfiguración del contenedor.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Service Locator is an Anti-Pattern',
            'source' => 'Mark Seemann: Dependency Injection Principles',
            'quote' => 'El Service Locator es un antipatrón porque oculta las dependencias de una clase en lugar de exponerlas, causando errores en tiempo de ejecución en lugar de fallos de compilación.',
            'author' => 'Mark Seemann',
            'explanation' => 'El libro de referencia de inyección de dependencias explica por qué las dependencias deben ser siempre explícitas.',
        ],
        [
            'topic' => 'Compiled Container Advantage',
            'source' => 'Symfony Internals Documentation',
            'quote' => 'Al compilar el contenedor en código PHP puro, Symfony elimina la sobrecarga de reflexión durante las peticiones web en producción.',
            'author' => 'Nicolas Grekas',
            'explanation' => 'Permite arquitecturas enterprise ricas sin penalizaciones en los milisegundos de respuesta.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Docs: Service Container & Autowiring',
            'url' => 'https://symfony.com/doc/current/service_container.html',
            'description' => 'Documentación oficial del contenedor de inyección de dependencias de Symfony.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Docs: Working with Tagged Services',
            'url' => 'https://symfony.com/doc/current/service_container/tags.html',
            'description' => 'Guía oficial sobre inyección de colecciones etiquetadas con iterable y AutowireIterator.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Martin Fowler: Inversion of Control Containers and the Dependency Injection pattern',
            'url' => 'https://martinfowler.com/articles/injection.html',
            'description' => 'El artículo fundacional de Martin Fowler comparando Dependency Injection vs Service Locator.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: DI Container & Inyección de Colecciones Etiquetadas',
        'objective' => 'Implementar la interfaz NotifierInterface y la clase NotificationManager recibiendo una colección tipada iterable $notifiers en el constructor, sin utilizar ContainerInterface ni el antipatrón Service Locator.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la interfaz \'NotifierInterface\' con los métodos \'supports(string $channel): bool\' y \'send(string $recipient, string $message): bool\'.
3. Define la clase \'NotificationManager\' en el namespace \'App\\Service\\Notification\'.
4. Inyecta en el constructor \'iterable $notifiers\'.
5. Implementa el método \'dispatch(string $channel, string $recipient, string $message): bool\'.
6. Prohibido estrictamente usar \'$container->get()\' o \'ContainerInterface\'.',
        'filename' => 'src/Service/Notification/NotificationManager.php',
        'guide' => [
            'explanation' => 'En NotificationManager, recorre $this->notifiers con un foreach. Si $notifier->supports($channel) es verdadero, ejecuta return $notifier->send($recipient, $message);. Si ninguno soporta el canal, retorna false.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe definirse y utilizarse la interfaz NotifierInterface y la clase NotificationManager.',
            ],
            [
                'text' => '[Pista 2: Estructura] NotificationManager debe recibir una colección de servicios tipada iterable $notifiers en el constructor. No utilices ContainerInterface.',
            ],
            [
                'text' => '[Pista 3: Snippet] public function dispatch(string $channel, string $recipient, string $message): bool { foreach ($this->notifiers as $notifier) { if ($notifier->supports($channel)) return $notifier->send($recipient, $message); } return false; }.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Service\\Notification;

// TODO: Define NotifierInterface con supports y send
interface NotifierInterface
{
    public function supports(string $channel): bool;
    public function send(string $recipient, string $message): bool;
}

// TODO: Implementa NotificationManager inyectando iterable $notifiers
class NotificationManager
{
    // TODO: Constructor con iterable $notifiers (¡Sin ContainerInterface!)

    public function dispatch(string $channel, string $recipient, string $message): bool
    {
        // TODO: Itera los notificadores y delega si supports($channel)
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Service\\Notification;

interface NotifierInterface
{
    public function supports(string $channel): bool;
    public function send(string $recipient, string $message): bool;
}

class NotificationManager
{
    /**
     * @param iterable<NotifierInterface> $notifiers
     */
    public function __construct(
        private readonly iterable $notifiers
    ) {}

    public function dispatch(string $channel, string $recipient, string $message): bool
    {
        foreach ($this->notifiers as $notifier) {
            if ($notifier->supports($channel)) {
                return $notifier->send($recipient, $message);
            }
        }

        return false;
    }
}
',
        'explanation' => 'NotificationManager cumple con los principios de Dependency Injection al recibir la colección de canales mediante un iterable tipado. Desacopla la orquestación de las implementaciones concretas y destierra el antipatrón Service Locator.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: DI Container, Compiler Passes & Autowire Tags',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué el uso de \'$container->get()\' se considera un antipatrón en Symfony moderno?',
                'options' => [
                    'a' => 'Porque las dependencias de la clase quedan ocultas, se pierde la verificación de tipos en tiempo de compilación y se acopla la lógica al framework.',
                    'b' => 'Porque el contenedor de dependencias no permite inyectar más de 3 servicios.',
                    'c' => 'Porque \'get()\' solo funciona en entornos de desarrollo.',
                    'd' => 'Porque ralentiza la conexión con la base de datos MySQL.',
                ],
                'correct' => 'a',
                'explanation' => 'Al usar el contenedor como Service Locator, no puedes saber qué necesita la clase sin leer cada línea de su código, destruyendo la modularidad y dificultando el testing unitario.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cómo maneja Symfony la inyección de múltiples servicios que implementan la misma interfaz?',
                'options' => [
                    'a' => 'Lanza una excepción fatal de colisión de servicios.',
                    'b' => 'Mediante etiquetas de servicio (tags) inyectadas como colecciones \'iterable\' o \'ServiceLocator\' usando atributos como #[AutowireIterator].',
                    'c' => 'Obliga a que solo exista una única implementación de cada interfaz en todo el proyecto.',
                    'd' => 'Requiere escribir código en C dentro de vendor.',
                ],
                'correct' => 'b',
                'explanation' => 'Las etiquetas permiten agrupar todas las implementaciones de una interfaz y pasarlas como una colección iterable al servicio orquestador de manera automática.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué ocurre durante la fase de compilación del contenedor de Symfony (\'cache:warmup\')?',
                'options' => [
                    'a' => 'Se descargan las dependencias de Composer desde internet.',
                    'b' => 'Se valida el grafo completo de dependencias, se ejecutan los Compiler Passes y se vuelca una clase PHP plana ultra-optimizada en la caché.',
                    'c' => 'Se borran todas las tablas de la base de datos relacional.',
                    'd' => 'Se inicia el servidor Nginx en segundo plano.',
                ],
                'correct' => 'b',
                'explanation' => 'La compilación congela el grafo de inyección de dependencias y genera código PHP estático que el Zend Engine puede almacenar en OpCache sin sobrecarga en runtime.',
            ],
        ],
    ],
];
