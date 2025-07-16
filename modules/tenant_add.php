<?php
require_once '../includes/db.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $landlord_id = $_POST['landlord_id'];
    $plot_id = $_POST['plot_id'];
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $room_number = $_POST['room_number'];
    $paid_status = $_POST['paid_status'];

    $sql = "INSERT INTO tenants (landlord_id, plot_id, name, phone, email, room_number, paid_status) 
            VALUES ('$landlord_id', '$plot_id', '$name', '$phone', '$email', '$room_number', '$paid_status')";

    if (mysqli_query($conn, $sql)) {
        header("Location: ../manage_tenants.php?success=1");
        exit();
    } else {
        die("Insert Failed: " . mysqli_error($conn));
    }
}
?>

<!DOCTYPE html>
<html>
<head>
    <title>Add Tenant</title>
    <link rel="stylesheet" href="../style.css">
</head>
<body>
    <h2>Add New Tenant</h2>
    <form method="POST" action="">
        <label>Landlord ID:</label><br>
        <input type="text" name="landlord_id" required><br><br>

        <label>Plot ID:</label><br>
        <input type="text" name="plot_id" required><br><br>

        <label>Name:</label><br>
        <input type="text" name="name" required><br><br>

        <label>Phone:</label><br>
        <input type="text" name="phone" required><br><br>

        <label>Email:</label><br>
        <input type="email" name="email" required><br><br>

        <label>Room Number:</label><br>
        <input type="text" name="room_number" required><br><br>

        <label>Paid Status:</label><br>
        <select name="paid_status" required>
            <option value="Paid">Paid</option>
            <option value="Unpaid">Unpaid</option>
        </select><br><br>

        <input type="submit" value="Add Tenant">
        <a href="../manage_tenants.php">Cancel</a>
    </form>
</body>
</html>
