<?php
// procesar_login.php
session_start();
include 'conexion.php';

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // Limpiamos los espacios en blanco de los campos
    $email = trim($_POST['email']);
    $password = trim($_POST['clave']); // Recuerda que en tu login.php el input name lo cambiamos a "clave"

    // 1. CONSULTA PREPARADA SEGURA (Buscamos por email usando ?)
    $stmt = $conn->prepare("SELECT * FROM usuarios WHERE email = ?");
    $stmt->bind_param("s", $email);
    $stmt->execute();
    $result = $stmt->get_result();

    if ($result->num_rows > 0) {
        $usuario = $result->fetch_assoc();
        
        // 2. VERIFICACIÓN DE CONTRASEÑA ENCRIPTADA
        if (password_verify($password, $usuario['password'])) {
            
            // Guardamos las variables de identidad en la sesión del servidor
            $_SESSION['usuario_id'] = $usuario['id'];
            $_SESSION['nombre'] = $usuario['nombre'];
            $_SESSION['rol'] = $usuario['rol'];
            
            // 3. ENRUTAMIENTO INTELIGENTE DE TRES VÍAS POR ROL
            if ($usuario['rol'] === 'administrador') {
                // 🛡️ Si es Betania (Administradora), viaja directo a su centro de mando
                header("Location: administrador.php");
            } elseif ($usuario['rol'] === 'docente') {
                // 👨‍🏫 Si es un profesor normal, va al panel de seguimiento docente
                header("Location: profesor.php");
            } else {
                // 👨‍🎓 Estudiante: redirige según perfil TEA o General
                if ($usuario['tipo_tea'] == 1) {
                    header("Location: index_tea.php");
                } else {
                    header("Location: index.php");
                }
            }
            exit();

        } else {
            // Redirige a la tarjeta blanca con error de clave
            header("Location: login.php?error=clave_incorrecta");
            exit();
        }
    } else {
        // Redirige a la tarjeta blanca con error de usuario
        header("Location: login.php?error=no_usuario");
        exit();
    }
    $stmt->close();
} else {
    header("Location: login.php");
    exit();
}
?>