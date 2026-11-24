<!DOCTYPE html>
<html lang="id">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sistem Informasi Inventaris Barang - Dashboard & Peminjaman</title>
    <meta name="description" content="Sistem Informasi Inventaris dan Peminjaman Barang Modern, Cepat, dan Akurat">
    
    <!-- Google Fonts -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:wght@300;400;500;600;700;800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    
    <!-- Lucide Icons CDN -->
    <script src="https://unpkg.com/lucide@latest"></script>
    
    <!-- Chart.js CDN -->
    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>

    <!-- Stylesheets -->
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="assets/css/print.css" media="print">
</head>
<body>

<div class="app-layout">
    <!-- Sidebar -->
    <aside class="app-sidebar">
        <div class="sidebar-header">
            <div class="brand-icon">
                <i data-lucide="boxes" style="width: 24px; height: 24px;"></i>
            </div>
            <div>
                <div class="brand-title">InventaPro</div>
                <div class="brand-subtitle">Sistem Inventaris</div>
            </div>
        </div>

        <nav class="sidebar-menu">
            <div class="menu-category">Utama</div>
            <a class="nav-item active" data-view="dashboard" id="nav-dashboard">
                <i data-lucide="layout-dashboard" style="width: 18px; height: 18px;"></i>
                <span>Dashboard</span>
            </a>

            <div class="menu-category">Master Data</div>
            <a class="nav-item" data-view="items" id="nav-items">
                <i data-lucide="package" style="width: 18px; height: 18px;"></i>
                <span>Manajemen Barang</span>
                <span class="badge" id="itemsCountBadge">0</span>
            </a>
            <a class="nav-item" data-view="categories" id="nav-categories">
                <i data-lucide="folder-tree" style="width: 18px; height: 18px;"></i>
                <span>Manajemen Kategori</span>
                <span class="badge" id="categoriesCountBadge">0</span>
            </a>
            <a class="nav-item" data-view="borrowers" id="nav-borrowers">
                <i data-lucide="users" style="width: 18px; height: 18px;"></i>
                <span>Manajemen Peminjam</span>
                <span class="badge" id="borrowersCountBadge">0</span>
            </a>

            <div class="menu-category">Operasional</div>
            <a class="nav-item" data-view="borrowings" id="nav-borrowings">
                <i data-lucide="arrow-left-right" style="width: 18px; height: 18px;"></i>
                <span>Transaksi Pinjam</span>
                <span class="badge" id="borrowingsCountBadge">0</span>
            </a>
            <a class="nav-item" data-view="reports" id="nav-reports">
                <i data-lucide="file-text" style="width: 18px; height: 18px;"></i>
                <span>Laporan & Ekspor</span>
            </a>
        </nav>

        <div class="sidebar-footer">
            <div class="user-snippet">
                <div class="user-avatar">AD</div>
                <div>
                    <div class="user-name">Administrator</div>
                    <div class="user-role">Pengelola Aset</div>
                </div>
            </div>
        </div>
    </aside>

    <!-- Main Content -->
    <main class="app-main">
        <!-- Top Header -->
        <header class="app-header">
            <div class="header-left">
                <button class="mobile-toggle" id="mobileToggleBtn" aria-label="Menu">
                    <i data-lucide="menu" style="width: 22px; height: 22px;"></i>
                </button>
                <div class="page-title-box">
                    <h1 id="headerTitle">Sistem Informasi Inventaris</h1>
                    <p id="headerSubtitle">Kelola inventaris aset, peminjam, dan sirkulasi barang secara real-time</p>
                </div>
            </div>

            <div class="header-actions">
                <button class="btn btn-primary btn-sm" onclick="App.openNewBorrowingModal()" id="quickBorrowBtn">
                    <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                    <span>Catat Pinjam</span>
                </button>
                <button class="btn btn-secondary btn-sm" onclick="App.openAddItemModal()" id="quickAddItemBtn">
                    <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                    <span>Tambah Barang</span>
                </button>
                
                <!-- Notification Bell & Dropdown -->
                <div class="notification-wrapper" id="notificationWrapper">
                    <button class="notif-toggle-btn" id="notifBellBtn" title="Pemberitahuan & Peringatan" aria-label="Notifikasi" onclick="App.toggleNotifDropdown()">
                        <i data-lucide="bell" style="width: 18px; height: 18px;"></i>
                        <span class="notif-badge" id="notifBadgeCount" style="display: none;">0</span>
                    </button>
                    
                    <div class="notif-dropdown" id="notifDropdown">
                        <div class="notif-header">
                            <div class="notif-title-box">
                                <div class="notif-title">
                                    <i data-lucide="bell-ring" style="width: 16px; height: 16px; color: var(--primary);"></i>
                                    <span>Pusat Notifikasi</span>
                                </div>
                                <div class="notif-subtitle" id="notifSubSummary">Memeriksa status sistem...</div>
                            </div>
                            <button class="btn btn-sm btn-secondary btn-icon-only" onclick="App.loadNotifications(true)" title="Segarkan Data">
                                <i data-lucide="refresh-cw" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>

                        <div class="notif-tabs">
                            <button class="notif-tab active" id="tabBtnOverdue" onclick="App.switchNotifTab('overdue')">
                                <i data-lucide="clock" style="width: 14px; height: 14px;"></i>
                                <span>Jatuh Tempo</span>
                                <span class="notif-pill pill-danger" id="tabOverdueCount">0</span>
                            </button>
                            <button class="notif-tab" id="tabBtnOutOfStock" onclick="App.switchNotifTab('outofstock')">
                                <i data-lucide="alert-octagon" style="width: 14px; height: 14px;"></i>
                                <span>Tidak Tersedia</span>
                                <span class="notif-pill pill-warning" id="tabOutOfStockCount">0</span>
                            </button>
                        </div>

                        <div class="notif-body" id="notifBody">
                            <!-- Populated dynamically via JS -->
                        </div>

                        <div class="notif-footer">
                            <button class="btn btn-sm btn-secondary" onclick="App.viewAllNotifications()" style="width: 100%;">
                                <i data-lucide="arrow-up-right" style="width: 14px; height: 14px;"></i>
                                <span>Buka Transaksi Pinjam / Manajemen Barang</span>
                            </button>
                        </div>
                    </div>
                </div>

                <button class="theme-toggle-btn" id="themeToggleBtn" title="Ganti Tema">
                    <i data-lucide="moon" style="width: 18px; height: 18px;"></i>
                </button>
            </div>
        </header>

        <div class="content-wrapper">

            <!-- ==========================================================================
                 VIEW 1: DASHBOARD
                 ========================================================================== -->
            <section class="view-section active" id="dashboard-view">
                <!-- Notification Alert Banners Container -->
                <div id="dashboardAlertsContainer"></div>

                <!-- Metrics Grid -->
                <div class="stats-grid">
                    <div class="stat-card blue">
                        <div class="stat-info">
                            <div class="stat-label">Jenis Barang</div>
                            <div class="stat-value" id="statTotalItems">-</div>
                            <div class="stat-hint"><i data-lucide="layers" style="width: 12px; height: 12px;"></i> Total katalog aset</div>
                        </div>
                        <div class="stat-icon blue">
                            <i data-lucide="boxes" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>

                    <div class="stat-card purple">
                        <div class="stat-info">
                            <div class="stat-label">Total Unit Fisik</div>
                            <div class="stat-value" id="statTotalUnits">-</div>
                            <div class="stat-hint"><i data-lucide="archive" style="width: 12px; height: 12px;"></i> Keseluruhan stok</div>
                        </div>
                        <div class="stat-icon purple">
                            <i data-lucide="box" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>

                    <div class="stat-card emerald">
                        <div class="stat-info">
                            <div class="stat-label">Barang Tersedia</div>
                            <div class="stat-value" id="statAvailableUnits" style="color: var(--success);">-</div>
                            <div class="stat-hint"><i data-lucide="check-circle" style="width: 12px; height: 12px;"></i> Siap dipinjam</div>
                        </div>
                        <div class="stat-icon emerald">
                            <i data-lucide="check-check" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>

                    <div class="stat-card amber">
                        <div class="stat-info">
                            <div class="stat-label">Sedang Dipinjam</div>
                            <div class="stat-value" id="statBorrowedUnits" style="color: var(--warning);">-</div>
                            <div class="stat-hint"><i data-lucide="clock" style="width: 12px; height: 12px;"></i> Di luar gudang</div>
                        </div>
                        <div class="stat-icon amber">
                            <i data-lucide="hand-metal" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>

                    <div class="stat-card rose">
                        <div class="stat-info">
                            <div class="stat-label">Peminjam Aktif</div>
                            <div class="stat-value" id="statTotalBorrowers">-</div>
                            <div class="stat-hint" id="statOverdueBadge">
                                <span class="badge-status badge-available">Semua Aman</span>
                            </div>
                        </div>
                        <div class="stat-icon rose">
                            <i data-lucide="user-check" style="width: 24px; height: 24px;"></i>
                        </div>
                    </div>
                </div>

                <!-- Dashboard 2-column: Chart & Low Stock Warnings -->
                <div class="dashboard-grid">
                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">
                                    <i data-lucide="pie-chart" style="width: 18px; height: 18px; color: var(--primary);"></i>
                                    Distribusi Unit Berdasarkan Kategori
                                </div>
                                <div class="card-subtitle">Komposisi jumlah unit aset per kelompok kategori barang</div>
                            </div>
                        </div>
                        <div class="card-body" style="height: 290px; position: relative;">
                            <canvas id="categoryPieChart"></canvas>
                        </div>
                    </div>

                    <div class="card">
                        <div class="card-header">
                            <div>
                                <div class="card-title">
                                    <i data-lucide="alert-triangle" style="width: 18px; height: 18px; color: var(--warning);"></i>
                                    Peringatan Stok Menipis
                                </div>
                                <div class="card-subtitle">Barang dengan sisa stok &le; 2 unit</div>
                            </div>
                        </div>
                        <div class="card-body" id="dashboardLowStockList" style="max-height: 290px; overflow-y: auto;">
                            <!-- Populated dynamically -->
                        </div>
                    </div>
                </div>

                <!-- Recent Active Borrowings Table -->
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">
                                <i data-lucide="clock" style="width: 18px; height: 18px; color: var(--primary);"></i>
                                Peminjaman Terkini yang Sedang Berjalan
                            </div>
                            <div class="card-subtitle">Daftar barang yang belum dikembalikan ke gudang inventaris</div>
                        </div>
                        <button class="btn btn-secondary btn-sm" onclick="App.navigateTo('borrowings')">
                            <span>Lihat Semua Transaksi</span>
                            <i data-lucide="chevron-right" style="width: 14px; height: 14px;"></i>
                        </button>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Kode Transaksi</th>
                                    <th>Peminjam</th>
                                    <th>Barang Dipinjam</th>
                                    <th>Jumlah</th>
                                    <th>Status & Batas Kembali</th>
                                    <th>Aksi Cepat</th>
                                </tr>
                            </thead>
                            <tbody id="dashboardRecentActiveBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <!-- ==========================================================================
                 VIEW 2: MANAJEMEN BARANG
                 ========================================================================== -->
            <section class="view-section" id="items-view">
                <!-- Out of Stock Notification Alert Banner -->
                <div id="itemsAlertBanner" style="display: none; margin-bottom: 16px;"></div>

                <div class="filter-toolbar">
                    <div class="search-input-box">
                        <i data-lucide="search" class="search-icon" style="width: 18px; height: 18px;"></i>
                        <input type="text" id="itemsSearchInput" placeholder="Cari barang berdasarkan nama, kode, kategori, atau lokasi...">
                    </div>

                    <div class="filter-group">
                        <select class="select-custom" id="itemCategoryFilter" onchange="App.loadItems()">
                            <option value="">Semua Kategori</option>
                        </select>

                        <select class="select-custom" id="itemStockFilter" onchange="App.loadItems()">
                            <option value="all">Semua Status Stok</option>
                            <option value="available">Hanya Tersedia (&gt; 0)</option>
                            <option value="empty">Stok Habis (0)</option>
                        </select>

                        <button class="btn btn-primary" onclick="App.openAddItemModal()">
                            <i data-lucide="plus" style="width: 16px; height: 16px;"></i>
                            <span>Tambah Barang</span>
                        </button>

                        <button class="btn btn-secondary" onclick="App.exportData('items')" title="Download CSV">
                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                            <span>Ekspor CSV</span>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Kode & Lokasi</th>
                                    <th>Nama Barang & Deskripsi</th>
                                    <th>Kategori</th>
                                    <th>Stok (Tersedia / Total)</th>
                                    <th>Status</th>
                                    <th>Kondisi</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="itemsTableBody">
                                <!-- Loaded dynamically via App.loadItems() -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <!-- ==========================================================================
                 VIEW: MANAJEMEN KATEGORI
                 ========================================================================== -->
            <section class="view-section" id="categories-view">
                <div class="filter-toolbar">
                    <div class="search-input-box">
                        <i data-lucide="search" class="search-icon" style="width: 18px; height: 18px;"></i>
                        <input type="text" id="categoriesSearchInput" placeholder="Cari nama kategori atau deskripsi...">
                    </div>

                    <div class="filter-group">
                        <button class="btn btn-primary" onclick="App.openAddCategoryModal()">
                            <i data-lucide="folder-plus" style="width: 16px; height: 16px;"></i>
                            <span>Tambah Kategori Baru</span>
                        </button>
                    </div>
                </div>

                <!-- Category Cards Grid -->
                <div class="category-grid" id="categoryCardsContainer">
                    <!-- Populated dynamically -->
                </div>

                <!-- Category Detail Table -->
                <div class="card" style="margin-top: 24px;">
                    <div class="card-header">
                        <div>
                            <div class="card-title">
                                <i data-lucide="list" style="width: 18px; height: 18px; color: var(--primary);"></i>
                                Tabel Rincian Kategori Barang
                            </div>
                            <div class="card-subtitle">Data lengkap kategori beserta kuantitas barang terdaftar</div>
                        </div>
                    </div>
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>Nama Kategori</th>
                                    <th>Deskripsi Kategori</th>
                                    <th>Jumlah Barang Terdaftar</th>
                                    <th>Total Unit Fisik</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="categoriesTableBody">
                                <!-- Populated dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <!-- ==========================================================================
                 VIEW 3: MANAJEMEN PEMINJAM
                 ========================================================================== -->
            <section class="view-section" id="borrowers-view">
                <div class="filter-toolbar">
                    <div class="search-input-box">
                        <i data-lucide="search" class="search-icon" style="width: 18px; height: 18px;"></i>
                        <input type="text" id="borrowersSearchInput" placeholder="Cari nama, NIM/NIP, atau prodi/divisi peminjam...">
                    </div>

                    <div class="filter-group">
                        <select class="select-custom" id="borrowerStatusFilter" onchange="App.loadBorrowers()">
                            <option value="all">Semua Status</option>
                            <option value="Aktif">Status Aktif</option>
                            <option value="Nonaktif">Status Nonaktif</option>
                        </select>

                        <button class="btn btn-primary" onclick="App.openAddBorrowerModal()">
                            <i data-lucide="user-plus" style="width: 16px; height: 16px;"></i>
                            <span>Tambah Peminjam</span>
                        </button>

                        <button class="btn btn-secondary" onclick="App.exportData('borrowers')" title="Download CSV">
                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                            <span>Ekspor CSV</span>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>NIM / NIP / Identitas</th>
                                    <th>Nama Lengkap</th>
                                    <th>No. Telepon / WA</th>
                                    <th>Divisi / Instansi / Prodi</th>
                                    <th>Status</th>
                                    <th>Peminjaman Aktif</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="borrowersTableBody">
                                <!-- Loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <!-- ==========================================================================
                 VIEW 4: TRANSAKSI PEMINJAMAN & PENGEMBALIAN
                 ========================================================================== -->
            <section class="view-section" id="borrowings-view">
                <!-- Overdue Notification Alert Banner -->
                <div id="borrowingsAlertBanner" style="display: none; margin-bottom: 16px;"></div>

                <div class="filter-toolbar">
                    <div class="search-input-box">
                        <i data-lucide="search" class="search-icon" style="width: 18px; height: 18px;"></i>
                        <input type="text" id="borrowingSearchInput" placeholder="Cari nomor transaksi, nama peminjam, atau nama barang...">
                    </div>

                    <div class="filter-group">
                        <select class="select-custom" id="borrowingStatusFilter" onchange="App.loadBorrowings()">
                            <option value="all">Semua Status</option>
                            <option value="Dipinjam">Sedang Dipinjam</option>
                            <option value="Terlambat">Jatuh Tempo / Terlambat</option>
                            <option value="Kembali">Sudah Kembali</option>
                        </select>

                        <button class="btn btn-primary" onclick="App.openNewBorrowingModal()">
                            <i data-lucide="plus-circle" style="width: 16px; height: 16px;"></i>
                            <span>Catat Peminjaman Baru</span>
                        </button>

                        <button class="btn btn-secondary" onclick="App.exportData('borrowings')" title="Download CSV">
                            <i data-lucide="download" style="width: 16px; height: 16px;"></i>
                            <span>Ekspor Riwayat</span>
                        </button>
                    </div>
                </div>

                <div class="card">
                    <div class="table-responsive">
                        <table class="modern-table">
                            <thead>
                                <tr>
                                    <th>No. Transaksi</th>
                                    <th>Peminjam</th>
                                    <th>Barang Dipinjam</th>
                                    <th>Periode Pinjam</th>
                                    <th>Status Transaksi</th>
                                    <th>Keperluan</th>
                                    <th>Aksi</th>
                                </tr>
                            </thead>
                            <tbody id="borrowingsTableBody">
                                <!-- Loaded dynamically -->
                            </tbody>
                        </table>
                    </div>
                </div>
            </section>


            <!-- ==========================================================================
                 VIEW 5: LAPORAN & EKSPOR DATA
                 ========================================================================== -->
            <section class="view-section" id="reports-view">
                <div class="card">
                    <div class="card-header">
                        <div>
                            <div class="card-title">
                                <i data-lucide="file-spreadsheet" style="width: 20px; height: 20px; color: var(--success);"></i>
                                Pusat Unduh Laporan Inventaris
                            </div>
                            <div class="card-subtitle">Ekspor data master inventaris, sirkulasi peminjaman, dan peminjam dalam format CSV / Excel</div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="stats-grid">
                            <div class="stat-card blue" style="cursor: pointer;" onclick="App.exportData('items')">
                                <div class="stat-info">
                                    <div class="stat-label">Laporan Inventaris Barang</div>
                                    <div style="font-weight: 700; font-size: 1.1rem; margin: 8px 0;">Data Stok & Lokasi</div>
                                    <div class="stat-hint"><i data-lucide="download" style="width: 14px; height: 14px;"></i> Klik untuk mengunduh CSV</div>
                                </div>
                                <div class="stat-icon blue">
                                    <i data-lucide="package" style="width: 24px; height: 24px;"></i>
                                </div>
                            </div>

                            <div class="stat-card emerald" style="cursor: pointer;" onclick="App.exportData('borrowings')">
                                <div class="stat-info">
                                    <div class="stat-label">Laporan Peminjaman</div>
                                    <div style="font-weight: 700; font-size: 1.1rem; margin: 8px 0;">Riwayat & Pengembalian</div>
                                    <div class="stat-hint"><i data-lucide="download" style="width: 14px; height: 14px;"></i> Klik untuk mengunduh CSV</div>
                                </div>
                                <div class="stat-icon emerald">
                                    <i data-lucide="arrow-left-right" style="width: 24px; height: 24px;"></i>
                                </div>
                            </div>

                            <div class="stat-card purple" style="cursor: pointer;" onclick="App.exportData('borrowers')">
                                <div class="stat-info">
                                    <div class="stat-label">Laporan Data Peminjam</div>
                                    <div style="font-weight: 700; font-size: 1.1rem; margin: 8px 0;">Database Anggota/Peminjam</div>
                                    <div class="stat-hint"><i data-lucide="download" style="width: 14px; height: 14px;"></i> Klik untuk mengunduh CSV</div>
                                </div>
                                <div class="stat-icon purple">
                                    <i data-lucide="users" style="width: 24px; height: 24px;"></i>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

        </div>
    </main>
