<?php

namespace Emunicipal\Services;

use Emunicipal\Helpers\SecurityHelper;
use Emunicipal\Core\Logger;

/**
 * FileUploadService — centralised, secure file upload handling.
 *
 * Security requirements implemented:
 *  Req 6.4  — Accept only PDF, JPEG, PNG
 *  Req 6.5  — Validate MIME type server-side using PHP finfo extension
 *  Req 6.6  — Enforce 5 MB maximum per file
 *  Req 6.7  — Generate a secure UUID-based filename; store outside web root
 *
 * This service handles one file at a time. The controller loops over uploaded files.
 */
class FileUploadService
{
    /** Allowed MIME types (per Req 6.4) */
    private const ALLOWED_MIME_TYPES = [
        'application/pdf' => '.pdf',
        'image/jpeg'      => '.jpg',
        'image/png'       => '.png',
    ];

    /** Default maximum file size in bytes: 5 MB (per Req 6.6). Used as fallback. */
    private const MAX_BYTES = 5_242_880;

    /** Maximum number of files per request submission (Req 6 — practical limit) */
    public const MAX_FILES = 10;

    /**
     * Return the maximum allowed file size in bytes.
     * Reads UPLOAD_MAX_SIZE_MB from environment (set by settings page or .env).
     * Falls back to the 5 MB constant so existing behaviour is unchanged.
     * Enforces a ceiling of 20 MB and a floor of 1 MB (Req 20.3 validation range).
     * Per Req 20.5 / Req 18.3.
     */
    public static function getMaxBytes(): int
    {
        $mb = (int)($_ENV['UPLOAD_MAX_SIZE_MB'] ?? 0);
        if ($mb < 1 || $mb > 20) {
            $mb = (int)round(self::MAX_BYTES / 1_048_576); // 5
        }
        return $mb * 1_048_576;
    }

    /**
     * Validate and store a single uploaded file.
     *
     * @param array  $file      Single entry from $_FILES (with keys: name, tmp_name, size, error)
     * @param string $storageRoot Absolute path to the uploads directory (outside web root)
     * @return array{
     *   original_filename: string,
     *   stored_filename:   string,
     *   storage_path:      string,
     *   mime_type:         string,
     *   file_size_bytes:   int
     * }
     * @throws \InvalidArgumentException on validation failure (safe message, no path info)
     * @throws \RuntimeException on filesystem failure
     */
    public function store(array $file, string $storageRoot): array
    {
        // PHP upload error check
        if (($file['error'] ?? UPLOAD_ERR_NO_FILE) !== UPLOAD_ERR_OK) {
            throw new \InvalidArgumentException(
                $this->uploadErrorMessage((int)($file['error'] ?? UPLOAD_ERR_NO_FILE))
            );
        }

        // Size check (Req 6.6) — also checked server-side regardless of Content-Length
        $fileSize = (int)($file['size'] ?? 0);
        if ($fileSize <= 0) {
            throw new \InvalidArgumentException('Uploaded file is empty.');
        }
        $maxBytes = self::getMaxBytes();
        if ($fileSize > $maxBytes) {
            $mb = round($maxBytes / 1_048_576, 0);
            throw new \InvalidArgumentException(
                "File '{$this->safeOriginalName($file['name'] ?? '')}' exceeds the {$mb} MB maximum size."
            );
        }

        // MIME type validation using finfo — never trust client-supplied MIME (Req 6.5)
        $tmpPath = $file['tmp_name'] ?? '';
        if (!is_uploaded_file($tmpPath)) {
            throw new \InvalidArgumentException('Invalid file upload.');
        }

        $finfo    = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($tmpPath);

        if (!array_key_exists($mimeType, self::ALLOWED_MIME_TYPES)) {
            throw new \InvalidArgumentException(
                "File '{$this->safeOriginalName($file['name'] ?? '')}' has an unsupported type. " .
                "Only PDF, JPEG, and PNG files are accepted."
            );
        }

        // Generate secure stored filename — never use client filename (Req 6.7)
        $extension      = self::ALLOWED_MIME_TYPES[$mimeType];
        $storedFilename = SecurityHelper::uuid4() . $extension;

        // Organise into YYYY/MM sub-directories to avoid flat-directory problems at scale
        $subDir  = date('Y') . DIRECTORY_SEPARATOR . date('m');
        $fullDir = rtrim($storageRoot, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR . $subDir;

        if (!is_dir($fullDir) && !mkdir($fullDir, 0755, true)) {
            Logger::error('FileUploadService: failed to create upload directory', ['dir' => $fullDir]);
            throw new \RuntimeException('Unable to store uploaded file. Please try again.');
        }

        $fullPath    = $fullDir . DIRECTORY_SEPARATOR . $storedFilename;
        $storagePath = $subDir . DIRECTORY_SEPARATOR . $storedFilename;   // relative — stored in DB

        if (!move_uploaded_file($tmpPath, $fullPath)) {
            Logger::error('FileUploadService: move_uploaded_file failed', ['dest' => $fullPath]);
            throw new \RuntimeException('Unable to store uploaded file. Please try again.');
        }

        return [
            'original_filename' => $this->safeOriginalName($file['name'] ?? 'file'),
            'stored_filename'   => $storedFilename,
            'storage_path'      => $storagePath,
            'mime_type'         => $mimeType,
            'file_size_bytes'   => $fileSize,
        ];
    }

    /**
     * Delete a file from the filesystem by its storage path.
     * Used for rollback cleanup (database.md §16.4).
     * Silent on failure — never throws.
     */
    public function delete(string $storageRoot, string $storagePath): void
    {
        $fullPath = rtrim($storageRoot, DIRECTORY_SEPARATOR)
                  . DIRECTORY_SEPARATOR
                  . ltrim($storagePath, DIRECTORY_SEPARATOR);

        if (file_exists($fullPath)) {
            @unlink($fullPath);
        }
    }

    // -------------------------------------------------------------------------

    /**
     * Sanitise the original filename for safe display (strip path traversal characters).
     * This value is only ever displayed to users — never used to write files.
     */
    private function safeOriginalName(string $name): string
    {
        return htmlspecialchars(
            basename(preg_replace('/[^\w\-. ]/', '', $name) ?: 'file'),
            ENT_QUOTES,
            'UTF-8'
        );
    }

    private function uploadErrorMessage(int $code): string
    {
        return match ($code) {
            UPLOAD_ERR_INI_SIZE, UPLOAD_ERR_FORM_SIZE => 'The uploaded file exceeds the allowed size limit.',
            UPLOAD_ERR_PARTIAL   => 'The file was only partially uploaded. Please try again.',
            UPLOAD_ERR_NO_FILE   => 'No file was uploaded.',
            UPLOAD_ERR_NO_TMP_DIR => 'Upload failed: missing temporary directory.',
            UPLOAD_ERR_CANT_WRITE => 'Upload failed: cannot write to disk.',
            default               => 'An error occurred during upload. Please try again.',
        };
    }
}
