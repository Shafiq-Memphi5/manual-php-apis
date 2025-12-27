<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header('Content-Type: application/json');
require('db.php');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;

    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM todos WHERE id = ?");

        $stmt->bind_param("i", $id);
        $stmt->execute();

        $result = $stmt->get_result();
        $todos = $result->fetch_assoc();

        if (!$todos) {
            http_response_code(404);
            echo json_encode([
                'error' => 'Not found',
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode($todos);
        exit;
    }

    $result = $conn->query("SELECT * FROM todos");
    $todos = $result->fetch_all(MYSQLI_ASSOC);
    echo json_encode($todos);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    $task = $data['task'] ?? null;
    if (!$task) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Input task',
        ]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO todos (task, done) VALUES (?, 0)");

    $stmt->bind_param("s", $task);
    $stmt->execute();
    http_response_code(200);
    echo json_encode([
        'message' => 'task added',
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'id is needed to delete',
        ]);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM todos WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();
    http_response_code(200);
    echo json_encode([
        'message' => 'successful deleted',
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);

    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'id is needed',
        ]);
        exit;
    }

    $task = $data['task'] ?? null;
    $done = isset($data['done']) ? (int) $data['done'] : null;

    if ($task === null && $done === null) {
        http_response_code(400);
        echo json_encode(["error" => "Nothing to update"]);
        exit;
    }

    $fields = [];
    $types = '';
    $values = [];

    if ($task !== null) {
        $fields[] = "task = ?";
        $types .= "s";
        $values[] = $task;
    }
    if ($done !== null) {
        $fields[] = "done = ?";
        $types .= "i";
        $values[] = $done;
    }
    $types .= "i";
    $values[] = $id;

    $sql = "UPDATE todos SET " . implode(", ", $fields) . " WHERE id = ?";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(404);
        echo json_encode(["error" => "Todo not found"]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'task updated',
    ]);
    exit;
}