<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>ONE PIECE - Set Sail Across the Grand Line</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Plus+Jakarta+Sans:ital,wght@0,300;0,400;0,500;0,600;0,700;0,800;1,800&family=Outfit:wght@400;600;700;800;900&display=swap" rel="stylesheet">
    <style>
        :root {
            --accent-gold: #d97706;
            --accent-gold-hover: #b45309;
            --accent-gold-glow: rgba(217, 119, 6, 0.25);
            --accent-red: #ef4444;
            --accent-orange: #f59e0b;
            --accent-orange-hover: #d97706;
            --accent-orange-glow: rgba(245, 158, 11, 0.25);
            --dark-bg: #f8fafc;
            --glass-card: rgba(255, 255, 255, 0.92);
            --glass-border: rgba(245, 158, 11, 0.35);
            --text-main: #0f172a;
            --text-muted: #475569;
        }

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Plus Jakarta Sans', system-ui, -apple-system, sans-serif;
            background-color: #0b1120;
            color: #ffffff;
            min-height: 100vh;
            display: flex;
            flex-direction: column;
            background-image:
                linear-gradient(to right, rgba(10, 15, 25, 0.30) 0%, rgba(10, 15, 25, 0.15) 50%, rgba(10, 15, 25, 0.35) 100%),
                url('../img/one_piece_bg.jpg');
            background-size: cover;
            background-position: center center;
            background-attachment: fixed;
            overflow-x: hidden;
        }

        /* Top Navigation Bar */
        .navbar-container {
            width: 100%;
            max-width: 1280px;
            margin: 1.25rem auto 0;
            padding: 0 1.5rem;
        }

        .navbar {
            background: rgba(15, 23, 42, 0.45);
            backdrop-filter: blur(25px);
            -webkit-backdrop-filter: blur(25px);
            border: 1px solid rgba(255, 255, 255, 0.18);
            border-radius: 50px;
            padding: 0.65rem 1.75rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 30px rgba(0, 0, 0, 0.25), 0 0 25px rgba(245, 158, 11, 0.15);
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
            filter: drop-shadow(0 0 8px rgba(245, 158, 11, 0.4));
            transition: transform 0.25s ease;
        }

        .logo:hover .logo-icon {
            transform: scale(1.08) rotate(-3deg);
        }

        .logo-text {
            font-family: 'Outfit', sans-serif;
            font-size: 1.6rem;
            letter-spacing: 1px;
            line-height: 1;
        }

        .logo-text .op-one {
            font-weight: 900;
            color: #ffffff;
            text-shadow: 0 2px 4px rgba(0, 0, 0, 0.5);
        }

        .logo-text .op-piece {
            font-weight: 900;
            color: #fbbf24;
            text-shadow: 0 0 14px rgba(245, 158, 11, 0.5);
            margin-left: 3px;
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
            padding: 0.5rem 1.35rem;
            border-radius: 30px;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            box-shadow: 0 4px 15px rgba(239, 68, 68, 0.4);
            transition: all 0.25s ease;
        }

        .btn-register-nav:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-1px);
            box-shadow: 0 6px 20px rgba(245, 158, 11, 0.55);
            color: #ffffff;
        }

        .btn-login-nav {
            display: inline-flex;
            align-items: center;
            gap: 0.4rem;
            padding: 0.5rem 1rem;
            color: #ffffff;
            font-size: 0.9rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.25s ease;
        }

        .btn-login-nav:hover {
            color: #fbbf24;
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
            max-width: 720px;
            animation: fadeInHero 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        .badge-pill {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            background: rgba(245, 158, 11, 0.25);
            border: 1.5px solid rgba(245, 158, 11, 0.6);
            backdrop-filter: blur(12px);
            padding: 0.4rem 1.1rem;
            border-radius: 20px;
            color: #fbbf24;
            font-size: 0.82rem;
            font-weight: 800;
            letter-spacing: 1.5px;
            text-transform: uppercase;
            margin-bottom: 1.25rem;
            box-shadow: 0 2px 10px rgba(245, 158, 11, 0.25);
        }

        .hero-title {
            font-family: 'Outfit', sans-serif;
            font-size: 4rem;
            font-weight: 900;
            line-height: 1.08;
            color: #ffffff;
            margin-bottom: 1.25rem;
            letter-spacing: -0.5px;
            text-shadow: 0 4px 20px rgba(0, 0, 0, 0.6);
        }

        .highlight-word {
            color: #fbbf24;
            position: relative;
            display: inline-block;
            text-shadow: 0 0 25px rgba(245, 158, 11, 0.5);
        }

        .highlight-word::after {
            content: '';
            position: absolute;
            bottom: 4px;
            left: 0;
            width: 100%;
            height: 5px;
            background: linear-gradient(90deg, #f59e0b, #ef4444);
            border-radius: 3px;
        }

        .hero-description {
            font-size: 1.15rem;
            line-height: 1.6;
            color: #f1f5f9;
            margin-bottom: 2.25rem;
            max-width: 600px;
            font-weight: 500;
            text-shadow: 0 2px 8px rgba(0, 0, 0, 0.6);
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
            padding: 0.9rem 2.2rem;
            background: linear-gradient(135deg, #ef4444 0%, #f59e0b 100%);
            color: #ffffff;
            font-size: 1.02rem;
            font-weight: 700;
            text-decoration: none;
            border-radius: 35px;
            border: none;
            cursor: pointer;
            box-shadow: 0 10px 25px rgba(239, 68, 68, 0.4);
            transition: all 0.3s ease;
        }

        .btn-cta-primary:hover {
            background: linear-gradient(135deg, #f87171 0%, #fbbf24 100%);
            transform: translateY(-2px);
            box-shadow: 0 14px 30px rgba(245, 158, 11, 0.55);
        }

        .btn-cta-secondary {
            display: inline-flex;
            align-items: center;
            gap: 0.5rem;
            padding: 0.85rem 1.85rem;
            border-radius: 35px;
            background: rgba(255, 255, 255, 0.15);
            backdrop-filter: blur(12px);
            -webkit-backdrop-filter: blur(12px);
            border: 1.5px solid rgba(255, 255, 255, 0.4);
            color: #ffffff;
            font-size: 0.95rem;
            font-weight: 700;
            text-decoration: none;
            transition: all 0.3s ease;
            box-shadow: 0 8px 25px rgba(0, 0, 0, 0.2);
        }

        .btn-cta-secondary:hover {
            background: rgba(255, 255, 255, 0.25);
            border-color: #fbbf24;
            color: #fbbf24;
            transform: translateY(-2px);
            box-shadow: 0 10px 30px rgba(245, 158, 11, 0.3);
        }

        footer {
            border-top: 1px solid rgba(255, 255, 255, 0.15);
            padding: 1.25rem 2rem;
            text-align: center;
            color: rgba(255, 255, 255, 0.7);
            font-size: 0.85rem;
            background: rgba(15, 23, 42, 0.35);
            backdrop-filter: blur(15px);
            -webkit-backdrop-filter: blur(15px);
        }

        footer a {
            color: #fbbf24;
            text-decoration: none;
            margin-left: 0.5rem;
            transition: color 0.2s ease;
        }

        footer a:hover {
            color: #fde047;
            text-decoration: underline;
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
                    <svg width="32" height="32" viewBox="0 0 64 64" fill="none" xmlns="http://www.w3.org/2000/svg">
                        <!-- Crossed Bones -->
                        <path d="M14 14 L50 50 M50 14 L14 50" stroke="#e2e8f0" stroke-width="5" stroke-linecap="round"/>
                        <circle cx="12" cy="12" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="16" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="12" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="16" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="48" r="3.5" fill="#cbd5e1"/>
                        <circle cx="12" cy="52" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="48" r="3.5" fill="#cbd5e1"/>
                        <circle cx="52" cy="52" r="3.5" fill="#cbd5e1"/>
                        <!-- Skull Base -->
                        <circle cx="32" cy="34" r="14" fill="#f8fafc"/>
                        <rect x="26" y="42" width="12" height="7" rx="2" fill="#f8fafc"/>
                        <!-- Eye Sockets -->
                        <circle cx="27.5" cy="35" r="3.2" fill="#0f172a"/>
                        <circle cx="36.5" cy="35" r="3.2" fill="#0f172a"/>
                        <!-- Teeth Lines -->
                        <line x1="28.5" y1="44" x2="28.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <line x1="32" y1="44" x2="32" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <line x1="35.5" y1="44" x2="35.5" y2="48" stroke="#0f172a" stroke-width="1.3"/>
                        <!-- Straw Hat Crown -->
                        <ellipse cx="32" cy="24" rx="12" ry="7.5" fill="#f59e0b"/>
                        <!-- Red Ribbon -->
                        <ellipse cx="32" cy="27" rx="12.2" ry="3" fill="#ef4444"/>
                        <!-- Straw Hat Brim -->
                        <ellipse cx="32" cy="28" rx="22" ry="5" fill="#f59e0b"/>
                    </svg>
                </div>
                <div class="logo-text">
                    <span class="op-one">ONE</span><span class="op-piece">PIECE</span>
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
                    Join Crew
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
                ⚓ Straw Hat Grand Fleet
            </div>
            <h1 class="hero-title">
                Set Sail for the <span class="highlight-word">One Piece</span>
            </h1>
            <p class="hero-description">
                Join Monkey D. Luffy and the Straw Hat crew as they navigate treacherous waters, find legendary nakama, and venture into the uncharted Grand Line.
            </p>
            <div class="hero-cta-group">
                <a href="registration.php" class="btn-cta-primary">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <line x1="5" y1="12" x2="19" y2="12"></line>
                        <polyline points="12 5 19 12 12 19"></polyline>
                    </svg>
                    JOIN THE CREW
                </a>
                <a href="login.php" class="btn-cta-secondary">
                    <svg width="17" height="17" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round">
                        <path d="M15 3h4a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2h-4"></path>
                        <polyline points="10 17 15 12 10 7"></polyline>
                        <line x1="15" y1="12" x2="3" y2="12"></line>
                    </svg>
                    CREW LOG IN
                </a>
            </div>
        </div>
    </div>

    <!-- Footer -->
    <footer>
        <p>&copy; 2024 ONE PIECE System. All rights reserved. | <a href="#">Grand Line Policy</a></p>
    </footer>
</body>

</html>