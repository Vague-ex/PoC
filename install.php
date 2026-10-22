<?php
/* Creates the asset_v2 schema and seeds demo holdings. Safe to re-run: it drops and rebuilds. */
require_once __DIR__ . '/config.php';

$server = db(false);
$server->exec('CREATE DATABASE IF NOT EXISTS ' . DB_NAME . ' CHARACTER SET utf8mb4');

$schema = [
"DROP TABLE IF EXISTS logs, searches, backups, papers, users",

"CREATE TABLE users (
  id INT AUTO_INCREMENT PRIMARY KEY,
  name VARCHAR(120) NOT NULL,
  email VARCHAR(160) NOT NULL UNIQUE,
  password VARCHAR(255) NOT NULL,
  role ENUM('admin','faculty','student') NOT NULL DEFAULT 'student',
  campus VARCHAR(60) NOT NULL DEFAULT 'Aparri',
  college VARCHAR(120) NOT NULL DEFAULT '',
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",

"CREATE TABLE papers (
  id INT AUTO_INCREMENT PRIMARY KEY,
  title VARCHAR(300) NOT NULL,
  authors VARCHAR(400) NOT NULL,
  co_authors VARCHAR(400) NOT NULL DEFAULT '',
  adviser VARCHAR(160) NOT NULL,
  keywords VARCHAR(300) NOT NULL DEFAULT '',
  year_published SMALLINT NOT NULL,
  agency VARCHAR(160) NOT NULL DEFAULT '',
  category ENUM('Social','Experimental','Technological') NOT NULL,
  campus VARCHAR(60) NOT NULL,
  college VARCHAR(120) NOT NULL,
  subject_area VARCHAR(120) NOT NULL,
  abstract TEXT NOT NULL,
  file_path VARCHAR(255) NOT NULL DEFAULT '',
  access_level ENUM('restricted','withheld') NOT NULL DEFAULT 'restricted',
  status ENUM('pending','approved') NOT NULL DEFAULT 'pending',
  views INT NOT NULL DEFAULT 0,
  downloads INT NOT NULL DEFAULT 0,
  likes INT NOT NULL DEFAULT 0,
  submitted_by INT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
  FULLTEXT KEY ft_search (title, authors, abstract, keywords)
) ENGINE=InnoDB",

"CREATE TABLE logs (
  id INT AUTO_INCREMENT PRIMARY KEY,
  user_id INT NULL,
  name VARCHAR(120) NOT NULL,
  action VARCHAR(255) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",

"CREATE TABLE searches (
  id INT AUTO_INCREMENT PRIMARY KEY,
  term VARCHAR(160) NOT NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",

"CREATE TABLE backups (
  id INT AUTO_INCREMENT PRIMARY KEY,
  filename VARCHAR(160) NOT NULL,
  size_bytes INT NOT NULL,
  rows_captured INT NOT NULL,
  sha256 CHAR(64) NOT NULL,
  status ENUM('verified','failed','unverified') NOT NULL DEFAULT 'unverified',
  verified_at DATETIME NULL,
  created_at DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB",
];
foreach ($schema as $stmt) db()->exec($stmt);

$h = password_hash('password', PASSWORD_DEFAULT);
$users = [
  ['Franksel Tindoc Jr.', 'admin@csu.edu.ph',   'admin',   'Aparri',  COLLEGES[0]],
  ['Billy Javier, PhD',   'faculty@csu.edu.ph', 'faculty', 'Aparri',  COLLEGES[0]],
  ['Jonalyn Salem',       'student@csu.edu.ph', 'student', 'Aparri',  COLLEGES[0]],
  ['Rogiefel Torres',     'torres@csu.edu.ph',  'faculty', 'Carig',   COLLEGES[1]],
];
foreach ($users as $u) {
    q('INSERT INTO users (name,email,password,role,campus,college) VALUES (?,?,?,?,?,?)',
      [$u[0], $u[1], $h, $u[2], $u[3], $u[4]]);
}

