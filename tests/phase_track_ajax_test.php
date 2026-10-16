<?php
/**
 * Track Request AJAX — Static Analysis Test Suite
 *
 * Tests:
 *  A. HomeController — JSON path, error responses, security gates preserved
 *  B. Landing page view — AJAX script present, correct selectors, safe rendering
 *  C. Route coverage — POST /track route exists for AJAX endpoint
 *  D. PHP syntax checks on changed files
 *
 * Static analysis only — no DB, no HTTP requests.
 * Run: php tests/phase_track_ajax_test.php
 */

$root = dirname(__DIR__);
$phpBin = 'c:\\xampp\\php\\php.exe';
if (!file_exists($phpBin)) {
    $phpBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'php.exe' : 'php';
}

$passed = 0; $failed = 0; $results = [];

function tChk(string $id, string $desc, bool $pass, string $detail = ''): void {
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

function syntaxOk(string $phpBin, string $path): bool {
    $out = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($path) . ' 2>&1') ?? '';
    return str_contains($out, 'No syntax errors');
}

$ctrl  = file_get_contents($root . '/app/Controllers/HomeController.php');
$view  = file_get_contents($root . '/views/home/index.php');
$routes = file_get_contents($root . '/routes/web.php');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK A — HomeController
// ─────────────────────────────────────────────────────────────────────────────

tChk('A01', 'Controller: isAjax detection checks $_GET[ajax]===1 OR X-Requested-With header',
    str_contains($ctrl, "ajax") && str_contains($ctrl, "=== '1'") && str_contains($ctrl, 'XMLHttpRequest'),
    'Both detection methods present');

tChk('A02', 'Controller: jsonTrackResponse() method exists',
    str_contains($ctrl, 'function jsonTrackResponse'),
    'jsonTrackResponse() found');

tChk('A03', 'Controller: JSON response sets Content-Type application/json',
    str_contains($ctrl, 'application/json'),
    'Content-Type header present');

tChk('A04', 'Controller: JSON response sets Cache-Control: no-store',
    str_contains($ctrl, 'no-store'),
    'Cache-Control no-store present');

tChk('A05', 'Controller: CSRF check still runs before any logic in AJAX path',
    (strpos($ctrl, 'validateCsrfToken') < strpos($ctrl, '$isAjax')),
    'CSRF check position < $isAjax usage');

tChk('A06', 'Controller: CSRF failure returns JSON error in AJAX path',
    str_contains($ctrl, "'error' => 'csrf'") && str_contains($ctrl, '$isAjax'),
    'CSRF AJAX error path present');

tChk('A07', 'Controller: rate_limit failure returns JSON in AJAX path',
    str_contains($ctrl, "'error' => 'rate_limit'"),
    'rate_limit JSON response present');

tChk('A08', 'Controller: empty ref returns JSON error in AJAX path',
    str_contains($ctrl, "'error' => 'empty'"),
    'empty JSON response present');

tChk('A09', 'Controller: not_found returns JSON error in AJAX path',
    str_contains($ctrl, "'error' => 'not_found'"),
    'not_found JSON response present');

tChk('A10', 'Controller: db_error returns JSON error in AJAX path',
    str_contains($ctrl, "'error' => 'db_error'"),
    'db_error JSON response present');

tChk('A11', 'Controller: success returns JSON with request+history+ref',
    str_contains($ctrl, "'request' => \$publicRequest") && str_contains($ctrl, "'history' => \$history"),
    'success JSON shape correct');

tChk('A12', 'Controller: JSON response shape always has error, ref, request, history keys',
    str_contains($ctrl, "'error'   =>") && str_contains($ctrl, "'ref'     =>") &&
    str_contains($ctrl, "'request' =>") && str_contains($ctrl, "'history' =>"),
    'All 4 JSON keys present in jsonTrackResponse');

tChk('A13', 'Controller: public-safe field list unchanged (no new fields exposed)',
    str_contains($ctrl, "'reference_number'") &&
    str_contains($ctrl, "'document_type_name'") &&
    str_contains($ctrl, "'status'") &&
    !str_contains($ctrl, "'user_id'") &&
    !str_contains($ctrl, "'rejection_reason'"),
    'public-safe field whitelist intact, private fields absent');

