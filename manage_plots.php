<?php
session_start();
include('includes/db.php');

// Fetch all plots

$landlord_id = $_SESSION['user_id'];
$sql = "
    SELECT 
        p.plot_id, p.plot_name, p.location, p.num_rooms, p.price, 
        COUNT(r.id) AS total_rooms,
        SUM(CASE WHEN r.status = 'vacant' THEN 1 ELSE 0 END) AS vacant_rooms,
        SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms
    FROM plots p
    LEFT JOIN rooms r ON p.plot_id = r.plot_id
    WHERE p.landlord_id = $landlord_id
    GROUP BY p.plot_id
    ORDER BY p.created_at DESC
";


$result = mysqli_query($conn, $sql);

if (!$result) {
    die("Error executing query: " . mysqli_error($conn));
}

// Handle deletion
if (isset($_GET['delete_id'])) {
    $plot_id = $_GET['delete_id'];
    $delete_sql = "DELETE FROM plots WHERE plot_id = $plot_id";
    if (mysqli_query($conn, $delete_sql)) {
        header("Location: manage_plots.php?message=Plot Deleted Successfully");
        exit();
    } else {
        echo "Error deleting plot: " . mysqli_error($conn);
    }
}
$edit_mode = false;
$edit_data = [];

if (isset($_GET['edit_id'])) {
    $edit_id = $_GET['edit_id'];
    $edit_mode = true;
    
    $edit_sql = "SELECT * FROM plots WHERE plot_id = $edit_id AND landlord_id = $landlord_id";
    $edit_result = mysqli_query($conn, $edit_sql);
    
    if ($edit_result && mysqli_num_rows($edit_result) > 0) {
        $edit_data = mysqli_fetch_assoc($edit_result);
    } else {
        echo "<p class='success'>Plot not found or unauthorized access.</p>";
        $edit_mode = false;
    }
}

// Handle update
if (isset($_POST['update_plot'])) {
    $plot_id = $_POST['plot_id'];
    $plot_name = $_POST['plot_name'];
    $location = $_POST['location'];
    $num_rooms = $_POST['num_rooms'];
    $vacant_rooms = $_POST['vacant_rooms'];
    $occupied_rooms = $_POST['occupied_rooms'];
    $price = $_POST['price'];
    $benefits = $_POST['benefits'];

    $update_sql = "UPDATE plots SET plot_name='$plot_name', location='$location', num_rooms='$num_rooms',
                    vacant_rooms='$vacant_rooms', occupied_rooms='$occupied_rooms', price='$price', benefits='$benefits'
                    WHERE plot_id=$plot_id AND landlord_id=$landlord_id";

    if (mysqli_query($conn, $update_sql)) {
        header("Location: manage_plots.php?message=Plot Updated Successfully");
        exit();
    } else {
        echo "Error updating plot: " . mysqli_error($conn);
    }
}
// Handle addition
if (isset($_POST['add_plot'])) {
    $plot_name = $_POST['plot_name'];
    $location = $_POST['location'];
    $num_rooms = $_POST['num_rooms'];
    $vacant_rooms = $_POST['vacant_rooms'];
    $occupied_rooms = $_POST['occupied_rooms'];
    $price = $_POST['price'];
    $benefits = $_POST['benefits'];
    $landlord_id = $_SESSION['user_id']; // Assuming user is the landlord

    $insert_sql = "INSERT INTO plots (plot_name, location, num_rooms, vacant_rooms, occupied_rooms, price, benefits, landlord_id)
                   VALUES ('$plot_name', '$location', '$num_rooms', '$vacant_rooms', '$occupied_rooms', '$price', '$benefits', '$landlord_id')";

    if (mysqli_query($conn, $insert_sql)) {
        header("Location: manage_plots.php?message=Plot Added Successfully");
        exit();
    } else {
        echo "Error adding plot: " . mysqli_error($conn);
    }
}

// Define $content to inject into layout
ob_start();
?>


  
<style>
    

   /* Box/Card Layout */
.form-section, .table-section {
    background-color: white;
    padding: 30px;
    margin: 3px auto;
    border-radius: 10px;
    box-shadow: 0 4px 8px rgba(0,0,0,0.15);
    max-width: 1200px;
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

    
</style>
<!-- Add Plot Form -->
<div class="form-section">
    <h1>Manage Plots</h1>
<?php if (isset($_GET['message'])) echo "<p class='success'>{$_GET['message']}</p>"; ?>
   <h3><?php echo $edit_mode ? "Edit Plot" : "Add Plot"; ?></h3>
<form method="POST" action="manage_plots.php">
    <?php if ($edit_mode): ?>
        <input type="hidden" name="plot_id" value="<?php echo $edit_data['plot_id']; ?>">
    <?php endif; ?>

    <label>Plot Name:</label>
    <input type="text" name="plot_name" value="<?php echo $edit_mode ? $edit_data['plot_name'] : ''; ?>" required>
    <label>Location:</label>
    <input type="text" name="location" value="<?php echo $edit_mode ? $edit_data['location'] : ''; ?>" required>
    <label>Number of Rooms:</label>
    <input type="number" name="num_rooms" value="<?php echo $edit_mode ? $edit_data['num_rooms'] : ''; ?>" required>
    <label>Vacant Rooms:</label>
    <input type="number" name="vacant_rooms" value="<?php echo $edit_mode ? $edit_data['vacant_rooms'] : ''; ?>" required>
    <label>Occupied Rooms:</label>
    <input type="number" name="occupied_rooms" value="<?php echo $edit_mode ? $edit_data['occupied_rooms'] : ''; ?>" required>
    <label>Price:</label>
    <input type="number" name="price" value="<?php echo $edit_mode ? $edit_data['price'] : ''; ?>" required>
    <label>Benefits:</label>
    <input type="text" name="benefits" value="<?php echo $edit_mode ? $edit_data['benefits'] : ''; ?>" required>

    <?php if ($edit_mode): ?>
        <button type="submit" name="update_plot">Update Plot</button>
        <a href="manage_plots.php" class="btn" style="background-color: grey;">Cancel</a>
    <?php else: ?>
        <button type="submit" name="add_plot">Add Plot</button>
    <?php endif; ?>
</form>

</div>


<!-- Plot List -->
<div class="table-section">
    <h3>Existing Plots</h3>
    <table>
        <thead>
            <tr>
                <th>Plot Name</th>
                <th>Location</th>
                <th>Rooms</th>
                <th>Vacant</th>
                <th>Occupied</th>
                <th>Price</th>
                <th>Actions</th>
            </tr>
        </thead>
        <tbody>
        <?php while ($row = mysqli_fetch_assoc($result)): ?>
            <tr>
                <td><?php echo $row['plot_name']; ?></td>
                <td><?php echo $row['location']; ?></td>
                <td><?php echo $row['num_rooms']; ?></td>
                <td><?php echo $row['vacant_rooms']; ?></td>
                <td><?php echo $row['occupied_rooms']; ?></td>
                <td><?php echo $row['price']; ?></td>
                <td>
 <a href="manage_rooms.php?plot_id=<?php echo $row['plot_id']; ?>#roomForm">Manage Rooms</a>|
  <a href="manage_plots.php?edit_id=<?php echo $row['plot_id']; ?>">Edit</a> |
    <a href="?delete_id=<?php echo $row['plot_id']; ?>" onclick="return confirm('Are you sure you want to delete this plot?')">Delete</a> 
                </td>
            </tr>
        <?php endwhile; ?>
        </tbody>
    </table>
</div>


<?php
$content = ob_get_clean();
include('layout.php');
?>
