<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json; charset=utf8");

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

if(!$result) {
    http_response_code(400);
    exit;
}

// Instantiate oboy categories object
$oboy = new Oboy($db);

if($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Categories query
    $result = $oboy->readCategories();
    echo $result;
    exit;
}


