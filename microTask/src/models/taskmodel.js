const { Schema, model } = require('mongoose');

const tareaSchema = new Schema(
  {
    proyecto_id: {
      type: Schema.Types.ObjectId,
      ref: 'Proyecto',
      required: true,
      description: 'ID del proyecto al que pertenece esta tarea'
    },
    titulo: {
      type: String,
      required: true,
      description: 'Título descriptivo de la tarea'
    },
    descripcion: {
      type: String,
      description: 'Descripción detallada de la tarea'
    },
    estado: {
      type: String,
      enum: ['Por hacer', 'En progreso', 'Hecho'],
      required: true,
      default: 'Por hacer'
    },
    prioridad: {
      type: String,
      enum: ['Alta', 'Media', 'Baja'],
      required: true,
      description: 'Nivel de prioridad de la tarea'
    },
    fecha_limite: {
      type: Date,
      required: false, // Ahora es opcional
      description: 'Fecha límite para completar la tarea (Opcional)'
    },
    usuario_asignado: {
      type: Schema.Types.ObjectId,
      ref: 'Usuario',
      required: false, // Ahora es opcional
      description: 'ID del usuario al que se asigna esta tarea (Opcional)'
    },
    notificaciones: [
      {
        usuario_id: { type: Schema.Types.ObjectId, ref: 'Usuario' },
        contenido: { type: String, required: true },
        visto: { type: Boolean, default: false }
      }
    ]
  },
  {
    collection: 'tareas',
    timestamps: true // Agrega automáticamente createdAt y updatedAt
  }
);

module.exports = model('Tarea', tareaSchema);
