const mongoose = require('mongoose');
const Tarea = require('../models/taskmodel');
const axios = require('axios');

// Crear Tarea
const crearTarea = async (req, res) => {
  try {
    // Extraemos datos de la solicitud - soportamos ambos enfoques
    // 1. Usando req.usuario.id (autenticación por middleware)
    // 2. Usando req.body.userId (envío explícito)
    const userId = req.usuario?.id || req.body.userId;
    const { proyecto_id, ...datosTarea } = req.body;

    // Validación básica
    if (!userId || !proyecto_id) {
      return res.status(400).json({
        ok: false,
        error: 'Se requiere el ID del usuario y el ID del proyecto'
      });
    }

    // Validar si el usuario es el creador del proyecto
    let proyecto;
    try {
      const respuesta = await axios.get(`http://micro_projects:3008/api/project/id/${proyecto_id}`);
      proyecto = respuesta.data.proyecto;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroProjects'
      });
    }

    if (!proyecto) {
      return res.status(404).json({ ok: false, error: 'Proyecto no encontrado' });
    }

    // Comparar correctamente los IDs
    if (String(proyecto.creador?._id) !== String(userId)) {
      return res.status(403).json({
        ok: false,
        error: 'No tienes los permisos necesarios para crear una tarea. Solo el creador del proyecto puede hacerlo'
      });
    }

    // Crear la tarea
    const nuevaTarea = new Tarea({
      ...datosTarea,
      proyecto_id
    });

    await nuevaTarea.save();

    res.status(201).json({
      ok: true,
      mensaje: 'Tarea creada con éxito',
      tarea: nuevaTarea
    });
  } catch (err) {
    console.error('Error en crearTarea:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Asignar Usuario a Tarea
const asignarUsuarioATarea = async (req, res) => {
  try {
    const { idTarea } = req.params;
    const { email, userId } = req.body;

    if (!idTarea || !email) {
      return res.status(400).json({
        ok: false,
        error: 'El id de la tarea y el email son obligatorios'
      });
    }

    // Obtener userId del middleware o del cuerpo de la solicitud
    const usuarioId = req.usuario?.id || userId;
    
    if (!usuarioId) {
      return res.status(400).json({
        ok: false,
        error: 'Se requiere autenticación o userId en el cuerpo de la solicitud'
      });
    }

    // 1. Buscar la tarea por ID
    const tarea = await Tarea.findById(idTarea);
    if (!tarea) {
      return res.status(404).json({ ok: false, error: 'Tarea no encontrada' });
    }

    // 2. Buscar el proyecto al que pertenece la tarea
    let proyecto;
    try {
      const respuestaProyecto = await axios.get(`http://micro_projects:3008/api/project/id/${tarea.proyecto_id}`);
      proyecto = respuestaProyecto.data.proyecto;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroProjects'
      });
    }

    if (!proyecto) {
      return res.status(404).json({
        ok: false,
        error: 'Proyecto no encontrado'
      });
    }

    // 3. Validar que el userId sea el creador del proyecto
    if (String(proyecto.creador._id) !== String(usuarioId)) {
      return res.status(403).json({
        ok: false,
        error: 'No tienes permisos para asignar usuarios a esta tarea'
      });
    }

    // 4. Buscar el usuario a asignar por email
    let usuario;
    try {
      const usuarioRespuesta = await axios.get(`http://micro_user:3009/api/user/buscar?email=${email}`);
      usuario = usuarioRespuesta.data.usuario;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroUser'
      });
    }

    if (!usuario) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }

    // 5. Asignar usuario a la tarea
    tarea.usuario_asignado = usuario._id;
    await tarea.save();

    res.status(200).json({
      ok: true,
      mensaje: `Usuario ${usuario.nombre} asignado a la tarea "${tarea.titulo}"`,
      tarea
    });
  } catch (err) {
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Obtener Tarea por Título o ID
const obtenerTarea = async (req, res) => {
  try {
    const { titulo, id } = req.params;  // Recibimos tanto el título como el ID

    if (!titulo && !id) {
      return res.status(400).json({ ok: false, error: 'El título o el ID de la tarea son obligatorios' });
    }

    let tarea;

    // Si se pasa el ID, buscamos por ID
    if (id) {
      tarea = await Tarea.findById(id);
    } 
    // Si se pasa el título, buscamos por título
    else if (titulo) {
      tarea = await Tarea.findOne({ titulo });
    }

    if (!tarea) {
      return res.status(404).json({ ok: false, error: 'Tarea no encontrada' });
    }

    // Obtener usuario asignado desde MicroUser
    let usuarioAsignado = null;
    try {
      const respuestaUsuario = await axios.get(`http://micro_user:3009/api/user/buscar?id=${tarea.usuario_asignado}`);
      usuarioAsignado = respuestaUsuario.data.usuario;
    } catch (error) {
      console.error(`Error obteniendo usuario asignado: ${error.message}`);
    }

    // Obtener proyecto relacionado desde MicroProjects
    let proyecto = null;
    try {
      const respuestaProyecto = await axios.get(`http://micro_projects:3008/api/project/id/${tarea.proyecto_id}`);
      proyecto = respuestaProyecto.data.proyecto;
    } catch (error) {
      console.error(`Error obteniendo proyecto: ${error.message}`);
    }

    res.status(200).json({
      ok: true,
      tarea: {
        ...tarea._doc,
        usuario_asignado: usuarioAsignado,
        proyecto,
      },
    });
  } catch (err) {
    console.error('Error en obtenerTarea:', err.message);
    res.status(500).json({ ok: false, error: 'Error interno del servidor al obtener la tarea' });
  }
};

// Actualizar Tarea
const actualizarTarea = async (req, res) => {
  try {
    const { titulo, id } = req.params;

    // Verificar si estamos actualizando solo por ID (mejor enfoque)
    const usingUpdateByIdRoute = !titulo && id;
    
    // Validar los parámetros según la ruta
    if (usingUpdateByIdRoute && !id) {
      return res.status(400).json({ ok: false, error: 'El ID de la tarea es obligatorio' });
    }
    
    if (!usingUpdateByIdRoute && (!titulo || !id)) {
      return res.status(400).json({ ok: false, error: 'El título y el ID de la tarea son obligatorios' });
    }

    // Primero, obtener la tarea actual para asegurarnos de que existe
    let tareaActual;
    try {
      tareaActual = await Tarea.findById(id);
      if (!tareaActual) {
        return res.status(404).json({ 
          ok: false, 
          error: 'Tarea no encontrada con ese ID'
        });
      }
    } catch (findErr) {
      console.error('Error al buscar la tarea actual:', findErr);
      return res.status(500).json({ ok: false, error: 'Error al buscar la tarea actual' });
    }

    // Crear un objeto de actualización que preserve los campos requeridos
    const updateData = {};
    
    // Lista de campos que se pueden actualizar
    const allowedFields = ['titulo', 'descripcion', 'estado', 'prioridad', 'fecha_limite', 'usuario_asignado'];
    
    // Copiar solo los campos permitidos desde req.body
    allowedFields.forEach(field => {
      if (req.body[field] !== undefined) {
        updateData[field] = req.body[field];
      }
    });

    // Asegurarse de que el campo proyecto_id siempre esté presente
    if (!updateData.proyecto_id && tareaActual.proyecto_id) {
      updateData.proyecto_id = tareaActual.proyecto_id;
    }

    // Realizar la actualización
    let tareaActualizada;
    try {
      if (usingUpdateByIdRoute) {
        // Si solo usamos ID, usar findByIdAndUpdate
        tareaActualizada = await Tarea.findByIdAndUpdate(
          id,
          { $set: updateData },
          { new: true }
        );
      } else {
        // Si usamos título e ID, usar findOneAndUpdate
        tareaActualizada = await Tarea.findOneAndUpdate(
          { _id: id, titulo },
          { $set: updateData },
          { new: true }
        );
      }
    } catch (updateErr) {
      console.error('Error al actualizar la tarea:', updateErr);
      return res.status(500).json({ ok: false, error: 'Error al actualizar la tarea: ' + updateErr.message });
    }

    if (!tareaActualizada) {
      return res.status(404).json({ 
        ok: false, 
        error: 'No se pudo actualizar la tarea'
      });
    }

    res.status(200).json({
      ok: true,
      tarea: tareaActualizada
    });
  } catch (err) {
    console.error('Error en actualizarTarea:', err);
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Actualizar solo el estado de una tarea
const actualizarEstadoTarea = async (req, res) => {
  try {
    const { id } = req.params;
    const { estado } = req.body;

    // Validaciones
    if (!id) {
      return res.status(400).json({ ok: false, error: 'El ID de la tarea es obligatorio' });
    }

    if (!estado) {
      return res.status(400).json({ ok: false, error: 'El estado es obligatorio' });
    }

    // Verificar que el estado sea válido
    if (!['Por hacer', 'En progreso', 'Hecho'].includes(estado)) {
      return res.status(400).json({ 
        ok: false, 
        error: 'Estado no válido. Debe ser: Por hacer, En progreso o Hecho' 
      });
    }

    // Buscar y actualizar la tarea solo con el nuevo estado
    const tareaActualizada = await Tarea.findByIdAndUpdate(
      id,
      { estado },
      { new: true }
    );

    if (!tareaActualizada) {
      return res.status(404).json({ ok: false, error: 'Tarea no encontrada' });
    }

    // Responder con éxito
    res.status(200).json({
      ok: true,
      mensaje: `Estado actualizado a "${estado}"`,
      tarea: tareaActualizada
    });
  } catch (err) {
    console.error('Error en actualizarEstadoTarea:', err);
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Eliminar Tarea
const eliminarTarea = async (req, res) => {
  try {
    // Soportamos dos enfoques: userId del middleware o del body
    const userId = req.usuario?.id || req.body.userId;
    const { titulo, id } = req.params;

    if (!userId || !id) {
      return res.status(400).json({
        ok: false,
        error: 'Se requiere el ID del usuario y el ID de la tarea'
      });
    }

    // Buscar la tarea por ID
    const tarea = await Tarea.findById(id);
    if (!tarea) {
      return res.status(404).json({
        ok: false,
        error: 'Tarea no encontrada'
      });
    }

    // Obtener el proyecto relacionado para validar permisos
    let proyecto;
    try {
      const respuesta = await axios.get(`http://micro_projects:3008/api/project/id/${tarea.proyecto_id}`);
      proyecto = respuesta.data.proyecto;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroProjects'
      });
    }

    // Verificar si el usuario es el creador del proyecto
    if (!proyecto || String(proyecto.creador._id) !== String(userId)) {
      return res.status(403).json({
        ok: false,
        error: 'No tienes permisos para eliminar esta tarea. Solo el creador del proyecto puede hacerlo.'
      });
    }

    // Eliminar la tarea
    await Tarea.findByIdAndDelete(id);

    res.status(200).json({
      ok: true,
      mensaje: 'Tarea eliminada con éxito'
    });
  } catch (err) {
    console.error('Error en eliminarTarea:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Obtener Todas las Tareas de un Proyecto
const obtenerTareasPorProyecto = async (req, res) => {
  try {
    const { idProyecto } = req.params;

    if (!idProyecto) {
      return res.status(400).json({ ok: false, error: 'El ID del proyecto es obligatorio' });
    }

    // Buscar el proyecto en MicroProjects usando el ID
    let proyecto;
    try {
      const respuesta = await axios.get(`http://micro_projects:3008/api/project/id/${idProyecto}`);
      proyecto = respuesta.data.proyecto;
    } catch (error) {
      console.error('Error al comunicarse con MicroProjects:', error.message);
      return res.status(500).json({ ok: false, error: 'Error al comunicarse con MicroProjects' });
    }

    if (!proyecto) {
      return res.status(404).json({ ok: false, error: 'Proyecto no encontrado' });
    }

    // Buscar tareas relacionadas con el proyecto
    const tareas = await Tarea.find({ proyecto_id: proyecto._id });

    // No devolvemos error si no hay tareas, solo un array vacío
    res.status(200).json({ 
      ok: true, 
      tareas,
      mensaje: tareas.length > 0 ? `${tareas.length} tareas encontradas` : 'No hay tareas para este proyecto'
    });
  } catch (err) {
    console.error('Error en obtenerTareasPorProyecto:', err.message);
    res.status(500).json({ ok: false, error: 'Error al obtener las tareas del proyecto' });
  }
};

// Obtener Todas las Tareas de un Usuario en un Proyecto
const obtenerTareasPorUsuarioYProyecto = async (req, res) => {
  try {
    const { idProyecto, emailUsuario } = req.params;

    // Si no se pasa `emailUsuario`, usa el del usuario logueado (req.usuario)
    const email = emailUsuario || req.usuario?.email;

    if (!email || !idProyecto) {
      return res.status(400).json({ ok: false, error: 'El email y el ID del proyecto son obligatorios' });
    }

    // Buscar usuario por email
    let usuario;
    try {
      const respuestaUsuario = await axios.get(`http://micro_user:3009/api/user/buscar?email=${email}`);
      usuario = respuestaUsuario.data.usuario;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroUser'
      });
    }

    if (!usuario) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }

    // Buscar proyecto por ID
    let proyecto;
    try {
      const respuestaProyecto = await axios.get(`http://micro_projects:3008/api/project/id/${idProyecto}`);
      proyecto = respuestaProyecto.data.proyecto;
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroProjects'
      });
    }

    if (!proyecto) {
      return res.status(404).json({ ok: false, error: 'Proyecto no encontrado' });
    }

    // Buscar tareas relacionadas con el proyecto y el usuario
    const tareas = await Tarea.find({
      proyecto_id: proyecto._id,
      usuario_asignado: usuario._id
    });

    // No devolvemos error si no hay tareas, solo un array vacío
    res.status(200).json({ 
      ok: true, 
      tareas,
      mensaje: tareas.length > 0 ? `${tareas.length} tareas encontradas` : 'No hay tareas para este usuario en el proyecto'
    });
  } catch (err) {
    console.error('Error en obtenerTareasPorUsuarioYProyecto:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
};

// Eliminar todas las tareas por ID de proyecto
const eliminarTareasPorProyecto = async (req, res) => {
  try {
    const { id } = req.params;
    const userId = req.usuario?.id || req.body.userId;

    if (!id) {
      return res.status(400).json({
        ok: false,
        error: 'El ID del proyecto es obligatorio'
      });
    }

    if (!userId) {
      return res.status(400).json({
        ok: false,
        error: 'Se requiere autenticación o userId en el cuerpo de la solicitud'
      });
    }

    // Verificar si el usuario es el creador del proyecto
    let proyecto;
    try {
      const respuesta = await axios.get(`http://micro_projects:3008/api/project/id/${id}`);
      proyecto = respuesta.data.proyecto;
      
      if (!proyecto) {
        return res.status(404).json({ ok: false, error: 'Proyecto no encontrado' });
      }

      if (String(proyecto.creador._id) !== String(userId)) {
        return res.status(403).json({
          ok: false,
          error: 'No tienes permisos para eliminar las tareas de este proyecto'
        });
      }
    } catch (error) {
      return res.status(500).json({
        ok: false,
        error: 'Error al comunicarse con MicroProjects'
      });
    }

    const resultado = await Tarea.deleteMany({ proyecto_id: id });

    res.status(200).json({
      ok: true,
      mensaje: `Se eliminaron ${resultado.deletedCount} tareas asociadas al proyecto`
    });
  } catch (err) {
    res.status(500).json({
      ok: false,
      error: 'Error al eliminar tareas del proyecto: ' + err.message
    });
  }
};

// Agregar Notificación a Tarea
const agregarNotificacion = async (req, res) => {
  try {
    const { id } = req.params;
    const { usuario_id, contenido } = req.body;

    if (!usuario_id || !contenido) {
      return res.status(400).json({ ok: false, error: 'El usuario_id y el contenido son obligatorios' });
    }

    // Validar que el usuario exista en el MicroUser
    try {
      const usuarioRespuesta = await axios.get(`http://micro_user:3009/api/user/buscar?id=${usuario_id}`);
      if (!usuarioRespuesta.data.usuario) {
        return res.status(404).json({ ok: false, error: 'Usuario no encontrado en MicroUser' });
      }
    } catch (error) {
      return res.status(500).json({ ok: false, error: 'Error al comunicarse con MicroUser' });
    }

    // Buscar la tarea por su ID
    const tarea = await Tarea.findById(id);
    if (!tarea) {
      return res.status(404).json({ ok: false, error: 'Tarea no encontrada' });
    }

    // Agregar la notificación a la tarea
    tarea.notificaciones.push({ usuario_id, contenido });
    await tarea.save();

    res.status(200).json({
      ok: true,
      mensaje: 'Notificación agregada a la tarea',
      tarea
    });
  } catch (err) {
    console.error('Error en agregarNotificacion:', err.message);
    res.status(500).json({ ok: false, error: 'Error al agregar la notificación' });
  }
};

// Marcar Notificación como Leída
const marcarNotificacionComoLeida = async (req, res) => {
  try {
    const { titulo, notificacionId } = req.params;

    // Buscar la tarea por título
    const tarea = await Tarea.findOne({ titulo });
    if (!tarea) {
      return res.status(404).json({ ok: false, error: 'Tarea no encontrada' });
    }

    // Buscar la notificación en la tarea
    const notificacion = tarea.notificaciones.id(notificacionId);
    if (!notificacion) {
      return res.status(404).json({ ok: false, error: 'Notificación no encontrada' });
    }

    // Marcar la notificación como vista
    notificacion.visto = true;
    await tarea.save();

    res.status(200).json({ 
      ok: true, 
      mensaje: 'Notificación marcada como leída',
      tarea 
    });
  } catch (err) {
    console.error('Error en marcarNotificacionComoLeida:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
};

module.exports = {
  crearTarea,
  obtenerTarea,
  asignarUsuarioATarea,
  actualizarTarea,
  actualizarEstadoTarea,
  eliminarTarea,
  agregarNotificacion,
  marcarNotificacionComoLeida,
  obtenerTareasPorProyecto,
  obtenerTareasPorUsuarioYProyecto,
  eliminarTareasPorProyecto
};