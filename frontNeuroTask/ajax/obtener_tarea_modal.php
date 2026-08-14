<?php
// ajax/obtener_tarea_modal.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    echo '<div class="alert alert-danger">No autenticado</div>';
    exit();
}

// Verificar que se recibió un ID de tarea
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de tarea no proporcionado</div>';
    exit();
}

$id = $_GET['id'];

// Log para depuración
error_log("Obteniendo tarea con ID: " . $id);

$response = getTaskById($id);

// Log de respuesta para depuración
error_log("Respuesta API: " . json_encode($response));

if (!isset($response['ok']) || $response['ok'] !== true || !isset($response['tarea'])) {
    echo '<div class="alert alert-danger">No se pudo cargar la tarea: ' . (isset($response['error']) ? $response['error'] : 'Error desconocido') . '</div>';
    exit();
}

$tarea = $response['tarea'];

// Obtener mensajes de la tarea
$mensajes = [];
$mensajesResponse = getMessagesByTask($id);
if (isset($mensajesResponse['ok']) && $mensajesResponse['ok'] === true && isset($mensajesResponse['mensajes'])) {
    $mensajes = $mensajesResponse['mensajes'];
}

// Formatear fechas
$fechaCreacion = isset($tarea['createdAt']) ? date('d/m/Y H:i', strtotime($tarea['createdAt'])) : 'N/A';
$fechaLimite = isset($tarea['fecha_limite']) && !empty($tarea['fecha_limite']) ? date('Y-m-d', strtotime($tarea['fecha_limite'])) : '';
$fechaLimiteFormateada = isset($tarea['fecha_limite']) && !empty($tarea['fecha_limite']) ? date('d/m/Y', strtotime($tarea['fecha_limite'])) : 'Sin fecha límite';

// Obtener usuarios para asignación
$allUsers = getAllUsers();
$usersData = isset($allUsers['usuarios']) ? $allUsers['usuarios'] : [];

// Determinar color según prioridad
$prioridadColor = '';
switch ($tarea['prioridad']) {
    case 'Alta':
        $prioridadColor = 'danger';
        break;
    case 'Media':
        $prioridadColor = 'warning';
        break;
    case 'Baja':
        $prioridadColor = 'success';
        break;
    default:
        $prioridadColor = 'secondary';
}
?>

