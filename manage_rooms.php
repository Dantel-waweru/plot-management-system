

<?php

session_start();
// Include DB connection
include('includes/db.php');
$landlord_id = $_SESSION['user_id'];
$sql = "SELECT * FROM plots WHERE landlord_id = $landlord_id ORDER BY created_at DESC";

$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error executing query: " . mysqli_error($conn));
}

// Plot selection
$plot_id = isset($_POST['plot_id']) ? (int)$_POST['plot_id'] : 0;
$plot_id = isset($_GET['plot_id']) ? (int)$_GET['plot_id'] : 0;
$plot_id = $_GET['plot_id'] ?? $_POST['plot_id'] ?? 0;
// Handle Add Room
if (isset($_POST['add_room'])) {
    $plot_id = $_POST['plot_id'];
    $room_number = $_POST['room_number'];
    $location = $_POST['location'];
    $size = $_POST['size'];
    $price = $_POST['price'];
    $status = $_POST['status'];
    $image = $_FILES['image']['name'];
    $image_tmp = $_FILES['image']['tmp_name'];

    $landlord_id = $landlord_id;
    $user_id = $landlord_id;

    $image_path = '';
    if (!empty($image)) {
        $image_path = "images/" . basename($image);
        move_uploaded_file($image_tmp, $image_path);
    }

    $sql = "INSERT INTO rooms (plot_id, landlord_id, room_number, user_id, location, size, price, status, image)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'iisissdss', $plot_id, $landlord_id, $room_number, $user_id, $location, $size, $price, $status, $image_path);
    mysqli_stmt_execute($stmt);

    $room_id = mysqli_insert_id($conn);

    // Insert amenities
    if (!empty($_POST['amenities'])) {
        $amenities = array_map('trim', explode(',', $_POST['amenities']));
        foreach ($amenities as $amenity) {
            $amenity = mysqli_real_escape_string($conn, $amenity);
            mysqli_query($conn, "INSERT INTO amenities (room_id, amenity) VALUES ($room_id, '$amenity')");
        }
    }

    // Update plot info
    $update_sql = "UPDATE plots SET num_rooms = num_rooms + 1, " . ($status == 'vacant' ? "vacant_rooms" : "occupied_rooms") . " = " . ($status == 'vacant' ? "vacant_rooms" : "occupied_rooms") . " + 1 WHERE plot_id = $plot_id";
    mysqli_query($conn, $update_sql);

    $success_msg = "Room added successfully.";
}

// Handle Delete Room
if (isset($_GET['delete_room'])) {
    $id = (int)$_GET['delete_room'];
    $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT plot_id, status FROM rooms WHERE id = $id"));
    $plot_id = $room['plot_id'];
    $status = $room['status'];
$room_id = $room['id'];
    $amenities_result = mysqli_query($conn, "SELECT amenity FROM amenities WHERE room_id = $id");
    $amenities = [];
    while ($row = mysqli_fetch_assoc($amenities_result)) {
        $amenities[] = $row['amenity'];
    }
    $amenities_str = implode(', ', $amenities);

    // Delete amenities first
    mysqli_query($conn, "DELETE FROM amenities WHERE room_id = $id");

    // Delete room
    mysqli_query($conn, "DELETE FROM rooms WHERE id = $id");

    // Update plot info
    $update_sql = "UPDATE plots SET num_rooms = num_rooms - 1, " . ($status == 'vacant' ? "vacant_rooms" : "occupied_rooms") . " = " . ($status == 'vacant' ? "vacant_rooms" : "occupied_rooms") . " - 1 WHERE plot_id = $plot_id";
    mysqli_query($conn, $update_sql);

    $success_msg = "Room and its amenities deleted.";
}

// Handle Toggle Status
if (isset($_GET['toggle_status'])) {
    $id = (int)$_GET['toggle_status'];
    $room = mysqli_fetch_assoc(mysqli_query($conn, "SELECT plot_id, status FROM rooms WHERE id = $id"));
    $plot_id = $room['plot_id'];
    $current_status = $room['status'];
    $new_status = ($current_status == 'vacant') ? 'occupied' : 'vacant';

    mysqli_query($conn, "UPDATE rooms SET status = '$new_status' WHERE id = $id");

    $vacant_change = $new_status == 'vacant' ? '+1' : '-1';
    $occupied_change = $new_status == 'vacant' ? '-1' : '+1';
    mysqli_query($conn, "UPDATE plots SET vacant_rooms = vacant_rooms $vacant_change, occupied_rooms = occupied_rooms $occupied_change WHERE plot_id = $plot_id");

    $success_msg = "Room status updated.";
}

