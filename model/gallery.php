<?php
namespace Model;

require_once __DIR__ . '/../config/Database.php';

use Config\{Database};
use PDOException;

class Gallery extends Database
{
    private $table_name = 'gallery';
    private $db;

    public $id;
    public $title;
    public $deskripsi;
    public $file;
    public $created_at;
    public $updated_at;
    public $author_id;

    public function __construct()
    {
        $this->db = new Database();
    }

    public function getAll()
    {
        $this->db->query("SELECT * FROM $this->table_name ORDER BY created_at DESC");
        return $this->db->resultSet();
    }

    public function findById($id)
    {
        $this->db->query("SELECT * FROM $this->table_name WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->single();
    }

    public function create($data)
    {
        try {
            $this->db->query("INSERT INTO $this->table_name (title, deskripsi, file, author_id, created_at) VALUES (:title, :deskripsi, :file, :author_id, :created_at)");
            $this->db->bind('title', $data['title']);
            $this->db->bind('deskripsi', $data['deskripsi'] ?? null);
            $this->db->bind('file', $data['file'] ?? null);
            $this->db->bind('author_id', $data['author_id'] ?? null);
            $this->db->bind('created_at', date('Y-m-d H:i:s'));

            $res = $this->db->execute();
            if ($res) {
                $id = $this->db->lastInsertId();
                return $this->findById($id);
            }
            return false;
        } catch (PDOException $e) {
            return $e;
        }
    }

    public function update($id, $data)
    {
        $fields = [];
        $params = [];
    if (isset($data['title'])) { $fields[] = 'title = :title'; $params['title'] = $data['title']; }
    if (isset($data['deskripsi'])) { $fields[] = 'deskripsi = :deskripsi'; $params['deskripsi'] = $data['deskripsi']; }
    if (isset($data['file'])) { $fields[] = 'file = :file'; $params['file'] = $data['file']; }
    if (isset($data['author_id'])) { $fields[] = 'author_id = :author_id'; $params['author_id'] = $data['author_id']; }

        if (empty($fields)) return false;

        $query = "UPDATE $this->table_name SET " . implode(', ', $fields) . ", updated_at = :updated_at WHERE id = :id";
        $this->db->query($query);
        foreach ($params as $k => $v) { $this->db->bind($k, $v); }
        $this->db->bind('updated_at', date('Y-m-d H:i:s'));
        $this->db->bind('id', $id);
        return $this->db->execute();
    }

    public function delete($id)
    {
        $this->db->query("DELETE FROM $this->table_name WHERE id = :id");
        $this->db->bind('id', $id);
        return $this->db->execute();
    }
}
