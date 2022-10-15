<?php
require "../../config/Database.php";
require "../../modules/User.php";

// Instantiate DB & connect
$database = new Database();
$db = $database->connect();


//Instantiate user
$user = new User($db);


$number = $database->filterPhoneNumber('+998(99)-747-04-73');

