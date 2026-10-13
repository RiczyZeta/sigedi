<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// El webhook de WhatsApp está en routes/web.php (/webhook/whatsapp), protegido con la firma de Meta
// (VerificarFirmaWhatsApp, RNF02). No se duplica aquí: una ruta sin esa validación aceptaría mensajes falsos.
