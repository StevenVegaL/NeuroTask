<?php
// ajax/actualizar_tarea_drag.php - Versión corregida
session_start();
require_once '../includes/api.php';

// Función para registrar mensajes de error detallados
function logError($message, $data = null) {
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

// Obtener datos JSON del cuerpo de la solicitud
$input = file_get_contents('php://input');
logError("Datos recibidos del cliente", $input);

// Decodificar los datos JSON
$data = json_decode($input, true);
if (json_last_error() !== JSON_ERROR_NONE) {
    logError("Error al decodificar JSON del cliente", json_last_error_msg());
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Error al decodificar JSON: ' . json_last_error_msg()]);
    exit();
}

// Verificar que se recibieron los datos necesarios
if (!isset($data['id']) || empty($data['id']) || !isset($data['tarea'])) {
    logError("Datos insuficientes", $data);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Datos insuficientes']);
    exit();
}

$tareaId = $data['id'];
$tareaData = $data['tarea'];

// Verificar que se recibió el nuevo estado
if (!isset($tareaData['estado']) || empty($tareaData['estado'])) {
    logError("Estado no proporcionado", $tareaData);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Estado no proporcionado']);
    exit();
}

// Log para depuración
logError("Actualizando tarea (drag & drop)", ['id' => $tareaId, 'estado' => $tareaData['estado']]);

// Para drag & drop, solo enviamos el estado para simplificar
$updateData = ['estado' => $tareaData['estado']];

try {
    // USAMOS LA NUEVA RUTA ESPECÍFICA PARA ACTUALIZAR ESTADO
    $url = "http://micro_task:3007/api/task/updateState/" . $tareaId;
    logError("Usando la nueva ruta específica para estado", $url);
    
    // Llamar directamente a callAPI para tener más control sobre el proceso
    $response = callAPI("PUT", $url, $updateData);
    
    logError("Respuesta recibida de actualizarEstadoTarea", $response);
    
    if (!isset($response['ok']) || $response['ok'] !== true) {
        $errorMsg = isset($response['error']) ? $response['error'] : 'Error desconocido';
        throw new Exception("La API indicó un error: " . $errorMsg);
    }
    
    // Devolver la respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true, 
        'mensaje' => 'Estado actualizado correctamente',
        'tarea' => isset($response['tarea']) ? $response['tarea'] : null
    ]);
    
} catch (Exception $e) {
    logError("Error al actualizar la tarea", $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false, 
        'error' => 'Error al actualizar estado: ' . $e->getMessage()
    ]);
}
?>