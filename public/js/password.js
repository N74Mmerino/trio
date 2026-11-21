// Botón "ojito" para mostrar u ocultar la contraseña.
// Se agrega solo con JS: sin JS el campo queda como un input de contraseña común.
// Toma todos los <input type="password"> de la página, así que alcanza con incluir este archivo.

(function () {
  const OJO = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M2 12s3.6-7 10-7 10 7 10 7-3.6 7-10 7S2 12 2 12Z" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/><circle cx="12" cy="12" r="3" fill="none" stroke="currentColor" stroke-width="1.8"/></svg>';
  const OJO_TACHADO = '<svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10.6 5.1A10.8 10.8 0 0 1 12 5c6.4 0 10 7 10 7a17.6 17.6 0 0 1-2.9 3.9M6.6 6.6A17.4 17.4 0 0 0 2 12s3.6 7 10 7a10 10 0 0 0 5.4-1.6M9.9 9.9a3 3 0 0 0 4.2 4.2M3 3l18 18" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>';

  document.querySelectorAll('input[type="password"]').forEach((input) => {
    const contenedor = document.createElement('div');
    contenedor.className = 'password-field';
    input.parentNode.insertBefore(contenedor, input);
    contenedor.appendChild(input);

    const boton = document.createElement('button');
    boton.type = 'button'; // Que no envíe el formulario.
    boton.className = 'password-toggle';
    boton.setAttribute('aria-label', 'Mostrar contraseña');
    if (input.id) {
      boton.setAttribute('aria-controls', input.id);
    }
    contenedor.appendChild(boton);

    function pintar(visible) {
      input.type = visible ? 'text' : 'password';
      boton.innerHTML = visible ? OJO_TACHADO : OJO;
      boton.setAttribute('aria-pressed', visible ? 'true' : 'false');
      boton.title = visible ? 'Ocultar contraseña' : 'Mostrar contraseña';
    }

    boton.addEventListener('click', () => {
      // Se guarda la posición del cursor: cambiar el type la puede mover en algunos navegadores.
      const inicio = input.selectionStart;
      const fin = input.selectionEnd;
      pintar(input.type === 'password');
      input.focus();
      try {
        input.setSelectionRange(inicio, fin);
      } catch (e) {
        // Algunos navegadores no permiten setSelectionRange en ciertos tipos: no es grave.
      }
    });

    // Al enviar se vuelve a ocultar, para que el navegador no la guarde como texto común.
    if (input.form) {
      input.form.addEventListener('submit', () => pintar(false));
    }

    pintar(false);
  });
})();
