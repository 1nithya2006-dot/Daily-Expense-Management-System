<?php
// ============================================================
// add-expense.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Add Expense';
$activePage = 'add-expense';
$base       = '/daily-expense-management';

$categories = ['Food','Transport','Shopping','Education','Medical','Bills','Entertainment','Travel','Other'];
$methods    = ['Cash','UPI','Card','Bank Transfer'];

$flash = '';
if (!empty($_SESSION['flash'])) { $flash = $_SESSION['flash']; unset($_SESSION['flash']); }

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title><?= htmlspecialchars($pageTitle) ?> &mdash; DailyExpense</title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css"/>
</head>
<body>
<div id="toast-container"></div>
<div class="app-wrapper">
  <?php require __DIR__ . '/includes/sidebar.php'; ?>
  <div class="main-wrapper">
    <header class="topbar">
      <button class="btn-hamburger" id="sidebarToggle" aria-label="Open menu">
        <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
          <line x1="3" y1="6" x2="21" y2="6"/><line x1="3" y1="12" x2="21" y2="12"/><line x1="3" y1="18" x2="21" y2="18"/>
        </svg>
      </button>
      <div class="topbar-title"><h2>Add Expense</h2></div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
      </div>
    </header>

    <main class="main-content">
      <div class="form-page-wrapper">
        <div class="card form-card">
          <h3 class="card-title">Record a New Expense</h3>
          <?php if ($flash): ?>
            <div class="alert alert-success"><?= htmlspecialchars($flash) ?></div>
          <?php endif; ?>

          <form id="expenseForm" method="POST" action="<?= $base ?>/actions/save-expense.php" novalidate>
            <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>

            <div class="form-row">
              <div class="form-group col-2">
                <label for="title">Expense Title <span class="required">*</span></label>
                <input type="text" id="title" name="title" class="form-control"
                       placeholder="e.g. Lunch, Bus fare" required maxlength="200"/>
              </div>
              <div class="form-group col-1">
                <label for="amount">Amount (&#8377;) <span class="required">*</span></label>
                <input type="number" id="amount" name="amount" class="form-control"
                       placeholder="0.00" step="0.01" min="0.01" required/>
              </div>
            </div>

            <div class="form-row">
              <div class="form-group col-1">
                <label for="category">Category <span class="required">*</span></label>
                <select id="category" name="category" class="form-control" required>
                  <option value="">Select category</option>
                  <?php foreach ($categories as $c): ?>
                    <option value="<?= $c ?>"><?= $c ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-1">
                <label for="payment_method">Payment Method <span class="required">*</span></label>
                <select id="payment_method" name="payment_method" class="form-control" required>
                  <option value="">Select method</option>
                  <?php foreach ($methods as $m): ?>
                    <option value="<?= $m ?>"><?= $m ?></option>
                  <?php endforeach; ?>
                </select>
              </div>
              <div class="form-group col-1">
                <label for="expense_date">Date <span class="required">*</span></label>
                <input type="date" id="expense_date" name="expense_date" class="form-control"
                       value="<?= date('Y-m-d') ?>" required max="<?= date('Y-m-d') ?>"/>
              </div>
            </div>

            <div class="form-group">
              <label for="notes">Notes (optional)</label>
              <textarea id="notes" name="notes" class="form-control" rows="3"
                        placeholder="Any additional details..."></textarea>
            </div>

            <div class="form-actions">
              <a href="<?= $base ?>/dashboard.php" class="btn btn-outline">Cancel</a>
              <button type="submit" class="btn btn-primary">Save Expense</button>
            </div>
          </form>
        </div>
      </div>
    </main>
  </div>
</div>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
