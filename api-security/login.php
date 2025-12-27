<?php
header("Content-Type: application/json");

function login()
{
    require_once 'dbs.php';

    $method = $_SERVER['REQUEST_METHOD'];

    if ($method === 'POST') {
        $data = json_decode(file_get_contents('php://input'), true);
        if ($data === null) {
            response(400, 'Missing email and password');
        }

        $email = trim($data['email'] ?? '');
        $password = $data['password'] ?? '';

        if (empty($email) || empty($password)) {
            response(400, 'Email and password can not be empty');
        }

        $sql = "SELECT * FROM users WHERE email = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('s', $email);
        $stmt->execute();
        $res = $stmt->get_result();
        $user = $res->fetch_assoc();

        if (!$user || !password_verify($password, $user['password'])) {
            response(400, 'Invalid credentials');
        }
        $user_id = (int) $user['id'];

        $token = bin2hex(random_bytes(32));
        $expires = date('Y-m-d H:i:s', time() + 7200); // from time of login add 2 hours

        $sql = "INSERT INTO tokens (token, user_id, expire_at) VALUES (?,?,?)";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param('sis', $token, $user_id, $expires);
        $stmt->execute();

        if ($stmt->affected_rows === 0) {
            response(400, 'Token generation failed');
        }

        http_response_code(200);
        echo json_encode([
            'message' => 'Your token is',
            'Token' => $token
        ]);
        exit;
    }
}