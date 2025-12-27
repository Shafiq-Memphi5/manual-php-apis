<?php
header("Content-Type: application/json");
require('dbs.php');

//echo bin2hex(random_bytes(32));
$method = $_SERVER['REQUEST_METHOD'];

function authenticate(mysqli $conn): int
{
    $headers = getallheaders();
    $auth = $headers['Authorization'] ?? '';

    if(!preg_match('/Bearer\s(\S+)/', $auth, $matches))
    {
        http_response_code(401);
        echo json_encode([
            'error'=> 'Unauthorised user'
        ]);
        exit;
    }

    $token = $matches[1];

    $stmt = $conn->prepare('SELECT user_id FROM tokens WHERE token = ?');
    $stmt->bind_param('s', $token);
    $stmt->execute();
    $res = $stmt->get_result();

    if ($res->num_rows === 0)
    {
        http_response_code(401);
        echo json_encode([
            'error'=> 'Invalid token'
        ]);
        exit;
    }

    $row = $res->fetch_assoc();
    return (int) $row['user_id'];
}

$currentUserID = authenticate($conn);
/*
echo json_encode([
    'message'=> 'Hello your user id is '.$rowTk['user_id']
]);
*/

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM todo WHERE id = ? && userid = ?");
        $stmt->bind_param('ii', $id, $currentUserID);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            http_response_code(404);
            echo json_encode([
                'error' => 'ID doesnt exist or doesnt belong to user'
            ]);
            exit;
        }

        $row = $res->fetch_assoc();

        http_response_code(200);
        echo json_encode($row);
        exit;
    }

    $stmt = $conn->prepare("SELECT * FROM todo WHERE userid = ?");
    $stmt->bind_param('i', $currentUserID);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode($rows);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'no data to input'
        ]);
        exit;
    }

    $todoIM = $data['todo'] ?? null;

    if ($todoIM === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'no todo to input'
        ]);
        exit;
    }

    $stmt = $conn->prepare('INSERT INTO todo(todo, userid) VALUE(?,?)');
    $stmt->bind_param('si', $todoIM, $currentUserID);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'todo was not added'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'todo added successfully'
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;

    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID is needed to delete'
        ]);
        exit;
    }

    $stmt = $conn->prepare('SELECT userid FROM todo WHERE id = ?');
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $todo = $res->fetch_assoc();

    if (!$todo) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID does not exist'
        ]);
        exit;
    }

    if ($todo['userid'] !== $currentUserID) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Forbidden action'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM todo WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Nothing delete'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'todo deleted successfully'
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID is needed'
        ]);
        exit;
    }

    $stmt = $conn->prepare("SELECT userid FROM todo WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    $res = $stmt->get_result();
    $todo = $res->fetch_assoc();

    if ($todo['userid'] !== $currentUserID) {
        http_response_code(403);
        echo json_encode([
            'error' => 'Forbidden action'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'error' => 'data is needed'
        ]);
        exit;
    }
    $todo = $data['todo'] ?? null;
    $done = isset($data['done']) ? (int) $data['done'] : null;
    $userid = $currentUserID;

    if ($todo === null && $done === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'data is needed for updating'
        ]);
        exit;
    }

    $fields = [];
    $values = [];
    $types = '';

    if ($todo !== null) {
        $fields[] = "todo = ?";
        $values[] = $todo;
        $types .= 's';
    }
    if ($done !== null) {
        $fields[] = "done = ?";
        $values[] = $done;
        $types .= 'i';
    }
    $values[] = $id;
    $types .= "i";

    $sql = "UPDATE todo SET " . implode(",", $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->error) {
        http_response_code(500);
        echo json_encode([
            'error' => 'nothing was updated'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'update successful'
    ]);
    exit;
}