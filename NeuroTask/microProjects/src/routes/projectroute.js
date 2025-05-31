const express = require('express');
const router = express.Router();
const {
    crearProyecto,
    obtenerProyectos,
    obtenerProyectoPorNombreOId,
    actualizarProyectoPorId,
    eliminarProyectoPorId,
    agregarMiembroPorEmail,
    eliminarMiembroPorEmail
    
} = require('../controllers/projectcontroller');

// Rutas para proyectos
router.post('/', crearProyecto);
router.get('/usuario/:usuarioId', obtenerProyectos);

router.get('/:nombre', obtenerProyectoPorNombreOId);  
router.get('/id/:id', obtenerProyectoPorNombreOId);  

router.put('/update/:id', actualizarProyectoPorId);
router.delete('/delete/:id', eliminarProyectoPorId);
router.post('/:nombre/miembros', agregarMiembroPorEmail);
router.delete('/:nombre/miembros/:email', eliminarMiembroPorEmail);



module.exports = router;

