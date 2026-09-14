<?php

declare(strict_types=1);

return [
    'slug' => 'poo-value-objects-dtos',
    'title' => 'Value Objects vs DTOs vs Entidades: Taxonomía y Modelado Estratégico',
    'module' => 'POO & Modelado',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En el diseño orientado al dominio (DDD), los objetos se clasifican rigurosamente según su ciclo de vida e identidad: las Entidades poseen un identificador único mutable a lo largo del tiempo, los Value Objects son inmutables y se definen exclusivamente por sus atributos estructurales, y los DTOs son contenedores pasivos para transporte de datos a través de fronteras.',
        'problem' => 'El uso de tipos primitivos sueltos (\'int $amount\', \'string $email\') genera el antipatrón \'Primitive Obsession\'. Si representas dinero como un simple entero o flotante, puedes sumar accidentalmente 100 dólares con 100 yenes sin que el compilador te alerte, o propagar emails mal formados por toda la aplicación.',
        'solution' => 'Modelar conceptos de negocio con Value Objects inmutables usando \'readonly class\', encapsular validaciones en el constructor, definir métodos de igualdad estructural (\'equals()\') y operaciones que retornen nuevas instancias inmutables (\'add()\').',
        'problem_label' => 'El Problema: Primitive Obsession y Mutaciones Fantasma',
        'solution_label' => 'La Solución Senior: Value Objects Inmutables con \'readonly class\'',
    ],
    'mental_model' => [
        'title' => 'El Billete de $20 vs El Pasaporte vs La Ficha de Emisión',
        'analogy' => 'Imagina tres conceptos del mundo real:

1. **Value Object (El Billete de $20):** Si tienes un billete de $20 dólares en el bolsillo y lo cambias con el billete de $20 de tu amigo, a nadie le importa el número de serie. Ambos billetes son **idénticos e intercambiables** porque su identidad la determinan sus atributos (`$cents = 2000, $currency = \'USD\'`). Además, un billete de $20 nunca se transforma en uno de $50 mágicamente; si sumas dinero, recibes un billete nuevo en tus manos (Inmutabilidad estricta: `readonly class Money`).
2. **Entidad (El Pasaporte):** Dos pasaportes pueden tener el mismo nombre y nacionalidad, pero son completamente distintos porque cada uno tiene un número único global. Una persona puede cambiar de nombre, dirección o estado civil (mutabilidad de ciclo de vida), pero sigue siendo la misma persona a lo largo de los años.
3. **DTO (La Ficha de Solicitud de Trámite):** Es un formulario de papel transparente que solo sirve para transportar datos desde la ventanilla (HTTP Request) hasta la oficina de validación (Controller). No tiene lógica de negocio ni cálculos; solo viaja.',
        'ascii_diagram' => 'TAXONOMÍA ESTRATÉGICA DE OBJETOS:

1. VALUE OBJECT (Inmutable, sin ID, igualdad por atributos):
   readonly class Money { int $cents, string $currency }
   $m1 = new Money(2000, \'USD\');
   $m2 = new Money(2000, \'USD\');
   $m1->equals($m2) ──> TRUE (Son idénticos)
   $m3 = $m1->add(new Money(500, \'USD\')); ──> Retorna NUEVA instancia inmutable

2. ENTIDAD (Ciclo de vida persistente, ID único global, igualdad por ID):
   class User { private Uuid $id; private string $name; }
   $user1->getId()->equals($user2->getId()) ──> Determina si es la misma persona

3. DTO (Transporte agnóstico entre fronteras de arquitectura):
   readonly class RegisterUserRequest { public string $email; public string $name; }',
        'key_concept' => 'Un Value Object nunca cambia. Si operas sobre él (sumar, restar, formatear), obtienes una nueva instancia. Esto elimina de raíz el 90% de los bugs de concurrencia y mutaciones colaterales en PHP.',
    ],
    'internals' => [
        'title' => 'Anatomía y Reglas del Value Object en PHP 8.4',
        'steps' => [
            [
                'phase' => '1. Inmutabilidad Estricta con \'readonly class\'',
                'description' => 'Al declarar \'readonly class Money\', PHP impide que cualquier propiedad sea reasignada tras la inicialización en el constructor, garantizando inmutabilidad a nivel de motor.',
            ],
            [
                'phase' => '2. Validación de Entrada (Self-Validation)',
                'description' => 'El constructor valida las precondiciones (centavos >= 0, formato ISO de moneda válido). Es físicamente imposible que exista una instancia de Money con datos corruptos.',
            ],
            [
                'phase' => '3. Igualdad Estructural (Structural Equality)',
                'description' => 'Dos Value Objects son iguales si todos sus atributos son idénticos. El método \'equals(self $other): bool\' evalúa centavos y divisas.',
            ],
            [
                'phase' => '4. Métodos de Transformación Inmutables',
                'description' => 'El método \'add(self $other): self\' verifica que ambas divisas coincidan (lanzando InvalidArgumentException si difieren) y retorna \'new self($this->cents + $other->cents, $this->currency)\'.',
            ],
            [
                'phase' => '5. Eliminación de Coma Flotante en Finanzas',
                'description' => 'Representar dinero en centavos enteros (int) elimina para siempre los errores de redondeo de números de coma flotante IEEE 754 (ej. 0.1 + 0.2 = 0.30000000000000004).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'La Catástrofe de los Flotantes en Sistemas Financieros',
            'icon' => 'alert-circle',
            'content' => '¿Por qué un ingeniero Senior NUNCA almacena dinero como `float`?

Los números de coma flotante en computadoras se representan en base 2 binaria (estándar IEEE 754). Números decimales simples como `0.1` o `0.7` son fracciones periódicas infinitas en binario.

Ejecuta esto en PHP:
```php
$a = 0.1 + 0.2;
$b = 0.3;
var_dump($a === $b); // ¡FALSE! $a es 0.3000000000000000444...
```

Si calculas el balance de 1 millón de transacciones bancarias con flotantes, perderás o crearás miles de dólares por errores acumulativos de redondeo. La regla de oro en ingeniería financiera es **trabajar siempre con números enteros representando la unidad atómica mínima (centavos)** encapsulados en un Value Object `Money`.',
            'takeaways' => 'Usa siempre enteros para centavos y Value Objects Money para garantizar exactitud matemática absoluta en operaciones financieras.',
        ],
        [
            'title' => 'Value Objects vs DTOs: La Distinción Arquitectónica',
            'icon' => 'git-commit',
            'content' => 'A menudo los desarrolladores confunden DTOs y Value Objects porque ambos pueden ser inmutables con `readonly class`.

**Diferencias Fundamentales:**
• **DTO (Data Transfer Object):** No tiene lógica de dominio. Solo transporta datos desestructurados desde el exterior hacia la aplicación (ej. JSON de un webhook). No garantiza que los datos sean válidos para el negocio; solo los mueve.
• **Value Object:** Es el corazón del dominio. Encapsula validaciones ricas, métodos de cálculo (`add`, `subtract`, `multiply`), reglas de negocio y garantiza que los datos sean semánticamente correctos en todo momento.

Un DTO `CheckoutRequest` recibe strings crudas; el servicio de aplicación las convierte en Value Objects `Money` y `EmailAddress` antes de tocar las entidades.',
            'takeaways' => 'Los DTOs transportan datos a través de fronteras; los Value Objects modelan y protegen conceptos dentro del dominio.',
        ],
    ],
    'video' => [
        'title' => 'PHP, Value Objects and You - Daniel Leech',
        'speaker' => 'Daniel Leech',
        'youtube_id' => 'woNLYVamn3A',
        'duration' => '38 min',
        'description' => 'Una presentación profunda sobre cómo erradicar el antipatrón Primitive Obsession en PHP moderno utilizando Value Objects inmutables.',
        'key_takeaways' => [
            'Por qué los tipos primitivos nativos no son suficientes para modelar lógica empresarial.',
            'La semántica de igualdad estructural y cómo implementarla limpiamente.',
            'Integración de Value Objects con Doctrine ORM como Embeddables.',
            'El impacto de \'readonly class\' en la seguridad de memoria en PHP 8.2 y 8.4.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Model/Money.php',
        'title' => 'Value Object Inmutable de Dinero con \'readonly class\'',
        'tag' => 'PHP 8.4 Immutable Value Object',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

/**
 * Value Object inmutable que modela una cantidad monetaria exacta en centavos
 * y una divisa ISO 4217, garantizando igualdad estructural y aritmética segura.
 */
readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency
    ) {
        if ($cents < 0) {
            throw new InvalidArgumentException(\'El monto monetario no puede ser negativo.\');
        }

        $normalizedCurrency = strtoupper(trim($currency));
        if (strlen($normalizedCurrency) !== 3) {
            throw new InvalidArgumentException(\'La divisa debe ser un código ISO 4217 de 3 letras (ej. USD, EUR).\');
        }
    }

    /**
     * Suma dos montos monetarios retornando una NUEVA instancia inmutable.
     */
    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                \'No se pueden sumar divisas incompatibles: %s y %s.\',
                $this->currency,
                $other->currency
            ));
        }

        return new self($this->cents + $other->cents, $this->currency);
    }

    /**
     * Evalúa la igualdad estructural de dos Value Objects Money.
     */
    public function equals(self $other): bool
    {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior pasa \'int $price\' y \'string $currency\' como parámetros separados por toda la aplicación, arriesgando mezclar euros con dólares y redondeos imprecisos con flotantes. Un ingeniero Senior encapsula el dinero en un Value Object inmutable con \'readonly class\', delegando la validación y operaciones matemáticas al objeto de valor.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Primitive Obsession y coma flotante peligrosa',
            'code' => '// Código con Primitive Obsession y flotantes
function chargeOrder(float $amount, string $curr) {
    // Si sumamos 0.1 + 0.2 con floats, perdemos precisión
    $total = $amount + 10.50; 
    // Ninguna validación de que $curr sea una divisa válida
    return [$total, $curr];
}',
            'flaws' => [
                'Usa float causando errores de redondeo acumulativo en sistemas financieros.',
                'Primitive Obsession: no hay encapsulación ni validación del formato de divisa.',
                'Permite mezclar accidentalmente monedas distintas sin que el compilador proteste.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Value Object inmutable con \'readonly class\' y centavos',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

readonly class Money
{
    public function __construct(public int $cents, public string $currency)
    {
        if ($cents < 0 || strlen($currency) !== 3) {
            throw new InvalidArgumentException(\'Invalid money values\');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(\'Currency mismatch\');
        }
        return new self($this->cents + $other->cents, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents && $this->currency === $other->currency;
    }
}',
            'rationale' => [
                'Usa readonly class para garantizar inmutabilidad absoluta en memoria.',
                'Almacena centavos enteros eliminando imprecisiones de coma flotante.',
                'Impide sumar divisas incompatibles mediante Fail-Fast y asegura igualdad estructural.',
            ],
            'trade_offs' => [
                'Crear nuevas instancias inmutables en operaciones matemáticas intensivas genera pequeños zvals que el GC de PHP recolecta eficientemente.',
                'Requiere mapeadores Embeddable en Doctrine ORM para persistir las propiedades del VO en la misma tabla de la base de datos.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Value Object Definition',
            'source' => 'Domain-Driven Design: Tackling Complexity in the Heart of Software',
            'quote' => 'Cuando te preocupas solo por los atributos de un elemento de ese modelo, clasifícalo como un Value Object. Trata el Value Object como inmutable.',
            'author' => 'Eric Evans',
            'explanation' => 'El libro canónico de DDD que sentó las bases de la inmutabilidad y la igualdad estructural.',
        ],
        [
            'topic' => 'Primitive Obsession',
            'source' => 'Refactoring: Improving the Design of Existing Code',
            'quote' => 'Primitive Obsession es el uso de tipos de datos primitivos para representar conceptos simples del dominio, como dinero, coordenadas o rangos.',
            'author' => 'Martin Fowler',
            'explanation' => 'Reemplazar primitivos por Value Objects hace que el código sea auto-documentado y a prueba de errores lógicos.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: Value Object',
            'url' => 'https://martinfowler.com/bliki/ValueObject.html',
            'description' => 'Artículo definitivo sobre inmutabilidad, igualdad por valor y sustitución.',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'PHP Manual Oficial: Readonly Classes en PHP 8.2+',
            'url' => 'https://www.php.net/manual/es/language.oop5.basic.php#language.oop5.basic.class.readonly',
            'description' => 'Especificación oficial del motor sobre clases de solo lectura.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Doctrine ORM: Embeddables & Value Objects',
            'url' => 'https://www.doctrine-project.org/projects/doctrine-orm/en/current/tutorials/embeddables.html',
            'description' => 'Cómo mapear Value Objects directamente en entidades de base de datos relacionales sin tablas extra.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Value Objects vs DTOs vs Entidades',
        'objective' => 'Implementar el Value Object inmutable Money utilizando \'readonly class Money\' con propiedades en centavos ($cents), divisa ISO ($currency), validación de divisas en add() e igualdad estructural con equals().',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'readonly class Money\' en el namespace \'App\\Domain\\Model\'.
3. Declara en el constructor \'public int $cents\' y \'public string $currency\'.
4. Implementa el método \'add(self $other): self\' que lance InvalidArgumentException si las divisas son distintas y retorne una nueva instancia.
5. Implementa el método \'equals(self $other): bool\' evaluando centavos y divisa.',
        'filename' => 'src/Domain/Model/Money.php',
        'guide' => [
            'explanation' => 'Utiliza constructor property promotion dentro de una readonly class. En add(), valida if ($this->currency !== $other->currency) { throw new \\InvalidArgumentException(...); }. Retorna una nueva instancia con new self($this->cents + $other->cents, $this->currency).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase Money (preferiblemente readonly class Money para inmutabilidad estricta) encapsulando centavos enteros ($cents) y divisa ($currency).',
            ],
            [
                'text' => '[Pista 2: Estructura] Implementa los métodos add(self $other): self y equals(self $other): bool. Lanza InvalidArgumentException si intentas sumar divisas distintas.',
            ],
            [
                'text' => '[Pista 3: Snippet] readonly class Money { public function __construct(public int $cents, public string $currency) {} public function add(self $other): self { if ($this->currency !== $other->currency) throw new \\InvalidArgumentException(\'Divisas incompatibles\'); return new self($this->cents + $other->cents, $this->currency); } }.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

// TODO: Declara readonly class Money
class Money
{
    // TODO: Encapsula $cents y $currency en el constructor

    public function add(self $other): self
    {
        // TODO: Lanza InvalidArgumentException ante divisas distintas y retorna nueva instancia
        return $this;
    }

    public function equals(self $other): bool
    {
        // TODO: Evalúa igualdad de centavos y divisa
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

readonly class Money
{
    public function __construct(
        public int $cents,
        public string $currency
    ) {
        if ($cents < 0) {
            throw new InvalidArgumentException(\'El monto no puede ser negativo.\');
        }

        if (strlen($currency) !== 3) {
            throw new InvalidArgumentException(\'La divisa debe ser un código ISO de 3 caracteres.\');
        }
    }

    public function add(self $other): self
    {
        if ($this->currency !== $other->currency) {
            throw new InvalidArgumentException(sprintf(
                \'No se pueden sumar montos con diferentes divisas: %s y %s.\',
                $this->currency,
                $other->currency
            ));
        }

        return new self($this->cents + $other->cents, $this->currency);
    }

    public function equals(self $other): bool
    {
        return $this->cents === $other->cents
            && $this->currency === $other->currency;
    }
}
',
        'explanation' => 'Money es un Value Object canónico: inmutable mediante readonly class, auto-validado en el constructor, con operaciones aritméticas que devuelven nuevas instancias sin efectos secundarios e igualdad estructural basada en sus atributos.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Value Objects vs DTOs vs Entidades: Taxonomía y Modelado Estratégico',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la diferencia fundamental de identidad entre una Entidad y un Value Object?',
                'options' => [
                    'a' => 'Las entidades se escriben en minúsculas y los Value Objects en mayúsculas.',
                    'b' => 'Una Entidad posee un identificador único que define su identidad a lo largo del tiempo, mientras que dos Value Objects son idénticos si sus atributos poseen los mismos valores.',
                    'c' => 'Los Value Objects solo pueden tener propiedades de tipo string.',
                    'd' => 'Las entidades nunca pueden ser persistidas en una base de datos relacional.',
                ],
                'correct' => 'b',
                'explanation' => 'La igualdad en Value Objects es estructural (por atributos). En entidades, la igualdad se determina exclusivamente por su ID único global (UUID / clave primaria), independientemente de que otros campos hayan mutado.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué un método que suma dinero en un Value Object (\'add()\') debe retornar una nueva instancia en lugar de modificar $this->cents?',
                'options' => [
                    'a' => 'Porque PHP prohíbe reasignar números enteros en memoria.',
                    'b' => 'Para preservar la inmutabilidad y evitar efectos secundarios invisibles en otras variables u objetos que compartían la misma referencia en memoria.',
                    'c' => 'Porque de lo contrario se produce una fuga de memoria en OpCache.',
                    'd' => 'Para forzar que el recolector de basura elimine la base de datos.',
                ],
                'correct' => 'b',
                'explanation' => 'Si mutaras la instancia original, cualquier otra parte del sistema que tuviera esa variable (ej. un carrito de compras previo o una factura emitida) vería su valor alterado sin saberlo (efecto colateral fantasma).',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué problema evita modelar dinero en centavos enteros ($cents) en lugar de un número flotante ($amount)?',
                'options' => [
                    'a' => 'Evita los errores de redondeo de la representación binaria IEEE 754 de coma flotante.',
                    'b' => 'Permite que el código funcione en servidores sin procesador de 64 bits.',
                    'c' => 'Reduce el tiempo de ejecución en un 99%.',
                    'd' => 'Evita tener que usar el operador de igualdad estricta ===.',
                ],
                'correct' => 'a',
                'explanation' => 'Las computadoras no pueden representar exactamente muchas fracciones decimales en binario (como 0.1). Usar centavos enteros garantiza operaciones aritméticas 100% exactas sin discrepancias de redondeo.',
            ],
        ],
    ],
];
