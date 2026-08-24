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

$can_view_users = in_array('view_users', $privileges);
$can_view_user_logs = in_array('view_user_logs', $privileges);
$can_view_admin_logs = in_array('view_admin_logs', $privileges);

if (!$can_view_user_logs && !$can_view_admin_logs) {
    header("Location: dashboard.php");
    exit();
}

// --- Filter Logic ---
$filter_role     = isset($_GET['role']) ? trim($_GET['role']) : '';
$filter_action   = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_username = isset($_GET['username']) ? trim($_GET['username']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to   = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where_clauses = [];
$params = [];
$types  = '';

// Privilege Enforcement
if ($can_view_user_logs && !$can_view_admin_logs) {
    $where_clauses[] = "role = 'user'";
} elseif ($can_view_admin_logs && !$can_view_user_logs) {
    $where_clauses[] = "role = 'admin'";
} else {
    $where_clauses[] = "(role = 'user' OR role = 'admin')";
}

if ($filter_role !== '') {
    $where_clauses[] = "role = ?";
    $params[] = $filter_role;
    $types .= 's';
}
if ($filter_action !== '') {
    $where_clauses[] = "action = ?";
    $params[] = $filter_action;
    $types .= 's';
}
if ($filter_username !== '') {
    $where_clauses[] = "username LIKE ?";
    $params[] = '%' . $filter_username . '%';
    $types .= 's';
}
if ($filter_date_from !== '') {
    $where_clauses[] = "DATE(timestamp) >= ?";
    $params[] = $filter_date_from;
    $types .= 's';
}
if ($filter_date_to !== '') {
    $where_clauses[] = "DATE(timestamp) <= ?";
    $params[] = $filter_date_to;
    $types .= 's';
}

$where_sql = '';
if (!empty($where_clauses)) {
    $where_sql = 'WHERE ' . implode(' AND ', $where_clauses);
}

$query = "SELECT * FROM audit_logs $where_sql ORDER BY timestamp DESC";
$stmt = mysqli_prepare($conn, $query);

if (!empty($params)) {
    mysqli_stmt_bind_param($stmt, $types, ...$params);
}

mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get distinct values for filter dropdowns
$roles_query = "SELECT DISTINCT role FROM audit_logs ";
if ($can_view_user_logs && !$can_view_admin_logs) {
    $roles_query .= "WHERE role = 'user' ";
} elseif ($can_view_admin_logs && !$can_view_user_logs) {
    $roles_query .= "WHERE role = 'admin' ";
} else {
    $roles_query .= "WHERE role = 'user' OR role = 'admin' ";
}
$roles_query .= "ORDER BY role ASC";

$roles_result = mysqli_query($conn, $roles_query);
$all_roles = mysqli_fetch_all($roles_result, MYSQLI_ASSOC);

$actions_result = mysqli_query($conn, "SELECT DISTINCT action FROM audit_logs ORDER BY action ASC");
$all_actions = mysqli_fetch_all($actions_result, MYSQLI_ASSOC);

// Process logs to merge Login/Logout
$processed_logs = [];
$logout_stack = []; // user_id => stack of logout logs

foreach ($logs as $index => $log) {
    if ($log['action'] === 'Logout') {
        $logout_stack[$log['user_id']][] = $log;
        $log['is_logout_row'] = true;
        $processed_logs[$index] = $log;
    } elseif ($log['action'] === 'Login') {
        if (isset($logout_stack[$log['user_id']]) && !empty($logout_stack[$log['user_id']])) {
            $matched_logout = array_shift($logout_stack[$log['user_id']]);
            $log['time_out'] = $matched_logout['timestamp'];
        } else {
            $log['time_out'] = null;
        }
        $processed_logs[$index] = $log;
    } else {
        $processed_logs[$index] = $log;
    }
}

// Second pass with better index tracking
$final_logs = $logs;
$logout_indices = [];

foreach ($final_logs as $i => $log) {
    if ($log['action'] === 'Logout') {
        $logout_indices[$log['user_id']][] = $i;
    } elseif ($log['action'] === 'Login') {
        if (isset($logout_indices[$log['user_id']]) && !empty($logout_indices[$log['user_id']])) {
            $logout_idx = array_pop($logout_indices[$log['user_id']]);
            $final_logs[$i]['time_out'] = $final_logs[$logout_idx]['timestamp'];
            $final_logs[$logout_idx]['skip_render'] = true;
        } else {
            $final_logs[$i]['time_out'] = null;
        }
    }
}

$has_filters = ($filter_role !== '' || $filter_action !== '' || $filter_username !== '' || $filter_date_from !== '' || $filter_date_to !== '');
?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audit Logs - Super Admin</title>
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

        /* ---- Filter Bar ---- */
        .filter-bar {
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 6px 20px rgba(0, 0, 0, 0.15);
        }

        .filter-bar-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: var(--text-muted);
            margin-bottom: 0.85rem;
            font-weight: 600;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-bar-title svg {
            width: 14px;
            height: 14px;
            opacity: 0.7;
        }

        .filter-row {
            display: flex;
            flex-wrap: wrap;
            gap: 0.75rem;
            align-items: flex-end;
        }

        .filter-group {
            display: flex;
            flex-direction: column;
            gap: 0.3rem;
            flex: 1;
            min-width: 150px;
        }

        .filter-group label {
            font-size: 0.75rem;
            color: var(--text-muted);
            font-weight: 500;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input[type="text"],
        .filter-group input[type="date"] {
            width: 100%;
            padding: 0.6rem 0.85rem;
            background: rgba(0, 0, 0, 0.35);
            border: 1px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: var(--text-color);
            font-size: 0.85rem;
            font-family: inherit;
            transition: all 0.25s ease;
            outline: none;
        }

        .filter-group select:hover,
        .filter-group input[type="text"]:hover,
        .filter-group input[type="date"]:hover {
            border-color: rgba(255, 255, 255, 0.2);
        }

        .filter-group select:focus,
        .filter-group input[type="text"]:focus,
        .filter-group input[type="date"]:focus {
            border-color: var(--accent-blue);
            box-shadow: 0 0 0 3px rgba(0, 123, 255, 0.15);
            background: rgba(0, 0, 0, 0.5);
        }

        .filter-group select option {
            background: #0d1117;
            color: #e6edf3;
        }

        .filter-actions {
            display: flex;
            gap: 0.5rem;
            align-items: flex-end;
            padding-bottom: 1px;
        }

        .btn-filter {
            padding: 0.6rem 1.3rem;
            border: none;
            border-radius: 8px;
            font-size: 0.85rem;
            font-weight: 600;
            cursor: pointer;
            transition: all 0.25s ease;
            font-family: inherit;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            white-space: nowrap;
        }

        .btn-filter-apply {
            background: linear-gradient(135deg, var(--accent-blue), #0056b3);
            color: white;
            box-shadow: 0 2px 10px rgba(0, 123, 255, 0.25);
        }

        .btn-filter-apply:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(0, 123, 255, 0.4);
        }

        .btn-filter-clear {
            background: rgba(255, 255, 255, 0.06);
            color: var(--text-muted);
            border: 1px solid rgba(255, 255, 255, 0.1);
        }

        .btn-filter-clear:hover {
            background: rgba(255, 59, 59, 0.15);
            color: var(--accent-red);
            border-color: rgba(255, 59, 59, 0.3);
        }

        .active-filters-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(0, 123, 255, 0.15);
            color: var(--accent-blue);
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            letter-spacing: 0.5px;
        }

        .results-count {
            font-size: 0.8rem;
            color: var(--text-muted);
            margin-top: 0.75rem;
            padding-top: 0.65rem;
            border-top: 1px solid rgba(255, 255, 255, 0.05);
        }

        .results-count strong {
            color: var(--text-color);
        }

        /* ---- Table ---- */
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
            min-width: 800px;
        }

        table {
            width: 100%;
            border-collapse: collapse;
            color: var(--text-color);
            table-layout: auto;
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
            padding: 1.2rem 1rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            font-size: 0.9rem;
        }

        tr:nth-child(even) {
            background: rgba(255, 255, 255, 0.02);
        }

        tr:hover {
            background: rgba(255, 255, 255, 0.05);
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

        .action-login {
            color: var(--accent-green);
            font-weight: bold;
            text-shadow: 0 0 8px rgba(40, 167, 69, 0.4);
        }

        .action-logout {
            color: var(--accent-orange);
            font-weight: bold;
        }

        .action-other {
            color: var(--accent-blue);
        }

        .pagination {
            display: flex;
            justify-content: flex-end;
            margin-top: 1rem;
            gap: 0.5rem;
        }

        .pagination a {
            padding: 0.6rem 1.2rem;
            background: rgba(0, 0, 0, 0.4);
            border: 2px solid rgba(255, 255, 255, 0.1);
            border-radius: 8px;
            color: white;
            font-weight: bold;
            text-decoration: none;
            transition: all 0.3s;
        }

        .pagination a:hover {
            background: var(--accent-blue);
            border-color: var(--accent-blue);
            box-shadow: 0 0 10px rgba(0, 123, 255, 0.4);
        }

        @media (max-width: 768px) {
            .filter-row {
                flex-direction: column;
            }
            .filter-group {
                min-width: 100%;
            }
            .filter-actions {
                width: 100%;
                justify-content: stretch;
            }
            .filter-actions .btn-filter {
                flex: 1;
                justify-content: center;
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
                <?php if ($can_view_users): ?>
                <li><a href="manage_users.php">Manage Users</a></li>
                <?php endif; ?>
                <li><a href="audit_logs.php" style="color: var(--accent-blue);">Audit Logs</a></li>
                <button onclick="location.href='../logout.php'">Logout</button>
            </ul>
        </div>
    </nav>

    <div class="container">
        <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 1rem;">
            <div>
                <h1>System Audit Logs</h1>
                <p>Tracking user authentication and system activities.</p>
            </div>
        </div>

        <!-- Filter Bar -->
        <div class="filter-bar">
            <div class="filter-bar-title">
                <svg xmlns="http://www.w3.org/2000/svg" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M3 4a1 1 0 011-1h16a1 1 0 011 1v2.586a1 1 0 01-.293.707l-6.414 6.414a1 1 0 00-.293.707V17l-4 4v-6.586a1 1 0 00-.293-.707L3.293 7.293A1 1 0 013 6.586V4z"/></svg>
                Filters
                <?php if ($has_filters): ?>
                    <span class="active-filters-badge">ACTIVE</span>
                <?php endif; ?>
            </div>
            <form method="GET" action="audit_logs.php" id="filterForm">
                <div class="filter-row">
                    <div class="filter-group">
                        <label for="filter-role">Role</label>
                        <select name="role" id="filter-role">
                            <option value="">All Roles</option>
                            <?php foreach ($all_roles as $r): ?>
                                <option value="<?php echo htmlspecialchars($r['role']); ?>"
                                    <?php echo ($filter_role === $r['role']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars(ucfirst($r['role'])); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-action">Action</label>
                        <select name="action" id="filter-action">
                            <option value="">All Actions</option>
                            <?php foreach ($all_actions as $a): ?>
                                <option value="<?php echo htmlspecialchars($a['action']); ?>"
                                    <?php echo ($filter_action === $a['action']) ? 'selected' : ''; ?>>
                                    <?php echo htmlspecialchars($a['action']); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="filter-group">
                        <label for="filter-username">Username</label>
                        <input type="text" name="username" id="filter-username" placeholder="Search username..."
                               value="<?php echo htmlspecialchars($filter_username); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter-date-from">Date From</label>
                        <input type="date" name="date_from" id="filter-date-from"
                               value="<?php echo htmlspecialchars($filter_date_from); ?>">
                    </div>
                    <div class="filter-group">
                        <label for="filter-date-to">Date To</label>
                        <input type="date" name="date_to" id="filter-date-to"
                               value="<?php echo htmlspecialchars($filter_date_to); ?>">
                    </div>
                    <div class="filter-actions">
                        <button type="submit" class="btn-filter btn-filter-apply">
                            <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="M21 21l-4.35-4.35"/></svg>
                            Apply
                        </button>
                        <?php if ($has_filters): ?>
                            <a href="audit_logs.php" class="btn-filter btn-filter-clear" style="text-decoration:none;">
                                ✕ Clear
                            </a>
                        <?php endif; ?>
                    </div>
                </div>
                <?php
                    // Count visible rows
                    $visible_count = 0;
                    foreach ($final_logs as $row) {
                        if (isset($row['skip_render']) && $row['skip_render']) continue;
                        $visible_count++;
                    }
                ?>
                <div class="results-count">
                    Showing <strong><?php echo $visible_count; ?></strong> log entries
                    <?php if ($has_filters): ?> (filtered)<?php endif; ?>
                </div>
            </form>
        </div>

        <div class="table-container">
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>ID</th>
                            <th>User ID</th>
                            <th>Username</th>
                            <th>Role</th>
                            <th>Action</th>
                            <th>Details</th>
                            <th>Time In</th>
                            <th>Time Out</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (!empty($final_logs)): ?>
                            <?php foreach ($final_logs as $row): ?>
                                <?php if (isset($row['skip_render']) && $row['skip_render'])
                                    continue; ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($row['id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['user_id']); ?></td>
                                    <td><?php echo htmlspecialchars($row['username']); ?></td>
                                    <td>
                                        <?php
                                        if ($row['role'] == 'superadmin') {
                                            $role_class = 'role-superadmin';
                                        } elseif ($row['role'] == 'admin') {
                                            $role_class = 'role-admin';
                                        } else {
                                            $role_class = 'role-user';
                                        }
                                        ?>
                                        <span class="<?php echo $role_class; ?>">
                                            <?php echo htmlspecialchars(ucfirst($row['role'])); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        if ($row['action'] == 'Login') {
                                            $action_class = 'action-login';
                                        } elseif ($row['action'] == 'Logout') {
                                            $action_class = 'action-logout';
                                        } else {
                                            $action_class = 'action-other';
                                        }
                                        ?>
                                        <span class="<?php echo $action_class; ?>">
                                            <?php echo htmlspecialchars($row['action']); ?>
                                        </span>
                                    </td>
                                    <td><?php echo htmlspecialchars($row['details']); ?></td>
                                    <td>
                                        <?php echo ($row['action'] === 'Login') ? htmlspecialchars($row['timestamp']) : (($row['action'] !== 'Logout') ? htmlspecialchars($row['timestamp']) : ''); ?>
                                    </td>
                                    <td>
                                        <?php
                                        if ($row['action'] === 'Login') {
                                            echo $row['time_out'] ? htmlspecialchars($row['time_out']) : '<span style="color: var(--accent-green)">Active</span>';
                                        } elseif ($row['action'] === 'Logout') {
                                            echo htmlspecialchars($row['timestamp']); // Orphaned logout
                                        } else {
                                            echo '';
                                        }
                                        ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" style="text-align: center;">No logs found.</td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

</body>

</html>