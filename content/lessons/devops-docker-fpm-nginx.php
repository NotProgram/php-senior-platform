<?php

declare(strict_types=1);

return [
    'slug' => 'devops-docker-fpm-nginx',
    'title' => 'Docker Multi-stage para PHP-FPM & Nginx',
    'module' => 'devops',
    'minutes' => 50,
    'difficulty' => 'Avanzado',
    'overview' => [
        'concept' => 'En la contenedorizacion moderna de aplicaciones PHP, construir imagenes Docker monoliticas donde se instalan Git, Composer, extensiones de compilacion (gcc, make) y dependencias de desarrollo (PHPUnit, PHPStan) es un grave antipatron. Produce imagenes de mas de 1.5 Gigabytes, ralentiza los despliegues en Kubernetes e introduce cientos de vulnerabilidades de seguridad conocidas en paquetes que nunca deberian existir en produccion.

La tecnica canonica de la industria es Docker Multi-stage Builds (Construcciones en Multiples Etapas). En la primera etapa (Builder), se instalan Composer y las herramientas de compilacion para ejecutar composer install --no-dev --optimize-autoloader. En la segunda etapa (Runtime de Produccion), se parte de una imagen limpia alpine o debian-slim minima, se copian unicamente los artefactos compilados desde el builder, se configura un usuario no-root (appuser / www-data) y se expone el socket FastCGI para Nginx, logrando imagenes ultraligeras (<150 MB) y blindadas.',
        'problem' => 'En la contenedorizacion moderna de aplicaciones PHP, construir imagenes Docker monoliticas donde se instalan Git, Composer, extensiones de compilacion (gcc, make) y dependencias de desarrollo (PHPUnit, PHPStan) es un grave antipatron. Produce imagenes de mas de 1.5 Gigabytes, ralentiza los despliegues en Kubernetes e introduce cientos de vulnerabilidades de seguridad conocidas en paquetes que nunca deberian existir en produccion.',
        'solution' => 'La tecnica canonica de la industria es Docker Multi-stage Builds (Construcciones en Multiples Etapas). En la primera etapa (Builder), se instalan Composer y las herramientas de compilacion para ejecutar composer install --no-dev --optimize-autoloader. En la segunda etapa (Runtime de Produccion), se parte de una imagen limpia alpine o debian-slim minima, se copian unicamente los artefactos compilados desde el builder, se configura un usuario no-root (appuser / www-data) y se expone el socket FastCGI para Nginx, logrando imagenes ultraligeras (<150 MB) y blindadas.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Andamio de Construccion vs el Rascacielos Terminado',
        'concept' => 'Imagina la construccion de una torre de oficinas de 50 pisos:
1. LA ETAPA BUILDER (El Andamiaje y las Gruas): Para levantar la torre necesitas gruas gigantes, andamios de metal, soldadoras, generadores de diesel y mezcladoras de cemento. Estas herramientas son indispensables para construir, pero ocupan espacio y son pesadas.
2. LA ENTREGA AL CLIENTE (La Imagen Final): Cuando el rascacielos se inaugura, los obreros desmontan los andamios, retiran las gruas y se llevan las mezcladoras de cemento. Nadie deja una grua oxidada en el vestibulo del edificio.

El Multi-stage build hace exactamente eso: utiliza una imagen \'Builder\' con todo el andamiaje (Composer, Git, gcc) para construir la aplicacion, y luego copia unicamente las paredes terminadas (vendor/ y public/) a la imagen final limpia.',
        'ascii_diagram' => 'DOCKER MULTI-STAGE BUILD PARA PHP-FPM:

+-------------------------------------------------------------+
| ETAPA 1: BUILDER (FROM composer:2 AS builder)              |
|  - Contiene Git, Composer, herramientas de compilacion      |
|  - Ejecuta: composer install --no-dev --optimize-autoloader |
|  - Peso de la imagen temporal: ~800 MB                      |
+-------------------------------------------------------------+
                               |
                   COPIA SOLO ARTEFACTOS
                    COPY --from=builder /app/vendor /var/www/vendor
                               |
                               v
+-------------------------------------------------------------+
| ETAPA 2: PRODUCCION (FROM php:8.4-fpm-alpine AS prod)       |
|  - Imagen base limpia sin Git ni compiladores               |
|  - USER www-data (Ejecucion no-root)                        |
|  - Extensiones PHP minimas de runtime (pdo_mysql, opcache)  |
|  - Peso final: ~120 MB (90% mas ligera, maxima seguridad)   |
+-------------------------------------------------------------+
',
        'analogy' => 'Imagina la construccion de una torre de oficinas de 50 pisos:
1. LA ETAPA BUILDER (El Andamiaje y las Gruas): Para levantar la torre necesitas gruas gigantes, andamios de metal, soldadoras, generadores de diesel y mezcladoras de cemento. Estas herramientas son indispensables para construir, pero ocupan espacio y son pesadas.
2. LA ENTREGA AL CLIENTE (La Imagen Final): Cuando el rascacielos se inaugura, los obreros desmontan los andamios, retiran las gruas y se llevan las mezcladoras de cemento. Nadie deja una grua oxidada en el vestibulo del edificio.

El Multi-stage build hace exactamente eso: utiliza una imagen \'Builder\' con todo el andamiaje (Composer, Git, gcc) para construir la aplicacion, y luego copia unicamente las paredes terminadas (vendor/ y public/) a la imagen final limpia.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En el analisis de seguridad de un Dockerfile, un auditor verifica tres politicas no negociables: 1. Multiples etapas: identificadas por expresiones regulares preg_match_all sobre clausulas FROM ...',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'AS ... 2.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Usuario no-root: inclusion obligatoria de la directiva USER para no correr el daemon como UID 0. 3.',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => 'Dependencias de produccion: inclusion de la bandera --no-dev en la ejecucion de Composer para que librerias de pruebas no queden expuestas en la superficie de ataque del contenedor.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'La comunicacion entre Nginx y PHP-FPM en contenedores: En entornos de alto rendimiento, Nginx y PHP-FPM pueden correr como contenedores separados en el mismo Pod de Kubernetes comunicandose por un socket Unix compartido en un volumen tmpfs en memoria RAM (/var/run/php-fpm.sock). Esto elimina la sobrecarga del stack de red TCP loopback (127.0.0.1:9000), reduciendo la latencia de cada peticion en 2ms.',
        ],
    ],
    'video' => [
        'title' => 'PHP and Docker - Multi-stage Builds',
        'speaker' => 'Gary Clarke',
        'youtube_id' => 'Y3qpFtxfh9Y',
        'duration' => '18 min',
        'description' => 'Gary Clarke ensena paso a paso como construir imagenes Docker profesionales para aplicaciones PHP y Symfony utilizando construcciones multi-stage, reduciendo el tamano de la imagen y aislando dependencias de desarrollo.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Docker;

use InvalidArgumentException;
use RuntimeException;

final class DockerManifestValidator
{
    /**
     * Valida si un manifiesto Dockerfile cumple con los estandares de seguridad enterprise.
     *
     * @return array{valid: bool, stages_count: int, non_root_user: bool}
     */
    public function validateDockerfile(string $dockerfileContent): array
    {
        $clean = trim($dockerfileContent);
        if ($clean === \'\') {
            throw new InvalidArgumentException(\'El contenido del Dockerfile no puede estar vacio.\');
        }

        // 1. Detectar etapas multi-stage
        preg_match_all(\'/FROM\\s+[^\\s]+\\s+AS\\s+([^\\s]+)/i\', $clean, $matches);
        $stagesCount = count($matches[1]);

        if ($stagesCount < 2) {
            throw new RuntimeException(\'El Dockerfile debe contener al menos 2 etapas multi-stage (FROM ... AS ...).\');
        }

        // 2. Verificar ejecucion como usuario no-root
        $hasNonRootUser = preg_match(\'/USER\\s+[^\\s]+/i\', $clean) === 1;
        if (!$hasNonRootUser) {
            throw new RuntimeException(\'El Dockerfile debe especificar la directiva USER para ejecucion no-root.\');
        }

        // 3. Verificar inclusion de bandera --no-dev
        $hasNoDev = str_contains($clean, \'--no-dev\');
        if (!$hasNoDev) {
            throw new RuntimeException(\'El Dockerfile debe incluir la bandera --no-dev en la instalacion de dependencias.\');
        }

        return [
            \'valid\' => true,
            \'stages_count\' => $stagesCount,
            \'non_root_user\' => true,
        ];
    }

    public function reset(): void {}
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior crea una sola imagen con FROM ubuntu, instala 40 paquetes con apt-get y deja todo corriendo como root. El desarrollador Senior utiliza multi-stage builds: la imagen de produccion solo contiene los artefactos indispensables, corre bajo un usuario restringido y reduce el tiempo de despliegue y escaneo de vulnerabilidades en CI/CD a segundos.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Despliega imagenes de 1.8 GB con Git y gcc instalados en los servidores de produccion.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Separa etapa builder de etapa runtime, generando imagenes minimas de 120 MB sin herramientas de compilacion en produccion.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Multi-stage Builds',
            'source' => 'Docker Official Documentation: Best practices for Dockerfiles',
            'quote' => 'Con las construcciones multi-stage, utilizas multiples sentencias FROM en tu Dockerfile. Puedes copiar selectivamente artefactos de una etapa a otra, dejando atras todo lo innecesario en la imagen final.',
            'author' => 'Docker Engineering Team',
            'explanation' => 'Permite mantener imagenes ligeras sin scripts de shell intermedios complejos.',
        ],
        [
            'topic' => 'Seguridad en Contenedores',
            'source' => 'CIS Docker Benchmark',
            'quote' => 'Por defecto, los contenedores se ejecutan con privilegios de root. Siempre debes crear un usuario dedicado para el proceso de la aplicacion y cambiar a el con la directiva USER.',
            'author' => 'Center for Internet Security',
            'explanation' => 'Previene escapes de contenedor hacia el host en caso de una vulnerabilidad de ejecucion de codigo.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Docker Docs: Multi-stage builds',
            'url' => 'https://docs.docker.com/build/building/multi-stage/',
            'type' => 'DOCS',
        ],
        [
            'title' => 'OWASP Docker Security Cheat Sheet',
            'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Docker_Security_Cheat_Sheet.html',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Validador de Manifiestos Docker Multi-Stage (DockerManifestValidator)',
        'objective' => 'Implementar la clase DockerManifestValidator con validateDockerfile() y reset(), validando $dockerfileContent no vacio con InvalidArgumentException, detectando al menos 2 etapas FROM ... AS ..., verificando la directiva USER y la bandera --no-dev, y lanzando RuntimeException ante incumplimientos.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\DevOps\\Docker.
2. Implementa la clase DockerManifestValidator.
3. Implementa function validateDockerfile(string $dockerfileContent): array.
4. Si $dockerfileContent esta vacio, lanza \\InvalidArgumentException.
5. Detecta etapas multi-stage con preg_match_all(\'/FROM\\s+[^\\s]+\\s+AS\\s+([^\\s]+)/i\', $content, $matches); si stages < 2, lanza \\RuntimeException.
6. Comprueba que contenga \'USER\'; si no, lanza \\RuntimeException.
7. Comprueba que contenga \'--no-dev\'; si no, lanza \\RuntimeException.
8. Retorna un array con claves valid (true), stages_count (int) y non_root_user (true).
9. Implementa function reset(): void.',
        'filename' => 'src/DevOps/Docker/DockerManifestValidator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que trim($dockerfileContent) !== \'\', de lo contrario lanza InvalidArgumentException.',
                'Paso 2: Ejecuta preg_match_all sobre las clausulas FROM con alias AS.',
                'Paso 3: Verifica que el total de coincidencias sea al menos 2.',
                'Paso 4: Comprueba que str_contains o preg_match contenga USER y --no-dev.',
                'Paso 5: Si alguna regla falla, lanza RuntimeException; de lo contrario retorna el array estructurado.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Multi-stage builds requieren al menos una etapa builder y una etapa prod.',
            ],
            [
                'text' => '[Pista 2: Estructura] preg_match_all(\'/FROM\\\\s+[^\\\\s]+\\\\s+AS\\\\s+([^\\\\s]+)/i\', $clean, $matches);',
            ],
            [
                'text' => '[Pista 3: Snippet] if (count($matches[1]) < 2) { throw new \\RuntimeException(\'Se requieren multiples etapas\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Docker;

use InvalidArgumentException;
use RuntimeException;

class DockerManifestValidator
{
    /**
     * @return array{valid: bool, stages_count: int, non_root_user: bool}
     */
    public function validateDockerfile(string $dockerfileContent): array
    {
        // TODO: Validar no vacio (lanzar InvalidArgumentException)
        // TODO: Detectar al menos 2 etapas FROM ... AS ... (lanzar RuntimeException)
        // TODO: Comprobar directiva USER (lanzar RuntimeException)
        // TODO: Comprobar bandera --no-dev (lanzar RuntimeException)
        // TODO: Retornar [\'valid\' => true, \'stages_count\' => ..., \'non_root_user\' => true]
        return [];
    }

    public function reset(): void {}
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Docker;

use InvalidArgumentException;
use RuntimeException;

class DockerManifestValidator
{
    /**
     * @return array{valid: bool, stages_count: int, non_root_user: bool}
     */
    public function validateDockerfile(string $dockerfileContent): array
    {
        $clean = trim($dockerfileContent);
        if ($clean === \'\') {
            throw new InvalidArgumentException(\'El manifiesto Dockerfile no puede estar vacio.\');
        }

        preg_match_all(\'/FROM\\s+[^\\s]+\\s+AS\\s+([^\\s]+)/i\', $clean, $matches);
        $stagesCount = count($matches[1]);

        if ($stagesCount < 2) {
            throw new RuntimeException(\'El Dockerfile debe implementar multi-stage builds con al menos 2 etapas.\');
        }

        if (!str_contains($clean, \'USER\')) {
            throw new RuntimeException(\'El Dockerfile debe especificar un usuario no-root mediante USER.\');
        }

        if (!str_contains($clean, \'--no-dev\')) {
            throw new RuntimeException(\'El Dockerfile debe compilar las dependencias con --no-dev.\');
        }

        return [
            \'valid\' => true,
            \'stages_count\' => $stagesCount,
            \'non_root_user\' => true,
        ];
    }

    public function reset(): void {}
}
',
        'explanation' => 'La clase DockerManifestValidator automatiza la verificacion de buenas practicas en manifiestos Docker. Exige construcciones multi-stage para desacoplar el entorno de compilacion del de produccion, fuerza la ejecucion no-root y verifica la exclusion de herramientas de desarrollo.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Docker Multi-stage para PHP-FPM & Nginx',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la razon principal para no incluir Git, Composer ni PHPUnit en la imagen final de produccion de Docker?',
                'options' => [
                    'a' => 'Porque Docker no permite ejecutar mas de un comando por contenedor.',
                    'b' => 'Porque reducen significativamente el peso de la imagen y eliminan posibles vectores de ataque (superficie de ataque) que un intruso podria explotar.',
                    'c' => 'Porque las licencias de Git prohiben su ejecucion en la nube.',
                    'd' => 'Porque PHP-FPM falla si detecta archivos de testing en el disco.',
                ],
                'correct' => 'b',
                'explanation' => 'Herramientas como Git o compiladores en la imagen de produccion facilitan que un atacante descargue o compile exploits si logra ejecutar comandos remotos. Las imagenes de produccion deben ser minimalistas.',
            ],
            [
                'id' => 'q2',
                'question' => 'En un Dockerfile multi-stage, ¿como se copian los archivos generados en la etapa \'builder\' hacia la etapa final?',
                'options' => [
                    'a' => 'COPY --from=builder /app/vendor /var/www/vendor',
                    'b' => 'IMPORT /app/vendor',
                    'c' => 'CLONE http://builder/vendor',
                    'd' => 'RUN download-builder-vendor',
                ],
                'correct' => 'a',
                'explanation' => 'La bandera --from=<etapa> en la instruccion COPY permite extraer artefactos especificos de una etapa anterior sin arrastrar las dependencias intermedias que los crearon.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que es obligatorio agregar la directiva USER (por ejemplo, USER www-data) en la imagen de produccion?',
                'options' => [
                    'a' => 'Para evitar que el contenedor consuma bateria en laptops.',
                    'b' => 'Para garantizar que el proceso PHP-FPM no se ejecute con privilegios de root (UID 0), previniendo que una vulnerabilidad comprometa el host subyacente.',
                    'c' => 'Porque Linux no permite crear archivos sin especificar un usuario en mayusculas.',
                    'd' => 'Porque Nginx no soporta peticiones HTTP provenientes del usuario root.',
                ],
                'correct' => 'b',
                'explanation' => 'Si un contenedor corre como root y se descubre una vulnerabilidad de escape de contenedor (container escape), el atacante obtiene privilegios de root sobre el servidor anfitrion completo.',
            ],
        ],
    ],
];
