<?php
session_start();
require_once 'includes/db.php';

// Allow only landlords or admins
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'landlord') {
    http_response_code(403);
    echo json_encode(['error' => 'Unauthorized']);
    exit;
}

$logged_in_user_id = $_SESSION['user_id'];

$sql = "SELECT * FROM notifications WHERE  user_id = ? ORDER BY created_at DESC LIMIT 20";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $logged_in_user_id);
$stmt->execute();
$result = $stmt->get_result();

$notifications = [];
while ($row = $result->fetch_assoc()) {
    $notifications[] = $row;
}

echo json_encode($notifications);
?>