<?php

declare(strict_types=1);

namespace App\Service;

/**
 * Curated learning paths ("rutas"): goal-oriented reorderings of the curriculum.
 *
 * The roadmap answers "what exists and what unlocks what". A path answers
 * "what do I study, in what order, to reach one concrete outcome" — with a
 * measurable exit criterion per phase so progress is not just lessons ticked.
 *
 * This class holds data only; LearningPathService turns it into progress.
 */
final class LearningPathCatalog
{
    /**
     * @return array<string, array<string, mixed>>
     */
    public function all(): array
    {
        return [
            'fundamentos-solidos' => [
                'title' => 'Fundamentos Sólidos',
                'subtitle' => 'Junior → Mid con bases que no se rompen',
                'icon' => 'book-open',
                'level' => 'Nivel 1 · Base',
                'accent' => 'var(--accent-cyan)',
                'glow' => 'linear-gradient(120deg, rgba(86,212,255,0.3), rgba(108,124,255,0.18))',
                'goal' => 'Cerrar los huecos que hacen que un desarrollador junior se estanque: ciclo de vida real de PHP, control de versiones con criterio, diseño orientado a objetos y limpieza de código sostenible.',
                'audience' => 'Escribes código que funciona pero te cuesta justificar decisiones en una revisión técnica.',
                'outcome' => 'Explicas el recorrido completo de una petición, trabajas con ramas sin miedo y defiendes tus clases con invariantes en lugar de setters.',
                'phases' => [
                    [
                        'title' => 'El runtime que ejecuta tu código',
                        'goal' => 'Dejar de tratar PHP como una caja negra: SAPI, memoria y errores.',
                        'lessons' => ['php-request-lifecycle', 'php-types-memory', 'php-namespaces-autoloading', 'php-error-handling-exceptions'],
                        'checkpoint' => 'Dibuja de memoria el camino Nginx → FPM → Zend y di dónde muere el estado.',
                    ],
                    [
                        'title' => 'Ingeniería, no improvisación',
                        'goal' => 'Requerimientos, cohesión, acoplamiento y el porqué de SOLID.',
                        'lessons' => ['se-sdlc-requirements', 'se-clean-code-quality', 'se-solid-principles'],
                        'checkpoint' => 'Reescribe una clase propia aplicando SRP y explica qué dejó de doler.',
                    ],
                    [
                        'title' => 'Git con modelo mental',
                        'goal' => 'Entender el DAG antes de memorizar comandos.',
                        'lessons' => ['git-fundamentals-plumbing', 'git-branching-strategies'],
                        'labs' => [['route' => 'app_lab_git', 'label' => 'Git & Conflict Simulator']],
                        'checkpoint' => 'Resuelve un conflicto en el simulador explicando qué commit es el ancestro común.',
                    ],
                    [
                        'title' => 'Objetos que protegen reglas',
                        'goal' => 'Encapsulación real, composición y PHP 8.4 moderno.',
                        'lessons' => ['php-modern-features-84', 'poo-encapsulation-invariants', 'poo-composition-over-inheritance', 'poo-value-objects-dtos'],
                        'checkpoint' => 'Convierte un array asociativo de tu código en un Value Object inmutable.',
                    ],
                ],
                'exit_criteria' => [
                    'Puedes explicar sin notas qué ocurre entre el TCP handshake y el primer byte de respuesta.',
                    'Ninguna de tus clases nuevas expone setters que rompan invariantes.',
                    'Haces rebase de una rama de trabajo sin consultar StackOverflow.',
                ],
            ],

            'entrevista-senior-30d' => [
                'title' => 'Entrevista Senior en 30 días',
                'subtitle' => 'Plan intensivo orientado a paneles técnicos',
                'icon' => 'award',
                'level' => 'Nivel 4 · Intensivo',
                'accent' => 'var(--accent-violet)',
                'glow' => 'linear-gradient(120deg, rgba(192,132,252,0.32), rgba(244,114,182,0.18))',
                'goal' => 'Cubrir en cuatro semanas lo que realmente se pregunta en un panel senior de PHP/Symfony: internals, diseño, datos, testing, arquitectura y comunicación de decisiones.',
                'audience' => 'Tienes experiencia pero necesitas verbalizar con precisión y sostener preguntas de segundo nivel.',
                'outcome' => 'Respondes con trade-offs explícitos, dibujas una arquitectura en pizarra y justificas cada decisión con costo, riesgo y reversibilidad.',
                'phases' => [
                    [
                        'title' => 'Semana 1 · Internals que te preguntan',
                        'goal' => 'Runtime, memoria, OpCache y el kernel de Symfony.',
                        'lessons' => ['php-request-lifecycle', 'php-types-memory', 'php-opcache-jit', 'symfony-http-kernel-lifecycle', 'symfony-service-container'],
                        'labs' => [['route' => 'app_lab_symfony_arch', 'label' => 'HttpKernel Lifecycle Lab'], ['route' => 'app_lab_di', 'label' => 'DI Container Lab']],
                        'checkpoint' => 'Explica en voz alta los 8 eventos del kernel y qué listener rompe qué.',
                    ],
                    [
                        'title' => 'Semana 2 · Datos y rendimiento',
                        'goal' => 'Índices, transacciones, Unit of Work y N+1.',
                        'lessons' => ['sql-indexing-explain', 'sql-transactions-isolation', 'doctrine-unit-of-work', 'doctrine-n-plus-one-optimization'],
                        'labs' => [['route' => 'app_lab_doctrine', 'label' => 'Unit of Work Inspector']],
                        'checkpoint' => 'Lee un EXPLAIN y di si el índice se usa, y por qué el ORM disparó 40 queries.',
                    ],
                    [
                        'title' => 'Semana 3 · Diseño y pruebas',
                        'goal' => 'SOLID aplicado, patrones, pirámide de testing y TDD.',
                        'lessons' => ['se-solid-principles', 'patterns-factory-strategy', 'testing-fundamentals-pyramid', 'testing-phpunit-mastery', 'testing-unit-vs-integration'],
                        'labs' => [['route' => 'app_lab_testing', 'label' => 'PHPUnit Interactive Lab']],
                        'checkpoint' => 'Justifica cuándo un mock es deuda técnica y cuándo es la única opción sensata.',
                    ],
                    [
                        'title' => 'Semana 4 · Arquitectura y criterio',
                        'goal' => 'Comparar arquitecturas, diseñar a escala y defender decisiones.',
                        'lessons' => ['arch-patterns-comparison', 'arch-hexagonal-clean', 'system-design-canvas', 'prof-adr-technical-decisions', 'eval-senior-code-review'],
                        'labs' => [['route' => 'app_system_design', 'label' => 'System Design Studio'], ['route' => 'app_lab_adr', 'label' => 'ADR Lab'], ['route' => 'app_lab_code_review', 'label' => 'Code Review Studio']],
                        'checkpoint' => 'Escribe un ADR de 1 página para una decisión irreversible y defiéndela.',
                    ],
                ],
                'exit_criteria' => [
                    'Sostienes 45 minutos de preguntas de internals sin respuestas memorizadas.',
                    'Diseñas un sistema de 10k req/s nombrando cuellos de botella y métricas.',
                    'Detectas al menos 6 problemas reales en un pull request ajeno.',
                ],
            ],

            'symfony-internals' => [
                'title' => 'Symfony por Dentro',
                'subtitle' => 'Del HttpKernel a la seguridad, sin magia',
                'icon' => 'symfony',
                'level' => 'Nivel 3 · Especialización',
                'accent' => 'var(--accent-primary-hi)',
                'glow' => 'linear-gradient(120deg, rgba(108,124,255,0.32), rgba(86,212,255,0.16))',
                'goal' => 'Entender el framework como una máquina de eventos y un contenedor compilado, para extenderlo en lugar de pelearse con él.',
                'audience' => 'Usas Symfony a diario pero el contenedor y los eventos siguen pareciendo magia.',
                'outcome' => 'Depuras cualquier comportamiento raro leyendo el flujo del kernel y el contenedor compilado.',
                'phases' => [
                    [
                        'title' => 'El corazón: HttpKernel',
                        'goal' => 'Request → Response como transformación con puntos de extensión.',
                        'lessons' => ['symfony-http-kernel-lifecycle', 'symfony-event-dispatcher'],
                        'labs' => [['route' => 'app_lab_symfony_arch', 'label' => 'HttpKernel Lifecycle Lab']],
                        'checkpoint' => 'Inserta mentalmente un listener en kernel.view y predice la respuesta.',
                    ],
                    [
                        'title' => 'Contenedor y autowiring',
                        'goal' => 'Compilación, passes, aliases y lazy services.',
                        'lessons' => ['symfony-service-container', 'patterns-decorator-proxy'],
                        'labs' => [['route' => 'app_lab_di', 'label' => 'DI Container Lab']],
                        'checkpoint' => 'Decora un servicio del núcleo sin tocar su código original.',
                    ],
                    [
                        'title' => 'Entrada y salida',
                        'goal' => 'Routing, value resolvers, plantillas y escapado.',
                        'lessons' => ['symfony-routing-controllers', 'twig-clean-separation', 'twig-inheritance-components', 'twig-escaping-security'],
                        'checkpoint' => 'Sustituye un json_decode manual por MapRequestPayload validado.',
                    ],
                    [
                        'title' => 'Autorización y defensa',
                        'goal' => 'Voters, jerarquía de roles y OWASP aplicado.',
                        'lessons' => ['security-voters-authorization', 'security-owasp-mitigation'],
                        'checkpoint' => 'Mueve una regla de permisos de un if en el controlador a un voter testeado.',
                    ],
                ],
                'exit_criteria' => [
                    'Explicas por qué un servicio no se autowirea leyendo debug:container.',
                    'Toda autorización vive en voters, no en condicionales dispersos.',
                ],
            ],

            'datos-rendimiento' => [
                'title' => 'Datos & Rendimiento',
                'subtitle' => 'SQL, Doctrine, profiling y escala real',
                'icon' => 'database',
                'level' => 'Nivel 3 · Especialización',
                'accent' => 'var(--accent-amber)',
                'glow' => 'linear-gradient(120deg, rgba(246,169,59,0.3), rgba(255,157,110,0.18))',
                'goal' => 'Convertir "va lento" en un diagnóstico con números: índices, transacciones, queries del ORM, perfiles de CPU y memoria, caché y colas.',
                'audience' => 'Tu aplicación funciona en local y se cae cuando llega tráfico real.',
                'outcome' => 'Encuentras el cuello de botella con evidencia y eliges la mitigación por costo, no por moda.',
                'phases' => [
                    [
                        'title' => 'La base de datos primero',
                        'goal' => 'Índices, planes de ejecución y aislamiento transaccional.',
                        'lessons' => ['sql-indexing-explain', 'sql-transactions-isolation'],
                        'checkpoint' => 'Encuentra una consulta con full scan y demuéstralo con EXPLAIN.',
                    ],
                    [
                        'title' => 'El ORM sin sorpresas',
                        'goal' => 'Unit of Work, Identity Map y el clásico N+1.',
                        'lessons' => ['doctrine-unit-of-work', 'doctrine-n-plus-one-optimization'],
                        'labs' => [['route' => 'app_lab_doctrine', 'label' => 'Unit of Work Inspector']],
                        'checkpoint' => 'Reduce un listado de 60 queries a 2 sin romper el dominio.',
                    ],
                    [
                        'title' => 'Medir antes de optimizar',
                        'goal' => 'Profiling de CPU y memoria con evidencia.',
                        'lessons' => ['php-opcache-jit', 'perf-profiling-blackfire'],
                        'checkpoint' => 'Presenta un antes/después con métricas, no con sensaciones.',
                    ],
                    [
                        'title' => 'Escala y asincronía',
                        'goal' => 'Caché multicapa, Messenger, colas y alto throughput.',
                        'lessons' => ['perf-redis-caching-queues', 'system-design-canvas', 'system-design-high-throughput'],
                        'labs' => [['route' => 'app_system_design', 'label' => 'System Design Studio']],
                        'checkpoint' => 'Diseña el camino de 500 a 10.000 req/s nombrando cada cuello de botella.',
                    ],
                ],
                'exit_criteria' => [
                    'Nunca optimizas sin un perfil que respalde el cambio.',
                    'Sabes qué invalida tu caché y qué pasa cuando la cola se atasca.',
                ],
            ],

            'arquitectura-dominio' => [
                'title' => 'Arquitectura & Dominio',
                'subtitle' => 'Hexagonal, DDD pragmático y decisiones reversibles',
                'icon' => 'layers',
                'level' => 'Nivel 4 · Senior',
                'accent' => 'var(--accent-success)',
                'glow' => 'linear-gradient(120deg, rgba(110,222,138,0.28), rgba(78,201,176,0.18))',
                'goal' => 'Elegir arquitectura por contexto y costo, no por tendencia, y modelar el dominio con lenguaje del negocio.',
                'audience' => 'Ya escribes buen código, ahora decides la forma del sistema.',
                'outcome' => 'Justificas monolito modular vs microservicios con una matriz de costos y documentas decisiones con ADRs.',
                'phases' => [
                    [
                        'title' => 'Mapa de arquitecturas',
                        'goal' => 'Capas, hexagonal, clean: qué resuelven y qué cuestan.',
                        'lessons' => ['arch-patterns-comparison', 'arch-hexagonal-clean'],
                        'checkpoint' => 'Marca la regla de dependencia violada en un proyecto real.',
                    ],
                    [
                        'title' => 'Dominio explícito',
                        'goal' => 'Enums como máquinas de estado, agregados y bounded contexts.',
                        'lessons' => ['poo-enums-state-machines', 'arch-pragmatic-ddd'],
                        'checkpoint' => 'Sustituye un string de estado por un enum con transiciones válidas.',
                    ],
                    [
                        'title' => 'Distribución consciente',
                        'goal' => 'Microservicios: latencia, consistencia y coste organizativo.',
                        'lessons' => ['arch-microservices-tradeoffs'],
                        'checkpoint' => 'Escribe las tres razones por las que NO partirías tu monolito hoy.',
                    ],
                    [
                        'title' => 'Decidir y documentar',
                        'goal' => 'ADRs, comunicación técnica y revisión entre pares.',
                        'lessons' => ['prof-team-communication', 'prof-adr-technical-decisions', 'project-07-capstone-distributed'],
                        'labs' => [['route' => 'app_lab_adr', 'label' => 'ADR Lab']],
                        'checkpoint' => 'Un ADR aprobado con alternativas descartadas y consecuencias asumidas.',
                    ],
                ],
                'exit_criteria' => [
                    'Cada decisión estructural del último mes tiene un ADR con alternativas.',
                    'Tu dominio no depende de Doctrine ni del framework para existir.',
                ],
            ],

            'calidad-tdd' => [
                'title' => 'Calidad, TDD & Refactor',
                'subtitle' => 'Pruebas que sostienen cambios agresivos',
                'icon' => 'test-tube',
                'level' => 'Nivel 2 · Práctica',
                'accent' => 'var(--accent-green)',
                'glow' => 'linear-gradient(120deg, rgba(78,201,176,0.3), rgba(110,222,138,0.16))',
                'goal' => 'Construir una red de pruebas que permita refactorizar sin miedo, con el equilibrio correcto entre unitario, integración y funcional.',
                'audience' => 'Tienes tests frágiles, lentos o inexistentes y cada cambio da miedo.',
                'outcome' => 'Refactorizas módulos completos guiado por tests rápidos y deterministas.',
                'phases' => [
                    [
                        'title' => 'Estrategia antes de escribir tests',
                        'goal' => 'Pirámide, costo de mantenimiento y qué NO testear.',
                        'lessons' => ['testing-fundamentals-pyramid', 'testing-phpunit-mastery'],
                        'labs' => [['route' => 'app_lab_testing', 'label' => 'PHPUnit Interactive Lab']],
                        'checkpoint' => 'Clasifica tus tests actuales y elimina los que solo prueban getters.',
                    ],
                    [
                        'title' => 'Fronteras y dobles',
                        'goal' => 'Unit vs integración vs funcional, y dónde poner cada doble.',
                        'lessons' => ['testing-unit-vs-integration', 'testing-symfony-functional'],
                        'checkpoint' => 'Un WebTestCase que cubre un flujo HTTP completo end-to-end.',
                    ],
                    [
                        'title' => 'TDD y limpieza continua',
                        'goal' => 'Ciclo rojo-verde-refactor sobre código real y code smells.',
                        'lessons' => ['testing-tdd-pragmatic', 'se-refactoring-code-smells', 'eval-senior-code-review'],
                        'labs' => [['route' => 'app_lab_code_review', 'label' => 'Code Review Studio']],
                        'checkpoint' => 'Refactoriza una clase de 300 líneas sin cambiar una sola aserción.',
                    ],
                ],
                'exit_criteria' => [
                    'La suite corre en segundos y falla solo cuando hay un bug real.',
                    'Refactorizas sin abrir el navegador para comprobar a mano.',
                ],
            ],

            'entrega-continua' => [
                'title' => 'Entrega & Operación',
                'subtitle' => 'Docker, CI/CD y sistemas que fallan bien',
                'icon' => 'docker',
                'level' => 'Nivel 3 · Producción',
                'accent' => 'var(--accent-orange)',
                'glow' => 'linear-gradient(120deg, rgba(255,157,110,0.28), rgba(246,169,59,0.18))',
                'goal' => 'Llevar código a producción de forma repetible y sobrevivir al día siguiente: contenedores, pipelines, resiliencia y gestión de incidentes.',
                'audience' => 'Despliegas a mano, o el pipeline es una caja negra que alguien más mantiene.',
                'outcome' => 'Tienes un pipeline que bloquea regresiones y un plan claro cuando algo se rompe a las 3 a.m.',
                'phases' => [
                    [
                        'title' => 'El sistema operativo importa',
                        'goal' => 'Procesos, permisos, señales y variables de entorno.',
                        'lessons' => ['devops-linux-cli-internals'],
                        'checkpoint' => 'Diagnostica un proceso zombi y un permiso mal puesto sin buscar en Google.',
                    ],
                    [
                        'title' => 'Empaquetar de verdad',
                        'goal' => 'Imágenes multi-stage para PHP-FPM y Nginx.',
                        'lessons' => ['devops-docker-fpm-nginx'],
                        'checkpoint' => 'Una imagen reproducible por debajo de los 150 MB.',
                    ],
                    [
                        'title' => 'Pipeline con dientes',
                        'goal' => 'GitHub Actions, análisis estático y gates de calidad.',
                        'lessons' => ['git-pr-code-review', 'git-github-collaboration', 'devops-ci-cd-github-actions'],
                        'checkpoint' => 'Un PR que no puede fusionarse si baja la cobertura o falla PHPStan.',
                    ],
                    [
                        'title' => 'Fallar bien',
                        'goal' => 'Circuit breaker, reintentos idempotentes, logs y postmortems.',
                        'lessons' => ['prof-failure-engineering', 'prof-incident-management-logs'],
                        'checkpoint' => 'Escribe un postmortem sin culpables con acciones verificables.',
                    ],
                ],
                'exit_criteria' => [
                    'Cualquier commit en main llega a producción sin pasos manuales.',
                    'Tus reintentos no duplican cobros ni corrompen estado.',
                ],
            ],
        ];
    }
}
