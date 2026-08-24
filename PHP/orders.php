<?php
// Session check for user role access
session_start();
include 'db.php';

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Fetch user's orders
$query = "SELECT id, item_name, quantity, total_price, order_date, status FROM orders WHERE user_id = ? ORDER BY order_date DESC";
$stmt = mysqli_prepare($conn, $query);
$orders = [];

if ($stmt) {
    mysqli_stmt_bind_param($stmt, "i", $user_id);
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
    <title>My Orders - Moto Shop Online Store</title>
    <link rel="stylesheet" href="../css/style.css">
    <style>
        .orders-table {
            width: 100%;
            border-collapse: separate;
            border-spacing: 0;
            margin-top: 1rem;
        }

        .orders-table th {
            background: rgba(255, 255, 255, 0.05);
            color: var(--text-muted);
            font-weight: 600;
            text-transform: uppercase;
            font-size: 0.85rem;
            letter-spacing: 1px;
            padding: 1rem;
            text-align: left;
            border-bottom: 2px solid rgba(255, 255, 255, 0.1);
        }

        .orders-table td {
            padding: 1rem;
            border-bottom: 1px solid rgba(255, 255, 255, 0.05);
            color: var(--text-color);
            vertical-align: middle;
        }

        .orders-table tr:last-child td {
            border-bottom: none;
        }

        .orders-table tr:hover td {
            background: rgba(255, 255, 255, 0.02);
        }

        .status-badge {
            padding: 0.4rem 0.8rem;
            border-radius: 20px;
            font-size: 0.85rem;
            font-weight: 600;
            display: inline-block;
        }

        .status-pending {
            background: rgba(255, 193, 7, 0.1);
            color: var(--accent-yellow);
            border: 1px solid rgba(255, 193, 7, 0.2);
        }

        .status-shipped {
            background: rgba(0, 123, 255, 0.1);
            color: var(--accent-blue);
            border: 1px solid rgba(0, 123, 255, 0.2);
        }

        .status-delivered {
            background: rgba(40, 167, 69, 0.1);
            color: var(--accent-green);
            border: 1px solid rgba(40, 167, 69, 0.2);
        }
    </style>
</head>

<body>
    <nav class="navbar">
        <div class="navdiv">
            <div class="logo">
                <a href="home.php" style="display: flex; align-items: center; gap: 0.5rem; text-decoration: none;">
                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="15" cy="5" r="1"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
                    </svg>
                    <span style="font-weight: 900; color: #ffffff; letter-spacing: 0.5px;">MOTO<span style="color: var(--accent-orange, #ff5500); font-weight: 400;">SHOP</span></span>
                </a>
            </div>
            <ul>
                <li><a href="home.php">Store</a></li>
                <li><a href="orders.php" style="color: var(--accent-blue);">My Orders</a></li>
                <li><a href="profile.php">Profile</a></li>
                <button onclick="window.location.href='logout.php'">Log out</button>
            </ul>
        </div>
    </nav>

    <div class="center-content">
        <div class="container" style="max-width: 1000px; margin: 0 auto;">
            <div class="wrapper"
                style="background: rgba(0,0,0,0.5); padding: 3rem; border-radius: 16px; border-top: 4px solid var(--accent-green);">
                <h1 style="font-size: 2.5rem; margin-bottom: 0.5rem; color: white;">Order History</h1>
                <p style="color: var(--text-muted); margin-bottom: 2rem;">Track the status of your motoparts orders.</p>

                <div
                    style="background: rgba(30,30,40,0.8); border-radius: 12px; overflow: hidden; border: 1px solid rgba(255,255,255,0.05);">
                    <?php if (empty($orders)): ?>
                        <div style="padding: 3rem; text-align: center;">
                            <p style="color: var(--text-muted); font-size: 1.2rem;">You haven't placed any orders yet.</p>
                            <a href="home.php" class="btn-action"
                                style="display: inline-block; margin-top: 1.5rem; background: var(--accent-blue); padding: 0.8rem 2rem; border-radius: 8px; text-decoration: none;">Go
                                to Store</a>
                        </div>
                    <?php else: ?>
                        <table class="orders-table">
                            <thead>
                                <tr>
                                    <th>Order ID</th>
                                    <th>Item</th>
                                    <th>Quantity</th>
                                    <th>Total Price</th>
                                    <th>Date Ordered</th>
                                    <th>Status</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($orders as $order): ?>
                                    <tr>
                                        <td style="color: var(--text-muted);">#
                                            <?= htmlspecialchars($order['id']) ?>
                                        </td>
                                        <td style="font-weight: 600;">
                                            <?= htmlspecialchars($order['item_name']) ?>
                                        </td>
                                        <td>
                                            <?= htmlspecialchars($order['quantity']) ?>
                                        </td>
                                        <td style="color: var(--accent-green); font-weight: bold;">$
                                            <?= htmlspecialchars($order['total_price']) ?>
                                        </td>
                                        <td>
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
    </div>

    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a></p>
    </footer>

</body>

</html>