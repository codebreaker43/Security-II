<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'admin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../db.php';

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/../includes/auth_guard.php';
enforce_profile_completion($conn);

// Fetch the current admin's privileges
$current_admin_id = mysqli_real_escape_string($conn, $_SESSION['user_id'] ?? '');
$priv_query = mysqli_query($conn, "SELECT privileges FROM user WHERE idno = '$current_admin_id'");
$priv_data = mysqli_fetch_assoc($priv_query);
$privileges_json = $priv_data['privileges'] ?? '[]';
$privileges = json_decode($privileges_json, true) ?? [];

$can_delete_users = in_array('delete_users', $privileges);
$can_view_users = in_array('view_users', $privileges);
$can_block = in_array('block_users', $privileges);
$can_edit = in_array('edit_users', $privileges);
$can_create_users = in_array('create_users', $privileges);
$can_approve = in_array('approve_users', $privileges);
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>User Management - ONE PIECE Admin</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            background-image:
                linear-gradient(rgba(240, 245, 255, 0.65), rgba(248, 250, 252, 0.80)),
                url('../../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        h1 {
            font-size: 2.5rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 1px 2px rgba(255, 255, 255, 0.8);
        }

        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 0 15px rgba(245, 158, 11, 0.1);
        }

        .search-input-wrapper {
            flex: 1;
            min-width: 260px;
            position: relative;
            display: flex;
            align-items: center;
        }

        .search-input-wrapper svg {
            position: absolute;
            left: 1rem;
            width: 18px;
            height: 18px;
            stroke: #94a3b8;
            pointer-events: none;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.92rem;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        .filter-controls {
            display: flex;
            flex-wrap: wrap;
            align-items: center;
            gap: 0.8rem;
        }

        .filter-group {
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-group label {
            font-size: 0.8rem;
            color: #475569;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 0.7rem 1rem;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            background: #ffffff;
            color: #0f172a;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        .filter-group select option {
            background: #ffffff;
            color: #0f172a;
        }

        .btn-create-user {
            padding: 0.75rem 1.4rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #ff5500, #e04400);
            color: #ffffff;
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(255, 85, 0, 0.35);
            transition: all 0.25s ease;
        }

        .btn-create-user:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #ff661a, #eb4d00);
            box-shadow: 0 6px 20px rgba(255, 85, 0, 0.5);
        }

        /* Table */
        .table-card {
            background: rgba(255, 255, 255, 0.92);
            backdrop-filter: blur(14px);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-radius: 14px;
            padding: 1.5rem;
            overflow-x: auto;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.05), 0 0 15px rgba(245, 158, 11, 0.1);
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .styled-table th {
            background: rgba(254, 243, 199, 0.75) !important;
            padding: 0.9rem 1rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            color: #78350f !important;
            font-weight: 800;
            border-top: 3px solid #f59e0b;
            border-bottom: 2px solid #fde68a;
        }

        .styled-table td {
            padding: 0.9rem 1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #0f172a;
        }

        .styled-table tr:hover {
            background: rgba(254, 243, 199, 0.3);
        }

        .badge {
            display: inline-block;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            font-size: 0.75rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-role-user { background: rgba(2, 132, 199, 0.15); color: #0284c7; border: 1px solid rgba(2, 132, 199, 0.35); }
        .badge-status-active { background: rgba(34, 197, 94, 0.15); color: #15803d; border: 1px solid rgba(34, 197, 94, 0.35); }
        .badge-status-blocked { background: rgba(239, 68, 68, 0.15); color: #b91c1c; border: 1px solid rgba(239, 68, 68, 0.35); }
        .badge-status-inactive { background: rgba(148, 163, 184, 0.2); color: #475569; border: 1px solid rgba(148, 163, 184, 0.35); }

        .action-btn-group {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .btn-action {
            padding: 0.4rem 0.75rem;
            border-radius: 6px;
            font-size: 0.78rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
        }

        .btn-view { background: #f1f5f9; color: #0284c7; border-color: #cbd5e1; }
        .btn-view:hover { background: #e0f2fe; }
        .btn-edit { background: #fef3c7; color: #92400e; border-color: #f59e0b; }
        .btn-edit:hover { background: #fde68a; }
        .btn-block { background: #fee2e2; color: #b91c1c; border-color: #fca5a5; }
        .btn-block:hover { background: #fecaca; }
        .btn-unblock { background: #dcfce7; color: #15803d; border-color: #86efac; }
        .btn-unblock:hover { background: #bbf7d0; }
        .btn-delete { background: #fee2e2; color: #dc2626; border-color: #fca5a5; }
        .btn-delete:hover { background: #fecaca; }

        .action-select {
            background: #ffffff;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            border-radius: 6px;
            padding: 0.4rem 0.8rem;
            font-size: 0.82rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all 0.2s ease;
            min-width: 105px;
        }

        .action-select:hover {
            border-color: #f59e0b;
            box-shadow: 0 0 10px rgba(245, 158, 11, 0.2);
            background: #ffffff;
        }

        .action-select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 2px rgba(245, 158, 11, 0.25);
        }

        .action-select option {
            background: #ffffff;
            color: #0f172a;
            padding: 0.4rem;
        }

        .action-select option.opt-delete {
            color: #dc2626;
        }

        /* Modal */
        .app-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.5);
            backdrop-filter: blur(6px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            padding: 1.5rem;
            animation: fadeIn 0.2s ease;
        }

        .app-modal-overlay.active {
            display: flex;
        }

        .app-modal-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.96) 0%, rgba(24, 34, 53, 0.94) 100%);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 16px;
            box-shadow: 0 25px 65px rgba(0, 0, 0, 0.6), 0 0 35px rgba(245, 158, 11, 0.15);
            max-width: 520px;
            width: 100%;
            padding: 2rem;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
            color: #ffffff;
        }

        .modal-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.15);
        }

        .modal-title-row h3 {
            font-size: 1.35rem;
            color: #ffffff;
            font-weight: 700;
            margin: 0;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
        }

        .btn-modal-close {
            background: transparent;
            border: none;
            color: #fbbf24;
            font-size: 1.6rem;
            cursor: pointer;
            line-height: 1;
            transition: color 0.2s ease;
        }

        .btn-modal-close:hover {
            color: #fde047;
        }

        .form-group-modal {
            margin-bottom: 1.1rem;
            text-align: left;
        }

        .form-group-modal label {
            display: block;
            font-size: 0.82rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #fbbf24;
            margin-bottom: 0.35rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .form-control-modal {
            width: 100%;
            padding: 0.75rem 0.95rem;
            border-radius: 8px;
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            background: rgba(8, 12, 22, 0.75);
            color: #ffffff !important;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        .form-control-modal::placeholder {
            color: #94a3b8 !important;
            font-weight: 500;
        }

        .form-control-modal:focus {
            border-color: #fbbf24 !important;
            background: rgba(15, 23, 42, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.35) !important;
            color: #ffffff !important;
        }

        select.form-control-modal option {
            background-color: #0f172a !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 8px 12px;
        }

        /* Autofill High Contrast Fix */
        .form-control-modal:-webkit-autofill,
        .form-control-modal:-webkit-autofill:hover, 
        .form-control-modal:-webkit-autofill:focus, 
        .form-control-modal:-webkit-autofill:active {
            -webkit-box-shadow: 0 0 0 1000px #090d16 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Real-time Field Error Messages */
        .error-msg {
            color: #ff4d4d;
            font-size: 0.76rem;
            font-weight: 600;
            display: none;
            margin-top: 5px;
            line-height: 1.2;
            animation: fadeInMsg 0.2s ease;
        }

        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-error-highlight {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.35) !important;
            background: rgba(45, 15, 20, 0.7) !important;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .details-cell {
            background: rgba(255, 255, 255, 0.03);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 8px;
            padding: 0.75rem;
        }

        .details-cell .label {
            font-size: 0.72rem;
            color: #94a3b8;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            font-weight: 700;
        }

        .details-cell .value {
            font-size: 0.95rem;
            color: #ffffff;
            font-weight: 600;
            margin-top: 0.25rem;
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/../includes/sidebar.php';
        render_sidebar('manage_users');
        ?>

        <div class="app-main">
            <main class="main-content">
    <div class="container" style="max-width: 1200px; margin: 2rem auto;">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1.5rem;">
            <div>
                <h1>User Management</h1>
                <p style="color: #94a3b8; font-size: 0.95rem;">Manage registered user accounts</p>
            </div>
            <?php if ($can_create_users): ?>
            <button class="btn-create-user" onclick="openCreateUserModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create User
            </button>
            <?php endif; ?>
        </div>


        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search by ID Number, Username, Name, Email..." oninput="filterUsers()">
            </div>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter" onchange="filterUsers()">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Users Table -->
        <div class="table-card">
            <table class="styled-table">
                <thead>
                    <tr>
                        <th>ID Number</th>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Role</th>
                        <th>Status</th>
                        <th>Actions</th>
                    </tr>
                </thead>
                <tbody id="usersTableBody">
                    <tr><td colspan="6" style="text-align: center; color: #94a3b8;">Loading user accounts...</td></tr>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Create User Modal (4 fields: ID Number, Username, Role (User), Default Password) -->
    <div class="app-modal-overlay" id="createUserModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>Create User Account</h3>
                <button class="btn-modal-close" onclick="closeModal('createUserModal')">&times;</button>
            </div>
            <form id="createUserForm" onsubmit="handleCreateUser(event)" novalidate>
                <div class="form-group-modal">
                    <label>ID Number <span style="color: #94a3b8; font-size: 0.75rem; font-weight: normal;">(Auto-generated)</span></label>
                    <input type="text" id="createIdNo" class="form-control-modal" readonly placeholder="Generating..." style="background: rgba(255,255,255,0.05); color: #94a3b8; cursor: not-allowed; font-family: monospace;">
                    <small class="error-msg" id="createIdNo_error"></small>
                </div>

                <div class="form-group-modal">
                    <label>Username <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="createUsername" class="form-control-modal" placeholder="Enter username" maxlength="30">
                    <small class="error-msg" id="createUsername_error"></small>
                </div>

                <div class="form-group-modal">
                    <label>Role</label>
                    <input type="text" class="form-control-modal" value="User" readonly style="color: #94a3b8; background: rgba(255,255,255,0.05);">
                </div>

                <div class="form-group-modal">
                    <label>Password <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="createPassword" class="form-control-modal" value="" placeholder="Enter password" style="padding-right: 2.5rem; font-family: monospace;" oninput="checkCreatePasswordStrength()" onblur="checkCreatePassword(true)">
                        <button type="button" class="password-toggle-btn" onclick="toggleCreatePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    <!-- Password Strength Bar (hidden initially when blank) -->
                    <div id="createPasswordStrengthWrap" style="margin-top: 8px; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.73rem; color: #94a3b8;">Password Strength:</span>
                            <span id="createStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #94a3b8;">Weak</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                            <div id="createBar1" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar2" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar3" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar4" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                        </div>
                        <small style="color: #94a3b8; font-size: 0.72rem; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
                    </div>
                    <small style="color: #94a3b8; font-size: 0.75rem; display: block; margin-top: 4px;">User will be prompted to change password on first login.</small>
                    <small class="error-msg" id="createPassword_error"></small>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.5rem;">
                    <button type="button" class="btn-action" style="background: rgba(255,255,255,0.1); color: #fff;" onclick="closeModal('createUserModal')">Cancel</button>
                    <button type="submit" class="btn-create-user">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal (NO Password Displayed) -->
    <div class="app-modal-overlay" id="viewDetailsModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>User Details</h3>
                <button class="btn-modal-close" onclick="closeModal('viewDetailsModal')">&times;</button>
            </div>
            <div class="details-grid">
                <div class="details-cell">
                    <div class="label">ID Number</div>
                    <div class="value" id="viewIdNo">--</div>
                </div>
                <div class="details-cell">
                    <div class="label">Username</div>
                    <div class="value" id="viewUsername">--</div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1;">
                    <div class="label">Full Name</div>
                    <div class="value" id="viewFullName">--</div>
                </div>
                <div class="details-cell">
                    <div class="label">Role</div>
                    <div class="value" id="viewRole">USER</div>
                </div>
                <div class="details-cell">
                    <div class="label">Account Status</div>
                    <div class="value" id="viewStatus">--</div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1; display: none;" id="viewReasonCell">
                    <div class="label" id="viewReasonLabel" style="color: #f87171;">Reason</div>
                    <div class="value" id="viewReasonVal" style="color: #fca5a5; font-size: 0.85rem; white-space: pre-wrap; margin-top: 0.25rem;"></div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1; display: none;" id="viewFileCell">
                    <div class="label" style="color: #60a5fa;">Attached Supporting File</div>
                    <div class="value" id="viewFileVal" style="margin-top: 0.25rem;"></div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="btn-action" style="background: rgba(255,255,255,0.1); color: #fff; padding: 0.6rem 1.2rem;" onclick="closeModal('viewDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit User Modal -->
    <div class="app-modal-overlay" id="editUserModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>Edit User Account</h3>
                <button class="btn-modal-close" onclick="closeModal('editUserModal')">&times;</button>
            </div>
            <form id="editUserForm" onsubmit="promptSensitiveAction(event, 'update_user_info')" novalidate>
                <input type="hidden" id="editUserId">

                <div class="form-group-modal">
                    <label>ID Number <span style="color: #94a3b8; font-size: 0.75rem; font-weight: normal;">(Cannot be modified)</span></label>
                    <input type="text" id="editIdNo" class="form-control-modal" readonly style="background: rgba(255,255,255,0.05); color: #94a3b8; cursor: not-allowed; font-family: monospace;" required>
                </div>

                <div class="form-group-modal">
                    <label>Full Name <span style="color: #ef4444;">*</span></label>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 0.5rem;">
                        <div>
                            <input type="text" id="editFirstName" class="form-control-modal" placeholder="First Name" required maxlength="25" oninput="checkEditFirstName(false)" onblur="checkEditFirstName(true)">
                            <small class="error-msg" id="editFirstName_error"></small>
                        </div>
                        <div>
                            <input type="text" id="editLastName" class="form-control-modal" placeholder="Last Name" required maxlength="25" oninput="checkEditLastName(false)" onblur="checkEditLastName(true)">
                            <small class="error-msg" id="editLastName_error"></small>
                        </div>
                    </div>
                </div>

                <div class="form-group-modal">
                    <label>Username <span style="color: #94a3b8; font-size: 0.75rem; font-weight: normal;">(Cannot be modified)</span></label>
                    <input type="text" id="editUsername" class="form-control-modal" readonly style="background: rgba(255,255,255,0.05); color: #94a3b8; cursor: not-allowed;" required>
                </div>

                <div class="form-group-modal">
                    <label>Account Status</label>
                    <select id="editStatus" class="form-control-modal" required>
                        <option value="active">Active</option>
                        <option value="blocked">Blocked</option>
                        <option value="pending">Pending</option>
                        <option value="inactive">Inactive</option>
                    </select>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('editUserModal')">Cancel</button>
                    <button type="submit" class="btn-create-user">Proceed to Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sensitive Action Confirmation & Admin Password Verification Modal -->
    <div class="app-modal-overlay" id="verificationModal">
        <div class="app-modal-card" style="max-width: 440px; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">&#128274;</div>
            <h3 id="verifTitle" style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.25rem;">Confirm Action</h3>
            <p id="verifPrompt" style="color: #cbd5e1; font-size: 0.88rem; margin-bottom: 1.25rem;">Please enter your current administrator password to confirm this action.</p>

            <form id="verificationForm" onsubmit="executeVerifiedAction(event)">
                <!-- Reason input (for delete and block) -->
                <div class="form-group-modal" id="reasonGroup" style="display: none; text-align: left;">
                    <label>Reason <span style="color: #ef4444;">*</span></label>
                    <textarea id="actionReason" class="form-control-modal" placeholder="Enter reason for this action..." rows="2"></textarea>
                    
                    <label style="margin-top: 0.85rem; display: block; font-size: 0.82rem; color: #cbd5e1;">Attach Supporting File / Document <span style="color: #94a3b8; font-weight: normal; font-size: 0.75rem;">(Optional)</span></label>
                    <input type="file" id="actionFile" class="form-control-modal" style="padding: 0.45rem 0.6rem; font-size: 0.82rem; color: #94a3b8; background: rgba(255,255,255,0.05);" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
                </div>

                <div class="form-group-modal" style="text-align: left;">
                    <label>Your Administrator Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="adminConfirmPassword" class="form-control-modal" placeholder="Enter current password" required autocomplete="current-password">
                </div>

                <div style="display: flex; justify-content: center; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('verificationModal')">Cancel</button>
                    <button type="submit" class="btn-create-user" style="padding: 0.65rem 1.6rem;">Confirm &amp; Execute</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Logout Modal (One Piece Pirate Theme) -->
    <div class="app-modal-overlay" id="logoutModal">
        <div class="app-modal-card" style="max-width: 420px; text-align: center;">
            <div class="pirate-emblem-ring" style="width: 60px; height: 60px; margin: 0 auto 1.1rem auto;">
                <svg width="40" height="40" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                    <path d="M14 14 L50 50 M50 14 L14 50" stroke="#e2e8f0" stroke-width="5" stroke-linecap="round"/>
                    <circle cx="12" cy="12" r="3.5" fill="#cbd5e1"/>
                    <circle cx="12" cy="16" r="3.5" fill="#cbd5e1"/>
                    <circle cx="52" cy="12" r="3.5" fill="#cbd5e1"/>
                    <circle cx="52" cy="16" r="3.5" fill="#cbd5e1"/>
                    <circle cx="12" cy="48" r="3.5" fill="#cbd5e1"/>
                    <circle cx="12" cy="52" r="3.5" fill="#cbd5e1"/>
                    <circle cx="52" cy="48" r="3.5" fill="#cbd5e1"/>
                    <circle cx="52" cy="52" r="3.5" fill="#cbd5e1"/>
                    <circle cx="32" cy="34" r="14" fill="#f8fafc"/>
                    <rect x="26" y="42" width="12" height="7" rx="2" fill="#f8fafc"/>
                    <circle cx="27.5" cy="35" r="3.2" fill="#0f172a"/>
                    <circle cx="36.5" cy="35" r="3.2" fill="#0f172a"/>
                    <line x1="28.5" y1="44" x2="28.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                    <line x1="32" y1="44" x2="32" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                    <line x1="35.5" y1="44" x2="35.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                    <ellipse cx="32" cy="24" rx="12" ry="7.5" fill="#f59e0b"/>
                    <ellipse cx="32" cy="27" rx="12.2" ry="3" fill="#ef4444"/>
                    <ellipse cx="32" cy="28" rx="22" ry="5" fill="#f59e0b"/>
                </svg>
            </div>
            <h3 style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.4rem; font-weight: 700;">Disembark <span style="color: #f59e0b;">Ship?</span></h3>
            <p style="color: #cbd5e1; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">Are you sure you want to log out of your One Piece crew session?</p>
            <div style="display: flex; justify-content: center; gap: 0.85rem;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('logoutModal')">Stay on Board</button>
                <button type="button" class="btn-create-user" style="padding: 0.65rem 1.6rem;" onclick="confirmLogout()">Log Out</button>
            </div>
        </div>
    </div>

    <script>
        const canApprove = <?php echo $can_approve ? 'true' : 'false'; ?>;
        let allUsers = [];
        let pendingActionPayload = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadUsers();
            initCreateUserListeners();
        });

        function initCreateUserListeners() {
            const uInput = document.getElementById("createUsername");
            if (uInput) {
                uInput.addEventListener("input", () => checkCreateUserUsername(false));
                uInput.addEventListener("blur", () => {
                    if (checkCreateUserUsername(true)) {
                        checkCreateUserUsernameDuplicate();
                    }
                });
            }

            const pInput = document.getElementById("createPassword");
            if (pInput) {
                pInput.addEventListener("input", () => checkCreateUserPassword(false));
                pInput.addEventListener("blur", () => checkCreateUserPassword(true));
            }

            const idInput = document.getElementById("createIdNo");
            if (idInput) {
                idInput.addEventListener("input", () => checkCreateUserIdNo(false));
                idInput.addEventListener("blur", () => checkCreateUserIdNo(true));
            }
        }

        function loadUsers() {
            fetch('../api/manage_users_api.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allUsers = data.users || [];
                        renderUsers(allUsers);
                    } else {
                        alert(data.error || 'Failed to load users');
                    }
                })
                .catch(err => console.error(err));
        }

        function renderUsers(users) {
            const tbody = document.getElementById('usersTableBody');
            if (!users || users.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 2rem;">No user accounts found.</td></tr>';
                return;
            }

            tbody.innerHTML = users.map(u => {
                const fullName = `${u.firstname || ''} ${u.lastname || ''}`.trim() || u.username;
                const statusBadge = `<span class="badge badge-status-${u.status}">${u.status}</span>`;

                let actionOptions = `
                    <option value="" disabled selected>Actions</option>
                    <option value="view">View</option>
                    <option value="edit">Edit</option>
                `;

                if (u.status === 'active') {
                    actionOptions += `<option value="block">Block</option>`;
                } else if (u.status === 'blocked') {
                    actionOptions += `<option value="unblock">Unblock</option>`;
                }

                actionOptions += `<option value="delete" class="opt-delete">Delete</option>`;

                return `
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: #cbd5e1;">${u.idno || 'N/A'}</td>
                        <td><strong>${escapeHtml(fullName)}</strong></td>
                        <td style="color: #60a5fa;">${escapeHtml(u.username)}</td>
                        <td><span class="badge badge-role-user">USER</span></td>
                        <td>${statusBadge}</td>
                        <td>
                            <select class="action-select" onchange="handleUserAction(this, '${u.idno}')">
                                ${actionOptions}
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function handleUserAction(selectEl, idno) {
            const action = selectEl.value;
            selectEl.value = '';
            if (!action) return;

            const u = allUsers.find(a => (a.idno || a.id) == idno);
            const username = u ? (u.username || idno) : idno;

            if (action === 'view') {
                openViewDetails(idno);
            } else if (action === 'edit') {
                openEditUser(idno);
            } else if (action === 'block') {
                confirmSensitiveAction('block', idno, 'Are you sure you want to block this user account?', true);
            } else if (action === 'unblock') {
                confirmSensitiveAction('unblock', idno, 'Are you sure you want to unblock this user account?');
            } else if (action === 'delete') {
                confirmSensitiveAction('delete', idno, `Are you sure you want to permanently delete account (${escapeHtml(username)})? This will remove the record completely from the database.`, true);
            }
        }

        function filterUsers() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const status = document.getElementById('statusFilter').value;

            const filtered = allUsers.filter(u => {
                const fullName = `${u.firstname || ''} ${u.lastname || ''}`.toLowerCase();
                const username = (u.username || '').toLowerCase();
                const idno = (u.idno || '').toLowerCase();
                const email = (u.email || '').toLowerCase();

                const matchesSearch = !search || fullName.includes(search) || username.includes(search) || idno.includes(search) || email.includes(search);
                const matchesStatus = status === 'all' || u.status === status;

                return matchesSearch && matchesStatus;
            });

            renderUsers(filtered);
        }

        function setCreateUserError(inputId, msg) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = msg;
                errEl.style.display = "block";
            }
            if (inpEl) inpEl.classList.add("input-error-highlight");
        }

        function clearCreateUserError(inputId) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = "";
                errEl.style.display = "none";
            }
            if (inpEl) inpEl.classList.remove("input-error-highlight");
        }

        function clearAllCreateUserErrors() {
            ['createIdNo', 'createUsername', 'createPassword'].forEach(id => clearCreateUserError(id));
        }

        function toggleCreatePasswordVisibility(btn) {
            const input = document.getElementById('createPassword');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }

        function checkCreatePasswordStrength() {
            const pwdInput = document.getElementById('createPassword');
            const wrap = document.getElementById('createPasswordStrengthWrap');
            const label = document.getElementById('createStrengthLabel');
            const bars = [
                document.getElementById('createBar1'),
                document.getElementById('createBar2'),
                document.getElementById('createBar3'),
                document.getElementById('createBar4')
            ];

            if (!pwdInput) return false;
            const pwd = pwdInput.value;

            if (!pwd || pwd.length === 0) {
                if (wrap) wrap.style.display = 'none';
                clearCreateUserError('createPassword');
                bars.forEach(b => {
                    if (b) b.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                });
                if (label) label.textContent = '';
                return false;
            }

            if (wrap) wrap.style.display = 'block';

            const matchedCases = ["[A-Z]", "[0-9]", "[!@#$%^&*()_+\\-=\\[\\]{};':\"\\\\|,.<>\\/?]", ".{8,}"];
            let score = 0;
            matchedCases.forEach(pattern => {
                if (new RegExp(pattern).test(pwd)) score++;
            });

            let color = "#ef4444";
            let levelText = "Weak password";
            switch (score) {
                case 0:
                case 1:
                    levelText = "Weak password";
                    color = "#ef4444";
                    break;
                case 2:
                    levelText = "Fair password";
                    color = "#f97316";
                    break;
                case 3:
                    levelText = "Medium password";
                    color = "#f59e0b";
                    break;
                case 4:
                    levelText = "Strong password";
                    color = "#10b981";
                    break;
            }

            bars.forEach((b, idx) => {
                if (b) {
                    if (idx < score) {
                        b.style.backgroundColor = color;
                    } else {
                        b.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                    }
                }
            });

            if (label) {
                label.textContent = levelText;
                label.style.color = color;
            }

            if (pwd.length < 8) {
                setCreateUserError('createPassword', 'Password must be at least 8 characters long.');
                return false;
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setCreateUserError('createPassword', 'Must include uppercase, number, and special symbol.');
                return false;
            } else {
                clearCreateUserError('createPassword');
                return true;
            }
        }

        function checkCreatePassword(showEmpty) {
            const el = document.getElementById("createPassword");
            if (!el) return true;
            const val = el.value;
            if (!val) {
                if (showEmpty) {
                    setCreateUserError("createPassword", "Password is required.");
                    return false;
                }
                clearCreateUserError("createPassword");
                return false;
            }
            return checkCreatePasswordStrength();
        }

        function checkCreateUsername(showEmpty) {
            const el = document.getElementById("createUsername");
            if (!el) return true;
            const val = el.value.trim();
            if (!val) {
                if (showEmpty) {
                    setCreateUserError("createUsername", "Username is required.");
                    return false;
                }
                clearCreateUserError("createUsername");
                return false;
            }
            if (val.length < 3) {
                setCreateUserError("createUsername", "Username must be at least 3 characters.");
                return false;
            }
            if (val.length > 30) {
                setCreateUserError("createUsername", "Username cannot exceed 30 characters.");
                return false;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(val)) {
                setCreateUserError("createUsername", "Username can only contain letters, numbers, and underscores.");
                return false;
            }
            clearCreateUserError("createUsername");
            return true;
        }

        function openCreateUserModal() {
            document.getElementById('createUserForm').reset();
            clearAllCreateUserErrors();
            const pwdInput = document.getElementById('createPassword');
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.type = 'password';
            }
            checkCreatePasswordStrength();
            const idInput = document.getElementById('createIdNo');
            if (idInput) {
                idInput.value = 'Generating...';
            }
            fetch('../api/manage_users_api.php?action=get_next_idno&role=user')
                .then(r => r.json())
                .then(d => {
                    if (d.success && idInput) document.getElementById('createIdNo').value = d.idno;
                })
                .catch(() => {
                    if (idInput) idInput.value = '';
                });
            openModal('createUserModal');
        }

        function handleCreateUser(e) {
            e.preventDefault();
            const vUn = checkCreateUsername(true);
            const vPw = checkCreatePassword(true);

            if (!vUn) {
                document.getElementById('createUsername').focus();
                return;
            }
            if (!vPw) {
                document.getElementById('createPassword').focus();
                return;
            }

            const usernameVal = document.getElementById('createUsername').value.trim();
            const passwordVal = document.getElementById('createPassword').value;

            const payload = {
                action: 'create_user',
                idno: document.getElementById('createIdNo').value.trim(),
                username: usernameVal,
                password: passwordVal,
                role: 'user'
            };

            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    closeModal('createUserModal');
                    loadUsers();
                } else {
                    if (res.error && res.error.toLowerCase().includes('username')) {
                        setCreateUserError('createUsername', res.error);
                        document.getElementById('createUsername').focus();
                    } else if (res.error && res.error.toLowerCase().includes('password')) {
                        setCreateUserError('createPassword', res.error);
                        document.getElementById('createPassword').focus();
                    } else {
                        alert(res.error || 'Failed to create user');
                    }
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        function openViewDetails(userId) {
            const u = allUsers.find(a => (a.idno || a.id) == userId);
            if (!u) return;

            document.getElementById('viewIdNo').textContent = u.idno || 'N/A';
            document.getElementById('viewUsername').textContent = u.username;
            document.getElementById('viewFullName').textContent = `${u.firstname || ''} ${u.lastname || ''}`.trim() || 'N/A';
            document.getElementById('viewRole').textContent = 'USER';
            document.getElementById('viewStatus').textContent = u.status.toUpperCase();

            const reasonCell = document.getElementById('viewReasonCell');
            const fileCell = document.getElementById('viewFileCell');
            const activeReason = u.block_reason || u.delete_reason;
            const activeFile = u.block_file || u.delete_file;

            if (activeReason) {
                reasonCell.style.display = 'block';
                document.getElementById('viewReasonLabel').textContent = u.block_reason ? 'Block Reason' : 'Deactivation / Delete Reason';
                document.getElementById('viewReasonVal').textContent = activeReason;
            } else {
                reasonCell.style.display = 'none';
            }

            if (activeFile) {
                fileCell.style.display = 'block';
                document.getElementById('viewFileVal').innerHTML = `<a href="../../uploads/${encodeURIComponent(activeFile)}" target="_blank" download style="display: inline-flex; align-items: center; gap: 0.4rem; color: #93c5fd; text-decoration: underline; font-size: 0.85rem;"><span>&#128190;</span> Download / View File (${escapeHtml(activeFile)})</a>`;
            } else {
                fileCell.style.display = 'none';
            }

            openModal('viewDetailsModal');
        }

        function openEditUser(userId) {
            const u = allUsers.find(a => (a.idno || a.id) == userId);
            if (!u) return;

            clearAllEditUserErrors();
            document.getElementById('editUserId').value = u.idno || u.id;
            document.getElementById('editIdNo').value = u.idno || '';
            document.getElementById('editFirstName').value = u.firstname || '';
            document.getElementById('editLastName').value = u.lastname || '';
            document.getElementById('editUsername').value = u.username || '';
            document.getElementById('editStatus').value = u.status;

            openModal('editUserModal');
        }

        function setEditUserError(inputId, msg) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = msg;
                errEl.style.display = "block";
            }
            if (inpEl) inpEl.classList.add("input-error-highlight");
        }

        function clearEditUserError(inputId) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = "";
                errEl.style.display = "none";
            }
            if (inpEl) inpEl.classList.remove("input-error-highlight");
        }

        function clearAllEditUserErrors() {
            ['editFirstName', 'editLastName'].forEach(id => clearEditUserError(id));
        }

        // Left-to-Right Name Field Scanner (Same as registration.php / signup.js)
        function scanNameField(val, fieldLabel) {
            if (val === "") return "";

            // 1. Check leading space at index 0
            if (val.charAt(0) === ' ') {
                return "The first character of the " + fieldLabel.toLowerCase() + " should not be a space.";
            }

            // 2. Scan characters left-to-right (from index 0 to end)
            for (var i = 0; i < val.length; i++) {
                var char = val.charAt(i);

                // Double space starting at index i
                if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
                    return fieldLabel + " cannot contain double space.";
                }

                // 3 consecutive repeated letters starting at index i
                if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
                    if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                        return fieldLabel + " cannot contain three consecutive repeated letters.";
                    }
                }

                // Number check at index i
                if (/[0-9]/.test(char)) {
                    return fieldLabel + " cannot contain numbers.";
                }

                // Special character check at index i
                if (/[^a-zA-Z\s]/.test(char)) {
                    return fieldLabel + " cannot contain special characters.";
                }

                // Capitalization check
                if (i === 0) {
                    if (/[a-z]/.test(char)) {
                        return "The first letter of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else if (val.charAt(i - 1) === ' ') {
                    // First character of the second/next word
                    if (/[a-z]/.test(char)) {
                        return "The second word of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else {
                    // Letters within the word after the first letter must be lowercase
                    if (/[A-Z]/.test(char)) {
                        return "The first letter of " + fieldLabel.toLowerCase() + " should be the only capitalized.";
                    }
                }
            }

            // 3. Length checks (only if character-by-character scan passed)
            if (val.length < 3) {
                return fieldLabel + " fields must be at least 3 characters.";
            }
            if (val.length > 25) {
                return fieldLabel + " should not exceed 25 characters.";
            }

            return "";
        }

        function checkEditFirstName(showEmpty) {
            const el = document.getElementById('editFirstName');
            if (!el) return true;
            const val = el.value;

            if (val === "") {
                if (showEmpty) {
                    setEditUserError('editFirstName', 'Firstname fields must be filled in.');
                    return false;
                }
                clearEditUserError('editFirstName');
                return false;
            }

            const err = scanNameField(val, "Firstname");
            if (err) {
                setEditUserError('editFirstName', err);
                return false;
            }

            clearEditUserError('editFirstName');
            return true;
        }

        function checkEditLastName(showEmpty) {
            const el = document.getElementById('editLastName');
            if (!el) return true;
            const val = el.value;

            if (val === "") {
                if (showEmpty) {
                    setEditUserError('editLastName', 'Lastname fields must be filled in.');
                    return false;
                }
                clearEditUserError('editLastName');
                return false;
            }

            const err = scanNameField(val, "Lastname");
            if (err) {
                setEditUserError('editLastName', err);
                return false;
            }

            clearEditUserError('editLastName');
            return true;
        }

        function validateEditUserForm() {
            const vFn = checkEditFirstName(true);
            const vLn = checkEditLastName(true);

            if (!vFn) { document.getElementById('editFirstName').focus(); return false; }
            if (!vLn) { document.getElementById('editLastName').focus(); return false; }

            return true;
        }

        function promptSensitiveAction(e, actionType) {
            e.preventDefault();
            if (actionType === 'update_user_info') {
                if (!validateEditUserForm()) {
                    return;
                }

                pendingActionPayload = {
                    action: 'update_user_info',
                    user_id: document.getElementById('editUserId').value,
                    idno: document.getElementById('editIdNo').value.trim(),
                    firstname: document.getElementById('editFirstName').value.trim(),
                    lastname: document.getElementById('editLastName').value.trim(),
                    username: document.getElementById('editUsername').value.trim(),
                    role: 'user',
                    status: document.getElementById('editStatus').value
                };

                closeModal('editUserModal');
                setupVerificationModal('Are you sure you want to edit this user account?', false);
            }
        }

        function confirmSensitiveAction(action, userId, promptText, requiresReason = false) {
            pendingActionPayload = {
                action: action,
                user_id: userId
            };
            setupVerificationModal(promptText, requiresReason);
        }

        function setupVerificationModal(promptText, requiresReason) {
            document.getElementById('verifPrompt').textContent = promptText;
            document.getElementById('adminConfirmPassword').value = '';
            const reasonGroup = document.getElementById('reasonGroup');
            const reasonInput = document.getElementById('actionReason');
            const fileInput = document.getElementById('actionFile');
            reasonInput.value = '';
            if (fileInput) fileInput.value = '';

            if (requiresReason) {
                reasonGroup.style.display = 'block';
                reasonInput.required = true;
            } else {
                reasonGroup.style.display = 'none';
                reasonInput.required = false;
            }

            openModal('verificationModal');
        }

        function executeVerifiedAction(e) {
            e.preventDefault();
            if (!pendingActionPayload) return;

            const password = document.getElementById('adminConfirmPassword').value;
            pendingActionPayload.admin_password = password;

            const reasonGroup = document.getElementById('reasonGroup');
            if (reasonGroup && reasonGroup.style.display !== 'none') {
                const reason = document.getElementById('actionReason').value.trim();
                if (!reason) {
                    alert('Please provide a reason to proceed.');
                    return;
                }
                pendingActionPayload.reason = reason;
            }

            const formData = new FormData();
            for (const key in pendingActionPayload) {
                if (typeof pendingActionPayload[key] === 'object' && pendingActionPayload[key] !== null) {
                    formData.append(key, JSON.stringify(pendingActionPayload[key]));
                } else {
                    formData.append(key, pendingActionPayload[key]);
                }
            }

            const fileInput = document.getElementById('actionFile');
            if (fileInput && fileInput.files.length > 0) {
                formData.append('reason_file', fileInput.files[0]);
            }

            fetch('../api/manage_users_api.php', {
                method: 'POST',
                body: formData
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    closeModal('verificationModal');
                    loadUsers();
                } else {
                    alert('Action Failed: ' + (res.error || 'Unknown error'));
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        function openModal(id) {
            document.getElementById(id)?.classList.add('active');
        }

        function closeModal(id) {
            document.getElementById(id)?.classList.remove('active');
        }

        function openLogoutModal() {
            openModal('logoutModal');
        }

        function confirmLogout() {
            window.location.href = '../logout.php';
        }

        function escapeHtml(text) {
            if (!text) return '';
            return text.replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;").replace(/"/g, "&quot;").replace(/'/g, "&#039;");
        }
    </script>
</main>
</div>
</div>
</body>

</html>