<form id="taskUpdateForm" data-task-id="<?= $tarea['_id'] ?>">
  <input type="hidden" name="id" value="<?= $tarea['_id'] ?>">
  
  <div class="row mb-4">
    <!-- Columna izquierda: formulario principal -->
    <div class="col-lg-8 col-md-7 col-sm-12 mb-3 mb-md-0">
      <div class="mb-3">
        <label for="titulo" class="form-label">Título</label>
        <input type="text" name="titulo" id="titulo" class="form-control" value="<?= htmlspecialchars($tarea['titulo']) ?>" required>
      </div>
      
      <div class="mb-3">
        <label for="descripcion" class="form-label">Descripción</label>
        <textarea name="descripcion" id="descripcion" class="form-control" rows="4"><?= htmlspecialchars($tarea['descripcion'] ?? '') ?></textarea>
      </div>
      
      <div class="row">
        <div class="col-md-4 col-sm-12 mb-3 mb-md-0">
          <div class="mb-3">
            <label for="estado" class="form-label">Estado</label>
            <select name="estado" id="estado" class="form-select">
              <option value="Por hacer" <?= ($tarea['estado'] === 'Por hacer') ? 'selected' : '' ?>>Por hacer</option>
              <option value="En progreso" <?= ($tarea['estado'] === 'En progreso') ? 'selected' : '' ?>>En progreso</option>
              <option value="Hecho" <?= ($tarea['estado'] === 'Hecho') ? 'selected' : '' ?>>Hecho</option>
            </select>
          </div>
        </div>
        
        <div class="col-md-4 col-sm-6 mb-3 mb-md-0">
          <div class="mb-3">
            <label for="prioridad" class="form-label">Prioridad</label>
            <select name="prioridad" id="prioridad" class="form-select">
              <option value="Alta" <?= ($tarea['prioridad'] === 'Alta') ? 'selected' : '' ?>>Alta</option>
              <option value="Media" <?= ($tarea['prioridad'] === 'Media') ? 'selected' : '' ?>>Media</option>
              <option value="Baja" <?= ($tarea['prioridad'] === 'Baja') ? 'selected' : '' ?>>Baja</option>
            </select>
          </div>
        </div>
        
        <div class="col-md-4 col-sm-6">
          <div class="mb-3">
            <label for="fecha_limite" class="form-label">Fecha límite</label>
            <input type="date" name="fecha_limite" id="fecha_limite" class="form-control" value="<?= $fechaLimite ?>">
          </div>
        </div>
      </div>
      
      <div class="mb-3">
        <label for="usuario_asignado" class="form-label">Asignar a</label>
        <select name="usuario_asignado" id="usuario_asignado" class="form-select">
          <option value="">-- Sin asignar --</option>
          <?php foreach ($usersData as $user): ?>
            <option value="<?= $user['_id'] ?>" <?= (isset($tarea['usuario_asignado']['_id']) && $tarea['usuario_asignado']['_id'] === $user['_id']) ? 'selected' : '' ?>><?= htmlspecialchars($user['nombre']) ?></option>
          <?php endforeach; ?>
        </select>
      </div>
    </div>
    
    <!-- Columna derecha: información y botones -->
    <div class="col-lg-4 col-md-5 col-sm-12">
      <div class="task-metadata mb-4">
        <h5 class="mb-3">Información</h5>
        
        <div class="task-metadata-item">
          <div class="task-metadata-label">Creado:</div>
          <div><?= $fechaCreacion ?></div>
        </div>
        
        <div class="task-metadata-item">
          <div class="task-metadata-label">Último cambio:</div>
          <div><?= isset($tarea['updatedAt']) ? date('d/m/Y H:i', strtotime($tarea['updatedAt'])) : 'N/A' ?></div>
        </div>
        
        <div class="task-metadata-item">
          <div class="task-metadata-label">Estado actual:</div>
          <div><span class="badge bg-<?= getEstadoBadgeColor($tarea['estado']) ?>"><?= $tarea['estado'] ?></span></div>
        </div>
        
        <div class="task-metadata-item">
          <div class="task-metadata-label">Prioridad:</div>
          <div><span class="badge bg-<?= $prioridadColor ?>"><?= $tarea['prioridad'] ?></span></div>
        </div>
        
        <div class="task-metadata-item">
          <div class="task-metadata-label">Fecha límite:</div>
          <div><?= $fechaLimiteFormateada ?></div>
        </div>
        
        <?php if (!empty($tarea['usuario_asignado'])): ?>
        <div class="task-metadata-item">
          <div class="task-metadata-label">Asignado a:</div>
          <div><?= htmlspecialchars($tarea['usuario_asignado']['nombre'] ?? 'N/A') ?></div>
        </div>
        <?php endif; ?>
      </div>
      
      <div class="d-grid gap-2">
        <button type="submit" class="btn btn-primary">
          <i class="fas fa-save me-2"></i> Guardar cambios
        </button>
        <button type="button" class="btn btn-outline-secondary" data-bs-dismiss="modal">
          <i class="fas fa-times me-2"></i> Cancelar
        </button>
        <button type="button" id="deleteTaskBtn" class="btn btn-outline-danger mt-2" data-task-id="<?= $tarea['_id'] ?>">
          <i class="fas fa-trash-alt me-2"></i> Eliminar tarea
        </button>
      </div>
    </div>
  </div>
  
  <!-- SECCIÓN DE COMENTARIOS MOVIDA FUERA DE LA ESTRUCTURA DE COLUMNAS -->
  <div class="row mt-4">
    <div class="col-12">
      <!-- Añadir metadatos para el usuario actual -->
      <meta name="usuario-nombre" content="<?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>">
      <meta name="usuario-id" content="<?= htmlspecialchars($_SESSION['usuario']['_id']) ?>">

      <div class="comentarios-section">
        <div class="d-flex justify-content-between align-items-center mb-3">
          <h5 class="mb-0"><i class="fas fa-comments me-2"></i>Comentarios</h5>
          <span class="badge bg-primary rounded-pill" id="comment-count"><?= count($mensajes) ?></span>
        </div>
        
        <!-- Contenedor de mensajes -->
        <div class="messages-container mb-4">
          <div id="messages-loading" class="text-center p-3 d-none">
            <div class="spinner-border spinner-border-sm text-primary" role="status">
              <span class="visually-hidden">Cargando mensajes...</span>
            </div>
            <p class="mb-0 mt-2">Cargando mensajes...</p>
          </div>
          
          <div id="messages-list">
            <?php if (empty($mensajes)): ?>
              <div class="text-center py-4 text-muted" id="no-messages">
                <i class="far fa-comment-dots fa-2x mb-3"></i>
                <p>No hay comentarios para esta tarea. ¡Sé el primero en comentar!</p>
              </div>
            <?php else: ?>
              <?php 
              // Ordenar mensajes por fecha (más recientes primero)
              usort($mensajes, function($a, $b) {
                return strtotime($b['timestamp']) - strtotime($a['timestamp']);
              });
              
              foreach ($mensajes as $mensaje): 
                $esAutor = isset($mensaje['usuario']['_id']) && $mensaje['usuario']['_id'] === $_SESSION['usuario']['_id'];
                $inicial = isset($mensaje['usuario']['nombre']) ? strtoupper(substr($mensaje['usuario']['nombre'], 0, 1)) : '?';
                $fechaFormateada = isset($mensaje['timestamp']) ? formatearFechaRelativa($mensaje['timestamp']) : '';
              ?>
                <div class="mensaje-item p-3 mb-3 bg-light rounded message-card" 
                    id="mensaje-<?= $mensaje['_id'] ?>" 
                    data-message-id="<?= $mensaje['_id'] ?>" 
                    data-usuario-id="<?= $mensaje['usuario_id'] ?? (isset($mensaje['usuario']['_id']) ? $mensaje['usuario']['_id'] : '') ?>">
                  <div class="message-header d-flex justify-content-between align-items-start">
                    <div class="d-flex align-items-center">
                      <div class="user-avatar"><?= $inicial ?></div>
                      <div class="message-info ms-2">
                        <p class="message-user"><?= isset($mensaje['usuario']['nombre']) ? htmlspecialchars($mensaje['usuario']['nombre']) : 'Usuario desconocido' ?></p>
                        <small class="message-date"><?= $fechaFormateada ?></small>
                      </div>
                    </div>
                    <?php if ($esAutor): ?>
                    <div class="message-actions">
                      <div class="btn-group">
                        <button type="button" class="btn btn-sm btn-outline-secondary edit-message-btn" title="Editar mensaje" data-message-id="<?= $mensaje['_id'] ?>">
                          <i class="fas fa-edit"></i>
                        </button>
                        <button type="button" class="btn btn-sm btn-outline-danger delete-message-btn" title="Eliminar mensaje" data-message-id="<?= $mensaje['_id'] ?>">
                          <i class="fas fa-trash-alt"></i>
                        </button>
                      </div>
                    </div>
                    <?php endif; ?>
                  </div>
                  <div class="message-content mt-2" id="content-<?= $mensaje['_id'] ?>"><?= nl2br(htmlspecialchars($mensaje['contenido'])) ?></div>
                </div>
              <?php endforeach; ?>
            <?php endif; ?>
          </div>
          
          <!-- Formulario para nuevo comentario -->
          <div class="new-message-form">
            <div class="card">
              <div class="card-body">
                <h6 class="card-title mb-3">Añadir comentario</h6>
                <div class="mb-3">
                  <textarea class="form-control" id="nuevo_comentario" rows="3" placeholder="Escribe tu comentario aquí..."></textarea>
                </div>
                <div class="d-flex justify-content-end">
                  <!-- Agregar atributo tarea-id como data y como valor oculto para redundancia -->
                  <input type="hidden" id="comentario_tarea_id" value="<?= htmlspecialchars($tarea['_id']) ?>">
                  <button type="button" class="btn btn-primary" id="addCommentBtn" data-task-id="<?= htmlspecialchars($tarea['_id']) ?>">
                    <i class="fas fa-paper-plane me-2"></i>Enviar comentario
                  </button>
                </div>
              </div>
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>

