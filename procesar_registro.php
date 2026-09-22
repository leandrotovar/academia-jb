<?php
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $nombre = $_POST['nombre'];
    $cedula = trim($_POST['cedula']);
    $email = $_POST['email'];
    $password_raw = $_POST['password'];
    $tipo_tea = isset($_POST['tipo_tea']) ? intval($_POST['tipo_tea']) : 0;

    // Validar dominio Gmail
    if (!preg_match('/^[a-zA-Z0-9._%+\-]+@gmail\.com$/', $email)) {
        echo "<script>alert('Solo se permiten correos electrónicos de Gmail (@gmail.com).'); window.history.back();</script>";
        exit();
    }

    // Validar requisitos de contraseña (mín. 8: mayúscula, minúscula, número y especial)
    if (strlen($password_raw) < 8 ||
        !preg_match('/[A-Z]/', $password_raw) ||
        !preg_match('/[a-z]/', $password_raw) ||
        !preg_match('/[0-9]/', $password_raw) ||
        !preg_match('/[^A-Za-z0-9\s]/', $password_raw)) {
        echo "<script>alert('La contraseña debe tener al menos 8 caracteres e incluir: mayúscula, minúscula, número y carácter especial.'); window.history.back();</script>";
        exit();
    }

    // Encriptamos la contraseña
    $password = password_hash($password_raw, PASSWORD_BCRYPT);

    // Capturar checkboxes TEA (solo si tipo_tea es 1)
    $modo_oscuro = ($tipo_tea == 1 && isset($_POST['modo_oscuro'])) ? 1 : 0;
    $fuente_grande = ($tipo_tea == 1 && isset($_POST['fuente_grande'])) ? 1 : 0;
    $pictogramas = ($tipo_tea == 1 && isset($_POST['pictogramas'])) ? 1 : 0;
    $temporizador = ($tipo_tea == 1 && isset($_POST['temporizador_visual'])) ? 1 : 0;

    // Verificar si el correo ya existe
    $check_email = "SELECT email FROM usuarios WHERE email = '$email'";
    $resultado = $conn->query($check_email);

    if ($resultado->num_rows > 0) {
        echo "<script>alert('El correo electrónico ya se encuentra registrado. Intenta con otro o inicia sesión.'); window.history.back();</script>";
    } else {
        $sql = "INSERT INTO usuarios (nombre, cedula, email, password, tipo_tea, modo_oscuro, fuente_grande, pictogramas_activos, temporizador_visual) 
                VALUES ('$nombre', '$cedula', '$email', '$password', $tipo_tea, $modo_oscuro, $fuente_grande, $pictogramas, $temporizador)";

        if ($conn->query($sql) === TRUE) {
            echo "<script>alert('Registro exitoso. Ya puedes iniciar sesión.'); window.location.href='login.php';</script>";
        } else {
            echo "Error: " . $sql . "<br>" . $conn->error;
        }
    }
}
?>