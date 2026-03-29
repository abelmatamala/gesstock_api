<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, $roleId)
    {
        $user = $request->user();

        if (!$user) {
            return response()->json(['message' => 'No autenticado'], 401);
        }

        // Asumiendo que el modelo tiene relación roles()
        if (!method_exists($user, 'roles')) {
            return response()->json(['message' => 'Roles no configurados'], 500);
        }

        $roles = $user->roles->pluck('id')->toArray();

        if (!in_array((int)$roleId, $roles)) {
            return response()->json(['message' => 'No autorizado'], 403);
        }

        return $next($request);
    }
}