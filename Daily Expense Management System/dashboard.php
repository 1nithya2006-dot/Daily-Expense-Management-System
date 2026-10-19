<?php
// ============================================================
// dashboard.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Dashboard';
$activePage = 'dashboard';
$uid        = currentUserId();
$today      = date('Y-m-d');
$month      = (int) date('m');
$year       = (int) date('Y');

// --- Today total expenses ---
$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND expense_date=?');
$stmt->bind_param('is', $uid, $today);
$stmt->execute();
$todayTotal = (float) $stmt->get_result()->fetch_row()[0];
$stmt->close();

// --- Month totals ---
$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?');
$stmt->bind_param('iii', $uid, $month, $year);
$stmt->execute();
$monthExpenses = (float) $stmt->get_result()->fetch_row()[0];
$stmt->close();

$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?');
$stmt->bind_param('iii', $uid, $month, $year);
$stmt->execute();
$monthIncome = (float) $stmt->get_result()->fetch_row()[0];
$stmt->close();

$monthBalance = $monthIncome - $monthExpenses;

// --- Transaction count ---
$stmt = $conn->prepare('SELECT COUNT(*) FROM expenses WHERE user_id=?');
$stmt->bind_param('i', $uid);
$stmt->execute();
$txCount = (int) $stmt->get_result()->fetch_row()[0];
$stmt->close();

// --- Budget ---
$stmt = $conn->prepare('SELECT amount FROM budgets WHERE user_id=? AND month=? AND year=? LIMIT 1');
$stmt->bind_param('iii', $uid, $month, $year);
$stmt->execute();
$budgetRow    = $stmt->get_result()->fetch_assoc();
$budget       = $budgetRow ? (float) $budgetRow['amount'] : 0;
$budgetLeft   = max(0, $budget - $monthExpenses);
$budgetUsedPct = $budget > 0 ? min(100, round($monthExpenses / $budget * 100, 1)) : 0;
$stmt->close();

