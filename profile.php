<?php
/* Figure 5 of the original paper, with the campus and college that v2 records. */
require_once __DIR__ . '/config.php';
require_login();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    q('UPDATE users SET name = ?, campus = ?, college = ? WHERE id = ?',
      [trim($_POST['name']), $_POST['campus'], $_POST['college'], user()['id']]);
    $_SESSION['user'] = q('SELECT id,name,email,role,campus,college FROM users WHERE id = ?',
                          [user()['id']])->fetch();
    log_action('updated their profile');
    flash('Profile updated.');
    header('Location: profile.php');
    exit;
}

$u = user();
$mine = q('SELECT COUNT(*) AS n FROM papers WHERE submitted_by = ?', [$u['id']])->fetch()['n'];

$title = 'My Profile';
include __DIR__ . '/partials/header.php';
?>
<div class="card" style="max-width:620px;margin:0 auto">
  <h2>My Profile</h2>
  <div class="body">
    <div style="display:flex;gap:16px;align-items:center;margin-bottom:18px">
      <span class="avatar" style="width:60px;height:60px;font-size:24px"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span>
      <div>
        <div style="font-size:17px;font-weight:600"><?= e($u['name']) ?></div>
        <div class="muted small"><?= e($u['email']) ?> &middot; <?= e($u['role']) ?>
          &middot; <?= (int)$mine ?> deposit<?= $mine == 1 ? '' : 's' ?></div>
      </div>
    </div>
    <form method="post">
      <div class="field"><label>Name</label><input type="text" name="name" value="<?= e($u['name']) ?>" required></div>
      <div class="field"><label>Campus</label>
        <select name="campus"><?php foreach (CAMPUSES as $c): ?>
          <option <?= $u['campus'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <div class="field"><label>College</label>
        <select name="college"><?php foreach (COLLEGES as $c): ?>
          <option <?= $u['college'] === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
      <button class="btn">Save changes</button>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
