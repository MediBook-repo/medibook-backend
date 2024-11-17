<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;
use MedibookBackend\UserRegistration;

try {
    header('Content-Type: application/json');

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Invalid request method, Must be POST'
        ]);
        exit;
    }

    $postData = file_get_contents('php://input');
    $postData = json_decode($postData, true);

    if (!isset($postData['full_name']) || !isset($postData['email']) || !isset($postData['password']) || !isset($postData['role'])) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Missing required key or value.'
        ]);
        exit;
    }

    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_DBNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $connection = $database->connect();

    $userRegistration = new UserRegistration(
        $connection,
        $postData['full_name'],
        $postData['email'],
        $postData['password'],
        $postData['role']
    );

    if ($userRegistration->checkIfEmailAlreadyExist()) {
        http_response_code(409);
        echo json_encode(['message' => 'Email already exists.']);
        exit;
    }

    if (!$userRegistration->registerUser()) {
        http_response_code(500);
        echo json_encode(['message' => 'User Registration Failed.']);
        exit;
    }

    http_response_code(201);
    echo json_encode([
        "message" => "User successfully registered.",
    ]);
    exit;
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
