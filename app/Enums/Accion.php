<?php

namespace App\Enums;

enum Accion: string
{
    case Ver = 'ver';
    case Crear = 'crear';
    case Editar = 'editar';
    case Eliminar = 'eliminar';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Ver => 'Ver',
            self::Crear => 'Crear',
            self::Editar => 'Editar',
            self::Eliminar => 'Eliminar',
        };
    }
}
