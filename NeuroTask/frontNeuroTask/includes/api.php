<?php
// includes/api.php - Versión corregida completa (Sin notificaciones)

/**
 * Actualiza una tarea existente con manejo mejorado de errores y validación
 * 
 * @param string $taskId ID de la tarea a actualizar
 * @param array $data Datos a actualizar
 * @return array Respuesta de la API
 */
function updateTask($taskId, $data) {
    // Log para depuración
    error_log("Función updateTask - ID: " . $taskId . " - Datos de entrada: " . json_encode($data));

    // Verificar si el ID de la tarea es válido
    if (empty($taskId)) {
        return ['ok' => false, 'error' => 'ID de tarea inválido'];
    }

    // Usar la nueva ruta updateById
    $url = "http://192.168.100.3:3007/api/task/updateById/" . $taskId;
    
    // Preparar datos para envío - Eliminando valores null y vacíos
    $updateData = [];
    
    // Procesar todos los campos, omitiendo aquellos con valor null o vacío
    foreach ($data as $field => $value) {
        // Si el valor es null o cadena vacía, no incluirlo en la solicitud
        if ($value === null || $value === '') {
            error_log("Campo $field: OMITIDO (valor " . ($value === null ? "null" : "cadena vacía") . ")");
        } else {
            $updateData[$field] = $value;
            error_log("Campo $field: Incluido con valor " . json_encode($value));
        }
    }
    
    // Si no hay datos para actualizar, terminar
    if (empty($updateData)) {
        error_log("No hay campos para actualizar después del filtrado");
        return ['ok' => true, 'mensaje' => 'No hay cambios para actualizar'];
    }
    
    // Depuración exhaustiva antes de enviar
    error_log("Datos finales a enviar (JSON): " . json_encode($updateData, JSON_PRETTY_PRINT));
    
    // Intentar hacer la solicitud directamente usando cURL para mayor control
    $curl = curl_init($url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
    
    // Configurar Headers y JSON data
    $jsonData = json_encode($updateData);
    curl_setopt($curl, CURLOPT_POSTFIELDS, $jsonData);
    curl_setopt($curl, CURLOPT_HTTPHEADER, [
        'Content-Type: application/json',
        'Content-Length: ' . strlen($jsonData)
    ]);
    
    // Registrar la solicitud exacta que estamos enviando
    error_log("CURL solicitud - URL: $url");
    error_log("CURL solicitud - Método: PUT");
    error_log("CURL solicitud - Datos: $jsonData");
    
    // Ejecutar la solicitud
    $responseText = curl_exec($curl);
    $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $curlError = curl_error($curl);
    
    error_log("CURL respuesta - Código HTTP: $httpCode");
    error_log("CURL respuesta - Texto: $responseText");
    
    if ($curlError) {
        error_log("CURL error: $curlError");
        return ['ok' => false, 'error' => "Error de conexión: $curlError"];
    }
    
    curl_close($curl);
    
    // Decodificar respuesta
    $response = json_decode($responseText, true);
    
    // Si no podemos decodificar como JSON, devolver error con el texto de respuesta
    if ($response === null && json_last_error() !== JSON_ERROR_NONE) {
        error_log("Error al decodificar JSON: " . json_last_error_msg());
        error_log("Respuesta raw: $responseText");
        return [
            'ok' => false, 
            'error' => 'Error al decodificar respuesta: ' . json_last_error_msg(),
            'raw_response' => $responseText,
            'http_code' => $httpCode
        ];
    }
    
    return $response;
}
/**
 * Actualiza los datos de un usuario
 * 
 * @param string $userId ID del usuario a actualizar
 * @param array $data Datos a actualizar
 * @return array Respuesta de la API
 */
function updateUser($userId, $data) {
    // Log para depuración
    error_log("Función updateUser - ID: " . $userId . " - Datos: " . json_encode($data));

    // Verificar si el ID de usuario es válido
    if (empty($userId)) {
        return ['ok' => false, 'error' => 'ID de usuario inválido'];
    }

    // Construir los parámetros de la URL para la consulta
    $url = "http://192.168.100.3:3009/api/user/actualizar?id=" . $userId;
    
    // Log de la URL para depuración
    error_log("URL para actualizar usuario: " . $url);
    
    // Sanear datos sensibles
    $cleanData = $data;
    
    // Enviar la solicitud de actualización
    $response = callAPI("PUT", $url, $cleanData);
    
    // Log de la respuesta para depuración
    error_log("Respuesta de actualización de usuario: " . json_encode($response));
    
    return $response;
}

/**
 * Elimina una tarea específica
 * 
 * @param string $taskTitle Título de la tarea a eliminar
 * @param string $taskId ID de la tarea a eliminar
 * @return array Respuesta de la API
 */
function deleteTask($taskTitle, $taskId) {
    // Verificar si el ID de la tarea es válido
    if (empty($taskId)) {
        return ['ok' => false, 'error' => 'ID de tarea inválido'];
    }
    
    // Log para depuración
    error_log("Intentando eliminar tarea - ID: " . $taskId . ", Título: " . $taskTitle);
    
    // Construir la URL para la eliminación
    $url = "http://192.168.100.3:3007/api/task/delete/" . urlencode($taskTitle) . "/" . $taskId;
    
    error_log("URL para eliminar tarea: " . $url);
    
    // Enviar la solicitud de eliminación
    $response = callAPI("DELETE", $url);
    
    // Log de la respuesta para depuración
    error_log("Respuesta de eliminación de tarea: " . json_encode($response));
    
    return $response;
}

/**
 * Elimina la cuenta de un usuario.
 * 
 * @param string $userId ID del usuario a eliminar
 * @return array Respuesta de la API
 */
function deleteUser($userId) {
    return callAPI("DELETE", "http://192.168.100.3:3009/api/user/" . $userId);
}

/**
 * Elimina un proyecto específico
 * 
 * @param string $projectId ID del proyecto a eliminar
 * @return array Respuesta de la API
 */
function deleteProject($projectId) {
    // La ruta correcta según projectroute.js es /api/project/delete/:id
    return callAPI("DELETE", "http://192.168.100.3:3008/api/project/delete/" . $projectId);
}

/**
 * Crea un nuevo proyecto
 * 
 * @param string $nombre Nombre del proyecto
 * @param string $descripcion Descripción del proyecto
 * @param string $creador ID del usuario creador del proyecto
 * @param array $miembros Array de IDs de usuarios miembros del proyecto
 * @return array Respuesta de la API
 */
function createProject($nombre, $descripcion, $creador, $miembros = []) {
    // Validar parámetros
    if (empty($nombre)) {
        return [
            'ok' => false,
            'error' => 'El nombre del proyecto es obligatorio'
        ];
    }
    
    if (empty($creador)) {
        return [
            'ok' => false,
            'error' => 'El ID del creador es obligatorio'
        ];
    }
    
    // Preparar datos para la API
    $data = [
        "nombre" => $nombre,
        "descripcion" => $descripcion,
        "creador" => $creador,
        "miembros" => $miembros
    ];
    
    // Registrar la solicitud para depuración
    error_log("Enviando solicitud createProject: " . json_encode($data));
    
    // Enviar solicitud a la API
    $resultado = callAPI("POST", "http://192.168.100.3:3008/api/project/", $data);
    
    // Registrar la respuesta para depuración
    error_log("Respuesta de createProject: " . json_encode($resultado));
    
    return $resultado;
}

/**
 * Añade un miembro a un proyecto existente.
 * Función optimizada para manejar nombres de proyectos con caracteres especiales.
 * 
 * @param string $projectIdentifier ID o nombre del proyecto
 * @param string $email Email del usuario a añadir
 * @return array Respuesta de la API
 */
function addProjectMember($projectIdentifier, $email) {
    // Log para depuración
    error_log("Iniciando addProjectMember - Proyecto: " . $projectIdentifier . " - Email: " . $email);
    
    if (empty($projectIdentifier) || empty($email)) {
        error_log("Error en addProjectMember: Datos incompletos");
        return [
            'ok' => false,
            'error' => 'Se requiere el identificador del proyecto y el email del usuario'
        ];
    }
    
    // 1. Determinar si el identificador es un ID o un nombre
    $proyecto_id = null;
    $projectName = $projectIdentifier; // Usar el identificador como nombre por defecto
    
    // Verificar si es un ID de MongoDB (24 caracteres hexadecimales)
    if (preg_match('/^[0-9a-f]{24}$/', $projectIdentifier)) {
        error_log("El identificador parece ser un ID de MongoDB, buscando por ID");
        $proyecto_id = $projectIdentifier;
        $response = getProjectById($proyecto_id);
        if (isset($response['ok']) && $response['ok'] === true && isset($response['proyecto'])) {
            error_log("Proyecto encontrado por ID: " . $proyecto_id);
            $projectName = $response['proyecto']['nombre'];
        } else {
            error_log("No se encontró proyecto con ese ID: " . $proyecto_id);
            error_log("Continuaremos con el nombre proporcionado: " . $projectIdentifier);
        }
    } else {
        // Es un nombre de proyecto, intentaremos buscar su ID
        error_log("Buscando proyecto por nombre: " . $projectIdentifier);
        
        // Intentar con getProjectByName primero
        $proyectoResponse = getProjectByName($projectIdentifier);
        error_log("Resultado de búsqueda del proyecto por nombre exacto: " . json_encode($proyectoResponse));
        
        if (isset($proyectoResponse['ok']) && $proyectoResponse['ok'] === true && 
            isset($proyectoResponse['proyecto']) && isset($proyectoResponse['proyecto']['_id'])) {
            
            $proyecto_id = $proyectoResponse['proyecto']['_id'];
            $projectName = $proyectoResponse['proyecto']['nombre'];
            error_log("Proyecto encontrado por nombre exacto, usando ID: " . $proyecto_id);
        } else {
            // Intentar buscar en todos los proyectos (búsqueda insensible a mayúsculas/minúsculas)
            error_log("No se encontró el proyecto con nombre exacto, intentando búsqueda flexible");
            $allProjects = callAPI("GET", "http://192.168.100.3:3008/api/project/");
            
            if (isset($allProjects['ok']) && $allProjects['ok'] === true && isset($allProjects['proyectos'])) {
                error_log("Se encontraron " . count($allProjects['proyectos']) . " proyectos para comparar");
                
                foreach ($allProjects['proyectos'] as $p) {
                    // Comparar insensible a mayúsculas/minúsculas
                    if (strcasecmp($p['nombre'], $projectIdentifier) === 0) {
                        $proyecto_id = $p['_id'];
                        $projectName = $p['nombre'];
                        error_log("Proyecto encontrado en búsqueda flexible, ID: " . $proyecto_id);
                        break;
                    }
                    
                    // También comparar sin espacios y caracteres especiales
                    $cleanProjectId = preg_replace('/[^a-zA-Z0-9]/', '', $projectIdentifier);
                    $cleanProjName = preg_replace('/[^a-zA-Z0-9]/', '', $p['nombre']);
                    
                    if (strcasecmp($cleanProjName, $cleanProjectId) === 0) {
                        $proyecto_id = $p['_id'];
                        $projectName = $p['nombre'];
                        error_log("Proyecto encontrado en búsqueda sin caracteres especiales, ID: " . $proyecto_id);
                        break;
                    }
                }
            } else {
                error_log("No se pudo obtener la lista de proyectos o no hay proyectos");
            }
        }
    }
    
    // 2. Buscar el usuario por email para obtener su ID
    $userResponse = getUserByEmail($email);
    $usuario_id = null;
    
    if (isset($userResponse['ok']) && $userResponse['ok'] === true && isset($userResponse['usuario'])) {
        $usuario_id = $userResponse['usuario']['_id'];
        error_log("Usuario encontrado, ID: " . $usuario_id);
    } else {
        error_log("Usuario no encontrado con email: " . $email);
        return [
            'ok' => false,
            'error' => 'No se encontró un usuario con el email: ' . $email
        ];
    }
    
    // 3. Manejar caso especial si no encontramos el ID del proyecto pero tenemos el nombre
    if ($proyecto_id === null) {
        error_log("No se encontró un ID para el proyecto, pero continuaremos con el nombre: " . $projectName);
        // Intentar directamente con el nombre proporcionado
    }
    
    // 4. Preparar datos y URL para añadir miembro - usando la ruta correcta /:nombre/miembros
    $data = ["email" => $email];
    $url = "http://192.168.100.3:3008/api/project/" . urlencode($projectName) . "/miembros";
    
    error_log("URL para añadir miembro: " . $url);
    error_log("Datos: " . json_encode($data));
    
    // Realizar la llamada API
    $response = callAPI("POST", $url, $data);
    error_log("Respuesta de API para añadir miembro: " . json_encode($response));
    
    return $response;
}

/**
 * Función genérica para realizar peticiones HTTP mediante cURL.
 * Versión mejorada que maneja errores y encodings correctamente.
 */
function callAPI($method, $url, $data = false) {
    // Registrar la llamada para depuración
    error_log("callAPI: Iniciando $method request a $url");
    if ($data) {
        error_log("callAPI: Data enviada: " . json_encode($data, JSON_UNESCAPED_UNICODE));
    }
    
    $curl = curl_init();
    
    // Configuración común para todas las solicitudes
    curl_setopt($curl, CURLOPT_URL, $url);
    curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
    curl_setopt($curl, CURLOPT_CONNECTTIMEOUT, 5); // Timeout de conexión
    curl_setopt($curl, CURLOPT_TIMEOUT, 10);       // Timeout total
    
    // Configurar para manejar correctamente caracteres UTF-8
    curl_setopt($curl, CURLOPT_HTTPHEADER, array(
        'Content-Type: application/json; charset=UTF-8',
        'Accept: application/json'
    ));
    
    // Configuraciones específicas para cada método HTTP
    switch ($method) {
        case "POST":
            curl_setopt($curl, CURLOPT_POST, true);
            if ($data) {
                $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
                error_log("callAPI: POST data: $json_data");
            }
            break;
            
        case "PUT":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "PUT");
            if ($data) {
                $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
                error_log("callAPI: PUT data: $json_data");
            }
            break;
            
        case "DELETE":
            curl_setopt($curl, CURLOPT_CUSTOMREQUEST, "DELETE");
            // Si se necesita enviar datos en un DELETE
            if ($data) {
                $json_data = json_encode($data, JSON_UNESCAPED_UNICODE);
                curl_setopt($curl, CURLOPT_POSTFIELDS, $json_data);
                error_log("callAPI: DELETE data: $json_data");
            }
            break;
            
        default: // GET
            if ($data) {
                // Para GET, añadir parámetros a la URL
                $url = sprintf("%s?%s", $url, http_build_query($data));
                curl_setopt($curl, CURLOPT_URL, $url);
                error_log("callAPI: GET URL con parámetros: $url");
            }
    }

    // Ejecutar la solicitud
    error_log("callAPI: Ejecutando solicitud cURL");
    $response = curl_exec($curl);
    $http_code = curl_getinfo($curl, CURLINFO_HTTP_CODE);
    $content_type = curl_getinfo($curl, CURLINFO_CONTENT_TYPE);
    $curl_error = curl_error($curl);
    $curl_errno = curl_errno($curl);
    
    // Registrar información detallada de la respuesta
    error_log("callAPI: Código HTTP: $http_code");
    error_log("callAPI: Content-Type: $content_type");
    if ($curl_error) {
        error_log("callAPI: Error cURL: $curl_error (código: $curl_errno)");
    }
    
    // Mostrar fragmento de la respuesta para depuración
    $response_preview = substr($response, 0, 500);
    error_log("callAPI: Respuesta (primeros 500 caracteres): $response_preview");
    
    curl_close($curl);
    
    // Manejar errores de cURL
    if ($curl_error) {
        return [
            'ok' => false,
            'error' => "Error de conexión: $curl_error",
            'curl_errno' => $curl_errno
        ];
    }
    
    // Intentar decodificar la respuesta JSON
    $decoded = json_decode($response, true);
    $json_error = json_last_error();
    
    // Si ocurrió un error al decodificar JSON
    if ($json_error !== JSON_ERROR_NONE) {
        error_log("callAPI: Error al decodificar JSON: " . json_last_error_msg());
        error_log("callAPI: Respuesta completa: " . $response);
        
        // Si el código HTTP indica éxito pero la respuesta no es JSON válido
        if ($http_code >= 200 && $http_code < 300) {
            return [
                'ok' => false,
                'error' => 'Error al decodificar la respuesta: ' . json_last_error_msg(),
                'raw_response' => $response,
                'http_code' => $http_code
            ];
        } else {
            // Error HTTP + respuesta no-JSON
            return [
                'ok' => false,
                'error' => "Error HTTP $http_code: " . $response,
                'http_code' => $http_code
            ];
        }
    }
    
    // Si el código HTTP indica error, pero tenemos JSON válido
    if ($http_code >= 400) {
        error_log("callAPI: Error HTTP $http_code con JSON válido");
        return [
            'ok' => false,
            'error' => isset($decoded['error']) ? $decoded['error'] : "Error HTTP $http_code",
            'response' => $decoded,
            'http_code' => $http_code
        ];
    }
    
    // Respuesta exitosa con JSON válido
    return $decoded;
}

