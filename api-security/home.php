<?php
header("Content-Type: application/json");

function home()
{
    require 'dbs.php';
    $method = $_SERVER['REQUEST_METHOD'];
    $user_id = authen($conn);
    if ($method === 'GET') {
        $id = $_GET['id'] ?? null;
        if ($id === '') {
            response(400, 'ID can not be blank');
        }
        if ($id !== null) {
            $sql = "SELECT * FROM todo WHERE id = ? AND user_id = ?";
            $stmt = $conn->prepare($sql);
            $stmt->bind_param('ii', $id, $user_id);
            $stmt->execute();
            $res = $stmt->get_result();

            if ($res->num_rows === 0) {
                response(400, 'You do not any have todos with that id');
            }
            $todos = $res->fetch_assoc();
            output(200, $todos);
        }

        $sql = "SELECT * FROM todo WHERE user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('i', $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            response(400, 'You do not any have todos');
        }
        $todos = $res->fetch_all(MYSQLI_ASSOC);
        output(200, $todos);
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        $todo = trim($data['todo'] ?? null);
        $done = isset($data['done']) ? (int) $data['done'] : 0;

        if ($todo === null || $done === null) {
            response(400, "Todo can not be empyt");
        }

        $sql = "INSERT INTO todo (todo, done, user_id) VALUES (?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sii', $todo, $done, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            response(400, 'Todo not added');
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Todo added',
        ]);
        exit;
    }

    if ($method === 'DELETE') {
        $id = $_GET['id'] ?? null;
        if ($id === null || $id === '') {
            response(400, 'ID is needed for deleting');
        }

        $sql = "DELETE FROM todo WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            response(400, 'Todo was not deleted');
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Todo was deleted'
        ]);
        exit;
    }

    if ($method === 'PUT') {
        $id = $_GET['id'] ?? null;
        if ($id === null || $id === '') {
            response(400, 'ID is needed for editing');
        }

        $sql = "SELECT * FROM todo WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ii', $id, $user_id);
        $stmt->execute();
        $res = $stmt->get_result();

        if ($res->num_rows === 0) {
            response(400, 'Doesnt exist completely or under this User ID');
        }

        $data = json_decode(file_get_contents('php://input'), true);
        $todo = trim($data['todo'] ?? null);
        $done = isset($data['done']) ? (int) $data['done'] : 0;

        if ($todo === '' && $done === '') {
            response(400, "Todo can not be empyt");
        }

        $fields = [];
        $values = [];
        $types = '';

        if ($todo !== null) {
            $fields[] = "todo = ?";
            $values[] = $todo;
            $types .= "s";
        }

        if ($done !== null) {
            $fields[] = "done = ?";
            $values[] = $done;
            $types .= "i";
        }

        $values[] = $id;
        $types .= "i";
        $values[] = $user_id;
        $types .= "i";

        $sql = "UPDATE todo SET " . implode(",", $fields) . " WHERE id = ? AND user_id = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param($types, ...$values);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            response(400, 'Todo not updated');
        }

        http_response_code(200);
        echo json_encode([
            'message'=> 'Todo was updated'
        ]);
        exit;
    }
}