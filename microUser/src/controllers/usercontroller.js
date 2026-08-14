import Usuario from '../models/usermodel.js';
import { hash, compare } from 'bcrypt';

const publicUser = (usuario) => {
  if (!usuario) return usuario;
  const data = typeof usuario.toObject === 'function' ? usuario.toObject() : { ...usuario };
  delete data.contrasena;
  return data;
};

const registrarUsuario = async (req, res) => {
  try {
    const { nombre, email, contrasena } = req.body;

    if (!nombre || !email || !contrasena) {
      return res.status(400).json({
        ok: false,
        error: 'Todos los campos son obligatorios: nombre, email y contrasena'
      });
    }

    const normalizedEmail = email.trim().toLowerCase();
    const existeEmail = await Usuario.findOne({ email: normalizedEmail });
    if (existeEmail) {
      return res.status(409).json({ ok: false, error: 'El correo ya está registrado' });
    }

    const newUsuario = await Usuario.create({
      nombre: nombre.trim(),
      email: normalizedEmail,
      contrasena: await hash(contrasena, 12)
    });

    return res.status(201).json({ ok: true, usuario: publicUser(newUsuario) });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible registrar el usuario' });
  }
};

const autenticarUsuario = async (req, res) => {
  try {
    const { email, contrasena } = req.body;
    if (!email || !contrasena) {
      return res.status(400).json({
        ok: false,
        error: 'Todos los campos son obligatorios: email y contrasena'
      });
    }

    const usuario = await Usuario.findOne({
      email: email.trim().toLowerCase()
    }).select('+contrasena');

    if (!usuario || !(await compare(contrasena, usuario.contrasena))) {
      return res.status(401).json({ ok: false, error: 'Credenciales inválidas' });
    }

    return res.status(200).json({ ok: true, usuario: publicUser(usuario) });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible autenticar el usuario' });
  }
};

const obtenerUsuario = async (req, res) => {
  try {
    const { id, email } = req.query;
    let usuario;

    if (id) {
      usuario = await Usuario.findById(id);
    } else if (email) {
      usuario = await Usuario.findOne({ email: email.trim().toLowerCase() });
    } else {
      return res.status(400).json({
        ok: false,
        error: 'Debes proporcionar un ID o email para buscar'
      });
    }

    if (!usuario) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }

    return res.status(200).json({ ok: true, usuario });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible obtener el usuario' });
  }
};

const obtenerTodosLosUsuarios = async (_req, res) => {
  try {
    const usuarios = await Usuario.find();
    return res.status(200).json({ ok: true, count: usuarios.length, usuarios });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible obtener los usuarios' });
  }
};

const actualizarUsuario = async (req, res) => {
  try {
    const { id, email, nombre } = req.query;
    const updates = {};

    if (req.body.nombre) updates.nombre = req.body.nombre.trim();
    if (req.body.email) updates.email = req.body.email.trim().toLowerCase();
    if (req.body.contrasena) updates.contrasena = await hash(req.body.contrasena, 12);

    if (Object.keys(updates).length === 0) {
      return res.status(400).json({ ok: false, error: 'No se proporcionaron campos válidos' });
    }

    const options = { new: true, runValidators: true };
    let usuarioActualizado;

    if (id) {
      usuarioActualizado = await Usuario.findByIdAndUpdate(id, updates, options);
    } else if (email) {
      usuarioActualizado = await Usuario.findOneAndUpdate(
        { email: email.trim().toLowerCase() },
        updates,
        options
      );
    } else if (nombre) {
      usuarioActualizado = await Usuario.findOneAndUpdate({ nombre }, updates, options);
    } else {
      return res.status(400).json({
        ok: false,
        error: 'Debes proporcionar un ID, email o nombre para actualizar'
      });
    }

    if (!usuarioActualizado) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }

    return res.status(200).json({ ok: true, usuario: usuarioActualizado });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible actualizar el usuario' });
  }
};

const eliminarUsuario = async (req, res) => {
  try {
    const usuarioEliminado = await Usuario.findByIdAndDelete(req.params.id);
    if (!usuarioEliminado) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }
    return res.status(200).json({ ok: true, mensaje: 'Usuario eliminado correctamente' });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible eliminar el usuario' });
  }
};

const asignarProyecto = (field) => async (req, res) => {
  try {
    const { userId, projectId } = req.body;
    if (!userId || !projectId) {
      return res.status(400).json({ ok: false, error: 'userId y projectId son obligatorios' });
    }

    const usuario = await Usuario.findByIdAndUpdate(
      userId,
      { $addToSet: { [field]: projectId } },
      { new: true, runValidators: true }
    );

    if (!usuario) {
      return res.status(404).json({ ok: false, error: 'Usuario no encontrado' });
    }

    return res.status(200).json({ ok: true, usuario });
  } catch (error) {
    return res.status(500).json({ ok: false, error: 'No fue posible asignar el proyecto' });
  }
};

export default {
  registrarUsuario,
  autenticarUsuario,
  obtenerUsuario,
  obtenerTodosLosUsuarios,
  actualizarUsuario,
  asignarProyectoAdministrado: asignarProyecto('proyectos_administrados'),
  asignarProyectoAsignado: asignarProyecto('proyectos_asignados'),
  eliminarUsuario
};
