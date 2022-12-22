<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');
require "../config/Database.php";
require "../modules/Temp.php";
// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate temp object
$temp = new Temp($db);
if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $data = json_decode(file_get_contents("php://input"));

    if (isset($data->image) && !empty($data->image)) {
        $result = $temp->writeTemp($data->image);
        if ($result['error'] == 0) {
            exit(json_encode($result));
//            $response = ['image' => 'https://systemvakhidov.uz/api/shahboz/api/' . $image, 'error' => $result['error']];
        } else {
            http_response_code(500);
            exit(json_encode($result));
        }
    }
}

