
<?php

require_once __DIR__ . '/auth_check.php';

$error = "";
$success = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $fullName = trim($_POST["full_name"] ?? "");
    $jerseyNumber = filter_input(INPUT_POST, "jersey_number", FILTER_VALIDATE_INT);
    $position = trim($_POST["position"] ?? "");
    $biography = trim($_POST["biography"] ?? "");

    $ppg = filter_input(INPUT_POST, "ppg", FILTER_VALIDATE_FLOAT);
    $rpg = filter_input(INPUT_POST, "rpg", FILTER_VALIDATE_FLOAT);
    $apg = filter_input(INPUT_POST, "apg", FILTER_VALIDATE_FLOAT);
    $spg = filter_input(INPUT_POST, "spg", FILTER_VALIDATE_FLOAT);

    $allowedPositions = [
        "Point Guard",
        "Shooting Guard",
        "Small Forward",
        "Power Forward",
        "Center"
    ];

    if (
        $fullName === "" ||
        $jerseyNumber === false || $jerseyNumber === null ||
        $jerseyNumber < 0 || $jerseyNumber > 99 ||
        !in_array($position, $allowedPositions, true) ||
        $ppg === false || $ppg === null || $ppg < 0 ||
        $rpg === false || $rpg === null || $rpg < 0 ||
        $apg === false || $apg === null || $apg < 0 ||
        $spg === false || $spg === null || $spg < 0
    ) {
        $error = "Please enter valid player details and statistics.";
    } else {

        $photoPath = null;

        try {

            // Process profile photograph
            if (
                isset($_FILES["photo"]) &&
                $_FILES["photo"]["error"] !== UPLOAD_ERR_NO_FILE
            ) {

                if ($_FILES["photo"]["error"] !== UPLOAD_ERR_OK) {
                    throw new RuntimeException("Photo upload failed.");
                }

                if ($_FILES["photo"]["size"] > 5 * 1024 * 1024) {
                    throw new RuntimeException("Photo must not exceed 5 MB.");
                }

                $imageInfo = getimagesize($_FILES["photo"]["tmp_name"]);

                if ($imageInfo === false) {
                    throw new RuntimeException("Invalid image file.");
                }

                $allowedTypes = [
                    "image/jpeg" => "jpg",
                    "image/png" => "png",
                    "image/webp" => "webp"
                ];

                $mimeType = $imageInfo["mime"];

                if (!isset($allowedTypes[$mimeType])) {
                    throw new RuntimeException(
                        "Only JPG, PNG and WEBP images are allowed."
                    );
                }

                $uploadDirectory = __DIR__ . '/../uploads/';

                if (!is_dir($uploadDirectory)) {
                    throw new RuntimeException("Uploads folder not found.");
                }

                $fileName = bin2hex(random_bytes(16))
                    . "." . $allowedTypes[$mimeType];

                if (!move_uploaded_file(
                    $_FILES["photo"]["tmp_name"],
                    $uploadDirectory . $fileName
                )) {
                    throw new RuntimeException("Could not save photograph.");
                }

                $photoPath = "uploads/" . $fileName;
            }

            // Save player and statistics together
            $pdo->beginTransaction();

            $playerSql = "INSERT INTO players
                (full_name, jersey_number, position, biography, photo_path)
                VALUES
                (:full_name, :jersey_number, :position, :biography, :photo_path)
                RETURNING id";

            $playerStmt = $pdo->prepare($playerSql);

            $playerStmt->execute([
                ":full_name" => $fullName,
                ":jersey_number" => $jerseyNumber,
                ":position" => $position,
                ":biography" => $biography,
                ":photo_path" => $photoPath
            ]);

            $playerId = $playerStmt->fetchColumn();

            $statsSql = "INSERT INTO player_statistics
                (player_id, ppg, rpg, apg, spg)
                VALUES (:player_id, :ppg, :rpg, :apg, :spg)";

            $statsStmt = $pdo->prepare($statsSql);

            $statsStmt->execute([
                ":player_id" => $playerId,
                ":ppg" => $ppg,
                ":rpg" => $rpg,
                ":apg" => $apg,
                ":spg" => $spg
            ]);

            $pdo->commit();

            $success = "Player registered successfully!";

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if ($photoPath !== null && file_exists(__DIR__ . '/../' . $photoPath)) {
                unlink(__DIR__ . '/../' . $photoPath);
            }

            if ($e instanceof PDOException && $e->getCode() === "23505") {
                $error = "That jersey number is already assigned.";
            } else {
                error_log($e->getMessage());
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Unable to register player. Please try again.";
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">
<title>Add Player | Kisumu Lakeside</title>

<style>
* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px 15px;
    font-family: Arial, sans-serif;
    background: #080808;
    color: white;
}

.container {
    max-width: 800px;
    margin: auto;
    background: #151515;
    padding: 30px;
    border: 1px solid #087A3D;
    border-radius: 10px;
}

h1, h2 {
    color: #16A05A;
}

.form-grid {
    display: grid;
    grid-template-columns: repeat(2, 1fr);
    gap: 18px;
}

.form-group {
    margin-bottom: 18px;
}

.full-width {
    grid-column: 1 / -1;
}

label {
    display: block;
    margin-bottom: 8px;
    font-weight: bold;
}

input, select, textarea {
    width: 100%;
    padding: 13px;
    background: #222;
    border: 1px solid #444;
    color: white;
    border-radius: 5px;
}

textarea {
    min-height: 100px;
    resize: vertical;
}

button {
    padding: 14px 25px;
    background: #087A3D;
    border: none;
    color: white;
    border-radius: 5px;
    font-weight: bold;
    cursor: pointer;
}

button:hover {
    background: #16A05A;
}

.message {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.success {
    background: #123d27;
    color: #8ff0b5;
}

.error {
    background: #491b1b;
    color: #ffaaaa;
}

a {
    color: #16A05A;
    text-decoration: none;
}

@media(max-width: 600px) {
    .form-grid {
        grid-template-columns: 1fr;
    }
}
</style>
</head>

<body>

<div class="container">

    <h1>Register New Player</h1>
    <p>Add a player to the Kisumu Lakeside roster.</p>

    <p><a href="dashboard.php">← Back to Dashboard</a></p>

    <?php if ($success): ?>
        <div class="message success">
            <?php echo htmlspecialchars($success); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <h2>Player Information</h2>

        <div class="form-grid">

            <div class="form-group">
                <label>Full Name</label>
                <input type="text" name="full_name" required>
            </div>

            <div class="form-group">
                <label>Jersey Number</label>
                <input type="number" name="jersey_number"
                       min="0" max="99" required>
            </div>

            <div class="form-group">
                <label>Position</label>
                <select name="position" required>
                    <option value="">Select Position</option>
                    <option>Point Guard</option>
                    <option>Shooting Guard</option>
                    <option>Small Forward</option>
                    <option>Power Forward</option>
                    <option>Center</option>
                </select>
            </div>

            <div class="form-group">
                <label>Player Photograph</label>
                <input type="file" name="photo"
                       accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group full-width">
                <label>Player Biography</label>
                <textarea name="biography"
                          placeholder="Write a short player biography..."></textarea>
            </div>

        </div>

        <h2>Player Statistics</h2>
        <p>Enter the player's current season averages.</p>

        <div class="form-grid">

            <div class="form-group">
                <label>Points Per Game (PPG)</label>
                <input type="number" name="ppg"
                       min="0" max="999.99" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Rebounds Per Game (RPG)</label>
                <input type="number" name="rpg"
                       min="0" max="999.99" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Assists Per Game (APG)</label>
                <input type="number" name="apg"
                       min="0" max="999.99" step="0.01" required>
            </div>

            <div class="form-group">
                <label>Steals Per Game (SPG)</label>
                <input type="number" name="spg"
                       min="0" max="999.99" step="0.01" required>
            </div>

        </div>

        <button type="submit">SAVE PLAYER</button>

    </form>

</div>

</body>
</html>
