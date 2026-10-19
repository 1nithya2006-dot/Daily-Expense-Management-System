<?php
// ============================================================
// includes/header.php
// ============================================================
if (session_status() === PHP_SESSION_NONE) { session_start(); }
$pageTitle  = $pageTitle  ?? 'Dashboard';
$activePage = $activePage ?? 'dashboard';
$base       = '/daily-expense-management';
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1.0" />
  <title><?= htmlspecialchars($pageTitle) ?> — DailyExpense</title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css" />
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js" defer></script>
</head>
<body>
<!-- Toast container -->
<div id="toast-container"></div>
