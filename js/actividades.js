// menu hamburguesa
const toggle = document.getElementById('menu-toggle');
const nav = document.getElementById('navbarNav');

toggle.addEventListener('click', () => {
    nav.classList.toggle('show');
});

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}

// Actividades: las publica el administrador desde el panel (admin/documentos.php).
// Cada tarjeta se voltea con hover/touch para mostrar la descripción y un
// botón "Ver más" que abre un modal con el título, la descripción completa y
// todas las imágenes (portada + galería) de la actividad.
const modalActividadEl = document.getElementById('modal-actividad');
const modalActividad = modalActividadEl ? new bootstrap.Modal(modalActividadEl) : null;

let actividadesCargadas = [];

function abrirModalActividad(id) {
    const actividad = actividadesCargadas.find((a) => a.id === id);
    if (!actividad || !modalActividad) return;

    document.getElementById('modal-actividad-titulo').textContent = actividad.titulo;
    document.getElementById('modal-actividad-descripcion').textContent = actividad.descripcion;

    const todasLasImagenes = [actividad.imagen_portada, ...actividad.imagenes];
    const galeriaEl = document.getElementById('modal-actividad-galeria');
    galeriaEl.innerHTML = todasLasImagenes.map((url) => {
        return '<img src="' + url + '" alt="' + escaparHtml(actividad.titulo) + '">';
    }).join('');

    modalActividad.show();
}

fetch('actividades_publicas.php')
    .then((respuesta) => respuesta.json())
    .then((datos) => {
        actividadesCargadas = datos.actividades || [];
        const contenedor = document.getElementById('grid-actividades');

        if (actividadesCargadas.length === 0) {
            contenedor.outerHTML = '<p class="testimonios-vacio">Todavía no hay actividades publicadas.</p>';
            return;
        }

        contenedor.innerHTML = actividadesCargadas.map((a) => {
            return '<div class="tarjeta-actividad" data-aos="zoom-in" tabindex="0">'
                + '<div class="tarjeta-interna">'
                + '<div class="tarjeta-frente">'
                + '<img src="' + a.imagen_portada + '" alt="' + escaparHtml(a.titulo) + '">'
                + '<h3>' + escaparHtml(a.titulo) + '</h3>'
                + '</div>'
                + '<div class="tarjeta-reverso">'
                + '<h3>' + escaparHtml(a.titulo) + '</h3>'
                + '<p>' + escaparHtml(a.descripcion) + '</p>'
                + '<button type="button" class="btn-ver-mas-actividad" data-id="' + a.id + '">Ver más</button>'
                + '</div>'
                + '</div>'
                + '</div>';
        }).join('');

        // Voltear tarjetas al tocar (pantallas táctiles sin hover)
        contenedor.querySelectorAll('.tarjeta-actividad').forEach((tarjeta) => {
            tarjeta.addEventListener('click', () => {
                tarjeta.classList.toggle('volteada');
            });
        });

        // "Ver más": abre el modal sin voltear/cerrar la tarjeta de golpe
        contenedor.querySelectorAll('.btn-ver-mas-actividad').forEach((boton) => {
            boton.addEventListener('click', (e) => {
                e.stopPropagation();
                abrirModalActividad(parseInt(boton.dataset.id, 10));
            });
        });

        if (window.AOS) {
            AOS.refreshHard();
        }
    })
    .catch(() => {
        const contenedor = document.getElementById('grid-actividades');
        if (contenedor) {
            contenedor.outerHTML = '<p class="testimonios-vacio">No se pudieron cargar las actividades en este momento.</p>';
        }
    });
