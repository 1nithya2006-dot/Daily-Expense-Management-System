<?php
// ============================================================
// expense-history.php
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$pageTitle  = 'Expense History';
$activePage = 'expense-history';
$base       = '/daily-expense-management';
$uid        = currentUserId();

$categories = ['Food','Transport','Shopping','Education','Medical','Bills','Entertainment','Travel','Other'];
$methods    = ['Cash','UPI','Card','Bank Transfer'];

// Filters
$search     = trim($_GET['search']   ?? '');
$filterCat  = trim($_GET['category'] ?? '');
$filterMeth = trim($_GET['method']   ?? '');
$dateFrom   = trim($_GET['date_from'] ?? '');
$dateTo     = trim($_GET['date_to']   ?? '');
$sort       = ($_GET['sort'] ?? 'newest') === 'oldest' ? 'ASC' : 'DESC';

// Pagination
$perPage = 10;
$page    = max(1, (int)($_GET['page'] ?? 1));
$offset  = ($page - 1) * $perPage;

// Build WHERE
$where  = ['user_id = ?'];
$params = [$uid];
$types  = 'i';

if ($search)    { $where[] = 'title LIKE ?'; $params[] = "%$search%"; $types .= 's'; }
if ($filterCat && in_array($filterCat, $categories, true)) { $where[] = 'category = ?'; $params[] = $filterCat; $types .= 's'; }
if ($filterMeth && in_array($filterMeth, $methods, true))  { $where[] = 'payment_method = ?'; $params[] = $filterMeth; $types .= 's'; }
if ($dateFrom)  { $where[] = 'expense_date >= ?'; $params[] = $dateFrom; $types .= 's'; }
if ($dateTo)    { $where[] = 'expense_date <= ?'; $params[] = $dateTo;   $types .= 's'; }

$whereClause = 'WHERE ' . implode(' AND ', $where);

// Count
$countSQL = "SELECT COUNT(*) FROM expenses $whereClause";
$stmtC    = $conn->prepare($countSQL);
$stmtC->bind_param($types, ...$params);
$stmtC->execute();
$totalRows = (int) $stmtC->get_result()->fetch_row()[0];
$stmtC->close();
$totalPages = max(1, (int) ceil($totalRows / $perPage));

