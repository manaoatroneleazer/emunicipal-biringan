<?php

namespace Emunicipal\Controllers;

use Emunicipal\Models\DocumentType;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\RequestStatusHistory;
use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Helpers\SessionHelper;
use Emunicipal\Services\EmailService;
use Emunicipal\Core\Logger;

/**
 * HomeController — public-facing pages.
 *
 * Handles the public homepage and placeholder pages.
 * Complete UI implementation follows design.md.
 */
class HomeController extends BaseController
{
    public function index(): void
    {
        // Load active document types for the services overview on homepage.
        // Graceful fallback: if DB is unavailable, render with empty array.
        $documentTypes = [];
        try {
            $documentTypes = (new DocumentType())->allActive();
        } catch (\Throwable $e) {
            // DB not yet set up — homepage still renders with placeholder cards
        }

        // PRG: read one-shot flash data from a prior POST / submission.
        // getFlash() reads and immediately deletes, so a page refresh gets nothing.
        $trackFlash = SessionHelper::getFlash('track_result');

        $this->renderWithLayout('home/index', [
            'title'         => 'eMunicipal — Biringan City Online Document Request',
            'description'   => 'Request community documents online from the Biringan City Administration — eMunicipal Academic Demonstration System.',
            'documentTypes' => $documentTypes,
            // Track result from flash (null on fresh load or after refresh)
            'trackRef'      => $trackFlash['ref']     ?? null,
            'trackRequest'  => $trackFlash['request'] ?? null,
            'trackHistory'  => $trackFlash['history'] ?? [],
            'trackError'    => $trackFlash['error']   ?? null,
        ]);
    }

    public function about(): void
    {
        $this->renderWithLayout('home/placeholder', [
            'title'   => 'About — eMunicipal',
            'heading' => 'About eMunicipal',
        ]);
    }

    public function services(): void
    {
        $documentTypes = [];
        try {
            $documentTypes = (new DocumentType())->allActive();
        } catch (\Throwable $e) {
            // graceful fallback
        }

        $this->renderWithLayout('home/placeholder', [
            'title'         => 'Services — eMunicipal',
            'heading'       => 'Municipal Services',
            'documentTypes' => $documentTypes,
        ]);
    }

    public function howItWorks(): void
    {
        $this->renderWithLayout('home/placeholder', [
            'title'   => 'How It Works — eMunicipal',
            'heading' => 'How It Works',
        ]);
    }

    public function faqs(): void
    {
        $this->renderWithLayout('home/placeholder', [
            'title'   => 'FAQs — eMunicipal',
            'heading' => 'Frequently Asked Questions',
        ]);
    }

    public function contact(): void
    {
        $this->renderWithLayout('home/placeholder', [
            'title'   => 'Contact — eMunicipal',
            'heading' => 'Contact Us',
        ]);
    }

    // -------------------------------------------------------------------------
    // POLICY PAGES
    // -------------------------------------------------------------------------

    /** GET /policy/privacy */
    public function policyPrivacy(): void
    {
        $this->renderWithLayout('home/policy-privacy', [
            'title'       => 'Privacy Policy — eMunicipal',
            'description' => 'How eMunicipal collects, uses, and protects information in the Biringan City academic demonstration system.',
        ]);
    }

    /** GET /policy/terms */
    public function policyTerms(): void
    {
        $this->renderWithLayout('home/policy-terms', [
            'title'       => 'Terms and Conditions — eMunicipal',
            'description' => 'Terms and conditions governing use of the eMunicipal Biringan City academic demonstration system.',
        ]);
    }

    /** GET /policy/moderation */
    public function policyModeration(): void
    {
        $this->renderWithLayout('home/policy-moderation', [
            'title'       => 'Moderation and Removal Policy — eMunicipal',
            'description' => 'How submissions and uploaded files are reviewed and handled in the eMunicipal academic demonstration system.',
        ]);
    }

    /** GET /policy/copyright */
    public function policyCopyright(): void
    {
        $this->renderWithLayout('home/policy-copyright', [
            'title'       => 'Copyright and Intellectual Property Policy — eMunicipal',
            'description' => 'Intellectual property rights and acceptable use of the eMunicipal academic demonstration system.',
        ]);
    }

    /**
     * POST /contact — handle contact form submission.
     * Returns JSON { success: bool, message: string }
     */
    public function sendContact(): never
    {
        header('Content-Type: application/json; charset=utf-8');

        // CSRF validation
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            echo json_encode(['success' => false, 'message' => 'Invalid security token.']);
            exit;
        }

