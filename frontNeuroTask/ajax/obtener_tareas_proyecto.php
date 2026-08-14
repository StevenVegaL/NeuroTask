<?php
// ajax/obtener_tareas_proyecto.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió el ID del proyecto
if (!isset($_GET['proyecto_id']) || empty($_GET['proyecto_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de proyecto no proporcionado']);
    exit();
}

$proyectoId = $_GET['proyecto_id'];

// Obtener tareas del proyecto
$tareasResponse = getTasksByProject($proyectoId);

// Procesar usuarios asignados para cada tarea
if (isset($tareasResponse['ok']) && $tareasResponse['ok'] === true && isset($tareasResponse['tareas'])) {
    $tareas = $tareasResponse['tareas'];
    
    // Obtener detalles de usuarios asignados
    foreach ($tareas as $key => $tarea) {
        if (!empty($tarea['usuario_asignado'])) {
            $userResponse = getUserById($tarea['usuario_asignado']);
            if (isset($userResponse['ok']) && $userResponse['ok'] === true && isset($userResponse['usuario'])) {
                $tareas[$key]['usuario_asignado'] = $userResponse['usuario'];
            }
        }
    }
    
    header('Content-Type: application/json');
    echo json_encode(['ok' => true, 'tareas' => $tareas]);
} else {
    header('Content-Type: application/json');
    echo json_encode($tareasResponse); // Devolver la respuesta original en caso de error
}
?>