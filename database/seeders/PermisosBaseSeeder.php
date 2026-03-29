<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class PermisosBaseSeeder extends Seeder
{
    public function run()
    {
        // ==============================
        // 1️⃣ MÓDULOS
        // ==============================

        $modulos = [
            ['nombre' => 'turnos', 'descripcion' => 'Gestión de turnos', 'platform' => 'both'],
            ['nombre' => 'usuarios', 'descripcion' => 'Gestión de usuarios', 'platform' => 'web'],
            ['nombre' => 'reportes', 'descripcion' => 'Reportes del sistema', 'platform' => 'web'],
            ['nombre' => 'stock', 'descripcion' => 'Gestión de stock', 'platform' => 'app'],
        ];

        foreach ($modulos as $modulo) {
            DB::table('tbl_modulos')->updateOrInsert(
                ['nombre' => $modulo['nombre']],
                $modulo
            );
        }

        // ==============================
        // 2️⃣ ACCIONES
        // ==============================

        $acciones = [
            'crear',
            'editar',
            'eliminar',
            'ver',
            'aprobar_turno',
            'cerrar_turno',
            'exportar_excel'
        ];

        foreach ($acciones as $accion) {
            DB::table('tbl_acciones')->updateOrInsert(
                ['nombre' => $accion],
                ['descripcion' => $accion]
            );
        }

        // ==============================
        // 3️⃣ RELACIONAR MÓDULO-ACCIÓN
        // ==============================

        $modulosDB = DB::table('tbl_modulos')->get();
        $accionesDB = DB::table('tbl_acciones')->get();

        foreach ($modulosDB as $modulo) {
            foreach ($accionesDB as $accion) {
                DB::table('tbl_modulo_acciones')->updateOrInsert([
                    'modulo_id' => $modulo->id,
                    'accion_id' => $accion->id,
                ]);
            }
        }

        // ==============================
        // 4️⃣ DAR TODOS LOS PERMISOS AL COORDINADOR
        // ==============================

        $coordinador = DB::table('tbl_roles')
            ->where('nombre', 'Coordinador')
            ->first();

        $moduloAcciones = DB::table('tbl_modulo_acciones')->get();

        foreach ($moduloAcciones as $ma) {
            DB::table('tbl_role_permisos')->updateOrInsert([
                'role_id' => $coordinador->id,
                'modulo_accion_id' => $ma->id,
            ]);
        }
    }
}