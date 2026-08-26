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
    die("Error: " . $conn->connect_error);
}
$conn->set_charset("utf8mb4");

$mensaje = "";
if (isset($_POST["importar"])) {
    $sql = file_get_contents(__DIR__ . "/academia_jb.sql");
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    $ok = 0;
    $fail = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        if ($conn->query($query)) {
            $ok++;
        } else {
            $fail++;
        }
    }
    $mensaje = "Importado: $ok exitosas, $fail advertencias.";
}

$tablas = [];
$res = $conn->query("SHOW TABLES");
if ($res) {
    while ($row = $res->fetch_row()) {
        $tablas[] = $row[0];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Importar BD - Academia JB</title>
    <style>
        body { font-family: Arial; max-width: 600px; margin: 50px auto; padding: 20px; }
        .card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1a426e; }
        .btn { background: #1a426e; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .msg { padding: 15px; border-radius: 5px; margin: 15px 0; background: #d4edda; color: #155724; }
        ul { background: #f0f0f0; padding: 15px; border-radius: 5px; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Academia JB - Importar Base de Datos</h1>
        <?php if ($mensaje): ?>
            <div class="msg"><?php echo $mensaje; ?></div>
        <?php endif; ?>
        <form method="POST">
            <input type="hidden" name="importar" value="1">
            <button type="submit" class="btn">Importar Base de Datos</button>
        </form>
        <?php if (count($tablas) > 0): ?>
            <h3>Tablas (<?php echo count($tablas); ?>):</h3>
            <ul><?php foreach ($tablas as $t) echo "<li>$t</li>"; ?></ul>
        <?php endif; ?>
    </div>
</body>
</html>
