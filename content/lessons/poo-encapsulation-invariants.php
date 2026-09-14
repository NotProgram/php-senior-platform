<?php

declare(strict_types=1);

return [
    'slug' => 'poo-encapsulation-invariants',
    'title' => 'Encapsulación, Constructores Nombrados & Protección de Invariantes',
    'module' => 'POO & Modelado',
    'minutes' => 40,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'La verdadera encapsulación en POO no consiste en ocultar propiedades con getters y setters, sino en vincular el estado a comportamientos que impidan físicamente que un objeto entre en un estado no válido. Las invariantes de negocio son condiciones que deben cumplirse siempre a lo largo de todo el ciclo de vida del objeto.',
        'problem' => 'El modelo de datos anémico, donde las clases son simples bolsas de propiedades con \'setBalance()\', permite que cualquier parte del código mute el estado sin validaciones, dispersando la lógica de negocio y permitiendo inconsistencias graves como cuentas con saldos ilegales.',
        'solution' => 'Eliminar setters públicos, adoptar constructores nombrados estáticos (Named Constructors) para encapsular la instanciación, exponer métodos semánticos de negocio (\'deposit\', \'withdraw\') y lanzar DomainException de forma temprana (Fail-Fast) si se violan las invariantes.',
        'problem_label' => 'El Problema: Modelos Anémicos y Estado Inconsistente en Memoria',
        'solution_label' => 'La Solución Senior: Encapsulación Estricta, Invariantes y Constructores Nombrados',
    ],
    'mental_model' => [
        'title' => 'El Cajero Automático Blindado vs La Caja Fuerte Abierta en la Acera',
        'analogy' => 'Imagina un banco donde el dinero se guarda en una caja fuerte abierta en plena calle con una manija giratoria que cualquiera puede mover a su antojo (`$account->setBalance($amount)`). Si alguien escribe `-5000`, la caja lo acepta; si dos personas meten la mano a la vez, el saldo se corrompe sin que nadie sepa quién lo hizo. Eso es un modelo de datos anémico con setters públicos. La verdadera **Encapsulación** es un **Cajero Automático (ATM) blindado**: nadie puede tocar los billetes físicamente. El cajero solo expone botones semánticos de negocio (`open()`, `deposit()`, `withdraw()`). El cajero verifica internamente las **invariantes de negocio** (reglas inviolables: saldo no negativo tras el límite de sobregiro, montos positivos). Si una operación intenta violar una invariante, la compuerta se traba al instante y activa una alarma de seguridad (`DomainException`).',
        'ascii_diagram' => 'MODELO ANÉMICO (Inseguro - "Caja fuerte en la acera"):
$account->setBalance(-500); // ¡Invariante rota! El objeto permite estado inválido.

MODELO RICO CON ENCAPSULACIÓN & INVARIANTES:
┌─────────────────────────────────────────────────────────────┐
│                       BankAccount                           │
│  private int $balance                                       │
│  private int $overdraftLimit                                │
├─────────────────────────────────────────────────────────────┤
│  + static open(int $initial, int $overdraft): BankAccount   │
│  + deposit(int $amount): void                               │
│  + withdraw(int $amount): void                              │
│  + getBalance(): int (Solo lectura)                         │
│                                                             │
│  [INVARIANTE INVIOLABLE]:                                    │
│  ($this->balance - $amount) >= -$this->overdraftLimit        │
│  └─ Si se viola ──> throw new DomainException(...)          │
└─────────────────────────────────────────────────────────────┘',
        'key_concept' => 'La encapsulación garantiza que un objeto sea imposible de poner en un estado inconsistente. Un objeto válido nace válido y permanece válido en cada milisegundo de su existencia.',
    ],
    'internals' => [
        'title' => 'Protección de Invariantes y Constructores Nombrados en PHP',
        'steps' => [
            [
                'phase' => '1. El Constructor Privado y Named Constructors',
                'description' => 'Hacer el constructor \'__construct()\' privado previene instanciaciones descontroladas. Métodos estáticos como \'BankAccount::open()\' comunican la intención semántica y validan las precondiciones de nacimiento.',
            ],
            [
                'phase' => '2. Eliminación Absoluta de Setters Genéricos',
                'description' => 'Un método \'setBalance()\' es una fuga masiva de abstracción. Las mutaciones deben reflejar eventos del mundo real: \'deposit(int $amount)\' o \'withdraw(int $amount)\'.',
            ],
            [
                'phase' => '3. Verificación de Invariantes en el Límite de Mutación',
                'description' => 'Cada método que altera el estado evalúa las reglas inviolables del negocio (invariantes). Si una regla se quiebra, la operación aborta inmediatamente antes de alterar la memoria interna.',
            ],
            [
                'phase' => '4. Excepciones Semánticas de Dominio',
                'description' => 'Lanzar \'DomainException\' en lugar de booleanos o excepciones técnicas comunica con precisión que una regla fundamental del dominio ha sido rechazada.',
            ],
            [
                'phase' => '5. Inmutabilidad de Identidad y Tipado Estricto',
                'description' => 'El balance se modela en enteros (centavos) para evitar errores de coma flotante IEEE 754, y el estado de la cuenta permanece protegido contra mutaciones no autorizadas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Por qué \'setBalance()\' destruye el diseño de software',
            'icon' => 'alert-triangle',
            'content' => 'Considera qué ocurre cuando permites setters genéricos en una entidad financiera:

1. **Lógica dispersa:** ¿Dónde se valida si el usuario puede retirar? En el controlador A, en el comando B, en la vista C... Si un programador olvida copiar la validación, nace un agujero de seguridad.
2. **Pérdida de semántica:** `setBalance(100)` no te dice si fue un depósito de nómina, una corrección de fraude o un retiro de cajero.
3. **Violación de \'Tell, Don\'t Ask\':** Obliga a los clientes a pedir el saldo, hacer la resta por fuera, y luego empujar el nuevo saldo hacia adentro.

Al encapsular con `withdraw()`, el objeto es el único responsable de validar su propio sobregiro, garantizando coherencia en el 100% de los casos.',
            'takeaways' => 'Nunca expongas setters públicos en entidades de dominio; sustitúyelos por métodos de negocio expresivos.',
        ],
        [
            'title' => 'Constructores Nombrados (Named Constructors) en la Práctica',
            'icon' => 'box',
            'content' => 'En PHP, una clase solo puede tener un único constructor `__construct()`. Los constructores nombrados estáticos resuelven esta limitación permitiendo múltiples formas de creación expresivas:

```php
class BankAccount
{
    private function __construct(private int $balance, private readonly int $overdraftLimit) {}

    public static function open(int $initialBalance, int $overdraftLimit = 0): self
    {
        if ($initialBalance < 0) throw new \\DomainException(\'Saldo inicial no puede ser negativo.\');
        return new self($initialBalance, $overdraftLimit);
    }

    public static function openWithPromotionalBonus(int $deposit, int $bonus): self
    {
        return new self($deposit + $bonus, 0);
    }
}
```

Cada método estático encapsula reglas de validación específicas para ese escenario de creación.',
            'takeaways' => 'Usa constructores estáticos nombrados para expresar claramente diferentes intenciones de instanciación y proteger invariantes desde el origen.',
        ],
    ],
    'video' => [
        'title' => 'RailsConf 2014 - All the Little Things by Sandi Metz',
        'speaker' => 'Sandi Metz',
        'youtube_id' => '8bZh5LMaSmE',
        'duration' => '43 min',
        'description' => 'Una de las conferencias más influyentes sobre diseño orientado a objetos, objetos pequeños, encapsulación de comportamiento y refactorización.',
        'key_takeaways' => [
            'Por qué los objetos deben ser pequeños, especializados y con responsabilidades únicas.',
            'Cómo la encapsulación de invariantes reduce la complejidad ciclomática de la aplicación.',
            'El principio Tell, Don\'t Ask y la eliminación de verificaciones externas de estado.',
            'Técnicas para convertir código procedural con estructuras de datos pasivas en modelos ricos.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Model/BankAccount.php',
        'title' => 'Entidad de Cuenta Bancaria con Invariantes Blindadas',
        'tag' => 'PHP 8.4 Rich Domain Entity',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use DomainException;

/**
 * Entidad de dominio que encapsula el saldo y protege estrictamente
 * la invariante de fondos disponibles considerando el límite de sobregiro.
 */
class BankAccount
{
    private function __construct(
        private int $balance,
        private readonly int $overdraftLimit
    ) {}

    /**
     * Constructor nombrado estático para apertura de cuenta.
     */
    public static function open(int $initialDeposit = 0, int $overdraftLimit = 0): self
    {
        if ($initialDeposit < 0) {
            throw new DomainException(\'El depósito inicial no puede ser negativo.\');
        }

        if ($overdraftLimit < 0) {
            throw new DomainException(\'El límite de sobregiro debe ser mayor o igual a cero.\');
        }

        return new self($initialDeposit, $overdraftLimit);
    }

    /**
     * Ingresa fondos a la cuenta.
     */
    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new DomainException(\'El monto a depositar debe ser estrictamente positivo.\');
        }

        $this->balance += $amount;
    }

    /**
     * Retira fondos de la cuenta asegurando que no se exceda el sobregiro permitido.
     */
    public function withdraw(int $amount): void
    {
        if ($amount <= 0) {
            throw new DomainException(\'El monto a retirar debe ser estrictamente positivo.\');
        }

        if (($this->balance - $amount) < -$this->overdraftLimit) {
            throw new DomainException(sprintf(
                \'Fondos insuficientes: El retiro de %d supera el saldo actual (%d) con sobregiro (%d).\',
                $amount,
                $this->balance,
                $this->overdraftLimit
            ));
        }

        $this->balance -= $amount;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getOverdraftLimit(): int
    {
        return $this->overdraftLimit;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un programador junior crea getters y setters para todo con atajos del IDE, externalizando la lógica de negocio a controladores y servicios de aplicación. Un ingeniero Senior hace privados los constructores y propiedades, prohíbe los setters genéricos, y encapsula las invariantes en métodos semánticos que garantizan que el objeto nunca pueda corromperse en memoria.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Modelo anémico con setter público peligroso',
            'code' => '// Modelo anémico vulnerable
class BankAccount {
    public int $balance = 0;

    public function setBalance(int $amount): void {
        // Peligro: permite cualquier mutación sin validar sobregiro ni contexto
        $this->balance = $amount;
    }
}',
            'flaws' => [
                'Expone setBalance() permitiendo que cualquier llamador rompa las reglas del banco.',
                'No protege la invariante de sobregiro máximo permitido.',
                'Falta de constructores nombrados para comunicar escenarios de negocio.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Constructor nombrado, encapsulación e invariantes',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Model;

use DomainException;

class BankAccount
{
    private function __construct(private int $balance, private readonly int $overdraftLimit) {}

    public static function open(int $initial = 0, int $overdraft = 0): self
    {
        if ($initial < 0 || $overdraft < 0) throw new DomainException(\'Invalid setup\');
        return new self($initial, $overdraft);
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) throw new DomainException(\'Deposit must be positive\');
        $this->balance += $amount;
    }

    public function withdraw(int $amount): void
    {
        if ($amount <= 0 || ($this->balance - $amount) < -$this->overdraftLimit) {
            throw new DomainException(\'Insufficient funds\');
        }
        $this->balance -= $amount;
    }
}',
            'rationale' => [
                'Constructor privado y named constructor open() garantizan precondiciones válidas.',
                'Métodos semánticos deposit() y withdraw() validan invariantes de negocio in situ.',
                'Prohíbe totalmente setBalance(), previniendo mutaciones externas descontroladas.',
            ],
            'trade_offs' => [
                'Los ORMs tradicionales pueden requerir reflexión o configuración especial para mapear propiedades privadas sin setters.',
                'Escribir métodos de negocio requiere más análisis de dominio inicial que generar getters/setters mecánicos.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Anemic Domain Model Antipattern',
            'source' => 'Martin Fowler Bliki: AnemicDomainModel',
            'quote' => 'El horror fundamental de este antipatrón es que es tan contrario a la idea básica de la orientación a objetos, que consiste en combinar datos y procesos.',
            'author' => 'Martin Fowler',
            'explanation' => 'Tener entidades con solo getters y setters convierte el software en un diseño procedural disfrazado con sintaxis de clases.',
        ],
        [
            'topic' => 'Tell, Don\'t Ask Principle',
            'source' => 'The Pragmatic Programmer',
            'quote' => 'Dile a los objetos qué hacer; no les pidas sus datos para hacer el trabajo por ellos.',
            'author' => 'Andy Hunt & Dave Thomas',
            'explanation' => 'En lugar de pedir el balance y restarle afuera, dile a la cuenta $account->withdraw($amount).',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: Anemic Domain Model',
            'url' => 'https://martinfowler.com/bliki/AnemicDomainModel.html',
            'description' => 'Artículo canónico que define el antipatrón del modelo anémico y por qué debe evitarse.',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Domain-Driven Design: Tackling Complexity in Software',
            'url' => 'https://www.domainlanguage.com/ddd/',
            'description' => 'El libro fundacional de Eric Evans sobre modelado de dominio e invariantes.',
            'type' => 'BOOK',
        ],
        [
            'title' => 'Refactoring Guru: Encapsulate Field',
            'url' => 'https://refactoring.guru/es/encapsulate-field',
            'description' => 'Guía práctica de refactorización hacia encapsulación y protección de estado.',
            'type' => 'REFERENCE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Encapsulación & Protección de Invariantes',
        'objective' => 'Implementar la clase BankAccount con constructor nombrado estático open(), métodos deposit() y withdraw(), y protección estricta contra sobregiros.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'BankAccount\' en el namespace \'App\\Domain\\Model\'.
3. Implementa el constructor nombrado estático \'public static function open(int $initialBalance = 0, int $overdraftLimit = 0): self\'.
4. Implementa \'public function deposit(int $amount): void\' validando que sea mayor a 0.
5. Implementa \'public function withdraw(int $amount): void\' lanzando \'DomainException\' si excede el balance más el sobregiro.
6. NO expongas ningún método \'setBalance()\'.',
        'filename' => 'src/Domain/Model/BankAccount.php',
        'guide' => [
            'explanation' => 'Recuerda que si el balance resultante ($this->balance - $amount) es menor a -$this->overdraftLimit, debes lanzar una DomainException. El constructor debe ser privado.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse el constructor nombrado estático open() para encapsular la creación y validar que ni el depósito inicial ni el sobregiro sean negativos.',
            ],
            [
                'text' => '[Pista 2: Estructura] La clase debe exponer métodos de negocio deposit() y withdraw(). Prohíbe terminantemente cualquier método llamado setBalance.',
            ],
            [
                'text' => '[Pista 3: Snippet] En withdraw: if (($this->balance - $amount) < -$this->overdraftLimit) { throw new \\DomainException(\'Fondos insuficientes\'); } $this->balance -= $amount;.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use DomainException;

class BankAccount
{
    private function __construct(
        private int $balance,
        private readonly int $overdraftLimit
    ) {}

    public static function open(int $initialBalance = 0, int $overdraftLimit = 0): self
    {
        // TODO: Valida e instancia mediante constructor privado
        return new self($initialBalance, $overdraftLimit);
    }

    public function deposit(int $amount): void
    {
        // TODO: Valida que el monto sea positivo y suma al balance
    }

    public function withdraw(int $amount): void
    {
        // TODO: Valida sobregiro y lanza DomainException ante violación
    }

    public function getBalance(): int
    {
        return $this->balance;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use DomainException;

class BankAccount
{
    private function __construct(
        private int $balance,
        private readonly int $overdraftLimit
    ) {}

    public static function open(int $initialBalance = 0, int $overdraftLimit = 0): self
    {
        if ($initialBalance < 0) {
            throw new DomainException(\'El saldo inicial no puede ser negativo.\');
        }

        if ($overdraftLimit < 0) {
            throw new DomainException(\'El límite de sobregiro no puede ser negativo.\');
        }

        return new self($initialBalance, $overdraftLimit);
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new DomainException(\'El monto a depositar debe ser mayor a cero.\');
        }

        $this->balance += $amount;
    }

    public function withdraw(int $amount): void
    {
        if ($amount <= 0) {
            throw new DomainException(\'El monto a retirar debe ser mayor a cero.\');
        }

        if (($this->balance - $amount) < -$this->overdraftLimit) {
            throw new DomainException(\'Fondos insuficientes considerando el límite de sobregiro.\');
        }

        $this->balance -= $amount;
    }

    public function getBalance(): int
    {
        return $this->balance;
    }

    public function getOverdraftLimit(): int
    {
        return $this->overdraftLimit;
    }
}
',
        'explanation' => 'La clase BankAccount encapsula el estado interno protegiendo la invariante de sobregiro. El uso del constructor nombrado estático open() y la omisión de setters aseguran que la cuenta nunca pueda existir en un estado financieramente inconsistente.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Encapsulación, Constructores Nombrados & Protección de Invariantes',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un método \'setBalance(int $amount)\' se considera una violación del principio de encapsulación?',
                'options' => [
                    'a' => 'Porque los métodos que comienzan con \'set\' están deprecados en PHP 8.4.',
                    'b' => 'Porque expone la estructura interna y delega la responsabilidad de validar invariantes de negocio al llamador externo.',
                    'c' => 'Porque los enteros en PHP no soportan valores negativos.',
                    'd' => 'Porque obliga a que la clase sea declarada como abstracta.',
                ],
                'correct' => 'b',
                'explanation' => 'Un setter anémico despoja al objeto de su capacidad de defender sus propias reglas, dispersando la lógica de validación por toda la aplicación.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué es una \'invariante de negocio\' en el diseño orientado a objetos?',
                'options' => [
                    'a' => 'Una variable constante que nunca se puede compilar.',
                    'b' => 'Una condición o regla del dominio que debe ser verdadera en todo momento durante el ciclo de vida de una entidad.',
                    'c' => 'Una directiva de configuración de php.ini que no cambia entre entornos.',
                    'd' => 'Un método estático que no recibe argumentos.',
                ],
                'correct' => 'b',
                'explanation' => 'Las invariantes son las reglas inviolables del negocio (ej. \'una cuenta no puede tener saldo menor al sobregiro permitido\'). El objeto debe protegerlas activamente ante cualquier operación.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el beneficio de hacer privado el constructor \'__construct()\' y usar un Named Constructor como \'BankAccount::open()\'?',
                'options' => [
                    'a' => 'Aumenta la velocidad de ejecución de la CPU en un 40%.',
                    'b' => 'Comunica la intención del negocio, permite múltiples formas de inicialización válidas y evita la creación de objetos en estado incompleto.',
                    'c' => 'Permite que la clase herede de múltiples clases base a la vez.',
                    'd' => 'Desactiva el recolector de basura para esa instancia.',
                ],
                'correct' => 'b',
                'explanation' => 'Los constructores nombrados estáticos ofrecen nombres semánticos expresivos (\'open\', \'reopen\', \'fromSnapshot\') y blindan las precondiciones de inicialización.',
            ],
        ],
    ],
];
