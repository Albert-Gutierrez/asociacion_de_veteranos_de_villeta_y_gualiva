<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Core\View;
use App\Models\Actividad;
use App\Models\Documento;

class DocumentoController
{
    private const TAMANO_MAXIMO = 10 * 1024 * 1024; // 10 MB

    private const TIPOS_PERMITIDOS = [
        'application/pdf' => 'pdf',
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
    ];

    /**
     * Página de administración "Doc e imágenes": dos columnas, documentos
     * públicos (izquierda) y actividades con galería de fotos (derecha,
     * ver ActividadController).
     */
    public function index(): void
    {
        $usuario = Auth::requerirRoles(['administrador', 'super_administrador']);
        $csrf = Csrf::token();

        $modelo = new Documento();
        $actividadModelo = new Actividad();

        View::render('admin/documentos', [
            'usuario' => $usuario,
            'csrf' => $csrf,
            'tituloPagina' => 'Doc e imágenes',
            'paginaActiva' => 'documentos',
            'documentos' => $modelo->listarTodos(),
            'actividades' => $actividadModelo->listarTodas(),
        ]);
    }

    public function subir(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirRolesApi(['administrador', 'super_administrador']);

        Csrf::requerirApi($_POST['csrf_token'] ?? null);

        $titulo = trim((string) ($_POST['titulo'] ?? ''));
        if ($titulo === '') {
            $this->responder(422, false, 'El título del documento es obligatorio.');
        }
        if (mb_strlen($titulo) > 150) {
            $titulo = mb_substr($titulo, 0, 150);
        }

        if (empty($_FILES['archivo']['tmp_name']) || $_FILES['archivo']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->responder(422, false, 'Selecciona un archivo para subir.');
        }
        if ($_FILES['archivo']['error'] !== UPLOAD_ERR_OK) {
            $this->responder(422, false, 'No se pudo subir el archivo. Intenta de nuevo.');
        }
        if ($_FILES['archivo']['size'] > self::TAMANO_MAXIMO) {
            $this->responder(422, false, 'El archivo no puede pesar más de 10 MB.');
        }

        // Se valida el contenido real del archivo (no la extensión ni el
        // Content-Type que manda el navegador), para evitar que alguien suba
        // un archivo ejecutable disfrazado de PDF/imagen.
        $finfo = finfo_open(FILEINFO_MIME_TYPE);
        $mime = finfo_file($finfo, $_FILES['archivo']['tmp_name']);
        finfo_close($finfo);

        if ($mime === false || !isset(self::TIPOS_PERMITIDOS[$mime])) {
            $this->responder(422, false, 'El archivo debe ser un PDF, JPG o PNG.');
        }

        $extension = self::TIPOS_PERMITIDOS[$mime];
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;
        $carpetaDestino = __DIR__ . '/../../uploads/documentos/';
        if (!is_dir($carpetaDestino)) {
            mkdir($carpetaDestino, 0755, true);
        }

        if (!move_uploaded_file($_FILES['archivo']['tmp_name'], $carpetaDestino . $nombreArchivo)) {
            $this->responder(500, false, 'No se pudo guardar el archivo.');
        }

        $nombreOriginal = basename((string) ($_FILES['archivo']['name'] ?? 'documento.' . $extension));

        $modelo = new Documento();
        $modelo->crear($titulo, $nombreArchivo, $nombreOriginal, $usuario['id']);

        $this->responder(200, true, 'Documento subido y publicado.');
    }

    public function eliminar(): void
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

        $documentoId = (int) ($entrada['documento_id'] ?? 0);
        if ($documentoId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Documento();
        $documento = $modelo->buscarPorId($documentoId);
        if (!$documento) {
            $this->responder(404, false, 'Documento no encontrado.');
        }

        $modelo->eliminar($documentoId);

        $rutaArchivo = __DIR__ . '/../../uploads/documentos/' . $documento['archivo_ruta'];
        if (is_file($rutaArchivo)) {
            @unlink($rutaArchivo);
        }

        $this->responder(200, true, 'Documento eliminado.');
    }

    /**
     * JSON público (sin autenticación) con los documentos disponibles, para
     * la sección "Documentos públicos" de quienes-somos.html.
     */
    public function publicos(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');

        $modelo = new Documento();
        $documentos = array_map(function (array $d): array {
            return [
                'id' => (int) $d['id'],
                'titulo' => $d['titulo'],
                'url' => 'descargar_documento.php?id=' . (int) $d['id'],
                'tipo' => strtolower(pathinfo($d['archivo_ruta'], PATHINFO_EXTENSION)),
            ];
        }, $modelo->listarTodos());

        echo json_encode(['documentos' => $documentos], JSON_UNESCAPED_UNICODE);
    }

    /**
     * Descarga pública de un documento (son documentos institucionales que
     * la asociación decide publicar; no requieren sesión).
     */
    public function descargar(): void
    {
        $id = (int) ($_GET['id'] ?? 0);
        if ($id <= 0) {
            http_response_code(400);
            exit('Solicitud inválida.');
        }

        $modelo = new Documento();
        $documento = $modelo->buscarPorId($id);
        if (!$documento) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }

        $ruta = __DIR__ . '/../../uploads/documentos/' . $documento['archivo_ruta'];
        if (!is_file($ruta)) {
            http_response_code(404);
            exit('Documento no encontrado.');
        }

        header('Content-Type: ' . (mime_content_type($ruta) ?: 'application/octet-stream'));
        header('Content-Length: ' . (string) filesize($ruta));
        header('Content-Disposition: inline; filename="' . basename($documento['archivo_nombre_original']) . '"');
        header('Cache-Control: public, max-age=3600');
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
