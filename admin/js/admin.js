// Menú lateral en móvil
const menuToggle = document.getElementById('admin-menu-toggle');
const sidebar = document.getElementById('admin-sidebar');
if (menuToggle && sidebar) {
    menuToggle.addEventListener('click', () => sidebar.classList.toggle('show'));
}

function mostrarMensaje(elemento, texto, ok) {
    if (!elemento) return;
    elemento.textContent = texto;
    elemento.className = 'admin-mensaje-accion ' + (ok ? 'ok' : 'error');
}

// Ojo para mostrar/ocultar en todos los campos de contraseña de la página
document.querySelectorAll('input[type="password"]').forEach((input) => {
    const wrapper = document.createElement('div');
    wrapper.className = 'campo-password-wrapper';
    input.parentNode.insertBefore(wrapper, input);
    wrapper.appendChild(input);

    const boton = document.createElement('button');
    boton.type = 'button';
    boton.className = 'btn-toggle-password';
    boton.setAttribute('aria-label', 'Mostrar contraseña');
    boton.innerHTML = '<i class="fas fa-eye"></i>';
    wrapper.appendChild(boton);

    boton.addEventListener('click', () => {
        const mostrar = input.type === 'password';
        input.type = mostrar ? 'text' : 'password';
        boton.innerHTML = mostrar ? '<i class="fas fa-eye-slash"></i>' : '<i class="fas fa-eye"></i>';
        boton.setAttribute('aria-label', mostrar ? 'Ocultar contraseña' : 'Mostrar contraseña');
    });
});

async function llamarAccion(url, datos) {
    const respuesta = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
    });
    const resultado = await respuesta.json();
    return { ok: respuesta.ok, resultado };
}

// Modal de contraseña temporal (asociado.php), reemplaza los alert() del navegador
const modalPasswordEl = document.getElementById('modal-password-temporal');
let modalPassword = null;
let modalPasswordAlCerrar = null;
if (modalPasswordEl) {
    modalPassword = new bootstrap.Modal(modalPasswordEl);
    modalPasswordEl.addEventListener('hidden.bs.modal', () => {
        const callback = modalPasswordAlCerrar;
        modalPasswordAlCerrar = null;
        if (callback) callback();
    });
}

function mostrarModalPassword(titulo, mensaje, alCerrar) {
    if (!modalPassword) {
        alert(mensaje);
        if (alCerrar) alCerrar();
        return;
    }
    document.getElementById('modal-password-temporal-titulo').textContent = titulo;
    document.getElementById('modal-password-temporal-texto').textContent = mensaje;
    modalPasswordAlCerrar = alCerrar;
    modalPassword.show();
}

// Filtro de la tabla de asociados (dashboard.php)
const tablaAsociados = document.getElementById('tabla-asociados');
if (tablaAsociados) {
    const filtroBusqueda = document.getElementById('filtro-busqueda');
    const filtroEstado = document.getElementById('filtro-estado');
    const filtroCuota = document.getElementById('filtro-cuota');
    const filas = tablaAsociados.querySelectorAll('tbody tr[data-busqueda]');

    function aplicarFiltros() {
        const texto = (filtroBusqueda.value || '').toLowerCase().trim();
        const estado = filtroEstado.value;
        const cuota = filtroCuota.value;

        filas.forEach((fila) => {
            const coincideTexto = fila.dataset.busqueda.includes(texto);
            const coincideEstado = !estado || fila.dataset.estado === estado;
            const coincideCuota = !cuota || fila.dataset.cuota === cuota;
            fila.style.display = (coincideTexto && coincideEstado && coincideCuota) ? '' : 'none';
        });
    }

    [filtroBusqueda, filtroEstado, filtroCuota].forEach((el) => {
        if (el) el.addEventListener('input', aplicarFiltros);
    });
}

// Cambiar estado de un asociado (asociado.php)
const formEstado = document.getElementById('form-estado');
if (formEstado) {
    formEstado.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formEstado.csrf_token.value,
            asociado_id: formEstado.asociado_id.value,
            estado: formEstado.estado.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/actualizar_estado.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('estado-mensaje'), resultado.mensaje, ok);
        if (ok && resultado.password_temporal_portal) {
            mostrarModalPassword(
                'Acceso al portal activado',
                'Se aprobó y se activó su acceso al portal de afiliados. Contraseña temporal: ' + resultado.password_temporal_portal,
                () => window.location.reload()
            );
        } else if (ok) {
            setTimeout(() => window.location.reload(), 800);
        }
    });
}

