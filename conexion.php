<?php
$host = "localhost";
$user = "root";
$pass = "";
$db = "academia_jb";

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexión: " . $conn->connect_error);
}
// Forzar codificación UTF-8 para evitar problemas con tildes y la 'ñ'
$conn->set_charset("utf8mb4");
?>