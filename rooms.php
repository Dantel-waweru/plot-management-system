<?php
require_once 'includes/db.php';
require_once 'config.php';

// --- Handle filter input ---
$selected_location = $_GET['location'] ?? '';
$selected_price = $_GET['price'] ?? '';
$selected_status = $_GET['status'] ?? '';

// Build WHERE conditions
$where = [];
if ($selected_location !== '') {
    $where[] = "location = '" . mysqli_real_escape_string($conn, $selected_location) . "'";
}
if ($selected_status !== '') {
    $where[] = "status = '" . mysqli_real_escape_string($conn, $selected_status) . "'";
}
if ($selected_price !== '') {
    switch ($selected_price) {
        case 'below-5000':
            $where[] = "price < 5000";
            break;
        case '5000-10000':
            $where[] = "price BETWEEN 5000 AND 10000";
            break;
        case 'above-10000':
            $where[] = "price > 10000";
            break;
    }
}
$where_clause = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// --- Fetch rooms with filter ---
$query = "SELECT * FROM rooms $where_clause ORDER BY location ASC, price ASC, status ASC";
$result = mysqli_query($conn, $query);

$rooms_by_location = [];
while ($row = mysqli_fetch_assoc($result)) {
    $location = $row['location'];
    $rooms_by_location[$location][] = $row;
}     


// --- Get all unique locations for dropdown ---
$location_query = "SELECT DISTINCT location FROM rooms ORDER BY location ASC";
$location_result = mysqli_query($conn, $location_query);
$locations = [];
while ($loc = mysqli_fetch_assoc($location_result)) {
    $locations[] = $loc['location'];
}
$rooms_by_status = [];
while ($ro = mysqli_fetch_assoc($result)) {
    $status = $ro['status'];
    $rooms_by_status[$status][] = $ro;
}
// --- Get all unique status for dropdown ---
$status_query = "SELECT DISTINCT status FROM rooms ORDER BY status ASC";
$status_result = mysqli_query($conn, $status_query);
$status = [];
while ($sta = mysqli_fetch_assoc($status_result)) {
    $status[] = $sta['status'];
}
?>

<!DOCTYPE html>
<html>
<head>
    <script>
