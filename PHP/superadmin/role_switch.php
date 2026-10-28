<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}
require_once __DIR__ . '/../db.php';
require_once __DIR__ . '/../includes/auth_guard.php';
enforce_profile_completion($conn);

$active_page = 'role_switch';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Role Handover - ONE PIECE</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        body {
            background-image:
                linear-gradient(rgba(10, 15, 25, 0.25), rgba(10, 15, 25, 0.40)),
                url('../../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
        }

        .role-switch-container {
            max-width: 1200px;
            margin: 0 auto;
            padding: 2rem 1.5rem;
        }

        .header-title-section {
            margin-bottom: 2rem;
        }

        .header-title-section h1 {
            font-size: 2.4rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.6);
        }

        .header-title-section p {
            color: #cbd5e1;
            font-size: 1.05rem;
            font-weight: 500;
        }

        /* Business Rule Banner */
        .business-rule-banner {
            background: linear-gradient(135deg, rgba(245, 158, 11, 0.12) 0%, rgba(239, 68, 68, 0.12) 100%);
            border: 1px solid rgba(245, 158, 11, 0.35);
            border-left: 5px solid #f59e0b;
            border-radius: 12px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 2rem;
            display: flex;
            align-items: flex-start;
            gap: 1.2rem;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
            backdrop-filter: blur(8px);
        }

        .rule-icon {
            font-size: 2rem;
            line-height: 1;
        }

        .rule-content h4 {
            margin: 0 0 0.3rem 0;
            font-size: 1.1rem;
            font-weight: 700;
            color: #fbbf24;
            letter-spacing: 0.3px;
        }

        .rule-content p {
            margin: 0;
            font-size: 0.92rem;
            color: #e2e8f0;
            line-height: 1.5;
        }

        /* Admin Cards Grid */
        .admins-grid {
            display: grid;
            grid-template-columns: repeat(auto-fit, minmax(320px, 1fr));
            gap: 1.5rem;
            margin-bottom: 2.5rem;
        }

        .admin-card {
            background: rgba(18, 22, 31, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.75rem;
            position: relative;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
            transition: all 0.3s ease;
            display: flex;
            flex-direction: column;
            justify-content: space-between;
        }

        .admin-card.is-active {
            border: 2px solid #10b981;
            box-shadow: 0 12px 35px rgba(16, 185, 129, 0.2);
            background: linear-gradient(135deg, rgba(16, 185, 129, 0.08) 0%, rgba(18, 22, 31, 0.95) 100%);
        }

        .admin-card-top {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.2rem;
        }

        .admin-avatar {
            width: 48px;
            height: 48px;
            border-radius: 12px;
            background: linear-gradient(135deg, #3b82f6, #1d4ed8);
            color: white;
            font-weight: 800;
            font-size: 1.25rem;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 4px 12px rgba(0, 0, 0, 0.3);
        }

        .admin-card.is-active .admin-avatar {
            background: linear-gradient(135deg, #10b981, #059669);
        }

        .status-badge {
            font-size: 0.75rem;
            font-weight: 800;
            letter-spacing: 0.8px;
            text-transform: uppercase;
            padding: 4px 12px;
            border-radius: 20px;
        }

        .status-badge.active {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .status-badge.inactive {
            background: rgba(148, 163, 184, 0.15);
            color: #94a3b8;
            border: 1px solid rgba(148, 163, 184, 0.3);
        }

        .admin-info h3 {
            font-size: 1.25rem;
            font-weight: 700;
            margin: 0 0 0.3rem 0;
            color: #ffffff;
        }

        .admin-email {
            font-size: 0.92rem;
            color: #94a3b8;
            margin-bottom: 1.2rem;
            display: flex;
            align-items: center;
            gap: 6px;
            word-break: break-all;
        }

        .admin-meta {
            font-size: 0.82rem;
            color: #64748b;
            margin-bottom: 1.5rem;
        }

        .btn-switch-trigger {
            width: 100%;
            padding: 0.8rem 1rem;
            border: none;
            border-radius: 10px;
            font-weight: 700;
            font-size: 0.92rem;
            cursor: pointer;
            transition: all 0.25s ease;
            display: flex;
            align-items: center;
            justify-content: center;
            gap: 8px;
        }

        .btn-switch-trigger.btn-enabled {
            background: linear-gradient(135deg, #f59e0b 0%, #d97706 100%);
            color: #ffffff;
            box-shadow: 0 4px 15px rgba(245, 158, 11, 0.3);
        }

        .btn-switch-trigger.btn-enabled:hover {
            transform: translateY(-2px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
        }

        .btn-switch-trigger.btn-current {
            background: rgba(16, 185, 129, 0.15);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.3);
            cursor: default;
        }

        /* History Table Section */
        .section-box {
            background: rgba(18, 22, 31, 0.88);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 16px;
            padding: 1.75rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25);
            backdrop-filter: blur(10px);
        }

        .section-header {
            display: flex;
            align-items: center;
            justify-content: space-between;
            margin-bottom: 1.25rem;
            padding-bottom: 0.8rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .section-header h2 {
            font-size: 1.35rem;
            font-weight: 700;
            margin: 0;
            color: #ffffff;
        }

        .custom-table {
            width: 100%;
            border-collapse: collapse;
            font-size: 0.9rem;
        }

        .custom-table th {
            text-align: left;
            padding: 0.85rem 1rem;
            background: rgba(255, 255, 255, 0.03);
            color: #94a3b8;
            font-weight: 700;
            text-transform: uppercase;
            font-size: 0.75rem;
            letter-spacing: 0.5px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
        }

        .custom-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: #e2e8f0;
        }

        .custom-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .req-status-pill {
            display: inline-block;
            font-size: 0.72rem;
            font-weight: 800;
            text-transform: uppercase;
            padding: 3px 8px;
            border-radius: 6px;
        }

        .req-status-pending {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.4);
        }

        .req-status-accepted {
            background: rgba(16, 185, 129, 0.2);
            color: #34d399;
            border: 1px solid rgba(16, 185, 129, 0.4);
        }

        .req-status-expired {
            background: rgba(239, 68, 68, 0.2);
            color: #f87171;
            border: 1px solid rgba(239, 68, 68, 0.4);
        }

        /* Modal Styles */
        .switch-modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(8px);
            z-index: 2000;
            align-items: center;
            justify-content: center;
            animation: fadeIn 0.2s ease;
        }

        .switch-modal-overlay.active {
            display: flex;
        }

        .switch-modal {
            background: #12161f;
            border: 1px solid rgba(255, 255, 255, 0.12);
            border-radius: 18px;
            max-width: 520px;
            width: 90%;
            padding: 2.2rem;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.6);
            animation: slideUp 0.3s ease;
        }

        .switch-modal h3 {
            margin: 0 0 0.5rem 0;
            font-size: 1.45rem;
            color: #ffffff;
        }

        .switch-modal p {
            color: #94a3b8;
            font-size: 0.92rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
        }

        .modal-field {
            margin-bottom: 1.25rem;
        }

        .modal-field label {
            display: block;
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #cbd5e1;
            margin-bottom: 0.4rem;
        }

        .modal-field input, .modal-field select {
            width: 100%;
            padding: 0.8rem 1rem;
            background: #0d1117;
            border: 1px solid rgba(255, 255, 255, 0.15);
            border-radius: 8px;
            color: #ffffff;
            font-size: 0.95rem;
            outline: none;
            box-sizing: border-box;
        }

        .modal-actions {
            display: flex;
            gap: 1rem;
            justify-content: flex-end;
            margin-top: 1.8rem;
        }

        .btn-modal {
            padding: 0.75rem 1.5rem;
            border-radius: 8px;
            font-size: 0.92rem;
            font-weight: 700;
            cursor: pointer;
            border: none;
            transition: all 0.25s ease;
        }

        .btn-modal-cancel {
            background: rgba(255, 255, 255, 0.08);
            color: #cbd5e1;
        }

        .btn-modal-cancel:hover {
            background: rgba(255, 255, 255, 0.15);
        }

        .btn-modal-confirm {
            background: linear-gradient(135deg, #ef4444 0%, #dc2626 100%);
            color: white;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.3);
        }

        .btn-modal-confirm:hover:not(:disabled) {
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(239, 68, 68, 0.5);
        }

        .btn-modal-confirm:disabled {
            opacity: 0.5;
            cursor: not-allowed;
        }

        /* Toast Alert */
        .toast-msg {
            position: fixed;
            bottom: 2rem;
            right: 2rem;
            padding: 1rem 1.5rem;
            border-radius: 10px;
            font-weight: 600;
            font-size: 0.95rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.5);
            display: none;
            z-index: 3000;
            animation: toastIn 0.3s ease;
        }

        .toast-msg.success {
            background: #10b981;
            color: white;
        }

        .toast-msg.error {
            background: #ef4444;
            color: white;
        }

        @keyframes toastIn {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }
    </style>
