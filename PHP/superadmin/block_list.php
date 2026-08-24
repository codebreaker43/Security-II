<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}
include("../db.php");
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Block List - Super Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            background-image:
                linear-gradient(rgba(10, 12, 18, 0.88), rgba(10, 12, 18, 0.92)),
                url('../../img/moto-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        .search-container {
            margin-bottom: 1rem;
        }

        .search-container input {
            width: 100%;
            padding: 1rem 1.5rem;
            background: rgba(20, 30, 45, 0.6);
            border: 2px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            color: var(--text-color);
            font-size: 1rem;
            backdrop-filter: blur(12px);
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            border-color: rgba(79, 172, 254, 0.6);
            outline: none;
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.2);
        }

        .table-container {
            width: 100%;
            margin-top: 1rem;
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            overflow: hidden;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
        }

        .table-wrapper {
            overflow-x: auto;
        }

        table {
            width: 100%;
            border-collapse: collapse;
        }

        th {
            background: rgba(0, 0, 0, 0.3);
            color: var(--accent-blue);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 1px;
            font-size: 0.8rem;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid rgba(79, 172, 254, 0.2);
        }

        td {
            padding: 0.8rem 1rem;
            color: var(--text-color);
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        tr:hover {
            background: rgba(79, 172, 254, 0.08);
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
    <nav class="navbar">
        <div class="navdiv">
            <div class="logo">
                <a href="dashboard.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <svg width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="15" cy="5" r="1"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
                    </svg>
                    <span style="font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">SUPER <span style="color: var(--accent-orange, #ff5500);">ADMIN</span></span>
                </a>
            </div>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_admins.php">Manage Admins</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="block_list.php" style="color: var(--accent-blue);">Block List</a></li>
                <li><a href="audit_logs.php">Audit Logs</a></li>
                <button onclick="location.href='../logout.php'">Logout</button>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Block List</h1>
        <p>View all blocked accounts and manage their status.</p>

        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by name, username, or email..."
                onkeyup="filterTable()">
        </div>

        <h2 class="section-title admin">Blocked Admins</h2>
        <div class="table-container">
            <div class="table-wrapper">
                <table id="blockListTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blockListTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center;">Loading blocked admins...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <h2 class="section-title user">Blocked Users</h2>
        <div class="table-container">
            <div class="table-wrapper">
                <table id="blockListUsersTable">
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>ID No</th>
                            <th>Name</th>
                            <th>Username</th>
                            <th>Email</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="blockListUsersTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center;">Loading blocked users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

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
        document.addEventListener('DOMContentLoaded', loadBlockedUsers);

        function loadBlockedUsers() {
            fetch('../api/manage_users_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const blockedAdmins = data.users.filter(user => (user.role === 'admin' || user.role === 'superadmin') && user.status === 'blocked');
                        const blockedUsers = data.users.filter(user => user.role === 'user' && user.status === 'blocked');
                        renderTable(blockedAdmins, 'blockListTableBody', 'No blocked admins found. All administrators are active.');
                        renderTable(blockedUsers, 'blockListUsersTableBody', 'No blocked users found. All users are active.');
                    } else {
                        showToast(data.error || 'Failed to load users', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
        }

        function renderTable(users, tbodyId, emptyMessage) {
            const tbody = document.getElementById(tbodyId);

            if (users.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state">
                                <div class="icon">✅</div>
                                <p>${emptyMessage}</p>
                            </div>
                        </td>
                    </tr>`;
                return;
            }

            tbody.innerHTML = users.map(user => `
                <tr data-search="${(user.firstname + ' ' + user.lastname + ' ' + user.username + ' ' + user.email).toLowerCase()}">
                    <td>${user.id}</td>
                    <td>${user.idno}</td>
                    <td>${user.firstname} ${user.lastname}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td class="status-blocked">Blocked</td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewUser(${user.id})">View</button>
                            <button class="btn-action btn-unblock" onclick="unblockUser(${user.id})">Unblock</button>
                        </div>
                    </td>
                </tr>
            `).join('');
        }

        function filterTable() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const adminRows = document.querySelectorAll('#blockListTableBody tr');
            const userRows = document.querySelectorAll('#blockListUsersTableBody tr');

            const filterRow = row => {
                const searchData = row.getAttribute('data-search') || '';
                row.style.display = searchData.includes(searchText) ? '' : 'none';
            };

            adminRows.forEach(filterRow);
            userRows.forEach(filterRow);
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

            if ((user.role === 'admin' || user.role === 'superadmin') && user.privileges) {
                try {
                    const privileges = JSON.parse(user.privileges);
                    if (Array.isArray(privileges) && privileges.length > 0) {
                        const privilegeNames = {
                            'view_user_logs': 'View User Logs',
                            'view_admin_logs': 'View Admin Logs',
                            'view_users': 'View All Users',
                            'block_users': 'Block/Unblock Users',
                            'delete_users': 'Delete Users',
                            'edit_users': 'Edit Users'
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
</body>

</html>