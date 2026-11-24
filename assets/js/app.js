/**
 * SISTEM INFORMASI INVENTARIS BARANG - CORE APPLICATION SCRIPT
 * Handles SPA navigation, real-time filtering, CRUD operations, Chart rendering, and Toasts.
 */

const App = {
    currentView: 'dashboard',
    chartInstance: null,
    categories: [],
    notificationsData: null,
    activeNotifTab: 'overdue',
    hasShownInitialToast: false,

    init() {
        this.setupTheme();
        this.bindEvents();
        this.loadCategories();
        this.loadNotifications(false);
        this.navigateTo('dashboard');
    },

    // Theme Switcher (Dark / Light)
    setupTheme() {
        const savedTheme = localStorage.getItem('inv_theme') || 'light';
        document.documentElement.setAttribute('data-theme', savedTheme);
        this.updateThemeIcon(savedTheme);
    },

    toggleTheme() {
        const current = document.documentElement.getAttribute('data-theme') || 'light';
        const next = current === 'dark' ? 'light' : 'dark';
        document.documentElement.setAttribute('data-theme', next);
        localStorage.setItem('inv_theme', next);
        this.updateThemeIcon(next);
        if (this.chartInstance) {
            this.renderDashboardChart();
        }
    },

    updateThemeIcon(theme) {
        const btn = document.getElementById('themeToggleBtn');
        if (!btn) return;
        btn.innerHTML = theme === 'dark' 
            ? `<i data-lucide="sun" style="width: 18px; height: 18px;"></i>` 
            : `<i data-lucide="moon" style="width: 18px; height: 18px;"></i>`;
        if (window.lucide) lucide.createIcons();
    },

    // Event Listeners
    bindEvents() {
        // Nav items
        document.querySelectorAll('.nav-item[data-view]').forEach(item => {
            item.addEventListener('click', (e) => {
                e.preventDefault();
                const view = item.getAttribute('data-view');
                this.navigateTo(view);
                
                // Close mobile sidebar if open
                document.querySelector('.app-sidebar').classList.remove('mobile-open');
            });
        });

        // Mobile sidebar toggle
        const mobileToggle = document.getElementById('mobileToggleBtn');
        if (mobileToggle) {
            mobileToggle.addEventListener('click', () => {
                document.querySelector('.app-sidebar').classList.toggle('mobile-open');
            });
        }

        // Theme toggle button
        const themeBtn = document.getElementById('themeToggleBtn');
        if (themeBtn) {
            themeBtn.addEventListener('click', () => this.toggleTheme());
        }

        // Modal backdrop click to close
        document.querySelectorAll('.modal-backdrop').forEach(modal => {
            modal.addEventListener('click', (e) => {
                if (e.target === modal) {
                    this.closeModal(modal.id);
                }
            });
        });

        // Search inputs debouncing
        const itemSearch = document.getElementById('itemsSearchInput');
        if (itemSearch) {
            let timeout = null;
            itemSearch.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.loadItems(), 300);
            });
        }

        const borrowerSearch = document.getElementById('borrowersSearchInput');
        if (borrowerSearch) {
            let timeout = null;
            borrowerSearch.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.loadBorrowers(), 300);
            });
        }

        const borrowingSearch = document.getElementById('borrowingSearchInput');
        if (borrowingSearch) {
            let timeout = null;
            borrowingSearch.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.loadBorrowings(), 300);
            });
        }

        const categorySearch = document.getElementById('categoriesSearchInput');
        if (categorySearch) {
            let timeout = null;
            categorySearch.addEventListener('input', () => {
                clearTimeout(timeout);
                timeout = setTimeout(() => this.loadCategoriesView(), 300);
            });
        }

        // Close Notification Dropdown when clicking outside
        document.addEventListener('click', (e) => {
            const wrapper = document.getElementById('notificationWrapper');
            const dropdown = document.getElementById('notifDropdown');
            const toggleBtn = document.getElementById('notifBellBtn');
            if (wrapper && dropdown && !wrapper.contains(e.target)) {
                dropdown.classList.remove('show');
                if (toggleBtn) toggleBtn.classList.remove('active');
            }
        });
    },

    // View Navigation
    navigateTo(viewName) {
        this.currentView = viewName;

        // Update nav active state
        document.querySelectorAll('.nav-item').forEach(el => {
            el.classList.toggle('active', el.getAttribute('data-view') === viewName);
        });

        // Hide all views, show targeted view
        document.querySelectorAll('.view-section').forEach(sec => {
            sec.classList.remove('active');
        });

        const activeSection = document.getElementById(`${viewName}-view`);
        if (activeSection) {
            activeSection.classList.add('active');
        }

        // Refresh icons
        if (window.lucide) lucide.createIcons();

        // Load specific view data
        switch (viewName) {
            case 'dashboard':
                this.loadDashboard();
                break;
            case 'items':
                this.loadItems();
                break;
            case 'categories':
                this.loadCategoriesView();
                break;
            case 'borrowers':
                this.loadBorrowers();
                break;
            case 'borrowings':
                this.loadBorrowings();
                break;
            case 'reports':
                this.loadReportsView();
                break;
        }
    },

    // Modal Helpers
    openModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.add('open');
            if (window.lucide) lucide.createIcons();
        }
    },

    closeModal(modalId) {
        const modal = document.getElementById(modalId);
        if (modal) {
            modal.classList.remove('open');
        }
    },

    // Toast Notifications
    showToast(message, type = 'success') {
        const container = document.getElementById('toastContainer');
        if (!container) return;

        const toast = document.createElement('div');
        toast.className = `toast toast-${type}`;
        
        let iconName = 'check-circle';
        if (type === 'error') iconName = 'alert-triangle';
        if (type === 'warning') iconName = 'alert-octagon';
        if (type === 'info') iconName = 'info';

        toast.innerHTML = `
            <i data-lucide="${iconName}" style="width: 20px; height: 20px; flex-shrink: 0;"></i>
            <span style="flex: 1;">${message}</span>
        `;

        container.appendChild(toast);
        if (window.lucide) lucide.createIcons();

        setTimeout(() => {
            toast.style.opacity = '0';
            toast.style.transform = 'translateX(100%)';
            setTimeout(() => toast.remove(), 250);
        }, 4500);
    },

    /* ==========================================================================
       PUSAT NOTIFIKASI & PERINGATAN (OVERDUE LOANS & OUT OF STOCK ITEMS)
       ========================================================================== */
    toggleNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        const btn = document.getElementById('notifBellBtn');
        if (!dropdown) return;

        const isOpen = dropdown.classList.contains('show');
        if (isOpen) {
            dropdown.classList.remove('show');
            if (btn) btn.classList.remove('active');
        } else {
            dropdown.classList.add('show');
            if (btn) btn.classList.add('active');
            this.renderNotifBody();
        }
    },

    closeNotifDropdown() {
        const dropdown = document.getElementById('notifDropdown');
        const btn = document.getElementById('notifBellBtn');
        if (dropdown) dropdown.classList.remove('show');
        if (btn) btn.classList.remove('active');
    },

    switchNotifTab(tabName) {
        this.activeNotifTab = tabName;
        const tabOverdue = document.getElementById('tabBtnOverdue');
        const tabOutOfStock = document.getElementById('tabBtnOutOfStock');

        if (tabOverdue) tabOverdue.classList.toggle('active', tabName === 'overdue');
        if (tabOutOfStock) tabOutOfStock.classList.toggle('active', tabName === 'outofstock');

        this.renderNotifBody();
    },

    async loadNotifications(forceToast = false) {
        try {
            const res = await fetch('api/notifications.php');
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.notificationsData = result.data;
            const data = result.data;

            // 1. Update Bell Counter Badge
            const badge = document.getElementById('notifBadgeCount');
            if (badge) {
                if (data.total_alerts > 0) {
                    badge.style.display = 'inline-block';
                    badge.textContent = data.total_alerts > 99 ? '99+' : data.total_alerts;
                } else {
                    badge.style.display = 'none';
                    badge.textContent = '0';
                }
            }

            // 2. Update Tabs Count Pills & Summary
            const tabOverdue = document.getElementById('tabOverdueCount');
            if (tabOverdue) tabOverdue.textContent = data.overdue_count;

            const tabOutOfStock = document.getElementById('tabOutOfStockCount');
            if (tabOutOfStock) tabOutOfStock.textContent = data.out_of_stock_count;

            const summaryEl = document.getElementById('notifSubSummary');
            if (summaryEl) {
                if (data.total_alerts === 0) {
                    summaryEl.textContent = 'Semua stok tersedia & peminjaman aman';
                } else {
                    summaryEl.textContent = `${data.overdue_count} jatuh tempo • ${data.out_of_stock_count} stok kosong`;
                }
            }

            // 3. Render Dropdown Body if open
            this.renderNotifBody();

            // 4. Render Dashboard Alert Banners
            this.renderDashboardAlerts(data);

            // 5. Update Items View & Borrowings View Banners
            this.updatePageAlertBanners(data);

            // 6. Automatic Toast Notification on First Visit or Refresh
            if (!this.hasShownInitialToast || forceToast) {
                this.hasShownInitialToast = true;

                if (data.overdue_count > 0) {
                    this.showToast(`⏰ Perhatian: Terdapat ${data.overdue_count} transaksi peminjaman yang JATUH TEMPO / terlambat!`, 'error');
                }

                if (data.out_of_stock_count > 0) {
                    setTimeout(() => {
                        this.showToast(`⚠️ Peringatan: Terdapat ${data.out_of_stock_count} barang saat ini TIDAK TERSEDIA (stok 0)!`, 'warning');
                    }, 500);
                }
            }

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error('Gagal memuat notifikasi:', err);
        }
    },

    renderNotifBody() {
        const body = document.getElementById('notifBody');
        if (!body) return;

        const data = this.notificationsData;
        if (!data) {
            body.innerHTML = `<div class="notif-empty"><p>Memuat notifikasi...</p></div>`;
            return;
        }

        if (this.activeNotifTab === 'overdue') {
            const list = data.overdue_borrowings || [];
            if (list.length === 0) {
                body.innerHTML = `
                    <div class="notif-empty">
                        <i data-lucide="check-circle" style="width: 34px; height: 34px; color: var(--success); margin: 0 auto 8px; display: block;"></i>
                        <p style="font-weight: 600; color: var(--text-primary);">Tidak Ada Peminjaman Jatuh Tempo</p>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Semua transaksi peminjaman barang berjalan dengan aman dan tepat waktu.</p>
                    </div>
                `;
            } else {
                body.innerHTML = list.map(trx => {
                    const isToday = parseInt(trx.days_overdue) === 0;
                    const badgePill = isToday ? 'pill-warning' : 'pill-danger';
                    const itemsSummary = trx.items_summary || 'Barang pinjaman';
                    
                    // Format phone for WhatsApp direct contact
                    const rawPhone = (trx.borrower_phone || '').replace(/[^0-9]/g, '');
                    const waPhone = rawPhone.startsWith('0') ? '62' + rawPhone.slice(1) : rawPhone;
                    const waMsg = encodeURIComponent(`Halo ${trx.borrower_name}, ini dari Tim Pengelola Inventaris. Peminjaman barang (${itemsSummary}) dengan No. Transaksi ${trx.code} telah jatuh tempo pada tanggal ${trx.expected_return_date}. Mohon konfirmasi atau segera kembalikan ke bagian inventaris. Terima kasih.`);

                    return `
                        <div class="notif-card card-overdue">
                            <div class="notif-card-header">
                                <span class="notif-card-code">${trx.code}</span>
                                <span class="notif-pill ${badgePill}">
                                    ${trx.overdue_label}
                                </span>
                            </div>
                            <div class="notif-card-title">${trx.borrower_name}</div>
                            <div class="notif-card-meta">
                                <div><strong>NIM/Dept:</strong> ${trx.borrower_nim} &bull; ${trx.borrower_dept}</div>
                                <div><strong>Barang Dipinjam:</strong> ${itemsSummary}</div>
                                <div><strong>Batas Kembali:</strong> <span style="color: var(--danger); font-weight: 600;">${trx.expected_return_date}</span> (Pinjam: ${trx.borrow_date})</div>
                            </div>
                            <div class="notif-card-actions">
                                ${waPhone ? `
                                    <a href="https://wa.me/${waPhone}?text=${waMsg}" target="_blank" class="btn btn-sm btn-secondary" style="font-size: 0.72rem; padding: 4px 8px;" title="Kirim Pengingat via WhatsApp">
                                        <i data-lucide="message-circle" style="width: 13px; height: 13px; color: var(--success);"></i>
                                        <span>Chat WA</span>
                                    </a>
                                ` : ''}
                                <button class="btn btn-sm btn-success" style="font-size: 0.72rem; padding: 4px 8px;" onclick="App.closeNotifDropdown(); App.openReturnModal(${trx.id}, '${trx.code}', '${trx.borrower_name}')">
                                    <i data-lucide="corner-down-left" style="width: 13px; height: 13px;"></i>
                                    <span>Kembalikan</span>
                                </button>
                            </div>
                        </div>
                    `;
                }).join('');
            }
        } else {
            // Out of stock tab
            const list = data.out_of_stock_items || [];
            if (list.length === 0) {
                body.innerHTML = `
                    <div class="notif-empty">
                        <i data-lucide="check-circle" style="width: 34px; height: 34px; color: var(--success); margin: 0 auto 8px; display: block;"></i>
                        <p style="font-weight: 600; color: var(--text-primary);">Semua Barang Tersedia</p>
                        <p style="font-size: 0.75rem; color: var(--text-muted); margin-top: 4px;">Tidak ada katalog barang dengan sisa stok 0 unit.</p>
                    </div>
                `;
            } else {
                body.innerHTML = list.map(item => `
                    <div class="notif-card card-outofstock">
                        <div class="notif-card-header">
                            <span class="notif-card-code">${item.code}</span>
                            <span class="notif-pill pill-danger">
                                STOK KOSONG (0 Unit)
                            </span>
                        </div>
                        <div class="notif-card-title">${item.name}</div>
                        <div class="notif-card-meta">
                            <div><strong>Kategori:</strong> ${item.category_name}</div>
                            <div><strong>Lokasi:</strong> ${item.location || '-'} &bull; <strong>Total Aset:</strong> ${item.total_stock} Unit</div>
                        </div>
                        <div class="notif-card-actions">
                            <button class="btn btn-sm btn-secondary" style="font-size: 0.72rem; padding: 4px 8px;" onclick="App.closeNotifDropdown(); App.viewItemDetail('${item.id}')">
                                <i data-lucide="eye" style="width: 13px; height: 13px;"></i>
                                <span>Rincian</span>
                            </button>
                            <button class="btn btn-sm btn-primary" style="font-size: 0.72rem; padding: 4px 8px;" onclick="App.closeNotifDropdown(); App.openEditItemModal('${item.id}')">
                                <i data-lucide="edit-3" style="width: 13px; height: 13px;"></i>
                                <span>Tambah Stok</span>
                            </button>
                        </div>
                    </div>
                `).join('');
            }
        }

        if (window.lucide) lucide.createIcons();
    },

    renderDashboardAlerts(notifData) {
        const container = document.getElementById('dashboardAlertsContainer');
        if (!container) return;

        if (!notifData || (notifData.overdue_count === 0 && notifData.out_of_stock_count === 0)) {
            container.innerHTML = '';
            container.style.display = 'none';
            return;
        }

        container.style.display = 'block';
        let html = '';

        if (notifData.overdue_count > 0) {
            html += `
                <div class="notif-banner banner-danger">
                    <div class="notif-banner-content">
                        <div class="notif-banner-icon">
                            <i data-lucide="clock" style="width: 24px; height: 24px; color: var(--danger);"></i>
                        </div>
                        <div>
                            <div class="notif-banner-title">Peringatan Jatuh Tempo: Terdapat ${notifData.overdue_count} Transaksi Peminjaman Melewati Batas Waktu!</div>
                            <div class="notif-banner-desc">Barang belum dikembalikan oleh peminjam sesuai estimasi jadwal. Segera hubungi peminjam atau proses pencatatan pengembalian unit.</div>
                        </div>
                    </div>
                    <div class="notif-banner-actions">
                        <button class="btn btn-sm btn-danger" onclick="App.navigateTo('borrowings'); document.getElementById('borrowingStatusFilter').value = 'Terlambat'; App.loadBorrowings();">
                            <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                            <span>Tinjau Jatuh Tempo</span>
                        </button>
                    </div>
                </div>
            `;
        }

        if (notifData.out_of_stock_count > 0) {
            html += `
                <div class="notif-banner banner-warning">
                    <div class="notif-banner-content">
                        <div class="notif-banner-icon">
                            <i data-lucide="alert-octagon" style="width: 24px; height: 24px; color: var(--warning);"></i>
                        </div>
                        <div>
                            <div class="notif-banner-title">Peringatan Ketersediaan: Terdapat ${notifData.out_of_stock_count} Barang Sedang Tidak Tersedia!</div>
                            <div class="notif-banner-desc">Sisa stok tersedia saat ini adalah 0 unit. Barang tidak dapat dipinjam oleh pengguna hingga unit fisik ditambah atau barang dikembalikan.</div>
                        </div>
                    </div>
                    <div class="notif-banner-actions">
                        <button class="btn btn-sm btn-secondary" onclick="App.navigateTo('items'); document.getElementById('itemStockFilter').value = 'empty'; App.loadItems();">
                            <i data-lucide="arrow-right" style="width: 14px; height: 14px;"></i>
                            <span>Lihat Barang Habis</span>
                        </button>
                    </div>
                </div>
            `;
        }

        container.innerHTML = html;
        if (window.lucide) lucide.createIcons();
    },

    updatePageAlertBanners(data) {
        // Items View Banner
        const itemsBanner = document.getElementById('itemsAlertBanner');
        if (itemsBanner) {
            if (data.out_of_stock_count > 0) {
                itemsBanner.style.display = 'block';
                itemsBanner.innerHTML = `
                    <div class="notif-banner banner-warning">
                        <div class="notif-banner-content">
                            <div class="notif-banner-icon"><i data-lucide="alert-triangle" style="width: 22px; height: 22px; color: var(--warning);"></i></div>
                            <div>
                                <div class="notif-banner-title">Peringatan: Terdapat ${data.out_of_stock_count} Barang dengan Stok Kosong / Tidak Tersedia!</div>
                                <div class="notif-banner-desc">Barang dengan sisa stok 0 unit tidak dapat dipinjam. Silakan lakukan penambahan unit fisik atau edit stok barang.</div>
                            </div>
                        </div>
                        <div class="notif-banner-actions">
                            <button class="btn btn-sm btn-secondary" onclick="document.getElementById('itemStockFilter').value='empty'; App.loadItems();">
                                <span>Filter Stok Habis</span>
                            </button>
                        </div>
                    </div>
                `;
            } else {
                itemsBanner.style.display = 'none';
                itemsBanner.innerHTML = '';
            }
        }

        // Borrowings View Banner
        const borrowingsBanner = document.getElementById('borrowingsAlertBanner');
        if (borrowingsBanner) {
            if (data.overdue_count > 0) {
                borrowingsBanner.style.display = 'block';
                borrowingsBanner.innerHTML = `
                    <div class="notif-banner banner-danger">
                        <div class="notif-banner-content">
                            <div class="notif-banner-icon"><i data-lucide="clock" style="width: 22px; height: 22px; color: var(--danger);"></i></div>
                            <div>
                                <div class="notif-banner-title">Peringatan: Terdapat ${data.overdue_count} Transaksi Peminjaman Jatuh Tempo / Terlambat!</div>
                                <div class="notif-banner-desc">Peminjam telah melewati tanggal estimasi pengembalian barang. Harap segera hubungi peminjam atau proses pengembalian unit.</div>
                            </div>
                        </div>
                        <div class="notif-banner-actions">
                            <button class="btn btn-sm btn-danger" onclick="document.getElementById('borrowingStatusFilter').value='Terlambat'; App.loadBorrowings();">
                                <span>Tampilkan Jatuh Tempo</span>
                            </button>
                        </div>
                    </div>
                `;
            } else {
                borrowingsBanner.style.display = 'none';
                borrowingsBanner.innerHTML = '';
            }
        }
    },

    viewAllNotifications() {
        this.closeNotifDropdown();
        if (this.activeNotifTab === 'overdue') {
            this.navigateTo('borrowings');
            const filter = document.getElementById('borrowingStatusFilter');
            if (filter) {
                filter.value = 'Terlambat';
                this.loadBorrowings();
            }
        } else {
            this.navigateTo('items');
            const filter = document.getElementById('itemStockFilter');
            if (filter) {
                filter.value = 'empty';
                this.loadItems();
            }
        }
    },

    // Load Categories from API
    async loadCategories() {
        try {
            const res = await fetch('api/items.php?action=categories');
            const data = await res.json();
            if (data.status === 'success') {
                this.categories = data.data;
                this.populateCategoryDropdowns();
            }
        } catch (err) {
            console.error('Failed to load categories', err);
        }
    },

    populateCategoryDropdowns() {
        const filterSelect = document.getElementById('itemCategoryFilter');
        const modalSelect = document.getElementById('itemModalCategory');

        if (filterSelect) {
            let options = '<option value="">Semua Kategori</option>';
            this.categories.forEach(c => {
                options += `<option value="${c.id}">${c.name}</option>`;
            });
            filterSelect.innerHTML = options;
        }

        if (modalSelect) {
            let options = '<option value="" disabled selected>Pilih Kategori Barang</option>';
            this.categories.forEach(c => {
                options += `<option value="${c.id}">${c.name}</option>`;
            });
            modalSelect.innerHTML = options;
        }
    },

    /* ==========================================================================
       DASHBOARD LOGIC
       ========================================================================== */
    async loadDashboard() {
        try {
            const res = await fetch('api/dashboard.php');
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const { metrics, categories, low_stock_items, recent_active } = result.data;

            // Update Metrics Cards
            document.getElementById('statTotalItems').textContent = metrics.total_items;
            document.getElementById('statTotalUnits').textContent = metrics.total_units + ' Unit';
            document.getElementById('statAvailableUnits').textContent = metrics.available_units + ' Unit';
            document.getElementById('statBorrowedUnits').textContent = metrics.borrowed_units + ' Unit';
            document.getElementById('statTotalBorrowers').textContent = metrics.total_borrowers;
            
            const overdueEl = document.getElementById('statOverdueBadge');
            if (overdueEl) {
                if (metrics.overdue_loans > 0) {
                    overdueEl.innerHTML = `<span class="badge-status badge-overdue">${metrics.overdue_loans} Terlambat</span>`;
                } else {
                    overdueEl.innerHTML = `<span class="badge-status badge-available">Semua Aman</span>`;
                }
            }

            // Render Low Stock Warning Banner
            const lowStockContainer = document.getElementById('dashboardLowStockList');
            if (lowStockContainer) {
                if (low_stock_items.length === 0) {
                    lowStockContainer.innerHTML = `<div style="color: var(--text-muted); font-size: 0.85rem; padding: 8px 0;">Stok seluruh barang dalam kondisi aman.</div>`;
                } else {
                    lowStockContainer.innerHTML = low_stock_items.map(item => {
                        const isZero = parseInt(item.available_stock) <= 0;
                        const badgeHtml = isZero
                            ? `<span class="badge-status" style="font-size: 0.72rem; background: var(--danger); color: #fff; font-weight: 700;">TIDAK TERSEDIA (0 Unit)</span>`
                            : `<span class="badge-status badge-overdue" style="font-size: 0.72rem;">Sisa ${item.available_stock} dari ${item.total_stock}</span>`;
                        return `
                            <div style="display: flex; align-items: center; justify-content: space-between; padding: 10px 0; border-bottom: 1px solid var(--border-subtle);">
                                <div>
                                    <div style="font-weight: 600; font-size: 0.85rem; color: var(--text-primary);">${item.name}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${item.code} &bull; ${item.category_name}</div>
                                </div>
                                ${badgeHtml}
                            </div>
                        `;
                    }).join('');
                }
            }

            // Render Recent Active Borrowings Table
            const recentTableBody = document.getElementById('dashboardRecentActiveBody');
            if (recentTableBody) {
                if (recent_active.length === 0) {
                    recentTableBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted); padding: 24px;">Tidak ada transaksi peminjaman aktif saat ini.</td></tr>`;
                } else {
                    recentTableBody.innerHTML = recent_active.map(trx => {
                        const isOverdue = trx.status === 'Terlambat';
                        const itemsDisplay = trx.item_names || 'Rincian barang';
                        return `
                            <tr class="${isOverdue ? 'row-overdue' : ''}">
                                <td>
                                    <strong>${trx.code}</strong>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${trx.borrow_date}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600;">${trx.borrower_name}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${trx.department}</div>
                                </td>
                                <td>
                                    <div style="font-weight: 600; font-size: 0.85rem; max-width: 280px;">${itemsDisplay}</div>
                                    <div style="font-size: 0.75rem; color: var(--text-muted);">${trx.item_types_count || 1} jenis barang</div>
                                </td>
                                <td>
                                    <span style="font-weight: 700;">${trx.total_quantity}</span> Unit
                                </td>
                                <td>
                                    <span class="badge-status ${isOverdue ? 'badge-overdue' : 'badge-borrowed'}">
                                        ${isOverdue ? '⏰ Terlambat' : trx.status} (${trx.expected_return_date})
                                    </span>
                                </td>
                                <td>
                                    <button class="btn btn-sm btn-success" onclick="App.openReturnModal(${trx.id}, '${trx.code}', '${trx.borrower_name}')">
                                        <i data-lucide="corner-down-left" style="width: 14px; height: 14px;"></i> Kembalikan
                                    </button>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }

            // Sync notifications system
            this.loadNotifications(false);

            // Render Chart.js
            this.renderCategoryChart(categories);

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error('Error loading dashboard:', err);
            this.showToast('Gagal memuat data dashboard: ' + err.message, 'error');
        }
    },

    renderCategoryChart(categories) {
        const ctx = document.getElementById('categoryPieChart');
        if (!ctx) return;

        if (this.chartInstance) {
            this.chartInstance.destroy();
        }

        const labels = categories.map(c => c.name);
        const dataUnits = categories.map(c => c.total_units);
        const colors = [
            '#3b82f6', '#10b981', '#f59e0b', '#8b5cf6', '#ec4899', '#06b6d4', '#64748b'
        ];

        this.chartInstance = new Chart(ctx, {
            type: 'doughnut',
            data: {
                labels: labels,
                datasets: [{
                    data: dataUnits,
                    backgroundColor: colors.slice(0, labels.length),
                    borderWidth: 2,
                    borderColor: document.documentElement.getAttribute('data-theme') === 'dark' ? '#111827' : '#ffffff'
                }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                plugins: {
                    legend: {
                        position: 'bottom',
                        labels: {
                            boxWidth: 12,
                            padding: 14,
                            font: { family: "'Plus Jakarta Sans', sans-serif", size: 11 },
                            color: document.documentElement.getAttribute('data-theme') === 'dark' ? '#9ca3af' : '#475569'
                        }
                    }
                },
                cutout: '68%'
            }
        });
    },

    /* ==========================================================================
       MANAJEMEN BARANG (CRUD & SEARCH & FILTER)
       ========================================================================== */
    async loadItems() {
        const search = document.getElementById('itemsSearchInput')?.value || '';
        const categoryId = document.getElementById('itemCategoryFilter')?.value || '';
        const stockFilter = document.getElementById('itemStockFilter')?.value || 'all';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (categoryId) params.append('category_id', categoryId);
        if (stockFilter !== 'all') params.append('stock', stockFilter);

        try {
            const res = await fetch(`api/items.php?${params.toString()}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const items = result.data;
            const tbody = document.getElementById('itemsTableBody');
            const totalBadge = document.getElementById('itemsCountBadge');
            if (totalBadge) totalBadge.textContent = `${items.length} Barang`;

            if (!tbody) return;

            if (items.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Tidak ditemukan data barang yang sesuai kriteria pencarian.</td></tr>`;
                return;
            }

            // Check and update Items Alert Banner
            const outOfStockItems = items.filter(it => parseInt(it.available_stock) <= 0);
            const itemsBanner = document.getElementById('itemsAlertBanner');
            if (itemsBanner) {
                if (outOfStockItems.length > 0) {
                    itemsBanner.style.display = 'block';
                    itemsBanner.innerHTML = `
                        <div class="notif-banner banner-warning">
                            <div class="notif-banner-content">
                                <div class="notif-banner-icon"><i data-lucide="alert-triangle" style="width: 22px; height: 22px; color: var(--warning);"></i></div>
                                <div>
                                    <div class="notif-banner-title">Peringatan Ketersediaan: Terdapat ${outOfStockItems.length} Barang Tidak Tersedia!</div>
                                    <div class="notif-banner-desc">Barang dengan stok 0 unit otomatis tidak dapat dipinjam oleh siapapun hingga stok fisik ditambah.</div>
                                </div>
                            </div>
                            <div class="notif-banner-actions">
                                <button class="btn btn-sm btn-secondary" onclick="document.getElementById('itemStockFilter').value='empty'; App.loadItems();">
                                    <span>Filter Stok Habis (0)</span>
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    itemsBanner.style.display = 'none';
                    itemsBanner.innerHTML = '';
                }
            }

            tbody.innerHTML = items.map(item => {
                const isAvailable = parseInt(item.available_stock) > 0;
                const percent = item.total_stock > 0 ? (item.available_stock / item.total_stock) * 100 : 0;
                let barColor = 'var(--success)';
                if (percent === 0) barColor = 'var(--danger)';
                else if (percent <= 30) barColor = 'var(--warning)';

                return `
                    <tr class="${!isAvailable ? 'row-overdue' : ''}">
                        <td>
                            <div style="font-weight: 700; color: var(--primary);">${item.code}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${item.location || '-'}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.9rem;">${item.name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted); max-width: 280px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;">${item.description || '-'}</div>
                        </td>
                        <td>
                            <span class="badge-category">${item.category_name}</span>
                        </td>
                        <td>
                            <div class="stock-bar-wrapper">
                                <span style="font-weight: 700; font-size: 0.85rem; color: ${isAvailable ? 'inherit' : 'var(--danger)'};">${item.available_stock}</span>
                                <span style="font-size: 0.75rem; color: var(--text-muted);">/ ${item.total_stock}</span>
                                <div class="stock-track">
                                    <div class="stock-fill" style="width: ${percent}%; background-color: ${barColor};"></div>
                                </div>
                            </div>
                        </td>
                        <td>
                            <span class="badge-status ${isAvailable ? 'badge-available' : 'badge-overdue'}" style="${!isAvailable ? 'font-weight: 700;' : ''}">
                                ${isAvailable ? 'Tersedia' : '<i data-lucide="alert-octagon" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 2px;"></i> Tidak Tersedia'}
                            </span>
                        </td>
                        <td>
                            <span class="badge-status badge-neutral">${item.condition || 'Baik'}</span>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px;">
                                <button class="btn btn-sm btn-secondary btn-icon-only" title="Lihat Detail & Riwayat" onclick="App.viewItemDetail('${item.id}')">
                                    <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                </button>
                                <button class="btn btn-sm btn-secondary btn-icon-only" title="Ubah Data" onclick="App.openEditItemModal('${item.id}')">
                                    <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-icon-only" title="Hapus Barang" onclick="App.deleteItem('${item.id}', '${item.name}')">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error(err);
            this.showToast('Gagal memuat barang: ' + err.message, 'error');
        }
    },

    openAddItemModal() {
        document.getElementById('itemModalTitle').textContent = 'Tambah Data Barang Baru';
        document.getElementById('itemForm').reset();
        document.getElementById('itemIdInput').value = '';
        this.openModal('itemModal');
    },

    async openEditItemModal(id) {
        try {
            const res = await fetch(`api/items.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const item = result.data;
            document.getElementById('itemModalTitle').textContent = 'Ubah Data Barang';
            document.getElementById('itemIdInput').value = item.id;
            document.getElementById('itemModalCode').value = item.code;
            document.getElementById('itemModalName').value = item.name;
            document.getElementById('itemModalCategory').value = item.category_id;
            document.getElementById('itemModalStock').value = item.total_stock;
            document.getElementById('itemModalLocation').value = item.location;
            document.getElementById('itemModalCondition').value = item.condition;
            document.getElementById('itemModalDesc').value = item.description || '';

            this.openModal('itemModal');
        } catch (err) {
            this.showToast('Gagal memuat rincian barang: ' + err.message, 'error');
        }
    },

    async saveItem(e) {
        e.preventDefault();
        const id = document.getElementById('itemIdInput').value;
        const isEdit = Boolean(id);

        const payload = {
            action: isEdit ? 'update' : 'create',
            id: id ? parseInt(id) : undefined,
            code: document.getElementById('itemModalCode').value.trim(),
            name: document.getElementById('itemModalName').value.trim(),
            category_id: parseInt(document.getElementById('itemModalCategory').value),
            total_stock: parseInt(document.getElementById('itemModalStock').value),
            location: document.getElementById('itemModalLocation').value.trim(),
            condition: document.getElementById('itemModalCondition').value,
            description: document.getElementById('itemModalDesc').value.trim()
        };

        try {
            const res = await fetch('api/items.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();

            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.closeModal('itemModal');
            this.loadItems();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async deleteItem(id, name) {
        if (!confirm(`Apakah Anda yakin ingin menghapus barang "${name}" dari sistem inventaris?`)) {
            return;
        }

        try {
            const res = await fetch('api/items.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.loadItems();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async viewItemDetail(id) {
        try {
            const res = await fetch(`api/items.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const item = result.data;
            document.getElementById('itemDetailCode').textContent = item.code;
            document.getElementById('itemDetailName').textContent = item.name;
            document.getElementById('itemDetailCategory').textContent = item.category_name;
            document.getElementById('itemDetailStock').textContent = `${item.available_stock} tersedia dari ${item.total_stock} total unit`;
            document.getElementById('itemDetailLocation').textContent = item.location || '-';
            document.getElementById('itemDetailCondition').textContent = item.condition || '-';
            document.getElementById('itemDetailDesc').textContent = item.description || '-';

            const historyBody = document.getElementById('itemDetailHistoryBody');
            if (item.history && item.history.length > 0) {
                historyBody.innerHTML = item.history.map(h => `
                    <tr>
                        <td><strong>${h.code}</strong></td>
                        <td>${h.borrower_name}</td>
                        <td>${h.quantity} Unit</td>
                        <td>${h.borrow_date}</td>
                        <td>${h.actual_return_date || h.expected_return_date}</td>
                        <td><span class="badge-status ${h.status === 'Kembali' ? 'badge-returned' : (h.status === 'Terlambat' ? 'badge-overdue' : 'badge-borrowed')}">${h.status}</span></td>
                    </tr>
                `).join('');
            } else {
                historyBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Belum ada riwayat peminjaman untuk barang ini.</td></tr>`;
            }

            this.openModal('itemDetailModal');
        } catch (err) {
            this.showToast('Gagal memuat detail barang: ' + err.message, 'error');
        }
    },

    /* ==========================================================================
       MANAJEMEN KATEGORI (CATEGORIES CRUD)
       ========================================================================== */
    async loadCategoriesView() {
        const search = document.getElementById('categoriesSearchInput')?.value || '';
        const params = new URLSearchParams();
        if (search) params.append('search', search);

        try {
            const res = await fetch(`api/categories.php?${params.toString()}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const categories = result.data;
            const badge = document.getElementById('categoriesCountBadge');
            if (badge) badge.textContent = `${categories.length} Kategori`;

            // 1. Render Cards Grid
            const cardsContainer = document.getElementById('categoryCardsContainer');
            if (cardsContainer) {
                if (categories.length === 0) {
                    cardsContainer.innerHTML = `<div style="grid-column: 1 / -1; text-align: center; color: var(--text-muted); padding: 32px; background: var(--bg-surface); border-radius: var(--radius-lg); border: 1px solid var(--border-subtle);">Tidak ditemukan kategori barang.</div>`;
                } else {
                    cardsContainer.innerHTML = categories.map(cat => {
                        const icon = cat.icon || 'box';
                        return `
                            <div class="category-card">
                                <div class="category-card-top">
                                    <div class="category-icon-box">
                                        <i data-lucide="${icon}" style="width: 24px; height: 24px;"></i>
                                    </div>
                                    <div class="category-card-actions">
                                        <button class="btn btn-sm btn-secondary btn-icon-only" title="Lihat Daftar Barang" onclick="App.viewCategoryDetail(${cat.id})">
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary btn-icon-only" title="Ubah Kategori" onclick="App.openEditCategoryModal(${cat.id})">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-icon-only" title="Hapus Kategori" onclick="App.deleteCategory(${cat.id}, '${cat.name}', ${cat.item_count})">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                        </button>
                                    </div>
                                </div>
                                <div>
                                    <div class="category-card-title">${cat.name}</div>
                                    <div class="category-card-desc">${cat.description || 'Tidak ada keterangan khusus.'}</div>
                                </div>
                                <div class="category-card-stats">
                                    <div style="flex: 1;">
                                        <strong>${cat.item_count}</strong> Jenis Barang
                                    </div>
                                    <div>
                                        <span class="badge-status badge-available" style="font-size: 0.72rem;">${cat.available_units} / ${cat.total_units} Unit Ready</span>
                                    </div>
                                </div>
                            </div>
                        `;
                    }).join('');
                }
            }

            // 2. Render Table View
            const tbody = document.getElementById('categoriesTableBody');
            if (tbody) {
                if (categories.length === 0) {
                    tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 24px;">Tidak ada data kategori.</td></tr>`;
                } else {
                    tbody.innerHTML = categories.map(cat => {
                        const icon = cat.icon || 'box';
                        return `
                            <tr>
                                <td>
                                    <div style="display: flex; align-items: center; gap: 10px;">
                                        <div class="category-icon-box" style="width: 34px; height: 34px; border-radius: var(--radius-sm);">
                                            <i data-lucide="${icon}" style="width: 18px; height: 18px;"></i>
                                        </div>
                                        <div style="font-weight: 700; font-size: 0.9rem;">${cat.name}</div>
                                    </div>
                                </td>
                                <td>
                                    <div style="font-size: 0.8rem; color: var(--text-secondary); max-width: 320px;">
                                        ${cat.description || '-'}
                                    </div>
                                </td>
                                <td>
                                    <span style="font-weight: 700; color: var(--primary);">${cat.item_count}</span> Jenis Barang
                                </td>
                                <td>
                                    <span style="font-weight: 700;">${cat.total_units}</span> Unit Fisik
                                    <div style="font-size: 0.72rem; color: var(--text-muted);">${cat.available_units} Tersedia</div>
                                </td>
                                <td>
                                    <div style="display: flex; gap: 6px;">
                                        <button class="btn btn-sm btn-secondary btn-icon-only" title="Lihat Barang" onclick="App.viewCategoryDetail(${cat.id})">
                                            <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-secondary btn-icon-only" title="Ubah Kategori" onclick="App.openEditCategoryModal(${cat.id})">
                                            <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                                        </button>
                                        <button class="btn btn-sm btn-danger btn-icon-only" title="Hapus Kategori" onclick="App.deleteCategory(${cat.id}, '${cat.name}', ${cat.item_count})">
                                            <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                        </button>
                                    </div>
                                </td>
                            </tr>
                        `;
                    }).join('');
                }
            }

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error('Error loading categories:', err);
            this.showToast('Gagal memuat kategori: ' + err.message, 'error');
        }
    },

    openAddCategoryModal() {
        document.getElementById('categoryModalTitle').textContent = 'Tambah Kategori Barang Baru';
        document.getElementById('categoryForm').reset();
        document.getElementById('categoryIdInput').value = '';
        this.openModal('categoryModal');
    },

    async openEditCategoryModal(id) {
        try {
            const res = await fetch(`api/categories.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const cat = result.data;
            document.getElementById('categoryModalTitle').textContent = 'Ubah Kategori Barang';
            document.getElementById('categoryIdInput').value = cat.id;
            document.getElementById('categoryModalName').value = cat.name;
            document.getElementById('categoryModalIcon').value = cat.icon || 'box';
            document.getElementById('categoryModalDesc').value = cat.description || '';

            this.openModal('categoryModal');
        } catch (err) {
            this.showToast('Gagal memuat data kategori: ' + err.message, 'error');
        }
    },

    async saveCategory(e) {
        e.preventDefault();
        const id = document.getElementById('categoryIdInput').value;
        const isEdit = Boolean(id);

        const payload = {
            action: isEdit ? 'update' : 'create',
            id: id ? parseInt(id) : undefined,
            name: document.getElementById('categoryModalName').value.trim(),
            icon: document.getElementById('categoryModalIcon').value,
            description: document.getElementById('categoryModalDesc').value.trim()
        };

        try {
            const res = await fetch('api/categories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.closeModal('categoryModal');

            // Refresh category list, global dropdowns, and dashboard chart
            await this.loadCategories();
            this.loadCategoriesView();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async deleteCategory(id, name, itemCount) {
        if (itemCount > 0) {
            this.showToast(`Kategori "${name}" tidak bisa dihapus karena masih digunakan oleh ${itemCount} barang!`, 'error');
            return;
        }

        if (!confirm(`Hapus kategori "${name}"?`)) return;

        try {
            const res = await fetch('api/categories.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            await this.loadCategories();
            this.loadCategoriesView();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async viewCategoryDetail(id) {
        try {
            const res = await fetch(`api/categories.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const cat = result.data;
            document.getElementById('categoryDetailName').textContent = cat.name;
            document.getElementById('categoryDetailDesc').textContent = cat.description || 'Tidak ada keterangan';
            
            const iconBox = document.getElementById('categoryDetailIconBox');
            if (iconBox) {
                iconBox.innerHTML = `<i data-lucide="${cat.icon || 'folder'}" style="width: 20px; height: 20px;"></i>`;
            }

            const tbody = document.getElementById('categoryDetailItemsBody');
            if (cat.items && cat.items.length > 0) {
                tbody.innerHTML = cat.items.map(it => `
                    <tr>
                        <td><strong>${it.code}</strong></td>
                        <td>${it.name}</td>
                        <td><span style="font-weight: 700; color: ${it.available_stock > 0 ? 'var(--success)' : 'var(--danger)'};">${it.available_stock}</span> / ${it.total_stock} Unit</td>
                        <td>${it.location || '-'}</td>
                        <td><span class="badge-status badge-neutral">${it.condition || 'Baik'}</span></td>
                    </tr>
                `).join('');
            } else {
                tbody.innerHTML = `<tr><td colspan="5" style="text-align: center; color: var(--text-muted); padding: 18px;">Belum ada barang di kategori ini.</td></tr>`;
            }

            this.openModal('categoryDetailModal');
            if (window.lucide) lucide.createIcons();
        } catch (err) {
            this.showToast('Gagal memuat rincian kategori: ' + err.message, 'error');
        }
    },

    /* ==========================================================================
       MANAJEMEN PEMINJAM (BORROWERS CRUD)
       ========================================================================== */
    async loadBorrowers() {
        const search = document.getElementById('borrowersSearchInput')?.value || '';
        const status = document.getElementById('borrowerStatusFilter')?.value || 'all';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status !== 'all') params.append('status', status);

        try {
            const res = await fetch(`api/borrowers.php?${params.toString()}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const borrowers = result.data;
            const tbody = document.getElementById('borrowersTableBody');
            const totalBadge = document.getElementById('borrowersCountBadge');
            if (totalBadge) totalBadge.textContent = `${borrowers.length} Peminjam`;

            if (!tbody) return;

            if (borrowers.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Tidak ditemukan data peminjam.</td></tr>`;
                return;
            }

            tbody.innerHTML = borrowers.map(b => `
                <tr>
                    <td>
                        <strong style="color: var(--primary);">${b.identity_number}</strong>
                    </td>
                    <td>
                        <div style="font-weight: 600;">${b.name}</div>
                        <div style="font-size: 0.75rem; color: var(--text-muted);">${b.email || '-'}</div>
                    </td>
                    <td>
                        <div>${b.phone}</div>
                    </td>
                    <td>
                        <span class="badge-category">${b.department}</span>
                    </td>
                    <td>
                        <span class="badge-status ${b.status === 'Aktif' ? 'badge-available' : 'badge-overdue'}">${b.status}</span>
                    </td>
                    <td>
                        <span style="font-weight: 600; color: ${b.active_loans_count > 0 ? 'var(--info)' : 'var(--text-muted)'};">
                            ${b.active_loans_count} aktif
                        </span>
                        <span style="font-size: 0.75rem; color: var(--text-muted);">(${b.total_loans_count} total)</span>
                    </td>
                    <td>
                        <div style="display: flex; gap: 6px;">
                            <button class="btn btn-sm btn-secondary btn-icon-only" title="Lihat Peminjam" onclick="App.viewBorrowerDetail(${b.id})">
                                <i data-lucide="eye" style="width: 14px; height: 14px;"></i>
                            </button>
                            <button class="btn btn-sm btn-secondary btn-icon-only" title="Ubah Data" onclick="App.openEditBorrowerModal(${b.id})">
                                <i data-lucide="edit-3" style="width: 14px; height: 14px;"></i>
                            </button>
                            <button class="btn btn-sm btn-danger btn-icon-only" title="Hapus Data" onclick="App.deleteBorrower(${b.id}, '${b.name}')">
                                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                            </button>
                        </div>
                    </td>
                </tr>
            `).join('');

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error(err);
            this.showToast('Gagal memuat peminjam: ' + err.message, 'error');
        }
    },

    openAddBorrowerModal() {
        document.getElementById('borrowerModalTitle').textContent = 'Tambah Identitas Peminjam Baru';
        document.getElementById('borrowerForm').reset();
        document.getElementById('borrowerIdInput').value = '';
        this.openModal('borrowerModal');
    },

    async openEditBorrowerModal(id) {
        try {
            const res = await fetch(`api/borrowers.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const b = result.data;
            document.getElementById('borrowerModalTitle').textContent = 'Ubah Identitas Peminjam';
            document.getElementById('borrowerIdInput').value = b.id;
            document.getElementById('borrowerModalNim').value = b.identity_number;
            document.getElementById('borrowerModalName').value = b.name;
            document.getElementById('borrowerModalPhone').value = b.phone;
            document.getElementById('borrowerModalEmail').value = b.email || '';
            document.getElementById('borrowerModalDept').value = b.department;
            document.getElementById('borrowerModalStatus').value = b.status;

            this.openModal('borrowerModal');
        } catch (err) {
            this.showToast('Gagal memuat data peminjam: ' + err.message, 'error');
        }
    },

    async saveBorrower(e) {
        e.preventDefault();
        const id = document.getElementById('borrowerIdInput').value;
        const isEdit = Boolean(id);

        const payload = {
            action: isEdit ? 'update' : 'create',
            id: id ? parseInt(id) : undefined,
            identity_number: document.getElementById('borrowerModalNim').value.trim(),
            name: document.getElementById('borrowerModalName').value.trim(),
            phone: document.getElementById('borrowerModalPhone').value.trim(),
            email: document.getElementById('borrowerModalEmail').value.trim(),
            department: document.getElementById('borrowerModalDept').value.trim(),
            status: document.getElementById('borrowerModalStatus').value
        };

        try {
            const res = await fetch('api/borrowers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.closeModal('borrowerModal');
            this.loadBorrowers();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async deleteBorrower(id, name) {
        if (!confirm(`Hapus data peminjam "${name}"?`)) return;

        try {
            const res = await fetch('api/borrowers.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id })
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.loadBorrowers();
            this.loadDashboard();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async viewBorrowerDetail(id) {
        try {
            const res = await fetch(`api/borrowers.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const b = result.data;
            document.getElementById('borrowerDetailNim').textContent = b.identity_number;
            document.getElementById('borrowerDetailName').textContent = b.name;
            document.getElementById('borrowerDetailPhone').textContent = b.phone;
            document.getElementById('borrowerDetailEmail').textContent = b.email || '-';
            document.getElementById('borrowerDetailDept').textContent = b.department;
            document.getElementById('borrowerDetailStatus').textContent = b.status;

            const loansBody = document.getElementById('borrowerDetailLoansBody');
            if (b.loans && b.loans.length > 0) {
                loansBody.innerHTML = b.loans.map(l => `
                    <tr>
                        <td><strong>${l.code}</strong></td>
                        <td>${l.item_name}</td>
                        <td>${l.quantity} Unit</td>
                        <td>${l.borrow_date}</td>
                        <td>${l.actual_return_date || l.expected_return_date}</td>
                        <td><span class="badge-status ${l.status === 'Kembali' ? 'badge-returned' : (l.status === 'Terlambat' ? 'badge-overdue' : 'badge-borrowed')}">${l.status}</span></td>
                    </tr>
                `).join('');
            } else {
                loansBody.innerHTML = `<tr><td colspan="6" style="text-align: center; color: var(--text-muted);">Belum pernah meminjam barang.</td></tr>`;
            }

            this.openModal('borrowerDetailModal');
        } catch (err) {
            this.showToast('Gagal memuat rincian peminjam: ' + err.message, 'error');
        }
    },

    /* ==========================================================================
       TRANSAKSI PEMINJAMAN & PENGEMBALIAN BARANG
       ========================================================================== */
    async loadBorrowings() {
        const search = document.getElementById('borrowingSearchInput')?.value || '';
        const status = document.getElementById('borrowingStatusFilter')?.value || 'all';

        const params = new URLSearchParams();
        if (search) params.append('search', search);
        if (status !== 'all') params.append('status', status);

        try {
            const res = await fetch(`api/borrowings.php?${params.toString()}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const list = result.data;
            const tbody = document.getElementById('borrowingsTableBody');
            const totalBadge = document.getElementById('borrowingsCountBadge');
            if (totalBadge) totalBadge.textContent = `${list.length} Transaksi`;

            // Overdue notification banner on Borrowings View
            const overdueLoans = list.filter(t => t.status === 'Terlambat');
            const borrowingsBanner = document.getElementById('borrowingsAlertBanner');
            if (borrowingsBanner) {
                if (overdueLoans.length > 0) {
                    borrowingsBanner.style.display = 'block';
                    borrowingsBanner.innerHTML = `
                        <div class="notif-banner banner-danger">
                            <div class="notif-banner-content">
                                <div class="notif-banner-icon"><i data-lucide="clock" style="width: 22px; height: 22px; color: var(--danger);"></i></div>
                                <div>
                                    <div class="notif-banner-title">Peringatan Jatuh Tempo: Terdapat ${overdueLoans.length} Transaksi Telah Melewati Batas Pengembalian!</div>
                                    <div class="notif-banner-desc">Harap hubungi peminjam melalui kontak WhatsApp / telepon yang tertera di tabel, atau segera proses pengembalian unit.</div>
                                </div>
                            </div>
                            <div class="notif-banner-actions">
                                <button class="btn btn-sm btn-danger" onclick="document.getElementById('borrowingStatusFilter').value='Terlambat'; App.loadBorrowings();">
                                    <span>Tampilkan Hanya Terlambat</span>
                                </button>
                            </div>
                        </div>
                    `;
                } else {
                    borrowingsBanner.style.display = 'none';
                    borrowingsBanner.innerHTML = '';
                }
            }

            if (!tbody) return;

            if (list.length === 0) {
                tbody.innerHTML = `<tr><td colspan="7" style="text-align: center; color: var(--text-muted); padding: 32px;">Tidak ditemukan data transaksi peminjaman.</td></tr>`;
                return;
            }

            tbody.innerHTML = list.map(trx => {
                let badgeClass = 'badge-borrowed';
                if (trx.status === 'Kembali') badgeClass = 'badge-returned';
                if (trx.status === 'Terlambat') badgeClass = 'badge-overdue';

                const isOverdue = trx.status === 'Terlambat';
                const canReturn = trx.status === 'Dipinjam' || trx.status === 'Terlambat';
                const itemsSummary = trx.items_summary || (trx.details && trx.details.map(d => `${d.item_name} (${d.quantity} unit)`).join(', ')) || '-';

                // Format WhatsApp contact message
                const rawPhone = (trx.borrower_phone || '').replace(/[^0-9]/g, '');
                const waPhone = rawPhone.startsWith('0') ? '62' + rawPhone.slice(1) : rawPhone;
                const waMsg = encodeURIComponent(`Halo ${trx.borrower_name}, ini dari Tim Inventaris. Mengingatkan bahwa peminjaman barang (${itemsSummary}) dengan No. Transaksi ${trx.code} telah jatuh tempo pada tanggal ${trx.expected_return_date}. Mohon segera konfirmasi atau kembalikan barang. Terima kasih.`);

                return `
                    <tr class="${isOverdue ? 'row-overdue' : ''}">
                        <td>
                            <strong style="color: var(--primary);">${trx.code}</strong>
                            <div style="font-size: 0.72rem; color: var(--text-muted);">${trx.created_at || ''}</div>
                        </td>
                        <td>
                            <div style="font-weight: 600;">${trx.borrower_name}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${trx.borrower_nim} &bull; ${trx.borrower_dept}</div>
                            ${trx.borrower_phone ? `<div style="font-size: 0.72rem; color: var(--text-muted);">${trx.borrower_phone}</div>` : ''}
                        </td>
                        <td>
                            <div style="font-weight: 600; font-size: 0.85rem; max-width: 300px;">${itemsSummary}</div>
                            <div style="font-size: 0.75rem; color: var(--text-muted);">${trx.total_item_types || (trx.details ? trx.details.length : 1)} jenis &bull; <strong>${trx.total_quantity || 1} Total Unit</strong></div>
                        </td>
                        <td>
                            <div style="font-size: 0.8rem;">Pinjam: <strong>${trx.borrow_date}</strong></div>
                            <div style="font-size: 0.75rem; color: ${isOverdue ? 'var(--danger)' : 'var(--text-muted)'}; font-weight: ${isOverdue ? '700' : 'normal'};">
                                ${isOverdue ? '⚠️ ' : ''}Batas: ${trx.expected_return_date}
                            </div>
                        </td>
                        <td>
                            <span class="badge-status ${badgeClass}" style="${isOverdue ? 'font-weight: 700;' : ''}">
                                ${isOverdue ? '<i data-lucide="alert-circle" style="width: 12px; height: 12px; display: inline-block; vertical-align: middle; margin-right: 2px;"></i> Terlambat' : trx.status}
                            </span>
                            ${trx.actual_return_date ? `<div style="font-size: 0.72rem; color: var(--text-muted); margin-top: 2px;">Tgl Kembali: ${trx.actual_return_date}</div>` : ''}
                        </td>
                        <td>
                            <div style="font-size: 0.75rem; max-width: 180px; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="${trx.purpose || '-'}">
                                ${trx.purpose || '-'}
                            </div>
                        </td>
                        <td>
                            <div style="display: flex; gap: 6px; align-items: center;">
                                ${canReturn ? `
                                    <button class="btn btn-sm btn-success" title="Catat Pengembalian" onclick="App.openReturnModal(${trx.id}, '${trx.code}', '${trx.borrower_name}')">
                                        <i data-lucide="corner-down-left" style="width: 14px; height: 14px;"></i> Kembali
                                    </button>
                                ` : ''}
                                ${waPhone && isOverdue ? `
                                    <a href="https://wa.me/${waPhone}?text=${waMsg}" target="_blank" class="btn btn-sm btn-secondary btn-icon-only" title="Hubungi Peminjam via WhatsApp">
                                        <i data-lucide="message-circle" style="width: 14px; height: 14px; color: var(--success);"></i>
                                    </a>
                                ` : ''}
                                <button class="btn btn-sm btn-secondary btn-icon-only" title="Cetak Bukti Transaksi" onclick="App.showReceiptModal(${trx.id})">
                                    <i data-lucide="printer" style="width: 14px; height: 14px;"></i>
                                </button>
                                <button class="btn btn-sm btn-danger btn-icon-only" title="Hapus / Batalkan Transaksi" onclick="App.deleteBorrowing(${trx.id}, '${trx.code}')">
                                    <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
                                </button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');

            if (window.lucide) lucide.createIcons();
        } catch (err) {
            console.error(err);
            this.showToast('Gagal memuat transaksi: ' + err.message, 'error');
        }
    },

    // Cache of available items for multi-item row dropdowns
    availableItemsList: [],

    // Modal Catat Peminjaman Baru (Multi-Item)
    async openNewBorrowingModal() {
        document.getElementById('newBorrowingForm').reset();
        
        // Default dates: today and +3 days
        const today = new Date().toISOString().split('T')[0];
        const returnDate = new Date(Date.now() + 3 * 24 * 60 * 60 * 1000).toISOString().split('T')[0];
        document.getElementById('borrowDateInput').value = today;
        document.getElementById('expectedReturnDateInput').value = returnDate;

        const container = document.getElementById('borrowingItemsContainer');
        if (container) container.innerHTML = '';

        // Fetch Active Borrowers and ALL Items (so unavailable items can be clearly signaled)
        try {
            const [resBorrowers, resItems] = await Promise.all([
                fetch('api/borrowers.php?status=Aktif').then(r => r.json()),
                fetch('api/items.php').then(r => r.json())
            ]);

            const borrowerSelect = document.getElementById('borrowingSelectBorrower');
            if (borrowerSelect) {
                let bOpts = '<option value="" disabled selected>Pilih Peminjam (NIM/Nama)</option>';
                (resBorrowers.data || []).forEach(b => {
                    bOpts += `<option value="${b.id}">${b.name} (${b.identity_number}) - ${b.department}</option>`;
                });
                borrowerSelect.innerHTML = bOpts;
            }

            this.availableItemsList = resItems.data || [];

            // Add initial 1 item row
            this.addBorrowItemRow();

            this.openModal('newBorrowingModal');
        } catch (err) {
            this.showToast('Gagal menyiapkan form peminjaman: ' + err.message, 'error');
        }
    },

    // Add a new dynamic item row with real-time stock availability notification
    addBorrowItemRow(selectedItemId = '', selectedQty = 1) {
        const container = document.getElementById('borrowingItemsContainer');
        if (!container) return;

        let options = '<option value="" disabled selected>Pilih Barang</option>';
        this.availableItemsList.forEach(item => {
            const isSelected = item.id === selectedItemId ? 'selected' : '';
            const isOutOfStock = parseInt(item.available_stock) <= 0;
            if (isOutOfStock) {
                options += `<option value="${item.id}" data-available="0" data-name="${item.name}" ${isSelected} style="color: var(--danger); font-weight: 600;">[STOK HABIS / TIDAK TERSEDIA] ${item.code} - ${item.name}</option>`;
            } else {
                options += `<option value="${item.id}" data-available="${item.available_stock}" data-name="${item.name}" ${isSelected}>[${item.code}] ${item.name} (Tersedia: ${item.available_stock} Unit)</option>`;
            }
        });

        const row = document.createElement('div');
        row.className = 'borrowing-item-row';
        row.style.cssText = 'display: flex; gap: 10px; align-items: flex-start; background: var(--bg-surface); padding: 12px; border-radius: var(--radius-sm); border: 1px solid var(--border-subtle); margin-bottom: 8px;';
        row.innerHTML = `
            <div style="flex: 3;" class="item-select-col">
                <select class="form-control item-select" required>
                    ${options}
                </select>
                <div class="stock-hint" style="font-size: 0.74rem; color: var(--primary); margin-top: 4px;">Pilih barang untuk cek stok</div>
            </div>
            <div style="flex: 1; min-width: 90px;">
                <input type="number" class="form-control item-qty" min="1" max="1" value="${selectedQty}" placeholder="Qty" required>
            </div>
            <button type="button" class="btn btn-sm btn-danger btn-icon-only remove-row-btn" title="Hapus Barang" style="margin-top: 4px;">
                <i data-lucide="trash-2" style="width: 14px; height: 14px;"></i>
            </button>
        `;

        const selectEl = row.querySelector('.item-select');
        const qtyEl = row.querySelector('.item-qty');
        const hintEl = row.querySelector('.stock-hint');
        const removeBtn = row.querySelector('.remove-row-btn');

        selectEl.addEventListener('change', () => {
            const opt = selectEl.options[selectEl.selectedIndex];
            const maxAvail = parseInt(opt.getAttribute('data-available') || '0');
            const itemName = opt.getAttribute('data-name') || 'Barang';

            // Remove existing inline warning if present
            const oldAlert = row.querySelector('.item-stock-warning-alert');
            if (oldAlert) oldAlert.remove();

            if (maxAvail <= 0) {
                qtyEl.value = 0;
                qtyEl.max = 0;
                qtyEl.disabled = true;
                hintEl.innerHTML = `<span style="color: var(--danger); font-weight: 700;">⚠️ STOK TIDAK TERSEDIA!</span>`;

                const alertDiv = document.createElement('div');
                alertDiv.className = 'item-stock-warning-alert';
                alertDiv.innerHTML = `<i data-lucide="alert-triangle" style="width: 14px; height: 14px; flex-shrink: 0;"></i> <span>Barang "${itemName}" sedang <strong>TIDAK TERSEDIA</strong> (stok 0) dan tidak bisa dipinjam!</span>`;
                hintEl.parentNode.appendChild(alertDiv);
                if (window.lucide) lucide.createIcons();

                App.showToast(`Barang "${itemName}" sedang TIDAK TERSEDIA (stok habis)!`, 'error');
            } else {
                qtyEl.disabled = false;
                qtyEl.max = maxAvail;
                if (parseInt(qtyEl.value) < 1 || parseInt(qtyEl.value) > maxAvail) {
                    qtyEl.value = 1;
                }
                hintEl.innerHTML = `<span style="color: var(--success); font-weight: 600;">Maks. tersedia: ${maxAvail} unit</span>`;
            }
        });

        qtyEl.addEventListener('input', () => {
            const opt = selectEl.options[selectEl.selectedIndex];
            const maxAvail = parseInt(opt?.getAttribute('data-available') || '1');
            const val = parseInt(qtyEl.value) || 0;
            if (maxAvail > 0 && val > maxAvail) {
                qtyEl.value = maxAvail;
                App.showToast(`Jumlah pinjam melebihi stok yang tersedia (tersisa ${maxAvail} unit)!`, 'warning');
            }
        });

        removeBtn.addEventListener('click', () => {
            const allRows = container.querySelectorAll('.borrowing-item-row');
            if (allRows.length <= 1) {
                App.showToast('Minimal harus ada 1 barang dalam transaksi peminjaman.', 'info');
                return;
            }
            row.remove();
        });

        container.appendChild(row);
        if (window.lucide) lucide.createIcons();
    },

    async saveNewBorrowing(e) {
        e.preventDefault();
        const borrowerId = parseInt(document.getElementById('borrowingSelectBorrower').value);
        const borrowDate = document.getElementById('borrowDateInput').value;
        const expectedReturnDate = document.getElementById('expectedReturnDateInput').value;
        const purpose = document.getElementById('borrowingPurposeInput').value.trim();
        const notes = document.getElementById('borrowingNotesInput').value.trim();

        const itemRows = document.querySelectorAll('#borrowingItemsContainer .borrowing-item-row');
        if (itemRows.length === 0) {
            this.showToast('Tambahkan minimal 1 barang yang ingin dipinjam!', 'error');
            return;
        }

        const items = [];
        const seenItems = new Set();

        for (const row of itemRows) {
            const selectEl = row.querySelector('.item-select');
            const qtyEl = row.querySelector('.item-qty');
            const itemId = selectEl.value;
            const opt = selectEl.options[selectEl.selectedIndex];
            const maxAvail = parseInt(opt?.getAttribute('data-available') || '0');
            const itemName = opt?.getAttribute('data-name') || 'Barang';
            const qty = parseInt(qtyEl.value);

            if (!itemId) {
                this.showToast('Silakan pilih barang pada semua baris yang ditambahkan.', 'error');
                return;
            }

            // Notification & prevention if user tries to borrow out-of-stock item
            if (maxAvail <= 0) {
                this.showToast(`Barang "${itemName}" TIDAK TERSEDIA (stok habis)! Hapus atau ganti barang tersebut sebelum menyimpan transaksi.`, 'error');
                return;
            }

            if (qty < 1) {
                this.showToast(`Jumlah unit untuk barang "${itemName}" minimal 1 unit!`, 'error');
                return;
            }

            if (qty > maxAvail) {
                this.showToast(`Jumlah pinjam untuk "${itemName}" melebihi stok (tersedia: ${maxAvail} unit)!`, 'error');
                return;
            }

            if (seenItems.has(itemId)) {
                this.showToast(`Barang yang sama dipilih lebih dari 1 kali. Gabungkan jumlah unitnya pada satu baris.`, 'error');
                return;
            }
            seenItems.add(itemId);

            if (isNaN(qty) || qty < 1) {
                this.showToast('Jumlah unit barang minimal 1.', 'error');
                return;
            }

            items.push({ item_id: itemId, quantity: qty });
        }

        const payload = {
            action: 'create',
            borrower_id: borrowerId,
            borrow_date: borrowDate,
            expected_return_date: expectedReturnDate,
            purpose: purpose,
            notes: notes,
            items: items
        };

        try {
            const res = await fetch('api/borrowings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.closeModal('newBorrowingModal');
            this.loadBorrowings();
            this.loadDashboard();
            this.loadItems();

            // Prompt print receipt
            if (result.data && result.data.id) {
                setTimeout(() => {
                    this.showReceiptModal(result.data.id);
                }, 500);
            }
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    // Modal Catat Pengembalian Barang (Multi-Item)
    async openReturnModal(id, code, borrowerName) {
        try {
            const res = await fetch(`api/borrowings.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const trx = result.data;
            document.getElementById('returnTrxId').value = trx.id;
            document.getElementById('returnTrxCode').textContent = trx.code;
            document.getElementById('returnBorrowerName').textContent = `${trx.borrower_name} (${trx.borrower_nim})`;
            document.getElementById('returnActualDate').value = new Date().toISOString().split('T')[0];
            document.getElementById('returnCondition').value = 'Baik';
            document.getElementById('returnNotes').value = '';

            const itemsList = document.getElementById('returnItemsListSummary');
            if (itemsList && trx.items) {
                itemsList.innerHTML = trx.items.map(it => `
                    <li><strong>${it.item_name}</strong> [${it.item_code || it.item_id}] &mdash; <span style="font-weight: 700; color: var(--primary);">${it.quantity} Unit</span> (${it.status})</li>
                `).join('');
            }

            this.openModal('returnModal');
        } catch (err) {
            this.showToast('Gagal memuat data transaksi pengembalian: ' + err.message, 'error');
        }
    },

    async saveReturn(e) {
        e.preventDefault();
        const id = document.getElementById('returnTrxId').value;
        const payload = {
            action: 'return',
            id: parseInt(id),
            actual_return_date: document.getElementById('returnActualDate').value,
            return_condition: document.getElementById('returnCondition').value,
            return_notes: document.getElementById('returnNotes').value.trim()
        };

        try {
            const res = await fetch('api/borrowings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.closeModal('returnModal');
            this.loadBorrowings();
            this.loadDashboard();
            this.loadItems();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    async deleteBorrowing(id, code) {
        if (!confirm(`Hapus/batalkan data transaksi "${code}"? Stok seluruh barang akan dikembalikan jika status masih dipinjam.`)) {
            return;
        }

        try {
            const res = await fetch('api/borrowings.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ action: 'delete', id: id })
            });
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            this.showToast(result.message, 'success');
            this.loadBorrowings();
            this.loadDashboard();
            this.loadItems();
        } catch (err) {
            this.showToast(err.message, 'error');
        }
    },

    // Show Printable Receipt Modal (Multi-Item)
    async showReceiptModal(id) {
        try {
            const res = await fetch(`api/borrowings.php?id=${id}`);
            const result = await res.json();
            if (result.status !== 'success') throw new Error(result.message);

            const trx = result.data;
            document.getElementById('receiptCode').textContent = trx.code;
            document.getElementById('receiptDate').textContent = trx.borrow_date;
            document.getElementById('receiptBorrower').textContent = `${trx.borrower_name} (${trx.borrower_nim})`;
            document.getElementById('receiptDept').textContent = trx.borrower_dept;
            document.getElementById('receiptPhone').textContent = trx.borrower_phone;
            document.getElementById('receiptExpectedReturn').textContent = trx.expected_return_date;
            document.getElementById('receiptStatus').textContent = trx.status;
            document.getElementById('receiptPurpose').textContent = trx.purpose || '-';

            const tbody = document.getElementById('receiptItemsTableBody');
            if (tbody && trx.items) {
                tbody.innerHTML = trx.items.map(it => `
                    <tr style="border-bottom: 1px dashed #e2e8f0;">
                        <td style="padding: 6px 0;"><strong>${it.item_name}</strong></td>
                        <td style="padding: 6px 0; text-align: center; color: #475569;">${it.item_code || it.item_id}</td>
                        <td style="padding: 6px 0; text-align: right; font-weight: bold;">${it.quantity} Unit</td>
                    </tr>
                `).join('');
            }

            this.openModal('receiptModal');
        } catch (err) {
            this.showToast('Gagal memuat bukti transaksi: ' + err.message, 'error');
        }
    },

    printReceipt() {
        window.print();
    },

    /* ==========================================================================
       LAPORAN & EXPORT LOGIC
       ========================================================================== */
    loadReportsView() {
        // Just refresh badges / counters
    },

    exportData(type) {
        window.location.href = `api/export.php?type=${type}`;
        this.showToast(`Mengunduh file laporan format CSV...`, 'info');
    }
};

// Initialize when DOM is ready
document.addEventListener('DOMContentLoaded', () => {
    App.init();
});
