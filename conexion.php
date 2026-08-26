<?php
if (getenv("MYSQLHOST")) {
    $host = getenv("MYSQLHOST");
    $user = getenv("MYSQLUSER");
    $pass = getenv("MYSQLPASSWORD");
    $db = getenv("MYSQLDATABASE");
} elseif (getenv("DATABASE_URL")) {
    $url = parse_url(getenv("DATABASE_URL"));
    $host = $url["host"];
    $user = $url["user"];
    $pass = $url["pass"];
    $db = ltrim($url["path"], "/");
} else {
    $host = "localhost";
    $user = "root";
    $pass = "";
    $db = "academia_jb";
}

$conn = new mysqli($host, $user, $pass, $db);

if ($conn->connect_error) {
    die("Error de conexion: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");
?>