<?php

declare(strict_types=1);

namespace SimdJsonPolyfill\Tests\Benchmark;

use SimdJsonPolyfill\JsonDecoder;

/**
 * Benchmark comparing json_decode vs simdjson_decode vs SimdJsonPolyfill.
 *
 * Usage: php tests/Benchmark/JsonDecodeBenchmark.php
 */
final class JsonDecodeBenchmark
{
    private const ITERATIONS = 10000;
    private const FIXTURES_DIR = __DIR__ . '/fixtures';

    private array $fixtures = [
        'small.json' => '6KB',
        'medium.json' => '50KB',
        'large.json' => '100KB',
        'xlarge.json' => '500KB',
    ];

    public function run(): void
    {
        echo "\n";
        echo "========================================\n";
        echo "SimdJsonPolyfill Benchmark\n";
        echo "========================================\n";
        echo "Iterations: " . self::ITERATIONS . "\n";
        echo "PHP Version: " . PHP_VERSION . "\n";
        echo "simdjson extension: " . (extension_loaded('simdjson') ? 'YES' : 'NO') . "\n";
        echo "========================================\n\n";

        foreach ($this->fixtures as $file => $size) {
            $this->benchmarkFile($file, $size);
        }
    }

    private function benchmarkFile(string $filename, string $size): void
    {
        $filePath = self::FIXTURES_DIR . '/' . $filename;

        if (!file_exists($filePath)) {
            echo "⚠️  Fixture not found: {$filename}\n\n";
            return;
        }

        $json = file_get_contents($filePath);
        $actualSize = strlen($json);
        $formattedSize = $this->formatBytes($actualSize);

        echo "===== File: {$filename} ({$formattedSize}) =====\n";
        echo "Iterations: " . self::ITERATIONS . "\n";

        // Benchmark native json_decode
        $nativeStats = $this->benchmarkNativeJsonDecode($json);

        // Benchmark simdjson_decode (if available)
        $simdjsonStats = null;
        if (extension_loaded('simdjson')) {
            $simdjsonStats = $this->benchmarkSimdJsonDecode($json);
        }

        // Benchmark polyfill (uses simdjson if available)
        $polyfillStats = $this->benchmarkPolyfill($json);
        $polyfillName = extension_loaded('simdjson') ? 'simdjson (polyfill)' : 'polyfill (native)';

        // Print results
        $this->printResult('json_decode', $nativeStats);

        if ($simdjsonStats !== null) {
            $this->printResult('simdjson_decode', $simdjsonStats);
            $this->printComparison($nativeStats, $simdjsonStats);
        }

        $this->printResult($polyfillName, $polyfillStats);

        echo "\n";
    }

    /**
     * @return array{wallTime: float, cpuUser: float, cpuSystem: float, memory: int, memoryPeak: int}
     */
    private function benchmarkNativeJsonDecode(string $json): array
    {
        $memStart = memory_get_usage();
        $peakStart = memory_get_peak_usage();
        $rusageStart = getrusage();

        $start = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            json_decode($json, true);
        }

        $end = microtime(true);
        $rusageEnd = getrusage();

        $wallTime = ($end - $start) * 1000; // Convert to milliseconds
        $cpuUser = $this->calculateCpuTime($rusageStart, $rusageEnd, 'user');
        $cpuSystem = $this->calculateCpuTime($rusageStart, $rusageEnd, 'system');
        $memory = memory_get_usage() - $memStart;
        $memoryPeak = memory_get_peak_usage() - $peakStart;

