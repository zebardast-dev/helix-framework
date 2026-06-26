<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;

class QueryCollector implements CollectorInterface
{
    public function name(): string  { return 'queries'; }
    public function title(): string { return 'Queries'; }
    public function icon(): string  { return '🗄'; }

    public function boot(): void
    {
        if (!defined('SAVEQUERIES')) {
            define('SAVEQUERIES', true);
        }
    }

    public function collect(): array
    {
        global $wpdb;

        $savequeries = defined('SAVEQUERIES') && SAVEQUERIES;

        if (!$savequeries || empty($wpdb->queries)) {
            return [
                'enabled'    => $savequeries,
                'queries'    => [],
                'total'      => 0,
                'total_time' => 0,
                'duplicates' => 0,
                'slow'       => 0,
            ];
        }

        $queries   = [];
        $totalTime = 0.0;

        foreach ($wpdb->queries as $q) {
            $timeMs = round((float) ($q[1] ?? 0) * 1000, 3);
            $totalTime += $timeMs;
            $queries[] = [
                'sql'    => trim($q[0] ?? ''),
                'time'   => $timeMs,
                'caller' => $this->shortCaller($q[2] ?? ''),
            ];
        }

        $sqls  = array_column($queries, 'sql');
        $dupes = count(array_filter(array_count_values($sqls), fn($c) => $c > 1));
        $slow  = count(array_filter($queries, fn($q) => $q['time'] > 50));

        return [
            'enabled'    => true,
            'queries'    => $queries,
            'total'      => count($queries),
            'total_time' => round($totalTime, 2),
            'duplicates' => $dupes,
            'slow'       => $slow,
        ];
    }

    private function shortCaller(string $caller): string
    {
        $parts = array_map('trim', explode(',', $caller));

        // Walk from innermost caller outward (last item = most specific before wpdb)
        $reversed = array_reverse($parts);

        // Patterns to skip — WP infrastructure, not meaningful to the developer
        $skip = [
            'wpdb->',
            'WP_Hook->',
            'do_action',
            'apply_filters',
            'call_user_func',
            'require(',
            'require_once(',
            'include(',
            'include_once(',
        ];

        foreach ($reversed as $part) {
            if (!$part) continue;

            $ignore = false;
            foreach ($skip as $s) {
                if (str_contains($part, $s)) {
                    $ignore = true;
                    break;
                }
            }

            if (!$ignore) {
                return $part;
            }
        }

        // Fallback: first non-wpdb part
        foreach ($parts as $part) {
            if ($part && !str_contains($part, 'wpdb->')) {
                return $part;
            }
        }

        return $parts[0] ?? '';
    }
}
