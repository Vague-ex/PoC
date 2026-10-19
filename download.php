<?php
/* Gated full-document release. Every release is logged - the audit trail the
   tiered access policy and RA 10173 compliance rest on. */
require_once __DIR__ . '/config.php';

$p = q("SELECT * FROM papers WHERE id = ? AND status = 'approved'", [(int)($_GET['id'] ?? 0)])->fetch();
if (!$p) { http_response_code(404); exit('404 - no such holding.'); }

if (!can_download($p)) {
    if (!user()) { header('Location: login.php?next=' . urlencode('record.php?id=' . $p['id'])); exit; }
    http_response_code(403);
    exit('403 - the full document of this holding is withheld.');
}

$file = __DIR__ . '/' . $p['file_path'];
if (!is_file($file)) { http_response_code(404); exit('404 - the deposited file is missing.'); }

q('UPDATE papers SET downloads = downloads + 1 WHERE id = ?', [$p['id']]);
log_action('downloaded the full document of "' . mb_strimwidth($p['title'], 0, 60, '...') . '"');

$name = preg_replace('/[^A-Za-z0-9 _-]/', '', $p['title']);
header('Content-Type: application/pdf');
header('Content-Length: ' . filesize($file));
header('Content-Disposition: attachment; filename="' . $name . '.pdf"');
readfile($file);
