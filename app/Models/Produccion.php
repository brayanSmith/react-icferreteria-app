<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Produccion extends Model
{
    //
    use HasFactory;

    protected $fillable = [
        'formula_id',
        'bodega_id',
        'cantidad',
        'lote',
        'fecha_produccion',
        'fecha_caducidad',
        'observaciones',
        'ph',
        'biscocidad',
        'homogeneidad',
        'responsable_lote_id',
        'responsable_cc_id',
    ];
    public function formula()
    {
        return $this->belongsTo(Formula::class);
    }
    public function bodega()
    {
        return $this->belongsTo(Bodega::class);
    }
    public function detallesProduccionEntradas()
    {
        return $this->hasMany(DetalleProduccionEntrada::class, 'produccion_id');
    }
    public function detallesProduccionSalidas()
    {
        return $this->hasMany(DetalleProduccionSalida::class, 'produccion_id');
    }
    public function responsableLote()
    {
        return $this->belongsTo(User::class, 'responsable_lote_id')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['comercial', 'cliente']);
            });
    }
    public function responsableCC()
    {
        return $this->belongsTo(User::class, 'responsable_cc_id')
            ->whereDoesntHave('roles', function ($query) {
                $query->whereIn('name', ['comercial', 'cliente']);
            });
    }
    public function getCostoTotalMateriaPrimaAttribute()
    {
        return $this->detallesProduccionSalidas->sum(function ($detalle) {
            return $detalle->total_costo;
        });
    }

}
