<?php
require_once 'includes/db.php';
session_start();

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_profile'])) {
    $name = trim($_POST['name']);
    $email = trim($_POST['email']);
    $phone = trim($_POST['phone']);

    if ($name && $email && $phone) {
        $stmt = $conn->prepare("UPDATE users SET name = ?, email = ?, phone = ? WHERE user_id = ?");
        $stmt->bind_param("sssi", $name, $email, $phone, $user_id);
        $stmt->execute();
        if ($stmt->affected_rows >= 0) {
            $_SESSION['message'] = "Profile updated successfully.";
        } else {
            $_SESSION['error'] = "Failed to update profile.";
        }
        $stmt->close();
    } else {
        $_SESSION['error'] = "Please fill in all fields.";
    }
}

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['change_password'])) {
    $current_pass = $_POST['current_password'];
    $new_pass = $_POST['new_password'];
    $confirm_pass = $_POST['confirm_password'];

    $stmt = $conn->prepare("SELECT password FROM users WHERE user_id = ?");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $stmt->bind_result($hashed_password);
    $stmt->fetch();
    $stmt->close();

    if (!password_verify($current_pass, $hashed_password)) {
        $_SESSION['error'] = "Current password is incorrect.";
    } elseif ($new_pass !== $confirm_pass) {
        $_SESSION['error'] = "New passwords do not match.";
    } elseif (strlen($new_pass) < 6) {
        $_SESSION['error'] = "New password must be at least 6 characters.";
    } else {
        $new_hashed = password_hash($new_pass, PASSWORD_DEFAULT);
        $stmt = $conn->prepare("UPDATE users SET password = ? WHERE user_id = ?");
        $stmt->bind_param("si", $new_hashed, $user_id);
        $stmt->execute();
        if ($stmt->affected_rows >= 0) {
            $_SESSION['message'] = "Password changed successfully.";
        } else {
            $_SESSION['error'] = "Failed to change password.";
        }
        $stmt->close();
    }
}

// Fetch user info
$stmt = $conn->prepare("SELECT name, email, role, phone, created_at FROM users WHERE user_id = ?");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();
$user = $result->fetch_assoc();

$user_name = $user['name'];
$user_email = $user['email'];
$user_role = $user['role'];
$user_phone = $user['phone'];
$user_join_date = $user['created_at'];

