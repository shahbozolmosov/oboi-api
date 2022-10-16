<?php
require "../../config/Database.php";
require "../../modules/User.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();


//Instantiate user
$user = new User($db);

if ($_SERVER['REQUEST_METHOD'] === 'POST' || true) {
    $user->telefon = $database->filterPhoneNumber('+998(99)-747-04-73');

    // Check user action
    $result = $user->checkUserActions();

    if ($result === 'ok') {
        $result = $user->register();
        echo "success";
    } else {
        http_response_code($result['status_code']);
        print(json_encode($result['data']));
        exit;
    }
}
