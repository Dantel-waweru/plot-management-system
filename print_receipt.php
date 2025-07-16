<?php
require_once 'includes/db.php';

if (!isset($_GET['payment_id'])) {
    die("No payment selected.");
}

$payment_id = intval($_GET['payment_id']);

// Get payment + tenant + room info
$query = "
    SELECT 
        p.*, 
        t.name AS tenant_name, 
        t.amount_paid AS total_amount_paid,
        t.created_at AS tenant_start_date,
        r.price AS room_price,
        pl.plot_name, 
        r.room_number, 
        u.name AS landlord_name, 
        u.phone AS landlord_phone
    FROM payments p
    JOIN tenants t ON p.tenant_id = t.tenant_id
    JOIN rooms r ON t.room_id = r.id
    JOIN plots pl ON r.plot_id = pl.plot_id
    JOIN users u ON pl.landlord_id = u.user_id
    WHERE p.payment_id = $payment_id
";

$result = mysqli_query($conn, $query);
$payment = mysqli_fetch_assoc($result);

if (!$payment) {
    die("Payment not found.");
}

$tenant_id = $payment['tenant_id'];
$room_price = floatval($payment['room_price']);
$tenant_start_date = new DateTime($payment['tenant_start_date']);
$current_payment_date = new DateTime($payment['payment_date']);
$current_payment_month = $current_payment_date->format('Y-m');
$current_payment_value = floatval($payment['amount']);

// Step 1: Calculate months stayed inclusive
$months_stayed = (($current_payment_date->format('Y') - $tenant_start_date->format('Y')) * 12) + 
                 ($current_payment_date->format('n') - $tenant_start_date->format('n')) + 1;

// Step 2: Expected rent till last month
$expected_till_last_month = ($months_stayed - 1) * $room_price;

// Step 3: Get total paid before this payment
$total_paid_before = $payment['total_amount_paid'] - $payment['amount'];

// Step 4: Previous balance = Rent due till last month - total paid before
$previous_balance = max(0, $expected_till_last_month - $total_paid_before);

// Step 5: This month's expected rent
$expected_this_month = $room_price;

// Step 6: Get paid this month before current payment
$payment_id = $payment['payment_id'];
$payment_date = $payment['payment_date'];

$paid_this_month_query = "
    SELECT SUM(amount) AS initial_paid_this_month
FROM payments
WHERE tenant_id = $tenant_id 
AND DATE_FORMAT(payment_date, '%Y-%m') = '$current_payment_month'
AND (
    payment_date < '$payment_date' OR
    (payment_date = '$payment_date' AND payment_id < $payment_id)
)
";

$paid_result = mysqli_query($conn, $paid_this_month_query);
$initial_paid_this_month = floatval(mysqli_fetch_assoc($paid_result)['initial_paid_this_month'] ?? 0);

// Step 7: Add current payment to this month’s paid amount


// Step 8: Total paid so far
$total_paid_all_time = $total_paid_before + $current_payment_value;
$total_paid_this_month = $total_paid_all_time - $room_price;
// Step 9: Total expected till now (month inclusive)
$total_expected = $months_stayed * $room_price;

// Step 10: Final balance
$current_balance = $total_expected - $total_paid_all_time;

// Step 11: Total due now = this month's rent + any previous balance
$total_due_now = $expected_this_month + $previous_balance;
$current_payment_month1 = $total_paid_all_time - $room_price  - $current_payment_value;
?>


<!DOCTYPE html>
<html>
<head>
    <title>Payment Receipt</title>
    <style>
        body {
            font-family: 'Courier New', monospace;
            background: #f8f8f8;
            padding: 30px;
        }
        .receipt-box {
            background: #fff;
            border: 1px dashed #000;
            padding: 20px 30px;
            max-width: 400px;
            margin: auto;
            box-shadow: 0 0 10px rgba(0,0,0,0.15);
        }
        .receipt-box h2 {
            text-align: center;
            margin-bottom: 20px;
            text-transform: uppercase;
            border-bottom: 1px dashed #000;
            padding-bottom: 10px;
        }
        .receipt-line {
            margin: 8px 0;
            display: flex;
            justify-content: space-between;
        }
        .footer {
            margin-top: 20px;
            text-align: center;
            font-size: 12px;
            color: #666;
        }
        .print-btn {
            display: block;
            text-align: center;
            margin-top: 30px;
        }
        .print-btn button {
            padding: 8px 20px;
            background: #007bff;
            border: none;
            color: white;
            border-radius: 4px;
            cursor: pointer;
        }
        @media print {
            .print-btn {
                display: none;
            }
        }
    </style>
</head>
<body onload="window.print()">
<div class="receipt-box">
    <h2>Payment Receipt</h2>

    <div class="receipt-line"><strong>Date:</strong> <?= date('Y-m-d H:i', strtotime($payment['payment_date'])) ?></div>
    <div class="receipt-line"><strong>Tenant:</strong> <?= htmlspecialchars($payment['tenant_name']) ?></div>
    <div class="receipt-line"><strong>Plot:</strong> <?= htmlspecialchars($payment['plot_name']) ?></div>
    <div class="receipt-line"><strong>Room:</strong> <?= htmlspecialchars($payment['room_number']) ?></div>
    <div class="receipt-line"><strong>Landlord:</strong> <?= htmlspecialchars($payment['landlord_name']) ?></div>
    <div class="receipt-line"><strong>Phone:</strong> <?= htmlspecialchars($payment['landlord_phone']) ?></div>
    <hr>
    <div class="receipt-line"><strong>Expected Rent this Month:</strong> KES <?= number_format($room_price, 2) ?></div>
    <div class="receipt-line"><strong>Previous month Rent Balance:</strong> KES <?= number_format($previous_balance, 2) ?></div>
    <div class="receipt-line"><strong>Total Expected Rent:</strong> KES <?= number_format($total_due_now, 2) ?></div>
    <div class="receipt-line"><strong>Initial Paid This Month:</strong> KES <?= number_format($current_payment_month1, 2) ?></div>
    <div class="receipt-line"><strong>Now Rent Paid:</strong> KES <?= number_format($current_payment_value, 2) ?></div>
    <div class="receipt-line"><strong>Total Rent Paid:</strong> KES <?= number_format($total_paid_this_month, 2) ?></div>
    <div class="receipt-line"><strong>Balance:</strong> KES <?= number_format($current_balance, 2) ?></div>
    <hr>
    <div class="receipt-line"><strong>Method:</strong> <?= htmlspecialchars($payment['payment_method']) ?></div>
    <div class="receipt-line"><strong>Reference:</strong> <?= htmlspecialchars($payment['reference']) ?></div>
 <div class="receipt-line"><strong>Total expected:</strong> KES <?= number_format($total_expected , 2) ?></div>
  <div class="receipt-line"><strong>Total altime:</strong> KES <?= number_format($total_paid_all_time , 2) ?></div>
  <div class="receipt-line"><strong>Total befor:</strong> KES <?= number_format($total_paid_before , 2) ?></div>
    <div class="footer">Thank you for your payment</div>
</div>

<div class="print-btn">
    <form>
        <button type="button" onclick="window.print()">🖨️ Reprint Receipt</button> 
        <button type="button" onclick="window.location.href='payments.php'">⬅️ Back to payments.</button>
        <button type="button" onclick="window.history.back()">⬅️ Back</button>
    </form>
    
</div>
</div>
</body>
</html>
