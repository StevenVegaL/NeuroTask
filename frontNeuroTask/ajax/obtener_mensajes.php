<?php
// ajax/obtener_mensajes.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió el ID de la tarea
if (!isset($_GET['tarea_id']) || empty($_GET['tarea_id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de tarea no proporcionado']);
    exit();
}

$tarea_id = $_GET['tarea_id'];

// Obtener los mensajes de la tarea
$response = getMessagesByTask($tarea_id);

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>