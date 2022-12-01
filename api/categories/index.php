<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: POST, GET, PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require "../../config/Database.php";
require "../../modules/Oboy.php";
require "../../modules/CheckToken.php";


// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

$requestHeaders = apache_request_headers();

if (!isset($requestHeaders['Authorization'])) {
    print('Bad request');
    exit;
}
$Authorization = isset($requestHeaders['Authorization']) ? $database->filter($requestHeaders['Authorization']) : die(http_response_code(400));
$checkToken = new CheckToken($db);
$result = $checkToken->check($Authorization);

if (!$result) {
    http_response_code(400);
    print(json_encode(['message' => 'Bad Requestaa!']));
    exit;
}

// Instantiate oboy categories object
$oboy = new Oboy($db);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Categories query
    $result = $oboy->readCategories();
    echo $result;
    exit;
}
