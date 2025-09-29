<?php
namespace controllers;

require_once __DIR__ . '/../../model/users.php';

use Model\Users;

class UsersController
{
    // GET /api/users
    public function index()
    {
    $model = new Users();
    $users = $model->getAll();

        // Remove passwords
        foreach ($users as &$u) {
            if (isset($u['password'])) unset($u['password']);
        }

        http_response_code(200);
        echo json_encode(['message' => 'Daftar user', 'data' => $users]);
        exit();
    }

    // GET /api/users/{id}
    public function show($id)
    {
        $model = new Users();
        $user = $model->findById($id);
        if (!$user) {
            http_response_code(404);
            echo json_encode(['message' => 'User tidak ditemukan']);
            exit();
        }
        unset($user['password']);
        http_response_code(200);
        echo json_encode(['message' => 'Detail user', 'data' => $user]);
        exit();
    }

    // POST /api/users
    public function create()
    {
        $request = json_decode(file_get_contents('php://input'), true);
        if (!$request || empty($request['name']) || empty($request['email']) || empty($request['password'])) {
            http_response_code(400);
            echo json_encode(['message' => 'Data tidak lengkap']);
            exit();
        }

        $model = new Users();
        if ($model->findEmail($request['email'])) {
            http_response_code(409);
            echo json_encode(['message' => 'Email sudah digunakan']);
            exit();
        }

        $data = [
            'name' => $request['name'],
            'email' => $request['email'],
            'password' => password_hash($request['password'], PASSWORD_DEFAULT)
        ];

        $res = $model->registrasi($data);
        if ($res && is_array($res)) {
            unset($res['password']);
            http_response_code(201);
            echo json_encode(['message' => 'User dibuat', 'data' => $res]);
            exit();
        }

        http_response_code(500);
        echo json_encode(['message' => 'Gagal membuat user']);
        exit();
    }

    // PUT /api/users/{id}
    public function update($id)
    {
        $request = json_decode(file_get_contents('php://input'), true);
        if (!$request) {
            http_response_code(400);
            echo json_encode(['message' => 'Invalid JSON']);
            exit();
        }

        $model = new Users();
        $existing = $model->findById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'User tidak ditemukan']);
            exit();
        }

        // Build update data
        $updateData = [];
        if (!empty($request['name'])) $updateData['name'] = $request['name'];
        if (!empty($request['email'])) $updateData['email'] = $request['email'];
        if (!empty($request['password'])) $updateData['password'] = password_hash($request['password'], PASSWORD_DEFAULT);

        $res = $model->update($id, $updateData);
        if ($res) {
            $user = $model->findById($id);
            unset($user['password']);
            http_response_code(200);
            echo json_encode(['message' => 'User diperbarui', 'data' => $user]);
            exit();
        }

        http_response_code(500);
        echo json_encode(['message' => 'Gagal memperbarui user']);
        exit();
    }

    // DELETE /api/users/{id}
    public function delete($id)
    {
        $model = new Users();
        $existing = $model->findById($id);
        if (!$existing) {
            http_response_code(404);
            echo json_encode(['message' => 'User tidak ditemukan']);
            exit();
        }

        $res = $model->delete($id);
        if ($res) {
            http_response_code(200);
            echo json_encode(['message' => 'User dihapus']);
            exit();
        }

        http_response_code(500);
        echo json_encode(['message' => 'Gagal menghapus user']);
        exit();
    }
}
