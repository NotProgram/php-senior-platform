# Senior PHP & Symfony DevLab Platform

Plataforma formativa de alto nivel diseñada para llevar a desarrolladores desde fundamentos rigurosos hasta el estándar de **Senior Software Engineer / Software Architect** en el ecosistema **PHP 8.4** y **Symfony 8.1**.

Construida con arquitectura realista de grado empresarial, editor interactivo en navegador **Monaco Code Editor**, validación de sintaxis en tiempo real, evaluaciones conceptuales profundas y diseño visual oscuro inspirado en **VS Code Dark+**.

---

## Principios de Diseño & Restricciones

- **Zero-Emoji Policy Estricta**: No se utilizan emojis en ninguna interfaz, lección, quiz o componente. Todos los elementos gráficos utilizan iconos vectoriales SVG oficiales de alta precisión integrados en `templates/components/icons.html.twig`.
- **Tema Visual VS Code Dark+**: Paleta corporativa de ingeniería basada en `#1e1e1e` (fondo de editor), `#252526` (barras laterales), `#2d2d2d` (tarjetas), `#007acc` (azul de acento), `#89d185` (éxito/aprobado) y `#f14c4c` (error/alerta).
- **Plantilla Canónica de 14 Puntos**: Cada una de las 41 lecciones cumple estrictamente una estructura de 14 puntos pedagógicos y técnicos.
- **Validación Pragmática y Ejecutable**: Cada reto de código cuenta con evaluadores sintácticos y semánticos automatizados en `ExerciseEvaluatorService`, y los quizzes técnicos evalúan escenarios reales de producción.

---

## Stack Tecnológico

| Capa | Tecnología | Detalle de Implementación |
|---|---|---|
| **Lenguaje Core** | PHP 8.4 | Strict types, Property Hooks, Enums, Readonly Classes, First-Class Callables, JIT |
| **Framework** | Symfony 8.1 | HttpKernel Lifecycle, Dependency Injection, EventDispatcher, Routing |
| **Persistencia** | Doctrine ORM 3.7 & DBAL | MySQL 8.0, Transacciones ACID, Identity Map, Unit of Work, PostgreSQL/SQLite compat |
| **Plantillas** | Twig 3.x | Herencia jerárquica, macro de iconos SVG, auto-escaping contextual |
| **Editor de Código** | Monaco Editor | El mismo motor de Visual Studio Code integrado en navegador con tema `vs-dark` |
| **Linter API** | Endpoint `/api/lint` | Linter en tiempo real de PHP con reporte de errores de sintaxis y línea |
| **Servidor Local** | PHP-S / PHP-FPM | Servidor local `127.0.0.1:8000` y compatible con Docker Nginx + FPM |

---

## Matriz Curricular Completa (17 Módulos · 41 Lecciones)

### Nivel 1: PHP Moderno (Lenguaje Core)
1. `php-request-lifecycle` - Request Lifecycle & Web Servers (35 min, Fundamentos)
2. `php-types-memory` - Tipado Estricto & Gestión de Memoria (45 min, Intermedio)
3. `php-opcache-jit` - OpCache, Preloading & JIT Compiler (40 min, Avanzado)
4. `php-namespaces-autoloading` - PSR-4, Namespaces & Composer Internals (30 min, Fundamentos)
5. `php-error-handling-exceptions` - Manejo Robusto de Errores & Excepciones (40 min, Intermedio)
6. `php-modern-features-84` - PHP 8.4: Property Hooks & Asymmetric Visibility (50 min, Senior)

### Nivel 2: POO & Modelado (Paradigma)
7. `poo-encapsulation-invariants` - Encapsulación & Protección de Invariantes (40 min, Intermedio)
8. `poo-composition-over-inheritance` - Composition over Inheritance en la Práctica (45 min, Avanzado)
9. `poo-value-objects-dtos` - Value Objects vs DTOs vs Entidades (45 min, Senior)
10. `poo-enums-state-machines` - PHP Enums como Máquinas de Estado Seguras (35 min, Avanzado)

### Nivel 3: Symfony Framework
11. `symfony-http-kernel-lifecycle` - HttpKernel: El Ciclo de Vida Real (60 min, Senior)
12. `symfony-service-container` - DI Container & Compiler Passes (55 min, Senior)
13. `symfony-event-dispatcher` - Event Dispatcher & Subscriptions (40 min, Avanzado)
14. `symfony-routing-controllers` - Routing, Argument Resolvers & Value Resolvers (45 min, Intermedio)

### Nivel 4: Twig Template Engine
15. `twig-clean-separation` - Separación Estricta de Lógica de Presentación (35 min, Intermedio)
16. `twig-inheritance-components` - Herencia Jerárquica & Componentes Reutilizables (40 min, Intermedio)
17. `twig-escaping-security` - Auto-escaping, Contextos Seguros & XSS Defense (45 min, Senior)

