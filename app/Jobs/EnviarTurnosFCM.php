<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\FirebaseService;

class EnviarTurnosFCM implements ShouldQueue
{
    use Dispatchable, Queueable, SerializesModels;

    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

    public function handle()
    {
        $turnos = DB::table("tbl_lista_espera")
            ->whereIn("id", $this->ids)
            ->get();

        \Log::info("Worker ejecutando FCM");

        foreach ($turnos as $turno) {

            $usuario = DB::table("tbl_usuarios")
                ->where("id", $turno->usuario_id)
                ->first();

            if (!$usuario || !$usuario->fcm_token) {
                continue;
            }

            try {

                FirebaseService::enviarNotificacion(
                    $usuario->fcm_token,
                    "Es tu turno",
                    "Puedes tomar pedido"
                );

            } catch (\Exception $e) {

                \Log::error(
                    "Error FCM turno ".$turno->id." : ".$e->getMessage()
                );

            }
        }
    }
}