<?php

declare(strict_types=1);

return [
    'slug' => 'devops-ci-cd-github-actions',
    'title' => 'CI/CD Pipeline con GitHub Actions, PHPStan & PHPUnit',
    'module' => 'devops',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En un equipo de ingenieria de alto rendimiento, la calidad del software no es un acto de fe; es una garantia matematica impuesta por un pipeline de Integracion Continua (CI) automatizado. Ningun commit puede integrarse a la rama principal (main/master) sin haber superado una bateria rigurosa de chequeos estaticos y dinamicos en cada Pull Request.

Un pipeline profesional en GitHub Actions se estructura en compuertas de calidad (Quality Gates) ordenadas segun su costo computacional:
1. Linter y Estilo de Codigo (PHP-CS-Fixer): chequeo sintactico inmediato en 5 segundos.
2. Analisis Estatico Estricto (PHPStan nivel 8 o Max): verificacion formal de tipos e invariantes en 15 segundos.
3. Linter del Framework: bin/console lint:container y bin/console lint:twig para validar cableado de servicios.
4. Pruebas Automatizadas (PHPUnit): ejecucion de la piramide de pruebas con base de datos en memoria.

En esta leccion implementamos un ejecutor de pipelines de CI (CiPipelineRunner) que registra pasos declarativos, captura fallos mediante Throwable y genera un reporte estructurado de ejecucion.',
        'problem' => 'En un equipo de ingenieria de alto rendimiento, la calidad del software no es un acto de fe; es una garantia matematica impuesta por un pipeline de Integracion Continua (CI) automatizado. Ningun commit puede integrarse a la rama principal (main/master) sin haber superado una bateria rigurosa de chequeos estaticos y dinamicos en cada Pull Request.',
        'solution' => 'Un pipeline profesional en GitHub Actions se estructura en compuertas de calidad (Quality Gates) ordenadas segun su costo computacional:
1. Linter y Estilo de Codigo (PHP-CS-Fixer): chequeo sintactico inmediato en 5 segundos.
2. Analisis Estatico Estricto (PHPStan nivel 8 o Max): verificacion formal de tipos e invariantes en 15 segundos.
3. Linter del Framework: bin/console lint:container y bin/console lint:twig para validar cableado de servicios.
4. Pruebas Automatizadas (PHPUnit): ejecucion de la piramide de pruebas con base de datos en memoria.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Control de Seguridad de un Aeropuerto Internacional',
        'concept' => 'Piensa en los controles sucesivos que atraviesa un pasajero antes de abordar un vuelo trasatlantico:
1. PRIMERA COMPUERTA (Chequeo Rapido de Pasaporte): En la entrada, un agente revisa si tienes pasaporte valido (Linter de sintaxis). Toma 5 segundos. Si no tienes boleto, te rechazan sin gastar mas tiempo.
2. SEGUNDA COMPUERTA (Detector de Metales y Escaner): Pasas por la maquina de rayos X para verificar que no lleves objetos prohibidos (Analisis estatico con PHPStan). Si hay algo sospechoso, la alarma suena.
3. TERCERA COMPUERTA (Inspeccion de Equipaje): Se abre la maleta y se valida que los liquidos y medicamentos cumplan las normativas (Pruebas unitarias y de integracion con PHPUnit).

Si cualquiera de las compuertas falla, el pasajero no sube al avion (el Pull Request queda bloqueado). No se permite abordar el avion confiando en que el pasajero \'promete portarse bien\' (el merge manual sin CI).',
        'ascii_diagram' => 'PIPELINE DE CALIDAD CONTINUA (GitHub Actions Workflow):

git push origin feat/new-payment
               |
               v Dispara GitHub Actions Runner
+-------------------------------------------------------------+
| STEP 1: PHP-CS-Fixer / Linter (Estilo y formato)            |
|   - ¿Sintaxis valida y tipado declare(strict_types=1)?      |
+-------------------------------------------------------------+
               | PASA
               v
+-------------------------------------------------------------+
| STEP 2: PHPStan Nivel 8 / Max (Analisis Estatico)           |
|   - ¿Metodos devuelven tipos correctos? ¿Nulos controlados? |
+-------------------------------------------------------------+
               | PASA
               v
+-------------------------------------------------------------+
| STEP 3: Symfony Container & Twig Lint                       |
|   - bin/console lint:container (Autowiring valido)          |
+-------------------------------------------------------------+
               | PASA
               v
+-------------------------------------------------------------+
| STEP 4: PHPUnit Test Suite (Piramide de Pruebas)            |
|   - 100% de aserciones verdes en entorno test               |
+-------------------------------------------------------------+
               | PASA
               v
 [ Pull Request Aprobado para Merge ] ---> Despliegue Continuo
',
        'analogy' => 'Piensa en los controles sucesivos que atraviesa un pasajero antes de abordar un vuelo trasatlantico:
1. PRIMERA COMPUERTA (Chequeo Rapido de Pasaporte): En la entrada, un agente revisa si tienes pasaporte valido (Linter de sintaxis). Toma 5 segundos. Si no tienes boleto, te rechazan sin gastar mas tiempo.
2. SEGUNDA COMPUERTA (Detector de Metales y Escaner): Pasas por la maquina de rayos X para verificar que no lleves objetos prohibidos (Analisis estatico con PHPStan). Si hay algo sospechoso, la alarma suena.
3. TERCERA COMPUERTA (Inspeccion de Equipaje): Se abre la maleta y se valida que los liquidos y medicamentos cumplan las normativas (Pruebas unitarias y de integracion con PHPUnit).

Si cualquiera de las compuertas falla, el pasajero no sube al avion (el Pull Request queda bloqueado). No se permite abordar el avion confiando en que el pasajero \'promete portarse bien\' (el merge manual sin CI).',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En un runner de CI, los pasos deben ejecutarse en un entorno determinista.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'El ejecutor (CiPipelineRunner) almacena closures de comprobacion asociados a un nombre descriptivo.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Durante la ejecucion en run(), itera sobre los pasos en orden de definicion envolviendo cada llamada en un bloque try-catch capturando Throwable (para atrapar tanto Excepciones como Errores fatales de PHP).',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => 'Ante el primer fallo, el pipeline se detiene (Fail-Fast), reportando el nombre del paso fallido y el mensaje de error.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Buenas practicas de GitHub Actions para Symfony: 1. Cache de Composer: utilizar actions/cache para preservar el directorio ~/.composer/cache, reduciendo el tiempo de build en un 70%. 2. Matriz de versiones: probar contra multiples versiones de PHP (PHP 8.3 y PHP 8.4) para anticipar incompatibilidades. 3. Concurrencia: cancelar automaticamente ejecuciones previas en el mismo PR si se realiza un nuevo push mediante concurrency.',
        ],
    ],
    'video' => [
        'title' => 'GitHub Actions Tutorial - Basic Concepts and CI/CD Pipeline with Docker',
        'speaker' => 'TechWorld with Nana',
        'youtube_id' => 'R8_veQiYBjI',
        'duration' => '40 min',
        'description' => 'Nana Janashia explica los conceptos fundamentales de GitHub Actions: workflows, triggers, runners, steps, variables de entorno y como construir un pipeline completo de CI/CD para automatizar pruebas y despliegues.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\CiCd;

use Closure;
use InvalidArgumentException;
use Throwable;

final class CiPipelineRunner
{
    /**
     * @var array<string, Closure(): bool>
     */
    private array $steps = [];

    public function addStep(string $name, Closure $check): void
    {
        $cleanName = trim($name);
        if ($cleanName === \'\') {
            throw new InvalidArgumentException(\'El nombre del paso del pipeline no puede estar vacio.\');
        }

        $this->steps[$cleanName] = $check;
    }

    /**
     * @return array{passed: bool, total_steps: int, executed_steps: int, failed_step: ?string, error: ?string}
     */
    public function run(): array
    {
        $total = count($this->steps);
        $executed = 0;

        foreach ($this->steps as $name => $check) {
            $executed++;
            try {
                $result = $check();
                if ($result === false) {
                    return [
                        \'passed\' => false,
                        \'total_steps\' => $total,
                        \'executed_steps\' => $executed,
                        \'failed_step\' => $name,
                        \'error\' => sprintf(\'El paso "%s" retorno false.\', $name),
                    ];
                }
            } catch (Throwable $e) {
                return [
                    \'passed\' => false,
                    \'total_steps\' => $total,
                    \'executed_steps\' => $executed,
                    \'failed_step\' => $name,
                    \'error\' => $e->getMessage(),
                ];
            }
        }

        return [
            \'passed\' => true,
            \'total_steps\' => $total,
            \'executed_steps\' => $executed,
            \'failed_step\' => null,
            \'error\' => null,
        ];
    }

    public function reset(): void
    {
        $this->steps = [];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior confia en su memoria: \'prometo que ejecute las pruebas en mi laptop antes de hacer push\'. El desarrollador Senior automatiza la confianza: todo commit pasa por un pipeline implacable de CI en GitHub Actions donde PHPStan, linters y PHPUnit certifican la calidad del codigo antes de que cualquier humano gaste tiempo en code review.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Hace push directo a master sin pasar por pruebas automatizadas, rompiendo produccion a las 6:00 PM.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Configura branch protection en main exigiendo que el pipeline de GitHub Actions este en verde para habilitar el merge.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Integracion Continua',
            'source' => 'Continuous Integration: Improving Software Quality and Reducing Risk',
            'quote' => 'La integracion continua es una practica donde los miembros del equipo integran su trabajo frecuentemente. Cada integracion se verifica mediante una construccion automatizada que incluye pruebas para detectar errores lo antes posible.',
            'author' => 'Paul Duvall, Steve Matyas, Andrew Glover',
            'explanation' => 'Reduce drasticamente el tiempo transcurrido entre la introduccion de un bug y su deteccion.',
        ],
        [
            'topic' => 'Compuertas de Calidad',
            'source' => 'Accelerate: The Science of Lean Software and DevOps',
            'quote' => 'Los equipos de alto desempeno incorporan la calidad dentro del proceso automatizado en lugar de inspeccionarla manualmente al final.',
            'author' => 'Nicole Forsgren, Jez Humble, Gene Kim',
            'explanation' => 'Los pipelines automatizados correlacionan directamente con mayor estabilidad y velocidad de entrega.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'GitHub Actions Documentation',
            'url' => 'https://docs.github.com/es/actions',
            'type' => 'DOCS',
        ],
        [
            'title' => 'PHPStan: PHP Static Analysis Tool',
            'url' => 'https://phpstan.org/',
            'type' => 'TOOL',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Ejecutor de Pipelines de CI con Captura de Excepciones (CiPipelineRunner)',
        'objective' => 'Implementar la clase CiPipelineRunner con addStep(), run() y reset(), validando nombres de paso con InvalidArgumentException, ejecutando los closures con captura de Throwable y retornando las claves passed, total_steps, executed_steps, failed_step y error.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\DevOps\\CiCd.
2. Implementa la clase CiPipelineRunner.
3. Implementa function addStep(string $name, \\Closure $check): void; si $name esta vacio lanza \\InvalidArgumentException.
4. Implementa function run(): array.
5. En run(), itera sobre los pasos registrados contando $executed_steps.
6. Ejecuta cada closure dentro de un bloque try-catch capturando \\Throwable.
7. Si el closure retorna false o arroja una excepcion, detiene la ejecucion y retorna passed => false con failed_step y error.
8. Si todos los pasos pasan, retorna passed => true, failed_step => null y error => null.
9. Implementa function reset(): void vaciando los pasos.',
        'filename' => 'src/DevOps/CiCd/CiPipelineRunner.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array asociativo privado $steps = [].',
                'Paso 2: En addStep, valida trim($name) !== \'\' y almacena el closure.',
                'Paso 3: En run(), calcula $total_steps = count($this->steps).',
                'Paso 4: Envuelve la llamada $check() en try / catch(Throwable $e).',
                'Paso 5: Si falla, retorna inmediatamente el array con passed => false.',
                'Paso 6: Si finaliza el bucle con exito, retorna passed => true.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Un pipeline de CI aplica Fail-Fast: ante el primer paso que falle, se interrumpe el proceso.',
            ],
            [
                'text' => '[Pista 2: Estructura] catch (\\Throwable $e) atrapa tanto Exception como Error (errores fatales de PHP).',
            ],
            [
                'text' => '[Pista 3: Snippet] return [\'passed\' => true, \'total_steps\' => $total, \'executed_steps\' => $executed, \'failed_step\' => null, \'error\' => null];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\CiCd;

use Closure;
use InvalidArgumentException;
use Throwable;

class CiPipelineRunner
{
    /**
     * @var array<string, Closure>
     */
    private array $steps = [];

    public function addStep(string $name, Closure $check): void
    {
        // TODO: Validar $name no vacio (lanzar InvalidArgumentException)
        // TODO: Registrar paso
    }

    public function run(): array
    {
        // TODO: Iterar pasos y capturar Throwable
        // TODO: Ante fallo retornar passed => false con failed_step y error
        // TODO: Ante exito retornar passed => true
        return [];
    }

    public function reset(): void
    {
        // TODO: Vaciar pasos
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\CiCd;

use Closure;
use InvalidArgumentException;
use Throwable;

class CiPipelineRunner
{
    /**
     * @var array<string, Closure>
     */
    private array $steps = [];

    public function addStep(string $name, Closure $check): void
    {
        $clean = trim($name);
        if ($clean === \'\') {
            throw new InvalidArgumentException(\'El nombre del paso no puede estar vacio.\');
        }

        $this->steps[$clean] = $check;
    }

    public function run(): array
    {
        $total = count($this->steps);
        $executed = 0;

        foreach ($this->steps as $name => $check) {
            $executed++;
            try {
                $result = $check();
                if ($result === false) {
                    return [
                        \'passed\' => false,
                        \'total_steps\' => $total,
                        \'executed_steps\' => $executed,
                        \'failed_step\' => $name,
                        \'error\' => sprintf(\'Paso "%s" fallo.\', $name),
                    ];
                }
            } catch (Throwable $e) {
                return [
                    \'passed\' => false,
                    \'total_steps\' => $total,
                    \'executed_steps\' => $executed,
                    \'failed_step\' => $name,
                    \'error\' => $e->getMessage(),
                ];
            }
        }

        return [
            \'passed\' => true,
            \'total_steps\' => $total,
            \'executed_steps\' => $executed,
            \'failed_step\' => null,
            \'error\' => null,
        ];
    }

    public function reset(): void
    {
        $this->steps = [];
    }
}
',
        'explanation' => 'La clase CiPipelineRunner modela la ejecucion secuencial de compuertas de calidad. Aplica el principio Fail-Fast deteniendo la ejecucion ante la primera falla, captura cualquier excepcion o error mediante Throwable y genera un reporte diagnostico completo.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: CI/CD Pipeline con GitHub Actions, PHPStan & PHPUnit',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el orden logico mas eficiente para organizar los pasos de un pipeline de CI en GitHub Actions?',
                'options' => [
                    'a' => '1. Pruebas E2E lentas, 2. Despliegue a produccion, 3. Revision de sintaxis.',
                    'b' => '1. Linter y formato (5s), 2. Analisis estatico con PHPStan (15s), 3. Linters de framework (10s), 4. Pruebas automatizadas con PHPUnit (30s).',
                    'c' => 'Ejecutar todos los pasos al azar para que el procesador no se acostumbre.',
                    'd' => 'Omitir el analisis estatico si el autor del commit es un desarrollador Senior.',
                ],
                'correct' => 'b',
                'explanation' => 'Los chequeos mas rapidos y baratos deben ejecutarse primero. Si hay un error de sintaxis o de tipado estatico, el pipeline falla en segundos sin gastar minutos arrancando bases de datos de prueba.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Que nivel de analisis de PHPStan se considera el estandar de rigor para software empresarial de alto nivel?',
                'options' => [
                    'a' => 'Nivel 0 (solo revisa sintaxis basica)',
                    'b' => 'Nivel 8 o Max (exige tipado estricto, verifica nulos y tipado generico de colecciones)',
                    'c' => 'Nivel -1 (desactiva todos los avisos)',
                    'd' => 'PHPStan no debe utilizarse en proyectos Symfony',
                ],
                'correct' => 'b',
                'explanation' => 'El nivel 8 de PHPStan fuerza el control de nulos en todas las llamadas a metodos y retorno de funciones, erradicando los temidos errores Call to a member function on null en produccion.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que en PHP es critico que el bloque catch del ejecutor capture \\Throwable y no solo \\Exception?',
                'options' => [
                    'a' => 'Porque Throwable consume menos memoria RAM.',
                    'b' => 'Porque Throwable es la interfaz base en PHP que engloba tanto a \\Exception como a \\Error (errores fatales de tipado TypeError, ParseError, etc.), impidiendo que un error fatal detenga el runner.',
                    'c' => 'Porque Exception esta prohibido en PER Coding Style.',
                    'd' => 'No hay diferencia; son sinonimos identicos.',
                ],
                'correct' => 'b',
                'explanation' => 'En PHP 7+, los errores fatales del motor lanzan instancias de \\Error que implementan \\Throwable pero NO extienden de \\Exception. Capturar Throwable garantiza atrapar cualquier fallo del runtime.',
            ],
        ],
    ],
];
