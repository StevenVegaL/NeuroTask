import { Schema, model } from 'mongoose';

const usuarioSchema = new Schema({
  nombre: {
    type: String,
    required: true,
    trim: true,
    description: 'Nombre completo del usuario'
  },
  email: {
    type: String,
    required: true,
    unique: true,
    lowercase: true,
    trim: true,
    match: /^.+@.+$/,
    description: 'Correo electrónico válido del usuario'
  },
  contrasena: {
    type: String,
    required: true,
    select: false,
    description: 'Hash de la contraseña del usuario'
  },
  proyectos_administrados: {
    type: [Schema.Types.ObjectId],
    ref: 'proyecto',
    default: [],
    description: 'Proyectos administrados por el usuario'
  },
  proyectos_asignados: {
    type: [Schema.Types.ObjectId],
    ref: 'proyecto',
    default: [],
    description: 'Proyectos en los que participa el usuario'
  }
}, {
  collection: 'usuarios',
  timestamps: true
});

export default model('Usuario', usuarioSchema);
