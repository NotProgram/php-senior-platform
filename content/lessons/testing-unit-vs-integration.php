<?php

declare(strict_types=1);

return [
    'slug' => 'testing-unit-vs-integration',
    'title' => 'Unit vs Integration vs Functional Testing',
    'module' => 'testing',
    'minutes' => 45,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'Una de las controversias mas costosas en la ingenieria de software radica en la definicion de que constituye una \'unidad\' de prueba. Para la escuela solitaria (Solitary / London School), una unidad es una clase individual, y cualquier colaborador debe ser reemplazado por un doble de prueba. Para la escuela sociable (Sociable / Detroit School), una unidad es un comportamiento de negocio observable, que puede abarcar multiples clases colaborando juntas en memoria.

El desarrollador Senior comprende cuando trazar la frontera: 1. Prueba Unitaria Sociable: valida logica pura de dominio coordinando entidades y value objects en memoria a velocidad de microsegundos. 2. Prueba de Integracion: valida la comunicacion real a traves de una frontera de infraestructura que no controlamos por completo (por ejemplo, un Repositorio de Doctrine ejecutando queries DQL reales contra SQLite o PostgreSQL). 3. Prueba Funcional / E2E: valida el flujo completo a traves del HttpKernel de Symfony simulando peticiones HTTP reales.',
        'problem' => 'Una de las controversias mas costosas en la ingenieria de software radica en la definicion de que constituye una \'unidad\' de prueba. Para la escuela solitaria (Solitary / London School), una unidad es una clase individual, y cualquier colaborador debe ser reemplazado por un doble de prueba. Para la escuela sociable (Sociable / Detroit School), una unidad es un comportamiento de negocio observable, que puede abarcar multiples clases colaborando juntas en memoria.',
        'solution' => 'El desarrollador Senior comprende cuando trazar la frontera: 1. Prueba Unitaria Sociable: valida logica pura de dominio coordinando entidades y value objects en memoria a velocidad de microsegundos. 2. Prueba de Integracion: valida la comunicacion real a traves de una frontera de infraestructura que no controlamos por completo (por ejemplo, un Repositorio de Doctrine ejecutando queries DQL reales contra SQLite o PostgreSQL). 3. Prueba Funcional / E2E: valida el flujo completo a traves del HttpKernel de Symfony simulando peticiones HTTP reales.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Alternador del Coche: Banco de Trabajo vs Motor Ensamblado',
        'concept' => 'Considera como un mecanico automotriz diagnostica un alternador electrico:
1. PRUEBA UNITARIA: Coloca el alternador solo en un banco de pruebas. Lo hace girar con un motor electrico calibrado y mide con un osciloscopio si genera exactamente 14.2 voltios. Si falla, el alternador esta roto; no hay ninguna duda.
2. PRUEBA DE INTEGRACION: Conecta el alternador a la bateria real del vehiculo y al regulador de voltaje. Verifica si la corriente fluye correctamente por los cables y si la bateria absorbe la carga sin sobrecalentarse. Aqui ya no prueba solo el alternador; prueba el acople fisico y los conectores entre ambos.
3. PRUEBA FUNCIONAL: Arranca el vehiculo completo, enciende los faros, la radio y el aire acondicionado al maximo, y comprueba si el sistema electrico soporta la carga simultanea en la carretera.

Si solo hicieras pruebas en carretera, nunca sabrias si un fallo electrico es culpa de un cable flojo, la bateria o el alternador.',
        'ascii_diagram' => 'FRONTERAS ARQUITECTONICAS DE PRUEBA:

[ PRUEBA UNITARIA SOCIABLE ]
+-------------------------------------------------------------+
| Dominio en Memoria (Microsegundos, 0 I/O)                   |
|                                                             |
|  [ DiscountCalculator ] ---> [ Order ] ---> [ MoneyVO ]     |
+-------------------------------------------------------------+

[ PRUEBA DE INTEGRACION ]
+------------------------------------+      +------------------+
| Symfony KernelTestCase             |      | Infraestructura  |
|                                    | I/O  |                  |
|  [ OrderRepository ] -------------(SQL)-->| [ SQLite / PG ]  |
+------------------------------------+      +------------------+

[ PRUEBA FUNCIONAL HTTP ]
+-------------------------------------------------------------+
| Symfony WebTestCase (KernelBrowser)                         |
|                                                             |
|  POST /api/v1/orders ---> HttpKernel ---> Router ---> Resp  |
+-------------------------------------------------------------+
',
        'analogy' => 'Considera como un mecanico automotriz diagnostica un alternador electrico:
1. PRUEBA UNITARIA: Coloca el alternador solo en un banco de pruebas. Lo hace girar con un motor electrico calibrado y mide con un osciloscopio si genera exactamente 14.2 voltios. Si falla, el alternador esta roto; no hay ninguna duda.
2. PRUEBA DE INTEGRACION: Conecta el alternador a la bateria real del vehiculo y al regulador de voltaje. Verifica si la corriente fluye correctamente por los cables y si la bateria absorbe la carga sin sobrecalentarse. Aqui ya no prueba solo el alternador; prueba el acople fisico y los conectores entre ambos.
3. PRUEBA FUNCIONAL: Arranca el vehiculo completo, enciende los faros, la radio y el aire acondicionado al maximo, y comprueba si el sistema electrico soporta la carga simultanea en la carretera.

Si solo hicieras pruebas en carretera, nunca sabrias si un fallo electrico es culpa de un cable flojo, la bateria o el alternador.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Symfony, KernelTestCase arranca el contenedor de dependencias del framework sin inicializar un servidor web real.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Esto permite inyectar repositorios reales y servicios de infraestructura directamente desde el contenedor ($container = static::getContainer()).',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Para que las pruebas de integracion sean deterministicas y no dejen basura en la base de datos, se utiliza DAMA\\DoctrineTestBundle, que intercepta cada prueba dentro de una transaccion de base de datos y emite un ROLLBACK automatico al finalizar el test, garantizando aislamiento total sin necesidad de recrear el schema en cada ejecucion.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Un error frecuente es simular la base de datos con mocks en pruebas de repositorios. Un mock de EntityManager no prueba si tu sintaxis DQL es correcta, si tus mapeos de entidades contienen errores o si una clave foranea causara un error de integridad referencial. Las pruebas de repositorios e infraestructura DEBEN ser de integracion y ejecutarse contra un motor relacional real.',
        ],
    ],
    'video' => [
        'title' => 'When To Unit, E2E, And Integration Test',
        'speaker' => 'ThePrimeagen (The PrimeTime)',
        'youtube_id' => 'isI1c0eGSZ0',
        'duration' => '22 min',
        'description' => 'ThePrimeagen analiza con brutal honestidad los trade-offs de la vida real: por que los tests con mocks excesivos otorgan falsa seguridad y como las pruebas de integracion salvan sistemas criticos en produccion.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Pricing;

use InvalidArgumentException;

final class DiscountPolicyEngine
{
    /**
     * Calcula el descuento monetario aplicable respetando invariantes de negocio.
     */
    public function calculateDiscount(float $subtotal, float $discountPercent, ?string $couponCode = null): float
    {
        if ($subtotal < 0.0) {
            throw new InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        if ($discountPercent < 0.0 || $discountPercent > 100.0) {
            throw new InvalidArgumentException(\'El porcentaje de descuento debe estar entre 0 y 100.\');
        }

        $discount = ($subtotal * $discountPercent) / 100.0;

        // Regla especial de cupon promocional VIP
        if ($couponCode === \'VIP2026\' && $subtotal > 100.0) {
            $discount += 15.0;
        }

        // Invariante critica: el descuento nunca puede superar el subtotal
        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return round($discount, 2, PHP_ROUND_HALF_UP);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior cree que cualquier prueba que instancie una clase es \'unitaria\' aunque conecte con una base de datos externa, o bien cree que todo colaborador debe ser un Mock obligatoriamente. El desarrollador Senior sabe que las pruebas unitarias son para la logica de negocio pura en memoria, y las de integracion son para validar que los adaptadores de infraestructura conversan fielmente con los sistemas externos.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Hace mock de la base de datos para probar un query complejo, creyendo que probo la persistencia.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Ejecuta pruebas de integracion con SQLite o Testcontainers para validar que los queries DQL y constraints SQL se ejecutan a la perfeccion.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Sociable vs Solitary Tests',
            'source' => 'Mocks Aren\'t Stubs / Bliki',
            'quote' => 'Las pruebas sociables no aislan a la clase de sus colaboradores si estos son deterministicos y rapidos. Solo se aislan los colaboradores que cruzan fronteras de infraestructura o son no deterministicos.',
            'author' => 'Martin Fowler',
            'explanation' => 'El aislamiento excesivo genera pruebas fragiles que no garantizan la colaboracion real entre componentes.',
        ],
        [
            'topic' => 'Pruebas de Integracion Reales',
            'source' => 'Unit Testing Principles, Practices, and Patterns',
            'quote' => 'Una prueba de integracion verifica que dos o mas subsistemas funcionen juntos correctamente. Probar un repositorio con un mock del ORM es un desperdicio de tiempo.',
            'author' => 'Vladimir Khorikov',
            'explanation' => 'La integracion solo es valiosa cuando interactua con la infraestructura real o su emulacion fiel.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: KernelTestCase for Integration Tests',
            'url' => 'https://symfony.com/doc/current/testing.html#integration-tests',
            'type' => 'DOCS',
        ],
        [
            'title' => 'DAMA\\DoctrineTestBundle: Fast isolated database testing',
            'url' => 'https://github.com/dmaicher/doctrine-test-bundle',
            'type' => 'TOOL',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Motor de Descuentos con Invariantes Estrictas (DiscountPolicyEngine)',
        'objective' => 'Implementar el motor de calculo de descuentos con validacion rigurosa de invariantes aritmeticas, evaluacion del cupon promocional VIP2026, acotacion maxima al subtotal y redondeo estandar PHP_ROUND_HALF_UP.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Pricing.
2. Implementa el metodo calculateDiscount(float $subtotal, float $discountPercent, ?string $couponCode = null): float.
3. Si $subtotal < 0.0 o $discountPercent no esta en [0.0, 100.0], lanza InvalidArgumentException.
4. Calcula el descuento base ($subtotal * $discountPercent / 100.0).
5. Si $couponCode === \'VIP2026\' y $subtotal > 100.0, agrega 15.0 al descuento.
6. Garantiza que el descuento nunca supere el subtotal ($discount > $subtotal).
7. Retorna el resultado redondeado a 2 decimales mediante round($discount, 2, PHP_ROUND_HALF_UP).',
        'filename' => 'src/Pricing/DiscountPolicyEngine.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que el subtotal y porcentaje cumplan los rangos validos.',
                'Paso 2: Calcula la proporcion inicial sobre el subtotal.',
                'Paso 3: Verifica la bonificacion de 15.0 para el cupon VIP2026 cuando el subtotal supere 100.0.',
                'Paso 4: Limita el descuento con min($discount, $subtotal) o una condicion if.',
                'Paso 5: Redondea a 2 decimales con PHP_ROUND_HALF_UP.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El descuento no puede exceder el costo total del producto.',
            ],
            [
                'text' => '[Pista 2: Estructura] Emplea if ($couponCode === \'VIP2026\' && $subtotal > 100.0) { $discount += 15.0; }.',
            ],
            [
                'text' => '[Pista 3: Snippet] return round(min($discount, $subtotal), 2, PHP_ROUND_HALF_UP);',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Pricing;

use InvalidArgumentException;

class DiscountPolicyEngine
{
    public function calculateDiscount(float $subtotal, float $discountPercent, ?string $couponCode = null): float
    {
        // TODO: Validar que $subtotal >= 0.0 y $discountPercent entre 0 y 100 (lanzar InvalidArgumentException)
        // TODO: Calcular descuento porcentual
        // TODO: Si cupon es \'VIP2026\' y $subtotal > 100.0, agregar 15.0 de bonificacion
        // TODO: Acotar descuento para que no supere $subtotal
        // TODO: Retornar redondeado a 2 decimales con round($val, 2, PHP_ROUND_HALF_UP)
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Pricing;

use InvalidArgumentException;

class DiscountPolicyEngine
{
    public function calculateDiscount(float $subtotal, float $discountPercent, ?string $couponCode = null): float
    {
        if ($subtotal < 0.0) {
            throw new InvalidArgumentException(\'El subtotal no puede ser negativo.\');
        }

        if ($discountPercent < 0.0 || $discountPercent > 100.0) {
            throw new InvalidArgumentException(\'El porcentaje de descuento debe estar entre 0 y 100.\');
        }

        $discount = ($subtotal * $discountPercent) / 100.0;

        if ($couponCode === \'VIP2026\' && $subtotal > 100.0) {
            $discount += 15.0;
        }

        if ($discount > $subtotal) {
            $discount = $subtotal;
        }

        return round($discount, 2, PHP_ROUND_HALF_UP);
    }
}
',
        'explanation' => 'La implementacion protege las invariantes aritmeticas y de negocio sin tolerar valores negativos. El cupon VIP2026 solo otorga beneficio adicional si el subtotal califica (> 100.0), y el tope final previene saldos negativos para la empresa.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Unit vs Integration vs Functional Testing',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el rol principal de DAMA\\DoctrineTestBundle en una suite de pruebas de integracion en Symfony?',
                'options' => [
                    'a' => 'Compilar las plantillas Twig en archivos estaticos HTML.',
                    'b' => 'Ejecutar cada prueba dentro de una transaccion de base de datos y hacer rollback automatico al terminar, aislando el estado de la DB a alta velocidad.',
                    'c' => 'Generar mocks automaticos de todos los servicios registrados en el contenedor.',
                    'd' => 'Verificar que las clases de Symfony cumplan con el estandar PSR-4 de Composer.',
                ],
                'correct' => 'b',
                'explanation' => 'DAMA\\DoctrineTestBundle envuelve cada test en una transaccion y la revierte con ROLLBACK. De este modo, la base de datos vuelve a su estado limpio sin la sobrecarga de recrear el schema o truncar tablas en cada caso.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por que las pruebas unitarias que reemplazan todos los colaboradores con Mocks pueden fallar en detectar bugs de integracion?',
                'options' => [
                    'a' => 'Porque los mocks en PHP no admiten tipos estrictos int y string.',
                    'b' => 'Porque un mock solo verifica que los metodos fueron llamados segun la asuncion del autor del test, pero no verifica si las clases reales son compatibles entre si.',
                    'c' => 'Porque PHPUnit desactiva las aserciones si detecta mas de 2 mocks en una prueba.',
                    'd' => 'Porque el motor Zend VM no ejecuta codigo PHP cuando hay un mock activo.',
                ],
                'correct' => 'b',
                'explanation' => 'Si la clase colaboradora cambia su comportamiento pero el mock sigue configurado con el comportamiento antiguo, la prueba unitaria pasara con exito mientras el sistema real fallara en produccion.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que caracteriza a una prueba unitaria \'Sociable\' segun Martin Fowler?',
                'options' => [
                    'a' => 'Requiere que todos los ingenieros del equipo revisen la prueba en vivo.',
                    'b' => 'Permite que la clase bajo prueba colabore con otras clases reales de dominio en memoria, siempre que sean deterministicas y no crucen fronteras de I/O.',
                    'c' => 'Se conecta a redes sociales externas mediante cURL.',
                    'd' => 'Solo se ejecuta cuando el servidor web Nginx esta en linea.',
                ],
                'correct' => 'b',
                'explanation' => 'Las pruebas sociables integran objetos reales en memoria (entidades, agregados, calculadoras) para verificar comportamientos completos sin la fragilidad ni la artificialidad de aislar cada metodo con mocks.',
            ],
        ],
    ],
];
