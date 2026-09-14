<?php

declare(strict_types=1);

return [
    'slug' => 'git-github-collaboration',
    'title' => 'Gobernanza en GitHub: Branch Protection, Forks & CI Status Checks',
    'module' => 'Git & GitHub Profesional',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'La gobernanza de repositorios en plataformas empresariales (GitHub Enterprise, GitLab) establece barreras arquitectónicas automatizadas que impiden que código defectuoso, vulnerable o sin revisar llegue a la rama principal de producción.',
        'problem' => 'En equipos sin gobernanza, cualquier desarrollador puede hacer `git push origin main --force`, sobreescribir la historia de producción, saltarse los tests automatizados y desplegar errores catastróficos un viernes por la tarde.',
        'solution' => 'Configurar Branch Protection Rules con Status Checks de CI/CD obligatorios, revisión obligatoria de Code Owners (mínimo de aprobaciones) y deshabilitación estricta de force pushes y borrado de ramas principales.',
        'problem_label' => 'El Problema: Force Pushes destructivos y Código roto en main',
        'solution_label' => 'La Solución Senior: Branch Protection Rules y Verificación Automatizada de CI',
    ],
    'mental_model' => [
        'title' => 'El Sistema de Dos Llaves del Submarino Nuclear y el Chequeo de Vuelo',
        'analogy' => 'Imagina el sistema de lanzamiento de un submarino nuclear. Ningún oficial, sin importar su rango, puede presionar el botón rojo por sí solo. Se requiere: 1) Que la computadora de a bordo ejecute un diagnóstico automatizado completo verificando presión, coordenadas y estado de los reactores (**Status Checks de CI/CD**). 2) Que dos oficiales independientes inserten y giren sus llaves simultáneamente a más de 3 metros de distancia (**Revisiones aprobadas obligatorias de Code Owners**). Las **Branch Protection Rules** de GitHub son el sistema de dos llaves de tu empresa: nadie puede empujar código directamente a `main`. Cada cambio debe someterse a la inspección de los robots (PHPStan, PHPUnit, Security Checker) y recibir el visto bueno explícito de al menos uno o dos ingenieros antes de que el botón de merge se desbloquee.',
        'ascii_diagram' => 'DESARROLLADOR: Abre Pull Request con nuevo código
                        │
                        ▼
┌─────────────────────────────────────────────────────────┐
│     BARRERA DE PROTECCIÓN DE RAMA (BRANCH PROTECTION)   │
├────────────────────────────┬────────────────────────────┤
│ 1. ROBOTS (Status Checks)  │ 2. HUMANOS (Code Owners)   │
│ • PHPStan Nivel 9:  [PASS] │ • Revisor 1:  [APROBADO]   │
│ • PHPUnit Tests:    [PASS] │ • Revisor 2:  [APROBADO]   │
│ • Security Audit:   [PASS] │ (Mínimo de approvals OK)   │
└────────────────────────────┴────────────────────────────┘
                        │
                        ▼ (Todas las barreras superadas)
     [BOTÓN MERGE DESBLOQUEADO] ──> Fusión segura a main',
        'key_concept' => 'La confianza en un equipo de ingeniería no se basa en esperar que nadie cometa errores; se basa en crear sistemas automatizados donde cometer un error catastrófico sea físicamente imposible.',
    ],
    'internals' => [
        'title' => 'Los 5 Pilares de la Gobernanza en GitHub Enterprise',
        'steps' => [
            [
                'phase' => '1. Require a Pull Request Before Merging',
                'description' => 'Deshabilita por completo la capacidad de hacer git push directo a la rama main o release, obligando a que cualquier cambio pase por un Pull Request formal.',
            ],
            [
                'phase' => '2. Require Status Checks to Pass Before Merging',
                'description' => 'Exige que los pipelines de GitHub Actions (tests unitarios, análisis estático con PHPStan, auditoría de dependencias con composer audit) terminen con éxito (código 0) antes de habilitar el botón de merge.',
            ],
            [
                'phase' => '3. Require Approvals & Code Owners (CODEOWNERS)',
                'description' => 'Define un número mínimo de revisiones aprobadas (ej. 1 o 2). Mediante el archivo .github/CODEOWNERS, se asignan equipos responsables obligatorios según la ruta del archivo (ej. /config/security* -> @security-team).',
            ],
            [
                'phase' => '4. Dismiss Stale Approvals on New Commits',
                'description' => 'Si un revisor aprobó un PR y el autor empuja un nuevo commit con cambios adicionales, la aprobación previa se invalida automáticamente, requiriendo una nueva revisión.',
            ],
            [
                'phase' => '5. Restrict Who Can Push & Enforce Linear History',
                'description' => 'Bloquea los \'force pushes\' (--force), prohíbe el borrado de la rama y exige que el merge se resuelva linealmente sin merge commits desordenados.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Archivo CODEOWNERS en Repositorios Grandes',
            'icon' => 'shield',
            'content' => 'En un monolito empresarial donde trabajan 50 ingenieros, el archivo `.github/CODEOWNERS` es el guardián de la arquitectura:

```text
# Cualquier archivo no especificado requiere aprobación general
* @backend-leads

# La configuración de seguridad requiere al equipo de Ciberseguridad
/config/packages/security.yaml @security-team

# Las migraciones de base de datos requieren al equipo de Datos/DBA
/migrations/ @database-admins

# La infraestructura y Dockerfiles requieren a DevOps
/Dockerfile @devops-team
/.github/workflows/ @devops-team
```

Cuando un desarrollador toca una migración, GitHub solicita automáticamente la revisión de `@database-admins` y bloquea el merge hasta que dicho equipo apruebe.',
            'takeaways' => 'CODEOWNERS previene que cambios en infraestructura crítica o seguridad pasen desapercibidos en revisiones casuales.',
        ],
        [
            'title' => 'Flujo de Colaboración con Forks (Open Source & Multi-Vendor)',
            'icon' => 'layers',
            'content' => 'Cuando colaboras en proyectos de código abierto o con contratistas externos a los que no quieres dar acceso de escritura directo:
1. El desarrollador hace un **Fork** (copia del repositorio en su cuenta personal).
2. Clona su fork localmente y configura el remoto original como `upstream`: `git remote add upstream <url_original>`.
3. Crea su rama, hace sus commits y los sube a su fork (`origin`).
4. Abre un Pull Request desde `usuario:feature` hacia `organizacion:main`.
5. Los administradores revisan y fusionan el PR sin haber otorgado jamás permisos de escritura en el repositorio corporativo.',
            'takeaways' => 'El flujo de Forks proporciona aislamiento de seguridad total: los contratistas externos pueden proponer código sin tener permisos de alteración directa.',
        ],
    ],
    'video' => [
        'title' => 'Git & GitHub for Professionals: Forks, PRs & CI/CD',
        'speaker' => 'freeCodeCamp / Beau Carnes',
        'youtube_id' => 'RGOj5yH7evk',
        'duration' => '68 min',
        'description' => 'Guía exhaustiva sobre colaboración en GitHub: flujos de trabajo con remotes múltiples, forks, protección de ramas y automatización de status checks.',
        'chapters' => [
            '00:00' => 'Configuración de llaves SSH y remotes upstream',
            '17:30' => 'Flujo de trabajo de Forks y sincronización continua',
            '36:00' => 'Branch Protection Rules y requerimientos de revisión',
            '52:45' => 'CI/CD checks obligatorios y merge queues',
        ],
    ],
    'architecture_code' => [
        'filename' => 'BranchProtectionPolicyValidator.php',
        'title' => 'Motor de Evaluación de Políticas de Protección de Rama en PHP 8.4',
        'tag' => 'Governance Engine Pattern',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Motor de validación de políticas de protección de rama (Branch Protection).
 * Simula las reglas de gobernanza que GitHub ejecuta antes de habilitar
 * la fusión de un Pull Request.
 */
final readonly class BranchProtectionPolicyValidator
{
    /**
     * Evalúa si un Pull Request cumple con las reglas de gobernanza para ser fusionado.
     *
     * @param array{require_ci: bool, min_approvals: int} $policy Reglas de la rama.
     * @param array{ci_passed: bool, approvals_count: int, is_draft: bool} $prState Estado actual del PR.
     * @return array{can_merge: bool, violations: list<string>}
     */
    public function evaluate(array $policy, array $prState): array
    {
        $violations = [];

        // 1. Un PR en borrador (Draft) nunca puede ser fusionado
        if (!empty($prState[\'is_draft\'])) {
            $violations[] = \'El Pull Request está marcado como Borrador (Draft).\';
        }

        // 2. Verificación de Status Checks de CI/CD obligatorios
        $requireCi = $policy[\'require_ci\'] ?? false;
        if ($requireCi && empty($prState[\'ci_passed\'])) {
            $violations[] = \'Los chequeos obligatorios de CI/CD no han concluido con éxito.\';
        }

        // 3. Verificación del número mínimo de revisiones aprobadas
        $requiredApprovals = $policy[\'min_approvals\'] ?? 1;
        $currentApprovals = $prState[\'approvals_count\'] ?? 0;
        if ($currentApprovals < $requiredApprovals) {
            $violations[] = sprintf(
                \'Revisiones insuficientes: se requieren al menos %d aprobaciones (actuales: %d).\',
                $requiredApprovals,
                $currentApprovals
            );
        }

        return [
            \'can_merge\' => empty($violations),
            \'violations\' => $violations,
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior configura el repositorio para que los humanos no tengan que ser policías. Si una regla de calidad o seguridad es importante, la convierte en un check obligatorio de CI y en una política de branch protection. Sabe que las políticas automatizadas reducen la fricción interpersonal y elevan la calidad base del equipo.',
        'critical_questions' => [
            '¿La rama main está protegida contra force pushes y commits directos?',
            '¿Qué pasa si un PR pasa los tests unitarios pero no la auditoría de seguridad de paquetes? ¿El check es bloqueante?',
            '¿Tenemos configurado CODEOWNERS para alertar al equipo de seguridad cuando se editan configuraciones sensibles?',
            '¿Las aprobaciones previas se descartan automáticamente cuando el autor sube nuevos commits?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Establecer la gobernanza de código en un equipo de 12 desarrolladores trabajando en un sistema crítico de pagos.',
        'junior' => [
            'approach' => 'Pide a todos por el chat de Slack: \'Por favor no hagan push a main sin avisar y corran los tests en su máquina local antes de subir\'.',
            'flaws' => [
                'Dos semanas después, un desarrollador con prisa hace git push directo con un test roto, se despliega a producción y se cae la pasarela de pagos durante una campaña publicitaria.',
            ],
        ],
        'senior' => [
            'approach' => 'Configura Branch Protection en GitHub: 1) Push directo prohibido; 2) Mínimo 2 aprobaciones de Code Owners; 3) Checks de GitHub Actions (PHPStan nivel 9 + PHPUnit) obligatorios; 4) \'Dismiss stale approvals\' habilitado.',
            'rationale' => [
                'Imposibilidad matemática de que código sin verificar entre a main. Cero estrés en el equipo y auditoría total respaldada por la plataforma.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Gobernanza Automatizada',
            'source' => 'Continuous Delivery: Reliable Software Releases through Build, Test, and Deployment Automation',
            'quote' => 'La única forma de garantizar la calidad de forma sostenible es hacer que el camino correcto sea el único camino posible.',
            'author' => 'Jez Humble & David Farley',
            'explanation' => 'Las políticas automatizadas en el repositorio eliminan el error humano y garantizan que los estándares se cumplan de forma uniforme sin excepciones.',
        ],
        [
            'topic' => 'Seguridad en la Cadena de Suministro',
            'source' => 'OpenSSF: Best Practices for Open Source Security',
            'quote' => 'Proteger las ramas principales contra escrituras no autorizadas y requerir firmas criptográficas y revisiones independientes es la primera línea de defensa contra ataques a la cadena de suministro.',
            'author' => 'Open Source Security Foundation (OpenSSF)',
            'explanation' => 'Las Branch Protection Rules evitan la inyección de código malicioso tanto interno como de dependencias comprometidas.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'GitHub Docs: Managing a Branch Protection Rule',
            'url' => 'https://docs.github.com/es/repositories/configuring-branches-and-merges-in-your-repository/managing-protected-branches/managing-a-branch-protection-rule',
            'description' => 'Documentación oficial de GitHub sobre la configuración de ramas protegidas y status checks.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'GitHub Docs: About Code Owners',
            'url' => 'https://docs.github.com/es/repositories/managing-your-repositorys-settings-and-features/customizing-your-repository/about-code-owners',
            'description' => 'Cómo definir el archivo CODEOWNERS para asignar revisiones automáticas por dominio técnico.',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Motor de Evaluación de Políticas de Branch Protection',
        'objective' => 'Implementar la clase BranchProtectionPolicyValidator con el método evaluate() para certificar si un Pull Request cumple con las reglas de gobernanza antes de ser fusionado.',
        'instructions' => 'Crea la clase BranchProtectionPolicyValidator dentro del namespace App\\Engineering. Implementa el método público evaluate(array $policy, array $prState): array. El array $policy contiene las claves: \'require_ci\' (bool) y \'min_approvals\' (int). El array $prState contiene: \'ci_passed\' (bool), \'approvals_count\' (int) e \'is_draft\' (bool). El método debe verificar: 1) Si is_draft es true, registrar violación; 2) Si require_ci es true y ci_passed es false, registrar violación; 3) Si approvals_count es menor que min_approvals, registrar violación. Debe retornar un array con la estructura: [\'can_merge\' => bool, \'violations\' => list<string>], donde can_merge es true solo si no hay ninguna violación.',
        'filename' => 'BranchProtectionPolicyValidator.php',
        'guide' => [
            'explanation' => 'En este reto vas a simular el motor de gobernanza que GitHub ejecuta en sus servidores. Tu clase recibirá las reglas de la rama (por ejemplo: requerir CI y al menos 2 aprobaciones) y el estado del PR. Si el PR es un borrador, no tiene CI verde o le faltan aprobaciones, devolverás las violaciones exactas y can_merge = false.',
            'steps' => [
                'Paso 1: Declara <code>class BranchProtectionPolicyValidator</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: Implementa <code>public function evaluate(array $policy, array $prState): array</code>.',
                'Paso 3: Inicializa un array de violaciones: <code>$violations = [];</code>.',
                'Paso 4: Si <code>!empty($prState[\'is_draft\'])</code>, agrega una violación a <code>$violations</code>.',
                'Paso 5: Si <code>!empty($policy[\'require_ci\']) && empty($prState[\'ci_passed\'])</code>, agrega una violación de CI a <code>$violations</code>.',
                'Paso 6: Si <code>($prState[\'approvals_count\'] ?? 0) < ($policy[\'min_approvals\'] ?? 1)</code>, agrega una violación de aprobaciones.',
                'Paso 7: Retorna <code>[\'can_merge\' => empty($violations), \'violations\' => $violations]</code>.',
            ],
            'useful_functions' => [
                [
                    'name' => 'empty($var)',
                    'desc' => 'Comprueba si una variable está vacía o no definida de forma segura sin emitir warnings.',
                ],
                [
                    'name' => 'sprintf(string $format, ...$values)',
                    'desc' => 'Construye cadenas formateadas con marcadores de posición %d o %s.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Manejo de Violaciones',
                'text' => 'Cada regla no cumplida debe agregar una descripción textual al array $violations.',
                'snippet' => 'if (!empty($prState[\'is_draft\'])) {
    $violations[] = \'El PR es un borrador.\';
}',
            ],
            [
                'label' => 'Evaluación de CI y Aprobaciones',
                'text' => 'Evalúa require_ci comparando con ci_passed, y min_approvals comparando con approvals_count.',
                'snippet' => 'if (!empty($policy[\'require_ci\']) && empty($prState[\'ci_passed\'])) {
    $violations[] = \'CI fallido.\';
}
if (($prState[\'approvals_count\'] ?? 0) < ($policy[\'min_approvals\'] ?? 1)) {
    $violations[] = \'Faltan aprobaciones.\';
}',
            ],
            [
                'label' => 'Estructura de Retorno',
                'text' => 'can_merge es simplemente empty($violations), lo que significa que no se encontró ninguna regla rota.',
                'snippet' => 'return [
    \'can_merge\' => empty($violations),
    \'violations\' => $violations,
];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Motor de validación de Branch Protection en GitHub.
 */
class BranchProtectionPolicyValidator
{
    /**
     * @param array{require_ci: bool, min_approvals: int} $policy
     * @param array{ci_passed: bool, approvals_count: int, is_draft: bool} $prState
     * @return array{can_merge: bool, violations: list<string>}
     */
    public function evaluate(array $policy, array $prState): array
    {
        // PASO 1: Inicializa $violations = []
        // PASO 2: Verifica si el PR es un borrador (is_draft)
        // PASO 3: Verifica si se requiere CI (require_ci) y ci_passed es false
        // PASO 4: Verifica si las aprobaciones (approvals_count) son menores a min_approvals
        // PASO 5: Retorna [\'can_merge\' => empty($violations), \'violations\' => $violations]
        return [\'can_merge\' => false, \'violations\' => []];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior de BranchProtectionPolicyValidator.
 */
class BranchProtectionPolicyValidator
{
    public function evaluate(array $policy, array $prState): array
    {
        $violations = [];

        if (!empty($prState[\'is_draft\'])) {
            $violations[] = \'El Pull Request está marcado como Borrador (Draft).\';
        }

        $requireCi = $policy[\'require_ci\'] ?? false;
        if ($requireCi && empty($prState[\'ci_passed\'])) {
            $violations[] = \'Los chequeos de CI/CD obligatorios no han finalizado con éxito.\';
        }

        $minApprovals = $policy[\'min_approvals\'] ?? 1;
        $currentApprovals = $prState[\'approvals_count\'] ?? 0;
        if ($currentApprovals < $minApprovals) {
            $violations[] = sprintf(
                \'Revisiones insuficientes: se requieren al menos %d aprobaciones (actuales: %d).\',
                $minApprovals,
                $currentApprovals
            );
        }

        return [
            \'can_merge\' => empty($violations),
            \'violations\' => $violations,
        ];
    }
}
',
        'explanation' => 'La solución implementa la lógica de gobernanza de GitHub de forma determinista y exhaustiva: valida el estado de borrador, la ejecución satisfactoria del pipeline de CI/CD y el quórum de revisiones de Code Owners antes de otorgar el permiso de fusión a la rama protegida.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Gobernanza y Protección de Ramas',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la función del ajuste \'Dismiss stale pull request approvals when new commits are pushed\' en las Branch Protection Rules de GitHub?',
                'options' => [
                    'a' => 'Borra el repositorio si alguien sube un commit no autorizado.',
                    'b' => 'Invalida automáticamente las aprobaciones previas de los revisores si el autor del PR añade nuevos commits, exigiendo una nueva revisión de los cambios recién introducidos.',
                    'c' => 'Convierte los commits automáticamente en archivos ZIP.',
                    'd' => 'Envía un mensaje de texto a los administradores del servidor.',
                ],
                'correct' => 'b',
                'explanation' => 'Evita que un desarrollador obtenga aprobación para un cambio inofensivo y luego añada código malicioso o defectuoso en un commit posterior antes de hacer merge.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Para qué sirve el archivo .github/CODEOWNERS en un repositorio?',
                'options' => [
                    'a' => 'Para definir las contraseñas de los usuarios de MySQL.',
                    'b' => 'Para definir automáticamente qué ingenieros o equipos son responsables obligatorios de revisar Pull Requests que modifiquen rutas o archivos específicos.',
                    'c' => 'Para configurar los estilos CSS del portal de documentación.',
                    'd' => 'Para calcular el salario de los desarrolladores según sus líneas de código.',
                ],
                'correct' => 'b',
                'explanation' => 'CODEOWNERS automatiza la asignación de revisores expertos según las rutas de archivos afectadas (ej. seguridad, base de datos, infraestructura).',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por qué las políticas de Branch Protection deben deshabilitar estrictamente los \'force pushes\' (--force) en la rama main?',
                'options' => [
                    'a' => 'Porque los force pushes consumen más ancho de banda que un push normal.',
                    'b' => 'Porque un force push reescribe el grafo de la rama en el servidor remoto, permitiendo que un desarrollador borre o sobreescriba accidentalmente semanas de trabajo de sus compañeros.',
                    'c' => 'Porque GitHub cobra una tarifa adicional por cada force push.',
                    'd' => 'Porque desactiva temporalmente el protocolo HTTPS.',
                ],
                'correct' => 'b',
                'explanation' => 'El force push altera destructivamente los punteros del repositorio remoto. Deshabilitarlo en ramas protegidas protege la integridad histórica del proyecto.',
            ],
        ],
    ],
];
