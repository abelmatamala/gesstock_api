<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Google\Auth\Credentials\ServiceAccountCredentials;

class FirebaseService
{
    public static function enviarNotificacion($token, $titulo = null, $mensaje = null, $data = [])
    {
        try {

            if (is_array($titulo) && $mensaje === null) {
                $data = $titulo;
                $titulo = $data["Titulo"] ?? $data["titulo"] ?? null;
                $mensaje = $data["Mensaje"] ?? $data["mensaje"] ?? null;
            }

            $data = $data ?? [];

            if (empty($token)) {
                Log::warning("FCM token vacÃ­o, no se envÃ­a notificaciÃ³n");
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
            if ($titulo !== null) {
                $data["Titulo"] = $titulo;
            }

            if ($mensaje !== null) {
                $data["Mensaje"] = $mensaje;
            }

            $payloadData = array_map('strval', $data);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$accessToken}",
                'Content-Type' => 'application/json'
            ])->post(
                    "https://fcm.googleapis.com/v1/projects/{$projectId}/messages:send",
                    [
                        "message" => [
                            "token" => $token,
                            "notification" => [
                                "title" => $titulo ?? ($payloadData["Titulo"] ?? ""),
                                "body" => $mensaje ?? ($payloadData["Mensaje"] ?? "")
                            ],
                            "android" => [
                                "priority" => "high"
                            ],
                            "data" => $payloadData
                        ]
                    ]
                );

            // ðŸ” LOG CLAVE
            Log::info("FCM RESPONSE", [
                "status" => $response->status(),
                "body" => $response->json()
            ]);
            // ðŸ”¥ Manejo de token invÃ¡lido
            if (
                isset($response['error']['details'][0]['errorCode']) &&
                $response['error']['details'][0]['errorCode'] === 'UNREGISTERED'
            ) {
                Log::warning("Token invÃ¡lido detectado", ["token" => $token]);
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
            Log::error("ExcepciÃ³n enviando FCM: " . $e->getMessage());
            return false;
        }
    }
    /*public static function enviarNotificacion($token, $titulo, $mensaje, $data = [])
    {
        try {

            // Validar token
            if (empty($token)) {
                Log::warning("FCM token vacÃ­o, no se envÃ­a notificaciÃ³n");
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

            // Enviar notificaciÃ³n
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
                            "data" => array_map('strval', $data ?? []) // ðŸ‘ˆ NUEVO
                        ]
                    ]
                );
            if (
                isset($response['error']['details'][0]['errorCode']) &&
                $response['error']['details'][0]['errorCode'] === 'UNREGISTERED'
            ) {

                Log::warning("Token invÃ¡lido detectado", [
                    "token" => $token
                ]);
            }

            // Registrar respuesta para depuraciÃ³n
            Log::info("Respuesta FCM", [
                "status" => $response->status(),
                "body" => $response->json()
            ]);

            // Verificar si Firebase devolviÃ³ error
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
            Log::error("ExcepciÃ³n enviando FCM: " . $e->getMessage());

            return false;
        }
    }*/

    public static function enviarNotificacionMultiple($tokens, $titulo, $mensaje, $data = [])
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