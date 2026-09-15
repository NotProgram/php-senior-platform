<?php

declare(strict_types=1);

return [
    'slug' => 'symfony-architecture-controllers',
    'title' => 'Arquitectura Symfony, Flex & Controladores Delgados',
    'module' => 'Symfony Framework',
    'minutes' => 40,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'Symfony estructura aplicaciones robustas alrededor del patrón Front Controller (public/index.php). Con Symfony Flex, la configuración e integración de paquetes se automatiza mediante recetas oficiales. Los controladores en Symfony deben ser delgados (Thin Controllers): actúan como adaptadores HTTP que reciben el Request, delegan a los servicios de dominio y devuelven un Response o JsonResponse con códigos semánticos.',
        'problem' => 'Los desarrolladores inexpertos convierten los controladores en "Fat Controllers" monolíticos: escriben consultas directas a base de datos, lógica de negocio y cálculos matemáticos dentro de las acciones, además de usar sentencias echo o die() que rompen el ciclo de vida HTTP y destruyen la posibilidad de escribir tests unitarios.',
        'solution' => 'Adoptar la regla de oro arquitectónica de controladores delgados: extender AbstractController, declarar rutas explícitas con el atributo #[Route] indicando métodos HTTP (GET, POST), delegar la lógica a servicios inyectados por autowiring, y devolver siempre objetos Response o JsonResponse con códigos HTTP estándar (200, 201, 400, 503).',
        'problem_label' => 'El Problema: Controladores Grasos (Fat Controllers) y Mezcla de Responsabilidades',
        'solution_label' => 'La Solución Senior: Thin Controllers, Atributos de Ruta y Respuestas HTTP Semánticas',
    ],
    'mental_model' => [
        'title' => 'La Torre de Control Aéreo y los Hangares Especializados',
        'analogy' => 'Imagina el tráfico en un aeropuerto internacional. El Controlador Aéreo en la torre no carga combustible, no revisa motores ni sirve comida en los aviones (esas tareas pertenecen a mecánicos y servicios de pista especializados en sus hangares).

El Controlador Aéreo tiene una única responsabilidad: recibir la señal de radio del avión que entra (Request), verificar la pista asignada, consultar a los servicios de tierra para confirmar que todo está despejado, y emitir una autorización de aterrizaje o advertencia clara (Response HTTP 200 o 503).

En Symfony, tus controladores son la torre de control: nunca tocan la lógica interna ni la persistencia directamente, solo orquestan la comunicación entre el protocolo HTTP exterior y tus servicios de dominio.',
        'ascii_diagram' => '             [Cliente HTTP: Browser / API Client]
                           │
                           ▼
                  [public/index.php]  (Front Controller)
                           │
                           ▼
                   [HttpKernel::handle()]
                           │  (Resuelve ruta #[Route] y argumentos)
                           ▼
               ┌───────────────────────┐
               │ HealthCheckController │  (Torre de Control Delgada)
               └───────────┬───────────┘
                           │ (Delega ejecución)
              ┌────────────┴────────────┐
              ▼                         ▼
      [SystemHealthService]     [DatabaseMonitor]  (Servicios de Dominio)
              └────────────┬────────────┘
                           │
                           ▼
               [JsonResponse (200 / 503)]
                           │
                           ▼
             [Cliente HTTP: Recibe JSON]',
        'key_concept' => 'Un controlador es solo un adaptador de entrada en la frontera HTTP. Su única responsabilidad es transformar una petición HTTP en llamadas a servicios y devolver una respuesta HTTP tipada.',
    ],
    'internals' => [
        'title' => 'Anatomía del Ciclo Front Controller y Controladores Delgados',
        'steps' => [
            [
                'phase' => '1. Front Controller (public/index.php)',
                'description' => 'El servidor web redirige el 100% del tráfico a public/index.php, que arranca Composer, instancia App\Kernel y le entrega el Request creado desde Request::createFromGlobals().',
            ],
            [
                'phase' => '2. Symfony Flex & Autoconfiguración',
                'description' => 'Symfony Flex instala paquetes con recetas que autogeneran config/packages/ y enlazan bundles en config/bundles.php sin edición manual.',
            ],
            [
                'phase' => '3. Atributo #[Route] y Métodos HTTP',
                'description' => 'Las rutas se declaran con path, nombre único y verbos HTTP (methods: [\'GET\']) para garantizar seguridad e idempotencia.',
            ],
            [
                'phase' => '4. AbstractController y JsonResponse',
                'description' => 'Heredar de AbstractController permite invocar $this->json() y devolver instancias de JsonResponse con constantes de Response (HTTP_OK, HTTP_SERVICE_UNAVAILABLE).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Estructura de Directorios Canónica en Symfony 7',
            'icon' => 'folder',
            'content' => 'Symfony separa responsabilidades con claridad quirúrgica:
• **config/**: Configuración del contenedor de servicios, rutas y bundles.
• **src/**: Toda la lógica de tu aplicación (Controladores, Servicios, Entidades, DTOs).
• **public/**: El único directorio expuesto al servidor web con index.php y assets estáticos.
• **var/**: Datos volátiles como la caché compilada del contenedor y logs de Monolog.',
            'takeaways' => 'Nunca coloques código ejecutable en public/ salvo el archivo index.php.',
        ],
        [
            'title' => 'De la Petición a la Respuesta: La Regla de Oro del Thin Controller',
            'icon' => 'layers',
            'content' => 'Un controlador no debe contener lógica de negocio. Debe delegar siempre a servicios inyectados en la frontera HTTP.',
            'code' => '<?php

#[Route(\'/orders\', methods: [\'POST\'])]
public function create(CreateOrderDto $dto, OrderService $service): JsonResponse
{
    $order = $service->create($dto);
    return $this->json($order, Response::HTTP_CREATED);
}',
            'takeaways' => 'Si una acción de controlador supera las 20 líneas de código, casi con total seguridad estás cometiendo la violación de Fat Controller.',
        ],
    ],
    'video' => [
        'title' => 'Symfony 7 Tutorial - Controllers, Routes & JSON Responses',
        'speaker' => 'SymfonyCasts',
        'youtube_id' => 'xUODy8G9QG8',
        'duration' => '18 min',
        'description' => 'Guía esencial para entender la arquitectura básica de una aplicación Symfony moderna: desde el Front Controller hasta el retorno de respuestas JSON y HTML usando atributos nativos de PHP 8.',
        'key_takeaways' => [
            'Cómo viaja una petición desde el navegador hasta public/index.php.',
            'Uso de #[Route] con nombre y verbos HTTP para evitar colisiones.',
            'Por qué los controladores deben permanecer delgados y delegar la lógica a servicios.',
            'Diferencias entre Response genérico y JsonResponse.',
        ],
    ],
    'architecture_code' => [
        'title' => 'Controlador Delgado de Auditoría de Salud (Health Check)',
        'description' => 'Ejemplo de implementación de un Thin Controller en Symfony 7 que expone endpoints REST semánticos usando atributos y códigos HTTP nativos.',
        'language' => 'php',
        'code' => '<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route(\'/api\', name: \'api_\')]
final class HealthCheckController extends AbstractController
{
    #[Route(\'/health\', name: \'health_check\', methods: [\'GET\'])]
    public function __invoke(): JsonResponse
    {
        return $this->json([
            \'status\' => \'pass\',
            \'timestamp\' => time(),
            \'version\' => \'1.0.0\',
        ], Response::HTTP_OK);
    }

    #[Route(\'/health/component/{component}\', name: \'health_component\', methods: [\'GET\'])]
    public function checkComponent(string $component, bool $isHealthy = true): JsonResponse
    {
        $statusCode = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;
        $statusText = $isHealthy ? \'healthy\' : \'unhealthy\';

        return $this->json([
            \'component\' => $component,
            \'status\' => $statusText,
        ], $statusCode);
    }
}',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador Senior concibe el controlador únicamente como un traductor de protocolo: su trabajo es adaptar una petición HTTP al lenguaje de su aplicación y devolver una respuesta en el formato acordado. No coloca lógica de cálculo ni llamadas a bases de datos dentro de él; si necesita calcular algo, delega a un servicio de dominio que puede ser probado en aislamiento.',
        'critical_questions' => [
            '¿Este método de controlador puede ser testeado sin simular el entorno web completo?',
            '¿Estoy devolviendo códigos de estado HTTP correctos (ej. 201 en creación, 503 en indisponibilidad, 404 en ausencia)?',
            '¿He especificado el método HTTP (methods: [\'GET\']) para prevenir mutaciones indebidas por enlaces o prefetching del navegador?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Crear un endpoint para consultar el estado del sistema y de componentes individuales.',
        'junior' => [
            'approach' => 'Crea un script PHP plano o un controlador sin atributos con lógica inline, imprimiendo con echo json_encode(...) y llamando a exit().',
            'flaws' => [
                'La llamada a exit() corta el ciclo de vida de Symfony, impidiendo que los Event Listeners limpien memoria o cierren conexiones.',
                'No maneja cabeceras de respuesta formales ni códigos de estado HTTP adecuados como 503 Service Unavailable.',
            ],
        ],
        'senior' => [
            'approach' => 'Implementa un Thin Controller con #[Route], extiende AbstractController y devuelve instancias de JsonResponse con constantes de Response.',
            'rationale' => [
                'Permite que HttpKernel gestione cabeceras, compresión, listeners de respuesta y pruebas funcionales limpias con WebTestCase.',
            ],
            'trade_offs' => [
                'Requiere conocer la estructura del framework, pero asegura robustez industrial y testeabilidad inmediata.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Thin Controllers en Symfony',
            'source' => 'Symfony Best Practices Guide',
            'quote' => 'Mantén tus controladores delgados: su única función debe ser unir el protocolo HTTP con tu lógica de negocio.',
            'author' => 'Symfony Documentation Team',
            'explanation' => 'Un controlador delgado delega todo el trabajo a servicios reutilizables, permitiendo que la misma lógica se use desde la web o desde comandos CLI.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Documentación Oficial de Symfony: Controllers',
            'url' => 'https://symfony.com/doc/current/controller.html',
            'description' => 'Guía completa sobre creación de controladores, rutas y respuestas en Symfony.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Controlador Delgado de Verificación de Salud',
        'objective' => 'Implementar la clase HealthCheckController en el namespace App\\Controller extendiendo AbstractController, con dos endpoints tipados que devuelvan JsonResponse con códigos semánticos.',
        'instructions' => 'Crea la clase HealthCheckController en el namespace App\\Controller con declare(strict_types=1); y extendiendo AbstractController. Debe implementar dos métodos públicos: 1) check(): JsonResponse mapeado con #[Route(\'/api/health\', name: \'api_health_check\', methods: [\'GET\'])], que retorne un JsonResponse con status="pass", version="1.0.0" y código Response::HTTP_OK (200). 2) checkComponent(string $component, bool $isHealthy): JsonResponse que si $isHealthy es true devuelva status="healthy" con Response::HTTP_OK, y si es false devuelva status="unhealthy" con Response::HTTP_SERVICE_UNAVAILABLE (503).',
        'filename' => 'HealthCheckController.php',
        'guide' => [
            'explanation' => 'Este ejercicio refuerza el patrón de controladores delgados y el uso de constantes HTTP en Symfony.',
            'steps' => [
                'Paso 1: Declara declare(strict_types=1); y namespace App\\Controller;.',
                'Paso 2: Importa AbstractController, JsonResponse, Response y el atributo Route.',
                'Paso 3: Declara class HealthCheckController extends AbstractController.',
                'Paso 4: Implementa check(): JsonResponse con #[Route(\'/api/health\', name: \'api_health_check\', methods: [\'GET\'])].',
                'Paso 5: Implementa checkComponent(string $component, bool $isHealthy): JsonResponse evaluando el estado y retornando Response::HTTP_OK o Response::HTTP_SERVICE_UNAVAILABLE.',
            ],
            'useful_functions' => [
                [
                    'name' => '$this->json(mixed $data, int $status = 200)',
                    'desc' => 'Helper de AbstractController que crea y devuelve una instancia de JsonResponse serializada.',
                ],
                [
                    'name' => 'Response::HTTP_SERVICE_UNAVAILABLE',
                    'desc' => 'Constante oficial para el código de error HTTP 503 (Servicio no disponible).',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Uso del Helper json()',
                'text' => 'Puedes usar return $this->json([...], Response::HTTP_OK); o new JsonResponse([...], Response::HTTP_OK);.',
                'snippet' => 'return new JsonResponse([\'status\' => \'pass\', \'version\' => \'1.0.0\'], Response::HTTP_OK);',
            ],
            [
                'label' => 'Código de Estado 503',
                'text' => 'Usa la constante Response::HTTP_SERVICE_UNAVAILABLE para el caso no saludable.',
                'snippet' => '$code = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de Monitoreo y Verificación de Salud.
 */
class HealthCheckController extends AbstractController
{
    /**
     * Endpoint general de salud del sistema.
     */
    #[Route(\'/api/health\', name: \'api_health_check\', methods: [\'GET\'])]
    public function check(): JsonResponse
    {
        // PASO 1: Devuelve JsonResponse con [\'status\' => \'pass\', \'version\' => \'1.0.0\'] y código 200
        return new JsonResponse([], Response::HTTP_OK);
    }

    /**
     * Endpoint para consultar la disponibilidad de un componente específico.
     */
    public function checkComponent(string $component, bool $isHealthy): JsonResponse
    {
        // PASO 2: Si $isHealthy es true -> [\'component\' => $component, \'status\' => \'healthy\'] con 200
        // PASO 3: Si $isHealthy es false -> [\'component\' => $component, \'status\' => \'unhealthy\'] con 503
        return new JsonResponse([], Response::HTTP_OK);
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

/**
 * Controlador de Monitoreo y Verificación de Salud.
 */
class HealthCheckController extends AbstractController
{
    #[Route(\'/api/health\', name: \'api_health_check\', methods: [\'GET\'])]
    public function check(): JsonResponse
    {
        return new JsonResponse([
            \'status\' => \'pass\',
            \'version\' => \'1.0.0\',
        ], Response::HTTP_OK);
    }

    public function checkComponent(string $component, bool $isHealthy): JsonResponse
    {
        $status = $isHealthy ? \'healthy\' : \'unhealthy\';
        $statusCode = $isHealthy ? Response::HTTP_OK : Response::HTTP_SERVICE_UNAVAILABLE;

        return new JsonResponse([
            \'component\' => $component,
            \'status\' => $status,
        ], $statusCode);
    }
}
',
        'explanation' => 'HealthCheckController demuestra el diseño de controladores delgados: declara rutas y verbos con precisión, extiende AbstractController y encapsula respuestas con constantes HTTP estándar.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Arquitectura Symfony y Controladores Delgados',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es el rol exclusivo de un controlador delgado (Thin Controller) en una aplicación bien diseñada?',
                'options' => [
                    'a' => 'Ejecutar las consultas a la base de datos y calcular la facturación directamente.',
                    'b' => 'Adaptar la petición HTTP entrante, delegar la lógica a servicios y retornar un objeto Response semántico.',
                    'c' => 'Definir el esquema de tablas de la base de datos mediante anotaciones.',
                    'd' => 'Configurar los paquetes instalados por Composer mediante Flex.',
                ],
                'correct' => 'b',
                'explanation' => 'Un controlador actúa como un adaptador de transporte: traduce el protocolo HTTP a llamadas de servicios y responde con el status code adecuado.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué es una mala práctica usar echo y exit() dentro de una acción de controlador en Symfony?',
                'options' => [
                    'a' => 'Porque PHP 8 ha deprecado la función echo.',
                    'b' => 'Porque corta abruptamente el ciclo de vida de HttpKernel, impidiendo que se ejecuten los listeners de respuesta, caché y registro de métricas.',
                    'c' => 'Porque hace que el archivo de configuración services.yaml sea inválido.',
                    'd' => 'Porque Symfony no permite devolver texto plano.',
                ],
                'correct' => 'b',
                'explanation' => 'Al llamar a exit(), el framework no puede emitir eventos posteriores (kernel.response, kernel.terminate), perdiendo cookies, logs y cabeceras de seguridad.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué componente se encarga de automatizar la configuración de nuevos paquetes y bundles instalados en Symfony?',
                'options' => [
                    'a' => 'Doctrine ORM',
                    'b' => 'Symfony Flex',
                    'c' => 'Monolog Logger',
                    'd' => 'Twig Template Engine',
                ],
                'correct' => 'b',
                'explanation' => 'Symfony Flex es el plugin de Composer que detecta paquetes y descarga sus recetas oficiales de configuración en config/.',
            ],
        ],
    ],
];
