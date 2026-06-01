<!doctype html>
<html lang="en">
  <head>
    <meta charset="UTF-8" />
    <meta name="viewport" content="width=device-width, initial-scale=1.0" />
    <title>ManageBeta - Login</title>
    <link rel="stylesheet" href="/public/css/style.css" />
  </head>
  <body>

    <nav class="navbar">
      <a href="/" class="navbar-brand">
        <span class="brand-icon">T</span>
        ManageBeta
      </a>
      <button class="hamburger" id="hamburger" aria-label="Menu">
        <span></span><span></span><span></span>
      </button>
      <ul class="navbar-links" id="navLinks">
        <li><a href="/" class="active" id="navLogin">Login</a></li>
        <li><a href="#register" id="navRegister">Register</a></li>
      </ul>
    </nav>

    <main>
      <div class="auth-container">

        <!-- ===================== LOGIN CARD ===================== -->
        <div class="auth-card" id="loginCard">
          <h2>Welcome Back</h2>
          <p class="subtitle">Sign in to manage your tasks</p>

          <form id="loginForm">
            <div class="form-group">
              <label for="loginEmail">Email</label>
              <input type="email" id="loginEmail" placeholder="you@example.com" required />
              <div class="error-msg" id="loginEmailError"></div>
            </div>

            <div class="form-group">
              <label for="loginPassword">Password</label>
              <input type="password" id="loginPassword" placeholder="Enter your password" required />
              <div class="error-msg" id="loginPasswordError"></div>
            </div>

            <!-- General error shown below all fields (e.g. "Invalid email or password") -->
            <div class="error-msg" id="loginGeneralError"></div>

            <button type="submit" class="btn-primary">Sign In</button>
          </form>

          <p class="auth-footer">
            Don't have an account? <a href="#register" id="showRegister">Create one</a>
          </p>
        </div>

        <!-- ==================== REGISTER CARD ==================== -->
        <!-- Hidden by default — toggled by the links above -->
        <div class="auth-card" id="registerCard" style="display:none;">
          <h2>Create Account</h2>
          <p class="subtitle">Start organising your tasks today</p>

          <form id="registerForm">
            <div class="form-group">
              <label for="regName">Full Name</label>
              <input type="text" id="regName" placeholder="John Doe" required />
              <div class="error-msg" id="regNameError"></div>
            </div>

            <div class="form-group">
              <label for="regEmail">Email</label>
              <input type="email" id="regEmail" placeholder="you@example.com" required />
              <div class="error-msg" id="regEmailError"></div>
            </div>

            <div class="form-group">
              <label for="regPassword">Password</label>
              <input type="password" id="regPassword" placeholder="Min 6 characters" required />
              <div class="error-msg" id="regPasswordError"></div>
            </div>

            <div class="form-group">
              <label for="regConfirm">Confirm Password</label>
              <input type="password" id="regConfirm" placeholder="Repeat your password" required />
              <div class="error-msg" id="regConfirmError"></div>
            </div>

            <div class="error-msg" id="regGeneralError"></div>

            <button type="submit" class="btn-primary">Create Account</button>
          </form>

          <p class="auth-footer">
            Already have an account? <a href="#login" id="showLogin">Sign in</a>
          </p>
        </div>

      </div>
    </main>

    <footer class="footer">
      <p>ManageBeta &copy; 2026 &mdash; Built with love, by Daniella.</p>
    </footer>

    <script src="/public/js/auth.js"></script>
  </body>
</html>