### Nivel 5: Bases de Datos & SQL
18. `sql-indexing-explain` - Estrategias de Índices & Dominio de EXPLAIN (50 min, Senior)
19. `sql-transactions-isolation` - Transacciones ACID & Niveles de Aislamiento (50 min, Senior)

### Nivel 6: Doctrine ORM
20. `doctrine-unit-of-work` - Unit of Work & Identity Map Internals (60 min, Senior)
21. `doctrine-n-plus-one-optimization` - Detección & Mitigación de Queries N+1 (45 min, Senior)

### Nivel 7: APIs RESTful
22. `apis-rest-architecture` - Diseño de APIs REST Nivel Enterprise (50 min, Avanzado)
23. `apis-rate-limiting-auth` - Rate Limiting, JWT & Idempotencia (55 min, Senior)

### Nivel 8: Testing & TDD
24. `testing-unit-vs-integration` - Unit vs Integration vs Functional Testing (45 min, Intermedio)
25. `testing-tdd-pragmatic` - TDD Pragmático en Symfony (50 min, Senior)

### Nivel 9: Patrones de Diseño
26. `patterns-factory-strategy` - Factory & Strategy con Inyección de Symfony (45 min, Avanzado)
27. `patterns-decorator-proxy` - Decorator & Proxy en Servicios Enterprise (50 min, Senior)

### Nivel 10: Arquitectura de Software
28. `arch-hexagonal-clean` - Arquitectura Hexagonal: Cuándo Sí y Cuándo No (60 min, Senior)
29. `arch-pragmatic-ddd` - Domain-Driven Design Pragmático (65 min, Senior)

### Nivel 11: System Design Lab
30. `system-design-canvas` - Laboratorio Interactivo de Arquitecturas Distribuidas (60 min, Senior)
31. `system-design-high-throughput` - Caso: 10,000 Requests/sec en Symfony + Redis (55 min, Senior)

### Nivel 12: Seguridad Defensiva
32. `security-voters-authorization` - Security Voters & Autorización Granular (45 min, Senior)
33. `security-owasp-mitigation` - Mitigación Activa de OWASP Top 10 (50 min, Senior)

### Nivel 13: Performance & Caching
34. `perf-profiling-blackfire` - Profiling de Memoria & CPU en Symfony (50 min, Senior)
35. `perf-redis-caching-queues` - Symfony Messenger & Redis Queues (55 min, Senior)

### Nivel 14: DevOps & Contenedores
36. `devops-docker-fpm-nginx` - Docker Multi-stage para PHP-FPM & Nginx (50 min, Avanzado)
37. `devops-ci-cd-github-actions` - CI/CD Pipeline con PHPStan & PHPUnit (45 min, Senior)

### Nivel 15: Proyectos Guiados
38. `project-01-senior-crud` - Proyecto 1: CRUD Enterprise con DTOs & Validation (120 min, Intermedio)
39. `project-07-capstone-distributed` - Proyecto Final: Arquitectura Modular & Caching (240 min, Senior)

### Nivel 16: Evaluaciones & Retos
40. `eval-senior-code-review` - Reto: Code Review & Detección de Smells (45 min, Senior)

### Nivel 17: Recursos & RFCs
41. `resources-php-rfcs` - Guía de RFCs & Estándares PSR (30 min, Todos)

---

## Anatomía de una Lección (Plantilla de 14 Puntos)

Cada lección incluye:
1. **Identificadores Core**: `slug`, `title`, `module`, `minutes`, `difficulty`.
2. **Overview Profundo**: `concept`, `problem`, `problem_label`, `solution`, `solution_label`.
3. **Internals**: Fases técnicas detalladas (`title`, `steps` estructurados con `phase` y `description`).
4. **Architecture Code Box**: Implementación realista (`filename`, `title`, `tag`, `code`).
5. **Senior Mindset**: `thought_process` y `critical_questions` (preguntas clave de producción).
6. **Junior vs Senior**: Caso de estudio comparativo (`problem_statement`, `junior` con `approach` y `flaws`, `senior` con `approach` y `rationale`, `trade_offs`).
7. **Laboratorio Monaco Code Editor**: `title`, `objective`, `instructions`, `filename`, `starter_code`, `solution_code`, `explanation`.
8. **Quiz Técnico Interactivo**: `title`, 3+ preguntas de opción múltiple con respuestas justificadas.

---

## Ejecución Local

### Con DDEV (recomendado)
El repositorio incluye `.ddev/config.yaml` (PHP 8.4, nginx-fpm, MySQL 8.0):

```bash
ddev start
ddev composer install
ddev exec bin/console doctrine:migrations:migrate -n
ddev launch
```

