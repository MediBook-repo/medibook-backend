<?php


require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;
use MedibookBackend\UserLogin;
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

    if (!isset($postData['email']) || !isset($postData['password'])) {
        http_response_code(400);
        echo json_encode([
            'message' => 'Missing required key or value.'
        ]);
        exit;
    }

    $userLogin = new UserLogin($connection, $postData['email'], $postData['password']);

    if (!$userLogin->checkIfEmailMatchesToAnAccount()) {
        http_response_code(409);
        echo json_encode(['message' => 'Email does not exist']);
        exit;
    }

    if (!$userLogin->checkIfPasswordMatches()) {
        http_response_code(409);
        echo json_encode(['message' => 'Password does not match.']);
        exit;
    }

    $user_id = $userLogin->getUserInformation()['id'];
    $role = $userLogin->getUserInformation()['role'];

    $secretKey = 'WEEZUS GONZALES 123';

    $payload_access_token = [
        'iss' => 'medibook', // Issuer
        'aud' => 'medibook-users', // Audience
        'iat' => time(), // Issued at
        'exp' => time() + 50, // Expiration (50 seconds)
        'user_id' =>  $user_id,
        'role' => $role
    ];

    $payload_refresh_token = [
        'iss' => 'medibook', // Issuer
        'aud' => 'medibook-users', // Audience
        'iat' => time(), // Issued at
        'exp' => time() + 300, // Expiration (2 minutes)
        'user_id' =>  $user_id,
        'role' => $role
    ];

    $refresh_token_user_id = $payload_refresh_token['user_id'];
    $refresh_token_exp = $payload_refresh_token['exp'];

    $access_token = JWT::encode($payload_access_token, $secretKey, 'HS256');
    $refresh_token = JWT::encode($payload_refresh_token, $secretKey, alg: 'HS256');

    $refreshTokenClass = new RefreshToken($connection, $refresh_token_user_id, $refresh_token, $refresh_token_exp);
    $refreshTokenClass->createNewRefreshToken();

    http_response_code(200);

    echo json_encode([
        'access_token' => $access_token,
        'refresh_token' => $refresh_token
    ]);
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
