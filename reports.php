<?php
/* Objective 4 / Outcome 3 - archive composition reporting. Only possible because
   deposit now captures campus, college and subject area. CSV export is what the
   campus research office actually attaches to an accreditation submission. */
require_once __DIR__ . '/config.php';
require_role('admin', 'faculty');

$dims = [
  'college'      => 'College',
  'campus'       => 'Campus of origin',
  'subject_area' => 'Subject area',
  'year_published' => 'Year published',
  'access_level' => 'Access level',
];

$compose = function (string $col): array {
    return q("SELECT $col AS k, COUNT(*) AS n,
                     SUM(access_level <> 'withheld') AS releasable,
                     SUM(downloads) AS downloads
              FROM papers WHERE status = 'approved'
              GROUP BY $col ORDER BY n DESC, k")->fetchAll();
};

if (($_GET['export'] ?? '') !== '') {
    $col = array_key_exists($_GET['export'], $dims) ? $_GET['export'] : 'college';
    log_action('exported the archive composition report by ' . $col);
    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="asset-composition-' . $col . '-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, [$dims[$col], 'Holdings', 'Full document releasable', 'Downloads']);
    foreach ($compose($col) as $r) fputcsv($out, [$r['k'], $r['n'], $r['releasable'], $r['downloads']]);
    exit;
}

$total = (int)q("SELECT COUNT(*) AS n FROM papers WHERE status = 'approved'")->fetch()['n'];

/* Quarterly deposit volume - the cadence the logframe reports on. */
$quarters = q("SELECT CONCAT(YEAR(created_at), ' Q', QUARTER(created_at)) AS k, COUNT(*) AS n
               FROM papers WHERE status = 'approved'
               GROUP BY YEAR(created_at), QUARTER(created_at)
               ORDER BY YEAR(created_at), QUARTER(created_at)")->fetchAll();

$title = 'Archive Composition';
$wide = true;
include __DIR__ . '/partials/header.php';
?>
<h1 style="font-size:22px;margin:0 0 4px">Archive Composition Report</h1>
<p class="muted small" style="margin-top:0">
  <?= $total ?> approved holdings as of <?= date('d F Y') ?>.
</p>

<?php foreach ($dims as $col => $label):
    $rows = $compose($col);
    $max = max(1, (int)($rows[0]['n'] ?? 1)); ?>
  <div class="card">
    <h2><?= e($label) ?>
      <a class="small" style="float:right;text-transform:none;letter-spacing:0;font-weight:400"
         href="?export=<?= e($col) ?>">export CSV</a></h2>
    <table>
      <tr><th><?= e($label) ?></th><th style="width:300px">Share</th>
          <th style="width:90px">Holdings</th><th style="width:110px">Doc. released</th><th style="width:110px">Downloads</th></tr>
      <?php foreach ($rows as $r): ?>
        <tr>
          <td><a href="search.php?<?= e($col === 'year_published' ? 'year' : $col) ?>=<?= urlencode($r['k']) ?>"><?= e($r['k']) ?></a></td>
          <td><span class="bar"><span style="width:<?= round($r['n'] / $max * 100) ?>%"></span></span></td>
          <td><?= (int)$r['n'] ?> <span class="muted small">(<?= round($r['n'] / max(1, $total) * 100) ?>%)</span></td>
          <td><?= (int)$r['releasable'] ?></td>
          <td><?= (int)$r['downloads'] ?></td>
        </tr>
      <?php endforeach; ?>
    </table>
  </div>
<?php endforeach; ?>

<div class="card">
  <h2>Deposit Volume by Quarter</h2>
  <table>
    <tr><th style="width:140px">Quarter</th><th>Deposits</th><th style="width:90px">Count</th></tr>
    <?php $qm = max(1, max(array_column($quarters, 'n') ?: [1]));
    foreach ($quarters as $r): ?>
      <tr><td><?= e($r['k']) ?></td>
          <td><span class="bar"><span style="width:<?= round($r['n'] / $qm * 100) ?>%"></span></span></td>
          <td><?= (int)$r['n'] ?></td></tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
