<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use PDO;

class Asociado
{
    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    public function contarSolicitudesRecientesPorIp(string $ip): int
    {
        $stmt = $this->pdo->prepare(
            'SELECT COUNT(*) FROM asociados WHERE ip_registro = :ip AND creado_en > (NOW() - INTERVAL 10 MINUTE)'
        );
        $stmt->execute(['ip' => $ip]);
        return (int) $stmt->fetchColumn();
    }

    /**
     * @param array<string, mixed> $datos
     */
    public function crear(array $datos): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO asociados
                (nombres, apellidos, cedula, fecha_nacimiento, telefono, email, direccion, fuerza, mensaje, ip_registro)
             VALUES
                (:nombres, :apellidos, :cedula, :fecha_nacimiento, :telefono, :email, :direccion, :fuerza, :mensaje, :ip)'
        );
        $stmt->execute($datos);
    }

    public function buscarPorId(int $id): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asociados WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function existe(int $id): bool
    {
        $stmt = $this->pdo->prepare('SELECT id FROM asociados WHERE id = :id');
        $stmt->execute(['id' => $id]);
        return (bool) $stmt->fetch();
    }

    /**
     * Todos los asociados, con el pago del ciclo indicado si existe
     * (pago_fecha queda en null si no ha pagado ese ciclo).
     */
    public function listarConPagoDelCiclo(int $anio, int $mes): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT a.*, p.fecha_pago AS pago_fecha
             FROM asociados a
             LEFT JOIN pagos_cuota p ON p.asociado_id = a.id AND p.anio = :anio AND p.mes = :mes
             ORDER BY a.creado_en DESC'
        );
        $stmt->execute(['anio' => $anio, 'mes' => $mes]);
        return $stmt->fetchAll();
    }

    public function listarAprobados(): array
    {
        return $this->pdo->query(
            "SELECT id, nombres, apellidos, cedula, creado_en, fecha_afiliacion FROM asociados WHERE estado = 'aprobado' ORDER BY nombres, apellidos"
        )->fetchAll();
    }

    public function actualizarEstado(int $id, string $estado): int
    {
        $stmt = $this->pdo->prepare('UPDATE asociados SET estado = :estado WHERE id = :id');
        $stmt->execute(['estado' => $estado, 'id' => $id]);
        return $stmt->rowCount();
    }

    /**
     * @param string|null $fecha formato Y-m-d, o null para volver a usar creado_en como referencia.
     */
    public function actualizarFechaAfiliacion(int $id, ?string $fecha): int
    {
        $stmt = $this->pdo->prepare('UPDATE asociados SET fecha_afiliacion = :fecha WHERE id = :id');
        $stmt->execute(['fecha' => $fecha, 'id' => $id]);
        return $stmt->rowCount();
    }
}
