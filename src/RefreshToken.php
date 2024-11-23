<?php

namespace MedibookBackend;

use PDO;

class RefreshToken
{
    private PDO $connection;
    private int $user_id;
    private string $refresh_token;
    private int $expiration_at;

    public function __construct($connection, $user_id, $refresh_token, $expiration_at)
    {
        $this->connection = $connection;
        $this->user_id = $user_id;
        $this->refresh_token = $refresh_token;
        $this->expiration_at = $expiration_at;
    }

    public function createNewRefreshToken()
    {
        date_default_timezone_set('Asia/Manila');  // Set the timezone to GMT+8
        $created_at = date('Y-m-d H:i:s');  // Get the current date and time

        $query = "INSERT INTO `tbl_refresh_token_whitelist`(`user_id`, `refresh_token`, `expiration_at`, `created_at`) VALUES (:user_id, :refresh_token, :expiration_at, :created_at)";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':user_id', $this->user_id);
        $stmt->bindParam(':refresh_token', $this->refresh_token);
        $stmt->bindParam(':expiration_at', $this->expiration_at);
        $stmt->bindParam(':created_at', $created_at);

        $stmt->execute();
    }

    public function deleteOldRefreshToken()
    {
        $query = "DELETE FROM `tbl_refresh_token_whitelist` WHERE refresh_token = :refresh_token";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':refresh_token', $this->refresh_token);
        $stmt->execute();
    }

    public function checkIfRefreshTokenIsExpired()
    {
        $query = "SELECT * FROM tbl_refresh_token_whitelist WHERE refresh_token = :refresh_token AND expiration_at > UNIX_TIMESTAMP()";
        $stmt = $this->connection->prepare($query);
        $stmt->bindParam(':refresh_token', $this->refresh_token);
        $stmt->execute();

        // Check if any row is returned
        if ($stmt->rowCount() < 1) {
            return true; // Token is expired or doesn't exist
        } else {
            return false; // Token is valid
        }
    }
}
