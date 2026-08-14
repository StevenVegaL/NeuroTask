<?php
// ajax/crear_proyecto.php
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

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['nombre']) || empty($_POST['nombre']) || !isset($_POST['descripcion'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'El nombre y la descripción son obligatorios'
    ]);
    exit();
}

// Obtener datos del formulario
$nombre = trim($_POST['nombre']);
$descripcion = trim($_POST['descripcion']);
$creador = $_SESSION['usuario']['_id']; // ID del usuario actual
$miembros = []; // Por defecto, sin miembros adicionales

// Registrar información para depuración
error_log("Creando proyecto: Nombre=[{$nombre}], Descripción=[{$descripcion}], Creador=[{$creador}]");

try {
    // Llamar a la función que crea el proyecto
    $resultado = createProject($nombre, $descripcion, $creador, $miembros);
    
    // Registrar el resultado para depuración
    error_log("Resultado de creación: " . json_encode($resultado));
    
    if (isset($resultado['ok']) && $resultado['ok'] === true) {
        // Proyecto creado con éxito
        echo json_encode([
            'ok' => true,
            'proyecto' => $resultado['proyecto'] ?? null,
            'mensaje' => 'Proyecto creado correctamente'
        ]);
    } else {
        // Error al crear el proyecto
        echo json_encode([
            'ok' => false,
            'error' => $resultado['error'] ?? 'Error desconocido al crear el proyecto'
        ]);
    }
} catch (Exception $e) {
    // Registrar la excepción para depuración
    error_log("Excepción al crear proyecto: " . $e->getMessage());
    
    // Capturar cualquier excepción
    echo json_encode([
        'ok' => false,
        'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}
?>