<?php

namespace App\Console\Commands;

use App\Models\DemoAdminSession;
use App\Services\Demo\DemoSessionReverter;
use Illuminate\Console\Command;

class SweepExpiredDemoSessionsCommand extends Command
{
    protected $signature = 'demo:sweep-sessions';

    protected $description = 'Revert and close demo-admin sessions that expired without the tester logging out';

    public function handle(DemoSessionReverter $reverter): int
    {
        $sessions = DemoAdminSession::query()
            ->whereNull('reverted_at')
            ->where('expires_at', '<', now())
            ->get();

        foreach ($sessions as $session) {
            $reverter->revert($session);

            if (! $session->ended_at) {
                $session->forceFill(['ended_at' => now()])->saveQuietly();
            }
        }

        if ($sessions->isNotEmpty()) {
            $this->components->info("Reverted {$sessions->count()} expired demo session(s).");
        }

        return self::SUCCESS;
    }
}
