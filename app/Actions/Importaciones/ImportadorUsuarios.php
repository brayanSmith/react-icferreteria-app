<?php

namespace App\Actions\Importaciones;

use App\Actions\Tenants\CreateTenantUser;
use App\Actions\Tenants\UpdateTenantUser;
use App\Enums\Modulo;
use App\Models\Importacion;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rules\Password;
use Spatie\Permission\Models\Role;

/**
 * Importa el personal de la empresa (no administradores).
 *
 * El correo identifica a cada usuario (es único en toda la plataforma):
 *
 * - Crear: la fila crea el usuario con una contraseña temporal que debe
 *   cambiar al ingresar. Si el correo ya es de un usuario de tu personal se
 *   omite; si lo usa cualquier otra cuenta (de otra empresa, un
 *   administrador o una eliminada) se rechaza.
 * - Actualizar: la fila modifica al usuario con ese correo y solo cambia lo
 *   que trae lleno. Si trae contraseña, la reemplaza y vuelve a ser
 *   temporal; si trae roles, reemplazan a los actuales. El correo no cambia.
 * - Crear y actualizar: si el correo ya es de tu personal actualiza; si no, crea.
 *
 * Los roles se relacionan por nombre (varios, separados por | o ,) y deben
 * existir en tu empresa. Un usuario no puede modificarse a sí mismo por
 * importación, y la exportación nunca incluye contraseñas.
 */
class ImportadorUsuarios extends Importador
{
    use InterpretaValores;

    /** @var array<string, int>|null Roles de la empresa: nombre en minúsculas => id. */
    private ?array $roles = null;

    /** @var array<string, true>|null Correos del personal de la empresa. */
    private ?array $correosDelPersonal = null;

    public function __construct(
        private ?CreateTenantUser $crearUsuario = null,
        private ?UpdateTenantUser $actualizarUsuario = null,
    ) {
        $this->crearUsuario ??= new CreateTenantUser;
        $this->actualizarUsuario ??= new UpdateTenantUser;
    }

    public function modulo(): Modulo
    {
        return Modulo::Usuarios;
    }

    public function etiqueta(): string
    {
        return 'Usuarios';
    }

    public function columnas(): array
    {
        return [
            'name' => 'Nombre',
            'email' => 'Correo',
            'n_documento' => 'Documento',
            'password' => 'Contraseña temporal',
            'cod_vendedor' => 'Código de vendedor',
            'activo' => 'Activo',
            'roles' => 'Roles',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'name' => ['nombre completo', 'usuario', 'nombres'],
            'email' => ['correo electronico', 'e mail', 'mail'],
            'n_documento' => ['numero de documento', 'cedula', 'identificacion', 'nit', 'n documento'],
            'password' => ['contrasena', 'clave', 'password', 'contrasena inicial'],
            'cod_vendedor' => ['codigo vendedor', 'codigo de vendedor', 'vendedor'],
            'roles' => ['rol', 'perfil', 'perfiles'],
        ];
    }

    public function columnaId(): string
    {
        return 'email';
    }

    public function identificador(): string
    {
        return 'su correo';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR
            ? ['name', 'email', 'n_documento', 'password']
            : ['email'];
    }

    /**
     * En "ambos", el correo decide: si ya es de tu personal se actualiza; si no, se crea.
     */
    public function modoDeFila(array $datos, string $modo): string
    {
        if ($modo !== Importacion::MODO_AMBOS) {
            return $modo;
        }

        return $datos['email'] !== null && isset($this->correosDelPersonal()[$datos['email']])
            ? Importacion::MODO_ACTUALIZAR
            : Importacion::MODO_CREAR;
    }

    public function normalizar(array $datos): array
    {
        $datos['email'] = isset($datos['email']) ? mb_strtolower($datos['email']) : null;
        $datos['activo'] = self::booleano($datos['activo'] ?? null);

        return $datos;
    }

