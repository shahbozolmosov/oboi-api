<?php
// headers
header('Access-Control-Allow-Origin: *');
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: GET, POST, PUT');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once "../../config/Database.php";
require_once "../../modules/User.php";
require_once "../../modules/Profile.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

//Instantiate user
$user = new User($db);

// Get raw posted data
$data = json_decode(file_get_contents("php://input"));

$profile = new Profile($db);


if ($_SERVER['REQUEST_METHOD'] === 'POST') { //POST

    // Validation action
    validation($data, ['action']);
    
    if(isset($data->telefon)) $user->telefon = $database->filterPhoneNumber($data->telefon);

    if ($data->action === 'register') {// REGISTER
        // Validation telefon
        validation($data, ['telefon']);

        $result = $user->register();

        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    } else if ($data->action === 'verification') { // VERIFICATION
        // validation telefon with verification code
        validation($data, ['code', 'telefon']);
        $user->code = $database->filter($data->code);

        $result = $user->verification();

        http_response_code($result['status_code']);
        print(json_encode($result['data']));

        exit();
    } else if ($data->action === 'accessCode') { // ACCESS CODE
        // Validation telefon
        validation($data, ['token']);
        $profile->token = md5($data->token);
        $result = $profile->accessCode();
        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    }
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') { // GET
    // check token
    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $profile->token = md5($_GET['token']);

        $result = $profile->readUserData();
        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    }
    http_response_code(400);
    print(json_encode(['message' => 'Bad request!']));
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'PUT') { // PUT
    if (!isset($_GET['token']) || empty($_GET['token'])) {
        http_response_code(400);
        print('Bad request!');
        exit();
    }
    // validation telefon with verification code
    validation($data, ['fio', 'telefon', 'code']);
    // check token

    $profile->token = md5($_GET['token']);
    $profile->telefon = $database->filter($data->telefon);
    $profile->fio = $database->filter($data->fio);
    $profile->code = $database->filter($data->code);

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
        if (isset($data->{$value})) { // check isset
            $paramValue = $data->{$value};
            if ($paramValue === '') { // check empty
                $error[] = '`' . $value . '` bo\'sh qator bo\'lmasin!';
            }
        } else {
            $error[] = '`' . $value . '` talab qilinadi!';
        }
    }

    if ($error) {
        http_response_code(400);
        print(json_encode($error));
        exit();
    }
}
