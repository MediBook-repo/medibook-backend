<?php


require_once __DIR__ . '/vendor/autoload.php';

use Asus\MedibookBackend\Database;

try {
    $db = new Database('localhost', 'medibook-app', 'root', '');
    $connection = $db->connect();
} catch (PDOException $e) {
    echo "Connection failed: " . $e->getMessage();
}
