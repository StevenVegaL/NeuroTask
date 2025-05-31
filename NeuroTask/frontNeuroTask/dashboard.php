<?php
// dashboard.php (versión actualizada con correcciones para mensajes)
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

// Si hay un proyecto seleccionado
$proyecto_seleccionado = null;
$tareas = [];

if (isset($_GET['proyecto_id']) && !empty($_GET['proyecto_id'])) {
    $proyecto_id = $_GET['proyecto_id'];
    
    // Buscar el proyecto seleccionado en los proyectos del usuario
    foreach ($proyectosData as $proyecto) {
        if ($proyecto['_id'] == $proyecto_id) {
            $proyecto_seleccionado = $proyecto;
            break;
        }
    }
    
    // Obtener tareas del proyecto
    $tareasResponse = getTasksByProject($proyecto_id);
    if (isset($tareasResponse['ok']) && $tareasResponse['ok'] === true) {
        $tareas = $tareasResponse['tareas'];
    }
}

// Agrupar tareas por estado
$tareasPorEstado = [
    'Por hacer' => [],
    'En progreso' => [],
    'Hecho' => []
];

foreach ($tareas as $tarea) {
    if (isset($tarea['estado']) && array_key_exists($tarea['estado'], $tareasPorEstado)) {
        $tareasPorEstado[$tarea['estado']][] = $tarea;
    }
}

// Obtener todos los usuarios para asignación de tareas
$allUsers = getAllUsers();
$usersData = isset($allUsers['usuarios']) ? $allUsers['usuarios'] : [];
?>

