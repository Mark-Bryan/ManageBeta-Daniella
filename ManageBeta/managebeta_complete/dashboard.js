(function () {
  "use strict";

  // Auth guard
  var session = JSON.parse(localStorage.getItem("taskflow_session") || "null");
  if (!session) {
    window.location.href = "index.html";
    return;
  }

  // DOM refs
  var userNameEl = document.getElementById("userName");
  var userGreetingEl = document.getElementById("userGreeting");
  var logoutBtn = document.getElementById("logoutBtn");
  var hamburger = document.getElementById("hamburger");
  var navLinks = document.getElementById("navLinks");
  var statTotal = document.getElementById("statTotal");
  var statActive = document.getElementById("statActive");
  var statOverdue = document.getElementById("statOverdue");
  var statCompleted = document.getElementById("statCompleted");
  var tasksGrid = document.getElementById("tasksGrid");
  var addTaskBtn = document.getElementById("addTaskBtn");
  var taskModal = document.getElementById("taskModal");
  var modalTitle = document.getElementById("modalTitle");
  var taskForm = document.getElementById("taskForm");
  var taskIdField = document.getElementById("taskId");
  var taskTitleField = document.getElementById("taskTitle");
  var taskDescField = document.getElementById("taskDescription");
  var taskDeadlineField = document.getElementById("taskDeadline");
  var taskPriorityField = document.getElementById("taskPriority");
  var cancelModalBtn = document.getElementById("cancelModal");
  var confirmOverlay = document.getElementById("confirmOverlay");
  var confirmCancelBtn = document.getElementById("confirmCancel");
  var confirmDeleteBtn = document.getElementById("confirmDelete");
  var toastContainer = document.getElementById("toastContainer");
  var filterBtns = document.querySelectorAll(".filter-btn");

  // State
  var currentFilter = "all";
  var deleteTargetId = null;
  var countdownInterval = null;
  var alertedTasks = JSON.parse(localStorage.getItem("taskflow_alerted") || "{}");

  // Init
  userNameEl.textContent = session.name;
  userGreetingEl.textContent = "Hi, " + session.name.split(" ")[0];

  // Storage helpers
  function getTasks() {
    var all = JSON.parse(localStorage.getItem("taskflow_tasks") || "[]");
    return all.filter(function (t) { return t.userId === session.id; });
  }

  function getAllTasks() {
    return JSON.parse(localStorage.getItem("taskflow_tasks") || "[]");
  }

  function saveAllTasks(tasks) {
    localStorage.setItem("taskflow_tasks", JSON.stringify(tasks));
  }

  function generateId() {
    return Date.now().toString(36) + Math.random().toString(36).substr(2, 5);
  }

  // Logout
  logoutBtn.addEventListener("click", function () {
    localStorage.removeItem("taskflow_session");
    window.location.href = "index.html";
  });

  // Hamburger
  hamburger.addEventListener("click", function () {
    navLinks.classList.toggle("open");
  });
  document.addEventListener("click", function (e) {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove("open");
    }
  });

  // Toast
  function showToast(type, title, message) {
    var icons = { alert: "\u26A0", success: "\u2713", info: "\u2139" };
    var toast = document.createElement("div");
    toast.className = "toast " + type;
    toast.innerHTML =
      '<span class="toast-icon">' + (icons[type] || "") + "</span>" +
      '<div class="toast-body">' +
        '<div class="toast-title">' + title + "</div>" +
        '<div class="toast-message">' + message + "</div>" +
      "</div>" +
      '<button class="toast-close">&times;</button>';
    toast.querySelector(".toast-close").addEventListener("click", function () {
      removeToast(toast);
    });
    toastContainer.appendChild(toast);
    setTimeout(function () { removeToast(toast); }, 6000);
  }

  function removeToast(toast) {
    if (!toast.parentNode) return;
    toast.classList.add("leaving");
    setTimeout(function () {
      if (toast.parentNode) toast.parentNode.removeChild(toast);
    }, 300);
  }

  // Stats
  function updateStats() {
    var tasks = getTasks();
    var now = Date.now();
    var total = tasks.length;
    var completed = tasks.filter(function (t) { return t.completed; }).length;
    var overdue = tasks.filter(function (t) {
      return !t.completed && new Date(t.deadline).getTime() < now;
    }).length;
    var active = total - completed - overdue;

    statTotal.textContent = total;
    statActive.textContent = Math.max(0, active);
    statOverdue.textContent = overdue;
    statCompleted.textContent = completed;
  }

  // Countdown formatter
  function formatCountdown(deadline) {
    var diff = new Date(deadline).getTime() - Date.now();
    if (diff <= 0) {
      var absDiff = Math.abs(diff);
      var d = Math.floor(absDiff / 86400000);
      var h = Math.floor((absDiff % 86400000) / 3600000);
      var m = Math.floor((absDiff % 3600000) / 60000);
      var parts = [];
      if (d > 0) parts.push(d + "d");
      if (h > 0) parts.push(h + "h");
      parts.push(m + "m");
      return { text: "Overdue by " + parts.join(" "), overdue: true, completed: false };
    }
    var d = Math.floor(diff / 86400000);
    var h = Math.floor((diff % 86400000) / 3600000);
    var m = Math.floor((diff % 3600000) / 60000);
    var s = Math.floor((diff % 60000) / 1000);
    var parts = [];
    if (d > 0) parts.push(d + "d");
    if (h > 0) parts.push(h + "h");
    if (m > 0) parts.push(m + "m");
    parts.push(s + "s");
    return { text: parts.join(" "), overdue: false, completed: false };
  }

  // Escape HTML
  function escapeHtml(str) {
    var div = document.createElement("div");
    div.textContent = str;
    return div.innerHTML;
  }

  // Render tasks
  function renderTasks() {
    var tasks = getTasks();
    var now = Date.now();
    var filtered = tasks;

    if (currentFilter === "active") {
      filtered = tasks.filter(function (t) {
        return !t.completed && new Date(t.deadline).getTime() >= now;
      });
    } else if (currentFilter === "overdue") {
      filtered = tasks.filter(function (t) {
        return !t.completed && new Date(t.deadline).getTime() < now;
      });
    } else if (currentFilter === "completed") {
      filtered = tasks.filter(function (t) { return t.completed; });
    }

    tasksGrid.innerHTML = "";

    if (filtered.length === 0) {
      tasksGrid.innerHTML =
        '<div class="empty-state">' +
          '<div class="empty-icon">&#128203;</div>' +
          '<p>No tasks found. Click "+ Add Task" to create one!</p>' +
        "</div>";
      updateStats();
      return;
    }

    filtered.forEach(function (task) {
      var cd = task.completed
        ? { text: "Completed", overdue: false, completed: true }
        : formatCountdown(task.deadline);
      var cdClass = cd.overdue ? "overdue" : cd.completed ? "completed" : "";
      var cardClass = task.completed ? "task-card completed-task" : "task-card";
      var deadlineDate = new Date(task.deadline);
      var dateStr = deadlineDate.toLocaleDateString("en-US", { month: "short", day: "numeric", year: "numeric" });
      var timeStr = deadlineDate.toLocaleTimeString("en-US", { hour: "2-digit", minute: "2-digit" });

      var card = document.createElement("div");
      card.className = cardClass;
      card.setAttribute("data-task-id", task.id);
      card.innerHTML =
        '<span class="task-priority ' + task.priority + '">' + task.priority + "</span>" +
        '<div class="task-title">' + escapeHtml(task.title) + "</div>" +
        (task.description ? '<div class="task-description">' + escapeHtml(task.description) + "</div>" : "") +
        '<div class="countdown ' + cdClass + '" data-deadline="' + task.deadline + '" data-completed="' + task.completed + '">' +
          '<span class="countdown-icon">&#9202;</span>' +
          '<span class="countdown-value">' + cd.text + "</span>" +
        "</div>" +
        '<div class="task-meta"><span>' + dateStr + " at " + timeStr + "</span></div>" +
        '<div class="task-actions">' +
          '<button class="btn-icon complete" data-action="toggle" data-id="' + task.id + '">' +
            (task.completed ? "&#8634; Reopen" : "&#10003; Complete") +
          "</button>" +
          '<button class="btn-icon" data-action="edit" data-id="' + task.id + '">Edit</button>' +
          '<button class="btn-icon delete" data-action="delete" data-id="' + task.id + '">Delete</button>' +
        "</div>";

      tasksGrid.appendChild(card);
    });

    updateStats();
  }

  // Countdown timer - updates every second
  function startCountdownTimer() {
    if (countdownInterval) clearInterval(countdownInterval);
    countdownInterval = setInterval(function () {
      var countdowns = document.querySelectorAll(".countdown");
      var now = Date.now();
      var needRerender = false;

      countdowns.forEach(function (el) {
        var deadline = el.getAttribute("data-deadline");
        var isCompleted = el.getAttribute("data-completed") === "true";
        if (isCompleted) return;

        var diff = new Date(deadline).getTime() - now;
        var cdValue = el.querySelector(".countdown-value");

        if (diff <= 0) {
          var absDiff = Math.abs(diff);
          var d = Math.floor(absDiff / 86400000);
          var h = Math.floor((absDiff % 86400000) / 3600000);
          var m = Math.floor((absDiff % 3600000) / 60000);
          var parts = [];
          if (d > 0) parts.push(d + "d");
          if (h > 0) parts.push(h + "h");
          parts.push(m + "m");
          cdValue.textContent = "Overdue by " + parts.join(" ");
          el.classList.add("overdue");

          var taskId = el.closest(".task-card").getAttribute("data-task-id");
          if (!alertedTasks[taskId]) {
            alertedTasks[taskId] = true;
            localStorage.setItem("taskflow_alerted", JSON.stringify(alertedTasks));
            var taskTitle = el.closest(".task-card").querySelector(".task-title").textContent;
            showToast("alert", "Deadline Reached!", '"' + taskTitle + '" is now overdue.');
            needRerender = true;
          }
        } else {
          var d = Math.floor(diff / 86400000);
          var h = Math.floor((diff % 86400000) / 3600000);
          var m = Math.floor((diff % 3600000) / 60000);
          var s = Math.floor((diff % 60000) / 1000);
          var parts = [];
          if (d > 0) parts.push(d + "d");
          if (h > 0) parts.push(h + "h");
          if (m > 0) parts.push(m + "m");
          parts.push(s + "s");
          cdValue.textContent = parts.join(" ");
        }
      });

      if (needRerender) updateStats();
    }, 1000);
  }

  // Filter
  filterBtns.forEach(function (btn) {
    btn.addEventListener("click", function () {
      filterBtns.forEach(function (b) { b.classList.remove("active"); });
      btn.classList.add("active");
      currentFilter = btn.getAttribute("data-filter");
      renderTasks();
    });
  });

  // Modal
  function openModal(task) {
    if (task) {
      modalTitle.textContent = "Edit Task";
      document.getElementById("saveTaskBtn").textContent = "Update Task";
      taskIdField.value = task.id;
      taskTitleField.value = task.title;
      taskDescField.value = task.description || "";
      taskDeadlineField.value = task.deadline;
      taskPriorityField.value = task.priority;
    } else {
      modalTitle.textContent = "Add New Task";
      document.getElementById("saveTaskBtn").textContent = "Save Task";
      taskForm.reset();
      taskIdField.value = "";
      var oneHour = new Date(Date.now() + 3600000);
      taskDeadlineField.value = oneHour.toISOString().slice(0, 16);
    }
    taskModal.classList.add("active");
  }

  function closeModal() {
    taskModal.classList.remove("active");
    taskForm.reset();
    taskIdField.value = "";
  }

  addTaskBtn.addEventListener("click", function () { openModal(null); });
  cancelModalBtn.addEventListener("click", closeModal);
  taskModal.addEventListener("click", function (e) {
    if (e.target === taskModal) closeModal();
  });

  // Save task
  taskForm.addEventListener("submit", function (e) {
    e.preventDefault();

    var id = taskIdField.value;
    var title = taskTitleField.value.trim();
    var description = taskDescField.value.trim();
    var deadline = taskDeadlineField.value;
    var priority = taskPriorityField.value;
    if (!title || !deadline) return;

    var allTasks = getAllTasks();

    if (id) {
      allTasks = allTasks.map(function (t) {
        if (t.id === id) {
          return {
            id: t.id, userId: t.userId, title: title, description: description,
            deadline: deadline, priority: priority, completed: t.completed,
            createdAt: t.createdAt, updatedAt: new Date().toISOString()
          };
        }
        return t;
      });
      showToast("success", "Task Updated", '"' + title + '" has been updated.');
    } else {
      allTasks.push({
        id: generateId(), userId: session.id, title: title, description: description,
        deadline: deadline, priority: priority, completed: false,
        createdAt: new Date().toISOString(), updatedAt: new Date().toISOString()
      });
      showToast("success", "Task Created", '"' + title + '" has been added.');
    }

    saveAllTasks(allTasks);
    closeModal();
    renderTasks();
  });

  // Task actions (delegated)
  tasksGrid.addEventListener("click", function (e) {
    var btn = e.target.closest("[data-action]");
    if (!btn) return;
    var action = btn.getAttribute("data-action");
    var id = btn.getAttribute("data-id");

    if (action === "edit") {
      var task = getAllTasks().find(function (t) { return t.id === id; });
      if (task) openModal(task);
    } else if (action === "delete") {
      deleteTargetId = id;
      confirmOverlay.classList.add("active");
    } else if (action === "toggle") {
      var allTasks = getAllTasks();
      allTasks = allTasks.map(function (t) {
        if (t.id === id) { t.completed = !t.completed; t.updatedAt = new Date().toISOString(); }
        return t;
      });
      saveAllTasks(allTasks);
      renderTasks();
      var task = allTasks.find(function (t) { return t.id === id; });
      if (task && task.completed) showToast("success", "Task Completed", '"' + task.title + '" is done!');
    }
  });

  // Confirm delete
  confirmCancelBtn.addEventListener("click", function () {
    confirmOverlay.classList.remove("active");
    deleteTargetId = null;
  });
  confirmOverlay.addEventListener("click", function (e) {
    if (e.target === confirmOverlay) {
      confirmOverlay.classList.remove("active");
      deleteTargetId = null;
    }
  });
  confirmDeleteBtn.addEventListener("click", function () {
    if (!deleteTargetId) return;
    var allTasks = getAllTasks().filter(function (t) { return t.id !== deleteTargetId; });
    saveAllTasks(allTasks);
    delete alertedTasks[deleteTargetId];
    localStorage.setItem("taskflow_alerted", JSON.stringify(alertedTasks));
    showToast("info", "Task Deleted", "The task has been removed.");
    confirmOverlay.classList.remove("active");
    deleteTargetId = null;
    renderTasks();
  });

  // Keyboard shortcuts
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

  // Initial render
  renderTasks();
  startCountdownTimer();
})();
