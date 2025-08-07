<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Rute di sini bersifat stateless dan secara default menggunakan grup
| middleware 'api'. Rute ini ideal untuk otentikasi berbasis token
| (misalnya untuk aplikasi mobile), bukan untuk SPA berbasis sesi.
|
*/

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});