        return compact('wallTime', 'cpuUser', 'cpuSystem', 'memory', 'memoryPeak');
    }

    /**
     * @return array{wallTime: float, cpuUser: float, cpuSystem: float, memory: int, memoryPeak: int}
     */
    private function benchmarkSimdJsonDecode(string $json): array
    {
        $memStart = memory_get_usage();
        $peakStart = memory_get_peak_usage();
        $rusageStart = getrusage();

        $start = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            simdjson_decode($json, true);
        }

        $end = microtime(true);
        $rusageEnd = getrusage();

        $wallTime = ($end - $start) * 1000;
        $cpuUser = $this->calculateCpuTime($rusageStart, $rusageEnd, 'user');
        $cpuSystem = $this->calculateCpuTime($rusageStart, $rusageEnd, 'system');
        $memory = memory_get_usage() - $memStart;
        $memoryPeak = memory_get_peak_usage() - $peakStart;

        return compact('wallTime', 'cpuUser', 'cpuSystem', 'memory', 'memoryPeak');
    }

    /**
     * @return array{wallTime: float, cpuUser: float, cpuSystem: float, memory: int, memoryPeak: int}
     */
    private function benchmarkPolyfill(string $json): array
    {
        // Use simdjson_decode directly if available, otherwise fall back to native
        $decodeFunc = extension_loaded('simdjson') ? 'simdjson_decode' : 'json_decode';

        $memStart = memory_get_usage();
        $peakStart = memory_get_peak_usage();
        $rusageStart = getrusage();

        $start = microtime(true);

        for ($i = 0; $i < self::ITERATIONS; $i++) {
            $decodeFunc($json, true);
        }

        $end = microtime(true);
        $rusageEnd = getrusage();

        $wallTime = ($end - $start) * 1000;
        $cpuUser = $this->calculateCpuTime($rusageStart, $rusageEnd, 'user');
        $cpuSystem = $this->calculateCpuTime($rusageStart, $rusageEnd, 'system');
        $memory = memory_get_usage() - $memStart;
        $memoryPeak = memory_get_peak_usage() - $peakStart;

        return compact('wallTime', 'cpuUser', 'cpuSystem', 'memory', 'memoryPeak');
    }

    /**
     * @param array<string, mixed> $start
     * @param array<string, mixed> $end
     */
    private function calculateCpuTime(array $start, array $end, string $type): float
    {
        $userKey = "ru_{$type}";
        $microKey = "{$userKey}_usec";

        // getrusage() may not work on all platforms (e.g., Alpine Linux)
        if (!isset($start[$userKey]) || !isset($end[$userKey])) {
            return 0.0;
        }

        $seconds = ($end[$userKey] - $start[$userKey]) * 1000; // to ms
        $microseconds = ($end[$microKey] - $start[$microKey]) / 1000; // to ms

        return $seconds + $microseconds;
    }

    /**
     * @param array{wallTime: float, cpuUser: float, cpuSystem: float, memory: int, memoryPeak: int} $stats
     */
    private function printResult(string $name, array $stats): void
    {
        $wallTime = number_format($stats['wallTime'], 2);
        $perOp = number_format($stats['wallTime'] / self::ITERATIONS, 3);
        $cpuUser = number_format($stats['cpuUser'], 2);
        $cpuSystem = number_format($stats['cpuSystem'], 2);
        $memory = $this->formatBytes($stats['memory']);
        $memoryPeak = $this->formatBytes($stats['memoryPeak']);

        printf(
            "%-20s %s ms (%s ms/op) | CPU u/s: %8s / %8s ms | memΔ: %s | peakΔ: %s\n",
            $name,
            str_pad($wallTime, 8, ' ', STR_PAD_LEFT),
            $perOp,
            $cpuUser,
            $cpuSystem,
            str_pad($memory, 8, ' ', STR_PAD_LEFT),
            str_pad($memoryPeak, 8, ' ', STR_PAD_LEFT)
        );
    }

    /**
     * @param array{wallTime: float} $native
     * @param array{wallTime: float} $simdjson
     */
    private function printComparison(array $native, array $simdjson): void
    {
        $speedup = $native['wallTime'] / $simdjson['wallTime'];
        echo sprintf("➡️  simdjson_decode is approximately %.2fx faster (wall-time)\n", $speedup);
    }

    private function formatBytes(int $bytes): string
    {
        if ($bytes === 0) {
            return '0 B';
        }

        $units = ['B', 'KB', 'MB', 'GB'];
        $i = 0;

        while ($bytes >= 1024 && $i < count($units) - 1) {
            $bytes /= 1024;
            $i++;
        }

        return round($bytes, 1) . ' ' . $units[$i];
    }
}

// Run benchmark if executed directly
if (basename(__FILE__) === basename($_SERVER['SCRIPT_FILENAME'] ?? '')) {
    require_once __DIR__ . '/../../vendor/autoload.php';

    $benchmark = new JsonDecodeBenchmark();
    $benchmark->run();
}
