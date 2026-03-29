<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Sucursal extends Model
{
    protected $table = 'tbl_sucursales';

    protected $fillable = [
        'nombre',
        'latitud',
        'longitud',
        'activa'
    ];
}