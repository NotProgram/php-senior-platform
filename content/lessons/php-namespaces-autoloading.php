<?php

declare(strict_types=1);

return [
    'slug' => 'php-namespaces-autoloading',
    'title' => 'PSR-4, Namespaces, spl_autoload_register & Composer Internals',
    'module' => 'PHP Moderno',
    'minutes' => 30,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'El estándar PSR-4 establece una correspondencia determinista entre los espacios de nombres (Namespaces) y la jerarquía de directorios en el disco. Composer implementa esta especificación registrando funciones de autocarga en el motor mediante spl_autoload_register.',
        'problem' => 'En sistemas heterogéneos (desarrollar en macOS/Windows y desplegar en Linux), discrepancias en mayúsculas y minúsculas o rutas mal formadas provocan que clases válidas fallen misteriosamente en producción con \'Class not found\'. Además, la resolución dinámica de archivos en disco genera overhead innecesario.',
        'solution' => 'Comprender la mecánica de spl_autoload_register, dominar la traducción de FQCN a rutas de archivo terminadas en .php y optimizar el Classmap de Composer en producción con flags autoritativos para resolución en memoria O(1).',
        'problem_label' => 'El Problema: La Maldición de \'En mi máquina funciona\' y Syscalls a Disco',
        'solution_label' => 'La Solución Senior: Estándar PSR-4, Casing Estricto y Classmap Autoritativo',
    ],
    'mental_model' => [
        'title' => 'El Código Postal Internacional y el Archivador Automatizado',
        'analogy' => 'Imagina una biblioteca global donde cada documento tiene un código postal internacional: `App\\Infrastructure\\Payment\\StripeGateway`. Antes de Composer, tenías que llamar manualmente al mensajero cada vez: `require_once \'src/Infrastructure/Payment/StripeGateway.php\'`. Si olvidabas uno, el programa colapsaba. Con **PSR-4**, el Namespace es una dirección geográfica precisa: el prefijo `App\\` indica el país (que mapea a la carpeta base `src/`), cada barra invertida `\\` es una subcarpeta en el archivador, y el nombre final es el archivo. Cuando tu código dice `new StripeGateway()`, el motor de PHP le pregunta al archivador automatizado (`spl_autoload_register`): \'¿Dónde está esta clase?\'. El archivador traduce la dirección, busca el archivo en el estante y lo carga exactamente en el milisegundo en que se necesita (Lazy Loading).',
        'ascii_diagram' => 'CÓDIGO: new \\App\\Infrastructure\\Payment\\StripeGateway();
                         │
                         ▼
   ¿Está la clase en la tabla de símbolos del Zend Engine?
               ├── SI ──> Instancia el objeto inmediatamente
               └── NO ──> Invoca la pila de spl_autoload_register()
                               │
                               ▼
            [PSR-4 Autoloader de Composer]
            1. Prefijo registrado: \'App\\\' ──> \'/var/www/html/src/\'
            2. Remueve prefijo: \'Infrastructure\\Payment\\StripeGateway\'
            3. Reemplaza \'\\\' por \'/\': \'Infrastructure/Payment/StripeGateway\'
            4. Añade extensión: \'/var/www/html/src/Infrastructure/Payment/StripeGateway.php\'
                               │
                               ▼
                   require_once $filePath;
                               │
                               ▼
           La clase se registra en la clase table del Zend Engine.
           ¡La ejecución continúa de forma transparente!',
        'key_concept' => 'El autoloading en PHP es un mecanismo bajo demanda (lazy). Composer no carga miles de clases al arrancar; solo carga aquellas que el flujo de ejecución realmente instancia o utiliza.',
    ],
    'internals' => [
        'title' => 'De FQCN a Inclusión en Disco: El Algoritmo PSR-4',
        'steps' => [
            [
                'phase' => '1. Disparo de spl_autoload_register',
                'description' => 'Cuando el Zend Engine encuentra un identificador de clase no registrado, interrumpe la ejecución y llama secuencialmente a los callbacks registrados en la pila de autoloading de SPL.',
            ],
            [
                'phase' => '2. Coincidencia de Prefijo de Namespace',
                'description' => 'El autoloader compara el inicio del FQCN con su mapa de prefijos (ej. \'App\\\'). Si no coincide, retorna inmediatamente y cede el paso al siguiente autoloader.',
            ],
            [
                'phase' => '3. Normalización de Separadores de Ruta',
                'description' => 'Toma la parte relativa de la clase, convierte todas las barras invertidas (\'\\\') en el separador de directorios del sistema operativo (\'/\') y concatena la ruta base asociada al prefijo.',
            ],
            [
                'phase' => '4. Adición de la Extensión .php',
                'description' => 'El estándar PSR-4 exige explícitamente que la ruta resultante termine exactamente con la extensión \'.php\', respetando el casing exacto del nombre de la clase.',
            ],
            [
                'phase' => '5. Inclusión Atómica en Zend VM',
                'description' => 'Se ejecuta require_once sobre la ruta resuelta. Si el archivo declara correctamente la clase esperada, el motor reanuda la operación original (new o llamada estática).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'El Síndrome de \'Funciona en mi Mac pero Falla en Linux\'',
            'icon' => 'alert-circle',
            'content' => 'Este es uno de los bugs más frustrantes en equipos de desarrollo:

• **macOS y Windows** usan por defecto sistemas de archivos no sensibles a mayúsculas (APFS / NTFS case-insensitive). Si tu archivo se llama `userRepository.php` pero en tu código escribes `use App\\Repository\\UserRepository;`, el sistema operativo lo encuentra de todos modos.
• **Linux en producción** usa sistemas de archivos estrictamente sensibles a mayúsculas (ext4 case-sensitive). Cuando el autoloader de Composer busca `UserRepository.php`, Linux responde con un error de \'Archivo no encontrado\', lanzando un fatídico `ClassNotFoundError` en pleno despliegue.

El estándar PSR-4 impone que el nombre del archivo y cada directorio intermedio deben coincidir **letra por letra y mayúscula por mayúscula** con el espacio de nombres de la clase.',
            'takeaways' => 'Mantén disciplina estricta en el casing de archivos y corre linters de nombres en tu pipeline de CI para evitar sorpresas en producción Linux.',
        ],
        [
            'title' => 'Los 3 Niveles de Optimización de Composer para Producción',
            'icon' => 'terminal',
            'content' => 'Por defecto en desarrollo, Composer utiliza resolución dinámica buscando archivos en disco mediante `file_exists()`. En producción con miles de clases, esto genera una sobrecarga brutal de llamadas al sistema.

Composer ofrece 3 niveles de optimización:

1. **`composer dump-autoload -o` (Optimized Classmap):** Escanea todo el proyecto y genera un array asociativo gigante `clase => ruta`. Si la clase está en el mapa, la incluye directamente sin buscar en disco.
2. **`composer dump-autoload -o --no-dev`:** Elimina todas las clases de testing (PHPUnit, Faker) del mapa de clases.
3. **`composer dump-autoload -a` (Classmap Authoritative):** El nivel definitivo para producción. Si una clase no está en el classmap generado, Composer **ni siquiera intenta buscarla en el disco**; falla de inmediato. Esto ahorra millones de operaciones de disco y bloquea sondeos maliciosos de archivos.',
            'code' => '# Comando canónico de optimización en Dockerfiles de producción
composer dump-autoload --optimize --no-dev --classmap-authoritative',
            'takeaways' => 'Usa siempre --classmap-authoritative en entornos de producción cerrados donde no se añadirán archivos PHP en caliente.',
        ],
    ],
    'video' => [
        'title' => 'Jordi Boggiano - Composer Best Practices',
        'speaker' => 'Jordi Boggiano (Co-creador de Composer)',
        'youtube_id' => 'mNFKZeYRdto',
        'duration' => '40 min',
        'description' => 'Una conferencia magistral del autor de Composer sobre optimización de autoloading, resolución de dependencias y despliegues empresariales.',
        'key_takeaways' => [
            'Cómo funciona internamente el árbol de resolución de clases de Composer.',
            'El impacto real de --classmap-authoritative en contenedores de producción.',
            'Por qué los enlaces simbólicos y el casing disparan fallos sutiles en Linux.',
            'Mejores prácticas para organizar namespaces en arquitecturas modulares.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Infrastructure/Autoloading/Psr4Resolver.php',
        'title' => 'Motor de Resolución de Rutas PSR-4 en Memoria',
        'tag' => 'PHP 8.4 PSR-4 Class Resolver',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Autoloading;

/**
 * Resuelve la ruta en disco de una clase conforme a la especificación PSR-4.
 */
class Psr4Resolver
{
    /**
     * @param string $prefix Prefijo del espacio de nombres (ej. \'App\\\\\')
     * @param string $baseDir Directorio base en disco (ej. \'src/\')
     */
    public function __construct(
        private readonly string $prefix = \'App\\\\\',
        private readonly string $baseDir = \'src/\'
    ) {}

    /**
     * Resuelve la ruta relativa del archivo para un Fully Qualified Class Name (FQCN).
     * Retorna null si la clase no pertenece al prefijo registrado.
     */
    public function resolveFilePath(string $className): ?string
    {
        if (!str_starts_with($className, $this->prefix)) {
            return null;
        }

        // Remueve el prefijo del espacio de nombres
        $relativeClass = substr($className, strlen($this->prefix));

        // Sustituye los separadores de espacio de nombres por barras de directorio
        $filePath = str_replace(\'\\\\\', \'/\', $relativeClass);

        // Concatena con el directorio base y la extensión obligatoria .php
        return rtrim($this->baseDir, \'/\') . \'/\' . $filePath . \'.php\';
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior no entiende por qué un despliegue falla por una mayúscula o cómo Composer encuentra los archivos. Un ingeniero Senior domina la especificación PSR-4, configura \'classmap-authoritative\' en sus contenedores Docker para ahorrar cientos de miles de syscalls al sistema operativo, y estructura sus bounded contexts mediante prefijos limpios.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Inclusiones manuales arcaicas y namespaces caóticos',
            'code' => '// Código obsoleto sin PSR-4
require_once __DIR__ . \'/../../lib/User.php\';
require_once __DIR__ . \'/../../lib/Order.php\';

class Controller {
    // Si cambia de lugar un archivo, todo se rompe
}',
            'flaws' => [
                'Requiere manualmente cada archivo, volviendo el código frágil e imposible de mantener.',
                'No aprovecha la carga perezosa (lazy) de clases, cargando memoria innecesaria en cada petición.',
                'Riesgo permanente de colisiones de nombres por falta de namespaces formales.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Mapeo PSR-4 determinista y autoloading lazy',
            'code' => 'declare(strict_types=1);

namespace App\\Infrastructure\\Autoloading;

class Psr4Resolver
{
    public function __construct(
        private readonly string $prefix = \'App\\\\\',
        private readonly string $baseDir = \'src/\'
    ) {}

    public function resolveFilePath(string $className): ?string
    {
        if (!str_starts_with($className, $this->prefix)) {
            return null;
        }

        $relative = substr($className, strlen($this->prefix));
        return rtrim($this->baseDir, \'/\') . \'/\' . str_replace(\'\\\\\', \'/\', $relative) . \'.php\';
    }
}',
            'rationale' => [
                'Cumple con la especificación PSR-4 garantizando correspondencia unívoca entre FQCN y archivos .php.',
                'Usa tipado estricto e inmutabilidad con propiedades readonly promovidas.',
                'Facilita la creación de herramientas de análisis estático y emuladores de autoloader para pruebas.',
            ],
            'trade_offs' => [
                'PSR-4 impone una estructura de carpetas rígida que debe reflejar exactamente los namespaces.',
                'En proyectos con millones de archivos, no optimizar el classmap puede penalizar el rendimiento por exceso de llamadas stat() a disco.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'PSR-4 Autoloader Specification',
            'source' => 'PHP-FIG: PSR-4 Standard',
            'quote' => 'El nombre de la clase completamente calificado debe tener un nombre de espacio de nombres de nivel superior, y los caracteres \\ en el nombre de la clase relativo deben convertirse en separadores de directorio.',
            'author' => 'PHP-FIG',
            'explanation' => 'PSR-4 unificó el ecosistema PHP eliminando para siempre los require manuales y estandarizando la estructura de carpetas.',
        ],
        [
            'topic' => 'Composer Classmap Optimization',
            'source' => 'Composer Documentation: Performance',
            'quote' => 'El classmap autoritativo le dice a Composer que el mapa contiene todas las clases posibles del proyecto, eliminando cualquier búsqueda en el sistema de archivos.',
            'author' => 'Jordi Boggiano',
            'explanation' => 'Es la optimización más efectiva en despliegues de contenedores Docker para maximizar el throughput.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP-FIG: Especificación Oficial PSR-4',
            'url' => 'https://www.php-fig.org/psr/psr-4/',
            'description' => 'Norma oficial que define la estructura y reglas del autoloader moderno.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Composer Documentation: Autoloading & Optimization',
            'url' => 'https://getcomposer.org/doc/articles/autoloader-optimization.md',
            'description' => 'Guía oficial de Composer sobre flags de optimización (-o, -a) y classmaps.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PHP Manual Oficial: spl_autoload_register',
            'url' => 'https://www.php.net/manual/es/function.spl-autoload-register.php',
            'description' => 'Función del motor nativo para registrar manejadores de autocarga de clases.',
            'type' => 'DOCS',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Resolver Rutas PSR-4 sin Acceso a Disco',
        'objective' => 'Implementar un algoritmo de resolución de clases PSR-4 en la clase Psr4Resolver que mapee un FQCN a una ruta de archivo relativa.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'Psr4Resolver\' en el namespace \'App\\Infrastructure\\Autoloading\'.
3. Implementa el método \'resolveFilePath(string $className): ?string\'.
4. Verifica que la clase comience con el prefijo configurado; de lo contrario retorna null.
5. Reemplaza \'\\\' por \'/\' y concatena la extensión obligatoria \'.php\'.',
        'filename' => 'src/Infrastructure/Autoloading/Psr4Resolver.php',
        'guide' => [
            'explanation' => 'Remueve el prefijo del nombre de la clase usando substr(). Luego reemplaza cada barra invertida \'\\\' por una barra \'/\' con str_replace(). Concatena el directorio base con la ruta resultante y la extensión \'.php\'.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] PSR-4 establece que el prefijo del espacio de nombres registrado se reemplaza por el directorio base. El resto del nombre de la clase traduce cada separador de namespace \\ a separadores de ruta / y añade la extensión .php.',
            ],
            [
                'text' => '[Pista 2: Estructura] Implementa public function resolveFilePath(string $className): ?string. Verifica si la clase comienza con el prefijo registrado; si no coincide, retorna null.',
            ],
            [
                'text' => '[Pista 3: Snippet] Remueve el prefijo con substr($className, strlen($this->prefix)). Reemplaza \\ por / usando str_replace(\'\\\\\', \'/\', $relative). Retorna rtrim($this->baseDir, \'/\') . \'/\' . $relative . \'.php\'.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Autoloading;

class Psr4Resolver
{
    public function __construct(
        private readonly string $prefix = \'App\\\\\',
        private readonly string $baseDir = \'src/\'
    ) {}

    public function resolveFilePath(string $className): ?string
    {
        // TODO: Implementa la resolución de ruta según PSR-4 terminada en .php
        return null;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Infrastructure\\Autoloading;

class Psr4Resolver
{
    public function __construct(
        private readonly string $prefix = \'App\\\\\',
        private readonly string $baseDir = \'src/\'
    ) {}

    public function resolveFilePath(string $className): ?string
    {
        if (!str_starts_with($className, $this->prefix)) {
            return null;
        }

        $relativeClass = substr($className, strlen($this->prefix));
        $filePath = str_replace(\'\\\\\', \'/\', $relativeClass);

        return rtrim($this->baseDir, \'/\') . \'/\' . $filePath . \'.php\';
    }
}
',
        'explanation' => 'El método valida el prefijo del namespace, calcula la ruta relativa reemplazando las barras invertidas por el separador de carpetas y garantiza que el archivo resuelto termine en .php según el estándar PSR-4.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: PSR-4, Namespaces, spl_autoload_register & Composer Internals',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un archivo que carga perfectamente en macOS puede fallar con \'Class not found\' en producción Linux?',
                'options' => [
                    'a' => 'Porque macOS compila las clases antes de ejecutar PHP.',
                    'b' => 'Porque macOS usa por defecto sistemas de archivos case-insensitive, mientras que Linux es estrictamente case-sensitive.',
                    'c' => 'Porque Linux requiere que los namespaces se declaren en mayúsculas sostenidas.',
                    'd' => 'Porque Composer solo es compatible con servidores basados en Debian.',
                ],
                'correct' => 'b',
                'explanation' => 'En macOS, si el archivo se llama \'user.php\' y la clase es \'User\', el sistema de archivos lo encuentra igual. En Linux ext4, \'User.php\' y \'user.php\' son archivos completamente distintos.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué comportamiento específico activa el flag \'--classmap-authoritative\' en Composer?',
                'options' => [
                    'a' => 'Obliga a Composer a descargar todas las dependencias en formato ZIP.',
                    'b' => 'Le indica a Composer que no busque en el sistema de archivos si una clase no está en el mapa, devolviendo fallo inmediato.',
                    'c' => 'Inhabilita el uso de opcache en el servidor.',
                    'd' => 'Permite modificar clases en caliente sin necesidad de recargar el contenedor.',
                ],
                'correct' => 'b',
                'explanation' => 'Al ser autoritativo, Composer omite cualquier búsqueda en disco para clases desconocidas, eliminando llamadas file_exists() y mejorando la seguridad y rendimiento.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es la función exacta de \'spl_autoload_register\' en el motor de PHP?',
                'options' => [
                    'a' => 'Descargar paquetes desde el repositorio Packagist.',
                    'b' => 'Registrar funciones de devolución (callbacks) en la pila interna de autocarga que se disparan cuando una clase no está definida.',
                    'c' => 'Compilar código PHP a archivos binarios ejecutables de Linux.',
                    'd' => 'Verificar los permisos de lectura de la carpeta vendor.',
                ],
                'correct' => 'b',
                'explanation' => 'Permite registrar una o más funciones o métodos para que el motor Zend los invoque automáticamente cada vez que se intente usar una clase que aún no ha sido cargada en memoria.',
            ],
        ],
    ],
];
