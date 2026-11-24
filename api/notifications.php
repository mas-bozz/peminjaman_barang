<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

try {
    $db = Database::getConnection();
    $today = date('Y-m-d');

    // Auto-update status to 'Terlambat' for active borrowings that exceeded expected return date
    $db->prepare("UPDATE borrowings SET status = 'Terlambat' WHERE status = 'Dipinjam' AND expected_return_date < ?")
       ->execute([$today]);
    $db->prepare("UPDATE borrowing_details SET status = 'Terlambat' WHERE status = 'Dipinjam' AND borrowing_id IN (SELECT id FROM borrowings WHERE status = 'Terlambat')")
       ->execute();

    // 1. Get Overdue / Due Today Borrowings
    // Items borrowed that have reached or passed their expected return date
    $stmtOverdue = $db->prepare("
        SELECT b.id, b.code, b.borrow_date, b.expected_return_date, b.status, b.purpose,
               br.id as borrower_id, br.name as borrower_name, br.identity_number as borrower_nim, 
               br.phone as borrower_phone, br.email as borrower_email, br.department as borrower_dept,
               DATEDIFF(?, b.expected_return_date) as days_overdue,
               COALESCE(SUM(bd.quantity), 0) as total_quantity,
               GROUP_CONCAT(CONCAT(it.name, ' (', bd.quantity, ' unit)') SEPARATOR ', ') as items_summary
        FROM borrowings b
        JOIN borrowers br ON b.borrower_id = br.id
        LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
        LEFT JOIN items it ON bd.item_id = it.id
        WHERE b.status IN ('Dipinjam', 'Terlambat')
          AND b.expected_return_date <= ?
        GROUP BY b.id
        ORDER BY b.expected_return_date ASC, b.id ASC
    ");
    $stmtOverdue->execute([$today, $today]);
    $overdueLoans = $stmtOverdue->fetchAll();

    // Format overdue metadata for frontend
    foreach ($overdueLoans as &$loan) {
        $days = (int)$loan['days_overdue'];
        if ($days === 0) {
            $loan['overdue_label'] = 'Jatuh tempo HARI INI';
            $loan['urgency'] = 'warning';
        } else {
            $loan['overdue_label'] = "Terlambat {$days} hari";
            $loan['urgency'] = 'danger';
        }
    }
    unset($loan);

    // 2. Get Unavailable Items (Stok Habis / 0 Unit)
    $stmtOutOfStock = $db->query("
        SELECT i.id, i.code, i.name, i.category_id, i.total_stock, i.available_stock,
               i.location, i.condition, c.name as category_name, c.icon as category_icon
        FROM items i
        JOIN categories c ON i.category_id = c.id
        WHERE i.available_stock <= 0
        ORDER BY i.name ASC
    ");
    $outOfStockItems = $stmtOutOfStock->fetchAll();

    // 3. Get Low Stock Items (Stok Menipis: 1 - 2 unit)
    $stmtLowStock = $db->query("
        SELECT i.id, i.code, i.name, i.category_id, i.total_stock, i.available_stock,
               i.location, i.condition, c.name as category_name, c.icon as category_icon
        FROM items i
        JOIN categories c ON i.category_id = c.id
        WHERE i.available_stock > 0 AND i.available_stock <= 2
        ORDER BY i.available_stock ASC, i.name ASC
    ");
    $lowStockItems = $stmtLowStock->fetchAll();

    $overdueCount = count($overdueLoans);
    $outOfStockCount = count($outOfStockItems);
    $totalAlerts = $overdueCount + $outOfStockCount;

    echo json_encode([
        'status' => 'success',
        'data' => [
            'total_alerts' => $totalAlerts,
            'overdue_count' => $overdueCount,
            'out_of_stock_count' => $outOfStockCount,
            'low_stock_count' => count($lowStockItems),
            'overdue_borrowings' => $overdueLoans,
            'out_of_stock_items' => $outOfStockItems,
            'low_stock_items' => $lowStockItems
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'status' => 'error',
        'message' => $e->getMessage()
    ]);
}
