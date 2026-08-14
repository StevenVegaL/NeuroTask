<?php
// ajax/find_project.php
// Este script busca un proyecto de manera exhaustiva antes de agregar un miembro

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

logDebug("Datos JSON recibidos en find_project.php", $data);

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
if (empty($projectName) && empty($projectId)) {
    logDebug("Error: Se requiere nombre o ID del proyecto");
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Se requiere nombre o ID del proyecto']);
    exit();
}

try {
    // Paso 1: Verificar si el usuario existe
    logDebug("Verificando usuario por email", $email);
    $userResponse = getUserByEmail($email);
    
    if (!isset($userResponse['ok']) || $userResponse['ok'] !== true || !isset($userResponse['usuario'])) {
        logDebug("Error: Usuario no encontrado", $email);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'El usuario con el email proporcionado no está registrado en el sistema'
        ]);
        exit();
    }
    
    $userId = $userResponse['usuario']['_id'];
    logDebug("Usuario encontrado con ID", $userId);
    
    // Paso 2: Obtener todos los proyectos disponibles
    logDebug("Obteniendo lista de todos los proyectos");
    $allProjects = callAPI("GET", "http://micro_projects:3008/api/project/");
    
    if (!isset($allProjects['ok']) || $allProjects['ok'] !== true || !isset($allProjects['proyectos'])) {
        logDebug("Error al obtener la lista de proyectos", $allProjects);
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Error al obtener la lista de proyectos']);
        exit();
    }
    
    logDebug("Se encontraron " . count($allProjects['proyectos']) . " proyectos");
    
    // Paso 3: Buscar coincidencia del proyecto
    $foundProject = null;
    
    // Primero intentar por ID exacto si está disponible
    if (!empty($projectId)) {
        foreach ($allProjects['proyectos'] as $proyecto) {
            if ($proyecto['_id'] === $projectId) {
                $foundProject = $proyecto;
                logDebug("Proyecto encontrado por ID exacto", [
                    'id' => $projectId,
                    'nombre' => $proyecto['nombre']
                ]);
                break;
            }
        }
    }
    
    // Si no se encontró por ID, intentar por nombre exacto
    if ($foundProject === null && !empty($projectName)) {
        foreach ($allProjects['proyectos'] as $proyecto) {
            if ($proyecto['nombre'] === $projectName) {
                $foundProject = $proyecto;
                logDebug("Proyecto encontrado por nombre exacto", [
                    'nombre' => $projectName,
                    'id' => $proyecto['_id']
                ]);
                break;
            }
        }
    }
    
    // Si aún no se encuentra, intentar con una coincidencia insensible a mayúsculas/minúsculas
    if ($foundProject === null && !empty($projectName)) {
        foreach ($allProjects['proyectos'] as $proyecto) {
            if (strcasecmp($proyecto['nombre'], $projectName) === 0) {
                $foundProject = $proyecto;
                logDebug("Proyecto encontrado por nombre insensible a mayúsculas/minúsculas", [
                    'nombreBuscado' => $projectName,
                    'nombreEncontrado' => $proyecto['nombre'],
                    'id' => $proyecto['_id']
                ]);
                break;
            }
        }
    }
    
    // Si todavía no lo encontramos, intentar con una coincidencia parcial
    if ($foundProject === null && !empty($projectName)) {
        foreach ($allProjects['proyectos'] as $proyecto) {
            if (stripos($proyecto['nombre'], $projectName) !== false || 
                stripos($projectName, $proyecto['nombre']) !== false) {
                $foundProject = $proyecto;
                logDebug("Proyecto encontrado por coincidencia parcial", [
                    'nombreBuscado' => $projectName,
                    'nombreEncontrado' => $proyecto['nombre'],
                    'id' => $proyecto['_id']
                ]);
                break;
            }
        }
    }
    
    // Si no se encontró el proyecto
    if ($foundProject === null) {
        logDebug("No se encontró ningún proyecto que coincida", [
            'projectName' => $projectName,
            'projectId' => $projectId
        ]);
        
        // Listar los primeros 5 proyectos para depuración
        $projectSamples = array_slice($allProjects['proyectos'], 0, 5);
        $projectNames = array_map(function($p) { 
            return ['id' => $p['_id'], 'nombre' => $p['nombre']]; 
        }, $projectSamples);
        
        logDebug("Muestra de proyectos disponibles", $projectNames);
        
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'No se encontró ningún proyecto que coincida',
            'debug' => [
                'proyectosBuscados' => [
                    'nombre' => $projectName,
                    'id' => $projectId
                ],
                'muestraProyectos' => $projectNames
            ]
        ]);
        exit();
    }
    
    // Paso 4: Verificar si el usuario ya es miembro
    if (in_array($userId, $foundProject['miembros'])) {
        logDebug("El usuario ya es miembro del proyecto", [
            'userId' => $userId,
            'projectId' => $foundProject['_id']
        ]);
        
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false,
            'error' => 'El usuario ya es miembro de este proyecto'
        ]);
        exit();
    }
    
    // Paso 5: Agregar el usuario al proyecto
    $nombreProyecto = $foundProject['nombre'];
    $apiUrl = "http://micro_projects:3008/api/project/" . urlencode($nombreProyecto) . "/miembros";
    $apiData = ["email" => $email];
    
    logDebug("URL para agregar miembro", $apiUrl);
    logDebug("Datos para agregar miembro", $apiData);
    
    $curl = curl_init();
    
    curl_setopt_array($curl, [
        CURLOPT_URL => $apiUrl,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => json_encode($apiData),
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json; charset=UTF-8',
            'Accept: application/json'
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    $response = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $error = curl_error($curl);
    
    logDebug("Respuesta de CURL", [
        'httpCode' => $httpCode,
        'response' => $response,
        'error' => $error
    ]);
    
    curl_close($curl);
    
    // Manejar respuesta
    if ($error) {
        logDebug("Error de CURL", $error);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'Error de conexión: ' . $error,
            'debug' => [
                'url' => $apiUrl,
                'data' => $apiData,
                'projectFound' => [
                    'id' => $foundProject['_id'],
                    'nombre' => $foundProject['nombre']
                ]
            ]
        ]);
        exit();
    }
    
    $decoded = json_decode($response, true);
    
    if ($decoded === null) {
        logDebug("Error al decodificar respuesta JSON", $response);
        header('Content-Type: application/json');
        echo json_encode([
            'ok' => false, 
            'error' => 'Error al procesar la respuesta del servidor',
            'raw_response' => $response,
            'debug' => [
                'url' => $apiUrl,
                'data' => $apiData,
                'httpCode' => $httpCode
            ]
        ]);
        exit();
    }
    
    // Agregar mensaje de éxito si corresponde
    if (isset($decoded['ok']) && $decoded['ok'] === true) {
        $decoded['mensaje'] = "Usuario $email añadido correctamente al proyecto \"{$foundProject['nombre']}\".";
    }
    
    // Devolver la respuesta
    header('Content-Type: application/json');
    echo json_encode($decoded);
    
} catch (Exception $e) {
    logDebug("Excepción capturada", $e->getMessage());
    header('Content-Type: application/json');
    echo json_encode([
        'ok' => false, 
        'error' => 'Error al procesar la solicitud: ' . $e->getMessage()
    ]);
}