<?php

declare(strict_types=1);

return [
    'slug' => 'testing-phpunit-mastery',
    'title' => 'PHPUnit: Assertions, Test Doubles & Data Providers',
    'module' => 'testing',
    'minutes' => 50,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'PHPUnit es el marco de pruebas estandar de facto en el ecosistema PHP moderno. Dominar PHPUnit a nivel Senior requiere superar el simple uso de $this->assertTrue() y comprender la arquitectura de eventos introducida en PHPUnit 10/11, el uso riguroso de Data Providers para pruebas parametrizadas y la clasificacion formal de Test Doubles segun el modelo de Gerard Meszaros: Dummy, Stub, Spy, Mock y Fake.

Un error comun en equipos de desarrollo es tratar a todos los dobles de prueba como \'Mocks\'. El uso indiscriminado de mocks estrictos genera suites de pruebas sobre-especificadas que se rompen ante cualquier cambio en el orden de las llamadas internas de un metodo, aniquilando la capacidad de refactorizar el codigo. El ingeniero Senior utiliza Stubs para proveer datos predefinidos y reserva las expectativas de Mocks unicamente para comandos salientes criticos.',
        'problem' => 'PHPUnit es el marco de pruebas estandar de facto en el ecosistema PHP moderno. Dominar PHPUnit a nivel Senior requiere superar el simple uso de $this->assertTrue() y comprender la arquitectura de eventos introducida en PHPUnit 10/11, el uso riguroso de Data Providers para pruebas parametrizadas y la clasificacion formal de Test Doubles segun el modelo de Gerard Meszaros: Dummy, Stub, Spy, Mock y Fake.',
        'solution' => 'Un error comun en equipos de desarrollo es tratar a todos los dobles de prueba como \'Mocks\'. El uso indiscriminado de mocks estrictos genera suites de pruebas sobre-especificadas que se rompen ante cualquier cambio en el orden de las llamadas internas de un metodo, aniquilando la capacidad de refactorizar el codigo. El ingeniero Senior utiliza Stubs para proveer datos predefinidos y reserva las expectativas de Mocks unicamente para comandos salientes criticos.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Taxonomia del Doble de Accion en el Cine',
        'concept' => 'Piensa en la filmacion de una pelicula de accion. El actor principal (el objeto bajo prueba o SUT) debe interactuar con diversos personajes secundarios segun la escena:
1. DUMMY (Maniqui): Un muñeco en el fondo de la escena que solo se coloca para ocupar espacio en un asiento. Nunca dice nada ni interactua; solo satisface la firma del constructor (argumento obligatorio).
2. STUB (Cartel con Respuestas): Un personaje que tiene un guion fijo. Si el protagonista le pregunta la hora, siempre responde \'10:30 AM\', sin consultar ningun reloj real.
3. SPY (Detective Encubierto): Actua como un Stub, pero en su libreta anota silenciosamente cuantas veces el protagonista le hablo y que palabras exactas utilizo, permitiendo verificar los hechos a posteriori.
4. MOCK (Director Estricto): Exige un ensayo previo exacto. Espera que le digas una frase precisa en el minuto 2 y con el tono exacto. Si el orden de las palabras cambia aunque el resultado sea el mismo, detiene la filmacion (falla el test).
5. FAKE (Automovil de Madera): Un vehiculo completamente funcional para rodar por la calle del estudio, pero construido con madera y motor electrico simple en lugar de un motor V8 real (como un repositorio en memoria con un array asociativo).',
        'ascii_diagram' => 'TAXONOMIA FORMAL DE TEST DOUBLES (Gerard Meszaros):

                  +-------------------------------------------------+
                  |              TEST DOUBLE (Generico)             |
                  +-------------------------------------------------+
                                           |
         +-----------------+---------------+----------------+----------------+
         |                 |                                |                |
         v                 v                                v                v
   +-----------+     +-----------+                    +-----------+    +-----------+
   |   DUMMY   |     |   STUB    |                    |   MOCK    |    |   FAKE    |
   +-----------+     +-----------+                    +-----------+    +-----------+
   | Solo para |     | Retorna   |                    | Verifica  |    | Implementa|
   | rellenar  |     | datos     |                    | llamadas  |    | logica real|
   | firmas de |     | enlatados |                    | y orden   |    | simplificada|
   | metodos   |     | prefijados|                    | estricto  |    | en memoria|
   +-----------+     +-----------+                    +-----------+    +-----------+
                           |
                           v
                     +-----------+
                     |    SPY    |
                     +-----------+
                     | Registra  |
                     | llamadas  |
                     | para post-|
                     | asercion  |
                     +-----------+
',
        'analogy' => 'Piensa en la filmacion de una pelicula de accion. El actor principal (el objeto bajo prueba o SUT) debe interactuar con diversos personajes secundarios segun la escena:
1. DUMMY (Maniqui): Un muñeco en el fondo de la escena que solo se coloca para ocupar espacio en un asiento. Nunca dice nada ni interactua; solo satisface la firma del constructor (argumento obligatorio).
2. STUB (Cartel con Respuestas): Un personaje que tiene un guion fijo. Si el protagonista le pregunta la hora, siempre responde \'10:30 AM\', sin consultar ningun reloj real.
3. SPY (Detective Encubierto): Actua como un Stub, pero en su libreta anota silenciosamente cuantas veces el protagonista le hablo y que palabras exactas utilizo, permitiendo verificar los hechos a posteriori.
4. MOCK (Director Estricto): Exige un ensayo previo exacto. Espera que le digas una frase precisa en el minuto 2 y con el tono exacto. Si el orden de las palabras cambia aunque el resultado sea el mismo, detiene la filmacion (falla el test).
5. FAKE (Automovil de Madera): Un vehiculo completamente funcional para rodar por la calle del estudio, pero construido con madera y motor electrico simple en lugar de un motor V8 real (como un repositorio en memoria con un array asociativo).',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En versiones anteriores a PHPUnit 10, las extensiones y oyentes dependian de interfaces mutables que compartian estado global durante la ejecucion de la suite. En PHPUnit 10 y 11, Sebastian Bergmann reescribio el motor para adoptar un modelo puramente orientado a eventos inmutables (Event System).',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Cuando se genera un Mock mediante createMock(), PHPUnit compila dinamicamente en memoria una subclase de la clase o interfaz objetivo utilizando la funcion eval(). Esta subclase intercepta cada llamada a metodo y la coteja contra una cola interna de expectativas (InvocationOrder).',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Abusar de mocks no solo incrementa el consumo de memoria en la Zend VM, sino que acopla la prueba a la estructura de clases del momento.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Los Data Providers en PHP moderno deben implementarse utilizando generadores (yield) o arrays asociativos con nombres descriptivos en las claves. Al nombrar cada dataset (por ejemplo, \'calculo_con_tarifa_plana\' o \'descuento_aplicado_a_clientes_vip\'), si una asercion falla en CI, el informe de PHPUnit indica inmediatamente el nombre del escenario sin obligar al desarrollador a inspeccionar los indices numericos del proveedor.',
        ],
    ],
    'video' => [
        'title' => 'From Events to Insights: Testing and Documenting Event‑Based Software',
        'speaker' => 'Sebastian Bergmann (Creador de PHPUnit)',
        'youtube_id' => 'CySblzGly_U',
        'duration' => '48 min',
        'description' => 'Sebastian Bergmann explica en PHP UK Conference la evolucion de PHPUnit hacia una arquitectura basada en eventos inmutables y las mejores tecnicas para evitar el acoplamiento excesivo con dobles de prueba.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Tests\\Unit;

use PHPUnit\\Framework\\Attributes\\DataProvider;
use PHPUnit\\Framework\\Attributes\\Test;
use PHPUnit\\Framework\\TestCase;
use App\\Billing\\TieredPricingCalculator;
use InvalidArgumentException;

final class TieredPricingCalculatorTest extends TestCase
{
    private TieredPricingCalculator $calculator;

    protected function setUp(): void
    {
        $this->calculator = new TieredPricingCalculator();
    }

    #[Test]
    #[DataProvider(\'providePricingTiers\')]
    public function it_calculates_correct_price_per_tier(
        int $units,
        float $unitPrice,
        float $expectedTotal
    ): void {
        $actual = $this->calculator->calculateTotal($units, $unitPrice);
        $this->assertSame($expectedTotal, $actual);
    }

    /**
     * @return iterable<string, array{int, float, float}>
     */
    public static function providePricingTiers(): iterable
    {
        yield \'unidades_menores_a_10_sin_descuento\' => [5, 100.0, 500.0];
        yield \'limite_inferior_tramo_1_descuento_10\' => [10, 100.0, 900.0];
        yield \'tramo_intermedio_descuento_10\' => [25, 100.0, 2250.0];
        yield \'limite_superior_tramo_1_descuento_10\' => [49, 100.0, 4410.0];
        yield \'limite_tramo_2_descuento_20\' => [50, 100.0, 4000.0];
        yield \'tramo_masivo_descuento_20\' => [100, 100.0, 8000.0];
    }

    #[Test]
    public function it_rejects_negative_or_zero_units(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->calculator->calculateTotal(0, 50.0);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior crea Mocks para absolutamente cada clase colaboradora, incluso para DTOs y Value Objects inmutables. El desarrollador Senior utiliza instancias reales siempre que sean rapidas y deterministas (Value Objects, Entidades, algoritmos puros), emplea Fakes en memoria para repositorios, y reserva los Mocks unicamente para colaboradores externos que realizan operaciones de I/O con efectos secundarios irreversibles (pasarelas de pago, servicios de envio de SMS).',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Copia y pega la misma logica de prueba con 10 valores distintos cambiando una sola variable en cada metodo de prueba.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Utiliza Data Providers con generadores yield y datasets nombrados, garantizando pruebas exhaustivas, mantenibles y autodocumentadas.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Taxonomia de Dobles de Prueba',
            'source' => 'xUnit Test Patterns: Refactoring Test Code',
            'quote' => 'Un Test Double es un termino generico para cualquier caso en el que reemplazas un objeto de produccion por motivos de prueba. Tratar a todos como Mocks es la causa primordial de pruebas fragiles.',
            'author' => 'Gerard Meszaros',
            'explanation' => 'Diferenciar entre Stubs y Mocks protege a la suite contra el sobre-acoplamiento.',
        ],
        [
            'topic' => 'Mocks y Aislamiento',
            'source' => 'Growing Object-Oriented Software, Guided by Tests',
            'quote' => 'Solo haz mock de tus propios tipos; nunca hagas mock de tipos que no te pertenecen como librerias de terceros o clases de framework.',
            'author' => 'Steve Freeman & Nat Pryce',
            'explanation' => 'Hacer mock de clases externas produce falsas asunciones que fallan silenciosamente en tiempo de ejecucion real.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHPUnit 11 Manual: Test Doubles',
            'url' => 'https://docs.phpunit.de/en/11.0/test-doubles.html',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Martin Fowler: Mocks Aren\'t Stubs',
            'url' => 'https://martinfowler.com/articles/mocksArentStubs.html',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Motor de Precios Escalonados (TieredPricingCalculator)',
        'objective' => 'Implementar la clase TieredPricingCalculator con validacion rigurosa de invariantes y calculo de tramos escalonados de descuento para satisfacer las aserciones de pruebas automatizadas en PHPUnit.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Challenge.
2. Implementa la clase TieredPricingCalculator con el metodo calculateTotal(int $units, float $unitPrice): float.
3. Si $units <= 0 o $unitPrice < 0.0, lanza \\InvalidArgumentException con un mensaje descriptivo.
4. Aplica la siguiente logica de negocio: de 1 a 9 unidades: 0% descuento; de 10 a 49 unidades: 10% descuento; de 50 unidades o mas: 20% descuento.
5. Calcula el subtotal ($units * $unitPrice) y aplica la tasa de descuento calculada, retornando el total resultante.',
        'filename' => 'src/Challenge/TieredPricingCalculator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida las invariantes al inicio del metodo y lanza InvalidArgumentException ante valores invalidos.',
                'Paso 2: Utiliza una expresion match(true) para evaluar los rangos de unidades de mayor a menor.',
                'Paso 3: Multiplica las unidades por el precio y por el factor complementario (1.0 - $discountRate).',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El calculo aplica un descuento global porcentual segun el volumen total adquirido.',
            ],
            [
                'text' => '[Pista 2: Estructura] Emplea match(true) { $units >= 50 => 0.20, $units >= 10 => 0.10, default => 0.0 }.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($units <= 0 || $unitPrice < 0.0) { throw new \\InvalidArgumentException(\'Units and price must be positive\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Challenge;

use InvalidArgumentException;

class TieredPricingCalculator
{
    public function calculateTotal(int $units, float $unitPrice): float
    {
        // TODO: Validar que $units > 0 y $unitPrice >= 0.0 (lanzar \\InvalidArgumentException)
        // TODO: 1 a 9 unidades: 0% descuento
        // TODO: 10 a 49 unidades: 10% descuento
        // TODO: 50 o mas unidades: 20% descuento
        // TODO: Retornar subtotal con descuento aplicado
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Challenge;

use InvalidArgumentException;

class TieredPricingCalculator
{
    public function calculateTotal(int $units, float $unitPrice): float
    {
        if ($units <= 0 || $unitPrice < 0.0) {
            throw new \\InvalidArgumentException(\'Units and price must be positive\');
        }

        $discountRate = match (true) {
            $units >= 50 => 0.20,
            $units >= 10 => 0.10,
            default => 0.0,
        };

        $subtotal = $units * $unitPrice;

        return $subtotal * (1.0 - $discountRate);
    }
}
',
        'explanation' => 'La solucion protege las invariantes aritmeticas arrojando InvalidArgumentException si se ingresan unidades nulas o precios negativos. Utiliza match(true) para evaluar limpiamente los tramos de descuento sin bloques if anidados.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: PHPUnit: Assertions, Test Doubles & Data Providers',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la diferencia fundamental entre un Stub y un Mock segun Martin Fowler y Gerard Meszaros?',
                'options' => [
                    'a' => 'Un Stub siempre se escribe en C++ mientras que un Mock se escribe en PHP puro.',
                    'b' => 'Un Stub provee respuestas prefabricadas a llamadas recibidas (verificacion de estado), mientras que un Mock espera recibir llamadas especificas y verifica la interaccion (verificacion de comportamiento).',
                    'c' => 'Un Stub solo se utiliza en pruebas funcionales y un Mock unicamente en pruebas unitarias.',
                    'd' => 'No existe diferencia; son sinonimos exactos en el estandar PSR-12.',
                ],
                'correct' => 'b',
                'explanation' => 'Los Stubs devuelven valores fijos para que el objeto bajo prueba continue su ejecucion sin fallar. Los Mocks ademas verifican activamente que el objeto bajo prueba haya emitido comandos con argumentos exactos.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por que se desaconseja hacer Mock de Value Objects inmutables (como Money o Email)?',
                'options' => [
                    'a' => 'Porque PHP no permite instanciar clases readonly dentro del directorio de pruebas.',
                    'b' => 'Porque los Value Objects son rapidos, deterministicos, no tienen dependencias externas y usar la instancia real otorga mayor fidelidad sin complejidad.',
                    'c' => 'Porque PHPUnit falla con error fatal si se hace mock de un metodo que retorne string.',
                    'd' => 'Porque los Value Objects deben ser siempre persistidos en base de datos antes de ser leidos.',
                ],
                'correct' => 'b',
                'explanation' => 'Hacer mock de un objeto simple sin dependencias agrega codigo innecesario y oculta posibles errores reales en el comportamiento de dicho objeto.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cual es la ventaja principal de utilizar generadores yield con claves string en Data Providers de PHPUnit?',
                'options' => [
                    'a' => 'Permite ejecutar las pruebas de manera asincrona utilizando hilos de Node.js.',
                    'b' => 'Consume minima memoria al no instanciar todos los datasets a la vez y nombra explicitamente cada caso de prueba en el reporte de fallos.',
                    'c' => 'Evita que se verifique el tipado estricto declare(strict_types=1).',
                    'd' => 'Obliga a PHPUnit a ejecutar las pruebas en orden alfabetico inverso.',
                ],
                'correct' => 'b',
                'explanation' => 'Al usar yield con nombres descriptivos en las claves, el desarrollador identifica al instante el escenario exacto que provoco la falla en CI sin tener que rastrear indices numericos genericos.',
            ],
        ],
    ],
];
