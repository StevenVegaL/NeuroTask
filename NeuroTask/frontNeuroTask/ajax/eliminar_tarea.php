<?php
// ajax/eliminar_tarea.php - Versión final
session_start();
require_once '../includes/api.php';

// Función para depuración
function logDebug($message, $data = null) {
    $logMessage = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $logMessage .= ": " . (is_string($data) ? $data : json_encode($data));
    }
    error_log($logMessage);
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió el ID de la tarea
if (!isset($_POST['id']) || empty($_POST['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de tarea requerido']);
    exit();
}

$tareaId = $_POST['id'];
logDebug("Intentando eliminar tarea con ID", $tareaId);

// Obtener primero los detalles de la tarea para usar el título en la eliminación
$tareaResponse = getTaskById($tareaId);
logDebug("Respuesta de getTaskById", $tareaResponse);

if (!isset($tareaResponse['ok']) || $tareaResponse['ok'] !== true || !isset($tareaResponse['tarea'])) {
    logDebug("Error al obtener tarea para eliminar", $tareaResponse);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No se pudo obtener la tarea para eliminar']);
    exit();
}

$tarea = $tareaResponse['tarea'];
logDebug("Tarea obtenida para eliminar", ['id' => $tareaId, 'titulo' => $tarea['titulo']]);

// Eliminar la tarea
$response = deleteTask($tarea['titulo'], $tareaId);
logDebug("Respuesta de deleteTask", $response);

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>