<?php
// ajax/crear_tarea.php (versión actualizada)
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Obtener userId de la sesión o del POST
$userId = isset($_POST['userId']) ? $_POST['userId'] : $_SESSION['usuario']['_id'];

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['proyecto_id']) || empty($_POST['proyecto_id']) || 
    !isset($_POST['titulo']) || empty($_POST['titulo']) || 
    !isset($_POST['estado']) || empty($_POST['estado']) || 
    !isset($_POST['prioridad']) || empty($_POST['prioridad'])) {
    
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Faltan datos requeridos para crear la tarea']);
    exit();
}

// Preparar los datos para la API
$data = [
    'proyecto_id' => $_POST['proyecto_id'],
    'titulo' => trim($_POST['titulo']),
    'descripcion' => isset($_POST['descripcion']) ? trim($_POST['descripcion']) : '',
    'estado' => $_POST['estado'],
    'prioridad' => $_POST['prioridad'],
    'userId' => $userId // Añadir el ID del usuario que crea la tarea
];

// Agregar fecha límite si se proporcionó
if (isset($_POST['fecha_limite']) && !empty($_POST['fecha_limite'])) {
    $data['fecha_limite'] = $_POST['fecha_limite'];
}

// Agregar usuario asignado si se proporcionó
if (isset($_POST['usuario_asignado']) && !empty($_POST['usuario_asignado'])) {
    // Verificar si el usuario existe antes de intentar asignarlo
    $userResponse = getUserById($_POST['usuario_asignado']);
    if (isset($userResponse['ok']) && $userResponse['ok'] === true && isset($userResponse['usuario'])) {
        $data['usuario_asignado'] = $_POST['usuario_asignado'];
    }
}

// Crear la tarea
$response = createTask($data);

// Depuración - opcional, puedes comentarlo en producción
error_log('Datos enviados a createTask: ' . json_encode($data));
error_log('Respuesta de createTask: ' . json_encode($response));

// Si la tarea se creó exitosamente, obtener los detalles completos de la tarea para mostrarla en la UI
if (isset($response['ok']) && $response['ok'] === true && isset($response['tarea'])) {
    // Si hay un usuario asignado, obtener sus detalles para incluirlos en la respuesta
    if (!empty($data['usuario_asignado'])) {
        $userResponse = getUserById($data['usuario_asignado']);
        if (isset($userResponse['ok']) && $userResponse['ok'] === true && isset($userResponse['usuario'])) {
            $response['tarea']['usuario_asignado'] = $userResponse['usuario'];
        }
    }
}

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
exit();
?>