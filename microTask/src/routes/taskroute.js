// Reemplaza completamente el archivo taskroute.js con esta versión
const express = require('express');
const router = express.Router();
const {
  crearTarea,
  asignarUsuarioATarea,
  obtenerTarea,
  actualizarTarea,
  actualizarEstadoTarea,
  eliminarTarea,
  agregarNotificacion,
  marcarNotificacionComoLeida,
  obtenerTareasPorProyecto,
  obtenerTareasPorUsuarioYProyecto,
} = require('../controllers/taskcontroller');

// Rutas para tareas
router.post('/', crearTarea);
router.put('/:idTarea/asignar', asignarUsuarioATarea);
router.get('/:titulo', obtenerTarea);
router.get('/id/:id', obtenerTarea);

// NUEVA RUTA: Actualizar tarea solo por ID (más simple y segura)
router.put('/updateById/:id', actualizarTarea);

// IMPORTANTE: Mantener esta ruta por compatibilidad, pero ahora usa el ID y ignora el título
router.put('/update/:titulo/:id', actualizarTarea);

// Ruta específica para actualizar solo el estado
router.put('/updateState/:id', actualizarEstadoTarea);

router.delete('/delete/:titulo/:id', eliminarTarea);
router.get('/todos/project/id/:idProyecto', obtenerTareasPorProyecto);
router.get('/proyecto/:idProyecto', obtenerTareasPorProyecto);
router.get('/proyecto/:idProyecto/usuario/:emailUsuario', obtenerTareasPorUsuarioYProyecto);
router.get('/proyecto/:idProyecto/mis-tareas', obtenerTareasPorUsuarioYProyecto);
router.post('/:id/notificaciones', agregarNotificacion);  
router.put('/:titulo/notificaciones/:notificacionId', marcarNotificacionComoLeida);

module.exports = router;