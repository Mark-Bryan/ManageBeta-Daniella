<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ManageBeta - Dashboard</title>
    <link rel="stylesheet" href="/public/css/style.css" />
  </head>
  <body>

    <nav class="navbar">
      <a href="/dashboard" class="navbar-brand">
        <span class="brand-icon">T</span>
        ManageBeta
      </a>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <ul class="navbar-links" id="navLinks">
        <li><a href="/dashboard" class="active">Dashboard</a></li>
        <!-- PHP prints the user's first name directly in the HTML.
             htmlspecialchars() prevents XSS — it turns < > & into safe HTML entities. -->
        <li>
          <span style="color:var(--text-secondary);font-size:0.9rem;">
            Hi, <?= htmlspecialchars(explode(' ', $userName)[0]) ?>
          </span>
        </li>
        <li>
          <!-- Logout is a plain link — clicking it hits GET /api/auth/logout
               which destroys the session and redirects to the login page. -->
          <a href="/api/auth/logout" class="btn-logout">Logout</a>
        </li>
      </ul>
    </nav>

    <main>

      <!-- Welcome heading — PHP fills in the full name server-side -->
      <div class="dashboard-header">
        <h1>Welcome, <span><?= htmlspecialchars($userName) ?></span></h1>
      </div>

      <!-- ==================== STATS ROW ==================== -->
      <!-- These numbers start at 0 and are filled in by dashboard.js -->
      <div class="stats-row">
        <div class="stat-card total">
          <div class="stat-number" id="statTotal">0</div>
          <div class="stat-label">Total Tasks</div>
        </div>
        <div class="stat-card active">
          <div class="stat-number" id="statActive">0</div>
          <div class="stat-label">Active</div>
        </div>
        <div class="stat-card overdue">
          <div class="stat-number" id="statOverdue">0</div>
          <div class="stat-label">Overdue</div>
        </div>
        <div class="stat-card completed">
          <div class="stat-number" id="statCompleted">0</div>
          <div class="stat-label">Completed</div>
        </div>
      </div>

      <!-- ==================== TASKS SECTION ==================== -->
      <div class="task-section-header">
        <h2>My Tasks</h2>
        <button class="btn-add" id="addTaskBtn">+ Add Task</button>
      </div>

      <!-- Filter buttons — clicking one filters the task list -->
      <div class="filter-bar">
        <button class="filter-btn active" data-filter="all">All</button>
        <button class="filter-btn" data-filter="active">Active</button>
        <button class="filter-btn" data-filter="overdue">Overdue</button>
        <button class="filter-btn" data-filter="completed">Completed</button>
      </div>

      <!-- Task cards are injected here by dashboard.js -->
      <div class="tasks-grid" id="tasksGrid"></div>

    </main>

    <!-- ==================== ADD / EDIT TASK MODAL ==================== -->
    <div class="modal-overlay" id="taskModal">
      <div class="modal">
        <h3 id="modalTitle">Add New Task</h3>

        <form id="taskForm">
          <!-- Hidden field stores the task ID when editing an existing task.
               Empty when creating a new one. -->
          <input type="hidden" id="taskId" />

          <div class="form-group">
            <label for="taskTitle">Title</label>
            <input type="text" id="taskTitle" placeholder="Task title" required />
          </div>

          <div class="form-group">
            <label for="taskDescription">Description</label>
            <textarea id="taskDescription" placeholder="Describe your task..."></textarea>
          </div>

          <div class="form-group">
            <label for="taskDeadline">Deadline</label>
            <input type="datetime-local" id="taskDeadline" required />
          </div>

          <div class="form-group">
            <label for="taskPriority">Priority</label>
            <select id="taskPriority">
              <option value="low">Low</option>
              <option value="medium" selected>Medium</option>
              <option value="high">High</option>
            </select>
          </div>

          <div class="modal-actions">
            <button type="button" class="btn-secondary" id="cancelModal">Cancel</button>
            <button type="submit" class="btn-primary" id="saveTaskBtn">Save Task</button>
          </div>
        </form>
      </div>
    </div>

    <!-- ==================== DELETE CONFIRMATION DIALOG ==================== -->
    <div class="confirm-overlay" id="confirmOverlay">
      <div class="confirm-box">
        <h4>Delete Task?</h4>
        <p>This action cannot be undone.</p>
        <div class="confirm-actions">
          <button class="btn-secondary" id="confirmCancel">Cancel</button>
          <button class="btn-danger" id="confirmDelete">Delete</button>
        </div>
      </div>
    </div>

    <!-- ==================== TOAST NOTIFICATIONS ==================== -->
    <!-- dashboard.js appends toast elements here dynamically -->
    <div class="toast-container" id="toastContainer"></div>

    <footer class="footer">
      <p>ManageBeta &copy; 2026 &mdash; Built with love, by Daniella.</p>
    </footer>

    <!-- Alarm sound played when a task deadline is reached -->
    <audio id="alarmSound" src="/alarm.mp3" preload="auto"></audio>

    <script src="/public/js/dashboard.js"></script>
  </body>
</html>
