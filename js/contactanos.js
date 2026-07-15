// menu hamburguesa
const toggle = document.getElementById('menu-toggle');
const nav = document.getElementById('navbarNav');

toggle.addEventListener('click', () => {
    nav.classList.toggle('show');
});

// Envía el formulario al backend (guarda en BD + correo) y luego abre WhatsApp
const formWhatsApp = document.getElementById('formWhatsApp');
const formError = document.getElementById('form-error');
const btnEnviar = formWhatsApp.querySelector('.btn-enviar');
const NUMERO_WHATSAPP = '573212281546';

function mostrarError(mensaje) {
    formError.textContent = mensaje;
    formError.style.display = 'block';
}

function ocultarError() {
    formError.style.display = 'none';
    formError.textContent = '';
}

formWhatsApp.addEventListener('submit', async function (e) {
    e.preventDefault();
    ocultarError();

    const datos = {
        nombres: document.getElementById('nombres').value,
        apellidos: document.getElementById('apellidos').value,
        cedula: document.getElementById('cedula').value,
        fecha_nacimiento: document.getElementById('fecha_nacimiento').value,
        telefono: document.getElementById('telefono').value,
        email: document.getElementById('email').value,
        direccion: document.getElementById('direccion').value,
        fuerza: document.getElementById('fuerza').value,
        mensaje: document.getElementById('mensaje').value,
        sitio_web: document.getElementById('sitio_web').value, // honeypot
    };

    btnEnviar.disabled = true;

    try {
        const respuesta = await fetch('procesar_formulario.php', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(datos),
        });

        const resultado = await respuesta.json();

        if (!respuesta.ok || !resultado.exito) {
            mostrarError(resultado.mensaje || 'No se pudo enviar tu solicitud. Intenta de nuevo.');
            btnEnviar.disabled = false;
            return;
        }

        const texto =
            `*Hola, deseo afiliarme.*%0A` +
            `*Nuevo contacto desde la web ASOVEGU*%0A%0A` +
            `*Nombre:* ${datos.nombres} ${datos.apellidos}%0A` +
            `*Teléfono:* ${datos.telefono}%0A` +
            `*Email:* ${datos.email}%0A` +
            `*Fuerza:* ${datos.fuerza}%0A` +
            `*Mensaje:* ${datos.mensaje}`;

        const url = `https://wa.me/${NUMERO_WHATSAPP}?text=${texto}`;
        window.open(url, '_blank');

        setTimeout(() => {
            window.location.href = 'contactanos_gracias.html';
        }, 1000);
    } catch (error) {
        mostrarError('No se pudo conectar con el servidor. Verifica tu conexión e intenta de nuevo.');
        btnEnviar.disabled = false;
    }
});
