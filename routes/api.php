<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\TurnoController;
use App\Http\Controllers\Api\QrController;

/*
|--------------------------------------------------------------------------
| LOGIN
|--------------------------------------------------------------------------
*/

Route::post("/login", [AuthController::class, "login"]);

/*
|--------------------------------------------------------------------------
| RUTAS WEB (Supervisor)
|--------------------------------------------------------------------------
*/
Route::prefix("supervisor")
    ->middleware(["jwt", "role:1"])
    ->group(function () {
        Route::get("/qr-activo/{sucursal}", [\App\Http\Controllers\Api\QrController::class, "qrActivo"]);
    });

Route::prefix("web")
    ->middleware("jwt")
    ->group(function () {
        // 🔹 LISTAR TURNOS DEL DÍA POR SUCURSAL
        Route::get("/lista-turnos/{sucursal}", [TurnoController::class, "lista"])->middleware("permiso:turnos,ver");

        // 🔹 ASIGNAR TURNO (cambia estado → editar)
        Route::post("/asignar-turno", [TurnoController::class, "asignar"])->middleware("permiso:turnos,aprobar_turno");
    
        // 🔹 ASIGNAR TURNO (cambia estado → editar)
        Route::post("/asignar-todos", [TurnoController::class, "asignartodos"])->middleware("permiso:turnos,aprobar_turno");

        // 🔹 CREAR TURNO MANUAL
        Route::post("/tomar-turno", [TurnoController::class, "crear"])->middleware("permiso:tomar-turno,crear");

        // 🔹 SUCURSALES DEL USUARIO
        Route::get("/mis-sucursales", function () {
            $user = auth()->user();

            $sucursales = \DB::table("tbl_usuario_sucursal as us")
                ->join("tbl_sucursales as s", "us.sucursal_id", "=", "s.id")
                ->where("us.usuario_id", $user->id)
                ->where("s.activa", 1)
                ->select("s.id", "s.nombre")
                ->get();

            return response()->json($sucursales);
        });
    });

Route::prefix("app")
    ->middleware("jwt")
    ->group(function () {
        Route::post("/tomar-turno", [TurnoController::class, "tomarTurnoAndroid"])
            ->middleware("permiso:tomar-turno,crear"
        );
        
        Route::post("/turno-activo", [TurnoController::class, "TurnoActivo"])
            ->middleware("permiso:tomar-turno,crear"
        );

        // 👇 NUEVA RUTA PARA CONSULTAR ESTADO
        Route::get("/estado/{id}", [TurnoController::class, "estado"])
            ->middleware("permiso:tomar-turno,ver"
        );

        Route::get("/anular/{id}", [TurnoController::class, "anular"])
            ->middleware("permiso:tomar-turno,crear"
        );
        // 🔹 INGRESAR POSICION EN LA SUCURSAL
        Route::post("/posicion", [TurnoController::class, "posicionamiento"])
            ->middleware("permiso:posicion,editar"
        );
       Route::post("/registrar-dispositivo", [AuthController::class, "registrarDispositivo"])->middleware("jwt");
    });