// Fetch
$dataSQL  = "SELECT * FROM expenses $whereClause ORDER BY expense_date $sort, id $sort LIMIT ? OFFSET ?";
$dataTypes = $types . 'ii';
$dataParams = array_merge($params, [$perPage, $offset]);
$stmtD    = $conn->prepare($dataSQL);
$stmtD->bind_param($dataTypes, ...$dataParams);
$stmtD->execute();
$rows = $stmtD->get_result()->fetch_all(MYSQLI_ASSOC);
$stmtD->close();

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
      <div class="topbar-title"><h2>Expense History</h2></div>
      <div class="topbar-actions">
        <button class="btn-theme" id="themeToggle" aria-label="Toggle theme">
          <svg id="iconSun" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
          <svg id="iconMoon" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="display:none"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
        </button>
        <a href="<?= $base ?>/add-expense.php" class="btn btn-primary btn-sm">+ Add Expense</a>
      </div>
    </header>

    <main class="main-content">
      <?php if ($flash): ?><div class="alert alert-success"><?= htmlspecialchars($flash) ?></div><?php endif; ?>
      <?php if ($flashError): ?><div class="alert alert-error"><?= htmlspecialchars($flashError) ?></div><?php endif; ?>

      <!-- Filters -->
      <div class="card filter-card">
        <form method="GET" action="" id="filterForm" class="filter-form">
          <input type="text" name="search" class="form-control" placeholder="Search by title..." value="<?= htmlspecialchars($search) ?>"/>
          <select name="category" class="form-control">
            <option value="">All Categories</option>
            <?php foreach ($categories as $c): ?>
              <option value="<?= $c ?>" <?= $filterCat === $c ? 'selected' : '' ?>><?= $c ?></option>
            <?php endforeach; ?>
          </select>
          <select name="method" class="form-control">
            <option value="">All Methods</option>
            <?php foreach ($methods as $m): ?>
              <option value="<?= $m ?>" <?= $filterMeth === $m ? 'selected' : '' ?>><?= $m ?></option>
            <?php endforeach; ?>
          </select>
          <input type="date" name="date_from" class="form-control" value="<?= htmlspecialchars($dateFrom) ?>" placeholder="From"/>
          <input type="date" name="date_to"   class="form-control" value="<?= htmlspecialchars($dateTo) ?>"   placeholder="To"/>
          <select name="sort" class="form-control">
            <option value="newest" <?= $sort === 'DESC' ? 'selected' : '' ?>>Newest First</option>
            <option value="oldest" <?= $sort === 'ASC'  ? 'selected' : '' ?>>Oldest First</option>
          </select>
          <button type="submit" class="btn btn-primary">Filter</button>
          <a href="<?= $base ?>/expense-history.php" class="btn btn-outline">Reset</a>
          <a href="<?= $base ?>/reports.php?type=expenses&search=<?= urlencode($search) ?>&category=<?= urlencode($filterCat) ?>&method=<?= urlencode($filterMeth) ?>&date_from=<?= urlencode($dateFrom) ?>&date_to=<?= urlencode($dateTo) ?>" class="btn btn-outline">Export CSV</a>
        </form>
      </div>

      <!-- Table -->
      <div class="card">
        <div class="section-header">
          <h3 class="card-title">Transactions (<?= $totalRows ?>)</h3>
        </div>
        <?php if (empty($rows)): ?>
          <div class="empty-state">
            <svg width="48" height="48" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/></svg>
            <p>No expenses found. Try adjusting your filters or <a href="<?= $base ?>/add-expense.php">add a new expense</a>.</p>
          </div>
        <?php else: ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>#</th><th>Title</th><th>Category</th><th>Method</th><th>Date</th><th>Notes</th>
                  <th class="text-right">Amount</th><th class="text-center">Actions</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($rows as $i => $r): ?>
                  <tr>
                    <td><?= $offset + $i + 1 ?></td>
                    <td><?= htmlspecialchars($r['title']) ?></td>
                    <td><span class="badge badge-cat badge-<?= strtolower($r['category']) ?>"><?= htmlspecialchars($r['category']) ?></span></td>
                    <td><?= htmlspecialchars($r['payment_method']) ?></td>
                    <td><?= date('d M Y', strtotime($r['expense_date'])) ?></td>
                    <td class="notes-cell"><?= $r['notes'] ? htmlspecialchars(substr($r['notes'], 0, 40)) . (strlen($r['notes']) > 40 ? '...' : '') : '—' ?></td>
                    <td class="text-right text-expense">&#8377;<?= number_format($r['amount'], 2) ?></td>
                    <td class="text-center">
                      <button class="btn btn-sm btn-outline btn-edit"
                        data-id="<?= $r['id'] ?>"
                        data-title="<?= htmlspecialchars($r['title'], ENT_QUOTES) ?>"
                        data-amount="<?= $r['amount'] ?>"
                        data-category="<?= htmlspecialchars($r['category'], ENT_QUOTES) ?>"
                        data-method="<?= htmlspecialchars($r['payment_method'], ENT_QUOTES) ?>"
                        data-date="<?= $r['expense_date'] ?>"
                        data-notes="<?= htmlspecialchars($r['notes'] ?? '', ENT_QUOTES) ?>"
                        onclick="openEditModal(this)">Edit</button>
                      <button class="btn btn-sm btn-danger"
                        data-id="<?= $r['id'] ?>"
                        onclick="deleteExpense(this, '<?= $csrfToken ?>')">Delete</button>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>

          <!-- Pagination -->
          <?php if ($totalPages > 1): ?>
            <div class="pagination">
              <?php for ($p = 1; $p <= $totalPages; $p++): ?>
                <a href="?<?= http_build_query(array_merge($_GET, ['page' => $p])) ?>"
                   class="page-link <?= $p === $page ? 'active' : '' ?>"><?= $p ?></a>
              <?php endfor; ?>
            </div>
          <?php endif; ?>
        <?php endif; ?>
      </div>
    </main>
  </div>
</div>

<!-- Edit Modal -->
<div class="modal-overlay" id="editModal" style="display:none">
  <div class="modal">
    <div class="modal-header">
      <h3>Edit Expense</h3>
      <button class="modal-close" onclick="closeEditModal()">&times;</button>
    </div>
    <form method="POST" action="<?= $base ?>/actions/update-expense.php" novalidate>
      <input type="hidden" name="csrf_token" value="<?= $csrfToken ?>"/>
      <input type="hidden" name="id" id="editId"/>
      <div class="modal-body">
        <div class="form-group">
          <label>Title</label>
          <input type="text" name="title" id="editTitle" class="form-control" required/>
        </div>
        <div class="form-row">
          <div class="form-group col-1">
            <label>Amount</label>
            <input type="number" name="amount" id="editAmount" class="form-control" step="0.01" min="0.01" required/>
          </div>
          <div class="form-group col-1">
            <label>Date</label>
            <input type="date" name="expense_date" id="editDate" class="form-control" required/>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group col-1">
            <label>Category</label>
            <select name="category" id="editCategory" class="form-control">
              <?php foreach ($categories as $c): ?>
                <option value="<?= $c ?>"><?= $c ?></option>
              <?php endforeach; ?>
            </select>
          </div>
          <div class="form-group col-1">
            <label>Payment Method</label>
            <select name="payment_method" id="editMethod" class="form-control">
              <?php foreach ($methods as $m): ?>
                <option value="<?= $m ?>"><?= $m ?></option>
              <?php endforeach; ?>
            </select>
          </div>
        </div>
        <div class="form-group">
          <label>Notes</label>
          <textarea name="notes" id="editNotes" class="form-control" rows="2"></textarea>
        </div>
      </div>
      <div class="modal-footer">
        <button type="button" class="btn btn-outline" onclick="closeEditModal()">Cancel</button>
        <button type="submit" class="btn btn-primary">Update</button>
      </div>
    </form>
  </div>
</div>

<script src="<?= $base ?>/assets/js/script.js"></script>
</body>
</html>
