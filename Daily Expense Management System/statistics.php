<?php
// ============================================================
// statistics.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Statistics';
$activePage = 'statistics';
$base       = '/daily-expense-management';
$uid        = currentUserId();

$selMonth = trim($_GET['month'] ?? date('Y-m'));
$parts    = explode('-', $selMonth);
$year     = (int)($parts[0] ?? date('Y'));
$month    = (int)($parts[1] ?? date('m'));
$monthLabel = date('F Y', mktime(0,0,0,$month,1,$year));

// --- Month totals ---
$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$monthExp = (float)$stmt->get_result()->fetch_row()[0]; $stmt->close();

$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$monthInc = (float)$stmt->get_result()->fetch_row()[0]; $stmt->close();

$netBalance = $monthInc - $monthExp;

// --- Category breakdown ---
$stmt = $conn->prepare('SELECT category, COALESCE(SUM(amount),0) AS total, COUNT(*) AS cnt FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? GROUP BY category ORDER BY total DESC');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$catRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// --- Payment method breakdown ---
$stmt = $conn->prepare('SELECT payment_method, COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? GROUP BY payment_method ORDER BY total DESC');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$methRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// --- Daily expenses for selected month ---
$stmt = $conn->prepare('SELECT expense_date, COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? GROUP BY expense_date ORDER BY expense_date ASC');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$dailyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// --- Weekly expenses ---
$stmt = $conn->prepare('SELECT WEEK(expense_date,1) AS wk, COALESCE(SUM(amount),0) AS total FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? GROUP BY wk ORDER BY wk ASC');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$weeklyRows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC); $stmt->close();

// --- Highest & Lowest expense ---
$stmt = $conn->prepare('SELECT title,amount,expense_date FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? ORDER BY amount DESC LIMIT 1');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$highestExp = $stmt->get_result()->fetch_assoc(); $stmt->close();

$stmt = $conn->prepare('SELECT title,amount,expense_date FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=? ORDER BY amount ASC LIMIT 1');
$stmt->bind_param('iii',$uid,$month,$year); $stmt->execute();
$lowestExp = $stmt->get_result()->fetch_assoc(); $stmt->close();

// --- Monthly comparison last 6 months ---
$mLabels = []; $mExpData = []; $mIncData = [];
for ($i = 5; $i >= 0; $i--) {
    $ts = strtotime("-$i months", mktime(0,0,0,$month,1,$year));
    $m  = (int)date('m',$ts); $y = (int)date('Y',$ts);
    $mLabels[] = date('M Y',$ts);
    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM expenses WHERE user_id=? AND MONTH(expense_date)=? AND YEAR(expense_date)=?');
    $stmt->bind_param('iii',$uid,$m,$y); $stmt->execute();
    $mExpData[] = (float)$stmt->get_result()->fetch_row()[0]; $stmt->close();
    $stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?');
    $stmt->bind_param('iii',$uid,$m,$y); $stmt->execute();
    $mIncData[] = (float)$stmt->get_result()->fetch_row()[0]; $stmt->close();
}

