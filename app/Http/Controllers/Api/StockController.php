<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class StockController extends Controller
{

    // ðŸ”¹ RESPUESTA OK UNIFICADA
    private function ok($data = null)
    {
        return response()->json([
            'success' => true,
            'data' => $data,
            'error' => null
        ]);
    }

    // ðŸ”¹ RESPUESTA ERROR UNIFICADA
    private function fail($message, $code = 400)
    {
        return response()->json([
            'success' => false,
            'data' => null,
            'error' => $message
        ], $code);
    }

    // =========================================================

    public function buscarRequerimiento(Request $request)
    {
        $request->validate([
            'numero_articulo' => 'required|string|max:50'
        ]);

        $user = auth()->user();

        $reqs = DB::table('tbl_requerimientos as r')
            ->join('tbl_articulos as a', 'a.id', '=', 'r.articulo_id')
            ->where('a.numero_articulo', $request->numero_articulo)
            ->where('r.sucursal_idq', $user->sucursal_id)
            ->whereDate('r.created_at', '>=', now()->subDay())
            ->orderByDesc('r.created_at')
            ->get([
                'r.id',
                'r.estado',
                'r.created_at',
                'r.ultima_respuesta_at'
            ]);

        return $this->ok($reqs);
    }

    // =========================================================

    public function crearRequerimiento(Request $request)
    {
        $request->validate([
            'numero_articulo' => 'required|string|max:50',
            'descripcion' => 'required|string|max:255',
            'sucursal_id' => 'required|integer',
            'cantidad' => 'required|integer',
            'imagenes' => 'required|array|min:1|max:3',
            'imagenes.*' => 'image|max:2048'
        ]);

        $user = auth()->user();
        $now = now();
        $sucursal = DB::table('tbl_sucursales')
            ->where('id', $request->sucursal_id)
            ->first();

        if (!$sucursal) {
            return $this->fail('Sucursal no válida', 400);
        }

        $tiempoEspera = $sucursal->tiempo_espera;

        DB::beginTransaction();

        try {

            // ðŸ”¹ Buscar o crear artÃ­culo
            $articulo = DB::table('tbl_articulos')
                ->where('numero_articulo', $request->numero_articulo)
                ->first();

            if (!$articulo) {

                $articuloId = DB::table('tbl_articulos')->insertGetId([
                    'numero_articulo' => $request->numero_articulo,
                    'descripcion' => $request->descripcion,
                    'seccion_id' => $request->seccion_id,
                    'created_at' => $now
                ]);

            } else {

                $articuloId = $articulo->id;

                // 🔥 actualizar sección si viene nueva
                DB::table('tbl_articulos')
                    ->where('id', $articuloId)
                    ->update([
                        'descripcion' => $request->descripcion,
                        'seccion_id' => $request->seccion_id
                    ]);
            }

            // ðŸ”´ Validar duplicado
            $existeActivo = DB::table('tbl_requerimientos')
                ->where('articulo_id', $articuloId)
                ->where('usuario_crea_id', $user->id)
                ->where('sucursal_id', $request->sucursal_id)
                ->where('activo', true)
                ->exists();

            if ($existeActivo) {
                DB::rollBack();
                return $this->fail('Ya existe un requerimiento activo', 400);
            }

            $cierre = now()->addMinutes($tiempoEspera);

            // ðŸ”¹ Crear requerimiento
            $reqId = DB::table('tbl_requerimientos')->insertGetId([
                'producto_id' => $articuloId,
                'sucursal_id' => $request->sucursal_id,
                'usuario_crea_id' => $user->id,
                'estado' => 'Esperando...',
                'cantidad' => $request->cantidad,
                'fecha_cierre' => $cierre,
                'created_at' => $now
            ]);

            $urls = [];

            // ðŸ”¥ GUARDAR MÃšLTIPLES IMÃGENES
            foreach ($request->file('imagenes') as $index => $imagen) {

                $filename = $now->format('Ymd_His') . '_' .
                    $request->numero_articulo . '_' .
                    $user->id . '_' . $index . '.' .
                    $imagen->getClientOriginalExtension();

                $path = $imagen->storeAs(
                    'requerimientos',
                    $filename,
                    'public'
                );

                $urls[] = url('storage/' . $path);

                // ðŸ”¹ Historial por imagen
                DB::table('tbl_requerimiento_historial')->insert([
                    'requerimiento_id' => $reqId,
                    'usuario_id' => $user->id,
                    'comentario' => $request->descripcion,
                    'foto_url' => $path,
                    'created_at' => $now
                ]);
            }

            DB::commit();

            return $this->ok([
                'requerimiento_id' => $reqId,
                'imagenes' => $urls
            ]);

        } catch (\Exception $e) {

            DB::rollBack();
            return response()->json([
                'success' => false,
                'error' => $e->getMessage(),
                'line' => $e->getLine(),
                'file' => $e->getFile()
            ], 500);
            //return $this->fail('Error al crear requerimiento', 500);
        }
    }

    // =========================================================

    public function obtenerRespuestas(Request $request)
    {
        $request->validate([
            'requerimiento_id' => 'nullable|exists:tbl_requerimientos,id'
        ]);

        $ultimaRespuestaId = null;

        if ($request->filled('requerimiento_id')) {
            $ultimaRespuestaId = DB::table('tbl_requerimiento_historial')
                ->where('requerimiento_id', $request->requerimiento_id)
                ->whereNotNull('respuesta_id')
                ->orderByDesc('created_at')
                ->value('respuesta_id');
        }

        $respuestas = DB::table('tbl_respuestas as r')
            ->leftJoin('tbl_respuesta_flujo as f', function ($join) use ($ultimaRespuestaId) {
                $join->on('f.respuesta_destino_id', '=', 'r.id');

                if ($ultimaRespuestaId) {
                    $join->where('f.respuesta_origen_id', '=', $ultimaRespuestaId);
                } else {
                    $join->whereRaw('1 = 0');
                }
            })
            ->orderByDesc(DB::raw('COALESCE(f.total, 0)'))
            ->orderBy('r.nombre')
            ->get([
                'r.id',
                'r.nombre',
                'r.cierra_requerimiento',
                'r.icono',
                DB::raw('COALESCE(f.total, 0) as total')
            ]);

        return $this->ok($respuestas);
    }

    // =========================================================

    public function responder(Request $request)
    {
        $request->validate([
            'requerimiento_id' => 'required|exists:tbl_requerimientos,id',
            'respuesta_id' => 'required|exists:tbl_respuestas,id',
            'comentario' => 'nullable|string|max:255',
            'imagen' => 'nullable|image|max:2048'
        ]);

        $user = auth()->user();
        $now = now();

        $req = DB::table('tbl_requerimientos')
            ->where('id', $request->requerimiento_id)
            ->first();

        if (!$req || $req->activo !== true) {
            return $this->fail('Requerimiento no vÃ¡lido', 400);
        }

        // ðŸ”¹ Ãšltima respuesta
        $ultima = DB::table('tbl_requerimiento_historial')
            ->where('requerimiento_id', $req->id)
            ->where('es_ultima', 1)
            ->first();

        $origen = $ultima ? $ultima->respuesta_id : null;
        $destino = $request->respuesta_id;

        // ðŸ”¹ Imagen opcional
        $path = null;

        if ($request->hasFile('imagen')) {
            $filename = $now->format('Ymd_His') . '_' . $req->id . '_' . $user->id . '.' .
                $request->file('imagen')->extension();

            $path = $request->file('imagen')->storeAs(
                'requerimientos',
                $filename,
                'public'
            );
        }

        DB::table('tbl_requerimiento_historial')
            ->where('requerimiento_id', $req->id)
            ->update(['es_ultima' => 0]);

        DB::table('tbl_requerimiento_historial')->insert([
            'requerimiento_id' => $req->id,
            'usuario_id' => $user->id,
            'respuesta_id' => $destino,
            'comentario' => $request->comentario,
            'foto_url' => $path,
            'es_ultima' => 1,
            'created_at' => $now
        ]);

        if ($origen) {
            DB::table('tbl_respuesta_flujo')
                ->updateOrInsert(
                    [
                        'respuesta_origen_id' => $origen,
                        'respuesta_destino_id' => $destino
                    ],
                    [
                        'total' => DB::raw('total + 1')
                    ]
                );
        }

        $respuesta = DB::table('tbl_respuestas')
            ->where('id', $destino)
            ->first();

        $update = [
            'ultima_respuesta_at' => $now,
            'updated_at' => $now
        ];

        if ($respuesta->cierra_requerimiento) {

            $estado = match (true) {
                str_contains($respuesta->nombre, 'stock') => 'CERRADO_QUIEBRE',
                str_contains($respuesta->nombre, 'entregado') => 'CERRADO_ENTREGA',
                default => 'CERRADO'
            };

            $update['estado'] = $estado;
        }

        DB::table('tbl_requerimientos')
            ->where('id', $req->id)
            ->update($update);

        return $this->ok(true);
    }

    // =========================================================

    public function listarHoy(Request $request)
    {
        $request->validate([
            'sucursal_id' => 'required|integer'
        ]);

        $user = auth()->user();

        // 🔐 Validar sucursal
        $valida = DB::table('tbl_usuario_sucursal')
            ->where('usuario_id', $user->id)
            ->where('sucursal_id', $request->sucursal_id)
            ->exists();

        if (!$valida) {
            return $this->fail('Sucursal no válida', 403);
        }

        // ⏱️ Cierre automático
        DB::table('tbl_requerimientos')
            ->where('activo', true)
            //->whereNull('motivo_cierre')
            ->where('fecha_cierre', '<', now())
            ->update([
                'activo' => false,
                'estado' => 'CERRADO',
                'motivo_cierre' => DB::raw("
            CASE 
                WHEN ultima_respuesta_at IS NULL THEN 'SIN_RESPUESTA'
                ELSE 'EXPIRADO'
            END
        ")
            ]);

        // 🧠 Base query
        $query = DB::table('tbl_requerimientos as r')
            ->join('tbl_articulos as a', 'a.id', '=', 'r.articulo_id')
            ->join('tbl_sucursales as s', 's.id', '=', 'r.sucursal_id')
            ->whereDate('r.created_at', now())
            ->where('r.sucursal_id', $request->sucursal_id);
        if ($user->hasPermission('requerimientos', 'crear')) {
            $query->where('r.usuario_crea_id', $user->id);
        }

        // Runner → ve todos (no filtramos)

        $data = $query
            ->orderByDesc('r.created_at')
            ->get([
                'r.id',
                'r.estado',
                'r.activo',
                'r.created_at',
                'r.ultima_respuesta_at',
                "r.motivo_cierre",
                "r.fecha_cierre",
                'r.usuario_crea_id',
                'r.usuario_gestor_id',
                DB::raw('a.numero_articulo as idProducto'),
                'a.descripcion',
                's.tiempo_espera',
                's.tiempo_respuesta',
                's.tiempo_maximo'
            ]);

        return $this->ok($data);
    }
    // =========================================================

    public function tomarRequerimiento(Request $request)
    {
        $request->validate([
            'requerimiento_id' => 'required|exists:tbl_requerimientos,id'
        ]);

        $user = auth()->user();
        $now = now();

        $req = DB::table('tbl_requerimientos')
            ->where('id', $request->requerimiento_id)
            ->lockForUpdate()
            ->first();

        if (!$req || $req->estado !== 'ACTIVO') {
            return $this->fail('Requerimiento no disponible', 400);
        }

        DB::table('tbl_requerimientos')
            ->where('id', $req->id)
            ->update([
                'estado' => 'EN_PROCESO',
                'usuario_gestiona_id' => $user->id,
                'updated_at' => $now
            ]);

        return $this->ok(true);
    }

    // =========================================================

    public function buscarArticulo(Request $request)
    {
        $request->validate([
            'numero_articulo' => 'required|string|max:50'
        ]);

        $reqs = DB::table('tbl_articulos as r')
            ->leftJoin('tbl_secciones as s', 's.id', '=', 'r.seccion_id')
            ->where('r.numero_articulo', $request->numero_articulo)
            ->get([
                'r.id',
                'r.descripcion',
                'r.seccion_id',
                's.nombre as seccion'
            ]);

        return $this->ok($reqs);
    }

    // =========================================================
    public function detalle(Request $request)
    {
        $request->validate([
            'requerimiento_id' => 'required|exists:tbl_requerimientos,id'
        ]);

        $data = DB::table('tbl_requerimiento_historial as h')
            ->leftJoin('tbl_respuestas as r', 'r.id', '=', 'h.respuesta_id')
            ->where('h.requerimiento_id', $request->requerimiento_id)
            ->orderBy('h.created_at', 'asc')
            ->get([
                'h.id',
                'h.usuario_id',
                'h.comentario',
                'h.foto_url',
                'h.created_at',
                'r.nombre as respuesta_nombre'
            ]);

        return $this->ok($data);
    }

    public function listar()
    {
        return DB::table('tbl_secciones')
            ->select('id', 'nombre')
            ->orderBy('nombre')
            ->get();
    }
}

/*
|--------------------------------------------------------------------------
| TIPOS DE CIERRE DE REQUERIMIENTOS
|--------------------------------------------------------------------------
|
| El requerimiento puede cerrarse por 3 motivos principales:
|
| 1. CIERRE POR RESPUESTA (PRIORIDAD ALTA)
| --------------------------------------------------
| Ocurre cuando el runner selecciona una respuesta
| que tiene cierra_requerimiento = true.
|
| Ejemplos:
| - Producto entregado           â†’ CERRADO_ENTREGA
| - Producto sin stock           â†’ CERRADO_QUIEBRE
| - Imposible acceder            â†’ CERRADO_NO_ACCESIBLE
| - CÃ¡mara obstruida             â†’ CERRADO_NO_ACCESIBLE
|
| Nota:
| Este cierre es inmediato y tiene prioridad sobre los tiempos.
|
|--------------------------------------------------------------------------
|
| 2. CIERRE POR INACTIVIDAD (SIN RESPUESTA)
| --------------------------------------------------
| Ocurre cuando NO existe ninguna respuesta desde la creaciÃ³n.
|
| CondiciÃ³n:
| - ultima_respuesta_at IS NULL
| - now() >= created_at + tiempo_espera
|
| Estado:
| - CERRADO_SIN_RESPUESTA
|
|--------------------------------------------------------------------------
|
| 3. CIERRE POR TIEMPO EXPIRADO
| --------------------------------------------------
| Ocurre cuando hubo respuestas, pero el tiempo se agotÃ³.
|
| CondiciÃ³n:
| - now() >= deadline
|
| Donde:
|
| deadline = MIN(
|   created_at + tiempo_maximo,
|   ultima_respuesta_at + tiempo_respuesta
| )
|
| Regla clave:
| - El tiempo se puede extender con respuestas
| - PERO nunca puede superar el tiempo_maximo
|
| Estado:
| - CERRADO_EXPIRADO
|
|--------------------------------------------------------------------------
|
| PRIORIDAD DE EVALUACIÃ“N
|--------------------------------------------------------------------------
|
| 1. Cierre por respuesta (inmediato)
| 2. Cierre por inactividad
| 3. Cierre por expiraciÃ³n
|
|--------------------------------------------------------------------------
|
| NOTAS IMPORTANTES
|--------------------------------------------------------------------------
|
| - Cada respuesta actualiza: ultima_respuesta_at
| - El requerimiento inicia sin respuestas
| - El sistema debe evaluar estos cierres en cada consulta
|   o mediante un proceso automÃ¡tico (cron / job)
|
|--------------------------------------------------------------------------
*/
