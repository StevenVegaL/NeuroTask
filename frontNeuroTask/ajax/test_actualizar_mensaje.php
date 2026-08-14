<?php
// ajax/test_actualizar_mensaje.php
session_start();
require_once '../includes/api.php';

// Este archivo es para probar directamente la función updateMessage
// Configura algunos valores de prueba
$mensaje_id = $_GET['id'] ?? '123456789012345678901234'; // ID de prueba o el proporcionado por URL
$contenido = $_GET['contenido'] ?? 'Contenido de prueba ' . date('Y-m-d H:i:s');

// Configura la salida como HTML para mejor legibilidad
header('Content-Type: text/html');
?>
<!DOCTYPE html>
<html>
<head>
    <title>Test de Actualización de Mensaje</title>
    <style>
        body { font-family: Arial, sans-serif; margin: 20px; }
        pre { background: #f4f4f4; padding: 10px; border-radius: 5px; }
        .success { color: green; }
        .error { color: red; }
    </style>
</head>
<body>
    <h1>Prueba de actualización de mensaje</h1>
    
    <h2>Parámetros de prueba:</h2>
    <ul>
        <li><strong>ID del mensaje:</strong> <?= htmlspecialchars($mensaje_id) ?></li>
        <li><strong>Contenido:</strong> <?= htmlspecialchars($contenido) ?></li>
    </ul>
    
    <h2>Llamando a updateMessage directamente:</h2>
    <?php
    try {
        echo "<p>Enviando solicitud a updateMessage...</p>";
        
        // Inspeccionar la función updateMessage
        echo "<pre>Código de updateMessage:\n";
        $reflection = new ReflectionFunction('updateMessage');
        echo htmlspecialchars(file_get_contents($reflection->getFileName()), ENT_QUOTES, 'UTF-8');
        echo "</pre>";
        
        // Llamar a la función y capturar el resultado
        $response = updateMessage($mensaje_id, $contenido);
        
        echo "<pre>";
        if (isset($response['ok']) && $response['ok'] === true) {
            echo "<div class='success'>✓ ÉXITO: Mensaje actualizado correctamente</div>\n";
        } else {
            echo "<div class='error'>✗ ERROR: No se pudo actualizar el mensaje</div>\n";
        }
        echo "Respuesta completa:\n";
        echo htmlspecialchars(print_r($response, true));
        echo "</pre>";
        
        // Inspeccionar cabeceras HTTP
        echo "<h3>Inspección de la función callAPI:</h3>";
        echo "<p>Verificando cómo se construyen las solicitudes HTTP...</p>";
        
        // Prueba directa al microservicio (requires curl)
        echo "<h3>Prueba directa con cURL:</h3>";
        $url = "http://micro_coments:3010/api/mensajes/" . urlencode($mensaje_id);
        $ch = curl_init($url);
        curl_setopt($ch, CURLOPT_CUSTOMREQUEST, "PUT");
        curl_setopt($ch, CURLOPT_POSTFIELDS, json_encode(["contenido" => $contenido]));
        curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($ch, CURLOPT_HTTPHEADER, [
            'Content-Type: application/json',
            'Accept: application/json'
        ]);
        $curl_response = curl_exec($ch);
        $http_code = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curl_error = curl_error($ch);
        curl_close($ch);
        
        echo "<p>URL: $url</p>";
        echo "<p>Método: PUT</p>";
        echo "<p>Datos enviados: " . json_encode(["contenido" => $contenido]) . "</p>";
        echo "<p>Código HTTP: $http_code</p>";
        
        if ($curl_error) {
            echo "<p class='error'>Error cURL: $curl_error</p>";
        }
        
        echo "<pre>Respuesta directa del microservicio:\n";
        echo htmlspecialchars($curl_response);
        echo "</pre>";
    } catch (Exception $e) {
        echo "<div class='error'>Excepción: " . $e->getMessage() . "</div>";
        echo "<pre>" . $e->getTraceAsString() . "</pre>";
    }
    ?>
    
    <h2>Instrucciones:</h2>
    <p>Para probar con un mensaje real, añade los parámetros a la URL:</p>
    <code>test_actualizar_mensaje.php?id=ID_REAL_DEL_MENSAJE&contenido=Nuevo contenido de prueba</code>
</body>
</html>