<style>
  /* Estilos para la sección de mensajes */
  .messages-container {
    max-height: 350px;
    overflow-y: auto;
    padding-right: 5px;
  }
  
  .message-card {
    border-radius: 8px;
    margin-bottom: 15px;
    border-left: 3px solid var(--bluepurple-light, #a680ff);
    background-color: var(--light-gray, #f5f6f8);
    box-shadow: 0 2px 4px rgba(0,0,0,0.05);
    transition: transform 0.2s;
  }
  
  .message-card:hover {
    transform: translateY(-2px);
    box-shadow: 0 4px 8px rgba(0,0,0,0.08);
  }
  
  .message-header {
    display: flex;
    align-items: center;
    padding: 12px 15px 5px;
  }
  
  .user-avatar {
    width: 36px;
    height: 36px;
    border-radius: 50%;
    background-color: var(--bluepurple-light, #a680ff);
    color: white;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 600;
    margin-right: 10px;
    font-size: 1rem;
  }
  
  .message-info {
    flex: 1;
  }
  
  .message-user {
    font-weight: 600;
    margin-bottom: 0;
    color: var(--text-dark, #333);
    font-size: 0.9rem;
  }
  
  .message-date {
    font-size: 0.75rem;
    color: var(--text-gray, #777);
  }
  
  .message-content {
    padding: 5px 15px 15px;
    color: var(--text-dark, #333);
    font-size: 0.95rem;
    white-space: pre-wrap;
    word-break: break-word;
  }
  
  .message-actions {
    padding: 0 15px 12px;
    display: flex;
    justify-content: flex-end;
  }
  
  .message-btn {
    background: none;
    border: none;
    color: var(--text-gray, #777);
    font-size: 0.8rem;
    padding: 3px 8px;
    cursor: pointer;
    transition: color 0.2s;
  }
  
  .message-btn:hover {
    color: #dc3545;
  }
  
  /* Formulario de nuevo mensaje */
  .new-message-form textarea {
    resize: none;
    border-radius: 8px;
  }
  
  .new-message-form textarea:focus {
    border-color: var(--bluepurple-light, #a680ff);
    box-shadow: 0 0 0 0.25rem rgba(166, 128, 255, 0.25);
  }
  
  /* Mejoras para responsividad */
  .task-metadata-item {
    display: flex;
    justify-content: space-between;
    margin-bottom: 0.75rem;
    flex-wrap: wrap;
    gap: 0.5rem;
  }
  
  .task-metadata-label {
    font-weight: 500;
    color: var(--text-gray, #777);
    flex: 0 0 100px;
  }
  
  /* Responsive para diferentes tamaños de pantalla */
  @media (max-width: 991.98px) {
    .task-metadata {
      margin-bottom: 2rem;
    }
  }
  
  @media (max-width: 767.98px) {
    .messages-container {
      max-height: 300px;
    }
    
    .task-metadata-item {
      margin-bottom: 0.5rem;
    }
    
    /* Asegurar que los elementos de formulario se expandan correctamente */
    .form-control, .form-select {
      width: 100%;
    }
    
    /* Asegurar que los metadatos siempre tengan buen aspecto */
    .task-metadata {
      padding: 12px;
      margin-bottom: 1rem;
      border-radius: 8px;
    }
    
    /* Mejorar orden en dispositivos móviles */
    .row {
      flex-direction: column;
    }
    
    .col-lg-4 {
      order: -1;
      margin-bottom: 20px;
    }
  }
  
  /* Para pantallas muy pequeñas */
  @media (max-width: 575.98px) {
    .messages-container {
      max-height: 200px;
    }
    
    .message-header {
      padding: 10px 12px 5px;
      flex-direction: column;
      align-items: flex-start;
    }
    
    .message-content {
      padding: 5px 12px 12px;
      font-size: 0.9rem;
    }
    
    .user-avatar {
      width: 32px;
      height: 32px;
      font-size: 0.9rem;
    }
    
    .message-actions {
      margin-top: 0.5rem;
      padding: 0 12px 10px;
    }
    
    /* Reducir padding en formularios */
    .form-label {
      margin-bottom: 0.25rem;
    }
    
    .row > * {
      padding-left: 10px;
      padding-right: 10px;
    }
  }
  
  /* Corrección para pantallas muy pequeñas */
  @media (max-width: 480px) {
    .task-metadata-item {
      flex-direction: column;
      align-items: flex-start;
    }
    
    .task-metadata-label {
      flex: 0 0 100%;
      margin-bottom: 0.25rem;
    }
  }
  
  /* Asegurar que los iconos se muestren correctamente */
  .fas, .far, .fa {
    display: inline-block;
    width: 1.25em;
    text-align: center;
  }
  
  /* Corrección para la sección de comentarios */
  .comentarios-section {
    border-top: 1px solid #dee2e6;
    padding-top: 20px;
    margin-top: 10px;
  }
</style>

<script>
  // Reemplazar cualquier manejador existente
  document.getElementById('taskUpdateForm').addEventListener('submit', function(e) {
    e.preventDefault();
    console.log('Formulario interceptado');
    
    // Crear FormData con el formulario
    const formData = new FormData(this);
    
    // Obtener ID de la tarea
    const tareaId = this.getAttribute('data-task-id');
    formData.append('id', tareaId);
    
    // Mostrar los datos que se van a enviar (para depuración)
    console.log('ID de tarea:', tareaId);
    console.log('Datos del formulario:');
    for (const [key, value] of formData.entries()) {
      console.log(`${key}: ${value}`);
    }
    
    // Sanear los datos
    if (formData.get('fecha_limite') === '') {
      console.log('Eliminando fecha_limite vacía');
      formData.delete('fecha_limite');
    }
    
    if (formData.get('usuario_asignado') === '') {
      console.log('Estableciendo usuario_asignado como cadena vacía');
      formData.set('usuario_asignado', '');
    }
    
    // Mostrar notificación de carga
    const btnSubmit = this.querySelector('button[type="submit"]');
    if (btnSubmit) {
      btnSubmit.disabled = true;
      btnSubmit.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i> Actualizando...';
    }
    
    // Enviar solicitud AJAX
    fetch('ajax/actualizar_tarea.php', {
      method: 'POST',
      body: formData
    })
    .then(response => {
      console.log('Código de respuesta:', response.status);
      return response.json();
    })
    .then(data => {
      console.log('Respuesta recibida:', data);
      
      // Restaurar el botón
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-save me-2"></i> Guardar cambios';
      }
      
      if (data.ok) {
        // Mostrar mensaje de éxito
        alert('Tarea actualizada correctamente');
        
        // Cerrar el modal (si existe un botón para cerrar)
        const closeBtn = document.querySelector('[data-bs-dismiss="modal"]');
        if (closeBtn) {
          closeBtn.click();
        }
        
        // Recargar la página para mostrar los cambios
        window.location.reload();
      } else {
        // Mostrar mensaje de error
        alert('Error al actualizar la tarea: ' + (data.error || 'Error desconocido'));
      }
    })
    .catch(error => {
      console.error('Error en la solicitud:', error);
      alert('Error al comunicarse con el servidor');
      
      // Restaurar el botón
      if (btnSubmit) {
        btnSubmit.disabled = false;
        btnSubmit.innerHTML = '<i class="fas fa-save me-2"></i> Guardar cambios';
      }
    });
  });

  // Función para formatear fechas relativas
  function formatearFechaRelativa(fechaString) {
    if (!fechaString) return 'Fecha desconocida';
    
    try {
      const fecha = new Date(fechaString);
      if (isNaN(fecha.getTime())) return 'Fecha inválida';
      
      const ahora = new Date();
      const diffMs = ahora - fecha;
      const diffSecs = Math.floor(diffMs / 1000);
      const diffMins = Math.floor(diffSecs / 60);
      const diffHours = Math.floor(diffMins / 60);
      const diffDays = Math.floor(diffHours / 24);
      
      if (diffDays < 1) {
        if (diffHours < 1) {
          if (diffMins < 1) {
            return 'Hace unos segundos';
          }
          return `Hace ${diffMins} ${diffMins === 1 ? 'minuto' : 'minutos'}`;
        }
        return `Hace ${diffHours} ${diffHours === 1 ? 'hora' : 'horas'}`;
      }
      
      if (diffDays === 1) {
        return `Ayer a las ${fecha.getHours().toString().padStart(2, '0')}:${fecha.getMinutes().toString().padStart(2, '0')}`;
      }
      
      if (diffDays < 7) {
        const dias = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
        return `${dias[fecha.getDay()]} a las ${fecha.getHours().toString().padStart(2, '0')}:${fecha.getMinutes().toString().padStart(2, '0')}`;
      }
      
      return `${fecha.getDate()}/${fecha.getMonth() + 1}/${fecha.getFullYear()} ${fecha.getHours().toString().padStart(2, '0')}:${fecha.getMinutes().toString().padStart(2, '0')}`;
    } catch (e) {
      console.error('Error al formatear fecha:', e);
      return 'Fecha desconocida';
    }
  }

  // Función para mostrar notificaciones (si no existe en el código global)
  function showNotification(message, type) {
    console.log(`Notificación [${type}]: ${message}`);
    // Si ya existe esta función en el contexto global, esta definición será ignorada
    alert(message);
  }

  // Función para escapar HTML
  function escapeHTML(text) {
    const div = document.createElement('div');
    div.textContent = text;
    return div.innerHTML;
  }

  // Función para convertir saltos de línea a <br>
  function nl2br(str) {
    return str.replace(/\n/g, '<br>');
  }

  // Configurar evento para eliminar mensajes
  document.querySelectorAll('.delete-message-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      if (confirm('¿Estás seguro de que deseas eliminar este comentario?')) {
        const messageId = this.dataset.messageId;
        
        // Mensaje de depuración
        console.log('Eliminando mensaje ID:', messageId);
        
        fetch('ajax/eliminar_mensajes.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({ id: messageId })
        })
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            // Eliminar visualmente el mensaje con animación
            const messageCard = document.getElementById(`mensaje-${messageId}`);
            if (messageCard) {
              messageCard.style.opacity = '0';
              messageCard.style.transform = 'translateX(20px)';
              messageCard.style.transition = 'opacity 0.3s ease, transform 0.3s ease';
              
              setTimeout(() => {
                messageCard.remove();
                // Actualizar contador
                const counter = document.getElementById('comment-count');
                if (counter) {
                  counter.textContent = parseInt(counter.textContent) - 1;
                }
                
                // Mostrar mensaje de "no hay comentarios" si era el último
                if (document.querySelectorAll('.message-card').length === 0) {
                  document.getElementById('messages-list').innerHTML = `
                    <div class="text-center py-4 text-muted" id="no-messages">
                      <i class="far fa-comment-dots fa-2x mb-3"></i>
                      <p>No hay comentarios para esta tarea. ¡Sé el primero en comentar!</p>
                    </div>
                  `;
                }
              }, 300);
            }
            showNotification('Comentario eliminado correctamente', 'success');
          } else {
            showNotification('Error: ' + (data.error || 'No se pudo eliminar el comentario'), 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          showNotification('Error al eliminar el comentario', 'error');
        });
      }
    });
  });

  // Configurar eventos para editar mensajes
  document.querySelectorAll('.edit-message-btn').forEach(btn => {
    btn.addEventListener('click', function(e) {
      e.preventDefault();
      e.stopPropagation();
      
      const messageId = this.dataset.messageId;
      const contentElement = document.getElementById(`content-${messageId}`);
      
      if (!contentElement) return;
      
      // Obtener el contenido actual (eliminando los <br> HTML)
      const currentContent = contentElement.innerHTML.replace(/<br\s*\/?>/gi, '\n');
      const plainContent = document.createElement('div');
      plainContent.innerHTML = currentContent;
      const cleanContent = plainContent.textContent;
      
      // Crear un textarea para editar
      const textArea = document.createElement('textarea');
      textArea.className = 'form-control mb-2';
      textArea.value = cleanContent;
      textArea.rows = 3;
      
      // Crear botones de acción
      const actionButtons = document.createElement('div');
      actionButtons.className = 'd-flex justify-content-end gap-2 mb-2';
      actionButtons.innerHTML = `
        <button class="btn btn-sm btn-outline-secondary cancel-edit-btn">Cancelar</button>
        <button class="btn btn-sm btn-primary save-edit-btn">Guardar</button>
      `;
      
      // Guardar el contenido original para poder restaurarlo si se cancela
      contentElement.dataset.originalContent = contentElement.innerHTML;
      
      // Reemplazar el contenido con el textarea y botones
      contentElement.innerHTML = '';
      contentElement.appendChild(textArea);
      contentElement.appendChild(actionButtons);
      
      // Enfocar el textarea
      textArea.focus();
      
      // Manejar evento de cancelar
      actionButtons.querySelector('.cancel-edit-btn').addEventListener('click', function() {
        contentElement.innerHTML = contentElement.dataset.originalContent;
      });
      
      // Manejar evento de guardar
      actionButtons.querySelector('.save-edit-btn').addEventListener('click', function() {
        const newContent = textArea.value.trim();
        
        if (!newContent) {
          showNotification('El comentario no puede estar vacío', 'warning');
          return;
        }
        
        // Deshabilitar botones mientras se procesa
        this.disabled = true;
        actionButtons.querySelector('.cancel-edit-btn').disabled = true;
        this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
        
        // Enviar la edición
        fetch('ajax/actualizar_mensaje.php', {
          method: 'POST',
          headers: {
            'Content-Type': 'application/json',
          },
          body: JSON.stringify({
            id: messageId,
            contenido: newContent
          })
        })
        .then(response => response.json())
        .then(data => {
          if (data.ok) {
            // Actualizar el contenido del mensaje
            contentElement.innerHTML = nl2br(escapeHTML(newContent));
            showNotification('Comentario actualizado correctamente', 'success');
          } else {
            // Restaurar el contenido original
            contentElement.innerHTML = contentElement.dataset.originalContent;
            showNotification('Error: ' + (data.error || 'No se pudo actualizar el comentario'), 'error');
          }
        })
        .catch(error => {
          console.error('Error:', error);
          contentElement.innerHTML = contentElement.dataset.originalContent;
          showNotification('Error al comunicarse con el servidor', 'error');
        });
      });
    });
  });

  // CÓDIGO MEJORADO PARA ENVIAR COMENTARIOS
  document.getElementById('addCommentBtn').addEventListener('click', function() {
    // Obtener los datos con verificación adicional
    const taskId = this.getAttribute('data-task-id') || document.getElementById('comentario_tarea_id').value;
    const comentario = document.getElementById('nuevo_comentario').value.trim();
    
    console.log('Verificando datos para enviar comentario:');
    console.log('- ID de tarea:', taskId);
    console.log('- Contenido:', comentario ? 'Presente (longitud: ' + comentario.length + ')' : 'Vacío');
    
    if (!comentario) {
      showNotification('Por favor, escribe un comentario', 'warning');
      return;
    }
    
    if (!taskId) {
      console.error('Error crítico: El ID de tarea no está disponible');
      // Última alternativa: intentar obtener del formulario
      const formTaskId = document.querySelector('form#taskUpdateForm').getAttribute('data-task-id');
      if (formTaskId) {
        console.log('ID de tarea alternativo encontrado:', formTaskId);
        taskId = formTaskId;
      } else {
        showNotification('Error del sistema: No se pudo determinar la tarea', 'error');
        return;
      }
    }
    
    // Deshabilitar botón mientras se procesa
    this.disabled = true;
    const originalText = this.innerHTML;
    this.innerHTML = '<i class="fas fa-spinner fa-spin me-2"></i>Enviando...';
    
    // Preparar los datos en formato JSON
    const data = {
      tarea_id: taskId,
      contenido: comentario
    };
    
    console.log('Datos a enviar:', JSON.stringify(data));
    
    // Enviar comentario mediante AJAX
    fetch('ajax/crear_mensaje.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest'
      },
      body: JSON.stringify(data)
    })
    .then(response => {
      console.log('Status de respuesta:', response.status);
      // Extraer y parsear el texto JSON, con manejo de errores robusto
      return response.text().then(text => {
        console.log('Texto de respuesta raw:', text);
        try {
          // Intentar parsear el JSON
          return JSON.parse(text);
        } catch (e) {
          console.error('Error al parsear JSON:', e);
          // Devolver un objeto de error formateado
          return { 
            ok: false, 
            error: 'Error en formato de respuesta', 
            rawResponse: text 
          };
        }
      });
    })
    .then(data => {
      console.log('Respuesta del servidor (procesada):', data);
      
      // Restaurar botón
      this.disabled = false;
      this.innerHTML = originalText;
      
      // Verificar si la respuesta tiene alguna señal de éxito
      const esRespuestaExitosa = 
        (data.ok === true) || 
        (data.mensaje && data.mensaje._id) || 
        (data._id) ||
        (data.insertedId);
      
      // Crear un objeto mensaje estandarizado, buscando datos en diferentes lugares posibles
      const mensaje = {
        _id: data.mensaje?._id || data._id || data.insertedId || 'temp-' + Date.now(),
        contenido: comentario,
        timestamp: new Date().toISOString(),
        usuario: {
          _id: '<?= htmlspecialchars($_SESSION['usuario']['_id']) ?>',
          nombre: '<?= htmlspecialchars($_SESSION['usuario']['nombre']) ?>'
        }
      };
      
      // Si hay alguna señal de éxito, mostrar el mensaje aunque la estructura no sea perfecta
      if (esRespuestaExitosa) {
        // Actualizar la interfaz como si fuera exitoso
        // Limpiar el campo de comentario
        document.getElementById('nuevo_comentario').value = '';
        
        // Añadir el nuevo mensaje a la lista
        const noMensajes = document.getElementById('no-messages');
        if (noMensajes) {
          noMensajes.remove();
        }
        
        // Crear elemento de mensaje
        const messageElement = document.createElement('div');
        messageElement.className = 'mensaje-item p-3 mb-3 bg-light rounded message-card';
        messageElement.id = `mensaje-${mensaje._id}`;
        messageElement.setAttribute('data-message-id', mensaje._id);
        messageElement.setAttribute('data-usuario-id', mensaje.usuario._id);
        messageElement.style.opacity = '0';
        messageElement.style.transform = 'translateY(20px)';
        messageElement.style.transition = 'opacity 0.5s ease, transform 0.5s ease';
        
        // Obtener inicial del usuario
        const userInitial = '<?= strtoupper(substr($_SESSION['usuario']['nombre'], 0, 1)) ?>';
        
        messageElement.innerHTML = `
          <div class="message-header d-flex justify-content-between align-items-start">
            <div class="d-flex align-items-center">
              <div class="user-avatar">${userInitial}</div>
              <div class="message-info ms-2">
                <p class="message-user"><?= htmlspecialchars($_SESSION['usuario']['nombre']) ?></p>
                <small class="message-date">Hace unos segundos</small>
              </div>
            </div>
            <div class="message-actions">
              <div class="btn-group">
                <button type="button" class="btn btn-sm btn-outline-secondary edit-message-btn" title="Editar mensaje" data-message-id="${mensaje._id}">
                  <i class="fas fa-edit"></i>
                </button>
                <button type="button" class="btn btn-sm btn-outline-danger delete-message-btn" title="Eliminar mensaje" data-message-id="${mensaje._id}">
                  <i class="fas fa-trash-alt"></i>
                </button>
              </div>
            </div>
          </div>
          <div class="message-content mt-2" id="content-${mensaje._id}">${nl2br(escapeHTML(comentario))}</div>
        `;
        
        // Añadir al inicio de la lista
        const messagesList = document.getElementById('messages-list');
        if (messagesList.firstChild) {
          messagesList.insertBefore(messageElement, messagesList.firstChild);
        } else {
          messagesList.appendChild(messageElement);
        }
        
        // Animar entrada
        setTimeout(() => {
          messageElement.style.opacity = '1';
          messageElement.style.transform = 'translateY(0)';
        }, 50);
        
        // Actualizar contador
        const counter = document.getElementById('comment-count');
        if (counter) {
          counter.textContent = parseInt(counter.textContent) + 1;
        }
        
        // Añadir evento de eliminación al nuevo mensaje
        const deleteBtn = messageElement.querySelector('.delete-message-btn');
        if (deleteBtn) {
          deleteBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            if (confirm('¿Estás seguro de que deseas eliminar este comentario?')) {
              const messageId = this.dataset.messageId;
              
              fetch('ajax/eliminar_mensajes.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({ id: messageId })
              })
              .then(response => response.json())
              .then(data => {
                if (data.ok) {
                  // Eliminar visualmente el mensaje
                  const messageCard = document.getElementById(`mensaje-${messageId}`);
                  if (messageCard) {
                    messageCard.style.opacity = '0';
                    messageCard.style.transform = 'translateX(20px)';
                    
                    setTimeout(() => {
                      messageCard.remove();
                      // Actualizar contador
                      const counter = document.getElementById('comment-count');
                      if (counter) {
                        counter.textContent = parseInt(counter.textContent) - 1;
                      }
                      
                      // Mostrar mensaje de "no hay comentarios" si era el último
                      if (document.querySelectorAll('.message-card').length === 0) {
                        document.getElementById('messages-list').innerHTML = `
                          <div class="text-center py-4 text-muted" id="no-messages">
                            <i class="far fa-comment-dots fa-2x mb-3"></i>
                            <p>No hay comentarios para esta tarea. ¡Sé el primero en comentar!</p>
                          </div>
                        `;
                      }
                    }, 300);
                  }
                  showNotification('Comentario eliminado correctamente', 'success');
                } else {
                  showNotification('Error: ' + (data.error || 'No se pudo eliminar el comentario'), 'error');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                showNotification('Error al eliminar el comentario', 'error');
              });
            }
          });
        }
        
        // Añadir evento de edición al nuevo mensaje
        const editBtn = messageElement.querySelector('.edit-message-btn');
        if (editBtn) {
          editBtn.addEventListener('click', function(e) {
            e.preventDefault();
            e.stopPropagation();
            
            const messageId = this.dataset.messageId;
            const contentElement = document.getElementById(`content-${messageId}`);
            
            if (!contentElement) return;
            
            // Obtener el contenido actual
            const currentContent = contentElement.textContent;
            
            // Crear un textarea para editar
            const textArea = document.createElement('textarea');
            textArea.className = 'form-control mb-2';
            textArea.value = currentContent;
            textArea.rows = 3;
            
            // Crear botones de acción
            const actionButtons = document.createElement('div');
            actionButtons.className = 'd-flex justify-content-end gap-2 mb-2';
            actionButtons.innerHTML = `
              <button class="btn btn-sm btn-outline-secondary cancel-edit-btn">Cancelar</button>
              <button class="btn btn-sm btn-primary save-edit-btn">Guardar</button>
            `;
            
            // Guardar el contenido original
            contentElement.dataset.originalContent = contentElement.innerHTML;
            
            // Reemplazar el contenido
            contentElement.innerHTML = '';
            contentElement.appendChild(textArea);
            contentElement.appendChild(actionButtons);
            
            // Enfocar el textarea
            textArea.focus();
            
            // Manejar evento de cancelar
            actionButtons.querySelector('.cancel-edit-btn').addEventListener('click', function() {
              contentElement.innerHTML = contentElement.dataset.originalContent;
            });
            
            // Manejar evento de guardar
            actionButtons.querySelector('.save-edit-btn').addEventListener('click', function() {
              const newContent = textArea.value.trim();
              
              if (!newContent) {
                showNotification('El comentario no puede estar vacío', 'warning');
                return;
              }
              
              // Deshabilitar botones
              this.disabled = true;
              actionButtons.querySelector('.cancel-edit-btn').disabled = true;
              this.innerHTML = '<i class="fas fa-spinner fa-spin"></i> Guardando...';
              
              // Enviar la edición
              fetch('ajax/actualizar_mensaje.php', {
                method: 'POST',
                headers: {
                  'Content-Type': 'application/json',
                },
                body: JSON.stringify({
                  id: messageId,
                  contenido: newContent
                })
              })
              .then(response => response.json())
              .then(data => {
                if (data.ok) {
                  contentElement.innerHTML = nl2br(escapeHTML(newContent));
                  showNotification('Comentario actualizado correctamente', 'success');
                } else {
                  contentElement.innerHTML = contentElement.dataset.originalContent;
                  showNotification('Error: ' + (data.error || 'No se pudo actualizar el comentario'), 'error');
                }
              })
              .catch(error => {
                console.error('Error:', error);
                contentElement.innerHTML = contentElement.dataset.originalContent;
                showNotification('Error al comunicarse con el servidor', 'error');
              });
            });
          });
        }
        
        showNotification('Comentario añadido correctamente', 'success');
      } else {
        showNotification('Error: ' + (data.error || 'No se pudo añadir el comentario'), 'error');
        console.error('Detalles de la respuesta de error:', data);
      }
    })
    .catch(error => {
      console.error('Error en la solicitud:', error);
      this.disabled = false;
      this.innerHTML = originalText;
      showNotification('Error al comunicarse con el servidor', 'error');
    });
  });

  // Detectar y corregir problemas comunes en pantallas pequeñas
  function fixLayoutIssues() {
    // Ajustar altura del contenedor de mensajes en móviles
    const messagesContainer = document.querySelector('.messages-container');
    if (messagesContainer) {
      if (window.innerWidth < 768) {
        messagesContainer.style.maxHeight = '250px';
      } else {
        messagesContainer.style.maxHeight = '350px';
      }
    }
    
    // Verificar fechas inválidas
    document.querySelectorAll('.message-date').forEach(el => {
      if (el.textContent.includes('NaN') || el.textContent.includes('Invalid Date')) {
        el.textContent = 'Fecha desconocida';
      }
    });
  }

  // Ejecutar correcciones al cargar
  document.addEventListener('DOMContentLoaded', function() {
    fixLayoutIssues();
    
    // Verificar permisos del usuario
    const usuarioActualId = document.querySelector('meta[name="usuario-id"]')?.content || '';
    const esPropietario = usuarioActualId === '<?= isset($tarea['usuario_id']) ? $tarea['usuario_id'] : "" ?>';
    
    console.log('Usuario actual:', usuarioActualId);
    console.log('Es propietario:', esPropietario);
  });

  // Ajustar al cambiar tamaño de ventana
  window.addEventListener('resize', fixLayoutIssues);

  // Añade este código al final del archivo obtener_tarea_modal.php
