<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Cross-Origin Resource Sharing (CORS) Configuration
    |--------------------------------------------------------------------------
    |
    | Configura CORS para el consumo de la API desde la tienda/escáner. En
    | producción, restringir `allowed_origins` a los dominios reales (ver
    | docs/deployment.md). Por defecto permite cualquier origen para facilitar
    | la integración en dev.
    |
    */

    'paths' => ['api/*'],

    'allowed_methods' => ['*'],

    'allowed_origins' => ['*'],

    'allowed_origins_patterns' => [],

    'allowed_headers' => ['*'],

    'exposed_headers' => [],

    'max_age' => 0,

    'supports_credentials' => false,

];
