<?php
session_start();
if (!isset($_SESSION['role']) || $_SESSION['role'] !== 'superadmin') {
    header("Location: ../login.php");
    exit();
}
include(__DIR__ . "/../db.php");

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
    <title>Super Admin Dashboard - Moto Shop</title>
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
                linear-gradient(rgba(10, 12, 18, 0.88), rgba(10, 12, 18, 0.92)),
                url('../../img/moto-bg.jpg');
            background-size: cover;
            background-position: center;
            background-attachment: fixed;
        }

        /* Professional Dashboard Styles */
        .hero {
            padding: 2rem 0;
            animation: fadeIn 0.6s ease;
        }

        .hero h1 {
            font-size: 2.8rem;
            font-weight: 700;
            color: #ffffff;
            margin-bottom: 0.5rem;
            letter-spacing: -0.5px;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .metric-card {
            background: rgba(20, 30, 45, 0.6);
            backdrop-filter: blur(12px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 12px;
            padding: 1.5rem;
            display: flex;
            flex-direction: column;
            justify-content: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.2);
            transition: transform 0.3s ease, box-shadow 0.3s ease, border-color 0.3s ease;
            position: relative;
            overflow: hidden;
            min-height: 160px;
        }

        .metric-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 15px 40px rgba(0, 0, 0, 0.4);
            border-color: rgba(79, 172, 254, 0.4);
        }

        .metric-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 4px;
            height: 100%;
            background: linear-gradient(to bottom, #4facfe, #00f2fe);
            border-top-left-radius: 12px;
            border-bottom-left-radius: 12px;
        }

        .metric-card h3 {
            font-size: 1.1rem;
            color: #a0aec0;
            font-weight: 500;
            margin-bottom: 0.5rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .metric-card p {
            font-size: 2.5rem;
            font-weight: 700;
            color: #ffffff;
            margin: 0.2rem 0;
            line-height: 1.2;
        }

        .metric-card small {
            font-size: 0.9rem;
            color: #718096;
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
                <li><a href="#">Dashboard</a></li>
                <li><a href="manage_admins.php">Manage Admins</a></li>
                <li><a href="manage_users.php">Manage Users</a></li>
                <li><a href="block_list.php">Block List</a></li>
                <li><a href="audit_logs.php">Audit Logs</a></li>
                <button onclick="openLogoutModal()">Logout</button>
            </ul>
        </div>
    </nav>

    <div class="container">
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
                    <small>Registered Users & Admins</small>
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

                <div class="metric-card">
                    <h3>Blocked Accounts</h3>
                    <p><?php echo $blocked_count; ?></p>
                    <small>Restricted access</small>
                </div>

            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal -->
    <div class="modal-overlay" id="logoutModal">
        <div class="modal-content">
            <div class="modal-icon">👋</div>
            <h3>Confirm Logout</h3>
            <p>Are you sure you want to log out of your account?</p>
            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Cancel</button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmLogout()">Logout</button>
            </div>
        </div>
    </div>

    <script>
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        function confirmLogout() {
            window.location.href = '../logout.php';
        }

        // Close modal when clicking outside
        document.getElementById('logoutModal').addEventListener('click', function (e) {
            if (e.target === this) {
                closeLogoutModal();
            }
        });

        // Close modal with Escape key
        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
            }
        });
    </script>
</body>

</html>