/* Endpoints de Usuarios (MicroUser en puerto 3009) */
function userLogin($email, $password) {
    $data = [
        "email" => $email,
        "contrasena" => $password
    ];
    return callAPI("POST", "http://192.168.100.3:3009/api/user/login", $data);
}

function userRegister($nombre, $email, $password) {
    $data = [
        "nombre" => $nombre,
        "email" => $email,
        "contrasena" => $password
    ];
    return callAPI("POST", "http://192.168.100.3:3009/api/user/registro", $data);
}

function getUserById($userId) {
    return callAPI("GET", "http://192.168.100.3:3009/api/user/buscar", ["id" => $userId]);
}

function getUserByEmail($email) {
    return callAPI("GET", "http://192.168.100.3:3009/api/user/buscar", ["email" => $email]);
}

function getAllUsers() {
    return callAPI("GET", "http://192.168.100.3:3009/api/user/todos");
}

/* Endpoints de Proyectos (MicroProjects en puerto 3008) */
function getUserProjects($userId) {
    return callAPI("GET", "http://192.168.100.3:3008/api/project/usuario/" . $userId);
}

function getProjectById($projectId) {
    return callAPI("GET", "http://192.168.100.3:3008/api/project/id/" . $projectId);
}

function getProjectByName($projectName) {
    return callAPI("GET", "http://192.168.100.3:3008/api/project/nombre/" . urlencode($projectName));
}

