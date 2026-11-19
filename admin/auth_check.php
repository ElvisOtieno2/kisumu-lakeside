
<?php

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

// Check whether an administrator is logged in
if (!isset($_SESSION["admin_id"])) {
    header("Location: login.php");
    exit;
}

try {

    // Confirm that the administrator still exists and is active
    $stmt = $pdo->prepare("
        SELECT id, full_name, username, is_active
        FROM admins
        WHERE id = :id
        AND is_active = TRUE
        LIMIT 1
    ");

    $stmt->execute([
        ":id" => $_SESSION["admin_id"]
    ]);

    $admin = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$admin) {

        // Account was deleted or deactivated
        $_SESSION = [];

        if (ini_get("session.use_cookies")) {

            $params = session_get_cookie_params();

            setcookie(
                session_name(),
                "",
                time() - 42000,
                $params["path"],
                $params["domain"],
                $params["secure"],
                $params["httponly"]
            );
        }

        session_destroy();

        header("Location: login.php?error=account_disabled");
        exit;
    }

    // Refresh administrator details
    $_SESSION["admin_name"] = $admin["full_name"];
    $_SESSION["admin_username"] = $admin["username"];

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(503);
    exit("Unable to verify administrator access. Please try again later.");
}
