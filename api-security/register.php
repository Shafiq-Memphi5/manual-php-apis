<?php
header("Content-Type: application/json");

function register()
{
    require_once 'dbs.php';
    $method = $_SERVER['REQUEST_METHOD'];

    if ($method !== 'POST') {
        response(400, 'You can not use that on this url');
    }

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if (!$data) {
            response(400, 'Your details are missing');
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            response(400, 'You can not leave email and password field null');
        }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            response(400, 'Invalid email format');
        }


        $passwordHash = password_hash($password, PASSWORD_DEFAULT);

        $sql = "INSERT INTO users (email, password) VALUES (?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('ss', $email, $passwordHash);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            response(400, 'Registration failed');
        }

        http_response_code(200);
        echo json_encode([
            'Message' => 'User created successfully'
        ]);
        exit;
    }
}