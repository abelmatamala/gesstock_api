<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Tymon\JWTAuth\Facades\JWTAuth;

class JwtMiddleware
{
    
    public function handle(Request $request, Closure $next)
    {
        try {

            $usuario = JWTAuth::parseToken()->authenticate();

            if (!$usuario) {
                return response()->json(['error' => 'Usuario no encontrado'], 401);
            }

            if (!$usuario->activo) {
                return response()->json(['error' => 'Usuario inactivo'], 403);
            }

        } catch (\Exception $e) {
            return response()->json(['error' => 'Token inválido o ausente'], 401);
        }

        return $next($request);
    }
}