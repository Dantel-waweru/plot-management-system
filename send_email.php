<?php
require_once 'includes/db.php';
session_start();

// Only allow admins or landlords to send email
if ($_SESSION['user_role'] !== 'admin' && $_SESSION['user_role'] !== 'landlord') {
    http_response_code(403);
    echo "Access denied.";
    exit();
}

// Validate and get POST data
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $to = filter_var($_POST['email'], FILTER_VALIDATE_EMAIL);
    $name = htmlspecialchars($_POST['name']);
    $plot = htmlspecialchars($_POST['plot']);
    $room = htmlspecialchars($_POST['room']);

    if (!$to) {
        echo "Invalid email.";
        exit();
    }

    $subject = "Room Booking Notification";
    $message = "Dear $name,\n\nThank you for booking at $plot, Room $room.\nWe have received your request and will get back to you shortly.\n\nRegards,\nPlot Management";

    $headers = "From: no-reply@yourdomain.com\r\n" .
               "Reply-To: no-reply@yo
