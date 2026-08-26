<?php
session_start();
include 'conexion.php';

$mensaje = "";

if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["importar"])) {
    $sql = file_get_contents(__DIR__ . "/academia_jb.sql");
    $queries = preg_split('/;\s*[\r\n]+/', $sql);
    $errores = 0;
    foreach ($queries as $query) {
        $query = trim($query);
        if (empty($query)) continue;
        if (!$conn->query($query)) {
            $errores++;
        }
    }
    $mensaje = $errores === 0
        ? "Base de datos importada correctamente."
        : "Importada con $errores advertencias (esto es normal si las tablas ya existian).";
}

$tablas = $conn->query("SHOW TABLES");
$lista = [];
if ($tablas) {
    while ($row = $tablas->fetch_row()) {
        $lista[] = $row[0];
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Setup - Academia JB</title>
    <style>
        body { font-family: Arial, sans-serif; max-width: 600px; margin: 50px auto; padding: 20px; background: #f5f5f5; }
        .card { background: white; border-radius: 10px; padding: 30px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }
        h1 { color: #1a426e; }
        .btn { background: #1a426e; color: white; border: none; padding: 12px 24px; border-radius: 5px; cursor: pointer; font-size: 16px; }
        .btn:hover { background: #0d2d4a; }
        .msg { padding: 15px; border-radius: 5px; margin: 15px 0; background: #d4edda; color: #155724; }
        .tablas { background: #f0f0f0; padding: 10px; border-radius: 5px; margin: 10px 0; }
    </style>
</head>
<body>
    <div class="card">
        <h1>Academia JB - Setup</h1>
        <p>Haz clic en el boton para importar la base de datos:</p>

        <?php if ($mensaje): ?>
            <div class="msg"><?php echo $mensaje; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="hidden" name="importar" value="1">
            <button type="submit" class="btn">Importar Base de Datos</button>
        </form>

        <?php if (count($lista) > 0): ?>
            <div class="tablas">
                <strong>Tablas encontradas (<?php echo count($lista); ?>):</strong>
                <ul>
                <?php foreach ($lista as $t): ?>
                    <li><?php echo $t; ?></li>
                <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>
    </div>
</body>
</html>