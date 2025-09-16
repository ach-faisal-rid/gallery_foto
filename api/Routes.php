<?php

//file Routes.php digunakan untuk list sebuah endpoint

require_once __DIR__ . '/../config/Route.php';
require_once __DIR__ .'/controllers/AuthController.php';

use Config\Route;
use controllers\AuthController;

// Determine base URL dynamically so routes work regardless of folder name or host
$scriptName = dirname($_SERVER['SCRIPT_NAME']);
$base_url = rtrim($scriptName, "\\/");

// Register a GET route for /api/registrasi (for testing)
Route::get($base_url . "/api/registrasi", function () {
    echo json_encode([
        "message"=> "ini registrasi"
    ]);
});

// Example: if you expect a POST for registration, register it too
Route::post( $base_url . "/api/auth/registrasi", function () {
    $controller = new AuthController();
    $controller->registrasi();
});

// Run the router
Route::run();
