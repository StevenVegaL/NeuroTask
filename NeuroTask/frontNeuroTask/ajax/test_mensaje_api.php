<?php
// test_mensajes_api.php - Archivo para probar la conexión directa con el microservicio

// Información de la solicitud
$url = "http://192.168.100.3:3010/api/mensajes/";
$data = [
    "tarea_id" => "67ddd232588775ab07ae00d0", // Usar un ID de tarea existente
    "usuario_id" => "67dce67699a8be7305e4d3fd", // Usar un ID de usuario existente
    "contenido" => "Mensaje de prueba " . date('Y-m-d H:i:s')
];

echo "<h1>Prueba de conexión al microservicio de mensajes</h1>";
echo "<h2>Enviando solicitud a: $url</h2>";
echo "<pre>Datos a enviar: " . json_encode($data, JSON_PRETTY_PRINT) . "</pre>";

// Crear la solicitud cURL
$curl = curl_init($url);
curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
curl_setopt($curl, CURLOPT_POST, true);
curl_setopt($curl, CURLOPT_HTTPHEADER, [
    'Content-Type: application/json',
    'Accept: application/json'
]);
curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($data));

// Ejecutar la solicitud
$response = curl_exec($curl);
$http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
$curl_error = curl_error($curl);

// Mostrar resultados
echo "<h2>Resultado:</h2>";
echo "<p>Código HTTP: $http_code</p>";

if ($curl_error) {
    echo "<p style='color:red'>Error cURL: $curl_error</p>";
} else {
    echo "<p>Respuesta raw:</p>";
    echo "<pre>" . htmlspecialchars($response) . "</pre>";
    
    echo "<p>Respuesta formateada:</p>";
    $decoded = json_decode($response, true);
    if ($decoded) {
        echo "<pre>" . json_encode($decoded, JSON_PRETTY_PRINT) . "</pre>";
    } else {
        echo "<p style='color:red'>Error al decodificar JSON</p>";
    }
}

curl_close($curl);
?>