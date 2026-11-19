<<?php

require_once __DIR__ . '/auth_check.php';

// the existing dashboard code continues below

$totalPlayers = (int) $pdo->query('SELECT COUNT(*) FROM players')->fetchColumn();
$totalFixtures = (int) $pdo->query('SELECT COUNT(*) FROM fixtures')->fetchColumn();
$totalPhotos = (int) $pdo->query('SELECT COUNT(*) FROM gallery')->fetchColumn();
$totalNews = (int) $pdo->query('SELECT COUNT(*) FROM news')->fetchColumn();

$adminName = $_SESSION['admin_name'] ?? 'Administrator';

function escapeDashboard($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard | Kisumu Lakeside</title>

    <style>
        :root {
            --background: #080808;
            --sidebar: #111111;
            --panel: #151515;
            --panel-hover: #1b1b1b;
            --border: #252525;
            --green: #087a3d;
            --green-light: #16a05a;
            --text: #ffffff;
            --muted: #aaaaaa;
        }

        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            background: var(--background);
            color: var(--text);
            font-family: Arial, sans-serif;
        }

        a {
            color: inherit;
            text-decoration: none;
        }

        .sidebar {
            position: fixed;
            inset: 0 auto 0 0;
            z-index: 10;
            width: 250px;
            padding: 28px 18px;
            background: var(--sidebar);
            border-right: 1px solid rgba(22, 160, 90, 0.35);
        }

        .brand {
            display: flex;
            align-items: center;
            gap: 12px;
            margin: 0 8px 36px;
            color: var(--text);
        }

        .brand-mark {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            flex: 0 0 42px;
            border-radius: 12px;
            background: var(--green);
            font-size: 17px;
            font-weight: 800;
        }

        .brand-text {
            line-height: 1.05;
            font-size: 14px;
            font-weight: 800;
        }

        .brand-text span {
            display: block;
            margin-top: 4px;
            color: var(--green-light);
        }

        .nav-label {
            margin: 22px 12px 8px;
            color: #777777;
            font-size: 10px;
            font-weight: 700;
            letter-spacing: 1.4px;
            text-transform: uppercase;
        }

        .nav-link {
            min-height: 44px;
            display: flex;
            align-items: center;
            gap: 12px;
            margin-bottom: 5px;
            padding: 10px 12px;
            border: 1px solid transparent;
            border-radius: 8px;
            color: #bbbbbb;
            font-size: 13px;
            transition: 160ms ease;
        }

        .nav-link svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .nav-link:hover,
        .nav-link.active {
            background: rgba(22, 160, 90, 0.12);
            border-color: rgba(22, 160, 90, 0.22);
            color: var(--green-light);
        }

        .nav-link.active {
            background: var(--green);
            border-color: var(--green);
            color: var(--text);
        }

        .main-content {
            min-height: 100vh;
            margin-left: 250px;
            padding: 34px 38px 50px;
        }

        .topbar {
            display: flex;
            align-items: center;
            justify-content: space-between;
            gap: 20px;
            margin-bottom: 30px;
        }

        .topbar h1 {
            margin: 0;
            font-size: 28px;
            letter-spacing: -0.5px;
        }

        .welcome {
            margin: 7px 0 0;
            color: var(--muted);
            font-size: 14px;
        }

        .profile {
            display: flex;
            align-items: center;
            gap: 11px;
        }

        .profile-avatar {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            border: 1px solid rgba(22, 160, 90, 0.45);
            border-radius: 50%;
            background: var(--panel);
            color: var(--green-light);
            font-weight: 800;
            text-transform: uppercase;
        }

        .profile-name {
            max-width: 160px;
            color: var(--muted);
            font-size: 12px;
        }

        .profile-name strong {
            display: block;
            overflow: hidden;
            margin-bottom: 3px;
            color: var(--text);
            font-size: 13px;
            text-overflow: ellipsis;
            white-space: nowrap;
        }

        .logout {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-left: 6px;
            padding: 10px 13px;
            border: 1px solid var(--border);
            border-radius: 8px;
            color: var(--muted);
            font-size: 12px;
            font-weight: 700;
            transition: 160ms ease;
        }

        .logout svg {
            width: 16px;
            height: 16px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .logout:hover {
            border-color: var(--green-light);
            color: var(--green-light);
        }

        .stats-container {
            display: grid;
            grid-template-columns: repeat(4, minmax(0, 1fr));
            gap: 16px;
        }

        .stat-card {
            min-height: 145px;
            position: relative;
            overflow: hidden;
            padding: 22px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
        }

        .stat-card::after {
            content: "";
            position: absolute;
            right: -28px;
            bottom: -38px;
            width: 110px;
            height: 110px;
            border-radius: 50%;
            background: rgba(22, 160, 90, 0.05);
        }

        .stat-icon {
            width: 38px;
            height: 38px;
            display: grid;
            place-items: center;
            margin-bottom: 18px;
            border-radius: 9px;
            background: rgba(22, 160, 90, 0.12);
            color: var(--green-light);
        }

        .stat-icon svg {
            width: 20px;
            height: 20px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .stat-card h2 {
            margin: 0 0 5px;
            color: var(--green-light);
            font-size: 30px;
            line-height: 1;
        }

        .stat-card p {
            margin: 0;
            color: var(--muted);
            font-size: 13px;
        }

        .section {
            margin-top: 36px;
        }

        .section-heading {
            display: flex;
            align-items: end;
            justify-content: space-between;
            gap: 15px;
            margin-bottom: 16px;
        }

        .section-heading h2 {
            margin: 0;
            font-size: 18px;
        }

        .section-heading span {
            color: #777777;
            font-size: 12px;
        }

        .management-grid {
            display: grid;
            grid-template-columns: repeat(3, minmax(0, 1fr));
            gap: 16px;
        }

        .action-card {
            min-height: 190px;
            display: flex;
            flex-direction: column;
            align-items: flex-start;
            padding: 22px;
            background: var(--panel);
            border: 1px solid var(--border);
            border-radius: 12px;
            transition: 180ms ease;
        }

        .action-card:hover {
            background: var(--panel-hover);
            border-color: rgba(22, 160, 90, 0.55);
            transform: translateY(-3px);
        }

        .action-icon {
            width: 42px;
            height: 42px;
            display: grid;
            place-items: center;
            margin-bottom: 17px;
            border-radius: 10px;
            background: var(--green);
            color: var(--text);
        }

        .action-icon svg {
            width: 21px;
            height: 21px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .action-card h3 {
            margin: 0 0 8px;
            font-size: 15px;
        }

        .action-card p {
            margin: 0 0 18px;
            color: var(--muted);
            font-size: 13px;
            line-height: 1.55;
        }

        .action-card a {
            display: inline-flex;
            align-items: center;
            gap: 8px;
            margin-top: auto;
            color: var(--green-light);
            font-size: 12px;
            font-weight: 800;
        }

        .action-card a:hover {
            color: var(--text);
        }

        @media (max-width: 1050px) {
            .stats-container {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }

            .management-grid {
                grid-template-columns: repeat(2, minmax(0, 1fr));
            }
        }

        @media (max-width: 760px) {
            .sidebar {
                position: relative;
                width: 100%;
                height: auto;
                padding: 16px;
                border-right: 0;
                border-bottom: 1px solid rgba(22, 160, 90, 0.35);
            }

            .brand {
                margin: 0 0 12px;
            }

            .nav-label {
                display: none;
            }

            .nav-link {
                display: inline-flex;
                width: auto;
                margin: 4px 4px 0 0;
            }

            .main-content {
                margin-left: 0;
                padding: 24px 16px 40px;
            }

            .topbar h1 {
                font-size: 23px;
            }

            .profile-name {
                display: none;
            }
        }

        @media (max-width: 540px) {
            .topbar {
                align-items: flex-start;
            }

            .logout span {
                display: none;
            }

            .logout {
                margin: 0;
                padding: 10px;
            }

            .stats-container,
            .management-grid {
                grid-template-columns: 1fr;
            }

            .section-heading span {
                display: none;
            }
        }
    </style>
</head>
<body>
    <aside class="sidebar">
        <div class="brand">
            <div class="brand-mark">KL</div>
            <div class="brand-text">
                KISUMU
                <span>LAKESIDE</span>
            </div>
        </div>

        <p class="nav-label">Main menu</p>
        <a class="nav-link active" href="dashboard.php" aria-current="page">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="7" height="7" rx="1"></rect><rect x="14" y="3" width="7" height="7" rx="1"></rect><rect x="3" y="14" width="7" height="7" rx="1"></rect><rect x="14" y="14" width="7" height="7" rx="1"></rect></svg>
            Dashboard
        </a>

        <p class="nav-label">Club management</p>
        <a class="nav-link" href="players.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
            Manage Players
        </a>
        <a class="nav-link" href="add_player.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="4"></circle><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M19 8v6M16 11h6"></path></svg>
            Add Player
        </a>
        <a class="nav-link" href="fixtures.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
            Manage Fixtures
        </a>
        <a class="nav-link" href="gallery.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
            Manage Gallery
        </a>
        <a class="nav-link" href="news.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V5H8v15a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7h6M8 9h10M8 13h10M8 17h7"></path></svg>
            Manage News
        </a>

        <p class="nav-label">Administration</p>
        <a class="nav-link" href="admins.php">
            <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21v-2a8 8 0 0 1 16 0v2M18 6l1 1 2-2"></path></svg>
            Administrators
        </a>
    </aside>

    <main class="main-content">
        <header class="topbar">
            <div>
                <h1>Admin Dashboard</h1>
                <p class="welcome">Welcome back, <?= escapeDashboard($adminName) ?></p>
            </div>

            <div class="profile">
                <div class="profile-avatar"><?= escapeDashboard(substr($adminName, 0, 1)) ?></div>
                <div class="profile-name">
                    <strong><?= escapeDashboard($adminName) ?></strong>
                    Administrator
                </div>
                <a class="logout" href="logout.php" aria-label="Logout">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M10 17l5-5-5-5M15 12H3M21 3v18"></path></svg>
                    <span>Logout</span>
                </a>
            </div>
        </header>

        <section class="stats-container" aria-label="Dashboard overview">
            <article class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87M16 3.13a4 4 0 0 1 0 7.75"></path></svg>
                </div>
                <h2><?= escapeDashboard($totalPlayers) ?></h2>
                <p>Registered Players</p>
            </article>

            <article class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
                </div>
                <h2><?= escapeDashboard($totalFixtures) ?></h2>
                <p>Registered Fixtures</p>
            </article>

            <article class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                </div>
                <h2><?= escapeDashboard($totalPhotos) ?></h2>
                <p>Gallery Photos</p>
            </article>

            <article class="stat-card">
                <div class="stat-icon">
                    <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V5H8v15a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7h6M8 9h10M8 13h10M8 17h7"></path></svg>
                </div>
                <h2><?= escapeDashboard($totalNews) ?></h2>
                <p>News Articles</p>
            </article>
        </section>

        <section class="section">
            <div class="section-heading">
                <h2>Club Management</h2>
                <span>Manage club content and registrations</span>
            </div>

            <div class="management-grid">
                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"></path><circle cx="9" cy="7" r="4"></circle><path d="M22 21v-2a4 4 0 0 0-3-3.87"></path></svg>
                    </div>
                    <h3>Player Management</h3>
                    <p>View, update and manage player profiles and roster details.</p>
                    <a href="players.php">View Players <span aria-hidden="true">→</span></a>
                </article>

                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="9" cy="7" r="4"></circle><path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2M19 8v6M16 11h6"></path></svg>
                    </div>
                    <h3>Register New Player</h3>
                    <p>Add a new basketball player to the club roster.</p>
                    <a href="add_player.php">Add Player <span aria-hidden="true">→</span></a>
                </article>

                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="5" width="18" height="16" rx="2"></rect><path d="M16 3v4M8 3v4M3 10h18"></path></svg>
                    </div>
                    <h3>Fixtures Management</h3>
                    <p>Create upcoming matches and update completed game results.</p>
                    <a href="fixtures.php">Manage Fixtures <span aria-hidden="true">→</span></a>
                </article>

                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><rect x="3" y="3" width="18" height="18" rx="2"></rect><circle cx="8.5" cy="8.5" r="1.5"></circle><path d="M21 15l-5-5L5 21"></path></svg>
                    </div>
                    <h3>Gallery Management</h3>
                    <p>Upload basketball photos and manage existing gallery images.</p>
                    <a href="gallery.php">Manage Gallery <span aria-hidden="true">→</span></a>
                </article>

                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><path d="M4 22h16a2 2 0 0 0 2-2V5H8v15a2 2 0 0 1-2 2H4a2 2 0 0 1-2-2V7h6M8 9h10M8 13h10M8 17h7"></path></svg>
                    </div>
                    <h3>News &amp; Updates</h3>
                    <p>Publish club announcements and manage news articles.</p>
                    <a href="news.php">Manage News <span aria-hidden="true">→</span></a>
                </article>

                <article class="action-card">
                    <div class="action-icon">
                        <svg viewBox="0 0 24 24" aria-hidden="true"><circle cx="12" cy="8" r="4"></circle><path d="M4 21v-2a8 8 0 0 1 16 0v2M18 6l1 1 2-2"></path></svg>
                    </div>
                    <h3>Administrator Management</h3>
                    <p>Manage authorised accounts and dashboard access.</p>
                    <a href="admins.php">Manage Admins <span aria-hidden="true">→</span></a>
                </article>
            </div>
        </section>
    </main>
</body>
</html>
