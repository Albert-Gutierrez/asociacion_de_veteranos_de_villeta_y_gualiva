-- Esquema de base de datos para la Asociación de Veteranos de Villeta y Gualivá
-- Ejecutar en phpMyAdmin o con: mysql -u root -p < database/schema.sql

CREATE DATABASE IF NOT EXISTS asovegu
    CHARACTER SET utf8mb4
    COLLATE utf8mb4_unicode_ci;

USE asovegu;

CREATE TABLE IF NOT EXISTS asociados (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombres VARCHAR(100) NOT NULL,
    apellidos VARCHAR(100) NOT NULL,
    cedula VARCHAR(20) NOT NULL,
    fecha_nacimiento DATE NULL,
    telefono VARCHAR(20) NOT NULL,
    email VARCHAR(150) NOT NULL,
    direccion VARCHAR(255) NULL,
    fuerza VARCHAR(100) NOT NULL,
    mensaje TEXT NULL,
    estado ENUM('pendiente', 'aprobado', 'rechazado') NOT NULL DEFAULT 'pendiente',
    ip_registro VARCHAR(45) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_asociados_cedula (cedula)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Usuario de base de datos de mínimo privilegio para la aplicación web.
-- No uses la cuenta "root" en el .env de producción: crea un usuario dedicado
-- que solo pueda leer/insertar en esta base de datos concreta.
--
-- Ejecuta estas líneas manualmente (ajusta la contraseña) y usa esas
-- credenciales en tu archivo .env (DB_USER / DB_PASS):
--
-- CREATE USER 'asovegu_app'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
-- GRANT SELECT, INSERT ON asovegu.* TO 'asovegu_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
