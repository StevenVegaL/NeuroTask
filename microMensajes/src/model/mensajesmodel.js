const { Schema, model } = require('mongoose');

const mensajeSchema = new Schema(
  {
    tarea_id: {
      type: Schema.Types.ObjectId,
      ref: 'Tarea', // Opcional: referencia al modelo de Tareas si lo tienes
      required: true
    },
    usuario_id: {
      type: Schema.Types.ObjectId,
      ref: 'Usuario', // Opcional: referencia al modelo de Usuarios
      required: true
    },
    contenido: {
      type: String,
      required: true
    },
    timestamp: {
      type: Date,
      default: Date.now,
      required: true
    }
  },
  {
    collection: 'mensajes' // Especifica el nombre de la colección
  }
);

module.exports = model('Mensaje', mensajeSchema);
