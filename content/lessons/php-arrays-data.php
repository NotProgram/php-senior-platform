<?php

declare(strict_types=1);

return [
    'slug' => 'php-arrays-data',
    'title' => 'Arrays Indexados, Asociativos & Transformación Funcional',
    'module' => 'PHP Moderno',
    'minutes' => 40,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'Los arrays en PHP son en realidad mapas ordenados en memoria que combinan las capacidades de listas indexadas, tablas hash y diccionarios. Dominar la manipulación de arrays con funciones nativas funcionales (array_map, array_filter), desestructuración y el operador spread (...) es esencial para procesar datos de forma limpia.',
        'problem' => 'Iterar colecciones con bucles foreach que mutan arrays externos acumulando estados temporales ($resultado = []; foreach ($x as $y) { ... }) crea código propenso a efectos secundarios, difícil de leer y propenso a desbordamientos de memoria.',
        'solution' => 'Adoptar un estilo funcional inmutable: filtrar elementos inválidos con array_filter(), transformar registros con array_map() y funciones flecha (fn ($x) => ...), y calcular agregaciones con array_sum() de forma declarativa.',
        'problem_label' => 'El Problema: Mutación de Estado y Bucles Imperativos Enredados',
        'solution_label' => 'La Solución Senior: Pipelines de Transformación Funcional Inmutable',
    ],
    'mental_model' => [
        'title' => 'La Cinta Transportadora de Fábrica',
        'analogy' => 'Imagina una fábrica de ensamblaje. En el enfoque imperativo tradicional, un operario toma una caja vacía, camina por la bodega buscando piezas con un carrito, descarta piezas dañadas a mano y va metiendo cosas una a una, corriendo el riesgo de tropezar o mezclar cajas. En el enfoque funcional declarativo, colocas las piezas en una cinta transportadora: la primera estación filtra los defectuosos (array_filter), la segunda estación pinta o transforma cada pieza (array_map), y la báscula al final pesa el lote total (array_sum). Las piezas fluyen limpias sin que nadie tenga que manipular variables intermedias.',
        'ascii_diagram' => 'COLECCIÓN ORIGINAL: [Producto A ($10, Activo), Producto B ($25, Inactivo), Producto C ($15, Activo)]
                         │
                         ▼
        array_filter(..., fn($p) => $p[\'active\'])
                         │
                         ▼
             [Producto A ($10), Producto C ($15)]
                         │
                         ▼
        array_sum(array_column(..., \'price\'))
                         │
                         ▼
                 TOTAL: $25.00',
        'key_concept' => 'Las funciones funcionales no mutan el array original; devuelven una nueva colección procesada, preservando la inmutabilidad y reduciendo bugs colaterales.',
    ],
    'internals' => [
        'title' => 'Los Dos Rostros de los Arrays en PHP',
        'steps' => [
            [
                'phase' => '1. Arrays Indexados (Listas)',
                'description' => 'Colecciones donde las claves son enteros secuenciales que inician en 0: [\'PHP\', \'Symfony\', \'Docker\']. En PHP 8.1+ se verifica con array_is_list($arr).',
            ],
            [
                'phase' => '2. Arrays Asociativos (Mapas Clave-Valor)',
                'description' => 'Colecciones donde las claves son strings descriptivos: [\'name\' => \'Curso Senior\', \'price\' => 99.0]. Son la estructura base para payloads JSON y filas de base de datos.',
            ],
            [
                'phase' => '3. El Operador Spread (...)',
                'description' => 'Permite desempaquetar arrays tanto en listas como en arrays asociativos: $combined = [...$defaults, ...$custom]; los valores de claves repetidas se sobreescriben limpiamente.',
            ],
            [
                'phase' => '4. Desestructuración de Arrays',
                'description' => 'Extrae elementos directamente a variables: [\'price\' => $price, \'title\' => $title] = $product; evitando múltiples asignaciones manuales.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Funciones Nativas Indispensables para Arrays',
            'icon' => 'layers',
            'content' => 'Para escribir código limpio y senior, apóyate en las funciones nativas optimizadas en C:

• **array_filter($array, callback):** Conserva únicamente los elementos donde el callback retorne true.
• **array_map(callback, $array):** Aplica una transformación a cada elemento y retorna el nuevo array.
• **array_column($array, \'clave\'):** Extrae los valores de una columna específica de una lista de arrays asociativos.
• **in_array($needle, $haystack, true):** Verifica si un valor existe. ¡Siempre pasa true como tercer parámetro para comparación estricta!',
            'takeaways' => 'Un Senior conoce y utiliza las funciones estándar del lenguaje antes de escribir un bucle manual.',
        ],
    ],
    'video' => [
        'title' => 'Modern Array Manipulation in PHP 8',
        'speaker' => 'Derick Rethans (PHP Core Contributor)',
        'youtube_id' => 'k3y6pA9gXzE',
        'duration' => '30 min',
        'description' => 'Explicación técnica sobre arrays asociativos, arrays desempaquetados con spread operator y array_is_list.',
        'chapters' => [
            '00:00' => 'Estructura interna de arrays en Zend Engine',
            '08:00' => 'array_is_list y listas continuas',
            '16:20' => 'Transformaciones con array_map y array_filter',
            '25:00' => 'Operador spread en arrays asociativos',
        ],
    ],
    'architecture_code' => [
        'filename' => 'ProductCatalogProcessor.php',
        'title' => 'Procesador de Catálogo con Enfoque Funcional',
        'tag' => 'PHP 8.4 Functional Clean Code',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

/**
 * Procesador de catálogos y colecciones de productos en memoria.
 * Aplica transformaciones funcionales sin mutaciones colaterales.
 */
final readonly class ProductCatalogProcessor
{
    /**
     * Filtra una lista de productos reteniendo solo los activos.
     *
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     * @return list<array{id: int, name: string, price: float, active: bool}>
     */
    public function filterActive(array $products): array
    {
        $filtered = array_filter(
            $products,
            static fn (array $product): bool => (bool) ($product[\'active\'] ?? false)
        );

        // Reindexa numéricamente para garantizar una lista pura en formato JSON
        return array_values($filtered);
    }

    /**
     * Calcula la suma total del inventario de productos activos.
     *
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     */
    public function calculateInventoryValue(array $products): float
    {
        $activeProducts = $this->filterActive($products);
        $prices = array_column($activeProducts, \'price\');

        return round((float) array_sum($prices), 2);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Antes de escribir un bucle foreach, pregúntate: ¿Estoy filtrando? Usa array_filter. ¿Estoy transformando? Usa array_map. ¿Estoy extrayendo una propiedad? Usa array_column. El código declarativo expresa la intención directa del negocio.',
        'critical_questions' => [
            '¿Este array_filter desordenó los índices numéricos? (Usa array_values si necesitas una lista contigua).',
            '¿Estoy pasando true como tercer argumento en in_array() para evitar comparaciones flojas?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Calcular el valor total de los productos que estén disponibles en el inventario.',
        'junior' => [
            'approach' => 'Crea un acumulador mutable $total = 0; y un bucle con un if anidado.',
            'flaws' => [
                'Código propenso a errores de índice, difícil de componer y no testeable en pasos separados.',
            ],
        ],
        'senior' => [
            'approach' => 'Compone funciones puras: filtra con array_filter(), extrae precios con array_column() y suma con array_sum().',
            'rationale' => [
                'Alta legibilidad, cada paso tiene una única responsabilidad y no hay mutación de variables externas.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Inmutabilidad y Funciones Puras',
            'source' => 'Functional Programming in PHP',
            'quote' => 'Al evitar mutar las colecciones de entrada, tus funciones se vuelven predecibles y libres de efectos secundarios sorpresa.',
            'author' => 'Luis Atencio',
            'explanation' => 'Las transformaciones funcionales facilitan el paralelismo y el razonamiento formal del código.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Manual Oficial de PHP: Funciones de Manipulación de Arrays',
            'url' => 'https://www.php.net/manual/es/ref.array.php',
            'description' => 'Referencia de todas las funciones nativas para arrays en PHP.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Procesador de Catálogo de Productos',
        'objective' => 'Implementar la clase ProductCatalogProcessor en App\\Fundamentals con métodos para filtrar productos activos y calcular el valor total del inventario.',
        'instructions' => 'Crea la clase ProductCatalogProcessor en el namespace App\\Fundamentals con declare(strict_types=1);. Implementa dos métodos públicos: filterActive(array $products): array (recibe un array de arrays asociativos con claves id, name, price y active, y retorna solo aquellos con active === true, reindexados con array_values) y calculateInventoryValue(array $products): float (filtra los productos activos, extrae sus precios con array_column y retorna la suma redondeada a 2 decimales).',
        'filename' => 'ProductCatalogProcessor.php',
        'guide' => [
            'explanation' => 'Este ejercicio te entrena para procesar colecciones de datos del mundo real tal como se reciben de APIs REST o bases de datos.',
            'steps' => [
                'Paso 1: Declara namespace App\\Fundamentals; con tipado estricto.',
                'Paso 2: En filterActive(array $products): array, usa array_filter con una función flecha fn ($p) => (bool) ($p[\'active\'] ?? false).',
                'Paso 3: Retorna array_values($filtered) para restablecer los índices numéricos.',
                'Paso 4: En calculateInventoryValue(array $products): float, llama a filterActive($products).',
                'Paso 5: Extrae los precios con array_column($active, \'price\') y suma con array_sum, retornando el float redondeado a 2 decimales.',
            ],
            'useful_functions' => [
                [
                    'name' => 'array_filter(array $array, ?callable $callback)',
                    'desc' => 'Filtra elementos de un array usando una función de retorno booleano.',
                ],
                [
                    'name' => 'array_values(array $array)',
                    'desc' => 'Devuelve todos los valores de un array e indexa las claves numéricamente desde 0.',
                ],
                [
                    'name' => 'array_column(array $array, string|int $columnKey)',
                    'desc' => 'Devuelve los valores de una sola columna del array de entrada.',
                ],
                [
                    'name' => 'array_sum(array $array)',
                    'desc' => 'Calcula la suma de los valores de un array.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Filtrado con Arrow Function',
                'text' => 'Utiliza fn ($item) => (bool) ($item[\'active\'] ?? false).',
                'snippet' => '$active = array_values(array_filter($products, fn ($p) => (bool) ($p[\'active\'] ?? false)));',
            ],
            [
                'label' => 'Extracción y Suma de Precios',
                'text' => 'Combina array_column y array_sum.',
                'snippet' => 'return round((float) array_sum(array_column($active, \'price\')), 2);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

/**
 * Procesador de Catálogo de Productos.
 */
class ProductCatalogProcessor
{
    /**
     * Filtra la lista retornando únicamente los productos activos reindexados.
     *
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     * @return list<array{id: int, name: string, price: float, active: bool}>
     */
    public function filterActive(array $products): array
    {
        // PASO 1: Filtra los productos donde active sea true
        // PASO 2: Reindexa el array con array_values
        return [];
    }

    /**
     * Retorna la suma total de los precios de los productos activos.
     *
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     */
    public function calculateInventoryValue(array $products): float
    {
        // PASO 3: Obtén los productos activos
        // PASO 4: Suma sus precios y retorna el resultado redondeado a 2 decimales
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

/**
 * Procesador de Catálogo de Productos.
 */
class ProductCatalogProcessor
{
    /**
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     * @return list<array{id: int, name: string, price: float, active: bool}>
     */
    public function filterActive(array $products): array
    {
        $filtered = array_filter(
            $products,
            static fn (array $p): bool => (bool) ($p[\'active\'] ?? false)
        );

        return array_values($filtered);
    }

    /**
     * @param list<array{id: int, name: string, price: float, active: bool}> $products
     */
    public function calculateInventoryValue(array $products): float
    {
        $active = $this->filterActive($products);
        $prices = array_column($active, \'price\');

        return round((float) array_sum($prices), 2);
    }
}
',
        'explanation' => 'ProductCatalogProcessor demuestra cómo manipular colecciones de forma declarativa e inmutable utilizando las funciones estándar array_filter, array_values, array_column y array_sum.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Arrays y Manipulación Funcional en PHP',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué es una buena práctica aplicar array_values() después de un array_filter() en arrays indexados?',
                'options' => [
                    'a' => 'Porque array_filter elimina la memoria del array si no se llama a array_values().',
                    'b' => 'Porque array_filter conserva las claves numéricas originales, dejando "huecos" en los índices (ej. [0, 2, 5]), lo que rompe listas serializadas en JSON.',
                    'c' => 'Porque array_values convierte los strings a números automáticamente.',
                    'd' => 'No tiene ninguna utilidad; es una función obsoleta de PHP 5.',
                ],
                'correct' => 'b',
                'explanation' => 'array_filter preserva las claves originales. Si no reindexas con array_values, un json_encode transformará tu array en un objeto con claves no contiguas en vez de una lista limpia.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué función nativa permite extraer una sola propiedad de una colección de arrays asociativos sin usar un bucle?',
                'options' => [
                    'a' => 'array_extract()',
                    'b' => 'array_column()',
                    'c' => 'array_pluck()',
                    'd' => 'array_slice()',
                ],
                'correct' => 'b',
                'explanation' => 'array_column($records, \'nombre_columna\') extrae directamente los valores de esa clave en un nuevo array plano.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué hace el operador spread (...) en la expresión $final = [...$base, ...$extra] en PHP 8.1+?',
                'options' => [
                    'a' => 'Crea un puntero de memoria compartida entre ambos arrays.',
                    'b' => 'Desempaqueta y fusiona los elementos de ambos arrays en un nuevo array independiente.',
                    'c' => 'Convierte ambos arrays en cadenas de texto separadas por comas.',
                    'd' => 'Elimina los duplicados y ordena los elementos alfabéticamente.',
                ],
                'correct' => 'b',
                'explanation' => 'El operador spread (...) desempaqueta los pares clave-valor o elementos indexados en un nuevo array de forma inmutable.',
            ],
        ],
    ],
];
