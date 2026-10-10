<?php

namespace App\Actions\Importaciones;

use App\Enums\Modulo;
use App\Http\Requests\Gestion\GuardarClienteRequest;
use App\Models\Cliente;
use App\Models\Importacion;
use App\Models\Ruta;
use App\Models\User;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Auth;

/**
 * Importa clientes.
 *
 * El número de documento identifica a cada cliente (es único en el tenant,
 * incluso entre los eliminados), así que no hace falta un ID:
 *
 * - Crear: la fila crea el cliente; si el documento ya existe, se omite. Un
 *   cliente eliminado con ese documento se rechaza: hay que restaurarlo.
 * - Actualizar: la fila modifica el cliente con ese documento y solo cambia lo
 *   que trae lleno (una celda vacía deja el dato actual). El documento no cambia.
 * - Crear y actualizar: si el documento ya existe actualiza; si no, crea.
 *
 * La ruta y el comercial se relacionan por nombre (el comercial, también por
 * correo) y deben existir; si no, la fila se rechaza.
 */
class ImportadorClientes extends Importador
{
    use InterpretaValores;

    /** Columnas que se copian tal cual al cliente. */
    private const DIRECTAS = [
        'tipo_documento', 'razon_social', 'representante_legal', 'email', 'telefono', 'ciudad', 'direccion',
        'categoria', 'sector', 'tipo_cliente', 'retenedor_fuente', 'limite_credito', 'activo', 'novedad',
    ];

    /** @var array<string, string> */
    private const TIPOS_DOCUMENTO = [
        'NIT' => 'NIT',
        'CC' => 'Cédula de ciudadanía',
        'CE' => 'Cédula de extranjería',
        'TI' => 'Tarjeta de identidad',
        'PASAPORTE' => 'Pasaporte',
        'OTRO' => 'Otro',
    ];

    /** @var array<string, string> */
    private const TIPOS_CLIENTE = [
        'ELECTRONICO' => 'Electrónico',
        'REMISIONADO' => 'Remisionado',
    ];

    /** @var array<string, int>|null */
    private ?array $rutas = null;

    /** @var array<string, int>|null */
    private ?array $comerciales = null;

    /** @var array<string, true>|null Documentos que ya existen en el tenant (eliminados incluidos). */
    private ?array $documentos = null;

    public function modulo(): Modulo
    {
        return Modulo::Clientes;
    }

    public function etiqueta(): string
    {
        return 'Clientes';
    }

    public function columnas(): array
    {
        return [
            'tipo_documento' => 'Tipo de documento',
            'numero_documento' => 'Número de documento',
            'razon_social' => 'Razón social',
            'representante_legal' => 'Representante legal',
            'email' => 'Correo',
            'telefono' => 'Teléfono',
            'ciudad' => 'Ciudad',
            'direccion' => 'Dirección',
            'ruta' => 'Ruta',
            'comercial' => 'Comercial',
            'categoria' => 'Categoría',
            'sector' => 'Sector',
            'tipo_cliente' => 'Tipo de cliente',
            'retenedor_fuente' => 'Retenedor de fuente',
            'limite_credito' => 'Límite de crédito',
            'activo' => 'Activo',
            'novedad' => 'Novedad',
        ];
    }

    public function sinonimos(): array
    {
        return [
            'tipo_documento' => ['tipo doc', 'tipo de identificacion', 'tipo identificacion'],
            'numero_documento' => ['documento', 'nit', 'cedula', 'identificacion', 'numero de identificacion', 'nit cc', 'nit/cc', 'n documento'],
            'razon_social' => ['nombre', 'cliente', 'nombre cliente', 'nombre o razon social'],
            'representante_legal' => ['representante'],
            'email' => ['correo electronico', 'e mail', 'email'],
            'telefono' => ['celular', 'telefono celular', 'tel'],
            'direccion' => ['direccion cliente'],
            'comercial' => ['vendedor', 'asesor', 'asesor comercial'],
            'tipo_cliente' => ['tipo cliente', 'facturacion'],
            'retenedor_fuente' => ['retenedor', 'retefuente'],
            'limite_credito' => ['limite', 'cupo', 'cupo de credito', 'credito'],
        ];
    }

    public function columnaId(): string
    {
        return 'numero_documento';
    }

