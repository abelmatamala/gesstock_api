<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use App\Models\CodigoQr;
use App\Models\Sucursal;
use Illuminate\Http\Request;

class QrController extends Controller
{
    public function qrActivo($sucursalId)
        
    {
        //Log::info(auth()->user());
        return DB::transaction(function () use ($sucursalId) {

            // Validar que la sucursal exista
            $sucursal = Sucursal::find($sucursalId);

            if (!$sucursal) {
                return response()->json([
                    'success' => false,
                    'message' => 'Sucursal no existe'
                ], 404);
            }

            // Buscar QR activo con lock
            $qr = CodigoQr::where('sucursal_id', $sucursalId)
                ->where('activo', 1)
                ->lockForUpdate()
                ->first();

            // Si no existe, lo creamos
            if (!$qr) {

                $qr = CodigoQr::create([
                    'sucursal_id' => $sucursalId,
                    'token' => Str::random(64),
                    'activo' => 1
                ]);
            }

            return response()->json([
                'success' => true,
                'sucursal_id' => $sucursalId,
                'qr_token' => $qr->token
            ], 200);
        });
    }
}