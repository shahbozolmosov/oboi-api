<?php
// headers
header('Access-Control-Allow-Origin: http://localhost:3000');
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require "../../config/Database.php";
require "../../modules/Oboy.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate oboy rooms object
$oboy = new Oboy($db);

// GET Request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Rooms by categoryId
    if (isset($_GET['categoryId'])) {
        // Filter Params Value
        $categoryId = intval($_GET['categoryId']);

        // Rooms query
        $result = $oboy->readRooms($categoryId);

        // Check result
        if (!$result) {
            http_response_code(404);
            exit();
        }
        echo $result;
        exit();
    }
    http_response_code(400);
    exit();

}


