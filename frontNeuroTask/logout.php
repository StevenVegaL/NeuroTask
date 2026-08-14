<?php
/**
 * logout.php
 * 
 * Script para cerrar la sesión del usuario y redirigir a la página de inicio.
 */
session_start();

// Preparar mensaje de cierre de sesión (opcional)
if (isset($_SESSION['usuario'])) {
    $nombre = $_SESSION['usuario']['nombre'] ?? 'Usuario';
    $_SESSION['logout_message'] = "¡Hasta pronto, {$nombre}! Has cerrado sesión correctamente.";
}

// Destruir todas las variables de sesión
$_SESSION = array();

// Si se desea destruir completamente la sesión, borrar también la cookie de sesión
if (ini_get("session.use_cookies")) {
    $params = session_get_cookie_params();
    setcookie(session_name(), '', time() - 42000,
        $params["path"], $params["domain"],
        $params["secure"], $params["httponly"]
    );
}

// Finalmente, destruir la sesión
session_destroy();

// Redirigir a la página principal
header("Location: index.php");
exit();
?>