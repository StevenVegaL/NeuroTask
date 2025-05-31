<?php
// ajax/eliminar_mensaje.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Obtener el cuerpo JSON de la solicitud
$inputJSON = file_get_contents('php://input');
$input = json_decode($inputJSON, TRUE);

// Log para depuración
error_log("Datos recibidos en eliminar_mensaje.php: " . $inputJSON);

// Si input es null (error al parsear JSON), intentar obtener de POST
if ($input === null) {
    error_log("Error al decodificar JSON, intentando con POST");
    if (isset($_POST['id'])) {
        $input = ['id' => $_POST['id']];
    } else {
        header('Content-Type: application/json');
        echo json_encode(['ok' => false, 'error' => 'Formato de datos no válido']);
        exit();
    }
}

// Verificar que se recibió el ID del mensaje
if (!isset($input['id']) || empty($input['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de mensaje no proporcionado']);
    exit();
}

$mensaje_id = $input['id'];

// Log para depuración
error_log("Eliminando mensaje con ID: $mensaje_id");

// Eliminar el mensaje
$response = deleteMessage($mensaje_id);

// Log de respuesta
error_log("Respuesta del microservicio para eliminación: " . json_encode($response));

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>