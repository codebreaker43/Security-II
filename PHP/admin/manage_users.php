<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
include("../db.php");

// Fetch the current admin's privileges
$current_admin_id = $_SESSION['user_id'];
$priv_query = mysqli_query($conn, "SELECT privileges FROM user WHERE id = $current_admin_id");
$priv_data = mysqli_fetch_assoc($priv_query);
$privileges_json = $priv_data['privileges'] ?? '[]';
$privileges = json_decode($privileges_json, true) ?? [];

$can_delete_users = in_array('delete_users', $privileges);
$can_view_users = in_array('view_users', $privileges);
$can_block = in_array('block_users', $privileges);
$can_edit = in_array('edit_users', $privileges);
$can_view_user_logs = in_array('view_user_logs', $privileges);
$can_view_admin_logs = in_array('view_admin_logs', $privileges);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - Admin</title>
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

        /* Logout Modal Styles */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content-logout {
            background: linear-gradient(135deg, rgba(30, 30, 40, 0.95), rgba(20, 20, 30, 0.98));
            padding: 2rem 2.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            text-align: center;
            max-width: 400px;
            animation: slideUp 0.3s ease;
        }

        .modal-content-logout h3 {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .modal-content-logout p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .modal-buttons-logout {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, var(--accent-blue), #4facfe);
            color: white;
        }

        .modal-btn-confirm:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(79, 172, 254, 0.4);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Professional Theme */
        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .info-box {
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-left: 4px solid var(--accent-blue);
            border-radius: 12px;
            padding: 1.5rem;
            margin-bottom: 1.5rem;
            color: white;
            box-shadow: 0 10px 20px rgba(0, 0, 0, 0.2);
        }

        .info-box strong,
        .info-box p {
            color: white !important;
            text-shadow: 1px 1px 3px rgba(0, 0, 0, 0.3);
        }

        .search-container {
            margin-bottom: 1.5rem;
        }

        .search-container input {
            padding: 1rem 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            color: var(--text-color);
            width: 100%;
            max-width: 500px;
            font-size: 1rem;
            box-shadow: 0 4px 15px rgba(0, 0, 0, 0.2);
            transition: all 0.3s ease;
        }

        .search-container input:focus {
            border-color: rgba(79, 172, 254, 0.6);
            outline: none;
            box-shadow: 0 0 15px rgba(79, 172, 254, 0.2);
        }

        .table-container {
            overflow-x: auto;
            margin-top: 1rem;
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            padding: 1.5rem;
            border-radius: 16px;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
        }

        .table-wrapper {
            background: transparent;
            border-radius: 10px;
            position: relative;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color);
        }

        th {
            background: rgba(0, 0, 0, 0.8);
            font-weight: 700;
            color: white !important;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 4px solid #050505 !important;
        }

        /* Colored headers for Professional theme */
        th {
            border-top: 4px solid var(--accent-blue) !important;
        }

        th,
        td {
            padding: 1.2rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
            margin: 0.2rem;
            transition: all 0.2s ease;
        }

        .btn-view {
            background-color: var(--accent-blue);
            color: white;
        }

        .btn-edit {
            background-color: var(--accent-orange);
            color: white;
        }

        .btn-block {
            background-color: var(--accent-red);
            color: white;
        }

        .btn-unblock {
            background-color: var(--accent-green);
            color: white;
        }

        .btn-delete {
            background-color: #ff1a1a;
            color: white;
        }

        .btn-action:hover {
            transform: translateY(-3px) scale(1.05);
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.5), inset 0 0 10px rgba(255, 255, 255, 0.5);
            filter: brightness(1.1);
        }

        .btn-action:active {
            transform: translateY(1px);
        }

        .status-active {
            color: var(--accent-green);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(40, 167, 69, 0.4);
        }

        .status-blocked {
            color: var(--accent-red);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(255, 59, 59, 0.4);
        }

        .status-inactive {
            color: #6c757d;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            text-shadow: 0 0 10px rgba(108, 117, 125, 0.4);
        }

        .status-inactive {
            color: #888;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .toast {
            position: fixed;
            bottom: 20px;
            right: 20px;
            padding: 1rem 2rem;
            border-radius: 8px;
            border: 3px solid #050505;
            color: white;
            font-weight: bold;
            z-index: 3000;
            animation: slideIn 0.3s cubic-bezier(0.175, 0.885, 0.32, 1.275);
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

        /* Professional Modal styling */
        .modal {
            display: none;
            position: fixed;
            z-index: 2000;
            left: 0;
            top: 0;
            width: 100%;
            height: 100%;
            overflow: auto;
            background-color: rgba(0, 0, 0, 0.8);
            backdrop-filter: blur(8px);
        }

        .modal-content {
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

        #deleteModal {
            align-items: center;
            justify-content: center;
            padding: 0;
            display: none;
        }

        #deleteModal .modal-content {
            max-width: 500px;
            margin: 10% auto;
        }

        .modal-inner {
            background: transparent;
            border-radius: 10px;
            padding: 1.5rem;
            position: relative;
            overflow: hidden;
            color: var(--text-color);
        }

        .modal-inner::before {
            display: none;
        }

        .modal-header,
        .modal-body,
        #editUserForm {
            position: relative;
            z-index: 2;
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

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
        }

        .form-grid label {
            display: block;
            margin-bottom: 0.5rem;
            font-size: 0.9rem;
            font-weight: 600;
            color: var(--accent-blue);
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .form-grid input,
        .form-grid select {
            width: 100%;
            padding: 1rem;
            margin-top: 0;
            margin-bottom: 0;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-color);
            transition: all 0.3s;
        }

        .form-grid input:focus,
        .form-grid select:focus {
            outline: none;
            border-color: var(--accent-yellow);
            background: rgba(0, 0, 0, 0.6);
            box-shadow: 0 0 10px rgba(255, 193, 7, 0.3);
        }

        .modal-body {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1.5rem;
            background: rgba(0, 0, 0, 0.3);
            padding: 1.5rem;
            border-radius: 12px;
            border: 1px solid rgba(255, 255, 255, 0.05);
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
                    <span style="font-weight: 800; color: #ffffff; letter-spacing: 0.5px;">ADMIN <span style="color: var(--accent-orange, #ff5500);">PANEL</span></span>
                </a>
            </div>
            <ul>
                <li><a href="dashboard.php">Dashboard</a></li>
                <li><a href="manage_users.php" style="color: var(--accent-blue);">Manage Users</a></li>
                <?php if ($can_view_user_logs || $can_view_admin_logs): ?>
                <li><a href="audit_logs.php">Audit Logs</a></li>
                <?php endif; ?>

                <button onclick="openLogoutModal()">Logout</button>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Manage Users</h1>
        <p>View and manage user accounts. <em>Note: You can only manage users with the "user" role.</em></p>

        <div class="info-box">
            <strong>Your Admin Privileges:</strong>
            <?php if ($can_view_users): ?><span style="color: var(--accent-green); margin-left: 10px;">✓ View users</span><?php endif; ?>
            <?php if ($can_block): ?><span style="color: var(--accent-green); margin-left: 10px;">✓ Block/Unblock users</span><?php endif; ?>
            <?php if ($can_edit): ?><span style="color: var(--accent-green); margin-left: 10px;">✓ Edit user info</span><?php endif; ?>
            <?php if ($can_delete_users): ?><span style="color: var(--accent-green); margin-left: 10px;">✓ Delete users</span><?php endif; ?>
        </div>

        <?php if ($can_view_users): ?>
        <div class="search-container">
            <input type="text" id="searchInput" placeholder="Search by name, username, or email..."
                onkeyup="filterTable()">
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table id="usersTable">
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
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="7" style="text-align: center;">Loading...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
        <?php else: ?>
        <div class="table-container" style="text-align: center; padding: 3rem;">
            <h2 style="color: var(--accent-red); margin-bottom: 1rem;">Access Restricted</h2>
            <p style="color: var(--text-muted);">You do not have the required "View Users" privilege to load this list.</p>
        </div>
        <?php endif; ?>
    </div>

    <!-- View User Modal -->
    <div id="viewUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2 id="modalUserName">User Details</h2>
                    <span class="close" onclick="closeModal('viewUserModal')">&times;</span>
                </div>
                <div class="modal-body" id="modalBody">
                    <!-- Details will be populated here -->
                </div>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2 style="color: var(--accent-orange);">Edit User</h2>
                    <span class="close" onclick="closeModal('editUserModal')">&times;</span>
                </div>
                <form id="editUserForm" onsubmit="event.preventDefault(); submitEditForm();">
                    <input type="hidden" id="edit_user_id">
                    <div class="form-grid">
                        <div><label>ID No</label><input type="text" id="edit_idno" readonly style="background: rgba(255,255,255,0.08); cursor: not-allowed;"></div>
                        <div><label>First Name</label><input type="text" id="edit_firstname" required></div>
                        <div><label>Middle Initial</label><input type="text" id="edit_middlename" maxlength="1"></div>
                        <div><label>Last Name</label><input type="text" id="edit_lastname" required></div>
                        <div>
                            <label>Suffix</label>
                            <select id="edit_suffix">
                                <option value="">None</option>
                                <option value="Sr">Sr.</option>
                                <option value="Jr">Jr.</option>
                            </select>
                        </div>
                        <div><label>Sex</label><select id="edit_sex">
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select></div>
                        <div><label>Birthdate</label><input type="date" id="edit_birthdate"
                                onchange="calculateAgeEdit()"></div>
                        <div><label>Age</label><input type="text" id="edit_age" readonly></div>

                        <div><label>Purok</label><input type="text" id="edit_purokstreet"></div>
                        <div><label>Barangay</label><input type="text" id="edit_brgy"></div>
                        <div><label>Municipality</label><input type="text" id="edit_municipal"></div>
                        <div><label>Province</label><input type="text" id="edit_province"></div>
                        <div><label>Country</label><input type="text" id="edit_country"></div>
                        <div><label>Zip Code</label><input type="text" id="edit_zipcode"></div>

                        <div><label>Username</label><input type="text" id="edit_username" required></div>
                        <div><label>Email</label><input type="email" id="edit_email" required></div>
                    </div>
                    <button type="submit" class="btn-action btn-block"
                        style="margin-top: 1.5rem; width: 100%; border: border-color: var(--accent-orange); font-size: 1rem; padding: 1rem;">Save
                        Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Approve User Modal -->
    <div class="modal" id="approveModal">
        <div class="modal-content">
            <div class="modal-inner" style="text-align: center;">
                <h3 style="color: var(--accent-green); margin-top: 0;">Confirm Approval</h3>
                <p>Are you sure you want to approve this user? They will be able to log in to the system.</p>
                <div class="modal-buttons" style="margin-top: 2rem;">
                    <button class="btn-action btn-unblock" id="confirmApproveBtn">Approve</button>
                    <button class="btn-action" style="background: rgba(255,255,255,0.1); color: white;"
                        onclick="closeModal('approveModal')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Delete Confirmation Modal -->
    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-inner" style="text-align: center;">
                <h3 style="color: var(--accent-red); margin-top: 0;">Confirm Delete</h3>
                <p>Are you sure you want to deactivate this user? They will no longer be able to log in to the system.</p>
                <div class="modal-buttons" style="margin-top: 2rem;">
                    <button class="btn-action btn-delete" style="border-color: #ff1a1a;" id="confirmDeleteBtn">Delete</button>
                    <button class="btn-action" style="background: rgba(255,255,255,0.1); color: white;" onclick="closeModal('deleteModal')">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-content-logout">
            <div class="modal-icon">👋</div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out of your account?</p>
            <div class="modal-buttons-logout">
                <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <script>
        const canDelete = <?php echo $can_delete_users ? 'true' : 'false'; ?>;
        const canBlock = <?php echo $can_block ? 'true' : 'false'; ?>;
        const canEdit = <?php echo $can_edit ? 'true' : 'false'; ?>;
        
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        function confirmLogout() {
            window.location.href = '../logout.php';
        }

        // Close logout modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
        
        let approveUserId = null;
        let deleteUserId = null;

        document.addEventListener('DOMContentLoaded', loadUsers);

        function loadUsers() {
            fetch('../api/manage_users_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        renderTable(data.users);
                    } else {
                        showToast(data.error || 'Failed to load users', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
        }

        function renderTable(users) {
            const tbody = document.getElementById('usersTableBody');

            // If tbody doesn't exist (e.g., table was hidden due to no view_users privilege)
            if (!tbody) return;

            if (users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="7" style="text-align: center;">No users found.</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(user => {
                let statusClass = '';
                let statusLabel = user.status ? user.status.charAt(0).toUpperCase() + user.status.slice(1) : 'Active';
                
                if (user.status === 'active') {
                    statusClass = 'status-active';
                } else if (user.status === 'pending') {
                    statusClass = 'status-blocked';
                } else if (user.status === 'inactive') {
                    statusClass = 'status-inactive';
                } else {
                    statusClass = 'status-blocked';
                }
                
                let blockButtonHtml = '';
                if (canBlock && user.status !== 'inactive') {
                    if (user.status === 'pending') {
                        blockButtonHtml = `<button class="btn-action btn-unblock" onclick="confirmApprove(${user.id})">Approve</button>`;
                    } else if (user.status === 'blocked') {
                        blockButtonHtml = `<button class="btn-action btn-unblock" onclick="unblockUser(${user.id})">Unblock</button>`;
                    } else {
                        blockButtonHtml = `<button class="btn-action btn-block" onclick="blockUser(${user.id})">Block</button>`;
                    }
                }
                
                let deleteButtonHtml = '';
                if (canDelete && user.status !== 'inactive') {
                     deleteButtonHtml = `<button class="btn-action btn-delete" onclick="confirmDelete(${user.id})">Delete</button>`;
                }

                let editButtonHtml = '';
                if (canEdit) {
                     editButtonHtml = `<button class="btn-action btn-edit" onclick="openEditModal(${user.id})">Edit</button>`;
                }

                return `
                <tr data-search="${user.firstname} ${user.lastname} ${user.username} ${user.email}".toLowerCase()>
                    <td>${user.id}</td>
                    <td>${user.idno}</td>
                    <td>${user.firstname} ${user.lastname}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td class="${statusClass}" style="${user.status === 'pending' ? 'color: var(--accent-yellow); text-shadow: 0 0 10px rgba(255, 193, 7, 0.4);' : ''}">
                        ${statusLabel}
                    </td>
                    <td>
                        <button class="btn-action btn-view" onclick="viewUser(${user.id})">View</button>
                        ${editButtonHtml}
                        ${blockButtonHtml}
                        ${deleteButtonHtml}
                    </td>
                </tr>
            `}).join('');
        }

        function filterTable() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const rows = document.querySelectorAll('#usersTableBody tr');

            rows.forEach(row => {
                const searchData = row.getAttribute('data-search') || '';
                row.style.display = searchData.includes(searchText) ? '' : 'none';
            });
        }

        function blockUser(userId) {
            sendAction(userId, 'block');
        }

        function unblockUser(userId) {
            sendAction(userId, 'unblock');
        }

        function confirmApprove(userId) {
            approveUserId = userId;
            document.getElementById('approveModal').style.display = 'flex';
        }
        
        function confirmDelete(userId) {
            deleteUserId = userId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        document.getElementById('confirmApproveBtn').addEventListener('click', function () {
            if (approveUserId) {
                sendAction(approveUserId, 'approve');
                closeModal('approveModal');
            }
        });
        
        document.getElementById('confirmDeleteBtn').addEventListener('click', function() {
            if (deleteUserId) {
                fetch('../api/manage_users_api.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: deleteUserId })
                })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadUsers();
                    } else {
                        showToast(data.error || 'Failed to delete user', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
                
                closeModal('deleteModal');
                deleteUserId = null;
            }
        });

        function sendAction(userId, action) {
            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, action: action })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadUsers();
                    } else {
                        showToast(data.error || 'Action failed', 'error');
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
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

        // Modal Functions
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
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Error loading user details', 'error');
                });
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
                { label: 'Zip Code', value: user.zipcode },
                { label: 'Address', value: `${user.purokstreet}, ${user.brgy}, ${user.municipal}, ${user.province}, ${user.country}` },
            ];

            modalBody.innerHTML = fields.map(field => `
                <div class="detail-item">
                    <span class="detail-label">${field.label}</span>
                    <span class="detail-value">${field.value || 'N/A'}</span>
                </div>
            `).join('');
        }

        function closeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // Edit User Functions
        function openEditModal(userId) {
            fetch(`../api/manage_users_api.php?user_id=${userId}`)
                .then(response => response.json())
                .then(data => {
                    if (data.success && data.users && data.users.length > 0) {
                        const user = data.users[0];
                        document.getElementById('edit_user_id').value = user.id;
                        document.getElementById('edit_idno').value = user.idno;
                        document.getElementById('edit_firstname').value = user.firstname;
                        document.getElementById('edit_middlename').value = user.middlename;
                        document.getElementById('edit_lastname').value = user.lastname;
                        document.getElementById('edit_suffix').value = (['Sr', 'Jr'].includes(user.suffix)) ? user.suffix : '';
                        document.getElementById('edit_sex').value = user.sex;
                        document.getElementById('edit_birthdate').value = user.birthdate;
                        document.getElementById('edit_age').value = user.age;

                        document.getElementById('edit_purokstreet').value = user.purokstreet;
                        document.getElementById('edit_brgy').value = user.brgy;
                        document.getElementById('edit_municipal').value = user.municipal;
                        document.getElementById('edit_province').value = user.province;
                        document.getElementById('edit_country').value = user.country;
                        document.getElementById('edit_zipcode').value = user.zipcode;

                        document.getElementById('edit_username').value = user.username;
                        document.getElementById('edit_email').value = user.email;

                        document.getElementById('editUserModal').style.display = 'block';
                    } else {
                        showToast('Failed to load user details', 'error');
                    }
                })
                .catch(error => showToast('Error fetching user data', 'error'));
        }

        function calculateAgeEdit() {
            const birthDate = new Date(document.getElementById('edit_birthdate').value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            document.getElementById('edit_age').value = age;
        }

        function submitEditForm() {
            const data = {
                action: 'update_user_info',
                user_id: document.getElementById('edit_user_id').value,
                idno: document.getElementById('edit_idno').value,
                firstname: document.getElementById('edit_firstname').value,
                middlename: document.getElementById('edit_middlename').value,
                lastname: document.getElementById('edit_lastname').value,
                suffix: document.getElementById('edit_suffix').value,
                sex: document.getElementById('edit_sex').value,
                birthdate: document.getElementById('edit_birthdate').value,
                age: document.getElementById('edit_age').value,
                purokstreet: document.getElementById('edit_purokstreet').value,
                brgy: document.getElementById('edit_brgy').value,
                municipal: document.getElementById('edit_municipal').value,
                province: document.getElementById('edit_province').value,
                country: document.getElementById('edit_country').value,
                zipcode: document.getElementById('edit_zipcode').value,
                username: document.getElementById('edit_username').value,
                email: document.getElementById('edit_email').value
            };

            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(res => {
                    if (res.success) {
                        showToast(res.message, 'success');
                        closeModal('editUserModal');
                        loadUsers();
                    } else {
                        showToast(res.error, 'error');
                    }
                })
                .catch(err => showToast('Error updating user', 'error'));
        }

        // Close modal when clicking outside
        window.onclick = function (event) {
            const viewModal = document.getElementById('viewUserModal');
            const editModal = document.getElementById('editUserModal');
            const approveModal = document.getElementById('approveModal');
            const deleteModal = document.getElementById('deleteModal');
            if (event.target == viewModal) viewModal.style.display = 'none';
            if (event.target == editModal) editModal.style.display = 'none';
            if (event.target == approveModal) approveModal.style.display = 'none';
            if (event.target == deleteModal) deleteModal.style.display = 'none';
        }
    </script>
</body>

</html>