<?php

require_once __DIR__ . '/auth_check.php';
$message = "";
$error = "";

if ($_SERVER["REQUEST_METHOD"] === "POST") {

    $title = trim($_POST["title"] ?? "");
    $content = trim($_POST["content"] ?? "");
    $status = $_POST["status"] ?? "draft";

    if ($title === "" || $content === "") {
        $error = "Please provide both a headline and article content.";
    } elseif (!in_array($status, ["draft", "published"], true)) {
        $error = "Invalid publication status.";
    } else {

        $imagePath = null;

        // Handle optional cover image
        if (isset($_FILES["image"]) && $_FILES["image"]["error"] !== UPLOAD_ERR_NO_FILE) {

            if ($_FILES["image"]["error"] !== UPLOAD_ERR_OK) {
                $error = "The image upload failed.";
            } elseif ($_FILES["image"]["size"] > 5 * 1024 * 1024) {
                $error = "Image must not exceed 5MB.";
            } else {

                $imageInfo = getimagesize($_FILES["image"]["tmp_name"]);

                $allowedTypes = [
                    IMAGETYPE_JPEG => "jpg",
                    IMAGETYPE_PNG => "png",
                    IMAGETYPE_WEBP => "webp"
                ];

                if (
                    !$imageInfo ||
                    !isset($allowedTypes[$imageInfo[2]])
                ) {
                    $error = "Only JPEG, PNG and WebP images are allowed.";
                } else {

                    $uploadDirectory = __DIR__ . '/../uploads/';

                    if (!is_dir($uploadDirectory)) {
                        $error = "Uploads directory was not found.";
                    } else {

                        $filename = bin2hex(random_bytes(16)) . "." . $allowedTypes[$imageInfo[2]];

                        if (move_uploaded_file(
                            $_FILES["image"]["tmp_name"],
                            $uploadDirectory . $filename
                        )) {
                            $imagePath = "uploads/" . $filename;
                        } else {
                            $error = "Unable to save the uploaded image.";
                        }
                    }
                }
            }
        }

        if ($error === "") {

            try {

                $publishedAt = $status === "published"
                    ? date("Y-m-d H:i:s")
                    : null;

                $stmt = $pdo->prepare("
                    INSERT INTO news
                    (title, content, image_path, status, published_at)
                    VALUES (?, ?, ?, ?, ?)
                ");

                $stmt->execute([
                    $title,
                    $content,
                    $imagePath,
                    $status,
                    $publishedAt
                ]);

                header("Location: news.php");
                exit;

            } catch (PDOException $e) {

                error_log($e->getMessage());
                $error = "Unable to save the news article.";

                // Remove uploaded image if database save fails
                if ($imagePath && is_file(__DIR__ . '/../' . $imagePath)) {
                    unlink(__DIR__ . '/../' . $imagePath);
                }
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

    <title>Create News | Kisumu Lakeside</title>

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
            max-width: 850px;
            margin: 40px auto;
            padding: 30px;
            background: #151515;
            border-radius: 10px;
        }

        h1 {
            color: #16A05A;
        }

        label {
            display: block;
            margin-top: 22px;
            margin-bottom: 9px;
            font-weight: bold;
        }

        input, textarea, select {
            width: 100%;
            padding: 13px;
            background: #222;
            color: white;
            border: 1px solid #444;
            border-radius: 5px;
            font-size: 15px;
        }

        textarea {
            min-height: 220px;
            resize: vertical;
        }

        input[type="file"] {
            background: #222;
        }

        .button {
            display: inline-block;
            padding: 13px 20px;
            margin-top: 25px;
            background: #087A3D;
            color: white;
            border: none;
            border-radius: 5px;
            text-decoration: none;
            cursor: pointer;
            font-weight: bold;
        }

        .button:hover {
            background: #16A05A;
        }

        .back {
            margin-left: 10px;
            background: #333;
        }

        .error {
            background: #451919;
            color: #ffaaaa;
            padding: 13px;
            border-radius: 5px;
        }

    </style>

</head>

<body>

<div class="container">

    <h1>Create News Article</h1>

    <p>Publish club news, announcements and updates.</p>

    <?php if ($error): ?>
        <div class="error">
            <?php echo htmlspecialchars($error); ?>
        </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data">

        <label>News Headline</label>

        <input
            type="text"
            name="title"
            maxlength="200"
            placeholder="Enter news headline"
            value="<?php echo htmlspecialchars($_POST["title"] ?? ""); ?>"
            required
        >

        <label>Article Content</label>

        <textarea
            name="content"
            placeholder="Write your news article here..."
            required
        ><?php echo htmlspecialchars($_POST["content"] ?? ""); ?></textarea>

        <label>Cover Image (Optional)</label>

        <input
            type="file"
            name="image"
            accept=".jpg,.jpeg,.png,.webp"
        >

        <label>Publication Status</label>

        <select name="status">
            <option value="draft">Save as Draft</option>
            <option value="published">Publish Article</option>
        </select>

        <button type="submit" class="button">
            SAVE NEWS ARTICLE
        </button>

        <a href="news.php" class="button back">
            CANCEL
        </a>

    </form>

</div>

</body>
</html>
