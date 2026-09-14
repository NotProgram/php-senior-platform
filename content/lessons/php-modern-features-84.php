<?php

declare(strict_types=1);

return [
    'slug' => 'php-modern-features-84',
    'title' => 'PHP 8.4: Property Hooks, Visibilidad Asimétrica & Modelado Moderno',
    'module' => 'PHP Moderno',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'PHP 8.4 introduce Property Hooks (inspirados en Kotlin, C# y Swift) y Visibilidad Asimétrica (public private(set)), permitiendo interceptar lecturas y mutaciones de propiedades directamente en su declaración sin escribir getters y setters repetitivos.',
        'problem' => 'El modelado tradicional en POO generaba cientos de líneas de \'código pegajoso\' (boilerplate): propiedades privadas con getters y setters triviales que inflaban las clases, diluían las invariantes de negocio y tentaban a los desarrolladores a volver públicas las propiedades para ahorrarse escribir métodos.',
        'solution' => 'Aprovechar Property Hooks con lógica de validación ligada a la propiedad, usar visibilidad asimétrica para exponer lectura pública con mutación estrictamente encapsulada, y adoptar funciones nativas modernas como array_find() y la instanciación sin paréntesis.',
        'problem_label' => 'El Problema: La Peste del Boilerplate y Getters/Setters Anémicos',
        'solution_label' => 'La Solución Senior: Property Hooks, Asymmetric Visibility e Invariantes Declarativas',
    ],
    'mental_model' => [
        'title' => 'La Válvula Inteligente Integrada vs Las Tuberías Ocultas con Intercomunicador',
        'analogy' => 'Durante más de dos décadas en POO tradicional, para proteger una propiedad como `$balance` contra números negativos, estabas obligado a esconder la tubería detrás de un muro (propiedad `private int $balance`) y perforar dos ventanillas con intercomunicador: `public function getBalance(): int` y `public function setBalance(int $value): void`. Esto triplicaba el tamaño del archivo con código redundante. En PHP 8.4, los **Property Hooks** y la **Visibilidad Asimétrica** convierten la abertura de la tubería en una **válvula inteligente visible**: puedes hacer que la tubería sea pública para lectura (`public private(set)`), pero cuando alguien intenta verter agua (asignar `$account->balance = 500`), la válvula intercepta la asignación automáticamente mediante el hook `set`, verifica que el monto sea válido, y actualiza el casillero interno sin necesidad de métodos artificiales.',
        'ascii_diagram' => 'MODELO TRADICIONAL (PHP 5 a 8.3): 30 líneas de boilerplate
class Account {
    private int $balance;
    public function getBalance(): int { return $this->balance; }
    public function setBalance(int $amount): void {
        if ($amount < 0) throw new InvalidArgumentException();
        $this->balance = $amount;
    }
}

MODELADO MODERNO PHP 8.4: Conciso, declarativo y blindado
class BankAccount {
    public private(set) int $balance = 0 {
        set {
            if ($value < 0) {
                throw new \\InvalidArgumentException(\'El saldo no puede ser negativo.\');
            }
            $this->balance = $value;
        }
    }

    public function deposit(int $amount): void {
        if ($amount <= 0) throw new \\InvalidArgumentException(\'Monto debe ser positivo.\');
        $this->balance += $amount; // Activa el hook \'set\' automáticamente
    }
}

ASIGNACIÓN TRANSPARENTE:
$account->deposit(100); ──> $this->balance += 100 ──> Hook \'set\' valida invariante
echo $account->balance; ──> Lectura O(1) de propiedad pública sin getBalance()',
        'key_concept' => 'Property Hooks y Visibilidad Asimétrica garantizan encapsulación estricta sin obligar a escribir métodos getter y setter anémicos. La validación vive exactamente donde se define la propiedad.',
    ],
    'internals' => [
        'title' => 'Anatomía de Property Hooks y Visibilidad Asimétrica en Zend Engine',
        'steps' => [
            [
                'phase' => '1. Visibilidad Asimétrica: public private(set)',
                'description' => 'Permite que una propiedad sea legible públicamente desde cualquier contexto sin requerir un método getter, mientras que las asignaciones directas están restringidas al ámbito de la clase o sus descendientes (protected(set)).',
            ],
            [
                'phase' => '2. Backed Properties vs Virtual Properties',
                'description' => 'Una propiedad es \'respaldada\' (backed) si reserva almacenamiento en zval y usa $this->prop = $value. Es \'virtual\' si solo define un getter computado (ej. fullName { get => $this->first . \' \' . $this->last; }) sin reservar memoria física.',
            ],
            [
                'phase' => '3. Intercepción en Tiempo de Compilación',
                'description' => 'El compilador Zend traduce la asignación $obj->prop = $val a una instrucción ZEND_ASSIGN_OBJ_HOOK, llamando a la función C del hook set sin pasar por métodos mágicos lentos como __get o __set.',
            ],
            [
                'phase' => '4. Nuevas Funciones de Array en C (array_find, array_all)',
                'description' => 'PHP 8.4 añade funciones funcionales implementadas en C optimizado que buscan elementos en listas con callbacks de predicado, deteniéndose en la primera coincidencia (cortocircuito).',
            ],
            [
                'phase' => '5. Instanciación sin Paréntesis',
                'description' => 'La sintaxis \'new ClassName()->method()\' ya no requiere paréntesis envolventes \'(new ClassName())->method()\', simplificando la creación de objetos fluidos y fábricas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Property Hooks vs Métodos Mágicos (__get / __set)',
            'icon' => 'zap',
            'content' => 'Históricamente, algunos desarrolladores intentaban evitar getters usando métodos mágicos `__get()` y `__set()`.

Esa práctica es destructiva en proyectos senior:
1. **Rendimiento:** `__get` y `__set` desactivan optimizaciones de Zend VM, ralentizando el acceso hasta por 4x.
2. **Análisis Estático ciego:** PHPStan y PhpStorm no pueden inferir tipos fácilmente a través de métodos mágicos sin docblocks pesados.
3. **Ambigüedad:** Ocultan errores tipográficos en propiedades no declaradas.

Los **Property Hooks de PHP 8.4** son ciudadanos de primera clase en el sistema de tipos: están tipados estáticamente, soportados de forma nativa por PHPStan en nivel 8+, y compilados a instrucciones optimizadas en la máquina virtual.',
            'takeaways' => 'Nunca uses métodos mágicos __get y __set para simular propiedades: usa Property Hooks nativos en PHP 8.4.',
        ],
        [
            'title' => 'Diseño de Entidades Limpias en Symfony 7.2 & Doctrine',
            'icon' => 'layers',
            'content' => 'En entidades Doctrine tradicionales, un modelo con 15 campos requería más de 200 líneas de código solo en getters y setters triviales.

Con PHP 8.4:
• Todas las propiedades de lectura pueden ser `public private(set)`.
• Los hooks `set` garantizan que ni siquiera un desarrollador interno pueda poner un valor inválido.
• Los DTOs de API con validación se reducen a declaraciones concisas que se validan en el momento de la asignación.

El resultado es una reducción de hasta el **60% del código boilerplate** sin ceder ni un milímetro de encapsulación.',
            'takeaways' => 'Combina visibilidad asimétrica con Property Hooks para crear entidades de dominio ultralimpias y auto-validadas.',
        ],
    ],
    'video' => [
        'title' => 'Property Hooks Are Coming To PHP 8.4!',
        'speaker' => 'PHP 8.4 Features Breakdown',
        'youtube_id' => '6_muUspWtw4',
        'duration' => '18 min',
        'description' => 'Una revisión completa de la sintaxis, funcionamiento interno y beneficios arquitectónicos de los Property Hooks en PHP 8.4.',
        'key_takeaways' => [
            'Cómo declarar hooks \'get\' y \'set\' en propiedades respaldadas y virtuales.',
            'La sintaxis de visibilidad asimétrica: public private(set) y protected(set).',
            'Comparativa de rendimiento frente a métodos getters/setters tradicionales.',
            'La eliminación de paréntesis envolventes en instanciaciones de clases con encadenamiento.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Model/BankAccount.php',
        'title' => 'Entidad de Dominio con Property Hooks y Visibilidad Asimétrica',
        'tag' => 'PHP 8.4 Domain Model',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

/**
 * Representa una cuenta bancaria con protección estricta de invariantes de negocio
 * implementada mediante Property Hooks y Visibilidad Asimétrica en PHP 8.4.
 */
class BankAccount
{
    /**
     * El saldo está disponible públicamente para lectura O(1),
     * pero su escritura está estrictamente encapsulada y protegida contra negativos.
     */
    public private(set) int $balance = 0 {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException(\'El saldo de la cuenta no puede ser negativo.\');
            }
            $this->balance = $value;
        }
    }

    public function __construct(int $initialBalance = 0)
    {
        $this->balance = $initialBalance;
    }

    /**
     * Método semántico de negocio para ingresar fondos a la cuenta.
     */
    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException(\'El monto a depositar debe ser estrictamente positivo.\');
        }

        $this->balance += $amount;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un programador junior ve las novedades de PHP 8.4 como \'azúcar sintáctico\' y sigue generando mecánicamente getters y setters con el IDE. Un ingeniero Senior entiende el impacto cognitivo del código limpio: utiliza visibilidad asimétrica para garantizar inmutabilidad externa y property hooks para blindar las invariantes de negocio exactamente donde nacen los datos.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Boilerplate anémico y propiedades públicas sin control',
            'code' => '// Modelo tradicional con 40 líneas de boilerplate o peor: pública sin validación
class BankAccount {
    public int $balance = 0; // Peligro: cualquiera puede mutar balance a -999999!
    
    public function setBalance($val) {
        $this->balance = $val; // Setter anémico que no protege ninguna regla
    }
}',
            'flaws' => [
                'Propiedades públicas mutables que permiten estados corruptos sin validación.',
                'Setters anémicos que exponen la estructura interna sin validar reglas de dominio.',
                'Falta de tipado estricto y ausencia de control de visibilidad en escrituras.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Visibilidad asimétrica y validación declarativa en hooks',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

class BankAccount
{
    public private(set) int $balance = 0 {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException(\'Saldo no puede ser negativo\');
            }
            $this->balance = $value;
        }
    }

    public function __construct(int $initial = 0)
    {
        $this->balance = $initial;
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException(\'Monto debe ser positivo\');
        }
        $this->balance += $amount;
    }
}',
            'rationale' => [
                'Usa visibilidad asimétrica public private(set) para lectura pública sin getters redundantes.',
                'El hook set intercepta cualquier asignación interna validando que balance no sea negativo.',
                'Expone métodos con intención semántica de negocio (deposit) en lugar de mutaciones genéricas.',
            ],
            'trade_offs' => [
                'Property Hooks requieren PHP 8.4+; no son compatibles con versiones anteriores del runtime.',
                'En hooks complejos con dependencias de base de datos o llamadas externas, es preferible utilizar servicios de dominio para no sobrecargar el modelo de datos.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Property Hooks in PHP 8.4',
            'source' => 'PHP RFC: Property Hooks',
            'quote' => 'Property Hooks reducen la verbosidad de getters y setters sin sacrificar la encapsulación, permitiendo lógica de validación directamente ligada a la propiedad.',
            'author' => 'Larry Garfield & Ilija Tovilo',
            'explanation' => 'La propuesta técnica oficial que modernizó el modelado de clases en PHP elevándolo al estándar de lenguajes modernos.',
        ],
        [
            'topic' => 'Asymmetric Visibility',
            'source' => 'PHP RFC: Asymmetric Visibility',
            'quote' => 'Permite especificar diferentes niveles de visibilidad para operaciones de lectura y escritura sobre una misma propiedad.',
            'author' => 'Ilija Tovilo',
            'explanation' => 'Elimina la necesidad de getters de una sola línea cuando la intención es proveer acceso de solo lectura al exterior.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP RFC Oficial: Property Hooks',
            'url' => 'https://wiki.php.net/rfc/property-hooks',
            'description' => 'Documento fundacional con la especificación técnica completa y ejemplos de sintaxis.',
            'type' => 'RFC',
        ],
        [
            'title' => 'PHP Manual Oficial: Novedades de PHP 8.4',
            'url' => 'https://www.php.net/releases/8.4/es.php',
            'description' => 'Resumen oficial del equipo de PHP con todas las características nuevas del release 8.4.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Stitcher.io: A Comprehensive Guide to Property Hooks',
            'url' => 'https://stitcher.io/blog/php-84-property-hooks',
            'description' => 'Guía práctica con casos de uso arquitectónicos y comparativas de diseño.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Modelado de Dominio con PHP 8.4 Moderno',
        'objective' => 'Implementar la clase BankAccount con visibilidad asimétrica y property hooks que impida saldos negativos y permita depósitos controlados.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'BankAccount\' en el namespace \'App\\Domain\\Model\'.
3. Declara la propiedad \'public private(set) int $balance = 0\' con un hook \'set\' que rechace valores negativos lanzando InvalidArgumentException.
4. Implementa el constructor y el método semántico \'deposit(int $amount): void\' validando que el monto sea positivo.',
        'filename' => 'src/Domain/Model/BankAccount.php',
        'guide' => [
            'explanation' => 'Utiliza la sintaxis de PHP 8.4 para Property Hooks: public private(set) int $balance = 0 { set { if ($value < 0) throw new \\InvalidArgumentException(\'...\'); $this->balance = $value; } }. En deposit(), suma al balance tras validar que $amount > 0.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] En PHP 8.4, puedes declarar una propiedad con visibilidad asimétrica public private(set) int $balance y asociarle un hook set para validar que el nuevo valor no sea negativo.',
            ],
            [
                'text' => '[Pista 2: Estructura] La clase BankAccount debe exponer el método semántico public function deposit(int $amount): void. Valida que el monto a depositar sea mayor que cero.',
            ],
            [
                'text' => '[Pista 3: Snippet] public private(set) int $balance = 0 { set { if ($value < 0) throw new \\InvalidArgumentException(\'Saldo negativo\'); $this->balance = $value; } }. En deposit: if ($amount <= 0) throw new \\InvalidArgumentException(\'Monto positivo\'); $this->balance += $amount;.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

class BankAccount
{
    // TODO: Declara $balance con visibilidad asimétrica y property hooks en PHP 8.4
    public int $balance = 0;

    public function __construct(int $initialBalance = 0)
    {
        $this->balance = $initialBalance;
    }

    public function deposit(int $amount): void
    {
        // TODO: Valida que amount sea mayor que cero e incrementa $this->balance
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Model;

use InvalidArgumentException;

class BankAccount
{
    public private(set) int $balance = 0 {
        set {
            if ($value < 0) {
                throw new InvalidArgumentException(\'El saldo de la cuenta no puede ser negativo.\');
            }
            $this->balance = $value;
        }
    }

    public function __construct(int $initialBalance = 0)
    {
        $this->balance = $initialBalance;
    }

    public function deposit(int $amount): void
    {
        if ($amount <= 0) {
            throw new InvalidArgumentException(\'El monto a depositar debe ser estrictamente positivo.\');
        }

        $this->balance += $amount;
    }
}
',
        'explanation' => 'La clase BankAccount utiliza la visibilidad asimétrica public private(set) para permitir lectura abierta con mutación controlada. El property hook set valida cualquier asignación, impidiendo saldos negativos y garantizando la invariante de negocio.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: PHP 8.4: Property Hooks, Visibilidad Asimétrica & Modelado Moderno',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué ventaja aporta la visibilidad asimétrica \'public private(set)\' sobre una propiedad \'public readonly\'?',
                'options' => [
                    'a' => 'Permite que la propiedad cambie de tipo dinámicamente en tiempo de ejecución.',
                    'b' => 'Permite que la clase mute internamente la propiedad múltiples veces, mientras que los consumidores externos solo tienen permiso de lectura.',
                    'c' => 'Inhabilita el recolector de basura para esa propiedad.',
                    'd' => 'Permite modificar la propiedad desde cualquier controlador sin importar el ámbito.',
                ],
                'correct' => 'b',
                'explanation' => '\'public readonly\' solo permite asignar la propiedad una única vez en el constructor. \'public private(set)\' permite lecturas públicas ilimitadas mientras que la clase puede seguir actualizando el valor internamente a lo largo de su ciclo de vida.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la diferencia entre una Backed Property y una Virtual Property al usar Property Hooks en PHP 8.4?',
                'options' => [
                    'a' => 'Las Backed Properties solo pueden ser strings, mientras que las Virtual Properties son enteros.',
                    'b' => 'Una Backed Property almacena físicamente un valor en la zval de la instancia, mientras que una Virtual Property se calcula dinámicamente en su hook get sin reservar almacenamiento.',
                    'c' => 'Las Virtual Properties requieren implementar la interfaz Serializable.',
                    'd' => 'No hay diferencia; son sinónimos en la especificación.',
                ],
                'correct' => 'b',
                'explanation' => 'Las Backed Properties tienen almacenamiento físico en memoria y pueden referenciar $this->prop. Las Virtual Properties no reservan zval propio; su valor se computa en el momento (como un getter sin campo de respaldo).',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué nueva funcionalidad sintáctica introdujo PHP 8.4 para la instanciación de objetos?',
                'options' => [
                    'a' => 'Permite instanciar interfaces abstractas directamente.',
                    'b' => 'Permite encadenar métodos inmediatamente después del operador new sin necesidad de envolver la expresión entre paréntesis (ej. new Request()->handle()).',
                    'c' => 'Elimina la necesidad de utilizar constructores en las clases.',
                    'd' => 'Convierte todas las clases en Singletons automáticamente.',
                ],
                'correct' => 'b',
                'explanation' => 'En versiones previas de PHP se requería escribir \'(new Request())->handle()\'. PHP 8.4 permite la sintaxis natural \'new Request()->handle()\' sin paréntesis redundantes.',
            ],
        ],
    ],
];
