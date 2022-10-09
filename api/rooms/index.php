<?php
// headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json; charset=utf8');
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require "../../config/Database.php";
require "../../modules/Oboi.php";
require "../../modules/CheckToken.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Check token
$requestHeaders = apache_request_headers();
$Authorization = $database->filter($requestHeaders['Authorization']);
$checkToken = new CheckToken($db);
$result = $checkToken->check($Authorization);
if (!$result) {
    http_response_code(400);
    exit();
}

// Instantiate oboi rooms object
$oboi = new Oboi($db);

// GET Request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Rooms by categoryId
    if (isset($_GET['categoryId'])) {
        // Filter Params Value
        $categoryId = $_GET['categoryId'];
        $categoryId = $database->filter($categoryId);

        // Rooms query
        $result = $oboi->readRooms($categoryId);

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


