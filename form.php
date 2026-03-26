<?php
header('Content-Type: application/json');

// ✅ Validate required fields
if (
    empty($_POST["name"]) ||
    empty($_POST["email"]) ||
    empty($_POST["phone"]) ||
    empty($_POST["message"]) ||
    empty($_POST["preferred_datetime"]) ||
    empty($_POST["user_timezone"])
) {
    echo json_encode(['status' => 'error', 'message' => 'All fields are required.']);
    exit;
}

// ✅ Sanitize inputs
$name = htmlspecialchars(trim($_POST["name"]));
$email = filter_var(trim($_POST["email"]), FILTER_SANITIZE_EMAIL);
$mobilenumber = htmlspecialchars(trim($_POST["phone"]));
$message = htmlspecialchars(trim($_POST["message"]));
$user_timezone = htmlspecialchars(trim($_POST["user_timezone"]));
$preferred_datetime = trim($_POST["preferred_datetime"]);

// ✅ Validate email format
if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
    echo json_encode(['status' => 'error', 'message' => 'Invalid email address.']);
    exit;
}

// ✅ Convert user local datetime → Canada (Toronto) Time
try {
    $userTz = new DateTimeZone($user_timezone);
    $canadaTz = new DateTimeZone("America/Toronto");

    // Create date object with user's timezone
    $date = new DateTime($preferred_datetime, $userTz);

    // Convert to Canada timezone
    $date->setTimezone($canadaTz);

    // Format final date
    $formatted_datetime = $date->format("d-m-Y g:i A");
} catch (Exception $e) {
    $formatted_datetime = "Invalid datetime";
}

// ✅ Load PHPMailer
require 'PHPMailer/PHPMailerAutoload.php';
$mail = new PHPMailer(true);

try {
    // SMTP Configuration
    $mail->isSMTP();
    $mail->Host       = 'smtp.zoho.com';
    $mail->SMTPAuth   = true;
    // $mail->Username   = 'noreply@lintcloud.com';
    // $mail->Password   = 'L!ntCl0ud@77';
    $mail->SMTPSecure = 'ssl';
    $mail->Port       = 465;

    // From & To
    $mail->setFrom('noreply@lintcloud.com', 'LintCloud');
    $mail->addAddress('fayas@lintcloud.com');

    // Email to Admin
    $mail->isHTML(true);
    $mail->Subject = 'New Contact Form Submission';
    $mail->Body = "
        <html>
            <head>
                <style>
                    table {
                        width: 100%;
                        border-collapse: collapse;
                    }
                    th, td {
                        padding: 10px;
                        border: 1px solid #ddd;
                        text-align: left;
                    }
                    th { background-color: #f9f9f9; }
                    h2 { text-align: center; color: #333; }
                </style>
            </head>
            <body>
                <h2>Contact Form Details</h2>
                <table>
                    <tr><th>Name</th><td>{$name}</td></tr>
                    <tr><th>Email</th><td>{$email}</td></tr>
                    <tr><th>Mobile No</th><td>{$mobilenumber}</td></tr>
                    <tr><th>User Timezone</th><td>{$user_timezone}</td></tr>
                    <tr><th>Preferred Date & Time (Converted to Canada)</th><td>{$formatted_datetime}</td></tr>
                    <tr><th>Message</th><td>{$message}</td></tr>
                </table>
            </body>
        </html>
    ";

    // Send to Admin
    if ($mail->send()) {

        // Send thank-you email to User
        $mail->clearAddresses();
        $mail->addAddress($email);
        $mail->Subject = 'Thank You for Contacting AIS Data Solution';
        $mail->Body = "
        <!DOCTYPE html>
        <html lang='en'>
        <head>
            <meta charset='UTF-8'>
            <meta name='viewport' content='width=device-width, initial-scale=1.0'>
            <title>AIS Data Solution</title>
            <style>
                body {
                    font-family: 'Roboto', sans-serif;
                    background-color: #fefefe;
                    margin: 0;
                    padding: 20px;
                    color: #333;
                }
                .container {
                    max-width: 600px;
                    margin: auto;
                    background-color: #e6f6f1;
                    padding: 20px;
                    border-radius: 8px;
                    box-shadow: 0 0 10px rgba(0,0,0,0.1);
                }
                h2 { text-align: center; color: black; }
                hr { border: 0.5px solid #0a0a0a; margin: 15px 0; }
                p { font-size: 13px; line-height: 1.6; }
                .regards {
                    text-align: right;
                    margin-top: 20px;
                    font-weight: bold;
                    font-size: 13px;
                }
            </style>
        </head>
        <body>
            <div class='container'>
                <h2>AIS Data Solution</h2>
                <hr/>
                <p><strong>Dear {$name},</strong></p>
                <p>Thank you for reaching out to AIS Data Solution. We appreciate your inquiry and will get back to you as soon as possible.</p>
                <p class='regards'>Best Regards,<br>TEAM AIS Data Solution</p>
            </div>
        </body>
        </html>
        ";

        if ($mail->send()) {
            echo json_encode(['status' => 'success', 'message' => 'Message sent successfully.']);
        } else {
            echo json_encode(['status' => 'warning', 'message' => 'Main mail sent but thank-you mail failed.']);
        }

    } else {
        echo json_encode(['status' => 'error', 'message' => 'Unable to send main email.']);
    }

} catch (Exception $e) {
    echo json_encode(['status' => 'error', 'message' => 'Mailer Error: ' . $mail->ErrorInfo]);
}
?>