</div>


<!-- ==========================================================================
     MODALS SECTION
     ========================================================================== -->

<!-- 1. Modal Tambah / Edit Barang -->
<div class="modal-backdrop" id="itemModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="itemModalTitle">Tambah Data Barang</h3>
            <button class="modal-close-btn" onclick="App.closeModal('itemModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <form id="itemForm" onsubmit="App.saveItem(event)">
            <input type="hidden" id="itemIdInput">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="itemModalCode">Kode Barang (Opsional)</label>
                        <input type="text" id="itemModalCode" class="form-control" placeholder="Contoh: BRG-011 (Otomatis jika kosong)">
                    </div>
                    <div class="form-group">
                        <label for="itemModalCategory">Kategori <span class="required">*</span></label>
                        <select id="itemModalCategory" class="form-control" required></select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="itemModalName">Nama Barang <span class="required">*</span></label>
                    <input type="text" id="itemModalName" class="form-control" placeholder="Contoh: Proyektor Epson EB-X500" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="itemModalStock">Jumlah Total Stok <span class="required">*</span></label>
                        <input type="number" id="itemModalStock" class="form-control" min="1" value="1" required>
                    </div>
                    <div class="form-group">
                        <label for="itemModalCondition">Kondisi Fisik</label>
                        <select id="itemModalCondition" class="form-control">
                            <option value="Baik" selected>Baik</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Perlu Perbaikan">Perlu Perbaikan</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="itemModalLocation">Lokasi Penyimpanan / Ruangan</label>
                    <input type="text" id="itemModalLocation" class="form-control" placeholder="Contoh: Lab Komputer Lantai 2" value="Gudang Utama">
                </div>

                <div class="form-group">
                    <label for="itemModalDesc">Deskripsi & Kelengkapan</label>
                    <textarea id="itemModalDesc" class="form-control" placeholder="Catatan spesifikasi, nomor seri, kelengkapan kabel, dll..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('itemModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Barang</button>
            </div>
        </form>
    </div>
