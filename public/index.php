<?php

ini_set('display_errors', 0);
ini_set('display_startup_errors', 0);
error_reporting(E_ERROR | E_WARNING | E_PARSE);
ini_set('log_errors', 1);
ini_set('error_log', __DIR__ . '/../logs/app.log');

session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'use_strict_mode' => true
]);

require_once '../vendor/autoload.php';

use Akshayffb\Spava\RecaptchaVerifier;
use Akshayffb\Spava\Mailer;
use Dotenv\Dotenv;
use Monolog\Logger;
use Monolog\Handler\StreamHandler;

$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();

$appEnv = $_ENV['APP_ENV'] ?? 'production';

$log = new Logger('app');
$logLevel = ($appEnv === 'development') ? Logger::DEBUG : Logger::WARNING;
$log->pushHandler(new StreamHandler(__DIR__ . '/../logs/app.log', $logLevel));

$limit = 10;
$timeWindow = 3600;

if (!isset($_SESSION['submission_count'])) {
  $_SESSION['submission_count'] = 0;
}

if (!isset($_SESSION['last_submission']) || time() - $_SESSION['last_submission'] > $timeWindow) {
  $_SESSION['submission_count'] = 0;
  $_SESSION['last_submission'] = time();
}

if ($_SESSION['submission_count'] >= $limit) {
  $log->warning('Rate limit exceeded.');
  $message = 'Rate limit exceeded. Please try again later.';
  $messageType = 'danger';
} else {
  $_SESSION['submission_count'] = ($_SESSION['submission_count'] ?? 0) + 1;
  $_SESSION['last_submission'] = time();

  $recaptchaSecret = $_ENV['RECAPTCHA_SECRET'];
  $recaptchaVerifier = new RecaptchaVerifier($recaptchaSecret, $log);

  $message = '';
  $messageType = 'info';

  if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!empty($_POST['honeypot'])) {
      $log->warning('Honeypot field filled.');
      $message = 'Spam detected. Your submission could not be processed.';
      $messageType = 'danger';
    } elseif ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
      $log->warning('Invalid CSRF token.');
      $message = 'Invalid CSRF token. Please try again.';
      $messageType = 'danger';
    } else {
      $name = htmlspecialchars(trim($_POST['name']));
      $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
      $summary = htmlspecialchars(trim($_POST['summary']));

      if (empty($name)) {
        $message = 'Please enter your name.';
        $messageType = 'danger';
      } elseif (!$email) {
        $message = 'Please enter your email address.';
        $messageType = 'danger';
      } elseif (containsSuspiciousContent($summary)) {
        $message = 'Suspicious content detected. Please remove any promotional material or HTML tags.';
        $messageType = 'danger';
      } else {
        $recaptchaResponse = $_POST['g-recaptcha-response'];

        if (!$recaptchaVerifier->verify($recaptchaResponse)) {
          $log->warning('CAPTCHA verification failed.', [
            'recaptcha_response' => $recaptchaResponse,
            'recaptcha_secret' => $recaptchaSecret
          ]);
          $message = 'CAPTCHA verification failed. Please try again.';
          $messageType = 'danger';
        } else {
          try {
            $mailer = new Mailer($log);
            $mailer->sendEmail($name, $email, $summary);
            $message = 'Thank you for joining the waitlist! We’ll keep you updated as we get closer to our launch.';
            $messageType = 'success';
          } catch (Exception $e) {
            $log->error("Mailer Error: {$e->getMessage()}");
            $message = 'Message could not be sent. Please try again later.';
            $messageType = 'danger';
          }
        }
      }
    }
  }
}

// Generate a new CSRF token for the form
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

function containsSuspiciousContent($text)
{
  $bannedWords = ['buy', 'discount', 'free', 'click', 'visit', 'apply'];
  foreach ($bannedWords as $word) {
    if (stripos($text, $word) !== false) {
      return true;
    }
  }

  $patterns = [
    '/<script\b[^>]*>(.*?)<\/script>/is',
    '/<[^>]+>/i'
  ];
  foreach ($patterns as $pattern) {
    if (preg_match($pattern, $text)) {
      return true;
    }
  }

  return false;
}


