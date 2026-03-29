<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Rol extends Model
{
    protected $table = 'tbl_roles';

    protected $fillable = [
        'nombre',
        'acceso_web',
        'acceso_android'
    ];

    public $timestamps = false;

    // 🔹 Relación con módulos (N:M)
    public function modulos()
    {
        return $this->belongsToMany(
            Modulo::class,
            'tbl_roles_modulos',
            'rol_id',
            'modulo_id'
        );
    }

    // 🔹 Relación con usuarios (N:M)
    public function usuarios()
    {
        return $this->belongsToMany(
            Usuario::class,
            'tbl_usuario_rol',
            'rol_id',
            'usuario_id'
        );
    }
}
