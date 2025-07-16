<?php
header('Content-Type: application/json');
require_once 'includes/db.php';
require_once 'config.php';
session_start();
mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

// Get POST data
$room_id = $_POST['room_id'] ?? 0;
$name = trim($_POST['name'] ?? '');
$phone = trim($_POST['phone'] ?? '');
$email = trim($_POST['email'] ?? '');

// Validate input
if (!is_numeric($room_id) || $room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit;
}

if (empty($name)) {
    echo json_encode(['success' => false, 'message' => 'Name is required']);
    exit;
}

if (empty($phone)) {
    echo json_encode(['success' => false, 'message' => 'Phone number is required']);
    exit;
}

// Validate phone number (basic validation)
if (!preg_match('/^[0-9+\-\s()]+$/', $phone)) {
    echo json_encode(['success' => false, 'message' => 'Invalid phone number format']);
    exit;
}

// Validate email if provided
if (!empty($email) && !filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['success' => false, 'message' => 'Invalid email format']);
    exit;
}

try {
    // Fetch room_number, location, and landlord_id from the database
    $stmt = $conn->prepare("SELECT room_number, location, landlord_id FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->bind_result($room_number, $location, $landlord_id);
    $stmt->fetch();
    $stmt->close();
    
    // Fallback if data is missing
    if (empty($room_number)) $room_number = "unknown";
    if (empty($location)) $location = "unknown";
    
    // Construct the message
    $message = "Please notify me when room $room_number located at $location becomes available.";
    
    // Check if the same person already requested notification for this room
    $check_query = "SELECT id FROM bookings WHERE room_id = ? AND phone = ?";
    $check_stmt = $conn->prepare($check_query);
    $check_stmt->bind_param('is', $room_id, $phone);
    $check_stmt->execute();
    $existing = $check_stmt->get_result()->fetch_assoc();
    
    if ($existing) {
        echo json_encode(['success' => false, 'message' => 'You have already requested notifications for this room']);
        exit;
    }
    
    // Insert notification with landlord's user_id
    $stmt = $conn->prepare("INSERT INTO notifications (user_id, room_id, name, phone, email, message, type, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'email', 'unread')");
    $stmt->bind_param("iissss", $landlord_id, $room_id, $name, $phone, $email, $message);
    $stmt->execute();
    
    $stmt = $conn->prepare("INSERT INTO bookings (room_id, user_id, name, phone, email, message, status) 
                            VALUES (?, ?, ?, ?, ?, ?, 'pending')");
    $stmt->bind_param("iissss", $room_id, $landlord_id, $name, $phone, $email, $message);
    $stmt->execute();
    
    echo json_encode(['success' => true, 'message' => 'Notification request added successfully']);
    
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>