// Agrega un botón de prueba en el DOM

document.addEventListener('DOMContentLoaded', function() {
  // Añadir botón de prueba después de cargar el DOM
  const testButton = document.createElement('button');
  testButton.className = 'btn btn-info mt-3';
  testButton.textContent = 'Test Editar Mensaje';
  testButton.style.position = 'fixed';
  testButton.style.bottom = '20px';
  testButton.style.right = '20px';
  testButton.style.zIndex = '9999';
  
  testButton.addEventListener('click', function() {
    // Probemos una solicitud directa de prueba
    const testMessageId = document.querySelector('[data-message-id]')?.dataset.messageId || 'ID_DE_PRUEBA';
    console.log('Iniciando prueba de edición con ID:', testMessageId);
    
    // Crear un mensaje de estado en la página
    const statusDiv = document.createElement('div');
    statusDiv.style.position = 'fixed';
    statusDiv.style.top = '20px';
    statusDiv.style.right = '20px';
    statusDiv.style.padding = '15px';
    statusDiv.style.background = '#f8f9fa';
    statusDiv.style.border = '1px solid #dee2e6';
    statusDiv.style.borderRadius = '5px';
    statusDiv.style.zIndex = '10000';
    statusDiv.style.maxWidth = '400px';
    statusDiv.style.maxHeight = '400px';
    statusDiv.style.overflow = 'auto';
    statusDiv.innerHTML = '<h4>Prueba de edición</h4><div id="test-status"></div>';
    document.body.appendChild(statusDiv);
    
    const updateStatus = (msg, isError = false) => {
      const statusEl = document.getElementById('test-status');
      const newP = document.createElement('p');
      newP.style.margin = '5px 0';
      newP.style.color = isError ? 'red' : 'green';
      newP.textContent = msg;
      statusEl.appendChild(newP);
    };
    
    // 1. Probar AJAX a actualizar_mensaje.php
    updateStatus('Probando actualizar_mensaje.php...');
    fetch('ajax/actualizar_mensaje.php', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-Test': 'true'
      },
      body: JSON.stringify({
        id: testMessageId,
        contenido: 'Test contenido actualizado ' + new Date().toLocaleTimeString()
      })
    })
    .then(response => {
      updateStatus(`Respuesta recibida: ${response.status} ${response.statusText}`);
      return response.text().then(text => {
        try {
          return JSON.parse(text);
        } catch (e) {
          updateStatus(`Error parsing JSON: ${e.message}`, true);
          updateStatus(`Texto recibido: ${text}`, true);
          throw new Error('Respuesta no es JSON válido');
        }
      });
    })
    .then(data => {
      updateStatus(`Respuesta procesada: ${JSON.stringify(data)}`);
      if (data.ok) {
        updateStatus('✓ Prueba exitosa con actualizar_mensaje.php');
      } else {
        updateStatus(`✗ Error: ${data.error || 'Desconocido'}`, true);
      }
    })
    .catch(error => {
      updateStatus(`✗ Error en fetch: ${error.message}`, true);
    })
    .finally(() => {
      // 2. Probar AJAX a editar_mensaje.php
      updateStatus('Probando editar_mensaje.php...');
      fetch('ajax/editar_mensaje.php', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'X-Test': 'true'
        },
        body: JSON.stringify({
          id: testMessageId,
          contenido: 'Test contenido editado ' + new Date().toLocaleTimeString()
        })
      })
      .then(response => {
        updateStatus(`Respuesta recibida: ${response.status} ${response.statusText}`);
        return response.text().then(text => {
          try {
            return JSON.parse(text);
          } catch (e) {
            updateStatus(`Error parsing JSON: ${e.message}`, true);
            updateStatus(`Texto recibido: ${text}`, true);
            throw new Error('Respuesta no es JSON válido');
          }
        });
      })
      .then(data => {
        updateStatus(`Respuesta procesada: ${JSON.stringify(data)}`);
        if (data.ok) {
          updateStatus('✓ Prueba exitosa con editar_mensaje.php');
        } else {
          updateStatus(`✗ Error: ${data.error || 'Desconocido'}`, true);
        }
        
        // Añadir un enlace al archivo de test
        const testLink = document.createElement('a');
        testLink.href = 'ajax/test_actualizar_mensaje.php?id=' + testMessageId;
        testLink.target = '_blank';
        testLink.className = 'btn btn-sm btn-primary mt-2';
        testLink.textContent = 'Probar con test_actualizar_mensaje.php';
        document.getElementById('test-status').appendChild(testLink);
      })
      .catch(error => {
        updateStatus(`✗ Error en fetch: ${error.message}`, true);
      });
    });
  });
  
  document.body.appendChild(testButton);
});
</script>
</form>

