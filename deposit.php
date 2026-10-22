<?php
/* Figure 7 of the original paper, extended for v2:
   Objective 4 - campus and college of origin, and duplicate detection run before the
                 record is accepted;
   Objective 5 - the depositor sets the access level and records the author's consent. */
require_once __DIR__ . '/config.php';
require_role('admin', 'faculty', 'student');

$in = fn(string $k, string $d = '') => trim($_POST[$k] ?? $d);
$dupes = [];
$error = '';
$posted = $_SERVER['REQUEST_METHOD'] === 'POST';

if ($posted) {
    $title_in = $in('title');
    if ($title_in === '' || $in('authors') === '' || $in('adviser') === '' || $in('abstract') === '') {
        $error = 'Title, author, adviser and abstract are required.';
    } else {
        $dupes = find_duplicates($title_in);
        /* A flagged title is not blocked outright - the depositor may confirm it is a
           distinct study, which is the behaviour a librarian actually needs. */
        if ($dupes && !isset($_POST['confirm_distinct'])) {
            $error = 'A similar title already exists in the archive. Review the matches below.';
        } else {
            $path = 'uploads/sample.pdf';
            if (!empty($_FILES['document']['tmp_name']) && is_uploaded_file($_FILES['document']['tmp_name'])) {
                $name = 'uploads/' . date('YmdHis') . '-' . preg_replace('/[^A-Za-z0-9._-]/', '', $_FILES['document']['name']);
                if (move_uploaded_file($_FILES['document']['tmp_name'], __DIR__ . '/' . $name)) $path = $name;
            }
            q('INSERT INTO papers (title,authors,co_authors,adviser,keywords,year_published,agency,category,
                 campus,college,subject_area,abstract,file_path,access_level,status,submitted_by)
               VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
              [$title_in, $in('authors'), $in('co_authors'), $in('adviser'),
               implode(', ', array_filter([$in('kw1'), $in('kw2'), $in('kw3')])),
               (int)$in('year_published', date('Y')), $in('agency'), $in('category', 'Technological'),
               $in('campus'), $in('college'), $in('subject_area'), $in('abstract'),
               $path, $in('access_level', 'restricted'),
               is_role('admin') ? 'approved' : 'pending', user()['id']]);

            log_action('added new research paper "' . mb_strimwidth($title_in, 0, 60, '...') . '"');
            flash(is_role('admin')
                ? 'Holding deposited and approved.'
                : 'Holding deposited. It is queued for adviser approval.');
            header('Location: publications.php');
            exit;
        }
    }
}

/* The depositor's own origin prefills the record - what makes origin tracking stick. */
$defCampus  = $posted ? $in('campus')  : user()['campus'];
$defCollege = $posted ? $in('college') : user()['college'];

