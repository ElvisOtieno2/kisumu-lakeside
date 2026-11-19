<?php

require_once __DIR__ . '/auth_check.php';

// Only allow POST requests
if ($_SERVER["REQUEST_METHOD"] !== "POST") {
    header("Location: players.php");
    exit;
}

$id = filter_input(INPUT_POST, "id", FILTER_VALIDATE_INT);

if (!$id || $id < 1) {
    header("Location: players.php?error=invalid");
    exit;
}

try {

    // Get photograph path before deleting player
    $stmt = $pdo->prepare(
        "SELECT photo_path FROM players WHERE id = :id"
    );

    $stmt->execute([":id" => $id]);

    $player = $stmt->fetch(PDO::FETCH_ASSOC);

    if (!$player) {
        header("Location: players.php?error=notfound");
        exit;
    }

    // Delete player (statistics are removed through ON DELETE CASCADE)
    $delete = $pdo->prepare(
        "DELETE FROM players WHERE id = :id"
    );

    $delete->execute([":id" => $id]);

    // Delete photograph from uploads folder
    if (!empty($player["photo_path"])) {

        $uploadDirectory = realpath(dirname(__DIR__) . '/uploads');
        $photoPath = realpath(
            dirname(__DIR__) . '/' . $player["photo_path"]
        );

        // Ensure the file is inside uploads before deleting it
        if (
            $uploadDirectory !== false &&
            $photoPath !== false &&
            str_starts_with(
                $photoPath,
                $uploadDirectory . DIRECTORY_SEPARATOR
            ) &&
            is_file($photoPath)
        ) {
            unlink($photoPath);
        }
    }

    header("Location: players.php?deleted=1");
    exit;

} catch (PDOException $e) {

    error_log($e->getMessage());

    header("Location: players.php?error=deletefailed");
    exit;
}
