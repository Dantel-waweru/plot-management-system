<?php
include '../../includes/db.php';
$id = $_GET['id'];
mysqli_query($conn, "DELETE FROM tenants WHERE tenant_id='$id'");
header("Location: tenants_list.php");
?>
