<?php
session_start();
include 'conexion.php';

// 1. Validar que el usuario haya iniciado sesión y que sea un docente o administrador
if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] === 'estudiante') {
    echo '<!DOCTYPE html>
<html lang="es">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Academia JB</title>
<script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
<script src="msj_jb.js?v=4"></script>
</head>
<body>
<script>msjJb("Acceso denegado. Esta pantalla es exclusiva para profesores.", "error", function(){ window.location.href="login.php"; });</script>
</body>
</html>';
    exit();
}

$id_profesor = $_SESSION['usuario_id'];
$nombre_profesor = $_SESSION['nombre'];

// 2. Consultar datos del profesor actual
$res_profesor = $conn->query("SELECT * FROM usuarios WHERE id = $id_profesor");
$datos_profesor = $res_profesor->fetch_assoc();

// 3. Cargar todas las materias asignadas al profesor desde la tabla junction
$materias_profesor = [];
$res_pm = $conn->query("
    SELECT pm.materia_clave, c.nombre AS curso_nombre
    FROM profesor_materias pm
    JOIN cursos c ON pm.materia_clave = c.clave
    WHERE pm.profesor_id = $id_profesor
    ORDER BY c.nombre ASC
");
$iconos_materias = ['matematica'=>'📐', 'fisica'=>'⚛️', 'quimica'=>'🧪', 'aeronautica'=>'✈️', 'informatica'=>'💻'];
while ($rmp = $res_pm->fetch_assoc()) {
    $clave = $rmp['materia_clave'];
    $rmp['icono'] = $iconos_materias[$clave] ?? '📚';
    // Cargar estudiantes inscritos en este curso
    $alumnos = [];
    $res_al = $conn->query("
        SELECT u.id as usuario_id, u.nombre, u.email, u.fuente_grande, u.temporizador_visual,
               i.id as inscripcion_id, i.fecha_inscripcion, i.notas
        FROM inscripciones i
        JOIN usuarios u ON i.usuario_id = u.id
        WHERE i.materia = '$clave'
        ORDER BY u.nombre ASC
    ");
    while ($al = $res_al->fetch_assoc()) {
        $alumnos[] = $al;
    }
    $rmp['alumnos'] = $alumnos;
    $rmp['total_inscritos'] = count($alumnos);
    $materias_profesor[] = $rmp;
}

require_once 'pizarra_bd.php';
pizarra_crear_tablas($conn);
$salas_profesor = [];
$res_sp = $conn->query("SELECT materia, codigo FROM pizarra_salas WHERE activa = 1 ORDER BY id DESC");
if ($res_sp) {
    while ($fs = $res_sp->fetch_assoc()) {
        if (!isset($salas_profesor[$fs['materia']])) {
            $salas_profesor[$fs['materia']] = $fs['codigo'];
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel del Docente - Formación Académica JB</title>
    <style>
        body {
            font-family: 'Arial', sans-serif;
            margin: 0;
            padding: 0;
            background-color: #f0f4f8;
            color: #1a3557;
        }
        .navbar {
            background-color: #1a426e; /* Azul cálido JB unificado */
            color: white;
            padding: 15px 30px;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 4px 6px rgba(0,0,0,0.1);
        }
        .navbar a { color: white; text-decoration: none; font-weight: bold; margin-left: 20px; }
        
        .contenedor {
            max-width: 1100px;
            margin: 40px auto;
            padding: 0 20px;
        }
        
        /* Contenedor Superior Informativo */
        .encabezado {
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            margin-bottom: 25px;
        }
        h2 { margin: 0 0 10px 0; color: #1a426e; }
        
        /* Contenedor General de la Tabla */
        .tabla-contenedor {
            background: white;
            border-radius: 12px;
            padding: 25px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            overflow-x: auto;
        }
        table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }
        th, td {
            padding: 14px 16px;
            border-bottom: 1px solid #e2e8f0;
        }
        th {
            background-color: #f8fafc;
            color: #1a426e;
            font-weight: bold;
        }
        tr:hover { background-color: #f1f5f9; }
        
        /* Badges de Estado Limpios */
        .badge {
            display: inline-block;
            padding: 6px 10px;
            font-size: 12px;
            font-weight: bold;
            border-radius: 6px;
            text-align: center;
            min-width: 35px;
        }
        .badge-activo { background-color: #dcfce7; color: #15803d; }   /* Verde suave */
        .badge-inactivo { background-color: #fee2e2; color: #b91c1c; } /* Rojo suave */
        
        /* Botones de Acción de Gestión (Nueva Mejora Alcance 3 y 5) */
        .btn-accion-tabla {
            background-color: #1a426e;
            color: white;
            border: none;
            padding: 8px 12px;
            border-radius: 6px;
            font-size: 13px;
            font-weight: bold;
            cursor: pointer;
            text-decoration: none;
            display: inline-block;
            transition: background 0.2s;
        }
        .btn-accion-tabla:hover { background-color: #112d4e; }
        
        .btn-admin-lista {
            background-color: #4CAF50;
            color: white;
            padding: 10px 15px;
            border-radius: 6px;
            text-decoration: none;
            font-weight: bold;
            font-size: 14px;
            display: inline-block;
            float: right;
            margin-top: -5px;
        }
        .btn-admin-lista:hover { background-color: #45a049; }
        
        /* Tarjeta de Curso Asignado */
        .curso-card {
            background: #f8fafc;
            border: 2px solid #e2e8f0;
            border-radius: 12px;
            padding: 20px;
            display: flex;
            align-items: center;
            gap: 18px;
            cursor: pointer;
            transition: all 0.2s;
        }
        .curso-card:hover {
            border-color: #1a426e;
            background: #f1f5f9;
            transform: translateY(-2px);
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .curso-card-icon {
            font-size: 40px;
            width: 60px;
            height: 60px;
            background: #1a426e;
            border-radius: 12px;
            display: flex;
            align-items: center;
            justify-content: center;
        }
        .curso-card-info { flex: 1; }
        .curso-card-info h4 { margin: 0; font-size: 20px; color: #1a426e; }
        .curso-card-info p { margin: 5px 0 0 0; font-size: 14px; color: #334155; }
        .curso-card-flecha {
            font-size: 20px;
            color: #1a426e;
            transition: transform 0.3s;
        }
        .curso-card-flecha.abierto { transform: rotate(90deg); }
        .tabla-curso {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(-10px); }
            to { opacity: 1; transform: translateY(0); }
        }
        
        /* Modal de Perfil */
        .modal-overlay {
            position: fixed;
            top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5);
            z-index: 1000;
            display: none;
            justify-content: center;
            align-items: center;
        }
        .modal-overlay.mostrar { display: flex; }
        .modal-contenido {
            background: white;
            padding: 35px;
            border-radius: 16px;
            width: 420px;
            max-width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative;
            animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-contenido h2 { margin-top: 0; color: #1a426e; border-bottom: 2px solid #e2e8f0; padding-bottom: 15px; }
        .modal-cerrar {
            position: absolute;
            top: 15px; right: 20px;
            font-size: 24px;
            cursor: pointer;
            color: #555;
            background: none;
            border: none;
            font-weight: bold;
        }
        .modal-cerrar:hover { color: #f44336; }
        .perfil-item { margin-bottom: 18px; }
        .perfil-label { font-size: 12px; color: #334155; font-weight: bold; text-transform: uppercase; letter-spacing: 0.5px; }
        .perfil-valor { font-size: 18px; color: #1a3557; font-weight: bold; margin-top: 3px; }
        .perfil-badge {
            display: inline-block;
            background: #1a426e;
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 14px;
            font-weight: bold;
        }

        @media (prefers-reduced-motion: reduce) {
            *, *::before, *::after {
                animation-duration: 0.01ms !important;
                animation-iteration-count: 1 !important;
                transition-duration: 0.01ms !important;
            }
        }

        .pizarras-panel {
            background: #ffffff;
            border-radius: 10px;
            padding: 25px;
            box-shadow: 0 4px 6px rgba(0,0,0,0.05);
            margin-bottom: 30px;
        }
        .pizarras-panel h3 {
            color: #1a426e;
            margin-top: 0;
            margin-bottom: 6px;
        }
        .pizarras-panel > p {
            color: #555;
            font-size: 15px;
            margin: 0 0 18px 0;
        }
        .pizarras-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 15px;
        }
        .pizarra-card {
            border: 2px solid #dbe7f3;
            border-radius: 10px;
            padding: 16px;
            display: flex;
            flex-direction: column;
            gap: 10px;
            align-items: flex-start;
        }
        .pizarra-card h4 {
            margin: 0;
            color: #1a426e;
            font-size: 18px;
        }
        .codigo-sala {
            font-size: 22px;
            font-weight: bold;
            letter-spacing: 4px;
            color: #b91c1c;
        }
        .btn-pizarra {
            display: inline-block;
            background: #1a426e;
            color: white;
            border: none;
            text-decoration: none;
            padding: 9px 16px;
            border-radius: 8px;
            font-weight: bold;
            font-size: 14px;
            cursor: pointer;
        }
        .btn-pizarra.crear { background: #6B9E5B; }
        .btn-pizarra.nueva { background: #8A7F9F; }
        .btn-pizarra:hover { filter: brightness(0.94); }
    </style>
    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11/dist/sweetalert2.all.min.js"></script>
    <script src="msj_jb.js?v=4"></script>
</head>
<body>
    

<nav class="navbar">
    <div style="font-weight: bold; font-size: 20px;">Formación Académica JB - Panel Docente 👨‍🏫</div>
    <div>
        <span>Prof. <?php echo htmlspecialchars($nombre_profesor); ?></span>
        <button onclick="togglePerfil()" style="background:#4CAF50; color:white; border:none; padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:bold; margin-left:10px;">👤 Mi Perfil</button>
        <a href="logout.php" style="background: #f44336; padding: 6px 12px; border-radius: 6px; margin-left:10px;">Cerrar Sesion</a>
    </div>
</nav>

<div class="contenedor">
    <div style="margin-bottom: 15px; font-size: 14px; color: #334155;">
        <a href="profesor.php" style="color: #1a426e; text-decoration: none;">Inicio</a>
        <span style="color: #94a3b8; margin: 0 8px;">›</span>
        <span style="color: #334155; font-weight: bold;">Panel Docente</span>
    </div>
    <!-- Encabezado de Gestión -->
    <div class="encabezado">
        <h2>Gestión, Evaluación y Seguimiento de Cursos</h2>
        <p style="margin: 0; color: #555; font-size: 16px;">Supervise las adaptaciones del entorno y administre de forma activa el progreso académico global de los estudiantes.</p>
    </div>

    <!-- CURSOS ASIGNADOS Y ESTUDIANTES INSCRITOS -->
    <div class="tabla-contenedor">
        <h3 style="color: #1a426e; margin-top: 0; margin-bottom: 20px;">📋 Mis Cursos Asignados</h3>
        
        <?php if (!empty($materias_profesor)): ?>
            <?php foreach ($materias_profesor as $mp):
                $clave = $mp['materia_clave'];
                $nombre_curso = htmlspecialchars($mp['curso_nombre']);
            ?>
            <!-- Tarjeta del curso -->
            <div class="curso-card" onclick="toggleCurso('<?php echo $clave; ?>')" data-curso="<?php echo $clave; ?>">
                <div class="curso-card-icon"><?php echo $mp['icono']; ?></div>
                <div class="curso-card-info">
                    <h4><?php echo $nombre_curso; ?></h4>
                    <p><?php echo $mp['total_inscritos']; ?> estudiante(s) inscrito(s)</p>
                </div>
                <div class="curso-card-flecha" id="flecha-<?php echo $clave; ?>">▶</div>
            </div>
            
            <!-- Tabla de estudiantes del curso -->
            <div class="tabla-curso" id="tabla-<?php echo $clave; ?>" style="display:none; margin-top:20px;">
                <h4 style="color:#1a426e; margin:0 0 15px 0;">Estudiantes inscritos en <?php echo $nombre_curso; ?></h4>
                <table>
                    <thead>
                        <tr>
                            <th>Nombre Estudiante</th>
                            <th>Correo Electrónico</th>
                            <th>Fecha de Inscripción</th>
                            <th style="text-align: center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($mp['alumnos'])): ?>
                            <?php foreach ($mp['alumnos'] as $alumno): ?>
                                <tr>
                                    <td><strong><?php echo htmlspecialchars($alumno['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($alumno['email']); ?></td>
                                    <td><?php echo date('d/m/Y H:i', strtotime($alumno['fecha_inscripcion'])); ?></td>
                                    <td style="text-align: center;">
                                        <button class="btn-accion-tabla" onclick="abrirMonitoreo(<?php echo $alumno['usuario_id']; ?>, <?php echo $alumno['inscripcion_id']; ?>, '<?php echo htmlspecialchars($alumno['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($alumno['email'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($alumno['notas'], ENT_QUOTES); ?>')">
                                            🔍 Monitorear
                                        </button>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="4" style="text-align: center; color: #334155;">No hay estudiantes inscritos en este curso todavía.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
            <?php endforeach; ?>
        <?php else: ?>
            <div style="text-align:center; padding:30px; color:#334155;">
                <p style="font-size:18px;">❌ No tienes ninguna materia asignada.</p>
                <p style="font-size:14px;">Contacta al administrador para que te asigne un curso.</p>
            </div>
        <?php endif; ?>
    </div>

    <!-- PIZARRAS EN VIVO -->
    <?php if (!empty($materias_profesor)): ?>
    <div class="pizarras-panel">
        <h3>🎨 Pizarras en vivo</h3>
        <p>Crea una sala por curso y comparte el código con los estudiantes inscritos. Solo el profesor puede crear salas.</p>
        <div class="pizarras-grid">
            <?php foreach ($materias_profesor as $mp):
                $clave = $mp['materia_clave'];
                $codigo_sala = isset($salas_profesor[$clave]) ? $salas_profesor[$clave] : null; ?>
                <div class="pizarra-card">
                    <h4><?php echo $mp['icono'] . ' ' . htmlspecialchars($mp['curso_nombre']); ?></h4>
                    <?php if ($codigo_sala): ?>
                        <span class="codigo-sala"><?php echo htmlspecialchars($codigo_sala); ?></span>
                        <div>
                            <a href="pizarra.php?sala=<?php echo urlencode($codigo_sala); ?>&materia=<?php echo urlencode($clave); ?>" class="btn-pizarra">Entrar</a>
                            <button class="btn-pizarra nueva" onclick="crearSalaProfesor('<?php echo $clave; ?>')">Nueva sala</button>
                        </div>
                    <?php else: ?>
                        <button class="btn-pizarra crear" onclick="crearSalaProfesor('<?php echo $clave; ?>')">🆕 Crear sala</button>
                    <?php endif; ?>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endif; ?>
</div>

<!-- Modal Perfil del Profesor -->
<div class="modal-overlay" id="modalPerfil">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="togglePerfil()">&times;</button>
        <h2>👤 Mi Perfil</h2>
        
        <div class="perfil-item">
            <div class="perfil-label">Nombre Completo</div>
            <div class="perfil-valor"><?php echo htmlspecialchars($datos_profesor['nombre']); ?></div>
        </div>
        
        <div class="perfil-item">
            <div class="perfil-label">Cédula</div>
            <div class="perfil-valor"><?php echo htmlspecialchars($datos_profesor['cedula'] ?? ''); ?></div>
        </div>
        
        <div class="perfil-item">
            <div class="perfil-label">Correo Electrónico</div>
            <div class="perfil-valor"><?php echo htmlspecialchars($datos_profesor['email']); ?></div>
        </div>
        
        <div class="perfil-item">
            <div class="perfil-label">Rol</div>
            <div class="perfil-valor">
                <span class="perfil-badge">Docente</span>
            </div>
        </div>
        
        <div class="perfil-item">
            <div class="perfil-label">Materias Asignadas</div>
            <div class="perfil-valor"><?php
                $nombres_cursos = array_map(function($mp) { return $mp['curso_nombre']; }, $materias_profesor);
                echo !empty($nombres_cursos) ? htmlspecialchars(implode(', ', $nombres_cursos)) : 'Ninguna';
            ?></div>
        </div>
        
        <div class="perfil-item">
            <div class="perfil-label">Miembro desde</div>
            <div class="perfil-valor"><?php echo date('d/m/Y', strtotime($datos_profesor['fecha_registro'])); ?></div>
        </div>
    </div>
</div>

<!-- Modal Monitoreo de Estudiante -->
<div class="modal-overlay" id="modalMonitoreo">
    <div class="modal-contenido" style="width:550px;">
        <button class="modal-cerrar" onclick="cerrarMonitoreo()">&times;</button>
        <h2 style="margin-top:0; color:#1a426e; border-bottom:2px solid #e2e8f0; padding-bottom:15px;">🔍 Monitoreo de Estudiante</h2>
        
        <div id="infoEstudiante" style="margin-bottom:20px;">
            <div style="display:flex; gap:20px; flex-wrap:wrap;">
                <div style="flex:1; min-width:200px;">
                    <div class="perfil-label">Nombre</div>
                    <div class="perfil-valor" id="monitorNombre" style="font-size:16px;"></div>
                </div>
                <div style="flex:1; min-width:200px;">
                    <div class="perfil-label">Correo</div>
                    <div class="perfil-valor" id="monitorEmail" style="font-size:16px;"></div>
                </div>
            </div>
        </div>

        <!-- Historial de cursos -->
        <div style="margin-bottom:20px;">
            <h4 style="color:#1a426e; margin:0 0 10px 0;">📜 Historial de Cursos</h4>
            <div id="historialCursos" style="font-size:14px; color:#334155; padding:10px; background:#f8fafc; border-radius:8px; min-height:40px;">
                Cargando historial...
            </div>
        </div>

        <!-- Notas del profesor -->
        <div style="margin-bottom:15px;">
            <h4 style="color:#1a426e; margin:0 0 10px 0;">📝 Notas del Profesor</h4>
            <textarea id="notasProfesor" rows="3" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:8px; box-sizing:border-box; font-family:Arial; font-size:14px; resize:vertical;"></textarea>
            <button onclick="guardarNotas()" style="background:#1a426e; color:white; border:none; padding:8px 16px; border-radius:6px; cursor:pointer; font-weight:bold; font-size:13px; margin-top:8px;">💾 Guardar Notas</button>
            <span id="notasStatus" style="font-size:13px; margin-left:10px; font-weight:bold;"></span>
        </div>

        <input type="hidden" id="inscripcionId" value="">
        <input type="hidden" id="usuarioId" value="">
    </div>
</div>

<script>
function togglePerfil() {
    const modal = document.getElementById('modalPerfil');
    modal.classList.toggle('mostrar');
}

function toggleCurso(materia) {
    const tabla = document.getElementById('tabla-' + materia);
    const flecha = document.getElementById('flecha-' + materia);
    if (tabla.style.display === 'none') {
        tabla.style.display = 'block';
        if (flecha) flecha.classList.add('abierto');
    } else {
        tabla.style.display = 'none';
        if (flecha) flecha.classList.remove('abierto');
    }
}

function crearSalaProfesor(materia) {
    if (!confirm('¿Crear una sala de pizarra para este curso? Se desactivará la sala anterior si existía.')) return;
    const xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_pizarra.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onreadystatechange = function() {
        if (xhr.readyState === 4 && xhr.status === 200) {
            try {
                const r = JSON.parse(xhr.responseText);
                if (r.ok) {
                    window.location.href = 'pizarra.php?sala=' + encodeURIComponent(r.codigo) + '&materia=' + encodeURIComponent(r.materia);
                } else {
                    msjJb(r.error || 'No se pudo crear la sala', 'error');
                }
            } catch (e) {
                msjJb('Error al crear la sala', 'error');
            }
        }
    };
    xhr.send('accion=crear_sala&materia=' + encodeURIComponent(materia));
}

function abrirMonitoreo(usuarioId, inscripcionId, nombre, email, notas) {
    document.getElementById('usuarioId').value = usuarioId;
    document.getElementById('inscripcionId').value = inscripcionId;
    document.getElementById('monitorNombre').innerText = nombre;
    document.getElementById('monitorEmail').innerText = email;
    document.getElementById('notasProfesor').value = notas || '';
    document.getElementById('historialCursos').innerHTML = 'Cargando historial...';
    document.getElementById('modalMonitoreo').classList.add('mostrar');
    cargarHistorial(usuarioId);
}

function cerrarMonitoreo() {
    document.getElementById('modalMonitoreo').classList.remove('mostrar');
}

function cargarHistorial(usuarioId) {
    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_historial.php?usuario_id=' + usuarioId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var cursos = JSON.parse(xhr.responseText);
            var html = '';
            if (cursos.length === 0) {
                html = '<p style="margin:0; color:#334155;">No tiene inscripciones registradas.</p>';
            } else {
                html = '<table style="width:100%; border-collapse:collapse; font-size:13px;">';
                html += '<tr style="background:#e2e8f0;"><th style="padding:8px; text-align:left;">Curso</th><th style="padding:8px; text-align:center;">Estado</th><th style="padding:8px; text-align:center;">Inscripción</th><th style="padding:8px; text-align:left;">Evaluación</th></tr>';
                for (var i = 0; i < cursos.length; i++) {
                    var c = cursos[i];
                    var badge = c.estado === 'completado'
                        ? '<span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:bold;">✅ Completado</span>'
                        : '<span style="background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-weight:bold;">📌 Activo</span>';
                    var evalBadge = c.notas
                        ? '<span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:bold; font-size:12px;">' + c.notas + '</span>'
                        : '<span style="color:#94a3b8; font-size:12px;">—</span>';
                    html += '<tr style="border-bottom:1px solid #e2e8f0;">';
                    html += '<td style="padding:8px;">' + c.materia_nombre + '</td>';
                    html += '<td style="padding:8px; text-align:center;">' + badge + '</td>';
                    html += '<td style="padding:8px; text-align:center; color:#334155;">' + new Date(c.fecha_inscripcion).toLocaleDateString('es-ES') + '</td>';
                    html += '<td style="padding:8px; font-size:12px;">' + evalBadge + '</td>';
                    html += '</tr>';
                }
                html += '</table>';
            }
            document.getElementById('historialCursos').innerHTML = html;
        }
    };
    xhr.send();
}

function guardarNotas() {
    var inscripcionId = document.getElementById('inscripcionId').value;
    var notas = document.getElementById('notasProfesor').value;
    var xhr = new XMLHttpRequest();
    xhr.open('POST', 'ajax_inscribir.php', true);
    xhr.setRequestHeader('Content-Type', 'application/x-www-form-urlencoded');
    xhr.onload = function() {
        var status = document.getElementById('notasStatus');
        if (xhr.responseText === 'ok') {
            status.innerText = '✅ Guardado';
            status.style.color = '#15803d';
        } else {
            status.innerText = '❌ Error';
            status.style.color = '#b91c1c';
        }
        setTimeout(function() { status.innerText = ''; }, 2500);
    };
    xhr.send('accion=guardar_notas&inscripcion_id=' + inscripcionId + '&notas=' + encodeURIComponent(notas));
}

// Cerrar modales al hacer clic fuera
document.getElementById('modalPerfil').addEventListener('click', function(e) {
    if (e.target === this) togglePerfil();
});
document.getElementById('modalMonitoreo').addEventListener('click', function(e) {
    if (e.target === this) cerrarMonitoreo();
});
</script>
</body>
</html>