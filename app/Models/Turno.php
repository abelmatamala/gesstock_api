<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class Turno extends Model
{
    protected $table = 'tbl_lista_espera';

        protected $fillable = [
        'sucursal_id',
        'usuario_id',
        'numero_turno',
        'estado',
        'fecha_ingreso',
        'fecha_asignado'
    ];

    public $timestamps = false;
}

