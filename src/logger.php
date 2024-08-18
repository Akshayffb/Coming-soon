<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$appEnv = $_ENV['APP_ENV'] ?? 'production';

$log = new Logger('app');

$logFilePath = '../var/log/spava.in_error.log';

if ($appEnv === 'development') {
  $log->pushHandler(new StreamHandler($logFilePath, Logger::DEBUG));
} else {
  $log->pushHandler(new StreamHandler($logFilePath, Logger::WARNING));
}

return $log;