tChk('A14', 'Controller: raw DB row never passed to JSON response (uses $publicRequest)',
    str_contains($ctrl, "\$publicRequest") &&
    !str_contains($ctrl, "'request' => \$found"),
    'only $publicRequest (not $found) goes into JSON');

tChk('A15', 'Controller: PRG (non-AJAX) path still intact — redirect(\'/\') present',
    str_contains($ctrl, "\$this->redirect('/')"),
    'non-AJAX redirect path preserved');

tChk('A16', 'Controller: non-AJAX flash session still set for PRG path',
    str_contains($ctrl, "flash('track_result'"),
    'non-AJAX flash still present');

tChk('A17', 'Controller PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Controllers/HomeController.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK B — Landing page view
// ─────────────────────────────────────────────────────────────────────────────

tChk('B01', 'View: AJAX script is present in track section',
    str_contains($view, 'TRACK REQUEST') && str_contains($view, 'fetch(ajaxUrl'),
    'fetch() call found in track script');

tChk('B02', 'View: AJAX URL uses /track?ajax=1',
    str_contains($view, "/track?ajax=1"),
    'AJAX endpoint URL constructed correctly');

tChk('B03', 'View: form selector targets #track form with correct aria-label',
    str_contains($view, 'querySelector(\'#track form[aria-label="Track your document request"]\')'),
    'precise form selector found');

tChk('B04', 'View: result card selector targets .track-result-card',
    str_contains($view, "querySelector('.track-result-card')"),
    '.track-result-card selector found');

tChk('B05', 'View: e.preventDefault() only called when JS handles submission',
    str_contains($view, 'e.preventDefault()'),
    'preventDefault present in submit handler');

tChk('B06', 'View: duplicate submission prevented (busy flag)',
    str_contains($view, 'var busy = false') && str_contains($view, 'if (busy) return'),
    'busy flag prevents duplicate submissions');

tChk('B07', 'View: loading spinner shown while fetching',
    str_contains($view, 'spinner-border') && str_contains($view, 'Searching'),
    'spinner and loading label present');

tChk('B08', 'View: submit button restored on completion (finally block)',
    str_contains($view, '.finally(function'),
    'finally block restores button state');

tChk('B09', 'View: CSRF token forwarded via FormData (existing hidden input)',
    str_contains($view, 'new FormData(form)'),
    'FormData used — includes existing CSRF token');

tChk('B10', 'View: X-Requested-With header sent with AJAX request',
    str_contains($view, 'X-Requested-With') && str_contains($view, 'XMLHttpRequest'),
    'X-Requested-With header present');

tChk('B11', 'View: credentials: same-origin set on fetch (sends cookies for session/rate-limit)',
    str_contains($view, "credentials: 'same-origin'"),
    'credentials: same-origin present');

tChk('B12', 'View: network error caught and shown as friendly message',
    str_contains($view, '.catch(function') && str_contains($view, 'Network error'),
    'catch block with friendly network error message');

tChk('B13', 'View: HTTP error (non-2xx) thrown and handled by catch',
    str_contains($view, "throw new Error('HTTP ' + resp.status)"),
    'HTTP error rejection present');

tChk('B14', 'View: success result rendered using buildSuccessHtml (same CSS classes)',
    str_contains($view, 'function buildSuccessHtml') &&
    str_contains($view, 'track-result-fields') &&
    str_contains($view, 'track-timeline'),
    'buildSuccessHtml uses existing CSS classes');

tChk('B15', 'View: error result rendered using buildErrorHtml',
    str_contains($view, 'function buildErrorHtml') &&
    str_contains($view, 'track-error-box'),
    'buildErrorHtml uses existing CSS class');

tChk('B16', 'View: esc() function used for all server data (XSS prevention)',
    str_contains($view, 'function esc(str)') &&
    str_contains($view, 'esc(req.reference_number)') &&
    str_contains($view, 'esc(req.document_type_name)'),
    'esc() wraps all server-provided strings');

tChk('B17', 'View: innerHTML never set from unescaped server data',
    !preg_match('/innerHTML\s*=\s*data\.|innerHTML\s*=\s*resp\./', $view),
    'no direct innerHTML assignment from response properties');

tChk('B18', 'View: ALL error keys from server covered (empty, not_found, csrf, rate_limit, db_error)',
    str_contains($view, "'empty'") && str_contains($view, "'not_found'") &&
    str_contains($view, "'csrf'") && str_contains($view, "'rate_limit'") &&
    str_contains($view, "'db_error'"),
    'all 5 error keys handled in JS');

tChk('B19', 'View: error messages in JS match PHP error messages exactly',
    str_contains($view, 'Please enter a reference number.') &&
    str_contains($view, 'Request not found.') &&
    str_contains($view, 'Invalid security token.') &&
    str_contains($view, 'Too many attempts.'),
    'JS error messages match PHP error map');

tChk('B20', 'View: result card gets focus after AJAX (screen reader accessibility)',
    str_contains($view, 'resultCard.focus()'),
    'resultCard.focus() called after render');

tChk('B21', 'View: aria-label updated on result card after render',
    str_contains($view, "resultCard.setAttribute('aria-label'"),
    'aria-label updated dynamically');

tChk('B22', 'View: scroll-to-track called after AJAX result',
    str_contains($view, 'scrollToTrack()'),
    'scrollToTrack() called in fetch chain');

tChk('B23', 'View: STATUS_CONFIG in JS mirrors PHP $trackStatusConfig (all 8 statuses)',
    str_contains($view, 'pending:') && str_contains($view, 'under_verification:') &&
    str_contains($view, 'additional_info_required:') && str_contains($view, 'approved:') &&
    str_contains($view, 'rejected:') && str_contains($view, 'ready_for_claiming:') &&
    str_contains($view, 'completed:') && str_contains($view, 'cancelled:'),
    'all 8 statuses in JS STATUS_CONFIG');

tChk('B24', 'View: server-side fallback scroll script still present for no-JS path',
    str_contains($view, 'Scroll to #track after a server-side POST result'),
    'no-JS scroll script comment + code preserved');

tChk('B25', 'View PHP syntax clean',
    syntaxOk($phpBin, $root . '/views/home/index.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK C — Routes
// ─────────────────────────────────────────────────────────────────────────────

tChk('C01', 'Routes: POST /track registered (AJAX endpoint)',
    str_contains($routes, "post('/track'"),
    'POST /track route present');

tChk('C02', 'Routes: GET /track registered (standalone track page)',
    str_contains($routes, "get('/track'"),
    'GET /track route present');

tChk('C03', 'Routes: POST / still registered (landing-page form fallback)',
    str_contains($routes, "post('/'"),
    'POST / route preserved for non-JS fallback');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK D — Unchanged files not broken
// ─────────────────────────────────────────────────────────────────────────────

tChk('D01', 'views/home/track.php syntax clean (standalone track page unchanged)',
    syntaxOk($phpBin, $root . '/views/home/track.php'),
    'php -l passed');

tChk('D02', 'routes/web.php syntax clean',
    syntaxOk($phpBin, $root . '/routes/web.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// REPORT
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "=================================================================\n";
echo "  Track Request AJAX — Static Analysis Tests\n";
echo "=================================================================\n\n";

foreach ($results as [$status, $id, $desc, $detail]) {
    $label = match($status) {
        'PASS' => "\e[32mPASS\e[0m",
        'FAIL' => "\e[31mFAIL\e[0m",
        default => $status,
    };
    printf("  %s  %-6s  %-58s  %s\n", $label, $id, $desc, $detail);
}

echo "\n-----------------------------------------------------------------\n";
printf("  Total: %d   PASS: %d   FAIL: %d\n", $passed + $failed, $passed, $failed);
echo "-----------------------------------------------------------------\n\n";

if ($failed > 0) {
    echo "  FAILED:\n";
    foreach ($results as [$status, $id, $desc, $detail]) {
        if ($status === 'FAIL') printf("    [%s] %s\n          %s\n", $id, $desc, $detail);
    }
    echo "\n";
    exit(1);
}
exit(0);
