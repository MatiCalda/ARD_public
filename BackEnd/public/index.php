<?php
date_default_timezone_set('America/Argentina/Buenos_Aires');

$allowedOrigins = [
    'http://localhost',
    'https://tu-dominio.com'
];

$origin = $_SERVER['HTTP_ORIGIN'] ?? null;
$isAllowedOrigin = $origin && in_array($origin, $allowedOrigins, true);

// Preflight (OPTIONS) primero
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    if ($isAllowedOrigin) {
        header("Access-Control-Allow-Origin: $origin");
        header("Vary: Origin, Access-Control-Request-Method, Access-Control-Request-Headers");

        // Si querés reflejar lo pedido por el browser:
        if (!empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD'])) {
            header('Access-Control-Allow-Methods: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_METHOD']);
        } else {
            header('Access-Control-Allow-Methods: GET, POST, PUT, PATCH, DELETE, OPTIONS');
        }
        if (!empty($_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS'])) {
            header('Access-Control-Allow-Headers: ' . $_SERVER['HTTP_ACCESS_CONTROL_REQUEST_HEADERS']);
        } else {
            header('Access-Control-Allow-Headers: Authorization, Content-Type, X-Requested-With');
        }

        // Cacheá la respuesta del preflight
        header('Access-Control-Max-Age: 86400');

        // Habilitá si vas a usar cookies/autenticación basada en cookies
        // header('Access-Control-Allow-Credentials: true');

        http_response_code(204); // No Content
    } else {
        // Origin no permitido: explícito
        http_response_code(403);
    }
    exit;
}

// Respuestas "normales": sólo poner CORS si el origin está permitido
if ($isAllowedOrigin) {
    header("Access-Control-Allow-Origin: $origin");
    header("Vary: Origin");
    // header('Access-Control-Allow-Credentials: true'); // si usás cookies
    // header('Access-Control-Expose-Headers: Authorization'); // si necesitás exponer headers al JS
}

// Sugerido: contenido JSON por defecto (ajustá según tu output real)
header('Content-Type: application/json; charset=utf-8');

require __DIR__ . '/../src/App/App.php';
