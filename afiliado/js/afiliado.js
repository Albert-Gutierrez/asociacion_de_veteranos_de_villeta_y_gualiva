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

// Reportar un pago no reflejado (dashboard.php)
const formTicket = document.getElementById('form-ticket');
if (formTicket) {
    formTicket.addEventListener('submit', async (e) => {
        e.preventDefault();
        const mensajeEl = document.getElementById('ticket-mensaje');
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
}
