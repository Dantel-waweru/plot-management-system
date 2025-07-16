<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Services</title>
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

        .services {
            padding: 40px 20px;
        }

        .services h2 {
            font-size: 2em;
            color: #007bff;
        }

        .services ul {
            font-size: 1.1em;
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

    <section class="services">
        <h2>Our Services</h2>
        <p>At Plots Management, we offer comprehensive services designed to streamline property rental management. Our platform is crafted to meet the diverse needs of landlords and tenants. Below are the services we provide:</p>
        <ul>
            <li><strong>Plot Rental Management:</strong> We manage the entire rental process, including listing, availability tracking, and payment processing.</li>
            <li><strong>Tenant Communication Platform:</strong> A centralized communication channel to ensure landlords and tenants are always in touch.</li>
            <li><strong>Real-time Booking and Availability Tracking:</strong> Our platform provides real-time availability of rental plots, making booking easy for tenants.</li>
            <li><strong>Payment Management and Notifications:</strong> Manage payments, generate receipts, and send reminders for upcoming dues directly through our system.</li>
            <li><strong>Customizable Subscription Plans:</strong> Flexible pricing plans for landlords and tenants, allowing them to choose based on their needs and usage.</li>
            <li><strong>Data Analytics:</strong> Access insightful reports on rental trends, payment history, and plot performance to help you make informed decisions.</li>
            <li><strong>Automated Contracts:</strong> Automatically generate and manage rental agreements, ensuring that both parties are protected by clear, legally sound contracts.</li>
            <li><strong>Mobile and Web App Access:</strong> Both landlords and tenants can manage their accounts, communicate, and handle transactions from anywhere via our mobile and web apps.</li>
            <li><strong>Customer Support:</strong> 24/7 customer support to help resolve any issues promptly, ensuring a smooth experience for both landlords and tenants.</li>
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