// Fetch plots and rooms summary
$stmt = $conn->prepare("
    SELECT 
        p.plot_id, 
        p.plot_name, 
        COUNT(r.id) AS total_rooms,
        SUM(CASE WHEN r.status = 'occupied' THEN 1 ELSE 0 END) AS occupied_rooms,
        SUM(CASE WHEN r.status = 'vacant' THEN 1 ELSE 0 END) AS vacant_rooms
    FROM plots p
    LEFT JOIN rooms r ON p.plot_id = r.plot_id
    WHERE p.landlord_id = ?
    GROUP BY p.plot_id, p.plot_name
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$plots = [];
while ($row = $result->fetch_assoc()) {
    $plots[] = $row;
}

// Fetch tenants
$stmt = $conn->prepare("
    SELECT 
        t.name AS tenant_name,
        t.room_number,
        p.plot_name
    FROM tenants t
    INNER JOIN plots p ON t.plot_id = p.plot_id
    WHERE t.landlord_id = ?
    ORDER BY p.plot_name, t.room_number
");
$stmt->bind_param("i", $user_id);
$stmt->execute();
$result = $stmt->get_result();

$tenants = [];
while ($row = $result->fetch_assoc()) {
    $tenants[] = $row;
}

$total_plots = count($plots);
$total_rooms = 0;
$total_occupied = 0;
$total_vacant = 0;

foreach ($plots as $plot) {
    $total_rooms += $plot['total_rooms'];
    $total_occupied += $plot['occupied_rooms'];
    $total_vacant += $plot['vacant_rooms'];
}

$message = $_SESSION['message'] ?? '';
$error = $_SESSION['error'] ?? '';
unset($_SESSION['message'], $_SESSION['error']);


ob_start();
?>


<head>
<meta charset="UTF-8" />
<meta name="viewport" content="width=device-width, initial-scale=1" />
<title>User Profile</title>
<style>
    body {
        font-family: Arial, sans-serif;
        background-color: #e9ecef;
        margin: 0;
        padding: 0;
    }
    .container {
        max-width: 1000px;
        margin: 30px auto;
        background: #fff;
        padding: 25px 30px;
        border-radius: 8px;
        box-shadow: 0 2px 10px rgba(0,0,0,0.1);
    }
    h1, h2, h3, h4 {
        color: #333;
    }
    p {
        font-size: 1.1rem;
        margin: 8px 0;
        color: #444;
    }
    .message {
        padding: 10px 15px;
        margin-bottom: 20px;
        border-radius: 5px;
        font-weight: bold;
    }
    .success {
        background-color: #d4edda;
        color: #155724;
        border: 1px solid #c3e6cb;
    }
    .error {
        background-color: #f8d7da;
        color: #721c24;
        border: 1px solid #f5c6cb;
    }
    .profile-container {
        display: flex;
        gap: 40px;
        flex-wrap: wrap;
    }
    .profile-info {
        flex: 2;
        min-width: 320px;
    }
    .profile-actions {
        flex: 1;
        min-width: 200px;
        display: flex;
        flex-direction: column;
        gap: 15px;
        align-items: flex-start;
        margin-top: 40px;
    }
    button.btn {
        background-color: blueviolet;
        color: white;
        border: none;
        padding: 12px 25px;
        font-size: 1rem;
        border-radius: 5px;
        cursor: pointer;
        transition: background-color 0.3s ease;
        width: 100%;
    }
    button.btn:hover {
        background-color: indigo;
    }
    table {
        width: 100%;
        border-collapse: collapse;
        margin-top: 15px;
        font-size: 0.95rem;
    }
    th, td {
        padding: 10px;
        border: 1px solid #ccc;
        text-align: left;
    }
    th {
        background-color: #6a0dad;
        color: #fff;
    }

    /* Modal Styles */
    .modal {
        display: none;
        position: fixed;
        z-index: 9999;
        padding-top: 100px;
        left: 0; top: 0;
        width: 100%;
        height: 100%;
        overflow: auto;
        background-color: rgba(0,0,0,0.4);
    }
    .modal-content {
        background-color: #fff;
        margin: auto;
        padding: 25px 30px;
        border-radius: 8px;
        width: 90%;
        max-width: 450px;
        position: relative;
        box-shadow: 0 0 15px rgba(0,0,0,0.2);
    }
    .close {
        color: #aaa;
        position: absolute;
        top: 12px;
        right: 18px;
        font-size: 30px;
        font-weight: bold;
        cursor: pointer;
        transition: color 0.2s ease;
    }
    .close:hover, .close:focus {
        color: #000;
        text-decoration: none;
    }
    input[type="text"], input[type="email"], input[type="password"] {
        width: 100%;
        padding: 9px 12px;
        margin: 8px 0 20px 0;
        border: 1px solid #ccc;
        border-radius: 5px;
        box-sizing: border-box;
        font-size: 1rem;
    }
    label {
        font-weight: 600;
        display: block;
        margin-bottom: 6px;
        color: #333;
    }
</style>
</head>
<body>

<div class="container">

<?php if ($message): ?>
    <div class="messagesuccess"><?= htmlspecialchars($message) ?></div>

<?php endif; ?> <?php if ($error): ?>

<div class="message error"><?= htmlspecialchars($error) ?></div>
<?php endif; ?> 
<h1>User Profile</h1> 
<div class="profile-container"> 
  <div class="profile-info"> 
    <h2>Welcome, <?= htmlspecialchars($user_name) ?></h2> 
    <p><strong>Email:</strong> <?= htmlspecialchars($user_email) ?></p> 
    <p><strong>Role:</strong> <?= htmlspecialchars(ucfirst($user_role)) ?></p> 
    <p><strong>Phone:</strong> <?= htmlspecialchars($user_phone) ?></p> 
    <p><strong>Member since:</strong> <?= date("F j, Y", strtotime($user_join_date)) ?></p> 
  </div> 
  <div class="profile-actions"> 
    <button class="btn" id="editProfileBtn">Edit Profile</button> 
    <button class="btn" id="changePasswordBtn">Change Password</button> 
  </div> 
</div> 
<hr style="margin:40px 0; border-color:#bbb;" /> 
<h2>Plots and Rooms Summary</h2> 
<p><strong>Total Plots:</strong> <?= $total_plots ?></p> 
<p><strong>Total Rooms:</strong> <?= $total_rooms ?></p> 
<p><strong>Occupied Rooms:</strong> <?= $total_occupied ?></p> 
<p><strong>Vacant Rooms:</strong> <?= $total_vacant ?></p> 
<?php if ($plots): ?>


<table>
    <thead>
        <tr>
            <th>Plot Name</th>
            <th>Total Rooms</th>
            <th>Occupied</th>
            <th>Vacant</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($plots as $plot): ?>
            <tr>
                <td><?= htmlspecialchars($plot['plot_name']) ?></td>
                <td><?= $plot['total_rooms'] ?></td>
                <td><?= $plot['occupied_rooms'] ?></td>
                <td><?= $plot['vacant_rooms'] ?></td>
            </tr>
        <?php endforeach; ?>
    </tbody>
</table>
<?php else: ?>

<p>No plots found.</p>
<?php endif; ?> 
<hr style="margin:40px 0; border-color:#bbb;" /> 
<h2>Tenants</h2> 
<?php if ($tenants): ?> 
<table> 
  <thead> 
    <tr> 
      <th>Tenant Name</th> 
      <th>Room Number</th> 
      <th>Plot Name</th> 
    </tr> 
  </thead> 
  <tbody> 
    <?php foreach ($tenants as $tenant): ?> 
      <tr> 
        <td><?= htmlspecialchars($tenant['tenant_name']) ?></td> 
        <td><?= htmlspecialchars($tenant['room_number']) ?></td> 
        <td><?= htmlspecialchars($tenant['plot_name']) ?></td> 
      </tr> 
    <?php endforeach; ?> 
  </tbody> 
</table> 
<?php else: ?> 
<p>No tenants found.</p> 
<?php endif; ?> 
</div> 

<!-- Edit Profile Modal --> 
<div id="editProfileModal" class="modal"> 
  <div class="modal-content"> 
    <span class="close" id="closeEditProfile">&times;</span> 
    <h3>Edit Profile</h3> 
    <form method="POST" action=""> 
      <label for="name">Full Name</label> 
      <input type="text" id="name" name="name" required value="<?= htmlspecialchars($user_name) ?>" />

    <label for="email">Email</label>
    <input type="email" id="email" name="email" required value="<?= htmlspecialchars($user_email) ?>" />

    <label for="phone">Phone</label>
    <input type="text" id="phone" name="phone" required value="<?= htmlspecialchars($user_phone) ?>" />

    <button type="submit" name="update_profile" class="btn">Save Changes</button>
</form>
</div> </div> <!-- Change Password Modal --> <div id="changePasswordModal" class="modal"> <div class="modal-content"> <span class="close" id="closeChangePassword">&times;</span> <h3>Change Password</h3> <form method="POST" action=""> <label for="current_password">Current Password</label> <input type="password" id="current_password" name="current_password" required />

    <label for="new_password">New Password</label>
    <input type="password" id="new_password" name="new_password" required minlength="6" />

    <label for="confirm_password">Confirm New Password</label>
    <input type="password" id="confirm_password" name="confirm_password" required minlength="6" />

    <button type="submit" name="change_password" class="btn">Change Password</button>
</form>
</div> </div> <script>
  // Get modal elements and buttons
  const editProfileBtn = document.getElementById('editProfileBtn');
  const editProfileModal = document.getElementById('editProfileModal');
  const closeEditProfile = document.getElementById('closeEditProfile');

  const changePasswordBtn = document.getElementById('changePasswordBtn');
  const changePasswordModal = document.getElementById('changePasswordModal');
  const closeChangePassword = document.getElementById('closeChangePassword');

  // Show Edit Profile modal on button click
  editProfileBtn.onclick = () => {
    editProfileModal.style.display = 'block';
  };

  // Hide Edit Profile modal on close click
  closeEditProfile.onclick = () => {
    editProfileModal.style.display = 'none';
  };

  // Show Change Password modal on button click
  changePasswordBtn.onclick = () => {
    changePasswordModal.style.display = 'block';
  };

  // Hide Change Password modal on close click
  closeChangePassword.onclick = () => {
    changePasswordModal.style.display = 'none';
  };

  // Hide modals when clicking outside of modal content
  window.onclick = (event) => {
    if (event.target === editProfileModal) {
      editProfileModal.style.display = 'none';
    }
    if (event.target === changePasswordModal) {
      changePasswordModal.style.display = 'none';
    }
  };
</script>
<script>
window.onload = function() {
    const hash = window.location.hash;

    if (hash === '#editProfileModal') {
        document.getElementById('editProfileModal').style.display = 'block';
    } else if (hash === '#changePasswordModal') {
        document.getElementById('changePasswordModal').style.display = 'block';
    }
};

// You should also add your modal close logic here, e.g. clicking 'X' closes modal
</script>
</body>

<?php
$content = ob_get_clean();
include 'layout.php';
?>