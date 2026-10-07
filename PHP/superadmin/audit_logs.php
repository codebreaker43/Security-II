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

// --- Filter Logic ---
$filter_role     = isset($_GET['role']) ? trim($_GET['role']) : '';
$filter_action   = isset($_GET['action']) ? trim($_GET['action']) : '';
$filter_username = isset($_GET['username']) ? trim($_GET['username']) : '';
$filter_date_from = isset($_GET['date_from']) ? trim($_GET['date_from']) : '';
$filter_date_to   = isset($_GET['date_to']) ? trim($_GET['date_to']) : '';

$where_clauses = [];
$params = [];
$types  = '';

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

// --- Pagination ---
$per_page = 15;
$current_page = isset($_GET['page']) && is_numeric($_GET['page']) ? max(1, (int)$_GET['page']) : 1;

// Count total matching rows
$count_stmt = mysqli_prepare($conn, "SELECT COUNT(*) FROM audit_logs $where_sql");
if (!empty($params)) {
    mysqli_stmt_bind_param($count_stmt, $types, ...$params);
}
mysqli_stmt_execute($count_stmt);
mysqli_stmt_bind_result($count_stmt, $total_logs);
mysqli_stmt_fetch($count_stmt);
mysqli_stmt_close($count_stmt);
$total_pages = max(1, (int)ceil($total_logs / $per_page));
if ($current_page > $total_pages) $current_page = $total_pages;
$offset = ($current_page - 1) * $per_page;

// Paginated query
$query = "SELECT * FROM audit_logs $where_sql ORDER BY timestamp DESC LIMIT ? OFFSET ?";
$stmt = mysqli_prepare($conn, $query);
$paginated_params = $params;
$paginated_types  = $types . 'ii';
$paginated_params[] = $per_page;
$paginated_params[] = $offset;
mysqli_stmt_bind_param($stmt, $paginated_types, ...$paginated_params);
mysqli_stmt_execute($stmt);
$result = mysqli_stmt_get_result($stmt);
$logs = mysqli_fetch_all($result, MYSQLI_ASSOC);

