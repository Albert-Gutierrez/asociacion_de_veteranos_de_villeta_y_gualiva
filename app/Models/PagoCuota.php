<?php

declare(strict_types=1);

namespace App\Models;

use App\Core\Database;
use DateTimeImmutable;
use PDO;

class PagoCuota
{
    public const MONTO_CUOTA = 20000.00;

    private const NOMBRES_MESES = [
        1 => 'Enero', 2 => 'Febrero', 3 => 'Marzo', 4 => 'Abril',
        5 => 'Mayo', 6 => 'Junio', 7 => 'Julio', 8 => 'Agosto',
        9 => 'Septiembre', 10 => 'Octubre', 11 => 'Noviembre', 12 => 'Diciembre',
    ];

    private PDO $pdo;

    public function __construct()
    {
        $this->pdo = Database::conexion();
    }

    // ------------------------------------------------------------------
    // Lógica de negocio (sin BD)
    // ------------------------------------------------------------------

    /**
     * La cuota de un mes se paga entre el día 27 del mes anterior y el día 1
     * del mes en curso. Devuelve el ciclo (año/mes) que corresponde evaluar hoy:
     * si ya estamos en la ventana de pago anticipado (día >= 27), el ciclo
     * vigente es el del mes siguiente; si no, es el mes en curso.
     */
    public static function obtenerCicloPago(?DateTimeImmutable $hoy = null): array
    {
        $hoy = $hoy ?? new DateTimeImmutable('today');
        $dia = (int) $hoy->format('j');

        $referencia = $dia >= 27
            ? $hoy->modify('first day of next month')
            : $hoy->modify('first day of this month');

        return [
            'anio' => (int) $referencia->format('Y'),
            'mes' => (int) $referencia->format('n'),
            'dia_hoy' => $dia,
        ];
    }

    /**
     * 'pagado'   -> ya existe un registro de pago para el ciclo vigente.
     * 'pendiente'-> aún dentro del plazo (día 27-31 o día 1) y no ha pagado.
     * 'vencido'  -> el plazo ya cerró (día 2-26) y no ha pagado.
     */
    public static function estadoCuota(bool $pagado, int $diaHoy): string
    {
        if ($pagado) {
            return 'pagado';
        }
        return ($diaHoy >= 27 || $diaHoy === 1) ? 'pendiente' : 'vencido';
    }

    public static function nombreMes(int $mes): string
    {
        return self::NOMBRES_MESES[$mes] ?? (string) $mes;
    }

    public static function formatoPesos(float $valor): string
    {
        return '$' . number_format($valor, 0, ',', '.');
    }

    /**
     * El año/mes en que un asociado empieza a deber cuota: el mes de su
     * inscripción (antes de eso no era asociado, no se le puede exigir cuota).
     */
    public static function primerMesElegible(string $fechaInscripcionSql): array
    {
        $fecha = new DateTimeImmutable($fechaInscripcionSql);
        return ['anio' => (int) $fecha->format('Y'), 'mes' => (int) $fecha->format('n')];
    }

    /**
     * true si el mes indicado es igual o posterior al mes en que el
     * asociado se inscribió (es decir, si le aplica cobro de cuota ese mes).
     */
    public static function mesEsElegible(int $anio, int $mes, array $primerMes): bool
    {
        return ($anio * 12 + $mes) >= ($primerMes['anio'] * 12 + $primerMes['mes']);
    }

    /**
     * La fecha que se usa como referencia para calcular desde qué mes debe
     * cuota un asociado: su fecha de afiliación real si el tesorero la
     * cargó, o si no, la fecha en que se registró en el sitio web.
     */
    public static function fechaBaseCuota(array $asociado): string
    {
        return $asociado['fecha_afiliacion'] ?? $asociado['creado_en'];
    }

