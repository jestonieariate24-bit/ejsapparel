<?php
include 'db_connect.php';

// Set header first
header('Content-Type: application/json');

try {
    // Get raw POST data
    $rawData = file_get_contents("php://input");
    $input = json_decode($rawData, true);
    
    if (!$input) {
        throw new Exception('Invalid JSON data');
    }
    
    $username = $conn->real_escape_string($input['username'] ?? '');
    $password = $input['password'] ?? '';
    
    if (empty($username) || empty($password)) {
        throw new Exception('Username and password are required');
    }
    
    // Query user from database
    $sql = "SELECT * FROM users WHERE username = '$username'";
    $result = $conn->query($sql);
    
    if ($result && $result->num_rows > 0) {
        $user = $result->fetch_assoc();
        
        // Verify password against hash
        if (password_verify($password, $user['password'])) {
            echo json_encode([
                'status' => 'success', 
                'message' => 'Login successful',
                'username' => $user['username'],
                'fullname' => $user['fullname'],
                'email' => $user['email']
            ]);
        } else {
            throw new Exception('Invalid password');
        }
    } else {
        throw new Exception('User not found');
    }
    
} catch (Exception $e) {
    echo json_encode([
        'status' => 'error', 
        'message' => $e->getMessage()
    ]);
}

$conn->close();
?>