<?php
// ajax/exact_name_member.php - Implementación directa con el nombre exacto del proyecto
ob_start();

session_start();

// Función para registrar eventos
function log_debug($message) {
    error_log("[" . date('Y-m-d H:i:s') . "] " . $message);
}

// Función para enviar respuesta JSON
function json_response($data) {
    ob_end_clean();
    header('Content-Type: application/json');
    echo json_encode($data);
    exit;
}

// Verificar autenticación
if (!isset($_SESSION['usuario'])) {
    json_response(['ok' => false, 'error' => 'No autenticado']);
}

// Obtener datos JSON
$input = file_get_contents('php://input');
log_debug("Datos recibidos: " . $input);
$data = json_decode($input, true);

// Verificar datos
if (!isset($data['email']) || empty($data['email'])) {
    json_response(['ok' => false, 'error' => 'Email no proporcionado']);
}

if (!isset($data['projectName']) || empty($data['projectName'])) {
    json_response(['ok' => false, 'error' => 'Nombre del proyecto no proporcionado']);
}

$email = $data['email'];
$projectName = $data['projectName'];

log_debug("Email: $email, Nombre Proyecto: $projectName");

// Verificar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Email inválido']);
}

// Evitar auto-invitación
if ($_SESSION['usuario']['email'] === $email) {
    json_response(['ok' => false, 'error' => 'No puedes invitarte a ti mismo al proyecto']);
}

// Realizar la solicitud exactamente como espera el controlador
$url = "http://micro_projects:3008/api/project/" . urlencode($projectName) . "/miembros";
$postData = json_encode(['email' => $email]);

log_debug("URL: $url");
log_debug("Datos POST: $postData");

// Configurar cURL
$ch = curl_init();
curl_setopt_array($ch, [
    CURLOPT_URL => $url,
    CURLOPT_POST => true,
    CURLOPT_POSTFIELDS => $postData,
    CURLOPT_RETURNTRANSFER => true,
    CURLOPT_HTTPHEADER => [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($postData)
    ],
    CURLOPT_TIMEOUT => 10
]);

// Ejecutar solicitud
$response = curl_exec($ch);
$httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
$error = curl_error($ch);

log_debug("HTTP Code: $httpCode");
log_debug("Respuesta: $response");
if ($error) {
    log_debug("Error cURL: $error");
}

curl_close($ch);

// Manejar respuesta
if ($error) {
    json_response(['ok' => false, 'error' => 'Error de conexión: ' . $error]);
}

// Decodificar respuesta
$result = json_decode($response, true);
if ($result === null) {
    json_response([
        'ok' => false, 
        'error' => 'Error al procesar la respuesta del servidor',
        'raw_response' => $response
    ]);
}

// Mostrar respuesta con mensaje amigable
if (isset($result['ok']) && $result['ok'] === true) {
    $result['mensaje'] = isset($result['mensaje']) ? $result['mensaje'] : "Usuario $email añadido correctamente al proyecto \"$projectName\".";
}

json_response($result);