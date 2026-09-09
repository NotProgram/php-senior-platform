<?php

declare(strict_types=1);

namespace App\Service;

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
            'php-fundamentals' => [
                'title' => 'PHP Moderno',
                'icon' => 'php',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'php-fundamentals'],
                'category' => 'Lenguaje Core',
                'lessons' => [
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
                'lessons' => [
                    ['slug' => 'apis-rest-architecture', 'title' => 'Diseño de APIs REST Nivel Enterprise', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'apis-rate-limiting-auth', 'title' => 'Rate Limiting, JWT & Idempotencia', 'minutes' => 55, 'difficulty' => 'Senior'],
                ],
            ],
            'testing' => [
                'title' => 'Testing & TDD',
                'icon' => 'phpunit',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'testing'],
                'category' => 'Calidad',
                'lessons' => [
                    ['slug' => 'testing-unit-vs-integration', 'title' => 'Unit vs Integration vs Functional Testing', 'minutes' => 45, 'difficulty' => 'Intermedio'],
                    ['slug' => 'testing-tdd-pragmatic', 'title' => 'TDD Pragmático en Symfony', 'minutes' => 50, 'difficulty' => 'Senior'],
                ],
            ],
            'design-patterns' => [
                'title' => 'Patrones de Diseño',
                'icon' => 'layers',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'design-patterns'],
                'category' => 'Diseño',
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
                'lessons' => [
                    ['slug' => 'arch-hexagonal-clean', 'title' => 'Arquitectura Hexagonal: Cuándo Sí y Cuándo No', 'minutes' => 60, 'difficulty' => 'Senior'],
                    ['slug' => 'arch-pragmatic-ddd', 'title' => 'Domain-Driven Design Pragmático', 'minutes' => 65, 'difficulty' => 'Senior'],
                ],
            ],
            'system-design' => [
                'title' => 'System Design Lab',
                'icon' => 'sliders',
                'route' => 'app_system_design',
                'category' => 'Sistemas',
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
                'lessons' => [
                    ['slug' => 'devops-docker-fpm-nginx', 'title' => 'Docker Multi-stage para PHP-FPM & Nginx', 'minutes' => 50, 'difficulty' => 'Avanzado'],
                    ['slug' => 'devops-ci-cd-github-actions', 'title' => 'CI/CD Pipeline con PHPStan & PHPUnit', 'minutes' => 45, 'difficulty' => 'Senior'],
                ],
            ],
            'projects' => [
                'title' => 'Proyectos Guiados',
                'icon' => 'terminal',
                'route' => 'app_module',
                'route_params' => ['moduleSlug' => 'projects'],
                'category' => 'Práctica',
                'lessons' => [
                    ['slug' => 'project-01-senior-crud', 'title' => 'Proyecto 1: CRUD Enterprise con DTOs & Validation', 'minutes' => 120, 'difficulty' => 'Intermedio'],
                    ['slug' => 'project-07-capstone-distributed', 'title' => 'Proyecto Final: Arquitectura Modular & Caching', 'minutes' => 240, 'difficulty' => 'Senior'],
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
