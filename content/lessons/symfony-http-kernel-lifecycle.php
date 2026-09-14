<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-http-kernel-lifecycle',
    'title' => 'HttpKernel: Anatomía del Ciclo de Vida, Eventos & Sub-Requests',
    'module' => 'Symfony Framework',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En el núcleo de Symfony reside un único contrato minimalista: \'HttpKernelInterface::handle(Request $request): Response\'. Toda la magia del framework es una tubería secuencial y predecible gobernada por 8 eventos del kernel que transforman una petición HTTP en una respuesta.',
        'problem' => 'Muchos desarrolladores ven a Symfony como un monolito inescrutable y recurren a hacks en controladores o middleware desordenado porque desconocen en qué evento del kernel deben interceptar la petición, provocando cabeceras de seguridad ausentes o ejecuciones redundantes.',
        'solution' => 'Dominar la cinta transportadora de HttpKernel, entender la bifurcación entre peticiones principales (MAIN_REQUEST) y sub-peticiones (SUB_REQUEST), e implementar suscriptores de eventos con EventSubscriberInterface para inyectar cabeceras de seguridad como X-Frame-Options y X-Content-Type-Options en \'KernelEvents::RESPONSE\'.',
        'problem_label' => 'El Problema: La Caja Negra de Symfony y Hacks en Controladores',
        'solution_label' => 'La Solución Senior: Tubería de HttpKernel, Sub-Requests y Suscriptores Formales',
    ],
    'mental_model' => [
        'title' => 'La Cinta Transportadora de Control de Calidad con 8 Sensores Láser',
        'analogy' => 'Imagina una fábrica automotriz de alta precisión. En la entrada llega una caja con la materia prima (`Request`). En lugar de que un operario intente ensamblar todo a mano, la caja se coloca en una cinta transportadora mecánica e inmutable llamada **HttpKernel** cuyo único trabajo es cumplir el contrato `handle(Request): Response`. A lo largo de la cinta existen **8 sensores con brazos robóticos (Eventos del Kernel)** colocados en un orden estrictamente invariable:

1. `kernel.request`: Seguridad y enrutamiento. Si un sensor detecta que el cliente no tiene autorización o excedió el límite de peticiones, emite un `Response` 401/429 inmediatamente y la cinta se salta todo el resto del ensamblaje (Short-circuit).
2. `kernel.controller`: Determina qué máquina especializada (Controlador) procesará la caja.
3. `kernel.controller_arguments`: Inyecta los componentes exactos que el controlador necesita (Value Resolvers / MapRequestPayload).
4. *Ejecución del Controlador*: Produce el producto (Response o DTO).
5. `kernel.view`: Si el controlador devolvió un DTO o array en vez de un Response, este brazo lo traduce a JSON o HTML.
6. `kernel.response`: Inspección final obligatoria. Se inyectan sellos de seguridad indelebles (`X-Frame-Options: DENY`, `X-Content-Type-Options: nosniff`), cookies seguras y compresión gzip.
7. `kernel.finish_request`: Restaura el contexto si era una sub-petición interna.
8. `kernel.terminate`: La caja ya viajó por la red; este sensor procesa tareas en segundo plano (logs, emails, colas) sin hacer esperar al cliente.',
        'ascii_diagram' => '                                 [HTTP REQUEST]
                                       │
                                       ▼
                             HttpKernel::handle()
                                       │
                    ┌──────────────────┴──────────────────┐
                    ▼                                     │
           [1. kernel.request] ──(¿Response prematura?)───┤ (Short-Circuit: 401/429)
                    │ NO                                  │
                    ▼                                     │
          [2. kernel.controller]                          │
                    │                                     │
                    ▼                                     │
     [3. kernel.controller_arguments]                     │
                    │                                     │
                    ▼                                     │
        [Ejecución de Controller]                         │
                    │                                     │
                    ▼                                     │
           [4. kernel.view] (Si no retornó Response)      │
                    │                                     │
                    ▼                                     ▼
           [5. kernel.response] <─────────────────────────┘
                    │ (Añade X-Frame-Options: DENY, cookies, gzip)
                    ▼
        [Emisión FastCGI a Nginx] ──> [HTTP 200 OK al Navegador]
                    │
                    ▼
          [6. kernel.terminate] (Post-envío: logs, emails, Messenger)',
        'key_concept' => 'Toda petición en Symfony es procesada por HttpKernel::handle(). Conocer sus eventos te permite interceptar flujos globalmente sin tocar los controladores.',
    ],
    'internals' => [
        'title' => 'Las Fases Críticas de HttpKernel en Symfony 7',
        'steps' => [
            [
                'phase' => '1. kernel.request y el Enrutador',
                'description' => 'RouterListener intercepta este evento y ejecuta la coincidencia de URLs, inyectando el controlador y los parámetros de ruta en los atributos del objeto Request ($request->attributes).',
            ],
            [
                'phase' => '2. Short-Circuiting (Cortocircuito)',
                'description' => 'Si cualquier listener en kernel.request establece una respuesta llamando a $event->setResponse($response), HttpKernel omite inmediatamente la resolución y ejecución del controlador, saltando directamente a kernel.response.',
            ],
            [
                'phase' => '3. Argument Resolvers y Value Resolvers',
                'description' => 'En kernel.controller_arguments, el framework analiza la firma de parámetros del método del controlador e invoca la cadena de Value Resolvers (para inyectar entidades Doctrine, DTOs con #[MapRequestPayload] o tokens de seguridad).',
            ],
            [
                'phase' => '4. kernel.response y Cabeceras Defensivas',
                'description' => 'Todos los objetos Response (tanto normales como nacidos de excepciones) pasan por este evento. Es el lugar idóneo para forzar políticas de Content-Security-Policy, HSTS y cabeceras anti-clickjacking.',
            ],
            [
                'phase' => '5. kernel.terminate y la Función fastcgi_finish_request()',
                'description' => 'Tras vaciar los buffers hacia Nginx con fastcgi_finish_request(), el kernel ejecuta kernel.terminate. Esto permite despachar mensajes asíncronos en Symfony Messenger sin retrasar la percepción de velocidad del usuario.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Main Requests vs Sub-Requests: Evitando Fugas de Estado',
            'icon' => 'layers',
            'content' => 'En Symfony existen dos tipos de peticiones:

• **HttpKernelInterface::MAIN_REQUEST:** La petición HTTP real que inició el cliente web.
• **HttpKernelInterface::SUB_REQUEST:** Peticiones internas disparadas por Twig (`{{ render(controller(...)) }}`) o por fragmentos ESI (Edge Side Includes).

**La Regla de Oro Senior:**
En listeners de eventos como `kernel.request` o `kernel.response`, SIEMPRE verifica si la petición es principal antes de ejecutar lógica global:

```php
public function onKernelResponse(ResponseEvent $event): void
{
    if (!$event->isMainRequest()) {
        return; // No añadas cabeceras ni cookies duplicadas en fragmentos internos!
    }
    // Lógica para la respuesta principal...
}
```',
            'takeaways' => 'Verifica siempre $event->isMainRequest() para evitar ejecutar lógica de sesión o cabeceras múltiples veces en sub-peticiones de Twig.',
        ],
        [
            'title' => 'Inyección Global de Seguridad en kernel.response',
            'icon' => 'shield',
            'content' => 'Un error típico en equipos junior es agregar cabeceras de seguridad manualmente en cada controlador con `$response->headers->set(...)`.

Esto deja rutas olvidadas y desprotegidas. Un ingeniero Senior implementa un `EventSubscriber` global suscrito a `KernelEvents::RESPONSE` que inyecta automáticamente:

• `X-Frame-Options: DENY` (Mitiga ataques de Clickjacking impidiendo que la web se incruste en iframes externos).
• `X-Content-Type-Options: nosniff` (Impide que los navegadores ignoren el Content-Type y ejecuten archivos subidos como scripts).

Esto garantiza que el 100% de las respuestas que salen de tu aplicación cumplan con los estándares de seguridad OWASP.',
            'takeaways' => 'Centraliza las cabeceras HTTP de seguridad en un suscriptor de kernel.response en lugar de ensuciar controladores individuales.',
        ],
    ],
    'video' => [
        'title' => 'Symfony7 HttpKernel Component and Request-Response lifecycle explained',
        'speaker' => 'Symfony Architecture Guide',
        'youtube_id' => 'eIxhElPzDLU',
        'duration' => '28 min',
        'description' => 'Una explicación visual paso a paso de la arquitectura interna del componente HttpKernel y el ciclo de eventos en Symfony 7.',
        'key_takeaways' => [
            'El contrato fundamental de HttpKernelInterface::handle().',
            'El orden cronológico exacto de los 8 eventos del kernel.',
            'Cómo el RouterListener y el ErrorListener interceptan excepciones.',
            'Diferencias de comportamiento entre Main Requests y Sub-Requests.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/EventSubscriber/SecurityHeadersSubscriber.php',
        'title' => 'Suscriptor de HttpKernel para Inyección de Cabeceras de Seguridad',
        'tag' => 'Symfony 7 HttpKernel Subscriber',
        'code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;
use Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;
use Symfony\\Component\\HttpKernel\\KernelEvents;

/**
 * Inyecta cabeceras defensivas HTTP en todas las respuestas principales
 * interceptando el evento KernelEvents::RESPONSE.
 */
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [\'onKernelResponse\', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) {
            return;
        }

        $response = $event->getResponse();
        $headers = $response->headers;

        // Protección estricta contra Clickjacking y MIME Sniffing
        $headers->set(\'X-Frame-Options\', \'DENY\');
        $headers->set(\'X-Content-Type-Options\', \'nosniff\');
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior copia y pega cabeceras de respuesta en cada controlador o culpa a Nginx cuando falta un header. Un ingeniero Senior comprende la tubería de eventos de HttpKernel, implementa un EventSubscriber que se autoconfigura mediante Symfony DI, y aplica políticas de seguridad transversales de forma declarativa y comprobable mediante WebTestCase.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Cabeceras manuales dispersas y sin filtro de sub-requests',
            'code' => '// En cada controlador por separado:
class UserController extends AbstractController {
    public function profile(): Response {
        $response = new Response("Perfil");
        $response->headers->set(\'X-Frame-Options\', \'DENY\'); // Si olvidas esto en 1 ruta, vulnerabilidad!
        return $response;
    }
}',
            'flaws' => [
                'Obliga a duplicar cabeceras en decenas de controladores, con riesgo de omisiones.',
                'No cubre páginas de error 404/500 producidas por el ExceptionListener.',
                'Imposible de mantener o auditar de forma centralizada.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: EventSubscriberInterface centralizado sobre KernelEvents::RESPONSE',
            'code' => 'declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;
use Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;
use Symfony\\Component\\HttpKernel\\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [KernelEvents::RESPONSE => \'onKernelResponse\'];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        if (!$event->isMainRequest()) return;
        $event->getResponse()->headers->set(\'X-Frame-Options\', \'DENY\');
        $event->getResponse()->headers->set(\'X-Content-Type-Options\', \'nosniff\');
    }
}',
            'rationale' => [
                'Aplica las cabeceras defensivas al 100% de las respuestas de la aplicación de forma automática.',
                'Verifica isMainRequest() para no alterar respuestas parciales en fragmentos embebidos de Twig.',
                'Autoconfigurado automáticamente por el contenedor de dependencias sin configuración YAML extra.',
            ],
            'trade_offs' => [
                'Añadir demasiada lógica pesada o consultas de base de datos en kernel.response retrasa la entrega de bytes a Nginx.',
                'Si una página legítima requiere incrustarse en un iframe interno, debe contemplarse una excepción o configuración para relajar X-Frame-Options en esa ruta específica.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'HttpKernelInterface Contract',
            'source' => 'Symfony Architecture Guide',
            'quote' => 'El HttpKernelInterface es el corazón de Symfony: una sola función pública que recibe un Request y devuelve un Response.',
            'author' => 'Fabien Potencier',
            'explanation' => 'Toda la infraestructura de Symfony está diseñada para honrar este contrato atómico y determinista.',
        ],
        [
            'topic' => 'Defense in Depth via HTTP Headers',
            'source' => 'OWASP Secure Headers Project',
            'quote' => 'Las cabeceras de respuesta HTTP como X-Frame-Options y X-Content-Type-Options proporcionan una capa vital de defensa en profundidad contra ataques del lado del cliente.',
            'author' => 'OWASP Foundation',
            'explanation' => 'Mitiga clases enteras de vulnerabilidades web directamente en la capa de transporte.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Docs: The HttpKernel Component',
            'url' => 'https://symfony.com/doc/current/components/http_kernel.html',
            'description' => 'Documentación oficial exhaustiva sobre el ciclo de vida y los eventos de HttpKernel.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'MDN Web Docs: X-Frame-Options Header',
            'url' => 'https://developer.mozilla.org/es/docs/Web/HTTP/Headers/X-Frame-Options',
            'description' => 'Guía de Mozilla sobre clickjacking y cómo prevenir el incrustado no autorizado en iframes.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'OWASP Cheat Sheet: Secure Headers',
            'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/HTTP_Headers_Cheat_Sheet.html',
            'description' => 'Lista de verificación oficial de OWASP para cabeceras HTTP defensivas.',
            'type' => 'SPEC',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Configuración Defensiva de HttpKernel con EventSubscriber',
        'objective' => 'Implementar SecurityHeadersSubscriber implementando EventSubscriberInterface para suscribirse a KernelEvents::RESPONSE e inyectar las cabeceras X-Frame-Options (DENY) y X-Content-Type-Options (nosniff) en la respuesta.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'SecurityHeadersSubscriber\' en el namespace \'App\\EventSubscriber\'.
3. Implementa \'Symfony\\Component\\EventDispatcher\\EventSubscriberInterface\'.
4. Suscribe el método \'onKernelResponse\' a \'KernelEvents::RESPONSE\' en \'getSubscribedEvents()\'.
5. Implementa \'onKernelResponse(ResponseEvent $event): void\' inyectando \'X-Frame-Options: DENY\' y \'X-Content-Type-Options: nosniff\' en los headers de la Response.',
        'filename' => 'src/EventSubscriber/SecurityHeadersSubscriber.php',
        'guide' => [
            'explanation' => 'Obtén la respuesta con $response = $event->getResponse();. Luego usa $response->headers->set(\'X-Frame-Options\', \'DENY\') y $response->headers->set(\'X-Content-Type-Options\', \'nosniff\'). Asegúrate de verificar si es main request.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse EventSubscriberInterface para autoconfigurar el suscriptor en el HttpKernel.',
            ],
            [
                'text' => '[Pista 2: Estructura] En getSubscribedEvents(), retorna [KernelEvents::RESPONSE => \'onKernelResponse\']. Implementa onKernelResponse(ResponseEvent $event): void.',
            ],
            [
                'text' => '[Pista 3: Snippet] $event->getResponse()->headers->set(\'X-Frame-Options\', \'DENY\'); $event->getResponse()->headers->set(\'X-Content-Type-Options\', \'nosniff\');.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;
use Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;
use Symfony\\Component\\HttpKernel\\KernelEvents;

// TODO: Implementa EventSubscriberInterface
class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        // TODO: Suscríbete a KernelEvents::RESPONSE
        return [];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        // TODO: Inyecta X-Frame-Options y X-Content-Type-Options
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\EventSubscriber;

use Symfony\\Component\\EventDispatcher\\EventSubscriberInterface;
use Symfony\\Component\\HttpKernel\\Event\\ResponseEvent;
use Symfony\\Component\\HttpKernel\\KernelEvents;

class SecurityHeadersSubscriber implements EventSubscriberInterface
{
    public static function getSubscribedEvents(): array
    {
        return [
            KernelEvents::RESPONSE => [\'onKernelResponse\', 0],
        ];
    }

    public function onKernelResponse(ResponseEvent $event): void
    {
        $response = $event->getResponse();

        $response->headers->set(\'X-Frame-Options\', \'DENY\');
        $response->headers->set(\'X-Content-Type-Options\', \'nosniff\');
    }
}
',
        'explanation' => 'SecurityHeadersSubscriber implementa EventSubscriberInterface y se suscribe a KernelEvents::RESPONSE para inyectar de manera global y automática las cabeceras defensivas X-Frame-Options y X-Content-Type-Options en todas las respuestas HTTP.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: HttpKernel: Anatomía del Ciclo de Vida, Eventos & Sub-Requests',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué ocurre si un listener asigna una Response durante el evento \'kernel.request\' usando $event->setResponse()?',
                'options' => [
                    'a' => 'El kernel ignora la respuesta y continúa ejecutando el controlador normalmente.',
                    'b' => 'Se produce un cortocircuito (Short-circuit): el kernel omite la resolución del controlador y salta directamente a los eventos de respuesta.',
                    'c' => 'PHP lanza un error fatal TypeError.',
                    'd' => 'El servidor Nginx se reinicia de emergencia.',
                ],
                'correct' => 'b',
                'explanation' => 'El short-circuiting permite a mecanismos de autenticación, rate-limiting y caché responder de forma inmediata sin desperdiciar CPU resolviendo controladores ni argumentos.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué es crucial verificar \'$event->isMainRequest()\' en listeners de \'KernelEvents::RESPONSE\'?',
                'options' => [
                    'a' => 'Porque de lo contrario PHPStan reporta un error de sintaxis.',
                    'b' => 'Para evitar que sub-peticiones internas generadas por fragmentos de Twig (render) sobreescriban cabeceras o cookies de la respuesta HTTP principal.',
                    'c' => 'Porque las sub-peticiones se ejecutan en un hilo de sistema operativo diferente.',
                    'd' => 'Porque las peticiones secundarias no tienen acceso a la base de datos.',
                ],
                'correct' => 'b',
                'explanation' => 'Si una página contiene llamadas internas para renderizar bloques parciales, sus respuestas individuales no deben interferir con las cabeceras ni el estado global de la página principal.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué ventaja tiene el evento \'kernel.terminate\' para optimizar la velocidad percibida por el usuario?',
                'options' => [
                    'a' => 'Permite ejecutar tareas pesadas (como enviar emails o escribir métricas) después de que la respuesta ya fue emitida al cliente web con fastcgi_finish_request().',
                    'b' => 'Borra la base de datos para liberar memoria RAM.',
                    'c' => 'Compila el contenedor de servicios en tiempo real.',
                    'd' => 'Invalida la sesión del usuario inmediatamente.',
                ],
                'correct' => 'a',
                'explanation' => 'kernel.terminate ocurre cuando la conexión HTTP con el navegador ya concluyó, permitiendo realizar labores de mantenimiento y despacho asíncrono sin agregar latencia visible al usuario.',
            ],
        ],
    ],
];
