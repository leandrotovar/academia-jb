<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id'])) {
    echo "error: no_session";
    exit();
}

$usuario_id = $_SESSION['usuario_id'];
$accion = $_POST['accion'] ?? '';
$materia = $_POST['materia'] ?? '';

if ($accion === 'inscribir' && $materia) {
    $stmt = $conn->prepare("INSERT IGNORE INTO inscripciones (usuario_id, materia) VALUES (?, ?)");
    $stmt->bind_param("is", $usuario_id, $materia);
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }
    $stmt->close();
} elseif ($accion === 'desinscribir' && $materia) {
    $notas = $_POST['notas'] ?? '';
    if ($notas) {
        $stmt = $conn->prepare("UPDATE inscripciones SET estado = 'completado', notas = CONCAT(IFNULL(notas,''), '\n', ?) WHERE usuario_id = ? AND materia = ?");
        $stmt->bind_param("sis", $notas, $usuario_id, $materia);
    } else {
        $stmt = $conn->prepare("UPDATE inscripciones SET estado = 'completado' WHERE usuario_id = ? AND materia = ?");
        $stmt->bind_param("is", $usuario_id, $materia);
    }
    $stmt->execute();
    echo "ok";
    $stmt->close();
} elseif ($accion === 'guardar_notas') {
    $inscripcion_id = (int)($_POST['inscripcion_id'] ?? 0);
    $notas = $_POST['notas'] ?? '';
    $stmt = $conn->prepare("UPDATE inscripciones SET notas = ? WHERE id = ?");
    $stmt->bind_param("si", $notas, $inscripcion_id);
    if ($stmt->execute()) {
        echo "ok";
    } else {
        echo "error: " . $stmt->error;
    }
    $stmt->close();
} else {
    echo "error: invalid";
}
