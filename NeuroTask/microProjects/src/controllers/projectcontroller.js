const Proyecto = require('../models/projectmodel');
const axios = require('axios');
const mongoose = require('mongoose');

const crearProyecto = async (req, res) => {
    try {
      const { nombre, descripcion, creador, miembros } = req.body;
  
      if (!nombre || !descripcion || !creador) {
        return res.status(400).json({
          ok: false,
          error: 'Los campos nombre, descripcion y creador son obligatorios'
        });
      }
  
      // 1. Crear el nuevo proyecto en MicroProjects
      const nuevoProyecto = new Proyecto({
        nombre,
        descripcion,
        creador,        // OJO: aquí guardas el _id del usuario creador
        miembros: miembros || []
      });
      await nuevoProyecto.save();
  
      // 2. Llamar a MicroUser para asignar el proyecto al creador
      try {
        await axios.put('http://micro_user:3009/api/user/admin-proyecto', {
          userId: creador,
          projectId: nuevoProyecto._id
        });
      } catch (error) {
        console.error(`Error asignando el proyecto al creador:`, error.message);
        // Aquí decides si devuelves un error o continúas
      }
  
      // 3. Llamar a MicroUser para cada miembro y asignarlo al proyecto
      if (miembros && miembros.length > 0) {
        for (const miembroId of miembros) {
          try {
            await axios.put('http://micro_user:3009/api/user/asigna-proyecto', {
              userId: miembroId,
              projectId: nuevoProyecto._id
            });
          } catch (error) {
            console.error(`Error asignando proyecto al miembro ${miembroId}:`, error.message);
            // Decides si omites este error o detienes el proceso
          }
        }
      }
  
      // 4. Responder con éxito
      res.status(201).json({
        ok: true,
        proyecto: nuevoProyecto,
        mensaje: 'Proyecto creado y miembros asignados correctamente'
      });
    } catch (err) {
      console.error('Error en crearProyecto:', err.message);
      res.status(500).json({
        ok: false,
        error: err.message
      });
    }
  };
  