$catLabels  = array_column($catRows,  'category');
$catData    = array_column($catRows,  'total');
$methLabels = array_column($methRows, 'payment_method');
$methData   = array_column($methRows, 'total');
$dailyLabels = array_map(fn($r)=>date('d',strtotime($r['expense_date'])), $dailyRows);
$dailyData   = array_column($dailyRows, 'total');
$wkLabels    = array_map(fn($r)=>'Week '.$r['wk'], $weeklyRows);
$wkData      = array_column($weeklyRows,'total');
?>
<!DOCTYPE html>
<html lang="en" data-theme="light">
<head>
  <meta charset="UTF-8"/>
  <meta name="viewport" content="width=device-width, initial-scale=1.0"/>
  <title>Statistics &mdash; DailyExpense</title>
  <link rel="stylesheet" href="<?= $base ?>/assets/css/style.css"/>
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
      <div class="topbar-title"><h2>Statistics</h2></div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
      </div>
    </header>

    <main class="main-content">
      <!-- Month selector -->
      <div class="card filter-card">
        <form method="GET" class="filter-form">
          <label>Month:</label>
          <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($selMonth) ?>"/>
          <button type="submit" class="btn btn-primary">View</button>
        </form>
      </div>

      <!-- Summary Cards -->
      <div class="cards-grid">
        <div class="card summary-card card-green">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg></div>
          <div class="card-body"><p class="card-label">Total Income</p><h3 class="card-value">&#8377;<?= number_format($monthInc,2) ?></h3></div>
        </div>
        <div class="card summary-card card-red">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 18 13.5 8.5 8.5 13.5 1 6"/></svg></div>
          <div class="card-body"><p class="card-label">Total Expenses</p><h3 class="card-value">&#8377;<?= number_format($monthExp,2) ?></h3></div>
        </div>
        <div class="card summary-card <?= $netBalance>=0?'card-teal':'card-orange' ?>">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="12" y1="1" x2="12" y2="23"/><path d="M17 5H9.5a3.5 3.5 0 0 0 0 7h5a3.5 3.5 0 0 1 0 7H6"/></svg></div>
          <div class="card-body"><p class="card-label">Net Balance</p><h3 class="card-value">&#8377;<?= number_format(abs($netBalance),2) ?><small><?= $netBalance<0?' deficit':'' ?></small></h3></div>
        </div>
      </div>

      <!-- Highest / Lowest -->
      <div class="cards-grid cards-grid-2">
        <div class="card summary-card card-red">
          <div class="card-body"><p class="card-label">Highest Expense</p>
            <?php if ($highestExp): ?>
              <h3 class="card-value">&#8377;<?= number_format($highestExp['amount'],2) ?></h3>
              <p class="card-sub"><?= htmlspecialchars($highestExp['title']) ?> &mdash; <?= date('d M Y',strtotime($highestExp['expense_date'])) ?></p>
            <?php else: ?><p>No data</p><?php endif; ?>
          </div>
        </div>
        <div class="card summary-card card-green">
          <div class="card-body"><p class="card-label">Lowest Expense</p>
            <?php if ($lowestExp): ?>
              <h3 class="card-value">&#8377;<?= number_format($lowestExp['amount'],2) ?></h3>
              <p class="card-sub"><?= htmlspecialchars($lowestExp['title']) ?> &mdash; <?= date('d M Y',strtotime($lowestExp['expense_date'])) ?></p>
            <?php else: ?><p>No data</p><?php endif; ?>
          </div>
        </div>
      </div>

      <!-- Charts -->
      <div class="charts-grid">
        <div class="card chart-card">
          <h3 class="card-title">Daily Expenses — <?= htmlspecialchars($monthLabel) ?></h3>
          <?php if (empty($dailyRows)): ?><div class="empty-state-small">No data.</div>
          <?php else: ?><canvas id="dailyChart"></canvas><?php endif; ?>
        </div>
        <div class="card chart-card">
          <h3 class="card-title">Category Breakdown</h3>
          <?php if (empty($catRows)): ?><div class="empty-state-small">No data.</div>
          <?php else: ?><canvas id="categoryChart"></canvas><?php endif; ?>
        </div>
        <div class="card chart-card">
          <h3 class="card-title">Weekly Expenses</h3>
          <?php if (empty($weeklyRows)): ?><div class="empty-state-small">No data.</div>
          <?php else: ?><canvas id="weeklyChart"></canvas><?php endif; ?>
        </div>
        <div class="card chart-card">
          <h3 class="card-title">Payment Method Breakdown</h3>
          <?php if (empty($methRows)): ?><div class="empty-state-small">No data.</div>
          <?php else: ?><canvas id="methodChart"></canvas><?php endif; ?>
        </div>
      </div>

      <!-- Monthly comparison -->
      <div class="card chart-card-full">
        <h3 class="card-title">Income vs Expenses (Last 6 Months)</h3>
        <canvas id="monthlyChart"></canvas>
      </div>

      <!-- Category Table -->
      <?php if (!empty($catRows)): ?>
      <div class="card">
        <h3 class="card-title">Category-wise Breakdown</h3>
        <div class="table-responsive">
          <table class="table">
            <thead><tr><th>Category</th><th>Transactions</th><th class="text-right">Total</th><th>% of Expenses</th></tr></thead>
            <tbody>
              <?php foreach ($catRows as $r): ?>
                <tr>
                  <td><span class="badge badge-cat badge-<?= strtolower($r['category']) ?>"><?= htmlspecialchars($r['category']) ?></span></td>
                  <td><?= $r['cnt'] ?></td>
                  <td class="text-right">&#8377;<?= number_format($r['total'],2) ?></td>
                  <td><?= $monthExp > 0 ? round($r['total']/$monthExp*100,1) : 0 ?>%</td>
                </tr>
              <?php endforeach; ?>
            </tbody>
          </table>
        </div>
      </div>
      <?php endif; ?>
    </main>
  </div>
</div>

<script>
const dailyLabels  = <?= json_encode($dailyLabels) ?>;
const dailyData    = <?= json_encode($dailyData) ?>;
const catLabels    = <?= json_encode($catLabels) ?>;
const catData      = <?= json_encode($catData) ?>;
const wkLabels     = <?= json_encode($wkLabels) ?>;
const wkData       = <?= json_encode($wkData) ?>;
const methLabels   = <?= json_encode($methLabels) ?>;
const methData     = <?= json_encode($methData) ?>;
const monthLabels  = <?= json_encode($mLabels) ?>;
const monthExpData = <?= json_encode($mExpData) ?>;
const monthIncData = <?= json_encode($mIncData) ?>;
const PAGE = 'statistics';
</script>
<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
