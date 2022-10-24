<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");

require "../../config/Database.php";
require "../../modules/Oboy.php";
require "../../modules/CheckToken.php";


// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate oboy categories object
$oboy = new Oboy($db);
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Categories query
    $result = $oboy->readCategories();
    echo $result;
    exit;
}
