<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Testimonio
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function buscarPorAsociado(int $asociadoId): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM testimonios WHERE asociado_id = :id');
        $stmt->execute(['id' => $asociadoId]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    /**
     * Crea el testimonio del asociado o lo reemplaza si ya tenía uno (vuelve
     * a quedar "pendiente" para que un admin lo revise de nuevo).
     */
    public function guardar(int $asociadoId, string $mensaje, ?string $fotoRuta): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO testimonios (asociado_id, mensaje, foto_ruta, estado)
             VALUES (:asociado_id, :mensaje, :foto_ruta, \'pendiente\')
             ON DUPLICATE KEY UPDATE
                mensaje = VALUES(mensaje),
                foto_ruta = VALUES(foto_ruta),
                estado = \'pendiente\',
                aprobado_por = NULL,
                aprobado_en = NULL'
        );
        $stmt->execute([
            'asociado_id' => $asociadoId,
            'mensaje' => $mensaje,
            'foto_ruta' => $fotoRuta,
        ]);
    }

    /**
     * @param string|null $estado 'pendiente'|'aprobado'|'rechazado'|null (todos)
     */
    public function listarTodos(?string $estado = null): array
    {
        $sql = 'SELECT t.*, a.nombres, a.apellidos, u.nombre AS aprobado_por_nombre
                FROM testimonios t
                JOIN asociados a ON a.id = t.asociado_id
                LEFT JOIN usuarios_admin u ON u.id = t.aprobado_por';
        $params = [];
        if ($estado !== null) {
            $sql .= ' WHERE t.estado = :estado';
            $params['estado'] = $estado;
        }
        $sql .= ' ORDER BY t.creado_en DESC';

        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($params);
        return $stmt->fetchAll();
    }

    public function listarAprobadosPublico(): array
    {
        $stmt = $this->pdo->query(
            "SELECT t.mensaje, t.foto_ruta, a.nombres, a.apellidos
             FROM testimonios t
             JOIN asociados a ON a.id = t.asociado_id
             WHERE t.estado = 'aprobado'
             ORDER BY t.aprobado_en DESC"
        );
        return $stmt->fetchAll();
    }

    public function buscarPorFotoRuta(string $fotoRuta): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM testimonios WHERE foto_ruta = :foto_ruta LIMIT 1');
        $stmt->execute(['foto_ruta' => $fotoRuta]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, a.nombres, a.apellidos
             FROM testimonios t
             JOIN asociados a ON a.id = t.asociado_id
             WHERE t.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function actualizarEstado(int $id, string $estado, int $aprobadoPor): int
    {
        $stmt = $this->pdo->prepare(
            'UPDATE testimonios
             SET estado = :estado, aprobado_por = :aprobado_por, aprobado_en = NOW()
             WHERE id = :id'
        );
        $stmt->execute(['estado' => $estado, 'aprobado_por' => $aprobadoPor, 'id' => $id]);
        return $stmt->rowCount();
    }

    public function contarPendientes(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM testimonios WHERE estado = 'pendiente'")->fetchColumn();
    }
}