        // Sanitise inputs — strip tags to prevent header injection
        $name    = strip_tags(trim($_POST['contact_name']    ?? ''));
        $email   = strip_tags(trim($_POST['contact_email']   ?? ''));
        $subject = strip_tags(trim($_POST['contact_subject'] ?? ''));
        $message = strip_tags(trim($_POST['contact_message'] ?? ''));

        // Server-side validation
        $errors = [];
        if ($name    === '' || mb_strlen($name)    > 100)  { $errors[] = 'name'; }
        if (!filter_var($email, FILTER_VALIDATE_EMAIL))     { $errors[] = 'email'; }
        if ($subject === '' || mb_strlen($subject) > 200)  { $errors[] = 'subject'; }
        if ($message === '' || mb_strlen($message) > 3000) { $errors[] = 'message'; }

        if (!empty($errors)) {
            echo json_encode(['success' => false, 'message' => 'Validation failed.', 'errors' => $errors]);
            exit;
        }

        // Owner email from env — never hardcoded
        $ownerEmail = $_ENV['CONTACT_OWNER_EMAIL'] ?? $_ENV['MAIL_FROM_ADDRESS'] ?? '';

        if ($ownerEmail === '') {
            Logger::warning('Contact form: CONTACT_OWNER_EMAIL not configured in .env');
            // Still return success to user (don't expose config issues)
            echo json_encode(['success' => true, 'message' => 'Message received.']);
            exit;
        }