/* Seeded holdings spread across campuses, colleges, subject areas and years so the
   filters, the composition report and duplicate detection all have something to show.
   Columns: title, authors, co-authors, adviser, keywords, year, category,
            campus, college index, subject area index, access level, status */
$seed = [
 ['Grocery Ordering and Delivering System','Athena Erika B. Enriquez, Tea P. Latila','Kiezie C. Villaspir, Riza E. Umengan','Jhunrey C. Ordiuxo','ecommerce, logistics, web',2022,'Technological','Aparri',0,0,'restricted','approved'],
 ['Student Events Monitoring System','Arjunifer R. Arellano, Ronaliza G. Guibani','Jamaica P. Parto','Iolani A. Silvestre','monitoring, events, attendance',2022,'Technological','Aparri',0,0,'restricted','approved'],
 ['Profiling System for Barangay Residents','Bautista, Gaspar','','Billy Javier, PhD','profiling, barangay, records',2021,'Technological','Aparri',0,0,'restricted','approved'],
 ['Web-Based Research Archiving System for Undergraduate Theses','Franksel P. Tindoc Jr., Rogiefel G. Torres','Key Ann C. Corpuz','Leo P. Paliuanan','archiving, repository, laravel',2023,'Technological','Aparri',0,1,'restricted','approved'],
 ['Reading Comprehension of Grade 7 Learners in Modular Instruction','Maria L. Bagayan','Jerome T. Cabantac','Julieta Babas, EdD','reading, modular, literacy',2021,'Social','Aparri',1,2,'restricted','approved'],
 ['Teaching Strategies in Mathematics During Blended Learning','Nilo R. Aggabao','','Julieta Babas, EdD','mathematics, blended learning',2022,'Social','Lal-lo',1,2,'restricted','approved'],
 ['Financial Literacy Among Senior High School Students','Kristine Joy A. Manuel','Paulo S. Ramos','Simeon Rabanal Jr., PhD','finance, literacy, youth',2023,'Social','Aparri',2,3,'withheld','approved'],
 ['Inventory Management Practices of Micro Enterprises in Aparri','Rhea Mae C. Ruiz','','Simeon Rabanal Jr., PhD','inventory, msme, management',2020,'Social','Aparri',2,3,'restricted','approved'],
 ['Growth Performance of Tilapia Fingerlings Fed with Moringa Meal','Jerico P. Tumaliuan','Angela D. Battung','Leo P. Paliuanan','aquaculture, tilapia, feeds',2022,'Experimental','Aparri',3,4,'restricted','approved'],
 ['Water Quality Assessment of the Cagayan River Estuary','Angela D. Battung','','Leo P. Paliuanan','water quality, estuary, ecology',2023,'Experimental','Aparri',3,4,'withheld','approved'],
 ['Antibacterial Activity of Guava Leaf Extract on Staphylococcus aureus','Mark Cedric L. Pagulayan','Hazel Ann V. Baquiran','Billy Javier, PhD','antibacterial, extract, microbiology',2021,'Experimental','Sanchez Mira',4,5,'restricted','approved'],
 ['Solar Powered Water Pump for Small Scale Farming','Dennis R. Taguiam','','Iolani A. Silvestre','solar, irrigation, renewable',2023,'Experimental','Piat',6,8,'restricted','approved'],
 ['Guest Satisfaction in Budget Accommodations in Cagayan','Lovely Grace M. Tuddao','Shiela Mae R. Agustin','Julieta Babas, EdD','tourism, hospitality, satisfaction',2022,'Social','Gonzaga',5,6,'restricted','approved'],
 ['Nutrient Content of Locally Produced Rice Bran','Hazel Ann V. Baquiran','','Leo P. Paliuanan','nutrition, rice bran, analysis',2020,'Experimental','Lasam',4,5,'restricted','approved'],
 ['Mobile Based Attendance System Using QR Codes','Jhon Patrick C. Baggay','Kyle Vincent A. Lasam','Billy Javier, PhD','qr code, attendance, mobile',2024,'Technological','Aparri',0,0,'restricted','pending'],
 /* Near-duplicate of row 1, left pending so the approval queue shows duplicate detection working. */
 ['Grocery Ordering and Delivery System','Mark A. Dela Cruz','','Jhunrey C. Ordiuxo','ecommerce, delivery',2023,'Technological','Carig',1,1,'restricted','pending'],
];

