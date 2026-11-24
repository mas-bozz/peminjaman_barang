<?php
require_once __DIR__ . '/../config/database.php';

$type = $_GET['type'] ?? 'items'; // items, borrowings, borrowers
$db = Database::getConnection();

if ($type === 'items') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Laporan_Inventaris_Barang_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    // Add BOM for Indonesian / Excel UTF-8 support
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['No', 'Kode Barang', 'Nama Barang', 'Kategori', 'Total Stok', 'Stok Tersedia', 'Stok Dipinjam', 'Lokasi', 'Kondisi', 'Keterangan']);

    $stmt = $db->query("
        SELECT i.*, c.name as category_name
        FROM items i
        JOIN categories c ON i.category_id = c.id
        ORDER BY i.id ASC
    ");
    $no = 1;
    while ($row = $stmt->fetch()) {
        $borrowed = $row['total_stock'] - $row['available_stock'];
        fputcsv($output, [
            $no++,
            $row['code'],
            $row['name'],
            $row['category_name'],
            $row['total_stock'],
            $row['available_stock'],
            $borrowed,
            $row['location'],
            $row['condition'],
            $row['description']
        ]);
    }
    fclose($output);
    exit;
}

if ($type === 'borrowings') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Laporan_Transaksi_Peminjaman_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['No', 'Kode Transaksi', 'Nama Peminjam', 'NIM/NIP', 'No. HP', 'Divisi/Prodi', 'Nama Barang', 'Kode Barang', 'Jumlah Unit', 'Tgl Pinjam', 'Tgl Batas Kembali', 'Tgl Kembali Aktual', 'Status', 'Keperluan', 'Kondisi Kembali', 'Catatan']);

    $stmt = $db->query("
        SELECT b.*, br.name as borrower_name, br.identity_number, br.phone, br.department,
               bd.quantity, bd.return_condition, bd.return_notes,
               i.name as item_name, i.code as item_code
        FROM borrowings b
        JOIN borrowers br ON b.borrower_id = br.id
        JOIN borrowing_details bd ON b.id = bd.borrowing_id
        JOIN items i ON bd.item_id = i.id
        ORDER BY b.id DESC
    ");
    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['code'],
            $row['borrower_name'],
            $row['identity_number'],
            $row['phone'],
            $row['department'],
            $row['item_name'],
            $row['item_code'],
            $row['quantity'],
            $row['borrow_date'],
            $row['expected_return_date'],
            $row['actual_return_date'] ?: '-',
            $row['status'],
            $row['purpose'],
            $row['return_condition'] ?: '-',
            $row['notes'] ?: '-'
        ]);
    }
    fclose($output);
    exit;
}

if ($type === 'borrowers') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="Data_Peminjam_' . date('Ymd_His') . '.csv"');

    $output = fopen('php://output', 'w');
    fprintf($output, chr(0xEF).chr(0xBB).chr(0xBF));
    fputcsv($output, ['No', 'Nomor Identitas (NIM/NIP)', 'Nama Lengkap', 'Nomor Telepon/WA', 'Email', 'Divisi / Program Studi', 'Status']);

    $stmt = $db->query("SELECT * FROM borrowers ORDER BY id ASC");
    $no = 1;
    while ($row = $stmt->fetch()) {
        fputcsv($output, [
            $no++,
            $row['identity_number'],
            $row['name'],
            $row['phone'],
            $row['email'] ?: '-',
            $row['department'],
            $row['status']
        ]);
    }
    fclose($output);
    exit;
}
