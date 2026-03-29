<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class CheckPermiso
{
    public function handle($request, Closure $next, $modulo, $accion)
{
    $user = auth()->user();

    if (!$user) {
        return response()->json(['error' => 'No autenticado'], 401);
    }

    // 🔹 Obtener roles del usuario
    $rolesIds = \DB::table('tbl_usuario_rol')
        ->where('usuario_id', $user->id)
        ->pluck('rol_id')
        ->toArray();

    if (empty($rolesIds)) {
        return response()->json(['error' => 'Sin roles'], 403);
    }

// 🔹 Validar permiso (nuevo esquema granular)
    $tienePermiso = \DB::table('tbl_usuario_rol as ur')
        ->join('tbl_roles_modulo_accion as rma', 'ur.rol_id', '=', 'rma.rol_id')
        ->join('tbl_modulos as m', 'rma.modulo_id', '=', 'm.id')
        ->join('tbl_acciones as a', 'rma.accion_id', '=', 'a.id')
        ->where('ur.usuario_id', $user->id)
        ->where('m.nombre', $modulo)
        ->where('a.nombre', $accion)
        ->exists();

    if (!$tienePermiso) {
        return response()->json(['error' => 'No autorizado'], 403);
    }

    return $next($request);
}
}