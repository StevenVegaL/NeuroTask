import express from 'express';
import userController from '../controllers/usercontroller.js';

const router = express.Router();

// Rutas para usuarios
router.post('/registro', userController.registrarUsuario);
router.post('/login', userController.autenticarUsuario);
router.get('/all', userController.obtenerTodosLosUsuarios);
router.get('/buscar', userController.obtenerUsuario);
router.put('/actualizar', userController.actualizarUsuario);
router.put('/admin-proyecto', userController.asignarProyectoAdministrado);
router.put('/asigna-proyecto', userController.asignarProyectoAsignado);
router.delete('/:id', userController.eliminarUsuario);

export default router;