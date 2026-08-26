<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id'];
$res_user = $conn->query("SELECT * FROM usuarios WHERE id = $id_usuario");
$usuario = $res_user->fetch_assoc();
$dashboard_url = ($usuario['tipo_tea'] == 1) ? 'index_tea.php' : 'index.php';

$materias_map = [
    'matematica' => 'Matemáticas',
    'fisica' => 'Física',
    'quimica' => 'Química',
    'aeronautica' => 'Aeronáutica',
    'informatica' => 'Informática'
];

$res_historial = $conn->query("
    SELECT i.materia, i.estado, i.fecha_inscripcion, i.notas
    FROM inscripciones i
    WHERE i.usuario_id = $id_usuario
    ORDER BY i.fecha_inscripcion DESC
");

$total_cursos = 0;
$completados = 0;
$activos = 0;
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Progreso - Academia JB</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo $usuario['modo_oscuro'] ? '#121212' : '#f0f4f8'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#ffffff' : '#1a3557'; ?>;
            font-size: <?php echo $usuario['fuente_grande'] ? '24px' : '18px'; ?>;
            transition: all 0.3s ease;
        }
        .navbar {
            background-color: #1a426e;
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; }

        .contenedor {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .breadcrumb {
            font-size: 14px;
            margin-bottom: 20px;
            color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;
        }
        .breadcrumb a { color: #1a426e; text-decoration: none; }
        .breadcrumb .actual { font-weight: bold; }

        .resumen {
            display: grid;
            grid-template-columns: repeat(3, 1fr);
            gap: 15px;
            margin-bottom: 25px;
        }
        .resumen-item {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 20px;
            border-radius: 12px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .resumen-item .numero {
            font-size: <?php echo $usuario['fuente_grande'] ? '36px' : '32px'; ?>;
            font-weight: bold;
        }
        .resumen-item .etiqueta {
            font-size: <?php echo $usuario['fuente_grande'] ? '16px' : '13px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#555'; ?>;
            margin-top: 5px;
        }

        .curso-item {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            border-radius: 12px;
            padding: 20px;
            margin-bottom: 15px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border-left: 5px solid #1a426e;
        }
        .curso-item .cabecera {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 10px;
        }
        .curso-item .nombre {
            font-size: <?php echo $usuario['fuente_grande'] ? '22px' : '18px'; ?>;
            font-weight: bold;
            color: <?php echo $usuario['modo_oscuro'] ? '#4fc3f7' : '#1a426e'; ?>;
        }
        .curso-item .badge {
            display: inline-block;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: <?php echo $usuario['fuente_grande'] ? '16px' : '13px'; ?>;
            font-weight: bold;
        }
        .badge-activo { background: #dbeafe; color: #1d4ed8; }
        .badge-completado { background: #dcfce7; color: #15803d; }

        .curso-item .meta {
            font-size: <?php echo $usuario['fuente_grande'] ? '16px' : '13px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#555'; ?>;
            margin-top: 8px;
        }
        .curso-item .nota {
            margin-top: 10px;
            padding: 12px;
            background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>;
            border-radius: 8px;
            font-size: <?php echo $usuario['fuente_grande'] ? '18px' : '15px'; ?>;
            border: 1px solid <?php echo $usuario['modo_oscuro'] ? '#444' : '#e2e8f0'; ?>;
        }
        .curso-item .nota .label {
            font-weight: bold;
            font-size: <?php echo $usuario['fuente_grande'] ? '16px' : '13px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#334155'; ?>;
        }

        .vacio {
            text-align: center;
            padding: 40px;
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            border-radius: 12px;
            font-size: <?php echo $usuario['fuente_grande'] ? '20px' : '16px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#555' : '#334155'; ?>;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
        @media (max-width: 600px) {
            .resumen { grid-template-columns: 1fr; }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="font-weight: bold; font-size: 20px;">🏆 Mi Progreso</div>
    <div>
        <span>Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></span>
        <a href="logout.php" style="background: #f44336; padding: 5px 10px; border-radius: 4px; margin-left:10px;">Cerrar Sesion</a>
    </div>
</nav>

<div class="contenedor">

    <div class="breadcrumb">
        <a href="<?php echo $dashboard_url; ?>">⬅️ Volver al Inicio</a>
        <span style="color:#94a3b8;"> › </span>
        <span class="actual">Mi Progreso</span>
    </div>

    <?php
    if ($res_historial) {
        $total_cursos = $res_historial->num_rows;
        $res_historial->data_seek(0);
        while ($h = $res_historial->fetch_assoc()) {
            if ($h['estado'] === 'completado') {
                $completados++;
            } else {
                $activos++;
            }
        }
        $res_historial->data_seek(0);
    }
    ?>

    <div class="resumen">
        <div class="resumen-item">
            <div class="numero" style="color:#1a426e;"><?php echo $total_cursos; ?></div>
            <div class="etiqueta">📚 Cursos inscritos</div>
        </div>
        <div class="resumen-item">
            <div class="numero" style="color:#15803d;"><?php echo $completados; ?></div>
            <div class="etiqueta">✅ Completados</div>
        </div>
        <div class="resumen-item">
            <div class="numero" style="color:#1d4ed8;"><?php echo $activos; ?></div>
            <div class="etiqueta">📌 Activos</div>
        </div>
    </div>

    <?php if ($res_historial && $total_cursos > 0): ?>
        <?php while($h = $res_historial->fetch_assoc()):
            $materia_nom = $materias_map[$h['materia']] ?? $h['materia'];
            $es_completado = $h['estado'] === 'completado';
            $badge_class = $es_completado ? 'badge-completado' : 'badge-activo';
            $badge_text = $es_completado ? '✅ Completado' : '📌 Activo';
            $fecha = date('d/m/Y', strtotime($h['fecha_inscripcion']));
        ?>
        <div class="curso-item">
            <div class="cabecera">
                <span class="nombre"><?php echo htmlspecialchars($materia_nom); ?></span>
                <span class="badge <?php echo $badge_class; ?>"><?php echo $badge_text; ?></span>
            </div>
            <div class="meta">📅 Inscrito el <?php echo $fecha; ?></div>
            <?php if ($h['notas'] && trim($h['notas']) !== ''): ?>
                <div class="nota">
                    <span class="label">📝 Resultado:</span>
                    <?php echo nl2br(htmlspecialchars($h['notas'])); ?>
                </div>
            <?php endif; ?>
        </div>
        <?php endwhile; ?>
    <?php else: ?>
        <div class="vacio">
            <div style="font-size:60px; margin-bottom:15px;">🎯</div>
            <p>Aún no te has inscrito en ningún curso.</p>
            <a href="registrar_cursos.php" style="display:inline-block; margin-top:15px; padding:12px 25px; background:#1a426e; color:white; text-decoration:none; border-radius:8px; font-weight:bold;">Ver cursos disponibles</a>
        </div>
    <?php endif; ?>

</div>

<?php include 'asistente.php'; ?>
</body>
</html>