// Editar datos personales del asociado (asociado.php)
const btnEditarDatos = document.getElementById('btn-editar-datos');
const formDatosAsociado = document.getElementById('form-datos-asociado');
const vistaDatosAsociado = document.getElementById('datos-asociado-vista');
if (btnEditarDatos && formDatosAsociado && vistaDatosAsociado) {
    const btnCancelarEditarDatos = document.getElementById('btn-cancelar-editar-datos');

    btnEditarDatos.addEventListener('click', () => {
        vistaDatosAsociado.style.display = 'none';
        btnEditarDatos.style.display = 'none';
        formDatosAsociado.style.display = '';
    });

    btnCancelarEditarDatos.addEventListener('click', () => {
        formDatosAsociado.style.display = 'none';
        vistaDatosAsociado.style.display = '';
        btnEditarDatos.style.display = '';
    });

    formDatosAsociado.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formDatosAsociado.csrf_token.value,
            asociado_id: formDatosAsociado.asociado_id.value,
            nombres: formDatosAsociado.nombres.value,
            apellidos: formDatosAsociado.apellidos.value,
            cedula: formDatosAsociado.cedula.value,
            fecha_nacimiento: formDatosAsociado.fecha_nacimiento.value,
            telefono: formDatosAsociado.telefono.value,
            email: formDatosAsociado.email.value,
            direccion: formDatosAsociado.direccion.value,
            fuerza: formDatosAsociado.fuerza.value,
            mensaje: formDatosAsociado.mensaje.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/actualizar_datos_asociado.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('datos-asociado-mensaje'), resultado.mensaje, ok);
        if (ok && resultado.password_temporal_portal) {
            mostrarModalPassword(
                'Correo corregido: nueva contraseña generada',
                'Como cambió el correo de acceso, la contraseña anterior quedó invalidada. Nueva contraseña temporal: ' + resultado.password_temporal_portal,
                () => window.location.reload()
            );
        } else if (ok) {
            setTimeout(() => window.location.reload(), 800);
        }
    });
}

// Subir foto de perfil (mi-cuenta.php)
const formFotoPerfil = document.getElementById('form-foto-perfil');
if (formFotoPerfil) {
    formFotoPerfil.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mensajeEl = document.getElementById('foto-perfil-mensaje');
        const boton = formFotoPerfil.querySelector('button[type="submit"]');
        boton.disabled = true;

        try {
            const respuesta = await fetch('acciones/subir_foto.php', {
                method: 'POST',
                body: new FormData(formFotoPerfil),
            });
            const resultado = await respuesta.json();
            mostrarMensaje(mensajeEl, resultado.mensaje, respuesta.ok);
            if (respuesta.ok) {
                setTimeout(() => window.location.reload(), 800);
            }
        } catch (error) {
            mostrarMensaje(mensajeEl, 'No se pudo conectar con el servidor.', false);
        } finally {
            boton.disabled = false;
        }
    });
}

// Cambiar la fecha real de afiliación (asociado.php)
const formFechaAfiliacion = document.getElementById('form-fecha-afiliacion');
if (formFechaAfiliacion) {
    formFechaAfiliacion.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formFechaAfiliacion.csrf_token.value,
            asociado_id: formFechaAfiliacion.asociado_id.value,
            fecha_afiliacion: formFechaAfiliacion.fecha_afiliacion.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/actualizar_fecha_afiliacion.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('fecha-afiliacion-mensaje'), resultado.mensaje, ok);
        if (ok) setTimeout(() => window.location.reload(), 800);
    });
}

