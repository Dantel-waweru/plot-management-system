<?php
session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}
require_once 'includes/db.php'; // Make sure this path is correct

$landlord_id = $_SESSION['user_id'];
// Get total plots count
$sqlPlots = "SELECT COUNT(*) AS total_plots FROM plots WHERE landlord_id = ?";
$stmtPlots = $conn->prepare($sqlPlots);
$stmtPlots->bind_param("i", $landlord_id);
$stmtPlots->execute();
$resultPlots = $stmtPlots->get_result();
$totalPlots = 0;
if ($row = $resultPlots->fetch_assoc()) {
    $totalPlots = $row['total_plots'];
}
$sqlRooms = "SELECT COUNT(*) AS total_rooms FROM rooms WHERE landlord_id = ?";
$stmtRooms = $conn->prepare($sqlRooms);
$stmtRooms->bind_param("i", $landlord_id);
$stmtRooms->execute();
$resultRooms = $stmtRooms->get_result();
$totalRooms = 0;
if ($row = $resultRooms->fetch_assoc()) {
    $totalRooms = $row['total_rooms'];
}

// Get total tenants count
$sqlTenants = "SELECT COUNT(*) AS total_tenants FROM rooms WHERE landlord_id = ? AND status = 'occupied'";
$stmtTenants = $conn->prepare($sqlTenants);
$stmtTenants->bind_param("i", $landlord_id);
$stmtTenants->execute();
$resultTenants = $stmtTenants->get_result();
$totalTenants = 0;
if ($row = $resultTenants->fetch_assoc()) {
    $totalTenants = $row['total_tenants'];
}

// Get total payments sum
$sqlPayments = "SELECT IFNULL(SUM(price),0) AS expected_payments FROM rooms WHERE landlord_id = ? AND status = 'occupied'";
$stmtPayments = $conn->prepare($sqlPayments);
$stmtPayments->bind_param("i", $landlord_id);
$stmtPayments->execute();
$resultPayments = $stmtPayments->get_result();
$expectedPayments = 0;
if ($row = $resultPayments->fetch_assoc()) {
    $expectedPayments = $row['expected_payments'];
        }
// Get current month and year
$currentMonth = date('m');
$currentYear = date('Y');


// Fetch total paid this month from payments table
// Get total payments made this month based on rent per room
// Calculate total rent paid this month based on logic
$sqlTenantsRent = "SELECT t.tenant_id, t.created_at, r.price AS rent_per_month 
                   FROM tenants t 
                   JOIN rooms r ON t.room_id = r.id 
                   WHERE t.landlord_id = ? AND r.status = 'occupied'";
$stmtTenantsRent = $conn->prepare($sqlTenantsRent);
$stmtTenantsRent->bind_param("i", $landlord_id);
$stmtTenantsRent->execute();
$resultTenantsRent = $stmtTenantsRent->get_result();

$actualRentPaidThisMonth = 0;

while ($tenant = $resultTenantsRent->fetch_assoc()) {
    $tenant_id = $tenant['tenant_id'];
    $rent_per_month = $tenant['rent_per_month'];
    $start_date = strtotime($tenant['created_at']);
    $current_month_start = strtotime(date('Y-m-01'));

    $months_elapsed = date('n', $current_month_start) - date('n', $start_date) + 1 + 
                     (date('Y', $current_month_start) - date('Y', $start_date)) * 12;

    $total_rent_due = $rent_per_month * $months_elapsed;

    // Fetch payments for this tenant
    $sqlPayments = "SELECT amount, payment_date FROM payments WHERE tenant_id = ?";
    $stmtPay = $conn->prepare($sqlPayments);
    $stmtPay->bind_param("i", $tenant_id);
    $stmtPay->execute();
    $resPay = $stmtPay->get_result();

    $payments_before_this_month = 0;
    $payments_this_month = 0;
    $current_year_month = date('Y-m-01');

    while ($payment = $resPay->fetch_assoc()) {
        $payment_date = $payment['payment_date'];
        $amount = $payment['amount'];

        if (strtotime($payment_date) < strtotime($current_year_month)) {
            $payments_before_this_month += $amount;
        } elseif (date('Y-m', strtotime($payment_date)) === date('Y-m')) {
            $payments_this_month += $amount;
        }
    }

    $unpaid_previous_balance = $total_rent_due - $rent_per_month - $payments_before_this_month;
    $rent_paid_this_month = max(0, $payments_this_month - $unpaid_previous_balance);
    $rent_paid_this_month = min($rent_paid_this_month, $rent_per_month);

    $actualRentPaidThisMonth += $rent_paid_this_month;
}


