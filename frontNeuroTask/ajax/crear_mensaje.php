<?php
// ajax/crear_mensaje.php
session_start();
require_once '../includes/api.php';

// Configuración de encabezados para permitir solicitudes AJAX
header('Content-Type: application/json');

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Obtener y registrar todos los datos de la solicitud para depuración
$inputJSON = file_get_contents('php://input');
$headers = getallheaders();
error_log("Headers recibidos: " . json_encode($headers));
error_log("Cuerpo raw recibido: " . $inputJSON);

// Decodificar los datos JSON
$input = json_decode($inputJSON, TRUE);
error_log("Datos JSON decodificados: " . json_encode($input));

// Si no es JSON válido, probar con POST
if ($input === null) {
    error_log("JSON inválido, intentando con datos POST");
    $input = $_POST;
    error_log("Datos POST: " . json_encode($input));
}

// Verificar que se recibieron los datos necesarios
if (!isset($input['tarea_id']) || empty($input['tarea_id']) || 
    !isset($input['contenido']) || empty($input['contenido'])) {
    error_log("ERROR: Faltan datos requeridos");
    error_log("tarea_id: " . (isset($input['tarea_id']) ? $input['tarea_id'] : 'no definido'));
    error_log("contenido: " . (isset($input['contenido']) ? 'presente' : 'no definido'));
    
    echo json_encode([
        'ok' => false, 
        'error' => 'Falta la tarea_id o el contenido del mensaje',
        'debug' => [
            'input_recibido' => $input,
            'raw_json' => $inputJSON
        ]
    ]);
    exit();
}

// Preparar los datos exactamente como los espera el microservicio
$tarea_id = $input['tarea_id'];
$usuario_id = $_SESSION['usuario']['_id']; // Usar siempre el ID del usuario actual
$contenido = trim($input['contenido']);

error_log("Preparando para crear mensaje: tarea_id=$tarea_id, usuario_id=$usuario_id, contenido=$contenido");

// Usar la función del API en lugar de llamada directa
$response = createMessage($tarea_id, $usuario_id, $contenido);
error_log("Respuesta de createMessage: " . json_encode($response));

// Verificar si la respuesta del API tiene la estructura correcta
if (isset($response['ok']) && $response['ok'] === true) {
    // Si la respuesta es exitosa pero no incluye el mensaje completo, añadimos la información básica
    if (!isset($response['mensaje']) || empty($response['mensaje'])) {
        error_log("Respuesta exitosa pero sin datos del mensaje. Creando objeto mensaje básico.");
        $response['mensaje'] = [
            '_id' => isset($response['mensaje']['_id']) ? $response['mensaje']['_id'] : '',
            'tarea_id' => $tarea_id,
            'usuario_id' => $usuario_id,
            'contenido' => $contenido,
            'timestamp' => date('Y-m-d H:i:s'),
            'usuario' => [
                '_id' => $_SESSION['usuario']['_id'],
                'nombre' => $_SESSION['usuario']['nombre'],
                'email' => $_SESSION['usuario']['email']
            ]
        ];
    } else if (is_array($response['mensaje']) && !isset($response['mensaje']['usuario'])) {
        // Si hay mensaje pero no tiene información del usuario, la añadimos
        $response['mensaje']['usuario'] = [
            '_id' => $_SESSION['usuario']['_id'],
            'nombre' => $_SESSION['usuario']['nombre'],
            'email' => $_SESSION['usuario']['email']
        ];
    }
}

// Devolver la respuesta al cliente
echo json_encode($response);
?>