<?php

declare(strict_types=1);

return [
    'slug' => 'php-control-flow-functions',
    'title' => 'Control de Flujo, Expresiones Match & Funciones Tipadas',
    'module' => 'PHP Moderno',
    'minutes' => 40,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'El control de flujo determina los caminos de ejecución de un programa. PHP 8 introdujo la expresión match, una alternativa moderna, estricta y segura a switch que evalúa con igualdad estricta (===), retorna valores como expresión y es exhaustiva por diseño.',
        'problem' => 'El switch tradicional utiliza comparación débil (==), permitiendo que 0 == "gratis" sea true y ejecutando ramas equivocadas. Además, requiere break obligatorio en cada caso; un break olvidado causa ejecución en cascada (fall-through) con efectos desastrosos en transacciones.',
        'solution' => 'Utilizar la expresión match para mapeos deterministas de estado, aplicar Cláusulas de Guarda (Early Return) en condicionales if/else para eliminar el anidamiento profundo, y estructurar la lógica en funciones puras con tipado estricto.',
        'problem_label' => 'El Problema: La Trampa de Switch y la Pirámide de Ifs Anidados',
        'solution_label' => 'La Solución Senior: Expresión Match Exhaustiva y Cláusulas de Guarda',
    ],
    'mental_model' => [
        'title' => 'El Despachador de Trenes: Match vs Switch',
        'analogy' => 'Imagina una estación de trenes. Con el antiguo switch, el operador mira el billete de forma descuidada: si dice "0", asume que cualquier tren que tenga texto "gratis" puede pasar por la misma vía. Si además el operador olvida poner la barrera (break), el tren avanza y choca con los siguientes 3 andenes. Con la expresión match de PHP 8, cada vía tiene un lector láser de alta precisión (===): solo pasa el tren con el identificador exacto. Y como match es una expresión, no solo desvía el tren, sino que entrega el resultado directamente en una sola línea sin peligro de cascada.',
        'ascii_diagram' => 'ENTRADA: $tier = "VIP";
      │
      ├── match ($tier) ───────────────> Retorna 0.20 (Asignación directa)
      │   "STANDARD" => 0.0,
      │   "PREMIUM"  => 0.10,
      │   "VIP"      => 0.20,
      │   default    => 0.0
      │
      └── Sin necesidad de break, seguro contra fall-through, comparación ===',
        'key_concept' => 'Match evalúa con === estricto y retorna un valor directamente. Si ningún caso coincide y no hay default, lanza un UnhandledMatchError impidiendo estados indefinidos.',
    ],
    'internals' => [
        'title' => 'Evolución del Control de Flujo en PHP',
        'steps' => [
            [
                'phase' => '1. Cláusulas de Guarda (Early Return)',
                'description' => 'En lugar de anidar if ($valido) { if ($activo) { ... } }, valida los errores primero y retorna inmediatamente: if (!$valido) return false; if (!$activo) return false; Mantén el código plano.',
            ],
            [
                'phase' => '2. Expresión Match vs Declaración Switch',
                'description' => 'Match es una expresión (puede asignarse a una variable o retornarse). Evalúa con identidad estricta (===), no requiere break y soporta múltiples condiciones separadas por coma: "ADMIN", "ROOT" => true.',
            ],
            [
                'phase' => '3. Funciones con Argumentos Nombrados',
                'description' => 'En PHP 8+, puedes invocar createUser(name: "Dilan", role: "ADMIN", isActive: true). Ya no dependes del orden posicional para entender qué significa cada argumento booleano.',
            ],
            [
                'phase' => '4. Funciones Flecha (Arrow Functions)',
                'description' => 'Sintaxis compacta fn ($x) => $x * 2 para callbacks de una sola expresión, capturando automáticamente las variables del ámbito exterior por valor.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Por qué Match es Superior a Switch',
            'icon' => 'zap',
            'content' => 'Considera este caso clásico de bug en switch:

$status = 0;
switch ($status) {
    case \'pendiente\': // \'pendiente\' se convertía a 0 en PHP 7!
        echo \'Es pendiente\';
        break;
}

Con match:
match ($status) {
    \'pendiente\' => \'Es pendiente\', // No coincide: 0 === \'pendiente\' es FALSE!
    0 => \'Es numérico cero\',
};

Match previene bugs de coerción y garantiza tipado estricto en la evaluación.',
            'takeaways' => 'Usa match siempre que necesites mapear un valor a un resultado o cuando la lógica dependa de una variable discreta.',
        ],
    ],
    'video' => [
        'title' => 'PHP 8 Match Expression & Named Arguments',
        'speaker' => 'Gary Clarke',
        'youtube_id' => 'k3y6pA9gXzE',
        'duration' => '18 min',
        'description' => 'Tutorial práctico de cómo simplificar condicionales en PHP moderno usando match y argumentos nombrados.',
        'chapters' => [
            '00:00' => 'El problema con switch en PHP clásico',
            '04:30' => 'Sintaxis de match',
            '09:15' => 'Comparación estricta y exhaustividad',
            '14:00' => 'Refactorización de código real',
        ],
    ],
    'architecture_code' => [
        'filename' => 'CustomerDiscountEvaluator.php',
        'title' => 'Evaluador de Descuentos con Expresión Match',
        'tag' => 'PHP 8.4 Clean Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Servicio de evaluación de descuentos comerciales.
 * Demuestra control de flujo plano y expresiones match.
 */
final readonly class CustomerDiscountEvaluator
{
    /**
     * Retorna el porcentaje de descuento según el nivel del cliente.
     */
    public function getDiscountPercentage(string $customerTier): float
    {
        return match (strtoupper(trim($customerTier))) {
            \'STANDARD\' => 0.0,
            \'PREMIUM\'  => 0.10,
            \'VIP\'      => 0.20,
            default    => 0.0,
        };
    }

    /**
     * Aplica el descuento correspondiente sobre un monto base.
     */
    public function applyDiscount(float $amount, string $customerTier): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException(\'El monto base no puede ser negativo.\');
        }

        $discountRate = $this->getDiscountPercentage($customerTier);

        return round($amount * (1.0 - $discountRate), 2);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un código con 5 niveles de if anidados es una trampa mortal para el mantenimiento. Un Senior utiliza cláusulas de guarda para salir temprano y expresiones match para transformar estados limpiamente, manteniendo la complejidad ciclomática al mínimo.',
        'critical_questions' => [
            '¿Puedo reemplazar este switch o cadena de if/else con una sola expresión match?',
            '¿Estoy validando los casos de error al principio del método (Early Return)?',
            '¿Qué ocurre si el argumento no coincide con ningún caso esperado?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Calcular un descuento según el tipo de cliente.',
        'junior' => [
            'approach' => 'Escribe un switch largo con variables intermedias y olvida el break en uno de los casos.',
            'flaws' => [
                'El flujo cae en cascada y aplica un descuento VIP a usuarios estándar.',
            ],
        ],
        'senior' => [
            'approach' => 'Utiliza match con default explícito y normaliza la entrada con strtoupper(trim()).',
            'rationale' => [
                'Cero riesgo de fall-through, comparación estrictamente tipada y código conciso de una sola línea.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Cláusulas de Guarda & Early Return',
            'source' => 'Refactoring: Improving the Design of Existing Code',
            'quote' => 'Si tienes dos caminos de los cuales uno es una condición anormal, verifícala y sal de la función inmediatamente. No hagas esperar al lector.',
            'author' => 'Martin Fowler',
            'explanation' => 'Las cláusulas de guarda reducen la carga cognitiva al eliminar el anidamiento innecesario.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Manual Oficial de PHP: Expresión Match',
            'url' => 'https://www.php.net/manual/es/control-structures.match.php',
            'description' => 'Documentación canónica sobre la expresión match en PHP 8.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Evaluador de Descuentos con Expresión Match',
        'objective' => 'Implementar la clase CustomerDiscountEvaluator en App\\Fundamentals utilizando match para determinar tasas de descuento comerciales.',
        'instructions' => 'Crea la clase CustomerDiscountEvaluator en el namespace App\\Fundamentals con declare(strict_types=1);. Implementa getDiscountPercentage(string $customerTier): float (usando match con "STANDARD" => 0.0, "PREMIUM" => 0.10, "VIP" => 0.20, default => 0.0; tolera mayúsculas/minúsculas con strtoupper y trim) y applyDiscount(float $amount, string $customerTier): float (valida que $amount >= 0 lanzando InvalidArgumentException, calcula $amount * (1.0 - $discount) y retorna con round a 2 decimales).',
        'filename' => 'CustomerDiscountEvaluator.php',
        'guide' => [
            'explanation' => 'Aprenderás a estructurar lógica de negocio sin caer en la trampa de switches obsoletos o condicionales anidados.',
            'steps' => [
                'Paso 1: Declara namespace App\\Fundamentals; con tipado estricto.',
                'Paso 2: En getDiscountPercentage(string $customerTier): float, usa match sobre strtoupper(trim($customerTier)).',
                'Paso 3: Devuelve 0.0 para STANDARD, 0.10 para PREMIUM, 0.20 para VIP y 0.0 como default.',
                'Paso 4: En applyDiscount(float $amount, string $customerTier): float, valida que $amount >= 0; si no, lanza InvalidArgumentException.',
                'Paso 5: Obtén la tasa llamando a getDiscountPercentage y retorna round($amount * (1.0 - $rate), 2).',
            ],
            'useful_functions' => [
                [
                    'name' => 'strtoupper(string $string)',
                    'desc' => 'Convierte una cadena de texto a mayúsculas para normalizar entradas.',
                ],
                [
                    'name' => 'trim(string $string)',
                    'desc' => 'Elimina espacios en blanco accidentales al inicio y final.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Estructura de la expresión match',
                'text' => 'Recuerda que match es una expresión y termina con punto y coma (;).',
                'snippet' => 'return match (strtoupper(trim($customerTier))) {
    \'STANDARD\' => 0.0,
    \'PREMIUM\'  => 0.10,
    \'VIP\'      => 0.20,
    default    => 0.0,
};',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Evaluador de Descuentos para Clientes.
 */
class CustomerDiscountEvaluator
{
    /**
     * Retorna el porcentaje de descuento (0.0 a 0.20) según el tier.
     */
    public function getDiscountPercentage(string $customerTier): float
    {
        // PASO 1: Usa la expresión match para evaluar el tier normalizado (STANDARD, PREMIUM, VIP)
        return 0.0;
    }

    /**
     * Aplica el descuento sobre el importe base.
     */
    public function applyDiscount(float $amount, string $customerTier): float
    {
        // PASO 2: Valida que $amount no sea negativo
        // PASO 3: Aplica la tasa de descuento y redondea a 2 decimales
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Evaluador de Descuentos para Clientes.
 */
class CustomerDiscountEvaluator
{
    public function getDiscountPercentage(string $customerTier): float
    {
        return match (strtoupper(trim($customerTier))) {
            \'STANDARD\' => 0.0,
            \'PREMIUM\'  => 0.10,
            \'VIP\'      => 0.20,
            default    => 0.0,
        };
    }

    public function applyDiscount(float $amount, string $customerTier): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException(\'El monto no puede ser negativo.\');
        }

        $rate = $this->getDiscountPercentage($customerTier);

        return round($amount * (1.0 - $rate), 2);
    }
}
',
        'explanation' => 'CustomerDiscountEvaluator utiliza la expresión match de PHP 8 para lograr una asignación de descuentos limpia, segura e insensible a fall-throughs.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Control de Flujo y Expresión Match',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la diferencia fundamental en la comparación entre match y switch?',
                'options' => [
                    'a' => 'Match utiliza igualdad estricta (===), mientras que switch utiliza igualdad débil (==).',
                    'b' => 'Switch solo admite enteros y match solo admite strings.',
                    'c' => 'Match requiere la instrucción break al final de cada caso.',
                    'd' => 'No hay diferencia; match es simplemente un alias de switch.',
                ],
                'correct' => 'a',
                'explanation' => 'Match evalúa los casos con estricta identidad de tipo y valor (===), eliminando los errores de coerción accidental de switch.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué ocurre si una expresión match no encuentra ningún caso coincidente y no se definió un default?',
                'options' => [
                    'a' => 'Retorna null de forma silenciosa.',
                    'b' => 'Lanza una excepción UnhandledMatchError deteniendo la ejecución errónea.',
                    'c' => 'Ejecuta el primer caso de la lista.',
                    'd' => 'Reinicia el script PHP.',
                ],
                'correct' => 'b',
                'explanation' => 'Match es exhaustiva: si el valor no coincide con ningún patrón y no existe la rama default, PHP lanza un UnhandledMatchError.',
            ],
            [
                'id' => 'q3',
                'question' => '¿En qué consiste el principio de Cláusula de Guarda (Early Return)?',
                'options' => [
                    'a' => 'En obligar a que toda función tenga al menos 100 líneas de código.',
                    'b' => 'En validar los casos de error o condiciones de salida al inicio del método para evitar niveles profundos de indentación if/else.',
                    'c' => 'En guardar copias de seguridad de las variables en archivos de texto.',
                    'd' => 'En evitar el uso de funciones y escribir todo en un solo bloque principal.',
                ],
                'correct' => 'b',
                'explanation' => 'Early Return mantiene el código plano y fácil de razonar al despachar las excepciones y casos borde primero.',
            ],
        ],
    ],
];
