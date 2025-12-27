<?php
require('db.php');
header("Content-Type: application/json");

$file = "todos.json";
$todos = json_decode(file_get_contents($file), true);
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if (isset($id)) {
        foreach ($todos as $todo) {
            if ($todo['id'] == $id) {
                echo json_encode($todo);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode([
            "error" => "Unknown ID",
        ]);
        exit;
    }
    echo json_encode($todos);
    exit;
}

if ($method === 'POST') {

    $data = json_decode(file_get_contents('php://input'), true);
    $todos[] = [
        "id" => count($todos) + 1,
        "task" => $data['task'],
        "done" => false,
    ];

    file_put_contents($file, json_encode($todos, JSON_PRETTY_PRINT));

    http_response_code(201);
    echo json_encode([
        "Message" => "Data added successfully",
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if (!isset($id)) {
        echo json_encode([
            "error" => "ID is required for update",
        ]);
        exit;
    }
    $data = json_decode(file_get_contents('php://input'), true);
    foreach ($todos as &$todo) {
        if ($id == $todo['id']) {
            $todo['task'] = $data['task'] ?? $todo['task'];
            $todo['done'] = $data['done'] ?? $todo['done'];
            file_put_contents($file, json_encode($todos, JSON_PRETTY_PRINT));
            echo json_encode([
                "Message" => "Data with ID $id updated successfully",
            ]);
            exit;
        }
    }
    http_response_code(404);
    echo json_encode([
        "error" => "ID not found",
    ]);
    exit;
}

if ($method === 'DELETE')
{
    $id = $_GET['id'] ?? null;
    if (!isset($id)) {
        echo json_encode([
            "error" => "ID is required for deletion",
        ]);
        exit;
    }
    if ($id > count($todos)) {
        http_response_code(404);
        echo json_encode([
            "error" => "ID not found",
        ]);
        exit;
    }
    foreach ($todos as $index => &$todo)
    {
        if ($todo['id'] == $id) {
            array_splice($todos, $index, 1);
            break;
        }
    }
    foreach ($todos as $index => &$todo)
    {
        $todo['id'] = $index + 1;
    }
    file_put_contents($file, json_encode($todos, JSON_PRETTY_PRINT));
    echo json_encode([
        "Message" => "Data with ID $id deleted successfully",
    ]);
    exit;
}