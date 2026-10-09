<?php

namespace App\Http\Resources;

use App\Actions\Importaciones\Registro;
use App\Models\Importacion;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin Importacion
 */
class ImportacionResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tipo' => Registro::importador($this->modulo)?->etiqueta(),
            'nombre_original' => $this->nombre_original,
            'estado' => $this->estado,
            'modo' => $this->modo,
            'delimitador' => $this->delimitador,
            'total_filas' => $this->total_filas,
            'filas_procesadas' => $this->filas_procesadas,
            'filas_creadas' => $this->filas_creadas,
            'filas_actualizadas' => $this->filas_actualizadas,
            'filas_omitidas' => $this->filas_omitidas,
            'filas_con_error' => $this->filas_con_error,
            'errores' => $this->errores ?? [],
            'mensaje' => $this->mensaje,
            'creada' => $this->created_at?->toIso8601String(),
        ];
    }
}
