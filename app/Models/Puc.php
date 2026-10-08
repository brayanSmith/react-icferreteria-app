<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\PucFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Puc extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<PucFactory> */
    use HasFactory;

    protected $fillable = [
        'tipo',
        'cuenta',
        'subcuenta',
        'concepto',
        'descripcion',
        'concatenar_subcuenta_concepto',
    ];
}
