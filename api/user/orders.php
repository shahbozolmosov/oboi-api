<?php
// headers
header('Access-Control-Allow-Origin: http://localhost:3000');
header("Content-Type: application/json");
header('Access-Control-Allow-Methods: GET, POST');
header('Access-Control-Allow-Headers: Access-Control-Allow-Headers, Content-Type, Access-Control-Allow-Methods, Authorization, X-Requested-With');

require_once "../../config/Database.php";
require_once "../../modules/User.php";
require_once "../../modules/Profile.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();


$profile = new Profile($db);
if ($_SERVER['REQUEST_METHOD'] === 'POST') { // POST Request
    $data = json_decode(file_get_contents('php://input'));

    // Validation
    validation($data, ['token','telefon', 'location', 'article', 'count', 'cashback',]);

    // Filter values
    $profile->token = md5($data->token);
    $profile->telefon = $database->filterPhoneNumber($data->telefon);
    $profile->location = $database->filter($data->location);
    $profile->article = $database->filter($data->article);
    $profile->count = $database->filter($data->count);
    $profile->cashback = $database->filter($data->cashback);

    //Order
    $result = $profile->createOrder();

    http_response_code($result['status_code']);
    print(json_encode($result['data']));
    exit;
} else if ($_SERVER['REQUEST_METHOD'] === 'GET') { // GET Request
    // Check token
    if (isset($_GET['token']) && !empty($_GET['token'])) {
        $profile->token = md5($_GET['token']);

        $result = $profile->readOrders();
        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    }
    http_response_code(400);
    print(json_encode(['message' => 'Bad request!']));
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
