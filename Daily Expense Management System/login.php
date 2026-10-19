<?php
// ============================================================
// login.php
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
        $email    = trim($_POST['email']    ?? '');
        $password = trim($_POST['password'] ?? '');

        if (!$email || !$password) {
            $error = 'Please fill in all fields.';
        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = 'Please enter a valid email address.';
        } else {
            $stmt = $conn->prepare('SELECT id, name, email, password FROM users WHERE email = ? LIMIT 1');
            $stmt->bind_param('s', $email);
            $stmt->execute();
            $result = $stmt->get_result();
            $user   = $result->fetch_assoc();
            $stmt->close();

            if ($user && password_verify($password, $user['password'])) {
                session_regenerate_id(true);
                $_SESSION['user_id']    = $user['id'];
                $_SESSION['user_name']  = $user['name'];
                $_SESSION['user_email'] = $user['email'];
                header('Location: /daily-expense-management/dashboard.php');
                exit;
            } else {
                $error = 'Invalid email or password.';
            }
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
  <title>Login &mdash; DailyExpense</title>
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
    <h2 class="auth-title">Welcome back</h2>
    <p class="auth-subtitle">Sign in to your account</p>

    <?php if ($error): ?>
      <div class="alert alert-error"><?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate id="loginForm">
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>

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
                 placeholder="Enter your password" required autocomplete="current-password"/>
          <button type="button" class="toggle-password" aria-label="Toggle password visibility">
            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
          </button>
        </div>
      </div>

      <button type="submit" class="btn btn-primary btn-full">Sign In</button>
    </form>

    <p class="auth-switch">Don&rsquo;t have an account? <a href="/daily-expense-management/register.php">Create one</a></p>
  </div>
</div>
<script src="/daily-expense-management/assets/js/script.js"></script>
</body>
</html>
