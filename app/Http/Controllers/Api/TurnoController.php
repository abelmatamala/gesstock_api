<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request; // 👈 ESTE FALTABA
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Services\FirebaseService;
use App\Models\CodigoQr;
use App\Models\Sucursal;
use App\Models\Turno;
use App\jobs\EnviarTurnosFCM;

class TurnoController extends Controller
{
    // 📋 Lista de turnos por sucursal
    public function lista($sucursalId)
    {
        $hoy = now("America/Santiago")->format("Y-m-d");

        return DB::table("tbl_lista_espera as le")
            ->join("tbl_usuarios as u", "le.usuario_id", "=", "u.id")
            ->where("le.sucursal_id", $sucursalId)
            ->whereDate("le.fecha_ingreso", $hoy)
            ->orderBy("le.fecha_ingreso", "asc")
            ->select(
                "le.id",
                "le.estado",
                "le.numero_turno",
                "le.fecha_ingreso",
                "le.fecha_asignado",
                DB::raw("CONCAT(u.nombre,' ',u.apellido) as nombre_usuario")
            )
            ->get();
    }

    // 🔄 Asignar turno
    public function asignar(Request $request)
    {
        $sucursalId = $request->sucursal_id;

        $turno = DB::table("tbl_lista_espera")
            ->where("sucursal_id", $request->sucursal_id)
            ->whereDate("fecha_ingreso", today())
            ->where("estado", "en_espera")
            ->orderBy("fecha_ingreso", "asc")
            ->first();

        if (!$turno) {
            return response()->json(
                [
                    "message" => "No hay turnos pendientes",
                ],
                404
            );
        }

        DB::table("tbl_lista_espera")
            ->where("id", $turno->id)
            ->where("estado", "en_espera")
            ->update([
                "estado" => "asignado",
                "fecha_asignado" => now(),
            ]);

        // 🔔 Enviar notificación al usuario
        $usuario = DB::table("tbl_usuarios")
            ->where("id", $turno->usuario_id)
            ->first();
        if ($usuario && $usuario->fcm_token) {
            try {
                FirebaseService::enviarNotificacion($usuario->fcm_token, "Es tu turno", "Puedes tomar pedido");
            } catch (\Exception $e) {
                \Log::error("Error enviando FCM: " . $e->getMessage());
            }
        }

        return response()->json([
            "mensaje" => "Turno asignado correctamente",
            "turno_id" => $turno->id,
        ]);
    }

    // 🔄 Asignar turno a todos
    public function asignarTodos(Request $request)
    {
        $sucursalId = $request->sucursal_id;

        $turnos = DB::table("tbl_lista_espera")
            ->where("sucursal_id", $sucursalId)
            ->whereDate("fecha_ingreso", today())
            ->where("estado", "en_espera")
            ->orderBy("fecha_ingreso", "asc")
            ->get();

        if ($turnos->isEmpty()) {
            return response()->json(
                [
                    "message" => "No hay turnos pendientes",
                ],
                404
            );
        }

        $ids = $turnos->pluck("id");

        DB::table("tbl_lista_espera")
            ->whereIn("id", $ids)
            ->update([
                "estado" => "asignado",
                "fecha_asignado" => now(),
            ]);

        dispatch(new EnviarTurnosFCM($ids));

        return response()->json([
            "mensaje" => "Turnos asignados",
            "cantidad" => $ids->count(),
        ]);
    }
    // ➕ Crear turno manual

    public function tomarTurnoAndroid(Request $request)
    {
        return DB::transaction(function () use ($request) {
            // 🔎 Validar QR activo
            $qr = CodigoQr::where("token", trim($request->qr_token))
                ->where("activo", 1)
                ->lockForUpdate()
                ->first();

            if (!$qr) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "QR inválido o ya utilizado",
                    ],
                    403
                );
            }

            // 🏢 Validar sucursal
            $sucursal = Sucursal::find($qr->sucursal_id);

