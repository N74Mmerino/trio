<?php
// Etapa 17: ícono de cada servicio (por id_servicio, fijo en el script SQL: 1 Luz, 2 Agua, 3 Gas).
// Etapa 18: cada ícono es un círculo del color del servicio con su símbolo, igual que en el logo Trío.
// Se usa con: $iconos = require APP_PATH . 'views/consumo/iconos.php';
return [
    1 => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#ffcf4d" /><path transform="translate(6.2 6) scale(.5)" d="M13 2 3 14h7v8l10-12h-7z" fill="#4d3c08" /></svg>',
    2 => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#6fb3e6" /><path transform="translate(6 5.6) scale(.5)" d="M12 2.5C12 2.5 5 10.2 5 14.8a7 7 0 0 0 14 0C19 10.2 12 2.5 12 2.5z" fill="#0f3554" /></svg>',
    3 => '<svg viewBox="0 0 24 24"><circle cx="12" cy="12" r="12" fill="#f39a5f" /><path transform="translate(6 6) scale(.5)" d="M12 2C13 6 18 8.5 18 14a6 6 0 0 1-12 0c0-3 1.6-4.8 3-6 .2 2 1 3.2 2.2 3.6C10.6 8.6 11 5 12 2z" fill="#5a2508" /></svg>',
];