// Get distinct values for filter dropdowns
$roles_result = mysqli_query($conn, "SELECT DISTINCT role FROM audit_logs ORDER BY role ASC");
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
    <title>Audit Logs - ONE PIECE Super Admin</title>
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

        /* Luminous One Piece Theme */
        h1 {
            font-size: 2.2rem;
            font-weight: 800;
            color: #0f172a;
            margin-bottom: 0.35rem;
            letter-spacing: -0.5px;
            text-shadow: 0 1px 3px rgba(255, 255, 255, 0.8);
        }

        .main-content p {
            color: #475569;
            font-weight: 500;
        }

        /* ---- Filter Bar ---- */
        .filter-bar {
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.25rem 1.5rem;
            margin-bottom: 1rem;
            box-shadow: 0 4px 20px rgba(0, 0, 0, 0.08);
        }

        .filter-bar-title {
            font-size: 0.8rem;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            color: #d97706;
            margin-bottom: 0.85rem;
            font-weight: 700;
            display: flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-bar-title svg {
            width: 14px;
            height: 14px;
            opacity: 0.9;
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
            font-size: 0.80rem;
            color: #0f172a;
            font-weight: 700;
            letter-spacing: 0.5px;
        }

        .filter-group select,
        .filter-group input[type="text"],
        .filter-group input[type="date"] {
            width: 100%;
            padding: 0.65rem 0.85rem;
            background: #ffffff;
            border: 1.5px solid #94a3b8;
            border-radius: 8px;
            color: #0f172a;
            font-size: 0.88rem;
            font-weight: 600;
            font-family: inherit;
            transition: all 0.25s ease;
            outline: none;
            box-shadow: 0 1px 2px rgba(0,0,0,0.05);
        }

        .filter-group select:hover,
        .filter-group input[type="text"]:hover,
        .filter-group input[type="date"]:hover {
            border-color: #f59e0b;
        }

        .filter-group select:focus,
        .filter-group input[type="text"]:focus,
        .filter-group input[type="date"]:focus {
            border-color: #d97706;
            box-shadow: 0 0 0 3px rgba(245, 158, 11, 0.2);
            background: #ffffff;
        }

        .filter-group select option {
            background: #ffffff;
            color: #0f172a;
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
            background: linear-gradient(135deg, #d97706, #b45309);
            color: white;
            box-shadow: 0 2px 10px rgba(217, 119, 6, 0.25);
        }

        .btn-filter-apply:hover {
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(217, 119, 6, 0.4);
        }

        .btn-filter-clear {
            background: #f1f5f9;
            color: #475569;
            border: 1px solid #cbd5e1;
        }

        .btn-filter-clear:hover {
            background: #fee2e2;
            color: #dc2626;
            border-color: #fca5a5;
        }

        .active-filters-badge {
            display: inline-flex;
            align-items: center;
            background: rgba(245, 158, 11, 0.15);
            color: #d97706;
            font-size: 0.7rem;
            font-weight: 700;
            padding: 0.15rem 0.5rem;
            border-radius: 20px;
            margin-left: 0.5rem;
            letter-spacing: 0.5px;
        }

        .results-count {
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.75rem;
            padding-top: 0.65rem;
            border-top: 1px solid #e2e8f0;
        }

        .results-count strong {
            color: #0f172a;
        }

        /* ---- Table ---- */
        .table-container {
            width: 100%;
            margin-top: 1rem;
            background: #ffffff;
            border: 1px solid #e2e8f0;
            border-radius: 16px;
            padding: 1.5rem;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.08);
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
            color: #0f172a;
            table-layout: auto;
        }

        th {
            background: rgba(254, 243, 199, 0.75) !important;
            font-weight: 800;
            color: #78350f !important;
            text-transform: uppercase;
            letter-spacing: 0.75px;
            border-top: 3px solid #f59e0b !important;
            border-bottom: 2px solid #fde68a !important;
        }

        th,
        td {
            padding: 1.1rem 1rem;
            text-align: left;
            border-bottom: 1px solid #f1f5f9;
            font-size: 0.88rem;
        }

        tr:nth-child(even) {
            background: rgba(248, 250, 252, 0.6);
        }

        tr:hover {
            background: rgba(254, 243, 199, 0.3);
        }

        .role-superadmin {
            color: #b91c1c;
            font-weight: 800;
        }

        .role-admin {
            color: #0284c7;
            font-weight: 700;
        }

        .role-user {
            color: #475569;
            font-weight: 600;
        }

        .action-login {
            color: #15803d;
            font-weight: 700;
        }

        .action-logout {
            color: #b45309;
            font-weight: 700;
        }

        .action-other {
            color: #0284c7;
            font-weight: 600;
        }

        /* ---- Pagination ---- */
        .pagination {
            display: flex;
            justify-content: center;
            align-items: center;
            margin-top: 1.5rem;
            gap: 0.4rem;
            flex-wrap: wrap;
        }

        .pagination a,
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
        }

        .pagination a:hover {
            background: #fef3c7;
            border-color: #f59e0b;
            color: #92400e;
            transform: translateY(-1px);
            box-shadow: 0 4px 12px rgba(245, 158, 11, 0.25);
        }

        .pagination a.active {
            background: linear-gradient(135deg, #d97706, #b45309);
            border-color: #d97706;
            color: #fff;
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

        .pagination-info {
            text-align: center;
            font-size: 0.8rem;
            color: #64748b;
            margin-top: 0.6rem;
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
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/../includes/sidebar.php';
        render_sidebar('audit_logs');
        ?>

        <div class="app-main">
            <main class="main-content">
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
                    $visible_count = 0;
                    foreach ($final_logs as $row) {
                        if (isset($row['skip_render']) && $row['skip_render']) continue;
                        $visible_count++;
                    }
                    $range_start = $total_logs > 0 ? $offset + 1 : 0;
                    $range_end   = min($offset + $per_page, $total_logs);
                ?>
                <div class="results-count">
                    Showing <strong><?php echo $range_start; ?>&ndash;<?php echo $range_end; ?></strong>
                    of <strong><?php echo $total_logs; ?></strong> log entries
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

            <?php
            $query_params = array_filter([
                'role'      => $filter_role,
                'action'    => $filter_action,
                'username'  => $filter_username,
                'date_from' => $filter_date_from,
                'date_to'   => $filter_date_to,
            ]);

            if (!function_exists('pagLink')) {
                function pagLink($query_params, $p, $label, $isCurrent = false, $isDots = false) {
                    if ($isDots) return "<span class='dots'>$label</span>";
                    $cls = $isCurrent ? ' class="active"' : '';
                    $params = $query_params;
                    $params['page'] = $p;
                    $url = 'audit_logs.php?' . http_build_query($params);
                    return "<a href='{$url}'{$cls}>{$label}</a>";
                }
            }

            if ($total_pages > 1):
            ?>
            <div class="pagination">
                <?php
                if ($current_page > 1) {
                    echo pagLink($query_params, $current_page - 1, '&laquo; Prev');
                } else {
                    echo '<span style="opacity:0.35;pointer-events:none">&laquo; Prev</span>';
                }

                $window = 2;
                $pages_to_show = [];
                for ($p = 1; $p <= $total_pages; $p++) {
                    if ($p === 1 || $p === $total_pages ||
                        ($p >= $current_page - $window && $p <= $current_page + $window)) {
                        $pages_to_show[] = $p;
                    }
                }

                $prev_p = null;
                foreach ($pages_to_show as $p) {
                    if ($prev_p !== null && $p - $prev_p > 1) {
                        echo pagLink($query_params, 0, '&hellip;', false, true);
                    }
                    echo pagLink($query_params, $p, $p, $p === $current_page);
                    $prev_p = $p;
                }

                if ($current_page < $total_pages) {
                    echo pagLink($query_params, $current_page + 1, 'Next &raquo;');
                } else {
                    echo '<span style="opacity:0.35;pointer-events:none">Next &raquo;</span>';
                }
                ?>
            </div>
            <div class="pagination-info">Page <?php echo $current_page; ?> of <?php echo $total_pages; ?></div>
            <?php endif; ?>
        </div>
            </main>
        </div>
    </div>

</body>

</html>