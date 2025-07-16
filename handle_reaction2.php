<?php
header('Content-Type: application/json');
require_once 'includes/db.php';
require_once 'config.php';

session_start();

// Get POST data
$room_id = $_POST['room_id'] ?? 0;
$reaction = $_POST['reaction'] ?? '';
$action = $_POST['action'] ?? 'add'; // 'add' or 'remove'

// Validate input
if (!is_numeric($room_id) || $room_id <= 0) {
    echo json_encode(['success' => false, 'message' => 'Invalid room ID']);
    exit;
}

$allowed_reactions = ['like', 'love', 'interested', 'wow'];
if (!in_array($reaction, $allowed_reactions)) {
    echo json_encode(['success' => false, 'message' => 'Invalid reaction type']);
    exit;
}

// Get or create user ID (for guests, use session ID)
$user_id = $_SESSION['user_id'] ?? 'guest_' . session_id();

try {
    if ($action === 'add') {
        // Check if reaction already exists
        $check_query = "SELECT id FROM room_reactions WHERE room_id = ? AND user_id = ? AND reaction = ?";
        $check_stmt = $conn->prepare($check_query);
        $check_stmt->bind_param('iss', $room_id, $user_id, $reaction);
        $check_stmt->execute();
        $existing = $check_stmt->get_result()->fetch_assoc();
        
        if ($existing) {
            echo json_encode(['success' => false, 'message' => 'You have already reacted with this emotion']);
            exit;
        }
        
        // Add new reaction
        $insert_query = "INSERT INTO room_reactions (room_id, user_id, reaction, created_at) VALUES (?, ?, ?, NOW())";
        $insert_stmt = $conn->prepare($insert_query);
        $insert_stmt->bind_param('iss', $room_id, $user_id, $reaction);
        
        if ($insert_stmt->execute()) {
            echo json_encode(['success' => true, 'message' => 'Reaction added successfully']);
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to add reaction']);
        }
    } else {
        // Remove reaction
        $delete_query = "DELETE FROM room_reactions WHERE room_id = ? AND user_id = ? AND reaction = ?";
        $delete_stmt = $conn->prepare($delete_query);
        $delete_stmt->bind_param('iss', $room_id, $user_id, $reaction);
        
        if ($delete_stmt->execute()) {
            if ($delete_stmt->affected_rows > 0) {
                echo json_encode(['success' => true, 'message' => 'Reaction removed successfully']);
            } else {
                echo json_encode(['success' => false, 'message' => 'Reaction not found']);
            }
        } else {
            echo json_encode(['success' => false, 'message' => 'Failed to remove reaction']);
        }
    }
} catch (Exception $e) {
    echo json_encode(['success' => false, 'message' => 'Database error: ' . $e->getMessage()]);
}

$conn->close();
?>