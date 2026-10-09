<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Database\Factories\ImportacionFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Seguimiento de un archivo CSV importado: su estado, el avance y las filas
 * rechazadas. El archivo se procesa en segundo plano (ver ProcesarImportacion).
 */
class Importacion extends Model
{
    use BelongsToTenant;

    /** @use HasFactory<ImportacionFactory> */
    use HasFactory;

    /** El archivo está subido y el usuario aún relaciona sus columnas. */
    public const CONFIGURANDO = 'configurando';

    public const MODO_CREAR = 'crear';

    public const MODO_ACTUALIZAR = 'actualizar';

    /** Cada fila decide: con ID actualiza ese registro, sin ID crea uno nuevo. */
    public const MODO_AMBOS = 'ambos';

    public const PENDIENTE = 'pendiente';

    public const PROCESANDO = 'procesando';

    public const COMPLETADA = 'completada';

    public const FALLIDA = 'fallida';

    /** Máximo de filas con error que se guardan en detalle (el conteo sí es completo). */
    public const MAX_ERRORES_GUARDADOS = 200;

    protected $table = 'importaciones';

    protected $fillable = [
        'user_id',
        'modulo',
        'nombre_original',
        'ruta',
        'estado',
        'delimitador',
        'modo',
        'encabezados',
        'muestra',
        'mapeo',
        'total_filas',
        'filas_procesadas',
        'filas_creadas',
        'filas_actualizadas',
        'filas_omitidas',
        'filas_con_error',
        'errores',
        'mensaje',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return ['errores' => 'array', 'encabezados' => 'array', 'muestra' => 'array', 'mapeo' => 'array'];
    }

    /**
     * Las últimas importaciones de un módulo, para mostrar su avance.
     *
     * @param  Builder<Importacion>  $query
     * @param  list<string>  $modulos
     */
    public function scopeRecientesDe(Builder $query, array $modulos, int $limite = 5): void
    {
        $query->whereIn('modulo', $modulos)->latest('id')->limit($limite);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function estaEnCurso(): bool
    {
        return in_array($this->estado, [self::PENDIENTE, self::PROCESANDO], true);
    }
}
