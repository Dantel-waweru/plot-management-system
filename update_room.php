<?php
require_once 'includes/db.php';
session_start();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $room_id = $_POST['room_id'];
    $new_status = $_POST['status'];

    // Get the current status before updating
    $stmt = $conn->prepare("SELECT status FROM rooms WHERE id = ?");
    $stmt->bind_param("i", $room_id);
    $stmt->execute();
    $stmt->bind_result($current_status);
    $stmt->fetch();
    $stmt->close();

    // Update the room status
    $stmt = $conn->prepare("UPDATE rooms SET status = ? WHERE id = ?");
    $stmt->bind_param("si", $new_status, $room_id);
    $stmt->execute();

    // If status changed to "vacant", fetch users to notify
    if ($current_status !== 'vacant' && $new_status === 'vacant') {
        $_SESSION['notify_room_id'] = $room_id;
        header("Location: notify_users.php");
        exit();
    } else {
        header("Location: rooms.php?message=updated");
        exit();
    }
}
?>
