<?php

// ============================================================
// controllers/TaskController.php — Task Controller
// ============================================================
// Handles: show dashboard page, and all task API endpoints.
//
// showDashboard() renders HTML.
// Everything else returns JSON for JavaScript fetch() calls.
// ============================================================

class TaskController extends Controller
{
    // The Task model — used to talk to the tasks table.
    private $taskModel;

    public function __construct()
    {
        $this->taskModel = new Task();
    }

    // -------------------------------------------------------
    // GET /dashboard
    // Render the dashboard HTML page.
    // The user MUST be logged in to see this page.
    // -------------------------------------------------------
    public function showDashboard()
    {
        // If not logged in, redirect to the login page.
        Session::requireAuth();

        // Pass the user's name and email to the view so it can
        // greet them by name without any JavaScript needed.
        $this->render('dashboard', [
            'userName'  => Session::getUserName(),
            'userEmail' => Session::getUserEmail(),
        ]);
    }

    // -------------------------------------------------------
    // GET /api/tasks
    // Return all tasks for the logged-in user as JSON.
    // JavaScript calls this when the dashboard first loads.
    // -------------------------------------------------------
    public function list()
    {
        $this->requireApiAuth();

        $tasks = $this->taskModel->getAllByUser(Session::getUserId());

        // Convert due_date from "2026-05-26 14:30:00" → "2026-05-26T14:30:00"
        // so JavaScript's new Date() can parse it reliably in all browsers.
        $tasks = array_map(function ($task) {
            $task['due_date'] = str_replace(' ', 'T', $task['due_date']);
            return $task;
        }, $tasks);

        $this->json(['success' => true, 'tasks' => $tasks]);
    }

    // -------------------------------------------------------
    // POST /api/tasks
    // Create a new task.
    // Expects JSON body: { title, description, due_date, priority }
    // Returns JSON: { success, task }
    // -------------------------------------------------------
    public function create()
    {
        $this->requireApiAuth();

        $data = $this->getJsonBody();

        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '');
        $dueDate     = trim($data['due_date']    ?? '');
        $priority    = $data['priority']         ?? 'medium';

        // --- Validate ---
        if (empty($title)) {
            $this->json(['success' => false, 'message' => 'Title is required.'], 400);
        }

        if (empty($dueDate)) {
            $this->json(['success' => false, 'message' => 'Deadline is required.'], 400);
        }

        // Only allow known priority values — reject anything unexpected.
        if (!in_array($priority, ['low', 'medium', 'high'])) {
            $priority = 'medium';
        }

        // Save the task and get back its new ID.
        $taskId = $this->taskModel->create(
            Session::getUserId(),
            $title,
            $description,
            $dueDate,
            $priority
        );

        // Fetch the full saved task row to return to JavaScript.
        $task = $this->taskModel->findById($taskId);

        // Convert due_date back to ISO format for JavaScript.
        $task['due_date'] = str_replace(' ', 'T', $task['due_date']);

        // 201 Created is the correct HTTP status for a newly created resource.
        $this->json(['success' => true, 'task' => $task], 201);
    }

    // -------------------------------------------------------
    // PUT /api/tasks/{id}
    // Update an existing task.
    // $id comes from the URL — the Router passes it in.
    // Expects JSON body: { title, description, due_date, priority }
    // Returns JSON: { success, task }
    // -------------------------------------------------------
    public function update($id)
    {
        $this->requireApiAuth();

        $data = $this->getJsonBody();

        $title       = trim($data['title']       ?? '');
        $description = trim($data['description'] ?? '');
        $dueDate     = trim($data['due_date']    ?? '');
        $priority    = $data['priority']         ?? 'medium';

        if (empty($title) || empty($dueDate)) {
            $this->json(['success' => false, 'message' => 'Title and deadline are required.'], 400);
        }

        // update() also checks that the task belongs to this user.
        $updated = $this->taskModel->update(
            $id,
            Session::getUserId(),
            $title,
            $description,
            $dueDate,
            $priority
        );

        if (!$updated) {
            $this->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        // Return the updated task row.
        $task = $this->taskModel->findById($id);
        $task['due_date'] = str_replace(' ', 'T', $task['due_date']);

        $this->json(['success' => true, 'task' => $task]);
    }

    // -------------------------------------------------------
    // DELETE /api/tasks/{id}
    // Permanently delete a task.
    // $id comes from the URL.
    // Returns JSON: { success, message }
    // -------------------------------------------------------
    public function delete($id)
    {
        $this->requireApiAuth();

        $deleted = $this->taskModel->delete($id, Session::getUserId());

        if (!$deleted) {
            $this->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $this->json(['success' => true, 'message' => 'Task deleted successfully.']);
    }

    // -------------------------------------------------------
    // POST /api/tasks/{id}/toggle
    // Toggle a task between 'pending' and 'completed'.
    // $id comes from the URL.
    // Returns JSON: { success, new_status }
    // -------------------------------------------------------
    public function toggle($id)
    {
        $this->requireApiAuth();

        $newStatus = $this->taskModel->toggleStatus($id, Session::getUserId());

        if ($newStatus === false) {
            $this->json(['success' => false, 'message' => 'Task not found.'], 404);
        }

        $this->json(['success' => true, 'new_status' => $newStatus]);
    }
}
