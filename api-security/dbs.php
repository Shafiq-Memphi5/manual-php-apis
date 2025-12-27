<?php
header("Content-Type: application/json");

$server_name = 'localhost';
$port = ;
$user = '';
$password = ;
$dbname = 'api_security';

$conn = mysqli_connect($server_name, $user, $password, $dbname, $port);

/*if($conn->connect_errno)
{
    echo json_encode([
        'error'=> 'database didnt connect'
    ]);
}
echo json_encode([
    'message' => 'database connected'
]);*/
