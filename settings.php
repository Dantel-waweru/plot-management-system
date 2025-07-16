<?php
// settings.php

session_start();
if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'User';

// Save theme preference
$update_success = false;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selected_theme = $_POST['theme'] ?? 'light';
    $_SESSION['preferred_theme'] = $selected_theme;
    $update_success = true;
}

// Load current theme
$current_theme = $_SESSION['preferred_theme'] ?? 'light';

ob_start();
?>

<h1>Settings</h1>

<?php if ($update_success): ?>
    <div class="message success">✅ Settings updated successfully.</div>
<?php endif; ?>

<form method="POST" action="settings.php" style="max-width: 600px;">
    <label for="theme">Theme Preference:</label>
    <select id="theme" name="theme">
        <option value="light" <?= $current_theme === 'light' ? 'selected' : '' ?>>Light</option>
        <option value="dark" <?= $current_theme === 'dark' ? 'selected' : '' ?>>Dark</option>
        <option value="auto" <?= $current_theme === 'auto' ? 'selected' : '' ?>>Auto</option>
    </select>

    <br><br>
    <button type="submit" class="btn">💾 Save Settings</button>
</form>

<hr style="margin: 40px 0; border-color: #bbb;" />

<h2>Account Settings</h2>
<!-- These links open the profile.php page with URL hash to trigger modal open -->
<p>⚙️ <a href="profile.php#editProfileModal">Edit Profile</a></p>
<p>🔑 <a href="profile.php#changePasswordModal">Change Password</a></p>

<?php
$content = ob_get_clean();
include 'layout.php';
?>
