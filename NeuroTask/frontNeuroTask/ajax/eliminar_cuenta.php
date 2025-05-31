<?php
// ajax/eliminar_cuenta.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['ok' => false, 'error' => 'No autenticado']);
    exit();
}

$userId = $_SESSION['usuario']['_id'];

// Eliminar cuenta
$response = deleteUser($userId);

// Si es exitoso, destruir la sesión y redirigir al inicio
if (isset($response['ok']) && $response['ok'] === true) {
    session_destroy();
    header("Location: ../index.php?deleted=true");
    exit();
} else {
    // Si hay un error, redirigir a configuración con mensaje de error
    $_SESSION['error_eliminar_cuenta'] = $response['error'] ?? "Error al eliminar la cuenta";
    header("Location: ../configuracion.php");
    exit();
}
?>