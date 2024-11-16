<?php

require_once __DIR__ . '/../vendor/autoload.php';

use MedibookBackend\Database;

try {
    $database = new Database('localhost', 'medibook-app', 'root', '');
    $connection = $database->connect();
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
