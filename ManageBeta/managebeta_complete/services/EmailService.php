<?php

// ============================================================
// services/EmailService.php — Email Service
// ============================================================
// This class is responsible for sending emails.
//
// RIGHT NOW it uses PHP's built-in mail() function.
// mail() works on servers that have a mail agent configured
// (like a live hosting server), but NOT on local development
// machines — on localhost, emails are silently dropped.
//
// WHEN YOU'RE READY TO USE REAL EMAIL:
// Replace the mail() call in sendTaskReminder() with PHPMailer
// connected to a Gmail SMTP account. The rest of the code
// (ReminderService, Task model) stays exactly the same.
// ============================================================

class EmailService
{
    // The email address that reminder emails are sent FROM.
    // Change this to a real address when you deploy.
    private $senderEmail = 'no-reply@managebeta.com';
    private $senderName  = 'ManageBeta';

    // Send a reminder email to a user about an upcoming task.
    //
    // $toEmail   = the user's email address
    // $toName    = the user's name
    // $taskTitle = the name of the task
    // $dueDate   = when the task is due (stored format: "2026-05-26 14:30:00")
    //
    // Returns: true if the email was accepted for delivery, false otherwise.
    // Note: true only means PHP handed it to the mail server — not that
    //       the recipient actually received it.
    public function sendTaskReminder($toEmail, $toName, $taskTitle, $dueDate)
    {
        $subject = 'Reminder: "' . $taskTitle . '" is due soon!';

        // Format the date into something human-readable.
        // e.g. "Monday, May 26, 2026 at 2:30 PM"
        $formattedDate = date('l, F j, Y \a\t g:i A', strtotime($dueDate));

        // Build the plain-text email body.
        $body  = "Hello " . $toName . ",\n\n";
        $body .= "This is a reminder that one of your tasks is due in less than 24 hours:\n\n";
        $body .= "  Task:     " . $taskTitle . "\n";
        $body .= "  Due Date: " . $formattedDate . "\n\n";
        $body .= "Log in to ManageBeta to view or update your task.\n\n";
        $body .= "— The ManageBeta Team";

        // Build the email headers.
        // Headers tell the mail server who is sending the email.
        $headers  = "From: " . $this->senderName . " <" . $this->senderEmail . ">\r\n";
        $headers .= "Reply-To: " . $this->senderEmail . "\r\n";
        $headers .= "X-Mailer: PHP/" . phpversion();

        // Send the email using PHP's built-in mail() function.
        $sent = mail($toEmail, $subject, $body, $headers);

        return $sent;
    }

    // -------------------------------------------------------
    // DEVELOPMENT STUB (optional)
    // -------------------------------------------------------
    // If you're testing locally and don't have a mail server,
    // you can swap sendTaskReminder() for this version below.
    // It writes the email to a log file instead of sending it,
    // so you can confirm the reminder system is working.
    //
    // To use it: comment out the real method above and
    // uncomment this one. Put it back before going live.
    // -------------------------------------------------------

    /*
    public function sendTaskReminder($toEmail, $toName, $taskTitle, $dueDate)
    {
        $logFile = __DIR__ . '/../database/email_log.txt';

        $line = date('Y-m-d H:i:s')
            . " | TO: $toEmail ($toName)"
            . " | TASK: $taskTitle"
            . " | DUE: $dueDate"
            . "\n";

        file_put_contents($logFile, $line, FILE_APPEND);

        echo "  [DEV] Email logged for: $toEmail\n";
        return true;
    }
    */
}
