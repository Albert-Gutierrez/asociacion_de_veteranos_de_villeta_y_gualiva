<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\Mailer;
use App\Core\View;
use App\Models\Asociado;
use App\Models\PagoCuota;
use PDOException;

class AsociadoController
{
    public function show(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(404);
            exit('Asociado no encontrado.');
        }

        $asociadoModelo = new Asociado();
        $pagoModelo = new PagoCuota();

        $asociado = $asociadoModelo->buscarPorId($id);
        if (!$asociado) {
            http_response_code(404);
            exit('Asociado no encontrado.');
        }

        $pagos = $pagoModelo->historialPorAsociado($id);

        $pagosPorMes = [];
        $totalPagadoHistorico = 0.0;
        foreach ($pagos as $p) {
            $pagosPorMes[$p['anio'] . '-' . $p['mes']] = $p;
            $totalPagadoHistorico += (float) $p['monto'];
        }

        $ciclo = PagoCuota::obtenerCicloPago();
        $yaPagoCicloActual = isset($pagosPorMes[$ciclo['anio'] . '-' . $ciclo['mes']]);

        // Historial mes a mes: desde su afiliación real (o su inscripción si el
        // tesorero no ha cargado la fecha real) hasta hoy, con tope de los
        // últimos 12 meses para los asociados más antiguos.
        $fechaBase = PagoCuota::fechaBaseCuota($asociado);
        $primerMes = PagoCuota::primerMesElegible($fechaBase);
        $historialMeses = [];
        foreach (PagoCuota::obtenerUltimos12Meses() as $m) {
            if (!PagoCuota::mesEsElegible($m['anio'], $m['mes'], $primerMes)) {
                continue;
            }
            $pago = $pagosPorMes[$m['anio'] . '-' . $m['mes']] ?? null;
            $historialMeses[] = [
                'anio' => $m['anio'],
                'mes' => $m['mes'],
                'pago' => $pago,
            ];
        }

        // Total que debe: TODOS los meses desde su afiliación hasta hoy que no
        // tengan pago registrado (sin tope de 12, a diferencia del historial
        // visible arriba, para que la deuda real no quede subestimada).
        $mesesDebe = 0;
        foreach (PagoCuota::obtenerMesesDesde($primerMes) as $m) {
            if (!isset($pagosPorMes[$m['anio'] . '-' . $m['mes']])) {
                $mesesDebe++;
            }
        }
        $totalDebe = $mesesDebe * PagoCuota::MONTO_CUOTA;

