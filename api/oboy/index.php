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

// Instantiate oboy object
$oboy = new Oboy($db);

// GET Request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Get Rooms by categoryId
    if (isset($_GET['categoryId'])) {
        // Filter Params Value
        $categoryId = intval($_GET['categoryId']);

        if (isset($_GET['limit']) && !empty($_GET['limit'])) {
            if (!is_numeric($_GET['limit']) || $_GET['limit'] < 1) { // CHECK LIMIT
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

            $limit = $_GET['limit'];

            // Categories query
            $result = $oboy->readOboy($categoryId,$limit, $page);
            echo $result;
            exit;
        }

        // Oboy query
        $result = $oboy->readOboy($categoryId);

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


