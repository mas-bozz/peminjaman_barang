<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = Database::getConnection();

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = trim($_GET['id']);
            $stmt = $db->prepare("SELECT * FROM categories WHERE id = ?");
            $stmt->execute([$id]);
            $category = $stmt->fetch();

            if (!$category) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Kategori tidak ditemukan']);
                exit;
            }

            // Fetch items in this category
            $stmtItems = $db->prepare("SELECT * FROM items WHERE category_id = ? ORDER BY id DESC");
            $stmtItems->execute([$id]);
            $category['items'] = $stmtItems->fetchAll();

            echo json_encode(['status' => 'success', 'data' => $category]);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $sql = "
            SELECT c.*, 
                   COUNT(i.id) as item_count, 
                   COALESCE(SUM(i.total_stock), 0) as total_units,
                   COALESCE(SUM(i.available_stock), 0) as available_units
            FROM categories c
            LEFT JOIN items i ON c.id = i.category_id
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (c.name LIKE ? OR c.id LIKE ? OR c.description LIKE ?)";
            $wildcard = "%$search%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        $sql .= " GROUP BY c.id ORDER BY c.id ASC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $categories = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $categories, 'total' => count($categories)]);
        exit;
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $name = trim($input['name'] ?? '');
            $icon = trim($input['icon'] ?? 'box');
            $description = trim($input['description'] ?? '');

            if ($name === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Nama kategori wajib diisi!']);
                exit;
            }

            // Check duplicate name
            $chk = $db->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?)");
            $chk->execute([$name]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Kategori '$name' sudah ada!"]);
                exit;
            }

            // Generate KTG-xxx ID
            $id = trim($input['id'] ?? '');
            if ($id === '') {
                $stmtMax = $db->query("SELECT id FROM categories WHERE id LIKE 'KTG-%' ORDER BY id DESC LIMIT 1");
                $lastRow = $stmtMax->fetch();
                $seq = 1;
                if ($lastRow && preg_match('/KTG-(\d+)/', $lastRow['id'], $m)) {
                    $seq = (int)$m[1] + 1;
                }
                $id = 'KTG-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
            }

            // Ensure unique ID
            $chkId = $db->prepare("SELECT id FROM categories WHERE id = ?");
            $chkId->execute([$id]);
            if ($chkId->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "ID Kategori '$id' sudah ada!"]);
                exit;
            }

            $stmt = $db->prepare("INSERT INTO categories (id, name, icon, description) VALUES (?, ?, ?, ?)");
            $stmt->execute([$id, $name, $icon, $description]);

            echo json_encode([
                'status' => 'success',
                'message' => 'Kategori baru berhasil ditambahkan dengan ID ' . $id,
                'data' => ['id' => $id, 'name' => $name]
            ]);
            exit;
        }

        if ($action === 'update') {
            $id = trim($input['id'] ?? '');
            $name = trim($input['name'] ?? '');
            $icon = trim($input['icon'] ?? 'box');
            $description = trim($input['description'] ?? '');

            if ($id === '' || $name === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID dan Nama kategori wajib diisi!']);
                exit;
            }

            // Check duplicate name on other category
            $chk = $db->prepare("SELECT id FROM categories WHERE LOWER(name) = LOWER(?) AND id != ?");
            $chk->execute([$name, $id]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Nama kategori '$name' sudah digunakan kategori lain!"]);
                exit;
            }

            $stmt = $db->prepare("UPDATE categories SET name = ?, icon = ?, description = ? WHERE id = ?");
            $stmt->execute([$name, $icon, $description, $id]);

            echo json_encode(['status' => 'success', 'message' => 'Data kategori berhasil diperbarui!']);
            exit;
        }

        if ($action === 'delete') {
            $id = trim($input['id'] ?? '');
            if ($id === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID kategori tidak valid.']);
                exit;
            }

            // Check if items are currently categorized under this
            $stmtCheck = $db->prepare("SELECT COUNT(*) as item_count FROM items WHERE category_id = ?");
            $stmtCheck->execute([$id]);
            $itemCount = (int)$stmtCheck->fetch()['item_count'];

            if ($itemCount > 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => "Kategori tidak dapat dihapus karena masih digunakan oleh $itemCount barang inventaris. Pindahkan atau hapus barang terkait terlebih dahulu."
                ]);
                exit;
            }

            $stmt = $db->prepare("DELETE FROM categories WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'Kategori berhasil dihapus!']);
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
