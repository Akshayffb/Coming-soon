<?php

namespace Akshayffb\Spava;

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

class RecaptchaVerifier
{
  private $secret;
  private $log;

  public function __construct($secret)
  {
    $this->secret = $secret;
    $this->log = $this->getLogger();
  }

  private function getLogger()
  {
    $logger = new Logger('recaptcha');

    // Determine log level based on environment
    $logLevel = ($_ENV['APP_ENV'] === 'production') ? Logger::ERROR : Logger::DEBUG;

    $logFilePath = '../var/log/spava.in_error.log';

    $logger->pushHandler(new StreamHandler($logFilePath, $logLevel));
    return $logger;
  }

  public function verify($response)
  {
    // Check if the response is empty
    if (empty($response)) {
      $this->log->warning("reCAPTCHA response is empty.");
      return false;
    }

    // Make the API request
    $apiUrl = "https://www.google.com/recaptcha/api/siteverify?secret=" . urlencode($this->secret) . "&response=" . urlencode($response);
    $this->log->info("API URL: $apiUrl");

    $responseContent = file_get_contents($apiUrl);
    if ($responseContent === false) {
      $this->log->error("Failed to fetch API response.");
      return false;
    }

    $this->log->info("API response content: $responseContent");

    $responseKeys = json_decode($responseContent, true);
    if (json_last_error() !== JSON_ERROR_NONE) {
      $this->log->error("Failed to decode API response JSON.");
      return false;
    }

    // Log the entire response for debugging
    $this->log->info("Verification response keys:", $responseKeys);

    $isSuccess = intval($responseKeys['success']) === 1;
    $this->log->info("Verification success: " . ($isSuccess ? 'Yes' : 'No'));

    return $isSuccess;
  }
}
