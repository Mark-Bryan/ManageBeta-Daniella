<?php

// ============================================================
// config/Database.php — Database Connection
// ============================================================
// This class manages the connection to our SQLite database.
//
// It uses the "Singleton" pattern, which means only ONE
// database connection is ever created per request. Instead of
// creating a new connection every time you need the database,
// you call Database::getInstance() and always get the same one.
// ============================================================

class Database
{
    // Holds the one and only instance of this class.
    // "static" means it belongs to the class, not to any object.
    private static $instance = null;

    // The PDO object that lets us run SQL queries.
    private $connection;

    // Where the SQLite database file is stored on disk.
    // __DIR__ means "the folder this file is in" (config/).
    // We go up one level with .. to reach the project root.
    private $databasePath = __DIR__ . '/../database/tasks.db';

    // The constructor is private so nobody can do: new Database()
    // The only way to get a Database is through getInstance() below.
    private function __construct()
    {
        try {
            // PDO (PHP Data Objects) is PHP's built-in database tool.
            // "sqlite:" tells it we want an SQLite file database.
            $this->connection = new PDO('sqlite:' . $this->databasePath);

            // If something goes wrong, throw an exception instead of silently failing.
            $this->connection->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);

            // Return rows as associative arrays: ['id' => 1, 'name' => 'Alice']
            // instead of indexed arrays: [0 => 1, 1 => 'Alice']
            $this->connection->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);

            // SQLite needs this turned on to respect FOREIGN KEY constraints.
            $this->connection->exec('PRAGMA foreign_keys = ON');

        } catch (PDOException $e) {
            // Stop everything and show the error if the connection fails.
            die('Database connection failed: ' . $e->getMessage());
        }
    }

    // This is the only way to get a Database object.
    // First call: creates the connection and saves it in $instance.
    // Every call after: just returns the same $instance again.
    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new Database();
        }

        return self::$instance;
    }

    // Returns the raw PDO connection so models can run SQL queries.
    public function getConnection()
    {
        return $this->connection;
    }

    // Creates the database tables if they don't exist yet.
    // "IF NOT EXISTS" means it's safe to call this on every request —
    // it won't wipe your data if the tables are already there.
    public function setupTables()
    {
        // --- Users table ---
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS users (
                id         INTEGER PRIMARY KEY AUTOINCREMENT,
                name       TEXT    NOT NULL,
                email      TEXT    UNIQUE NOT NULL,
                password   TEXT    NOT NULL,
                created_at TEXT    NOT NULL
            )
        ");

        // --- Tasks table ---
        $this->connection->exec("
            CREATE TABLE IF NOT EXISTS tasks (
                id            INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id       INTEGER NOT NULL,
                title         TEXT    NOT NULL,
                description   TEXT    DEFAULT '',
                due_date      TEXT    NOT NULL,
                priority      TEXT    DEFAULT 'medium',
                status        TEXT    DEFAULT 'pending',
                reminder_sent INTEGER DEFAULT 0,
                created_at    TEXT    NOT NULL,
                updated_at    TEXT    NOT NULL,
                FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
            )
        ");
    }
}
