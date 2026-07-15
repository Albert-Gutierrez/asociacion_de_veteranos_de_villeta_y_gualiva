// menu hamburguesa
const toggle = document.getElementById('menu-toggle');
const nav = document.getElementById('navbarNav');

toggle.addEventListener('click', () => {
    nav.classList.toggle('show');
});

// Voltear tarjetas de actividades al tocar (pantallas táctiles sin hover)
document.querySelectorAll('.tarjeta-actividad').forEach((tarjeta) => {
    tarjeta.addEventListener('click', () => {
        tarjeta.classList.toggle('volteada');
    });
});
