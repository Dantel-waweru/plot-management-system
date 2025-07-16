<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Plots Management System</title>
    <style>
       body {
  font-family: 'Segoe UI', sans-serif;
  line-height: 1.6;
  background-image: 
   
    url("images/xyz3.jpg");
  background-size: cover;
  background-repeat: no-repeat;
  background-position: center;
  color: #212529;
}


        /* Header Styles */
        header {
            background-color: #007bff;
            color: white;
            padding: 10px 20px;
            text-align: center;
        }

        .top-header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            padding: 10px 20px;
            background-color: #0056b3;
        }

        .top-header div {
            color: white;
            font-size: 0.9em;
        }

        .top-header .social-links a {
            color: white;
            margin: 0 10px;
            text-decoration: none;
            font-size: 1.1em;
        }

        .logo h1 {
            margin: 0;
            font-size: 2em;
        }

        .nav-links {
            display: flex;
            justify-content: center;
            padding: 10px 0;
            background-color: #004085;
        }

        .nav-links a {
            color: white;
            text-decoration: none;
            padding: 15px 20px;
            font-size: 1.1em;
            transition: background-color 0.3s;
        }

        .nav-links a:hover {
            background-color: #0056b3;
        }

        /* Hero Section */
        .hero {
            background: url('assets/hero-image.jpg') no-repeat center center/cover;
            color: white;
            text-align: center;
            padding: 100px 20px;
            background-size: cover;
        }

        .hero h1 {
            font-size: 3em;
            margin-bottom: 10px;
        }

        .hero p {
            font-size: 1.2em;
            margin-bottom: 20px;
        }

        .btn-explore {
            background-color: #28a745;
            color: white;
            padding: 10px 20px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1em;
        }

        section {
            padding: 40px 20px;
            margin: 20px 0;
        }

        section h2 {
            font-size: 2em;
            color: #007bff;
            margin-bottom: 10px;
        }

        section p, section ul {
            font-size: 1.1em;
        }

        section ul {
            margin-left: 20px;
            list-style-type: square;
        }

        .roles ul li {
            margin: 10px 0;
        }

        .auth-buttons {
            text-align: center;
            margin: 40px 0;
        }

        .auth-buttons a {
            background-color: #007bff;
            color: white;
            padding: 12px 25px;
            margin: 10px;
            text-decoration: none;
            border-radius: 5px;
            font-size: 1.2em;
        }

        footer {
            background-color: #333;
            color: white;
            text-align: center;
            padding: 10px;
            position: fixed;
            width: 100%;
            bottom: 0;
        }

        footer a {
            color: #007bff;
            text-decoration: none;
        }

        footer a:hover {
            text-decoration: underline;
        }

    </style>
</head>
<body>

    <!-- Top Header Section (Contact and Social Links) -->
    <div class="top-header">
        <div>
            <span>Email: 2danielwaweru@gmail.com</span> | 
            <span>Phone: +254745462796</span>
        </div>
        <div class="social-links">
            <a href="https://www.whatsapp.com" target="_blank">WhatsApp</a>
            <a href="https://www.facebook.com" target="_blank">Facebook</a>
            <a href="https://twitter.com" target="_blank">Twitter</a>
        </div>
    </div>

    <!-- Main Header Section -->
    <header>
        <div class="logo">
            <h1>Plots Management System</h1>
        </div>

        <!-- Navigation Links -->
        <div class="nav-links">
            <a href="index.php">Home</a>
            <a href="about.php">About Us</a>
            <a href="services.php">Services</a>
            <a href="portfolio.php">Portfolio</a>
            <a href="contact.php">Contact</a>
            <a href="login.php">Login</a>
            <a href="register.php">Register</a>
        </div>
    </header>

    <!-- Hero Section -->
    <section class="hero">
        <div class="hero-content">
            <h1>Welcome to the Plots Management System</h1>
            <p>Your go-to platform for renting plots, managing properties, and connecting with landlords and tenants.</p>
            <a href="rooms.php" class="btn-explore">Explore Rooms</a>
        </div>
    </section>

    <!-- Project Overview Section -->
    <section class="overview">
        <h2>Project Overview</h2>
        <p>The Plots Management System is a web-based and mobile-responsive platform designed to simplify the process of renting plots and managing rooms. It connects landlords, tenants, and potential clients by offering an integrated environment for managing plot-related activities.</p>
        <div class="goals">
            <h3>Goals:</h3>
            <ul>
                <li>Simplify rental processes for tenants and landlords.</li>
                <li>Enable digital payment tracking and reminders.</li>
                <li>Provide room availability updates and booking notifications.</li>
                <li>Subscription-based access with different user roles.</li>
            </ul>
        </div>
    </section>

    <!-- User Roles Section -->
    <section class="roles">
        <h2>User Roles & Permissions</h2>
        <ul>
            <li><strong>Landlords:</strong> Manage own plots, view tenant data, receive payment reports.</li>
            <li><strong>Tenants:</strong> View/pay rent, receive receipts, communicate with landlords.</li>
            <li><strong>Potential Clients:</strong> View rooms, book rooms, receive vacancy notifications.</li>
            <li><strong>Agents:</strong> Assist clients with finding rooms, visible 24/7.</li>
            <li><strong>Relocation Workers:</strong> Provide moving services.</li>
            <li><strong>Admin:</strong> Manage system-wide operations, track analytics, view all data.</li>
        </ul>
    </section>

    <!-- Subscription Section -->
    <section class="subscription">
        <h2>Subscription Model</h2>
        <ul>
            <li>Landlords: KES 20/day</li>
            <li>Tenants: KES 20/5 days</li>
            <li>Potential Clients: KES 10/2 days</li>
        </ul>
        <p>Note: Access to the app requires a subscription, except for viewing this index page.</p>
    </section>

    <!-- System Features Section -->
    <section class="features">
        <h2>System Features</h2>
        <ul>
            <li>Role-based dashboards</li>
            <li>Payment reminders and tracking</li>
            <li>Room bookings and notifications</li>
            <li>Chat system for communication</li>
        </ul>
    </section>

    <!-- Login & Register Buttons -->
    <section class="auth-buttons">
        <a href="login.php" class="btn-login">Login</a>
        <a href="register.php" class="btn-register">Register</a>
    </section>

    <!-- Footer -->
    <footer>
        <p>&copy; 2025 Plots Management System | <a href="contact.php">Contact Us</a></p>
    </footer>

</body>
</html>
