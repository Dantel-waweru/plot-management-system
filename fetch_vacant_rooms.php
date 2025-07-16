<?php
require_once 'includes/db.php';

// Validate plot_id
if (!isset($_GET['plot_id'])) {
    echo json_encode([]);
    exit;
}

$plot_id = mysqli_real_escape_string($conn, $_GET['plot_id']);
$tenant_id = isset($_GET['tenant_id']) ? mysqli_real_escape_string($conn, $_GET['tenant_id']) : null;

$rooms = [];

// If tenant_id is provided, get the current room of the tenant
$current_room = null;
if ($tenant_id) {
    $query = "SELECT room_number FROM tenants WHERE tenant_id='$tenant_id' AND plot_id='$plot_id'";
    $result = mysqli_query($conn, $query);
    if ($result && mysqli_num_rows($result) === 1) {
        $row = mysqli_fetch_assoc($result);
        $current_room = $row['room_number'];
    }
}

// Now fetch rooms: either vacant OR the current tenant's room
$sql = "SELECT room_number FROM rooms WHERE plot_id='$plot_id' AND (status='Vacant'";
if ($current_room !== null) {
    $sql .= " OR room_number='$current_room'";
}
$sql .= ") ORDER BY room_number";

$result = mysqli_query($conn, $sql);
while ($row = mysqli_fetch_assoc($result)) {
    $rooms[] = $row;
}

header('Content-Type: application/json');
echo json_encode($rooms);
?>
