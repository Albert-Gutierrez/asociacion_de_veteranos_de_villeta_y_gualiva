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

    /**
     * Corrección de datos personales por parte de un administrador
     * (nombres, apellidos, cédula, etc. mal digitados por el asociado).
     *
     * @param array<string, mixed> $datos
     */
    public function actualizarDatos(int $id, array $datos): int
    {
        $datos['id'] = $id;
        $stmt = $this->pdo->prepare(
            'UPDATE asociados SET
                nombres = :nombres,
                apellidos = :apellidos,
                cedula = :cedula,
                fecha_nacimiento = :fecha_nacimiento,
                telefono = :telefono,
                email = :email,
                direccion = :direccion,
                fuerza = :fuerza,
                mensaje = :mensaje
             WHERE id = :id'
        );
        $stmt->execute($datos);
        return $stmt->rowCount();
    }

    /**
     * Único dato que el propio afiliado puede cambiar desde su portal.
     */
    public function actualizarFoto(int $id, ?string $rutaFoto): void
    {
        $stmt = $this->pdo->prepare('UPDATE asociados SET foto_ruta = :foto WHERE id = :id');
        $stmt->execute(['foto' => $rutaFoto, 'id' => $id]);
    }

    // ------------------------------------------------------------------
    // Acceso al portal del afiliado (mismo patrón que UsuarioAdmin)
    // ------------------------------------------------------------------

    public function buscarPorEmail(string $email): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asociados WHERE email = :email LIMIT 1');
        $stmt->execute(['email' => $email]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function buscarPorCedula(string $cedula): ?array
    {
        $stmt = $this->pdo->prepare('SELECT * FROM asociados WHERE cedula = :cedula LIMIT 1');
        $stmt->execute(['cedula' => $cedula]);
        $fila = $stmt->fetch();
        return $fila ?: null;
    }

    public function registrarLoginExitoso(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asociados SET intentos_fallidos = 0, bloqueado_hasta = NULL, ultimo_acceso = NOW() WHERE id = :id'
        );
        $stmt->execute(['id' => $id]);
    }

    public function incrementarIntentos(int $id, int $intentos): void
    {
        $stmt = $this->pdo->prepare('UPDATE asociados SET intentos_fallidos = :intentos WHERE id = :id');
        $stmt->execute(['intentos' => $intentos, 'id' => $id]);
    }

    public function bloquear(int $id, int $intentos, string $bloqueadoHastaFormateado): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asociados SET intentos_fallidos = :intentos, bloqueado_hasta = :bloqueo WHERE id = :id'
        );
        $stmt->execute(['intentos' => $intentos, 'bloqueo' => $bloqueadoHastaFormateado, 'id' => $id]);
    }

    /**
     * Genera/renueva el acceso al portal: guarda el hash y limpia bloqueos
     * previos (se usa tanto al aprobar por primera vez como al reactivar
     * o resetear el acceso de alguien ya aprobado). Queda marcado para que
     * tenga que cambiarla en su próximo ingreso.
     */
    public function activarAcceso(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asociados
             SET password_hash = :hash, debe_cambiar_password = 1, intentos_fallidos = 0, bloqueado_hasta = NULL
             WHERE id = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }

    public function obtenerHashPassword(int $id): ?string
    {
        $stmt = $this->pdo->prepare('SELECT password_hash FROM asociados WHERE id = :id');
        $stmt->execute(['id' => $id]);
        $fila = $stmt->fetch();
        return $fila ? $fila['password_hash'] : null;
    }

    /**
     * Cambio de contraseña hecho por el propio afiliado: ya no queda
     * marcado como pendiente de cambio.
     */
    public function actualizarPassword(int $id, string $hash): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE asociados SET password_hash = :hash, debe_cambiar_password = 0 WHERE id = :id'
        );
        $stmt->execute(['hash' => $hash, 'id' => $id]);
    }
}
