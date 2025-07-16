<?php
require_once 'includes/db.php'; // Your DB connection file

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (!isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];
$user_name = $_SESSION['user_name'] ?? 'User';
$user_role = $_SESSION['user_role'] ?? 'guest';
$theme = $_SESSION['preferred_theme'] ?? 'light';
$theme_class = ($theme === 'auto') ? '' : $theme . '-theme';

// Fetch messages for the logged-in user using MySQLi
try {
    $stmt = $conn->prepare("SELECT m.id, m.subject, m.body, m.sent_at, m.is_read, u.name AS sender_name
                            FROM messages m
                            JOIN users u ON m.sender_id = u.user_id
                            WHERE m.recipient_id = ?
                            ORDER BY m.sent_at DESC");
    $stmt->bind_param("i", $user_id);
    $stmt->execute();
    $result = $stmt->get_result();
    $messages = $result->fetch_all(MYSQLI_ASSOC);
    $stmt->close();
} catch (Exception $e) {
    $messages = [];
    // Log error or handle properly in production
}

// Fetch tenants for SMS chat
try {
    $tenants_stmt = $conn->prepare("SELECT tenant_id, name, phone FROM tenants ORDER BY name ASC");
    $tenants_stmt->execute();
    $tenants_result = $tenants_stmt->get_result();
    $tenants = $tenants_result->fetch_all(MYSQLI_ASSOC);
    $tenants_stmt->close();
} catch (Exception $e) {
    $tenants = [];
}

ob_start();
?>

<!-- Permission Request Modal (Simulated) -->
<div id="sms-permission-modal" style="display:none; position:fixed; top:0; left:0; width:100%; height:100%; background:rgba(0,0,0,0.6); z-index:1000; justify-content:center; align-items:center;">
    <div style="background:#fff; padding:20px; border-radius:8px; max-width:400px; text-align:center;">
        <h3>Allow SMS Access?</h3>
        <p>To read and send messages using your device's SMS app, we need your permission.</p>
        <button onclick="grantSmsPermission()" style="margin-right:10px;">Allow</button>
        <button onclick="denySmsPermission()">Deny</button>
    </div>
</div>

<script>
    // Simulate asking for permission (localStorage just for demo)
    window.onload = function () {
        if (!localStorage.getItem('sms_permission')) {
            document.getElementById('sms-permission-modal').style.display = 'flex';
        }
    };

    function grantSmsPermission() {
        localStorage.setItem('sms_permission', 'granted');
        document.getElementById('sms-permission-modal').style.display = 'none';
    }

    function denySmsPermission() {
        localStorage.setItem('sms_permission', 'denied');
        document.getElementById('sms-permission-modal').style.display = 'none';
    }
</script>

<h2>Inbox</h2>

<?php if (empty($messages)): ?>
    <p>No messages found.</p>
<?php else: ?>
    <ul class="messages-list" style="list-style:none; padding:0;">
    <?php foreach ($messages as $msg): ?>
        <li style="border-bottom:1px solid #ccc; padding:10px 0; background-color: <?= $msg['is_read'] ? '#f9f9f9' : '#e6f7ff' ?>">
            <a href="message_view.php?id=<?= htmlspecialchars($msg['id']) ?>" style="text-decoration:none; color: inherit;">
                <strong><?= htmlspecialchars($msg['subject']) ?></strong> from <em><?= htmlspecialchars($msg['sender_name']) ?></em><br/>
                <small><?= htmlspecialchars(substr($msg['body'], 0, 100)) ?><?= strlen($msg['body']) > 100 ? '...' : '' ?></small><br/>
                <small><time datetime="<?= $msg['sent_at'] ?>"><?= date('M d, Y H:i', strtotime($msg['sent_at'])) ?></time></small>
            </a>
        </li>
    <?php endforeach; ?>
    </ul>
<?php endif; ?>

<hr>

<button id="toggle-tenants" style="background-color: #007bff; color: white; border: none; padding: 10px 20px; border-radius: 5px; margin-bottom: 15px; cursor: pointer;">
    ➕ New Chat
</button>

<div id="tenant-chat-section" style="display: none; padding: 15px; background-color: #f2f2f2; border-radius: 10px;">
    <h3>📱 Start Chat with Tenant via SMS</h3>

    <!-- Search box -->
    <input type="text" id="tenant-search" placeholder="Search tenants by name or phone..." 
           style="width: 100%; padding: 8px; margin-bottom: 15px; border-radius: 5px; border: 1px solid #ccc;">

    <?php if (!empty($tenants)): ?>
        <ul id="tenant-list" style="list-style: none; padding: 0;">
            <?php foreach ($tenants as $tenant): 
                $tenant_name = htmlspecialchars($tenant['name']);
                $tenant_phone_raw = $tenant['phone'];
                $tenant_phone_clean = preg_replace('/\D/', '', $tenant_phone_raw);
                $encoded_name = urlencode($tenant_name);
                $encoded_user = urlencode($user_name);
                $sms_message = "Hello $encoded_name, this is $encoded_user.";
                $wa_message = "Hello $encoded_name, this is $encoded_user.";
            ?>
            <li class="tenant-item" style="padding: 8px; margin-bottom: 8px; background: #fff; border: 1px solid #ccc; border-radius: 6px;"
                data-name="<?= strtolower($tenant_name) ?>" data-phone="<?= strtolower($tenant_phone_raw) ?>">
                <strong><?= $tenant_name ?></strong> - <?= htmlspecialchars($tenant_phone_raw) ?><br>
                <a href="sms:<?= $tenant_phone_clean ?>?body=<?= $sms_message ?>" 
                   style="margin-right: 15px; color: #007bff; text-decoration: none;">
                    📤 Send SMS
                </a>
                <a href="https://wa.me/<?= $tenant_phone_clean ?>?text=<?= $wa_message ?>" target="_blank" 
                   style="color: #25D366; text-decoration: none;">
                    💬 WhatsApp
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    <?php else: ?>
        <p>No tenants found.</p>
    <?php endif; ?>
</div>

<script>
    // Toggle tenant chat section (existing)
    document.getElementById('toggle-tenants').addEventListener('click', function () {
        const section = document.getElementById('tenant-chat-section');
        section.style.display = (section.style.display === 'none' || section.style.display === '') ? 'block' : 'none';
    });

    // Tenant search filter
    document.getElementById('tenant-search').addEventListener('input', function() {
        const searchTerm = this.value.toLowerCase();
        const tenants = document.querySelectorAll('#tenant-list .tenant-item');

        tenants.forEach(tenant => {
            const name = tenant.getAttribute('data-name');
            const phone = tenant.getAttribute('data-phone');
            if (name.includes(searchTerm) || phone.includes(searchTerm)) {
                tenant.style.display = '';
            } else {
                tenant.style.display = 'none';
            }
        });
    });
</script>



<?php
$content = ob_get_clean();
include 'layout.php';  // Or your layout file
