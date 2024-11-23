<?php


require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;
use MedibookBackend\RefreshToken;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;


try {
    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Invalid request method, Must be POST'
        ]);
        exit;
    }

    Dotenv\Dotenv::createImmutable(__DIR__ . '/../')->load();
    $database = new Database($_ENV['DB_HOST'], $_ENV['DB_DBNAME'], $_ENV['DB_USERNAME'], $_ENV['DB_PASSWORD']);
    $connection = $database->connect();

    $postData = file_get_contents('php://input');
    $postData = json_decode($postData, true);

    if (!isset($postData['refresh_token'])) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Missing required key or value.'
        ]);
        exit;
    }

    $refresh_token = new RefreshToken($connection, 0, $postData['refresh_token'], 0);

    if ($refresh_token->checkIfRefreshTokenIsExpired()) {
        http_response_code(401);  // Unauthorized
        echo json_encode([
            'error' => 'Refresh Token has expired. Please login again.',
        ]);
        exit;
    }

    $refresh_token->deleteOldRefreshToken();

    $secretKey = 'WEEZUS GONZALES 123';
    $decoded = JWT::decode($postData['refresh_token'], new Key($secretKey, 'HS256'));

    $decodedArray = (array) $decoded;
    $user_id = $decodedArray['user_id'];
    $role = $decodedArray['role'];
    $expiration_at = $decodedArray['exp'];

    $new_payload_access_token = [
        'iss' => 'medibook', // Issuer
        'aud' => 'medibook-users', // Audience
        'iat' => time(), // Issued at
        'exp' => time() + 50, // Expiration (50 seconds)
        'user_id' =>  $user_id,
        'role' => $role
    ];

    $new_payload_refresh_token = [
        'iss' => 'medibook', // Issuer
        'aud' => 'medibook-users', // Audience
        'iat' => time(), // Issued at
        'exp' => time() + 300, // Expiration (2 minutes)
        'user_id' =>  $user_id,
        'role' => $role
    ];

    $new_access_token = JWT::encode($new_payload_access_token, $secretKey, 'HS256');
    $new_refresh_token = JWT::encode($new_payload_refresh_token, $secretKey, 'HS256');

    $new_refresh_token_class = new RefreshToken($connection, $user_id, $new_refresh_token,  $expiration_at);
    $new_refresh_token_class->createNewRefreshToken();

    echo json_encode([
        'access_token' => $new_access_token,
        'refresh_token' => $new_refresh_token
    ]);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
