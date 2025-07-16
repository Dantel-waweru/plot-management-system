<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Portfolio | Plots Management System</title>
  <style>
    body {
      font-family: 'Segoe UI', sans-serif;
      line-height: 1.6;
      background: linear-gradient(0.95turn, #1e3a76, #4b6faa, #96b5e4, #f1e4b1, #f8c3b4);
      color: #212529;
      margin: 0;
      padding: 0;
    }

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

    .portfolio {
      background-color: #fff;
      border-radius: 10px;
      padding: 40px;
      box-shadow: 0 0 15px rgba(0,0,0,0.1);
      max-width: 1200px;
      margin: auto;
    }

    .portfolio-description {
      text-align: center;
      margin-bottom: 30px;
    }

    .portfolio-images {
      display: flex;
      flex-wrap: wrap;
      gap: 30px;
      justify-content: center;
    }

    .project-card {
      width: 300px;
      background-color: #f8f9fa;
      border-radius: 10px;
      box-shadow: 0 4px 8px rgba(0,0,0,0.1);
      overflow: hidden;
      text-align: left;
    }

    .project-card img {
      width: 100%;
      height: 200px;
      object-fit: cover;
    }

    .project-content {
      padding: 15px;
    }

    .project-content h3 {
      margin-top: 0;
      color: #007bff;
    }

    .project-content p {
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

  <header>
    <div class="logo">
      <h1>Plots Management System</h1>
    </div>
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

  <section class="portfolio">
    <div class="portfolio-description">
      <h2>Our Project Portfolio</h2>
      <p>Here are some featured projects that reflect our commitment to innovative and effective property management solutions.</p>
    </div>

    <div class="portfolio-images">
      <div class="project-card">
        <img src="assets/project1.jpg" alt="Rental Plot Management System">
        <div class="project-content">
          <h3>Rental Plot Manager</h3>
          <p>A web-based platform for tracking tenants, payments, and vacant plots in real time.</p>
          <p><strong>Technologies:</strong> PHP, MySQL, HTML, CSS, JavaScript</p>
        </div>
      </div>

      <div class="project-card">
        <img src="assets/project2.jpg" alt="Landlord Dashboard">
        <div class="project-content">
          <h3>Landlord Dashboard</h3>
          <p>An interactive dashboard for property owners to monitor occupancy and billing history.</p>
          <p><strong>Features:</strong> Role-based access, analytics, mobile-responsive</p>
        </div>
      </div>

      <div class="project-card">
        <img src="assets/project3.jpg" alt="Tenant Self-Service Portal">
        <div class="project-content">
          <h3>Tenant Self-Service Portal</h3>
          <p>Enables tenants to view rent balances, make payments, and lodge maintenance requests.</p>
          <p><strong>Tech Stack:</strong> JavaScript, Bootstrap, REST APIs</p>
        </div>
      </div>
    </div>
  </section>

  <section class="auth-buttons">
    <a href="login.php" class="btn-login">Login</a>
    <a href="register.php" class="btn-register">Register</a>
  </section>

  <footer>
    <p>&copy; 2025 Plots Management System | <a href="contact.php">Contact Us</a></p>
  </footer>

</body>
</html>
