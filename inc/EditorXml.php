<?php
declare(strict_types=1);

require_once __DIR__ . '/xml-helpers.php';

/**
 * Clase de utilidades para gestión de XML/DAT.
 *
 * Nota: Durante la transición, estos métodos delegan en los helpers
 * existentes de `xml-helpers.php` para evitar duplicación de lógica.
 * En iteraciones siguientes, se moverá la implementación aquí y se
 * retirarán los helpers globales.
 */
final class EditorXml
{
    /**
     * Asegura que la carpeta de subidas exista.
     */
    public static function asegurarCarpetaUploads(string $dir): void
    {
        asegurarCarpetaUploads($dir);
    }

    /**
     * Carga el XML actual si está disponible en disco y la sesión lo indica.
     * Devuelve SimpleXMLElement o null si no hay XML o si falla la carga.
     */
    public static function cargarXmlSiDisponible(string $xmlFile): ?SimpleXMLElement
    {
        return cargarXmlSiDisponible($xmlFile);
    }

    /**
     * Elimina nodos de texto que contengan solo espacios/saltos de línea.
     */
    public static function limpiarEspaciosEnBlancoDom(DOMDocument $dom): void
    {
        limpiarEspaciosEnBlancoDom($dom);
    }

    /**
     * Crea un archivo de copia de seguridad con extensión .bak si el XML existe.
     */
    public static function crearBackup(string $xmlFile): void
    {
        crearBackup($xmlFile);
    }

    /**
     * Guarda el DOM en disco creando un backup previo si existía el archivo.
     * En caso de fallo, revierte desde el backup y devuelve false.
     */
    public static function guardarDomConBackup(DOMDocument $dom, string $xmlFile): bool
    {
        return guardarDomConBackup($dom, $xmlFile);
    }

    /**
     * Divide una cadena en tokens alfanuméricos en mayúsculas (A-Z0-9).
     */
    public static function tokenizar(string $s): array
    {
        return tokenizar($s);
    }

    /**
     * Comprueba si algún término coincide con el haystack.
     */
    public static function anyTermMatch(array $tokens, string $haystackUpper, array $terms): bool
    {
        return anyTermMatch($tokens, $haystackUpper, $terms);
    }

    /**
     * Mapea selects de regiones a términos de inclusión y de idiomas a términos de exclusión.
     */
    public static function mapearRegionesIdiomas(array $includeRegions, array $excludeLangs, array &$includeTerms, array &$excludeTerms): void
    {
        mapearRegionesIdiomas($includeRegions, $excludeLangs, $includeTerms, $excludeTerms);
    }
}
