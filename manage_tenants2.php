<?php
require_once 'includes/db.php';
session_start();

// Simulate a logged-in landlord
$logged_in_landlord_id = $_SESSION['user_id'] ?? 1;

$message = "";

// Handle Add or Update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $landlord_id = $logged_in_landlord_id;
    $plot_id = $_POST['plot_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $room_number = $_POST['room_number'];
    $paid_status = $_POST['paid_status'];
    $amount_paid = $_POST['amount_paid'];

    // Get room_id from rooms
    $room_result = mysqli_query($conn, "SELECT id FROM rooms WHERE plot_id='$plot_id' AND room_number='$room_number'");
    $room_row = mysqli_fetch_assoc($room_result);
    $room_id = $room_row['id'] ?? null;

    if (!$room_id) {
        $message = "<p style='color:red;'>Room ID not found. Please check the selected room.</p>";
    } else {
        if (isset($_POST['update'])) {
            $tenant_id = $_POST['tenant_id'];

            // Get current (old) room and plot for the tenant
            $old_query = mysqli_query($conn, "SELECT plot_id, room_number FROM tenants WHERE tenant_id=$tenant_id");
            $old_data = mysqli_fetch_assoc($old_query);
            $old_plot = $old_data['plot_id'];
            $old_room = $old_data['room_number'];

            // Update tenant
            $update_sql = "UPDATE tenants SET 
                landlord_id='$landlord_id', 
                plot_id='$plot_id', 
                name='$name', 
                phone='$phone', 
                email='$email', 
                room_number='$room_number', 
                paid_status='$paid_status', 
                amount_paid='$amount_paid',
                room_id='$room_id'
                WHERE tenant_id=$tenant_id";
// Get old amount_paid before update
$old_amount_query = mysqli_query($conn, "SELECT amount_paid FROM tenants WHERE tenant_id=$tenant_id");
$old_amount_row = mysqli_fetch_assoc($old_amount_query);
$old_amount_paid = $old_amount_row['amount_paid'] ?? 0;

if ($amount_paid != $old_amount_paid) {
    $check_payment = mysqli_query($conn, "SELECT * FROM payments WHERE tenant_id='$tenant_id'");
    if (mysqli_num_rows($check_payment) > 0) {
        // Update existing payment
        mysqli_query($conn, "UPDATE payments SET amount='$amount_paid', payment_date=NOW(), notes='Edited payment' WHERE tenant_id='$tenant_id'");
    } else {
        // Insert new payment record if missing
        mysqli_query($conn, "INSERT INTO payments (tenant_id, plot_id, room_number, amount, payment_date, payment_method, reference, notes, created_at)
        VALUES ('$tenant_id', '$plot_id', '$room_number', '$amount_paid', NOW(), 'Manual', 'Updated-Ref', 'Auto-generated on tenant edit', NOW())");
    }
}

            if (mysqli_query($conn, $update_sql)) {
                // Update room status if changed
                if ($old_plot != $plot_id || $old_room != $room_number) {
                    mysqli_query($conn, "UPDATE rooms SET status='Vacant' WHERE plot_id='$old_plot' AND room_number='$old_room'");
                    mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE id='$room_id'");
                }
                $message = "<p style='color:green;'>Tenant updated successfully.</p>";
            } else {
                $message = "<p style='color:red;'>Update Failed: " . mysqli_error($conn) . "</p>";
            }
        } else {
            // Insert new tenant
            $insert_sql = "INSERT INTO tenants (landlord_id, plot_id, name, phone, email, room_number, paid_status, amount_paid, room_id)
                           VALUES ('$landlord_id', '$plot_id', '$name', '$phone', '$email', '$room_number', '$paid_status', '$amount_paid', '$room_id')";

            if (mysqli_query($conn, $insert_sql)) {
    $new_tenant_id = mysqli_insert_id($conn); // Get newly inserted tenant ID
    mysqli_query($conn, "UPDATE rooms SET status='Occupied' WHERE id='$room_id'");

    // If paid, record payment
    if ($paid_status === 'Paid' && $amount_paid > 0) {
        $payment_date = date('Y-m-d');
        $payment_method = 'Cash'; // Or pull from form if needed
        $reference = 'Initial Payment'; // Could be dynamic
        $notes = 'Payment recorded on tenant registration';
        $created_at = date('Y-m-d H:i:s');

        $insert_payment_sql = "INSERT INTO payments (tenant_id, plot_id, room_number, amount, payment_date, payment_method, reference, notes, created_at)
                               VALUES ('$new_tenant_id', '$plot_id', '$room_number', '$amount_paid', '$payment_date', '$payment_method', '$reference', '$notes', '$created_at')";
        mysqli_query($conn, $insert_payment_sql);
    }

    $message = "<p style='color:green;'>Tenant added successfully and payment recorded.</p>";
} else {
    $message = "<p style='color:red;'>Insert Failed: " . mysqli_error($conn) . "</p>";
}
}
    }
}

// Handle Delete
if (isset($_GET['delete'])) {
    $id = $_GET['delete'];

    // Get room info before deletion
    $room_query = mysqli_query($conn, "SELECT plot_id, room_number FROM tenants WHERE tenant_id=$id");
    $room_data = mysqli_fetch_assoc($room_query);

    mysqli_query($conn, "DELETE FROM tenants WHERE tenant_id=$id");
    // Also delete payment record for this tenant
mysqli_query($conn, "DELETE FROM payments WHERE tenant_id=$id");


    // Set room to Vacant
    if ($room_data) {
        $plot_id = $room_data['plot_id'];
        $room_number = $room_data['room_number'];
        mysqli_query($conn, "UPDATE rooms SET status='Vacant' WHERE plot_id='$plot_id' AND room_number='$room_number'");
    }

    $message = "<p style='color:red;'>Tenant deleted and room marked as vacant.</p>";
}

// Edit data
$edit_data = null;
if (isset($_GET['edit'])) {
    $id = $_GET['edit'];
    $edit_result = mysqli_query($conn, "SELECT * FROM tenants WHERE tenant_id=$id");
    if ($edit_result && mysqli_num_rows($edit_result) === 1) {
        $edit_data = mysqli_fetch_assoc($edit_result);
    }
}

// Get plots
$plots = [];
$plot_sql = "SELECT plot_id, plot_name FROM plots WHERE landlord_id = $logged_in_landlord_id";
$plot_result = mysqli_query($conn, $plot_sql);
while ($row = mysqli_fetch_assoc($plot_result)) {
    $plots[] = $row;
}

// Get tenant list
$sql = "SELECT t.*, r.price 
        FROM tenants t 
        LEFT JOIN rooms r ON t.room_id = r.id
        WHERE t.landlord_id = $logged_in_landlord_id
        ORDER BY t.tenant_id DESC";
$result = mysqli_query($conn, $sql);

// Output layout
?>
<?php ob_start(); ?>

<!-- Your HTML/PHP view for displaying form, message, and tenants -->
<!-- For example: -->

<?= $message ?>

<!-- Tenant Form and List Goes Here -->



<style>
    .form-section, .table-section {
    background-color: white;
    padding: 30px;
    margin: 3px auto;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1400px;
}
/* Form Inputs */
input[type="text"],
input[type="number"],
input[type="file"],
select,
textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0 20px auto;
    border: 1px solid #ccc;
    border-radius: 5px;
}
 table {
        border-collapse: collapse;
       width: 100%;
overflow-x: auto;
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
    }

