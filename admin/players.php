
<?php

require_once __DIR__ . '/auth_check.php';

// Search functionality
$search = trim($_GET["search"] ?? "");

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
";

if ($search !== "") {
    $sql .= "
        WHERE p.full_name ILIKE :search
        OR CAST(p.jersey_number AS TEXT) ILIKE :search
    ";
}

$sql .= " ORDER BY p.jersey_number ASC";

$stmt = $pdo->prepare($sql);

if ($search !== "") {
    $stmt->execute([":search" => "%" . $search . "%"]);
} else {
    $stmt->execute();
}

$players = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">

<head>

<meta charset="UTF-8">
<meta name="viewport" content="width=device-width, initial-scale=1.0">

<title>Manage Players | Kisumu Lakeside</title>

<style>

* {
    box-sizing: border-box;
}

body {
    margin: 0;
    padding: 30px;
    font-family: Arial, sans-serif;
    background: #080808;
    color: white;
}

.container {
    max-width: 1200px;
    margin: auto;
}

.header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    flex-wrap: wrap;
    gap: 15px;
    margin-bottom: 30px;
}

h1 {
    color: #16A05A;
}

.button {
    display: inline-block;
    padding: 12px 18px;
    background: #087A3D;
    color: white;
    text-decoration: none;
    border-radius: 5px;
    margin: 4px;
    border: none;
    cursor: pointer;
}

.button:hover {
    background: #16A05A;
}

.search-form {
    display: flex;
    gap: 10px;
    margin-bottom: 25px;
    flex-wrap: wrap;
}

.search-form input {
    flex: 1;
    min-width: 200px;
    padding: 13px;
    background: #181818;
    border: 1px solid #444;
    color: white;
    border-radius: 5px;
}

.table-container {
    overflow-x: auto;
    background: #151515;
    border-radius: 8px;
}

table {
    width: 100%;
    min-width: 850px;
    border-collapse: collapse;
}

th, td {
    padding: 15px;
    text-align: left;
    border-bottom: 1px solid #333;
}

th {
    color: #16A05A;
    background: #202020;
}

.player-photo {
    width: 55px;
    height: 55px;
    object-fit: cover;
    border-radius: 50%;
}

.no-photo {
    width: 55px;
    height: 55px;
    background: #087A3D;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: bold;
}

.edit {
    background: #087A3D;
}

.delete {
    background: #8b2222;
}

.delete:hover {
    background: #b52c2c;
}

.empty {
    text-align: center;
    padding: 35px;
    color: #aaa;
}

@media(max-width: 600px) {
    body {
        padding: 15px;
    }
}

</style>

</head>

<body>

<div class="container">

    <div class="header">

        <div>
            <h1>Players Management</h1>
            <p>Manage the Kisumu Lakeside player roster.</p>
        </div>

        <div>
            <a href="dashboard.php" class="button">Dashboard</a>

            <a href="add_player.php" class="button">
                + Add Player
            </a>
        </div>

    </div>


    <!-- Search -->

    <form method="GET" class="search-form">

        <input
            type="text"
            name="search"
            placeholder="Search by player name or jersey number..."
            value="<?php echo htmlspecialchars($search); ?>"
        >

        <button type="submit" class="button">
            Search
        </button>

        <a href="players.php" class="button">
            Reset
        </a>

    </form>


    <!-- Players Table -->

    <div class="table-container">

        <table>

            <thead>

                <tr>
                    <th>Photo</th>
                    <th>Player</th>
                    <th>Number</th>
                    <th>Position</th>
                    <th>PPG</th>
                    <th>RPG</th>
                    <th>APG</th>
                    <th>SPG</th>
                    <th>Actions</th>
                </tr>

            </thead>

            <tbody>

            <?php if (count($players) > 0): ?>

                <?php foreach ($players as $player): ?>

                    <tr>

                        <td>

                            <?php if (!empty($player["photo_path"])): ?>

                                <img
                                    src="../<?php echo htmlspecialchars($player["photo_path"]); ?>"
                                    class="player-photo"
                                    alt="Player photograph"
                                >

                            <?php else: ?>

                                <div class="no-photo">
                                    <?php echo (int)$player["jersey_number"]; ?>
                                </div>

                            <?php endif; ?>

                        </td>

                        <td>
                            <?php echo htmlspecialchars($player["full_name"]); ?>
                        </td>

                        <td>
                            #<?php echo (int)$player["jersey_number"]; ?>
                        </td>

                        <td>
                            <?php echo htmlspecialchars($player["position"] ?? "Not specified"); ?>
                        </td>

                        <td><?php echo number_format((float)$player["ppg"], 1); ?></td>
                        <td><?php echo number_format((float)$player["rpg"], 1); ?></td>
                        <td><?php echo number_format((float)$player["apg"], 1); ?></td>
                        <td><?php echo number_format((float)$player["spg"], 1); ?></td>

                        <td>

                            <a
                                href="edit_player.php?id=<?php echo (int)$player["id"]; ?>"
                                class="button edit"
                            >
                                Edit
                            </a>

                            <form
                                method="POST"
                                action="delete_player.php"
                                style="display:inline;"
                                onsubmit="return confirm('Are you sure you want to delete this player?');"
                            >

                                <input
                                    type="hidden"
                                    name="id"
                                    value="<?php echo (int)$player["id"]; ?>"
                                >

                                <button type="submit" class="button delete">
                                    Delete
                                </button>

                            </form>

                        </td>

                    </tr>

                <?php endforeach; ?>

            <?php else: ?>

                <tr>
                    <td colspan="9" class="empty">
                        No players found.
                    </td>
                </tr>

            <?php endif; ?>

            </tbody>

        </table>

    </div>

    <p>
        Total players displayed: <?php echo count($players); ?>
    </p>

</div>

</body>
</html>
