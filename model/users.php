<?php
namespace Model;

require_once __DIR__ . '/../config/Database.php';

use Config\{Database};
use PDOException;

class Users extends Database
{
    private $table_name = 'users';
    private $db;

    public $id;
    public $name;
    public $email;
    public $password;
    public $created_at;
    public $updated_at;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function findEmail($email) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE email=:email";
        $this->db->query($query);
        $this->db->bind('email', $email);
        return $this->db->single();
    }

    public function findById($id) {
        $query = "SELECT * FROM " . $this->table_name . " WHERE id=:id";
        $this->db->query($query);
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    /**
     * form_data [name,email,password(has)]
    */
    public function registrasi($form_data)
    {
        try {
        $query = "INSERT INTO $this->table_name (name, email, password, created_at) VALUES (:name, :email, :password, :created_at)";
        $this->db->query($query);
        $this->db->bind('name', $form_data['name']);
        $this->db->bind('email', $form_data['email']);
        $this->db->bind('password', $form_data['password']);
        $this->db->bind('created_at', date("Y-m-d H:i:s"));

        $res = $this->db->execute();

        if ($res) {
            $user_id = $this->db->lastInsertId();
            // Mengambil data yang baru disimpan
            $this->db->query("SELECT * FROM $this->table_name WHERE id = :id");
            $this->db->bind('id', $user_id);
            return $this->db->single();
        } else {
            return false;
        }
        } catch (PDOException $exception) {
            return $exception;
        }
    }

    // Ambil semua user
    public function getAll()
    {
        $this->db->query("SELECT * FROM $this->table_name");
        return $this->db->resultSet();
    }

    // Update user (partial)
    public function update($id, $data)
    {
        $fields = [];
        $params = [];
        if (isset($data['name'])) {
            $fields[] = 'name = :name';
            $params['name'] = $data['name'];
        }
        if (isset($data['email'])) {
            $fields[] = 'email = :email';
            $params['email'] = $data['email'];
        }
        if (isset($data['password'])) {
            $fields[] = 'password = :password';
            $params['password'] = $data['password'];
        }

        if (empty($fields)) return false;

        $query = "UPDATE $this->table_name SET " . implode(', ', $fields) . " , updated_at = :updated_at WHERE id = :id";
        $this->db->query($query);
        foreach ($params as $k => $v) {
            $this->db->bind($k, $v);
        }
        $this->db->bind('updated_at', date('Y-m-d H:i:s'));
        $this->db->bind('id', $id);

        return $this->db->execute();
    }

    // Hapus user
    public function delete($id)
    {
        $this->db->query("DELETE FROM $this->table_name WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->execute();
    }

}
