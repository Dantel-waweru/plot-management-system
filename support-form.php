<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>Support Inquiry Form</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <style>
                  body {
      font-family: 'Segoe UI', sans-serif;
      line-height: 1.6;
      background: linear-gradient(0.95turn, #1e3a76, #4b6faa, #96b5e4, #f1e4b1, #f8c3b4);
      color: #212529;
    
        }

        .container {
            max-width: 600px;
            margin: 60px auto;
            background: #fff;
            padding: 30px;
            border-radius: 10px;
            box-shadow: 0 4px 12px rgba(0,0,0,0.1);
        }

        h2 {
            color: #007bff;
            text-align: center;
            margin-bottom: 20px;
        }

        form label {
            display: block;
            margin-top: 15px;
            font-weight: bold;
        }

        form input[type="text"],
        form input[type="email"],
        form select,
        form textarea {
            width: 100%;
            padding: 10px;
            margin-top: 5px;
            border: 1px solid #ccc;
            border-radius: 5px;
            font-size: 1em;
        }

        form textarea {
            resize: vertical;
            height: 120px;
        }

        form button {
            margin-top: 20px;
            background: #007bff;
            color: white;
            border: none;
            padding: 12px 20px;
            border-radius: 5px;
            cursor: pointer;
            font-size: 1em;
        }

        form button:hover {
            background: #0056b3;
        }

        .back-link {
            display: block;
            text-align: center;
            margin-top: 20px;
        }

        .back-link a {
            color: #007bff;
            text-decoration: none;
        }

        .back-link a:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>

<div class="container">
    <h2>Submit Support Inquiry</h2>
    <form action="submit_inquiry.php" method="POST">
        <label for="name">Your Name:</label>
        <input type="text" id="name" name="name" required>

        <label for="email">Your Email:</label>
        <input type="email" id="email" name="email" required>

        <label for="subject">Subject:</label>
        <select id="subject" name="subject" required>
            <option value="">-- Select --</option>
            <option value="general">General Inquiry</option>
            <option value="technical">Technical Support</option>
            <option value="billing">Billing/Payment</option>
            <option value="account">Account/Login Issues</option>
        </select>

        <label for="message">Your Message:</label>
        <textarea id="message" name="message" placeholder="Describe your issue or question..." required></textarea>

        <button type="submit">Submit Inquiry</button>
    </form>

    <div class="back-link">
        <a href="contact.php">← Back to Contact Page</a>
    </div>
</div>

</body>
</html>
