<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}
include(__DIR__ . "/../db.php");

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/../includes/auth_guard.php';
enforce_profile_completion($conn);


// Fetch Real Data
// 1. Count Active Admins
$admin_query = "SELECT COUNT(*) as count FROM user WHERE role = 'admin' AND status = 'active'";
$admin_result = mysqli_query($conn, $admin_query);
$active_admins = ($admin_result && mysqli_num_rows($admin_result) > 0) ? mysqli_fetch_assoc($admin_result)['count'] : 0;

// 2. Count Active Users
$user_query = "SELECT COUNT(*) as count FROM user WHERE role = 'user' AND status = 'active'";
$user_result = mysqli_query($conn, $user_query);
$active_users = ($user_result && mysqli_num_rows($user_result) > 0) ? mysqli_fetch_assoc($user_result)['count'] : 0;

// 3. Count Blocked Accounts
$blocked_query = "SELECT COUNT(*) as count FROM user WHERE status = 'blocked'";
$blocked_result = mysqli_query($conn, $blocked_query);
$blocked_count = ($blocked_result && mysqli_num_rows($blocked_result) > 0) ? mysqli_fetch_assoc($blocked_result)['count'] : 0;

// 4. Count Total Users (All roles except superadmin)
$total_query = "SELECT COUNT(*) as count FROM user WHERE role != 'superadmin'";
$total_result = mysqli_query($conn, $total_query);
$total_users = ($total_result && mysqli_num_rows($total_result) > 0) ? mysqli_fetch_assoc($total_result)['count'] : 0;

?>
<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Super Admin Dashboard - ONE PIECE</title>
    <link rel="stylesheet" href="../../css/style.css">
    <style>
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.7);
            backdrop-filter: blur(5px);
            z-index: 1000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.2s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(30, 30, 40, 0.95), rgba(20, 20, 30, 0.98));
            padding: 2rem 2.5rem;
            border-radius: 16px;
            border: 1px solid rgba(255, 255, 255, 0.1);
            box-shadow: 0 20px 60px rgba(0, 0, 0, 0.5);
            text-align: center;
            max-width: 400px;
            animation: slideUp 0.3s ease;
        }

        .modal-content h3 {
            margin-bottom: 0.5rem;
            font-size: 1.5rem;
            color: var(--text-color);
        }

        .modal-content p {
            color: var(--text-muted);
            margin-bottom: 1.5rem;
        }

        .modal-icon {
            font-size: 3rem;
            margin-bottom: 1rem;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
        }

        .modal-btn {
            padding: 0.75rem 2rem;
            border: none;
            border-radius: 8px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 600;
            transition: all 0.3s ease;
        }

        .modal-btn-cancel {
            background: rgba(255, 255, 255, 0.1);
            color: var(--text-color);
            border: 1px solid rgba(255, 255, 255, 0.2);
        }

        .modal-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.2);
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, var(--accent-red), #ff6b6b);
            color: white;
        }

        .modal-btn-confirm:hover {
            transform: scale(1.05);
            box-shadow: 0 5px 20px rgba(79, 172, 254, 0.4);
        }

        body {
            background-image:
                linear-gradient(rgba(10, 15, 25, 0.20), rgba(10, 15, 25, 0.35)),
                url('../../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
            color: #ffffff;
        }

        /* Professional Dashboard Styles */
        .hero {
            padding: 2rem 0;
            animation: fadeIn 0.6s ease;
        }

        .hero h1 {
            font-size: 2.8rem;
            font-weight: 800;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .hero p {
            color: #cbd5e1 !important;
        }

        .metric-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.35);
            border-radius: 14px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.25), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 160px;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 20px 45px rgba(245, 158, 11, 0.3);
            border-color: #fbbf24;
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 5px;
            height: 100%;
            background: linear-gradient(to bottom, #ef4444, #f59e0b);
            border-top-left-radius: 14px;
            border-bottom-left-radius: 14px;
        }

        .metric-card h3 {
            font-size: 1.05rem;
            color: #fbbf24;
            text-shadow: 0 0 10px rgba(245, 158, 11, 0.4);
            font-weight: 700;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-card p {
            font-size: 2.5rem;
            font-weight: 800;
            color: #ffffff;
            margin: 0.2rem 0;
            line-height: 1.2;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.4);
        }

        .metric-card small {
            font-size: 0.9rem;
            color: #cbd5e1;
            margin-top: 0.5rem;
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
                transform: translateY(10px);
            }

            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @keyframes slideUp {
            from {
                transform: translateY(20px);
                opacity: 0;
            }

            to {
                transform: translateY(0);
                opacity: 1;
            }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/../includes/sidebar.php';
        render_sidebar('dashboard');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="hero" style="text-align: left;">
                    <h1>Super Admin Dashboard</h1>
                    <p style="font-size: 1.2rem; margin-bottom: 2rem;">Welcome back,
                        <?php echo htmlspecialchars($_SESSION['username']); ?>.
                    </p>

                    <div class="grid-container"
                        style="display: grid; grid-template-columns: repeat(auto-fit, minmax(260px, 1fr)); gap: 1.5rem;">

                        <div class="metric-card">
                            <h3>Total Accounts</h3>
                            <p><?php echo $total_users; ?></p>
                            <small>Registered Users &amp; Admins</small>
                        </div>

                        <div class="metric-card">
                            <h3>Active Admins</h3>
                            <p><?php echo $active_admins; ?></p>
                            <small>Managing the system</small>
                        </div>

                        <div class="metric-card">
                            <h3>Active Users</h3>
                            <p><?php echo $active_users; ?></p>
                            <small>Standard users</small>
                        </div>

                        <div class="metric-card" style="cursor: pointer;" onclick="location.href='block_list.php'">
                            <h3>Blocked Accounts</h3>
                            <p><?php echo $blocked_count; ?></p>
                            <small>Restricted access</small>
                        </div>


                    </div>
                </div>
            </main>
        </div>
    </div>

    <script>
        function openLogoutModal() { openSidebarLogoutModal(); }
        function closeLogoutModal() { closeSidebarLogoutModal(); }
    </script>
</body>

</html>