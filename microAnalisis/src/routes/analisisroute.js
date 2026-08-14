import express from 'express';
import analisisController from '../controllers/analisiscontroller.js';

const router = express.Router();

router.get('/:usuario_id', analisisController.obtenerAnalisisPorUsuario);

export default router;
