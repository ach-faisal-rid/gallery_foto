<?php
namespace controllers;
require_once __DIR__ . '/../../model/users.php';
require_once __DIR__ . '/../../config/TokenJwt.php';

use Model\Users;
use Config\TokenJwt;

class AuthController
{
    public function registrasi() {

        // menerima request dari client content-type JSON
        $request = json_decode(file_get_contents('php://input'), true);
        
        // validasi request client
        if (empty($request['name']) || empty($request['email']) || empty($request['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Data tidak lengkap harus diisi']);
            exit();
        }

        // validasi email
        $model_users = new Users();
        $validasi_email = $model_users->findEmail($request['email']);
        if (is_array($validasi_email)) {
            http_response_code(409); // Conflict
            echo json_encode(['message' => 'Email sudah digunakan']);
            exit();
        }

        // enkripsi password
        $hashedPassword = password_hash($request['password'], PASSWORD_DEFAULT);
        $form_data = [
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => $hashedPassword
        ];

        // simpan data
        $result = $model_users->registrasi($form_data);

        // response
        if ($result) {
            http_response_code(201); // Created
            echo json_encode([
                'message' => 'Registrasi berhasil',
                'data' => $form_data
            ]);
        } else {
            http_response_code(500); // Internal Server Error
            echo json_encode(['message' => 'Registrasi gagal, terjadi kesalahan pada database saat proses registrasi']);
        }
    }

    public function login () {
        // menerima request dari client content-type JSON
        $request = json_decode(file_get_contents('php://input'), true);

        // Validasi input   
        if (empty($request['email']) || empty($request['password'])) {
            echo json_encode(['message' => 'Data tidak lengkap harus diisi']);
            http_response_code(400);
            exit();
        } 

        // validasi email yang SAMA
        $model_users = new Users();
        $verifikasi_email = $model_users->findEmail($request['email']);
        if ($verifikasi_email === false) {
            echo json_encode(['message' => 'login gagal, cek email dan password']);
            http_response_code(400);
            exit();
        }

        // Verifikasi password
        $verifikasi_password = password_verify($request['password'], $verifikasi_email['password']);
        if ($verifikasi_email === false) {
            echo json_encode(['message' => 'login gagal, cek email dan password']);
            http_response_code(400);
            exit();
        }

        // Password cocok, buat token auth (misalnya JWT atau token sederhana)
        $library_token = new TokenJwt();
        $token_baru = $library_token->create($verifikasi_email['id']);
        
        // hapus key password
        unset($verifikasi_email['password']);

        // Kirim respons sukses dengan token
        http_response_code(200);
        echo json_encode([
            'data' => $verifikasi_email,
            'message' => 'login berhasil',
            'token' => $token_baru,
        ]);
        exit();
    }

    public function getByToken()
    {
        // Get user data from token (this would typically be passed from middleware)
        $headers = getallheaders();
        if (!isset($headers['Authorization'])) {
            http_response_code(401);
            echo json_encode(['message' => 'Authorization header tidak ditemukan']);
            exit();
        }

        $auth_header = $headers['Authorization'];
        $token = substr($auth_header, 7); // Remove 'Bearer ' prefix

        // Verify token and get user ID
        $library_token = new TokenJwt();
        $decoded = $library_token->verify($token);

        if (!$decoded) {
            http_response_code(401);
            echo json_encode(['message' => 'Token tidak valid atau telah kedaluwarsa']);
            exit();
        }

        // Get user data by ID
        $model_users = new Users();
        $user_data = $model_users->findById($decoded['user_id']);

        if (!$user_data) {
            http_response_code(404);
            echo json_encode(['message' => 'User tidak ditemukan']);
            exit();
        }

        // Remove password from response
        unset($user_data['password']);

        // Send success response
        http_response_code(200);
        echo json_encode([
            'message' => 'Data user berhasil diambil',
            'data' => $user_data
        ]);
        exit();
    }


}
