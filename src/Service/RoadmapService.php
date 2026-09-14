<?php

declare(strict_types=1);

namespace App\Service;

use App\DTO\LessonLocation;
use App\Entity\User;
use App\Entity\UserProgress;
use App\Repository\UserProgressRepository;

class RoadmapService
{
    public function __construct(
        private readonly UserProgressRepository $progressRepository
    ) {}

    /**
     * @return array<string, array<string, mixed>>
     */
    public function getSections(): array
    {
        return [
            'dashboard' => [
                'title' => 'Dashboard',
                'icon' => 'layout-dashboard',
                'route' => 'app_dashboard',
                'description' => 'Métricas de aprendizaje, progreso global y radar de habilidades',
            ],
            'roadmap' => [
                'title' => 'Roadmap',
                'icon' => 'map',
                'route' => 'app_roadmap',
                'description' => 'Mapa de progresión integral desde Junior a Senior',
            ],
            'labs' => [
                'title' => 'Laboratorios Interactivos',
                'icon' => 'sliders',
                'route' => 'app_labs',
                'description' => 'Simuladores de Git, Testing, HttpKernel, DI Container, Doctrine, Code Review y ADRs',
            ],
            'software-engineering' => [
                'title' => 'Software Engineering',
                'icon' => 'layers',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'software-engineering'],
                'category' => 'Ingeniería de Software',
                'description' => 'Fundamentos rigurosos del ciclo de vida del software (SDLC), especificación técnica con User Stories e INVEST, principios de diseño SOLID en PHP 8.4, métricas de cohesión y acoplamiento, y técnicas de refactorización para código de alta criticidad.',
                'video' => array (
  'title' => 'Clean Architecture & Software Craftsmanship',
  'speaker' => 'Robert C. Martin (Uncle Bob)',
  'youtube_id' => 'o_TH-Y78tt4',
  'duration' => '58 min',
  'description' => 'Conferencia canónica sobre cómo estructurar sistemas desacoplados de frameworks, protegiendo las reglas de negocio contra la volatilidad de la base de datos y la UI.',
  'key_takeaways' => 
  array (
    0 => 'La arquitectura es sobre intención, no sobre frameworks o bases de datos.',
    1 => 'El principio de inversión de dependencias como escudo ante cambios de infraestructura.',
    2 => 'La regla de dependencia: el código interno nunca debe conocer detalles del externo.',
    3 => 'El costo de no refactorizar: la curva exponencial de deuda técnica acumulada.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Deuda Técnica & Refactorización',
    'source' => 'Refactoring: Improving the Design of Existing Code',
    'quote' => 'Cualquier tonto puede escribir código que una computadora entienda. Los buenos programadores escriben código que los humanos pueden entender.',
    'author' => 'Martin Fowler',
    'explanation' => 'El código en producción pasa el 80% de su ciclo de vida siendo leído y mantenido por otros ingenieros. Optimizar para legibilidad reduce drásticamente los incidentes.',
  ),
  1 => 
  array (
    'topic' => 'Regla del Boy Scout',
    'source' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
    'quote' => 'Deja el campamento más limpio de como lo encontraste. En cada commit, el código debe quedar ligeramente mejor diseñado.',
    'author' => 'Robert C. Martin',
    'explanation' => 'La calidad no se logra parando el desarrollo tres meses para reescribir todo, sino mediante micro-refactorizaciones continuas respaldadas por tests.',
  ),
  2 => 
  array (
    'topic' => 'Criterios INVEST',
    'source' => 'Extreme Programming Installed / Agile Software Development',
    'quote' => 'Una historia de usuario no es una especificación rígida; es una promesa de conversación centrada en el valor de negocio medible.',
    'author' => 'Bill Wake & Kent Beck',
    'explanation' => 'Descomponer requerimientos en unidades independientes y testeables previene desbordamientos de sprint y ambigüedad funcional.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'Agile Alliance: Guía Oficial de Criterios INVEST',
    'url' => 'https://www.agilealliance.org/glossary/invest/',
    'description' => 'Definición formal de los 6 atributos esenciales para historias de usuario de alto valor.',
    'type' => 'SPEC',
  ),
  1 => 
  array (
    'title' => 'Refactoring.guru: Catálogo de Code Smells y Refactorización',
    'url' => 'https://refactoring.guru/es/refactoring',
    'description' => 'Guía visual completa con 24 olores de código y técnicas de extracción de métodos.',
    'type' => 'REFERENCE',
  ),
  2 => 
  array (
    'title' => 'PHP-FIG: PER Coding Style 2.0 (Evolución de PSR-12)',
    'url' => 'https://www.php-fig.org/per/coding-style/',
    'description' => 'Estándar oficial de interoperabilidad y estilo de código para PHP moderno.',
    'type' => 'DOCS',
  ),
),
                'lessons' => [
                    ['slug' => 'se-sdlc-requirements', 'title' => 'SDLC, Requerimientos & User Stories', 'minutes' => 45, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'se-clean-code-quality', 'title' => 'Clean Code, Cohesión & Acoplamiento', 'minutes' => 50, 'difficulty' => 'Intermedio'],
                    ['slug' => 'se-solid-principles', 'title' => 'Principios SOLID en PHP 8.4 & Casos Symfony', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'se-refactoring-code-smells', 'title' => 'Detección de Code Smells & Refactorización', 'minutes' => 55, 'difficulty' => 'Avanzado'],
                ],
            ],
            'git' => [
                'title' => 'Git & GitHub Profesional',
                'icon' => 'git',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'git'],
                'category' => 'Control de Versiones',
                'description' => 'Control de versiones profesional: modelo mental interno del grafo acíclico dirigido (DAG), árboles y blobs, rebase vs merge interactivo, Conventional Commits y gobernanza de repositorios con Branch Protection y Pull Requests de alto nivel.',
                'video' => array (
  'title' => 'Linus Torvalds on Git & Content-Addressable Storage',
  'speaker' => 'Linus Torvalds (Google Tech Talks)',
  'youtube_id' => '4XpnKHJAok8',
  'duration' => '1h 10m',
  'description' => 'Linus Torvalds explica desde los principios de diseño por qué Git trata el historial como un grafo de instantáneas criptográficas SHA-1 inmutables y no como diferencias (deltas) entre archivos.',
  'key_takeaways' => 
  array (
    0 => 'Git es un sistema de archivos direccionable por contenido con interfaz VCS por encima.',
    1 => 'Los commits son instantáneas atómicas completas, nunca deltas acumulativos.',
    2 => 'El DAG (Directed Acyclic Graph) hace que las fusiones y ramas sean operaciones O(1).',
    3 => 'La inmutabilidad de la historia protege la integridad de los artefactos de software.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Direccionamiento por Contenido',
    'source' => 'Pro Git Book (2nd Edition)',
    'quote' => 'En su núcleo, Git es un almacén de clave-valor direccionable por contenido: insertas contenido y obtienes un hash con el que recuperarlo en cualquier momento.',
    'author' => 'Scott Chacon & Ben Straub',
    'explanation' => 'Comprender los objetos blob, tree, commit y tag desmitifica cualquier problema de merge o detached HEAD.',
  ),
  1 => 
  array (
    'topic' => 'Higiene de Commits',
    'source' => 'Conventional Commits Specification v1.0.0',
    'quote' => 'El mensaje de commit es la documentación viva del software. Un historial limpio permite versionado semántico automatizado y changelogs sin fricción.',
    'author' => 'Conventional Commits Working Group',
    'explanation' => 'Estructurar los commits con feat:, fix:, refactor: permite que los pipelines de CI/CD decidan releases automáticamente.',
  ),
  2 => 
  array (
    'topic' => 'Rebase vs Merge',
    'source' => 'Git Documentation: Branching & Rebasing',
    'quote' => 'Rebase reescribe el historial proyectando tus cambios sobre la punta de otra rama; debe usarse para mantener ramas locales limpias antes de publicarlas.',
    'author' => 'Junio C Hamano (Git Maintainer)',
    'explanation' => 'La regla de oro: nunca hagas rebase en ramas compartidas públicas ya empujadas al servidor.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'Pro Git: Libro Oficial Gratuito (Scott Chacon)',
    'url' => 'https://git-scm.com/book/es/v2',
    'description' => 'El libro canónico definitivo sobre el funcionamiento interno y comandos de Git.',
    'type' => 'BOOK',
  ),
  1 => 
  array (
    'title' => 'Especificación Conventional Commits v1.0.0',
    'url' => 'https://www.conventionalcommits.org/es/v1.0.0/',
    'description' => 'Convención formal para estructurar mensajes de commit compatibles con SemVer.',
    'type' => 'SPEC',
  ),
  2 => 
  array (
    'title' => 'Git SCM Reference: Plumbing Commands',
    'url' => 'https://git-scm.com/docs',
    'description' => 'Documentación de los comandos internos de bajo nivel (hash-object, cat-file).',
    'type' => 'DOCS',
  ),
),
                'lessons' => [
                    ['slug' => 'git-fundamentals-plumbing', 'title' => 'Modelo Mental de Git: Árbol de Trabajo & Staging Area', 'minutes' => 40, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'git-branching-strategies', 'title' => 'Ramas, Merge vs Rebase & Cherry-pick', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'git-pr-code-review', 'title' => 'Conventional Commits, Pull Requests & Code Review', 'minutes' => 45, 'difficulty' => 'Senior'],
                    ['slug' => 'git-github-collaboration', 'title' => 'GitHub Flow, Trunk-Based & Branch Protection', 'minutes' => 40, 'difficulty' => 'Avanzado'],
                ],
            ],
            'php-fundamentals' => [
                'title' => 'PHP Moderno',
                'icon' => 'php',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'php-fundamentals'],
                'category' => 'Lenguaje Core',
                'description' => 'El runtime de PHP moderno: ciclo de vida de la petición web en PHP-FPM, Zend VM, gestión de memoria con zvals y Copy-On-Write (COW), OpCache, compilación JIT, autoloading PSR-4 en Composer y las novedades de PHP 8.4 (Property Hooks, Asymmetric Visibility).',
                'video' => array (
  'title' => 'Give your PHP apps superpowers with FrankenPHP',
  'speaker' => 'Kévin Dunglas (Creador de FrankenPHP & Core Team Symfony)',
  'youtube_id' => 'nuxI6MSEMEg',
  'duration' => '45 min',
  'description' => 'Conferencia de Kévin Dunglas sobre la evolución del runtime de PHP, Worker Mode, Go embed y la ejecución moderna de alto rendimiento sin PHP-FPM tradicional.',
  'key_takeaways' => 
  array (
    0 => 'Diferencia entre el modelo tradicional PHP-FPM y el Worker Mode en memoria persistente.',
    1 => 'El modelo de memoria zval en PHP 8: paso por valor con semántica Copy-On-Write.',
    2 => 'El recolector de ciclos de basura (GC) y cómo evitar fugas de memoria en workers de larga vida.',
    3 => 'La revolución del tipado estricto y el impacto en optimizaciones del motor Zend.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Share Nothing Architecture',
    'source' => 'PHP Internals Architecture & History',
    'quote' => 'PHP fue diseñado bajo la premisa de no compartir estado entre peticiones web (share-nothing), eliminando carreras de hilos y garantizando un aislamiento total.',
    'author' => 'Rasmus Lerdorf',
    'explanation' => 'Cada petición comienza limpia y libera su memoria al terminar. En entornos CLI o daemons asíncronos, el ingeniero debe gestionar el estado explícitamente.',
  ),
  1 => 
  array (
    'topic' => 'Sistemas de Tipos Modernos',
    'source' => 'RFC: Strict Types & Static Analysis in PHP',
    'quote' => 'El tipado estricto (declare(strict_types=1)) no solo atrapa errores en tiempo de desarrollo; permite que el compilador y analizadores como PHPStan prueben la corrección formal.',
    'author' => 'Nikita Popov',
    'explanation' => 'En aplicaciones enterprise, las conversiones implícitas de strings a enteros causan brechas de seguridad y cálculos erróneos en transacciones financieras.',
  ),
  2 => 
  array (
    'topic' => 'Property Hooks en PHP 8.4',
    'source' => 'PHP RFC: Property Hooks',
    'quote' => 'Property hooks reducen la verbosidad de getters y setters sin sacrificar la encapsulación, permitiendo lógica de validación directamente ligada a la propiedad.',
    'author' => 'Larry Garfield & Ilija Tovilo',
    'explanation' => 'Elimina cientos de líneas de boilerplate en DTOs y entidades mientras mantiene las propiedades protegidas contra mutaciones inválidas.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'PHP Manual Oficial: Internals & Predefined Attributes',
    'url' => 'https://www.php.net/manual/es/',
    'description' => 'Documentación oficial del motor PHP, funciones nativas y directivas de php.ini.',
    'type' => 'DOCS',
  ),
  1 => 
  array (
    'title' => 'PHP Internals Handbook',
    'url' => 'https://www.phpinternalsbook.com/',
    'description' => 'Guía exhaustiva sobre la estructura en C de Zend Engine, zvals, hash tables y AST.',
    'type' => 'BOOK',
  ),
  2 => 
  array (
    'title' => 'PHP RFC: Property Hooks (PHP 8.4)',
    'url' => 'https://wiki.php.net/rfc/property-hooks',
    'description' => 'La propuesta técnica oficial que revoluciona el modelado de propiedades y encapsulación.',
    'type' => 'RFC',
  ),
),
                'lessons' => [
                    ['slug' => 'php-syntax-types-variables', 'title' => 'Sintaxis, Tipos Primitivos & Tipado Estricto', 'minutes' => 35, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-control-flow-functions', 'title' => 'Control de Flujo, Expresiones Match & Funciones Tipadas', 'minutes' => 40, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-arrays-data', 'title' => 'Arrays Indexados, Asociativos & Transformación Funcional', 'minutes' => 40, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-oop-foundations', 'title' => 'Fundamentos de POO: Clases, Instancias & Encapsulación', 'minutes' => 45, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-request-lifecycle', 'title' => 'Request Lifecycle & Web Servers', 'minutes' => 35, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-types-memory', 'title' => 'Tipado Estricto & Gestión de Memoria', 'minutes' => 45, 'difficulty' => 'Intermedio'],
                    ['slug' => 'php-opcache-jit', 'title' => 'OpCache, Preloading & JIT Compiler', 'minutes' => 40, 'difficulty' => 'Avanzado'],
                    ['slug' => 'php-namespaces-autoloading', 'title' => 'PSR-4, Namespaces & Composer Internals', 'minutes' => 30, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'php-error-handling-exceptions', 'title' => 'Manejo Robusto de Errores & Excepciones', 'minutes' => 40, 'difficulty' => 'Intermedio'],
                    ['slug' => 'php-modern-features-84', 'title' => 'PHP 8.4: Property Hooks & Asymmetric Visibility', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'poo' => [
                'title' => 'POO & Modelado',
                'icon' => 'code',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'poo'],
                'category' => 'Paradigma',
                'description' => 'Programación Orientada a Objetos y Modelado de Dominio: protección de invariantes de negocio, encapsulación estricta, composición sobre herencia en sistemas reales, Value Objects inmutables vs DTOs vs Entidades, y PHP Backed Enums como máquinas de estados deterministas.',
                'video' => array (
  'title' => 'Nothing is Something: Composition, OO Design & Polymorphism',
  'speaker' => 'Sandi Metz (Autora de POODR & 99 Bottles of OOP)',
  'youtube_id' => 'OMPfEXIlTVE',
  'duration' => '34 min',
  'description' => 'Masterclass indispensable sobre cómo modelar comportamientos evitando la herencia jerárquica frágil, utilizando composición, inyección de dependencias y polimorfismo limpio.',
  'key_takeaways' => 
  array (
    0 => 'La herencia acopla fuertemente las subclases a la implementación de la superclase.',
    1 => 'La composición permite ensamblar comportamientos en tiempo de ejecución de manera intercambiable.',
    2 => 'El principio Tell, Don\'t Ask: pide al objeto que ejecute una acción, no le pidas sus datos para decidir por él.',
    3 => 'Los objetos nulos y polimorfismo eliminan condicionales if/else interminables.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Tell, Don\'t Ask',
    'source' => 'The Pragmatic Programmer',
    'quote' => 'La encapsulación no es solo ocultar atributos privados con getters; es agrupar los datos con los métodos que operan sobre ellos para que el llamador no tenga que conocer su estado interno.',
    'author' => 'Andy Hunt & Dave Thomas',
    'explanation' => 'Hacer $order->getStatus() === "PAID" en 10 sitios del código dispersa la lógica. Lo correcto es encapsular con $order->canBeCancelled().',
  ),
  1 => 
  array (
    'topic' => 'Inmutabilidad en Value Objects',
    'source' => 'Domain-Driven Design: Tackling Complexity in the Heart of Software',
    'quote' => 'Muchos objetos no tienen identidad conceptual. Estos objetos describen características de una cosa. Se llaman Value Objects y deben ser estrictamente inmutables.',
    'author' => 'Eric Evans',
    'explanation' => 'Un Money o un EmailAddress no cambian su valor; si sumas dinero, obtienes una nueva instancia de Money, previniendo efectos secundarios invisibles en la memoria.',
  ),
  2 => 
  array (
    'topic' => 'Principio de Sustitución de Liskov',
    'source' => 'Behavioral Subtyping Using Invariants and Constraints',
    'quote' => 'Si para cada objeto o1 de tipo S existe un objeto o2 de tipo T tal que para todos los programas P definidos en términos de T, el comportamiento de P no cambia cuando se usa o1 en lugar de o2, entonces S es un subtipo de T.',
    'author' => 'Barbara Liskov',
    'explanation' => 'Una subclase nunca debe debilitar las precondiciones ni fortalecer las postcondiciones de la clase base.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'Martin Fowler: Value Object Definition & Mechanics',
    'url' => 'https://martinfowler.com/bliki/ValueObject.html',
    'description' => 'Artículo canónico sobre la igualdad por atributos y la inmutabilidad de los objetos de valor.',
    'type' => 'ARTICLE',
  ),
  1 => 
  array (
    'title' => 'PHP Manual: Enumeraciones (Backed Enums & Métodos)',
    'url' => 'https://www.php.net/manual/es/language.enumerations.php',
    'description' => 'Sintaxis, interfaces y métodos de los Enums nativos introducidos en PHP 8.1.',
    'type' => 'DOCS',
  ),
  2 => 
  array (
    'title' => 'Refactoring Guru: Patrones de Diseño & POO',
    'url' => 'https://refactoring.guru/es/design-patterns',
    'description' => 'Explicación visual de encapsulación, abstracción, herencia y polimorfismo.',
    'type' => 'REFERENCE',
  ),
),
                'lessons' => [
                    ['slug' => 'poo-encapsulation-invariants', 'title' => 'Encapsulación & Protección de Invariantes', 'minutes' => 40, 'difficulty' => 'Intermedio'],
                    ['slug' => 'poo-composition-over-inheritance', 'title' => 'Composition over Inheritance en la Práctica', 'minutes' => 45, 'difficulty' => 'Avanzado'],
                    ['slug' => 'poo-value-objects-dtos', 'title' => 'Value Objects vs DTOs vs Entidades', 'minutes' => 45, 'difficulty' => 'Senior'],
                    ['slug' => 'poo-enums-state-machines', 'title' => 'PHP Enums como Máquinas de Estado Seguras', 'minutes' => 35, 'difficulty' => 'Avanzado'],
                ],
            ],
            'symfony' => [
                'title' => 'Symfony Framework',
                'icon' => 'symfony',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'symfony'],
                'category' => 'Framework',
                'description' => 'Arquitectura del framework empresarial Symfony: ciclo de vida completo de HttpKernel, eventos del kernel, compilación del Dependency Injection Container, Compiler Passes, Value Resolvers y mejores prácticas del framework.',
                'video' => array (
  'title' => '20 Years of Symfony, What\'s Next?',
  'speaker' => 'Fabien Potencier (Creador y Project Lead de Symfony)',
  'youtube_id' => '_UU_ZIwgBv0',
  'duration' => '48 min',
  'description' => 'Fabien Potencier repasa la evolución arquitectónica del framework, la adopción radical de PHP 8+, atributos modernos y el ecosistema de componentes desacoplados.',
  'key_takeaways' => 
  array (
    0 => 'HttpKernelInterface es el contrato fundamental de Symfony: handle(Request): Response.',
    1 => 'El contenedor de dependencias se compila a código PHP plano ultra-rápido en cache/dev.',
    2 => 'Los Event Listeners y Subscribers permiten interceptar peticiones, controladores y excepciones sin acoplamiento.',
    3 => 'Autoconfiguración, autowiring y atributos modernos reducen la configuración manual al mínimo.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Contrato HttpKernelInterface',
    'source' => 'Symfony Architecture Documentation',
    'quote' => 'El HttpKernelInterface es el corazón de Symfony: una sola función pública que recibe un Request y devuelve un Response. Todo el framework gira en torno a este contrato.',
    'author' => 'Fabien Potencier',
    'explanation' => 'Comprender este contrato te permite crear middlewares, sub-requests, pruebas funcionales ultra-rápidas y emuladores sin arrancar un servidor HTTP.',
  ),
  1 => 
  array (
    'topic' => 'Contenedor Compilado',
    'source' => 'Symfony Service Container Internals',
    'quote' => 'A diferencia de contenedores dinámicos en otros lenguajes que usan reflexión lenta en cada petición, Symfony compila el grafo de servicios en una clase PHP nativa altamente optimizada.',
    'author' => 'Nicolas Grekas',
    'explanation' => 'El tiempo de arranque (boot) es casi instantáneo en producción gracias al volcado en Opcache.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'Documentación Oficial de Symfony: HttpKernel Component',
    'url' => 'https://symfony.com/doc/current/components/http_kernel.html',
    'description' => 'Flujo de eventos del kernel: request, controller, view, response, finish_request, exception.',
    'type' => 'DOCS',
  ),
  1 => 
  array (
    'title' => 'Symfony Best Practices Guide (Official)',
    'url' => 'https://symfony.com/doc/current/best_practices.html',
    'description' => 'Guía oficial de convenciones de arquitectura y código limpio en Symfony.',
    'type' => 'DOCS',
  ),
),
                'lessons' => [
                    ['slug' => 'symfony-http-kernel-lifecycle', 'title' => 'HttpKernel: El Ciclo de Vida Real', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'symfony-service-container', 'title' => 'DI Container & Compiler Passes', 'minutes' => 55, 'difficulty' => 'Senior'],
                    ['slug' => 'symfony-event-dispatcher', 'title' => 'Event Dispatcher & Subscriptions', 'minutes' => 40, 'difficulty' => 'Avanzado'],
                    ['slug' => 'symfony-routing-controllers', 'title' => 'Routing, Argument Resolvers & Value Resolvers', 'minutes' => 45, 'difficulty' => 'Intermedio'],
                ],
            ],
            'twig' => [
                'title' => 'Twig Template Engine',
                'icon' => 'twig',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'twig'],
                'category' => 'Presentación',
                'description' => 'Motor de plantillas y renderizado defensivo: arquitectura del compilador Lexer-Parser-Compiler, herencia con bloques y macros, extensiones desacopladas (AbstractExtension) y auto-escaping contextual contra Cross-Site Scripting (XSS).',
                'video' => [
                    'title' => 'PHP Template Engines Explained: Twig, Smarty, Plates for Beginners',
                    'speaker' => 'CodeLucky',
                    'youtube_id' => 'almJ8Gsn6SA',
                    'duration' => '18 min',
                    'description' => 'Introducción rigurosa a la separación de responsabilidades entre vista y lógica de negocio, compilación de plantillas a PHP nativo en caché y prevención de código espagueti.',
                    'key_takeaways' => [
                        'Por qué mezclar lógica de negocio y SQL en las vistas degrada la mantenibilidad del sistema.',
                        'El ciclo de vida de compilación de Twig: de sintaxis declarativa a clases PHP compiladas en var/cache.',
                        'Uso de TwigFilter para transformaciones de datos y TwigFunction para generación de contenido.',
                        'El modelo de seguridad de auto-escaping contextual en contextos HTML, JS, CSS y URL.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Separación de Presentación',
                        'source' => 'Patterns of Enterprise Application Architecture',
                        'quote' => 'La vista debe ser tan delgada e ignorante como sea posible; su único trabajo es presentar datos preparados por capas inferiores.',
                        'author' => 'Martin Fowler',
                        'explanation' => 'Toda lógica de cálculo, acceso a persistencia o transformación compleja debe residir en servicios o extensiones dedicadas.',
                    ],
                    [
                        'topic' => 'Seguridad Contextual en Vistas',
                        'source' => 'Twig Security Architecture',
                        'quote' => 'El auto-escaping no es una opción estética, es la primera línea de defensa contra inyecciones XSS que comprometen las sesiones de los usuarios.',
                        'author' => 'Fabien Potencier',
                        'explanation' => 'Twig sanitiza por defecto según el contexto de renderizado para neutralizar payloads maliciosos.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Twig Documentation Oficial (v3.x)',
                        'url' => 'https://twig.symfony.com/doc/3.x/',
                        'description' => 'Referencia completa de etiquetas, filtros, funciones y arquitectura interna del motor.',
                        'type' => 'DOCS',
                    ],
                    [
                        'title' => 'Symfony UX Twig Components',
                        'url' => 'https://symfony.com/bundles/ux-twig-component/current/index.html',
                        'description' => 'Componentes de interfaz encapsulados y reutilizables en Symfony con Twig.',
                        'type' => 'GUIDE',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'twig-clean-separation', 'title' => 'Separación Estricta de Lógica de Presentación', 'minutes' => 35, 'difficulty' => 'Intermedio'],
                    ['slug' => 'twig-inheritance-components', 'title' => 'Herencia Jerárquica & Componentes Reutilizables', 'minutes' => 40, 'difficulty' => 'Intermedio'],
                    ['slug' => 'twig-escaping-security', 'title' => 'Auto-escaping, Contextos Seguros & XSS Defense', 'minutes' => 45, 'difficulty' => 'Senior'],
                ],
            ],
            'databases' => [
                'title' => 'Bases de Datos & SQL',
                'icon' => 'mysql',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'databases'],
                'category' => 'Persistencia',
                'description' => 'Persistencia relacional de alto rendimiento: anatomía física de índices B-Tree en disco, optimización de planes de ejecución con EXPLAIN, consultas sargables y transacciones ACID bajo niveles de aislamiento Read Committed, Repeatable Read y Serializable.',
                'video' => [
                    'title' => 'Database Indexing & Query Optimization Internals',
                    'speaker' => 'Hussein Nasser',
                    'youtube_id' => '-qNSXK7s7_w',
                    'duration' => '35 min',
                    'description' => 'Conferencia profunda sobre B-Trees, paginación en disco de MySQL/PostgreSQL y lectura rigurosa de planes EXPLAIN para eliminar Full Table Scans en producción.',
                    'key_takeaways' => [
                        'Diferencia entre Index Scan, Index Range Scan y Full Table Scan.',
                        'Por qué aplicar funciones sobre columnas en el WHERE anula los índices (Non-Sargable).',
                        'Impacto de los índices compuestos y la regla del prefijo más a la izquierda.',
                        'Niveles de aislamiento ACID y gestión de bloqueos de fila.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Diseño Físico de Índices',
                        'source' => 'SQL Antipatterns: Avoiding the Pitfalls of Database Programming',
                        'quote' => 'Un índice no es una varita mágica; un índice mal estructurado ralentiza las escrituras sin acelerar las lecturas.',
                        'author' => 'Bill Karwin',
                        'explanation' => 'Cada índice tiene un costo O(log N) de actualización en INSERT, UPDATE y DELETE.',
                    ],
                    [
                        'topic' => 'Transacciones e Integridad',
                        'source' => 'Designing Data-Intensive Applications',
                        'quote' => 'ACID no es un estado binario; el nivel de aislamiento que elijas determina si tu aplicación sufrirá lecturas fantasma o anomalías de serialización bajo concurrencia.',
                        'author' => 'Martin Kleppmann',
                        'explanation' => 'Comprender los niveles de aislamiento previene pérdidas financieras por carreras de concurrencia.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Use The Index, Luke! (Markus Winand)',
                        'url' => 'https://use-the-index-luke.com/',
                        'description' => 'Guía canónica de diseño de índices y optimización SQL para desarrolladores.',
                        'type' => 'GUIDE',
                    ],
                    [
                        'title' => 'MySQL 8.0 Reference: EXPLAIN Optimization',
                        'url' => 'https://dev.mysql.com/doc/refman/8.0/en/explain-output.html',
                        'description' => 'Documentación formal sobre el formato de salida y tipos de acceso de EXPLAIN.',
                        'type' => 'DOCS',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'sql-indexing-explain', 'title' => 'Estrategias de Índices & Dominio de EXPLAIN', 'minutes' => 50, 'difficulty' => 'Senior'],
                    ['slug' => 'sql-transactions-isolation', 'title' => 'Transacciones ACID & Niveles de Aislamiento', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'doctrine' => [
                'title' => 'Doctrine ORM',
                'icon' => 'database',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'doctrine'],
                'category' => 'Persistencia',
                'description' => 'Mapeo Objeto-Relacional enterprise con Doctrine ORM: arquitectura interna del Unit of Work, ciclo de vida de entidades, Identity Map en memoria, flush en lotes para batch processing y erradicación del antipatrón N+1 con DQL join fetch.',
                'video' => [
                    'title' => 'Doctrine ORM Good Practices & Internals',
                    'speaker' => 'Marco Pivetta (Ocramius)',
                    'youtube_id' => 'rzGeNYC3oz0',
                    'duration' => '52 min',
                    'description' => 'Marco Pivetta, principal mantenedor de Doctrine ORM, detalla cómo funciona el Unit of Work por dentro, cómo evitar fugas de memoria con clear() y el peligro de los setters anémicos.',
                    'key_takeaways' => [
                        'El Identity Map garantiza unicidad de referencias pero acumula memoria hasta clear().',
                        'En procesamiento masivo, flush() y clear() en lotes es vital para no agotar la RAM.',
                        'DQL JOIN FETCH resuelve colaboradores relacionados en una sola sentencia SQL.',
                        'Doctrine implementa Data Mapper, desacoplando el dominio de la estructura de tablas.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Patrón Data Mapper',
                        'source' => 'Patterns of Enterprise Application Architecture',
                        'quote' => 'Un Data Mapper es una capa que mueve datos entre objetos y una base de datos manteniéndolos independientes entre sí y del propio mapper.',
                        'author' => 'Martin Fowler',
                        'explanation' => 'Desacopla el modelo de dominio de las particularidades del motor SQL.',
                    ],
                    [
                        'topic' => 'El Antipatrón N+1',
                        'source' => 'High-Performance Java Persistence / PHP ORMs',
                        'quote' => 'El problema N+1 no es una falla del ORM; es una consecuencia del lazy loading inconsciente por no planificar el grafo de dependencias de la consulta.',
                        'author' => 'Vlad Mihalcea',
                        'explanation' => 'Un solo JOIN FETCH ahorra miles de roundtrips de red hacia la base de datos.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Doctrine ORM Documentation: Architecture & Internals',
                        'url' => 'https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/architecture.html',
                        'description' => 'Manual formal sobre el ciclo de vida del Unit of Work y Change Tracking.',
                        'type' => 'DOCS',
                    ],
                    [
                        'title' => 'Ocramius Blog: Doctrine Best Practices',
                        'url' => 'https://ocramius.github.io/',
                        'description' => 'Artículos técnicos avanzados del principal mantenedor de Doctrine sobre rendimiento.',
                        'type' => 'BLOG',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'doctrine-unit-of-work', 'title' => 'Unit of Work & Identity Map Internals', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'doctrine-n-plus-one-optimization', 'title' => 'Detección & Mitigación de Queries N+1', 'minutes' => 45, 'difficulty' => 'Senior'],
                ],
            ],
            'apis' => [
                'title' => 'APIs RESTful',
                'icon' => 'zap',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'apis'],
                'category' => 'Distribución',
                'description' => 'Diseño y arquitectura de APIs RESTful empresariales: especificación RFC 7807 (Problem Details for HTTP APIs), semántica estricta de códigos HTTP, cabeceras de idempotencia con Idempotency-Key para endpoints mutables, autenticación JWT y protección con Rate Limiting.',
                'video' => [
                    'title' => 'Problem Details for HTTP APIs (RFC 7807)',
                    'speaker' => 'Milan Jovanović',
                    'youtube_id' => 'eN4GX5WW87s',
                    'duration' => '24 min',
                    'description' => 'Milan Jovanović analiza la importancia de estandarizar respuestas de error en APIs REST utilizando RFC 7807 para eliminar formatos ad-hoc inconsistentes.',
                    'key_takeaways' => [
                        'RFC 7807 estandariza la comunicación de errores para clientes frontend y móviles.',
                        'Idempotency Keys previenen duplicación de pagos en caídas transitorias de red.',
                        'Diferenciación estricta entre 401 Unauthorized y 403 Forbidden.',
                        'Rate Limiting con token bucket en Symfony para proteger la infraestructura.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Idempotencia en APIs',
                        'source' => 'RFC 7231 Hypertext Transfer Protocol (HTTP/1.1)',
                        'quote' => 'Un método se considera idempotente si el efecto previsto sobre el servidor de múltiples peticiones idénticas es el mismo que para una sola petición.',
                        'author' => 'Roy Fielding & Julian Reschke',
                        'explanation' => 'Garantizar idempotencia en endpoints mutables como POST /charges previene cobros dobles.',
                    ],
                    [
                        'topic' => 'Detalle de Problemas RFC 7807',
                        'source' => 'RFC 7807: Problem Details for HTTP APIs',
                        'quote' => 'Este documento define una entidad "problem detail" para transportar detalles legibles por humanos y máquinas sobre errores en APIs HTTP.',
                        'author' => 'Mark Nottingham & Erik Wilde',
                        'explanation' => 'Estandariza los atributos type, title, status, detail e instance.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'IETF RFC 7807 Specification',
                        'url' => 'https://datatracker.ietf.org/doc/html/rfc7807',
                        'description' => 'Especificación oficial de Problem Details para APIs HTTP.',
                        'type' => 'SPEC',
                    ],
                    [
                        'title' => 'Symfony RateLimiter Component',
                        'url' => 'https://symfony.com/doc/current/rate_limiter.html',
                        'description' => 'Documentación del componente de limitación de tasa con algoritmos Token Bucket y Sliding Window.',
                        'type' => 'DOCS',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'apis-rest-architecture', 'title' => 'Diseño de APIs REST Nivel Enterprise', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'apis-rate-limiting-auth', 'title' => 'Rate Limiting, JWT & Idempotencia', 'minutes' => 55, 'difficulty' => 'Senior'],
                ],
            ],
            'testing' => [
                'title' => 'Testing & Calidad',
                'icon' => 'phpunit',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'testing'],
                'category' => 'Calidad & Pruebas',
                'description' => 'Estrategias de ingeniería de calidad y pruebas automatizadas: pirámide de testing, PHPUnit 11+, Test Doubles (Mocks, Stubs, Spies), pruebas de integración con KernelTestCase, pruebas funcionales con WebTestCase y TDD pragmático.',
                'video' => array (
  'title' => 'The Magic Tricks of Testing',
  'speaker' => 'Sandi Metz (Keynote Speaker)',
  'youtube_id' => 'URSWYvyc42M',
  'duration' => '32 min',
  'description' => 'Guía definitiva sobre qué probar y qué no probar: mensajes entrantes (consultas y comandos), mensajes salientes y cómo evitar tests sobre-acoplados a detalles de implementación.',
  'key_takeaways' => 
  array (
    0 => 'Prueba el estado de los mensajes que entran a tu objeto.',
    1 => 'No pruebes los mensajes privados internos que tu objeto se envía a sí mismo.',
    2 => 'Para comandos salientes con efectos secundarios, prueba que el mensaje fue enviado (mocking).',
    3 => 'Para consultas salientes sin efectos secundarios, no las pruebes (ignóralas).',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Pirámide de Pruebas',
    'source' => 'Succeeding with Agile / Fowler Bliki',
    'quote' => 'La pirámide de pruebas enfatiza tener una base amplia de tests unitarios rápidos y baratos, una capa intermedia de integración y un número reducido de tests end-to-end.',
    'author' => 'Mike Cohn & Martin Fowler',
    'explanation' => 'Tener demasiados tests E2E lentos y frágiles destruye la velocidad de entrega del equipo.',
  ),
  1 => 
  array (
    'topic' => 'TDD y Diseño',
    'source' => 'Test-Driven Development by Example',
    'quote' => 'El objetivo de TDD no es únicamente verificar que el código funciona; su mayor valor es que fuerza a diseñar interfaces desacopladas y testeables desde el primer minuto.',
    'author' => 'Kent Beck',
    'explanation' => 'Si una clase es difícil de testear, el problema no es el test; es el diseño acoplado de la clase.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'PHPUnit 11 Manual Oficial (Sebastian Bergmann)',
    'url' => 'https://docs.phpunit.de/en/11.0/',
    'description' => 'Guía completa de assertions, atributos PHP 8 para tests y mocking.',
    'type' => 'DOCS',
  ),
  1 => 
  array (
    'title' => 'Symfony Testing Guide: WebTestCase & Panther',
    'url' => 'https://symfony.com/doc/current/testing.html',
    'description' => 'Testing funcional de endpoints, clientes HTTP emulados y fixtures.',
    'type' => 'DOCS',
  ),
),
                'lessons' => [
                    ['slug' => 'testing-fundamentals-pyramid', 'title' => 'Pirámide de Testing & Estrategia de Calidad', 'minutes' => 40, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'testing-phpunit-mastery', 'title' => 'PHPUnit: Assertions, Test Doubles & Data Providers', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'testing-unit-vs-integration', 'title' => 'Unit vs Integration vs Functional Testing', 'minutes' => 45, 'difficulty' => 'Intermedio'],
                    ['slug' => 'testing-symfony-functional', 'title' => 'Symfony Testing con KernelTestCase & WebTestCase', 'minutes' => 55, 'difficulty' => 'Senior'],
                    ['slug' => 'testing-tdd-pragmatic', 'title' => 'TDD Pragmático en Symfony', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'design-patterns' => [
                'title' => 'Patrones de Diseño',
                'icon' => 'layers',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'design-patterns'],
                'category' => 'Diseño',
                'description' => 'Patrones de diseño de software aplicados a arquitecturas Symfony modernas: Strategy y Factory con Tagged Iterators y Service Locators perezosos, y Decorator y Proxy empresarial con el atributo #[AsDecorator] para resiliencia y tolerancia a fallos.',
                'video' => [
                    'title' => '10 Design Patterns Explained in 10 Minutes',
                    'speaker' => 'Fireship',
                    'youtube_id' => 'tv-_1er1mWI',
                    'duration' => '11 min',
                    'description' => 'Recorrido visual de los patrones canónicos del Gang of Four: Strategy, Decorator, Factory, Proxy, Observer y Singleton, contrastando sus casos de uso reales.',
                    'key_takeaways' => [
                        'Strategy encapsula familias de algoritmos y los hace intercambiables en runtime.',
                        'Decorator extiende funcionalidades mediante composición sin la rigidez de la herencia.',
                        'Proxy controla el acceso a un objeto para carga perezosa o control de seguridad.',
                        'El contenedor de Symfony implementa estos patrones de forma nativa con Service Tags.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Composición sobre Herencia',
                        'source' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
                        'quote' => 'Favorece la composición de objetos sobre la herencia de clases. La herencia expone los detalles internos del padre, mientras que la composición mantiene interfaces desacopladas.',
                        'author' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides (GoF)',
                        'explanation' => 'La composición permite alterar y componer comportamientos dinámicamente en tiempo de ejecución.',
                    ],
                    [
                        'topic' => 'Decoración de Servicios',
                        'source' => 'Symfony Service Container Best Practices',
                        'quote' => 'Decorar un servicio permite interceptar llamadas y agregar responsabilidades como logging o reintentos sin modificar el servicio original ni romper el código cliente.',
                        'author' => 'Nicolas Grekas',
                        'explanation' => 'El atributo #[AsDecorator] automatiza el reemplazo en el Dependency Injection Container.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Refactoring.guru: Catálogo de Patrones de Diseño en PHP',
                        'url' => 'https://refactoring.guru/es/design-patterns/php',
                        'description' => 'Explicación detallada con diagramas UML y código de producción de los 23 patrones GoF.',
                        'type' => 'REFERENCE',
                    ],
                    [
                        'title' => 'Symfony Documentation: Service Decoration',
                        'url' => 'https://symfony.com/doc/current/service_container/service_decoration.html',
                        'description' => 'Guía oficial para decorar servicios en el contenedor de Symfony con #[AsDecorator].',
                        'type' => 'DOCS',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'patterns-factory-strategy', 'title' => 'Factory & Strategy con Inyección de Symfony', 'minutes' => 45, 'difficulty' => 'Avanzado'],
                    ['slug' => 'patterns-decorator-proxy', 'title' => 'Decorator & Proxy en Servicios Enterprise', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'architecture' => [
                'title' => 'Arquitectura de Software',
                'icon' => 'cpu',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'architecture'],
                'category' => 'Arquitectura',
                'description' => 'Arquitectura de Software Empresarial: comparación formal de patrones (Monolito Modular, Capas, Hexagonal, Clean Architecture), trade-offs económicos de Microservicios vs Monolitos y Domain-Driven Design (DDD) táctico y estratégico.',
                'video' => array (
  'title' => 'DDD & Microservices: At Last, Some Boundaries!',
  'speaker' => 'Eric Evans (Creador de Domain-Driven Design)',
  'youtube_id' => 'yPvef9R3k-M',
  'duration' => '49 min',
  'description' => 'Eric Evans en GOTO Conference explicando Bounded Contexts, Context Mapping y la delimitación estratégica del modelo de dominio en sistemas distribuidos.',
  'key_takeaways' => 
  array (
    0 => 'La arquitectura y el diseño estratégico definen límites claros de contexto (Bounded Contexts).',
    1 => 'Los puertos y adaptadores protegen el dominio central de la volatilidad de infraestructura.',
    2 => 'El Monolito Modular permite la misma separación de dominios sin la complejidad de red distribuida.',
    3 => 'La regla de dependencia: las dependencias de código siempre deben apuntar hacia el modelo de negocio.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Arquitectura Hexagonal (Ports & Adapters)',
    'source' => 'Hexagonal Architecture Manifesto',
    'quote' => 'Crea tu aplicación para que pueda ejecutarse sin su interfaz de usuario o su base de datos, para que puedas ejecutar tests de regresión automatizados y cambiar componentes sin tocar el núcleo.',
    'author' => 'Alistair Cockburn',
    'explanation' => 'Permite actualizar Symfony o cambiar Doctrine por Redis o un cliente API externo sin tener que tocar la lógica de negocio.',
  ),
  1 => 
  array (
    'topic' => 'Trade-offs de Microservicios',
    'source' => 'Building Microservices (2nd Edition)',
    'quote' => 'No empieces con microservicios. Primero comprende el dominio en un monolito modular. Si no sabes dividir el código dentro de un solo proceso, los microservicios solo distribuirán tu caos.',
    'author' => 'Sam Newman',
    'explanation' => 'Los microservicios añaden complejidad de latencia de red, consistencia eventual, transacciones distribuidas y tracing distribuido.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'Martin Fowler: Microservice Trade-Offs & Prerequisites',
    'url' => 'https://martinfowler.com/articles/microservice-trade-offs.html',
    'description' => 'Matriz de costos operacionales y requisitos previos para microservicios.',
    'type' => 'ARTICLE',
  ),
  1 => 
  array (
    'title' => 'DDD Crew: Patterns & Bounded Context Canvas',
    'url' => 'https://github.com/ddd-crew',
    'description' => 'Herramientas visuales y plantillas para diseño estratégico y táctico con DDD.',
    'type' => 'REFERENCE',
  ),
),
                'lessons' => [
                    ['slug' => 'arch-patterns-comparison', 'title' => 'Monolito Modular, Capas, Hexagonal & Clean Architecture', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'arch-hexagonal-clean', 'title' => 'Arquitectura Hexagonal: Cuándo Sí y Cuándo No', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'arch-microservices-tradeoffs', 'title' => 'Microservicios vs Monolito: Matriz de Decisión y Costos', 'minutes' => 50, 'difficulty' => 'Senior'],
                    ['slug' => 'arch-pragmatic-ddd', 'title' => 'Domain-Driven Design Pragmático', 'minutes' => 65, 'difficulty' => 'Senior'],
                ],
            ],
            'system-design' => [
                'title' => 'System Design Lab',
                'icon' => 'sliders',
                'route' => 'app_system_design',
                'category' => 'Sistemas',
                'description' => 'Diseño de sistemas distribuidos y arquitecturas de alta concurrencia: patrones de cache (Cache-Aside, Write-Through), prevención de Cache Stampede, rate limiting con ventana deslizante en Redis y desacoplamiento asíncrono para escalar Symfony a 10,000 requests/segundo.',
                'video' => [
                    'title' => 'The Barebones of Distributed Systems',
                    'speaker' => 'Hussein Nasser',
                    'youtube_id' => 'uR4YjsrBj14',
                    'duration' => '28 min',
                    'description' => 'Hussein Nasser desglosa los fundamentos esenciales de los sistemas distribuidos: comunicación por red, particionamiento, consistencia, latencia y el rol crítico de las capas de cache en memoria.',
                    'key_takeaways' => [
                        'La red es inherentemente no determinista; la latencia y los fallos parciales deben ser tolerados.',
                        'El Teorema CAP impone sacrificios explícitos entre consistencia y disponibilidad.',
                        'Cache-Aside y rate limiting son la primera línea de defensa para proteger la base de datos.',
                        'El desacoplamiento asíncrono con colas absorbe picos masivos de tráfico transitorio.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Sistemas Escalables',
                        'source' => 'Designing Data-Intensive Applications',
                        'quote' => 'Un sistema escalable no es aquel que resuelve todos los problemas con más hardware, sino aquel que degrada elegantemente bajo sobrecarga protegiendo sus recursos críticos.',
                        'author' => 'Martin Kleppmann',
                        'explanation' => 'El Rate Limiting y las colas asíncronas previenen que los picos de tráfico destruyan la base de datos.',
                    ],
                    [
                        'topic' => 'El Teorema CAP',
                        'source' => 'Brewer\'s Conjecture / IEEE Computer',
                        'quote' => 'En presencia de una partición de red, debes elegir entre Consistencia (cancelar la operación) o Disponibilidad (responder con datos potencialmente desactualizados).',
                        'author' => 'Eric Brewer',
                        'explanation' => 'Comprender esta compensación guía el diseño de sistemas distribuidos tolerantes a caídas.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'The System Design Primer (Donne Martin)',
                        'url' => 'https://github.com/donnemartin/system-design-primer',
                        'description' => 'Guía de referencia abierta sobre diseño de sistemas a gran escala y alta concurrencia.',
                        'type' => 'GUIDE',
                    ],
                    [
                        'title' => 'Redis Documentation: Caching & Rate Limiting Patterns',
                        'url' => 'https://redis.io/docs/manual/patterns/',
                        'description' => 'Patrones oficiales de implementación de estructuras en memoria de alto rendimiento.',
                        'type' => 'DOCS',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'system-design-canvas', 'title' => 'Laboratorio Interactivo de Arquitecturas Distribuidas', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'system-design-high-throughput', 'title' => 'Caso: 10,000 Requests/sec en Symfony + Redis', 'minutes' => 55, 'difficulty' => 'Senior'],
                ],
            ],
            'security' => [
                'title' => 'Seguridad Defensiva',
                'icon' => 'shield',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'security'],
                'category' => 'Seguridad',
                'description' => 'Seguridad defensiva de nivel bancario: modelo de autorización granular con Security Voters, mitigación activa de OWASP Top 10 (SQL Injection, XSS, CSRF, IDOR, SSRF), autenticación stateless JWT y auditorías automatizadas de dependencias.',
                'video' => [
                    'title' => 'Symfony 5/6/7 Voters | Permission Based Access Control',
                    'speaker' => 'Gary Clarke',
                    'youtube_id' => 'cbcz0NjX4g8',
                    'duration' => '22 min',
                    'description' => 'Tutorial exhaustivo sobre la implementación de Security Voters desacoplados para autorización contextual de dominio en aplicaciones Symfony.',
                    'key_takeaways' => [
                        'Diferencia crítica entre Autenticación (¿quién eres?) y Autorización (¿qué puedes hacer?).',
                        'Cómo desacoplar las reglas de permisos del controlador moviéndolas a clases Voter independientes.',
                        'El contrato supports() y voteOnAttribute() para evaluar políticas de acceso con objetos de dominio.',
                        'Estrategias de decisión de acceso (Affirmative, Consensus, Unanimous).',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Principio de Menor Privilegio',
                        'source' => 'OWASP Top 10 Security Standard',
                        'quote' => 'El control de acceso defectuoso es la vulnerabilidad número uno en aplicaciones web. Toda entidad y endpoint debe negar el acceso por defecto.',
                        'author' => 'OWASP Foundation',
                        'explanation' => 'Un Voter bien diseñado previene brechas de Broken Object Level Authorization (BOLA/IDOR).',
                    ],
                    [
                        'topic' => 'Seguridad como Proceso Continuo',
                        'source' => 'Secrets and Lies: Digital Security in a Networked World',
                        'quote' => 'La seguridad es un proceso, no un producto. Un sistema robusto asume la posibilidad de brechas y diseña defensas en profundidad.',
                        'author' => 'Bruce Schneier',
                        'explanation' => 'Múltiples capas de validación y autorización minimizan el radio de impacto de cualquier vector de ataque.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Symfony Security Documentation: Voters',
                        'url' => 'https://symfony.com/doc/current/security/voters.html',
                        'description' => 'Guía oficial para crear y registrar voters personalizados en el contenedor de servicios.',
                        'type' => 'DOCS',
                    ],
                    [
                        'title' => 'OWASP Top 10 Application Security Risks',
                        'url' => 'https://owasp.org/www-project-top-ten/',
                        'description' => 'El estándar de la industria sobre las 10 vulnerabilidades más críticas en aplicaciones web.',
                        'type' => 'SPEC',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'security-voters-authorization', 'title' => 'Security Voters & Autorización Granular', 'minutes' => 45, 'difficulty' => 'Senior'],
                    ['slug' => 'security-owasp-mitigation', 'title' => 'Mitigación Activa de OWASP Top 10', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'performance' => [
                'title' => 'Performance & Caching',
                'icon' => 'zap',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'performance'],
                'category' => 'Optimización',
                'description' => 'Optimización y alto rendimiento: profiling de CPU y memoria con Blackfire, identificación de cuellos de botella en hot paths, arquitectura de colas asíncronas con Symfony Messenger y Redis, y estrategias de caché multinivel.',
                'video' => [
                    'title' => 'PHP profiling using Blackfire with Fabien Potencier',
                    'speaker' => 'Fabien Potencier',
                    'youtube_id' => 'xbOiPnFOdqw',
                    'duration' => '32 min',
                    'description' => 'Demostración magistral del creador de Symfony sobre cómo medir consumo de CPU, memoria, llamadas de red y E/S para optimizar aplicaciones PHP en producción.',
                    'key_takeaways' => [
                        'La diferencia fundamental entre wall time, CPU time, memoria y operaciones de I/O.',
                        'Cómo leer un Call Graph para localizar funciones que consumen el 80% de los recursos.',
                        'Optimización basada en métricas objetivas y nunca en intuiciones subjetivas.',
                        'Integración de assertions de rendimiento en suites de CI/CD.',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Optimización Basada en Métricas',
                        'source' => 'The Art of Computer Programming',
                        'quote' => 'La optimización prematura es la raíz de todos los males. Primero haz que funcione, luego hazlo correcto, y finalmente mide antes de acelerar.',
                        'author' => 'Donald Knuth',
                        'explanation' => 'Un desarrollador Senior no adivina cuellos de botella: corre un profiler y actúa con datos concretos.',
                    ],
                    [
                        'topic' => 'Arquitectura Asíncrona',
                        'source' => 'Enterprise Integration Patterns',
                        'quote' => 'Desacoplar la ejecución temporal de tareas costosas mediante mensajería asíncrona es la clave para escalar la capacidad de respuesta.',
                        'author' => 'Gregor Hohpe & Bobby Woolf',
                        'explanation' => 'Mover correos, PDFs y llamadas externas a workers de Symfony Messenger reduce la latencia HTTP a milisegundos.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Blackfire.io Documentation & Profiling Guide',
                        'url' => 'https://blackfire.io/docs',
                        'description' => 'Guía completa de profiling continuo, métricas y tests de rendimiento en PHP.',
                        'type' => 'DOCS',
                    ],
                    [
                        'title' => 'Symfony Messenger Component Documentation',
                        'url' => 'https://symfony.com/doc/current/messenger.html',
                        'description' => 'Arquitectura de buses de mensajes, transportes Redis y procesamiento concurrente.',
                        'type' => 'DOCS',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'perf-profiling-blackfire', 'title' => 'Profiling de Memoria & CPU en Symfony', 'minutes' => 50, 'difficulty' => 'Senior'],
                    ['slug' => 'perf-redis-caching-queues', 'title' => 'Symfony Messenger & Redis Queues', 'minutes' => 55, 'difficulty' => 'Senior'],
                ],
            ],
            'devops' => [
                'title' => 'DevOps & Contenedores',
                'icon' => 'docker',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'devops'],
                'category' => 'Infraestructura',
                'description' => 'Infraestructura, contenedores y automatización: Linux CLI, gestión de procesos en servidores, Dockerfiles multi-stage para producción (PHP-FPM + Nginx), pipelines de CI/CD con GitHub Actions y despliegues sin tiempo de caída.',
                'video' => array (
  'title' => 'Docker Tutorial for Beginners - Full DevOps Course',
  'speaker' => 'TechWorld with Nana',
  'youtube_id' => 'fqMOX6JJhGo',
  'duration' => '2h 00m',
  'description' => 'Curso completo de contenedores Docker: arquitectura del engine, capas de imágenes OCI, multi-stage builds para PHP y networking de producción.',
  'key_takeaways' => 
  array (
    0 => 'Multi-stage builds permiten tener herramientas de compilación en el build y no en la imagen final.',
    1 => 'PHP-FPM y Nginx deben correr con aislamiento de procesos y configuración declarativa de networking.',
    2 => 'Nunca almacenar credenciales o secretos dentro de la imagen de Docker; inyectar vía variables de entorno.',
    3 => 'Alinear los UIDs de los usuarios para evitar problemas de permisos de escritura en directorios temporales.',
  ),
),
                'citations' => array (
  0 => 
  array (
    'topic' => 'Contenedores en Producción',
    'source' => 'The Twelve-Factor App',
    'quote' => 'Las aplicaciones modernas son completamente sin estado (stateless) y se ejecutan como procesos independientes. Los datos persistentes residen en servicios respaldados (bases de datos, colas).',
    'author' => 'Adam Wiggins (Heroku Co-founder)',
    'explanation' => 'Facilita la escalabilidad horizontal y el reemplazo de contenedores defectuosos sin pérdida de datos.',
  ),
  1 => 
  array (
    'topic' => 'Pipelines de Integración Continua',
    'source' => 'Continuous Delivery: Reliable Software Releases',
    'quote' => 'Si duele, hazlo más seguido. Integrar código diariamente mediante CI automatizado previene el infierno de merges al final del ciclo de entrega.',
    'author' => 'Jez Humble & David Farley',
    'explanation' => 'Ejecutar PHPStan en nivel 8 y la suite completa de PHPUnit en cada commit garantiza que ningún bug llegue a staging.',
  ),
),
                'resources' => array (
  0 => 
  array (
    'title' => 'The Twelve-Factor App (Metodología 12-Factor)',
    'url' => 'https://12factor.net/es/',
    'description' => 'Los doce principios fundamentales para construir aplicaciones web listas para la nube.',
    'type' => 'SPEC',
  ),
  1 => 
  array (
    'title' => 'Docker Documentation: Multi-Stage Builds',
    'url' => 'https://docs.docker.com/build/building/multi-stage/',
    'description' => 'Guía técnica para optimizar el tamaño y seguridad de contenedores de producción.',
    'type' => 'DOCS',
  ),
  2 => 
  array (
    'title' => 'GitHub Actions Documentation',
    'url' => 'https://docs.github.com/es/actions',
    'description' => 'Flujos de trabajo, runners y sintaxis YAML para integración y despliegue continuo.',
    'type' => 'DOCS',
  ),
),
                'lessons' => [
                    ['slug' => 'devops-linux-cli-internals', 'title' => 'Linux CLI, Procesos, Permisos & Variables de Entorno', 'minutes' => 45, 'difficulty' => 'Fundamentos'],
                    ['slug' => 'devops-docker-fpm-nginx', 'title' => 'Docker Multi-stage para PHP-FPM & Nginx', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'devops-ci-cd-github-actions', 'title' => 'CI/CD Pipeline con GitHub Actions, PHPStan & PHPUnit', 'minutes' => 55, 'difficulty' => 'Senior'],
                ],
            ],
            'professional-developer' => [
                'title' => 'Desarrollo Profesional',
                'icon' => 'network',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'professional-developer'],
                'category' => 'Ingeniería en Producción',
                'description' => 'Excelencia técnica y cultura de ingeniería senior: comunicación técnica asertiva con producto, documentación de decisiones con Architecture Decision Records (ADRs), ingeniería de resiliencia (Circuit Breaker, Idempotencia) y gestión de incidentes con postmortems sin culpa (blameless).',
                'video' => [
                    'title' => 'Architecture Decision Records (ADR) as a LOG that answers WHY',
                    'speaker' => 'Derek Comartin (CodeOpinion)',
                    'youtube_id' => '6H6zfCNeqek',
                    'duration' => '14 min',
                    'description' => 'Cómo capturar y versionar decisiones arquitectónicas críticas junto al código fuente para mantener la trazabilidad y contexto técnico a largo plazo.',
                    'key_takeaways' => [
                        'El costo catastrófico de perder el contexto de por qué se tomó una decisión arquitectónica.',
                        'Estructura estándar de un ADR: Contexto, Decisión, Consecuencias y Estado.',
                        'Por qué los ADRs deben vivir en Git junto al código fuente y no en wikis olvidadas.',
                        'Diferencia entre decisiones reversibles (puerta de dos vías) e irreversibles (puerta de una vía).',
                    ],
                ],
                'citations' => [
                    [
                        'topic' => 'Resiliencia en Sistemas Distribuidos',
                        'source' => 'Release It! Design and Deploy Production-Ready Software',
                        'quote' => 'Los sistemas van a fallar. Diseña para la resiliencia asumiendo que cualquier servicio externo puede colapsar en cualquier segundo.',
                        'author' => 'Michael T. Nygard',
                        'explanation' => 'Implementar Circuit Breakers y límites de tiempo (timeouts) previene fallos en cascada en toda la infraestructura.',
                    ],
                    [
                        'topic' => 'Cultura de Postmortems Sin Culpa',
                        'source' => 'Site Reliability Engineering: How Google Runs Production Systems',
                        'quote' => 'No puedes cambiar la condición humana, pero puedes cambiar las condiciones bajo las cuales trabajan los humanos.',
                        'author' => 'Google SRE Team',
                        'explanation' => 'Un postmortem blameless se enfoca en reparar las causas raíz del sistema, nunca en señalar personas.',
                    ],
                ],
                'resources' => [
                    [
                        'title' => 'Documenting Architecture Decisions (ADR Standard)',
                        'url' => 'https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions',
                        'description' => 'El artículo fundacional de Michael Nygard sobre el formato y propósito de los ADRs.',
                        'type' => 'GUIDE',
                    ],
                    [
                        'title' => 'Google SRE Book: Postmortem Culture',
                        'url' => 'https://sre.google/sre-book/postmortem-culture/',
                        'description' => 'Capítulo canónico de Google sobre aprendizaje de fallos y resiliencia organizacional.',
                        'type' => 'SPEC',
                    ],
                ],
                'lessons' => [
                    ['slug' => 'prof-team-communication', 'title' => 'Trabajo en Equipo, User Stories & Estimación', 'minutes' => 40, 'difficulty' => 'Intermedio'],
                    ['slug' => 'prof-adr-technical-decisions', 'title' => 'Architecture Decision Records (ADRs) en Producción', 'minutes' => 45, 'difficulty' => 'Senior'],
                    ['slug' => 'prof-failure-engineering', 'title' => 'Failure Engineering: Circuit Breaker, Retries & Idempotencia', 'minutes' => 55, 'difficulty' => 'Senior'],
                    ['slug' => 'prof-incident-management-logs', 'title' => 'Gestión de Incidentes, Observabilidad & Postmortems', 'minutes' => 45, 'difficulty' => 'Senior'],
                ],
            ],
            'projects' => [
                'title' => 'Proyectos Guiados',
                'icon' => 'terminal',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'projects'],
                'category' => 'Práctica Profesional',
                'lessons' => [
                    ['slug' => 'project-01-senior-crud', 'title' => 'Proyecto 1: CRUD Enterprise con DTOs & Validation', 'minutes' => 120, 'difficulty' => 'Intermedio'],
                    ['slug' => 'project-07-capstone-distributed', 'title' => 'Proyecto Final: Arquitectura Modular, Docker & CI/CD', 'minutes' => 240, 'difficulty' => 'Senior'],
                ],
            ],
            'evaluations' => [
                'title' => 'Evaluaciones & Retos',
                'icon' => 'award',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'evaluations'],
                'category' => 'Evaluación',
                'lessons' => [
                    ['slug' => 'eval-senior-code-review', 'title' => 'Reto: Code Review & Detección de Smells', 'minutes' => 45, 'difficulty' => 'Senior'],
                ],
            ],
            'resources' => [
                'title' => 'Recursos & RFCs',
                'icon' => 'file-code',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'resources'],
                'category' => 'Referencia',
                'lessons' => [
                    ['slug' => 'resources-php-rfcs', 'title' => 'Guía de RFCs & Estándares PSR', 'minutes' => 30, 'difficulty' => 'Todos'],
                ],
            ],
            'settings' => [
                'title' => 'Configuración',
                'icon' => 'settings',
                'route' => 'app_settings',
                'description' => 'Ajustes del perfil del desarrollador y preferencias',
            ],
        ];
    }

    /**
     * Career progression mapping: 18 levels from Level 0 to Level 17
     * @return array<int, array<string, mixed>>
     */
    public function getCareerLevels(): array
    {
        return [
            0 => [
                'level' => 0,
                'title' => 'Development Fundamentals',
                'subtitle' => 'Fundamentos del Entorno y Línea de Comandos',
                'icon' => 'terminal',
                'modules' => ['devops'],
                'focus' => 'Sistemas operativos, terminal Linux, variables de entorno y ejecución de procesos.',
            ],
            1 => [
                'level' => 1,
                'title' => 'Software Engineering Fundamentals',
                'subtitle' => 'Ingeniería de Software & Ciclo de Vida',
                'icon' => 'layers',
                'modules' => ['software-engineering'],
                'focus' => 'SDLC, requerimientos funcionales y no funcionales, User Stories y alcance técnico.',
            ],
            2 => [
                'level' => 2,
                'title' => 'Git + Professional Workflow',
                'subtitle' => 'Control de Versiones & Colaboración',
                'icon' => 'git',
                'modules' => ['git'],
                'focus' => 'Modelo de objetos Git, ramas, rebase, Pull Requests profesionales y Conventional Commits.',
            ],
            3 => [
                'level' => 3,
                'title' => 'Programming Fundamentals',
                'subtitle' => 'Estructuras de Datos & Algoritmos Pragmáticos',
                'icon' => 'code',
                'modules' => ['php-fundamentals'],
                'focus' => 'Tipado, algoritmos, complejidad temporal y manejo de memoria en tiempo de ejecución.',
            ],
            4 => [
                'level' => 4,
                'title' => 'PHP Moderno (Core Runtime)',
                'subtitle' => 'Zend Engine, OpCache & PHP 8.4',
                'icon' => 'php',
                'modules' => ['php-fundamentals'],
                'focus' => 'Request Lifecycle, Copy-on-Write, JIT, Property Hooks y SAPI FastCGI.',
            ],
            5 => [
                'level' => 5,
                'title' => 'Object-Oriented Programming',
                'subtitle' => 'Modelado de Dominio & Encapsulación',
                'icon' => 'code',
                'modules' => ['poo'],
                'focus' => 'Protección de invariantes, Composición sobre Herencia, Value Objects y Enums seguros.',
            ],
            6 => [
                'level' => 6,
                'title' => 'SOLID + Clean Code',
                'subtitle' => 'Diseño Sostenible & Refactorización',
                'icon' => 'check-square',
                'modules' => ['software-engineering'],
                'focus' => 'SRP, OCP, LSP, ISP, DIP aplicados rigurosamente con detección de Code Smells.',
            ],
            7 => [
                'level' => 7,
                'title' => 'Design Patterns',
                'subtitle' => 'Patrones GoF en Ecosistemas Modernos',
                'icon' => 'layers',
                'modules' => ['design-patterns'],
                'focus' => 'Factory, Strategy, Decorator, Proxy y su integración nativa con Dependency Injection.',
            ],
            8 => [
                'level' => 8,
                'title' => 'Symfony Framework Internals',
                'subtitle' => 'Arquitectura del Framework Empresarial',
                'icon' => 'symfony',
                'modules' => ['symfony'],
                'focus' => 'HttpKernel, EventDispatcher, Container de Inyección compilado y Value Resolvers.',
            ],
            9 => [
                'level' => 9,
                'title' => 'Twig + Doctrine + APIs',
                'subtitle' => 'Capa de Presentación, Persistencia & Servicios',
                'icon' => 'database',
                'modules' => ['twig', 'databases', 'doctrine', 'apis'],
                'focus' => 'Unit of Work, Identity Map, índices SQL, mitigación de N+1 y APIs REST robustas.',
            ],
            10 => [
                'level' => 10,
                'title' => 'Testing & Quality Engineering',
                'subtitle' => 'Pirámide de Pruebas & TDD Pragmático',
                'icon' => 'phpunit',
                'modules' => ['testing'],
                'focus' => 'PHPUnit, KernelTestCase, WebTestCase, mocking de dependencias y pruebas de regresión.',
            ],
            11 => [
                'level' => 11,
                'title' => 'Software Architecture',
                'subtitle' => 'Arquitectura Hexagonal & Clean Architecture',
                'icon' => 'cpu',
                'modules' => ['architecture'],
                'focus' => 'Puertos y adaptadores, Regla de Dependencia, Monolitos Modulares y trade-offs de Microservicios.',
            ],
            12 => [
                'level' => 12,
                'title' => 'Domain-Driven Design (DDD)',
                'subtitle' => 'Modelado Estratégico & Táctico',
                'icon' => 'book-open',
                'modules' => ['architecture'],
                'focus' => 'Bounded Contexts, Agregados, Entidades, Repositorios de Dominio y Eventos de Dominio.',
            ],
            13 => [
                'level' => 13,
                'title' => 'System Design',
                'subtitle' => 'Arquitecturas Escalables & Resilientes',
                'icon' => 'sliders',
                'modules' => ['system-design'],
                'focus' => 'Load Balancers, Caching en capas, Colas asíncronas y 10,000 req/sec en producción.',
            ],
            14 => [
                'level' => 14,
                'title' => 'Security & Performance',
                'subtitle' => 'Seguridad Defensiva & Alta Eficiencia',
                'icon' => 'shield',
                'modules' => ['security', 'performance'],
                'focus' => 'Security Voters, OWASP Top 10, Profiling de CPU/Memoria y optimización de workers.',
            ],
            15 => [
                'level' => 15,
                'title' => 'DevOps & Contenedores',
                'subtitle' => 'Docker Multi-stage & Pipelines CI/CD',
                'icon' => 'docker',
                'modules' => ['devops'],
                'focus' => 'Nginx + PHP-FPM en contenedores, GitHub Actions, PHPStan y pipelines de despliegue.',
            ],
            16 => [
                'level' => 16,
                'title' => 'Production Engineering & Professional Developer',
                'subtitle' => 'Operación Real, Failure Engineering & ADRs',
                'icon' => 'network',
                'modules' => ['professional-developer'],
                'focus' => 'Circuit Breaker, Idempotencia, Postmortems sin culpa, Observabilidad y documentación ADR.',
            ],
            17 => [
                'level' => 17,
                'title' => 'Senior Software Engineer Capstone',
                'subtitle' => 'Liderazgo Técnico, Proyectos & Code Review',
                'icon' => 'award',
                'modules' => ['projects', 'evaluations'],
                'focus' => 'Proyectos completos de grado empresarial, revisiones arquitecturales y mentoría.',
            ],
        ];
    }

    public function getTotalLessonsCount(): int
    {
        $count = 0;
        foreach ($this->getSections() as $section) {
            if (isset($section['lessons'])) {
                $count += count($section['lessons']);
            }
        }
        return $count;
    }

    public function findLessonLocation(string $lessonSlug): ?LessonLocation
    {
        foreach ($this->getSections() as $moduleSlug => $module) {
            foreach ($module['lessons'] ?? [] as $lesson) {
                if ($lesson['slug'] === $lessonSlug) {
                    return new LessonLocation($moduleSlug, $module, $lesson);
                }
            }
        }

        return null;
    }

    /**
     * The roadmap is the single source of truth for which module owns a lesson.
     */
    public function getModuleSlugForLesson(string $lessonSlug): string
    {
        return $this->findLessonLocation($lessonSlug)?->moduleSlug
            ?? throw new \InvalidArgumentException(sprintf('Lesson "%s" is not part of the roadmap.', $lessonSlug));
    }

    /**
     * @return array<string, mixed>
     */
    public function getDashboardMetrics(User $user): array
    {
        $totalLessons = $this->getTotalLessonsCount();
        $stats = $this->progressRepository->calculateUserStats($user);

        $completed = $stats['total_completed'];
        $percentage = $totalLessons > 0 ? (int) round(($completed / $totalLessons) * 100) : 0;

        return [
            'user' => $user,
            'total_lessons' => $totalLessons,
            'completed_lessons' => $completed,
            'in_progress_lessons' => $stats['total_in_progress'],
            'completion_percentage' => $percentage,
            'total_score' => $stats['total_score'],
            'streak_days' => $user->getStreakDays(),
            'current_level' => $user->getCurrentLevel(),
            'hours_estimated' => (int) round($totalLessons * 0.75),
            'next_target' => 'Dominio del Request Lifecycle y Memoria en PHP 8.4',
        ];
    }
}
