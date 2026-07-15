-- Esquema de base de datos para la Asociación de Veteranos de Villeta y Gualivá
--
-- En XAMPP local: ejecuta todo el archivo tal cual (con el usuario root)
--   mysql -u root < database/schema.sql
--
-- En hosting compartido (cPanel/similares): normalmente NO tienes permisos
-- para CREATE DATABASE ni CREATE USER por SQL. En esos casos:
--   1. Crea la base de datos y el usuario desde el panel de control
--      ("MySQL Databases" en cPanel). El nombre real de ambos suele venir
--      prefijado con tu usuario de cPanel (ej: cpaneluser_asovegu).
--   2. Entra a esa base ya creada (desde phpMyAdmin) y ejecuta solo el
--      bloque CREATE TABLE de abajo.
--   3. Usa esos nombres reales en tu archivo .env (DB_NAME/DB_USER/DB_PASS).

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
-- FASE 2: Panel de administración (roles + cuota mensual)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'super_administrador') NOT NULL DEFAULT 'administrador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    ultimo_acceso TIMESTAMP NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_usuarios_admin_email (email)
) ENGINE=InnoDB;

CREATE TABLE IF NOT EXISTS pagos_cuota (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asociado_id INT UNSIGNED NOT NULL,
    anio SMALLINT UNSIGNED NOT NULL,
    mes TINYINT UNSIGNED NOT NULL,
    fecha_pago DATE NOT NULL,
    monto DECIMAL(10, 2) NOT NULL DEFAULT 20000.00,
    registrado_por INT UNSIGNED NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uq_pago_mes (asociado_id, anio, mes),
    CONSTRAINT fk_pago_asociado FOREIGN KEY (asociado_id) REFERENCES asociados(id),
    CONSTRAINT fk_pago_admin FOREIGN KEY (registrado_por) REFERENCES usuarios_admin(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Usuario de base de datos de mínimo privilegio para la aplicación web.
-- No uses la cuenta "root" en el .env de producción: crea un usuario dedicado
-- que solo pueda leer/insertar/actualizar en esta base de datos concreta
-- (no necesita DELETE: las cuentas de admin se desactivan, no se borran).
--
-- Ejecuta estas líneas manualmente (ajusta la contraseña) y usa esas
-- credenciales en tu archivo .env (DB_USER / DB_PASS):
--
-- CREATE USER 'asovegu_app'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
-- GRANT SELECT, INSERT, UPDATE ON asovegu.* TO 'asovegu_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
