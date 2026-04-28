<?php
/**
 * router.php - Request router for Railway
 * Used by Procfile: php -S 0.0.0.0:$PORT router.php
 */

require_once __DIR__ . '/boot.php';

// Static files
if (preg_match('/\.(?:css|js|jpg|png|gif|ico|woff|woff2|ttf|svg)$/i', $_SERVER['REQUEST_URI'])) {
    return false;
}

// Route to requested file
$request_uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$file = __DIR__ . $request_uri;

// Remove query string
if (strpos($file, '?') !== false) {
    $file = substr($file, 0, strpos($file, '?'));
}

// Default to index.php
if ($request_uri === '/' || !file_exists($file)) {
    include __DIR__ . '/index.php';
    return;
}

// Include the file
if (is_file($file)) {
    include $file;
    return;
}

// 404
http_response_code(404);
echo "404 Not Found";
?>
