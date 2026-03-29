<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\DB;
use App\Services\FirebaseService;

class EnviarTurnosFCM
{
    use Dispatchable, Queueable, SerializesModels;

    protected $ids;

    public function __construct($ids)
    {
        $this->ids = $ids;
    }

public function handle()
{
    \Log::info("Job EnviarTurnosFCM iniciado", ["ids" => $this->ids]);

    $turnos = DB::table("tbl_lista_espera")
        ->whereIn("id", $this->ids)
        ->get();

    $tokens = [];

    foreach ($turnos as $turno) {

        $usuario = DB::table("tbl_usuarios")
            ->where("id", $turno->usuario_id)
            ->first();

        if ($usuario && $usuario->fcm_token) {
            $tokens[] = $usuario->fcm_token;
        }
    }

    if (!empty($tokens)) {
            \Log::info("Job EnviarTurnosFCMMultiple iniciado", ["ids" => $this->ids]);
        FirebaseService::enviarNotificacionMultiple(
            $tokens,
            "Es tu turno",
            "Puedes tomar pedido"
        );
    }
}

}