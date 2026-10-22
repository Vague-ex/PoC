<?php
/* Objective 5 - tiered access. The record description is public and carries
   Dublin Core and schema.org markup so external academic search services can
   index it; the full document stays behind authentication. */
require_once __DIR__ . '/config.php';

$p = q("SELECT * FROM papers WHERE id = ? AND status = 'approved'", [(int)($_GET['id'] ?? 0)])->fetch();
if (!$p) { http_response_code(404); exit('404 - no such holding.'); }

q('UPDATE papers SET views = views + 1 WHERE id = ?', [$p['id']]);

$url = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
     . dirname($_SERVER['SCRIPT_NAME']) . '/record.php?id=' . $p['id'];

/* Dublin Core is what harvesters read. Emitted for every approved record. */
$dc = [
  'DC.title'       => $p['title'],
  'DC.creator'     => $p['authors'] . ($p['co_authors'] ? ', ' . $p['co_authors'] : ''),
  'DC.contributor' => $p['adviser'],
  'DC.subject'     => $p['subject_area'] . ', ' . $p['keywords'],
  'DC.description' => $p['abstract'],
  'DC.publisher'   => 'Cagayan State University - ' . $p['campus'] . ' Campus',
  'DC.date'        => $p['year_published'],
  'DC.type'        => 'Text/Thesis',
  'DC.format'      => 'application/pdf',
  'DC.identifier'  => $url,
  'DC.language'    => 'en',
  'DC.rights'      => $p['access_level'] === 'withheld'
      ? 'Description openly available; full text withheld, requests to the adviser'
      : 'Description openly available; full text to authenticated institutional users',
];
$head = "<link rel=\"canonical\" href=\"" . e($url) . "\">\n";
foreach ($dc as $k => $v) {
    $head .= '<meta name="' . $k . '" content="' . e(mb_substr((string)$v, 0, 500)) . "\">\n";
}
$head .= '<meta name="citation_title" content="' . e($p['title']) . "\">\n";
$head .= '<meta name="citation_publication_date" content="' . (int)$p['year_published'] . "\">\n";
$head .= "<script type=\"application/ld+json\">" . json_encode([
    '@context' => 'https://schema.org', '@type' => 'Thesis',
    'name' => $p['title'], 'author' => $p['authors'], 'datePublished' => (string)$p['year_published'],
    'abstract' => $p['abstract'], 'inLanguage' => 'en', 'url' => $url,
    'publisher' => ['@type' => 'CollegeOrUniversity',
                    'name' => 'Cagayan State University - ' . $p['campus'] . ' Campus'],
    /* The description is free; the document is not. Claiming otherwise would send a
       harvester's users to a sign-in wall, which indexing services penalise. */
    'isAccessibleForFree' => false,
], JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT) . "</script>\n";

$title = $p['title'];
include __DIR__ . '/partials/header.php';
?>
<div class="card">
  <div class="body">
    <span class="tag tag-<?= e($p['access_level']) ?>"><?= e($p['access_level']) ?> record</span>
    <span class="tag"><?= e($p['subject_area']) ?></span>
    <h1 style="font-size:22px;margin:11px 0 6px"><?= e($p['title']) ?></h1>
    <p class="small" style="margin:0">
      <strong>Authors:</strong> <?= e($p['authors'] . ($p['co_authors'] ? ', ' . $p['co_authors'] : '')) ?><br>
      <strong>Adviser:</strong> <?= e($p['adviser']) ?><br>
      <strong>Origin:</strong> <?= e($p['college']) ?>, <?= e($p['campus']) ?> Campus<br>
      <strong>Year published:</strong> <?= (int)$p['year_published'] ?>
      <?= $p['agency'] ? ' &middot; <strong>Agency:</strong> ' . e($p['agency']) : '' ?><br>
      <strong>Keywords:</strong> <?= e($p['keywords']) ?>
    </p>
    <h3 style="font-size:14px;margin:18px 0 4px">Abstract</h3>
    <p style="margin:0"><?= nl2br(e($p['abstract'])) ?></p>
  </div>
</div>

<div class="card">
  <h2>Full Document</h2>
  <div class="body">
    <?php if ($p['access_level'] === 'withheld'): ?>
      <div class="flash warn" style="margin:0 0 12px">
        The full document is withheld at the author&rsquo;s or adviser&rsquo;s instruction.
        The description above remains publicly available.
      </div>
      <p class="small" style="margin:0">
        <strong>To request a copy</strong>, write to the adviser,
        <strong><?= e($p['adviser']) ?></strong>, through the
        <?= e($p['college']) ?>, Cagayan State University &ndash; <?= e($p['campus']) ?> Campus.
        A withheld document is released by the author or adviser directly, not through
        this archive.
      </p>
      <?php if (is_role('admin')): ?>
        <p class="small muted" style="margin:12px 0 0">
          As custodian of the archive you may release it. The release is written to the system log.</p>
        <a class="btn" href="download.php?id=<?= (int)$p['id'] ?>">Release full document (PDF)</a>
      <?php endif; ?>
    <?php elseif (!user()): ?>
      <div class="flash warn" style="margin:0 0 12px">
        The description above is public and indexable. The full document is released
        to authenticated institutional users only.
      </div>
      <a class="btn" href="login.php?next=<?= urlencode('record.php?id=' . $p['id']) ?>">Sign in to download</a>
    <?php else: ?>
      <p class="small muted" style="margin-top:0">Released to <?= e(user()['name']) ?> as an authenticated institutional user. The release is written to the system log.</p>
      <a class="btn" href="download.php?id=<?= (int)$p['id'] ?>">Download full document (PDF)</a>
    <?php endif; ?>
    <p class="small muted" style="margin-bottom:0">
      <?= (int)$p['views'] ?> views &middot; <?= (int)$p['downloads'] ?> downloads &middot;
      deposited <?= date('d M Y', strtotime($p['created_at'])) ?>
    </p>
  </div>
</div>

<div class="card">
  <h2>Discovery Metadata Emitted by This Page</h2>
  <div class="body">
    <p class="small muted" style="margin-top:0">
      These tags are in the page head of every approved record, which is what makes
      the holding reachable by external academic search services. Listed here for the demonstration only.
    </p>
    <table>
      <?php foreach ($dc as $k => $v): ?>
        <tr><td style="width:170px"><code><?= e($k) ?></code></td><td class="small"><?= e(mb_strimwidth((string)$v, 0, 150, '...')) ?></td></tr>
      <?php endforeach; ?>
    </table>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
