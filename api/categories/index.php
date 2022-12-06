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

// Instantiate oboy categories object
$oboy = new Oboy($db);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    $limit = $database->filter($_GET['limit']);
    $page = $database->filter($_GET['page']);

    if (isset($limit) && !empty($limit)) {
        if(!is_numeric($limit)) { // CHECK LIMIT
            http_response_code(400);
            exit('Bad request');
        }
        if(!isset($page) || empty($page) || !is_numeric($page)) { // CHECK PAGE
            http_response_code(400);
            exit('Bad request');
        }

        // Categories query
        $result = $oboy->readCategories($limit, $page);
        echo $result;
        exit;
    }

    // Categories query
    $result = $oboy->readCategories();
    echo $result;
    exit;
}
