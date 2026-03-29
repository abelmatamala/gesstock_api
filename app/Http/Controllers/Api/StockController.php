public function store(Request $request)
{
    $request->validate([
        'numero_articulo' => 'required|string|max:50',
        'descripcion' => 'nullable|string|max:255',
        'sucursal_id' => 'required|integer',
        'imagen' => 'required|image|max:2048'
    ]);

    $userId = auth()->id();
    $now = now();

    // 🔹 1. ARTÍCULO (crear o buscar)
    $articulo = DB::table('tbl_articulos')
        ->where('numero_articulo', $request->numero_articulo)
        ->first();

    if (!$articulo) {
        $articuloId = DB::table('tbl_articulos')->insertGetId([
            'numero_articulo' => $request->numero_articulo,
            'descripcion' => $request->descripcion,
            'created_at' => $now
        ]);
    } else {
        $articuloId = $articulo->id;
    }

    // 🔹 2. CREAR REQUERIMIENTO
    $reqId = DB::table('tbl_requerimientos')->insertGetId([
        'articulo_id' => $articuloId,
        'sucursal_id' => $request->sucursal_id,
        'usuario_crea_id' => $userId,
        'estado' => 'ACTIVO',
        'created_at' => $now
    ]);

    // 🔹 3. GENERAR NOMBRE IMAGEN
    $filename = $now->format('Ymd_His') . '_' .
                $request->numero_articulo . '_' .
                $userId . '.' .
                $request->file('imagen')->extension();

    // 🔹 4. GUARDAR IMAGEN
    $path = $request->file('imagen')->storeAs(
        'requerimientos',
        $filename,
        'public'
    );

    // 🔹 5. HISTORIAL (primer registro)
    DB::table('tbl_requerimiento_historial')->insert([
        'requerimiento_id' => $reqId,
        'usuario_id' => $userId,
        'comentario' => $request->descripcion,
        'foto_url' => $path,
        'created_at' => $now
    ]);

    return response()->json([
        'success' => true,
        'requerimiento_id' => $reqId
    ]);
}