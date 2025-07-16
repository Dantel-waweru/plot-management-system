<?php
require_once 'includes/db.php';
require_once 'config.php';

$id = $_GET['id'] ?? 0;

if (!is_numeric($id) || $id <= 0) {
    echo "<p>Invalid room ID.</p>";
    exit;
}

// Fetch room and landlord info using JOIN
$query = "
    SELECT 
        r.*, 
        l.name AS landlord_name, 
        l.phone AS landlord_phone, 
        l.email AS landlord_email,
        p.plot_name AS plots_plot_name, 
        p.location AS plots_location
    FROM rooms r
    LEFT JOIN landlords l ON r.landlord_id = l.user_id
    LEFT JOIN plots p ON r.plot_id = p.plot_id
    WHERE r.id = ?
";

$stmt = $conn->prepare($query);
$stmt->bind_param('i', $id);
$stmt->execute();
$result = $stmt->get_result();
$room = $result->fetch_assoc();

$amenities = [];
if ($room) {
    // Get room amenities from the amenities table directly
    $amenities_query = "SELECT amenity FROM amenities WHERE room_id = ?";
    $amenities_stmt = $conn->prepare($amenities_query);
    
    if ($amenities_stmt) {
        $amenities_stmt->bind_param('i', $id);
        $amenities_stmt->execute();
        $amenities_result = $amenities_stmt->get_result();
        while ($amenity = $amenities_result->fetch_assoc()) {
            $amenities[] = $amenity['amenity'];
        }
    } else {
        echo "<p>Error preparing amenities query: " . $conn->error . "</p>";
    }
} else {
    echo "<p>Room not found for ID: $id</p>";
    exit;
}

// Get user's current reactions for this room (you'll need to implement user sessions)
session_start();
$user_id = $_SESSION['user_id'] ?? 'guest_' . session_id(); // Use session ID for guests

$user_reactions_query = "SELECT reaction FROM room_reactions WHERE room_id = ? AND user_id = ?";
$user_reactions_stmt = $conn->prepare($user_reactions_query);
$user_reactions_stmt->bind_param('is', $id, $user_id);
$user_reactions_stmt->execute();
$user_reactions_result = $user_reactions_stmt->get_result();
$user_reactions = [];
while ($reaction = $user_reactions_result->fetch_assoc()) {
    $user_reactions[] = $reaction['reaction'];
}
?>

