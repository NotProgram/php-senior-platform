<?php

declare(strict_types=1);

return [
    'slug' => 'se-sdlc-requirements',
    'title' => 'SDLC, Requerimientos & User Stories',
    'module' => 'Ingeniería de Software',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'El Ciclo de Vida del Software (SDLC - Software Development Life Cycle) es el proceso sistemático para planificar, diseñar, construir, probar y desplegar software resiliente. Desarrollar software sin una especificación rigurosa es la causa número 1 de sobrecostos, deuda técnica y sistemas que colapsan en producción.',
        'problem' => 'En equipos con baja madurez, los clientes piden requerimientos ambiguos ("quiero un botón de reportes rápidos"). El equipo asume detalles sin validar, invierte semanas construyendo soluciones sobredimensionadas, y en producción el servidor colapsa por memoria o no resuelve la necesidad real del negocio.',
        'solution' => 'Descomponer requerimientos en User Stories atómicas mediante los criterios INVEST, y definir Requerimientos No Funcionales (NFR) cuantitativos respaldados por Service Level Agreements (SLAs) con métricas de percentiles de latencia (P95/P99).',
        'problem_label' => 'El Problema: Requerimientos Ambiguos y Sobreingeniería Costosa',
        'solution_label' => 'La Solución Senior: Criterios INVEST y Acuerdos de Nivel de Servicio (SLA/P95)',
    ],
    'mental_model' => [
        'title' => 'El Plano del Arquitecto vs Pedir \'Una Casa Bonita\'',
        'analogy' => 'Imagina que contratas a un constructor y le pides "una casa bonita y rápida". El constructor te entrega un palacio de mármol de tres pisos con piscina climatizada. Cuando te entrega la factura por 500,000 dólares, exclamas horrorizado: "¡Pero yo solo quería una cabaña de madera para guardar herramientas!". En ingeniería de software ocurre exactamente lo mismo si no separamos dos dimensiones fundamentales: 1) **Requerimiento Funcional** (User Story): QUÉ capacidad de negocio necesita el usuario. 2) **Requerimiento No Funcional (NFR / SLA)**: CÓMO debe responder el sistema bajo carga medido en milisegundos de latencia percentil.',
        'ascii_diagram' => 'PETICIÓN DEL CLIENTE: "Necesitamos reportes más rápidos"
                    │
   ┌────────────────┴────────────────┐
   ▼                                 ▼
[REQUERIMIENTO FUNCIONAL]        [REQUERIMIENTO NO FUNCIONAL]
¿QUÉ HACE? (User Story / INVEST) ¿BAJO QUÉ LÍMITES? (SLA / P95)
"Como analista financiero        "El 95% de las exportaciones (P95)
 quiero exportar transacciones    debe resolverse en < 500 ms y
 en formato CSV para conciliar"   con un pico de memoria < 64 MB"
   │                                 │
   ▼                                 ▼
Validado por el Product Owner     Validado por el Arquitecto Senior',
        'key_concept' => 'Un requerimiento sin métrica de rendimiento (SLA/P95) no es una especificación de ingeniería; es solo una lista de deseos.',
    ],
    'internals' => [
        'title' => 'Las 6 Fases del SDLC y la Anatomía de una Especificación',
        'steps' => [
            [
                'phase' => '1. Descubrimiento & Requerimientos Funcionales vs No Funcionales',
                'description' => 'Entrevistas de dominio para separar lo que el usuario ve (capacidades funcionales) de los atributos de calidad arquitectónica (disponibilidad, latencia, throughput, seguridad y recuperabilidad).',
            ],
            [
                'phase' => '2. Deconstrucción Atómica con Criterios INVEST',
                'description' => 'Las Historias de Usuario deben ser Independientes, Negociables, Valiosas, Estimables, Pequeñas (Small) y Testeables. Si una historia no tiene criterios de aceptación medibles, no entra al sprint.',
            ],
            [
                'phase' => '3. Definición de Contratos SLA, SLO y SLI',
                'description' => 'SLI (Indicador de Nivel de Servicio): métrica real medida. SLO (Objetivo): meta interna del equipo (ej. 99% de peticiones exitosas). SLA (Acuerdo): compromiso formal con penalización económica.',
            ],
            [
                'phase' => '4. Medición Estadística: La Falacia del Promedio y los Percentiles',
                'description' => 'En producción, la media aritmética oculta las catástrofes. Si 9 peticiones tardan 10ms y 1 tarda 10,000ms, el promedio es 1,009ms. El Percentil 95 (P95) revela la experiencia real del 95% de tus usuarios.',
            ],
            [
                'phase' => '5. TDD y Pruebas Automatizadas de Aceptación',
                'description' => 'Convertir los Criterios de Aceptación en tests de integración y funcionales antes de escribir el código de negocio.',
            ],
            [
                'phase' => '6. Despliegue Continuo (CI/CD) y Observabilidad Activa',
                'description' => 'Automatizar la verificación de calidad en pipelines de CI y monitorear latencias P95/P99 en tiempo real con alertas tempranas.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Criterios INVEST: Cómo desarmar requerimientos gigantes',
            'icon' => 'layers',
            'content' => 'Los criterios INVEST fueron formulados por Bill Wake para garantizar que las historias de usuario aporten valor inmediato sin bloquear al equipo de ingeniería:

• **I - Independent (Independiente):** La historia no debe depender de otra historia en paralelo. Si la historia A bloquea a la B, combínalas o rediseña la frontera del dominio.
• **N - Negotiable (Negociable):** No es un contrato rígido. Los detalles técnicos se discuten entre el desarrollador y el Product Owner buscando la solución más simple y eficiente.
• **V - Valuable (Valiosa):** Debe entregar valor tangible a un usuario real o resolver un riesgo de infraestructura crítico. No escribas historias técnicas como "Crear tabla en MySQL"; escribe "Persistir transacciones de pago para auditoría".
• **E - Estimable (Estimable):** El equipo debe comprender el alcance lo suficiente como para dimensionar el esfuerzo técnico. Si no se puede estimar, se requiere un "Spike" (investigación técnica previa de tiempo acotado).
• **S - Small (Pequeña):** Una historia debe completarse en 1 a 3 días de desarrollo. Si dura dos semanas, es un Epic disfrazado que acumula riesgo.
• **T - Testable (Testeable):** Debe incluir Criterios de Aceptación claros en formato Given-When-Then (Dado-Cuando-Entonces). Si no se puede probar de forma binaria (Pasa/Falla), está mal redactada.',
            'takeaways' => 'Si una historia no cumple con la \'T\' (Testeable) o la \'S\' (Small), recházala en el refinamiento técnico. Aceptar historias ambiguas es la causa #1 de sprints fallidos.',
        ],
        [
            'title' => 'Matemática de Rendimiento en Producción: Por qué el Promedio Miente',
            'icon' => 'cpu',
            'content' => 'En sistemas web concurrentes, nunca evalúes el rendimiento usando el promedio (media aritmética). Observa el siguiente caso real:

Supongamos 10 peticiones web medidas en milisegundos:
`[45, 50, 52, 48, 55, 49, 51, 53, 47, 9800]`

• **Promedio (Mean):** (45 + 50 + ... + 9800) / 10 = **1,075 ms (~1.1 segundos)**.
• **Interpretación ingenua:** "El sistema responde en torno a 1 segundo, es aceptable".
• **Realidad técnica:** El 90% de los usuarios experimentó una respuesta ultra-rápida de 50 ms. Pero un 10% sufrió una congelación de casi 10 segundos debido a un lock de base de datos o timeout externo. El promedio ocultó la verdad a ambos grupos.

**Percentil 95 (P95):**
Representa el valor por debajo del cual cae el 95% de las observaciones. Para calcularlo en un conjunto de datos:
1. Se ordenan las latencias de menor a mayor.
2. Se calcula la posición del percentil: `index = ceil(N * 0.95) - 1`.
3. El valor en dicho índice es el P95. Si ese valor supera el SLA acordado, el sistema está degradado.',
            'code' => '// Cálculo algorítmico del P95 en PHP 8.4
$latencies = [45.0, 50.0, 52.0, 48.0, 55.0, 49.0, 51.0, 53.0, 47.0, 9800.0];
sort($latencies, SORT_NUMERIC);

$count = count($latencies);
$p95Index = (int) ceil($count * 0.95) - 1;
$p95Value = $latencies[max(0, min($p95Index, $count - 1))];

// $p95Value será 9800.0 ms -> Alerta inmediata de violación de SLA!',
            'takeaways' => 'Los SLAs modernos se pactan siempre sobre el P95 o P99. Un Senior diseña para los percentiles de cola (tail latencies).',
        ],
    ],
    'video' => [
        'title' => 'Clean Code & Software Craftsmanship - Lesson 1',
        'speaker' => 'Robert C. Martin (Uncle Bob)',
        'youtube_id' => '7EmboKQH8lM',
        'duration' => '45 min',
        'description' => 'Uncle Bob explica el verdadero costo del código sucio, la rigidez arquitectónica y por qué la calidad de código es un imperativo profesional en el SDLC.',
        'chapters' => [
            '00:00' => 'Por qué importa la calidad del código',
            '11:20' => 'La trampa de la velocidad a corto plazo',
            '24:45' => 'Profesionalismo y estimaciones técnicas',
            '36:10' => 'El ciclo de vida del software mantenible',
        ],
    ],
    'architecture_code' => [
        'filename' => 'UserStorySpecification.php',
        'title' => 'Especificación de User Stories y Criterios INVEST en PHP 8.4',
        'tag' => 'Agile & Requirements Architecture',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Modelo inmutable de especificación técnica para Historias de Usuario.
 * Garantiza que todo requerimiento cumpla los criterios INVEST
 * y cuente con límites cuantitativos de rendimiento (SLA).
 */
final readonly class UserStorySpecification
{
    /**
     * @param list<string> $acceptanceCriteria Criterios de aceptación en formato Given-When-Then.
     * @param int|null $maxLatencyMs Límite de latencia SLA acordado en milisegundos (NFR).
     */
    public function __construct(
        public string $role,
        public string $goal,
        public string $businessValue,
        public array $acceptanceCriteria = [],
        public ?int $maxLatencyMs = null
    ) {}

    /**
     * Valida que la historia cumpla con los principios INVEST (Valuable y Testable).
     */
    public function isInvestCompliant(): bool
    {
        return trim($this->role) !== \'\'
            && trim($this->goal) !== \'\'
            && trim($this->businessValue) !== \'\'
            && count($this->acceptanceCriteria) > 0;
    }

    /**
     * Verifica si se definió un Requerimiento No Funcional (SLA de latencia).
     */
    public function hasNonFunctionalSla(): bool
    {
        return $this->maxLatencyMs !== null && $this->maxLatencyMs > 0;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un ingeniero Senior nunca acepta un ticket que diga "optimizar la aplicación". Pregunta: ¿Cuál es el SLI actual? ¿Cuál es el SLO objetivo? ¿Qué percentil estamos atacando (P50 o P99)? Diseña la arquitectura para que los requerimientos no funcionales se midan automáticamente en el pipeline de observabilidad.',
        'critical_questions' => [
            '¿Esta historia de usuario tiene criterios de aceptación medibles en formato Dado-Cuando-Entonces?',
            '¿Cuál es el SLA de latencia P95 y consumo de memoria acordado para este endpoint?',
            '¿Qué pasa si un servicio externo del que dependemos tarda 15 segundos en responder? ¿Tenemos timeout y fallback definido en el requerimiento?',
            '¿Podemos dividir esta historia de 5 días en dos historias de 2 días que entreguen valor antes?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'El Product Owner solicita: "Los clientes corporativos deben poder descargar un reporte con todas sus órdenes de compra en un solo click".',
        'junior' => [
            'approach' => 'Crea un controlador que hace un SELECT * masivo sin paginación, genera un archivo Excel con una librería pesada en memoria y lo devuelve sincrónicamente en la misma petición HTTP.',
            'flaws' => [
                'Cuando un cliente con 50,000 órdenes ejecuta la descarga, el script agota los 128MB de memoria de PHP-FPM, la petición da HTTP 504 Gateway Timeout y el servidor colapsa por OOM.',
            ],
        ],
        'senior' => [
            'approach' => 'Desglosa el requerimiento: 1) User Story funcional para exportación asíncrona. 2) NFR: el endpoint HTTP solo despacha un mensaje a Symfony Messenger (RabbitMQ/Redis) y responde en < 50ms (P95). 3) Un worker en background genera el archivo por lotes (streaming) y notifica al usuario por email con un enlace seguro firmado.',
            'rationale' => [
                'Cero consumo de memoria en peticiones HTTP concurrentes, SLA de < 50ms garantizado y tolerancia a fallos con reintentos automáticos en colas.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Criterios INVEST en Requerimientos',
            'source' => 'Extreme Programming Installed / Agile Software Development',
            'quote' => 'Una historia de usuario no es una especificación rígida; es una promesa de conversación centrada en el valor de negocio medible.',
            'author' => 'Bill Wake & Kent Beck',
            'explanation' => 'Descomponer requerimientos en unidades independientes y testeables previene desbordamientos de sprint y ambigüedad funcional.',
        ],
        [
            'topic' => 'Métricas de Percentiles y Latencia de Cola',
            'source' => 'Site Reliability Engineering: How Google Runs Production Systems',
            'quote' => 'Si mides el promedio, estás ignorando deliberadamente a tus clientes más descontentos. Los percentiles P95 y P99 son donde residen los verdaderos cuellos de botella.',
            'author' => 'Betsy Beyer, Chris Jones (Google SRE Team)',
            'explanation' => 'Monitorear percentiles de cola permite detectar saturación de sockets de red y cuellos de botella antes de que causen caídas totales.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Agile Alliance: Guía Oficial de Criterios INVEST',
            'url' => 'https://www.agilealliance.org/glossary/invest/',
            'description' => 'Definición formal de los 6 atributos esenciales para historias de usuario de alto valor.',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Google SRE Book: Service Level Objectives (SLAs, SLOs, SLIs)',
            'url' => 'https://sre.google/sre-book/service-level-objectives/',
            'description' => 'Capítulo canónico de Google sobre cómo definir acuerdos de nivel de servicio cuantitativos.',
            'type' => 'BOOK',
        ],
    ],
    'exercise' => [
        'type' => 'specification',
        'language' => 'markdown',
        'language_badge' => 'Markdown / BDD',
        'filename' => 'user-story-specification.md',
        'title' => 'Taller de Especificación: User Story, Criterios BDD y SLA No Funcional',
        'objective' => 'Redactar una especificación técnica formal de requerimientos para el caso de uso "Cancelación de Pedidos en E-commerce", definiendo la Historia de Usuario (Connextra), Criterios de Aceptación BDD (Dado/Cuando/Entonces), Requerimientos No Funcionales (SLA p95) y verificando los atributos de calidad INVEST sin necesidad de código PHP.',
        'instructions' => 'En este taller de ingeniería no necesitas programar una clase PHP. Tu rol como ingeniero es formalizar el requerimiento en el editor de especificación Markdown: 1) Redacta la Historia de Usuario siguiendo la fórmula Connextra ("Como [rol], Quiero [acción], Para [beneficio]"). 2) Formula dos escenarios de Criterios de Aceptación en formato Gherkin ("Dado que..., Cuando..., Entonces..."). 3) Define un Requerimiento No Funcional cuantitativo (ej. latencia p95 < 300ms o disponibilidad 99.9%). 4) Audita y certifica la lista de verificación INVEST.',
        'guide' => [
            'explanation' => 'En la fase de análisis del SDLC no empezamos tirando código sin rumbo; especificamos con rigor el comportamiento esperado. Este taller entrena tu habilidad para redactar requerimientos inequívocos que guíen al equipo de desarrollo y QA antes de comenzar a codificar.',
            'steps' => [
                'Paso 1: Define el rol del usuario, la acción concreta y el beneficio de negocio usando la fórmula Connextra (Como / Quiero / Para).',
                'Paso 2: Formula al menos dos criterios de aceptación BDD (Camino Feliz y Caso Borde) con la sintaxis Dado que / Cuando / Entonces.',
                'Paso 3: Establece un requerimiento no funcional medible (SLA de latencia en milisegundos o disponibilidad en %).',
                'Paso 4: Revisa los 6 criterios INVEST para certificar que la historia es apta para el sprint backlog.',
            ],
            'useful_functions' => [
                [
                    'name' => 'Fórmula Connextra',
                    'desc' => 'Como [rol de usuario] + Quiero [capacidad del sistema] + Para [valor de negocio medible].',
                ],
                [
                    'name' => 'Sintaxis Gherkin (BDD)',
                    'desc' => 'Dado que [precondición/estado inicial] + Cuando [acción disparada] + Entonces [resultado observable].',
                ],
                [
                    'name' => 'Métrica SLA / NFR',
                    'desc' => 'Percentil de latencia (ej. p95 < 300ms) y disponibilidad de servicio (ej. 99.95%).',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'Estructura Connextra',
                'text' => 'Asegúrate de incluir las tres cláusulas: Como [rol], Quiero [acción] y Para [beneficio].',
                'snippet' => 'Como: Cliente registrado en la tienda\nQuiero: Cancelar mi pedido pendiente desde mi panel\nPara: Recuperar mi dinero rápidamente sin llamar a soporte',
            ],
            [
                'label' => 'Criterios de Aceptación Gherkin',
                'text' => 'Describe el estado inicial (Dado que), la interacción (Cuando) y la respuesta del sistema (Entonces).',
                'snippet' => 'Dado que: El pedido está en preparación\nCuando: Pulso cancelar y confirmo\nEntonces: Se cancela la orden, se repone el stock y se reembolsa el saldo',
            ],
            [
                'label' => 'Requerimiento No Funcional (SLA)',
                'text' => 'Todo requerimiento de nivel profesional debe incluir un límite de latencia medible (ej. p95 < 300ms).',
                'snippet' => 'Latencia: p95 < 250ms para la confirmación de cancelación\nDisponibilidad: 99.95%',
            ],
        ],
        'starter_code' => '# ESPECIFICACIÓN TÉCNICA DE REQUERIMIENTOS (SDLC)

## 1. HISTORIA DE USUARIO (Formato Connextra)
Como: [Define el rol de usuario o persona]
Quiero: [Define la acción o funcionalidad requerida]
Para: [Define el beneficio directo o valor de negocio medible]

## 2. CRITERIOS DE ACEPTACIÓN (Formato BDD / Gherkin)
- Escenario 1 (Camino Feliz): Cancelación de pedido en estado pendiente
  Dado que: [Precondición o estado inicial del pedido]
  Cuando: [Acción que ejecuta el usuario]
  Entonces: [Resultado observable y cambio de estado del sistema]

- Escenario 2 (Caso Borde): Intento de cancelación de pedido ya despachado
  Dado que: [Estado del pedido cuando ya no es cancelable]
  Cuando: [Intento de cancelación por parte del usuario]
  Entonces: [Mensaje del sistema y alternativa ofrecida al usuario]

## 3. REQUERIMIENTO NO FUNCIONAL (SLA & Rendimiento)
- Latencia: [Métrica cuantitativa en milisegundos, ej. p95 < 300ms]
- Disponibilidad: [Porcentaje de disponibilidad requerido, ej. 99.9%]
- Confiabilidad / Idempotencia: [Garantía técnica ante fallos de red o reintentos]

## 4. AUDITORÍA DE CRITERIOS INVEST
- [x] Independiente: No bloquea ni depende de otras historias del sprint.
- [x] Negociable: Los textos y detalles de UI son discutibles con el equipo.
- [x] Valiosa: Reduce fricción y tickets al equipo de soporte.
- [x] Estimable: El equipo técnico puede estimar el esfuerzo con precisión.
- [x] Pequeña: Se puede completar y probar dentro del sprint.
- [x] Testeable: Posee criterios de aceptación objetivos verificables por QA.
',
        'solution_code' => '# ESPECIFICACIÓN TÉCNICA DE REQUERIMIENTOS (SDLC)

## 1. HISTORIA DE USUARIO (Formato Connextra)
Como: Cliente registrado con compras activas en la tienda online
Quiero: Cancelar mi pedido directamente desde mi panel de compras mientras esté pendiente
Para: Recuperar mi dinero de inmediato si cometí un error sin necesidad de contactar a soporte

## 2. CRITERIOS DE ACEPTACIÓN (Formato BDD / Gherkin)
- Escenario 1 (Camino Feliz): Cancelación de pedido en estado pendiente
  Dado que: Tengo un pedido en estado "Pendiente de despacho" y saldo cobrado
  Cuando: Hago clic en "Cancelar Pedido" y confirmo en el diálogo de advertencia
  Entonces: El pedido cambia a "Cancelado", se repone el stock y se dispara el reembolso automático

- Escenario 2 (Caso Borde): Intento de cancelación de pedido ya despachado
  Dado que: Mi pedido ya se encuentra en estado "En tránsito"
  Cuando: Intento hacer clic en el botón de cancelación
  Entonces: El sistema muestra un mensaje indicando que el pedido ya fue enviado y me ofrece el enlace de "Solicitar Devolución"

## 3. REQUERIMIENTO NO FUNCIONAL (SLA & Rendimiento)
- Latencia: El endpoint de confirmación de cancelación debe responder en un percentil p95 < 250ms
- Disponibilidad: Disponibilidad del servicio de órdenes y pagos del 99.95%
- Confiabilidad / Idempotencia: Uso obligatorio de Idempotency-Key para prevenir duplicación de reembolsos ante fallos de conexión

## 4. AUDITORÍA DE CRITERIOS INVEST
- [x] Independiente: Puede desarrollarse y desplegarse sin esperar por el módulo de devoluciones.
- [x] Negociable: El plazo exacto de cancelación (ej. 30 min) se puede ajustar con negocio.
- [x] Valiosa: Disminuye llamadas al contact center en un 35% y mejora el NPS.
- [x] Estimable: Estimada en 3 Story Points por el equipo de backend y frontend.
- [x] Pequeña: Diseñada para completarse en un único sprint de 2 semanas.
- [x] Testeable: Cada escenario cuenta con pasos Given-When-Then testeables con Behat / Cypress.
',
        'explanation' => 'Esta especificación técnica formaliza el contrato de un requerimiento: vincula el valor de negocio de la Historia de Usuario con criterios de aceptación Gherkin BDD testeables y SLAs cuantitativos no funcionales, garantizando que el equipo construya exactamente lo necesario sin ambigüedad.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: SDLC, INVEST y Métricas de Rendimiento',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Qué significa la letra "T" en los criterios INVEST para Historias de Usuario?',
                'options' => [
                    'a' => 'Throughput: La historia debe procesar al menos 1,000 requests por segundo.',
                    'b' => 'Time-boxed: La historia debe implementarse estrictamente en menos de 2 horas.',
                    'c' => 'Testable: La historia debe contar con criterios de aceptación claros y verificables mediante pruebas objetivas.',
                    'd' => 'Transactional: Toda la historia debe ejecutarse dentro de una transacción de base de datos.',
                ],
                'correct' => 'c',
                'explanation' => 'Testable significa que deben existir criterios de aceptación objetivos (Given-When-Then) que permitan verificar de forma binaria si la historia está completa.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Por qué un ingeniero Senior prefiere medir el Percentil 95 (P95) en lugar del Promedio (Media) en endpoints críticos?',
                'options' => [
                    'a' => 'Porque el promedio requiere más ciclos de CPU para calcularse que un percentil.',
                    'b' => 'Porque el promedio oculta las latencias extremas sufridas por los usuarios más lentos, mientras que el P95 expone la degradación del peor 5% de las peticiones.',
                    'c' => 'Porque los percentiles solo funcionan con bases de datos relacionales.',
                    'd' => 'Porque los navegadores web descartan automáticamente las métricas que usan el promedio.',
                ],
                'correct' => 'b',
                'explanation' => 'La media aritmética diluye los outliers extremos. El P95 garantiza que el 95% de tus usuarios experimente un rendimiento dentro del SLA acordado.',
            ],
            [
                'id' => 'q3',
                'question' => 'En el contexto de contratos de servicio, ¿cuál es la diferencia exacta entre SLO y SLA?',
                'options' => [
                    'a' => 'SLO es un objetivo interno del equipo de ingeniería; SLA es un acuerdo formal con el cliente o negocio que conlleva penalizaciones o reembolsos si se incumple.',
                    'b' => 'SLO aplica solo a frontend y SLA aplica solo a backend.',
                    'c' => 'SLO y SLA son sinónimos idénticos según el estándar ISO 27001.',
                    'd' => 'SLA es una herramienta de software y SLO es un lenguaje de programación.',
                ],
                'correct' => 'a',
                'explanation' => 'El SLO (Service Level Objective) es la meta técnica que guía la ingeniería; el SLA (Service Level Agreement) es el compromiso contractual con consecuencias legales o financieras.',
            ],
        ],
    ],
];
