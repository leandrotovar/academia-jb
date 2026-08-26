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

if (isset($_GET['cambiar_ajuste'])) {
    $ajuste = $_GET['cambiar_ajuste'];
    if (in_array($ajuste, ['fuente_grande','pictogramas_activos','modo_oscuro','temporizador_visual'])) {
        $valor_actual = $usuario[$ajuste];
        $nuevo_valor = $valor_actual == 1 ? 0 : 1;
        $conn->query("UPDATE usuarios SET $ajuste = $nuevo_valor WHERE id = $id_usuario");
        header("Location: index_tea.php");
        exit();
    }
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Mi Aula - Academia JB</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: <?php echo $usuario['modo_oscuro'] ? '#121212' : '#e8f0fe'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#ffffff' : '#1a3557'; ?>;
            font-size: <?php echo $usuario['fuente_grande'] ? '26px' : '20px'; ?>;
            transition: all 0.3s ease;
        }

        .navbar {
            background-color: #1a426e;
            color: white;
            padding: 18px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; }
        .navbar .user-name {
            font-size: <?php echo $usuario['fuente_grande'] ? '22px' : '18px'; ?>;
        }

        .contenedor {
            max-width: 900px;
            margin: 30px auto;
            padding: 0 20px;
        }

        .bienvenida {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 30px;
            border-radius: 16px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
            text-align: center;
        }
        .bienvenida h2 {
            margin: 0 0 5px 0;
            color: #1a426e;
            font-size: <?php echo $usuario['fuente_grande'] ? '32px' : '28px'; ?>;
        }
        .bienvenida p {
            margin: 0;
            font-size: <?php echo $usuario['fuente_grande'] ? '20px' : '16px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;
        }

        .grid-modulos {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(200px, 1fr));
            gap: 20px;
            margin-bottom: 25px;
        }
        .tarjeta {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 30px 20px;
            border-radius: 16px;
            text-align: center;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 3px solid transparent;
            transition: transform 0.2s, border-color 0.2s;
            text-decoration: none;
            color: inherit;
            display: block;
            cursor: pointer;
        }
        .tarjeta:hover {
            transform: translateY(-5px);
            border-color: #2196F3;
        }
        .tarjeta .icono {
            font-size: 60px;
            margin-bottom: 10px;
        }
        .tarjeta h3 {
            margin: 0 0 5px 0;
            font-size: <?php echo $usuario['fuente_grande'] ? '24px' : '20px'; ?>;
            color: #1a426e;
        }
        .tarjeta p {
            margin: 0;
            font-size: <?php echo $usuario['fuente_grande'] ? '18px' : '14px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#bbb' : '#555'; ?>;
        }

        .historial-notas {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        .historial-notas h4 {
            margin: 0 0 5px 0;
            color: <?php echo $usuario['modo_oscuro'] ? '#4fc3f7' : '#1a426e'; ?>;
            font-size: <?php echo $usuario['fuente_grande'] ? '24px' : '20px'; ?>;
        }
        .historial-notas > p {
            margin: 0 0 15px 0;
            font-size: <?php echo $usuario['fuente_grande'] ? '18px' : '14px'; ?>;
            color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#444'; ?>;
        }

        .ajustes-rapidos {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#e3f2fd'; ?>;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 25px;
        }
        .ajustes-rapidos h4 {
            margin: 0 0 5px 0;
            color: #1a426e;
            font-size: <?php echo $usuario['fuente_grande'] ? '22px' : '18px'; ?>;
        }
        .ajustes-rapidos p {
            font-size: <?php echo $usuario['fuente_grande'] ? '18px' : '14px'; ?>;
            margin-bottom: 15px;
            color: <?php echo $usuario['modo_oscuro'] ? '#bbb' : '#334155'; ?>;
        }
        .asistente-integrado-bloque {
            background: #1a426e;
            color: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 15px rgba(255, 152, 0, 0.2);
            text-align: center;
            cursor: pointer;
            transition: transform 0.2s, background-color 0.2s;
            display: flex;
            flex-direction: column;
            justify-content: center;
            align-items: center;
        }
        .asistente-integrado-bloque:hover {
            transform: translateY(-5px);
            background-color: #1a426e;
        }

        .btn-ajuste {
            display: inline-block;
            padding: 14px 20px;
            margin: 6px;
            border-radius: 30px;
            border: none;
            background: #1a426e;
            color: white;
            text-decoration: none;
            font-size: <?php echo $usuario['fuente_grande'] ? '18px' : '15px'; ?>;
            cursor: pointer;
            font-weight: bold;
        }
        .btn-ajuste.activo { background: #4CAF50; color: white; }

        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 2000;
            display: none; justify-content: center; align-items: center;
        }
        .modal-overlay.mostrar { display: flex; }
        .modal-contenido {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : 'white'; ?>;
            padding: 35px; border-radius: 16px;
            width: 420px; max-width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-contenido h2 { margin-top: 0; color: #1a426e; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .modal-contenido h2.dark { color: #4fc3f7; border-bottom-color: #444; }
        .modal-cerrar {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; cursor: pointer; color: #555;
            background: none; border: none; font-weight: bold;
        }
        .modal-cerrar:hover { color: #f44336; }
        .perfil-item { margin-bottom: 18px; }
        .perfil-label { font-size: 12px; color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .perfil-label.dark { color: #aaa; }
        .perfil-valor { font-size: 18px; color: #1a3557; font-weight: bold; margin-top: 3px; }
        .perfil-valor.dark { color: #e0e0e0; }
        .perfil-badge {
            display: inline-block; background: #1a426e; color: white;
            padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: bold;
        }
        .perfil-tea { display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px; }
        .perfil-tea-item { display: inline-block; padding: 3px 10px; border-radius: 12px; font-size: 12px; font-weight: bold; }
        .perfil-tea-si { background: #dcfce7; color: #15803d; }
        .perfil-tea-no { background: #fee2e2; color: #b91c1c; }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }
    </style>
</head>
<body>

<nav class="navbar">
    <div style="font-weight: bold; font-size: 22px;">🎓 Academia JB</div>
    <div>
        <span class="user-name">👋 <?php echo htmlspecialchars($usuario['nombre']); ?></span>
        <button onclick="togglePerfilEst()" style="background:#4CAF50; color:white; border:none; padding:8px 14px; border-radius:6px; cursor:pointer; font-weight:bold; margin-left:12px;">👤 Mi Perfil</button>
        <a href="logout.php" style="background: #f44336; padding: 8px 14px; border-radius: 6px; margin-left:12px;">Cerrar Sesion</a>
    </div>
</nav>

<div class="contenedor">

    <div style="display: grid; grid-template-columns: 2fr 1fr; gap: 20px; margin-bottom: 25px;">
        <div class="bienvenida" style="margin-bottom:0; display:flex; flex-direction:column; justify-content:center;">
            <h2>¡Hola, <?php echo htmlspecialchars(explode(' ', $usuario['nombre'])[0]); ?>! 👋</h2>
            <p>Elige lo que quieres hacer hoy</p>
        </div>
        <div class="asistente-integrado-bloque" onclick="alternarAsistente()">
            <div style="font-size: 50px; margin-bottom: 5px;">🤖</div>
            <h3 style="margin: 0; font-size: <?php echo $usuario['fuente_grande'] ? '22px' : '18px'; ?>;">Asistente Virtual</h3>
            <p style="margin: 5px 0 0 0; font-size: <?php echo $usuario['fuente_grande'] ? '16px' : '13px'; ?>; opacity: 0.9;">¿Tienes dudas? Haz clic aquí</p>
        </div>
    </div>

    <div class="grid-modulos">

        <a href="registrar_cursos.php" class="tarjeta">
            <div class="icono">📚</div>
            <h3>Mis Cursos</h3>
            <p>Ver mis lecciones</p>
        </a>

        <a href="progreso.php" class="tarjeta">
            <div class="icono">🏆</div>
            <h3>Mi Progreso</h3>
            <p>Ver mis logros</p>
        </a>

        <a href="calendario.php" class="tarjeta">
            <div class="icono">📅</div>
            <h3>Calendario</h3>
            <p>Fechas importantes</p>
        </a>

        <a href="glosario.php" class="tarjeta">
            <div class="icono">📖</div>
            <h3>Glosario</h3>
            <p>Palabras y概念</p>
        </a>
    </div>

    <div class="historial-notas">
        <h4>📋 Mis Cursos</h4>
        <p>Aquí están los cursos en los que te has inscrito.</p>

        <?php if ($res_historial && $res_historial->num_rows > 0): ?>
            <?php while($h = $res_historial->fetch_assoc()):
                $materia_nom = $materias_map[$h['materia']] ?? $h['materia'];
                $es_completado = $h['estado'] === 'completado';
                $badge_color = $es_completado ? '#15803d' : '#1d4ed8';
                $badge_bg = $es_completado ? '#dcfce7' : '#dbeafe';
                $badge_text = $es_completado ? '✅ Completado' : '📌 Activo';
            ?>
                <div style="background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>; border-left: 5px solid #1a426e; padding: 15px; border-radius: 8px; margin-bottom: 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                        <div>
                            <strong style="font-size: 18px; color: <?php echo $usuario['modo_oscuro'] ? '#4fc3f7' : '#1a426e'; ?>;"><?php echo htmlspecialchars($materia_nom); ?></strong>
                            <span style="display: inline-block; margin-left: 10px; padding: 3px 10px; border-radius: 4px; font-size: 14px; font-weight: bold; background: <?php echo $badge_bg; ?>; color: <?php echo $badge_color; ?>;"><?php echo $badge_text; ?></span>
                        </div>
                        <div style="font-size: 14px; color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#334155'; ?>;">
                            📅 <?php echo date('d/m/Y', strtotime($h['fecha_inscripcion'])); ?>
                        </div>
                    </div>
                    <?php if ($h['notas'] && trim($h['notas']) !== ''): ?>
                        <div style="margin-top: 10px; padding: 10px; background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#fff'; ?>; border-radius: 6px; border: 1px solid <?php echo $usuario['modo_oscuro'] ? '#444' : '#e2e8f0'; ?>;">
                            <span style="font-size: 13px; font-weight: bold; color: <?php echo $usuario['modo_oscuro'] ? '#aaa' : '#334155'; ?>;">📝 Nota del profesor:</span>
                            <p style="margin: 5px 0 0 0; font-size: 16px; color: <?php echo $usuario['modo_oscuro'] ? '#ddd' : '#334155'; ?>;"><?php echo nl2br(htmlspecialchars($h['notas'])); ?></p>
                        </div>
                    <?php endif; ?>
                </div>
            <?php endwhile; ?>
        <?php else: ?>
            <div style="padding: 20px; text-align: center; color: <?php echo $usuario['modo_oscuro'] ? '#555' : '#334155'; ?>; background: <?php echo $usuario['modo_oscuro'] ? '#2d2d2d' : '#f8fafc'; ?>; border-radius: 8px; font-size: 18px;">
                🎯 Aún no tienes cursos. Ve a <strong>Mis Cursos</strong> para empezar.
            </div>
        <?php endif; ?>
    </div>

    <div class="ajustes-rapidos">
        <h4>🛠️ Herramientas para ti</h4>
        <p>Haz clic para cambiar cómo se ve la pantalla:</p>
        <a href="index_tea.php?cambiar_ajuste=fuente_grande" class="btn-ajuste <?php echo $usuario['fuente_grande'] ? 'activo' : ''; ?>">
            🔍 Texto Grande (<?php echo $usuario['fuente_grande'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index_tea.php?cambiar_ajuste=pictogramas_activos" class="btn-ajuste <?php echo $usuario['pictogramas_activos'] ? 'activo' : ''; ?>">
            🖼️ Dibujos (<?php echo $usuario['pictogramas_activos'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index_tea.php?cambiar_ajuste=modo_oscuro" class="btn-ajuste <?php echo $usuario['modo_oscuro'] ? 'activo' : ''; ?>">
            🌙 Modo Oscuro (<?php echo $usuario['modo_oscuro'] ? 'SÍ' : 'NO'; ?>)
        </a>
        <a href="index_tea.php?cambiar_ajuste=temporizador_visual" class="btn-ajuste <?php echo $usuario['temporizador_visual'] ? 'activo' : ''; ?>">
            ⏱️ Temporizador (<?php echo $usuario['temporizador_visual'] ? 'SÍ' : 'NO'; ?>)
        </a>
    </div>

</div>

<div class="modal-overlay" id="modalPerfilEst">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="togglePerfilEst()">&times;</button>
        <h2 class="<?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">👤 Mi Perfil</h2>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Nombre Completo</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['nombre']); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Cédula</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['cedula'] ?? ''); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Correo Electrónico</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo htmlspecialchars($usuario['email']); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Rol</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">
                <span class="perfil-badge">Estudiante</span>
                <span style="display:inline-block; margin-left:8px; padding:3px 10px; border-radius:12px; font-size:12px; font-weight:bold; background:#dcfce7; color:#15803d;">TEA</span>
            </div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Miembro desde</div>
            <div class="perfil-valor <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>"><?php echo date('d/m/Y', strtotime($usuario['fecha_registro'])); ?></div>
        </div>
        <div class="perfil-item">
            <div class="perfil-label <?php echo $usuario['modo_oscuro'] ? 'dark' : ''; ?>">Preferencias TEA</div>
            <div class="perfil-tea">
                <span class="perfil-tea-item <?php echo $usuario['fuente_grande'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Letra Grande: <?php echo $usuario['fuente_grande'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['pictogramas_activos'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Pictogramas: <?php echo $usuario['pictogramas_activos'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['modo_oscuro'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Modo Oscuro: <?php echo $usuario['modo_oscuro'] ? 'SÍ' : 'NO'; ?></span>
                <span class="perfil-tea-item <?php echo $usuario['temporizador_visual'] ? 'perfil-tea-si' : 'perfil-tea-no'; ?>">Temporizador: <?php echo $usuario['temporizador_visual'] ? 'SÍ' : 'NO'; ?></span>
            </div>
        </div>
    </div>
</div>

<script>
function togglePerfilEst() {
    document.getElementById('modalPerfilEst').classList.toggle('mostrar');
}
document.getElementById('modalPerfilEst').addEventListener('click', function(e) {
    if (e.target === this) togglePerfilEst();
});
</script>

<?php include 'asistente.php'; ?>
</body>
</html>
