<?php

// ============================================================
// index.php — Front Controller
// ============================================================
// Every single request to this application passes through here.
// This file loads all the classes, defines all the routes,
// then hands off to the Router to find the right controller.
// ============================================================

// -----------------------------------------------
// Static file handler (PHP built-in server only)
// -----------------------------------------------
// When running with: php -S localhost:8000 index.php
// the built-in server sends EVERY request here —
// including requests for CSS, JS, images, and audio files.
//
// This block checks if the request is for a real file on disk.
// If it is, returning false tells PHP to serve it directly,
// bypassing all our routing logic below.
//
// Without this, the browser gets a 404 for style.css, dashboard.js, etc.
// -----------------------------------------------
if (php_sapi_name() === 'cli-server') {
    $requestedFile = __DIR__ . parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    if (is_file($requestedFile)) {
        return false; // Serve the static file as-is and stop here
    }
}

// --- Load all class files ---
require_once 'config/Database.php';
require_once 'core/Session.php';
require_once 'core/Controller.php';
require_once 'core/Router.php';
require_once 'models/User.php';
require_once 'models/Task.php';
require_once 'controllers/AuthController.php';
require_once 'controllers/TaskController.php';
require_once 'services/EmailService.php';
require_once 'services/ReminderService.php';

// --- Start the session so we can track who is logged in ---
Session::start();

// --- Set up the database (creates tables if they don't exist yet) ---
Database::getInstance()->setupTables();

// --- Create the router ---
$router = new Router();

// -----------------------------------------------
// Page routes — these render full HTML pages
// -----------------------------------------------
$router->get('/',           'AuthController', 'showAuthPage');    // Login/Register page
$router->get('/dashboard',  'TaskController', 'showDashboard');   // Dashboard page

// -----------------------------------------------
// Auth API routes — called by JavaScript fetch()
// -----------------------------------------------
$router->post('/api/auth/register', 'AuthController', 'register'); // Create account
$router->post('/api/auth/login',    'AuthController', 'login');    // Sign in
$router->get('/api/auth/logout',    'AuthController', 'logout');   // Sign out

// -----------------------------------------------
// Task API routes — called by JavaScript fetch()
// -----------------------------------------------
$router->get('/api/tasks',              'TaskController', 'list');   // Get all tasks
$router->post('/api/tasks',             'TaskController', 'create'); // Create a task
$router->put('/api/tasks/{id}',         'TaskController', 'update'); // Update a task
$router->delete('/api/tasks/{id}',      'TaskController', 'delete'); // Delete a task
$router->post('/api/tasks/{id}/toggle', 'TaskController', 'toggle'); // Toggle complete/pending

// --- Let the router read the current URL and call the right controller ---
$router->dispatch();
