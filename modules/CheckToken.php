<?php


class CheckToken
{
    private $conn;
    private $table = 'app_token';

    public function __construct($db=null) {
        if(!$db) return null;
        $this->conn = $db;
    }

    public function check($Authorization) {
        if(isset($Authorization)) {
            list($type, $data) = explode(" ", $Authorization);
            if(strcasecmp($type, "Bearer") === 0) {
                return $this->handleCheck($data);
            }
        }
        return null;
    }

    private function handleCheck($token) {
        if(!$this->conn) return null;
        $query = 'SELECT token FROM '. $this->table;

        // Prepare statment
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($data, $row);
        }
        return $data[0]['token'] === $token;
    }
}