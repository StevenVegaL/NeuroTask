const express = require('express');
const router = express.Router();
const {
  crearMensaje,
  obtenerMensajesPorTarea,
  eliminarMensaje,
  actualizarMensaje
} = require('../controllers/mensajescontroller');

// Crear un mensaje
router.post('/', crearMensaje);

// Obtener todos los mensajes de una tarea específica
router.get('/tarea/:tarea_id', obtenerMensajesPorTarea);

// Eliminar un mensaje por ID
router.delete('/:mensaje_id', eliminarMensaje);

// Actualizar un mensaje por ID
router.put('/:mensaje_id', actualizarMensaje);

module.exports = router;

