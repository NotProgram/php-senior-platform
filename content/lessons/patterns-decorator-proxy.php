<?php

declare(strict_types=1);

return [
    'slug' => 'patterns-decorator-proxy',
    'title' => 'Decorator & Proxy en Servicios Enterprise',
    'module' => 'design-patterns',
    'minutes' => 50,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En sistemas de software empresariales, con frecuencia surge la necesidad de anadir responsabilidades transversales a un servicio: reintentos automaticos ante fallos de red transitorios, almacenamiento en cache, registro de auditoria (logging), limitacion de tasa o control de acceso. El enfoque ingenuo suele ser heredar de la clase base (subclassing) o modificar directamente el servicio de produccion. Ambos enfoques son letales: la herencia produce una explosion combinatoria de subclases (por ejemplo, CachedRetryableAuditedPaymentGateway), mientras que modificar la clase viola el Principio de Responsabilidad Unica (SRP).

El patron Decorator resuelve este dilema envolviendo al objeto original dentro de otro objeto que implementa exactamente la misma interfaz, agregando comportamiento antes y/o despues de delegar la llamada al objeto interno. Por su parte, el patron Proxy controla el acceso al objeto real (para carga perezosa \'Lazy Loading\', seguridad o control remoto). En Symfony, la decoracion de servicios es una caracteristica de primera clase del contenedor de inyeccion de dependencias mediante el atributo #[AsDecorator], que permite interceptar cualquier servicio del framework de forma totalmente transparente.',
        'problem' => 'En sistemas de software empresariales, con frecuencia surge la necesidad de anadir responsabilidades transversales a un servicio: reintentos automaticos ante fallos de red transitorios, almacenamiento en cache, registro de auditoria (logging), limitacion de tasa o control de acceso. El enfoque ingenuo suele ser heredar de la clase base (subclassing) o modificar directamente el servicio de produccion. Ambos enfoques son letales: la herencia produce una explosion combinatoria de subclases (por ejemplo, CachedRetryableAuditedPaymentGateway), mientras que modificar la clase viola el Principio de Responsabilidad Unica (SRP).',
        'solution' => 'El patron Decorator resuelve este dilema envolviendo al objeto original dentro de otro objeto que implementa exactamente la misma interfaz, agregando comportamiento antes y/o despues de delegar la llamada al objeto interno. Por su parte, el patron Proxy controla el acceso al objeto real (para carga perezosa \'Lazy Loading\', seguridad o control remoto). En Symfony, la decoracion de servicios es una caracteristica de primera clase del contenedor de inyeccion de dependencias mediante el atributo #[AsDecorator], que permite interceptar cualquier servicio del framework de forma totalmente transparente.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Vestimenta en Capas Termicas de Alta Montana',
        'concept' => 'Imagina a un alpinista preparandose para subir al Everest en condiciones extremas:
1. EL SERVICIO NUCLEO (El Cuerpo Humano): El alpinista tiene una interfaz basica: genera calor, respira y camina.
2. PRIMER DECORADOR (Camiseta Termica): Se adapta perfectamente a la forma del cuerpo (misma interfaz). Agrega la responsabilidad de retener el calor corporal antes de dejar pasar el aire.
3. SEGUNDO DECORADOR (Chaqueta Polar): Envuelve a la camiseta termica. Agrega aislamiento termico grueso.
4. TERCER DECORADOR (Chaqueta Impermeable Gore-Tex con Reintentos): Envuelve a la chaqueta polar. Bloquea el viento y la lluvia externa. Si una rafaga de viento la golpea, resiste y se mantiene en su sitio.

Desde el exterior, el alpinista sigue siendo un humano caminando (misma interfaz publica). Cada capa (Decorador) agrego una proteccion especializada sin alterar los organos internos ni el ADN del alpinista.',
        'ascii_diagram' => 'PATRON DECORATOR EN CAPAS (Arquitectura Cebolla):

      Cliente (Caller)
             |
             v invoca charge(...)
+-------------------------------------------------------------+
| RetryableGatewayDecorator (Implementa PaymentGateway)       |
|   - Captura TransientGatewayException                       |
|   - Reintenta hasta maxRetries veces en un bucle            |
|                                                             |
|   +-----------------------------------------------------+   |
|   | LoggingGatewayDecorator (PaymentGateway)            |   |
|   |   - Registra logs de auditoria                      |   |
|   |                                                     |   |
|   |   +---------------------------------------------+   |   |
|   |   | RealStripePaymentGateway (Nucleo Original)  |   |   |
|   |   |   - Realiza llamada HTTP a Stripe           |   |   |
|   |   +---------------------------------------------+   |   |
|   +-----------------------------------------------------+   |
+-------------------------------------------------------------+
',
        'analogy' => 'Imagina a un alpinista preparandose para subir al Everest en condiciones extremas:
1. EL SERVICIO NUCLEO (El Cuerpo Humano): El alpinista tiene una interfaz basica: genera calor, respira y camina.
2. PRIMER DECORADOR (Camiseta Termica): Se adapta perfectamente a la forma del cuerpo (misma interfaz). Agrega la responsabilidad de retener el calor corporal antes de dejar pasar el aire.
3. SEGUNDO DECORADOR (Chaqueta Polar): Envuelve a la camiseta termica. Agrega aislamiento termico grueso.
4. TERCER DECORADOR (Chaqueta Impermeable Gore-Tex con Reintentos): Envuelve a la chaqueta polar. Bloquea el viento y la lluvia externa. Si una rafaga de viento la golpea, resiste y se mantiene en su sitio.

Desde el exterior, el alpinista sigue siendo un humano caminando (misma interfaz publica). Cada capa (Decorador) agrego una proteccion especializada sin alterar los organos internos ni el ADN del alpinista.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Symfony, cuando anades #[AsDecorator(decorates: StripeGateway::class, priority: 10)] a una clase, el compilador del contenedor (DecoratorServicePass) reescribe los aliases internos en tiempo de compilacion: el identificador original del servicio ahora apunta a tu clase decoradora, y la instancia original se inyecta en el decorador con el sufijo \'.inner\'.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'En tiempo de ejecucion en PHP 8.4, el impacto en rendimiento es nulo porque no se realiza ninguna introspeccion dinamica; es una simple delegacion de metodos en memoria.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Ademas, PHP 8.4 introduce soporte nativo para Lazy Proxies a traves de ReflectionClass::newLazyGhost(), permitiendo instanciar objetos perezosos que solo cargan sus datos cuando se accede a una propiedad.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'En el diseno de un Decorador de Resiliencia con Reintentos (RetryDecorator): 1. El decorador DEBE validar que maxRetries sea al menos 1 lanzando InvalidArgumentException. 2. Solo deben capturarse excepciones transitorias (por ejemplo, TransientGatewayException o timeouts de red); capturar excepciones logicas (como fondos insuficientes o tarjeta invalida) provocaria reintentos inutiles que pueden bloquear cuentas de clientes. 3. Si todos los reintentos fallan, la ultima excepcion capturada DEBE ser relanzada intacta para preservar el Stack Trace.',
        ],
    ],
    'video' => [
        'title' => 'Decorator Pattern – Design Patterns (ep 3)',
        'speaker' => 'Christopher Okhravi',
        'youtube_id' => 'GCraGHx6gso',
        'duration' => '27 min',
        'description' => 'Christopher Okhravi explica en profundidad el patron Decorator, analizando por que la herencia tradicional falla ante requerimientos combinatorios y como la composicion mediante envoltorios ofrece maxima flexibilidad.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Payment\\Decorator;

use InvalidArgumentException;
use RuntimeException;

class TransientGatewayException extends RuntimeException {}

interface PaymentGatewayInterface
{
    public function charge(int $amountInCents, string $currency): string;
}

final readonly class RetryableGatewayDecorator implements PaymentGatewayInterface
{
    public function __construct(
        private PaymentGatewayInterface $inner,
        private int $maxRetries = 3
    ) {
        if ($this->maxRetries < 1) {
            throw new InvalidArgumentException(\'El numero maximo de reintentos debe ser al menos 1.\');
        }
    }

    public function charge(int $amountInCents, string $currency): string
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            $attempts++;
            try {
                return $this->inner->charge($amountInCents, $currency);
            } catch (TransientGatewayException $e) {
                $lastException = $e;
                if ($attempts >= $this->maxRetries) {
                    throw $e;
                }
            }
        }

        throw ($lastException ?? new RuntimeException(\'Error inesperado en reintentos.\'));
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior modifica la clase de produccion agregando bloques try-catch con sleep() dentro del metodo de cobro, contaminando la logica de negocio con detalles de infraestructura. El desarrollador Senior crea un Decorador independiente que envuelve a la pasarela original: la logica de pago permanece pura e intacta, la logica de reintentos queda aislada y testeable, y se puede activar o desactivar desde la configuracion de Symfony sin tocar una sola linea de la pasarela.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Crea una subclase que sobreescribe metodos usando parent::charge(), generando un acoplamiento rigido a la clase padre.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Utiliza composicion mediante el patron Decorator implementando la interfaz compartida, permitiendo apilar multiples capas dinamicamente.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Composicion sobre Herencia',
            'source' => 'Design Patterns: Elements of Reusable Object-Oriented Software (GoF)',
            'quote' => 'El patron Decorator adjunta responsabilidades adicionales a un objeto dinamicamente. Los decoradores proporcionan una alternativa flexible a la herencia para extender la funcionalidad.',
            'author' => 'Gang of Four',
            'explanation' => 'Evita la explosion de clases al permitir combinar responsabilidades en tiempo de ejecucion.',
        ],
        [
            'topic' => 'Decoracion en Symfony',
            'source' => 'Symfony Service Container Reference: Decorating Services',
            'quote' => 'Decorar servicios permite sobrescribir o extender la funcionalidad de cualquier servicio del framework o de librerias de terceros sin romper el principio de sustitucion de Liskov.',
            'author' => 'Symfony Documentation Team',
            'explanation' => 'El mecanismo #[AsDecorator] automatiza el registro en el contenedor sin configuracion manual en YAML.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: How to Decorate Services',
            'url' => 'https://symfony.com/doc/current/service_container/service_decoration.html',
            'type' => 'DOCS',
        ],
        [
            'title' => 'Refactoring.guru: Patron Decorator en PHP',
            'url' => 'https://refactoring.guru/es/design-patterns/decorator/php/example',
            'type' => 'REFERENCE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Decorador de Resiliencia con Reintentos (RetryableGatewayDecorator)',
        'objective' => 'Implementar RetryableGatewayDecorator para dotar a cualquier pasarela de pagos de tolerancia a fallos transitorios, validando maxRetries >= 1, interceptando TransientGatewayException y delegando en el servicio interno.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Payment\\Decorator.
2. Asegurate de tener declarada la clase TransientGatewayException extends RuntimeException e interfaz PaymentGatewayInterface.
3. Implementa la clase RetryableGatewayDecorator implementando PaymentGatewayInterface.
4. En el constructor, inyecta private readonly PaymentGatewayInterface $inner y private readonly int $maxRetries = 3.
5. Si $maxRetries < 1 lanza InvalidArgumentException.
6. En charge(int $amountInCents, string $currency): string, utiliza un bucle while o for para reintentar hasta $maxRetries.
7. En cada intento, delega en $this->inner->charge($amountInCents, $currency) dentro de un bloque try-catch capturando TransientGatewayException.
8. Si se alcanza el maximo de reintentos, relanza la excepcion capturada.',
        'filename' => 'src/Payment/Decorator/RetryableGatewayDecorator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida en el constructor que maxRetries sea mayor o igual a 1.',
                'Paso 2: En charge(), inicia una variable de intentos a 0 y una referencia a la ultima excepcion.',
                'Paso 3: Ejecuta un bucle while ($attempts < $this->maxRetries).',
                'Paso 4: Invoca $this->inner->charge dentro de un bloque try.',
                'Paso 5: En el catch(TransientGatewayException $e), almacena la excepcion y relanzala si se alcanzo el maximo.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El decorador implementa la misma interfaz PaymentGatewayInterface que el objeto que envuelve.',
            ],
            [
                'text' => '[Pista 2: Estructura] En el catch: $lastException = $e; if ($attempts >= $this->maxRetries) { throw $e; }.',
            ],
            [
                'text' => '[Pista 3: Snippet] while ($attempts < $this->maxRetries) { $attempts++; try { return $this->inner->charge(...); } catch (...) { ... } }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Payment\\Decorator;

use RuntimeException;
use InvalidArgumentException;

class TransientGatewayException extends RuntimeException {}

interface PaymentGatewayInterface
{
    public function charge(int $amountInCents, string $currency): string;
}

class RetryableGatewayDecorator implements PaymentGatewayInterface
{
    public function __construct(
        private readonly PaymentGatewayInterface $inner,
        private readonly int $maxRetries = 3
    ) {
        // TODO: Validar que $this->maxRetries >= 1 o lanzar InvalidArgumentException
    }

    public function charge(int $amountInCents, string $currency): string
    {
        // TODO: Delegar en $this->inner->charge($amountInCents, $currency)
        // TODO: Capturar TransientGatewayException y reintentar hasta $this->maxRetries veces con un bucle
        // TODO: Si todos los reintentos fallan, propagar la ultima excepcion capturada
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Payment\\Decorator;

use RuntimeException;
use InvalidArgumentException;

class TransientGatewayException extends RuntimeException {}

interface PaymentGatewayInterface
{
    public function charge(int $amountInCents, string $currency): string;
}

class RetryableGatewayDecorator implements PaymentGatewayInterface
{
    public function __construct(
        private readonly PaymentGatewayInterface $inner,
        private readonly int $maxRetries = 3
    ) {
        if ($this->maxRetries < 1) {
            throw new InvalidArgumentException(\'El numero maximo de reintentos debe ser al menos 1.\');
        }
    }

    public function charge(int $amountInCents, string $currency): string
    {
        $attempts = 0;
        $lastException = null;

        while ($attempts < $this->maxRetries) {
            $attempts++;
            try {
                return $this->inner->charge($amountInCents, $currency);
            } catch (TransientGatewayException $e) {
                $lastException = $e;
                if ($attempts >= $this->maxRetries) {
                    throw $e;
                }
            }
        }

        throw ($lastException ?? new RuntimeException(\'Error inesperado en reintentos.\'));
    }
}
',
        'explanation' => 'RetryableGatewayDecorator anade resiliencia transparente a cualquier pasarela de pagos. Valida la invariante del numero de reintentos en el constructor y reintenta exclusivamente ante excepciones transitorias, preservando la excepcion original si la infraestructura no se recupera.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Decorator & Proxy en Servicios Enterprise',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la diferencia arquitectonica principal entre el patron Decorator y la herencia de clases (subclassing)?',
                'options' => [
                    'a' => 'El patron Decorator solo funciona con metodos estaticos, mientras que la herencia solo funciona con interfaces.',
                    'b' => 'El patron Decorator utiliza composicion envolviendo una instancia en tiempo de ejecucion respetando la misma interfaz, mientras que la herencia fija la extension en tiempo de compilacion provocando rigidez combinatoria.',
                    'c' => 'La herencia permite ejecutar metodos en paralelo y el decorador no.',
                    'd' => 'No existe diferencia; son implementaciones equivalentes segun PSR-12.',
                ],
                'correct' => 'b',
                'explanation' => 'Con decoradores puedes componer en tiempo de ejecucion: LoggingGateway(RetryableGateway(StripeGateway)). Con herencia necesitarias clases rigidas para cada posible combinacion.',
            ],
            [
                'id' => 'q2',
                'question' => 'En el contenedor de Symfony, ¿que atributo PHP permite registrar automaticamente un servicio como decorador de otro servicio existente?',
                'options' => [
                    'a' => '#[AsDecorator]',
                    'b' => '#[AutowireDecorator]',
                    'c' => '#[MakeSubclass]',
                    'd' => '#[DecorateThisClass]',
                ],
                'correct' => 'a',
                'explanation' => 'El atributo #[AsDecorator(decorates: TargetService::class)] instruye al compilador del contenedor para reemplazar el servicio original e inyectarlo dentro del decorador como .inner.',
            ],
            [
                'id' => 'q3',
                'question' => 'En un decorador de reintentos (RetryableDecorator), ¿por que es critico capturar unicamente excepciones transitorias (como TransientGatewayException) y NO capturar Exception de forma generica?',
                'options' => [
                    'a' => 'Porque capturar Exception bloquea el recolector de basura de PHP.',
                    'b' => 'Porque excepciones de logica (como FondosInsuficientesException o TarjetaInvalidaException) nunca se resolveran reintentando y causarian demoras y posibles bloqueos de seguridad.',
                    'c' => 'Porque PHPUnit falla con error de sintaxis si se captura una excepcion generica dentro de un decorador.',
                    'd' => 'Porque las excepciones genericas no tienen el metodo getMessage().',
                ],
                'correct' => 'b',
                'explanation' => 'Reintentar un error de tarjeta expirada o fondos insuficientes es inutil y danino. Solo los fallos transitorios de red o timeouts se benefician de una politica de reintentos.',
            ],
        ],
    ],
];
