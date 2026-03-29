<!DOCTYPE html>
<script> 
   
let usuario = null;
let sucursales = null;

try {
    usuario = JSON.parse(sessionStorage.getItem('usuario'));
    sucursales = JSON.parse(sessionStorage.getItem('sucursales'));
} catch (e) {
    sessionStorage.clear();
}

if (!usuario || !sucursales) {
    alert();
    window.location = '/login';
}
</script>
<html>
<head>
    <title>Seleccionar Sucursal</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">

    <style>
body {
    font-family: Arial;
    background:#f4f6f9;
    display:flex;
    flex-direction:column;
    justify-content:space-between;
    align-items:center;
    min-height:100vh;
    margin:0;
}

        .card {
            background:white;
            padding:30px;
            width:350px;
            border-radius:8px;
            box-shadow:0 0 10px rgba(0,0,0,.1);
            text-align:center;
            margin-top:40px;
        }        
 .card-apk {
    position:fixed;
    bottom:20px;
    left:50%;
    transform:translateX(-50%);
    width:350px;
}

        button {
            width:100%;
            padding:10px;
            margin-bottom:10px;
            background:#2c3e50;
            color:white;
            border:none;
            cursor:pointer;
        }

        h3 {
            margin-top:5px;
            margin-bottom:20px;
            font-weight:normal;
            color:#555;
        }
    </style>
</head>
<body>

<div class="card">

    <h2 id="saludo"></h2>
    <h3>Seleccione una sucursal</h3>

    <div id="lista-sucursales"></div>

</div>
<div class="card-apk">
    <a href="https://api.gesstock.cl/app">Descargar APK</a>
</div>

<script>

document.getElementById('saludo').innerText =
    "Hola " + usuario.nombre_completo;

const lista = document.getElementById('lista-sucursales');

sucursales.forEach(sucursal => {

    const button = document.createElement('button');
    button.innerText = sucursal.nombre;

    button.onclick = function() {
        seleccionar(sucursal.id, sucursal.nombre);
    };

    lista.appendChild(button);

});

function seleccionar(id, nombre) {
    sessionStorage.setItem('sucursal_id', id);
    sessionStorage.setItem('sucursal_nombre', nombre);
    window.location = '/panel-turnos';
}

</script>

</body>
</html>