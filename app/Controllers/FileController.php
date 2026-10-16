<?php

namespace Emunicipal\Controllers;

use Emunicipal\Middleware\AuthMiddleware;
use Emunicipal\Helpers\UrlHelper;
use Emunicipal\Models\RequestDocument;
use Emunicipal\Models\Request as RequestModel;
use Emunicipal\Models\AuditLog;
use Emunicipal\Services\AuditLogService;
use Emunicipal\Core\Logger;

/**
 * FileController — PHP-controlled secure file download handler.
 *
 * Security requirements implemented:
 *  Req 9.4  — Files served through PHP handler; no direct HTTP access to filesystem
 *  Req 9.5  — storage_path never exposed in URLs or HTTP responses
 *  Req 6.7  — Files stored outside web root (enforced at upload time)
 *  Req 9.4  — Access control: Resident must own the request; Staff/Admin have read access
 *  Req 9.8  — File download recorded in audit_logs
 *  Req 13.1 — Audit event: file_downloaded
 *
 * Route (defined in routes/web.php):
 *  GET /files/{id}  → download(string $id)
 *
 * The {id} is the request_documents.id — never a filesystem path or filename.
 */
class FileController extends BaseController
{
    /**
     * GET /files/{id}
     *
     * Serve the file identified by request_documents.id.
     * Enforces authentication and ownership before streaming the file.
     */
    public function download(string $id): void
    {
        // Authentication required — all roles may access files they are authorised for
        AuthMiddleware::requireAuth();

        $documentId = (int)$id;
        if ($documentId <= 0) {
            $this->abort404();
        }

        $userId = AuthMiddleware::userId();
        $role   = AuthMiddleware::getRole();

        // Load the request_documents record
        $docRecord = null;
        try {
            $docRecord = (new RequestDocument())->find($documentId);
        } catch (\Throwable $e) {
            Logger::error('FileController: DB error loading request_document: ' . $e->getMessage());
            $this->abort500();
        }

        if (!$docRecord) {
            $this->abort404();
        }

        // Load the parent request for ownership check
        $requestRecord = null;
        try {
            $requestRecord = (new RequestModel())->find((int)$docRecord['request_id']);
        } catch (\Throwable $e) {
            Logger::error('FileController: DB error loading request: ' . $e->getMessage());
            $this->abort500();
        }

        if (!$requestRecord) {
            $this->abort404();
        }

        // Access control (Req 9.4):
        //   - Resident: may only download files belonging to their own requests
        //   - Staff or Admin: may download any file
        if ($role === 'resident' && (int)$requestRecord['user_id'] !== $userId) {
            $this->abort403();
        }

        // Resolve absolute filesystem path — never returned to the client
        $fullPath = $this->resolveFilePath($docRecord['storage_path']);

        if ($fullPath === null || !file_exists($fullPath) || !is_file($fullPath)) {
            Logger::error('FileController: file not found on disk', [
                'document_id' => $documentId,
                // Log document_id only — never log the filesystem path
            ]);
            $this->abortFileNotFound();
        }

        // Audit log (Req 9.8, 13.1) — before streaming so the log is written even if the stream fails
        AuditLogService::log(
            $userId,
            $role,
            AuditLog::ACTION_FILE_DOWNLOADED,
            'files',
            'request_documents',
            $documentId,
            null,
            ['request_id' => (int)$docRecord['request_id']],
            "File ID {$documentId} downloaded by user ID {$userId} ({$role})"
        );

        // Stream the file to the client (Req 9.5 — storage_path never in response)
        $this->streamFile($fullPath, $docRecord['original_filename'], $docRecord['mime_type']);
    }

    // -------------------------------------------------------------------------
    // Private helpers
    // -------------------------------------------------------------------------

    /**
     * Resolve the absolute path to a stored file, given its storage_path value from the DB.
     * Returns null if the resolved path would escape the upload root (path traversal guard).
     */
    private function resolveFilePath(string $storagePath): ?string
    {
        $projectRoot = dirname(__DIR__, 2);
        $uploadPath  = $_ENV['UPLOAD_PATH'] ?? 'storage/uploads';

        // Strip any path traversal sequences from the stored path (defence in depth)
        $safePath = str_replace(['../', '..\\.', '..\\'], '', $storagePath);
        $safePath = ltrim($safePath, '/\\');

        $uploadRoot = $projectRoot . DIRECTORY_SEPARATOR
                    . ltrim(str_replace(['../', '..\\'], '', $uploadPath), '/\\');

        $fullPath   = $uploadRoot . DIRECTORY_SEPARATOR . $safePath;

        // Ensure the resolved path is still inside the upload root (path traversal guard)
        $realUpload = realpath($uploadRoot);
        $realFile   = realpath($fullPath);

        if ($realFile === false || $realUpload === false) {
            // realpath returns false for non-existent paths; that's handled by the caller
            // Try without realpath for the file existence check that follows
            return $fullPath;
        }

        if (!str_starts_with($realFile, $realUpload . DIRECTORY_SEPARATOR)
            && $realFile !== $realUpload) {
            Logger::error('FileController: path traversal attempt blocked', [
                'document_storage_path' => '(redacted)',
            ]);
            return null;
        }

        return $fullPath;
    }

    /**
     * Stream a file to the browser as a download.
     * Never exposes the server-side path in any HTTP header.
     */
    private function streamFile(string $fullPath, string $originalFilename, string $mimeType): never
    {
        // Sanitise the filename for Content-Disposition: keep only safe characters
        $safeFilename = preg_replace('/[^\w\-. ]/', '_', basename($originalFilename));
        $safeFilename = $safeFilename ?: 'download';

        // Clear any output buffering that might corrupt binary data
        while (ob_get_level() > 0) {
            ob_end_clean();
        }

        $fileSize = filesize($fullPath);

        header('Content-Type: ' . $mimeType);
        header('Content-Disposition: attachment; filename="' . $safeFilename . '"');
        header('Content-Length: ' . $fileSize);
        header('Cache-Control: private, no-cache, no-store, must-revalidate');
        header('Pragma: no-cache');
        header('X-Content-Type-Options: nosniff');
        // Explicitly remove any X-Powered-By that might leak version info
        header_remove('X-Powered-By');

        readfile($fullPath);
        exit;
    }

    // -------------------------------------------------------------------------
    // Error terminators — send appropriate HTTP response and halt
    // These never reveal filesystem paths or internal detail
    // -------------------------------------------------------------------------

    private function abort403(): never
    {
        http_response_code(403);
        $view = dirname(__DIR__, 2) . '/views/errors/403.php';
        if (file_exists($view)) require $view;
        else echo 'Access denied.';
        exit;
    }

    private function abort404(): never
    {
        http_response_code(404);
        $view = dirname(__DIR__, 2) . '/views/errors/404.php';
        if (file_exists($view)) require $view;
        else echo 'Not found.';
        exit;
    }

    private function abort500(): never
    {
        http_response_code(500);
        $view = dirname(__DIR__, 2) . '/views/errors/500.php';
        if (file_exists($view)) require $view;
        else echo 'Server error.';
        exit;
    }

    /**
     * File exists in the DB but is missing from the filesystem.
     * Return 404 — never reveal whether the file existed.
     */
    private function abortFileNotFound(): never
    {
        http_response_code(404);
        $view = dirname(__DIR__, 2) . '/views/errors/404.php';
        if (file_exists($view)) require $view;
        else echo 'File not found.';
        exit;
    }
}
