
<?php
require_once __DIR__ . '/auth_check.php';

$success = "";
$error = "";

// Upload directory
$uploadDirectory = dirname(__DIR__) . DIRECTORY_SEPARATOR . 'uploads' . DIRECTORY_SEPARATOR;

// Create uploads folder if missing
if (!is_dir($uploadDirectory)) {
    mkdir($uploadDirectory, 0755, true);
}

// Handle photo upload
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["upload_photo"])) {

    $caption = trim($_POST["caption"] ?? "");
    $description = trim($_POST["description"] ?? "");

    if (!isset($_FILES["gallery_image"]) ||
        $_FILES["gallery_image"]["error"] !== UPLOAD_ERR_OK) {

        $error = "Please select a valid image.";

    } elseif ($_FILES["gallery_image"]["size"] > 5 * 1024 * 1024) {

        $error = "Image must not exceed 5MB.";

    } else {

        $temporaryFile = $_FILES["gallery_image"]["tmp_name"];

        $imageInfo = getimagesize($temporaryFile);

        $allowedTypes = [
            "image/jpeg" => "jpg",
            "image/png" => "png",
            "image/webp" => "webp"
        ];

        $mimeType = $imageInfo["mime"] ?? "";

        if (!$imageInfo || !isset($allowedTypes[$mimeType])) {

            $error = "Only JPEG, PNG and WebP images are allowed.";

        } else {

            $extension = $allowedTypes[$mimeType];

            $fileName = bin2hex(random_bytes(16)) . "." . $extension;

            $destination = $uploadDirectory . $fileName;

            if (!move_uploaded_file($temporaryFile, $destination)) {

                $error = "Unable to upload the image.";

            } else {

                $imagePath = "uploads/" . $fileName;

                try {

                    $stmt = $pdo->prepare("
                        INSERT INTO gallery
                        (image_path, caption, description)
                        VALUES (:image_path, :caption, :description)
                    ");

                    $stmt->execute([
                        ":image_path" => $imagePath,
                        ":caption" => $caption,
                        ":description" => $description
                    ]);

                    header("Location: gallery.php?uploaded=1");
                    exit;

                } catch (PDOException $e) {

                    error_log($e->getMessage());

                    if (is_file($destination)) {
                        unlink($destination);
                    }

                    $error = "Unable to save the image information.";
                }
            }
        }
    }
}


// Handle photo deletion
if ($_SERVER["REQUEST_METHOD"] === "POST" && isset($_POST["delete_photo"])) {

    $photoId = filter_var(
        $_POST["photo_id"] ?? null,
        FILTER_VALIDATE_INT
    );

    if (!$photoId) {

        $error = "Invalid photo selected.";

    } else {

        try {

            $stmt = $pdo->prepare("
                SELECT image_path
                FROM gallery
                WHERE id = :id
            ");

            $stmt->execute([":id" => $photoId]);

            $photo = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$photo) {

                $error = "Photo not found.";

            } else {

                $delete = $pdo->prepare("
                    DELETE FROM gallery
                    WHERE id = :id
                ");

                $delete->execute([":id" => $photoId]);

                // Delete the physical image only if it is inside uploads
                $uploadsPath = realpath($uploadDirectory);
                $imageFullPath = realpath(dirname(__DIR__) . DIRECTORY_SEPARATOR . $photo["image_path"]);

                if (
                    $imageFullPath !== false &&
                    $uploadsPath !== false &&
                    dirname($imageFullPath) === $uploadsPath &&
                    is_file($imageFullPath)
                ) {
                    unlink($imageFullPath);
                }

                header("Location: gallery.php?deleted=1");
                exit;
            }

        } catch (PDOException $e) {

            error_log($e->getMessage());

            $error = "Unable to delete the photo.";
        }
    }
}


// Success messages
if (isset($_GET["uploaded"])) {
    $success = "Photo uploaded successfully.";
}

if (isset($_GET["deleted"])) {
    $success = "Photo deleted successfully.";
}


// Retrieve gallery photos
$stmt = $pdo->query("
    SELECT *
    FROM gallery
    ORDER BY uploaded_at DESC, id DESC
");

$photos = $stmt->fetchAll(PDO::FETCH_ASSOC);


function escape($value) {
    return htmlspecialchars((string)$value, ENT_QUOTES, "UTF-8");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">

    <title>Gallery Management | Kisumu Lakeside</title>

    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;500;600;700;800&display=swap" rel="stylesheet">

    <style>
        * {
            margin: 0;
            padding: 0;
            box-sizing: border-box;
        }

        body {
            font-family: 'Montserrat', sans-serif;
            background: #f3f5f4;
            color: #222;
        }

        header {
            background: #101b16;
            color: white;
            padding: 22px 6%;
            display: flex;
            justify-content: space-between;
            align-items: center;
            gap: 15px;
            flex-wrap: wrap;
        }

        header h2 {
            color: #42c77a;
            font-size: 20px;
        }

        header a {
            color: white;
            text-decoration: none;
            font-size: 13px;
            font-weight: 600;
        }

        .container {
            max-width: 1150px;
            margin: 35px auto;
            padding: 0 20px;
        }

        .page-heading {
            margin-bottom: 25px;
        }

        .page-heading h1 {
            font-size: 27px;
            margin-bottom: 8px;
        }

        .page-heading p {
            color: #777;
            font-size: 13px;
        }

        .panel {
            background: white;
            padding: 25px;
            border-radius: 12px;
            margin-bottom: 30px;
            box-shadow: 0 4px 15px rgba(0,0,0,0.04);
        }

        .panel h2 {
            font-size: 18px;
            margin-bottom: 20px;
        }

        .form-group {
            margin-bottom: 18px;
        }

        label {
            display: block;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 8px;
        }

        input, textarea {
            width: 100%;
            padding: 13px;
            border: 1px solid #ddd;
            border-radius: 7px;
            font-family: inherit;
            font-size: 13px;
        }

        textarea {
            min-height: 90px;
            resize: vertical;
        }

        button {
            border: none;
            cursor: pointer;
            font-family: inherit;
            font-weight: 700;
            border-radius: 6px;
        }

        .upload-btn {
            background: #17864b;
            color: white;
            padding: 13px 22px;
        }

        .upload-btn:hover {
            background: #106b3a;
        }

        .success {
            background: #d9f6e5;
            color: #176c3b;
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .error {
            background: #ffe1e1;
            color: #a32222;
            padding: 13px;
            border-radius: 7px;
            margin-bottom: 20px;
            font-size: 13px;
        }

        .gallery-grid {
            display: grid;
            grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
            gap: 20px;
        }

        .photo-card {
            background: white;
            border: 1px solid #eee;
            border-radius: 10px;
            overflow: hidden;
        }

        .photo-card img {
            width: 100%;
            height: 180px;
            object-fit: cover;
            display: block;
        }

        .photo-info {
            padding: 15px;
        }

        .photo-info h3 {
            font-size: 14px;
            margin-bottom: 7px;
        }

        .photo-info p {
            color: #777;
            font-size: 12px;
            line-height: 1.5;
            margin-bottom: 12px;
        }

        .delete-btn {
            background: #d93636;
            color: white;
            padding: 9px 13px;
            font-size: 11px;
        }

        .empty-message {
            color: #777;
            font-size: 13px;
        }

        @media(max-width: 600px) {
            header {
                padding: 18px 5%;
            }

            .container {
                margin-top: 25px;
            }

            .panel {
                padding: 18px;
            }
        }
    </style>
</head>

<body>

<header>
    <h2>KISUMU LAKESIDE</h2>
    <a href="dashboard.php">← Back to Dashboard</a>
</header>

<main class="container">

    <div class="page-heading">
        <h1>Gallery Management</h1>
        <p>Upload and manage basketball photos displayed on the public website.</p>
    </div>

    <?php if ($success): ?>
        <div class="success">
            <?= escape($success) ?>
        </div>
    <?php endif; ?>

    <?php if ($error): ?>
        <div class="error">
            <?= escape($error) ?>
        </div>
    <?php endif; ?>


    <section class="panel">

        <h2>Upload New Photo</h2>

        <form method="POST" enctype="multipart/form-data">

            <div class="form-group">
                <label for="gallery_image">Select Photo</label>

                <input
                    type="file"
                    name="gallery_image"
                    id="gallery_image"
                    accept=".jpg,.jpeg,.png,.webp"
                    required
                >
            </div>

            <div class="form-group">
                <label for="caption">Photo Caption</label>

                <input
                    type="text"
                    name="caption"
                    id="caption"
                    maxlength="200"
                    placeholder="e.g. Kisumu Lakeside in action"
                >
            </div>

            <div class="form-group">
                <label for="description">Description</label>

                <textarea
                    name="description"
                    id="description"
                    placeholder="Add a short description..."
                ></textarea>
            </div>

            <button
                type="submit"
                name="upload_photo"
                class="upload-btn"
            >
                Upload Photo
            </button>

        </form>

    </section>


    <section class="panel">

        <h2>Uploaded Photos (<?= count($photos) ?>)</h2>

        <?php if (count($photos) === 0): ?>

            <p class="empty-message">
                No photos have been uploaded yet.
            </p>

        <?php else: ?>

            <div class="gallery-grid">

                <?php foreach ($photos as $photo): ?>

                    <article class="photo-card">

                        <img
                            src="../<?= escape($photo["image_path"]) ?>"
                            alt="<?= escape($photo["caption"] ?: "Basketball gallery photo") ?>"
                        >

                        <div class="photo-info">

                            <h3>
                                <?= escape($photo["caption"] ?: "Untitled Photo") ?>
                            </h3>

                            <p>
                                <?= escape($photo["description"] ?: "No description provided.") ?>
                            </p>

                            <form method="POST"
                                  onsubmit="return confirm('Are you sure you want to delete this photo?');">

                                <input
                                    type="hidden"
                                    name="photo_id"
                                    value="<?= (int)$photo["id"] ?>"
                                >

                                <button
                                    type="submit"
                                    name="delete_photo"
                                    class="delete-btn"
                                >
                                    Delete Photo
                                </button>

                            </form>

                        </div>

                    </article>

                <?php endforeach; ?>

            </div>

        <?php endif; ?>

    </section>

</main>

</body>
</html>
