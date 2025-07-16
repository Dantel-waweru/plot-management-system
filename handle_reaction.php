<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo json_encode(["status" => "error", "message" => "Not logged in."]);
    exit();
}

$user_id = $_SESSION['user_id'];
$room_id = $_POST['room_id'] ?? 0;
$reaction_type = $_POST['reaction_type'] ?? '';

if (empty($room_id) || empty($reaction_type)) {
    echo json_encode(["status" => "error", "message" => "Invalid input."]);
    exit();
}

$room_id = mysqli_real_escape_string($conn, $room_id);
$reaction_type = mysqli_real_escape_string($conn, $reaction_type);

$query = "SELECT * FROM reactions WHERE user_id = '$user_id' AND room_id = '$room_id'";
$result = mysqli_query($conn, $query);

if (mysqli_num_rows($result) > 0) {
    mysqli_query($conn, "UPDATE reactions SET reaction_type = '$reaction_type' WHERE user_id = '$user_id' AND room_id = '$room_id'");
} else {
    mysqli_query($conn, "INSERT INTO reactions (room_id, user_id, reaction_type) VALUES ('$room_id', '$user_id', '$reaction_type')");
}

// Fetch updated counts
$count_query = "SELECT reaction_type, COUNT(*) AS count FROM reactions WHERE room_id = '$room_id' GROUP BY reaction_type";
$count_result = mysqli_query($conn, $count_query);
$counts = ['like' => 0, 'love' => 0, 'interested' => 0];

while ($row = mysqli_fetch_assoc($count_result)) {
    $counts[$row['reaction_type']] = $row['count'];
}

echo json_encode(["status" => "success", "message" => "Reaction saved.", "updatedCounts" => $counts]);
?>
