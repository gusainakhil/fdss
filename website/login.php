<?php
session_start();
require_once __DIR__ . '/../includes/login_auth.php';

$login_error = fdss_process_login($conn, '../');
$submitted_username = htmlspecialchars($_POST['username'] ?? '', ENT_QUOTES, 'UTF-8');
$role_label = htmlspecialchars($_GET['role'] ?? 'Unknown', ENT_QUOTES, 'UTF-8');
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width,initial-scale=1">
  <title>Login | FDSS / FSDS</title>
  <link rel="stylesheet" href="css/styles.css">
</head>
<body>
<header class="nav">
  <div class="container nav-inner">
    <a class="brand" href="index.html"><img src="assets/beatle-logo.png" alt="Beatle Analytics"></a>
    <button class="menu-btn" aria-label="Open navigation" aria-expanded="false">☰</button>
    <nav class="nav-links">
      <a href="index.html">Home</a>
      <a href="about.html">About</a>
      <a href="how-it-works.html">How It Works</a>
      <a href="modules.html">Modules</a>
      <a href="contact.php">Contact</a>
      <a class="active" href="login.php">Login</a>
    </nav>
  </div>
</header>

<main class="login-page">
  <div class="login-layout">
    <div class="login-art">
      <div class="eyebrow">FDSS / FSDS SECURE ACCESS</div>
      <h1 class="section-title">Connected for Safety.<br>Committed to Protection.</h1>
      <p class="section-copy">Secure access for railway administrators, auditors and authorised operational users.</p>
      <img src="assets/hero-equipment.png" alt="FDSS fire safety equipment">
    </div>

    <form class="login-card" method="POST" action="login.php">
      <h1>Welcome Back!</h1>
      <p class="muted">Login to your FDSS / FSDS account</p>

      <?php if ($login_error !== ''): ?>
        <div class="form-alert error"><?php echo htmlspecialchars($login_error, ENT_QUOTES, 'UTF-8'); ?></div>
      <?php endif; ?>

      <?php if (isset($_GET['logout']) && $_GET['logout'] === 'success'): ?>
        <div class="form-alert success">You have been successfully logged out.</div>
      <?php endif; ?>

      <?php if (isset($_GET['access']) && $_GET['access'] === 'denied'): ?>
        <div class="form-alert error">Your role (<?php echo $role_label; ?>) does not have permission to access this dashboard.</div>
      <?php endif; ?>

      <div class="field">
        <label for="username">Username</label>
        <input id="username" name="username" type="text" placeholder="Enter your username" value="<?php echo $submitted_username; ?>" required>
      </div>

      <div class="field">
        <label for="password">Password</label>
        <input id="password" name="password" type="password" placeholder="Enter your password" required>
      </div>

      <div class="login-links">
        <label><input type="checkbox" name="rememberMe"> Remember Me</label>
        <a href="contact.php">Need Help?</a>
      </div>

      <button class="btn btn-primary" type="submit">Login</button>
      <button class="btn" type="button" style="margin-top:12px">Login with Railway SSO</button>
      <p class="muted" style="margin-top:18px">Secure. Reliable. Always.</p>
    </form>
  </div>
</main>

<script src="js/main.js"></script>
</body>
</html>
<?php
$conn->close();
?>
