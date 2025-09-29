<?php

//file Routes.php digunakan untuk list sebuah endpoint

require_once __DIR__ . '/../config/Route.php';
require_once __DIR__ .'/controllers/AuthController.php';
require_once __DIR__ .'/controllers/UsersController.php';
require_once __DIR__ . '/../model/Users.php';
require_once __DIR__ . "/AuthMiddleware.php";

use Config\Route;
use Config\TokenJwt;
use Model\Users;
use controllers\AuthController;

// CORS headers - Allow cross-origin requests from frontend
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Authorization, X-Requested-With');
header('Access-Control-Max-Age: 86400'); // 24 hours

// Handle preflight OPTIONS requests
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') {
    http_response_code(200);
    exit();
}

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

// Example: if you expect a POST for login, login it too
Route::post( $base_url . "/api/auth/login", function () {
    $controller = new AuthController();
    $controller->login();
});


/**
 * api auth current user
 */
Route::get($base_url . '/api/auth/current', function () {
    require_once __DIR__ . '/controllers/AuthController.php';
    AuthMiddleware::authenticate();
    $controller = new AuthController();
    $controller->getByToken();
});

// Users CRUD routes
Route::get($base_url . '/api/users', function () {
    $controller = new \controllers\UsersController();
    $controller->index();
});

Route::get($base_url . '/api/users/{id}', function ($id) {
    $controller = new \controllers\UsersController();
    $controller->show($id);
});

Route::post($base_url . '/api/users', function () {
    $controller = new \controllers\UsersController();
    $controller->create();
});

Route::put($base_url . '/api/users/{id}', function ($id) {
    $controller = new \controllers\UsersController();
    $controller->update($id);
});

Route::delete($base_url . '/api/users/{id}', function ($id) {
    $controller = new \controllers\UsersController();
    $controller->delete($id);
});

// Run the router
Route::run();

