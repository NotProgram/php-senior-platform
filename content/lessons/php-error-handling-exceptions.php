<?php

declare(strict_types=1);

return [
    'slug' => 'php-error-handling-exceptions',
    'title' => 'Manejo Robusto de Errores, Excepciones de Dominio & Fail-Fast',
    'module' => 'PHP Moderno',
    'minutes' => 40,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'PHP 8 unificó el modelo de errores bajo la interfaz nativa \\Throwable, distinguiendo formalmente entre \\Error (fallos críticos de la máquina virtual o tipos) y \\Exception (condiciones excepcionales controladas por la aplicación).',
        'problem' => 'El uso de códigos de retorno primitivos (devolver false o null ante un fallo) y bloques vacíos \'catch (\\Exception $e) {}\' crean un sistema zombi donde los fallos se propagan en silencio, corrompiendo datos en la base de datos hasta que el sistema colapsa de forma impredecible.',
        'solution' => 'Aplicar el principio Fail-Fast, diseñar jerarquías semánticas de excepciones de dominio heredando de DomainException, encapsular el estado contextual del error para auditoría y utilizar encadenamiento de excepciones ($previous) para preservar la causa raíz.',
        'problem_label' => 'El Problema: Retornos Silenciosos, Estado Corrupto y Catch Ciego',
        'solution_label' => 'La Solución Senior: Jerarquía \\Throwable, Excepciones de Dominio y Principio Fail-Fast',
    ],
    'mental_model' => [
        'title' => 'La Alarma Contra Incendios vs La Nota Adhesiva Olvidada',
        'analogy' => 'Imagina que un empleado de un banco detecta que una cuenta no tiene fondos para pagar una transferencia. Un enfoque imprudente es que el empleado pegue una pequeña nota adhesiva amarilla en una esquina del escritorio que diga `saldo = false` y siga atendiendo normalmente. Si el siguiente cajero no ve la nota, transfiere el dinero de todos modos. Eso es lo que pasa cuando una función devuelve `false` o `null` ante un fallo de negocio. Una **Excepción** es la activación inmediata de la alarma contra incendios del edificio: todas las operaciones normales se detienen al instante (Fail-Fast), la pila de llamadas se desenrolla frame a frame liberando memoria, y solo un protocolo de seguridad autorizado (un bloque `catch` específico) puede atender la emergencia con el reporte forense completo de lo que ocurrió.',
        'ascii_diagram' => '                      ┌─────────────────────────┐
                      │  interface \\Throwable   │
                      └────────────┬────────────┘
                                   │
          ┌────────────────────────┴────────────────────────┐
          ▼                                                 ▼
┌──────────────────────┐                         ┌───────────────────────┐
│     \\Error           │ (Errores de motor PHP)  │     \\Exception        │ (Excepciones de app)
├──────────────────────┤                         ├───────────────────────┤
│ • \\TypeError         │                         │ • \\LogicException     │ (Bugs de desarrollo)
│ • \\ValueError        │                         │   └─ DomainException  │ (Invariantes dominio)
│ • \\ParseError        │                         │ • \\RuntimeException   │ (Fallos de entorno I/O)
│ • \\DivisionByZero    │                         └───────────────────────┘
└──────────────────────┘
                                   │
   DESENROLLADO DE PILA (STACK UNWINDING):
   [OrderController] ──> [CheckoutService] ──> [Account::debit()]
         │                                            │
         │ (Interrupción inmediata y limpia)          ▼
         │                                     throw new InsufficientFundsException()
         ▼
   [Symfony ExceptionListener: Captura y transforma a HTTP 422 Problem Details]',
        'key_concept' => 'El principio Fail-Fast dictamina que el software debe detenerse inmediatamente ante un estado inconsistente en lugar de intentar continuar con datos corruptos.',
    ],
    'internals' => [
        'title' => 'El Árbol \\Throwable y la Mecánica del Desenroscado de Pila',
        'steps' => [
            [
                'phase' => '1. La Frontera \\Throwable: \\Error vs \\Exception',
                'description' => 'En PHP 8, los errores fatales tradicionales ahora lanzan instancias de \\Error (TypeError, ValueError). Las excepciones de aplicación heredan de \\Exception. \\Throwable es la interfaz común en la cúspide.',
            ],
            [
                'phase' => '2. Stack Unwinding (Desenroscado de Pila)',
                'description' => 'Al lanzarse un throw, el motor Zend suspende la función actual y busca hacia arriba en la pila un bloque catch compatible. En cada frame descartado, ejecuta bloques finally y decrementa los refcounts de variables locales.',
            ],
            [
                'phase' => '3. Encadenamiento de Excepciones ($previous)',
                'description' => 'El tercer parámetro del constructor de Exception permite pasar la excepción original ($previous), formando una lista enlazada que preserva la traza forense de bajo nivel sin exponerla al cliente final.',
            ],
            [
                'phase' => '4. Excepciones de Lógica vs Excepciones de Runtime',
                'description' => 'LogicException representa errores del programador detectables en desarrollo (ej. violaciones de invariantes de dominio). RuntimeException representa contingencias del entorno en ejecución (ej. fallo de red, base de datos no disponible).',
            ],
            [
                'phase' => '5. Mapeo Centralizado en Límites de Arquitectura',
                'description' => 'En Symfony, un ExceptionListener captura las excepciones de dominio que escapan de los servicios y las traduce a respuestas HTTP estructuradas (RFC 7807) sin ensuciar la lógica de negocio con detalles web.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Por qué devolver \'false\' o \'null\' es una Bomba de Tiempo',
            'icon' => 'alert-triangle',
            'content' => 'Considera este fragmento clásico de código junior:

```php
function debit(int $amount) {
    if ($this->balance < $amount) {
        return false;
    }
    $this->balance -= $amount;
    return true;
}
```

¿Por qué es catastrófico?
1. **Omisión silenciosa:** El llamador puede olvidar verificar el retorno: `$account->debit(500); $mailer->sendReceipt();` continuará felizmente aunque no hubiera fondos.
2. **Pérdida total de contexto:** `false` no te dice cuánto dinero faltaba, qué cuenta falló ni en qué fecha.
3. **Corrupción acumulativa:** El sistema sigue ejecutando pasos posteriores con datos inconsistentes, haciendo que el error explote 5 pasos después en un lugar completamente inconexo.

Lanzar una excepción `InsufficientFundsException` fuerza al código a detenerse en el milisegundo cero, protegiendo las invariantes del negocio.',
            'takeaways' => 'Haz que las funciones de mutación lancen excepciones específicas ante condiciones de fallo en lugar de retornar banderas booleanas ambiguas.',
        ],
        [
            'title' => 'Excepciones Ricas en Datos para Auditoría Empresarial',
            'icon' => 'shield',
            'content' => 'Un desarrollador Senior nunca lanza una excepción genérica con un simple string:
`throw new Exception(\'No hay saldo\');`

En su lugar, diseña una **Excepción de Dominio Rica** que encapsula las propiedades del negocio:

```php
final class InsufficientFundsException extends \\DomainException
{
    public function __construct(
        public readonly int $currentBalance,
        public readonly int $requiredAmount,
        string $message = \'\',
        ?\\Throwable $previous = null
    ) {
        $msg = $message !== \'\' 
            ? $message 
            : sprintf(\'Saldo insuficiente: Disponible %d, requerido %d.\', $currentBalance, $requiredAmount);
        parent::__construct($msg, 0, $previous);
    }
}
```

Esto permite al manejador de logs auditar con precisión matemática qué usuario intentó gastar qué monto, y al serializador de API emitir un JSON RFC 7807 con los campos exactos del problema.',
            'takeaways' => 'Encapsula las variables clave del negocio dentro de la excepción como propiedades readonly para permitir telemetría y diagnósticos forenses.',
        ],
    ],
    'video' => [
        'title' => 'Saving Time by Using a Debugger - Derick Rethans',
        'speaker' => 'Derick Rethans (Creador de Xdebug)',
        'youtube_id' => 'FsHP4zGg4Ss',
        'duration' => '44 min',
        'description' => 'Una clase magistral de Derick Rethans sobre el funcionamiento de las excepciones, inspección de la pila de llamadas y depuración profesional de fallos en PHP.',
        'key_takeaways' => [
            'Cómo el motor Zend construye y desenrolla el Stack Trace de un Throwable.',
            'La diferencia entre errores de lógica en tiempo de compilación y fallos en runtime.',
            'El peligro de atrapar excepciones genéricas y suprimir el rastro original.',
            'Técnicas avanzadas para depurar excepciones encadenadas sin perder la causa raíz.',
        ],
    ],
    'architecture_code' => [
        'filename' => 'src/Domain/Exception/InsufficientFundsException.php',
        'title' => 'Excepción de Dominio Enriquecida con Telemetría de Saldo',
        'tag' => 'PHP 8.4 Rich Domain Exception',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Exception;

use DomainException;
use Throwable;

/**
 * Excepción de dominio lanzada cuando una operación viola el saldo disponible.
 * Encapsula los montos involucrados para auditoría y telemetría de negocio.
 */
class InsufficientFundsException extends DomainException
{
    public function __construct(
        public readonly int $currentBalance,
        public readonly int $requiredAmount,
        string $message = \'\',
        ?Throwable $previous = null
    ) {
        $formattedMessage = $message !== \'\'
            ? $message
            : sprintf(
                \'Saldo insuficiente: El balance actual (%d) no cubre el monto requerido (%d).\',
                $currentBalance,
                $requiredAmount
            );

        parent::__construct($formattedMessage, 422, $previous);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un desarrollador junior atrapa excepciones con \'catch (\\Exception $e) {}\' para ocultar los errores en pantalla, dejando el sistema en un estado zombi corrupto. Un ingeniero Senior diseña jerarquías de excepciones de dominio ricas en datos, permite que los fallos detengan la transacción de inmediato (Fail-Fast), y centraliza la captura en listeners de infraestructura para registrar logs estructurados y emitir códigos HTTP semánticos.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Código Junior: Supresión de errores y retornos booleanos engañosos',
            'code' => '// Código frágil que traga excepciones
function transferMoney($account, $amount) {
    try {
        if ($account->balance < $amount) {
            return false; // Retorno silencioso sin causa explicada
        }
        $account->balance -= $amount;
        return true;
    } catch (\\Exception $e) {
        // Bloque catch ciego: traga el error sin loguear nada!
        return false;
    }
}',
            'flaws' => [
                'Atrapa genéricamente \\Exception suprimiendo errores fatales y bugs de código.',
                'Devuelve false, impidiendo que el llamador sepa si falló por saldo, base de datos o red.',
                'No encadena ni registra la excepción original, haciendo imposible depurar en producción.',
            ],
        ],
        'senior' => [
            'approach' => 'Código Senior: Excepción semántica, inmutabilidad y Fail-Fast',
            'code' => 'declare(strict_types=1);

namespace App\\Domain\\Exception;

use DomainException;
use Throwable;

class InsufficientFundsException extends DomainException
{
    public function __construct(
        public readonly int $currentBalance,
        public readonly int $requiredAmount,
        string $message = \'\',
        ?Throwable $previous = null
    ) {
        $msg = $message !== \'\' ? $message : "Insufficient funds: {$currentBalance} < {$requiredAmount}";
        parent::__construct($msg, 422, $previous);
    }
}',
            'rationale' => [
                'Hereda de DomainException, señalando una violación de invariantes de negocio.',
                'Encapsula las propiedades currentBalance y requiredAmount con visibilidad readonly.',
                'Preserva el encadenamiento mediante el parámetro $previous para auditoría forense.',
            ],
            'trade_offs' => [
                'Las excepciones tienen un costo menor de CPU por la recolección del stack trace; no deben usarse para control de flujo normal.',
                'Debes asegurarte de filtrar datos personales o secretos (passwords, tokens) antes de loguear la traza de la excepción.',
            ],
        ],
    ],
    'citations' => [
        [
            'topic' => 'The Fail-Fast Principle',
            'source' => 'IEEE Software: Fail-Fast Systems',
            'quote' => 'Un sistema fail-fast está diseñado para fallar de inmediato y de forma visible ante cualquier condición de error inesperada, facilitando el diagnóstico y evitando la propagación de datos corruptos.',
            'author' => 'Jim Shore',
            'explanation' => 'Detener el flujo de inmediato ante un saldo negativo evita que se emitan facturas erróneas o se despachen productos impagos.',
        ],
        [
            'topic' => 'Exception Hierarchy in PHP 8',
            'source' => 'PHP RFC: Throwable Interface',
            'quote' => 'La introducción de Throwable permite atrapar contingencias esperadas sin capturar accidentalmente errores de sintaxis o de tipado del motor.',
            'author' => 'PHP Internals',
            'explanation' => 'Distingue formalmente entre errores de programación (Error) y contingencias de la aplicación (Exception).',
        ],
    ],
    'external_references' => [
        [
            'title' => 'PHP Manual Oficial: La Jerarquía de Excepciones Predefinidas',
            'url' => 'https://www.php.net/manual/es/spl.exceptions.php',
            'description' => 'Guía completa de las clases de excepción estándar de la biblioteca SPL.',
            'type' => 'DOCS',
        ],
        [
            'title' => 'RFC 7807: Problem Details for HTTP APIs',
            'url' => 'https://datatracker.ietf.org/doc/html/rfc7807',
            'description' => 'Estándar de la IETF para serializar errores y excepciones de dominio en respuestas JSON de APIs REST.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Martin Fowler: Fail-Fast Design Pattern',
            'url' => 'https://martinfowler.com/ieeeSoftware/failFast.pdf',
            'description' => 'Artículo seminal sobre por qué el código debe abortar ante estados no válidos.',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto de Código: Jerarquía Semántica de Excepciones de Dominio',
        'objective' => 'Crear una excepción de dominio InsufficientFundsException para saldo insuficiente que encapsule el saldo actual, el monto requerido y encadene el fallo previo.',
        'instructions' => '1. Declara tipado estricto \'declare(strict_types=1);\'.
2. Define la clase \'InsufficientFundsException\' en el namespace \'App\\Domain\\Exception\'.
3. Hereda de \'DomainException\' (o \'Exception\').
4. Declara en el constructor las propiedades readonly \'currentBalance\' y \'requiredAmount\'.
5. Invoca a \'parent::__construct()\' pasando el mensaje y el parámetro \'$previous\'.',
        'filename' => 'src/Domain/Exception/InsufficientFundsException.php',
        'guide' => [
            'explanation' => 'Utiliza constructor property promotion para definir las propiedades \'public readonly int $currentBalance\' y \'public readonly int $requiredAmount\'. Permite recibir un mensaje opcional y una instancia previa de Throwable para encadenamiento.',
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Las excepciones de dominio deben heredar de \\DomainException (o \\LogicException / \\Exception) y encapsular datos específicos del contexto para facilitar la auditoría.',
            ],
            [
                'text' => '[Pista 2: Estructura] Declara las propiedades public readonly int $currentBalance y public readonly int $requiredAmount en el constructor de la excepción, invocando a parent::__construct().',
            ],
            [
                'text' => '[Pista 3: Snippet] En el constructor: public function __construct(public readonly int $currentBalance, public readonly int $requiredAmount, string $message = \'\', ?\\Throwable $previous = null) { parent::__construct($message !== \'\' ? $message : \'Fondos insuficientes\', 0, $previous); }.',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Exception;

use DomainException;
use Throwable;

class InsufficientFundsException extends DomainException
{
    // TODO: Declara las propiedades currentBalance y requiredAmount en el constructor
    public function __construct(
        string $message = \'\',
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Domain\\Exception;

use DomainException;
use Throwable;

class InsufficientFundsException extends DomainException
{
    public function __construct(
        public readonly int $currentBalance,
        public readonly int $requiredAmount,
        string $message = \'\',
        ?Throwable $previous = null
    ) {
        $msg = $message !== \'\'
            ? $message
            : sprintf(\'Fondos insuficientes: Saldo actual %d, requerido %d.\', $currentBalance, $requiredAmount);

        parent::__construct($msg, 422, $previous);
    }
}
',
        'explanation' => 'La clase hereda de DomainException y encapsula como propiedades readonly los montos de saldo y requerimiento, permitiendo trazabilidad y soporte para encadenamiento de excepciones.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Manejo Robusto de Errores, Excepciones de Dominio & Fail-Fast',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué un desarrollador Senior NUNCA debería escribir \'catch (\\Throwable $e) {}\' con un cuerpo vacío?',
                'options' => [
                    'a' => 'Porque \\Throwable no puede ser capturado por la sintaxis de PHP.',
                    'b' => 'Porque suprime silenciosamente errores críticos del motor (como TypeErrors y ParseErrors), dejando el sistema en un estado corrupto e inauditable.',
                    'c' => 'Porque causa un bucle infinito en el recolector de basura.',
                    'd' => 'Porque solo las excepciones de tipo RuntimeException son legales dentro de un bloque catch.',
                ],
                'correct' => 'b',
                'explanation' => 'Atrapar Throwable sin registrar ni relanzar la excepción oculta bugs de desarrollo, violaciones de tipos y fallos de infraestructura, imposibilitando el diagnóstico de problemas en producción.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cuál es la principal ventaja de encapsular valores como \'currentBalance\' en una excepción de dominio?',
                'options' => [
                    'a' => 'Reduce el consumo de memoria zval en un 50%.',
                    'b' => 'Permite que los manejadores de telemetría y formateadores de API extraigan datos estructurados sin tener que parsear expresiones regulares del mensaje de texto.',
                    'c' => 'Evita que la excepción se registre en el log de errores de PHP.',
                    'd' => 'Permite que el cliente HTTP modifique el saldo directamente.',
                ],
                'correct' => 'b',
                'explanation' => 'Tener propiedades tipadas en la excepción desacopla el mensaje legible para humanos de los datos computables requeridos para auditoría, métricas y especificaciones como RFC 7807.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué propósito cumple el parámetro \'$previous\' en el constructor de una excepción?',
                'options' => [
                    'a' => 'Indica cuántas veces se ha ejecutado el método anteriormente.',
                    'b' => 'Permite encadenar la excepción original de bajo nivel, conservando la causa raíz mientras se eleva una excepción de dominio limpia al exterior.',
                    'c' => 'Invalida la sesión del usuario si la excepción anterior fue de seguridad.',
                    'd' => 'Le indica a OpCache que descarte los Opcodes de la clase fallida.',
                ],
                'correct' => 'b',
                'explanation' => 'El encadenamiento de excepciones preserva la causa forense original (ej. un PDOException de SQL) dentro de una excepción de negocio (ej. PaymentGatewayFailedException) sin perder trazabilidad.',
            ],
        ],
    ],
];
