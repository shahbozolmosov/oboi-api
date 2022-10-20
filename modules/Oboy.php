<?php


class Oboy
{
    // DB Stuff
    private $conn;
    private $table = 'rooms_category';
    private $imageFolder = '../../oboy-images-room/';

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
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY id DESC ';

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
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';

        // Execute query
        $result = $this->executeQuery($query);

        $data = array();
        foreach ($result as $row) {
            // Convert Image to base64
            $bgImage = $this->convertImage("rooms/", $row['bgimg']);
            array_push($data, [
                'id' => $row['id'],
                'img' => 'http://oboi-api/oboy-images-room/rooms/'.$row['img'],
                'bgimg' => $bgImage,
                'room_category_id' => $row['room_category_id'],
            ]);
        }

        return count($data) ? json_encode($data) : false;
    }

    //Read Oboy
    public function readOboy($categoryId = null)
    {
        if (!$this->conn || !$categoryId) return null;
        $this->table = 'oboyimages';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';

        // Execute query
        $result = $this->executeQuery($query);

        $data = array();
        foreach ($result as $row) {
            // Convert Image to base64
            $image = $this->convertImage("oboys/", $row['img']);
            array_push($data, [
                'id' => $row['id'],
                'name' => $row['name'],
                'img' => $image,
                'article' => $row['article'],
                'room_id' => $row['room_id'],
                'room_category_id' => $row['room_category_id'],
            ]);
        }

        return count($data) ? json_encode($data) : false;
    }

    // Convert Image to base64
    private function convertImage($folder = null, $image = null)
    {
        if (!$folder || !$image) return null;
        $path = $this->imageFolder;
        $path .= $folder;
        $path .= $image;

        $ext = pathinfo($path, PATHINFO_EXTENSION);
        $data = file_get_contents($path);
        $base64 = 'data:image/' . $ext . ';base64,' . base64_encode($data);
        return $base64;
    }

    // Execute Query
    private function executeQuery($query = null): ?array
    {
        if (!$query) return null;
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