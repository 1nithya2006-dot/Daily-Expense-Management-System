<?php
// ============================================================
// income.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Income';
$activePage = 'income';
$base       = '/daily-expense-management';
$uid        = currentUserId();

$categories = ['Salary','Allowance','Freelance','Gift','Other'];

// Filters
$filterMonth = trim($_GET['month'] ?? date('Y-m'));
$parts       = explode('-', $filterMonth);
$filterYear  = (int)($parts[0] ?? date('Y'));
$filterMon   = (int)($parts[1] ?? date('m'));

// Month total
$stmt = $conn->prepare('SELECT COALESCE(SUM(amount),0) FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=?');
$stmt->bind_param('iii', $uid, $filterMon, $filterYear);
$stmt->execute();
$monthTotal = (float) $stmt->get_result()->fetch_row()[0];
$stmt->close();

// Fetch records
$stmt = $conn->prepare('SELECT * FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=? ORDER BY income_date DESC, id DESC');
$stmt->bind_param('iii', $uid, $filterMon, $filterYear);
$stmt->execute();
$rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
$stmt->close();

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
      <div class="topbar-title"><h2>Income Management</h2></div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <button class="btn btn-primary btn-sm" onclick="openAddIncomeModal()">+ Add Income</button>
      </div>
    </header>

    <main class="main-content">
      <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
      <?php if ($flashError): ?><div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

      <!-- Summary Card -->
      <div class="cards-grid cards-grid-2">
        <div class="card summary-card card-green">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg></div>
          <div class="card-body">
            <p class="card-label">Total Income (<?= date('F Y', mktime(0,0,0,$filterMon,1,$filterYear)) ?>)</p>
            <h3 class="card-value">&#8377;<?= number_format($monthTotal, 2) ?></h3>
          </div>
        </div>
        <div class="card summary-card card-blue">
          <div class="card-icon"><svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="2" y="5" width="20" height="14" rx="2"/><line x1="2" y1="10" x2="22" y2="10"/></svg></div>
          <div class="card-body">
            <p class="card-label">Number of Records</p>
            <h3 class="card-value"><?= count($rows) ?></h3>
          </div>
        </div>
      </div>

      <!-- Filter by month -->
      <div class="card filter-card">
        <form method="GET" class="filter-form">
          <input type="month" name="month" class="form-control" value="<?= htmlspecialchars($filterMonth) ?>"/>
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= $base ?>/income.php" class="btn btn-outline">Reset</a>
          <a href="<?= $base ?>/reports.php?type=income&month=<?= urlencode($filterMonth) ?>" class="btn btn-outline">Export CSV</a>
        </form>
      </div>

      <!-- Income Table -->
      <div class="card">
        <?php if (empty($rows)): ?>
          <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/></svg>
            <p>No income recorded for this month. <button class="btn-link" onclick="openAddIncomeModal()">Add one now</button>.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr><th>#</th><th>Title</th><th>Category</th><th>Date</th><th>Notes</th><th class="text-right">Amount</th><th class="text-center">Actions</th></tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $i => $r): ?>
                  <tr>
                    <td><?= $i + 1 ?></td>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td><span class="badge badge-success"><?= htmlspecialchars($r['category']) ?></span></td>
                    <td><?= date('d M Y', strtotime($r['income_date'])) ?></td>
                    <td><?= $r['notes'] ? htmlspecialchars(substr($r['notes'],0,40)) . (strlen($r['notes'])>40?'...':'') : '—' ?></td>
                    <td class="text-right text-income">&#8377;<?= number_format($r['amount'], 2) ?></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline btn-edit"
                        data-id="<?= $r['id'] ?>"
                        data-title="<?= htmlspecialchars($r['title'],ENT_QUOTES) ?>"
                        data-amount="<?= $r['amount'] ?>"
                        data-category="<?= htmlspecialchars($r['category'],ENT_QUOTES) ?>"
                        data-date="<?= $r['income_date'] ?>"
                        data-notes="<?= htmlspecialchars($r['notes']??'',ENT_QUOTES) ?>"
                        onclick="openEditIncomeModal(this)">Edit</button>
                      <button class="btn btn-sm btn-danger" data-id="<?= $r['id'] ?>"
                        onclick="deleteIncome(this, '<?= $csrfToken ?>')">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Add Income Modal -->
<div class="modal-overlay" id="addIncomeModal" style="display:none">
  <div class="modal">
    <div class="modal-header"><h3>Add Income</h3><button class="modal-close" onclick="closeModal('addIncomeModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/actions/save-income.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>
      <div class="modal-body">
        <div class="form-group"><label>Title</label><input type="text" name="title" class="form-control" required/></div>
        <div class="form-row">
          <div class="form-group col-1"><label>Amount (&#8377;)</label><input type="number" name="amount" class="form-control" step="0.01" min="0.01" required/></div>
          <div class="form-group col-1"><label>Date</label><input type="date" name="income_date" class="form-control" value="<?= date('Y-m-d') ?>" required/></div>
        </div>
        <div class="form-group"><label>Category</label>
          <select name="category" class="form-control">
            <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Notes (optional)</label><textarea name="notes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('addIncomeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Save</button>
      </div>
    </form>
  </div>
</div>

<!-- Edit Income Modal -->
<div class="modal-overlay" id="editIncomeModal" style="display:none">
  <div class="modal">
    <div class="modal-header"><h3>Edit Income</h3><button class="modal-close" onclick="closeModal('editIncomeModal')">&times;</button></div>
    <form method="POST" action="<?= $base ?>/actions/update-income.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>
      <input type="hidden" name="id" id="editIncomeId"/>
      <div class="modal-body">
        <div class="form-group"><label>Title</label><input type="text" name="title" id="editIncomeTitle" class="form-control" required/></div>
        <div class="form-row">
          <div class="form-group col-1"><label>Amount (&#8377;)</label><input type="number" name="amount" id="editIncomeAmount" class="form-control" step="0.01" min="0.01" required/></div>
          <div class="form-group col-1"><label>Date</label><input type="date" name="income_date" id="editIncomeDate" class="form-control" required/></div>
        </div>
        <div class="form-group"><label>Category</label>
          <select name="category" id="editIncomeCategory" class="form-control">
            <?php foreach ($categories as $c): ?><option value="<?= $c ?>"><?= $c ?></option><?php endforeach; ?>
          </select>
        </div>
        <div class="form-group"><label>Notes</label><textarea name="notes" id="editIncomeNotes" class="form-control" rows="2"></textarea></div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeModal('editIncomeModal')">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
