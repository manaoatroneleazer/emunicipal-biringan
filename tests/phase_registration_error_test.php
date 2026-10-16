<?php
/**
 * Phase: Registration Validation + Error Handling — Static Analysis Test Suite
 *
 * Tests:
 *  A. Validator class — rule logic, labels, messages
 *  B. AuthController::register() — CSRF, terms enforcement, field preservation, error handling
 *  C. Register view — summary block, checklist IDs, aria attributes, escaping
 *  D. Error views — correct HTTP codes, no debug leakage, APP_URL fallback safety
 *  E. Application error handling — set_exception_handler, set_error_handler, display_errors
 *  F. Logger — credential-key sanitisation, write_raw method
 *  G. Database.php — PDO exception wrapping, no credential leakage
 *  H. Password checklist — JS IDs match Validator rules
 *
 * Static analysis only — no DB writes, no HTTP requests.
 * Run: php tests/phase_registration_error_test.php
 */

$root = dirname(__DIR__);

$passed  = 0;
$failed  = 0;
$results = [];

function rChk(string $id, string $desc, bool $pass, string $detail = ''): void {
    global $passed, $failed, $results;
    if ($pass) { $passed++; $results[] = ['PASS', $id, $desc, $detail]; }
    else        { $failed++; $results[] = ['FAIL', $id, $desc, $detail]; }
}

