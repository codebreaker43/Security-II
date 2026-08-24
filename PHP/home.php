<?php
// Session check for user role access
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Redirect admins and superadmins to their proper dashboards
if ($_SESSION['role'] === 'superadmin') {
    header("Location: superadmin/dashboard.php");
    exit();
} elseif ($_SESSION['role'] === 'admin') {
    header("Location: admin/dashboard.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Home - Moto Shop Online Store</title>
    <link rel="stylesheet" href="../css/style.css">
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
            box-shadow: 0 5px 20px rgba(255, 59, 59, 0.4);
        }

        @keyframes fadeIn {
            from {
                opacity: 0;
            }

            to {
                opacity: 1;
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

        .hero-section {
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            padding: 4rem 2rem;
            border-radius: 20px;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            overflow: hidden;
            margin-bottom: 2rem;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at top right, rgba(0, 123, 255, 0.15), transparent 40%),
                radial-gradient(circle at bottom left, rgba(40, 167, 69, 0.15), transparent 40%);
            z-index: 0;
            pointer-events: none;
        }

        .hero-content {
            position: relative;
            z-index: 1;
        }

        .primary-title {
            font-size: 4rem;
            margin-bottom: 0.5rem;
            font-weight: 900;
            letter-spacing: 3px;
            background: linear-gradient(to right, #ff3b3b, #00d2ff, #20c997, #ffc107);
            -webkit-background-clip: text;
            color: transparent;
            text-transform: uppercase;
        }

        .cube-card {
            width: 320px;
            background: rgba(20, 20, 30, 0.7);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border-radius: 20px;
            padding: 3rem 2rem;
            border: 1px solid rgba(255, 255, 255, 0.08);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.1);
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
        }

        .cube-card:hover {
            transform: translateY(-15px);
            box-shadow: 0 25px 50px rgba(0, 0, 0, 0.6), inset 0 1px 0 rgba(255, 255, 255, 0.2);
        }

        .cube-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }

        .card-red::before {
            background: linear-gradient(to right, var(--accent-red), #ff1a1a);
        }

        .card-blue::before {
            background: linear-gradient(to right, var(--accent-blue), #00d2ff);
        }

        .card-green::before {
            background: linear-gradient(to right, var(--accent-green), #20c997);
        }

        .cube-icon {
            width: 130px;
            height: 130px;
            border-radius: 50%;
            margin: 0 auto 1.8rem;
            display: flex;
            align-items: center;
            justify-content: center;
            border: 4px solid rgba(30, 30, 40, 0.9);
            color: white;
            font-weight: 900;
            font-size: 2.2rem;
            transition: transform 0.3s ease;
        }

        .cube-card:hover .cube-icon {
            transform: scale(1.1) rotate(5deg);
        }

        .icon-red {
            background: linear-gradient(135deg, var(--accent-red), #ff1a1a);
            box-shadow: 0 10px 25px rgba(255, 59, 59, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.4);
        }

        .icon-blue {
            background: linear-gradient(135deg, var(--accent-blue), #00d2ff);
            box-shadow: 0 10px 25px rgba(0, 123, 255, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.4);
        }

        .icon-green {
            background: linear-gradient(135deg, var(--accent-green), #20c997);
            box-shadow: 0 10px 25px rgba(40, 167, 69, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.4);
        }

        .icon-green-outer {
            transform: rotate(45deg);
        }

        .icon-green span {
            transform: rotate(-45deg);
            display: block;
        }

        .btn-order {
            width: 100%;
            border-radius: 12px;
            padding: 0.9rem;
            font-weight: 700;
            font-size: 1.1rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: auto;
        }

        .btn-order-red {
            background: linear-gradient(135deg, var(--accent-red), #ff1a1a);
            box-shadow: 0 5px 15px rgba(255, 59, 59, 0.3);
        }

        .btn-order-red:hover {
            box-shadow: 0 8px 25px rgba(255, 59, 59, 0.6);
            transform: translateY(-3px);
        }

        .btn-order-blue {
            background: linear-gradient(135deg, var(--accent-blue), #00d2ff);
            box-shadow: 0 5px 15px rgba(0, 123, 255, 0.3);
        }

        .btn-order-blue:hover {
            box-shadow: 0 8px 25px rgba(0, 123, 255, 0.6);
            transform: translateY(-3px);
        }

        .btn-order-green {
            background: linear-gradient(135deg, var(--accent-green), #20c997);
            box-shadow: 0 5px 15px rgba(40, 167, 69, 0.3);
        }

        .btn-order-green:hover {
            box-shadow: 0 8px 25px rgba(40, 167, 69, 0.6);
            transform: translateY(-3px);
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
                <li><a href="home.php" style="color: var(--accent-orange, #ff5500);">Store</a></li>
                <li><a href="orders.php">My Orders</a></li>
                <li><a href="profile.php">Profile</a></li>
                <button onclick="openLogoutModal()">Log out</button>
            </ul>
        </div>
    </nav>

    <div class="center-content">
        <div class="container" style="text-align: center; max-width: 1100px; margin: 0 auto; padding: 2rem 0;">
            <div class="hero-section">
                <div class="hero-content">
                    <h1 class="primary-title" style="color: var(--accent-orange, #ff5500);">FEATURED PARTS</h1>
                    <h2 style="font-size: 2.2rem; color: white; margin-bottom: 1rem; font-weight: 700;">PREMIUM MOTORCYCLE PARTS & ACCESSORIES</h2>
                    <p style="color: #8892b0; margin-bottom: 3.5rem; font-size: 1.1rem;">Explore our lineup of performance exhausts, brake kits, and racing gear. Click "Order" to purchase.</p>

                    <div
                        style="display: flex; flex-direction: row; justify-content: center; flex-wrap: wrap; gap: 2.5rem; width: 100%; margin: 0 auto;">

                        <!-- Pro Racing Exhaust -->
                        <div class="cube-card card-red">
                            <div class="cube-icon icon-red" style="font-size: 2.5rem;">
                                <span>🏍️</span>
                            </div>
                            <h3 style="color: white; margin-bottom: 0.5rem; font-size: 1.5rem; font-weight: 700;">Pro Racing Exhaust</h3>
                            <p style="color: #e6f1ff; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">
                                $189.99</p>
                            <button class="btn-order btn-order-red"
                                onclick="orderCube('Pro Racing Exhaust', 189.99)">Order Now</button>
                        </div>

                        <!-- Brembo Brake Kit -->
                        <div class="cube-card card-blue">
                            <div class="cube-icon icon-blue" style="font-size: 2.5rem;">
                                <span>⚙️</span>
                            </div>
                            <h3 style="color: white; margin-bottom: 0.5rem; font-size: 1.5rem; font-weight: 700;">Brembo Brake Kit</h3>
                            <p style="color: #e6f1ff; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">
                                $129.99</p>
                            <button class="btn-order btn-order-blue"
                                onclick="orderCube('Brembo Brake Kit', 129.99)">Order Now</button>
                        </div>

                        <!-- Carbon Helmet Pro -->
                        <div class="cube-card card-green">
                            <div class="cube-icon icon-green" style="font-size: 2.5rem;">
                                <span>🛡️</span>
                            </div>
                            <h3
                                style="color: white; margin-bottom: 0.5rem; font-size: 1.5rem; font-weight: 700;">
                                Carbon Helmet Pro</h3>
                            <p style="color: #e6f1ff; font-size: 1.5rem; font-weight: 800; margin-bottom: 1.5rem;">
                                $159.99</p>
                            <button class="btn-order btn-order-green"
                                onclick="orderCube('Carbon Helmet Pro', 159.99)">Order Now</button>
                        </div>

                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Order Toast Notification -->
    <div id="toast"
        style="display: none; position: fixed; bottom: 20px; right: 20px; background: var(--accent-green); color: white; padding: 1rem 2rem; border-radius: 8px; font-weight: bold; box-shadow: 0 10px 20px rgba(0,0,0,0.5); z-index: 2000;">
        Order placed successfully!
    </div>

    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
        <p><a href="#">Privacy Policy</a></p>
    </footer>

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
            window.location.href = 'logout.php';
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

        // Ordering Function
        function orderCube(itemName, price) {
            const data = {
                item_name: itemName,
                price: price,
                quantity: 1
            };

            fetch('api/order_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(res => {
                    const toast = document.getElementById('toast');
                    if (res.success) {
                        toast.style.background = 'var(--accent-green)';
                        toast.textContent = "✅ " + itemName + " ordered successfully!";
                    } else {
                        toast.style.background = 'var(--accent-red)';
                        toast.textContent = "❌ Failed: " + (res.error || "Could not place order");
                    }

                    toast.style.display = 'block';
                    setTimeout(() => {
                        toast.style.display = 'none';
                    }, 3000);
                })
                .catch(err => {
                    alert("Error placing order: " + err);
                });
        }
    </script>
</body>

</html>