        try {
            EmailService::sendContactMessage($ownerEmail, $name, $email, $subject, $message);
            echo json_encode(['success' => true, 'message' => 'Message sent.']);
        } catch (\Throwable $e) {
            Logger::error('Contact form email error: ' . $e->getMessage());
            echo json_encode(['success' => false, 'message' => 'Failed to send.']);
        }
        exit;
    }

    /**
     * GET /track
     * Public request tracking page.
     * Accepts optional ?ref= query param to pre-fill the input only.
     * Does NOT perform a lookup from GET — lookup requires POST.
     */
    public function trackPage(): void
    {
        // Sanitise optional prefill — trim and cap length only
        $prefillRef = substr(trim($_GET['ref'] ?? ''), 0, 20);

        $this->renderWithLayout('home/track', [
            'title'      => 'Track Your Request — eMunicipal',
            'prefillRef' => $prefillRef,
            'request'    => null,
            'history'    => [],
            'error'      => null,
        ]);
    }

    /**
     * POST /track  (and POST / for landing-page form)
     * Public request tracking lookup.
     *
     * Security:
     *  - CSRF validated (Req 14.3)
     *  - Session-based rate limit: 20 attempts per 10-minute window per session
     *  - PDO prepared statement for DB query (Req 14.2)
     *  - Output escaping in view (Req 14.4)
     *  - Generic error messages — no internal detail exposed
     *
     * Rate limit note: session-based, not IP-based. A user who clears
     * cookies gets a fresh counter. Accepted for academic scope.
     *
     * Ajax path: when the request carries ?ajax=1 (added by the landing-page
     * JavaScript fetch handler), the same security gates run and the method
     * returns JSON instead of a redirect+flash. The caller never receives
     * raw exception messages or database details.
     */
    public function trackLookup(): void
    {
        // Detect whether this is an AJAX fetch call from the landing page JS.
        // Only the landing-page JS adds ?ajax=1 to the POST URL.
        $isAjax = (($_GET['ajax'] ?? '') === '1')
               || (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest');

        // -- 1. CSRF --
        if (!SecurityHelper::validateCsrfToken($_POST['_csrf_token'] ?? '')) {
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'csrf']);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => '', 'error' => 'csrf', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        // -- 2. Rate limiting --
        SessionHelper::start();
        $now          = time();
        $windowStart  = (int) SessionHelper::get('_track_window_start', 0);
        $attempts     = (int) SessionHelper::get('_track_attempts', 0);
        $windowLength = 600; // 10 minutes
        $maxAttempts  = 20;

        if ($now - $windowStart >= $windowLength) {
            // Window expired -- reset
            SessionHelper::set('_track_window_start', $now);
            SessionHelper::set('_track_attempts', 0);
            $attempts = 0;
        }

        if ($attempts >= $maxAttempts) {
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'rate_limit']);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => trim($_POST['ref'] ?? ''), 'error' => 'rate_limit', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        // Increment counter before DB work (counts even invalid attempts)
        SessionHelper::set('_track_attempts', $attempts + 1);

        // -- 3. Sanitise input --
        $ref = strtoupper(substr(trim($_POST['ref'] ?? ''), 0, 20));

        // -- 4. Empty check --
        if ($ref === '') {
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'empty']);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => '', 'error' => 'empty', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        // -- 5. Format validation --
        // Pattern derived from Req 21.1 and confirmed from live data: EMU-YYYY-NNNNNN
        if (!preg_match('/^EMU-\d{4}-\d{6}$/', $ref)) {
            // Same generic message as not-found -- no format hint to caller
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'not_found', 'ref' => $ref]);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => $ref, 'error' => 'not_found', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        // -- 6. DB lookup --
        try {
            $requestModel = new RequestModel();
            $found        = $requestModel->findPublicByReferenceNumber($ref);
        } catch (\Throwable $e) {
            Logger::error('trackLookup DB error: ' . $e->getMessage());
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'db_error', 'ref' => $ref]);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => $ref, 'error' => 'db_error', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        if (!$found) {
            if ($isAjax) {
                $this->jsonTrackResponse(['error' => 'not_found', 'ref' => $ref]);
            } else {
                SessionHelper::flash('track_result', [
                    'ref' => $ref, 'error' => 'not_found', 'request' => null, 'history' => [],
                ]);
                $this->redirect('/');
            }
            return;
        }

        // -- 7. Status history --
        $history = [];
        try {
            $history = (new RequestStatusHistory())->publicForRequest((int)$found['id']);
        } catch (\Throwable $e) {
            Logger::error('trackLookup history error: ' . $e->getMessage());
            // Non-fatal -- show request without history
        }

        // -- 8. Build public-safe result -- never pass the raw DB row --
        $publicRequest = [
            'reference_number'   => $found['reference_number'],
            'document_type_name' => $found['document_type_name'],
            'status'             => $found['status'],
            'created_at'         => $found['created_at'],
            'updated_at'         => $found['updated_at'],
            // 'id' intentionally excluded from public result
        ];

        if ($isAjax) {
            $this->jsonTrackResponse([
                'request' => $publicRequest,
                'history' => $history,
                'ref'     => $ref,
            ]);
        } else {
            SessionHelper::flash('track_result', [
                'ref'     => $ref,
                'error'   => null,
                'request' => $publicRequest,
                'history' => $history,
            ]);
            $this->redirect('/');
        }
    }

    /**
     * Emit a JSON response for the AJAX track path and halt.
     *
     * Shape (always the same keys — client checks 'error' for null):
     *   { error: string|null, ref: string, request: object|null, history: array }
     *
     * Never exposes exception messages, DB details, or internal paths.
     *
     * @param array $data  Keys: error (string|null), ref (string), request (array|null), history (array)
     */
    private function jsonTrackResponse(array $data): never
    {
        if (!headers_sent()) {
            header('Content-Type: application/json; charset=utf-8');
            // Prevent the browser from caching tracking results
            header('Cache-Control: no-store, no-cache, must-revalidate');
        }
        echo json_encode([
            'error'   => $data['error']   ?? null,
            'ref'     => $data['ref']     ?? '',
            'request' => $data['request'] ?? null,
            'history' => $data['history'] ?? [],
        ], JSON_UNESCAPED_UNICODE);
        exit;
    }
    /**
     * Shared render helper for GET and POST /track states.
     *
     * @param string      $prefillRef  Value to pre-fill the input
     * @param string|null $error       Error key or null on success
     * @param array|null  $request     Public-safe request fields or null
     * @param array       $history     Public-safe status history entries
     */
    private function renderTrack(
        string  $prefillRef,
        ?string $error,
        ?array  $request,
        array   $history
    ): void {
        // Load active document types for the homepage sections (same as index()).
        $documentTypes = [];
        try {
            $documentTypes = (new DocumentType())->allActive();
        } catch (\Throwable $ex) {
            // Graceful fallback — homepage renders without document list
        }
        $this->renderWithLayout('home/index', [
            'title'          => 'eMunicipal — Biringan City Online Document Request',
            'description'    => 'Request community documents online from the Biringan City Administration — eMunicipal Academic Demonstration System.',
            'documentTypes'  => $documentTypes,
            // Tracking result vars — consumed by the #track section in home/index
            'trackRef'       => $prefillRef,
            'trackRequest'   => $request,
            'trackHistory'   => $history,
            'trackError'     => $error,
        ]);
    }
}
