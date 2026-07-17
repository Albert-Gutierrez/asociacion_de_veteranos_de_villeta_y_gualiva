// menu hamburguesa
const toggle = document.getElementById('menu-toggle');
const nav = document.getElementById('navbarNav');

toggle.addEventListener('click', () => {
    nav.classList.toggle('show');
});

// Pestañas Misión / Visión / Valores / Objetivo
document.querySelectorAll('.mv-tab-boton').forEach((boton) => {
    boton.addEventListener('click', () => {
        document.querySelectorAll('.mv-tab-boton').forEach((b) => b.classList.remove('activo'));
        document.querySelectorAll('.mv-panel').forEach((p) => p.classList.remove('activo'));

        boton.classList.add('activo');
        document.getElementById('mv-panel-' + boton.dataset.panel).classList.add('activo');
    });
});

// Testimonios de asociados: se cargan desde la base de datos (solo los
// aprobados por un administrador); si todavía no hay ninguno, se muestra
// un mensaje en vez de un carrusel vacío.
fetch('testimonios_publicos.php')
    .then((respuesta) => respuesta.json())
    .then((datos) => {
        const contenedor = document.getElementById('slider-testimonios');
        const testimonios = datos.testimonios || [];

        if (testimonios.length === 0) {
            contenedor.outerHTML = '<p class="testimonios-vacio">Todavía no hay testimonios publicados. ¡Sé el primero desde tu portal de afiliado!</p>';
            return;
        }

        contenedor.innerHTML = testimonios.map((t) => {
            const foto = t.foto
                ? '<img src="' + t.foto + '" alt="Foto de ' + escaparHtml(t.nombre) + '">'
                : '<i class="fas fa-user-circle" aria-hidden="true"></i>';
            return '<div class="card-testimonio">'
                + '<div class="cont-img">' + foto + '</div>'
                + '<div class="cont-info">'
                + '<h3>' + escaparHtml(t.nombre) + '</h3>'
                + '<p>' + escaparHtml(t.mensaje) + '</p>'
                + '</div>'
                + '</div>';
        }).join('');

        $('.slider-testimonios').slick({
            slidesToShow: Math.min(3, testimonios.length),
            slidesToScroll: 1,
            infinite: testimonios.length > 1,
            arrows: true,
            dots: true,
            autoplay: true,
            autoplaySpeed: 2500,
            responsive: [
                { breakpoint: 1024, settings: { slidesToShow: Math.min(2, testimonios.length) } },
                { breakpoint: 768, settings: { slidesToShow: 1 } },
            ],
        });
    })
    .catch(() => {
        const contenedor = document.getElementById('slider-testimonios');
        contenedor.outerHTML = '<p class="testimonios-vacio">No se pudieron cargar los testimonios en este momento.</p>';
    });

function escaparHtml(texto) {
    const div = document.createElement('div');
    div.textContent = texto;
    return div.innerHTML;
}
