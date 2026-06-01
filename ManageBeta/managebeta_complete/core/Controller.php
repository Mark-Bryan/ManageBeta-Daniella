<?php

// ============================================================
// core/Controller.php — Base Controller
// ============================================================
// Every controller (AuthController, TaskController) extends this
// class so they all share these helper methods.
//
// Think of this as a toolbox that every controller can use:
//   - render()  → load and display an HTML view
//   - json()    → send a JSON response back to JavaScript
//   - redirect() → send the browser to a different URL
//   - getJsonBody() → read JSON data sent by JavaScript
//   - requireApiAuth() → protect API routes from non-logged-in users
// ============================================================

class Controller
{
    // Load an HTML view file and display it in the browser.
    //
    // $view = the view filename without .php (e.g. 'auth' or 'dashboard')
    // $data = variables to make available inside the view file
    //
    // Example: $this->render('dashboard', ['userName' => 'Alice'])
    // Inside dashboard.php you can then use $userName directly.
    protected function render($view, $data = [])
    {
        // extract() takes ['userName' => 'Alice'] and creates $userName = 'Alice'
        // so the view file can use $userName without knowing it came from an array.
        extract($data);

        // Load the view file. PHP will execute it and send its HTML to the browser.
        require_once __DIR__ . '/../views/' . $view . '.php';
    }

    // Send a JSON response back to the browser.
    // This is used by API endpoints that JavaScript calls with fetch().
    //
    // $data       = PHP array to convert to JSON   e.g. ['success' => true]
    // $statusCode = HTTP status code               e.g. 200 (OK), 400 (Bad Request), 401 (Unauthorized)
    protected function json($data, $statusCode = 200)
    {
        // Set the HTTP status code so the browser knows if it worked or not.
        http_response_code($statusCode);

        // Tell the browser the response is JSON, not HTML.
        header('Content-Type: application/json');

        // Convert the PHP array to a JSON string and print it.
        echo json_encode($data);

        // Stop here — don't render any HTML after a JSON response.
        exit;
    }

    // Redirect the browser to a different URL.
    // The browser will automatically load that new URL.
    protected function redirect($url)
    {
        header('Location: ' . $url);
        exit;
    }

    // Read the raw JSON body from a fetch() request sent by JavaScript.
    // When JS does: fetch('/api/tasks', { body: JSON.stringify({...}) })
    // this method reads and decodes that body.
    protected function getJsonBody()
    {
        // php://input is a stream that contains the raw request body.
        $rawBody = file_get_contents('php://input');

        // Decode the JSON string into a PHP array.
        // true = return as array, not as an object.
        // If decoding fails, return an empty array instead of null.
        return json_decode($rawBody, true) ?? [];
    }

    // Protect an API endpoint from unauthenticated requests.
    // If the user is not logged in, respond with a 401 JSON error
    // instead of redirecting (because JS fetch() expects JSON back).
    protected function requireApiAuth()
    {
        if (!Session::isLoggedIn()) {
            $this->json(['success' => false, 'message' => 'You are not logged in.'], 401);
        }
    }
}
