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

// Cambiar contraseña (cambiar-password.php, obligatorio en el primer ingreso)
const formCambiarPasswordAfiliado = document.getElementById('form-cambiar-password-afiliado');
if (formCambiarPasswordAfiliado) {
    formCambiarPasswordAfiliado.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mensajeEl = document.getElementById('password-afiliado-mensaje');
        const datos = {
            csrf_token: formCambiarPasswordAfiliado.csrf_token.value,
            password_actual: formCambiarPasswordAfiliado.password_actual.value,
            password_nueva: formCambiarPasswordAfiliado.password_nueva.value,
            password_confirmar: formCambiarPasswordAfiliado.password_confirmar.value,
        };
        try {
            const respuesta = await fetch('acciones/cambiar_password.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(datos),
            });
            const resultado = await respuesta.json();
            mostrarMensaje(mensajeEl, resultado.mensaje, respuesta.ok);
            if (respuesta.ok) {
                setTimeout(() => { window.location.href = 'dashboard.php'; }, 800);
            }
        } catch (error) {
            mostrarMensaje(mensajeEl, 'No se pudo conectar con el servidor.', false);
        }
    });
}

// Subir foto de perfil (dashboard.php, la única info que edita el afiliado)
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

// Reportar un pago no reflejado o pedir corrección de datos (soporte.php)
document.querySelectorAll('.form-ticket').forEach((formTicket) => {
    const mensajeEl = document.getElementById('ticket-' + formTicket.dataset.tipo + '-mensaje');

    formTicket.addEventListener('submit', async (e) => {
        e.preventDefault();
        const boton = formTicket.querySelector('button[type="submit"]');
        boton.disabled = true;

        try {
            const respuesta = await fetch('acciones/crear_ticket.php', {
                method: 'POST',
                body: new FormData(formTicket),
            });
            const resultado = await respuesta.json();
            mostrarMensaje(mensajeEl, resultado.mensaje, respuesta.ok);
            if (respuesta.ok) {
                formTicket.reset();
                setTimeout(() => window.location.reload(), 1200);
            }
        } catch (error) {
            mostrarMensaje(mensajeEl, 'No se pudo conectar con el servidor.', false);
        } finally {
            boton.disabled = false;
        }
    });
});
