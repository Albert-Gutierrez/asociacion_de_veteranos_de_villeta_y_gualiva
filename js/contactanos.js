// menu hamburguesa
const toggle = document.getElementById('menu-toggle');
const nav = document.getElementById('navbarNav');

toggle.addEventListener('click', () => {
    nav.classList.toggle('show');
});

//enviar datos a whatsapp
document.getElementById('formWhatsApp').addEventListener('submit', function(e) {
    e.preventDefault();

    let nombres = document.getElementById('nombres').value;
    let apellidos = document.getElementById('apellidos').value;
    let telefono = document.getElementById('telefono').value;
    let email = document.getElementById('email').value;
    let fuerza = document.getElementById('fuerza').value;
    let mensaje = document.getElementById('mensaje').value;

    let texto = `*Hola, deseo afiliarme.` + 
                `*Nuevo contacto desde la web ASOVEGU*%0A%0A` +
                `*Nombre:* ${nombres} ${apellidos}%0A` +
                `*Teléfono:* ${telefono}%0A` +
                `*Email:* ${email}%0A` +
                `*Fuerza:* ${fuerza}%0A` +
                `*Mensaje:* ${mensaje}`;

    let url = `https://wa.me/+57.3212281546?text=${texto}`;

    // Abrir WhatsApp
    window.open(url, '_blank');

    // Redirigir a página de gracias después de 1 segundo
    setTimeout(() => {
        window.location.href = "contactanos_gracias.html";
    }, 1000);
});






// document.getElementById('formWhatsApp').addEventListener('submit', function(e) {
//     e.preventDefault();

//     let nombres = document.getElementById('nombres').value;
//     let apellidos = document.getElementById('apellidos').value;
//     let telefono = document.getElementById('telefono').value;
//     let email = document.getElementById('email').value;
//     let fuerza = document.getElementById('fuerza').value;
//     let mensaje = document.getElementById('mensaje').value;

//     let texto = `*Hola, deseo afiliarme. Nuevo contacto desde la web ASOVEGU*%0A%0A` +
//                 `*Nombre:* ${nombres} ${apellidos}%0A` +
//                 `*Teléfono:* ${telefono}%0A` +
//                 `*Email:* ${email}%0A` +
//                 `*Fuerza:* ${fuerza}%0A` +
//                 `*Mensaje:* ${mensaje}`;

//     let url = `https://wa.me/573212281546?text=${texto}`;
//     window.open(url, '_blank');
// });

