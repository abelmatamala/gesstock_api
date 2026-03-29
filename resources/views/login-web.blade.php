<!DOCTYPE html>
<html>
<head>
    <title>Login Web - GesStock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <style>
        body {
            margin: 0;
            height: 100vh;
            display: flex;
            justify-content: center;
            align-items: center;
            background: linear-gradient(135deg, #2c3e50, #34495e);
            font-family: Arial, sans-serif;
        }
        .login-card {
            background: #ffffff;
            padding: 40px;
            width: 320px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.25);

            display: flex;
            flex-direction: column;
            align-items: center;   /* 👈 centra horizontalmente */
        }
        .login-card h2 {
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .login-card input {
            width: 85%;   /* 👈 no 100%, así se ve centrado */
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .login-card input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52,152,219,0.4);
        }

        .btn-login {
            width: 100%;
            padding: 12px;
            border: none;
            border-radius: 6px;
            background: #2c3e50;
            color: white;
            font-size: 15px;
            cursor: pointer;
            transition: all 0.2s ease;
        }

        /* Hover */
        .btn-login:hover {
            background: #1f2d3a;
        }

        /* Click animation */
        .btn-login:active {
            transform: scale(0.97);
            background: #16222c;
        }
        .card {
            background:white;
            padding:30px;
            width:350px;
            border-radius:8px;
            box-shadow:0 0 10px rgba(0,0,0,.1);
        }
        input {
            width:100%;
            padding:10px;
            margin-bottom:15px;
        }
        button {
            width:100%;
            padding:10px;
            background:#2c3e50;
            color:white;
            border:none;
            cursor:pointer;
        }
        .error {
            color:red;
            margin-bottom:10px;
        }
    </style>
</head>
<body>

    
<div class="login-card">
    <h2>Login</h2>
    <div class="error" id="error"></div>
    <input type="text" id="run" placeholder="Usuario">
    <input type="password" id="password" placeholder="Contraseña">
    
    <button class="btn-login" onclick="login()">Ingresar</button>
</div>

<script>
    
async function login() {

    const run = document.getElementById('run').value.trim();
    const password = document.getElementById('password').value.trim();
    const errorDiv = document.getElementById('error');
    const boton = document.querySelector('.btn-login');

    errorDiv.innerText = '';

    if (!run || !password) {
        errorDiv.innerText = 'Debe ingresar usuario y contraseña';
        return;
    }

    boton.disabled = true;
    boton.innerText = 'Validando...';

    try {

        const response = await fetch('/api/login', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({
                run: run,
                password: password,
                platform: 'web'
            })
        });

        const data = await response.json();

        if (!response.ok) {
            errorDiv.innerText = data.error ?? 'Credenciales incorrectas';
            boton.disabled = false;
            boton.innerText = 'Ingresar';
            return;
        }

        sessionStorage.setItem('token', data.token);
        sessionStorage.setItem('usuario', JSON.stringify(data.usuario));
        sessionStorage.setItem('sucursales', JSON.stringify(data.sucursales));
        sessionStorage.setItem('permissions', JSON.stringify(data.permissions));

        window.location.href = '/seleccionar-sucursal';

    } catch (error) {
        errorDiv.innerText = 'Error de conexión';
        boton.disabled = false;
        boton.innerText = 'Ingresar';
    }
}
</script>

</body>
</html>