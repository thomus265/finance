<?php

class Attachment {
    private $db;
    
    public function __construct() {
        $this->db = new Database();
    }

    public function add_attachment($file_path, $file_type) {
        return $this->db->query(
            "INSERT INTO attachments (file_path, file_type, stamp) VALUES (?, ?, current_timestamp())",
            $file_path, $file_type
        );
    }

    public function get_last_id() {
        return $this->db->lastInsertID();
    }

    public function get_attachments() {
        return $this->db->query("SELECT * FROM attachments")->fetchAll();
    }

    public function get_attachment($id) {
        return $this->db->query("SELECT * FROM attachments WHERE id = ?", $id)->fetchArray();
    }

    public function update_attachment($id, $file_path, $file_type) {
        return $this->db->query(
            "UPDATE attachments SET file_path = ?, file_type = ? WHERE id = ?",
            $file_path, $file_type, $id
        );
    }

    public function delete_attachment($id) {
        return $this->db->query("DELETE FROM attachments WHERE id = ?", $id);
    }
}
