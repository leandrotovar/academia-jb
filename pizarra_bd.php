<?php
function pizarra_crear_tablas($conn) {
    $conn->query("CREATE TABLE IF NOT EXISTS pizarra_salas (
        id INT AUTO_INCREMENT PRIMARY KEY,
        codigo VARCHAR(6) NOT NULL UNIQUE,
        materia VARCHAR(50) DEFAULT 'blanco',
        creador_id INT NULL,
        activa TINYINT(1) DEFAULT 1,
        epoca INT NOT NULL DEFAULT 0,
        ultima_actividad DATETIME DEFAULT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");

    $conn->query("CREATE TABLE IF NOT EXISTS pizarra_trazos (
        id INT AUTO_INCREMENT PRIMARY KEY,
        sala_id INT NOT NULL,
        usuario_id INT NULL,
        tipo VARCHAR(20) NOT NULL,
        datos TEXT NOT NULL,
        created_at DATETIME DEFAULT CURRENT_TIMESTAMP,
        INDEX (sala_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_general_ci");
}
?>