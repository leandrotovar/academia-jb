<?php
session_start();
include 'conexion.php';
require_once 'pizarra_bd.php';
pizarra_crear_tablas($conn);

header('Content-Type: application/json');

if (!isset($_SESSION['usuario_id'])) {
    echo json_encode(['ok' => 0, 'error' => 'Sesión requerida']);
    exit;
}

$id_usuario = (int) $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? $_GET['accion'] ?? '';

// ---------- SALA ACTIVA DE UN CURSO ----------
if ($accion === 'sala_curso') {
    $materia = substr(trim($_GET['materia'] ?? ''), 0, 50);
    if ($materia === '') { echo json_encode(['ok' => 1, 'sala' => null]); exit; }
    $stmt = $conn->prepare("SELECT id, codigo, materia, epoca, creador_id FROM pizarra_salas
                            WHERE materia = ? AND activa = 1 ORDER BY id DESC LIMIT 1");
    $stmt->bind_param('s', $materia);
    $stmt->execute();
    $res = $stmt->get_result();
    $fila = $res->fetch_assoc();
    if ($fila) {
        $conn->query("UPDATE pizarra_salas SET ultima_actividad = NOW() WHERE id = " . (int) $fila['id']);
        echo json_encode(['ok' => 1, 'sala' => [
            'id' => (int) $fila['id'],
            'codigo' => $fila['codigo'],
            'materia' => $fila['materia'],
            'epoca' => (int) $fila['epoca'],
            'creador_id' => (int) $fila['creador_id']
        ]]);
    } else {
        echo json_encode(['ok' => 1, 'sala' => null]);
    }
    exit;
}

// ---------- CREAR SALA ----------
if ($accion === 'crear_sala') {
    $materias = ['matematica', 'fisica', 'quimica', 'aeronautica', 'informatica', 'blanco'];
    $materia = $_POST['materia'] ?? 'blanco';
    if (!in_array($materia, $materias, true)) $materia = 'blanco';

    $codigo = '';
    do {
        $codigo = strtoupper(substr(str_shuffle('ABCDEFGHKLMNPQRSTUVWXYZ2345679'), 0, 5));
        $chk = $conn->query("SELECT id FROM pizarra_salas WHERE codigo = '$codigo'");
    } while ($chk && $chk->num_rows > 0);

    if ($materia !== 'blanco') {
        $conn->query("UPDATE pizarra_salas SET activa = 0 WHERE materia = '$materia' AND activa = 1");
    }
    $stmt = $conn->prepare("INSERT INTO pizarra_salas (codigo, materia, creador_id, activa, ultima_actividad) VALUES (?, ?, ?, 1, NOW())");
    $stmt->bind_param('ssi', $codigo, $materia, $id_usuario);
    $stmt->execute();
    echo json_encode(['ok' => 1, 'codigo' => $codigo, 'sala_id' => $conn->insert_id, 'materia' => $materia]);
    exit;
}

// ---------- UNIRSE A SALA ----------
if ($accion === 'unirse_sala') {
    $codigo = strtoupper(trim($_POST['codigo'] ?? ''));
    if ($codigo === '') { echo json_encode(['ok' => 0, 'error' => 'Escribe un código válido']); exit; }
    $stmt = $conn->prepare("SELECT id, materia FROM pizarra_salas WHERE codigo = ? AND activa = 1");
    $stmt->bind_param('s', $codigo);
    $stmt->execute();
    $res = $stmt->get_result();
    if ($fila = $res->fetch_assoc()) {
        $sala_id = (int) $fila['id'];
        $conn->query("UPDATE pizarra_salas SET ultima_actividad = NOW() WHERE id = $sala_id");
        echo json_encode(['ok' => 1, 'codigo' => $codigo, 'materia' => $fila['materia'], 'sala_id' => $sala_id]);
    } else {
        echo json_encode(['ok' => 0, 'error' => 'Código no válido o sala inactiva']);
    }
    exit;
}

// ---------- AGREGAR TRAZO ----------
if ($accion === 'agregar_trazo') {
    $sala_id = (int) ($_POST['sala_id'] ?? 0);
    $tipo = substr(trim($_POST['tipo'] ?? ''), 0, 20);
    $datos = trim($_POST['datos'] ?? '');
    if (!$sala_id || $tipo === '' || $datos === '') { echo json_encode(['ok' => 0, 'error' => 'Datos incompletos']); exit; }
    $ver = $conn->query("SELECT id FROM pizarra_salas WHERE id = $sala_id AND activa = 1");
    if (!$ver || $ver->num_rows === 0) { echo json_encode(['ok' => 0, 'error' => 'Sala no encontrada']); exit; }
    $stmt = $conn->prepare("INSERT INTO pizarra_trazos (sala_id, usuario_id, tipo, datos) VALUES (?, ?, ?, ?)");
    $stmt->bind_param('iiss', $sala_id, $id_usuario, $tipo, $datos);
    $stmt->execute();
    echo json_encode(['ok' => 1, 'id' => $conn->insert_id]);
    exit;
}

// ---------- SINCRONIZAR ----------
if ($accion === 'sincronizar') {
    $sala_id = (int) ($_GET['sala_id'] ?? 0);
    $ultimo = (int) ($_GET['ultimo_id'] ?? 0);
    $epoca = (int) ($_GET['epoca'] ?? 0);
    if (!$sala_id) { echo json_encode(['ok' => 0]); exit; }

    $se = $conn->query("SELECT epoca FROM pizarra_salas WHERE id = $sala_id AND activa = 1");
    if (!$se || $se->num_rows === 0) { echo json_encode(['ok' => 0, 'error' => 'sala_no_disponible']); exit; }
    $epoca_actual = (int) $se->fetch_assoc()['epoca'];

    if ($epoca_actual !== $epoca) {
        $sql = "SELECT id, usuario_id, tipo, datos, (SELECT nombre FROM usuarios u WHERE u.id = pizarra_trazos.usuario_id) AS nombre
                FROM pizarra_trazos WHERE sala_id = $sala_id ORDER BY id ASC LIMIT 2000";
    } else {
        $sql = "SELECT id, usuario_id, tipo, datos, (SELECT nombre FROM usuarios u WHERE u.id = pizarra_trazos.usuario_id) AS nombre
                FROM pizarra_trazos WHERE sala_id = $sala_id AND id > $ultimo ORDER BY id ASC LIMIT 500";
    }

    $r = $conn->query($sql);
    $trazos = [];
    $ultimo_nombre = '';
    if ($r) {
        while ($f = $r->fetch_assoc()) {
            $datos = json_decode($f['datos'], true);
            if (!$datos) continue;
            $datos['__id'] = (int) $f['id'];
            $datos['__usuario'] = (int) $f['usuario_id'];
            $trazos[] = [
                'id' => (int) $f['id'],
                'usuario_id' => (int) $f['usuario_id'],
                'nombre' => $f['nombre'] ?? '',
                'tipo' => $f['tipo'],
                'datos' => $datos
            ];
            if (!empty($f['nombre'])) $ultimo_nombre = $f['nombre'];
        }
    }
    echo json_encode(['ok' => 1, 'epoca' => $epoca_actual, 'trazos' => $trazos, 'ultimo_nombre' => $ultimo_nombre]);
    exit;
}

// ---------- LIMPIAR (todos) ----------
if ($accion === 'limpiar') {
    $sala_id = (int) ($_POST['sala_id'] ?? 0);
    if (!$sala_id) { echo json_encode(['ok' => 0]); exit; }
    $conn->query("DELETE FROM pizarra_trazos WHERE sala_id = $sala_id");
    $conn->query("UPDATE pizarra_salas SET epoca = epoca + 1, ultima_actividad = NOW() WHERE id = $sala_id");
    echo json_encode(['ok' => 1]);
    exit;
}

echo json_encode(['ok' => 0, 'error' => 'Acción desconocida']);
?>