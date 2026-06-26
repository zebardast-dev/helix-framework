<?php

namespace Helix\Inspector;

use Helix\Inspector\Collectors\EnvironmentCollector;
use Helix\Inspector\Collectors\ErrorCollector;
use Helix\Inspector\Collectors\HooksCollector;
use Helix\Inspector\Collectors\PerformanceCollector;
use Helix\Inspector\Collectors\QueryCollector;
use Helix\Inspector\Collectors\SeoCollector;
use Helix\Inspector\Collectors\ViewCollector;

class Inspector
{
    protected static array $collectors = [];
    protected static float $startTime  = 0.0;
    protected static bool  $booted     = false;

    protected static array $defaults = [
        'errors'      => ErrorCollector::class,
        'hooks'       => HooksCollector::class,
        'performance' => PerformanceCollector::class,
        'environment' => EnvironmentCollector::class,
        'queries'     => QueryCollector::class,
        'views'       => ViewCollector::class,
        'seo'         => SeoCollector::class,
    ];

    public static function boot(array $enabled = []): void
    {
        if (!static::shouldRun()) return;
        if (static::$booted) return;

        static::$startTime = defined('WP_START_TIMESTAMP')
            ? (float) WP_START_TIMESTAMP
            : microtime(true);

        static::$booted = true;

        if (empty($enabled)) {
            $enabled = array_keys(static::$defaults);
        }

        foreach ($enabled as $key) {
            if (!isset(static::$defaults[$key])) continue;
            try {
                $collector = new (static::$defaults[$key])();
                $collector->boot();
                static::$collectors[$key] = $collector;
            } catch (\Throwable $e) {
                // collector failed to boot — skip silently
            }
        }

        add_action('wp_footer',    [static::class, 'render'], 9999);
        add_action('admin_footer', [static::class, 'render'], 9999);
    }

    public static function getStartTime(): float
    {
        return static::$startTime;
    }

    protected static function shouldRun(): bool
    {
        // Primary gate: must be explicitly enabled in config
        $enabled = function_exists('config')
            ? (bool) config('inspector.enabled', false)
            : ((defined('WP_DEBUG') && WP_DEBUG) && (defined('APP_DEBUG') && APP_DEBUG));

        if (!$enabled) return false;

        // Safety net: never run if WP_DEBUG is off (catches misconfigured production)
        if (!defined('WP_DEBUG') || !WP_DEBUG) return false;

        // Safety net: block only if WP_ENVIRONMENT_TYPE is explicitly set to production/staging
        if (defined('WP_ENVIRONMENT_TYPE') && function_exists('wp_get_environment_type')) {
            $env = wp_get_environment_type();
            if (in_array($env, ['production', 'staging'], true)) return false;
        }

        return true;
    }

    public static function render(): void
    {
        if (!static::$booted) return;

        $now = microtime(true);

        $meta = [
            'start_time'  => static::$startTime,
            'render_time' => $now - static::$startTime,
            'memory'      => memory_get_usage(),
            'peak_memory' => memory_get_peak_usage(),
        ];

        $data = ['_meta' => $meta];

        foreach (static::$collectors as $key => $collector) {
            try {
                $data[$key] = $collector->collect();
            } catch (\Throwable $e) {
                $data[$key] = ['_error' => $e->getMessage(), '_file' => basename($e->getFile()) . ':' . $e->getLine()];
            }
        }

        $assetsDir = __DIR__ . '/assets/';
        $css = file_exists($assetsDir . 'inspector.css')
            ? file_get_contents($assetsDir . 'inspector.css')
            : '';
        $js = file_exists($assetsDir . 'inspector.js')
            ? file_get_contents($assetsDir . 'inspector.js')
            : '';

        $json = wp_json_encode($data);

        echo "\n<!-- Helix Inspector -->\n";
        echo '<div id="hxi-root">';
        echo '<script id="hxi-data" type="application/json">' . $json . '</script>';
        echo '<style>' . $css . '</style>';
        echo '<div id="hxi-panel" class="collapsed">';
        echo '<div id="hxi-content"><div id="hxi-panes"></div></div>';
        echo '<div id="hxi-bar">';
        echo '<div class="hxi-logo">⚡ <strong>Helix</strong></div>';
        echo '<nav id="hxi-tabs">';
        echo '<button class="hxi-tab active" data-tab="overview">📊 Overview</button>';

        foreach (static::$collectors as $key => $collector) {
            echo '<button class="hxi-tab" data-tab="' . esc_attr($key) . '">'
                . esc_html($collector->icon() . ' ' . $collector->title())
                . '</button>';
        }

        echo '</nav>';
        echo '<div id="hxi-meta-bar"></div>';
        echo '<button id="hxi-toggle" title="Toggle Inspector">▲</button>';
        echo '</div>'; // hxi-bar
        echo '</div>'; // hxi-panel
        echo '<script>' . $js . '</script>';
        echo '</div>'; // hxi-root
    }
}
