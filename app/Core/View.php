<?php

declare(strict_types=1);

namespace App\Core;

class View
{
    /**
     * Extrae $datos a variables locales e incluye la vista. La vista es
     * responsable de su propio layout (igual que antes: cada vista de admin
     * llama a layout_inicio.php / layout_fin.php).
     *
     * @param array<string, mixed> $datos
     */
    public static function render(string $vista, array $datos = []): void
    {
        extract($datos, EXTR_SKIP);
        require __DIR__ . '/../Views/' . $vista . '.php';
    }

    /**
     * Igual que render(), pero devuelve el HTML como string en vez de
     * imprimirlo directamente (para generar PDFs con Dompdf, por ejemplo).
     *
     * @param array<string, mixed> $datos
     */
    public static function renderToString(string $vista, array $datos = []): string
    {
        ob_start();
        self::render($vista, $datos);
        return (string) ob_get_clean();
    }
}
