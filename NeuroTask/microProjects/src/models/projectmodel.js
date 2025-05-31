const { Schema, model } = require('mongoose');

const proyectoSchema = new Schema(
  {
    nombre: {
      type: String,
      required: true,
      description: 'Nombre del proyecto'
    },
    descripcion: {
      type: String,
      required: true,
      description: 'Descripción breve del proyecto'
    },
    creador: {
      type: Schema.Types.ObjectId,
      ref: 'Usuario',
      required: true,
      description: 'ID del usuario que creó el proyecto (Administrador)'
    },
    miembros: [
      {
        type: Schema.Types.ObjectId,
        ref: 'Usuario',
        description: 'IDs de los usuarios miembros del proyecto'
      }
    ],
    fecha_creacion: {
      type: Date,
      default: Date.now,
      description: 'Fecha en que se creó el proyecto'
    },
    estado: {
      type: String,
      enum: ['Activo', 'Inactivo'],
      default: 'Activo',
      description: 'Estado actual del proyecto'
    }
  },
  {
    collection: 'proyectos', // Nombre de la colección en MongoDB
    
  }
);

module.exports = model('Proyecto', proyectoSchema);
