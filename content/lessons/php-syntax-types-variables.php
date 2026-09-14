<?php

declare(strict_types=1);

return [
    'slug' => 'php-syntax-types-variables',
    'title' => 'Sintaxis, Tipos Primitivos & Tipado Estricto',
    'module' => 'PHP Moderno',
    'minutes' => 35,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'La base de cualquier lenguaje es cómo representa y procesa la información en memoria. PHP 8.4 es un lenguaje con soporte completo de tipado estricto (declare(strict_types=1);), permitiendo que el motor y las herramientas de análisis estático verifiquen que un entero nunca se confunda con un string o un booleano.',
        'problem' => 'En PHP tradicional sin tipado estricto, la coerción implícita (type juggling) convierte tipos sin avisar: "100" + 20 da 120, pero "100 manzanas" + 20 causaba comportamientos inesperados. En plataformas financieras o de comercio, las comparaciones flojas (==) o conversiones de strings vacíos a 0 causan graves fallas de lógica y brechas de seguridad.',
        'solution' => 'Declarar siempre declare(strict_types=1); en la primera línea de cada archivo, utilizar tipos escalares primitivos (int, float, string, bool) en argumentos y retornos de funciones, y diferenciar claramente entre variables mutables ($variable) y constantes inmutables (const).',
        'problem_label' => 'El Problema: Coerción Implícita (Type Juggling) y Errores Silenciosos',
        'solution_label' => 'La Solución Senior: Tipado Estricto (strict_types=1) y Contratos Tipados',
    ],
    'mental_model' => [
        'title' => 'Las Cajas Etiquetadas y el Inspector de Aduanas',
        'analogy' => 'Imagina una aduana de mercancías con cajas etiquetadas. Con tipado débil, si una caja dice "Líquidos" pero alguien le mete una piedra, el inspector se encoge de hombros e intenta usar la piedra como líquido más adelante. Con tipado estricto (declare(strict_types=1);), el inspector de aduanas examina cada caja: si la etiqueta dice int, solo acepta enteros. Si alguien intenta pasar un float o un string, la aduana detiene el envío inmediatamente con un TypeError, impidiendo que el error explote cuando el cliente ya esté pagando en producción.',
        'ascii_diagram' => 'LLAMADA: calculateTax("100", 0.16);
                 │
  ┌──────────────┴──────────────┐
  ▼                             ▼
[MODO DÉBIL (Sin strict_types)] [MODO ESTRICTO (strict_types=1)]
"100" es convertido a 100.0     TypeError: Argument #1 ($subtotal)
silenciosamente.                must be of type float, string given!
Efectos secundarios ocultos.    ¡Falla rápida en desarrollo (Fail-Fast)!',
        'key_concept' => 'El tipado estricto no es una limitación: es tu primer test automatizado, ejecutado en tiempo real por el motor antes de procesar una sola línea de lógica.',
    ],
    'internals' => [
        'title' => 'Los 4 Tipos Escalares Primitivos en PHP 8.4',
        'steps' => [
            [
                'phase' => '1. Enteros (int)',
                'description' => 'Representan números sin decimales (positivos, negativos o cero). En sistemas de 64 bits ocupan 8 bytes (desde -9 trillones hasta +9 trillones). Permiten separadores legibles: 1_000_000.',
            ],
            [
                'phase' => '2. Decimales / Coma Flotante (float)',
                'description' => 'Representan números con parte fraccionaria conforme al estándar IEEE 754. Nota: para cálculos financieros de alta precisión, se prefieren enteros en centavos o la extensión bcmath.',
            ],
            [
                'phase' => '3. Cadenas de Texto (string)',
                'description' => 'Secuencias de bytes. PHP soporta interpolación con comillas dobles ("Hola $nombre") y cadenas literales con comillas simples (\'Texto sin variables\').',
            ],
            [
                'phase' => '4. Booleanos (bool)',
                'description' => 'Solo admiten los valores true o false (insensibles a mayúsculas). Son la base de toda bifurcación lógica.',
            ],
            [
                'phase' => '5. El Valor Especial null',
                'description' => 'Representa la ausencia total de valor. En tipos modernos se especifica con prefijo de nullable: ?string o la unión string|null.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'declare(strict_types=1): Dónde y Cómo Funciona',
            'icon' => 'shield',
            'content' => 'La directiva declare(strict_types=1); debe colocarse como la primera instrucción de un archivo PHP (antes de cualquier namespace o código ejecutable).
            
Su alcance es por archivo (file-level): afecta a todas las llamadas a funciones y métodos que se originen dentro de dicho archivo. Si el llamador tiene strict_types activo, PHP no realizará coerción de tipos para argumentos escalares (con la única excepción de poder pasar un int a un parámetro que espera float).',
            'code' => '<?php

declare(strict_types=1);

function addPoints(int $current, int $earned): int
{
    return $current + $earned;
}

// addPoints(100, "50"); // ¡TypeError inmediato!
addPoints(100, 50); // Correcto: 150',
            'takeaways' => 'Todo archivo moderno en Symfony o PHP profesional debe comenzar con declare(strict_types=1);. Es un estándar no negociable de calidad.',
        ],
        [
            'title' => 'Constantes vs Variables: Cuándo Usar Cada Una',
            'icon' => 'layers',
            'content' => 'Las variables ($precio = 100) representan datos mutables cuyo valor cambia durante la ejecución. Las constantes (const TAX_RATE = 0.21;) representan valores inmutables conocidos en tiempo de compilación.
            
Dentro de clases, const permite definir reglas de negocio fijas sin gastar memoria por cada instancia:

const MIN_AGE = 18;
const DEFAULT_CURRENCY = \'EUR\';',
            'takeaways' => 'Si un valor nunca debe cambiar durante la vida de la petición, modelalo como const, no como una variable.',
        ],
    ],
    'video' => [
        'title' => 'PHP 8 Types & Strict Typing Deep Dive',
        'speaker' => 'Brent Roose (Stitcher.io)',
        'youtube_id' => 'k3y6pA9gXzE',
        'duration' => '25 min',
        'description' => 'Explicación detallada del sistema de tipos en PHP 8, por qué el tipado estricto previene bugs y cómo diseñar contratos robustos.',
        'chapters' => [
            '00:00' => 'Introducción a los tipos en PHP',
            '05:10' => 'Type Juggling vs Strict Types',
            '12:30' => 'Tipos primitivos y tipos compuestos',
            '20:00' => 'Mejores prácticas en producción',
        ],
    ],
    'architecture_code' => [
        'filename' => 'InvoiceCalculator.php',
        'title' => 'Cálculo Financiero con Tipado Estricto',
        'tag' => 'PHP 8.4 Clean Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Calculadora financiera básica para emisión de facturas.
 * Demuestra el uso estricto de tipos escalares (float, string).
 */
final readonly class InvoiceCalculator
{
    public const DEFAULT_CURRENCY = \'USD\';

    /**
     * Calcula el total con impuestos aplicados.
     */
    public function calculateTotal(float $subtotal, float $taxRate): float
    {
        if ($subtotal < 0.0) {
            throw new InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        if ($taxRate < 0.0 || $taxRate > 1.0) {
            throw new InvalidArgumentException(\'La tasa de impuesto debe estar entre 0.0 y 1.0.\');
        }

        return round($subtotal + ($subtotal * $taxRate), 2);
    }

    /**
     * Formatea un importe numérico con divisa para presentación.
     */
    public function formatCurrency(float $amount, string $currency = self::DEFAULT_CURRENCY): string
    {
        return sprintf(\'%s %s\', $currency, number_format($amount, 2));
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un programador novato confía en que PHP "adivine" el tipo de los datos. Un ingeniero Senior hace los contratos explícitos: define tipos estrictos en cada firma de método, lanza excepciones cuando las invariantes se rompen y nunca permite que un valor nulo o inesperado circule sin control.',
        'critical_questions' => [
            '¿Todos mis archivos tienen declare(strict_types=1); al inicio?',
            '¿Podría un valor negativo romper este cálculo financiero?',
            '¿Estoy usando float donde debería usar centavos enteros para evitar errores de redondeo?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Calcular el monto final de una compra sumando el precio y el impuesto.',
        'junior' => [
            'approach' => 'No declara tipos ni strict_types. Pasa strings provenientes del formulario directamente a la suma: $total = $_POST[\'price\'] + $_POST[\'tax\'].',
            'flaws' => [
                'Si el usuario envía "abc" o un string malformado, PHP emite warnings o produce resultados numéricos corruptos en la base de datos.',
            ],
        ],
        'senior' => [
            'approach' => 'Valida los tipos en el punto de entrada, castea a tipos primitivos seguros (float o int) y pasa argumentos estrictamente tipados a un servicio dedicado.',
            'rationale' => [
                'Garantiza integridad matemática, facilita pruebas unitarias y previene inyecciones o bugs de coerción.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Tipado Estricto en PHP',
            'source' => 'PHP Documentation: Type Declarations',
            'quote' => 'El tipado estricto previene conversiones inesperadas que pueden ocultar errores de lógica en tiempo de ejecución.',
            'author' => 'PHP Documentation Group',
            'explanation' => 'Al forzar tipos exactos, el desarrollador detecta las incoherencias durante las pruebas o la compilación inicial.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Manual Oficial de PHP: Tipos Escalares',
            'url' => 'https://www.php.net/manual/es/language.types.declarations.php',
            'description' => 'Guía completa sobre declaración de tipos escalares y modo estricto en PHP.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Calculadora de Facturación con Tipado Estricto',
        'objective' => 'Implementar la clase InvoiceCalculator dentro del namespace App\\Fundamentals con tipado estricto, que reciba subtotales y tasas de impuestos y calcule totales formateados.',
        'instructions' => 'Crea la clase InvoiceCalculator en el namespace App\\Fundamentals. Debe incluir declare(strict_types=1); e implementar dos métodos públicos: calculateTotal(float $subtotal, float $taxRate): float (valida que subtotal >= 0 y taxRate entre 0 y 1, retornando subtotal + (subtotal * taxRate) redondeado a 2 decimales con round()) y formatCurrency(float $amount, string $currency = "USD"): string (retorna la divisa y el importe con 2 decimales usando number_format o sprintf).',
        'filename' => 'InvoiceCalculator.php',
        'guide' => [
            'explanation' => 'Este reto establece la disciplina de programación en PHP moderno: tipado estricto, validación de parámetros de entrada y retorno garantizado.',
            'steps' => [
                'Paso 1: Inicia el archivo con declare(strict_types=1);.',
                'Paso 2: Declara namespace App\\Fundamentals; y class InvoiceCalculator.',
                'Paso 3: Implementa calculateTotal(float $subtotal, float $taxRate): float. Si $subtotal < 0 o $taxRate < 0, lanza InvalidArgumentException.',
                'Paso 4: Retorna round($subtotal + ($subtotal * $taxRate), 2).',
                'Paso 5: Implementa formatCurrency(float $amount, string $currency = "USD"): string retornando "$currency " seguido de number_format($amount, 2).',
            ],
            'useful_functions' => [
                [
                    'name' => 'round(float $val, int $precision)',
                    'desc' => 'Redondea un número en coma flotante a la cantidad de decimales indicada.',
                ],
                [
                    'name' => 'number_format(float $num, int $decimals)',
                    'desc' => 'Formatea un número con decimales y separador de miles.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Validación de Argumentos',
                'text' => 'Protege la función contra valores negativos lanzando InvalidArgumentException.',
                'snippet' => 'if ($subtotal < 0.0 || $taxRate < 0.0) { throw new \InvalidArgumentException(\'Parámetros inválidos\'); }',
            ],
            [
                'label' => 'Formateo de Moneda',
                'text' => 'Puedes usar sprintf o concatenación directa con number_format.',
                'snippet' => 'return $currency . \' \' . number_format($amount, 2);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Calculadora de Facturación Básica.
 */
class InvoiceCalculator
{
    /**
     * Calcula el total con impuestos aplicados.
     */
    public function calculateTotal(float $subtotal, float $taxRate): float
    {
        // PASO 1: Valida que $subtotal no sea negativo y que $taxRate esté entre 0 y 1
        // PASO 2: Retorna el subtotal más el impuesto redondeado a 2 decimales
        return 0.0;
    }

    /**
     * Formatea el importe con la divisa especificada.
     */
    public function formatCurrency(float $amount, string $currency = \'USD\'): string
    {
        // PASO 3: Retorna la divisa seguida de un espacio y el número formateado con 2 decimales
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Calculadora de Facturación Básica con Tipado Estricto.
 */
class InvoiceCalculator
{
    public function calculateTotal(float $subtotal, float $taxRate): float
    {
        if ($subtotal < 0.0) {
            throw new InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        if ($taxRate < 0.0 || $taxRate > 1.0) {
            throw new InvalidArgumentException(\'La tasa de impuesto debe estar entre 0.0 y 1.0.\');
        }

        return round($subtotal + ($subtotal * $taxRate), 2);
    }

    public function formatCurrency(float $amount, string $currency = \'USD\'): string
    {
        return $currency . \' \' . number_format($amount, 2);
    }
}
',
        'explanation' => 'La clase InvoiceCalculator modela un cálculo financiero simple pero robusto: rechaza datos negativos mediante excepciones de argumento y devuelve resultados precisos y estrictamente tipados.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Sintaxis y Tipado Estricto en PHP',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es el efecto exacto de declarar declare(strict_types=1); al inicio de un archivo?',
                'options' => [
                    'a' => 'Hace que PHP se compile en lenguaje C antes de ejecutarse.',
                    'b' => 'Obliga al motor a rechazar conversiones implícitas de tipos escalares en las llamadas a funciones dentro de ese archivo.',
                    'c' => 'Convierte todas las variables globales en constantes inmutables.',
                    'd' => 'Deshabilita el recolector de basura de la memoria.',
                ],
                'correct' => 'b',
                'explanation' => 'declare(strict_types=1); desactiva la coerción implícita de tipos escalares para las invocaciones realizadas en dicho archivo, lanzando un TypeError si los tipos no coinciden exactamente.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál de los siguientes NO es un tipo primitivo escalar en PHP?',
                'options' => [
                    'a' => 'int',
                    'b' => 'bool',
                    'c' => 'array',
                    'd' => 'float',
                ],
                'correct' => 'c',
                'explanation' => 'array es un tipo compuesto (compound type), mientras que int, float, string y bool son tipos escalares primitivos.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué ocurre al intentar mutar una constante definida con la palabra clave const en PHP?',
                'options' => [
                    'a' => 'PHP emite un error fatal en tiempo de compilación impidiendo la modificación.',
                    'b' => 'La constante cambia de valor pero genera una advertencia en los logs.',
                    'c' => 'El valor se duplica en memoria creando una nueva variable.',
                    'd' => 'PHP convierte la constante en un array asociativo.',
                ],
                'correct' => 'a',
                'explanation' => 'Las constantes son estrictamente inmutables; cualquier intento de reasignación produce un error sintáctico o de compilación inmediato.',
            ],
        ],
    ],
];
