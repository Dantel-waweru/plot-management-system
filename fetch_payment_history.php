<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    echo "Unauthorized";
    exit();
}

$landlord_id = $_SESSION['user_id'];
$tenant_id = intval($_GET['tenant_id'] ?? 0);

if ($tenant_id <= 0) {
    echo "Invalid tenant";
    exit();
}

// Verify tenant belongs to landlord
$sql = "SELECT COUNT(*) FROM tenants t
        JOIN rooms r ON t.room_id = r.id
        JOIN plots pl ON r.plot_id = pl.plot_id
        WHERE t.tenant_id = ? AND pl.landlord_id = ?";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "ii", $tenant_id, $landlord_id);
mysqli_stmt_execute($stmt);
mysqli_stmt_bind_result($stmt, $count);
mysqli_stmt_fetch($stmt);
mysqli_stmt_close($stmt);

if ($count == 0) {
    echo "Unauthorized tenant";
    exit();
}

// Fetch payments
$sql = "SELECT * FROM payments WHERE tenant_id = ? ORDER BY payment_date DESC";
$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, "i", $tenant_id);
mysqli_stmt_execute($stmt);
$res = mysqli_stmt_get_result($stmt);

if (mysqli_num_rows($res) == 0) {
    echo "<p>No payments recorded for this tenant.</p>";
} else {
    echo '<table border="1" cellpadding="10" style="width:100%; background:#f0f0f0;">';
    echo '<tr><th>ID</th><th>Amount (KES)</th><th>Method</th><th>Reference</th><th>Date</th></tr>';
    while ($p = mysqli_fetch_assoc($res)) {
        echo '<tr>';
        echo '<td>' . $p['payment_id'] . '</td>';
        echo '<td>' . number_format($p['amount'], 2) . '</td>';
        echo '<td>' . htmlspecialchars($p['payment_method']) . '</td>';
        echo '<td>' . htmlspecialchars($p['reference']) . '</td>';
        echo '<td>' . date('d M Y', strtotime($p['payment_date'])) . '</td>';
        echo '</tr>';
    }
    echo '</table>';
}
?>
