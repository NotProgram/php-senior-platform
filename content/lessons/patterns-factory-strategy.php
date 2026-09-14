<?php

declare(strict_types=1);

return [
    'slug' => 'patterns-factory-strategy',
    'title' => 'Factory & Strategy con Inyeccion de Symfony',
    'module' => 'design-patterns',
    'minutes' => 45,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'En aplicaciones PHP empresariales, una de las mayores fuentes de complejidad accidental y deuda tecnica son los bloques de control masivos: sentencias switch o match con decenas de ramas que deciden como calcular un precio, como procesar un pago o como exportar un reporte segun el tipo de cliente o canal de venta. Cada nuevo requerimiento obliga a modificar el mismo archivo monolitico, violando flagrantemente el principio Open/Closed (SOLID) y generando colisiones continuas en los merges de Git.

La combinacion de los patrones Strategy y Factory resuelve este problema de raiz. El patron Strategy encapsula cada algoritmo o politica de negocio detras de una interfaz polimorfica comun. El patron Factory (o un Registry/Resolver) se encarga de resolver e instanciar la estrategia adecuada en tiempo de ejecucion. En Symfony, este patron alcanza su maxima expresion mediante el contenedor de inyeccion de dependencias, utilizando etiquetas de servicios (Service Tags como #[AutoconfigureTag]), tagged iterators y ServiceLocators perezosos que instancian unicamente la estrategia requerida sin sobrecargar la memoria del proceso.',
        'problem' => 'En aplicaciones PHP empresariales, una de las mayores fuentes de complejidad accidental y deuda tecnica son los bloques de control masivos: sentencias switch o match con decenas de ramas que deciden como calcular un precio, como procesar un pago o como exportar un reporte segun el tipo de cliente o canal de venta. Cada nuevo requerimiento obliga a modificar el mismo archivo monolitico, violando flagrantemente el principio Open/Closed (SOLID) y generando colisiones continuas en los merges de Git.',
        'solution' => 'La combinacion de los patrones Strategy y Factory resuelve este problema de raiz. El patron Strategy encapsula cada algoritmo o politica de negocio detras de una interfaz polimorfica comun. El patron Factory (o un Registry/Resolver) se encarga de resolver e instanciar la estrategia adecuada en tiempo de ejecucion. En Symfony, este patron alcanza su maxima expresion mediante el contenedor de inyeccion de dependencias, utilizando etiquetas de servicios (Service Tags como #[AutoconfigureTag]), tagged iterators y ServiceLocators perezosos que instancian unicamente la estrategia requerida sin sobrecargar la memoria del proceso.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Taladro Electrico y el Mandril de Brocas Intercambiables',
        'concept' => 'Piensa en un taladro profesional de velocidad variable:
1. EL CONTEXTO (El Taladro): Provee el motor, el gatillo, el mango ergonomico y la alimentacion de energia. Sabe como girar y como aplicar torque, pero no sabe si va a perforar madera, concreto, acero o ajustar un tornillo Torx.
2. LA ESTRATEGIA (La Broca / Punta): Existe una familia de brocas intercambiables (Broca de Concreto con punta de tungsteno, Broca de Madera con punta de centrado, Broca de Metal HSS). Cada una implementa la misma interfaz fisica de acople: un vastago hexagonal estandar.
3. LA FABRICA / REGISTRY (El Maletin de Herramientas): Cuando el operario ve la pared, selecciona la broca adecuada del maletin segun el material y la acopla al mandril del taladro.

Si manana inventan un material nuevo como fibra de carbono, compras una broca para fibra de carbono y la insertas en el taladro. Jamas tienes que abrir el motor del taladro para redisenarlo.',
        'ascii_diagram' => 'PATRON STRATEGY CON INYECCION DE DEPENDENCIAS EN SYMFONY:

                           +-------------------------------+
                           |  <<interface>>                |
                           |  DiscountStrategyInterface    |
                           +-------------------------------+
                           | + getIdentifier(): string     |
                           | + apply(amount, rate): float  |
                           +-------------------------------+
                                    ^         ^          ^
             +----------------------+         |          +----------------------+
             |                                |                                 |
+--------------------------+    +--------------------------+    +--------------------------+
| PercentageDiscount       |    | FixedAmountDiscount      |    | TieredVipDiscount        |
| Strategy                 |    | Strategy                 |    | Strategy                 |
+--------------------------+    +--------------------------+    +--------------------------+
             ^                                ^                                 ^
             +--------------------------------+---------------------------------+
                                              |
                              Autoconfiguradas via Service Tag
                                              |
                                              v
                              +--------------------------------+
                              | DiscountStrategyResolver       |
                              | (Factory / Registry)           |
                              +--------------------------------+
                              | + registerStrategy(...)        |
                              | + resolve(identifier)          |
                              | + execute(...)                 |
                              +--------------------------------+
',
        'analogy' => 'Piensa en un taladro profesional de velocidad variable:
1. EL CONTEXTO (El Taladro): Provee el motor, el gatillo, el mango ergonomico y la alimentacion de energia. Sabe como girar y como aplicar torque, pero no sabe si va a perforar madera, concreto, acero o ajustar un tornillo Torx.
2. LA ESTRATEGIA (La Broca / Punta): Existe una familia de brocas intercambiables (Broca de Concreto con punta de tungsteno, Broca de Madera con punta de centrado, Broca de Metal HSS). Cada una implementa la misma interfaz fisica de acople: un vastago hexagonal estandar.
3. LA FABRICA / REGISTRY (El Maletin de Herramientas): Cuando el operario ve la pared, selecciona la broca adecuada del maletin segun el material y la acopla al mandril del taladro.

Si manana inventan un material nuevo como fibra de carbono, compras una broca para fibra de carbono y la insertas en el taladro. Jamas tienes que abrir el motor del taladro para redisenarlo.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Symfony, cuando registras estrategias con un atributo #[AutoconfigureTag(\'app.discount_strategy\')], el Dependency Injection Container agrupa todos los servicios que implementan la interfaz durante la compilacion.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Al inyectar #[TaggedIterator(\'app.discount_strategy\')] o #[TaggedLocator(\'app.discount_strategy\')], Symfony no crea todas las estrategias en memoria de inmediato; genera un ServiceLocator perezoso.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Las estrategias pesadas que dependen de clientes HTTP o conexiones a bases de datos secundarias no se instancian hasta el instante exacto en que se invoca $locator->get($identifier), optimizando los zvals de memoria y el tiempo de arranque de la peticion.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'El Resolver o Factory debe implementar politicas defensivas estrictas: 1. Impedir identificadores vacios o duplicados arrojando InvalidArgumentException. 2. Proteger las invariantes de dominio (importes o tasas negativas). 3. Proveer mensajes de excepcion precisos cuando un cliente solicita un identificador inexistente, permitiendo a los desarrolladores diagnosticar configuraciones erroneas inmediatamente.',
        ],
    ],
    'video' => [
        'title' => 'Strategy Pattern – Design Patterns (ep 1)',
        'speaker' => 'Christopher Okhravi',
        'youtube_id' => 'v9ejT8FO-7I',
        'duration' => '31 min',
        'description' => 'Christopher Okhravi desglosa de forma magistral el patron Strategy segun los principios canonicos de Head First Design Patterns y Gang of Four, ensenando a programar contra interfaces y encapsular comportamientos variables.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Pricing\\Strategy;

use InvalidArgumentException;

interface DiscountStrategyInterface
{
    public function getIdentifier(): string;
    public function apply(float $amount, float $rate): float;
}

final class DiscountStrategyResolver
{
    /**
     * @var array<string, DiscountStrategyInterface>
     */
    private array $strategies = [];

    public function registerStrategy(DiscountStrategyInterface $strategy): void
    {
        $id = trim($strategy->getIdentifier());
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador de la estrategia no puede estar vacio.\');
        }

        if (isset($this->strategies[$id])) {
            throw new InvalidArgumentException(sprintf(\'La estrategia "%s" ya se encuentra registrada.\', $id));
        }

        $this->strategies[$id] = $strategy;
    }

    public function resolve(string $identifier): DiscountStrategyInterface
    {
        $key = trim($identifier);
        if (!isset($this->strategies[$key])) {
            throw new InvalidArgumentException(sprintf(\'No se encontro una estrategia registrada para "%s".\', $key));
        }

        return $this->strategies[$key];
    }

    public function execute(string $identifier, float $amount, float $rate): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException(\'El importe base no puede ser negativo.\');
        }

        if ($rate < 0.0) {
            throw new InvalidArgumentException(\'El valor de descuento no puede ser negativo.\');
        }

        $strategy = $this->resolve($identifier);

        return $strategy->apply($amount, $rate);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior llena sus controladores de estructuras switch o if/else anidados para cada nuevo requerimiento de negocio. El desarrollador Senior aplica Strategy y Factory: para agregar una nueva regla de negocio, crea una nueva clase aislada que implementa la interfaz, la prueba de forma unitaria en aislamiento y la registra en el contenedor de Symfony sin modificar una sola linea del codigo existente, honrando el principio Open/Closed.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Agrega un nuevo case al switch de 300 lineas, arriesgandose a alterar la ejecucion de los casos anteriores.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Crea una nueva clase Strategy autoconfigurada mediante tags en el contenedor de Symfony, garantizando cero impacto sobre las demas estrategias.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'El Principio Open/Closed',
            'source' => 'Design Patterns: Elements of Reusable Object-Oriented Software (GoF)',
            'quote' => 'El patron Strategy define una familia de algoritmos, encapsula cada uno de ellos y los hace intercambiables. Permite que el algoritmo varie independientemente de los clientes que lo utilizan.',
            'author' => 'Erich Gamma, Richard Helm, Ralph Johnson, John Vlissides',
            'explanation' => 'Desacopla la seleccion del algoritmo de su logica interna de calculo.',
        ],
        [
            'topic' => 'Inyeccion y Localizacion de Servicios',
            'source' => 'Symfony Service Container Best Practices',
            'quote' => 'El uso de TaggedIterators y ServiceLocators permite a Symfony implementar el patron Strategy de manera nativa sin instanciar servicios innecesariamente.',
            'author' => 'Nicolas Grekas',
            'explanation' => 'Combina la flexibilidad del polimorfismo con el rendimiento optimo de memoria de PHP.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Refactoring.guru: Patron Strategy en PHP',
            'url' => 'https://refactoring.guru/es/design-patterns/strategy/php/example',
            'type' => 'REFERENCE',
        ],
        [
            'title' => 'Symfony Documentation: How to Work with Service Tags',
            'url' => 'https://symfony.com/doc/current/service_container/tags.html',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Resolver Polimorfico de Estrategias de Descuento (DiscountStrategyResolver)',
        'objective' => 'Implementar DiscountStrategyResolver para registrar estrategias polimorficas, resolverlas dinamicamente por su identificador y ejecutar el calculo protegiendo invariantes contra importes o tasas negativas.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Pricing\\Strategy.
2. Define la interfaz DiscountStrategyInterface con getIdentifier(): string y apply(float $amount, float $rate): float.
3. Implementa DiscountStrategyResolver con los metodos registerStrategy, resolve y execute.
4. En registerStrategy, valida que el identificador no sea vacio ni este duplicado; de lo contrario lanza InvalidArgumentException.
5. En resolve, retorna la estrategia registrada o lanza InvalidArgumentException si no existe.
6. En execute, valida que $amount >= 0.0 y $rate >= 0.0 (InvalidArgumentException), resuelve la estrategia e invoca apply($amount, $rate).',
        'filename' => 'src/Pricing/Strategy/DiscountStrategyResolver.php',
        'guide' => [
            'steps' => [
                'Paso 1: Declara la interfaz DiscountStrategyInterface con getIdentifier(): string y apply(float $amount, float $rate): float.',
                'Paso 2: Almacena las estrategias en un array asociativo privado indexado por su identificador.',
                'Paso 3: Lanza InvalidArgumentException si el id esta vacio o ya existe en el array.',
                'Paso 4: En execute, valida importes no negativos y delega en el metodo apply() de la estrategia resuelta.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El resolver funciona como un Registry y Factory de estrategias polimorficas.',
            ],
            [
                'text' => '[Pista 2: Estructura] El metodo execute debe invocar $strategy = $this->resolve($identifier); return $strategy->apply($amount, $rate);.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($amount < 0.0 || $rate < 0.0) { throw new InvalidArgumentException(\'Valores no pueden ser negativos\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Pricing\\Strategy;

use InvalidArgumentException;

interface DiscountStrategyInterface
{
    public function getIdentifier(): string;
    public function apply(float $amount, float $rate): float;
}

class DiscountStrategyResolver
{
    /**
     * @var array<string, DiscountStrategyInterface>
     */
    private array $strategies = [];

    public function registerStrategy(DiscountStrategyInterface $strategy): void
    {
        // TODO: Validar que getIdentifier() no sea vacio ni este repetido (InvalidArgumentException)
        // TODO: Registrar en el array $this->strategies indexado por su identificador
    }

    public function resolve(string $identifier): DiscountStrategyInterface
    {
        // TODO: Retornar estrategia o lanzar InvalidArgumentException si no esta registrada
        throw new InvalidArgumentException(\'Estrategia no encontrada\');
    }

    public function execute(string $identifier, float $amount, float $rate): float
    {
        // TODO: Validar que $amount >= 0.0 y $rate >= 0.0 (lanzar InvalidArgumentException)
        // TODO: Resolver la estrategia y ejecutar apply($amount, $rate)
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Pricing\\Strategy;

use InvalidArgumentException;

interface DiscountStrategyInterface
{
    public function getIdentifier(): string;
    public function apply(float $amount, float $rate): float;
}

class DiscountStrategyResolver
{
    /**
     * @var array<string, DiscountStrategyInterface>
     */
    private array $strategies = [];

    public function registerStrategy(DiscountStrategyInterface $strategy): void
    {
        $id = trim($strategy->getIdentifier());
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador de la estrategia no puede estar vacio.\');
        }

        if (isset($this->strategies[$id])) {
            throw new InvalidArgumentException(sprintf(\'La estrategia "%s" ya se encuentra registrada.\', $id));
        }

        $this->strategies[$id] = $strategy;
    }

    public function resolve(string $identifier): DiscountStrategyInterface
    {
        if (!isset($this->strategies[$identifier])) {
            throw new InvalidArgumentException(sprintf(\'No se encontro una estrategia registrada para "%s".\', $identifier));
        }

        return $this->strategies[$identifier];
    }

    public function execute(string $identifier, float $amount, float $rate): float
    {
        if ($amount < 0.0) {
            throw new InvalidArgumentException(\'El importe base no puede ser negativo.\');
        }

        if ($rate < 0.0) {
            throw new InvalidArgumentException(\'El valor de descuento no puede ser negativo.\');
        }

        $strategy = $this->resolve($identifier);

        return $strategy->apply($amount, $rate);
    }
}
',
        'explanation' => 'La clase DiscountStrategyResolver implementa el registro y despacho polimorfico. Valida la singularidad de los identificadores, previene estrategias nulas y encapsula la llamada a apply() garantizando que ningun importe invalido pueda alcanzar el algoritmo de descuento.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Factory & Strategy con Inyeccion de Symfony',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Que principio de diseno SOLID se cumple de manera primordial al utilizar el patron Strategy en lugar de un switch con multiples ramas?',
                'options' => [
                    'a' => 'Single Responsibility solamente.',
                    'b' => 'Open/Closed Principle (OCP), permitiendo extender el sistema con nuevas estrategias sin modificar el codigo existente.',
                    'c' => 'Liskov Substitution Principle exclusivamente.',
                    'd' => 'Interface Segregation Principle unicamente.',
                ],
                'correct' => 'b',
                'explanation' => 'El principio Open/Closed exige que el software este abierto a la extension pero cerrado a la modificacion. Con Strategy, una nueva regla de negocio se agrega creando una nueva clase, sin tocar el resolver existente.',
            ],
            [
                'id' => 'q2',
                'question' => 'En Symfony, ¿cual es la ventaja de inyectar estrategias utilizando un ServiceLocator perezoso en lugar de un array con todas las instancias ya creadas?',
                'options' => [
                    'a' => 'Permite convertir variables PHP en punteros de C++.',
                    'b' => 'Evita instanciar en memoria todas las estrategias durante el arranque de la peticion; unicamente se instancia la estrategia especifica solicitada por la operacion.',
                    'c' => 'Hace que las pruebas unitarias se ejecuten en paralelo de forma obligatoria.',
                    'd' => 'Elimina la necesidad de utilizar el tipado estricto declare(strict_types=1).',
                ],
                'correct' => 'b',
                'explanation' => 'Si tienes 50 estrategias de pago o descuento y cada una inyecta clientes HTTP o repositorios pesados, instanciar las 50 desperdiciaria memoria y CPU. El ServiceLocator difiere la creacion de la instancia hasta su uso real.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que excepcion debe arrojar un Resolver o Factory cuando el cliente solicita un identificador que no existe en el registro?',
                'options' => [
                    'a' => 'Retornar silenciosamente null o 0.0 para no interrumpir el flujo.',
                    'b' => 'InvalidArgumentException con un mensaje claro indicando que el identificador solicitado no se encuentra registrado.',
                    'c' => 'Emitir un fatal error usando trigger_error(..., E_USER_ERROR).',
                    'd' => 'Detener el proceso de PHP con exit(0).',
                ],
                'correct' => 'b',
                'explanation' => 'En codigo Senior, nunca se retorna null silenciosamente ante configuraciones invalidas. Lanzar InvalidArgumentException alerta inmediatamente de un error de integracion antes de que cause corrupcion de datos.',
            ],
        ],
    ],
];
