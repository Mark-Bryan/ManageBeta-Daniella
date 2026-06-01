(function () {
  "use strict";

  function getUsers() {
    return JSON.parse(localStorage.getItem("taskflow_users") || "[]");
  }

  function saveUsers(users) {
    localStorage.setItem("taskflow_users", JSON.stringify(users));
  }

  function getSession() {
    return JSON.parse(localStorage.getItem("taskflow_session") || "null");
  }

  function saveSession(user) {
    localStorage.setItem("taskflow_session", JSON.stringify(user));
  }

  function showError(id, msg) {
    var el = document.getElementById(id);
    if (el) {
      el.textContent = msg;
      el.classList.add("visible");
    }
  }

  function clearErrors() {
    document.querySelectorAll(".error-msg").forEach(function (el) {
      el.textContent = "";
      el.classList.remove("visible");
    });
  }

  // Redirect if already logged in
  if (getSession()) {
    window.location.href = "dashboard.html";
    return;
  }

  // Toggle Login / Register
  var loginCard = document.getElementById("loginCard");
  var registerCard = document.getElementById("registerCard");
  var showRegisterLinks = document.querySelectorAll("#showRegister, #navRegister");
  var showLoginLink = document.getElementById("showLogin");

  function updateNavActive(type) {
    document.querySelectorAll(".navbar-links a").forEach(function (a) {
      a.classList.remove("active");
    });
    if (type === "register") {
      document.getElementById("navRegister").classList.add("active");
    } else {
      document.querySelector('.navbar-links a[href="index.html"]').classList.add("active");
    }
  }

  showRegisterLinks.forEach(function (link) {
    link.addEventListener("click", function (e) {
      e.preventDefault();
      clearErrors();
      loginCard.style.display = "none";
      registerCard.style.display = "block";
      updateNavActive("register");
    });
  });

  showLoginLink.addEventListener("click", function (e) {
    e.preventDefault();
    clearErrors();
    registerCard.style.display = "none";
    loginCard.style.display = "block";
    updateNavActive("login");
  });

  // Handle hash for direct linking
  if (window.location.hash === "#register") {
    loginCard.style.display = "none";
    registerCard.style.display = "block";
    updateNavActive("register");
  }

  // Registration
  document.getElementById("registerForm").addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var name = document.getElementById("regName").value.trim();
    var email = document.getElementById("regEmail").value.trim().toLowerCase();
    var password = document.getElementById("regPassword").value;
    var confirm = document.getElementById("regConfirm").value;
    var valid = true;

    if (name.length < 2) {
      showError("regNameError", "Name must be at least 2 characters");
      valid = false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError("regEmailError", "Please enter a valid email");
      valid = false;
    }
    if (password.length < 6) {
      showError("regPasswordError", "Password must be at least 6 characters");
      valid = false;
    }
    if (password !== confirm) {
      showError("regConfirmError", "Passwords do not match");
      valid = false;
    }
    if (!valid) return;

    var users = getUsers();
    if (users.some(function (u) { return u.email === email; })) {
      showError("regEmailError", "An account with this email already exists");
      return;
    }

    var newUser = {
      id: Date.now().toString(36) + Math.random().toString(36).substr(2, 5),
      name: name,
      email: email,
      password: password,
      createdAt: new Date().toISOString()
    };

    users.push(newUser);
    saveUsers(users);
    saveSession({ id: newUser.id, name: newUser.name, email: newUser.email });
    window.location.href = "dashboard.html";
  });

  // Login
  document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var email = document.getElementById("loginEmail").value.trim().toLowerCase();
    var password = document.getElementById("loginPassword").value;
    var valid = true;

    if (!email) { showError("loginEmailError", "Email is required"); valid = false; }
    if (!password) { showError("loginPasswordError", "Password is required"); valid = false; }
    if (!valid) return;

    var user = getUsers().find(function (u) { return u.email === email && u.password === password; });
    if (!user) { showError("loginGeneralError", "Invalid email or password"); return; }

    saveSession({ id: user.id, name: user.name, email: user.email });
    window.location.href = "dashboard.html";
  });

  // Hamburger menu
  var hamburger = document.getElementById("hamburger");
  var navLinks = document.getElementById("navLinks");

  hamburger.addEventListener("click", function () {
    navLinks.classList.toggle("open");
  });

  document.addEventListener("click", function (e) {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove("open");
    }
  });
})();
