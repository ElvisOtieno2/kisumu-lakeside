<?php

require_once __DIR__ . '/auth_check.php';

$message = "";
$error = "";

// Handle news deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_news"])) {

    $newsId = filter_input(INPUT_POST, "news_id", FILTER_VALIDATE_INT);

    if ($newsId) {
        try {
            $stmt = $pdo->prepare("SELECT image_path FROM news WHERE id = ?");
            $stmt->execute([$newsId]);
            $article = $stmt->fetch(PDO::FETCH_ASSOC);

            if ($article) {
                $delete = $pdo->prepare("DELETE FROM news WHERE id = ?");
                $delete->execute([$newsId]);

                if (!empty($article["image_path"])) {
                    $uploadDirectory = realpath(__DIR__ . '/../uploads');
                    $imageFile = realpath(__DIR__ . '/../' . $article["image_path"]);

                    if (
                        $uploadDirectory &&
                        $imageFile &&
                        strpos($imageFile, $uploadDirectory . DIRECTORY_SEPARATOR) === 0 &&
                        is_file($imageFile)
                    ) {
                        unlink($imageFile);
                    }
                }

                $message = "News article deleted successfully.";
            } else {
                $error = "News article not found.";
            }

        } catch (PDOException $e) {
            error_log($e->getMessage());
            $error = "Unable to delete the news article.";
        }
    }
}

// Fetch news articles
$stmt = $pdo->query("
    SELECT *
    FROM news
    ORDER BY created_at DESC, id DESC
");

$newsArticles = $stmt->fetchAll(PDO::FETCH_ASSOC);

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>News Management | Kisumu Lakeside</title>

    <style>
        * {
            box-sizing: border-box;
        }

        body {
            margin: 0;
            background: #080808;
            color: white;
            font-family: Arial, sans-serif;
        }

        .container {
            max-width: 1200px;
            margin: auto;
            padding: 35px 20px;
        }

        .header {
            display: flex;
            justify-content: space-between;
            align-items: center;
            flex-wrap: wrap;
            gap: 15px;
            margin-bottom: 30px;
        }

        h1, h2 {
            color: #16A05A;
        }

        .button {
            display: inline-block;
            padding: 12px 18px;
            background: #087A3D;
            color: white;
            text-decoration: none;
            border: none;
            border-radius: 5px;
            cursor: pointer;
            font-weight: bold;
        }

        .button:hover {
            background: #16A05A;
        }

        .message {
            background: #123b23;
            padding: 14px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #8df0b4;
        }

        .error {
            background: #451919;
            padding: 14px;
            border-radius: 5px;
            margin-bottom: 20px;
            color: #ffaaaa;
        }

        .news-card {
            display: flex;
            gap: 20px;
            align-items: center;
            background: #151515;
            padding: 20px;
            margin-bottom: 18px;
            border-radius: 8px;
            border: 1px solid #292929;
        }

        .news-card img {
            width: 150px;
            height: 110px;
            object-fit: cover;
            border-radius: 6px;
        }

        .news-info {
            flex: 1;
        }

        .news-info h3 {
            margin-top: 0;
        }

        .news-info p {
            color: #aaa;
        }

        .status {
            display: inline-block;
            padding: 6px 10px;
            border-radius: 4px;
            font-size: 12px;
            font-weight: bold;
        }

        .published {
            background: #164d2b;
            color: #8df0b4;
        }

        .draft {
            background: #51401b;
            color: #ffdc83;
        }

        .delete-button {
            background: #a32222;
            color: white;
            padding: 9px 13px;
            border: none;
            border-radius: 5px;
            cursor: pointer;
        }

        .empty {
            background: #151515;
            padding: 30px;
            text-align: center;
            color: #aaa;
            border-radius: 8px;
        }

        @media(max-width: 650px) {
            .news-card {
                flex-direction: column;
                align-items: flex-start;
            }

            .news-card img {
                width: 100%;
                height: 180px;
            }
        }
    </style>
</head>

<body>

<div class="container">

    <div class="header">

        <div>
            <h1>News Management</h1>
            <p>Manage Kisumu Lakeside news and announcements.</p>
        </div>

        <a href="dashboard.php" class="button">← Dashboard</a>

    </div>

    <?php if ($message): ?>
        <div class="message">
            <?php echo htmlspecialchars($message); ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <a href="add_news.php" class="button">
        + Create News Article
    </a>

    <h2>Published & Draft Articles</h2>

    <?php if (count($newsArticles) > 0): ?>

        <?php foreach ($newsArticles as $article): ?>

            <div class="news-card">

                <?php if (!empty($article["image_path"])): ?>
                    <img
                        src="../<?php echo htmlspecialchars($article["image_path"]); ?>"
                        alt="News cover"
                    >
                <?php endif; ?>

                <div class="news-info">

                    <h3>
                        <?php echo htmlspecialchars($article["title"]); ?>
                    </h3>

                    <p>
                        <?php echo htmlspecialchars(substr($article["content"], 0, 150)); ?>
                    </p>

                    <span class="status <?php echo htmlspecialchars($article["status"]); ?>">
                        <?php echo strtoupper(htmlspecialchars($article["status"])); ?>
                    </span>

                    <p>
                        Created:
                        <?php echo htmlspecialchars($article["created_at"]); ?>
                    </p>

                    <a
                        href="edit_news.php?id=<?php echo (int)$article["id"]; ?>"
                        class="button"
                    >
                        Edit
                    </a>

                    <form method="POST" style="display:inline;"
                          onsubmit="return confirm('Delete this news article?');">

                        <input
                            type="hidden"
                            name="news_id"
                            value="<?php echo (int)$article["id"]; ?>"
                        >

                        <button
                            type="submit"
                            name="delete_news"
                            class="delete-button"
                        >
                            Delete
                        </button>

                    </form>

                </div>

            </div>

        <?php endforeach; ?>

    <?php else: ?>

        <div class="empty">
            <h3>No news articles yet.</h3>
            <p>Create your first club announcement.</p>
        </div>

    <?php endif; ?>

</div>

</body>
</html>
