# ASSET v2 — Proof of Concept

A working mock of the **Enhancement of ASSET: A Web-Based Research Archiving System**,
built to demonstrate the five enhancements proposed in the CHED RPAG deliverable against
the system described by Tindoc et al. (2023).

The v1 screens from the original paper (landing page, admin dashboard, system logs,
profile, publications, submit-a-paper) are reproduced so the enhancement is visibly
*additive*, as the proposal states. PHP + MySQL on XAMPP, matching the original stack;
no framework, no CDN, so it runs offline during a defence.

## Running it

1. Start **Apache** and **MySQL** in the XAMPP control panel.
2. Visit <http://localhost/PoC/install.php> once. It builds the `asset_v2` database and
   seeds 16 holdings across 6 campuses, 7 colleges, 9 subject areas and 5 years.
3. Visit <http://localhost/PoC/index.php>.

Accounts — password `password` for all:

| Email | Role | Sees |
|---|---|---|
| `admin@csu.edu.ph` | admin | everything, including backup and system logs |
| `faculty@csu.edu.ph` | faculty | dashboard, approval queue, composition report |
| `student@csu.edu.ph` | student | deposit, own submissions, full-document downloads |

Re-running `install.php` resets the demo to a clean state.

## Where each objective is demonstrated

| # | Specific objective | Page | What to show |
|---|---|---|---|
| 2 | Scheduled backup with **verified restoration** | `backup.php` | Run a backup → Verify (SHA-256 matches, statements counted) → delete a holding from the archive → Restore, and the holding returns. A dump altered on disk fails verification and restoration from it is refused — `selfcheck.php` proves both. |
| 3 | **Search filtering** by year, adviser, college, subject area | `search.php` | The filter panel. v1 had a search box and no filters at all — its three broad categories (Social / Experimental / Technological) were recorded at deposit but the paper documents no way to narrow results by them, and the original developers listed a filtering feature among their own recommendations. v2 promotes the category to a filter and adds the four the objective names, plus campus. Retrieval time is printed on every result set for the baseline-vs-endline measure. |
| 4 | **Record origin tracking** and **duplicate detection** | `deposit.php`, `publications.php` | Deposit a paper titled *Grocery Ordering and Delivering System* — the form refuses it and shows a 100% match plus a 94.6% near-match from another campus. The pending queue raises the same warning against the seeded near-duplicate. Campus and college of origin are captured on every record. |
| 5 | **Tiered access** and external discoverability | `record.php`, `download.php`, `sitemap.php` | A record description is readable signed out, and the page lists the Dublin Core / schema.org tags it emits for harvesters. The full document asks for a sign-in; a *withheld* holding is refused even to signed-in users. `sitemap.php` is what a crawler reads. |
| — | Archive composition reporting (Outcome 3) | `reports.php` | Composition by college, campus, subject area, year and access level, with CSV export for the research office. Impossible under v1, which recorded no origin. |

The dashboard (`dashboard.php`) keeps the original v1 analytics panel and adds three v2
indicators: publicly discoverable records (v1 baseline: zero), campuses represented
(v1: not recorded), and last verified backup (v1: no mechanism).

## What this proof of concept is not

Deliberately out of scope — it demonstrates the enhancement, it is not the deployment:

- **No real scheduler.** The "Run backup now" button stands in for the cron job.
- **No real harvesting.** The Dublin Core and schema.org metadata and the sitemap are
  emitted correctly, but nothing is registered with an external indexing service.
- **No Laravel.** The proposal retains the existing Laravel codebase; this mock is plain
  PHP so it runs from `htdocs` with nothing installed.
- **Uploads are nominal.** A placeholder PDF stands in unless a file is actually attached.
- **Similarity is a string comparison**, not a semantic one — enough to catch the
  resubmitted and lightly-retitled duplicates that the proposal targets.

## Checking it still works

```
C:\xampp\php\php.exe C:\xampp\htdocs\PoC\selfcheck.php
```

17 assertions over duplicate detection, the tiered-access rule, and the
backup → verify → tamper → restore cycle. It leaves the database as it found it.

## Files

```
config.php        db handle, roles, duplicate detection, access rule
install.php       schema + demo data (re-runnable)
selfcheck.php     command-line assertions
index.php         public landing (v1 Figure 2)
search.php        filtered search                        ← Objective 3
record.php        public description + discovery metadata ← Objective 5
download.php      gated full document                     ← Objective 5
sitemap.php       crawler entry point                     ← Objective 5
deposit.php       submit a paper (v1 Figure 7) + origin + duplicates ← Objective 4
publications.php  approval queue (v1 Figure 6) + duplicate warnings
reports.php       archive composition + CSV               ← Outcome 3
backup.php        backup, verification, restoration       ← Objective 2
dashboard.php     admin dashboard (v1 Figure 3) + v2 indicators
logs.php          system logs (v1 Figure 4)
profile.php       my profile (v1 Figure 5)
```
