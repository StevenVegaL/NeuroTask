<?php
// ajax/decoded_name_member.php - Implementación con decodificación URL correcta
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

// El nombre original del proyecto (para mensajes)
$originalProjectName = $projectName;

log_debug("Email: $email, Nombre Proyecto Original: $projectName");

// Verificar email
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    json_response(['ok' => false, 'error' => 'Email inválido']);
}

// Evitar auto-invitación
if ($_SESSION['usuario']['email'] === $email) {
    json_response(['ok' => false, 'error' => 'No puedes invitarte a ti mismo al proyecto']);
}

// Codificar correctamente el nombre del proyecto para la URL
// Usamos rawurlencode que codifica según RFC 3986
// Esto preserva espacios como %20 y otros caracteres especiales correctamente
$encodedName = rawurlencode($projectName);

// Construir URL - asegurando que se usa la codificación correcta
$url = "http://192.168.100.3:3008/api/project/" . $encodedName . "/miembros";
$postData = json_encode(['email' => $email]);

log_debug("URL codificada: $url");
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
    // Si no podemos decodificar la respuesta, intentar con una URL alternativa
    log_debug("Error al decodificar respuesta JSON, intentando URL alternativa");
    
    // Probar con una codificación diferente (solo espacios como +)
    $altEncodedName = str_replace(' ', '+', $projectName);
    $altUrl = "http://192.168.100.3:3008/api/project/" . $altEncodedName . "/miembros";
    
    log_debug("URL alternativa: $altUrl");
    
    // Configurar cURL nuevamente
    $ch = curl_init();
    curl_setopt_array($ch, [
        CURLOPT_URL => $altUrl,
        CURLOPT_POST => true,
        CURLOPT_POSTFIELDS => $postData,
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_HTTPHEADER => [
            'Content-Type: application/json',
            'Content-Length: ' . strlen($postData)
        ],
        CURLOPT_TIMEOUT => 10
    ]);
    
    // Ejecutar solicitud alternativa
    $altResponse = curl_exec($ch);
    $altHttpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
    $altError = curl_error($ch);
    
    log_debug("HTTP Code (alt): $altHttpCode");
    log_debug("Respuesta (alt): $altResponse");
    
    curl_close($ch);
    
    if ($altError) {
        json_response(['ok' => false, 'error' => 'Error de conexión en ambos intentos']);
    }
    
    $altResult = json_decode($altResponse, true);
    if ($altResult === null) {
        json_response([
            'ok' => false, 
            'error' => 'Error al procesar la respuesta del servidor',
            'debug' => [
                'original_response' => substr($response, 0, 500),
                'alt_response' => substr($altResponse, 0, 500)
            ]
        ]);
    }
    
    $result = $altResult;
}

// Mostrar respuesta con mensaje amigable
if (isset($result['ok']) && $result['ok'] === true) {
    $result['mensaje'] = isset($result['mensaje']) ? $result['mensaje'] : "Usuario $email añadido correctamente al proyecto \"$originalProjectName\".";
}

json_response($result);