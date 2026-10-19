<?php
require_once __DIR__ . '/config.php';

$next = $_GET['next'] ?? 'dashboard.php';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $row = q('SELECT * FROM users WHERE email = ?', [trim($_POST['email'] ?? '')])->fetch();
    if ($row && password_verify($_POST['password'] ?? '', $row['password'])) {
        unset($row['password']);
        $_SESSION['user'] = $row;
        log_action('logged in');
        header('Location: ' . ($_POST['next'] ?: 'dashboard.php'));
        exit;
    }
    $error = 'Those credentials were not recognised.';
}

$title = 'Login';
include __DIR__ . '/partials/header.php';
?>
<div style="max-width:380px;margin:40px auto">
  <div class="card">
    <h2>Sign in</h2>
    <div class="body">
      <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <input type="hidden" name="next" value="<?= e($next) ?>">
        <div class="field"><label>Email</label><input type="email" name="email" required autofocus></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <button class="btn" style="width:100%">Login</button>
      </form>
      <p class="small muted" style="margin-bottom:0">
        Demo accounts, password <code>password</code>:<br>
        admin@csu.edu.ph &middot; faculty@csu.edu.ph &middot; student@csu.edu.ph
      </p>
    </div>
  </div>
  <p class="small muted" style="text-align:center">No account? <a href="register.php">Register</a></p>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
