<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UsuariosSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tbl_usuarios')->insert([
            [
                'run' => '11111111-1',
                'password_hash' => Hash::make('123456'),
                'rol_id' => 1, // supervisor
                'sucursal_id' => 1,
                'device_id' => null,
                'activo' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}