$i = 0;
foreach ($seed as $s) {
    $i++;
    $abstract = "This study examined {$s[0]}. The research was conducted at Cagayan State University - {$s[7]} Campus and employed a descriptive-developmental design. Findings indicate that the approach addressed the problems identified in the existing practice and was rated acceptable by the intended respondents. The study recommends adoption and further evaluation in a wider institutional setting.";
    q('INSERT INTO papers (title,authors,co_authors,adviser,keywords,year_published,category,campus,college,subject_area,abstract,file_path,access_level,status,views,downloads,likes,submitted_by,created_at)
       VALUES (?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?,?)',
      [$s[0], $s[1], $s[2], $s[3], $s[4], $s[5], $s[6], $s[7], COLLEGES[$s[8]], SUBJECT_AREAS[$s[9]],
       $abstract, 'uploads/sample.pdf', $s[10], $s[11],
       rand(5, 180), rand(0, 40), rand(0, 25), 2,
       date('Y-m-d H:i:s', strtotime('-' . ($i * 23) . ' days'))]);
}

foreach (['research archiving','tilapia','attendance system','ISO 25010','financial literacy',
          'research archiving','tilapia','research archiving'] as $t) {
    q('INSERT INTO searches (term) VALUES (?)', [$t]);
}
foreach ([['Franksel Tindoc Jr.','logged in'], ['Franksel Tindoc Jr.','approved a research paper'],
          ['Billy Javier, PhD','added new research paper'], ['Jonalyn Salem','downloaded a full document'],
          ['Franksel Tindoc Jr.','ran a verified backup']] as $l) {
    q('INSERT INTO logs (user_id,name,action) VALUES (?,?,?)', [1, $l[0], $l[1]]);
}

/* One placeholder full document so the gated download actually delivers a file. */
$pdf = __DIR__ . '/uploads/sample.pdf';
if (!file_exists($pdf)) {
    @mkdir(dirname($pdf), 0777, true);
    $body = 'BT /F1 13 Tf 56 740 Td (ASSET v2 - placeholder full document) Tj ET';
    $objs = ['<< /Type /Catalog /Pages 2 0 R >>',
             '<< /Type /Pages /Kids [3 0 R] /Count 1 >>',
             '<< /Type /Page /Parent 2 0 R /MediaBox [0 0 612 792] /Contents 4 0 R /Resources << /Font << /F1 5 0 R >> >> >>',
             '<< /Length ' . strlen($body) . " >>\nstream\n" . $body . "\nendstream",
             '<< /Type /Font /Subtype /Type1 /BaseFont /Helvetica >>'];
    $out = "%PDF-1.4\n"; $off = [];
    foreach ($objs as $n => $o) { $off[] = strlen($out); $out .= ($n + 1) . " 0 obj\n" . $o . "\nendobj\n"; }
    $start = strlen($out);
    $out .= "xref\n0 " . (count($objs) + 1) . "\n0000000000 65535 f \n";
    foreach ($off as $o) $out .= sprintf("%010d 00000 n \n", $o);
    $out .= "trailer\n<< /Size " . (count($objs) + 1) . " /Root 1 0 R >>\nstartxref\n" . $start . "\n%%EOF";
    file_put_contents($pdf, $out);
}

echo "<meta charset='utf-8'><body style='font:15px system-ui;max-width:640px;margin:60px auto;color:#222'>";
echo "<h2>ASSET v2 installed</h2><p>Database <code>" . DB_NAME . "</code> rebuilt with "
   . count($seed) . " seeded holdings.</p><p>Accounts (password <code>password</code>):</p><ul>";
foreach ($users as $u) echo "<li><code>" . $u[1] . "</code> &mdash; " . $u[2] . "</li>";
echo "</ul><p><a href='index.php'>Open ASSET &rarr;</a></p>";
