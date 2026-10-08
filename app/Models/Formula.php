<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Formula extends Model
{
    use BelongsToTenant;

    //
    use HasFactory;

    protected $fillable = ['nombre_formula', 'descripcion_formula'];

    public function detalleFormulas()
    {
        return $this->hasMany(DetalleFormula::class);
    }

    public function producciones()
    {
        return $this->hasMany(Produccion::class);
    }
}
