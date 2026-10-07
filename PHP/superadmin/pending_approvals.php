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
    <title>Pending Approvals - ONE PIECE Super Admin</title>
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

        .page-header-row {
            display: flex;
            justify-content: space-between;
            align-items: flex-start;
            flex-wrap: wrap;
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .pending-count-chip {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.5rem 1rem;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.45);
            border-radius: 999px;
            color: #fbbf24;
            font-size: 0.85rem;
            font-weight: 700;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.15);
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
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
            color: #334155;
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 0.9px;
            font-weight: 800;
            padding: 1rem 1.1rem;
            border-bottom: 2px solid #e2e8f0;
            text-shadow: none;
        }

        .styled-table td {
            padding: 0.95rem 1.1rem;
            color: #1e293b;
            font-size: 0.92rem;
            border-bottom: 1px solid #f1f5f9;
            vertical-align: middle;
        }

        .styled-table tr:last-child td {
            border-bottom: none;
        }

        .styled-table tbody tr {
            transition: background-color 0.2s ease;
        }

        .styled-table tbody tr:hover {
            background: #f8fafc;
        }

        /* Badges */
        .badge {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.25rem 0.65rem;
            border-radius: 6px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .badge-role-user { background: #dbeafe; color: #1e40af; border: 1px solid #bfdbfe; }
        .badge-role-admin { background: #fef3c7; color: #92400e; border: 1px solid #fde68a; }
        .badge-role-superadmin { background: #fee2e2; color: #991b1b; border: 1px solid #fecaca; }

        .badge-status-pending {
            background: #fef3c7;
            color: #b45309;
            border: 1px solid #fde68a;
            box-shadow: none;
        }

        .action-btn-group {
            display: flex;
            gap: 0.45rem;
            align-items: center;
        }

        .btn-action {
            padding: 0.45rem 0.85rem;
            border-radius: 7px;
            font-size: 0.8rem;
            font-weight: 700;
            cursor: pointer;
            border: 1px solid transparent;
            transition: all 0.2s ease;
            text-decoration: none;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
        }

        .btn-view { background: #eff6ff; color: #1d4ed8; border: 1px solid #bfdbfe; }
        .btn-view:hover { background: #dbeafe; color: #1e40af; }

        .btn-approve {
            background: #ecfdf5;
            color: #15803d;
            border: 1px solid #a7f3d0;
        }
        .btn-approve:hover {
            background: #dcfce7;
            color: #166534;
            transform: translateY(-1px);
        }

        .btn-reject {
            background: #fef2f2;
            color: #b91c1c;
            border: 1px solid #fecaca;
        }
        .btn-reject:hover {
            background: #fee2e2;
            color: #991b1b;
            transform: translateY(-1px);
        }

        /* Modal Styles */
        .app-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(6px);
            z-index: 9999;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        }

        .app-modal-overlay.active {
            display: flex;
        }

        .app-modal-card {
            background: linear-gradient(135deg, rgba(24, 30, 42, 0.98), rgba(16, 20, 30, 0.98));
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.7);
            max-width: 580px;
            width: 100%;
            padding: 2rem;
            position: relative;
            max-height: 90vh;
            overflow-y: auto;
        }

        .modal-title-row {
            display: flex;
            justify-content: space-between;
            align-items: center;
            margin-bottom: 1.25rem;
            padding-bottom: 0.75rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .modal-title-row h3 {
            font-size: 1.35rem;
            color: #ffffff;
            margin: 0;
        }

        .btn-modal-close {
            background: transparent;
            border: none;
            color: #94a3b8;
            font-size: 1.5rem;
            cursor: pointer;
            line-height: 1;
        }

        .btn-modal-close:hover {
            color: #ffffff;
        }

        .applicant-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 1rem;
            margin-bottom: 1.5rem;
        }

        .applicant-grid.full {
            grid-column: span 2;
        }

        .applicant-field {
            background: rgba(10, 14, 22, 0.6);
            border: 1px solid rgba(255, 255, 255, 0.06);
            border-radius: 10px;
            padding: 0.8rem 1rem;
        }

        .applicant-field.span-2 {
            grid-column: span 2;
        }

        .applicant-label {
            font-size: 0.78rem;
            font-weight: 700;
            color: #fbbf24;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            margin-bottom: 0.25rem;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .applicant-value {
            font-size: 0.95rem;
            color: #ffffff;
            font-weight: 600;
            word-break: break-word;
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
            background: rgba(8, 12, 22, 0.85);
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

        .empty-state-box {
            text-align: center;
            padding: 4rem 1.5rem;
            color: #cbd5e1;
        }

        .empty-state-box h3 {
            color: #ffffff !important;
            font-size: 1.35rem;
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-shadow: 0 2px 6px rgba(0, 0, 0, 0.6);
        }

        .empty-state-box p {
            color: #cbd5e1 !important;
            font-size: 0.95rem;
            margin: 0 0 1.25rem 0;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.6);
        }

        .empty-state-icon {
            width: 64px;
            height: 64px;
            margin: 0 auto 1.25rem;
            color: #10b981;
            opacity: 0.85;
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
        render_sidebar('pending_approvals');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="page-header-row">
                    <div>
                        <h1>Pending Approvals</h1>
                        <p style="color: #cbd5e1; margin: 0; font-size: 0.95rem; text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);">Review, inspect applicant registration details, and approve or reject accounts.</p>
                    </div>
                    <div>
                        <span class="pending-count-chip" id="pendingChip">
                            <span style="width: 8px; height: 8px; border-radius: 50%; background: #fbbf24; display: inline-block;"></span>
                            <span id="pendingCountText">Checking pending accounts...</span>
                        </span>
                    </div>
                </div>

                <!-- Filter Toolbar -->
                <div class="filter-toolbar">
                    <div class="search-input-wrapper">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                            <circle cx="11" cy="11" r="8" />
                            <line x1="21" y1="21" x2="16.65" y2="16.65" />
                        </svg>
                        <input type="text" id="searchInput" placeholder="Search applicant by ID, Name, Username, or Email..." oninput="filterPendingAccounts()">
                    </div>
                    <div>
                        <a href="manage_users.php" class="btn-action" style="background: #f1f5f9; color: #334155; border: 1px solid #cbd5e1;">
                            &larr; Back to Account Management
                        </a>
                    </div>
                </div>

                <!-- Pending Approvals Table -->
                <div class="table-card">
                    <table class="styled-table">
                        <thead>
                            <tr>
                                <th>ID Number</th>
                                <th>Applicant Name</th>
                                <th>Username</th>
                                <th>Email</th>
                                <th>Role</th>
                                <th>Status</th>
                                <th>Actions</th>
                            </tr>
                        </thead>
                        <tbody id="pendingTableBody">
                            <tr>
                                <td colspan="7" style="text-align: center; color: #94a3b8; padding: 2.5rem;">Loading pending applications...</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </main>
        </div>
    </div>

    <!-- View Applicant Details Modal -->
    <div class="app-modal-overlay" id="viewApplicantModal">
        <div class="app-modal-card">
            <div class="modal-title-row">
                <h3 id="modalApplicantTitle">Applicant Information</h3>
                <button class="btn-modal-close" onclick="closeModal('viewApplicantModal')">&times;</button>
            </div>

            <div class="applicant-grid">
                <div class="applicant-field">
                    <div class="applicant-label">ID Number</div>
                    <div class="applicant-value" id="viewIdNo" style="font-family: monospace; color: #fbbf24;">-</div>
                </div>
                <div class="applicant-field">
                    <div class="applicant-label">Username</div>
                    <div class="applicant-value" id="viewUsername" style="color: #f59e0b; font-weight: 700;">-</div>
                </div>
                <div class="applicant-field span-2">
                    <div class="applicant-label">Full Name</div>
                    <div class="applicant-value" id="viewFullName">-</div>
                </div>
                <div class="applicant-field">
                    <div class="applicant-label">Email Address</div>
                    <div class="applicant-value" id="viewEmail">-</div>
                </div>
                <div class="applicant-field">
                    <div class="applicant-label">Account Role</div>
                    <div class="applicant-value" id="viewRole">USER</div>
                </div>
                <div class="applicant-field">
                    <div class="applicant-label">Birthdate &amp; Age</div>
                    <div class="applicant-value" id="viewBirthdateAge">-</div>
                </div>
                <div class="applicant-field">
                    <div class="applicant-label">Gender / Sex</div>
                    <div class="applicant-value" id="viewSex">-</div>
                </div>
                <div class="applicant-field span-2">
                    <div class="applicant-label">Address</div>
                    <div class="applicant-value" id="viewAddress">-</div>
                </div>
                <div class="applicant-field span-2">
                    <div class="applicant-label">Application Status</div>
                    <div class="applicant-value"><span class="badge badge-status-pending">PENDING APPROVAL</span></div>
                </div>
            </div>

            <div style="display: flex; justify-content: flex-end; gap: 0.75rem;">
                <button type="button" class="btn-action" style="background: rgba(255,255,255,0.1); color: #fff;" onclick="closeModal('viewApplicantModal')">Close</button>
                <button type="button" class="btn-action btn-reject" id="btnModalReject">Reject</button>
                <button type="button" class="btn-action btn-approve" id="btnModalApprove">Approve Account</button>
            </div>
        </div>
    </div>

    <!-- Verification / Action Confirmation Modal -->
    <div class="app-modal-overlay" id="verificationModal">
        <div class="app-modal-card" style="max-width: 440px; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;" id="verifIcon">&#128274;</div>
            <h3 id="verifTitle" style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.25rem;">Confirm Action</h3>
            <p id="verifPrompt" style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 1.25rem;">Please enter your Super Admin password to verify this action.</p>

            <form id="verificationForm" onsubmit="executeVerifiedAction(event)">
                <!-- Reason input for rejection -->
                <div class="form-group-modal" id="reasonGroup" style="display: none; text-align: left;">
                    <label>Reason for Rejection <span style="color: #ef4444;">*</span></label>
                    <textarea id="actionReason" class="form-control-modal" placeholder="Enter reason why this registration is rejected..." rows="2"></textarea>
                </div>

                <div class="form-group-modal" style="text-align: left;">
                    <label>Your Password <span style="color: #ef4444;">*</span></label>
                    <input type="password" id="adminConfirmPassword" class="form-control-modal" placeholder="Enter current password" required autocomplete="current-password">
                </div>

                <div style="display: flex; justify-content: center; gap: 0.85rem; margin-top: 1.5rem;">
                    <button type="button" class="modal-btn modal-btn-cancel" onclick="closeModal('verificationModal')">Cancel</button>
                    <button type="submit" class="btn-create-account" id="btnConfirmAction" style="padding: 0.65rem 1.6rem;">Confirm</button>
                </div>
            </form>
        </div>
    </div>

    <script>
        let allPendingAccounts = [];
        let pendingActionPayload = null;

        document.addEventListener('DOMContentLoaded', () => {
            loadPendingAccounts();
        });

        function loadPendingAccounts() {
            fetch('../api/manage_users_api.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        // Strictly filter pending registrations only
                        allPendingAccounts = (data.users || []).filter(u => u.status === 'pending');
                        renderPendingTable(allPendingAccounts);
                        updatePendingChip(allPendingAccounts.length);
                    } else {
                        alert(data.error || 'Failed to load pending registrations');
                    }
                })
                .catch(err => {
                    console.error(err);
                });
        }

        function updatePendingChip(count) {
            const chip = document.getElementById('pendingCountText');
            if (chip) {
                chip.textContent = `${count} Pending Application${count === 1 ? '' : 's'}`;
            }
        }

        function renderPendingTable(accounts) {
            const tbody = document.getElementById('pendingTableBody');
            if (!accounts || accounts.length === 0) {
                tbody.innerHTML = `
                    <tr>
                        <td colspan="7">
                            <div class="empty-state-box">
                                <svg class="empty-state-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                    <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path>
                                    <polyline points="22 4 12 14.01 9 11.01"></polyline>
                                </svg>
                                <h3 style="color: #0f172a; margin-bottom: 0.5rem; font-weight: 800;">No Pending Approvals</h3>
                                <p style="margin: 0 0 1.25rem 0; color: #64748b;">All user registrations have been reviewed. There are no pending applications waiting for approval.</p>
                                <a href="manage_users.php" class="btn-action" style="background: #0f172a; color: #ffffff; padding: 0.6rem 1.2rem;">Go to Account Management</a>
                            </div>
                        </td>
                    </tr>
                `;
                return;
            }

            tbody.innerHTML = accounts.map(acc => {
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.trim() || acc.username;
                const roleBadge = `<span class="badge badge-role-${acc.role}">${acc.role}</span>`;
                const statusBadge = `<span class="badge badge-status-pending">PENDING</span>`;

                return `
                    <tr>
                        <td style="font-family: monospace; font-weight: 700; color: #2563eb; letter-spacing: 0.5px;">${acc.idno || 'N/A'}</td>
                        <td style="font-weight: 600; color: #0f172a;">${escapeHtml(fullName)}</td>
                        <td style="color: #d97706; font-weight: 700;">${escapeHtml(acc.username)}</td>
                        <td style="color: #475569;">${escapeHtml(acc.email || 'N/A')}</td>
                        <td>${roleBadge}</td>
                        <td>${statusBadge}</td>
                        <td>
                            <div class="action-btn-group">
                                <button class="btn-action btn-view" onclick="openViewApplicant('${acc.idno}')">View</button>
                                <button class="btn-action btn-approve" onclick="promptApprove('${acc.idno}', '${escapeHtml(acc.username)}')">Approve</button>
                                <button class="btn-action btn-reject" onclick="promptReject('${acc.idno}', '${escapeHtml(acc.username)}')">Reject</button>
                            </div>
                        </td>
                    </tr>
                `;
            }).join('');
        }

        function filterPendingAccounts() {
            const search = document.getElementById('searchInput').value.toLowerCase().trim();
            const filtered = allPendingAccounts.filter(acc => {
                const fullName = `${acc.firstname || ''} ${acc.lastname || ''}`.toLowerCase();
                const username = (acc.username || '').toLowerCase();
                const idno = (acc.idno || '').toLowerCase();
                const email = (acc.email || '').toLowerCase();

                return !search || fullName.includes(search) || username.includes(search) || idno.includes(search) || email.includes(search);
            });

            renderPendingTable(filtered);
        }

        function openViewApplicant(idno) {
            const acc = allPendingAccounts.find(a => (a.idno || a.id) == idno);
            if (!acc) return;

            document.getElementById('viewIdNo').textContent = acc.idno || 'N/A';
            document.getElementById('viewUsername').textContent = acc.username || 'N/A';
            
            const middle = acc.middlename ? ` ${acc.middlename}` : '';
            const suffix = acc.suffix ? ` ${acc.suffix}` : '';
            const fullName = `${acc.firstname || ''}${middle} ${acc.lastname || ''}${suffix}`.trim() || acc.username;
            document.getElementById('viewFullName').textContent = fullName;
            document.getElementById('viewEmail').textContent = acc.email || 'N/A';
            document.getElementById('viewRole').textContent = (acc.role || 'user').toUpperCase();

            const bdate = acc.birthdate || 'N/A';
            const age = acc.age ? ` (${acc.age} yrs old)` : '';
            document.getElementById('viewBirthdateAge').textContent = `${bdate}${age}`;
            document.getElementById('viewSex').textContent = (acc.sex || 'N/A').toUpperCase();

            // Address
            const addrParts = [acc.purokstreet, acc.brgy, acc.municipal, acc.province, acc.country, acc.zipcode].filter(Boolean);
            document.getElementById('viewAddress').textContent = addrParts.length > 0 ? addrParts.join(', ') : 'Not provided';

            // Modal Buttons binding
            document.getElementById('btnModalApprove').onclick = () => {
                closeModal('viewApplicantModal');
                promptApprove(acc.idno, acc.username);
            };
            document.getElementById('btnModalReject').onclick = () => {
                closeModal('viewApplicantModal');
                promptReject(acc.idno, acc.username);
            };

            openModal('viewApplicantModal');
        }

        function promptApprove(idno, username) {
            pendingActionPayload = {
                action: 'approve',
                user_id: idno,
                username: username
            };
            document.getElementById('verifIcon').textContent = '✅';
            document.getElementById('verifTitle').textContent = `Approve Account (${username})`;
            document.getElementById('verifPrompt').textContent = `Are you sure you want to approve this account (${username})? Once approved, they will be able to log in.`;
            document.getElementById('reasonGroup').style.display = 'none';
            document.getElementById('btnConfirmAction').textContent = 'Confirm Approval';
            document.getElementById('btnConfirmAction').style.background = 'linear-gradient(135deg, #10b981, #059669)';
            document.getElementById('adminConfirmPassword').value = '';
            openModal('verificationModal');
            document.getElementById('adminConfirmPassword').focus();
        }

        function promptReject(idno, username) {
            pendingActionPayload = {
                action: 'reject',
                user_id: idno,
                username: username
            };
            document.getElementById('verifIcon').textContent = '❌';
            document.getElementById('verifTitle').textContent = `Reject Registration (${username})`;
            document.getElementById('verifPrompt').textContent = `Are you sure you want to reject and remove the registration for ${username}?`;
            document.getElementById('reasonGroup').style.display = 'block';
            document.getElementById('actionReason').value = '';
            document.getElementById('btnConfirmAction').textContent = 'Confirm Rejection';
            document.getElementById('btnConfirmAction').style.background = 'linear-gradient(135deg, #ef4444, #dc2626)';
            document.getElementById('adminConfirmPassword').value = '';
            openModal('verificationModal');
            document.getElementById('actionReason').focus();
        }

        function executeVerifiedAction(e) {
            e.preventDefault();
            if (!pendingActionPayload) return;

            const password = document.getElementById('adminConfirmPassword').value;
            if (!password) {
                alert('Please enter your administrator password.');
                return;
            }

            if (pendingActionPayload.action === 'reject') {
                const reason = document.getElementById('actionReason').value.trim();
                if (!reason) {
                    alert('Please enter a reason for rejecting this registration.');
                    document.getElementById('actionReason').focus();
                    return;
                }
                pendingActionPayload.reason = reason;
            }

            pendingActionPayload.admin_password = password;

            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(pendingActionPayload)
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    alert(res.message);
                    closeModal('verificationModal');
                    loadPendingAccounts();
                } else {
                    alert(res.error || 'Operation failed');
                }
            })
            .catch(err => {
                alert('Network error: ' + err);
            });
        }

        function openModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.add('active');
        }

        function closeModal(id) {
            const modal = document.getElementById(id);
            if (modal) modal.classList.remove('active');
        }

        function escapeHtml(text) {
            if (!text) return '';
            return String(text).replace(/[&<>"']/g, m => ({
                '&': '&amp;',
                '<': '&lt;',
                '>': '&gt;',
                '"': '&quot;',
                "'": '&#039;'
            })[m]);
        }
    </script>
</body>

</html>
