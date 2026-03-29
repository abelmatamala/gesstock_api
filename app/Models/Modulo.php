<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Modulo extends Model
{
    protected $table = 'tbl_modulos';

    protected $fillable = [
        'nombre',
        'platform'
    ];

    public $timestamps = false;

    // Relación con roles (N:M)
    public function roles()
    {
        return $this->belongsToMany(
            Rol::class,
            'tbl_roles_modulos',
            'modulo_id',
            'rol_id'
        );
    }
}