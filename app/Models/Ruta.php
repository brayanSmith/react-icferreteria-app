<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;

class Ruta extends Model
{
    use BelongsToTenant;

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
