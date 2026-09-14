<?php

declare(strict_types=1);

return [
    'slug' => 'arch-microservices-tradeoffs',
    'title' => 'Microservicios vs Monolito: Matriz de Decision y Costos',
    'module' => 'architecture',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En la decada de 2010, muchas empresas adoptaron microservicios con la promesa de resolver problemas de escalabilidad organizativa y tecnica. Sin embargo, para la mayoria de los equipos de ingenieria, el resultado fue el antipatron mas temido de la industria: el Monolito Distribuido. Un sistema con todas las desventajas de un monolito (acoplamiento funcional, despliegues coordinados en cascada) combinado con todas las pesadillas de los sistemas distribuidos (latencia de red, fallos parciales, consistencia eventual, transacciones distribuidas y necesidad de trazabilidad distribuida compleja).

Un desarrollador Senior comprende la Primera Ley de los Objetos Distribuidos formulada por Martin Fowler: \'No distribuyas tus objetos\'. Los microservicios no son un objetivo de diseno; son una estrategia de organizacion empresarial para companias con cientos de ingenieros que ya no caben coordinandose en un solo repositorio. En esta leccion construimos una matriz cuantitativa de evaluacion para auditar la preparacion tecnica y organizacional de un proyecto antes de decidir si adoptar microservicios o consolidar un Monolito Modular.',
        'problem' => 'En la decada de 2010, muchas empresas adoptaron microservicios con la promesa de resolver problemas de escalabilidad organizativa y tecnica. Sin embargo, para la mayoria de los equipos de ingenieria, el resultado fue el antipatron mas temido de la industria: el Monolito Distribuido. Un sistema con todas las desventajas de un monolito (acoplamiento funcional, despliegues coordinados en cascada) combinado con todas las pesadillas de los sistemas distribuidos (latencia de red, fallos parciales, consistencia eventual, transacciones distribuidas y necesidad de trazabilidad distribuida compleja).',
        'solution' => 'Un desarrollador Senior comprende la Primera Ley de los Objetos Distribuidos formulada por Martin Fowler: \'No distribuyas tus objetos\'. Los microservicios no son un objetivo de diseno; son una estrategia de organizacion empresarial para companias con cientos de ingenieros que ya no caben coordinandose en un solo repositorio. En esta leccion construimos una matriz cuantitativa de evaluacion para auditar la preparacion tecnica y organizacional de un proyecto antes de decidir si adoptar microservicios o consolidar un Monolito Modular.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Cocina de Restaurante vs La Flota de Food Trucks',
        'concept' => 'Imagina como opera un restaurante de comida gourmet:
1. EL MONOLITO MODULAR (La Gran Cocina): En un unico salon espacioso, la estacion de pastas, la estacion de carnes y la estacion de postres estan separadas por mesadas de acero inoxidable (modulos claros). Cuando el plato principal esta listo, el chef le extiende la bandeja al mozo en su propia mano en 0.5 segundos (llamada a metodo en memoria). No hay trafico, no hay lluvia y los ingredientes se comparten en una despensa central.
2. LOS MICROSERVICIOS (La Flota de Food Trucks): Cada estacion se traslada a una furgoneta independiente estacionada en esquinas separadas de la ciudad. Para ensamblar un pedido, el camion de hamburguesas debe enviar un repartidor en motocicleta por el trafico urbano para recoger el pan en el camion de panaderia (llamada RPC por red con latencia y timeouts). Si la moto tiene un pinchazo (falla de red), el cliente se queda sin comer. Se requiere GPS en cada moto (Distributed Tracing) y un centro de control con pantallas gigantes (Kubernetes, Service Mesh, Observabilidad).

Si tu restaurante atiende a 50 mesas, tener 10 furgonetas en la calle es una locura economica. Solo tiene sentido si tienes 200 cocineros y necesitas despachar simultaneamente en 10 ciudades distintas.',
        'ascii_diagram' => 'COSTO FISICO: LLAMADA EN MEMORIA VS LLAMADA POR RED (RPC):

LLAMADA EN MEMORIA (Monolito / Monolito Modular):
Thread CPU ---> [ $orderService->process() ] ---> 2 nanosegundos (0.000002 ms)
                  - Fiabilidad: 100% (cero fallos de red)
                  - Transaccion ACID: 100% garantizada

LLAMADA DISTRIBUIDA (Microservicios via HTTP / gRPC):
Host A ---> [ TCP Handshake ] ---> [ TLS Cryptography ] ---> [ Red / Switch ] ---> Host B
               |                           |                       |
            1.5 ms                       2.0 ms                  8.5 ms
            
Total por salto: ~12 a 25 milisegundos (¡6,000,000 de veces mas lento!)
Riesgos: Packet loss, DNS timeout, Circuit Breaker abierto, Desincronizacion de datos.
',
        'analogy' => 'Imagina como opera un restaurante de comida gourmet:
1. EL MONOLITO MODULAR (La Gran Cocina): En un unico salon espacioso, la estacion de pastas, la estacion de carnes y la estacion de postres estan separadas por mesadas de acero inoxidable (modulos claros). Cuando el plato principal esta listo, el chef le extiende la bandeja al mozo en su propia mano en 0.5 segundos (llamada a metodo en memoria). No hay trafico, no hay lluvia y los ingredientes se comparten en una despensa central.
2. LOS MICROSERVICIOS (La Flota de Food Trucks): Cada estacion se traslada a una furgoneta independiente estacionada en esquinas separadas de la ciudad. Para ensamblar un pedido, el camion de hamburguesas debe enviar un repartidor en motocicleta por el trafico urbano para recoger el pan en el camion de panaderia (llamada RPC por red con latencia y timeouts). Si la moto tiene un pinchazo (falla de red), el cliente se queda sin comer. Se requiere GPS en cada moto (Distributed Tracing) y un centro de control con pantallas gigantes (Kubernetes, Service Mesh, Observabilidad).

Si tu restaurante atiende a 50 mesas, tener 10 furgonetas en la calle es una locura economica. Solo tiene sentido si tienes 200 cocineros y necesitas despachar simultaneamente en 10 ciudades distintas.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En un sistema distribuido, la falacia comun es asumir que la red es confiable y que la latencia es cero.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Cuando una peticion de usuario atraviesa 4 microservicios en cadena (Gateway -> Orders -> Inventory -> Billing), la latencia P99 se acumula exponencialmente: si cada servicio tiene un P99 de 12ms, el usuario final sufre al menos 50ms solo en sobrecarga de red sin contar el tiempo de procesamiento.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Ademas, garantizar atomicidad requiere implementar el patron Saga mediante orquestacion o coreografia con eventos asincronos en colas de RabbitMQ o Kafka, agregando enorme complejidad para compensar transacciones fallidas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'La Ley de Conway establece que las organizaciones disenan sistemas que son copias exactas de sus canales de comunicacion. Si un equipo de 8 ingenieros adopta 15 microservicios, cada ingeniero debe mantener 2 servicios, gestionar 15 pipelines de CI/CD independientes y depurar logs dispersos en Grafana/Jaeger, destruyendo su productividad. La regla general de la industria es: al menos un equipo de 5 a 8 ingenieros por cada microservicio independiente.',
        ],
    ],
    'video' => [
        'title' => 'When To Use Microservices (And When Not To!)',
        'speaker' => 'Sam Newman & Martin Fowler',
        'youtube_id' => 'GBTdnfD6s5Q',
        'duration' => '43 min',
        'description' => 'Sam Newman (autor de Building Microservices) y Martin Fowler debaten en GOTO Conference sobre los verdaderos requisitos organizacionales, la prima de complejidad (Microservice Premium) y por que el monolito modular debe ser siempre la opcion predeterminada.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Architecture\\Decision;

use InvalidArgumentException;

final readonly class ArchitectureTradeoffMatrix
{
    /**
     * Evalua cuantitativamente la viabilidad de adoptar microservicios frente a un monolito modular.
     *
     * @return array{recommended_architecture: string, estimated_complexity_score: int, requires_distributed_tracing: bool, reasoning: string}
     */
    public function evaluateReadiness(
        int $engineeringTeamSize,
        int $boundedContextsCount,
        bool $requiresIndependentDeployments,
        float $monthlyDevOpsBudgetUsd
    ): array {
        if ($engineeringTeamSize <= 0) {
            throw new InvalidArgumentException(\'El tamano del equipo de ingenieria debe ser mayor a 0.\');
        }

        if ($boundedContextsCount <= 0) {
            throw new InvalidArgumentException(\'El conteo de contextos delimitados debe ser mayor a 0.\');
        }

        if ($monthlyDevOpsBudgetUsd < 0.0) {
            throw new InvalidArgumentException(\'El presupuesto DevOps mensual no puede ser negativo.\');
        }

        // Criterios de Conway y umbrales economicos
        $isTeamReady = $engineeringTeamSize >= 20;
        $isBudgetAdequate = $monthlyDevOpsBudgetUsd >= 5000.0;
        $hasSufficientDomains = $boundedContextsCount >= 3;

        if ($isTeamReady && $isBudgetAdequate && $hasSufficientDomains && $requiresIndependentDeployments) {
            return [
                \'recommended_architecture\' => \'MICROSERVICES\',
                \'estimated_complexity_score\' => 85,
                \'requires_distributed_tracing\' => true,
                \'reasoning\' => \'El equipo cuenta con masa critica, presupuesto y dominios suficientes para justificar la sobrecarga operativa.\',
            ];
        }

        return [
            \'recommended_architecture\' => \'MODULAR_MONOLITH\',
            \'estimated_complexity_score\' => 30,
            \'requires_distributed_tracing\' => false,
            \'reasoning\' => \'El equipo debe adoptar un Monolito Modular: maximiza velocidad de entrega y elimina la sobrecarga de red y transacciones distribuidas.\',
        ];
    }

    public function calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float
    {
        if ($serviceHops < 0) {
            throw new InvalidArgumentException(\'El numero de saltos de red no puede ser negativo.\');
        }

        return round($serviceHops * $p99HopLatencyMs, 2);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior cree que utilizar microservicios es sinonimo de estatus profesional y modernidad. El desarrollador Senior ve a los microservicios como una costosa medida de escalabilidad organizacional: los evita a toda costa mientras el negocio quepa en un Monolito Modular optimizado, protegiendo a la empresa de costos astronomicos de nube y degradaciones por latencia de red.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Propone dividir una aplicacion de 3 desarrolladores en 8 microservicios con Docker y Kubernetes.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Propone un Monolito Modular con limites claros de Bounded Contexts y base de datos relacional optimizada con indices, entregando valor 10 veces mas rapido.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'La Primera Ley de los Objetos Distribuidos',
            'source' => 'Patterns of Enterprise Application Architecture',
            'quote' => 'Primera ley del diseno distribuido: No distribuyas tus objetos a traves de la red a menos que sea estrictamente necesario por razones organizativas de escala extrema.',
            'author' => 'Martin Fowler',
            'explanation' => 'El costo de latencia y fallo parcial siempre supera la comodidad teorica del desacoplamiento fisico.',
        ],
        [
            'topic' => 'La Prima de Microservicios',
            'source' => 'Monolith First / Fowler Bliki',
            'quote' => 'Casi todos los casos exitosos de microservicios comenzaron con un monolito que se volvio demasiado grande y fue descompuesto gradualmente. Casi todos los que comenzaron desde cero con microservicios terminaron en un desastre.',
            'author' => 'Martin Fowler',
            'explanation' => 'No puedes trazar limites de servicio correctos sin conocer primero el dominio maduro.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: MonolithFirst Strategy',
            'url' => 'https://martinfowler.com/bliki/MonolithFirst.html',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Sam Newman: Back to the Monolith? - Trade-offs Analysis',
            'url' => 'https://samnewman.io/blog/2023/05/23/back-to-the-monolith/',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Matriz Cuantitativa de Decision de Arquitectura (ArchitectureTradeoffMatrix)',
        'objective' => 'Implementar ArchitectureTradeoffMatrix para evaluar cuantitativamente si un proyecto debe adoptar un Monolito Modular o Microservicios segun el tamano del equipo, dominios delimitados y presupuesto, y calcular el impacto acumulado de latencia de red P99.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Architecture\\Decision.
2. Implementa la clase ArchitectureTradeoffMatrix.
3. Implementa evaluateReadiness(int $engineeringTeamSize, int $boundedContextsCount, bool $requiresIndependentDeployments, float $monthlyDevOpsBudgetUsd): array.
4. Valida que $engineeringTeamSize > 0, $boundedContextsCount > 0 y $monthlyDevOpsBudgetUsd >= 0.0; si no, lanza \\InvalidArgumentException.
5. Si $engineeringTeamSize >= 20, $boundedContextsCount >= 3, $requiresIndependentDeployments === true y $monthlyDevOpsBudgetUsd >= 5000.0, recomienda \'MICROSERVICES\'; en cualquier otro caso, retorna \'MODULAR_MONOLITH\'.
6. Implementa calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float validando $serviceHops >= 0 (InvalidArgumentException) y retornando el calculo redondeado a 2 decimales.',
        'filename' => 'src/Architecture/Decision/ArchitectureTradeoffMatrix.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida invariantes de entrada en evaluateReadiness arrojando InvalidArgumentException ante valores no positivos o presupuestos negativos.',
                'Paso 2: Evalua las 4 condiciones requeridas para microservicios.',
                'Paso 3: Retorna el array con \'recommended_architecture\', \'estimated_complexity_score\' y \'requires_distributed_tracing\'.',
                'Paso 4: En calculateNetworkOverheadMs, valida que los saltos no sean negativos y retorna round($serviceHops * $p99HopLatencyMs, 2).',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Microservicios solo es recomendable si equipo >= 20, contextos >= 3, despliegues independientes requeridos y presupuesto >= 5000 USD.',
            ],
            [
                'text' => '[Pista 2: Estructura] Retorna \'MODULAR_MONOLITH\' o \'MICROSERVICES\' bajo la clave \'recommended_architecture\'.',
            ],
            [
                'text' => '[Pista 3: Snippet] public function calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float { ... return round($serviceHops * $p99HopLatencyMs, 2); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Architecture\\Decision;

use InvalidArgumentException;

class ArchitectureTradeoffMatrix
{
    /**
     * @return array{recommended_architecture: string, estimated_complexity_score: int, requires_distributed_tracing: bool}
     */
    public function evaluateReadiness(
        int $engineeringTeamSize,
        int $boundedContextsCount,
        bool $requiresIndependentDeployments,
        float $monthlyDevOpsBudgetUsd
    ): array {
        // TODO: Validar que teamSize > 0, contexts > 0, budget >= 0.0 (lanzar InvalidArgumentException)
        // TODO: Evaluar condiciones: team >= 20, contexts >= 3, independentDeployments === true, budget >= 5000.0
        // TODO: Si se cumplen todas, retornar MICROSERVICES; en caso contrario MODULAR_MONOLITH
        return [];
    }

    public function calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float
    {
        // TODO: Validar que $serviceHops >= 0 (lanzar InvalidArgumentException)
        // TODO: Retornar multiplicacion redondeada a 2 decimales
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Architecture\\Decision;

use InvalidArgumentException;

class ArchitectureTradeoffMatrix
{
    /**
     * @return array{recommended_architecture: string, estimated_complexity_score: int, requires_distributed_tracing: bool}
     */
    public function evaluateReadiness(
        int $engineeringTeamSize,
        int $boundedContextsCount,
        bool $requiresIndependentDeployments,
        float $monthlyDevOpsBudgetUsd
    ): array {
        if ($engineeringTeamSize <= 0) {
            throw new InvalidArgumentException(\'El tamano del equipo de ingenieria debe ser mayor a 0.\');
        }

        if ($boundedContextsCount <= 0) {
            throw new InvalidArgumentException(\'El conteo de contextos delimitados debe ser mayor a 0.\');
        }

        if ($monthlyDevOpsBudgetUsd < 0.0) {
            throw new InvalidArgumentException(\'El presupuesto DevOps mensual no puede ser negativo.\');
        }

        $canAdoptMicroservices = (
            $engineeringTeamSize >= 20
            && $boundedContextsCount >= 3
            && $requiresIndependentDeployments === true
            && $monthlyDevOpsBudgetUsd >= 5000.0
        );

        if ($canAdoptMicroservices) {
            return [
                \'recommended_architecture\' => \'MICROSERVICES\',
                \'estimated_complexity_score\' => 85,
                \'requires_distributed_tracing\' => true,
            ];
        }

        return [
            \'recommended_architecture\' => \'MODULAR_MONOLITH\',
            \'estimated_complexity_score\' => 30,
            \'requires_distributed_tracing\' => false,
        ];
    }

    public function calculateNetworkOverheadMs(int $serviceHops, float $p99HopLatencyMs = 12.0): float
    {
        if ($serviceHops < 0) {
            throw new InvalidArgumentException(\'El numero de saltos de red no puede ser negativo.\');
        }

        return round($serviceHops * $p99HopLatencyMs, 2);
    }
}
',
        'explanation' => 'La clase ArchitectureTradeoffMatrix formaliza la toma de decisiones tecnicas en la organizacion. Evita que equipos pequenos sin presupuesto de infraestructura caigan en la trampa del monolito distribuido, recomendando el Monolito Modular como la arquitectura mas eficiente y rentable hasta alcanzar la masa critica necesaria.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Microservicios vs Monolito: Matriz de Decision y Costos',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la consecuencia primordial de dividir un sistema en microservicios cuando el equipo de desarrollo es menor a 10 ingenieros?',
                'options' => [
                    'a' => 'El servidor web Apache se rehusa a compilar codigo PHP.',
                    'b' => 'El equipo gasta mas tiempo manteniendo infraestructura, pipelines de CI/CD, contratos de red y depurando caidas transitorias que desarrollando valor de negocio (Monolito Distribuido).',
                    'c' => 'La base de datos MySQL desactiva los indices B-Tree de forma automatica.',
                    'd' => 'Se vuelve obligatorio reescribir todo el backend en lenguaje ensamblador.',
                ],
                'correct' => 'b',
                'explanation' => 'Los microservicios imponen un costo fijo operativo muy alto (la prima de microservicios). En equipos pequenos, este costo absorbe casi toda la capacidad de ingenieria.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por que las transacciones tradicionales ACID (BEGIN ... COMMIT) dejan de funcionar de manera simple entre multiples microservicios?',
                'options' => [
                    'a' => 'Porque cada microservicio posee su propia base de datos aislada, lo que imposibilita una transaccion ACID local y obliga a emplear consistencia eventual o patrones como Saga.',
                    'b' => 'Porque el protocolo HTTP prohibe enviar mas de una consulta SQL en una peticion.',
                    'c' => 'Porque Docker no admite conexiones relacionales a traves de redes virtuales.',
                    'd' => 'Porque la especificacion PSR-7 impide usar transacciones.',
                ],
                'correct' => 'a',
                'explanation' => 'El principio de oro de los microservicios es \'Database per Service\'. Al no compartir la misma base de datos fisica, no existe un motor comun que garantice el commit o rollback atomico entre los dos sistemas.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que establece la Ley de Conway en relacion a la arquitectura de software?',
                'options' => [
                    'a' => 'Que todo software escrito en PHP debe migrarse a microservicios tras dos anos de desarrollo.',
                    'b' => 'Que las organizaciones que disenan sistemas estan constrenidas a producir arquitecturas que son copias exactas de las estructuras de comunicacion de la propia organizacion.',
                    'c' => 'Que la velocidad de los microprocesadores se duplica cada 18 meses.',
                    'd' => 'Que el uso de Docker reduce la latencia de red a 0 milisegundos.',
                ],
                'correct' => 'b',
                'explanation' => 'Si tienes un equipo centralizado de 5 personas, disenar 20 microservicios independientes fracasara porque la estructura del equipo no coincide con los limites de los sistemas.',
            ],
        ],
    ],
];
