<?php
// ajax/obtener_tarea_datos.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió un ID de tarea
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de tarea no proporcionado']);
    exit();
}

$id = $_GET['id'];

// Log para depuración
error_log("Obteniendo datos de tarea con ID: " . $id);

// Obtener la tarea
$response = getTaskById($id);

// Log de respuesta para depuración
error_log("Respuesta API getTaskById: " . json_encode($response));

// Enviar la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>