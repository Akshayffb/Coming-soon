<?php

// Start the session with enhanced security
session_start([
  'cookie_secure' => true,
  'cookie_httponly' => true,
  'use_strict_mode' => true
]);

// Rate limiting configuration
$limit = 50; // Emails per hour
$timeWindow = 3600; // 1 hour in seconds

// Reset submission count if the time window has passed
if (!isset($_SESSION['last_submission']) || time() - $_SESSION['last_submission'] > $timeWindow) {
  $_SESSION['submission_count'] = 0;
}

// Check rate limit
if ($_SESSION['submission_count'] >= $limit) {
  die('Rate limit exceeded. Please try again later.');
}

// Update submission count and timestamp
$_SESSION['submission_count'] = ($_SESSION['submission_count'] ?? 0) + 1;
$_SESSION['last_submission'] = time();

// Load Composer autoload
require '../vendor/autoload.php';

use Akshayffb\Spava\Mailer;
use Dotenv\Dotenv;

// Initialize and load environment variables
$dotenv = Dotenv::createImmutable(__DIR__ . '/../');
$dotenv->load();


$message = '';
$messageType = 'info';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
  // CSRF Protection
  if ($_POST['csrf_token'] !== $_SESSION['csrf_token']) {
    die('Invalid CSRF token.');
  }

  // Validate and sanitize input
  $name = htmlspecialchars(trim($_POST['name']));
  $email = filter_var(trim($_POST['email']), FILTER_VALIDATE_EMAIL);
  $summary = htmlspecialchars(trim($_POST['summary']));

  // CAPTCHA Verification (reCAPTCHA v3)
  $recaptchaSecret = getenv('RECAPTCHA_SECRET');
  $recaptchaResponse = $_POST['g-recaptcha-response'];
  $response = file_get_contents("https://www.google.com/recaptcha/api/siteverify?secret=$recaptchaSecret&response=$recaptchaResponse");

  $responseKeys = json_decode($response, true);

  if (intval($responseKeys['success']) !== 1) {
    die('CAPTCHA verification failed. Please try again.');
  }


  if (!$email) {
    $message = 'Invalid email address.';
    $messageType = 'danger';
  } else {
    try {
      $mailer = new Mailer();
      $message = $mailer->sendEmail($name, $email, $summary);
      $messageType = 'success';
    } catch (Exception $e) {
      $message = "Message could not be sent. Mailer Error: {$e->getMessage()}";
      $messageType = 'danger';
    }
  }
}

// Generate a new CSRF token for the form
$token = bin2hex(random_bytes(32));
$_SESSION['csrf_token'] = $token;

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
  <script src="https://www.google.com/recaptcha/api.js?onload=onloadCallback&render=explicit"
    async defer>
  </script>
  <script src="https://www.google.com/recaptcha/api.js?render=<?php echo htmlspecialchars(getenv('RECAPTCHA_SITE_KEY')); ?>"></script>
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
      <form method="post" id="waitlist">
        <input type="hidden" name="csrf_token" value="<?php echo htmlspecialchars($token); ?>">
        <input type="hidden" id="g-recaptcha-response" name="g-recaptcha-response">
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
            data-sitekey="<?php echo htmlspecialchars(getenv('RECAPTCHA_SITE_KEY')); ?>"
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

    let onloadCallback = function() {
      console.log("grecaptcha is ready!");
    };

    function onSubmit(token) {
      document.getElementById('g-recaptcha-response').value = token;
      document.getElementById('waitlist').submit();
    }
  </script>
</body>

</html>