<?php

namespace MedibookBackend;

use PDO;

class UserRegistration
{
    private string $full_name;
    private string $email;
    private string $password;
    private string $role;
    private string $DEFAULT_PROFILE_PICTURE_URL = "https://cdn.pixabay.com/photo/2015/10/05/22/37/blank-profile-picture-973460_1280.png";
    private $connection;

    public function __construct($connection, $full_name, $email, $password, $role)
    {
        $this->connection = $connection;
        $this->full_name = $full_name;
        $this->email = $email;
        $this->password = $password;
        $this->role = $role;
    }

    public function checkIfEmailAlreadyExist(): bool
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

    public function registerUser(): bool
    {
        // Prepare the SQL query
        $query = "INSERT INTO `tbl_users`(`full_name`, `email`, `password`) VALUES (:full_name, :email, :password)";

        // Sanitize input values
        $this->full_name = htmlspecialchars(string: strip_tags($this->full_name));
        $this->email = htmlspecialchars(strip_tags($this->email));
        $this->password = password_hash(htmlspecialchars(strip_tags($this->password)), PASSWORD_BCRYPT);

        // Prepare the statement
        $stmt = $this->connection->prepare($query);

        // Bind parameters to the query
        $stmt->bindParam(':full_name', $this->full_name);
        $stmt->bindParam(':email', $this->email);
        $stmt->bindParam(':password', $this->password);

        // Execute the statement
        if ($stmt->execute()) {
            $this->registerUserRole();
            $this->setDefaultProfilePictureForNewRegisteredUser();
            return true;
        } else {
            return false;
        }
    }

    private function registerUserRole(): void
    {
        $query = "INSERT INTO `tbl_users_roles`(`user_id`, `role`) VALUES (:user_id, :role)";
        $stmt = $this->connection->prepare($query);

        $user_id = htmlspecialchars(string: strip_tags($this->getUserIdByEmail()));
        $this->role = htmlspecialchars(string: strip_tags($this->role));

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':role', $this->role);

        $stmt->execute();
    }

    public function setDefaultProfilePictureForNewRegisteredUser(): void
    {
        $query = "INSERT INTO `tbl_users_information` (`user_id`, `profile_picture_url`) VALUES (:user_id, :profile_picture_url)";
        $stmt = $this->connection->prepare($query);

        $user_id = htmlspecialchars(string: strip_tags($this->getUserIdByEmail()));

        $stmt->bindParam(':user_id', $user_id);
        $stmt->bindParam(':profile_picture_url', $this->DEFAULT_PROFILE_PICTURE_URL);

        $stmt->execute();
    }

    private function getUserIdByEmail(): string
    {
        $query = "SELECT id FROM `tbl_users` WHERE email = :email";

        $stmt = $this->connection->prepare($query);

        // Sanitize the email input
        $this->email = htmlspecialchars(strip_tags($this->email));

        // Bind the email parameter to the prepared statement
        $stmt->bindParam(':email', $this->email);

        // Execute the statement
        $stmt->execute();

        $result = $stmt->fetch(PDO::FETCH_ASSOC);

        return $result['id'];
    }
}
