-- Tabla de usuarios web (login / roles). Ejecutar en tu base `taller_reparaciones` si aún no existe.

CREATE TABLE IF NOT EXISTS Usuario (
    id_usuario INT AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(80) NOT NULL UNIQUE,
    password_hash VARCHAR(255) NOT NULL,
    rol VARCHAR(40) NOT NULL DEFAULT 'cliente',
    estado VARCHAR(20) NOT NULL DEFAULT 'activo',
    id_tecnico INT NULL,
    INDEX idx_usuario_rol (rol),
    INDEX idx_usuario_estado (estado)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;
