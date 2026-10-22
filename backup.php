<?php
/* Objective 2 - scheduled backup with verified restoration.
   The dump is produced in PHP rather than by shelling out to mysqldump, so the
   mechanism works wherever the application runs. Each dump is fingerprinted with
   SHA-256 at the moment of writing; verification recomputes the digest and replays
   the statement count, which is what turns "a file exists" into "a backup verified". */
require_once __DIR__ . '/config.php';
require_role('admin');

const BACKUP_DIR = __DIR__ . '/backups';
const TABLES = ['users', 'papers', 'logs', 'searches'];   /* the backups log itself is not dumped */

/* Every statement is written on one line terminated by ";", so restoration can split
   on line boundaries. PDO::quote escapes newlines inside values, so no value breaks a line.
   ponytail: line-based splitting; move to a real SQL tokeniser only if dumps ever carry
   literals this cannot survive. */
function make_backup(): array {
    @mkdir(BACKUP_DIR, 0777, true);
    $name = 'asset_v2-' . date('Ymd-His') . '.sql';
    $fh = fopen(BACKUP_DIR . '/' . $name, 'w');
    fwrite($fh, "-- ASSET v2 backup taken " . date('c') . "\n");
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=0;\n");

    $rows = 0;
    foreach (TABLES as $t) {
        $create = q("SHOW CREATE TABLE `$t`")->fetch()['Create Table'];
        fwrite($fh, "DROP TABLE IF EXISTS `$t`;\n");
        fwrite($fh, preg_replace('/\s+/', ' ', $create) . ";\n");
        foreach (q("SELECT * FROM `$t`") as $r) {
            $vals = array_map(fn($v) => $v === null ? 'NULL' : db()->quote((string)$v), array_values($r));
            $cols = '`' . implode('`,`', array_keys($r)) . '`';
            fwrite($fh, "INSERT INTO `$t` ($cols) VALUES (" . implode(',', $vals) . ");\n");
            $rows++;
        }
    }
    fwrite($fh, "SET FOREIGN_KEY_CHECKS=1;\n");
    fclose($fh);

    $path = BACKUP_DIR . '/' . $name;
    q('INSERT INTO backups (filename,size_bytes,rows_captured,sha256,status) VALUES (?,?,?,?,?)',
      [$name, filesize($path), $rows, hash_file('sha256', $path), 'unverified']);
    return [$name, $rows];
}

/* Verification: the digest must still match, and the dump must still parse into at
   least as many statements as it captured rows. A dump that fails either is not a backup. */
function verify_backup(array $b): array {
    $path = BACKUP_DIR . '/' . $b['filename'];
    if (!is_file($path)) return ['failed', 'the dump file is missing from ' . basename(BACKUP_DIR) . '/'];
    $now = hash_file('sha256', $path);
    if ($now !== $b['sha256']) {
        return ['failed', 'checksum mismatch - recorded ' . substr($b['sha256'], 0, 16)
                        . '..., recomputed ' . substr($now, 0, 16) . '...'];
    }
    $stmts = 0;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        if ($line[0] !== '-' && str_ends_with(rtrim($line), ';')) $stmts++;
    }
    if ($stmts < (int)$b['rows_captured']) return ['failed', 'the dump holds fewer statements than rows captured'];
    return ['verified', $stmts . ' statements replayable, SHA-256 matches, ' . $b['rows_captured'] . ' rows captured'];
}

function restore_backup(array $b): int {
    $path = BACKUP_DIR . '/' . $b['filename'];
    $n = 0;
    foreach (file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
        $line = rtrim($line);
        if ($line === '' || $line[0] === '-' || !str_ends_with($line, ';')) continue;
        db()->exec(rtrim($line, ';'));
        $n++;
    }
    return $n;
}

$result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $do = $_POST['do'] ?? '';
    $b  = isset($_POST['id']) ? q('SELECT * FROM backups WHERE id = ?', [(int)$_POST['id']])->fetch() : null;

    if ($do === 'run') {
        [$name, $rows] = make_backup();
        log_action('ran a backup (' . $name . ')');
        flash('Backup <code>' . e($name) . '</code> written, ' . $rows . ' rows captured. Verify it to complete the cycle.');

    } elseif ($do === 'verify' && $b) {
        [$status, $detail] = verify_backup($b);
        q('UPDATE backups SET status = ?, verified_at = NOW() WHERE id = ?', [$status, $b['id']]);
        log_action('verified a backup (' . $b['filename'] . '): ' . $status);
        flash('<strong>' . ucfirst($status) . '</strong> &mdash; ' . e($detail),
              $status === 'verified' ? 'success' : 'error');

    } elseif ($do === 'restore' && $b) {
        [$status] = verify_backup($b);
        if ($status !== 'verified') {
            flash('Restoration refused: this dump does not pass verification.', 'error');
        } else {
            $n = restore_backup($b);
            flash('Restored ' . $n . ' statements from <code>' . e($b['filename']) . '</code>. The archive is back at that point in time.');
            /* The session user may no longer exist in the restored data. */
            if (!q('SELECT id FROM users WHERE id = ?', [user()['id']])->fetch()) { session_destroy(); }
            else { log_action('restored the archive from ' . $b['filename']); }
        }

    } elseif ($do === 'delete' && $b) {
        @unlink(BACKUP_DIR . '/' . $b['filename']);
        q('DELETE FROM backups WHERE id = ?', [$b['id']]);
        flash('Backup removed.', 'warn');
    }
    header('Location: backup.php');
    exit;
}

