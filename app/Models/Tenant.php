<?php

namespace App\Models;

use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Cliente de la plataforma. Lo crea y administra el super administrador;
 * los datos de su empresa (modelo Empresa) los edita el propio tenant.
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory;

    use SoftDeletes;

    protected $fillable = ['nombre', 'slug', 'activo'];

    protected function casts(): array
    {
        return ['activo' => 'boolean'];
    }

    public function empresa(): HasOne
    {
        return $this->hasOne(Empresa::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }
}
