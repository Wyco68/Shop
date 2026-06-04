<?php

namespace App\Console\Commands;

use App\Models\Product;
use App\Models\User;
use App\Support\StoreCache;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Route;
use Symfony\Component\HttpFoundation\Request as SymfonyRequest;

class BenchmarkPerformanceCommand extends Command
{
    protected $signature = 'perf:benchmark
                            {--iterations=5 : Requests per endpoint}
                            {--json : Output JSON only}';

    protected $description = 'Measure response time, query count, and memory for key HTTP endpoints';

    public function handle(): int
    {
        if (! extension_loaded('pdo')) {
            $this->components->error('PDO is not available in this PHP runtime. Run via Sail: ./vendor/bin/sail artisan perf:benchmark');

            return self::FAILURE;
        }

        $iterations = max(1, (int) $this->option('iterations'));
        $results = [];

        $this->components->info("Running {$iterations} iteration(s) per endpoint…");

        $results[] = $this->benchmarkRoute('GET /', 'home', iterations: $iterations);
        $results[] = $this->benchmarkRoute('GET /products', 'products.index', iterations: $iterations);
        $results[] = $this->benchmarkProductShow($iterations);
        $results[] = $this->benchmarkAuthenticated('GET /cart', 'cart.index', $iterations);
        $results[] = $this->benchmarkAuthenticated('GET /orders/create', 'orders.create', $iterations);

        if ($this->option('json')) {
            $this->line(json_encode($results, JSON_PRETTY_PRINT));

            return self::SUCCESS;
        }

        $this->table(
            ['Endpoint', 'Avg (ms)', 'P95 (ms)', 'Queries', 'Memory (MB)', 'Status'],
            collect($results)->map(fn (array $r) => [
                $r['endpoint'],
                $r['avg_time_ms'],
                $r['p95_time_ms'],
                $r['queries'].' ('.$r['query_note'].')',
                $r['memory_mb'],
                (string) $r['status'],
            ])->all()
        );

        $this->newLine();
        $this->components->twoColumnDetail('Cache keys (sample)', implode(', ', [
            StoreCache::SETTINGS,
            StoreCache::CATEGORIES_ACTIVE,
            'products.index.*',
        ]));

        return self::SUCCESS;
    }

    private function benchmarkRoute(string $label, string $routeName, int $iterations, ?User $user = null): array
    {
        $times = [];
        $queries = [];
        $memory = [];
        $status = 200;

        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = hrtime(true);
            $memBefore = memory_get_usage(true);

            $response = $this->dispatchRoute($routeName, $user);

            $elapsed = (hrtime(true) - $start) / 1_000_000;
            $times[] = $elapsed;
            $memory[] = (memory_get_peak_usage(true) - $memBefore) / 1024 / 1024;
            $queries[] = count(DB::getQueryLog());
            $status = $response->getStatusCode();
        }

        return $this->formatResult($label, $times, $queries, $memory, $status);
    }

    private function benchmarkProductShow(int $iterations): array
    {
        $product = Product::query()->where('is_active', true)->first();

        if (! $product) {
            return [
                'endpoint' => 'GET /products/{slug}',
                'avg_time_ms' => 0,
                'p95_time_ms' => 0,
                'queries' => 0,
                'query_note' => 'skipped (no products)',
                'memory_mb' => 0,
                'status' => 'skip',
            ];
        }

        $times = [];
        $queries = [];
        $memory = [];
        $status = 200;

        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = hrtime(true);
            $memBefore = memory_get_usage(true);

            $response = $this->dispatchRoute('products.show', null, ['product' => $product->slug]);

            $times[] = (hrtime(true) - $start) / 1_000_000;
            $memory[] = (memory_get_peak_usage(true) - $memBefore) / 1024 / 1024;
            $queries[] = count(DB::getQueryLog());
            $status = $response->getStatusCode();
        }

        return $this->formatResult('GET /products/{slug}', $times, $queries, $memory, $status);
    }

    private function benchmarkAuthenticated(string $label, string $routeName, int $iterations): array
    {
        $user = User::factory()->create();

        $times = [];
        $queries = [];
        $memory = [];
        $status = 200;

        for ($i = 0; $i < $iterations; $i++) {
            Cache::flush();
            DB::flushQueryLog();
            DB::enableQueryLog();

            $start = hrtime(true);
            $memBefore = memory_get_usage(true);

            $response = $this->dispatchRoute($routeName, $user);

            $times[] = (hrtime(true) - $start) / 1_000_000;
            $memory[] = (memory_get_peak_usage(true) - $memBefore) / 1024 / 1024;
            $queries[] = count(DB::getQueryLog());
            $status = $response->getStatusCode();
        }

        return $this->formatResult($label, $times, $queries, $memory, $status);
    }

    private function dispatchRoute(string $routeName, ?User $user, array $parameters = []): \Symfony\Component\HttpFoundation\Response
    {
        $route = Route::getRoutes()->getByName($routeName);

        if (! $route) {
            throw new \RuntimeException("Route [{$routeName}] not found.");
        }

        $uri = route($routeName, $parameters, false);
        $method = $route->methods()[0] ?? 'GET';

        $symfony = SymfonyRequest::create($uri, $method);
        $request = \Illuminate\Http\Request::createFromBase($symfony);

        if ($user) {
            $request->setUserResolver(fn () => $user);
        }

        return app()->handle($request);
    }

    /** @param  list<float>  $times */
    private function formatResult(string $label, array $times, array $queries, array $memory, int|string $status): array
    {
        sort($times);
        $avgQueries = (int) round(array_sum($queries) / max(1, count($queries)));
        $p95Index = (int) floor(count($times) * 0.95) ?: 0;
        $p95 = $times[$p95Index] ?? $times[array_key_last($times)];

        return [
            'endpoint' => $label,
            'avg_time_ms' => round(array_sum($times) / count($times), 2),
            'p95_time_ms' => round($p95, 2),
            'queries' => $avgQueries,
            'query_note' => $avgQueries > 15 ? 'review N+1' : 'ok',
            'memory_mb' => round(array_sum($memory) / count($memory), 2),
            'status' => $status,
        ];
    }
}
