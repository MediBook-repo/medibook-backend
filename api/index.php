<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Firebase\JWT\ExpiredException;

try {

    if ($_SERVER['REQUEST_METHOD'] != 'POST') {
        http_response_code(405);
        echo json_encode([
            'error' => 'Invalid request method, Must be POST'
        ]);
        exit;
    }

    $database = new Database('localhost', 'medibook-app', 'root', '');
    $connection = $database->connect();

    $headers = apache_request_headers(); // Get all request headers

    if (isset($headers['Authorization'])) {
        $authorizationHeader = $headers['Authorization'];
        if (preg_match('/Bearer\s(\S+)/', $authorizationHeader, $matches)) {
            $access_token = $matches[1];

            $secretKey = 'WEEZUS GONZALES 123';

            try {
                http_response_code(response_code: 200);
                $decoded = JWT::decode($access_token, new Key($secretKey, 'HS256'));
                echo json_encode($decoded);
                
            } catch (ExpiredException $e) {
                // Handle the expired token case
                http_response_code(401);  // Unauthorized
                echo json_encode([
                    'error' => 'Token has expired.',
                    'message' => $e->getMessage()
                ]);
            } catch (Exception $e) {
                // Handle other exceptions (e.g., invalid token)
                http_response_code(400);  // Bad Request
                echo json_encode([
                    'error' => 'Invalid token.',
                    'message' => $e->getMessage()
                ]);
            }
        } else {
            // If no Authorization header is provided
            http_response_code(400);  // Bad Request
            echo json_encode([
                'error' => 'Authorization header missing or invalid.'
            ]);
        }
    } else {
        // If no headers are set
        http_response_code(400);  // Bad Request
        echo json_encode([
            'error' => 'Authorization header is required.'
        ]);
    }
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
