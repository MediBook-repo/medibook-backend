<?php

namespace MedibookBackend;

use PDO;

class UserLogin
{
    private PDO $connection;
    private string $email;
    private string $password;

    public function __construct($connection, $email, $password)
    {
        $this->connection = $connection;
        $this->email = $email;
        $this->password = $password;
    }

    public function checkIfEmailMatchesToAnAccount()
    {
        $query = "SELECT * FROM `tbl_users` WHERE email = :email";
        $stmt = $this->connection->prepare($query);

        // Sanitize the email input
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind the email parameter to the prepared statement
        $stmt->bindParam(':email', $this->email);

        // Execute the statement
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($result) {
            return true; // Email exists
        } else {
            return false; // Email does not exist
        }
    }

    public function checkIfPasswordMatches(): bool
    {
        $query = "SELECT password FROM `tbl_users` WHERE email = :email";
        $stmt = $this->connection->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        if (password_verify($this->password, $result['password'])) {
            return true;
        } else {
            return false;
        }
    }

    public function getUserInformation(): array
    {
        $query = "SELECT tbl_users.id, tbl_users_roles.role FROM `tbl_users` 
                JOIN tbl_users_roles ON tbl_users_roles.user_id = tbl_users.id
                WHERE email = :email";

        $stmt = $this->connection->prepare($query);
        $this->email = htmlspecialchars(strip_tags($this->email));
        $stmt->bindParam(':email', $this->email);

        $stmt->execute();
        $result = $stmt->fetch(PDO::FETCH_ASSOC);
        return $result;
    }
}
