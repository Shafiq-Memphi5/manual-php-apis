<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);
require('db.php');
header('Content-Type: application/json');

$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id !== null) {
        $stmt = $conn->prepare("SELECT * FROM inventory WHERE prod_id = ?");
        $stmt->bind_param('i', $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $inv = $res->fetch_assoc();

        if ($stmt->affected_rows === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'product with such id doesnt exist',
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode($inv);
        exit;
    }

    $stmt = $conn->query("SELECT * FROM inventory");
    $inv = $stmt->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode($inv);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);

    if ($data === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product info is needed to proceeed',
        ]);
        exit;
    }
    $name = $data['prod_name'] ?? null;
    $qty = $data['prod_quantity'] ?? 0;

    if ($name === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product name is a must',
        ]);
        exit;
    }
    $stmt = $conn->prepare("INSERT INTO inventory (prod_name, prod_quantity) VALUES (?, ?)");
    $stmt->bind_param("si", $name, $qty);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        'message' => 'product added successfully'
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'id is need to delete a product'
        ]);
        exit;
    }
    $stmt = $conn->prepare("DELETE FROM inventory WHERE prod_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    http_response_code(200);
    echo json_encode([
        'message' => 'product deleted successfully'
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'id is need to delete a product'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['prod_name'] ?? null;
    $qty = isset($data['prod_quantity']) ? (int) $data['prod_quantity'] : null;

    if ($name === null && $qty === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'nothing to update'
        ]);
        exit;
    }

    $fields = [];
    $values = [];
    $types = "";

    if ($name !== null) {
        $fields[] = "prod_name = ?";
        $values[] = $name;
        $types .= "s";
    }

    if ($qty !== null) {
        $fields[] = "prod_quantity = ?";
        $values[] = $qty;
        $types .= "i";
    }

    $types .= "i";
    $values[] = $id;

    $sql = "UPDATE inventory SET " . implode(", ", $fields) . " WHERE prod_id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'nothing was updated'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'product has been updated'
    ]);
    exit;
}