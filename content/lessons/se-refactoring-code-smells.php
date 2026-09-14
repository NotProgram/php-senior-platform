<?php

declare(strict_types=1);

return [
    'slug' => 'se-refactoring-code-smells',
    'title' => 'Detección de Code Smells & Refactorización',
    'module' => 'Ingeniería de Software',
    'minutes' => 55,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'Un Code Smell (mal olor de código) no es un bug que rompe el programa inmediatamente; es un síntoma superficial en el diseño del código que revela problemas arquitectónicos más profundos que ralentizan el desarrollo e introducen fallos a largo plazo.',
        'problem' => 'La \'Obsesión por Primitivos\' (Primitive Obsession) es uno de los code smells más peligrosos en PHP: usar strings, enteros o arrays planos para representar conceptos ricos de negocio (Email, Dinero, UUID, Coordenadas). El código se llena de validaciones duplicadas, regex inconsistentes y fallos de inyección que explotan en la base de datos.',
        'solution' => 'Refactorizar tipos primitivos hacia Value Objects inmutables con semántica Fail-Fast y encapsulación estricta de invariantes de dominio en el constructor.',
        'problem_label' => 'El Problema: Primitive Obsession y Validaciones Dispersas',
        'solution_label' => 'La Solución Senior: Value Objects Inmutables (Fail-Fast)',
    ],
    'mental_model' => [
        'title' => 'El Pasaporte Oficial vs la Servilleta de Papel',
        'analogy' => 'Imagina que llegas al control de aduanas de un aeropuerto internacional. Si le entregas al oficial de inmigración un trozo de servilleta de papel arrugada con tu nombre escrito a lápiz, no te dejará pasar. Exige un Pasaporte Oficial: un documento emitido por una autoridad gubernamental, con chips criptográficos y sellos que garantizan que los datos son 100% auténticos. En software, usar un `string` plano para representar un correo electrónico es como viajar con una servilleta: cualquiera puede escribir `"admin@@invalido"` o código malicioso, y PHP lo pasará de función en función hasta que rompa la base de datos. Un **Value Object** es un pasaporte oficial: valida sus reglas al nacer en el constructor; si los datos son inválidos, explota de inmediato (Fail-Fast). Si el objeto existe en memoria, está matemáticamente garantizado que es válido.',
        'ascii_diagram' => 'PRIMITIVE OBSESSION (Servilleta en blanco):
function sendInvoice(string $email, int $amount) { ... }
sendInvoice("no-es-un-correo", -500); // PHP lo permite! Explota horas después en producción.

VALUE OBJECTS INMUTABLES (Pasaportes blindados):
function sendInvoice(EmailAddress $email, Money $amount) { ... }
new EmailAddress("no-es-un-correo"); // EXPLOTA INMEDIATAMENTE al instanciar (Fail-Fast)
// Si la instancia existe, está 100% GARANTIZADO que es un correo válido en todo el sistema.',
        'key_concept' => 'Haz que los estados inválidos sean irrepresentables en tu código mediante Value Objects inmutables.',
    ],
    'internals' => [
        'title' => 'Catálogo de Code Smells y el Proceso de Refactorización',
        'steps' => [
            [
                'phase' => '1. Primitive Obsession (Obsesión por Primitivos)',
                'description' => 'Uso de tipos primitivos (string, int, float) en lugar de pequeños objetos de dominio para representar conceptos con reglas de validación propias (Email, Moneda, Teléfono, Rango de Fechas).',
            ],
            [
                'phase' => '2. Long Method & Large Class (El Método Dios)',
                'description' => 'Métodos de más de 20 líneas o clases de más de 300 líneas. Revelan acumulación de responsabilidades y falta de abstracción.',
            ],
            [
                'phase' => '3. Feature Envy (Envidia de Funcionalidad)',
                'description' => 'Un método en la Clase A accede constantemente a los getters de la Clase B para calcular algo. El método pertenece a la Clase B; debes moverlo allí.',
            ],
            [
                'phase' => '4. Shotgun Surgery (Cirugía con Escopeta)',
                'description' => 'Cada vez que haces un cambio pequeño en una regla de negocio, tienes que editar 15 archivos diferentes dispersos por todo el repositorio.',
            ],
            [
                'phase' => '5. Refactorización Asistida por Pruebas (Red-Green-Refactor)',
                'description' => 'Nunca refactorices sin una suite de tests automatizados previa que garantice que el comportamiento externo se mantiene 100% inalterado.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Anatomía de un Value Object Inmutable en PHP 8.4',
            'icon' => 'shield',
            'content' => 'Un Value Object auténtico en DDD (Domain-Driven Design) cumple tres propiedades irrompibles:

1. **Igualdad por valor (no por identidad):** Dos objetos `new Money(100, \'USD\')` son idénticos aunque ocupen diferente dirección física de memoria.
2. **Inmutabilidad absoluta:** Una vez creado, sus propiedades no pueden modificarse jamás. En PHP 8.2+ se utiliza `final readonly class`.
3. **Autovalidación Fail-Fast:** El constructor es el guardián del objeto. Si los datos no cumplen las reglas de negocio, lanza una excepción (`\\InvalidArgumentException`) inmediatamente. Es imposible que exista en memoria un Value Object con estado inválido.',
            'code' => 'final readonly class EmailAddress
{
    public string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);
        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new \\InvalidArgumentException("Formato de email inválido: {$value}");
        }
        $this->value = strtolower($trimmed);
    }
}',
            'takeaways' => 'Reemplazar primitivos por Value Objects elimina el 90% de los chequeos condicionales redundantes en controladores y servicios.',
        ],
        [
            'title' => 'Técnicas Canónicas de Refactorización de Martin Fowler',
            'icon' => 'layers',
            'content' => 'Refactorizar es transformar la estructura interna del software sin cambiar su comportamiento observable externo.

Las técnicas fundamentales de Martin Fowler incluyen:
• **Extract Method (Extraer Método):** Toma un bloque de código cohesivo dentro de un método largo y extráelo a un método privado con un nombre intencional.
• **Replace Temp with Query (Reemplazar Temporal por Consulta):** Elimina variables temporales intermedias que ensucian el scope sustituyéndolas por métodos descriptivos.
• **Move Method (Mover Método):** Resuelve Feature Envy llevando el método a la clase que posee los datos.',
            'takeaways' => 'Refactoriza en pasos microscópicos: un cambio a la vez, correr los tests unitarios, confirmar con commit. Si algo falla, git reset te salva la vida.',
        ],
    ],
    'video' => [
        'title' => 'Clean Code - Uncle Bob / Lesson 3: Code Smells & Refactoring',
        'speaker' => 'Robert C. Martin (Uncle Bob)',
        'youtube_id' => 'Qjywrq2gM8o',
        'duration' => '45 min',
        'description' => 'Uncle Bob demuestra cómo identificar malos olores en funciones, métodos extensos y listas de parámetros, aplicando refactorización segura paso a paso.',
        'chapters' => [
            '00:00' => 'Anatomía de una función limpia',
            '14:10' => 'Nombres intencionales y abstracción uniforme',
            '26:30' => 'Eliminación de efectos secundarios y parámetros booleanos',
            '37:50' => 'Transformaciones de refactorización sistemática',
        ],
    ],
    'architecture_code' => [
        'filename' => 'EmailAddress.php',
        'title' => 'Value Object EmailAddress Inmutable en PHP 8.4',
        'tag' => 'DDD Value Object Pattern',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Value Object inmutable que representa una dirección de correo electrónico válida.
 * Resuelve el Code Smell de Primitive Obsession garantizando que ninguna instancia
 * pueda existir con un formato corrupto.
 */
final readonly class EmailAddress implements \\Stringable
{
    public string $value;

    public function __construct(string $value)
    {
        $normalized = trim($value);

        if (!filter_var($normalized, FILTER_VALIDATE_EMAIL)) {
            throw new \\InvalidArgumentException("La dirección de correo \'{$value}\' no tiene un formato RFC válido.");
        }

        $this->value = strtolower($normalized);
    }

    /**
     * Extrae el dominio de la dirección de correo (ej. \'gmail.com\').
     */
    public function getDomain(): string
    {
        $parts = explode(\'@\', $this->value);

        return $parts[1] ?? \'\';
    }

    /**
     * Compara la igualdad por valor contra otro EmailAddress.
     */
    public function equals(self $other): bool
    {
        return $this->value === $other->value;
    }

    public function __toString(): string
    {
        return $this->value;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior detecta un Code Smell mucho antes de que se convierta en un bug. Ve un array asociativo sin tipar y piensa: \'Esto debería ser un DTO tipado\'. Ve un string para un correo o dinero y piensa: \'Esto debe ser un Value Object inmutable\'. Aplica la Regla del Boy Scout en cada Pull Request sin esperar a un sprint dedicado a refactorización.',
        'critical_questions' => [
            '¿Estoy pasando un string o int que tiene reglas de negocio complejas asociadas? ¿Debería ser un Value Object?',
            '¿Este método tiene más de una pantalla de largo? ¿Puedo extraer sub-métodos con nombres que expliquen la intención?',
            '¿Tengo pruebas unitarias verdes antes de empezar a tocar la estructura de este código heredado?',
            '¿El cambio que voy a hacer requiere editar 10 archivos distintos? ¿Cómo puedo reducir ese acoplamiento escopeta?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Manejar correos electrónicos en una plataforma de suscripciones y facturación.',
        'junior' => [
            'approach' => 'Acepta `string $email` en todos los métodos. Valida el formato con una expresión regular copiada de internet en el controlador, pero en el comando de consola o en el worker de colas olvida validarlo.',
            'flaws' => [
                'Entran correos maliciosos o mal formateados a la base de datos. Cuando el despachador de emails intenta enviar la factura, la librería SMTP falla y revienta el worker asíncrono en bucle infinito.',
            ],
        ],
        'senior' => [
            'approach' => 'Crea el Value Object inmutable `EmailAddress`. Firma todos los métodos del dominio con `EmailAddress $email`. Es imposible que el dominio reciba un string sin validar.',
            'rationale' => [
                'Cero validaciones duplicadas en controladores, cero riesgo de inyección SMTP y consistencia total garantizada por el propio sistema de tipos de PHP.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Obsesión por Primitivos',
            'source' => 'Refactoring: Improving the Design of Existing Code',
            'quote' => 'Las personas a menudo dudan en crear pequeñas clases para conceptos simples como dinero, rangos de fechas o teléfonos. Pero los Value Objects limpian el código y eliminan duplicación masiva.',
            'author' => 'Martin Fowler',
            'explanation' => 'El uso indiscriminado de strings y enteros planos dispersa la lógica de validación por toda la base de código en lugar de centralizarla en el objeto.',
        ],
        [
            'topic' => 'Inmutabilidad de Value Objects',
            'source' => 'Domain-Driven Design: Tackling Complexity in the Heart of Software',
            'quote' => 'Cuando solo te importa los atributos de un elemento del modelo y no su identidad continua, clasifícalo como un Value Object y hazlo inmutable.',
            'author' => 'Eric Evans',
            'explanation' => 'La inmutabilidad elimina por completo los efectos secundarios no deseados y las condiciones de carrera en arquitecturas concurrentes.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Refactoring.guru: Primitive Obsession Code Smell',
            'url' => 'https://refactoring.guru/es/refactoring/smells/primitive-obsession',
            'description' => 'Catálogo exhaustivo del olor de código Primitive Obsession y cómo refactorizarlo a objetos.',
            'type' => 'GUIDE',
        ],
        [
            'title' => 'Martin Fowler: Value Object Definition',
            'url' => 'https://martinfowler.com/bliki/ValueObject.html',
            'description' => 'Artículo canónico de Martin Fowler sobre el patrón Value Object y la igualdad por valor.',
            'type' => 'BLOG',
        ],
    ],
    'exercise' => [
        'title' => 'Eliminación de Primitive Obsession: Value Object EmailAddress',
        'objective' => 'Refactorizar el manejo de correos electrónicos implementando el Value Object inmutable EmailAddress con validación Fail-Fast y extracción de dominio.',
        'instructions' => 'Implementa la clase EmailAddress dentro del namespace App\\Engineering. La clase debe: 1) Recibir string $value en su constructor y almacenarlo; 2) Validar el formato con filter_var($value, FILTER_VALIDATE_EMAIL) y lanzar \\InvalidArgumentException si es inválido; 3) Exponer el método público getDomain(): string que retorne la parte posterior al símbolo \'@\' (ej. \'gmail.com\').',
        'filename' => 'EmailAddress.php',
        'guide' => [
            'explanation' => 'En este reto vas a erradicar el Code Smell de Primitive Obsession creando un Value Object inmutable para representar correos electrónicos. Al centralizar la validación en el constructor, garantizas el principio Fail-Fast: ningún correo inválido podrá circular jamás dentro de la aplicación.',
            'steps' => [
                'Paso 1: Declara <code>class EmailAddress</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: Define una propiedad pública o método de acceso para el valor validado.',
                'Paso 3: En el constructor, usa <code>filter_var($value, FILTER_VALIDATE_EMAIL)</code> para validar el formato estándar.',
                'Paso 4: Si la validación falla (retorna false), lanza inmediatamente <code>throw new \\InvalidArgumentException("Email inválido");</code>.',
                'Paso 5: Implementa <code>public function getDomain(): string</code> separando el string por el carácter \'@\' usando <code>explode(\'@\', $this->value)</code> y retornando la segunda parte.',
            ],
            'useful_functions' => [
                [
                    'name' => 'filter_var($email, FILTER_VALIDATE_EMAIL)',
                    'desc' => 'Función nativa de PHP que valida si una cadena cumple con los estándares RFC de formato de correo electrónico.',
                ],
                [
                    'name' => 'explode(\'@\', $string)',
                    'desc' => 'Divide una cadena en un array de subcadenas usando el delimitador especificado.',
                ],
                [
                    'name' => 'throw new \\InvalidArgumentException($msg)',
                    'desc' => 'Lanza la excepción estándar de PHP cuando un argumento pasado a un método no cumple las restricciones requeridas.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Validación con filter_var',
                'text' => 'filter_var retorna el email saneado si es válido, o false si tiene formato incorrecto.',
                'snippet' => 'if (!filter_var($value, FILTER_VALIDATE_EMAIL)) {
    throw new \\InvalidArgumentException(\'Email inválido: \' . $value);
}',
            ],
            [
                'label' => 'Extracción del Dominio',
                'text' => 'Usa explode() dividiendo por \'@\'. El elemento en el índice 1 es el dominio.',
                'snippet' => 'public function getDomain(): string
{
    $parts = explode(\'@\', $this->value);
    return $parts[1] ?? \'\';
}',
            ],
            [
                'label' => 'Estructura Inmutable',
                'text' => 'Guarda el valor en una propiedad inmutable o mediante propiedad pública readonly.',
                'snippet' => 'public readonly string $value;

public function __construct(string $value)
{
    // validación aquí
    $this->value = $value;
}',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Value Object inmutable EmailAddress.
 * Erradica el Code Smell de Primitive Obsession mediante validación Fail-Fast.
 */
class EmailAddress
{
    // PASO 1: Define la propiedad para almacenar el correo
    // PASO 2: En el constructor, valida con filter_var($value, FILTER_VALIDATE_EMAIL)
    // PASO 3: Si es inválido, lanza \\InvalidArgumentException
    // PASO 4: Implementa el método getDomain(): string
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior de EmailAddress como Value Object.
 */
class EmailAddress
{
    public readonly string $value;

    public function __construct(string $value)
    {
        $trimmed = trim($value);

        if (!filter_var($trimmed, FILTER_VALIDATE_EMAIL)) {
            throw new \\InvalidArgumentException("Formato de correo electrónico inválido: {$value}");
        }

        $this->value = $trimmed;
    }

    public function getDomain(): string
    {
        $parts = explode(\'@\', $this->value);

        return $parts[1] ?? \'\';
    }
}
',
        'explanation' => 'La solución implementa el patrón Value Object a la perfección: valida sus invariantes en el constructor con filter_var y lanza \\InvalidArgumentException de inmediato si no cumple las reglas (Fail-Fast). El método getDomain() encapsula la extracción del dominio sin exponer detalles de bajo nivel.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Code Smells y Refactorización',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué problema fundamental introduce el Code Smell \'Primitive Obsession\' en una aplicación empresarial?',
                'options' => [
                    'a' => 'Hace que el recolector de basura de PHP libere memoria demasiado rápido.',
                    'b' => 'Dispersa la lógica de validación por decenas de controladores y servicios, permitiendo que datos corruptos se propaguen y exploten en la base de datos.',
                    'c' => 'Impide que los arrays asociativos puedan serializarse en JSON.',
                    'd' => 'Obliga a usar Docker en entornos de desarrollo.',
                ],
                'correct' => 'b',
                'explanation' => 'Al usar tipos primitivos planos, cada punto de entrada debe recordar validar las reglas a mano, lo que invariablemente lleva a inconsistencias y fallos de seguridad.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la característica definitoria que diferencia a un Value Object de una Entidad en DDD?',
                'options' => [
                    'a' => 'Los Value Objects tienen clave primaria autoincremental en la base de datos.',
                    'b' => 'Los Value Objects son inmutables y se definen por sus atributos (dos objetos con los mismos valores son idénticos), mientras que una Entidad tiene una identidad única continua a lo largo del tiempo.',
                    'c' => 'Los Value Objects solo pueden escribirse en JavaScript.',
                    'd' => 'Las Entidades no pueden contener métodos de negocio.',
                ],
                'correct' => 'b',
                'explanation' => 'Un Value Object no tiene ID; dos billetes de $20 dólares son intercambiables porque su valor es el mismo. Un usuario es una Entidad porque conserva su identidad aunque cambie de nombre.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el primer paso obligatorio antes de iniciar una refactorización de código legado riesgosa?',
                'options' => [
                    'a' => 'Borrar todas las clases viejas y reescribirlas desde cero en una sola noche.',
                    'b' => 'Contar con una suite de pruebas automatizadas verdes que verifiquen y congelen el comportamiento observable actual del sistema.',
                    'c' => 'Actualizar inmediatamente la versión de PHP a la más reciente sin probar dependencias.',
                    'd' => 'Desactivar temporalmente los logs de errores en producción.',
                ],
                'correct' => 'b',
                'explanation' => 'Refactorizar sin pruebas automatizadas es como caminar en la cuerda floja sin red de seguridad; no puedes saber si rompiste un caso borde crítico del negocio.',
            ],
        ],
    ],
];
