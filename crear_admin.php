<?php
// crear_admin.php
require_once 'conexion.php';

// Borramos cualquier rastro previo de ese correo para evitar duplicados
$conn->query("DELETE FROM usuarios WHERE email = 'betania@academiajb.com'");

// Generamos el hash criptográfico exacto y nativo de tu PHP actual para "betania26"
$clave_limpia = "betania26";
$hash_real = password_hash($clave_limpia, PASSWORD_DEFAULT);
$nombre = "Betania Silva";
$rol = "administrador";

// Inserción limpia mediante una consulta preparada nativa
$stmt = $conn->prepare("INSERT INTO usuarios (nombre, email, password, rol) VALUES (?, ?, ?, ?)");
$stmt->bind_param("ssss", $nombre, $email_form, $hash_real, $rol);
$email_form = "betania@academiajb.com";

if ($stmt->execute()) {
    echo "<h2 style='color:green; font-family:Arial;'>✅ ¡Usuario Administrador Creado con Éxito desde el Servidor!</h2>";
    echo "<p style='font-family:Arial;'>Ahora ve al login e ingresa con: <b>betania@academiajb.com</b> y la clave <b>betania26</b></p>";
} else {
    echo "Error al crear: " . $stmt->error;
}
$stmt->close();
$conn->close();
?>