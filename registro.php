<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Academia JB - Registro</title>
    <style>
         body { 
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #AEC6CF;
            display: flex; 
            justify-content: center; 
            align-items: center; 
            height: 100vh; 
            margin: 0; 
        }
        
        /* Esta es la caja blanca que protege tu formulario */
        .form-container { 
            background: #ffffff; 
            padding: 35px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(26, 66, 110, 0.25); 
            width: 380px; 
            box-sizing: border-box;
        }
        h2 { 
            text-align: center; 
            color: #1a426e; 
            margin: 0 0 20px 0; 
            font-weight: bold; 
        }
        label { 
            display: block; 
            margin-top: 14px; 
            color: #1a3557; 
            font-size: 14px; 
            font-weight: bold; 
        }
        input[type="text"], input[type="email"], input[type="password"], select { 
            width: 100%; 
            padding: 10px; 
            margin-top: 5px; 
            border: 1px solid #1a426e; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 15px;
        }
        .checkbox-group { 
            margin-top: 15px; 
            background: #f8fafc; 
            padding: 12px; 
            border-radius: 8px; 
            border: 1px solid #cbd5e1;
        }
        .checkbox-item {
            display: flex;
            align-items: center;
            margin-bottom: 8px;
        }
        .checkbox-item:last-child {
            margin-bottom: 0;
        }
        .checkbox-item input {
            margin: 0;
            cursor: pointer;
        }
        .checkbox-item label { 
            display: inline; 
            font-weight: bold; 
            margin: 0 0 0 8px; 
            font-size: 14px; 
            color: #334155; 
            cursor: pointer;
        }
        button { 
            width: 100%; 
            background: #2196F3; 
            color: white; 
            border: none; 
            padding: 14px; 
            margin-top: 20px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.3);
        }
        button:hover { 
            background: #1976D2; 
        }
        .enlaces-retorno {
            text-align: center;
            margin-top: 15px;
        }
        .enlaces-retorno a { 
            display: block; 
            color: #2196F3; 
            text-decoration: none; 
            font-size: 14px; 
            font-weight: bold;
            margin-top: 8px;
        }
        .enlaces-retorno a:hover { 
            text-decoration: underline; 
        }
    </style>
</head>
<body>
    

