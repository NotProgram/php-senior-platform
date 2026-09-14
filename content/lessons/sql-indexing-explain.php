<?php

declare(strict_types=1);

return [
    'slug' => 'sql-indexing-explain',
    'title' => 'Estrategias de Índices B-Tree, Consultas Sargables & Dominio de EXPLAIN',
    'module' => 'Bases de Datos & SQL',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Los índices en bases de datos relacionales son estructuras de datos en árbol (B-Tree) ordenadas que permiten búsquedas logarítmicas O(log N). Una consulta es \'Sargable\' (Search-Argument-Able) cuando permite al motor utilizar el índice sin aplicar funciones sobre las columnas que fuercen un escaneo total de la tabla.',
        'problem' => 'El uso de funciones en predicados WHERE (como \'WHERE YEAR(created_at) = 2024\') invalida el índice B-Tree, obligando a la base de datos a realizar un Full Table Scan (tipo \'ALL\' en EXPLAIN), destruyendo la I/O del servidor bajo millones de registros.',
        'solution' => 'Reescribir predicados no sargables en rangos abiertos (\'created_at >= :start AND created_at < :end\') mediante la clase \'SargableQueryOptimizer\', y auditar planes de ejecución de EXPLAIN verificando tipos de acceso óptimos (\'const\', \'eq_ref\', \'ref\', \'range\').',
        'problem_label' => 'El Problema: Consultas Non-Sargable y Colapso por Full Table Scan',
        'solution_label' => 'La Solución Senior: B-Trees, Predicados Sargables y Tipos Óptimos de EXPLAIN',
    ],
    'mental_model' => [
        'title' => 'La Guía Telefónica Alfabética vs Leer el Libro Entero (Full Table Scan)',
        'analogy' => 'Imagina una guía telefónica con 1 millón de personas ordenada alfabéticamente por apellido (Índice B-Tree). Si buscas a \'García\', abres la mitad del libro y en solo 5 saltos encuentras el número (Búsqueda binaria O(log N) / tipo `ref` o `range`). Pero imagina que alguien te pide: *\'Encuentra a todos los clientes cuyo año de nacimiento calculado al vuelo sea 1990 (`WHERE YEAR(created_at) = 2024`)\'*. Como la guía está ordenada por la fecha completa y no por el resultado de la función `YEAR()`, el índice B-Tree queda **completamente inservible (Non-Sargable Query)**. La base de datos no tiene más remedio que leer físicamente cada una de las 1,000,000 de páginas desde el disco (Full Table Scan / `type: ALL`), saturando el disco y tardando segundos. Un predicado **Sargable** reescribe la búsqueda como un rango nativo: `WHERE created_at >= \'2024-01-01\' AND created_at < \'2025-01-01\'`, permitiendo a la base de datos saltar directamente al árbol en milisegundos.',
        'ascii_diagram' => 'NON-SARGABLE (Destruye el índice B-Tree):
WHERE YEAR(created_at) = 2024
       │
       ▼ (Aplica función YEAR() a cada fila de disco)
[FULL TABLE SCAN: type = ALL] ──> Lee 1,000,000 filas de disco (1500 ms)

SARGABLE (Aprovecha el índice B-Tree):
WHERE created_at >= \'2024-01-01\' AND created_at < \'2025-01-01\'
       │
       ▼ (Salto directo en la raíz del árbol B-Tree)
[INDEX RANGE SCAN: type = range] ──> Lee 450 filas de memoria (1.2 ms)

JERARQUÍA DE TIPOS EN EXPLAIN (De óptimo a crítico):
const > eq_ref > ref > range > index > ALL
    ▲              ▲                     ▲
 (Óptimos para producción)         (Crítico: Escaneo total de tabla)',
        'key_concept' => 'Una consulta es Sargable si permite al motor usar un índice B-Tree sin evaluar funciones sobre la columna. En EXPLAIN, los tipos de acceso sanos en producción son const, eq_ref, ref y range.',
    ],
    'internals' => [
        'title' => 'Anatomía del B-Tree y los Tipos de Acceso de EXPLAIN',
        'steps' => [
            [
                'phase' => '1. Estructura del B-Tree en Bloques de 16KB (InnoDB Pages)',
                'description' => 'InnoDB almacena los índices en árboles B+ compuestos por páginas de 16KB. Los nodos raíz e internos contienen claves de separación; las hojas contienen los punteros o claves primarias.',
            ],
            [
                'phase' => '2. Por qué las Funciones Destruyen el Índice (Non-Sargable)',
                'description' => 'Al escribir \'YEAR(col)\', la base de datos no puede inferir qué valor original de \'col\' produce ese resultado sin calcular la función fila por fila, desactivando el recorrido de árbol.',
            ],
            [
                'phase' => '3. Transformación a Rangos Semánticos',
                'description' => 'Transformar \'YEAR(col) = 2024\' en \'col >= 2024-01-01 00:00:00 AND col <= 2024-12-31 23:59:59\' permite una búsqueda de rango directa en las hojas del árbol B-Tree.',
            ],
            [
                'phase' => '4. Interpretación de la Columna \'type\' en EXPLAIN',
                'description' => '\'const\'/\'eq_ref\' (1 fila por clave primaria/única), \'ref\' (clave no única indexada), \'range\' (rango acotado con <, >, BETWEEN). Si aparece \'ALL\', el motor escanea el 100% de la tabla.',
            ],
            [
                'phase' => '5. Covering Indexes (Using index)',
                'description' => 'Si todas las columnas del SELECT están presentes en el propio índice, la base de datos ni siquiera toca la tabla principal en disco, sirviendo la consulta puramente desde la memoria del buffer pool.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Índices Compuestos y la Regla del Prefijo Más a la Izquierda',
            'icon' => 'layers',
            'content' => 'Si creas un índice compuesto `INDEX idx_user_status_date (tenant_id, status, created_at)`:

• `WHERE tenant_id = 5 AND status = \'active\'` -> **USA EL ÍNDICE**.
• `WHERE tenant_id = 5 AND created_at > \'...\'` -> **USA SOLO LA PRIMERA COLUMNA**.
• `WHERE status = \'active\'` -> **NO USA EL ÍNDICE** (rompe la regla del prefijo izquierdo).

Un índice compuesto es como un directorio telefónico ordenado por (Apellido, Nombre, Ciudad). No puedes buscar eficientemente por Nombre si no conoces el Apellido.',
            'takeaways' => 'Ordena las columnas de tus índices compuestos colocando primero las de igualdad estricta (=) y al final las de rango (<, >).',
        ],
        [
            'title' => 'El Peligro Oculto de la Coerción Implícita de Tipos',
            'icon' => 'alert-triangle',
            'content' => 'Si tienes una columna `phone VARCHAR(20)` indexada y ejecutas:
`SELECT * FROM users WHERE phone = 12345678;` (entero sin comillas):

MySQL convierte automáticamente cada string de la tabla a número para poder comparar (`CAST(phone AS UNSIGNED)`). Esto convierte la consulta en **Non-Sargable en tiempo de ejecución**, ejecutando un Full Table Scan a pesar de que el índice existe.',
            'takeaways' => 'Pasa siempre parámetros fuertemente tipados a la base de datos para evitar que la coerción implícita invalide los índices.',
        ],
    ],
    'video' => [
        'title' => 'Database Indexing Explained (with PostgreSQL)',
        'speaker' => 'Hussein Nasser',
        'youtube_id' => '-qNSXK7s7_w',
        'duration' => '45 min',
        'description' => 'Una inmersión profunda en la estructura física de índices B-Tree, el costo en escrituras, planes de ejecución y optimización de consultas en bases de datos relacionales.',
        'key_takeaways' => [
            'Cómo están representados los nodos raíz, ramas y hojas en un índice B-Tree.',
            'Por qué los índices aceleran lecturas pero penalizan las operaciones INSERT y UPDATE.',
            'Cómo leer e interpretar el comando EXPLAIN y EXPLAIN ANALYZE.',
            'Técnicas para convertir consultas lentas en escaneos de rango ultra-rápidos.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Database/Optimizer/SargableQueryOptimizer.php',
        'title' => 'Optimizador de Consultas Sargables y Auditor de EXPLAIN',
        'tag' => 'SQL Optimization & EXPLAIN',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Optimizer;

use InvalidArgumentException;

/**
 * Optimiza predicados SQL transformando funciones no sargables en rangos B-Tree
 * y audita si un tipo de acceso de EXPLAIN cumple con estándares de rendimiento Senior.
 */
class SargableQueryOptimizer
{
    private const array OPTIMAL_ACCESS_TYPES = [
        \'const\',
        \'eq_ref\',
        \'ref\',
        \'range\',
    ];

    /**
     * Transforma un predicado \'YEAR(col) = YYYY\' en una condición de rango sargable
     * que permite a la base de datos utilizar índices B-Tree.
     */
    public function optimizeYearPredicate(string $column, int $year): string
    {
        if ($year < 1970) {
            throw new InvalidArgumentException(\'El año debe ser mayor o igual a 1970.\');
        }

        $start = sprintf(\'%04d-01-01 00:00:00\', $year);
        $end = sprintf(\'%04d-12-31 23:59:59\', $year);

        return sprintf(\'%s >= "%s" AND %s <= "%s"\', $column, $start, $column, $end);
    }

    /**
     * Determina si el tipo de acceso reportado por EXPLAIN es óptimo para producción.
     */
    public function isOptimalAccessType(string $accessType): bool
    {
        return in_array(strtolower(trim($accessType)), self::OPTIMAL_ACCESS_TYPES, true);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior ve que una consulta tarda 3 segundos y sugiere \'subir la RAM del servidor\'. Un ingeniero Senior corre EXPLAIN, identifica el \'type: ALL\', detecta funciones aplicadas sobre columnas en el WHERE (Non-Sargable), reescribe la consulta a rangos de B-Tree (\'range\') o crea índices compuestos que reducen la latencia de 3000ms a 2ms.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Consulta non-sargable que destruye el índice',
            'code' => '// Consulta que fuerza un Full Table Scan
$sql = "SELECT * FROM orders WHERE YEAR(created_at) = 2024";
// En una tabla con 2 millones de filas, colapsa el CPU de la base de datos!',
            'flaws' => [
                'Aplica la función YEAR() sobre la columna indexada impidiendo usar el B-Tree.',
                'Fuerza un escaneo completo de 2 millones de filas desde disco (type: ALL).',
                'Ignora la auditoría de EXPLAIN y el análisis de costo de I/O.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Predicado sargable con rango cerrado e índice B-Tree',
            'code' => 'declare(strict_types=1);

namespace App\\Database\\Optimizer;

class SargableQueryOptimizer
{
    private const array OPTIMAL_TYPES = [\'const\', \'eq_ref\', \'ref\', \'range\'];

    public function optimizeYearPredicate(string $column, int $year): string
    {
        if ($year < 1970) throw new \\InvalidArgumentException(\'Year must be >= 1970\');
        return sprintf(\'%s >= "%04d-01-01 00:00:00" AND %s <= "%04d-12-31 23:59:59"\', $column, $year, $column, $year);
    }

    public function isOptimalAccessType(string $type): bool
    {
        return in_array(strtolower(trim($type)), self::OPTIMAL_TYPES, true);
    }
}',
            'rationale' => [
                'Transforma el predicado a un rango abierto que el optimizador B-Tree resuelve en O(log N).',
                'Valida años válidos (>= 1970) previniendo bugs de timestamp Unix.',
                'Audita formalmente los tipos de acceso sanos de EXPLAIN (const, eq_ref, ref, range).',
            ],
            'trade_offs' => [
                'Reescribir a rangos requiere manejar zonas horarias (UTC) con rigor en la aplicación.',
                'Crear índices excesivos ralentiza las operaciones INSERT/UPDATE; cada índice debe justificarse con telemetría.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Sargable Queries in Relational Databases',
            'source' => 'Use The Index, Luke! - SQL Indexing Guide',
            'quote' => 'Una consulta es sargable si el motor de la base de datos puede aprovechar un índice B-Tree para saltar directamente a los datos sin evaluar expresiones sobre cada fila.',
            'author' => 'Markus Winand',
            'explanation' => 'El libro de referencia global de optimización SQL explica cómo evitar invalidar índices con funciones.',
        ],
        [
            'topic' => 'EXPLAIN Plan Interpretation',
            'source' => 'High Performance MySQL',
            'quote' => 'La columna \'type\' en EXPLAIN es el indicador más crítico del plan de ejecución: \'ALL\' significa que estás desperdiciando ciclos de CPU y disco.',
            'author' => 'Baron Schwartz',
            'explanation' => 'Comprender la jerarquía de tipos de acceso separa al programador novato del ingeniero Senior.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Use The Index, Luke! (Guía canónica de Índices SQL)',
            'url' => 'https://use-the-index-luke.com/es',
            'description' => 'El mejor recurso de la web para dominar índices B-Tree, clustering y sargabilidad.',
            'type' => 'BOOK',
        ],
        [
            'title' => 'MySQL 8.0 Reference Manual: EXPLAIN Output Format',
            'url' => 'https://dev.mysql.com/doc/refman/8.0/en/explain-output.html',
            'description' => 'Documentación oficial sobre la columna \'type\', filas estimadas y filtros en EXPLAIN.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PostgreSQL Documentation: Using EXPLAIN',
            'url' => 'https://www.postgresql.org/docs/current/using-explain.html',
            'description' => 'Guía oficial de PostgreSQL para interpretar planes de consulta, costos y buffer hits.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Optimizador de Consultas Sargables y Auditor de EXPLAIN',
        'objective' => 'Implementar la clase SargableQueryOptimizer con los métodos optimizeYearPredicate(string $column, int $year): string (validando $year >= 1970) e isOptimalAccessType(string $accessType): bool evaluando const, eq_ref, ref y range.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'SargableQueryOptimizer\' en el namespace \'App\\Database\\Optimizer\'.
3. Implementa \'optimizeYearPredicate(string $column, int $year): string\'. Si \'$year < 1970\', lanza \'InvalidArgumentException\'.
4. Genera la condición de rango: \'$column >= "YYYY-01-01 00:00:00" AND $column <= "YYYY-12-31 23:59:59"\'.
5. Implementa \'isOptimalAccessType(string $accessType): bool\' retornando true para \'const\', \'eq_ref\', \'ref\' y \'range\'.',
        'filename' => 'src/Database/Optimizer/SargableQueryOptimizer.php',
        'guide' => [
            'explanation' => 'Valida if ($year < 1970) throw new \\InvalidArgumentException(\'...\');. Retorna sprintf(\'%s >= "%04d-01-01 00:00:00" AND %s <= "%04d-12-31 23:59:59"\', $column, $year, $column, $year). En isOptimalAccessType, usa in_array con los 4 tipos.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase SargableQueryOptimizer y el método optimizeYearPredicate(string $column, int $year): string.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe validarse que el año sea mayor o igual a 1970 lanzando InvalidArgumentException.',
            ],
            [
                'text' => '[Pista 3: Snippet] Los tipos óptimos de EXPLAIN deben incluir const, eq_ref, ref y range: return in_array(strtolower($accessType), [\'const\', \'eq_ref\', \'ref\', \'range\'], true);.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Optimizer;

use InvalidArgumentException;

class SargableQueryOptimizer
{
    public function optimizeYearPredicate(string $column, int $year): string
    {
        // TODO: Valida año >= 1970 y transforma a rango sargable
        return \'\';
    }

    public function isOptimalAccessType(string $accessType): bool
    {
        // TODO: Retorna true para const, eq_ref, ref, range
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Database\\Optimizer;

use InvalidArgumentException;

class SargableQueryOptimizer
{
    private const array OPTIMAL_ACCESS_TYPES = [
        \'const\',
        \'eq_ref\',
        \'ref\',
        \'range\',
    ];

    public function optimizeYearPredicate(string $column, int $year): string
    {
        if ($year < 1970) {
            throw new InvalidArgumentException(\'El año debe ser mayor o igual a 1970.\');
        }

        $start = sprintf(\'%04d-01-01 00:00:00\', $year);
        $end = sprintf(\'%04d-12-31 23:59:59\', $year);

        return sprintf(\'%s >= "%s" AND %s <= "%s"\', $column, $start, $column, $end);
    }

    public function isOptimalAccessType(string $accessType): bool
    {
        return in_array(strtolower(trim($accessType)), self::OPTIMAL_ACCESS_TYPES, true);
    }
}
',
        'explanation' => 'SargableQueryOptimizer reescribe predicados no sargables en rangos compatibles con índices B-Tree y provee una verificación formal de los tipos de acceso de EXPLAIN óptimos para entornos de alta concurrencia.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Estrategias de Índices B-Tree, Consultas Sargables & Dominio de EXPLAIN',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué la consulta \'WHERE YEAR(created_at) = 2024\' es considerada Non-Sargable?',
                'options' => [
                    'a' => 'Porque la función YEAR() no existe en el estándar SQL.',
                    'b' => 'Porque al aplicar una función sobre la columna indexada, la base de datos se ve obligada a evaluar cada fila individualmente, impidiendo el uso del árbol B-Tree.',
                    'c' => 'Porque las fechas no se pueden indexar en MySQL.',
                    'd' => 'Porque solo funciona en años bisiestos.',
                ],
                'correct' => 'b',
                'explanation' => 'El optimizador B-Tree solo puede buscar valores exactos o rangos de la clave tal como está almacenada. Si envuelves la columna en una función, el índice queda ciego y ocurre un Full Table Scan.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál de los siguientes tipos de acceso en EXPLAIN indica el PEOR rendimiento en una tabla grande?',
                'options' => [
                    'a' => 'const',
                    'b' => 'ref',
                    'c' => 'range',
                    'd' => 'ALL',
                ],
                'correct' => 'd',
                'explanation' => '\'ALL\' significa Full Table Scan: el motor lee absolutamente todos los registros del disco, lo cual satura la memoria y CPU en tablas con miles o millones de registros.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué es un \'Covering Index\' (Índice Cubridor)?',
                'options' => [
                    'a' => 'Un índice que cifra las contraseñas de los usuarios.',
                    'b' => 'Un índice que contiene todas las columnas solicitadas por la consulta, permitiendo responderla sin acceder a los datos de la tabla en disco.',
                    'c' => 'Un índice que se aplica sobre todas las tablas de la base de datos a la vez.',
                    'd' => 'Un índice temporal que se borra al reiniciar el servidor.',
                ],
                'correct' => 'b',
                'explanation' => 'Cuando un índice contiene todos los campos del SELECT y del WHERE (\'Using index\'), la base de datos resuelve la consulta directamente desde el buffer pool de RAM sin tocar el almacenamiento de la tabla.',
            ],
        ],
    ],
];
