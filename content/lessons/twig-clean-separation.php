<?php

declare(strict_types=1);

return [
    'slug' => 'twig-clean-separation',
    'title' => 'Separación Estricta de Lógica de Presentación',
    'module' => 'Twig Template Engine',
    'minutes' => 35,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'Las plantillas Twig deben contener exclusivamente lógica de presentación visual (renderizado, estructuras condicionales simples y bucles de iteración). La lógica de cálculo de negocio, formateo financiero complejo y transformaciones de datos deben encapsularse en extensiones Twig formales que hereden de \'Twig\\Extension\\AbstractExtension\'.',
        'problem' => 'Escribir cálculos aritméticos complejos dentro de las plantillas Twig (\'{% set total = (item.cents / 100) * 1.16 %}\') viola la separación de responsabilidades, hace imposible testear el formateo con PHPUnit y dispersa la lógica monetaria por decenas de archivos de vista.',
        'solution' => 'Crear una extensión Twig \'FinancialTwigExtension\' extendiendo \'AbstractExtension\', registrar el filtro \'price_cents\' en \'getFilters()\', y proveer el método \'formatCents(int $cents): string\' dividiendo los centavos entre 100 y validando montos negativos.',
        'problem_label' => 'El Problema: Lógica de Negocio Dispersa en Plantillas HTML',
        'solution_label' => 'La Solución Senior: AbstractExtension y Filtros Twig Testeables',
    ],
    'mental_model' => [
        'title' => 'El Escaparate de la Tienda de Moda vs El Taller de Costura',
        'analogy' => 'Imagina una tienda de alta costura. El escaparate (`plantilla.html.twig`) solo debe exhibir el vestido terminado bajo luces elegantes; nunca debe tener máquinas de coser funcionando, tijeras en el suelo ni rollos de tela cortándose en vivo. Cuando escribes lógica compleja de cálculo matemático o conversiones de divisas dentro de una plantilla Twig (`{% set total = (order.cents / 100) * 1.16 %}`), estás metiendo la máquina de coser ruidosa en el escaparate. La **Separación Estricta** exige que todo el formateo y cálculo complejo viva en el taller de costura: una **Twig Extension** (`FinancialTwigExtension extends AbstractExtension`). La extensión recibe los centavos enteros (`12500`), valida que no sean negativos, divide entre 100 y le entrega al escaparate una cadena inmaculada (`$125.00`) mediante un filtro limpio: `{{ order.cents|price_cents }}`.',
        'ascii_diagram' => 'ANTIPATRÓN (Lógica sucia en la plantilla Twig):
{{ (item.cents / 100)|number_format(2, \'.\', \',\') ~ \' USD\' }}  <-- Frágil, no testeable

PATRÓN SENIOR (Extensión Twig Formal):
┌─────────────────────────────────────────────────────────────┐
│  FinancialTwigExtension extends AbstractExtension           │
│  ├─ getFilters(): [ new TwigFilter(\'price_cents\', ...) ]    │
│  └─ formatCents(int $cents): string                         │
└─────────────────────────────────────────────────────────────┘
                              │
                              ▼
PLANTILLA LIMPIA Y EXPRESIVA:
<span>{{ item.cents|price_cents }}</span>',
        'key_concept' => 'Una plantilla Twig solo renderiza datos preparados. Cualquier transformación no trivial debe vivir en una extensión testeable con PHPUnit.',
    ],
    'internals' => [
        'title' => 'Mecánica de Compilación y Registro de Extensiones Twig',
        'steps' => [
            [
                'phase' => '1. Extensión de AbstractExtension',
                'description' => 'La clase hereda de \'Twig\\Extension\\AbstractExtension\', el contrato base que permite a Twig registrar filtros, funciones, tests y operadores.',
            ],
            [
                'phase' => '2. Declaración en getFilters()',
                'description' => 'Se retorna un array de instancias \'TwigFilter\'. El primer argumento es el nombre en la plantilla (\'price_cents\') y el segundo es el callback \'[$this, \'formatCents\']\'.',
            ],
            [
                'phase' => '3. Compilación a Código PHP Nativo',
                'description' => 'Twig no interpreta plantillas en tiempo real: las compila a clases PHP en var/cache. Al encontrar \'|price_cents\', genera una llamada directa a \'$this->extensions[...]->formatCents($cents)\'.',
            ],
            [
                'phase' => '4. Validación de Precondiciones (Fail-Fast)',
                'description' => 'El método formatCents valida que los centavos no sean negativos ($cents < 0), lanzando InvalidArgumentException si se violan las invariantes.',
            ],
            [
                'phase' => '5. División entre 100 y Formateo Moneda',
                'description' => 'Convierte los centavos enteros a dólares ($dollars = $cents / 100.0) y formatea con número decimal exacto \'sprintf(\'$%0.2f\', $dollars)\'.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Filtros vs Funciones en Twig: Cuándo usar cuál',
            'icon' => 'code',
            'content' => 'A menudo surge la duda: ¿debo crear un `TwigFilter` o una `TwigFunction`?

• **TwigFilter (`{{ data|mi_filtro }}`):** Se usa cuando tomas un valor de entrada existente, lo transformas y devuelves el resultado formateado (ej. fechas, divisas, truncate, capitalización). Opera sobre el flujo del dato.
• **TwigFunction (`{{ mi_funcion(arg1, arg2) }}`):** Se usa cuando generas contenido nuevo a partir de parámetros (ej. `asset(\'css/app.css\')`, `path(\'user_show\')`, `csrf_token()`).

Para formatear centavos monetarios, el filtro `{{ product.cents|price_cents }}` es semánticamente perfecto porque transforma la propiedad del producto en su representación visual.',
            'takeaways' => 'Usa TwigFilter para transformar datos existentes; usa TwigFunction para generar contenido nuevo o interactuar con el entorno.',
        ],
        [
            'title' => 'Testabilidad de Extensiones Twig con PHPUnit',
            'icon' => 'check-circle',
            'content' => 'Una de las enormes ventajas de encapsular lógica en `AbstractExtension` es la facilidad para escribir tests unitarios puros:

```php
public function testFormatCentsConvertsCorrectly(): void
{
    $ext = new FinancialTwigExtension();
    $this->assertSame(\'$10.50\', $ext->formatCents(1050));
    $this->assertSame(\'$0.00\', $ext->formatCents(0));
}

public function testFormatCentsRejectsNegative(): void
{
    $this->expectException(\\InvalidArgumentException::class);
    (new FinancialTwigExtension())->formatCents(-50);
}
```

Intentar probar esto si la lógica estuviera dentro de un archivo `.twig` requeriría renderizar el template completo en un test funcional lento.',
            'takeaways' => 'Las extensiones de Twig son clases PHP ordinarias que se prueban en microsegundos con tests unitarios sin levantar el kernel.',
        ],
    ],
    'video' => [
        'title' => 'PHP Template Engines Explained: Twig, Smarty, Plates for Beginners',
        'speaker' => 'CodeLucky',
        'youtube_id' => 'almJ8Gsn6SA',
        'duration' => '18 min',
        'description' => 'Una introducción completa a los motores de plantillas en PHP, la separación de responsabilidades entre vista y lógica, y la prevención de código espagueti.',
        'key_takeaways' => [
            'Por qué mezclar código PHP crudo en el HTML es un antipatrón destructivo.',
            'Cómo Twig compila plantillas a código PHP puro en caché.',
            'La ventaja de usar filtros y funciones en lugar de condicionales densos.',
            'El impacto del tipado y la inmutabilidad en la capa de presentación.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Twig/Extension/FinancialTwigExtension.php',
        'title' => 'Extensión Twig para Formateo Seguro de Centavos a Moneda',
        'tag' => 'Twig 3 AbstractExtension',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Extension;

use InvalidArgumentException;
use Twig\\Extension\\AbstractExtension;
use Twig\\TwigFilter;

/**
 * Extensión de Twig que provee el filtro \'price_cents\' para transformar
 * centavos enteros en su representación formateada en dólares.
 */
class FinancialTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(\'price_cents\', [$this, \'formatCents\']),
        ];
    }

    /**
     * Convierte un monto en centavos a formato monetario (ej. 1050 -> "$10.50").
     */
    public function formatCents(int $cents): string
    {
        if ($cents < 0) {
            throw new InvalidArgumentException(\'El monto en centavos no puede ser negativo.\');
        }

        $dollars = $cents / 100.0;

        return sprintf(\'$%.2f\', $dollars);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior escribe fórmulas matemáticas y formateos con number_format dentro de las plantillas Twig, creando código frágil e intestable. Un ingeniero Senior hereda de AbstractExtension, registra filtros declarativos en getFilters(), protege las invariantes con excepciones, y mantiene las plantillas como lienzos limpios de presentación.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Fórmulas y formateos sucios dentro del archivo Twig',
            'code' => '{# Plantilla con código espagueti y cálculos sucios #}
<div class="price">
    {% set dollars = order.cents / 100 %}
    ${{ dollars|number_format(2, \'.\', \',\') }}
    {# Si la regla de formato cambia, hay que editar 50 archivos Twig! #}
</div>',
            'flaws' => [
                'Mezcla cálculos matemáticos en la vista.',
                'Imposible de testear con pruebas unitarias automatizadas.',
                'Duplicación masiva de código de formateo en múltiples plantillas.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: AbstractExtension con filtro \'price_cents\' reutilizable',
            'code' => 'declare(strict_types=1);

namespace App\\Twig\\Extension;

use InvalidArgumentException;
use Twig\\Extension\\AbstractExtension;
use Twig\\TwigFilter;

class FinancialTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [new TwigFilter(\'price_cents\', [$this, \'formatCents\'])];
    }

    public function formatCents(int $cents): string
    {
        if ($cents < 0) throw new InvalidArgumentException(\'Negative cents not allowed\');
        return sprintf(\'$%.2f\', $cents / 100.0);
    }
}',
            'rationale' => [
                'Encapsula la regla de presentación en una extensión reutilizable y centralizada.',
                'Valida invariantes (monto no negativo) lanzando InvalidArgumentException.',
                'Permite plantillas ultralimpias: {{ order.cents|price_cents }}.',
            ],
            'trade_offs' => [
                'Crear extensiones requiere un archivo PHP adicional, pero se amortiza de inmediato al reutilizar el filtro en todo el proyecto.',
                'Las extensiones no deben inyectar repositorios de Doctrine para evitar problemas de consultas N+1 en vistas.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Separation of Concerns in Templating',
            'source' => 'Twig Official Documentation',
            'quote' => 'Twig fue diseñado para asegurar una estricta separación entre la lógica de la aplicación y la presentación visual.',
            'author' => 'Fabien Potencier',
            'explanation' => 'Mantener las plantillas libres de código de negocio es el principio rector de Twig.',
        ],
        [
            'topic' => 'Single Responsibility Principle in Views',
            'source' => 'Clean Code',
            'quote' => 'Una vista solo debe tener una única razón para cambiar: cómo se ven los datos ante el usuario final, no cómo se calculan.',
            'author' => 'Robert C. Martin',
            'explanation' => 'Los cálculos matemáticos y lógicas de validación pertenecen a clases PHP especializadas.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Docs: How to Write a custom Twig Extension',
            'url' => 'https://symfony.com/doc/current/templates.html#creating-lazy-twig-extensions',
            'description' => 'Guía oficial para crear extensiones personalizadas con AbstractExtension y autowiring.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Twig Docs: Filters and Functions API',
            'url' => 'https://twig.symfony.com/doc/3.x/advanced.html#filters',
            'description' => 'Documentación avanzada de la API de filtros en Twig 3.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Refactoring Guru: Model-View-Controller',
            'url' => 'https://refactoring.guru/es/design-patterns/mvc',
            'description' => 'Conceptos fundamentales de separación de la vista respecto al modelo de datos.',
            'type' => 'REFERENCE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Extensión Twig para Formateo Financiero Limpio',
        'objective' => 'Implementar la clase FinancialTwigExtension heredando de Twig\\Extension\\AbstractExtension, registrando el filtro \'price_cents\' en getFilters() y el método formatCents(int $cents): string dividiendo entre 100.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'FinancialTwigExtension\' en el namespace \'App\\Twig\\Extension\'.
3. Extiende de \'Twig\\Extension\\AbstractExtension\'.
4. En \'getFilters()\', registra el filtro \'price_cents\' apuntando a \'formatCents\'.
5. Implementa \'formatCents(int $cents): string\'. Si \'$cents < 0\', lanza \'InvalidArgumentException\'.
6. Convierte los centavos a dólares dividiendo entre 100 y retorna el string formateado con \'$\' y dos decimales.',
        'filename' => 'src/Twig/Extension/FinancialTwigExtension.php',
        'guide' => [
            'explanation' => 'Usa new TwigFilter(\'price_cents\', [$this, \'formatCents\']) en getFilters(). En formatCents, valida centavos negativos y retorna sprintf(\'$%.2f\', $cents / 100.0).',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] La clase FinancialTwigExtension debe extender Twig\\Extension\\AbstractExtension.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe registrarse el filtro \'price_cents\' en getFilters() e implementarse el método formatCents(int $cents): string.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($cents < 0) throw new \\InvalidArgumentException(\'Centavos no pueden ser negativos\'); return sprintf(\'$%.2f\', $cents / 100.0);.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Extension;

use InvalidArgumentException;
use Twig\\Extension\\AbstractExtension;
use Twig\\TwigFilter;

class FinancialTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        // TODO: Registra el filtro \'price_cents\'
        return [];
    }

    public function formatCents(int $cents): string
    {
        // TODO: Valida que cents >= 0 y divide entre 100
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Extension;

use InvalidArgumentException;
use Twig\\Extension\\AbstractExtension;
use Twig\\TwigFilter;

class FinancialTwigExtension extends AbstractExtension
{
    public function getFilters(): array
    {
        return [
            new TwigFilter(\'price_cents\', [$this, \'formatCents\']),
        ];
    }

    public function formatCents(int $cents): string
    {
        if ($cents < 0) {
            throw new InvalidArgumentException(\'El monto en centavos no puede ser negativo.\');
        }

        $dollars = $cents / 100.0;

        return sprintf(\'$%.2f\', $dollars);
    }
}
',
        'explanation' => 'FinancialTwigExtension extiende AbstractExtension y encapsula el formateo monetario en el filtro \'price_cents\'. Protege la lógica financiera dividiendo entre 100 y validando entradas no negativas mediante InvalidArgumentException.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Separación Estricta de Lógica de Presentación',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un desarrollador Senior evita realizar operaciones matemáticas o consultas SQL dentro de plantillas Twig?',
                'options' => [
                    'a' => 'Porque Twig no soporta números con decimales.',
                    'b' => 'Porque viola la separación de responsabilidades, dispersa la lógica del negocio e imposibilita probar los cálculos con tests unitarios automatizados.',
                    'c' => 'Porque reduce la velocidad de descarga del navegador en un 90%.',
                    'd' => 'Porque Twig borra las variables automáticamente cada 3 segundos.',
                ],
                'correct' => 'b',
                'explanation' => 'Las plantillas deben dedicarse exclusivamente a mostrar datos. La computación y formateo deben vivir en extensiones testeables.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la clase base recomendada en Symfony para crear extensiones de Twig personalizadas?',
                'options' => [
                    'a' => 'Twig\\Extension\\AbstractExtension',
                    'b' => 'Symfony\\Component\\HttpKernel\\Kernel',
                    'c' => 'Doctrine\\ORM\\EntityManager',
                    'd' => 'Twig\\Template',
                ],
                'correct' => 'a',
                'explanation' => 'AbstractExtension provee las implementaciones base de la interfaz ExtensionInterface, permitiendo registrar getFilters() y getFunctions() limpiamente.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es la principal ventaja de encapsular el formateo en un filtro Twig como \'price_cents\'?',
                'options' => [
                    'a' => 'Inhabilita el auto-escaping de seguridad.',
                    'b' => 'Centraliza la regla de presentación en un único punto, facilitando cambios globales y pruebas unitarias aisladas con PHPUnit.',
                    'c' => 'Permite que el navegador traduzca la divisa automáticamente.',
                    'd' => 'Evita que Nginx almacene en caché las páginas.',
                ],
                'correct' => 'b',
                'explanation' => 'Si el formato monetario cambia de \'$10.50\' a \'10,50 USD\', solo modificas la extensión de Twig en un solo lugar en vez de buscar en 50 archivos de plantilla.',
            ],
        ],
    ],
];
