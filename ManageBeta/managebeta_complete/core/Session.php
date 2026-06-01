<?php

// ============================================================
// core/Session.php — Session Management
// ============================================================
// PHP sessions let us remember who is logged in across multiple
// page requests. When a user logs in, we save their info into
// the session. On every page load we can check that info.
//
// All methods are "static" — you call them on the class itself:
//   Session::start()
//   Session::isLoggedIn()
// You never need to do: $s = new Session()
// ============================================================

class Session
{
    // Start the PHP session engine.
    // Must be called before any HTML output or header() calls.
    // "if" check prevents errors if session was already started.
    public static function start()
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    // Save the logged-in user's details into the session.
    // After this, every page load will know who the user is.
    public static function setUser($user)
    {
        $_SESSION['user_id']    = $user['id'];
        $_SESSION['user_name']  = $user['name'];
        $_SESSION['user_email'] = $user['email'];
    }

    // Returns true if a user is currently logged in, false if not.
    public static function isLoggedIn()
    {
        return isset($_SESSION['user_id']);
    }

    // Get the logged-in user's ID (used to fetch their tasks).
    public static function getUserId()
    {
        return $_SESSION['user_id'] ?? null;
    }

    // Get the logged-in user's full name (shown on the dashboard).
    public static function getUserName()
    {
        return $_SESSION['user_name'] ?? null;
    }

    // Get the logged-in user's email address.
    public static function getUserEmail()
    {
        return $_SESSION['user_email'] ?? null;
    }

    // Destroy the session completely (used on logout).
    // This clears all session data and ends the session.
    public static function destroy()
    {
        $_SESSION = [];
        session_destroy();
    }

    // Protect a page — if the user is NOT logged in, send them
    // back to the login page immediately. Used on page routes.
    public static function requireAuth()
    {
        if (!self::isLoggedIn()) {
            header('Location: /');
            exit;
        }
    }
}
