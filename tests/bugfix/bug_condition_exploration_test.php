<?php
/**
 * Bug Condition Exploration Test
 *
 * Spec: emunicipal-nav-sidebar-faq-fixes
 * Purpose: Confirm that each defect EXISTS in the unfixed codebase by asserting on
 *          the bug condition. A PASS here means the bug is confirmed present.
 *          A FAIL means the bug was NOT found (unexpected on unfixed code).
 *
 * Run with:
 *   php tests/bugfix/bug_condition_exploration_test.php
 *
 * Exit codes:
 *   0 — All bug conditions confirmed (all bugs exist — expected outcome)
 *   1 — One or more bug conditions NOT found (unexpected — code may already be partially fixed)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

$passed  = 0;
$failed  = 0;
$results = [];

/**
 * Assert that $condition is TRUE (bug condition is present).
 * A true result = PASS (bug confirmed). A false result = FAIL (bug not found).
 */
function assertBugPresent(
    string $id,
    string $description,
    bool   $condition,
    string $evidence,
    array  &$results,
    int    &$passed,
    int    &$failed
): void {
    if ($condition) {
        $status = 'PASS';
        $passed++;
    } else {
        $status = 'FAIL';
        $failed++;
    }
    $results[] = compact('id', 'status', 'description', 'evidence');
}

// ---------------------------------------------------------------------------
// File paths (relative to project root; script is run from project root)
// ---------------------------------------------------------------------------

$root         = dirname(__DIR__, 2); // two levels up from tests/bugfix/
$authLayout   = $root . '/views/layouts/auth.php';
$homeView     = $root . '/views/home/index.php';
$adminShow    = $root . '/views/admin/requests/show.php';
$staffShow    = $root . '/views/staff/requests/show.php';
$residentShow = $root . '/views/resident/requests/show.php';

// Verify all files exist before reading
$missingFiles = [];
foreach (
    [$authLayout, $homeView, $adminShow, $staffShow, $residentShow]
    as $f
) {
    if (!file_exists($f)) {
        $missingFiles[] = $f;
    }
}

if (!empty($missingFiles)) {
    echo "\n[ERROR] The following required source files were not found:\n";
    foreach ($missingFiles as $f) {
        echo "  - {$f}\n";
    }
    echo "\nRun this script from the project root:\n";
    echo "  php tests/bugfix/bug_condition_exploration_test.php\n\n";
    exit(2);
}

// ---------------------------------------------------------------------------
// Load file contents
// ---------------------------------------------------------------------------

$authContent     = file_get_contents($authLayout);
$homeContent     = file_get_contents($homeView);
$adminContent    = file_get_contents($adminShow);
$staffContent    = file_get_contents($staffShow);
$residentContent = file_get_contents($residentShow);

// ---------------------------------------------------------------------------
// Defect 1 — Duplicate header: <header class="auth-header"> present in auth.php
// ---------------------------------------------------------------------------

$headerPattern  = 'class="auth-header"';
$headerFound    = str_contains($authContent, $headerPattern);
$headerEvidence = $headerFound
    ? "Found: '<header class=\"auth-header\"' element present in views/layouts/auth.php"
    : "NOT found: '<header class=\"auth-header\"' is absent from views/layouts/auth.php";

// Extract the matching line for extra context
$headerLine = '';
foreach (explode("\n", $authContent) as $lineNum => $line) {
    if (str_contains($line, $headerPattern)) {
        $headerLine = sprintf("  Line %d: %s", $lineNum + 1, trim($line));
        break;
    }
}

