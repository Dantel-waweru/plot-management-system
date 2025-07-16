<?php
require_once 'includes/db.php';
require_once 'config.php';
session_start();

mysqli_report(MYSQLI_REPORT_ERROR | MYSQLI_REPORT_STRICT);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['notify'])) {
    $room_id = $_POST['room_id'];
    $name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);

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

    try {
        // Insert notification with landlord's user_id
        $stmt = $conn->prepare("INSERT INTO notifications (user_id, room_id, name, phone, email, message, type, status) 
                                VALUES (?, ?, ?, ?, ?, ?, 'email', 'unread')");
        $stmt->bind_param("iissss", $landlord_id, $room_id, $name, $phone, $email, $message);
        $stmt->execute();
$stmt = $conn->prepare("INSERT INTO bookings (room_id, user_id, name, phone, email, message, status) 
                        VALUES (?, ?, ?, ?, ?, ?, 'pending')");
$stmt->bind_param("iissss", $room_id, $landlord_id, $name, $phone, $email, $message);
$stmt->execute();
        echo "<script>alert('Thank you! You will be notified when the room is vacant.'); window.location.href='rooms.php';</script>";
    } catch (Exception $e) {
        echo "Error inserting notification: " . $e->getMessage();
    }
}
?>
