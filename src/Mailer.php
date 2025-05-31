<?php

namespace Akshayffb\Spava;

use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;
use Dotenv\Dotenv;
use Monolog\Logger;

class Mailer
{
    private $mail;
    private $log;

    public function __construct(Logger $log)
    {
        // Load environment variables from .env file
        $dotenv = Dotenv::createImmutable(__DIR__ . '/../');
        $dotenv->load();

        $this->mail = new PHPMailer(true);
        $this->mail->isSMTP();
        $this->mail->Host       = $_ENV['MAIL_HOST'];
        $this->mail->SMTPAuth   = true;
        $this->mail->Username   = $_ENV['MAIL_USERNAME'];
        $this->mail->Password   = $_ENV['MAIL_PASSWORD'];
        $this->mail->SMTPSecure = PHPMailer::ENCRYPTION_STARTTLS;
        $this->mail->Port       = $_ENV['MAIL_PORT'];

        $this->log = $log;
    }


    public function sendEmail($name, $email, $summary)
    {

        $trustedDomains = ['gmail.com', 'yahoo.com', 'outlook.com', 'hotmail.com',];

        $emailDomain = substr(strrchr($email, "@"), 1);
        $userIp = $_SERVER['REMOTE_ADDR'];

        if (!in_array($emailDomain, $trustedDomains)) {
            $this->log->warning("Submission from untrusted email domain: $emailDomain", ['IP' => $userIp, 'Email' => $email,]);
            throw new \Exception("Untrusted email domain. Please use a trusted email provider.");
        }

        try {
            // Email to the recipient
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = "Welcome to Spava – You're Officially on the List";
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Body    = '
                <!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <style>
    body {
      margin: 0;
      background: #f1f5f9;
      font-family: "Segoe UI", sans-serif;
    }
    .container {
      max-width: 600px;
      margin: 40px auto;
      background: #ffffff;
      border-radius: 12px;
      overflow: hidden;
      box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
    }
    .header {
      background: #4f46e5;
      color: #ffffff;
      padding: 30px 40px;
      text-align: center;
    }
    .header img {
      max-width: 120px;
      margin-bottom: 10px;
    }
    .header h1 {
      margin: 0;
      font-size: 24px;
    }
    .body {
      padding: 30px 40px;
      color: #334155;
    }
    .body h2 {
      font-size: 20px;
      margin-bottom: 15px;
    }
    .body p {
      line-height: 1.6;
    }
    .cta {
      margin-top: 30px;
      text-align: center;
    }
    .cta a {
      background: #4f46e5;
      color: #ffffff;
      padding: 12px 24px;
      border-radius: 8px;
      text-decoration: none;
      display: inline-block;
      font-weight: 600;
    }
    .footer {
      padding: 20px 40px;
      font-size: 13px;
      text-align: center;
      color: #94a3b8;
      background: #f8fafc;
    }
    @media (max-width: 600px) {
      .body, .footer, .header {
        padding: 20px;
      }
    }
  </style>
</head>
<body>
  <div class="container">
    <div class="header">
      <!-- Optional logo -->
      <!-- <img src=\"https://yourdomain.com/logo.png\" alt=\"Logo\"> -->
      <h1>You\'re on the List! 🎉</h1>
    </div>
    <div class="body">
      <h2>Hello ' . htmlspecialchars($name) . ',</h2>
      <p>Thank you for signing up for early access to <strong>Spava</strong>! We\'re thrilled to have you with us.</p>
      <p>As we prepare for launch, we\'ll keep you updated with all the exciting developments.</p>
      <div class="cta">
        <a href="https://spava.in">Visit Website</a>
      </div>
    </div>
    <div class="footer">
      You received this email because you joined the waitlist.<br>
      &copy; 2025 Spava. All rights reserved.
    </div>
  </div>
</body>
</html>
            ';

            $this->mail->send();

            // Email to the admin
            $this->mail->clearAddresses();
            $this->mail->addAddress($_ENV['ADMIN_EMAIL']);
            $this->mail->Subject = "Heads Up! {$name} Just Joined Your Waitlist";
            $this->mail->CharSet = 'UTF-8';
            $this->mail->Body = <<<HTML
<html>
  <head>
    <meta charset="UTF-8">
    <style>
      body {
        margin: 0;
        background: #f1f5f9;
        font-family: 'Segoe UI', sans-serif;
      }
      .container {
        max-width: 600px;
        margin: 40px auto;
        background: #ffffff;
        border-radius: 12px;
        overflow: hidden;
        box-shadow: 0 5px 15px rgba(0, 0, 0, 0.05);
      }
      .header {
        background: #4f46e5;
        color: #ffffff;
        padding: 30px 40px;
        text-align: center;
      }
      .header h1 {
        margin: 0;
        font-size: 24px;
      }
      .body {
        padding: 30px 40px;
        color: #334155;
      }
      .body h2 {
        font-size: 20px;
        margin-bottom: 15px;
      }
      .body p {
        line-height: 1.6;
        margin: 10px 0;
      }
      .footer {
        padding: 20px 40px;
        font-size: 13px;
        text-align: center;
        color: #94a3b8;
        background: #f8fafc;
      }
      @media (max-width: 600px) {
        .body, .footer, .header {
          padding: 20px;
        }
      }
    </style>
  </head>
  <body>
    <div class="container">
      <div class="header">
        <h1>New Waitlist Signup</h1>
      </div>
      <div class="body">
        <h2>New Entry Details:</h2>
        <p><strong>Name:</strong> {$name}</p>
        <p><strong>Email:</strong> {$email}</p>
        <p><strong>Summary:</strong><br>{$summary}</p>
      </div>
      <div class="footer">
        This notification was triggered by a new waitlist signup.<br>
        © 2025 Spava. All rights reserved.
      </div>
    </div>
  </body>
</html>
HTML;

            $this->mail->send();
            return 'You have successfully joined the waitlist. Thank you for your interest in Spava!';
        } catch (Exception $e) {
            $this->log->error("Mailer Error: {$e->getMessage()}", [
                'to' => $_ENV['MAIL_TO_ADDRESS'],
                'subject' => 'New Message from Website',
                'body' => "Name: $name\nEmail: $email\nSummary: $summary"
            ]);
            throw new \Exception("Message could not be sent. Mailer Error: {$e->getMessage()}");
            // return 'Oops! Something went wrong while sending your message. Please try again later.';
            // return "Message could not be sent. Mailer Error: {$this->mail->ErrorInfo}";
        }
    }
}
