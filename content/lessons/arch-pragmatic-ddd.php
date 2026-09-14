<?php

declare(strict_types=1);

return [
    'slug' => 'arch-pragmatic-ddd',
    'title' => 'Domain-Driven Design Pragmatico',
    'module' => 'architecture',
    'minutes' => 65,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El Diseno Guiado por el Dominio (DDD, Domain-Driven Design, concebido por Eric Evans) es una de las filosofias tecnicas mas profundas para combatir la complejidad en el software enterprise. Lamentablemente, suele ser malinterpretado como un catalogo ritualistico de patrones tecnicos: \'poner Entity, Repository, Factory y Aggregate en cada modulo\'.

El verdadero DDD se divide en dos pilares:
1. DDD Estrategico: Delimitar Bounded Contexts (Contextos Delimitados), forjar un Ubiquitous Language (Lenguaje Ubicuo) compartido rigurosamente entre expertos de negocio y programadores, y trazar mapas de contexto (Context Maps).
2. DDD Tactico: Modelar Agregados (Aggregates) como limites transaccionales y guardianes absolutos de las invariantes de negocio. En esta leccion implementamos un Carrito de Compras (ShoppingCart Aggregate) modelado segun DDD pragmatico: las entidades internas no se modifican desde el exterior; toda alteracion atraviesa la Raiz del Agregado (Aggregate Root), garantizando que ninguna operacion invalida (cantidades no positivas, precios negativos o checkouts de carritos vacios) pueda existir jamas en el sistema.',
        'problem' => 'El Diseno Guiado por el Dominio (DDD, Domain-Driven Design, concebido por Eric Evans) es una de las filosofias tecnicas mas profundas para combatir la complejidad en el software enterprise. Lamentablemente, suele ser malinterpretado como un catalogo ritualistico de patrones tecnicos: \'poner Entity, Repository, Factory y Aggregate en cada modulo\'.',
        'solution' => 'El verdadero DDD se divide en dos pilares:
1. DDD Estrategico: Delimitar Bounded Contexts (Contextos Delimitados), forjar un Ubiquitous Language (Lenguaje Ubicuo) compartido rigurosamente entre expertos de negocio y programadores, y trazar mapas de contexto (Context Maps).
2. DDD Tactico: Modelar Agregados (Aggregates) como limites transaccionales y guardianes absolutos de las invariantes de negocio. En esta leccion implementamos un Carrito de Compras (ShoppingCart Aggregate) modelado segun DDD pragmatico: las entidades internas no se modifican desde el exterior; toda alteracion atraviesa la Raiz del Agregado (Aggregate Root), garantizando que ninguna operacion invalida (cantidades no positivas, precios negativos o checkouts de carritos vacios) pueda existir jamas en el sistema.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Contrato Notarial y el Notario Publico',
        'concept' => 'Imagina una transaccion inmobiliaria donde se firma una escritura publica ante un Notario:
1. EL AGREGADO (La Escritura Completa): Es un conjunto indivisible compuesto por clausulas legales, anexos de planos, identificaciones fiscales de las partes y certificados de no deuda.
2. LA RAIZ DEL AGREGADO (El Notario): Es la unica persona con autoridad legal para abrir el documento y alterar su contenido. Si el comprador quiere cambiar una clausula (agregar un item al carrito), no puede tomar un boligrafo y tachar la hoja 5 en privado.
3. LA INVARIANTE (La Ley Notarial): El notario revisa que la suma de los pagos cuadre al centavo y que el inmueble este libre de hipotecas. Si una clausula no cumple la ley (un item con cantidad cero o precio negativo), el notario expulsa a las partes y anula el tramite.
4. EL CHECKOUT ATOMICO: Cuando se estampa la firma final, se sella la escritura completa en un solo instante. No existe la posibilidad de que se registre el comprador pero se olvide el pago.',
        'ascii_diagram' => 'EL AGREGADO DE DDD (Limite de Consistencia Transaccional):

                 Cliente Externo (Caso de Uso)
                              |
                              | invoca metodos publicos de negocio
                              v
        +-------------------------------------------------------------+
        | AGGREGATE ROOT: ShoppingCart                                |
        |                                                             |
        |   - id: CartId                                              |
        |   - customerId: CustomerId                                  |
        |                                                             |
        |   + addItem(productId, quantity, unitPrice): void           |
        |   + removeItem(productId): void                            |
        |   + getTotal(): float                                       |
        |   + checkout(): float                                       |
        |                                                             |
        |   [ Frontera Protectora de Invariantes ]                    |
        |   |   - Cantidad DEBE ser >= 1                              |
        |   |   - Precio unitario DEBE ser >= 0.0                     |
        |   |   - Checkout en carrito vacio arroja DomainException    |
        |   v                                                         |
        |                                                             |
        |   +-----------------------+     +-----------------------+   |
        |   | Entidad Interna       |     | Entidad Interna       |   |
        |   | CartItem (Product A)  |     | CartItem (Product B)  |   |
        |   +-----------------------+     +-----------------------+   |
        +-------------------------------------------------------------+
               (Las entidades internas NUNCA son accedidas
                directamente por clientes externos al Agregado)
',
        'analogy' => 'Imagina una transaccion inmobiliaria donde se firma una escritura publica ante un Notario:
1. EL AGREGADO (La Escritura Completa): Es un conjunto indivisible compuesto por clausulas legales, anexos de planos, identificaciones fiscales de las partes y certificados de no deuda.
2. LA RAIZ DEL AGREGADO (El Notario): Es la unica persona con autoridad legal para abrir el documento y alterar su contenido. Si el comprador quiere cambiar una clausula (agregar un item al carrito), no puede tomar un boligrafo y tachar la hoja 5 en privado.
3. LA INVARIANTE (La Ley Notarial): El notario revisa que la suma de los pagos cuadre al centavo y que el inmueble este libre de hipotecas. Si una clausula no cumple la ley (un item con cantidad cero o precio negativo), el notario expulsa a las partes y anula el tramite.
4. EL CHECKOUT ATOMICO: Cuando se estampa la firma final, se sella la escritura completa en un solo instante. No existe la posibilidad de que se registre el comprador pero se olvide el pago.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En PHP 8.4, el Agregado mantiene sus colecciones internas en arrays privados tipados, protegiendo las referencias para que el codigo cliente no pueda mutar los items por referencia.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Al implementar addItem(), se comprueba si el producto ya existe para incrementar su cantidad en lugar de duplicar lineas.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'El metodo checkout() valida el estado global del agregado: si el carrito no tiene items, arroja una excepcion de dominio DomainException, evitando procesar ordenes fantasmas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'El error comun en DDD tactico es crear Agregados gigantescos (por ejemplo, cargar todos los pedidos historicos de un cliente dentro del Agregado Customer). Esto provoca bloqueos de concurrencia masivos en base de datos y un consumo inmanejable de RAM. Un Agregado Senior debe ser lo mas pequeno posible: unicamente lo necesario para mantener la consistencia transaccional inmediata de las invariantes bajo modificacion concurrente.',
        ],
    ],
    'video' => [
        'title' => 'DDD & Microservices: At Last, Some Boundaries!',
        'speaker' => 'Eric Evans (Creador de Domain-Driven Design)',
        'youtube_id' => 'yPvef9R3k-M',
        'duration' => '49 min',
        'description' => 'Eric Evans diserta en GOTO Conference sobre como delimitar contextos (Bounded Contexts), la definicion de agregados y por que el diseno imperfecto pero pragmatico supera siempre al purismo teorico en proyectos reales.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Cart\\Domain;

use DomainException;
use InvalidArgumentException;

final class ShoppingCart
{
    /**
     * @var array<string, array{productId: string, quantity: int, unitPrice: float}>
     */
    private array $items = [];

    public function __construct(
        public readonly string $cartId
    ) {}

    public function addItem(string $productId, int $quantity, float $unitPrice): void
    {
        $id = trim($productId);
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador del producto no puede estar vacio.\');
        }

        if ($quantity <= 0) {
            throw new InvalidArgumentException(\'La cantidad agregada debe ser estrictamente mayor a 0.\');
        }

        if ($unitPrice < 0.0) {
            throw new InvalidArgumentException(\'El precio unitario no puede ser negativo.\');
        }

        if (isset($this->items[$id])) {
            $this->items[$id][\'quantity\'] += $quantity;
        } else {
            $this->items[$id] = [
                \'productId\' => $id,
                \'quantity\' => $quantity,
                \'unitPrice\' => $unitPrice,
            ];
        }
    }

    public function removeItem(string $productId): void
    {
        $id = trim($productId);
        unset($this->items[$id]);
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item[\'quantity\'] * $item[\'unitPrice\'];
        }

        return round($total, 2);
    }

    public function checkout(): float
    {
        if (empty($this->items)) {
            throw new DomainException(\'No se puede ejecutar el checkout de un carrito de compras vacio.\');
        }

        return $this->getTotal();
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior crea modelos anemicos donde las entidades son meras bolsas de datos con getters y setters publicos, dejando que los servicios modifiquen las propiedades sin ninguna validacion. El desarrollador Senior aplica DDD: el Agregado encapsula su propio estado, expone metodos con lenguaje de negocio del mundo real (addItem, checkout) y hace que sea tecnicamente imposible que el sistema entre en un estado invalido o inconsistente.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Invoca $item->setPrice(-50) directamente desde un controlador sin que nadie valide la invariante.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'La entidad no tiene setPrice; cualquier modificacion se realiza a traves del Agregado con invariantes blindadas.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'El Agregado en DDD',
            'source' => 'Domain-Driven Design: Tackling Complexity in the Heart of Software',
            'quote' => 'Un Agregado es un grupo de objetos asociados que tratamos como una unidad para el proposito de los cambios de datos. La Raiz del Agregado es la unica puerta de entrada para clientes externos.',
            'author' => 'Eric Evans',
            'explanation' => 'Garantiza que todas las invariantes dentro del limite se verifiquen antes de persistir.',
        ],
        [
            'topic' => 'Modelos Anemicos',
            'source' => 'Anemic Domain Model / Fowler Bliki',
            'quote' => 'El horror fundamental del modelo de dominio anemico es que es exactamente lo opuesto al diseno orientado a objetos: combina datos sin comportamiento y comportamiento sin datos.',
            'author' => 'Martin Fowler',
            'explanation' => 'Separar la logica de las entidades convierte a los objetos en simples estructuras C sin encapsulacion.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Martin Fowler: Aggregate Pattern in DDD',
            'url' => 'https://martinfowler.com/bliki/DDD_Aggregate.html',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'Vaughn Vernon: Effective Aggregate Design',
            'url' => 'https://kalele.io/blog/',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Agregado de Carrito con Proteccion de Invariantes (ShoppingCart Aggregate)',
        'objective' => 'Implementar el Agregado ShoppingCart con addItem(), removeItem(), getTotal() y checkout(), validando estrictamente que $quantity > 0, $unitPrice >= 0.0 (InvalidArgumentException) y que no se permita el checkout de un carrito vacio (DomainException).',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Cart\\Domain.
2. Implementa la clase ShoppingCart.
3. Implementa addItem(string $productId, int $quantity, float $unitPrice): void.
4. Valida que $quantity <= 0 o $unitPrice < 0.0 lance \\InvalidArgumentException.
5. Si el item ya existe, incrementa su cantidad; si no, anadelo a la coleccion interna.
6. Implementa removeItem(string $productId): void eliminando el producto de la coleccion.
7. Implementa getTotal(): float calculando la suma de ($quantity * $unitPrice) de los items.
8. Implementa checkout(): float que lance \\DomainException si el carrito esta vacio; de lo contrario, retorne getTotal().',
        'filename' => 'src/Cart/Domain/ShoppingCart.php',
        'guide' => [
            'steps' => [
                'Paso 1: Manten un array asociativo privado $items = [] indexado por $productId.',
                'Paso 2: En addItem(), valida que $quantity > 0 y $unitPrice >= 0.0, de lo contrario lanza InvalidArgumentException.',
                'Paso 3: En removeItem(), usa unset($this->items[$productId]).',
                'Paso 4: En getTotal(), suma los subtotales de cada item.',
                'Paso 5: En checkout(), si empty($this->items) lanza DomainException; de lo contrario retorna el total.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El Agregado es responsable exclusivo de la consistencia de sus items internos.',
            ],
            [
                'text' => '[Pista 2: Estructura] if ($quantity <= 0 || $unitPrice < 0.0) { throw new \\InvalidArgumentException(...); }',
            ],
            [
                'text' => '[Pista 3: Snippet] if (empty($this->items)) { throw new \\DomainException(\'Carrito vacio\'); } return $this->getTotal();',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Cart\\Domain;

use DomainException;
use InvalidArgumentException;

class ShoppingCart
{
    /**
     * @var array<string, array{productId: string, quantity: int, unitPrice: float}>
     */
    private array $items = [];

    public function addItem(string $productId, int $quantity, float $unitPrice): void
    {
        // TODO: Validar que $quantity > 0 y $unitPrice >= 0.0 (lanzar InvalidArgumentException)
        // TODO: Si ya existe, sumar cantidad; si no, crear nuevo item
    }

    public function removeItem(string $productId): void
    {
        // TODO: Eliminar producto de la coleccion
    }

    public function getTotal(): float
    {
        // TODO: Calcular suma total de items
        return 0.0;
    }

    public function checkout(): float
    {
        // TODO: Lanzar DomainException si carrito esta vacio; de lo contrario retornar getTotal()
        return 0.0;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Cart\\Domain;

use DomainException;
use InvalidArgumentException;

class ShoppingCart
{
    /**
     * @var array<string, array{productId: string, quantity: int, unitPrice: float}>
     */
    private array $items = [];

    public function addItem(string $productId, int $quantity, float $unitPrice): void
    {
        if ($quantity <= 0) {
            throw new InvalidArgumentException(\'La cantidad agregada debe ser estrictamente mayor a 0.\');
        }

        if ($unitPrice < 0.0) {
            throw new InvalidArgumentException(\'El precio unitario no puede ser negativo.\');
        }

        $id = trim($productId);
        if (isset($this->items[$id])) {
            $this->items[$id][\'quantity\'] += $quantity;
        } else {
            $this->items[$id] = [
                \'productId\' => $id,
                \'quantity\' => $quantity,
                \'unitPrice\' => $unitPrice,
            ];
        }
    }

    public function removeItem(string $productId): void
    {
        $id = trim($productId);
        unset($this->items[$id]);
    }

    public function getTotal(): float
    {
        $total = 0.0;
        foreach ($this->items as $item) {
            $total += $item[\'quantity\'] * $item[\'unitPrice\'];
        }

        return round($total, 2);
    }

    public function checkout(): float
    {
        if (empty($this->items)) {
            throw new DomainException(\'No se puede ejecutar el checkout de un carrito de compras vacio.\');
        }

        return $this->getTotal();
    }
}
',
        'explanation' => 'La clase ShoppingCart representa un Agregado clasico de DDD. Protege activamente sus invariantes (cantidades positivas y precios validos) y utiliza excepciones semanticas de dominio (DomainException) para evitar transacciones invalidas en el momento del checkout.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Domain-Driven Design Pragmatico',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la responsabilidad principal de la Raiz del Agregado (Aggregate Root) en DDD?',
                'options' => [
                    'a' => 'Conectarse directamente al servidor de base de datos MySQL para ejecutar sentencias SELECT.',
                    'b' => 'Ser el unico punto de entrada para todas las modificaciones de datos dentro del limite del agregado, garantizando que todas las invariantes se cumplan en todo momento.',
                    'c' => 'Mapear las rutas de Symfony a controladores Twig.',
                    'd' => 'Convertir objetos PHP en codigo binario nativo.',
                ],
                'correct' => 'b',
                'explanation' => 'La Raiz del Agregado actua como guardian. Los clientes externos no pueden alterar directamente entidades hijas internas sin pasar por la raiz, protegiendo la consistencia transaccional.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Que problema introduce el antipatron del \'Modelo de Dominio Anemico\' (Anemic Domain Model)?',
                'options' => [
                    'a' => 'Hace que las entidades solo tengan datos con getters y setters sin comportamiento de negocio, forzando a que la logica se disperse en controladores o servicios descontrolados.',
                    'b' => 'Provoca que la base de datos se quede sin espacio de almacenamiento.',
                    'c' => 'Obliga a utilizar unicamente variables estaticas en PHP.',
                    'd' => 'Impide la creacion de contenedores Docker en el entorno local.',
                ],
                'correct' => 'a',
                'explanation' => 'En un modelo anemico, las entidades son simples bolsas de datos pasivas. La logica de validacion se duplica en multiples servicios y no hay ninguna garantia de que los datos sean consistentes.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que define a un \'Bounded Context\' (Contexto Delimitado) en el DDD estrategico de Eric Evans?',
                'options' => [
                    'a' => 'Un limite geografico de la ciudad donde opera el centro de datos.',
                    'b' => 'Un limite conceptual explicito dentro del cual un modelo de dominio y un Lenguaje Ubicuo particular tienen un significado unico, estricto y no ambiguo.',
                    'c' => 'La cantidad maxima de memoria RAM asignada al proceso PHP-FPM.',
                    'd' => 'El tiempo maximo de espera en un socket TCP de red.',
                ],
                'correct' => 'b',
                'explanation' => 'En el contexto de Ventas, un \'Cliente\' tiene carrito y direccion de envio; en el contexto de Soporte, ese mismo cliente tiene tickets e historial de quejas. El Bounded Context separa ambos significados para evitar un modelo monolitico ambiguo.',
            ],
        ],
    ],
];
