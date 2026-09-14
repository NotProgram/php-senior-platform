<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-event-dispatcher',
    'title' => 'Event Dispatcher, EventSubscriberInterface & Prioridades Numéricas',
    'module' => 'Symfony Framework',
    'minutes' => 40,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'El componente EventDispatcher de Symfony implementa el patrón Observer mediado, permitiendo desacoplar componentes independientes. Un EventSubscriber es una clase autoconfigurada que declara explícitamente a qué eventos se suscribe y el orden de ejecución mediante prioridades numéricas enteras.',
        'problem' => 'El acoplamiento directo entre servicios (ej. que el proceso de checkout llame directamente a inventario, envío de correos, analítica y facturación) genera clases monstruosas (\'God Classes\') imposibles de mantener y donde el fallo de una tarea secundaria (analítica) tumba la operación principal.',
        'solution' => 'Emitir un evento de dominio (\'order.placed\'), implementar un suscriptor con EventSubscriberInterface y estructurar la ejecución mediante prioridades formales: prioridad alta (100) para operaciones críticas que deben ejecutarse primero (reserva de inventario), y prioridad baja (-50) para tareas accesorias (analítica).',
        'problem_label' => 'El Problema: Clases Monstruosas y Acoplamiento en Cascada',
        'solution_label' => 'La Solución Senior: EventDispatcher Desacoplado y Prioridades Numéricas',
    ],
    'mental_model' => [
        'title' => 'La Frecuencia de Radio de Emergencia y los Protocolos de Prioridad',
        'analogy' => 'Imagina la torre de control de un aeropuerto. Cuando un vuelo reporta una novedad de aterrizaje (`order.placed`), el controlador no se pone a marcar el teléfono de 10 personas una por una. En su lugar, emite un aviso por el canal de radio de emergencias (`EventDispatcher::dispatch()`). Todos los equipos sintonizados (`EventSubscriberInterface`) escuchan el mensaje simultáneamente, pero su actuación está estrictamente coordinada por **prioridades numéricas** (de mayor a menor entero):

• **Prioridad 100 (Equipo de bomberos en la pista: `reserveInventory`):** La operación más crítica debe actuar primero. Si no hay pista disponible o el stock se agotó, la operación se cancela en el milisegundo cero (Fail-Fast).
• **Prioridad 0 (Operaciones regulares: facturación y cobro).**
• **Prioridad -50 (Oficina de estadísticas y analítica: `trackAnalytics`):** La telemetría y métricas operan al final de la cola, cuando toda la lógica crítica ya concluyó con éxito y sin riesgo de bloquear la operación.',
        'ascii_diagram' => 'EVENT DISPATCHER: dispatch(new OrderPlacedEvent($order), \'order.placed\');
                                  │
                                  ▼
      COLA DE SUBSCRIBERS ORDENADA POR PRIORIDAD (DESCENDENTE):
      ┌────────────────────────────────────────────────────────┐
      │ Prioridad 100 : OrderSubscriber::reserveInventory      │ (Crítico: Fail-Fast)
      ├────────────────────────────────────────────────────────┤
      │ Prioridad 0   : PaymentProcessor::captureFunds         │ (Operación estándar)
      ├────────────────────────────────────────────────────────┤
      │ Prioridad -50 : AnalyticsSubscriber::trackAnalytics    │ (Telemetría diferida)
      └────────────────────────────────────────────────────────┘',
        'key_concept' => 'En Symfony EventDispatcher, los listeners con mayor prioridad numérica (positivos) se ejecutan antes. Los listeners con menor prioridad (negativos) se ejecutan al final.',
    ],
    'internals' => [
        'title' => 'Mecánica del Despacho de Eventos y Cola de Prioridades',
        'steps' => [
            [
                'phase' => '1. Registro en getSubscribedEvents()',
                'description' => 'La clase suscriptora declara un array asociativo con el nombre del evento y un array de tuplas [\'nombreMetodo\', prioridadEntera].',
            ],
            [
                'phase' => '2. Autoconfiguración del Contenedor',
                'description' => 'Al implementar EventSubscriberInterface, Symfony DI añade la etiqueta \'kernel.event_subscriber\' automáticamente sin necesidad de configuración en YAML.',
            ],
            [
                'phase' => '3. Ordenamiento del Árbol de Listeners (krsort)',
                'description' => 'El EventDispatcher agrupa los listeners de cada evento en una tabla hash indexada por prioridad y los ordena descendentemente para ejecución secuencial.',
            ],
            [
                'phase' => '4. Propagación y Detención ($event->stopPropagation())',
                'description' => 'Si un suscriptor crítico detecta una inconsistencia fatal, puede invocar $event->stopPropagation(), cancelando la ejecución de todos los listeners posteriores en la cola.',
            ],
            [
                'phase' => '5. Eventos Tipados Modernos vs Nombres de String',
                'description' => 'En Symfony 7, es una mejor práctica utilizar clases de evento tipadas (OrderPlacedEvent::class) en lugar de cadenas de texto (\'order.placed\'), permitiendo análisis estático riguroso.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Listeners vs Subscribers: La Decisión Arquitectónica',
            'icon' => 'git-branch',
            'content' => '¿Cuál es la diferencia entre un Event Listener y un Event Subscriber en Symfony?

• **Event Listener:** Es una clase PHP ordinaria. La relación con el evento y su prioridad se declara **afuera**, en un archivo de configuración YAML o mediante atributos `#[AsEventListener]`. Es útil cuando integras librerías de terceros cuyo código fuente no puedes modificar.
• **Event Subscriber:** Implementa `EventSubscriberInterface`. La clase declara **adentro** a qué eventos escucha y con qué prioridad mediante el método estático `getSubscribedEvents()`. El conocimiento reside en la propia clase (alta cohesión y portabilidad).

En arquitectura de dominio empresarial, **los Event Subscribers son los preferidos** porque mantienen la lógica de negocio y sus prioridades auto-contenidas y fácilmente testeables.',
            'takeaways' => 'Prefiere siempre EventSubscriberInterface para que el suscriptor sea el único dueño de sus prioridades y eventos asociados.',
        ],
        [
            'title' => 'Manejo de Fallos y Aislamiento de Errores en Analítica',
            'icon' => 'shield',
            'content' => 'Considera qué pasa si el servicio de Google Analytics o Mixpanel está caído durante el checkout de una tienda:

Si la llamada de analítica está dentro del flujo secuencial principal sin prioridad baja o sin try/catch defensivo, un timeout de 5 segundos en la API de analítica hará que el cliente vea un error 500 y crea que su pago no pasó, aunque la tarjeta ya haya sido cobrada.

Al otorgar prioridad `-50` a `trackAnalytics`, aseguras que el inventario y el cobro ya se hayan consolidado, y puedes capturar cualquier excepción en el método de analítica sin interrumpir la experiencia de compra del usuario.',
            'takeaways' => 'Asigna prioridades negativas a tareas secundarias de observabilidad y aísla sus posibles fallos para no interrumpir operaciones de negocio.',
        ],
    ],
    'video' => [
        'title' => 'From Events to Insights: Testing and Documenting Event-Based Software',
        'speaker' => 'Sebastian Bergmann (Creador de PHPUnit)',
        'youtube_id' => 'CySblzGly_U',
        'duration' => '46 min',
        'description' => 'Una conferencia magistral del creador de PHPUnit sobre diseño orientado a eventos, desacoplamiento, prioridades y testing de sistemas asíncronos.',
        'key_takeaways' => [
            'Cómo desacoplar sistemas mediante eventos sin caer en el \'infierno de la indirección\'.',
            'Estrategias para probar Event Subscribers de forma aislada con dobles de prueba.',
            'El peligro de los efectos secundarios descontrolados en cadenas de eventos.',
            'Patrones para documentar flujos de eventos en arquitecturas complejas.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/EventSubscriber/OrderProcessingSubscriber.php',
        'title' => 'Suscriptor de Pedidos con Prioridades Numéricas Diferenciadas',
        'tag' => 'Symfony 7 EventSubscriber Prioritized',
        'code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

/**
 * Suscriptor que procesa eventos de compra asignando prioridades numéricas estrictas:
 * la reserva de inventario actúa primero (100) y la analítica al final (-50).
 */
class OrderProcessingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \'order.placed\' => [
                [\'reserveInventory\', 100],
                [\'trackAnalytics\', -50],
            ],
        ];
    }

    /**
     * Tarea de alta criticidad (Prioridad 100): Bloquea el stock de inmediato.
     *
     * @param array<string, mixed> $event Datos del pedido
     */
    public function reserveInventory(array $event = []): void
    {
        // Lógica de reserva de inventario en almacén
    }

    /**
     * Tarea accesoria (Prioridad -50): Registra telemetría para marketing.
     *
     * @param array<string, mixed> $event Datos del pedido
     */
    public function trackAnalytics(array $event = []): void
    {
        // Lógica de tracking diferido
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior ejecuta todas las acciones de compra dentro del mismo método del controlador, acoplando el código de facturación con el de analítica y emails. Un ingeniero Senior emite un evento de dominio \'order.placed\', delega las responsabilidades a un EventSubscriber con prioridades numéricas explícitas, garantizando que los fallos secundarios no impidan el éxito de la transacción.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Controlador acoplado sin eventos ni prioridades',
            'code' => '// Controlador monolítico
class CheckoutController {
    public function placeOrder() {
        $this->inventory->reserve();
        $this->billing->charge();
        $this->analytics->track(); // Si esto falla o tarda 5s, se cuelga la compra!
        $this->mailer->send();
    }
}',
            'flaws' => [
                'Controlador sobrecargado con múltiples responsabilidades ajenas a HTTP.',
                'Si el servidor de analítica falla, tumba la compra del cliente.',
                'Imposible agregar nuevas acciones tras la compra sin modificar el controlador.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: EventSubscriber con prioridades numéricas 100 y -50',
            'code' => 'declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

class OrderProcessingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \'order.placed\' => [
                [\'reserveInventory\', 100],
                [\'trackAnalytics\', -50]
            ]
        ];
    }

    public function reserveInventory(array $event = []): void {}
    public function trackAnalytics(array $event = []): void {}
}',
            'rationale' => [
                'Desacopla el checkout emitiendo un evento y aislando cada colaborador.',
                'Asigna prioridad 100 a reserva de inventario para asegurar consistencia primero.',
                'Asigna prioridad -50 a analítica para que opere al final de la cadena de forma segura.',
            ],
            'trade_offs' => [
                'El abuso de eventos puede dificultar seguir el flujo de ejecución si no se documentan adecuadamente.',
                'En sistemas distribuidos masivos, eventos síncronos pesados deben migrarse a colas asíncronas con Symfony Messenger.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Observer Pattern & Decoupling',
            'source' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
            'quote' => 'El patrón Observer define una dependencia uno a muchos entre objetos de modo que cuando un objeto cambia de estado, todos sus dependientes son notificados automáticamente.',
            'author' => 'Gang of Four (GoF)',
            'explanation' => 'Permite que múltiples subsistemas reaccionen a un evento sin que el emisor los conozca.',
        ],
        [
            'topic' => 'Event Subscriber Self-Configuration',
            'source' => 'Symfony Documentation',
            'quote' => 'Un suscriptor le dice al despachador exactamente a qué eventos desea suscribirse y con qué prioridades, manteniendo la configuración dentro de la propia clase.',
            'author' => 'Fabien Potencier',
            'explanation' => 'Elimina la configuración externa en archivos YAML y facilita la cohesión del código.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Docs: The EventDispatcher Component',
            'url' => 'https://symfony.com/doc/current/components/event_dispatcher.html',
            'description' => 'Documentación oficial del componente de eventos de Symfony.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Docs: Event Listeners and Subscribers',
            'url' => 'https://symfony.com/doc/current/event_dispatcher.html',
            'description' => 'Guía práctica comparando listeners vs subscribers y gestión de prioridades.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Martin Fowler: Domain Event Pattern',
            'url' => 'https://martinfowler.com/eaaDev/DomainEvent.html',
            'description' => 'Artículo de Martin Fowler sobre eventos de dominio en aplicaciones empresariales.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Event Dispatcher & Subscriptions con Prioridades',
        'objective' => 'Implementar OrderProcessingSubscriber implementando EventSubscriberInterface para suscribirse al evento \'order.placed\', asignando prioridad 100 a reserveInventory y -50 a trackAnalytics.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'OrderProcessingSubscriber\' en el namespace \'App\\EventSubscriber\'.
3. Implementa \'EventSubscriberInterface\'.
4. En \'getSubscribedEvents()\', suscribe el evento \'order.placed\' a dos métodos: \'reserveInventory\' con prioridad 100, y \'trackAnalytics\' con prioridad -50.
5. Implementa los métodos \'reserveInventory(array $event = []): void\' y \'trackAnalytics(array $event = []): void\'.',
        'filename' => 'src/EventSubscriber/OrderProcessingSubscriber.php',
        'guide' => [
            'explanation' => 'En getSubscribedEvents(), retorna [\'order.placed\' => [[\'reserveInventory\', 100], [\'trackAnalytics\', -50]]]. Declara los métodos receptores.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse EventSubscriberInterface y suscribirse al evento \'order.placed\'.',
            ],
            [
                'text' => '[Pista 2: Estructura] Deben asignarse prioridades numéricas explícitas: 100 para reserveInventory y -50 para trackAnalytics.',
            ],
            [
                'text' => '[Pista 3: Snippet] return [\'order.placed\' => [[\'reserveInventory\', 100], [\'trackAnalytics\', -50]]];. Implementa ambos métodos con firma (array $event = []): void.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

class OrderProcessingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // TODO: Suscribe \'order.placed\' con prioridades 100 y -50
        return [];
    }

    // TODO: Implementa reserveInventory y trackAnalytics
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;

class OrderProcessingSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            \'order.placed\' => [
                [\'reserveInventory\', 100],
                [\'trackAnalytics\', -50],
            ],
        ];
    }

    public function reserveInventory(array $event = []): void
    {
        // Reserva de inventario con máxima prioridad
    }

    public function trackAnalytics(array $event = []): void
    {
        // Telemetría con menor prioridad al final
    }
}
',
        'explanation' => 'OrderProcessingSubscriber modela la orquestación ordenada de un evento mediante EventSubscriberInterface, garantizando que el stock se reserve primero (100) y las analíticas se registren al final (-50).',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Event Dispatcher, EventSubscriberInterface & Prioridades Numéricas',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿En qué orden ejecuta el EventDispatcher de Symfony a los listeners suscritos al mismo evento?',
                'options' => [
                    'a' => 'En orden aleatorio para prevenir bloqueos de concurrencia.',
                    'b' => 'De mayor a menor prioridad numérica entera (ej. 100 antes que 0, y 0 antes que -50).',
                    'c' => 'En orden alfabético según el nombre del método.',
                    'd' => 'De menor a mayor prioridad numérica.',
                ],
                'correct' => 'b',
                'explanation' => 'Symfony ordena los listeners de forma descendente por prioridad numérica. Cuanto mayor sea el número entero, más temprano se ejecuta el listener.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué método permite a un EventSubscriber declarar sus eventos y prioridades al contenedor?',
                'options' => [
                    'a' => 'registerEvents()',
                    'b' => 'getSubscribedEvents()',
                    'c' => 'listen()',
                    'd' => 'configureRoutes()',
                ],
                'correct' => 'b',
                'explanation' => 'EventSubscriberInterface exige el método estático getSubscribedEvents(), el cual retorna el mapa de eventos, métodos y prioridades asociadas.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué efecto tiene llamar a \'$event->stopPropagation()\' dentro de un listener de eventos?',
                'options' => [
                    'a' => 'Reinicia el servidor web Apache.',
                    'b' => 'Detiene inmediatamente la ejecución de todos los listeners restantes en la cola para ese evento.',
                    'c' => 'Borra el registro de la base de datos.',
                    'd' => 'Lanza una excepción fatal de división por cero.',
                ],
                'correct' => 'b',
                'explanation' => 'stopPropagation() interrumpe la cadena de despacho, impidiendo que los listeners posteriores reciban la notificación del evento.',
            ],
        ],
    ],
];
