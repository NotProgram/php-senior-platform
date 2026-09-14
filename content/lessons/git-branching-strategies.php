<?php

declare(strict_types=1);

return [
    'slug' => 'git-branching-strategies',
    'title' => 'Estrategias de Ramas: Trunk-Based vs GitFlow & Rebase',
    'module' => 'Git & GitHub Profesional',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Las estrategias de ramificación definen cómo un equipo de ingenieros colabora concurrentemente en un repositorio sin bloquearse, determinando el flujo de entrega continua y la higiene del historial.',
        'problem' => 'El uso de ramas de larga vida (GitFlow tradicional con branches de meses) genera el infierno de merges (\'Merge Hell\'): semanas de resolución manual de conflictos, branches incompatibles y miedo crónico a desplegar a producción.',
        'solution' => 'Adoptar Trunk-Based Development con ramas de vida corta (< 24 horas), integración continua respaldada por Feature Flags y mantenimiento de un historial lineal y limpio mediante Git Rebase interactivo.',
        'problem_label' => 'El Problema: El \'Merge Hell\' de ramas gigantescas de 3 semanas',
        'solution_label' => 'La Solución Senior: Trunk-Based Development, Fast-Forward y Rebase Lineal',
    ],
    'mental_model' => [
        'title' => 'La Vía de Tren Única vs El Desvío de Ferrocarril (Fast-Forward vs 3-Way Merge)',
        'analogy' => 'Imagina una vía de tren principal (`main`). Cuando creas una rama de feature, es como construir un desvío lateral temporal. Si mientras trabajabas en tu desvío **nadie colocó ningún vagón nuevo en la vía principal**, para integrar tu trabajo solo necesitas correr la señal del tren hasta el final de tu desvío: eso es un **Fast-Forward Merge** (un avance lineal instantáneo sin cruces ni choques). Pero si mientras trabajabas, otro colega empujó dos vagones nuevos a la vía principal, tus líneas se bifurcaron. Ahora tienes dos opciones de ingeniería: 1) **3-Way Merge Commit**: Crear un vagón de empalme con dos entradas que une ambas vías, dejando un historial en forma de trenza. 2) **Rebase**: Levantar tus vagones del desvío y re-anclarlos cuidadosamente en la punta del último vagón que puso tu colega en la vía principal, manteniendo la vía como una sola línea recta y perfecta.',
        'ascii_diagram' => 'ESCENARIO 1: FAST-FORWARD MERGE (Línea directa)
main:     A ─── B
                 └─── C ─── D (feature)
Al fusionar: main avanza su puntero a D (cero conflicto):
main:     A ─── B ─── C ─── D

ESCENARIO 2: RAMAS DIVERGENTES (Bifurcación)
main:     A ─── B ─── E (alguien hizo push aquí)
                 └─── C ─── D (tu rama feature)

OPCIÓN MERGE COMMIT:                       OPCIÓN REBASE LINEAL:
A ─── B ─── E ───────── M (Merge Commit)   A ─── B ─── E ─── C\' ─── D\' (main)
       └─── C ─── D ───┘                   (Historial plano sin nudos)',
        'key_concept' => 'Un Fast-Forward solo es matemáticamente posible si el commit destino es un ancestro directo alcanzable en el grafo del commit origen.',
    ],
    'internals' => [
        'title' => 'Topología de Grafos: Análisis de Ancestros Comunes',
        'steps' => [
            [
                'phase' => '1. El Ancestro Común Más Cercano (Merge Base)',
                'description' => 'Para fusionar dos ramas, Git busca el ancestro común más reciente mediante \'git merge-base main feature\'. Si el merge base coincide con el commit de main, el Fast-Forward es factible.',
            ],
            [
                'phase' => '2. Fast-Forward vs No-Fast-Forward (--no-ff)',
                'description' => 'Fast-Forward simplemente avanza el puntero de la rama. Con \'--no-ff\', Git fuerza la creación de un commit de fusión explícito para documentar la existencia de la rama en el historial.',
            ],
            [
                'phase' => '3. Anatomía del Git Rebase',
                'description' => 'Rebase no mueve commits mágicamente: toma los diffs de tus commits locales, los almacena en parches temporales, reinicia tu rama en el commit destino (HEAD de main) y re-aplica cada commit uno por uno generando nuevos hashes SHA-1.',
            ],
            [
                'phase' => '4. La Regla de Oro del Rebase',
                'description' => 'NUNCA hagas rebase sobre una rama pública compartida por otros desarrolladores (como main). Solo haz rebase en tus ramas locales privadas antes de abrir un Pull Request.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Trunk-Based Development vs GitFlow',
            'icon' => 'layers',
            'content' => 'El reporte DORA (DevOps Research and Assessment) de Google concluyó que los equipos de mayor rendimiento del mundo usan **Trunk-Based Development**:

• **GitFlow (Tradicional):** Ramas `master`, `develop`, `release/*`, `feature/*`, `hotfix/*`. Las ramas duran semanas o meses. Requiere ceremonias de release complejas y merges de decenas de archivos conflictivos.
• **Trunk-Based (Moderno):** Todos los desarrolladores integran directamente en `main` (trunk) al menos una vez al día en ramas de vida corta (< 24 horas). Las funcionalidades no terminadas se ocultan tras **Feature Flags** (toggles de configuración) para no romper producción.

Beneficios de Trunk-Based: merges de 5 minutos, resolución de conflictos microscópica y entrega continua real.',
            'takeaways' => 'Las ramas que duran más de 3 días son un olor arquitectónico de falta de granularidad en las historias de usuario.',
        ],
        [
            'title' => 'Resolución de Conflictos Automática con git rerere',
            'icon' => 'shield',
            'content' => 'Git incluye una herramienta oculta llamada **rerere** (Reuse Recorded Resolution):
Al activarla con `git config --global rerere.enabled true`, Git recuerda exactamente cómo resolviste un conflicto en un archivo.

Si estás haciendo rebases continuos sobre una rama larga y el mismo conflicto aparece 10 veces, Git aplicará automáticamente tu resolución previa sin que tengas que volver a editar el archivo manualmente.',
            'takeaways' => 'Habilita \'git rerere\' en tu máquina local para eliminar la fricción repetitiva en flujos de rebase interactivo.',
        ],
    ],
    'video' => [
        'title' => 'Git MERGE vs REBASE: The Definitive Guide',
        'speaker' => 'Fireship',
        'youtube_id' => 'CRlGDDprdOQ',
        'duration' => '8 min',
        'description' => 'Explicación gráfica y técnica de las diferencias estructurales entre merge commits y rebase lineal, con casos de uso en Trunk-Based Development.',
        'chapters' => [
            '00:00' => 'El árbol de Git y punteros de ramas',
            '02:15' => 'Fast-Forward vs Merge Commits de 3 vías',
            '04:30' => 'Git Rebase: Reescribiendo el punto de anclaje',
            '06:45' => 'La regla de oro: Cuándo NUNCA hacer rebase',
        ],
    ],
    'architecture_code' => [
        'filename' => 'BranchMergeAnalyzer.php',
        'title' => 'Analizador de Grafos para Fast-Forward Merges en PHP 8.4',
        'tag' => 'Graph Algorithm Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Analizador topológico del Grafo Acíclico Dirigido (DAG) de Git.
 * Determina si una operación de fusión puede resolverse como Fast-Forward lineal
 * verificando si el commit destino es un ancestro alcanzable del commit origen.
 */
final readonly class BranchMergeAnalyzer
{
    /**
     * Determina si es posible realizar un Fast-Forward merge.
     *
     * @param string $targetCommit Hash del commit en la rama destino (ej. main).
     * @param string $sourceCommit Hash del commit en la rama origen (ej. feature).
     * @param array<string, list<string>> $commitGraph Mapa commit => lista de hashes de sus padres.
     * @return bool True si el targetCommit es ancestro de sourceCommit; false si divergieron.
     */
    public function canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool
    {
        // Caso base: si ambos apuntan al mismo commit, ya están sincronizados
        if ($targetCommit === $sourceCommit) {
            return true;
        }

        // Búsqueda en Anchura (BFS) sobre el grafo de parents
        $queue = [$sourceCommit];
        $visited = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if ($current === $targetCommit) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $parents = $commitGraph[$current] ?? [];
            foreach ($parents as $parent) {
                if (!isset($visited[$parent])) {
                    $queue[] = $parent;
                }
            }
        }

        return false;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior prioriza la velocidad de entrega del equipo sobre la complejidad de procesos. Si el equipo sufre con ramas de semanas, aboga por Trunk-Based Development: descompone las tareas en PRs de menos de 300 líneas, usa Feature Flags para código en progreso y rebase interactivo local para mantener un historial bisectable.',
        'critical_questions' => [
            '¿Esta rama de feature tiene más de 2 días abierta? ¿Cómo puedo integrar una parte hoy mismo tras un Feature Flag?',
            '¿Hacer rebase aquí alterará la historia de ramas que mis compañeros ya clonaron?',
            '¿Un Fast-Forward mantendrá nuestro historial limpio y bisectable con \'git bisect\'?',
            '¿Qué estrategia de merge (Squash & Merge, Rebase & Merge, Merge Commit) conviene a la gobernanza de este repositorio?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Integrar una funcionalidad que tomó 2 semanas de desarrollo a la rama principal \'main\'.',
        'junior' => [
            'approach' => 'Abre un Pull Request con 80 commits desordenados (\'fix\', \'asdf\', \'test\', \'typo\'), 4,500 líneas cambiadas y 15 conflictos de merge con main. Ejecuta un merge commit a ciegas sin probar.',
            'flaws' => [
                'El commit de merge rompe la build de main. Al tener 80 commits caóticos, es imposible usar \'git bisect\' para encontrar cuál línea introdujo el bug.',
            ],
        ],
        'senior' => [
            'approach' => 'Hace rebase interactivo local (`git rebase -i main`), hace squash de los commits temporales dejando 2 o 3 commits atómicos con mensajes convencionales, resuelve conflictos localmente y abre un PR limpio de 250 líneas.',
            'rationale' => [
                'Revisión de código en 15 minutos, cero conflictos en el servidor y un historial impecable que facilita auditorías y rollbacks.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Higiene del Historial',
            'source' => 'Git Documentation: Rebasing & Merging Best Practices',
            'quote' => 'El historial de tu proyecto es un registro de lo que realmente ocurrió, pero también es una guía para que otros ingenieros comprendan por qué se construyó así. Mantenlo limpio.',
            'author' => 'Scott Chacon',
            'explanation' => 'Un historial lineal y atómico permite que herramientas automatizadas generen changelogs y bisecten errores sin fricción.',
        ],
        [
            'topic' => 'Frecuencia de Integración',
            'source' => 'Accelerate: The Science of Lean Software and DevOps',
            'quote' => 'Los equipos que integran código diariamente en Trunk-Based Development tienen tasas de fallos en producción significativamente más bajas y tiempos de recuperación 100 veces más rápidos.',
            'author' => 'Nicole Forsgren, Jez Humble & Gene Kim',
            'explanation' => 'La integración continua real requiere ramas microscópicas; ramas de larga vida destruyen la agilidad organizacional.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Trunk-Based Development: Official Guide',
            'url' => 'https://trunkbaseddevelopment.com/',
            'description' => 'Sitio canónico sobre la metodología de ramas de vida corta adoptada por Google, Meta y Netflix.',
            'type' => 'GUIDE',
        ],
        [
            'title' => 'Atlassian: Merging vs Rebasing Tutorial',
            'url' => 'https://www.atlassian.com/es/git/tutorials/merging-vs-rebasing',
            'description' => 'Comparativa visual detallada con diagramas de flujo de Rebase y Merge.',
            'type' => 'TUTORIAL',
        ],
    ],
    'exercise' => [
        'title' => 'Analizador de Fast-Forward Merges en Grafos de Git',
        'objective' => 'Implementar la clase BranchMergeAnalyzer con el método canFastForward() para determinar mediante recorrido de grafos si una fusión puede resolverse sin commit de merge.',
        'instructions' => 'Crea la clase BranchMergeAnalyzer en el namespace App\\Engineering. Implementa el método público canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool. El método recibe el commit de destino (targetCommit), el commit de origen (sourceCommit) y un array asociativo (commitGraph) donde cada clave es un hash de commit y su valor es una lista de hashes de sus commits padres. Retorna true si targetCommit es alcanzable navegando hacia atrás por los padres de sourceCommit (o si son idénticos); de lo contrario retorna false.',
        'filename' => 'BranchMergeAnalyzer.php',
        'guide' => [
            'explanation' => 'En este reto vas a implementar el algoritmo fundamental que Git ejecuta cuando decides hacer un merge. Si la rama destino (ej. main) no ha tenido commits nuevos desde que creaste tu feature, targetCommit es un ancestro de sourceCommit y la fusión puede resolverse instantáneamente como Fast-Forward.',
            'steps' => [
                'Paso 1: Declara <code>class BranchMergeAnalyzer</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: Implementa <code>public function canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool</code>.',
                'Paso 3: Si <code>$targetCommit === $sourceCommit</code>, retorna <code>true</code> inmediatamente.',
                'Paso 4: Inicializa una cola (FIFO) con <code>$sourceCommit</code> y un mapa de visitados.',
                'Paso 5: Mientras la cola tenga elementos, extrae el commit actual con <code>array_shift($queue)</code>.',
                'Paso 6: Si el commit extraído es igual a <code>$targetCommit</code>, retorna <code>true</code> (se encontró el camino de ancestros).',
                'Paso 7: Obtén los padres del commit actual desde <code>$commitGraph[$current] ?? []</code> y encola los que no hayan sido visitados.',
                'Paso 8: Si la cola se vacía sin alcanzar <code>$targetCommit</code>, retorna <code>false</code> (las ramas divergieron).',
            ],
            'useful_functions' => [
                [
                    'name' => 'array_shift(&$array)',
                    'desc' => 'Extrae y retorna el primer elemento de un array (ideal para colas BFS).',
                ],
                [
                    'name' => 'isset($visited[$key])',
                    'desc' => 'Comprueba en O(1) si un commit ya fue explorado para evitar ciclos o trabajo redundante.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'El Algoritmo de Búsqueda',
                'text' => 'Usa una Búsqueda en Anchura (Breadth-First Search / BFS). Comienza en sourceCommit y retrocede por los parents de commit en commit.',
                'snippet' => '$queue = [$sourceCommit];
$visited = [];',
            ],
            [
                'label' => 'Extracción y Coincidencia',
                'text' => 'En cada iteración del bucle while, extrae el primer commit y comprueba si coincide con targetCommit.',
                'snippet' => 'while (!empty($queue)) {
    $current = array_shift($queue);
    if ($current === $targetCommit) return true;',
            ],
            [
                'label' => 'Exploración de Padres',
                'text' => 'Obtén el array de padres desde commitGraph e inserta en la cola los que no estén en visited.',
                'snippet' => '    $parents = $commitGraph[$current] ?? [];
    foreach ($parents as $parent) {
        if (!isset($visited[$parent])) { $queue[] = $parent; }
    }
}',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Analizador de grafos de Git para evaluar fusiones Fast-Forward.
 */
class BranchMergeAnalyzer
{
    /**
     * @param string $targetCommit Commit de destino (ej. main)
     * @param string $sourceCommit Commit de origen (ej. feature)
     * @param array<string, list<string>> $commitGraph Mapa commit => lista de hashes padres
     */
    public function canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool
    {
        // PASO 1: Caso base de igualdad directa
        // PASO 2: Implementa recorrido BFS sobre los parents en commitGraph
        // PASO 3: Retorna true si targetCommit es alcanzable; false de lo contrario
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior de BranchMergeAnalyzer.
 */
class BranchMergeAnalyzer
{
    public function canFastForward(string $targetCommit, string $sourceCommit, array $commitGraph): bool
    {
        if ($targetCommit === $sourceCommit) {
            return true;
        }

        $queue = [$sourceCommit];
        $visited = [];

        while (!empty($queue)) {
            $current = array_shift($queue);

            if ($current === $targetCommit) {
                return true;
            }

            if (isset($visited[$current])) {
                continue;
            }
            $visited[$current] = true;

            $parents = $commitGraph[$current] ?? [];
            foreach ($parents as $parent) {
                if (!isset($visited[$parent])) {
                    $queue[] = $parent;
                }
            }
        }

        return false;
    }
}
',
        'explanation' => 'La solución modela el grafo DAG de Git y ejecuta una búsqueda en anchura (BFS) navegando desde el commit de origen hacia sus ancestros. Si el commit destino es alcanzable sin bifurcaciones no resueltas, la operación se clasifica como Fast-Forward legítimo en O(V + E).',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Estrategias de Ramificación y Rebase',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿En qué condición es matemáticamente posible realizar una fusión de tipo Fast-Forward en Git?',
                'options' => [
                    'a' => 'Siempre que la rama de feature tenga menos de 10 commits.',
                    'b' => 'Cuando el commit al que apunta la rama de destino es un ancestro directo y alcanzable del commit de la rama de origen (no hubo commits intermedios en destino).',
                    'c' => 'Solo si se ejecuta el comando git merge con la bandera --no-ff.',
                    'd' => 'Únicamente cuando todos los archivos son de extensión .php.',
                ],
                'correct' => 'b',
                'explanation' => 'Fast-Forward requiere que la rama destino no haya divergido. Si destino es ancestro de origen, basta con mover el puntero hacia adelante sin crear un commit de fusión.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la \'Regla de Oro\' sobre el uso de Git Rebase?',
                'options' => [
                    'a' => 'Nunca hacer rebase en ramas locales privadas.',
                    'b' => 'Nunca hacer rebase sobre ramas públicas compartidas (como main) porque reescribe los hashes SHA-1 y desincroniza a los demás colaboradores del equipo.',
                    'c' => 'Hacer rebase siempre con la bandera --force en todos los repositorios de producción.',
                    'd' => 'El rebase debe ejecutarse exclusivamente en servidores de CI/CD.',
                ],
                'correct' => 'b',
                'explanation' => 'Rebase reescribe el historial creando nuevos hashes SHA-1. Si se hace en una rama pública donde otros ingenieros tienen trabajo anclado, se genera un desastre de ramas desfasadas.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el postulado central de la metodología Trunk-Based Development?',
                'options' => [
                    'a' => 'Mantener ramas de release durante al menos 6 meses antes de desplegar.',
                    'b' => 'Que los ingenieros fusionen ramas de vida corta (< 24 horas) frecuentemente en la rama principal (trunk/main), apoyándose en Feature Flags para código en progreso.',
                    'c' => 'Eliminar la rama main y trabajar únicamente en ramas hotfix.',
                    'd' => 'Desarrollar sin pruebas automatizadas para acelerar la entrega.',
                ],
                'correct' => 'b',
                'explanation' => 'Trunk-Based Development minimiza la divergencia de código mediante integraciones continuas y atómicas a la rama troncal, evitando ramas gigantescas.',
            ],
        ],
    ],
];
