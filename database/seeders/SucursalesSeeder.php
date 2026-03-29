<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class SucursalesSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('tbl_sucursales')->insert([
            [
                'nombre' => 'Sucursal Centro',
                'lat' => -33.4489,
                'lng' => -70.6693,
                'radio_metros' => 150,
                'activa' => true,
                'created_at' => now(),
                'updated_at' => now(),
            ]
        ]);
    }
}