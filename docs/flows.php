<?php
/* Flow diagrams for the version comparison document. Rendered in the browser and
   captured as figures; not part of the archive application itself.
   Open as docs/flows.php?f=1 .. ?f=6 */

$flows = [

1 => ['Deposit of a Holding', [
  'v1' => [
    ['actor', 'Depositor signs in'],
    ['step',  'Opens <b>Submit a Paper</b>'],
    ['step',  'Fills title, author, co-authors, adviser,<br>keywords, year, agency, abstract'],
    ['step',  'Picks one of three categories:<br>Social / Experimental / Technological'],
    ['save',  'Record saved, marked <i>for approval</i>'],
  ],
  'v2' => [
    ['actor', 'Depositor signs in'],
    ['step',  'Opens <b>Submit a Paper</b>'],
    ['step',  'Same fields as v1, plus <b>campus</b>, <b>college</b>,<br><b>subject area</b> and <b>access level</b><br><span class="n">origin prefilled from the account</span>'],
    ['step',  'Records the author&rsquo;s <b>consent to deposit</b>'],
    ['check', 'Title normalised and compared against<br>every existing holding'],
    ['branch','Any match at 70% or above?'],
    ['warn',  'YES &mdash; matches listed with similarity,<br>campus and year. Deposit is held until the<br>depositor confirms the study is distinct'],
    ['save',  'NO, or confirmed &mdash; record saved<br>with its origin, pending approval'],
    ['log',   'Written to the system log'],
  ],
]],

2 => ['Approval of a Submission', [
  'v1' => [
    ['actor', 'Research teacher signs in'],
    ['step',  'Opens <b>Publications</b>'],
    ['step',  'Sees each submission with its remark:<br><i>for approval</i> or <i>approved</i>'],
    ['step',  'Presses <b>Approve</b>'],
    ['save',  'Remark becomes <i>approved</i>'],
  ],
  'v2' => [
    ['actor', 'Research teacher signs in'],
    ['step',  'Opens <b>Publications</b><br><span class="n">a student sees only their own deposits</span>'],
    ['check', 'Each pending submission is re-checked<br>against every existing holding'],
    ['warn',  'A flagged submission shows its closest<br>match, similarity, campus and year<br><span class="n">catches duplicates deposited earlier</span>'],
    ['step',  'Approver also sees <b>origin</b> and<br><b>access level</b> on every row'],
    ['step',  'Presses <b>Approve</b>'],
    ['save',  'Holding is published to the archive<br>and becomes publicly described'],
    ['log',   'Written to the system log'],
  ],
]],

3 => ['Search and Retrieval', [
  'v1' => [
    ['actor', 'User signs in<br><span class="n">an account was required to see anything</span>'],
    ['step',  'Types a topic into the search bar'],
    ['step',  'Results returned'],
    ['end',   'No way to narrow them further'],
  ],
  'v2' => [
    ['actor', 'Anyone, signed in or not'],
    ['step',  'Types a topic, or opens the archive<br>with no search term at all'],
    ['step',  'Term recorded for the<br><i>Most Searched</i> panel'],
    ['check', 'Filter panel offers <b>year</b>, <b>adviser</b>,<br><b>college</b>, <b>subject area</b>, <b>campus</b><br>and the v1 <b>category</b>'],
    ['step',  'Choices combine &mdash; each one applied<br>together with the others<br><span class="n">values drawn from the holdings, so no<br>choice returns an empty result by surprise</span>'],
    ['save',  'Matching holdings listed with<br><b>count and elapsed time</b><br><span class="n">the retrieval measure for the baseline</span>'],
  ],
]],

4 => ['Release of a Full Document', [
  'v1' => [
    ['actor', 'Visitor arrives'],
    ['branch','Signed in?'],
    ['deny',  'NO &mdash; nothing is visible at all,<br>not even the title of a holding'],
    ['step',  'YES &mdash; record opens'],
    ['save',  'Document downloaded'],
  ],
  'v2' => [
    ['actor', 'Visitor arrives, signed in or not'],
    ['save',  '<b>Description is always served</b><br>title, authors, adviser, origin, abstract<br><span class="n">with Dublin Core and schema.org tags<br>for external search services</span>'],
    ['step',  'Visitor requests the full document'],
    ['branch','Is the holding withheld?'],
    ['deny',  'YES &mdash; released to no one but the<br>administrator; the description stays public'],
    ['branch','NO &mdash; is the visitor signed in?'],
    ['warn',  'NO &mdash; sent to sign in, then returned<br>to the record they asked for'],
    ['save',  'YES &mdash; document released'],
    ['log',   'Release written to the system log'],
  ],
]],

5 => ['Backup and Restoration', [
  'v1' => [
    ['deny',  'No backup mechanism of any kind'],
    ['end',   'The preservation risk that justified<br>the system persisted in digital form'],
  ],
  'v2' => [
    ['actor', 'Scheduler runs<br><span class="n">a button stands in for it here</span>'],
    ['step',  'Holdings, accounts, logs and search<br>history written to a dump file'],
    ['check', '<b>SHA-256 digest computed</b> and recorded<br>at the moment the dump is written'],
    ['step',  'Later: verification requested'],
    ['check', 'Digest recomputed and statements counted'],
    ['branch','Does the digest still match?'],
    ['deny',  'NO &mdash; marked <i>failed</i>, and any<br>restoration from it is <b>refused</b>'],
    ['save',  'YES &mdash; marked <i>verified</i>'],
    ['step',  'Restoration replays the dump<br>and the archive returns to that point'],
    ['log',   'Every run, verification and restoration<br>written to the system log'],
  ],
]],

6 => ['Archive Composition Reporting', [
  'v1' => [
    ['deny',  'Not possible'],
    ['end',   'No campus, college or subject area<br>was ever recorded, so there was nothing<br>to report on'],
  ],
  'v2' => [
    ['actor', 'Research office opens the report'],
    ['step',  'Approved holdings grouped by<br><b>college</b>, <b>campus</b>, <b>subject area</b>,<br><b>year</b> and <b>access level</b>'],
    ['step',  'Each group shown with its share,<br>how many are publicly discoverable<br>and its download activity'],
    ['step',  'Deposit volume broken down by quarter<br><span class="n">the cadence the logframe reports on</span>'],
    ['save',  'Any dimension exported as <b>CSV</b><br><span class="n">the form attached to an accreditation<br>submission</span>'],
    ['log',   'Export written to the system log'],
  ],
]],

];

