<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'guest';
$theme = $_SESSION['preferred_theme'] ?? 'light';
$theme_class = ($theme === 'auto') ? '' : $theme . '-theme';

if (!isset($content)) {
    $content = "<p>No content defined.</p>";
}
$current_page = basename($_SERVER['PHP_SELF']);
?>
<!DOCTYPE html>
<html lang="en">
<link rel="icon" type="image/jpeg" href="images/icon.jpg">
<head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
    <title>Plots Management Dashboard</title>
    <link rel="stylesheet" href="style.css"/>
    <script defer src="script.js"></script>
    <style>
          body {
            font-family: Arial, sans-serif;
            margin: 0;
            padding: 0;
        }

        :root {
            --light-bg: #ffffff;
            --light-text: #1a1a1a;
            --light-sidebar: #f1f1f1;
            --light-hover: #e0e0e0;
            --light-taskbar: #f9f9f9;

            --dark-bg: #121212;
            --dark-text: blue;
            --dark-sidebar: #1f1f1f;
            --dark-hover: #2c2c2c;
            --dark-taskbar: #1e1e1e;

            --primary: #007bff;
            --primary-hover: #0056b3;
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
  transition: margin-left 0.3s ease;
  margin-left: 250px; /* same as .sidebar width */
  padding: 20px;
  overflow-y: auto;
}

.sidebar.collapsed + .main-content {
  margin-left: 0;
}


        body.dark-theme {
            background-color: var(--dark-bg);
            color: var(--dark-text);
        }

        a {
            color: var(--primary);
            text-decoration: none;
        }

        body.dark-theme a {
            color: #66b2ff;
        }

        a:hover {
            text-decoration: underline;
        }

        .sidebar {
            width: 250px;
            background-color: var(--light-sidebar);
            color: var(--light-text);
            padding: 20px;
            transition: background-color 0.3s, color 0.3s;
        }

        body.dark-theme .sidebar {
            background-color: var(--dark-sidebar);
            color: var(--dark-text);
        }

        .sidebar a, .sidebar span {
            display: block;
            padding: 10px 0;
            color: inherit;
        }

        .sidebar a:hover, .sidebar span:hover {
            background-color: var(--light-hover);
        }

        body.dark-theme .sidebar a:hover {
            background-color: var(--dark-hover);
        }

        .sidebar.collapsed {
            width: 0;
            padding: 0;
            overflow: hidden;
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

.main-content {
    flex: 1;
    padding: 20px;
    transition: all 0.3s ease;
}

.main-content.expanded {
    margin-left: 0.00;
}

        .main-content {
            padding: 20px;
            flex: 1;
        }

        .toggle-btn {
            position: fixed;
            top: 10px;
            left: 10px;
            z-index: 1000;
            background-color: #6c63ff;
            color: #fff;
            border: none;
            padding: 10px;
            cursor: pointer;
            border-radius: 4px;
        }

        .taskbar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: var(--light-taskbar);
            color: var(--light-text);
            border-bottom: 1px solid #ccc;
        }

        body.dark-theme .taskbar {
            background-color: var(--dark-taskbar);
            color: var(--dark-text);
            border-bottom: 1px solid #444;
        }

        .taskbar-btn {
            background-color: #555;
            color: white;
            padding: 6px 12px;
            border: none;
            border-radius: 4px;
            cursor: pointer;
        }

        body.light-theme .taskbar-btn {
            background-color: #ccc;
            color: #000;
        }

        body.dark-theme .taskbar-btn {
            background-color: #333;
            color: #fff;
        }

        .btn {
            padding: 10px 20px;
            background-color: var(--primary);
            color: #fff;
            border: none;
            border-radius: 4px;
        }

        .btn:hover {
            background-color: var(--primary-hover);
        }

        body.dark-theme .btn {
            background-color: #444;
        }

        body.dark-theme .btn:hover {
            background-color: #666;
        }

        .message.success {
            background-color: #d4edda;
            color: #155724;
            padding: 10px;
            border: 1px solid #c3e6cb;
            border-radius: 4px;
            margin-bottom: 20px;
        }

        body.dark-theme .message.success {
            background-color: #23472a;
            color: #c8fbc8;
            border-color: #4c8a55;
        }

       

        h2, h3, h4, p, span, li {
            transition: color 0.3s;
        }

        /* Make sidebar menu active item stand out */
        .sidebar-menu a.active {
            font-weight: bold;
            background-color: var(--primary);
            color: #fff;
            border-radius: 4px;
        }

        body.dark-theme .sidebar-menu a.active {
            background-color: #0056b3;
            color: #fff;
        }
    </style>
</head>
<body class="<?= $theme_class ?>">

<!-- Sidebar -->
<aside class="sidebar">
    <div class="sidebar-header">
        <h2>Plots Manager</h2>
        <p class="role-badge"><?= ucfirst($user_role); ?></p>
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
</aside>

<!-- Main Content -->
<main class="main-content">
    <!-- Top Taskbar -->
    <div class="taskbar">
        <div class="taskbar-left">
           <button class="toggle-btn" onclick="toggleSidebar()">☰</button>
            <span><h2>Welcome, <strong><?= ucfirst($user_name); ?></strong></h2></span>
        </div>
        <div class="taskbar-right">
            <button class="taskbar-btn">🔔 Notifications</button>
            <button class="taskbar-btn">✉️ Messages</button>
            <a href="logout.php" class="taskbar-btn logout" onclick="return confirm('Logout?')">🚪 Logout</a>
        </div>
    </div>

    <!-- Page Content -->
    <section class="page-content">
        <?= $content ?>
    </section>
</main>

<!-- JavaScript -->
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
</script>
<script>
function toggleSidebar() {
    const sidebar = document.querySelector('.sidebar');
    const mainContent = document.querySelector('.main-content');

    sidebar.classList.toggle('collapsed');

    if (sidebar.classList.contains('collapsed')) {
        mainContent.style.marginLeft = '0';
    } else {
        mainContent.style.marginLeft = '250px'; // match sidebar width
    }
}
</script>

<?php if ($theme === 'auto'): ?>
<script>
    const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
    document.body.classList.add(prefersDark ? 'dark-theme' : 'light-theme');
</script>
<?php endif; ?>
</body>
</html>
