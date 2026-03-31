<?php

namespace App\Services;

use App\Events\BackupCreated;
use App\Events\BackupDeleted;
use App\Events\BackupRestored;
use App\Models\BackupHistory;
use App\Support\BackupConfig;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;
use RuntimeException;

class BackupService
{

    /*
    |--------------------------------------------------------------------------
    | Public API
    |--------------------------------------------------------------------------
    */

    public function createBackup(array $data = []): BackupHistory
    {
        $type     = $data['type'] ?? 'db';
        $fileName = 'backup_' . now()->format('Y_m_d_His') . '_' . $type . '.sql.gz';
        $filePath = BackupConfig::FOLDER . '/' . $fileName;

        $record = $this->createPendingRecord($fileName, $filePath, $type);

        try {
            $compressed = $this->compressSql($this->dumpDatabase());

            Storage::disk(BackupConfig::DISK)->put($filePath, $compressed);

            $record->update([
                'status' => 'success',
                'size'   => Storage::disk(BackupConfig::DISK)->size($filePath),
            ]);

            $this->log('info', 'Backup created.', $record);

            event(new BackupCreated($record->fresh()));

            return $record->fresh();

        } catch (\Throwable $e) {
            $this->markFailed($record);
            $this->cleanupFile($filePath);
            $this->log('error', 'Backup failed.', $record, $e);

            throw new RuntimeException('Backup creation failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function listBackups(int $perPage = 20): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return BackupHistory::with('creator:id,name')
            ->orderByDesc('created_at')
            ->paginate($perPage)
            ->through(fn(BackupHistory $b) => [
                'id'             => $b->id,
                'file_name'      => $b->file_name,
                'type'           => $b->type,
                'status'         => $b->status,
                'size'           => $b->size,
                'size_formatted' => $b->formattedSize(),
                'created_by'     => $b->creator?->name,
                'created_at'     => $b->created_at->toIso8601String(),
            ]);
    }

    public function restoreBackup(int $id): BackupHistory
    {
        $record = BackupHistory::findOrFail($id);

        $this->assertRestoreable($record);

        try {
            $sql = $this->decompressSql(
                Storage::disk(BackupConfig::DISK)->get($record->file_path)
            );

            $this->importDatabase($sql);

            $this->log('info', 'Restore completed.', $record);

            event(new BackupRestored($record));

            return $record;

        } catch (\Throwable $e) {
            $this->log('error', 'Restore failed.', $record, $e);

            throw new RuntimeException('Restore failed: ' . $e->getMessage(), 0, $e);
        }
    }

    public function deleteBackup(int $id): void
    {
        $record = BackupHistory::findOrFail($id);

        try {
            $this->cleanupFile($record->file_path);
            $record->delete();

            $this->log('info', 'Backup deleted.', $record);

            event(new BackupDeleted($record));

        } catch (\Throwable $e) {
            $this->log('error', 'Delete failed.', $record, $e);

            throw new RuntimeException('Delete failed: ' . $e->getMessage(), 0, $e);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Database Helpers
    |--------------------------------------------------------------------------
    */

    private function dumpDatabase(): string
    {
        $command = $this->buildMysqldumpCommand();

        exec($command, $output, $exitCode);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'mysqldump failed (exit ' . $exitCode . '): ' . implode("\n", $output)
            );
        }

        $sql = implode("\n", $output);

        if (empty(trim($sql))) {
            throw new RuntimeException('mysqldump returned empty output.');
        }

        return $sql;
    }

    private function importDatabase(string $sql): void
    {
        $tmpFile = $this->writeTempFile($sql);

        $command = $this->buildMysqlImportCommand($tmpFile);

        exec($command, $output, $exitCode);

        @unlink($tmpFile);

        if ($exitCode !== 0) {
            throw new RuntimeException(
                'mysql import failed (exit ' . $exitCode . '): ' . implode("\n", $output)
            );
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Command Builders
    |--------------------------------------------------------------------------
    */

    private function buildMysqldumpCommand(): string
    {
        ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass, 'db' => $db] = $this->dbConfig();

        $binary = $this->mysqlBinary('mysqldump');

        return sprintf(
            '%s --host=%s --port=%s --user=%s%s --single-transaction --routines --triggers %s 2>&1',
            $binary,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass ? ' -p' . escapeshellarg($pass) : '',
            escapeshellarg($db)
        );
    }

    private function buildMysqlImportCommand(string $tmpFile): string
    {
        ['host' => $host, 'port' => $port, 'user' => $user, 'pass' => $pass, 'db' => $db] = $this->dbConfig();

        $binary = $this->mysqlBinary('mysql');

        return sprintf(
            '%s --host=%s --port=%s --user=%s%s %s < %s 2>&1',
            $binary,
            escapeshellarg($host),
            escapeshellarg($port),
            escapeshellarg($user),
            $pass ? ' -p' . escapeshellarg($pass) : '',
            escapeshellarg($db),
            escapeshellarg($tmpFile)
        );
    }

    /*
    |--------------------------------------------------------------------------
    | Small Helpers
    |--------------------------------------------------------------------------
    */

    private function dbConfig(): array
    {
        $c = config('database.connections.mysql');

        return [
            'host' => $c['host'],
            'port' => (string) ($c['port'] ?? 3306),
            'user' => $c['username'],
            'pass' => $c['password'],
            'db'   => $c['database'],
        ];
    }

    /**
     * Resolve the full path to a mysql binary.
     * Reads MYSQL_BINARIES_PATH from .env — useful on Windows/XAMPP.
     * Falls back to just the binary name (relies on system PATH).
     */
    private function mysqlBinary(string $binary): string
    {
        $dir = rtrim(env('MYSQL_BINARIES_PATH', ''), '/\\');

        if ($dir) {
            $separator = DIRECTORY_SEPARATOR;
            $path = $dir . $separator . $binary;

            // On Windows add .exe if missing
            if (PHP_OS_FAMILY === 'Windows' && !str_ends_with($path, '.exe')) {
                $path .= '.exe';
            }

            return escapeshellarg($path);
        }

        return $binary;
    }

    private function compressSql(string $sql): string
    {
        $result = gzencode($sql, 9);

        if ($result === false) {
            throw new RuntimeException('gzencode failed.');
        }

        return $result;
    }

    private function decompressSql(string $compressed): string
    {
        $result = gzdecode($compressed);

        if ($result === false) {
            throw new RuntimeException('gzdecode failed — file may be corrupt.');
        }

        return $result;
    }

    private function writeTempFile(string $content): string
    {
        $path = tempnam(sys_get_temp_dir(), 'restore_') . '.sql';
        file_put_contents($path, $content);
        return $path;
    }

    private function createPendingRecord(string $fileName, string $filePath, string $type): BackupHistory
    {
        return BackupHistory::create([
            'file_name'  => $fileName,
            'file_path'  => $filePath,
            'type'       => $type,
            'status'     => 'pending',
            'created_by' => Auth::id(),
        ]);
    }

    private function markFailed(BackupHistory $record): void
    {
        $record->update(['status' => 'failed']);
    }

    private function cleanupFile(string $filePath): void
    {
        if (Storage::disk(BackupConfig::DISK)->exists($filePath)) {
            Storage::disk(BackupConfig::DISK)->delete($filePath);
        }
    }

    private function assertRestoreable(BackupHistory $record): void
    {
        if (!$record->isSuccess()) {
            throw new RuntimeException(
                "Backup #{$record->id} cannot be restored — status is '{$record->status}'."
            );
        }

        if (!Storage::disk(BackupConfig::DISK)->exists($record->file_path)) {
            throw new RuntimeException(
                "Backup file not found on disk: {$record->file_path}"
            );
        }
    }

    private function log(string $level, string $message, BackupHistory $record, ?\Throwable $e = null): void
    {
        $context = [
            'backup_id' => $record->id,
            'file'      => $record->file_name,
            'actor'     => Auth::id(),
        ];

        if ($e) {
            $context['error'] = $e->getMessage();
        }

        Log::$level('[BackupService] ' . $message, $context);
    }
}

