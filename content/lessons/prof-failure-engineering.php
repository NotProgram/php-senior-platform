<?php

declare(strict_types=1);

return [
    'slug' => 'prof-failure-engineering',
    'title' => 'Failure Engineering: Circuit Breaker, Retries & Idempotencia',
    'module' => 'professional-developer',
    'minutes' => 55,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En sistemas distribuidos y arquitecturas de microservicios modernas, los fallos no son una posibilidad teorica; son una certeza matematica continua. Las redes se congestionan, las pasarelas de pago de terceros sufren caidas temporales y las bases de datos experimentan deadlocks bajo picos de concurrencia. La diferencia entre un sistema fragil y un sistema resiliente radica en como se gestiona la degradacion parcial.

El patron Circuit Breaker (Disyuntor / Interruptor de Circuito, popularizado por Michael Nygard en su obra \'Release It!\') evita que una falla en un servicio dependiente provoque una caida en cascada en toda la plataforma. Opera como el disyuntor termomagnetico de una instalacion electrica:
1. Estado CERRADO (CLOSED): El flujo normal de peticiones pasa hacia el servicio externo. Si se registran fallos sucesivos, se incrementa un contador.
2. Estado ABIERTO (OPEN): Al superar el umbral de fallos tolerados (por ejemplo, 3 fallos seguidos), el disyuntor salta. Las siguientes peticiones fallan inmediatamente (Fail-Fast) sin intentar conectar con el servicio caido, protegiendo los hilos del servidor y permitiendo que la pasarela externa se recupere.',
        'problem' => 'En sistemas distribuidos y arquitecturas de microservicios modernas, los fallos no son una posibilidad teorica; son una certeza matematica continua. Las redes se congestionan, las pasarelas de pago de terceros sufren caidas temporales y las bases de datos experimentan deadlocks bajo picos de concurrencia. La diferencia entre un sistema fragil y un sistema resiliente radica en como se gestiona la degradacion parcial.',
        'solution' => 'El patron Circuit Breaker (Disyuntor / Interruptor de Circuito, popularizado por Michael Nygard en su obra \'Release It!\') evita que una falla en un servicio dependiente provoque una caida en cascada en toda la plataforma. Opera como el disyuntor termomagnetico de una instalacion electrica:
1. Estado CERRADO (CLOSED): El flujo normal de peticiones pasa hacia el servicio externo. Si se registran fallos sucesivos, se incrementa un contador.
2. Estado ABIERTO (OPEN): Al superar el umbral de fallos tolerados (por ejemplo, 3 fallos seguidos), el disyuntor salta. Las siguientes peticiones fallan inmediatamente (Fail-Fast) sin intentar conectar con el servicio caido, protegiendo los hilos del servidor y permitiendo que la pasarela externa se recupere.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Disyuntor Termomagnetico del Cuadro de Luz del Hogar',
        'concept' => 'Imagina la instalacion electrica de tu casa en una noche de tormenta:
1. ESTADO CERRADO (CLOSED - Normal): Los cables conducen electricidad normalmente hacia la nevera, el televisor y las luces. Todo opera en equilibrio.
2. EL CORTOCIRCUITO (Fallo del Proveedor): Un electrodomestico en la cocina sufre una averia y genera un cortocircuito violento. La corriente se dispara a niveles peligrosos.
3. SALTO DEL DISYUNTOR (OPEN - Proteccion): En lugar de dejar que los cables de la pared se sobrecalienten hasta incendiar la casa (el agotamiento de hilos de PHP-FPM que tumba todo el servidor), el disyuntor termomagnetico salta con un \'clac\' instantaneo.
4. EL FAIL-FAST: Mientras la palanca este abajo (OPEN), si intentas encender la luz de la habitacion, ni siquiera fluye corriente. Se protege toda la casa mientras se repara el problema en la cocina.',
        'ascii_diagram' => 'MAQUINA DE ESTADOS DEL PATRON CIRCUIT BREAKER:

           [ Exito / Operacion Normal ]
                     |
                     v
             +---------------+
   +-------->|    CLOSED     |<-------+
   |         +---------------+        |
   |           |                      |
   |           | Falla >= Umbral      | Exito en prueba
   |           | (ej. 3 fallos)       |
   |           v                      |
   |         +---------------+        |
   |         |     OPEN      |        |
   |         +---------------+        |
   |           |                      |
   |           | Timeout expiro       |
   |           v                      |
   |         +---------------+        |
   +---------|   HALF_OPEN   |--------+
  Falla en   +---------------+
  la prueba
',
        'analogy' => 'Imagina la instalacion electrica de tu casa en una noche de tormenta:
1. ESTADO CERRADO (CLOSED - Normal): Los cables conducen electricidad normalmente hacia la nevera, el televisor y las luces. Todo opera en equilibrio.
2. EL CORTOCIRCUITO (Fallo del Proveedor): Un electrodomestico en la cocina sufre una averia y genera un cortocircuito violento. La corriente se dispara a niveles peligrosos.
3. SALTO DEL DISYUNTOR (OPEN - Proteccion): En lugar de dejar que los cables de la pared se sobrecalienten hasta incendiar la casa (el agotamiento de hilos de PHP-FPM que tumba todo el servidor), el disyuntor termomagnetico salta con un \'clac\' instantaneo.
4. EL FAIL-FAST: Mientras la palanca este abajo (OPEN), si intentas encender la luz de la habitacion, ni siquiera fluye corriente. Se protege toda la casa mientras se repara el problema en la cocina.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En la implementacion de la maquina de estados de CircuitBreaker en PHP: 1. Inicia en estado \'CLOSED\' con failureCount = 0.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => '2. Cada invocacion a recordFailure() incrementa failureCount.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Si failureCount >= failureThreshold (ej. 3), el estado transiciona a \'OPEN\'.',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => '3. Cada invocacion a recordSuccess() decrementa o resetea failureCount y retorna el estado a \'CLOSED\'.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Failure Engineering integral en Symfony: 1. Timeout agresivos: nunca permitas un HttpClient sin timeout explicono. Un timeout de 3 segundos previene retencion indefinida de conexiones. 2. Backoff exponencial con Jitter: en reintentos automaticos, no reintentes a intervalos fijos (ej. 1s, 1s, 1s); reintenta a 1s, 2s, 4s agregando un valor aleatorio (jitter) para no saturar al servidor al mismo tiempo.',
        ],
    ],
    'video' => [
        'title' => 'The Circuit Breaker Pattern | Resilient Microservices',
        'speaker' => 'Nick Chapsas',
        'youtube_id' => '5_Bt_OEg0no',
        'duration' => '16 min',
        'description' => 'Nick Chapsas analiza el patron Circuit Breaker, por que los reintentos ciegos empeoran las caidas de sistemas dependientes y como los estados Closed, Open y Half-Open aportan resiliencia real en produccion.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Resilience;

final class CircuitBreaker
{
    public const STATE_CLOSED = \'CLOSED\';
    public const STATE_OPEN = \'OPEN\';

    private string $state = self::STATE_CLOSED;
    private int $failureCount = 0;

    public function __construct(
        private readonly int $failureThreshold = 3
    ) {}

    public function recordFailure(): void
    {
        $this->failureCount++;
        if ($this->failureCount >= $this->failureThreshold) {
            $this->state = self::STATE_OPEN;
        }
    }

    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = self::STATE_CLOSED;
    }

    public function getState(): string
    {
        return $this->state;
    }

    public function isAvailable(): bool
    {
        return $this->state === self::STATE_CLOSED;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior reintenta indefinidamente peticiones fallidas contra una API caida, terminando de tumbar al proveedor y bloqueando a todos sus usuarios. El desarrollador Senior disena para el fallo: implementa Circuit Breakers para cortar la conexion rapidamente (Fail-Fast), muestra respuestas degradadas pero utiles al usuario (Fallback UI) y permite que la infraestructura dependiente se recupere.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Deja peticiones HTTP colgadas durante 60 segundos sin timeout, agotando todos los workers de PHP-FPM.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Fija timeouts de 2 segundos y abre el Circuit Breaker al 3er fallo consecutivo, respondiendo con datos de cache o mensaje amigable.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'El Patron Circuit Breaker',
            'source' => 'Release It!: Design and Deploy Production-Ready Software',
            'quote' => 'El patron Circuit Breaker protege tu sistema previniendo que intente ejecutar repetidamente una operacion que seguramente fallara, permitiendo que la aplicacion continue funcionando sin agotar recursos.',
            'author' => 'Michael T. Nygard',
            'explanation' => 'Detiene la propagacion de fallos antes de que provoquen una caida catastrofica en cascada.',
        ],
        [
            'topic' => 'Degradacion Elegante',
            'source' => 'Site Reliability Engineering (Google SRE Book)',
            'quote' => 'Los sistemas confiables estan disenados para fallar parcialmente sin colapsar por completo. Siempre es mejor ofrecer una experiencia degradada que una pantalla en blanco.',
            'author' => 'Betsy Beyer, Chris Jones, Jennifer Petoff, Niall Richard Murphy',
            'explanation' => 'La resiliencia se mide por la capacidad de seguir operando bajo condiciones adversas.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: CircuitBreaker',
            'url' => 'https://martinfowler.com/bliki/CircuitBreaker.html',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Microsoft Cloud Design Patterns: Circuit Breaker',
            'url' => 'https://learn.microsoft.com/en-us/azure/architecture/patterns/circuit-breaker',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Implementacion del Patron Circuit Breaker (CircuitBreaker)',
        'objective' => 'Implementar la clase CircuitBreaker con recordFailure() y recordSuccess(), gestionando la transicion de estados entre CLOSED y OPEN al alcanzar el umbral de fallos.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Professional\\Resilience.
2. Declara la clase CircuitBreaker con constantes o valores string \'CLOSED\' y \'OPEN\'.
3. Inicia el estado en \'CLOSED\'.
4. Implementa recordFailure(): void incrementando el contador de fallos. Si alcanza el umbral (>= 3), transiciona el estado a \'OPEN\'.
5. Implementa recordSuccess(): void reseteando el contador de fallos y retornando el estado a \'CLOSED\'.
6. Implementa getState(): string retornando el estado actual (\'CLOSED\' u \'OPEN\').',
        'filename' => 'src/Professional/Resilience/CircuitBreaker.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten propiedades privadas $state = \'CLOSED\' y $failureCount = 0.',
                'Paso 2: En recordFailure(), suma 1 a failureCount y si es >= 3, asigna $state = \'OPEN\'.',
                'Paso 3: En recordSuccess(), asigna failureCount = 0 y $state = \'CLOSED\'.',
                'Paso 4: Asegurate de que getState() retorne \'CLOSED\' u \'OPEN\'.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El disyuntor permanece CLOSED hasta que supera el umbral de fallos, momento en que pasa a OPEN.',
            ],
            [
                'text' => '[Pista 2: Estructura] En recordFailure: $this->failureCount++; if ($this->failureCount >= 3) { $this->state = \'OPEN\'; }',
            ],
            [
                'text' => '[Pista 3: Snippet] public function recordSuccess(): void { $this->failureCount = 0; $this->state = \'CLOSED\'; }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Resilience;

class CircuitBreaker
{
    // TODO: Propiedades de estado (CLOSED, OPEN) y contador de fallos

    public function recordFailure(): void
    {
        // TODO: Incrementar fallos y transicionar a OPEN si alcanza el umbral
    }

    public function recordSuccess(): void
    {
        // TODO: Resetear fallos y transicionar a CLOSED
    }

    public function getState(): string
    {
        // TODO: Retornar estado actual
        return \'CLOSED\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Resilience;

class CircuitBreaker
{
    private string $state = \'CLOSED\';
    private int $failureCount = 0;
    private int $threshold = 3;

    public function recordFailure(): void
    {
        $this->failureCount++;
        if ($this->failureCount >= $this->threshold) {
            $this->state = \'OPEN\';
        }
    }

    public function recordSuccess(): void
    {
        $this->failureCount = 0;
        $this->state = \'CLOSED\';
    }

    public function getState(): string
    {
        return $this->state;
    }
}
',
        'explanation' => 'La clase CircuitBreaker implementa el patron clasico de resiliencia. Protege a la aplicacion contra caidas en cascada saltando a estado OPEN tras superar el umbral de fallos sucesivos y restaurando la conexion a CLOSED tras registrar un exito.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Failure Engineering: Circuit Breaker, Retries & Idempotencia',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el principal peligro de reintentar inmediatamente peticiones fallidas sin un Circuit Breaker ni backoff exponencial?',
                'options' => [
                    'a' => 'Que la base de datos se borre por completo.',
                    'b' => 'El efecto estampida: miles de peticiones reintentando simultaneamente terminan de colapsar al servicio externo que ya estaba teniendo dificultades para responder.',
                    'c' => 'Que PHPUnit marque las pruebas como saltadas.',
                    'd' => 'Que el navegador del usuario se quede sin memoria RAM.',
                ],
                'correct' => 'b',
                'explanation' => 'Si un servicio esta degradado, enviar una oleada masiva de reintentos instantaneos le impide recuperarse y agota todos los sockets del sistema cliente.',
            ],
            [
                'id' => 'q2',
                'question' => 'En el patron Circuit Breaker, ¿que ocurre cuando el disyuntor se encuentra en estado \'OPEN\'?',
                'options' => [
                    'a' => 'Las peticiones se ejecutan normalmente sin ningun control.',
                    'b' => 'Las peticiones fallan de forma inmediata (Fail-Fast) sin intentar conectar con el servicio externo caido, ahorrando tiempo y recursos.',
                    'c' => 'El servidor web se reinicia automaticamente.',
                    'd' => 'Se envia un correo de alerta a todos los usuarios del sistema.',
                ],
                'correct' => 'b',
                'explanation' => 'En estado OPEN, el disyuntor rechaza la llamada de inmediato, devolviendo una respuesta de fallback rapida sin esperar timeouts costosos de red.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que estado intermedio permite al Circuit Breaker probar si el servicio remoto ya se ha recuperado tras un periodo de tiempo?',
                'options' => [
                    'a' => 'HALF_OPEN',
                    'b' => 'PAUSED',
                    'c' => 'RETRYING',
                    'd' => 'STANDBY',
                ],
                'correct' => 'a',
                'explanation' => 'En HALF_OPEN, el disyuntor deja pasar un numero muy limitado de peticiones de prueba. Si tienen exito, transiciona a CLOSED; si fallan, regresa a OPEN.',
            ],
        ],
    ],
];
