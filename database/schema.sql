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
    estado ENUM('pendiente', 'aprobado', 'rechazado', 'inactivo') NOT NULL DEFAULT 'pendiente',
    ip_registro VARCHAR(45) NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    fecha_afiliacion DATE NULL COMMENT 'Fecha real en la que se asoció (puede ser anterior a creado_en, que es solo la fecha de registro en el sitio web). Si es NULL, se usa creado_en.',
    password_hash VARCHAR(255) NULL COMMENT 'Acceso al portal del afiliado; NULL hasta que se aprueba/activa.',
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    ultimo_acceso TIMESTAMP NULL,
    UNIQUE KEY uq_asociados_cedula (cedula)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- FASE 2: Panel de administración (roles + cuota mensual)
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS usuarios_admin (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    nombre VARCHAR(150) NOT NULL,
    email VARCHAR(150) NOT NULL,
    telefono VARCHAR(20) NULL,
    password_hash VARCHAR(255) NOT NULL,
    rol ENUM('administrador', 'super_administrador', 'tesorero') NOT NULL DEFAULT 'administrador',
    activo TINYINT(1) NOT NULL DEFAULT 1,
    intentos_fallidos TINYINT UNSIGNED NOT NULL DEFAULT 0,
    bloqueado_hasta TIMESTAMP NULL,
    ultimo_acceso TIMESTAMP NULL,
    ultimo_reset_solicitado TIMESTAMP NULL,
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
-- FASE 5: Portal del afiliado + tickets de soporte
-- ---------------------------------------------------------------------------

CREATE TABLE IF NOT EXISTS tickets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    asociado_id INT UNSIGNED NOT NULL,
    mensaje TEXT NOT NULL,
    imagen_ruta VARCHAR(255) NULL,
    estado ENUM('abierto', 'resuelto') NOT NULL DEFAULT 'abierto',
    respuesta TEXT NULL,
    respondido_por INT UNSIGNED NULL,
    respondido_en TIMESTAMP NULL,
    creado_en TIMESTAMP NOT NULL DEFAULT CURRENT_TIMESTAMP,
    CONSTRAINT fk_ticket_asociado FOREIGN KEY (asociado_id) REFERENCES asociados(id),
    CONSTRAINT fk_ticket_admin FOREIGN KEY (respondido_por) REFERENCES usuarios_admin(id)
) ENGINE=InnoDB;

-- ---------------------------------------------------------------------------
-- Usuario de base de datos de mínimo privilegio para la aplicación web.
-- No uses la cuenta "root" en el .env de producción: crea un usuario dedicado
-- que solo pueda leer/insertar/actualizar en esta base de datos concreta.
-- DELETE se otorga SOLO sobre pagos_cuota (para que el tesorero pueda
-- revertir un pago marcado por error / marcar "moroso"); nunca sobre
-- asociados ni usuarios_admin (esas cuentas se desactivan, no se borran).
--
-- Ejecuta estas líneas manualmente (ajusta la contraseña) y usa esas
-- credenciales en tu archivo .env (DB_USER / DB_PASS):
--
-- CREATE USER 'asovegu_app'@'localhost' IDENTIFIED BY 'CAMBIA_ESTA_CLAVE';
-- GRANT SELECT, INSERT, UPDATE ON asovegu.* TO 'asovegu_app'@'localhost';
-- GRANT DELETE ON asovegu.pagos_cuota TO 'asovegu_app'@'localhost';
-- FLUSH PRIVILEGES;
-- ---------------------------------------------------------------------------
