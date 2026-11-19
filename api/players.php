
<?php

header("Content-Type: application/json; charset=UTF-8");

require_once __DIR__ . '/../config/db.php';

try {

    $sql = "
        SELECT
            p.id,
            p.full_name,
            p.jersey_number,
            p.position,
            p.biography,
            p.photo_path,

            COALESCE(s.ppg, 0) AS ppg,
            COALESCE(s.rpg, 0) AS rpg,
            COALESCE(s.apg, 0) AS apg,
            COALESCE(s.spg, 0) AS spg

        FROM players p

        LEFT JOIN player_statistics s
            ON p.id = s.player_id

        ORDER BY p.jersey_number ASC
    ";

    $stmt = $pdo->query($sql);

    $players = $stmt->fetchAll(PDO::FETCH_ASSOC);

    echo json_encode([
        "success" => true,
        "players" => $players
    ]);

} catch (PDOException $e) {

    error_log($e->getMessage());

    http_response_code(500);

    echo json_encode([
        "success" => false,
        "message" => "Unable to retrieve players."
    ]);
}
