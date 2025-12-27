<?php
$server_name = "localhost";
$username = "root";
$password = 1234;
//$dbname = "todos_db";
//$dbname = "students_db";
$dbname = "inventory_db";
$port = 3307;

$conn = mysqli_connect($server_name, $username, $password, $dbname, $port);

/*
if (!$conn) {
    echo json_encode([
        "error" => "Connection Unsuccessful",
    ]);
}
echo json_encode([
    "message" => "Connection Successful",
]);
*/