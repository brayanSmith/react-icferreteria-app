<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Storage;

/**
 * Perfil de la empresa de un tenant (nombre, NIT, logo, etc.). Cada tenant
 * tiene una sola y puede editarla sin intervención del super administrador.
 */
class Empresa extends Model
{
    use BelongsToTenant;
    use HasFactory;

    /**
     * Colores de identidad y la variable CSS con la que se aplica cada uno.
     */
    private const VARIABLES_DE_COLOR = [
        'color_primario' => '--marca',
        'color_primario_oscuro' => '--marca-oscuro',
        'color_menu' => '--menu',
        'color_menu_oscuro' => '--menu-oscuro',
    ];

    public const CAMPOS_COLOR = [
        'color_primario',
        'color_primario_oscuro',
        'color_menu',
        'color_menu_oscuro',
    ];

    protected $fillable = [
        'nombre_empresa',
        'direccion_empresa',
        'telefono_empresa',
        'email_empresa',
        'nit_empresa',
        'logo_empresa',
        'icono_empresa',
        'color_primario',
        'color_primario_oscuro',
        'color_menu',
        'color_menu_oscuro',
        'cuentas_bancarias',
        'mostrar_productos_sin_inventario',
    ];

    protected $casts = [
        'cuentas_bancarias' => 'array',
        'mostrar_productos_sin_inventario' => 'boolean',
    ];

    public function logoUrl(): ?string
    {
        return $this->urlDeImagen($this->logo_empresa);
    }

    public function iconoUrl(): ?string
    {
        return $this->urlDeImagen($this->icono_empresa);
    }

    /**
     * Variables CSS de la identidad de la empresa, solo las que tienen color.
     * Cada color lleva su variable de texto (`-foreground`), clara u oscura
     * según la luminancia, para que lo escrito encima siempre se lea.
     *
     * @return array<string, string>
     */
    public function variablesDeColor(): array
    {
        $variables = [];

        foreach (self::VARIABLES_DE_COLOR as $campo => $variable) {
            $color = $this->{$campo};

            if ($color !== null) {
                $variables[$variable] = $color;
                $variables["{$variable}-foreground"] = $this->textoSobre($color);
            }
        }

        return $variables;
    }

    private function textoSobre(string $color): string
    {
        [$rojo, $verde, $azul] = array_map(
            fn (string $canal): float => hexdec($canal) / 255,
            str_split(ltrim($color, '#'), 2),
        );

        $luminancia = 0.2126 * $rojo + 0.7152 * $verde + 0.0722 * $azul;

        return $luminancia > 0.6 ? '#171717' : '#fafafa';
    }

    private function urlDeImagen(?string $ruta): ?string
    {
        return $ruta ? Storage::disk('public')->url($ruta) : null;
    }
}
