<script>
const token = sessionStorage.getItem("token");
const usuario = sessionStorage.getItem("usuario");
const sucursal = sessionStorage.getItem("sucursal_id");

if (!token || !usuario || !sucursal) {
    window.location.replace("/login");
}

</script>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel Turnos</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    
    <?php echo app('Illuminate\Foundation\Vite')(['resources/css/app.css', 'resources/js/app.js']); ?>

    
    <link rel="stylesheet" href="https://cdn.datatables.net/1.13.6/css/jquery.dataTables.min.css">
</head>

<body>

<div class="left">
    <button onclick="toggleDarkMode()" style="margin-bottom:15px;">
    🌙 Modo Nocturno
    </button>
     <!-- h3>Código QR Activo</!--h3 -->
    <h3 id="nombreSucursal"></h3>

    <canvas id="qrCanvas"></canvas>

    <div id="totalEnEspera" class="contador-panel">
        Total en espera: 0
    </div>

    <div class="imagen-container">
        <span id="mensajeImagen">Sin imagen cargada</span>
        <img id="imagenPreview" src="">
    </div>

    <input type="file" id="inputImagen" accept="image/*" hidden>

    <button class="btn" onclick="document.getElementById('inputImagen').click()">
        Cargar Imagen
    </button>
</div>

<div class="right">
    <h2 class="titulo-lista">Lista de espera de turnos</h2>
    <div class="tabla-container">
        <table id="tablaTurnos" class="display">
            <thead>
                <tr>
                    <th>N°</th>
                    <th>Usuario</th>
                    <th>Estado</th>
                    <th>Hora</th>
                    <th>Asignado</th>
                </tr>
            </thead>
            <tbody></tbody>
        </table>
    </div>
    <div class="acciones-panel">

    <button class="btn-fifo" onclick="asignarSiguiente()">
        ASIGNAR SIGUIENTE
    </button>

    <button class="btn-todos" onclick="asignarTodos()">
        ASIGNAR TODOS
    </button>

</div>
</div>


<script src="https://code.jquery.com/jquery-3.7.0.min.js"></script>
<script src="https://cdn.datatables.net/1.13.6/js/jquery.dataTables.min.js"></script>
<script src="https://cdn.jsdelivr.net/npm/qrcode/build/qrcode.min.js"></script>

<script>

    const sucursalId = sessionStorage.getItem('sucursal_id');
    const nombreSucursal = sessionStorage.getItem('sucursal_nombre');
    document.getElementById('inputImagen').addEventListener('change', function (event) {

    const file = event.target.files[0];
    if (!file) return;

    const reader = new FileReader();

    reader.onload = function (e) {
        const img = document.getElementById('imagenPreview');
        img.src = e.target.result;
        img.style.display = 'block';

        document.getElementById('mensajeImagen').style.display = 'none';
    };

    reader.readAsDataURL(file);
});
    if (!sucursalId) {
    window.location.href = '/login';
}

    let tabla = null;

    /* ===========================
   🔳 CARGAR QR
=========================== */
    async function cargarQR() {

    const response = await window.apiFetch('/api/supervisor/qr-activo/' + sucursalId);
    if (!response || !response.ok) return;

    const data = await response.json();

    const canvas = document.getElementById('qrCanvas');
    canvas.getContext('2d').clearRect(0, 0, canvas.width, canvas.height);

    QRCode.toCanvas(canvas, data.qr_token, {
    width: 200,   // 👈 tamaño en px
    margin: 2
});
}

    /* ===========================
   📋 CARGAR LISTA
=========================== */
    async function cargarLista() {

        if (tabla) {
            tabla.ajax.reload(null, false);
            return;
        }
        $.fn.dataTable.ext.errMode = 'none';
        tabla = $('#tablaTurnos').DataTable({
            ajax: { 
                url: '/api/web/lista-turnos/' + sucursalId,
                                dataSrc: function(data) {

                    const totalEnEspera = data.filter(t => t.estado === 'en_espera').length;

                    const contador = document.getElementById('totalEnEspera');
                    contador.innerText = `Total en espera: ${totalEnEspera}`;

                    contador.classList.remove('contador-verde','contador-amarillo','contador-rojo');

                    if (totalEnEspera === 0) contador.classList.add('contador-verde');
                    else if (totalEnEspera <= 10) contador.classList.add('contador-amarillo');
                    else contador.classList.add('contador-rojo');

                    /* control del botón */
                    const btnTodos = document.querySelector('.btn-todos');

                    if (btnTodos) {
                        btnTodos.disabled = (totalEnEspera === 0);
                    }
                    return data;
                },

                beforeSend: function (xhr) {
            const token = sessionStorage.getItem('token');
            if (token) {
                xhr.setRequestHeader('Authorization', 'Bearer ' + token);
            }
            }
            },
    columns: [
        { data: 'numero_turno',
          render: function (data) {
              return data ?? '-';
          }
        },
        { data: 'nombre_usuario' },
        { data: 'estado' },
        { 
            data: 'fecha_ingreso',
            render: function (data) {
                return new Date(data).toLocaleTimeString('es-CL', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
            }
        },
        {
            data: 'fecha_asignado',
            render: function (data) {
                if (!data) return '-';
                return new Date(data).toLocaleTimeString('es-CL', {
                    hour: '2-digit',
                    minute: '2-digit',
                    hour12: false
                });
            }
        }
    ],
    createdRow: function (row, data) {

        if (data.estado === 'en_espera') {
            $(row).addClass('fila-espera');
        }

        if (data.estado === 'asignado') {
            $(row).addClass('fila-asignado');
        }

        if (data.estado === 'atendido') {
            $(row).addClass('fila-atendido');
        }

        if (data.estado === 'fuera_de_rango') {
            $(row).addClass('fila-fuera');
        }
    },
    order: [[3, "desc"]],
    scrollY: "60vh",
    scrollCollapse: true,
    paging: false,
    info: false,
    searching: true
});
}

    /* ===========================
   🔁 ASIGNAR FIFO
=========================== */
    window.asignarSiguiente = async function () {
    const response = await window.apiFetch('/api/web/asignar-turno', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ sucursal_id: sucursalId })
    });

    if (!response || !response.ok) return;
    cargarLista();
    cargarQR();
}

    /* ===========================
   🚀 INICIALIZACIÓN SEGURA
=========================== */
    document.addEventListener("DOMContentLoaded", function () {

    if (nombreSucursal) {
        document.getElementById('nombreSucursal').innerText = nombreSucursal;
    }

    cargarQR();
    cargarLista();

    setInterval(() => {
        tabla.ajax.reload(null, false); // 👈 false mantiene orden y filtro
        cargarQR();
    }, 5000);

});
    
    function toggleDarkMode() {
    document.body.classList.toggle('dark-mode');
}
    
    window.asignarTodos = async function () {

    const confirmar = confirm(
        "Se asignarán todos los turnos en espera.\n\n¿Desea continuar?"
    );

    if (!confirmar) return;

    const response = await window.apiFetch('/api/web/asignar-todos', {
        method: 'POST',
        headers: { 'Content-Type':'application/json' },
        body: JSON.stringify({ sucursal_id: sucursalId })
    });

    if (!response || !response.ok) return;

    tabla.ajax.reload(null,false);
    cargarQR();
}
</script>

</body>
</html><?php /**PATH C:\laragon\www\gesstock_api\resources\views/panel-turnos.blade.php ENDPATH**/ ?>