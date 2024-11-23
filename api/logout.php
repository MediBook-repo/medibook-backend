<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;
use MedibookBackend\RefreshToken;

try {
    if ($_SERVER['REQUEST_METHOD'] != 'DELETE') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Invalid request method, Must be DELETE'
        ]);
        exit;
    }

    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_DBNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $connection = $database->connect();

    $postData = file_get_contents('php://input');
    $postData = json_decode($postData, true);

    if (!isset($postData['refresh_token']) || $postData['refresh_token'] == "") {
        http_response_code(400);
        echo json_encode([
            'message' => 'Missing refresh token'
        ]);
        exit;
    }

    $refresh_token_class = new RefreshToken($connection, 0, $postData['refresh_token'], 0);
    $refresh_token_class->deleteOldRefreshToken();

    http_response_code(201);
    echo json_encode([
        'message' => 'Logout Successfully'
    ]);
    exit;
    
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
