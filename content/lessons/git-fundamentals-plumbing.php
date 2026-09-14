<?php

declare(strict_types=1);

return [
    'slug' => 'git-fundamentals-plumbing',
    'title' => 'Git Plumbing, Objetos & el Grafo Acíclico Dirigido (DAG)',
    'module' => 'Git & GitHub Profesional',
    'minutes' => 45,
    'difficulty' => 'Intermedio',
    'overview' => [
        'concept' => 'En su núcleo interno, Git no es un sistema de control de versiones basado en diferencias de archivos (deltas), sino un almacén de clave-valor direccionable por contenido (Content-Addressable Storage / CAS) con un sistema de archivos criptográfico inmutable basado en un Grafo Acíclico Dirigido (DAG).',
        'problem' => 'Los desarrolladores que aprenden Git memorizando comandos superficiales (\'git pull\', \'git add .\') viven con miedo constante a perder código, sufren con merges misteriosos y no entienden qué significa realmente un commit o un detached HEAD.',
        'solution' => 'Comprender la anatomía interna de los 4 objetos de Git (Blob, Tree, Commit, Tag) y cómo Git calcula el hash SHA-1 de cada objeto con su cabecera binaria nula, haciendo que ramas y fusiones sean operaciones O(1) instantáneas.',
        'problem_label' => 'El Problema: La Caja Negra de Git y el Miedo a Perder Código',
        'solution_label' => 'La Solución Senior: Almacén Direccionable por Contenido y el DAG de Objetos',
    ],
    'mental_model' => [
        'title' => 'Los Casilleros Criptográficos de la Biblioteca Central',
        'analogy' => 'Imagina una biblioteca gigante donde los casilleros no están etiquetados con el nombre del libro, sino con una huella dactilar de su contenido exacto. Cuando dejas un texto, el bibliotecario le antepone una etiqueta formal con el tipo y la longitud en bytes: `"blob &lt;longitud&gt;\\0"`. Luego pasa esa cadena por un algoritmo criptográfico (SHA-1) que genera una clave de 40 caracteres hexadecimales (ej. `d670460b...`). Ese hash se convierte en el número de casillero donde se guarda el texto comprimido con zlib. ¿Qué significa esto? Que si dos archivos en carpetas totalmente diferentes tienen exactamente el mismo contenido (por ejemplo, una imagen de logo idéntica o un archivo vacío), **Git solo guarda un casillero**. Git no rastrea nombres de archivos en los blobs; los nombres se guardan en objetos de directorio llamados **Trees**. Y un **Commit** es simplemente un ticket que apunta a un Tree raíz y a los commits que vinieron antes (sus padres).',
        'ascii_diagram' => 'CONTENIDO: "echo \'hola mundo\';"
       │
       ▼
[CABECERA DE GIT]: "blob 18\\0echo \'hola mundo\';"
       │
       ▼ (Algoritmo SHA-1)
[HASH]: d670460b4b4aece5915caf5c68d12f560a9fe3e4
       │
       ▼
[DISCO]: .git/objects/d6/70460b4b4aece5915caf5c68d12f560a9fe3e4

ESTRUCTURA DEL GRAFO:
[Commit A38F] ──> [Tree Root 41B2] ──> [Blob D670] (index.php)
      │                              └──> [Tree src/ 89F1] ──> [Blob E912] (Kernel.php)
      ▼ (padre)
[Commit 102E] (Commit anterior)',
        'key_concept' => 'Un commit en Git no es una lista de diferencias (deltas); es una foto completa de todo tu proyecto en ese instante del tiempo, compartiendo casilleros inmutables con los commits anteriores.',
    ],
    'internals' => [
        'title' => 'Los 4 Objetos Canónicos de Git y la Carpeta .git',
        'steps' => [
            [
                'phase' => '1. El Objeto Blob (Binary Large Object)',
                'description' => 'Almacena los bytes puros de un archivo. No guarda el nombre del archivo, ni sus permisos, ni la fecha de modificación. Solo el contenido precedido por \'blob <bytes>\\0\'.',
            ],
            [
                'phase' => '2. El Objeto Tree (Árbol de Directorios)',
                'description' => 'Representa una carpeta en el disco. Contiene una lista de entradas con: permisos Unix (100644 para archivos normales, 040000 para subdirectorios), nombre del archivo y el hash SHA-1 del blob o sub-tree correspondiente.',
            ],
            [
                'phase' => '3. El Objeto Commit (Instantánea Atómica)',
                'description' => 'Un archivo de texto pequeño que apunta al hash del Tree raíz, lista los hashes de los commits padres (0 para el inicial, 1 para normal, 2 o más para merges), y registra autor, committer, timestamp y mensaje.',
            ],
            [
                'phase' => '4. Las Referencias (Refs) y HEAD',
                'description' => 'Una rama (branch) en Git no es una copia de archivos ni una estructura pesada; es simplemente un archivo de texto de 41 bytes en .git/refs/heads/main que contiene el hash SHA-1 del último commit. Mover una rama es una operación O(1) de escribir un hash en un archivo de texto.',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Comandos de Fontanería (Plumbing) vs Porcelana (Porcelain)',
            'icon' => 'terminal',
            'content' => 'Linus Torvalds dividió los comandos de Git en dos familias:

• **Porcelain (Porcelana):** Los comandos de alto nivel para humanos (`git status`, `git checkout`, `git branch`, `git commit`).
• **Plumbing (Fontanería):** Las herramientas de bajo nivel que manipulan los objetos crudos en el directorio `.git/objects/`:
  - `git hash-object -w archivo.php`: Calcula el SHA-1 y escribe el objeto comprimido en disco.
  - `git cat-file -p &lt;hash&gt;`: Inspecciona el contenido decodificado de cualquier objeto (sea blob, tree o commit).
  - `git cat-file -t &lt;hash&gt;`: Devuelve el tipo exacto del objeto.
  - `git ls-tree &lt;hash&gt;`: Muestra el contenido interno de un objeto Tree.',
            'code' => '# Puedes crear un commit en Git sin usar \'git add\' ni \'git commit\':
HASH=$(echo "echo \'hola\';" | git hash-object -w --stdin)
TREE=$(git write-tree)
COMMIT=$(echo "Commit manual por plumbing" | git commit-tree $TREE)
git update-ref refs/heads/main $COMMIT',
            'takeaways' => 'Conocer los comandos de fontanería te da superpoderes: te permite recuperar commits borrados, reconstruir árboles corruptos y automatizar herramientas sobre repositorios.',
        ],
        [
            'title' => 'Por qué el DAG hace que las ramas sean instantáneas',
            'icon' => 'cpu',
            'content' => 'En sistemas antiguos como SVN o CVS, crear una rama significaba duplicar físicamente todo el directorio del proyecto en el servidor, tardando minutos en repositorios grandes.

En Git, como el grafo es inmutable y los commits apuntan hacia atrás:
• Crear una rama `git branch feature` solo crea el archivo `.git/refs/heads/feature` con el hash del commit actual (41 bytes en disco, 0.001 milisegundos).
• Cambiar de rama con `git switch feature` solo actualiza el puntero simbólico `.git/HEAD` para que apunte a `ref: refs/heads/feature`.',
            'takeaways' => 'Las ramas en Git son punteros volátiles y baratos. Puedes crear y descartar 50 ramas al día sin ningún impacto en el rendimiento del repositorio.',
        ],
    ],
    'video' => [
        'title' => 'Linus Torvalds on Git: Plumbing, Objects & DAG',
        'speaker' => 'Linus Torvalds (Google Tech Talks)',
        'youtube_id' => '4XpnKHJAok8',
        'duration' => '70 min',
        'description' => 'Conferencia histórica de Linus Torvalds detallando la representación de blobs, trees, commits y el DAG SHA-1 que sustenta la integridad de Git.',
        'chapters' => [
            '00:00' => 'Por qué los sistemas VCS tradicionales fallaban',
            '18:30' => 'Objetos inmutables y SHA-1: Blobs y Trees',
            '35:45' => 'El Grafo Acíclico Dirigido (DAG) y commits atómicos',
            '52:10' => 'Rendimiento de ramas y fusiones en O(1)',
        ],
    ],
    'architecture_code' => [
        'filename' => 'GitBlobHasher.php',
        'title' => 'Calculador de Hash SHA-1 de Blobs según la Especificación Interna de Git',
        'tag' => 'Git Internals Plumbing in PHP 8.4',
        'code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Emulador del comando de fontanería \'git hash-object\'.
 * Replica la especificación binaria canónica que Git utiliza internamente
 * para calcular el identificador único SHA-1 de un archivo.
 */
final readonly class GitBlobHasher
{
    /**
     * Calcula el hash SHA-1 de 40 caracteres hexadecimales de un blob de Git.
     * Formato canónico: "blob <longitud_en_bytes>\\0<contenido_crudo>"
     *
     * @param string $content Contenido crudo del archivo a indexar.
     * @return string Hash SHA-1 de 40 caracteres en minúsculas.
     */
    public function computeBlobSha(string $content): string
    {
        // 1. Longitud en bytes exactos del contenido (strlen seguro a nivel binario)
        $length = strlen($content);

        // 2. Encabezado estándar de Git delimitado por byte nulo (chr(0) o "\\0")
        $header = "blob {$length}\\0";

        // 3. Empaquetado del payload binario
        $payload = $header . $content;

        // 4. Hash SHA-1 del payload combinado
        return sha1($payload);
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'Un Senior nunca entra en pánico ante un merge conflict, un detached HEAD o un rebase fallido porque sabe que mientras un objeto haya sido agregado al index (git add), vive en `.git/objects/` y se puede recuperar con `git reflog` o `git fsck`. Ve el historial como un grafo inmutable de estados y no como una cinta lineal frágil.',
        'critical_questions' => [
            '¿Qué objeto de Git representa este cambio: un blob nuevo, un tree actualizado o un commit con múltiples padres?',
            '¿Dónde apunta HEAD en este momento: a una rama simbólica o a un commit desconectado (detached HEAD)?',
            '¿Por qué hacer \'git commit --amend\' no modifica el commit anterior sino que crea uno completamente nuevo con diferente hash SHA-1?',
            '¿Cómo puedo usar \'git reflog\' para rescatar commits huérfanos que ya no son alcanzables por ninguna rama?',
        ],
    ],
    'junior_vs_senior' => [
        'problem_statement' => 'Un desarrollador hizo un `git reset --hard HEAD~3` por error y \'perdió\' tres días de trabajo en su rama local.',
        'junior' => [
            'approach' => 'Entra en pánico, asume que los archivos fueron eliminados permanentemente del disco y empieza a reescribir todo el código desde cero.',
            'flaws' => [
                'Pérdida de tiempo masiva, estrés en el equipo y desconfianza en el sistema de control de versiones.',
            ],
        ],
        'senior' => [
            'approach' => 'Ejecuta `git reflog`, localiza el hash SHA-1 del commit antes del reset (ej. `HEAD@{1}: a7b21e`), y ejecuta `git branch recovered-work a7b21e`.',
            'rationale' => [
                'Recuperación del 100% del trabajo en exactamente 10 segundos. Sabe que Git jamás borra objetos de inmediato; los conserva en el garbage collector durante semanas.',
            ],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Direccionamiento por Contenido',
            'source' => 'Pro Git Book (2nd Edition)',
            'quote' => 'En su núcleo, Git es un almacén de clave-valor direccionable por contenido: insertas contenido y obtienes un hash con el que recuperarlo en cualquier momento.',
            'author' => 'Scott Chacon & Ben Straub',
            'explanation' => 'Comprender los objetos blob, tree, commit y tag desmitifica cualquier problema de merge, detached HEAD o rebase interactivo.',
        ],
        [
            'topic' => 'Inmutabilidad del Grafo',
            'source' => 'Git Internals Source Code & Architecture',
            'quote' => 'La historia en Git es criptográficamente inmutable. Cambiar un solo espacio en blanco en un archivo de hace 5 años altera el hash del blob, del árbol, del commit y de todos los commits subsecuentes.',
            'author' => 'Linus Torvalds',
            'explanation' => 'El DAG con hashes SHA garantiza integridad forense absoluta contra alteraciones silenciosas o corrupción de hardware.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Pro Git Book: Git Internals - Git Objects',
            'url' => 'https://git-scm.com/book/en/v2/Git-Internals-Git-Objects',
            'description' => 'Capítulo oficial y canónico sobre la anatomía interna de los objetos y el cálculo de hashes en Git.',
            'type' => 'BOOK',
        ],
        [
            'title' => 'Git from the Bottom Up by John Wiegley',
            'url' => 'https://jwiegley.github.io/git-from-the-bottom-up/',
            'description' => 'Tratado técnico excepcional que explica el modelo mental matemático de grafos en Git.',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Calculador de Hash SHA-1 de Blobs de Git (Plumbing)',
        'objective' => 'Implementar la clase GitBlobHasher con el método computeBlobSha(string $content): string que replique fielmente la especificación binaria de Git.',
        'instructions' => 'Crea la clase GitBlobHasher dentro del namespace App\\Engineering. Implementa el método público computeBlobSha(string $content): string. El método debe anteponer el encabezado \'blob <longitud_en_bytes>\\0\' al contenido y retornar el hash SHA-1 en formato hexadecimal de 40 caracteres en minúsculas. Pista: la longitud debe ser en bytes y el delimitador debe ser el byte nulo (\\0).',
        'filename' => 'GitBlobHasher.php',
        'guide' => [
            'explanation' => 'En este reto vas a crear el corazón de fontanería de Git en PHP. Cuando ejecutas \'git hash-object\' en tu terminal, Git no hace simplemente sha1($content). Primero calcula cuántos bytes mide el contenido, crea el prefijo \'blob &lt;bytes&gt;\\0\' (con un byte nulo al final), une el encabezado con el contenido y calcula el SHA-1 de toda la cadena.',
            'steps' => [
                'Paso 1: Declara <code>class GitBlobHasher</code> con <code>declare(strict_types=1);</code>.',
                'Paso 2: Implementa <code>public function computeBlobSha(string $content): string</code>.',
                'Paso 3: Obtén la longitud en bytes del contenido usando <code>strlen($content)</code>.',
                'Paso 4: Construye el encabezado exacto de Git: <code>\'blob \' . $length . "\\0"</code> (o usando <code>chr(0)</code>).',
                'Paso 5: Concatena el encabezado con el contenido: <code>$payload = $header . $content;</code>.',
                'Paso 6: Retorna el hash hexadecimal usando la función nativa <code>sha1($payload)</code>.',
            ],
            'useful_functions' => [
                [
                    'name' => 'strlen(string $string)',
                    'desc' => 'Retorna la cantidad de bytes que componen la cadena de texto.',
                ],
                [
                    'name' => '"\\0" o chr(0)',
                    'desc' => 'Representa el byte nulo (null byte, ASCII 0), usado en C y en Git como delimitador de fin de encabezado.',
                ],
                [
                    'name' => 'sha1(string $string)',
                    'desc' => 'Calcula el hash SHA-1 criptográfico de 160 bits (40 caracteres hexadecimales) de la cadena.',
                ],
            ],
        ],
        'hints' => [
            [
                'label' => 'El Encabezado Binario',
                'text' => 'Git requiere que el encabezado contenga la palabra \'blob\', un espacio, la longitud del contenido en bytes y un byte nulo \'\\0\'.',
                'snippet' => '$header = "blob " . strlen($content) . "\\0";',
            ],
            [
                'label' => 'Concatenación del Payload',
                'text' => 'Une el encabezado y el contenido original antes de pasarlo a la función de hash.',
                'snippet' => '$payload = $header . $content;
return sha1($payload);',
            ],
            [
                'label' => 'Prueba de Verificación Mental',
                'text' => 'Si el contenido es \'hola mundo\\n\' (11 bytes), el payload es \'blob 11\\0hola mundo\\n\'. En tu terminal de Linux puedes verificarlo con: printf \'hola mundo\\n\' | git hash-object --stdin.',
                'snippet' => 'public function computeBlobSha(string $content): string
{
    return sha1("blob " . strlen($content) . "\\0" . $content);
}',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Emulador del comando \'git hash-object\'.
 * Calcula el hash SHA-1 canónico de un blob de Git con su encabezado binario.
 */
class GitBlobHasher
{
    /**
     * @param string $content Contenido crudo a procesar.
     * @return string Hash SHA-1 de 40 caracteres.
     */
    public function computeBlobSha(string $content): string
    {
        // PASO 1: Calcula la longitud en bytes del contenido
        // PASO 2: Construye el encabezado \'blob <longitud>\\0\'
        // PASO 3: Concatena encabezado y contenido
        // PASO 4: Retorna sha1() del resultado
        return \'\';
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Engineering;

/**
 * Implementación Senior de GitBlobHasher.
 */
class GitBlobHasher
{
    public function computeBlobSha(string $content): string
    {
        $length = strlen($content);
        $header = "blob {$length}\\0";
        $payload = $header . $content;

        return sha1($payload);
    }
}
',
        'explanation' => 'La solución replica exactamente la especificación de fontanería interna de Git: antepone el prefijo de tipo de objeto (\'blob\'), el espacio, la longitud en bytes y el terminador nulo (\'\\0\') antes de aplicar SHA-1. Esto garantiza que el hash coincida con el generado por el binario oficial de Git.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Git Plumbing y Modelo de Datos Interno',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Por qué dos archivos con nombres completamente diferentes pero contenido idéntico ocupan solo un objeto blob en el directorio .git/objects?',
                'options' => [
                    'a' => 'Porque Git renombra el segundo archivo automáticamente en el disco.',
                    'b' => 'Porque Git es un almacén direccionable por contenido: el identificador del objeto es el hash SHA-1 de su contenido; como el contenido es igual, el hash y el casillero son idénticos.',
                    'c' => 'Porque el sistema operativo Linux fusiona los archivos con hard links de forma predeterminada.',
                    'd' => 'Porque los blobs no se comprimen hasta que se ejecuta git push.',
                ],
                'correct' => 'b',
                'explanation' => 'En Content-Addressable Storage, la clave es el hash del valor. Dos contenidos idénticos generan siempre el mismo hash SHA-1, evitando duplicación innecesaria en el repositorio.',
            ],
            [
                'id' => 'q2',
                'question' => '¿Qué objeto de Git es responsable de almacenar los nombres de los archivos y los permisos Unix asociados a ellos?',
                'options' => [
                    'a' => 'El objeto Blob.',
                    'b' => 'El objeto Commit.',
                    'c' => 'El objeto Tree.',
                    'd' => 'El archivo .git/HEAD.',
                ],
                'correct' => 'c',
                'explanation' => 'Los blobs son ciegos respecto a metadatos de archivos; solo contienen bytes. Los objetos Tree representan directorios y contienen los nombres, modos Unix y punteros a los blobs o sub-trees.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Qué es físicamente una rama (branch) en el directorio interno de Git?',
                'options' => [
                    'a' => 'Una copia clonada completa de todos los archivos del proyecto en una carpeta oculta.',
                    'b' => 'Un simple archivo de texto plano de 41 bytes que contiene el hash SHA-1 del commit más reciente de esa línea de historia.',
                    'c' => 'Una base de datos SQLite embebida en .git/index.',
                    'd' => 'Un hilo de ejecución en el kernel del sistema operativo.',
                ],
                'correct' => 'b',
                'explanation' => 'Una rama es una referencia volátil: un archivo de texto en .git/refs/heads/<nombre> con el hash del commit al que apunta. Por eso crear o mover ramas toma cero tiempo.',
            ],
        ],
    ],
];
