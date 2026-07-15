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

async function llamarAccion(url, datos) {
    const respuesta = await fetch(url, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(datos),
    });
    const resultado = await respuesta.json();
    return { ok: respuesta.ok, resultado };
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
        if (ok) setTimeout(() => window.location.reload(), 800);
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
