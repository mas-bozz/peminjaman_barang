<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = Database::getConnection();

try {
    if ($method === 'GET') {
        // Return categories list
        if (isset($_GET['action']) && $_GET['action'] === 'categories') {
            $stmt = $db->query("SELECT * FROM categories ORDER BY name ASC");
            echo json_encode(['status' => 'success', 'data' => $stmt->fetchAll()]);
            exit;
        }

        // Return single item
        if (isset($_GET['id'])) {
            $id = trim($_GET['id']);
            $stmt = $db->prepare("
                SELECT i.*, c.name as category_name 
                FROM items i 
                JOIN categories c ON i.category_id = c.id 
                WHERE i.id = ?
            ");
            $stmt->execute([$id]);
            $item = $stmt->fetch();

            if (!$item) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Barang tidak ditemukan']);
                exit;
            }

            // Fetch loan history for this item from borrowing_details & borrowings
            $stmtHist = $db->prepare("
                SELECT b.code, b.borrow_date, b.expected_return_date, b.actual_return_date,
                       bd.quantity, bd.status, bd.return_condition, bd.return_notes,
                       br.name as borrower_name, br.phone as borrower_phone 
                FROM borrowing_details bd
                JOIN borrowings b ON bd.borrowing_id = b.id
                JOIN borrowers br ON b.borrower_id = br.id
                WHERE bd.item_id = ?
                ORDER BY b.id DESC
                LIMIT 10
            ");
            $stmtHist->execute([$id]);
            $item['history'] = $stmtHist->fetchAll();

            echo json_encode(['status' => 'success', 'data' => $item]);
            exit;
        }

        // List items with filters and search
        $search = trim($_GET['search'] ?? '');
        $categoryId = isset($_GET['category_id']) && $_GET['category_id'] !== '' ? trim($_GET['category_id']) : null;
        $stockFilter = $_GET['stock'] ?? 'all'; // all, available, empty

        $sql = "
            SELECT i.*, c.name as category_name, c.icon as category_icon
            FROM items i
            JOIN categories c ON i.category_id = c.id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (i.name LIKE ? OR i.code LIKE ? OR i.id LIKE ? OR c.name LIKE ? OR i.location LIKE ?)";
            $wildcard = "%$search%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($categoryId !== null) {
            $sql .= " AND i.category_id = ?";
            $params[] = $categoryId;
        }

        if ($stockFilter === 'available') {
            $sql .= " AND i.available_stock > 0";
        } elseif ($stockFilter === 'empty') {
            $sql .= " AND i.available_stock = 0";
        }

        $sql .= " ORDER BY i.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $items = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $items, 'total' => count($items)]);
        exit;
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $name = trim($input['name'] ?? '');
            $categoryId = trim($input['category_id'] ?? '');
            $totalStock = (int)($input['total_stock'] ?? 1);
            $location = trim($input['location'] ?? 'Gudang Utama');
            $condition = trim($input['condition'] ?? 'Baik');
            $description = trim($input['description'] ?? '');

            if ($name === '' || $categoryId === '' || $totalStock < 1) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nama barang, kategori, dan jumlah stok (min 1) wajib diisi.']);
                exit;
            }

            // Generate or use custom code/id with prefix BRG-xxx
            $code = trim($input['code'] ?? '');
            if ($code === '') {
                $stmtMax = $db->query("SELECT id FROM items WHERE id LIKE 'BRG-%' ORDER BY id DESC LIMIT 1");
                $lastRow = $stmtMax->fetch();
                $seq = 1;
                if ($lastRow && preg_match('/BRG-(\d+)/', $lastRow['id'], $m)) {
                    $seq = (int)$m[1] + 1;
                }
                $code = 'BRG-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }

            // Check id/code uniqueness
            $chk = $db->prepare("SELECT id FROM items WHERE id = ? OR code = ?");
            $chk->execute([$code, $code]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Kode/ID barang '$code' sudah digunakan!"]);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO items (id, code, name, category_id, total_stock, available_stock, location, condition, description)
                VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([
                $code, $code, $name, $categoryId, $totalStock, $totalStock, $location, $condition, $description
            ]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Barang berhasil ditambahkan dengan ID ' . $code,
                'data' => ['id' => $code, 'code' => $code]
            ]);
            exit;
        }

        if ($action === 'update') {
            $id = trim($input['id'] ?? '');
            $name = trim($input['name'] ?? '');
            $code = trim($input['code'] ?? '');
            $categoryId = trim($input['category_id'] ?? '');
            $totalStock = (int)($input['total_stock'] ?? 1);
            $location = trim($input['location'] ?? 'Gudang Utama');
            $condition = trim($input['condition'] ?? 'Baik');
            $description = trim($input['description'] ?? '');

            if ($id === '' || $name === '' || $categoryId === '' || $totalStock < 1) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Data pembaharuan barang tidak valid.']);
                exit;
            }

            // Check current item
            $stmtCur = $db->prepare("SELECT * FROM items WHERE id = ?");
            $stmtCur->execute([$id]);
            $current = $stmtCur->fetch();
            if (!$current) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Barang tidak ditemukan.']);
                exit;
            }

            if ($code === '') $code = $id;

            // Check code uniqueness excluding current
            $chk = $db->prepare("SELECT id FROM items WHERE code = ? AND id != ?");
            $chk->execute([$code, $id]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Kode barang '$code' sudah dipakai barang lain!"]);
                exit;
            }

            // Calculate currently borrowed units
            $currentlyBorrowed = $current['total_stock'] - $current['available_stock'];
            if ($totalStock < $currentlyBorrowed) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Total stok tidak boleh kurang dari $currentlyBorrowed (jumlah unit yang sedang dipinjam saat ini)!"
                ]);
                exit;
            }

            $newAvailableStock = $totalStock - $currentlyBorrowed;

            $stmt = $db->prepare("
                UPDATE items SET 
                    code = ?, 
                    name = ?, 
                    category_id = ?, 
                    total_stock = ?, 
                    available_stock = ?, 
                    location = ?, 
                    condition = ?, 
                    description = ?,
                    updated_at = CURRENT_TIMESTAMP
                WHERE id = ?
            ");
            $stmt->execute([
                $code, $name, $categoryId, $totalStock, $newAvailableStock, $location, $condition, $description, $id
            ]);

            echo json_encode(['status' => 'success', 'message' => 'Data barang berhasil diperbarui!']);
            exit;
        }

        if ($action === 'delete') {
            $id = trim($input['id'] ?? '');
            if ($id === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID barang tidak valid.']);
                exit;
            }

            // Check if there are active loans in borrowing_details
            $stmtCheck = $db->prepare("
                SELECT COUNT(*) as active_count 
                FROM borrowing_details 
                WHERE item_id = ? AND status IN ('Dipinjam', 'Terlambat')
            ");
            $stmtCheck->execute([$id]);
            $activeCount = (int)$stmtCheck->fetch()['active_count'];

            if ($activeCount > 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Barang tidak dapat dihapus karena masih sedang dipinjam!'
                ]);
                exit;
            }

            // Delete associated borrowing details or reject
            $stmtDelHist = $db->prepare("DELETE FROM borrowing_details WHERE item_id = ?");
            $stmtDelHist->execute([$id]);

            $stmt = $db->prepare("DELETE FROM items WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'Barang berhasil dihapus dari inventaris.']);
            exit;
        }

        http_response_code(400);
        echo json_encode(['status' => 'error', 'message' => 'Aksi tidak valid']);
        exit;
    }
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode(['status' => 'error', 'message' => $e->getMessage()]);
}