// Enviar/restablecer acceso al portal del afiliado (asociado.php)
const btnGenerarAcceso = document.getElementById('btn-generar-acceso');
if (btnGenerarAcceso) {
    btnGenerarAcceso.addEventListener('click', async () => {
        if (!confirm('¿Generar y enviar por correo una nueva contraseña de acceso al portal?')) return;
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        btnGenerarAcceso.disabled = true;
        const { ok, resultado } = await llamarAccion('acciones/generar_acceso_afiliado.php', {
            csrf_token: csrf,
            asociado_id: btnGenerarAcceso.dataset.id,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        btnGenerarAcceso.disabled = false;
        mostrarMensaje(document.getElementById('acceso-afiliado-mensaje'), resultado.mensaje, ok);
        if (ok && resultado.password_temporal) {
            mostrarModalPassword(
                'Contraseña temporal generada',
                'Contraseña temporal del portal: ' + resultado.password_temporal,
                () => window.location.reload()
            );
        } else if (ok) {
            setTimeout(() => window.location.reload(), 1200);
        }
    });
}

// Marcar cuota como pagada (asociado.php)
const formPago = document.getElementById('form-pago');
if (formPago) {
    formPago.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formPago.csrf_token.value,
            asociado_id: formPago.asociado_id.value,
            anio: formPago.anio.value,
            mes: formPago.mes.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/marcar_pago.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('pago-mensaje'), resultado.mensaje, ok);
        if (ok) setTimeout(() => window.location.reload(), 800);
    });
}

// Crear cuenta de administrador (administradores.php)
const formCrearAdmin = document.getElementById('form-crear-admin');
if (formCrearAdmin) {
    formCrearAdmin.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formCrearAdmin.csrf_token.value,
            accion: 'crear',
            nombre: formCrearAdmin.nombre.value,
            email: formCrearAdmin.email.value,
            telefono: formCrearAdmin.telefono.value,
            rol: formCrearAdmin.rol.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/gestionar_admin.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('crear-admin-mensaje'), resultado.mensaje, ok);
        if (ok && resultado.password_temporal) {
            alert(
                'Cuenta creada.\n\nCorreo: ' + datos.email +
                '\nContraseña temporal: ' + resultado.password_temporal +
                '\n\nCópiala ahora, no se volverá a mostrar. Pídele a esa persona que la cambie desde "Mi cuenta" al ingresar.'
            );
            setTimeout(() => window.location.reload(), 300);
        }
    });
}

