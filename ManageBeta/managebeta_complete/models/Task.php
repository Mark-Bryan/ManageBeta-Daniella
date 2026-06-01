<?php

// ============================================================
// models/Task.php — Task Model
// ============================================================
// This class handles all database operations for tasks:
// creating, reading, updating, deleting, and toggling status.
//
// Every method includes "user_id = :user_id" in its WHERE clause
// so a user can ONLY ever see or modify their own tasks.
// ============================================================

class Task
{
    // The database connection.
    private $db;

    public function __construct()
    {
        $this->db = Database::getInstance()->getConnection();
    }

    // Get every task that belongs to a specific user.
    // Tasks are sorted by due date — nearest deadline first.
    //
    // Returns: an array of task rows (may be empty if user has no tasks).
    public function getAllByUser($userId)
    {
        $sql  = "SELECT * FROM tasks WHERE user_id = :user_id ORDER BY due_date ASC";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':user_id' => $userId]);

        // fetchAll() returns every matching row as an array of arrays.
        return $stmt->fetchAll();
    }

    // Create a new task.
    //
    // $dueDate format coming in from JS: "2026-05-26T14:30"
    // We convert it to "2026-05-26 14:30:00" for SQLite.
    //
    // Returns: the new task's integer ID.
    public function create($userId, $title, $description, $dueDate, $priority)
    {
        // Convert "2026-05-26T14:30" → "2026-05-26 14:30:00"
        // str_replace swaps the T for a space, then we append :00 for seconds.
        $dueDate = str_replace('T', ' ', $dueDate);
        if (strlen($dueDate) === 16) {
            $dueDate .= ':00'; // add seconds if missing
        }

        $now = date('Y-m-d H:i:s');

        $sql = "
            INSERT INTO tasks
                (user_id, title, description, due_date, priority, status, reminder_sent, created_at, updated_at)
            VALUES
                (:user_id, :title, :description, :due_date, :priority, 'pending', 0, :created_at, :updated_at)
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':user_id'     => $userId,
            ':title'       => $title,
            ':description' => $description,
            ':due_date'    => $dueDate,
            ':priority'    => $priority,
            ':created_at'  => $now,
            ':updated_at'  => $now,
        ]);

        return $this->db->lastInsertId();
    }

    // Find a single task by its ID.
    //
    // Returns: the task row as an array, or null if not found.
    public function findById($id)
    {
        $sql  = "SELECT * FROM tasks WHERE id = :id LIMIT 1";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);

        $task = $stmt->fetch();
        return $task ?: null;
    }

    // Update an existing task's details.
    // "AND user_id = :user_id" ensures users can only edit their own tasks.
    //
    // Returns: true if the update succeeded, false if the task wasn't found
    //          or belongs to a different user.
    public function update($id, $userId, $title, $description, $dueDate, $priority)
    {
        // Same date conversion as in create().
        $dueDate = str_replace('T', ' ', $dueDate);
        if (strlen($dueDate) === 16) {
            $dueDate .= ':00';
        }

        $sql = "
            UPDATE tasks
            SET title       = :title,
                description = :description,
                due_date    = :due_date,
                priority    = :priority,
                updated_at  = :updated_at
            WHERE id      = :id
              AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':id'          => $id,
            ':user_id'     => $userId,
            ':title'       => $title,
            ':description' => $description,
            ':due_date'    => $dueDate,
            ':priority'    => $priority,
            ':updated_at'  => date('Y-m-d H:i:s'),
        ]);

        // rowCount() tells us how many rows were changed.
        // If it's 0, either the task doesn't exist or the user doesn't own it.
        return $stmt->rowCount() > 0;
    }

    // Toggle a task's status between 'pending' and 'completed'.
    // If it's pending → mark it completed. If completed → reopen it.
    //
    // Returns: the new status string ('pending' or 'completed'),
    //          or false if the task wasn't found / user doesn't own it.
    public function toggleStatus($id, $userId)
    {
        // First, fetch the task to see its current status.
        $task = $this->findById($id);

        // Make sure the task exists and belongs to this user.
        if (!$task || $task['user_id'] != $userId) {
            return false;
        }

        // Flip the status.
        $newStatus = ($task['status'] === 'completed') ? 'pending' : 'completed';

        $sql = "
            UPDATE tasks
            SET status     = :status,
                updated_at = :updated_at
            WHERE id      = :id
              AND user_id = :user_id
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([
            ':status'     => $newStatus,
            ':updated_at' => date('Y-m-d H:i:s'),
            ':id'         => $id,
            ':user_id'    => $userId,
        ]);

        // Return the new status so the caller knows what changed.
        return $newStatus;
    }

    // Delete a task permanently.
    // "AND user_id = :user_id" ensures users can only delete their own tasks.
    //
    // Returns: true if deleted, false if not found / wrong user.
    public function delete($id, $userId)
    {
        $sql  = "DELETE FROM tasks WHERE id = :id AND user_id = :user_id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id, ':user_id' => $userId]);

        return $stmt->rowCount() > 0;
    }

    // Get all tasks that are due within the next 24 hours,
    // are still pending, and haven't had a reminder sent yet.
    // This is called by the cron job to find who needs to be emailed.
    //
    // We JOIN with the users table so we also get the user's name and email.
    public function getTasksDueSoon()
    {
        $now       = date('Y-m-d H:i:s');
        $in24Hours = date('Y-m-d H:i:s', strtotime('+24 hours'));

        $sql = "
            SELECT tasks.*, users.name AS user_name, users.email AS user_email
            FROM tasks
            JOIN users ON tasks.user_id = users.id
            WHERE tasks.due_date    >  :now
              AND tasks.due_date    <= :in24hours
              AND tasks.status      =  'pending'
              AND tasks.reminder_sent = 0
        ";

        $stmt = $this->db->prepare($sql);
        $stmt->execute([':now' => $now, ':in24hours' => $in24Hours]);

        return $stmt->fetchAll();
    }

    // Mark a task as "reminder sent" so the cron job doesn't email again.
    public function markReminderSent($id)
    {
        $sql  = "UPDATE tasks SET reminder_sent = 1 WHERE id = :id";
        $stmt = $this->db->prepare($sql);
        $stmt->execute([':id' => $id]);
    }
}
