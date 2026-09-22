<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Academia JB - Iniciar Sesión</title>
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
        }
        
        .login-container { 
            background: #ffffff; 
            padding: 40px; 
            border-radius: 16px; 
            box-shadow: 0 15px 35px rgba(26, 66, 110, 0.25); 
            width: 380px; 
        }
        h2 { text-align: center; color: #1a426e; margin-bottom: 25px; font-weight: bold; }
        label { display: block; margin-top: 15px; color: #1a3557; font-size: 14px; font-weight: bold; }
        input[type="email"], input[type="password"] { 
            width: 100%; 
            padding: 12px; 
            margin-top: 6px; 
            border: 1px solid #1a426e; 
            border-radius: 6px; 
            box-sizing: border-box; 
            font-size: 15px; 
        }
        input:focus {
            border-color: #2196F3;
            outline: none;
        }
        /* Estilo para el cuadro de alerta integrado */
        .alerta-interna {
            background-color: #FEE2E2;
            color: #991B1B;
            border: 1px solid #FCA5A5;
            padding: 10px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            margin-bottom: 15px;
            text-align: center;
        }
        button { 
            width: 100%; 
            background: #2196F3; 
            color: white; 
            border: none; 
            padding: 14px; 
            margin-top: 25px; 
            border-radius: 6px; 
            cursor: pointer; 
            font-size: 16px; 
            font-weight: bold; 
            box-shadow: 0 4px 10px rgba(33, 150, 243, 0.3); 
        }
        button:hover { background: #1976D2; }
        a { display: block; text-align: center; margin-top: 20px; color: #2196F3; text-decoration: none; font-size: 14px; font-weight: bold; }
        a:hover { text-decoration: underline; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="msj_jb.js?v=7"></script>
</head>
<body>

<div class="login-container">
    <h2>Academia JB</h2>

    <div style="display:flex; justify-content:center; gap:8px; margin-bottom:20px;">
        <div style="background:#1a426e; color:white; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;">1️⃣ Datos</div>
        <span style="color:#94a3b8; line-height:30px;">→</span>
        <div style="background:#e2e8f0; color:#94a3b8; padding:6px 14px; border-radius:20px; font-size:13px; font-weight:bold;">2️⃣ Ingresar</div>
    </div>
    <p style="text-align:center; font-size:14px; color:#334155; margin-top:-10px; margin-bottom:15px;">Ingresa tu correo y contraseña para entrar.</p>

    <!-- INYECCIÓN DEL BLOQUE DE ERROR DINÁMICO -->
    <?php
    if (isset($_GET['error'])) {
        if ($_GET['error'] == 'clave_incorrecta') {
            $msg_jb = '⚠️ Contraseña incorrecta. Inténtalo de nuevo.';
        } elseif ($_GET['error'] == 'no_usuario') {
            $msg_jb = '❌ El correo electrónico no está registrado.';
        }
        if (isset($msg_jb)) {
            echo '<script>document.addEventListener("DOMContentLoaded", function(){ msjJb(' . json_encode($msg_jb, JSON_UNESCAPED_UNICODE) . ', "error"); });</script>';
        }
    }
    ?>

    <form action="procesar_login.php" method="POST">
        <label>Correo Electrónico:</label>
        <input type="email" name="email" required>

        <label>Contraseña:</label>
        <!-- CORREGIDO: Cambiado name="password" a name="clave" para emparejar con lo que recibe procesar_login.php -->
        <input type="password" name="clave" required>

        <button type="submit">Ingresar</button>
    </form>
    
    <a href="registro.php">¿No tienes cuenta? Regístrate aquí</a>
    <a href="olvido_password.php" style="margin-top: 8px;">🔒 ¿Olvidaste tu contraseña?</a>
    <a href="inicio_publico.php" style="color: #444; margin-top: 10px;">⬅️ Volver a la página principal</a>
</div>

<?php include 'asistente.php'; ?>
</body>
</html>