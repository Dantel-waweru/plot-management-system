<?php
// super_admin_dashboard.php
include 'config.php'; // your DB connection file


// Check if super admin is logged in

session_start();
if (!isset($_SESSION['user_role']) || $_SESSION['user_role'] !== 'super_admin') {
    header("Location: login.php");
    exit();
}
// 1. Total Landlords
$landlords_result = $conn->query("SELECT COUNT(*) AS total FROM landlords");
$landlords = $landlords_result->fetch_assoc();

// 2. Pending Landlords
$pending_result = $conn->query("SELECT COUNT(*) AS pending FROM landlords WHERE status = 'pending'");
$pending = $pending_result->fetch_assoc();

// 3. Total Plots
$plots_result = $conn->query("SELECT COUNT(*) AS total FROM plots");
$plots = $plots_result->fetch_assoc();

// 4. Total Rooms & Occupancy
$room_result = $conn->query("
    SELECT 
        COUNT(*) AS total,
        SUM(CASE WHEN status = 'occupied' THEN 1 ELSE 0 END) AS occupied,
        SUM(CASE WHEN status = 'vacant' THEN 1 ELSE 0 END) AS vacant
    FROM rooms
");
$rooms = $room_result->fetch_assoc();

// 5. Latest Bookings
$bookings_result = $conn->query("SELECT * FROM bookings ORDER BY booked_at DESC LIMIT 5");

// 6. Pending Approvals (details)
$pending_landlords_result = $conn->query("SELECT * FROM landlords WHERE status = 'pending'");

// Handle approval or reset (if form submitted)
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['approve_id'])) {
        $id = intval($_POST['approve_id']);
        $conn->query("UPDATE landlords SET status = 'approved' WHERE landlord_id = $id");
        header("Location: super_admin_dashboard.php");
        exit();
    }

    if (isset($_POST['reset_id']) && isset($_POST['new_pass'])) {
        $id = intval($_POST['reset_id']);
        $new_pass = password_hash($_POST['new_pass'], PASSWORD_DEFAULT);
        $conn->query("UPDATE landlords SET password = '$new_pass' WHERE landlord_id = $id");
        header("Location: super_admin_dashboard.php");
        exit();
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Super Admin Dashboard</title>
    <style>
        body { font-family: Arial; background: #f4f4f4; padding: 20px; }
        .card { background: white; padding: 20px; margin: 10px; border-radius: 10px; box-shadow: 0 0 5px #ccc; display: inline-block; width: 200px; vertical-align: top; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ccc; }
        h2 { margin-top: 40px; }
        .form { margin-top: 10px; }
        input[type="password"] { padding: 5px; width: 70%; }
        input[type="submit"] { padding: 5px 10px; }
    </style>
</head>
<body>

<h1>👑 Super Admin Dashboard</h1>

<div class="card">
    <h3>Total Landlords</h3>
    <p><?= $landlords['total'] ?></p>
</div>

<div class="card">
    <h3>Pending Approvals</h3>
    <p><?= $pending['pending'] ?></p>
</div>

<div class="card">
    <h3>Total Plots</h3>
    <p><?= $plots['total'] ?></p>
</div>

<div class="card">
    <h3>Rooms</h3>
    <p>Total: <?= $rooms['total'] ?></p>
    <p>Occupied: <?= $rooms['occupied'] ?></p>
    <p>Vacant: <?= $rooms['vacant'] ?></p>
</div>

<h2>📅 Latest Bookings</h2>
<table>
    <tr>
        <th>Name</th><th>Email</th><th>Phone</th><th>Room ID</th><th>Status</th><th>Booked At</th>
    </tr>
    <?php while ($b = $bookings_result->fetch_assoc()): ?>
        <tr>
            <td><?= $b['name'] ?></td>
            <td><?= $b['email'] ?></td>
            <td><?= $b['phone'] ?></td>
            <td><?= $b['room_id'] ?></td>
            <td><?= $b['status'] ?></td>
            <td><?= $b['booked_at'] ?></td>
        </tr>
    <?php endwhile; ?>
</table>

<h2>🕵️ Pending Landlord Approvals</h2>
<table>
    <tr>
        <th>Name</th><th>Email</th><th>Phone</th><th>Approve</th><th>Password Reset</th>
    </tr>
    <?php while ($l = $pending_landlords_result->fetch_assoc()): ?>
        <tr>
            <td><?= $l['name'] ?></td>
            <td><?= $l['email'] ?></td>
            <td><?= $l['phone'] ?></td>
            <td>
                <form method="POST">
                    <input type="hidden" name="approve_id" value="<?= $l['landlord_id'] ?>">
                    <input type="submit" value="✅ Approve">
                </form>
            </td>
            <td>
                <form method="POST" class="form">
                    <input type="hidden" name="reset_id" value="<?= $l['landlord_id'] ?>">
                    <input type="password" name="new_pass" placeholder="New Password">
                    <input type="submit" value="🔒 Reset">
                </form>
            </td>
        </tr>
    <?php endwhile; ?>
</table>

</body>
</html>
