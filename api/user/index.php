<?php
// headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
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

// Check token
$requestHeaders = apache_request_headers();

if(!isset($requestHeaders['Authorization'])) {
    print(json_encode(['error' => ['message' => 'Authorization talab qilinadi!','status_code' => 501]]));
    exit;
}
$Authorization = isset($requestHeaders['Authorization'])?$database->filter($requestHeaders['Authorization']):die(http_response_code(400));
$checkToken = new CheckToken($db);
$result = $checkToken->check($Authorization);

if (!$result) {
    http_response_code(400);
    print(json_encode(['message' => 'Bad Requestaa!']));
    exit;
}


//Instantiate user
$user = new User($db);

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

$profile = new Profile($db);


if ($_SERVER['REQUEST_METHOD'] === 'POST') { //POST
    
    // Validation action
    validation($data, ['action', 'telefon']);

    $user->telefon = $database->filterPhoneNumber($data->telefon);

    if ($data->action === 'register') {
        $result = $user->register();

        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    } else if ($data->action === 'verification') {
        // validation telefon with verification code
        validation($data, ['code']);
        $user->code = $database->filter($data->code);

        $result = $user->verification();

        http_response_code($result['status_code']);
        print(json_encode($result['data']));

        exit();
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') { // GET
    // validation token
    validation($data, ['token']);

    $profile->token = md5($data->token);

    $result = $profile->readUserData();
    http_response_code($result['status_code']);
    print(json_encode($result['data']));
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') { // PUT
    // validation token, fio, telefon
    validation($data, ['token', 'telefon', 'fio']);

    $profile->token = md5($data->token);
    $profile->telefon = $database->filter($data->telefon);
    $profile->fio = $database->filter($data->fio);

    $result = $profile->updateUserData();
    http_response_code($result['status_code']);
    print(json_encode($result['data']));
    exit;
}

// VALIDATION
function validation($data, $checkParam)
{
    $error = [];
    foreach ($checkParam as $key => $value) {
        $paramValue = $data->{$value};
        if ($paramValue === null) { // check isset
            $error['error'][] = '`' . $value . '` talab qilinadi!';
        }
        if ($paramValue === '') { // check empty
            $error['error'][] = '`' . $value . '` bo\'sh qator bo\'lmasin!';
        }
    }

    if ($error) {
        http_response_code(400);
        print(json_encode($error));
        exit();
    }
}
