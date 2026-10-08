<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    //
    protected $fillable = [
        'ruta',
        'descripcion',
    ];

    public function clientes()
    {
        return $this->hasMany(Cliente::class);
    }
}