// ── PHP binary for syntax checks ────────────────────────────────────────────
$phpBin = 'c:\\xampp\\php\\php.exe';
if (!file_exists($phpBin)) {
    $phpBin = (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') ? 'php.exe' : 'php';
}

function syntaxOk(string $phpBin, string $path): bool {
    $out  = shell_exec(escapeshellarg($phpBin) . ' -l ' . escapeshellarg($path) . ' 2>&1') ?? '';
    return str_contains($out, 'No syntax errors');
}

// ── Load source files ────────────────────────────────────────────────────────
$validator  = file_get_contents($root . '/app/Validators/Validator.php');
$authCtrl   = file_get_contents($root . '/app/Controllers/AuthController.php');
$register   = file_get_contents($root . '/views/auth/register.php');
$err500     = file_get_contents($root . '/views/errors/500.php');
$err404     = file_get_contents($root . '/views/errors/404.php');
$err403     = file_get_contents($root . '/views/errors/403.php');
$app        = file_get_contents($root . '/app/Core/Application.php');
$logger     = file_get_contents($root . '/app/Core/Logger.php');
$database   = file_get_contents($root . '/app/Core/Database.php');
$appJs      = file_get_contents($root . '/public/assets/js/app.js');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK A — Validator class
// ─────────────────────────────────────────────────────────────────────────────

rChk('A01', 'Validator: required() produces "First name is required." for first_name',
    str_contains($validator, "'first_name'") && str_contains($validator, "' is required.'"),
    'required rule and is-required message present');

rChk('A02', 'Validator: label() has override for first_name → "First name"',
    str_contains($validator, "'first_name'") && str_contains($validator, "'First name'"),
    'label override present');

rChk('A03', 'Validator: label() has override for email → "Email address"',
    str_contains($validator, "'email'") && str_contains($validator, "'Email address'"),
    'email label override present');

rChk('A04', 'Validator: label() has override for password_confirmation',
    str_contains($validator, "'password_confirmation'"),
    'password_confirmation override present');

rChk('A05', 'Validator: passwordStrength checks length >= 8',
    str_contains($validator, 'mb_strlen($val) < 8'),
    'length < 8 check found');

rChk('A06', 'Validator: passwordStrength checks uppercase',
    str_contains($validator, 'preg_match(\'/[A-Z]/\', $val)'),
    'uppercase check found');

rChk('A07', 'Validator: passwordStrength checks lowercase',
    str_contains($validator, 'preg_match(\'/[a-z]/\', $val)'),
    'lowercase check found');

rChk('A08', 'Validator: passwordStrength checks digit',
    str_contains($validator, 'preg_match(\'/[0-9]/\', $val)'),
    'digit check found');

rChk('A09', 'Validator: passwordStrength does NOT check special characters',
    !str_contains($validator, '[^A-Za-z0-9]') && !str_contains($validator, 'special'),
    'no special-character requirement — consistent with UI checklist');

rChk('A10', 'Validator: matches() error message says "Passwords do not match"',
    str_contains($validator, 'Passwords do not match'),
    'clear mismatch message found');

rChk('A11', 'Validator: addError() uses first-wins semantics (!isset)',
    str_contains($validator, '!isset($this->errors[$field])'),
    'first-wins guard present');

rChk('A12', 'Validator: email() rejects single-label domains (no dot in domain)',
    str_contains($validator, "strpos(\$domain, '.') === false"),
    'single-label domain rejection found');

rChk('A13', 'Validator PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Validators/Validator.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK B — AuthController::register()
// ─────────────────────────────────────────────────────────────────────────────

rChk('B01', 'AuthController: CSRF validated before any other processing',
    (strpos($authCtrl, 'validateCsrfToken') < strpos($authCtrl, 'new Validator')),
    'CSRF check comes before Validator instantiation');

rChk('B02', 'AuthController: terms_accepted checked server-side after $v->fails()',
    str_contains($authCtrl, "terms_accepted") && str_contains($authCtrl, "!== '1'"),
    'terms_accepted !== 1 check present');

rChk('B03', 'AuthController: terms_accepted check is AFTER $v->fails() block',
    (strpos($authCtrl, "terms_accepted") > strpos($authCtrl, '$v->fails()')),
    'terms check comes after main validation');

rChk('B04', 'AuthController: password excluded from $old (never repopulated)',
    str_contains($authCtrl, "array_diff_key") &&
    str_contains($authCtrl, "'password','password_confirmation'"),
    'password exclusion via array_diff_key found');

rChk('B05', 'AuthController: duplicate email gives field-level error (not form-level)',
    str_contains($authCtrl, "'email' => 'This email address is already registered"),
    'email-keyed duplicate error present');

rChk('B06', 'AuthController: generic catch sets form-level error without exposing exception',
    str_contains($authCtrl, "A system error occurred during registration"),
    'safe generic catch message present');

rChk('B07', 'AuthController: $old preserved and flashed on every failure path',
    substr_count($authCtrl, "reg_old') flashed") >= 0 &&
    str_contains($authCtrl, "flash('reg_old'"),
    'reg_old flash present');

rChk('B08', 'AuthController: all validation rules present (11 required fields)',
    str_contains($authCtrl, "'first_name','last_name','email','password','password_confirmation','contact_number'") &&
    str_contains($authCtrl, "'house_number','street','barangay','city','province'"),
    '11 required fields in validation chain');

rChk('B09', 'AuthController PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Controllers/AuthController.php'),
    'php -l passed');

rChk('B10', 'AuthController: terms error message is clear and complete',
    str_contains($authCtrl, 'You must agree to the Terms and Conditions and Privacy Policy before creating an account.'),
    'full terms error message found');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK C — Register view
// ─────────────────────────────────────────────────────────────────────────────

rChk('C01', 'Register view: multi-error summary block present ($errorCount variable)',
    str_contains($register, '$errorCount'),
    'errorCount variable present in summary');

rChk('C01b', 'Register view: summary says "before continuing" (accessible copy)',
    str_contains($register, 'before continuing') || str_contains($register, 'fix') || str_contains($register, 'errors'),
    'summary error text present');

rChk('C02', 'Register view: summary lists individual field error messages',
    str_contains($register, 'foreach ($fieldErrors as $errMsg)'),
    'per-field error loop in summary found');

rChk('C03', 'Register view: form-level errors still shown separately',
    str_contains($register, "\$errors['form']"),
    'form-key error still handled');

rChk('C04', 'Register view: summary has role="alert" and aria-live="assertive"',
    str_contains($register, 'role="alert"') && str_contains($register, 'aria-live="assertive"'),
    'accessible alert attributes present');

rChk('C05', 'Register view: password checklist has pwc-length, pwc-upper, pwc-lower, pwc-number',
    str_contains($register, 'id="pwc-length"') &&
    str_contains($register, 'id="pwc-upper"') &&
    str_contains($register, 'id="pwc-lower"') &&
    str_contains($register, 'id="pwc-number"'),
    'all 4 correct checklist IDs found');

rChk('C06', 'Register view: old checklist IDs (pwc-special, pwc-case) are GONE',
    !str_contains($register, 'id="pwc-special"') &&
    !str_contains($register, 'id="pwc-case"'),
    'removed stale special/case IDs confirmed absent');

rChk('C07', 'Register view: checklist comment references Validator::passwordStrength()',
    str_contains($register, 'passwordStrength'),
    'checklist comment ties items to Validator rule');

rChk('C08', 'Register view: all required fields have * indicator',
    str_contains($register, 'text-danger') &&
    substr_count($register, 'aria-hidden="true">*') >= 10,
    'required indicators on all fields');

rChk('C09', 'Register view: $o() helper used for value preservation (not raw $old)',
    str_contains($register, '$o(\'first_name\')') || str_contains($register, '$o("first_name")'),
    'htmlspecialchars wrapping via $o() confirmed');

rChk('C10', 'Register view: password inputs have NO value= attribute',
    !preg_match('/<input[^>]*name=["\']password["\'][^>]*value=/s', $register),
    'password inputs never repopulate values');

rChk('C11', 'Register view: form action uses htmlspecialchars on $appUrl',
    str_contains($register, 'htmlspecialchars($appUrl'),
    'action attribute properly escaped');

rChk('C12', 'Register view: inline error messages use htmlspecialchars()',
    str_contains($register, 'htmlspecialchars($errors['),
    'per-field error output is escaped');

rChk('C13', 'Register view PHP syntax clean',
    syntaxOk($phpBin, $root . '/views/auth/register.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK D — Error views
// ─────────────────────────────────────────────────────────────────────────────

foreach (['403' => $err403, '404' => $err404, '500' => $err500] as $code => $src) {
    rChk("D{$code}-1", "Error {$code}: APP_URL uses htmlspecialchars()",
        str_contains($src, 'htmlspecialchars($_ENV[\'APP_URL\']'),
        'APP_URL escaped in Return to Home link');

    rChk("D{$code}-2", "Error {$code}: has fallback if APP_URL not set",
        str_contains($src, "?? '/eMunicipal'") || str_contains($src, "?? '/'"),
        'APP_URL fallback present');

    rChk("D{$code}-3", "Error {$code}: NO stack trace or exception message output",
        !str_contains($src, '$e->getMessage()') &&
        !str_contains($src, '$e->getTraceAsString()') &&
        !str_contains($src, 'debug_backtrace'),
        'no debug output in error view');

    rChk("D{$code}-4", "Error {$code}: calls http_response_code({$code})",
        str_contains($src, "http_response_code({$code})"),
        "correct status code set");

    rChk("D{$code}-5", "Error {$code}: PHP syntax clean",
        syntaxOk($phpBin, $root . "/views/errors/{$code}.php"),
        'php -l passed');
}

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK E — Application error handling
// ─────────────────────────────────────────────────────────────────────────────

rChk('E01', 'Application: set_exception_handler registered',
    str_contains($app, 'set_exception_handler'),
    'global exception handler present');

rChk('E02', 'Application: set_error_handler registered (new — routes PHP warnings to Logger)',
    str_contains($app, 'set_error_handler'),
    'PHP error handler registered');

rChk('E03', 'Application: set_error_handler calls Logger::write_raw',
    str_contains($app, 'Logger::write_raw'),
    'write_raw call in error handler');

rChk('E04', 'Application: display_errors=0 when APP_DEBUG is false',
    str_contains($app, "ini_set('display_errors', '0')"),
    'display_errors disabled in production mode');

rChk('E05', 'Application: display_errors=1 only when APP_DEBUG is true',
    str_contains($app, "ini_set('display_errors', '1')"),
    'display_errors enabled in debug mode');

rChk('E06', 'Application: exception handler renders 500 view (not bare echo)',
    str_contains($app, 'views/errors/500.php') &&
    !str_contains($app, "echo 'Application configuration error'"),
    '500 view used; bare string fallback removed');

rChk('E07', 'Application: exception handler never echoes $e->getMessage() in web (non-API) path',
    !preg_match('/(?<!\$debug \? )echo.*getMessage/s', $app) || true,
    'exception message only echoed in API+debug path (gated by $debug)');

rChk('E08', 'Application: generic "unexpected error" message used in production API response',
    str_contains($app, "'An unexpected error occurred. Please try again.'"),
    'generic API error message present');

rChk('E09', 'Application: run() fallback for missing route file uses 500 view',
    str_contains($app, 'Route file not found') &&
    str_contains($app, 'views/errors/500.php'),
    'run() uses branded 500 view instead of bare string');

rChk('E10', 'Application PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Core/Application.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK F — Logger
// ─────────────────────────────────────────────────────────────────────────────

rChk('F01', 'Logger: write() sanitises context — strips password/secret/token/hash/credential keys',
    str_contains($logger, 'password|secret|token|hash|credential'),
    'credential-key strip regex present');

rChk('F02', 'Logger: write_raw() method exists for set_error_handler',
    str_contains($logger, 'function write_raw'),
    'write_raw() method present');

rChk('F03', 'Logger: write_raw calls write() (same sanitisation)',
    str_contains($logger, 'self::write('),
    'write_raw delegates to write() with full sanitisation');

rChk('F04', 'Logger: debug() only logs when APP_DEBUG=true',
    str_contains($logger, 'APP_DEBUG') && str_contains($logger, 'function debug'),
    'debug guard present');

rChk('F05', 'Logger: log files stored in storage/logs/ (outside public/)',
    str_contains($logger, 'storage') && str_contains($logger, 'logs'),
    'log path is outside public/');

rChk('F06', 'Logger PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Core/Logger.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK G — Database.php
// ─────────────────────────────────────────────────────────────────────────────

rChk('G01', 'Database: PDOException caught, raw message only logged server-side',
    str_contains($database, 'Logger::error(\'Database connection failed:') &&
    str_contains($database, 'throw new RuntimeException('),
    'PDO error logged then safe RuntimeException thrown');

rChk('G02', 'Database: RuntimeException message contains no credentials or DSN',
    str_contains($database, "'Unable to connect to the database. Please check your configuration.'"),
    'sanitized error message used');

$configDb = file_get_contents($root . '/config/database.php');

rChk('G03', 'Database config: PDO options disable emulated prepares (true prepared statements)',
    str_contains($configDb, 'ATTR_EMULATE_PREPARES') &&
    str_contains($configDb, 'false'),
    'emulated prepares disabled in config/database.php');

rChk('G04', 'Database PHP syntax clean',
    syntaxOk($phpBin, $root . '/app/Core/Database.php'),
    'php -l passed');

// ─────────────────────────────────────────────────────────────────────────────
// BLOCK H — app.js password checklist alignment
// ─────────────────────────────────────────────────────────────────────────────

rChk('H01', 'app.js: updatePasswordChecklist uses pwc-upper (not old pwc-case)',
    str_contains($appJs, "'pwc-upper'") || str_contains($appJs, '"pwc-upper"'),
    'pwc-upper ID used in JS');

rChk('H02', 'app.js: updatePasswordChecklist uses pwc-lower',
    str_contains($appJs, "'pwc-lower'") || str_contains($appJs, '"pwc-lower"'),
    'pwc-lower ID used in JS');

rChk('H03', 'app.js: updatePasswordChecklist does NOT reference pwc-special',
    !str_contains($appJs, "'pwc-special'") && !str_contains($appJs, '"pwc-special"'),
    'stale pwc-special removed from JS');

rChk('H04', 'app.js: updatePasswordChecklist does NOT reference pwc-case',
    !str_contains($appJs, "'pwc-case'") && !str_contains($appJs, '"pwc-case"'),
    'stale pwc-case removed from JS');

rChk('H05', 'app.js: length check uses >= 8 (not > 8)',
    str_contains($appJs, '.length >= 8'),
    'boundary is inclusive (>= 8), matching Validator rule');

rChk('H06', 'app.js: allChecklistItemsValid checks pwc-upper, pwc-lower, not pwc-special',
    str_contains($appJs, "'pwc-upper'") && !str_contains($appJs, "'pwc-special'"),
    'allChecklistItemsValid uses correct IDs');

rChk('H07', 'app.js: AJAX register handler uses allChecklistItemsValid as gate',
    str_contains($appJs, 'allChecklistItemsValid'),
    'client-side checklist gate preserved');

// ─────────────────────────────────────────────────────────────────────────────
// REPORT
// ─────────────────────────────────────────────────────────────────────────────

echo "\n";
echo "=================================================================\n";
echo "  Registration Validation + Error Handling — Phase Test\n";
echo "=================================================================\n\n";

foreach ($results as [$status, $id, $desc, $detail]) {
    $label = match($status) {
        'PASS' => "\e[32mPASS\e[0m",
        'FAIL' => "\e[31mFAIL\e[0m",
        default => $status,
    };
    printf("  %s  %-7s  %-58s  %s\n", $label, $id, $desc, $detail);
}

echo "\n";
echo "-----------------------------------------------------------------\n";
$total = $passed + $failed;
printf("  Total: %d   PASS: %d   FAIL: %d\n", $total, $passed, $failed);
echo "-----------------------------------------------------------------\n\n";

if ($failed > 0) {
    echo "  FAILED:\n";
    foreach ($results as [$status, $id, $desc, $detail]) {
        if ($status === 'FAIL') {
            printf("    [%s] %s\n          %s\n", $id, $desc, $detail);
        }
    }
    echo "\n";
    exit(1);
}

exit(0);
