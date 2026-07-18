<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Documento
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function crear(string $titulo, string $archivoRuta, string $archivoNombreOriginal, int $subidoPor): int
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO documentos (titulo, archivo_ruta, archivo_nombre_original, subido_por)
             VALUES (:titulo, :archivo_ruta, :archivo_nombre_original, :subido_por)'
        );
        $stmt->execute([
            'titulo' => $titulo,
            'archivo_ruta' => $archivoRuta,
            'archivo_nombre_original' => $archivoNombreOriginal,
            'subido_por' => $subidoPor,
        ]);
        return (int) $this->pdo->lastInsertId();
    }

    public function listarTodos(): array
    {
        return $this->pdo->query(
            'SELECT d.*, u.nombre AS subido_por_nombre
             FROM documentos d
             LEFT JOIN usuarios_admin u ON u.id = d.subido_por
             ORDER BY d.creado_en DESC'
        )->fetchAll();
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM documentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function eliminar(int $id): int
    {
        $stmt = $this->pdo->prepare('DELETE FROM documentos WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return $stmt->rowCount();
    }
}
