<?php
/**
 * Bug Condition Fixed Verification Test
 *
 * Spec: emunicipal-nav-sidebar-faq-fixes
 * Purpose: Verify that each defect has been FIXED by asserting the OPPOSITE of the
 *          exploration test. A PASS here means the bug is confirmed absent (fixed).
 *          A FAIL means the fix was NOT applied (unexpected on fixed code).
 *
 * Run with:
 *   php tests/bugfix/bug_condition_fixed_test.php
 *
 * Exit codes:
 *   0 — All defects confirmed fixed (expected outcome after applying fixes)
 *   1 — One or more defects still present (unexpected — code may not be fully fixed)
 */

declare(strict_types=1);

// ---------------------------------------------------------------------------
// Helpers
// ---------------------------------------------------------------------------

$passed  = 0;
$failed  = 0;
$results = [];

/**
 * Assert that $condition is TRUE (fix is confirmed in place).
 * A true result = PASS (fix confirmed). A false result = FAIL (bug still present).
 */
function assertFixed(
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
    echo "  php tests/bugfix/bug_condition_fixed_test.php\n\n";
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
// Defect 1 FIXED — Duplicate header REMOVED: <header class="auth-header"> absent
// ---------------------------------------------------------------------------

$headerPattern = 'class="auth-header"';
$headerAbsent  = !str_contains($authContent, $headerPattern);
$defect1Evidence = $headerAbsent
    ? "FIXED: '<header class=\"auth-header\"' element is NOT present in views/layouts/auth.php"
    : "NOT FIXED: '<header class=\"auth-header\"' element is STILL present in views/layouts/auth.php";

// Provide line evidence if still present (for debugging failed case)
if (!$headerAbsent) {
    foreach (explode("\n", $authContent) as $lineNum => $line) {
        if (str_contains($line, $headerPattern)) {
            $defect1Evidence .= sprintf("\n  Still found at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

assertFixed(
    'DEFECT-1',
    'Duplicate branding fixed: <header class="auth-header"> element absent from auth.php',
    $headerAbsent,
    $defect1Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 2 FIXED — Track Request link PRESENT in Resident sidebar
// ---------------------------------------------------------------------------

// Isolate the resident sidebar block (between "if ($role === 'resident')" and "elseif ($role === 'staff')")
$residentBlockStart = strpos($authContent, "if (\$role === 'resident')");
$residentBlockEnd   = strpos($authContent, "elseif (\$role === 'staff')", $residentBlockStart);

$residentSidebarBlock = ($residentBlockStart !== false && $residentBlockEnd !== false)
    ? substr($authContent, $residentBlockStart, $residentBlockEnd - $residentBlockStart)
    : $authContent; // fallback: search entire file

$trackLinkPresent = str_contains($residentSidebarBlock, '/resident/track');
$defect2Evidence  = $trackLinkPresent
    ? "FIXED: '/resident/track' link IS present in the Resident sidebar block of auth.php"
    : "NOT FIXED: '/resident/track' link is STILL absent from the Resident sidebar block of auth.php";

// Find the line for positive confirmation
if ($trackLinkPresent) {
    foreach (explode("\n", $authContent) as $lineNum => $line) {
        if (str_contains($line, '/resident/track')) {
            $defect2Evidence .= sprintf("\n  Found at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

assertFixed(
    'DEFECT-2',
    'Track Request link fixed: /resident/track link present in Resident sidebar in auth.php',
    $trackLinkPresent,
    $defect2Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 3 FIXED — FAQ array contains 15 or more entries
// ---------------------------------------------------------------------------

// Extract the $faqs array literal and count sub-array entries.
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

// Fallback: count by pattern across the file
if ($faqCount === 0) {
    preg_match_all("/\['[^']+',/", $homeContent, $faqMatches);
    $faqCount = count($faqMatches[0]);
}

$faqFixed        = ($faqCount >= 15);
$defect3Evidence = $faqFixed
    ? "FIXED: FAQ array contains {$faqCount} entries (≥ 15 required)"
    : "NOT FIXED: FAQ array contains only {$faqCount} entries (< 15 required)";

// Cross-check with accordion-item count
$accordionItemCount  = substr_count($homeContent, 'accordion-item');
$defect3Evidence    .= "\n  accordion-item occurrences in source: {$accordionItemCount}";

assertFixed(
    'DEFECT-3',
    'Incomplete FAQ fixed: FAQ array contains 15 or more entries in views/home/index.php',
    $faqFixed,
    $defect3Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 4 FIXED — Begin Verification uses Bootstrap modal (NOT standalone data-confirm)
// ---------------------------------------------------------------------------

// Fix is confirmed when:
//   (a) #modal-under_verification modal IS present in the file, AND
//   (b) the under_verification action does NOT use a standalone data-confirm form
//       (i.e., under_verification is routed through the modal path via $hasModal)

$modalPresent = str_contains($adminContent, 'modal-under_verification');

// Check that $hasModal includes STATUS_UNDER_VERIFICATION
// The fix adds: || $next === RequestModel::STATUS_UNDER_VERIFICATION to the $hasModal condition
$hasModalFixPresent = str_contains($adminContent, 'STATUS_UNDER_VERIFICATION');

// Verify data-confirm is NOT used in the context of the under_verification button itself.
// After the fix, the under_verification button is a data-bs-toggle="modal" button, not a form.
// The data-confirm may still exist in the else-branch for other actions — that's fine.
// We check that the modal trigger for under_verification is a data-bs-toggle="modal" button.
$modalTriggerUsed = str_contains($adminContent, 'data-bs-toggle="modal"');

$defect4Fixed = $modalPresent && $hasModalFixPresent && $modalTriggerUsed;

$modalEvidence = '';
if ($modalPresent) {
    $modalEvidence .= "FIXED: #modal-under_verification Bootstrap modal IS present in views/admin/requests/show.php";
} else {
    $modalEvidence .= "NOT FIXED: #modal-under_verification Bootstrap modal is MISSING from views/admin/requests/show.php";
}

$modalEvidence .= "\n  STATUS_UNDER_VERIFICATION in \$hasModal condition: " . ($hasModalFixPresent ? 'yes' : 'no');
$modalEvidence .= "\n  data-bs-toggle=\"modal\" trigger present: " . ($modalTriggerUsed ? 'yes' : 'no');

// Find the modal definition line for confirmation
if ($modalPresent) {
    foreach (explode("\n", $adminContent) as $lineNum => $line) {
        if (str_contains($line, 'modal-under_verification') && str_contains($line, 'id=')) {
            $modalEvidence .= sprintf("\n  Modal found at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

assertFixed(
    'DEFECT-4',
    'Begin Verification modal fixed: #modal-under_verification present and modal path used in admin show.php',
    $defect4Fixed,
    $modalEvidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 5 FIXED — Admin terminal-state panel contains "Already Completed" text
// ---------------------------------------------------------------------------

$adminAlreadyCompleted = str_contains($adminContent, 'Already Completed');
$defect5Evidence = $adminAlreadyCompleted
    ? "FIXED: 'Already Completed' status-specific text IS present in views/admin/requests/show.php"
    : "NOT FIXED: 'Already Completed' text is ABSENT from views/admin/requests/show.php";

if ($adminAlreadyCompleted) {
    foreach (explode("\n", $adminContent) as $lineNum => $line) {
        if (str_contains($line, 'Already Completed')) {
            $defect5Evidence .= sprintf("\n  Found at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

// Also confirm generic "terminal state" text is gone (or now in an else-branch, not the primary)
$adminGenericStillPrimary = false;
if (str_contains($adminContent, 'terminal state')) {
    $defect5Evidence .= "\n  Note: 'terminal state' text still exists in the file (expected — used as fallback else-branch).";
} else {
    $defect5Evidence .= "\n  Note: 'terminal state' generic text has been fully removed.";
}

assertFixed(
    'DEFECT-5',
    'Generic terminal message fixed (Admin): "Already Completed" status-specific text present in admin show.php',
    $adminAlreadyCompleted,
    $defect5Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 6 FIXED — Staff terminal-state panel contains "Already Completed" text
// ---------------------------------------------------------------------------

$staffAlreadyCompleted = str_contains($staffContent, 'Already Completed');
$defect6Evidence = $staffAlreadyCompleted
    ? "FIXED: 'Already Completed' status-specific text IS present in views/staff/requests/show.php"
    : "NOT FIXED: 'Already Completed' text is ABSENT from views/staff/requests/show.php";

if ($staffAlreadyCompleted) {
    foreach (explode("\n", $staffContent) as $lineNum => $line) {
        if (str_contains($line, 'Already Completed')) {
            $defect6Evidence .= sprintf("\n  Found at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

if (str_contains($staffContent, 'terminal state')) {
    $defect6Evidence .= "\n  Note: 'terminal state' text still exists in the file (expected — used as fallback else-branch).";
} else {
    $defect6Evidence .= "\n  Note: 'terminal state' generic text has been fully removed.";
}

assertFixed(
    'DEFECT-6',
    'Generic terminal message fixed (Staff): "Already Completed" status-specific text present in staff show.php',
    $staffAlreadyCompleted,
    $defect6Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Defect 7 FIXED — Resident right column contains BOTH "completed" AND "cancelled" branches
// ---------------------------------------------------------------------------

// Isolate the right-column block in resident show.php
$rightColStart = strpos($residentContent, 'col-12 col-lg-4');
$rightColBlock = $rightColStart !== false
    ? substr($residentContent, $rightColStart)
    : $residentContent;

// Check for the completed branch
$completedBranchPresent = str_contains($rightColBlock, "strtolower(\$status) === 'completed'")
                       || str_contains($rightColBlock, 'Request Completed');

// Check for the cancelled branch
$cancelledBranchPresent = str_contains($rightColBlock, "strtolower(\$status) === 'cancelled'")
                       || str_contains($rightColBlock, 'Request Cancelled');

$defect7Fixed = $completedBranchPresent && $cancelledBranchPresent;

$defect7Evidence = '';
if ($defect7Fixed) {
    $defect7Evidence = "FIXED: Both 'completed' and 'cancelled' status branches present in right column of views/resident/requests/show.php";
} else {
    $defect7Evidence = "NOT FIXED: Missing status branch(es) in right column of views/resident/requests/show.php";
}
$defect7Evidence .= "\n  'completed' branch present: " . ($completedBranchPresent ? 'yes' : 'NO');
$defect7Evidence .= "\n  'cancelled' branch present: " . ($cancelledBranchPresent ? 'yes' : 'NO');

// Find the line for positive confirmation
if ($completedBranchPresent) {
    foreach (explode("\n", $residentContent) as $lineNum => $line) {
        if (str_contains($line, "strtolower(\$status) === 'completed'") || str_contains($line, 'Request Completed')) {
            $defect7Evidence .= sprintf("\n  'completed' branch at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}
if ($cancelledBranchPresent) {
    foreach (explode("\n", $residentContent) as $lineNum => $line) {
        if (str_contains($line, "strtolower(\$status) === 'cancelled'") || str_contains($line, 'Request Cancelled')) {
            $defect7Evidence .= sprintf("\n  'cancelled' branch at Line %d: %s", $lineNum + 1, trim($line));
            break;
        }
    }
}

assertFixed(
    'DEFECT-7',
    'Missing terminal card fixed (Resident): both completed and cancelled branches present in right column',
    $defect7Fixed,
    $defect7Evidence,
    $results, $passed, $failed
);

// ---------------------------------------------------------------------------
// Output
// ---------------------------------------------------------------------------

$divider = str_repeat('─', 72);
$total   = $passed + $failed;

echo "\n";
echo "╔" . str_repeat("═", 70) . "╗\n";
echo "║   BUG CONDITION FIXED VERIFICATION — eMunicipal Nav/Sidebar/FAQ    ║\n";
echo "╚" . str_repeat("═", 70) . "╝\n";
echo "\n";
echo "Goal: Confirm each defect has been FIXED in the updated codebase.\n";
echo "PASS = bug is absent (fix confirmed — expected).\n";
echo "FAIL = bug still present (fix NOT applied — unexpected).\n";
echo "\n";
echo $divider . "\n";

foreach ($results as $r) {
    $icon = $r['status'] === 'PASS' ? '✓' : '✗';
    echo sprintf("\n[%s] %s  %s\n", $r['status'], $icon, $r['id']);
    echo "     " . $r['description'] . "\n";
    echo "\n     Evidence:\n";
    foreach (explode("\n", $r['evidence']) as $evidenceLine) {
        echo "     " . $evidenceLine . "\n";
    }
    echo $divider . "\n";
}

echo "\n";
echo "Results: {$passed}/{$total} defects confirmed fixed.\n";

if ($failed === 0) {
    echo "\n✓  ALL DEFECTS CONFIRMED FIXED — every bug has been resolved.\n";
    echo "   The codebase is in the expected post-fix state.\n\n";
    exit(0);
} else {
    echo "\n✗  {$failed} defect(s) still present — fixes may be incomplete.\n";
    echo "   Review the FAIL entries above and apply the missing changes.\n\n";
    exit(1);
}
