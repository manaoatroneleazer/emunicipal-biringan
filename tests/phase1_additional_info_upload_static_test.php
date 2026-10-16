<?php
/**
 * Static-analysis test — PHASE 1: Additional Information Upload
 *
 * Inspects source files for correctness without requiring a running application.
 * Checks every acceptance criterion from the implementation spec.
 *
 * Run:  c:\xampp\php\php.exe tests\phase1_additional_info_upload_static_test.php
 */

$root   = dirname(__DIR__);
$passed = 0;
$failed = 0;

function check(string $description, bool $result): void {
    global $passed, $failed;
    if ($result) {
        echo "[PASS] {$description}\n";
        $passed++;
    } else {
        echo "[FAIL] {$description}\n";
        $failed++;
    }
}

// ---------------------------------------------------------------------------
// Load file contents once
// ---------------------------------------------------------------------------
$notificationModel   = file_get_contents($root . '/app/Models/Notification.php');
$notificationService = file_get_contents($root . '/app/Services/NotificationService.php');
$requestController   = file_get_contents($root . '/app/Controllers/Resident/RequestController.php');
$routes              = file_get_contents($root . '/routes/web.php');
$showView            = file_get_contents($root . '/views/resident/requests/show.php');

// ---------------------------------------------------------------------------
// CHANGE 1 — Notification.php
// ---------------------------------------------------------------------------
echo "\n=== CHANGE 1: Notification.php ===\n";

check(
    'TYPE_ADDITIONAL_INFO_SUBMITTED constant defined',
    str_contains($notificationModel, "TYPE_ADDITIONAL_INFO_SUBMITTED")
);

check(
    "TYPE_ADDITIONAL_INFO_SUBMITTED value is 'additional_info_submitted'",
    str_contains($notificationModel, "'additional_info_submitted'")
);

check(
    'Comment block updated to include Req 25.14',
    str_contains($notificationModel, '25.14')
);

check(
    'TYPE_NEW_REQUEST_STAFF still present (not removed)',
    str_contains($notificationModel, 'TYPE_NEW_REQUEST_STAFF')
);

check(
    'TYPE_RESIDENT_CANCELLED still present (not removed)',
    str_contains($notificationModel, 'TYPE_RESIDENT_CANCELLED')
);

// ---------------------------------------------------------------------------
// CHANGE 2 — NotificationService.php
// ---------------------------------------------------------------------------
echo "\n=== CHANGE 2: NotificationService.php ===\n";

check(
    'additionalInfoSubmittedForStaffAdmin method exists',
    str_contains($notificationService, 'additionalInfoSubmittedForStaffAdmin')
);

check(
    'Uses TYPE_ADDITIONAL_INFO_SUBMITTED constant',
    str_contains($notificationService, 'Notification::TYPE_ADDITIONAL_INFO_SUBMITTED')
);

check(
    'Calls activeStaffAdminIds() for fan-out',
    str_contains($notificationService, 'activeStaffAdminIds()')
);

check(
    'Non-fatal: catches Throwable and logs (never rethrows)',
    str_contains($notificationService, "Logger::error('NotificationService::additionalInfoSubmittedForStaffAdmin failed")
);

check(
    'Title is "Additional Information Submitted"',
    str_contains($notificationService, "'Additional Information Submitted'")
);

check(
    'residentCancelledForStaffAdmin still present (not removed)',
    str_contains($notificationService, 'residentCancelledForStaffAdmin')
);

// ---------------------------------------------------------------------------
// CHANGE 3 — RequestController.php
// ---------------------------------------------------------------------------
echo "\n=== CHANGE 3: RequestController.php ===\n";

check(
    'uploadAdditionalDocuments method declared public',
    str_contains($requestController, 'public function uploadAdditionalDocuments(string $id): void')
);

check(
    'CSRF token validation present',
    str_contains($requestController, "validateCsrfToken(\$_POST['_csrf_token'] ?? '')")
);

check(
    'requireRole(resident) called',
    str_contains($requestController, "AuthMiddleware::requireRole('resident')")
);

check(
    'Ownership check: request user_id vs userId',
    str_contains($requestController, "(int)\$request['user_id'] !== \$userId")
);

check(
    'Status guard: checks STATUS_ADDITIONAL_INFO_REQUIRED',
    str_contains($requestController, 'RequestModel::STATUS_ADDITIONAL_INFO_REQUIRED')
);

check(
    'Uses FileUploadService for file handling',
    (substr_count($requestController, 'new FileUploadService()') >= 2) // store() + uploadAdditional
);