/* Buttons */
button,
a.btn {
    display: inline-block;
    padding: 10px 20px;
    background-color: #007bff;
    border: none;
    color: white;
    border-radius: 6px;
    text-decoration: none;
    cursor: pointer;
    margin-top: 10px;
    transition: background-color 0.3s ease;
}

button:hover,
a.btn:hover {
    background-color: #0056b3;
}

img {
    max-width: 80px;
    border-radius: 4px;
}

/* Success message */
.success {
    color: green;
    font-weight: bold;
    margin-bottom: 10px;
    text-align: center;
}

/* Action links */
.action-btns a {
    margin: 0 5px;
    color: #007bff;
    text-decoration: none;
}

.action-btns a:hover {
    text-decoration: underline;
}

    button[type="submit"] {
        margin-top: 20px;
        padding: 12px 20px;
        background-color: #007bff;
        color: white;
        border: none;
        border-radius: 6px;
        cursor: pointer;
        font-size: 16px;
        width: 100%;
    }

    button[type="submit"]:hover {
        background-color: #0056b3;
    }

    .success {
        color: green;
        font-weight: bold;
        text-align: center;
    }
    form {
        max-width: 800px;
        padding: 20px;
        margin: 20px auto;
        border: 1px solid #ccc;
        border-radius: 8px;
        background: #f9f9f9;
        box-shadow: 2px 2px 12px rgba(0,0,0,0.1);
        font-family: Arial, sans-serif;
    }
    form label {
        font-weight: bold;
    }
    form input[type=text],
    form input[type=email],
    form input[type=number],
    form select {
        width: 100%;
        padding: 8px;
        margin-top: 5px;
        margin-bottom: 15px;
        border: 1px solid #ccc;
        border-radius: 4px;
        box-sizing: border-box;
        font-size: 14px;
    }
    form input[type=submit] {
        background-color: #4CAF50;
        color: white;
        font-weight: bold;
        padding: 10px 15px;
        border: none;
        border-radius: 4px;
        cursor: pointer;
        font-size: 16px;
    }
    form input[type=submit]:hover {
        background-color: #45a049;
    }

    table {
        width: 80%;
        margin: 20px auto;
        border-collapse: collapse;
        font-family: Arial, sans-serif;
        box-shadow: 0 0 10px rgba(0,0,0,0.1);
    }
    table, th, td {
        border: 1px solid #ddd;
    }
    th, td {
        text-align: left;
        padding: 12px;
    }
    th {
        background-color: #4CAF50;
        color: white;
    }
    tr:nth-child(even) {
        background-color: #f2f2f2;
    }
    tr:hover {
        background-color: #ddd;
    }
    a {
        text-decoration: none;
        color: #0066cc;
    }
    a:hover {
        text-decoration: underline;
    }


