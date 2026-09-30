<?php

namespace App\Console\Commands;

use App\Models\Driver;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class BenchmarkSpatialCommand extends Command
{
    /**
     * The name and signature of the console command.
     */
    protected $signature = 'geo:benchmark {--iterations=3 : Number of test runs to average}';

    /**
     * The console command description.
     */
    protected $description = 'Benchmark PostGIS spatial queries (Radius & KNN) with and without GiST index';

    /**
     * Execute the console command.
     */
    public function handle(): int
    {
        $driverCount = Driver::count();

        $this->newLine();
        $this->info("===============================================================");
        $this->info("   GeoDispatch Spatial Benchmark (PostgreSQL + PostGIS GiST)   ");
        $this->info("===============================================================");
        $this->line("Dataset Size: <fg=yellow>{$driverCount} drivers</>");
        $this->line("Test Coordinates: <fg=cyan>Cairo Tahrir Square (Lat: 30.0444, Lng: 31.2357)</>");
        $this->line("Iterations per query: <fg=cyan>{$this->option('iterations')}</>");
        $this->newLine();

        if ($driverCount < 1000) {
            $this->warn("Warning: Driver count is small ({$driverCount}). Benchmarks are most meaningful with 100k+ rows.");
            $this->line("Tip: Run 'php artisan db:seed --class=LargeScaleDriverSeeder' first.");
            $this->newLine();
        }

        $iterations = (int) $this->option('iterations');

        $benchmarks = [
            [
                'name' => 'UC-01: Radius Search (ST_DWithin 5km)',
                'description' => 'Find available drivers within 5km radius sorted by proximity',
                'sql' => "
                    SELECT id, name, status, ST_Distance(location, ST_SetSRID(ST_MakePoint(31.2357, 30.0444), 4326)::geography) as distance_in_meters
                    FROM drivers
                    WHERE ST_DWithin(location, ST_SetSRID(ST_MakePoint(31.2357, 30.0444), 4326)::geography, 5000)
                      AND status = 'available'
                    ORDER BY location <-> ST_SetSRID(ST_MakePoint(31.2357, 30.0444), 4326)::geography ASC
                    LIMIT 20;
                ",
            ],
            [
                'name' => 'UC-02: Nearest Driver (KNN Proximity <->)',
                'description' => 'Find the single nearest available driver across all records',
                'sql' => "
                    SELECT id, name, status, ST_Distance(location, ST_SetSRID(ST_MakePoint(31.2357, 30.0444), 4326)::geography) as distance_in_meters
                    FROM drivers
                    WHERE status = 'available'
                    ORDER BY location <-> ST_SetSRID(ST_MakePoint(31.2357, 30.0444), 4326)::geography ASC
                    LIMIT 1;
                ",
            ],
        ];

        $tableRows = [];

        foreach ($benchmarks as $benchmark) {
            $this->line("Benchmarking: <fg=cyan>{$benchmark['name']}</>...");

            // 1. Benchmark WITHOUT index
            $withoutIndex = $this->runExplainAnalyze($benchmark['sql'], $iterations, useIndex: false);

            // 2. Benchmark WITH GiST index
            $withIndex = $this->runExplainAnalyze($benchmark['sql'], $iterations, useIndex: true);

            $speedup = $withIndex['execution_time'] > 0
                ? round($withoutIndex['execution_time'] / $withIndex['execution_time'], 1)
                : 1;

            $tableRows[] = [
                $benchmark['name'],
                number_format($withoutIndex['execution_time'], 2) . ' ms (' . $withoutIndex['scan_type'] . ')',
                number_format($withIndex['execution_time'], 2) . ' ms (' . $withIndex['scan_type'] . ')',
                "<fg=green;options=bold>{$speedup}x faster 🚀</>",
            ];
        }

        $this->newLine();
        $this->table(
            ['Use Case / Query', 'Without GiST Index', 'With GiST Index', 'Performance Gain'],
            $tableRows
        );

        $this->newLine();
        $this->info("Summary of Execution Plans:");
        $this->line(" - <fg=yellow>Without Index</>: PostgreSQL executes a <fg=red>Sequential Scan (Seq Scan)</>, evaluating distance for every single row in the table.");
        $this->line(" - <fg=green>With GiST Index</>: PostgreSQL traverses the R-Tree bounding-box index (<fg=green>Bitmap / Index Scan</>), retrieving matching candidates instantly.");
        $this->newLine();

        return self::SUCCESS;
    }

    /**
     * Run EXPLAIN (ANALYZE, FORMAT JSON) for a given SQL query.
     *
     * @return array{execution_time: float, scan_type: string}
     */
    private function runExplainAnalyze(string $sql, int $iterations, bool $useIndex): array
    {
        $totalTime = 0.0;
        $primaryScan = 'Unknown';

        for ($i = 0; $i < $iterations; $i++) {
            DB::beginTransaction();

            try {
                if ($useIndex) {
                    DB::statement("SET LOCAL enable_seqscan = on;");
                    DB::statement("SET LOCAL enable_indexscan = on;");
                    DB::statement("SET LOCAL enable_bitmapscan = on;");
                } else {
                    DB::statement("SET LOCAL enable_seqscan = on;");
                    DB::statement("SET LOCAL enable_indexscan = off;");
                    DB::statement("SET LOCAL enable_bitmapscan = off;");
                }

                $rawResults = DB::select("EXPLAIN (ANALYZE, FORMAT JSON) " . $sql);
                $planJson = $rawResults[0]->{'QUERY PLAN'} ?? $rawResults[0]->query_plan ?? null;

                if ($planJson) {
                    $decoded = json_decode($planJson, true);
                    if (! empty($decoded[0])) {
                        $totalTime += (float) ($decoded[0]['Execution Time'] ?? 0.0);
                        if ($i === 0) {
                            $primaryScan = $this->extractScanType($decoded[0]['Plan'] ?? []);
                        }
                    }
                }
            } finally {
                DB::rollBack();
            }
        }

        return [
            'execution_time' => $iterations > 0 ? $totalTime / $iterations : 0.0,
            'scan_type' => $primaryScan,
        ];
    }

    /**
     * Recursively extract the predominant scan node type from execution plan.
     */
    private function extractScanType(array $plan): string
    {
        $nodeType = $plan['Node Type'] ?? 'Unknown';

        if (in_array($nodeType, ['Seq Scan', 'Index Scan', 'Bitmap Index Scan', 'Bitmap Heap Scan'])) {
            return $nodeType;
        }

        if (! empty($plan['Plans'])) {
            foreach ($plan['Plans'] as $subPlan) {
                $subType = $this->extractScanType($subPlan);
                if ($subType !== 'Unknown') {
                    return $subType;
                }
            }
        }

        return $nodeType;
    }
}
