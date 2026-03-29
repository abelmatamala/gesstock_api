<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AuthController extends Controller
{
    public function registrarDispositivo(Request $request)
    {
        //\Log::info("Entró a registrarDispositivo");
        $request->validate([
            "fcm_token" => "required|string",
        ]);

        $user = auth()->user();

        if (!$user) {
            //\Log::info("Usuario no autenticado");
            return response()->json(
                [
                    "error" => "Usuario no autenticado",
                ],
                401
            );
        }

        /*\Log::info("Registrando FCM", [
            "user_id" => $user->id,
            "token" => $request->fcm_token,
        ]);*/

        DB::table("tbl_usuarios")
            ->where("id", $user->id)
            ->update([
                "fcm_token" => $request->fcm_token,
            ]);

        return response()->json([
            "success" => true,
        ]);
    }
    public function login(Request $request)
    {
        // 🔹 Validación
        $validator = \Validator::make($request->all(), [
            "run" => "required",
            "password" => "required",
            "platform" => "required|in:web,app",
        ]);

        if ($validator->fails()) {
            return response()->json(
                [
                    "error" => $validator->errors()->first(),
                ],
                422
            );
        }

        $credentials = $request->only("run", "password");
        $platform = $request->platform;

        // 🔹 Autenticación
        try {
            $token = auth()->attempt($credentials);
        } catch (\Exception $e) {
            \Log::error($e);

            return response()->json(
                [
                    "error" => "Error interno del servidor",
                ],
                500
            );
        }

        if (!$token) {
            return response()->json(
                [
                    "error" => "Credenciales inválidas",
                ],
                401
            );
        }

        $user = auth()->user();

        if (!$user->activo) {
            return response()->json(
                [
                    "error" => "Usuario inactivo",
                ],
                403
            );
        }

        // 🔹 Obtener roles del usuario (N:M)
        $rolesIds = \DB::table("tbl_usuario_rol")
            ->where("usuario_id", $user->id)
            ->pluck("rol_id");

        if ($rolesIds->isEmpty()) {
            return response()->json(
                [
                    "error" => "Usuario sin roles asignados",
                ],
                403
            );
        }

        $rolesArray = $rolesIds->toArray();

        // 🔹 Validar acceso por plataforma
        $tienePermisos = \DB::table("tbl_roles_modulo_accion as rma")
            ->join("tbl_modulos as m", "rma.modulo_id", "=", "m.id")
            ->whereIn("rma.rol_id", $rolesArray)
            ->whereIn("m.platform", [$platform, "both"])
            ->exists();

        if (!$tienePermisos) {
            return response()->json(
                [
                    "error" => "Usuario no autorizado para esta plataforma",
                ],
                403
            );
        }

        // 🔹 Obtener permisos completos
        $permisos = \DB::table("tbl_roles_modulo_accion as rma")
            ->join("tbl_modulos as m", "rma.modulo_id", "=", "m.id")
            ->join("tbl_acciones as a", "rma.accion_id", "=", "a.id")
            ->whereIn("rma.rol_id", $rolesArray)
            ->whereIn("m.platform", [$platform, "both"])
            ->select("m.nombre as modulo", "a.nombre as accion")
            ->get();

        $permisosAgrupados = [];

        foreach ($permisos as $permiso) {
            $permisosAgrupados[$permiso->modulo][] = $permiso->accion;
        }

        // 🔹 Obtener sucursales
        $sucursales = \DB::table("tbl_usuario_sucursal as us")
            ->join("tbl_sucursales as s", "us.sucursal_id", "=", "s.id")
            ->where("us.usuario_id", $user->id)
            ->select("s.id", "s.nombre")
            ->get();

        return response()->json([
            "success" => true,
            "token" => $token,
            "usuario" => [
                "id" => $user->id,
                "run" => $user->run,
                "nombre" => $user->nombre,
                "apellido" => $user->apellido,
                "nombre_completo" => trim($user->nombre . " " . $user->apellido),
            ],
            "platform" => $platform,
            "roles" => $rolesArray,
            "sucursales" => $sucursales,
            "permissions" => $permisosAgrupados,
        ]);
    }
}