?>

<!DOCTYPE html>
<html lang="en">

<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Spava | Coming Soon</title>
  <link rel="apple-touch-icon" sizes="180x180" href="img/favicon_32.png">
  <link rel="icon" href="img/favicon.ico" sizes="16x16" type="image/x-icon">
  <link rel="icon" href="img/favicon_32.png" sizes="32x32" type="image/png">
  <meta name="robots" content="noindex, nofollow">
  <link rel="stylesheet" href="styles.css">
  <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/css/bootstrap.min.css" rel="stylesheet" integrity="sha384-QWTKZyjpPEjISv5WaRU9OFeRpok6YctnYmDr5pNlyT2bRjXh0JMhjY6hW+ALEwIH" crossorigin="anonymous">
  <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.3/dist/js/bootstrap.bundle.min.js" integrity="sha384-YvpcrYf0tY3lHB60NNkmXc5s9fDVZLESaAA55NDzOxhy9GkcIdslK1eN7N6jIeHz" crossorigin="anonymous"></script>
  <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars($_ENV['RECAPTCHA_SITE_KEY']); ?>"></script>
</head>

<body>
  <div class="container">
    <div class="logo">
      <a href="/">
        <img src="img/Spava-logo.png" alt="Spava logo"> Spava
      </a>
    </div>
  </div>
  <div class="container main">
    <div class="text-container">
      <p>Coming Soon</p>
      <h1>All in one student study solutions is almost here!</h1>
      <p>Imagine having everything you need to ace your studies in one place—dynamic materials, detailed answers, interactive quizzes, and a supportive community. That’s what we’re bringing you with <a href="">Spava!</a></p>
      <p>The countdown has begun. Sign up on the right for exclusive early access and stay ahead of the game!</p>
    </div>
    <div class="form-container">
      <form method="post" id="waitlist" action="">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="text" name="honeypot" style="display:none;">

        <div class="mb-3">
          <label for="nameInput" class="form-label">Name</label>
          <input type="text" class="form-control form-control-lg" id="nameInput" name="name" aria-describedby="nameHelp" required>
        </div>
        <div class="mb-3">
          <label for="emailInput" class="form-label">Email address</label>
          <input type="email" class="form-control form-control-lg" id="emailInput" name="email" aria-describedby="emailHelp" required>
        </div>
        <div class="mb-3">
          <label for="summaryInput" class="form-label">Write down any other information you’d like to share...</label>
          <textarea id="summaryInput" class="form-control form-control-lg" name="summary" rows="4" required></textarea>
        </div>
        <div class="d-flex">
          <button class="g-recaptcha btn btn-primary"
            data-sitekey="<?php echo htmlspecialchars($_ENV['RECAPTCHA_SITE_KEY']); ?>"
            data-callback='onSubmit'
            data-action='submit'>Notify me</button>
        </div>
      </form>
    </div>
  </div>
  <div class="container footer">
    <p>© 2024 Spava. All rights reserved.</p>
  </div>
  <!-- Toasts -->
  <?php if ($message): ?>
    <div class="position-fixed top-0 end-0 p-3" style="z-index: 11">
      <div id="liveToast" class="toast align-items-center text-bg-<?php echo $messageType; ?> border-0" role="alert" aria-live="assertive" aria-atomic="true">
        <div class="d-flex">
          <div class="toast-body">
            <?php echo htmlspecialchars($message); ?>
          </div>
          <button type="button" class="btn-close btn-close-white me-2 m-auto" data-bs-dismiss="toast" aria-label="Close"></button>
        </div>
      </div>
    </div>
  <?php endif; ?>
  <script>
    document.addEventListener('DOMContentLoaded', function() {
      const toastElList = document.querySelectorAll('.toast');
      const toastList = [...toastElList].map(toastEl => new bootstrap.Toast(toastEl));
      toastList.forEach(toast => toast.show());
    });

    function onSubmit(token) {
      document.getElementById('g-recaptcha-response').value = token;
      document.getElementById('waitlist').submit();
    }
  </script>
</body>

</html>