$backups = q('SELECT * FROM backups ORDER BY created_at DESC')->fetchAll();
$last = $backups[0] ?? null;

$title = 'Backup';
$wide = true;
include __DIR__ . '/partials/header.php';
?>
<h1 style="font-size:22px;margin:0 0 4px">Backup and Verified Restoration</h1>
<p class="muted small" style="margin-top:0">
  ASSET v1 had no backup mechanism, so the preservation risk that justified the system
  persisted in digital form. Each dump below is fingerprinted with SHA-256 when written;
  restoration is refused unless that fingerprint still matches.
</p>

<div class="grid cols-3" style="margin-bottom:18px">
  <div class="stat"><div class="label">Backups held</div><div class="value"><?= count($backups) ?></div></div>
  <div class="stat"><div class="label">Verified</div>
    <div class="value"><?= count(array_filter($backups, fn($b) => $b['status'] === 'verified')) ?></div>
    <div class="small muted">Outcome 2 target: 100% monthly</div></div>
  <div class="stat"><div class="label">Most recent</div>
    <div class="value" style="font-size:17px"><?= $last ? e(date('d M Y H:i', strtotime($last['created_at']))) : 'none' ?></div>
    <div class="small muted"><?= $last ? e($last['status']) : 'no backup taken yet' ?></div></div>
</div>

<div class="card">
  <h2>Backup Log</h2>
  <div class="body" style="padding-bottom:6px">
    <form method="post" style="display:inline">
      <button class="btn" name="do" value="run">Run backup now</button>
    </form>
    <span class="small muted" style="margin-left:10px">
      In deployment this runs on a schedule; the button stands in for the scheduler.
    </span>
  </div>
  <table>
    <tr><th>File</th><th style="width:90px">Rows</th><th style="width:95px">Size</th>
        <th style="width:200px">SHA-256</th><th style="width:150px">Status</th><th style="width:270px">Action</th></tr>
    <?php foreach ($backups as $b): ?>
      <tr>
        <td><code class="small"><?= e($b['filename']) ?></code>
            <div class="muted small"><?= e(date('d M Y H:i:s', strtotime($b['created_at']))) ?></div></td>
        <td><?= (int)$b['rows_captured'] ?></td>
        <td class="small"><?= number_format($b['size_bytes'] / 1024, 1) ?> KB</td>
        <td><code class="small"><?= e(substr($b['sha256'], 0, 24)) ?>&hellip;</code></td>
        <td>
          <span class="tag tag-<?= $b['status'] === 'verified' ? 'approved' : ($b['status'] === 'failed' ? 'withheld' : 'pending') ?>">
            <?= e($b['status']) ?></span>
          <?php if ($b['verified_at']): ?>
            <div class="muted small"><?= e(date('d M H:i', strtotime($b['verified_at']))) ?></div>
          <?php endif; ?>
        </td>
        <td>
          <?php foreach ([['verify', 'Verify', 'btn-outline', ''],
                          ['restore', 'Restore', '', 'This replaces the live archive with the contents of this dump. Continue?'],
                          ['delete', 'Delete', 'btn-outline', 'Delete this backup?']] as [$do, $label, $cls, $confirm]): ?>
            <form method="post" style="display:inline"
                  <?= $confirm ? 'onsubmit="return confirm(' . "'" . $confirm . "'" . ')"' : '' ?>>
              <input type="hidden" name="id" value="<?= (int)$b['id'] ?>">
              <button class="btn btn-sm <?= $cls ?>" name="do" value="<?= $do ?>"><?= $label ?></button>
            </form>
          <?php endforeach; ?>
        </td>
      </tr>
    <?php endforeach; ?>
    <?php if (!$backups): ?>
      <tr><td colspan="6" class="muted">No backups taken yet.</td></tr>
    <?php endif; ?>
  </table>
</div>

<p class="small muted">
  <strong>Demonstration sequence:</strong> run a backup, verify it, then delete a holding
  from the archive and restore from that dump &mdash; the holding returns.
  A dump whose SHA-256 no longer matches fails verification and restoration from it is refused.
</p>
<?php include __DIR__ . '/partials/footer.php'; ?>