$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'guest';
$notif_count = 0;
$user_id = $_SESSION['user_id']; // current landlord

$sql = "SELECT COUNT(*) AS count FROM notifications 
        WHERE status = 'unread' AND user_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

if ($row = $result->fetch_assoc()) {
    $notif_count = $row['count'];
}
?>
<!DOCTYPE html>
<html lang="en">
<link rel="icon" type="image/jpeg" href="images/icon.jpg">
<head>

  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <!-- Your other <head> content -->

    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plots Management Dashboard</title>
    <link rel="stylesheet" href="style.css">
    
</head>
<body>
    <!-- Sidebar Menu -->
    <div class="sidebar"style=" >
    <div class="sidebar-header">
        <div class="sidebar-header">
            <h2>Plots Manager</h2>
            <p class="role-badge"><b><?php echo ucfirst($user_role); ?></b></p>
        </div>
        <ul class="sidebar-menu">
         <li><a href="dashboard.php" class="<?php echo $current_page === 'dashboard.php' ? 'active' : ''; ?>">🏠 Dashboard</a></li>

            <?php if ($user_role === 'landlord' || $user_role === 'admin'): ?>
        <li><a href="manage_plots.php" class="<?= $current_page === 'manage_plots.php' ? 'active' : ''; ?>">📋 Manage Plots</a></li>
        <li><a href="manage_rooms.php" class="<?= $current_page === 'manage_rooms.php' ? 'active' : ''; ?>">🛏️ Manage Rooms</a></li>
        <li><a href="manage_tenants2.php" class="<?= $current_page === 'manage_tenants2.php' ? 'active' : ''; ?>">👥 Manage Tenants</a></li>
    <?php endif; ?>

    <li><a href="payments.php" class="<?= $current_page === 'payments.php' ? 'active' : ''; ?>">💰 Payments</a></li>
    <li><a href="bookings.php" class="<?= $current_page === 'bookings.php' ? 'active' : ''; ?>">📅 Bookings</a></li>
    <li><a href="profile.php" class="<?= $current_page === 'profile.php' ? 'active' : ''; ?>">👤 Profile</a></li>
    <li><a href="messages.php" class="<?= $current_page === 'messages.php' ? 'active' : ''; ?>">✉️ Messages</a></li>
    <li><a href="settings.php" class="<?= $current_page === 'settings.php' ? 'active' : ''; ?>">⚙️ Settings</a></li>
    <li><a href="logout.php" onclick="return confirm('Are you sure you want to logout?')">🚪 Logout</a></li>
    </ul>
    </div>

    <!-- Main Content -->
    <div class="main-content">
        <!-- Taskbar -->
        <div class="taskbar">
    <div class="taskbar-left">
        <button class="toggle-btn" onclick="toggleSidebar()">☰</button> <!-- Button to toggle sidebar -->
        <span><h2>Welcome, <strong><?php echo ucfirst($user_name); ?></strong></h2></span>
    </div>

    <div class="taskbar-right">
        <a href="tax_breakdown.php" class="taskbar-btn" 
   style="
     display: inline-block;
     background: #2d89ef; 
     color: white; 
     border-radius: 5px; 
     padding: 10px 20px; 
     text-decoration: none; 
     font-weight: bold; 
     cursor: pointer;
     user-select: none;
     transition: background-color 0.3s ease;
   "
   onmouseover="this.style.background='#1b5dab';"
   onmouseout="this.style.background='#2d89ef';"
>
    💰 Tax Breakdown
