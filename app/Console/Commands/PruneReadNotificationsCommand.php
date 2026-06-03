<?php

namespace App\Console\Commands;

use App\Services\NotificationService;
use Illuminate\Console\Command;

class PruneReadNotificationsCommand extends Command
{
    protected $signature = 'notifications:prune-read {--days=10 : Remove notifications read at least this many days ago}';

    protected $description = 'Delete read notifications older than the retention period (default 10 days)';

    public function handle(NotificationService $notifications): int
    {
        $days = (int) $this->option('days');

        if ($days < 1) {
            $this->components->error('The --days option must be at least 1.');

            return self::FAILURE;
        }

        $deleted = $notifications->pruneReadOlderThan($days);

        $this->components->info("Deleted {$deleted} read notification(s) older than {$days} day(s).");

        return self::SUCCESS;
    }
}
