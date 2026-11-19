
<?php

require_once __DIR__ . '/auth_check.php';

$id = filter_input(INPUT_GET, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    exit("Invalid player ID.");
}

// Fetch player and statistics
$stmt = $pdo->prepare("
    SELECT p.*, s.ppg, s.rpg, s.apg, s.spg
    FROM players p
    LEFT JOIN player_statistics s ON p.id = s.player_id
    WHERE p.id = :id
");

$stmt->execute([":id" => $id]);
$player = $stmt->fetch(PDO::FETCH_ASSOC);

if (!$player) {
    exit("Player not found.");
}

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

        $newPhotoPath = $player["photo_path"];

        try {

            // Handle optional replacement photograph
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

                $uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR
                    . 'uploads' . DIRECTORY_SEPARATOR;

                if (!is_dir($uploadDirectory)) {
                    mkdir($uploadDirectory, 0755, true);
                }

                if (!is_writable($uploadDirectory)) {
                    throw new RuntimeException("Uploads folder is not writable.");
                }

                $fileName = bin2hex(random_bytes(16))
                    . "." . $allowedTypes[$mimeType];

                if (!move_uploaded_file(
                    $_FILES["photo"]["tmp_name"],
                    $uploadDirectory . $fileName
                )) {
                    throw new RuntimeException("Could not save photograph.");
                }

                $newPhotoPath = "uploads/" . $fileName;
            }

            $pdo->beginTransaction();

            // Update player profile
            $updatePlayer = $pdo->prepare("
                UPDATE players
                SET full_name = :full_name,
                    jersey_number = :jersey_number,
                    position = :position,
                    biography = :biography,
                    photo_path = :photo_path
                WHERE id = :id
            ");

            $updatePlayer->execute([
                ":full_name" => $fullName,
                ":jersey_number" => $jerseyNumber,
                ":position" => $position,
                ":biography" => $biography,
                ":photo_path" => $newPhotoPath,
                ":id" => $id
            ]);

            // Update or create statistics
            $updateStats = $pdo->prepare("
                INSERT INTO player_statistics
                    (player_id, ppg, rpg, apg, spg, updated_at)
                VALUES
                    (:player_id, :ppg, :rpg, :apg, :spg, CURRENT_TIMESTAMP)
                ON CONFLICT (player_id)
                DO UPDATE SET
                    ppg = EXCLUDED.ppg,
                    rpg = EXCLUDED.rpg,
                    apg = EXCLUDED.apg,
                    spg = EXCLUDED.spg,
                    updated_at = CURRENT_TIMESTAMP
            ");

            $updateStats->execute([
                ":player_id" => $id,
                ":ppg" => $ppg,
                ":rpg" => $rpg,
                ":apg" => $apg,
                ":spg" => $spg
            ]);

            $pdo->commit();

            // Delete old photo only after successful database update
            if (
                $newPhotoPath !== $player["photo_path"] &&
                !empty($player["photo_path"])
            ) {
                $oldPhoto = dirname(__DIR__) . DIRECTORY_SEPARATOR
                    . str_replace("/", DIRECTORY_SEPARATOR, $player["photo_path"]);

                if (is_file($oldPhoto)) {
                    unlink($oldPhoto);
                }
            }

            header("Location: players.php?updated=1");
            exit;

        } catch (Throwable $e) {

            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }

            if (
                $newPhotoPath !== $player["photo_path"] &&
                !empty($newPhotoPath)
            ) {
                $newPhoto = dirname(__DIR__) . DIRECTORY_SEPARATOR
                    . str_replace("/", DIRECTORY_SEPARATOR, $newPhotoPath);

                if (is_file($newPhoto)) {
                    unlink($newPhoto);
                }
            }

            if ($e instanceof PDOException && $e->getCode() === "23505") {
                $error = "That jersey number is already assigned.";
            } else {
                error_log($e->getMessage());
                $error = $e instanceof RuntimeException
                    ? $e->getMessage()
                    : "Unable to update player. Please try again.";
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

<title>Edit Player | Kisumu Lakeside</title>

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

a {
    color: #16A05A;
    text-decoration: none;
}

.photo-preview {
    width: 120px;
    height: 120px;
    object-fit: cover;
    border-radius: 50%;
    margin-bottom: 15px;
}

.message {
    padding: 12px;
    margin-bottom: 20px;
    border-radius: 5px;
}

.error {
    background: #491b1b;
    color: #ffaaaa;
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

    <h1>Edit Player</h1>

    <p>Update player information and performance statistics.</p>

    <p><a href="players.php">← Back to Players</a></p>

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
                <input type="text" name="full_name"
                    value="<?php echo htmlspecialchars($player["full_name"]); ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Jersey Number</label>
                <input type="number" name="jersey_number"
                    min="0" max="99"
                    value="<?php echo (int)$player["jersey_number"]; ?>"
                    required>
            </div>

            <div class="form-group">
                <label>Position</label>

                <select name="position" required>

                    <?php
                    $positions = [
                        "Point Guard",
                        "Shooting Guard",
                        "Small Forward",
                        "Power Forward",
                        "Center"
                    ];
                    ?>

                    <?php foreach ($positions as $position): ?>

                        <option value="<?php echo htmlspecialchars($position); ?>"
                            <?php echo ($player["position"] === $position) ? "selected" : ""; ?>>

                            <?php echo htmlspecialchars($position); ?>

                        </option>

                    <?php endforeach; ?>

                </select>
            </div>

            <div class="form-group">
                <label>Replace Photograph (Optional)</label>

                <?php if (!empty($player["photo_path"])): ?>
                    <img
                        src="../<?php echo htmlspecialchars($player["photo_path"]); ?>"
                        class="photo-preview"
                        alt="Current player photograph">
                <?php else: ?>
                    <p>No photograph uploaded.</p>
                <?php endif; ?>

                <input type="file" name="photo"
                    accept=".jpg,.jpeg,.png,.webp,image/jpeg,image/png,image/webp">
            </div>

            <div class="form-group full-width">
                <label>Biography</label>

                <textarea name="biography"><?php echo htmlspecialchars($player["biography"] ?? ""); ?></textarea>
            </div>

        </div>

        <h2>Performance Statistics</h2>

        <div class="form-grid">

            <div class="form-group">
                <label>PPG</label>
                <input type="number" name="ppg" step="0.01"
                    min="0" max="999.99"
                    value="<?php echo htmlspecialchars((string)($player["ppg"] ?? 0)); ?>"
                    required>
            </div>

            <div class="form-group">
                <label>RPG</label>
                <input type="number" name="rpg" step="0.01"
                    min="0" max="999.99"
                    value="<?php echo htmlspecialchars((string)($player["rpg"] ?? 0)); ?>"
                    required>
            </div>

            <div class="form-group">
                <label>APG</label>
                <input type="number" name="apg" step="0.01"
                    min="0" max="999.99"
                    value="<?php echo htmlspecialchars((string)($player["apg"] ?? 0)); ?>"
                    required>
            </div>

            <div class="form-group">
                <label>SPG</label>
                <input type="number" name="spg" step="0.01"
                    min="0" max="999.99"
                    value="<?php echo htmlspecialchars((string)($player["spg"] ?? 0)); ?>"
                    required>
            </div>

        </div>

        <button type="submit">SAVE CHANGES</button>

    </form>

</div>

</body>
</html>
