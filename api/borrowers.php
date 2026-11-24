<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';

$method = $_SERVER['REQUEST_METHOD'] ?? 'GET';
$db = Database::getConnection();

try {
    if ($method === 'GET') {
        if (isset($_GET['id'])) {
            $id = (int)$_GET['id'];
            $stmt = $db->prepare("SELECT * FROM borrowers WHERE id = ?");
            $stmt->execute([$id]);
            $borrower = $stmt->fetch();

            if (!$borrower) {
                http_response_code(404);
                echo json_encode(['status' => 'error', 'message' => 'Peminjam tidak ditemukan']);
                exit;
            }

            // Fetch loans history for this borrower
            $stmtHist = $db->prepare("
                SELECT b.*,
                       COALESCE(SUM(bd.quantity), 0) as total_quantity,
                       GROUP_CONCAT(CONCAT(i.name, ' (', bd.quantity, ' unit)') SEPARATOR ', ') as item_names
                FROM borrowings b
                JOIN borrowing_details bd ON b.id = bd.borrowing_id
                JOIN items i ON bd.item_id = i.id
                WHERE b.borrower_id = ?
                GROUP BY b.id
                ORDER BY b.id DESC
            ");
            $stmtHist->execute([$id]);
            $borrower['loans'] = $stmtHist->fetchAll();

            echo json_encode(['status' => 'success', 'data' => $borrower]);
            exit;
        }

        $search = trim($_GET['search'] ?? '');
        $status = trim($_GET['status'] ?? '');

        $sql = "
            SELECT b.*,
                   (SELECT COUNT(*) FROM borrowings WHERE borrower_id = b.id AND status IN ('Dipinjam', 'Terlambat')) as active_loans_count,
                   (SELECT COUNT(*) FROM borrowings WHERE borrower_id = b.id) as total_loans_count
            FROM borrowers b
            WHERE 1=1
        ";
        $params = [];

        if ($search !== '') {
            $sql .= " AND (b.name LIKE ? OR b.identity_number LIKE ? OR b.department LIKE ? OR b.phone LIKE ?)";
            $wildcard = "%$search%";
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
            $params[] = $wildcard;
        }

        if ($status !== '' && $status !== 'all') {
            $sql .= " AND b.status = ?";
            $params[] = $status;
        }

        $sql .= " ORDER BY b.id DESC";

        $stmt = $db->prepare($sql);
        $stmt->execute($params);
        $borrowers = $stmt->fetchAll();

        echo json_encode(['status' => 'success', 'data' => $borrowers, 'total' => count($borrowers)]);
        exit;
    }

    if ($method === 'POST') {
        $rawInput = file_get_contents('php://input');
        $input = json_decode($rawInput, true) ?: $_POST;

        $action = $input['action'] ?? '';

        if ($action === 'create') {
            $identityNumber = trim($input['identity_number'] ?? '');
            $name = trim($input['name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $email = trim($input['email'] ?? '');
            $department = trim($input['department'] ?? '');
            $status = trim($input['status'] ?? 'Aktif');

            if ($identityNumber === '' || $name === '' || $phone === '' || $department === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'NIM/NIP/Identitas, Nama, No. HP, dan Divisi/Prodi wajib diisi.']);
                exit;
            }

            // Check unique identity_number
            $chk = $db->prepare("SELECT id FROM borrowers WHERE identity_number = ?");
            $chk->execute([$identityNumber]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Nomor identitas '$identityNumber' sudah terdaftar!"]);
                exit;
            }

            $stmt = $db->prepare("
                INSERT INTO borrowers (identity_number, name, phone, email, department, status)
                VALUES (?, ?, ?, ?, ?, ?)
            ");
            $stmt->execute([$identityNumber, $name, $phone, $email, $department, $status]);

            echo json_encode(['status' => 'success', 'message' => 'Peminjam berhasil ditambahkan!']);
            exit;
        }

        if ($action === 'update') {
            $id = (int)($input['id'] ?? 0);
            $identityNumber = trim($input['identity_number'] ?? '');
            $name = trim($input['name'] ?? '');
            $phone = trim($input['phone'] ?? '');
            $email = trim($input['email'] ?? '');
            $department = trim($input['department'] ?? '');
            $status = trim($input['status'] ?? 'Aktif');

            if ($id <= 0 || $identityNumber === '' || $name === '' || $phone === '' || $department === '') {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'Formulir data peminjam tidak lengkap.']);
                exit;
            }

            // Check unique identity_number excluding current
            $chk = $db->prepare("SELECT id FROM borrowers WHERE identity_number = ? AND id != ?");
            $chk->execute([$identityNumber, $id]);
            if ($chk->fetch()) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => "Nomor identitas '$identityNumber' sudah dipakai peminjam lain!"]);
                exit;
            }

            $stmt = $db->prepare("
                UPDATE borrowers SET
                    identity_number = ?,
                    name = ?,
                    phone = ?,
                    email = ?,
                    department = ?,
                    status = ?
                WHERE id = ?
            ");
            $stmt->execute([$identityNumber, $name, $phone, $email, $department, $status, $id]);

            echo json_encode(['status' => 'success', 'message' => 'Data peminjam berhasil diperbarui!']);
            exit;
        }

        if ($action === 'delete') {
            $id = (int)($input['id'] ?? 0);
            if ($id <= 0) {
                http_response_code(400);
                echo json_encode(['status' => 'error', 'message' => 'ID peminjam tidak valid.']);
                exit;
            }

            // Check if borrower has active unreturned loans
            $stmtCheck = $db->prepare("SELECT COUNT(*) as active_count FROM borrowings WHERE borrower_id = ? AND status IN ('Dipinjam', 'Terlambat')");
            $stmtCheck->execute([$id]);
            $activeCount = (int)$stmtCheck->fetch()['active_count'];

            if ($activeCount > 0) {
                http_response_code(400);
                echo json_encode([
                    'status' => 'error',
                    'message' => 'Peminjam tidak dapat dihapus karena masih memiliki barang yang belum dikembalikan!'
                ]);
                exit;
            }

            // Remove borrowing history or reject
            $stmtDelHist = $db->prepare("DELETE FROM borrowings WHERE borrower_id = ?");
            $stmtDelHist->execute([$id]);

            $stmt = $db->prepare("DELETE FROM borrowers WHERE id = ?");
            $stmt->execute([$id]);

            echo json_encode(['status' => 'success', 'message' => 'Data peminjam berhasil dihapus.']);
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
