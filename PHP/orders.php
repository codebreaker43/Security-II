<?php
// Session check for user role access
session_start();
require_once __DIR__ . '/db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/includes/auth_guard.php';
enforce_profile_completion($conn);

// Fetch user's orders
$query = "SELECT id, item_name, quantity, total_price, order_date, status FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $query);
$orders = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "s", $user_id);
    mysqli_stmt_execute($stmt);
    $result = mysqli_stmt_get_result($stmt);
    while ($row = mysqli_fetch_assoc($result)) {
        $orders[] = $row;
    }
    mysqli_stmt_close($stmt);
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Loot & Treasure Orders - ONE PIECE</title>
    <link rel="stylesheet" href="../css/style.css?v=<?= time() ?>">
    <style>
        .orders-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            padding: 3rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            color: #ffffff;
            position: relative;
            overflow: hidden;
        }

        .orders-header-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.4);
            color: #fbbf24;
            font-size: 0.8rem;
            font-weight: 800;
            padding: 0.35rem 0.9rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1rem;
        }

        .orders-table-wrap {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
            border-radius: 14px;
            overflow: hidden;
            border: 1px solid rgba(255, 255, 255, 0.2);
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
        }

        .orders-table th {
            background: rgba(245, 158, 11, 0.15);
            color: #fbbf24;
            font-weight: 800;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            padding: 1.1rem 1.25rem;
            text-align: left;
            border-bottom: 1px solid rgba(255, 255, 255, 0.18);
        }

        .orders-table td {
            padding: 1.1rem 1.25rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.08);
            color: #ffffff;
            vertical-align: middle;
            font-size: 0.95rem;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover td {
            background: rgba(255, 255, 255, 0.07);
        }

        .status-badge {
            padding: 0.4rem 0.85rem;
            border-radius: 20px;
            font-size: 0.82rem;
            font-weight: 700;
            display: inline-flex;
            align-items: center;
            gap: 0.35rem;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .status-pending {
            background: rgba(245, 158, 11, 0.25);
            color: #fde047;
            border: 1px solid rgba(245, 158, 11, 0.5);
        }

        .status-shipped {
            background: rgba(14, 165, 233, 0.25);
            color: #7dd3fc;
            border: 1px solid rgba(14, 165, 233, 0.5);
        }

        .status-delivered {
            background: rgba(16, 185, 129, 0.25);
            color: #86efac;
            border: 1px solid rgba(16, 185, 129, 0.5);
        }

        .status-cancelled {
            background: rgba(239, 68, 68, 0.25);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.5);
        }

        .btn-bazaar {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #ffffff;
            font-weight: 800;
            padding: 0.85rem 2rem;
            border-radius: 12px;
            text-decoration: none;
            box-shadow: 0 8px 25px rgba(245, 158, 11, 0.4);
            transition: all 0.3s ease;
        }

        .btn-bazaar:hover {
            transform: translateY(-2px);
            box-shadow: 0 12px 30px rgba(245, 158, 11, 0.6);
            color: #ffffff;
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/includes/sidebar.php';
        render_sidebar('orders');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="container" style="max-width: 1100px; margin: 0 auto; padding: 1.5rem 0;">
                    <div class="orders-card">
                        <div class="orders-header-badge">
                            <span>🏴‍☠️</span> Grand Line Cargo Fleet
                        </div>
                        <h1 style="font-size: 2.6rem; margin-bottom: 0.5rem; color: #ffffff; font-weight: 900; text-shadow: 0 2px 10px rgba(0,0,0,0.5);">
                            Loot & Treasure Orders
                        </h1>
                        <p style="color: #cbd5e1; margin-bottom: 2rem; font-size: 1.05rem;">
                            Track the delivery status of your Devil Fruits, pirate vessels, and Meito blades across the Grand Line.
                        </p>

                        <div class="orders-table-wrap">
                            <?php if (empty($orders)): ?>
                                <div style="padding: 4rem 2rem; text-align: center;">
                                    <div style="font-size: 3.5rem; margin-bottom: 1rem;">📦⚓</div>
                                    <h3 style="color: #ffffff; font-size: 1.5rem; margin-bottom: 0.5rem; font-weight: 800;">No Treasures Ordered Yet</h3>
                                    <p style="color: #94a3b8; font-size: 1.05rem; max-width: 500px; margin: 0 auto 1.75rem;">
                                        Your cargo hold is currently empty. Visit the Grand Line Bazaar to claim legendary Devil Fruits, Ships, and Meito Swords!
                                    </p>
                                    <a href="home.php" class="btn-bazaar">
                                        <span>⚔️</span> Explore Grand Line Bazaar
                                    </a>
                                </div>
                            <?php else: ?>
                                <table class="orders-table">
                                    <thead>
                                        <tr>
                                            <th>Order #</th>
                                            <th>Treasure Item</th>
                                            <th>Quantity</th>
                                            <th>Total Bounty</th>
                                            <th>Date Placed</th>
                                            <th>Voyage Status</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($orders as $order): ?>
                                            <tr>
                                                <td style="color: #94a3b8; font-weight: 700;">
                                                    #<?= htmlspecialchars($order['id']) ?>
                                                </td>
                                                <td style="font-weight: 700; color: #ffffff;">
                                                    <?= htmlspecialchars($order['item_name']) ?>
                                                </td>
                                                <td style="color: #e2e8f0;">
                                                    <?= htmlspecialchars($order['quantity']) ?>x
                                                </td>
                                                <td style="color: #fbbf24; font-weight: 800; font-size: 1.05rem; text-shadow: 0 0 10px rgba(245, 158, 11, 0.3);">
                                                    $<?= number_format($order['total_price'], 2) ?>
                                                </td>
                                                <td style="color: #cbd5e1; font-size: 0.9rem;">
                                                    <?= date('M d, Y - h:i A', strtotime($order['order_date'])) ?>
                                                </td>
                                                <td>
                                                    <span class="status-badge status-<?= htmlspecialchars($order['status']) ?>">
                                                        <?= ucfirst(htmlspecialchars($order['status'])) ?>
                                                    </span>
                                                </td>
                                            </tr>
                                        <?php endforeach; ?>
                                    </tbody>
                                </table>
                            <?php endif; ?>
                        </div>

                    </div>
                </div>
            </main>

            <footer style="text-align: center; padding: 2rem 0; color: #94a3b8; font-size: 0.9rem;">
                <p>&copy; 2024 ONE PIECE Pirate Fleet. All rights reserved.</p>
            </footer>
        </div>
    </div>
</body>

</html>