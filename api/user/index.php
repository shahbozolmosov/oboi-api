<?php
// headers
header('Access-Control-Allow-Origin: *');
header('Content-Type: application/json');
header('Access-Control-Allow-Methods: POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once "../../config/Database.php";
require_once "../../modules/User.php";
require_once "../../modules/CheckToken.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Check token
$requestHeaders = apache_request_headers();
$Authorization = $database->filter($requestHeaders['Authorization']);
$checkToken = new CheckToken($db);
$result = $checkToken->check($Authorization);
if (!$result) {
    http_response_code(400);
    print(json_encode(['message' => 'Bad Requestaa!']));
    exit;
}

//Instantiate user
$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    // Get raw posted data
    $data = json_decode(file_get_contents("php://input"));
    // Validation action
    validation($data, ['action']);

    if ($data->action === 'register') {
        // validation telefon
        validation($data, ['telefon']);

        $user->telefon = $database->filterPhoneNumber($data->telefon);

        $result = $user->register();

        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    } else if ($data->action === 'verification') {
        // validation telefon with verification code
        validation($data, ['telefon', 'code']);
        echo "Verification";
        exit();
    }
}

// VALIDATION
function validation($data, $checkParam)
{
    foreach ($checkParam as $key => $value) {
        $paramValue = $data->{$value};
        if ($paramValue === null) {
            http_response_code(400);
            print(json_encode('`' . $value . '` talab qilinadi!')) ;
            exit();
        }else if($paramValue === '') {
            http_response_code(400);
            print(json_encode('`' . $value . '` bo\'sh qator bo\'lmasin!')) ;
            exit();
        }
    }
}

http_response_code(400);
print(json_encode(['message' => 'Bad Request!']));