assertBugPresent(
    'DEFECT-1',
    'Duplicate branding: <header class="auth-header"> element present in auth.php',
    $headerFound,
    $headerEvidence . ($headerLine ? "\n  " . $headerLine : ''),
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 2 — Missing Track Request link: /resident/track absent from Resident sidebar
// ---------------------------------------------------------------------------

// Isolate the resident sidebar block (between "RESIDENT SIDEBAR" comment and first
// "elseif" / "endif" block that terminates it)
$residentBlockStart = strpos($authContent, "if (\$role === 'resident')");
$residentBlockEnd   = strpos($authContent, "elseif (\$role === 'staff')", $residentBlockStart);

$residentSidebarBlock = ($residentBlockStart !== false && $residentBlockEnd !== false)
    ? substr($authContent, $residentBlockStart, $residentBlockEnd - $residentBlockStart)
    : $authContent; // fallback: search entire file

$trackLinkPresent = str_contains($residentSidebarBlock, '/resident/track');
// Bug exists when the link is ABSENT
$trackBugPresent = !$trackLinkPresent;
$trackEvidence   = $trackBugPresent
    ? "Confirmed: '/resident/track' link is NOT present in the Resident sidebar block of auth.php"
    : "NOT expected: '/resident/track' link WAS found in Resident sidebar block — bug may already be fixed";

assertBugPresent(
    'DEFECT-2',
    'Missing Track Request: /resident/track link absent from Resident sidebar in auth.php',
    $trackBugPresent,
    $trackEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 3 — Incomplete FAQ: fewer than 15 accordion items in views/home/index.php
// ---------------------------------------------------------------------------

// Count $faqs array entries by counting opening single-quote strings in the $faqs array.
// Most reliable: count occurrences of ['How ... or ['What ... patterns — but instead
// count the number of array sub-arrays by looking for the start of each FAQ tuple.
// The file uses: ['Question text', 'Answer text'] — each tuple starts with "['"
// In the context of the $faqs array.

// Strategy: extract the $faqs array literal and count entries by finding the
// pattern that starts each entry: a line that opens a new sub-array element.
// Each entry is: "['...'," on its own context line inside the array.

// Count by the number of accordion-item div occurrences in the rendered markup.
// The PHP loops over $faqs, so count $faqs entries directly.
// Each FAQ tuple starts as: "['..." following a comma or "[" at the array start.

preg_match_all("/\['[^']+',/", $homeContent, $faqMatches);
$faqCountByPattern = count($faqMatches[0]);

// Cross-check: count 'accordion-item' occurrences (rendered output would produce one per entry,
// but here we count the PHP source pattern driving the accordion loop)
$accordionItemCount = substr_count($homeContent, 'accordion-item');

// The most reliable count: look for the $faqs array and count sub-array entries.
// Each entry begins with a line like: "    ['How do ..." or "    ['What is ..."
// Extract the $faqs = [ ... ]; block and count opening "['" occurrences
$faqArrayStart = strpos($homeContent, '$faqs = [');
$faqArrayEnd   = $faqArrayStart !== false
    ? strpos($homeContent, '];', $faqArrayStart)
    : false;

$faqCount = 0;
if ($faqArrayStart !== false && $faqArrayEnd !== false) {
    $faqBlock = substr($homeContent, $faqArrayStart, $faqArrayEnd - $faqArrayStart);
    // Each FAQ entry opens with: ['Question
    preg_match_all("/\['/", $faqBlock, $faqEntryMatches);
    $faqCount = count($faqEntryMatches[0]);
}

// Fallback to pattern count if block extraction failed
if ($faqCount === 0) {
    $faqCount = $faqCountByPattern;
}

$faqBugPresent = ($faqCount < 15);
$faqEvidence   = $faqBugPresent
    ? "Confirmed: FAQ array contains {$faqCount} entries (expected ≥ 15). "
      . "accordion-item occurrences in source: {$accordionItemCount}."
    : "NOT expected: FAQ array contains {$faqCount} entries — already has 15+ items.";

assertBugPresent(
    'DEFECT-3',
    'Incomplete FAQ: fewer than 15 accordion items in views/home/index.php',
    $faqBugPresent,
    $faqEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 4 — Native dialog: data-confirm present on Begin Verification form in admin show.php
// ---------------------------------------------------------------------------

// The bug: the Begin Verification action uses data-confirm (triggers window.confirm)
// instead of a Bootstrap modal.
$dataConfirmFound = str_contains($adminContent, 'data-confirm');

// Also confirm the specific under_verification form (not a remark modal) carries it
$underVerifFormPos = strpos($adminContent, 'under_verification');
$dataConfirmEvidence = '';

if ($dataConfirmFound) {
    // Extract the specific line(s) with data-confirm
    $lines = explode("\n", $adminContent);
    $dcLines = [];
    foreach ($lines as $lineNum => $line) {
        if (str_contains($line, 'data-confirm')) {
            $dcLines[] = sprintf("Line %d: %s", $lineNum + 1, trim($line));
        }
    }
    $dataConfirmEvidence = "Confirmed: 'data-confirm' attribute found in views/admin/requests/show.php\n  "
        . implode("\n  ", $dcLines);
} else {
    $dataConfirmEvidence = "NOT found: 'data-confirm' attribute absent from views/admin/requests/show.php — bug may already be fixed";
}

// Also verify there is NO #modal-under_verification on unfixed code
$modalUnderVerifPresent = str_contains($adminContent, 'modal-under_verification');
if (!$modalUnderVerifPresent) {
    $dataConfirmEvidence .= "\n  Confirmed: #modal-under_verification Bootstrap modal is NOT present (unfixed).";
} else {
    $dataConfirmEvidence .= "\n  Note: #modal-under_verification was found — partial fix may already exist.";
}

assertBugPresent(
    'DEFECT-4',
    'Native dialog on Begin Verification: data-confirm attribute present in admin show.php (no Bootstrap modal)',
    $dataConfirmFound,
    $dataConfirmEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 5 — Generic terminal message (Admin): "terminal state" text present
// ---------------------------------------------------------------------------

$adminTerminalGeneric = str_contains($adminContent, 'terminal state');
$adminTerminalLines   = [];
if ($adminTerminalGeneric) {
    foreach (explode("\n", $adminContent) as $lineNum => $line) {
        if (str_contains($line, 'terminal state')) {
            $adminTerminalLines[] = sprintf("Line %d: %s", $lineNum + 1, trim($line));
        }
    }
}

// Also confirm status-specific messages are NOT present
$adminAlreadyCompleted = str_contains($adminContent, 'Already Completed');
$adminReqCancelled     = str_contains($adminContent, 'Request Cancelled');

$adminTerminalEvidence = $adminTerminalGeneric
    ? "Confirmed: Generic 'terminal state' message found in views/admin/requests/show.php\n  "
      . implode("\n  ", $adminTerminalLines)
      . ($adminAlreadyCompleted ? "\n  Note: 'Already Completed' text also present (partial fix?)" : "\n  Confirmed: 'Already Completed' text is absent.")
      . ($adminReqCancelled     ? "\n  Note: 'Request Cancelled' text also present (partial fix?)"  : "\n  Confirmed: 'Request Cancelled' text is absent.")
    : "NOT found: 'terminal state' text absent — bug may already be fixed";

assertBugPresent(
    'DEFECT-5',
    'Generic terminal message (Admin): "terminal state" text in actions panel, no status-specific messages',
    $adminTerminalGeneric,
    $adminTerminalEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 6 — Generic terminal message (Staff): "terminal state" text present
// ---------------------------------------------------------------------------

$staffTerminalGeneric = str_contains($staffContent, 'terminal state');
$staffTerminalLines   = [];
if ($staffTerminalGeneric) {
    foreach (explode("\n", $staffContent) as $lineNum => $line) {
        if (str_contains($line, 'terminal state')) {
            $staffTerminalLines[] = sprintf("Line %d: %s", $lineNum + 1, trim($line));
        }
    }
}

$staffAlreadyCompleted = str_contains($staffContent, 'Already Completed');
$staffReqCancelled     = str_contains($staffContent, 'Request Cancelled');

$staffTerminalEvidence = $staffTerminalGeneric
    ? "Confirmed: Generic 'terminal state' message found in views/staff/requests/show.php\n  "
      . implode("\n  ", $staffTerminalLines)
      . ($staffAlreadyCompleted ? "\n  Note: 'Already Completed' text also present (partial fix?)" : "\n  Confirmed: 'Already Completed' text is absent.")
      . ($staffReqCancelled     ? "\n  Note: 'Request Cancelled' text also present (partial fix?)"  : "\n  Confirmed: 'Request Cancelled' text is absent.")
    : "NOT found: 'terminal state' text absent — bug may already be fixed";

assertBugPresent(
    'DEFECT-6',
    'Generic terminal message (Staff): "terminal state" text in actions panel, no status-specific messages',
    $staffTerminalGeneric,
    $staffTerminalEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 7 — Missing terminal card (Resident): no completed/cancelled card in right column
// ---------------------------------------------------------------------------

// Isolate the right-column block in resident show.php
// The right column starts at: <div class="col-12 col-lg-4">
$rightColStart = strpos($residentContent, 'col-12 col-lg-4');
$rightColBlock = $rightColStart !== false
    ? substr($residentContent, $rightColStart)
    : $residentContent;

// On unfixed code, the right column only has the ready_for_claiming card.
// Bug exists when there is NO card for 'completed' and NO card for 'cancelled' in the right column.
$completedCardPresent   = str_contains($rightColBlock, "strtolower(\$status) === 'completed'")
                       || str_contains($rightColBlock, "Request Completed");
$cancelledCardPresent   = str_contains($rightColBlock, "strtolower(\$status) === 'cancelled'")
                       || str_contains($rightColBlock, "Request Cancelled");

$residentTerminalBug = !$completedCardPresent && !$cancelledCardPresent;

$residentEvidence = $residentTerminalBug
    ? "Confirmed: No 'completed' or 'cancelled' status card found in the right column of views/resident/requests/show.php\n"
      . "  Right column only contains the 'ready_for_claiming' Claiming Instructions card.\n"
      . "  'completed' card present: " . ($completedCardPresent ? 'yes' : 'no') . "\n"
      . "  'cancelled' card present: " . ($cancelledCardPresent ? 'yes' : 'no')
    : "NOT expected: Status card(s) already present in Resident right column — bug may already be fixed\n"
      . "  'completed' card present: " . ($completedCardPresent ? 'yes' : 'no') . "\n"
      . "  'cancelled' card present: " . ($cancelledCardPresent ? 'yes' : 'no');

assertBugPresent(
    'DEFECT-7',
    'Missing terminal card (Resident): no completed/cancelled status card in right column of resident show.php',
    $residentTerminalBug,
    $residentEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------

$divider = str_repeat('─', 72);
$total   = $passed + $failed;

echo "\n";
echo "╔" . str_repeat("═", 70) . "╗\n";
echo "║  BUG CONDITION EXPLORATION TEST — eMunicipal Nav/Sidebar/FAQ Fixes  ║\n";
echo "╚" . str_repeat("═", 70) . "╝\n";
echo "\n";
echo "Goal: Confirm each defect EXISTS in the unfixed codebase.\n";
echo "PASS = bug confirmed present (expected).\n";
echo "FAIL = bug NOT found (unexpected — code may already be partially fixed).\n";
echo "\n";
echo $divider . "\n";

foreach ($results as $r) {
    $icon = $r['status'] === 'PASS' ? '✓' : '✗';
    echo sprintf("\n[%s] %s  %s\n", $r['status'], $icon, $r['id']);
    echo "     " . $r['description'] . "\n";
    echo "\n     Evidence:\n";
    // Indent each line of evidence
    foreach (explode("\n", $r['evidence']) as $evidenceLine) {
        echo "     " . $evidenceLine . "\n";
    }
    echo $divider . "\n";
}

echo "\n";
echo "Results: {$passed}/{$total} bug conditions confirmed.\n";

if ($failed === 0) {
    echo "\n✓  ALL BUG CONDITIONS CONFIRMED — every defect is present in the unfixed code.\n";
    echo "   This is the expected outcome. Proceed with implementing the fixes.\n\n";
    exit(0);
} else {
    echo "\n✗  {$failed} bug condition(s) NOT found — unexpected result on unfixed code.\n";
    echo "   Investigate whether those defects are already partially fixed,\n";
    echo "   or whether the file paths / detection patterns need updating.\n\n";
    exit(1);
}
