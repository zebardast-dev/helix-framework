<?php

namespace Helix\Inspector\Collectors;

use Helix\Inspector\CollectorInterface;
use Helix\Inspector\Inspector;

class PerformanceCollector implements CollectorInterface
{
    private float $wpLoadedAt  = 0.0;
    private float $templateAt  = 0.0;

    public function name(): string  { return 'performance'; }
    public function title(): string { return 'Performance'; }
    public function icon(): string  { return '⚡'; }

    public function boot(): void
    {
        add_action('wp_loaded', function () {
            $this->wpLoadedAt = microtime(true);
        });

        add_filter('template_include', function ($template) {
            $this->templateAt = microtime(true);
            return $template;
        }, 9998);
    }

    public function collect(): array
    {
        $start = Inspector::getStartTime();

        return [
            'wp_loaded_ms'  => $this->wpLoadedAt  ? round(($this->wpLoadedAt  - $start) * 1000, 2) : null,
            'template_ms'   => $this->templateAt   ? round(($this->templateAt   - $start) * 1000, 2) : null,
            'memory'        => memory_get_usage(),
            'peak_memory'   => memory_get_peak_usage(),
            'memory_limit'  => ini_get('memory_limit'),
            'php_version'   => PHP_VERSION,
        ];
    }
}
