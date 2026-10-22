<?php
/* Objective 3 - search filtering by year, adviser, college and subject area,
   where ASSET v1 had a search box and no filters at all. Campus comes from Objective 4.
   Public: approved holdings are listed to anyone; only the full document is gated. */
require_once __DIR__ . '/config.php';

$q = trim($_GET['q'] ?? '');
$f = [
  'year'         => $_GET['year'] ?? '',
  'adviser'      => $_GET['adviser'] ?? '',
  'college'      => $_GET['college'] ?? '',
  'subject_area' => $_GET['subject_area'] ?? '',
  'campus'       => $_GET['campus'] ?? '',
  'category'     => $_GET['category'] ?? '',
];

$where = ["status = 'approved'"];
$args  = [];
if ($q !== '') {
    $where[] = '(title LIKE ? OR authors LIKE ? OR co_authors LIKE ? OR abstract LIKE ? OR keywords LIKE ?)';
    array_push($args, ...array_fill(0, 5, '%' . $q . '%'));
    q('INSERT INTO searches (term) VALUES (?)', [$q]);
}
foreach (['year' => 'year_published', 'adviser' => 'adviser', 'college' => 'college',
          'subject_area' => 'subject_area', 'campus' => 'campus', 'category' => 'category'] as $k => $col) {
    if ($f[$k] !== '') { $where[] = "$col = ?"; $args[] = $f[$k]; }
}
$sql = 'FROM papers WHERE ' . implode(' AND ', $where);

$t0 = microtime(true);
$rows = q("SELECT * $sql ORDER BY year_published DESC, title", $args)->fetchAll();
$elapsed = (microtime(true) - $t0) * 1000;

/* Facet values are drawn from the approved holdings themselves, so the controls
   never offer a filter that would return nothing. */
$opt = fn(string $col) => array_column(
    q("SELECT DISTINCT $col AS v FROM papers WHERE status = 'approved' AND $col <> '' ORDER BY $col")->fetchAll(), 'v');

$active = array_filter($f) + ($q !== '' ? ['q' => $q] : []);

$title = 'Search';
$wide = true;
include __DIR__ . '/partials/header.php';
?>
<div style="display:grid;grid-template-columns:265px 1fr;gap:18px;align-items:start">

  <form class="card" method="get" style="position:sticky;top:18px">
    <h2>Refine Results</h2>
    <div class="body">
      <input type="hidden" name="q" value="<?= e($q) ?>">
      <?php
      $facets = [
        'year'         => ['Year published', $opt('year_published')],
        'college'      => ['College',        $opt('college')],
        'subject_area' => ['Subject area',   $opt('subject_area')],
        'adviser'      => ['Adviser',        $opt('adviser')],
        'campus'       => ['Campus of origin', $opt('campus')],
        'category'     => ['Category (v1)',  $opt('category')],
      ];
      foreach ($facets as $name => [$label, $values]): ?>
        <div class="field">
          <label><?= e($label) ?></label>
          <select name="<?= e($name) ?>">
            <option value="">All</option>
            <?php foreach ($values as $v): ?>
              <option value="<?= e($v) ?>" <?= $f[$name] === (string)$v ? 'selected' : '' ?>><?= e($v) ?></option>
            <?php endforeach; ?>
          </select>
        </div>
      <?php endforeach; ?>
      <button class="btn" style="width:100%">Apply filters</button>
      <?php if ($active): ?>
        <a class="btn btn-outline" style="width:100%;text-align:center;margin-top:7px" href="search.php">Clear all</a>
      <?php endif; ?>
    </div>
  </form>

  <div>
    <div class="card">
      <h2>Results</h2>
      <div class="body">
        <p class="small muted" style="margin:0">
          <strong><?= count($rows) ?></strong> holding<?= count($rows) === 1 ? '' : 's' ?>
          <?= $q !== '' ? 'matching &ldquo;' . e($q) . '&rdquo;' : '' ?>
          retrieved in <?= number_format($elapsed, 1) ?> ms
          <?php if ($active): ?>
            &middot; filters:
            <?php foreach ($active as $k => $v): ?><span class="tag"><?= e($v) ?></span> <?php endforeach; ?>
          <?php endif; ?>
        </p>
      </div>
    </div>

    <?php if (!$rows): ?>
      <div class="card"><div class="body muted">No holdings match those criteria.</div></div>
    <?php endif; ?>

    <?php foreach ($rows as $p): ?>
      <div class="card"><div class="body" style="display:flex;gap:14px">
        <div class="doc-icon">PDF</div>
        <div style="flex:1">
          <a href="record.php?id=<?= (int)$p['id'] ?>" style="font-weight:600;font-size:15px"><?= e($p['title']) ?></a>
          <div class="small"><strong>Authors:</strong> <?= e($p['authors'] . ($p['co_authors'] ? ', ' . $p['co_authors'] : '')) ?></div>
          <div class="small"><strong>Adviser:</strong> <?= e($p['adviser']) ?></div>
          <div class="small muted" style="margin-top:5px">
            <?= (int)$p['year_published'] ?> &middot; <?= e($p['subject_area']) ?>
            &middot; <?= e($p['college']) ?> &middot; <?= e($p['campus']) ?> Campus
          </div>
        </div>
        <div style="text-align:right">
          <span class="tag tag-<?= e($p['access_level']) ?>"><?= e($p['access_level']) ?></span>
          <div class="muted small" style="margin-top:6px"><?= (int)$p['views'] ?> views</div>
        </div>
      </div></div>
    <?php endforeach; ?>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
