<?php

require_once __DIR__ . '/../config/TokenJwt.php';

use Config\TokenJwt;

class AuthMiddleware
{
    public static function authenticate()
    {
        // Check if Authorization header exists
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header tidak ditemukan']);
            exit();
        }

        // Extract token from Authorization header (format: Bearer <token>)
        $auth_header = $headers['Authorization'];
        if (strpos($auth_header, 'Bearer ') !== 0) {
            http_response_code(401);
            echo json_encode(['message' => 'Format token tidak valid']);
            exit();
        }

        $token = substr($auth_header, 7); // Remove 'Bearer ' prefix

        // Verify token
        $tokenJwt = new TokenJwt();
        $decoded = $tokenJwt->verify($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['message' => 'Token tidak valid atau telah kedaluwarsa']);
            exit();
        }

        return $decoded;
    }
}
