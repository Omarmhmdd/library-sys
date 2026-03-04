<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/** MIME types for SPA assets so browsers accept module scripts and styles */
const SPA_MIME_TYPES = [
    'js' => 'application/javascript; charset=UTF-8',
    'mjs' => 'application/javascript; charset=UTF-8',
    'css' => 'text/css; charset=UTF-8',
    'json' => 'application/json',
    'svg' => 'image/svg+xml',
    'ico' => 'image/x-icon',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
];

function serveSpa(Request $request)
{
    $path = $request->path();
    $spaPath = public_path('spa');
    $file = $spaPath . '/' . $path;
    if ($path !== '' && $path !== '/' && file_exists($file) && is_file($file)) {
        $ext = strtolower(pathinfo($file, PATHINFO_EXTENSION));
        $mime = SPA_MIME_TYPES[$ext] ?? 'application/octet-stream';
        // Serve with explicit Content-Type so browser accepts module scripts (BinaryFileResponse can send text/plain)
        return response(file_get_contents($file), 200, [
            'Content-Type' => $mime,
            'Cache-Control' => 'public, max-age=31536000',
        ]);
    }
    $index = $spaPath . '/index.html';
    if (! file_exists($index)) {
        return response('SPA not built. Run build and copy frontend/dist to public/spa.', 503);
    }
    return response(file_get_contents($index), 200, [
        'Content-Type' => 'text/html; charset=UTF-8',
    ]);
}

Route::get('/', function (Request $request) {
    return serveSpa($request);
});
Route::get('/{any}', function (Request $request) {
    return serveSpa($request);
})->where('any', '.*');