</div>


<!-- 2. Modal Detail Barang & Riwayat -->
<div class="modal-backdrop" id="itemDetailModal">
    <div class="modal-box" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title">Rincian & Riwayat Barang</h3>
            <button class="modal-close-btn" onclick="App.closeModal('itemDetailModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="background: var(--bg-base); padding: 18px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid var(--border-subtle);">
                <div style="display: flex; justify-content: space-between; align-items: flex-start;">
                    <div>
                        <h4 id="itemDetailName" style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">-</h4>
                        <div style="font-size: 0.85rem; color: var(--primary); font-weight: 600;" id="itemDetailCode">-</div>
                    </div>
                    <span class="badge-category" id="itemDetailCategory">-</span>
                </div>
                <div style="margin-top: 12px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.825rem;">
                    <div><strong>Stok:</strong> <span id="itemDetailStock">-</span></div>
                    <div><strong>Lokasi:</strong> <span id="itemDetailLocation">-</span></div>
                    <div><strong>Kondisi:</strong> <span id="itemDetailCondition">-</span></div>
                    <div><strong>Keterangan:</strong> <span id="itemDetailDesc">-</span></div>
                </div>
            </div>

            <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px;">Riwayat Sirkulasi Peminjaman</h4>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Kode TRX</th>
                            <th>Peminjam</th>
                            <th>Qty</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Selesai</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="itemDetailHistoryBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('itemDetailModal')">Tutup</button>
        </div>
    </div>
