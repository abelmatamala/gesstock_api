<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CodigoQr extends Model
{
    protected $table = 'tbl_codigos_qr'; // 👈 ajusta si tu tabla tiene otro nombre

    protected $fillable = [
        'sucursal_id',
        'codigo',
        'token',
        'activo',
        'expira_en'
    ];

    protected $casts = [
        'activo' => 'boolean',
        'expira_en' => 'datetime'
    ];
}