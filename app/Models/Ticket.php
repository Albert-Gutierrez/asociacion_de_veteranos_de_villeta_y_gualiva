<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Ticket
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function crear(int $asociadoId, string $mensaje, ?string $imagenRuta): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO tickets (asociado_id, mensaje, imagen_ruta) VALUES (:asociado_id, :mensaje, :imagen_ruta)'
        );
        $stmt->execute([
            'asociado_id' => $asociadoId,
            'mensaje' => $mensaje,
            'imagen_ruta' => $imagenRuta,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listarPorAsociado(int $asociadoId): array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM tickets WHERE asociado_id = :id ORDER BY creado_en DESC');
        $stmt->execute(['id' => $asociadoId]);
        return $stmt->fetchAll();
    }

    /**
     * @param string|null $estado 'abierto'|'resuelto'|null (todos)
     */
    public function listarTodos(?string $estado = null): array
    {
        $sql = 'SELECT t.*, a.nombres, a.apellidos, a.cedula, u.nombre AS respondido_por_nombre
                FROM tickets t
                JOIN asociados a ON a.id = t.asociado_id
                LEFT JOIN usuarios_admin u ON u.id = t.respondido_por';
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

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare(
            'SELECT t.*, a.nombres, a.apellidos, a.cedula
             FROM tickets t
             JOIN asociados a ON a.id = t.asociado_id
             WHERE t.id = :id'
        );
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function marcarResuelto(int $id, ?string $respuesta, int $respondidoPor): int
    {
        $stmt = $this->pdo->prepare(
            "UPDATE tickets
             SET estado = 'resuelto', respuesta = :respuesta, respondido_por = :respondido_por, respondido_en = NOW()
             WHERE id = :id"
        );
        $stmt->execute(['respuesta' => $respuesta, 'respondido_por' => $respondidoPor, 'id' => $id]);
        return $stmt->rowCount();
    }

    public function contarAbiertos(): int
    {
        return (int) $this->pdo->query("SELECT COUNT(*) FROM tickets WHERE estado = 'abierto'")->fetchColumn();
    }
}
