<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SucursalMiddleware
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = auth()->user();

        if (!$user) {
            return response()->json([
                'error' => 'Usuario no autenticado'
            ], 401);
        }

        $sucursalId = $request->header('X-Sucursal-ID');

        if (!$sucursalId) {
            return response()->json([
                'error' => 'Sucursal no especificada'
            ], 400);
        }

        // Validar que la sucursal sea numérica
        if (!is_numeric($sucursalId)) {
            return response()->json([
                'error' => 'Sucursal inválida'
            ], 400);
        }

        // Verificar que el usuario pertenezca a esa sucursal
        $pertenece = $user->sucursales()
            ->where('tbl_sucursales.id', $sucursalId)
            ->exists();

        if (!$pertenece) {
            return response()->json([
                'error' => 'No pertenece a esa sucursal'
            ], 403);
        }

        // Inyectar sucursal activa en el request
        $request->merge([
            'sucursal_id' => (int) $sucursalId
        ]);

        return $next($request);
    }
}
    
