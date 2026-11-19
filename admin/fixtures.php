<?php
require_once __DIR__ . '/auth_check.php';

$success = "";
$error = "";

$allowedStatuses = [
    "upcoming",
    "completed",
    "postponed",
    "cancelled"
];

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $homeTeam = trim($_POST["home_team"] ?? "");
    $awayTeam = trim($_POST["away_team"] ?? "");
    $matchDate = trim($_POST["match_date"] ?? "");
    $venue = trim($_POST["venue"] ?? "");
    $status = $_POST["status"] ?? "upcoming";

    $homeScore = trim($_POST["home_score"] ?? "");
    $awayScore = trim($_POST["away_score"] ?? "");

    if ($homeTeam === "" || $awayTeam === "") {
        $error = "Both team names are required.";

    } elseif (!in_array($status, $allowedStatuses, true)) {
        $error = "Invalid match status.";

    } elseif (
        ($homeScore !== "" && (!ctype_digit($homeScore) || (int)$homeScore < 0)) ||
        ($awayScore !== "" && (!ctype_digit($awayScore) || (int)$awayScore < 0))
    ) {
        $error = "Scores must be non-negative whole numbers.";

    } elseif (
        ($homeScore === "") !== ($awayScore === "")
    ) {
        $error = "Enter both scores or leave both blank.";

    } elseif (
        $status === "completed" &&
        ($homeScore === "" || $awayScore === "")
    ) {
        $error = "Completed matches must have both scores.";

    } elseif (
        $status === "upcoming" &&
        ($homeScore !== "" || $awayScore !== "")
    ) {
        $error = "Upcoming matches should not have scores.";

    } else {

        $dateValue = null;

        if ($matchDate !== "") {
            $dateObject = DateTime::createFromFormat("Y-m-d", $matchDate);

            if (!$dateObject || $dateObject->format("Y-m-d") !== $matchDate) {
                $error = "Please enter a valid match date.";
            } else {
                $dateValue = $matchDate;
            }
        }

        if ($error === "") {

            try {

                $sql = "
                    INSERT INTO fixtures (
                        home_team,
                        away_team,
                        match_date,
                        venue,
                        status,
                        home_score,
                        away_score
                    )
                    VALUES (
                        :home_team,
                        :away_team,
                        :match_date,
                        :venue,
                        :status,
                        :home_score,
                        :away_score
                    )
                ";

                $stmt = $pdo->prepare($sql);

                $stmt->execute([
                    ":home_team" => $homeTeam,
                    ":away_team" => $awayTeam,
                    ":match_date" => $dateValue,
                    ":venue" => $venue !== "" ? $venue : null,
                    ":status" => $status,
                    ":home_score" => $homeScore !== "" ? (int)$homeScore : null,
                    ":away_score" => $awayScore !== "" ? (int)$awayScore : null
                ]);

                header("Location: fixtures.php?added=1");
                exit;

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $error = "Unable to save the fixture.";

            }

        }
    }
}

if (isset($_GET["added"])) {
    $success = "Fixture added successfully.";
}

