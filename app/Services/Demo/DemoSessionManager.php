<?php

namespace App\Services\Demo;

use App\Models\DemoAdminSession;
use App\Models\User;
use Illuminate\Http\Request;

/**
 * Tracks the demo session (if any) active for the current request. The
 * shared/demo admin account is any admin row that is not the owner
 * (User::is_owner). Bound as a singleton so the recorder can see which
 * session, if any, writes made during this request belong to.
 */
class DemoSessionManager
{
    private ?DemoAdminSession $current = null;

    public function current(): ?DemoAdminSession
    {
        return $this->current;
    }

    public function setCurrent(?DemoAdminSession $session): void
    {
        $this->current = $session;
    }

    public function isDemoAdmin(?User $user): bool
    {
        return $user !== null && $user->isAdmin() && ! $user->is_owner;
    }

    public function enabled(): bool
    {
        return (bool) config('admin.demo.enabled', true);
    }

    public function sessionMinutes(): int
    {
        return max(1, (int) config('admin.demo.session_minutes', 30));
    }

    public function startOrResume(Request $request, User $user): DemoAdminSession
    {
        $sessionId = $request->session()->getId();

        $session = DemoAdminSession::query()
            ->where('session_id', $sessionId)
            ->whereNull('ended_at')
            ->whereNull('reverted_at')
            ->first();

        if (! $session) {
            $session = DemoAdminSession::query()->create([
                'session_id' => $sessionId,
                'user_id' => $user->id,
                'started_at' => now(),
                'expires_at' => now()->addMinutes($this->sessionMinutes()),
            ]);
        }

        $this->current = $session;

        return $session;
    }

    public function endBySessionId(string $sessionId, DemoSessionReverter $reverter): void
    {
        $session = DemoAdminSession::query()
            ->where('session_id', $sessionId)
            ->whereNull('reverted_at')
            ->first();

        if ($session) {
            $reverter->revert($session);

            if (! $session->ended_at) {
                $session->forceFill(['ended_at' => now()])->saveQuietly();
            }
        }

        $this->current = null;
    }

    public function expireAndRevert(DemoAdminSession $session, DemoSessionReverter $reverter): void
    {
        $reverter->revert($session);

        if (! $session->ended_at) {
            $session->forceFill(['ended_at' => now()])->saveQuietly();
        }

        $this->current = null;
    }
}
