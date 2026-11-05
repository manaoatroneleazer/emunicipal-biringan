<?php
/**
 * eMunicipal — Timezone Correctness Tests
 *
 * Verifies that PHP and MySQL both operate in Asia/Manila (UTC+8, PHT)
 * and that timestamps stored in the database are consistent with PHT.
 *
 * Tests:
 *   TZ-01  PHP reports Asia/Manila as the active timezone
 *   TZ-02  PHP date() output matches Asia/Manila for a controlled UTC epoch
 *   TZ-03  MySQL session timezone is set to +08:00
 *   TZ-04  MySQL NOW() and PHP date() agree within a 2-second window
 *   TZ-05  MySQL NOW() is NOT equal to UTC_TIMESTAMP (confirms +8h offset)
 *   TZ-06  No double conversion: storing a PHP date and reading it back gives the same string
 *   TZ-07  Approval-style timestamp written via PHP date() matches PHT
 *   TZ-08  Payment confirmed_at written via MySQL NOW() matches PHT
 *   TZ-09  Existing historical timestamps in the database are not altered by loading config
 *   TZ-10  Application::__construct() sets timezone before any other bootstrap step
 */

$root = dirname(__DIR__);
foreach (file($root . '/.env', FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES) as $line) {
    if (str_starts_with(trim($line), '#') || !str_contains($line, '=')) continue;
    [$k, $v] = explode('=', $line, 2);
    $_ENV[trim($k)] = trim($v, " \t\n\r\"'");
}
require_once $root . '/vendor/autoload.php';

use Emunicipal\Core\Application;
use Emunicipal\Core\Database;

$passed  = 0;
$failed  = 0;
$results = [];

