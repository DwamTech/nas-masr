<?php

namespace App\Support;

/**
 * Single source of truth for backup system configuration.
 * Used by BackupService and BackupDiagnosticsService.
 */
final class BackupConfig
{
    public const DISK   = 'local';
    public const FOLDER = 'backups';
}