// Activar/desactivar y resetear contraseña de administradores (administradores.php)
document.querySelectorAll('.btn-toggle-activo').forEach((boton) => {
    boton.addEventListener('click', async () => {
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        const { ok, resultado } = await llamarAccion('acciones/gestionar_admin.php', {
            csrf_token: csrf,
            accion: 'toggle',
            id: boton.dataset.id,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        if (ok) {
            window.location.reload();
        } else {
            alert(resultado.mensaje);
        }
    });
});

document.querySelectorAll('.btn-resetear').forEach((boton) => {
    boton.addEventListener('click', async () => {
        if (!confirm('¿Generar una nueva contraseña temporal para esta cuenta?')) return;
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        const { ok, resultado } = await llamarAccion('acciones/gestionar_admin.php', {
            csrf_token: csrf,
            accion: 'resetear',
            id: boton.dataset.id,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        if (ok) {
            alert('Nueva contraseña temporal: ' + resultado.password_temporal + '\n\nCópiala ahora, no se volverá a mostrar.');
        } else {
            alert(resultado.mensaje);
        }
    });
});

// Cambiar mi propia contraseña (mi-cuenta.php)
const formCambiarPassword = document.getElementById('form-cambiar-password');
if (formCambiarPassword) {
    formCambiarPassword.addEventListener('submit', async (e) => {
        e.preventDefault();
        const datos = {
            csrf_token: formCambiarPassword.csrf_token.value,
            password_actual: formCambiarPassword.password_actual.value,
            password_nueva: formCambiarPassword.password_nueva.value,
            password_confirmar: formCambiarPassword.password_confirmar.value,
        };
        const { ok, resultado } = await llamarAccion('acciones/cambiar_password.php', datos)
            .catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));
        mostrarMensaje(document.getElementById('password-mensaje'), resultado.mensaje, ok);
        if (ok) formCambiarPassword.reset();
    });
}

// Modal "Cuotas" con celdas clickeables de los últimos 12 meses
// (usado desde dashboard.php y cuentas.php)
const modalCuotasEl = document.getElementById('modal-cuotas');
if (modalCuotasEl) {
    const modalCuotas = new bootstrap.Modal(modalCuotasEl);
    const modalNombre = document.getElementById('modal-cuotas-nombre');
    const modalAsociadoId = document.getElementById('modal-cuotas-asociado-id');
    const modalGrid = document.getElementById('modal-cuotas-grid');
    const modalMensaje = document.getElementById('modal-cuotas-mensaje');
    const modalCsrf = document.getElementById('modal-cuotas-csrf');

    function pintarCelda(celda, pagado) {
        celda.classList.toggle('cuota-mes-pagado', pagado);
        celda.classList.toggle('cuota-mes-moroso', !pagado);
        celda.dataset.pagado = pagado ? '1' : '0';
    }

    function renderizarGrid(meses) {
        modalGrid.innerHTML = '';
        meses.forEach((m) => {
            const celda = document.createElement('button');
            celda.type = 'button';
            celda.className = 'cuota-mes-chip';
            celda.textContent = m.label;
            celda.dataset.anio = m.anio;
            celda.dataset.mes = m.mes;
            pintarCelda(celda, m.pagado);
            celda.addEventListener('click', () => alClicEnMes(celda));
            modalGrid.appendChild(celda);
        });
    }

    async function alClicEnMes(celda) {
        const pagado = celda.dataset.pagado === '1';
        const mensajeConfirmacion = pagado
            ? 'El usuario tiene un pago registrado para ' + celda.textContent + '. ¿Desea revertirlo?'
            : 'El usuario aportó la cuota mensual de ' + celda.textContent + '. ¿Desea cambiar el estado a pago?';

        if (!confirm(mensajeConfirmacion)) return;

        const accion = pagado ? 'moroso' : 'pagado';
        celda.disabled = true;

        const { ok, resultado } = await llamarAccion('acciones/marcar_pago.php', {
            csrf_token: modalCsrf.value,
            asociado_id: modalAsociadoId.value,
            anio: celda.dataset.anio,
            mes: celda.dataset.mes,
            accion,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        celda.disabled = false;
        mostrarMensaje(modalMensaje, resultado.mensaje, ok);
        if (ok) {
            pintarCelda(celda, accion === 'pagado');
        }
    }

    document.querySelectorAll('.btn-ver-cuotas').forEach((boton) => {
        boton.addEventListener('click', () => {
            modalNombre.textContent = boton.dataset.nombre;
            modalAsociadoId.value = boton.dataset.id;
            modalMensaje.textContent = '';
            modalMensaje.className = 'admin-mensaje-accion';
            renderizarGrid(JSON.parse(boton.dataset.meses));
            modalCuotas.show();
        });
    });
}

// Descargar PDF general de cuentas (admin/cuentas.php)
const btnReporteGeneral = document.getElementById('btn-reporte-general');
if (btnReporteGeneral) {
    btnReporteGeneral.addEventListener('click', () => {
        const anio = document.getElementById('reporte-general-anio').value;
        window.open('descargar_reporte_general.php?anio=' + encodeURIComponent(anio), '_blank');
    });
}

// Descargar PDF de un asociado (admin/cuentas.php)
const modalDescargaEl = document.getElementById('modal-descarga-cuenta');
if (modalDescargaEl) {
    const modalDescarga = new bootstrap.Modal(modalDescargaEl);
    const modalDescargaNombre = document.getElementById('modal-descarga-nombre');
    const modalDescargaAsociadoId = document.getElementById('modal-descarga-asociado-id');
    const modalDescargaTipo = document.getElementById('modal-descarga-tipo');
    const modalDescargaCampoAnio = document.getElementById('modal-descarga-campo-anio');
    const modalDescargaAnio = document.getElementById('modal-descarga-anio');

    modalDescargaTipo.addEventListener('change', () => {
        modalDescargaCampoAnio.style.display = modalDescargaTipo.value === 'anio' ? '' : 'none';
    });

    document.querySelectorAll('.btn-descargar-cuenta').forEach((boton) => {
        boton.addEventListener('click', () => {
            modalDescargaNombre.textContent = boton.dataset.nombre;
            modalDescargaAsociadoId.value = boton.dataset.id;

            const anioMin = parseInt(boton.dataset.anioMin, 10);
            const anioActual = new Date().getFullYear();
            modalDescargaAnio.innerHTML = '';
            for (let a = anioActual; a >= anioMin; a--) {
                const opcion = document.createElement('option');
                opcion.value = String(a);
                modalDescargaAnio.appendChild(opcion);
            }

            modalDescargaTipo.value = 'todo';
            modalDescargaCampoAnio.style.display = 'none';
            modalDescarga.show();
        });
    });

    document.getElementById('btn-confirmar-descarga-cuenta').addEventListener('click', () => {
        const params = new URLSearchParams({
            id: modalDescargaAsociadoId.value,
            tipo: modalDescargaTipo.value,
        });
        if (modalDescargaTipo.value === 'anio') {
            params.set('anio', modalDescargaAnio.value);
        }
        window.open('descargar_reporte_asociado.php?' + params.toString(), '_blank');
    });
}

// Resolver tickets (admin/tickets.php)
const modalTicketEl = document.getElementById('modal-ticket');
if (modalTicketEl) {
    const modalTicket = new bootstrap.Modal(modalTicketEl);
    const modalTicketId = document.getElementById('modal-ticket-id');
    const modalTicketRespuesta = document.getElementById('modal-ticket-respuesta');
    const modalTicketMensaje = document.getElementById('modal-ticket-mensaje');
    const modalTicketCsrf = document.getElementById('modal-ticket-csrf');

    document.querySelectorAll('.btn-resolver-ticket').forEach((boton) => {
        boton.addEventListener('click', () => {
            modalTicketId.value = boton.dataset.id;
            modalTicketRespuesta.value = '';
            modalTicketMensaje.textContent = '';
            modalTicketMensaje.className = 'admin-mensaje-accion';
            modalTicket.show();
        });
    });

    document.getElementById('btn-confirmar-resolver').addEventListener('click', async () => {
        const { ok, resultado } = await llamarAccion('acciones/responder_ticket.php', {
            csrf_token: modalTicketCsrf.value,
            ticket_id: modalTicketId.value,
            respuesta: modalTicketRespuesta.value,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        mostrarMensaje(modalTicketMensaje, resultado.mensaje, ok);
        if (ok) setTimeout(() => window.location.reload(), 800);
    });
}

// Aprobar/rechazar testimonios (admin/testimonios.php)
const testimoniosCsrfEl = document.getElementById('testimonios-csrf');
if (testimoniosCsrfEl) {
    async function actualizarTestimonio(boton, estado) {
        boton.disabled = true;
        const { ok, resultado } = await llamarAccion('acciones/actualizar_testimonio.php', {
            csrf_token: testimoniosCsrfEl.value,
            testimonio_id: boton.dataset.id,
            estado,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        if (ok) {
            window.location.reload();
        } else {
            boton.disabled = false;
            alert(resultado.mensaje);
        }
    }

    document.querySelectorAll('.btn-testimonio-aprobar').forEach((boton) => {
        boton.addEventListener('click', () => actualizarTestimonio(boton, 'aprobado'));
    });

    document.querySelectorAll('.btn-testimonio-rechazar').forEach((boton) => {
        boton.addEventListener('click', () => {
            if (confirm('¿Rechazar este testimonio? No se publicará en el sitio.')) {
                actualizarTestimonio(boton, 'rechazado');
            }
        });
    });
}

// Subir documento público (admin/documentos.php)
const formSubirDocumento = document.getElementById('form-subir-documento');
if (formSubirDocumento) {
    formSubirDocumento.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mensajeEl = document.getElementById('documento-mensaje');
        const boton = formSubirDocumento.querySelector('button[type="submit"]');
        boton.disabled = true;

        try {
            const respuesta = await fetch('acciones/subir_documento.php', {
                method: 'POST',
                body: new FormData(formSubirDocumento),
            });
            const resultado = await respuesta.json();
            mostrarMensaje(mensajeEl, resultado.mensaje, respuesta.ok);
            if (respuesta.ok) {
                formSubirDocumento.reset();
                setTimeout(() => window.location.reload(), 1000);
            }
        } catch (error) {
            mostrarMensaje(mensajeEl, 'No se pudo conectar con el servidor.', false);
        } finally {
            boton.disabled = false;
        }
    });
}

// Eliminar documento público (admin/documentos.php)
document.querySelectorAll('.btn-eliminar-documento').forEach((boton) => {
    boton.addEventListener('click', async () => {
        if (!confirm('¿Eliminar este documento? Dejará de verse en el sitio público.')) return;
        boton.disabled = true;
        const csrf = document.querySelector('input[name="csrf_token"]').value;
        const { ok, resultado } = await llamarAccion('acciones/eliminar_documento.php', {
            csrf_token: csrf,
            documento_id: boton.dataset.id,
        }).catch(() => ({ ok: false, resultado: { mensaje: 'No se pudo conectar con el servidor.' } }));

        if (ok) {
            window.location.reload();
        } else {
            boton.disabled = false;
            alert(resultado.mensaje);
        }
    });
});
