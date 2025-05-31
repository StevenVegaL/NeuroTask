<?php
// test_registro.php
// Script de diagnóstico para identificar problemas en el registro de usuarios

// Incluir archivo de API
require_once 'includes/api.php';

// Habilitar visualización de errores (solo para debugging)
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

// Crear un archivo de log para este test
$logFile = 'registro_debug_' . date('Y-m-d_H-i-s') . '.log';
file_put_contents($logFile, "=== Inicio de diagnóstico de registro ===\n");

// Función para escribir en el log
function writeLog($message) {
    global $logFile;
    $timestamp = date('Y-m-d H:i:s');
    file_put_contents($logFile, "[$timestamp] $message\n", FILE_APPEND);
    echo "$message<br>";
}

// Información del sistema
$phpVersion = phpversion();
writeLog("Versión de PHP: $phpVersion");

// Verificar extensión cURL
if (function_exists('curl_version')) {
    $curlVersion = curl_version();
    writeLog("cURL habilitado: " . $curlVersion['version']);
} else {
    writeLog("ERROR: cURL no está habilitado en este servidor");
}

// Probar conexión al microservicio de usuarios
writeLog("Probando conexión al microservicio de usuarios (puerto 3009)...");
$ch = curl_init("http://192.168.100.3:3009/api/user/ping");
curl_setopt($ch, CURLOPT_RETURNTRANSFER, true);
curl_setopt($ch, CURLOPT_CONNECTTIMEOUT, 5);
curl_setopt($ch, CURLOPT_TIMEOUT, 5);
$response = curl_exec($ch);
$error = curl_error($ch);
$info = curl_getinfo($ch);
curl_close($ch);

if ($error) {
    writeLog("ERROR: No se pudo conectar al microservicio: $error");
} else {
    writeLog("Conexión exitosa. Código de respuesta HTTP: " . $info['http_code']);
    writeLog("Respuesta: " . $response);
}

// Generar datos de prueba para un usuario
$testNombre = "Usuario Test " . rand(1000, 9999);
$testEmail = "test" . rand(1000, 9999) . "@ejemplo.com";
$testPassword = "password" . rand(1000, 9999);

writeLog("Intentando registrar usuario de prueba:");
writeLog("Nombre: $testNombre");
writeLog("Email: $testEmail");
writeLog("Password: $testPassword");

// Analizar la función userRegister antes de ejecutarla
$userRegisterDef = new ReflectionFunction('userRegister');
writeLog("Función userRegister definida en: " . $userRegisterDef->getFileName() . " (línea " . $userRegisterDef->getStartLine() . ")");

// Intentar registrar un usuario directamente con la función
writeLog("Ejecutando userRegister()...");
try {
    $result = userRegister($testNombre, $testEmail, $testPassword);
    writeLog("Resultado de userRegister(): " . json_encode($result, JSON_PRETTY_PRINT));
    
    if (isset($result['ok']) && $result['ok'] === true) {
        writeLog("ÉXITO: Usuario registrado correctamente en la base de datos");
    } else {
        writeLog("ERROR: Falló el registro. Mensaje: " . ($result['error'] ?? 'Error desconocido'));
    }
} catch (Exception $e) {
    writeLog("EXCEPCIÓN: " . $e->getMessage());
}

// Verificar si el usuario fue creado
writeLog("Verificando si el usuario fue creado...");
try {
    $userCheck = getUserByEmail($testEmail);
    writeLog("Resultado de verificación: " . json_encode($userCheck, JSON_PRETTY_PRINT));
    
    if (isset($userCheck['ok']) && $userCheck['ok'] === true && isset($userCheck['usuario'])) {
        writeLog("ÉXITO: El usuario existe en la base de datos");
    } else {
        writeLog("ERROR: El usuario no existe en la base de datos");
    }
} catch (Exception $e) {
    writeLog("EXCEPCIÓN al verificar usuario: " . $e->getMessage());
}

// Inspeccionar el código fuente de callAPI
$callAPIDef = new ReflectionFunction('callAPI');
writeLog("Función callAPI definida en: " . $callAPIDef->getFileName() . " (línea " . $callAPIDef->getStartLine() . ")");

// Registrar opciones de cURL que se están utilizando
writeLog("Inspeccionando configuración de cURL en callAPI():");
$callAPISource = file_get_contents($callAPIDef->getFileName());
$callAPIFunction = substr($callAPISource, $callAPIDef->getStartLine() - 1, $callAPIDef->getEndLine() - $callAPIDef->getStartLine() + 1);
writeLog($callAPIFunction);

writeLog("=== Fin del diagnóstico ===");
?>

<h1>Diagnóstico de Registro de Usuarios</h1>
<p>El resultado del diagnóstico se ha guardado en el archivo: <?php echo $logFile; ?></p>
<p>Por favor revisa los resultados anteriores y el archivo de log para identificar posibles problemas.</p>

<h3>Posibles soluciones según el diagnóstico:</h3>
<ul>
    <li>Si no se pudo conectar al microservicio (Puerto 3009): Verifica que el servicio de usuarios esté en ejecución</li>
    <li>Si el microservicio respondió con un error: Revisa los logs del microservicio</li>
    <li>Si hay excepciones en PHP: Corrige los errores indicados en las excepciones</li>
    <li>Si se registró correctamente pero no aparece: Puede ser un problema en el microservicio o en la base de datos</li>
</ul>

<h3>Siguiente paso:</h3>
<a href="registro.php">Volver a la página de registro</a>