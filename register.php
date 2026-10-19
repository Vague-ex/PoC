<?php
/* Registration captures campus and college at account level - part of Objective 4
   (record origin tracking), since a deposit inherits its depositor's origin. */
require_once __DIR__ . '/config.php';

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name  = trim($_POST['name'] ?? '');
    $email = trim($_POST['email'] ?? '');
    $pass  = $_POST['password'] ?? '';
    if ($name === '' || $email === '' || strlen($pass) < 6) {
        $error = 'Supply a name, an email and a password of at least six characters.';
    } elseif (q('SELECT id FROM users WHERE email = ?', [$email])->fetch()) {
        $error = 'That email is already registered.';
    } else {
        q('INSERT INTO users (name,email,password,role,campus,college) VALUES (?,?,?,?,?,?)',
          [$name, $email, password_hash($pass, PASSWORD_DEFAULT), 'student',
           $_POST['campus'] ?? 'Aparri', $_POST['college'] ?? COLLEGES[0]]);
        flash('Account created. You may now sign in.');
        header('Location: login.php');
        exit;
    }
}

$title = 'Register';
include __DIR__ . '/partials/header.php';
?>
<div style="max-width:430px;margin:30px auto">
  <div class="card">
    <h2>Create an account</h2>
    <div class="body">
      <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>
      <form method="post">
        <div class="field"><label>Full name</label><input type="text" name="name" required></div>
        <div class="field"><label>Institutional email</label><input type="email" name="email" required></div>
        <div class="field"><label>Password</label><input type="password" name="password" required></div>
        <div class="field"><label>Campus</label>
          <select name="campus"><?php foreach (CAMPUSES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select>
        </div>
        <div class="field"><label>College</label>
          <select name="college"><?php foreach (COLLEGES as $c): ?><option><?= e($c) ?></option><?php endforeach; ?></select>
        </div>
        <button class="btn" style="width:100%">Register</button>
      </form>
    </div>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
