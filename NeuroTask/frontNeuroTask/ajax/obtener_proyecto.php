<?php
// ajax/obtener_proyecto.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió un ID de proyecto
if (!isset($_GET['id']) || empty($_GET['id'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'ID de proyecto no proporcionado']);
    exit();
}

$id = $_GET['id'];
$response = getProjectById($id);

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>