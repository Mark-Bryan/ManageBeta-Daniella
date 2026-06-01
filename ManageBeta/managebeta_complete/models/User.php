<?php

// ============================================================
// models/User.php — User Model
// ============================================================
// This class handles everything related to users in the database:
// creating accounts, finding users, and checking passwords.
//
// A "model" is responsible for all database interactions.
// Controllers call these methods — they never write SQL themselves.
// ============================================================

class User
{
    // The database connection (a PDO object).
    private $db;

    public function __construct()
    {
        // Get the shared database connection from our Database singleton.
        $this->db = Database::getInstance()->getConnection();
    }

    // Create a new user account in the database.
    //
    // Returns: the new user's integer ID if successful,
    //          false if the email address is already taken.
    public function create($name, $email, $password)
    {
        // NEVER store plain-text passwords.
        // password_hash() scrambles the password using bcrypt,
        // so even if the database is stolen, passwords are safe.
        $hashedPassword = password_hash($password, PASSWORD_DEFAULT);

        $createdAt = date('Y-m-d H:i:s');

        $sql = "
            INSERT INTO users (name, email, password, created_at)
            VALUES (:name, :email, :password, :created_at)
        ";

        // prepare() creates a "prepared statement" — a safe way to run SQL.
        // We use :placeholders instead of putting values directly in the SQL
        // to prevent SQL injection attacks.
        $stmt = $this->db->prepare($sql);

        try {
            $stmt->execute([
                ':name'       => $name,
                ':email'      => $email,
                ':password'   => $hashedPassword,
                ':created_at' => $createdAt,
            ]);

            // lastInsertId() returns the ID that SQLite assigned to the new row.
            return $this->db->lastInsertId();

        } catch (PDOException $e) {
            // If the email already exists, SQLite throws an error because
            // the email column has a UNIQUE constraint. We return false.
            return false;
        }
    }

    // Find a user by their email address.
    //
    // Returns: the user row as an array ['id' => 1, 'name' => ..., ...]
    //          or null if no user with that email exists.
    public function findByEmail($email)
    {
        $sql  = "SELECT * FROM users WHERE email = :email LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':email' => $email]);

        // fetch() returns one row as an array, or false if nothing was found.
        $user = $stmt->fetch();

        // Return null instead of false for consistency.
        return $user ?: null;
    }

    // Find a user by their numeric ID.
    //
    // Returns: the user row as an array, or null if not found.
    public function findById($id)
    {
        $sql  = "SELECT * FROM users WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $user = $stmt->fetch();
        return $user ?: null;
    }

    // Check if a plain-text password matches a stored hashed password.
    //
    // password_verify() re-hashes the plain password and compares it
    // to the stored hash. Returns true if they match, false if not.
    public function verifyPassword($plainPassword, $hashedPassword)
    {
        return password_verify($plainPassword, $hashedPassword);
    }
}
