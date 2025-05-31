<?php
// proyectos.php - versión mejorada y corregida
session_start();
require_once 'includes/api.php';

if (!isset($_SESSION['usuario'])) {
    header("Location: login.php");
    exit();
}
$usuario = $_SESSION['usuario'];

// Obtener los proyectos del usuario
$proyectos = getUserProjects($usuario['_id']);
$proyectosData = isset($proyectos['proyectos']) ? $proyectos['proyectos'] : [];

$colores = [
    // Azul celeste/medio
    ['bg' => 'rgba(219, 206, 232, 0.77)', 'header' => '#D3D3D3', 'btn' => '#D3D3D3'],
];

// Mezclar el array de colores para asignarlos aleatoriamente
shuffle($colores);
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>Administración de Proyectos - NeuroTask</title>
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
      --bluepurple-light: rgb(198, 176, 249);
      --blue-primary: #688db9;
      --purple: rgb(178, 125, 228);
      
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
      background-image: linear-gradient(135deg, var(--bluepurple-light) 0%, var(--blue-primary) 100%);
      color: white;
      z-index: 100;
      transition: all 0.3s ease;
      box-shadow: 2px 0 10px rgba(0, 0, 0, 0.1);
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
    
    .page-title {
      font-size: 1.5rem;
      font-weight: 600;
      color: var(--text-dark);
    }
    
    .page-title h1 {
      margin: 0;
      font-size: 1.8rem;
    }
    
    .main-content {
      padding: 30px;
    }
    
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
      transform: translateY(-2px);
      box-shadow: 0 4px 8px rgba(166, 128, 255, 0.3);
    }
    
    /* Cards de proyectos - Ahora más pequeñas */
    .projects-grid {
      display: grid;
      grid-template-columns: repeat(auto-fill, minmax(250px, 1fr));
      gap: 20px;
    }
    
    .project-card {
      background-color: var(--white);
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.08);
      transition: transform 0.3s, box-shadow 0.3s;
      height: 100%;
      display: flex;
      flex-direction: column;
      max-width: 100%;
    }
    
    .project-card:hover {
      transform: translateY(-5px);
      box-shadow: 0 8px 15px rgba(0, 0, 0, 0.12);
    }
    
    .project-header {
      padding: 15px;
      color: var(--text-dark);
      font-weight: 600;
    }
    
    .project-header h2 {
      margin: 0;
      font-size: 1.1rem;
      font-weight: 600;
      color: var(--text-dark);
      white-space: nowrap;
      overflow: hidden;
      text-overflow: ellipsis;
    }
    
    .project-body {
      padding: 15px;
      flex: 1;
    }
    
    .project-description {
      color: var(--text-gray);
      margin-bottom: 15px;
      display: -webkit-box;
      -webkit-line-clamp: 3;
      -webkit-box-orient: vertical;
      overflow: hidden;
      text-overflow: ellipsis;
      max-height: 4.5rem;
      font-size: 0.9rem;
    }
    
    .project-meta {
      font-size: 0.8rem;
      color: var(--text-gray);
      margin-bottom: 15px;
    }
    
    .project-meta i {
      width: 16px;
      margin-right: 5px;
    }
    
    .project-footer {
      padding: 10px 15px;
      display: flex;
      justify-content: space-between;
      gap: 5px;
    }
    
    .btn-action {
      padding: 6px 8px;
      border-radius: 6px;
      font-size: 0.8rem;
      font-weight: 500;
      display: flex;
      align-items: center;
      justify-content: center;
      white-space: nowrap;
      flex: 1;
    }
    
    .btn-action i {
      margin-right: 4px;
    }
    
    .btn-view, .btn-edit, .btn-delete {
      border: none;
      color: white;
      transition: all 0.2s;
    }
    
    .btn-view:hover, .btn-edit:hover, .btn-delete:hover {
      transform: translateY(-2px);
      filter: brightness(110%);
      color: white;
    }
    
    .btn-delete {
      background-color: #dc3545;
    }
    
    /* Estilos para el modal */
    .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.1);
    }
    
    .modal-header {
      padding: 20px 25px;
      border-bottom: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    .modal-title {
      font-weight: 600;
      color: var(--text-dark);
    }
    
    .modal-body {
      padding: 25px;
    }
    
    .form-label {
      font-weight: 500;
      color: var(--text-dark);
    }
    
    .form-control, .form-select {
      border-radius: 8px;
      padding: 12px 15px;
      border: 1px solid #ced4da;
      transition: all 0.3s;
    }
    
    .form-control:focus, .form-select:focus {
      box-shadow: 0 0 0 0.25rem rgba(148, 113, 255, 0.25);
      border-color: var(--bluepurple-light);
    }
    
    .modal-footer {
      padding: 15px 25px;
      border-top: 1px solid rgba(0, 0, 0, 0.05);
    }
    
    /* Notificaciones */
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
    
    .toast-warning .toast-header {
      background-color: var(--orange-dark);
      color: white;
    }
    
    .empty-state {
      text-align: center;
      padding: 60px 20px;
      background-color: var(--white);
      border-radius: 12px;
      box-shadow: 0 4px 12px rgba(0, 0, 0, 0.05);
    }
    
    .empty-state i {
      font-size: 4rem;
      color: var(--text-gray);
      margin-bottom: 20px;
    }
    
    .empty-state h3 {
      font-weight: 600;
      margin-bottom: 15px;
      color: var(--text-dark);
    }
    
    .empty-state p {
      color: var(--text-gray);
      margin-bottom: 25px;
      max-width: 500px;
      margin-left: auto;
      margin-right: auto;
    }
    
    .project-count {
      background-color: rgba(255, 255, 255, 0.2);
      color: var(--text-dark);
      border-radius: 20px;
      padding: 3px 10px;
      font-size: 0.8rem;
      font-weight: 500;
      margin-left: 10px;
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
      
      .projects-grid {
        grid-template-columns: repeat(auto-fill, minmax(200px, 1fr));
      }
    }
    
    @media (max-width: 768px) {
      .projects-grid {
        grid-template-columns: repeat(auto-fill, minmax(180px, 1fr));
      }
      
      .main-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .page-title {
        margin-bottom: 15px;
      }
      
      .btn-action {
        padding: 6px;
      }
      
      .btn-action span {
        display: none;
      }
      
      .btn-action i {
        margin: 0;
        font-size: 1rem;
      }
    }
    
    @media (max-width: 576px) {
      .sidebar {
        width: 0;
        transform: translateX(-100%);
      }
      
      .content-wrapper {
        margin-left: 0;
      }
      
      .main-header {
        padding: 15px;
      }
      
      .main-content {
        padding: 15px;
      }
      
      .projects-grid {
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 15px;
      }
      
      .project-header {
        padding: 10px;
      }
      
      .project-header h2 {
        font-size: 1rem;
      }
      
      .project-body {
        padding: 10px;
      }
      
      .project-description {
        -webkit-line-clamp: 2;
        font-size: 0.85rem;
      }
      
      .project-footer {
        padding: 8px 10px;
      }
    }
  </style>
</head>
<body>
  <!-- Contenedor de Notificaciones -->
  <div class="toast-container"></div>
  
  <!-- Sidebar -->
  <div class="sidebar">
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
        <a href="proyectos.php" class="active">
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
          <a href="configuracion.php">
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
      <div class="page-title">
        <h1>Gestión de Proyectos <span class="project-count"><?= count($proyectosData) ?> proyectos</span></h1>
      </div>
      
      <div>
        <button class="btn btn-primary" id="createProjectBtn">
          <i class="fas fa-plus me-1"></i> Crear Nuevo Proyecto
        </button>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-content">
      <?php if (empty($proyectosData)): ?>
        <!-- Estado vacío -->
        <div class="empty-state">
          <i class="fas fa-folder-open"></i>
          <h3>No tienes proyectos todavía</h3>
          <p>Crea tu primer proyecto para comenzar a organizar tus tareas y colaborar con tu equipo.</p>
          <button class="btn btn-primary" id="createFirstProjectBtn">
            <i class="fas fa-plus me-1"></i> Crear mi primer proyecto
          </button>
        </div>
      <?php else: ?>
        <!-- Grid de proyectos -->
        <div class="projects-grid">
          <?php 
          $colorIndex = 0;
          foreach ($proyectosData as $proyecto): 
            // Asignar color aleatorio de la paleta
            $color = $colores[$colorIndex % count($colores)];
            $colorIndex++;
          ?>
            <div class="project-card" data-project-id="<?= $proyecto['_id'] ?>">
              <div class="project-header" style="background-color: <?= $color['header'] ?>;">
                <h2><?= htmlspecialchars($proyecto['nombre']) ?></h2>
              </div>
              <div class="project-body" style="background-color: <?= $color['bg'] ?>;">
                <p class="project-description"><?= htmlspecialchars($proyecto['descripcion']) ?></p>
                <div class="project-meta">
                  <p><i class="fas fa-calendar-alt"></i> <?= isset($proyecto['createdAt']) ? date('d/m/Y', strtotime($proyecto['createdAt'])) : 'N/A' ?></p>
                  <p><i class="fas fa-tasks"></i> 
                    <?php
                    // Obtener el conteo de tareas para este proyecto
                    $tareasResponse = getTasksByProject($proyecto['_id']);
                    $numTareas = isset($tareasResponse['tareas']) ? count($tareasResponse['tareas']) : 0;
                    echo $numTareas . ' ' . ($numTareas == 1 ? 'tarea' : 'tareas');
                    ?>
                  </p>
                </div>
              </div>
              <div class="project-footer" style="background-color: <?= $color['bg'] ?>;">
                <a href="dashboard.php?proyecto_id=<?= $proyecto['_id'] ?>" class="btn btn-action btn-view" style="background-color: <?= $color['btn'] ?>;">
                  <i class="fas fa-eye"></i> <span>Ver</span>
                </a>
                <button class="btn btn-action btn-edit edit-project-btn" data-project-id="<?= $proyecto['_id'] ?>" data-project-name="<?= htmlspecialchars($proyecto['nombre']) ?>" style="background-color: <?= $color['btn'] ?>;">
                  <i class="fas fa-edit"></i> <span>Editar</span>
                </button>
                <button class="btn btn-action btn-delete btn-eliminar-proyecto" data-id="<?= $proyecto['_id'] ?>" data-project-name="<?= htmlspecialchars($proyecto['nombre']) ?>">
                  <i class="fas fa-trash-alt"></i> <span>Eliminar</span>
                </button>
              </div>
            </div>
          <?php endforeach; ?>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal para Crear/Editar Proyecto -->
  <div class="modal fade" id="projectModal" tabindex="-1" aria-labelledby="projectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="projectModalLabel">Crear Proyecto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="projectForm">
            <input type="hidden" id="projectId" name="id">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre del Proyecto</label>
              <input type="text" class="form-control" id="nombre" name="nombre" required>
            </div>
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción</label>
              <textarea class="form-control" id="descripcion" name="descripcion" rows="4" required></textarea>
            </div>
          </form>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-primary" id="saveProjectBtn">Guardar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Confirmar Eliminación -->
  <div class="modal fade" id="deleteProjectModal" tabindex="-1" aria-labelledby="deleteProjectModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="deleteProjectModalLabel">Confirmar Eliminación</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <p>¿Estás seguro de que deseas eliminar el proyecto "<span id="projectNameToDelete"></span>"?</p>
          <p class="text-danger">Esta acción no se puede deshacer. Todas las tareas asociadas también serán eliminadas.</p>
        </div>
        <div class="modal-footer">
          <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancelar</button>
          <button type="button" class="btn btn-danger" id="confirmDeleteBtn">Eliminar</button>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Script para eliminar proyectos -->
  <script>
    // Código JavaScript para manejar la eliminación de proyectos
    document.addEventListener('DOMContentLoaded', function() {
      // Seleccionar todos los botones de eliminar proyecto
      const botonesEliminar = document.querySelectorAll('.btn-eliminar-proyecto');
      
      botonesEliminar.forEach(boton => {
        boton.addEventListener('click', function(e) {
          e.preventDefault();
          e.stopPropagation(); // Evitar que el clic se propague a la tarjeta
          
          // Obtener el ID del proyecto
          const proyectoId = this.getAttribute('data-id');
          const proyectoNombre = this.getAttribute('data-project-name');
          
          // Confirmar la eliminación
          if (confirm(`¿Estás seguro de que deseas eliminar el proyecto "${proyectoNombre}"? Esta acción no se puede deshacer.`)) {
            // Guardar referencia al botón
            const btnElement = this;
            
            // Mostrar indicador de carga
            const originalText = btnElement.innerHTML;
            btnElement.innerHTML = '<i class="fas fa-spinner fa-spin"></i>';
            btnElement.disabled = true;
            
            // Preparar datos para la solicitud
            const formData = new FormData();
            formData.append('id', proyectoId);
            
            // Enviar solicitud AJAX
            fetch('ajax/eliminar_proyecto.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              console.log('Respuesta del servidor:', data);
              
              if (data.ok) {
                // Éxito: eliminar la tarjeta con animación
                const card = btnElement.closest('.project-card');
                card.style.transition = 'all 0.3s ease';
                card.style.opacity = '0';
                card.style.transform = 'scale(0.9)';
                
                setTimeout(() => {
                  card.remove();
                  
                  // Actualizar contador si existe
                  const contador = document.querySelector('.project-count');
                  if (contador) {
                    const currentCount = parseInt(contador.textContent);
                    const newCount = currentCount - 1;
                    contador.textContent = newCount + ' proyectos';
                    
                    // Si no quedan proyectos, mostrar estado vacío
                    if (newCount === 0) {
                      document.querySelector('.main-content').innerHTML = `
                        <div class="empty-state">
                          <i class="fas fa-folder-open"></i>
                          <h3>No tienes proyectos todavía</h3>
                          <p>Crea tu primer proyecto para comenzar a organizar tus tareas y colaborar con tu equipo.</p>
                          <button class="btn btn-primary" id="createFirstProjectBtn">
                            <i class="fas fa-plus me-1"></i> Crear mi primer proyecto
                          </button>
                        </div>
                      `;
                      
                      // Volver a añadir evento al nuevo botón
                      document.getElementById('createFirstProjectBtn').addEventListener('click', function() {
                        resetProjectModal();
                        document.getElementById('projectModalLabel').textContent = 'Crear Proyecto';
                        new bootstrap.Modal(document.getElementById('projectModal')).show();
                      });
                    }
                  }
                  
                  // Mostrar mensaje de éxito
                  showNotification('Proyecto eliminado correctamente', 'success');
                }, 300);
              } else {
                // Error: restaurar botón y mostrar mensaje
                btnElement.innerHTML = originalText;
                btnElement.disabled = false;
                showNotification('Error: ' + (data.error || 'No se pudo eliminar el proyecto'), 'error');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              btnElement.innerHTML = originalText;
              btnElement.disabled = false;
              showNotification('Error de conexión. Inténtelo de nuevo.', 'error');
            });
          }
        });
      });
      
      // Función para mostrar notificaciones
      function showNotification(mensaje, tipo) {
        // Crear container si no existe
        let container = document.querySelector('.toast-container');
        if (!container) {
          container = document.createElement('div');
          container.className = 'toast-container position-fixed top-0 end-0 p-3';
          container.style.zIndex = '1050';
          document.body.appendChild(container);
        }
        
        // Crear toast
        const toast = document.createElement('div');
        toast.className = `toast bg-${tipo === 'success' ? 'success' : 'danger'} text-white`;
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        toast.innerHTML = `
          <div class="toast-header bg-${tipo === 'success' ? 'success' : 'danger'} text-white">
            <strong class="me-auto">${tipo === 'success' ? 'Éxito' : 'Error'}</strong>
            <small>Ahora</small>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast" aria-label="Close"></button>
          </div>
          <div class="toast-body">
            ${mensaje}
          </div>
        `;
        
        // Añadir al container
        container.appendChild(toast);
        
        // Inicializar toast
        const bsToast = new bootstrap.Toast(toast, {
          autohide: true,
          delay: 5000
        });
        
        bsToast.show();
        
        // Eliminar del DOM cuando se oculta
        toast.addEventListener('hidden.bs.toast', () => toast.remove());
      }
    });
  </script>
  
  <!-- Script principal -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Referencias a los modales
      const projectModal = new bootstrap.Modal(document.getElementById('projectModal'));
      
      // Referencias a botones y formularios
      const createProjectBtn = document.getElementById('createProjectBtn');
      const createFirstProjectBtn = document.getElementById('createFirstProjectBtn');
      const saveProjectBtn = document.getElementById('saveProjectBtn');
      const projectForm = document.getElementById('projectForm');
      const projectModalLabel = document.getElementById('projectModalLabel');
      
      // Evento para abrir el modal de crear proyecto
      [createProjectBtn, createFirstProjectBtn].forEach(btn => {
        if (btn) {
          btn.addEventListener('click', function() {
            resetProjectModal();
            projectModalLabel.textContent = 'Crear Proyecto';
            projectModal.show();
          });
        }
      });
      
      // Evento para guardar proyecto (crear o editar)
      saveProjectBtn.addEventListener('click', function() {
        if (!projectForm.checkValidity()) {
          projectForm.reportValidity();
          return;
        }
        
        // Mostrar indicador de carga en el botón
        const originalBtnText = saveProjectBtn.innerHTML;
        saveProjectBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
        saveProjectBtn.disabled = true;
        
        const projectId = document.getElementById('projectId').value;
        const formData = new FormData(projectForm);
        
        // Determinar si es una creación o actualización
        let url = 'ajax/crear_proyecto.php';
        let method = 'POST';
        
        if (projectId) {
          // Es una edición
          url = 'ajax/actualizar_proyecto.php';
        }
        
        // Enviar solicitud
        fetch(url, {
          method: method,
          body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
          // Restaurar el botón
          saveProjectBtn.innerHTML = originalBtnText;
          saveProjectBtn.disabled = false;
          
          if (data.ok) {
            projectModal.hide();
            // Mostrar notificación de éxito
            showNotification(projectId ? 'Proyecto actualizado con éxito' : 'Proyecto creado con éxito', 'success');
            // Recargar la página para ver los cambios
            setTimeout(() => {
              window.location.reload();
            }, 1000);
          } else {
            // Mostrar error
            showNotification('Error: ' + (data.error || 'Ha ocurrido un error'), 'error');
          }
        })
        .catch(error => {
          // Restaurar el botón
          saveProjectBtn.innerHTML = originalBtnText;
          saveProjectBtn.disabled = false;
          
          console.error('Error:', error);
          showNotification('Error al procesar la solicitud', 'error');
        });
      });
      
      // Evento para abrir el modal de edición de proyecto
      document.querySelectorAll('.edit-project-btn').forEach(btn => {
        btn.addEventListener('click', function(e) {
          e.stopPropagation();
          const projectId = this.dataset.projectId;
          loadProjectDetails(projectId);
        });
      });
      
      // Función para cargar detalles de un proyecto para edición
      function loadProjectDetails(projectId) {
        // Mostrar mensaje de carga en el modal
        resetProjectModal();
        document.getElementById('projectId').value = projectId;
        projectModalLabel.textContent = 'Editar Proyecto';
        projectModal.show();
        
        // Obtener detalles del proyecto
        fetch(`ajax/obtener_proyecto.php?id=${projectId}`)
          .then(response => response.json())
          .then(data => {
            if (data.ok && data.proyecto) {
              const proyecto = data.proyecto;
              document.getElementById('nombre').value = proyecto.nombre || '';
              document.getElementById('descripcion').value = proyecto.descripcion || '';
            } else {
              showNotification('Error: ' + (data.error || 'No se pudo cargar el proyecto'), 'error');
              projectModal.hide();
            }
          })
          .catch(error => {
            console.error('Error:', error);
            showNotification('Error al cargar los detalles del proyecto', 'error');
            projectModal.hide();
          });
      }
      
      // Hacer que toda la tarjeta sea clickeable para ir al tablero
      document.querySelectorAll('.project-card').forEach(card => {
        card.addEventListener('click', function(e) {
          // Si el clic no es en un botón
          if (!e.target.closest('.btn-action')) {
            const projectId = this.dataset.projectId;
            window.location.href = `dashboard.php?proyecto_id=${projectId}`;
          }
        });
      });
      
      // Función para restablecer el modal
      function resetProjectModal() {
        projectForm.reset();
        document.getElementById('projectId').value = '';
      }
      
      // Función para mostrar notificaciones
      function showNotification(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        
        const toastElement = document.createElement('div');
        toastElement.className = `toast toast-${type} show`;
        toastElement.setAttribute('role', 'alert');
        toastElement.setAttribute('aria-live', 'assertive');
        toastElement.setAttribute('aria-atomic', 'true');
        
        let iconClass = 'info-circle';
        let title = 'Información';
        
        switch (type) {
          case 'success':
            iconClass = 'check-circle';
            title = 'Éxito';
            break;
          case 'error':
            iconClass = 'exclamation-circle';
            title = 'Error';
            break;
          case 'warning':
            iconClass = 'exclamation-triangle';
            title = 'Advertencia';
            break;
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
    });
  </script>
</body>
</html>