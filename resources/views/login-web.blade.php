<!DOCTYPE html>
<html>

<head>
    <title>Login Web - GesStock</title>
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/css/index.css">
    <script src="https://cdn.jsdelivr.net/npm/simple-keyboard@latest/build/index.js"></script>
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

        .simple-keyboard {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            width: 90%;
            max-width: 700px;
            z-index: 9999;
        }

        .login-card {
            background: #ffffff;
            padding: 40px;
            width: 320px;
            border-radius: 12px;
            box-shadow: 0 20px 40px rgba(0, 0, 0, 0.25);

            display: flex;
            flex-direction: column;
            align-items: center;
            /* 👈 centra horizontalmente */
        }

        .login-card h2 {
            margin-bottom: 25px;
            color: #2c3e50;
        }

        .login-card input {
            width: 85%;
            /* 👈 no 100%, así se ve centrado */
            padding: 12px;
            margin-bottom: 15px;
            border-radius: 6px;
            border: 1px solid #ddd;
            font-size: 14px;
        }

        .login-card input:focus {
            border-color: #3498db;
            outline: none;
            box-shadow: 0 0 5px rgba(52, 152, 219, 0.4);
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
            background: white;
            padding: 30px;
            width: 350px;
            border-radius: 8px;
            box-shadow: 0 0 10px rgba(0, 0, 0, .1);
        }

        input {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
        }

        button {
            width: 100%;
            padding: 10px;
            background: #2c3e50;
            color: white;
            border: none;
            cursor: pointer;
        }

        .error {
            color: red;
            margin-bottom: 10px;
        }

        .keyboard {
            position: fixed;
            bottom: 10px;
            left: 50%;
            transform: translateX(-50%);
            background: #2c3e50;
            padding: 10px;
            border-radius: 10px;
            display: flex;
            flex-wrap: wrap;
            width: 90%;
            max-width: 600px;
            z-index: 9999;
        }

        .keyboard button {
            width: 10%;
            margin: 3px;
            padding: 10px;
            border: none;
            border-radius: 5px;
            background: #ecf0f1;
            cursor: pointer;
        }

        .keyboard button:active {
            background: #bdc3c7;
        }

        .hidden {
            display: none;
        }

        .keyboard-icon {
            position: absolute;
            right: 10px;
            top: 50%;
            transform: translateY(-50%);
            cursor: pointer;
            z-index: 10;
            /* 🔥 esto es lo importante */
            pointer-events: auto;
        }

        .input-container {
            position: relative;
            width: 85%;
            margin-bottom: 15px
        }

        .input-container input {

            width: 100%;
            padding-right: 40px;

            /* espacio para el icono */
            position: relative;
            z-index: 1;

        }

        .keyboard-icon {
            position: absolute;
            right: 10px;
            top: 35%;
            cursor: pointer;
        }
    </style>
</head>

<body>


    <div class="login-card">
        <h2>Login</h2>
        <div class="error" id="error"></div>
        <div class="input-container">
            <input type="text" id="run" placeholder="Usuario" onclick="openKeyboard('run')">
            <span class="keyboard-icon" onclick="openKeyboard('run')">⌨</span>
        </div>

        <div class="input-container">
            <input type="password" id="password" placeholder="Contraseña" onclick="openKeyboard('password')">
            <span class="keyboard-icon" onclick="openKeyboard('password')">⌨</span>
        </div>

        <button class="btn-login" onclick="login()">Ingresar</button>
    </div>

    <div id="keyboard" class="simple-keyboard hidden"></div>

    <script>
        let keyboard = null;
        let currentInput = null;
        let currentLayout = "default";
        let isCaps = false;

        function createKeyboard() {
            keyboard = new window.SimpleKeyboard.default({
                onChange: input => {
                    if (currentInput) {
                        currentInput.value = input;
                        currentInput.focus();
                    }
                },
                onKeyPress: button => handleKeyPress(button),
                layoutName: currentLayout,
                layout: {
                    default: [
                        "1 2 3 4 5 6 7 8 9 0 ?",
                        "q w e r t y u i o p",
                        "a s d f g h j k l ñ",
                        "{shift} z x c v b n m {bksp}",
                        "{numbers} {space} {close}"
                    ],
                    shift: [
                        "! @ # $ % & * ( )",
                        "Q W E R T Y U I O P",
                        "A S D F G H J K L Ñ",
                        "{shift} Z X C V B N M {bksp}",
                        "{numbers} {space} {close}"

                        
                    ],
                    numbers: [
                        "1 2 3 *",
                        "4 5 6 /",
                        "7 8 9 +", 
                        "{abc} . 0 -"

                    ]
                },
                display: {
                    "{shift}": "Mayús",
                    "{bksp}": "←",
                    "{space}": "Espacio",
                    "{close}": "Cerrar",
                    "{numbers}": "123",
                    "{abc}": "ABC"
                },
                theme: "hg-theme-default hg-layout-default myTheme"
            });
        }

        function openKeyboard(inputId) {
            currentInput = document.getElementById(inputId);
            const kb = document.getElementById("keyboard");

            if (!keyboard) {
                createKeyboard();
            }

            kb.classList.remove("hidden");
            keyboard.setInput(currentInput.value || "");
        }

        function closeKeyboard() {
            const kb = document.getElementById("keyboard");
            kb.classList.add("hidden");
        }

        function handleKeyPress(button) {
            if (button === "{shift}") {
                isCaps = !isCaps;
                currentLayout = isCaps ? "shift" : "default";
                keyboard.setOptions({ layoutName: currentLayout });
                return;
            }

            if (button === "{numbers}") {
                currentLayout = "numbers";
                keyboard.setOptions({ layoutName: currentLayout });
                return;
            }

            if (button === "{abc}") {
                currentLayout = isCaps ? "shift" : "default";
                keyboard.setOptions({ layoutName: currentLayout });
                return;
            }

            if (button === "{close}") {
                closeKeyboard();
                return;
            }
        }
        document.addEventListener("click", function (e) {
            const kb = document.getElementById("keyboard");
            const isKeyboard = e.target.closest(".simple-keyboard");
            const isInput = e.target.closest(".input-container");

            if (!isKeyboard && !isInput && kb && !kb.classList.contains("hidden")) {
                kb.classList.add("hidden");
            }
        });

        function renderKeyboard() {
            const kb = document.getElementById('keyboard');
            if (!kb || !currentInput) return;

            const keys = "1234567890qwertyuiopasdfghjklzxcvbnm".split('');
            kb.innerHTML = '';

            keys.forEach(k => {
                const btn = document.createElement('button');
                btn.type = 'button';
                btn.innerText = k;
                btn.onclick = () => {
                    currentInput.value += k;
                    currentInput.focus();
                };
                kb.appendChild(btn);
            });

            const space = document.createElement('button');
            space.type = 'button';
            space.innerText = 'Espacio';
            space.style.width = '30%';
            space.onclick = () => {
                currentInput.value += ' ';
                currentInput.focus();
            };
            kb.appendChild(space);

            const del = document.createElement('button');
            del.type = 'button';
            del.innerText = '←';
            del.style.width = '20%';
            del.onclick = () => {
                currentInput.value = currentInput.value.slice(0, -1);
                currentInput.focus();
            };
            kb.appendChild(del);

            const close = document.createElement('button');
            close.type = 'button';
            close.innerText = 'Cerrar';
            close.style.width = '20%';
            close.onclick = () => kb.classList.add('hidden');
            kb.appendChild(close);
        }


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