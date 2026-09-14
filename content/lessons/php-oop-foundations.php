<?php

declare(strict_types=1);

return [
    'slug' => 'php-oop-foundations',
    'title' => 'Fundamentos de POO: Clases, Instancias & Encapsulación Básica',
    'module' => 'PHP Moderno',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'La Programación Orientada a Objetos (POO) organiza el software modelando entidades del mundo real que combinan estado (propiedades) y comportamiento (métodos). Una clase es el plano arquitectónico y un objeto es la instancia viva en memoria. PHP 8.4 cuenta con soporte de primera clase para POO estricta, constructores con propiedades promocionadas y encapsulación robusta.',
        'problem' => 'En el código procedural o con clases que exponen todas sus propiedades como public, cualquier parte de la aplicación puede mutar los datos arbitrariamente ($estudiante->promedio = -99;). Esto genera inconsistencias invisibles, duplicación de lógica de validación y fallas impredecibles en producción.',
        'solution' => 'Encapsular el estado interno marcando las propiedades como private o readonly, validar invariantes en el constructor y exponer métodos de negocio con intención semántica ($estudiante->addGrade(95.0);) que aseguren la validez del objeto en todo momento.',
        'problem_label' => 'El Problema: Propiedades Públicas Desprotegidas y Estado Inconsistente',
        'solution_label' => 'La Solución Senior: Encapsulación Estricta y Constructores Promocionados',
    ],
    'mental_model' => [
        'title' => 'El Cajero Automático vs la Caja de Zapatos con Dinero',
        'analogy' => 'Imagina que dejas el dinero de tu negocio en una caja de zapatos abierta en el mostrador (propiedades públicas). Cualquiera puede meter la mano, sacar billetes, dejar pagarés falsos o cambiar el saldo sin control. Eso es un objeto sin encapsulación. Un objeto bien diseñado en POO es como un Cajero Automático (ATM): el dinero está en una caja fuerte blindada (private). Para interactuar, debes usar la ranura autorizada (métodos públicos como depositar() o retirar()), y el cajero valida tu PIN y tu saldo disponible antes de realizar cualquier cambio.',
        'ascii_diagram' => 'CÓDIGO LLAMADOR: $estudiante->addGrade(85.0);
                           │
                           ▼
            ┌─────────────────────────────┐
            │  StudentProfile (Objeto)    │
            │                             │
            │  [MÉTODO PÚBLICO]           │
            │  addGrade(float $val)       │
            │  1. ¿$val entre 0 y 100?    │
            │     ├── NO ──> Excepción!   │
            │     └── SI ──> Registra     │
            │                             │
            │  [ESTADO PRIVADO OCULTO]    │
            │  private array $grades;     │
            └─────────────────────────────┘',
        'key_concept' => 'Un objeto debe nacer válido y mantenerse válido a lo largo de todo su ciclo de vida. Nunca expongas propiedades internas que permitan corromper sus reglas de negocio.',
    ],
    'internals' => [
        'title' => 'Los Pilares Básicos de una Clase en PHP 8.4',
        'steps' => [
            [
                'phase' => '1. Constructor Property Promotion (Promoción en Constructor)',
                'description' => 'En lugar de declarar propiedades arriba y asignarlas manualmente en el constructor ($this->x = $x;), PHP 8 permite declararlas directamente en la firma: public function __construct(private string $name) {}.',
            ],
            [
                'phase' => '2. Modificadores de Visibilidad',
                'description' => 'public: accesible desde cualquier lugar. private: accesible únicamente dentro de la misma clase. protected: accesible dentro de la clase y sus subclases.',
            ],
            [
                'phase' => '3. La Pseudo-variable $this',
                'description' => 'Dentro de un método de instancia, $this hace referencia al objeto concreto que está ejecutando la acción en ese momento.',
            ],
            [
                'phase' => '4. Tipado Estricto de Propiedades',
                'description' => 'Desde PHP 7.4+, las propiedades de clase tienen tipado estricto (private float $balance). PHP impide que se asigne un tipo incompatible, garantizando integridad en memoria.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Promoción de Propiedades en PHP 8.4',
            'icon' => 'code',
            'content' => 'Observa cómo la promoción de propiedades reduce el boilerplate sin perder seguridad:

// Antes de PHP 8 (Verbosidad innecesaria):
class User {
    private string $name;
    public function __construct(string $name) {
        $this->name = $name;
    }
}

// PHP 8+ Moderno (Limpio y directo):
class User {
    public function __construct(
        private string $name
    ) {}
}',
            'takeaways' => 'Aprovecha la promoción en constructor para escribir clases concisas y legibles.',
        ],
    ],
    'video' => [
        'title' => 'Object-Oriented PHP 8 for Beginners',
        'speaker' => 'Kevin Powell',
        'youtube_id' => 'k3y6pA9gXzE',
        'duration' => '35 min',
        'description' => 'Fundamentos de orientación a objetos en PHP: clases, instancias, constructor promotion y encapsulación.',
        'chapters' => [
            '00:00' => 'Qué es una clase y qué es una instancia',
            '10:00' => 'Visibilidad: public vs private',
            '18:30' => 'Constructor Property Promotion',
            '28:00' => 'Protección de invariantes',
        ],
    ],
    'architecture_code' => [
        'filename' => 'StudentProfile.php',
        'title' => 'Modelado de Perfil de Estudiante con Encapsulación',
        'tag' => 'PHP 8.4 OOP Fundamentals',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Entidad de dominio que representa a un estudiante y sus calificaciones.
 * Protege sus invariantes internas de negocio.
 */
class StudentProfile
{
    /**
     * @param list<float> $grades Lista de calificaciones (0.0 a 100.0).
     */
    public function __construct(
        private string $name,
        private string $email,
        private array $grades = []
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Registra una nueva calificación validando que esté en el rango permitido (0 - 100).
     */
    public function addGrade(float $grade): self
    {
        if ($grade < 0.0 || $grade > 100.0) {
            throw new InvalidArgumentException(\'La calificación debe estar entre 0.0 y 100.0.\');
        }

        $this->grades[] = $grade;

        return $this;
    }

    /**
     * Calcula el promedio aritmético de las calificaciones registradas.
     */
    public function getAverage(): float
    {
        if (empty($this->grades)) {
            return 0.0;
        }

        $sum = array_sum($this->grades);

        return round($sum / count($this->grades), 2);
    }

    /**
     * Determina si el estudiante está aprobado (promedio >= 60.0).
     */
    public function isApproved(): bool
    {
        return $this->getAverage() >= 60.0;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Nunca diseñes clases que sean simples bolsas de datos pasivas (Anemic Domain Model) con getters y setters para todo. Agrega métodos que representen acciones del negocio (addGrade, isApproved) para que las reglas vivan dentro del objeto y no dispersas por controladores.',
        'critical_questions' => [
            '¿Puede esta clase terminar en un estado inválido si alguien llama a sus métodos en desorden?',
            '¿Las propiedades son privadas por defecto para evitar mutaciones externas no autorizadas?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Calcular si un alumno aprobó un curso según sus notas.',
        'junior' => [
            'approach' => 'Guarda las notas en un array asociativo público y calcula el promedio con un bucle dentro de un controlador.',
            'flaws' => [
                'Si otro controlador necesita saber si aprobó, duplica la fórmula. Si la nota mínima cambia a 70, hay que buscar y reemplazar en 10 archivos.',
            ],
        ],
        'senior' => [
            'approach' => 'Encapsula la lista de notas y el método isApproved() dentro de la clase StudentProfile.',
            'rationale' => [
                'Una única fuente de la verdad: si la regla de aprobación cambia, se modifica en un solo lugar y todos los consumidores quedan actualizados.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Encapsulación de Dominio',
            'source' => 'Object-Oriented Analysis and Design with Applications',
            'quote' => 'La encapsulación oculta los detalles de implementación de un objeto, exponiendo solo una interfaz de comportamiento seguro hacia el exterior.',
            'author' => 'Grady Booch',
            'explanation' => 'Un objeto bien encapsulado actúa como una caja negra confiable que garantiza sus propias reglas.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Manual Oficial de PHP: Clases y Objetos',
            'url' => 'https://www.php.net/manual/es/language.oop5.php',
            'description' => 'Guía completa sobre el modelo de objetos en PHP.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Modelado de Estudiante y Calificaciones',
        'objective' => 'Implementar la clase StudentProfile en App\\Fundamentals con constructor promocionado, registro de calificaciones con validación y cálculo de promedio.',
        'instructions' => 'Crea la clase StudentProfile en el namespace App\\Fundamentals con declare(strict_types=1);. Su constructor debe recibir private string $name, private string $email y private array $grades = []. Implementa los métodos getName(): string, getEmail(): string, addGrade(float $grade): self (valida que $grade esté entre 0.0 y 100.0, lanzando InvalidArgumentException si no lo está, la agrega al array y retorna $this), getAverage(): float (retorna 0.0 si no hay notas, o el promedio redondeado a 2 decimales con round()) e isApproved(): bool (retorna true si el promedio es mayor o igual a 60.0).',
        'filename' => 'StudentProfile.php',
        'guide' => [
            'explanation' => 'Aprenderás a construir entidades de negocio con estado protegido y métodos semánticos.',
            'steps' => [
                'Paso 1: Declara namespace App\\Fundamentals; y usa declare(strict_types=1);.',
                'Paso 2: Constructor con private string $name, private string $email, private array $grades = [].',
                'Paso 3: Métodos de lectura getName(): string y getEmail(): string.',
                'Paso 4: En addGrade(float $grade): self, si $grade < 0.0 o $grade > 100.0, lanza InvalidArgumentException. Añade al array $this->grades[] = $grade; y retorna $this.',
                'Paso 5: En getAverage(): float, si $grades está vacío retorna 0.0. Si no, retorna round(array_sum($this->grades) / count($this->grades), 2).',
                'Paso 6: En isApproved(): bool, retorna $this->getAverage() >= 60.0.',
            ],
            'useful_functions' => [
                [
                    'name' => 'array_sum(array $arr)',
                    'desc' => 'Suma los valores numéricos del array.',
                ],
                [
                    'name' => 'count(array $arr)',
                    'desc' => 'Retorna la cantidad de elementos en el array.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Validación en addGrade',
                'text' => 'Protege el rango de calificaciones de 0 a 100.',
                'snippet' => 'if ($grade < 0.0 || $grade > 100.0) { throw new \InvalidArgumentException(\'Nota inválida\'); }',
            ],
            [
                'label' => 'Cálculo de Promedio',
                'text' => 'Recuerda prevenir la división por cero si el array está vacío.',
                'snippet' => 'if (empty($this->grades)) return 0.0; return round(array_sum($this->grades) / count($this->grades), 2);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Perfil de Estudiante con Calificaciones Encapsuladas.
 */
class StudentProfile
{
    public function __construct(
        private string $name,
        private string $email,
        private array $grades = []
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    /**
     * Agrega una calificación validando el rango 0.0 a 100.0.
     */
    public function addGrade(float $grade): self
    {
        // PASO 1: Valida que $grade esté entre 0.0 y 100.0 (lanza InvalidArgumentException si no)
        // PASO 2: Añade la nota a $this->grades y retorna $this
        return $this;
    }

    /**
     * Retorna el promedio de calificaciones (0.0 si no hay calificaciones).
     */
    public function getAverage(): float
    {
        // PASO 3: Calcula el promedio redondeado a 2 decimales
        return 0.0;
    }

    /**
     * Retorna true si el promedio es >= 60.0.
     */
    public function isApproved(): bool
    {
        // PASO 4: Verifica si el promedio alcanza 60.0
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Fundamentals;

use InvalidArgumentException;

/**
 * Perfil de Estudiante con Calificaciones Encapsuladas.
 */
class StudentProfile
{
    public function __construct(
        private string $name,
        private string $email,
        private array $grades = []
    ) {}

    public function getName(): string
    {
        return $this->name;
    }

    public function getEmail(): string
    {
        return $this->email;
    }

    public function addGrade(float $grade): self
    {
        if ($grade < 0.0 || $grade > 100.0) {
            throw new InvalidArgumentException(\'La calificación debe estar entre 0.0 y 100.0.\');
        }

        $this->grades[] = $grade;

        return $this;
    }

    public function getAverage(): float
    {
        if (empty($this->grades)) {
            return 0.0;
        }

        return round(array_sum($this->grades) / count($this->grades), 2);
    }

    public function isApproved(): bool
    {
        return $this->getAverage() >= 60.0;
    }
}
',
        'explanation' => 'StudentProfile ilustra cómo modelar una entidad en POO con propiedades privadas, protección de invariantes en métodos mutadores y cálculo de métricas de negocio encapsuladas.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Fundamentos de POO en PHP',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es la diferencia conceptual entre una "clase" y un "objeto"?',
                'options' => [
                    'a' => 'Una clase es la definición o plano arquitectónico; un objeto es la instancia concreta de esa clase en la memoria.',
                    'b' => 'Una clase es para bases de datos y un objeto es para la interfaz de usuario.',
                    'c' => 'No hay diferencia; son términos intercambiables en PHP.',
                    'd' => 'Un objeto solo puede tener constantes y una clase solo funciones.',
                ],
                'correct' => 'a',
                'explanation' => 'La clase define la estructura y el comportamiento; el objeto es la entidad viva creada en memoria con sus propios datos específicos.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué beneficio ofrece declarar propiedades como private en lugar de public?',
                'options' => [
                    'a' => 'Hace que el código se ejecute 10 veces más rápido.',
                    'b' => 'Impide que el estado interno sea manipulado arbitrariamente desde el exterior, forzando a que las mutaciones pasen por métodos que validan las reglas de negocio.',
                    'c' => 'Permite que la clase se convierta en una función global.',
                    'd' => 'Oculta el código fuente de los usuarios en el navegador.',
                ],
                'correct' => 'b',
                'explanation' => 'La visibilidad privada protege la encapsulación y asegura que las invariantes de negocio se cumplan en todo momento.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué significa la palabra clave $this dentro de un método de clase en PHP?',
                'options' => [
                    'a' => 'Hace referencia a la versión actual de PHP instalada.',
                    'b' => 'Es una referencia a la instancia específica del objeto sobre la cual se está invocando el método.',
                    'c' => 'Es una variable global compartida por todas las clases del proyecto.',
                    'd' => 'Representa el archivo en el disco donde está escrita la clase.',
                ],
                'correct' => 'b',
                'explanation' => '$this apunta a la instancia actual del objeto en memoria, permitiendo acceder a sus propiedades y métodos de instancia.',
            ],
        ],
    ],
];