### Sin DDEV
- PHP 8.4+ con extensiones `pdo_mysql`, `pdo_sqlite` (tests), `mbstring`, `tokenizer`, `xml`, `ctype`, `iconv`.
- Composer 2.x y MySQL 8.0.

```bash
composer install

# Configurar la base de datos en .env.local
DATABASE_URL="mysql://root:root@127.0.0.1:3306/php_senior_platform?serverVersion=8.0.32&charset=utf8mb4"

php bin/console doctrine:database:create --if-not-exists
php bin/console doctrine:migrations:migrate -n
symfony serve -d   # o bien: php -S 127.0.0.1:8000 -t public
```

### Tests
La suite usa una base SQLite desechable (definida en `.env.test`), así que no necesita MySQL:

```bash
ddev exec bin/phpunit   # o bien: php bin/phpunit
```

- `tests/Unit`: servicios y entidades, sin kernel.
- `tests/Integration/CurriculumIntegrityTest.php`: comprueba que roadmap, archivos de contenido, quizzes y grafo de prerrequisitos sean coherentes (toda lección tiene contenido y es desbloqueable, toda respuesta correcta está entre las opciones).
- `tests/Functional`: flujo HTTP real (todas las páginas y lecciones, completar lección, quiz, reto de código, CSRF, reinicio de progreso, API de lint).

---

## Despliegue en la Nube

Para estudiar desde cualquier dispositivo o red, la plataforma se puede desplegar tanto en **Vercel** (Serverless) como en **Railway / Render** (Contenedor Docker persistente).

### Opción 1: Despliegue en Vercel (Configuración ya incluida)

El repositorio incluye `vercel.json` y `api/index.php` preconfigurados para ejecutar el runtime de PHP en modo serverless.

1. **Base de Datos Remota**:
   Como Vercel es una plataforma serverless sin MySQL local, crea una base de datos MySQL gratuita en:
   - [TiDB Serverless](https://tidbcloud.com/) (5 GB gratis, 100% compatible con MySQL)
   - [Aiven for MySQL](https://aiven.io/mysql) (Tier gratuito de pruebas)
   - [Railway MySQL](https://railway.app/) (Tier gratuito con MySQL nativo)

2. **Subir a GitHub**:
   ```bash
   git init
   git add .
   git commit -m "feat: complete senior php learning platform"
   git remote add origin https://github.com/TU-USUARIO/php-senior-platform.git
   git push -u origin main
   ```

3. **Importar en Vercel**:
   - Entra a [vercel.com](https://vercel.com/) e inicia sesión con tu cuenta de GitHub.
   - Haz clic en **"Add New..."** -> **"Project"** e importa tu repositorio `php-senior-platform`.
   - En la sección **Environment Variables**, agrega:
     - `APP_ENV` = `prod`
     - `APP_SECRET` = `(un string aleatorio de 32 caracteres)`
     - `DATABASE_URL` = `mysql://usuario:password@tu-host-remoto:3306/tu_bd?sslmode=require`
   - Haz clic en **Deploy**.

4. **Despliegue directo por CLI (Alternativa)**:
   ```bash
   npx vercel
   ```
   Sigue las instrucciones en consola para autenticarte y confirmar el despliegue.

### Opción 2: Despliegue en Railway o Render (Recomendado para Symfony + MySQL)
Si prefieres un servidor persistente con base de datos MySQL en un solo clic:
- **Railway**: Permite desplegar Symfony y un contenedor MySQL vinculado con un clic.
- **Render**: Permite crear un Web Service conectando tu repositorio GitHub.

---

## Estructura de Directorios

```
php-senior-platform/
├── api/
│   └── index.php                 # Bridge para Serverless de Vercel
├── config/                       # Configuración de bundles y servicios Symfony
├── content/lessons/              # Una lección por archivo: <slug>.php devuelve su array de contenido
├── migrations/                   # Migraciones de base de datos Doctrine
├── public/                       # Raíz pública web (FastCGI Front Controller y assets)
│   ├── css/vscode.css            # Hoja de estilos VS Code Dark+
│   └── index.php                 # Punto de entrada HTTP estándar de Symfony
├── src/
│   ├── Controller/               # DashboardController, LessonController, LintController, etc.
│   ├── DTO/                      # Data Transfer Objects inmutables
│   ├── Entity/                   # Entidades Doctrine (User, UserProgress, ExerciseAttempt, etc.)
│   ├── Enum/                     # Enums de estado (ProgressStatus)
│   ├── Repository/               # Repositorios Doctrine de consulta
│   └── Service/                  # Servicios de negocio (LessonContentService, Evaluators, etc.)
├── templates/                    # Plantillas Twig (base, dashboard, lesson, roadmap, icons)
├── tests/                        # Unit, Integration (integridad del currículo) y Functional (HTTP)
├── vercel.json                   # Configuración de funciones y rutas para Vercel
└── README.md                     # Documentación general de la plataforma
```