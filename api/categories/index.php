<?php
// headers
header('Access-Control-Allow-Origin: *');
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
    
    if (isset($_GET['limit']) && !empty($_GET['limit'])) {
        $limit = $database->filter($_GET['limit']);
        $page = $database->filter($_GET['page']);
        if(!is_numeric($limit) || $_GET['limit'] < 1) { // CHECK LIMIT
            http_response_code(400);
            exit('Bad request!');
        }
        if (isset($_GET['page'])) { // CHECK PAGE
            if(empty($_GET['page']) || !is_numeric($_GET['page']) || $_GET['page'] < 1) {
                http_response_code(400);
                exit('Bad request!');
            }
            $page = $_GET['page'];

        }else
            $page = 1;

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
