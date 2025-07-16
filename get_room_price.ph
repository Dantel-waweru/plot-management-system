<?php
require_once 'includes/db.php';

if (isset($_GET['room_number']) && isset($_GET['plot_id'])) {
    $room_number = $_GET['room_number'];
    $plot_id = $_GET['plot_id'];

    $stmt = mysqli_prepare($conn, "SELECT price FROM rooms WHERE room_number = ? AND plot_id = ?");
    mysqli_stmt_bind_param($stmt, "si", $room_number, $plot_id);
    mysqli_stmt_execute($stmt);
    mysqli_stmt_bind_result($stmt, $price);
    mysqli_stmt_fetch($stmt);

    echo json_encode(['price' => $price ?? 0]);
    exit;
}
?>
