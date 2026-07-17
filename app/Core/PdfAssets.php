<?php

declare(strict_types=1);

namespace App\Core;

/**
 * Imágenes reutilizadas en los PDF generados con Dompdf, incrustadas como
 * data URI base64 (la forma más confiable de que Dompdf las renderice, sin
 * depender de rutas de archivo ni de tener habilitado el acceso remoto).
 */
class PdfAssets
{
    public static function escudoDataUri(): string
    {
        return self::dataUri(__DIR__ . '/../../img/escudo sin fondo.png', 'image/png');
    }

    /**
     * @return string[] las 4 imágenes de fuerza, en el mismo orden en que se
     *                   usan como fondo difuminado en el sitio público.
     */
    public static function emblemasDataUri(): array
    {
        return array_filter([
            self::dataUri(__DIR__ . '/../../img/fondo_ejercito.svg', 'image/svg+xml'),
            self::dataUri(__DIR__ . '/../../img/fondo_policia.svg', 'image/svg+xml'),
            self::dataUri(__DIR__ . '/../../img/fondo_armada.svg', 'image/svg+xml'),
            self::dataUri(__DIR__ . '/../../img/fondo_fuerza_aerea.svg', 'image/svg+xml'),
        ]);
    }

    private static function dataUri(string $ruta, string $mime): string
    {
        if (!is_file($ruta)) {
            return '';
        }
        return 'data:' . $mime . ';base64,' . base64_encode((string) file_get_contents($ruta));
    }
}