</a>
        <button class="taskbar-btn" id="notif-btn">
            🔔 Notifications 
            <span id="notif-count" style="background:red;color:white;padding:4px 9px;border-radius:20px;">
                <?php echo $notif_count; ?>
            </span>
        </button>
        <button class="taskbar-btn">✉️ Messages</button>
        <a href="logout.php" class="taskbar-btn logout" onclick="return confirm('Logout?')">🚪 Logout</a>
    </div>
</div>

        <!-- Dashboard Content -->
        <div  class="dashboard-content">
       <div style="text-align:center;" class="stats">
 
    <div class="stat-card">
    <h1>Total Plots</h1>
    <p class="count" data-target="<?php echo $totalPlots; ?>">0</p>
  </div>
    <div class="stat-card">
    <h2>Total Rooms</h2>
    <p class="count" data-target="<?php echo $totalRooms; ?>">0</p>
  </div>
     <div class="stat-card">
    <h2>Total Tenants</h2>
    <p class="count" data-target="<?php echo $totalTenants; ?>">0</p>
  </div>
    <div class="stat-card">
        <h3>Expected Rent (This Month)</h3>
      <p class="count" data-target="<?php echo $expectedPayments; ?>">KES 0.00</p>
  </div>
          <div class="stat-card">
            <h3>Actual Rent Paid (This Month)</h3>
            <p class="count" data-target="<?php echo $actualRentPaidThisMonth; ?>" >KES 0.00</p>
        </div>

        <!-- Updated Coverage with count style -->
        <div class="stat-card">
            <h3>Coverage</h3>
            <p> <?php 
                $coverage = ($expectedPayments > 0) 
                    ? round(($actualRentPaidThisMonth / $expectedPayments) * 100, 2) 
                    : 0;
            ?></p>
            <p class="count" data-target="<?php  echo $coverage ; ?>">

            0 %</p>
    </div>
    <!-- KRA Tax Calculation Card -->
<div class="stat-card">
    <h3>Estimated KRA Tax (This Month)</h3>
    <?php
        $kraTax = $expectedPayments * 0.075;
    ?>
    <p class="count" data-target="<?php echo $kraTax; ?>">KES 0.00</p>
</div>
</div>

<!-- Notification Modal -->



     <div  class="recent-activities">
    <h2 style="text-align:center;"> RECENT ACTIVITIES AND NOTIFICATIONS!</h2>
   <h4> <ul id="recentActivitiesList">
       <!-- Recent notifications will be inserted here -->
    </ul></h4>
</div>
        </div>
    </div>
    

<div id="notifModal" class="modal" style="display:none; position: fixed; top: 0; left: 0; width: 100%; height: 100%; 
     background: rgba(0,0,0,0.5); justify-content: center; align-items: center; z-index: 9999;">

    <div class="modal-content" style="background: #fff; width: 400px; max-height: 80vh; padding: 20px; border-radius: 10px; overflow: hidden;">
        <span class="close" onclick="document.getElementById('notifModal').style.display='none'" style="float: right; cursor: pointer;">&times;</span>
        <h2>New Notification</h2>

        <!-- Scrollable content -->
        <div id="notifList" style="max-height: 60vh; overflow-y: auto; margin-top: 10px; border-top: 1px solid #ccc; padding-top: 10px;"></div>
    </div>
</div>

<style>
/* Base styles for mobile first */
body {
  font-family: Arial, sans-serif;
  padding: 10px;
  margin: 0;
}

.form-section {
  width: 100%;
  box-sizing: border-box;
  margin-bottom: 30px;
  padding: 15px;
  background-color: #f8f8f8;
  border-radius: 10px;
}
body {
  display: flex;
  margin: 0;
  font-family: Arial, sans-serif;
  height: 100vh;
  overflow: hidden;
}

.sidebar {
  width: 250px;
  background-color: #333;
  color: #fff;
  transition: width 0.3s ease;
}

.sidebar.collapsed {
  width: 0;
  overflow: hidden;
}

