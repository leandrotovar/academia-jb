<?php
session_start();
require_once 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] !== 'administrador') {
    die("<center><h2 style='color:red; font-family:Arial; margin-top:50px;'>⚠️ Acceso Denegado. Esta sección es exclusiva para el Administrador.</h2><a href='login.php'>Volver al Login</a></center>");
}

date_default_timezone_set('America/Caracas');
$mensaje = "";
$tipo_alerta = "";

// ---- PROCESAR REGISTRO DE PROFESOR ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_profe'])) {
    $nombre = trim($_POST['nombre']);
    $cedula = trim($_POST['cedula']);
    $email = trim($_POST['email']);
    $password_plana = trim($_POST['password']);
    $materias_asignadas = $_POST['materias'] ?? [];

    if (empty($nombre) || empty($email) || empty($password_plana)) {
        $mensaje = "⚠️ Todos los campos son obligatorios.";
        $tipo_alerta = "error";
    } else {
        $stmt_buscar = $conn->prepare("SELECT id FROM usuarios WHERE email = ?");
        $stmt_buscar->bind_param("s", $email);
        $stmt_buscar->execute();
        if ($stmt_buscar->get_result()->num_rows > 0) {
            $mensaje = "❌ El correo electrónico ya está registrado.";
            $tipo_alerta = "error";
        } else {
            $pass_hash = password_hash($password_plana, PASSWORD_DEFAULT);
            $stmt_i = $conn->prepare("INSERT INTO usuarios (nombre, cedula, email, password, rol) VALUES (?, ?, ?, ?, 'docente')");
            $stmt_i->bind_param("ssss", $nombre, $cedula, $email, $pass_hash);
            if ($stmt_i->execute()) {
                $nuevo_id = $stmt_i->insert_id;
                // Asignar materias seleccionadas
                if (!empty($materias_asignadas)) {
                    $stmt_m = $conn->prepare("INSERT IGNORE INTO profesor_materias (profesor_id, materia_clave) VALUES (?, ?)");
                    foreach ($materias_asignadas as $clave) {
                        $clave = trim($clave);
                        if ($clave) {
                            $stmt_m->bind_param("is", $nuevo_id, $clave);
                            $stmt_m->execute();
                        }
                    }
                    $stmt_m->close();
                }
                $mensaje = "✅ ¡Profesor registrado con éxito!";
                $tipo_alerta = "exito";
                $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador registró al docente: $nombre ($email)', CURDATE(), CURTIME())");
            } else {
                $mensaje = "❌ Error al registrar.";
                $tipo_alerta = "error";
            }
            $stmt_i->close();
        }
        $stmt_buscar->close();
    }
}

// ---- PROCESAR MODIFICAR PROFESOR ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['modificar_profe'])) {
    $profe_id = (int)$_POST['profe_id'];
    $nombre = trim($_POST['nombre']);
    $cedula = trim($_POST['cedula']);
    $email = trim($_POST['email']);
    $materias_asignadas = $_POST['materias'] ?? [];
    $password_nueva = trim($_POST['password']);

    if (!empty($password_nueva)) {
        $pass_hash = password_hash($password_nueva, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, cedula=?, email=?, password=? WHERE id=? AND rol='docente'");
        $stmt->bind_param("ssssi", $nombre, $cedula, $email, $pass_hash, $profe_id);
    } else {
        $stmt = $conn->prepare("UPDATE usuarios SET nombre=?, cedula=?, email=? WHERE id=? AND rol='docente'");
        $stmt->bind_param("sssi", $nombre, $cedula, $email, $profe_id);
    }
    if ($stmt->execute()) {
        // Reasignar materias: eliminar todas y luego insertar las seleccionadas
        $conn->query("DELETE FROM profesor_materias WHERE profesor_id = $profe_id");
        if (!empty($materias_asignadas)) {
            $stmt_m = $conn->prepare("INSERT IGNORE INTO profesor_materias (profesor_id, materia_clave) VALUES (?, ?)");
            foreach ($materias_asignadas as $clave) {
                $clave = trim($clave);
                if ($clave) {
                    $stmt_m->bind_param("is", $profe_id, $clave);
                    $stmt_m->execute();
                }
            }
            $stmt_m->close();
        }
        $mensaje = "✅ Profesor actualizado.";
        $tipo_alerta = "exito";
        $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador modificó al docente: $nombre ($email)', CURDATE(), CURTIME())");
    } else {
        $mensaje = "❌ Error al actualizar: " . $conn->error;
        $tipo_alerta = "error";
    }
    $stmt->close();
}

// ---- PROCESAR REGISTRAR CURSO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_curso'])) {
    $clave = trim($_POST['clave']);
    $nombre = trim($_POST['nombre']);
    if ($clave && $nombre) {
        $stmt = $conn->prepare("INSERT IGNORE INTO cursos (clave, nombre) VALUES (?, ?)");
        $stmt->bind_param("ss", $clave, $nombre);
        if ($stmt->execute() && $stmt->affected_rows > 0) {
            $mensaje = "✅ Curso '$nombre' creado.";
            $tipo_alerta = "exito";
            $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador creó el curso: $nombre ($clave)', CURDATE(), CURTIME())");
        } else {
            $mensaje = "❌ La clave ya existe o es inválida.";
            $tipo_alerta = "error";
        }
        $stmt->close();
    }
}

// ---- PROCESAR ELIMINAR CURSO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_curso'])) {
    $curso_id = (int)$_POST['curso_id'];
    $res_c = $conn->query("SELECT nombre FROM cursos WHERE id = $curso_id");
    if ($res_c && $row_c = $res_c->fetch_assoc()) {
        $nombre_curso = $row_c['nombre'];
        $clave_curso = $conn->query("SELECT clave FROM cursos WHERE id = $curso_id")->fetch_assoc()['clave'];
        $conn->query("DELETE FROM cursos WHERE id = $curso_id");
        $conn->query("DELETE FROM profesor_materias WHERE materia_clave = '$clave_curso'");
        $conn->query("DELETE FROM inscripciones WHERE materia = '$clave_curso'");
        $mensaje = "✅ Curso '$nombre_curso' eliminado.";
        $tipo_alerta = "exito";
        $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador eliminó el curso: $nombre_curso', CURDATE(), CURTIME())");
    }
}

