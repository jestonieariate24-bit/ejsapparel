<?php
include 'db_connect.php';

header('Content-Type: application/json');

$json = file_get_contents('php://input');
$input = json_decode($json, true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

$username = $conn->real_escape_string($input['username'] ?? '');
$fullname = $conn->real_escape_string($input['fullname'] ?? $username);
$email = $conn->real_escape_string($input['email'] ?? '');
$address = $conn->real_escape_string($input['address'] ?? '');
$items = $input['items'] ?? [];
$total_amount = floatval($input['total_amount'] ?? 0);

if (empty($username) || $total_amount <= 0 || empty($items)) {
    echo json_encode(['status' => 'error', 'message' => 'Valid order data required']);
    exit;
}

// Generate order ID
$order_id = 'EJ' . str_pad(mt_rand(1, 99999), 5, '0', STR_PAD_LEFT);

// Get user_id
$user_id = 0;
$user_result = $conn->query("SELECT id FROM users WHERE username = '$username' LIMIT 1");
if ($user_result && $user_result->num_rows > 0) {
    $user = $user_result->fetch_assoc();
    $user_id = $user['id'];
}

// Start transaction
$conn->begin_transaction();

try {
    // Insert main order
    $order_sql = "INSERT INTO orders (order_id, user_id, username, fullname, email, address, total_amount) 
                  VALUES ('$order_id', '$user_id', '$username', '$fullname', '$email', '$address', '$total_amount')";
    
    if (!$conn->query($order_sql)) {
        throw new Exception('Failed to save order: ' . $conn->error);
    }
    
    // Insert order items
    foreach ($items as $item) {
        $product_name = $conn->real_escape_string($item['name'] ?? '');
        $price = floatval($item['price'] ?? 0);
        $size = $conn->real_escape_string($item['size'] ?? '');
        $color = $conn->real_escape_string($item['color'] ?? '');
        $quantity = intval($item['qty'] ?? 1);
        
        $item_sql = "INSERT INTO order_items (order_id, product_name, price, size, color, quantity) 
                     VALUES ('$order_id', '$product_name', '$price', '$size', '$color', '$quantity')";
        
        if (!$conn->query($item_sql)) {
            throw new Exception('Failed to save order item: ' . $conn->error);
        }
    }
    
    // Commit transaction
    $conn->commit();
    
    echo json_encode([
        'status' => 'success', 
        'message' => 'Order saved successfully',
        'order_id' => $order_id
    ]);
    
} catch (Exception $e) {
    // Rollback on error
    $conn->rollback();
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}

$conn->close();
?>