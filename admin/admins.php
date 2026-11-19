
<?php

require_once __DIR__ . '/auth_check.php';

// Generate CSRF token
if (empty($_SESSION["csrf_token"])) {
    $_SESSION["csrf_token"] = bin2hex(random_bytes(32));
}

$message = "";
$messageType = "success";

// Safe output
function e($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}

// Handle administrator actions
if ($_SERVER["REQUEST_METHOD"] === "POST") {

    if (
        !isset($_POST["csrf_token"]) ||
        !hash_equals($_SESSION["csrf_token"], $_POST["csrf_token"])
    ) {
        die("Invalid security token. Please refresh the page.");
    }

    $action = $_POST["action"] ?? "";

    // CREATE ADMINISTRATOR
    if ($action === "create") {

        $fullName = trim($_POST["full_name"] ?? "");
        $username = trim($_POST["username"] ?? "");
        $email = trim($_POST["email"] ?? "");
        $password = $_POST["password"] ?? "";

        if (
            $fullName === "" ||
            $username === "" ||
            $email === "" ||
            $password === ""
        ) {
            $message = "Please complete all fields.";
            $messageType = "error";

        } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $message = "Please enter a valid email address.";
            $messageType = "error";

        } elseif (strlen($password) < 10) {
            $message = "Password must contain at least 10 characters.";
            $messageType = "error";

        } else {

            try {

                $passwordHash = password_hash(
                    $password,
                    PASSWORD_DEFAULT
                );

                $stmt = $pdo->prepare("
                    INSERT INTO admins
                    (full_name, username, email, password_hash, is_active)
                    VALUES
                    (:full_name, :username, :email, :password_hash, TRUE)
                ");

                $stmt->execute([
                    ":full_name" => $fullName,
                    ":username" => $username,
                    ":email" => $email,
                    ":password_hash" => $passwordHash
                ]);

                $message = "Administrator created successfully.";

            } catch (PDOException $e) {

                if ($e->getCode() === "23505") {
                    $message = "Username or email already exists.";
                } else {
                    error_log($e->getMessage());
                    $message = "Unable to create administrator.";
                }

                $messageType = "error";
            }
        }
    }

    // ACTIVATE OR DEACTIVATE ADMINISTRATOR
    elseif ($action === "toggle") {

        $targetId = filter_var(
            $_POST["admin_id"] ?? null,
            FILTER_VALIDATE_INT
        );

       if (!$targetId) {

    $message = "Invalid administrator account.";
    $messageType = "error";

} elseif ($targetId === 1) {

    $message = "The primary administrator account (ID 1) cannot be deactivated.";
    $messageType = "error";

} elseif ($targetId === (int)$_SESSION["admin_id"]) {

    $message = "You cannot change your own account status.";
    $messageType = "error";

} else {

            $stmt = $pdo->prepare("
                SELECT is_active
                FROM admins
                WHERE id = :id
            ");

            $stmt->execute([":id" => $targetId]);
            $target = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$target) {

                $message = "Administrator not found.";
                $messageType = "error";

            } else {

                $currentlyActive = in_array(
                    $target["is_active"],
                    [true, 1, "1", "t", "true"],
                    true
                );

                if ($currentlyActive) {

                    $activeCount = $pdo->query("
                        SELECT COUNT(*)
                        FROM admins
                        WHERE is_active = TRUE
                    ")->fetchColumn();

                    if ((int)$activeCount <= 1) {

                        $message = "You cannot deactivate the last active administrator.";
                        $messageType = "error";

                    } else {
                        $newStatus = false;
                    }

                } else {
                    $newStatus = true;
                }

                if ($messageType !== "error") {

                    $update = $pdo->prepare("
                        UPDATE admins
                        SET is_active = :status
                        WHERE id = :id
                    ");

                    $update->execute([
                        ":status" => $newStatus ? 'true' : 'false',
                        ":id" => $targetId
                    ]);

                    $message = $newStatus
                        ? "Administrator activated successfully."
                        : "Administrator deactivated successfully.";
                }
            }
        }
    }

    // DELETE ADMINISTRATOR
    elseif ($action === "delete") {

        $targetId = filter_var(
            $_POST["admin_id"] ?? null,
            FILTER_VALIDATE_INT
        );

        if (!$targetId) {

    $message = "Invalid administrator account.";
    $messageType = "error";

} elseif ($targetId === 1) {

    $message = "The primary administrator account (ID 1) is protected and cannot be deleted.";
    $messageType = "error";

} elseif ($targetId === (int)$_SESSION["admin_id"]) {
            $message = "You cannot delete your own account.";
            $messageType = "error";

        } else {

            try {

                $pdo->beginTransaction();

                $stmt = $pdo->prepare("
                    SELECT id, is_active
                    FROM admins
                    WHERE id = :id
                    FOR UPDATE
                ");

                $stmt->execute([":id" => $targetId]);
                $target = $stmt->fetch(PDO::FETCH_ASSOC);

                if (!$target) {

                    $pdo->rollBack();

                    $message = "Administrator not found.";
                    $messageType = "error";

                } else {

                    $targetIsActive = in_array(
                        $target["is_active"],
                        [true, 1, "1", "t", "true"],
                        true
                    );

                    if ($targetIsActive) {

                        $activeCount = $pdo->query("
                            SELECT COUNT(*)
                            FROM admins
                            WHERE is_active = TRUE
                        ")->fetchColumn();

                        if ((int)$activeCount <= 1) {

                            $pdo->rollBack();

                            $message = "You cannot delete the last active administrator.";
                            $messageType = "error";

                        } else {

                            $delete = $pdo->prepare("
                                DELETE FROM admins
                                WHERE id = :id
                            ");

                            $delete->execute([":id" => $targetId]);

                            $pdo->commit();

                            $message = "Administrator deleted successfully.";
                        }

                    } else {

                        $delete = $pdo->prepare("
                            DELETE FROM admins
                            WHERE id = :id
                        ");

                        $delete->execute([":id" => $targetId]);

                        $pdo->commit();

                        $message = "Administrator deleted successfully.";
                    }
                }

            } catch (PDOException $e) {

                if ($pdo->inTransaction()) {
                    $pdo->rollBack();
                }

                error_log($e->getMessage());

                $message = "Unable to delete administrator. Please check whether this account is linked to other records.";
                $messageType = "error";
            }
        }
    }
}

// Retrieve administrators
$stmt = $pdo->query("
    SELECT id, full_name, username, email, is_active, created_at
    FROM admins
    ORDER BY created_at DESC
");

$admins = $stmt->fetchAll(PDO::FETCH_ASSOC);

$adminName = $_SESSION["admin_name"] ?? "Administrator";

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Administrators | Kisumu Lakeside</title>

<link href="https://fonts.googleapis.com/css2?family=Poppins:wght@400;500;600;700;800&display=swap" rel="stylesheet">

<style>

* {
    margin: 0;
    padding: 0;
    box-sizing: border-box;
}

body {
    font-family: 'Poppins', sans-serif;
    background: #101010;
    color: white;
}

.sidebar {
    width: 250px;
    height: 100vh;
    background: #080808;
    position: fixed;
    padding: 30px 20px;
}

.logo {
    font-size: 20px;
    font-weight: 800;
    margin-bottom: 40px;
}

.logo span {
    color: #20c56b;
}

.sidebar a {
    display: block;
    color: #bbb;
    text-decoration: none;
    padding: 13px;
    border-radius: 8px;
    margin-bottom: 8px;
    font-size: 13px;
}

.sidebar a:hover,
.sidebar a.active {
    background: #20c56b;
    color: #000;
}

.main {
    margin-left: 250px;
    padding: 35px;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    gap: 15px;
    flex-wrap: wrap;
    margin-bottom: 30px;
}

.header h1 {
    font-size: 25px;
}

.header p {
    color: #aaa;
    font-size: 13px;
    margin-top: 5px;
}

.panel {
    background: #1b1b1b;
    border: 1px solid #303030;
    border-radius: 12px;
    padding: 25px;
    margin-bottom: 25px;
}

.panel h2 {
    font-size: 18px;
    margin-bottom: 20px;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 15px;
}

input {
    width: 100%;
    padding: 13px;
    background: #101010;
    border: 1px solid #444;
    border-radius: 7px;
    color: white;
    font-family: inherit;
    margin-top: 7px;
}

label {
    font-size: 12px;
    color: #bbb;
}

button {
    border: none;
    padding: 11px 17px;
    border-radius: 7px;
    cursor: pointer;
    font-family: inherit;
    font-weight: 600;
}

.create-btn {
    background: #20c56b;
    color: #000;
    margin-top: 18px;
}

.table-wrapper {
    overflow-x: auto;
}

table {
    width: 100%;
    border-collapse: collapse;
    min-width: 750px;
}

th, td {
    padding: 15px 12px;
    text-align: left;
    border-bottom: 1px solid #333;
    font-size: 12px;
}

th {
    color: #20c56b;
    font-weight: 600;
}

.status {
    padding: 5px 10px;
    border-radius: 20px;
    font-size: 10px;
}

.active-status {
    background: #17452c;
    color: #42df86;
}

.inactive-status {
    background: #4a2929;
    color: #ff7777;
}

.toggle-btn {
    background: #333;
    color: white;
    font-size: 11px;
}

.toggle-btn.activate {
    background: #20c56b;
    color: black;
}

.delete-btn {
    background: #8b2525;
    color: white;
    font-size: 11px;
    margin-top: 6px;
}

.delete-btn:hover {
    background: #c0392b;
}

.message {
    padding: 13px;
    margin-bottom: 20px;
    border-radius: 7px;
    font-size: 13px;
}

.success {
    background: #17452c;
    color: #42df86;
}

.error {
    background: #4a2929;
    color: #ff7777;
}

.action-form {
    margin-bottom: 5px;
}

@media(max-width: 800px) {

    .sidebar {
        width: 100%;
        height: auto;
        position: relative;
    }

    .main {
        margin-left: 0;
        padding: 20px;
    }

    .form-grid {
        grid-template-columns: 1fr;
    }
}

</style>

</head>

<body>

<aside class="sidebar">

    <div class="logo">
        KISUMU <span>LAKESIDE</span>
    </div>

    <a href="dashboard.php">Dashboard</a>
    <a href="players.php">Manage Players</a>
    <a href="fixtures.php">Manage Fixtures</a>
    <a href="gallery.php">Manage Gallery</a>
    <a href="news.php">Manage News</a>
    <a href="admins.php" class="active">Administrators</a>
    <a href="logout.php">Logout</a>

</aside>

<main class="main">

    <div class="header">

        <div>
            <h1>Administrator Management</h1>
            <p>Welcome, <?= e($adminName) ?>. Manage dashboard access.</p>
        </div>

    </div>

    <?php if ($message !== ""): ?>

        <div class="message <?= e($messageType) ?>">
            <?= e($message) ?>
        </div>

    <?php endif; ?>

    <!-- CREATE ADMINISTRATOR -->

    <section class="panel">

        <h2>Register New Administrator</h2>

        <form method="POST">

            <input type="hidden" name="csrf_token"
                   value="<?= e($_SESSION["csrf_token"]) ?>">

            <input type="hidden" name="action" value="create">

            <div class="form-grid">

                <div>
                    <label>Full Name</label>
                    <input type="text" name="full_name"
                           placeholder="Enter full name" required>
                </div>

                <div>
                    <label>Username</label>
                    <input type="text" name="username"
                           placeholder="Enter username" required>
                </div>

                <div>
                    <label>Email Address</label>
                    <input type="email" name="email"
                           placeholder="Enter email address" required>
                </div>

                <div>
                    <label>Password</label>
                    <input type="password" name="password"
                           placeholder="Minimum 10 characters"
                           minlength="10" required>
                </div>

            </div>

            <button type="submit" class="create-btn">
                + Create Administrator
            </button>

        </form>

    </section>

    <!-- ADMINISTRATOR LIST -->

    <section class="panel">

        <h2>Registered Administrators</h2>

        <div class="table-wrapper">

            <table>

                <thead>

                    <tr>
                        <th>Full Name</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Status</th>
                        <th>Date Created</th>
                        <th>Actions</th>
                    </tr>

                </thead>

                <tbody>

                    <?php foreach ($admins as $admin): ?>

                        <?php

                        $isActive = in_array(
                            $admin["is_active"],
                            [true, 1, "1", "t", "true"],
                            true
                        );

                        ?>

                        <tr>

                            <td><?= e($admin["full_name"]) ?></td>

                            <td><?= e($admin["username"]) ?></td>

                            <td><?= e($admin["email"]) ?></td>

                            <td>

                                <?php if ($isActive): ?>

                                    <span class="status active-status">
                                        Active
                                    </span>

                                <?php else: ?>

                                    <span class="status inactive-status">
                                        Inactive
                                    </span>

                                <?php endif; ?>

                            </td>

                            <td>
                                <?= e($admin["created_at"]) ?>
                            </td>

                            <td>

                                <?php if (
    (int)$admin["id"] !== (int)$_SESSION["admin_id"]
    && (int)$admin["id"] !== 1
): ?>

                                    <!-- ACTIVATE / DEACTIVATE -->

                                    <form method="POST"
                                          class="action-form"
                                          onsubmit="return confirm('Change this administrator account status?');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="toggle">

                                        <input type="hidden"
                                               name="admin_id"
                                               value="<?= e($admin["id"]) ?>">

                                        <button type="submit"
                                                class="toggle-btn <?= !$isActive ? 'activate' : '' ?>">

                                            <?= $isActive ? "Deactivate" : "Activate" ?>

                                        </button>

                                    </form>

                                    <!-- DELETE ADMINISTRATOR -->
                     
                                    <?php if ((int)$admin["id"] !== 1): ?>

                                    <form method="POST"
                                          class="action-form"
                                          onsubmit="return confirm('WARNING: Permanently delete this administrator? This action cannot be undone.');">

                                        <input type="hidden"
                                               name="csrf_token"
                                               value="<?= e($_SESSION["csrf_token"]) ?>">

                                        <input type="hidden"
                                               name="action"
                                               value="delete">

                                        <input type="hidden"
                                               name="admin_id"
                                               value="<?= e($admin["id"]) ?>">

                                        <button type="submit" class="delete-btn">
                                            Delete
                                        </button>

                                    </form>
                                   <?php endif; ?>
                                <?php else: ?>

                                    <span style="color:#20c56b;">
                                        Current Account
                                    </span>

                                <?php endif; ?>

                            </td>

                        </tr>

                    <?php endforeach; ?>

                </tbody>

            </table>

        </div>

    </section>

</main>

</body>
</html>
