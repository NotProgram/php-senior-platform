<?php

declare(strict_types=1);

return [
    'slug' => 'poo-composition-over-inheritance',
    'title' => 'Composition over Inheritance: Estrategias, Polimorfismo & Acoplamiento',
    'module' => 'POO & Modelado',
    'minutes' => 45,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => '\'Favor object composition over class inheritance\' es uno de los principios fundacionales del diseño orientado a objetos. La herencia (\'es-un\') establece un acoplamiento rígido en tiempo de compilación, mientras que la composición (\'tiene-un\') permite ensamblar comportamientos intercambiables en tiempo de ejecución mediante interfaces.',
        'problem' => 'El abuso de jerarquías de herencia (\'extends BaseService\', \'extends AbstractPricingManager\') crea el antipatrón de \'clases frágiles\': cualquier modificación en la superclase rompe silenciosamente subclases hijas lejanas, y combinaciones nuevas de comportamiento provocan explosiones de subclases combinatorias.',
        'solution' => 'Inyectar dependencias contractuales a través de interfaces (Patrón Strategy), delegar responsabilidades a colaboradores especializados y mantener las clases de orquestación libres de la palabra clave \'extends\'.',
        'problem_label' => 'El Problema: La Jerarquía Frágil y la Explosión Combinatoria',
        'solution_label' => 'La Solución Senior: Composición Polimórfica y el Patrón Strategy',
    ],
    'mental_model' => [
        'title' => 'El Kit de Herramientas Modular vs La Navaja Suiza Monolítica',
        'analogy' => 'Imagina que compras una navaja suiza que tiene cuchillo, tijeras, sierra, linterna y destornillador forjados en una sola pieza de metal indivisible (Herencia monolítica: `class PricingEngine extends BasePriceCalculator extends AbstractBillingService`). Si un día necesitas un destornillador 5 milímetros más largo, tienes que forjar una navaja suiza completamente nueva (`CustomDiscountPricingEngine`). Si el resorte del cuchillo se rompe, toda la navaja queda inutilizable. La **Composición** es un taladro modular con mandril de acople rápido: el motor del taladro (`PricingEngine`) no sabe ni le interesa si hoy le conectas una broca de acero, una punta de estrella o un cepillo de pulido. Solo le inyectas una herramienta que cumpla el contrato de la interfaz (`DiscountStrategyInterface`). Puedes cambiar la herramienta en tiempo de ejecución, probar cada broca en aislamiento perfecto con tests unitarios y jamás romper el motor central.',
        'ascii_diagram' => 'HERENCIA RÍGIDA (Antipatrón: Acoplamiento vertical frágil):
[BaseService] ──> [DiscountService] ──> [VipDiscountService] ──> [BlackFridayVipService]
(Cualquier cambio en BaseService rompe toda la pirámide hacia abajo)

COMPOSICIÓN POLIMÓRFICA (Patrón Senior):
┌────────────────────────────────────────┐
│             PricingEngine              │ (¡Sin extends!)
│ ────────────────────────────────────── │
│ - strategy: DiscountStrategyInterface  │
│ ────────────────────────────────────── │
│ + calculateFinalPrice(int $subtotal)   │
└───────────────────┬────────────────────┘
                    │ Inyección en constructor (HAS-A)
                    ▼
      <<interface>> DiscountStrategyInterface
                    ▲
       ┌────────────┼────────────┐
       │            │            │
[NoDiscount]  [Percentage]  [FixedTierDiscount]',
        'key_concept' => 'La herencia acopla tu código a la estructura interna de otra clase. La composición acopla tu código únicamente a un contrato de comportamiento (interfaz).',
    ],
    'internals' => [
        'title' => 'Mecánica de la Composición y el Patrón Strategy',
        'steps' => [
            [
                'phase' => '1. Definición del Contrato Funcional (Interface)',
                'description' => 'Se declara una interfaz formal como \'DiscountStrategyInterface\' que estipula el método de cálculo sin asumir nada sobre cómo se obtiene el descuento.',
            ],
            [
                'phase' => '2. Inyección de Dependencias por Constructor',
                'description' => 'El motor \'PricingEngine\' recibe la estrategia en su constructor como dependencia tipada por interfaz, desacoplándose de las implementaciones concretas.',
            ],
            [
                'phase' => '3. Delegación de Responsabilidad',
                'description' => 'En \'calculateFinalPrice(int $subtotalCents)\', el motor no utiliza condicionales if/else ni lógica heredada: delega la deducción a la estrategia inyectada.',
            ],
            [
                'phase' => '4. Intercambiabilidad en Tiempo de Ejecución',
                'description' => 'El contenedor de servicios de Symfony (o el llamador) puede inyectar PercentageDiscountStrategy o TieredDiscountStrategy sin modificar una sola línea de PricingEngine.',
            ],
            [
                'phase' => '5. Testabilidad y Principio Open/Closed',
                'description' => 'Añadir un nuevo tipo de descuento no requiere alterar PricingEngine (Cerrado a modificación), solo crear una nueva clase que implemente la interfaz (Abierto a extensión).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Problema de la Explosión Combinatoria de Subclases',
            'icon' => 'layers',
            'content' => 'Supongamos que tienes una clase base `Vehicle`. Necesitas modelar dos dimensiones de variación: **Propulsión** (Gasolina, Eléctrico) y **Conducción** (Manual, Autónomo).

• **Con Herencia:** Terminas con 4 subclases: `ElectricAutonomousVehicle`, `ElectricManualVehicle`, `GasolineAutonomousVehicle`, `GasolineManualVehicle`. Si añades \'Híbrido\' y \'Control Remoto\', pasas a 9 subclases. Si añades otra dimensión, la jerarquía colapsa en cientos de clases.
• **Con Composición:** Tienes 1 sola clase `Vehicle` que recibe en su constructor un `EngineInterface` y un `SteeringInterface`. Las variaciones se ensamblan combinando componentes existentes, pasando de una complejidad multiplicativa O(N*M) a una aditiva O(N+M).',
            'takeaways' => 'Usa composición siempre que tu modelo tenga múltiples ejes de variación independiente.',
        ],
        [
            'title' => 'Cuándo SÍ está justificada la Herencia',
            'icon' => 'check-circle',
            'content' => 'La herencia no está prohibida, pero debe cumplir criterios rigurosos:

1. **Cumplimiento estricto del Principio de Sustitución de Liskov (LSP):** Cualquier subclase debe poder reemplazar a la clase base sin que el llamador lo note ni se altere la corrección del programa.
2. **Relación \'Es-Un\' pura y eterna:** Un `Apple` es un `Fruit`. Pero un `OrderService` NO es un `BaseService`; solo \'utiliza\' servicios base.
3. **Clases del Framework:** Extender `AbstractController` en Symfony está justificado porque solo aporta helpers de acceso al contenedor de inyección y no define el modelo del dominio.',
            'takeaways' => 'Reserva \'extends\' para jerarquías puras de dominio que respeten Liskov al 100% o adaptadores del framework, nunca para reutilizar código arbitrario.',
        ],
    ],
    'video' => [
        'title' => 'RailsConf 2015 - Nothing is Something',
        'speaker' => 'Sandi Metz',
        'youtube_id' => 'OMPfEXIlTVE',
        'duration' => '34 min',
        'description' => 'Una clase magistral sobre composición de objetos, sustitución de condicionales por polimorfismo y diseño desacoplado.',
        'key_takeaways' => [
            'Por qué los condicionales repetidos son un síntoma de objetos faltantes en el diseño.',
            'Cómo el polimorfismo permite que objetos diferentes respondan al mismo mensaje.',
            'La ventaja de ensamblar comportamientos mediante composición frente a jerarquías profundas.',
            'El patrón Null Object como técnica para eliminar comprobaciones de existencia.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Pricing/PricingEngine.php',
        'title' => 'Motor de Precios con Composición Polimórfica',
        'tag' => 'PHP 8.4 Strategy Pattern',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Pricing;

/**
 * Contrato contractual para cualquier algoritmo de descuento.
 */
interface DiscountStrategyInterface
{
    /**
     * Aplica el descuento sobre el subtotal en centavos y retorna el monto descontado.
     */
    public function calculateDiscount(int $subtotalCents): int;
}

/**
 * Motor de precios desacoplado: No hereda de ninguna clase base.
 * Ensambla el comportamiento mediante composición inyectando DiscountStrategyInterface.
 */
class PricingEngine
{
    public function __construct(
        private readonly DiscountStrategyInterface $discountStrategy
    ) {}

    /**
     * Calcula el precio final restando el descuento y asegurando que no sea negativo.
     */
    public function calculateFinalPrice(int $subtotalCents): int
    {
        if ($subtotalCents < 0) {
            throw new \\InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        $discount = $this->discountStrategy->calculateDiscount($subtotalCents);
        $finalPrice = $subtotalCents - $discount;

        return max(0, $finalPrice);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior ve dos clases con métodos parecidos y crea inmediatamente una clase abstracta \'BaseService\' con \'extends\', atrapando a su equipo en una jerarquía rígida. Un ingeniero Senior prefiere la duplicación temporal antes que la abstracción incorrecta, identifica las responsabilidades independientes y las ensambla mediante composición de interfaces.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Herencia frágil con \'extends\' y sobreescritura caótica',
            'code' => '// Jerarquía frágil
abstract class BasePricing {
    public function applyBaseTax($amount) { return $amount * 1.16; }
}

class PricingEngine extends BasePricing {
    // Fuertemente acoplado a BasePricing; si cambia applyBaseTax, se rompe todo
    public function calculateFinalPrice($subtotal) {
        return $this->applyBaseTax($subtotal) - 100;
    }
}',
            'flaws' => [
                'Usa \'extends\' acoplando PricingEngine a la implementación de una clase base arbitraria.',
                'Imposible cambiar la estrategia de cálculo en caliente sin crear nuevas subclases.',
                'Dificulta el testing unitario al arrastrar dependencias ocultas de la superclase.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Composición pura sin \'extends\' con inyección de interfaz',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Pricing;

interface DiscountStrategyInterface
{
    public function calculateDiscount(int $subtotalCents): int;
}

class PricingEngine
{
    public function __construct(
        private readonly DiscountStrategyInterface $discountStrategy
    ) {}

    public function calculateFinalPrice(int $subtotalCents): int
    {
        if ($subtotalCents < 0) {
            throw new \\InvalidArgumentException(\'Subtotal cannot be negative\');
        }
        $discount = $this->discountStrategy->calculateDiscount($subtotalCents);
        return max(0, $subtotalCents - $discount);
    }
}',
            'rationale' => [
                'PricingEngine no hereda de ninguna clase (\'sin extends\'), eliminando acoplamiento vertical.',
                'Inyecta DiscountStrategyInterface permitiendo intercambiar estrategias en runtime.',
                'Testable al 100% mediante mocks o implementaciones anónimas de la interfaz.',
            ],
            'trade_offs' => [
                'La composición requiere definir interfaces y crear más archivos de clases que una simple herencia.',
                'Requiere usar un contenedor de inyección de dependencias (como Symfony DI) para ensamblar los colaboradores de forma ergonómica.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Favor Composition over Inheritance',
            'source' => 'Design Patterns: Elements of Reusable Object-Oriented Software',
            'quote' => 'Favorezca la composición de objetos sobre la herencia de clases.',
            'author' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides (GoF)',
            'explanation' => 'El consejo de diseño más citado en la historia de la ingeniería de software.',
        ],
        [
            'topic' => 'The Fragile Base Class Problem',
            'source' => 'Object-Oriented Programming Systems',
            'quote' => 'El problema de la clase base frágil es una vulnerabilidad arquitectónica donde modificaciones aparentemente seguras a una clase base causan fallos en clases derivadas.',
            'author' => 'Sandi Metz',
            'explanation' => 'La composición elimina el problema de raíz al no compartir estado mutable protegido entre clases.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Refactoring Guru: Patrón Strategy',
            'url' => 'https://refactoring.guru/es/design-patterns/strategy',
            'description' => 'Explicación visual del patrón estrategia y cómo sustituye la herencia de algoritmos.',
            'type' => 'REFERENCE',
        ],
        [
            'title' => 'Composition over Inheritance Explained',
            'url' => 'https://en.wikipedia.org/wiki/Composition_over_inheritance',
            'description' => 'Definición formal del principio polimórfico en computación.',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Sandi Metz: Practical Object-Oriented Design in Ruby (POODR)',
            'url' => 'https://www.sandimetz.com/products',
            'description' => 'Libro de referencia global sobre cómo desacoplar sistemas orientados a objetos.',
            'type' => 'BOOK',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Composition over Inheritance en la Práctica',
        'objective' => 'Implementar la interfaz DiscountStrategyInterface y la clase PricingEngine utilizando composición estricta (sin \'extends\') para calcular el precio final en centavos.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la interfaz \'DiscountStrategyInterface\' con el método \'calculateDiscount(int $subtotalCents): int\'.
3. Define la clase \'PricingEngine\' (¡sin utilizar la palabra clave \'extends\'!).
4. Inyecta \'DiscountStrategyInterface\' en el constructor de \'PricingEngine\'.
5. Implementa el método \'calculateFinalPrice(int $subtotalCents): int\' asegurando que el resultado nunca sea menor a 0.',
        'filename' => 'src/Domain/Pricing/PricingEngine.php',
        'guide' => [
            'explanation' => 'PricingEngine NO debe heredar de ninguna clase base. El método calculateFinalPrice debe restar el valor devuelto por la estrategia al subtotal, retornando max(0, $subtotalCents - $discount).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe definirse y utilizarse la interfaz DiscountStrategyInterface. PricingEngine no debe heredar de ninguna clase base mediante extends.',
            ],
            [
                'text' => '[Pista 2: Estructura] Inyecta la estrategia en el constructor: public function __construct(private readonly DiscountStrategyInterface $discountStrategy) {}. Implementa calculateFinalPrice(int $subtotalCents): int.',
            ],
            [
                'text' => '[Pista 3: Snippet] $discount = $this->discountStrategy->calculateDiscount($subtotalCents); return max(0, $subtotalCents - $discount);. Asegúrate de incluir declare(strict_types=1);.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Pricing;

interface DiscountStrategyInterface
{
    public function calculateDiscount(int $subtotalCents): int;
}

// TODO: Implementa PricingEngine SIN heredar de ninguna clase (sin extends)
class PricingEngine
{
    // TODO: Inyecta DiscountStrategyInterface en el constructor

    public function calculateFinalPrice(int $subtotalCents): int
    {
        // TODO: Delega el cálculo a la estrategia y retorna el precio final >= 0
        return 0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Pricing;

interface DiscountStrategyInterface
{
    public function calculateDiscount(int $subtotalCents): int;
}

class PricingEngine
{
    public function __construct(
        private readonly DiscountStrategyInterface $discountStrategy
    ) {}

    public function calculateFinalPrice(int $subtotalCents): int
    {
        if ($subtotalCents < 0) {
            throw new \\InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        $discount = $this->discountStrategy->calculateDiscount($subtotalCents);
        $final = $subtotalCents - $discount;

        return max(0, $final);
    }
}
',
        'explanation' => 'PricingEngine ejemplifica la composición sobre herencia al recibir un contrato DiscountStrategyInterface por constructor y delegar la variación del cálculo sin depender de superclases ni acoplamiento rígido.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Composition over Inheritance: Estrategias, Polimorfismo & Acoplamiento',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué la herencia rígida (extends) suele ser más frágil que la composición con interfaces?',
                'options' => [
                    'a' => 'Porque la herencia rompe la encapsulación al exponer detalles internos de la clase base a las subclases.',
                    'b' => 'Porque PHP no permite herencia en versiones superiores a 8.0.',
                    'c' => 'Porque las interfaces consumen el doble de memoria RAM que las clases abstractas.',
                    'd' => 'Porque la herencia impide usar constructores promovidos.',
                ],
                'correct' => 'a',
                'explanation' => 'La herencia expone el estado protegido y los supuestos de implementación de la clase padre. Un cambio sutil en la superclase puede quebrar invariantes de subclases que no estaban previstas.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué principio SOLID se cumple directamente al implementar el patrón Strategy mediante composición?',
                'options' => [
                    'a' => 'Principio de Inversión de Dependencias (DIP) y Principio Abierto/Cerrado (OCP).',
                    'b' => 'Principio de Segregación de Hardware.',
                    'c' => 'Principio de Autoloading Unificado.',
                    'd' => 'Principio de Inmutabilidad de Servidores.',
                ],
                'correct' => 'a',
                'explanation' => 'Depender de interfaces cumple DIP (depender de abstracciones, no de detalles concretos), y permite añadir nuevas estrategias sin modificar la clase orquestadora (OCP).',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cómo ayuda la composición a resolver el problema de la \'explosión combinatoria de subclases\'?',
                'options' => [
                    'a' => 'Convierte todas las clases en Singletons de forma automática.',
                    'b' => 'Permite ensamblar dimensiones independientes de comportamiento en tiempo de ejecución combinando objetos especializados en lugar de crear subclases para cada combinación posible.',
                    'c' => 'Obliga a que todo el código resida en un único archivo PHP.',
                    'd' => 'Inhabilita el uso de tipos de retorno en las funciones.',
                ],
                'correct' => 'b',
                'explanation' => 'Al separar las dimensiones de variación en colaboradores inyectados, el número de clases necesarias crece de forma lineal y aditiva en lugar de multiplicativa y exponencial.',
            ],
        ],
    ],
];
