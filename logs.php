<?php
/* Figure 4 of the original paper, retained. In v2 it also carries the audit trail
   for full-document releases and backup verification. */
require_once __DIR__ . '/config.php';
require_role('admin');

$rows = q('SELECT * FROM logs ORDER BY created_at DESC, id DESC LIMIT 200')->fetchAll();

$title = 'System Logs';
$wide = true;
include __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2>System Logs</h2>
  <table>
    <tr><th style="width:60px">No.</th><th style="width:90px">User ID</th>
        <th style="width:200px">Name</th><th>Action</th><th style="width:180px">Date and Time</th></tr>
    <?php foreach ($rows as $i => $l): ?>
      <tr>
        <td><?= $i + 1 ?></td>
        <td><?= $l['user_id'] === null ? '&mdash;' : (int)$l['user_id'] ?></td>
        <td><?= e($l['name']) ?></td>
        <td><?= e($l['action']) ?></td>
        <td class="small muted"><?= e(date('Y-m-d H:i:s', strtotime($l['created_at']))) ?></td>
      </tr>
    <?php endforeach; ?>
  </table>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
