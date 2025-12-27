<?php
$server_name = "localhost";
$username = "";
$password = ;
//$dbname = "todos_db";
//$dbname = "students_db";
$dbname = "inventory_db";
$port = ;

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