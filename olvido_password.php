<?php
session_start();
include 'conexion.php';

$mensaje = '';
$tipo_alerta = '';
$mostrar_form = true;

// Paso 2: si ya verificó email+cedula, mostrar formulario de nueva contraseña
if (isset($_SESSION['reset_uid'])) {
    if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['restablecer'])) {
        $password = trim($_POST['password']);
        $confirmar = trim($_POST['confirmar']);
        if (strlen($password) < 8) {
            $mensaje = "La contraseña debe tener al menos 8 caracteres.";
            $tipo_alerta = "error";
        } elseif ($password !== $confirmar) {
            $mensaje = "Las contraseñas no coinciden.";
            $tipo_alerta = "error";
        } else {
            $uid = (int)$_SESSION['reset_uid'];
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $stmt = $conn->prepare("UPDATE usuarios SET password = ? WHERE id = ?");
            $stmt->bind_param("si", $hash, $uid);
            if ($stmt->execute()) {
                $mensaje = "✅ Contraseña actualizada correctamente. <a href='login.php' style='color:#1a426e;'>Inicia sesión aquí</a>";
                $tipo_alerta = "exito";
                $mostrar_form = false;
                unset($_SESSION['reset_uid']);
            } else {
                $mensaje = "❌ Error al actualizar. Intenta de nuevo.";
                $tipo_alerta = "error";
            }
            $stmt->close();
        }
    }
    if ($mostrar_form) {
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Nueva Contraseña - Academia JB</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #AEC6CF; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 15px 35px rgba(26,66,110,0.25); width: 380px; }
        h2 { text-align: center; color: #1a426e; margin-bottom: 25px; }
        label { display: block; margin-top: 15px; color: #1a3557; font-size: 14px; font-weight: bold; }
        input[type="password"] { width: 100%; padding: 12px; margin-top: 6px; border: 1px solid #1a426e; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input:focus { border-color: #2196F3; outline: none; }
        button { width: 100%; background: #2196F3; color: white; border: none; padding: 14px; margin-top: 25px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #1976D2; }
        .alerta { padding: 10px; border-radius: 6px; font-size: 13px; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .exito { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        a { display: block; text-align: center; margin-top: 20px; color: #2196F3; text-decoration: none; font-size: 14px; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>🔑 Nueva Contraseña</h2>
    <?php if ($mensaje): ?>
        <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Nueva Contraseña <span style="font-weight:normal; color:#555;">(mín. 8 caracteres)</span>:</label>
        <input type="password" name="password" minlength="8" required>
        <label>Confirmar Contraseña:</label>
        <input type="password" name="confirmar" minlength="8" required>
        <input type="hidden" name="restablecer" value="1">
        <button type="submit">Cambiar Contraseña</button>
    </form>
    <a href="login.php">Volver al inicio de sesión</a>
</div>
</body>
</html>
<?php
        exit();
    } else {
        // mensaje de éxito, mostrar solo mensaje
    }
}

// Paso 1: verificar email + cédula
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['verificar'])) {
    $email = trim($_POST['email']);
    $cedula = trim($_POST['cedula']);
    if (empty($email) || empty($cedula)) {
        $mensaje = "⚠️ Completa todos los campos.";
        $tipo_alerta = "error";
    } else {
        $stmt = $conn->prepare("SELECT id, nombre FROM usuarios WHERE email = ? AND cedula = ?");
        $stmt->bind_param("ss", $email, $cedula);
        $stmt->execute();
        $res = $stmt->get_result();
        if ($usuario = $res->fetch_assoc()) {
            $_SESSION['reset_uid'] = $usuario['id'];
            header("Location: olvido_password.php");
            exit();
        } else {
            $mensaje = "❌ No encontramos una cuenta con ese correo y cédula.";
            $tipo_alerta = "error";
        }
        $stmt->close();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Recuperar Contraseña - Academia JB</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 0; padding: 0; background-color: #AEC6CF; display: flex; justify-content: center; align-items: center; height: 100vh; }
        .container { background: #fff; padding: 40px; border-radius: 16px; box-shadow: 0 15px 35px rgba(26,66,110,0.25); width: 380px; }
        h2 { text-align: center; color: #1a426e; margin-bottom: 25px; }
        label { display: block; margin-top: 15px; color: #1a3557; font-size: 14px; font-weight: bold; }
        input[type="email"], input[type="text"] { width: 100%; padding: 12px; margin-top: 6px; border: 1px solid #1a426e; border-radius: 6px; box-sizing: border-box; font-size: 15px; }
        input:focus { border-color: #2196F3; outline: none; }
        button { width: 100%; background: #2196F3; color: white; border: none; padding: 14px; margin-top: 25px; border-radius: 6px; cursor: pointer; font-size: 16px; font-weight: bold; }
        button:hover { background: #1976D2; }
        .alerta { padding: 10px; border-radius: 6px; font-size: 13px; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .exito { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }
        a { display: block; text-align: center; margin-top: 20px; color: #2196F3; text-decoration: none; font-size: 14px; font-weight: bold; }
        a:hover { text-decoration: underline; }
    </style>
</head>
<body>
<div class="container">
    <h2>🔒 Recuperar Contraseña</h2>
    <p style="text-align:center; font-size:14px; color:#334155;">Ingresa tu correo y cédula para verificar tu identidad.</p>
    <?php if ($mensaje): ?>
        <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>
    <form method="POST">
        <label>Correo Electrónico:</label>
        <input type="email" name="email" required>
        <label>Cédula:</label>
        <input type="text" name="cedula" placeholder="Ej: V-12345678" required>
        <input type="hidden" name="verificar" value="1">
        <button type="submit">Verificar Identidad</button>
    </form>
    <a href="login.php">⬅️ Volver al inicio de sesión</a>
</div>
</body>
</html>