    public function identificador(): string
    {
        return 'su número de documento';
    }

    public function columnasRequeridas(string $modo): array
    {
        return $modo === Importacion::MODO_CREAR
            ? ['tipo_documento', 'numero_documento', 'razon_social']
            : ['numero_documento'];
    }

    /**
     * En "ambos", el documento decide: si ya existe se actualiza; si no, se crea.
     */
    public function modoDeFila(array $datos, string $modo): string
    {
        if ($modo !== Importacion::MODO_AMBOS) {
            return $modo;
        }

        return $datos['numero_documento'] !== null && isset($this->documentosExistentes()[$datos['numero_documento']])
            ? Importacion::MODO_ACTUALIZAR
            : Importacion::MODO_CREAR;
    }

    public function normalizar(array $datos): array
    {
        $datos['limite_credito'] = self::numero($datos['limite_credito'] ?? null);
        $datos['tipo_documento'] = self::deLista($datos['tipo_documento'] ?? null, self::TIPOS_DOCUMENTO, [
            'cedula' => 'CC', 'cedula_ciudadania' => 'CC', 'cedula_de_ciudadania' => 'CC',
            'cedula_extranjeria' => 'CE', 'cedula_de_extranjeria' => 'CE',
        ]);
        $datos['tipo_cliente'] = self::deLista($datos['tipo_cliente'] ?? null, self::TIPOS_CLIENTE);
        $datos['retenedor_fuente'] = match (self::booleano($datos['retenedor_fuente'] ?? null)) {
            '1' => 'SI',
            '0' => 'NO',
            default => $datos['retenedor_fuente'] ?? null,
        };
        $datos['activo'] = self::booleano($datos['activo'] ?? null);

        return $datos;
    }

