<?php

use LDAP\Result;

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
    public function readCategories($limit = null, $page = null)
    {
        $this->table = 'rooms_category';
        
        // Create query
        $query = 'SELECT * FROM ' . $this->table . ' ORDER BY id DESC';

        $data = array();
        
        if ($limit || $page) {
            $query = 'SELECT * FROM ' . $this->table . ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . ($page - 1) * $limit;
            $countQuery = 'SELECT COUNT(*) as "count" FROM ' . $this->table .' ORDER BY id DESC ';
            $stmt = $this->conn->prepare($countQuery);
            
            if($stmt->execute()){
                $rows = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $rows['count']; 
                $data['pages'] = ceil($count / $limit);
                $data['active_page'] = empty($page)?'1':$page;
            }
        }

        // Execute query
        $data['categories'] = $this->executeQuery($query);

        return json_encode($data);
    }
    // Read Single Room
    public function readSingleRoom($id) 
    {
        if (!$this->conn || !$id) return null;
        $this->table = 'rooms';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE id=' . $id;
        $data = array();

        // Execute query
        $result = $this->executeQuery($query);
        $result = $result[0];
        // Convert Image to base64
        $image = $this->convertImage("rooms/", $result['img']);
        $bgImage = $this->convertImage("rooms/", $result['bgimg']);
        $data['room'] = [
            'id' => $result['id'],
            'img' => $image,
            'bgimg' => $bgImage,
            'room_category_id' => $result['room_category_id'],
        ];
        
        return count($data['room']) ? json_encode($data) : false;
    }
    //Read Rooms
    public function readRooms($categoryId = null, $limit = null, $page = null)
    {
        if (!$this->conn || !$categoryId) return null;
        $this->table = 'rooms';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';
        $data = array();
        
        if ($limit || $page) {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . ($page - 1) * $limit;
            $countQuery = 'SELECT COUNT(*) as "count" FROM ' . $this->table .' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';
            $stmt = $this->conn->prepare($countQuery);
            
            if($stmt->execute()){
                $rows = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $rows['count']; 
                $data['pages'] = ceil($count / $limit);
                $data['active_page'] = empty($page)?'1':$page;
            }
        }
        // Execute query
        $result = $this->executeQuery($query);

        foreach ($result as $row) {
            // Convert Image to base64
            $image = $this->convertImage("rooms/", $row['img']);
            $bgImage = $this->convertImage("rooms/", $row['bgimg']);
            $data['rooms'][] = [
                'id' => $row['id'],
                'img' => $image,
                'bgimg' => $bgImage,
                'room_category_id' => $row['room_category_id'],
            ];

        }

        return count($data['rooms']) ? json_encode($data) : false;
    }

    //Read Oboy
    public function readOboy($categoryId = null, $limit = null, $page = null)
    {
        if (!$this->conn || !$categoryId) return null;
        $this->table = 'oboyimages';
        // Create Query
        $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';
        $data = array();
        
        if ($limit || $page) {
            $query = 'SELECT * FROM ' . $this->table . ' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC LIMIT ' . $limit . ' OFFSET ' . ($page - 1) * $limit;
            $countQuery = 'SELECT COUNT(*) as "count" FROM ' . $this->table .' WHERE room_category_id=' . $categoryId . ' ORDER BY id DESC ';
            $stmt = $this->conn->prepare($countQuery);
            
            if($stmt->execute()){
                $rows = $stmt->fetch(PDO::FETCH_ASSOC);
                $count = $rows['count']; 
                $data['pages'] = ceil($count / $limit);
                $data['active_page'] = empty($page)?'1':$page;
            }
        }

        // Execute query
        $result = $this->executeQuery($query);

        foreach ($result as $row) {
            // Get currency
            $usd = $this->getCurrency('usd', $row['article']);
            $price = $this->getOboyPrice($row['article']);
            // $price = ($price * 1.37 * $usd);
            // $price = ($price['price'] * 1.37 * $usd);
            // Get Firma
            $firma = $this->getFirma($row['article']);
            // Convert Image to base64
            $image = $this->convertImage("oboys/", $row['img']);
            $data['oboys'][] = [
                'price' => $price,
                // 'firma' => $firma['name'],
                'id' => $row['id'],
                'name' => $row['name'],
               'img' => $image,
                'article' => $row['article'],
                'room_id' => $row['rooms_id'],
                'room_category_id' => $row['room_category_id'],

            ];
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
    private function executeQuery($query = null)
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

    // Get currency
    private function getCurrency($type = null, $article = null)
    {
        if (!$type || !$article) return null;

        $this->table = 'valyuta';
        $query = 'SELECT ' . $type . '  FROM valyuta';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article', $article);
        $stmt->execute();
        return $stmt->fetch(PDO::FETCH_ASSOC)[$type];
    }

    // Get Oboy Price
    private function getOboyPrice($article)
    {
        if (!$article) return null;
        $this->table = 'krimproducts';
        $query = 'SELECT * FROM ' . $this->table . ' WHERE article=:article ORDER BY id DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article', $article);
        $stmt->execute();
        $resutl = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resutl) return $resutl;
        return 0;
    }

    // Get Oboy Price
    private function getFirma($article)
    {
        if (!$article) return null;
        $this->table = 'products';
        $query = 'SELECT * FROM ' . $this->table . ' WHERE article=:article ORDER BY id DESC';
        $stmt = $this->conn->prepare($query);
        $stmt->bindParam(':article', $article);
        $stmt->execute();
        $resutl = $stmt->fetch(PDO::FETCH_ASSOC);
        if ($resutl) return $resutl;
        return 0;
    }
}