<?php
/* Objective 5 - what an external academic search service crawls to find the
   holdings. Every approved record description is listed, whatever its access level,
   because the description is public even when the full document is not. */
require_once __DIR__ . '/config.php';

$base = (isset($_SERVER['HTTPS']) ? 'https' : 'http') . '://' . $_SERVER['HTTP_HOST']
      . rtrim(dirname($_SERVER['SCRIPT_NAME']), '/\\') . '/';

header('Content-Type: application/xml; charset=utf-8');
echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . "\n";
foreach (q("SELECT id, created_at FROM papers WHERE status = 'approved' ORDER BY id") as $p) {
    echo "  <url>\n";
    echo '    <loc>' . e($base . 'record.php?id=' . $p['id']) . "</loc>\n";
    echo '    <lastmod>' . date('Y-m-d', strtotime($p['created_at'])) . "</lastmod>\n";
    echo "  </url>\n";
}
echo "</urlset>\n";
