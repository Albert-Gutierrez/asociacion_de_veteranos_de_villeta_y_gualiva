<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Actividad
{
    public const MAX_IMAGENES_GALERIA = 20;

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function crear(string $titulo, string $descripcion, string $imagenPortada, ?int $creadoPor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO actividades (titulo, descripcion, imagen_portada, creado_por)
             VALUES (:titulo, :descripcion, :imagen, :creado_por)'
        );
        $stmt->execute([
            'titulo' => $titulo,
            'descripcion' => $descripcion,
            'imagen' => $imagenPortada,
            'creado_por' => $creadoPor,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function agregarImagen(int $actividadId, string $ruta, int $orden): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO actividad_imagenes (actividad_id, imagen_ruta, orden) VALUES (:id, :ruta, :orden)'
        );
        $stmt->execute(['id' => $actividadId, 'ruta' => $ruta, 'orden' => $orden]);
    }

    public function listarTodas(): array
    {
        return $this->pdo->query(
            'SELECT a.*, u.nombre AS creado_por_nombre,
                    (SELECT COUNT(*) FROM actividad_imagenes ai WHERE ai.actividad_id = a.id) AS total_imagenes_galeria
             FROM actividades a
             LEFT JOIN usuarios_admin u ON u.id = a.creado_por
             ORDER BY a.creado_en DESC'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM actividades WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function obtenerImagenes(int $actividadId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT * FROM actividad_imagenes WHERE actividad_id = :id ORDER BY orden ASC, id ASC'
        );
        $stmt->execute(['id' => $actividadId]);
        return $stmt->fetchAll();
    }

    public function eliminar(int $id): void
    {
        // Las filas de actividad_imagenes se borran en cascada (FK ON DELETE CASCADE).
        $stmt = $this->pdo->prepare('DELETE FROM actividades WHERE id = :id');
        $stmt->execute(['id' => $id]);
    }
}