</head>
<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/../includes/sidebar.php';
        render_sidebar('role_switch');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="role-switch-container">
                    
                    <div class="header-title-section">
                        <h1>Super Admin Role Handover</h1>
                        <p>Securely transfer Active Super Admin rights via Gmail SMTP 24-Hour Confirmation Link.</p>
                    </div>

                    <!-- Business Rule Notice -->
                    <div class="business-rule-banner">
                        <div class="rule-icon">⚖️</div>
                        <div class="rule-content">
                            <h4>Strict Business Rule: Single Active Super Admin</h4>
                            <p>Only ONE (1) Super Admin can hold an <strong>"Active"</strong> status at any given time, while the other two remain <strong>"Inactive"</strong>. You remain Active while a handover request is Pending. Once the recipient confirms via Gmail, the database performs an atomic switch: setting your account to Inactive, promoting their account to Active, and invalidating previous sessions.</p>
                        </div>
                    </div>

                    <!-- Super Admins 3 Accounts Grid -->
                    <h2 style="font-size: 1.4rem; margin-bottom: 1rem;">Super Administrator Accounts</h2>
                    <div class="admins-grid" id="adminsGrid">
                        <div style="grid-column: 1/-1; text-align: center; padding: 2rem; color: #94a3b8;">
                            Loading Super Admin accounts...
                        </div>
                    </div>

                    <!-- Request History Section -->
                    <div class="section-box">
                        <div class="section-header">
                            <h2>Handover Request History</h2>
                            <button onclick="loadRoleSwitchData()" style="background: transparent; border: 1px solid rgba(255,255,255,0.15); color: #cbd5e1; padding: 6px 14px; border-radius: 6px; cursor: pointer;">
                                🔄 Refresh
                            </button>
                        </div>
                        <div style="overflow-x: auto;">
                            <table class="custom-table">
                                <thead>
                                    <tr>
                                        <th>Date Initiated</th>
                                        <th>From (Active)</th>
                                        <th>To (Recipient)</th>
                                        <th>Expires At</th>
                                        <th>Status</th>
                                        <th>Action</th>
                                    </tr>
                                </thead>
                                <tbody id="requestsTableBody">
                                    <tr>
                                        <td colspan="6" style="text-align: center; color: #94a3b8;">Loading history...</td>
                                    </tr>
                                </tbody>
                            </table>
                        </div>
                    </div>

                </div>
            </main>
        </div>
    </div>

    <!-- Handover Confirmation Modal -->
    <div class="switch-modal-overlay" id="switchModal">
        <div class="switch-modal">
            <div style="font-size: 2.2rem; margin-bottom: 0.5rem;">📩</div>
            <h3>Initiate Role Handover</h3>
            <p>Send a secure confirmation link via Gmail SMTP to the selected Inactive Super Admin. The recipient must click the link within 24 hours to activate their account.</p>
            
            <form id="switchForm" onsubmit="submitSwitchRequest(event)">
                <input type="hidden" id="targetAdminId" name="target_admin_id" value="">
                
                <div class="modal-field">
                    <label>Selected Inactive Super Admin</label>
                    <input type="text" id="targetAdminName" readonly style="background: rgba(255,255,255,0.05); color: #fbbf24; font-weight: 700;">
                </div>

                <div class="modal-field">
                    <label>Recipient Gmail Address</label>
                    <input type="email" id="targetAdminEmail" readonly style="background: rgba(255,255,255,0.05); color: #94a3b8;">
                </div>

                <div style="background: rgba(239, 68, 68, 0.1); border-left: 3px solid #ef4444; padding: 10px 14px; border-radius: 6px; font-size: 0.85rem; color: #fca5a5; margin-bottom: 1.5rem;">
                    <strong>Security Notice:</strong> You will remain Active until the recipient clicks the Gmail verification button. Once accepted, your account will immediately become Inactive and your current session will be invalidated.
                </div>

                <div class="modal-actions">
                    <button type="button" class="btn-modal btn-modal-cancel" onclick="closeSwitchModal()">Cancel</button>
                    <button type="submit" class="btn-modal btn-modal-confirm" id="btnSubmitSwitch">
                        Send Gmail Confirmation
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div class="toast-msg" id="toastMsg"></div>

    <script>
        let currentAdminData = null;

        function showToast(message, isSuccess = true) {
            const toast = document.getElementById('toastMsg');
            toast.className = 'toast-msg ' + (isSuccess ? 'success' : 'error');
            toast.innerText = message;
            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 5000);
        }

        async function loadRoleSwitchData() {
            try {
                const res = await fetch('../api/role_switch_api.php?action=list');
                const data = await res.json();

                if (!data.success) {
                    showToast(data.message || 'Failed to load data', false);
                    return;
                }

                currentAdminData = data.current_admin;
                renderAdminsGrid(data.admins);
                renderRequestsTable(data.requests);
            } catch (err) {
                console.error(err);
                showToast('Network error loading role handover data', false);
            }
        }

        function renderAdminsGrid(admins) {
            const container = document.getElementById('adminsGrid');
            if (!admins || admins.length === 0) {
                container.innerHTML = '<div style="grid-column: 1/-1; text-align: center;">No Super Admins found.</div>';
                return;
            }

            container.innerHTML = admins.map(a => {
                const isActive = (a.status === 'Active');
                const initial = a.name ? a.name.charAt(0).toUpperCase() : 'S';
                const hasEmail = a.email && a.email.trim() !== '';

                let actionButton = '';
                if (isActive) {
                    actionButton = `<button class="btn-switch-trigger btn-current" disabled>
                        <span>👑 Currently Active Super Admin</span>
                    </button>`;
                } else if (!hasEmail) {
                    actionButton = `<button class="btn-switch-trigger" disabled style="opacity: 0.6; cursor: not-allowed; background: rgba(100, 116, 139, 0.2); border-color: rgba(148, 163, 184, 0.3); color: #94a3b8;" title="Add email in Account Management first to initiate handover">
                        <span>⚠️ No Email (Cannot Handover)</span>
                    </button>`;
                } else {
                    actionButton = `<button class="btn-switch-trigger btn-enabled" onclick="openSwitchModal(${a.id}, '${escapeHtml(a.name)}', '${escapeHtml(a.email)}')">
                        <span>✉️ Initiate Handover to this Admin</span>
                    </button>`;
                }

                return `
                    <div class="admin-card ${isActive ? 'is-active' : ''}">
                        <div>
                            <div class="admin-card-top">
                                <div class="admin-avatar">${initial}</div>
                                <span class="status-badge ${isActive ? 'active' : 'inactive'}">
                                    ${isActive ? '👑 ACTIVE' : 'INACTIVE'}
                                </span>
                            </div>
                            <div class="admin-info">
                                <h3>${escapeHtml(a.name)}</h3>
                                <div class="admin-email">
                                    <span>📧</span>
                                    <span>${hasEmail ? escapeHtml(a.email) : '<em style="color:#94a3b8; font-style:italic;">No email registered</em>'}</span>
                                </div>
                                <div class="admin-meta">
                                    Role: <strong>${escapeHtml(a.role)}</strong> &bull; ID: ${escapeHtml(a.user_idno || 'A-' + a.id)}
                                </div>
                            </div>
                        </div>
                        <div>
                            ${actionButton}
                        </div>
                    </div>
                `;
            }).join('');
        }

        function renderRequestsTable(requests) {
            const tbody = document.getElementById('requestsTableBody');
            if (!requests || requests.length === 0) {
                tbody.innerHTML = '<tr><td colspan="6" style="text-align: center; color: #94a3b8; padding: 1.5rem;">No handover requests recorded yet.</td></tr>';
                return;
            }

            tbody.innerHTML = requests.map(r => {
                let statusBadge = '';
                if (r.status === 'Accepted') {
                    statusBadge = '<span class="req-status-pill req-status-accepted">✓ Accepted</span>';
                } else if (r.status === 'Pending') {
                    statusBadge = '<span class="req-status-pill req-status-pending">⏳ Pending</span>';
                } else {
                    statusBadge = '<span class="req-status-pill req-status-expired">✗ Expired</span>';
                }

                let cancelBtn = '-';
                if (r.status === 'Pending') {
                    cancelBtn = `<button onclick="cancelRequest(${r.id})" style="background: rgba(239,68,68,0.2); border: 1px solid rgba(239,68,68,0.4); color: #f87171; padding: 4px 10px; border-radius: 6px; font-size: 0.8rem; cursor: pointer;">Cancel</button>`;
                }

                const createdDate = new Date(r.created_at).toLocaleString();
                const expiresDate = new Date(r.expires_at).toLocaleString();

                return `
                    <tr>
                        <td>${createdDate}</td>
                        <td><strong>${escapeHtml(r.from_name || 'Admin')}</strong></td>
                        <td><strong>${escapeHtml(r.to_name || 'Admin')}</strong><br><small style="color: #94a3b8;">${escapeHtml(r.to_email || '')}</small></td>
                        <td>${expiresDate}</td>
                        <td>${statusBadge}</td>
                        <td>${cancelBtn}</td>
                    </tr>
                `;
            }).join('');
        }

        function openSwitchModal(targetId, targetName, targetEmail) {
            document.getElementById('targetAdminId').value = targetId;
            document.getElementById('targetAdminName').value = targetName;
            document.getElementById('targetAdminEmail').value = targetEmail;
            document.getElementById('switchModal').classList.add('active');
        }

        function closeSwitchModal() {
            document.getElementById('switchModal').classList.remove('active');
        }

        async function submitSwitchRequest(e) {
            e.preventDefault();
            const btn = document.getElementById('btnSubmitSwitch');
            const targetId = document.getElementById('targetAdminId').value;

            btn.disabled = true;
            btn.innerText = 'Sending Gmail via SMTP...';

            try {
                const formData = new FormData();
                formData.append('action', 'initiate');
                formData.append('target_admin_id', targetId);

                const res = await fetch('../api/role_switch_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    closeSwitchModal();
                    showToast(result.message, true);
                    loadRoleSwitchData();
                } else {
                    showToast(result.message || 'Error initiating switch', false);
                }
            } catch (err) {
                console.error(err);
                showToast('Failed to contact server', false);
            } finally {
                btn.disabled = false;
                btn.innerText = 'Send Gmail Confirmation';
            }
        }

        async function cancelRequest(requestId) {
            if (!confirm('Are you sure you want to cancel this pending handover request?')) return;

            try {
                const formData = new FormData();
                formData.append('action', 'cancel');
                formData.append('request_id', requestId);

                const res = await fetch('../api/role_switch_api.php', {
                    method: 'POST',
                    body: formData
                });
                const result = await res.json();

                if (result.success) {
                    showToast(result.message, true);
                    loadRoleSwitchData();
                } else {
                    showToast(result.message || 'Failed to cancel', false);
                }
            } catch (err) {
                console.error(err);
                showToast('Network error cancelling request', false);
            }
        }

        function escapeHtml(str) {
            if (!str) return '';
            return String(str)
                .replace(/&/g, '&amp;')
                .replace(/</g, '&lt;')
                .replace(/>/g, '&gt;')
                .replace(/"/g, '&quot;')
                .replace(/'/g, '&#039;');
        }

        document.addEventListener('DOMContentLoaded', loadRoleSwitchData);
    </script>
</body>
</html>
