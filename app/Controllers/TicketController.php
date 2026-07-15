<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Ticket;

class TicketController
{
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Crear un ticket desde el portal del afiliado (multipart/form-data,
     * porque puede incluir una imagen).
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $afiliado = AuthAfiliado::requerirSesionApi();

        Csrf::requerirApi($_POST['csrf_token'] ?? null);

        $mensaje = trim((string) ($_POST['mensaje'] ?? ''));
        if ($mensaje === '') {
            $this->responder(422, false, 'Cuéntanos qué pago realizaste.');
        }
        if (mb_strlen($mensaje) > 2000) {
            $mensaje = mb_substr($mensaje, 0, 2000);
        }

        $imagenRuta = null;

        if (!empty($_FILES['imagen']['tmp_name']) && $_FILES['imagen']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['imagen']['error'] !== UPLOAD_ERR_OK) {
                $this->responder(422, false, 'No se pudo subir la imagen. Intenta de nuevo.');
            }
            if ($_FILES['imagen']['size'] > self::TAMANO_MAXIMO) {
                $this->responder(422, false, 'La imagen no puede pesar más de 5 MB.');
            }

            // Se valida el contenido real del archivo (no solo la extensión ni el
            // Content-Type que manda el navegador), para evitar que alguien suba
            // un archivo ejecutable disfrazado de imagen.
            $info = @getimagesize($_FILES['imagen']['tmp_name']);
            if ($info === false || !isset(self::TIPOS_PERMITIDOS[$info['mime']])) {
                $this->responder(422, false, 'El archivo debe ser una imagen JPG, PNG o WEBP.');
            }

            $extension = self::TIPOS_PERMITIDOS[$info['mime']];
            $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
            $carpetaDestino = __DIR__ . '/../../uploads/tickets/';
            if (!is_dir($carpetaDestino)) {
                mkdir($carpetaDestino, 0755, true);
            }

            if (!move_uploaded_file($_FILES['imagen']['tmp_name'], $carpetaDestino . $nombreArchivo)) {
                $this->responder(500, false, 'No se pudo guardar la imagen.');
            }

            $imagenRuta = 'tickets/' . $nombreArchivo;
        }

        $modelo = new Ticket();
        $modelo->crear($afiliado['id'], $mensaje, $imagenRuta);

        $this->responder(200, true, 'Tu reporte fue enviado. La asociación lo revisará pronto.');
    }

    /**
     * Página de administración: lista de tickets (los 3 roles que gestionan
     * pagos pueden verla, sin restricción adicional de rol).
     */
    public function index(): void
    {
        $usuario = Auth::requerirSesion();
        $csrf = Csrf::token();

        $filtro = $_GET['estado'] ?? null;
        if (!in_array($filtro, ['abierto', 'resuelto'], true)) {
            $filtro = null;
        }

        $modelo = new Ticket();
        $tickets = $modelo->listarTodos($filtro);

        View::render('admin/tickets', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Tickets de asociados',
            'paginaActiva' => 'tickets',
            'tickets' => $tickets,
            'filtro' => $filtro,
        ]);
    }

    public function resolver(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirSesionApi();

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $ticketId = (int) ($entrada['ticket_id'] ?? 0);
        $respuesta = trim((string) ($entrada['respuesta'] ?? ''));

        if ($ticketId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Ticket();
        if (!$modelo->buscarPorId($ticketId)) {
            $this->responder(404, false, 'Ticket no encontrado.');
        }

        $modelo->marcarResuelto($ticketId, $respuesta !== '' ? $respuesta : null, $usuario['id']);

        $this->responder(200, true, 'Ticket marcado como resuelto.');
    }

    /**
     * Sirve la imagen de un ticket solo si quien la pide es staff logueado
     * o el propio afiliado dueño del ticket — nunca se expone como archivo
     * público directo (las capturas de pago pueden traer datos bancarios).
     */
    public function imagen(): void
    {
        $ticketId = (int) ($_GET['ticket_id'] ?? 0);
        if ($ticketId <= 0) {
            http_response_code(400);
            exit('Solicitud inválida.');
        }

        $modelo = new Ticket();
        $ticket = $modelo->buscarPorId($ticketId);
        if (!$ticket || !$ticket['imagen_ruta']) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }

        $esStaff = Auth::usuarioActual() !== null;
        $afiliado = AuthAfiliado::afiliadoActual();
        $esDueno = $afiliado !== null && $afiliado['id'] === (int) $ticket['asociado_id'];

        if (!$esStaff && !$esDueno) {
            http_response_code(403);
            exit('No autorizado.');
        }

        $ruta = __DIR__ . '/../../uploads/' . $ticket['imagen_ruta'];
        if (!is_file($ruta)) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }

        header('Content-Type: ' . (mime_content_type($ruta) ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($ruta));
        header('Cache-Control: private, max-age=3600');
        readfile($ruta);
        exit;
    }

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
