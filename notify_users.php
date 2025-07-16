<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['notify_room_id'])) {
    header("Location: rooms.php");
    exit();
}

$room_id = $_SESSION['notify_room_id'];
unset($_SESSION['notify_room_id']);

// Fetch bookings for this room
$stmt = $conn->prepare("
    SELECT b.name, b.phone, b.email, p.plot_name, r.room_number
    FROM bookings b
    JOIN rooms r ON b.room_id = r.id
    JOIN plots p ON r.plot_id = p.plot_id
    WHERE b.room_id = ?
");
$stmt->bind_param("i", $room_id);
$stmt->execute();
$result = $stmt->get_result();

?>
<!DOCTYPE html>
<html>
<head>
    <title>Notify Booked Users</title>
    <style>
        .popup {
            width: 80%;
            margin: 30px auto;
            padding: 20px;
            background: #fefefe;
            border: 1px solid #ccc;
            box-shadow: 0 0 12px #aaa;
        }
        h2 { text-align: center; color: #444; }
        table { width: 100%; border-collapse: collapse; margin-top: 20px; }
        th, td { padding: 10px; border: 1px solid #ddd; text-align: left; }
        th { background-color: #007bff; color: white; }
        .action select { padding: 5px; }
    </style>
</head>
<body>
<div class="popup">
    <h2>Room is Now Vacant – Notify Booked Users</h2>
    <table>
        <tr>
            <th>Name</th><th>Phone</th><th>Email</th><th>Plot</th><th>Room</th><th>Action</th>
        </tr>
        <?php while ($row = $result->fetch_assoc()): 
            $name = htmlspecialchars($row['name']);
            $phone = htmlspecialchars($row['phone']);
            $email = htmlspecialchars($row['email']);
            $plot = htmlspecialchars($row['plot_name']);
            $room = htmlspecialchars($row['room_number']);
            $message = rawurlencode("Hi $name, Room $room at $plot is now vacant. If still interested, kindly confirm.");
            $subject = rawurlencode("Room Vacant Notification");
            $body = rawurlencode("Hello $name,\n\nThe room you had booked ($plot - $room) is now vacant. Kindly reach out if still interested.\n\nThanks.");

            $smsLink = "sms:+254$phone?body=$message";
            $emailLink = "mailto:$email?subject=$subject&body=$body";
            $gmailLink = "https://mail.google.com/mail/?view=cm&fs=1&to=$email&su=$subject&body=$body";
        ?>
        <tr>
            <td><?= $name ?></td>
            <td><?= $phone ?></td>
            <td><?= $email ?></td>
            <td><?= $plot ?></td>
            <td><?= $room ?></td>
            <td class="action">
                <select onchange="handleNotify(this)">
                    <option value="">Send Notification</option>
                    <option value="<?= $smsLink ?>">Send via SMS</option>
                    <option value="<?= $emailLink ?>">Send via Email App</option>
                    <option value="<?= $gmailLink ?>">Send via Gmail</option>
                </select>
            </td>
        </tr>
        <?php endwhile; ?>
    </table>
</div>
<script>
function handleNotify(select) {
    if (select.value) {
        window.open(select.value, "_blank");
        select.selectedIndex = 0;
    }
}
</script>
</body>
</html>
