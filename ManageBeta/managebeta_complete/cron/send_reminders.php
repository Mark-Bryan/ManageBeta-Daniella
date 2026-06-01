<?php

// ============================================================
// cron/send_reminders.php — Email Reminder Cron Script
// ============================================================
// This script is meant to be run from the command line,
// either manually or automatically on a schedule.
//
// --- Run manually (for testing) ---
//   php cron/send_reminders.php
//
// --- Set up automatic hourly runs (Linux/Mac) ---
//   Open cron editor:    crontab -e
//   Add this line:       0 * * * * php /full/path/to/persis-ca/cron/send_reminders.php
//   That runs it at the top of every hour (e.g. 1:00, 2:00, 3:00...).
//
// --- What it does ---
//   1. Looks for tasks due in the next 24 hours
//   2. That are still pending (not completed)
//   3. That haven't had a reminder sent yet
//   4. Sends an email to each task's owner
//   5. Marks the task as "reminder sent" so it doesn't email again
// ============================================================

// Move to the project root folder so our require_once paths work correctly.
// __DIR__ is the folder this script is in (cron/).
// '/..' goes up one level to the project root.
chdir(__DIR__ . '/..');

// Load all the classes we need (same as index.php does)
require_once 'config/Database.php';
require_once 'models/User.php';
require_once 'models/Task.php';
require_once 'services/EmailService.php';
require_once 'services/ReminderService.php';

// Make sure the database and tables exist
// (Safe to call every time — won't wipe data)
Database::getInstance()->setupTables();

// Run the reminder check and send emails
$reminderService = new ReminderService();
$reminderService->sendPendingReminders();
