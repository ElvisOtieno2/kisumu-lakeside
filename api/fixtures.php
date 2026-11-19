
<?php
header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

try {
    $sql = "
        SELECT
            id,
            home_team,
            away_team,
            match_date,
            venue,
            status,
            home_score,
            away_score
        FROM fixtures
        ORDER BY
            match_date ASC NULLS LAST,
            id DESC
    ";

    $stmt = $pdo->query($sql);
    $fixtures = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "fixtures" => $fixtures
    ]);

} catch (PDOException $e) {
    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve fixtures."
    ]);
}
