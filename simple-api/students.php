<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
header("Content-Type: application/json");
require('db.php');
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {

    $id = $_GET['id'] ?? null;
    if ($id) {
        $stmt = $conn->prepare("SELECT * FROM students WHERE id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $std = $res->fetch_assoc();
        if (!$std) {
            http_response_code(400);
            echo json_encode([
                'error' => 'student with id not found'
            ]);
            exit;
        }
        http_response_code(200);
        echo json_encode($std);
        exit;
    }

    $stmt = $conn->query("SELECT * FROM students");
    $stds = $stmt->fetch_all(MYSQLI_ASSOC);
    http_response_code(200);
    echo json_encode($stds);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'error' => 'you\'re missing'
        ]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO students(name, age, course) VALUES (?,?,?)");
    $stmt->bind_param("sis", $data['name'], $data['age'], $data['course']);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'student not added',
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'student added'
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$id) {
        http_response_code(400);
        echo json_encode([
            'error' => 'student id is needed'
        ]);
        exit;
    }

    $name = $data['name'] ?? null;
    $age = isset($data['age']) ? (int) $data['age'] : null;
    $course = $data['course'] ?? null;

    $fields = [];
    $values = [];
    $types = "";

    if ($name === null && $age === null && $course === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'data needed to update'
        ]);
        exit;
    }

    if ($name !== null) {
        $fields[] = "name = ?";
        $types .= "s";
        $values[] = $name;
    }
    if ($age !== null) {
        $fields[] = "age = ?";
        $types .= "i";
        $values[] = $age;
    }
    if ($course !== null) {
        $fields[] = "course = ?";
        $types .= "s";
        $values[] = $course;
    }
    $types .= "i";
    $values[] = $id;

    $sql = "UPDATE students SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'no student was edited',
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'student edited successfully',
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID is needed inorder to remove student',
        ]);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM students WHERE id = ?");
    $stmt->bind_param('i', $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'student not found',
        ]);
        exit;
    }
    http_response_code(200);
    echo json_encode([
        'message' => 'student has been removed',
    ]);
    exit;
}