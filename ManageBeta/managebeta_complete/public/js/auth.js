(function () {
  "use strict";

  // ============================================================
  // public/js/auth.js — Login & Registration Logic
  // ============================================================
  // This file handles the login and register forms.
  // Instead of saving data to localStorage like before,
  // it sends the form data to the PHP backend using fetch().
  // PHP validates it, saves to the database, and sets a session.
  // ============================================================


  // -------------------------------------------------------
  // Helper: show an error message under a specific input field.
  // id      = the ID of the error <div> element
  // message = the text to show
  // -------------------------------------------------------
  function showError(id, message) {
    var el = document.getElementById(id);
    if (el) {
      el.textContent = message;
      el.classList.add("visible"); // CSS makes it visible when this class is added
    }
  }

  // -------------------------------------------------------
  // Helper: clear ALL error messages on the page.
  // Called at the start of every form submission.
  // -------------------------------------------------------
  function clearErrors() {
    document.querySelectorAll(".error-msg").forEach(function (el) {
      el.textContent = "";
      el.classList.remove("visible");
    });
  }


  // -------------------------------------------------------
  // Toggle between the Login and Register cards.
  // Both cards are in the same page — we just show/hide them.
  // -------------------------------------------------------
  var loginCard    = document.getElementById("loginCard");
  var registerCard = document.getElementById("registerCard");

  function showLoginCard() {
    clearErrors();
    registerCard.style.display = "none";
    loginCard.style.display    = "block";
  }

  function showRegisterCard() {
    clearErrors();
    loginCard.style.display    = "none";
    registerCard.style.display = "block";
  }

  // "Create one" link inside the login card
  document.getElementById("showRegister").addEventListener("click", function (e) {
    e.preventDefault();
    showRegisterCard();
  });

  // "Register" link in the navbar
  document.getElementById("navRegister").addEventListener("click", function (e) {
    e.preventDefault();
    showRegisterCard();
  });

  // "Sign in" link inside the register card
  document.getElementById("showLogin").addEventListener("click", function (e) {
    e.preventDefault();
    showLoginCard();
  });

  // If the URL has #register in it (e.g. someone clicked a link),
  // show the register card straight away.
  if (window.location.hash === "#register") {
    showRegisterCard();
  }


  // -------------------------------------------------------
  // REGISTER FORM
  // -------------------------------------------------------
  document.getElementById("registerForm").addEventListener("submit", function (e) {
    e.preventDefault(); // Stop the form from doing a normal browser submit
    clearErrors();

    // Read the values from each input field
    var name     = document.getElementById("regName").value.trim();
    var email    = document.getElementById("regEmail").value.trim();
    var password = document.getElementById("regPassword").value;
    var confirm  = document.getElementById("regConfirm").value;

    // --- Client-side validation (quick checks before hitting the server) ---
    var valid = true;

    if (name.length < 2) {
      showError("regNameError", "Name must be at least 2 characters.");
      valid = false;
    }
    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
      showError("regEmailError", "Please enter a valid email address.");
      valid = false;
    }
    if (password.length < 6) {
      showError("regPasswordError", "Password must be at least 6 characters.");
      valid = false;
    }
    if (password !== confirm) {
      showError("regConfirmError", "Passwords do not match.");
      valid = false;
    }

    // If any validation failed, stop here — don't send to the server.
    if (!valid) return;

    // --- Send data to PHP backend ---
    // fetch() sends an HTTP request and returns a Promise.
    fetch("/api/auth/register", {
      method:  "POST",
      headers: { "Content-Type": "application/json" },
      // JSON.stringify() converts our JS object to a JSON string for the request body
      body: JSON.stringify({ name: name, email: email, password: password, confirm: confirm })
    })
    .then(function (response) {
      // Parse the JSON that PHP sent back
      return response.json();
    })
    .then(function (result) {
      if (result.success) {
        // PHP created the account and started a session — go to the dashboard
        window.location.href = result.redirect;
      } else {
        // PHP returned an error — show it on the right field
        if (result.field === "name")     showError("regNameError",     result.message);
        if (result.field === "email")    showError("regEmailError",    result.message);
        if (result.field === "password") showError("regPasswordError", result.message);
        if (result.field === "confirm")  showError("regConfirmError",  result.message);
        if (result.field === "general")  showError("regGeneralError",  result.message);
      }
    })
    .catch(function () {
      // Network error or PHP crashed — show a generic message
      showError("regGeneralError", "Something went wrong. Please try again.");
    });
  });


  // -------------------------------------------------------
  // LOGIN FORM
  // -------------------------------------------------------
  document.getElementById("loginForm").addEventListener("submit", function (e) {
    e.preventDefault();
    clearErrors();

    var email    = document.getElementById("loginEmail").value.trim();
    var password = document.getElementById("loginPassword").value;

    // Quick checks before hitting the server
    if (!email)    { showError("loginEmailError",    "Email is required.");    return; }
    if (!password) { showError("loginPasswordError", "Password is required."); return; }

    // Send login credentials to PHP
    fetch("/api/auth/login", {
      method:  "POST",
      headers: { "Content-Type": "application/json" },
      body: JSON.stringify({ email: email, password: password })
    })
    .then(function (response) {
      return response.json();
    })
    .then(function (result) {
      if (result.success) {
        // PHP verified the credentials and started a session
        window.location.href = result.redirect;
      } else {
        if (result.field === "email")    showError("loginEmailError",    result.message);
        if (result.field === "password") showError("loginPasswordError", result.message);
        if (result.field === "general")  showError("loginGeneralError",  result.message);
      }
    })
    .catch(function () {
      showError("loginGeneralError", "Something went wrong. Please try again.");
    });
  });


  // -------------------------------------------------------
  // Hamburger menu (mobile navigation toggle)
  // -------------------------------------------------------
  var hamburger = document.getElementById("hamburger");
  var navLinks  = document.getElementById("navLinks");

  hamburger.addEventListener("click", function () {
    navLinks.classList.toggle("open");
  });

  // Close the menu when clicking anywhere else on the page
  document.addEventListener("click", function (e) {
    if (!hamburger.contains(e.target) && !navLinks.contains(e.target)) {
      navLinks.classList.remove("open");
    }
  });

})();
