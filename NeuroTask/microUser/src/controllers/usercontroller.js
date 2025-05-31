import Usuario from '../models/usermodel.js';
import { hash, compare } from 'bcrypt';



// Registrar un usuario
const registrarUsuario = async (req, res) => {
    try {
        const { nombre, email, contrasena } = req.body;

        // Validar que los datos existen
        if (!nombre || !email || !contrasena) {
            return res.status(400).json({
                ok: false,
                error: "Todos los campos son obligatorios: nombre, email y contrasena"
            });
        }

        // Validar que el correo sea único
        const existeEmail = await Usuario.findOne({ email });
        if (existeEmail) {
            return res.status(409).json({
                ok: false,
                error: "El correo ya está registrado"
            });
        }

        // Encriptar la contraseña
        const hashedPassword = await hash(contrasena, 10);

        // Crear un nuevo usuario
        const newUsuario = new Usuario({
            nombre,
            email,
            contrasena: hashedPassword
        });

        await newUsuario.save();

        res.status(201).json({
            ok: true,
            usuario: newUsuario
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};

// Autenticar al usuario
const autenticarUsuario = async (req, res) => {
    try {
        const { email, contrasena } = req.body;

        // Validar que los datos existen
        if (!email || !contrasena) {
            return res.status(400).json({
                ok: false,
                error: "Todos los campos son obligatorios: email y contrasena"
            });
        }

        // Buscar usuario por email
        const usuarioEncontrado = await Usuario.findOne({ email });

        if (!usuarioEncontrado) {
            return res.status(404).json({
                ok: false,
                error: "Usuario no encontrado"
            });
        }

        // Validar contraseña
        const passwordValido = await compare(contrasena, usuarioEncontrado.contrasena);

        if (!passwordValido) {
            return res.status(401).json({
                ok: false,
                error: "Credenciales inválidas"
            });
        }

        // Respuesta exitosa
        res.status(200).json({
            ok: true,
            usuario: usuarioEncontrado
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};




const obtenerUsuario = async (req, res) => {
    try {
        const { id, email } = req.query; // Ahora obtenemos los parámetros de consulta (query)

        let usuario;

        // Buscar por email o nombre
        if (id) {
            usuario = await Usuario.findById(id);
        } else if (email) {
            usuario = await Usuario.findOne({ email });
        } else {
            return res.status(400).json({
                ok: false,
                error: "Debes proporcionar un ID, email o nombre para buscar"
            });
        }

        if (!usuario) {
            return res.status(404).json({
                ok: false,
                error: "Usuario no encontrado"
            });
        }

        res.status(200).json({
            ok: true,
            usuario
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};






// Obtener todos los usuarios
const obtenerTodosLosUsuarios = async (req, res) => {
    try {
        const usuarios = await Usuario.find();

        res.status(200).json({
            ok: true,
            count: usuarios.length,
            usuarios
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};




const actualizarUsuario = async (req, res) => {
    try {
        const { id, email, nombre } = req.query; // Buscar por ID, email o nombre
        const updates = req.body;

        let usuarioActualizado;

        if (id) {
            usuarioActualizado = await Usuario.findByIdAndUpdate(id, updates, { new: true });
        } else if (email) {
            usuarioActualizado = await Usuario.findOneAndUpdate({ email }, updates, { new: true });
        } else if (nombre) {
            usuarioActualizado = await Usuario.findOneAndUpdate({ nombre }, updates, { new: true });
        } else {
            return res.status(400).json({
                ok: false,
                error: "Debes proporcionar un ID, email o nombre para actualizar"
            });
        }

        if (!usuarioActualizado) {
            return res.status(404).json({
                ok: false,
                error: "Usuario no encontrado"
            });
        }

        res.status(200).json({
            ok: true,
            usuario: usuarioActualizado
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};

// Eliminar un usuario
const eliminarUsuario = async (req, res) => {
    try {
        const { id } = req.params;
        
        // Buscar y eliminar el usuario
        const usuarioEliminado = await Usuario.findByIdAndDelete(id);
        
        if (!usuarioEliminado) {
            return res.status(404).json({
                ok: false,
                error: "Usuario no encontrado"
            });
        }
        
        res.status(200).json({
            ok: true,
            mensaje: "Usuario eliminado correctamente"
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};

// Asignar un proyecto como administrado
const asignarProyectoAdministrado = async (req, res) => {
    try {
      const { userId, projectId } = req.body;
  
      // Buscar el usuario por su ID
      const usuario = await Usuario.findById(userId);
      if (!usuario) {
        return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
      }
  
      // Agregar el projectId al array de proyectos_administrados
      if (!usuario.proyectos_administrados.includes(projectId)) {
        usuario.proyectos_administrados.push(projectId);
        await usuario.save();
      }
  
      res.status(200).json({ ok: true, usuario });
    } catch (err) {
      res.status(500).json({ ok: false, error: err.message });
    }
  };
  
  // Asignar un proyecto como asignado (miembro)
  const asignarProyectoAsignado = async (req, res) => {
    try {
      const { userId, projectId } = req.body;
  
      // Buscar el usuario por su ID
      const usuario = await Usuario.findById(userId);
      if (!usuario) {
        return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
      }
  
      // Agregar el projectId al array de proyectos_asignados
      if (!usuario.proyectos_asignados.includes(projectId)) {
        usuario.proyectos_asignados.push(projectId);
        await usuario.save();
      }
  
      res.status(200).json({ ok: true, usuario });
    } catch (err) {
      res.status(500).json({ ok: false, error: err.message });
    }
  };





export default {
    registrarUsuario,
    autenticarUsuario,
    obtenerUsuario,
    obtenerTodosLosUsuarios,
    actualizarUsuario,
    asignarProyectoAdministrado,
    asignarProyectoAsignado,
    eliminarUsuario,
};