</div>


<!-- Modal Tambah / Edit Kategori -->
<div class="modal-backdrop" id="categoryModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="categoryModalTitle">Tambah Kategori Barang</h3>
            <button class="modal-close-btn" onclick="App.closeModal('categoryModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <form id="categoryForm" onsubmit="App.saveCategory(event)">
            <input type="hidden" id="categoryIdInput">
            <div class="modal-body">
                <div class="form-group">
                    <label for="categoryModalName">Nama Kategori <span class="required">*</span></label>
                    <input type="text" id="categoryModalName" class="form-control" placeholder="Contoh: Perangkat Audio Visual / Furnitur Kantor" required>
                </div>

                <div class="form-group">
                    <label for="categoryModalIcon">Pilih Icon Representatif</label>
                    <select id="categoryModalIcon" class="form-control">
                        <option value="box">Box / Kotak Umum</option>
                        <option value="laptop">Laptop / Komputer</option>
                        <option value="video">Proyektor / Video</option>
                        <option value="network">Jaringan / Router</option>
                        <option value="camera">Kamera & Dokumentasi</option>
                        <option value="flask-conical">Alat Lab / Sains</option>
                        <option value="wrench">Peralatan / Perkakas</option>
                        <option value="folder">Folder / Dokumen</option>
                        <option value="package">Paket / Logistik</option>
                        <option value="cpu">Komponen Elektronika</option>
                        <option value="monitor">Layar Monitor / Display</option>
                        <option value="hard-drive">Penyimpanan / Server</option>
                    </select>
                </div>

                <div class="form-group">
                    <label for="categoryModalDesc">Deskripsi Kategori</label>
                    <textarea id="categoryModalDesc" class="form-control" placeholder="Jelaskan jenis-jenis barang yang termasuk dalam kategori ini..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('categoryModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Kategori</button>
            </div>
        </form>
    </div>
