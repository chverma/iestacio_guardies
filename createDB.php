<?php

// Configuración
include 'config.php';

try {
    // Conectar a SQLite (crea el archivo si no existe)
    $db = new PDO("sqlite:$db_file");
    $db->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

    // Crear tabla si no existe
    $db->exec("
        CREATE TABLE IF NOT EXISTS eventos (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            timestamp INTEGER NOT NULL,
            nom TEXT NOT NULL,
            email TEXT NOT NULL,
            titol TEXT NOT NULL,
            descripcio TEXT,
            data_ini TEXT,
            data_fi TEXT,
            classe TEXT NOT NULL,
            dia TEXT NOT NULL,
            hora TEXT NOT NULL,
            id_calendar_event INTEGER,
            status INTEGER DEFAULT 0,
            id_list INTEGER DEFAULT 0,
            created_at TEXT DEFAULT CURRENT_TIMESTAMP
        );

        CREATE INDEX IF NOT EXISTS idx_eventos_dia_hora ON eventos(dia, hora);
        CREATE INDEX IF NOT EXISTS idx_eventos_classe ON eventos(classe);
    ");

} catch (PDOException $e) {
    echo json_encode([
        "status" => "error",
        "message" => "Error de base de datos: " . $e->getMessage()
    ]);
}
?>
