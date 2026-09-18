<?php
// includes/send_mail.php

require_once __DIR__ . '/PHPMailer/Exception.php';
require_once __DIR__ . '/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

/**
 * Creates and configures a PHPMailer instance using centralized mail_config.php.
 */
function getMailerInstance() {
    $mail = new PHPMailer(true);
    $config = file_exists(__DIR__ . '/mail_config.php') ? require __DIR__ . '/mail_config.php' : [
        'smtp_host'  => 'smtp.gmail.com',
        'smtp_port'  => 587,
        'smtp_user'  => 'judekristian08@gmail.com',
        'smtp_pass'  => 'corm pkyx tbgl olnu',
        'from_email' => 'judekristian08@gmail.com',
        'from_name'  => 'BookWagon'
    ];

    $mail->isSMTP();
    $mail->Host       = $config['smtp_host'];
    $mail->SMTPAuth   = true;
    $mail->Username   = $config['smtp_user'];
    $mail->Password   = $config['smtp_pass'];
    $mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
    $mail->Port       = $config['smtp_port'];

    return [$mail, $config];
}

function sendVerificationEmail($userEmail, $firstName, $token) {
    try {
        list($mail, $config) = getMailerInstance();

        // Recipients
        $mail->setFrom($config['from_email'], 'BookWagon');
        $mail->addAddress($userEmail, $firstName);

        // Verification Link (Using localhost for local dev)
        $verifyLink = "http://localhost/BookwagonNew1/verify_email.php?token=" . urlencode($token);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Verify Your BookWagon Account';
        $mail->Body    = "
            <h2>Welcome to BookWagon, {$firstName}!</h2>
            <p>Thank you for signing up. To complete your registration and activate your account, please click the link below to verify your email address:</p>
            <p><a href='{$verifyLink}' style='display:inline-block; padding:10px 20px; background-color:#f8a100; color:white; text-decoration:none; border-radius:5px;'>Verify My Email</a></p>
            <p>If the button doesn't work, copy and paste this link into your browser:</p>
            <p>{$verifyLink}</p>
            <br>
            <p>Best regards,<br>The BookWagon Team</p>
        ";
        
        $mail->AltBody = "Welcome to BookWagon, {$firstName}!\n\nPlease verify your email by copying and pasting the following link into your browser:\n{$verifyLink}";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$e->getMessage()}");
        return false;
    }
}

function send2FACode($userEmail, $firstName, $code) {
    try {
        list($mail, $config) = getMailerInstance();

        // Recipients
        $mail->setFrom($config['from_email'], 'BookWagon Security');
        $mail->addAddress($userEmail, $firstName);

        // Content
        $mail->isHTML(true);
        $mail->Subject = 'Your BookWagon Verification Code';
        $mail->Body    = "
            <h2>Hi {$firstName},</h2>
            <p>Your Two-Factor Authentication (2FA) code is:</p>
            <h1 style='font-size: 36px; letter-spacing: 5px; color: #f8a100;'>{$code}</h1>
            <p>Please enter this code in the BookWagon app to continue. This code will expire shortly.</p>
            <br>
            <p>If you did not request this code, please ignore this email or secure your account.</p>
            <p>Best regards,<br>The BookWagon Team</p>
        ";
        
        $mail->AltBody = "Hi {$firstName},\n\nYour Two-Factor Authentication (2FA) code is: {$code}\n\nPlease enter this code in the BookWagon app to continue.";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("2FA Message could not be sent. Mailer Error: {$e->getMessage()}");
        return false;
    }
}

function sendPayoutNotificationEmail($userEmail, $firstName, $amount, $transactionId, $isWithdrawal = false) {
    try {
        list($mail, $config) = getMailerInstance();

        $mail->setFrom($config['from_email'], 'BookWagon Finance');
        $mail->addAddress($userEmail, $firstName);

        $mail->isHTML(true);
        $typeLabel = $isWithdrawal ? "Wallet Withdrawal" : "Seller Payout";
        $mail->Subject = "Your {$typeLabel} is Complete!";
        
        $amountFormatted = number_format($amount, 2);
        
        $mail->Body = "
            <h2>Great news, {$firstName}!</h2>
            <p>Your <strong>{$typeLabel}</strong> for <strong>₱{$amountFormatted}</strong> has been successfully processed and transferred to your E-Wallet!</p>
            <p><strong>Transaction ID:</strong> {$transactionId}</p>
            <p>Please check your GCash or Maya account to verify the funds.</p>
            <br>
            <p>Thank you for being part of BookWagon!</p>
            <p>Best regards,<br>The BookWagon Team</p>
        ";
        
        $mail->AltBody = "Great news, {$firstName}!\n\nYour {$typeLabel} for ₱{$amountFormatted} has been successfully processed and transferred to your E-Wallet! Transaction ID: {$transactionId}\n\nPlease check your GCash or Maya account to verify the funds.\n\nBest regards,\nThe BookWagon Team";

        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Payout notification could not be sent. Mailer Error: {$e->getMessage()}");
        return false;
    }
}
?>