</div>

<!-- Modal Detail Kategori & Barang -->
<div class="modal-backdrop" id="categoryDetailModal">
    <div class="modal-box" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title">Daftar Barang dalam Kategori</h3>
            <button class="modal-close-btn" onclick="App.closeModal('categoryDetailModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="background: var(--bg-base); padding: 18px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid var(--border-subtle);">
                <div style="display: flex; justify-content: space-between; align-items: center;">
                    <div style="display: flex; align-items: center; gap: 12px;">
                        <div class="category-icon-box" id="categoryDetailIconBox" style="width: 40px; height: 40px;">
                            <i data-lucide="folder" id="categoryDetailIcon" style="width: 20px; height: 20px;"></i>
                        </div>
                        <div>
                            <h4 id="categoryDetailName" style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">-</h4>
                            <div style="font-size: 0.8rem; color: var(--text-muted);" id="categoryDetailDesc">-</div>
                        </div>
                    </div>
                </div>
            </div>

            <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px;">Barang yang Terdaftar</h4>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Kode</th>
                            <th>Nama Barang</th>
                            <th>Tersedia / Total</th>
                            <th>Lokasi</th>
                            <th>Kondisi</th>
                        </tr>
                    </thead>
                    <tbody id="categoryDetailItemsBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('categoryDetailModal')">Tutup</button>
        </div>
    </div>
