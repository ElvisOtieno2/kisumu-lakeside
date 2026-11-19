
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

try {

    $sql = "
        SELECT
            id,
            image_path,
            caption,
            description,
            uploaded_at
        FROM gallery
        ORDER BY uploaded_at DESC, id DESC
    ";

    $stmt = $pdo->query($sql);

    $photos = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "photos" => $photos
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve gallery photos."
    ]);
}
