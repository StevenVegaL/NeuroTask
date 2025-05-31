import { Schema, model } from 'mongoose';

// Definición del esquema del usuario
const usuarioSchema = new Schema({
    nombre: {
        type: String,
        required: true,
        description: 'Nombre completo del usuario'
    },
    email: {
        type: String,
        required: true,
        unique: true,
        match: /^.+@.+$/, // Validación de formato de correo
        description: 'Correo electrónico válido del usuario'
    },
    contrasena: {
        type: String,
        required: true,
        description: 'Contraseña encriptada del usuario'
    },
    proyectos_administrados: {
        type: [Schema.Types.ObjectId],
        ref: 'proyecto',
        description: 'Lista de IDs de los proyectos donde es administrador'
    },
    proyectos_asignados: {
        type: [Schema.Types.ObjectId],
        ref: 'proyecto',
        description: 'Lista de IDs de los proyectos en los que participa como miembro'
    }
}, {
    collection: 'usuarios', // Nombre de la colección en MongoDB
    timestamps: true // Agrega automáticamente createdAt y updatedAt
});

// Exportar el modelo
export default model('Usuario', usuarioSchema);
