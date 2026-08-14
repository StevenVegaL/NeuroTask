<?php
// ajax/actualizar_proyecto.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

// Verificar que se recibieron los datos necesarios
if (!isset($_POST['id']) || empty($_POST['id']) || 
    !isset($_POST['nombre']) || empty($_POST['nombre']) || 
    !isset($_POST['descripcion'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'Datos incompletos']);
    exit();
}

$proyecto_id = $_POST['id'];
$data = [
    'nombre' => trim($_POST['nombre']),
    'descripcion' => trim($_POST['descripcion'])
];

// Actualizar el proyecto
$response = updateProject($proyecto_id, $data);

// Devolver la respuesta como JSON
header('Content-Type: application/json');
echo json_encode($response);
?>