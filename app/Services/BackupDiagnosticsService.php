<?php

namespace App\Services;

use App\Models\BackupHistory;
use App\Support\BackupConfig;
use Illuminate\Support\Facades\Storage;

class BackupDiagnosticsService
{

    /**
     * Run all diagnostics and return a structured report.
     */
    public function run(): array
    {
        return [
            'storage_writable'  => $this->checkStorageWritable(),
            'orphaned_files'    => $this->checkOrphanedFiles(),
            'missing_files'     => $this->checkMissingFiles(),
            'failed_records'    => $this->checkFailedRecords(),
            'pending_stuck'     => $this->checkStuckPending(),
            'mysqldump_available' => $this->checkMysqldumpAvailable(),
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | Individual Checks
    |--------------------------------------------------------------------------
    */

    /**
     * Verify the backups folder is writable.
     */
    public function checkStorageWritable(): array
    {
        $testFile = 'backups/.write_test_' . time();

        try {
            Storage::disk(BackupConfig::DISK)->put($testFile, 'ok');
            Storage::disk(BackupConfig::DISK)->delete($testFile);

            return $this->pass('Storage is writable.');
        } catch (\Throwable $e) {
            return $this->fail('Storage is not writable: ' . $e->getMessage());
        }
    }

    /**
     * Find DB records whose file no longer exists on disk.
     */
    public function checkMissingFiles(): array
    {
        $missing = BackupHistory::where('status', 'success')
            ->get()
            ->filter(fn($b) => !Storage::disk(BackupConfig::DISK)->exists($b->file_path))
            ->map(fn($b) => ['id' => $b->id, 'file' => $b->file_name])
            ->values()
            ->all();

        return empty($missing)
            ? $this->pass('All backup files are present on disk.')
            : $this->fail(count($missing) . ' backup record(s) have missing files.', $missing);
    }

    /**
     * Find files on disk that have no matching DB record.
     */
    public function checkOrphanedFiles(): array
    {
        $diskFiles = collect(Storage::disk(BackupConfig::DISK)->files('backups'));
        $dbPaths   = BackupHistory::pluck('file_path')->flip();

        $orphans = $diskFiles
            ->filter(fn($path) => !$dbPaths->has($path))
            ->values()
            ->all();

        return empty($orphans)
            ? $this->pass('No orphaned files found on disk.')
            : $this->fail(count($orphans) . ' file(s) on disk have no DB record.', $orphans);
    }

    /**
     * List all records with status = failed.
     */
    public function checkFailedRecords(): array
    {
        $failed = BackupHistory::where('status', 'failed')
            ->orderByDesc('created_at')
            ->limit(20)
            ->get(['id', 'file_name', 'type', 'created_at'])
            ->toArray();

        return empty($failed)
            ? $this->pass('No failed backup records found.')
            : $this->fail(count($failed) . ' failed backup record(s) found.', $failed);
    }

    /**
     * Find records stuck in pending status for more than 30 minutes.
     */
    public function checkStuckPending(): array
    {
        $stuck = BackupHistory::where('status', 'pending')
            ->where('created_at', '<', now()->subMinutes(30))
            ->get(['id', 'file_name', 'created_at'])
            ->toArray();

        return empty($stuck)
            ? $this->pass('No stuck pending records found.')
            : $this->fail(count($stuck) . ' backup(s) stuck in pending state.', $stuck);
    }

    /**
     * Verify mysqldump binary is available on the system.
     */
    public function checkMysqldumpAvailable(): array
    {
        $dir    = rtrim(env('MYSQL_BINARIES_PATH', ''), '/\\');
        $binary = $dir
            ? $dir . DIRECTORY_SEPARATOR . 'mysqldump' . (PHP_OS_FAMILY === 'Windows' ? '.exe' : '')
            : 'mysqldump';

        exec(escapeshellarg($binary) . ' --version 2>&1', $output, $exitCode);

        return $exitCode === 0
            ? $this->pass('mysqldump is available: ' . ($output[0] ?? ''))
            : $this->fail('mysqldump binary not found at: ' . $binary);
    }

    /*
    |--------------------------------------------------------------------------
    | Response Helpers
    |--------------------------------------------------------------------------
    */

    private function pass(string $message, array $data = []): array
    {
        return ['status' => 'ok', 'message' => $message, 'data' => $data];
    }

    private function fail(string $message, array $data = []): array
    {
        return ['status' => 'fail', 'message' => $message, 'data' => $data];
    }
}

