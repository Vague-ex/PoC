<?php
/* Shared chrome. $title and optional $head are set by the including page. */
$u = user();
?><!doctype html>
<html lang="en">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<title><?= e($title ?? 'ASSET') ?> &middot; ASSET v2</title>
<link rel="stylesheet" href="assets/style.css?v=<?= @filemtime(__DIR__ . '/../assets/style.css') ?>">
<?= $head ?? '' ?>
</head>
<body>
<nav class="nav">
  <a href="index.php" class="logo"><span class="a">AS</span><span class="b">SET</span><span class="sub">CAGAYAN STATE UNIVERSITY</span></a>
  <form action="search.php" method="get">
    <input type="text" name="q" value="<?= e($_GET['q'] ?? '') ?>" placeholder="Search">
    <button class="btn">Search</button>
  </form>
  <div class="right">
    <?php if (!$u): ?>
      <a href="login.php">LOGIN</a>
      <a href="register.php">REGISTER</a>
    <?php else: ?>
      <details class="menu">
        <summary>
          <span class="avatar"><?= e(strtoupper(substr($u['name'], 0, 1))) ?></span>
          <span><?= e($u['name']) ?> &#9662;</span>
        </summary>
        <div class="drop">
          <a href="profile.php">My Profile</a>
          <a href="search.php">Browse Holdings</a>
          <?php if (is_role('admin', 'faculty')): ?>
            <a href="dashboard.php">Dashboard</a>
            <a href="deposit.php">Submit a Paper</a>
            <a href="publications.php">Publications</a>
            <a href="reports.php">Archive Composition</a>
          <?php endif; ?>
          <?php if (is_role('admin')): ?>
            <a href="backup.php">Backup &amp; Restoration</a>
            <a href="logs.php">System Logs</a>
          <?php endif; ?>
          <hr>
          <a href="logout.php">Logout</a>
        </div>
      </details>
    <?php endif; ?>
  </div>
</nav>
<main class="wrap<?= !empty($wide) ? ' wrap-wide' : '' ?>">
<?php /* Prefixed name: the including page's own variables are still in scope here. */ ?>
<?php if ($__flash = flash()): ?>
  <div class="flash <?= e($__flash[1]) ?>"><?= $__flash[0] ?></div>
<?php endif; ?>
