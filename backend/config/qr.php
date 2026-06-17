<?php

return [

    /*
    | Versión de clave HMAC actualmente usada para FIRMAR nuevos QR.
    | Rotación: subir este número y añadir el nuevo secreto al mapa `secrets`,
    | dejando los anteriores para que los tickets viejos sigan verificando.
    */
    'current_version' => (int) env('QR_KEY_VERSION', 1),

    /*
    | Mapa version => secreto HMAC. NUNCA exponer estos valores.
    | Cada entrada permite verificar tokens firmados con esa versión.
    */
    'secrets' => [
        1 => env('QR_SECRET', ''),
        // 2 => env('QR_SECRET_V2'),
    ],

    /*
    | Ventana (segundos) anti-rebote en validación de puerta: una segunda
    | lectura del mismo code dentro de esta ventana por el mismo operador se
    | trata como repetición benigna (no cuenta como already_used).
    */
    'scan_rebounce_seconds' => (int) env('QR_SCAN_REBOUNCE_SECONDS', 5),

    /*
    | Tamaño en px del PNG generado para el QR.
    */
    'image_size' => (int) env('QR_IMAGE_SIZE', 300),
];
