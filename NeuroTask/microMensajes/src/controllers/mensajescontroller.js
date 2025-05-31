const Mensaje = require('../model/mensajesmodel');
const axios = require('axios');

const crearMensaje = async (req, res) => {
  try {
    const { tarea_id, usuario_id, contenido } = req.body;

    // Validar que se envíen todos los datos requeridos
    if (!tarea_id || !usuario_id || !contenido) {
      return res.status(400).json({ ok: false, error: 'Todos los campos (tarea_id, usuario_id, contenido) son obligatorios' });
    }

    // Validar que la tarea existe en MicroTask
    let tareaRespuesta;
    try {
      tareaRespuesta = await axios.get(`http://micro_task:3007/api/task/id/${tarea_id}`);
    } catch (error) {
      console.error('Error al comunicarse con MicroTask:', error.message);
      return res.status(500).json({ ok: false, error: 'Error al comunicarse con MicroTask' });
    }
    if (!tareaRespuesta.data.tarea) {
      return res.status(404).json({ ok: false, error: 'La tarea no existe' });
    }

    // Validar que el usuario existe en MicroUser
    let usuarioRespuesta;
    try {
      usuarioRespuesta = await axios.get(`http://micro_user:3009/api/user/buscar?id=${usuario_id}`);
    } catch (error) {
      console.error('Error al comunicarse con MicroUser:', error.message);
      return res.status(500).json({ ok: false, error: 'Error al comunicarse con MicroUser' });
    }
    if (!usuarioRespuesta.data.usuario) {
      return res.status(404).json({ ok: false, error: 'El usuario no existe' });
    }

    // Crear el mensaje en MicroMessages
    const nuevoMensaje = new Mensaje({ tarea_id, usuario_id, contenido });
    await nuevoMensaje.save();

    res.status(201).json({ ok: true, mensaje: nuevoMensaje });
  } catch (err) {
    console.error('Error en crearMensaje:', err.message);
    res.status(500).json({ ok: false, error: err.message });
  }
};


const obtenerMensajesPorTarea = async (req, res) => {
  try {
    const { tarea_id } = req.params;

    if (!tarea_id) {
      return res.status(400).json({ ok: false, error: 'El ID de la tarea es obligatorio' });
    }

    // Buscar todos los mensajes asociados a la tarea
    const mensajes = await Mensaje.find({ tarea_id });

    if (mensajes.length === 0) {
      return res.status(404).json({ ok: false, error: 'No se encontraron mensajes para esta tarea' });
    }

    // Para cada mensaje, obtener detalles del usuario desde MicroUser
    const mensajesConUsuarios = await Promise.all(
      mensajes.map(async (mensaje) => {
        try {
          const respuestaUsuario = await axios.get(`http://micro_user:3009/api/user/buscar?id=${mensaje.usuario_id}`);
          const usuario = respuestaUsuario.data.usuario;
          return {
            ...mensaje._doc,
            usuario: usuario ? { nombre: usuario.nombre, email: usuario.email } : null
          };
        } catch (error) {
          console.error(`Error obteniendo usuario ${mensaje.usuario_id}:`, error.message);
          return {
            ...mensaje._doc,
            usuario: null
          };
        }
      })
    );

    res.status(200).json({ ok: true, mensajes: mensajesConUsuarios });
  } catch (err) {
    console.error('Error en obtenerMensajesPorTarea:', err.message);
    res.status(500).json({ ok: false, error: 'Error al obtener los mensajes de la tarea' });
  }
};



const eliminarMensaje = async (req, res) => {
  try {
    const { mensaje_id } = req.params;

    if (!mensaje_id) {
      return res.status(400).json({ ok: false, error: 'El ID del mensaje es obligatorio' });
    }

    const mensajeEliminado = await Mensaje.findByIdAndDelete(mensaje_id);

    if (!mensajeEliminado) {
      return res.status(404).json({ ok: false, error: 'Mensaje no encontrado' });
    }

    res.status(200).json({ ok: true, mensaje: 'Mensaje eliminado correctamente' });
  } catch (err) {
    console.error('Error en eliminarMensaje:', err.message);
    res.status(500).json({ ok: false, error: 'Error al eliminar el mensaje' });
  }
};



const actualizarMensaje = async (req, res) => {
  try {
    const { mensaje_id } = req.params;
    const { contenido } = req.body;

    if (!mensaje_id) {
      return res.status(400).json({ ok: false, error: 'El ID del mensaje es obligatorio' });
    }

    if (!contenido) {
      return res.status(400).json({ ok: false, error: 'El nuevo contenido es obligatorio' });
    }

    const mensajeActualizado = await Mensaje.findByIdAndUpdate(
      mensaje_id,
      { contenido },
      { new: true }
    );

    if (!mensajeActualizado) {
      return res.status(404).json({ ok: false, error: 'Mensaje no encontrado' });
    }

    res.status(200).json({ ok: true, mensaje: mensajeActualizado });
  } catch (err) {
    console.error('Error en actualizarMensaje:', err.message);
    res.status(500).json({ ok: false, error: 'Error al actualizar el mensaje' });
  }
};




module.exports = { 
  
  crearMensaje
, obtenerMensajesPorTarea
, eliminarMensaje
, actualizarMensaje

 };




