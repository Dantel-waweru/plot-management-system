
require 'includes/db.php';

$today = date('Y-m-d');
$currentMonth = date('Y-m');

$tenants = mysqli_query($conn, "SELECT t.tenant_id, r.price AS rent, t.amount_paid FROM tenants t JOIN rooms r ON t.room_id = r.id");

while ($t = mysqli_fetch_assoc($tenants)) {
    $newRent = $t['rent'];
    $balance = $t['rent'] - $t['amount_paid']; // Underpayment or overpayment

    if ($balance < 0) {
        $credit = abs($balance);
        mysqli_query($conn, "UPDATE tenants SET amount_paid = $credit WHERE tenant_id = {$t['tenant_id']}");
    } else {
        mysqli_query($conn, "UPDATE tenants SET amount_paid = 0 WHERE tenant_id = {$t['tenant_id']}");
    }

    // Optional: archive monthly summary if needed
}
