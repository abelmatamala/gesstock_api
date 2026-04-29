<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseService
{
    public static function enviarNotificacion($token, $data = [])
    {
        try {

            if (empty($token)) {
                Log::warning("FCM token vacío, no se envía notificación");
                return false;
            }

            $credentials = new ServiceAccountCredentials(
                ["https://www.googleapis.com/auth/firebase.messaging"],
                storage_path('app/firebase/firebase.json')
            );

            $authToken = $credentials->fetchAuthToken();

            if (!isset($authToken['access_token'])) {
                Log::error("No se pudo obtener access_token de Firebase");
                return false;
            }

            $accessToken = $authToken['access_token'];
            $projectId = 'turno-app-926b6';

            // 🔥 IMPORTANTE
            $payloadData = array_map('strval', $data);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        "message" => [
                            "token" => $token,
                            "android" => [
                                "priority" => "high"
                            ],
                            "data" => $payloadData
                        ]
                    ]
                );

            // 🔍 LOG CLAVE
            Log::info("FCM RESPONSE", [
                "status" => $response->status(),
                "body" => $response->json()
            ]);
            // 🔥 Manejo de token inválido
            if (
                isset($response['error']['details'][0]['errorCode']) &&
                $response['error']['details'][0]['errorCode'] === 'UNREGISTERED'
            ) {
                Log::warning("Token inválido detectado", ["token" => $token]);

                // 👉 OPCIONAL PERO RECOMENDADO
                // DB::table('tbl_usuarios')->where('fcm_token', $token)->update(['fcm_token' => null]);
            }

            Log::info("Respuesta FCM", [
                "status" => $response->status(),
                "body" => $response->json()
            ]);

            if (!$response->successful()) {
                Log::error("Error enviando FCM", [
                    "status" => $response->status(),
                    "body" => $response->body()
                ]);
                return false;
            }

            return $response->json();

        } catch (\Throwable $e) {
            Log::error("Excepción enviando FCM: " . $e->getMessage());
            return false;
        }
    }
    /*public static function enviarNotificacion($token, $titulo, $mensaje, $data = [])
    {
        try {

            // Validar token
            if (empty($token)) {
                Log::warning("FCM token vacío, no se envía notificación");
                return false;
            }

            // Credenciales de Firebase
            $credentials = new ServiceAccountCredentials(
                ["https://www.googleapis.com/auth/firebase.messaging"],
                storage_path('app/firebase/firebase.json')
            );

            // Obtener access token
            $authToken = $credentials->fetchAuthToken();

            if (!isset($authToken['access_token'])) {
                Log::error("No se pudo obtener access_token de Firebase");
                return false;
            }

            $accessToken = $authToken['access_token'];

            $projectId = 'turno-app-926b6';

            // Enviar notificación
            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->post("https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        "message" => [
                            "token" => $token,
                            "notification" => [
                                "title" => $titulo,
                                "body" => $mensaje
                            ],
                            "data" => array_map('strval', $data ?? []) // 👈 NUEVO
                        ]
                    ]
                );
            if (
                isset($response['error']['details'][0]['errorCode']) &&
                $response['error']['details'][0]['errorCode'] === 'UNREGISTERED'
            ) {

                Log::warning("Token inválido detectado", [
                    "token" => $token
                ]);
            }

            // Registrar respuesta para depuración
            Log::info("Respuesta FCM", [
                "status" => $response->status(),
                "body" => $response->json()
            ]);

            // Verificar si Firebase devolvió error
            if (!$response->successful()) {
                Log::error("Error enviando FCM", [
                    "status" => $response->status(),
                    "body" => $response->body()
                ]);
                return false;
            }

            return $response->json();

        } catch (\Throwable $e) {

            // Nunca romper el flujo del sistema
            Log::error("Excepción enviando FCM: " . $e->getMessage());

            return false;
        }
    }*/

    public static function enviarNotificacionMultiple($tokens, $titulo, $mensaje, $data)
    {
        $tokens = array_unique(array_filter($tokens));

        foreach ($tokens as $token) {

            self::enviarNotificacion(
                $token,
                $titulo,
                $mensaje,
                $data
            );
        }
    }

}