    public function reglas(string $modo): array
    {
        $crear = $modo === Importacion::MODO_CREAR;
        $obligatoria = $crear ? 'required' : 'nullable';

        return [
            'name' => [$obligatoria, 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255'],
            'n_documento' => [$obligatoria, 'string', 'max:255'],
            'password' => [$obligatoria, 'string', Password::default()],
            'cod_vendedor' => ['nullable', 'string', 'max:255'],
            'activo' => ['nullable', 'boolean'],
            'roles' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function mensajes(): array
    {
        return [
            ...parent::mensajes(),
            'email' => ':attribute no es un correo válido.',
        ];
    }

    public function guardar(array $datos, string $modo): bool
    {
        return $modo === Importacion::MODO_CREAR
            ? $this->crear($datos)
            : $this->actualizar($datos);
    }

    public function ejemplos(): array
    {
        $ejemplos = [
            ['name' => 'Ana Ventas', 'email' => 'ana@empresa.com', 'n_documento' => '1032456789', 'password' => 'ClaveTemporal#2026', 'cod_vendedor' => 'V-01', 'activo' => 'Sí', 'roles' => 'Vendedor'],
            ['name' => 'Luis Bodega', 'email' => 'luis@empresa.com', 'n_documento' => '80123456', 'password' => 'ClaveTemporal#2026', 'roles' => 'Bodeguero | Consulta'],
        ];

        return array_map(
            fn (array $ejemplo): array => array_map(fn (string $clave): string => $ejemplo[$clave] ?? '', array_keys($this->columnas())),
            $ejemplos,
        );
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function crear(array $datos): bool
    {
        $correo = $datos['email'];

        if (isset($this->correosDelPersonal()[$correo])) {
            return false;
        }

        // El correo es único en toda la plataforma, incluidas las cuentas eliminadas.
        if (User::withTrashed()->where('email', $correo)->exists()) {
            throw new FilaRechazada("El correo {$correo} ya está en uso.");
        }

        $tenantId = Auth::user()->tenant_id;

        if (User::withTrashed()->where('tenant_id', $tenantId)->where('n_documento', $datos['n_documento'])->exists()) {
            throw new FilaRechazada("El documento {$datos['n_documento']} ya lo tiene otro usuario.");
        }

        $this->crearUsuario->handle($tenantId, [
            'name' => $datos['name'],
            'email' => $correo,
            'n_documento' => $datos['n_documento'],
            'password' => $datos['password'],
            'cod_vendedor' => $datos['cod_vendedor'],
            'activo' => ($datos['activo'] ?? '1') === '1',
            'roles' => $this->idsDeRoles($datos['roles']) ?? [],
        ]);
        $this->correosDelPersonal[$correo] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        // Solo personal de esta empresa: un administrador o un usuario ajeno "no existe".
        $usuario = User::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('es_admin', false)
            ->where('es_super_admin', false)
            ->where('email', $datos['email'])
            ->first()
            ?? throw new FilaRechazada("No existe un usuario de tu empresa con el correo {$datos['email']}.");

        if ($usuario->is(Auth::user())) {
            throw new FilaRechazada('No puedes modificar tu propio usuario por importación.');
        }

        $rolesNuevos = $this->idsDeRoles($datos['roles']);
        $rolesActuales = $usuario->roles()->pluck('id')->map(fn (mixed $id): int => (int) $id)->sort()->values()->all();

        $cambios = array_filter([
            'name' => $datos['name'],
            'n_documento' => $datos['n_documento'],
            'cod_vendedor' => $datos['cod_vendedor'],
        ], fn (?string $valor): bool => $valor !== null);

        $hayCambios = $datos['password'] !== null
            || ($datos['activo'] !== null && ($datos['activo'] === '1') !== (bool) $usuario->activo)
            || ($rolesNuevos !== null && $rolesNuevos !== $rolesActuales)
            || array_filter($cambios, fn (string $valor, string $campo): bool => (string) $usuario->getAttribute($campo) !== $valor, ARRAY_FILTER_USE_BOTH) !== [];

        if (! $hayCambios) {
            return false;
        }

        if (isset($cambios['n_documento']) && User::withTrashed()
            ->where('tenant_id', $usuario->tenant_id)
            ->where('n_documento', $cambios['n_documento'])
            ->whereKeyNot($usuario->id)
            ->exists()) {
            throw new FilaRechazada("El documento {$cambios['n_documento']} ya lo tiene otro usuario.");
        }

        $this->actualizarUsuario->handle($usuario, [
            'name' => $cambios['name'] ?? $usuario->name,
            'email' => $usuario->email,
            'n_documento' => $cambios['n_documento'] ?? $usuario->n_documento,
            'cod_vendedor' => $cambios['cod_vendedor'] ?? $usuario->cod_vendedor,
            'activo' => $datos['activo'] === null ? (bool) $usuario->activo : $datos['activo'] === '1',
            'password' => $datos['password'],
            'roles' => $rolesNuevos ?? $rolesActuales,
        ]);

        return true;
    }

    /**
     * Ids de los roles nombrados en la celda (separados por | , ; o /), en orden.
     * Devuelve null si la fila no trae roles; si algún nombre no existe, rechaza la fila.
     *
     * @return list<int>|null
     */
    private function idsDeRoles(?string $celda): ?array
    {
        if ($celda === null) {
            return null;
        }

        $this->roles ??= Role::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->pluck('id', 'name')
            ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
            ->all();

        $ids = [];

        foreach (preg_split('/[|,;\/]/', $celda) ?: [] as $nombre) {
            $nombre = trim($nombre);

            if ($nombre === '') {
                continue;
            }

            $ids[] = $this->roles[mb_strtolower($nombre)] ?? throw new FilaRechazada("El rol {$nombre} no existe.");
        }

        return collect($ids)->unique()->sort()->values()->all();
    }

    /**
     * @return array<string, true>
     */
    private function correosDelPersonal(): array
    {
        return $this->correosDelPersonal ??= User::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('es_admin', false)
            ->where('es_super_admin', false)
            ->pluck('email')
            ->mapWithKeys(fn (string $correo): array => [mb_strtolower($correo) => true])
            ->all();
    }
}
