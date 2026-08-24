<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Moto Shop - Ride the Edge of Performance</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-orange: #ff5500;
            --accent-orange-hover: #ff6a1a;
            --accent-orange-glow: rgba(255, 85, 0, 0.4);
            --dark-bg: #090a0f;
            --glass-card: rgba(18, 22, 32, 0.75);
            --glass-border: rgba(255, 255, 255, 0.08);
            --text-main: #f8fafc;
            --text-muted: #94a3b8;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: var(--dark-bg);
            color: var(--text-main);
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image:
                linear-gradient(to right, rgba(9, 10, 15, 0.88) 0%, rgba(9, 10, 15, 0.65) 50%, rgba(9, 10, 15, 0.85) 100%),
                url('../img/moto-bg.jpg');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
        .navbar-container {
            width: 100%;
            max-width: 1280px;
            margin: 1.5rem auto 0;
            padding: 0 1.5rem;
        }

        .navbar {
            background: rgba(20, 24, 33, 0.75);
            backdrop-filter: blur(16px);
            -webkit-backdrop-filter: blur(16px);
            border: 1px solid rgba(255, 255, 255, 0.08);
            border-radius: 50px;
            padding: 0.75rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.4);
        }

        .logo {
            display: flex;
            align-items: center;
            gap: 0.75rem;
            text-decoration: none;
        }

        .logo-icon {
            display: inline-flex;
            align-items: center;
            justify-content: center;
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.75rem;
            letter-spacing: 0.5px;
            line-height: 1;
        }

        .logo-text .moto {
            font-weight: 900;
            color: #ffffff;
        }

        .logo-text .shop {
            font-weight: 400;
            color: var(--accent-orange);
            margin-left: 1px;
        }

        .nav-actions {
            display: flex;
            align-items: center;
            gap: 1rem;
        }

        .btn-register-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1.35rem;
            border-radius: 30px;
            border: 1.5px solid rgba(255, 85, 0, 0.6);
            background: rgba(255, 85, 0, 0.08);
            color: #ff7733;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-register-nav:hover {
            background: rgba(255, 85, 0, 0.2);
            border-color: var(--accent-orange);
            color: #ffffff;
            transform: translateY(-1px);
            box-shadow: 0 4px 15px rgba(255, 85, 0, 0.25);
        }

        .btn-login-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.55rem 1rem;
            color: #cbd5e1;
            font-size: 0.95rem;
            font-weight: 600;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-login-nav:hover {
            color: #ffffff;
            transform: translateY(-1px);
        }

        /* Hero Main Section */
        .hero-container {
            flex: 1;
            display: flex;
            align-items: center;
            max-width: 1280px;
            width: 100%;
            margin: 0 auto;
            padding: 3rem 2rem 4rem;
        }

        .hero-content {
            max-width: 680px;
            animation: fadeInHero 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(255, 85, 0, 0.12);
            border: 1px solid rgba(255, 85, 0, 0.35);
            padding: 0.4rem 1.1rem;
            border-radius: 20px;
            color: #ff8844;
            font-size: 0.82rem;
            font-weight: 700;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1.5rem;
        }

        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 4.2rem;
            font-weight: 900;
            line-height: 1.08;
            color: #ffffff;
            margin-bottom: 1.25rem;
            letter-spacing: -1px;
        }

        .highlight-word {
            color: var(--accent-orange);
            position: relative;
            display: inline-block;
        }

        .highlight-word::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 0;
            width: 100%;
            height: 5px;
            background: var(--accent-orange);
            border-radius: 3px;
        }

        .hero-description {
            font-size: 1.18rem;
            line-height: 1.6;
            color: #94a3b8;
            margin-bottom: 2.5rem;
            max-width: 580px;
            font-weight: 400;
        }

        .hero-cta-group {
            display: flex;
            align-items: center;
            gap: 1.25rem;
            flex-wrap: wrap;
        }

        .btn-cta-primary {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.95rem 2.2rem;
            background: linear-gradient(135deg, #ff5500 0%, #e04400 100%);
            color: #ffffff;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 35px;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(255, 85, 0, 0.4), inset 0 1px 0 rgba(255, 255, 255, 0.2);
            transition: all 0.3s ease;
        }

        .btn-cta-primary:hover {
            background: linear-gradient(135deg, #ff661a 0%, #eb4d00 100%);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(255, 85, 0, 0.5), inset 0 1px 0 rgba(255, 255, 255, 0.3);
        }

        .btn-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.6rem;
            padding: 0.95rem 2rem;
            background: rgba(255, 255, 255, 0.06);
            backdrop-filter: blur(10px);
            color: #f1f5f9;
            font-size: 1.05rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 35px;
            border: 1px solid rgba(255, 255, 255, 0.15);
            cursor: pointer;
            transition: all 0.3s ease;
        }

        .btn-cta-secondary:hover {
            background: rgba(255, 255, 255, 0.12);
            border-color: rgba(255, 255, 255, 0.3);
            transform: translateY(-2px);
            box-shadow: 0 10px 25px rgba(0, 0, 0, 0.3);
        }

        /* Footer */
        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.05);
            padding: 1.5rem;
            text-align: center;
            color: #64748b;
            font-size: 0.88rem;
        }

        footer a {
            color: #64748b;
            text-decoration: none;
            margin-left: 0.5rem;
            transition: color 0.2s ease;
        }

        footer a:hover {
            color: var(--accent-orange);
        }

        @keyframes fadeInHero {
            from {
                opacity: 0;
                transform: translateY(20px);
            }
            to {
                opacity: 1;
                transform: translateY(0);
            }
        }

        @media (max-width: 768px) {
            .hero-title {
                font-size: 2.8rem;
            }
            .hero-description {
                font-size: 1rem;
            }
            .navbar {
                border-radius: 20px;
                padding: 0.75rem 1rem;
            }
            .hero-container {
                padding: 2rem 1.25rem;
            }
        }
    </style>
