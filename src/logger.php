<?php

use Monolog\Logger;
use Monolog\Handler\StreamHandler;

require_once '../vendor/autoload.php';
$dotenv = Dotenv\Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$appEnv = $_ENV['APP_ENV'] ?? 'production';

$log = new Logger('app');

if ($appEnv === 'development') {
  $log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::DEBUG));
} else {
  $log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', Logger::WARNING));
}

return $log;