    public function reglas(string $modo): array
    {
        $crear = $modo === Importacion::MODO_CREAR;
        $obligatoria = $crear ? 'required' : 'nullable';

        return [
            'tipo_documento' => [$obligatoria, 'in:'.implode(',', GuardarClienteRequest::TIPOS_DOCUMENTO)],
            'numero_documento' => ['required', 'string', 'max:50'],
            'razon_social' => [$obligatoria, 'string', 'max:255'],
            'representante_legal' => ['nullable', 'string', 'max:255'],
            'email' => ['nullable', 'email', 'max:255'],
            'telefono' => ['nullable', 'string', 'max:50'],
            'ciudad' => ['nullable', 'string', 'max:100'],
            'direccion' => ['nullable', 'string', 'max:255'],
            'ruta' => ['nullable', 'string', 'max:255'],
            'comercial' => ['nullable', 'string', 'max:255'],
            'categoria' => ['nullable', 'string', 'max:100'],
            'sector' => ['nullable', 'string', 'max:100'],
            'tipo_cliente' => ['nullable', 'in:'.implode(',', GuardarClienteRequest::TIPOS_CLIENTE)],
            'retenedor_fuente' => ['nullable', 'in:'.implode(',', GuardarClienteRequest::RETENEDOR_FUENTE)],
            'limite_credito' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'activo' => ['nullable', 'boolean'],
            'novedad' => ['nullable', 'string', 'max:255'],
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
            ['tipo_documento' => 'NIT', 'numero_documento' => '900123456', 'razon_social' => 'Ferretería El Tornillo SAS', 'email' => 'compras@eltornillo.com', 'telefono' => '3001234567', 'ciudad' => 'Bogotá', 'tipo_cliente' => 'Electrónico', 'retenedor_fuente' => 'No', 'limite_credito' => '5000000', 'activo' => 'Sí'],
            ['tipo_documento' => 'CC', 'numero_documento' => '1032456789', 'razon_social' => 'Juan Pérez', 'ciudad' => 'Medellín'],
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
        $documento = $datos['numero_documento'];

        if (isset($this->documentosExistentes()[$documento])) {
            // Se omite si el cliente está activo; si está eliminado hay que restaurarlo, no duplicarlo.
            if (Cliente::onlyTrashed()->where('numero_documento', $documento)->exists()) {
                throw new FilaRechazada("El cliente con documento {$documento} está eliminado. Restáuralo en lugar de importarlo.");
            }

            return false;
        }

        $this->verificarCorreoLibre($datos['email'], null);

        $atributos = array_filter(Arr::only($datos, self::DIRECTAS), fn (?string $valor): bool => $valor !== null);
        $atributos['activo'] = ($atributos['activo'] ?? '1') === '1';

        Cliente::create([
            ...$atributos,
            ...$this->idsDeRutaYComercial($datos),
            'numero_documento' => $documento,
        ]);
        $this->documentos[$documento] = true;

        return true;
    }

    /**
     * @param  array<string, string|null>  $datos
     */
    private function actualizar(array $datos): bool
    {
        $cliente = Cliente::query()->where('numero_documento', $datos['numero_documento'])->first();

        if ($cliente === null) {
            throw new FilaRechazada(
                Cliente::onlyTrashed()->where('numero_documento', $datos['numero_documento'])->exists()
                    ? "El cliente con documento {$datos['numero_documento']} está eliminado. Restáuralo para poder actualizarlo."
                    : "No existe un cliente con el documento {$datos['numero_documento']}.",
            );
        }

        $cambios = array_filter(Arr::only($datos, self::DIRECTAS), fn (?string $valor): bool => $valor !== null);
        if (isset($cambios['activo'])) {
            $cambios['activo'] = $cambios['activo'] === '1';
        }

        $cambios = [...$cambios, ...$this->idsDeRutaYComercial($datos)];

        // Solo cuenta lo que de verdad es distinto del valor actual.
        $cambios = array_filter($cambios, fn (mixed $nuevo, string $campo): bool => ! self::sonIguales($cliente->getAttribute($campo), $nuevo), ARRAY_FILTER_USE_BOTH);

        if ($cambios === []) {
            return false;
        }

        if (isset($cambios['email'])) {
            $this->verificarCorreoLibre($cambios['email'], $cliente->id);
        }

        $cliente->update($cambios);

        return true;
    }

    /**
     * El correo es único en el tenant (también entre los eliminados).
     */
    private function verificarCorreoLibre(?string $email, ?int $clienteId): void
    {
        if ($email === null) {
            return;
        }

        $enUso = Cliente::withTrashed()
            ->where('email', $email)
            ->when($clienteId !== null, fn ($query) => $query->whereKeyNot($clienteId))
            ->exists();

        if ($enUso) {
            throw new FilaRechazada("El correo {$email} ya lo usa otro cliente.");
        }
    }

    /**
     * Ids de la ruta y del comercial a partir de su nombre. Solo devuelve los
     * que la fila trae; si no existen, la fila se rechaza.
     *
     * @param  array<string, string|null>  $datos
     * @return array<string, int>
     */
    private function idsDeRutaYComercial(array $datos): array
    {
        $ids = [];

        if ($datos['ruta'] !== null) {
            $this->rutas ??= Ruta::query()
                ->pluck('id', 'ruta')
                ->mapWithKeys(fn (int $id, string $nombre): array => [mb_strtolower($nombre) => $id])
                ->all();

            $ids['ruta_id'] = $this->rutas[mb_strtolower($datos['ruta'])]
                ?? throw new FilaRechazada("La ruta {$datos['ruta']} no existe.");
        }

        if ($datos['comercial'] !== null) {
            $this->comerciales ??= $this->comercialesDelTenant();

            $ids['comercial_id'] = $this->comerciales[mb_strtolower($datos['comercial'])]
                ?? throw new FilaRechazada("No hay un usuario llamado {$datos['comercial']} para asignarlo como comercial.");
        }

        return $ids;
    }

    /**
     * Usuarios de la empresa por nombre y por correo, en minúsculas.
     *
     * @return array<string, int>
     */
    private function comercialesDelTenant(): array
    {
        $mapa = [];

        foreach (User::query()->where('tenant_id', Auth::user()->tenant_id)->get(['id', 'name', 'email']) as $usuario) {
            $mapa[mb_strtolower($usuario->email)] = $usuario->id;
            $mapa[mb_strtolower($usuario->name)] ??= $usuario->id;
        }

        return $mapa;
    }

    /**
     * @return array<string, true>
     */
    private function documentosExistentes(): array
    {
        return $this->documentos ??= Cliente::withTrashed()
            ->pluck('numero_documento')
            ->mapWithKeys(fn (string $documento): array => [$documento => true])
            ->all();
    }
}
