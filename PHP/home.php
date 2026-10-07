<?php
// Session check for user role access
session_start();

// Redirect to login if not authenticated
if (!isset($_SESSION['role']) || !isset($_SESSION['user_id'])) {
    header("Location: login.php");
    exit();
}

// Guard: Require profile completion before accessing features
require_once __DIR__ . '/db.php';
require_once __DIR__ . '/includes/auth_guard.php';
enforce_profile_completion($conn);

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
    <title>Grand Line Bazaar & Armory - ONE PIECE</title>
    <link rel="stylesheet" href="../css/style.css?v=<?= time() ?>">
    <style>
        /* Base Glass Layout & Modals */
        .modal-overlay {
            display: none;
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.75);
            backdrop-filter: blur(10px);
            -webkit-backdrop-filter: blur(10px);
            z-index: 2000;
            justify-content: center;
            align-items: center;
            animation: fadeIn 0.25s ease;
        }

        .modal-overlay.active {
            display: flex;
        }

        .modal-content {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.22) 0%, rgba(255, 255, 255, 0.10) 100%);
            backdrop-filter: blur(30px) saturate(180%);
            -webkit-backdrop-filter: blur(30px) saturate(180%);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            border-radius: 24px;
            box-shadow: 0 25px 60px rgba(0, 0, 0, 0.5), inset 0 0 0 1px rgba(255, 255, 255, 0.2);
            padding: 2.5rem 2.5rem;
            text-align: center;
            max-width: 460px;
            width: 90%;
            animation: slideUp 0.3s cubic-bezier(0.16, 1, 0.3, 1);
            color: #ffffff;
            position: relative;
        }

        .modal-content h3 {
            margin-bottom: 0.5rem;
            font-size: 1.6rem;
            color: #ffffff;
            font-weight: 800;
            text-shadow: 0 2px 10px rgba(0, 0, 0, 0.5);
        }

        .modal-content p {
            color: #cbd5e1;
            margin-bottom: 1.5rem;
            font-size: 0.98rem;
            line-height: 1.5;
        }

        .modal-buttons {
            display: flex;
            gap: 1rem;
            justify-content: center;
            margin-top: 1.5rem;
        }

        .modal-btn {
            padding: 0.85rem 1.8rem;
            border: none;
            border-radius: 12px;
            cursor: pointer;
            font-size: 1rem;
            font-weight: 700;
            transition: all 0.3s ease;
            flex: 1;
        }

        .modal-btn-cancel {
            background: rgba(255, 255, 255, 0.12);
            color: #ffffff;
            border: 1px solid rgba(255, 255, 255, 0.3);
            backdrop-filter: blur(10px);
        }

        .modal-btn-cancel:hover {
            background: rgba(255, 255, 255, 0.22);
            color: #fbbf24;
            border-color: #fbbf24;
        }

        .modal-btn-confirm {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: white;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .modal-btn-confirm:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.6);
        }

        @keyframes fadeIn {
            from { opacity: 0; }
            to { opacity: 1; }
        }

        @keyframes slideUp {
            from { transform: translateY(20px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        /* Hero Section */
        .hero-section {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            padding: 3.5rem 2rem 3rem;
            border-radius: 24px;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            position: relative;
            overflow: hidden;
            margin-bottom: 2.5rem;
            color: #ffffff;
            text-align: center;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: radial-gradient(circle at 20% 20%, rgba(245, 158, 11, 0.15), transparent 45%),
                        radial-gradient(circle at 80% 80%, rgba(239, 68, 68, 0.12), transparent 45%);
            pointer-events: none;
        }

        .hero-badge {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.2);
            border: 1px solid rgba(245, 158, 11, 0.45);
            color: #fbbf24;
            font-size: 0.85rem;
            font-weight: 800;
            padding: 0.4rem 1.1rem;
            border-radius: 30px;
            text-transform: uppercase;
            letter-spacing: 1.5px;
            margin-bottom: 1.25rem;
            box-shadow: 0 0 15px rgba(245, 158, 11, 0.2);
        }

        .primary-title {
            font-size: 3.5rem;
            margin-bottom: 0.6rem;
            font-weight: 900;
            letter-spacing: 2px;
            color: #fbbf24 !important;
            text-shadow: 0 0 25px rgba(245, 158, 11, 0.5), 0 2px 10px rgba(0, 0, 0, 0.6);
            text-transform: uppercase;
            line-height: 1.1;
        }

        .hero-subtitle {
            font-size: 1.45rem;
            color: #ffffff;
            margin-bottom: 1rem;
            font-weight: 800;
            letter-spacing: 0.5px;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        }

        .hero-desc {
            color: #cbd5e1;
            max-width: 780px;
            margin: 0 auto 2rem;
            font-size: 1.08rem;
            line-height: 1.6;
            text-shadow: 0 1px 4px rgba(0, 0, 0, 0.5);
        }

        /* Filter Tabs */
        .filter-tabs-bar {
            display: flex;
            justify-content: center;
            flex-wrap: wrap;
            gap: 0.75rem;
            margin-bottom: 2rem;
        }

        .filter-tab-btn {
            background: rgba(255, 255, 255, 0.12);
            border: 1px solid rgba(255, 255, 255, 0.25);
            color: #e2e8f0;
            font-size: 0.95rem;
            font-weight: 700;
            padding: 0.65rem 1.35rem;
            border-radius: 30px;
            cursor: pointer;
            transition: all 0.3s ease;
            backdrop-filter: blur(10px);
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
        }

        .filter-tab-btn:hover {
            background: rgba(245, 158, 11, 0.25);
            color: #ffffff;
            border-color: rgba(245, 158, 11, 0.5);
            transform: translateY(-2px);
        }

        .filter-tab-btn.active {
            background: linear-gradient(135deg, #f59e0b, #ea580c);
            color: #ffffff;
            border-color: #fbbf24;
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.45);
        }

        /* Product Cards Grid */
        .products-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(320px, 1fr));
            gap: 2.25rem;
            width: 100%;
            margin: 0 auto;
        }

        .cube-card {
            background: linear-gradient(135deg, rgba(255, 255, 255, 0.20) 0%, rgba(255, 255, 255, 0.08) 100%);
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border-radius: 22px;
            padding: 2.25rem 1.85rem;
            border: 1px solid rgba(255, 255, 255, 0.35);
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.3), inset 0 0 0 1px rgba(255, 255, 255, 0.15);
            position: relative;
            transition: all 0.4s cubic-bezier(0.175, 0.885, 0.32, 1.275);
            overflow: hidden;
            display: flex;
            flex-direction: column;
            align-items: center;
            color: #ffffff;
            text-align: center;
        }

        .cube-card:hover {
            transform: translateY(-10px);
            box-shadow: 0 22px 45px rgba(245, 158, 11, 0.35);
            border-color: rgba(251, 191, 36, 0.6);
        }

        /* Category accent top stripes */
        .cube-card::before {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            width: 100%;
            height: 5px;
        }

        .card-fruit::before {
            background: linear-gradient(to right, #ea580c, #ef4444);
        }

        .card-ship::before {
            background: linear-gradient(to right, #f59e0b, #38bdf8);
        }

        .card-sword::before {
            background: linear-gradient(to right, #8b5cf6, #06b6d4);
        }

        /* Item Category Badge */
        .card-cat-badge {
            font-size: 0.72rem;
            font-weight: 800;
            padding: 0.3rem 0.85rem;
            border-radius: 20px;
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-bottom: 1.5rem;
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
        }

        .badge-fruit {
            background: rgba(239, 68, 68, 0.22);
            color: #fca5a5;
            border: 1px solid rgba(239, 68, 68, 0.45);
        }

        .badge-ship {
            background: rgba(245, 158, 11, 0.22);
            color: #fde047;
            border: 1px solid rgba(245, 158, 11, 0.45);
        }

        .badge-sword {
            background: rgba(139, 92, 246, 0.22);
            color: #c4b5fd;
            border: 1px solid rgba(139, 92, 246, 0.45);
        }

        /* Circular Icons */
        .cube-icon {
            width: 120px;
            height: 120px;
            border-radius: 50%;
            margin: 0 auto 1.5rem;
            display: flex;
            align-items: center;
            justify-content: center;
            position: relative;
            border: 3px solid rgba(255, 255, 255, 0.3);
            transition: transform 0.3s ease;
        }

        .cube-card:hover .cube-icon {
            transform: scale(1.1) rotate(6deg);
        }

        .icon-fruit {
            background: radial-gradient(circle at 35% 35%, #f97316, #dc2626);
            box-shadow: 0 10px 25px rgba(234, 88, 12, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.5);
        }

        .icon-ship {
            background: radial-gradient(circle at 35% 35%, #fbbf24, #d97706);
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.5);
        }

        .icon-sword {
            background: radial-gradient(circle at 35% 35%, #8b5cf6, #0284c7);
            box-shadow: 0 10px 25px rgba(139, 92, 246, 0.5), inset 0 2px 8px rgba(255, 255, 255, 0.5);
        }

        .icon-symbol {
            font-size: 3.2rem;
            filter: drop-shadow(0 4px 10px rgba(0, 0, 0, 0.4));
        }

        .card-title {
            color: #ffffff;
            margin-bottom: 0.4rem;
            font-size: 1.55rem;
            font-weight: 800;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
        }

        .card-class {
            color: #fbbf24;
            font-size: 0.88rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.8px;
            margin-bottom: 0.85rem;
        }

        .card-desc {
            color: #cbd5e1;
            font-size: 0.92rem;
            line-height: 1.5;
            margin-bottom: 1.5rem;
            min-height: 58px;
            text-shadow: 0 1px 3px rgba(0, 0, 0, 0.5);
        }

        /* Price Box */
        .card-price-wrap {
            margin-bottom: 1.5rem;
            display: flex;
            flex-direction: column;
            align-items: center;
            gap: 0.2rem;
        }

        .card-beli {
            font-size: 1.6rem;
            font-weight: 900;
            color: #fbbf24;
            text-shadow: 0 0 12px rgba(245, 158, 11, 0.5);
            letter-spacing: 0.5px;
        }

        .card-usd {
            font-size: 0.82rem;
            color: #94a3b8;
            font-weight: 600;
        }

        /* Order Buttons */
        .btn-order {
            width: 100%;
            border-radius: 12px;
            padding: 0.95rem;
            font-weight: 800;
            font-size: 1.05rem;
            text-transform: uppercase;
            letter-spacing: 1px;
            border: none;
            color: white;
            cursor: pointer;
            transition: all 0.3s ease;
            margin-top: auto;
            display: inline-flex;
            align-items: center;
            justify-content: center;
            gap: 0.5rem;
        }

        .btn-order-fruit {
            background: linear-gradient(135deg, #ea580c, #dc2626);
            box-shadow: 0 6px 20px rgba(234, 88, 12, 0.4);
        }

        .btn-order-fruit:hover {
            box-shadow: 0 10px 25px rgba(234, 88, 12, 0.7);
            transform: translateY(-3px);
        }

        .btn-order-ship {
            background: linear-gradient(135deg, #f59e0b, #d97706);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.4);
        }

        .btn-order-ship:hover {
            box-shadow: 0 10px 25px rgba(245, 158, 11, 0.7);
            transform: translateY(-3px);
        }

        .btn-order-sword {
            background: linear-gradient(135deg, #7c3aed, #0284c7);
            box-shadow: 0 6px 20px rgba(124, 58, 237, 0.4);
        }

        .btn-order-sword:hover {
            box-shadow: 0 10px 25px rgba(124, 58, 237, 0.7);
            transform: translateY(-3px);
        }

        /* Order Confirmation Modal Quantity Selector */
        .qty-control-box {
            display: inline-flex;
            align-items: center;
            background: rgba(255, 255, 255, 0.15);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 12px;
            padding: 0.3rem 0.5rem;
            margin: 1rem 0;
            gap: 1rem;
        }

        .qty-btn {
            width: 36px;
            height: 36px;
            border-radius: 8px;
            border: none;
            background: rgba(255, 255, 255, 0.2);
            color: #ffffff;
            font-size: 1.2rem;
            font-weight: 800;
            cursor: pointer;
            transition: all 0.2s ease;
            display: flex;
            align-items: center;
            justify-content: center;
        }

        .qty-btn:hover {
            background: #fbbf24;
            color: #0f172a;
        }

        .qty-display {
            font-size: 1.25rem;
            font-weight: 800;
            color: #ffffff;
            min-width: 32px;
            text-align: center;
        }

        /* Floating Toast Notification */
        .toast-notification {
            display: none;
            position: fixed;
            top: 25px;
            right: 25px;
            background: linear-gradient(135deg, rgba(20, 30, 50, 0.95), rgba(15, 23, 42, 0.95));
            backdrop-filter: blur(25px) saturate(180%);
            -webkit-backdrop-filter: blur(25px) saturate(180%);
            border: 1.5px solid #10b981;
            box-shadow: 0 15px 35px rgba(0, 0, 0, 0.5), 0 0 20px rgba(16, 185, 129, 0.3);
            color: #ffffff;
            padding: 1.1rem 1.6rem;
            border-radius: 16px;
            z-index: 9999;
            animation: slideInRight 0.35s cubic-bezier(0.16, 1, 0.3, 1);
            max-width: 380px;
        }

        @keyframes slideInRight {
            from { transform: translateX(100px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }
    </style>
</head>

<body>
    <div class="app-layout">
        <?php
        include_once __DIR__ . '/includes/sidebar.php';
        render_sidebar('store');
        ?>

        <div class="app-main">
            <main class="main-content">
                <div class="container" style="max-width: 1180px; margin: 0 auto; padding: 1rem 0;">
                    
                    <!-- Grand Line Bazaar Hero Section -->
                    <div class="hero-section">
                        <div class="hero-badge">
                            <span>⚓</span> Grand Line Bazaar • Straw Hat Treasury
                        </div>
                        <h1 class="primary-title">PIRATE TREASURES & ARTIFACTS</h1>
                        <h2 class="hero-subtitle">LEGENDARY DEVIL FRUITS, GRAND LINE SHIPS & MEITO SWORDS</h2>
                        <p class="hero-desc">
                            Equip your pirate crew with mythical artifacts from across the Grand Line and New World. 
                            Wield supreme Devil Fruit abilities, navigate the seas with legendary vessels, and master Great Grade Meito swords.
                        </p>

                        <!-- Filter Tabs -->
                        <div class="filter-tabs-bar">
                            <button class="filter-tab-btn active" onclick="filterTreasures('all', this)">
                                <span>🏴‍☠️</span> All Treasures (6)
                            </button>
                            <button class="filter-tab-btn" onclick="filterTreasures('fruit', this)">
                                <span>🍈</span> Devil Fruits
                            </button>
                            <button class="filter-tab-btn" onclick="filterTreasures('ship', this)">
                                <span>⛵</span> Grand Line Ships
                            </button>
                            <button class="filter-tab-btn" onclick="filterTreasures('sword', this)">
                                <span>⚔️</span> Meito Swords
                            </button>
                        </div>
                    </div>

                    <!-- Products Grid -->
                    <div class="products-grid" id="productsGrid">

                        <!-- Item 1: Devil Fruit - Mera Mera no Mi -->
                        <div class="cube-card card-fruit" data-category="fruit">
                            <div class="card-cat-badge badge-fruit">
                                <span>🔥</span> Devil Fruit • Logia
                            </div>
                            <div class="cube-icon icon-fruit">
                                <span class="icon-symbol">🍈</span>
                            </div>
                            <h3 class="card-title">Mera Mera no Mi</h3>
                            <div class="card-class">Logia Class • Flame Emperor</div>
                            <p class="card-desc">
                                Grants the supernatural power to create, control, and transform into pure blazing fire. Formerly wielded by "Fire Fist" Ace, now inherited by Sabo.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 500,000,000</div>
                                <div class="card-usd">Equivalent: $499.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-fruit" 
                                    onclick="openOrderModal('Mera Mera no Mi (Flame-Flame Fruit)', 499.99, '฿ 500,000,000', '🍈')">
                                <span>🔥</span> Claim Devil Fruit
                            </button>
                        </div>

                        <!-- Item 2: Pirate Ship - Thousand Sunny -->
                        <div class="cube-card card-ship" data-category="ship">
                            <div class="card-cat-badge badge-ship">
                                <span>⛵</span> Pirate Vessel • Flagship
                            </div>
                            <div class="cube-icon icon-ship">
                                <span class="icon-symbol">⛵</span>
                            </div>
                            <h3 class="card-title">Thousand Sunny</h3>
                            <div class="card-class">Brigantine • Adam Wood Hull</div>
                            <p class="card-desc">
                                Mastercrafted by Franky using the mythical Treasure Tree Adam. Features the Soldier Dock System, Gaon Cannon, and Coup de Burst air propulsion.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 1,500,000,000</div>
                                <div class="card-usd">Equivalent: $1,299.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-ship" 
                                    onclick="openOrderModal('Thousand Sunny (Flagship Vessel)', 1299.99, '฿ 1,500,000,000', '⛵')">
                                <span>🦁</span> Commission Ship
                            </button>
                        </div>

                        <!-- Item 3: Legendary Sword - Enma Katana -->
                        <div class="cube-card card-sword" data-category="sword">
                            <div class="card-cat-badge badge-sword">
                                <span>⚔️</span> 21 Great Grade Meito
                            </div>
                            <div class="cube-icon icon-sword">
                                <span class="icon-symbol">⚔️</span>
                            </div>
                            <h3 class="card-title">Enma Katana</h3>
                            <div class="card-class">O Wazamono • Ryuo Blade</div>
                            <p class="card-desc">
                                Masterpiece forged by Shimotsuki Kozaburo. Wielded by Kozuki Oden to wound Kaido, and mastered by Roronoa Zoro to unleash supreme Conqueror's Haki.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 800,000,000</div>
                                <div class="card-usd">Equivalent: $799.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-sword" 
                                    onclick="openOrderModal('Enma Katana (21 Great Grade Meito)', 799.99, '฿ 800,000,000', '⚔️')">
                                <span>⚡</span> Forge Meito Blade
                            </button>
                        </div>

                        <!-- Item 4: Devil Fruit - Gomu Gomu no Mi / Nika -->
                        <div class="cube-card card-fruit" data-category="fruit">
                            <div class="card-cat-badge badge-fruit">
                                <span>☀️</span> Mythical Zoan • Sun God
                            </div>
                            <div class="cube-icon icon-fruit" style="background: radial-gradient(circle at 35% 35%, #fbbf24, #f59e0b);">
                                <span class="icon-symbol">☀️</span>
                            </div>
                            <h3 class="card-title">Hito Hito: Model Nika</h3>
                            <div class="card-class">Mythical Zoan • Warrior of Liberation</div>
                            <p class="card-desc">
                                Grants rubber body properties and reality-bending cartoon freedom. Awakens the Drum of Liberation and the Sun God Nika Gear 5 transformation.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 3,000,000,000</div>
                                <div class="card-usd">Equivalent: $2,999.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-fruit" 
                                    onclick="openOrderModal('Gomu Gomu no Mi (Model Nika)', 2999.99, '฿ 3,000,000,000', '☀️')">
                                <span>✨</span> Claim Sun God Fruit
                            </button>
                        </div>

                        <!-- Item 5: Pirate Ship - Going Merry -->
                        <div class="cube-card card-ship" data-category="ship">
                            <div class="card-cat-badge badge-ship">
                                <span>🐑</span> Caravel of Memories
                            </div>
                            <div class="cube-icon icon-ship" style="background: radial-gradient(circle at 35% 35%, #38bdf8, #0284c7);">
                                <span class="icon-symbol">🐑</span>
                            </div>
                            <h3 class="card-title">Going Merry</h3>
                            <div class="card-class">Caravel • Spirit of Klabautermann</div>
                            <p class="card-desc">
                                The beloved first ship of the Straw Hat Pirates. Blessed with an incarnate soul, carrying the crew through East Blue, the Grand Line, and Sky Island.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 250,000,000</div>
                                <div class="card-usd">Equivalent: $249.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-ship" 
                                    onclick="openOrderModal('Going Merry (Caravel of Memories)', 249.99, '฿ 250,000,000', '🐑')">
                                <span>🌊</span> Commission Caravel
                            </button>
                        </div>

                        <!-- Item 6: Legendary Sword - Wado Ichimonji -->
                        <div class="cube-card card-sword" data-category="sword">
                            <div class="card-cat-badge badge-sword">
                                <span>🗡️</span> 21 Great Grade Meito
                            </div>
                            <div class="cube-icon icon-sword" style="background: radial-gradient(circle at 35% 35%, #e2e8f0, #64748b);">
                                <span class="icon-symbol">🗡️</span>
                            </div>
                            <h3 class="card-title">Wado Ichimonji</h3>
                            <div class="card-class">O Wazamono • White Heritage</div>
                            <p class="card-desc">
                                Kuina's treasured sword inherited by Roronoa Zoro to fulfill his vow of becoming the World's Greatest Swordsman. Forged with pure white tsuka and scabbard.
                            </p>
                            <div class="card-price-wrap">
                                <div class="card-beli">฿ 650,000,000</div>
                                <div class="card-usd">Equivalent: $649.99 USD</div>
                            </div>
                            <button class="btn-order btn-order-sword" 
                                    onclick="openOrderModal('Wado Ichimonji (Heritage Meito)', 649.99, '฿ 650,000,000', '🗡️')">
                                <span>⚔️</span> Forge Meito Blade
                            </button>
                        </div>

                    </div>
                </div>
            </main>

            <footer style="text-align: center; padding: 2.5rem 0; color: #94a3b8; font-size: 0.92rem;">
                <p>&copy; 2024 ONE PIECE Pirate Fleet. All rights reserved.</p>
            </footer>
        </div>
    </div>

    <!-- Order Confirmation Modal -->
    <div class="modal-overlay" id="orderModal">
        <div class="modal-content">
            <div id="modalItemIcon" style="font-size: 3.5rem; margin-bottom: 0.85rem;">📦</div>
            <h3 id="modalItemTitle">Confirm Treasure Order</h3>
            <p id="modalItemBeli" style="color: #fbbf24; font-weight: 800; font-size: 1.25rem; margin-bottom: 0.5rem;"></p>
            <p id="modalItemUSD" style="color: #94a3b8; font-size: 0.88rem; margin-bottom: 1.25rem;"></p>

            <div style="margin-bottom: 1rem;">
                <label style="color: #cbd5e1; font-size: 0.85rem; font-weight: 700; text-transform: uppercase; letter-spacing: 0.8px; display: block; margin-bottom: 0.5rem;">Select Quantity</label>
                <div class="qty-control-box">
                    <button class="qty-btn" type="button" onclick="adjustQty(-1)">−</button>
                    <span class="qty-display" id="qtyDisplay">1</span>
                    <button class="qty-btn" type="button" onclick="adjustQty(1)">+</button>
                </div>
            </div>

            <div style="background: rgba(0, 0, 0, 0.25); border-radius: 12px; padding: 0.75rem 1rem; margin-bottom: 1rem;">
                <span style="color: #94a3b8; font-size: 0.85rem;">Total Payment: </span>
                <span id="modalTotalUSD" style="color: #10b981; font-weight: 800; font-size: 1.15rem;"></span>
            </div>

            <div class="modal-buttons">
                <button class="modal-btn modal-btn-cancel" onclick="closeOrderModal()">Cancel</button>
                <button class="modal-btn modal-btn-confirm" id="btnConfirmOrder" onclick="submitTreasureOrder()">Confirm Order</button>
            </div>
        </div>
    </div>

    <!-- Global Toast Notification -->
    <div class="toast-notification" id="toast">
        <div style="display: flex; align-items: center; gap: 0.85rem;">
            <div style="font-size: 2rem;" id="toastIcon">✅</div>
            <div>
                <div style="font-weight: 800; font-size: 1rem; color: #ffffff;" id="toastTitle">Order Placed!</div>
                <div style="font-size: 0.88rem; color: #cbd5e1;" id="toastMessage">Your cargo has been secured.</div>
                <div style="margin-top: 0.4rem;">
                    <a href="orders.php" style="color: #fbbf24; font-weight: 700; font-size: 0.85rem; text-decoration: underline;">View in Loot & Orders →</a>
                </div>
            </div>
        </div>
    </div>

    <!-- Logout Confirmation Modal (One Piece Pirate Theme) -->
    <div class="modal-overlay" id="logoutModal">
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
                <button class="modal-btn modal-btn-cancel" onclick="closeLogoutModal()">Stay on Board</button>
                <button class="modal-btn modal-btn-confirm" onclick="confirmLogout()">Log Out</button>
            </div>
        </div>
    </div>

    <script>
        // Category Filter Function
        function filterTreasures(category, btn) {
            const tabs = document.querySelectorAll('.filter-tab-btn');
            tabs.forEach(t => t.classList.remove('active'));
            if (btn) btn.classList.add('active');

            const cards = document.querySelectorAll('.cube-card');
            cards.forEach(card => {
                if (category === 'all' || card.getAttribute('data-category') === category) {
                    card.style.display = 'flex';
                } else {
                    card.style.display = 'none';
                }
            });
        }

        // Active Order State
        let currentOrder = {
            itemName: '',
            unitPrice: 0,
            beliFormatted: '',
            icon: '📦',
            quantity: 1
        };

        // Modal Controls
        function openOrderModal(itemName, unitPrice, beliFormatted, icon) {
            currentOrder.itemName = itemName;
            currentOrder.unitPrice = unitPrice;
            currentOrder.beliFormatted = beliFormatted;
            currentOrder.icon = icon;
            currentOrder.quantity = 1;

            document.getElementById('modalItemIcon').textContent = icon;
            document.getElementById('modalItemTitle').textContent = itemName;
            document.getElementById('modalItemBeli').textContent = beliFormatted;
            document.getElementById('modalItemUSD').textContent = 'Unit Price: $' + unitPrice.toFixed(2) + ' USD';
            document.getElementById('qtyDisplay').textContent = '1';
            updateModalTotal();

            document.getElementById('orderModal').classList.add('active');
        }

        function closeOrderModal() {
            document.getElementById('orderModal').classList.remove('active');
        }

        function adjustQty(change) {
            let newQty = currentOrder.quantity + change;
            if (newQty < 1) newQty = 1;
            if (newQty > 10) newQty = 10;
            currentOrder.quantity = newQty;
            document.getElementById('qtyDisplay').textContent = newQty;
            updateModalTotal();
        }

        function updateModalTotal() {
            const total = currentOrder.unitPrice * currentOrder.quantity;
            document.getElementById('modalTotalUSD').textContent = '$' + total.toFixed(2) + ' USD';
        }

        // Submit Order to API
        function submitTreasureOrder() {
            const confirmBtn = document.getElementById('btnConfirmOrder');
            confirmBtn.disabled = true;
            confirmBtn.textContent = 'Securing Bounty...';

            const payload = {
                item_name: currentOrder.itemName,
                price: currentOrder.unitPrice,
                quantity: currentOrder.quantity
            };

            fetch('api/order_api.php', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
            .then(res => res.json())
            .then(data => {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Order';
                closeOrderModal();

                if (data.success) {
                    showToast('✅ Order Confirmed!', currentOrder.quantity + 'x ' + currentOrder.itemName + ' added to your cargo.', currentOrder.icon);
                } else {
                    showToast('❌ Order Failed', data.error || 'Could not place order', '⚠️');
                }
            })
            .catch(err => {
                confirmBtn.disabled = false;
                confirmBtn.textContent = 'Confirm Order';
                closeOrderModal();
                showToast('❌ Network Error', 'Connection failed. Please retry.', '⚠️');
            });
        }

        // Show Toast Notification
        function showToast(title, message, icon) {
            const toast = document.getElementById('toast');
            document.getElementById('toastTitle').textContent = title;
            document.getElementById('toastMessage').textContent = message;
            document.getElementById('toastIcon').textContent = icon || '📦';

            toast.style.display = 'block';
            setTimeout(() => {
                toast.style.display = 'none';
            }, 5000);
        }

        // Logout Modal
        function openLogoutModal() {
            document.getElementById('logoutModal').classList.add('active');
        }

        function closeLogoutModal() {
            document.getElementById('logoutModal').classList.remove('active');
        }

        function confirmLogout() {
            window.location.href = 'logout.php';
        }

        // Global Modal Escape & Outside Click Handlers
        document.querySelectorAll('.modal-overlay').forEach(modal => {
            modal.addEventListener('click', function(e) {
                if (e.target === this) {
                    this.classList.remove('active');
                }
            });
        });

        document.addEventListener('keydown', function(e) {
            if (e.key === 'Escape') {
                closeLogoutModal();
                closeOrderModal();
            }
        });
    </script>
</body>

</html>