try {

    $stmt = $pdo->query("
        SELECT *
        FROM fixtures
        ORDER BY match_date DESC NULLS LAST, id DESC
    ");

    $fixtures = $stmt->fetchAll(PDO::FETCH_ASSOC);

} catch (PDOException $e) {

    error_log($e->getMessage());
    $fixtures = [];
    $error = "Unable to retrieve fixtures.";

}

?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Manage Fixtures | Kisumu Lakeside</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>

        * {
            box-sizing: border-box;
            margin: 0;
            padding: 0;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f3f5f4;
            color: #202820;
            padding: 30px;
        }

        .container {
            max-width: 1150px;
            margin: auto;
        }

        .top-bar {
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            margin-bottom: 30px;
            flex-wrap: wrap;
        }

        h1 {
            font-size: 25px;
            font-weight: 800;
        }

        .subtitle {
            color: #777;
            margin-top: 7px;
            font-size: 13px;
        }

        .back-btn {
            background: #080808;
            color: white;
            padding: 12px 18px;
            border-radius: 6px;
            text-decoration: none;
            font-size: 13px;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 10px;
            margin-bottom: 25px;
            box-shadow: 0 3px 15px rgba(0,0,0,0.04);
        }

        h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-grid {
            display: grid;
            grid-template-columns: repeat(2, 1fr);
            gap: 18px;
        }

        label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            margin-bottom: 8px;
        }

        input, select {
            width: 100%;
            padding: 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
            font-family: inherit;
            font-size: 13px;
        }

        input:focus, select:focus {
            outline: 2px solid #087a3d;
            border-color: #087a3d;
        }

        .submit-btn {
            background: #087a3d;
            color: white;
            border: none;
            padding: 13px 22px;
            border-radius: 5px;
            font-weight: 700;
            cursor: pointer;
            margin-top: 20px;
        }

        .submit-btn:hover {
            background: #065c2d;
        }

        .message {
            padding: 13px;
            border-radius: 5px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .success {
            background: #e0f5e8;
            color: #087a3d;
        }

        .error {
            background: #ffe5e5;
            color: #b00020;
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
            padding: 14px;
            border-bottom: 1px solid #eee;
            text-align: left;
            font-size: 12px;
        }

        th {
            background: #f5f7f5;
            color: #555;
        }

        .status {
            padding: 6px 9px;
            border-radius: 20px;
            font-size: 10px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .upcoming {
            background: #e0f5e8;
            color: #087a3d;
        }

        .completed {
            background: #e3eaff;
            color: #2451a6;
        }

        .postponed {
            background: #fff0d8;
            color: #9a6200;
        }

        .cancelled {
            background: #ffe5e5;
            color: #b00020;
        }

        @media(max-width: 650px) {

            body {
                padding: 15px;
            }

            .form-grid {
                grid-template-columns: 1fr;
            }

            .panel {
                padding: 18px;
            }

        }

    </style>

</head>

<body>

<div class="container">

    <div class="top-bar">

        <div>
            <h1>Fixtures Management</h1>
            <p class="subtitle">
                Manage Kisumu Lakeside matches and results.
            </p>
        </div>

        <a href="dashboard.php" class="back-btn">
            Back to Dashboard
        </a>

    </div>


    <?php if ($success): ?>
        <div class="message success">
            <?= htmlspecialchars($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="message error">
            <?= htmlspecialchars($error) ?>
        </div>
    <?php endif; ?>


    <div class="panel">

        <h2>Add New Fixture</h2>

        <form method="POST">

            <div class="form-grid">

                <div>
                    <label>Home Team</label>
                    <input
                        type="text"
                        name="home_team"
                        value="Kisumu Lakeside"
                        maxlength="100"
                        required
                    >
                </div>

                <div>
                    <label>Away Team / Opponent</label>
                    <input
                        type="text"
                        name="away_team"
                        placeholder="Enter opponent name"
                        maxlength="100"
                        required
                    >
                </div>

                <div>
                    <label>Match Date</label>
                    <input
                        type="date"
                        name="match_date"
                    >
                </div>

                <div>
                    <label>Venue</label>
                    <input
                        type="text"
                        name="venue"
                        placeholder="Enter match venue"
                        maxlength="150"
                    >
                </div>

                <div>
                    <label>Match Status</label>

                    <select name="status" id="status">

                        <option value="upcoming">Upcoming</option>
                        <option value="completed">Completed</option>
                        <option value="postponed">Postponed</option>
                        <option value="cancelled">Cancelled</option>

                    </select>
                </div>

                <div>
                    <label>Home Team Score</label>
                    <input
                        type="number"
                        name="home_score"
                        min="0"
                        placeholder="Leave blank if not played"
                    >
                </div>

                <div>
                    <label>Away Team Score</label>
                    <input
                        type="number"
                        name="away_score"
                        min="0"
                        placeholder="Leave blank if not played"
                    >
                </div>

            </div>

            <button type="submit" class="submit-btn">
                Save Fixture
            </button>

        </form>

    </div>


    <div class="panel">

        <h2>Registered Fixtures</h2>

        <div class="table-wrapper">

            <table>

                <thead>
                    <tr>
                        <th>Home Team</th>
                        <th>Away Team</th>
                        <th>Date</th>
                        <th>Venue</th>
                        <th>Score</th>
                        <th>Status</th>
                    </tr>
                </thead>

                <tbody>

                <?php if (count($fixtures) > 0): ?>

                    <?php foreach ($fixtures as $fixture): ?>

                        <tr>

                            <td>
                                <?= htmlspecialchars($fixture["home_team"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($fixture["away_team"]) ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($fixture["match_date"] ?? "TBA") ?>
                            </td>

                            <td>
                                <?= htmlspecialchars($fixture["venue"] ?? "TBA") ?>
                            </td>

                            <td>
                                <?php
                                if ($fixture["home_score"] !== null && $fixture["away_score"] !== null) {
                                    echo (int)$fixture["home_score"] . " - " . (int)$fixture["away_score"];
                                } else {
                                    echo "VS";
                                }
                                ?>
                            </td>

                            <td>
                                <span class="status <?= htmlspecialchars($fixture["status"]) ?>">
                                    <?= htmlspecialchars($fixture["status"]) ?>
                                </span>
                            </td>

                        </tr>

                    <?php endforeach; ?>

                <?php else: ?>

                    <tr>
                        <td colspan="6">
                            No fixtures have been registered yet.
                        </td>
                    </tr>

                <?php endif; ?>

                </tbody>

            </table>

        </div>

    </div>

</div>

</body>
</html>
