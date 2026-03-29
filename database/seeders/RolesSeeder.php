<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class RolesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tbl_roles')->insert([
            [
                'nombre' => 'supervisor',
                'acceso_web' => true,
                'acceso_android' => false,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'operario1',
                'acceso_web' => false,
                'acceso_android' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'nombre' => 'operario2',
                'acceso_web' => false,
                'acceso_android' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}