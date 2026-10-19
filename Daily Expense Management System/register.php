<?php
// ============================================================
// register.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';

if (!empty($_SESSION['user_id'])) {
    header('Location: /daily-expense-management/dashboard.php');
    exit;
}

$error   = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!verifyCsrfToken($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid request. Please try again.';
    } else {
        $name     = trim($_POST['name']     ?? '');
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');
        $confirm  = trim($_POST['confirm']  ?? '');

        if (!$name || !$email || !$password || !$confirm) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } elseif (strlen($password) < 6) {
            $error = 'Password must be at least 6 characters.';
        } elseif ($password !== $confirm) {
            $error = 'Passwords do not match.';
        } else {
            // Check if email exists
            $stmt = $conn->prepare('SELECT id FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $stmt->store_result();
            if ($stmt->num_rows > 0) {
                $error = 'An account with this email already exists.';
            } else {
                $stmt->close();
                $hash = password_hash($password, PASSWORD_BCRYPT);
                $stmt2 = $conn->prepare('INSERT INTO users (name, email, password) VALUES (?, ?, ?)');
                $stmt2->bind_param('sss', $name, $email, $hash);
                if ($stmt2->execute()) {
                    $success = 'Account created! You can now <a href="/daily-expense-management/login.php">sign in</a>.';
                } else {
                    $error = 'Registration failed. Please try again.';
                }
                $stmt2->close();
            }
            if (isset($stmt) && $stmt instanceof mysqli_stmt) { $stmt->close(); }
        }
    }
}

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Register &mdash; DailyExpense</title>
  <link rel="stylesheet" href="/daily-expense-management/assets/css/style.css"/>
</head>
<body class="auth-body">
<div id="toast-container"></div>
<div class="auth-wrapper">
  <div class="auth-card">
    <div class="auth-brand">
      <svg width="36" height="36" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      <h1>DailyExpense</h1>
    </div>
    <h2 class="auth-title">Create account</h2>
    <p class="auth-subtitle">Start tracking your finances today</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= $error ?></div>
    <?php endif; ?>
    <?php if ($success): ?>
      <div class="alert alert-success"><?= $success ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate id="registerForm">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>

      <div class="form-group">
        <label for="name">Full name</label>
        <input type="text" id="name" name="name" class="form-control"
               value="<?= htmlspecialchars($_POST['name'] ?? '') ?>"
               placeholder="John Doe" required autocomplete="name"/>
      </div>

      <div class="form-group">
        <label for="email">Email address</label>
        <input type="email" id="email" name="email" class="form-control"
               value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
               placeholder="you@example.com" required autocomplete="email"/>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-password-wrapper">
          <input type="password" id="password" name="password" class="form-control"
                 placeholder="At least 6 characters" required autocomplete="new-password"/>
          <button type="button" class="toggle-password" aria-label="Toggle password visibility">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <div class="form-group">
        <label for="confirm">Confirm password</label>
        <input type="password" id="confirm" name="confirm" class="form-control"
               placeholder="Repeat password" required autocomplete="new-password"/>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Create Account</button>
    </form>

    <p class="auth-switch">Already have an account? <a href="/daily-expense-management/login.php">Sign in</a></p>
  </div>
</div>
<script src="/daily-expense-management/assets/js/script.js"></script>
</body>
</html>
