<?php
include '../../includes/db.php';
$id = $_GET['id'];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    $name = $_POST['name'];
    $phone = $_POST['phone'];
    $email = $_POST['email'];
    $room_number = $_POST['room_number'];
    $paid_status = $_POST['paid_status'];

    $sql = "UPDATE tenants SET name='$name', phone='$phone', email='$email', room_number='$room_number', paid_status='$paid_status' WHERE tenant_id='$id'";
    mysqli_query($conn, $sql);
    header("Location: tenants_list.php");
}

$result = mysqli_query($conn, "SELECT * FROM tenants WHERE tenant_id='$id'");
$row = mysqli_fetch_assoc($result);
?>

<h2>Edit Tenant</h2>
<form method="POST">
    Name: <input type="text" name="name" value="<?= $row['name'] ?>"><br>
    Phone: <input type="text" name="phone" value="<?= $row['phone'] ?>"><br>
    Email: <input type="email" name="email" value="<?= $row['email'] ?>"><br>
    Room Number: <input type="text" name="room_number" value="<?= $row['room_number'] ?>"><br>
    Paid Status: 
    <select name="paid_status">
        <option value="Paid" <?= $row['paid_status'] == 'Paid' ? 'selected' : '' ?>>Paid</option>
        <option value="Unpaid" <?= $row['paid_status'] == 'Unpaid' ? 'selected' : '' ?>>Unpaid</option>
    </select><br>
    <button type="submit">Update</button>
</form>