.main-content {
  flex-grow: 1;
  transition: margin-left 0.001s ease;
  margin-left: 250px; /* same as .sidebar width */
  padding: 20px;
  overflow-y: auto;
}

.sidebar.collapsed + .main-content {
  margin-left: 0;
}


form input[type="text"],
form input[type="number"],
form textarea,
form select,
form input[type="file"] {
  width: 100%;
  padding: 10px;
  margin-top: 5px;
  margin-bottom: 15px;
  box-sizing: border-box;
}

form button {
  width: 100%;
  padding: 12px;
  background-color: #007bff;
  color: white;
  border: none;
  border-radius: 6px;
  cursor: pointer;
}

/* Desktop screens */
@media (min-width: 768px) {
  .form-section {
    width: 60%;
    margin: auto;
  }

  form button {
    width: auto;
  }
}

    .sidebar {
    width: 250px;
    background-color: #333;
    color: white;
    padding: 20px;
    transition: all 0.3s ease;
}

.sidebar a,
.sidebar span {
    display: block;
    color: white;
    padding: 10px 0;
    text-decoration: none;
}

.sidebar a:hover,
.sidebar span:hover {
    background-color: #444;
}

.sidebar.collapsed {
    width: 0;
    padding: 0;
    overflow: hidden;
}

.main-content {
    flex: 1;
    padding: 20px;
    transition: all 0.3s ease;
}

.main-content.expanded {
    margin-left: 0.00;
}

.toggle-btn {
    position: fixed;
    top: 10px;
    left: 10px;
    z-index: 999;
    background-color: blueviolet;
    color: white;
    border: none;
    padding: 10px 15px;
    cursor: pointer;
}
.stat-card p {
  font-size: 3em;
  font-weight: 900;
  opacity: 0.1; /* Start hidden */
  transition: opacity 2.5s ease, color 20s ease;
  color: #333;
}

/* Make it visible after page loads */
.stat-card p.visible {
  opacity: 1;
}

/* Pulse and color transition */
@keyframes pulseFade {
  0% {
    opacity: 1;
    color: #333;
  }
  50% {
    opacity: 0.7; /* Not too low, still readable */
    color: #007BFF;
  }
  100% {
    opacity: 1;
    color: #28A745;
  }
}

/* Apply the animation loop */
.stat-card p.animate-loop {
  animation: pulseFade 6s infinite ease-in-out;
}

.modal {
    display: none; /* Keep it hidden until triggered */
    position: fixed;
    z-index: 1000;
    left: 0; top: 0;
    width: 100%; height: 140%;
    background-color: rgba(0,0,0,0.4);
    
    align-items: center;
    justify-content: center;
}

.modal-content {
    background-color: #fff;
    padding: 20px;
    border: 1px solid #888;
    width: 100%;
    max-width: 500px;
    border-radius: 30px;
    box-shadow: 0 0 15px rgba(0,0,0,0.3);
    position: relative;
}

.close {
    position: absolute;
    top: 10px;
    right: 15px;
    color: #aaa;
    font-size: 28px;
    font-weight: bold;
    cursor: pointer;
}
.close:hover, .close:focus {
    color: black;
    text-decoration: none;
}
</style>
    <!-- JavaScript for sidebar toggle -->
  <script>
function adjustSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    if (window.innerWidth <= 768) { 
        sidebar.classList.add('collapsed');
        mainContent.style.marginLeft = '0';
    } else {
        sidebar.classList.remove('collapsed');
        mainContent.style.marginLeft = '250px';
    }
}

function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('collapsed');

    if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '0';
    } else {
        mainContent.style.marginLeft = '250px';
    }
}

window.addEventListener('load', adjustSidebar);
window.addEventListener('resize', adjustSidebar);


// Automatically apply collapsed state on load if saved

