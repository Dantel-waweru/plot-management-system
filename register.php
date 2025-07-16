<?php
session_start();
require_once 'includes/db.php';
require_once 'includes/csrf.php';


$errors = [];

if ($_SERVER["REQUEST_METHOD"] == "POST") {
    // CSRF Token Check
    if (!validateToken($_POST['csrf_token'])) {
        $errors[] = "Invalid CSRF token.";
    }

    // Validate inputs
    $business_name = trim($_POST['name']);
    $phone = trim($_POST['phone']);
    $email = trim($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    $role = $_POST['role'];
    $country_code = $_POST['country_code'];

    if (empty($business_name) || empty($phone) || empty($email) || empty($password) || empty($confirm_password) || empty($role) || empty($country_code)) {
        $errors[] = "All fields are required.";
    }

    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match.";
    }

    $full_phone = $country_code . $phone;

    if (empty($errors)) {
        // Check if user already exists
        $stmt = $conn->prepare("SELECT user_id FROM users WHERE email = ? OR phone = ?");
        $stmt->bind_param("ss", $email, $full_phone);
        $stmt->execute();
        $stmt->store_result();

        if ($stmt->num_rows > 0) {
            $errors[] = "User with that email or phone already exists.";
        } else {
            $hashed_password = password_hash($password, PASSWORD_BCRYPT);

            // Insert into users table
            $stmt = $conn->prepare("INSERT INTO users (name, phone, email, password, role, subscription_status) VALUES (?, ?, ?, ?, ?, 'active')");
            $stmt->bind_param("sssss", $business_name, $full_phone, $email, $hashed_password, $role);
            
            if ($stmt->execute()) {
                $user_id = $stmt->insert_id;

                // If role is landlord, also insert into landlords table
                if ($role === 'landlord') {
                    $stmt_landlord = $conn->prepare("INSERT INTO landlords (name, phone, email, password, user_id) VALUES (?, ?, ?, ?,?)");
                    $stmt_landlord->bind_param("sssss", $business_name, $full_phone, $email, $hashed_password, $user_id);
                    $stmt_landlord->execute();
                }

                $_SESSION['user_id'] = $user_id;
                $_SESSION['user_name'] = $business_name;
                $_SESSION['user_role'] = $role;

                header("Location: dashboard.php");
                exit();
            }
             if ($role === 'tenant') {
            $paid_status = 'unpaid';
            $stmt_tenant = $conn->prepare("INSERT INTO tenants (plot_id, name, phone, email, room_number, paid_status) VALUES (?, ?, ?, ?, ?, ?)");
            $stmt_tenant->bind_param("isssss", $plot_id, $fullname, $full_phone, $email, $room_number, $paid_status);
            $stmt_tenant->execute();
        }
 else {
                $errors[] = "Registration failed. Please try again.";
            }
        }
    }
}

$csrf_token = generateToken();
?>

