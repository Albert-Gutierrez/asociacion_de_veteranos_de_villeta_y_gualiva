<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\AuthAfiliado;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Testimonio;

class TestimonioController
{
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    /**
     * Crear/actualizar el testimonio del afiliado (multipart/form-data,
     * porque puede incluir una foto). Vuelve a quedar "pendiente" cada vez.
     */
    public function guardar(): void
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
            $this->responder(422, false, 'Escribe tu testimonio antes de enviarlo.');
        }
        if (mb_strlen($mensaje) > 500) {
            $mensaje = mb_substr($mensaje, 0, 500);
        }

        $modelo = new Testimonio();
        $existente = $modelo->buscarPorAsociado($afiliado['id']);
        $fotoRuta = $existente['foto_ruta'] ?? null;

        if (!empty($_FILES['foto']['tmp_name']) && $_FILES['foto']['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($_FILES['foto']['error'] !== UPLOAD_ERR_OK) {
                $this->responder(422, false, 'No se pudo subir la foto. Intenta de nuevo.');
            }
            if ($_FILES['foto']['size'] > self::TAMANO_MAXIMO) {
                $this->responder(422, false, 'La foto no puede pesar más de 5 MB.');
            }

            $info = @getimagesize($_FILES['foto']['tmp_name']);
            if ($info === false || !isset(self::TIPOS_PERMITIDOS[$info['mime']])) {
                $this->responder(422, false, 'El archivo debe ser una imagen JPG, PNG o WEBP.');
            }

            $extension = self::TIPOS_PERMITIDOS[$info['mime']];
            $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
            $carpetaDestino = __DIR__ . '/../../uploads/testimonios/';
            if (!is_dir($carpetaDestino)) {
                mkdir($carpetaDestino, 0755, true);
            }

            if (!move_uploaded_file($_FILES['foto']['tmp_name'], $carpetaDestino . $nombreArchivo)) {
                $this->responder(500, false, 'No se pudo guardar la foto.');
            }

            // Se reemplaza la foto anterior (si había) para no acumular archivos huérfanos.
            if ($fotoRuta && is_file(__DIR__ . '/../../uploads/' . $fotoRuta)) {
                @unlink(__DIR__ . '/../../uploads/' . $fotoRuta);
            }

            $fotoRuta = 'testimonios/' . $nombreArchivo;
        }

        $modelo->guardar($afiliado['id'], $mensaje, $fotoRuta);

        $this->responder(200, true, 'Tu testimonio fue enviado. Se publicará cuando un administrador lo revise.');
    }

    /**
     * Página de administración: lista de testimonios para aprobar/rechazar
     * (solo administrador/super administrador, igual que aprobar asociados).
     */
    public function index(): void
    {
        $usuario = Auth::requerirRoles(['administrador', 'super_administrador']);
        $csrf = Csrf::token();

        $filtro = $_GET['estado'] ?? null;
        if (!in_array($filtro, ['pendiente', 'aprobado', 'rechazado'], true)) {
            $filtro = null;
        }

        $modelo = new Testimonio();
        $testimonios = $modelo->listarTodos($filtro);

        View::render('admin/testimonios', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Testimonios',
            'paginaActiva' => 'testimonios',
            'testimonios' => $testimonios,
            'filtro' => $filtro,
        ]);
    }

    public function actualizarEstado(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirRolesApi(['administrador', 'super_administrador']);

        $entrada = json_decode(file_get_contents('php://input'), true);
        if (!is_array($entrada)) {
            $this->responder(400, false, 'Solicitud inválida.');
        }

        Csrf::requerirApi($entrada['csrf_token'] ?? null);

        $testimonioId = (int) ($entrada['testimonio_id'] ?? 0);
        $estado = (string) ($entrada['estado'] ?? '');

        if ($testimonioId <= 0 || !in_array($estado, ['aprobado', 'rechazado'], true)) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Testimonio();
        if (!$modelo->buscarPorId($testimonioId)) {
            $this->responder(404, false, 'Testimonio no encontrado.');
        }

        $modelo->actualizarEstado($testimonioId, $estado, $usuario['id']);

        $this->responder(200, true, $estado === 'aprobado' ? 'Testimonio aprobado y publicado.' : 'Testimonio rechazado.');
    }

    /**
     * JSON público (sin autenticación) con los testimonios aprobados, para
     * alimentar el carrusel de quienes-somos.html.
     */
    public function publicos(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');

        $modelo = new Testimonio();
        $testimonios = array_map(function (array $t): array {
            return [
                'nombre' => trim($t['nombres'] . ' ' . $t['apellidos']),
                'mensaje' => $t['mensaje'],
                'foto' => $t['foto_ruta'] ? 'ver_foto_testimonio.php?ruta=' . urlencode($t['foto_ruta']) : null,
            ];
        }, $modelo->listarAprobadosPublico());

        echo json_encode(['testimonios' => $testimonios], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Sirve una foto de testimonio. Las aprobadas son públicas (es contenido
     * que ya se decidió mostrar en el sitio); las demás solo las ve el
     * staff o el propio afiliado dueño, igual que las imágenes de tickets.
     */
    public function foto(): void
    {
        $ruta = (string) ($_GET['ruta'] ?? '');
        if ($ruta === '' || str_contains($ruta, '..')) {
            http_response_code(400);
            exit('Solicitud inválida.');
        }

        $modelo = new Testimonio();
        $testimonio = $modelo->buscarPorFotoRuta($ruta);

        if (!$testimonio) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }

        if ($testimonio['estado'] !== 'aprobado') {
            $esStaff = Auth::usuarioActual() !== null;
            $afiliado = AuthAfiliado::afiliadoActual();
            $esDueno = $afiliado !== null && $afiliado['id'] === (int) $testimonio['asociado_id'];
            if (!$esStaff && !$esDueno) {
                http_response_code(403);
                exit('No autorizado.');
            }
        }

        $rutaCompleta = __DIR__ . '/../../uploads/' . $ruta;
        if (!is_file($rutaCompleta)) {
            http_response_code(404);
            exit('Imagen no encontrada.');
        }

        header('Content-Type: ' . (mime_content_type($rutaCompleta) ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($rutaCompleta));
        header('Cache-Control: public, max-age=3600');
        readfile($rutaCompleta);
        exit;
    }

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
