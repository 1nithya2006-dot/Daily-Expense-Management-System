<?php
// ============================================================
// budget.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Budget';
$activePage = 'budget';
$base       = '/daily-expense-management';
$uid        = currentUserId();

$selMonth = trim($_GET['month'] ?? date('Y-m'));
$parts    = explode('-', $selMonth);
$year     = (int)($parts[0] ?? date('Y'));
$month    = (int)($parts[1] ?? date('m'));
$monthLabel = date('F Y', mktime(0,0,0,$month,1,$year));

// Fetch budget for selected month
$stmt = $conn->prepare('SELECT * FROM budgets WHERE user_id=? AND month=? AND year=? LIMIT 1');
$stmt->bind_param('iii',$uid,$month,$year);
$stmt->execute();
$budgetRow = $stmt->get_result()->fetch_assoc();
$stmt->close();
$budget = $budgetRow ? (float)$budgetRow['amount'] : 0;

// Fetch total expenses for selected month
$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?');
$stmt->bind_param('iii',$uid,$month,$year);
$stmt->execute();
$spent = (float)$stmt->get_result()->fetch_row()[0];
$stmt->close();

$remaining    = max(0, $budget - $spent);
$usedPct      = $budget > 0 ? min(100, round($spent/$budget*100,1)) : 0;
$exceeded     = $spent > $budget && $budget > 0;
$warning      = !$exceeded && $usedPct >= 80 && $budget > 0;

// Flash
$flash      = $_SESSION['flash']       ?? '';
$flashError = $_SESSION['flash_error'] ?? '';
unset($_SESSION['flash'], $_SESSION['flash_error']);

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
      <div class="topbar-title"><h2>Budget Management</h2></div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
      </div>
    </header>

    <main class="main-content">
      <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
      <?php if ($flashError): ?><div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

      <!-- Month selector -->
      <div class="card filter-card">
        <form method="GET" class="filter-form">
          <label>Select Month:</label>
          <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($selMonth) ?>"/>
          <button type="submit" class="btn btn-primary">View</button>
        </form>
      </div>

      <!-- Set / Update Budget Form -->
      <div class="card form-card">
        <h3 class="card-title"><?= $budgetRow ? 'Update' : 'Set' ?> Budget for <?= htmlspecialchars($monthLabel) ?></h3>
        <form method="POST" action="<?= $base ?>/actions/save-budget.php" novalidate>
          <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>
          <input type="hidden" name="month" value="<?= $month ?>"/>
          <input type="hidden" name="year"  value="<?= $year ?>"/>
          <div class="form-row">
            <div class="form-group col-2">
              <label for="budgetAmount">Monthly Budget Amount (&#8377;)</label>
              <input type="number" id="budgetAmount" name="amount" class="form-control"
                     value="<?= $budget > 0 ? $budget : '' ?>"
                     placeholder="e.g. 20000" step="0.01" min="1" required/>
            </div>
            <div class="form-group col-1 form-actions-inline">
              <button type="submit" class="btn btn-primary"><?= $budgetRow ? 'Update Budget' : 'Save Budget' ?></button>
            </div>
          </div>
        </form>
      </div>

      <?php if ($budget > 0): ?>
      <!-- Budget Usage -->
      <div class="cards-grid">
        <div class="card summary-card card-indigo">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></div>
          <div class="card-body"><p class="card-label">Monthly Budget</p><h3 class="card-value">&#8377;<?= number_format($budget,2) ?></h3></div>
        </div>
        <div class="card summary-card card-red">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg></div>
          <div class="card-body"><p class="card-label">Amount Spent</p><h3 class="card-value">&#8377;<?= number_format($spent,2) ?></h3></div>
        </div>
        <div class="card summary-card <?= $exceeded ? 'card-orange' : 'card-green' ?>">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <div class="card-body"><p class="card-label">Remaining</p><h3 class="card-value">&#8377;<?= number_format($remaining,2) ?></h3></div>
        </div>
        <div class="card summary-card card-purple">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="22 12 18 12 15 21 9 3 6 12 2 12"/></svg></div>
          <div class="card-body"><p class="card-label">Used</p><h3 class="card-value"><?= $usedPct ?>%</h3></div>
        </div>
      </div>

      <div class="card budget-card">
        <div class="budget-header">
          <span>Budget Usage — <?= htmlspecialchars($monthLabel) ?></span>
          <span class="badge <?= $exceeded ? 'badge-danger' : ($warning ? 'badge-warning' : 'badge-success') ?>">
            <?= $exceeded ? 'Budget Exceeded!' : ($warning ? 'Warning: 80%+' : 'On Track') ?>
          </span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar <?= $exceeded ? 'progress-danger' : ($warning ? 'progress-warning' : '') ?>"
               style="width:<?= $usedPct ?>%"></div>
        </div>
        <div class="budget-footer">
          <span>Spent: &#8377;<?= number_format($spent,2) ?></span>
          <span><?= $usedPct ?>% of &#8377;<?= number_format($budget,2) ?></span>
        </div>
        <?php if ($exceeded): ?>
          <div class="alert alert-error mt-2">You have exceeded your budget by &#8377;<?= number_format($spent-$budget,2) ?>!</div>
        <?php elseif ($warning): ?>
          <div class="alert alert-warning mt-2">You have used <?= $usedPct ?>% of your budget. Remaining: &#8377;<?= number_format($remaining,2) ?>.</div>
        <?php endif; ?>
      </div>
      <?php else: ?>
      <div class="empty-state card">
        <svg width="56" height="56" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>
        <p>No budget set for <?= htmlspecialchars($monthLabel) ?>. Use the form above to set one.</p>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