$f = (int)($_GET['f'] ?? 1);
if (!isset($flows[$f])) { http_response_code(404); exit('no such flow'); }
[$heading, $cols] = $flows[$f];

function col(string $label, string $sub, array $steps, string $cls): void { ?>
  <div class="col <?= $cls ?>">
    <div class="colhead"><span class="ver"><?= $label ?></span> <?= $sub ?></div>
    <?php foreach ($steps as $i => [$kind, $text]): ?>
      <?php if ($i): ?><div class="arrow">&#9660;</div><?php endif; ?>
      <div class="box <?= $kind ?>"><?= $text ?></div>
    <?php endforeach; ?>
  </div>
<?php }
?><!doctype html>
<html lang="en"><head><meta charset="utf-8">
<title>Flow <?= $f ?></title>
<style>
  * { box-sizing: border-box; }
  body { margin: 0; padding: 26px 30px 30px; background: #fff;
         font: 13px/1.45 "Segoe UI", Tahoma, Arial, sans-serif; color: #2f3542; width: 1160px; }
  h1 { font-size: 19px; margin: 0 0 3px; }
  .sub { color: #7a8194; font-size: 12.5px; margin: 0 0 20px; }
  .cols { display: flex; gap: 34px; align-items: flex-start; }
  .col { flex: 1; }
  .colhead { font-size: 12px; text-transform: uppercase; letter-spacing: .7px; color: #55607a;
             border-bottom: 2px solid #e2e5ea; padding-bottom: 7px; margin-bottom: 14px; }
  .colhead .ver { font-weight: 800; color: #2f3542; }
  .v2 .colhead { border-bottom-color: #16a39a; }
  .box { border: 1px solid #ccd1d9; border-radius: 4px; padding: 9px 13px; background: #fff; text-align: center; }
  .arrow { text-align: center; color: #b7bdc8; font-size: 11px; line-height: 1; margin: 5px 0; }
  .n { color: #7a8194; font-size: 11.5px; font-style: italic; }
  .actor  { background: #eef1f4; border-color: #ccd1d9; font-weight: 600; }
  .check  { background: #eef6fb; border-color: #bcd8ea; }
  .branch { background: #fff; border: 2px solid #55607a; font-weight: 600; border-radius: 18px; }
  .warn   { background: #fdf2dd; border-color: #f0dcae; }
  .deny   { background: #fbe6e5; border-color: #f0c2bf; }
  .save   { background: #e4f4ec; border-color: #b7e0c9; font-weight: 600; }
  .log    { background: #f7f8fa; border-color: #e2e5ea; color: #55607a; font-size: 12px; }
  .end    { background: #f7f8fa; border-color: #e2e5ea; color: #7a8194; font-style: italic; }
  .key { margin-top: 22px; padding-top: 13px; border-top: 1px solid #e2e5ea;
         display: flex; gap: 20px; flex-wrap: wrap; font-size: 11.5px; color: #55607a; }
  .key span { display: inline-flex; align-items: center; gap: 6px; }
  .key i { width: 15px; height: 11px; border-radius: 2px; border: 1px solid #ccd1d9; display: inline-block; }
</style></head><body>
<h1><?= $f ?>. <?= htmlspecialchars($heading) ?></h1>
<p class="sub">ASSET Version 1 (Tindoc et al., 2023) compared with the enhanced Version 2</p>
<div class="cols">
  <?php col('VERSION 1', '&mdash; as built', $cols['v1'], 'v1'); ?>
  <?php col('VERSION 2', '&mdash; enhanced', $cols['v2'], 'v2'); ?>
</div>
<div class="key">
  <span><i style="background:#eef1f4"></i> actor</span>
  <span><i style="background:#eef6fb;border-color:#bcd8ea"></i> system check</span>
  <span><i style="background:#fff;border:2px solid #55607a;border-radius:7px"></i> decision</span>
  <span><i style="background:#fdf2dd;border-color:#f0dcae"></i> held for review</span>
  <span><i style="background:#fbe6e5;border-color:#f0c2bf"></i> refused or absent</span>
  <span><i style="background:#e4f4ec;border-color:#b7e0c9"></i> outcome</span>
  <span><i style="background:#f7f8fa"></i> audit record</span>
</div>
</body></html>
