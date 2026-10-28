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
    <title>Crew Management - ONE PIECE Super Admin</title>
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
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
        }

        .page-subtitle {
            color: #ffffff !important;
            font-size: 1.05rem;
            font-weight: 600;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.85);
            margin-top: 0.25rem;
        }

        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 14px;
            padding: 1rem 1.25rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
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
            stroke: #64748b;
            pointer-events: none;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a !important;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input::placeholder {
            color: #64748b !important;
            font-weight: 500;
        }

        .search-input-wrapper input:focus {
            border-color: #f59e0b !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
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
            font-size: 0.84rem;
            color: #1e293b;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .filter-group select,
        .filter-group input {
            padding: 0.7rem 1rem;
            border-radius: 10px;
            border: 1.5px solid #cbd5e1;
            background: #f8fafc;
            color: #0f172a !important;
            font-size: 0.88rem;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
        }

        .filter-group select {
            cursor: pointer;
        }

        .filter-group input {
            width: 140px;
            font-family: monospace;
            font-weight: 700;
            color: #0284c7 !important;
        }

        .filter-group input::placeholder {
            color: #94a3b8 !important;
            font-family: inherit;
            font-weight: 500;
        }

        .filter-group select:focus,
        .filter-group input:focus {
            border-color: #f59e0b !important;
            background: #ffffff !important;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2) !important;
        }

        .filter-group select option {
            background: #ffffff !important;
            color: #0f172a !important;
            font-weight: 600;
            padding: 8px 12px;
        }

        .btn-create-account {
            padding: 0.75rem 1.4rem;
            border-radius: 10px;
            background: linear-gradient(135deg, #ef4444, #f59e0b);
            color: #ffffff;
            border: none;
            font-weight: 700;
            font-size: 0.9rem;
            cursor: pointer;
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.38);
            transition: all 0.25s ease;
        }

        .btn-create-account:hover {
            transform: translateY(-2px);
            background: linear-gradient(135deg, #f87171, #fbbf24);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.55);
        }

        /* Table Styles - Solid White Card */
        .table-card {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            overflow-x: auto;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .styled-table th {
            background: #f8fafc;
            padding: 1rem 1.1rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: #334155;
            font-weight: 800;
            border-bottom: 2px solid #e2e8f0;
            text-shadow: none;
        }

        .styled-table td {
            padding: 0.95rem 1.1rem;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.9rem;
            color: #1e293b;
        }

        .styled-table tr:hover {
            background: #f8fafc;
        }

        .badge {
            display: inline-block;
            padding: 0.3rem 0.75rem;
            border-radius: 20px;
            font-size: 0.73rem;
            font-weight: 800;
            text-transform: uppercase;
            letter-spacing: 0.6px;
        }

        .badge-role-superadmin { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
        .badge-role-admin { 
            background: #e0f2fe; 
            color: #0369a1; 
            border: 1px solid #bae6fd; 
        }
        .badge-role-user { 
            background: #dcfce7; 
            color: #15803d; 
            border: 1px solid #bbf7d0; 
        }

        .badge-status-active { 
            background: #dcfce7; 
            color: #15803d; 
            border: 1px solid #bbf7d0; 
        }
        .badge-status-blocked { 
            background: #fee2e2; 
            color: #991b1b; 
            border: 1px solid #fecaca; 
        }
        .badge-status-inactive { 
            background: #f1f5f9; 
            color: #475569; 
            border: 1px solid #e2e8f0; 
        }

        .action-btn-group {
            display: flex;
            gap: 0.4rem;
            align-items: center;
        }

        .btn-action {
            padding: 0.45rem 0.85rem;
            border-radius: 8px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
        }

        .btn-view { background: #e0f2fe; color: #0369a1; border-color: #bae6fd; }
        .btn-view:hover { background: #bae6fd; color: #0284c7; }
        .btn-edit { background: #fef3c7; color: #92400e; border-color: #fde68a; }
        .btn-edit:hover { background: #fde68a; color: #78350f; }
        .btn-block { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .btn-block:hover { background: #fecaca; color: #7f1d1d; }
        .btn-unblock { background: #dcfce7; color: #15803d; border-color: #bbf7d0; }
        .btn-unblock:hover { background: #bbf7d0; color: #166534; }
        .btn-delete { background: #fee2e2; color: #991b1b; border-color: #fecaca; }
        .btn-delete:hover { background: #fecaca; color: #7f1d1d; }

        .action-select {
            background: #f8fafc;
            color: #0f172a;
            border: 1.5px solid #cbd5e1;
            border-radius: 8px;
            padding: 0.45rem 0.85rem;
            font-size: 0.84rem;
            font-weight: 700;
            cursor: pointer;
            outline: none;
            transition: all 0.25s ease;
            min-width: 110px;
        }

        .action-select:hover {
            border-color: #f59e0b;
            color: #b45309;
            background: #ffffff;
        }

        .action-select:focus {
            border-color: #f59e0b;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
        }

        .action-select option {
            background: #ffffff;
            color: #0f172a;
            padding: 0.4rem;
        }

        .action-select option.opt-delete {
            color: #ef4444;
        }

        /* General Modal Styles */
        .app-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.65);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
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
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1px solid rgba(245, 158, 11, 0.4);
            border-radius: 18px;
            box-shadow: 0 25px 65px rgba(0, 0, 0, 0.6), 0 0 35px rgba(245, 158, 11, 0.15);
            max-width: 540px;
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
            backdrop-filter: blur(12px);
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
            color: #f87171;
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
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.25) !important;
            background: rgba(239, 68, 68, 0.15) !important;
        }

        .privilege-box {
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 0.85rem;
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.6rem;
            margin-top: 0.4rem;
            transition: all 0.2s ease;
        }

        .privilege-box.locked {
            background: rgba(245, 158, 11, 0.15);
            border-color: rgba(245, 158, 11, 0.4);
            cursor: not-allowed;
        }

        .privilege-item {
            display: flex;
            align-items: center;
            gap: 0.45rem;
            font-size: 0.82rem;
            color: #f1f5f9;
            cursor: pointer;
            user-select: none;
            transition: all 0.2s ease;
        }

        .privilege-item input[type="checkbox"] {
            accent-color: #f59e0b;
            cursor: pointer;
        }

        .privilege-box.locked .privilege-item {
            cursor: not-allowed;
            pointer-events: none;
            color: #fbbf24;
        }

        .privilege-box.locked .privilege-item input[type="checkbox"] {
            cursor: not-allowed;
            pointer-events: none;
            opacity: 0.95;
        }

        .privilege-item.default-locked {
            cursor: not-allowed !important;
            color: #fbbf24;
            font-weight: 600;
        }

        .privilege-item.default-locked input[type="checkbox"] {
            cursor: not-allowed !important;
            pointer-events: none !important;
            opacity: 0.95;
            accent-color: #f59e0b;
        }

        .privilege-item.default-locked .default-badge {
            display: inline-block !important;
        }

        .details-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .details-cell {
            background: rgba(255, 255, 255, 0.10);
            border: 1px solid rgba(255, 255, 255, 0.2);
            backdrop-filter: blur(10px);
            border-radius: 8px;
            padding: 0.75rem;
        }

        .details-cell .label {
            font-size: 0.72rem;
            color: #cbd5e1;
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
                <h1>Account Management</h1>
                <p class="page-subtitle" style="color: #ffffff; font-size: 1.05rem; font-weight: 600; text-shadow: 0 2px 4px rgba(0, 0, 0, 0.85); margin-top: 0.25rem;">Unified management for Users, Admins, and Super Admins</p>
            </div>
            <button class="btn-create-account" onclick="openCreateAccountModal()">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                    <line x1="12" y1="5" x2="12" y2="19"></line>
                    <line x1="5" y1="12" x2="19" y2="12"></line>
                </svg>
                Create Account
            </button>
        </div>


        <!-- Filter Toolbar -->
        <div class="filter-toolbar">
            <div class="search-input-wrapper">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Search by ID Number, Username, Name, Email..." oninput="filterAccounts()">
            </div>
            <div class="filter-controls">
                <div class="filter-group">
                    <label for="idFilter">ID Number:</label>
                    <input type="text" id="idFilter" list="idList" placeholder="Filter ID..." oninput="filterAccounts()">
                    <datalist id="idList"></datalist>
                </div>
                <div class="filter-group">
                    <label for="roleFilter">Role:</label>
                    <select id="roleFilter" onchange="filterAccounts()">
                        <option value="all">All Roles</option>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>
                <div class="filter-group">
                    <label for="statusFilter">Status:</label>
                    <select id="statusFilter" onchange="filterAccounts()">
                        <option value="all">All Statuses</option>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                    </select>
                </div>
            </div>
        </div>

        <!-- Accounts Table -->
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
                <tbody id="accountsTableBody">
                    <tr><td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">Loading accounts...</td></tr>
                </tbody>
        </div>

        <!-- Accounts Pagination -->
        <div id="accountsPagination" class="pagination"></div>
        <div id="accountsPaginationInfo" class="pagination-info"></div>
    </div>

    <!-- Create Account Modal (4 fields only: ID Number, Username, Role, Default Password) -->
    <div class="app-modal-overlay" id="createAccountModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>Create New Account</h3>
                <button class="btn-modal-close" onclick="closeModal('createAccountModal')">&times;</button>
            </div>
            <form id="createAccountForm" onsubmit="handleCreateAccount(event)" novalidate>
                <div class="form-group-modal">
                    <label>ID Number <span style="color: #cbd5e1; font-size: 0.78rem; font-weight: 600;">(Auto-generated)</span></label>
                    <input type="text" id="createIdNo" class="form-control-modal" readonly placeholder="Generating..." style="background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: not-allowed; font-family: monospace;">
                    <small class="error-msg" id="createIdNo_error"></small>
                </div>

                <div class="form-group-modal">
                    <label>Username <span style="color: #ef4444;">*</span></label>
                    <input type="text" id="createUsername" class="form-control-modal" placeholder="Enter username" maxlength="30">
                    <small class="error-msg" id="createUsername_error"></small>
                </div>

                <div class="form-group-modal">
                    <label>Role <span style="color: #ef4444;">*</span></label>
                    <select id="createRole" class="form-control-modal" onchange="handleCreateRoleChange()">
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                    <small class="error-msg" id="createRole_error"></small>
                    <div id="createSuperadminNotice" style="display: none; color: #92400e; font-size: 0.76rem; margin-top: 6px; background: #fef3c7; border: 1px solid rgba(245, 158, 11, 0.45); border-radius: 6px; padding: 6px 10px; line-height: 1.4;">
                        ⚠️ <strong>Notice:</strong> Only 1 Super Admin can be Active at a time. Creating a new Super Admin will automatically set the new account to <strong>Active</strong>, and your current Super Admin account will be set to <strong>Inactive</strong> and logged out immediately so the new account can be used.
                    </div>
                </div>

                <div class="form-group-modal">
                    <label>Password <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="createPassword" class="form-control-modal" value="" placeholder="Enter password" style="padding-right: 2.5rem; font-family: monospace;" oninput="checkCreatePasswordStrength()" onblur="checkCreatePassword(true)">
                        <button type="button" class="password-toggle-btn" onclick="toggleCreatePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    <!-- Password Strength Bar (hidden initially when blank) -->
                    <div id="createPasswordStrengthWrap" style="margin-top: 8px; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.75rem; color: #cbd5e1; font-weight: 600;">Password Strength:</span>
                            <span id="createStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;">Weak</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                            <div id="createBar1" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar2" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar3" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar4" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                        </div>
                        <small style="color: #cbd5e1; font-size: 0.75rem; font-weight: 500; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
                    </div>
                    <small style="color: #cbd5e1; font-size: 0.78rem; font-weight: 500; display: block; margin-top: 4px;">User will be prompted to change password on first login.</small>
                    <small class="error-msg" id="createPassword_error"></small>
                </div>

                <!-- Privileges (shown ONLY for Admin / Super Admin) -->
                <div class="form-group-modal" id="createPrivilegeSection" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label style="margin-bottom: 0;">Assign Privileges</label>
                        <span class="superadmin-priv-note" style="display: none; font-size: 0.72rem; color: #fbbf24; font-weight: 600;">🔒 Full access granted (locked)</span>
                    </div>
                    <div class="privilege-box">
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="view_users"> View Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="create_users"> Create Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="edit_users"> Edit Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="delete_users"> Delete Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="block_users"> Block/Unblock Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="view_audit_logs"> View Audit Logs</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="approve_users"> Approve Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('createAccountModal')">Cancel</button>
                    <button type="submit" class="btn-create-account">Create Account</button>
                </div>
            </form>
        </div>
    </div>

    <!-- View Details Modal (NO Password Displayed) -->
    <div class="app-modal-overlay" id="viewDetailsModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>Account Details</h3>
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
                    <div class="value" id="viewRole">--</div>
                </div>
                <div class="details-cell">
                    <div class="label">Account Status</div>
                    <div class="value" id="viewStatus">--</div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1;" id="viewPrivilegesCell">
                    <div class="label">Assigned Privileges</div>
                    <div class="value" id="viewPrivileges" style="font-size: 0.88rem; color: #ffffff; font-weight: 500; line-height: 1.5;">None</div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1; display: none;" id="viewReasonCell">
                    <div class="label" id="viewReasonLabel" style="color: #f87171;">Reason</div>
                    <div class="value" id="viewReasonVal" style="color: #fca5a5; font-size: 0.85rem; white-space: pre-wrap; margin-top: 0.25rem;"></div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1; display: none;" id="viewFileCell">
                    <div class="label" style="color: #0284c7;">Attached Supporting File</div>
                    <div class="value" id="viewFileVal" style="margin-top: 0.25rem;"></div>
                </div>
            </div>
            <div style="display: flex; justify-content: flex-end;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('viewDetailsModal')">Close</button>
            </div>
        </div>
    </div>

    <!-- Edit Account Modal -->
    <div class="app-modal-overlay" id="editAccountModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3>Edit Account</h3>
                <button class="btn-modal-close" onclick="closeModal('editAccountModal')">&times;</button>
            </div>
            <form id="editAccountForm" onsubmit="promptSensitiveAction(event, 'update_user_info')" novalidate>
                <input type="hidden" id="editUserId">

                <div class="form-group-modal">
                    <label>ID Number <span style="color: #cbd5e1; font-size: 0.78rem; font-weight: 600;">(Cannot be modified)</span></label>
                    <input type="text" id="editIdNo" class="form-control-modal" readonly style="background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: not-allowed; font-family: monospace;" required>
                </div>

                <div class="form-group-modal">
                    <label>Full Name <span style="color: #ef4444;">*</span></label>
                    <div style="grid-template-columns: 1fr 1fr; gap: 0.5rem; display: grid;">
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
                    <label>Username <span style="color: #cbd5e1; font-size: 0.78rem; font-weight: 600;">(Cannot be modified)</span></label>
                    <input type="text" id="editUsername" class="form-control-modal" readonly style="background: rgba(255, 255, 255, 0.05); color: #94a3b8; cursor: not-allowed;" required>
                </div>

                <div class="form-group-modal">
                    <label>Reset Password <span style="color: #cbd5e1; font-size: 0.78rem; font-weight: 600;">(Leave blank to keep unchanged)</span></label>
                    <div style="position: relative;">
                        <input type="password" id="editPassword" class="form-control-modal" placeholder="Enter new password if resetting" autocomplete="new-password" style="padding-right: 2.5rem;" oninput="checkEditPasswordStrength()">
                        <button type="button" class="password-toggle-btn" onclick="toggleEditPasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #cbd5e1; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    
                    <!-- Password Strength Bar -->
                    <div id="editPasswordStrengthWrap" style="margin-top: 8px; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.75rem; color: #cbd5e1; font-weight: 600;">Password Strength:</span>
                            <span id="editStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #cbd5e1;">Weak</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                            <div id="editBar1" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar2" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar3" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar4" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                        </div>
                        <small style="color: #cbd5e1; font-size: 0.75rem; font-weight: 500; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
                    </div>
                    <small class="error-msg" id="editPassword_error"></small>
                </div>

                <div class="form-group-modal">
                    <label>Role</label>
                    <select id="editRole" class="form-control-modal" onchange="togglePrivilegeSection('edit'); updateSuperAdminStatusNote();" required>
                        <option value="user">User</option>
                        <option value="admin">Admin</option>
                        <option value="superadmin">Super Admin</option>
                    </select>
                </div>

                <div class="form-group-modal">
                    <label>Account Status</label>
                    <select id="editStatus" class="form-control-modal" onchange="updateSuperAdminStatusNote();" required>
                        <option value="active">Active</option>
                        <option value="inactive">Inactive</option>
                        <option value="blocked">Blocked</option>
                    </select>
                    <div id="superadminStatusNote" style="display: none; color: #92400e; font-size: 0.76rem; margin-top: 6px; background: #fef3c7; border: 1px solid rgba(245, 158, 11, 0.45); border-radius: 6px; padding: 6px 10px; line-height: 1.4;">
                        ⚠️ <strong>Notice:</strong> Only 1 Super Admin can be Active at a time. Setting this Super Admin to <strong>Active</strong> will automatically set your current account to <strong>Inactive</strong>, and you will be logged out upon saving.
                    </div>
                </div>

                <div class="form-group-modal" id="editPrivilegeSection" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label style="margin-bottom: 0;">Assigned Privileges</label>
                        <span class="superadmin-priv-note" style="display: none; font-size: 0.72rem; color: #b45309; font-weight: 600;">🔒 Full access granted (locked)</span>
                    </div>
                    <div class="privilege-box">
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="view_users"> View Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="create_users"> Create Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="edit_users"> Edit Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="delete_users"> Delete Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="block_users"> Block/Unblock Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="view_audit_logs"> View Audit Logs</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="approve_users"> Approve Users <span class="default-badge" style="display: none; font-size: 0.65rem; color: #fbbf24; font-weight: 800; background: rgba(245, 158, 11, 0.15); padding: 1px 5px; border-radius: 4px; border: 1px solid rgba(245, 158, 11, 0.4); margin-left: 4px;">DEFAULT</span></label>
                    </div>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('editAccountModal')">Cancel</button>
                    <button type="submit" class="btn-create-account">Proceed to Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Sensitive Action Confirmation & Password Verification Modal -->
    <div class="app-modal-overlay" id="verificationModal">
        <div class="app-modal-card" style="max-width: 440px; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;" id="verifIcon">&#128274;</div>
            <h3 id="verifTitle" style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.25rem;">Confirm Action</h3>
            <p id="verifPrompt" style="color: #cbd5e1; font-size: 0.88rem; margin-bottom: 1.25rem;">Please enter your current administrator password to confirm this action.</p>

            <form id="verificationForm" onsubmit="executeVerifiedAction(event)">
                <!-- Reason input (required for delete and block) -->
                <div class="form-group-modal" id="reasonGroup" style="display: none; text-align: left;">
                    <label>Reason <span style="color: #ef4444;">*</span></label>
                    <textarea id="actionReason" class="form-control-modal" placeholder="Enter reason for this action..." rows="2"></textarea>
                    
                    <label style="margin-top: 0.85rem; display: block; font-size: 0.82rem; color: #cbd5e1;">Attach Supporting File / Document <span style="color: #94a3b8; font-weight: normal; font-size: 0.75rem;">(Optional)</span></label>
                    <input type="file" id="actionFile" class="form-control-modal" style="padding: 0.45rem 0.6rem; font-size: 0.82rem; color: #94a3b8; background: rgba(255, 255, 255, 0.05);" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
                </div>

                <div class="form-group-modal" style="text-align: left;">
                    <label>Administrator Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="adminConfirmPassword" class="form-control-modal" placeholder="Enter your current password" required autocomplete="current-password">
                </div>

                <div style="display: flex; justify-content: center; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('verificationModal')">Cancel</button>
                    <button type="submit" class="btn-create-account" id="verifSubmitBtn" style="padding: 0.65rem 1.4rem;">Confirm &amp; Execute</button>
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
            <h3 style="color: #0f172a; margin-bottom: 0.5rem; font-size: 1.4rem; font-weight: 700;">Disembark <span style="color: #d97706;">Ship?</span></h3>
            <p style="color: #475569; font-size: 0.9rem; line-height: 1.5; margin-bottom: 1.5rem;">Logging out will set your account to Blocked status and activate the next eligible Super Admin account.</p>
            <div style="display: flex; justify-content: center; gap: 0.85rem;">
                <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('logoutModal')">Stay on Board</button>
                <button type="button" class="btn-create-account" style="padding: 0.65rem 1.6rem;" onclick="confirmLogout()">Log Out</button>
            </div>
        </div>
    </div>

    <script>
        const CURRENT_USER_IDNO = '<?php echo $_SESSION["user_id"]; ?>';
        const CURRENT_USER_USERNAME = '<?php echo addslashes($_SESSION["username"] ?? ""); ?>';
        let allAccounts = [];
        let pendingActionPayload = null;
        let currentAccountPage = 1;
        const accountsPerPage = 10;
        let currentFilteredAccounts = [];

        document.addEventListener('DOMContentLoaded', () => {
            loadAccounts();
            initCreateAccountListeners();
        });

        function initCreateAccountListeners() {
            const uInput = document.getElementById("createUsername");
            if (uInput) {
                uInput.addEventListener("input", () => checkCreateUsername(false));
                uInput.addEventListener("blur", () => {
                    if (checkCreateUsername(true)) {
                        checkCreateUsernameDuplicate();
                    }
                });
            }

            const pInput = document.getElementById("createPassword");
            if (pInput) {
                pInput.addEventListener("input", () => checkCreatePassword(false));
                pInput.addEventListener("blur", () => checkCreatePassword(true));
            }

            const idInput = document.getElementById("createIdNo");
            if (idInput) {
                idInput.addEventListener("input", () => checkCreateIdNo(false));
                idInput.addEventListener("blur", () => checkCreateIdNo(true));
            }

            const rInput = document.getElementById("createRole");
            if (rInput) {
                rInput.addEventListener("change", () => checkCreateRole());
            }
        }

        function loadAccounts() {
            fetch('../api/manage_users_api.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        allAccounts = data.users || [];
                        updateIdFilterOptions(allAccounts);
                        filterAccounts();
                    } else {
                        alert(data.error || 'Failed to load accounts');
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }

        function updateIdFilterOptions(accounts) {
            const dl = document.getElementById('idList');
            if (!dl) return;
            const ids = [...new Set(accounts.map(a => a.idno).filter(Boolean))].sort();
            dl.innerHTML = ids.map(id => `<option value="${id}">`).join('');
        }

        function renderAccounts(accounts, resetPage = false) {
            if (resetPage) currentAccountPage = 1;
            currentFilteredAccounts = accounts || [];

            const tbody = document.getElementById('accountsTableBody');
            if (!currentFilteredAccounts || currentFilteredAccounts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">No accounts found.</td></tr>';
                renderAccountsPagination(0, 0);
                return;
            }

            const totalPages = Math.ceil(currentFilteredAccounts.length / accountsPerPage) || 1;
            if (currentAccountPage > totalPages) currentAccountPage = totalPages;
            if (currentAccountPage < 1) currentAccountPage = 1;

            const start = (currentAccountPage - 1) * accountsPerPage;
            const end = Math.min(start + accountsPerPage, currentFilteredAccounts.length);
            const pagedAccounts = currentFilteredAccounts.slice(start, end);

            tbody.innerHTML = pagedAccounts.map(acc => {
                const isSelf = (acc.idno === CURRENT_USER_IDNO) || (CURRENT_USER_USERNAME && acc.username === CURRENT_USER_USERNAME);
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.trim() || acc.username;
                const roleBadge = `<span class="badge badge-role-${acc.role}">${(acc.role || '').toUpperCase()}</span>`;
                let statusBadge = `<span class="badge badge-status-${acc.status}">${(acc.status || '').toUpperCase()}</span>`;
                if (acc.status === 'pending_deletion') {
                    statusBadge = `<span class="badge" style="background: rgba(239, 68, 68, 0.2); color: #fca5a5; border: 1px solid rgba(239, 68, 68, 0.45); font-weight: 700;">PENDING DELETION</span>`;
                }

                let actionOptions = `
                    <option value="" disabled selected>Actions</option>
                    <option value="view">View</option>
                `;

                if (!isSelf) {
                    if (acc.status === 'pending_deletion') {
                        actionOptions += `
                            <option value="approve_deletion" style="color: #ef4444; font-weight: 700;">Approve Deletion</option>
                            <option value="reject_deletion" style="color: #10b981; font-weight: 700;">Reject Deletion</option>
                        `;
                    } else {
                        actionOptions += `<option value="edit">Edit</option>`;
                        if (acc.status === 'active') {
                            actionOptions += `<option value="block">Block</option>`;
                        } else if (acc.status === 'blocked') {
                            actionOptions += `<option value="unblock">Unblock</option>`;
                        }
                        actionOptions += `<option value="delete" class="opt-delete">Delete</option>`;
                    }
                }

                return `
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: #0284c7; letter-spacing: 0.5px;">${acc.idno || 'N/A'}</td>
                        <td style="font-weight: 600; color: #0f172a;">${escapeHtml(fullName)}</td>
                        <td style="color: #b45309; font-weight: 700;">${escapeHtml(acc.username)}</td>
                        <td>${roleBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <select class="action-select" onchange="handleAccountAction(this, '${acc.idno}')">
                                ${actionOptions}
                            </select>
                        </td>
                    </tr>
                `;
            }).join('');

            renderAccountsPagination(totalPages, currentFilteredAccounts.length, start, end);
        }

        function renderAccountsPagination(totalPages, totalItems, start = 0, end = 0) {
            const pagContainer = document.getElementById('accountsPagination');
            const infoContainer = document.getElementById('accountsPaginationInfo');
            if (!pagContainer || !infoContainer) return;

            if (totalPages <= 1) {
                pagContainer.innerHTML = '';
                infoContainer.innerHTML = totalItems > 0 ? `Showing ${totalItems} account${totalItems > 1 ? 's' : ''}` : '';
                return;
            }

            let html = '';
            // Prev button
            if (currentAccountPage > 1) {
                html += `<button type="button" class="btn-pag" onclick="changeAccountPage(${currentAccountPage - 1})">&laquo; Prev</button>`;
            } else {
                html += `<button type="button" class="btn-pag disabled" disabled>&laquo; Prev</button>`;
            }

            // Window numbers
            const windowSize = 2;
            let pages = [];
            for (let p = 1; p <= totalPages; p++) {
                if (p === 1 || p === totalPages || (p >= currentAccountPage - windowSize && p <= currentAccountPage + windowSize)) {
                    pages.push(p);
                }
            }

            let lastP = null;
            pages.forEach(p => {
                if (lastP !== null && p - lastP > 1) {
                    html += `<span class="dots">&hellip;</span>`;
                }
                if (p === currentAccountPage) {
                    html += `<button type="button" class="btn-pag active">${p}</button>`;
                } else {
                    html += `<button type="button" class="btn-pag" onclick="changeAccountPage(${p})">${p}</button>`;
                }
                lastP = p;
            });

            // Next button
            if (currentAccountPage < totalPages) {
                html += `<button type="button" class="btn-pag" onclick="changeAccountPage(${currentAccountPage + 1})">Next &raquo;</button>`;
            } else {
                html += `<button type="button" class="btn-pag disabled" disabled>Next &raquo;</button>`;
            }

            pagContainer.innerHTML = html;
            infoContainer.innerHTML = `Showing ${start + 1}&ndash;${end} of ${totalItems} accounts (Page ${currentAccountPage} of ${totalPages})`;
        }

        function changeAccountPage(page) {
            currentAccountPage = page;
            renderAccounts(currentFilteredAccounts, false);
            const table = document.querySelector('.table-card');
            if (table) table.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
        }

        function handleAccountAction(selectEl, idno) {
            const action = selectEl.value;
            selectEl.value = '';
            if (!action) return;

            const acc = allAccounts.find(a => (a.idno || a.id) == idno);
            const username = acc ? (acc.username || idno) : idno;
            const isSelf = acc && ((acc.idno === CURRENT_USER_IDNO) || (CURRENT_USER_USERNAME && acc.username === CURRENT_USER_USERNAME));

            if (action === 'view') {
                openViewDetails(idno);
            } else if (action === 'edit') {
                if (isSelf) {
                    alert("Dili nimo pwede i-edit ang imong kaugalingon nga account. Mka view ra ka.");
                    return;
                }
                openEditAccount(idno);
            } else if (action === 'block') {
                if (isSelf) return;
                confirmSensitiveAction('block', idno, 'Are you sure you want to block this account?', true);
            } else if (action === 'unblock') {
                confirmSensitiveAction('unblock', idno, 'Are you sure you want to unblock this account?');
            } else if (action === 'delete') {
                if (isSelf) return;
                confirmSensitiveAction('delete', idno, `Are you sure you want to permanently delete account (${escapeHtml(username)})? This will remove the record completely from the database.`, true);
            } else if (action === 'approve_deletion') {
                confirmSensitiveAction('approve_deletion', idno, `Are you sure you want to approve the deletion request for (${escapeHtml(username)})? This will permanently delete the account from the database.`);
            } else if (action === 'reject_deletion') {
                confirmSensitiveAction('reject_deletion', idno, `Are you sure you want to reject the deletion request for (${escapeHtml(username)})? The account will be restored to Active status.`, true);
            }
        }

        function filterAccounts() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const idFilter = (document.getElementById('idFilter')?.value || '').toLowerCase().trim();
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;

            const filtered = allAccounts.filter(acc => {
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.toLowerCase();
                const username = (acc.username || '').toLowerCase();
                const idno = (acc.idno || '').toLowerCase();
                const email = (acc.email || '').toLowerCase();

                const matchesSearch = !search || fullName.includes(search) || username.includes(search) || idno.includes(search) || email.includes(search);
                const matchesId = !idFilter || idno.includes(idFilter);
                const matchesRole = role === 'all' || acc.role === role;
                const matchesStatus = status === 'all' || acc.status === status;

                return matchesSearch && matchesId && matchesRole && matchesStatus;
            });

            renderAccounts(filtered, true);
        }

        function togglePrivilegeSection(context) {
            const roleSelect = document.getElementById(context === 'create' ? 'createRole' : 'editRole');
            const privSection = document.getElementById(context === 'create' ? 'createPrivilegeSection' : 'editPrivilegeSection');
            if (roleSelect && privSection) {
                const selector = context === 'create' ? 'input[name="create_priv"]' : 'input[name="edit_priv"]';
                const privBox = privSection.querySelector('.privilege-box');
                const lockNote = privSection.querySelector('.superadmin-priv-note');
                const checkboxes = privSection.querySelectorAll(selector);

                if (roleSelect.value === 'superadmin') {
                    privSection.style.display = 'block';
                    if (privBox) privBox.classList.add('locked');
                    if (lockNote) lockNote.style.display = 'inline-block';
                    // Auto-check all and lock/disable checkboxes so they cannot be clicked
                    checkboxes.forEach(cb => {
                        cb.checked = true;
                        cb.disabled = true;
                        const parentLabel = cb.closest('.privilege-item');
                        if (parentLabel) parentLabel.classList.remove('default-locked');
                    });
                } else if (roleSelect.value === 'admin') {
                    privSection.style.display = 'block';
                    if (privBox) privBox.classList.remove('locked');
                    if (lockNote) lockNote.style.display = 'none';

                    const defaultAdminPrivs = ['view_users', 'create_users', 'approve_users'];

                    if (context === 'create') {
                        checkboxes.forEach(cb => {
                            const isDefault = defaultAdminPrivs.includes(cb.value);
                            const parentLabel = cb.closest('.privilege-item');
                            if (isDefault) {
                                cb.checked = true;
                                cb.disabled = true; // Default privileges CANNOT be unchecked
                                if (parentLabel) parentLabel.classList.add('default-locked');
                            } else {
                                cb.checked = false;
                                cb.disabled = false;
                                if (parentLabel) parentLabel.classList.remove('default-locked');
                            }
                        });
                    } else if (context === 'edit') {
                        // In edit mode: restore account's saved privileges and lock default ones
                        const editUserId = document.getElementById('editUserId').value;
                        const acc = allAccounts.find(a => (a.idno || a.id) == editUserId);
                        let savedPrivs = [];
                        if (acc && acc.privileges) {
                            try { savedPrivs = JSON.parse(acc.privileges); } catch(e) {}
                        }
                        checkboxes.forEach(cb => {
                            const isDefault = defaultAdminPrivs.includes(cb.value);
                            const parentLabel = cb.closest('.privilege-item');
                            if (isDefault) {
                                cb.checked = true;
                                cb.disabled = true; // Default privileges CANNOT be unchecked
                                if (parentLabel) parentLabel.classList.add('default-locked');
                            } else {
                                cb.disabled = false;
                                if (parentLabel) parentLabel.classList.remove('default-locked');
                                cb.checked = Array.isArray(savedPrivs) && savedPrivs.includes(cb.value);
                            }
                        });
                    }
                } else {
                    privSection.style.display = 'none';
                    if (privBox) privBox.classList.remove('locked');
                    if (lockNote) lockNote.style.display = 'none';
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        cb.disabled = false;
                        const parentLabel = cb.closest('.privilege-item');
                        if (parentLabel) parentLabel.classList.remove('default-locked');
                    });
                }
            }
        }

        function updateSuperAdminStatusNote() {
            const roleVal = document.getElementById('editRole')?.value;
            const statusVal = document.getElementById('editStatus')?.value;
            const editId = document.getElementById('editUserId')?.value;
            const noteEl = document.getElementById('superadminStatusNote');
            if (!noteEl) return;

            if (roleVal === 'superadmin' && statusVal === 'active' && editId && editId !== CURRENT_USER_IDNO) {
                noteEl.style.display = 'block';
            } else {
                noteEl.style.display = 'none';
            }
        }

        function setCreateAccountError(inputId, msg) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = msg;
                errEl.style.display = "block";
            }
            if (inpEl) inpEl.classList.add("input-error-highlight");
        }

        function clearCreateAccountError(inputId) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = "";
                errEl.style.display = "none";
            }
            if (inpEl) inpEl.classList.remove("input-error-highlight");
        }

        function clearAllCreateAccountErrors() {
            ['createIdNo', 'createUsername', 'createRole', 'createPassword'].forEach(id => clearCreateAccountError(id));
        }

        function checkCreateIdNo(showEmpty) {
            const el = document.getElementById("createIdNo");
            if (!el) return true;
            const val = el.value.trim();
            if (!val || val === 'Generating...') {
                clearCreateAccountError("createIdNo");
                return true;
            }
            if (!/^\d{4}-\d{4}$/.test(val)) {
                if (showEmpty) setCreateAccountError("createIdNo", "ID Number must follow format YYYY-NNNN (e.g. 2026-0001).");
                return false;
            }
            clearCreateAccountError("createIdNo");
            return true;
        }

        function checkCreateUsername(showEmpty) {
            const el = document.getElementById("createUsername");
            if (!el) return true;
            const val = el.value.trim();
            if (!val) {
                if (showEmpty) {
                    setCreateAccountError("createUsername", "Username is required.");
                    return false;
                }
                clearCreateAccountError("createUsername");
                return false;
            }
            if (val.length < 3) {
                setCreateAccountError("createUsername", "Username must be at least 3 characters.");
                return false;
            }
            if (val.length > 30) {
                setCreateAccountError("createUsername", "Username cannot exceed 30 characters.");
                return false;
            }
            if (!/^[a-zA-Z0-9_]+$/.test(val)) {
                setCreateAccountError("createUsername", "Username can only contain letters, numbers, and underscores.");
                return false;
            }
            clearCreateAccountError("createUsername");
            return true;
        }

        function checkCreateUsernameDuplicate() {
            const el = document.getElementById("createUsername");
            if (!el) return Promise.resolve(true);
            const val = el.value.trim();
            if (!checkCreateUsername(false)) return Promise.resolve(false);
            if (!val) return Promise.resolve(false);
            return fetch(`../api/check_duplicate.php?field=username&value=${encodeURIComponent(val)}`)
                .then(r => r.json())
                .then(d => {
                    if (d.exists) {
                        setCreateAccountError("createUsername", "This username is already taken.");
                        return false;
                    }
                    clearCreateAccountError("createUsername");
                    return true;
                })
                .catch(() => true);
        }

        function checkCreateRole() {
            const el = document.getElementById("createRole");
            if (!el) return true;
            const val = el.value;
            if (!['user', 'admin', 'superadmin'].includes(val)) {
                setCreateAccountError("createRole", "Please select a valid role.");
                return false;
            }
            clearCreateAccountError("createRole");
            return true;
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
                clearCreateAccountError('createPassword');
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

            var hasRepeated = false;
            for (var i = 0; i < pwd.length - 2; i++) {
                if (pwd[i] === pwd[i+1] && pwd[i] === pwd[i+2]) {
                    hasRepeated = true;
                    break;
                }
            }

            if (pwd.length < 8) {
                setCreateAccountError('createPassword', 'Password must be at least 8 characters long.');
                return false;
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setCreateAccountError('createPassword', 'Must include uppercase, number, and special symbol.');
                return false;
            } else if (hasRepeated) {
                setCreateAccountError('createPassword', 'Password is too weak. Cannot contain 3 consecutive repeated characters.');
                return false;
            } else if (score < 4) {
                setCreateAccountError('createPassword', 'Password is too weak. Please choose a stronger password.');
                return false;
            } else {
                clearCreateAccountError('createPassword');
                return true;
            }
        }

        function checkCreatePassword(showEmpty) {
            const el = document.getElementById("createPassword");
            if (!el) return true;
            const val = el.value;
            if (!val) {
                if (showEmpty) {
                    setCreateAccountError("createPassword", "Password is required.");
                    return false;
                }
                clearCreateAccountError("createPassword");
                return false;
            }
            return checkCreatePasswordStrength();
        }

        function validateCreateAccountForm() {
            const vId = checkCreateIdNo(true);
            const vUn = checkCreateUsername(true);
            const vRl = checkCreateRole();
            const vPw = checkCreatePassword(true);

            if (!vId) { document.getElementById("createIdNo").focus(); return false; }
            if (!vUn) { document.getElementById("createUsername").focus(); return false; }
            if (!vRl) { document.getElementById("createRole").focus(); return false; }
            if (!vPw) { document.getElementById("createPassword").focus(); return false; }

            const userErr = document.getElementById("createUsername_error");
            if (userErr && userErr.style.display === "block" && userErr.textContent !== "") {
                document.getElementById("createUsername").focus();
                return false;
            }
            return true;
        }

        function handleCreateRoleChange() {
            togglePrivilegeSection('create');
            const role = document.getElementById('createRole').value;
            const saNotice = document.getElementById('createSuperadminNotice');
            if (saNotice) {
                saNotice.style.display = (role === 'superadmin') ? 'block' : 'none';
            }
            const idInput = document.getElementById('createIdNo');
            if (idInput) {
                idInput.value = 'Generating...';
            }
            fetch(`../api/manage_users_api.php?action=get_next_idno&role=${encodeURIComponent(role)}`)
                .then(r => r.json())
                .then(d => {
                    if (d.success && idInput) {
                        idInput.value = d.idno;
                        clearCreateAccountError("createIdNo");
                    }
                })
                .catch(() => {
                    if (idInput) idInput.value = '';
                });
        }

        function openCreateAccountModal() {
            document.getElementById('createAccountForm').reset();
            clearAllCreateAccountErrors();
            const saNotice = document.getElementById('createSuperadminNotice');
            if (saNotice) saNotice.style.display = 'none';
            const pwdInput = document.getElementById('createPassword');
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.type = 'password';
            }
            checkCreatePasswordStrength();
            document.getElementById('createRole').value = 'user';
            document.querySelectorAll('input[name="create_priv"]').forEach(cb => cb.checked = false);
            togglePrivilegeSection('create');
            
            const idInput = document.getElementById('createIdNo');
            if (idInput) {
                idInput.value = 'Generating...';
            }
            // Fetch next ID
            fetch('../api/manage_users_api.php?action=get_next_idno&role=user')
                .then(r => r.json())
                .then(d => {
                    if (d.success && idInput) {
                        idInput.value = d.idno;
                        clearCreateAccountError("createIdNo");
                    }
                })
                .catch(() => {
                    if (idInput) idInput.value = '';
                });
            openModal('createAccountModal');
        }

        function handleCreateAccount(e) {
            e.preventDefault();
            if (!validateCreateAccountForm()) {
                return;
            }

            const roleVal = document.getElementById('createRole').value;
            let privs = [];
            if (roleVal === 'superadmin') {
                privs = ['view_users', 'create_users', 'edit_users', 'delete_users', 'block_users', 'view_audit_logs', 'approve_users'];
            } else if (roleVal === 'admin') {
                const defaultAdminPrivs = ['view_users', 'create_users', 'approve_users'];
                const privCheckboxes = document.querySelectorAll('input[name="create_priv"]:checked');
                const checkedPrivs = Array.from(privCheckboxes).map(cb => cb.value);
                privs = Array.from(new Set([...defaultAdminPrivs, ...checkedPrivs]));
            }
            const usernameVal = document.getElementById('createUsername').value.trim();
            const pwdInput = document.getElementById('createPassword');
            const passwordVal = pwdInput ? pwdInput.value : '';

            const payload = {
                action: 'create_user',
                idno: document.getElementById('createIdNo').value.trim(),
                username: usernameVal,
                password: passwordVal,
                role: roleVal,
                privileges: privs
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
                    closeModal('createAccountModal');
                    if (res.self_deactivated && res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        loadAccounts();
                    }
                } else {
                    if (res.error && res.error.toLowerCase().includes('username')) {
                        setCreateAccountError('createUsername', res.error);
                        document.getElementById('createUsername').focus();
                    } else if (res.error && res.error.toLowerCase().includes('id')) {
                        setCreateAccountError('createIdNo', res.error);
                        document.getElementById('createIdNo').focus();
                    } else {
                        alert(res.error || 'Failed to create account');
                    }
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        function openViewDetails(userId) {
            const acc = allAccounts.find(a => (a.idno || a.id) == userId);
            if (!acc) return;

            document.getElementById('viewIdNo').textContent = acc.idno || 'N/A';
            document.getElementById('viewUsername').textContent = acc.username;
            document.getElementById('viewFullName').textContent = `${acc.firstname || ''} ${acc.lastname || ''}`.trim() || 'N/A';
            document.getElementById('viewRole').textContent = acc.role.toUpperCase();
            const isPendingDel = acc.status === 'pending_deletion';
            const statusEl = document.getElementById('viewStatus');
            statusEl.textContent = isPendingDel ? 'PENDING DELETION (Requested by Admin)' : acc.status.toUpperCase();
            statusEl.style.color = isPendingDel ? '#fca5a5' : '#ffffff';

            let privText = 'None (Standard User)';
            if (acc.privileges) {
                try {
                    const parsed = JSON.parse(acc.privileges);
                    if (Array.isArray(parsed) && parsed.length > 0) {
                        const labels = {
                            'view_users': 'View Users',
                            'create_users': 'Create Users',
                            'edit_users': 'Edit Users',
                            'delete_users': 'Delete Users',
                            'block_users': 'Block/Unblock Users',
                            'view_audit_logs': 'View Audit Logs',
                            'approve_users': 'Approve Users'
                        };
                        privText = parsed.map(p => labels[p] || p).join(', ');
                    }
                } catch(e) {}
            }
            document.getElementById('viewPrivileges').textContent = privText;

            const reasonCell = document.getElementById('viewReasonCell');
            const fileCell = document.getElementById('viewFileCell');
            const activeReason = acc.delete_reason || acc.block_reason;
            const activeFile = acc.delete_file || acc.block_file;

            if (isPendingDel) {
                reasonCell.style.display = 'block';
                const reqAdmin = acc.delete_requested_by ? ` (Requested by Admin: ${escapeHtml(acc.delete_requested_by)})` : '';
                document.getElementById('viewReasonLabel').textContent = `⚠️ Deletion Request Reason${reqAdmin}`;
                document.getElementById('viewReasonLabel').style.color = '#f87171';
                document.getElementById('viewReasonVal').textContent = acc.delete_reason || 'Deletion requested by Admin.';
            } else if (activeReason) {
                reasonCell.style.display = 'block';
                document.getElementById('viewReasonLabel').textContent = acc.block_reason ? 'Block Reason' : 'Deactivation / Delete Reason';
                document.getElementById('viewReasonLabel').style.color = '#f87171';
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

        function openEditAccount(userId) {
            const acc = allAccounts.find(a => (a.idno || a.id) == userId);
            if (!acc) return;

            const isSelf = (acc.idno === CURRENT_USER_IDNO) || (CURRENT_USER_USERNAME && acc.username === CURRENT_USER_USERNAME);
            if (isSelf) {
                alert("Dili nimo pwede i-edit ang imong kaugalingon nga account. Mka view ra ka.");
                return;
            }

            clearAllEditAccountErrors();
            document.getElementById('editUserId').value = acc.idno || acc.id;
            document.getElementById('editIdNo').value = acc.idno || '';
            document.getElementById('editFirstName').value = acc.firstname || '';
            document.getElementById('editLastName').value = acc.lastname || '';
            document.getElementById('editUsername').value = acc.username || '';
            
            const pwdInput = document.getElementById('editPassword');
            pwdInput.value = '';
            pwdInput.type = 'password';
            checkEditPasswordStrength();

            document.getElementById('editRole').value = acc.role;
            document.getElementById('editStatus').value = acc.status;

            // Privileges
            const privCheckboxes = document.querySelectorAll('input[name="edit_priv"]');
            privCheckboxes.forEach(cb => cb.checked = false);
            let parsedPrivs = [];
            if (acc.privileges) {
                try {
                    const parsed = JSON.parse(acc.privileges);
                    if (Array.isArray(parsed)) parsedPrivs = parsed;
                } catch(e) {}
            }
            if (acc.role === 'admin') {
                const defaultAdminPrivs = ['view_users', 'create_users', 'approve_users'];
                parsedPrivs = Array.from(new Set([...defaultAdminPrivs, ...parsedPrivs]));
            }

            privCheckboxes.forEach(cb => {
                if (parsedPrivs.includes(cb.value)) cb.checked = true;
            });

            if (acc.role === 'superadmin') {
                privCheckboxes.forEach(cb => cb.checked = true);
            }

            togglePrivilegeSection('edit');
            updateSuperAdminStatusNote();
            openModal('editAccountModal');
        }

        // Edit Account Helpers & Validations
        function setEditAccountError(inputId, msg) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = msg;
                errEl.style.display = "block";
            }
            if (inpEl) inpEl.classList.add("input-error-highlight");
        }

        function clearEditAccountError(inputId) {
            const errEl = document.getElementById(inputId + "_error");
            const inpEl = document.getElementById(inputId);
            if (errEl) {
                errEl.textContent = "";
                errEl.style.display = "none";
            }
            if (inpEl) inpEl.classList.remove("input-error-highlight");
        }

        function clearAllEditAccountErrors() {
            ['editFirstName', 'editLastName', 'editPassword'].forEach(id => clearEditAccountError(id));
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
                    setEditAccountError('editFirstName', 'Firstname fields must be filled in.');
                    return false;
                }
                clearEditAccountError('editFirstName');
                return false;
            }

            const err = scanNameField(val, "Firstname");
            if (err) {
                setEditAccountError('editFirstName', err);
                return false;
            }

            clearEditAccountError('editFirstName');
            return true;
        }

        function checkEditLastName(showEmpty) {
            const el = document.getElementById('editLastName');
            if (!el) return true;
            const val = el.value;

            if (val === "") {
                if (showEmpty) {
                    setEditAccountError('editLastName', 'Lastname fields must be filled in.');
                    return false;
                }
                clearEditAccountError('editLastName');
                return false;
            }

            const err = scanNameField(val, "Lastname");
            if (err) {
                setEditAccountError('editLastName', err);
                return false;
            }

            clearEditAccountError('editLastName');
            return true;
        }

        function checkEditPasswordStrength() {
            const pwdInput = document.getElementById('editPassword');
            const wrap = document.getElementById('editPasswordStrengthWrap');
            const label = document.getElementById('editStrengthLabel');
            const bars = [
                document.getElementById('editBar1'),
                document.getElementById('editBar2'),
                document.getElementById('editBar3'),
                document.getElementById('editBar4')
            ];

            if (!pwdInput) return;
            const pwd = pwdInput.value;

            if (!pwd || pwd.length === 0) {
                if (wrap) wrap.style.display = 'none';
                clearEditAccountError('editPassword');
                bars.forEach(b => {
                    if (b) b.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                });
                if (label) label.textContent = '';
                return;
            }

            if (wrap) wrap.style.display = 'block';

            const matchedCases = ["[A-Z]", "[0-9]", "[!@#$%^&*()_+\\-=\\[\\]{};':\"\\\\|,.<>\\/?]", ".{8,}"];
            let score = 0;
            matchedCases.forEach(pattern => {
                if (new RegExp(pattern).test(pwd)) score++;
            });

            let color = "#ef4444";
            let levelText = "Weak";
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

            var hasRepeated = false;
            for (var i = 0; i < pwd.length - 2; i++) {
                if (pwd[i] === pwd[i+1] && pwd[i] === pwd[i+2]) {
                    hasRepeated = true;
                    break;
                }
            }

            if (pwd.length < 8) {
                setEditAccountError('editPassword', 'Password must be at least 8 characters long.');
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setEditAccountError('editPassword', 'Must include uppercase, number, and special symbol.');
            } else if (hasRepeated) {
                setEditAccountError('editPassword', 'Password is too weak. Cannot contain 3 consecutive repeated characters.');
            } else if (score < 4) {
                setEditAccountError('editPassword', 'Password is too weak. Please choose a stronger password.');
            } else {
                clearEditAccountError('editPassword');
            }
        }

        function toggleEditPasswordVisibility(btn) {
            const input = document.getElementById('editPassword');
            if (!input) return;
            const isPassword = input.type === 'password';
            input.type = isPassword ? 'text' : 'password';
            btn.innerHTML = isPassword 
                ? '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#f59e0b" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>'
                : '<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>';
        }

        function validateEditAccountForm() {
            const vFn = checkEditFirstName(true);
            const vLn = checkEditLastName(true);
            const pwdVal = document.getElementById('editPassword').value;

            let vPw = true;
            if (pwdVal && pwdVal.length > 0) {
                if (pwdVal.length < 8) {
                    setEditAccountError('editPassword', 'Password must be at least 8 characters long.');
                    vPw = false;
                } else if (!/[A-Z]/.test(pwdVal) || !/[0-9]/.test(pwdVal) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwdVal)) {
                    setEditAccountError('editPassword', 'Must include uppercase, number, and special symbol.');
                    vPw = false;
                } else {
                    clearEditAccountError('editPassword');
                }
            } else {
                clearEditAccountError('editPassword');
            }

            if (!vFn) { document.getElementById('editFirstName').focus(); return false; }
            if (!vLn) { document.getElementById('editLastName').focus(); return false; }
            if (!vPw) { document.getElementById('editPassword').focus(); return false; }

            return true;
        }

        function promptSensitiveAction(e, actionType) {
            e.preventDefault();
            if (actionType === 'update_user_info') {
                if (!validateEditAccountForm()) {
                    return;
                }

                const roleVal = document.getElementById('editRole').value;
                let privs = [];
                if (roleVal === 'superadmin') {
                    privs = ['view_users', 'create_users', 'edit_users', 'delete_users', 'block_users', 'view_audit_logs', 'approve_users'];
                } else if (roleVal === 'admin') {
                    const defaultAdminPrivs = ['view_users', 'create_users', 'approve_users'];
                    const privCheckboxes = document.querySelectorAll('input[name="edit_priv"]:checked');
                    const checkedPrivs = Array.from(privCheckboxes).map(cb => cb.value);
                    privs = Array.from(new Set([...defaultAdminPrivs, ...checkedPrivs]));
                }

                pendingActionPayload = {
                    action: 'update_user_info',
                    user_id: document.getElementById('editUserId').value,
                    idno: document.getElementById('editIdNo').value.trim(),
                    firstname: document.getElementById('editFirstName').value.trim(),
                    lastname: document.getElementById('editLastName').value.trim(),
                    username: document.getElementById('editUsername').value.trim(),
                    password: document.getElementById('editPassword').value,
                    role: roleVal,
                    status: document.getElementById('editStatus').value,
                    privileges: privs
                };

                closeModal('editAccountModal');

                let confirmPrompt = 'Are you sure you want to edit this account?';
                const editTargetId = document.getElementById('editUserId').value;
                const newStatus = document.getElementById('editStatus').value;
                if (roleVal === 'superadmin' && newStatus === 'active' && editTargetId && editTargetId !== CURRENT_USER_IDNO) {
                    confirmPrompt = '⚠️ You are activating another Super Admin. Only 1 Super Admin can be active at a time. Your current Super Admin account will immediately become INACTIVE and you will be logged out. Are you sure you want to proceed?';
                }

                setupVerificationModal(confirmPrompt, false);
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
            .then(async r => {
                const text = await r.text();
                try {
                    return JSON.parse(text);
                } catch (e) {
                    const jsonStart = text.indexOf('{');
                    if (jsonStart !== -1) {
                        try {
                            return JSON.parse(text.substring(jsonStart));
                        } catch (e2) {}
                    }
                    throw new Error(text || 'Invalid server response');
                }
            })
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    closeModal('verificationModal');
                    if (res.self_deactivated && res.redirect) {
                        window.location.href = res.redirect;
                    } else {
                        loadAccounts();
                    }
                } else {
                    if (res.error === 'Unauthorized' || res.error === 'Account inactive') {
                        alert(res.message || 'Your session has expired. Redirecting to login page...');
                        window.location.href = res.redirect || '../login.php';
                    } else {
                        alert('Action Failed: ' + (res.error || 'Unknown error'));
                    }
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