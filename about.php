<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>About Us</title>
    <style>
      body {
      font-family: 'Segoe UI', sans-serif;
      line-height: 1.6;
      background: linear-gradient(0.95turn, #1e3a76, #4b6faa, #96b5e4, #f1e4b1, #f8c3b4);
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

        .about-us {
            padding: 40px 20px;
        }

        .about-us h2 {
            font-size: 2em;
            color: #007bff;
        }

        .about-us p {
            font-size: 1.1em;
        }

        .features ul {
            list-style-type: disc;
            margin-left: 20px;
        }
    </style>
</head>
<body>
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

    <section class="about-us">
        <h2>About Us</h2>
        <p>We are a dedicated team focused on simplifying the property rental process for landlords and tenants. Our system aims to streamline the entire process, from property management to tenant communications, payments, and more.</p>
        <p>Our system enables landlords to effectively manage their plots by tracking rent payments, monitoring tenant status, and managing lease agreements. For tenants, we provide an easy-to-use platform to manage rental payments and communicate with their landlords directly.</p>
    </section>

    <section class="features">
        <h2>Features of Our System</h2>
        <ul>
            <li>Tenant Management: Register and manage tenant profiles, track lease agreements, and monitor payment status.</li>
            <li>Property Listings: Display available rental plots with detailed information and images.</li>
            <li>Payment Tracking: Keep track of rental payments, due dates, and outstanding balances.</li>
            <li>Communication Tools: Allow landlords and tenants to communicate directly within the platform for updates and inquiries.</li>
            <li>Admin Dashboard: A centralized control panel for administrators to monitor system usage and manage user roles.</li>
        </ul>
    </section>

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
