<?php
// ============================================================
// includes/sidebar.php
// ============================================================
$base = '/daily-expense-management';
$nav = [
    ['href' => "$base/dashboard.php",        'icon' => 'grid',         'label' => 'Dashboard',       'key' => 'dashboard'],
    ['href' => "$base/add-expense.php",       'icon' => 'plus-circle',  'label' => 'Add Expense',     'key' => 'add-expense'],
    ['href' => "$base/expense-history.php",   'icon' => 'list',         'label' => 'Expense History', 'key' => 'expense-history'],
    ['href' => "$base/income.php",            'icon' => 'trending-up',  'label' => 'Income',          'key' => 'income'],
    ['href' => "$base/budget.php",            'icon' => 'target',       'label' => 'Budget',          'key' => 'budget'],
    ['href' => "$base/statistics.php",        'icon' => 'bar-chart-2',  'label' => 'Statistics',      'key' => 'statistics'],
    ['href' => "$base/reports.php",           'icon' => 'file-text',    'label' => 'Reports',         'key' => 'reports'],
];
?>
<div class="sidebar" id="sidebar">
  <div class="sidebar-header">
    <div class="brand">
      <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><path d="M12 6v6l4 2"/></svg>
      <span>DailyExpense</span>
    </div>
    <button class="sidebar-close" id="sidebarClose" aria-label="Close menu">&#x2715;</button>
  </div>

  <nav class="sidebar-nav">
    <?php foreach ($nav as $item): ?>
      <a href="<?= $item['href'] ?>"
         class="nav-item<?= ($activePage === $item['key']) ? ' active' : '' ?>"
         aria-label="<?= $item['label'] ?>">
        <?= svgIcon($item['icon']) ?>
        <span><?= $item['label'] ?></span>
      </a>
    <?php endforeach; ?>
  </nav>

  <div class="sidebar-footer">
    <div class="user-info">
      <div class="avatar"><?= strtoupper(substr(currentUserName(), 0, 1)) ?></div>
      <div class="user-details">
        <strong><?= currentUserName() ?></strong>
        <small><?= htmlspecialchars($_SESSION['user_email'] ?? '') ?></small>
      </div>
    </div>
    <a href="<?= $base ?>/logout.php" class="btn-logout" title="Logout">
      <?= svgIcon('log-out') ?>
    </a>
  </div>
</div>
<div class="sidebar-overlay" id="sidebarOverlay"></div>

<?php
function svgIcon(string $name): string {
    $icons = [
        'grid'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/></svg>',
        'plus-circle' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="16"/><line x1="8" y1="12" x2="16" y2="12"/></svg>',
        'list'        => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>',
        'trending-up' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><polyline points="23 6 13.5 15.5 8.5 10.5 1 18"/><polyline points="17 6 23 6 23 12"/></svg>',
        'target'      => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><circle cx="12" cy="12" r="6"/><circle cx="12" cy="12" r="2"/></svg>',
        'bar-chart-2' => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>',
        'file-text'   => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>',
        'log-out'     => '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>',
    ];
    return $icons[$name] ?? '';
}
?>