</div>

<!-- 3. Modal Tambah / Edit Peminjam -->
<div class="modal-backdrop" id="borrowerModal">
    <div class="modal-box">
        <div class="modal-header">
            <h3 class="modal-title" id="borrowerModalTitle">Kelola Peminjam</h3>
            <button class="modal-close-btn" onclick="App.closeModal('borrowerModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <form id="borrowerForm" onsubmit="App.saveBorrower(event)">
            <input type="hidden" id="borrowerIdInput">
            <div class="modal-body">
                <div class="form-row">
                    <div class="form-group">
                        <label for="borrowerModalNim">NIM / NIP / No. KTP <span class="required">*</span></label>
                        <input type="text" id="borrowerModalNim" class="form-control" placeholder="Contoh: 202401050" required>
                    </div>
                    <div class="form-group">
                        <label for="borrowerModalStatus">Status Peminjam</label>
                        <select id="borrowerModalStatus" class="form-control">
                            <option value="Aktif" selected>Aktif (Bisa Pinjam)</option>
                            <option value="Nonaktif">Nonaktif</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="borrowerModalName">Nama Lengkap <span class="required">*</span></label>
                    <input type="text" id="borrowerModalName" class="form-control" placeholder="Contoh: Ahmad Fadhilah" required>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="borrowerModalPhone">No. WhatsApp / Telepon <span class="required">*</span></label>
                        <input type="text" id="borrowerModalPhone" class="form-control" placeholder="Contoh: 081234567890" required>
                    </div>
                    <div class="form-group">
                        <label for="borrowerModalEmail">Email</label>
                        <input type="email" id="borrowerModalEmail" class="form-control" placeholder="Contoh: ahmad@domain.com">
                    </div>
                </div>

                <div class="form-group">
                    <label for="borrowerModalDept">Divisi / Instansi / Program Studi <span class="required">*</span></label>
                    <input type="text" id="borrowerModalDept" class="form-control" placeholder="Contoh: Dosen Teknik / Mahasiswa Informatika" required>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('borrowerModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Simpan Peminjam</button>
            </div>
        </form>
    </div>
</div>


<!-- 4. Modal Detail Peminjam -->
<div class="modal-backdrop" id="borrowerDetailModal">
    <div class="modal-box" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title">Profil & Riwayat Peminjam</h3>
            <button class="modal-close-btn" onclick="App.closeModal('borrowerDetailModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div style="background: var(--bg-base); padding: 18px; border-radius: var(--radius-md); margin-bottom: 20px; border: 1px solid var(--border-subtle);">
                <h4 id="borrowerDetailName" style="font-size: 1.15rem; font-weight: 700; color: var(--text-primary);">-</h4>
                <div style="margin-top: 10px; display: grid; grid-template-columns: 1fr 1fr; gap: 8px; font-size: 0.825rem;">
                    <div><strong>Nomor Identitas:</strong> <span id="borrowerDetailNim">-</span></div>
                    <div><strong>Divisi / Prodi:</strong> <span id="borrowerDetailDept">-</span></div>
                    <div><strong>No. Telp / WA:</strong> <span id="borrowerDetailPhone">-</span></div>
                    <div><strong>Email:</strong> <span id="borrowerDetailEmail">-</span></div>
                    <div><strong>Status:</strong> <span id="borrowerDetailStatus">-</span></div>
                </div>
            </div>

            <h4 style="font-size: 0.95rem; font-weight: 700; margin-bottom: 10px;">Daftar Peminjaman</h4>
            <div class="table-responsive">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>No. Transaksi</th>
                            <th>Barang</th>
                            <th>Qty</th>
                            <th>Tgl Pinjam</th>
                            <th>Tgl Kembali</th>
                            <th>Status</th>
                        </tr>
                    </thead>
                    <tbody id="borrowerDetailLoansBody">
                        <!-- Loaded dynamically -->
                    </tbody>
                </table>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('borrowerDetailModal')">Tutup</button>
        </div>
    </div>
</div>


<!-- 5. Modal Catat Peminjaman Baru (Multi-Item) -->
<div class="modal-backdrop" id="newBorrowingModal">
    <div class="modal-box" style="max-width: 680px;">
        <div class="modal-header">
            <h3 class="modal-title">Formulir Peminjaman Barang (Multi-Barang)</h3>
            <button class="modal-close-btn" onclick="App.closeModal('newBorrowingModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <form id="newBorrowingForm" onsubmit="App.saveNewBorrowing(event)">
            <div class="modal-body">
                <div class="form-group">
                    <label for="borrowingSelectBorrower">Pilih Identitas Peminjam <span class="required">*</span></label>
                    <select id="borrowingSelectBorrower" class="form-control" required></select>
                </div>

                <!-- Multi-Item Detail Section -->
                <div style="background: var(--bg-base); padding: 16px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 12px;">
                        <div>
                            <div style="font-weight: 700; font-size: 0.95rem; color: var(--text-primary);">
                                <i data-lucide="package" style="width: 16px; height: 16px; vertical-align: middle;"></i>
                                Daftar Barang yang Dipinjam
                            </div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">Peminjam dapat meminjam lebih dari satu barang sekaligus dalam satu transaksi</div>
                        </div>
                        <button type="button" class="btn btn-sm btn-secondary" onclick="App.addBorrowItemRow()">
                            <i data-lucide="plus" style="width: 14px; height: 14px;"></i> Tambah Barang
                        </button>
                    </div>

                    <div id="borrowingItemsContainer" style="display: flex; flex-direction: column; gap: 10px;">
                        <!-- Dynamic item rows inserted here -->
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="borrowDateInput">Tanggal Pinjam <span class="required">*</span></label>
                        <input type="date" id="borrowDateInput" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="expectedReturnDateInput">Estimasi / Batas Tanggal Kembali <span class="required">*</span></label>
                        <input type="date" id="expectedReturnDateInput" class="form-control" required>
                    </div>
                </div>

                <div class="form-group">
                    <label for="borrowingPurposeInput">Keperluan / Kegiatan Peminjaman <span class="required">*</span></label>
                    <input type="text" id="borrowingPurposeInput" class="form-control" placeholder="Contoh: Praktikum Pemrograman Web / Seminar Nasional" required>
                </div>

                <div class="form-group">
                    <label for="borrowingNotesInput">Catatan Tambahan (Kelengkapan yang dibawa)</label>
                    <textarea id="borrowingNotesInput" class="form-control" placeholder="Contoh: Lengkap dengan charger, tas laptop, dan adapter HDMI..."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('newBorrowingModal')">Batal</button>
                <button type="submit" class="btn btn-primary">Konfirmasi Peminjaman</button>
            </div>
        </form>
    </div>
</div>


<!-- 6. Modal Catat Pengembalian Barang (Multi-Item) -->
<div class="modal-backdrop" id="returnModal">
    <div class="modal-box" style="max-width: 620px;">
        <div class="modal-header">
            <h3 class="modal-title">Formulir Pengembalian Barang</h3>
            <button class="modal-close-btn" onclick="App.closeModal('returnModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <form id="returnForm" onsubmit="App.saveReturn(event)">
            <input type="hidden" id="returnTrxId">
            <div class="modal-body">
                <div style="background: var(--bg-base); padding: 14px 18px; border-radius: var(--radius-md); border: 1px solid var(--border-subtle); margin-bottom: 18px;">
                    <div style="display: flex; justify-content: space-between;">
                        <div>
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Kode Transaksi:</div>
                            <strong id="returnTrxCode" style="color: var(--primary); font-size: 1.05rem;">-</strong>
                        </div>
                        <div style="text-align: right;">
                            <div style="font-size: 0.8rem; color: var(--text-muted);">Peminjam:</div>
                            <div style="font-weight: 600;" id="returnBorrowerName">-</div>
                        </div>
                    </div>

                    <div style="margin-top: 12px; border-top: 1px dashed var(--border-subtle); padding-top: 8px;">
                        <div style="font-size: 0.78rem; font-weight: 700; color: var(--text-secondary); margin-bottom: 4px;">Daftar Barang yang Dipinjam:</div>
                        <ul id="returnItemsListSummary" style="font-size: 0.825rem; padding-left: 20px; color: var(--text-primary); line-height: 1.6;">
                            <!-- Populated dynamically -->
                        </ul>
                    </div>
                </div>

                <div class="form-row">
                    <div class="form-group">
                        <label for="returnActualDate">Tanggal Pengembalian Fisik <span class="required">*</span></label>
                        <input type="date" id="returnActualDate" class="form-control" required>
                    </div>
                    <div class="form-group">
                        <label for="returnCondition">Kondisi Barang Saat Dikembalikan <span class="required">*</span></label>
                        <select id="returnCondition" class="form-control" required>
                            <option value="Baik" selected>Baik (Lengkap & Normal)</option>
                            <option value="Rusak Ringan">Rusak Ringan</option>
                            <option value="Rusak Berat">Rusak Berat (Perlu Diservis)</option>
                            <option value="Hilang">Hilang</option>
                        </select>
                    </div>
                </div>

                <div class="form-group">
                    <label for="returnNotes">Catatan Pengembalian / Pemeriksaan Fisik</label>
                    <textarea id="returnNotes" class="form-control" placeholder="Contoh: Barang telah diperiksa dalam keadaan lengkap dan berfungsi normal."></textarea>
                </div>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" onclick="App.closeModal('returnModal')">Batal</button>
                <button type="submit" class="btn btn-success">Selesaikan & Kembalikan Seluruh Stok</button>
            </div>
        </form>
    </div>
</div>


<!-- 7. Modal Cetak Bukti / Nota Peminjaman -->
<div class="modal-backdrop" id="receiptModal">
    <div class="modal-box" style="max-width: 580px;">
        <div class="modal-header">
            <h3 class="modal-title">Bukti Peminjaman Barang</h3>
            <button class="modal-close-btn" onclick="App.closeModal('receiptModal')">
                <i data-lucide="x" style="width: 20px; height: 20px;"></i>
            </button>
        </div>
        <div class="modal-body">
            <div class="receipt-paper" id="receiptPrintArea">
                <div class="receipt-header">
                    <div class="receipt-title">INVENTAPRO INVENTORY</div>
                    <div>SURAT BUKTI SIRKULASI BARANG</div>
                    <div style="font-size: 0.75rem; color: #64748b;">Dicetak otomatis oleh Sistem Inventaris</div>
                </div>

                <div class="receipt-row">
                    <span>No. Transaksi</span>
                    <strong id="receiptCode">-</strong>
                </div>
                <div class="receipt-row">
                    <span>Tanggal Pinjam</span>
                    <span id="receiptDate">-</span>
                </div>
                <div class="receipt-row">
                    <span>Batas Pengembalian</span>
                    <strong id="receiptExpectedReturn">-</strong>
                </div>

                <div class="receipt-divider"></div>

                <div class="receipt-row">
                    <span>Nama Peminjam</span>
                    <strong id="receiptBorrower">-</strong>
                </div>
                <div class="receipt-row">
                    <span>Divisi/Prodi</span>
                    <span id="receiptDept">-</span>
                </div>
                <div class="receipt-row">
                    <span>No. HP/WA</span>
                    <span id="receiptPhone">-</span>
                </div>

                <div class="receipt-divider"></div>

                <div style="font-weight: bold; margin-bottom: 6px; font-size: 0.825rem;">RINCIAN BARANG DIPINJAM:</div>
                <table style="width: 100%; border-collapse: collapse; font-size: 0.8rem; margin-bottom: 12px;">
                    <thead>
                        <tr style="border-bottom: 1px dashed #94a3b8; text-align: left;">
                            <th style="padding: 4px 0;">Item</th>
                            <th style="padding: 4px 0; text-align: center;">ID/Kode</th>
                            <th style="padding: 4px 0; text-align: right;">Jumlah</th>
                        </tr>
                    </thead>
                    <tbody id="receiptItemsTableBody">
                        <!-- Populated dynamically -->
                    </tbody>
                </table>

                <div class="receipt-row">
                    <span>Status Peminjaman</span>
                    <span id="receiptStatus">-</span>
                </div>
                <div class="receipt-row">
                    <span>Keperluan</span>
                    <span id="receiptPurpose">-</span>
                </div>

                <div class="receipt-divider"></div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; margin-top: 24px; text-align: center; font-size: 0.75rem;">
                    <div>
                        <div>Peminjam,</div>
                        <div style="height: 48px;"></div>
                        <div>( ............................ )</div>
                    </div>
                    <div>
                        <div>Petugas Inventaris,</div>
                        <div style="height: 48px;"></div>
                        <div>( ............................ )</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="modal-footer">
            <button type="button" class="btn btn-secondary" onclick="App.closeModal('receiptModal')">Tutup</button>
            <button type="button" class="btn btn-primary" onclick="App.printReceipt()">
                <i data-lucide="printer" style="width: 16px; height: 16px;"></i> Cetak Nota / Struk
            </button>
        </div>
</div>

<!-- Toast Notifications Container -->
<div class="toast-container" id="toastContainer"></div>

<!-- App Script -->
<script src="assets/js/app.js"></script>
</body>
</html>