check(
    'Calls rdModel->insert() to persist file metadata',
    str_contains($requestController, '$rdModel->insert([')
);

// Critical: status MUST NOT be changed in the new method
// Extract the uploadAdditionalDocuments method body
$methodStart = strpos($requestController, 'public function uploadAdditionalDocuments');
$methodEnd   = strpos($requestController, 'public function track', $methodStart);
$methodBody  = substr($requestController, $methodStart, $methodEnd - $methodStart);

check(
    'CRITICAL: requestModel->update() is ABSENT in uploadAdditionalDocuments (status not changed)',
    !str_contains($methodBody, '$requestModel->update')
);

check(
    'Audit log written per file (ACTION_FILE_UPLOADED)',
    str_contains($methodBody, 'AuditLog::ACTION_FILE_UPLOADED')
);

check(
    'NotificationService::additionalInfoSubmittedForStaffAdmin called',
    str_contains($methodBody, 'NotificationService::additionalInfoSubmittedForStaffAdmin(')
);

check(
    'Cleans up stored files on DB persist failure (rollback)',
    str_contains($methodBody, "foreach (\$validFiles as \$vf)")
    && str_contains($methodBody, "\$uploadService->delete(\$storageRoot, \$vf['storage_path'])")
);

check(
    'uploadAdditionalDocuments is positioned BEFORE track()',
    $methodStart < strpos($requestController, 'public function track(')
);

check(
    'uploadAdditionalDocuments is positioned AFTER cancel()',
    strpos($requestController, 'public function cancel(') < $methodStart
);

check(
    'Flash success message includes file count',
    str_contains($methodBody, 'additional {$noun} uploaded successfully')
);

// ---------------------------------------------------------------------------
// CHANGE 4 — routes/web.php
// ---------------------------------------------------------------------------
echo "\n=== CHANGE 4: routes/web.php ===\n";

check(
    "Route POST /resident/requests/{id}/documents defined",
    str_contains($routes, "'/resident/requests/{id}/documents'")
);

check(
    'Route points to uploadAdditionalDocuments action',
    str_contains($routes, "uploadAdditionalDocuments")
);

check(
    'Cancel route still present (not removed)',
    str_contains($routes, "'/resident/requests/{id}/cancel'")
);

// New route must appear after cancel route
$cancelPos    = strpos($routes, '/{id}/cancel');
$documentsPos = strpos($routes, '/{id}/documents');
check(
    'New documents route appears after cancel route',
    $documentsPos > $cancelPos
);

// ---------------------------------------------------------------------------
// CHANGE 5 — views/resident/requests/show.php
// ---------------------------------------------------------------------------
echo "\n=== CHANGE 5: views/resident/requests/show.php ===\n";

check(
    '$uploadErrors variable initialized with null-coalescing',
    str_contains($showView, '$uploadErrors = $uploadErrors ?? []')
);

check(
    'Upload form card present in view',
    str_contains($showView, 'Upload Additional Documents')
);

check(
    'Upload form has correct action URL with {id}/documents',
    str_contains($showView, '/documents"')
);

check(
    'Form field name is additional_documents[]',
    str_contains($showView, "name=\"additional_documents[]\"")
);

check(
    'Form has enctype="multipart/form-data"',
    str_contains($showView, 'enctype="multipart/form-data"')
);

check(
    'CSRF token hidden input present in upload form',
    str_contains($showView, 'name="_csrf_token"')
);

check(
    'Upload form only shown when status is additional_info_required',
    str_contains($showView, "strtolower(\$status) === 'additional_info_required'")
);

check(
    'Upload button uses btn-warning class',
    str_contains($showView, 'btn btn-warning')
);

check(
    '$uploadErrors alert block present (for upload errors)',
    str_contains($showView, '!empty($uploadErrors)')
);

check(
    'Alert text updated to "using the form below" (not old text)',
    str_contains($showView, 'upload the requested documents using the form below')
);

check(
    'Old alert text "upload any additional documents requested" is GONE',
    !str_contains($showView, 'upload any additional documents requested')
);

check(
    'File accepts PDF/JPG/PNG via accept attribute',
    str_contains($showView, 'accept=".pdf,.jpg,.jpeg,.png"')
);

check(
    'multiple attribute present on file input',
    str_contains($showView, 'multiple')
);

// ---------------------------------------------------------------------------
// Summary
// ---------------------------------------------------------------------------
echo "\n" . str_repeat('=', 60) . "\n";
echo "Results: {$passed} passed, {$failed} failed\n";
echo str_repeat('=', 60) . "\n";

exit($failed > 0 ? 1 : 0);
