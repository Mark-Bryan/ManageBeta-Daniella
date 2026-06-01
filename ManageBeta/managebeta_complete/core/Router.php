<?php

// ============================================================
// core/Router.php — URL Router
// ============================================================
// The Router looks at the current URL and HTTP method, finds
// the matching route, and calls the right controller action.
//
// How routing works:
//   1. You register routes:  $router->get('/dashboard', 'TaskController', 'showDashboard')
//   2. A request comes in:   GET /dashboard
//   3. Router finds a match and calls: TaskController->showDashboard()
//
// Routes with {id} in the path (like /api/tasks/{id}) capture
// the number in the URL and pass it to the controller method.
// ============================================================

class Router
{
    // All registered routes are stored in this array.
    // Each route is: [method, path, controllerName, actionName]
    private $routes = [];

    // Register a route for GET requests.
    public function get($path, $controller, $action)
    {
        $this->routes[] = ['GET', $path, $controller, $action];
    }

    // Register a route for POST requests.
    public function post($path, $controller, $action)
    {
        $this->routes[] = ['POST', $path, $controller, $action];
    }

    // Register a route for PUT requests (used for updates).
    public function put($path, $controller, $action)
    {
        $this->routes[] = ['PUT', $path, $controller, $action];
    }

    // Register a route for DELETE requests.
    public function delete($path, $controller, $action)
    {
        $this->routes[] = ['DELETE', $path, $controller, $action];
    }

    // Read the current request and find + call the matching route.
    public function dispatch()
    {
        // What HTTP method is this? (GET, POST, PUT, DELETE)
        $method = $_SERVER['REQUEST_METHOD'];

        // What is the URL path? e.g. /dashboard or /api/tasks/5
        // parse_url() extracts just the path part, ignoring query strings.
        $path = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

        // Remove a trailing slash from the path (but keep the root "/" as-is).
        // This makes /dashboard and /dashboard/ both match the same route.
        if ($path !== '/') {
            $path = rtrim($path, '/');
        }

        // Loop through every registered route and try to match it.
        foreach ($this->routes as $route) {
            [$routeMethod, $routePath, $controllerName, $actionName] = $route;

            // Skip this route if the HTTP method doesn't match.
            if ($routeMethod !== $method) {
                continue;
            }

            // Convert route placeholders like {id} into a regex capture group.
            // {id} becomes ([0-9]+) which matches any number in the URL.
            // Example: /api/tasks/{id} becomes #^/api/tasks/([0-9]+)$#
            $pattern = preg_replace('/\{[a-z]+\}/', '([0-9]+)', $routePath);
            $pattern = '#^' . $pattern . '$#';

            // Check if the current URL path matches this route's pattern.
            if (preg_match($pattern, $path, $matches)) {
                // $matches[0] is the full URL match (we don't need it).
                // $matches[1], $matches[2], etc. are the captured {id} values.
                $params = array_slice($matches, 1);

                // Create an instance of the controller class.
                // $controllerName is a string like 'TaskController',
                // and PHP lets us use a string as a class name here.
                $controller = new $controllerName();

                // Call the action method on the controller, passing any URL params.
                // e.g. if params = [5], this calls $controller->update(5)
                call_user_func_array([$controller, $actionName], $params);

                return; // We found and handled the route, stop here.
            }
        }

        // If we get here, no route matched the URL — show a 404 page.
        http_response_code(404);
        echo '<h1 style="font-family:sans-serif;padding:2rem;">404 — Page Not Found</h1>';
    }
}
