<?php

declare(strict_types=1);

return [
    'slug' => 'se-solid-principles',
    'title' => 'Principios SOLID en PHP 8.4 & Casos Symfony',
    'module' => 'Ingeniería de Software',
    'minutes' => 60,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Los 5 principios SOLID (Single Responsibility, Open/Closed, Liskov Substitution, Interface Segregation y Dependency Inversion) son los pilares universales de la arquitectura de software orientada a objetos para crear sistemas que aceptan cambios sin romperse.',
        'problem' => 'El código monolítico lleno de switch masivos y comprobaciones de tipos con instanceof viola el principio Open/Closed: cada vez que el negocio agrega un nuevo país, tipo de pago o pasarela, los desarrolladores deben modificar clases críticas en producción, arriesgando caídas generales del sistema.',
        'solution' => 'Aplicar el Principio Open/Closed (OCP) y Dependency Inversion (DIP) mediante el Patrón Estrategia (Strategy Pattern) respaldado por interfaces y el contenedor de inyección de dependencias de Symfony.',
        'problem_label' => 'El Problema: El switch monstruoso que rompe producción en cada release',
        'solution_label' => 'La Solución Senior: Open/Closed Principle con Strategy Pattern e Inyección de Dependencias',
    ],
    'mental_model' => [
        'title' => 'El Puerto USB vs Soldar Cables a la Placa Madre',
        'analogy' => 'Tu computadora portátil no viene con los cables del teclado o del ratón soldados directamente a los microcircuitos de la placa madre. En lugar de eso, los fabricantes crearon un estándar universal: el puerto USB. Si compras un teclado nuevo de otra marca o un micrófono profesional, no abres tu computadora con un soldador de estaño para modificar sus circuitos internos; simplemente conectas el dispositivo al puerto USB. La computadora está **Cerrada a modificación** (su hardware interno no se toca ni se arriesga), pero está **Abierta a extensión** (soporta cualquier periférico que respete el protocolo USB). En PHP y Symfony, una interfaz (`TaxStrategyInterface`) es el puerto USB, y cada clase concreta (`SpainTaxStrategy`, `GermanyTaxStrategy`) es un dispositivo conectado.',
        'ascii_diagram' => 'CÓDIGO ACOPLADO (VIOLA OCP):              ARQUITECTURA SOLID (OCP + STRATEGY):
TaxEngine                                 TaxEngine (Cerrado a modificación)
  ├── switch($country) {                         │
  │     case \'ES\': ...                           ├── [Puerto: TaxStrategyInterface]
  │     case \'DE\': ...                                  ▲              ▲
  │     case \'FR\': ...                                  │              │
  │     case \'CL\': ... (Editas y rompes todo)   [SpainTaxStrategy] [GermanyTaxStrategy]
  └── }                                         (Para agregar Chile, creas ChileTaxStrategy
                                                 sin tocar una sola línea de TaxEngine)',
        'key_concept' => 'Para agregar una nueva funcionalidad al sistema, debes escribir código nuevo, nunca editar código viejo que ya está probado en producción.',
    ],
    'internals' => [
        'title' => 'Anatomía de los 5 Principios SOLID en PHP 8.4',
        'steps' => [
            [
                'phase' => '1. Single Responsibility Principle (SRP)',
                'description' => 'Una clase debe tener una y solo una razón para cambiar. Si una clase cambia cuando el departamento de finanzas cambia una regla Y TAMBIÉN cuando el departamento de marketing cambia un email, viola SRP.',
            ],
            [
                'phase' => '2. Open/Closed Principle (OCP)',
                'description' => 'Las entidades de software deben estar abiertas a la extensión, pero cerradas a la modificación. Se implementa mediante polimorfismo, interfaces y el patrón Estrategia.',
            ],
            [
                'phase' => '3. Liskov Substitution Principle (LSP)',
                'description' => 'Los objetos de una subclase deben poder reemplazar a los de la superclase sin alterar la corrección del programa. Si una subclase lanza NotSupportedException o altera precondiciones, viola LSP.',
            ],
            [
                'phase' => '4. Interface Segregation Principle (ISP)',
                'description' => 'Los clientes no deben ser obligados a depender de interfaces que no utilizan. Prefiere 10 interfaces pequeñas con 1 o 2 métodos que una interfaz gigante con 20 métodos.',
            ],
            [
                'phase' => '5. Dependency Inversion Principle (DIP)',
                'description' => 'Los módulos de alto nivel no deben depender de módulos de bajo nivel; ambos deben depender de abstracciones (interfaces). Las abstracciones no deben depender de los detalles; los detalles deben depender de las abstracciones.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Cómo implementa Symfony el OCP con Service Tags y AutowireIterator',
            'icon' => 'cpu',
            'content' => 'En Symfony, el contenedor de servicios lleva el principio Open/Closed a su máxima expresión arquitectónica mediante Service Tags.

En lugar de inyectar estrategias a mano o usar un switch:
1. Creas una interfaz común, ej. `TaxStrategyInterface`.
2. Creas una etiqueta de autoconfiguración: `#[AutoconfigureTag(\'app.tax_strategy\')]`.
3. En el servicio principal `TaxEngine`, inyectas todas las estrategias con `#[AutowireIterator(\'app.tax_strategy\')] iterable $strategies`.

Cuando la empresa comience a vender en Japón, simplemente creas `JapanTaxStrategy implements TaxStrategyInterface`. ¡Symfony lo descubre y registra automáticamente sin que toques una sola línea de `TaxEngine` ni de configuración YAML!',
            'code' => 'final readonly class TaxEngine
{
    /**
     * @param iterable<TaxStrategyInterface> $strategies
     */
    public function __construct(
        #[AutowireIterator(\'app.tax_strategy\')]
        private iterable $strategies
    ) {}
}',
            'takeaways' => 'El contenedor compilado de Symfony está optimizado para OCP: agrega nuevas capacidades creando nuevas clases, sin modificar código existente.',
        ],
        [
            'title' => 'Liskov Substitution en PHP: Covarianza y Contravarianza',
            'icon' => 'shield',
            'content' => 'El principio de Liskov se hace cumplir en tiempo de compilación por el motor de tipos de PHP 8+ mediante:
• **Covarianza de retornos:** Una subclase puede retornar un tipo más específico que el método del padre.
• **Contravarianza de argumentos:** Una subclase puede aceptar un tipo más genérico en sus argumentos que el padre.

Si una clase base retorna `Response` y una subclase decide retornar `void` o cambiar el contrato, el compilador de PHP emitirá un Fatal Error inmediato.',
            'takeaways' => 'LSP garantiza que el polimorfismo sea seguro: nunca uses instanceof dentro de un consumidor para saber qué subclase te pasaron.',
        ],
    ],
    'video' => [
        'title' => 'SOLID Principles: Do You Really Understand Them?',
        'speaker' => 'Dave Farley (Continuous Delivery)',
        'youtube_id' => 'kF7rQmSRlq0',
        'duration' => '24 min',
        'description' => 'Dave Farley profundiza en cada uno de los 5 principios SOLID, desmontando mitos comunes y mostrando su aplicación real en arquitecturas desacopladas.',
        'chapters' => [
            '00:00' => 'Single Responsibility: Cohesión y razones para cambiar',
            '06:15' => 'Open/Closed: Extensibilidad sin mutación',
            '12:30' => 'Liskov Substitution & Interface Segregation',
            '18:45' => 'Dependency Inversion: El pilar del desacoplamiento',
        ],
    ],
    'architecture_code' => [
        'filename' => 'TaxEngine.php',
        'title' => 'Motor de Impuestos Desacoplado con Patrón Estrategia (OCP & DIP)',
        'tag' => 'SOLID Strategy Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Contrato para estrategias de cálculo de impuestos internacionales (OCP / DIP).
 */
interface TaxStrategyInterface
{
    public function supports(string $countryCode): bool;
    public function calculateTax(float $amount): float;
}

/**
 * Estrategia de impuestos para España (IVA estándar 21%).
 */
final readonly class SpainTaxStrategy implements TaxStrategyInterface
{
    public function supports(string $countryCode): bool
    {
        return strtoupper($countryCode) === \'ES\';
    }

    public function calculateTax(float $amount): float
    {
        return round($amount * 0.21, 2);
    }
}

/**
 * Motor de cálculo de impuestos cerrado a modificación y abierto a extensión.
 */
final readonly class TaxEngine
{
    /**
     * @param iterable<TaxStrategyInterface> $strategies
     */
    public function __construct(
        private iterable $strategies
    ) {}

    public function calculate(string $countryCode, float $amount): float
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($countryCode)) {
                return $strategy->calculateTax($amount);
            }
        }

        throw new \\InvalidArgumentException("No existe una estrategia fiscal para el país: {$countryCode}");
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior busca activamente patrones que violen OCP. En cuanto ve un switch que evalúa un tipo de entidad, un país o un estado de pedido, sabe que esa clase se convertirá en un cuello de botella de fusiones de git (merge conflicts) y errores de regresión. Lo reemplaza inmediatamente con el patrón Estrategia.',
        'critical_questions' => [
            'Si la empresa se expande a un nuevo mercado o canal, ¿tengo que editar este archivo existente o puedo crear una clase nueva?',
            '¿Estoy inyectando interfaces o clases concretas en los constructores de mis servicios?',
            '¿Esta subclase respeta fielmente todas las promesas y contratos de su interfaz?',
            '¿Puedo probar este componente unitariamente sin necesidad de levantar una base de datos real?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Calcular impuestos para transacciones internacionales en constante expansión regulatoria.',
        'junior' => [
            'approach' => 'Escribe un método con 30 bloques switch/case dentro de una sola clase. Cuando un nuevo país requiere lógica especial (ej. exenciones por categorías), agrega más condicionales booleanos adentro del case.',
            'flaws' => [
                'Cada cambio requiere desplegar toda la clase de facturación central. Un error en la tasa de Chile puede romper la facturación de España. Pruebas unitarias gigantescas y frágiles.',
            ],
        ],
        'senior' => [
            'approach' => 'Define TaxStrategyInterface y TaxEngine. Cada país es una clase independiente y aislada con sus propios tests unitarios. Registra las estrategias como servicios etiquetados en Symfony.',
            'rationale' => [
                'Añadir un país nuevo tiene riesgo cero de regresión sobre los países existentes. Cumplimiento perfecto de Open/Closed Principle.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'El Principio Open/Closed',
            'source' => 'The Open-Closed Principle (C++ Report)',
            'quote' => 'Las entidades de software (clases, módulos, funciones) deben estar abiertas para extensión pero cerradas para modificación.',
            'author' => 'Bertrand Meyer & Robert C. Martin',
            'explanation' => 'El cambio de requerimientos se atiende agregando código nuevo que implementa contratos existentes, nunca alterando binarios o clases estables ya en producción.',
        ],
        [
            'topic' => 'Inversión de Dependencias',
            'source' => 'Agile Software Development: Principles, Patterns, and Practices',
            'quote' => 'Los detalles deben depender de las políticas de alto nivel. La arquitectura de un sistema se define por la dirección de sus dependencias.',
            'author' => 'Robert C. Martin',
            'explanation' => 'La lógica de negocio central nunca debe depender de librerías externas o detalles de la base de datos; la infraestructura debe acoplarse al dominio.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: Service Tags & Autowiring',
            'url' => 'https://symfony.com/doc/current/service_container/tags.html',
            'description' => 'Cómo implementar el patrón Estrategia y OCP de forma nativa en el contenedor de Symfony.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'SOLID Design Principles Explained Simply',
            'url' => 'https://www.digitalocean.com/community/conceptual-articles/s-o-l-i-d-the-first-five-principles-of-object-oriented-design',
            'description' => 'Explicación conceptual completa de los 5 principios con diagramas y código.',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Motor de Impuestos Desacoplado con TaxStrategyInterface y TaxEngine',
        'objective' => 'Aplicar los principios Open/Closed (OCP) y Dependency Inversion (DIP) declarando la interfaz TaxStrategyInterface e implementando la clase TaxEngine.',
        'instructions' => '1) Declara la interfaz TaxStrategyInterface con los métodos supports(string $countryCode): bool y calculateTax(float $amount): float. 2) Implementa la clase TaxEngine con un constructor que reciba una colección iterable de estrategias (iterable $strategies). 3) Implementa el método calculate(string $countryCode, float $amount): float en TaxEngine que itere las estrategias, encuentre la que soporta el país y retorne el impuesto calculado. Si ninguna aplica, lanza \\InvalidArgumentException.',
        'filename' => 'TaxEngine.php',
        'guide' => [
            'explanation' => 'En este ejercicio vas a crear una arquitectura desacoplada estilo Symfony. En lugar de un switch con países quemados en el código, crearás una interfaz que sirve de contrato y un motor (TaxEngine) que delega la responsabilidad a la estrategia correspondiente sin conocer sus detalles internos.',
            'steps' => [
                'Paso 1: Declara <code>interface TaxStrategyInterface</code> con <code>supports(string $countryCode): bool</code> y <code>calculateTax(float $amount): float</code>.',
                'Paso 2: Declara <code>class TaxEngine</code> con su constructor: <code>public function __construct(private readonly iterable $strategies) {}</code>.',
                'Paso 3: En <code>calculate(string $countryCode, float $amount): float</code>, recorre <code>$this->strategies</code> con un bucle <code>foreach</code>.',
                'Paso 4: Si <code>$strategy->supports($countryCode)</code> es true, retorna inmediatamente <code>$strategy->calculateTax($amount)</code>.',
                'Paso 5: Si el bucle termina sin coincidencias, lanza <code>new \\InvalidArgumentException("País no soportado")</code>.',
            ],
            'useful_functions' => [
                [
                    'name' => 'iterable',
                    'desc' => 'Pseudotipo de PHP que acepta tanto arrays tradicionales como instancias de Traversable / Generator.',
                ],
                [
                    'name' => 'foreach ($strategies as $strategy)',
                    'desc' => 'Permite despachar dinámicamente sobre la colección de estrategias polimórficas.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'La Interfaz TaxStrategyInterface',
                'text' => 'La interfaz solo declara las firmas públicas de los métodos sin llaves ni código en el cuerpo.',
                'snippet' => 'interface TaxStrategyInterface
{
    public function supports(string $countryCode): bool;
    public function calculateTax(float $amount): float;
}',
            ],
            [
                'label' => 'El Constructor de TaxEngine',
                'text' => 'Usa promoción de propiedades en el constructor para inyectar las estrategias en una sola línea.',
                'snippet' => 'class TaxEngine
{
    public function __construct(
        private readonly iterable $strategies
    ) {}',
            ],
            [
                'label' => 'El Bucle de Despacho',
                'text' => 'Recorre cada estrategia y evalúa el método supports() pasándole el código del país.',
                'snippet' => 'foreach ($this->strategies as $strategy) {
    if ($strategy->supports($countryCode)) {
        return $strategy->calculateTax($amount);
    }
}
throw new \\InvalidArgumentException(\'No soportado\');',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

// PASO 1: Declara la interfaz TaxStrategyInterface
// Debe definir: supports(string $countryCode): bool
// Debe definir: calculateTax(float $amount): float

// PASO 2: Declara la clase TaxEngine
// Constructor: recibe iterable $strategies
// Método: calculate(string $countryCode, float $amount): float
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

interface TaxStrategyInterface
{
    public function supports(string $countryCode): bool;
    public function calculateTax(float $amount): float;
}

class TaxEngine
{
    /**
     * @param iterable<TaxStrategyInterface> $strategies
     */
    public function __construct(
        private readonly iterable $strategies
    ) {}

    public function calculate(string $countryCode, float $amount): float
    {
        foreach ($this->strategies as $strategy) {
            if ($strategy->supports($countryCode)) {
                return $strategy->calculateTax($amount);
            }
        }

        throw new \\InvalidArgumentException("No se encontró estrategia fiscal para: {$countryCode}");
    }
}
',
        'explanation' => 'Esta solución implementa fielmente los principios Open/Closed y Dependency Inversion. TaxEngine depende exclusivamente de la abstracción TaxStrategyInterface. Nuevas reglas fiscales para cualquier país del mundo pueden agregarse implementando la interfaz, sin modificar TaxEngine jamás.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Principios SOLID y Arquitectura Desacoplada',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la principal ventaja de aplicar el Principio Open/Closed (OCP) mediante el Patrón Estrategia?',
                'options' => [
                    'a' => 'Reduce el uso de memoria a la mitad porque elimina las interfaces.',
                    'b' => 'Permite extender el comportamiento del sistema creando clases nuevas sin necesidad de modificar ni arriesgar el código existente ya probado en producción.',
                    'c' => 'Hace que las consultas SQL sean un 30% más veloces.',
                    'd' => 'Elimina la necesidad de escribir pruebas unitarias.',
                ],
                'correct' => 'b',
                'explanation' => 'OCP garantiza que para agregar un nuevo requerimiento no se toque código en producción, eliminando los riesgos de regresión y conflictos de git.',
            ],
            [
                'id' => 'q2',
                'question' => 'En el Principio de Sustitución de Liskov (LSP), ¿qué ocurre si una subclase sobreescribe un método lanzando una excepción del tipo BadMethodCallException para indicar que no soporta esa operación?',
                'options' => [
                    'a' => 'Es una práctica recomendada en PHP 8.4.',
                    'b' => 'Viola flagrantemente el principio LSP porque rompe el contrato de la superclase y cualquier consumidor fallará en tiempo de ejecución al sustituirla.',
                    'c' => 'Symfony lo exige para los controladores delgados.',
                    'd' => 'Mejora la seguridad de la aplicación.',
                ],
                'correct' => 'b',
                'explanation' => 'LSP exige que cualquier subclase sea un reemplazo transparente de su tipo padre sin alterar el comportamiento esperado ni negarse a ejecutar operaciones del contrato.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué establece el Principio de Inversión de Dependencias (DIP)?',
                'options' => [
                    'a' => 'Los módulos de alto nivel no deben depender de módulos de bajo nivel; ambos deben depender de abstracciones (interfaces).',
                    'b' => 'Las bases de datos deben crearse antes que el código PHP.',
                    'c' => 'Todo el código de la aplicación debe escribirse en un solo namespace raíz.',
                    'd' => 'Las dependencias de Composer deben instalarse siempre con la bandera --no-dev.',
                ],
                'correct' => 'a',
                'explanation' => 'DIP invierte la dirección tradicional de las dependencias, protegiendo la lógica de negocio central contra el acoplamiento a librerías o detalles de infraestructura.',
            ],
        ],
    ],
];
