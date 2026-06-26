<?php

namespace Helix\Console\Commands;

use Helix\Console\Command;

class CacheStatus extends Command
{
    protected string $signature   = 'cache:status';
    protected string $description = 'Show cache status and item count';

    public function handle(): int
    {
        global $wpdb;

        $enabled  = function_exists('config') ? (bool) config('cache.templates', false) : false;
        $expire   = function_exists('config') ? (int)  config('cache.expire', 3600)    : 3600;
        $themeDir = defined('THEME_DIR') ? THEME_DIR : getcwd();

        // Blade compiled files
        $viewCacheDir   = $themeDir . '/storage/cache/views';
        $compiledCount  = is_dir($viewCacheDir)
            ? count(glob($viewCacheDir . '/*.php') ?: [])
            : 0;

        // Output cache (transients)
        $transientCount = (int) $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*) FROM {$wpdb->options} WHERE option_name LIKE %s",
                '_transient_' . $wpdb->esc_like('helix_tpl_') . '%'
            )
        );

        $statusLabel = $enabled
            ? $this->green('● enabled')
            : $this->dim('○ disabled');

        $this->newLine();
        $this->line('  ' . $this->yellow('Status')   . '   ' . $statusLabel);
        $this->line('  ' . $this->yellow('Expire')   . '   ' . $this->cyan($expire . 's') . '  ' . $this->dim('(' . round($expire / 60) . ' min)'));
        $this->line('  ' . $this->yellow('Compiled') . '   ' . $this->cyan("{$compiledCount}") . $this->dim(' blade file(s)  →  storage/cache/views/'));
        $this->line('  ' . $this->yellow('Output')   . '   ' . $this->cyan("{$transientCount}") . $this->dim(' cached template(s)  →  wp transients'));
        $this->newLine();

        return 0;
    }
}
