<?php
/* ASSET v2 PoC - shared config, db handle and helpers. */
session_start();

const DB_HOST = '127.0.0.1';
const DB_NAME = 'asset_v2';
const DB_USER = 'root';
const DB_PASS = '';

const CAMPUSES = ['Aparri','Andrews','Carig','Gonzaga','Lal-lo','Lasam','Piat','Sanchez Mira'];
const COLLEGES = ['College of Information and Computing Sciences','College of Teacher Education','College of Business Entrepreneurship and Accountancy','College of Fisheries and Aquatic Sciences','College of Arts and Sciences','College of Hospitality Management','College of Industrial Technology'];
const SUBJECT_AREAS = ['Information Systems','Software Engineering','Education','Business and Management','Fisheries and Aquaculture','Natural Sciences','Hospitality and Tourism','Industrial Technology','Public Health'];
/* Legacy ASSET v1 categories - recorded at deposit in v1 but never searchable; v2 makes them a filter. */
const CATEGORIES = ['Social','Experimental','Technological'];

function db(bool $withDb = true): PDO {
    static $pdo = [];
    $k = $withDb ? 'db' : 'server';
    if (!isset($pdo[$k])) {
        $dsn = 'mysql:host=' . DB_HOST . ($withDb ? ';dbname=' . DB_NAME : '') . ';charset=utf8mb4';
        $pdo[$k] = new PDO($dsn, DB_USER, DB_PASS, [
            PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        ]);
    }
    return $pdo[$k];
}

function q(string $sql, array $args = []): PDOStatement {
    $st = db()->prepare($sql);
    $st->execute($args);
    return $st;
}

function user(): ?array { return $_SESSION['user'] ?? null; }
function is_role(string ...$roles): bool { return user() && in_array(user()['role'], $roles, true); }

function require_login(): void {
    if (!user()) { header('Location: login.php?next=' . urlencode($_SERVER['REQUEST_URI'])); exit; }
}
function require_role(string ...$roles): void {
    require_login();
    if (!is_role(...$roles)) { http_response_code(403); exit('403 - not permitted for your role.'); }
}

/* System logs - Figure 4 of the original paper. */
function log_action(string $action): void {
    $u = user();
    q('INSERT INTO logs (user_id, name, action) VALUES (?,?,?)',
       [$u['id'] ?? null, $u['name'] ?? 'guest', $action]);
}

function e($s): string { return htmlspecialchars((string)$s, ENT_QUOTES, 'UTF-8'); }

function flash(?string $msg = null, string $type = 'success') {
    if ($msg !== null) { $_SESSION['flash'] = [$msg, $type]; return null; }
    $f = $_SESSION['flash'] ?? null; unset($_SESSION['flash']); return $f;
}

/* Objective 4: duplicate detection. Normalised-title similarity against existing holdings. */
function find_duplicates(string $title, int $exceptId = 0): array {
    $norm = fn($s) => preg_replace('/[^a-z0-9 ]/', '', strtolower(trim($s)));
    $t = $norm($title);
    if ($t === '') return [];
    $hits = [];
    foreach (q('SELECT id, title, year_published, campus FROM papers WHERE id <> ?', [$exceptId]) as $p) {
        similar_text($t, $norm($p['title']), $pct);
        if ($pct >= 70) { $p['similarity'] = round($pct, 1); $hits[] = $p; }
    }
    usort($hits, fn($a, $b) => $b['similarity'] <=> $a['similarity']);
    return $hits;
}

/* Objective 5: tiered access. The description of every approved holding is public.
   Only the full document is tiered, and only two tiers are needed:
     restricted - released to any authenticated institutional user;
     withheld   - released to no one through the system, on the author's or
                  adviser's instruction. Requests go to the adviser off-system.
   The administrator can always release, since the archive is in their custody. */
function can_download(array $paper): bool {
    if ($paper['access_level'] === 'withheld') return is_role('admin');
    return (bool)user();
}
