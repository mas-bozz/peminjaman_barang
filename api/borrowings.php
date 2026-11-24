<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = Database::getConnection();

try {
    // Auto check overdue status for active borrowings where expected_return_date < CURRENT_DATE
    $today = date('Y-m-d');
    $db->prepare("UPDATE borrowings SET status = 'Terlambat' WHERE status = 'Dipinjam' AND expected_return_date < ?")
       ->execute([$today]);
    $db->prepare("UPDATE borrowing_details SET status = 'Terlambat' WHERE status = 'Dipinjam' AND borrowing_id IN (SELECT id FROM borrowings WHERE status = 'Terlambat')")
       ->execute();

    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("
                SELECT b.*, 
                       br.name as borrower_name, br.identity_number as borrower_nim, br.phone as borrower_phone, 
                       br.email as borrower_email, br.department as borrower_dept
                FROM borrowings b
                JOIN borrowers br ON b.borrower_id = br.id
                WHERE b.id = ?
            ");
            $stmt->execute([$id]);
            $trans = $stmt->fetch();

            if (!$trans) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Transaksi tidak ditemukan']);
                exit;
            }

            // Fetch all items for this borrowing from borrowing_details
            $stmtDetails = $db->prepare("
                SELECT bd.*, it.name as item_name, it.code as item_code, it.location as item_location, c.name as category_name
                FROM borrowing_details bd
                JOIN items it ON bd.item_id = it.id
                JOIN categories c ON it.category_id = c.id
                WHERE bd.borrowing_id = ?
                ORDER BY bd.id ASC
            ");
            $stmtDetails->execute([$id]);
            $trans['items'] = $stmtDetails->fetchAll();

            echo json_encode(['status' => 'success', 'data' => $trans]);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? 'all'); // all, Dipinjam, Kembali, Terlambat

        $sql = "
            SELECT b.*, 
                   br.name as borrower_name, br.identity_number as borrower_nim, br.phone as borrower_phone, br.department as borrower_dept,
                   COALESCE(SUM(bd.quantity), 0) as total_quantity,
                   COUNT(bd.id) as total_item_types,
                   GROUP_CONCAT(CONCAT(it.name, ' (', bd.quantity, ' unit)') SEPARATOR ', ') as items_summary
            FROM borrowings b
            JOIN borrowers br ON b.borrower_id = br.id
            LEFT JOIN borrowing_details bd ON b.id = bd.borrowing_id
            LEFT JOIN items it ON bd.item_id = it.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (b.code LIKE ? OR br.name LIKE ? OR br.identity_number LIKE ? OR it.name LIKE ? OR it.code LIKE ?)";
            $wildcard = "%$search%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($status !== '' && $status !== 'all') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $sql .= " GROUP BY b.id ORDER BY b.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $loans = $stmt->fetchAll();

        // Also fetch individual item details for each loan to make client rendering ultra clean
        foreach ($loans as &$loan) {
            $stmtDet = $db->prepare("
                SELECT bd.*, it.name as item_name, it.code as item_code
                FROM borrowing_details bd
                JOIN items it ON bd.item_id = it.id
                WHERE bd.borrowing_id = ?
            ");
            $stmtDet->execute([$loan['id']]);
            $loan['details'] = $stmtDet->fetchAll();
        }

        echo json_encode(['status' => 'success', 'data' => $loans, 'total' => count($loans)]);
        exit;
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $action = $input['action'] ?? '';

        // 1. Catat Transaksi Peminjaman Baru (Multi-Item)
        if ($action === 'create') {
            $borrowerId = (int)($input['borrower_id'] ?? 0);
            $borrowDate = trim($input['borrow_date'] ?? date('Y-m-d'));
            $expectedReturnDate = trim($input['expected_return_date'] ?? '');
            $purpose = trim($input['purpose'] ?? '');
            $notes = trim($input['notes'] ?? '');

            // Support items array or legacy single item
            $itemsList = $input['items'] ?? [];
            if (empty($itemsList) && isset($input['item_id'])) {
                $itemsList[] = [
                    'item_id' => trim($input['item_id']),
                    'quantity' => (int)($input['quantity'] ?? 1)
                ];
            }

            if ($borrowerId <= 0 || empty($itemsList) || $expectedReturnDate === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Peminjam, minimal 1 barang, dan estimasi tanggal kembali wajib diisi!']);
                exit;
            }

            if ($expectedReturnDate < $borrowDate) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Tanggal kembali tidak boleh sebelum tanggal peminjaman!']);
                exit;
            }

            $db->beginTransaction();

            try {
                // Check Borrower Status
                $stmtB = $db->prepare("SELECT id, name, status FROM borrowers WHERE id = ?");
                $stmtB->execute([$borrowerId]);
                $borrower = $stmtB->fetch();
                if (!$borrower) {
                    throw new Exception("Data peminjam tidak ditemukan.");
                }
                if ($borrower['status'] !== 'Aktif') {
                    throw new Exception("Peminjam berstatus Nonaktif, tidak diizinkan meminjam barang.");
                }

                // Validate each item and check stock
                $validatedItems = [];
                foreach ($itemsList as $idx => $it) {
                    $itemId = trim($it['item_id'] ?? '');
                    $qty = (int)($it['quantity'] ?? 1);

                    if ($itemId === '' || $qty < 1) {
                        throw new Exception("Data barang ke-" . ($idx + 1) . " tidak valid.");
                    }

                    // Check Item Stock
                    $stmtI = $db->prepare("SELECT id, name, available_stock FROM items WHERE id = ?");
                    $stmtI->execute([$itemId]);
                    $itemRow = $stmtI->fetch();
                    if (!$itemRow) {
                        throw new Exception("Barang '$itemId' tidak ditemukan.");
                    }

                    if ($itemRow['available_stock'] <= 0) {
                        throw new Exception("Barang '{$itemRow['name']}' sedang TIDAK TERSEDIA (Stok 0 / Habis)!");
                    }

                    if ($itemRow['available_stock'] < $qty) {
                        throw new Exception("Stok barang '{$itemRow['name']}' tidak mencukupi! Sisa tersedia hanya {$itemRow['available_stock']} unit (diminta {$qty} unit).");
                    }

                    $validatedItems[] = [
                        'item_id' => $itemId,
                        'name' => $itemRow['name'],
                        'quantity' => $qty
                    ];
                }

                // Generate Transaction Code (e.g. TRX-20261027-001)
                $datePrefix = date('Ymd');
                $stmtSeq = $db->query("SELECT COUNT(*) as cnt FROM borrowings WHERE code LIKE 'TRX-$datePrefix-%'");
                $seq = ((int)$stmtSeq->fetch()['cnt']) + 1;
                $transCode = 'TRX-' . $datePrefix . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);

                // Insert into borrowings header
                $stmtInsert = $db->prepare("
                    INSERT INTO borrowings (code, borrower_id, borrow_date, expected_return_date, status, purpose, notes)
                    VALUES (?, ?, ?, ?, 'Dipinjam', ?, ?)
                ");
                $stmtInsert->execute([
                    $transCode, $borrowerId, $borrowDate, $expectedReturnDate, $purpose, $notes
                ]);
                $borrowingId = $db->lastInsertId();

                // Insert into borrowing_details and deduct item available stock
                $stmtDetail = $db->prepare("
                    INSERT INTO borrowing_details (borrowing_id, item_id, quantity, status)
                    VALUES (?, ?, ?, 'Dipinjam')
                ");
                $stmtDeduct = $db->prepare("
                    UPDATE items 
                    SET available_stock = available_stock - ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");

                foreach ($validatedItems as $vItem) {
                    $stmtDetail->execute([$borrowingId, $vItem['item_id'], $vItem['quantity']]);
                    $stmtDeduct->execute([$vItem['quantity'], $vItem['item_id']]);
                }

                $db->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Transaksi peminjaman (' . count($validatedItems) . ' jenis barang) berhasil dicatat! Stok barang otomatis dikurangi.',
                    'data' => [
                        'id' => $borrowingId,
                        'code' => $transCode
                    ]
                ]);
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }

        // 2. Catat Pengembalian Barang (Multi-Item)
        if ($action === 'return') {
            $borrowingId = (int)($input['id'] ?? 0);
            $actualReturnDate = trim($input['actual_return_date'] ?? date('Y-m-d'));
            $globalCondition = trim($input['return_condition'] ?? 'Baik');
            $globalNotes = trim($input['return_notes'] ?? '');
            $itemReturns = $input['item_returns'] ?? []; // Optional per-item condition [{ id: detail_id, return_condition, return_notes }]

            if ($borrowingId <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID Transaksi tidak valid.']);
                exit;
            }

            $db->beginTransaction();

            try {
                // Fetch transaction details
                $stmtTrx = $db->prepare("SELECT * FROM borrowings WHERE id = ?");
                $stmtTrx->execute([$borrowingId]);
                $trx = $stmtTrx->fetch();

                if (!$trx) {
                    throw new Exception("Transaksi peminjaman tidak ditemukan.");
                }

                if ($trx['status'] === 'Kembali') {
                    throw new Exception("Semua barang untuk transaksi ini sudah dikembalikan sebelumnya.");
                }

                // Fetch details
                $stmtDetails = $db->prepare("SELECT * FROM borrowing_details WHERE borrowing_id = ?");
                $stmtDetails->execute([$borrowingId]);
                $details = $stmtDetails->fetchAll();

                if (empty($details)) {
                    throw new Exception("Tidak ada rincian barang pada transaksi ini.");
                }

                // Index per-item condition if supplied
                $conditionMap = [];
                foreach ($itemReturns as $ir) {
                    $conditionMap[$ir['id']] = $ir;
                }

                $stmtUpdateDetail = $db->prepare("
                    UPDATE borrowing_details 
                    SET return_condition = ?, return_notes = ?, status = 'Kembali' 
                    WHERE id = ?
                ");
                $stmtRestoreStock = $db->prepare("
                    UPDATE items 
                    SET available_stock = available_stock + ?, updated_at = CURRENT_TIMESTAMP 
                    WHERE id = ?
                ");

                foreach ($details as $d) {
                    if ($d['status'] !== 'Kembali') {
                        $cond = isset($conditionMap[$d['id']]) ? $conditionMap[$d['id']]['return_condition'] : $globalCondition;
                        $notes = isset($conditionMap[$d['id']]) ? $conditionMap[$d['id']]['return_notes'] : $globalNotes;

                        $stmtUpdateDetail->execute([$cond, $notes, $d['id']]);
                        $stmtRestoreStock->execute([$d['quantity'], $d['item_id']]);

                        if ($cond === 'Rusak Berat') {
                            $stmtCond = $db->prepare("UPDATE items SET condition = 'Perlu Perbaikan' WHERE id = ?");
                            $stmtCond->execute([$d['item_id']]);
                        }
                    }
                }

                // Update header status to Kembali
                $stmtUpdateHeader = $db->prepare("
                    UPDATE borrowings SET
                        actual_return_date = ?,
                        status = 'Kembali'
                    WHERE id = ?
                ");
                $stmtUpdateHeader->execute([$actualReturnDate, $borrowingId]);

                $db->commit();

                echo json_encode([
                    'status' => 'success',
                    'message' => 'Pengembalian barang berhasil dicatat! Seluruh stok barang telah dikembalikan ke stok tersedia.'
                ]);
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }

        // 3. Batalkan / Hapus Transaksi (Rollback stok jika aktif)
        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID Transaksi tidak valid.']);
                exit;
            }

            $db->beginTransaction();
            try {
                $stmtTrx = $db->prepare("SELECT * FROM borrowings WHERE id = ?");
                $stmtTrx->execute([$id]);
                $trx = $stmtTrx->fetch();

                if (!$trx) {
                    throw new Exception("Transaksi tidak ditemukan.");
                }

                // If transaction is still active, restore stock for all active details
                if (in_array($trx['status'], ['Dipinjam', 'Terlambat'])) {
                    $stmtDetails = $db->prepare("SELECT * FROM borrowing_details WHERE borrowing_id = ? AND status IN ('Dipinjam', 'Terlambat')");
                    $stmtDetails->execute([$id]);
                    $details = $stmtDetails->fetchAll();

                    $stmtRestore = $db->prepare("UPDATE items SET available_stock = available_stock + ? WHERE id = ?");
                    foreach ($details as $d) {
                        $stmtRestore->execute([$d['quantity'], $d['item_id']]);
                    }
                }

                // Delete header (cascades to borrowing_details)
                $stmtDel = $db->prepare("DELETE FROM borrowings WHERE id = ?");
                $stmtDel->execute([$id]);

                $db->commit();
                echo json_encode(['status' => 'success', 'message' => 'Data transaksi berhasil dihapus!']);
                exit;
            } catch (Exception $e) {
                $db->rollBack();
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
                exit;
            }
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
