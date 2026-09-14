<?php

declare(strict_types=1);

return [
    'slug' => 'devops-linux-cli-internals',
    'title' => 'Linux CLI, Procesos, Permisos & Variables de Entorno',
    'module' => 'devops',
    'minutes' => 45,
    'difficulty' => 'Fundamentos',
    'overview' => [
        'concept' => 'En el mundo real de la ingenieria de software, una aplicacion PHP no flota en el vacio; se ejecuta sobre un kernel de Linux dentro de un contenedor o maquina virtual. Comprender la gestion de procesos (PID, señales SIGTERM/SIGKILL), la matriz de permisos octales (rwx en owner, group, others) y el aislamiento de variables de entorno segun los estandares POSIX es una habilidad indispensable para todo desarrollador Senior.

Uno de los fallos de seguridad mas comunes en despliegues es la asignacion negligente de permisos 0777 (world-writable) para \'solucionar rapido\' problemas de permisos en var/cache/ o var/log/. Esto permite que cualquier proceso no privilegiado en el sistema operativo inyecte codigo o altere archivos.

En esta leccion implementamos un auditor de seguridad de procesos y permisos Linux (LinuxProcessSecurityAuditor) que analiza modos octales, detecta riesgos de escritura global y valida nombres de variables de entorno bajo POSIX.',
        'problem' => 'En el mundo real de la ingenieria de software, una aplicacion PHP no flota en el vacio; se ejecuta sobre un kernel de Linux dentro de un contenedor o maquina virtual. Comprender la gestion de procesos (PID, señales SIGTERM/SIGKILL), la matriz de permisos octales (rwx en owner, group, others) y el aislamiento de variables de entorno segun los estandares POSIX es una habilidad indispensable para todo desarrollador Senior.',
        'solution' => 'Uno de los fallos de seguridad mas comunes en despliegues es la asignacion negligente de permisos 0777 (world-writable) para \'solucionar rapido\' problemas de permisos en var/cache/ o var/log/. Esto permite que cualquier proceso no privilegiado en el sistema operativo inyecte codigo o altere archivos.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'La Cerradura de Combinacion de Tres Ruedas',
        'concept' => 'Imagina la cerradura de combinacion de una caja fuerte con tres diales giratorios:
1. PRIMER DIAL (Propietario / User): Los permisos del usuario dueno del archivo (Lectura=4, Escritura=2, Ejecucion=1).
2. SEGUNDO DIAL (Grupo / Group): Los permisos compartidos para el equipo o grupo del sistema (como www-data).
3. TERCER DIAL (Otros / World): Los permisos para cualquier transeunte anonimo del sistema operativo.

Si configuras el modo 0755, el dueno puede leer, escribir y ejecutar (7); el grupo puede leer y ejecutar (5); y otros pueden leer y ejecutar (5). Pero si configuras 0777, le estas entregando una llave maestra a cualquier persona en la calle para que sobreescriba tus registros o modifique tus binarios (falla de seguridad critica).',
        'ascii_diagram' => 'MATRIZ OCTAL DE PERMISOS LINUX Y REGLA DE PRIVILEGIO MINIMO:

        Modo Octal:  0  7  5  5
                     |  |  |  |
                     |  |  |  +-- Others: Read(4) + Execute(1) = 5 (r-x)
                     |  |  +----- Group:  Read(4) + Execute(1) = 5 (r-x)
                     |  +-------- Owner:  Read(4) + Write(2) + Execute(1) = 7 (rwx)
                     +----------- Octal prefix

Riesgo Critico (0777 / World-Writable):
[ Others ] ---> Tiene permiso de ESCRITURA (+2) -> ¡Cualquier proceso puede destruir el archivo!
Regla Senior: Directorios en 0755, Archivos en 0644, Secretos (.env.local) en 0600.
',
        'analogy' => 'Imagina la cerradura de combinacion de una caja fuerte con tres diales giratorios:
1. PRIMER DIAL (Propietario / User): Los permisos del usuario dueno del archivo (Lectura=4, Escritura=2, Ejecucion=1).
2. SEGUNDO DIAL (Grupo / Group): Los permisos compartidos para el equipo o grupo del sistema (como www-data).
3. TERCER DIAL (Otros / World): Los permisos para cualquier transeunte anonimo del sistema operativo.

Si configuras el modo 0755, el dueno puede leer, escribir y ejecutar (7); el grupo puede leer y ejecutar (5); y otros pueden leer y ejecutar (5). Pero si configuras 0777, le estas entregando una llave maestra a cualquier persona en la calle para que sobreescriba tus registros o modifique tus binarios (falla de seguridad critica).',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En el kernel de Linux, los permisos se almacenan como una mascara de bits (bitmask) en el inodo del archivo.',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Para verificar si un archivo es editable por cualquiera (world-writable), se realiza una operacion bitwise AND contra la mascara 0002: ($octalMode & 0002) !== 0.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'Asimismo, los nombres de variables de entorno en el estandar POSIX.1-2017 deben consistir unicamente en letras mayusculas, digitos y guiones bajos, comenzando obligatoriamente por una letra o guion bajo: ^[A-Z_][A-Z0-9_]*$.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'En Symfony en produccion: 1. El proceso PHP-FPM nunca debe correr como root (UID 0), sino como un usuario no privilegiado (www-data o appuser). 2. Las senales de Linux: cuando Docker o Kubernetes detienen un pod, envian SIGTERM. PHP-FPM o el worker de Messenger debe interceptar SIGTERM y finalizar la peticion o trabajo en curso de forma limpia (graceful shutdown) antes del SIGKILL forzoso.',
        ],
    ],
    'video' => [
        'title' => 'the Linux File System explained in 1,233 seconds',
        'speaker' => 'NetworkChuck',
        'youtube_id' => 'A3G-3hp88mo',
        'duration' => '20 min',
        'description' => 'NetworkChuck explica de forma dinamica la jerarquia del sistema de archivos de Linux, inodos, permisos octales (chmod, chown) y por que entender la gestion de usuarios es vital para la seguridad en servidores.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Linux;

use InvalidArgumentException;

final class LinuxProcessSecurityAuditor
{
    /**
     * @return array{is_secure: bool, risk_level: string, recommendation: string}
     */
    public function auditFilePermissions(string $path, int $octalMode): array
    {
        $cleanPath = trim($path);
        if ($cleanPath === \'\') {
            throw new InvalidArgumentException(\'La ruta del archivo no puede estar vacia.\');
        }

        if ($octalMode < 0) {
            throw new InvalidArgumentException(\'El modo octal no puede ser negativo.\');
        }

        // Comprobar si tiene bit de escritura para others (world-writable)
        $isWorldWritable = ($octalMode & 0002) !== 0;

        if ($isWorldWritable) {
            return [
                \'is_secure\' => false,
                \'risk_level\' => \'CRITICAL\',
                \'recommendation\' => \'El archivo es modificable por cualquier usuario (world-writable). Aplica chmod 0644 o 0755.\',
            ];
        }

        return [
            \'is_secure\' => true,
            \'risk_level\' => \'LOW\',
            \'recommendation\' => \'Los permisos respetan el principio de menor privilegio.\',
        ];
    }

    public function validateEnvVarName(string $name): bool
    {
        $cleanName = trim($name);
        if ($cleanName === \'\') {
            return false;
        }

        return preg_match(\'/^[A-Z_][A-Z0-9_]*$/\', $cleanName) === 1;
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior soluciona errores de permisos ejecutando sudo chmod -R 777 en todo el servidor. El desarrollador Senior comprende la propiedad de usuarios y grupos en Linux: asigna chown www-data:www-data, establece permisos 0755/0644 estrictos y valida que los contenedores nunca ejecuten procesos como superusuario root.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Usa chmod 777 para que el directorio var/cache no de errores de escritura.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Configura ACLs o asigna el usuario www-data al contenedor con permisos de lectura estricta y escritura acotada.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Principio de Menor Privilegio',
            'source' => 'The Protection of Information in Computer Systems',
            'quote' => 'Cada programa y cada usuario del sistema debe operar utilizando el conjunto minimo de privilegios necesarios para completar el trabajo.',
            'author' => 'Jerome Saltzer & Michael Schroeder',
            'explanation' => 'Otorgar permisos 0777 viola las bases cardinales de la seguridad informatica.',
        ],
        [
            'topic' => 'Estandar POSIX',
            'source' => 'IEEE Std 1003.1-2017 (POSIX)',
            'quote' => 'Los nombres de variables de entorno consisten exclusivamente en caracteres alfanumericos y guiones bajos para garantizar interoperabilidad entre shells.',
            'author' => 'The Open Group',
            'explanation' => 'El formato estandarizado previene colisiones e inyecciones de comandos en la linea de comandos.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Linux Permissions Reference Guide',
            'url' => 'https://wiki.archlinux.org/title/File_permissions_and_attributes',
            'type' => 'DOCS',
        ],
        [
            'title' => 'POSIX Environment Variables Specification',
            'url' => 'https://pubs.opengroup.org/onlinepubs/9699919799/basedefs/V1_chap08.html',
            'type' => 'SPEC',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Auditor de Seguridad de Permisos y Procesos Linux (LinuxProcessSecurityAuditor)',
        'objective' => 'Implementar la clase LinuxProcessSecurityAuditor con auditFilePermissions() y validateEnvVarName(), validando argumentos con InvalidArgumentException, detectando permisos inseguros world-writable y validando variables de entorno POSIX con preg_match().',
        'instructions' => '1. Declara strict_types=1 y namespace App\\DevOps\\Linux.
2. Implementa la clase LinuxProcessSecurityAuditor.
3. Implementa function auditFilePermissions(string $path, int $octalMode): array.
4. Si $path esta vacia o $octalMode < 0, lanza \\InvalidArgumentException.
5. Comprueba si el archivo es world-writable mediante ($octalMode & 0002) !== 0.
6. Retorna un array con claves is_secure (bool), risk_level (\'LOW\' o \'CRITICAL\') y recommendation (string).
7. Implementa function validateEnvVarName(string $name): bool validando con preg_match(\'/^[A-Z_][A-Z0-9_]*$/\', $name).',
        'filename' => 'src/DevOps/Linux/LinuxProcessSecurityAuditor.php',
        'guide' => [
            'steps' => [
                'Paso 1: En auditFilePermissions, valida que trim($path) !== \'\' y $octalMode >= 0.',
                'Paso 2: Evalua la operacion bitwise ($octalMode & 0002) !== 0.',
                'Paso 3: Si es verdadero, retorna is_secure => false, risk_level => \'CRITICAL\'.',
                'Paso 4: En caso contrario, retorna is_secure => true, risk_level => \'LOW\'.',
                'Paso 5: En validateEnvVarName, retorna true si preg_match(\'/^[A-Z_][A-Z0-9_]*$/\', trim($name)) === 1.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El bit 0002 corresponde a escritura (write) para \'others\'.',
            ],
            [
                'text' => '[Pista 2: Estructura] ($octalMode & 0002) !== 0 evalua si otros tienen permiso de escritura.',
            ],
            [
                'text' => '[Pista 3: Snippet] return preg_match(\'/^[A-Z_][A-Z0-9_]*$/\', $name) === 1;',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Linux;

use InvalidArgumentException;

class LinuxProcessSecurityAuditor
{
    /**
     * @return array{is_secure: bool, risk_level: string, recommendation: string}
     */
    public function auditFilePermissions(string $path, int $octalMode): array
    {
        // TODO: Validar path no vacio y octalMode >= 0 (lanzar InvalidArgumentException)
        // TODO: Comprobar world-writable ($octalMode & 0002 !== 0)
        // TODO: Retornar resultado estructurado
        return [];
    }

    public function validateEnvVarName(string $name): bool
    {
        // TODO: Validar formato POSIX con preg_match
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\DevOps\\Linux;

use InvalidArgumentException;

class LinuxProcessSecurityAuditor
{
    /**
     * @return array{is_secure: bool, risk_level: string, recommendation: string}
     */
    public function auditFilePermissions(string $path, int $octalMode): array
    {
        $cleanPath = trim($path);
        if ($cleanPath === \'\') {
            throw new InvalidArgumentException(\'La ruta del archivo no puede estar vacia.\');
        }

        if ($octalMode < 0) {
            throw new InvalidArgumentException(\'El modo octal no puede ser negativo.\');
        }

        $isWorldWritable = ($octalMode & 0002) !== 0;

        if ($isWorldWritable) {
            return [
                \'is_secure\' => false,
                \'risk_level\' => \'CRITICAL\',
                \'recommendation\' => \'El archivo tiene permisos de escritura publica (world-writable).\',
            ];
        }

        return [
            \'is_secure\' => true,
            \'risk_level\' => \'LOW\',
            \'recommendation\' => \'Permisos seguros bajo menor privilegio.\',
        ];
    }

    public function validateEnvVarName(string $name): bool
    {
        $clean = trim($name);
        if ($clean === \'\') {
            return false;
        }

        return preg_match(\'/^[A-Z_][A-Z0-9_]*$/\', $clean) === 1;
    }
}
',
        'explanation' => 'La clase LinuxProcessSecurityAuditor encapsula politicas de seguridad del sistema operativo Linux. Detecta permisos globales peligrosos mediante mascaras de bits y valida variables de entorno conforme a las convenciones formales de POSIX.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Linux CLI, Procesos, Permisos & Variables de Entorno',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por que se considera una grave negligencia de seguridad aplicar permisos 0777 (chmod 777) en servidores de produccion?',
                'options' => [
                    'a' => 'Porque Linux desactiva la conexion a Internet si detecta tres sietes.',
                    'b' => 'Porque otorga permisos de escritura a cualquier usuario anonimo o proceso del sistema operativo, permitiendo alterar el codigo fuente o inyectar scripts maliciosos.',
                    'c' => 'Porque hace que las consultas SQL se vuelvan mas lentas.',
                    'd' => 'Porque el archivo se borra automaticamente al reiniciar el servidor.',
                ],
                'correct' => 'b',
                'explanation' => 'El permiso 7 en \'others\' significa lectura, escritura y ejecucion publica. Si el servidor sufre una intrusion menor, el atacante puede sobreescribir cualquier archivo 777 de inmediato.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Que senal envia el orquestador (Docker o Kubernetes) a un contenedor PHP-FPM para solicitarle un apagado ordenado (graceful shutdown)?',
                'options' => [
                    'a' => 'SIGKILL',
                    'b' => 'SIGTERM',
                    'c' => 'SIGSTOP',
                    'd' => 'SIGHUP',
                ],
                'correct' => 'b',
                'explanation' => 'SIGTERM (signal 15) solicita la terminacion limpia del proceso, permitiendole completar las peticiones en curso. Si no se apaga tras un periodo de gracia, se envia SIGKILL (signal 9) forzoso.',
            ],
            [
                'id' => 'q3',
                'question' => 'En el estandar POSIX, ¿cual de los siguientes nombres de variable de entorno es COMPLETAMENTE VALIDO?',
                'options' => [
                    'a' => 'DATABASE-URL',
                    'b' => '127_DATABASE_URL',
                    'c' => 'DATABASE_URL_PRODUCTION',
                    'd' => 'database.url',
                ],
                'correct' => 'c',
                'explanation' => 'Las variables POSIX deben comenzar con letra o guion bajo y contener solo letras mayusculas, digitos y guiones bajos (sin guiones medios ni puntos).',
            ],
        ],
    ],
];
