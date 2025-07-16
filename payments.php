<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$landlord_id = $_SESSION['user_id'];

// === HANDLE SEARCH FILTER ===


$search = '';
$whereFilter = "pl.landlord_id = ?";
$params = [$landlord_id];
$paramTypes = "i";

if (!empty($_GET['search'])) {
    $search = trim($_GET['search']);
    $whereFilter = "(t.name LIKE ? OR r.room_number LIKE ? OR pl.plot_name LIKE ?) AND pl.landlord_id = ?";
    $params = ["%$search%", "%$search%", "%$search%", $landlord_id];
    $paramTypes = "sssi";
}

// === HANDLE ADD/EDIT PAYMENT POST ===
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tenant_id'])) {
    $tenant_id = $_POST['tenant_id'];
    $amount = floatval($_POST['amount']);
    $method = mysqli_real_escape_string($conn, $_POST['method']);
    $reference = mysqli_real_escape_string($conn, $_POST['reference']);
    $payment_id = $_POST['payment_id'] ?? null;

    if ($payment_id) {
        $old = mysqli_fetch_assoc(mysqli_query($conn, "SELECT amount FROM payments WHERE payment_id = $payment_id"));
        $diff = $amount - $old['amount'];
        mysqli_query($conn, "UPDATE payments SET amount = $amount, payment_method = '$method', reference = '$reference' WHERE payment_id = $payment_id");
        mysqli_query($conn, "UPDATE tenants SET amount_paid = amount_paid + $diff WHERE tenant_id = $tenant_id");
    } else {
        mysqli_query($conn, "INSERT INTO payments (tenant_id, amount, payment_method, reference, payment_date) 
                             VALUES ($tenant_id, $amount, '$method', '$reference', NOW())");
        mysqli_query($conn, "UPDATE tenants SET amount_paid = amount_paid + $amount WHERE tenant_id = $tenant_id");
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Handle Delete Payment
if (isset($_GET['delete_payment']) && isset($_GET['tenant_id'])) {
    $payment_id = intval($_GET['delete_payment']);
    $tenant_id = intval($_GET['tenant_id']);
    $payment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT amount FROM payments WHERE payment_id = $payment_id"));

    if ($payment) {
        mysqli_query($conn, "DELETE FROM payments WHERE payment_id = $payment_id");
        mysqli_query($conn, "UPDATE tenants SET amount_paid = amount_paid - {$payment['amount']} WHERE tenant_id = $tenant_id");
    }

    header("Location: " . strtok($_SERVER["REQUEST_URI"], '?'));
    exit();
}

// Fetch Tenants with Aggregates
$sql = "SELECT 
            t.tenant_id,
            t.name AS tenant_name,
            pl.plot_name,
            r.room_number,
            r.price AS room_rent,
            t.amount_paid,
            COALESCE(MIN(p.payment_date), t.created_at, NOW() - INTERVAL 1 MONTH) AS first_payment_date
        FROM tenants t
        JOIN rooms r ON t.room_id = r.id
        JOIN plots pl ON r.plot_id = pl.plot_id
        LEFT JOIN payments p ON t.tenant_id = p.tenant_id
        WHERE $whereFilter
        GROUP BY t.tenant_id, t.name, pl.plot_name, r.room_number, r.price, t.amount_paid, t.created_at
        ORDER BY first_payment_date DESC";

$stmt = mysqli_prepare($conn, $sql);
mysqli_stmt_bind_param($stmt, $paramTypes, ...$params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);

// Output HTML
$editTenant = null;
$editingPayment = null;
if (isset($_GET['tenant_id'])) {
    $tenant_id = intval($_GET['tenant_id']);
    $editTenant = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM tenants WHERE tenant_id = $tenant_id"));
    if (isset($_GET['edit_payment'])) {
        $editingPayment = mysqli_fetch_assoc(mysqli_query($conn, "SELECT * FROM payments WHERE payment_id = " . intval($_GET['edit_payment'])));
    }
}
ob_start();
?>

<style>
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
    form.search-form {
        max-width: 600px;
        margin: 10px auto 30px auto;
        text-align: center;
    }
    form.search-form input[type="text"] {
        width: 70%;
        padding: 8px;
        font-size: 16px;
    }
    form.search-form button {
        padding: 8px 15px;
        font-size: 16px;
        cursor: pointer;
    }
    /* Modal styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 1000;
        left: 0; top: 0;
        width: 100%; height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
        background-color: navajowhite;
        margin: 10% auto;
        padding: 20px;
        border: 1px solid #888;
        width: 350px;
        box-shadow: 0 0 15px #333;
        position: relative;
    }
    .close {
        color: #aaa;
        float: right;
        font-size: 28px;
        font-weight: bold;
        position: absolute;
        right: 10px;
        top: 5px;
        cursor: pointer;
    }
    .close:hover, .close:focus {
        color: black;
    }
    form label {
        display: block;
        margin: 10px 0 5px 0;
        font-weight: bold;
    }
    form input, form select {
        width: 100%;
        padding: 7px;
        box-sizing: border-box;
    }
    form button {
        margin-top: 15px;
        width: 100%;
        padding: 10px;
        background-color: #007bff;
        border: none;
        color: white;
        cursor: pointer;
        font-size: 16px;
    }
    form button:hover {
        background-color: #0056b3;
    }
    /* Balance colors */
    .balance-negative { color: red; }
    .balance-positive { color: green; }
    /* Payment History Table */
    .payment-history th, .payment-history td {
        border: 1px solid #ccc;
    }
    .modal {
  display: none;
  position: fixed;
  z-index: 1000;
  padding-top: 60px;
  left: 0;
  top: 0;
  width: 100%;
  height: 100%;
  overflow: auto;
  background-color: rgba(0, 0, 0, 0.5);
}

.modal-content {
  background-color: #fff;
  margin: auto;
  padding: 30px;
  border: 1px solid #888;
  width: 80%;
  max-width: 500px;
  border-radius: 10px;
  position: relative;
}

.close {
  position: absolute;
  right: 20px;
  top: 15px;
  color: #aaa;
  font-size: 28px;
  font-weight: bold;
  cursor: pointer;
}

.close:hover {
  color: #000;
}

button {
  padding: 10px 20px;
  background-color: #4CAF50;
  border: none;
  color: white;
  border-radius: 5px;
  cursor: pointer;
}
    th, td {
        padding: 10px;
        text-align: center;
    }
    th {
        background-color: antiquewhite;
    }

    form {
        background: navajowhite;
        border: 1px solid #ddd;
        padding: 20px;
        max-width: 500px;
        margin: 20px auto;
        box-shadow: 0 0 8px #ccc;
    }
    form input, form select {
        width: 100%;
        padding: 8px;
        margin-bottom: 15px;
    }
    form button {
        padding: 10px 20px;
        background-color: #007bff;
        border: none;
        color: white;
        cursor: pointer;
    }
    form h3 {
        text-align: center;
    }
</style>

<h2>Tenant Rent Balances</h2>

<form class="search-form" method="GET" action="">
    <input type="text" name="search" placeholder="Search by Tenant, Room, or Plot" value="<?= htmlspecialchars($search) ?>">
    <button type="submit">Search</button>
    <?php if ($search !== ''): ?>
        <button type="button" onclick="window.location='<?= strtok($_SERVER['REQUEST_URI'], '?') ?>'">Clear</button>
    <?php endif; ?>
</form>
<table border="1" width="100%" cellpadding="10">

<h2 style="text-align:center;">💰 Tenant Payments</h2>
<thead>
  

    <tr>
        <th>#</th>
        <th>Tenant</th>
        <th>Plot</th>
        <th>Room</th>
        <th>Monthly Rent</th>
        <th>Months Elapsed</th>
        <th>Total Expected</th>
        <th>Total Paid</th>
        <th>Paid This Month</th>
        <th>Current Balance</th>
        <th>Actions</th>
    </tr>
    </thead>
<tbody>
<?php if (mysqli_num_rows($result) > 0): $i = 1; ?>
    <?php while ($row = $result->fetch_assoc()): 

        $tenant_id = $row['tenant_id'];
        $tenant_name = $row['tenant_name'];
        $plot_name = $row['plot_name'];
        $room_number = $row['room_number'];
        $monthly_rent = floatval($row['room_rent']);
        $amount_paid = floatval($row['amount_paid']);
       
        // Determine first payment date or default to 1 month ago
        $first_payment_date_str = $row['first_payment_date'] ?? null;
        $start_date = $first_payment_date_str ? new DateTime($first_payment_date_str) : (new DateTime())->modify('-1 month');
        $now = new DateTime();

        // Calculate number of 30-day periods elapsed
        $interval = $start_date->diff($now);
       $start_date = $first_payment_date_str ? new DateTime($first_payment_date_str) : (new DateTime())->modify('-1 month');
$now = new DateTime();

// Add 1 to include the current partial month
$months_elapsed = ($start_date->diff($now)->m + 1) + ($start_date->diff($now)->y * 12);

        $expected_total = $monthly_rent * $months_elapsed;
        $balance = $expected_total - $amount_paid;
        $paid_this_month = $amount_paid - ($expected_total- $monthly_rent);
    ?>

    <tr>
        <td><?= $i++ ?></td>
        <td><?= htmlspecialchars($tenant_name) ?></td>
        <td><?= htmlspecialchars($plot_name) ?></td>
        <td><?= htmlspecialchars($room_number) ?></td>
        <td>Ksh <?= number_format($monthly_rent, 2) ?></td>
        <td><?= $months_elapsed ?></td>
        <td>Ksh <?= number_format($expected_total, 2) ?></td>
        <td>Ksh <?= number_format($amount_paid, 2) ?></td>
        <td>Ksh <?= number_format($paid_this_month, 2) ?></td>
        <td style="color:<?= $balance > 0 ? 'red' : 'green' ?>; font-weight:bold;">
            Ksh <?= number_format($balance, 2) ?>
        </td>
        <td>
            <a href="#" style="color:blue;" onclick="openModal(<?= $tenant_id ?>); return false;">➕ Add payments</a> |
            <button type="button" onclick="toggleHistory(<?= $tenant_id ?>)">📜 History</button>
        </td>
    </tr>

    <!-- Hidden Payment History Row for this tenant -->
    <?php
        $pResult = mysqli_query($conn, "SELECT * FROM payments WHERE tenant_id = {$tenant_id} ORDER BY payment_date DESC");
        if (mysqli_num_rows($pResult) > 0):
    ?>
    <tr id="payment-history-<?= $tenant_id ?>" style="display:none;">
        <td colspan="10">
            <strong>🧾 Payment History for <?= htmlspecialchars($tenant_name) ?>:</strong>
            <table width="100%" border="1" cellpadding="5">
                <tr><th>Amount</th><th>Method</th><th>Ref</th><th>Date</th><th>Actions</th></tr>
                <?php while ($p = mysqli_fetch_assoc($pResult)): ?>
                    <tr>
                        <td><?= number_format($p['amount'], 2) ?></td>
                        <td><?= htmlspecialchars($p['payment_method']) ?></td>
                        <td><?= htmlspecialchars($p['reference']) ?></td>
                        <td><?= htmlspecialchars($p['payment_date']) ?></td>
                        <td>
                            <a href="?tenant_id=<?= $tenant_id ?>&edit_payment=<?= $p['payment_id'] ?>" style="color:orange;">✏️ Edit</a> |
                            <a href="?tenant_id=<?= $tenant_id ?>&delete_payment=<?= $p['payment_id'] ?>" onclick="return confirm('Are you sure you want to delete this payment?')" style="color:red;">🗑 Delete</a> |
                            <a href="print_receipt.php?payment_id=<?= $p['payment_id'] ?>" target="_blank" style="color:green;">🖨 Print</a>
                        </td>
                    </tr>
                <?php endwhile; ?>
            </table>
        </td>
    </tr>
    <?php endif; ?>

<?php endwhile; ?>
<?php else: ?>
    <tr>
        <td colspan="10" style="text-align:center;">No records found.</td>
    </tr>
<?php endif; ?>
</tbody>

  

</table>

<script>
function toggleHistory(tenant_id) {
    const historyRow = document.getElementById('payment-history-' + tenant_id);
    if (!historyRow) return;

    if (historyRow.style.display === 'none') {
        historyRow.style.display = 'table-row';
    } else {
        historyRow.style.display = 'none';
    }
}
</script>


<!-- Modal -->
<div id="paymentModal" class="modal">
    <div class="modal-content">
        <span class="close" title="Close Modal">&times;</span>
        <h3 id="modalTitle">Add Payment</h3>

      <form method="POST" id="paymentForm" action="">
   <h3>
    <?= $editingPayment ? '✏️ Edit Payment' : '➕ Add Payment' ?>
    <?php
        $tenantName = null;
        if (isset($editTenant['name'])) {
            $tenantName = $editTenant['name'];
        } elseif (isset($tenant['name'])) {
            $tenantName = $tenant['name'];
        }
    ?>
    <?php if ($tenantName): ?>
        for <?= htmlspecialchars($tenantName) ?>
    <?php endif; ?>
</h3>
    <input type="hidden" name="tenant_id" value="<?= !empty($editTenant['tenant_id']) ? $editTenant['tenant_id'] : '' ?>">
    <?php if ($editingPayment): ?>
        <input type="hidden" name="payment_id" value="<?= $editingPayment['payment_id'] ?>">
    <?php endif; ?>
    <label>Amount: <input type="number" step="0.01" name="amount" required value="<?= $editingPayment['amount'] ?? '' ?>"></label><br>
    <label>Method:
        <select name="method" required>
            <option value="Cash" <?= (isset($editingPayment) && $editingPayment['payment_method'] === 'Cash') ? 'selected' : '' ?>>Cash</option>
            <option value="Mpesa" <?= (isset($editingPayment) && $editingPayment['payment_method'] === 'Mpesa') ? 'selected' : '' ?>>Mpesa</option>
            <option value="Bank" <?= (isset($editingPayment) && $editingPayment['payment_method'] === 'Bank') ? 'selected' : '' ?>>Bank</option>
        </select>
    </label><br>
    <label>Reference: <input type="text" name="reference" required value="<?= $editingPayment['reference'] ?? '' ?>"></label><br>
    <button type="submit"><?= $editingPayment ? 'Update Payment' : 'Add Payment' ?></button>
</form>

    </div>
</div>

<script>
    const modal = document.getElementById("paymentModal");
    const closeBtn = modal.querySelector(".close");
    const form = document.getElementById("paymentForm");
    const modalTitle = document.getElementById("modalTitle");

    closeBtn.onclick = () => {
        modal.style.display = "none";
        clearForm();
        history.replaceState(null, '', location.pathname);
    };

    window.onclick = (event) => {
        if (event.target === modal) {
            modal.style.display = "none";
            clearForm();
            history.replaceState(null, '', location.pathname);
        }
    };

    function clearForm() {
        form.reset();
        if (form.querySelector('input[name="payment_id"]')) {
            form.querySelector('input[name="payment_id"]').value = '';
        }
        form.querySelector('input[name="tenant_id"]').value = '';
        modalTitle.textContent = 'Add Payment';
    }

    function openModal(tenant_id) {
        modal.style.display = "block";
        form.querySelector('input[name="tenant_id"]').value = tenant_id ;
        if (form.querySelector('input[name="payment_id"]')) {
            form.querySelector('input[name="payment_id"]').value = '';
        }
        modalTitle.textContent = 'Add Payment';
        form.amount.focus();
    }

    <?php if (!empty($editTenant) && !empty($editingPayment)): ?>
        modal.style.display = "block";
        form.querySelector('input[name="tenant_id"]').value = <?= json_encode($editTenant['tenant_id']) ?>;
        form.querySelector('input[name="payment_id"]').value = <?= json_encode($editingPayment['payment_id']) ?>;
        form.amount.value = <?= json_encode($editingPayment['amount']) ?>;
        form.method.value = <?= json_encode($editingPayment['payment_method']) ?>;
        form.reference.value = <?= json_encode($editingPayment['reference']) ?>;
        modalTitle.textContent = 'Edit Payment';
    <?php endif; ?>
</script>



<?php
$content = ob_get_clean();
include 'layout.php';
?>