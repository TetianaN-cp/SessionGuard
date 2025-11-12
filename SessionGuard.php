<?php

class SessionGuard
{
    // Path to session storage
    private string $path;

    // Maximum session file age in seconds (default: 3 days)
    private int $maxAge;

    // Enable or disable logging
    private bool $logging;

    // Fallback paths if the main session path is not writable
    private array $fallbackPaths = ['/var/lib/php/sessions', '/tmp/php_sessions', '/tmp'];

    /**
     * Constructor: initializes session path, max age, and logging.
     * Ensures a writable path is selected.
     */
    public function __construct(string $path = '', int $maxAge = 259200, bool $logging = true)
    {
        $this->path = $path ?: ini_get('session.save_path') ?: '/tmp';
        $this->maxAge = $maxAge;
        $this->logging = $logging;

        $this->ensureWritablePath();
    }

    /**
     * Starts the session after cleaning up old files.
     * Logs errors if session_start fails.
     */
    public function start(): bool
    {
        $this->cleanupOldSessions();
        try {
            return session_start();
        } catch (\Throwable $e) {
            if ($this->logging) {
                error_log("SessionGuard error: " . $e->getMessage());
            }
            return false;
        }
    }

    /**
     * Ensures the session path is writable and has enough space.
     * Falls back to alternative paths if needed.
     */
    private function ensureWritablePath(): void
    {
        if ($this->isWritable($this->path) && $this->hasEnoughSpace($this->path)) {
            return;
        }

        foreach ($this->fallbackPaths as $altPath) {
            if (!is_dir($altPath)) {
                @mkdir($altPath, 0733, true);
            }

            if ($this->isWritable($altPath) && $this->hasEnoughSpace($altPath)) {
                ini_set('session.save_path', $altPath);
                $this->path = $altPath;
                if ($this->logging) {
                    error_log("SessionGuard: switched to fallback path '$altPath'");
                }
                return;
            }
        }

        if ($this->logging) {
            error_log("SessionGuard: no writable session path found");
        }
    }

    /**
     * Deletes old session files based on maxAge.
     */
    private function cleanupOldSessions(): void
    {
        $files = glob($this->path . '/sess_*');
        $now = time();

        foreach ($files as $file) {
            if (is_file($file) && filemtime($file) < $now - $this->maxAge) {
                @unlink($file);
            }
        }
    }

    /**
     * Checks if a path is writable.
     */
    private function isWritable(string $path): bool
    {
        return is_writable($path);
    }

    /**
     * Checks if a path has at least 10MB of free space.
     */
    private function hasEnoughSpace(string $path): bool
    {
        return disk_free_space($path) > 1024 * 1024 * 10;
    }

    /**
     * Logs current session path status: writability and disk space.
     */
    public function logStatus(): void
    {
        if (!$this->isWritable($this->path)) {
            error_log("SessionGuard: save_path '{$this->path}' is not writable");
        }

        $free = disk_free_space($this->path);
        if ($free < 1024 * 1024 * 10) {
            error_log("SessionGuard: low disk space in '{$this->path}' — {$free} bytes");
        }
    }

    /**
     * Logs detailed environment info for a given path.
     */
    private function verifyEnvironment(string $path): void
    {
        if (!file_exists($path)) {
            error_log("SessionGuard ENV: path '$path' does not exist");
            return;
        }

        if (!is_dir($path)) {
            error_log("SessionGuard ENV: path '$path' exists but is not a directory");
            return;
        }

        $owner = function_exists('posix_getpwuid')
            ? posix_getpwuid(fileowner($path))['name'] ?? 'unknown'
            : 'unknown';

        $perms = substr(sprintf('%o', fileperms($path)), -3);
        $writable = is_writable($path) ? 'yes' : 'no';
        $freeSpaceBytes = disk_free_space($path);
        $freeSpaceMB = round($freeSpaceBytes / 1024 / 1024, 2);
        $freeSpaceGB = round($freeSpaceBytes / 1024 / 1024 / 1024, 2);

        error_log("SessionGuard ENV: path = '$path'");
        error_log("SessionGuard ENV: writable = $writable");
        error_log("SessionGuard ENV: free space = {$freeSpaceBytes} bytes ({$freeSpaceMB} MB {$freeSpaceGB} GB)");

        if ($freeSpaceBytes < 1024 * 1024 * 10) {
            error_log("SessionGuard ENV: WARNING — low disk space");
        }
    }

    /**
     * Diagnoses whether the current session path matches the expected one.
     * Logs environment details.
     */
    public function diagnose(string $expectedPath): void
    {
        $currentPath = ini_get('session.save_path') ?: 'not set';

        if ($currentPath !== $expectedPath) {
            error_log("SessionGuard DIAG: mismatch — expected '$expectedPath', got '$currentPath'");
            $this->verifyEnvironment($currentPath);
        } else {
            error_log("SessionGuard DIAG: session.save_path matches expected '$expectedPath'");
            $this->verifyEnvironment($currentPath);
        }
    }

    /**
     * Returns free space in MB as a localized string.
     */
    public function getFreeSpaceMB(): string
    {
        $bytes = disk_free_space($this->path);
        $mb = round($bytes / 1024 / 1024, 2);

        return "Available session space: {$mb} MB";
    }
}
