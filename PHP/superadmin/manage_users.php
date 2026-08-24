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
    <title>Manage Users - Super Admin</title>
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

        /* Professional Theme */
        h1 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
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
            width: 100%;
            margin-top: 1rem;
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            margin-bottom: 2rem;
            overflow-x: auto;
        }

        .table-wrapper {
            background: transparent;
            border-radius: 10px;
            position: relative;
            min-width: 900px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color);
            table-layout: fixed;
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
            padding: 1.2rem 0.5rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
            white-space: nowrap;
            overflow: hidden;
            text-overflow: ellipsis;
        }

        th:last-child,
        td:last-child {
            width: 350px;
            overflow: visible;
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
        }

        .role-select {
            padding: 0.5rem;
            border-radius: 8px;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            width: 100%;
            cursor: pointer;
            font-size: 0.85rem;
            font-weight: bold;
            transition: all 0.3s;
        }

        .role-select:focus {
            outline: none;
            border-color: var(--accent-yellow);
        }

        .role-select option {
            background: #050505;
            color: white;
        }

        .action-buttons {
            display: flex;
            gap: 0.3rem;
            align-items: center;
            justify-content: flex-start;
        }

        .btn-action {
            padding: 0.4rem 0.8rem;
            border: 1px solid transparent;
            border-radius: 6px;
            cursor: pointer;
            font-size: 0.8rem;
            font-weight: 600;
            text-transform: uppercase;
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

        .role-superadmin {
            color: var(--accent-blue);
            font-weight: bold;
        }

        .role-admin {
            color: #4facfe;
            font-weight: bold;
        }

        .role-user {
            color: var(--text-color);
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
            background-color: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(8px);
            align-items: flex-start;
            padding-top: 2rem;
            padding-bottom: 2rem;
        }

        .modal-content,
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

        #deleteModal {
            align-items: center;
            justify-content: center;
            padding: 0;
        }

        #deleteModal .modal-content {
            max-width: 400px;
            margin: auto;
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
        form,
        h3,
        p,
        .modal-buttons {
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
            text-align: left;
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

        h3 {
            color: var(--accent-green);
            margin-top: 1.5rem;
            margin-bottom: 1rem;
            font-size: 1.3rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border-bottom: 1px solid rgba(255, 255, 255, 0.1);
            padding-bottom: 0.5rem;
            text-shadow: 1px 1px 2px rgba(0, 0, 0, 0.5);
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
                <li><a href="manage_users.php" style="color: var(--accent-blue);">Manage Users</a></li>
                <li><a href="block_list.php">Block List</a></li>
                <li><a href="audit_logs.php">Audit Logs</a></li>
                <button onclick="location.href='../logout.php'">Logout</button>
            </ul>
        </div>
    </nav>

    <div class="container">
        <h1>Manage Users</h1>
        <p>View, edit, block/unblock, and delete user accounts.</p>
        <button class="btn-action btn-block" style="background: var(--accent-blue); margin-bottom: 1rem;"
            onclick="openCreateUserModal()">+ Create User</button>

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
                            <th>Role</th>
                            <th>Status</th>
                            <th>Actions</th>
                        </tr>
                    </thead>
                    <tbody id="usersTableBody">
                        <tr>
                            <td colspan="8" style="text-align: center;">Loading users...</td>
                        </tr>
                    </tbody>
                </table>
            </div>
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

    <!-- Edit User Modal -->
    <div id="editUserModal" class="modal">
        <div class="modal-large-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2 style="color: var(--accent-orange);">Edit User</h2>
                    <span class="close" onclick="closeLargeModal('editUserModal')">&times;</span>
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
                        style="margin-top: 1.5rem; width: 100%; border-color: var(--accent-orange); font-size: 1rem; padding: 1rem;">Save
                        Changes</button>
                </form>
            </div>
        </div>
    </div>

    <!-- Create User Modal -->
    <div id="createUserModal" class="modal">
        <div class="modal-large-content">
            <div class="modal-inner">
                <div class="modal-header">
                    <h2>Create New User</h2>
                    <span class="close" onclick="closeLargeModal('createUserModal')">&times;</span>
                </div>
                <form id="createUserForm" onsubmit="event.preventDefault(); submitCreateUserForm();">
                    <input type="hidden" id="create_role" value="user">

                    <h3>Personal Information</h3>
                    <div class="form-grid">
                        <div><label>ID No <small style="color:var(--accent-green)">(Auto-generated)</small></label><input type="text"
                                id="create_idno" readonly style="background: rgba(255,255,255,0.08); cursor: not-allowed; color: #00ff66; font-weight: bold;" required placeholder="xxxx-xxxx"></div>
                        <div><label>First Name <small style="color:var(--accent-red)">*</small></label><input
                                type="text" id="create_firstname" required></div>
                        <div><label>Last Name <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_lastname" required></div>
                        <div><label>Middle Initial</label><input type="text" id="create_middlename" maxlength="1"></div>
                        <div><label>Suffix</label>
                            <select id="create_suffix">
                                <option value="">None</option>
                                <option value="Sr">Sr.</option>
                                <option value="Jr">Jr.</option>
                            </select>
                        </div>
                        <div><label>Birthdate <small style="color:var(--accent-red)">*</small></label><input type="date"
                                id="create_birthdate" required onchange="calculateAgeCreate()"></div>
                        <div><label>Age <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_age" readonly></div>
                        <div><label>Sex <small style="color:var(--accent-red)">*</small></label><select id="create_sex"
                                required>
                                <option value="">Select Sex</option>
                                <option value="Male">Male</option>
                                <option value="Female">Female</option>
                            </select></div>
                    </div>

                    <h3 style="color: var(--accent-yellow);">Address Information</h3>
                    <div class="form-grid">
                        <div><label>Purok <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_purokstreet" required></div>
                        <div><label>Barangay <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_brgy" required></div>
                        <div><label>Municipality <small style="color:var(--accent-red)">*</small></label><input
                                type="text" id="create_municipal" required></div>
                        <div><label>Province <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_province" required></div>
                        <div><label>Country <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_country" required></div>
                        <div><label>Zip Code <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_zipcode" required></div>
                    </div>

                    <h3 style="color: var(--accent-green);">User's Login Account</h3>
                    <div class="form-grid">
                        <div><label>Username <small style="color:var(--accent-red)">*</small></label><input type="text"
                                id="create_username" required></div>
                        <div><label>Email <small style="color:var(--accent-red)">*</small></label><input type="email"
                                id="create_email" required></div>
                        <div><label>Password <small style="color:var(--accent-red)">*</small></label><input
                                type="password" id="create_password" required minlength="8"></div>
                        <div><label>Confirm Password <small style="color:var(--accent-red)">*</small></label><input
                                type="password" id="create_confirm_password" required minlength="8"></div>
                    </div>

                    <h3 style="color: var(--accent-red);">Security Questions</h3>
                    <div class="form-grid">
                        <div>
                            <label>Security Question 1 <small style="color:var(--accent-red)">*</small></label>
                            <select id="create_security_question1" required>
                                <option value="">Select a question</option>
                                <option value="What is your mother's maiden name?">What is your mother's maiden name?
                                </option>
                                <option value="What was the name of your first pet?">What was the name of your first
                                    pet?</option>
                                <option value="What is the name of the city where you were born?">What is the name of
                                    the city where you were born?</option>
                                <option value="What is your favorite food?">What is your favorite food?</option>
                            </select>
                            <input type="password" id="create_security_answer1" placeholder="Answer 1"
                                style="margin-top: 5px;" required>
                        </div>
                        <div>
                            <label>Security Question 2 <small style="color:var(--accent-red)">*</small></label>
                            <select id="create_security_question2" required>
                                <option value="">Select a question</option>
                                <option value="What is the name of your favorite teacher?">What is the name of your
                                    favorite teacher?</option>
                                <option value="What is your favorite movie?">What is your favorite movie?</option>
                                <option value="What is the make of your first car?">What is the make of your first car?
                                </option>
                                <option value="What is your lucky number?">What is your lucky number?</option>
                            </select>
                            <input type="password" id="create_security_answer2" placeholder="Answer 2"
                                style="margin-top: 5px;" required>
                        </div>
                        <div>
                            <label>Security Question 3 <small style="color:var(--accent-red)">*</small></label>
                            <select id="create_security_question3" required>
                                <option value="">Select a question</option>
                                <option value="What is your favorite color?">What is your favorite color?</option>
                                <option value="What is the name of your best friend?">What is the name of your best
                                    friend?</option>
                                <option value="What is your dream job?">What is your dream job?</option>
                                <option value="What is your favorite sports team?">What is your favorite sports team?
                                </option>
                            </select>
                            <input type="password" id="create_security_answer3" placeholder="Answer 3"
                                style="margin-top: 5px;" required>
                        </div>
                    </div>

                    <button type="submit" class="btn-action btn-block"
                        style="margin-top: 2rem; width: 100%; background: var(--accent-blue); border-color: var(--accent-blue); font-size: 1rem; padding: 1rem;">Create
                        User Account</button>
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
                <div class="modal-buttons">
                    <button class="btn-action btn-unblock" id="confirmApproveBtn">Approve</button>
                    <button class="btn-action" style="background: rgba(255,255,255,0.1); color: white;"
                        onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <div class="modal" id="deleteModal">
        <div class="modal-content">
            <div class="modal-inner" style="text-align: center;">
                <h3 style="color: var(--accent-red); margin-top: 0;">Confirm Delete</h3>
                <p>Are you sure you want to delete this user? This action cannot be undone.</p>
                <div class="modal-buttons">
                    <button class="btn-action btn-delete" style="border-color: #ff1a1a;"
                        id="confirmDeleteBtn">Delete</button>
                    <button class="btn-action" style="background: rgba(255,255,255,0.1); color: white;"
                        onclick="closeModal()">Cancel</button>
                </div>
            </div>
        </div>
    </div>

    <script>
        let deleteUserId = null;
        let approveUserId = null;

        // Load users on page load
        document.addEventListener('DOMContentLoaded', loadUsers);

        function loadUsers() {
            fetch('../api/manage_users_api.php')
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        const users = data.users.filter(user => user.role === 'user');
                        renderTable(users, 'usersTableBody', 'No users found.');
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
                tbody.innerHTML = `<tr><td colspan="8" style="text-align: center;">${emptyMessage}</td></tr>`;
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
                let deleteButtonHtml = '';

                if (user.status !== 'inactive') {
                    if (user.status === 'pending') {
                        blockButtonHtml = `<button class="btn-action btn-unblock" onclick="confirmApprove(${user.id})">Approve</button>
                                           <button class="btn-action btn-delete" onclick="confirmDelete(${user.id})">Reject</button>`;
                    } else if (user.status === 'blocked') {
                        blockButtonHtml = `<button class="btn-action btn-unblock" onclick="unblockUser(${user.id})">Unblock</button>`;
                        deleteButtonHtml = `<button class="btn-action btn-delete" onclick="confirmDelete(${user.id})">Delete</button>`;
                    } else {
                        blockButtonHtml = `<button class="btn-action btn-block" onclick="blockUser(${user.id})">Block</button>`;
                        deleteButtonHtml = `<button class="btn-action btn-delete" onclick="confirmDelete(${user.id})">Delete</button>`;
                    }
                }

                return `
                <tr data-search="${(user.firstname + ' ' + user.lastname + ' ' + user.username + ' ' + user.email).toLowerCase()}">
                    <td>${user.id}</td>
                    <td>${user.idno}</td>
                    <td>${user.firstname} ${user.lastname}</td>
                    <td>${user.username}</td>
                    <td>${user.email}</td>
                    <td>
                        <select class="role-select" onchange="changeRole(${user.id}, this.value)" ${user.role === 'superadmin' ? 'disabled' : ''}>
                            <option value="user" ${user.role === 'user' ? 'selected' : ''}>User</option>
                            <option value="admin" ${user.role === 'admin' ? 'selected' : ''}>Admin</option>
                        </select>
                    </td>
                    <td class="${statusClass}" style="${user.status === 'pending' ? 'color: var(--accent-yellow); text-shadow: 0 0 10px rgba(255, 193, 7, 0.4);' : ''}">
                        ${statusLabel}
                    </td>
                    <td>
                        <div class="action-buttons">
                            <button class="btn-action btn-view" onclick="viewUser(${user.id})">View</button>
                            <button class="btn-action btn-edit" onclick="openEditModal(${user.id})">Edit</button>
                            ${blockButtonHtml}
                            ${deleteButtonHtml}
                        </div>
                    </td>
                </tr>
            `}).join('');
        }

        function filterTable() {
            const searchText = document.getElementById('searchInput').value.toLowerCase();
            const userRows = document.querySelectorAll('#usersTableBody tr');

            const filterRow = (row) => {
                const searchData = row.getAttribute('data-search') || '';
                if (searchData.includes(searchText)) {
                    row.style.display = '';
                } else {
                    row.style.display = 'none';
                }
            };

            userRows.forEach(filterRow);
        }

        function blockUser(userId) {
            sendAction(userId, 'block');
        }

        function unblockUser(userId) {
            sendAction(userId, 'unblock');
        }

        function changeRole(userId, newRole) {
            fetch('../api/manage_users_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({ user_id: userId, action: 'update_role', new_role: newRole })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        showToast(data.message, 'success');
                        loadUsers();
                    } else {
                        showToast(data.error || 'Failed to update role', 'error');
                        loadUsers();
                    }
                })
                .catch(error => {
                    console.error('Error:', error);
                    showToast('Failed to connect to server', 'error');
                });
        }

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

        function confirmDelete(userId) {
            deleteUserId = userId;
            document.getElementById('deleteModal').style.display = 'flex';
        }

        function confirmApprove(userId) {
            approveUserId = userId;
            document.getElementById('approveModal').style.display = 'flex';
        }

        document.getElementById('confirmApproveBtn').addEventListener('click', function () {
            if (approveUserId) {
                sendAction(approveUserId, 'approve');
                closeModal();
            }
        });

        function closeModal() {
            deleteUserId = null;
            approveUserId = null;
            document.getElementById('deleteModal').style.display = 'none';
            document.getElementById('approveModal').style.display = 'none';
        }

        function closeLargeModal(modalId) {
            document.getElementById(modalId).style.display = 'none';
        }

        // View/Edit User Functions
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
                        closeLargeModal('editUserModal');
                        loadUsers();
                    } else {
                        showToast(res.error, 'error');
                    }
                })
                .catch(err => showToast('Error updating user', 'error'));
        }

        // Window click to close modals
        window.onclick = function (event) {
            const deleteModal = document.getElementById('deleteModal');
            const viewModal = document.getElementById('viewUserModal');
            const editModal = document.getElementById('editUserModal');
            if (event.target == deleteModal) {
                deleteModal.style.display = 'none';
            }
            if (event.target == viewModal) {
                viewModal.style.display = 'none';
            }
            if (event.target == editModal) {
                editModal.style.display = 'none';
            }
            if (event.target == document.getElementById('createUserModal')) {
                document.getElementById('createUserModal').style.display = 'none';
            }
        }

        function openCreateUserModal() {
            document.getElementById('createUserModal').style.display = 'block';
            document.getElementById('createUserForm').reset();
            document.getElementById('create_idno').value = 'Generating...';
            fetch('../api/manage_users_api.php?action=get_next_idno&role=user')
                .then(r => r.json())
                .then(data => {
                    if (data.success && data.idno) {
                        document.getElementById('create_idno').value = data.idno;
                    }
                })
                .catch(() => {
                    document.getElementById('create_idno').value = `${new Date().getFullYear()}-0001`;
                });
        }

        function calculateAgeCreate() {
            const birthDate = new Date(document.getElementById('create_birthdate').value);
            const today = new Date();
            let age = today.getFullYear() - birthDate.getFullYear();
            const m = today.getMonth() - birthDate.getMonth();
            if (m < 0 || (m === 0 && today.getDate() < birthDate.getDate())) {
                age--;
            }
            document.getElementById('create_age').value = age;
        }

        function submitCreateUserForm() {
            const password = document.getElementById('create_password').value;
            const confirm = document.getElementById('create_confirm_password').value;

            if (password !== confirm) {
                showToast("Passwords do not match", "error");
                return;
            }

            const data = {
                action: 'create_user',
                role: 'user',
                firstname: document.getElementById('create_firstname').value,
                lastname: document.getElementById('create_lastname').value,
                middlename: document.getElementById('create_middlename').value,
                suffix: document.getElementById('create_suffix').value,
                username: document.getElementById('create_username').value,
                email: document.getElementById('create_email').value,
                password: password,
                idno: document.getElementById('create_idno').value,
                birthdate: document.getElementById('create_birthdate').value,
                age: document.getElementById('create_age').value,
                sex: document.getElementById('create_sex').value,

                // Address Info
                purokstreet: document.getElementById('create_purokstreet').value,
                brgy: document.getElementById('create_brgy').value,
                municipal: document.getElementById('create_municipal').value,
                province: document.getElementById('create_province').value,
                country: document.getElementById('create_country').value,
                zipcode: document.getElementById('create_zipcode').value,

                // Security Questions
                security_question1: document.getElementById('create_security_question1').value,
                security_answer1: document.getElementById('create_security_answer1').value,
                security_question2: document.getElementById('create_security_question2').value,
                security_answer2: document.getElementById('create_security_answer2').value,
                security_question3: document.getElementById('create_security_question3').value,
                security_answer3: document.getElementById('create_security_answer3').value
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
                        closeLargeModal('createUserModal');
                        loadUsers();
                    } else {
                        showToast(res.error, 'error');
                    }
                })
                .catch(err => showToast('Error creating user', 'error'));
        }

        document.getElementById('confirmDeleteBtn').addEventListener('click', function () {
            if (deleteUserId) {
                fetch('../api/manage_users_api.php', {
                    method: 'DELETE',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify({ user_id: deleteUserId })
                })
                    .then(response => response.json())
                    .then(data => {
                        closeModal();
                        if (data.success) {
                            showToast(data.message, 'success');
                            loadUsers();
                        } else {
                            showToast(data.error || 'Delete failed', 'error');
                        }
                    })
                    .catch(error => {
                        closeModal();
                        console.error('Error:', error);
                        showToast('Failed to connect to server', 'error');
                    });
            }
        });

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