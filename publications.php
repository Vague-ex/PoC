<?php
/* Figure 6 of the original paper - the approval queue - with v2 origin columns
   and a duplicate warning raised against each pending submission. */
require_once __DIR__ . '/config.php';
require_role('admin', 'faculty', 'student');

if ($_SERVER['REQUEST_METHOD'] === 'POST' && is_role('admin', 'faculty')) {
    $id = (int)($_POST['id'] ?? 0);
    $p = q('SELECT title FROM papers WHERE id = ?', [$id])->fetch();
    if ($p) {
        if (($_POST['do'] ?? '') === 'approve') {
            q("UPDATE papers SET status = 'approved' WHERE id = ?", [$id]);
            log_action('approved a research paper');
            flash('Holding approved and published to the archive.');
        } elseif (($_POST['do'] ?? '') === 'delete' && is_role('admin')) {
            q('DELETE FROM papers WHERE id = ?', [$id]);
            log_action('removed a research paper');
            flash('Holding removed.', 'warn');
        }
    }
    header('Location: publications.php');
    exit;
}

/* Students see only their own deposits; staff see the whole queue. */
$own = is_role('student');
$rows = $own
  ? q('SELECT * FROM papers WHERE submitted_by = ? ORDER BY status, created_at DESC', [user()['id']])->fetchAll()
  : q("SELECT * FROM papers ORDER BY status = 'approved', created_at DESC")->fetchAll();

$title = 'Publications';
$wide = true;
include __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2><?= $own ? 'My Deposits' : 'All Publications' ?></h2>
  <div class="body" style="padding-bottom:0">
    <a class="btn" href="deposit.php" style="float:right;margin-top:-6px">Submit a paper</a>
    <p class="small muted">
      Pending submissions are checked against existing holdings; a flagged title is
      shown with its closest match before approval.
    </p>
  </div>
  <table>
    <tr>
      <th>Title</th><th style="width:210px">Origin</th><th style="width:110px">Access</th>
      <th style="width:120px">Remarks</th><th style="width:170px">Action</th>
    </tr>
    <?php foreach ($rows as $p):
        $dupes = $p['status'] === 'pending' ? find_duplicates($p['title'], $p['id']) : []; ?>
      <tr>
        <td>
          <a href="record.php?id=<?= (int)$p['id'] ?>"><strong><?= e($p['title']) ?></strong></a>
          <div class="small muted"><strong>Authors:</strong> <?= e($p['authors']) ?>
            &middot; <strong>Adviser:</strong> <?= e($p['adviser']) ?></div>
          <?php if ($dupes): ?>
            <div class="flash warn small" style="margin:7px 0 0;padding:6px 10px">
              Possible duplicate of
              <a href="record.php?id=<?= (int)$dupes[0]['id'] ?>"><?= e($dupes[0]['title']) ?></a>
              (<?= $dupes[0]['similarity'] ?>% similar, <?= e($dupes[0]['campus']) ?> Campus,
              <?= (int)$dupes[0]['year_published'] ?>)
            </div>
          <?php endif; ?>
        </td>
        <td class="small"><?= e($p['campus']) ?> Campus<br><span class="muted"><?= e($p['subject_area']) ?></span></td>
        <td><span class="tag tag-<?= e($p['access_level']) ?>"><?= e($p['access_level']) ?></span></td>
        <td><span class="tag tag-<?= e($p['status']) ?>"><?= $p['status'] === 'approved' ? 'approved' : 'for approval' ?></span></td>
        <td>
          <?php if ($p['status'] === 'pending' && is_role('admin', 'faculty')): ?>
            <form method="post" style="display:inline">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-sm" name="do" value="approve">Approve</button>
            </form>
          <?php endif; ?>
          <?php if (is_role('admin')): ?>
            <form method="post" style="display:inline"
                  onsubmit="return confirm('Remove this holding permanently?')">
              <input type="hidden" name="id" value="<?= (int)$p['id'] ?>">
              <button class="btn btn-sm btn-danger" name="do" value="delete">Remove</button>
            </form>
          <?php endif; ?>
        </td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
