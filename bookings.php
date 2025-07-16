<?php
require_once 'includes/db.php';
session_start();

// Only allow landlords/admins to view
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'landlord') {
    header('Location: dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];



// Get search filters from GET params
$plotName = $_GET['plot_name'] ?? '';
$roomNumber = $_GET['room_number'] ?? '';
$bookedAt = $_GET['booked_at'] ?? '';

// Get sorting params, default sort by booked_at desc
$validSortColumns = [
    'id' => 'b.id',
    'name' => 'b.name',
    'phone' => 'b.phone',
    'email' => 'b.email',
    'plot_name' => 'p.plot_name',
    'room_number' => 'r.room_number',
    'location' => 'r.location',
    'message' => 'b.message',
    'status' => 'b.status',
    'booked_at' => 'b.booked_at'
];
$sortColumn = $_GET['sort'] ?? 'booked_at';
$sortOrder = strtolower($_GET['order'] ?? 'desc');
if (!array_key_exists($sortColumn, $validSortColumns)) {
    $sortColumn = 'booked_at';
}
if ($sortOrder !== 'asc' && $sortOrder !== 'desc') {
    $sortOrder = 'desc';
}

// Helper to toggle sorting order on headers
function nextOrder($currentOrder) {
    return $currentOrder === 'asc' ? 'desc' : 'asc';
}

// Build base query with filters
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

$query .= " ORDER BY {$validSortColumns[$sortColumn]} $sortOrder";

$stmt = $conn->prepare($query);
$stmt->bind_param($types, ...$params);
$stmt->execute();
$result = $stmt->get_result();

// Start building content
$content = '


<style>

 table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 30px;
        background: #fff;
        box-shadow: 0 0 8px #ccc;
    }
    th, td {
        padding: 10px;
        text-align: center;
        border: 1px solid #ddd;
    }
    th {
        background-color: antiquewhite;
    }
    h2 {
        text-align: center;
        margin-bottom: 20px;
        color: #333;
    }
    form {
        margin: 20px auto;
        text-align: center;
    }
    form input, form button, form a {
        padding: 8px 12px;
        margin: 5px;
        border-radius: 5px;
        border: 1px solid #ccc;
        font-size: 14px;
    }
    form button {
        background-color: #28a745;
        color: white;
        border: none;
        cursor: pointer;
    }
    form a {
        text-decoration: none;
        background-color: #dc3545;
        color: white;
        padding: 8px 12px;
    }
    table {
        width: 95%;
        margin: 0 auto 40px;
        border-collapse: collapse;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
        font-size: 14px;
        font-family: Arial, sans-serif;
    }
    th, td {
        padding: 10px;
        text-align: left;
        border: 1px solid #ddd;
        vertical-align: middle;
    }
    th {
        background-color: #007bff;
        color: white;
        cursor: pointer;
        user-select: none;
        position: relative;
    }
    th .sort-arrow {
        margin-left: 6px;
        font-size: 12px;
    }
    tr:nth-child(even) {
        background-color: #f9f9f9;
    }
    tr:hover {
        background-color: #f1f1f1;
    }
    .notify-btn {
        background-color: #17a2b8;
        color: white;
        padding: 6px 10px;
        border-radius: 4px;
        text-decoration: none;
        font-weight: bold;
        margin-right: 4px;
    }
    select {
    padding: 6px 10px;
    font-size: 14px;
    border-radius: 4px;
    border: 1px solid #ccc;
}

</style>

<h2>Room Booking Requests</h2>

<form method="GET">
plot_name <input type="text" name="plot_name" placeholder="Plot Name" value="' . htmlspecialchars($plotName) . '">
roomNumber <input type="text" name="room_number" placeholder="Room Number" value="' . htmlspecialchars($roomNumber) . '">
booking date <input type="date" name="booked_at" value="' . htmlspecialchars($bookedAt) . '">
<button type="submit">Search</button>
<a href="bookings.php">Reset</a>
</form>

<table>
    <thead>
        <tr>';

$headers = [
    '#' => null,
    'Name' => 'name',
    'Phone' => 'phone',
    'Email' => 'email',
    'Plot Name' => 'plot_name',
    'Room' => 'room_number',
    'Location' => 'location',
    'Message' => 'message',
    'Status' => 'status',
    'Booked At' => 'booked_at',
    'Action' => null
];

foreach ($headers as $label => $column) {
    if ($column) {
        $arrow = '';
        if ($sortColumn === $column) {
            $arrow = $sortOrder === 'asc' ? '▲' : '▼';
            $arrow = "<span class='sort-arrow'>$arrow</span>";
        }
        $urlParams = $_GET;
        $urlParams['sort'] = $column;
        $urlParams['order'] = ($sortColumn === $column) ? nextOrder($sortOrder) : 'asc';
        $url = htmlspecialchars($_SERVER['PHP_SELF']) . '?' . http_build_query($urlParams);

        $content .= "<th><a href='$url' style='color:inherit; text-decoration:none;'>{$label}{$arrow}</a></th>";
    } else {
        $content .= "<th>{$label}</th>";
    }
}

$content .= '
        </tr>
    </thead>
    <tbody>';

$content .= '
        </tr>
    </thead>
    <tbody>';

$content .= '
        </tr>
    </thead>
    <tbody>';

$counter = 1;
while ($row = $result->fetch_assoc()) {
    $phone = htmlspecialchars($row['phone']);
    $email = htmlspecialchars($row['email']);
    $name = htmlspecialchars($row['name']);
    $plot = htmlspecialchars($row['plot_name']);
    $room = htmlspecialchars($row['room_number']);
    $message = rawurlencode("Hello $name, your booking at $plot (Room $room) was received. and the room is now vacant incase you are still interested");

    $subject = rawurlencode("Room Booking Notification");
    $body = rawurlencode("Dear $name,\n\nThank you for booking at $plot, Room $room.\nWe have received your request and will get back to you shortly.\n\nRegards,\nPlot Management");
    $gmailLink = "https://mail.google.com/mail/?view=cm&fs=1&to=$email&su=$subject&body=$body";

    $smsLink = "sms:+254$phone?body=$message";
    $emailLink = "mailto:$email?subject=$subject&body=$body";

    $content .= "<tr data-booking-id='{$row['id']}'>
    <td>{$counter}</td>
    <td>$name</td>
    <td>$phone</td>
    <td>$email</td>
    <td>$plot</td>
    <td>$room</td>
    <td>" . htmlspecialchars($row['location']) . "</td>
    <td>" . htmlspecialchars($row['message']) . "</td>
    <td>" . htmlspecialchars($row['status']) . "</td>
    <td>" . htmlspecialchars($row['booked_at']) . "</td>
    <td>
        <select onchange='handleAction(this)'>
            <option value=''>Send Notification</option>
            <option value=\"$smsLink\">Send via SMS</option>
            <option value=\"$emailLink\">Send via Email App</option>
            <option value=\"$gmailLink\">Send via Gmail</option>
        </select>
    </td>
</tr>";


    $counter++;
}

 


if ($counter === 1) {
    $content .= "<tr><td colspan='11' style='text-align:center;'>No bookings found.</td></tr>";
}


$content .= '
    </tbody>
</table>';
$content .= '
<script>
function handleAction(selectElement) {
    const value = selectElement.value;
    if (value) {
        window.open(value, "_blank");
        selectElement.selectedIndex = 0; // Reset to "Select Action"
    }
}
</script>
<script>
function handleAction(selectElement) {
    const url = selectElement.value;
    if (!url) return;

    // Extract booking ID from the table row
    const row = selectElement.closest("tr");
    const bookingId = row.dataset.bookingId;

    // Open notification link
    window.open(url, "_blank");

    // Send AJAX request to update status
    if (bookingId) {
        fetch("update_status.php", {
            method:"POST",
            headers: {
                "Content-Type": "application/json"
            },
            body: JSON.stringify({ id: bookingId })
        }).then(res => res.json()).then(data => {
            if (data.success) {
                // Update the status cell
                const statusCell = row.querySelector("td:nth-child(9)");
                statusCell.textContent = "notified";
            } else {
                alert("Failed to update status");
            }
        });
    }
}
</script>

';

include 'layout.php';

?>
