<?php

declare(strict_types=1);

namespace App\Controllers;

use App\Core\Auth;
use App\Core\Csrf;
use App\Models\Actividad;

class ActividadController
{
    private const TAMANO_MAXIMO = 5 * 1024 * 1024; // 5 MB por imagen

    private const TIPOS_PERMITIDOS = [
        'image/jpeg' => 'jpg',
        'image/png' => 'png',
        'image/webp' => 'webp',
    ];

    private const CARPETA = __DIR__ . '/../../img/actividades/';

    /**
     * Publica una actividad (solo administrador/super administrador): imagen
     * de portada para la tarjeta de actividades.html, más hasta 20 imágenes
     * de galería para el modal "Ver más".
     */
    public function crear(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        ini_set('display_errors', '0');

        if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
            $this->responder(405, false, 'Método no permitido.');
        }

        $usuario = Auth::requerirRolesApi(['administrador', 'super_administrador']);

        // Si el POST llegó vacío pero el navegador sí envió contenido, es que
        // se superó post_max_size y PHP descartó todo silenciosamente.
        if ($_POST === [] && $_FILES === [] && (int) ($_SERVER['CONTENT_LENGTH'] ?? 0) > 0) {
            $this->responder(413, false, 'Las imágenes pesan demasiado en conjunto. Sube menos imágenes o de menor tamaño.');
        }

        Csrf::requerirApi($_POST['csrf_token'] ?? null);

        $titulo = $this->limpiarTexto($_POST['titulo'] ?? '', 150);
        $descripcion = $this->limpiarTexto($_POST['descripcion'] ?? '', 2000);

        if ($titulo === '') {
            $this->responder(422, false, 'El título es obligatorio.');
        }
        if ($descripcion === '') {
            $this->responder(422, false, 'La descripción es obligatoria.');
        }
        if (empty($_FILES['imagen_portada']['tmp_name']) || $_FILES['imagen_portada']['error'] === UPLOAD_ERR_NO_FILE) {
            $this->responder(422, false, 'La imagen de portada es obligatoria.');
        }

        $galeria = $_FILES['imagenes'] ?? null;
        $indicesGaleria = [];
        if ($galeria && is_array($galeria['name'] ?? null)) {
            foreach ($galeria['error'] as $i => $err) {
                if ($err !== UPLOAD_ERR_NO_FILE) {
                    $indicesGaleria[] = $i;
                }
            }
        }
        if (count($indicesGaleria) > Actividad::MAX_IMAGENES_GALERIA) {
            $this->responder(422, false, 'Puedes subir máximo ' . Actividad::MAX_IMAGENES_GALERIA . ' imágenes de galería por actividad.');
        }

        $rutaPortada = $this->guardarImagen($_FILES['imagen_portada']);
        if ($rutaPortada === null) {
            $this->responder(422, false, 'La imagen de portada debe ser JPG, PNG o WEBP (máx. 5 MB).');
        }

        $modelo = new Actividad();
        $actividadId = $modelo->crear($titulo, $descripcion, $rutaPortada, $usuario['id']);

        $orden = 0;
        $fallidas = 0;
        foreach ($indicesGaleria as $i) {
            $archivo = [
                'name' => $galeria['name'][$i],
                'type' => $galeria['type'][$i],
                'tmp_name' => $galeria['tmp_name'][$i],
                'error' => $galeria['error'][$i],
                'size' => $galeria['size'][$i],
            ];
            $ruta = $this->guardarImagen($archivo);
            if ($ruta === null) {
                $fallidas++;
                continue;
            }
            $modelo->agregarImagen($actividadId, $ruta, $orden);
            $orden++;
        }

        $mensaje = 'Actividad publicada correctamente.';
        if ($fallidas > 0) {
            $mensaje .= ' ' . $fallidas . ' imagen(es) de la galería no se pudieron subir (formato o tamaño inválido).';
        }

        $this->responder(200, true, $mensaje);
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

        $actividadId = (int) ($entrada['actividad_id'] ?? 0);
        if ($actividadId <= 0) {
            $this->responder(422, false, 'Datos inválidos.');
        }

        $modelo = new Actividad();
        $actividad = $modelo->buscarPorId($actividadId);
        if (!$actividad) {
            $this->responder(404, false, 'Actividad no encontrada.');
        }

        $imagenes = $modelo->obtenerImagenes($actividadId);
        $modelo->eliminar($actividadId);

        $this->borrarArchivo($actividad['imagen_portada']);
        foreach ($imagenes as $img) {
            $this->borrarArchivo($img['imagen_ruta']);
        }

        $this->responder(200, true, 'Actividad eliminada.');
    }

    /**
     * JSON público (sin autenticación) para la galería dinámica de
     * actividades.html: portada de cada tarjeta + galería completa para el
     * modal "Ver más".
     */
    public function publicas(): void
    {
        header('Content-Type: application/json; charset=utf-8');
        header('Cache-Control: public, max-age=300');

        $modelo = new Actividad();
        $actividades = $modelo->listarTodas();

        $resultado = array_map(function (array $a) use ($modelo): array {
            $imagenes = $modelo->obtenerImagenes((int) $a['id']);
            return [
                'id' => (int) $a['id'],
                'titulo' => $a['titulo'],
                'descripcion' => $a['descripcion'],
                'imagen_portada' => 'img/actividades/' . $a['imagen_portada'],
                'imagenes' => array_map(fn (array $i): string => 'img/actividades/' . $i['imagen_ruta'], $imagenes),
            ];
        }, $actividades);

        echo json_encode(['actividades' => $resultado], JSON_UNESCAPED_UNICODE);
    }

    private function guardarImagen(array $archivo): ?string
    {
        if (empty($archivo['tmp_name']) || $archivo['error'] !== UPLOAD_ERR_OK) {
            return null;
        }
        if ($archivo['size'] > self::TAMANO_MAXIMO) {
            return null;
        }

        $info = @getimagesize($archivo['tmp_name']);
        if ($info === false || !isset(self::TIPOS_PERMITIDOS[$info['mime']])) {
            return null;
        }

        $extension = self::TIPOS_PERMITIDOS[$info['mime']];
        $nombreArchivo = bin2hex(random_bytes(16)) . '.' . $extension;

        if (!is_dir(self::CARPETA)) {
            mkdir(self::CARPETA, 0755, true);
        }
        if (!move_uploaded_file($archivo['tmp_name'], self::CARPETA . $nombreArchivo)) {
            return null;
        }

        return $nombreArchivo;
    }

    private function borrarArchivo(string $nombreArchivo): void
    {
        $ruta = self::CARPETA . $nombreArchivo;
        if (is_file($ruta)) {
            @unlink($ruta);
        }
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

    private function responder(int $codigo, bool $exito, string $mensaje): void
    {
        http_response_code($codigo);
        echo json_encode(['exito' => $exito, 'mensaje' => $mensaje], JSON_UNESCAPED_UNICODE);
        exit;
    }
}
