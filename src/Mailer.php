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
        try {
            // Email to the recipient
            $this->mail->setFrom($_ENV['MAIL_FROM_ADDRESS'], $_ENV['MAIL_FROM_NAME']);
            $this->mail->addAddress($email);
            $this->mail->isHTML(true);
            $this->mail->Subject = 'Thank you for joining the waitlist!';
            $this->mail->Body    = "
                <html>
                <head>
                    <style>
                        .email-container {
                            font-family: Arial, sans-serif;
                            padding: 20px;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #f9f9f9;
                        }
                        .header {
                            color: #fff;
                            padding: 10px 20px 0px 20px;
                        }
                        .content {
                            padding: 20px;
                        }
                        .footer {
                            text-align: center;
                            padding: 10px;
                            background-color: #eee;
                            border-radius: 0 0 4px 4px;
                        }
                        h1 {
                            color: #007bff;
                            margin: 0px;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                          <div class='header'>
                        <h1>You're on the Waitlist!</h1>
                    </div>
                    <div class='content'>
                        <p>Hello $name,</p>
                        <p>Thank you for joining the waitlist for Spava! We're thrilled to have you on board and can't wait to introduce you to our all-in-one student study solution.</p>
                        <p>Here's a quick summary of your submission:</p>
                        <p><strong>Additional Note:</strong> $summary</p>
                        <p>We'll keep you updated with all the exciting developments as we approach our launch. Stay tuned for more information!</p>

                    <p>Best regards,<br>Team Spava</p>
                    </div>
                        <div class='footer'>
                            <p>Copyright 2024 Spava. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

            $this->mail->send();

            // Email to the admin
            $this->mail->clearAddresses();
            $this->mail->addAddress($_ENV['ADMIN_EMAIL']);
            $this->mail->Subject = "{$name} - New Waitlist Submission";
            $this->mail->Body    = "
                <html>
                <head>
                    <style>
                        .email-container {
                            font-family: Arial, sans-serif;
                            padding: 20px;
                            border: 1px solid #ddd;
                            border-radius: 4px;
                            max-width: 600px;
                            margin: 0 auto;
                            background-color: #f9f9f9;
                        }
                        .header {
                            color: #fff;
                            padding: 10px 20px 0px 20px;
                        }
                        .content {
                            padding: 20px;
                        }
                        .footer {
                            text-align: center;
                            padding: 10px;
                            background-color: #eee;
                            border-radius: 0 0 4px 4px;
                        }
                        h1 {
                            color: #007bff;
                            margin: 0px;
                        }
                    </style>
                </head>
                <body>
                    <div class='email-container'>
                        <div class='header'>
                        <h1>New Waitlist Submission</h1>
                        </div>
                        <div class='content'>
                            <p><strong>Name:</strong> $name</p>
                            <p><strong>Email:</strong> $email</p>
                            <p><strong>Additional Note:</strong> $summary</p>
                        </div>
                        <div class='footer'>
                            <p>Copyright 2024 Spava. All rights reserved.</p>
                        </div>
                    </div>
                </body>
                </html>
            ";

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