        View::render('admin/asociado', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Detalle del asociado',
            'paginaActiva' => 'dashboard',
            'asociado' => $asociado,
            'pagos' => $pagos,
            'totalPagadoHistorico' => $totalPagadoHistorico,
            'ciclo' => $ciclo,
            'yaPagoCicloActual' => $yaPagoCicloActual,
            'historialMeses' => $historialMeses,
            'mesesDebe' => $mesesDebe,
            'totalDebe' => $totalDebe,
        ]);
    }

    public function actualizarFechaAfiliacion(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        $fecha = trim((string) ($entrada['fecha_afiliacion'] ?? ''));

        if ($asociadoId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $fechaValida = null;
        if ($fecha !== '') {
            $dt = \DateTime::createFromFormat('Y-m-d', $fecha);
            if (!$dt || $dt->format('Y-m-d') !== $fecha) {
                $this->responder(422, false, 'La fecha no es válida.');
            }
            if ($dt > new \DateTime('today')) {
                $this->responder(422, false, 'La fecha de afiliación no puede ser futura.');
            }
            $fechaValida = $fecha;
        }

        $modelo = new Asociado();

        try {
            $filas = $modelo->actualizarFechaAfiliacion($asociadoId, $fechaValida);
        } catch (PDOException $e) {
            error_log('Error actualizando fecha de afiliación: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo actualizar la fecha.');
        }

        if ($filas === 0 && !$modelo->existe($asociadoId)) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }

        $this->responder(200, true, 'Fecha de afiliación actualizada.');
    }

    /**
     * Corrección de datos personales por un administrador/super administrador
     * (el asociado los digitó mal en el formulario público).
     */
    public function actualizarDatos(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        Auth::requerirRolesApi(['administrador', 'super_administrador']);

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        if ($asociadoId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Asociado();
        $asociadoAntes = $modelo->buscarPorId($asociadoId);
        if (!$asociadoAntes) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }

        $nombres = $this->limpiarTexto($entrada['nombres'] ?? '', 100);
        $apellidos = $this->limpiarTexto($entrada['apellidos'] ?? '', 100);
        $cedula = $this->limpiarTexto($entrada['cedula'] ?? '', 20);
        $fechaNacimientoRaw = trim((string) ($entrada['fecha_nacimiento'] ?? ''));
        $telefono = $this->limpiarTexto($entrada['telefono'] ?? '', 20);
        $email = trim((string) ($entrada['email'] ?? ''));
        $direccion = $this->limpiarTexto($entrada['direccion'] ?? '', 255);
        $fuerza = $this->limpiarTexto($entrada['fuerza'] ?? '', 100);
        $mensaje = $this->limpiarTexto($entrada['mensaje'] ?? '', 2000);

        $errores = [];
        if ($nombres === '') {
            $errores[] = 'El nombre es obligatorio.';
        }
        if ($apellidos === '') {
            $errores[] = 'El apellido es obligatorio.';
        }
        if (!preg_match('/^[0-9]{5,20}$/', $cedula)) {
            $errores[] = 'La cédula debe contener solo números (5 a 20 dígitos).';
        }
        $fechaNacimiento = null;
        if ($fechaNacimientoRaw !== '') {
            $fecha = \DateTime::createFromFormat('Y-m-d', $fechaNacimientoRaw);
            if (!$fecha || $fecha->format('Y-m-d') !== $fechaNacimientoRaw) {
                $errores[] = 'La fecha de nacimiento no es válida.';
            } else {
                $fechaNacimiento = $fechaNacimientoRaw;
            }
        }
        if (!preg_match('/^[0-9+()\s-]{7,20}$/', $telefono)) {
            $errores[] = 'El teléfono no es válido.';
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $errores[] = 'El correo electrónico no es válido.';
        }
        if (!in_array($fuerza, Asociado::FUERZAS_VALIDAS, true)) {
            $errores[] = 'Selecciona una fuerza válida de la lista.';
        }

        if ($errores !== []) {
            $this->responder(422, false, implode(' ', $errores));
        }

        $otroConEsaCedula = $modelo->buscarPorCedula($cedula);
        if ($otroConEsaCedula && (int) $otroConEsaCedula['id'] !== $asociadoId) {
            $this->responder(409, false, 'Ya existe otro asociado registrado con ese número de cédula.');
        }
        $otroConEseEmail = $modelo->buscarPorEmail($email);
        if ($otroConEseEmail && (int) $otroConEseEmail['id'] !== $asociadoId) {
            $this->responder(409, false, 'Ya existe otro asociado registrado con ese correo electrónico.');
        }

        try {
            $modelo->actualizarDatos($asociadoId, [
                'nombres' => $nombres,
                'apellidos' => $apellidos,
                'cedula' => $cedula,
                'fecha_nacimiento' => $fechaNacimiento,
                'telefono' => $telefono,
                'email' => $email,
                'direccion' => $direccion !== '' ? $direccion : null,
                'fuerza' => $fuerza,
                'mensaje' => $mensaje !== '' ? $mensaje : null,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                $this->responder(409, false, 'Ya existe otro asociado registrado con ese número de cédula.');
            }
            error_log('Error actualizando datos del asociado: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo guardar los cambios.');
        }

        // Si le corrigieron el correo (es su usuario de acceso al portal) y ya
        // tenía una contraseña activa, esa contraseña queda huérfana: se
        // invalida y se genera una nueva para el correo correcto.
        $passwordTemporal = null;
        if ($email !== $asociadoAntes['email'] && !empty($asociadoAntes['password_hash'])) {
            $asociadoActualizado = $modelo->buscarPorId($asociadoId);
            [, $passwordTemporal] = $this->activarAccesoYNotificar($modelo, $asociadoId, $asociadoActualizado);
        }

        $mensajeExito = 'Datos actualizados correctamente.'
            . ($passwordTemporal !== null ? ' Como cambió el correo, se generó una nueva contraseña de acceso al portal.' : '');

        $this->responder(200, true, $mensajeExito, $passwordTemporal !== null
            ? ['password_temporal_portal' => $passwordTemporal]
            : []);
    }

    public function actualizarEstado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        Auth::requerirRolesApi(['administrador', 'super_administrador']);

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        $estado = (string) ($entrada['estado'] ?? '');

        $estadosValidos = ['pendiente', 'aprobado', 'rechazado', 'inactivo'];
        if ($asociadoId <= 0 || !in_array($estado, $estadosValidos, true)) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Asociado();
        $asociadoAntes = $modelo->buscarPorId($asociadoId);

        try {
            $filas = $modelo->actualizarEstado($asociadoId, $estado);
        } catch (PDOException $e) {
            error_log('Error actualizando estado: ' . $e->getMessage());
            $this->responder(500, false, 'No se pudo actualizar el estado.');
        }

        if ($filas === 0 && !$modelo->existe($asociadoId)) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }

        // Al aprobar por primera vez (todavía sin acceso al portal), se activa
        // y se le envía la contraseña temporal automáticamente.
        $passwordTemporal = null;
        if ($estado === 'aprobado' && $asociadoAntes && empty($asociadoAntes['password_hash'])) {
            [, $passwordTemporal] = $this->activarAccesoYNotificar($modelo, $asociadoId, $asociadoAntes);
        }

        $this->responder(200, true, 'Estado actualizado.', $passwordTemporal !== null
            ? ['password_temporal_portal' => $passwordTemporal]
            : []);
    }

    /**
     * Botón manual: enviar/restablecer el acceso al portal de un asociado
     * ya aprobado (primera activación si nunca la tuvo, o reseteo si se
     * bloqueó/olvidó su contraseña).
     */
    public function generarAccesoAfiliado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $asociadoId = (int) ($entrada['asociado_id'] ?? 0);
        if ($asociadoId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Asociado();
        $asociado = $modelo->buscarPorId($asociadoId);
        if (!$asociado) {
            $this->responder(404, false, 'Asociado no encontrado.');
        }
        if ($asociado['estado'] !== 'aprobado') {
            $this->responder(422, false, 'Solo los asociados aprobados pueden tener acceso al portal.');
        }

        [$enviado, $passwordTemporal] = $this->activarAccesoYNotificar($modelo, $asociadoId, $asociado);

        $mensaje = $enviado
            ? 'Se generó y envió la contraseña de acceso por correo. También la ves abajo por si prefieres dársela tú mismo.'
            : 'Se generó la contraseña, pero no se pudo enviar el correo (revisa la configuración de SMTP). Dásela tú mismo.';

        $this->responder(200, true, $mensaje, ['password_temporal' => $passwordTemporal]);
    }

    /**
     * @return array{0: bool, 1: string} [se_envio_el_correo, password_en_texto_plano]
     */
    private function activarAccesoYNotificar(Asociado $modelo, int $asociadoId, array $asociado): array
    {
        $passwordTemporal = Auth::generarPasswordTemporal();
        $modelo->activarAcceso($asociadoId, password_hash($passwordTemporal, PASSWORD_DEFAULT));

        $cuerpo = '<p>Hola ' . htmlspecialchars($asociado['nombres'], ENT_QUOTES, 'UTF-8') . ',</p>'
            . '<p>Ya puedes ingresar al portal de afiliados de ASOVEGU para ver tu información y el estado de tu cuota.</p>'
            . '<p>Tu correo de acceso es: <strong>' . htmlspecialchars($asociado['email'], ENT_QUOTES, 'UTF-8') . '</strong></p>'
            . '<p>Tu contraseña temporal es:</p>'
            . '<p style="font-size:20px;font-weight:bold;letter-spacing:1px;">' . htmlspecialchars($passwordTemporal, ENT_QUOTES, 'UTF-8') . '</p>'
            . '<p>Al ingresar con ella te pediremos que la cambies de inmediato.</p>';

        $enviado = Mailer::enviar($asociado['email'], 'Acceso al portal de afiliados - ASOVEGU', $cuerpo);

        return [$enviado, $passwordTemporal];
    }

    /**
     * @param mixed $valor
     */
    private function limpiarTexto($valor, int $maxLength): string
    {
        $valor = trim((string) ($valor ?? ''));
        $valor = strip_tags($valor);
        return mb_substr($valor, 0, $maxLength);
    }

    private function responder(int $codigo, bool $exito, string $mensaje, array $extra = []): void
    {
        http_response_code($codigo);
        echo json_encode(array_merge(['exito' => $exito, 'mensaje' => $mensaje], $extra), JSON_UNESCAPED_UNICODE);
        exit;
    }
}
