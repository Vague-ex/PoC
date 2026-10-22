<?php
/* Public landing page - Figure 2 of the original paper, with the v2 public
   holdings counter and a link into the filtered search. */
require_once __DIR__ . '/config.php';

/* Every approved description is public and listed in the sitemap, withheld ones
   included - the access level governs the document, not the description. */
$stats = q("SELECT
    COUNT(*) AS total,
    SUM(access_level <> 'withheld') AS releasable,
    COUNT(DISTINCT campus) AS campuses
  FROM papers WHERE status = 'approved'")->fetch();

$recent = q("SELECT id, title, authors, year_published, campus, access_level
             FROM papers WHERE status = 'approved' ORDER BY created_at DESC LIMIT 5")->fetchAll();

$title = 'Home';
include __DIR__ . '/partials/header.php';
?>
<div class="hero">
  <span class="logo logo-lg"><span class="a">AS</span><span class="b">SET</span>
    <span class="sub">CAGAYAN STATE UNIVERSITY &ndash; RESEARCH ARCHIVE</span></span>
  <form action="search.php" method="get">
    <input type="text" name="q" placeholder="Search titles, authors, abstracts or keywords">
    <button class="btn">Search</button>
  </form>
  <p class="muted small" style="margin-top:14px">
    <?= (int)$stats['total'] ?> approved holdings, all publicly described &middot;
    <?= (int)$stats['releasable'] ?> with the full document available on sign-in &middot;
    <?= (int)$stats['campuses'] ?> campuses &middot;
    <a href="search.php">browse with filters</a> &middot;
    <a href="sitemap.php">sitemap</a>
  </p>
</div>

<div class="card">
  <h2>Recently Deposited</h2>
  <table>
    <tbody>
    <?php foreach ($recent as $p): ?>
      <tr>
        <td><a href="record.php?id=<?= (int)$p['id'] ?>"><?= e($p['title']) ?></a>
            <div class="muted small"><?= e($p['authors']) ?></div></td>
        <td class="muted small" style="width:150px"><?= e($p['campus']) ?> Campus</td>
        <td style="width:110px"><?= (int)$p['year_published'] ?></td>
        <td style="width:100px"><span class="tag tag-<?= e($p['access_level']) ?>"><?= e($p['access_level']) ?></span></td>
      </tr>
    <?php endforeach; ?>
    </tbody>
  </table>
</div>

<p class="muted small">
  Record descriptions on this archive are readable without an account.
  Full documents require an institutional sign-in.
</p>
<?php include __DIR__ . '/partials/footer.php'; ?>