</script>


    <script>
       function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('collapsed');
    mainContent.classList.toggle('expanded');
}
document.addEventListener('DOMContentLoaded', function () {
    const recentActivitiesList = document.getElementById('recentActivitiesList');

    // Fetch all notifications (or just recent ones depending on your backend)
    fetch('fetch_notifications.php')
        .then(res => res.json())
        .then(data => {
            recentActivitiesList.innerHTML = ''; // Clear old list

            if (data.length === 0) {
                recentActivitiesList.innerHTML = '<li>No recent activities</li>';
            } else {
                data.forEach(notif => {
                    const li = document.createElement('li');
                    // Customize message format here, e.g.:
                    li.textContent = `${notif.name}:"phone"📞 ${notif.phone} 💬${notif.message}`;
                    recentActivitiesList.appendChild(li);
                });
            }
        })
        .catch(err => {
            console.error('Error loading recent activities:', err);
            recentActivitiesList.innerHTML = '<li>Error loading activities</li>';
        });
});

    </script>
<script>
document.querySelectorAll('.stat-card .count').forEach(counter => {
  const target = parseFloat(counter.getAttribute('data-target'));
  const isMoney = counter.innerText.includes("KES");
  const isPercent = counter.innerText.includes("%");
  let count = 0;
  const duration = 700;
  const steps = duration / 50;
  const increment = target / steps;

  function updateCount() {
    counter.classList.remove('visible');
    setTimeout(() => {
      count += increment;
      if (count < target) {
        if (isMoney) {
          counter.innerText = 'KES ' + count.toFixed(2);
        } else if (isPercent) {
          counter.innerText = count.toFixed(0) + '%';
        } else {
          counter.innerText = Math.floor(count);
        }
        counter.classList.add('visible');
        setTimeout(updateCount, 50);
      } else {
        if (isMoney) {
          counter.innerText = 'KES ' + target.toFixed(2);
        } else if (isPercent) {
          counter.innerText = target.toFixed(0) + '%';
        } else {
          counter.innerText = Math.floor(target);
        }
        counter.classList.add('visible');

        // Animation loop after final value
        setTimeout(() => {
          counter.classList.add('animate-loop');
        }, 500);
      }
    }, 200);
  }

  counter.classList.add('visible');
  updateCount();
});
</script>




<script>
document.addEventListener('DOMContentLoaded', function () {
    const notifButton = document.querySelector('.taskbar-btn'); // 🔔 button
    const modal = document.getElementById('notifModal');
    const notifList = document.getElementById('notifList');

    // Show all notifications on button click
    notifButton.addEventListener('click', function () {
        fetch('fetch_notifications.php')
            .then(res => res.json())
            .then(data => {
                notifList.innerHTML = '';
                if (data.length === 0) {
                    notifList.innerHTML = '<p>No notifications</p>';
                } else {
                    data.forEach(notif => {
                        notifList.innerHTML += `
                            <div style="border-bottom:1px solid #ccc; margin-bottom:10px; padding-bottom:10px;">
                                <strong>${notif.name}</strong><br>
                                📞 ${notif.phone}<br>
                                📧 ${notif.email}<br>
                                💬 ${notif.message}<br>
                                <small style="color:${notif.status === 'unread' ? 'red' : 'gray'}">
                                    ${notif.status === 'unread' ? 'Unread' : 'Read'}
                                </small>
                            </div>
                        `;
                    });
                }
                modal.style.display = 'flex'; // Open modal
            });
    });

    // On page load: check for unread and auto-popup
    fetch('fetch_unread_notifications.php')
        .then(res => res.json())
        .then(data => {
            if (data.length > 0) {
                notifList.innerHTML = `<p><strong>You have ${data.length} new notification(s):</strong></p>`;
                data.forEach(notif => {
                    notifList.innerHTML += `
                        <div style="border-bottom:1px solid #ccc; margin-bottom:10px; padding-bottom:10px;">
                            <strong>${notif.name}</strong><br>
                            📞 ${notif.phone}<br>
                            📧 ${notif.email}<br>
                            💬 ${notif.message}
                        </div>
                    `;

                });
                modal.style.display = 'flex';

                // Mark as read
                fetch('mark_notifications_read.php', { method: 'POST' });
            }
        });

    // Click outside to close
    window.addEventListener('click', function (e) {
        if (e.target === modal) {
            modal.style.display = 'none';
        }
    });
});
</script>


</body>
</html>
