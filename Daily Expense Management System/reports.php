<?php
// ============================================================
// reports.php  -  CSV export
// ============================================================
require_once __DIR__ . '/config/db.php';
require_once __DIR__ . '/includes/auth.php';
requireLogin();

$uid  = currentUserId();
$type = trim($_GET['type'] ?? 'expenses'); // expenses | income

if ($type === 'income') {
    $filterMonth = trim($_GET['month'] ?? date('Y-m'));
    $parts = explode('-', $filterMonth);
    $y = (int)($parts[0] ?? date('Y'));
    $m = (int)($parts[1] ?? date('m'));

    $stmt = $conn->prepare('SELECT title, amount, category, income_date, notes FROM income WHERE user_id=? AND MONTH(income_date)=? AND YEAR(income_date)=? ORDER BY income_date DESC');
    $stmt->bind_param('iii', $uid, $m, $y);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $filename = 'income_' . $filterMonth . '.csv';
    $headers  = ['Title','Amount','Category','Date','Notes'];
    $mapRow   = fn($r) => [
        $r['title'], $r['amount'], $r['category'],
        date('d-m-Y', strtotime($r['income_date'])), $r['notes'] ?? ''
    ];
} else {
    $search     = trim($_GET['search']    ?? '');
    $filterCat  = trim($_GET['category']  ?? '');
    $filterMeth = trim($_GET['method']    ?? '');
    $dateFrom   = trim($_GET['date_from'] ?? '');
    $dateTo     = trim($_GET['date_to']   ?? '');

    $where  = ['user_id = ?'];
    $params = [$uid];
    $types  = 'i';
    if ($search)    { $where[] = 'title LIKE ?'; $params[] = "%$search%"; $types .= 's'; }
    if ($filterCat) { $where[] = 'category = ?'; $params[] = $filterCat; $types .= 's'; }
    if ($filterMeth){ $where[] = 'payment_method = ?'; $params[] = $filterMeth; $types .= 's'; }
    if ($dateFrom)  { $where[] = 'expense_date >= ?'; $params[] = $dateFrom; $types .= 's'; }
    if ($dateTo)    { $where[] = 'expense_date <= ?'; $params[] = $dateTo;   $types .= 's'; }

    $whereClause = 'WHERE ' . implode(' AND ', $where);
    $stmt = $conn->prepare("SELECT title, amount, category, payment_method, expense_date, notes FROM expenses $whereClause ORDER BY expense_date DESC");
    $stmt->bind_param($types, ...$params);
    $stmt->execute();
    $rows = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);
    $stmt->close();

    $filename = 'expenses_' . date('Y-m-d') . '.csv';
    $headers  = ['Title','Amount','Category','Payment Method','Date','Notes'];
    $mapRow   = fn($r) => [
        $r['title'], $r['amount'], $r['category'], $r['payment_method'],
        date('d-m-Y', strtotime($r['expense_date'])), $r['notes'] ?? ''
    ];
}

// Output CSV
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $filename . '"');
header('Pragma: no-cache');
header('Expires: 0');

$out = fopen('php://output', 'w');
fputs($out, "\xEF\xBB\xBF"); // UTF-8 BOM
fputcsv($out, $headers);
foreach ($rows as $r) {
    fputcsv($out, $mapRow($r));
}
fclose($out);
exit;
