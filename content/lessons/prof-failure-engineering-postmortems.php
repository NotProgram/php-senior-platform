<?php

declare(strict_types=1);

return [
    'slug' => 'prof-failure-engineering-postmortems',
    'title' => 'Failure Engineering, Resiliencia & Blameless Postmortems',
    'module' => 'Flujo Profesional & Liderazgo',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En sistemas distribuidos en producción, el fallo no es una posibilidad remota: es una certeza matemática. Failure Engineering diseña sistemas que toleran la caída de dependencias externas (bases de datos, APIs de terceros) y establece una cultura de aprendizaje sin culpables (Blameless Postmortems).',
        'problem' => 'Caídas en cascada donde un microservicio lento congela todos los workers de PHP-FPM, y una cultura tóxica de buscar a "quién tuvo la culpa" tras un incidente, lo que provoca que los ingenieros oculten problemas por miedo a represalias.',
        'solution' => 'Implementar patrones de resiliencia (Timeouts agresivos, Circuit Breakers, Bulkheads, Fallbacks) y ejecutar revisiones de incidentes enfocadas en fallos de los procesos y salvaguardas sistémicas, nunca en errores individuales.',
    ],
    'internals' => [
        'title' => 'Patrones de Resiliencia y Estructura de un Postmortem',
        'steps' => [
            [
                'phase' => '1. Circuit Breaker Pattern',
                'description' => 'Estados Closed (normal), Open (falla rápida sin llamar al servicio externo) y Half-Open (prueba gradual de recuperación).',
            ],
            [
                'phase' => '2. Exponential Backoff con Jitter',
                'description' => 'Reintentos con retardo exponencial más una variación aleatoria (jitter) para evitar el efecto de manada (thundering herd).',
            ],
            [
                'phase' => '3. Línea de Tiempo del Incidente (UTC)',
                'description' => 'Registro cronológico exacto: detección, escalado, mitigación temporal y resolución definitiva.',
            ],
            [
                'phase' => '4. Causa Raíz Sistémica (Los 5 Porqués)',
                'description' => 'Profundizar hasta entender qué salvaguarda o prueba automática faltó en el proceso de entrega.',
            ],
            [
                'phase' => '5. Acciones Preventivas con Responsables',
                'description' => 'Creación de tickets concretos en el backlog para evitar que la misma clase de incidente pueda volver a ocurrir.',
            ],
        ],
    ],
    'senior_mindset' => [
        'thought_process' => 'Si un desarrollador comete un error humano que tumba producción, el problema no es el desarrollador: el problema es que el sistema permitió que un solo humano pudiera tumbar producción sin que los tests, el linter, el staging o el despliegue canary lo impidieran.',
        'critical_questions' => [
            '¿Si la pasarela de pagos tarda 10 segundos en responder, nuestro servidor colapsa por acumulación de procesos o responde un error controlado en 800ms?',
            '¿Tenemos monitoreo y alertas automáticas o nos enteramos de las caídas porque los clientes nos escriben en redes sociales?',
            '¿Las acciones derivadas del postmortem están asignadas y priorizadas en el sprint actual?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Una API de tipo de cambio de divisas externa caída provoca una pantalla blanca en toda la tienda online.',
        'junior' => [
            'approach' => 'Llama a file_get_contents() o curl síncrono en cada carga de página sin timeout especificado.',
            'flaws' => [
                'Cuando la API externa se ralentiza, cada petición web en PHP queda congelada 60 segundos esperando el socket TCP.',
                'Se agota el pool de workers de PHP-FPM en 30 segundos.',
                'Toda la plataforma cae, incluyendo páginas que ni siquiera usan divisas.',
            ],
        ],
        'senior' => [
            'approach' => 'Aplica Circuit Breaker con timeout de 500ms y un Fallback a la última tasa de cambio guardada en la caché de Redis con TTL de 24 horas.',
            'rationale' => [
                'La tienda online sigue vendiendo a la última tasa conocida sin degradación para el usuario.',
                'Los workers de PHP-FPM responden en 15ms.',
                'El sistema alerta automáticamente al equipo de operaciones sobre la caída del proveedor.',
            ],
            'trade_offs' => [
                'Puede haber una pequeña discrepancia en centavos si la tasa de cambio fluctúa drásticamente durante la caída.',
            ],
        ],
    ],
    'exercise' => [
        'title' => 'Reto: Circuit Breaker Básico en PHP 8.4',
        'objective' => 'Implementar una máquina de estados para un Circuit Breaker con protección contra fallos consecutivos.',
        'instructions' => 'Completa la clase CircuitBreaker para cambiar de estado CLOSED a OPEN tras alcanzar el umbral de fallos.',
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Challenge;

class CircuitBreaker
{
    private int $failureCount = 0;
    private string $state = \'CLOSED\';

    public function __construct(private readonly int $failureThreshold = 3) {}

    public function recordFailure(): void
    {
        // TODO: Incrementa fallos y pasa a OPEN si supera el umbral
    }

    public function recordSuccess(): void
    {
        // TODO: Reinicia contador y vuelve a CLOSED
    }

    public function isCallAllowed(): bool
    {
        return $this->state !== \'OPEN\';
    }

    public function getState(): string
    {
        return $this->state;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Challenge;

class CircuitBreaker
{
    private int $failureCount = 0;
    private string $state = \'CLOSED\';

    public function __construct(private readonly int $failureThreshold = 3) {}

    public function recordFailure(): void
    {
        $this->failureCount++;
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = \'OPEN\';
        }
    }

    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = \'CLOSED\';
    }

    public function isCallAllowed(): bool
    {
        return $this->state !== \'OPEN\';
    }

    public function getState(): string
    {
        return $this->state;
    }
}
',
        'explanation' => 'Un Circuit Breaker evita el desperdicio de recursos al fallar rápido (Fast-Fail) cuando una dependencia ya demostró estar caída.',
    ],
    'quiz' => [
        'title' => 'Evaluación: Resiliencia y Blameless Postmortems',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cuál es el principio cardinal de un Blameless Postmortem promovido por Google SRE?',
                'options' => [
                    'a' => 'Encontrar al culpable y despedirlo para dar ejemplo.',
                    'b' => 'Asumir que las personas tuvieron buenas intenciones con la información disponible en ese momento; el objetivo es reparar los procesos, tests y defensas del sistema para hacer imposible que el error se repita.',
                    'c' => 'No escribir nada para evitar litigios legales.',
                    'd' => 'Culpar siempre al proveedor de la nube.',
                ],
                'correct' => 'b',
                'explanation' => 'Si los ingenieros temen ser castigados, ocultarán los incidentes o no compartirán información clave. La cultura sin culpas fomenta la transparencia y la solidez técnica.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué se agrega "Jitter" (variación aleatoria) a la estrategia de Exponential Backoff en los reintentos de red?',
                'options' => [
                    'a' => 'Para cifrar las comunicaciones TCP.',
                    'b' => 'Para evitar el efecto de "Thundering Herd" (manada atronadora), desincronizando los reintentos de miles de clientes para no saturar el servidor que intenta recuperarse.',
                    'c' => 'Porque los estándares RFC lo exigen obligatoriamente.',
                    'd' => 'Para que el código funcione en Windows.',
                ],
                'correct' => 'b',
                'explanation' => 'Si 10,000 clientes reintentan exactamente a los 2, 4 y 8 segundos, envían pulsos masivos sincronizados que vuelven a tirar el servidor en cada intervalo. Jitter dispersa los reintentos uniformemente.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Cuál es el propósito del patrón "Bulkhead" (mamparas de barco) en la arquitectura de software?',
                'options' => [
                    'a' => 'Hacer que las bases de datos sean resistentes al agua.',
                    'b' => 'Aislar componentes críticos y sus pools de recursos (hilos, memoria, conexiones) para que el fallo o saturación de un componente secundario no hunda todo el sistema.',
                    'c' => 'Permitir que Symfony ejecute código C++.',
                    'd' => 'Monitorear la temperatura del datacenter.',
                ],
                'correct' => 'b',
                'explanation' => 'Al igual que los compartimentos estancos de un barco evitan que se hunda por una vía de agua localizada, el Bulkhead previene la propagación del colapso.',
            ],
        ],
    ],
];
