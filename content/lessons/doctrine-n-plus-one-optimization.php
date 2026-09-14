<?php

declare(strict_types=1);

return [
    'slug' => 'doctrine-n-plus-one-optimization',
    'title' => 'Detección & Mitigación de Queries N+1 con JOIN FETCH y DQL',
    'module' => 'Doctrine ORM',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El problema de las consultas N+1 ocurre cuando Doctrine utiliza carga perezosa (Lazy Loading) dentro de un bucle de iteración: ejecuta 1 consulta para la entidad principal y N consultas adicionales individuales para cargar las relaciones de cada fila. Esto se resuelve instruyendo a Doctrine a realizar una carga ansiosa (Eager Loading) mediante \'JOIN FETCH\' en DQL.',
        'problem' => 'Una vista que lista 50 pedidos y muestra el nombre del cliente y sus productos asociados dispara inadvertidamente 101 consultas SQL individuales hacia la base de datos, multiplicando la latencia por 50x y saturando el pool de conexiones del servidor.',
        'solution' => 'Construir consultas DQL optimizadas mediante la clase \'OptimizedDqlQueryBuilder\' con el método \'buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string\', validando que $joins no esté vacío (lanzando InvalidArgumentException) e incorporando los alias en el SELECT y cláusulas INNER JOIN para resolver las relaciones en una única consulta consolidada.',
        'problem_label' => 'El Problema: La Trampa de Lazy Loading y las 100 Queries Ocultas',
        'solution_label' => 'La Solución Senior: DQL con JOIN FETCH y Carga Consolidada en un Solo Viaje',
    ],
    'mental_model' => [
        'title' => 'El Mensajero que Hace 100 Viajes en Bicicleta vs El Camión de Reparto Único',
        'analogy' => 'Imagina que tienes una lista de 50 pedidos y necesitas imprimir el nombre del cliente de cada uno. En código junior con Lazy Loading inconsciente, el sistema manda a un mensajero a traer la lista de los 50 pedidos en una consulta (`SELECT * FROM orders`). Luego, dentro de un bucle `foreach ($orders as $order) { echo $order->getCustomer()->getName(); }`, cada vez que tocas `getCustomer()`, el mensajero tiene que subirse a su bicicleta, pedalear hasta el almacén central (base de datos por red TCP), traer al cliente 1, volver, y repetir el viaje 50 veces (**Problema N+1: 1 consulta inicial + 50 consultas secundarias = 51 viajes de red**). En una red con 5ms de latencia, esto añade 250ms de lentitud inútil. La **Solución Senior** es enviar un solo camión de reparto (`JOIN FETCH` en DQL / `OptimizedDqlQueryBuilder`): en **una única consulta consolidada**, el camión carga los pedidos y sus clientes juntos (`SELECT o, c FROM Order o INNER JOIN o.customer c`), resolviendo todo en 1 solo viaje de 5ms.',
        'ascii_diagram' => 'EL PROBLEMA N+1 (Lazy Loading Inconsciente):
Query 1: SELECT * FROM orders LIMIT 50;
Query 2: SELECT * FROM customers WHERE id = 1;
Query 3: SELECT * FROM customers WHERE id = 2;
...
Query 51: SELECT * FROM customers WHERE id = 50;
Total: 51 consultas de red individuales (Latencia: ~500ms)

LA SOLUCIÓN SENIOR (DQL JOIN FETCH):
OptimizedDqlQueryBuilder::buildJoinFetchDql(\'App\\Entity\\Order\', \'o\', [\'customer\' => \'c\'])
       │
       ▼
SELECT o, c FROM App\\Entity\\Order o INNER JOIN o.customer c
Total: ¡1 SOLA consulta consolidada de red! (Latencia: ~10ms)',
        'key_concept' => 'Lazy loading en bucles es el asesino silencioso del rendimiento en Symfony. Añadir el alias de la relación en el SELECT de DQL (\'SELECT o, c\') le indica a Doctrine que hidrate los objetos asociados de inmediato en una sola consulta.',
    ],
    'internals' => [
        'title' => 'Anatomía de la Carga Ansiosa (Eager Loading) en Doctrine',
        'steps' => [
            [
                'phase' => '1. El Objeto Proxy de Doctrine (Ghost Objects)',
                'description' => 'Por defecto, cuando Doctrine carga una relación ManyToOne, no consulta la base de datos: crea una clase proxy generada en caché que extiende la entidad original y solo tiene el ID.',
            ],
            [
                'phase' => '2. Disparo de Inicialización del Proxy',
                'description' => 'En el milisegundo en que el código invoca un getter (ej. $order->getCustomer()->getName()), el proxy intercepta la llamada, comprueba que no está inicializado y ejecuta una consulta SQL individual a la base de datos.',
            ],
            [
                'phase' => '3. Cláusula SELECT con Múltiples Alias en DQL',
                'description' => 'En DQL, escribir \'SELECT o, c FROM Order o JOIN o.customer c\' tiene un significado especial: \'c\' en el SELECT instruye al hydrator de Doctrine a poblar la propiedad $customer con datos reales en lugar de un proxy.',
            ],
            [
                'phase' => '4. Hidratación en Objeto Anidado',
                'description' => 'El hydrator lee el result set plano de la base de datos (columnas order_id, order_total, customer_id, customer_name) y construye el grafo de objetos en memoria sin consultas secundarias.',
            ],
            [
                'phase' => '5. Detección en el Web Profiler de Symfony',
                'description' => 'El panel de Doctrine en /_profiler resalta en rojo las consultas duplicadas o idénticas, permitiendo al ingeniero Senior identificar problemas N+1 antes de que lleguen a producción.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'fetch="EAGER" en Mapeo de Entidad: Un Antipatrón Peligroso',
            'icon' => 'alert-triangle',
            'content' => 'Algunos desarrolladores intentan solucionar el problema N+1 poniendo `fetch: \'EAGER\'` en la anotación o atributo de la entidad:
```php
#[ORM\\ManyToOne(fetch: \'EAGER\')]
private ?Customer $customer = null;
```

**Por qué es un error gravísimo:**
Esto hace que cada vez que consultes un `Order` en CUALQUIER lugar de la aplicación (incluso en una API ligera que solo necesita el total), Doctrine se vea forzado a cargar el cliente siempre. Si tienes 5 relaciones EAGER, cada consulta traerá un árbol monstruoso innecesario.

La regla Senior es **mantener las relaciones en LAZY por defecto en las entidades**, y hacer la carga ansiosa **explícitamente en el repositorio mediante DQL** solo para los casos de uso que realmente lo requieran.',
            'takeaways' => 'Mantén las relaciones en LAZY en las entidades y aplica JOIN FETCH explícito en los repositorios según el caso de uso.',
        ],
        [
            'title' => 'Paginación Segura con Paginator de Doctrine',
            'icon' => 'layers',
            'content' => 'Cuando combinas `JOIN FETCH` sobre colecciones OneToMany (`SELECT o, items FROM Order o JOIN o.items items`) junto con `LIMIT` y `OFFSET`, SQL tradicional duplica las filas del pedido por cada item, rompiendo la cuenta de paginación.

Para solucionar esto de forma segura, Symfony y Doctrine proveen la clase `Doctrine\\ORM\\Tools\\Pagination\\Paginator`:
```php
$query = $em->createQuery($dql)->setFirstResult(0)->setMaxResults(20);
$paginator = new Paginator($query, fetchJoinCollection: true);
```

El paginador realiza dos pasos: primero consulta los IDs distintos y luego hidrata exactamente los 20 pedidos con sus items en una sola consulta estructurada.',
            'takeaways' => 'Usa siempre Doctrine Paginator con fetchJoinCollection=true cuando pagines consultas con JOIN FETCH sobre relaciones OneToMany.',
        ],
    ],
    'video' => [
        'title' => 'Marco Pivetta: A complex ORM... faster than raw SQL?',
        'speaker' => 'Marco Pivetta (Doctrine Core Team)',
        'youtube_id' => 'KmSSipwmURo',
        'duration' => '42 min',
        'description' => 'Una clase magistral de Marco Pivetta sobre optimización de consultas en Doctrine, mitigación de problemas N+1, uso de JOIN FETCH y estrategias de hidratación avanzadas.',
        'key_takeaways' => [
            'La causa raíz del problema N+1 en arquitecturas con ORMs.',
            'Cómo el uso de JOIN FETCH en DQL consolida múltiples viajes de red en una sola consulta.',
            'Diferencias de rendimiento entre hidratación completa de objetos, arrays y escalares.',
            'Técnicas para diagnosticar consultas redundantes mediante el Symfony Web Profiler.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Doctrine/Query/OptimizedDqlQueryBuilder.php',
        'title' => 'Constructor de Consultas DQL con JOIN FETCH Consolidado',
        'tag' => 'Doctrine 3 DQL JOIN FETCH',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Query;

use InvalidArgumentException;

/**
 * Construye consultas DQL optimizadas incorporando cláusulas JOIN FETCH
 * para erradicar el problema de las consultas N+1 en relaciones de entidad.
 */
class OptimizedDqlQueryBuilder
{
    /**
     * Construye una sentencia DQL con JOIN FETCH sobre las relaciones indicadas.
     *
     * @param string $rootEntity FQCN de la entidad raíz (ej. \'App\\\\Entity\\\\Order\')
     * @param string $rootAlias Alias principal en la consulta (ej. \'o\')
     * @param array<string, string> $joins Mapa de relación => alias (ej. [\'customer\' => \'c\'])
     */
    public function buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string
    {
        if (empty($joins)) {
            throw new InvalidArgumentException(\'Debe especificarse al menos una relación en $joins para construir una consulta JOIN FETCH.\');
        }

        $selectAliases = array_merge([$rootAlias], array_values($joins));
        $selectClause = sprintf(\'SELECT %s\', implode(\', \', $selectAliases));
        $fromClause = sprintf(\'FROM %s %s\', $rootEntity, $rootAlias);

        $joinClauses = [];
        foreach ($joins as $relation => $alias) {
            $joinClauses[] = sprintf(\'INNER JOIN %s.%s %s\', $rootAlias, $relation, $alias);
        }

        return sprintf(
            \'%s %s %s\',
            $selectClause,
            $fromClause,
            implode(\' \', $joinClauses)
        );
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior ignora cuántas consultas genera su código y no abre el Web Profiler de Symfony hasta que el servidor colapsa bajo tráfico real. Un ingeniero Senior monitorea activamente el panel de Doctrine, detecta consultas N+1 en bucles de vista o comandos, y construye consultas DQL con JOIN FETCH para traer todos los colaboradores en un solo viaje de red.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Bucle de vista que dispara 50 consultas secundarias',
            'code' => '{# Plantilla con problema N+1 catastrófico #}
{% for order in orders %}
    <tr>
        <td>{{ order.id }}</td>
        {# En cada iteración, Doctrine dispara una consulta SQL para el cliente! #}
        <td>{{ order.customer.name }}</td>
    </tr>
{% endfor %}',
            'flaws' => [
                'Dispara N consultas adicionales de forma invisible en la vista.',
                'Multiplica la latencia por el número de filas de la tabla.',
                'No aprovecha las capacidades de Eager Loading de DQL.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: DQL con JOIN FETCH consolidado en una sola consulta',
            'code' => 'declare(strict_types=1);

namespace App\\Doctrine\\Query;

use InvalidArgumentException;

class OptimizedDqlQueryBuilder
{
    public function buildJoinFetchDql(string $entity, string $alias, array $joins): string
    {
        if (empty($joins)) throw new InvalidArgumentException(\'Joins cannot be empty\');
        $select = \'SELECT \' . implode(\', \', array_merge([$alias], array_values($joins)));
        $from = "FROM {$entity} {$alias}";
        $joinLines = [];
        foreach ($joins as $rel => $relAlias) {
            $joinLines[] = "INNER JOIN {$alias}.{$rel} {$relAlias}";
        }
        return $select . \' \' . $from . \' \' . implode(\' \', $joinLines);
    }
}',
            'rationale' => [
                'Incluye los alias de las relaciones en el SELECT, instruyendo a Doctrine a hidratar en un solo paso.',
                'Construye cláusulas INNER JOIN explícitas sobre las relaciones de la entidad.',
                'Valida precondiciones (joins no vacíos) lanzando InvalidArgumentException.',
            ],
            'trade_offs' => [
                'JOIN FETCH genera result sets SQL más anchos con columnas duplicadas en la respuesta de la base de datos.',
                'En relaciones OneToMany múltiples concurrentes (ej. pedidos con items y con pagos), hacer múltiples JOINs genera un producto cartesiano que debe manejarse con cuidado.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'The N+1 Select Problem',
            'source' => 'Doctrine ORM Best Practices',
            'quote' => 'El problema N+1 es el antipatrón de rendimiento más frecuente en aplicaciones que utilizan ORMs. Se soluciona forzando una carga ansiosa (fetch join) en la consulta DQL.',
            'author' => 'Doctrine Project',
            'explanation' => 'Comprender la diferencia entre lazy y eager loading es vital para la escalabilidad de aplicaciones Symfony.',
        ],
        [
            'topic' => 'DQL Hydration Mechanics',
            'source' => 'High Performance PHP & Doctrine',
            'quote' => 'Al incluir la entidad relacionada en la cláusula SELECT de DQL, el hydrator de Doctrine puebla la propiedad con datos reales en lugar de un proxy diferido.',
            'author' => 'Marco Pivetta',
            'explanation' => 'Explica el mecanismo interno por el cual un JOIN FETCH erradica las consultas secundarias.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Doctrine Docs: DQL Select Queries & Joins',
            'url' => 'https://www.doctrine-project.org/projects/doctrine-orm/en/current/reference/dql-doctrine-query-language.html#joins',
            'description' => 'Documentación oficial sobre sintaxis DQL, INNER JOIN, LEFT JOIN y fetch joins.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Profiler: Inspecting Doctrine Queries',
            'url' => 'https://symfony.com/doc/current/profiler.html',
            'description' => 'Guía sobre cómo utilizar la barra de depuración y el profiler para auditar consultas SQL.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Doctrine Paginator Tool',
            'url' => 'https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/pagination.html',
            'description' => 'Tutorial oficial sobre cómo paginar consultas complejas con fetch joins de forma segura.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Constructor DQL con JOIN FETCH para Erradicar N+1',
        'objective' => 'Implementar la clase OptimizedDqlQueryBuilder con el método buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string, validando que $joins no esté vacío (lanzando InvalidArgumentException), e incluyendo la cláusula SELECT y los INNER JOINs correspondientes.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'OptimizedDqlQueryBuilder\' en el namespace \'App\\Doctrine\\Query\'.
3. Implementa el método \'buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string\'.
4. Valida si \'$joins\' está vacío (empty($joins)); si es así, lanza una \'InvalidArgumentException\'.
5. Construye la cláusula \'SELECT\' conteniendo el alias raíz y todos los alias de las relaciones.
6. Construye las cláusulas \'INNER JOIN\' para cada relación en \'$joins\' y retorna la sentencia DQL completa.',
        'filename' => 'src/Doctrine/Query/OptimizedDqlQueryBuilder.php',
        'guide' => [
            'explanation' => 'En buildJoinFetchDql, valida if (empty($joins)) throw new \\InvalidArgumentException(\'...\');. Une los alias con array_merge([$rootAlias], array_values($joins)). Genera SELECT ... FROM ... INNER JOIN ... y retorna el string concatenado.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase OptimizedDqlQueryBuilder con el método buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe validarse si el array $joins está vacío lanzando InvalidArgumentException.',
            ],
            [
                'text' => '[Pista 3: Snippet] if (empty($joins)) throw new \\InvalidArgumentException(\'Joins no puede estar vacío\'); $select = \'SELECT \' . implode(\', \', array_merge([$rootAlias], array_values($joins)));.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Query;

use InvalidArgumentException;

class OptimizedDqlQueryBuilder
{
    /**
     * @param array<string, string> $joins
     */
    public function buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string
    {
        // TODO: Valida que joins no esté vacío y construye sentencia DQL con SELECT y INNER JOINs
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Doctrine\\Query;

use InvalidArgumentException;

class OptimizedDqlQueryBuilder
{
    /**
     * @param array<string, string> $joins
     */
    public function buildJoinFetchDql(string $rootEntity, string $rootAlias, array $joins): string
    {
        if (empty($joins)) {
            throw new InvalidArgumentException(\'El array de joins no puede estar vacío.\');
        }

        $selectAliases = array_merge([$rootAlias], array_values($joins));
        $select = sprintf(\'SELECT %s\', implode(\', \', $selectAliases));
        $from = sprintf(\'FROM %s %s\', $rootEntity, $rootAlias);

        $joinClauses = [];
        foreach ($joins as $relation => $alias) {
            $joinClauses[] = sprintf(\'INNER JOIN %s.%s %s\', $rootAlias, $relation, $alias);
        }

        return sprintf(\'%s %s %s\', $select, $from, implode(\' \', $joinClauses));
    }
}
',
        'explanation' => 'OptimizedDqlQueryBuilder construye dinámicamente sentencias DQL con JOIN FETCH, incorporando los alias en la cláusula SELECT y emitiendo los INNER JOINs necesarios para traer las relaciones en una única consulta consolidada.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Detección & Mitigación de Queries N+1 con JOIN FETCH y DQL',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿En qué consiste exactamente el problema de las consultas N+1 en Doctrine ORM?',
                'options' => [
                    'a' => 'En que la base de datos se niega a ejecutar más de N consultas por minuto.',
                    'b' => 'En ejecutar 1 consulta inicial para la entidad principal y luego N consultas individuales secundarias en un bucle para cargar las relaciones diferidas (Lazy Loading).',
                    'c' => 'En que una tabla tiene más de N columnas indexadas.',
                    'd' => 'En un fallo de memoria al compilar el contenedor de servicios.',
                ],
                'correct' => 'b',
                'explanation' => 'Si cargas 100 pedidos y luego accedes a $order->getCustomer() dentro de un bucle, Doctrine disparará 100 consultas SQL separadas si no se usó JOIN FETCH, multiplicando la latencia de red de forma innecesaria.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cómo le indica una consulta DQL a Doctrine que debe hidratar una relación de forma ansiosa (Eager Loading)?',
                'options' => [
                    'a' => 'Añadiendo el alias de la entidad relacionada en la cláusula SELECT (ej. \'SELECT o, c FROM Order o JOIN o.customer c\').',
                    'b' => 'Añadiendo la palabra clave FORCE INDEX en la consulta SQL.',
                    'c' => 'Invocando $em->clear() antes de ejecutar la consulta.',
                    'd' => 'Usando el método setMaxResults(1).',
                ],
                'correct' => 'a',
                'explanation' => 'En DQL, incluir el alias de la relación en el SELECT instruye al hydrator a poblar los atributos de la entidad relacionada inmediatamente en lugar de colocar un objeto proxy diferido.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por qué no es recomendable configurar \'fetch="EAGER"\' de forma global en los atributos de la entidad?',
                'options' => [
                    'a' => 'Porque Doctrine no lo soporta a partir de PHP 8.1.',
                    'b' => 'Porque forzará la carga de la relación en el 100% de las consultas de esa entidad en toda la aplicación, degradando el rendimiento de endpoints ligeros que no necesitan esos datos.',
                    'c' => 'Porque inhabilita el uso de claves foráneas en la base de datos.',
                    'd' => 'Porque borra automáticamente la caché de OpCache.',
                ],
                'correct' => 'b',
                'explanation' => 'La carga ansiosa global rompe la flexibilidad. Es una mejor práctica mantener las relaciones en LAZY en la entidad y aplicar JOIN FETCH explícito en el repositorio solo cuando la pantalla o endpoint lo necesite.',
            ],
        ],
    ],
];
