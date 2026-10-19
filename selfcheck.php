<?php
/* Self-check for the logic that is not obvious by reading: duplicate detection,
   the tiered-access rule, and the backup / verify / restore cycle.
   Run from the command line:  php selfcheck.php
   It works on the live asset_v2 database and leaves it as it found it. */
if (PHP_SAPI !== 'cli') exit('Run this from the command line.');
require_once __DIR__ . '/config.php';

$checks = 0;
function ok(string $what, bool $cond): void {
    global $checks; $checks++;
    echo ($cond ? '  PASS  ' : '  FAIL  ') . $what . PHP_EOL;
    if (!$cond) { echo "selfcheck failed." . PHP_EOL; exit(1); }
}

echo "ASSET v2 self-check" . PHP_EOL;

/* --- duplicate detection --- */
$known = q("SELECT title FROM papers WHERE title LIKE 'Grocery Ordering%' LIMIT 1")->fetch()['title'];
ok('an exact existing title is flagged',            count(find_duplicates($known)) > 0);
ok('punctuation and case are ignored',              count(find_duplicates(strtoupper($known) . '!!')) > 0);
ok('an unrelated title is not flagged',             find_duplicates('Zygomorphic Lattice Dynamics in Cold Plasma') === []);
ok('the highest-similarity match comes first', (function () use ($known) {
    $d = find_duplicates($known);
    return count($d) < 2 || $d[0]['similarity'] >= $d[1]['similarity'];
})());

/* --- tiered access --- */
$public     = ['access_level' => 'public'];
$restricted = ['access_level' => 'restricted'];
$withheld   = ['access_level' => 'withheld'];
unset($_SESSION['user']);
ok('a guest cannot download a public holding',      !can_download($public));
ok('a guest cannot download a restricted holding',  !can_download($restricted));
$_SESSION['user'] = ['id' => 3, 'name' => 'test', 'role' => 'student'];
ok('a signed-in user can download a public holding',     can_download($public));
ok('a signed-in user can download a restricted holding', can_download($restricted));
ok('a student cannot download a withheld holding',      !can_download($withheld));
$_SESSION['user']['role'] = 'admin';
ok('an admin can download a withheld holding',           can_download($withheld));

/* --- backup, verification and restoration --- */
$_SESSION['user'] = ['id' => 1, 'name' => 'selfcheck', 'role' => 'admin'];
$before = (int)q('SELECT COUNT(*) AS n FROM papers')->fetch()['n'];

$_SERVER['REQUEST_METHOD'] = 'GET';          /* keep backup.php from acting on POST */
$_GET = [];
ob_start(); require_once __DIR__ . '/backup.php'; ob_end_clean();

[$name, $rows] = make_backup();
$b = q('SELECT * FROM backups WHERE filename = ?', [$name])->fetch();
ok('the dump captured every row',                   $rows >= $before);
ok('a fresh dump verifies',                         verify_backup($b)[0] === 'verified');

/* Remove a holding, restore, and confirm the count comes back. */
q('DELETE FROM papers ORDER BY id DESC LIMIT 1');
ok('the deletion took effect', (int)q('SELECT COUNT(*) AS n FROM papers')->fetch()['n'] === $before - 1);
restore_backup($b);
ok('restoration brought the holdings back', (int)q('SELECT COUNT(*) AS n FROM papers')->fetch()['n'] === $before);

/* Tamper with the file: the checksum must catch it and restoration must be refused. */
file_put_contents(BACKUP_DIR . '/' . $name, "-- tampered\n", FILE_APPEND);
[$status, $detail] = verify_backup($b);
ok('a tampered dump fails verification',            $status === 'failed');
ok('the failure names the checksum',                str_contains($detail, 'checksum'));

/* Clean up after ourselves. */
@unlink(BACKUP_DIR . '/' . $name);
q('DELETE FROM backups WHERE id = ?', [$b['id']]);
$b['filename'] = 'does-not-exist.sql';
ok('a missing dump fails verification',             verify_backup($b)[0] === 'failed');

echo PHP_EOL . "All $checks checks passed." . PHP_EOL;
