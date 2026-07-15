<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class UsuarioAdmin
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios_admin WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarActivoPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM usuarios_admin WHERE email = :email AND activo = 1 LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function listarTodos(): array
    {
        return $this->pdo->query('SELECT * FROM usuarios_admin ORDER BY creado_en ASC')->fetchAll();
    }

    public function crear(string $nombre, string $email, ?string $telefono, string $hash, string $rol): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO usuarios_admin (nombre, email, telefono, password_hash, rol) VALUES (:nombre, :email, :telefono, :hash, :rol)'
        );
        $stmt->execute([
            'nombre' => $nombre,
            'email' => $email,
            'telefono' => $telefono,
            'hash' => $hash,
            'rol' => $rol,
        ]);
    }

    public function registrarLoginExitoso(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios_admin SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function incrementarIntentos(int $id, int $intentos): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios_admin SET intentos_fallidos = :intentos WHERE id = :id');
        $stmt->execute(['intentos' => $intentos, 'id' => $id]);
    }

    public function bloquear(int $id, int $intentos, string $bloqueadoHastaFormateado): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios_admin SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueo WHERE id = :id'
        );
        $stmt->execute(['intentos' => $intentos, 'bloqueo' => $bloqueadoHastaFormateado, 'id' => $id]);
    }

    /**
     * Reset auto-servicio (desde "olvidé mi contraseña"): también marca
     * ultimo_reset_solicitado para el límite de una solicitud cada 5 minutos.
     */
    public function resetearPasswordAutoServicio(int $id, string $hash, string $ahoraFormateada): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios_admin
             SET password_hash = :hash, intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_reset_solicitado = :ahora
             WHERE id = :id'
        );
        $stmt->execute(['hash' => $hash, 'ahora' => $ahoraFormateada, 'id' => $id]);
    }

    /**
     * Reset disparado por un super administrador desde Administradores.
     */
    public function resetearPasswordAdmin(int $id, string $hash): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE usuarios_admin SET password_hash = :hash, intentos_fallidos = 0, bloqueado_hasta = NULL WHERE id = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $id]);
        return $stmt->rowCount();
    }

    public function alternarActivo(int $id): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios_admin SET activo = NOT activo WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }

    public function obtenerActivo(int $id): ?int
    {
        $stmt = $this->pdo->prepare('SELECT activo FROM usuarios_admin WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? (int) $fila['activo'] : null;
    }

    public function obtenerHashPassword(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM usuarios_admin WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila['password_hash'] : null;
    }

    public function actualizarPassword(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare('UPDATE usuarios_admin SET password_hash = :hash WHERE id = :id');
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
