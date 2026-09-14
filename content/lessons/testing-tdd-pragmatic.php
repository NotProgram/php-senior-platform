<?php

declare(strict_types=1);

return [
    'slug' => 'testing-tdd-pragmatic',
    'title' => 'TDD Pragmatico en Symfony',
    'module' => 'testing',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El Desarrollo Guiado por Pruebas (TDD, Test-Driven Development) es una de las disciplinas tecnicas mas incomprendidas en la industria del software. Con frecuencia se ensena como un dogma rigido de \'escribir una prueba unitaria para cada metodo privado antes de codificarlo\'. Esta interpretacion (a menudo denominada \'TDD dogmatico\' o de la escuela de Londres mal aplicada) produce cientos de pruebas acopladas a detalles efimeros de implementacion que impiden refactorizar el sistema sin que todo el suite falle.

El TDD Pragmatico (basado en la vision original de Kent Beck y refinado por ingenieros como Ian Cooper) establece que la prueba debe escribirse contra el comportamiento del caso de uso o la frontera publica de un modulo. El ciclo Rojo-Verde-Refactor tiene un proposito claro: \'Rojo\' define el contrato y la necesidad de negocio; \'Verde\' hace que funcione de la forma mas directa posible; \'Refactor\' es donde ocurre la verdadera ingenieria de diseno, eliminando duplicaciones, extrayendo Value Objects y aplicando patrones bajo la proteccion de una prueba verde que no cambia.',
        'problem' => 'El Desarrollo Guiado por Pruebas (TDD, Test-Driven Development) es una de las disciplinas tecnicas mas incomprendidas en la industria del software. Con frecuencia se ensena como un dogma rigido de \'escribir una prueba unitaria para cada metodo privado antes de codificarlo\'. Esta interpretacion (a menudo denominada \'TDD dogmatico\' o de la escuela de Londres mal aplicada) produce cientos de pruebas acopladas a detalles efimeros de implementacion que impiden refactorizar el sistema sin que todo el suite falle.',
        'solution' => 'El TDD Pragmatico (basado en la vision original de Kent Beck y refinado por ingenieros como Ian Cooper) establece que la prueba debe escribirse contra el comportamiento del caso de uso o la frontera publica de un modulo. El ciclo Rojo-Verde-Refactor tiene un proposito claro: \'Rojo\' define el contrato y la necesidad de negocio; \'Verde\' hace que funcione de la forma mas directa posible; \'Refactor\' es donde ocurre la verdadera ingenieria de diseno, eliminando duplicaciones, extrayendo Value Objects y aplicando patrones bajo la proteccion de una prueba verde que no cambia.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Anclaje del Escalador en Pared de Roca',
        'concept' => 'Imagina a un escalador que asciende por una pared vertical de roca en una montana alpina:
1. FASE ROJA (Fijar el anclaje): El escalador busca una grieta solida en la roca y coloca un mosqueton de seguridad por encima de su cabeza. La cuerda queda anclada, pero aun no ha subido el cuerpo (la prueba falla porque la funcionalidad no existe).
2. FASE VERDE (Subir el paso): Da el salto y coloca los pies en la siguiente repisa. El movimiento puede ser tosco o poco elegante, pero sus pies estan firmes y la cuerda lo sostiene (la prueba pasa, el requerimiento esta cumplido).
3. FASE DE REFACTORIZACION (Asegurar la postura y el equipo): Ahora que esta seguro y no puede caer al vacio, el escalador ajusta su centro de gravedad, reorganiza los mosquetones en su arnes y limpia el magnesio de sus manos. Mejora su postura sin cambiar de posicion en la pared.

Sin el anclaje previo (la prueba), si intentara hacer movimientos elegantes de refactorizacion y fallara el apoyo, caeria cientos de metros al vacio (el bug en produccion).',
        'ascii_diagram' => 'EL CICLO REAL DE TDD PRAGMATICO:

             +----------------------------------------------+
             |                                              |
             v                                              |
      [ 1. ROJO: Especificar ]                              |
      Escribe una prueba que defina un COMPORTAMIENTO       |
      observable del caso de uso.                           |
      (Falla por razones legitimas)                         |
             |                                              |
             v                                              |
      [ 2. VERDE: Funcionar ]                               |
      Escribe el codigo minimo para que pase.               |
      Permitido codigo ingenuo o duplicado.                 |
      (La prueba pasa)                                      |
             |                                              |
             v                                              |
      [ 3. REFACTORIZAR: Disenar ]                          |
      Extrae Value Objects, elimina code smells,            |
      aplica SOLID sin alterar el comportamiento.           |
      (La prueba DEBE continuar en verde)                   |
             |                                              |
             +----------------------------------------------+
',
        'analogy' => 'Imagina a un escalador que asciende por una pared vertical de roca en una montana alpina:
1. FASE ROJA (Fijar el anclaje): El escalador busca una grieta solida en la roca y coloca un mosqueton de seguridad por encima de su cabeza. La cuerda queda anclada, pero aun no ha subido el cuerpo (la prueba falla porque la funcionalidad no existe).
2. FASE VERDE (Subir el paso): Da el salto y coloca los pies en la siguiente repisa. El movimiento puede ser tosco o poco elegante, pero sus pies estan firmes y la cuerda lo sostiene (la prueba pasa, el requerimiento esta cumplido).
3. FASE DE REFACTORIZACION (Asegurar la postura y el equipo): Ahora que esta seguro y no puede caer al vacio, el escalador ajusta su centro de gravedad, reorganiza los mosquetones en su arnes y limpia el magnesio de sus manos. Mejora su postura sin cambiar de posicion en la pared.

Sin el anclaje previo (la prueba), si intentara hacer movimientos elegantes de refactorizacion y fallara el apoyo, caeria cientos de metros al vacio (el bug en produccion).',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'Durante el paso de Refactorizacion en TDD, las invariantes del dominio deben encapsularse de inmediato.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Por ejemplo, en un sistema de facturacion mensual prorrateada, una clase de servicio nunca debe operar con tipos primitivos flotantes descontrolados sin verificar dias validos de calendario (entre 28 y 31) o consumos no negativos.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'En PHP 8.4, TDD facilita modelar estos comportamientos limpiamente utilizando tipado estricto, Property Hooks para validaciones de propiedades y el lanzamiento explicito de excepciones InvalidArgumentException ante cualquier violacion.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Ian Cooper demostro que el mayor error de TDD es ligar las pruebas a las clases individuales en lugar de a las operaciones de negocio. Si tienes una clase OrderService que colabora internamente con OrderPricingCalculator y TaxRulePolicy, la prueba debe ejecutarse contra OrderService. Si mas adelante decides fusionar o dividir OrderPricingCalculator en tres clases, la prueba de OrderService seguira pasando sin tocar una sola linea del test. Esto otorga verdadera libertad de refactorizacion.',
        ],
    ],
    'video' => [
        'title' => 'TDD, Where Did It All Go Wrong',
        'speaker' => 'Ian Cooper (Conferencia DevTernity)',
        'youtube_id' => 'EZ05e7EMOLM',
        'duration' => '58 min',
        'description' => 'La conferencia mas influyente de la ultima decada sobre TDD. Ian Cooper rescata los conceptos originales de Kent Beck demostrando por que probar detalles de implementacion y hacer mock de clases internas destruyo la reputacion de TDD.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Billing;

use InvalidArgumentException;

/**
 * Calculadora de Prorrateo y Consumo Escalonado guiada por TDD Pragmatico.
 */
final class TieredBillingCalculator
{
    /**
     * Calcula el monto mensual prorrateado y suma los cargos por consumo escalonado.
     */
    public function calculateProratedAmount(
        float $monthlyRate,
        int $activeDays,
        int $totalDaysInMonth,
        int $consumedUnits
    ): float {
        if ($monthlyRate < 0.0) {
            throw new InvalidArgumentException(\'La tarifa mensual no puede ser negativa.\');
        }

        if ($totalDaysInMonth < 28 || $totalDaysInMonth > 31) {
            throw new InvalidArgumentException(\'El total de dias en el mes debe estar entre 28 y 31.\');
        }

        if ($activeDays < 1 || $activeDays > $totalDaysInMonth) {
            throw new InvalidArgumentException(\'Los dias activos deben estar entre 1 y el total de dias del mes.\');
        }

        if ($consumedUnits < 0) {
            throw new InvalidArgumentException(\'Las unidades consumidas no pueden ser negativas.\');
        }

        $proratedBase = ($monthlyRate / $totalDaysInMonth) * $activeDays;

        // Tramos de consumo escalonado
        $unitsCost = 0.0;
        if ($consumedUnits > 500) {
            // Primeros 100 incluidos (0.0), 400 unidades a 0.10 = 40.0, exceso a 0.25
            $unitsCost = 40.0 + (($consumedUnits - 500) * 0.25);
        } elseif ($consumedUnits > 100) {
            $unitsCost = ($consumedUnits - 100) * 0.10;
        }

        return round($proratedBase + $unitsCost, 2, PHP_ROUND_HALF_UP);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior salta inmediatamente al editor de codigo a escribir cientos de lineas sin saber si su interfaz es comoda de consumir. El desarrollador Senior utiliza TDD como una herramienta de diseno de interfaces: la prueba es el primer cliente de la API. Si la prueba es engorrosa de escribir, significa que el diseno de la clase es deficiente.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Escribe pruebas despues de haber terminado todo el codigo, solo para satisfacer la meta de cobertura de SonarQube.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Escribe la prueba primero para descubrir la interfaz mas intuitiva y refactoriza agresivamente sabiendo que los tests garantizan cero regresiones.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'TDD y Diseno de Software',
            'source' => 'Test-Driven Development by Example',
            'quote' => 'Escribir la prueba primero cambia tu perspectiva de como implementar el codigo a como el codigo sera utilizado por otros. Es primordialmente una herramienta de diseno.',
            'author' => 'Kent Beck',
            'explanation' => 'TDD fuerza a pensar en contratos, responsabilidades e invariantes antes de comprometerse con una implementacion.',
        ],
        [
            'topic' => 'Pruebas de Casos de Uso',
            'source' => 'TDD, Where Did It All Go Wrong (DevTernity)',
            'quote' => 'El objetivo de TDD es probar comportamientos, no clases. Si tus pruebas estan acopladas a la estructura de clases, cada refactorizacion requerira reescribir tus pruebas.',
            'author' => 'Ian Cooper',
            'explanation' => 'El acoplamiento estructural es el enemigo numero uno de la agilidad en desarrollo.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Kent Beck: Canonical TDD Principles',
            'url' => 'https://tidyfirst.substack.com/',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Martin Fowler: Test Driven Development',
            'url' => 'https://martinfowler.com/bliki/TestDrivenDevelopment.html',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Calculadora de Facturacion Escalonada con TDD (TieredBillingCalculator)',
        'objective' => 'Implementar TieredBillingCalculator guiado por pruebas: validar invariantes de calendario (dias entre 28 y 31), prorrateo de tarifa base y aplicacion de tramos de consumo escalonado con redondeo a 2 decimales.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Billing.
2. Implementa la clase TieredBillingCalculator con el metodo calculateProratedAmount(float $monthlyRate, int $activeDays, int $totalDaysInMonth, int $consumedUnits): float.
3. Valida estrictamente las invariantes: $monthlyRate >= 0, $totalDaysInMonth entre 28 y 31, $activeDays entre 1 y $totalDaysInMonth, y $consumedUnits >= 0; de lo contrario lanza InvalidArgumentException.
4. Calcula la base prorrateada: ($monthlyRate / $totalDaysInMonth) * $activeDays.
5. Calcula el costo escalonado de unidades: 0 a 100 unidades = 0.0; de 101 a 500 unidades = 0.10 por cada unidad adicional; por encima de 500 unidades = 40.0 + (unidades - 500) * 0.25.
6. Retorna la suma redondeada a 2 decimales con round(..., 2, PHP_ROUND_HALF_UP).',
        'filename' => 'src/Billing/TieredBillingCalculator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que $monthlyRate >= 0.0 y $consumedUnits >= 0.',
                'Paso 2: Comprueba que $totalDaysInMonth este en el rango [28, 31].',
                'Paso 3: Comprueba que $activeDays este en [1, $totalDaysInMonth].',
                'Paso 4: Calcula la proporcion de dias sobre la tarifa mensual.',
                'Paso 5: Aplica los tramos de 100 y 500 unidades con sus tasas correspondientes (0.10 y 0.25).',
                'Paso 6: Redondea a 2 decimales con PHP_ROUND_HALF_UP.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Los primeros 100 consumos son gratuitos. De 101 a 500 pagan 0.10 cada uno. A partir de 501 pagan 0.25.',
            ],
            [
                'text' => '[Pista 2: Estructura] Si $consumedUnits > 500 el costo base de los tramos anteriores es 400 * 0.10 = 40.0.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($totalDaysInMonth < 28 || $totalDaysInMonth > 31) { throw new InvalidArgumentException(\'El total de dias en el mes debe estar entre 28 y 31.\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Billing;

use InvalidArgumentException;

class TieredBillingCalculator
{
    public function calculateProratedAmount(
        float $monthlyRate,
        int $activeDays,
        int $totalDaysInMonth,
        int $consumedUnits
    ): float {
        // TODO: Validar invariantes estrictas de dias y tarifas (lanzando InvalidArgumentException)
        // TODO: Calcular base prorrateada por dias activos
        // TODO: Calcular cargo escalonado por unidades consumidas (tiers en 100 y 500)
        // TODO: Retornar suma redondeada a 2 decimales con PHP_ROUND_HALF_UP
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Billing;

use InvalidArgumentException;

class TieredBillingCalculator
{
    public function calculateProratedAmount(
        float $monthlyRate,
        int $activeDays,
        int $totalDaysInMonth,
        int $consumedUnits
    ): float {
        if ($monthlyRate < 0.0) {
            throw new InvalidArgumentException(\'La tarifa mensual no puede ser negativa.\');
        }

        if ($totalDaysInMonth < 28 || $totalDaysInMonth > 31) {
            throw new InvalidArgumentException(\'El total de dias en el mes debe estar entre 28 y 31.\');
        }

        if ($activeDays < 1 || $activeDays > $totalDaysInMonth) {
            throw new InvalidArgumentException(\'Los dias activos deben estar entre 1 y el total de dias del mes.\');
        }

        if ($consumedUnits < 0) {
            throw new InvalidArgumentException(\'Las unidades consumidas no pueden ser negativas.\');
        }

        $proratedBase = ($monthlyRate / $totalDaysInMonth) * $activeDays;

        $unitsCost = 0.0;
        if ($consumedUnits > 500) {
            $unitsCost = 40.0 + (($consumedUnits - 500) * 0.25);
        } elseif ($consumedUnits > 100) {
            $unitsCost = ($consumedUnits - 100) * 0.10;
        }

        return round($proratedBase + $unitsCost, 2, PHP_ROUND_HALF_UP);
    }
}
',
        'explanation' => 'La solucion valida exhaustivamente las reglas de negocio e invariantes de tiempo y consumo. La logica de tramos escalonados en 100 y 500 unidades garantiza una facturacion predecible y precisa, redondeando siempre con la constante estandar de PHP PHP_ROUND_HALF_UP.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: TDD Pragmatico en Symfony',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿En que fase del ciclo TDD (Rojo-Verde-Refactor) se debe aplicar el diseno de software, eliminacion de code smells y extraccion de patrones?',
                'options' => [
                    'a' => 'En la fase Roja, antes de que exista cualquier prueba.',
                    'b' => 'En la fase Verde, tratando de escribir el codigo perfecto a la primera.',
                    'c' => 'En la fase de Refactorizacion, una vez que la prueba esta en verde y garantiza la ausencia de regresiones.',
                    'd' => 'Nunca; el codigo de produccion nunca debe ser modificado tras pasar las pruebas.',
                ],
                'correct' => 'c',
                'explanation' => 'El refactoring solo puede realizarse con seguridad bajo una prueba verde. Intentar disenar la arquitectura perfecta en la fase verde suele generar sobre-ingenieria y paralisis.',
            ],
            [
                'id' => 'q2',
                'question' => 'Segun Ian Cooper, ¿por que es perjudicial escribir pruebas unitarias para cada metodo privado o clase interna?',
                'options' => [
                    'a' => 'Porque PHP no permite ejecutar metodos privados desde archivos con extension .php.',
                    'b' => 'Porque acopla las pruebas a los detalles de implementacion internos, haciendo que cualquier cambio de refactorizacion rompa los tests aunque el comportamiento publico siga intacto.',
                    'c' => 'Porque incrementa el consumo de memoria en la base de datos MySQL.',
                    'd' => 'Porque Sebastian Bergmann elimino los metodos privados en PHPUnit 11.',
                ],
                'correct' => 'b',
                'explanation' => 'Cuando las pruebas prueban la implementacion en lugar del comportamiento, los desarrolladores no pueden refactorizar libremente, perdiendo el beneficio central de contar con una suite automatizada.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cual es el proposito principal de obligar a que la prueba falle (Fase Roja) antes de escribir el codigo de produccion?',
                'options' => [
                    'a' => 'Comprobar que el compilador de PHP y el runner de PHPUnit estan instalados en el sistema operativo.',
                    'b' => 'Verificar que la prueba realmente es capaz de fallar y que no esta pasando accidentalmente por un falso positivo (como una asercion vacia o mal orientada).',
                    'c' => 'Garantizar que el repositorio Git rechace el commit mediante un pre-commit hook.',
                    'd' => 'Obligar al desarrollador a utilizar mocks en lugar de clases reales.',
                ],
                'correct' => 'b',
                'explanation' => 'Ver la prueba fallar por la razon exacta esperada prueba la validez de la prueba misma. Si pasa antes de escribir el codigo, la prueba no esta probando nada o contiene un error de logica.',
            ],
        ],
    ],
];
