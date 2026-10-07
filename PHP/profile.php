<?php
session_start();
require_once __DIR__ . '/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$role = $_SESSION['role'];

// Check if personal information is complete
require_once __DIR__ . '/includes/auth_guard.php';
$is_profile_complete = check_user_personal_info_complete($conn, $_SESSION['user_id']);
$_SESSION['profile_completed'] = $is_profile_complete ? 1 : 0;
if (!$is_profile_complete) {
    header("Location: complete_profile.php");
    exit();
}
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Pirate Profile - ONE PIECE</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        body {
            background-image:
                linear-gradient(rgba(10, 15, 25, 0.20), rgba(10, 15, 25, 0.35)),
                url('../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            min-height: 100vh;
            color: #ffffff;
            overflow: hidden;
        }

        .app-layout {
            height: 100vh;
            overflow: hidden;
        }

        .app-main {
            height: 100vh;
            overflow: hidden;
        }

        .app-main .main-content {
            height: calc(100vh - 0px);
            overflow: hidden;
            padding: 0.75rem 1.25rem !important;
        }

        .profile-container {
            display: grid;
            grid-template-columns: 280px 1fr;
            gap: 1.25rem;
            max-width: 1100px;
            margin: 0.5rem auto;
            align-items: start;
            height: calc(100vh - 2rem);
        }

        .profile-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-radius: 16px;
            padding: 1.25rem;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            position: relative;
        }

        .details-card {
            background: linear-gradient(145deg, rgba(15, 23, 42, 0.92) 0%, rgba(24, 34, 53, 0.90) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            border: 1px solid rgba(245, 158, 11, 0.4);
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.55), 0 0 35px rgba(245, 158, 11, 0.12);
            position: relative;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
            scrollbar-width: none;
            -ms-overflow-style: none;
        }

        .details-card::-webkit-scrollbar {
            display: none;
        }

        .profile-avatar {
            width: 80px;
            height: 80px;
            background: linear-gradient(135deg, #ef4444, #f59e0b);
            border-radius: 50%;
            margin: 0 auto 0.75rem;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 2.2rem;
            color: white;
            font-weight: 800;
            box-shadow: 0 8px 22px rgba(245, 158, 11, 0.4);
            border: 3px solid rgba(245, 158, 11, 0.45);
        }

        .profile-input {
            width: 100%;
            padding: 0.65rem 0.95rem;
            background: rgba(8, 12, 22, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            color: #ffffff !important;
            font-size: 0.92rem;
            font-weight: 600;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .profile-input::placeholder,
        .modal-form-input::placeholder {
            color: #94a3b8 !important;
            font-weight: 500;
        }

        .profile-input:focus {
            border-color: #fbbf24 !important;
            background: rgba(15, 23, 42, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.35) !important;
            color: #ffffff !important;
        }

        .profile-input:read-only {
            background: rgba(8, 12, 22, 0.55) !important;
            border-color: rgba(255, 255, 255, 0.18) !important;
            color: #f8fafc !important;
            cursor: default;
        }

        select.profile-select option {
            background: #0f172a !important;
            color: #ffffff !important;
            font-weight: 600;
            padding: 8px 12px;
        }

        .modal-form-input {
            width: 100%;
            padding: 0.65rem 0.95rem;
            background: rgba(8, 12, 22, 0.75);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.25);
            border-radius: 8px;
            color: #ffffff !important;
            font-size: 0.92rem;
            font-weight: 600;
            box-sizing: border-box;
            outline: none;
            transition: all 0.25s ease;
        }

        .modal-form-input:focus {
            border-color: #fbbf24 !important;
            background: rgba(15, 23, 42, 0.95) !important;
            box-shadow: 0 0 0 3px rgba(251, 191, 36, 0.35) !important;
            color: #ffffff !important;
        }

        /* Autofill High Contrast Fix */
        .profile-input:-webkit-autofill,
        .profile-input:-webkit-autofill:hover, 
        .profile-input:-webkit-autofill:focus, 
        .profile-input:-webkit-autofill:active,
        .modal-form-input:-webkit-autofill {
            -webkit-box-shadow: 0 0 0 1000px #090d16 inset !important;
            -webkit-text-fill-color: #ffffff !important;
            caret-color: #ffffff !important;
            transition: background-color 5000s ease-in-out 0s;
        }

        /* Real-time Field Error Messages */
        .error-msg {
            color: #f87171;
            font-size: 0.74rem;
            font-weight: 600;
            display: none;
            margin-top: 3px;
            line-height: 1.2;
            animation: fadeInMsg 0.2s ease;
        }

        @keyframes fadeInMsg {
            from { opacity: 0; transform: translateY(-2px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .input-error-highlight {
            border-color: #ef4444 !important;
            box-shadow: 0 0 0 3px rgba(239, 68, 68, 0.3) !important;
            background: rgba(239, 68, 68, 0.15) !important;
        }

        .profile-label {
            display: block;
            color: #fbbf24;
            font-size: 0.80rem;
            margin-bottom: 0.3rem;
            text-transform: uppercase;
            letter-spacing: 0.6px;
            font-weight: 700;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.8);
        }

        .info-group {
            background: rgba(8, 12, 22, 0.65);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 0.6rem 0.85rem;
            border-radius: 8px;
            margin-bottom: 0.4rem;
            border: 1px solid rgba(255, 255, 255, 0.18);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .info-group .profile-label {
            margin-bottom: 0;
            font-size: 0.76rem;
            color: #fbbf24;
            font-weight: 700;
        }

        .info-group p {
            margin: 0;
            font-weight: 800;
            color: #ffffff !important;
            font-size: 0.82rem;
        }

        .btn-edit,
        .btn-save {
            padding: 0.5rem 1.1rem;
            border-radius: 7px;
            cursor: pointer;
            transition: all 0.2s ease;
            font-weight: 700;
            font-size: 0.78rem;
            letter-spacing: 0.5px;
            text-transform: uppercase;
        }

        .btn-edit {
            background: rgba(245, 158, 11, 0.2);
            color: #fbbf24;
            border: 1px solid rgba(245, 158, 11, 0.5);
            backdrop-filter: blur(8px);
        }

        .btn-edit:hover {
            background: rgba(245, 158, 11, 0.35);
            color: #ffffff;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.4);
        }

        .btn-save {
            background: linear-gradient(135deg, #ff5500, #dc2626);
            color: white;
            border: none;
            display: none;
            box-shadow: 0 4px 15px rgba(255, 85, 0, 0.4);
        }

        .btn-save:hover {
            transform: translateY(-1px);
            background: linear-gradient(135deg, #ff661a, #eb4d00);
            box-shadow: 0 6px 20px rgba(255, 85, 0, 0.5);
        }

        .section-title {
            margin-bottom: 0.75rem;
            padding-bottom: 0.5rem;
            border-bottom: 1px solid rgba(245, 158, 11, 0.45);
            display: flex;
            justify-content: space-between;
            align-items: center;
        }

        .section-title h3 {
            font-size: 1.05rem;
            font-weight: 800;
            color: #fbbf24;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
            margin: 0;
            text-transform: uppercase;
            letter-spacing: 0.75px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: 1fr 1fr;
            gap: 0.65rem;
            margin-bottom: 0.75rem;
        }

        /* Confirmation Modal */
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
            overflow-y: auto;
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
            border-radius: 20px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.4), inset 0 0 0 1px rgba(255, 255, 255, 0.2);
            max-width: 440px;
            width: 100%;
            max-height: calc(100vh - 3rem);
            overflow-y: auto;
            margin: auto;
            padding: 2rem;
            text-align: center;
            color: #ffffff;
        }

        @media (max-width: 850px) {
            .profile-container {
                grid-template-columns: 1fr;
                max-width: 550px;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/includes/sidebar.php';
        render_sidebar('profile');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="container" style="max-width: 1200px; margin: 0 auto; padding: 0.25rem 0;">
                    <div class="profile-container">
                <!-- Left Sidebar: Basic Info Card -->
                <div class="profile-card" style="text-align: center;">
                    <div class="profile-avatar" id="avatarInitial">?</div>
                    <h2 id="displayFullName" style="color: #ffffff; margin-bottom: 0.15rem; font-size: 1.15rem; font-weight: 800; text-shadow: 0 2px 6px rgba(0,0,0,0.5);">Loading...</h2>
                    <p id="displayUsername" style="color: #fbbf24; margin-bottom: 1rem; font-weight: 800; font-size: 0.9rem; text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);">username</p>

                    <div style="text-align: left; width: 100%;">
                        <div class="info-group">
                            <span class="profile-label">ID Number</span>
                            <p id="displayIdno" style="font-family: monospace; color: #38bdf8; font-weight: 800;">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Role</span>
                            <p id="displayRole" style="text-transform: uppercase;">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Status</span>
                            <p id="displayStatus" style="text-transform: uppercase;">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Sex</span>
                            <p id="displaySex">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Birthdate</span>
                            <p id="displayBirthdate">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Age</span>
                            <p id="displayAge">----</p>
                        </div>

                        <div class="info-group">
                            <span class="profile-label">Security Questions</span>
                            <p id="displaySecurityStatus" style="font-size: 0.8rem; font-weight: 700;">----</p>
                        </div>
                    </div>
                </div>

                <!-- Right Main Content: Editable Personal Details Form -->
                <div class="details-card">
                    <?php if (isset($_GET['first_login'])): ?>
                    <div class="first-login-banner" style="background: linear-gradient(135deg, rgba(34, 197, 94, 0.12), rgba(16, 185, 129, 0.08)); border: 1.5px solid rgba(34, 197, 94, 0.4); border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 15px rgba(0,0,0,0.04);">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(34, 197, 94, 0.15); border: 1.5px solid rgba(34, 197, 94, 0.4); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#16a34a" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <polyline points="20 6 9 17 4 12"></polyline>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: #166534; font-size: 1.05rem; margin-bottom: 3px;">Password Updated Successfully!</div>
                            <div style="font-size: 0.88rem; color: #374151; line-height: 1.45;">Welcome! You have been redirected to your <strong>Personal Information</strong> page. Please review your details and set up your security questions below.</div>
                        </div>
                    </div>
                    <?php endif; ?>
                    
                    <?php if (!$is_profile_complete || isset($_GET['incomplete_profile'])): ?>
                    <div class="incomplete-profile-banner" style="background: linear-gradient(135deg, rgba(239, 68, 68, 0.12), rgba(185, 28, 28, 0.08)); border: 1.5px solid rgba(239, 68, 68, 0.35); border-radius: 12px; padding: 1.1rem 1.25rem; margin-bottom: 1.5rem; display: flex; align-items: center; gap: 14px; box-shadow: 0 4px 15px rgba(239, 68, 68, 0.08);">
                        <div style="width: 44px; height: 44px; border-radius: 50%; background: rgba(239, 68, 68, 0.15); border: 1.5px solid rgba(239, 68, 68, 0.4); display: flex; align-items: center; justify-content: center; flex-shrink: 0;">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="12" y1="8" x2="12" y2="12"></line>
                                <line x1="12" y1="16" x2="12.01" y2="16"></line>
                            </svg>
                        </div>
                        <div>
                            <div style="font-weight: 800; color: #991b1b; font-size: 1.05rem; margin-bottom: 3px;">Action Required: Complete Personal Information</div>
                            <div style="font-size: 0.88rem; color: #4b5563; line-height: 1.45;">Other system features and buttons are <strong>locked</strong>. Please fill out all required fields marked with an asterisk (<span style="color: #dc2626; font-weight: bold;">*</span>) including Name, Email, Birthdate, Sex, Address, and Security Questions, then click <strong>Save Changes</strong> to unlock your account.</div>
                        </div>
                    </div>
                    <?php endif; ?>

                    <form id="profileForm" onsubmit="promptSaveConfirmation(event)">
                        <div class="section-title">
                            <h3>Personal Information</h3>
                            <div>
                                <button type="button" class="btn-edit" id="editBtn" onclick="toggleEdit()">Edit Information</button>
                                <button type="submit" class="btn-save" id="saveBtn">Save Changes</button>
                            </div>
                        </div>

                        <div class="form-grid">
                            <div>
                                <span class="profile-label">First Name *</span>
                                <input type="text" class="profile-input" id="firstname" readonly required maxlength="25">
                                <small class="error-msg" id="firstname_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Middle Name</span>
                                <input type="text" class="profile-input" id="middlename" readonly maxlength="25">
                                <small class="error-msg" id="middlename_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Last Name *</span>
                                <input type="text" class="profile-input" id="lastname" readonly required maxlength="25">
                                <small class="error-msg" id="lastname_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Suffix</span>
                                <input type="text" class="profile-input" id="suffix" placeholder="e.g. Jr., Sr., III" readonly maxlength="10">
                                <small class="error-msg" id="suffix_error"></small>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <span class="profile-label">Email Address *</span>
                                <input type="email" class="profile-input" id="email" readonly required>
                                <small class="error-msg" id="email_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Birthdate *</span>
                                <input type="date" class="profile-input" id="birthdate" readonly required>
                                <small class="error-msg" id="birthdate_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Sex *</span>
                                <select id="sex" class="profile-input profile-select" disabled required>
                                    <option value="">Select Sex</option>
                                    <option value="Male">Male</option>
                                    <option value="Female">Female</option>
                                </select>
                                <small class="error-msg" id="sex_error"></small>
                            </div>
                            <div style="grid-column: 1 / -1;">
                                <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 0.35rem;">
                                    <span class="profile-label" style="margin-bottom: 0;">New Password <span style="color: #94a3b8; font-size: 0.72rem; font-weight: normal; text-transform: none;">(Optional - leave blank to keep unchanged)</span></span>
                                </div>
                                <div style="position: relative;">
                                    <input type="password" class="profile-input" id="newPassword" placeholder="Enter new password if changing" readonly style="padding-right: 2.75rem;" autocomplete="new-password">
                                    <button type="button" class="password-toggle-btn" onclick="togglePasswordVisibility('newPassword', this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                    </button>
                                </div>

                                <!-- Password Strength Bar -->
                                <div id="profilePasswordStrengthWrap" style="margin-top: 8px; display: none;">
                                    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 4px;">
                                        <span style="font-size: 0.73rem; color: #94a3b8;">Password Strength:</span>
                                        <span id="profileStrengthLabel" style="font-size: 0.75rem; font-weight: 700; color: #94a3b8;">Weak</span>
                                    </div>
                                    <div style="display: grid; grid-template-columns: repeat(4, 1fr); gap: 4px; height: 5px;">
                                        <div id="profileBar1" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                        <div id="profileBar2" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                        <div id="profileBar3" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                        <div id="profileBar4" style="height: 100%; background: rgba(255, 255, 255, 0.1); border-radius: 3px; transition: background 0.3s ease;"></div>
                                    </div>
                                    <small style="color: #94a3b8; font-size: 0.72rem; display: block; margin-top: 5px;">Must be at least 8 characters with uppercase, number, and special character.</small>
                                </div>
                                <small class="error-msg" id="newPassword_error"></small>
                            </div>
                        </div>

                        <div class="section-title" style="margin-top: 0.5rem;">
                            <h3>Address Information</h3>
                        </div>

                        <div class="form-grid">
                            <div>
                                <span class="profile-label">Purok / Street *</span>
                                <input type="text" class="profile-input" id="purokstreet" readonly required maxlength="25">
                                <small class="error-msg" id="purokstreet_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Barangay *</span>
                                <input type="text" class="profile-input" id="brgy" readonly required maxlength="25">
                                <small class="error-msg" id="brgy_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Municipality / City *</span>
                                <input type="text" class="profile-input" id="municipal" readonly required maxlength="25">
                                <small class="error-msg" id="municipal_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Province *</span>
                                <input type="text" class="profile-input" id="province" readonly required maxlength="25">
                                <small class="error-msg" id="province_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Country *</span>
                                <input type="text" class="profile-input" id="country" readonly required maxlength="40">
                                <small class="error-msg" id="country_error"></small>
                            </div>
                            <div>
                                <span class="profile-label">Zip Code *</span>
                                <input type="text" class="profile-input" id="zipcode" readonly required maxlength="6">
                                <small class="error-msg" id="zipcode_error"></small>
                            </div>
                        </div>

                        <div class="section-title" style="margin-top: 0.5rem; margin-bottom: 0.35rem;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 0.5rem;">
                                <h3>Security Questions</h3>
                                <button type="button" onclick="openResetSecurityFlow()" style="padding: 0.45rem 1rem; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; font-weight: 700; font-size: 0.8rem; cursor: pointer; display: inline-flex; align-items: center; gap: 0.4rem; box-shadow: 0 3px 12px rgba(59,130,246,0.35); transition: all 0.2s ease;" onmouseover="this.style.transform='translateY(-1px)';this.style.boxShadow='0 5px 18px rgba(59,130,246,0.5)'" onmouseout="this.style.transform='';this.style.boxShadow='0 3px 12px rgba(59,130,246,0.35)'">
                                    <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="M21 2l-2 2m-7.61 7.61a5.5 5.5 0 1 1-7.778 7.778 5.5 5.5 0 0 1 7.777-7.777zm0 0L15.5 7.5m0 0l3 3L22 7l-3-3m-3.5 3.5L19 4"/></svg>
                                    Reset Security Questions
                                </button>
                            </div>
                        </div>
                        <div style="background: rgba(59,130,246,0.08); border: 1px solid rgba(59,130,246,0.25); border-radius: 7px; padding: 0.5rem 0.75rem; margin-bottom: 0.65rem; font-size: 0.78rem; color: #93c5fd; display: flex; align-items: flex-start; gap: 0.5rem;">
                            <span style="font-size: 1rem; margin-top: 0.05rem;">💡</span>
                            <span><strong>Forgot your security answers?</strong> Click <em>Reset Security Questions</em> above to verify your identity with your current password, then choose new questions and answers. No need to remember your old answers.</span>
                        </div>

                        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 0.65rem;">
                            <div style="background: rgba(255, 255, 255, 0.10); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 0.85rem; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.22); cursor: pointer;" onclick="if(!isEditing) enableEditSecurity('security_question1')">
                                <span class="profile-label">Question 1 <span style="color: #ef4444;">*</span></span>
                                <select id="security_question1" class="profile-input profile-select" disabled style="margin-top: 0.25rem;" onclick="enableEditSecurity('security_question1')">
                                    <option value="">Select Question 1</option>
                                    <option value="What is your mother's maiden name?">Mother's maiden name?</option>
                                    <option value="What was the name of your first pet?">First pet's name?</option>
                                    <option value="What is the name of the city where you were born?">City born in?</option>
                                    <option value="What is your favorite food?">Favorite food?</option>
                                </select>
                                <div style="margin-top: 0.4rem;">
                                    <span class="profile-label">Answer 1</span>
                                    <div style="position: relative;">
                                        <input type="password" class="profile-input" id="security_answer1" placeholder="Leave blank to keep current" readonly onclick="enableEditSecurity('security_answer1')" style="padding-right: 2.5rem;">
                                        <button type="button" onclick="togglePasswordVisibility('security_answer1', this)" aria-label="Toggle visibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <small class="error-msg" id="security_answer1_error"></small>
                            </div>

                            <div style="background: rgba(255, 255, 255, 0.10); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 0.85rem; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.22); cursor: pointer;" onclick="if(!isEditing) enableEditSecurity('security_question2')">
                                <span class="profile-label">Question 2 <span style="color: #ef4444;">*</span></span>
                                <select id="security_question2" class="profile-input profile-select" disabled style="margin-top: 0.25rem;" onclick="enableEditSecurity('security_question2')">
                                    <option value="">Select Question 2</option>
                                    <option value="What is the name of your favorite teacher?">Favorite teacher?</option>
                                    <option value="What is your favorite movie?">Favorite movie?</option>
                                    <option value="What is the make of your first car?">First car make?</option>
                                    <option value="What is your lucky number?">Lucky number?</option>
                                </select>
                                <div style="margin-top: 0.4rem;">
                                    <span class="profile-label">Answer 2</span>
                                    <div style="position: relative;">
                                        <input type="password" class="profile-input" id="security_answer2" placeholder="Leave blank to keep current" readonly onclick="enableEditSecurity('security_answer2')" style="padding-right: 2.5rem;">
                                        <button type="button" onclick="togglePasswordVisibility('security_answer2', this)" aria-label="Toggle visibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <small class="error-msg" id="security_answer2_error"></small>
                            </div>

                            <div style="background: rgba(255, 255, 255, 0.10); backdrop-filter: blur(12px); -webkit-backdrop-filter: blur(12px); padding: 0.85rem; border-radius: 10px; border: 1px solid rgba(255, 255, 255, 0.22); cursor: pointer;" onclick="if(!isEditing) enableEditSecurity('security_question3')">
                                <span class="profile-label">Question 3 <span style="color: #ef4444;">*</span></span>
                                <select id="security_question3" class="profile-input profile-select" disabled style="margin-top: 0.25rem;" onclick="enableEditSecurity('security_question3')">
                                    <option value="">Select Question 3</option>
                                    <option value="What is your favorite color?">Favorite color?</option>
                                    <option value="What is the name of your best friend?">Best friend's name?</option>
                                    <option value="What is your dream job?">Dream job?</option>
                                    <option value="What is your favorite sports team?">Favorite sports team?</option>
                                </select>
                                <div style="margin-top: 0.4rem;">
                                    <span class="profile-label">Answer 3</span>
                                    <div style="position: relative;">
                                        <input type="password" class="profile-input" id="security_answer3" placeholder="Leave blank to keep current" readonly onclick="enableEditSecurity('security_answer3')" style="padding-right: 2.5rem;">
                                        <button type="button" onclick="togglePasswordVisibility('security_answer3', this)" aria-label="Toggle visibility" style="position: absolute; right: 10px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                                            <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                                        </button>
                                    </div>
                                </div>
                                <small class="error-msg" id="security_answer3_error"></small>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </main>

    <!-- Current Password Verification Modal -->
    <div class="app-modal-overlay" id="passwordModal">
        <div class="app-modal-card">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">&#128274;</div>
            <h3 style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.25rem;">Confirm Profile Updates</h3>
            <p style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 1.25rem;">Please enter your current password or passkey to confirm and save your personal information.</p>

            <form onsubmit="submitProfileWithPassword(event)">
                <div style="text-align: left; margin-bottom: 1.25rem;">
                    <label class="profile-label">Current Password / Passkey <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="confirmCurrentPassword" class="modal-form-input" style="padding-right: 2.5rem;" required placeholder="Enter current password or passkey">
                        <button type="button" onclick="togglePasswordVisibility('confirmCurrentPassword', this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.75rem;">
                    <button type="button" class="btn-edit" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff;" onclick="closePasswordModal()">Cancel</button>
                    <button type="submit" class="btn-save" style="display: inline-block;">Verify & Save</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Security Questions: Step 1 - Password Verification Modal -->
    <div class="app-modal-overlay" id="resetSQPasswordModal">
        <div class="app-modal-card" style="max-width: 440px; text-align: center;">
            <div style="font-size: 2.5rem; margin-bottom: 0.5rem;">🔐</div>
            <h3 style="color: #ffffff; margin-bottom: 0.5rem; font-size: 1.25rem;">Verify Your Identity</h3>
            <p style="color: #cbd5e1; font-size: 0.88rem; margin-bottom: 1.25rem;">Before resetting your security questions, please confirm your identity by entering your current login password or passkey.</p>

            <form onsubmit="verifySQPassword(event)">
                <div style="text-align: left; margin-bottom: 1.25rem;">
                    <label class="profile-label">Current Password / Passkey <span style="color: #ef4444;">*</span></label>
                    <div style="position: relative;">
                        <input type="password" id="sqVerifyPassword" class="modal-form-input" style="padding-right: 2.5rem;" required placeholder="Enter current password or passkey">
                        <button type="button" onclick="togglePasswordVisibility('sqVerifyPassword', this)" aria-label="Toggle password visibility" style="position: absolute; right: 12px; top: 50%; transform: translateY(-50%); background: none; border: none; color: #94a3b8; cursor: pointer; padding: 0; display: flex; align-items: center;">
                            <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>
                        </button>
                    </div>
                </div>

                <div style="display: flex; justify-content: center; gap: 0.75rem;">
                    <button type="button" class="btn-edit" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff;" onclick="closeModal('resetSQPasswordModal')">Cancel</button>
                    <button type="submit" style="padding: 0.55rem 1.4rem; border-radius: 8px; background: linear-gradient(135deg, #ef4444, #f59e0b); color: #fff; border: 1px solid rgba(254, 215, 170, 0.35); font-weight: 700; font-size: 0.9rem; cursor: pointer; box-shadow: 0 4px 14px rgba(245, 158, 11, 0.35);">Verify Identity</button>
                </div>
            </form>
        </div>
    </div>

    <!-- Reset Security Questions: Step 2 - Choose New Questions Modal -->
    <div class="app-modal-overlay" id="resetSQFormModal">
        <div class="app-modal-card" style="max-width: 560px; max-height: calc(100vh - 3rem); overflow-y: auto; text-align: left;">
            <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem; padding-bottom: 0.75rem; border-bottom: 1px solid rgba(255,255,255,0.08);">
                <div>
                    <h3 style="color: #ffffff; font-size: 1.2rem; margin: 0;">Choose New Security Questions</h3>
                    <p style="color: #94a3b8; font-size: 0.78rem; margin: 0.2rem 0 0;">Identity verified ✅ — Select 3 new questions and enter your answers below.</p>
                </div>
                <button style="background: transparent; border: none; color: #94a3b8; font-size: 1.5rem; cursor: pointer; line-height: 1;" onclick="closeResetSQModal()">&times;</button>
            </div>

            <form id="resetSQForm" autocomplete="off" onsubmit="submitResetSecurityQuestions(event)">
                <div style="display: flex; flex-direction: column; gap: 1rem;">
                    <!-- Question 1 -->
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #cbd5e1; margin-bottom: 0.35rem;">Question 1 <span style="color: #ef4444;">*</span></label>
                        <select id="resetSQ1" class="modal-form-input profile-select" style="margin-top: 0.15rem;" required autocomplete="off">
                            <option value="" selected disabled>Select Question 1</option>
                            <option value="What is your mother's maiden name?">Mother's maiden name?</option>
                            <option value="What was the name of your first pet?">First pet's name?</option>
                            <option value="What is the name of the city where you were born?">City born in?</option>
                            <option value="What is your favorite food?">Favorite food?</option>
                        </select>
                        <input type="password" id="resetSA1" class="modal-form-input" placeholder="Enter new answer" required autocomplete="new-password" style="margin-top: 0.5rem;">
                    </div>

                    <!-- Question 2 -->
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #cbd5e1; margin-bottom: 0.35rem;">Question 2 <span style="color: #ef4444;">*</span></label>
                        <select id="resetSQ2" class="modal-form-input profile-select" style="margin-top: 0.15rem;" required autocomplete="off">
                            <option value="" selected disabled>Select Question 2</option>
                            <option value="What is the name of your favorite teacher?">Favorite teacher?</option>
                            <option value="What is your favorite movie?">Favorite movie?</option>
                            <option value="What is the make of your first car?">First car make?</option>
                            <option value="What is your lucky number?">Lucky number?</option>
                        </select>
                        <input type="password" id="resetSA2" class="modal-form-input" placeholder="Enter new answer" required autocomplete="new-password" style="margin-top: 0.5rem;">
                    </div>

                    <!-- Question 3 -->
                    <div style="background: rgba(255,255,255,0.02); padding: 1rem; border-radius: 10px; border: 1px solid rgba(255,255,255,0.06);">
                        <label style="display: block; font-size: 0.75rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.5px; color: #cbd5e1; margin-bottom: 0.35rem;">Question 3 <span style="color: #ef4444;">*</span></label>
                        <select id="resetSQ3" class="modal-form-input profile-select" style="margin-top: 0.15rem;" required autocomplete="off">
                            <option value="" selected disabled>Select Question 3</option>
                            <option value="What is your favorite color?">Favorite color?</option>
                            <option value="What is the name of your best friend?">Best friend's name?</option>
                            <option value="What is your dream job?">Dream job?</option>
                            <option value="What is your favorite sports team?">Favorite sports team?</option>
                        </select>
                        <input type="password" id="resetSA3" class="modal-form-input" placeholder="Enter new answer" required autocomplete="new-password" style="margin-top: 0.5rem;">
                    </div>
                </div>

                <div style="display: flex; align-items: center; gap: 0.5rem; margin-top: 1rem; font-size: 0.82rem; color: #94a3b8;">
                    <input type="checkbox" id="toggleResetAnswers" style="accent-color: #3b82f6; width: 15px; height: 15px;">
                    <label for="toggleResetAnswers" style="cursor: pointer;">Show answers</label>
                </div>

                <div style="display: flex; justify-content: flex-end; gap: 0.75rem; margin-top: 1.25rem;">
                    <button type="button" class="btn-edit" style="background: rgba(255,255,255,0.1); border-color: rgba(255,255,255,0.2); color: #fff;" onclick="closeResetSQModal()">Cancel</button>
                    <button type="submit" style="padding: 0.6rem 1.4rem; border-radius: 8px; background: linear-gradient(135deg, #3b82f6, #2563eb); color: #fff; border: none; font-weight: 700; font-size: 0.9rem; cursor: pointer; box-shadow: 0 3px 12px rgba(59,130,246,0.35); display: inline-flex; align-items: center; gap: 0.4rem;">
                        <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="20 6 9 17 4 12"/></svg>
                        Save New Security Questions
                    </button>
                </div>
            </form>
        </div>
    </div>

    <!-- Toast Notification -->
    <div id="toast"
        style="display: none; position: fixed; bottom: 20px; right: 20px; background: #22c55e; color: white; padding: 1rem 1.75rem; border-radius: 10px; font-weight: 700; box-shadow: 0 10px 25px rgba(0,0,0,0.5); z-index: 3000;">
        Profile updated!
    </div>

    <footer style="display: none;">
        <p>&copy; 2024 ONE PIECE Pirate Fleet. All rights reserved.</p>
    </footer>
</div>
</div>

    <script>
        let isEditing = false;
        let userData = {};

        document.addEventListener('DOMContentLoaded', () => {
            loadProfile();
            clearResetSQFields();

            // Setup live input listeners for profile form
            const fnInput = document.getElementById("firstname");
            if (fnInput) {
                fnInput.addEventListener("input", () => checkProfileFirstname(false));
                fnInput.addEventListener("blur", () => checkProfileFirstname(true));
            }

            const mnInput = document.getElementById("middlename");
            if (mnInput) {
                mnInput.addEventListener("input", () => checkProfileMiddlename());
            }

            const lnInput = document.getElementById("lastname");
            if (lnInput) {
                lnInput.addEventListener("input", () => checkProfileLastname(false));
                lnInput.addEventListener("blur", () => checkProfileLastname(true));
            }

            const sufInput = document.getElementById("suffix");
            if (sufInput) {
                sufInput.addEventListener("input", () => checkProfileSuffix());
            }

            const emInput = document.getElementById("email");
            if (emInput) {
                emInput.addEventListener("input", () => checkProfileEmail(false));
                emInput.addEventListener("blur", () => checkProfileEmail(true));
            }

            const bdInput = document.getElementById("birthdate");
            if (bdInput) {
                bdInput.addEventListener("input", () => checkProfileBirthdate(false));
                bdInput.addEventListener("change", () => checkProfileBirthdate(false));
            }

            const sexSelect = document.getElementById("sex");
            if (sexSelect) {
                sexSelect.addEventListener("change", () => checkProfileSex(false));
            }

            const purokInput = document.getElementById("purokstreet");
            if (purokInput) {
                purokInput.addEventListener("input", () => checkProfilePurok(false));
                purokInput.addEventListener("blur", () => checkProfilePurok(true));
            }

            const brgyInput = document.getElementById("brgy");
            if (brgyInput) {
                brgyInput.addEventListener("input", () => checkProfileBrgy(false));
                brgyInput.addEventListener("blur", () => checkProfileBrgy(true));
            }

            const munInput = document.getElementById("municipal");
            if (munInput) {
                munInput.addEventListener("input", () => checkProfileMunicipal(false));
                munInput.addEventListener("blur", () => checkProfileMunicipal(true));
            }

            const provInput = document.getElementById("province");
            if (provInput) {
                provInput.addEventListener("input", () => checkProfileProvince(false));
                provInput.addEventListener("blur", () => checkProfileProvince(true));
            }

            const countryInput = document.getElementById("country");
            if (countryInput) {
                countryInput.addEventListener("input", () => checkProfileCountry(false));
                countryInput.addEventListener("blur", () => checkProfileCountry(true));
            }

            const zipInput = document.getElementById("zipcode");
            if (zipInput) {
                zipInput.addEventListener("input", () => checkProfileZipcode(false));
                zipInput.addEventListener("blur", () => checkProfileZipcode(true));
            }

            const newPwdInput = document.getElementById("newPassword");
            if (newPwdInput) {
                newPwdInput.addEventListener("input", () => checkProfilePasswordStrength());
                newPwdInput.addEventListener("blur", () => checkProfilePassword(true));
            }

            for (let i = 1; i <= 3; i++) {
                const q = document.getElementById("security_question" + i);
                const a = document.getElementById("security_answer" + i);
                if (q) q.addEventListener("change", () => checkProfileSecurityQuestions(false));
                if (a) a.addEventListener("input", () => checkProfileSecurityQuestions(false));
            }
        });

        function loadProfile() {
            fetch('api/profile_api.php')
                .then(r => r.json())
                .then(data => {
                    if (data.success) {
                        userData = data.user;

                        document.getElementById('displayFullName').textContent = `${userData.firstname || ''} ${userData.lastname || ''}`.trim() || userData.username;
                        document.getElementById('displayUsername').textContent = userData.username;
                        document.getElementById('displayIdno').textContent = userData.idno || 'N/A';
                        document.getElementById('displayRole').textContent = userData.role || 'USER';
                        document.getElementById('displayStatus').textContent = userData.status || 'ACTIVE';
                        document.getElementById('displaySex').textContent = userData.sex || 'N/A';
                        document.getElementById('displayBirthdate').textContent = userData.birthdate || 'N/A';
                        document.getElementById('displayAge').textContent = userData.age || 'N/A';

                        if (userData.firstname) {
                            document.getElementById('avatarInitial').textContent = userData.firstname.charAt(0).toUpperCase();
                        } else if (userData.username) {
                            document.getElementById('avatarInitial').textContent = userData.username.charAt(0).toUpperCase();
                        }

                        // Form fields
                        document.getElementById('firstname').value = userData.firstname || '';
                        document.getElementById('middlename').value = userData.middlename || '';
                        document.getElementById('lastname').value = userData.lastname || '';
                        document.getElementById('suffix').value = userData.suffix || '';
                        document.getElementById('email').value = userData.email || '';
                        document.getElementById('birthdate').value = userData.birthdate || '';
                        document.getElementById('sex').value = userData.sex || '';
                        document.getElementById('purokstreet').value = userData.purokstreet || '';
                        document.getElementById('brgy').value = userData.brgy || '';
                        document.getElementById('municipal').value = userData.municipal || '';
                        document.getElementById('province').value = userData.province || '';
                        document.getElementById('country').value = userData.country || 'Philippines';
                        document.getElementById('zipcode').value = userData.zipcode || '';

                        // Reset optional new password field
                        const newPwdEl = document.getElementById('newPassword');
                        if (newPwdEl) {
                            newPwdEl.value = '';
                            newPwdEl.type = 'password';
                        }
                        const pWrap = document.getElementById('profilePasswordStrengthWrap');
                        if (pWrap) pWrap.style.display = 'none';
                        clearSingleProfileFieldError('newPassword');

                        // Security Questions
                        const q1 = userData.security_question1 || '';
                        const q2 = userData.security_question2 || '';
                        const q3 = userData.security_question3 || '';
                        document.getElementById('security_question1').value = q1;
                        document.getElementById('security_question2').value = q2;
                        document.getElementById('security_question3').value = q3;
                        document.getElementById('security_answer1').value = '';
                        document.getElementById('security_answer2').value = '';
                        document.getElementById('security_answer3').value = '';

                        let qCount = 0;
                        if (q1) qCount++;
                        if (q2) qCount++;
                        if (q3) qCount++;
                        const statusEl = document.getElementById('displaySecurityStatus');
                        if (qCount === 3) {
                            statusEl.textContent = '● Configured (3/3)';
                            statusEl.style.color = '#4ade80';
                        } else if (qCount > 0) {
                            statusEl.textContent = `● Partial (${qCount}/3)`;
                            statusEl.style.color = '#facc15';
                        } else {
                            statusEl.textContent = '○ Not Set (0/3)';
                            statusEl.style.color = '#f87171';
                        }

                        // If user has incomplete profile or is arriving from first login, automatically open edit mode
                        const isProfileIncomplete = <?php echo !$is_profile_complete ? 'true' : 'false'; ?>;
                        if ((window.location.search.includes('first_login=1') || window.location.search.includes('incomplete_profile=1') || isProfileIncomplete) && !isEditing) {
                            toggleEdit();
                            // Smooth scroll to Personal Information form
                            const pForm = document.getElementById('profileForm');
                            if (pForm) pForm.scrollIntoView({ behavior: 'smooth' });
                        }
                    } else {
                        alert(data.error || 'Failed to load personal information');
                    }
                })
                .catch(err => console.error(err));
        }

        function setProfileFieldError(inputId, message) {
            const errorEl = document.getElementById(inputId + "_error");
            const inputEl = document.getElementById(inputId);
            if (errorEl) {
                errorEl.textContent = message;
                errorEl.style.display = "block";
            }
            if (inputEl) {
                inputEl.classList.add("input-error-highlight");
            }
        }

        function clearSingleProfileFieldError(inputId) {
            const errorEl = document.getElementById(inputId + "_error");
            const inputEl = document.getElementById(inputId);
            if (errorEl) {
                errorEl.textContent = "";
                errorEl.style.display = "none";
            }
            if (inputEl) {
                inputEl.classList.remove("input-error-highlight");
            }
        }

        function clearAllProfileErrors() {
            document.querySelectorAll('.error-msg').forEach(el => {
                el.textContent = "";
                el.style.display = "none";
            });
            document.querySelectorAll('.input-error-highlight').forEach(el => {
                el.classList.remove('input-error-highlight');
            });
        }

        function scanProfileNameField(val, fieldLabel) {
            if (val === "") return "";
            if (val.charAt(0) === ' ') {
                return "The first character of " + fieldLabel.toLowerCase() + " should not be a space.";
            }
            for (let i = 0; i < val.length; i++) {
                const char = val.charAt(i);
                if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
                    return fieldLabel + " cannot contain double space.";
                }
                if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
                    if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                        return fieldLabel + " cannot contain three consecutive repeated letters.";
                    }
                }
                if (/[0-9]/.test(char)) {
                    return fieldLabel + " cannot contain numbers.";
                }
                if (/[^a-zA-Z\s]/.test(char)) {
                    return fieldLabel + " cannot contain special characters.";
                }
                if (i === 0) {
                    if (/[a-z]/.test(char)) {
                        return "The first letter of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else if (val.charAt(i - 1) === ' ') {
                    if (/[a-z]/.test(char)) {
                        return "The second word of " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                } else {
                    if (/[A-Z]/.test(char)) {
                        return "Only the first letter of each word in " + fieldLabel.toLowerCase() + " should be capitalized.";
                    }
                }
            }
            if (val.length < 3) {
                return fieldLabel + " must be at least 3 characters.";
            }
            if (val.length > 25) {
                return fieldLabel + " should not exceed 25 characters.";
            }
            return "";
        }

        function checkProfileFirstname(showEmpty) {
            const el = document.getElementById("firstname");
            if (!el) return true;
            const val = el.value;
            if (val.trim() === "") {
                if (showEmpty) {
                    setProfileFieldError("firstname", "First name is required.");
                    return false;
                }
                clearSingleProfileFieldError("firstname");
                return false;
            }
            const err = scanProfileNameField(val, "First Name");
            if (err) {
                setProfileFieldError("firstname", err);
                return false;
            }
            clearSingleProfileFieldError("firstname");
            return true;
        }

        function checkProfileMiddlename() {
            const el = document.getElementById("middlename");
            if (!el) return true;
            const val = el.value;
            if (val.trim() === "") {
                clearSingleProfileFieldError("middlename");
                return true;
            }
            if (val.charAt(0) === ' ') {
                setProfileFieldError("middlename", "Middle name should not start with a space.");
                return false;
            }
            for (let i = 0; i < val.length; i++) {
                const char = val.charAt(i);
                if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
                    setProfileFieldError("middlename", "Middle name cannot contain double space.");
                    return false;
                }
                if (/[0-9]/.test(char)) {
                    setProfileFieldError("middlename", "Middle name cannot contain numbers.");
                    return false;
                }
                if (/[^a-zA-Z\s]/.test(char)) {
                    setProfileFieldError("middlename", "Middle name cannot contain special characters.");
                    return false;
                }
            }
            if (val.length > 25) {
                setProfileFieldError("middlename", "Middle name cannot exceed 25 characters.");
                return false;
            }
            clearSingleProfileFieldError("middlename");
            return true;
        }

        function checkProfileLastname(showEmpty) {
            const el = document.getElementById("lastname");
            if (!el) return true;
            const val = el.value;
            if (val.trim() === "") {
                if (showEmpty) {
                    setProfileFieldError("lastname", "Last name is required.");
                    return false;
                }
                clearSingleProfileFieldError("lastname");
                return false;
            }
            const err = scanProfileNameField(val, "Last Name");
            if (err) {
                setProfileFieldError("lastname", err);
                return false;
            }
            clearSingleProfileFieldError("lastname");
            return true;
        }

        function checkProfileSuffix() {
            const el = document.getElementById("suffix");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                clearSingleProfileFieldError("suffix");
                return true;
            }
            if (val.length > 10) {
                setProfileFieldError("suffix", "Suffix cannot exceed 10 characters.");
                return false;
            }
            if (!/^[a-zA-Z\s.]+$/.test(val)) {
                setProfileFieldError("suffix", "Suffix can only contain letters, spaces, and periods.");
                return false;
            }
            clearSingleProfileFieldError("suffix");
            return true;
        }

        function checkProfileEmail(showEmpty) {
            const el = document.getElementById("email");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("email", "Email address is required.");
                    return false;
                }
                clearSingleProfileFieldError("email");
                return false;
            }
            const emailRegex = /^[^\s@]+@[^\s@]+\.[^\s@]+$/;
            if (!emailRegex.test(val)) {
                setProfileFieldError("email", "Please enter a valid email address.");
                return false;
            }
            const parts = val.toLowerCase().split('@');
            if (parts.length === 2 && parts[1]) {
                const domain = parts[1];
                const isGmail = (domain === 'gmail.com');
                const isUniversity = /(^|\.)[a-z0-9-]+\.edu\.ph$/.test(domain) || /(^|\.)[a-z0-9-]+\.edu$/.test(domain);

                if (!isGmail && !isUniversity) {
                    setProfileFieldError("email", "Only Gmail (@gmail.com) and university email (@...edu.ph / @...edu) are allowed.");
                    return false;
                }
            }
            clearSingleProfileFieldError("email");
            return true;
        }

        function checkProfileBirthdate(showEmpty) {
            const el = document.getElementById("birthdate");
            const ageDisplay = document.getElementById("displayAge");
            if (!el) return true;
            const val = el.value;
            if (!val) {
                if (showEmpty) {
                    setProfileFieldError("birthdate", "Birthdate is required.");
                    return false;
                }
                clearSingleProfileFieldError("birthdate");
                return false;
            }
            const bd = new Date(val);
            if (isNaN(bd.getTime())) {
                setProfileFieldError("birthdate", "Invalid birthdate format.");
                return false;
            }
            const today = new Date();
            let age = today.getFullYear() - bd.getFullYear();
            if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) {
                age--;
            }
            if (ageDisplay) {
                ageDisplay.textContent = age >= 0 ? age : 'N/A';
            }
            if (age < 18) {
                setProfileFieldError("birthdate", "You must be at least 18 years old.");
                return false;
            }
            if (age > 120) {
                setProfileFieldError("birthdate", "Please enter a valid birthdate.");
                return false;
            }
            clearSingleProfileFieldError("birthdate");
            return true;
        }

        function checkProfileSex(showEmpty) {
            const el = document.getElementById("sex");
            if (!el) return true;
            const val = el.value;
            if (val !== "Male" && val !== "Female") {
                if (showEmpty) {
                    setProfileFieldError("sex", "Please select a valid sex (Male or Female).");
                    return false;
                }
                return false;
            }
            clearSingleProfileFieldError("sex");
            return true;
        }

        function scanProfileAddressField(val, fieldLabel, minLen, maxLen) {
            if (val === "") return "";
            if (val.charAt(0) === ' ') {
                return fieldLabel + " should not start with a space.";
            }
            for (let i = 0; i < val.length; i++) {
                const char = val.charAt(i);
                if (char === ' ' && i + 1 < val.length && val.charAt(i + 1) === ' ') {
                    return fieldLabel + " cannot contain double space.";
                }
                if (i + 2 < val.length && /[a-zA-Z]/.test(char)) {
                    if (char.toLowerCase() === val.charAt(i + 1).toLowerCase() && char.toLowerCase() === val.charAt(i + 2).toLowerCase()) {
                        return fieldLabel + " cannot contain three consecutive repeated letters.";
                    }
                }
                if (fieldLabel === "Barangay") {
                    if (/[^a-zA-Z0-9\s-]/.test(char)) {
                        return "Barangay cannot contain special characters except hyphens.";
                    }
                } else {
                    if (/[0-9]/.test(char)) {
                        return fieldLabel + " cannot contain numbers.";
                    }
                    if (/[^a-zA-Z\s]/.test(char)) {
                        return fieldLabel + " cannot contain special characters.";
                    }
                }
            }
            if (val.length < minLen) {
                return fieldLabel + " must be at least " + minLen + " characters.";
            }
            if (val.length > maxLen) {
                return fieldLabel + " should not exceed " + maxLen + " characters.";
            }
            return "";
        }

        function checkProfilePurok(showEmpty) {
            const el = document.getElementById("purokstreet");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("purokstreet", "Purok/Street is required.");
                    return false;
                }
                clearSingleProfileFieldError("purokstreet");
                return false;
            }
            if (val.length > 25) {
                setProfileFieldError("purokstreet", "Purok/Street should not exceed 25 characters.");
                return false;
            }
            if (!/^[a-zA-Z0-9\s\-]+$/.test(val)) {
                setProfileFieldError("purokstreet", "Purok/Street can only contain letters, numbers, spaces, and hyphens.");
                return false;
            }
            clearSingleProfileFieldError("purokstreet");
            return true;
        }

        function checkProfileBrgy(showEmpty) {
            const el = document.getElementById("brgy");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("brgy", "Barangay is required.");
                    return false;
                }
                clearSingleProfileFieldError("brgy");
                return false;
            }
            const err = scanProfileAddressField(val, "Barangay", 3, 25);
            if (err) {
                setProfileFieldError("brgy", err);
                return false;
            }
            clearSingleProfileFieldError("brgy");
            return true;
        }

        function checkProfileMunicipal(showEmpty) {
            const el = document.getElementById("municipal");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("municipal", "Municipality/City is required.");
                    return false;
                }
                clearSingleProfileFieldError("municipal");
                return false;
            }
            const err = scanProfileAddressField(val, "Municipality", 3, 25);
            if (err) {
                setProfileFieldError("municipal", err);
                return false;
            }
            clearSingleProfileFieldError("municipal");
            return true;
        }

        function checkProfileProvince(showEmpty) {
            const el = document.getElementById("province");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("province", "Province is required.");
                    return false;
                }
                clearSingleProfileFieldError("province");
                return false;
            }
            const err = scanProfileAddressField(val, "Province", 3, 25);
            if (err) {
                setProfileFieldError("province", err);
                return false;
            }
            clearSingleProfileFieldError("province");
            return true;
        }

        function checkProfileCountry(showEmpty) {
            const el = document.getElementById("country");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("country", "Country is required.");
                    return false;
                }
                clearSingleProfileFieldError("country");
                return false;
            }
            const err = scanProfileAddressField(val, "Country", 4, 40);
            if (err) {
                setProfileFieldError("country", err);
                return false;
            }
            clearSingleProfileFieldError("country");
            return true;
        }

        function checkProfileZipcode(showEmpty) {
            const el = document.getElementById("zipcode");
            if (!el) return true;
            const val = el.value.trim();
            if (val === "") {
                if (showEmpty) {
                    setProfileFieldError("zipcode", "Zip Code is required.");
                    return false;
                }
                clearSingleProfileFieldError("zipcode");
                return false;
            }
            if (!/^[0-9]{4,6}$/.test(val)) {
                setProfileFieldError("zipcode", "Zip Code must be 4 to 6 numeric digits.");
                return false;
            }
            clearSingleProfileFieldError("zipcode");
            return true;
        }

        function checkProfileSecurityQuestions(showEmpty) {
            const q1 = document.getElementById("security_question1").value.trim();
            const q2 = document.getElementById("security_question2").value.trim();
            const q3 = document.getElementById("security_question3").value.trim();
            const a1 = document.getElementById("security_answer1").value.trim();
            const a2 = document.getElementById("security_answer2").value.trim();
            const a3 = document.getElementById("security_answer3").value.trim();

            let valid = true;

            // Check duplicate questions
            if (q1 && q2 && q1 === q2) {
                if (showEmpty) setProfileFieldError("security_answer2", "Question 2 cannot be the same as Question 1.");
                valid = false;
            }
            if (q1 && q3 && q1 === q3) {
                if (showEmpty) setProfileFieldError("security_answer3", "Question 3 cannot be the same as Question 1.");
                valid = false;
            }
            if (q2 && q3 && q2 === q3) {
                if (showEmpty) setProfileFieldError("security_answer3", "Question 3 cannot be the same as Question 2.");
                valid = false;
            }

            // Check if question changed without answer
            const origQ1 = userData.security_question1 || '';
            const origQ2 = userData.security_question2 || '';
            const origQ3 = userData.security_question3 || '';

            if (q1 && q1 !== origQ1 && !a1) {
                if (showEmpty) setProfileFieldError("security_answer1", "Question 1 was changed. Please enter a new answer.");
                valid = false;
            } else if (a1 && a1.length < 2) {
                if (showEmpty) setProfileFieldError("security_answer1", "Answer 1 must be at least 2 characters.");
                valid = false;
            } else if (!q1 && a1) {
                if (showEmpty) setProfileFieldError("security_answer1", "Please select Question 1.");
                valid = false;
            } else {
                clearSingleProfileFieldError("security_answer1");
            }

            if (q2 && q2 !== origQ2 && !a2) {
                if (showEmpty) setProfileFieldError("security_answer2", "Question 2 was changed. Please enter a new answer.");
                valid = false;
            } else if (a2 && a2.length < 2) {
                if (showEmpty) setProfileFieldError("security_answer2", "Answer 2 must be at least 2 characters.");
                valid = false;
            } else if (!q2 && a2) {
                if (showEmpty) setProfileFieldError("security_answer2", "Please select Question 2.");
                valid = false;
            } else if (valid) {
                clearSingleProfileFieldError("security_answer2");
            }

            if (q3 && q3 !== origQ3 && !a3) {
                if (showEmpty) setProfileFieldError("security_answer3", "Question 3 was changed. Please enter a new answer.");
                valid = false;
            } else if (a3 && a3.length < 2) {
                if (showEmpty) setProfileFieldError("security_answer3", "Answer 3 must be at least 2 characters.");
                valid = false;
            } else if (!q3 && a3) {
                if (showEmpty) setProfileFieldError("security_answer3", "Please select Question 3.");
                valid = false;
            } else if (valid) {
                clearSingleProfileFieldError("security_answer3");
            }

            return valid;
        }

        function checkProfilePasswordStrength() {
            const pwdInput = document.getElementById('newPassword');
            const wrap = document.getElementById('profilePasswordStrengthWrap');
            const label = document.getElementById('profileStrengthLabel');
            const bars = [
                document.getElementById('profileBar1'),
                document.getElementById('profileBar2'),
                document.getElementById('profileBar3'),
                document.getElementById('profileBar4')
            ];

            if (!pwdInput) return true;
            const pwd = pwdInput.value;

            // Optional: if empty or untouched, hide strength bar and clear errors
            if (!pwd || pwd.length === 0) {
                if (wrap) wrap.style.display = 'none';
                clearSingleProfileFieldError('newPassword');
                bars.forEach(b => {
                    if (b) b.style.backgroundColor = 'rgba(255, 255, 255, 0.1)';
                });
                if (label) label.textContent = '';
                return true;
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
                setProfileFieldError('newPassword', 'Password must be at least 8 characters long.');
                return false;
            } else if (!/[A-Z]/.test(pwd) || !/[0-9]/.test(pwd) || !/[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?]/.test(pwd)) {
                setProfileFieldError('newPassword', 'Must include uppercase, number, and special symbol.');
                return false;
            } else {
                clearSingleProfileFieldError('newPassword');
                return true;
            }
        }

        function checkProfilePassword(showEmpty) {
            const pwdInput = document.getElementById('newPassword');
            if (!pwdInput) return true;
            const pwd = pwdInput.value;
            // Password is fully optional (pwede ra laktawan)
            if (!pwd || pwd.length === 0) {
                clearSingleProfileFieldError('newPassword');
                const wrap = document.getElementById('profilePasswordStrengthWrap');
                if (wrap) wrap.style.display = 'none';
                return true;
            }
            return checkProfilePasswordStrength();
        }

        function validateProfileForm() {
            const vFn = checkProfileFirstname(true);
            const vMn = checkProfileMiddlename();
            const vLn = checkProfileLastname(true);
            const vSuf = checkProfileSuffix();
            const vEm = checkProfileEmail(true);
            const vBd = checkProfileBirthdate(true);
            const vSex = checkProfileSex(true);
            const vNewPw = checkProfilePassword(true);
            const vP = checkProfilePurok(true);
            const vB = checkProfileBrgy(true);
            const vM = checkProfileMunicipal(true);
            const vPr = checkProfileProvince(true);
            const vC = checkProfileCountry(true);
            const vZ = checkProfileZipcode(true);
            const vSq = checkProfileSecurityQuestions(true);

            if (!vFn) { document.getElementById("firstname").focus(); return false; }
            if (!vMn) { document.getElementById("middlename").focus(); return false; }
            if (!vLn) { document.getElementById("lastname").focus(); return false; }
            if (!vSuf) { document.getElementById("suffix").focus(); return false; }
            if (!vEm) { document.getElementById("email").focus(); return false; }
            if (!vBd) { document.getElementById("birthdate").focus(); return false; }
            if (!vSex) { document.getElementById("sex").focus(); return false; }
            if (!vNewPw) { document.getElementById("newPassword").focus(); return false; }
            if (!vP) { document.getElementById("purokstreet").focus(); return false; }
            if (!vB) { document.getElementById("brgy").focus(); return false; }
            if (!vM) { document.getElementById("municipal").focus(); return false; }
            if (!vPr) { document.getElementById("province").focus(); return false; }
            if (!vC) { document.getElementById("country").focus(); return false; }
            if (!vZ) { document.getElementById("zipcode").focus(); return false; }
            if (!vSq) { return false; }

            return true;
        }

        function toggleEdit() {
            isEditing = !isEditing;
            const inputs = document.querySelectorAll('#profileForm .profile-input');
            const editBtn = document.getElementById('editBtn');
            const saveBtn = document.getElementById('saveBtn');

            inputs.forEach(input => {
                if (input.tagName.toLowerCase() === 'select') {
                    if (isEditing) {
                        input.removeAttribute('disabled');
                        input.disabled = false;
                    } else {
                        input.setAttribute('disabled', 'true');
                        input.disabled = true;
                    }
                } else {
                    if (isEditing) {
                        input.removeAttribute('readonly');
                        input.readOnly = false;
                    } else {
                        input.setAttribute('readonly', 'true');
                        input.readOnly = true;
                    }
                }
            });

            if (isEditing) {
                editBtn.textContent = 'Cancel Edit';
                saveBtn.style.display = 'inline-block';
            } else {
                editBtn.textContent = 'Edit Information';
                saveBtn.style.display = 'none';
                clearAllProfileErrors();
                loadProfile();
            }
        }

        function enableEditSecurity(targetId) {
            if (!isEditing) {
                toggleEdit();
            }
            if (targetId) {
                setTimeout(() => {
                    const el = document.getElementById(targetId);
                    if (el) {
                        el.focus();
                    }
                }, 100);
            }
        }

        function togglePasswordVisibility(inputId, btn) {
            const input = document.getElementById(inputId);
            if (!input) return;
            const isPass = input.type === 'password';
            input.type = isPass ? 'text' : 'password';
            if (btn) {
                btn.innerHTML = isPass
                    ? `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17.94 17.94A10.07 10.07 0 0 1 12 20c-7 0-11-8-11-8a18.45 18.45 0 0 1 5.06-5.94M9.9 4.24A9.12 9.12 0 0 1 12 4c7 0 11 8 11 8a18.5 18.5 0 0 1-2.16 3.19m-6.72-1.07a3 3 0 1 1-4.24-4.24"/><line x1="1" y1="1" x2="23" y2="23"/></svg>`
                    : `<svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M1 12s4-8 11-8 11 8 11 8-4 8-11 8-11-8-11-8z"/><circle cx="12" cy="12" r="3"/></svg>`;
            }
        }

        function promptSaveConfirmation(e) {
            e.preventDefault();
            if (!validateProfileForm()) {
                return false;
            }
            const pwdInput = document.getElementById('confirmCurrentPassword');
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.removeAttribute('readonly');
                pwdInput.removeAttribute('disabled');
                pwdInput.readOnly = false;
                pwdInput.disabled = false;
            }
            document.getElementById('passwordModal').classList.add('active');
            setTimeout(() => {
                if (pwdInput) pwdInput.focus();
            }, 100);
        }

        function closePasswordModal() {
            document.getElementById('passwordModal').classList.remove('active');
        }

        function submitProfileWithPassword(e) {
            e.preventDefault();
            const password = document.getElementById('confirmCurrentPassword').value;

            const bdateVal = document.getElementById('birthdate').value;
            let calculatedAge = 0;
            if (bdateVal) {
                const bd = new Date(bdateVal);
                const today = new Date();
                calculatedAge = today.getFullYear() - bd.getFullYear();
                if (today.getMonth() < bd.getMonth() || (today.getMonth() === bd.getMonth() && today.getDate() < bd.getDate())) {
                    calculatedAge--;
                }
            }

            const payload = {
                current_password: password,
                firstname: document.getElementById('firstname').value.trim(),
                middlename: document.getElementById('middlename').value.trim(),
                lastname: document.getElementById('lastname').value.trim(),
                suffix: document.getElementById('suffix').value.trim(),
                birthdate: bdateVal,
                age: calculatedAge,
                sex: document.getElementById('sex').value.trim(),
                email: document.getElementById('email').value.trim(),
                purokstreet: document.getElementById('purokstreet').value.trim(),
                brgy: document.getElementById('brgy').value.trim(),
                municipal: document.getElementById('municipal').value.trim(),
                province: document.getElementById('province').value.trim(),
                country: document.getElementById('country').value.trim(),
                zipcode: document.getElementById('zipcode').value.trim(),
                security_question1: document.getElementById('security_question1').value.trim(),
                security_question2: document.getElementById('security_question2').value.trim(),
                security_question3: document.getElementById('security_question3').value.trim(),
                security_answer1: document.getElementById('security_answer1').value.trim(),
                security_answer2: document.getElementById('security_answer2').value.trim(),
                security_answer3: document.getElementById('security_answer3').value.trim(),
                new_password: document.getElementById('newPassword') ? document.getElementById('newPassword').value : ''
            };

            fetch('api/profile_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                const toast = document.getElementById('toast');
                if (res.success) {
                    closePasswordModal();
                    toast.style.background = '#22c55e';
                    toast.textContent = '✅ ' + res.message;
                    toast.style.display = 'block';

                    isEditing = true;
                    toggleEdit();
                    loadProfile();

                    setTimeout(() => { toast.style.display = 'none'; }, 3000);
                } else {
                    alert('Update Failed: ' + (res.error || 'Incorrect password'));
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        // =============================================
        // Reset Security Questions Flow
        // =============================================
        let sqVerifiedPassword = '';

        function openModal(id) {
            document.getElementById(id).classList.add('active');
        }
        function closeModal(id) {
            document.getElementById(id).classList.remove('active');
        }

        function clearResetSQFields() {
            const form = document.getElementById('resetSQForm');
            if (form) form.reset();
            ['resetSQ1', 'resetSQ2', 'resetSQ3'].forEach(id => {
                const sq = document.getElementById(id);
                if (sq) {
                    sq.selectedIndex = 0;
                    sq.value = '';
                    sq.disabled = false;
                    sq.removeAttribute('disabled');
                }
            });
            ['resetSA1', 'resetSA2', 'resetSA3'].forEach(id => {
                const sa = document.getElementById(id);
                if (sa) {
                    sa.value = '';
                    sa.type = 'password';
                    sa.readOnly = false;
                    sa.disabled = false;
                    sa.removeAttribute('readonly');
                    sa.removeAttribute('disabled');
                }
            });
            const toggle = document.getElementById('toggleResetAnswers');
            if (toggle) toggle.checked = false;
        }

        function closeResetSQModal() {
            closeModal('resetSQFormModal');
            sqVerifiedPassword = '';
            clearResetSQFields();
        }

        function openResetSecurityFlow() {
            sqVerifiedPassword = '';
            const pwdInput = document.getElementById('sqVerifyPassword');
            if (pwdInput) {
                pwdInput.value = '';
                pwdInput.removeAttribute('readonly');
                pwdInput.removeAttribute('disabled');
                pwdInput.readOnly = false;
                pwdInput.disabled = false;
            }
            clearResetSQFields();
            openModal('resetSQPasswordModal');
            setTimeout(() => {
                if (pwdInput) {
                    pwdInput.focus();
                }
            }, 100);
        }

        function verifySQPassword(e) {
            e.preventDefault();
            const pwdInput = document.getElementById('sqVerifyPassword');
            const pwd = pwdInput ? pwdInput.value : '';
            if (!pwd) return;

            // Quick verify by calling the profile API with just the password and current data
            fetch('api/profile_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify({
                    action: 'verify_password',
                    current_password: pwd
                })
            })
            .then(r => r.json())
            .then(res => {
                if (res.success) {
                    sqVerifiedPassword = pwd;
                    closeModal('resetSQPasswordModal');

                    // Start completely fresh and ensure fields are active and typeable
                    clearResetSQFields();
                    ['resetSQ1', 'resetSQ2', 'resetSQ3', 'resetSA1', 'resetSA2', 'resetSA3'].forEach(id => {
                        const el = document.getElementById(id);
                        if (el) {
                            el.removeAttribute('readonly');
                            el.removeAttribute('disabled');
                            el.readOnly = false;
                            el.disabled = false;
                        }
                    });

                    openModal('resetSQFormModal');
                    setTimeout(() => {
                        const sq1 = document.getElementById('resetSQ1');
                        if (sq1) sq1.focus();
                    }, 100);
                } else {
                    alert('Verification Failed: ' + (res.error || 'Incorrect password.'));
                    if (pwdInput) {
                        pwdInput.focus();
                        pwdInput.select();
                    }
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        function submitResetSecurityQuestions(e) {
            e.preventDefault();

            const q1 = document.getElementById('resetSQ1').value.trim();
            const q2 = document.getElementById('resetSQ2').value.trim();
            const q3 = document.getElementById('resetSQ3').value.trim();
            const a1 = document.getElementById('resetSA1').value.trim();
            const a2 = document.getElementById('resetSA2').value.trim();
            const a3 = document.getElementById('resetSA3').value.trim();

            if (!q1 || !q2 || !q3) { alert('Please select all 3 security questions.'); return; }
            if (!a1 || !a2 || !a3) { alert('Please enter answers for all 3 security questions.'); return; }
            if (q1 === q2 || q1 === q3 || q2 === q3) { alert('Please select 3 different security questions.'); return; }

            const payload = {
                action: 'reset_security_questions',
                current_password: sqVerifiedPassword,
                security_question1: q1,
                security_answer1: a1,
                security_question2: q2,
                security_answer2: a2,
                security_question3: q3,
                security_answer3: a3
            };

            fetch('api/profile_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(r => r.json())
            .then(res => {
                const toast = document.getElementById('toast');
                if (res.success) {
                    closeResetSQModal();

                    toast.style.background = '#3b82f6';
                    toast.textContent = '🔒 ' + res.message;
                    toast.style.display = 'block';
                    setTimeout(() => { toast.style.display = 'none'; }, 3500);

                    loadProfile();
                } else {
                    alert('Reset Failed: ' + (res.error || 'Could not update security questions.'));
                }
            })
            .catch(err => alert('Network error: ' + err));
        }

        // Toggle answer visibility in reset modal
        document.getElementById('toggleResetAnswers')?.addEventListener('change', function() {
            const type = this.checked ? 'text' : 'password';
            document.getElementById('resetSA1').type = type;
            document.getElementById('resetSA2').type = type;
            document.getElementById('resetSA3').type = type;
        });

        // Close modals when clicking backdrop overlay
        document.querySelectorAll('.app-modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                    if (this.id === 'resetSQFormModal') {
                        closeResetSQModal();
                    }
                }
            });
        });

        // Close modals on Escape key
        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closePasswordModal();
                closeModal('resetSQPasswordModal');
                closeResetSQModal();
            }
        });
    </script>
</body>

</html>