// ---- PROCESAR BACKUP ----
if (isset($_GET['backup'])) {
    $backup_file = "backup_academia_jb_" . date("Y-m-d_H-i-s") . ".sql";
    $sql = "-- Backup generado el " . date("Y-m-d H:i:s") . "\n\n";
    $tables = $conn->query("SHOW TABLES");
    if ($tables && $tables->num_rows > 0) {
        while ($row = $tables->fetch_array()) {
            $table = $row[0];
            $create = $conn->query("SHOW CREATE TABLE `$table`")->fetch_array();
            $sql .= "DROP TABLE IF EXISTS `$table`;\n";
            $sql .= $create[1] . ";\n\n";
            $data = $conn->query("SELECT * FROM `$table`");
            if ($data && $data->num_rows > 0) {
                $sql .= "INSERT INTO `$table` VALUES\n";
                $rows = [];
                while ($fila = $data->fetch_row()) {
                    $values = array_map(function ($v) use ($conn) {
                        return $v === null ? "NULL" : "'" . $conn->real_escape_string($v) . "'";
                    }, $fila);
                    $rows[] = "(" . implode(", ", $values) . ")";
                }
                $sql .= implode(",\n", $rows) . ";\n\n";
            }
        }
    }
    if (file_put_contents($backup_file, $sql)) {
        $mensaje = "✅ Backup creado: <a href='$backup_file' download style='color:#1a426e;'>$backup_file</a>";
        $tipo_alerta = "exito";
    } else {
        $mensaje = "❌ Error al crear backup. Verifica los permisos de escritura.";
        $tipo_alerta = "error";
    }
}

// ---- PROCESAR RESTAURAR ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES['sql_file']) && $_FILES['sql_file']['error'] === UPLOAD_ERR_OK) {
    $tmp_name = $_FILES['sql_file']['tmp_name'];
    $content = file_get_contents($tmp_name);
    if ($content) {
        $queries = explode(";\n", $content);
        $ok = true;
        foreach ($queries as $q) {
            $q = trim($q);
            if (!empty($q) && stripos($q, 'INSERT INTO') !== 0 && stripos($q, 'CREATE TABLE') !== 0 && stripos($q, 'ALTER TABLE') !== 0 && stripos($q, 'DROP TABLE') !== 0 && stripos($q, '--') !== 0) {
                continue;
            }
            if (!empty($q) && $conn->query($q) === false && stripos($q, '--') !== 0) {
                $ok = false;
            }
        }
        if ($ok) {
            $mensaje = "✅ Base de datos restaurada correctamente.";
            $tipo_alerta = "exito";
        } else {
            $mensaje = "⚠️ Restauración completada con algunos errores (posiblemente datos duplicados).";
            $tipo_alerta = "exito";
        }
    } else {
        $mensaje = "❌ No se pudo leer el archivo.";
        $tipo_alerta = "error";
    }
}

// ---- PROCESAR REGISTRAR EVENTO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_evento'])) {
    $titulo = trim($_POST['titulo']);
    $descripcion = trim($_POST['descripcion']);
    $fecha_inicio = trim($_POST['fecha_inicio']);
    $fecha_fin = !empty($_POST['fecha_fin']) ? trim($_POST['fecha_fin']) : null;
    $tipo = $_POST['tipo'] ?? 'otro';
    $icono = $_POST['icono'] ?? '📌';
    if ($titulo && $fecha_inicio) {
        $stmt = $conn->prepare("INSERT INTO eventos (titulo, descripcion, fecha_inicio, fecha_fin, tipo, icono) VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->bind_param("ssssss", $titulo, $descripcion, $fecha_inicio, $fecha_fin, $tipo, $icono);
        if ($stmt->execute()) {
            $mensaje = "✅ Evento '$titulo' creado.";
            $tipo_alerta = "exito";
            $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador creó el evento: $titulo', CURDATE(), CURTIME())");
        } else {
            $mensaje = "❌ Error al crear evento.";
            $tipo_alerta = "error";
        }
        $stmt->close();
    }
}

// ---- PROCESAR ELIMINAR EVENTO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_evento'])) {
    $evento_id = (int)$_POST['evento_id'];
    $res_e = $conn->query("SELECT titulo FROM eventos WHERE id = $evento_id");
    if ($res_e && $row_e = $res_e->fetch_assoc()) {
        $titulo = $row_e['titulo'];
        $conn->query("DELETE FROM eventos WHERE id = $evento_id");
        $mensaje = "✅ Evento '$titulo' eliminado.";
        $tipo_alerta = "exito";
        $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador eliminó el evento: $titulo', CURDATE(), CURTIME())");
    }
}

// ---- PROCESAR REGISTRAR TERMINO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['registrar_termino'])) {
    $termino = trim($_POST['termino']);
    $definicion = trim($_POST['definicion']);
    $materia = trim($_POST['materia']);
    if ($termino && $definicion) {
        $materia = $materia ?: null;
        $stmt = $conn->prepare("INSERT INTO terminos (termino, definicion, materia) VALUES (?, ?, ?)");
        $stmt->bind_param("sss", $termino, $definicion, $materia);
        if ($stmt->execute()) {
            $mensaje = "✅ Término '$termino' creado.";
            $tipo_alerta = "exito";
            $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador creó el término: $termino', CURDATE(), CURTIME())");
        } else {
            $mensaje = "❌ Error al crear término.";
            $tipo_alerta = "error";
        }
        $stmt->close();
    }
}

// ---- PROCESAR ELIMINAR TERMINO ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['eliminar_termino'])) {
    $termino_id = (int)$_POST['termino_id'];
    $res_t = $conn->query("SELECT termino FROM terminos WHERE id = $termino_id");
    if ($res_t && $row_t = $res_t->fetch_assoc()) {
        $termino = $row_t['termino'];
        $conn->query("DELETE FROM terminos WHERE id = $termino_id");
        $mensaje = "✅ Término '$termino' eliminado.";
        $tipo_alerta = "exito";
        $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador eliminó el término: $termino', CURDATE(), CURTIME())");
    }
}

