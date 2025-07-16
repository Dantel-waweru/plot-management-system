<?php
require_once 'includes/db.php';

if (isset($_GET['token'])) {
    $token = $_GET['token'];
    $stmt = $conn->prepare("SELECT user_id FROM users WHERE activation_token = ?");
    $stmt->bind_param("s", $token);
    $stmt->execute();
    $stmt->store_result();

    if ($stmt->num_rows == 1) {
        $stmt->bind_result($user_id);
        $stmt->fetch();

        $update = $conn->prepare("UPDATE users SET is_active = 1, activation_token = NULL WHERE user_id = ?");
        $update->bind_param("i", $user_id);
        $update->execute();

        echo "Account activated! You may now <a href='login.php'>login</a>.";
    } else {
        echo "Invalid or expired activation token.";
    }
}
?>
