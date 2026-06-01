<?php

// ============================================================
// database/seed.php — Database Seeder
// ============================================================
// Inserts one test user and four sample tasks into the database.
// Run this once from the terminal to populate the app with data.
//
// Usage:
//   php database/seed.php
//
// Test account created:
//   Email:    admin@managebeta.com
//   Password: password123
// ============================================================

// Go up one level so require paths work from the project root
chdir(__DIR__ . '/..');

require_once 'config/Database.php';

// Make sure the tables exist before we try to insert anything
Database::getInstance()->setupTables();

$db = Database::getInstance()->getConnection();

echo "============================================================\n";
echo " ManageBeta — Database Seeder\n";
echo "============================================================\n\n";


// -------------------------------------------------------
// Step 1: Wipe existing seed data (clean slate)
// -------------------------------------------------------
// We delete by email so running this twice doesn't crash.
// Deleting the user also cascades to delete their tasks
// because of the FOREIGN KEY ... ON DELETE CASCADE constraint.
$db->prepare("DELETE FROM users WHERE email = :email")
   ->execute([':email' => 'admin@managebeta.com']);

echo "Old seed data cleared.\n\n";


// -------------------------------------------------------
// Step 2: Create the test user
// -------------------------------------------------------
$name      = 'Persis Admin';
$email     = 'admin@managebeta.com';
$password  = password_hash('password123', PASSWORD_DEFAULT); // Always hash passwords
$createdAt = date('Y-m-d H:i:s');

$stmt = $db->prepare("
    INSERT INTO users (name, email, password, created_at)
    VALUES (:name, :email, :password, :created_at)
");

$stmt->execute([
    ':name'       => $name,
    ':email'      => $email,
    ':password'   => $password,
    ':created_at' => $createdAt,
]);

$userId = $db->lastInsertId();

echo "✓ User created\n";
echo "    Name:     $name\n";
echo "    Email:    $email\n";
echo "    Password: password123\n";
echo "    ID:       $userId\n\n";


// -------------------------------------------------------
// Step 3: Create 4 sample tasks
// -------------------------------------------------------
$now = time(); // Current timestamp in seconds — we'll build due dates from this

$tasks = [
    [
        'title'       => 'Set up project documentation',
        'description' => 'Write the README and document all API endpoints clearly.',
        'due_date'    => date('Y-m-d H:i:s', $now + (2 * 3600)),   // Due in 2 hours
        'priority'    => 'high',
        'status'      => 'pending',
    ],
    [
        'title'       => 'Design the database schema',
        'description' => 'Decide on the tables, columns, and relationships for the app.',
        'due_date'    => date('Y-m-d H:i:s', $now + (26 * 3600)),  // Due in 26 hours
        'priority'    => 'medium',
        'status'      => 'pending',
    ],
    [
        'title'       => 'Build the login and register pages',
        'description' => 'Create the auth forms and connect them to the PHP backend.',
        'due_date'    => date('Y-m-d H:i:s', $now - (3 * 3600)),   // Was due 3 hours ago (overdue)
        'priority'    => 'high',
        'status'      => 'pending',
    ],
    [
        'title'       => 'Write unit tests for the Task model',
        'description' => 'Test create, update, delete, and toggle methods.',
        'due_date'    => date('Y-m-d H:i:s', $now - (2 * 86400)),  // Was due 2 days ago
        'priority'    => 'low',
        'status'      => 'completed',
    ],
];

$stmt = $db->prepare("
    INSERT INTO tasks
        (user_id, title, description, due_date, priority, status, reminder_sent, created_at, updated_at)
    VALUES
        (:user_id, :title, :description, :due_date, :priority, :status, 0, :created_at, :updated_at)
");

foreach ($tasks as $index => $task) {
    $stmt->execute([
        ':user_id'     => $userId,
        ':title'       => $task['title'],
        ':description' => $task['description'],
        ':due_date'    => $task['due_date'],
        ':priority'    => $task['priority'],
        ':status'      => $task['status'],
        ':created_at'  => $createdAt,
        ':updated_at'  => $createdAt,
    ]);

    $taskNumber = $index + 1;
    echo "✓ Task $taskNumber created\n";
    echo "    Title:    {$task['title']}\n";
    echo "    Priority: {$task['priority']}\n";
    echo "    Status:   {$task['status']}\n";
    echo "    Due:      {$task['due_date']}\n\n";
}


// -------------------------------------------------------
// Done
// -------------------------------------------------------
echo "============================================================\n";
echo " Seeding complete! 1 user, 4 tasks inserted.\n";
echo "\n";
echo " Log in at: http://localhost:8000\n";
echo "   Email:    admin@managebeta.com\n";
echo "   Password: password123\n";
echo "============================================================\n";
