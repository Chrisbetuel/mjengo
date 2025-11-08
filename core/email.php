<?php
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require_once __DIR__ . '/../vendor/autoload.php';

// Gmail SMTP Configuration
define('GMAIL_USERNAME', 'chrisbetuelmlay@gmail.com');
define('GMAIL_PASSWORD', '5544MAC1');

function sendPasswordResetEmail($toEmail, $resetLink) {
    $mail = new PHPMailer(true);

    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USERNAME;
        $mail->Password   = GMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Enable SMTP debugging for troubleshooting (set to 0 for production)
        $mail->SMTPDebug = 0;

        // Recipients
        $mail->setFrom(GMAIL_USERNAME, 'Mjengo Challenge');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Password Reset Request - Mjengo Challenge';
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { text-align: center; color: #1a5276; margin-bottom: 30px; }
                    .button { display: inline-block; padding: 12px 24px; background-color: #27ae60; color: white; text-decoration: none; border-radius: 5px; margin: 20px 0; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1 class='header'>Password Reset Request</h1>
                    <p>Hello,</p>
                    <p>You have requested to reset your password for your Mjengo Challenge account. Click the button below to reset your password:</p>
                    <a href='$resetLink' class='button'>Reset Password</a>
                    <p>If the button doesn't work, copy and paste this link into your browser:</p>
                    <p><a href='$resetLink'>$resetLink</a></p>
                    <p>This link will expire in 24 hours for security reasons.</p>
                    <p>If you didn't request this password reset, please ignore this email.</p>
                    <div class='footer'>
                        <p>&copy; 2024 Mjengo Challenge. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Hello,\n\nYou have requested to reset your password for your Mjengo Challenge account.\n\nClick this link to reset your password: $resetLink\n\nThis link will expire in 24 hours.\n\nIf you didn't request this, please ignore this email.\n\nMjengo Challenge";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        echo "Email sending failed: " . $mail->ErrorInfo;
        return false;
    }
}

function sendEmail($toEmail, $subject, $message) {
    $mail = new PHPMailer(true);

    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USERNAME;
        $mail->Password   = GMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(GMAIL_USERNAME, 'Mjengo Challenge');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { text-align: center; color: #1a5276; margin-bottom: 30px; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1 class='header'>Mjengo Challenge</h1>
                    <div style='white-space: pre-line;'>{$message}</div>
                    <div class='footer'>
                        <p>&copy; 2024 Mjengo Challenge. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = $message;

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        echo "Email sending failed: " . $mail->ErrorInfo;
        return false;
    }
}

function generateRandomPassword($length = 8) {
    $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ!@#$%^&*()';
    $password = '';
    for ($i = 0; $i < $length; $i++) {
        $password .= $characters[rand(0, strlen($characters) - 1)];
    }
    return $password;
}

function sendAutoGeneratedPassword($toEmail, $newPassword) {
    $mail = new PHPMailer(true);

    try {
        // Server settings for Gmail
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = GMAIL_USERNAME;
        $mail->Password   = GMAIL_PASSWORD;
        $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom(GMAIL_USERNAME, 'Mjengo Challenge');
        $mail->addAddress($toEmail);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your New Password - Mjengo Challenge';
        $mail->Body    = "
            <html>
            <head>
                <style>
                    body { font-family: Arial, sans-serif; background-color: #f4f4f4; padding: 20px; }
                    .container { max-width: 600px; margin: 0 auto; background-color: #ffffff; padding: 30px; border-radius: 10px; box-shadow: 0 0 10px rgba(0,0,0,0.1); }
                    .header { text-align: center; color: #1a5276; margin-bottom: 30px; }
                    .password-box { background-color: #f8f9fa; border: 1px solid #dee2e6; border-radius: 5px; padding: 15px; margin: 20px 0; text-align: center; font-family: monospace; font-size: 18px; font-weight: bold; color: #495057; }
                    .footer { margin-top: 30px; font-size: 12px; color: #666; text-align: center; }
                </style>
            </head>
            <body>
                <div class='container'>
                    <h1 class='header'>Your New Password</h1>
                    <p>Hello,</p>
                    <p>Your password has been automatically generated. Please use the following password to log in to your Mjengo Challenge account:</p>
                    <div class='password-box'>{$newPassword}</div>
                    <p><strong>Important:</strong> Please change your password after logging in for security reasons.</p>
                    <p>If you did not request this password reset, please contact support immediately.</p>
                    <div class='footer'>
                        <p>&copy; 2024 Mjengo Challenge. All rights reserved.</p>
                    </div>
                </div>
            </body>
            </html>
        ";
        $mail->AltBody = "Hello,\n\nYour password has been automatically generated. Your new password is: {$newPassword}\n\nPlease change your password after logging in.\n\nMjengo Challenge";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Email sending failed: " . $mail->ErrorInfo);
        return false;
    }
}
?>
