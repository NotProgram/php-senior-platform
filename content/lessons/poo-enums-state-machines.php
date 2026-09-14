<?php

declare(strict_types=1);

return [
    'slug' => 'poo-enums-state-machines',
    'title' => 'PHP Enums como Máquinas de Estado Seguras',
    'module' => 'POO & Modelado',
    'minutes' => 35,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'En PHP 8.1+, los Backed Enums son tipos de datos estrictos y cerrados que combinan valores escalares (string/int) con métodos de negocio y contratos de interfaces. Pueden modelar máquinas de estados finitas (FSM) deterministas donde solo las transiciones válidas están permitidas.',
        'problem' => 'Gestionar estados de procesos de negocio (como el ciclo de un pedido) mediante cadenas de texto mágicas (\'draft\', \'paid\', \'cancelled\') dispersas en el código provoca errores tipográficos silenciosos y transiciones ilegales (ej. cancelar un pedido ya entregado a la puerta del cliente).',
        'solution' => 'Declarar un Backed Enum con tipos estrictos (\'enum OrderStatus: string\'), implementar métodos de transición de estados (\'canTransitionTo()\') y estados terminales (\'isTerminal()\') utilizando la expresión \'match ($this)\' para verificación exhaustiva en tiempo de compilación.',
        'problem_label' => 'El Problema: Strings Mágicos y Transiciones Ilegales de Estado',
        'solution_label' => 'La Solución Senior: Backed Enums y Máquinas de Estado Exhaustivas con \'match\'',
    ],
    'mental_model' => [
        'title' => 'El Semáforo de Cruce Ferroviario con Enclavamiento Mecánico',
        'analogy' => 'Imagina un cruce de trenes. En un sistema descuidado (strings mágicos), el operario escribe en una libreta con lápiz `estado = \'abierto\'`. Si el operario escribe `\'abirto\'` con una falta de ortografía, o si escribe `\'abierto\'` cuando el tren ya está a 5 metros de la barrera, ocurre un descarrilamiento trágico. Un **PHP Backed Enum como Máquina de Estados** es un semáforo ferroviario con **enclavamiento mecánico computarizado**: solo existen posiciones físicas predefinidas y cerradas (`OrderStatus::Draft`, `Paid`, `Shipped`, `Delivered`, `Cancelled`). El engranaje tiene compuertas mecánicas (`canTransitionTo()`): una vez que la barrera baja y el tren pasa a `Delivered` o `Cancelled`, entra en un **estado terminal irreversible**; es físicamente imposible que la palanca vuelva a moverse a `Draft`. El compilador y la expresión `match ($this)` actúan como el sensor láser que verifica todos los carriles posibles sin dejar ningún caso al azar.',
        'ascii_diagram' => 'MÁQUINA DE ESTADOS DETERMINISTA:

       ┌────────────┐
       │   Draft    │
       └─────┬──────┘
             │ pagar()
             ▼
       ┌────────────┐               cancelar()
       │    Paid    ├───────────────────────────────────┐
       └─────┬──────┘                                   │
             │ despachar()                              │
             ▼                                          ▼
       ┌────────────┐        cancelar()           ┌───────────┐
       │  Shipped   ├────────────────────────────>│ Cancelled │ [TERMINAL]
       └─────┬──────┘                             └───────────┘
             │ entregar()
             ▼
       ┌────────────┐
       │ Delivered  │ [TERMINAL]
       └────────────┘',
        'key_concept' => 'Los Enums en PHP no son simples listas de constantes; son objetos Singleton inmutables que encapsulan lógica de transición y garantizan exhaustividad de casos en tiempo de análisis estático.',
    ],
    'internals' => [
        'title' => 'Anatomía de los Backed Enums y la Expresión match en PHP 8.4',
        'steps' => [
            [
                'phase' => '1. Declaración de Backed Enums (: string / : int)',
                'description' => 'Al declarar \'enum OrderStatus: string\', cada caso tiene una representación escalar respaldada accesible mediante \'$case->value\', ideal para serialización en base de datos.',
            ],
            [
                'phase' => '2. Instancias Singleton Inmutables',
                'description' => 'Cada caso de un enum (ej. OrderStatus::Paid) es un objeto único en memoria gestionado por el motor Zend. Compararlos con \'===\' es una operación O(1) de punteros en memoria.',
            ],
            [
                'phase' => '3. Métodos en Enums',
                'description' => 'Los enums en PHP pueden tener métodos públicos y privados, constantes e implementar interfaces completas, permitiendo agrupar lógica de negocio junto al estado.',
            ],
            [
                'phase' => '4. Verificación Exhaustiva con \'match ($this)\'',
                'description' => 'A diferencia de switch que permite caídas (fall-through) accidentales, match evalúa de forma estricta (===) y lanza un UnhandledMatchError fatal si un caso no está cubierto.',
            ],
            [
                'phase' => '5. Estados Terminales y Control de Transiciones',
                'description' => 'Métodos como \'canTransitionTo(self $target): bool\' y \'isTerminal(): bool\' restringen el grafo de avance del proceso, bloqueando transiciones inválidas en la entidad.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'La Superioridad de match ($this) sobre switch',
            'icon' => 'zap',
            'content' => 'Históricamente, PHP usaba `switch` para evaluar estados:
```php
// PELIGROSO: Coerción débil y olvido de break
switch ($status) {
    case OrderStatus::Draft:
        // Si olvidas break;, cae al siguiente caso!
    case OrderStatus::Paid:
        return true;
}
```

En PHP moderno, `match` resuelve todos los problemas:
1. **Comparación estricta:** Usa `===`, impidiendo coerción extraña de tipos.
2. **Sin fall-through:** Solo ejecuta el brazo coincidente sin necesidad de `break`.
3. **Exhaustividad obligatoria:** Si añades un nuevo caso `Refunded` al enum, PHPStan y el motor te obligarán a cubrirlo en todos los `match` del proyecto, previniendo ramas olvidadas.',
            'takeaways' => 'Usa siempre match ($this) dentro de métodos de enum para garantizar que todos los casos estén cubiertos de forma exhaustiva.',
        ],
        [
            'title' => 'Persistencia de Enums en Symfony & Doctrine ORM',
            'icon' => 'database',
            'content' => 'Doctrine ORM soporta Backed Enums de forma nativa sin necesidad de Value Resolvers complejos:

```php
#[ORM\\Entity]
class Order
{
    #[ORM\\Column(type: \'string\', enumType: OrderStatus::class)]
    private OrderStatus $status = OrderStatus::Draft;

    public function transitionTo(OrderStatus $newStatus): void
    {
        if (!$this->status->canTransitionTo($newStatus)) {
            throw new \\DomainException(sprintf(\'Transición no permitida de %s a %s\', $this->status->value, $newStatus->value));
        }
        $this->status = $newStatus;
    }
}
```

Doctrine guarda el string (`draft`, `paid`) en la columna VARCHAR de la base de datos e hidrata automáticamente la instancia de Enum en la entidad PHP.',
            'takeaways' => 'Usa enumType en las columnas de Doctrine para mapear estados a Backed Enums tipados sin transformaciones manuales.',
        ],
    ],
    'video' => [
        'title' => 'PHP Enums With Practical Examples - Full PHP 8 Tutorial',
        'speaker' => 'PHP Architecture Tutorials',
        'youtube_id' => '5Cgio2OfOYk',
        'duration' => '25 min',
        'description' => 'Una guía práctica sobre la creación de Backed Enums, métodos de negocio, interfaces y modelado de flujos de trabajo en PHP 8.1 y superiores.',
        'key_takeaways' => [
            'Diferencias entre Pure Enums y Backed Enums.',
            'Cómo implementar métodos estáticos y de instancia dentro de un Enum.',
            'El uso de match ($this) para evaluar transiciones de estado deterministas.',
            'Manejo seguro de casos desconocidos mediante tryFrom() vs from().',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Model/OrderStatus.php',
        'title' => 'Máquina de Estados Finita con PHP Backed Enums',
        'tag' => 'PHP 8.4 Backed Enum State Machine',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

/**
 * Representa los estados del ciclo de vida de un pedido
 * funcionando como una máquina de estados determinista (FSM).
 */
enum OrderStatus: string
{
    case Draft = \'draft\';
    case Paid = \'paid\';
    case Shipped = \'shipped\';
    case Delivered = \'delivered\';
    case Cancelled = \'cancelled\';

    /**
     * Evalúa si es válido transicionar desde el estado actual hacia el estado objetivo.
     */
    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($target, [self::Shipped, self::Cancelled], true),
            self::Shipped => in_array($target, [self::Delivered, self::Cancelled], true),
            self::Delivered, self::Cancelled => false, // Estados terminales: no admiten salida
        };
    }

    /**
     * Determina si el estado actual es terminal (fin del ciclo de vida).
     */
    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Cancelled => true,
            default => false,
        };
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un programador junior usa strings mágicos para los estados y llena el código de condicionales dispersos como \'if ($status === "cancelado")\'. Un ingeniero Senior modela el flujo mediante un Backed Enum con métodos de transición y estados terminales, garantizando que el sistema rechace transiciones ilegales antes de tocar la base de datos.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Strings mágicos y transiciones descontroladas',
            'code' => '// Strings mágicos propensos a errores
class Order {
    public string $status = \'draft\';

    public function cancel() {
        // Peligro: Permite cancelar pedidos ya enviados o entregados!
        $this->status = \'cancelled\'; 
    }
}',
            'flaws' => [
                'Usa strings primitivos permitiendo errores tipográficos (\'canclled\').',
                'No valida si la transición desde el estado actual es legal en el negocio.',
                'Permite cancelar pedidos que ya fueron entregados al cliente.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Backed Enum con máquina de estados y \'match\'',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Model;

enum OrderStatus: string
{
    case Draft = \'draft\';
    case Paid = \'paid\';
    case Shipped = \'shipped\';
    case Delivered = \'delivered\';
    case Cancelled = \'cancelled\';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($target, [self::Shipped, self::Cancelled], true),
            self::Shipped => in_array($target, [self::Delivered, self::Cancelled], true),
            self::Delivered, self::Cancelled => false,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Cancelled => true,
            default => false,
        };
    }
}',
            'rationale' => [
                'Backed Enum enum OrderStatus: string restringe los estados a opciones cerradas.',
                'canTransitionTo() y isTerminal() modelan la máquina de estados determinista.',
                'Usa expresiones match para evaluación estricta y exhaustiva de casos.',
            ],
            'trade_offs' => [
                'Los Enums son estáticos; añadir un nuevo estado requiere desplegar código y migrar la base de datos si hay restricciones de columna.',
                'Para flujos de trabajo altamente dinámicos configurables por el usuario final, un motor de reglas en base de datos o Symfony Workflow puede ser más adecuado que un Enum estricto.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Enumerations RFC in PHP 8.1',
            'source' => 'PHP RFC: Enumerations',
            'quote' => 'Las enumeraciones son un tipo de datos que restringe el valor posible a un conjunto fijo de constantes semánticas de primera clase.',
            'author' => 'Ilija Tovilo & Larry Garfield',
            'explanation' => 'La propuesta oficial que introdujo los Enums como ciudadanos de primera clase en el motor de PHP.',
        ],
        [
            'topic' => 'Finite State Machines in Domain Modeling',
            'source' => 'Domain-Driven Design Reference',
            'quote' => 'Un proceso de negocio con fases discretas debe modelarse como una máquina de estados finita donde las transiciones están gobernadas por reglas explícitas.',
            'author' => 'Eric Evans',
            'explanation' => 'Previene estados corruptos garantizando que el avance del ciclo de vida respete las reglas del dominio.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP Manual Oficial: Enumeraciones (Enums)',
            'url' => 'https://www.php.net/manual/es/language.enumerations.php',
            'description' => 'Documentación oficial sobre sintaxis, Backed Enums y métodos en enumeraciones.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Workflow Component',
            'url' => 'https://symfony.com/doc/current/workflow.html',
            'description' => 'Componente empresarial de Symfony para gestionar flujos y máquinas de estado complejas.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Stitcher.io: PHP 8.1 Enums In Depth',
            'url' => 'https://stitcher.io/blog/php-81-enums',
            'description' => 'Guía completa de Brent Roose sobre casos de uso avanzados con Enums en PHP 8.1.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: PHP Enums como Máquinas de Estado Seguras',
        'objective' => 'Implementar el Backed Enum OrderStatus: string con los casos Draft, Paid, Shipped, Delivered y Cancelled, junto con los métodos canTransitionTo(self $target): bool e isTerminal(): bool usando match.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define el enum \'enum OrderStatus: string\' en el namespace \'App\\Domain\\Model\'.
3. Declara los 5 casos: Draft = \'draft\', Paid = \'paid\', Shipped = \'shipped\', Delivered = \'delivered\', Cancelled = \'cancelled\'.
4. Implementa el método \'canTransitionTo(self $target): bool\' gobernando el avance válido entre estados.
5. Implementa el método \'isTerminal(): bool\' retornando true únicamente para Delivered y Cancelled.',
        'filename' => 'src/Domain/Model/OrderStatus.php',
        'guide' => [
            'explanation' => 'Utiliza la expresión match ($this) para evaluar las transiciones válidas. Recuerda que Delivered y Cancelled son estados terminales que no admiten transición a ningún otro estado (retornan false).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe declararse el Backed Enum: enum OrderStatus: string con los casos Draft, Paid, Shipped, Delivered y Cancelled.',
            ],
            [
                'text' => '[Pista 2: Estructura] Implementa los métodos canTransitionTo(self $target): bool e isTerminal(): bool. Utiliza la expresión match ($this) para garantizar verificación exhaustiva.',
            ],
            [
                'text' => '[Pista 3: Snippet] public function isTerminal(): bool { return match ($this) { self::Delivered, self::Cancelled => true, default => false }; }.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

// TODO: Declara enum OrderStatus: string
enum OrderStatus: string
{
    // TODO: Define casos Draft, Paid, Shipped, Delivered, Cancelled

    public function canTransitionTo(self $target): bool
    {
        // TODO: Evalúa transiciones válidas con match ($this)
        return false;
    }

    public function isTerminal(): bool
    {
        // TODO: Retorna true solo para Delivered y Cancelled
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

enum OrderStatus: string
{
    case Draft = \'draft\';
    case Paid = \'paid\';
    case Shipped = \'shipped\';
    case Delivered = \'delivered\';
    case Cancelled = \'cancelled\';

    public function canTransitionTo(self $target): bool
    {
        return match ($this) {
            self::Draft => in_array($target, [self::Paid, self::Cancelled], true),
            self::Paid => in_array($target, [self::Shipped, self::Cancelled], true),
            self::Shipped => in_array($target, [self::Delivered, self::Cancelled], true),
            self::Delivered, self::Cancelled => false,
        };
    }

    public function isTerminal(): bool
    {
        return match ($this) {
            self::Delivered, self::Cancelled => true,
            default => false,
        };
    }
}
',
        'explanation' => 'OrderStatus es una máquina de estados finita modelada con Backed Enums nativos. El uso de match ($this) garantiza evaluación estricta y exhaustiva, impidiendo transiciones ilegales y delimitando los estados terminales.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: PHP Enums como Máquinas de Estado Seguras',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué diferencia existe entre un Pure Enum y un Backed Enum en PHP 8.1+?',
                'options' => [
                    'a' => 'Los Pure Enums solo funcionan en la línea de comandos (CLI).',
                    'b' => 'Un Backed Enum tiene asociado un valor escalar primitivo (: string o : int) accesible vía ->value, mientras que un Pure Enum solo representa un caso de identidad sin valor escalar subyacente.',
                    'c' => 'Los Backed Enums no pueden tener métodos.',
                    'd' => 'Los Pure Enums se compilan automáticamente a JSON.',
                ],
                'correct' => 'b',
                'explanation' => 'Los Backed Enums permiten una serialización directa con bases de datos y APIs mediante $enum->value y la reconstrucción segura con Enum::from($val).',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué es preferible usar \'match ($this)\' en lugar de \'switch ($this)\' dentro de los métodos de un Enum?',
                'options' => [
                    'a' => 'Porque \'match\' utiliza comparación estricta (===), no sufre de caídas (fall-through) accidentales y exige que todos los casos estén cubiertos o lanza un error fatal.',
                    'b' => 'Porque switch consume el triple de memoria zval en PHP 8.',
                    'c' => 'Porque switch no puede evaluar objetos.',
                    'd' => 'Porque match convierte los casos a enteros automáticamente.',
                ],
                'correct' => 'a',
                'explanation' => '\'match\' es exhaustivo: si añades un nuevo estado al Enum y olvidas actualizar un método que usa \'match\', el sistema falla inmediatamente previniendo ramas no contempladas en producción.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué define a un \'estado terminal\' en una máquina de estados de un pedido?',
                'options' => [
                    'a' => 'Un estado que se ejecuta exclusivamente en la consola de Linux.',
                    'b' => 'Un estado final del cual es imposible realizar transiciones a ningún otro estado posterior (ej. Delivered o Cancelled).',
                    'c' => 'Un estado que lanza una excepción PDOException.',
                    'd' => 'Un estado que reinicia el servidor web.',
                ],
                'correct' => 'b',
                'explanation' => 'En un estado terminal, el ciclo de vida del objeto ha concluido y ninguna mutación posterior está permitida.',
            ],
        ],
    ],
];
