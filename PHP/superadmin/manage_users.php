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
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .filter-toolbar {
            display: flex;
            flex-wrap: wrap;
            gap: 1rem;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.5rem;
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            padding: 1rem 1.25rem;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
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
            stroke: #cbd5e1;
            pointer-events: none;
        }

        .search-input-wrapper input {
            width: 100%;
            padding: 0.8rem 1rem 0.8rem 2.8rem;
            border-radius: 10px;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            color: #ffffff;
            font-size: 0.92rem;
            font-weight: 600;
            outline: none;
            transition: all 0.3s ease;
        }

        .search-input-wrapper input:focus {
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
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
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .filter-group select {
            padding: 0.7rem 1rem;
            border-radius: 10px;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            color: #ffffff;
            font-size: 0.86rem;
            font-weight: 600;
            cursor: pointer;
            outline: none;
            transition: all 0.3s ease;
        }

        .filter-group select:focus {
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
        }

        .filter-group select option {
            background: #0f172a;
            color: #ffffff;
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

        /* Table Styles - One Piece Theme */
        .table-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 16px;
            padding: 1.5rem;
            overflow-x: auto;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
        }

        .styled-table {
            width: 100%;
            border-collapse: collapse;
            text-align: left;
        }

        .styled-table th {
            background: rgba(245, 158, 11, 0.2);
            padding: 1rem 1.1rem;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            color: #fbbf24;
            font-weight: 800;
            border-bottom: 2px solid rgba(245, 158, 11, 0.4);
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
        }

        .styled-table td {
            padding: 0.95rem 1.1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.12);
            font-size: 0.9rem;
            color: #ffffff;
        }

        .styled-table tr:hover {
            background: rgba(255, 255, 255, 0.08);
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
            background: rgba(239, 68, 68, 0.2); 
            color: #fca5a5; 
            border: 1px solid rgba(239, 68, 68, 0.4); 
            box-shadow: 0 0 8px rgba(239, 68, 68, 0.2);
        }
        .badge-role-admin { 
            background: rgba(14, 165, 233, 0.2); 
            color: #7dd3fc; 
            border: 1px solid rgba(14, 165, 233, 0.4); 
            box-shadow: 0 0 8px rgba(14, 165, 233, 0.2);
        }
        .badge-role-user { 
            background: rgba(16, 185, 129, 0.2); 
            color: #86efac; 
            border: 1px solid rgba(16, 185, 129, 0.4); 
            box-shadow: 0 0 8px rgba(16, 185, 129, 0.2);
        }

        .badge-status-active { 
            background: rgba(34, 197, 94, 0.2); 
            color: #86efac; 
            border: 1px solid rgba(34, 197, 94, 0.4); 
            box-shadow: 0 0 8px rgba(34, 197, 94, 0.2);
        }
        .badge-status-blocked { 
            background: rgba(239, 68, 68, 0.2); 
            color: #fca5a5; 
            border: 1px solid rgba(239, 68, 68, 0.4); 
        }
        .badge-status-inactive { 
            background: rgba(255, 255, 255, 0.12); 
            color: #cbd5e1; 
            border: 1px solid rgba(255, 255, 255, 0.25); 
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

        .btn-view { background: rgba(14, 165, 233, 0.2); color: #7dd3fc; border-color: rgba(14, 165, 233, 0.4); }
        .btn-view:hover { background: rgba(14, 165, 233, 0.35); color: #ffffff; }
        .btn-edit { background: rgba(245, 158, 11, 0.2); color: #fbbf24; border-color: rgba(245, 158, 11, 0.4); }
        .btn-edit:hover { background: rgba(245, 158, 11, 0.35); color: #ffffff; }
        .btn-block { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border-color: rgba(239, 68, 68, 0.4); }
        .btn-block:hover { background: rgba(239, 68, 68, 0.35); color: #ffffff; }
        .btn-unblock { background: rgba(34, 197, 94, 0.2); color: #86efac; border-color: rgba(34, 197, 94, 0.4); }
        .btn-unblock:hover { background: rgba(34, 197, 94, 0.35); color: #ffffff; }
        .btn-delete { background: rgba(239, 68, 68, 0.2); color: #fca5a5; border-color: rgba(239, 68, 68, 0.4); }
        .btn-delete:hover { background: rgba(239, 68, 68, 0.35); color: #ffffff; }

        .action-select {
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(10px);
            color: #ffffff;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
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
            border-color: #fbbf24;
            box-shadow: 0 0 12px rgba(245, 158, 11, 0.35);
            color: #fbbf24;
            background: rgba(255, 255, 255, 0.25);
        }

        .action-select:focus {
            border-color: #fbbf24;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.25);
        }

        .action-select option {
            background: #0f172a;
            color: #ffffff;
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
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.10) 100%);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 18px;
            box-shadow: 0 25px 65px rgba(0, 0, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.2);
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
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
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
            font-size: 0.78rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            color: #f1f5f9;
            margin-bottom: 0.35rem;
            text-shadow: 0 1px 2px rgba(0, 0, 0, 0.4);
        }

        .form-control-modal {
            width: 100%;
            padding: 0.75rem 0.9rem;
            border-radius: 8px;
            border: 1.5px solid rgba(255, 255, 255, 0.35);
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 600;
            outline: none;
            box-sizing: border-box;
            transition: all 0.25s ease;
        }

        .form-control-modal:focus {
            border-color: #fbbf24;
            background: rgba(255, 255, 255, 0.25);
            box-shadow: 0 0 15px rgba(251, 191, 36, 0.4);
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
                <h1>Account Management</h1>
                <p style="color: #64748b; font-size: 0.95rem;">Unified management for Users, Admins, and Super Admins</p>
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
            </table>
        </div>
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
                    <label>ID Number <span style="color: #64748b; font-size: 0.75rem; font-weight: normal;">(Auto-generated)</span></label>
                    <input type="text" id="createIdNo" class="form-control-modal" readonly placeholder="Generating..." style="background: #f1f5f9; color: #475569; cursor: not-allowed; font-family: monospace;">
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
                </div>

                <div class="form-group-modal">
                    <label>Password <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="createPassword" class="form-control-modal" value="" placeholder="Enter password" style="padding-right: 2.5rem; font-family: monospace;" oninput="checkCreatePasswordStrength()" onblur="checkCreatePassword(true)">
                        <button type="button" class="password-toggle-btn" onclick="toggleCreatePasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>

                    <!-- Password Strength Bar (hidden initially when blank) -->
                    <div id="createPasswordStrengthWrap" style="margin-top: 8px; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.73rem; color: #64748b;">Password Strength:</span>
                            <span id="createStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Weak</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                            <div id="createBar1" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar2" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar3" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="createBar4" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                        </div>
                        <small style="color: #64748b; font-size: 0.72rem; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
                    </div>
                    <small style="color: #64748b; font-size: 0.75rem; display: block; margin-top: 4px;">User will be prompted to change password on first login.</small>
                    <small class="error-msg" id="createPassword_error"></small>
                </div>

                <!-- Privileges (shown ONLY for Admin / Super Admin) -->
                <div class="form-group-modal" id="createPrivilegeSection" style="display: none;">
                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                        <label style="margin-bottom: 0;">Assign Privileges</label>
                        <span class="superadmin-priv-note" style="display: none; font-size: 0.72rem; color: #fbbf24; font-weight: 600;">🔒 Full access granted (locked)</span>
                    </div>
                    <div class="privilege-box">
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="view_users"> View Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="create_users"> Create Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="edit_users"> Edit Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="delete_users"> Delete Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="block_users"> Block/Unblock Users</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="view_audit_logs"> View Audit Logs</label>
                        <label class="privilege-item"><input type="checkbox" name="create_priv" value="approve_users"> Approve Users</label>
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
                    <div class="value" id="viewPrivileges" style="font-size: 0.85rem; color: #334155;">None</div>
                </div>
                <div class="details-cell" style="grid-column: 1 / -1; display: none;" id="viewReasonCell">
                    <div class="label" id="viewReasonLabel" style="color: #dc2626;">Reason</div>
                    <div class="value" id="viewReasonVal" style="color: #991b1b; font-size: 0.85rem; white-space: pre-wrap; margin-top: 0.25rem;"></div>
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
                    <label>ID Number <span style="color: #64748b; font-size: 0.75rem; font-weight: normal;">(Cannot be modified)</span></label>
                    <input type="text" id="editIdNo" class="form-control-modal" readonly style="background: #f1f5f9; color: #475569; cursor: not-allowed; font-family: monospace;" required>
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
                    <label>Username <span style="color: #64748b; font-size: 0.75rem; font-weight: normal;">(Cannot be modified)</span></label>
                    <input type="text" id="editUsername" class="form-control-modal" readonly style="background: #f1f5f9; color: #475569; cursor: not-allowed;" required>
                </div>

                <div class="form-group-modal">
                    <label>Reset Password <span style="color: #64748b; font-size: 0.75rem; font-weight: normal;">(Leave blank to keep unchanged)</span></label>
                    <div style="position: relative;">
                        <input type="password" id="editPassword" class="form-control-modal" placeholder="Enter new password if resetting" style="padding-right: 2.5rem;" oninput="checkEditPasswordStrength()">
                        <button type="button" class="password-toggle-btn" onclick="toggleEditPasswordVisibility(this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #64748b; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                    
                    <!-- Password Strength Bar -->
                    <div id="editPasswordStrengthWrap" style="margin-top: 8px; display: none;">
                        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                            <span style="font-size: 0.73rem; color: #64748b;">Password Strength:</span>
                            <span id="editStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #64748b;">Weak</span>
                        </div>
                        <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                            <div id="editBar1" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar2" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar3" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                            <div id="editBar4" style="height: 100%; background: #e2e8f0; border-radius: 3px; transition: background 0.3s ease;"></div>
                        </div>
                        <small style="color: #64748b; font-size: 0.72rem; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
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
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="view_users"> View Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="create_users"> Create Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="edit_users"> Edit Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="delete_users"> Delete Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="block_users"> Block/Unblock Users</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="view_audit_logs"> View Audit Logs</label>
                        <label class="privilege-item"><input type="checkbox" name="edit_priv" value="approve_users"> Approve Users</label>
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
            <h3 id="verifTitle" style="color: #0f172a; margin-bottom: 0.5rem; font-size: 1.25rem;">Confirm Action</h3>
            <p id="verifPrompt" style="color: #475569; font-size: 0.88rem; margin-bottom: 1.25rem;">Please enter your current administrator password to confirm this action.</p>

            <form id="verificationForm" onsubmit="executeVerifiedAction(event)">
                <!-- Reason input (required for delete and block) -->
                <div class="form-group-modal" id="reasonGroup" style="display: none; text-align: left;">
                    <label>Reason <span style="color: #ef4444;">*</span></label>
                    <textarea id="actionReason" class="form-control-modal" placeholder="Enter reason for this action..." rows="2"></textarea>
                    
                    <label style="margin-top: 0.85rem; display: block; font-size: 0.82rem; color: #334155;">Attach Supporting File / Document <span style="color: #64748b; font-weight: normal; font-size: 0.75rem;">(Optional)</span></label>
                    <input type="file" id="actionFile" class="form-control-modal" style="padding: 0.45rem 0.6rem; font-size: 0.82rem; color: #334155; background: #ffffff; border: 1px solid #cbd5e1;" accept=".jpg,.jpeg,.png,.webp,.pdf,.doc,.docx,.txt">
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
        let allAccounts = [];
        let pendingActionPayload = null;

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
                        renderAccounts(allAccounts);
                    } else {
                        alert(data.error || 'Failed to load accounts');
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }

        function renderAccounts(accounts) {
            const tbody = document.getElementById('accountsTableBody');
            if (!accounts || accounts.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #64748b; padding: 2rem;">No accounts found.</td></tr>';
                return;
            }

            tbody.innerHTML = accounts.map(acc => {
                const isSelf = acc.idno === CURRENT_USER_IDNO;
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.trim() || acc.username;
                const roleBadge = `<span class="badge badge-role-${acc.role}">${(acc.role || '').toUpperCase()}</span>`;
                const statusBadge = `<span class="badge badge-status-${acc.status}">${(acc.status || '').toUpperCase()}</span>`;

                let actionOptions = `
                    <option value="" disabled selected>Actions</option>
                    <option value="view">View</option>
                    <option value="edit">Edit</option>
                `;

                if (!isSelf) {
                    if (acc.status === 'active') {
                        actionOptions += `<option value="block">Block</option>`;
                    } else if (acc.status === 'blocked') {
                        actionOptions += `<option value="unblock">Unblock</option>`;
                    }
                    actionOptions += `<option value="delete" class="opt-delete">Delete</option>`;
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
        }

        function handleAccountAction(selectEl, idno) {
            const action = selectEl.value;
            selectEl.value = '';
            if (!action) return;

            const acc = allAccounts.find(a => (a.idno || a.id) == idno);
            const username = acc ? (acc.username || idno) : idno;

            if (action === 'view') {
                openViewDetails(idno);
            } else if (action === 'edit') {
                openEditAccount(idno);
            } else if (action === 'block') {
                confirmSensitiveAction('block', idno, 'Are you sure you want to block this account?', true);
            } else if (action === 'unblock') {
                confirmSensitiveAction('unblock', idno, 'Are you sure you want to unblock this account?');
            } else if (action === 'delete') {
                confirmSensitiveAction('delete', idno, `Are you sure you want to permanently delete account (${escapeHtml(username)})? This will remove the record completely from the database.`, true);
            }
        }

        function filterAccounts() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const role = document.getElementById('roleFilter').value;
            const status = document.getElementById('statusFilter').value;

            const filtered = allAccounts.filter(acc => {
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.toLowerCase();
                const username = (acc.username || '').toLowerCase();
                const idno = (acc.idno || '').toLowerCase();
                const email = (acc.email || '').toLowerCase();

                const matchesSearch = !search || fullName.includes(search) || username.includes(search) || idno.includes(search) || email.includes(search);
                const matchesRole = role === 'all' || acc.role === role;
                const matchesStatus = status === 'all' || acc.status === status;

                return matchesSearch && matchesRole && matchesStatus;
            });

            renderAccounts(filtered);
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
                    });
                } else if (roleSelect.value === 'admin') {
                    privSection.style.display = 'block';
                    if (privBox) privBox.classList.remove('locked');
                    if (lockNote) lockNote.style.display = 'none';
                    // Checkboxes are editable for Admin
                    checkboxes.forEach(cb => {
                        cb.disabled = false;
                    });

                    if (context === 'create') {
                        checkboxes.forEach(cb => cb.checked = false);
                    } else if (context === 'edit') {
                        // In edit mode: restore account's saved privileges or keep unchecked
                        const editUserId = document.getElementById('editUserId').value;
                        const acc = allAccounts.find(a => (a.idno || a.id) == editUserId);
                        let savedPrivs = [];
                        if (acc && acc.privileges) {
                            try { savedPrivs = JSON.parse(acc.privileges); } catch(e) {}
                        }
                        checkboxes.forEach(cb => {
                            cb.checked = Array.isArray(savedPrivs) && savedPrivs.includes(cb.value);
                        });
                    }
                } else {
                    privSection.style.display = 'none';
                    if (privBox) privBox.classList.remove('locked');
                    if (lockNote) lockNote.style.display = 'none';
                    checkboxes.forEach(cb => {
                        cb.checked = false;
                        cb.disabled = false;
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

            if (pwd.length < 8) {
                setCreateAccountError('createPassword', 'Password must be at least 8 characters long.');
                return false;
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setCreateAccountError('createPassword', 'Must include uppercase, number, and special symbol.');
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
                const privCheckboxes = document.querySelectorAll('input[name="create_priv"]:checked');
                privs = Array.from(privCheckboxes).map(cb => cb.value);
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
                    loadAccounts();
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
            document.getElementById('viewStatus').textContent = acc.status.toUpperCase();

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
            const activeReason = acc.block_reason || acc.delete_reason;
            const activeFile = acc.block_file || acc.delete_file;

            if (activeReason) {
                reasonCell.style.display = 'block';
                document.getElementById('viewReasonLabel').textContent = acc.block_reason ? 'Block Reason' : 'Deactivation / Delete Reason';
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
            if (acc.privileges) {
                try {
                    const parsed = JSON.parse(acc.privileges);
                    if (Array.isArray(parsed)) {
                        privCheckboxes.forEach(cb => {
                            if (parsed.includes(cb.value)) cb.checked = true;
                        });
                    }
                } catch(e) {}
            }

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

            if (pwd.length < 8) {
                setEditAccountError('editPassword', 'Password must be at least 8 characters long.');
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setEditAccountError('editPassword', 'Must include uppercase, number, and special symbol.');
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
                    const privCheckboxes = document.querySelectorAll('input[name="edit_priv"]:checked');
                    privs = Array.from(privCheckboxes).map(cb => cb.value);
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
            .then(r => r.json())
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