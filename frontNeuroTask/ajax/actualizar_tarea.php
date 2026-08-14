<?php
// ajax/actualizar_tarea.php - Versión mejorada
session_start();
require_once '../includes/api.php';

// Función para depuración
function logDebug($message, $data = null) {
    $logMessage = "[ACTUALIZAR-TAREA] [" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $logMessage .= ": " . (is_string($data) ? $data : json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
    }
    error_log($logMessage);
}

// Registrar todos los datos recibidos
logDebug("REQUEST_METHOD: " . $_SERVER['REQUEST_METHOD']);
logDebug("DATOS POST RECIBIDOS", $_POST);

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    logDebug("ERROR: Usuario no autenticado");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['id']) || empty($_POST['id'])) {
    logDebug("ERROR: ID de tarea no proporcionado");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de tarea requerido']);
    exit();
}

$tareaId = $_POST['id'];
logDebug("Procesando actualización para tarea ID", $tareaId);

// PASO 1: Obtener la tarea actual
logDebug("Obteniendo información actual de la tarea");
$tareaActual = getTaskById($tareaId);
logDebug("Respuesta de getTaskById", $tareaActual);

if (!isset($tareaActual['ok']) || $tareaActual['ok'] !== true || !isset($tareaActual['tarea'])) {
    logDebug("ERROR: No se pudo obtener la tarea", $tareaActual);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No se pudo obtener la tarea para actualizar']);
    exit();
}

// PASO 2: Preparar los datos para la actualización de manera minimalista
$tareaOriginal = $tareaActual['tarea'];
logDebug("Tarea original obtenida", $tareaOriginal);

// Crear un conjunto minimalista de datos a actualizar
// Solo incluir los campos que realmente han cambiado
$data = [];

// Solo procesar campos si se incluyen en $_POST y son diferentes del valor actual
$fieldsToCheck = ['titulo', 'descripcion', 'estado', 'prioridad', 'proyecto_id'];

foreach ($fieldsToCheck as $field) {
    if (isset($_POST[$field]) && $_POST[$field] !== "") {
        if (!isset($tareaOriginal[$field]) || $_POST[$field] != $tareaOriginal[$field]) {
            $data[$field] = $_POST[$field];
            logDebug("Campo $field actualizado", $data[$field]);
        }
    }
}

// Manejar fecha_limite (campo opcional)
if (isset($_POST['fecha_limite'])) {
    if (empty($_POST['fecha_limite'])) {
        // Si se envía vacío, lo eliminamos (usando null)
        $data['fecha_limite'] = null;
        logDebug("fecha_limite establecido a NULL (eliminado)");
    } else {
        // Solo actualizar si es diferente
        $newDate = $_POST['fecha_limite'];
        $oldDate = isset($tareaOriginal['fecha_limite']) ? substr($tareaOriginal['fecha_limite'], 0, 10) : null;
        
        if ($newDate != $oldDate) {
            $data['fecha_limite'] = $newDate;
            logDebug("fecha_limite actualizado", $data['fecha_limite']);
        }
    }
}

// Manejar usuario_asignado (campo opcional)
if (isset($_POST['usuario_asignado'])) {
    // Determinar el valor actual
    $currentAssignedUser = null;
    if (isset($tareaOriginal['usuario_asignado'])) {
        if (is_array($tareaOriginal['usuario_asignado']) && isset($tareaOriginal['usuario_asignado']['_id'])) {
            $currentAssignedUser = $tareaOriginal['usuario_asignado']['_id'];
        } else {
            $currentAssignedUser = $tareaOriginal['usuario_asignado'];
        }
    }
    
    if (empty($_POST['usuario_asignado'])) {
        // Si se envía vacío y actualmente tiene un valor, establecer a null
        if ($currentAssignedUser !== null) {
            $data['usuario_asignado'] = null;
            logDebug("usuario_asignado establecido a NULL (eliminado)");
        }
    } else if ($_POST['usuario_asignado'] !== $currentAssignedUser) {
        // Solo actualizar si es diferente
        $data['usuario_asignado'] = $_POST['usuario_asignado'];
        logDebug("usuario_asignado actualizado", $data['usuario_asignado']);
    }
}

// Verificar si no hay cambios
if (empty($data)) {
    logDebug("No se detectaron cambios, enviando respuesta exitosa sin actualizar");
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => true, 
        'tarea' => $tareaOriginal,
        'mensaje' => 'No se detectaron cambios'
    ]);
    exit();
}

logDebug("DATOS FINALES PREPARADOS PARA ACTUALIZACIÓN", $data);

try {
    // PASO 3: Enviar la actualización a la API
    logDebug("Enviando solicitud de actualización a updateTask");
    $response = updateTask($tareaId, $data);
    logDebug("Respuesta recibida de updateTask", $response);
    
    // Verificar respuesta
    if (!isset($response['ok']) || $response['ok'] !== true) {
        $error = isset($response['error']) ? $response['error'] : 'Error desconocido al actualizar la tarea';
        logDebug("ERROR en la respuesta", $error);
        throw new Exception($error);
    }
    
    logDebug("Actualización EXITOSA de la tarea");
    
    // Devolver respuesta exitosa
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("EXCEPCIÓN durante la actualización", $e->getMessage());
    
    // Devolver error
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false, 
        'error' => 'Error al actualizar la tarea: ' . $e->getMessage()
    ]);
}
?>