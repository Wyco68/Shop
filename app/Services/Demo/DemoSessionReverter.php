<?php

namespace App\Services\Demo;

use App\Models\DemoAdminSession;
use App\Models\DemoChangeLog;
use Illuminate\Support\Facades\Crypt;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

/**
 * Replays a demo session's change log in reverse to undo every write it
 * made. Uses the query builder (not Eloquent) so reverting never re-fires
 * model events, and each row is best-effort — one bad row can't block the
 * rest of the rollback.
 */
class DemoSessionReverter
{
    public function revert(DemoAdminSession $session): void
    {
        if ($session->reverted_at) {
            return;
        }

        $fullyReverted = true;

        DB::transaction(function () use ($session, &$fullyReverted) {
            DemoChangeLog::query()
                ->where('demo_admin_session_id', $session->id)
                ->orderByDesc('id')
                ->cursor()
                ->each(function (DemoChangeLog $log) use (&$fullyReverted) {
                    if (! $this->revertOne($log)) {
                        $fullyReverted = false;
                    }
                });

            // Only mark the session reverted if every row actually undid
            // cleanly. Leaving reverted_at null on partial failure means the
            // demo:sweep-sessions backstop (and a future logout/expiry check)
            // will keep retrying instead of silently reporting a clean
            // rollback while some of the demo admin's writes still stand.
            if ($fullyReverted) {
                $session->forceFill(['reverted_at' => now()])->saveQuietly();
            }
        });
    }

    private function revertOne(DemoChangeLog $log): bool
    {
        try {
            $modelType = $log->model_type;

            if (! class_exists($modelType)) {
                return true;
            }

            $instance = new $modelType;
            $table = $instance->getTable();
            $keyName = $instance->getKeyName();

            match ($log->action) {
                'created' => DB::table($table)->where($keyName, $log->model_key)->delete(),
                'updated' => $this->restore($table, $keyName, $log),
                'deleted' => $this->reinsert($table, $keyName, $log),
                default => null,
            };

            return true;
        } catch (\Throwable $e) {
            Log::error('Demo session revert failed for a change log entry', [
                'demo_change_log_id' => $log->id,
                'model_type' => $log->model_type,
                'action' => $log->action,
                'error' => $e->getMessage(),
            ]);

            return false;
        }
    }

    private function restore(string $table, string $keyName, DemoChangeLog $log): void
    {
        if (empty($log->before)) {
            return;
        }

        DB::table($table)->where($keyName, $log->model_key)->update($this->decryptSensitive($log->before));
    }

    private function reinsert(string $table, string $keyName, DemoChangeLog $log): void
    {
        if (empty($log->before)) {
            return;
        }

        DB::table($table)->updateOrInsert([$keyName => $log->model_key], $this->decryptSensitive($log->before));
    }

    private function decryptSensitive(array $attributes): array
    {
        foreach (DemoActivityRecorder::REDACTED_KEYS as $key) {
            if (array_key_exists($key, $attributes) && $attributes[$key] !== null) {
                $attributes[$key] = Crypt::decryptString($attributes[$key]);
            }
        }

        return $attributes;
    }
}
