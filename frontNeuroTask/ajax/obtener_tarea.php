<?php
// ajax/obtener_tarea.php
session_start();
require_once '../includes/api.php';

// Verificar que el usuario esté autenticado
if (!isset($_SESSION['usuario'])) {
    header('Content-Type: application/json');
    echo json_encode(['error' => 'No autenticado']);
    exit();
}

// Verificar que se recibió un ID de tarea
if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">ID de tarea no proporcionado</div>';
    exit();
}

$id = $_GET['id'];
$response = getTaskById($id);

if (!isset($response['ok']) || $response['ok'] !== true || !isset($response['tarea'])) {
    echo '<div class="alert alert-danger">No se pudo cargar la tarea</div>';
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
$fechaLimite = isset($tarea['fecha_limite']) && !empty($tarea['fecha_limite']) ? date('d/m/Y', strtotime($tarea['fecha_limite'])) : 'Sin fecha límite';

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

// Determinar icono según estado
$estadoIcon = '';
switch ($tarea['estado']) {
    case 'Por hacer':
        $estadoIcon = 'far fa-circle';
        break;
    case 'En progreso':
        $estadoIcon = 'fas fa-sync-alt';
        break;
    case 'Hecho':
        $estadoIcon = 'fas fa-check-circle';
        break;
    default:
        $estadoIcon = 'far fa-question-circle';
}
?>

<div class="d-flex justify-content-between align-items-start mb-3">
    <h3><?= htmlspecialchars($tarea['titulo']) ?></h3>
    <button type="button" class="btn-close" id="closeTaskDetailsBtn" aria-label="Close"></button>
</div>

<div class="mb-3">
    <span class="badge bg-<?= $prioridadColor ?> me-2"><?= htmlspecialchars($tarea['prioridad']) ?></span>
    <span class="badge bg-light text-dark me-2">
        <i class="<?= $estadoIcon ?> me-1"></i>
        <?= htmlspecialchars($tarea['estado']) ?>
    </span>
    <span class="badge bg-light text-dark">
        <i class="far fa-calendar me-1"></i>
        <?= $fechaLimite ?>
    </span>
</div>

<div class="row mb-4">
    <div class="col-md-8">
        <h5><i class="fas fa-align-left me-2"></i> Descripción</h5>
        <div class="task-description">
            <?php if (!empty($tarea['descripcion'])): ?>
                <?= nl2br(htmlspecialchars($tarea['descripcion'])) ?>
            <?php else: ?>
                <p class="text-muted">Sin descripción.</p>
            <?php endif; ?>
        </div>
        
        <h5 class="mt-4"><i class="fas fa-comments me-2"></i> Comentarios</h5>
        <div class="task-messages mb-3">
            <?php if (!empty($mensajes)): ?>
                <?php foreach ($mensajes as $mensaje): ?>
                    <div class="message">
                        <div class="d-flex justify-content-between">
                            <div class="message-user">
                                <?= isset($mensaje['usuario']['nombre']) ? htmlspecialchars($mensaje['usuario']['nombre']) : 'Usuario' ?>
                            </div>
                            <div class="message-timestamp">
                                <?= isset($mensaje['timestamp']) ? date('d/m/Y H:i', strtotime($mensaje['timestamp'])) : '' ?>
                            </div>
                        </div>
                        <div class="message-content">
                            <?= nl2br(htmlspecialchars($mensaje['contenido'])) ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted">No hay comentarios en esta tarea.</p>
            <?php endif; ?>
        </div>
        
        <form id="commentForm" action="ajax/crear_mensaje.php" method="POST">
            <input type="hidden" name="tarea_id" value="<?= $tarea['_id'] ?>">
            <input type="hidden" name="usuario_id" value="<?= $_SESSION['usuario']['_id'] ?>">
            <div class="mb-3">
                <textarea name="contenido" class="form-control" placeholder="Escribe un comentario..." rows="2" required></textarea>
            </div>
            <div class="d-flex justify-content-end">
                <button type="submit" class="btn btn-primary">Añadir comentario</button>
            </div>
        </form>
    </div>
    
    <div class="col-md-4">
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-user me-2"></i> Asignado a</h5>
            </div>
            <div class="card-body">
                <?php if (isset($tarea['usuario_asignado']) && is_array($tarea['usuario_asignado']) && !empty($tarea['usuario_asignado']['nombre'])): ?>
                    <p><?= htmlspecialchars($tarea['usuario_asignado']['nombre']) ?></p>
                <?php else: ?>
                    <p class="text-muted">No asignado</p>
                    <button class="btn btn-sm btn-outline-primary" id="btnAssignUser">
                        <i class="fas fa-user-plus me-1"></i> Asignar
                    </button>
                <?php endif; ?>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-info-circle me-2"></i> Información</h5>
            </div>
            <div class="card-body">
                <p><strong>Creado:</strong> <?= $fechaCreacion ?></p>
                <p><strong>Último cambio:</strong> 
                   <?= isset($tarea['updatedAt']) ? date('d/m/Y H:i', strtotime($tarea['updatedAt'])) : 'N/A' ?>
                </p>
            </div>
        </div>
        
        <div class="card mb-3">
            <div class="card-header">
                <h5 class="m-0"><i class="fas fa-cog me-2"></i> Acciones</h5>
            </div>
            <div class="card-body">
                <div class="d-grid gap-2">
                    <button class="btn btn-outline-primary btn-sm" id="btnEditTask">
                        <i class="fas fa-edit me-1"></i> Editar
                    </button>
                    <button class="btn btn-outline-danger btn-sm" id="btnDeleteTask">
                        <i class="fas fa-trash-alt me-1"></i> Eliminar
                    </button>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
    // Manejar el envío del formulario de comentarios con AJAX
    document.getElementById('commentForm').addEventListener('submit', function(e) {
        e.preventDefault();
        const formData = new FormData(this);
        
        fetch(this.action, {
            method: 'POST',
            body: new URLSearchParams(formData)
        })
        .then(response => response.json())
        .then(data => {
            if (data.ok) {
                // Recargar los detalles de la tarea para mostrar el nuevo comentario
                fetch(`ajax/obtener_tarea.php?id=<?= $id ?>`)
                    .then(response => response.text())
                    .then(html => {
                        document.getElementById('taskDetailsContent').innerHTML = html;
                    });
            } else {
                alert('Error al añadir el comentario: ' + data.error);
            }
        })
        .catch(error => {
            console.error('Error:', error);
            alert('Error al añadir el comentario');
        });
    });

    // Event listeners para botones de acciones
    document.getElementById('btnEditTask')?.addEventListener('click', function() {
        // Implementar edición de tarea
        alert('Función de edición de tarea en desarrollo');
    });
    
    document.getElementById('btnDeleteTask')?.addEventListener('click', function() {
        if (confirm('¿Estás seguro de que quieres eliminar esta tarea?')) {
            fetch('ajax/eliminar_tarea.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/x-www-form-urlencoded',
                },
                body: `id=<?= $tarea['_id'] ?>`
            })
            .then(response => response.json())
            .then(data => {
                if (data.ok) {
                    document.getElementById('taskDetailsModal').style.display = 'none';
                    // Recargar la página para actualizar la lista de tareas
                    window.location.reload();
                } else {
                    alert('Error al eliminar la tarea: ' + data.error);
                }
            });
        }
    });
    
    document.getElementById('btnAssignUser')?.addEventListener('click', function() {
        // Implementar asignación de usuario
        alert('Función de asignación de usuario en desarrollo');
    });
</script>