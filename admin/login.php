<?php

session_start();

require_once __DIR__ . '/../config/db.php';

if (isset($_SESSION['admin_id'])) {
    header('Location: dashboard.php');
    exit;
}

if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}

$error = '';
$submittedUsername = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $submittedUsername = trim($_POST['username'] ?? '');
    $password = $_POST['password'] ?? '';
    $csrfToken = $_POST['csrf_token'] ?? '';

    if (!is_string($csrfToken) || !hash_equals($_SESSION['csrf_token'], $csrfToken)) {
        $error = 'Your session has expired. Please refresh the page and try again.';
    } elseif ($submittedUsername === '' || $password === '') {
        $error = 'Please enter your username and password.';
    } else {
        try {
            $stmt = $pdo->prepare("
                SELECT id, full_name, username, password_hash
                FROM admins
                WHERE username = :username
                  AND is_active = TRUE
                LIMIT 1
            ");
            $stmt->execute(['username' => $submittedUsername]);
            $admin = $stmt->fetch(PDO::FETCH_ASSOC);

            if (
                $admin &&
                is_string($admin['password_hash']) &&
                password_verify($password, $admin['password_hash'])
            ) {
                session_regenerate_id(true);

                $_SESSION['admin_id'] = (int) $admin['id'];
                $_SESSION['admin_name'] = $admin['full_name'];
                $_SESSION['admin_username'] = $admin['username'];
                unset($_SESSION['csrf_token']);

                header('Location: dashboard.php');
                exit;
            }

            $error = 'Invalid username or password.';
        } catch (PDOException $exception) {
            error_log('Administrator login failed: ' . $exception->getMessage());
            $error = 'Login is temporarily unavailable. Please try again.';
        }
    }
}

function escapeHtml($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Login | Kisumu Lakeside</title>
    <style>
        * {
            box-sizing: border-box;
        }

        body {
            min-height: 100vh;
            margin: 0;
            padding: 20px;
            display: flex;
            align-items: center;
            justify-content: center;
            background: #080808;
            color: #ffffff;
            font-family: Arial, sans-serif;
        }

        .login-box {
            width: 100%;
            max-width: 400px;
            padding: 35px;
            background: #151515;
            border: 1px solid #087a3d;
            border-radius: 10px;
        }

        h1 {
            margin: 0;
            color: #16a05a;
            text-align: center;
            font-size: 24px;
        }

        .subtitle {
            margin: 10px 0 0;
            color: #aaaaaa;
            text-align: center;
        }

        label {
            display: block;
            margin: 18px 0 8px;
        }

        input {
            width: 100%;
            padding: 13px;
            background: #222222;
            border: 1px solid #444444;
            border-radius: 5px;
            color: #ffffff;
        }

        input:focus {
            border-color: #16a05a;
            outline: 2px solid rgba(22, 160, 90, 0.25);
        }

        .password-wrapper {
            position: relative;
            width: 100%;
        }

        .password-wrapper input {
            padding-right: 70px;
        }

        .toggle-password {
            position: absolute;
            right: 10px;
            top: 50%;
            width: 36px;
            height: 36px;
            margin: 0;
            padding: 8px;
            transform: translateY(-50%);
            background: transparent;
            border: 0;
            border-radius: 50%;
            color: #9ca3af;
            display: flex;
            align-items: center;
            justify-content: center;
            cursor: pointer;
        }

        .toggle-password:hover,
        .toggle-password:focus-visible {
            background: #303030;
            color: #ffffff;
            outline: none;
        }

        .toggle-password svg {
            width: 18px;
            height: 18px;
            fill: none;
            stroke: currentColor;
            stroke-width: 1.8;
            stroke-linecap: round;
            stroke-linejoin: round;
        }

        .submit-button {
            width: 100%;
            margin-top: 25px;
            padding: 14px;
            background: #087a3d;
            border: none;
            border-radius: 5px;
            color: #ffffff;
            cursor: pointer;
            font-weight: bold;
        }

        .submit-button:hover,
        .submit-button:focus {
            background: #16a05a;
        }

        .error {
            margin-top: 15px;
            color: #ff6b6b;
            text-align: center;
        }
    </style>
</head>
<body>
    <main class="login-box">
        <h1>KISUMU LAKESIDE</h1>
        <p class="subtitle">Administrator Portal</p>

        <?php if ($error !== ''): ?>
            <div class="error" role="alert">
                <?= escapeHtml($error) ?>
            </div>
        <?php endif; ?>

        <form method="post" action="login.php">
            <input type="hidden" name="csrf_token" value="<?= escapeHtml($_SESSION['csrf_token']) ?>">

            <label for="username">Username</label>
            <input
                type="text"
                id="username"
                name="username"
                value="<?= escapeHtml($submittedUsername) ?>"
                autocomplete="username"
                maxlength="100"
                required
                autofocus
            >

            <label for="password">Password</label>
            <div class="password-wrapper">
                <input
                    type="password"
                    id="password"
                    name="password"
                    autocomplete="current-password"
                    maxlength="255"
                    required
                >
                <button type="button" id="togglePassword" class="toggle-password" aria-controls="password" aria-label="Show password">
                    <svg class="eye-icon" viewBox="0 0 24 24" aria-hidden="true">
                        <path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z"></path>
                        <circle cx="12" cy="12" r="2.7"></circle>
                    </svg>
                </button>
            </div>

            <button type="submit" class="submit-button">LOGIN</button>
        </form>
    </main>

    <script>
        const passwordInput = document.getElementById('password');
        const togglePassword = document.getElementById('togglePassword');

        togglePassword.addEventListener('click', function () {
            const isHidden = passwordInput.type === 'password';

            passwordInput.type = isHidden ? 'text' : 'password';
            this.setAttribute('aria-label', isHidden ? 'Hide password' : 'Show password');
            this.innerHTML = isHidden
                ? '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M3 3l18 18"></path><path d="M10.6 10.7a2 2 0 0 0 2.7 2.7"></path><path d="M9.9 5.2A9.8 9.8 0 0 1 12 5c6 0 9.6 7 9.6 7a15.5 15.5 0 0 1-2.7 3.4"></path><path d="M6.2 6.2C3.7 8.1 2.4 12 2.4 12s3.6 7 9.6 7a9 9 0 0 0 3.1-.6"></path></svg>'
                : '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="M2.4 12s3.6-6 9.6-6 9.6 6 9.6 6-3.6 6-9.6 6-9.6-6-9.6-6Z"></path><circle cx="12" cy="12" r="2.7"></circle></svg>';
            passwordInput.focus();
        });
    </script>
</body>
</html>