</style>

<div class="content">
    
<div class="form-section">
    <form method="POST">
        <h2 style="text-align:center;">Tenants Management</h2>

    <?php if (isset($message)) echo $message; ?>

    <h3 style="text-align:center;"><?= $edit_data ? 'Edit Tenant' : 'Add New Tenant' ?></h3>
        <input type="hidden" name="tenant_id" value="<?= htmlspecialchars($edit_data['tenant_id'] ?? '') ?>">

        <label>Landlord ID:</label><br>
        <input type="text" name="landlord_id" value="<?= htmlspecialchars($logged_in_landlord_id) ?>" readonly>

        <label>Plot Name:</label><br>
        <select name="plot_id" id="plotSelect" required>
            <option value="">Select Plot</option>
            <?php foreach ($plots as $plot): ?>
                <option value="<?= $plot['plot_id'] ?>"
                    <?= (isset($edit_data['plot_id']) && $edit_data['plot_id'] == $plot['plot_id']) ? 'selected' : '' ?>>
                    <?= htmlspecialchars($plot['plot_name']) ?>
                </option>
            <?php endforeach; ?>
        </select>

        <label>Room Number 1:</label><br>
        <select name="room_number" id="roomSelect" required>
            <option value="">Select Room</option>
        </select>
        <label>Room Price:</label><br>
<input type="text" id="roomPrice" readonly style="background:#f9f9f9;">

        <label>Name:</label><br>
        <input type="text" name="name" value="<?= htmlspecialchars($edit_data['name'] ?? '') ?>" required>

        <label>Phone:</label><br>
        <input type="text" name="phone" value="<?= htmlspecialchars($edit_data['phone'] ?? '') ?>" required>

        <label>Email22:</label><br>
        <input type="email" name="email" value="<?= htmlspecialchars($edit_data['email'] ?? '') ?>" required>

        <label>Paid Status:</label><br>
        <select name="paid_status" required>
            <option value="Paid" <?= (($edit_data['paid_status'] ?? '') == 'Paid') ? 'selected' : '' ?>>Paid</option>
            <option value="Unpaid" <?= (($edit_data['paid_status'] ?? '') == 'Unpaid') ? 'selected' : '' ?>>Unpaid</option>
        </select>

        <label>Amount Paid:</label><br>
        <input type="number" name="amount_paid" value="<?= htmlspecialchars($edit_data['amount_paid'] ?? 0) ?>" required>

        <input type="submit" name="<?= $edit_data ? 'update' : 'submit' ?>" value="<?= $edit_data ? 'Update Tenant' : 'Add Tenant' ?>">
    </form>
</div>
    <hr>
<div style="overflow-x:auto;" class="table-section">

    <table>
        
            <thead>
<tr>
    <th>Tenant ID</th>
    <th>Name</th>
    <th>Phone</th>
    <th>Email2</th>
    <th>Plot ID</th>
    <th>Room Nox</th>
    <th>Monthly Rent</th>
    <th>Months Elapsed</th>
    <th>Expected Rent</th>
    <th>Amount Paid</th>
    <th>Balance</th>
    <th>Status</th>
    <th>Actions</th>
