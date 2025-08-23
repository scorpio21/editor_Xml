<?php
declare(strict_types=1);

/**
 * Funciones específicas para filtros MAME
 */

/**
 * Aplica filtros MAME específicos a un elemento machine
 * @param DOMElement $element Elemento machine
 * @param array $filters Filtros MAME a aplicar
 * @return bool true si el elemento pasa los filtros MAME
 */
function aplicarFiltrosMame(DOMElement $element, array $filters): bool
{
    // Solo aplicar a elementos machine
    if ($element->tagName !== 'machine') {
        return true;
    }
    
    // Filtros de exclusión MAME
    if (!empty($filters['mame_filters'])) {
        // Filtro BIOS
        if (in_array('bios', $filters['mame_filters'], true)) {
            if ($element->getAttribute('isbios') === 'yes') {
                return false;
            }
        }
        
        // Filtro dispositivos
        if (in_array('device', $filters['mame_filters'], true)) {
            if ($element->getAttribute('isdevice') === 'yes') {
                return false;
            }
        }
    }
    
    // Filtro de estado del driver
    if (!empty($filters['mame_driver_status'])) {
        $driverNode = $element->getElementsByTagName('driver')->item(0);
        if ($driverNode) {
            $driverStatus = $driverNode->getAttribute('status');
            if ($driverStatus !== $filters['mame_driver_status']) {
                return false;
            }
        } else {
            // Si no hay nodo driver y se requiere un estado específico, excluir
            return false;
        }
    }
    
    // Filtro de clones
    if (!empty($filters['mame_clone_filter'])) {
        $cloneof = $element->getAttribute('cloneof');
        if ($filters['mame_clone_filter'] === 'parent_only' && !empty($cloneof)) {
            return false;
        } elseif ($filters['mame_clone_filter'] === 'clone_only' && empty($cloneof)) {
            return false;
        }
    }
    
    return true;
}

/**
 * Sanitiza texto de entrada
 * @param string $texto Texto a sanitizar
 * @return string Texto sanitizado
 */
function sanitizarTexto(string $texto): string
{
    return trim(htmlspecialchars($texto, ENT_QUOTES, 'UTF-8'));
}

/**
 * Verifica token CSRF para acciones POST
 * @return bool true si el token es válido
 */
function verificarCSRFParaAccion(): bool
{
    if (!isset($_POST['csrf_token']) || !isset($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $_POST['csrf_token']);
}

/**
 * Obtiene texto combinado para búsqueda de un elemento
 * @param SimpleXMLElement $element Elemento game o machine
 * @param string $type Tipo de elemento ('game' o 'machine')
 * @return string Texto combinado para búsqueda
 */
function obtenerTextoParaBusqueda($element, string $type): string
{
    $name = (string)$element['name'];
    $desc = (string)$element->description;
    
    if ($type === 'game') {
        $category = (string)$element->category;
        return $name . ' ' . $desc . ' ' . $category;
    } elseif ($type === 'machine') {
        $year = (string)$element['year'];
        $manufacturer = (string)$element['manufacturer'];
        return $name . ' ' . $desc . ' ' . $year . ' ' . $manufacturer;
    }
    
    return $name . ' ' . $desc;
}

/**
 * Procesa los filtros MAME desde POST
 * @return array Filtros MAME procesados
 */
function procesarFiltrosMame(): array
{
    $mameFilters = [];
    if (!empty($_POST['mame_filter_bios'])) $mameFilters[] = 'bios';
    if (!empty($_POST['mame_filter_device'])) $mameFilters[] = 'device';
    
    return [
        'mame_filters' => $mameFilters,
        'mame_driver_status' => sanitizarTexto($_POST['mame_driver_status'] ?? ''),
        'mame_clone_filter' => sanitizarTexto($_POST['mame_clone_filter'] ?? '')
    ];
}
