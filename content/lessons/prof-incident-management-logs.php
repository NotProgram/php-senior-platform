<?php

declare(strict_types=1);

return [
    'slug' => 'prof-incident-management-logs',
    'title' => 'Gestion de Incidentes, Observabilidad & Postmortems',
    'module' => 'professional-developer',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En la operacion de sistemas en produccion, los incidentes mayores (Outages) son inevitables. Lo que distingue a una organizacion de ingenieria mediocre de una organizacion madura es la cultura de gestion del incidente y el proceso posterior de aprendizaje.

El pilar central de la ingenieria de confiabilidad de sitios (SRE, Site Reliability Engineering, formalizado por Google) es el Postmortem Sin Culpas (Blameless Postmortem). Cuando ocurre una caida en produccion, el instinto toxico es buscar un culpable: \'El desarrollador X cometio un error al hacer merge\'. La cultura SRE asume que los seres humanos son falibles por naturaleza. Si una sola persona pudo tirar produccion, el fallo NO es de la persona; es del SISTEMA que carecia de tests en CI, validaciones automaticas, canary deployments o rollback automatico.

En esta leccion implementamos un auditor de postmortems sin culpas (BlamelessPostmortemAuditor) que valida la completitud formal del informe, rechaza cualquier intento de atribucion individual de culpa y verifica que existan acciones de mitigacion sistemica para garantizar que la causa raiz jamas vuelva a repetirse.',
        'problem' => 'En la operacion de sistemas en produccion, los incidentes mayores (Outages) son inevitables. Lo que distingue a una organizacion de ingenieria mediocre de una organizacion madura es la cultura de gestion del incidente y el proceso posterior de aprendizaje.',
        'solution' => 'El pilar central de la ingenieria de confiabilidad de sitios (SRE, Site Reliability Engineering, formalizado por Google) es el Postmortem Sin Culpas (Blameless Postmortem). Cuando ocurre una caida en produccion, el instinto toxico es buscar un culpable: \'El desarrollador X cometio un error al hacer merge\'. La cultura SRE asume que los seres humanos son falibles por naturaleza. Si una sola persona pudo tirar produccion, el fallo NO es de la persona; es del SISTEMA que carecia de tests en CI, validaciones automaticas, canary deployments o rollback automatico.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Investigacion de la Junta Nacional de Seguridad en el Transporte (NTSB)',
        'concept' => 'Piensa en como se investiga un accidente en la aviacion comercial moderna:
1. LA BUSQUEDA DE CULPABLES (El Modelo Obsoleto y Toxico): Si un avion tiene un aterrizaje forzoso, el juez dice: \'El piloto se equivoco al presionar el boton equivocado; despidanlo\'. El piloto se va, pero el boton sigue colocado al lado del interruptor de luces. Tres meses despues, otro piloto comete el mismo error y mueren pasajeros.
2. LA INVESTIGACION SIN CULPAS DE LA NTSB: Los investigadores no buscan culpar a los pilotos; buscan la causa sistemica:
   - ¿Por que la palanca de tren de aterrizaje y el interruptor de luces tienen exactamente la misma forma fisica?
   - ¿Por que la computadora de abordo no emitio una alarma sonora antes de confirmar la maniobra?
   - Accion de Remediacion: Redisenar la palanca con forma de rueda de goma y exigir confirmacion doble en la cabina.

Al eliminar el miedo al castigo, los pilotos reportan sus propios incidentes voluntariamente, haciendo que la aviacion sea el medio de transporte mas seguro del planeta. Exactamente lo mismo ocurre con los incidentes de software.',
        'ascii_diagram' => 'FLUJO DE UN POSTMORTEM SIN CULPAS (Blameless Postmortem):

                     [ INCIDENTE EN PRODUCCION ]
                                  |
                                  v
+-------------------------------------------------------------+
| 1. MITIGACION Y RESTAURACION INMEDIATA                      |
|    - El foco es recuperar el servicio (Rollback, Failover)  |
|    - No se busca la causa raiz durante el fuego             |
+-------------------------------------------------------------+
                                  |
                                  v
+-------------------------------------------------------------+
| 2. ANALISIS DE CAUSA RAIZ SISTEMICA (Los 5 Por Que)         |
|    - ¿Por que fallo? La base de datos se saturo.            |
|    - ¿Por que se saturo? Se lanzo una query sin indice.     |
|    - ¿Por que llego a prod? No habia linter de SQL en CI.    |
|    -> CAUSA RAIZ: Ausencia de compuerta automatizada en CI  |
|    -> PROHIBIDO: "Error del empleado / Juan fue culpable"   |
+-------------------------------------------------------------+
                                  |
                                  v
+-------------------------------------------------------------+
| 3. ACCIONES PREVENTIVAS CON RESPONSABLES (Action Items)     |
|    - Tarea 1: Agregar chequeo de indices en GitHub Actions  |
|    - Tarea 2: Configurar alerta P99 en Datadog / Prometheus |
+-------------------------------------------------------------+
',
        'analogy' => 'Piensa en como se investiga un accidente en la aviacion comercial moderna:
1. LA BUSQUEDA DE CULPABLES (El Modelo Obsoleto y Toxico): Si un avion tiene un aterrizaje forzoso, el juez dice: \'El piloto se equivoco al presionar el boton equivocado; despidanlo\'. El piloto se va, pero el boton sigue colocado al lado del interruptor de luces. Tres meses despues, otro piloto comete el mismo error y mueren pasajeros.
2. LA INVESTIGACION SIN CULPAS DE LA NTSB: Los investigadores no buscan culpar a los pilotos; buscan la causa sistemica:
   - ¿Por que la palanca de tren de aterrizaje y el interruptor de luces tienen exactamente la misma forma fisica?
   - ¿Por que la computadora de abordo no emitio una alarma sonora antes de confirmar la maniobra?
   - Accion de Remediacion: Redisenar la palanca con forma de rueda de goma y exigir confirmacion doble en la cabina.

Al eliminar el miedo al castigo, los pilotos reportan sus propios incidentes voluntariamente, haciendo que la aviacion sea el medio de transporte mas seguro del planeta. Exactamente lo mismo ocurre con los incidentes de software.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En la evaluacion de un reporte de postmortem con BlamelessPostmortemAuditor: 1. El informe debe incluir campos obligatorios: incident_id, severity, time_to_resolve_minutes, root_cause y action_items.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => '2. Se comprueba la cultura sin culpas: si el texto de root_cause contiene la palabra \'culpable\' o atribuciones individuales en lugar de terminos sistemicos o blameless, el auditor rechaza el reporte.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => '3. Se verifica si el tiempo de resolucion excedio el SLO (Service Level Objective, ej.',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => '60 minutos) marcando slo_breached.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Metricas doradas de observabilidad segun Google SRE: 1. Latencia: tiempo que tarda en servirse una peticion. 2. Trafico: cantidad de demanda sobre el sistema (RPS). 3. Errores: tasa de peticiones que fallan (HTTP 5xx). 4. Saturacion: que tan lleno esta el recurso mas restringido (CPU, memoria RAM, conexiones de base de datos).',
        ],
    ],
    'video' => [
        'title' => 'SRE Incident Management: Google\'s Reliability Approach',
        'speaker' => 'CodeLucky',
        'youtube_id' => 'vNm4XnfVu0Y',
        'duration' => '15 min',
        'description' => 'Explicacion profunda sobre la disciplina SRE de Google para la gestion de incidentes en produccion, resolucion sin panico, niveles de severidad y redaccion de postmortems sin culpas para fortalecer la ingenieria.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Incident;

use InvalidArgumentException;

final class BlamelessPostmortemAuditor
{
    /**
     * @param array<string, mixed> $report
     * @return array{is_approved: bool, slo_breached: bool, action_items_count: int, rejection_reason: ?string}
     */
    public function auditPostmortem(array $report): array
    {
        $requiredKeys = [\'incident_id\', \'severity\', \'root_cause\', \'time_to_resolve_minutes\', \'action_items\'];
        foreach ($requiredKeys as $key) {
            if (!isset($report[$key])) {
                throw new InvalidArgumentException(sprintf(\'El reporte carece del campo obligatorio "%s".\', $key));
            }
        }

        $id = trim((string) $report[\'incident_id\']);
        if ($id === \'\') {
            throw new InvalidArgumentException(\'El identificador del incidente no puede estar vacio.\');
        }

        $timeToResolve = $report[\'time_to_resolve_minutes\'];
        if (!is_int($timeToResolve) || $timeToResolve < 0) {
            throw new InvalidArgumentException(\'El tiempo de resolucion debe ser un entero positivo o cero.\');
        }

        $rootCause = strtolower((string) $report[\'root_cause\']);

        // Deteccion de cultura toxica / culpabilidad individual
        if (str_contains($rootCause, \'culpable\') || str_contains($rootCause, \'error de empleado\')) {
            return [
                \'is_approved\' => false,
                \'slo_breached\' => false,
                \'action_items_count\' => 0,
                \'rejection_reason\' => \'El reporte fue rechazado: atribuye culpas individuales violando la cultura blameless.\',
            ];
        }

        /** @var list<mixed> $actionItems */
        $actionItems = is_array($report[\'action_items\']) ? $report[\'action_items\'] : [];
        if (empty($actionItems)) {
            return [
                \'is_approved\' => false,
                \'slo_breached\' => false,
                \'action_items_count\' => 0,
                \'rejection_reason\' => \'El reporte debe incluir al menos una accion de remediacion preventiva.\',
            ];
        }

        $sloBreached = ($timeToResolve > 60);

        return [
            \'is_approved\' => true,
            \'slo_breached\' => $sloBreached,
            \'action_items_count\' => count($actionItems),
            \'rejection_reason\' => null,
        ];
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior busca a quien culpar cuando produccion se cae, provocando que la gente oculte los errores por miedo. El desarrollador Senior lidera con cultura sin culpas (Blameless): asume que los errores humanos son sintomas de fallas de diseno en el sistema, fomenta la transparencia y transforma cada incidente en mejoras automaticas de pipelines y monitoreo.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Escribe en el informe: \'El incidente ocurrio porque Juan corrio una migracion sin probarla\'.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Escribe en el informe: \'El incidente ocurrio porque el pipeline permitia ejecutar migraciones destructivas sin confirmacion previa\'.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Postmortems Sin Culpas',
            'source' => 'Site Reliability Engineering: How Google Runs Production Systems',
            'quote' => 'Para que un postmortem sea efectivo, debe ser sin culpas (blameless). Asume que todos los involucrados actuaron de buena fe con la informacion que tenian en ese momento.',
            'author' => 'Google SRE Team',
            'explanation' => 'Castigar a las personas solo ensena al equipo a ocultar los problemas futuros.',
        ],
        [
            'topic' => 'Ingenieria de Resiliencia',
            'source' => 'Human Error',
            'quote' => 'No podemos cambiar la condicion humana, pero podemos cambiar las condiciones bajo las cuales los humanos trabajan para que los errores sean menos frecuentes y tengan menor impacto.',
            'author' => 'James Reason',
            'explanation' => 'El foco debe estar siempre en redisenar los sistemas, no en exigir humanos infalibles.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Google SRE: Example Postmortem Template',
            'url' => 'https://sre.google/sre-book/example-postmortem/',
            'type' => 'GUIDE',
        ],
        [
            'title' => 'Etsy: Blameless PostMortems and Just Culture',
            'url' => 'https://www.etsy.com/codeascraft/blameless-postmortems',
            'type' => 'ARTICLE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Auditor de Postmortems Sin Culpas de Incidentes (BlamelessPostmortemAuditor)',
        'objective' => 'Implementar la clase BlamelessPostmortemAuditor con auditPostmortem(array $report): array, validando incident_id no vacio y time_to_resolve_minutes >= 0 con InvalidArgumentException, verificando la ausencia de atribuciones de culpa (\'culpable\') y comprobando acciones de remediacion.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Professional\\Incident.
2. Implementa la clase BlamelessPostmortemAuditor.
3. Implementa function auditPostmortem(array $report): array.
4. Si no contiene incident_id o este esta vacio, lanza \\InvalidArgumentException.
5. Si time_to_resolve_minutes no es entero o es menor a 0, lanza \\InvalidArgumentException.
6. Comprueba que el texto de root_cause no contenga la palabra \'culpable\'; si la contiene, retorna is_approved => false.
7. Comprueba que action_items sea un array no vacio.
8. Retorna un array con is_approved (bool), slo_breached (bool, si tiempo > 60) y action_items_count (int).',
        'filename' => 'src/Professional/Incident/BlamelessPostmortemAuditor.php',
        'guide' => [
            'steps' => [
                'Paso 1: Valida que isset($report[\'incident_id\']) y trim((string)$report[\'incident_id\']) !== \'\'.',
                'Paso 2: Valida $report[\'time_to_resolve_minutes\'] >= 0, arrojando InvalidArgumentException si no cumple.',
                'Paso 3: Si str_contains(strtolower((string)$report[\'root_cause\']), \'culpable\'), retorna is_approved => false.',
                'Paso 4: Cuenta las acciones de remediacion y calcula si tiempo > 60 para slo_breached.',
                'Paso 5: Retorna el array estructurado con is_approved, slo_breached y action_items_count.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Un postmortem sin culpas se enfoca en las causas sistemicas, no en castigar individuos.',
            ],
            [
                'text' => '[Pista 2: Estructura] if (str_contains(strtolower($rootCause), \'culpable\')) return [\'is_approved\' => false, ...];',
            ],
            [
                'text' => '[Pista 3: Snippet] $slo = ($timeToResolve > 60); return [\'is_approved\' => true, \'slo_breached\' => $slo, \'action_items_count\' => count($report[\'action_items\'])];',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Incident;

use InvalidArgumentException;

class BlamelessPostmortemAuditor
{
    /**
     * @param array<string, mixed> $report
     * @return array{is_approved: bool, slo_breached: bool, action_items_count: int}
     */
    public function auditPostmortem(array $report): array
    {
        // TODO: Validar incident_id y time_to_resolve_minutes >= 0 (lanzar InvalidArgumentException)
        // TODO: Rechazar si root_cause contiene \'culpable\' (cultura blameless)
        // TODO: Evaluar slo_breached (> 60 minutos) y contar action_items
        // TODO: Retornar resultado estructurado
        return [];
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Incident;

use InvalidArgumentException;

class BlamelessPostmortemAuditor
{
    /**
     * @param array<string, mixed> $report
     * @return array{is_approved: bool, slo_breached: bool, action_items_count: int}
     */
    public function auditPostmortem(array $report): array
    {
        if (!isset($report[\'incident_id\']) || trim((string) $report[\'incident_id\']) === \'\') {
            throw new InvalidArgumentException(\'El identificador del incidente no puede estar vacio.\');
        }

        $time = $report[\'time_to_resolve_minutes\'] ?? -1;
        if (!is_int($time) || $time < 0) {
            throw new InvalidArgumentException(\'El tiempo de resolucion debe ser mayor o igual a cero.\');
        }

        $rootCause = strtolower((string) ($report[\'root_cause\'] ?? \'\'));
        if (str_contains($rootCause, \'culpable\')) {
            return [
                \'is_approved\' => false,
                \'slo_breached\' => false,
                \'action_items_count\' => 0,
            ];
        }

        $items = $report[\'action_items\'] ?? [];
        $itemsCount = is_array($items) ? count($items) : 0;

        return [
            \'is_approved\' => ($itemsCount > 0),
            \'slo_breached\' => ($time > 60),
            \'action_items_count\' => $itemsCount,
        ];
    }
}
',
        'explanation' => 'La clase BlamelessPostmortemAuditor promueve la cultura de confiabilidad SRE. Valida la integridad cuantitativa del informe de incidente, rechaza acusaciones individuales garantizando un enfoque sistemico y audita el cumplimiento de los acuerdos de nivel de servicio (SLO).',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Gestion de Incidentes, Observabilidad & Postmortems',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por que las organizaciones de ingenieria de elite como Google o Etsy insisten en realizar Postmortems \'Sin Culpas\' (Blameless)?',
                'options' => [
                    'a' => 'Para evitar tener que pagar indemnizaciones legales a los programadores.',
                    'b' => 'Porque si la gente teme ser castigada o despedida, ocultara los errores o no reportara los cuasi-accidentes, impidiendo que la organizacion aprenda y arregle las fallas sistemicas.',
                    'c' => 'Porque los programadores tienen sindicatos que prohiben redactar informes tecnicos.',
                    'd' => 'Para que los postmortems se redacten en menos de 5 minutos.',
                ],
                'correct' => 'b',
                'explanation' => 'La cultura del castigo solo genera secretos y miedo. La cultura sin culpas fomenta la transparencia total para que el sistema aprenda y se refuerce continuamente.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Cual es el elemento mas importante que debe quedar registrado al final de un informe de postmortem?',
                'options' => [
                    'a' => 'La disculpa formal por escrito de los ingenieros de guardia.',
                    'b' => 'Las acciones de remediacion concretas (Action Items) con responsables y fechas asignadas para prevenir que esa causa raiz especifica vuelva a ocurrir jamas.',
                    'c' => 'El registro de asistencia a la reunion.',
                    'd' => 'La confirmacion de que el servidor no sufrio danos fisicos.',
                ],
                'correct' => 'b',
                'explanation' => 'Un postmortem sin acciones preventivas es solo un lamento inutil. Lo que previene futuros desastres son las tareas concretas de automatizacion y monitoreo que surgen de el.',
            ],
            [
                'id' => 'q3',
                'question' => 'En el marco de observabilidad SRE de Google, ¿cuales son las \'Cuatro Senales Doradas\' (Golden Signals)?',
                'options' => [
                    'a' => 'Latencia, Trafico, Errores y Saturacion.',
                    'b' => 'Memoria, CPU, Disco y Teclado.',
                    'c' => 'Pull Requests, Commits, Branches y Tags.',
                    'd' => 'Frontend, Backend, Base de Datos y Cache.',
                ],
                'correct' => 'a',
                'explanation' => 'Latencia (tiempo de respuesta), Trafico (volumen de peticiones), Errores (tasa de fallos) y Saturacion (que tan lleno esta el recurso mas limitado) describen el estado vital de cualquier servicio.',
            ],
        ],
    ],
];
