<?php

namespace App\Providers;

use App\Services\Demo\DemoActivityRecorder;
use App\Services\Demo\DemoSessionManager;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\ServiceProvider;

class DemoModeServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton(DemoSessionManager::class);
        $this->app->singleton(DemoActivityRecorder::class);
    }

    public function boot(): void
    {
        $recorder = fn () => $this->app->make(DemoActivityRecorder::class);

        Event::listen('eloquent.created: *', function (string $event, array $data) use ($recorder) {
            if ($data[0] instanceof Model) {
                $recorder()->recordCreated($data[0]);
            }
        });

        Event::listen('eloquent.updating: *', function (string $event, array $data) use ($recorder) {
            if ($data[0] instanceof Model) {
                $recorder()->stashOriginal($data[0]);
            }
        });

        Event::listen('eloquent.updated: *', function (string $event, array $data) use ($recorder) {
            if ($data[0] instanceof Model) {
                $recorder()->recordUpdated($data[0]);
            }
        });

        Event::listen('eloquent.deleting: *', function (string $event, array $data) use ($recorder) {
            if ($data[0] instanceof Model) {
                $recorder()->stashForDelete($data[0]);
            }
        });

        Event::listen('eloquent.deleted: *', function (string $event, array $data) use ($recorder) {
            if ($data[0] instanceof Model) {
                $recorder()->recordDeleted($data[0]);
            }
        });
    }
}
