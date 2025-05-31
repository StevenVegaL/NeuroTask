<?php
// ajax/actualizar_mensaje.php
error_log("Respuesta completa: " . print_r($response, true));
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Configurar cabeceras
header('Content-Type: application/json');

// Obtener el cuerpo JSON de la solicitud
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Log para depuración
error_log("Datos recibidos en actualizar_mensaje.php: " . $inputJSON);

// Si input es null (error al parsear JSON), intentar obtener de PUT
if ($input === null) {
    error_log("Error al decodificar JSON, intentando con PUT");
    if (isset($_PUT['id']) && isset($_PUT['contenido'])) {
        $input = [
            'id' => $_PUT['id'],
            'contenido' => $_PUT['contenido']
        ];
    } else {
        echo json_encode(['ok' => false, 'error' => 'Formato de datos no válido']);
        exit();
    }
}

// Verificar que se recibieron los datos necesarios
if (!isset($input['id']) || empty($input['id']) || 
    !isset($input['contenido']) || empty($input['contenido'])) {
    echo json_encode(['ok' => false, 'error' => 'Faltan datos obligatorios (id o contenido)']);
    exit();
}

$mensaje_id = $input['id'];
$contenido = trim($input['contenido']);

// Log para depuración
error_log("Actualizando mensaje con ID: $mensaje_id, Contenido: $contenido");

// Actualizar el mensaje
$response = updateMessage($mensaje_id, $contenido);

// Log de respuesta
error_log("Respuesta del microservicio para actualización: " . json_encode($response));

// Devolver la respuesta como JSON
echo json_encode($response);