function updateProject($projectId, $data) {
    return callAPI("PUT", "http://192.168.100.3:3008/api/project/update/" . $projectId, $data);
}

function removeProjectMember($projectName, $email) {
    return callAPI("DELETE", "http://192.168.100.3:3008/api/project/miembro/" . urlencode($projectName) . "/" . urlencode($email));
}

/* Endpoints de Tareas (MicroTask en puerto 3007) */
function createTask($data) {
    return callAPI("POST", "http://192.168.100.3:3007/api/task/", $data);
}

function getTaskById($taskId) {
    return callAPI("GET", "http://192.168.100.3:3007/api/task/id/" . $taskId);
}

function getTasksByProject($projectId) {
    // Cambiar esta ruta para que coincida con la definida en taskroute.js
    return callAPI("GET", "http://192.168.100.3:3007/api/task/todos/project/id/" . $projectId);
}

function getTasksByUserAndProject($projectId, $userEmail) {
    return callAPI("GET", "http://192.168.100.3:3007/api/task/usuario-proyecto/" . $projectId . "/" . urlencode($userEmail));
}

function assignUserToTask($taskId, $email) {
    $data = ["email" => $email];
    return callAPI("POST", "http://192.168.100.3:3007/api/task/asignar/" . $taskId, $data);
}

function addTaskNotification($taskId, $userId, $content) {
    $data = [
        "usuario_id" => $userId,
        "contenido" => $content
    ];
    return callAPI("POST", "http://192.168.100.3:3007/api/task/notificacion/" . $taskId, $data);
}

/* Endpoints de Mensajes (MicroMessages en puerto 3010) */
function createMessage($tarea_id, $usuario_id, $contenido) {
    $data = [
        "tarea_id" => $tarea_id,
        "usuario_id" => $usuario_id,
        "contenido" => $contenido
    ];
    return callAPI("POST", "http://192.168.100.3:3010/api/mensajes/", $data);
}

function getMessagesByTask($taskId) {
    return callAPI("GET", "http://192.168.100.3:3010/api/mensajes/tarea/" . $taskId);
}

function updateMessage($messageId, $content) {
    $data = ["contenido" => $content];
    return callAPI("PUT", "http://192.168.100.3:3010/api/mensajes/" . $messageId, $data);
}

function deleteMessage($messageId) {
    return callAPI("DELETE", "http://192.168.100.3:3010/api/mensajes/" . $messageId);
}
?>