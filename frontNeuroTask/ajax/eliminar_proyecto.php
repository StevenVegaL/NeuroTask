<?php
// ajax/eliminar_proyecto.php
session_start();
require_once '../includes/api.php';

// Verificar si el usuario está autenticado
if (!isset($_SESSION['usuario'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Usuario no autenticado'
    ]);
    exit();
}

// Verificar si se recibió el ID del proyecto
if (!isset($_POST['id']) || empty($_POST['id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'ID de proyecto no proporcionado'
    ]);
    exit();
}

$proyecto_id = $_POST['id'];

// Registrar información para depuración
error_log("Intentando eliminar proyecto con ID: " . $proyecto_id);

try {
    // Llamar a la función que elimina el proyecto
    $resultado = deleteProject($proyecto_id);
    
    // Registrar el resultado para depuración
    error_log("Resultado de eliminación: " . json_encode($resultado));
    
    if (isset($resultado['ok']) && $resultado['ok'] === true) {
        // Proyecto eliminado con éxito
        echo json_encode([
            'ok' => true,
            'mensaje' => 'Proyecto eliminado correctamente'
        ]);
    } else {
        // Error al eliminar el proyecto
        echo json_encode([
            'ok' => false,
            'error' => $resultado['error'] ?? 'Error desconocido al eliminar el proyecto'
        ]);
    }
} catch (Exception $e) {
    // Registrar la excepción para depuración
    error_log("Excepción al eliminar proyecto: " . $e->getMessage());
    
    // Capturar cualquier excepción
    echo json_encode([
        'ok' => false,
        'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}
?>