// Handle Update Room
if (isset($_POST['update_room'])) {
    $id = $_POST['id'];
    $room_number = $_POST['room_number'];
    $location = $_POST['location'];
    $size = $_POST['size'];
    $price = $_POST['price'];
    $status = $_POST['status'];

    // Handle image
    $image_path = isset($_POST['current_image']) ? $_POST['current_image'] : '';
    if (!empty($_FILES['image']['name'])) {
        $image = $_FILES['image']['name'];
        $image_tmp = $_FILES['image']['tmp_name'];
        $image_path = "images/" . basename($image);
        move_uploaded_file($image_tmp, $image_path);
    }

    // Update room details
    $sql = "UPDATE rooms SET room_number=?, location=?, size=?, price=?, status=?, image=? WHERE id=?";
    $stmt = mysqli_prepare($conn, $sql);
    mysqli_stmt_bind_param($stmt, 'sssdssi', $room_number, $location, $size, $price, $status, $image_path, $id);
    mysqli_stmt_execute($stmt);

    // Update amenities only if amenities field is set
    if (isset($_POST['amenities']) && trim($_POST['amenities']) !== '') {
        // Remove old amenities
        mysqli_query($conn, "DELETE FROM amenities WHERE room_id = $id");

        // Add new ones
        $amenities = array_map('trim', explode(',', $_POST['amenities']));
        foreach ($amenities as $amenity) {
            $amenity = mysqli_real_escape_string($conn, $amenity);
            mysqli_query($conn, "INSERT INTO amenities (room_id, amenity) VALUES ($id, '$amenity')");
        }
    }

    $success_msg = "Room updated successfully.";
}
// Fetch plots and rooms
$plot_result = mysqli_query($conn, "SELECT * FROM plots WHERE landlord_id = $landlord_id ORDER BY plot_name");
$rooms = ($plot_id > 0) ? mysqli_fetch_all(mysqli_query($conn, "SELECT * FROM rooms WHERE plot_id = $plot_id ORDER BY room_number"), MYSQLI_ASSOC) : [];


ob_start();
?>


<!-- HTML -->
<!DOCTYPE html>
<html>


    <style>
    
/* Box/Card Layout */
.form-section, .table-section {
    background-color: white;
    padding: 30px;
    margin: 3px auto;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1300px;
}
 table {
        border-collapse: collapse;
        width: 100%;
        margin-bottom: 30px;
        background: #fff;
        box-shadow: 0 0 8px #ccc;
    }
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
    }