<!DOCTYPE html>
<html>
<link rel="icon" type="image/jpeg" href="images/icon.jpg">
<head>
<meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Explore Room</title>
    <style>
        body {
            font-family: 'Segoe UI', sans-serif;
            line-height: 1.6;
            background-image: 
                linear-gradient(0.95turn, rgba(30, 58, 118, 0.7), rgba(75, 111, 170, 0.7)),
                url("images/water2.gif");
            opacity: 0.95; 
            background-size: cover;
            background-repeat: no-repeat;
            background-position: center;
            color: #212529;
        }

        .room-container {
            max-width: 1000px;
            margin: 40px auto;
            background: #fff;
            border-radius: 10px;
            box-shadow: 0 2px 10px rgba(0,0,0,0.1);
        }

        .room-header {
            background: #3f51b5;
            color: #fff;
            padding: 20px;
            text-align: center;
        }

        .room-images {
            display: flex;
            justify-content: center;
            gap: 15px;
            padding: 20px;
            background: #fafafa;
        }

        .room-images img {
            width: 280px;
            height: 180px;
            object-fit: cover;
            border-radius: 8px;
            cursor: pointer;
        }

        .room-details, .amenities, .reaction-box, .notify-box {
            padding: 20px;
        }

        .status.vacant { color: green; font-weight: bold; }
        .status.occupied { color: red; font-weight: bold; }

        .back-button {
            margin-top: 20px;
            padding: 10px 20px;
            background-color: #3f51b5;
            color: white;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            text-align: center;
            text-decoration: none;
        }
        .back-button:hover { background-color: #303f9f; }

        /* Floating Back Button Styles */
        .floating-back-btn {
            position: fixed;
            bottom: 30px;
            right: 30px;
            background: linear-gradient(135deg, #3f51b5, #5c6bc0);
            color: white;
            border: none;
            border-radius: 30px;
            cursor: pointer;
            box-shadow: 0 4px 20px rgba(63, 81, 181, 0.3);
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
            padding: 12px 20px;
            font-size: 16px;
            font-weight: 600;
            transition: all 0.3s ease;
            z-index: 1000;
            text-decoration: none;
            white-space: nowrap;
        }

        .floating-back-btn .arrow {
            font-size: 18px;
            font-weight: bold;
        }

        .floating-back-btn:hover {
            transform: translateY(-3px);
            box-shadow: 0 6px 25px rgba(63, 81, 181, 0.4);
            background: linear-gradient(135deg, #303f9f, #3f51b5);
        }

        .floating-back-btn:active {
            transform: translateY(-1px);
        }

        /* Responsive design for smaller screens */
        @media (max-width: 768px) {
            .floating-back-btn {
                bottom: 20px;
                right: 20px;
                padding: 10px 16px;
                font-size: 14px;
            }
            
            .floating-back-btn .arrow {
                font-size: 16px;
            }
        }

        @media (max-width: 480px) {
            .floating-back-btn {
                padding: 8px 12px;
                font-size: 12px;
            }
            
            .floating-back-btn .arrow {
                font-size: 14px;
            }
        }

        .modal { 
            display: none; 
            position: fixed; 
            z-index: 1; 
            left: 0; 
            top: 0; 
            width: 100%; 
            height: 100%; 
            background-color: rgba(0,0,0,0.8); 
        }
        .modal-content { 
            margin: auto; 
            display: block; 
            width: 80%; 
            max-width: 700px; 
        }
        .modal-content img { 
            width: 100%; 
            height: auto; 
        }
        .close { 
            position: absolute; 
            top: 15px; 
            right: 35px; 
            color: #fff; 
            font-size: 40px; 
            font-weight: bold; 
            cursor: pointer; 
        }

        /* Reaction Styles */
        .reaction-form {
            display: flex;
            gap: 10px;
            flex-wrap: wrap;
            justify-content: center;
        }

        .reaction-btn {
            background: #f5f5f5;
            margin: 5px;
            padding: 12px 20px;
            font-size: 18px;
            border-radius: 25px;
            border: 2px solid #ddd;
            cursor: pointer;
            transition: all 0.3s ease;
            position: relative;
            overflow: hidden;
            display: flex;
            align-items: center;
            gap: 8px;
        }

        .reaction-btn:hover {
            background: #e8f4fd;
            border-color: #2196f3;
            transform: translateY(-2px);
        }

        .reaction-btn.active {
            background: #2196f3;
            color: white;
            border-color: #1976d2;
            animation: reactionPulse 0.6s ease;
        }

        .reaction-btn .emoji {
            font-size: 22px;
            transition: transform 0.3s ease;
        }

        .reaction-btn.active .emoji {
            animation: emojiExpand 0.6s ease;
        }

        @keyframes reactionPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.1); }
            100% { transform: scale(1); }
        }

        @keyframes emojiExpand {
            0% { transform: scale(1); }
            50% { transform: scale(1.3); }
            100% { transform: scale(1); }
        }

        /* Notification Popup Styles */
        .notification-popup {
            position: fixed;
            top: 20px;
            right: 20px;
            background: linear-gradient(135deg, #4caf50, #45a049);
            color: white;
            padding: 15px 20px;
            border-radius: 8px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.3);
            z-index: 10000;
            opacity: 0;
            transform: translateX(100%);
            transition: all 0.3s ease;
            max-width: 300px;
            word-wrap: break-word;
        }

        .notification-popup.show {
            opacity: 1;
            transform: translateX(0);
        }

        .notification-popup.error {
            background: linear-gradient(135deg, #f44336, #d32f2f);
        }

        .notify-box form {
            margin-top: 20px;
            background: #f9f9f9;
            padding: 15px;
            border-radius: 8px;
        }

        .notify-box input, .notify-box button {
            display: block;
            width: 100%;
            margin: 8px 0;
            padding: 10px;
            font-size: 14px;
            border: 1px solid #ddd;
            border-radius: 4px;
        }

        .notify-box button {
            background: #3f51b5;
            color: white;
            border: none;
            cursor: pointer;
            transition: background 0.3s ease;
        }

        .notify-box button:hover {
            background: #303f9f;
        }

        .contact-info {
            background: #f1f1f1;
            padding: 15px;
            margin-top: 20px;
            border-radius: 8px;
        }

        /* Loading animation */
        .loading {
            pointer-events: none;
            opacity: 0.7;
        }

        .loading::after {
            content: '';
            position: absolute;
            top: 50%;
            left: 50%;
            width: 20px;
            height: 20px;
            margin: -10px 0 0 -10px;
            border: 2px solid #fff;
            border-top: 2px solid transparent;
            border-radius: 50%;
            animation: spin 1s linear infinite;
        }

        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
</head>
<body>

<div class="room-container">
    <div class="room-header">
        <h2>Room <?php echo htmlspecialchars($room['room_number']); ?> Details</h2>
    </div>

    <div class="room-images">
        <img id="roomImage" src="<?php echo htmlspecialchars($room['image']); ?>" alt="Room Image">
    </div>

    <div class="room-details">
        <p><strong>Location:</strong> <?php echo htmlspecialchars($room['plots_location']); ?></p>
        <p><strong>plot location:</strong> <?php echo htmlspecialchars($room['location']); ?></p>
        <p><strong>plot name:</strong> <?php echo htmlspecialchars($room['plots_plot_name']); ?></p>
        <p><strong>Size:</strong> <?php echo htmlspecialchars($room['size']); ?></p>
        <p><strong>Price:</strong> Ksh <?php echo number_format($room['price']); ?></p>
        <p><strong>Status:</strong> 
            <span class="status <?php echo $room['status'] === 'vacant' ? 'vacant' : 'occupied'; ?>">
                <?php echo ucfirst($room['status']); ?>
            </span>
        </p>
    </div>

    <div class="amenities">
        <h3>Amenities</h3>
        <?php if (!empty($amenities)): ?>
            <ul>
                <?php foreach ($amenities as $amenity): ?>
                    <li><?php echo htmlspecialchars($amenity); ?></li>
                <?php endforeach; ?>
            </ul>
        <?php else: ?>
            <p>No amenities listed for this room.</p>
        <?php endif; ?>
    </div>

    <div class="reaction-box">
        <h3>What do you think about this room?</h3>
        <div class="reaction-form">
            <button class="reaction-btn <?php echo in_array('like', $user_reactions) ? 'active' : ''; ?>" 
                    data-reaction="like">
                <span class="emoji">👍</span> Like
            </button>
            <button class="reaction-btn <?php echo in_array('love', $user_reactions) ? 'active' : ''; ?>" 
                    data-reaction="love">
                <span class="emoji">❤️</span> Love
            </button>
            <button class="reaction-btn <?php echo in_array('interested', $user_reactions) ? 'active' : ''; ?>" 
                    data-reaction="interested">
                <span class="emoji">⭐</span> Interested
            </button>
            <button class="reaction-btn <?php echo in_array('wow', $user_reactions) ? 'active' : ''; ?>" 
                    data-reaction="wow">
                <span class="emoji">😲</span> Wow
            </button>
        </div>
    </div>

    <?php if ($room['status'] === 'occupied'): ?>
        <div class="notify-box">
            <h3>Room is occupiedooh 😢</h3>
            <p>Would you like to be notified when it's vacant?</p>
            <form id="notifyForm">
                <input type="hidden" name="room_id" value="<?php echo $id; ?>">
                <input type="text" name="name" placeholder="Your Name" required>
                <input type="text" name="phone" placeholder="Phone Number" required>
                <input type="email" name="email" placeholder="Email (optional)">
                <button type="submit">🔔 Notify Me1</button>
            </form>
        </div>
    <?php endif; ?>

    <div class="contact-info">
        <h4>Landlord Contact Info</h4>
        <p><strong>Name:</strong> <?php echo htmlspecialchars($room['landlord_name'] ?? 'N/A'); ?></p>
        <p><strong>Phone:</strong> <?php echo htmlspecialchars($room['landlord_phone'] ?? 'N/A'); ?></p>
        <p><strong>Email:</strong> <?php echo htmlspecialchars($room['landlord_email'] ?? 'N/A'); ?></p>
    </div>
</div>

<!-- Floating Back Button -->
<a href="#" id="floatingBackBtn" class="floating-back-btn" title="Go Back">
    <span class="arrow">←</span>
    <span>Back</span>
</a>

<!-- Image Modal -->
<div id="myModal" class="modal">
    <span class="close">&times;</span>
    <img class="modal-content" id="modalImage">
</div>

<script>
// Global variables
const roomId = <?php echo $id; ?>;

// Image modal functionality
let modal = document.getElementById("myModal");
let img = document.getElementById("roomImage");
let modalImg = document.getElementById("modalImage");

img.onclick = function() {
    modal.style.display = "block";
    modalImg.src = this.src;
}

document.getElementsByClassName("close")[0].onclick = function() {
    modal.style.display = "none";
}

// Optional: close modal when clicking outside the image
modal.onclick = function(e) {
    if (e.target === modal) {
        modal.style.display = "none";
    }
}

// Floating back button functionality
document.getElementById('floatingBackBtn').addEventListener('click', function(e) {
    e.preventDefault();
    
    // Check if there's browser history to go back to
    if (window.history.length > 1) {
        // Use browser's back functionality
        window.history.back();
    } else {
        // Fallback: go to rooms page if no history (e.g., direct link access)
        window.location.href = 'rooms.php';
    }
});

// Notification popup function
function showNotification(message, isError = false) {
    // Remove existing notifications
    const existingNotifications = document.querySelectorAll('.notification-popup');
    existingNotifications.forEach(notification => notification.remove());

    // Create new notification
    const notification = document.createElement('div');
    notification.className = `notification-popup ${isError ? 'error' : ''}`;
    notification.textContent = message;
    
    document.body.appendChild(notification);
    
    // Show notification
    setTimeout(() => {
        notification.classList.add('show');
    }, 100);
    
    // Hide notification after 2 seconds
    setTimeout(() => {
        notification.classList.remove('show');
        setTimeout(() => {
            notification.remove();
        }, 900);
    }, 2000);
}

// Reaction handling
document.querySelectorAll('.reaction-btn').forEach(button => {
    button.addEventListener('click', function() {
        const reaction = this.dataset.reaction;
        const isActive = this.classList.contains('active');
        
        // Add loading state
        this.classList.add('loading');
        
        // AJAX request to handle reaction
        fetch('handle_reaction2.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/x-www-form-urlencoded',
            },
            body: `room_id=${roomId}&reaction=${reaction}&action=${isActive ? 'remove' : 'add'}`
        })
        .then(response => response.json())
        .then(data => {
            this.classList.remove('loading');
            
            if (data.success) {
                if (isActive) {
                    // Remove reaction
                    this.classList.remove('active');
                    showNotification(`You removed your ${reaction} from this room`);
                } else {
                    // Add reaction
                    this.classList.add('active');
                    showNotification(`You ${reaction}d this room!`);

                     // 🔽 Scroll to notify form if it exists
                    const notifyForm = document.getElementById('notifyForm');
                    if (notifyForm) {
                        notifyForm.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        notifyForm.querySelector('input[name="name"]').focus();
                    }
                }
            } else {
                showNotification(data.message || 'Something went wrong', true);
            }
        })
        .catch(error => {
            this.classList.remove('loading');
            console.error('Error:', error);
            showNotification('Network error occurred', true);
        });
    });
});

// Notify form handling
const notifyForm = document.getElementById('notifyForm');
if (notifyForm) {
    notifyForm.addEventListener('submit', function(e) {
        e.preventDefault();
        
        const formData = new FormData(this);
        const button = this.querySelector('button[type="submit"]');
        
        // Add loading state
        button.classList.add('loading');
        button.disabled = true;
        
        fetch('handle_notify.php', {
            method: 'POST',
            body: formData
        })
        .then(response => response.json())
        .then(data => {
            button.classList.remove('loading');
            button.disabled = false;
            
            if (data.success) {
                showNotification('You will be notified when this room becomes vacant!');
                this.reset(); // Clear form
            } else {
                showNotification(data.message || 'Something went wrong', true);
            }
        })
        .catch(error => {
            button.classList.remove('loading');
            button.disabled = false;
            console.error('Error:', error);
            showNotification('Network error occurred', true);
        });
    });
}

// Store current scroll position when page loads (in case user refreshes)
window.addEventListener('beforeunload', function() {
    // This won't help much for this page, but good practice for rooms.php
    sessionStorage.setItem('currentPageScroll', window.scrollY);
});
</script>

</body>
</html>