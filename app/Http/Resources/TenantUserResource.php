<?php

namespace App\Http\Resources;

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin User
 */
class TenantUserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'n_documento' => $this->n_documento,
            'cod_vendedor' => $this->cod_vendedor,
            'activo' => $this->activo,
            'debe_cambiar_password' => $this->debe_cambiar_password,
            'roles' => $this->roles->map(fn ($rol): array => ['id' => $rol->id, 'name' => $rol->name])->values()->all(),
        ];
    }
}