$title = 'Submit a Paper';
include __DIR__ . '/partials/header.php';
?>
<div class="card">
  <h2>Submit a Paper</h2>
  <div class="body">
    <?php if ($error): ?><div class="flash error"><?= e($error) ?></div><?php endif; ?>

    <?php if ($dupes): ?>
      <div class="card" style="border-color:#f0dcae">
        <h3 style="color:#8a5f16">Duplicate Detection &ndash; <?= count($dupes) ?> possible match<?= count($dupes) === 1 ? '' : 'es' ?></h3>
        <table>
          <tr><th>Existing holding</th><th style="width:90px">Year</th><th style="width:130px">Campus</th><th style="width:110px">Similarity</th></tr>
          <?php foreach ($dupes as $d): ?>
            <tr>
              <td><a href="record.php?id=<?= (int)$d['id'] ?>" target="_blank"><?= e($d['title']) ?></a></td>
              <td><?= (int)$d['year_published'] ?></td>
              <td><?= e($d['campus']) ?></td>
              <td><strong><?= $d['similarity'] ?>%</strong></td>
            </tr>
          <?php endforeach; ?>
        </table>
      </div>
    <?php endif; ?>

    <form method="post" enctype="multipart/form-data">
      <div class="grid cols-2">
        <div>
          <div class="field"><label>Title</label>
            <input type="text" name="title" required value="<?= e($in('title')) ?>"></div>
          <div class="field"><label>Author</label>
            <input type="text" name="authors" required value="<?= e($in('authors')) ?>"></div>
          <div class="field"><label>Co-authors</label>
            <input type="text" name="co_authors" value="<?= e($in('co_authors')) ?>"></div>
          <div class="field"><label>Adviser</label>
            <input type="text" name="adviser" required value="<?= e($in('adviser')) ?>"></div>
          <div class="field"><label>Keywords</label>
            <div class="grid cols-3">
              <input type="text" name="kw1" value="<?= e($in('kw1')) ?>">
              <input type="text" name="kw2" value="<?= e($in('kw2')) ?>">
              <input type="text" name="kw3" value="<?= e($in('kw3')) ?>">
            </div></div>
          <div class="grid cols-2">
            <div class="field"><label>Year published</label>
              <input type="number" name="year_published" min="1980" max="<?= date('Y') + 1 ?>"
                     value="<?= e($in('year_published', date('Y'))) ?>"></div>
            <div class="field"><label>Funding agency</label>
              <input type="text" name="agency" value="<?= e($in('agency')) ?>"></div>
          </div>
        </div>
        <div>
          <div class="field"><label>Campus of origin</label>
            <select name="campus"><?php foreach (CAMPUSES as $c): ?>
              <option <?= $defCampus === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>College of origin</label>
            <select name="college"><?php foreach (COLLEGES as $c): ?>
              <option <?= $defCollege === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>Subject area</label>
            <select name="subject_area"><?php foreach (SUBJECT_AREAS as $c): ?>
              <option <?= $in('subject_area') === $c ? 'selected' : '' ?>><?= e($c) ?></option><?php endforeach; ?></select></div>
          <div class="field"><label>Category (retained from v1)</label>
            <?php foreach (CATEGORIES as $c): ?>
              <label style="font-weight:400;display:inline-block;margin-right:14px">
                <input type="radio" name="category" value="<?= e($c) ?>" style="width:auto"
                  <?= $in('category', 'Technological') === $c ? 'checked' : '' ?>> <?= e($c) ?>
              </label>
            <?php endforeach; ?></div>
          <div class="field"><label>Access level</label>
            <select name="access_level">
              <option value="restricted" <?= $in('access_level', 'restricted') === 'restricted' ? 'selected' : '' ?>>Restricted &ndash; full document released to signed-in institutional users</option>
              <option value="withheld" <?= $in('access_level') === 'withheld' ? 'selected' : '' ?>>Withheld &ndash; full document released to no one; requests go to the adviser</option>
            </select>
            <p class="small muted" style="margin:5px 0 0">
              The description is public either way.</p></div>
          <div class="field"><label>Full document (PDF)</label>
            <input type="file" name="document" accept="application/pdf" style="width:100%"></div>
        </div>
      </div>

      <div class="field"><label>Abstract</label>
        <textarea name="abstract" required><?= e($in('abstract')) ?></textarea></div>

      <label class="small" style="font-weight:400;display:block;margin-bottom:12px">
        <input type="checkbox" name="consent" required style="width:auto">
        The author consents to deposit under the institutional licence (RA 10173, Objective 8).
      </label>

      <?php if ($dupes): ?>
        <label class="small" style="font-weight:400;display:block;margin-bottom:12px;color:#8a5f16">
          <input type="checkbox" name="confirm_distinct" style="width:auto">
          I have reviewed the matches above and confirm this is a distinct study.
        </label>
      <?php endif; ?>

      <button class="btn">Deposit record</button>
      <a class="btn btn-outline" href="publications.php">Cancel</a>
    </form>
  </div>
</div>
<?php include __DIR__ . '/partials/footer.php'; ?>
