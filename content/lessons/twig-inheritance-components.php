<?php

declare(strict_types=1);

return [
    'slug' => 'twig-inheritance-components',
    'title' => 'Herencia Jerárquica & Componentes Reutilizables',
    'module' => 'Twig Template Engine',
    'minutes' => 40,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'La herencia jerárquica en Twig ({% extends %} y {% block %}) estructura el esqueleto general de la aplicación, mientras que los componentes de interfaz reutilizables encapsulan fragmentos de UI autocontenidos con validación estricta de variantes visuales y protección contra ataques XSS.',
        'problem' => 'Copiar y pegar fragmentos de marcado HTML para componentes UI comunes (como insignias de estado o badges) provoca inconsistencia visual, marcado desincronizado y vulnerabilidades graves si el texto de la etiqueta no se escapa correctamente antes de inyectarse en el HTML.',
        'solution' => 'Implementar un helper de componentes \'BadgeComponentHelper\' que valide variantes permitidas (ALLOWED_VARIANTS: primary, success, warning, danger), rechace estilos inválidos con InvalidArgumentException, y neutralice caracteres peligrosos con htmlspecialchars() antes de emitir el marcado HTML del badge.',
        'problem_label' => 'El Problema: Duplicación de Marcado UI y Vulnerabilidad XSS',
        'solution_label' => 'La Solución Senior: Componentes Reutilizables, Variantes Cerradas y Escapado Seguro',
    ],
    'mental_model' => [
        'title' => 'El Chasis Estructural del Edificio vs Los Módulos Prefabricados',
        'analogy' => 'Imagina la construcción de un rascacielos. La **Herencia de Plantillas** (`{% extends \'base.html.twig\' %}`) es el chasis de vigas de acero: define dónde van las columnas principales, las salidas de emergencia y los huecos de ascensor (`{% block stylesheets %}`, `{% block body %}`). Cada piso (página) hereda el chasis y solo llena sus propios departamentos. Por otro lado, los **Componentes UI Reutilizables** (como badges de estado, alertas o modales) son módulos prefabricados estandarizados de alta tecnología. Un `BadgeComponentHelper` es como una fábrica de cerraduras automáticas: valida que la variante de color sea oficial (`ALLOWED_VARIANTS: primary, success, warning, danger`), pasa el texto del cliente por un proceso de sanitización química (`htmlspecialchars()`) para neutralizar cualquier script malicioso inyectado, y ensambla un componente impecable sin duplicar HTML por toda la obra.',
        'ascii_diagram' => 'BASE TEMPLATE (base.html.twig):
┌──────────────────────────────────────────────────────────┐
│  <head>{% block stylesheets %}{% endblock %}</head>      │
│  <body>                                                  │
│      <nav>Sidebar Navigation</nav>                       │
│      <main>{% block body %}{% endblock %}</main>         │
└──────────────────────────────────────────────────────────┘
                            ▲
                            │ {% extends \'base.html.twig\' %}
CHILD TEMPLATE (order/show.html.twig):
{% block body %}
    <h1>Pedido #{{ order.id }}</h1>
    {{ badgeHelper.renderBadge(order.statusText, \'success\')|raw }}
{% endblock %}

COMPONENTE SEGURO (BadgeComponentHelper):
Valida variante (\'success\' in ALLOWED_VARIANTS)
+ Escapa texto (htmlspecialchars($label, ENT_QUOTES, \'UTF-8\'))
= <span class="badge badge-success">Pagado</span>',
        'key_concept' => 'La herencia organiza la estructura global del layout; los componentes y helpers encapsulan elementos visuales reutilizables con variantes tipadas y auto-escapado estricto.',
    ],
    'internals' => [
        'title' => 'Mecánica de Bloques y Helpers de Componentes en Twig',
        'steps' => [
            [
                'phase' => '1. Resolución del Árbol de Herencia ({% extends %})',
                'description' => 'Twig compila la plantilla base en una clase padre y la plantilla hija en una subclase. Los bloques {% block %} se convierten en métodos de la clase que sobreescriben la implementación padre.',
            ],
            [
                'phase' => '2. Función parent()',
                'description' => 'Invocar {{ parent() }} dentro de un bloque ejecuta el método equivalente de la clase base, permitiendo extender estilos o scripts sin reemplazarlos.',
            ],
            [
                'phase' => '3. Validación de Variantes Permitidas',
                'description' => 'El helper define una constante ALLOWED_VARIANTS = [\'primary\', \'success\', \'warning\', \'danger\']. Si se pasa una variante no reconocida, lanza InvalidArgumentException (Fail-Fast).',
            ],
            [
                'phase' => '4. Sanitización XSS Obligatoria',
                'description' => 'Antes de concatenar el label en el string HTML, se ejecuta htmlspecialchars($label, ENT_QUOTES, \'UTF-8\') para neutralizar caracteres como <, >, &, y comillas.',
            ],
            [
                'phase' => '5. Generación de Marcado Atómico',
                'description' => 'Retorna el elemento HTML <span class=\'badge badge-{$variant}\'>{$escapedLabel}</span> listo para renderizarse con seguridad.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Symfony UX: Twig Components y Live Components',
            'icon' => 'layers',
            'content' => 'En el ecosistema moderno de Symfony 7, el paquete `symfony/ux-twig-component` lleva este concepto al siguiente nivel:

```php
#[AsTwigComponent(\'badge\')]
class BadgeComponent {
    public string $label;
    public string $variant = \'primary\';
}
```

En la plantilla Twig se invoca con sintaxis de etiqueta HTML declarativa:
```html
&lt;twig:badge label="Completado" variant="success" /&gt;
```

Esto une la potencia de componentes modernos estilo React o Vue con la velocidad de renderizado en el servidor en PHP.',
            'takeaways' => 'Usa componentes autocontenidos para crear sistemas de diseño (Design Systems) coherentes y seguros en Symfony.',
        ],
        [
            'title' => 'El Peligro del Filtro |raw y por qué htmlspecialchars es Obligatorio',
            'icon' => 'alert-triangle',
            'content' => 'Cuando un helper devuelve una cadena HTML completa para renderizarse en Twig, suele usarse `|raw` en la vista para que Twig no escape las etiquetas `<span>`.

**El Riesgo Mortal:**
Si el helper concatena `$label` directamente sin escapar:
```php
// VULNERABLE: Si $label es &lt;script&gt;alert(1)&lt;/script&gt;, se ejecuta!
return "<span class=\'badge\'>{$label}</span>";
```

Por eso, cualquier helper o componente que emita HTML crudo tiene la **responsabilidad sagrada** de aplicar `htmlspecialchars($label, ENT_QUOTES, \'UTF-8\')` sobre cualquier contenido dinámico.',
            'takeaways' => 'Si tu helper genera HTML que será renderizado con |raw, debes escapar obligatoriamente todo dato dinámico con htmlspecialchars.',
        ],
    ],
    'video' => [
        'title' => 'Let\'s build some Twig UI Components',
        'speaker' => 'Ryan Weaver (Creador de Symfony UX)',
        'youtube_id' => 'anXeWMLHNWE',
        'duration' => '35 min',
        'description' => 'Una clase magistral de Ryan Weaver sobre cómo construir componentes de interfaz reutilizables, limpios y testeables en Twig.',
        'key_takeaways' => [
            'Estructuración de componentes de interfaz reutilizables en Twig.',
            'Cómo validar propiedades de entrada para prevenir estados visuales inválidos.',
            'Técnicas de composición frente a la duplicación de plantillas parciales.',
            'Integración de componentes con sistemas de diseño accesibles.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Twig/Component/BadgeComponentHelper.php',
        'title' => 'Helper Seguro para Renderizado de Badges de Estado',
        'tag' => 'Twig Component Helper',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Component;

use InvalidArgumentException;

/**
 * Genera el marcado HTML para insignias (badges) de estado validando
 * variantes permitidas y aplicando sanitización XSS sobre el texto.
 */
class BadgeComponentHelper
{
    private const array ALLOWED_VARIANTS = [
        \'primary\',
        \'success\',
        \'warning\',
        \'danger\',
    ];

    /**
     * Renderiza el badge como HTML seguro.
     */
    public function renderBadge(string $label, string $variant = \'primary\'): string
    {
        if (!in_array($variant, self::ALLOWED_VARIANTS, true)) {
            throw new InvalidArgumentException(sprintf(
                \'Variante no permitida: "%s". Variantes válidas: %s.\',
                $variant,
                implode(\', \', self::ALLOWED_VARIANTS)
            ));
        }

        // Sanitización obligatoria contra Cross-Site Scripting (XSS)
        $escapedLabel = htmlspecialchars($label, ENT_QUOTES, \'UTF-8\');

        return sprintf(
            \'<span class="badge badge-%s">%s</span>\',
            $variant,
            $escapedLabel
        );
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior copia trozos de HTML de Bootstrap en 40 plantillas y usa \'|raw\' sin pensar, permitiendo inyecciones XSS catastróficas. Un ingeniero Senior modela componentes reutilizables con variantes cerradas, valida los argumentos con Fail-Fast y neutraliza cualquier dato dinámico con htmlspecialchars antes de emitir HTML.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Marcado HTML duplicado y vulnerabilidad XSS con |raw',
            'code' => '{# Código junior vulnerable #}
<span class="badge {{ variant }}">
    {{ userProvidedStatus|raw }} {# Peligro extremo: XSS directo al usar |raw sin sanitizar! #}
</span>',
            'flaws' => [
                'Usa |raw sobre datos provistos por el usuario, abriendo una brecha XSS crítica.',
                'No valida si la variante CSS existe o es permitida.',
                'Duplicación de clases y estilos en decenas de archivos Twig.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: BadgeComponentHelper con variantes estrictas y sanitización',
            'code' => 'declare(strict_types=1);

namespace App\\Twig\\Component;

use InvalidArgumentException;

class BadgeComponentHelper
{
    private const array ALLOWED_VARIANTS = [\'primary\', \'success\', \'warning\', \'danger\'];

    public function renderBadge(string $label, string $variant = \'primary\'): string
    {
        if (!in_array($variant, self::ALLOWED_VARIANTS, true)) {
            throw new InvalidArgumentException("Invalid variant: {$variant}");
        }
        $safeLabel = htmlspecialchars($label, ENT_QUOTES, \'UTF-8\');
        return sprintf(\'<span class="badge badge-%s">%s</span>\', $variant, $safeLabel);
    }
}',
            'rationale' => [
                'Valida variantes contra un array cerrado (primary, success, warning, danger).',
                'Aplica htmlspecialchars obligatoriamente para garantizar seguridad absoluta contra XSS.',
                'Genera marcado estandarizado y testeable con pruebas unitarias.',
            ],
            'trade_offs' => [
                'Para componentes complejos con slots dinámicos, es preferible utilizar componentes de Symfony UX Twig Component en vez de helpers en PHP puro.',
                'Emitir HTML desde clases PHP debe limitarse a componentes UI pequeños y atómicos.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Template Inheritance Architecture',
            'source' => 'Twig Architecture Guide',
            'quote' => 'La herencia de plantillas es la característica más poderosa de Twig. Te permite construir una plantilla esqueleto base que contiene todos los elementos comunes de tu sitio y define bloques que las plantillas secundarias pueden anular.',
            'author' => 'Twig Team',
            'explanation' => 'Elimina la necesidad de includes desordenados y unifica el layout maestro.',
        ],
        [
            'topic' => 'Defense Against XSS in Components',
            'source' => 'OWASP Top 10: Injection Prevention',
            'quote' => 'Cualquier salida generada dinámicamente que se inyecte en el DOM debe someterse a codificación contextual según el estándar de seguridad correspondiente.',
            'author' => 'OWASP Foundation',
            'explanation' => 'El escapado contextual es la barrera no negociable contra ataques Cross-Site Scripting.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Twig Docs: Template Inheritance ({% extends %})',
            'url' => 'https://twig.symfony.com/doc/3.x/tags/extends.html',
            'description' => 'Documentación oficial sobre herencia de plantillas y bloques.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony UX: Twig Components Documentation',
            'url' => 'https://symfony.com/bundles/ux-twig-component/current/index.html',
            'description' => 'Guía oficial del componente Symfony UX para construir componentes de UI modernos.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PHP Manual Oficial: htmlspecialchars',
            'url' => 'https://www.php.net/manual/es/function.htmlspecialchars.php',
            'description' => 'Función del motor nativo para convertir caracteres especiales en entidades HTML.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Componente Seguro de Insignias de Estado (Badges)',
        'objective' => 'Implementar la clase BadgeComponentHelper con validación estricta de variantes permitidas (primary, success, warning, danger) y sanitización XSS con htmlspecialchars().',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'BadgeComponentHelper\' en el namespace \'App\\Twig\\Component\'.
3. Define las variantes permitidas: \'ALLOWED_VARIANTS\' incluyendo \'primary\', \'success\', \'warning\', \'danger\'.
4. Implementa el método \'renderBadge(string $label, string $variant = "primary"): string\'.
5. Si \'$variant\' no es válida, lanza una \'InvalidArgumentException\'.
6. Escapa el texto del \'$label\' usando \'htmlspecialchars($label, ENT_QUOTES, \'UTF-8\')\' y retorna el span formateado.',
        'filename' => 'src/Twig/Component/BadgeComponentHelper.php',
        'guide' => [
            'explanation' => 'En renderBadge, valida if (!in_array($variant, self::ALLOWED_VARIANTS, true)) throw new \\InvalidArgumentException(...);. Escapa el label y retorna sprintf(\'<span class="badge badge-%s">%s</span>\', $variant, $escapedLabel);.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase BadgeComponentHelper validando las variantes permitidas (primary, success, warning, danger).',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe implementarse el método renderBadge(string $label, string $variant = \'primary\'): string lanzando InvalidArgumentException si la variante no es válida.',
            ],
            [
                'text' => '[Pista 3: Snippet] $escaped = htmlspecialchars($label, ENT_QUOTES, \'UTF-8\'); return sprintf(\'<span class="badge badge-%s">%s</span>\', $variant, $escaped);.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Component;

use InvalidArgumentException;

class BadgeComponentHelper
{
    // TODO: Define variantes permitidas (primary, success, warning, danger)

    public function renderBadge(string $label, string $variant = \'primary\'): string
    {
        // TODO: Valida variante, escapa label con htmlspecialchars y retorna span
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Twig\\Component;

use InvalidArgumentException;

class BadgeComponentHelper
{
    private const array ALLOWED_VARIANTS = [
        \'primary\',
        \'success\',
        \'warning\',
        \'danger\',
    ];

    public function renderBadge(string $label, string $variant = \'primary\'): string
    {
        if (!in_array($variant, self::ALLOWED_VARIANTS, true)) {
            throw new InvalidArgumentException(sprintf(\'Variante "%s" no permitida.\', $variant));
        }

        $escapedLabel = htmlspecialchars($label, ENT_QUOTES, \'UTF-8\');

        return sprintf(\'<span class="badge badge-%s">%s</span>\', $variant, $escapedLabel);
    }
}
',
        'explanation' => 'BadgeComponentHelper garantiza consistencia visual y seguridad: rechaza variantes inválidas mediante excepciones tempranas y protege al navegador contra inyecciones XSS aplicando htmlspecialchars sobre el texto antes de generar el HTML.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Herencia Jerárquica & Componentes Reutilizables',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué es peligroso usar el filtro \'|raw\' de Twig sin sanitización previa?',
                'options' => [
                    'a' => 'Porque desactiva el compilador JIT en PHP 8.',
                    'b' => 'Porque desactiva el auto-escaping de Twig, permitiendo que scripts maliciosos (<script>) inyectados por usuarios se ejecuten directamente en el navegador (XSS).',
                    'c' => 'Porque borra automáticamente los archivos de la caché.',
                    'd' => 'Porque solo funciona en archivos que terminen en .xml.',
                ],
                'correct' => 'b',
                'explanation' => 'El filtro |raw le indica a Twig que confíe ciegamente en la cadena. Si contiene texto controlado por un atacante, se producirá una ejecución de código del lado del cliente (XSS).',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué función cumple el segundo parámetro \'ENT_QUOTES\' al invocar \'htmlspecialchars()\'?',
                'options' => [
                    'a' => 'Convierte tanto comillas dobles (") como comillas simples (\') en entidades HTML seguras.',
                    'b' => 'Inhabilita el soporte para caracteres UTF-8.',
                    'c' => 'Traduce el texto al idioma inglés.',
                    'd' => 'Convierte todos los números a enteros.',
                ],
                'correct' => 'a',
                'explanation' => 'Por defecto, htmlspecialchars histórico no escapaba las comillas simples. \'ENT_QUOTES\' asegura que tanto comillas simples como dobles se codifiquen, impidiendo escapar de atributos HTML.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cómo se invoca el contenido de un bloque de la plantilla base desde una plantilla secundaria en Twig?',
                'options' => [
                    'a' => '{{ super() }}',
                    'b' => '{{ parent() }}',
                    'c' => '{{ base() }}',
                    'd' => '{{ inherit() }}',
                ],
                'correct' => 'b',
                'explanation' => 'La función {{ parent() }} ejecuta el contenido definido en el bloque de la plantilla padre ({% extends %}), permitiendo añadir estilos o scripts sin sobreescribir el contenido original.',
            ],
        ],
    ],
];
