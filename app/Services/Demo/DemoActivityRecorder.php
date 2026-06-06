<?php

namespace App\Services\Demo;

use App\Models\AdminPasswordChangeLog;
use App\Models\DemoAdminSession;
use App\Models\DemoChangeLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Crypt;

/**
 * Records every write made by the active demo-admin session so it can be
 * undone later by DemoSessionReverter. Hooked up to global Eloquent wildcard
 * events in DemoModeServiceProvider. Bound as a singleton — the "pending"
 * stash bridges the *ing (before) and *ed (after) event pair for the same
 * request.
 */
class DemoActivityRecorder
{
    /** @var array<int, array<string, mixed>> */
    private array $pending = [];

    public function __construct(private readonly DemoSessionManager $sessions) {}

    public function stashOriginal(Model $model): void
    {
        if (! $this->sessions->current() || ! $this->shouldTrack($model)) {
            return;
        }

        $this->pending[spl_object_id($model)] = $model->getOriginal();
    }

    public function stashForDelete(Model $model): void
    {
        if (! $this->sessions->current() || ! $this->shouldTrack($model)) {
            return;
        }

        $this->pending[spl_object_id($model)] = $model->getAttributes();
    }

    public function recordCreated(Model $model): void
    {
        $session = $this->sessions->current();

        if (! $session || ! $this->shouldTrack($model)) {
            return;
        }

        $this->write($session, $model, 'created', null, $model->getAttributes());
    }

    public function recordUpdated(Model $model): void
    {
        $key = spl_object_id($model);
        $original = $this->pending[$key] ?? null;
        unset($this->pending[$key]);

        $session = $this->sessions->current();

        if (! $session || ! $this->shouldTrack($model) || $original === null) {
            return;
        }

        $changes = $model->getChanges();

        if (empty($changes)) {
            return;
        }

        $before = array_intersect_key($original, $changes);
        $after = array_intersect_key($model->getAttributes(), $changes);

        $this->write($session, $model, 'updated', $before, $after);
    }

    public function recordDeleted(Model $model): void
    {
        $key = spl_object_id($model);
        $attributes = $this->pending[$key] ?? null;
        unset($this->pending[$key]);

        $session = $this->sessions->current();

        if (! $session || ! $this->shouldTrack($model) || $attributes === null) {
            return;
        }

        $this->write($session, $model, 'deleted', $attributes, null);
    }

    /**
     * Attribute keys that are live authentication secrets. Their values are
     * encrypted (not dropped) before landing in demo_change_logs — a queryable,
     * unencrypted table — so a password change made during a demo session can
     * still be reverted (DemoSessionReverter::decryptSensitive) without ever
     * storing the password hash or remember-me token in plaintext there.
     */
    public const REDACTED_KEYS = ['password', 'remember_token'];

    private function write(DemoAdminSession $session, Model $model, string $action, ?array $before, ?array $after): void
    {
        DemoChangeLog::query()->create([
            'demo_admin_session_id' => $session->id,
            'model_type' => get_class($model),
            'model_key' => (string) $model->getKey(),
            'action' => $action,
            'before' => $this->redact($before),
            'after' => $this->redact($after),
        ]);
    }

    private function redact(?array $attributes): ?array
    {
        if ($attributes === null) {
            return null;
        }

        foreach (self::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null) {
                $attributes[$key] = Crypt::encryptString((string) $attributes[$key]);
            }
        }

        return $attributes;
    }

    private function shouldTrack(Model $model): bool
    {
        $class = get_class($model);

        if (in_array($class, [DemoAdminSession::class, DemoChangeLog::class, AdminPasswordChangeLog::class], true)) {
            return false;
        }

        return str_starts_with($class, 'App\\Models\\');
    }
}
