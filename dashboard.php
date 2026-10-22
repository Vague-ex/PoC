<?php
/* Figure 3 of the original paper, kept intact, with the v2 preservation and
   discoverability indicators added alongside the original analytics. */
require_once __DIR__ . '/config.php';
require_role('admin', 'faculty');

$s = q("SELECT COUNT(*) AS papers,
               SUM(status = 'pending') AS pending,
               SUM(views) AS views, SUM(downloads) AS downloads, SUM(likes) AS likes,
               SUM(status = 'approved') AS discoverable,
               SUM(access_level = 'withheld' AND status = 'approved') AS withheld,
               COUNT(DISTINCT campus) AS campuses
        FROM papers")->fetch();
$users = q('SELECT COUNT(*) AS n FROM users')->fetch()['n'];

$byCategory = q("SELECT category, SUM(views) AS v FROM papers GROUP BY category ORDER BY v DESC")->fetchAll();
$maxCat = max(1, (int)($byCategory[0]['v'] ?? 1));

$top = fn(string $col) => q("SELECT id, title, $col AS n FROM papers WHERE status = 'approved' ORDER BY $col DESC LIMIT 4")->fetchAll();
$searched = q('SELECT term, COUNT(*) AS n FROM searches GROUP BY term ORDER BY n DESC LIMIT 4')->fetchAll();

$lastBackup = q('SELECT * FROM backups ORDER BY created_at DESC LIMIT 1')->fetch();

$title = 'Dashboard';
include __DIR__ . '/partials/header.php';
?>
<h1 style="font-size:23px;margin:0 0 4px">Welcome, <?= e(user()['name']) ?></h1>
<p class="muted small" style="margin-top:0">
  <?= e(ucfirst(user()['role'])) ?> &middot; <?= e(user()['college']) ?> &middot; <?= e(user()['campus']) ?> Campus
</p>

<?php if ((int)$s['pending']): ?>
  <div class="flash warn">
    <strong><?= (int)$s['pending'] ?></strong> submission<?= $s['pending'] == 1 ? '' : 's' ?>
    awaiting approval. <a href="publications.php">Open the publications queue</a>.
  </div>
<?php endif; ?>

<div class="card">
  <h2>Most Viewed Categories</h2>
  <div class="body">
    <?php foreach ($byCategory as $c): ?>
      <div style="display:flex;align-items:center;gap:12px;margin-bottom:9px">
        <span class="small" style="width:120px;text-align:right"><?= e($c['category']) ?></span>
        <span class="bar" style="flex:1"><span style="width:<?= round($c['v'] / $maxCat * 100) ?>%"></span></span>
        <span class="small muted" style="width:50px"><?= (int)$c['v'] ?></span>
      </div>
    <?php endforeach; ?>
  </div>
</div>

<div class="grid cols-5" style="margin-bottom:18px">
  <?php foreach ([['Papers', $s['papers']], ['Users', $users], ['Downloads', $s['downloads']],
                  ['Views', $s['views']], ['Likes', $s['likes']]] as $st): ?>
    <div class="stat"><div class="label"><?= e($st[0]) ?></div><div class="value"><?= (int)$st[1] ?></div></div>
  <?php endforeach; ?>
</div>

<div class="card">
  <h2>v2 Enhancement Indicators</h2>
  <div class="body grid cols-3">
    <div class="stat">
      <div class="label">Publicly discoverable records</div>
      <div class="value"><?= (int)$s['discoverable'] ?></div>
      <div class="small muted">
        every approved description, <?= (int)$s['withheld'] ?> of them with the document withheld.
        Baseline under v1: 0 &mdash; an account was required to view anything</div>
    </div>
    <div class="stat">
      <div class="label">Campuses represented</div>
      <div class="value"><?= (int)$s['campuses'] ?></div>
      <div class="small muted">v1 recorded no campus or college of origin</div>
    </div>
    <div class="stat">
      <div class="label">Last verified backup</div>
      <div class="value" style="font-size:17px">
        <?= $lastBackup ? e(date('d M Y H:i', strtotime($lastBackup['created_at']))) : 'none yet' ?>
      </div>
      <div class="small muted">
        <?php if ($lastBackup): ?>
          <?= e($lastBackup['status']) ?> &middot; <a href="backup.php">backup log</a>
        <?php else: ?>
          <a href="backup.php">run the first backup</a>
        <?php endif; ?>
      </div>
    </div>
  </div>
</div>

<div class="grid cols-4">
  <?php
  $panels = [
    ['Most Downloaded', $top('downloads')],
    ['Most Viewed',     $top('views')],
    ['Most Liked',      $top('likes')],
  ];
  foreach ($panels as [$label, $rows]): ?>
    <div class="card"><h3><?= e($label) ?></h3><div class="body">
      <?php foreach ($rows as $r): ?>
        <div class="small" style="margin-bottom:7px">
          <a href="record.php?id=<?= (int)$r['id'] ?>"><?= e(mb_strimwidth($r['title'], 0, 40, '...')) ?></a>
          <span class="muted">(<?= (int)$r['n'] ?>)</span>
        </div>
      <?php endforeach; ?>
    </div></div>
  <?php endforeach; ?>
  <div class="card"><h3>Most Searched</h3><div class="body">
    <?php foreach ($searched as $r): ?>
      <div class="small" style="margin-bottom:7px">
        <a href="search.php?q=<?= urlencode($r['term']) ?>"><?= e($r['term']) ?></a>
        <span class="muted">(<?= (int)$r['n'] ?>)</span>
      </div>
    <?php endforeach; ?>
  </div></div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
