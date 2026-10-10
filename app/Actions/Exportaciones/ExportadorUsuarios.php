<?php

namespace App\Actions\Exportaciones;

use App\Enums\Modulo;
use App\Models\User;
use Illuminate\Support\Facades\Auth;

/**
 * Exporta el personal de la empresa (no los administradores) con las mismas
 * columnas que acepta la importación, salvo la contraseña, que nunca se
 * exporta. El archivo se puede editar y volver a importar para actualizar
 * usuarios: el correo los identifica y la columna de contraseña, si se agrega,
 * la restablece.
 */
class ExportadorUsuarios extends Exportador
{
    public function modulo(): Modulo
    {
        return Modulo::Usuarios;
    }

    public function titulo(): string
    {
        return 'Usuarios';
    }

    public function encabezados(): array
    {
        return ['Nombre', 'Correo', 'Documento', 'Código de vendedor', 'Activo', 'Roles'];
    }

    public function filas(): iterable
    {
        $usuarios = User::query()
            ->where('tenant_id', Auth::user()->tenant_id)
            ->where('es_admin', false)
            ->where('es_super_admin', false)
            ->with('roles:id,name')
            ->orderBy('name')
            ->lazy(500);

        foreach ($usuarios as $usuario) {
            yield [
                $usuario->name,
                $usuario->email,
                $usuario->n_documento,
                $usuario->cod_vendedor ?? '',
                $usuario->activo ? 'Sí' : 'No',
                $usuario->roles->pluck('name')->sort()->implode(' | '),
            ];
        }
    }
}
