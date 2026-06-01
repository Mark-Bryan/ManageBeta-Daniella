(function () {
  "use strict";

  // ============================================================
  // public/js/dashboard.js — Dashboard Logic
  // ============================================================
  // This file drives everything on the dashboard page:
  //  - Loading tasks from the PHP backend
  //  - Rendering task cards
  //  - Countdown timers (updated every second)
  //  - Alarm notifications when a deadline is reached
  //  - Add / Edit / Delete / Complete task actions
  //  - Filter buttons (All / Active / Overdue / Completed)
  //  - Toast popup messages
  //
  // All data comes from and goes to the PHP API via fetch().
  // There is NO localStorage — the database is the source of truth.
  // ============================================================


  // -------------------------------------------------------
  // State — variables that track what's happening right now
  // -------------------------------------------------------
  var allTasks       = [];    // All tasks loaded from the server
  var currentFilter  = "all"; // Which filter tab is active
  var deleteTargetId = null;  // ID of the task the user wants to delete
  var alertedIds     = {};    // Tracks tasks we've already alarmed (prevents repeats)
  var countdownTimer = null;  // Reference to the setInterval so we can clear it


  // -------------------------------------------------------
  // DOM References — grab every element we'll need
  // -------------------------------------------------------
  var tasksGrid        = document.getElementById("tasksGrid");
  var addTaskBtn       = document.getElementById("addTaskBtn");
  var taskModal        = document.getElementById("taskModal");
  var modalTitle       = document.getElementById("modalTitle");
  var taskForm         = document.getElementById("taskForm");
  var taskIdField      = document.getElementById("taskId");
  var taskTitleField   = document.getElementById("taskTitle");
  var taskDescField    = document.getElementById("taskDescription");
  var taskDeadline     = document.getElementById("taskDeadline");
  var taskPriority     = document.getElementById("taskPriority");
  var cancelModalBtn   = document.getElementById("cancelModal");
  var confirmOverlay   = document.getElementById("confirmOverlay");
  var confirmCancelBtn = document.getElementById("confirmCancel");
  var confirmDeleteBtn = document.getElementById("confirmDelete");
  var toastContainer   = document.getElementById("toastContainer");
  var filterBtns       = document.querySelectorAll(".filter-btn");
  var statTotal        = document.getElementById("statTotal");
  var statActive       = document.getElementById("statActive");
  var statOverdue      = document.getElementById("statOverdue");
  var statCompleted    = document.getElementById("statCompleted");
  var alarmSound       = document.getElementById("alarmSound");
  var hamburger        = document.getElementById("hamburger");
  var navLinks         = document.getElementById("navLinks");


  // -------------------------------------------------------
  // API Helper
  // -------------------------------------------------------
  // A single function for all fetch() calls to the PHP backend.
  // method = "GET", "POST", "PUT", or "DELETE"
  // url    = the API endpoint, e.g. "/api/tasks" or "/api/tasks/5"
  // body   = optional JS object to send as JSON in the request body
  //
  // Returns a Promise that resolves to a parsed JSON object.
  // -------------------------------------------------------
  function apiRequest(method, url, body) {
    var options = {
      method:  method,
      headers: { "Content-Type": "application/json" }
    };

    // Only attach a body for methods that support it (POST, PUT, DELETE)
    if (body) {
      options.body = JSON.stringify(body);
    }

    return fetch(url, options).then(function (response) {
      return response.json();
    });
  }


  // -------------------------------------------------------
  // Load Tasks
  // -------------------------------------------------------
  // Called once when the page loads.
  // Fetches all tasks from PHP and renders them.
  // -------------------------------------------------------
  function loadTasks() {
    apiRequest("GET", "/api/tasks").then(function (result) {
      if (result.success) {
        allTasks = result.tasks; // Store tasks in our local state
        renderTasks();
        startCountdownTimer();
      }
    });
  }


  // -------------------------------------------------------
  // Toast Notifications
  // -------------------------------------------------------
  // Shows a small popup in the top-right corner.
  // type    = "success" (green), "alert" (red), or "info" (blue)
  // title   = bold heading text
  // message = smaller detail text
  // -------------------------------------------------------
  function showToast(type, title, message) {
    var icons = { success: "✓", alert: "⚠", info: "ℹ" };

    var toast = document.createElement("div");
    toast.className = "toast " + type;

    toast.innerHTML =
      '<span class="toast-icon">' + (icons[type] || "") + "</span>" +
      '<div class="toast-body">' +
        '<div class="toast-title">' + title + "</div>" +
        '<div class="toast-message">' + message + "</div>" +
      "</div>" +
      '<button class="toast-close">&times;</button>';

    // Allow closing the toast by clicking its X button
    toast.querySelector(".toast-close").addEventListener("click", function () {
      removeToast(toast);
    });

    toastContainer.appendChild(toast);

    // Automatically remove the toast after 6 seconds
    setTimeout(function () { removeToast(toast); }, 6000);
  }

  function removeToast(toast) {
    if (!toast.parentNode) return;
    toast.classList.add("leaving"); // CSS slide-out animation
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
  }


  // -------------------------------------------------------
  // Stats Row
  // -------------------------------------------------------
  // Updates the 4 numbers at the top: Total, Active, Overdue, Completed.
  // -------------------------------------------------------
  function updateStats() {
    var now       = Date.now();
    var total     = allTasks.length;
    var completed = allTasks.filter(function (t) { return t.status === "completed"; }).length;
    var overdue   = allTasks.filter(function (t) {
      // Overdue = not completed AND deadline has passed
      return t.status !== "completed" && new Date(t.due_date).getTime() < now;
    }).length;
    // Active = everything that isn't completed or overdue
    var active = total - completed - overdue;

    statTotal.textContent     = total;
    statActive.textContent    = Math.max(0, active);
    statOverdue.textContent   = overdue;
    statCompleted.textContent = completed;
  }


  // -------------------------------------------------------
  // Countdown Formatter
  // -------------------------------------------------------
  // Figures out what text to show on the countdown badge.
  // Returns an object with the display text and some flags.
  // -------------------------------------------------------
  function getCountdownInfo(dueDate, isCompleted) {
    // Completed tasks just say "Completed"
    if (isCompleted) {
      return { text: "Completed", isOverdue: false, isCompleted: true };
    }

    var diff = new Date(dueDate).getTime() - Date.now();

    if (diff <= 0) {
      // Deadline has passed — show how long ago
      var absDiff = Math.abs(diff);
      var d = Math.floor(absDiff / 86400000);           // days
      var h = Math.floor((absDiff % 86400000) / 3600000); // hours
      var m = Math.floor((absDiff % 3600000)  / 60000);   // minutes
      var parts = [];
      if (d > 0) parts.push(d + "d");
      if (h > 0) parts.push(h + "h");
      parts.push(m + "m");
      return { text: "Overdue by " + parts.join(" "), isOverdue: true, isCompleted: false };
    }

    // Deadline is in the future — show the remaining time
    var d = Math.floor(diff / 86400000);
    var h = Math.floor((diff % 86400000) / 3600000);
    var m = Math.floor((diff % 3600000)  / 60000);
    var s = Math.floor((diff % 60000)    / 1000);
    var parts = [];
    if (d > 0) parts.push(d + "d");
    if (h > 0) parts.push(h + "h");
    if (m > 0) parts.push(m + "m");
    parts.push(s + "s");
    return { text: parts.join(" "), isOverdue: false, isCompleted: false };
  }


  // -------------------------------------------------------
  // Escape HTML
  // -------------------------------------------------------
  // Prevents XSS — if a task title contains <script>, this
  // turns it into &lt;script&gt; so it renders as text, not code.
  // -------------------------------------------------------
  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }


  // -------------------------------------------------------
  // Render Tasks
  // -------------------------------------------------------
  // Clears the tasks grid and rebuilds it from the allTasks array.
  // Applies the currently active filter first.
  // -------------------------------------------------------
  function renderTasks() {
    var now      = Date.now();
    var filtered = allTasks; // Start with all tasks

    // Apply the selected filter
    if (currentFilter === "active") {
      filtered = allTasks.filter(function (t) {
        return t.status !== "completed" && new Date(t.due_date).getTime() >= now;
      });
    } else if (currentFilter === "overdue") {
      filtered = allTasks.filter(function (t) {
        return t.status !== "completed" && new Date(t.due_date).getTime() < now;
      });
    } else if (currentFilter === "completed") {
      filtered = allTasks.filter(function (t) { return t.status === "completed"; });
    }

    // Clear whatever is currently showing
    tasksGrid.innerHTML = "";

    // Show an empty state message if there are no tasks to display
    if (filtered.length === 0) {
      tasksGrid.innerHTML =
        '<div class="empty-state">' +
          '<div class="empty-icon">📋</div>' +
          '<p>No tasks here. Click "+ Add Task" to create one!</p>' +
        "</div>";
      updateStats();
      return;
    }

    // Build one card for each task
    filtered.forEach(function (task) {
      var isCompleted = task.status === "completed";
      var cd          = getCountdownInfo(task.due_date, isCompleted);
      var cdClass     = cd.isOverdue ? "overdue" : cd.isCompleted ? "completed" : "";
      var cardClass   = isCompleted ? "task-card completed-task" : "task-card";

      // Format the due date for the bottom of the card
      var deadlineDate = new Date(task.due_date);
      var dateStr = deadlineDate.toLocaleDateString("en-US", {
        month: "short", day: "numeric", year: "numeric"
      });
      var timeStr = deadlineDate.toLocaleTimeString("en-US", {
        hour: "2-digit", minute: "2-digit"
      });

      var card = document.createElement("div");
      card.className = cardClass;
      card.setAttribute("data-task-id", task.id); // Used by the countdown timer

      card.innerHTML =
        // Priority badge (top-right corner of the card)
        '<span class="task-priority ' + task.priority + '">' + escapeHtml(task.priority) + "</span>" +
        // Task title
        '<div class="task-title">' + escapeHtml(task.title) + "</div>" +
        // Description (only shown if there is one)
        (task.description ? '<div class="task-description">' + escapeHtml(task.description) + "</div>" : "") +
        // Countdown timer badge — data-due stores the deadline for the interval to read
        '<div class="countdown ' + cdClass + '" data-due="' + task.due_date + '" data-completed="' + isCompleted + '">' +
          '<span class="countdown-icon">⏲</span>' +
          '<span class="countdown-value">' + cd.text + "</span>" +
        "</div>" +
        // Deadline date shown at the bottom
        '<div class="task-meta"><span>' + dateStr + " at " + timeStr + "</span></div>" +
        // Action buttons
        '<div class="task-actions">' +
          '<button class="btn-icon complete" data-action="toggle" data-id="' + task.id + '">' +
            (isCompleted ? "↺ Reopen" : "✓ Complete") +
          "</button>" +
          '<button class="btn-icon" data-action="edit" data-id="' + task.id + '">Edit</button>' +
          '<button class="btn-icon delete" data-action="delete" data-id="' + task.id + '">Delete</button>' +
        "</div>";

      tasksGrid.appendChild(card);
    });

    updateStats();
  }


  // -------------------------------------------------------
  // Countdown Timer
  // -------------------------------------------------------
  // Runs every second and updates the countdown text on each task card.
  // Also fires the alarm the moment a deadline is reached.
  // -------------------------------------------------------
  function startCountdownTimer() {
    // Clear any previously running timer before starting a new one
    if (countdownTimer) clearInterval(countdownTimer);

    countdownTimer = setInterval(function () {
      var now          = Date.now();
      var countdowns   = document.querySelectorAll(".countdown");
      var needRerender = false; // True if we need to rebuild the stats

      countdowns.forEach(function (el) {
        var dueDate     = el.getAttribute("data-due");
        var isCompleted = el.getAttribute("data-completed") === "true";

        // Skip completed tasks — no countdown needed
        if (isCompleted) return;

        var diff     = new Date(dueDate).getTime() - now;
        var cdValue  = el.querySelector(".countdown-value");
        var taskCard = el.closest(".task-card");
        var taskId   = taskCard ? taskCard.getAttribute("data-task-id") : null;

        if (diff <= 0) {
          // === OVERDUE — show how long ago ===
          var absDiff = Math.abs(diff);
          var d = Math.floor(absDiff / 86400000);
          var h = Math.floor((absDiff % 86400000) / 3600000);
          var m = Math.floor((absDiff % 3600000)  / 60000);
          var parts = [];
          if (d > 0) parts.push(d + "d");
          if (h > 0) parts.push(h + "h");
          parts.push(m + "m");
          cdValue.textContent = "Overdue by " + parts.join(" ");
          el.classList.add("overdue");

          // Fire the alarm — but only ONCE per task, not every second
          if (taskId && !alertedIds[taskId]) {
            alertedIds[taskId] = true; // Remember we already alarmed this one

            var taskTitle = taskCard.querySelector(".task-title").textContent;
            showToast("alert", "Deadline Reached!", '"' + taskTitle + '" is now overdue.');

            // Play the alarm sound (browser may block if user hasn't interacted yet)
            if (alarmSound) {
              alarmSound.play().catch(function () {
                // Silently ignore autoplay blocks — the toast still shows
              });
            }

            needRerender = true; // Stats need updating (active → overdue)
          }

        } else {
          // === STILL PENDING — show remaining time ===
          var d = Math.floor(diff / 86400000);
          var h = Math.floor((diff % 86400000) / 3600000);
          var m = Math.floor((diff % 3600000)  / 60000);
          var s = Math.floor((diff % 60000)    / 1000);
          var parts = [];
          if (d > 0) parts.push(d + "d");
          if (h > 0) parts.push(h + "h");
          if (m > 0) parts.push(m + "m");
          parts.push(s + "s");
          cdValue.textContent = parts.join(" ");
        }
      });

      // Only rebuild stats if something changed (a task just became overdue)
      if (needRerender) updateStats();

    }, 1000); // Run every 1000 milliseconds = 1 second
  }


  // -------------------------------------------------------
  // Filter Buttons
  // -------------------------------------------------------
  filterBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      // Remove "active" from all buttons, then add it to the clicked one
      filterBtns.forEach(function (b) { b.classList.remove("active"); });
      btn.classList.add("active");

      currentFilter = btn.getAttribute("data-filter"); // "all", "active", "overdue", "completed"
      renderTasks();
    });
  });


  // -------------------------------------------------------
  // Modal — Open for Adding a New Task
  // -------------------------------------------------------
  function openAddModal() {
    modalTitle.textContent = "Add New Task";
    document.getElementById("saveTaskBtn").textContent = "Save Task";

    taskForm.reset();     // Clear all fields
    taskIdField.value = ""; // Make sure no ID is set (this is a new task)

    // Pre-fill the deadline to one hour from now as a convenience
    var oneHourFromNow = new Date(Date.now() + 3600000);
    taskDeadline.value = oneHourFromNow.toISOString().slice(0, 16); // "YYYY-MM-DDTHH:MM"

    taskModal.classList.add("active");
  }

  // -------------------------------------------------------
  // Modal — Open for Editing an Existing Task
  // -------------------------------------------------------
  function openEditModal(task) {
    modalTitle.textContent = "Edit Task";
    document.getElementById("saveTaskBtn").textContent = "Update Task";

    // Fill the form fields with the task's current values
    taskIdField.value    = task.id;
    taskTitleField.value = task.title;
    taskDescField.value  = task.description || "";
    taskPriority.value   = task.priority;

    // due_date from the server is "2026-05-26T14:30:00"
    // datetime-local input needs "2026-05-26T14:30" (no seconds)
    taskDeadline.value = task.due_date.slice(0, 16);

    taskModal.classList.add("active");
  }

  function closeModal() {
    taskModal.classList.remove("active");
    taskForm.reset();
    taskIdField.value = "";
  }

  addTaskBtn.addEventListener("click", function () { openAddModal(); });
  cancelModalBtn.addEventListener("click", closeModal);

  // Close modal when clicking the dark overlay behind it
  taskModal.addEventListener("click", function (e) {
    if (e.target === taskModal) closeModal();
  });


  // -------------------------------------------------------
  // Task Form — Save (Create or Update)
  // -------------------------------------------------------
  taskForm.addEventListener("submit", function (e) {
    e.preventDefault();

    var id          = taskIdField.value;       // Empty = new task, number = edit
    var title       = taskTitleField.value.trim();
    var description = taskDescField.value.trim();
    var dueDate     = taskDeadline.value;      // "2026-05-26T14:30"
    var priority    = taskPriority.value;

    if (!title || !dueDate) return; // Basic guard

    var taskData = {
      title:       title,
      description: description,
      due_date:    dueDate,
      priority:    priority
    };

    if (id) {
      // --- UPDATE an existing task ---
      apiRequest("PUT", "/api/tasks/" + id, taskData).then(function (result) {
        if (result.success) {
          // Replace the old version of this task in our local array
          allTasks = allTasks.map(function (t) {
            return t.id == id ? result.task : t;
          });
          closeModal();
          renderTasks();
          showToast("success", "Task Updated", '"' + title + '" has been updated.');
        }
      });
    } else {
      // --- CREATE a new task ---
      apiRequest("POST", "/api/tasks", taskData).then(function (result) {
        if (result.success) {
          // Add the new task to our local array
          allTasks.push(result.task);
          closeModal();
          renderTasks();
          showToast("success", "Task Created", '"' + title + '" has been added.');
        }
      });
    }
  });


  // -------------------------------------------------------
  // Task Card Buttons — Edit / Complete / Delete
  // -------------------------------------------------------
  // We listen on the GRID (not individual buttons) using event delegation.
  // This means it works for dynamically added cards too.
  // -------------------------------------------------------
  tasksGrid.addEventListener("click", function (e) {
    // Find the nearest ancestor button with a data-action attribute
    var btn = e.target.closest("[data-action]");
    if (!btn) return; // Click was not on an action button

    var action = btn.getAttribute("data-action"); // "edit", "delete", or "toggle"
    var id     = btn.getAttribute("data-id");     // The task's ID

    if (action === "edit") {
      // Find this task in our local array and open the edit modal
      var task = allTasks.find(function (t) { return t.id == id; });
      if (task) openEditModal(task);

    } else if (action === "delete") {
      // Show the confirmation dialog — don't delete yet
      deleteTargetId = id;
      confirmOverlay.classList.add("active");

    } else if (action === "toggle") {
      // Tell PHP to flip the status, then update our local array
      apiRequest("POST", "/api/tasks/" + id + "/toggle").then(function (result) {
        if (result.success) {
          allTasks = allTasks.map(function (t) {
            if (t.id == id) t.status = result.new_status;
            return t;
          });
          renderTasks();

          // Show a toast if the task was just completed
          if (result.new_status === "completed") {
            var task = allTasks.find(function (t) { return t.id == id; });
            if (task) showToast("success", "Task Completed", '"' + task.title + '" is done!');
          }
        }
      });
    }
  });


  // -------------------------------------------------------
  // Delete Confirmation Dialog
  // -------------------------------------------------------
  confirmCancelBtn.addEventListener("click", function () {
    confirmOverlay.classList.remove("active");
    deleteTargetId = null;
  });

  // Close dialog when clicking the overlay behind it
  confirmOverlay.addEventListener("click", function (e) {
    if (e.target === confirmOverlay) {
      confirmOverlay.classList.remove("active");
      deleteTargetId = null;
    }
  });

  confirmDeleteBtn.addEventListener("click", function () {
    if (!deleteTargetId) return;

    var idToDelete = deleteTargetId;

    apiRequest("DELETE", "/api/tasks/" + idToDelete).then(function (result) {
      if (result.success) {
        // Remove the task from our local array
        allTasks = allTasks.filter(function (t) { return t.id != idToDelete; });

        showToast("info", "Task Deleted", "The task has been removed.");
        confirmOverlay.classList.remove("active");
        deleteTargetId = null;
        renderTasks();
      }
    });
  });


  // -------------------------------------------------------
  // Keyboard Shortcuts
  // -------------------------------------------------------
  document.addEventListener("keydown", function (e) {
    if (e.key === "Escape") {
      if (confirmOverlay.classList.contains("active")) {
        confirmOverlay.classList.remove("active");
        deleteTargetId = null;
      } else if (taskModal.classList.contains("active")) {
        closeModal();
      }
    }
  });


  // -------------------------------------------------------
  // Hamburger Menu (mobile)
  // -------------------------------------------------------
  hamburger.addEventListener("click", function () {
    navLinks.classList.toggle("open");
  });

  document.addEventListener("click", function (e) {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove("open");
    }
  });


  // -------------------------------------------------------
  // Start — load tasks from the server when the page opens
  // -------------------------------------------------------
  loadTasks();

})();
