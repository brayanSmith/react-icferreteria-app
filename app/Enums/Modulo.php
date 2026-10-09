<?php

namespace App\Enums;

/**
 * Módulos de la aplicación sobre los que se conceden permisos.
 *
 * Los permisos forman un catálogo fijo definido en código ("pedidos.ver",
 * "productos.editar"...): los tenants no inventan permisos, solo los agrupan
 * en los roles que ellos mismos crean.
 */
enum Modulo: string
{
    case Pedidos = 'pedidos';
    case Compras = 'compras';
    case Productos = 'productos';
    case Clientes = 'clientes';
    case Proveedores = 'proveedores';
    case Traslados = 'traslados';
    case Produccion = 'produccion';
    case Comisiones = 'comisiones';
    case Usuarios = 'usuarios';
    case Roles = 'roles';
    case Empresa = 'empresa';
    case Pucs = 'pucs';
    case Medidas = 'medidas';
    case Categorias = 'categorias';
    case Bodegas = 'bodegas';
    case Rutas = 'rutas';
    case StockInicial = 'stock_inicial';

    public function etiqueta(): string
    {
        return match ($this) {
            self::Pedidos => 'Pedidos',
            self::Compras => 'Compras',
            self::Productos => 'Productos',
            self::Clientes => 'Clientes',
            self::Proveedores => 'Proveedores',
            self::Traslados => 'Traslados',
            self::Produccion => 'Producción',
            self::Comisiones => 'Comisiones',
            self::Usuarios => 'Usuarios',
            self::Roles => 'Roles',
            self::Empresa => 'Empresa',
            self::Pucs => 'Plan de cuentas (PUC)',
            self::Medidas => 'Medidas',
            self::Categorias => 'Categorías y subcategorías',
            self::Bodegas => 'Bodegas',
            self::Rutas => 'Rutas',
            self::StockInicial => 'Stock inicial',
        };
    }

    /**
     * Acciones disponibles en el módulo.
     *
     * @return list<Accion>
     */
    public function acciones(): array
    {
        return match ($this) {
            self::Empresa => [Accion::Ver, Accion::Editar],
            default => Accion::cases(),
        };
    }

    /**
     * Nombre del permiso para una acción, por ejemplo "pedidos.crear".
     */
    public function permiso(Accion $accion): string
    {
        return "{$this->value}.{$accion->value}";
    }

    /**
     * Todos los nombres de permiso del catálogo.
     *
     * @return list<string>
     */
    public static function permisos(): array
    {
        $permisos = [];

        foreach (self::cases() as $modulo) {
            foreach ($modulo->acciones() as $accion) {
                $permisos[] = $modulo->permiso($accion);
            }
        }

        return $permisos;
    }

    /**
     * Catálogo agrupado por módulo, listo para pintar una matriz de permisos.
     *
     * @return list<array{modulo: string, etiqueta: string, acciones: list<array{permiso: string, etiqueta: string}>}>
     */
    public static function catalogo(): array
    {
        return array_map(fn (self $modulo): array => [
            'modulo' => $modulo->value,
            'etiqueta' => $modulo->etiqueta(),
            'acciones' => array_map(fn (Accion $accion): array => [
                'permiso' => $modulo->permiso($accion),
                'etiqueta' => $accion->etiqueta(),
            ], $modulo->acciones()),
        ], self::cases());
    }
}