</tr>
</thead>
    
   <?php while ($row = mysqli_fetch_assoc($result)):
    $tenant_id = $row['tenant_id'];
    $room_price = $row['price'] ?? 0;

    // Fetch total paid and earliest payment date from payments table
    $payment_sql = "SELECT SUM(amount) AS total_paid, MIN(payment_date) AS first_payment FROM payments WHERE tenant_id = '$tenant_id'";
    $payment_result = mysqli_query($conn, $payment_sql);
    $payment_data = mysqli_fetch_assoc($payment_result);

    $amount_paid = $payment_data['total_paid'] ?? 0;
    $first_payment_date_str = $payment_data['first_payment'] ?? null;

    // If no payment date found, assume 1 month elapsed
    $start_date = $first_payment_date_str ? new DateTime($first_payment_date_str) : (new DateTime())->modify('-1 month');
    $now = new DateTime();

    // Calculate months elapsed based on calendar
    $interval = $start_date->diff($now);
    $months_elapsed = ($interval->y * 12 + $interval->m + 1); // +1 to include current month

    $expected_total = $room_price * $months_elapsed;
    $balance = $expected_total - $amount_paid;
?>
<tr>
    <td><?= $tenant_id ?></td>
    <td><?= htmlspecialchars($row['name']) ?></td>
    <td><?= htmlspecialchars($row['phone']) ?></td>
    <td><?= htmlspecialchars($row['email']) ?></td>
    <td><?= htmlspecialchars($row['plot_id']) ?></td>
    <td><?= htmlspecialchars($row['room_number']) ?></td>
    <td><?= number_format($room_price, 2) ?></td>
    <td><?= $months_elapsed ?></td>
    <td><?= number_format($expected_total, 2) ?></td>
    <td><?= number_format($amount_paid, 2) ?></td>
    <td style="color: <?= ($balance > 0) ? 'red' : 'green' ?>"><?= number_format($balance, 2) ?></td>
    <td><?= htmlspecialchars($row['paid_status']) ?></td>
    <td>
        <a href="?edit=<?= $tenant_id ?>">✏️ Edit</a> | 
        <a href="?delete=<?= $tenant_id ?>" onclick="return confirm('Delete this tenant?')">🗑️ Delete</a>
    </td>
</tr>
<?php endwhile; ?>


    </table>
  </div>
  
</div>

<script>
document.addEventListener('DOMContentLoaded', function() {
    const plotSelect = document.getElementById('plotSelect');
    const roomSelect = document.getElementById('roomSelect');

    function loadVacantRooms(plotId, selectedRoom = '') {
        roomSelect.innerHTML = '<option>Loading...</option>';
        fetch('fetch_vacant_rooms.php?plot_id=' + plotId)
            .then(response => response.json())
            .then(data => {
                roomSelect.innerHTML = '<option value="">Select Room</option>';
                if (data.length === 0) {
                    roomSelect.innerHTML = '<option value="">No vacant rooms</option>';
                    return;
                }
                data.forEach(room => {
                    const option = document.createElement('option');
                    option.value = room.room_number;
                    option.textContent = room.room_number;
                    if (room.room_number === selectedRoom) {
                        option.selected = true;
                    }
                    roomSelect.appendChild(option);
                });
            })
            .catch(err => {
                roomSelect.innerHTML = '<option value="">Error loading rooms</option>';
                console.error(err);
            });
    }

    <?php if ($edit_data && isset($edit_data['plot_id'])): ?>
        loadVacantRooms('<?= $edit_data['plot_id'] ?>', '<?= $edit_data['room_number'] ?>');
    <?php endif; ?>

    plotSelect.addEventListener('change', function() {
        if (this.value) {
            loadVacantRooms(this.value);
        } else {
            roomSelect.innerHTML = '<option value="">Select Room</option>';
        }
    });
});
</script>
<script>
document.addEventListener("DOMContentLoaded", function () {
    const roomSelect = document.getElementById("roomSelect");
    const plotSelect = document.getElementById("plotSelect");
    const roomPriceField = document.getElementById("roomPrice");

    function fetchRoomPrice() {
        const roomNumber = roomSelect.value;
        const plotId = plotSelect.value;

        if (roomNumber && plotId) {
            fetch(`get_room_price.php?room_number=${roomNumber}&plot_id=${plotId}`)
                .then(response => response.json())
                .then(data => {
                    roomPriceField.value = data.price || '0';
                })
                .catch(error => {
                    console.error("Error fetching preice:", error);
                    roomPriceField.value = '0';
                });
        } else {
            roomPriceField.value = '';
        }
    }

    roomSelect.addEventListener("change", fetchRoomPrice);
    plotSelect.addEventListener("change", function () {
        // Optional: Clear room price if plot changes
        roomPriceField.value = '';
    });
});
</script>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
