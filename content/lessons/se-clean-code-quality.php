<?php

declare(strict_types=1);

return [
    'slug' => 'se-clean-code-quality',
    'title' => 'Clean Code, Cohesión & Acoplamiento',
    'module' => 'Ingeniería de Software',
    'minutes' => 50,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'Clean Code no es una cuestión estética o de formato de llaves; es una disciplina de diseño orientada a minimizar la Complejidad Cognitiva del cerebro humano y mantener el costo del cambio constante a lo largo de los años.',
        'problem' => 'El código espagueti con estructuras de control profundamente anidadas (Arrow Anti-Pattern / Pirámide de la Muerte), métodos de 300 líneas y nombres de variables crípticos ralentiza el desarrollo exponencialmente y causa incidentes críticos cada vez que se introduce una nueva regla de negocio.',
        'solution' => 'Aplicar Cláusulas de Guarda (Early Return / Bouncer Pattern) para mantener la indentación plana, respetar el Principio de Responsabilidad Única (alta cohesión) y desacoplar componentes mediante contratos explícitos.',
        'problem_label' => 'El Problema: La Pirámide de if/else y la Deuda Técnica Acumulada',
        'solution_label' => 'La Solución Senior: Cláusulas de Guarda (Bouncer Pattern) y Alta Cohesión',
    ],
    'mental_model' => [
        'title' => 'El Portero de Discoteca (Bouncer Pattern & Guard Clauses)',
        'analogy' => 'Imagina una discoteca exclusiva. Si no hubiera portero en la entrada, tendrías que dejar entrar a todo el mundo, llevarlos por el pasillo, bajarlos al sótano, y solo después de 5 puertas cerradas pedirles su boleto. Si no lo tienen, los echas. Eso es exactamente un código lleno de `if` anidados. En cambio, un portero inteligente se para en la puerta principal: ¿No tienes ticket? Rechazado de inmediato (`return false;`). ¿Eres menor de edad? Rechazado de inmediato (`return false;`). ¿El local está lleno? Rechazado (`return false;`). Solo si superas todos los filtros de entrada, accedes a la pista de baile (`return true;`). En Clean Code esto se llama **Cláusulas de Guarda (Early Return)**: mantén el código plano a nivel 1 de indentación y rechaza las condiciones inválidas al principio.',
        'ascii_diagram' => 'PIRÁMIDE DE LA MUERTE (JUNIOR)            CLÁUSULAS DE GUARDA / BOUNCER (SENIOR)
if ($customerVerified) {                  if (!$isCustomerVerified) {
    if ($amount > 0) {                        return false; // Portero 1: fuera
        if ($amount <= 10000) {           }
            if ($itemsCount >= 1) {       if ($amount <= 0 || $amount > 10000) {
                if ($itemsCount <= 50) {      return false; // Portero 2: fuera
                    return true;          }
                }                         if ($itemsCount < 1 || $itemsCount > 50) {
            }                                 return false; // Portero 3: fuera
        }                                 }
    }                                     return true; // Camino feliz limpio y plano
}',
        'key_concept' => 'Las Cláusulas de Guarda (Early Return) reducen la carga cognitiva a cero: una vez superada la condición de error, el cerebro del programador se olvida de ella para siempre.',
    ],
    'internals' => [
        'title' => 'Métricas de Calidad de Código: Complejidad y Acoplamiento',
        'steps' => [
            [
                'phase' => '1. Complejidad Ciclomática vs Complejidad Cognitiva',
                'description' => 'La complejidad ciclomática cuenta los caminos de ejecución lineal (bifurcaciones). La Complejidad Cognitiva (inventada por SonarSource) mide qué tan difícil es para un cerebro humano entender el flujo. Cada nivel de anidamiento suma penalización exponencial.',
            ],
            [
                'phase' => '2. Cláusulas de Guarda (Early Returns)',
                'description' => 'Invertir las condiciones negativas para salir inmediatamente de la función (fail-fast) y eliminar completamente los bloques else redundantes.',
            ],
            [
                'phase' => '3. Cohesión: El Principio de Responsabilidad Única',
                'description' => 'Una clase tiene alta cohesión si todos sus métodos operan sobre el mismo estado interno del objeto. Si una clase valida pagos, envía emails y guarda en base de datos, tiene baja cohesión y viola SRP.',
            ],
            [
                'phase' => '4. Acoplamiento: La Ley de Demeter (Principio de Mínimo Conocimiento)',
                'description' => 'Un objeto solo debe hablar con sus amigos directos, nunca con los amigos de sus amigos: evita cadenas como $order->getCustomer()->getAddress()->getCountry()->getIsoCode().',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'La Ley de Demeter y por qué encadenar getters es un olor de código',
            'icon' => 'shield',
            'content' => 'La Ley de Demeter establece que un método solo debe invocar métodos de:
1. El propio objeto (`$this`).
2. Los objetos pasados como argumentos al método.
3. Los objetos instanciados dentro del método.
4. Las dependencias inyectadas en las propiedades de la clase.

Cuando escribes `$order->getUser()->getWallet()->deduct($amount)`, el método de la orden conoce la estructura interna íntima del usuario y de su billetera. Si mañana la billetera cambia a un servicio externo de tokens, 30 archivos que hacían esa llamada se rompen al mismo tiempo (Shotgun Surgery). La solución senior es delegar: `$order->pay()` y que la orden se encargue de hablar directamente solo con el usuario.',
            'takeaways' => 'Encadenar más de dos getters consecutivos (tren de llamadas) casi siempre delata una violación de la Ley de Demeter y un acoplamiento indebido.',
        ],
        [
            'title' => 'Análisis Estático con PHPStan a Nivel 9',
            'icon' => 'cpu',
            'content' => 'PHPStan no solo busca errores de tipado; inspecciona el AST (Abstract Syntax Tree) para detectar código inalcanzable, condiciones imposibles y violaciones de diseño.

En un pipeline profesional, se configuran reglas estrictas para:
• Prohibir métodos con Complejidad Cognitiva superior a 8.
• Prohibir condicionales if anidados más allá del nivel 2.
• Exigir tipado estricto `declare(strict_types=1);` en el 100% de los archivos.',
            'code' => '// Regla de oro en PHPStan: tipos de array estrictos
/**
 * @param list<int> $ids
 * @return array<string, OrderItem>
 */
public function findIndexedItems(array $ids): array',
            'takeaways' => 'Automatiza la calidad en el pipeline con PHPStan y PHP-CS-Fixer; las revisiones de código humanas deben centrarse en arquitectura, no en estilo ni sintaxis.',
        ],
    ],
    'video' => [
        'title' => 'Crafting Custom PHPStan Rules & Static Analysis',
        'speaker' => 'Ondřej Mirtes (Creador de PHPStan)',
        'youtube_id' => 'r6oc4Ctor4c',
        'duration' => '43 min',
        'description' => 'Ondřej Mirtes enseña cómo inspeccionar el AST con PHPStan para detectar acoplamientos, violaciones de tipos y code smells antes de llegar a producción.',
        'chapters' => [
            '00:00' => 'Arquitectura interna del AST en PHP',
            '10:15' => 'Cómo PHPStan modela tipos complejos',
            '22:30' => 'Creación de una regla personalizada de arquitectura',
            '34:40' => 'Integración en pipelines de CI/CD',
        ],
    ],
    'architecture_code' => [
        'filename' => 'CleanOrderValidator.php',
        'title' => 'Validador de Órdenes con Cláusulas de Guarda (Clean Code)',
        'tag' => 'Clean Architecture Pattern',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Validador de órdenes comerciales implementado bajo principios de Clean Code.
 * Aplica Cláusulas de Guarda (Bouncer Pattern) para eliminar el anidamiento de if
 * y mantener una complejidad cognitiva mínima.
 */
final readonly class CleanOrderValidator
{
    private const float MIN_AMOUNT = 0.01;
    private const float MAX_AMOUNT = 10000.0;
    private const int MIN_ITEMS = 1;
    private const int MAX_ITEMS = 50;

    /**
     * Determina si la orden cumple con las reglas de negocio para su procesamiento.
     *
     * @param float $amount Monto total de la orden en moneda base.
     * @param int $itemsCount Cantidad total de artículos en el carrito.
     * @param bool $isCustomerVerified Indicador de verificación antifraude del cliente.
     */
    public function isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool
    {
        // Guarda 1: El cliente debe estar verificado contra fraude
        if (!$isCustomerVerified) {
            return false;
        }

        // Guarda 2: El monto debe encontrarse dentro de los límites económicos permitidos
        if ($amount < self::MIN_AMOUNT || $amount > self::MAX_AMOUNT) {
            return false;
        }

        // Guarda 3: La cantidad de ítems debe estar dentro de la capacidad de empaque
        if ($itemsCount < self::MIN_ITEMS || $itemsCount > self::MAX_ITEMS) {
            return false;
        }

        // Camino feliz: todas las guardas fueron superadas con éxito
        return true;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior ve los `if` anidados como una señal de advertencia inmediata de que la función está haciendo demasiadas cosas o que el programador no razonó las condiciones de error como barreras tempranas. Prefiere funciones de 10 líneas planas que cualquier método \'inteligente\' de 100 líneas con múltiples niveles de sangría.',
        'critical_questions' => [
            '¿Puedo reemplazar este bloque if/else con una cláusula de guarda que retorne inmediatamente?',
            '¿Esta clase tiene una sola razón para cambiar o está mezclando validación con persistencia y notificaciones?',
            '¿Qué nivel de complejidad cognitiva reporta PHPStan sobre este método?',
            '¿Los nombres de los métodos revelan intención clara sin necesidad de comentarios explicativos?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Validar si una orden de compra puede ser procesada según monto, ítems y estado del cliente.',
        'junior' => [
            'approach' => 'Escribe un `if ($isCustomerVerified)` principal, dentro un `if ($amount > 0)`, dentro un `if ($amount <= 10000)`, dentro un `if ($itemsCount >= 1)` y retorna true al fondo del embudo con 4 bloques `else` al final.',
            'flaws' => [
                'La complejidad cognitiva sube a 10. Si se agrega una nueva condición de cupón de descuento, se añade otro nivel de indentación y resulta casi imposible cubrir todas las ramas con tests unitarios.',
            ],
        ],
        'senior' => [
            'approach' => 'Aplica Cláusulas de Guarda: comprueba cada condición de fallo de forma independiente y retorna `false` de inmediato. Si todas las guardas se superan, la última línea es simplemente `return true;`.',
            'rationale' => [
                'Complejidad cognitiva reducida a 1. El código se lee como una lista de verificación natural y cada guarda es trivialmente testeable de forma aislada.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'La Regla del Boy Scout',
            'source' => 'Clean Code: A Handbook of Agile Software Craftsmanship',
            'quote' => 'Deja el campamento más limpio de como lo encontraste. En cada commit, el código debe quedar ligeramente mejor diseñado que antes.',
            'author' => 'Robert C. Martin (Uncle Bob)',
            'explanation' => 'La calidad no se logra parando el desarrollo tres meses para reescribir todo, sino mediante micro-refactorizaciones continuas respaldadas por tests.',
        ],
        [
            'topic' => 'Legibilidad del Código',
            'source' => 'Refactoring: Improving the Design of Existing Code',
            'quote' => 'Cualquier tonto puede escribir código que una computadora entienda. Los buenos programadores escriben código que los humanos pueden entender.',
            'author' => 'Martin Fowler',
            'explanation' => 'El código en producción pasa el 80% de su ciclo de vida siendo leído y mantenido por otros ingenieros. Optimizar para legibilidad reduce drásticamente los incidentes.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Refactoring.guru: Replace Nested Conditional with Guard Clauses',
            'url' => 'https://refactoring.guru/es/replace-nested-conditional-with-guard-clauses',
            'description' => 'Guía visual del patrón de Cláusulas de Guarda para eliminar la pirámide de condicionales.',
            'type' => 'GUIDE',
        ],
        [
            'title' => 'PHPStan Documentation: Rule Levels and Static Analysis',
            'url' => 'https://phpstan.org/user-guide/rule-levels',
            'description' => 'Documentación oficial sobre los niveles de análisis estático y detección de complejidad en PHP.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Validador de Órdenes Limpio con Cláusulas de Guarda',
        'objective' => 'Implementar la clase CleanOrderValidator con el método isValidOrder() aplicando estrictamente Cláusulas de Guarda (Early Return) y prohibiendo cualquier anidamiento de condicionales if.',
        'instructions' => 'Crea la clase CleanOrderValidator en el namespace App\\Engineering. Implementa el método público isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool. La orden es válida si: 1) $isCustomerVerified es true; 2) $amount es estrictamente mayor a 0 y menor o igual a 10000; 3) $itemsCount es mayor o igual a 1 y menor o igual a 50. REGLA ESTRICTA: Está terminantemente prohibido anidar un if dentro de otro if.',
        'filename' => 'CleanOrderValidator.php',
        'guide' => [
            'explanation' => 'En este reto vas a ejercitar el Bouncer Pattern. Tu objetivo es convertir un validador con potencial de pirámide de if en un conjunto de barreras planas e independientes. Cada guarda revisa si la condición falla y retorna false inmediatamente. Al final de la función, colocas el return true plano.',
            'steps' => [
                'Paso 1: Declara <code>class CleanOrderValidator</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: En <code>isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool</code>, agrega la Guarda 1: si <code>!$isCustomerVerified</code>, retorna <code>false</code>.',
                'Paso 3: Agrega la Guarda 2: si <code>$amount <= 0.0 || $amount > 10000.0</code>, retorna <code>false</code>.',
                'Paso 4: Agrega la Guarda 3: si <code>$itemsCount < 1 || $itemsCount > 50</code>, retorna <code>false</code>.',
                'Paso 5: Si ninguna guarda disparó un rechazo, retorna <code>true</code> al final del método.',
                'Paso 6: Asegúrate de que no haya ninguna llave \'{\' de if conteniendo otro if dentro.',
            ],
            'useful_functions' => [
                [
                    'name' => 'Early Return',
                    'desc' => 'Técnica de control de flujo donde se sale de la función inmediatamente al detectar una precondición no cumplida.',
                ],
                [
                    'name' => 'Operador || (OR lógico)',
                    'desc' => 'Permite agrupar las violaciones de límite inferior y superior en una sola guarda plana.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'El Enfoque del Portero',
                'text' => 'Piensa en negativo: en lugar de preguntar si el cliente es válido para entrar, pregunta si NO es válido para sacarlo de inmediato.',
                'snippet' => 'if (!$isCustomerVerified) {
    return false;
}',
            ],
            [
                'label' => 'Validación de Rangos',
                'text' => 'Puedes evaluar el monto en una sola línea combinando el menor o igual a 0 y el mayor a 10000 con el operador ||.',
                'snippet' => 'if ($amount <= 0 || $amount > 10000) {
    return false;
}',
            ],
            [
                'label' => 'El Retorno Final',
                'text' => 'Cuando todas las guardas negativas han sido evaluadas sin dispararse, significa que todos los datos son válidos.',
                'snippet' => 'return true;',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Validador de órdenes comerciales implementado bajo principios de Clean Code.
 * REQUISITO: Cero condicionales if anidados. Usa Cláusulas de Guarda.
 */
class CleanOrderValidator
{
    /**
     * Valida si una orden es admisible para procesamiento.
     */
    public function isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool
    {
        // GUARDA 1: Verifica si el cliente no está verificado
        // GUARDA 2: Verifica si el monto está fuera del rango (<= 0 o > 10000)
        // GUARDA 3: Verifica si los ítems están fuera del rango (< 1 o > 50)
        // CAMINO FELIZ: Retorna true
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior con Cláusulas de Guarda (Bouncer Pattern).
 */
class CleanOrderValidator
{
    public function isValidOrder(float $amount, int $itemsCount, bool $isCustomerVerified): bool
    {
        if (!$isCustomerVerified) {
            return false;
        }

        if ($amount <= 0 || $amount > 10000) {
            return false;
        }

        if ($itemsCount < 1 || $itemsCount > 50) {
            return false;
        }

        return true;
    }
}
',
        'explanation' => 'La solución aplica el patrón de Cláusulas de Guarda de forma impecable: tres barreras de fallo rápido sin ningún nivel de anidamiento ni bloques else. Esto mantiene la complejidad cognitiva en 1 y hace que el código sea inmediatamente legible y extensible.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Clean Code, Cohesión y Complejidad',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué las Cláusulas de Guarda (Early Return) son superiores a los bloques if/else anidados?',
                'options' => [
                    'a' => 'Porque consumen menos memoria RAM en el Zend Engine.',
                    'b' => 'Porque reducen la Complejidad Cognitiva al manejar y descartar los casos de error temprano, dejando el flujo principal plano y lineal.',
                    'c' => 'Porque PHP 8.4 no permite más de 2 niveles de if en su compilador.',
                    'd' => 'Porque convierten las funciones automáticamente en asíncronas.',
                ],
                'correct' => 'b',
                'explanation' => 'Las cláusulas de guarda permiten que el lector mentalmente descarte las condiciones de error inmediatamente, reduciendo la carga en la memoria de trabajo del cerebro.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué describe con mayor precisión la Ley de Demeter (Principio de Mínimo Conocimiento)?',
                'options' => [
                    'a' => 'Una clase solo debe tener un máximo de 10 líneas de código.',
                    'b' => 'Un objeto solo debe comunicarse con sus colaboradores directos y nunca invocar métodos en objetos devueltos por otros colaboradores ($a->getB()->getC()->doSomething()).',
                    'c' => 'Toda consulta a la base de datos debe usar Doctrine DQL en lugar de SQL plano.',
                    'd' => 'Las variables globales deben declararse siempre con tipos estrictos.',
                ],
                'correct' => 'b',
                'explanation' => 'La Ley de Demeter previene el acoplamiento estructural profundo, evitando cadenas de llamadas a través de múltiples capas de objetos.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué caracteriza a una clase con Alta Cohesión?',
                'options' => [
                    'a' => 'Tiene más de 50 métodos públicos para cubrir todas las necesidades del sistema.',
                    'b' => 'Todos sus métodos y propiedades colaboran estrechamente para cumplir un único propósito de negocio bien definido.',
                    'c' => 'Depende de al menos 10 bundles diferentes de Symfony.',
                    'd' => 'Hereda de múltiples clases base abstractas.',
                ],
                'correct' => 'b',
                'explanation' => 'Alta cohesión significa que los elementos de una clase están fuertemente relacionados y enfocados en una única responsabilidad, haciendo la clase reutilizable y testeable.',
            ],
        ],
    ],
];
