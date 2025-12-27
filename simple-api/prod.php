<?php
ini_set('display_errors', 1);
ini_set('display_startup_errors', 1);
error_reporting(E_ALL);

require('db.php');
header("Content-Type: application/json");
$method = $_SERVER['REQUEST_METHOD'];

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id !== null) {
        $stmt = $conn->prepare("SELECT * FROM products WHERE id = ?");
        $stmt->bind_param("i", $id);
        $stmt->execute();
        $res = $stmt->get_result();
        $prod = $res->fetch_all(MYSQLI_ASSOC);

        if ($stmt->affected_rows === 0) {
            http_response_code(400);
            echo json_encode([
                'error' => 'no products with that id'
            ]);
            exit;
        }

        http_response_code(200);
        echo json_encode($prod);
        exit;
    }

    $stmt = $conn->query("SELECT * FROM products");
    $prod = $stmt->fetch_all(MYSQLI_ASSOC);

    http_response_code(200);
    echo json_encode($prod);
    exit;
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents("php://input"), true);
    if (!$data) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product is needed to be added'
        ]);
        exit;
    }

    $name = $data['name'] ?? null;
    $desc = $data['description'] ?? null;
    $price = isset($data['price']) ? (float) $data['price'] : null;
    $stock = isset($data['stock']) ? (int) $data['stock'] : null;

    if ($name === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product name is needed to be added'
        ]);
        exit;
    }
    if ($desc === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product description is needed to be added'
        ]);
        exit;
    }
    if ($price === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product price is needed to be added'
        ]);
        exit;
    }
    if ($stock === null) {
        $stock = 0;
    }

    $stmt = $conn->prepare("INSERT INTO products (name, description, price, stock) VALUES (?,?,?,?)");
    $stmt->bind_param("ssdi", $name, $desc, $price, $stock);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product not added'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'product added'
    ]);
    exit;
}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product id is needed to delete it'
        ]);
        exit;
    }

    $stmt = $conn->prepare("DELETE FROM products WHERE id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product wasn\'t deleted / doesn\'t exist'
        ]);
        exit;
    }

    http_response_code();
    echo json_encode([
        'message' => 'product was deleted'
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product id is needed to edit it'
        ]);
        exit;
    }

    $data = json_decode(file_get_contents("php://input"), true);
    $name = $data['name'] ?? null;
    $desc = $data['description'] ?? null;
    $price = isset($data['price']) ? (float) $data['price'] : null;
    $stock = isset($data['stock']) ? (int) $data['stock'] : null;

    if ($name === null && $desc === null && $price === null && $stock === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'nothing to edit on the product'
        ]);
        exit;
    }

    $fields = [];
    $values = [];
    $types = '';

    if ($name !== null) {
        $fields[] = "name = ?";
        $values[] = $name;
        $types .= "s";
    }
    if ($desc !== null) {
        $fields[] = "description = ?";
        $values[] = $desc;
        $types .= "s";
    }
    if ($price !== null) {
        $fields[] = "price = ?";
        $values[] = $price;
        $types .= "i";
    }
    if ($stock !== null) {
        $fields[] = "stock = ?";
        $values[] = $stock;
        $types .= "i";
    }

    $values[] = $id;
    $types .= "i";

    $sql = "UPDATE products SET " . implode(", ", $fields) . " WHERE id = ?";
    $stmt = $conn->prepare($sql);
    $stmt->bind_param($types, ...$values);
    $stmt->execute();

    if ($stmt->affected_rows === 0) {
        http_response_code(400);
        echo json_encode([
            'error' => 'product not edited'
        ]);
        exit;
    }

    http_response_code(200);
    echo json_encode([
        'message' => 'producted edited'
    ]);
    exit;
}