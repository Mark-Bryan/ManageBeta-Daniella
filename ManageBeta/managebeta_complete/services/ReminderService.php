<?php

// ============================================================
// services/ReminderService.php — Reminder Service
// ============================================================
// This service checks the database for tasks that are due
// within the next 24 hours and sends reminder emails for them.
//
// It is called by: cron/send_reminders.php
// Run it from the terminal: php cron/send_reminders.php
//
// Or set up a real cron job (runs automatically every hour):
//   crontab -e
//   Add: 0 * * * * php /full/path/to/cron/send_reminders.php
// ============================================================

class ReminderService
{
    // The Task model — to query the database for upcoming tasks.
    private $taskModel;

    // The Email service — to actually send the reminder emails.
    private $emailService;

    public function __construct()
    {
        $this->taskModel    = new Task();
        $this->emailService = new EmailService();
    }

    // Check for tasks due soon and send reminder emails.
    // Prints a summary to the terminal as it runs.
    public function sendPendingReminders()
    {
        echo "Checking for tasks due in the next 24 hours...\n";
        echo str_repeat('-', 50) . "\n";

        // Get all tasks that need a reminder (from Task model).
        $tasks = $this->taskModel->getTasksDueSoon();

        // If no tasks need reminders, we're done.
        if (empty($tasks)) {
            echo "No reminders to send right now.\n";
            return;
        }

        echo "Found " . count($tasks) . " task(s) to remind.\n\n";

        $sentCount   = 0;
        $failedCount = 0;

        // Loop through each task and send a reminder email.
        foreach ($tasks as $task) {
            echo "Sending reminder to: " . $task['user_email'] . "\n";
            echo "  Task: " . $task['title'] . "\n";
            echo "  Due:  " . $task['due_date'] . "\n";

            // Ask the EmailService to send the email.
            $sent = $this->emailService->sendTaskReminder(
                $task['user_email'],  // who to email
                $task['user_name'],   // their name (for "Hello, ...")
                $task['title'],       // the task title
                $task['due_date']     // the deadline
            );

            if ($sent) {
                // Mark the task so we don't email about it again.
                $this->taskModel->markReminderSent($task['id']);
                echo "  ✓ Reminder sent.\n\n";
                $sentCount++;
            } else {
                echo "  ✗ Failed to send.\n\n";
                $failedCount++;
            }
        }

        echo str_repeat('-', 50) . "\n";
        echo "Done. Sent: $sentCount | Failed: $failedCount\n";
    }
}
