<?php
include 'db_connect.php';

header('Content-Type: application/json');

// Get JSON data
$input = json_decode(file_get_contents('php://input'), true);

if (!$input) {
    echo json_encode(['status' => 'error', 'message' => 'No data received']);
    exit;
}

$fullname = $conn->real_escape_string($input['fullname'] ?? '');
$email = $conn->real_escape_string($input['email'] ?? '');
$username = $conn->real_escape_string($input['username'] ?? '');
$address = $conn->real_escape_string($input['address'] ?? '');
$password = $input['password'] ?? '';

// Validate all fields
if (empty($fullname) || empty($email) || empty($username) || empty($address) || empty($password)) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required']);
    exit;
}

// Check if user exists
$check_sql = "SELECT id FROM users WHERE username = '$username' OR email = '$email'";
$check_result = $conn->query($check_sql);

if ($check_result->num_rows > 0) {
    echo json_encode(['status' => 'error', 'message' => 'Username or email already exists']);
    exit;
}

// Hash password and insert
$hashed_password = password_hash($password, PASSWORD_DEFAULT);
$sql = "INSERT INTO users (fullname, email, username, address, password) 
        VALUES ('$fullname', '$email', '$username', '$address', '$hashed_password')";

if ($conn->query($sql)) {
    echo json_encode([
        'status' => 'success', 
        'message' => 'Registration successful',
        'username' => $username
    ]);
} else {
    echo json_encode(['status' => 'error', 'message' => 'Database error: ' . $conn->error]);
}

$conn->close();
?>