<?php
include 'db_connect.php';

header('Content-Type: application/json');
header("Access-Control-Allow-Origin: *");

$username = $conn->real_escape_string($_GET['username'] ?? '');

if (empty($username)) {
    echo json_encode(['status' => 'error', 'message' => 'Username required']);
    exit;
}

// Get orders with their items
$sql = "SELECT o.order_id, o.total_amount, o.status, o.order_date, o.address,
               oi.product_name, oi.price, oi.size, oi.color, oi.quantity
        FROM orders o
        LEFT JOIN order_items oi ON o.order_id = oi.order_id
        WHERE o.username = '$username'
        ORDER BY o.order_date DESC, oi.id ASC";
        
$result = $conn->query($sql);

$orders = [];
$current_order = null;

if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $order_id = $row['order_id'];
        
        // New order found
        if (!$current_order || $current_order['order_id'] !== $order_id) {
            if ($current_order) {
                $orders[] = $current_order;
            }
            $current_order = [
                'order_id' => $order_id,
                'total_amount' => $row['total_amount'],
                'status' => $row['status'],
                'order_date' => $row['order_date'],
                'address' => $row['address'],
                'items' => []
            ];
        }
        
        // Add item to current order
        if ($row['product_name']) {
            $current_order['items'][] = [
                'name' => $row['product_name'],
                'price' => $row['price'],
                'size' => $row['size'],
                'color' => $row['color'],
                'qty' => $row['quantity']
            ];
        }
    }
    
    // Add the last order
    if ($current_order) {
        $orders[] = $current_order;
    }
}

echo json_encode([
    'status' => 'success',
    'orders' => $orders
]);

$conn->close();
?>