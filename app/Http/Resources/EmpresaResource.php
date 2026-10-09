<?php

namespace App\Http\Resources;

use App\Models\Empresa;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Empresa
 */
class EmpresaResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'nombre_empresa' => $this->nombre_empresa,
            'nit_empresa' => $this->nit_empresa,
            'email_empresa' => $this->email_empresa,
            'telefono_empresa' => $this->telefono_empresa,
            'direccion_empresa' => $this->direccion_empresa,
            'color_primario' => $this->color_primario,
            'color_primario_oscuro' => $this->color_primario_oscuro,
            'color_menu' => $this->color_menu,
            'color_menu_oscuro' => $this->color_menu_oscuro,
            'mostrar_productos_sin_inventario' => $this->mostrar_productos_sin_inventario,
            'cuentas_bancarias' => $this->cuentas_bancarias ?? [],
            'logo_url' => $this->logoUrl(),
            'icono_url' => $this->iconoUrl(),
        ];
    }
}