// ---- PROCESAR ACTUALIZAR MATERIALES ----
if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_POST['actualizar_materiales'])) {
    $curso_id = (int)$_POST['curso_id'];
    $clave = trim($_POST['clave']);
    $video_url = trim($_POST['video_url']);
    $texto_leccion = trim($_POST['texto_leccion']);
    
    $pdf_path = null;
    if (isset($_FILES['pdf_file']) && $_FILES['pdf_file']['error'] === UPLOAD_ERR_OK) {
        $ext = strtolower(pathinfo($_FILES['pdf_file']['name'], PATHINFO_EXTENSION));
        if ($ext === 'pdf') {
            $nombre_archivo = 'Guia_Estudio_' . $clave . '.pdf';
            $destino = 'uploads/pdf/' . $nombre_archivo;
            move_uploaded_file($_FILES['pdf_file']['tmp_name'], $destino);
            $pdf_path = $destino;
        }
    }

    if ($pdf_path) {
        $stmt = $conn->prepare("UPDATE cursos SET video_url = ?, texto_leccion = ?, pdf_path = ? WHERE id = ?");
        $stmt->bind_param("sssi", $video_url, $texto_leccion, $pdf_path, $curso_id);
    } else {
        $stmt = $conn->prepare("UPDATE cursos SET video_url = ?, texto_leccion = ? WHERE id = ?");
        $stmt->bind_param("ssi", $video_url, $texto_leccion, $curso_id);
    }
    if ($stmt->execute()) {
        $mensaje = "✅ Materiales del curso actualizados.";
        $tipo_alerta = "exito";
        $conn->query("INSERT INTO bitacora (actividad, fecha, hora) VALUES ('El Administrador actualizó materiales del curso ID $curso_id', CURDATE(), CURTIME())");
    } else {
        $mensaje = "❌ Error al actualizar materiales.";
        $tipo_alerta = "error";
    }
    $stmt->close();
}

// ---- CONSULTAS ----
$res_profesores = $conn->query("SELECT * FROM usuarios WHERE rol = 'docente' ORDER BY nombre ASC");
// Obtener materias de cada profesor desde la tabla junction
$prof_materias = [];
$res_pm = $conn->query("SELECT pm.profesor_id, c.nombre AS curso_nombre FROM profesor_materias pm JOIN cursos c ON pm.materia_clave = c.clave");
while ($rpm = $res_pm->fetch_assoc()) {
    $prof_materias[$rpm['profesor_id']][] = $rpm['curso_nombre'];
}
$res_eventos = $conn->query("SELECT * FROM eventos ORDER BY fecha_inicio ASC");
$res_terminos = $conn->query("SELECT t.*, COALESCE(m.nombre, t.materia) AS materia_nombre FROM terminos t LEFT JOIN cursos m ON t.materia = m.clave WHERE t.activo=1 ORDER BY t.termino ASC");
$res_terminos_admin = $conn->query("SELECT * FROM terminos ORDER BY termino ASC");
$res_bitacora = $conn->query("SELECT * FROM bitacora ORDER BY id DESC LIMIT 100");
$res_cursos = $conn->query("SELECT * FROM cursos ORDER BY nombre ASC");
$materias_map = [];
$res_m = $conn->query("SELECT clave, nombre FROM cursos");
while ($rm = $res_m->fetch_assoc()) $materias_map[$rm['clave']] = $rm['nombre'];

