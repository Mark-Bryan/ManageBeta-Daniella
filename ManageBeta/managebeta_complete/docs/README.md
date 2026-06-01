# ManageBeta — Task Management System

A simple, OOP-based task management web application built with plain PHP, SQLite, HTML, CSS, and JavaScript. No frameworks used.

---

## Table of Contents

1. [Project Overview](#project-overview)
2. [Tech Stack](#tech-stack)
3. [Directory Structure](#directory-structure)
4. [Database Schema](#database-schema)
5. [How the Code Is Organised (OOP)](#how-the-code-is-organised-oop)
6. [API Routes](#api-routes)
7. [How to Run Locally](#how-to-run-locally)
8. [Email Reminder System](#email-reminder-system)
9. [Countdown & Alarm System](#countdown--alarm-system)
10. [How a Request Flows Through the App](#how-a-request-flows-through-the-app)

---

## Project Overview

ManageBeta lets registered users:
- Create, view, edit, and delete personal tasks
- Set a deadline and priority level (Low / Medium / High) for each task
- See a live countdown timer on every task
- Get an alarm notification the moment a deadline is reached
- Receive email reminders 24 hours before a task is due

---

## Tech Stack

| Layer      | Technology                          |
|------------|-------------------------------------|
| Backend    | PHP 8+ (OOP, no framework)          |
| Database   | SQLite (via PHP's PDO extension)     |
| Frontend   | HTML, CSS, Vanilla JavaScript        |
| Routing    | Custom front-controller (index.php) |
| Sessions   | PHP native sessions                 |
| Email      | PHP `mail()` / PHPMailer (optional) |

---

## Directory Structure

```
persis-ca/
│
├── index.php                  ← Entry point. ALL requests come here first.
├── .htaccess                  ← Tells Apache to route all requests to index.php
│
├── config/
│   └── Database.php           ← SQLite connection (Singleton pattern)
│
├── core/
│   ├── Router.php             ← Matches the URL to the right controller action
│   ├── Controller.php         ← Base class with render(), json(), redirect() helpers
│   └── Session.php            ← Login session management
│
├── models/
│   ├── User.php               ← All database queries for the users table
│   └── Task.php               ← All database queries for the tasks table
│
├── controllers/
│   ├── AuthController.php     ← showAuthPage, register, login, logout
│   └── TaskController.php     ← showDashboard, list, create, update, delete, toggle
│
├── services/
│   ├── EmailService.php       ← Sends reminder emails (wraps PHP mail())
│   └── ReminderService.php    ← Finds tasks due soon and triggers emails
│
├── views/
│   ├── auth.php               ← Login + Register page (PHP renders HTML)
│   └── dashboard.php          ← Dashboard page (PHP renders HTML)
│
├── public/
│   ├── css/style.css          ← All styles
│   └── js/
│       ├── auth.js            ← Login/register form logic (uses fetch())
│       └── dashboard.js       ← Dashboard logic: tasks, countdown, alarm
│
├── database/
│   └── tasks.db               ← SQLite database file (auto-created on first run)
│
├── cron/
│   └── send_reminders.php     ← Run by cron job to send reminder emails
│
└── docs/
    └── README.md              ← This file
```

---

## Database Schema

### `users` table

| Column      | Type    | Description                       |
|-------------|---------|-----------------------------------|
| id          | INTEGER | Primary key, auto-incremented     |
| name        | TEXT    | User's full name                  |
| email       | TEXT    | Unique — used to log in           |
| password    | TEXT    | Bcrypt-hashed (never plain text)  |
| created_at  | TEXT    | Date/time the account was created |

### `tasks` table

| Column        | Type    | Description                                        |
|---------------|---------|----------------------------------------------------|
| id            | INTEGER | Primary key, auto-incremented                      |
| user_id       | INTEGER | Foreign key → users.id (whose task this is)        |
| title         | TEXT    | Task name                                          |
| description   | TEXT    | Optional detail text                               |
| due_date      | TEXT    | Deadline: stored as "YYYY-MM-DD HH:MM:SS"          |
| priority      | TEXT    | "low", "medium", or "high"                         |
| status        | TEXT    | "pending" or "completed"                           |
| reminder_sent | INTEGER | 0 = not sent yet, 1 = reminder already emailed     |
| created_at    | TEXT    | When the task was created                          |
| updated_at    | TEXT    | When the task was last modified                    |

---

## How the Code Is Organised (OOP)

The application uses four main layers:

### 1. `config/Database.php` — Singleton
Only ever creates ONE database connection for the whole request.
Every model calls `Database::getInstance()->getConnection()` to get it.

### 2. `core/` — Infrastructure
- **Router** — reads the URL, finds the matching route, calls the controller method.
- **Controller** — base class all controllers extend. Has shared helper methods.
- **Session** — static class that wraps `$_SESSION` for login tracking.

### 3. `models/` — Data Layer
Each model class talks to one table. Controllers never write SQL — they call model methods.
- `User::create()`, `User::findByEmail()`, `User::verifyPassword()`
- `Task::getAllByUser()`, `Task::create()`, `Task::update()`, `Task::delete()`, `Task::toggleStatus()`

### 4. `controllers/` — Business Logic
Controllers receive requests, call models, and send back responses.
- Page routes call `$this->render('viewname', $data)` → outputs HTML
- API routes call `$this->json($data)` → outputs JSON for JavaScript

---

## API Routes

| Method | URL                        | What it does                          | Auth required |
|--------|----------------------------|---------------------------------------|---------------|
| GET    | `/`                        | Show login/register page              | No            |
| GET    | `/dashboard`               | Show the dashboard                    | Yes           |
| POST   | `/api/auth/register`       | Create account, start session         | No            |
| POST   | `/api/auth/login`          | Verify credentials, start session     | No            |
| GET    | `/api/auth/logout`         | Destroy session, redirect to login    | No            |
| GET    | `/api/tasks`               | Get all tasks for logged-in user      | Yes           |
| POST   | `/api/tasks`               | Create a new task                     | Yes           |
| PUT    | `/api/tasks/{id}`          | Update an existing task               | Yes           |
| DELETE | `/api/tasks/{id}`          | Delete a task                         | Yes           |
| POST   | `/api/tasks/{id}/toggle`   | Toggle task between pending/completed | Yes           |

All API routes return JSON in this shape:
```json
{ "success": true, "task": { ... } }
{ "success": false, "message": "Error description" }
```

---

## How to Run Locally

### Requirements
- PHP 8.0 or higher
- The `pdo_sqlite` PHP extension (usually enabled by default)
- Apache with `mod_rewrite` enabled (for `.htaccess`), **or** use PHP's built-in server

### Option A — PHP's Built-in Server (easiest, no Apache needed)

```bash
cd /path/to/persis-ca
php -S localhost:8000 index.php
```

Then open `http://localhost:8000` in your browser.

> The built-in server routes all requests through `index.php` automatically,
> so `.htaccess` is not needed for local development.

### Option B — Apache

1. Put the project in your Apache web root (e.g. `/var/www/html/persis-ca`)
2. Make sure `mod_rewrite` is enabled: `sudo a2enmod rewrite`
3. Make sure `AllowOverride All` is set for your directory in Apache config
4. Open `http://localhost/persis-ca` in your browser

The `database/tasks.db` file is created automatically on the first request.

---

## Email Reminder System

### How it works
1. The `cron/send_reminders.php` script is run periodically (manually or via cron)
2. `ReminderService` queries tasks due within the next 24 hours where `reminder_sent = 0`
3. `EmailService` sends a reminder email to the task owner
4. The task's `reminder_sent` flag is set to `1` so it never emails twice

### Running it manually (for testing)
```bash
php cron/send_reminders.php
```

### Setting up automatic runs (hourly)
```bash
crontab -e
# Add this line:
0 * * * * php /full/path/to/persis-ca/cron/send_reminders.php
```

### Email setup
**Current state:** Uses PHP's built-in `mail()` function.
- Works on configured servers (live hosting, VPS with Postfix)
- Will NOT work on most local development machines

**For local testing:** Open `services/EmailService.php` and switch to the
"DEV STUB" version of `sendTaskReminder()` — it logs emails to
`database/email_log.txt` instead of actually sending them.

**For production with real delivery:** Replace `mail()` with PHPMailer
connected to a Gmail SMTP account (one-time setup in `EmailService.php`).

---

## Countdown & Alarm System

All countdown logic lives in `public/js/dashboard.js`.

### How it works
1. When the dashboard loads, JavaScript fetches all tasks from `GET /api/tasks`
2. Each task card gets a `data-due` attribute containing the deadline
3. `setInterval` runs every 1 second and recalculates the remaining time for each card
4. The countdown text is updated in-place (no page reload needed)

### Alarm trigger
When the countdown reaches zero (diff ≤ 0):
- The card's countdown badge turns red and shows "Overdue by X"
- A toast notification pops up in the top-right corner
- The `alarm.mp3` file plays (if the browser allows audio autoplay)
- An `alertedIds` object prevents the alarm from firing more than once per task per session

### Stats update
The stat numbers (Total / Active / Overdue / Completed) are recalculated
any time a task transitions from Active → Overdue.

---

## How a Request Flows Through the App

Here is a step-by-step example of what happens when a user creates a task:

```
1. User fills out the "Add Task" form and clicks "Save Task"

2. dashboard.js calls:
   fetch("POST /api/tasks", { body: { title, due_date, priority, ... } })

3. index.php receives the request
   → Router matches "POST /api/tasks" → TaskController::create()

4. TaskController::create()
   → requireApiAuth()   — checks Session, returns 401 if not logged in
   → getJsonBody()      — reads the JSON from the request body
   → validates fields
   → calls Task::create()

5. Task::create()
   → runs INSERT INTO tasks (...) VALUES (...)
   → returns the new task's ID

6. TaskController::create()
   → calls Task::findById() to get the full saved row
   → calls $this->json(['success' => true, 'task' => $task], 201)

7. PHP sends back: { "success": true, "task": { "id": 5, "title": "...", ... } }

8. dashboard.js receives the response
   → pushes the new task into the allTasks array
   → calls renderTasks() to redraw the grid
   → shows a "Task Created" toast notification
```
