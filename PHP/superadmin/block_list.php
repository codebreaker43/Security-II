<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../db.php';

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/../includes/auth_guard.php';
enforce_profile_completion($conn);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block List - ONE PIECE Super Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            background-image:
                linear-gradient(rgba(10, 15, 25, 0.20), rgba(10, 15, 25, 0.35)),
                url('../../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .main-content > p {
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 600;
            margin-bottom: 1.25rem;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.85);
        }

        /* Filter Bar Card */
        .filter-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1.25rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .filter-bar-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #d97706;
            margin-bottom: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-bar-title svg {
            width: 14px;
            height: 14px;
            opacity: 0.9;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex: 1;
            min-width: 150px;
        }

        .filter-group.search-group {
            flex: 2;
            min-width: 220px;
        }

        .filter-group label {
            font-size: 0.80rem;
            color: #0f172a;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input[type="text"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: #ffffff;
            border: 1.5px solid #94a3b8;
            border-radius: 8px;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.25s ease;
            outline: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .filter-group select:hover,
        .filter-group input[type="text"]:hover {
            border-color: #f59e0b;
        }

        .filter-group select:focus,
        .filter-group input[type="text"]:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            background: #ffffff;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
            padding-bottom: 1px;
        }

        .btn-filter {
            padding: 0.6rem 1.3rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .btn-filter-clear {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-filter-clear:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fca5a5;
        }

        .results-count {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.75rem;
            padding-top: 0.65rem;
            border-top: 1px solid #e2e8f0;
        }

        .results-count strong {
            color: #0f172a;
        }

        .role-superadmin {
            color: #b91c1c;
            font-weight: 800;
        }

        .role-admin {
            color: #0284c7;
            font-weight: 700;
        }

        .role-user {
            color: #475569;
            font-weight: 600;
        }

        /* Pagination Styling */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .pagination .btn-pag,
        .pagination span {
            display: inline-flex;
            align-items: center;
            justify-content: center;
            min-width: 2.4rem;
            height: 2.4rem;
            padding: 0 0.75rem;
            background: #ffffff;
            border: 1px solid #cbd5e1;
            border-radius: 8px;
            color: #0f172a;
            font-size: 0.875rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.2s ease;
            white-space: nowrap;
            cursor: pointer;
            font-family: inherit;
        }

        .pagination .btn-pag:hover:not(:disabled):not(.active) {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }

        .pagination .btn-pag.active {
            background: linear-gradient(135deg, #d97706, #b45309) !important;
            border-color: #d97706 !important;
            color: #fff !important;
            box-shadow: 0 4px 14px rgba(217, 119, 6, 0.35);
            cursor: default;
            pointer-events: none;
        }

        .pagination span.dots {
            border-color: transparent;
            background: transparent;
            color: #94a3b8;
            cursor: default;
        }

        .pagination .btn-pag.disabled,
        .pagination .btn-pag:disabled {
            opacity: 0.35;
            cursor: not-allowed;
            pointer-events: none;
        }

        .pagination-info {
            text-align: center;
            font-size: 0.82rem;
            color: #ffffff;
            font-weight: 600;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.85);
            margin-top: 0.75rem;
        }

        /* Solid White Table Card */
        .table-container {
            width: 100%;
            margin-top: 1rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .table-wrapper {
            background: transparent;
            border-radius: 10px;
            position: relative;
            min-width: 800px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: #0f172a;
            table-layout: auto;
        }

        th {
            background: rgba(254, 243, 199, 0.75) !important;
            font-weight: 800;
            color: #78350f !important;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            border-top: 3px solid #f59e0b !important;
            border-bottom: 2px solid #fde68a !important;
            padding: 1.1rem 1rem;
            text-align: left;
            font-size: 0.88rem;
            text-shadow: none;
        }

        td {
            padding: 0.95rem 1.1rem;
            color: #1e293b;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tr:nth-child(even) {
            background: #f8fafc;
        }

        tr:hover {
            background: #f1f5f9;
        }

        .action-buttons {
            display: flex;
            gap: 0.5rem;
            flex-wrap: nowrap;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            background: transparent;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.75rem;
            font-weight: 600;
            text-transform: uppercase;
            transition: all 0.2s ease;
        }

        .btn-unblock {
            background-color: var(--accent-green);
            color: white;
        }

        .btn-unblock:hover {
            filter: brightness(1.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(40, 167, 69, 0.4);
        }

        .btn-view {
            background-color: var(--accent-blue);
            color: white;
        }

        .btn-view:hover {
            filter: brightness(1.2);
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(79, 172, 254, 0.4);
        }

        .status-blocked {
            color: var(--accent-red);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(255, 59, 59, 0.4);
        }

        .toast {
            position: fixed;
            top: 1.5rem;
            right: 1.5rem;
            padding: 1rem 2rem;
            border-radius: 12px;
            color: white;
            font-weight: 600;
            z-index: 9999;
            animation: slideIn 0.3s ease;
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.6);
        }

        .toast-success {
            background: var(--accent-green);
        }

        .toast-error {
            background: var(--accent-red);
        }

        @keyframes slideIn {
            from {
                transform: translateX(100%) scale(0.8);
                opacity: 0;
            }

            to {
                transform: translateX(0) scale(1);
                opacity: 1;
            }
        }

        /* View Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            align-items: flex-start;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .modal-large-content {
            background: rgba(20, 30, 45, 0.85);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            margin: 5% auto;
            padding: 1.5rem;
            border-radius: 16px;
            width: 80%;
            max-width: 800px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            position: relative;
            animation: modalSlide 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
        }

        .modal-inner {
            background: transparent;
            border-radius: 10px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            color: var(--text-color);
        }

        .modal-header {
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 1.5rem;
            margin-bottom: 1.5rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .modal-header h2 {
            font-size: 2rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 1px;
            color: var(--accent-blue);
            text-shadow: 2px 2px 4px rgba(0, 0, 0, 0.4);
            margin: 0;
        }

        .close {
            color: rgba(255, 255, 255, 0.5);
            font-size: 2rem;
            font-weight: bold;
            cursor: pointer;
            transition: all 0.2s;
            line-height: 1;
        }

        .close:hover {
            color: var(--accent-red);
            transform: scale(1.2) rotate(90deg);
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
            text-align: left;
        }

        .detail-item {
            margin-bottom: 0;
        }

        .detail-label {
            font-weight: 700;
            color: var(--accent-green);
            font-size: 0.85rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            display: block;
            margin-bottom: 0.3rem;
        }

        .detail-value {
            font-size: 1.1rem;
            color: white;
            font-weight: 500;
        }

        .privileges-list {
            list-style-type: none;
            padding: 0;
            margin: 0;
            column-count: 2;
            column-gap: 1rem;
        }

        .privileges-list li {
            position: relative;
            padding-left: 1.2rem;
            margin-bottom: 0.3rem;
            font-size: 0.95rem;
        }

        .privileges-list li::before {
            content: '✓';
            color: var(--accent-green);
            position: absolute;
            left: 0;
            font-weight: bold;
        }

        @keyframes modalSlide {
            from {
                transform: translateY(-50px) scale(0.9);
                opacity: 0;
            }

            to {
                transform: translateY(0) scale(1);
                opacity: 1;
            }
        }

        .empty-state {
            text-align: center;
            padding: 3rem 1rem;
            color: var(--text-muted);
        }

        .empty-state .icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .empty-state p {
            font-size: 1.1rem;
        }

        .section-title {
            font-size: 1.4rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 2.5rem;
            margin-bottom: 0.5rem;
            padding-bottom: 0.5rem;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .section-title.admin {
            color: var(--accent-orange);
        }

        .section-title.user {
            color: var(--accent-blue);
        }

        .section-title:first-of-type {
            margin-top: 1rem;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/../includes/sidebar.php';
        render_sidebar('block_list');
        ?>

        <div class="app-main">
            <main class="main-content">
        <h1>Block List</h1>
        <p>View all blocked accounts and manage their status.</p>

        <!-- Filters Bar -->
        <div class="filter-card">
            <div class="filter-bar-title">
                <svg viewBox="0 0 24 24" fill="currentColor">
                    <path d="M10 18h4v-2h-4v2zM3 6v2h18V6H3zm3 7h12v-2H6v2z" />
                </svg>
                Filters
            </div>
            <div class="filter-row">
                <div class="filter-group">
                    <label for="filterRole">Role</label>
                    <select id="filterRole" onchange="renderBlockedTable()">
                        <option value="all">All Roles</option>
                        <option value="admin">Admin</option>
                        <option value="user">User</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="filterIdno">ID Number</label>
                    <input type="text" id="filterIdno" placeholder="Search ID..." onkeyup="renderBlockedTable()">
                </div>
                <div class="filter-group">
                    <label for="filterUsername">Username</label>
                    <input type="text" id="filterUsername" placeholder="Search username..." onkeyup="renderBlockedTable()">
                </div>
                <div class="filter-group search-group">
                    <label for="searchInput">Name or Email</label>
                    <input type="text" id="searchInput" placeholder="Search name or email..." onkeyup="renderBlockedTable()">
                </div>
                <div class="filter-actions">
                    <button type="button" class="btn-filter btn-filter-clear" onclick="clearFilters()">
                        ✕ Clear
                    </button>
                </div>
            </div>
            <div class="results-count" id="resultsCount">
                Showing <strong>0</strong> blocked accounts
            </div>
        </div>

        <!-- Single Unified Table -->
        <div class="table-container">
            <div class="table-wrapper">
                <table id="blockListTable">
                    <thead>
                        <tr>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blockListTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center;">Loading blocked accounts...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- Block List Pagination -->
        <div id="blockPagination" class="pagination"></div>
        <div id="blockPaginationInfo" class="pagination-info"></div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-large-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2 id="modalUserName">User Details</h2>
                    <span class="close" onclick="closeLargeModal('viewUserModal')">&times;</span>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <script>
        let allBlockedUsers = [];
        let currentBlockPage = 1;
        const blockPerPage = 10;

        document.addEventListener('DOMContentLoaded', loadBlockedUsers);

        function loadBlockedUsers() {
            fetch('../api/manage_users_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        allBlockedUsers = (data.users || []).filter(user => user.status === 'blocked');
                        renderBlockedTable(true);
                    } else {
                        showToast(data.error || 'Failed to load users', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
        }

        function renderBlockedTable(resetPage = false) {
            if (resetPage) currentBlockPage = 1;

            const roleFilter = document.getElementById('filterRole') ? document.getElementById('filterRole').value : 'all';
            const idnoFilter = (document.getElementById('filterIdno') ? document.getElementById('filterIdno').value : '').toLowerCase().trim();
            const usernameFilter = (document.getElementById('filterUsername') ? document.getElementById('filterUsername').value : '').toLowerCase().trim();
            const searchFilter = (document.getElementById('searchInput') ? document.getElementById('searchInput').value : '').toLowerCase().trim();

            const isFiltered = (roleFilter !== 'all' || idnoFilter !== '' || usernameFilter !== '' || searchFilter !== '');

            const filtered = allBlockedUsers.filter(user => {
                // Role filter
                if (roleFilter !== 'all') {
                    if (roleFilter === 'admin' && user.role !== 'admin' && user.role !== 'superadmin') return false;
                    if (roleFilter === 'user' && user.role !== 'user') return false;
                }

                // ID Number filter
                if (idnoFilter !== '') {
                    if (!user.idno || !user.idno.toLowerCase().includes(idnoFilter)) return false;
                }

                // Username filter
                if (usernameFilter !== '') {
                    if (!user.username || !user.username.toLowerCase().includes(usernameFilter)) return false;
                }

                // Name or Email filter
                if (searchFilter !== '') {
                    const fullName = ((user.firstname || '') + ' ' + (user.lastname || '')).toLowerCase();
                    const email = (user.email || '').toLowerCase();
                    if (!fullName.includes(searchFilter) && !email.includes(searchFilter)) return false;
                }

                return true;
            });

            const totalPages = Math.ceil(filtered.length / blockPerPage) || 1;
            if (currentBlockPage > totalPages) currentBlockPage = totalPages;
            if (currentBlockPage < 1) currentBlockPage = 1;

            const startIndex = (currentBlockPage - 1) * blockPerPage;
            const endIndex = Math.min(startIndex + blockPerPage, filtered.length);
            const pagedUsers = filtered.slice(startIndex, endIndex);

            // Update result counter
            const counter = document.getElementById('resultsCount');
            if (counter) {
                if (filtered.length > 0) {
                    counter.innerHTML = `Showing <strong>${startIndex + 1}&ndash;${endIndex}</strong> of <strong>${filtered.length}</strong> blocked accounts${isFiltered ? ' (filtered)' : ''}`;
                } else {
                    counter.innerHTML = `Showing <strong>0</strong> blocked accounts`;
                }
            }

            const tbody = document.getElementById('blockListTableBody');
            if (!tbody) return;

            if (filtered.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="icon">✅</div>
                                <p>${allBlockedUsers.length === 0 ? 'No blocked accounts found. All accounts are active.' : 'No blocked accounts match the selected filters.'}</p>
                            </div>
                        </td>
                    </tr>`;
                renderBlockPagination(0, 0);
                return;
            }

            tbody.innerHTML = pagedUsers.map(user => {
                const isRoleAdmin = (user.role === 'admin' || user.role === 'superadmin');
                const roleClass = isRoleAdmin ? 'role-admin' : 'role-user';
                const roleDisplay = user.role ? (user.role.charAt(0).toUpperCase() + user.role.slice(1)) : 'User';

                return `
                <tr>
                    <td style="font-family: monospace; font-weight: 700; color: #0284c7; letter-spacing: 0.5px;">${escapeHtml(user.idno)}</td>
                    <td style="font-weight: 600; color: #0f172a;">${escapeHtml(user.firstname)} ${escapeHtml(user.lastname)}</td>
                    <td style="color: #b45309; font-weight: 700;">${escapeHtml(user.username)}</td>
                    <td>
                        <span class="${roleClass}" style="font-weight: 700;">
                            ${escapeHtml(roleDisplay)}
                        </span>
                    </td>
                    <td>${escapeHtml(user.email)}</td>
                    <td><span class="status-blocked">Blocked</span></td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewUser('${escapeHtml(user.idno)}')">View</button>
                            <button class="btn-action btn-unblock" onclick="unblockUser('${escapeHtml(user.idno)}')">Unblock</button>
                        </div>
                    </td>
                </tr>
            `}).join('');

            renderBlockPagination(totalPages, filtered.length);
        }

        function renderBlockPagination(totalPages, totalItems) {
            const pagContainer = document.getElementById('blockPagination');
            const infoContainer = document.getElementById('blockPaginationInfo');
            if (!pagContainer || !infoContainer) return;

            if (totalPages <= 1) {
                pagContainer.innerHTML = '';
                infoContainer.innerHTML = totalItems > 0 ? `Page 1 of 1 (${totalItems} total)` : '';
                return;
            }

            let html = '';
            // Prev button
            if (currentBlockPage > 1) {
                html += `<button type="button" class="btn-pag" onclick="changeBlockPage(${currentBlockPage - 1})">&laquo; Prev</button>`;
            } else {
                html += `<button type="button" class="btn-pag disabled" disabled>&laquo; Prev</button>`;
            }

            // Page numbers
            const windowSize = 2;
            let pages = [];
            for (let p = 1; p <= totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= currentBlockPage - windowSize && p <= currentBlockPage + windowSize)) {
                    pages.push(p);
                }
            }

            let lastP = null;
            pages.forEach(p => {
                if (lastP !== null && p - lastP > 1) {
                    html += `<span class="dots">&hellip;</span>`;
                }
                if (p === currentBlockPage) {
                    html += `<button type="button" class="btn-pag active">${p}</button>`;
                } else {
                    html += `<button type="button" class="btn-pag" onclick="changeBlockPage(${p})">${p}</button>`;
                }
                lastP = p;
            });

            // Next button
            if (currentBlockPage < totalPages) {
                html += `<button type="button" class="btn-pag" onclick="changeBlockPage(${currentBlockPage + 1})">Next &raquo;</button>`;
            } else {
                html += `<button type="button" class="btn-pag disabled" disabled>Next &raquo;</button>`;
            }

            pagContainer.innerHTML = html;
            infoContainer.innerHTML = `Page ${currentBlockPage} of ${totalPages} (${totalItems} total)`;
        }

        function changeBlockPage(page) {
            currentBlockPage = page;
            renderBlockedTable(false);
            const table = document.querySelector('.table-container');
            if (table) table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function clearFilters() {
            if (document.getElementById('filterRole')) document.getElementById('filterRole').value = 'all';
            if (document.getElementById('filterIdno')) document.getElementById('filterIdno').value = '';
            if (document.getElementById('filterUsername')) document.getElementById('filterUsername').value = '';
            if (document.getElementById('searchInput')) document.getElementById('searchInput').value = '';
            renderBlockedTable(true);
        }

        function unblockUser(userId) {
            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, action: 'unblock' })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadBlockedUsers();
                    } else {
                        showToast(data.error || 'Action failed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
        }

        function viewUser(userId) {
            fetch(`../api/manage_users_api.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users && data.users.length > 0) {
                        const user = data.users[0];
                        populateModal(user);
                        document.getElementById('viewUserModal').style.display = 'block';
                    } else {
                        showToast('Failed to load user details', 'error');
                    }
                })
                .catch(error => showToast('Error fetching data', 'error'));
        }

        function populateModal(user) {
            const modalBody = document.getElementById('modalBody');
            document.getElementById('modalUserName').textContent = `${user.firstname} ${user.lastname}`;

            const fields = [
                { label: 'ID No', value: user.idno },
                { label: 'Full Name', value: `${user.firstname} ${user.middlename ? user.middlename + '.' : ''} ${user.lastname} ${user.suffix || ''}` },
                { label: 'Username', value: user.username },
                { label: 'Email', value: user.email },
                { label: 'Sex', value: user.sex },
                { label: 'Age', value: user.age },
                { label: 'Birthdate', value: user.birthdate },
                { label: 'Role', value: user.role },
                { label: 'Status', value: user.status },
            ];

            let html = fields.map(field => `
                <div class="detail-item">
                    <span class="detail-label">${field.label}</span>
                    <span class="detail-value">${field.value || 'N/A'}</span>
                </div>
            `).join('');

            if (user.block_reason) {
                html += `
                    <div class="detail-item" style="grid-column: 1 / -1; margin-top: 0.5rem; background: rgba(239, 68, 68, 0.08); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(239, 68, 68, 0.2);">
                        <span class="detail-label" style="color: #f87171;">Block Reason</span>
                        <span class="detail-value" style="color: #fca5a5; white-space: pre-wrap; margin-top: 0.25rem;">${escapeHtml(user.block_reason)}</span>
                    </div>
                `;
            }

            if (user.block_file) {
                html += `
                    <div class="detail-item" style="grid-column: 1 / -1; margin-top: 0.5rem; background: rgba(59, 130, 246, 0.08); padding: 0.75rem 1rem; border-radius: 8px; border: 1px solid rgba(59, 130, 246, 0.2);">
                        <span class="detail-label" style="color: #60a5fa;">Attached Supporting File</span>
                        <div style="margin-top: 0.35rem;">
                            <a href="../../uploads/${encodeURIComponent(user.block_file)}" target="_blank" download style="display: inline-flex; align-items: center; gap: 0.4rem; color: #93c5fd; text-decoration: underline; font-size: 0.9rem;">
                                <span>&#128190;</span> Download / View File (${escapeHtml(user.block_file)})
                            </a>
                        </div>
                    </div>
                `;
            }

            if ((user.role === 'admin' || user.role === 'superadmin') && user.privileges) {
                try {
                    const privileges = JSON.parse(user.privileges);
                    if (Array.isArray(privileges) && privileges.length > 0) {
                        const privilegeNames = {
                            'view_user_logs': 'View User Logs',
                            'view_admin_logs': 'View Admin Logs',
                            'view_users': 'View All Users',
                            'create_users': 'Create Users',
                            'block_users': 'Block/Unblock Users',
                            'delete_users': 'Delete Users',
                            'edit_users': 'Edit Users',
                            'view_audit_logs': 'View Audit Logs',
                            'approve_users': 'Approve Users'
                        };

                        const privList = privileges.map(p => `<li>${privilegeNames[p] || p}</li>`).join('');
                        html += `
                            <div class="detail-item" style="grid-column: 1 / -1; margin-top: 1rem;">
                                <span class="detail-label">Admin Privileges</span>
                                <ul class="privileges-list">
                                    ${privList}
                                </ul>
                            </div>
                        `;
                    }
                } catch (e) {
                    console.error("Error parsing privileges", e);
                }
            }

            modalBody.innerHTML = html;
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.toString().replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }

        function closeLargeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Close modal on outside click
        window.onclick = function (event) {
            const viewModal = document.getElementById('viewUserModal');
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
        }

        function showToast(message, type) {
            const toast = document.createElement('div');
            toast.className = `toast toast-${type}`;
            toast.textContent = message;
            document.body.appendChild(toast);

            setTimeout(() => {
                toast.remove();
            }, 3000);
        }
    </script>
</main>
</div>
</div>
</body>

</html>