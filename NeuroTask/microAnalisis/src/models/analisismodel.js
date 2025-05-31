import { Schema, model } from 'mongoose';

const analisisUsuarioSchema = new Schema({
  usuario_id: {
    type: Schema.Types.ObjectId,
    ref: 'Usuario',
    required: true// Cambiado a false para evitar errores si viene vacío
  },
  tareas_completadas: { type: Number, default: 0 },
  tareas_pendientes: { type: Number, default: 0 },
  tareas_en_progreso: { type: Number, default: 0 },
  tareas_completadas_mes: { type: Number, default: 0 },
  promedio_tiempo_horas: { type: Number, default: 0 },
  total_asignadas: { type: Number, default: 0 },
  total_completadas: { type: Number, default: 0 },
  tareas_activas: { type: Number, default: 0 },
  tareas_en_progreso_actual: { type: Number, default: 0 },
  tareas_pendientes_por_prioridad: {
    alta: { type: Number, default: 0 },
    media: { type: Number, default: 0 },
    baja: { type: Number, default: 0 }
  },
  eficiencia_usuario: { type: Number, default: 0 },
  tareas_con_retraso: { type: Number, default: 0 },
  tareas_entregadas_a_tiempo: { type: Number, default: 0 },
  carga_critica: { type: Number, default: 0 }
}, {
  collection: 'analisis_usuario',
  timestamps: true
});

export default model('AnalisisUsuario', analisisUsuarioSchema);