<?php
// Función para formatear fecha relativa
function formatearFechaRelativa($fechaString) {
  if (!$fechaString) return 'Fecha desconocida';
  
  try {
    $fecha = strtotime($fechaString);
    if ($fecha === false) return 'Fecha inválida';
    
    $ahora = time();
    $diff = $ahora - $fecha;
    
    $segundos = $diff;
    $minutos = floor($segundos / 60);
    $horas = floor($minutos / 60);
    $dias = floor($horas / 24);
    
    if ($dias < 1) {
      if ($horas < 1) {
        if ($minutos < 1) {
          return 'Hace unos segundos';
        }
        return 'Hace ' . $minutos . ($minutos == 1 ? ' minuto' : ' minutos');
      }
      return 'Hace ' . $horas . ($horas == 1 ? ' hora' : ' horas');
    }
    
    if ($dias == 1) {
      return 'Ayer a las ' . date('H:i', $fecha);
    }
    
    if ($dias < 7) {
      $diasSemana = ['Domingo', 'Lunes', 'Martes', 'Miércoles', 'Jueves', 'Viernes', 'Sábado'];
      return $diasSemana[date('w', $fecha)] . ' a las ' . date('H:i', $fecha);
    }
    
    return date('d/m/Y H:i', $fecha);
  } catch (Exception $e) {
    error_log('Error al formatear fecha: ' . $e->getMessage());
    return 'Fecha desconocida';
  }
}

// Función para determinar el color de la insignia según el estado
function getEstadoBadgeColor($estado) {
  switch ($estado) {
    case 'Por hacer':
      return 'warning';
    case 'En progreso':
      return 'primary';
    case 'Hecho':
      return 'success';
    default:
      return 'secondary';
  }
}