// --- Category breakdown (month) ---
$stmt = $conn->prepare(
    'SELECT category, COALESCE(SUM(amount),0) AS total
     FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?
     GROUP BY category ORDER BY total DESC');
$stmt->bind_param('iii', $uid, $month, $year);
$stmt->execute();
$catRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$catLabels = array_column($catRows, 'category');
$catData   = array_column($catRows, 'total');
$topCat    = $catRows[0]['category'] ?? 'N/A';

// --- Daily spending last 7 days ---
$stmt = $conn->prepare(
    'SELECT expense_date, COALESCE(SUM(amount),0) AS total
     FROM expenses WHERE user_id=? AND expense_date >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
     GROUP BY expense_date ORDER BY expense_date ASC');
$stmt->bind_param('i', $uid);
$stmt->execute();
$dailyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$dailyLabels = [];
$dailyData   = [];
for ($i = 6; $i >= 0; $i--) {
    $d = date('Y-m-d', strtotime("-$i days"));
    $dailyLabels[] = date('D', strtotime($d));
    $found = array_filter($dailyRows, fn($r) => $r['expense_date'] === $d);
    $dailyData[]   = $found ? (float) array_values($found)[0]['total'] : 0;
}

// --- Monthly spending last 6 months ---
$monthlyLabels = [];
$monthlyExpData = [];
$monthlyIncData = [];
for ($i = 5; $i >= 0; $i--) {
    $ts   = strtotime("-$i months");
    $m    = (int) date('m', $ts);
    $y    = (int) date('Y', $ts);
    $monthlyLabels[] = date('M Y', $ts);

    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?');
    $stmt->bind_param('iii', $uid, $m, $y);
    $stmt->execute();
    $monthlyExpData[] = (float) $stmt->get_result()->fetch_row()[0];
    $stmt->close();

    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?');
    $stmt->bind_param('iii', $uid, $m, $y);
    $stmt->execute();
    $monthlyIncData[] = (float) $stmt->get_result()->fetch_row()[0];
    $stmt->close();
}

// --- Recent transactions ---
$stmt = $conn->prepare(
    'SELECT title, amount, category, payment_method, expense_date
     FROM expenses WHERE user_id=? ORDER BY expense_date DESC, id DESC LIMIT 8');
$stmt->bind_param('i', $uid);
$stmt->execute();
$recent = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

$csrfToken = generateCsrfToken();
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Dashboard &mdash; DailyExpense</title>
  <link rel="stylesheet" href="/daily-expense-management/assets/css/style.css"/>
  <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
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
      <div class="topbar-title">
        <h2>Dashboard</h2>
        <p><?= date('l, d F Y') ?></p>
      </div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a href="/daily-expense-management/add-expense.php" class="btn btn-primary btn-sm">
          <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
          Add Expense
        </a>
      </div>
    </header>

    <main class="main-content">
      <!-- Summary Cards -->
      <div class="cards-grid">
        <div class="card summary-card card-blue">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
          <div class="card-body">
            <p class="card-label">Today&rsquo;s Expenses</p>
            <h3 class="card-value">&#8377;<?= number_format($todayTotal, 2) ?></h3>
          </div>
        </div>
        <div class="card summary-card card-red">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/><polyline points="17 18 23 18 23 12"/></svg></div>
          <div class="card-body">
            <p class="card-label">Month Expenses</p>
            <h3 class="card-value">&#8377;<?= number_format($monthExpenses, 2) ?></h3>
          </div>
        </div>
        <div class="card summary-card card-green">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
          <div class="card-body">
            <p class="card-label">Month Income</p>
            <h3 class="card-value">&#8377;<?= number_format($monthIncome, 2) ?></h3>
          </div>
        </div>
        <div class="card summary-card <?= $monthBalance >= 0 ? 'card-teal' : 'card-orange' ?>">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <div class="card-body">
            <p class="card-label">Month Balance</p>
            <h3 class="card-value">&#8377;<?= number_format(abs($monthBalance), 2) ?><small><?= $monthBalance >= 0 ? '' : ' deficit' ?></small></h3>
          </div>
        </div>
        <div class="card summary-card card-purple">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 12h-4l-3 9L9 3l-3 9H2"/></svg></div>
          <div class="card-body">
            <p class="card-label">Total Transactions</p>
            <h3 class="card-value"><?= number_format($txCount) ?></h3>
          </div>
        </div>
        <div class="card summary-card card-indigo">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg></div>
          <div class="card-body">
            <p class="card-label">Budget Left</p>
            <h3 class="card-value">&#8377;<?= number_format($budgetLeft, 2) ?></h3>
            <?php if ($budget > 0): ?>
              <p class="card-sub"><?= $budgetUsedPct ?>% used of &#8377;<?= number_format($budget, 2) ?></p>
            <?php else: ?>
              <p class="card-sub"><a href="/daily-expense-management/budget.php">Set a budget</a></p>
            <?php endif; ?>
          </div>
        </div>
      </div>

      <?php if ($budget > 0): ?>
      <!-- Budget Progress -->
      <div class="card budget-card">
        <div class="budget-header">
          <span>Monthly Budget Usage</span>
          <span class="badge <?= $budgetUsedPct >= 100 ? 'badge-danger' : ($budgetUsedPct >= 80 ? 'badge-warning' : 'badge-success') ?>">
            <?= $budgetUsedPct >= 100 ? 'Exceeded!' : ($budgetUsedPct >= 80 ? 'Warning: 80%+' : 'On Track') ?>
          </span>
        </div>
        <div class="progress-bar-wrap">
          <div class="progress-bar" style="width:<?= $budgetUsedPct ?>%"
               data-pct="<?= $budgetUsedPct ?>"></div>
        </div>
        <div class="budget-footer">
          <span>Spent: &#8377;<?= number_format($monthExpenses, 2) ?></span>
          <span>Budget: &#8377;<?= number_format($budget, 2) ?></span>
        </div>
        <?php if ($budgetUsedPct >= 100): ?>
          <div class="alert alert-error mt-2">You have exceeded your monthly budget!</div>
        <?php elseif ($budgetUsedPct >= 80): ?>
          <div class="alert alert-warning mt-2">You have used <?= $budgetUsedPct ?>% of your budget. Be careful!</div>
        <?php endif; ?>
      </div>
      <?php endif; ?>

      <!-- Charts row -->
      <div class="charts-grid">
        <div class="card chart-card">
          <h3 class="card-title">Daily Spending (Last 7 Days)</h3>
          <canvas id="dailyChart"></canvas>
        </div>
        <div class="card chart-card">
          <h3 class="card-title">Category Distribution</h3>
          <?php if (empty($catRows)): ?>
            <div class="empty-state-small">No expenses this month.</div>
          <?php else: ?>
            <canvas id="categoryChart"></canvas>
            <p class="chart-sub">Top: <strong><?= htmlspecialchars($topCat) ?></strong></p>
          <?php endif; ?>
        </div>
      </div>

      <!-- Monthly comparison chart -->
      <div class="card chart-card-full">
        <h3 class="card-title">Income vs Expenses (Last 6 Months)</h3>
        <canvas id="monthlyChart"></canvas>
      </div>

      <!-- Recent Transactions -->
      <div class="card">
        <div class="section-header">
          <h3 class="card-title">Recent Transactions</h3>
          <a href="/daily-expense-management/expense-history.php" class="btn btn-outline btn-sm">View All</a>
        </div>
        <?php if (empty($recent)): ?>
          <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No expenses recorded yet. <a href="/daily-expense-management/add-expense.php">Add your first expense</a>.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead><tr><th>Title</th><th>Category</th><th>Method</th><th>Date</th><th class="text-right">Amount</th></tr></thead>
              <tbody>
                <?php foreach ($recent as $r): ?>
                  <tr>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td><span class="badge badge-cat badge-<?= strtolower($r['category']) ?>"><?= htmlspecialchars($r['category']) ?></span></td>
                    <td><?= htmlspecialchars($r['payment_method']) ?></td>
                    <td><?= date('d M Y', strtotime($r['expense_date'])) ?></td>
                    <td class="text-right text-expense">&#8377;<?= number_format($r['amount'], 2) ?></td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div><!-- /.main-wrapper -->
</div><!-- /.app-wrapper -->

<script>
const dailyLabels  = <?= json_encode($dailyLabels) ?>;
const dailyData    = <?= json_encode($dailyData) ?>;
const catLabels    = <?= json_encode($catLabels) ?>;
const catData      = <?= json_encode($catData) ?>;
const monthLabels  = <?= json_encode($monthlyLabels) ?>;
const monthExpData = <?= json_encode($monthlyExpData) ?>;
const monthIncData = <?= json_encode($monthlyIncData) ?>;
</script>
<script src="/daily-expense-management/assets/js/script.js"></script>
</body>
</html>
