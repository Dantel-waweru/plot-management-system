<?php
require_once 'includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = intval($_POST['room_id']);
    $reaction = $_POST['reaction'];

    $valid_reactions = ['like', 'love', 'interested'];
    if (!in_array($reaction, $valid_reactions)) {
        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Invalid reaction']);
        exit;
    }

    $stmt = $conn->prepare("INSERT INTO reactions (room_id, reaction_type) VALUES (?, ?)");
    $stmt->bind_param("is", $room_id, $reaction);
    if ($stmt->execute()) {
        echo json_encode(['status' => 'success']);
    } else {
        echo json_encode(['status' => 'error', 'message' => 'Database error']);
    }
}
?>