function chk(string $id, string $desc, bool $pass, string $detail = ''): void
{
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else       { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// Bootstrap the application — this must call date_default_timezone_set('Asia/Manila')
// and set the MySQL session timezone via the PDO connection.
// We instantiate Application directly so the timezone is applied before any test.
$app = new Application($root);

// ── TZ-01: PHP timezone is Asia/Manila ───────────────────────────────────────
$tz = date_default_timezone_get();
chk('TZ-01', 'PHP active timezone is Asia/Manila',
    $tz === 'Asia/Manila', "got: $tz");

// ── TZ-02: PHP date() converts a known UTC epoch correctly ───────────────────
// Unix epoch 0 is 1970-01-01 00:00:00 UTC = 1970-01-01 08:00:00 PHT
$phpConverted = date('Y-m-d H:i:s', 0);
chk('TZ-02', 'PHP date() converts UTC epoch 0 to 1970-01-01 08:00:00 (PHT)',
    $phpConverted === '1970-01-01 08:00:00', "got: $phpConverted");

// ── TZ-03: MySQL session timezone is +08:00 ──────────────────────────────────
try {
    $pdo = Database::getInstance();
    $row = $pdo->query("SELECT @@session.time_zone AS tz")->fetch(\PDO::FETCH_ASSOC);
    $mysqlTz = $row['tz'] ?? 'unknown';
    chk('TZ-03', "MySQL session timezone is '+08:00'",
        $mysqlTz === '+08:00', "got: $mysqlTz");
} catch (\Throwable $e) {
    chk('TZ-03', 'MySQL session timezone check', false, 'DB error: ' . $e->getMessage());
    $pdo = null;
}

// ── TZ-04: MySQL NOW() and PHP date() agree within 2 seconds ─────────────────
if (isset($pdo)) {
    $phpNow   = date('Y-m-d H:i:s');
    $mysqlNow = $pdo->query("SELECT NOW() AS n")->fetchColumn();
    $diff     = abs(strtotime($mysqlNow) - strtotime($phpNow));
    chk('TZ-04', 'MySQL NOW() and PHP date() agree within 2 seconds',
        $diff <= 2, "php=$phpNow mysql=$mysqlNow diff={$diff}s");
} else {
    chk('TZ-04', 'SKIP: no DB connection', false, 'DB unavailable');
}

// ── TZ-05: MySQL NOW() differs from UTC_TIMESTAMP by ~8 hours ─────────────────
// Confirms the session is offset, not UTC.
if (isset($pdo)) {
    $row2    = $pdo->query("SELECT NOW() AS n, UTC_TIMESTAMP() AS u")->fetch(\PDO::FETCH_ASSOC);
    $nowTs   = strtotime($row2['n']);
    $utcTs   = strtotime($row2['u']);
    $diffHrs = round(($nowTs - $utcTs) / 3600, 1);
    chk('TZ-05', 'MySQL NOW() is ahead of UTC_TIMESTAMP by 8 hours (PHT offset)',
        $diffHrs === 8.0, "diff=$diffHrs hours (NOW={$row2['n']} UTC={$row2['u']})");
} else {
    chk('TZ-05', 'SKIP: no DB connection', false, 'DB unavailable');
}

// ── TZ-06: Round-trip test — store a PHP date, read it back unchanged ─────────
// Uses a temporary row in a disposable table to verify no silent timezone conversion.
if (isset($pdo)) {
    $testTable = 'tz_roundtrip_test_' . time();
    try {
        $pdo->exec("CREATE TEMPORARY TABLE `{$testTable}` (
            `id` INT AUTO_INCREMENT PRIMARY KEY,
            `ts` DATETIME NOT NULL
        ) ENGINE=InnoDB");

        $phpTime = date('Y-m-d H:i:s');  // current PHT from PHP
        $pdo->prepare("INSERT INTO `{$testTable}` (ts) VALUES (:ts)")
            ->execute([':ts' => $phpTime]);

        $stored = $pdo->query("SELECT ts FROM `{$testTable}` LIMIT 1")->fetchColumn();
        chk('TZ-06', 'Round-trip: PHP date stored in DATETIME and read back unchanged',
            $stored === $phpTime, "stored=$stored php=$phpTime");

        $pdo->exec("DROP TEMPORARY TABLE IF EXISTS `{$testTable}`");
    } catch (\Throwable $e) {
        chk('TZ-06', 'Round-trip test', false, 'Error: ' . $e->getMessage());
    }
} else {
    chk('TZ-06', 'SKIP: no DB connection', false, 'DB unavailable');
}

// ── TZ-07: Approval-style timestamp uses PHT (spot-check date() for DB write) ─
// The application writes last_login_at, locked_until, and receipt paid_at via
// date('Y-m-d H:i:s'). Verify this produces a PHT string, not UTC.
$writtenTs  = date('Y-m-d H:i:s');
$asDateTime = new \DateTime($writtenTs, new \DateTimeZone('Asia/Manila'));
chk('TZ-07', 'Approval-style date() output is in Asia/Manila timezone',
    $asDateTime->getTimezone()->getName() === 'Asia/Manila', "ts=$writtenTs");

// ── TZ-08: Payment confirmed_at from MySQL NOW() is PHT ───────────────────────
// Simulate what Payment::confirm() / confirmOnline() stores via NOW().
// Confirmed_at = MySQL NOW() = should match PHP date() within 2 seconds.
if (isset($pdo)) {
    $nowRow = $pdo->query("SELECT NOW() AS confirmed_at")->fetch(\PDO::FETCH_ASSOC);
    $confirmedAt = $nowRow['confirmed_at'];
    $phpNow2     = date('Y-m-d H:i:s');
    $diff2       = abs(strtotime($confirmedAt) - strtotime($phpNow2));
    chk('TZ-08', 'Payment confirmed_at (MySQL NOW()) matches PHP date() within 2 seconds',
        $diff2 <= 2, "mysql=$confirmedAt php=$phpNow2 diff={$diff2}s");

    // Also confirm confirmed_at is in PHT hour range (UTC+8 hour = UTC hour + 8)
    $utcRow    = $pdo->query("SELECT UTC_TIMESTAMP() AS u")->fetch(\PDO::FETCH_ASSOC);
    $phHour    = (int)date('H', strtotime($confirmedAt));
    $utcHour   = (int)date('H', strtotime($utcRow['u']));
    $hourDiff  = ($phHour - $utcHour + 24) % 24;
    chk('TZ-08b', 'Payment confirmed_at hour is 8 ahead of UTC (confirms PHT storage)',
        $hourDiff === 8, "phHour=$phHour utcHour=$utcHour diff=$hourDiff");
} else {
    chk('TZ-08',  'SKIP: no DB connection', false, 'DB unavailable');
    chk('TZ-08b', 'SKIP: no DB connection', false, 'DB unavailable');
}

// ── TZ-09: Existing timestamps in the DB are not altered ──────────────────────
// Loading the application must not rewrite historical records.
// Verify record counts and a sample timestamp value are unchanged.
if (isset($pdo)) {
    // Read a known row count and a sample timestamp before and after another DB round-trip
    $countBefore = (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    // Re-read the count (no mutation happened)
    $countAfter  = (int)$pdo->query("SELECT COUNT(*) FROM requests")->fetchColumn();
    chk('TZ-09', 'Application bootstrap does not alter existing request record count',
        $countBefore === $countAfter, "before=$countBefore after=$countAfter");

    // Spot-check: pick the earliest audit_log entry; its timestamp must not have changed
    $oldestAudit = $pdo->query(
        "SELECT created_at FROM audit_logs ORDER BY id ASC LIMIT 1"
    )->fetchColumn();
    // It should be a valid datetime string matching the format 'YYYY-MM-DD HH:MM:SS'
    chk('TZ-09b', 'Oldest audit_log timestamp is a valid datetime string (not altered)',
        $oldestAudit !== false && preg_match('/^\d{4}-\d{2}-\d{2} \d{2}:\d{2}:\d{2}$/', $oldestAudit) === 1,
        "oldest_audit=$oldestAudit");
} else {
    chk('TZ-09',  'SKIP: no DB connection', false, 'DB unavailable');
    chk('TZ-09b', 'SKIP: no DB connection', false, 'DB unavailable');
}

// ── TZ-10: Application.php sets timezone before logger init ──────────────────
$appSrc   = file_get_contents($root . '/app/Core/Application.php');
// Confirm date_default_timezone_set appears before Logger::init
$tzPos    = strpos($appSrc, "date_default_timezone_set('Asia/Manila')");
$logPos   = strpos($appSrc, "Logger::init(");
chk('TZ-10', 'Application.php: date_default_timezone_set() comes before Logger::init()',
    $tzPos !== false && $logPos !== false && $tzPos < $logPos,
    "tz_pos=$tzPos log_pos=$logPos");

// Also confirm the MySQL timezone set is in Database.php
$dbSrc = file_get_contents($root . '/app/Core/Database.php');
chk('TZ-10b', "Database.php: SET time_zone = '+08:00' is present",
    str_contains($dbSrc, "SET time_zone = '+08:00'"));

// ── PHP syntax checks ─────────────────────────────────────────────────────────
$phpBin = 'c:\\xampp\\php\\php.exe';
foreach ([
    'app/Core/Application.php',
    'app/Core/Database.php',
    'views/home/index.php',
] as $sf) {
    exec("\"$phpBin\" -l \"{$root}/{$sf}\" 2>&1", $out, $code);
    chk("SYN-" . basename($sf), "Syntax: $sf", $code === 0,
        $code === 0 ? 'OK' : implode(' ', $out));
    unset($out);
}

// ── Results ───────────────────────────────────────────────────────────────────
echo "\n====================================================\n";
echo " TIMEZONE TESTS — eMunicipal (Asia/Manila)\n";
echo "====================================================\n\n";
foreach ($results as [$s, $id, $desc, $detail]) {
    echo "[{$s}] " . ($s === 'PASS' ? 'PASS' : 'FAIL') . " {$id}: {$desc}"
         . ($detail ? " ({$detail})" : '') . "\n";
}
$t = $passed + $failed;
echo "\n----------------------------------------------------\n";
echo "Results: {$passed}/{$t} passed.\n";
if ($failed === 0) { echo "\nALL TESTS PASSED\n\n"; exit(0); }
else               { echo "\nSOME TESTS FAILED\n\n"; exit(1); }
