<?php

// ============================================================
// controllers/AuthController.php — Authentication Controller
// ============================================================
// Handles: show login/register page, register, login, logout.
//
// Page routes return HTML.
// API routes (/api/auth/...) return JSON for JavaScript fetch().
// ============================================================

class AuthController extends Controller
{
    // The User model — used to talk to the users table.
    private $userModel;

    public function __construct()
    {
        $this->userModel = new User();
    }

    // -------------------------------------------------------
    // GET /
    // Show the login/register page.
    // -------------------------------------------------------
    public function showAuthPage()
    {
        // If someone is already logged in, skip the login page
        // and send them straight to their dashboard.
        if (Session::isLoggedIn()) {
            $this->redirect('/dashboard');
        }

        // Render the auth view (views/auth.php).
        $this->render('auth');
    }

    // -------------------------------------------------------
    // POST /api/auth/register
    // Handle account creation — called by JavaScript fetch().
    // Expects JSON body: { name, email, password, confirm }
    // Returns JSON: { success, redirect } or { success, field, message }
    // -------------------------------------------------------
    public function register()
    {
        // Read the JSON data that JavaScript sent in the request body.
        $data = $this->getJsonBody();

        // Pull out each field, with a safe default of '' if missing.
        $name     = trim($data['name']     ?? '');
        $email    = strtolower(trim($data['email'] ?? ''));
        $password = $data['password']      ?? '';
        $confirm  = $data['confirm']       ?? '';

        // --- Validate each field ---
        // We check every field and return early with an error if something is wrong.
        // 'field' tells JavaScript which input to highlight with the error message.

        if (strlen($name) < 2) {
            $this->json(['success' => false, 'field' => 'name', 'message' => 'Name must be at least 2 characters.'], 400);
        }

        if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $this->json(['success' => false, 'field' => 'email', 'message' => 'Please enter a valid email address.'], 400);
        }

        if (strlen($password) < 6) {
            $this->json(['success' => false, 'field' => 'password', 'message' => 'Password must be at least 6 characters.'], 400);
        }

        if ($password !== $confirm) {
            $this->json(['success' => false, 'field' => 'confirm', 'message' => 'Passwords do not match.'], 400);
        }

        // --- Try to save the user in the database ---
        // create() returns false if the email is already taken.
        $userId = $this->userModel->create($name, $email, $password);

        if ($userId === false) {
            $this->json(['success' => false, 'field' => 'email', 'message' => 'An account with this email already exists.'], 400);
        }

        // Fetch the full user row so we can store it in the session.
        $user = $this->userModel->findById($userId);

        // Save the user's info in the PHP session so they stay logged in.
        Session::setUser($user);

        // Tell JavaScript to redirect the browser to the dashboard.
        $this->json(['success' => true, 'redirect' => '/dashboard']);
    }

    // -------------------------------------------------------
    // POST /api/auth/login
    // Handle login — called by JavaScript fetch().
    // Expects JSON body: { email, password }
    // Returns JSON: { success, redirect } or { success, field, message }
    // -------------------------------------------------------
    public function login()
    {
        $data = $this->getJsonBody();

        $email    = strtolower(trim($data['email']    ?? ''));
        $password = $data['password'] ?? '';

        // --- Validate ---
        if (empty($email)) {
            $this->json(['success' => false, 'field' => 'email', 'message' => 'Email is required.'], 400);
        }

        if (empty($password)) {
            $this->json(['success' => false, 'field' => 'password', 'message' => 'Password is required.'], 400);
        }

        // --- Look up the user by email ---
        $user = $this->userModel->findByEmail($email);

        // Check if the user exists AND their password matches.
        // We use a single generic error message for both cases on purpose —
        // telling someone "email not found" would let attackers probe for valid accounts.
        if (!$user || !$this->userModel->verifyPassword($password, $user['password'])) {
            $this->json(['success' => false, 'field' => 'general', 'message' => 'Invalid email or password.'], 401);
        }

        // Credentials are correct — log the user in by saving to the session.
        Session::setUser($user);

        $this->json(['success' => true, 'redirect' => '/dashboard']);
    }

    // -------------------------------------------------------
    // GET /api/auth/logout
    // Destroy the session and send the user back to the login page.
    // -------------------------------------------------------
    public function logout()
    {
        Session::destroy();
        $this->redirect('/');
    }
}
