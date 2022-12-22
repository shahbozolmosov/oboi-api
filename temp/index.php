<?php
require "../config/Database.php";
require "../modules/Temp.php";
// Instantiate DB & connect
$database = new Database();
$db = $database->connect();

// Instantiate temp object
$temp = new Temp($db);
if (isset($_GET['image']) || !empty($_GET['image'])) {
    $openImage = $database->filter($_GET['image']);
    if($openImage) {
        ?>
        <!doctype html>
        <html lang="en">
        <head>
            <meta charset="UTF-8">
            <meta name="viewport"
                  content="width=device-width, user-scalable=no, initial-scale=1.0, maximum-scale=1.0, minimum-scale=1.0">
            <meta http-equiv="X-UA-Compatible" content="ie=edge">
            <title>Oboy Vakhidov</title>
        </head>
        <body>
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                let a = document.createElement('a');
                a.href = './<?=$openImage?>';
                a.download = 'Oboy Vakhidov';
                a.click();
                window.setTimeout('self.close();', 3000);
            })
        </script>
        </body>
        </html>
        <?php
            $temp->checkAndDelTemp();
        exit();
    }
}
http_response_code(404);