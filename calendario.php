<?php
session_start();
include 'conexion.php';

// Validar inicio de sesión
if (!isset($_SESSION['usuario_id'])) {
    header("Location: login.php");
    exit();
}

$id_usuario = $_SESSION['usuario_id']; 
$res_user = $conn->query("SELECT * FROM usuarios WHERE id = $id_usuario");
$usuario = $res_user->fetch_assoc();
$dashboard_url = ($usuario['tipo_tea'] == 1) ? 'index_tea.php' : 'index.php';
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Calendario Académico - Formación Académica JB</title>
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
            background-color: #1a426e; /* Azul cálido JB unificado */
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; }
        
        .contenedor { max-width: 900px; margin: 40px auto; padding: 0 20px; }
        
        .encabezado { 
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>; 
            padding: 25px; 
            border-radius: 12px; 
            box-shadow: 0 4px 10px rgba(0,0,0,0.05); 
            margin-bottom: 30px; 
        }
        
        /* Contenedor de la línea cronológica (Estructura lineal anti-ansiedad) */
        .cronograma {
            display: flex;
            flex-direction: column;
            gap: 20px;
        }
        
        .evento-tarjeta {
            background: <?php echo $usuario['modo_oscuro'] ? '#1f1f1f' : '#ffffff'; ?>;
            border-left: 8px solid #1a426e; /* Identificador lateral de color */
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            display: flex;
            align-items: center;
            gap: 20px;
        }
        
        /* Colores dinámicos por tipo de evento para fácil reconocimiento visual */
        .evento-inscripcion { border-left-color: #2196F3; } /* Azul */
        .evento-clase { border-left-color: #4CAF50; }       /* Verde */
        .evento-entrega { border-left-color: #FF9800; }     /* Naranja */

        .fecha-bloque {
            background: #1a426e;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-align: center;
            font-weight: bold;
            min-width: 90px;
        }
        .fecha-bloque span { display: block; font-size: 12px; font-weight: normal; text-transform: uppercase; }
        
        .info-bloque { flex: 1; }
        .info-bloque h3 { margin: 0 0 5px 0; font-size: 20px; color: #1a426e; }
        body[style*="background-color: #121212"] .info-bloque h3 { color: #4fc3f7; } /* Contraste en modo oscuro */
        .info-bloque p { margin: 0; color: #555; font-size: 15px; }
        body[style*="background-color: #121212"] .info-bloque p { color: #ccc; }

        .picto-evento { font-size: 35px; }

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
        .perfil-tea {
            display: flex; flex-wrap: wrap; gap: 8px; margin-top: 5px;
        }
        .perfil-tea-item {
            display: inline-block; padding: 3px 10px; border-radius: 12px;
            font-size: 12px; font-weight: bold;
        }
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
    <div style="display:flex; align-items:center; gap:15px;">
        <div style="font-weight: bold; font-size: 20px;">Formación Académica JB 📅</div>
    </div>
    <div>
        <span>Hola, <strong><?php echo htmlspecialchars($usuario['nombre']); ?></strong></span>
        <button onclick="togglePerfilEst()" style="background:#4CAF50; color:white; border:none; padding:5px 10px; border-radius:4px; cursor:pointer; font-weight:bold; margin-left:10px;">👤 Mi Perfil</button>
        <a href="logout.php" style="background: #f44336; padding: 5px 10px; border-radius: 4px; margin-left:10px;">Cerrar Sesion</a>
    </div>
</nav>

<div class="contenedor">
    <div style="margin-bottom: 15px; font-size: 14px; color: <?php echo $usuario['modo_oscuro'] ? '#ccc' : '#334155'; ?>;">
        <a href="<?php echo $dashboard_url; ?>" style="color: #1a426e; text-decoration: none;">⬅️ Volver al Inicio</a>
        <span style="color: #94a3b8; margin: 0 8px;">›</span>
        <span style="color: <?php echo $usuario['modo_oscuro'] ? '#fff' : '#334155'; ?>; font-weight: bold;">Calendario</span>
    </div>
    <div class="encabezado">
        <h2 style="margin: 0 0 10px 0; color: #1a426e;">Planificación Cronológica</h2>
        <p style="margin: 0; color: #555; font-size: 16px;">Consulta de manera clara e intuitiva los períodos de inscripción, inicios de clases virtuales y fechas límites de entregas estipuladas por la academia.</p>
    </div>

    <?php
    $eventos_res = $conn->query("SELECT * FROM eventos WHERE activo=1 ORDER BY fecha_inicio ASC");
    $meses_es = ['enero','febrero','marzo','abril','mayo','junio','julio','agosto','septiembre','octubre','noviembre','diciembre'];
    ?>
    <div class="cronograma">
        <?php while ($ev = $eventos_res->fetch_assoc()): 
            $color_map = ['inscripcion'=>'#2196F3','clase'=>'#4CAF50','entrega'=>'#FF9800','otro'=>'#1a426e'];
            $bg = $color_map[$ev['tipo']] ?? '#1a426e';
            $css_class = 'evento-'.htmlspecialchars($ev['tipo']);
            $ts = strtotime($ev['fecha_inicio']);
            $fecha = date('d', $ts);
            $mes_num = (int)date('n', $ts) - 1;
            $mes = $meses_es[$mes_num] ?? date('F', $ts);
            $fecha_fin = $ev['fecha_fin'] ? ' - '.date('d', strtotime($ev['fecha_fin'])) : '';
        ?>
        <div class="evento-tarjeta <?php echo $css_class; ?>">
            <div class="fecha-bloque" style="background-color: <?php echo $bg; ?>;">
                <?php echo $fecha . $fecha_fin; ?> <span><?php echo ucfirst($mes); ?></span>
            </div>
            <?php if ($usuario['pictogramas_activos'] == 1): ?>
                <div class="picto-evento"><?php echo htmlspecialchars($ev['icono']); ?></div>
            <?php endif; ?>
            <div class="info-bloque">
                <h3><?php echo htmlspecialchars($ev['titulo']); ?></h3>
                <p><?php echo htmlspecialchars($ev['descripcion']); ?></p>
            </div>
        </div>
        <?php endwhile; ?>
    </div>
</div>

<!-- Modal Perfil del Estudiante -->
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