const obtenerProyectos = async (req, res) => {
    try {
        const { usuarioId } = req.params;  // Obtener el ID del usuario desde la URL

        // Buscar proyectos donde el usuario es creador o miembro
        const proyectos = await Proyecto.find({
            $or: [
                { creador: usuarioId },  // Si el usuario es creador
                { miembros: usuarioId }   // Si el usuario es miembro
            ]
        });

        if (proyectos.length === 0) {
            return res.status(404).json({
                ok: false,
                error: 'No se encontraron proyectos para este usuario'
            });
        }

        // Iterar sobre cada proyecto para obtener detalles de creador y miembros
        const proyectosConDetalles = await Promise.all(
            proyectos.map(async (proyecto) => {
                let creador = null;
                let miembrosDetalles = [];

                // Obtener detalles del creador desde el MicroUser
                try {
                    const respuestaCreador = await axios.get(`http://micro_user:3009/api/user/buscar?id=${proyecto.creador}`);
                    creador = respuestaCreador.data.usuario;
                } catch (error) {
                    console.error(`Error obteniendo el creador con ID ${proyecto.creador}:`, error.message);
                }

                // Obtener detalles de los miembros desde el MicroUser
                try {
                    miembrosDetalles = await Promise.all(
                        proyecto.miembros.map(async (miembroId) => {
                            try {
                                const respuestaMiembro = await axios.get(`http://micro_user:3009/api/user/buscar?id=${miembroId}`);
                                return respuestaMiembro.data.usuario;
                            } catch (error) {
                                console.error(`Error obteniendo miembro con ID ${miembroId}:`, error.message);
                                return { error: `No se pudo obtener el usuario con ID: ${miembroId}` };
                            }
                        })
                    );
                } catch (error) {
                    console.error(`Error obteniendo detalles de los miembros:`, error.message);
                }

                // Retornar el proyecto con detalles
                return {
                    ...proyecto._doc,
                    creador,
                    miembros: miembrosDetalles
                };
            })
        );

        res.status(200).json({
            ok: true,
            proyectos: proyectosConDetalles
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};



const obtenerProyectoPorNombreOId = async (req, res) => {
    try {
        const { nombre, id } = req.params;

        console.log(id);  // Para ver el valor del ID recibido

        let proyecto;
        
        // Si se pasa un ID, usamos mongoose.Types.ObjectId para verificar y convertir
        if (id) {
            if (!mongoose.Types.ObjectId.isValid(id)) {
                return res.status(400).json({
                    ok: false,
                    error: 'ID de proyecto no válido'
                });
            }
            proyecto = await Proyecto.findById(id);
        } else if (nombre) {
            proyecto = await Proyecto.findOne({ nombre });
        }

        if (!proyecto) {
            return res.status(404).json({
                ok: false,
                error: 'Proyecto no encontrado'
            });
        }

        // Obtener detalles del creador desde el MicroUser
        let creador = null;
        try {
            const respuestaCreador = await axios.get(`http://micro_user:3009/api/user/buscar?id=${proyecto.creador}`);
            creador = respuestaCreador.data.usuario;
        } catch (error) {
            console.error(`Error obteniendo el creador con ID ${proyecto.creador}:`, error.message);
        }

        // Obtener detalles de los miembros desde el MicroUser
        const miembrosDetalles = await Promise.all(
            proyecto.miembros.map(async (miembroId) => {
                try {
                    const respuestaMiembro = await axios.get(`http://micro_user:3009/api/user/buscar?id=${miembroId}`);
                    return respuestaMiembro.data.usuario;
                } catch (error) {
                    console.error(`Error obteniendo miembro con ID ${miembroId}:`, error.message);
                    return { error: `No se pudo obtener el usuario con ID: ${miembroId}` };
                }
            })
        );

        res.status(200).json({
            ok: true,
            proyecto: {
                ...proyecto._doc,
                creador,
                miembros: miembrosDetalles
            }
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};



const actualizarProyectoPorId = async (req, res) => {
    try {
        const { id } = req.params;

        // Verificar que se pasó un ID
        if (!id) {
            return res.status(400).json({
                ok: false,
                error: 'El ID del proyecto es obligatorio'
            });
        }

        // Actualizar el proyecto por ID
        const proyectoActualizado = await Proyecto.findByIdAndUpdate(
            id,
            req.body,
            { new: true }
        );

        if (!proyectoActualizado) {
            return res.status(404).json({
                ok: false,
                error: 'Proyecto no encontrado'
            });
        }

        res.status(200).json({
            ok: true,
            proyecto: proyectoActualizado
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};





const eliminarProyectoPorId = async (req, res) => {
    try {
        const { id } = req.params;

        // Verificar que se pasó un ID
        if (!id) {
            return res.status(400).json({
                ok: false,
                error: 'El ID del proyecto es obligatorio'
            });
        }

        // Eliminar el proyecto por ID
        const proyectoEliminado = await Proyecto.findByIdAndDelete(id);

        if (!proyectoEliminado) {
            return res.status(404).json({
                ok: false,
                error: 'Proyecto no encontrado'
            });
        }

        res.status(200).json({
            ok: true,
            mensaje: 'Proyecto eliminado'
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};




const agregarMiembroPorEmail = async (req, res) => {
    try {
        const nombre = decodeURIComponent(req.params.nombre.replace(/\+/g, ' '));
        const { email } = req.body;
        console.log(req.params, nombre);

        // Buscar el proyecto por nombre
        const proyecto = await Proyecto.findOne({ nombre });
        if (!proyecto) {
            return res.status(404).json({
                ok: false,
                error: 'Proyecto no encontrado'
            });
        }

        // Buscar el usuario por email usando el MicroUser
        let usuario = null;
        try {
            const usuarioRespuesta = await axios.get(`http://micro_user:3009/api/user/buscar?email=${email}`);
            usuario = usuarioRespuesta.data.usuario;
        } catch (error) {
            console.error(`Error buscando usuario por email ${email}:`, error.message);
            return res.status(404).json({
                ok: false,
                error: 'Usuario no encontrado'
            });
        }

        // Validar si el usuario ya es miembro del proyecto
        if (proyecto.miembros.includes(usuario._id)) {
            return res.status(400).json({
                ok: false,
                error: 'El usuario ya es miembro del proyecto'
            });
        }

        // Agregar el miembro al proyecto
        proyecto.miembros.push(usuario._id);
        await proyecto.save();

        res.status(200).json({
            ok: true,
            mensaje: `Usuario ${usuario.nombre} agregado al proyecto con éxito`,
            proyecto
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};





//Elimina un miembro de un proyecto utilizando el email del miembro
const eliminarMiembroPorEmail = async (req, res) => {
    try {
        const { nombre, email } = req.params;

        // Buscar el proyecto por nombre
        const proyecto = await Proyecto.findOne({ nombre });
        if (!proyecto) {
            return res.status(404).json({
                ok: false,
                error: 'Proyecto no encontrado'
            });
        }

        // Buscar el usuario por email (desde MicroUser, configuraremos luego)
        const usuarioRespuesta = await axios.get(`http://micro_user:3009/api/user/buscar?email=${email}`);
        const usuario = usuarioRespuesta.data.usuario;

        if (!usuario) {
            return res.status(404).json({
                ok: false,
                error: 'Usuario no encontrado'
            });
        }

        // Eliminar el miembro del proyecto
        proyecto.miembros = proyecto.miembros.filter(
            miembroId => miembroId.toString() !== usuario._id
        );
        await proyecto.save();

        res.status(200).json({
            ok: true,
            proyecto
        });
    } catch (err) {
        res.status(500).json({
            ok: false,
            error: err.message
        });
    }
};

















module.exports = {
  crearProyecto,
  obtenerProyectos,
  obtenerProyectoPorNombreOId,
  actualizarProyectoPorId,
  eliminarProyectoPorId,
  agregarMiembroPorEmail,
  eliminarMiembroPorEmail
};


