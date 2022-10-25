<?php
// headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once "../../config/Database.php";
require_once "../../modules/User.php";
require_once "../../modules/CheckToken.php";
require_once "../../modules/Profile.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

if ($_SERVER['REQUEST_METHOD'] !== 'GET') {
    // Check token
    $requestHeaders = apache_request_headers();
    $Authorization = $database->filter($requestHeaders['Authorization']);
    $checkToken = new CheckToken($db);
    $result = $checkToken->check($Authorization);
    if (!$result) {
        http_response_code(400);
        print(json_encode(['message' => 'Bad Request!']));
        exit;
    }
}


$profile = new Profile($db);
// GET Request
if ($_SERVER['REQUEST_METHOD'] === 'GET') {
    // Check token
    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $profile->token = md5($_GET['token']);

        $result = $profile->readCashback();
        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    }
    http_response_code(400);
    print(json_encode(['message' => 'Bad request!']));
    exit;
}
