<?php

declare(strict_types=1);

return [
    'slug' => 'prof-team-communication',
    'title' => 'Trabajo en Equipo, User Stories & Estimacion',
    'module' => 'professional-developer',
    'minutes' => 40,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'En la ingenieria de software profesional, la capacidad tecnica de programar es solo la mitad de la ecuacion. El software de mayor calidad fracasa rotundamente si resuelve el problema equivocado o si el equipo trabaja en silos desarticulados con requerimientos ambiguos. Un desarrollador Senior domina el lenguaje de comunicacion del negocio: la formulacion rigurosa de Historias de Usuario segun la plantilla canonica de Connextra (Como [rol], Quiero [accion], Para [beneficio]), los Criterios de Aceptacion en formato Gherkin (Dado-Cuando-Entonces) y los criterios de calidad INVEST formulados por Bill Wake.

Asimismo, la estimacion agil no es una promesa contractual de fecha fija; es una herramienta probabilistica para calibrar la complejidad y el tamano relativo de las tareas en puntos de historia utilizando la serie de Fibonacci (1, 2, 3, 5, 8, 13). Si una historia supera los 8 puntos, el ingeniero Senior no \'aprieta el paso\'; descompone la historia en unidades mas pequenas e independientes para reducir el riesgo.',
        'problem' => 'En la ingenieria de software profesional, la capacidad tecnica de programar es solo la mitad de la ecuacion. El software de mayor calidad fracasa rotundamente si resuelve el problema equivocado o si el equipo trabaja en silos desarticulados con requerimientos ambiguos. Un desarrollador Senior domina el lenguaje de comunicacion del negocio: la formulacion rigurosa de Historias de Usuario segun la plantilla canonica de Connextra (Como [rol], Quiero [accion], Para [beneficio]), los Criterios de Aceptacion en formato Gherkin (Dado-Cuando-Entonces) y los criterios de calidad INVEST formulados por Bill Wake.',
        'solution' => 'Asimismo, la estimacion agil no es una promesa contractual de fecha fija; es una herramienta probabilistica para calibrar la complejidad y el tamano relativo de las tareas en puntos de historia utilizando la serie de Fibonacci (1, 2, 3, 5, 8, 13). Si una historia supera los 8 puntos, el ingeniero Senior no \'aprieta el paso\'; descompone la historia en unidades mas pequenas e independientes para reducir el riesgo.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Receta del Chef y la Comanda de Mesa',
        'concept' => 'Piensa en como se comunica un mozo de restaurante con la cocina:
1. EL REQUERIMIENTO AMBIGUO (Fallo de Comunicacion): El mozo entra gritando a la cocina: \'Hagan algo rico para la mesa 4\'. El cocinero prepara un lomo asado, pero el cliente resulta ser vegetariano estricto. El plato se tira a la basura y el cliente se va furioso.
2. LA COMANDA ESTRUCTURADA (User Story con Criterios): El mozo anota:
   - Rol: Cliente vegetariano con intolerancia al gluten.
   - Accion: Desea una ensalada tibia con aderezo balsamico.
   - Beneficio: Para cenar de forma segura sin riesgo de alergia.
   - Criterio de Aceptacion: \'Dado que el aderezo tiene salsa de soya, Cuando se prepare, Entonces debe utilizarse salsa de soya certificada sin gluten\'.

El cocinero sabe exactamente que hacer y el mozo sabe exactamente que verificar antes de llevar el plato a la mesa.',
        'ascii_diagram' => 'CRITERIOS INVEST PARA HISTORIAS DE USUARIO DE ALTO VALOR:

  [ I ] - Independent   (Independiente de otras historias del sprint)
  [ N ] - Negotiable    (Negociable: una invitacion a conversar, no un contrato rigido)
  [ V ] - Valuable      (Valiosa: entrega valor medible al usuario o al negocio)
  [ E ] - Estimable     (Estimable: el equipo comprende la complejidad tecnica)
  [ S ] - Small         (Pequena: cabe comodamente dentro de una sola iteracion)
  [ T ] - Testable      (Testeable: posee criterios de aceptacion objetivos)

ESTIMACION CON SERIE DE FIBONACCI:
1, 2, 3  ---> [ SMALL ]      (Claro, directo, bajo riesgo)
5, 8     ---> [ MEDIUM ]     (Complejidad moderada, requiere atencion)
13, 21+  ---> [ NEEDS_SPLIT] (Demasiado grande / incierta. ¡Debe dividirse!)
',
        'analogy' => 'Piensa en como se comunica un mozo de restaurante con la cocina:
1. EL REQUERIMIENTO AMBIGUO (Fallo de Comunicacion): El mozo entra gritando a la cocina: \'Hagan algo rico para la mesa 4\'. El cocinero prepara un lomo asado, pero el cliente resulta ser vegetariano estricto. El plato se tira a la basura y el cliente se va furioso.
2. LA COMANDA ESTRUCTURADA (User Story con Criterios): El mozo anota:
   - Rol: Cliente vegetariano con intolerancia al gluten.
   - Accion: Desea una ensalada tibia con aderezo balsamico.
   - Beneficio: Para cenar de forma segura sin riesgo de alergia.
   - Criterio de Aceptacion: \'Dado que el aderezo tiene salsa de soya, Cuando se prepare, Entonces debe utilizarse salsa de soya certificada sin gluten\'.

El cocinero sabe exactamente que hacer y el mozo sabe exactamente que verificar antes de llevar el plato a la mesa.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En la evaluacion programatica de historias de usuario, la clase UserStoryInvestEvaluator valida que el payload asociativo contenga los atributos canonicos: as_a (rol), i_want (deseo), so_that (beneficio de negocio), acceptance_criteria (lista de condiciones verificables) y story_points (entero positivo de Fibonacci).',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Si los puntos superan 8, el evaluador categoriza la historia como \'NEEDS_SPLIT\', advirtiendo al equipo que la historia es demasiado grande para un sprint y debe descomponerse.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Diferencia entre Definition of Ready (DoR) y Definition of Done (DoD): 1. Definition of Ready: criterios que una historia debe cumplir antes de entrar al sprint (estimada, criterios de aceptacion claros, dependencias resueltas). 2. Definition of Done: criterios que el incremento debe cumplir para considerarse terminado (codigo probado con PHPUnit, linting aprobado en CI, documentacion actualizada y desplegado en staging).',
        ],
    ],
    'video' => [
        'title' => 'How to write good User Stories in Agile',
        'speaker' => 'The Agile Shop',
        'youtube_id' => '7hoGqhb6qAs',
        'duration' => '10 min',
        'description' => 'Explicacion clara y practica sobre la estructura de las Historias de Usuario, como redactar criterios de aceptacion claros y como aplicar los principios INVEST para que el equipo entregue valor continuo.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Agile;

use InvalidArgumentException;

final class UserStoryInvestEvaluator
{
    /**
     * @param array<string, mixed> $storyData
     * @return array{is_invest_compliant: bool, estimation_category: string, missing_elements: list<string>}
     */
    public function evaluateStory(array $storyData): array
    {
        $requiredFields = [\'title\', \'as_a\', \'i_want\', \'so_that\', \'acceptance_criteria\', \'story_points\'];
        $missing = [];

        foreach ($requiredFields as $field) {
            if (!isset($storyData[$field]) || (is_string($storyData[$field]) && trim($storyData[$field]) === \'\')) {
                $missing[] = $field;
            }
        }

        if (in_array(\'title\', $missing, true) || in_array(\'as_a\', $missing, true)) {
            throw new InvalidArgumentException(\'El titulo y el rol de usuario (as_a) son campos obligatorios.\');
        }

        $points = $storyData[\'story_points\'] ?? null;
        if (!is_int($points) || $points <= 0) {
            throw new InvalidArgumentException(\'Los puntos de historia deben ser un entero positivo mayor a 0.\');
        }

        $category = match (true) {
            $points <= 3 => \'SMALL\',
            $points <= 8 => \'MEDIUM\',
            default => \'NEEDS_SPLIT\',
        };

        $isCompliant = empty($missing) && is_array($storyData[\'acceptance_criteria\']) && !empty($storyData[\'acceptance_criteria\']);

        return [
            \'is_invest_compliant\' => $isCompliant,
            \'estimation_category\' => $category,
            \'missing_elements\' => $missing,
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior toma requerimientos vagos y asume lo que el cliente quizo decir, entregando software que nadie usa. El desarrollador Senior cuestiona los requerimientos ambiguos con empatia profesional: exige historias con criterios INVEST, valida el valor de negocio (so_that) y descompone historias gigantescas antes de escribir una sola linea de codigo.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Acepta una historia de 21 puntos y llega al final del sprint con el 40% del trabajo a medias.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Descompone la historia de 21 puntos en 4 historias de 3 y 5 puntos, entregando incrementos funcionales terminados.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Criterios INVEST',
            'source' => 'Extreme Programming Installed / Agile Software Development',
            'quote' => 'INVEST resume los seis atributos de una historia de usuario efectiva: Independiente, Negociable, Valiosa, Estimable, Pequena y Testeable.',
            'author' => 'Bill Wake',
            'explanation' => 'Historias pequenas e independientes minimizan el riesgo de bloqueo durante el sprint.',
        ],
        [
            'topic' => 'La Plantilla Connextra',
            'source' => 'User Stories Applied: For Agile Software Development',
            'quote' => 'Como [rol], quiero [funcion], para [beneficio]. Esta estructura asegura que el valor para el usuario final este en el centro de cada tarea tecnica.',
            'author' => 'Mike Cohn',
            'explanation' => 'Obliga a justificar el para que antes de discutir el como tecnico.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Agile Alliance: User Stories and INVEST Criteria',
            'url' => 'https://www.agilealliance.org/glossary/invest/',
            'type' => 'SPEC',
        ],
        [
            'title' => 'Scrum Guide: Product Backlog Items',
            'url' => 'https://scrumguides.org/',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Evaluador de Historias de Usuario e INVEST (UserStoryInvestEvaluator)',
        'objective' => 'Implementar la clase UserStoryInvestEvaluator con evaluateStory(array $storyData): array, validando la presencia obligatoria de title, as_a, i_want, so_that y story_points > 0 con InvalidArgumentException, categorizando puntos Fibonacci (<=3 SMALL, <=8 MEDIUM, >8 NEEDS_SPLIT) y retornando is_invest_compliant.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Professional\\Agile.
2. Implementa la clase UserStoryInvestEvaluator.
3. Implementa function evaluateStory(array $storyData): array.
4. Si falta title, as_a, i_want, so_that o story_points <= 0, lanza \\InvalidArgumentException.
5. Categoriza $story_points: <= 3 como \'SMALL\', <= 8 como \'MEDIUM\', y mayor a 8 como \'NEEDS_SPLIT\'.
6. Comprueba que acceptance_criteria sea un array no vacio para otorgar is_invest_compliant => true.
7. Retorna un array asociativo con claves is_invest_compliant (bool), estimation_category (string) y missing_elements (array).',
        'filename' => 'src/Professional/Agile/UserStoryInvestEvaluator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que $storyData tenga as_a, i_want y so_that.',
                'Paso 2: Comprueba que story_points sea un entero mayor a 0, de lo contrario lanza InvalidArgumentException.',
                'Paso 3: Evalua los tramos de Fibonacci usando match(true).',
                'Paso 4: Retorna el array estructurado con is_invest_compliant, estimation_category y missing_elements.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] INVEST exige que la historia tenga criterios de aceptacion testeables y tamano estimable.',
            ],
            [
                'text' => '[Pista 2: Estructura] match (true) { $points <= 3 => \'SMALL\', $points <= 8 => \'MEDIUM\', default => \'NEEDS_SPLIT\' };',
            ],
            [
                'text' => '[Pista 3: Snippet] if (!isset($storyData[\'as_a\']) || !isset($storyData[\'i_want\']) || !isset($storyData[\'so_that\'])) { throw new \\InvalidArgumentException(\'Missing fields\'); }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Agile;

use InvalidArgumentException;

class UserStoryInvestEvaluator
{
    /**
     * @param array<string, mixed> $storyData
     * @return array{is_invest_compliant: bool, estimation_category: string, missing_elements: list<string>}
     */
    public function evaluateStory(array $storyData): array
    {
        // TODO: Validar campos obligatorios as_a, i_want, so_that (lanzar InvalidArgumentException)
        // TODO: Validar story_points > 0 (lanzar InvalidArgumentException)
        // TODO: Categorizar puntos en SMALL (<=3), MEDIUM (<=8) o NEEDS_SPLIT (>8)
        // TODO: Retornar resultado estructurado
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Agile;

use InvalidArgumentException;

class UserStoryInvestEvaluator
{
    /**
     * @param array<string, mixed> $storyData
     * @return array{is_invest_compliant: bool, estimation_category: string, missing_elements: list<string>}
     */
    public function evaluateStory(array $storyData): array
    {
        if (empty($storyData[\'title\']) || empty($storyData[\'as_a\']) || empty($storyData[\'i_want\']) || empty($storyData[\'so_that\'])) {
            throw new InvalidArgumentException(\'Faltan campos indispensables de la historia de usuario (as_a, i_want, so_that).\');
        }

        $points = $storyData[\'story_points\'] ?? 0;
        if (!is_int($points) || $points <= 0) {
            throw new InvalidArgumentException(\'Los puntos de historia deben ser un entero positivo.\');
        }

        $category = match (true) {
            $points <= 3 => \'SMALL\',
            $points <= 8 => \'MEDIUM\',
            default => \'NEEDS_SPLIT\',
        };

        $criteria = $storyData[\'acceptance_criteria\'] ?? [];
        $isCompliant = is_array($criteria) && !empty($criteria);

        return [
            \'is_invest_compliant\' => $isCompliant,
            \'estimation_category\' => $category,
            \'missing_elements\' => $isCompliant ? [] : [\'acceptance_criteria\'],
        ];
    }
}
',
        'explanation' => 'La clase UserStoryInvestEvaluator audita la calidad metodologica de los requerimientos. Exige la plantilla canonica de usuario (as_a, i_want, so_that), categoriza el tamano de Fibonacci y alerta inmediatamente si una historia debe dividirse (NEEDS_SPLIT) por sobrepasar los 8 puntos.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Trabajo en Equipo, User Stories & Estimacion',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la razon para formular una Historia de Usuario con la estructura \'Como [rol], Quiero [accion], Para [beneficio]\'?',
                'options' => [
                    'a' => 'Para que el compilador de PHP pueda generar codigo automaticamente.',
                    'b' => 'Para asegurar que todo desarrollo tecnico este anclado a un rol especifico y entregue un valor de negocio medible (el \'Para\'), evitando construir funciones que nadie necesita.',
                    'c' => 'Porque es un requisito de la licencia MIT de software libre.',
                    'd' => 'Para rellenar espacio en la documentacion de Jira.',
                ],
                'correct' => 'b',
                'explanation' => 'La clausula \'Para [beneficio]\' es la mas importante: si el equipo no puede articular el valor de negocio de una tarea, la tarea probablemente no deba construirse.',
            ],
            [
                'id' => 'q2',
                'question' => 'En la estimacion con la serie de Fibonacci, ¿que decision debe tomar un equipo Senior si una historia de usuario se estima en 13 o 21 puntos?',
                'options' => [
                    'a' => 'Aumentar las horas de la jornada laboral de los desarrolladores.',
                    'b' => 'Descomponer la historia en multiples historias mas pequenas e independientes (de 2, 3 o 5 puntos) que puedan entregarse de forma incremental.',
                    'c' => 'Eliminar las pruebas unitarias para acelerar el desarrollo.',
                    'd' => 'Ignorar los puntos y comprometerse a terminarla en 3 dias.',
                ],
                'correct' => 'b',
                'explanation' => 'Las historias de 13 o mas puntos conllevan demasiada incertidumbre y riesgo de bloqueo. Dividirlas en historias pequenas reduce la varianza y permite entregas continuas de valor.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Que atributo de los criterios INVEST significa que una historia de usuario puede ser desarrollada y entregada sin depender obligatoriamente de otra historia del mismo sprint?',
                'options' => [
                    'a' => 'I - Independent (Independiente)',
                    'b' => 'N - Negotiable (Negociable)',
                    'c' => 'V - Valuable (Valiosa)',
                    'd' => 'T - Testable (Testeable)',
                ],
                'correct' => 'a',
                'explanation' => 'Independent significa que las historias minimizan dependencias mutuas, evitando cuellos de botella donde un desarrollador queda bloqueado esperando que otro termine.',
            ],
        ],
    ],
];
