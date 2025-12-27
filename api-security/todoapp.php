<?php
header("Content-Type: application/json");

require_once 'dbs.php';
require_once 'login.php';
require_once 'register.php';
require_once 'home.php';

date_default_timezone_set('Africa/Kampala');


function response(int $code, string $msg)
{
    http_response_code($code);
    echo json_encode([
        'error' => $msg
    ]);
    exit;
}

function output($code, $data)
{
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function authen(mysqli $conn): int
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if (!preg_match('/Bearer\s(\S+)/', $auth, $matches)) {
        ///response(401, 'error', 'Invalid or missing token');
        http_response_code(401);
        echo json_encode([
            'error' => 'Invalid or missing token'
        ]);
        exit;
    }

    $token = $matches[1];
    $stmt = $conn->prepare("SELECT user_id FROM tokens WHERE token = ?");
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0) {
        //response(401, 'error', 'Token does not exist');
        http_response_code(401);
        echo json_encode([
            'error' => 'Token does not exist'
        ]);
        exit;
    }

    $row = $res->fetch_assoc();
    return (int) $row['user_id'];
}

$action = $_GET['action'] ?? '';
$method = $_SERVER['REQUEST_METHOD'];

$pub_actions = ['login', 'register'];

if (!in_array($action, $pub_actions)) {
    $currentUser = authen($conn);
}


switch ($action) {
    case 'login':
        login();
        break;
    case 'register':
        register();
        break;
    case null:
        home();
        break;
    default:
        response(400, 'Action does not exist');
        /*http_response_code(400);
        echo json_encode([
            'error' => 'Action does not exist'
        ]);
        exit;*/
}