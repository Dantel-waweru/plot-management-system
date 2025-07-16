<?php
require_once 'includes/db.php';
session_start();

// Only allow landlords/admins to view
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'landlord') {
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Get search filters from GET parameters
$plotName = $_GET['plot_name'] ?? '';
$roomNumber = $_GET['room_number'] ?? '';
$bookedAt = $_GET['booked_at'] ?? '';

// Build dynamic SQL with filters
$query = "
    SELECT 
        b.id, b.name, b.phone, b.email, b.message, b.booked_at, b.status,
        r.room_number, r.location,
        p.plot_name
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN plots p ON r.plot_id = p.plot_id
    WHERE b.user_id = ?
";

$params = [$user_id];
$types = "i";

if (!empty($plotName)) {
    $query .= " AND p.plot_name LIKE ?";
    $params[] = "%$plotName%";
    $types .= "s";
}
if (!empty($roomNumber)) {
    $query .= " AND r.room_number LIKE ?";
    $params[] = "%$roomNumber%";
    $types .= "s";
}
if (!empty($bookedAt)) {
    $query .= " AND DATE(b.booked_at) = ?";
    $params[] = $bookedAt;
    $types .= "s";
}

$query .= " ORDER BY b.booked_at DESC";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Begin HTML content
$content = "<h2>Room Booking Requests</h2>";

// Search form
$content .= '
<form method="GET" style="margin-bottom: 20px;">
    <input type="text" name="plot_name" placeholder="Plot Name" value="' . htmlspecialchars($plotName) . '">
    <input type="text" name="room_number" placeholder="Room Number" value="' . htmlspecialchars($roomNumber) . '">
    <input type="date" name="booked_at" value="' . htmlspecialchars($bookedAt) . '">
    <button type="submit">Search</button>
    <a href="bookings.php" style="margin-left: 10px;">Reset</a>
</form>
';

$content .= "<table border='1' cellpadding='10' cellspacing='0'>
<thead>
    <tr>
        <th>#</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Plot Name</th>
        <th>Room</th>
        <th>Location</th>
        <th>Message</th>
        <th>Status</th>
        <th>Booked At</th>
        <th>Action</th>
    </tr>
</thead>
<tbody>";

$counter = 1;
while ($row = $result->fetch_assoc()) {
    $content .= "<tr>
        <td>{$counter}</td>
        <td>{$row['name']}</td>
        <td>{$row['phone']}</td>
        <td>{$row['email']}</td>
        <td>{$row['plot_name']}</td>
        <td>{$row['room_number']}</td>
        <td>{$row['location']}</td>
        <td>{$row['message']}</td>
        <td>{$row['status']}</td>
        <td>{$row['booked_at']}</td>
        <td><a href='send_notification.php?booking_id={$row['id']}' onclick=\"return confirm('Send notification to this user?')\">Notify</a></td>
    </tr>";
    $counter++;
}

if ($counter === 1) {
    $content .= "<tr><td colspan='11'>No bookings found.</td></tr>";
}

$content .= "</tbody></table>";

// Include layout template (assumes $content will be printed in layout.php)
include 'layout.php';
?>