<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>NeuroTask - Gestión de Tareas</title>
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Bootstrap 5 -->
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
  <!-- Font Awesome -->
  <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
  <!-- Google Fonts - Montserrat -->
  <link rel="preconnect" href="https://fonts.googleapis.com">
  <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
  <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@300;400;500;600;700&display=swap" rel="stylesheet">
  <!-- Drag and Drop -->
  <script src="https://cdn.jsdelivr.net/npm/sortablejs@latest/Sortable.min.js"></script>
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
    
    .project-header {
      margin-bottom: 30px;
    }
    
    .project-title {
      font-size: 2rem;
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-dark);
    }
    
    .project-description {
      color: var(--text-gray);
      font-size: 1rem;
    }
    
    /* Estilos para tareas */
    .tasks-container {
      display: flex;
      gap: 25px;
      overflow-x: auto;
      padding-bottom: 30px;
      margin-top: 20px;
    }
    
    .task-column {
      min-width: 300px;
      width: 32%;
      border-radius: 12px;
      padding: 20px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.08);
    }
    
    /* Colores específicos para cada columna */
    .task-column.por-hacer {
      background-color: var(--orange-primary);
    }
    
    .task-column.en-progreso {
      background-color: var(--blue-primary);
    }
    
    .task-column.hecho {
      background-color: var(--purple);
    }
    
    .task-column-header {
      font-weight: 600;
      margin-bottom: 20px;
      display: flex;
      justify-content: space-between;
      align-items: center;
      color: white;
      font-size: 1.2rem;
    }
    
    .task-badge {
      background-color: rgba(255, 255, 255, 0.3);
      color: white;
      border-radius: 50px;
      padding: 3px 12px;
      font-size: 0.9rem;
    }
    
    .task-column-content {
      min-height: 100px;
      margin-bottom: 15px;
    }
    
    .task-card {
      background-color: white;
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 12px;
      box-shadow: 0 2px 4px rgba(0,0,0,0.05);
      cursor: pointer;
      transition: transform 0.2s, box-shadow 0.2s;
    }
    
    .task-card:hover {
      transform: translateY(-3px);
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
    }
    
    .task-title {
      font-weight: 600;
      margin-bottom: 10px;
      color: var(--text-dark);
      font-size: 1rem;
    }
    
    .task-meta {
      display: flex;
      justify-content: space-between;
      font-size: 0.8rem;
      color: var(--text-gray);
    }
    
    .priority-indicator {
      width: 50px;
      height: 5px;
      border-radius: 3px;
      margin-bottom: 10px;
    }
    
    .priority-high {
      background-color: #dc3545;
    }
    
    .priority-medium {
      background-color: var(--orange-dark);
    }
    
    .priority-low {
      background-color: #28a745;
    }
    
    .add-task-btn {
      display: flex;
      align-items: center;
      justify-content: center;
      background-color: rgba(255, 255, 255, 0.3);
      color: white;
      border: none;
      border-radius: 8px;
      padding: 12px;
      width: 100%;
      cursor: pointer;
      transition: background-color 0.2s;
      font-weight: 500;
    }
    
    .add-task-btn:hover {
      background-color: rgba(255, 255, 255, 0.4);
    }
    
    .add-task-btn i {
      margin-right: 8px;
    }
    
    .btn-primary, .btn-invite {
      background-color: var(--bluepurple-light);
      border-color: var(--bluepurple-light);
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
    }
    
    .btn-primary:hover, .btn-invite:hover {
      background-color: var(--purple);
      border-color: var(--purple);
    }
    
    .btn-outline-primary {
      color: var(--bluepurple-light);
      border-color: var(--bluepurple-light);
      padding: 10px 20px;
      font-weight: 500;
      border-radius: 8px;
    }
    
    .btn-outline-primary:hover {
      background-color: var(--bluepurple-light);
      color: white;
      border-color: var(--bluepurple-light);
    }
    
    .btn-outline-secondary {
      color: var(--text-gray);
      border-color: #ced4da;
      padding: 8px 16px;
      border-radius: 8px;
      transition: all 0.3s;
    }
    
    .btn-outline-secondary:hover {
      background-color: #f8f9fa;
      color: var(--text-dark);
    }
    
    /* Estilos para modal de tareas */
    .task-detail-modal .modal-content {
      border-radius: 12px;
      border: none;
      box-shadow: 0 10px 30px rgba(0,0,0,0.1);
    }
    
    .task-detail-modal .modal-header {
      border-bottom: 1px solid rgba(0,0,0,0.05);
      padding: 20px 25px;
    }
    
    .task-detail-modal .modal-body {
      padding: 25px;
    }
    
    .task-detail-modal .form-label {
      font-weight: 500;
      color: var(--text-dark);
    }
    
    .task-detail-modal .modal-footer {
      border-top: 1px solid rgba(0,0,0,0.05);
      padding: 15px 25px;
    }
    
    .task-metadata {
      background-color: var(--light-gray);
      border-radius: 8px;
      padding: 15px;
      margin-bottom: 20px;
    }
    
    .task-metadata-item {
      display: flex;
      margin-bottom: 10px;
    }
    
    .task-metadata-label {
      width: 120px;
      font-weight: 500;
      color: var(--text-dark);
    }
    
    /* Estilos para elementos dropdown */
    .dropdown-menu {
      border: none;
      box-shadow: 0 5px 15px rgba(0,0,0,0.1);
      border-radius: 8px;
      padding: 10px 0;
    }
    
    .dropdown-item {
      padding: 8px 20px;
      transition: background-color 0.2s;
    }
    
    .dropdown-item:hover {
      background-color: var(--light-gray);
    }
    
    .create-project-card {
      max-width: 600px;
      margin: 80px auto;
      text-align: center;
      padding: 40px;
      border-radius: 16px;
      background-color: white;
      box-shadow: 0 10px 30px rgba(0, 0, 0, 0.05);
    }
    
    .create-project-card h2 {
      font-size: 2rem;
      margin-bottom: 20px;
      color: var(--text-dark);
      font-weight: 600;
    }
    
    .create-project-card p {
      color: var(--text-gray);
      margin-bottom: 30px;
      font-size: 1.1rem;
    }
    
    .btn-create-project {
      background-color: var(--orange-primary);
      color: var(--text-dark);
      border: none;
      padding: 12px 30px;
      border-radius: 8px;
      font-weight: 500;
      font-size: 1.1rem;
      transition: all 0.3s;
      box-shadow: 0 4px 10px rgba(249, 187, 87, 0.2);
    }
    
    .btn-create-project:hover {
      background-color: var(--orange-dark);
      color: var(--text-dark);
      transform: translateY(-3px);
      box-shadow: 0 6px 15px rgba(249, 187, 87, 0.3);
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
    
    /* Estilos para los mensajes/comentarios */
    .mensaje-item {
      position: relative;
    }
    
    .mensaje-item:hover .mensaje-actions {
      opacity: 1;
    }
    
    .mensaje-actions {
      position: absolute;
      top: 10px;
      right: 10px;
      opacity: 0;
      transition: opacity 0.2s ease;
    }
    
    .mensaje-editing {
      background-color: rgba(0, 123, 255, 0.05);
      border-radius: 8px;
    }
    
    .edit-message-textarea {
      min-height: 80px;
      margin-bottom: 10px;
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
    }
    
    @media (max-width: 768px) {
      .tasks-container {
        flex-direction: column;
        gap: 20px;
      }
      
      .task-column {
        width: 100%;
        min-width: auto;
      }
      
      .main-header {
        flex-direction: column;
        align-items: flex-start;
      }
      
      .welcome-text {
        margin-bottom: 15px;
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
      
      .project-title {
        font-size: 1.5rem;
      }
      
      .btn-invite {
        padding: 8px 15px;
        font-size: 0.9rem;
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
        <a href="dashboard.php" class="active">
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
      <li>
        <a href="estadisticas.php">
          <i class="fas fa-chart-bar"></i>
          <span>Estadísticas</span>
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
      <div class="welcome-text">
        Bienvenido, <?= htmlspecialchars($usuario['nombre']) ?>!
      </div>
      
      <div class="d-flex align-items-center">
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="proyectosDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-folder me-1"></i> Proyectos
          </button>
          <ul class="dropdown-menu" aria-labelledby="proyectosDropdown">
            <?php if (!empty($proyectosData)): ?>
              <?php foreach ($proyectosData as $proyecto): ?>
                <li><a class="dropdown-item" href="dashboard.php?proyecto_id=<?= $proyecto['_id'] ?>"><?= htmlspecialchars($proyecto['nombre']) ?></a></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><a class="dropdown-item disabled">No tienes proyectos</a></li>
            <?php endif; ?>
            <li><hr class="dropdown-divider"></li>
            <li><a class="dropdown-item" href="#" id="createProjectBtn"><i class="fas fa-plus me-1"></i> Crear Proyecto</a></li>
            <li><a class="dropdown-item" href="proyectos.php"><i class="fas fa-cog me-1"></i> Administrar Proyectos</a></li>
          </ul>
        </div>
        <div class="dropdown me-3">
          <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="recentesDropdown" data-bs-toggle="dropdown" aria-expanded="false">
            <i class="fas fa-history me-1"></i> Recientes
          </button>
          <ul class="dropdown-menu" aria-labelledby="recentesDropdown">
            <?php if (!empty($proyectosData)): ?>
              <?php foreach (array_slice($proyectosData, 0, 5) as $proyecto): ?>
                <li><a class="dropdown-item" href="dashboard.php?proyecto_id=<?= $proyecto['_id'] ?>"><?= htmlspecialchars($proyecto['nombre']) ?></a></li>
              <?php endforeach; ?>
            <?php else: ?>
              <li><a class="dropdown-item disabled">No hay proyectos recientes</a></li>
            <?php endif; ?>
          </ul>
        </div>
      </div>
    </div>

    <!-- Contenido Principal -->
    <div class="main-content">
      <?php if ($proyecto_seleccionado): ?>
      <!-- Vista de Proyecto -->
      <div class="project-header">
        <div class="d-flex justify-content-between align-items-center mb-2">
          <h1 class="project-title"><?= htmlspecialchars($proyecto_seleccionado['nombre']) ?></h1>
          <div>
            <a href="proyectos.php" class="btn btn-outline-secondary me-2">
              <i class="fas fa-cog me-1"></i> Administrar Proyectos
            </a>
            <button class="btn btn-invite" id="invite-members-btn">
              <i class="fas fa-user-plus me-1"></i> Invitar Miembros
            </button>
          </div>
        </div>
        <p class="project-description"><?= htmlspecialchars($proyecto_seleccionado['descripcion']) ?></p>
      </div>
      
      <!-- Tablero de Tareas -->
      <div class="tasks-container">
        <!-- Columna Por hacer -->
        <div class="task-column por-hacer">
          <div class="task-column-header">
            <span>Por hacer</span>
            <span class="task-badge"><?= count($tareasPorEstado['Por hacer']) ?></span>
          </div>
          
          <div class="task-column-content" data-estado="Por hacer">
            <?php foreach ($tareasPorEstado['Por hacer'] as $tarea): ?>
              <div class="task-card" data-task-id="<?= $tarea['_id'] ?>">
                <?php 
                $priorityClass = '';
                if ($tarea['prioridad'] === 'Alta') $priorityClass = 'priority-high';
                else if ($tarea['prioridad'] === 'Media') $priorityClass = 'priority-medium';
                else if ($tarea['prioridad'] === 'Baja') $priorityClass = 'priority-low';
                ?>
                <div class="priority-indicator <?= $priorityClass ?>"></div>
                <div class="task-title"><?= htmlspecialchars($tarea['titulo']) ?></div>
                <div class="task-meta">
                  <div>
                    <?php if (!empty($tarea['fecha_limite'])): ?>
                      <i class="far fa-calendar-alt me-1"></i> <?= date('d M', strtotime($tarea['fecha_limite'])) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <?php if (!empty($tarea['usuario_asignado'])): ?>
                      <i class="far fa-user me-1"></i> <?= isset($tarea['usuario_asignado']['nombre']) ? htmlspecialchars(substr($tarea['usuario_asignado']['nombre'], 0, 10)) : 'Asignado' ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <button class="add-task-btn" data-estado="Por hacer">
            <i class="fas fa-plus"></i> Añadir tarea
          </button>
        </div>

        <!-- Columna En progreso -->
        <div class="task-column en-progreso">
          <div class="task-column-header">
            <span>En progreso</span>
            <span class="task-badge"><?= count($tareasPorEstado['En progreso']) ?></span>
          </div>
          
          <div class="task-column-content" data-estado="En progreso">
            <?php foreach ($tareasPorEstado['En progreso'] as $tarea): ?>
              <div class="task-card" data-task-id="<?= $tarea['_id'] ?>">
                <?php 
                $priorityClass = '';
                if ($tarea['prioridad'] === 'Alta') $priorityClass = 'priority-high';
                else if ($tarea['prioridad'] === 'Media') $priorityClass = 'priority-medium';
                else if ($tarea['prioridad'] === 'Baja') $priorityClass = 'priority-low';
                ?>
                <div class="priority-indicator <?= $priorityClass ?>"></div>
                <div class="task-title"><?= htmlspecialchars($tarea['titulo']) ?></div>
                <div class="task-meta">
                  <div>
                    <?php if (!empty($tarea['fecha_limite'])): ?>
                      <i class="far fa-calendar-alt me-1"></i> <?= date('d M', strtotime($tarea['fecha_limite'])) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <?php if (!empty($tarea['usuario_asignado'])): ?>
                      <i class="far fa-user me-1"></i> <?= isset($tarea['usuario_asignado']['nombre']) ? htmlspecialchars(substr($tarea['usuario_asignado']['nombre'], 0, 10)) : 'Asignado' ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <button class="add-task-btn" data-estado="En progreso">
            <i class="fas fa-plus"></i> Añadir tarea
          </button>
        </div>

        <!-- Columna Hecho -->
        <div class="task-column hecho">
          <div class="task-column-header">
            <span>Hecho</span>
            <span class="task-badge"><?= count($tareasPorEstado['Hecho']) ?></span>
          </div>
          
          <div class="task-column-content" data-estado="Hecho">
            <?php foreach ($tareasPorEstado['Hecho'] as $tarea): ?>
              <div class="task-card" data-task-id="<?= $tarea['_id'] ?>">
                <?php 
                $priorityClass = '';
                if ($tarea['prioridad'] === 'Alta') $priorityClass = 'priority-high';
                else if ($tarea['prioridad'] === 'Media') $priorityClass = 'priority-medium';
                else if ($tarea['prioridad'] === 'Baja') $priorityClass = 'priority-low';
                ?>
                <div class="priority-indicator <?= $priorityClass ?>"></div>
                <div class="task-title"><?= htmlspecialchars($tarea['titulo']) ?></div>
                <div class="task-meta">
                  <div>
                    <?php if (!empty($tarea['fecha_limite'])): ?>
                      <i class="far fa-calendar-alt me-1"></i> <?= date('d M', strtotime($tarea['fecha_limite'])) ?>
                    <?php endif; ?>
                  </div>
                  <div>
                    <?php if (!empty($tarea['usuario_asignado'])): ?>
                      <i class="far fa-user me-1"></i> <?= isset($tarea['usuario_asignado']['nombre']) ? htmlspecialchars(substr($tarea['usuario_asignado']['nombre'], 0, 10)) : 'Asignado' ?>
                    <?php endif; ?>
                  </div>
                </div>
              </div>
            <?php endforeach; ?>
          </div>
          
          <button class="add-task-btn" data-estado="Hecho">
            <i class="fas fa-plus"></i> Añadir tarea
          </button>
        </div>
      </div>
      
      <?php else: ?>
      <!-- Cuadro para crear proyecto -->
      <div class="create-project-card">
        <h2>Organiza tus proyectos como un profesional</h2>
        <p>NeuroTask te ayuda a gestionar proyectos, organizar tareas y colaborar con tu equipo, todo en un solo lugar.</p>
        <div class="d-flex justify-content-center gap-3">
          <button class="btn btn-create-project" id="createProjectBtnCenter">
            <i class="fas fa-plus me-1"></i> Crear Proyecto
          </button>
          <a href="proyectos.php" class="btn btn-outline-secondary">
            <i class="fas fa-folder-open me-1"></i> Ver Mis Proyectos
          </a>
        </div>
      </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Modal para Crear Proyecto -->
  <div class="modal fade" id="projectCreateModal" tabindex="-1" aria-labelledby="projectCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="projectCreateModalLabel">Crear Proyecto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <!-- Quitar el atributo action para evitar el envío tradicional del formulario -->
          <form id="projectCreateForm" method="POST">
            <div class="mb-3">
              <label for="nombre" class="form-label">Nombre del Proyecto:</label>
              <input type="text" name="nombre" id="nombre" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="descripcion" class="form-label">Descripción:</label>
              <textarea name="descripcion" id="descripcion" class="form-control" required></textarea>
            </div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Crear Proyecto</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Crear Tarea -->
  <div class="modal fade" id="taskCreateModal" tabindex="-1" aria-labelledby="taskCreateModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="taskCreateModalLabel">Crear Tarea</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <form id="taskCreateForm" method="POST">
            <input type="hidden" name="proyecto_id" id="proyecto_id" value="<?= isset($proyecto_seleccionado['_id']) ? $proyecto_seleccionado['_id'] : '' ?>">
            <input type="hidden" name="estado" id="estado" value="">
            <div class="mb-3">
              <label for="titulo" class="form-label">Título:</label>
              <input type="text" name="titulo" id="titulo" class="form-control" required>
            </div>
            <div class="mb-3">
              <label for="descripcion_task" class="form-label">Descripción:</label>
              <textarea name="descripcion" id="descripcion_task" class="form-control"></textarea>
            </div>
            <div class="mb-3">
              <label for="prioridad" class="form-label">Prioridad:</label>
              <select name="prioridad" id="prioridad" class="form-select" required>
                <option value="Alta">Alta</option>
                <option value="Media" selected>Media</option>
                <option value="Baja">Baja</option>
              </select>
            </div>
            <div class="mb-3">
              <label for="fecha_limite" class="form-label">Fecha límite (opcional):</label>
              <input type="date" name="fecha_limite" id="fecha_limite" class="form-control">
            </div>
            <!-- Campo para asignar usuario -->
            <div class="mb-3">
              <label for="usuario_asignado" class="form-label">Asignar a (opcional):</label>
              <select name="usuario_asignado" id="usuario_asignado" class="form-select">
                <option value="">-- Sin asignar --</option>
                <?php foreach ($usersData as $user): ?>
                  <option value="<?= $user['_id'] ?>"><?= htmlspecialchars($user['nombre']) ?></option>
                <?php endforeach; ?>
              </select>
            </div>
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Crear Tarea</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Detalles/Edición de Tarea -->
  <div class="modal fade task-detail-modal" id="taskDetailModal" tabindex="-1" aria-labelledby="taskDetailModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-lg">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="taskDetailModalLabel">Detalles de la Tarea</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body" id="taskDetailContent">
          <!-- El contenido se cargará dinámicamente -->
        </div>
      </div>
    </div>
  </div>

  <!-- Modal para Invitar Miembros -->
  <div class="modal fade" id="inviteMembersModal" tabindex="-1" aria-labelledby="inviteMembersModalLabel" aria-hidden="true">
    <div class="modal-dialog">
      <div class="modal-content">
        <div class="modal-header">
          <h5 class="modal-title" id="inviteMembersModalLabel">Invitar Miembros al Proyecto</h5>
          <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
        </div>
        <div class="modal-body">
          <div id="modalAlert" class="alert d-none"></div>

          <form id="inviteMembersForm">
            <input type="hidden" id="proyectoNombre" name="proyectoNombre" value="<?= htmlspecialchars($proyecto_seleccionado['nombre'] ?? '') ?>">
            
            <div class="mb-3">
              <label for="emailMiembro" class="form-label">Correo Electrónico</label>
              <input type="email" class="form-control" id="emailMiembro" name="email" required 
                     placeholder="Ingrese el correo electrónico del usuario">
              <div class="form-text">Ingrese el correo electrónico del usuario que desea invitar al proyecto.</div>
            </div>
            
            <div class="d-flex justify-content-end">
              <button type="button" class="btn btn-secondary me-2" data-bs-dismiss="modal">Cancelar</button>
              <button type="submit" class="btn btn-primary">Invitar</button>
            </div>
          </form>
        </div>
      </div>
    </div>
  </div>

  <!-- Scripts de Bootstrap -->
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
  
  <!-- Script tareas principal -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
  // Modales
  const projectCreateModal = new bootstrap.Modal(document.getElementById('projectCreateModal'));
  const taskCreateModal = new bootstrap.Modal(document.getElementById('taskCreateModal'));
  const taskDetailModal = new bootstrap.Modal(document.getElementById('taskDetailModal'));
  
  // Obtener el ID del usuario desde la sesión PHP
  const currentUserId = '<?= $_SESSION["usuario"]["_id"] ?>';
  
  // Botones para crear proyecto
  const createProjectBtn = document.getElementById('createProjectBtn');
  const createProjectBtnCenter = document.getElementById('createProjectBtnCenter');
  const projectCreateForm = document.getElementById('projectCreateForm');
  
  // Botones para crear tarea en cada columna
  const addTaskButtons = document.querySelectorAll('.add-task-btn');
  
  // Tarjetas de tareas existentes
  const taskCards = document.querySelectorAll('.task-card');
  
  // Verificar si hay un proyecto seleccionado
  const proyectoId = new URLSearchParams(window.location.search).get('proyecto_id');
  if (proyectoId) {
    // Cargar las tareas al iniciar
    cargarTareas(proyectoId);
    
    // Re-cargar tareas automáticamente cada 30 segundos para mantener todo sincronizado
    setInterval(() => {
      cargarTareas(proyectoId);
    }, 30000);
  }
  
  // Función para cargar tareas mediante AJAX
  function cargarTareas(proyectoId) {
    fetch(`ajax/obtener_tareas_proyecto.php?proyecto_id=${proyectoId}`)
      .then(response => response.json())
      .then(data => {
        if (data.ok && data.tareas && data.tareas.length > 0) {
          // Limpiar las columnas
          document.querySelectorAll('.task-column-content').forEach(column => {
            column.innerHTML = '';
          });

          // Procesar y agrupar tareas por estado
          const tareasPorEstado = {
            'Por hacer': [],
            'En progreso': [],
            'Hecho': []
          };

          data.tareas.forEach(tarea => {
            if (tareasPorEstado.hasOwnProperty(tarea.estado)) {
              tareasPorEstado[tarea.estado].push(tarea);
            }
          });

          // Añadir tareas a las columnas
          for (const estado in tareasPorEstado) {
            const tareas = tareasPorEstado[estado];
            const columna = document.querySelector(`.task-column-content[data-estado="${estado}"]`);
            
            if (columna) {
              tareas.forEach(tarea => {
                addTaskToInterface(tarea, columna);
              });
              
              // Actualizar contador
              const badge = columna.closest('.task-column').querySelector('.task-badge');
              if (badge) {
                badge.textContent = tareas.length;
              }
            }
          }
        } else {
          console.error('No se pudieron cargar las tareas o no hay tareas en este proyecto');
        }
      })
      .catch(error => {
        console.error('Error al cargar las tareas:', error);
        showNotification('Error al cargar las tareas. Por favor, recarga la página.', 'error');
      });
  }
  
  // Mostrar modal de crear proyecto
  if (createProjectBtn) {
    createProjectBtn.addEventListener('click', function(e) {
      e.preventDefault();
      projectCreateModal.show();
    });
  }
  
  if (createProjectBtnCenter) {
    createProjectBtnCenter.addEventListener('click', function(e) {
      e.preventDefault();
      projectCreateModal.show();
    });
  }
  
  // Manejar la creación de proyectos con AJAX en lugar de envío tradicional
  if (projectCreateForm) {
    projectCreateForm.addEventListener('submit', function(e) {
      e.preventDefault();
      
      // Validar el formulario
      if (!this.checkValidity()) {
        this.reportValidity();
        return;
      }
      
      // Preparar los datos del formulario
      const formData = new FormData(this);
      
      // Mostrar indicador de carga (opcional)
      const submitBtn = this.querySelector('button[type="submit"]');
      const originalBtnText = submitBtn.innerHTML;
      submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
      submitBtn.disabled = true;
      
      // Enviar solicitud AJAX
      fetch('ajax/crear_proyecto.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.ok) {
          // Proyecto creado con éxito
          projectCreateModal.hide();
          
          // Mostrar notificación de éxito
          showNotification('Proyecto creado con éxito', 'success');
          
          // Redirigir a la página de proyectos después de un breve retraso
          setTimeout(() => {
            window.location.href = 'proyectos.php';
          }, 1000);
        } else {
          // Error al crear el proyecto
          submitBtn.innerHTML = originalBtnText;
          submitBtn.disabled = false;
          showNotification('Error: ' + (data.error || 'Ha ocurrido un error'), 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        submitBtn.innerHTML = originalBtnText;
        submitBtn.disabled = false;
        showNotification('Error al procesar la solicitud', 'error');
      });
    });
  }
  
  // Mostrar modal de crear tarea con estado específico desde los botones de cada columna
  addTaskButtons.forEach(button => {
    button.addEventListener('click', function(e) {
      e.preventDefault();
      const estado = this.dataset.estado;
      document.getElementById('estado').value = estado;
      taskCreateModal.show();
    });
  });

  // Manejar el formulario de creación de tarea con AJAX
  const taskCreateForm = document.getElementById('taskCreateForm');
  if (taskCreateForm) {
    taskCreateForm.addEventListener('submit', function(e) {
      e.preventDefault(); // Prevenir el envío tradicional del formulario
      
      // Recopilar los datos del formulario
      const formData = new FormData(this);
      
      // AÑADIR ESTA LÍNEA - Agregar el ID del usuario al FormData
      formData.append('userId', currentUserId);
      
      
      // Enviar la solicitud usando fetch
      fetch('ajax/crear_tarea.php', {
        method: 'POST',
        body: formData
      })
      .then(response => response.json())
      .then(data => {
        if (data.ok && data.tarea) {
          // Cerrar el modal
          taskCreateModal.hide();
          
          // Añadir la tarea a la interfaz
          addTaskToInterface(data.tarea);
          
          // Limpiar el formulario
          taskCreateForm.reset();
          
          // Restaurar el estado para la próxima vez
          document.getElementById('estado').value = '';
          
          // Mostrar notificación de éxito
          showNotification('Tarea creada correctamente', 'success');
        } else {
          // Mostrar notificación de error
          showNotification('Error: ' + (data.error || 'No se pudo crear la tarea'), 'error');
        }
      })
      .catch(error => {
        console.error('Error:', error);
        showNotification('Error al crear la tarea', 'error');
      });
    });
  }
      
      // Función para añadir la tarea a la interfaz sin necesidad de recargar
      function addTaskToInterface(tarea, columna = null) {
        // Si no se proporciona columna, buscarla por el estado de la tarea
        if (!columna) {
          columna = document.querySelector(`.task-column-content[data-estado="${tarea.estado}"]`);
          if (!columna) return;
        }
        
        // Verificar si la tarea ya existe en la interfaz para evitar duplicados
        const tareaExistente = document.querySelector(`.task-card[data-task-id="${tarea._id}"]`);
        if (tareaExistente) {
          // Si la tarea existe pero está en una columna diferente, moverla
          if (tareaExistente.closest('.task-column-content') !== columna) {
            columna.appendChild(tareaExistente);
            updateColumnCounter(tarea.estado);
          }
          return;
        }
        
        // Crear el elemento de la tarjeta
        const taskCard = document.createElement('div');
        taskCard.className = 'task-card';
        taskCard.dataset.taskId = tarea._id;
        
        // Determinar la clase de prioridad
        let priorityClass = '';
        if (tarea.prioridad === 'Alta') priorityClass = 'priority-high';
        else if (tarea.prioridad === 'Media') priorityClass = 'priority-medium';
        else if (tarea.prioridad === 'Baja') priorityClass = 'priority-low';
        
        // Obtener nombre del usuario asignado si existe
        let nombreUsuarioAsignado = '';
        if (tarea.usuario_asignado && tarea.usuario_asignado.nombre) {
          nombreUsuarioAsignado = tarea.usuario_asignado.nombre.substring(0, 10);
        }
        
        // Construir el HTML de la tarjeta
        taskCard.innerHTML = `
          <div class="priority-indicator ${priorityClass}"></div>
          <div class="task-title">${escaparHTML(tarea.titulo)}</div>
          <div class="task-meta">
            <div>
              ${tarea.fecha_limite ? `<i class="far fa-calendar-alt me-1"></i> ${formatearFecha(tarea.fecha_limite)}` : ''}
            </div>
            <div>
              ${tarea.usuario_asignado ? `<i class="far fa-user me-1"></i> ${escaparHTML(nombreUsuarioAsignado)}` : ''}
            </div>
          </div>
        `;
        
        // Añadir evento de clic para ver detalles en modal
        taskCard.addEventListener('click', function() {
          loadTaskDetails(tarea._id);
        });
        
        // Añadir la tarjeta a la columna
        columna.appendChild(taskCard);
        
        // Actualizar el contador de la columna
        updateColumnCounter(tarea.estado);
      }
      
      // Función para actualizar el contador de una columna
      function updateColumnCounter(estado) {
        const column = document.querySelector(`.task-column.${getColumnClass(estado)}`);
        if (!column) return;
        
        const columnContent = column.querySelector('.task-column-content');
        const taskCount = columnContent.querySelectorAll('.task-card').length;
        
        const badge = column.querySelector('.task-badge');
        if (badge) {
          badge.textContent = taskCount;
        }
      }
      
      // Función para obtener la clase CSS de la columna
      function getColumnClass(estado) {
        switch (estado) {
          case 'Por hacer': return 'por-hacer';
          case 'En progreso': return 'en-progreso';
          case 'Hecho': return 'hecho';
          default: return '';
        }
      }
      
      // Función para formatear la fecha
      function formatearFecha(fecha) {
        if (!fecha) return '';
        const d = new Date(fecha);
        const meses = ['Ene', 'Feb', 'Mar', 'Abr', 'May', 'Jun', 'Jul', 'Ago', 'Sep', 'Oct', 'Nov', 'Dic'];
        return `${d.getDate()} ${meses[d.getMonth()]}`;
      }
      
      // Función para escapar HTML
      function escaparHTML(texto) {
        if (!texto) return '';
        const div = document.createElement('div');
        div.textContent = texto;
        return div.innerHTML;
      }
      
      // Mostrar notificaciones
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

      
      // Configurar Sortable.js para el arrastre de tarjetas
      const columnContents = document.querySelectorAll('.task-column-content');
      columnContents.forEach(column => {
        new Sortable(column, {
          group: 'tasks',
          animation: 150,
          ghostClass: 'bg-light',
          onEnd: function(evt) {
            const taskId = evt.item.dataset.taskId;
            const newStatus = evt.to.dataset.estado;
            
            // Mostrar indicador de carga
            showNotification(`Actualizando estado...`, 'info');
            
            // Realizar una solicitud mínima que solo actualice el estado
            fetch('ajax/actualizar_tarea_drag.php', {
              method: 'POST',
              headers: {
                'Content-Type': 'application/json',
              },
              body: JSON.stringify({
                id: taskId,
                tarea: {
                  estado: newStatus
                }
              })
            })
            .then(response => {
              // Verificar si la respuesta es JSON válido
              const contentType = response.headers.get('content-type');
              if (contentType && contentType.includes('application/json')) {
                return response.json();
              } else {
                // Si no es JSON, obtener el texto y mostrar el error
                return response.text().then(text => {
                  throw new Error(`Respuesta no JSON: ${text.substring(0, 200)}`);
                });
              }
            })
            .then(data => {
              if (data.ok) {
                // Actualizar los contadores de las columnas
                const oldColumn = evt.from.closest('.task-column');
                const newColumn = evt.to.closest('.task-column');
                
                if (oldColumn !== newColumn) {
                  const oldBadge = oldColumn.querySelector('.task-badge');
                  const newBadge = newColumn.querySelector('.task-badge');
                  
                  if (oldBadge) {
                    oldBadge.textContent = parseInt(oldBadge.textContent) - 1;
                  }
                  
                  if (newBadge) {
                    newBadge.textContent = parseInt(newBadge.textContent) + 1;
                  }
                  
                  // Mostrar notificación de éxito
                  showNotification(`Tarea movida a "${newStatus}"`, 'success');
                }
              } else {
                console.error('Error al actualizar estado:', data.error);
                showNotification('Error al actualizar estado de la tarea: ' + (data.error || 'Error desconocido'), 'error');
                
                // Revertir el movimiento en la interfaz
                evt.from.appendChild(evt.item);
              }
            })
            .catch(error => {
              console.error('Error al actualizar estado:', error);
              showNotification('Error al actualizar estado: ' + error.message, 'error');
              
              // Revertir el movimiento en la interfaz
              evt.from.appendChild(evt.item);
            });
          }
        });
      });  

      // Cargar detalles de tarea en modal al hacer clic en tarjetas existentes
      document.querySelectorAll('.task-card').forEach(card => {
        card.addEventListener('click', function() {
          const taskId = this.dataset.taskId;
          loadTaskDetails(taskId);
        });
      });

      // Función para cargar detalles de tarea en el modal
      function loadTaskDetails(taskId) {
        // Mostrar el modal con un indicador de carga
        document.getElementById('taskDetailContent').innerHTML = `
          <div class="text-center p-5">
            <div class="spinner-border text-primary" role="status">
              <span class="visually-hidden">Cargando...</span>
            </div>
            <p class="mt-3">Cargando detalles de la tarea...</p>
          </div>
        `;
        
        taskDetailModal.show();
        
        // Cargar los detalles de la tarea mediante AJAX
        fetch(`ajax/obtener_tarea_modal.php?id=${taskId}`)
          .then(response => {
            if (!response.ok) {
              throw new Error('Error al cargar los datos de la tarea');
            }
            return response.text();
          })
          .then(html => {
            document.getElementById('taskDetailContent').innerHTML = html;
            
            // Inicializar los event listeners para el formulario de actualización
            initTaskDetailEventHandlers();
          })
          .catch(error => {
            console.error('Error:', error);
            document.getElementById('taskDetailContent').innerHTML = `
              <div class="alert alert-danger">
                Error al cargar los detalles de la tarea. Por favor, inténtalo de nuevo.
              </div>
            `;
          });
      }

      // Inicializar eventos para el formulario de detalles/edición de tarea
      function initTaskDetailEventHandlers() {
        const taskUpdateForm = document.getElementById('taskUpdateForm');
        if (taskUpdateForm) {
          taskUpdateForm.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(this);
            const taskId = this.dataset.taskId;
            
            // Enviar la solicitud de actualización
            fetch('ajax/actualizar_tarea.php', {
              method: 'POST',
              body: formData
            })
            .then(response => response.json())
            .then(data => {
              if (data.ok) {
                // Actualizar la interfaz sin recargar la página
                updateTaskCard(data.tarea);
                
                // Añadir mensaje de éxito
                showNotification('Tarea actualizada correctamente', 'success');
                
                // Cerrar el modal automáticamente después de unos segundos
                setTimeout(() => {
                  taskDetailModal.hide();
                }, 2000);
              } else {
                // Mostrar mensaje de error
                showNotification('Error: ' + (data.error || 'No se pudo actualizar la tarea'), 'error');
              }
            })
            .catch(error => {
              console.error('Error:', error);
              showNotification('Error al actualizar la tarea', 'error');
            });
          });
          
          // Manejar el botón de eliminar
          const deleteTaskBtn = document.getElementById('deleteTaskBtn');
          if (deleteTaskBtn) {
            deleteTaskBtn.addEventListener('click', function() {
              if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
                const taskId = this.dataset.taskId;
                
                fetch('ajax/eliminar_tarea.php', {
                  method: 'POST',
                  headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                  },
                  body: `id=${taskId}`
                })
                .then(response => response.json())
                .then(data => {
                  if (data.ok) {
                    // Cerrar el modal
                    taskDetailModal.hide();
                    
                    // Eliminar la tarjeta de la interfaz
                    const taskCard = document.querySelector(`.task-card[data-task-id="${taskId}"]`);
                    if (taskCard) {
                      // Actualizar el contador
                      const column = taskCard.closest('.task-column');
                      const badge = column.querySelector('.task-badge');
                      if (badge) {
                        badge.textContent = parseInt(badge.textContent) - 1;
                      }
                      
                      // Eliminar la tarjeta con animación
                      taskCard.style.opacity = '0';
                      setTimeout(() => {
                        taskCard.remove();
                      }, 300);
                      
                      // Mostrar notificación
                      showNotification('Tarea eliminada correctamente', 'success');
                    }
                  } else {
                    showNotification('Error: ' + (data.error || 'No se pudo eliminar la tarea'), 'error');
                  }
                })
                .catch(error => {
                  console.error('Error:', error);
                  showNotification('Error al eliminar la tarea', 'error');
                });
              }
            });
          }
          
          // Manejar el botón de añadir comentario
          const addCommentBtn = document.getElementById('addCommentBtn');
          if (addCommentBtn) {
            addCommentBtn.addEventListener('click', function() {
              const taskId = this.dataset.taskId;
              const comentario = document.getElementById('nuevo_comentario').value.trim();
              
              if (!comentario) {
                showNotification('Por favor, escribe un comentario', 'warning');
                return;
              }
              
              // Mostrar indicador de carga
              const originalBtnText = this.innerHTML;
              this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Enviando...';
              this.disabled = true;
              
              // Enviar comentario mediante AJAX - Versión corregida
              fetch('ajax/crear_mensaje.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  tarea_id: taskId,
                  contenido: comentario
                })
              })
              .then(response => {
                // Comprobar si la respuesta es válida antes de intentar parsearla
                if (!response.ok) {
                  throw new Error(`Error de servidor: ${response.status}`);
                }
                return response.json();
              })
              .then(data => {
                // Restaurar el botón
                this.innerHTML = originalBtnText;
                this.disabled = false;
                
                if (data.ok) {
                  // Limpiar el campo de comentario
                  document.getElementById('nuevo_comentario').value = '';
                  
                  // Agregar el mensaje al DOM sin recargar toda la tarea
                  addMessageToUI(data.mensaje);
                  
                  showNotification('Comentario añadido correctamente', 'success');
                } else {
                  console.error('Error en respuesta:', data);
                  showNotification('Error: ' + (data.error || 'No se pudo añadir el comentario'), 'error');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                // Restaurar el botón
                this.innerHTML = originalBtnText;
                this.disabled = false;
                showNotification('Error al añadir el comentario: ' + error.message, 'error');
              });
            });
          }
          
          // Inicializar los controladores para mensajes existentes
          initMessageHandlers();
        }
      }

      // Función para agregar un mensaje nuevo a la interfaz sin recargar
      function addMessageToUI(mensaje) {
  console.log('Añadiendo mensaje a la UI:', mensaje);
  
  // 1. Encontrar el contenedor específico #messages-list
  const messagesList = document.getElementById('messages-list');
  if (!messagesList) {
    console.error('Error: No se encontró el contenedor #messages-list');
    return;
  }
  
  // 2. Crear el elemento de mensaje con la estructura exacta que se usa en la página
  const messageElement = document.createElement('div');
  messageElement.className = 'mensaje-item p-3 mb-3 bg-light rounded message-card';
  messageElement.id = `mensaje-${mensaje._id}`;
  messageElement.dataset.messageId = mensaje._id;
  messageElement.dataset.usuarioId = mensaje.usuario_id;
  
  // 3. Formatear la fecha (usando la fecha del servidor o la actual)
  const fecha = mensaje.timestamp ? new Date(mensaje.timestamp) : new Date();
  const fechaFormateada = `Hace unos segundos`; // Simplificado para el mensaje recién añadido
  
  // 4. Obtener la inicial del nombre para el avatar
  const userInitial = mensaje.usuario && mensaje.usuario.nombre ? 
                      mensaje.usuario.nombre.charAt(0).toUpperCase() : 'U';
  
  // 5. Crear el HTML interno con la estructura exacta de la página
  messageElement.innerHTML = `
    <div class="message-header d-flex justify-content-between align-items-start">
      <div class="d-flex align-items-center">
        <div class="user-avatar">${userInitial}</div>
        <div class="message-info ms-2">
          <p class="message-user">${mensaje.usuario ? mensaje.usuario.nombre : 'Usuario'}</p>
          <small class="message-date">${fechaFormateada}</small>
        </div>
      </div>
    </div>
    <div class="message-content mt-2" id="content-${mensaje._id}">${mensaje.contenido.replace(/\n/g, '<br>')}</div>
  `;
  
  // 6. Añadir los botones de acción
  const messageActions = document.createElement('div');
  messageActions.className = 'mensaje-actions';
  messageActions.innerHTML = `
    <div class="btn-group">
      <button class="btn btn-sm btn-outline-secondary edit-message-btn" title="Editar mensaje">
        <i class="fas fa-edit"></i>
      </button>
      <button class="btn btn-sm btn-outline-danger delete-message-btn" title="Eliminar mensaje">
        <i class="fas fa-trash-alt"></i>
      </button>
    </div>
  `;
  messageElement.appendChild(messageActions);
  
  // 7. Aplicar estilo inicial para animación
  messageElement.style.opacity = '0';
  messageElement.style.transform = 'translateY(-20px)';
  
  // 8. Insertar al principio de la lista (antes del primer hijo)
  const firstMessage = messagesList.firstChild;
  if (firstMessage) {
    messagesList.insertBefore(messageElement, firstMessage);
  } else {
    messagesList.appendChild(messageElement);
  }
  
  // 9. Añadir eventos para los botones de editar y eliminar
  const editBtn = messageElement.querySelector('.edit-message-btn');
  if (editBtn) {
    editBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof startEditingMessage === 'function') {
        startEditingMessage(messageElement);
      }
    });
  }
  
  const deleteBtn = messageElement.querySelector('.delete-message-btn');
  if (deleteBtn) {
    deleteBtn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      if (typeof confirmDeleteMessage === 'function') {
        confirmDeleteMessage(messageElement);
      }
    });
  }
  
  // 10. Animar la entrada del mensaje
  setTimeout(() => {
    messageElement.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
    messageElement.style.opacity = '1';
    messageElement.style.transform = 'translateY(0)';
  }, 10);
  
  // 11. Actualizar contador si existe
  const commentCount = document.getElementById('comment-count');
  if (commentCount) {
    const currentCount = parseInt(commentCount.textContent || '0');
    commentCount.textContent = currentCount + 1;
  }
  
  console.log('Mensaje añadido con éxito al principio de la lista');
  return messageElement;
}

      // Inicializar controladores para mensajes
      function initMessageHandlers() {
        // Añadir botones de acción a los mensajes del usuario actual
        document.querySelectorAll('.mensaje-item').forEach(item => {
          const messageUserId = item.dataset.usuarioId;
          const currentUserId = '<?= $_SESSION['usuario']['_id'] ?>';
          
          // Solo añadir controles a los mensajes del usuario actual
          if (messageUserId === currentUserId) {
            // Verificar si ya tiene acciones para evitar duplicados
            if (!item.querySelector('.mensaje-actions')) {
              const messageActions = document.createElement('div');
              messageActions.className = 'mensaje-actions';
              messageActions.innerHTML = `
                <div class="btn-group">
                  <button class="btn btn-sm btn-outline-secondary edit-message-btn" title="Editar mensaje">
                    <i class="fas fa-edit"></i>
                  </button>
                  <button class="btn btn-sm btn-outline-danger delete-message-btn" title="Eliminar mensaje">
                    <i class="fas fa-trash-alt"></i>
                  </button>
                </div>
              `;
              
              item.appendChild(messageActions);
              
              // Añadir eventos a los botones
              item.querySelector('.edit-message-btn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                startEditingMessage(item);
              });
              
              item.querySelector('.delete-message-btn').addEventListener('click', function(e) {
                e.preventDefault();
                e.stopPropagation();
                confirmDeleteMessage(item);
              });
            }
          }
        });
      }

      // Reemplaza la función startEditingMessage con esta versión corregida:

function startEditingMessage(messageElement) {
console.log("Este es el messageElement", messageElement) 
document.querySelectorAll('.mensaje-editing').forEach(el => {
    if (el !== messageElement) {
      const contentEl = el.querySelector('.mensaje-contenido');
      if (contentEl && el.dataset.originalContent) {
        contentEl.innerHTML = el.dataset.originalContent;
      }
      el.classList.remove('mensaje-editing');
    }
  });
  
  const messageId = messageElement.dataset.messageId;
  // Buscar el elemento contentElement dentro de messageElement
  const contentElement = messageElement.querySelector('.message-content');
  
  // Verificar que contentElement existe antes de continuar
  if (!contentElement) {
    console.error('Error: No se pudo encontrar el elemento de contenido dentro del mensaje', messageElement);
    alert('Error al intentar editar el mensaje. Por favor, recarga la página e intenta de nuevo.');
    return;
  }
  
  // Obtener el contenido actual (eliminando los <br> HTML)
  const currentContent = contentElement.innerHTML.replace(/<br\s*\/?>/gi, '\n');
  const plainContent = document.createElement('div');
  plainContent.innerHTML = currentContent;
  const cleanContent = plainContent.textContent;
  
  // Guardar el contenido original
  messageElement.dataset.originalContent = contentElement.innerHTML;
  messageElement.classList.add('mensaje-editing');
  
  // Reemplazar contenido con textarea
  contentElement.innerHTML = `
    <div class="edit-message-form">
      <textarea class="form-control edit-message-textarea">${cleanContent}</textarea>
      <div class="mt-2 d-flex justify-content-end">
        <button class="btn btn-sm btn-outline-secondary me-2 cancel-edit-btn">Cancelar</button>
        <button class="btn btn-sm btn-primary save-edit-btn">Guardar</button>
      </div>
    </div>
  `;
  
  // Enfocar el textarea
  contentElement.querySelector('textarea').focus();
  
  // Añadir eventos para los botones
  contentElement.querySelector('.cancel-edit-btn').addEventListener('click', function() {
    contentElement.innerHTML = messageElement.dataset.originalContent;
    messageElement.classList.remove('mensaje-editing');
  });
  
  contentElement.querySelector('.save-edit-btn').addEventListener('click', function() {
    const newContent = contentElement.querySelector('textarea').value.trim();
    if (newContent === '') {
      showNotification('El mensaje no puede estar vacío', 'warning');
      return;
    }
    
    saveEditedMessage(messageId, newContent, messageElement);
  });
}

      // Función para guardar un mensaje editado
      function saveEditedMessage(messageId, newContent, messageElement) {
        // Mostrar estado de carga
        console.log('datos de mensaje', messageId, newContent)
        const saveButton = messageElement.querySelector('.save-edit-btn');
        saveButton.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        saveButton.disabled = true;
        // Enviar solicitud de actualización
        fetch('ajax/actualizar_mensaje.php', {
          method: 'PUT',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id: messageId,
            contenido: newContent
          })
        })
        .then(data => {
          if (data.ok) {
            // Actualizar la visualización del mensaje
            messageElement.classList.remove('mensaje-editing');
            const contentElement = messageElement.querySelector('.message-content');
            contentElement.innerHTML = `<p>${newContent.replace(/\n/g, '<br>')}</p>`;
            showNotification('Mensaje actualizado correctamente', 'success');
          } else {
            // Restaurar contenido original y mostrar error
            const contentElement = messageElement.querySelector('.message-content');
            contentElement.innerHTML = messageElement.dataset.originalContent;
            messageElement.classList.remove('mensaje-editing');
            showNotification('Error: ' + (data.error || 'No se pudo actualizar el mensaje'), 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          const contentElement = messageElement.querySelector('.message-content');
          contentElement.innerHTML = messageElement.dataset.originalContent;
          messageElement.classList.remove('mensaje-editing');
          showNotification('Error al actualizar el mensaje', 'error');
        });
      }

      // Función para confirmar y eliminar un mensaje
      function confirmDeleteMessage(messageElement) {
        const messageId = messageElement.dataset.messageId;
        
        if (confirm('¿Estás seguro de que quieres eliminar este mensaje?')) {
          // Mostrar estado de carga
          messageElement.style.opacity = '0.5';
          
          // Enviar solicitud de eliminación
          fetch('ajax/eliminar_mensaje.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              id: messageId
            })
          })
          .then(response => response.json())
          .then(data => {
            if (data.ok) {
              // Eliminar el mensaje de la interfaz con animación
              messageElement.style.height = messageElement.offsetHeight + 'px';
              messageElement.style.overflow = 'hidden';
              messageElement.style.transition = 'all 0.3s ease';
              
              setTimeout(() => {
                messageElement.style.height = '0';
                messageElement.style.padding = '0';
                messageElement.style.margin = '0';
                setTimeout(() => {
                  messageElement.remove();
                  
                  // Si no hay más mensajes, mostrar mensaje "No hay comentarios"
                  const mensajesContainer = document.querySelector('.mensajes-container');
                  const mensajes = mensajesContainer.querySelectorAll('.mensaje-item');
                  if (mensajes.length === 0) {
                    const noCommentsMsg = document.createElement('p');
                    noCommentsMsg.className = 'text-muted';
                    noCommentsMsg.textContent = 'No hay comentarios aún para esta tarea.';
                    mensajesContainer.insertBefore(noCommentsMsg, mensajesContainer.querySelector('.nuevo-comentario'));
                  }
                  
                  showNotification('Mensaje eliminado correctamente', 'success');
                }, 300);
              }, 10);
            } else {
              // Restaurar opacidad y mostrar error
              messageElement.style.opacity = '1';
              showNotification('Error: ' + (data.error || 'No se pudo eliminar el mensaje'), 'error');
            }
          })
          .catch(error => {
            console.error('Error:', error);
            messageElement.style.opacity = '1';
            showNotification('Error al eliminar el mensaje', 'error');
          });
        }
      }
      
      // Función para actualizar una tarjeta de tarea con datos nuevos
      function updateTaskCard(tarea) {
        const taskCard = document.querySelector(`.task-card[data-task-id="${tarea._id}"]`);
        
        if (taskCard) {
          // Determinar la clase de prioridad
          let priorityClass = '';
          if (tarea.prioridad === 'Alta') priorityClass = 'priority-high';
          else if (tarea.prioridad === 'Media') priorityClass = 'priority-medium';
          else if (tarea.prioridad === 'Baja') priorityClass = 'priority-low';
          
          // Obtener nombre del usuario asignado si existe
          let nombreUsuarioAsignado = '';
          if (tarea.usuario_asignado && tarea.usuario_asignado.nombre) {
            nombreUsuarioAsignado = tarea.usuario_asignado.nombre.substring(0, 10);
          }
          
          // Actualizar el contenido de la tarjeta
          taskCard.innerHTML = `
            <div class="priority-indicator ${priorityClass}"></div>
            <div class="task-title">${escaparHTML(tarea.titulo)}</div>
            <div class="task-meta">
              <div>
                ${tarea.fecha_limite ? `<i class="far fa-calendar-alt me-1"></i> ${formatearFecha(tarea.fecha_limite)}` : ''}
              </div>
              <div>
                ${tarea.usuario_asignado ? `<i class="far fa-user me-1"></i> ${escaparHTML(nombreUsuarioAsignado)}` : ''}
              </div>
            </div>
          `;
          
          // Verificar si cambió el estado
          const currentColumn = taskCard.closest('.task-column-content');
          const currentState = currentColumn.dataset.estado;
          
          if (currentState !== tarea.estado) {
            // Mover la tarjeta a la columna correcta
            const targetColumn = document.querySelector(`.task-column-content[data-estado="${tarea.estado}"]`);
            
            if (targetColumn) {
              // Actualizar contadores
              const oldColumn = taskCard.closest('.task-column');
              const newColumn = targetColumn.closest('.task-column');
              
              const oldBadge = oldColumn.querySelector('.task-badge');
              const newBadge = newColumn.querySelector('.task-badge');
              
              if (oldBadge) {
                oldBadge.textContent = parseInt(oldBadge.textContent) - 1;
              }
              
              if (newBadge) {
                newBadge.textContent = parseInt(newBadge.textContent) + 1;
              }
              
              // Mover la tarjeta
              targetColumn.prepend(taskCard);
            }
          }
          
          // Reactivar el listener de clic
          taskCard.addEventListener('click', function() {
            loadTaskDetails(tarea._id);
          });
        }
      }
    });
  </script>

  <!-- Script para manejar invitaciones de miembros -->
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      // Obtener referencias a elementos
      const inviteMembersBtn = document.getElementById('invite-members-btn');
      const inviteMembersForm = document.getElementById('inviteMembersForm');
      const inviteMembersModal = new bootstrap.Modal(document.getElementById('inviteMembersModal'));
      const modalAlert = document.getElementById('modalAlert');
      
      // Función para mostrar alerta en el modal
      function showModalAlert(message, type) {
        modalAlert.className = `alert alert-${type}`;
        modalAlert.textContent = message;
        modalAlert.classList.remove('d-none');
      }
      
      // Función para mostrar notificación toast
      function showToast(message, type = 'info') {
        const toastContainer = document.querySelector('.toast-container');
        const toast = document.createElement('div');
        toast.className = 'toast show';
        toast.setAttribute('role', 'alert');
        toast.setAttribute('aria-live', 'assertive');
        toast.setAttribute('aria-atomic', 'true');
        
        const bgColor = type === 'success' ? 'bg-success' : 
                       type === 'error' ? 'bg-danger' : 
                       type === 'warning' ? 'bg-warning' : 'bg-info';
        
        const title = type === 'success' ? 'Éxito' : 
                    type === 'error' ? 'Error' :
                    type === 'warning' ? 'Advertencia' : 'Información';
        
        toast.innerHTML = `
          <div class="toast-header ${bgColor} text-white">
            <strong class="me-auto">${title}</strong>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="toast"></button>
          </div>
          <div class="toast-body">
            ${message}
          </div>
        `;
        
        toastContainer.appendChild(toast);
        
        // Eliminar después de 5 segundos
        setTimeout(() => toast.remove(), 5000);
      }
      
      // Añadir evento al botón
      if (inviteMembersBtn) {
        inviteMembersBtn.addEventListener('click', function() {
          // Limpiar alerta anterior
          modalAlert.classList.add('d-none');
          inviteMembersModal.show();
        });
      }
      
      // Manejar envío del formulario
      if (inviteMembersForm) {
        inviteMembersForm.addEventListener('submit', function(e) {
          e.preventDefault();
          
          // Obtener el botón submit y deshabilitarlo
          const submitBtn = this.querySelector('button[type="submit"]');
          submitBtn.disabled = true;
          submitBtn.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Procesando...';
          
          // Limpiar alerta anterior
          modalAlert.classList.add('d-none');
          
          // Obtener datos del formulario
          const email = document.getElementById('emailMiembro').value;
          const projectName = document.getElementById('proyectoNombre').value;
          
          // Verificar que tenemos los datos necesarios
          if (!email) {
            showModalAlert('Por favor ingrese un correo electrónico', 'warning');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Invitar';
            return;
          }
          
          if (!projectName) {
            showModalAlert('Error: No se pudo determinar el nombre del proyecto', 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Invitar';
            return;
          }
          
          // Enviar solicitud con los datos correctos
          fetch('ajax/exact_name_member.php', {
            method: 'POST',
            headers: {
              'Content-Type': 'application/json',
            },
            body: JSON.stringify({
              email: email,
              projectName: projectName
            })
          })
          .then(response => response.json())
          .catch(error => {
            console.error('Error de conexión:', error);
            showModalAlert('Error de conexión: ' + error.message, 'danger');
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Invitar';
            return { ok: false, error: 'Error de conexión' };
          })
          .then(data => {
            // Restaurar botón
            submitBtn.disabled = false;
            submitBtn.innerHTML = 'Invitar';
            
            if (data.ok) {
              // Éxito
              showToast(data.mensaje || 'Usuario añadido correctamente al proyecto', 'success');
              
              // Cerrar modal
              inviteMembersModal.hide();
              
              // Recargar página
              setTimeout(() => window.location.reload(), 2000);
            } else {
              // Error
              showModalAlert(data.error || 'Error al agregar miembro', 'danger');
            }
          });
        });
      }
    });
  </script>
</body>
</html>