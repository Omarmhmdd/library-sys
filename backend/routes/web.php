<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

function serveSpa(Request $request)
{
    $path = $request->path();
    $spaPath = public_path('spa');
    $file = $spaPath . '/' . $path;
    if ($path !== '' && $path !== '/' && file_exists($file) && is_file($file)) {
        return response()->file($file);
    }
    $index = $spaPath . '/index.html';
    if (! file_exists($index)) {
        return response('SPA not built. Run build and copy frontend/dist to public/spa.', 503);
    }
    return response()->file($index);
}

Route::get('/', function (Request $request) {
    return serveSpa($request);
});
Route::get('/{any}', function (Request $request) {
    return serveSpa($request);
})->where('any', '.*');
