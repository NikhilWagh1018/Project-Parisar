<?php
session_start();
if (isset($_SESSION['user_id'])) {
    header("Location: ../pages/dashboard.php");
    exit;
}

require_once "../config/db.php";

$error = "";

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $email    = trim($_POST['email'] ?? '');
    $password = $_POST['password'] ?? '';

    if (empty($email) || empty($password)) {
        $error = "Please fill in all fields.";
    } else {
        $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? LIMIT 1");
        $stmt->execute([$email]);
        $user = $stmt->fetch(PDO::FETCH_ASSOC);

        if ($user && password_verify($password, $user['password'])) {
            $_SESSION['user_id']   = $user['id'];
            $_SESSION['user_name'] = $user['name'];
            $_SESSION['user_email']= $user['email'];
            header("Location: ../pages/dashboard.php");
            exit;
        } else {
            $error = "Invalid email or password. Please try again.";
        }
    }
}
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Login — CycleAudit</title>
  <link rel="stylesheet" href="../css/auth.css">
</head>
<body>

<!-- LEFT PANEL -->
<div class="left-panel">
  <div class="brand">
    <div class="brand-mark">
      <svg viewBox="0 0 24 24"><path d="M12 2C8.5 2 6 5 6 8.5c0 4.5 6 11.5 6 11.5s6-7 6-11.5C18 5 15.5 2 12 2zm0 9.5a3 3 0 1 1 0-6 3 3 0 0 1 0 6z"/></svg>
    </div>
    <span class="brand-name">CycleAudit</span>
  </div>

  <h2 class="left-headline">Welcome back,<br><span class="hi">Surveyor.</span></h2>
  <p class="left-sub">Log in to access your audit dashboard and continue mapping Pune's cycle track network.</p>

  <div class="feature-pills">
    <div class="pill">
      <div class="pill-icon">🗺️</div>
      <div class="pill-text">
        <strong>Segment Mapping</strong>
        <span>Define and audit road segments precisely</span>
      </div>
    </div>
    <div class="pill">
      <div class="pill-icon">📊</div>
      <div class="pill-text">
        <strong>Live Scoring</strong>
        <span>Safety, Continuity & Comfort scores</span>
      </div>
    </div>
    <div class="pill">
      <div class="pill-icon">📄</div>
      <div class="pill-text">
        <strong>PDF Reports</strong>
        <span>Export professional audit reports</span>
      </div>
    </div>
  </div>
</div>

<!-- RIGHT PANEL -->
<div class="right-panel">
  <div class="form-box">
    <h1 class="form-title">Sign In</h1>
    <p class="form-subtitle">Don't have an account? <a href="register.php">Register here</a></p>

    <?php if ($error): ?>
    <div class="alert alert-error">⚠️ <?= htmlspecialchars($error) ?></div>
    <?php endif; ?>

    <form method="POST" action="" novalidate>
      <div class="form-group">
        <label for="email">Email Address</label>
        <div class="input-icon-wrap">
          <span class="icon">✉️</span>
          <input type="email" id="email" name="email"
                 value="<?= htmlspecialchars($_POST['email'] ?? '') ?>"
                 placeholder="you@example.com" autocomplete="email">
        </div>
      </div>

      <div class="form-group">
        <label for="password">Password</label>
        <div class="input-icon-wrap">
          <span class="icon">🔒</span>
          <input type="password" id="password" name="password"
                 placeholder="Enter your password" autocomplete="current-password">
          <button type="button" class="toggle-pass" onclick="togglePass('password', this)">Show</button>
        </div>
      </div>

      <button type="submit" class="btn-submit">
        Sign In
        <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="13 17 18 12 13 7"/><path d="M6 12h12"/></svg>
      </button>
    </form>

    <div class="back-link">
      <a href="../index.html">← Back to Home</a>
    </div>
  </div>
</div>

<script>
function togglePass(id, btn) {
  const inp = document.getElementById(id);
  inp.type = inp.type === 'password' ? 'text' : 'password';
  btn.textContent = inp.type === 'password' ? 'Show' : 'Hide';
}
</script>
</body>
</html>
