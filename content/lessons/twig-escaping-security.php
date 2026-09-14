<?php

declare(strict_types=1);

return [
    'slug' => 'twig-escaping-security',
    'title' => 'Auto-escaping, Contextos Seguros & XSS Defense',
    'module' => 'Twig Template Engine',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'Twig aplica auto-escaping contextual por defecto en todas las expresiones \'{{ var }}\', traduciendo caracteres peligrosos a entidades HTML. Sin embargo, cuando una aplicación debe permitir HTML enriquecido (ej. descripciones con negrita o párrafos), nunca debe usarse \'|raw\' a ciegas: se requiere una sanitización defensiva activa que elimine scripts y esquemas maliciosos.',
        'problem' => 'El uso irreflexivo del filtro \'|raw\' para renderizar texto enriquecido abre brechas críticas de Cross-Site Scripting (XSS) almacenado, permitiendo a atacantes robar cookies de sesión, tokens CSRF o secuestrar cuentas de usuario mediante etiquetas \'<script>\' o enlaces \'href="javascript:..."\'.',
        'solution' => 'Implementar un sanitizador de contenido seguro \'SafeContentSanitizer\' que detecte y rechace etiquetas \'<script>\', neutralice el pseudo-protocolo \'javascript:\' y aplique filtrado estricto mediante allowlists de etiquetas seguras (strip_tags con etiquetas permitidas).',
        'problem_label' => 'El Problema: La Tentación de \'|raw\' y la Catástrofe de XSS',
        'solution_label' => 'La Solución Senior: Sanitización Defensiva, Detección de Scripts y Protocolos Seguros',
    ],
    'mental_model' => [
        'title' => 'El Escáner de Rayos X de la Aduana vs El Pase VIP Sin Inspección (|raw)',
        'analogy' => 'Imagina la aduana de un aeropuerto internacional. Por defecto, todas las maletas pasan por un escáner de rayos X automático (**Auto-escaping de Twig**): si alguien intenta pasar un arma (un código `&lt;script&gt;`), el escáner la convierte en una foto de plástico inofensiva (`&lt;script&gt;`). Pero a veces el presidente o un diplomático necesita ingresar un paquete con herramientas especiales (contenido enriquecido con `<p>`, `<b>`, `<em>`). Usar `{{ content|raw }}` es como darle un pase VIP sin inspección a cualquier pasajero desconocido: el terrorista entra con el arma y toma el avión (XSS Stored). La **Defensa Senior** es implementar un **Escáner Forense Especializado** (`SafeContentSanitizer`): no prohíbe las maletas diplomáticas, pero inspecciona minuciosamente cada milímetro: detecta y destruye cualquier rastro de detonadores (`<script`), bloquea cables ocultos (`href="javascript:..."`) y solo permite ingresar objetos estrictamente certificados en su lista blanca (`strip_tags` con allowlist).',
        'ascii_diagram' => 'PAYLOAD MALICIOSO DEL ATACANTE:
"<script>stealTokens();</script><a href=\'javascript:pwn()\'>Click</a><p>Texto seguro</p>"
                       │
       ┌───────────────┴───────────────┐
       ▼                               ▼
[Twig por Defecto: {{ input }}]     [Uso Imprudente: {{ input|raw }}]
Codifica a texto inofensivo:         ¡Inyecta script activo en el DOM!
"&lt;script&gt;..." (Seguro)         Ataque XSS exitoso: Robo de credenciales.

DEFENSA SENIOR CON SANITIZADOR:
SafeContentSanitizer::sanitize($untrustedHtml)
├─ 1. Detecta y rechaza etiquetas \'<script\'
├─ 2. Detecta y neutraliza pseudo-protocolos \'javascript:\'
└─ 3. strip_tags($html, [\'<p>\', \'<b>\', \'<strong>\', \'<em>\', \'<a>\'])
                       │
                       ▼
HTML limpio, enriquecido y 100% seguro para el navegador.',
        'key_concept' => 'El auto-escaping de Twig es tu primera línea de defensa. Si debes renderizar HTML con |raw, sanitiza primero el contenido con un SafeContentSanitizer que erradique scripts y protocolos maliciosos.',
    ],
    'internals' => [
        'title' => 'Los 5 Contextos de Escapado y la Anatomía de XSS',
        'steps' => [
            [
                'phase' => '1. Escapado de Contexto HTML (html)',
                'description' => 'El modo predeterminado de Twig. Codifica &, <, >, ", y \' en entidades HTML (&amp;, &lt;, &gt;, &quot;, &#039;).',
            ],
            [
                'phase' => '2. Escapado de Contexto JavaScript (js)',
                'description' => 'Cuando interpolas variables dentro de bloques <script>, el escapado HTML no sirve. Twig provee |escape(\'js\'), que convierte caracteres no alfanuméricos a secuencias de escape hexadecimales (\\x22, \\x27).',
            ],
            [
                'phase' => '3. Escapado de Atributos y URLs (url / css)',
                'description' => 'En atributos como href=\'...\', caracteres como dos puntos \':\' o comillas permiten inyectar \'javascript:alert(1)\'. Se debe usar |escape(\'url\') o sanitizar protocolos.',
            ],
            [
                'phase' => '4. Detección de Pseudo-Protocolos Activos',
                'description' => 'Atacantes eluden filtros de tags insertando vectores en enlaces: <a href=\'javascript:alert(1)\'>. El sanitizador debe buscar y remover la secuencia \'javascript:\' insensible a mayúsculas.',
            ],
            [
                'phase' => '5. Filtrado con Allowlists (strip_tags)',
                'description' => 'Nunca intentes crear una \'lista negra\' (denylist) de tags malos. Una estrategia senior usa \'listas blancas\' (allowlists) permitiendo solo etiquetas de formato semántico estrictas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Los 3 Tipos de XSS y su Impacto en Aplicaciones Symfony',
            'icon' => 'shield-alert',
            'content' => 'Para defender tu aplicación, debes conocer cómo opera el adversario:

1. **Reflected XSS (Reflejado):** El payload malicioso viaja en la URL (`/search?q=&lt;script&gt;...`). Si el controlador lo imprime sin escapar, se ejecuta en el navegador de la víctima que hizo clic en el enlace.
2. **Stored XSS (Almacenado):** El más peligroso. El atacante guarda el script en la base de datos (ej. en el perfil o comentario de un blog). Cada usuario o administrador que abra esa página ejecutará el código sin saberlo.
3. **DOM-based XSS:** Ocurre puramente en el cliente cuando código JavaScript toma datos de `location.hash` y los evalúa con `innerHTML` o `eval()`.

Twig neutraliza el 99% de Reflected y Stored XSS mediante auto-escaping, siempre que no uses `|raw` imprudentemente.',
            'takeaways' => 'Nunca desactives el auto-escaping global en Twig y audita rigurosamente cada uso de |raw en tus plantillas.',
        ],
        [
            'title' => 'HtmlSanitizer Component en Symfony 6 & 7',
            'icon' => 'check-circle',
            'content' => 'A partir de Symfony 6.1, el framework introdujo el componente oficial `symfony/html-sanitizer`:

```php
use Symfony\\Component\\HtmlSanitizer\\HtmlSanitizerConfig;
use Symfony\\Component\\HtmlSanitizer\\HtmlSanitizer;

$sanitizer = new HtmlSanitizer(
    (new HtmlSanitizerConfig())
        ->allowSafeElements()
        ->allowLinkSchemes([\'http\', \'https\', \'mailto\'])
);

$cleanHtml = $sanitizer->sanitize($dirtyInput);
```

Este componente parsea el árbol DOM completo y elimina cualquier atributo o elemento no seguro conforme a las directrices W3C.',
            'takeaways' => 'En proyectos de producción con Symfony 7, aprovecha el componente HtmlSanitizer nativo para procesar texto enriquecido.',
        ],
    ],
    'video' => [
        'title' => 'XSS Explained - What Is Cross Site Scripting - Program With Gio',
        'speaker' => 'Gio (Program With Gio)',
        'youtube_id' => 'gPU_jyKDYx0',
        'duration' => '22 min',
        'description' => 'Una explicación visual magistral sobre los vectores de ataque XSS, robo de cookies de sesión, contexto de inyección y defensas con motores de plantillas.',
        'key_takeaways' => [
            'Cómo funciona un ataque XSS en una aplicación web real.',
            'La diferencia física entre codificar entidades HTML y ejecutar código en el DOM.',
            'Por qué los pseudo-protocolos como \'javascript:\' eluden filtros ingenuos.',
            'Estrategias de sanitización defensiva en PHP 8 y motores de plantillas.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Security/Sanitizer/SafeContentSanitizer.php',
        'title' => 'Sanitizador Defensivo de Contenido HTML contra XSS',
        'tag' => 'PHP 8.4 XSS Sanitizer',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Sanitizer;

use InvalidArgumentException;

/**
 * Sanitiza contenido HTML no confiable neutralizando etiquetas de script,
 * pseudo-protocolos maliciosos y restringiendo elementos a una allowlist segura.
 */
class SafeContentSanitizer
{
    private const array ALLOWED_TAGS = [\'<p>\', \'<b>\', \'<strong>\', \'<em>\', \'<ul>\', \'<ol>\', \'<li>\', \'<br>\'];

    /**
     * Sanitiza el HTML eliminando scripts y protocolos inseguros.
     */
    public function sanitize(string $untrustedHtml): string
    {
        // Detección proactiva de inyecciones de script
        if (preg_match(\'/<script\\b[^>]*>/i\', $untrustedHtml)) {
            throw new InvalidArgumentException(\'Contenido rechazado: Se detectó una etiqueta <script> no autorizada.\');
        }

        // Neutralización de pseudo-protocolos \'javascript:\'
        if (stripos($untrustedHtml, \'javascript:\') !== false) {
            throw new InvalidArgumentException(\'Contenido rechazado: Se detectó el pseudo-protocolo "javascript:".\');
        }

        // Filtrado estricto mediante allowlist de etiquetas
        return strip_tags($untrustedHtml, self::ALLOWED_TAGS);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior confía ciegamente en que \'un input de administrador nunca será hackeado\' y pone \'|raw\' en todas partes sin protección. Un ingeniero Senior asume la postura de Desconfianza Cero (Zero Trust): audita los contextos de escapado en Twig, sanitiza activamente cualquier HTML antes de emitirlo con un SafeContentSanitizer, y configura cabeceras Content-Security-Policy (CSP) para bloquear cualquier script no firmado.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Desactivación ciega del auto-escaping con |raw',
            'code' => '{# Código vulnerable a XSS Stored #}
<div class="article-body">
    {{ article.content|raw }} {# Si un redactor o atacante inyecta un script, hackea a todos los usuarios! #}
</div>',
            'flaws' => [
                'Apaga el auto-escaping de Twig permitiendo la ejecución de scripts arbitrarios.',
                'No inspecciona si existen etiquetas <script> o enlaces javascript: maliciosos.',
                'Asume ingenuamente que los datos de la base de datos siempre son seguros.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Sanitización previa con detección de scripts y allowlist',
            'code' => 'declare(strict_types=1);

namespace App\\Security\\Sanitizer;

use InvalidArgumentException;

class SafeContentSanitizer
{
    private const array ALLOWED_TAGS = [\'<p>\', \'<b>\', \'<strong>\', \'<em>\', \'<ul>\', \'<li>\'];

    public function sanitize(string $untrustedHtml): string
    {
        if (preg_match(\'/<script\\b[^>]*>/i\', $untrustedHtml)) {
            throw new InvalidArgumentException(\'Script tag rejected\');
        }
        if (stripos($untrustedHtml, \'javascript:\') !== false) {
            throw new InvalidArgumentException(\'Javascript protocol rejected\');
        }
        return strip_tags($untrustedHtml, self::ALLOWED_TAGS);
    }
}',
            'rationale' => [
                'Detecta proactivamente vectores de inyección de script y lanza InvalidArgumentException.',
                'Bloquea esquemas peligrosos como javascript: que ejecutan código al hacer clic.',
                'Aplica strip_tags con una lista blanca cerrada de elementos permitidos.',
            ],
            'trade_offs' => [
                'Sanitizar mediante expresiones regulares y strip_tags es adecuado para casos estándar; para HTML arbitrario complejo con estilos y medios es preferible usar el componente oficial Symfony HtmlSanitizer.',
                'Rechazar contenido con excepciones requiere un manejo adecuado en el formulario para alertar al usuario de forma amigable.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Context-Aware Escaping',
            'source' => 'Twig Security Guide',
            'quote' => 'Twig es consciente del contexto de escapado. Escapar para HTML es diferente de escapar para JavaScript o CSS, y usar la estrategia incorrecta deja la puerta abierta a XSS.',
            'author' => 'Fabien Potencier',
            'explanation' => 'El motor de Twig proporciona filtros de contexto como |escape(\'js\') para neutralizar inyecciones complejas.',
        ],
        [
            'topic' => 'The Zero-Trust View Architecture',
            'source' => 'OWASP Secure Coding Practices',
            'quote' => 'Trate todos los datos de entrada como no confiables, incluso aquellos almacenados en la base de datos que ya fueron validados anteriormente.',
            'author' => 'OWASP Foundation',
            'explanation' => 'La defensa en profundidad exige sanitizar en el límite de presentación antes de emitir HTML crudo.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Twig Docs: The Escape Filter & Contexts',
            'url' => 'https://twig.symfony.com/doc/3.x/filters/escape.html',
            'description' => 'Documentación oficial sobre auto-escaping, el filtro escape y estrategias de contexto.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Symfony Docs: The HtmlSanitizer Component',
            'url' => 'https://symfony.com/doc/current/html_sanitizer.html',
            'description' => 'Guía oficial para sanitizar contenido HTML no confiable en Symfony 6 y 7.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'OWASP Cross-Site Scripting (XSS) Prevention Cheat Sheet',
            'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Cross_Site_Scripting_Prevention_Cheat_Sheet.html',
            'description' => 'Manual de referencia de la industria sobre reglas de codificación y sanitización contra XSS.',
            'type' => 'SPEC',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Sanitizador de Contenido Defensivo contra XSS',
        'objective' => 'Implementar la clase SafeContentSanitizer con el método sanitize(string $untrustedHtml): string que detecte y rechace etiquetas <script>, rechace el pseudo-protocolo \'javascript:\' y aplique strip_tags con una allowlist.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'SafeContentSanitizer\' en el namespace \'App\\Security\\Sanitizer\'.
3. Implementa el método \'sanitize(string $untrustedHtml): string\'.
4. Si el HTML contiene una etiqueta de script (\'<script\'), lanza una \'InvalidArgumentException\'.
5. Si el HTML contiene el pseudo-protocolo \'javascript:\', lanza una \'InvalidArgumentException\'.
6. Sanitiza el HTML restante usando \'strip_tags\' con una lista blanca \'ALLOWED_TAGS\' (ej. [\'<p>\', \'<b>\', \'<strong>\', \'<em>\']).',
        'filename' => 'src/Security/Sanitizer/SafeContentSanitizer.php',
        'guide' => [
            'explanation' => 'En sanitize(), verifica if (str_contains(strtolower($untrustedHtml), \'<script\') || stripos($untrustedHtml, \'<script\') !== false) throw new \\InvalidArgumentException(\'Tag script detectado\');. Verifica javascript: de forma similar y retorna strip_tags($untrustedHtml, self::ALLOWED_TAGS);.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Debe implementarse la clase SafeContentSanitizer con el método sanitize(string $untrustedHtml): string.',
            ],
            [
                'text' => '[Pista 2: Estructura] Debe detectarse y rechazarse cualquier intento de inyección de etiquetas &lt;script&gt; y el pseudo-protocolo \'javascript:\' lanzando InvalidArgumentException.',
            ],
            [
                'text' => '[Pista 3: Snippet] if (stripos($untrustedHtml, \'<script\') !== false) throw new \\InvalidArgumentException(\'Script no permitido\'); if (stripos($untrustedHtml, \'javascript:\') !== false) throw new \\InvalidArgumentException(\'Javascript no permitido\'); return strip_tags($untrustedHtml, self::ALLOWED_TAGS);.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Sanitizer;

use InvalidArgumentException;

class SafeContentSanitizer
{
    private const array ALLOWED_TAGS = [\'<p>\', \'<b>\', \'<strong>\', \'<em>\'];

    public function sanitize(string $untrustedHtml): string
    {
        // TODO: Detecta <script y javascript: lanzando excepción, y filtra con strip_tags
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Sanitizer;

use InvalidArgumentException;

class SafeContentSanitizer
{
    private const array ALLOWED_TAGS = [\'<p>\', \'<b>\', \'<strong>\', \'<em>\', \'<ul>\', \'<li>\'];

    public function sanitize(string $untrustedHtml): string
    {
        if (stripos($untrustedHtml, \'<script\') !== false) {
            throw new InvalidArgumentException(\'Contenido rechazado: Se detectó una etiqueta <script> no autorizada.\');
        }

        if (stripos($untrustedHtml, \'javascript:\') !== false) {
            throw new InvalidArgumentException(\'Contenido rechazado: Se detectó el pseudo-protocolo "javascript:".\');
        }

        return strip_tags($untrustedHtml, self::ALLOWED_TAGS);
    }
}
',
        'explanation' => 'SafeContentSanitizer defiende proactivamente contra ataques XSS al rechazar de forma estricta etiquetas <script> y pseudo-protocolos \'javascript:\', y al limitar los elementos HTML restantes a una lista blanca cerrada mediante strip_tags.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Auto-escaping, Contextos Seguros & XSS Defense',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué es insuficiente confiar únicamente en una \'lista negra\' (denylist) de etiquetas para prevenir XSS?',
                'options' => [
                    'a' => 'Porque los atacantes pueden utilizar variaciones infinitas de ofuscación (como <sCrIpt>, eventos inline onerror=, o vectores SVG/img) que eluden listas negras.',
                    'b' => 'Porque las listas negras consumen demasiada memoria zval en el servidor.',
                    'c' => 'Porque las listas negras solo funcionan en peticiones POST.',
                    'd' => 'Porque Twig no permite usar expresiones regulares.',
                ],
                'correct' => 'a',
                'explanation' => 'Las listas negras siempre pierden la carrera armamentista de la seguridad. Una política segura utiliza allowlists (listas blancas) que solo permiten lo explícitamente conocido como inocuo.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué un enlace como \'<a href="javascript:steal()">\' es peligroso incluso si no contiene la etiqueta \'<script>\'?',
                'options' => [
                    'a' => 'Porque el pseudo-protocolo \'javascript:\' ejecuta código del lado del cliente inmediatamente cuando el usuario hace clic en el enlace.',
                    'b' => 'Porque desconfigura el certificado SSL de la conexión HTTPS.',
                    'c' => 'Porque envía el archivo de configuración .env al navegador.',
                    'd' => 'Porque reinicia el contenedor de Docker.',
                ],
                'correct' => 'a',
                'explanation' => 'El esquema \'javascript:\' es interpretado por el navegador como una instrucción para evaluar código, permitiendo ataques XSS sin necesidad de usar etiquetas <script> tradicionales.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué política de cabecera HTTP complementa al auto-escaping de Twig para bloquear la ejecución de scripts no autorizados?',
                'options' => [
                    'a' => 'Access-Control-Allow-Origin',
                    'b' => 'Content-Security-Policy (CSP)',
                    'c' => 'X-Powered-By',
                    'd' => 'Cache-Control',
                ],
                'correct' => 'b',
                'explanation' => 'Content-Security-Policy (CSP) es la barrera definitiva del navegador que impide la ejecución de scripts en línea no firmados por nonces criptográficos, mitigando el impacto de posibles fallos de sanitización.',
            ],
        ],
    ],
];
