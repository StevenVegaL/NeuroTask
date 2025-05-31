import AnalisisUsuario from '../models/analisismodel.js';

// Obtener análisis completo por usuario_id
const obtenerAnalisisPorUsuario = async (req, res) => {
  try {
    const { usuario_id } = req.params;

    if (!usuario_id) {
      return res.status(400).json({
        ok: false,
        error: "Debe proporcionar el usuario_id"
      });
    }

    const analisis = await AnalisisUsuario.findOne({ usuario_id });

    if (!analisis) {
      return res.status(404).json({
        ok: false,
        error: "No se encontró análisis para el usuario"
      });
    }

    res.status(200).json({
      ok: true,
      analisis
    });
  } catch (error) {
    res.status(500).json({
      ok: false,
      error: error.message
    });
  }
};

export default {
  obtenerAnalisisPorUsuario
};
