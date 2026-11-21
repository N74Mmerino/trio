// Requisitos de contraseña en vivo (Etapa 13).
// La política vive en Validador::REQUISITOS_PASSWORD y la vista la publica en cada <li data-patron>.
// Este archivo solo la lee y pinta estados; la validación que manda es la de PHP.

(function () {
  const password = document.getElementById('password');
  // Etapa 19: el formulario es el que contiene el campo (antes: form.auth-form), así también sirve en "Mi perfil".
  const form = password ? password.closest('form') : null;
  const confirmacion = document.getElementById('password_confirmacion');
  const lista = document.getElementById('password-requisitos');
  const coincidencia = document.getElementById('password-coincidencia');

  if (!form || !password || !confirmacion || !lista || !coincidencia) {
    return;
  }

  const ETIQUETAS = { pendiente: '(pendiente)', cumple: '(cumple)', 'no-cumple': '(no cumple)' };
  const ESTADOS = ['is-pendiente', 'is-cumple', 'is-no-cumple'];

  let requisitos;

  try {
    requisitos = Array.from(lista.querySelectorAll('li[data-patron]')).map((li) => ({
      li,
      etiqueta: li.querySelector('.req-estado'),
      patron: new RegExp(li.dataset.patron, 'u'),
    }));
  } catch (e) {
    return; // Navegador sin soporte para estos patrones: queda la versión PHP.
  }

  // "Intentado" = ya se trató de enviar (en esta carga o en la anterior, según PHP).
  let intentado = lista.dataset.intentado === '1';

  const estadoDe = (cumple) => (cumple ? 'cumple' : (intentado ? 'no-cumple' : 'pendiente'));

  function actualizarRequisitos() {
    let todos = true;

    requisitos.forEach((r) => {
      const cumple = r.patron.test(password.value);
      const estado = estadoDe(cumple);
      todos = todos && cumple;
      r.li.classList.remove(...ESTADOS);
      r.li.classList.add('is-' + estado);
      if (r.etiqueta) {
        r.etiqueta.textContent = ETIQUETAS[estado];
      }
    });

    password.classList.toggle('is-invalid', intentado && !todos);
    // Vacío: lo resuelve "required" con su propio mensaje.
    password.setCustomValidity(password.value === '' || todos ? '' : 'La contraseña no cumple los requisitos.');
  }

  function actualizarCoincidencia() {
    if (confirmacion.value === '') {
      coincidencia.hidden = true;
      confirmacion.setCustomValidity('');
      return;
    }

    const coinciden = confirmacion.value === password.value;
    const texto = coinciden ? 'Las contraseñas coinciden' : 'Las contraseñas no coinciden';

    coincidencia.hidden = false;
    if (coincidencia.textContent !== texto) {
      coincidencia.textContent = texto; // Solo si cambia, para no repetir el anuncio del lector de pantalla.
    }
    coincidencia.className = 'match-msg is-' + estadoDe(coinciden);
    confirmacion.setCustomValidity(coinciden ? '' : 'Las contraseñas no coinciden.');
  }

  password.addEventListener('input', () => {
    actualizarRequisitos();
    actualizarCoincidencia();
  });
  confirmacion.addEventListener('input', actualizarCoincidencia);

  // "invalid" no burbujea: se escucha en captura. Lo dispara el navegador al frenar un envío.
  form.addEventListener('invalid', () => {
    if (!intentado) {
      intentado = true;
      actualizarRequisitos();
      actualizarCoincidencia();
    }
  }, true);
})();
