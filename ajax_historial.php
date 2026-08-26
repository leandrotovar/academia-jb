<?php
session_start();
include 'conexion.php';

if (!isset($_SESSION['usuario_id']) || $_SESSION['rol'] === 'estudiante') {
    echo json_encode([]);
    exit();
}

$usuario_id = (int)($_GET['usuario_id'] ?? 0);
$materias_map = [
    'matematica' => 'Matemáticas',
    'fisica' => 'Física',
    'quimica' => 'Química',
    'aeronautica' => 'Aeronáutica',
    'informatica' => 'Informática'
];

$stmt = $conn->prepare("SELECT i.materia, i.estado, i.fecha_inscripcion, i.notas
                        FROM inscripciones i
                        WHERE i.usuario_id = ?
                        ORDER BY i.fecha_inscripcion DESC");
$stmt->bind_param("i", $usuario_id);
$stmt->execute();
$res = $stmt->get_result();

$historial = [];
while ($row = $res->fetch_assoc()) {
    $row['materia_nombre'] = $materias_map[$row['materia']] ?? $row['materia'];
    $historial[] = $row;
}
$stmt->close();

echo json_encode($historial);