/* Form Inputs */
input[type="text"],
input[type="number"],
input[type="file"],
select,
textarea {
    width: 100%;
    padding: 10px;
    margin: 8px 0 20px;
    border: 1px solid #ccc;
    border-radius: 5px;
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

/* Tables */
table {
    border-collapse: collapse;
    width: 100%;
    margin-top: 10px;
}

th, td {
    border: 1px solid #ccc;
    padding: 12px;
    text-align: center;
}

th {
    background-color: #f2f2f2;
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
    </style>



<!-- Plot Selection -->
<div class="form-section">
    <h1>Manage Rooms</h1>
<?php if (isset($success_msg)) echo "<p class='success'>$success_msg</p>"; ?>
    <form method="POST" action="manage_rooms.php #roomForm">
        

        <label>Select Plot:</label>
        <select name="plot_id" required onchange="this.form.submit()">
            <option value="">-- Choose a Plot --</option>
            <?php while ($plot = mysqli_fetch_assoc($plot_result)) { ?>
                <option value="<?= $plot['plot_id']; ?>" <?= ($plot['plot_id'] == $plot_id) ? 'selected' : ''; ?>>
                    <?= htmlspecialchars($plot['plot_name']) . ' - ' . htmlspecialchars($plot['location']); ?>
                </option>
            <?php } ?>
        </select>
          <div style="margin-top: 20px;">
    <a href="manage_plots.php" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Back to manage plots</a>
</div>

    </form>
</div>

<!-- Add Room Form -->
<?php if ($plot_id > 0 && !isset($_GET['edit_room'])): ?>
<div class="form-section">

    <h3>Add Room</h3>
    <form id="roomForm" method="post" action="manage_rooms.php" enctype="multipart/form-data">
        <input type="hidden" name="plot_id" value="<?= $plot_id; ?>">
        <label>Room Number:</label>
        <input type="text" name="room_number" required>
        <label>Location:</label>
        <input type="text" name="location" required>
        <label>Size:</label>
        <input type="text" name="size" required>
        <label>Price:</label>
        <input type="number" step="0.01" name="price" required>
        <label>Status:</label>
        <select name="status" required>
            <option value="vacant">Vacant</option>
            <option value="occupied">Occupied</option>
        </select>
        <label>Amenities (comma-separated):</label>
<textarea name="amenities" placeholder="e.g. WiFi, Water, Balcony, Parking"></textarea>
        <label>Image:</label>
        <input type="file" name="image" accept="image/*"" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">>
        <button type="submit" name="add_room" " style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">>Add Room</button>
    </form>
</div>
<?php endif; ?>

<!-- Edit Room Form -->
<?php if (isset($_GET['edit_room'])):
    $id = (int)$_GET['edit_room'];
    $room_edit = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM rooms WHERE id = $id"));
?>
<div class="form-section">
    <h3>Edit Room</h3>
    <form method="POST" enctype="multipart/form-data">
        <input type="hidden" name="id" value="<?= $room_edit['id'] ?>">
        <input type="hidden" name="current_image" value="<?= $room_edit['image'] ?>">
        <label>Room Number:</label>
        <input type="text" name="room_number" value="<?= $room_edit['room_number'] ?>" required>
        <label>Location:</label>
        <input type="text" name="location" value="<?= $room_edit['location'] ?>" required>
        <label>Size:</label>
        <input type="text" name="size" value="<?= $room_edit['size'] ?>" required>
        <label>Price:</label>
        <input type="number" step="0.01" name="price" value="<?= $room_edit['price'] ?>" required>
        <label>Status:</label>
        <select name="status" required>
            <option value="vacant" <?= $room_edit['status'] == 'vacant' ? 'selected' : '' ?>>Vacant</option>
            <option value="occupied" <?= $room_edit['status'] == 'occupied' ? 'selected' : '' ?>>Occupied</option>
        </select>
        <label>Amenities (comma-separated):</label>
<textarea name="amenities" placeholder="e.g. WiFi, Water, Balcony, Parking"></textarea>
        <label>Image:</label>
        <input type="file" name="image" accept="image/*">
        <br>
        <img src="<?= $room_edit['image'] ?>" alt="Room Image">
        <br><br>
        <button type="submit" name="update_room">Update Room</button>
        <a href="manage_rooms.php?plot_id=<?= $plot_id ?>">Cancel</a>
        <div style="margin-top: 20px;">
    <a href="manage_rooms.php?plot_id=<?= $plot_id ?>#roomForm" 
   style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">
   ← Back to manage rooms
</a>
</div>
    </form>
</div>

<?php endif; ?>

<!-- Room List -->
<?php if (!empty($rooms)): ?>
<div style="overflow-x:auto;" class="table-section">
    <h3>Rooms Under Selected Plot</h3>
    <table>
        <thead>
            <tr>
                <th>Room No.</th>
                <th>Location</th>
                <th>Size</th>
                <th>Price</th>
                <th>Status</th>
        <th>Amenities</th>
        <th>Image</th>
        <th>Actions</th>
            </tr>
        </thead>
        <tbody>

            <?php foreach ($rooms as $room): ?>
            <tr>
                <td><?= $room['room_number'] ?></td>
                <td><?= $room['location'] ?></td>
                <td><?= $room['size'] ?></td>
                <td>KES <?= number_format($room['price'], 2) ?></td>
                <td><?= ucfirst($room['status']) ?></td>
                <?php
            // Fetch amenities for this room
            $room_id = $room['id'];
            $amenities_result = mysqli_query($conn, "SELECT amenity FROM amenities WHERE room_id = $room_id");
            $amenities = [];
            while ($row = mysqli_fetch_assoc($amenities_result)) {
                $amenities[] = $row['amenity'];
            }
            $amenities_str = implode(', ', $amenities);
            ?>
                <td><?= $amenities_str ?></td>

                <td>
                    <?php if (!empty($room['image'])): ?>
                        <img src="<?= $room['image'] ?>" alt="Room Image">
                    <?php else: ?>No Image<?php endif; ?>

                </td>

                <td class="action-btns">
                    <a href="manage_rooms.php?plot_id=<?= $plot_id ?>&edit_room=<?= $room['id'] ?>">Edit</a>|
                    <a href="manage_rooms.php?plot_id=<?= $plot_id ?>&delete_room=<?= $room['id'] ?>" onclick="return confirm('Delete this room?')">Delete</a>|
                    <a href="manage_rooms.php?plot_id=<?= $plot_id ?>&toggle_status=<?= $room['id'] ?>">Mark as <?= $room['status'] == 'vacant' ? 'Occupied' : 'Vacant' ?></a>
                </td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
    <div style="margin-top: 20px;">
    <a href="manage_plots.php" style="padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Back to manage plots</a>
</div> 
</div>



<?php endif; ?>

<?php
$content = ob_get_clean();
include('layout.php');
?>
