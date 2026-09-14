<?php

declare(strict_types=1);

return [
    'slug' => 'prof-adr-technical-decisions',
    'title' => 'Architecture Decision Records (ADRs) en Produccion',
    'module' => 'professional-developer',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'En cualquier proyecto que supera los dos anos de vida, una de las mayores fuentes de friccion es la perdida de memoria historica: \'¿Por que demonios usamos Redis en lugar de RabbitMQ para esta cola?\', \'¿Por que desacoplamos este modulo con Hexagonal si el resto esta en capas?\'. Ante la falta de respuestas, los ingenieros nuevos asumen que los arquitectos anteriores \'no sabian lo que hacian\' e inician reescrituras costosas que tropiezan exactamente con los mismos problemas que ya habian sido resueltos en el pasado.

Un Architecture Decision Record (ADR, Registro de Decision Arquitectonica, propuesto por Michael Nygard) es un documento breve en texto plano (Markdown) almacenado en el repositorio Git junto al codigo fuente. Un ADR no documenta como funciona el codigo hoy; documenta el POR QUE se tomo una decision tecnica critica en un momento dado.

En esta leccion implementamos un evaluador de calidad de ADRs (AdrQualityEvaluator) que verifica la presencia rigurosa de los tres pilares indispensables de cualquier decision madura: Contexto (Context), Decision (Decision) y Consecuencias (Consequences), tanto positivas como negativas.',
        'problem' => 'En cualquier proyecto que supera los dos anos de vida, una de las mayores fuentes de friccion es la perdida de memoria historica: \'¿Por que demonios usamos Redis en lugar de RabbitMQ para esta cola?\', \'¿Por que desacoplamos este modulo con Hexagonal si el resto esta en capas?\'. Ante la falta de respuestas, los ingenieros nuevos asumen que los arquitectos anteriores \'no sabian lo que hacian\' e inician reescrituras costosas que tropiezan exactamente con los mismos problemas que ya habian sido resueltos en el pasado.',
        'solution' => 'Un Architecture Decision Record (ADR, Registro de Decision Arquitectonica, propuesto por Michael Nygard) es un documento breve en texto plano (Markdown) almacenado en el repositorio Git junto al codigo fuente. Un ADR no documenta como funciona el codigo hoy; documenta el POR QUE se tomo una decision tecnica critica en un momento dado.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Caja Negra del Avion y el Diario de Navegacion',
        'concept' => 'Piensa en el diario de bitacora de un barco que navega por aguas peligrosas:
1. EL ERROR DE LA FALTA DE REGISTRO: El nuevo capitan ve que el barco desvia su rumbo 20 millas al sur. Dice: \'Que estupidez, vayamos en linea recta al norte\'. Al hacerlo, el barco encalla contra un arrecife sumergido de coral.
2. EL REGISTRO EN LA BITACORA (El ADR): Si hubiera leido la bitacora anterior, habria encontrado:
   - Fecha y Estado: 14 de Octubre, Aprobado.
   - Contexto: Hay un banco de arrecifes no visible en superficie en las coordenadas Norte.
   - Decision: Desviaremos el rumbo 20 millas al sur por el canal seguro.
   - Consecuencias: Tardamos 3 horas mas de viaje (consecuencia negativa asumida), pero el casco permanece intacto (consecuencia positiva vital).

El ADR es la bitacora de tu arquitectura: protege a los futuros ingenieros de estrellar el sistema contra arrecifes tecnicos conocidos.',
        'ascii_diagram' => 'ESTRUCTURA FORMAL DE UN ARCHITECTURE DECISION RECORD (ADR):

+-------------------------------------------------------------+
| ADR-0012: Adopcion de Redis Streams para Colas de Mensajes  |
| Estado: APROBADO (Status: Accepted) | Fecha: 2026-09-10     |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
| 1. CONTEXTO (Context):                                      |
|    - Que problema tecnico u organizacional estamos resolviendo|
|    - Cuales son las fuerzas en conflicto (rendimiento, costo) |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
| 2. DECISION (Decision):                                     |
|    - La eleccion tecnica tomada (ej. Redis en vez de Kafka)  |
|    - Justificacion basada en la realidad del equipo         |
+-------------------------------------------------------------+
                              |
                              v
+-------------------------------------------------------------+
| 3. CONSECUENCIAS (Consequences):                            |
|    - Consecuencias Positivas (Menor latencia, stack simple) |
|    - Consecuencias Negativas asumidas conscientemente       |
|      (Memoria limitada a RAM, retencion no infinita)        |
+-------------------------------------------------------------+
',
        'analogy' => 'Piensa en el diario de bitacora de un barco que navega por aguas peligrosas:
1. EL ERROR DE LA FALTA DE REGISTRO: El nuevo capitan ve que el barco desvia su rumbo 20 millas al sur. Dice: \'Que estupidez, vayamos en linea recta al norte\'. Al hacerlo, el barco encalla contra un arrecife sumergido de coral.
2. EL REGISTRO EN LA BITACORA (El ADR): Si hubiera leido la bitacora anterior, habria encontrado:
   - Fecha y Estado: 14 de Octubre, Aprobado.
   - Contexto: Hay un banco de arrecifes no visible en superficie en las coordenadas Norte.
   - Decision: Desviaremos el rumbo 20 millas al sur por el canal seguro.
   - Consecuencias: Tardamos 3 horas mas de viaje (consecuencia negativa asumida), pero el casco permanece intacto (consecuencia positiva vital).

El ADR es la bitacora de tu arquitectura: protege a los futuros ingenieros de estrellar el sistema contra arrecifes tecnicos conocidos.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En la evaluacion de un ADR, un documento que solo menciona ventajas es un manifiesto de marketing, no un ADR.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Todo diseno de software implica trade-offs.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'La clase AdrQualityEvaluator analiza un array de datos de ADR y valida que las claves \'context\', \'decision\' y \'consequences\' no solo existan, sino que contengan texto sustancial (strings no vacios mayores a 10 caracteres).',
            ],
            [
                'phase' => '4. Resolución, Métricas & Efectos Secundarios',
                'description' => 'Si falta alguno de estos pilares, el metodo isComplete() retorna false.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Ciclo de vida de un ADR en Git: 1. Proposed: propuesto en un Pull Request para discusion del equipo. 2. Accepted: aprobado y fusionado a la rama principal. 3. Superseded: reemplazado formalmente por un nuevo ADR (por ejemplo, ADR-0024 reemplaza a ADR-0012). Nunca se edita el ADR original; la historia es inmutable.',
        ],
    ],
    'video' => [
        'title' => 'Architecture Decision Records (ADR) as a LOG that answers \'WHY?\'',
        'speaker' => 'CodeOpinion (Derek Comartin)',
        'youtube_id' => '6H6zfCNeqek',
        'duration' => '14 min',
        'description' => 'Derek Comartin explica por que los ADRs son una de las mejores practicas de ingenieria, como capturar el contexto de las decisiones tecnicas en Git y como evitar que el conocimiento arquitectonico se disipe con el tiempo.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Architecture;

final class AdrQualityEvaluator
{
    /**
     * @param array<string, mixed> $adrData
     */
    public function isComplete(array $adrData): bool
    {
        $requiredKeys = [\'context\', \'decision\', \'consequences\'];

        foreach ($requiredKeys as $key) {
            if (!isset($adrData[$key]) || !is_string($adrData[$key]) || trim($adrData[$key]) === \'\') {
                return false;
            }
        }

        return true;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior cambia de libreria o arquitectura sin dejar ningun rastro escrito del porque. El desarrollador Senior documenta cada decision de alto impacto en un ADR: especifica el contexto de negocio, las alternativas evaluadas y las consecuencias negativas que acepta conscientemente, construyendo cultura de ingenieria duradera.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Reemplaza una libreria criticando a los autores anteriores sin entender por que la eligieron.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Lee los ADRs existentes para comprender las restricciones historicas antes de proponer un nuevo ADR con fundamentos tecnicos.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Architecture Decision Records',
            'source' => 'Documenting Architecture Decisions',
            'quote' => 'Un Architecture Decision Record es un documento corto en texto plano que captura una decision arquitectonica importante junto con su contexto y sus consecuencias.',
            'author' => 'Michael Nygard',
            'explanation' => 'Mantiene el registro vivo de la intencion arquitectonica en el mismo control de versiones.',
        ],
        [
            'topic' => 'La Ley de Chesterton',
            'source' => 'The Thing (1929)',
            'quote' => 'Nunca derribes una cerca hasta que conozcas la razon por la cual fue construida en primer lugar.',
            'author' => 'G. K. Chesterton',
            'explanation' => 'No refactorices ni cambies una decision tecnica sin entender el problema original que intentaba resolver.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Michael Nygard: Documenting Architecture Decisions',
            'url' => 'https://cognitect.com/blog/2011/11/15/documenting-architecture-decisions',
            'type' => 'ARTICLE',
        ],
        [
            'title' => 'ADR GitHub Organization: Templates & Tools',
            'url' => 'https://adr.github.io/',
            'type' => 'TOOL',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Evaluador de Completitud de ADRs (AdrQualityEvaluator)',
        'objective' => 'Implementar la clase AdrQualityEvaluator con isComplete(array $adrData): bool, comprobando la presencia estricta de las secciones context, decision y consequences como strings no vacios.',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Professional\\Architecture.
2. Implementa la clase AdrQualityEvaluator.
3. Implementa function isComplete(array $adrData): bool.
4. Comprueba que las claves \'context\', \'decision\' y \'consequences\' existan y no esten vacias.
5. Retorna true solo si los tres campos indispensables estan presentes y contienen texto valido; de lo contrario retorna false.',
        'filename' => 'src/Professional/Architecture/AdrQualityEvaluator.php',
        'guide' => [
            'steps' => [
                'Paso 1: Define el array de claves requeridas: [\'context\', \'decision\', \'consequences\'].',
                'Paso 2: Itera sobre las claves y verifica isset, is_string y trim !== \'\'.',
                'Paso 3: Si alguna falla, retorna false inmediatamente.',
                'Paso 4: Si todas las comprobaciones pasan, retorna true.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] Un ADR sin consecuencias no es una decision tecnica valida.',
            ],
            [
                'text' => '[Pista 2: Estructura] foreach ([\'context\', \'decision\', \'consequences\'] as $k) { if (!isset($adrData[$k])) return false; }',
            ],
            [
                'text' => '[Pista 3: Snippet] if (!isset($adrData[\'context\']) || !isset($adrData[\'decision\']) || !isset($adrData[\'consequences\'])) return false;',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Architecture;

class AdrQualityEvaluator
{
    public function isComplete(array $adrData): bool
    {
        // TODO: Comprobar campos indispensables context, decision, consequences
        // TODO: Retornar true si todos estan presentes y con contenido; de lo contrario false
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Professional\\Architecture;

class AdrQualityEvaluator
{
    public function isComplete(array $adrData): bool
    {
        if (!isset($adrData[\'context\']) || !isset($adrData[\'decision\']) || !isset($adrData[\'consequences\'])) {
            return false;
        }

        if (trim((string) $adrData[\'context\']) === \'\'
            || trim((string) $adrData[\'decision\']) === \'\'
            || trim((string) $adrData[\'consequences\']) === \'\') {
            return false;
        }

        return true;
    }
}
',
        'explanation' => 'La clase AdrQualityEvaluator valida la completitud formal de los registros de decision tecnica. Garantiza que ningun ADR se apruebe sin articular el contexto del problema, la solucion elegida y las consecuencias directas resultantes.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Architecture Decision Records (ADRs) en Produccion',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es el proposito fundamental de redactar un Architecture Decision Record (ADR)?',
                'options' => [
                    'a' => 'Cumplir con un requisito burocratico para que Recursos Humanos audite el horario de los programadores.',
                    'b' => 'Preservar en el repositorio Git la intencion y el contexto del \'¿Por que?\' se tomo una decision tecnica de alto impacto, evitando la perdida de memoria historica en el equipo.',
                    'c' => 'Documentar linea por linea el funcionamiento de los controladores web de Symfony.',
                    'd' => 'Generar codigo fuente PHP automaticamente mediante inteligencia artificial.',
                ],
                'correct' => 'b',
                'explanation' => 'El codigo muestra COMO funciona el sistema hoy. El ADR muestra POR QUE se eligio ese enfoque y que alternativas fueron descartadas, protegiendo al equipo de repetir errores.',
            ],
            [
                'id' => 'q2',
                'question' => 'En la seccion \'Consecuencias\' de un ADR maduro, ¿que elemento es INDISPENSABLE incluir ademas de los beneficios esperados?',
                'options' => [
                    'a' => 'El nombre de la marca de monitor del arquitecto.',
                    'b' => 'Las compensaciones y consecuencias negativas (trade-offs) que el equipo asume conscientemente al tomar la decision.',
                    'c' => 'La firma digital de todos los clientes de la empresa.',
                    'd' => 'El numero de lineas de codigo de la aplicacion.',
                ],
                'correct' => 'b',
                'explanation' => 'Toda decision de ingenieria tiene un costo. Si un documento no menciona ninguna desventaja o consecuencia negativa, es propaganda, no ingenieria.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Como se debe gestionar un cambio de direccion tecnica sobre una decision documentada en un ADR aprobado anteriormente?',
                'options' => [
                    'a' => 'Borrar el archivo ADR anterior del historial de Git con git commit --amend.',
                    'b' => 'Crear un nuevo ADR que explique el nuevo contexto y marcar el ADR anterior como \'Superseded\' (Reemplazado) apuntando al nuevo documento.',
                    'c' => 'Editar el ADR original cambiando su texto sin avisar al equipo.',
                    'd' => 'Reescribir todo el proyecto desde cero en un nuevo repositorio.',
                ],
                'correct' => 'b',
                'explanation' => 'Los ADRs son inmutables historicamente. Si cambian las condiciones, se crea un nuevo ADR que reemplaza (supersedes) al antiguo, manteniendo la trazabilidad historica intacta.',
            ],
        ],
    ],
];
