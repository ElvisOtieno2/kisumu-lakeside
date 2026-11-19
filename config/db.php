
<?php

// Load local configuration if it exists.
$localConfigFile = __DIR__ . '/db.local.php';

$localConfig = [];

if (file_exists($localConfigFile)) {
    $localConfig = require $localConfigFile;
}

// Environment variables take priority on hosted servers.
$host = getenv("DB_HOST") ?: ($localConfig["host"] ?? "");
$port = getenv("DB_PORT") ?: ($localConfig["port"] ?? "5432");
$dbname = getenv("DB_NAME") ?: ($localConfig["dbname"] ?? "");
$username = getenv("DB_USER") ?: ($localConfig["username"] ?? "");
$password = getenv("DB_PASSWORD") ?: ($localConfig["password"] ?? "");

try {

    $pdo = new PDO(
        "pgsql:host=$host;port=$port;dbname=$dbname",
        $username,
        $password
    );

    $pdo->setAttribute(
        PDO::ATTR_ERRMODE,
        PDO::ERRMODE_EXCEPTION
    );

} catch (PDOException $e) {

    error_log("Database connection failed: " . $e->getMessage());

    die("Unable to connect to the database.");
}