    /**
     * Todos los meses (año/mes) desde $primerMes hasta el mes en curso,
     * ambos incluidos, sin tope de 12 — para calcular el total que debe un
     * asociado desde que se afilió, sin importar cuánto tiempo lleve.
     */
    public static function obtenerMesesDesde(array $primerMes): array
    {
        $cursor = DateTimeImmutable::createFromFormat('Y-n-j', "{$primerMes['anio']}-{$primerMes['mes']}-1");
        $fin = new DateTimeImmutable('first day of this month');

        $meses = [];
        while ($cursor <= $fin) {
            $meses[] = ['anio' => (int) $cursor->format('Y'), 'mes' => (int) $cursor->format('n')];
            $cursor = $cursor->modify('+1 month');
        }
        return $meses;
    }

    /**
     * Los últimos 12 meses calendario (el actual primero), como pares año/mes.
     */
    public static function obtenerUltimos12Meses(): array
    {
        $meses = [];
        $hoy = new DateTimeImmutable('first day of this month');
        for ($i = 0; $i < 12; $i++) {
            $ref = $hoy->modify("-{$i} months");
            $meses[] = ['anio' => (int) $ref->format('Y'), 'mes' => (int) $ref->format('n')];
        }
        return $meses;
    }

    // ------------------------------------------------------------------
    // Acceso a datos
    // ------------------------------------------------------------------

    /**
     * Mapa "asociado_id-anio-mes" => true de todos los pagos desde $anioMin.
     */
    public function obtenerMapaPagosDesde(int $anioMin): array
    {
        $stmt = $this->pdo->prepare('SELECT asociado_id, anio, mes FROM pagos_cuota WHERE anio >= :anio_min');
        $stmt->execute(['anio_min' => $anioMin]);

        $mapa = [];
        foreach ($stmt->fetchAll() as $p) {
            $mapa[$p['asociado_id'] . '-' . $p['anio'] . '-' . $p['mes']] = true;
        }
        return $mapa;
    }

    public function historialPorAsociado(int $asociadoId): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT p.*, u.nombre AS registrado_por_nombre
             FROM pagos_cuota p
             LEFT JOIN usuarios_admin u ON u.id = p.registrado_por
             WHERE p.asociado_id = :id
             ORDER BY p.anio DESC, p.mes DESC'
        );
        $stmt->execute(['id' => $asociadoId]);
        return $stmt->fetchAll();
    }

    public function recaudoHistoricoTotal(): float
    {
        return (float) $this->pdo->query('SELECT COALESCE(SUM(monto), 0) FROM pagos_cuota')->fetchColumn();
    }

    /**
     * @return int[]
     */
    public function aniosConDatos(): array
    {
        return array_map('intval', $this->pdo->query('SELECT DISTINCT anio FROM pagos_cuota')->fetchAll(PDO::FETCH_COLUMN));
    }

    /**
     * Recaudo (suma de monto) por cada uno de los 12 meses del año dado.
     *
     * @return array<int, float> mes (1-12) => total
     */
    public function recaudoPorMesDeAnio(int $anio): array
    {
        $stmt = $this->pdo->prepare('SELECT mes, SUM(monto) AS total FROM pagos_cuota WHERE anio = :anio GROUP BY mes');
        $stmt->execute(['anio' => $anio]);

        $recaudo = array_fill(1, 12, 0.0);
        foreach ($stmt->fetchAll() as $r) {
            $recaudo[(int) $r['mes']] = (float) $r['total'];
        }
        return $recaudo;
    }

    /**
     * @throws \PDOException si ya existe un pago para ese asociado/año/mes (clave UNIQUE)
     */
    public function registrar(int $asociadoId, int $anio, int $mes, int $registradoPor): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO pagos_cuota (asociado_id, anio, mes, fecha_pago, monto, registrado_por)
             VALUES (:asociado_id, :anio, :mes, CURDATE(), :monto, :registrado_por)'
        );
        $stmt->execute([
            'asociado_id' => $asociadoId,
            'anio' => $anio,
            'mes' => $mes,
            'monto' => self::MONTO_CUOTA,
            'registrado_por' => $registradoPor,
        ]);
    }

    public function eliminar(int $asociadoId, int $anio, int $mes): void
    {
        $stmt = $this->pdo->prepare('DELETE FROM pagos_cuota WHERE asociado_id = :asociado_id AND anio = :anio AND mes = :mes');
        $stmt->execute(['asociado_id' => $asociadoId, 'anio' => $anio, 'mes' => $mes]);
    }
}
