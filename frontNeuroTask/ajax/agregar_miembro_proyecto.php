<?php
// ajax/agregar_miembro_proyecto.php - Con depuración mejorada
session_start();
require_once '../includes/api.php';

// Función para depuración
function logDebug($message, $data = null) {
    $log = "[" . date('Y-m-d H:i:s') . "] " . $message;
    if ($data !== null) {
        $log .= ": " . (is_string($data) ? $data : json_encode($data));
    }
    error_log($log);
}

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Log de todos los datos recibidos
logDebug("POST data recibida", $_POST);
logDebug("GET data recibida", $_GET);

// Obtener datos del formulario
$proyectoNombre = isset($_POST['proyectoNombre']) ? $_POST['proyectoNombre'] : '';
$proyectoId = isset($_POST['proyectoId']) ? $_POST['proyectoId'] : '';
$email = isset($_POST['email']) ? $_POST['email'] : '';

// Verificar datos requeridos
if (empty($proyectoNombre) && empty($proyectoId)) {
    logDebug("Error: Identificador de proyecto no proporcionado", ['nombre' => $proyectoNombre, 'id' => $proyectoId]);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Identificador de proyecto no proporcionado']);
    exit();
}

if (empty($email)) {
    logDebug("Error: Email no proporcionado");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Email no proporcionado']);
    exit();
}

// Verificar que el email sea válido
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    logDebug("Error: Email inválido", $email);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'El correo electrónico no es válido']);
    exit();
}

// Evitar que se invite a sí mismo
if ($_SESSION['usuario']['email'] === $email) {
    logDebug("Error: Usuario intentando invitarse a sí mismo", $email);
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No puedes invitarte a ti mismo al proyecto']);
    exit();
}

try {
    // Verificar si el usuario existe
    logDebug("Buscando usuario por email", $email);
    $userResponse = getUserByEmail($email);
    logDebug("Respuesta de búsqueda de usuario", $userResponse);
    
    if (!isset($userResponse['ok']) || $userResponse['ok'] !== true || !isset($userResponse['usuario'])) {
        logDebug("Error: Usuario no encontrado", $email);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'El usuario con el email proporcionado no está registrado en el sistema'
        ]);
        exit();
    }
    
    // Si tenemos el ID del proyecto, intentar obtener detalles
    if (!empty($proyectoId)) {
        logDebug("Obteniendo detalles del proyecto por ID", $proyectoId);
        $proyectoResponse = getProjectById($proyectoId);
        logDebug("Respuesta de búsqueda de proyecto por ID", $proyectoResponse);
        
        if (isset($proyectoResponse['ok']) && $proyectoResponse['ok'] === true && isset($proyectoResponse['proyecto'])) {
            $proyectoNombre = $proyectoResponse['proyecto']['nombre'];
            logDebug("Nombre de proyecto obtenido del ID", $proyectoNombre);
        }
    }
    
    // Realizar la llamada directa a la API
    logDebug("Preparando llamada a la API para agregar miembro", [
        'proyectoNombre' => $proyectoNombre,
        'email' => $email
    ]);
    
    // Construir URL para la API
    $url = "http://micro_projects:3008/api/project/" . urlencode($proyectoNombre) . "/miembros";
    $data = ["email" => $email];
    
    logDebug("URL para agregar miembro", $url);
    logDebug("Datos para agregar miembro", $data);
    
    // Realizar la llamada directa sin usar la función intermedia
    $response = callAPI("POST", $url, $data);
    logDebug("Respuesta de la API", $response);
    
    // Mejorar mensaje si es éxito
    if (isset($response['ok']) && $response['ok'] === true) {
        $response['mensaje'] = "Usuario $email añadido correctamente al proyecto.";
    }
    
    // Enviar respuesta como JSON
    header('Content-Type: application/json');
    echo json_encode($response);
    
} catch (Exception $e) {
    logDebug("Excepción capturada", $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Error al procesar la solicitud: ' . $e->getMessage()]);
}