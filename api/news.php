
<?php

header("Content-Type: application/json; charset=UTF-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

require_once __DIR__ . '/../config/db.php';

try {

    // Retrieve published news articles only
    $sql = "
        SELECT
            id,
            title,
            content,
            image_path,
            status,
            published_at
        FROM news
        WHERE status = 'published'
        ORDER BY published_at DESC NULLS LAST, id DESC
    ";

    $stmt = $pdo->query($sql);

    $articles = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "articles" => $articles
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve news articles."
    ]);
}
