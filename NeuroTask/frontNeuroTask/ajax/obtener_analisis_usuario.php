<?php
if (!isset($_GET['usuario_id'])) {
    echo json_encode([
        'ok' => false,
        'error' => 'Falta el parámetro usuario_id'
    ]);
    exit;
}

$usuario_id = $_GET['usuario_id'];
$url = "http://micro_analisis:3011/api/analisis/$usuario_id";

$ch = curl_init($url);
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
$response = curl_exec($ch);

if (curl_errno($ch)) {
    echo json_encode([
        'ok' => false,
        'error' => 'Error al conectarse con el microservicio'
    ]);
    curl_close($ch);
    exit;
}

curl_close($ch);
header('Content-Type: application/json');
echo $response;
