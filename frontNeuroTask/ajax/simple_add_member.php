<?php
// ajax/simple_add_member.php - Versión simplificada para evitar errores
// Iniciar búfer de salida para capturar cualquier error o advertencia PHP
ob_start();

// Cerrar la sesión para evitar bloqueos
session_start();

// Función muy simple para registrar actividad
function log_debug($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

// Función para mostrar respuesta JSON y salir
function json_response($data) {
    // Limpiar cualquier salida anterior
    ob_end_clean();
    
    // Establecer encabezados
    header('Content-Type: application/json');
    header('Cache-Control: no-cache, must-revalidate');
    
    // Enviar respuesta
    echo json_encode($data);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    json_response(['ok' => false, 'error' => 'No autenticado']);
}

// Obtener los datos JSON
$input = file_get_contents('php://input');
$data = json_decode($input, true);

log_debug("Datos recibidos: " . $input);

// Verificar datos necesarios
if (!isset($data['email']) || empty($data['email'])) {
    json_response(['ok' => false, 'error' => 'Email no proporcionado']);
}

if (!isset($data['projectName']) || empty($data['projectName'])) {
    json_response(['ok' => false, 'error' => 'Nombre del proyecto no proporcionado']);
}

// Obtener valores
$email = trim($data['email']);
$projectName = trim($data['projectName']);

// Validar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Email inválido']);
}

// Enviar solicitud directamente al servidor de proyectos
$url = "http://micro_projects:3008/api/project/" . urlencode($projectName) . "/miembros";
$postData = json_encode(['email' => $email]);

log_debug("URL: " . $url);
log_debug("Datos a enviar: " . $postData);

// Configurar cURL
$ch = curl_init();
curl_setopt($ch, CURLOPT_URL, $url);
curl_setopt($ch, CURLOPT_POST, true);
curl_setopt($ch, CURLOPT_POSTFIELDS, $postData);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Content-Length: ' . strlen($postData)
]);

// Ejecutar la solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

log_debug("Respuesta HTTP: " . $httpCode);
log_debug("Respuesta: " . $response);
if ($error) {
    log_debug("Error cURL: " . $error);
}

curl_close($ch);

// Manejar la respuesta
if ($error) {
    json_response([
        'ok' => false, 
        'error' => 'Error de conexión: ' . $error
    ]);
}

// Decodificar la respuesta
$result = json_decode($response, true);

// Verificar si la decodificación fue exitosa
if ($result === null) {
    json_response([
        'ok' => false,
        'error' => 'Error al procesar la respuesta del servidor',
        'raw_response' => substr($response, 0, 200)
    ]);
}

// Si todo salió bien, añadir un mensaje amigable
if (isset($result['ok']) && $result['ok'] === true) {
    $result['mensaje'] = "Usuario $email añadido correctamente al proyecto \"$projectName\".";
}

// Devolver la respuesta final
json_response($result);