// ---- ESTUDIANTES: consultar todos con sus inscripciones ----
$res_estudiantes = $conn->query("
    SELECT u.id, u.nombre, u.cedula, u.email, u.fuente_grande, u.temporizador_visual, u.fecha_registro,
           (SELECT COUNT(*) FROM inscripciones WHERE usuario_id = u.id AND estado = 'activo') as cursos_activos,
           (SELECT COUNT(*) FROM inscripciones WHERE usuario_id = u.id AND estado = 'completado') as cursos_completados
    FROM usuarios u WHERE u.rol = 'estudiante' ORDER BY u.nombre ASC
");
?>
<!DOCTYPE html>
<html lang="es">
<head>
    <meta charset="UTF-8">
    <title>Panel de Administración - Academia JB</title>
    <style>
        * { box-sizing: border-box; }
        body { font-family: 'Arial', sans-serif; margin: 0; padding: 0; background-color: #f0f4f8; color: #1a3557; }
        .navbar { background-color: #1a426e; color: white; padding: 15px 30px; display: flex; justify-content: space-between; align-items: center; box-shadow: 0 4px 6px rgba(0,0,0,0.1); }
        .navbar a { color: white; text-decoration: none; font-weight: bold; background: #f44336; padding: 6px 12px; border-radius: 6px; font-size: 13px; }
        .contenedor { max-width: 1200px; margin: 30px auto; padding: 0 20px; display: flex; flex-direction: column; gap: 25px; }

        h3 { margin-top: 0; color: #1a426e; border-bottom: 2px solid #e2e8f0; padding-bottom: 10px; }
        label { display: block; margin-top: 12px; font-weight: bold; font-size: 14px; }
        input, select { width: 100%; padding: 10px; margin-top: 5px; border: 1px solid #cbd5e1; border-radius: 6px; box-sizing: border-box; }
        button { border: none; padding: 12px; border-radius: 6px; cursor: pointer; font-weight: bold; font-size: 15px; }

        .alerta { padding: 10px; border-radius: 6px; font-size: 13px; font-weight: bold; margin-bottom: 15px; text-align: center; }
        .error { background: #FEE2E2; color: #991B1B; border: 1px solid #FCA5A5; }
        .exito { background: #D1FAE5; color: #065F46; border: 1px solid #A7F3D0; }

        table { width: 100%; border-collapse: collapse; margin-top: 10px; font-size: 14px; }
        th, td { padding: 12px; border-bottom: 1px solid #e2e8f0; text-align: left; }
        th { background-color: #f8fafc; color: #1a426e; font-weight: bold; }
        tr:hover { background-color: #f1f5f9; }

        /* ---- MENÚ DE 4 BOTONES ---- */
        .grid-menu {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
            gap: 20px;
        }
        .menu-card {
            background: white;
            padding: 30px 20px;
            border-radius: 16px;
            text-align: center;
            cursor: pointer;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
            border: 3px solid transparent;
            border-bottom: 5px solid #1a426e;
            transition: transform 0.2s, border-color 0.2s, box-shadow 0.2s;
        }
        .menu-card:hover {
            transform: translateY(-4px);
            border-color: #1a426e;
            box-shadow: 0 8px 20px rgba(26,66,110,0.15);
        }
        .menu-card .icono { font-size: 48px; margin-bottom: 10px; }
        .menu-card .titulo { font-size: 18px; font-weight: bold; color: #1a426e; }
        .menu-card .desc { font-size: 13px; color: #334155; margin-top: 5px; }

        /* ---- SECCIONES CONTENIDO ---- */
        .seccion {
            display: none;
            background: white;
            padding: 25px;
            border-radius: 12px;
            box-shadow: 0 4px 10px rgba(0,0,0,0.05);
        }
        .seccion.activa { display: block; }

        .btn-accion {
            background: #1a426e; color: white; padding: 8px 16px; border-radius: 6px;
            font-size: 13px; font-weight: bold; cursor: pointer; display: inline-block; text-decoration: none;
        }
        .btn-accion:hover { background: #123052; }
        .btn-peligro { background: #f44336; }
        .btn-peligro:hover { background: #d32f2f; }
        .btn-warning { background: #FF9800; }
        .btn-warning:hover { background: #e68900; }
        .btn-exito { background: #4CAF50; }
        .btn-exito:hover { background: #45a049; }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        .doble-col { display: flex; gap: 25px; flex-wrap: wrap; }
        .doble-col > div { flex: 1; min-width: 300px; }

        /* Modal */
        .modal-overlay {
            position: fixed; top: 0; left: 0; width: 100%; height: 100%;
            background: rgba(0,0,0,0.5); z-index: 1000;
            display: none; justify-content: center; align-items: center;
        }
        .modal-overlay.mostrar { display: flex; }
        .modal-contenido {
            background: white; padding: 35px; border-radius: 16px;
            width: 440px; max-width: 90%;
            box-shadow: 0 20px 50px rgba(0,0,0,0.2);
            position: relative; animation: slideUp 0.3s ease;
        }
        @keyframes slideUp {
            from { opacity: 0; transform: translateY(30px); }
            to { opacity: 1; transform: translateY(0); }
        }
        .modal-contenido h3 { margin-top: 0; }
        .modal-cerrar {
            position: absolute; top: 15px; right: 20px;
            font-size: 24px; cursor: pointer; color: #555;
            background: none; border: none; font-weight: bold;
        }
        .modal-cerrar:hover { color: #f44336; }

        .backup-info {
            background: #f8fafc; padding: 20px; border-radius: 10px;
            border: 2px dashed #cbd5e1; text-align: center;
        }

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
    <div style="font-weight: bold; font-size: 20px;">🛡️ Academia JB - Panel del Administrador Supremo</div>
    <div>
        <span style="margin-right: 15px; font-weight: bold;">Modo: administrador</span>
        <a href="logout.php">Cerrar Sesión</a>
    </div>
</nav>

<div class="contenedor">
    <div style="margin-bottom: 5px; font-size: 14px; color: #334155;">
        <a href="administrador.php" style="color: #1a426e; text-decoration: none;">Inicio</a>
        <span style="color: #94a3b8; margin: 0 8px;">›</span>
        <span style="color: #334155; font-weight: bold;">Panel Administrador</span>
    </div>

    <?php if (!empty($mensaje)): ?>
        <div class="alerta <?php echo $tipo_alerta; ?>"><?php echo $mensaje; ?></div>
    <?php endif; ?>

    <!-- MENÚ DE 4 BOTONES -->
    <div class="grid-menu">
        <div class="menu-card" onclick="mostrarSeccion('profesores')">
            <div class="icono">👨‍🏫</div>
            <div class="titulo">Profesores</div>
            <div class="desc">Registrar, modificar y gestionar docentes</div>
        </div>
        <div class="menu-card" onclick="mostrarSeccion('estudiantes')">
            <div class="icono">👨‍🎓</div>
            <div class="titulo">Estudiantes</div>
            <div class="desc">Ver alumnos y sus inscripciones</div>
        </div>
        <div class="menu-card" onclick="mostrarSeccion('cursos')">
            <div class="icono">📚</div>
            <div class="titulo">Cursos</div>
            <div class="desc">Crear y eliminar materias académicas</div>
        </div>
        <div class="menu-card" onclick="mostrarSeccion('eventos')">
            <div class="icono">📅</div>
            <div class="titulo">Eventos</div>
            <div class="desc">Gestionar eventos del calendario académico</div>
        </div>
        <div class="menu-card" onclick="mostrarSeccion('glosario')">
            <div class="icono">📖</div>
            <div class="titulo">Glosario</div>
            <div class="desc">Administrar términos y definiciones técnicas</div>
        </div>
        <div class="menu-card" onclick="mostrarSeccion('mantenimiento')">
            <div class="icono">🔧</div>
            <div class="titulo">Mantenimiento</div>
            <div class="desc">Bitácora, respaldo y restauración de BD</div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN PROFESORES -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-profesores">
        <h3>👨‍🏫 Gestión de Profesores</h3>
        <div class="doble-col">
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">➕ Registrar Nuevo Profesor</h4>
                <form action="administrador.php" method="POST" style="background:#f8fafc; padding:20px; border-radius:10px;">
                    <input type="hidden" name="registrar_profe" value="1">
                    <label>Nombre Completo:</label>
                    <input type="text" name="nombre" placeholder="Ej. Prof. Carlos Mendoza" required>
                    <label>Cédula:</label>
                    <input type="text" name="cedula" placeholder="Ej: V-12345678" required>
                    <label>Correo Electrónico:</label>
                    <input type="email" name="email" placeholder="carlos@academiajb.com" required>
                    <label>Materias Asignadas:</label>
                    <div style="display:flex; flex-wrap:wrap; gap:8px; margin-top:5px;">
                        <?php foreach ($materias_map as $clave => $nombre): ?>
                            <label style="display:flex; align-items:center; gap:4px; background:#e2e8f0; padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:normal; font-size:14px;">
                                <input type="checkbox" name="materias[]" value="<?php echo $clave; ?>">
                                <?php echo htmlspecialchars($nombre); ?>
                            </label>
                        <?php endforeach; ?>
                    </div>
                    <label>Contraseña:</label>
                    <input type="password" name="password" placeholder="Crear contraseña" required>
                    <button type="submit" style="background:#1a426e; color:white; width:100%; margin-top:15px;">Dar de Alta</button>
                </form>
            </div>
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">📋 Profesores Registrados</h4>
                <input type="text" id="buscarProfesor" placeholder="🔍 Buscar por cédula..." onkeyup="filtrarCedula('buscarProfesor', 'tablaProfesores', 2)" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-bottom:10px; box-sizing:border-box;">
                <table id="tablaProfesores">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Nombre</th>
                            <th>Cédula</th>
                            <th>Correo</th>
                            <th>Materia</th>
                            <th style="text-align:center;">Acción</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_profesores && $res_profesores->num_rows > 0): ?>
                            <?php while($p = $res_profesores->fetch_assoc()): 
                                $cursos_prof = $prof_materias[$p['id']] ?? [];
                                $cursos_str = !empty($cursos_prof) ? implode(', ', $cursos_prof) : '<span style="color:#94a3b8;">No asignada</span>';
                                // Obtener las claves de materias para pasar al modal
                                $claves_prof = [];
                                $res_claves = $conn->query("SELECT materia_clave FROM profesor_materias WHERE profesor_id = " . $p['id']);
                                while ($rc = $res_claves->fetch_assoc()) $claves_prof[] = $rc['materia_clave'];
                                $claves_json = htmlspecialchars(json_encode($claves_prof), ENT_QUOTES, 'UTF-8');
                            ?>
                                <tr>
                                    <td>#<?php echo $p['id']; ?></td>
                                    <td><strong><?php echo htmlspecialchars($p['nombre']); ?></strong></td>
                                    <td><?php echo htmlspecialchars($p['cedula'] ?? ''); ?></td>
                                    <td><?php echo htmlspecialchars($p['email']); ?></td>
                                    <td><?php echo $cursos_str; ?></td>
                                    <td style="text-align:center;">
                                        <button class="btn-accion btn-warning btn-sm" onclick="abrirModal(<?php echo $p['id']; ?>, '<?php echo htmlspecialchars($p['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['cedula'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($p['email'], ENT_QUOTES); ?>', <?php echo $claves_json; ?>)">✏️ Modificar</button>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; color:#555;">No hay profesores registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN ESTUDIANTES -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-estudiantes">
        <h3>👨‍🎓 Gestión de Estudiantes</h3>
        <p style="font-size:14px; color:#334155; margin:-5px 0 15px 0;">Lista de todos los estudiantes registrados y sus estadísticas de cursos.</p>
        <input type="text" id="buscarEstudiante" placeholder="🔍 Buscar por cédula..." onkeyup="filtrarCedula('buscarEstudiante', 'tablaEstudiantes', 3)" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-bottom:10px; box-sizing:border-box;">
        <table id="tablaEstudiantes">
            <thead>
                <tr>
                    <th>ID</th>
                    <th>Nombre</th>
                    <th>Cédula</th>
                    <th>Correo</th>
                    <th style="text-align:center;">Cursos Activos</th>
                    <th style="text-align:center;">Cursos Completados</th>
                    <th style="text-align:center;">Letra Grande</th>
                    <th style="text-align:center;">Temporizador</th>
                    <th style="text-align:center;">Acción</th>
                </tr>
            </thead>
            <tbody>
                <?php if ($res_estudiantes && $res_estudiantes->num_rows > 0): ?>
                    <?php while($e = $res_estudiantes->fetch_assoc()): ?>
                        <tr>
                            <td>#<?php echo $e['id']; ?></td>
                            <td><strong><?php echo htmlspecialchars($e['nombre']); ?></strong></td>
                            <td><?php echo htmlspecialchars($e['cedula'] ?? ''); ?></td>
                            <td><?php echo htmlspecialchars($e['email']); ?></td>
                            <td style="text-align:center;">
                                <span style="background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-weight:bold;"><?php echo $e['cursos_activos']; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:bold;"><?php echo $e['cursos_completados']; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge <?php echo $e['fuente_grande'] ? 'badge-activo' : 'badge-inactivo'; ?>"><?php echo $e['fuente_grande'] ? 'SÍ' : 'NO'; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <span class="badge <?php echo $e['temporizador_visual'] ? 'badge-activo' : 'badge-inactivo'; ?>"><?php echo $e['temporizador_visual'] ? 'SÍ' : 'NO'; ?></span>
                            </td>
                            <td style="text-align:center;">
                                <button class="btn-accion btn-sm" onclick="verHistorial(<?php echo $e['id']; ?>, '<?php echo htmlspecialchars($e['nombre'], ENT_QUOTES); ?>')">🔍 Ver Cursos</button>
                            </td>
                        </tr>
                    <?php endwhile; ?>
                <?php else: ?>
                    <tr><td colspan="9" style="text-align:center; color:#555;">No hay estudiantes registrados.</td></tr>
                <?php endif; ?>
            </tbody>
        </table>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN CURSOS -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-cursos">
        <h3>📚 Gestión de Cursos</h3>
        <div class="doble-col">
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">➕ Registrar Nuevo Curso</h4>
                <form action="administrador.php" method="POST" style="background:#f8fafc; padding:20px; border-radius:10px;">
                    <input type="hidden" name="registrar_curso" value="1">
                    <label>Clave del Curso <span style="font-weight:normal; color:#555;">(identificador único, ej: biologia)</span>:</label>
                    <input type="text" name="clave" placeholder="ej: biologia" required pattern="[a-z]+">
                    <label>Nombre del Curso <span style="font-weight:normal; color:#555;">(ej: Biología General)</span>:</label>
                    <input type="text" name="nombre" placeholder="ej: Biología General" required>
                    <button type="submit" style="background:#4CAF50; color:white; width:100%; margin-top:15px;">➕ Crear Curso</button>
                </form>
            </div>
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">🗑️ Cursos Existentes</h4>
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>Clave</th>
                            <th>Nombre</th>
                            <th>Video</th>
                            <th>PDF</th>
                            <th style="text-align:center;">Acciones</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if ($res_cursos && $res_cursos->num_rows > 0): ?>
                            <?php while($c = $res_cursos->fetch_assoc()): ?>
                                <tr>
                                    <td>#<?php echo $c['id']; ?></td>
                                    <td><code><?php echo htmlspecialchars($c['clave']); ?></code></td>
                                    <td><strong><?php echo htmlspecialchars($c['nombre']); ?></strong></td>
                                    <td style="font-size:12px; max-width:150px; overflow:hidden; text-overflow:ellipsis;">
                                        <?php if ($c['video_url']): ?>
                                            <a href="<?php echo htmlspecialchars($c['video_url']); ?>" target="_blank" style="color:#1a426e;">🎬 Ver</a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="font-size:12px;">
                                        <?php if ($c['pdf_path']): ?>
                                            <a href="<?php echo htmlspecialchars($c['pdf_path']); ?>" target="_blank" style="color:#1a426e;">📄 Ver</a>
                                        <?php else: ?>
                                            <span style="color:#94a3b8;">—</span>
                                        <?php endif; ?>
                                    </td>
                                    <td style="text-align:center;">
                                        <button class="btn-accion btn-sm" onclick="abrirMateriales(<?php echo $c['id']; ?>, '<?php echo htmlspecialchars($c['clave'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['nombre'], ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['video_url'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['pdf_path'] ?? '', ENT_QUOTES); ?>', '<?php echo htmlspecialchars($c['texto_leccion'] ?? '', ENT_QUOTES); ?>')" style="margin-right:4px;">📦 Materiales</button>
                                        <form action="administrador.php" method="POST" onsubmit="return confirm('¿Eliminar el curso «<?php echo htmlspecialchars($c['nombre']); ?>»? Se eliminarán las inscripciones relacionadas.')" style="display:inline;">
                                            <input type="hidden" name="eliminar_curso" value="1">
                                            <input type="hidden" name="curso_id" value="<?php echo $c['id']; ?>">
                                            <button type="submit" class="btn-accion btn-peligro btn-sm">🗑️</button>
                                        </form>
                                    </td>
                                </tr>
                            <?php endwhile; ?>
                        <?php else: ?>
                            <tr><td colspan="6" style="text-align:center; color:#555;">No hay cursos registrados.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN EVENTOS -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-eventos">
        <h3>📅 Gestión de Eventos del Calendario</h3>
        <div class="doble-col">
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">➕ Nuevo Evento</h4>
                <form action="administrador.php" method="POST" style="background:#f8fafc; padding:20px; border-radius:10px;">
                    <input type="hidden" name="registrar_evento" value="1">
                    <label>Título:</label>
                    <input type="text" name="titulo" required>
                    <label>Descripción:</label>
                    <textarea name="descripcion" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; font-family:Arial;" rows="3"></textarea>
                    <label>Fecha de Inicio:</label>
                    <input type="date" name="fecha_inicio" required>
                    <label>Fecha de Fin <span style="font-weight:normal; color:#555;">(opcional)</span>:</label>
                    <input type="date" name="fecha_fin">
                    <div style="display:flex; gap:15px;">
                        <div style="flex:1;">
                            <label>Tipo:</label>
                            <select name="tipo" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                                <option value="inscripcion">📝 Inscripción</option>
                                <option value="clase">🚀 Clase</option>
                                <option value="entrega">📂 Entrega</option>
                                <option value="otro">📌 Otro</option>
                            </select>
                        </div>
                        <div style="flex:1;">
                            <label>Icono:</label>
                            <select name="icono" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                                <option value="📝">📝</option>
                                <option value="🚀">🚀</option>
                                <option value="📂">📂</option>
                                <option value="📌">📌</option>
                                <option value="🎓">🎓</option>
                                <option value="📢">📢</option>
                                <option value="✅">✅</option>
                                <option value="⭐">⭐</option>
                            </select>
                        </div>
                    </div>
                    <button type="submit" style="background:#1a426e; color:white; width:100%; margin-top:15px;">Crear Evento</button>
                </form>
            </div>
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">📋 Eventos Registrados</h4>
                <div style="max-height:400px; overflow-y:auto;">
                    <table>
                        <thead><tr><th>Título</th><th>Fecha</th><th>Tipo</th><th style="text-align:center;">Acción</th></tr></thead>
                        <tbody>
                            <?php if ($res_eventos && $res_eventos->num_rows > 0): ?>
                                <?php while ($ev = $res_eventos->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($ev['icono'] . ' ' . $ev['titulo']); ?></td>
                                        <td style="font-size:12px;"><?php echo $ev['fecha_inicio']; if ($ev['fecha_fin']) echo ' → '.$ev['fecha_fin']; ?></td>
                                        <td><span class="badge" style="background:#e2e8f0; color:#1a426e;"><?php echo $ev['tipo']; ?></span></td>
                                        <td style="text-align:center;">
                                            <form action="administrador.php" method="POST" onsubmit="return confirm('¿Eliminar evento?');">
                                                <input type="hidden" name="eliminar_evento" value="1">
                                                <input type="hidden" name="evento_id" value="<?php echo $ev['id']; ?>">
                                                <button type="submit" class="btn-accion btn-peligro btn-sm">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#555;">No hay eventos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN GLOSARIO -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-glosario">
        <h3>📖 Gestión del Glosario de Términos</h3>
        <div class="doble-col">
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">➕ Nuevo Término</h4>
                <form action="administrador.php" method="POST" style="background:#f8fafc; padding:20px; border-radius:10px;">
                    <input type="hidden" name="registrar_termino" value="1">
                    <label>Término:</label>
                    <input type="text" name="termino" required>
                    <label>Definición:</label>
                    <textarea name="definicion" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; margin-top:5px; font-family:Arial;" rows="4" required></textarea>
                    <label>Materia <span style="font-weight:normal; color:#555;">(opcional)</span>:</label>
                    <select name="materia" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px;">
                        <option value="">— Sin materia —</option>
                        <?php foreach ($materias_map as $clave => $nombre): ?>
                            <option value="<?php echo htmlspecialchars($clave); ?>"><?php echo htmlspecialchars($nombre); ?></option>
                        <?php endforeach; ?>
                    </select>
                    <button type="submit" style="background:#1a426e; color:white; width:100%; margin-top:15px;">Agregar Término</button>
                </form>
            </div>
            <div>
                <h4 style="color:#1a426e; margin:0 0 15px 0;">📋 Términos Registrados</h4>
                <div style="max-height:400px; overflow-y:auto;">
                    <table>
                        <thead><tr><th>Término</th><th>Materia</th><th style="text-align:center;">Acción</th></tr></thead>
                        <tbody>
                            <?php if ($res_terminos_admin && $res_terminos_admin->num_rows > 0): ?>
                                <?php while ($tr = $res_terminos_admin->fetch_assoc()): ?>
                                    <tr>
                                        <td><?php echo htmlspecialchars($tr['termino']); ?></td>
                                        <td><?php echo htmlspecialchars($tr['materia'] ?? '—'); ?></td>
                                        <td style="text-align:center;">
                                            <form action="administrador.php" method="POST" onsubmit="return confirm('¿Eliminar término?');">
                                                <input type="hidden" name="eliminar_termino" value="1">
                                                <input type="hidden" name="termino_id" value="<?php echo $tr['id']; ?>">
                                                <button type="submit" class="btn-accion btn-peligro btn-sm">🗑️</button>
                                            </form>
                                        </td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="3" style="text-align:center; color:#555;">No hay términos registrados.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <!-- ============================================ -->
    <!-- SECCIÓN MANTENIMIENTO -->
    <!-- ============================================ -->
    <div class="seccion" id="sec-mantenimiento">
        <h3>🔧 Mantenimiento del Sistema</h3>
        <div style="display:flex; gap:25px; flex-wrap:wrap;">
            <!-- Backup -->
            <div style="flex:1; min-width:300px; background:#f8fafc; padding:20px; border-radius:12px;">
                <h4 style="color:#1a426e; margin:0 0 15px 0;">💾 Respaldo de Base de Datos</h4>
                <div class="backup-info">
                    <p style="font-size:40px; margin:0;">💾</p>
                    <p style="font-weight:bold; color:#1a426e;">Descargar copia de seguridad</p>
                    <p style="font-size:13px; color:#334155;">Genera un archivo .sql con toda la base de datos.</p>
                    <a href="administrador.php?backup=1" class="btn-accion btn-exito" style="margin-top:10px;">📥 Generar Backup</a>
                </div>
            </div>

            <!-- Restaurar -->
            <div style="flex:1; min-width:300px; background:#f8fafc; padding:20px; border-radius:12px;">
                <h4 style="color:#1a426e; margin:0 0 15px 0;">📂 Restaurar Base de Datos</h4>
                <form action="administrador.php" method="POST" enctype="multipart/form-data" style="border:2px dashed #cbd5e1; padding:20px; border-radius:10px; text-align:center;">
                    <p style="font-size:40px; margin:0;">📂</p>
                    <p style="font-weight:bold; color:#1a426e;">Subir archivo .sql</p>
                    <input type="file" name="sql_file" accept=".sql" required style="margin:10px 0;">
                    <button type="submit" class="btn-accion btn-warning" style="width:100%;">🔄 Restaurar</button>
                </form>
            </div>

            <!-- Bitácora -->
            <div style="flex:1; min-width:300px; background:#f8fafc; padding:20px; border-radius:12px;">
                <h4 style="color:#1a426e; margin:0 0 15px 0;">📑 Bitácora del Sistema</h4>
                <p style="font-size:13px; color:#334155; margin:-5px 0 10px 0;">Últimas 100 acciones registradas.</p>
                <div style="max-height:300px; overflow-y:auto;">
                    <table>
                        <thead>
                            <tr>
                                <th>ID</th>
                                <th>Actividad</th>
                                <th>Fecha</th>
                                <th>Hora</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if ($res_bitacora && $res_bitacora->num_rows > 0): ?>
                                <?php while($b = $res_bitacora->fetch_assoc()): ?>
                                    <tr>
                                        <td>#<?php echo $b['id']; ?></td>
                                        <td style="font-size:13px;"><?php echo htmlspecialchars($b['actividad']); ?></td>
                                        <td style="font-size:13px;"><?php echo $b['fecha']; ?></td>
                                        <td style="font-size:13px; color:#FF9800; font-weight:bold;"><?php echo $b['hora']; ?></td>
                                    </tr>
                                <?php endwhile; ?>
                            <?php else: ?>
                                <tr><td colspan="4" style="text-align:center; color:#555;">Sin actividad registrada.</td></tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

</div>

<!-- Modal Modificar Profesor -->
<div class="modal-overlay" id="modalModificar">
    <div class="modal-contenido">
        <button class="modal-cerrar" onclick="cerrarModal()">&times;</button>
        <h3>✏️ Modificar Profesor</h3>
        <form action="administrador.php" method="POST">
            <input type="hidden" name="modificar_profe" value="1">
            <input type="hidden" name="profe_id" id="modalProfeId" value="">
            <label>Nombre Completo:</label>
            <input type="text" name="nombre" id="modalNombre" required>
            <label>Cédula:</label>
            <input type="text" name="cedula" id="modalCedula" required>
            <label>Correo Electrónico:</label>
            <input type="email" name="email" id="modalEmail" required>
            <label>Materias Asignadas:</label>
            <div id="modalMaterias" style="display:flex; flex-wrap:wrap; gap:8px; margin-top:5px;">
                <?php foreach ($materias_map as $clave => $nombre): ?>
                    <label style="display:flex; align-items:center; gap:4px; background:#e2e8f0; padding:6px 12px; border-radius:6px; cursor:pointer; font-weight:normal; font-size:14px;">
                        <input type="checkbox" name="materias[]" value="<?php echo $clave; ?>">
                        <?php echo htmlspecialchars($nombre); ?>
                    </label>
                <?php endforeach; ?>
            </div>
            <label>Nueva Contraseña <span style="font-weight:normal; color:#555;">(vacío = mantener actual)</span>:</label>
            <input type="password" name="password" placeholder="••••••••">
            <button type="submit" style="background:#1a426e; color:white; width:100%; margin-top:15px;">💾 Guardar Cambios</button>
        </form>
    </div>
</div>

<!-- Modal Materiales del Curso -->
<div class="modal-overlay" id="modalMateriales">
    <div class="modal-contenido" style="width:550px;">
        <button class="modal-cerrar" onclick="cerrarMateriales()">&times;</button>
        <h3>📦 Materiales del Curso</h3>
        <form action="administrador.php" method="POST" enctype="multipart/form-data">
            <input type="hidden" name="actualizar_materiales" value="1">
            <input type="hidden" name="curso_id" id="matCursoId" value="">
            <input type="hidden" name="clave" id="matClave" value="">
            <label>URL del Video <span style="font-weight:normal; color:#555;">(YouTube, se convierte automáticamente)</span>:</label>
            <input type="url" name="video_url" id="matVideoUrl" placeholder="https://www.youtube.com/watch?v=... o https://www.youtube.com/embed/...">
            <label>Guía PDF <span style="font-weight:normal; color:#555;">(subir archivo .pdf)</span>:</label>
            <input type="file" name="pdf_file" accept=".pdf" style="padding:8px;">
            <p style="font-size:12px; color:#94a3b8; margin:5px 0 0 0;">Actual: <span id="matPdfActual" style="color:#1a426e;">—</span></p>
            <label>Texto de la Lección:</label>
            <textarea name="texto_leccion" id="matTexto" rows="4" style="width:100%; padding:10px; border:1px solid #cbd5e1; border-radius:6px; box-sizing:border-box; font-family:Arial; font-size:14px;"></textarea>
            <button type="submit" style="background:#1a426e; color:white; width:100%; margin-top:15px;">💾 Guardar Materiales</button>
        </form>
    </div>
</div>

<!-- Modal Historial Estudiante -->
<div class="modal-overlay" id="modalHistorial">
    <div class="modal-contenido" style="width:550px;">
        <button class="modal-cerrar" onclick="document.getElementById('modalHistorial').classList.remove('mostrar')">&times;</button>
        <h3>🔍 Cursos del Estudiante</h3>
        <div id="historialContenido" style="font-size:14px; color:#334155; min-height:50px;">Cargando...</div>
    </div>
</div>

<style>
.badge {
    display: inline-block; padding: 3px 8px; font-size: 11px;
    font-weight: bold; border-radius: 4px; min-width: 30px;
}
.badge-activo { background: #dcfce7; color: #15803d; }
.badge-inactivo { background: #fee2e2; color: #b91c1c; }
</style>

<script>
function filtrarCedula(inputId, tablaId, colIndex) {
    var input = document.getElementById(inputId);
    var filtro = input.value.toLowerCase();
    var tabla = document.getElementById(tablaId);
    var filas = tabla.getElementsByTagName('tbody')[0].getElementsByTagName('tr');
    for (var i = 0; i < filas.length; i++) {
        var celdas = filas[i].getElementsByTagName('td');
        if (celdas.length > colIndex) {
            var cedula = celdas[colIndex].textContent.toLowerCase();
            filas[i].style.display = cedula.indexOf(filtro) > -1 ? '' : 'none';
        }
    }
}

function mostrarSeccion(id) {
    document.querySelectorAll('.seccion').forEach(s => s.classList.remove('activa'));
    var sec = document.getElementById('sec-' + id);
    if (sec) sec.classList.add('activa');
}

function abrirModal(id, nombre, cedula, email, materias) {
    document.getElementById('modalProfeId').value = id;
    document.getElementById('modalNombre').value = nombre;
    document.getElementById('modalCedula').value = cedula;
    document.getElementById('modalEmail').value = email;
    // Marcar los checkboxes de las materias del profesor
    var checks = document.querySelectorAll('#modalMaterias input[type="checkbox"]');
    for (var i = 0; i < checks.length; i++) {
        checks[i].checked = materias.indexOf(checks[i].value) !== -1;
    }
    document.getElementById('modalModificar').classList.add('mostrar');
}

function cerrarModal() {
    document.getElementById('modalModificar').classList.remove('mostrar');
}

function abrirMateriales(id, clave, nombre, videoUrl, pdfPath, texto) {
    document.getElementById('matCursoId').value = id;
    document.getElementById('matClave').value = clave;
    document.getElementById('matVideoUrl').value = videoUrl;
    document.getElementById('matTexto').value = texto;
    document.getElementById('matPdfActual').innerText = pdfPath || '—';
    document.getElementById('modalMateriales').classList.add('mostrar');
}

function cerrarMateriales() {
    document.getElementById('modalMateriales').classList.remove('mostrar');
}

function verHistorial(usuarioId, nombre) {
    var modal = document.getElementById('modalHistorial');
    var contenido = document.getElementById('historialContenido');
    contenido.innerHTML = 'Cargando historial de <strong>' + nombre + '</strong>...';
    modal.classList.add('mostrar');

    var xhr = new XMLHttpRequest();
    xhr.open('GET', 'ajax_historial.php?usuario_id=' + usuarioId, true);
    xhr.onload = function() {
        if (xhr.status === 200) {
            var cursos = JSON.parse(xhr.responseText);
            var html = '<p><strong>Estudiante:</strong> ' + nombre + '</p>';
            if (cursos.length === 0) {
                html += '<p style="color:#555;">No tiene inscripciones.</p>';
            } else {
                html += '<table style="width:100%; border-collapse:collapse; font-size:13px;">';
                html += '<tr style="background:#e2e8f0;"><th style="padding:8px; text-align:left;">Curso</th><th style="padding:8px; text-align:center;">Estado</th><th style="padding:8px; text-align:center;">Fecha</th><th style="padding:8px; text-align:left;">Notas</th></tr>';
                for (var i = 0; i < cursos.length; i++) {
                    var c = cursos[i];
                    var badge = c.estado === 'completado'
                        ? '<span style="background:#dcfce7; color:#15803d; padding:2px 8px; border-radius:4px; font-weight:bold;">✅ Completado</span>'
                        : '<span style="background:#dbeafe; color:#1d4ed8; padding:2px 8px; border-radius:4px; font-weight:bold;">📌 Activo</span>';
                    var notas = c.notas ? c.notas : '<span style="color:#94a3b8;">—</span>';
                    html += '<tr style="border-bottom:1px solid #e2e8f0;">';
                    html += '<td style="padding:8px;">' + c.materia_nombre + '</td>';
                    html += '<td style="padding:8px; text-align:center;">' + badge + '</td>';
                    html += '<td style="padding:8px; text-align:center; color:#334155;">' + new Date(c.fecha_inscripcion).toLocaleDateString('es-ES') + '</td>';
                    html += '<td style="padding:8px; font-size:12px; color:#334155;">' + notas + '</td>';
                    html += '</tr>';
                }
                html += '</table>';
            }
            contenido.innerHTML = html;
        }
    };
    xhr.send();
}

// Cerrar modales al hacer clic fuera
document.getElementById('modalModificar').addEventListener('click', function(e) {
    if (e.target === this) cerrarModal();
});
document.getElementById('modalHistorial').addEventListener('click', function(e) {
    if (e.target === this) this.classList.remove('mostrar');
});
document.getElementById('modalMateriales').addEventListener('click', function(e) {
    if (e.target === this) cerrarMateriales();
});
</script>

</body>
</html>