            if (!$sucursal) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Sucursal no encontrada",
                    ],
                    404
                );
            }

            $usuarioSucursal = DB::table("tbl_usuario_sucursal")
                ->where("usuario_id", auth()->id())
                ->where("sucursal_id", $sucursal->id)
                ->exists();

            if (!$usuarioSucursal) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "No tienes acceso a esta sucursal",
                    ],
                    403
                );
            }

            // 🔒 Verificar duplicado en espera
            $yaEnEspera = DB::table("tbl_lista_espera")
                ->where("usuario_id", auth()->id())
                ->where("sucursal_id", $sucursal->id)
                ->whereDate("fecha_ingreso", now()->toDateString())
                ->where("estado", "en_espera")
                ->exists();

            if ($yaEnEspera) {
                return response()->json(
                    [
                        "success" => false,
                        "message" => "Ya tienes un turno en espera",
                    ],
                    409
                );
            }

            // 📍 Validar geolocalización
            $request->validate([
                "lat" => "nullable|numeric",
                "lng" => "nullable|numeric",
            ]);

            $estado = "fuera_de_rango";
            $numeroTurno = null;
            $distancia = null;

            if (
                is_numeric($request->lat) &&
                is_numeric($request->lng) &&
                $request->lat >= -90 &&
                $request->lat <= 90 &&
                $request->lng >= -180 &&
                $request->lng <= 180
            ) {
                $latUsuario = (float) $request->lat;
                $lngUsuario = (float) $request->lng;

                $latSucursal = (float) $sucursal->lat;
                $lngSucursal = (float) $sucursal->lng;
                $radioPermitido = (int) $sucursal->radio_metros;

                $earthRadius = 6371000;

                $dLat = deg2rad($latUsuario - $latSucursal);
                $dLng = deg2rad($lngUsuario - $lngSucursal);

                $a =
                    sin($dLat / 2) * sin($dLat / 2) +
                    cos(deg2rad($latSucursal)) * cos(deg2rad($latUsuario)) * sin($dLng / 2) * sin($dLng / 2);

                $c = 2 * atan2(sqrt($a), sqrt(1 - $a));
                $distancia = $earthRadius * $c;

                // ✅ Solo si está dentro del radio
                if ($distancia <= $radioPermitido) {
                    $hoy = now()->toDateString();

                    $ultimoNumero = DB::table("tbl_lista_espera")
                        ->where("sucursal_id", $sucursal->id)
                        ->whereDate("fecha_ingreso", $hoy)
                        ->max("numero_turno");

                    $numeroTurno = $ultimoNumero ? $ultimoNumero + 1 : 1;
                    $estado = "en_espera";
                }
            }

            // 📝 Crear turno
            $turno = Turno::create([
                "sucursal_id" => $sucursal->id,
                "usuario_id" => auth()->id(),
                "numero_turno" => $numeroTurno,
                "estado" => $estado,
                "fecha_ingreso" => now(),
                "fecha_asignado" => null,
            ]);

            // 🔄 Invalidar QR
            $qr->update(["activo" => 0]);

            // 🔁 Generar nuevo QR
            CodigoQr::create([
                "sucursal_id" => $sucursal->id,
                "token" => Str::random(64),
                "activo" => 1,
            ]);

            //$estadoPantalla = $this->Estado(auth()->id());

            if ($estado === "en_espera") {
                $mensaje = "Turno registrado correctamente";
                $success = true;
            } else {
                $mensaje = "Estás fuera del rango de la sucursal";
                $success = false;
            }

            return response()->json(
                array_merge(
                    [
                        "success" => $success,
                        "message" => $mensaje,
                        "turno_id" => $turno->id,
                        "distancia_metros" => $distancia ? round($distancia, 2) : null,
                    ],
                    $estadoPantalla ?? []
                )
            );
        });
    }


    public function anular($id)
    {
        $turno = DB::table("tbl_lista_espera")
            ->where("id", $id)
            ->where("usuario_id", auth()->id())
            ->first();

        if (!$turno) {
            return response()->json([
                "success" => false,
                "message" => "Turno no encontrado"
            ], 404);
        }

        if ($turno->estado !== "en_espera") {
            return response()->json([
                "success" => false,
                "message" => "No se puede anular este turno"
            ], 400);
        }

        DB::table("tbl_lista_espera")
            ->where("id", $id)
            ->update([
                "estado" => "anulado"
            ]);

        return response()->json([
            "success" => true,
            "message" => "Turno anulado correctamente"
        ]);
    }


    public function estado($id)
    {
        $turno = DB::table("tbl_lista_espera")->where("id", $id)->first();

        if (!$turno) {
            return response()->json(
                [
                    "success" => false,
                    "message" => "Turno no encontrado",
                ],
                404
            );
        }

        $miTurno = $turno->numero_turno;

        $personasAdelante = DB::table("tbl_lista_espera")
            ->where("sucursal_id", $turno->sucursal_id)
            ->whereDate("fecha_ingreso", today())
            ->where("estado", "en_espera")
            ->where("numero_turno", "<", $miTurno)
            ->count();

        $totalEnEspera = DB::table("tbl_lista_espera")
            ->where("sucursal_id", $turno->sucursal_id)
            ->whereDate("fecha_ingreso", today())
            ->where("estado", "en_espera")
            ->count();

        return response()->json([
            "success" => true,
            "estado" => $turno->estado,
            "mi_turno" => $miTurno,
            "personas_adelante" => $personasAdelante,
            "total_en_espera" => $totalEnEspera,
        ]);
    }

    // ver turnos activos en espera
    public function TurnoActivo(Request $request)
    {
        $turno = DB::table("tbl_lista_espera")
            ->where("usuario_id", $request->user()->id)
            ->whereDate("fecha_ingreso", today())
            ->where("estado", "en_espera")
            ->first();

        if (!$turno) {
            return response()->json([
                "success" => false,
            ]);
        }

        return response()->json([
            "success" => true,
            "turno_id" => $turno->id,
        ]);
    }

        public function posicionamiento(Request $request)
    {
        $request->validate([
            'sucursal_id' => 'required',
            'lat' => 'required',
            'lng' => 'required'
        ]);

        DB::table('tbl_sucursales')
            ->where('id', $request->sucursal_id)
            ->update([
                'lat' => $request->lat,
                'lng' => $request->lng,
                'updated_at' => now()
            ]);

        return response()->json([
            'ok' => true,
            'mensaje' => 'Ubicación de sucursal actualizada'
        ]);
    }
}


