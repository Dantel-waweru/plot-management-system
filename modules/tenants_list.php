<?php
include '../../includes/db.php'; // DB connection

$query = "SELECT * FROM tenants";
$result = mysqli_query($conn, $query);
?>

<h2>Tenant List</h2>
<a href="tenant_add.php">+ Add New Tenant</a>
<table border="1">
    <tr>
        <th>ID</th>
        <th>Name</th>
        <th>Phone</th>
        <th>Email</th>
        <th>Room</th>
        <th>Plot ID</th>
        <th>Paid</th>
        <th>Actions</th>
    </tr>

    <?php while ($row = mysqli_fetch_assoc($result)): ?>
    <tr>
        <td><?= $row['tenant_id'] ?></td>
        <td><?= $row['name'] ?></td>
        <td><?= $row['phone'] ?></td>
        <td><?= $row['email'] ?></td>
        <td><?= $row['room_number'] ?></td>
        <td><?= $row['plot_id'] ?></td>
        <td><?= $row['paid_status'] ?></td>
        <td>
            <a href="tenant_edit.php?id=<?= $row['tenant_id'] ?>">Edit</a> |
            <a href="tenant_delete.php?id=<?= $row['tenant_id'] ?>" onclick="return confirm('Delete this tenant?')">Delete</a>
        </td>
    </tr>
    <?php endwhile; ?>
</table>
