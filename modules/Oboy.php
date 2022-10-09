<?php


class Oboy
{
    // DB Stuff
    private $conn;
    private string $table = 'rooms_category';

    // Construct
    public function __construct($db = null)
    {
        if (!$db) exit();
        $this->conn = $db;
    }

    // Read Category
    public function readCategories()
    {
        // Create query
        $query = 'SELECT * FROM ' . $this->table;

        // Execute query
        $result = $this->executeQuery($query);
        return json_encode($result);
    }

    //Read Rooms
    public function readRooms($categoryId = null)
    {
        if (!$this->conn || !$categoryId) return null;
        $this->table = 'rooms';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id='.$categoryId;

        // Execute query
        $result = $this->executeQuery($query);
        $data = array();
        foreach ($result as $row) {
            array_push($data, [
                'id' => $row['id'],
                'name' => $row['name'],
                'img' => $row['img'],
                'bgimg' => $row['bgimg'],
                'room_category_id' => $row['room_category_id'],
            ]);
        }

        return count($data)?json_encode($data):false;
    }

    //Read Oboy
    public function readOboy($categoryId = null)
    {
        if (!$this->conn || !$categoryId) return null;
        $this->table = 'oboyimages';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id='.$categoryId;

        // Execute query
        $result = $this->executeQuery($query);
        $data = array();
        foreach ($result as $row) {
            array_push($data, [
                'id' => $row['id'],
                'name' => $row['name'],
                'img' => $row['img'],
                'bgimg' => $row['bgimg'],
                'room_category_id' => $row['room_category_id'],
            ]);
        }

        return count($data)?json_encode($data):false;
    }

    private function executeQuery($query=null): ?array
    {
        if(!$query) return null;
        // Prepare statment
        $stmt = $this->conn->prepare($query);

        // Execute query
        $stmt->execute();

        $data = array();
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            array_push($data, $row);
        }
        return $data;
    }
}