<!-- CONTENEDOR BLANCO PROTECTOR -->
<div class="form-container">
    <h2>Registro Academia JB</h2>
    <div style="display:flex; justify-content:center; gap:8px; margin-bottom:20px;">
        <div style="background:#1a426e; color:white; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;">1️⃣ Datos</div>
        <span style="color:#94a3b8; line-height:30px;">→</span>
        <div style="background:#e2e8f0; color:#94a3b8; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;">2️⃣ Preferencias</div>
        <span style="color:#94a3b8; line-height:30px;">→</span>
        <div style="background:#e2e8f0; color:#94a3b8; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;">3️⃣ Listo</div>
    </div>
    <p style="text-align:center; font-size:14px; color:#334155; margin-top:-10px; margin-bottom:15px;">Crea tu cuenta para empezar a aprender.</p>
    <form action="procesar_registro.php" method="POST">
        <label>Nombre Completo:</label>
        <input type="text" name="nombre" required>

        <label>Cédula / Documento de Identidad:</label>
        <input type="text" name="cedula" placeholder="Ej: V-12345678" required>

        <label>Correo Electrónico (solo @gmail.com):</label>
        <input type="email" name="email" pattern="[a-zA-Z0-9._%+\-]+@gmail\.com" title="Solo se permiten correos de Gmail (@gmail.com)" required>

        <label>Contraseña (mínimo 8 caracteres):</label>
        <input type="password" name="password" id="password"
               pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*[0-9])(?=.*[^A-Za-z0-9\s]).{8,}"
               title="La contraseña debe tener al menos 8 caracteres e incluir: mayúscula, minúscula, número y carácter especial." required>
        <div id="passRequisitos" style="display:none; margin-top:8px; font-size:13px; color:#334155; background:#f8fafc; border:1px solid #cbd5e1; border-radius:6px; padding:10px; line-height:1.9;">
            <div id="reqLong"><span style="color:#94a3b8;">⬜</span> Mínimo 8 caracteres</div>
            <div id="reqMay"><span style="color:#94a3b8;">⬜</span> Al menos una mayúscula (A-Z)</div>
            <div id="reqMin"><span style="color:#94a3b8;">⬜</span> Al menos una minúscula (a-z)</div>
            <div id="reqNum"><span style="color:#94a3b8;">⬜</span> Al menos un número (0-9)</div>
            <div id="reqEsp"><span style="color:#94a3b8;">⬜</span> Al menos un carácter especial (!@#$…)</div>
        </div>

        <label>Rol:</label>
        <select name="rol">
            <option value="estudiante">Estudiante</option>
        </select>

        <label style="margin-top:18px;">Tipo de Usuario:</label>
        <div style="display:flex; gap:15px; margin-top:5px;">
            <label style="font-weight:normal; font-size:14px; display:flex; align-items:center; gap:5px; cursor:pointer;">
                <input type="radio" name="tipo_tea" value="0" checked onchange="toggleTEA(false)"> Usuario General
            </label>
            <label style="font-weight:normal; font-size:14px; display:flex; align-items:center; gap:5px; cursor:pointer;">
                <input type="radio" name="tipo_tea" value="1" onchange="toggleTEA(true)"> Persona con TEA
            </label>
        </div>

        <!-- Herramientas de Personalización TEA (solo para tipo TEA) -->
        <div class="checkbox-group" id="teaPrefs">
            <p style="margin: 0 0 10px 0; font-weight: bold; color: #1a426e; font-size: 14px;">Preferencias de Accesibilidad (TEA):</p>
            
            <div class="checkbox-item">
                <input type="checkbox" name="fuente_grande" id="fuente_grande" value="1"> 
                <label for="fuente_grande">Letra Grande</label>
            </div>
            
            <div class="checkbox-item">
                <input type="checkbox" name="pictogramas" id="pictogramas" value="1" checked> 
                <label for="pictogramas">Usar Pictogramas</label>
            </div>
        </div>

        <button type="submit">Crear Cuenta</button>
    </form>
    
    <div class="enlaces-retorno">
        <a href="login.php">¿Ya tienes cuenta? Inicia sesión aquí</a>
        <a href="inicio_publico.php" style="color: #444;">⬅️ Volver a la página principal</a>
    </div>
</div>

<script>
function toggleTEA(esTEA) {
    document.getElementById('teaPrefs').style.display = esTEA ? 'block' : 'none';
}
document.getElementById('teaPrefs').style.display = 'none';

var inputPass = document.getElementById('password');
var textosReq = {
    reqLong: 'Mínimo 8 caracteres',
    reqMay: 'Al menos una mayúscula (A-Z)',
    reqMin: 'Al menos una minúscula (a-z)',
    reqNum: 'Al menos un número (0-9)',
    reqEsp: 'Al menos un carácter especial (!@#$…)'
};
function revisarRequisitos() {
    var v = inputPass.value;
    var estados = {
        reqLong: v.length >= 8,
        reqMay: /[A-Z]/.test(v),
        reqMin: /[a-z]/.test(v),
        reqNum: /[0-9]/.test(v),
        reqEsp: /[^A-Za-z0-9\s]/.test(v)
    };
    for (var id in estados) {
        var el = document.getElementById(id);
        el.style.color = estados[id] ? '#15803d' : '#94a3b8';
        el.innerHTML = (estados[id] ? '✅' : '⬜') + ' ' + textosReq[id];
    }
}
inputPass.addEventListener('input', revisarRequisitos);
inputPass.addEventListener('focus', function(){
    document.getElementById('passRequisitos').style.display = 'block';
    revisarRequisitos();
});
inputPass.addEventListener('blur', function(){
    document.getElementById('passRequisitos').style.display = 'none';
});
</script>

<?php include 'asistente.php'; ?>
</body>
</html>