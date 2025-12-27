<?php
header("Content-Type: application/json");

$file = 'products.json';
$method = $_SERVER['REQUEST_METHOD'];
$products = json_decode(file_get_contents($file), true);

if ($method === 'GET') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        echo json_encode($products);
        exit;
    } else {
        foreach ($products as $product) {
            if ($product['id'] == $id) {
                echo json_encode($product);
                exit;
            }
        }
        http_response_code(404);
        echo json_encode([
            'error' => 'Unknown ID doesn\'t exist'
        ]);
        exit;
    }
}

if ($method === 'POST') {
    $data = json_decode(file_get_contents('php://input'), true);
    if (!$data || !isset($data['name']) || !isset($data['price'])) {
        http_response_code(400);
        echo json_encode([
            'error' => 'Some Fields are missing (name or price)'
        ]);
        exit;
    }
    $products[] = [
        'id' => count($products) + 1,
        'name' => $data['name'],
        'price' => $data['price']
    ];
    file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));
    echo json_encode([
        'message' => 'Product created successfully'
    ]);
    exit;
}

if ($method === 'PUT') {
    $id = $_GET['id'] ?? null;
    $data = json_decode(file_get_contents('php://input'), true);
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID is required for update'
        ]);
        exit;
    }
    if ($id > count($products))
    {
        http_response_code(404);
        echo json_encode([
            'error' => 'ID is required for update'
        ]);
        exit;
    }
    if (!$data || (!isset($data['name']) && !isset($data['price']))) {
        http_response_code(422);
        echo json_encode([
            'error' => 'At least one field (name or price) is required for update'
        ]);
        exit;
    }
    foreach ($products as &$product) {
        if ($product['id'] == $id) {
            $product['name'] = $data['name'];
            $product['price'] = $data['price'];
            file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));
            http_response_code(200);
            echo json_encode([
                'message' => 'successfully edited',
            ]);
            exit;
        }
    }


}

if ($method === 'DELETE') {
    $id = $_GET['id'] ?? null;
    if ($id === null) {
        http_response_code(400);
        echo json_encode([
            'error' => 'ID is required for deletion'
        ]);
        exit;
    }
    if ($id > count($products)) {
        http_response_code(404);
        echo json_encode([
            "error" => "ID not found",
        ]);
        exit;
    }
    foreach ($products as $index => &$product) {
        if ($product['id'] == $id) {
            array_splice($products, $index, 1);
            break;
        }
    }
    foreach ($products as $index => &$product) {
        $product['id'] = $index + 1;
        break;
    }
    file_put_contents($file, json_encode($products, JSON_PRETTY_PRINT));
    echo json_encode([
        'message' => 'Product deleted successfully'
    ]);
    exit;
}