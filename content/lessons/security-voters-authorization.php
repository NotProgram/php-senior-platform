<?php

declare(strict_types=1);

return [
    'slug' => 'security-voters-authorization',
    'title' => 'Security Voters & Autorizacion Granular',
    'module' => 'security',
    'minutes' => 45,
    'difficulty' => 'Senior',
    'overview' => [
        'concept' => 'El control de acceso en aplicaciones enterprise no puede limitarse a roles estaticos como ROLE_ADMIN. En sistemas del mundo real (B2B, SaaS, plataformas financieras), los permisos dependen del contexto de la entidad: ¿Puede este usuario editar esta factura especifica? Solo si es el propietario de la factura y la factura aun no ha sido pagada. El antipatron comun es colocar sentencias condicionales if ($invoice->getOwnerId() !== $user->getId()) dispersas en multiples controladores o plantillas Twig, creando brechas de seguridad ante cualquier omision accidental.

El componente Security de Symfony resuelve la autorizacion contextual mediante Security Voters. Un Voter es una clase desacoplada que responde a una sola pregunta: dados un atributo de permiso (INVOICE_VIEW, INVOICE_EDIT) y un sujeto (la entidad Invoice), ¿concede, deniega o se abstiene de votar? Toda la logica de autorizacion queda centralizada, testeable y accesible uniformemente mediante isGranted() en controladores, Twig y voters personalizados.',
        'problem' => 'El control de acceso en aplicaciones enterprise no puede limitarse a roles estaticos como ROLE_ADMIN. En sistemas del mundo real (B2B, SaaS, plataformas financieras), los permisos dependen del contexto de la entidad: ¿Puede este usuario editar esta factura especifica? Solo si es el propietario de la factura y la factura aun no ha sido pagada. El antipatron comun es colocar sentencias condicionales if ($invoice->getOwnerId() !== $user->getId()) dispersas en multiples controladores o plantillas Twig, creando brechas de seguridad ante cualquier omision accidental.',
        'solution' => 'El componente Security de Symfony resuelve la autorizacion contextual mediante Security Voters. Un Voter es una clase desacoplada que responde a una sola pregunta: dados un atributo de permiso (INVOICE_VIEW, INVOICE_EDIT) y un sujeto (la entidad Invoice), ¿concede, deniega o se abstiene de votar? Toda la logica de autorizacion queda centralizada, testeable y accesible uniformemente mediante isGranted() en controladores, Twig y voters personalizados.',
        'problem_label' => 'El Problema Arquitectural',
        'solution_label' => 'La Solución de Diseño Senior',
    ],
    'mental_model' => [
        'title' => 'El Notario de Acceso al Edificio de Archivos',
        'concept' => 'Imagina un edificio gubernamental donde se resguardan millones de expedientes confidenciales:
1. EL ROL ESTATICO (La Credencial Basica): Un visitante muestra una tarjeta que dice \'Ciudadano\' o \'Auditor\'. La tarjeta le permite cruzar la puerta de entrada general del edificio (Firewall de Symfony).
2. EL NOTARIO DE EXPEDIENTE (El Security Voter): Al llegar al mostrador del piso 4 y solicitar ver el expediente 4092, el notario no se fija solo en la tarjeta general. Toma el expediente, comprueba el nombre del propietario en la portada, revisa el estado del sello (si esta cerrado o abierto) y contrasta con la identidad del solicitante.
3. LA REGLA DE SUPERADMIN: Si el solicitante es el Inspector General del Estado (ROLE_SUPER_ADMIN), el notario le abre la boveda de inmediato sin requerir comprobacion de propietario.

La decision de acceso es contextual y granular, protegiendo cada documento de forma independiente.',
        'ascii_diagram' => 'FLUJO DE DECISION DE UN SECURITY VOTER EN SYMFONY:

$this->denyAccessUnlessGranted(\'INVOICE_EDIT\', $invoice);
                                  |
                                  v
+-------------------------------------------------------------+
| AccessDecisionManager de Symfony                            |
| (Estrategia Affirmative: concede si al menos un voter vota SI)
+-------------------------------------------------------------+
                                  |
                                  v
+-------------------------------------------------------------+
| InvoiceAccessVoter                                          |
|                                                             |
| 1. supports(\'INVOICE_EDIT\', $invoice):                      |
|    - ¿El atributo es valido? (VIEW, EDIT) -> SI             |
|    - ¿El sujeto es instanceof Invoice? -> SI                |
|                                                             |
| 2. voteOnAttribute(\'INVOICE_EDIT\', $invoice, $user):        |
|    - ¿Usuario no autenticado ($user === null)? -> FALSE     |
|    - ¿Tiene ROLE_SUPER_ADMIN? -> TRUE (Inmediato)           |
|    - ¿Es ROLE_AUDITOR y operacion VIEW? -> TRUE             |
|    - ¿Es el propietario ($ownerId === $userId)?             |
|        - VIEW: TRUE                                         |
|        - EDIT: $invoice->isEditable()                       |
+-------------------------------------------------------------+
',
        'analogy' => 'Imagina un edificio gubernamental donde se resguardan millones de expedientes confidenciales:
1. EL ROL ESTATICO (La Credencial Basica): Un visitante muestra una tarjeta que dice \'Ciudadano\' o \'Auditor\'. La tarjeta le permite cruzar la puerta de entrada general del edificio (Firewall de Symfony).
2. EL NOTARIO DE EXPEDIENTE (El Security Voter): Al llegar al mostrador del piso 4 y solicitar ver el expediente 4092, el notario no se fija solo en la tarjeta general. Toma el expediente, comprueba el nombre del propietario en la portada, revisa el estado del sello (si esta cerrado o abierto) y contrasta con la identidad del solicitante.
3. LA REGLA DE SUPERADMIN: Si el solicitante es el Inspector General del Estado (ROLE_SUPER_ADMIN), el notario le abre la boveda de inmediato sin requerir comprobacion de propietario.

La decision de acceso es contextual y granular, protegiendo cada documento de forma independiente.',
        'key_concept' => 'Comprender este modelo mental permite desacoplar responsabilidades, predecir el comportamiento del runtime y diseñar arquitecturas resilientes en producción.',
    ],
    'internals' => [
        'title' => 'Mecánica Interna y Flujo de Ejecución',
        'steps' => [
            [
                'phase' => '1. Inicialización & Carga de Contexto',
                'description' => 'En Symfony, cuando se invoca isGranted(), el AccessDecisionManager itera sobre todos los servicios registrados con la etiqueta security.voter. Cada voter invoca supports($attribute, $subject).',
            ],
            [
                'phase' => '2. Evaluación de Contratos & Invariantes',
                'description' => 'Si retorna false, el voter retorna ACCESS_ABSTAIN y no interfiere en la decision. Si retorna true, se ejecuta voteOnAttribute(), que retorna un booleano estricto traducido internamente a ACCESS_GRANTED o ACCESS_DENIED.',
            ],
            [
                'phase' => '3. Procesamiento en Runtime & Aislamiento',
                'description' => 'La estrategia de votacion predeterminada en Symfony es affirmative (concede acceso si al menos un voter concede y ninguno con veto lo impide).',
            ],
        ],
    ],
    'deep_dive' => [
        [
            'title' => 'Análisis en Profundidad',
            'content' => 'Para implementar voters robustos en sistemas enterprise: 1. Denegacion por defecto: si el usuario no esta autenticado ($user === null), retorna false de inmediato. 2. Jerarquia de administracion: verificar roles prioritarios como ROLE_SUPER_ADMIN antes de comprobar propiedades de entidad. 3. Estado de la entidad: los permisos de edicion deben consultar invariantes del sujeto, como $invoice->isEditable() (evitando que un usuario edite una factura que ya fue emitida a la autoridad fiscal).',
        ],
    ],
    'video' => [
        'title' => 'Symfony 5 Authentication Tutorial Part 7 | Symfony Voters',
        'speaker' => 'Gary Clarke',
        'youtube_id' => 'cbcz0NjX4g8',
        'duration' => '24 min',
        'description' => 'Gary Clarke explica en detalle la implementacion de Security Voters en Symfony, la separacion entre autenticacion y autorizacion, y como definir politicas de acceso basadas en permisos y propietarios de entidades.',
    ],
    'architecture_code' => [
        'code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Voter;

use Symfony\\Component\\Security\\Core\\Authentication\\Token\\TokenInterface;
use Symfony\\Component\\Security\\Core\\Authorization\\Voter\\Voter;
use Symfony\\Component\\Security\\Core\\User\\UserInterface;

class InvoiceUser
{
    public function __construct(
        private string $id,
        private array $roles = []
    ) {}

    public function getId(): string { return $this->id; }
    public function getRoles(): array { return $this->roles; }
}

class Invoice
{
    public function __construct(
        private string $ownerId,
        private bool $editable = true
    ) {}

    public function getOwnerId(): string { return $this->ownerId; }
    public function isEditable(): bool { return $this->editable; }
}

class InvoiceAccessVoter extends Voter
{
    public const VIEW = \'INVOICE_VIEW\';
    public const EDIT = \'INVOICE_EDIT\';

    protected function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [self::VIEW, self::EDIT], true)
            && $subject instanceof Invoice;
    }

    protected function voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (in_array(\'ROLE_SUPER_ADMIN\', $user->getRoles(), true)) {
            return true;
        }

        if ($attribute === self::VIEW && in_array(\'ROLE_AUDITOR\', $user->getRoles(), true)) {
            return true;
        }

        /** @var Invoice $subject */
        $isOwner = ($subject->getOwnerId() === $user->getId());

        return match ($attribute) {
            self::VIEW => $isOwner,
            self::EDIT => $isOwner && $subject->isEditable(),
            default => false,
        };
    }
}
',
    ],
    'senior_mindset' => [
        'thought_process' => 'El desarrollador Junior llena sus controladores con validaciones manuales if ($invoice->getUserId() != $me->getId()). El desarrollador Senior centraliza toda la autorizacion en Security Voters de Symfony: el controlador queda limpio con un simple $this->denyAccessUnlessGranted(\'INVOICE_EDIT\', $invoice), y las reglas de negocio de auditoria y superadmin se prueban exhaustivamente en pruebas unitarias aisladas.',
        'critical_questions' => [],
    ],
    'junior_vs_senior' => [
        'junior' => [
            'approach' => 'Copia y pega la logica de autorizacion en 5 controladores distintos, olvidando la validacion en la exportacion a PDF.',
            'flaws' => [],
        ],
        'senior' => [
            'approach' => 'Encapsula la autorizacion en un Voter unico, garantizando que el acceso este protegido en HTTP, consola CLI y API REST.',
            'rationale' => [],
            'trade_offs' => [],
        ],
    ],
    'citations' => [
        [
            'topic' => 'Autorizacion Contextual',
            'source' => 'Symfony Security Component Documentation',
            'quote' => 'Los voters permiten desacoplar las complejas reglas de autorizacion de los controladores, centralizando la logica de negocio en clases reutilizables y faciles de probar.',
            'author' => 'Ryan Weaver',
            'explanation' => 'Permite que la infraestructura de seguridad evalue entidades de negocio dinamicamente.',
        ],
        [
            'topic' => 'El Principio de Menor Privilegio',
            'source' => 'OWASP Top 10: Broken Access Control',
            'quote' => 'El control de acceso defectuoso es la vulnerabilidad numero uno en aplicaciones web. Deniega por defecto y valida permisos a nivel de objeto en cada solicitud.',
            'author' => 'OWASP Foundation',
            'explanation' => 'Nunca confies en que el usuario no conoce el ID numerico del registro de otro cliente.',
        ],
    ],
    'external_references' => [
        [
            'title' => 'Symfony Documentation: How to Use Voters to Check User Permissions',
            'url' => 'https://symfony.com/doc/current/security/voters.html',
            'type' => 'DOCS',
        ],
        [
            'title' => 'OWASP: Broken Access Control Prevention Cheat Sheet',
            'url' => 'https://cheatsheetseries.owasp.org/cheatsheets/Access_Control_Cheat_Sheet.html',
            'type' => 'GUIDE',
        ],
    ],
    'exercise' => [
        'title' => 'Reto CS50: Voter de Autorizacion Granular de Facturas (InvoiceAccessVoter)',
        'objective' => 'Implementar InvoiceAccessVoter con supports() y voteOnAttribute(), validando $subject instanceof Invoice, denegando a usuarios nulos, concediendo a ROLE_SUPER_ADMIN, otorgando lectura a ROLE_AUDITOR y verificando propietario (getOwnerId()) y estado editable (isEditable()).',
        'instructions' => '1. Declara strict_types=1 y namespace App\\Security\\Voter.
2. Define la clase InvoiceAccessVoter.
3. Implementa supports(string $attribute, mixed $subject): bool verificando que $subject instanceof Invoice y atributos INVOICE_VIEW o INVOICE_EDIT.
4. Implementa voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool.
5. Si $user === null retorna false de inmediato.
6. Si el usuario tiene ROLE_SUPER_ADMIN, retorna true.
7. Si el atributo es INVOICE_VIEW y el usuario tiene ROLE_AUDITOR, retorna true.
8. Valida propiedad: si el usuario es el dueno ($user->getId() === $subject->getOwnerId()): retorna true para INVOICE_VIEW, y $subject->isEditable() para INVOICE_EDIT.
9. Para cualquier otro caso, retorna false.',
        'filename' => 'src/Security/Voter/InvoiceAccessVoter.php',
        'guide' => [
            'steps' => [
                'Paso 1: Asegurate de definir o asumir las clases de soporte InvoiceUser y Invoice.',
                'Paso 2: En supports(), comprueba in_array($attribute, [\'INVOICE_VIEW\', \'INVOICE_EDIT\'], true) y $subject instanceof Invoice.',
                'Paso 3: En voteOnAttribute(), aplica primero las clausulas de guardia ($user === null -> false, ROLE_SUPER_ADMIN -> true).',
                'Paso 4: Comprueba ROLE_AUDITOR para la lectura.',
                'Paso 5: Compara $user->getId() con $subject->getOwnerId() y valida $subject->isEditable() en edicion.',
            ],
        ],
        'hints' => [
            [
                'text' => '[Pista 1: Concepto] El voter verifica el atributo y el sujeto; si no coinciden, no debe votar.',
            ],
            [
                'text' => '[Pista 2: Estructura] supports() comprueba $subject instanceof Invoice. voteOnAttribute() aplica el orden de autorizacion.',
            ],
            [
                'text' => '[Pista 3: Snippet] if ($user === null) { return false; } if (in_array(\'ROLE_SUPER_ADMIN\', $user->getRoles())) { return true; }',
            ],
        ],
        'starter_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Voter;

class InvoiceUser
{
    public function __construct(private string $id, private array $roles = []) {}
    public function getId(): string { return $this->id; }
    public function getRoles(): array { return $this->roles; }
}

class Invoice
{
    public function __construct(private string $ownerId, private bool $editable = true) {}
    public function getOwnerId(): string { return $this->ownerId; }
    public function isEditable(): bool { return $this->editable; }
}

class InvoiceAccessVoter
{
    public function supports(string $attribute, mixed $subject): bool
    {
        // TODO: Validar si atributo es INVOICE_VIEW o INVOICE_EDIT y $subject instanceof Invoice
        return false;
    }

    public function voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool
    {
        // TODO: Denegar si $user === null
        // TODO: Conceder a ROLE_SUPER_ADMIN
        // TODO: Conceder INVOICE_VIEW a ROLE_AUDITOR
        // TODO: Validar propietario y $subject->isEditable() para edicion
        return false;
    }
}
',
        'solution_code' => '<?php

declare(strict_types=1);

namespace App\\Security\\Voter;

class InvoiceUser
{
    public function __construct(private string $id, private array $roles = []) {}
    public function getId(): string { return $this->id; }
    public function getRoles(): array { return $this->roles; }
}

class Invoice
{
    public function __construct(private string $ownerId, private bool $editable = true) {}
    public function getOwnerId(): string { return $this->ownerId; }
    public function isEditable(): bool { return $this->editable; }
}

class InvoiceAccessVoter
{
    public function supports(string $attribute, mixed $subject): bool
    {
        return in_array($attribute, [\'INVOICE_VIEW\', \'INVOICE_EDIT\'], true)
            && $subject instanceof Invoice;
    }

    public function voteOnAttribute(string $attribute, mixed $subject, ?InvoiceUser $user): bool
    {
        if ($user === null) {
            return false;
        }

        if (in_array(\'ROLE_SUPER_ADMIN\', $user->getRoles(), true)) {
            return true;
        }

        if ($attribute === \'INVOICE_VIEW\' && in_array(\'ROLE_AUDITOR\', $user->getRoles(), true)) {
            return true;
        }

        if (!$subject instanceof Invoice) {
            return false;
        }

        $isOwner = ($subject->getOwnerId() === $user->getId());

        if ($attribute === \'INVOICE_VIEW\') {
            return $isOwner;
        }

        if ($attribute === \'INVOICE_EDIT\') {
            return $isOwner && $subject->isEditable();
        }

        return false;
    }
}
',
        'explanation' => 'La clase InvoiceAccessVoter centraliza la autorizacion contextual. Aplica denegacion por defecto ante usuarios anonimos, respeta roles administrativos elevados, permite auditoria de solo lectura y valida la condicion mutable isEditable() sobre el propietario real.',
    ],
    'quiz' => [
        'title' => 'Evaluación Técnica: Security Voters & Autorizacion Granular',
        'questions' => [
            [
                'id' => 'q1',
                'question' => '¿Cual es la diferencia principal entre un Role de Symfony (ROLE_ADMIN) y un Security Voter?',
                'options' => [
                    'a' => 'Los roles son variables de entorno y los voters son funciones de base de datos.',
                    'b' => 'Un role es un permiso estatico asignado a un usuario; un voter es una evaluacion dinamica que puede inspeccionar el estado particular de la entidad que se intenta manipular.',
                    'c' => 'Los voters solo funcionan en conexiones WebSocket.',
                    'd' => 'No existe diferencia; los voters fueron eliminados en Symfony 6.',
                ],
                'correct' => 'b',
                'explanation' => 'Un rol te dice quien eres en general (ej. ROLE_USER). Un voter te dice si tienes derecho a editar este objeto especifico en este momento exacto segun sus propiedades de negocio.',
            ],
            [
                'id' => 'q2',
                'question' => 'En la estrategia de votacion \'affirmative\' (predeterminada en Symfony), ¿cuando se concede el acceso?',
                'options' => [
                    'a' => 'Solo si el 100% de los voters registrados votan a favor.',
                    'b' => 'Tan pronto como al menos un voter conceda el acceso (voteOnAttribute retorna true).',
                    'c' => 'Unicamente si el usuario tiene contrasena de mas de 20 caracteres.',
                    'd' => 'Solo si la peticion es de tipo HTTP GET.',
                ],
                'correct' => 'b',
                'explanation' => 'La estrategia affirmative otorga acceso si al menos un voter concede permiso. Si todos se abstienen o deniegan, el acceso es rechazado.',
            ],
            [
                'id' => 'q3',
                'question' => '¿Por que un Security Voter debe comprobar siempre si el usuario es nulo ($user === null) como primera instruccion?',
                'options' => [
                    'a' => 'Para evitar errores fatales en PHP al intentar invocar metodos como $user->getId() en peticiones anonimas no autenticadas.',
                    'b' => 'Porque los usuarios anonimos siempre tienen permisos de superadministrador por defecto.',
                    'c' => 'Para compilar el archivo en lenguaje binario.',
                    'd' => 'Porque de lo contrario el recolector de basura de Linux se bloquea.',
                ],
                'correct' => 'a',
                'explanation' => 'En endpoints publicos o peticiones anonimas, el token de seguridad no tiene un User autenticado. Si intentas llamar a metodos sobre null, PHP arroja un TypeError fatal.',
            ],
        ],
    ],
];
