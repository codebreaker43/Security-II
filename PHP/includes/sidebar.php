<?php
/**
 * Modern Responsive Sidebar Component
 * Supports: superadmin, admin, user roles
 * 
 * Usage in page:
 * $active_page = 'dashboard'; // or 'store', 'orders', 'manage_users', 'block_list', 'audit_logs', 'profile'
 * include __DIR__ . '/../includes/sidebar.php'; (adjust relative path)
 * render_sidebar($active_page);
 */

function render_sidebar($active_page = '') {
    $role = $_SESSION['role'] ?? 'user';
    $username = $_SESSION['username'] ?? 'User';
    $user_id = $_SESSION['user_id'] ?? '';
    
    // Determine path depth relative to /PHP/ directory
    // If called from admin/ or superadmin/, path prefix to PHP root is '../'
    $script_path = str_replace('\\', '/', $_SERVER['SCRIPT_NAME'] ?? '');
    $in_subdir = (strpos($script_path, '/admin/') !== false || strpos($script_path, '/superadmin/') !== false);
    $base_path = $in_subdir ? '../' : './';
    
    // Guard: Check if personal information is complete
    $is_profile_completed = true;
    if (!empty($user_id) && isset($GLOBALS['conn'])) {
        global $conn;
        require_once __DIR__ . '/auth_guard.php';
        $is_profile_completed = check_user_personal_info_complete($conn, $user_id);
        $_SESSION['profile_completed'] = $is_profile_completed ? 1 : 0;

        if (!$is_profile_completed && $active_page !== 'profile') {
            if (!headers_sent()) {
                header("Location: " . $base_path . "complete_profile.php");
                exit();
            } else {
                echo "<script>window.location.href = '" . $base_path . "complete_profile.php';</script>";
                exit();
            }
        }
    }
    
    // Admin privilege check if applicable
    $can_view_users = true;
    $can_view_user_logs = true;
    $can_view_admin_logs = true;
    $can_approve = ($role === 'superadmin');
    
    if ($role === 'admin' && isset($GLOBALS['conn'])) {
        global $conn;
        $admin_id_clean = mysqli_real_escape_string($conn, $user_id);
        $priv_q = mysqli_query($conn, "SELECT privileges FROM user WHERE idno = '$admin_id_clean'");
        if ($priv_q && mysqli_num_rows($priv_q) > 0) {
            $p_data = mysqli_fetch_assoc($priv_q);
            $privs = json_decode($p_data['privileges'] ?? '[]', true) ?? [];
            $can_view_users = in_array('view_users', $privs);
            $can_view_user_logs = in_array('view_user_logs', $privs) || in_array('view_audit_logs', $privs);
            $can_view_admin_logs = in_array('view_admin_logs', $privs) || in_array('view_audit_logs', $privs);
            $can_approve = in_array('approve_users', $privs);
        }
    }


    // Keep active Super Admin session alive in database
    if ($role === 'superadmin' && isset($GLOBALS['conn']) && !empty($user_id)) {
        global $conn;
        $sa_clean = mysqli_real_escape_string($conn, $user_id);
        @mysqli_query($conn, "UPDATE user SET is_online = 1, last_activity = NOW() WHERE idno = '$sa_clean'");
    }

    // Role theme configuration
    $role_badge_text = 'CREW MEMBER';
    $role_badge_class = 'badge-user';
    $brand_title = 'ONE <span class="highlight">PIECE</span>';
    $home_url = $base_path . 'home.php';

    if ($role === 'superadmin') {
        $role_badge_text = 'SUPER ADMIN';
        $role_badge_class = 'badge-superadmin';
        $brand_title = 'SUPER <span class="highlight">ADMIN</span>';
        $home_url = $base_path . 'superadmin/dashboard.php';
    } elseif ($role === 'admin') {
        $role_badge_text = 'ADMINISTRATOR';
        $role_badge_class = 'badge-admin';
        $brand_title = 'ADMIN <span class="highlight">PORTAL</span>';
        $home_url = $base_path . 'admin/dashboard.php';
    }
    
    $initial = strtoupper(substr($username, 0, 1));
?>

<!-- Mobile Topbar -->
<header class="mobile-topbar">
    <button class="sidebar-toggle-btn" id="sidebarToggleBtn" onclick="toggleSidebar()" aria-label="Toggle navigation menu">
        <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
            <line x1="3" y1="12" x2="21" y2="12"></line>
            <line x1="3" y1="6" x2="21" y2="6"></line>
            <line x1="3" y1="18" x2="21" y2="18"></line>
        </svg>
    </button>
    <a href="<?php echo htmlspecialchars($home_url); ?>" class="mobile-logo">
        <svg width="26" height="26" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
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
        <span><?php echo $brand_title; ?></span>
    </a>
    <div class="mobile-user-avatar">
        <?php echo htmlspecialchars($initial); ?>
    </div>
</header>

<!-- Sidebar Backdrop Overlay for Mobile -->
<div class="sidebar-backdrop" id="sidebarBackdrop" onclick="toggleSidebar()"></div>

<!-- Main Navigation Sidebar -->
<aside class="app-sidebar" id="appSidebar">
    <div class="sidebar-header">
        <a href="<?php echo htmlspecialchars($home_url); ?>" class="sidebar-brand">
            <div class="brand-icon">
                <svg width="28" height="28" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
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
            <div class="brand-text"><?php echo $brand_title; ?></div>
        </a>
        <button class="sidebar-close-btn" onclick="toggleSidebar()" aria-label="Close sidebar">
            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <line x1="18" y1="6" x2="6" y2="18"></line>
                <line x1="6" y1="6" x2="18" y2="18"></line>
            </svg>
        </button>
    </div>

    <!-- User Profile Card -->
    <div class="sidebar-user-card">
        <div class="user-avatar-wrap">
            <div class="user-avatar-circle">
                <?php echo htmlspecialchars($initial); ?>
            </div>
            <span class="user-status-indicator"></span>
        </div>
        <div class="user-info-wrap">
            <div class="user-name" title="<?php echo htmlspecialchars($username); ?>">
                <?php echo htmlspecialchars($username); ?>
            </div>
            <div class="user-role-badge <?php echo $role_badge_class; ?>">
                <?php echo $role_badge_text; ?>
            </div>
        </div>
    </div>

    <?php
    $locked_attr = '';
    $locked_badge = '';
    if (!$is_profile_completed) {
        $locked_attr = ' onclick="alert(\'Please complete all required fields in your Personal Information first before accessing other features.\'); return false;" style="opacity: 0.38; cursor: not-allowed; pointer-events: auto;" ';
        $locked_badge = '<span style="margin-left: auto; font-size: 0.65rem; background: rgba(239, 68, 68, 0.25); color: #fca5a5; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(239, 68, 68, 0.4); font-weight: 700; display: inline-flex; align-items: center; gap: 3px;">🔒 LOCKED</span>';
    }
    ?>
    <!-- Sidebar Navigation Menu -->
    <nav class="sidebar-nav">
        <div class="nav-section-label">MAIN FEATURES</div>
        <ul class="nav-menu">
            <?php if ($role === 'superadmin'): ?>
                <!-- Superadmin Features -->
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'superadmin/dashboard.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </span>
                        <span class="nav-label">Dashboard</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'superadmin/manage_users.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'manage_users') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </span>
                        <span class="nav-label">Account Management</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'superadmin/pending_approvals.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'pending_approvals') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </span>
                        <span class="nav-label">Pending Approvals</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'superadmin/block_list.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'block_list') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="12" r="10"></circle>
                                <line x1="4.93" y1="4.93" x2="19.07" y2="19.07"></line>
                            </svg>
                        </span>
                        <span class="nav-label">Block List</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'superadmin/audit_logs.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'audit_logs') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </span>
                        <span class="nav-label">Audit Logs</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>

            <?php elseif ($role === 'admin'): ?>
                <!-- Admin Features -->
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'admin/dashboard.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'dashboard') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <rect x="3" y="3" width="7" height="7"></rect>
                                <rect x="14" y="3" width="7" height="7"></rect>
                                <rect x="14" y="14" width="7" height="7"></rect>
                                <rect x="3" y="14" width="7" height="7"></rect>
                            </svg>
                        </span>
                        <span class="nav-label">Dashboard</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <?php if ($can_view_users): ?>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'admin/manage_users.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'manage_users') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <path d="M23 21v-2a4 4 0 0 0-3-3.87"></path>
                                <path d="M16 3.13a4 4 0 0 1 0 7.75"></path>
                            </svg>
                        </span>
                        <span class="nav-label">User Management</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($can_approve): ?>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'admin/pending_approvals.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'pending_approvals') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                                <circle cx="9" cy="7" r="4"></circle>
                                <polyline points="16 11 18 13 22 9"></polyline>
                            </svg>
                        </span>
                        <span class="nav-label">Pending Approvals</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <?php endif; ?>
                <?php if ($can_view_user_logs || $can_view_admin_logs): ?>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'admin/audit_logs.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'audit_logs') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path>
                                <polyline points="14 2 14 8 20 8"></polyline>
                                <line x1="16" y1="13" x2="8" y2="13"></line>
                                <line x1="16" y1="17" x2="8" y2="17"></line>
                                <polyline points="10 9 9 9 8 9"></polyline>
                            </svg>
                        </span>
                        <span class="nav-label">Audit Logs</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <?php endif; ?>

            <?php else: ?>
                <!-- User / Customer Features -->
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'home.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'store') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <circle cx="12" cy="5" r="3"></circle>
                                <line x1="12" y1="22" x2="12" y2="8"></line>
                                <path d="M5 12H2a10 10 0 0 0 20 0h-3"></path>
                            </svg>
                        </span>
                        <span class="nav-label">Grand Line Bazaar</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
                <li class="nav-item">
                    <a href="<?php echo $is_profile_completed ? $base_path . 'orders.php' : 'javascript:void(0);'; ?>" class="nav-link <?php echo ($active_page === 'orders') ? 'active' : ''; ?>"<?php echo $locked_attr; ?>>
                        <span class="nav-icon">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                                <line x1="16.5" y1="9.4" x2="7.5" y2="4.21"></line>
                                <path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path>
                                <polyline points="3.27 6.96 12 12.01 20.73 6.96"></polyline>
                                <line x1="12" y1="22.08" x2="12" y2="12"></line>
                            </svg>
                        </span>
                        <span class="nav-label">Loot & Orders</span>
                        <?php echo $locked_badge; ?>
                    </a>
                </li>
            <?php endif; ?>

            <div class="nav-section-label" style="margin-top: 1.5rem;">ACCOUNT</div>
            <li class="nav-item">
                <a href="<?php echo $base_path; ?>profile.php" class="nav-link <?php echo ($active_page === 'profile') ? 'active' : ''; ?>">
                    <span class="nav-icon">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                            <path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path>
                            <circle cx="12" cy="7" r="4"></circle>
                        </svg>
                    </span>
                    <span class="nav-label">Personal Information</span>
                    <?php if (!$is_profile_completed): ?>
                        <span style="margin-left: auto; font-size: 0.65rem; background: rgba(234, 179, 8, 0.25); color: #facc15; padding: 2px 6px; border-radius: 4px; border: 1px solid rgba(234, 179, 8, 0.45); font-weight: 700;">⚠️ REQUIRED</span>
                    <?php endif; ?>
                </a>
            </li>
        </ul>
    </nav>

    <!-- Sidebar Footer with Logout Button -->
    <div class="sidebar-footer">
        <button class="sidebar-logout-btn" onclick="openSidebarLogoutModal()">
            <span class="logout-icon">
                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                    <path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"></path>
                    <polyline points="16 17 21 12 16 7"></polyline>
                    <line x1="21" y1="12" x2="9" y2="12"></line>
                </svg>
            </span>
            <span>Log out</span>
        </button>
    </div>
</aside>

<!-- Global Sidebar Logout Modal (One Piece Pirate Theme) -->
<div class="modal-overlay" id="sidebarLogoutModal">
    <div class="modal-content">
        <div class="pirate-emblem-ring">
            <svg width="44" height="44" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
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
        <h3>Disembark <span class="highlight-gold">Ship?</span></h3>
        <p>Are you sure you want to log out of your One Piece crew session?</p>
        <div class="modal-buttons">
            <button class="modal-btn modal-btn-cancel" onclick="closeSidebarLogoutModal()">Stay on Board</button>
            <button class="modal-btn modal-btn-confirm" onclick="window.location.href='<?php echo $base_path; ?>logout.php'">Log Out</button>
        </div>
    </div>
</div>

<script>
    function toggleSidebar() {
        const sidebar = document.getElementById('appSidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (sidebar && backdrop) {
            sidebar.classList.toggle('open');
            backdrop.classList.toggle('active');
        }
    }

    function openSidebarLogoutModal() {
        const modal = document.getElementById('sidebarLogoutModal');
        if (modal) modal.classList.add('active');
    }

    function closeSidebarLogoutModal() {
        const modal = document.getElementById('sidebarLogoutModal');
        if (modal) modal.classList.remove('active');
    }

    // Attach click outside listener to modal and move it to body root
    // to escape any stacking context (backdrop-filter on sidebar traps position:fixed)
    document.addEventListener('DOMContentLoaded', function() {
        const modal = document.getElementById('sidebarLogoutModal');
        if (modal) {
            document.body.appendChild(modal);
            modal.addEventListener('click', function(e) {
                if (e.target === this) closeSidebarLogoutModal();
            });
        }
    });

    document.addEventListener('keydown', function(e) {
        if (e.key === 'Escape') {
            closeSidebarLogoutModal();
            const sidebar = document.getElementById('appSidebar');
            const backdrop = document.getElementById('sidebarBackdrop');
            if (sidebar && sidebar.classList.contains('open')) {
                sidebar.classList.remove('open');
                if (backdrop) backdrop.classList.remove('active');
            }
        }
    });
</script>
<?php
}
?>
