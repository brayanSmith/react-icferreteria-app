<?php

namespace App\Actions\Empresas;

use App\Models\Empresa;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

class GuardarEmpresa
{
    /**
     * Disco donde se guardan el logo y el icono (servidos desde /storage).
     */
    private const DISCO = 'public';

    /**
     * Crea o actualiza la empresa del tenant. Cada tenant tiene una sola, que
     * se crea la primera vez que se guarda.
     *
     * Reemplazar o quitar una imagen borra el archivo anterior del disco.
     *
     * @param  array<string, mixed>  $datos  Campos validados de la empresa.
     */
    public function handle(int $tenantId, array $datos, ?UploadedFile $logo, ?UploadedFile $icono): Empresa
    {
        $empresa = Empresa::query()->firstOrNew(['tenant_id' => $tenantId]);

        $empresa->fill([
            'nombre_empresa' => $datos['nombre_empresa'],
            'nit_empresa' => $datos['nit_empresa'] ?? null,
            'email_empresa' => $datos['email_empresa'] ?? null,
            'telefono_empresa' => $datos['telefono_empresa'] ?? null,
            'direccion_empresa' => $datos['direccion_empresa'] ?? null,
            ...collect(Empresa::CAMPOS_COLOR)
                ->mapWithKeys(fn (string $campo): array => [$campo => $datos[$campo] ?? null])
                ->all(),
            'cuentas_bancarias' => array_values($datos['cuentas_bancarias'] ?? []),
            'mostrar_productos_sin_inventario' => $datos['mostrar_productos_sin_inventario'] ?? true,
        ]);

        $empresa->logo_empresa = $this->resolverImagen(
            $tenantId,
            $empresa->logo_empresa,
            $logo,
            (bool) ($datos['quitar_logo'] ?? false),
        );
        $empresa->icono_empresa = $this->resolverImagen(
            $tenantId,
            $empresa->icono_empresa,
            $icono,
            (bool) ($datos['quitar_icono'] ?? false),
        );

        $empresa->tenant_id = $tenantId;
        $empresa->save();

        return $empresa;
    }

    /**
     * Devuelve la ruta que debe quedar guardada: la nueva si se subió un
     * archivo, ninguna si se pidió quitarla o la actual si no hay cambios.
     */
    private function resolverImagen(int $tenantId, ?string $actual, ?UploadedFile $nueva, bool $quitar): ?string
    {
        if ($nueva === null && ! $quitar) {
            return $actual;
        }

        if ($actual !== null) {
            Storage::disk(self::DISCO)->delete($actual);
        }

        return $nueva?->store("empresas/{$tenantId}", self::DISCO);
    }
}