<!-- HTML FORM -->
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>User Registration</title>
    <style>
        body {
            font-family: 'Poppins', sans-serif;
            line-height: 1.6;
            background: linear-gradient(0.95turn, #1e3a76, #4b6faa, #96b5e4, #f1e4b1, #f8c3b4);
            color: #212529;
        }
        .container {
            max-width: 400px;
            margin: 50px auto;
            padding: 20px;
            background-color: #fff;
            border-radius: 10px;
            box-shadow: 0 4px 6px rgba(0, 0, 0, 0.1);
        }
        h2 {
            text-align: center;
            color: #333;
            margin-bottom: 20px;
        }
        label {
            display: block;
            margin-bottom: 6px;
            font-weight: 500;
            color: #333;
        }
        input[type="text"],
        input[type="email"],
        input[type="password"],
        select {
            width: 100%;
            padding: 10px;
            margin-bottom: 15px;
            border-radius: 8px;
            border: 1px solid #ccc;
            font-size: 14px;
        }
        .error {
            color: red;
            margin-bottom: 10px;
        }
        .btn-submit {
            background-color: #34b7f1;
            color: white;
            border: none;
            padding: 12px;
            width: 100%;
            border-radius: 8px;
            font-size: 16px;
            cursor: pointer;
            transition: background-color 0.3s;
        }
        .btn-submit:hover {
            background-color: #0085e2;
        }
        .signup-link {
            text-align: center;
            margin-top: 20px;
        }
        .signup-link a {
            color: #34b7f1;
            text-decoration: none;
        }
        .strength {
            font-size: 12px;
            margin-top: -10px;
        }
        .phone-container {
            display: flex;
            align-items: center;
        }
        .phone-container select,
        .phone-container input {
            width: 50%;
            margin-right: 5px;
        }

    </style>
      <style>
        label { display: block; margin-top: 10px; }
        #tenant-section { display: none; }
    </style>
    <script>
        function checkPasswordStrength(password) {
            var strength = 0;
            if (password.length > 5) strength += 1;
            if (password.match(/[a-z]/) && password.match(/[A-Z]/)) strength += 1;
            if (password.match(/[0-9]/)) strength += 1;
            if (password.match(/[^a-zA-Z0-9]/)) strength += 1;

            var strengthLabel = document.getElementById("passwordStrength");

            if (strength == 1) {
                strengthLabel.style.color = "red";
                strengthLabel.innerText = "Weak";
            } else if (strength == 2) {
                strengthLabel.style.color = "orange";
                strengthLabel.innerText = "Medium";
            } else if (strength == 3 || strength == 4) {
                strengthLabel.style.color = "green";
                strengthLabel.innerText = "Strong";
            } else {
                strengthLabel.innerText = "";
            }
        }
    </script>
    <script>
        function toggleTenantFields() {
            var role = document.getElementById("role").value;
            var tenantSection = document.getElementById("tenant-section");
            tenantSection.style.display = (role === "tenant") ? "block" : "none";
        }
    </script>
</head>
<body>

<div class="container">
    <h2>Create Account</h2>

    <?php if (!empty($errors)): ?>
        <div class="error">
            <?php foreach ($errors as $err): ?>
                <p><?php echo htmlspecialchars($err); ?></p>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>

    <form method="POST" action="">
        <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">

        <label for="name">Business Name *</label>
        <input type="text" name="name" id="name" required>

        <label for="email">Email Address *</label>
        <input type="email" name="email" id="email" required>

        <label for="phone">Mobile Number *</label>
        <div class="phone-container">
            <select name="country_code" id="country_code" required>
                <option value="+1">+1 (USA)</option>
                <option value="+44">+44 (UK)</option>
                <option value="+91">+91 (India)</option>
                <option value="+254">+254 (Kenya)</option>
            </select>
            <input type="text" name="phone" id="phone" required placeholder="Enter phone number">
        </div>

        <label for="password">Password *</label>
        <input type="password" name="password" id="password" onkeyup="checkPasswordStrength(this.value)" required>

        <label for="confirm_password">Confirm Password *</label>
        <input type="password" name="confirm_password" id="confirm_password" required>

        <div id="passwordStrength" class="strength"></div>

        <label for="role">Select Role *</label>
        <select name="role" id="role" required>
            <option value="">-- Select Role --</option>
            <option value="tenant">Tenant</option>
            <option value="landlord">Landlord</option>
            <option value="user">User</option>
            <option value="worker">Worker</option>
        </select>
        <div id="tenant-section">
            <label>Plot ID:</label>
            <input type="number" name="plot_id" min="1" placeholder="Enter plot ID">

            <label>Room Number:</label>
            <input type="text" name="room_number" placeholder="Room A1, B2 etc.">
        </div>

        <label>
            <input type="checkbox" name="terms" required> I agree to the <a href="terms.php" target="_blank">Terms and Conditions</a>
        </label>

        <button type="submit" class="btn-submit">Register</button>
    </form>

    <div class="signup-link">
        <p>Have an account? <a href="login.php">Sign In</a></p>
    </div>

    <div style="margin-top: 20px; text-align: center;">
        <a href="index.php" class="btn-home" style="display: inline-block; padding: 10px 20px; background-color: #007bff; color: white; text-decoration: none; border-radius: 5px;">← Back to Home</a>
    </div>
</div>

</body>
</html>
