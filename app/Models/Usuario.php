<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Tymon\JWTAuth\Contracts\JWTSubject;

class Usuario extends Authenticatable implements JWTSubject
{
    protected $table = 'tbl_usuarios';

    protected $fillable = [
        'run',
        'password_hash',
        'rol_id',
        'sucursal_id',
        'device_id',
        'activo'
    ];

    protected $hidden = [
        'password_hash'
    ];

    // 👇 ESTA PARTE FALTABA
    public function getAuthPassword()
    {
        return $this->password_hash;
    }

    // 🔐 JWT
    public function getJWTIdentifier()
    {
        return $this->getKey();
    }

    public function getJWTCustomClaims()
    {
        return [];
    }

    // Relaciones
    public function rol()
    {
        return $this->belongsTo(Rol::class, 'rol_id');
    }
    public function roles()
{
    return $this->belongsToMany(
        \App\Models\Rol::class,
        'tbl_usuario_rol',
        'usuario_id',
        'rol_id'
    );
}
    public function hasPermission($modulo, $accion)
    {
    return \DB::table('tbl_role_permisos as rp')
        ->join('tbl_modulo_acciones as ma', 'rp.modulo_accion_id', '=', 'ma.id')
        ->join('tbl_modulos as m', 'ma.modulo_id', '=', 'm.id')
        ->join('tbl_acciones as a', 'ma.accion_id', '=', 'a.id')
        ->where('rp.role_id', $this->rol_id)
        ->where('m.nombre', $modulo)
        ->where('a.nombre', $accion)
        ->exists();
    }
}