<?php
// configuracion.php
session_start();
require_once 'includes/api.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$usuario = $_SESSION['usuario'];

// Manejo de formulario
$mensaje = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $nombre = trim($_POST['nombre']);
    $email = trim($_POST['email']);
    $contrasena_actual = trim($_POST['contrasena_actual']);
    $contrasena_nueva = trim($_POST['contrasena_nueva']);
    $contrasena_confirmacion = trim($_POST['contrasena_confirmacion']);
    
    // Validaciones básicas
    if (empty($nombre) || empty($email)) {
        $error = "El nombre y el email son obligatorios";
    } else if (!empty($contrasena_nueva) && $contrasena_nueva !== $contrasena_confirmacion) {
        $error = "Las contraseñas nuevas no coinciden";
    } else {
        // Preparar datos para actualizar
        $userData = [
            "nombre" => $nombre,
            "email" => $email
        ];
        
        // Si se está cambiando la contraseña
        if (!empty($contrasena_actual) && !empty($contrasena_nueva)) {
            $userData["contrasena_actual"] = $contrasena_actual;
            $userData["contrasena_nueva"] = $contrasena_nueva;
        }
        
        // Actualizar usuario
        $response = updateUser($usuario['_id'], $userData);
        
        if (isset($response['ok']) && $response['ok'] === true) {
            $_SESSION['usuario'] = $response['usuario'];
            $usuario = $_SESSION['usuario'];
            $mensaje = "Perfil actualizado correctamente";
        } else {
            $error = $response['error'] ?? "Error al actualizar el perfil";
        }
    }
}
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Configuración - NeuroTask</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Google Fonts - Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <style>
    :root {
      /* Nueva paleta de colores */
      --orange-dark: rgba(249, 187, 87, 0.88);
      --orange-primary: rgb(250, 194, 110);
      --bluepurple-light: rgb(166, 128, 255);
      --blue-primary: #688db9;
      --purple: rgb(162, 120, 202);
      
      /* Colores complementarios */
      --white: #ffffff;
      --light-gray: #f5f6f8;
      --text-dark: #333333;
      --text-gray: #777777;
    }
    
    body {
      font-family: 'Montserrat', sans-serif;
      background-color: var(--light-gray);
      color: var(--text-dark);
      margin: 0;
      padding: 0;
    }
    
    .sidebar {
      width: 270px;
      height: 100vh;
      position: fixed;
      left: 0;
      top: 0;
      background-color: var(--bluepurple-light);
      color: white;
      z-index: 100;
      transition: all 0.3s ease;
    }
    
    .sidebar-logo {
      padding: 25px 20px;
      font-size: 2rem;
      font-weight: 600;
      display: flex;
      align-items: center;
    }
    
    .sidebar-logo i {
      margin-right: 15px;
      font-size: 24px;
    }
    
    .sidebar-nav {
      list-style: none;
      padding: 0;
      margin-top: 20px;
    }
    
    .sidebar-nav li {
      margin-bottom: 5px;
    }
    
    .sidebar-nav a {
      display: flex;
      align-items: center;
      padding: 15px 20px;
      text-decoration: none;
      color: rgba(255, 255, 255, 0.9);
      transition: all 0.3s;
      font-weight: 500;
      font-size: 1.1rem;
    }
    
    .sidebar-nav a.active {
      color: white;
      background-color: rgba(255, 255, 255, 0.2);
      border-left: 4px solid white;
    }
    
    .sidebar-nav a:hover {
      background-color: rgba(255, 255, 255, 0.1);
      color: white;
    }
    
    .sidebar-nav i {
      margin-right: 12px;
      width: 24px;
      text-align: center;
      font-size: 1.1rem;
    }
    
    .sidebar-bottom {
      position: absolute;
      bottom: 20px;
      width: 100%;
    }
    
    .content-wrapper {
      margin-left: 270px;
      min-height: 100vh;
      transition: all 0.3s ease;
    }
    
    .main-header {
      background-color: var(--white);
      padding: 20px 30px;
      border-bottom: 1px solid rgba(0,0,0,0.05);
      display: flex;
      justify-content: space-between;
      align-items: center;
      box-shadow: 0 2px 5px rgba(0,0,0,0.05);
    }
    
    .welcome-text {
      font-size: 1.3rem;
      color: var(--text-dark);
      font-weight: 500;
    }
    
    .main-content {
      padding: 30px;
    }
    
    .config-header {
      margin-bottom: 30px;
    }
    
    .config-title {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-dark);
    }
    
    .config-description {
      color: var(--text-gray);
      font-size: 1rem;
    }
    
    /* Estilos para las tarjetas de configuración */
    .config-card {
      background-color: white;
      border-radius: 12px;
      padding: 25px;
      margin-bottom: 25px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    
    .config-card h2 {
      font-weight: 600;
      margin-bottom: 20px;
      font-size: 1.3rem;
      color: var(--text-dark);
    }
    
    .config-section {
      margin-bottom: 25px;
    }
    
    .config-section h4 {
      font-weight: 600;
      margin-bottom: 15px;
      font-size: 1.1rem;
      color: var(--text-dark);
    }
    
    /* Avatar */
    .avatar-container {
      display: flex;
      flex-direction: column;
      align-items: center;
      margin-bottom: 25px;
    }
    
    .avatar {
      width: 100px;
      height: 100px;
      border-radius: 50%;
      background-color: var(--bluepurple-light);
      color: white;
      display: flex;
      align-items: center;
      justify-content: center;
      font-size: 2.5rem;
      font-weight: 600;
      margin-bottom: 15px;
    }
    
    /* Botones */
    .btn-primary {
      background-color: var(--bluepurple-light);
      border-color: var(--bluepurple-light);
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
    }
    
    .btn-primary:hover {
      background-color: var(--purple);
      border-color: var(--purple);
    }
    
    .btn-danger {
      background-color: #dc3545;
      border-color: #dc3545;
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
    }
    
    .btn-danger:hover {
      background-color: #bb2d3b;
      border-color: #bb2d3b;
    }
    
    .btn-secondary {
      background-color: #6c757d;
      border-color: #6c757d;
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
    }
    
    .btn-secondary:hover {
      background-color: #5c636a;
      border-color: #5c636a;
    }
    
    /* Form switch (toggles) */
    .form-check-input:checked {
      background-color: var(--bluepurple-light);
      border-color: var(--bluepurple-light);
    }
    
    /* Modal */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .modal-header {
      border-bottom: 1px solid rgba(0,0,0,0.05);
      padding: 20px 25px;
    }
    
    .modal-body {
      padding: 25px;
    }
    
    .modal-footer {
      border-top: 1px solid rgba(0,0,0,0.05);
      padding: 15px 25px;
    }
    
    /* Toast notifications */
    .toast-container {
      position: fixed;
      bottom: 20px;
      right: 20px;
      z-index: 1050;
    }
    
    .toast {
      background-color: white;
      color: var(--text-dark);
      border: none;
      border-radius: 8px;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.1);
    }
    
    .toast-header {
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .toast-success .toast-header {
      background-color: #28a745;
      color: white;
    }
    
    .toast-error .toast-header {
      background-color: #dc3545;
      color: white;
    }
    
    /* Mobile menu button */
    .mobile-menu-btn {
      display: none;
      align-items: center;
      justify-content: center;
      width: 40px;
      height: 40px;
      background-color: var(--bluepurple-light);
      color: white;
      border-radius: 8px;
      cursor: pointer;
      border: none;
      font-size: 1.2rem;
      position: fixed;
      top: 15px;
      left: 15px;
      z-index: 1200;
      box-shadow: 0 2px 5px rgba(0,0,0,0.2);
    }
    
    /* Overlay for mobile sidebar */
    .sidebar-overlay {
      position: fixed;
      top: 0;
      left: 0;
      width: 100%;
      height: 100%;
      background-color: rgba(0,0,0,0.5);
      z-index: 1050;
      display: none;
      opacity: 0;
      transition: opacity 0.3s;
    }
    
    .sidebar-overlay.show {
      display: block;
      opacity: 1;
    }
    
    /* Media queries */
    @media (max-width: 992px) {
      .sidebar {
        width: 70px;
      }
      
      .sidebar-logo span, .sidebar-nav span {
        display: none;
      }
      
      .sidebar-nav i {
        margin-right: 0;
        font-size: 1.2rem;
      }
      
      .content-wrapper {
        margin-left: 70px;
      }
      
      .config-title {
        font-size: 1.7rem;
      }
    }
    
    @media (max-width: 768px) {
      .sidebar {
        width: 0;
        transform: translateX(-100%);
      }
      
      .sidebar.show {
        width: 250px;
        transform: translateX(0);
      }
      
      .sidebar-logo span, .sidebar-nav span {
        display: block;
      }
      
      .sidebar-nav i {
        margin-right: 12px;
      }
      
      .content-wrapper {
        margin-left: 0;
      }
      
      .mobile-menu-btn {
        display: flex;
      }
      
      .main-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .welcome-text {
        margin-bottom: 15px;
      }
      
      .avatar {
        width: 80px;
        height: 80px;
        font-size: 2rem;
      }
    }
    
    @media (max-width: 576px) {
      .main-header {
        padding: 15px;
      }
      
      .main-content {
        padding: 15px;
      }
      
      .config-card {
        padding: 20px 15px;
      }
      
      .config-title {
        font-size: 1.5rem;
      }
      
      .btn-primary, .btn-secondary, .btn-danger {
        width: 100%;
        margin-bottom: 10px;
      }
      
      .d-flex.justify-content-end {
        flex-direction: column;
      }
      
      .form-check-input {
        transform: scale(1.1);
      }
    }
    
    /* Animación para notificaciones */
    @keyframes fadeInUp {
      from { opacity: 0; transform: translateY(20px); }
      to { opacity: 1; transform: translateY(0); }
    }
    
    .alert {
      animation: fadeInUp 0.4s ease-out forwards;
    }
  </style>
</head>
<body>
  <!-- Contenedor de Notificaciones -->
  <div class="toast-container"></div>
  
  <!-- Botón de menú móvil -->
  <button class="mobile-menu-btn" id="mobileMenuBtn">
    <i class="fas fa-bars"></i>
  </button>
  
  <!-- Overlay para menú móvil -->
  <div class="sidebar-overlay" id="sidebarOverlay"></div>
  
  <!-- Sidebar -->
  <div class="sidebar" id="sidebar">
    <div class="sidebar-logo">
      <i class="fas fa-tasks"></i>
      <span>NeuroTask</span>
    </div>
    
    <ul class="sidebar-nav">
      <li>
        <a href="dashboard.php">
          <i class="fas fa-columns"></i>
          <span>Inicio</span>
        </a>
      </li>
      <li>
        <a href="proyectos.php">
          <i class="fas fa-folder-open"></i>
          <span>Proyectos</span>
        </a>
      </li>
    </ul>
    
    <div class="sidebar-bottom">
      <ul class="sidebar-nav">
        <li>
          <a href="dashboard.php">
            <i class="fas fa-user"></i>
            <span>Mi Perfil</span>
          </a>
        </li>
        <li>
          <a href="configuracion.php" class="active">
            <i class="fas fa-cog"></i>
            <span>Configuración</span>
          </a>
        </li>
        <li>
          <a href="logout.php">
            <i class="fas fa-sign-out-alt"></i>
            <span>Cerrar Sesión</span>
          </a>
        </li>
      </ul>
    </div>
  </div>

  <!-- Contenido Principal -->
  <div class="content-wrapper">
    <!-- Header -->
    <div class="main-header">
      <div class="welcome-text">
        Configuración de Cuenta
      </div>
      
      <div class="d-flex align-items-center">
        <a href="dashboard.php" class="btn btn-secondary me-2">
          <i class="fas fa-arrow-left me-1"></i> Volver al Dashboard
        </a>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-content">
      <?php if($mensaje): ?>
        <div class="alert alert-success"><?= htmlspecialchars($mensaje) ?></div>
      <?php endif; ?>
      
      <?php if($error): ?>
        <div class="alert alert-danger"><?= htmlspecialchars($error) ?></div>
      <?php endif; ?>
      
      <div class="row">
        <!-- Columna Izquierda - Perfil -->
        <div class="col-lg-4 mb-4">
          <!-- Tarjeta de Usuario -->
          <div class="config-card">
            <div class="avatar-container">
              <div class="avatar">
                <?= strtoupper(substr($usuario['nombre'], 0, 1)) ?>
              </div>
              <h3><?= htmlspecialchars($usuario['nombre']) ?></h3>
              <p class="text-muted"><?= htmlspecialchars($usuario['email']) ?></p>
            </div>
            
            <div class="d-grid gap-2">
              <button class="btn btn-primary">
                <i class="fas fa-camera me-2"></i> Cambiar foto
              </button>
            </div>
          </div>
        </div>
        
        <!-- Columna Derecha - Configuraciones -->
        <div class="col-lg-8">
          <!-- Perfil General -->
          <div class="config-card">
            <h2>Información Personal</h2>
            
            <form method="POST" action="configuracion.php">
              <div class="row">
                <div class="col-md-6 mb-3">
                  <label for="nombre" class="form-label">Nombre completo</label>
                  <input type="text" class="form-control" id="nombre" name="nombre" value="<?= htmlspecialchars($usuario['nombre']) ?>" required>
                </div>
                <div class="col-md-6 mb-3">
                  <label for="email" class="form-label">Correo electrónico</label>
                  <input type="email" class="form-control" id="email" name="email" value="<?= htmlspecialchars($usuario['email']) ?>" required>
                </div>
              </div>
              
              <div class="config-section">
                <h4>Cambiar Contraseña</h4>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="contrasena_actual" class="form-label">Contraseña Actual</label>
                    <input type="password" class="form-control" id="contrasena_actual" name="contrasena_actual">
                  </div>
                </div>
                <div class="row">
                  <div class="col-md-6 mb-3">
                    <label for="contrasena_nueva" class="form-label">Nueva Contraseña</label>
                    <input type="password" class="form-control" id="contrasena_nueva" name="contrasena_nueva">
                  </div>
                  <div class="col-md-6 mb-3">
                    <label for="contrasena_confirmacion" class="form-label">Confirmar Nueva Contraseña</label>
                    <input type="password" class="form-control" id="contrasena_confirmacion" name="contrasena_confirmacion">
                  </div>
                </div>
              </div>
              
              <div class="d-flex justify-content-end mt-4">
                <a href="dashboard.php" class="btn btn-secondary me-2">Cancelar</a>
                <button type="submit" class="btn btn-primary">
                  <i class="fas fa-save me-2"></i> Guardar Cambios
                </button>
              </div>
            </form>
          </div>
          
          <!-- Preferencias de Notificación -->
          <div class="config-card">
            <h2>Preferencias de Notificación</h2>
            <div class="row">
              <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="emailNotifications" checked>
                  <label class="form-check-label" for="emailNotifications">Notificaciones por email</label>
                </div>
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="taskReminders" checked>
                  <label class="form-check-label" for="taskReminders">Recordatorios de tareas</label>
                </div>
              </div>
              <div class="col-md-6">
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="projectUpdates" checked>
                  <label class="form-check-label" for="projectUpdates">Actualizaciones de proyectos</label>
                </div>
                <div class="form-check form-switch mb-3">
                  <input class="form-check-input" type="checkbox" id="weeklyDigest">
                  <label class="form-check-label" for="weeklyDigest">Resumen semanal</label>
                </div>
              </div>
            </div>
            <div class="d-flex justify-content-end mt-3">
              <button type="button" class="btn btn-primary">
                <i class="fas fa-save me-2"></i> Guardar Preferencias
              </button>
            </div>
          </div>
          
          <!-- Eliminar Cuenta -->
          <div class="config-card">
            <h2>Eliminar Cuenta</h2>
            <p class="text-muted">Esta acción eliminará permanentemente tu cuenta y todos tus datos personales. Esta acción no se puede deshacer.</p>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-danger" data-bs-toggle="modal" data-bs-target="#deleteAccountModal">
                <i class="fas fa-trash-alt me-2"></i> Eliminar mi cuenta
              </button>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Eliminar Cuenta -->
  <div class="modal fade" id="deleteAccountModal" tabindex="-1" aria-labelledby="deleteAccountModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteAccountModalLabel">Confirmar Eliminación de Cuenta</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div class="alert alert-danger">
            <i class="fas fa-exclamation-triangle me-2"></i>
            Esta acción es permanente y no se puede deshacer. Todos tus datos y proyectos serán eliminados.
          </div>
          <div class="mb-3">
            <label for="deleteConfirmation" class="form-label">Escribe "ELIMINAR" para confirmar</label>
            <input type="text" class="form-control" id="deleteConfirmation">
          </div>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn" disabled>
            <i class="fas fa-trash-alt me-2"></i> Eliminar Cuenta
          </button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Validación para el botón de eliminar cuenta
      const deleteConfirmationInput = document.getElementById('deleteConfirmation');
      const confirmDeleteBtn = document.getElementById('confirmDeleteBtn');
      
      deleteConfirmationInput.addEventListener('input', function() {
        confirmDeleteBtn.disabled = this.value !== 'ELIMINAR';
      });
      
      confirmDeleteBtn.addEventListener('click', function() {
        if (deleteConfirmationInput.value === 'ELIMINAR') {
          // Aquí iría la lógica para eliminar la cuenta
          window.location.href = 'ajax/eliminar_cuenta.php';
        }
      });
      
      // Manejo del menú móvil
      const mobileMenuBtn = document.getElementById('mobileMenuBtn');
      const sidebar = document.getElementById('sidebar');
      const sidebarOverlay = document.getElementById('sidebarOverlay');
      
      if (mobileMenuBtn) {
        mobileMenuBtn.addEventListener('click', function() {
          sidebar.classList.toggle('show');
          sidebarOverlay.classList.toggle('show');
          document.body.style.overflow = sidebar.classList.contains('show') ? 'hidden' : '';
        });
      }
      
      if (sidebarOverlay) {
        sidebarOverlay.addEventListener('click', function() {
          sidebar.classList.remove('show');
          sidebarOverlay.classList.remove('show');
          document.body.style.overflow = '';
        });
      }
      
      // Mostrar notificaciones de éxito o error mediante toast
      function showNotification(message, type) {
        const toastContainer = document.querySelector('.toast-container');
        
        const toastElement = document.createElement('div');
        toastElement.className = `toast toast-${type} show`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        let iconClass = 'info-circle';
        let title = 'Información';
        
        if (type === 'success') {
          iconClass = 'check-circle';
          title = 'Éxito';
        } else if (type === 'error') {
          iconClass = 'exclamation-circle';
          title = 'Error';
        }
        
        toastElement.innerHTML = `
          <div class="toast-header">
            <i class="fas fa-${iconClass} me-2"></i>
            <strong class="me-auto">${title}</strong>
            <small>Ahora</small>
            <button type="button" class="btn-close" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body">${message}</div>
        `;
        
        toastContainer.appendChild(toastElement);
        
        // Crear y mostrar el toast
        const toast = new bootstrap.Toast(toastElement, {
          autohide: true,
          delay: 5000
        });
        
        toast.show();
        
        // Eliminar el toast del DOM cuando se oculta
        toastElement.addEventListener('hidden.bs.toast', function() {
          toastElement.remove();
        });
      }
      
      // Si hay mensajes o errores, mostrarlos con el sistema de notificaciones
      <?php if($mensaje): ?>
        //showNotification('<?= htmlspecialchars($mensaje) ?>', 'success');
      <?php endif; ?>
      
      <?php if($error): ?>
        //showNotification('<?= htmlspecialchars($error) ?>', 'error');
      <?php endif; ?>
    });
  </script>
</body>
</html>