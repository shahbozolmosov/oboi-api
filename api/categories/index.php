<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json; charset=utf8");
header('Access-Control-Allow-Methods: GET');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Authorization, X-Requested-With');

require "../../config/Database.php";
require "../../modules/Oboy.php";
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
    echo 'Bad request';
    http_response_code(404);
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