function sendReaction(roomId, reactionType) {
    fetch('record_reaction.php', {
        method: 'POST',
        headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
        body: `room_id=${roomId}&reaction=${reactionType}`
    })
    .then(response => response.json())
    .then(data => {
        if (data.status === 'success') {
            alert("Reaction recorded!");
        } else {
            alert("Error: " + data.message);
        }
    });
}
</script>

    <title>Rooms Available</title>
    <style>
        body {
        
      font-family: 'Segoe UI', sans-serif;
      line-height: 1.6;
      background: linear-gradient(0.95turn, #1e3a76, #4b6faa, #96b5e4, #f1e4b1, #f8c3b4);
      color: #212529;
    } 
        h2, h3 { text-align: center; }
        .room-card {
            border: 1px solid #ccc; border-radius: 8px; padding: 15px; margin: 10px 0;
            display: flex; flex-direction: row; align-items: center;
            box-shadow: 0 2px 5px rgba(0,0,0,0.1);
        }
        .room-card img {
            width: 150px; height: 120px; object-fit: cover;
            border-radius: 5px; margin-right: 20px;
        }
        .room-info { flex: 1; }
        .status {
            font-weight: bold; padding: 5px 10px; border-radius: 5px;
        }
        .vacant { background-color: #d4edda; color: #155724; }
        .occupied { background-color: #f8d7da; color: #721c24; }
        .home-button, .filter-form { text-align: center; margin-bottom: 20px; }
        .home-button a, .filter-form button {
            padding: 10px 20px; background: #007bff; color: white;
            text-decoration: none; border-radius: 4px; border: none;
            cursor: pointer;
        }
        .filter-form select {
            padding: 8px; margin: 0 10px;
        }
        .location-heading {
            background: #f4f4f4; padding: 10px;
            border-left: 5px solid #007BFF; margin-top: 30px;
        }
        .reaction-buttons {
            margin-top: 10px;
            display: flex;
            gap: 10px;
        }
        .reaction-buttons button {
            padding: 5px 15px;
            border: 1px solid #ccc;
            border-radius: 5px;
            cursor: pointer;
            background: #f1f1f1;
        }
        .reaction-buttons button:hover {
            background: #e0e0e0;
        }
        .explore-button {
            margin-top: 10px;
            padding: 8px 20px;
            background-color: #28a745;
            color: white;
            border-radius: 4px;
            text-decoration: none;
        }
        .explore-button:hover {
            background-color: #218838;
        }
    </style>
</head>
<body>

<div class="home-button">
    <a href="index.php">← Back to Home</a>
</div>

<h2>Filter Available Rooms</h2>

<form method="GET" class="filter-form">
    <label for="location">Location:</label>
    <select name="location" id="location">
        <option value="">All</option>
        <?php foreach ($locations as $loc): ?>
            <option value="<?php echo htmlspecialchars($loc); ?>" <?php if ($selected_location === $loc) echo 'selected'; ?>>
                <?php echo htmlspecialchars($loc); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <label for="price">Price:</label>
    <select name="price" id="price">
        <option value="">All</option>
        <option value="below-5000" <?php if ($selected_price === 'below-5000') echo 'selected'; ?>>Below Ksh 5,000</option>
        <option value="5000-10000" <?php if ($selected_price === '5000-10000') echo 'selected'; ?>>Ksh 5,000 - 10,000</option>
        <option value="above-10000" <?php if ($selected_price === 'above-10000') echo 'selected'; ?>>Above Ksh 10,000</option>
    </select>
      <label for="status">status:</label>
    <select name="status" id="status">
          <option value="">All</option>
        <?php foreach ($status as $sta): ?>
            <option value="<?php echo htmlspecialchars($sta); ?>" <?php if ($selected_status === $sta) echo 'selected'; ?>>
                <?php echo htmlspecialchars($sta); ?>
            </option>
        <?php endforeach; ?>
    </select>

    <button type="submit">Apply Filter</button>
</form>

<?php if (!empty($rooms_by_location)): ?>
    <?php foreach ($rooms_by_location as $location => $rooms): ?>
        <div class="location-heading">
            <h3><?php echo htmlspecialchars($location); ?></h3>
        </div>

        <?php foreach ($rooms as $room): ?>
            <div class="room-card">

<img src="<?php echo htmlspecialchars($room['image']); ?>" alt="Room Image">
                <div class="room-info">
                    <h4>Room <?php echo htmlspecialchars($room['room_number']); ?></h4>
                    <p>Size: <?php echo htmlspecialchars($room['size']); ?></p>
                    <p>Price: Ksh <?php echo number_format($room['price']); ?></p>
                    <p>Status: 
                        <span class="status <?php echo $room['status'] === 'vacant' ? 'vacant' : 'occupied'; ?>">
                            <?php echo ucfirst($room['status']); ?>
                        </span>
                    </p>
                   <div class="reaction-buttons">
    <button onclick="sendReaction(<?php echo $room['id']; ?>, 'like')">👍 Like</button>
    <button onclick="sendReaction(<?php echo $room['id']; ?>, 'love')">❤️ Love</button>
    <button onclick="sendReaction(<?php echo $room['id']; ?>, 'interested')">😲 Interested</button>
    <a href="explore_room.php?id=<?php echo $room['id']; ?>" class="explore-button">Explore Room</a>
</div>
                    
                </div>
            </div>
        <?php endforeach; ?>
    <?php endforeach; ?>
<?php else: ?>
    <p>No rooms found based on your filters.</p>
<?php endif; ?>

</body>
</html>