</head>

<body>
    <!-- Top Floating Navbar -->
    <div class="navbar-container">
        <nav class="navbar">
            <a href="homepage.php" class="logo">
                <div class="logo-icon">
                    <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#ff5500" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <circle cx="18.5" cy="17.5" r="3.5"/>
                        <circle cx="5.5" cy="17.5" r="3.5"/>
                        <circle cx="15" cy="5" r="1"/>
                        <path d="M12 17.5V14l-3-3 4-3 2 3h2"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="moto">MOTO</span><span class="shop">SHOP</span>
                </div>
            </a>
            <div class="nav-actions">
                <a href="registration.php" class="btn-register-nav">
                    <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    Register
                </a>
                <a href="login.php" class="btn-login-nav">
                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.3" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    Log In
                </a>
            </div>
        </nav>
    </div>

    <!-- Center Hero Content -->
    <div class="hero-container">
        <div class="hero-content">
            <div class="badge-pill">
                <svg width="13" height="13" viewBox="0 0 24 24" fill="#ff8844" stroke="#ff8844" stroke-width="1">
                    <polygon points="13 2 3 14 12 14 11 22 21 10 12 10 13 2"></polygon>
                </svg>
                2026 Collection
            </div>
            <h1 class="hero-title">
                Ride the <span class="highlight-word">edge</span><br>of performance
            </h1>
            <p class="hero-description">
                Unleash your passion with our handpicked lineup of sport, cruiser, and adventure machines. Built for the road ahead.
            </p>
            <div class="hero-cta-group">
                <a href="registration.php" class="btn-cta-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                    GET STARTED
                </a>
                <a href="registration.php" class="btn-cta-secondary">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path>
                        <circle cx="8.5" cy="7" r="4"></circle>
                        <line x1="20" y1="8" x2="20" y2="14"></line>
                        <line x1="23" y1="11" x2="17" y2="11"></line>
                    </svg>
                    SIGN UP
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 Moto Shop. All rights reserved.</p>
    </footer>
</body>

</html>