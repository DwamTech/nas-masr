<?php

namespace App\Events;

use App\Models\BackupHistory;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class BackupCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly BackupHistory $backup
    ) {}
}
