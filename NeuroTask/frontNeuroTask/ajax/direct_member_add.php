<?php
// ajax/direct_add_member.php - Versión más directa usando el ID del proyecto
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

// Obtener datos JSON del cuerpo de la solicitud
$json = file_get_contents('php://input');
$data = json_decode($json, true);

logDebug("Datos JSON recibidos", $data);

// Verificar datos mínimos necesarios
if (!isset($data['email']) || empty($data['email'])) {
    logDebug("Error: Email no proporcionado");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Email no proporcionado']);
    exit();
}

$email = $data['email'];
$projectName = isset($data['projectName']) ? $data['projectName'] : '';
$projectId = isset($data['projectId']) ? $data['projectId'] : '';

// Verificar datos mínimos
if (empty($projectId)) {
    logDebug("Error: Se requiere ID del proyecto");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Se requiere ID del proyecto']);
    exit();
}

try {
    // 1. Obtener detalles del proyecto por ID
    logDebug("Obteniendo detalles del proyecto por ID", $projectId);
    $projectResponse = getProjectById($projectId);
    logDebug("Respuesta de getProjectById", $projectResponse);
    
    if (!isset($projectResponse['ok']) || $projectResponse['ok'] !== true || !isset($projectResponse['proyecto'])) {
        logDebug("Error al obtener los detalles del proyecto", [
            'projectId' => $projectId,
            'response' => $projectResponse
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'Error al obtener los detalles del proyecto. ' . 
                      (isset($projectResponse['error']) ? $projectResponse['error'] : 'Proyecto no encontrado'),
            'debug' => [
                'projectId' => $projectId,
                'response' => $projectResponse
            ]
        ]);
        exit();
    }
    
    $proyecto = $projectResponse['proyecto'];
    logDebug("Proyecto encontrado", [
        'id' => $proyecto['_id'],
        'nombre' => $proyecto['nombre']
    ]);
    
    // 2. Verificar si el usuario existe
    logDebug("Verificando usuario por email", $email);
    $userResponse = getUserByEmail($email);
    logDebug("Respuesta de getUserByEmail", $userResponse);
    
    if (!isset($userResponse['ok']) || $userResponse['ok'] !== true || !isset($userResponse['usuario'])) {
        logDebug("Error: Usuario no encontrado", $email);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'El usuario con el email proporcionado no está registrado en el sistema',
            'debug' => [
                'email' => $email,
                'response' => $userResponse
            ]
        ]);
        exit();
    }
    
    $usuario = $userResponse['usuario'];
    logDebug("Usuario encontrado", [
        'id' => $usuario['_id'],
        'email' => $usuario['email'],
        'nombre' => $usuario['nombre']
    ]);
    
    // 3. Evitar que se invite a sí mismo
    if ($_SESSION['usuario']['email'] === $email) {
        logDebug("Error: Usuario intentando invitarse a sí mismo", $email);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'No puedes invitarte a ti mismo al proyecto']);
        exit();
    }
    
    // 4. Verificar si el usuario ya es miembro
    if (isset($proyecto['miembros']) && is_array($proyecto['miembros'])) {
        $esMiembro = false;
        foreach ($proyecto['miembros'] as $miembro) {
            if (is_array($miembro) && isset($miembro['_id']) && $miembro['_id'] === $usuario['_id']) {
                $esMiembro = true;
                break;
            } elseif (is_string($miembro) && $miembro === $usuario['_id']) {
                $esMiembro = true;
                break;
            }
        }
        
        if ($esMiembro) {
            logDebug("El usuario ya es miembro del proyecto", [
                'userId' => $usuario['_id'],
                'projectId' => $proyecto['_id']
            ]);
            
            header('Content-Type: application/json');
            echo json_encode([
                'ok' => false,
                'error' => 'El usuario ya es miembro de este proyecto'
            ]);
            exit();
        }
    }
    
    // 5. Llamar directamente a la API para agregar el miembro
    logDebug("Preparando datos para agregar miembro", [
        'nombre_proyecto' => $proyecto['nombre'],
        'email' => $email
    ]);
    
    // Construir la URL correcta
    $url = "http://192.168.100.3:3008/api/project/" . urlencode($proyecto['nombre']) . "/miembros";
    $data = ["email" => $email];
    
    logDebug("URL para agregar miembro", $url);
    logDebug("Datos para agregar miembro", $data);
    
    // Configurar cURL
    $ch = curl_init();
    curl_setopt($ch, CURLOPT_URL, $url);
    curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($ch, CURLOPT_POST, true);
    curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode($data));
    curl_setopt($ch, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Accept: application/json'
    ]);
    
    // Ejecutar la solicitud
    $response = curl_exec($ch);
    $info = curl_getinfo($ch);
    $error = curl_error($ch);
    
    logDebug("Respuesta de cURL", [
        'httpCode' => $info['http_code'],
        'response' => $response,
        'error' => $error
    ]);
    
    curl_close($ch);
    
    // Procesar la respuesta
    if ($error) {
        logDebug("Error de cURL", $error);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'Error de conexión: ' . $error,
            'debug' => [
                'url' => $url,
                'data' => $data
            ]
        ]);
        exit();
    }
    
    $result = json_decode($response, true);
    
    if ($result === null) {
        logDebug("Error al decodificar respuesta JSON", $response);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'Error al procesar la respuesta del servidor',
            'debug' => [
                'raw_response' => $response,
                'url' => $url
            ]
        ]);
        exit();
    }
    
    // Mejorar el mensaje si es éxito
    if (isset($result['ok']) && $result['ok'] === true) {
        $result['mensaje'] = "Usuario {$usuario['email']} añadido correctamente al proyecto \"{$proyecto['nombre']}\".";
    }
    
    // Enviar respuesta final
    header('Content-Type: application/json');
    echo json_encode($result);
    
} catch (Exception $e) {
    logDebug("Excepción capturada", $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false,
        'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}