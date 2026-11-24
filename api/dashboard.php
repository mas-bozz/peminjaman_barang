<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();

    // Auto-update overdue status for active borrowings where expected_return_date < CURRENT_DATE
    $today = date('Y-m-d');
    $db->prepare("UPDATE borrowings SET status = 'Terlambat' WHERE status = 'Dipinjam' AND expected_return_date < ?")
       ->execute([$today]);
    $db->prepare("UPDATE borrowing_details SET status = 'Terlambat' WHERE status = 'Dipinjam' AND borrowing_id IN (SELECT id FROM borrowings WHERE status = 'Terlambat')")
       ->execute();

    // 1. Metric Counts
    $stmtItems = $db->query("SELECT COUNT(*) as total_items, COALESCE(SUM(total_stock), 0) as total_units, COALESCE(SUM(available_stock), 0) as available_units FROM items");
    $itemStats = $stmtItems->fetch();

    $stmtBorrowed = $db->query("SELECT COALESCE(SUM(quantity), 0) as borrowed_units FROM borrowing_details WHERE status IN ('Dipinjam', 'Terlambat')");
    $borrowedUnits = (int)$stmtBorrowed->fetch()['borrowed_units'];

    $stmtBorrowers = $db->query("SELECT COUNT(*) as total_borrowers FROM borrowers WHERE status = 'Aktif'");
    $totalBorrowers = (int)$stmtBorrowers->fetch()['total_borrowers'];

    $stmtActiveLoans = $db->query("SELECT COUNT(*) as active_loans FROM borrowings WHERE status IN ('Dipinjam', 'Terlambat')");
    $activeLoans = (int)$stmtActiveLoans->fetch()['active_loans'];

    $stmtOverdueLoans = $db->query("SELECT COUNT(*) as overdue_loans FROM borrowings WHERE status = 'Terlambat'");
    $overdueLoans = (int)$stmtOverdueLoans->fetch()['overdue_loans'];

    $stmtOutOfStock = $db->query("SELECT COUNT(*) as out_of_stock FROM items WHERE available_stock <= 0");
    $outOfStockCount = (int)$stmtOutOfStock->fetch()['out_of_stock'];

    // 2. Category Distribution
    $stmtCats = $db->query("
        SELECT c.id, c.name, c.icon, COUNT(i.id) as item_count, COALESCE(SUM(i.total_stock), 0) as total_units
        FROM categories c
        LEFT JOIN items i ON c.id = i.category_id
        GROUP BY c.id, c.name, c.icon
        ORDER BY item_count DESC
    ");
    $categories = $stmtCats->fetchAll();

    // 3. Low stock warning (available_stock <= 2)
    $stmtLowStock = $db->query("
        SELECT i.id, i.code, i.name, i.available_stock, i.total_stock, c.name as category_name
        FROM items i
        JOIN categories c ON i.category_id = c.id
        WHERE i.available_stock <= 2
        ORDER BY i.available_stock ASC
        LIMIT 6
    ");
    $lowStockItems = $stmtLowStock->fetchAll();

    // 4. Recent Active Borrowings (Multi-item aware)
    $stmtRecentBorrowings = $db->query("
        SELECT b.id, b.code, b.borrow_date, b.expected_return_date, b.status, b.purpose,
               br.name as borrower_name, br.identity_number as borrower_nim, br.phone as borrower_phone, br.department,
               COALESCE(SUM(bd.quantity), 0) as total_quantity,
               COUNT(bd.id) as item_types_count,
               GROUP_CONCAT(CONCAT(it.name, ' (', bd.quantity, ' unit)') SEPARATOR ', ') as item_names
        FROM borrowings b
        JOIN borrowers br ON b.borrower_id = br.id
        LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
        LEFT JOIN items it ON bd.item_id = it.id
        WHERE b.status IN ('Dipinjam', 'Terlambat')
        GROUP BY b.id
        ORDER BY b.borrow_date DESC
        LIMIT 6
    ");
    $recentActive = $stmtRecentBorrowings->fetchAll();

    // 5. Recent Activity Log (all statuses)
    $stmtActivity = $db->query("
        SELECT b.id, b.code, b.borrow_date, b.expected_return_date, b.actual_return_date, b.status,
               br.name as borrower_name,
               COALESCE(SUM(bd.quantity), 0) as total_quantity,
               GROUP_CONCAT(CONCAT(it.name, ' (', bd.quantity, ' unit)') SEPARATOR ', ') as item_names
        FROM borrowings b
        JOIN borrowers br ON b.borrower_id = br.id
        LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
        LEFT JOIN items it ON bd.item_id = it.id
        GROUP BY b.id
        ORDER BY b.id DESC
        LIMIT 8
    ");
    $recentActivity = $stmtActivity->fetchAll();

    echo json_encode([
        'status' => 'success',
        'data' => [
            'metrics' => [
                'total_items' => (int)$itemStats['total_items'],
                'total_units' => (int)$itemStats['total_units'],
                'available_units' => (int)$itemStats['available_units'],
                'borrowed_units' => $borrowedUnits,
                'total_borrowers' => $totalBorrowers,
                'active_loans' => $activeLoans,
                'overdue_loans' => $overdueLoans,
                'out_of_stock' => $outOfStockCount
            ],
            'categories' => $categories,
            'low_stock_items' => $lowStockItems,
            'recent_active' => $recentActive,
            'recent_activity' => $recentActivity
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
