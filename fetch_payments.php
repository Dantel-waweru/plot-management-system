<?php
// fetch_payments.php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
  echo json_encode(['success' => false]);
  exit;
}

$landlord_id = $_SESSION['user_id'];

if (!isset($_GET['tenant_id'])) {
  echo json_encode(['success' => false]);
  exit;
}

$tenant_id = intval($_GET['tenant_id']);

// Verify tenant belongs to landlord
$res = mysqli_query($conn, "SELECT name FROM tenants t JOIN rooms r ON t.room_id = r.id JOIN plots pl ON r.plot_id = pl.plot_id WHERE t.tenant_id = $tenant_id AND pl.landlord_id = $landlord_id");
if (mysqli_num_rows($res) == 0) {
  echo json_encode(['success' => false]);
  exit;
}
$tenant = mysqli_fetch_assoc($res);

// Fetch payments
$paymentsRes = mysqli_query($conn, "SELECT payment_id, amount, payment_method, reference, DATE_FORMAT(payment_date, '%Y-%m-%d') AS payment_date FROM payments WHERE tenant_id = $tenant_id ORDER BY payment_date DESC");

$payments = [];
while ($row = mysqli_fetch_assoc($paymentsRes)) {
  $payments[] = $row;
}

echo json_encode(['success' => true, 'tenant_name' => $tenant['name'], 'payments' => $payments]);
