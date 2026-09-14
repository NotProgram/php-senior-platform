<?php

declare(strict_types=1);

return [
    'slug' => 'git-pr-code-review',
    'title' => 'Pull Requests de Alto Impacto, Code Review & Conventional Commits',
    'module' => 'Git & GitHub Profesional',
    'minutes' => 45,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'Un Pull Request profesional no es simplemente un volcado de código para pedir permiso; es un artefacto de comunicación técnica y documentación viva del sistema, estructurado con Conventional Commits para automatizar el ciclo de release y versionado semántico.',
        'problem' => 'Pull Requests titánicos de 2,000 líneas con mensajes como \'fix bug\', \'wip\' o \'cambios varios\' son imposibles de revisar con rigor, provocan \'Review Fatigue\' y terminan siendo aprobados sin leer con un \'LGTM\' que deja pasar brechas de seguridad.',
        'solution' => 'Adoptar el estándar Conventional Commits v1.0.0 (feat, fix, refactor, perf, etc.), limitar los PRs a menos de 400 líneas atómicas y aplicar rúbricas objetivas de Code Review centradas en arquitectura y mantenibilidad.',
        'problem_label' => 'El Problema: PRs Monstruo de 2,000 líneas y Mensajes \'wip\'',
        'solution_label' => 'La Solución Senior: Conventional Commits v1.0.0 y PRs Atómicos Auto-Documentados',
    ],
    'mental_model' => [
        'title' => 'El Manifiesto de Carga Aduanera vs Escribir \'Cosas varias\'',
        'analogy' => 'Imagina que eres el capitán de un buque carguero que transporta 500 contenedores hacia el puerto de Hamburgo. En el manifiesto aduanero no puedes escribir \'Cosas que empaqué hoy\' o \'Varios bultos\'. Los inspectores aduaneros retendrían el barco de inmediato. Tienes que declarar con un estándar internacional estricto: `categoría(subtipo): descripción precisa` (ej. `químicos(farmacia): vacunas de insulina a 4 grados`). En ingeniería de software, **Conventional Commits** es el manifiesto aduanero de tu repositorio. Cuando un robot de CI/CD o un colega lee `feat(auth): add OAuth2 refresh token rotation`, sabe al instante: 1) Es una funcionalidad nueva (`feat`), por lo que Semantic Versioning debe subir la versión MENOR (`1.2.0 -> 1.3.0`); 2) Afecta al módulo de autenticación; 3) El changelog para los clientes se redacta automáticamente sin intervención humana.',
        'ascii_diagram' => 'HISTORIAL JUNIOR (Caos sin sentido):
* a3f12: fix
* 81b4c: wip
* 92d01: asdfg
* 40c1e: ahora si funciona el login
(Imposible generar changelogs o saber qué cambió)

HISTORIAL SENIOR (Conventional Commits v1.0.0):
* feat(billing): add stripe webhook signature verification
* fix(auth): prevent timing attack in password hasher
* refactor(order): extract money value object from primitive
* perf(catalog): add composite index for elasticsearch queries
(100% auditable, changelogs automáticos y SemVer garantizado)',
        'key_concept' => 'Escribe tus commits pensando en el ingeniero que estará depurando una caída en producción a las 3:00 AM dentro de dos años.',
    ],
    'internals' => [
        'title' => 'Estructura Formal de Conventional Commits v1.0.0',
        'steps' => [
            [
                'phase' => '1. Los Tipos Estándar de la Especificación',
                'description' => 'feat (nueva capacidad funcional -> sube MINOR), fix (corrección de bug -> sube PATCH), refactor (cambio interno sin alterar comportamiento), perf (mejora de rendimiento), docs (documentación), test (añadir o corregir tests), chore (mantenimiento o dependencias).',
            ],
            [
                'phase' => '2. Ámbito Opcional (Scope)',
                'description' => 'Especifica la parte de la base de código impactada entre paréntesis: ej. feat(auth):, fix(checkout):, refactor(database):.',
            ],
            [
                'phase' => '3. Cambios Disruptivos (Breaking Changes)',
                'description' => 'Se señalan con un signo de exclamación \'!\' antes de los dos puntos (ej. feat(api)!: remove deprecated v1 endpoints) o con \'BREAKING CHANGE:\' en el footer del mensaje. Esto incrementa automáticamente la versión MAYOR en SemVer (1.0.0 -> 2.0.0).',
            ],
            [
                'phase' => '4. Automatización con Commitlint & Semantic Release',
                'description' => 'Un git hook (pre-commit o commit-msg) ejecuta un linter sobre el mensaje. Si no cumple la especificación, el commit es rechazado antes de crearse.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Rúbrica Profesional de Code Review: Más allá del estilo',
            'icon' => 'shield',
            'content' => 'Un Code Review senior NUNCA discute sobre espacios, comas o llaves; de eso se encargan PHP-CS-Fixer y PHPStan en el pipeline de CI.

La rúbrica humana se enfoca en preguntas arquitectónicas:
1. **Diseño:** ¿Esta solución respeta los principios SOLID? ¿Introduce dependencias circulares?
2. **Rendimiento:** ¿Hay consultas N+1 en Doctrine? ¿Qué impacto tiene en la memoria de PHP-FPM?
3. **Seguridad:** ¿Los datos del usuario se validan con Value Objects? ¿Hay riesgo de SQL Injection o XSS?
4. **Pruebas:** ¿El PR incluye tests unitarios o funcionales que cubran los casos borde de fallo?
5. **Mantenibilidad:** ¿Los nombres de métodos y variables explican claramente su propósito?',
            'takeaways' => 'Si el linter y los tests no están verdes, el Pull Request no debe ser revisado por ningún humano. Respeta el tiempo del equipo.',
        ],
        [
            'title' => 'Regla de los 400 Cambios (The 400-Line Rule)',
            'icon' => 'layers',
            'content' => 'Estudios de SmartBear sobre eficacia en revisiones de código demuestran que:
• En PRs de menos de 200 líneas, los revisores encuentran el 90% de los defectos potenciales.
• En PRs de 500 líneas, la detección de defectos cae por debajo del 45%.
• En PRs de más de 1,000 líneas, el cerebro entra en fatiga cognitiva y el revisor aprueba con un \'LGTM\' sin detectar bugs críticos.

La recomendación Senior: mantén tus Pull Requests por debajo de 300-400 líneas. Si tu tarea requiere 1,500 líneas, descompónla en 4 PRs secuenciales.',
            'takeaways' => 'PRs pequeños se revisan en 10 minutos, se fusionan rápido y reducen a cero el riesgo de regresiones masivas.',
        ],
    ],
    'video' => [
        'title' => 'So You Think You Know Git - FOSDEM 2024',
        'speaker' => 'Scott Chacon (Co-fundador de GitHub)',
        'youtube_id' => 'aolI_Rz0ZqY',
        'duration' => '40 min',
        'description' => 'Scott Chacon presenta técnicas avanzadas de Git en FOSDEM: git rebase interactivo, rerere, git maintenance y flujos de revisión de Pull Requests.',
        'chapters' => [
            '00:00' => 'Novedades y comandos modernos de Git',
            '11:20' => 'Git rebase -i --autosquash y fixups atómicos',
            '22:40' => 'Resolución de conflictos recordada: git rerere',
            '33:15' => 'Mejores prácticas para Pull Requests de alta calidad',
        ],
    ],
    'architecture_code' => [
        'filename' => 'ConventionalCommitValidator.php',
        'title' => 'Validador de Mensajes de Commit según la Especificación v1.0.0',
        'tag' => 'Commitlint Architecture Pattern',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Validador automatizado de la especificación Conventional Commits v1.0.0.
 * Puede ser invocado por hooks de git (commit-msg) o pipelines de CI.
 */
final readonly class ConventionalCommitValidator
{
    private const array VALID_TYPES = [
        \'feat\',     // Nueva funcionalidad (SemVer MINOR)
        \'fix\',      // Corrección de bug (SemVer PATCH)
        \'docs\',     // Documentación
        \'style\',    // Formateo o estilo sin cambio de lógica
        \'refactor\', // Refactorización interna de código
        \'perf\',     // Optimización de rendimiento
        \'test\',     // Adición o corrección de pruebas
        \'build\',    // Sistema de compilación o dependencias externas
        \'ci\',       // Integración continua o pipelines
        \'chore\',    // Tareas rutinarias de mantenimiento
        \'revert\',   // Reversión de un commit previo
    ];

    /**
     * Determina si el mensaje cumple con la sintaxis: <tipo>[ámbito opcional][!]: <descripción>
     */
    public function isValid(string $commitMessage): bool
    {
        $trimmed = trim($commitMessage);
        if ($trimmed === \'\') {
            return false;
        }

        // Expresión regular canónica según la especificación formal
        $typesRegex = implode(\'|\', self::VALID_TYPES);
        $pattern = "/^({$typesRegex})(\\([a-zA-Z0-9_\\-\\.\\/]+\\))?(!)?: .+/s";

        return (bool) preg_match($pattern, $trimmed);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior ve el Pull Request como su tarjeta de presentación profesional ante el equipo. Escribe una descripción detallada que explica el \'por qué\' del cambio, adjunta métricas de rendimiento y enlaces a User Stories, y se asegura de que sus commits cuenten una historia coherente y comprensible.',
        'critical_questions' => [
            '¿Este Pull Request es lo suficientemente pequeño como para ser revisado a fondo en 15 minutos?',
            '¿El mensaje del commit explica claramente el contexto del cambio o solo describe la sintaxis?',
            '¿Este cambio introduce una ruptura de compatibilidad (Breaking Change) que deba alertarse con \'!\'?',
            '¿Dejé comentarios en mi propio PR explicando las decisiones de diseño no obvias para facilitar la vida a mis revisores?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Abrir un Pull Request tras implementar la integración con un nuevo proveedor de SMS.',
        'junior' => [
            'approach' => 'Abre un PR titulado \'sms\', sin descripción, con 18 commits (\'fix\', \'probando\', \'revert\', \'wip\'), 1,200 líneas cambiadas mezclando la integración de SMS con cambios no relacionados de formateo de CSS.',
            'flaws' => [
                'El revisor tarda 3 días en empezar a mirar el PR por frustración. Se mezclan cambios de CSS que rompen la vista móvil y pasan desapercibidos.',
            ],
        ],
        'senior' => [
            'approach' => 'Limpia su historial con git rebase interactivo. Abre un PR de 280 líneas titulado \'feat(notification): integrate Twilio SMS gateway\'. Incluye resumen del contexto, capturas de logs de prueba y enlaces al ticket de Jira.',
            'rationale' => [
                'Aprobado y desplegado en menos de 2 horas. Cero fricción en el equipo y changelog generado automáticamente.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Especificación Conventional Commits',
            'source' => 'Conventional Commits Specification v1.0.0',
            'quote' => 'La especificación Conventional Commits es una convención ligera sobre los mensajes de commit que proporciona un conjunto fácil de reglas para crear un historial de commits explícito.',
            'author' => 'Conventional Commits Working Group',
            'explanation' => 'Estructurar los commits permite que las herramientas de CI/CD decidan automáticamente la versión semántica (SemVer) y generen releases sin errores manuales.',
        ],
        [
            'topic' => 'Eficacia en Code Reviews',
            'source' => 'Best Practices for Code Review (Cisco Systems Study)',
            'quote' => 'Revisar más de 400 líneas de código a la vez sobrecarga la atención humana. La tasa de descubrimiento de defectos cae en picada después de los primeros 60 minutos.',
            'author' => 'SmartBear Software & Cisco Systems',
            'explanation' => 'La granularidad y el tamaño reducido de los Pull Requests son el factor más determinante para la calidad del software en equipos de ingeniería.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Conventional Commits v1.0.0 Specification',
            'url' => 'https://www.conventionalcommits.org/es/v1.0.0/',
            'description' => 'Especificación oficial completa en español sobre los tipos, scopes y breaking changes.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Google Engineering Practices: Code Review Developer Guide',
            'url' => 'https://google.github.io/eng-practices/review/',
            'description' => 'La guía interna de Google sobre cómo realizar revisiones de código de alto estándar.',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Validador de Mensajes Conventional Commits',
        'objective' => 'Implementar la clase ConventionalCommitValidator con el método isValid() para certificar que un mensaje de commit cumpla con el estándar Conventional Commits v1.0.0.',
        'instructions' => 'Crea la clase ConventionalCommitValidator en el namespace App\\Engineering. Implementa el método público isValid(string $commitMessage): bool. El mensaje debe comenzar con uno de los tipos reconocidos: feat, fix, docs, style, refactor, perf, test, build, ci, chore o revert. Opcionalmente puede tener un ámbito entre paréntesis como feat(auth) y opcionalmente un signo de exclamación \'!\' para breaking changes, seguido obligatoriamente de dos puntos, un espacio y la descripción: \'<tipo>[ámbito opcional][!]: <descripción>\'.',
        'filename' => 'ConventionalCommitValidator.php',
        'guide' => [
            'explanation' => 'En este reto vas a construir un linter de commits similar a Commitlint. Tu validador analizará la sintaxis del mensaje para certificar que respeta el estándar de la industria. Si el mensaje es \'feat(api): add endpoint\', es válido; si es \'added new endpoint\' o \'wip\', debe ser rechazado con false.',
            'steps' => [
                'Paso 1: Declara <code>class ConventionalCommitValidator</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: Implementa <code>public function isValid(string $commitMessage): bool</code>.',
                'Paso 3: Limpia espacios en blanco iniciales y finales con <code>trim($commitMessage)</code>; si queda vacío, retorna <code>false</code>.',
                'Paso 4: Define la expresión regular para validar el formato: tipo (feat|fix|...), scope opcional con paréntesis <code>(\\([a-zA-Z0-9_\\-\\.\\/]+\\))?</code>, exclamación opcional <code>(!)?</code>, seguido de dos puntos y espacio <code>: .+</code>.',
                'Paso 5: Ejecuta <code>preg_match()</code> y retorna el resultado booleano.',
            ],
            'useful_functions' => [
                [
                    'name' => 'preg_match($pattern, $subject)',
                    'desc' => 'Evalúa una expresión regular sobre una cadena y retorna 1 si coincide, 0 si no coincide o false si hay error sintáctico.',
                ],
                [
                    'name' => 'trim(string $string)',
                    'desc' => 'Elimina espacios en blanco, tabulaciones y saltos de línea al principio y final de la cadena.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Estructura de la Expresión Regular',
                'text' => 'La expresión regular debe comenzar con el ancla de inicio \'^\', agrupar los tipos permitidos con \'|\', permitir el scope opcional y exigir \': \' seguido de texto.',
                'snippet' => '$pattern = \'/^(feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert)(\\([a-zA-Z0-9_\\-\\.\\/]+\\))?(!)?: .+/\';',
            ],
            [
                'label' => 'Manejo de Casos Vacíos',
                'text' => 'Comprueba primero si la cadena está vacía tras aplicar trim() para evitar evaluaciones innecesarias.',
                'snippet' => 'if (trim($commitMessage) === \'\') {
    return false;
}',
            ],
            [
                'label' => 'Conversión a Booleano',
                'text' => 'preg_match retorna 1 si encuentra coincidencia. Puedes forzarlo a booleano con (bool) preg_match(...).',
                'snippet' => 'return (bool) preg_match($pattern, trim($commitMessage));',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Validador de la especificación Conventional Commits v1.0.0.
 */
class ConventionalCommitValidator
{
    /**
     * @param string $commitMessage Mensaje de commit a evaluar.
     * @return bool True si cumple con la especificación; false de lo contrario.
     */
    public function isValid(string $commitMessage): bool
    {
        // PASO 1: Descarta mensajes vacíos
        // PASO 2: Evalúa el mensaje con la expresión regular de Conventional Commits
        // (Debe soportar tipos como feat, fix, docs, refactor, etc.)
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior de ConventionalCommitValidator.
 */
class ConventionalCommitValidator
{
    public function isValid(string $commitMessage): bool
    {
        $trimmed = trim($commitMessage);
        if ($trimmed === \'\') {
            return false;
        }

        $pattern = \'/^(feat|fix|docs|style|refactor|perf|test|build|ci|chore|revert)(\\([a-zA-Z0-9_\\-\\.\\/]+\\))?(!)?: .+/\';

        return (bool) preg_match($pattern, $trimmed);
    }
}
',
        'explanation' => 'La solución aplica una expresión regular rigurosa sobre el estándar Conventional Commits v1.0.0. Valida que el mensaje comience con uno de los prefijos semánticos admitidos, soporte scopes contextuales opcionales y marcadores de breaking changes (\'!\'), garantizando la automatización segura de Semantic Release.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Conventional Commits y Code Review',
        'questions' => [
            [
                'id' => 'q1',
                'question' => 'En la especificación Conventional Commits, ¿qué impacto tiene el uso del prefijo \'feat:\' en la versión semántica (SemVer)?',
                'options' => [
                    'a' => 'Incrementa la versión PATCH (ej. de 1.0.1 a 1.0.2).',
                    'b' => 'Incrementa la versión MINOR (ej. de 1.1.0 a 1.2.0) porque introduce una nueva funcionalidad compatible hacia atrás.',
                    'c' => 'Incrementa la versión MAJOR obligatoriamente.',
                    'd' => 'No tiene ningún efecto en el versionado.',
                ],
                'correct' => 'b',
                'explanation' => 'feat corresponde a una nueva capacidad compatible con versiones anteriores, lo que incrementa el dígito MINOR en SemVer (vX.Y.Z -> incrementa Y).',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cómo se señala formalmente un cambio disruptivo (Breaking Change) en la primera línea de un Conventional Commit?',
                'options' => [
                    'a' => 'Escribiendo \'URGENTE\' en mayúsculas.',
                    'b' => 'Colocando un signo de exclamación \'!\' justo antes de los dos puntos (ej. \'feat(api)!: remove deprecated endpoints\').',
                    'c' => 'Usando un emoji de fuego.',
                    'd' => 'Cambiando la extensión del archivo a .bak.',
                ],
                'correct' => 'b',
                'explanation' => 'El símbolo \'!\' inmediatamente antes de los dos puntos alerta a herramientas y humanos de que el commit introduce un cambio incompatible que requiere elevar la versión MAJOR.',
            ],
            [
                'id' => 'q3',
                'question' => 'Según las investigaciones empíricas sobre calidad en Code Review (Cisco Systems / SmartBear), ¿cuál es el límite óptimo de líneas de código por Pull Request para mantener una alta efectividad en la detección de defectos?',
                'options' => [
                    'a' => 'Más de 5,000 líneas para revisar todo de una sola vez.',
                    'b' => 'Menos de 300-400 líneas de código, ya que a partir de ese umbral la atención del revisor decae drásticamente.',
                    'c' => 'Exactamente 1 línea de código por commit.',
                    'd' => 'No existe límite; mientras más código se revise junto, mejor.',
                ],
                'correct' => 'b',
                'explanation' => 'A partir de 400 líneas, la fatiga cognitiva reduce la tasa de detección de bugs a la mitad. Los PRs pequeños y atómicos son la mejor garantía de calidad.',
            ],
        ],
    ],
];
